@extends('layouts.app')
@section('title', $compliance->exists ? 'Edit Compliance' : 'New Compliance')
@section('page-title', $compliance->exists ? 'Edit ' . $compliance->compliance_no : 'New Compliance Item')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('compliances.index') }}">Compliance</a></li><li class="breadcrumb-item active">{{ $compliance->exists ? 'Edit' : 'New' }}</li>@endsection
@section('content')
<form method="POST" action="{{ $compliance->exists ? route('compliances.update', $compliance) : route('compliances.store') }}" enctype="multipart/form-data">
    @csrf @if($compliance->exists) @method('PUT') @endif
    <div class="row"><div class="col-lg-8"><div class="card"><div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label required">Compliance Type</label><input name="compliance_type" list="ctypes" class="form-control" value="{{ old('compliance_type', $compliance->compliance_type) }}" required><datalist id="ctypes">@foreach($types as $t)<option value="{{ $t->name }}">@endforeach</datalist></div>
        <div class="col-md-6"><label class="form-label required">Inspection Name</label><input name="inspection_name" class="form-control" value="{{ old('inspection_name', $compliance->inspection_name) }}" required placeholder="Annual boiler inspection"></div>
        <div class="col-md-6"><label class="form-label">Asset</label><select name="asset_id" class="form-select"><option value="">-</option>@foreach($assets as $a)<option value="{{ $a->id }}" @selected(old('asset_id', $compliance->asset_id) == $a->id)>{{ $a->asset_code }} - {{ $a->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Responsible Department</label><select name="department_id" class="form-select"><option value="">-</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $compliance->department_id) == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Applicable Authority</label><input name="authority" class="form-control" value="{{ old('authority', $compliance->authority) }}" placeholder="Directorate of Boilers"></div>
        <div class="col-md-6"><label class="form-label">Inspection Agency</label><input name="agency" class="form-control" value="{{ old('agency', $compliance->agency) }}"></div>
        <div class="col-md-3"><label class="form-label">Frequency (days)</label><input type="number" name="frequency_days" class="form-control" value="{{ old('frequency_days', $compliance->frequency_days) }}"></div>
        <div class="col-md-3"><label class="form-label">Last Inspection Date</label><input type="date" name="last_inspection_date" class="form-control" value="{{ old('last_inspection_date', $compliance->last_inspection_date?->toDateString()) }}"></div>
        <div class="col-md-3"><label class="form-label">Next Due Date</label><input type="date" name="next_due_date" class="form-control" value="{{ old('next_due_date', $compliance->next_due_date?->toDateString()) }}"><div class="form-text">Blank = last + frequency</div></div>
        <div class="col-md-3"><label class="form-label">Result</label><select name="result" class="form-select"><option value="">-</option>@foreach(['passed', 'failed', 'conditional'] as $r)<option value="{{ $r }}" @selected(old('result', $compliance->result) == $r)>{{ ucfirst($r) }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Certificate Number</label><input name="certificate_no" class="form-control" value="{{ old('certificate_no', $compliance->certificate_no) }}"></div>
        <div class="col-md-6">@include('partials.upload-field', ['label' => 'Certificate File'])</div>
        <div class="col-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $compliance->remarks) }}</textarea></div>
    </div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body d-grid gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button><a href="{{ route('compliances.index') }}" class="btn btn-light">Cancel</a></div></div></div></div>
</form>
@endsection
