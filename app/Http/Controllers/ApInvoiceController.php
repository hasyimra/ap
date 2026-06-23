<?php

namespace App\Http\Controllers;

use App\Models\ApInvoice;
use App\Models\GlAccount;
use App\Models\GlJournal;
use App\Models\GlSetting;
use App\Models\PurchaseOrder;
use App\Models\Tax;
use App\Models\Vendor;
use App\Services\AutoNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = ApInvoice::with('vendor')
            ->when($request->search, fn ($q, $search) => $q->where('invoice_no', 'like', "%{$search}%"))
            ->orderByDesc('invoice_date')
            ->paginate(20)
            ->withQueryString();

        return view('ap-invoices.index', compact('invoices'));
    }

    public function create(): View
    {
        return view('ap-invoices.form', [
            'invoice' => new ApInvoice(),
            'vendors' => Vendor::where('is_active', true)->orderBy('name')->get(),
            'purchaseOrders' => PurchaseOrder::whereIn('status', ['disetujui', 'selesai'])->orderByDesc('order_date')->get(),
            'glAccounts' => GlAccount::where('is_active', true)->orderBy('code')->get(),
            'taxes' => Tax::where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $invoice = DB::transaction(function () use ($data) {
            $invoice = ApInvoice::create([
                'invoice_no' => app(AutoNumberService::class)->generate('ap_invoice'),
                'vendor_id' => $data['vendor_id'],
                'description' => $data['description'] ?? null,
                'prc_purchase_order_id' => $data['prc_purchase_order_id'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'pph_type' => $data['pph_type'] ?? 'none',
                'pph_rate' => $data['pph_rate'] ?? 0,
                'withholding_cert_no' => $data['withholding_cert_no'] ?? null,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            $invoice->lines()->createMany($data['lines']);
            $this->recalculatePph($invoice);

            return $invoice;
        });

        return redirect()->route('ap-invoices.show', $invoice)->with('success', 'AP Invoice berhasil dibuat.');
    }

    public function show(ApInvoice $invoice): View
    {
        $invoice->load(['vendor', 'purchaseOrder', 'lines.glAccount', 'lines.tax', 'allocations.payment', 'createdBy', 'approvedBy']);

        return view('ap-invoices.show', compact('invoice'));
    }

    public function edit(ApInvoice $invoice): View
    {
        abort_if($invoice->status !== 'draft', 403, 'Hanya invoice draft yang bisa diedit.');

        return view('ap-invoices.form', [
            'invoice' => $invoice->load('lines'),
            'vendors' => Vendor::where('is_active', true)->orderBy('name')->get(),
            'purchaseOrders' => PurchaseOrder::whereIn('status', ['disetujui', 'selesai'])->orderByDesc('order_date')->get(),
            'glAccounts' => GlAccount::where('is_active', true)->orderBy('code')->get(),
            'taxes' => Tax::where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, ApInvoice $invoice): RedirectResponse
    {
        abort_if($invoice->status !== 'draft', 403, 'Hanya invoice draft yang bisa diedit.');

        $data = $this->validateData($request);

        DB::transaction(function () use ($invoice, $data) {
            $invoice->update([
                'vendor_id' => $data['vendor_id'],
                'description' => $data['description'] ?? null,
                'prc_purchase_order_id' => $data['prc_purchase_order_id'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'pph_type' => $data['pph_type'] ?? 'none',
                'pph_rate' => $data['pph_rate'] ?? 0,
                'withholding_cert_no' => $data['withholding_cert_no'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            $invoice->lines()->delete();
            $invoice->lines()->createMany($data['lines']);
            $this->recalculatePph($invoice);
        });

        return redirect()->route('ap-invoices.show', $invoice)->with('success', 'AP Invoice berhasil diperbarui.');
    }

    public function destroy(ApInvoice $invoice): RedirectResponse
    {
        abort_if($invoice->status !== 'draft', 403, 'Hanya invoice draft yang bisa dihapus.');

        $invoice->delete();

        return redirect()->route('ap-invoices.index')->with('success', 'AP Invoice berhasil dihapus.');
    }

    public function submit(ApInvoice $invoice): RedirectResponse
    {
        abort_if($invoice->status !== 'draft', 403);

        $invoice->update(['status' => 'diajukan', 'updated_by' => Auth::id()]);

        return back()->with('success', 'AP Invoice diajukan untuk approval.');
    }

    public function approve(ApInvoice $invoice): RedirectResponse
    {
        abort_if($invoice->status !== 'diajukan', 403);

        DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => 'disetujui', 'approved_by' => Auth::id(), 'approved_at' => now()]);
            $this->postInvoiceJournal($invoice);
        });

        return back()->with('success', 'AP Invoice disetujui.');
    }

    /**
     * Dr {line.gl_account_id} per line + Dr PPN Masukan (tax per-line, beda dari AR yang flat
     * rate) = Cr AP Control (total - pph_amount) + Cr PPh Payable (pph_amount, kalau ada —
     * dipotong langsung dari yang dibayar ke vendor, disetor sendiri ke kas negara).
     */
    private function postInvoiceJournal(ApInvoice $invoice): void
    {
        $invoice->load('lines.tax');

        $apControlId = GlSetting::where('key', 'ap_control')->first()?->gl_account_id;
        if (! $apControlId) {
            throw new \RuntimeException('GL Account untuk AP Control belum diatur di GL Settings.');
        }

        $lines = [];

        foreach ($invoice->lines as $line) {
            $lines[] = [
                'gl_account_id' => $line->gl_account_id,
                'debit' => round($line->amount, 2),
                'credit' => 0,
                'description' => $line->description,
            ];

            if ($line->tax) {
                $taxAmount = round($line->amount * $line->tax->rate / 100, 2);

                if ($taxAmount > 0) {
                    $taxAccountId = $line->tax->gl_account_id;
                    if (! $taxAccountId) {
                        throw new \RuntimeException("Pajak {$line->tax->name} belum punya GL Account, lengkapi dulu di master pajak.");
                    }

                    $lines[] = ['gl_account_id' => $taxAccountId, 'debit' => $taxAmount, 'credit' => 0, 'description' => 'PPN Masukan - '.$line->description];
                }
            }
        }

        $lines[] = [
            'gl_account_id' => $apControlId,
            'debit' => 0,
            'credit' => round($invoice->total - $invoice->pph_amount, 2),
            'description' => 'AP - '.$invoice->invoice_no,
        ];

        if ($invoice->pph_amount > 0) {
            $pphAccountId = GlSetting::where('key', 'pph_payable')->first()?->gl_account_id;
            if (! $pphAccountId) {
                throw new \RuntimeException('GL Account untuk PPh Payable belum diatur di GL Settings.');
            }

            $lines[] = ['gl_account_id' => $pphAccountId, 'debit' => 0, 'credit' => round($invoice->pph_amount, 2), 'description' => 'PPh - '.$invoice->invoice_no];
        }

        GlJournal::postBalanced([
            'journal_date' => $invoice->invoice_date,
            'description' => 'AP Invoice '.$invoice->invoice_no,
            'source_type' => 'ap_invoice',
            'source_id' => $invoice->id,
            'created_by' => Auth::id(),
        ], $lines);
    }

    public function reject(ApInvoice $invoice): RedirectResponse
    {
        abort_if($invoice->status !== 'diajukan', 403);

        $invoice->update(['status' => 'ditolak', 'approved_by' => Auth::id(), 'approved_at' => now()]);

        return back()->with('success', 'AP Invoice ditolak.');
    }

    /**
     * pph_amount dihitung dari DPP (= subtotal baris GL, sebelum PPN) × pph_rate.
     * Ini hanya berlaku kalau pph_type bukan 'none'.
     */
    private function recalculatePph(ApInvoice $invoice): void
    {
        $invoice->load('lines');

        if ($invoice->pph_type === 'none') {
            $invoice->update(['pph_amount' => 0]);

            return;
        }

        $dpp = $invoice->subtotal;
        $invoice->update(['pph_amount' => round($dpp * $invoice->pph_rate / 100, 2)]);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'description' => 'nullable|string|max:255',
            'prc_purchase_order_id' => 'nullable|exists:prc_purchase_orders,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'pph_type' => 'nullable|in:none,pph23,pph22',
            'pph_rate' => 'nullable|numeric|min:0|max:100',
            'withholding_cert_no' => 'nullable|string|max:50',
            'lines' => 'required|array|min:1',
            'lines.*.gl_account_id' => 'required|exists:gl_accounts,id',
            'lines.*.tax_id' => 'nullable|exists:taxes,id',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.amount' => 'required|numeric|min:0.01',
        ]);
    }
}
