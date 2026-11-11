@extends('layouts.app')
@section('title', $amc->exists ? 'Edit AMC' : 'New AMC')
@section('page-title', $amc->exists ? 'Edit ' . $amc->amc_no : 'New AMC Contract')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('amc.index') }}">AMC</a></li><li class="breadcrumb-item active">{{ $amc->exists ? 'Edit' : 'New' }}</li>@endsection
@section('content')
<form method="POST" action="{{ $amc->exists ? route('amc.update', $amc) : route('amc.store') }}" enctype="multipart/form-data">
    @csrf @if($amc->exists) @method('PUT') @endif
    <div class="row"><div class="col-lg-8"><div class="card"><div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label required">Vendor</label><select name="vendor_id" class="form-select" required><option value="">Select…</option>@foreach($vendors as $v)<option value="{{ $v->id }}" @selected(old('vendor_id', $amc->vendor_id) == $v->id)>{{ $v->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label required">Contract Name</label><input name="name" class="form-control" value="{{ old('name', $amc->name) }}" required></div>
        <div class="col-md-3"><label class="form-label required">Start Date</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date', $amc->start_date?->toDateString()) }}" required></div>
        <div class="col-md-3"><label class="form-label required">End Date</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date', $amc->end_date?->toDateString()) }}" required></div>
        <div class="col-md-3"><label class="form-label">Contract Value</label><input type="number" step="0.01" name="contract_value" class="form-control" value="{{ old('contract_value', $amc->contract_value ?? 0) }}"></div>
        <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(['active', 'expired', 'terminated'] as $s)<option value="{{ $s }}" @selected(old('status', $amc->status) == $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">Service Frequency</label><select name="service_frequency" class="form-select">@foreach(['monthly', 'quarterly', 'half_yearly', 'yearly', 'on_call'] as $f)<option value="{{ $f }}" @selected(old('service_frequency', $amc->service_frequency) == $f)>{{ status_label($f) }}</option>@endforeach</select><div class="form-text">Visits are auto-scheduled on create</div></div>
        <div class="col-md-4"><label class="form-label">Response Time (hrs)</label><input type="number" name="response_time_hours" class="form-control" value="{{ old('response_time_hours', $amc->response_time_hours) }}"></div>
        <div class="col-md-4"><label class="form-label">Resolution Time (hrs)</label><input type="number" name="resolution_time_hours" class="form-control" value="{{ old('resolution_time_hours', $amc->resolution_time_hours) }}"></div>
        <div class="col-md-12"><label class="form-label">Covered Assets</label><select name="asset_ids[]" class="form-select" multiple size="6">@foreach($assets as $a)<option value="{{ $a->id }}" @selected(in_array($a->id, old('asset_ids', $amc->exists ? $amc->assets->pluck('id')->all() : [])))>{{ $a->asset_code }} - {{ $a->name }}</option>@endforeach</select><div class="form-text">Hold Ctrl to select several</div></div>
        <div class="col-md-6"><label class="form-label">Payment Terms</label><input name="payment_terms" class="form-control" value="{{ old('payment_terms', $amc->payment_terms) }}"></div>
        <div class="col-md-3"><label class="form-label">Contact Person</label><input name="contact_person" class="form-control" value="{{ old('contact_person', $amc->contact_person) }}"></div>
        <div class="col-md-3"><label class="form-label">Contact Number</label><input name="contact_number" class="form-control" value="{{ old('contact_number', $amc->contact_number) }}"></div>
        <div class="col-md-6"><label class="form-label">Scope of Work</label><textarea name="scope_of_work" class="form-control" rows="3">{{ old('scope_of_work', $amc->scope_of_work) }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Warranty / Service Conditions</label><textarea name="service_conditions" class="form-control" rows="3">{{ old('service_conditions', $amc->service_conditions) }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $amc->remarks) }}</textarea></div>
        <div class="col-md-6">@include('partials.upload-field', ['label' => 'Contract Document'])</div>
    </div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body d-grid gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Contract</button><a href="{{ route('amc.index') }}" class="btn btn-light">Cancel</a></div></div></div></div>
</form>
@endsection
