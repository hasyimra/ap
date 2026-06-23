@extends('layouts.admin')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Outstanding</h6>
                    <h3>{{ number_format($totalOutstanding, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Invoice Overdue</h6>
                    <h3>{{ $overdueCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Invoice Draft/Diajukan</h6>
                    <h3>{{ $invoiceDraftCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Payment Draft/Diajukan</h6>
                    <h3>{{ $paymentDraftCount }}</h3>
                </div>
            </div>
        </div>
    </div>
@endsection
