@extends('layouts.admin')

@section('title', $payment->payment_no)
@section('breadcrumb', $payment->payment_no)

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5>{{ $payment->payment_no }}</h5>
                        <span class="badge bg-{{ $payment->status_color }}">{{ $payment->status_label }}</span>
                    </div>
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-muted" style="width:160px">Vendor</td><td>{{ $payment->vendor->name }}</td></tr>
                        <tr><td class="text-muted">Bank</td><td>{{ $payment->bank?->name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">No. Referensi</td><td>{{ $payment->reference_no ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Tanggal</td><td>{{ $payment->payment_date->format('d/m/Y') }}</td></tr>
                        <tr><td class="text-muted">Jumlah</td><td>{{ number_format($payment->amount, 2) }}</td></tr>
                        <tr><td class="text-muted">Rekonsiliasi</td><td>{{ $payment->reconciled ? 'Sudah' : 'Belum' }}</td></tr>
                        <tr><td class="text-muted">Dibuat oleh</td><td>{{ $payment->createdBy?->name ?? '-' }}</td></tr>
                        @if($payment->approved_by)
                            <tr><td class="text-muted">Diproses oleh</td><td>{{ $payment->approvedBy?->name }} pada {{ $payment->approved_at?->format('d/m/Y H:i') }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6>Alokasi Invoice</h6>
                    <table class="table">
                        <thead>
                            <tr><th>No. Invoice</th><th>Bayar</th><th>Diskon</th><th>Write-off</th></tr>
                        </thead>
                        <tbody>
                            @foreach($payment->allocations as $allocation)
                                <tr>
                                    <td><a href="{{ route('ap-invoices.show', $allocation->invoice) }}">{{ $allocation->invoice->invoice_no }}</a></td>
                                    <td>{{ number_format($allocation->amount, 2) }}</td>
                                    <td>{{ number_format($allocation->disc_taken_amount, 2) }}</td>
                                    <td>{{ number_format($allocation->write_off_amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex flex-column gap-2">
                    <h6>Aksi</h6>

                    @if($payment->status === 'draft')
                        @canWrite
                            <form method="POST" action="{{ route('ap-payments.submit', $payment) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">Ajukan untuk Approval</button>
                            </form>
                            <form method="POST" action="{{ route('ap-payments.destroy', $payment) }}" onsubmit="return confirm('Hapus pembayaran ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger w-100">Hapus</button>
                            </form>
                        @endcanWrite
                    @endif

                    @if($payment->status === 'diajukan')
                        @canApprove
                            <form method="POST" action="{{ route('ap-payments.approve', $payment) }}">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">Setujui</button>
                            </form>
                            <form method="POST" action="{{ route('ap-payments.reject', $payment) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100">Tolak</button>
                            </form>
                        @endcanApprove
                    @endif

                    @if($payment->status === 'disetujui' && ! $payment->reconciled)
                        @canWrite
                            <form method="POST" action="{{ route('ap-payments.mark-reconciled', $payment) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary w-100">Tandai Sudah Rekonsiliasi</button>
                            </form>
                        @endcanWrite
                    @endif

                    <a href="{{ route('ap-payments.index') }}" class="btn btn-link">&larr; Kembali ke daftar</a>
                </div>
            </div>
        </div>
    </div>
@endsection
