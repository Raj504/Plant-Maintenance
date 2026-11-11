@extends('layouts.app')
@section('title', 'Users')
@section('page-title', 'Users')
@section('breadcrumb')<li class="breadcrumb-item active">Users</li>@endsection
@section('page-actions')<a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i> New User</a>@endsection
@section('content')
<form class="filter-bar row g-2" method="GET">
    <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Name / email / employee code"></div>
    <div class="col-md-3"><select name="role_id" class="form-select" data-autosubmit><option value="">All roles</option>@foreach($roles as $r)<option value="{{ $r->id }}" @selected(request('role_id') == $r->id)>{{ $r->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Employee</th><th>Email</th><th>Department</th><th>Designation</th><th>Roles</th><th>Status</th><th></th></tr></thead>
    <tbody>@forelse($users as $u)
    <tr><td><div class="d-flex align-items-center gap-2"><span class="avatar avatar-text">{{ $u->initials }}</span><div><div class="fw-semibold">{{ $u->name }}</div><div class="text-muted small">{{ $u->employee_code }}</div></div></div></td><td class="small">{{ $u->email }}</td><td class="small">{{ $u->department?->name }}</td><td class="small">{{ $u->designation }}</td><td>@foreach($u->roles as $r)<span class="badge text-bg-light border">{{ $r->name }}</span> @endforeach</td><td>{!! status_badge($u->is_active ? 'active' : 'inactive') !!}</td>
        <td class="text-end text-nowrap"><a href="{{ route('admin.users.edit', $u) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a><form method="POST" action="{{ route('admin.users.toggle', $u) }}" class="d-inline">@csrf<button class="btn btn-sm btn-light" title="{{ $u->is_active ? 'Deactivate' : 'Activate' }}"><i class="bi {{ $u->is_active ? 'bi-person-dash' : 'bi-person-check' }}"></i></button></form></td></tr>
    @empty<tr><td colspan="7">@include('partials.empty')</td></tr>@endforelse</tbody></table></div>
    @if($users->hasPages())<div class="card-footer">{{ $users->links() }}</div>@endif
</div>
@endsection
