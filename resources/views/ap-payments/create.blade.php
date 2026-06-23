@extends('layouts.admin')

@section('title', 'Buat Pembayaran')
@section('breadcrumb', 'Buat Pembayaran')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-select" onchange="this.form.submit()">
                        <option value="">- Pilih Vendor -</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected($selectedVendorId === $vendor->id)>{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($selectedVendorId)
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('ap-payments.store') }}">
                    @csrf
                    <input type="hidden" name="vendor_id" value="{{ $selectedVendorId }}">

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Bank</label>
                            <select name="bank_id" class="form-select">
                                <option value="">- Pilih Bank -</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">No. Referensi (cek/transfer)</label>
                            <input type="text" name="reference_no" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Bayar</label>
                            <input type="date" name="payment_date" class="form-control" required value="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Jumlah Total Dibayar</label>
                            <input type="number" step="0.01" name="amount" id="totalAmount" class="form-control" required>
                        </div>
                    </div>

                    <h6>Alokasi ke Invoice</h6>
                    @if($outstandingInvoices->isEmpty())
                        <p class="text-muted">Tidak ada invoice outstanding untuk vendor ini.</p>
                    @else
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>No. Invoice</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Owing</th>
                                    <th style="width:14%">Bayar</th>
                                    <th style="width:14%">Diskon</th>
                                    <th style="width:14%">Write-off</th>
                                    <th style="width:16%">GL Write-off</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($outstandingInvoices as $invoice)
                                    <tr>
                                        <td>
                                            {{ $invoice->invoice_no }}
                                            <input type="hidden" name="allocations[{{ $loop->index }}][ap_invoice_id]" value="{{ $invoice->id }}">
                                        </td>
                                        <td>{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td>{{ number_format($invoice->owing, 2) }}</td>
                                        <td><input type="number" step="0.01" min="0" max="{{ $invoice->owing }}" name="allocations[{{ $loop->index }}][amount]" class="form-control form-control-sm alloc-amount" value="0"></td>
                                        <td><input type="number" step="0.01" min="0" name="allocations[{{ $loop->index }}][disc_taken_amount]" class="form-control form-control-sm"></td>
                                        <td><input type="number" step="0.01" min="0" name="allocations[{{ $loop->index }}][write_off_amount]" class="form-control form-control-sm"></td>
                                        <td>
                                            <select name="allocations[{{ $loop->index }}][write_off_gl_account_id]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach($glAccounts as $gl)
                                                    <option value="{{ $gl->id }}">{{ $gl->code }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <button type="submit" class="btn btn-primary">Simpan Pembayaran</button>
                        <a href="{{ route('ap-payments.index') }}" class="btn btn-outline-secondary">Batal</a>
                    @endif
                </form>
            </div>
        </div>
    @endif
@endsection
