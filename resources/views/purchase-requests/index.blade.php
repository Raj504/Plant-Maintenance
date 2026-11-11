@extends('layouts.app')
@section('title', 'Purchase Requisitions')
@section('page-title', 'Maintenance Purchase Requisitions')
@section('breadcrumb')<li class="breadcrumb-item active">Purchase Requisitions</li>@endsection
@section('page-actions')@can('pm.purchase.manage')<a href="{{ route('purchase-requests.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Requisition</a>@endcan@endsection
@section('content')
<form class="filter-bar row g-2" method="GET">
    <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="PR number"></div>
    <div class="col-md-3"><select name="status" class="form-select" data-autosubmit><option value="">All statuses</option>@foreach(\App\Models\PurchaseRequest::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') == $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>PR No.</th><th>Spare</th><th class="text-end">Required</th><th class="text-end">Available</th><th class="text-end">Shortage</th><th>Required By</th><th>Work Order</th><th>Vendor</th><th>Requested By</th><th>Status</th></tr></thead>
    <tbody>@forelse($prs as $p)
    <tr><td><a href="{{ route('purchase-requests.show', $p) }}" class="fw-semibold">{{ $p->pr_no }}</a></td><td>{{ $p->spare->label }}</td><td class="text-end">{{ $p->required_qty + 0 }}</td><td class="text-end">{{ $p->available_stock + 0 }}</td><td class="text-end text-danger fw-semibold">{{ $p->shortage_qty + 0 }}</td><td class="small">{{ show_date($p->required_by_date) }}</td><td>@if($p->workOrder)<a href="{{ route('work-orders.show', $p->workOrder) }}">{{ $p->workOrder->wo_no }}</a>@endif</td><td class="small">{{ $p->vendor?->name }}</td><td class="small">{{ $p->requester->name }}</td><td>{!! status_badge($p->status) !!}</td></tr>
    @empty<tr><td colspan="10">@include('partials.empty', ['message' => 'No purchase requisitions.'])</td></tr>@endforelse</tbody></table></div>
    @if($prs->hasPages())<div class="card-footer">{{ $prs->links() }}</div>@endif
</div>
@endsection
