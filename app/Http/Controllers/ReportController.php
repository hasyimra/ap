<?php

namespace App\Http\Controllers;

use App\Models\ApInvoice;
use App\Models\ApPayment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function openPayables(): View
    {
        $invoices = ApInvoice::with('vendor')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->get()
            ->filter(fn (ApInvoice $invoice) => $invoice->owing > 0.009);

        $byVendor = $invoices->groupBy('vendor_id')->map(fn ($group) => [
            'vendor' => $group->first()->vendor,
            'invoices' => $group->sortBy('due_date')->values(),
            'total' => $group->sum('owing'),
        ])->sortBy(fn ($row) => $row['vendor']->name)->values();

        $grandTotal = $byVendor->sum('total');

        return view('reports.open-payables', compact('byVendor', 'grandTotal'));
    }

    public function agedPayables(): View
    {
        $byVendor = $this->buildAgedPayables();
        $grandTotal = $this->bucketGrandTotal($byVendor);

        return view('reports.aged-payables', compact('byVendor', 'grandTotal'));
    }

    public function agedPayablesSummary(): View
    {
        $byVendor = $this->buildAgedPayables();
        $grandTotal = $this->bucketGrandTotal($byVendor);

        return view('reports.aged-payables-summary', compact('byVendor', 'grandTotal'));
    }

    /**
     * Dipakai bersama oleh agedPayables() (detail per invoice) dan
     * agedPayablesSummary() (rollup per vendor saja) — query dan alokasi bucket-nya
     * identik, cuma view yang beda level detailnya.
     */
    private function buildAgedPayables()
    {
        $invoices = ApInvoice::with('vendor', 'lines.tax', 'allocations')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->get()
            ->filter(fn (ApInvoice $invoice) => $invoice->owing > 0.009);

        return $invoices->groupBy('vendor_id')->map(function ($group) {
            $buckets = ['current' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0, 'd90plus' => 0];

            foreach ($group as $invoice) {
                $owing = $invoice->owing;
                $age = $invoice->age;

                match (true) {
                    $age === 0 => $buckets['current'] += $owing,
                    $age <= 30 => $buckets['d30'] += $owing,
                    $age <= 60 => $buckets['d60'] += $owing,
                    $age <= 90 => $buckets['d90'] += $owing,
                    default => $buckets['d90plus'] += $owing,
                };
            }

            return [
                'vendor' => $group->first()->vendor,
                'invoices' => $group->sortBy('due_date')->values(),
                'buckets' => $buckets,
                'total' => array_sum($buckets),
            ];
        })->sortByDesc('total')->values();
    }

    private function bucketGrandTotal($byVendor): array
    {
        return [
            'current' => $byVendor->sum('buckets.current'),
            'd30' => $byVendor->sum('buckets.d30'),
            'd60' => $byVendor->sum('buckets.d60'),
            'd90' => $byVendor->sum('buckets.d90'),
            'd90plus' => $byVendor->sum('buckets.d90plus'),
        ];
    }

    public function apHistory(Request $request): View
    {
        $dateFrom = $request->date('date_from') ?? now()->startOfMonth();
        $dateTo = $request->date('date_to') ?? now()->endOfMonth();

        $invoiceRows = ApInvoice::with('vendor')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->get()
            ->map(fn (ApInvoice $i) => [
                'vendor_id' => $i->vendor_id,
                'vendor' => $i->vendor,
                'type' => 'invoice',
                'no' => $i->invoice_no,
                'date' => $i->invoice_date,
                'amount' => $i->total,
            ]);

        $paymentRows = ApPayment::with('vendor')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->get()
            ->map(fn (ApPayment $p) => [
                'vendor_id' => $p->vendor_id,
                'vendor' => $p->vendor,
                'type' => 'payment',
                'no' => $p->payment_no,
                'date' => $p->payment_date,
                'amount' => $p->amount,
            ]);

        $rows = $invoiceRows->concat($paymentRows);

        $byVendor = $rows->groupBy('vendor_id')->map(fn ($group) => [
            'vendor' => $group->first()['vendor'],
            'rows' => $group->sortBy('date')->values(),
        ])->sortBy(fn ($row) => $row['vendor']->name)->values();

        $summary = [
            'invoice_count' => $invoiceRows->count(),
            'invoice_total' => $invoiceRows->sum('amount'),
            'payment_count' => $paymentRows->count(),
            'payment_total' => $paymentRows->sum('amount'),
        ];

        return view('reports.ap-history', compact('byVendor', 'summary', 'dateFrom', 'dateTo'));
    }
}
