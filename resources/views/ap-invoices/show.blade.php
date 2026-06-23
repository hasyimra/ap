@extends('layouts.admin')

@section('title', $invoice->invoice_no)
@section('breadcrumb', $invoice->invoice_no)

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5>{{ $invoice->invoice_no }}</h5>
                        <span class="badge bg-{{ $invoice->status_color }}">{{ $invoice->status_label }}</span>
                    </div>
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-muted" style="width:160px">Vendor</td><td>{{ $invoice->vendor->name }}</td></tr>
                        <tr><td class="text-muted">PO Terkait</td><td>{{ $invoice->purchaseOrder?->po_no ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Deskripsi</td><td>{{ $invoice->description ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Tanggal</td><td>{{ $invoice->invoice_date->format('d/m/Y') }}</td></tr>
                        <tr><td class="text-muted">Jatuh Tempo</td><td>{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td></tr>
                        @if($invoice->pph_type !== 'none')
                            <tr><td class="text-muted">PPh</td><td>{{ strtoupper($invoice->pph_type) }} ({{ $invoice->pph_rate }}%) = {{ number_format($invoice->pph_amount, 2) }} — Bukti Potong: {{ $invoice->withholding_cert_no ?? '-' }}</td></tr>
                        @endif
                        <tr><td class="text-muted">Dibuat oleh</td><td>{{ $invoice->createdBy?->name ?? '-' }}</td></tr>
                        @if($invoice->approved_by)
                            <tr><td class="text-muted">Diproses oleh</td><td>{{ $invoice->approvedBy?->name }} pada {{ $invoice->approved_at?->format('d/m/Y H:i') }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6>Baris Beban</h6>
                    <table class="table">
                        <thead>
                            <tr><th>GL Account</th><th>Deskripsi</th><th>Tax</th><th>Jumlah</th></tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->lines as $line)
                                <tr>
                                    <td>{{ $line->glAccount->code }} - {{ $line->glAccount->name }}</td>
                                    <td>{{ $line->description ?? '-' }}</td>
                                    <td>{{ $line->tax ? $line->tax->code.' ('.$line->tax->rate.'%)' : '-' }}</td>
                                    <td>{{ number_format($line->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr><th colspan="3" class="text-end">Subtotal</th><th>{{ number_format($invoice->subtotal, 2) }}</th></tr>
                            <tr><th colspan="3" class="text-end">PPN Masukan</th><th>{{ number_format($invoice->tax_amount, 2) }}</th></tr>
                            <tr><th colspan="3" class="text-end">Total</th><th>{{ number_format($invoice->total, 2) }}</th></tr>
                            @if($invoice->pph_amount > 0)
                                <tr><th colspan="3" class="text-end">PPh Dipotong</th><th>-{{ number_format($invoice->pph_amount, 2) }}</th></tr>
                            @endif
                            <tr><th colspan="3" class="text-end">Sisa Dibayar (Owing)</th><th>{{ number_format($invoice->owing, 2) }}</th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex flex-column gap-2">
                    <h6>Aksi</h6>

                    @if($invoice->status === 'draft')
                        @canWrite
                            <a href="{{ route('ap-invoices.edit', $invoice) }}" class="btn btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('ap-invoices.submit', $invoice) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">Ajukan untuk Approval</button>
                            </form>
                            <form method="POST" action="{{ route('ap-invoices.destroy', $invoice) }}" onsubmit="return confirm('Hapus AP Invoice ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger w-100">Hapus</button>
                            </form>
                        @endcanWrite
                    @endif

                    @if($invoice->status === 'diajukan')
                        @canApprove
                            <form method="POST" action="{{ route('ap-invoices.approve', $invoice) }}">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">Setujui</button>
                            </form>
                            <form method="POST" action="{{ route('ap-invoices.reject', $invoice) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100">Tolak</button>
                            </form>
                        @endcanApprove
                    @endif

                    <a href="{{ route('ap-invoices.index') }}" class="btn btn-link">&larr; Kembali ke daftar</a>
                </div>
            </div>
        </div>
    </div>
@endsection
