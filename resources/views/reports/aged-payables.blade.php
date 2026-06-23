@extends('layouts.admin')

@section('title', 'Aged Payables')
@section('breadcrumb', 'Aged Payables')

@section('content')
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Aged Payables</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Vendor</th>
                            <th>Current</th>
                            <th>1-30 Hari</th>
                            <th>31-60 Hari</th>
                            <th>61-90 Hari</th>
                            <th>&gt;90 Hari</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byVendor as $row)
                            <tr>
                                <td>{{ $row['vendor']->name }}</td>
                                <td>{{ number_format($row['buckets']['current'], 2) }}</td>
                                <td>{{ number_format($row['buckets']['d30'], 2) }}</td>
                                <td>{{ number_format($row['buckets']['d60'], 2) }}</td>
                                <td>{{ number_format($row['buckets']['d90'], 2) }}</td>
                                <td class="text-danger">{{ number_format($row['buckets']['d90plus'], 2) }}</td>
                                <td><strong>{{ number_format($row['total'], 2) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Tidak ada hutang outstanding.</td></tr>
                        @endforelse
                    </tbody>
                    @if($byVendor->isNotEmpty())
                        <tfoot>
                            <tr>
                                <th>Grand Total</th>
                                <th>{{ number_format($grandTotal['current'], 2) }}</th>
                                <th>{{ number_format($grandTotal['d30'], 2) }}</th>
                                <th>{{ number_format($grandTotal['d60'], 2) }}</th>
                                <th>{{ number_format($grandTotal['d90'], 2) }}</th>
                                <th>{{ number_format($grandTotal['d90plus'], 2) }}</th>
                                <th>{{ number_format(array_sum($grandTotal), 2) }}</th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
