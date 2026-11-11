@extends('layouts.app')
@section('title', $calibration->exists ? 'Edit Instrument' : 'Register Instrument')
@section('page-title', $calibration->exists ? 'Edit ' . $calibration->instrument_code : 'Register Instrument for Calibration')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('calibrations.index') }}">Calibration</a></li><li class="breadcrumb-item active">{{ $calibration->exists ? 'Edit' : 'New' }}</li>@endsection
@section('content')
<form method="POST" action="{{ $calibration->exists ? route('calibrations.update', $calibration) : route('calibrations.store') }}" enctype="multipart/form-data">
    @csrf @if($calibration->exists) @method('PUT') @endif
    <div class="row"><div class="col-lg-8"><div class="card"><div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label required">Instrument Code</label><input name="instrument_code" class="form-control" value="{{ old('instrument_code', $calibration->instrument_code) }}" required></div>
        <div class="col-md-8"><label class="form-label required">Instrument Name</label><input name="instrument_name" class="form-control" value="{{ old('instrument_name', $calibration->instrument_name) }}" required></div>
        <div class="col-md-6"><label class="form-label">Asset</label><select name="asset_id" class="form-select"><option value="">-</option>@foreach($assets as $a)<option value="{{ $a->id }}" @selected(old('asset_id', $calibration->asset_id) == $a->id)>{{ $a->asset_code }} - {{ $a->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">-</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $calibration->department_id) == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Serial Number</label><input name="serial_number" class="form-control" value="{{ old('serial_number', $calibration->serial_number) }}"></div>
        <div class="col-md-4"><label class="form-label">Calibration Agency</label><input name="agency" class="form-control" value="{{ old('agency', $calibration->agency) }}"></div>
        <div class="col-md-4"><label class="form-label required">Frequency (days)</label><input type="number" name="frequency_days" class="form-control" value="{{ old('frequency_days', $calibration->frequency_days) }}" required></div>
        <div class="col-md-3"><label class="form-label">Last Calibration</label><input type="date" name="last_calibration_date" class="form-control" value="{{ old('last_calibration_date', $calibration->last_calibration_date?->toDateString()) }}"></div>
        <div class="col-md-3"><label class="form-label">Next Due</label><input type="date" name="next_due_date" class="form-control" value="{{ old('next_due_date', $calibration->next_due_date?->toDateString()) }}"><div class="form-text">Blank = last + frequency</div></div>
        <div class="col-md-3"><label class="form-label">Result</label><select name="result" class="form-select"><option value="">-</option><option value="passed" @selected(old('result', $calibration->result) == 'passed')>Passed</option><option value="failed" @selected(old('result', $calibration->result) == 'failed')>Failed</option></select></div>
        <div class="col-md-3"><label class="form-label">Certificate No.</label><input name="certificate_no" class="form-control" value="{{ old('certificate_no', $calibration->certificate_no) }}"></div>
        <div class="col-md-6">@include('partials.upload-field', ['label' => 'Certificate Attachment'])</div>
        <div class="col-md-6"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $calibration->remarks) }}</textarea></div>
    </div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body d-grid gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button><a href="{{ route('calibrations.index') }}" class="btn btn-light">Cancel</a></div></div></div></div>
</form>
@endsection
