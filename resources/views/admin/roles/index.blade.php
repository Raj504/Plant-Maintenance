@extends('layouts.app')
@section('title', 'Roles')
@section('page-title', 'Roles & Permissions')
@section('breadcrumb')<li class="breadcrumb-item active">Roles</li>@endsection
@section('page-actions')<a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Role</a>@endsection
@section('content')
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Role</th><th>Description</th><th class="text-end">Permissions</th><th class="text-end">Users</th><th>Type</th><th></th></tr></thead>
    <tbody>@foreach($roles as $r)
    <tr><td class="fw-semibold">{{ $r->name }}<div class="text-muted small">{{ $r->slug }}</div></td><td class="small">{{ $r->description }}</td><td class="text-end">{{ $r->slug === 'super-admin' ? 'All' : $r->permissions_count }}</td><td class="text-end">{{ $r->users_count }}</td><td>{!! $r->is_system ? status_badge('secondary', 'System') : status_badge('info', 'Custom') !!}</td>
        <td class="text-end text-nowrap"><a href="{{ route('admin.roles.edit', $r) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>@if(!$r->is_system)<form method="POST" action="{{ route('admin.roles.destroy', $r) }}" class="d-inline" data-confirm="Delete this role?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button></form>@endif</td></tr>
    @endforeach</tbody></table></div></div>
<div class="alert alert-info small"><i class="bi bi-shield-check me-1"></i> Permissions are enforced on the server for every route (middleware) and action, not only in the menu. Changes take effect on the user's next request.</div>
@endsection
