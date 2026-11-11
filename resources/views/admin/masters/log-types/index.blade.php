@extends('layouts.app')
@section('title', 'Log Types')
@section('page-title', 'Masters')
@section('breadcrumb')<li class="breadcrumb-item active">Masters</li>@endsection
@section('page-actions')<a href="{{ route('admin.log-types.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Log Type</a>@endsection
@section('content')
@include('admin.masters._nav')
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Code</th><th>Log Type</th><th>Meter Readings</th><th>Extra Fields</th><th>Status</th><th></th></tr></thead>
    <tbody>@foreach($logTypes as $t)<tr><td class="fw-semibold">{{ $t->code }}</td><td>{{ $t->name }}<div class="text-muted small">{{ $t->description }}</div></td><td>{!! $t->has_meter_readings ? '<i class="bi bi-check-circle text-success"></i>' : '-' !!}</td><td class="small">{{ collect($t->fields)->pluck('label')->join(', ') }}</td><td>{!! status_badge($t->is_active ? 'active' : 'inactive') !!}</td><td class="text-end text-nowrap"><a href="{{ route('admin.log-types.edit', $t) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>@if($t->is_active)<form method="POST" action="{{ route('admin.log-types.destroy', $t) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger" title="Deactivate"><i class="bi bi-x-circle"></i></button></form>@endif</td></tr>@endforeach</tbody></table></div></div>
@endsection
