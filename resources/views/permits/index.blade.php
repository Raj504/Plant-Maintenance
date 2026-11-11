@extends('layouts.app')
@section('title', 'Permits')
@section('page-title', 'Safety – Permit to Work & LOTO')
@section('breadcrumb')<li class="breadcrumb-item active">Permits</li>@endsection
@section('page-actions')@can('pm.safety.manage')<a href="{{ route('permits.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Permit</a>@endcan@endsection
@section('content')
<form class="filter-bar row g-2" method="GET">
    <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Permit no"></div>
    <div class="col-md-3"><select name="status" class="form-select" data-autosubmit><option value="">All statuses</option>@foreach(\App\Models\SafetyPermit::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') == $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-3"><select name="permit_type" class="form-select" data-autosubmit><option value="">All types</option>@foreach(\App\Models\SafetyPermit::TYPES as $k => $v)<option value="{{ $k }}" @selected(request('permit_type') == $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Permit</th><th>Type</th><th>Asset</th><th>Work Order</th><th>Valid</th><th>Issued By</th><th>Approved By</th><th>Status</th></tr></thead>
    <tbody>@forelse($permits as $p)
    <tr><td><a href="{{ route('permits.show', $p) }}" class="fw-semibold">{{ $p->permit_no }}</a></td><td>{{ \App\Models\SafetyPermit::TYPES[$p->permit_type] ?? $p->permit_type }}</td><td>{{ $p->asset->name }}</td><td>@if($p->workOrder)<a href="{{ route('work-orders.show', $p->workOrder) }}">{{ $p->workOrder->wo_no }}</a>@endif</td><td class="small">{{ show_datetime($p->valid_from) }}<br>{{ show_datetime($p->valid_until) }}</td><td>{{ $p->issuer->name }}</td><td>{{ $p->approver?->name ?? '-' }}</td><td>{!! status_badge($p->status, \App\Models\SafetyPermit::STATUSES[$p->status] ?? null) !!}</td></tr>
    @empty<tr><td colspan="8">@include('partials.empty', ['message' => 'No permits.'])</td></tr>@endforelse</tbody></table></div>
    @if($permits->hasPages())<div class="card-footer">{{ $permits->links() }}</div>@endif
</div>
@endsection
