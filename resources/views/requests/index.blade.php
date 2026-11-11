@extends('layouts.app')
@section('title', 'Maintenance Requests')
@section('page-title', 'Maintenance Requests / Complaints')
@section('breadcrumb')<li class="breadcrumb-item active">Requests</li>@endsection
@section('page-actions')@can('pm.requests.create')<a href="{{ route('requests.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Raise Request</a>@endcan@endsection

@section('content')
<form class="filter-bar row g-2 align-items-end" method="GET">
    <div class="col-md-2"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Request no / text"></div>
    <div class="col-md-2"><select name="status" class="form-select" data-autosubmit><option value="">All statuses</option>@foreach(\App\Models\MaintenanceRequest::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') == $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="priority" class="form-select" data-autosubmit><option value="">All priorities</option>@foreach(\App\Models\MaintenanceRequest::PRIORITIES as $k => $v)<option value="{{ $k }}" @selected(request('priority') == $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="request_type" class="form-select" data-autosubmit><option value="">All types</option>@foreach(\App\Models\MaintenanceRequest::TYPES as $k => $v)<option value="{{ $k }}" @selected(request('request_type') == $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="department_id" class="form-select" data-autosubmit><option value="">All departments</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
    <div class="col-md-1"><input type="date" name="from" value="{{ request('from') }}" class="form-control" title="From"></div>
    <div class="col-md-1 d-flex gap-1"><button class="btn btn-primary w-100"><i class="bi bi-search"></i></button><a href="{{ route('requests.index') }}" class="btn btn-light"><i class="bi bi-x"></i></a></div>
    <div class="col-12 d-flex gap-3 small">
        <a href="{{ route('requests.index', ['open' => 1]) }}" class="{{ request('open') ? 'fw-bold' : '' }}">Open</a>
        <a href="{{ route('requests.index', ['mine' => 1]) }}" class="{{ request('mine') ? 'fw-bold' : '' }}">My requests</a>
        @can('pm.requests.inspect')<a href="{{ route('requests.index', ['status' => 'submitted']) }}">Inspection pending</a>@endcan
        @can('pm.requests.assess')<a href="{{ route('requests.index', ['status' => 'assessment_pending']) }}">Assessment pending</a>@endcan
        @can('pm.requests.approve')<a href="{{ route('requests.index', ['status' => 'approval_pending']) }}">Approval pending</a>@endcan
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Request</th><th>Date</th><th>Asset</th><th>Type</th><th>Priority</th><th>Problem</th><th>Requested By</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($requests as $r)
            <tr class="row-{{ $r->priority }}">
                <td><a href="{{ route('requests.show', $r) }}" class="fw-semibold">{{ $r->request_no }}</a>@if($r->is_emergency) <span class="badge text-bg-danger">EMERGENCY</span>@endif</td>
                <td class="text-nowrap small">{{ show_datetime($r->request_date) }}</td>
                <td>{{ $r->asset->name }}<div class="text-muted small">{{ $r->asset->asset_code }} · {{ $r->department?->name }}</div></td>
                <td class="small">{{ \App\Models\MaintenanceRequest::TYPES[$r->request_type] ?? $r->request_type }}</td>
                <td>{!! status_badge($r->priority) !!}</td>
                <td class="small">{{ \Illuminate\Support\Str::limit($r->problem_description, 70) }} @if($r->machine_stopped)<span class="badge text-bg-danger ms-1" title="Machine stopped"><i class="bi bi-stop-circle"></i></span>@endif</td>
                <td class="small">{{ $r->requester->name }}</td>
                <td>{!! status_badge($r->status, \App\Models\MaintenanceRequest::STATUSES[$r->status] ?? null) !!}</td>
                <td><a href="{{ route('requests.show', $r) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a></td>
            </tr>
            @empty
            <tr><td colspan="9">@include('partials.empty', ['message' => 'No maintenance requests found.'])</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())<div class="card-footer d-flex justify-content-between align-items-center"><small class="text-muted">{{ $requests->total() }} requests</small>{{ $requests->links() }}</div>@endif
</div>
@endsection
