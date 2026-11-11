@extends('layouts.app')
@section('title', 'Plants')
@section('page-title', 'Masters')
@section('breadcrumb')<li class="breadcrumb-item active">Masters</li>@endsection
@section('content')
@include('admin.masters._nav')
<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Code</th><th>Plant / Unit</th><th>Location</th><th class="text-end">Departments</th><th>Status</th><th></th></tr></thead>
    <tbody>@foreach($plants as $p)<tr><td class="fw-semibold">{{ $p->code }}</td><td>{{ $p->name }}</td><td class="small">{{ $p->location }}</td><td class="text-end">{{ $p->departments_count }}</td><td>{!! status_badge($p->is_active ? 'active' : 'inactive') !!}</td><td class="text-end text-nowrap"><a href="{{ route('admin.plants.edit', $p) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a><form method="POST" action="{{ route('admin.plants.destroy', $p) }}" class="d-inline" data-confirm="Delete?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button></form></td></tr>@endforeach</tbody></table></div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-header">{{ $plant->exists ? 'Edit Plant' : 'Add Plant' }}</div><div class="card-body"><form method="POST" action="{{ $plant->exists ? route('admin.plants.update', $plant) : route('admin.plants.store') }}">@csrf @if($plant->exists) @method('PUT') @endif
    <div class="mb-2"><label class="form-label required">Code</label><input name="code" class="form-control" value="{{ old('code', $plant->code) }}" required></div>
    <div class="mb-2"><label class="form-label required">Name</label><input name="name" class="form-control" value="{{ old('name', $plant->name) }}" required></div>
    <div class="mb-2"><label class="form-label">Location</label><input name="location" class="form-control" value="{{ old('location', $plant->location) }}"></div>
    <div class="form-check form-switch mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="act" @checked(old('is_active', $plant->is_active ?? true))><label class="form-check-label" for="act">Active</label></div>
    <button class="btn btn-primary w-100">Save</button>@if($plant->exists)<a href="{{ route('admin.plants.index') }}" class="btn btn-light w-100 mt-1">Cancel</a>@endif</form></div></div></div></div>
@endsection
