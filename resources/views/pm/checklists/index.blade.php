@extends('layouts.app')
@section('title', 'Checklists')
@section('page-title', 'PM Checklists')
@section('breadcrumb')<li class="breadcrumb-item active">Checklists</li>@endsection
@section('page-actions')@can('pm.pm.manage')<a href="{{ route('pm.checklists.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Checklist</a>@endcan@endsection
@section('content')
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Name</th><th>Description</th><th class="text-end">Items</th><th>Status</th><th></th></tr></thead>
    <tbody>@forelse($checklists as $c)
    <tr><td class="fw-semibold">{{ $c->name }}</td><td class="small text-muted">{{ $c->description }}</td><td class="text-end">{{ $c->items_count }}</td><td>{!! status_badge($c->is_active ? 'active' : 'inactive') !!}</td>
        <td class="text-end text-nowrap">@can('pm.pm.manage')<a href="{{ route('pm.checklists.edit', $c) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a><form method="POST" action="{{ route('pm.checklists.destroy', $c) }}" class="d-inline" data-confirm="Delete this checklist?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button></form>@endcan</td></tr>
    @empty<tr><td colspan="5">@include('partials.empty', ['message' => 'No checklists.'])</td></tr>@endforelse</tbody></table></div></div>
@endsection
