@extends('layouts.app')
@section('title', $pr->pr_no)
@section('page-title'){{ $pr->pr_no }} {!! status_badge($pr->status) !!}@endsection
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('purchase-requests.index') }}">Purchase Requisitions</a></li><li class="breadcrumb-item active">{{ $pr->pr_no }}</li>@endsection
@section('content')
<div class="row g-3"><div class="col-lg-7">
    <div class="card"><div class="card-body"><dl class="detail-list">
        <dt>Spare</dt><dd><a href="{{ route('spares.show', $pr->spare) }}">{{ $pr->spare->label }}</a></dd>
        <dt>Required / Available / Shortage</dt><dd>{{ $pr->required_qty + 0 }} / {{ $pr->available_stock + 0 }} / <strong class="text-danger">{{ $pr->shortage_qty + 0 }}</strong> {{ $pr->spare->uom }}</dd>
        <dt>Required By</dt><dd>{{ show_date($pr->required_by_date) }}</dd>
        <dt>Vendor</dt><dd>{{ $pr->vendor?->name ?? '-' }}</dd>
        <dt>Work Order</dt><dd>@if($pr->workOrder)<a href="{{ route('work-orders.show', $pr->workOrder) }}">{{ $pr->workOrder->wo_no }}</a>@else - @endif</dd>
        <dt>Request</dt><dd>@if($pr->request)<a href="{{ route('requests.show', $pr->request) }}">{{ $pr->request->request_no }}</a>@else - @endif</dd>
        <dt>Requested By</dt><dd>{{ $pr->requester->name }} · {{ show_datetime($pr->created_at) }}</dd>
        <dt>Remarks</dt><dd>{{ $pr->remarks ?? '-' }}</dd>
    </dl></div></div>
    @can('pm.purchase.manage')
    @if(!in_array($pr->status, ['received', 'cancelled']))
    <div class="card"><div class="card-header">Update Status</div><div class="card-body"><form method="POST" action="{{ route('purchase-requests.status', $pr) }}" class="row g-3">@csrf
        <div class="col-md-4"><label class="form-label">New Status</label><select name="status" class="form-select" data-toggle-target="#recvBox" data-toggle-values="received">
            @if($pr->status === 'pending')<option value="approved">Approved</option>@endif
            @if(in_array($pr->status, ['pending', 'approved']))<option value="ordered">Ordered</option>@endif
            <option value="received">Received (posts stock receipt)</option>
            <option value="cancelled">Cancelled</option></select></div>
        <div class="col-md-8" id="recvBox"><div class="row g-2"><div class="col-6"><label class="form-label">Received Qty</label><input type="number" step="0.01" name="received_qty" class="form-control" value="{{ $pr->required_qty }}"></div><div class="col-6"><label class="form-label">Unit Cost</label><input type="number" step="0.01" name="unit_cost" class="form-control" value="{{ $pr->spare->unit_cost }}"></div></div></div>
        <div class="col-md-9"><input name="remarks" class="form-control" placeholder="Remarks / PO number"></div>
        <div class="col-md-3"><button class="btn btn-primary w-100">Update</button></div>
    </form></div></div>
    @endif
    @endcan
</div>
<div class="col-lg-5"><div class="card"><div class="card-header">History</div><div class="card-body">@include('partials.history', ['history' => $pr->history])</div></div></div></div>
@endsection
