@extends('layouts.app')
@section('title', $vendor->exists ? 'Edit Vendor' : 'New Vendor')
@section('page-title', $vendor->exists ? 'Edit ' . $vendor->name : 'New Vendor')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('vendors.index') }}">Vendors</a></li><li class="breadcrumb-item active">{{ $vendor->exists ? 'Edit' : 'New' }}</li>@endsection
@section('content')
<form method="POST" action="{{ $vendor->exists ? route('vendors.update', $vendor) : route('vendors.store') }}"><div class="row"><div class="col-lg-7"><div class="card"><div class="card-body row g-3">
    @csrf @if($vendor->exists) @method('PUT') @endif
    <div class="col-md-4"><label class="form-label required">Code</label><input name="code" class="form-control" value="{{ old('code', $vendor->code) }}" required></div>
    <div class="col-md-8"><label class="form-label required">Name</label><input name="name" class="form-control" value="{{ old('name', $vendor->name) }}" required></div>
    <div class="col-md-4"><label class="form-label">Contact Person</label><input name="contact_person" class="form-control" value="{{ old('contact_person', $vendor->contact_person) }}"></div>
    <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $vendor->phone) }}"></div>
    <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $vendor->email) }}"></div>
    <div class="col-md-6"><label class="form-label">GST Number</label><input name="gst_number" class="form-control" value="{{ old('gst_number', $vendor->gst_number) }}"></div>
    <div class="col-md-6"><label class="form-label">Services Offered</label><input name="services" class="form-control" value="{{ old('services', $vendor->services) }}"></div>
    <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2">{{ old('address', $vendor->address) }}</textarea></div>
    <div class="col-md-8"><label class="form-label">Remarks</label><input name="remarks" class="form-control" value="{{ old('remarks', $vendor->remarks) }}"></div>
    <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="act" @checked(old('is_active', $vendor->is_active ?? true))><label class="form-check-label" for="act">Active</label></div></div>
    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save</button> <a href="{{ route('vendors.index') }}" class="btn btn-light">Cancel</a></div>
</div></div></div></div></form>
@endsection
