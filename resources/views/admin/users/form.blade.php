@extends('layouts.app')
@section('title', $user->exists ? 'Edit User' : 'New User')
@section('page-title', $user->exists ? 'Edit ' . $user->name : 'New User')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li><li class="breadcrumb-item active">{{ $user->exists ? 'Edit' : 'New' }}</li>@endsection
@section('content')
<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
    @csrf @if($user->exists) @method('PUT') @endif
    <div class="row"><div class="col-lg-7">
        <div class="card"><div class="card-header">Employee Details</div><div class="card-body row g-3">
            <div class="col-md-4"><label class="form-label">Employee Code</label><input name="employee_code" class="form-control" value="{{ old('employee_code', $user->employee_code) }}"></div>
            <div class="col-md-8"><label class="form-label required">Full Name</label><input name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
            <div class="col-md-6"><label class="form-label required">Email (login)</label><input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
            <div class="col-md-6"><label class="form-label {{ $user->exists ? '' : 'required' }}">Password</label><input type="password" name="password" class="form-control" {{ $user->exists ? 'placeholder=Leave blank to keep' : 'required' }}></div>
            <div class="col-md-4"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">-</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $user->department_id) == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Designation</label><input name="designation" class="form-control" value="{{ old('designation', $user->designation) }}"></div>
            <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $user->phone) }}"></div>
            <div class="col-md-6"><label class="form-label">Maintenance Teams</label><select name="teams[]" class="form-select" multiple size="3">@foreach($teams as $t)<option value="{{ $t->id }}" @selected(in_array($t->id, old('teams', $user->exists ? $user->teams->pluck('id')->all() : [])))>{{ $t->name }}</option>@endforeach</select></div>
            <div class="col-md-6 d-flex align-items-end"><div class="form-check form-switch"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="act" @checked(old('is_active', $user->is_active ?? true))><label class="form-check-label" for="act">Active (can log in)</label></div></div>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-header">Roles <small class="text-muted fw-normal">(a user may hold several)</small></div><div class="card-body">
            @php $userRoles = old('roles', $user->exists ? $user->roles->pluck('id')->all() : []); @endphp
            @foreach($roles as $r)
            <div class="form-check mb-1"><input type="checkbox" name="roles[]" value="{{ $r->id }}" class="form-check-input" id="role{{ $r->id }}" @checked(in_array($r->id, $userRoles))><label class="form-check-label" for="role{{ $r->id }}"><strong>{{ $r->name }}</strong> <span class="text-muted small">— {{ $r->description }}</span></label></div>
            @endforeach
        </div></div>
        <div class="card"><div class="card-body d-grid gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save User</button><a href="{{ route('admin.users.index') }}" class="btn btn-light">Cancel</a></div></div>
    </div></div>
</form>
@endsection
