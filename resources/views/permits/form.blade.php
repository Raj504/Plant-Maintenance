@extends('layouts.app')
@section('title', $permit->exists ? 'Edit Permit' : 'New Permit')
@section('page-title', $permit->exists ? 'Edit ' . $permit->permit_no : 'New Permit to Work')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('permits.index') }}">Permits</a></li><li class="breadcrumb-item active">{{ $permit->exists ? 'Edit' : 'New' }}</li>@endsection
@section('content')
<form method="POST" action="{{ $permit->exists ? route('permits.update', $permit) : route('permits.store') }}" enctype="multipart/form-data">
    @csrf @if($permit->exists) @method('PUT') @endif
    <div class="row"><div class="col-lg-8">
        <div class="card"><div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label">Work Order</label><select name="work_order_id" class="form-select"><option value="">None</option>@foreach($workOrders as $w)<option value="{{ $w->id }}" @selected(old('work_order_id', $permit->work_order_id) == $w->id)>{{ $w->wo_no }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label required">Asset</label><select name="asset_id" class="form-select" required>@foreach($assets as $a)<option value="{{ $a->id }}" @selected(old('asset_id', $permit->asset_id) == $a->id)>{{ $a->asset_code }} - {{ $a->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label required">Permit Type</label><select name="permit_type" class="form-select">@foreach(\App\Models\SafetyPermit::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('permit_type', $permit->permit_type) == $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Work Location</label><input name="work_location" class="form-control" value="{{ old('work_location', $permit->work_location) }}"></div>
            <div class="col-12"><label class="form-label required">Work Description</label><textarea name="work_description" class="form-control" rows="3" required>{{ old('work_description', $permit->work_description) }}</textarea></div>
            <div class="col-md-6"><label class="form-label">Hazards Identified</label><textarea name="hazards_identified" class="form-control" rows="3" placeholder="Electric shock, arc flash, falling objects…">{{ old('hazards_identified', $permit->hazards_identified) }}</textarea></div>
            <div class="col-md-6"><label class="form-label">Safety Precautions</label><textarea name="safety_precautions" class="form-control" rows="3" placeholder="Isolate supply, test for dead, barricade area…">{{ old('safety_precautions', $permit->safety_precautions) }}</textarea></div>
            <div class="col-12"><label class="form-label">PPE Requirements</label><input name="ppe_requirements" class="form-control" value="{{ old('ppe_requirements', $permit->ppe_requirements) }}" placeholder="Helmet, insulated gloves, safety shoes, face shield"></div>
            <div class="col-md-6"><label class="form-label required">Valid From</label><input type="datetime-local" name="valid_from" class="form-control" value="{{ old('valid_from', $permit->valid_from?->format('Y-m-d\TH:i')) }}" required></div>
            <div class="col-md-6"><label class="form-label required">Valid Until</label><input type="datetime-local" name="valid_until" class="form-control" value="{{ old('valid_until', $permit->valid_until?->format('Y-m-d\TH:i')) }}" required></div>
            <div class="col-12">@include('partials.upload-field', ['label' => 'Risk assessment / JSA documents'])</div>
        </div></div>
    </div>
    <div class="col-lg-4"><div class="card"><div class="card-body d-grid gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Permit</button><a href="{{ route('permits.index') }}" class="btn btn-light">Cancel</a></div></div>
    <div class="alert alert-warning small"><i class="bi bi-shield-exclamation me-1"></i> Record each energy isolation (LOTO) on the permit page after saving, then submit for Safety Officer approval. Work orders flagged "permit required" cannot start until the permit is active.</div></div></div>
</form>
@endsection
