@extends('layouts.app')
@section('title', $role->exists ? 'Edit Role' : 'New Role')
@section('page-title', $role->exists ? 'Edit Role: ' . $role->name : 'New Role')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('admin.roles.index') }}">Roles</a></li><li class="breadcrumb-item active">{{ $role->exists ? 'Edit' : 'New' }}</li>@endsection
@section('content')
<form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}">
    @csrf @if($role->exists) @method('PUT') @endif
    <div class="card"><div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label required">Role Name</label><input name="name" class="form-control" value="{{ old('name', $role->name) }}" required></div>
        <div class="col-md-8"><label class="form-label">Description</label><input name="description" class="form-control" value="{{ old('description', $role->description) }}"></div>
    </div></div>
    @if($role->slug === 'super-admin')<div class="alert alert-warning">Super Admin always has every permission.</div>@endif
    <div class="card"><div class="card-header"><span>Permissions</span><span><button type="button" class="btn btn-sm btn-light" onclick="document.querySelectorAll('.perm').forEach(c=>c.checked=true)">Select all</button> <button type="button" class="btn btn-sm btn-light" onclick="document.querySelectorAll('.perm').forEach(c=>c.checked=false)">Clear</button></span></div>
        <div class="card-body row g-3">
            @foreach($permissions as $module => $perms)
            <div class="col-md-4 col-xl-3">
                <div class="border rounded p-2 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-1"><strong class="small text-uppercase">{{ $module }}</strong><button type="button" class="btn btn-link btn-sm p-0" onclick="this.closest('.border').querySelectorAll('.perm').forEach(c=>c.checked=!c.checked)">toggle</button></div>
                    @foreach($perms as $p)
                    <div class="form-check"><input type="checkbox" name="permissions[]" value="{{ $p->id }}" class="form-check-input perm" id="p{{ $p->id }}" @checked(in_array($p->id, old('permissions', $selected))) {{ $role->slug === 'super-admin' ? 'disabled checked' : '' }}><label class="form-check-label small" for="p{{ $p->id }}">{{ $p->label }}<div class="text-muted" style="font-size:.68rem">{{ $p->name }}</div></label></div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Role</button> <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Cancel</a>
</form>
@endsection
