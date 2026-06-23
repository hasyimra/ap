@extends('layouts.admin')

@section('title', 'Aged Payables Summary')
@section('breadcrumb', 'Aged Payables Summary')

@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Aged Payables Summary</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th class="text-end">Current</th>
                            <th class="text-end">1-30 Hari</th>
                            <th class="text-end">31-60 Hari</th>
                            <th class="text-end">61-90 Hari</th>
                            <th class="text-end">&gt;90 Hari</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byVendor as $row)
                            <tr>
                                <td>{{ $row['vendor']->name }}</td>
                                <td class="text-end">{{ number_format($row['buckets']['current'], 2) }}</td>
                                <td class="text-end">{{ number_format($row['buckets']['d30'], 2) }}</td>
                                <td class="text-end">{{ number_format($row['buckets']['d60'], 2) }}</td>
                                <td class="text-end">{{ number_format($row['buckets']['d90'], 2) }}</td>
                                <td class="text-end {{ $row['buckets']['d90plus'] > 0 ? 'text-danger fw-bold' : '' }}">{{ number_format($row['buckets']['d90plus'], 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($row['total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Tidak ada hutang outstanding.</td></tr>
                        @endforelse
                    </tbody>
                    @if($byVendor->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Grand Total</td>
                                <td class="text-end">{{ number_format($grandTotal['current'], 2) }}</td>
                                <td class="text-end">{{ number_format($grandTotal['d30'], 2) }}</td>
                                <td class="text-end">{{ number_format($grandTotal['d60'], 2) }}</td>
                                <td class="text-end">{{ number_format($grandTotal['d90'], 2) }}</td>
                                <td class="text-end">{{ number_format($grandTotal['d90plus'], 2) }}</td>
                                <td class="text-end">{{ number_format(array_sum($grandTotal), 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
