@extends('layouts.app')
@section('title', 'Daily Plant Logs')
@section('page-title', 'Daily Plant Logs & Utilities')
@section('breadcrumb')<li class="breadcrumb-item active">Daily Logs</li>@endsection
@section('page-actions')
@can('pm.logs.manage')
<div class="dropdown"><button class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-plus-lg me-1"></i> New Log Entry</button>
    <ul class="dropdown-menu dropdown-menu-end">@foreach($logTypes as $t)<li><a class="dropdown-item" href="{{ route('daily-logs.create', ['log_type_id' => $t->id]) }}">{{ $t->name }}</a></li>@endforeach</ul></div>
@endcan
@endsection
@section('content')
<form class="filter-bar row g-2" method="GET">
    <div class="col-md-2"><select name="log_type_id" class="form-select" data-autosubmit><option value="">All log types</option>@foreach($logTypes as $t)<option value="{{ $t->id }}" @selected(request('log_type_id') == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="department_id" class="form-select" data-autosubmit><option value="">All departments</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="asset_id" class="form-select" data-autosubmit><option value="">All equipment</option>@foreach($assets as $a)<option value="{{ $a->id }}" @selected(request('asset_id') == $a->id)>{{ $a->asset_code }} {{ $a->name }}</option>@endforeach</select></div>
    <div class="col-md-1"><select name="shift_id" class="form-select" data-autosubmit><option value="">Shift</option>@foreach($shifts as $s)<option value="{{ $s->id }}" @selected(request('shift_id') == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
    <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
    <div class="col-md-1 d-flex gap-1"><button class="btn btn-primary"><i class="bi bi-search"></i></button><a href="{{ route('daily-logs.index') }}" class="btn btn-light"><i class="bi bi-x"></i></a></div>
    <div class="col-12 small"><a href="{{ route('daily-logs.index', ['abnormal' => 1]) }}" class="text-danger">Abnormal readings only</a></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Log</th><th>Date</th><th>Type</th><th>Equipment</th><th>Shift</th><th>Operator</th><th class="text-end">Opening</th><th class="text-end">Closing</th><th class="text-end">Consumption</th><th>Flag</th></tr></thead>
    <tbody>@forelse($logs as $l)
    <tr class="{{ $l->is_abnormal ? 'table-danger' : '' }}"><td><a href="{{ route('daily-logs.show', $l) }}" class="fw-semibold">{{ $l->log_no }}</a></td><td>{{ show_date($l->log_date) }}</td><td class="small">{{ $l->logType->name }}</td><td class="small">{{ $l->asset?->name ?? '-' }}<div class="text-muted">{{ $l->department?->name }}</div></td><td>{{ $l->shift?->name }}</td><td class="small">{{ $l->operator?->name }}</td><td class="text-end">{{ $l->opening_reading !== null ? $l->opening_reading + 0 : '' }}</td><td class="text-end">{{ $l->closing_reading !== null ? $l->closing_reading + 0 : '' }}</td><td class="text-end fw-semibold">{{ $l->consumption !== null ? ($l->consumption + 0) . ' ' . $l->unit : '' }}</td><td>{!! $l->is_abnormal ? status_badge('danger', 'Abnormal') : '' !!}</td></tr>
    @empty<tr><td colspan="10">@include('partials.empty', ['message' => 'No log entries.'])</td></tr>@endforelse</tbody></table></div>
    @if($logs->hasPages())<div class="card-footer">{{ $logs->links() }}</div>@endif
</div>
@endsection
