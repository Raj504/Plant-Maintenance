@extends('layouts.app')
@section('title', $logType->exists ? 'Edit Log Type' : 'New Log Type')
@section('page-title', $logType->exists ? 'Edit Log Type: ' . $logType->name : 'New Log Type')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('admin.log-types.index') }}">Log Types</a></li><li class="breadcrumb-item active">{{ $logType->exists ? 'Edit' : 'New' }}</li>@endsection
@section('content')
<form method="POST" action="{{ $logType->exists ? route('admin.log-types.update', $logType) : route('admin.log-types.store') }}">
    @csrf @if($logType->exists) @method('PUT') @endif
    <div class="row"><div class="col-lg-8">
        <div class="card"><div class="card-body row g-3">
            <div class="col-md-3"><label class="form-label required">Code</label><input name="code" class="form-control" value="{{ old('code', $logType->code) }}" required placeholder="FUEL"></div>
            <div class="col-md-9"><label class="form-label required">Name</label><input name="name" class="form-control" value="{{ old('name', $logType->name) }}" required></div>
            <div class="col-md-8"><label class="form-label">Description</label><input name="description" class="form-control" value="{{ old('description', $logType->description) }}"></div>
            <div class="col-md-4 d-flex flex-column justify-content-end">
                <div class="form-check form-switch"><input type="checkbox" name="has_meter_readings" value="1" class="form-check-input" id="mtr" @checked(old('has_meter_readings', $logType->has_meter_readings))><label class="form-check-label" for="mtr">Show opening / closing / consumption</label></div>
                <div class="form-check form-switch"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="act" @checked(old('is_active', $logType->is_active ?? true))><label class="form-check-label" for="act">Active</label></div>
            </div>
            <div class="col-12"><label class="form-label">Extra Readings / Fields</label>
                <div id="fieldRows">
                    <template><div class="row g-2 mb-2 repeater-row"><div class="col-md-4"><input name="fields[__INDEX__][label]" class="form-control form-control-sm" placeholder="Label e.g. Temperature"></div><div class="col-md-2"><input name="fields[__INDEX__][unit]" class="form-control form-control-sm" placeholder="Unit"></div><div class="col-md-2"><select name="fields[__INDEX__][type]" class="form-select form-select-sm"><option value="number">Number</option><option value="text">Text</option><option value="select">Dropdown</option></select></div><div class="col-md-3"><input name="fields[__INDEX__][options]" class="form-control form-control-sm" placeholder="Options a,b,c (dropdown)"></div><div class="col-md-1"><button type="button" class="btn btn-sm btn-light text-danger" data-repeater-remove><i class="bi bi-x"></i></button></div></div></template>
                    @foreach(old('fields', $logType->fields ?? []) as $i => $f)
                    <div class="row g-2 mb-2 repeater-row"><div class="col-md-4"><input name="fields[{{ $i }}][label]" class="form-control form-control-sm" value="{{ $f['label'] ?? '' }}" placeholder="Label"></div><div class="col-md-2"><input name="fields[{{ $i }}][unit]" class="form-control form-control-sm" value="{{ $f['unit'] ?? '' }}" placeholder="Unit"></div><div class="col-md-2"><select name="fields[{{ $i }}][type]" class="form-select form-select-sm">@foreach(['number' => 'Number', 'text' => 'Text', 'select' => 'Dropdown'] as $k => $v)<option value="{{ $k }}" @selected(($f['type'] ?? 'number') == $k)>{{ $v }}</option>@endforeach</select></div><div class="col-md-3"><input name="fields[{{ $i }}][options]" class="form-control form-control-sm" value="{{ $f['options'] ?? '' }}" placeholder="Options a,b,c"></div><div class="col-md-1"><button type="button" class="btn btn-sm btn-light text-danger" data-repeater-remove><i class="bi bi-x"></i></button></div></div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-repeater-add="#fieldRows"><i class="bi bi-plus"></i> Add field</button>
            </div>
        </div></div>
    </div>
    <div class="col-lg-4"><div class="card"><div class="card-body d-grid gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button><a href="{{ route('admin.log-types.index') }}" class="btn btn-light">Cancel</a></div></div>
    <div class="alert alert-info small">Each department can have its own log form. Fields appear automatically on the daily log entry screen.</div></div></div>
</form>
@endsection
