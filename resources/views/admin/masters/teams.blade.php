@extends('layouts.app')
@section('title', 'Teams')
@section('page-title', 'Masters')
@section('breadcrumb')<li class="breadcrumb-item active">Masters</li>@endsection
@section('content')
@include('admin.masters._nav')
<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Team</th><th>Lead</th><th>Members</th><th>Status</th><th></th></tr></thead>
    <tbody>@foreach($teams as $t)<tr><td class="fw-semibold">{{ $t->name }}<div class="text-muted small">{{ $t->description }}</div></td><td class="small">{{ $t->lead?->name }}</td><td class="small">{{ $t->members->pluck('name')->join(', ') }}</td><td>{!! status_badge($t->is_active ? 'active' : 'inactive') !!}</td><td class="text-end text-nowrap"><a href="{{ route('admin.teams.edit', $t) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>@if($t->is_active)<form method="POST" action="{{ route('admin.teams.destroy', $t) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger" title="Deactivate"><i class="bi bi-x-circle"></i></button></form>@endif</td></tr>@endforeach</tbody></table></div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-header">{{ $team->exists ? 'Edit Team' : 'Add Team' }}</div><div class="card-body"><form method="POST" action="{{ $team->exists ? route('admin.teams.update', $team) : route('admin.teams.store') }}">@csrf @if($team->exists) @method('PUT') @endif
    <div class="mb-2"><label class="form-label required">Name</label><input name="name" class="form-control" value="{{ old('name', $team->name) }}" required></div>
    <div class="mb-2"><label class="form-label">Description</label><input name="description" class="form-control" value="{{ old('description', $team->description) }}"></div>
    <div class="mb-2"><label class="form-label">Team Lead</label><select name="lead_user_id" class="form-select"><option value="">-</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('lead_user_id', $team->lead_user_id) == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
    <div class="mb-2"><label class="form-label">Members</label><select name="members[]" class="form-select" multiple size="6">@foreach($users as $u)<option value="{{ $u->id }}" @selected(in_array($u->id, old('members', $team->exists ? $team->members->pluck('id')->all() : [])))>{{ $u->name }}</option>@endforeach</select></div>
    <div class="form-check form-switch mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="act" @checked(old('is_active', $team->is_active ?? true))><label class="form-check-label" for="act">Active</label></div>
    <button class="btn btn-primary w-100">Save</button>@if($team->exists)<a href="{{ route('admin.teams.index') }}" class="btn btn-light w-100 mt-1">Cancel</a>@endif</form></div></div></div></div>
@endsection
