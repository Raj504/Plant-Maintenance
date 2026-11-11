@extends('layouts.app')
@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail & Activity History')
@section('breadcrumb')<li class="breadcrumb-item active">Audit</li>@endsection
@section('content')
<form class="filter-bar row g-2" method="GET">
    <div class="col-md-2"><select name="user_id" class="form-select" data-autosubmit><option value="">All users</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="model_type" class="form-select" data-autosubmit><option value="">All records</option>@foreach($types as $t)<option value="{{ $t }}" @selected(request('model_type') == $t)>{{ $t }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="action" class="form-select" data-autosubmit><option value="">All actions</option>@foreach($actions as $a)<option value="{{ $a }}" @selected(request('action') == $a)>{{ status_label($a) }}</option>@endforeach</select></div>
    <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
    <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
    <div class="col-md-2 d-flex gap-1"><button class="btn btn-primary"><i class="bi bi-search"></i></button><a href="{{ route('admin.audit.index') }}" class="btn btn-light"><i class="bi bi-x"></i></a></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>When</th><th>User</th><th>Record</th><th>Action</th><th>Status Change</th><th>Remarks</th></tr></thead>
    <tbody>@forelse($history as $h)
    <tr><td class="text-nowrap small">{{ show_datetime($h->created_at) }}</td><td class="small">{{ $h->user?->name ?? 'System' }}</td>
        <td class="small">{{ class_basename($h->model_type) }} #{{ $h->model_id }}@if($h->model) <span class="text-muted">{{ $h->model->request_no ?? $h->model->wo_no ?? $h->model->asset_code ?? $h->model->permit_no ?? $h->model->plan_no ?? $h->model->log_no ?? $h->model->pr_no ?? $h->model->amc_no ?? $h->model->compliance_no ?? $h->model->instrument_code ?? $h->model->handover_no ?? '' }}</span>@endif</td>
        <td>{!! status_badge($h->action) !!}</td><td class="small">{!! $h->from_status ? status_badge($h->from_status) . ' → ' : '' !!}{!! $h->to_status ? status_badge($h->to_status) : '' !!}</td>
        <td class="small">{{ \Illuminate\Support\Str::limit($h->remarks, 100) }}@if($h->changes) <span class="badge text-bg-light border">{{ count($h->changes) }} field(s) changed</span>@endif</td></tr>
    @empty<tr><td colspan="6">@include('partials.empty')</td></tr>@endforelse</tbody></table></div>
    @if($history->hasPages())<div class="card-footer">{{ $history->links() }}</div>@endif
</div>
@endsection
