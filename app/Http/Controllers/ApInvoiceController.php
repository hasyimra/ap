<?php

namespace App\Http\Controllers;

use App\Models\ApInvoice;
use App\Models\GlAccount;
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

        $invoice->update(['status' => 'disetujui', 'approved_by' => Auth::id(), 'approved_at' => now()]);

        return back()->with('success', 'AP Invoice disetujui.');
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
