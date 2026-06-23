<?php

namespace App\Http\Controllers;

use App\Models\ApInvoice;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function agedPayables(): View
    {
        $invoices = ApInvoice::with('vendor', 'lines.tax', 'allocations')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->get()
            ->filter(fn (ApInvoice $invoice) => $invoice->owing > 0.009);

        $byVendor = $invoices->groupBy('vendor_id')->map(function ($group) {
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
                'invoices' => $group->values(),
                'buckets' => $buckets,
                'total' => array_sum($buckets),
            ];
        })->sortByDesc('total')->values();

        $grandTotal = [
            'current' => $byVendor->sum('buckets.current'),
            'd30' => $byVendor->sum('buckets.d30'),
            'd60' => $byVendor->sum('buckets.d60'),
            'd90' => $byVendor->sum('buckets.d90'),
            'd90plus' => $byVendor->sum('buckets.d90plus'),
        ];

        return view('reports.aged-payables', compact('byVendor', 'grandTotal'));
    }
}
