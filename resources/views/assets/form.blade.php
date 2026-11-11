@extends('layouts.app')
@section('title', $asset->exists ? 'Edit Asset' : 'New Asset')
@section('page-title', $asset->exists ? 'Edit Asset ' . $asset->asset_code : 'Register New Asset')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li><li class="breadcrumb-item active">{{ $asset->exists ? 'Edit' : 'New' }}</li>@endsection

@section('content')
<form method="POST" action="{{ $asset->exists ? route('assets.update', $asset) : route('assets.store') }}" enctype="multipart/form-data">
    @csrf
    @if($asset->exists) @method('PUT') @endif
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Basic Information</div>
                <div class="card-body row g-3">
                    <div class="col-md-4"><label class="form-label required">Asset Code</label><input name="asset_code" class="form-control" value="{{ old('asset_code', $asset->asset_code) }}" required placeholder="e.g. CMP-01"></div>
                    <div class="col-md-8"><label class="form-label required">Asset Name</label><input name="name" class="form-control" value="{{ old('name', $asset->name) }}" required></div>
                    <div class="col-md-4"><label class="form-label">Category</label>
                        <select name="category_id" class="form-select"><option value="">-</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $asset->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                    <div class="col-md-8"><label class="form-label">Parent Asset <small class="text-muted">(for hierarchy, e.g. the machine this motor belongs to)</small></label>
                        <select name="parent_id" class="form-select"><option value="">- None (top level) -</option>@foreach($parents as $p)@if($p->id !== $asset->id)<option value="{{ $p->id }}" @selected(old('parent_id', $asset->parent_id) == $p->id)>{{ $p->asset_code }} - {{ $p->name }}</option>@endif @endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Plant / Unit</label>
                        <select name="plant_id" class="form-select"><option value="">-</option>@foreach($plants as $p)<option value="{{ $p->id }}" @selected(old('plant_id', $asset->plant_id) == $p->id)>{{ $p->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Department</label>
                        <select name="department_id" class="form-select"><option value="">-</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $asset->department_id) == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Location</label><input name="location" class="form-control" value="{{ old('location', $asset->location) }}"></div>
                    <div class="col-md-4"><label class="form-label">Machine Number</label><input name="machine_number" class="form-control" value="{{ old('machine_number', $asset->machine_number) }}"></div>
                    <div class="col-md-4"><label class="form-label required">Criticality</label>
                        <select name="criticality" class="form-select">@foreach(\App\Models\Asset::CRITICALITIES as $k => $v)<option value="{{ $k }}" @selected(old('criticality', $asset->criticality) == $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label required">Operating Status</label>
                        <select name="status" class="form-select">@foreach(\App\Models\Asset::STATUSES as $k => $v)<option value="{{ $k }}" @selected(old('status', $asset->status) == $k)>{{ $v }}</option>@endforeach</select></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Manufacturer &amp; Dates</div>
                <div class="card-body row g-3">
                    <div class="col-md-4"><label class="form-label">Manufacturer</label><input name="manufacturer" class="form-control" value="{{ old('manufacturer', $asset->manufacturer) }}"></div>
                    <div class="col-md-4"><label class="form-label">Model Number</label><input name="model_number" class="form-control" value="{{ old('model_number', $asset->model_number) }}"></div>
                    <div class="col-md-4"><label class="form-label">Serial Number</label><input name="serial_number" class="form-control" value="{{ old('serial_number', $asset->serial_number) }}"></div>
                    <div class="col-md-3"><label class="form-label">Purchase Date</label><input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}"></div>
                    <div class="col-md-3"><label class="form-label">Installation Date</label><input type="date" name="installation_date" class="form-control" value="{{ old('installation_date', $asset->installation_date?->format('Y-m-d')) }}"></div>
                    <div class="col-md-3"><label class="form-label">Commissioning Date</label><input type="date" name="commissioning_date" class="form-control" value="{{ old('commissioning_date', $asset->commissioning_date?->format('Y-m-d')) }}"></div>
                    <div class="col-md-3"><label class="form-label">Warranty Expiry</label><input type="date" name="warranty_expiry_date" class="form-control" value="{{ old('warranty_expiry_date', $asset->warranty_expiry_date?->format('Y-m-d')) }}"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Technical</div>
                <div class="card-body row g-3">
                    <div class="col-md-4"><label class="form-label">Current Running Hours</label><input type="number" step="0.01" name="current_running_hours" class="form-control" value="{{ old('current_running_hours', $asset->current_running_hours) }}"></div>
                    <div class="col-md-4"><label class="form-label">Expected Life (years)</label><input type="number" step="0.5" name="expected_life_years" class="form-control" value="{{ old('expected_life_years', $asset->expected_life_years) }}"></div>
                    <div class="col-md-4"><label class="form-label">Photo</label><input type="file" name="image" class="form-control" accept="image/*"></div>
                    <div class="col-12"><hr class="my-1"><small class="text-muted">Rated capacity is used by the <strong>Machine Capacity report</strong>. Example: 200 kg per 8 hours.</small></div>
                    <div class="col-md-4"><label class="form-label">Rated Capacity</label><input type="number" step="0.01" name="rated_capacity" class="form-control" value="{{ old('rated_capacity', $asset->rated_capacity) }}" placeholder="200"></div>
                    <div class="col-md-4"><label class="form-label">Capacity Unit</label><input name="capacity_unit" class="form-control" value="{{ old('capacity_unit', $asset->capacity_unit) }}" placeholder="kg / litre / pcs"></div>
                    <div class="col-md-4"><label class="form-label">Per (hours)</label><input type="number" step="0.5" name="capacity_hours" class="form-control" value="{{ old('capacity_hours', $asset->capacity_hours) }}" placeholder="8"></div>
                    <div class="col-12"><label class="form-label">Technical Specifications</label><textarea name="specifications" class="form-control" rows="3">{{ old('specifications', $asset->specifications) }}</textarea></div>
                    <div class="col-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $asset->remarks) }}</textarea></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Documents</div>
                <div class="card-body">
                    @include('partials.upload-field', ['label' => 'Manuals, drawings, warranty, photos'])
                    @if($asset->image)<img src="{{ $asset->image_url }}" class="img-fluid rounded mb-2" alt="">@endif
                </div>
            </div>
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> {{ $asset->exists ? 'Update Asset' : 'Create Asset' }}</button>
                    <a href="{{ $asset->exists ? route('assets.show', $asset) : route('assets.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
