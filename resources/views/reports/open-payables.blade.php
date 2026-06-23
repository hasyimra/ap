@extends('layouts.admin')

@section('title', 'Open Payables')
@section('breadcrumb', 'Open Payables')

@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Open Payables</h5>
            @forelse($byVendor as $row)
                <div class="mb-4">
                    <h6 class="mb-2">{{ $row['vendor']->name }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>No. Invoice</th>
                                    <th>Tanggal</th>
                                    <th>Jatuh Tempo</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Dibayar</th>
                                    <th class="text-end">Owing</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($row['invoices'] as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_no }}</td>
                                        <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                        <td>{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="text-end">{{ number_format($invoice->total, 2) }}</td>
                                        <td class="text-end">{{ number_format($invoice->paid_amount, 2) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($invoice->owing, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="5">Subtotal Vendor</td>
                                    <td class="text-end">{{ number_format($row['total'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @empty
                <p class="text-center text-muted">Tidak ada hutang outstanding.</p>
            @endforelse

            @if($byVendor->isNotEmpty())
                <div class="text-end fw-bold border-top border-2 pt-2">
                    Grand Total: {{ number_format($grandTotal, 2) }}
                </div>
            @endif
        </div>
    </div>
@endsection
