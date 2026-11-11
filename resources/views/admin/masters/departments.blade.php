@extends('layouts.app')
@section('title', 'Departments')
@section('page-title', 'Masters')
@section('breadcrumb')<li class="breadcrumb-item active">Masters</li>@endsection
@section('content')
@include('admin.masters._nav')
<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Code</th><th>Department</th><th>Plant</th><th>Head</th><th class="text-end">Assets</th><th>Status</th><th></th></tr></thead>
    <tbody>@foreach($departments as $d)<tr><td class="fw-semibold">{{ $d->code }}</td><td>{{ $d->name }}</td><td class="small">{{ $d->plant?->name }}</td><td class="small">{{ $d->head?->name }}</td><td class="text-end">{{ $d->assets_count }}</td><td>{!! status_badge($d->is_active ? 'active' : 'inactive') !!}</td><td class="text-end text-nowrap"><a href="{{ route('admin.departments.edit', $d) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a><form method="POST" action="{{ route('admin.departments.destroy', $d) }}" class="d-inline" data-confirm="Delete?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button></form></td></tr>@endforeach</tbody></table></div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-header">{{ $department->exists ? 'Edit Department' : 'Add Department' }}</div><div class="card-body"><form method="POST" action="{{ $department->exists ? route('admin.departments.update', $department) : route('admin.departments.store') }}">@csrf @if($department->exists) @method('PUT') @endif
    <div class="mb-2"><label class="form-label required">Code</label><input name="code" class="form-control" value="{{ old('code', $department->code) }}" required></div>
    <div class="mb-2"><label class="form-label required">Name</label><input name="name" class="form-control" value="{{ old('name', $department->name) }}" required></div>
    <div class="mb-2"><label class="form-label">Plant</label><select name="plant_id" class="form-select"><option value="">-</option>@foreach($plants as $p)<option value="{{ $p->id }}" @selected(old('plant_id', $department->plant_id) == $p->id)>{{ $p->name }}</option>@endforeach</select></div>
    <div class="mb-2"><label class="form-label">Department Head</label><select name="head_user_id" class="form-select"><option value="">-</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('head_user_id', $department->head_user_id) == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
    <div class="form-check form-switch mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="act" @checked(old('is_active', $department->is_active ?? true))><label class="form-check-label" for="act">Active</label></div>
    <button class="btn btn-primary w-100">Save</button>@if($department->exists)<a href="{{ route('admin.departments.index') }}" class="btn btn-light w-100 mt-1">Cancel</a>@endif</form></div></div></div></div>
@endsection
