@extends('layouts.admin')

@section('title', 'AP Invoices')
@section('breadcrumb', 'AP Invoices')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Cari no. invoice..." style="width:220px">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                </form>
                @canWrite
                    <a href="{{ route('ap-invoices.create') }}" class="btn btn-primary">
                        <i data-feather="plus"></i> Buat AP Invoice
                    </a>
                @endcanWrite
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Vendor</th>
                            <th>Tanggal</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr style="cursor:pointer" onclick="window.location='{{ route('ap-invoices.show', $invoice) }}'">
                                <td>{{ $invoice->invoice_no }}</td>
                                <td>{{ $invoice->vendor->name }}</td>
                                <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                <td>{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td>
                                <td><span class="badge bg-{{ $invoice->status_color }}">{{ $invoice->status_label }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Belum ada AP Invoice.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $invoices->links() }}
        </div>
    </div>
@endsection
