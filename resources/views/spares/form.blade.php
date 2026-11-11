@extends('layouts.app')
@section('title', $spare->exists ? 'Edit Spare' : 'New Spare')
@section('page-title', $spare->exists ? 'Edit ' . $spare->code : 'New Spare Part')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('spares.index') }}">Spares</a></li><li class="breadcrumb-item active">{{ $spare->exists ? 'Edit' : 'New' }}</li>@endsection
@section('content')
<form method="POST" action="{{ $spare->exists ? route('spares.update', $spare) : route('spares.store') }}" enctype="multipart/form-data">
    @csrf @if($spare->exists) @method('PUT') @endif
    <div class="row"><div class="col-lg-8"><div class="card"><div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label required">Spare Code</label><input name="code" class="form-control" value="{{ old('code', $spare->code) }}" required></div>
        <div class="col-md-8"><label class="form-label required">Name</label><input name="name" class="form-control" value="{{ old('name', $spare->name) }}" required></div>
        <div class="col-md-4"><label class="form-label">Category</label><select name="category_id" class="form-select"><option value="">-</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $spare->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label required">Unit of Measurement</label><input name="uom" list="uoms" class="form-control" value="{{ old('uom', $spare->uom) }}" required><datalist id="uoms"><option>Nos</option><option>Kg</option><option>Litre</option><option>Metre</option><option>Set</option><option>Can</option><option>Box</option></datalist></div>
        <div class="col-md-4"><label class="form-label">Unit Cost</label><input type="number" step="0.01" name="unit_cost" class="form-control" value="{{ old('unit_cost', $spare->unit_cost ?? 0) }}"></div>
        <div class="col-md-4"><label class="form-label">Minimum Stock</label><input type="number" step="0.01" name="min_stock" class="form-control" value="{{ old('min_stock', $spare->min_stock ?? 0) }}"></div>
        <div class="col-md-4"><label class="form-label">Reorder Level</label><input type="number" step="0.01" name="reorder_level" class="form-control" value="{{ old('reorder_level', $spare->reorder_level ?? 0) }}"><div class="form-text">Low-stock alert when stock ≤ this</div></div>
        <div class="col-md-4"><label class="form-label">Store Location</label><input name="store_location" class="form-control" value="{{ old('store_location', $spare->store_location) }}" placeholder="Rack A1"></div>
        <div class="col-md-6"><label class="form-label">Preferred Vendor</label><select name="preferred_vendor_id" class="form-select"><option value="">-</option>@foreach($vendors as $v)<option value="{{ $v->id }}" @selected(old('preferred_vendor_id', $spare->preferred_vendor_id) == $v->id)>{{ $v->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Image</label><input type="file" name="image" class="form-control" accept="image/*"></div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ old('description', $spare->description) }}</textarea></div>
        <div class="col-md-8"><label class="form-label">Remarks</label><input name="remarks" class="form-control" value="{{ old('remarks', $spare->remarks) }}"></div>
        <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="act" @checked(old('is_active', $spare->is_active ?? true))><label class="form-check-label" for="act">Active</label></div></div>
    </div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body d-grid gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button><a href="{{ route('spares.index') }}" class="btn btn-light">Cancel</a></div></div>
    <div class="alert alert-info small">Stock quantity is never edited here. It is calculated from the stock ledger: record an <strong>Opening</strong> or <strong>Receipt</strong> transaction on the spare page.</div></div></div>
</form>
@endsection
