<?php

namespace App\Http\Controllers;

use App\Models\ApInvoice;
use App\Models\ApPayment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $openInvoices = ApInvoice::with('lines.tax', 'allocations')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->get()
            ->filter(fn (ApInvoice $invoice) => $invoice->owing > 0.009);

        return view('dashboard', [
            'invoiceDraftCount' => ApInvoice::whereIn('status', ['draft', 'diajukan'])->count(),
            'paymentDraftCount' => ApPayment::whereIn('status', ['draft', 'diajukan'])->count(),
            'totalOutstanding' => $openInvoices->sum('owing'),
            'overdueCount' => $openInvoices->filter(fn (ApInvoice $invoice) => $invoice->age > 0)->count(),
        ]);
    }
}
