@extends('layouts.admin')

@section('title', $invoice->exists ? 'Edit AP Invoice' : 'Buat AP Invoice')
@section('breadcrumb', $invoice->exists ? 'Edit AP Invoice' : 'Buat AP Invoice')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $invoice->exists ? route('ap-invoices.update', $invoice) : route('ap-invoices.store') }}">
                @csrf
                @if($invoice->exists) @method('PUT') @endif

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-select" required>
                            <option value="">- Pilih Vendor -</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected($invoice->vendor_id === $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PO Terkait (opsional, referensi saja)</label>
                        <select name="prc_purchase_order_id" class="form-select">
                            <option value="">- Tanpa PO -</option>
                            @foreach($purchaseOrders as $po)
                                <option value="{{ $po->id }}" @selected($invoice->prc_purchase_order_id === $po->id)>{{ $po->po_no }} - {{ $po->vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" name="description" class="form-control" value="{{ $invoice->description }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Invoice</label>
                        <input type="date" name="invoice_date" class="form-control" required
                               value="{{ $invoice->invoice_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jatuh Tempo</label>
                        <input type="date" name="due_date" class="form-control" value="{{ $invoice->due_date?->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">PPh</label>
                        <select name="pph_type" class="form-select">
                            <option value="none" @selected(($invoice->pph_type ?? 'none') === 'none')>Tidak ada</option>
                            <option value="pph23" @selected($invoice->pph_type === 'pph23')>PPh 23</option>
                            <option value="pph22" @selected($invoice->pph_type === 'pph22')>PPh 22</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tarif PPh (%)</label>
                        <input type="number" step="0.01" name="pph_rate" class="form-control" value="{{ $invoice->pph_rate ?? 0 }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">No. Bukti Potong</label>
                        <input type="text" name="withholding_cert_no" class="form-control" value="{{ $invoice->withholding_cert_no }}">
                    </div>
                </div>

                <h6>Baris Beban (GL Account)</h6>
                <table class="table" id="line-table">
                    <thead>
                        <tr>
                            <th style="width:25%">GL Account</th>
                            <th style="width:30%">Deskripsi</th>
                            <th style="width:20%">Tax (PPN Masukan)</th>
                            <th style="width:15%">Jumlah</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->lines ?? [] as $line)
                            <tr class="line-row">
                                <td>
                                    <select name="lines[][gl_account_id]" class="form-select" required>
                                        <option value="">- Pilih GL Account -</option>
                                        @foreach($glAccounts as $gl)
                                            <option value="{{ $gl->id }}" @selected($line->gl_account_id === $gl->id)>{{ $gl->code }} - {{ $gl->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="lines[][description]" class="form-control" value="{{ $line->description }}"></td>
                                <td>
                                    <select name="lines[][tax_id]" class="form-select">
                                        <option value="">- Tanpa Tax -</option>
                                        @foreach($taxes as $tax)
                                            <option value="{{ $tax->id }}" @selected($line->tax_id === $tax->id)>{{ $tax->code }} ({{ $tax->rate }}%)</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="lines[][amount]" class="form-control" value="{{ $line->amount }}" required></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i data-feather="trash-2"></i></button></td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
                <button type="button" id="add-row" class="btn btn-sm btn-outline-secondary mb-3">
                    <i data-feather="plus"></i> Tambah Baris
                </button>

                <div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ $invoice->exists ? route('ap-invoices.show', $invoice) : route('ap-invoices.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <template id="line-row-template">
        <tr class="line-row">
            <td>
                <select name="lines[][gl_account_id]" class="form-select" required>
                    <option value="">- Pilih GL Account -</option>
                    @foreach($glAccounts as $gl)
                        <option value="{{ $gl->id }}">{{ $gl->code }} - {{ $gl->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="text" name="lines[][description]" class="form-control"></td>
            <td>
                <select name="lines[][tax_id]" class="form-select">
                    <option value="">- Tanpa Tax -</option>
                    @foreach($taxes as $tax)
                        <option value="{{ $tax->id }}">{{ $tax->code }} ({{ $tax->rate }}%)</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" step="0.01" name="lines[][amount]" class="form-control" value="0" required></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i data-feather="trash-2"></i></button></td>
        </tr>
    </template>

    @push('scripts')
    <script>
        document.getElementById('add-row').addEventListener('click', function () {
            const template = document.getElementById('line-row-template').content.cloneNode(true);
            document.querySelector('#line-table tbody').appendChild(template);
            if (window.feather) feather.replace();
        });
        document.getElementById('line-table').addEventListener('click', function (e) {
            if (e.target.closest('.remove-row')) {
                e.target.closest('.line-row').remove();
            }
        });
        if (document.querySelectorAll('.line-row').length === 0) {
            document.getElementById('add-row').click();
        }
    </script>
    @endpush
@endsection
