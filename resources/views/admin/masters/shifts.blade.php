@extends('layouts.app')
@section('title', 'Shifts')
@section('page-title', 'Masters')
@section('breadcrumb')<li class="breadcrumb-item active">Masters</li>@endsection
@section('content')
@include('admin.masters._nav')
<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Shift</th><th>Start</th><th>End</th><th>Status</th><th></th></tr></thead>
    <tbody>@foreach($shifts as $s)<tr><td class="fw-semibold">{{ $s->name }}</td><td>{{ substr($s->start_time, 0, 5) }}</td><td>{{ substr($s->end_time, 0, 5) }}</td><td>{!! status_badge($s->is_active ? 'active' : 'inactive') !!}</td><td class="text-end text-nowrap"><a href="{{ route('admin.shifts.edit', $s) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>@if($s->is_active)<form method="POST" action="{{ route('admin.shifts.destroy', $s) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger" title="Deactivate"><i class="bi bi-x-circle"></i></button></form>@endif</td></tr>@endforeach</tbody></table></div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-header">{{ $shift->exists ? 'Edit Shift' : 'Add Shift' }}</div><div class="card-body"><form method="POST" action="{{ $shift->exists ? route('admin.shifts.update', $shift) : route('admin.shifts.store') }}">@csrf @if($shift->exists) @method('PUT') @endif
    <div class="mb-2"><label class="form-label required">Name</label><input name="name" class="form-control" value="{{ old('name', $shift->name) }}" required></div>
    <div class="row"><div class="col-6 mb-2"><label class="form-label required">Start</label><input type="time" name="start_time" class="form-control" value="{{ old('start_time', $shift->start_time ? substr($shift->start_time, 0, 5) : '') }}" required></div><div class="col-6 mb-2"><label class="form-label required">End</label><input type="time" name="end_time" class="form-control" value="{{ old('end_time', $shift->end_time ? substr($shift->end_time, 0, 5) : '') }}" required></div></div>
    <div class="form-check form-switch mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="act" @checked(old('is_active', $shift->is_active ?? true))><label class="form-check-label" for="act">Active</label></div>
    <button class="btn btn-primary w-100">Save</button>@if($shift->exists)<a href="{{ route('admin.shifts.index') }}" class="btn btn-light w-100 mt-1">Cancel</a>@endif</form></div></div></div></div>
@endsection
