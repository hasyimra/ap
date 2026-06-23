<?php

namespace App\Http\Controllers;

use App\Models\ApInvoice;
use App\Models\ApPayment;
use App\Models\Bank;
use App\Models\GlAccount;
use App\Models\Vendor;
use App\Services\AutoNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApPaymentController extends Controller
{
    public function index(): View
    {
        $payments = ApPayment::with('vendor')->orderByDesc('payment_date')->paginate(20);

        return view('ap-payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $selectedVendorId = $request->integer('vendor_id') ?: null;

        $outstandingInvoices = collect();
        if ($selectedVendorId) {
            $outstandingInvoices = ApInvoice::where('vendor_id', $selectedVendorId)
                ->whereIn('status', ['disetujui', 'selesai'])
                ->with('lines.tax', 'allocations')
                ->get()
                ->filter(fn (ApInvoice $invoice) => $invoice->owing > 0.009)
                ->values();
        }

        return view('ap-payments.create', [
            'vendors' => $vendors,
            'banks' => Bank::where('is_active', true)->orderBy('name')->get(),
            'glAccounts' => GlAccount::where('is_active', true)->orderBy('code')->get(),
            'selectedVendorId' => $selectedVendorId,
            'outstandingInvoices' => $outstandingInvoices,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'bank_id' => 'nullable|exists:banks,id',
            'reference_no' => 'nullable|string|max:50',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'allocations' => 'required|array|min:1',
            'allocations.*.ap_invoice_id' => 'required|exists:ap_invoices,id',
            'allocations.*.amount' => 'nullable|numeric|min:0',
            'allocations.*.disc_taken_amount' => 'nullable|numeric|min:0',
            'allocations.*.write_off_amount' => 'nullable|numeric|min:0',
            'allocations.*.write_off_gl_account_id' => 'nullable|exists:gl_accounts,id',
        ]);

        $allocations = collect($data['allocations'])
            ->map(fn ($a) => [
                'ap_invoice_id' => $a['ap_invoice_id'],
                'amount' => $a['amount'] ?? 0,
                'disc_taken_amount' => $a['disc_taken_amount'] ?? 0,
                'write_off_amount' => $a['write_off_amount'] ?? 0,
                'write_off_gl_account_id' => ($a['write_off_amount'] ?? 0) > 0 ? ($a['write_off_gl_account_id'] ?? null) : null,
            ])
            ->filter(fn ($a) => ($a['amount'] + $a['disc_taken_amount'] + $a['write_off_amount']) > 0)
            ->values();

        if ($allocations->isEmpty()) {
            return back()->withInput()->with('error', 'Minimal satu invoice harus dialokasikan.');
        }

        // Hanya porsi "amount" (tunai/transfer) yang harus dicocokkan ke jumlah pembayaran —
        // disc_taken/write_off mengurangi owing di sisi invoice, bukan bagian dari uang yang
        // benar-benar keluar dari kas/bank, jadi tidak ikut dibandingkan ke $data['amount'].
        $totalCashAllocated = $allocations->sum('amount');
        if ($totalCashAllocated > $data['amount'] + 0.01) {
            return back()->withInput()->with('error', 'Total alokasi tunai tidak boleh melebihi jumlah pembayaran.');
        }

        $payment = DB::transaction(function () use ($data, $allocations) {
            $payment = ApPayment::create([
                'payment_no' => app(AutoNumberService::class)->generate('ap_payment'),
                'vendor_id' => $data['vendor_id'],
                'bank_id' => $data['bank_id'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'payment_date' => $data['payment_date'],
                'amount' => $data['amount'],
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            $payment->allocations()->createMany($allocations->all());

            return $payment;
        });

        return redirect()->route('ap-payments.show', $payment)->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function show(ApPayment $payment): View
    {
        $payment->load(['vendor', 'bank', 'allocations.invoice', 'createdBy', 'approvedBy']);

        return view('ap-payments.show', compact('payment'));
    }

    public function submit(ApPayment $payment): RedirectResponse
    {
        abort_if($payment->status !== 'draft', 403);

        $payment->update(['status' => 'diajukan', 'updated_by' => Auth::id()]);

        return back()->with('success', 'Pembayaran diajukan untuk approval.');
    }

    public function approve(ApPayment $payment): RedirectResponse
    {
        abort_if($payment->status !== 'diajukan', 403);

        $payment->update(['status' => 'disetujui', 'approved_by' => Auth::id(), 'approved_at' => now()]);

        return back()->with('success', 'Pembayaran disetujui.');
    }

    public function reject(ApPayment $payment): RedirectResponse
    {
        abort_if($payment->status !== 'diajukan', 403);

        $payment->update(['status' => 'ditolak', 'approved_by' => Auth::id(), 'approved_at' => now()]);

        return back()->with('success', 'Pembayaran ditolak.');
    }

    public function markReconciled(ApPayment $payment): RedirectResponse
    {
        $payment->update(['reconciled' => true, 'updated_by' => Auth::id()]);

        return back()->with('success', 'Pembayaran ditandai sudah direkonsiliasi dengan rekening bank.');
    }

    public function destroy(ApPayment $payment): RedirectResponse
    {
        abort_if($payment->status !== 'draft', 403, 'Hanya pembayaran draft yang bisa dihapus.');

        $payment->delete();

        return redirect()->route('ap-payments.index')->with('success', 'Pembayaran berhasil dihapus.');
    }
}
