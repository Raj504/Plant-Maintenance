@extends('layouts.app')
@section('title', 'PM Schedules')
@section('page-title', 'PM Schedules')
@section('breadcrumb')<li class="breadcrumb-item active">PM Schedules</li>@endsection
@section('page-actions')
<a href="{{ route('pm.logs.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-journal-check me-1"></i> Completed PM Logs</a>
@can('pm.pm.manage')<form method="POST" action="{{ route('pm.run-scheduler') }}">@csrf<button class="btn btn-primary btn-sm"><i class="bi bi-arrow-repeat me-1"></i> Run Due Check</button></form>@endcan
@endsection
@section('content')
<form class="filter-bar row g-2" method="GET">
    <div class="col-md-3"><select name="status" class="form-select" data-autosubmit><option value="">Open (due / in progress)</option>@foreach(['due' => 'Due', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'skipped' => 'Skipped'] as $k => $v)<option value="{{ $k }}" @selected(request('status') == $k)>{{ $v }}</option>@endforeach</select></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Plan</th><th>Asset</th><th>Due</th><th>Trigger</th><th>Technician</th><th>Work Order</th><th>Status</th><th></th></tr></thead>
    <tbody>@forelse($schedules as $s)
    <tr class="{{ $s->due_date && $s->due_date->isPast() && $s->status !== 'completed' ? 'table-danger' : '' }}">
        <td><a href="{{ route('pm.plans.show', $s->plan) }}">{{ $s->plan->name }}</a></td><td>{{ $s->plan->asset->name }}</td>
        <td>{{ show_date($s->due_date) }} {{ $s->due_hours ? '/ ' . ($s->due_hours + 0) . ' h' : '' }}</td><td>{{ status_label($s->trigger_reason) }}</td><td>{{ $s->plan->technician?->name ?? '-' }}</td>
        <td>@if($s->workOrder)<a href="{{ route('work-orders.show', $s->workOrder) }}">{{ $s->workOrder->wo_no }}</a> {!! status_badge($s->workOrder->status) !!}@endif</td>
        <td>{!! status_badge($s->status) !!}</td>
        <td class="text-end">@can('pm.pm.execute')@if(in_array($s->status, ['due', 'in_progress']))<a href="{{ route('pm.schedules.complete', $s) }}" class="btn btn-sm btn-success"><i class="bi bi-list-check me-1"></i> Complete</a>@endif @endcan</td>
    </tr>
    @empty<tr><td colspan="8">@include('partials.empty', ['message' => 'No schedules. Run the due check to generate work orders for due plans.'])</td></tr>@endforelse</tbody></table></div>
    @if($schedules->hasPages())<div class="card-footer">{{ $schedules->links() }}</div>@endif
</div>
@endsection
