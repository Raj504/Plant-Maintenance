@extends('layouts.app')
@section('title', $log->exists ? 'Edit Log' : 'New Log')
@section('page-title', $log->exists ? 'Edit ' . $log->log_no : 'New Log Entry' . ($logType ? ' – ' . $logType->name : ''))
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('daily-logs.index') }}">Daily Logs</a></li><li class="breadcrumb-item active">{{ $log->exists ? 'Edit' : 'New' }}</li>@endsection
@section('content')
@if(!$logType)
<div class="card"><div class="card-body"><h6>Choose the log type</h6><div class="d-flex flex-wrap gap-2">@foreach($logTypes as $t)<a href="{{ route('daily-logs.create', ['log_type_id' => $t->id]) }}" class="btn btn-outline-primary">{{ $t->name }}</a>@endforeach</div></div></div>
@else
<form method="POST" action="{{ $log->exists ? route('daily-logs.update', $log) : route('daily-logs.store') }}" enctype="multipart/form-data">
    @csrf @if($log->exists) @method('PUT') @endif
    <input type="hidden" name="log_type_id" value="{{ $logType->id }}">
    <div class="row"><div class="col-lg-8">
        <div class="card"><div class="card-header">{{ $logType->name }} <small class="text-muted fw-normal">{{ $logType->description }}</small></div><div class="card-body row g-3">
            <div class="col-md-3"><label class="form-label required">Date</label><input type="date" name="log_date" class="form-control" value="{{ old('log_date', $log->log_date?->toDateString()) }}" required></div>
            <div class="col-md-3"><label class="form-label">Shift</label><select name="shift_id" class="form-select"><option value="">-</option>@foreach($shifts as $s)<option value="{{ $s->id }}" @selected(old('shift_id', $log->shift_id) == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">-</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $log->department_id ?? auth()->user()->department_id) == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label">Operator</label><select name="operator_id" class="form-select">@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('operator_id', $log->operator_id ?? auth()->id()) == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
            <div class="col-md-12"><label class="form-label">Asset / Equipment</label><select name="asset_id" class="form-select"><option value="">-</option>@foreach($assets as $a)<option value="{{ $a->id }}" @selected(old('asset_id', $log->asset_id) == $a->id)>{{ $a->asset_code }} - {{ $a->name }} ({{ $a->current_running_hours + 0 }} h)</option>@endforeach</select></div>

            @if($logType->has_meter_readings)
            <div class="col-md-3"><label class="form-label">Opening Reading</label><input type="number" step="0.01" name="opening_reading" class="form-control" value="{{ old('opening_reading', $log->opening_reading) }}"></div>
            <div class="col-md-3"><label class="form-label">Closing Reading</label><input type="number" step="0.01" name="closing_reading" class="form-control" value="{{ old('closing_reading', $log->closing_reading) }}"></div>
            <div class="col-md-3"><label class="form-label">Unit</label><input name="unit" class="form-control" value="{{ old('unit', $log->unit ?? ($logType->code === 'ENERGY' ? 'kWh' : 'hrs')) }}"></div>
            <div class="col-md-3 d-flex align-items-end"><div class="form-check"><input type="checkbox" name="update_running_hours" value="1" class="form-check-input" id="urh" @checked(old('update_running_hours'))><label class="form-check-label small" for="urh">Closing reading = machine running hours</label></div></div>
            <div class="col-12"><div class="form-text">Consumption is calculated automatically (closing − opening).</div></div>
            @endif

            @if($logType->fields)
            <div class="col-12"><hr class="my-1"><strong class="small text-muted">READINGS</strong></div>
            @foreach($logType->fields as $f)
            <div class="col-md-3"><label class="form-label">{{ $f['label'] }} {{ !empty($f['unit']) ? '(' . $f['unit'] . ')' : '' }}</label>
                @if(($f['type'] ?? 'number') === 'select')
                <select name="readings[{{ $f['key'] }}]" class="form-select"><option value="">-</option>@foreach(explode(',', $f['options'] ?? '') as $o)<option value="{{ trim($o) }}" @selected(old('readings.' . $f['key'], $log->readings[$f['key']] ?? null) == trim($o))>{{ trim($o) }}</option>@endforeach</select>
                @elseif(($f['type'] ?? 'number') === 'text')
                <input name="readings[{{ $f['key'] }}]" class="form-control" value="{{ old('readings.' . $f['key'], $log->readings[$f['key']] ?? null) }}">
                @else
                <input type="number" step="any" name="readings[{{ $f['key'] }}]" class="form-control" value="{{ old('readings.' . $f['key'], $log->readings[$f['key']] ?? null) }}">
                @endif
            </div>
            @endforeach
            @endif

            <div class="col-md-6"><label class="form-label">Observations</label><textarea name="observations" class="form-control" rows="3">{{ old('observations', $log->observations) }}</textarea></div>
            <div class="col-md-6"><label class="form-label">Abnormal Conditions <small class="text-muted">(filling this flags the entry)</small></label><textarea name="abnormal_conditions" class="form-control" rows="3">{{ old('abnormal_conditions', $log->abnormal_conditions) }}</textarea></div>
            <div class="col-md-8"><label class="form-label">Remarks</label><input name="remarks" class="form-control" value="{{ old('remarks', $log->remarks) }}"></div>
            <div class="col-md-4">@include('partials.upload-field', ['label' => 'Attachment'])</div>
        </div></div>
    </div>
    <div class="col-lg-4"><div class="card"><div class="card-body d-grid gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Log</button><a href="{{ route('daily-logs.index') }}" class="btn btn-light">Cancel</a></div></div></div></div>
</form>
@endif
@endsection
