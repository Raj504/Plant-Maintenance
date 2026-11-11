@extends('layouts.app')
@section('title', 'Calibration')
@section('page-title', 'Instrument Calibration Register')
@section('breadcrumb')<li class="breadcrumb-item active">Calibration</li>@endsection
@section('page-actions')@can('pm.compliance.manage')<a href="{{ route('calibrations.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Register Instrument</a>@endcan@endsection
@section('content')
<form class="filter-bar row g-2" method="GET">
    <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Instrument / code / serial"></div>
    <div class="col-md-2"><select name="status" class="form-select" data-autosubmit><option value="">All statuses</option>@foreach(\App\Models\Calibration::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') == $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-3"><select name="department_id" class="form-select" data-autosubmit><option value="">All departments</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
    <div class="col-md-2 d-flex gap-1"><button class="btn btn-primary"><i class="bi bi-search"></i></button><a href="{{ route('calibrations.index') }}" class="btn btn-light"><i class="bi bi-x"></i></a></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Code</th><th>Instrument</th><th>Asset / Dept</th><th>Serial</th><th>Agency</th><th>Last</th><th>Next Due</th><th>Certificate</th><th>Status</th></tr></thead>
    <tbody>@forelse($calibrations as $c)
    <tr class="{{ $c->status === 'overdue' ? 'table-danger' : ($c->status === 'due_soon' ? 'table-warning' : '') }}"><td><a href="{{ route('calibrations.show', $c) }}" class="fw-semibold">{{ $c->instrument_code }}</a></td><td>{{ $c->instrument_name }}</td><td class="small">{{ $c->asset?->name }}<br><span class="text-muted">{{ $c->department?->name }}</span></td><td class="small">{{ $c->serial_number }}</td><td class="small">{{ $c->agency }}</td><td class="small">{{ show_date($c->last_calibration_date) }}</td><td class="fw-semibold">{{ show_date($c->next_due_date) }}</td><td class="small">{{ $c->certificate_no }}</td><td>{!! status_badge($c->status, \App\Models\Calibration::STATUSES[$c->status] ?? null) !!}</td></tr>
    @empty<tr><td colspan="9">@include('partials.empty', ['message' => 'No instruments registered.'])</td></tr>@endforelse</tbody></table></div>
    @if($calibrations->hasPages())<div class="card-footer">{{ $calibrations->links() }}</div>@endif
</div>
@endsection
