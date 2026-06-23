@extends('layouts.admin')

@section('title', 'AP Payments')
@section('breadcrumb', 'AP Payments')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">AP Payments</h5>
                @canWrite
                    <a href="{{ route('ap-payments.create') }}" class="btn btn-primary">
                        <i data-feather="plus"></i> Buat Pembayaran
                    </a>
                @endcanWrite
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No. Payment</th>
                            <th>Vendor</th>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Rekonsiliasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr style="cursor:pointer" onclick="window.location='{{ route('ap-payments.show', $payment) }}'">
                                <td>{{ $payment->payment_no }}</td>
                                <td>{{ $payment->vendor->name }}</td>
                                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                <td>{{ number_format($payment->amount, 2) }}</td>
                                <td><span class="badge bg-{{ $payment->status_color }}">{{ $payment->status_label }}</span></td>
                                <td>{{ $payment->reconciled ? 'Sudah' : 'Belum' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Belum ada pembayaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $payments->links() }}
        </div>
    </div>
@endsection
