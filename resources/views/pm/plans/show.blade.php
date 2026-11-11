@extends('layouts.app')
@section('title', $plan->plan_no)
@section('page-title'){{ $plan->name }} {!! $plan->is_active ? '' : status_badge('inactive') !!} {!! $plan->isOverdue() ? status_badge('overdue') : '' !!}@endsection
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('pm.plans.index') }}">PM Plans</a></li><li class="breadcrumb-item active">{{ $plan->plan_no }}</li>@endsection
@section('page-actions')
@can('pm.pm.manage')
@if($plan->is_active && !$plan->openSchedule)<form method="POST" action="{{ route('pm.plans.generate', $plan) }}">@csrf<button class="btn btn-outline-primary btn-sm"><i class="bi bi-lightning me-1"></i> Generate Work Order Now</button></form>@endif
<form method="POST" action="{{ route('pm.plans.toggle', $plan) }}">@csrf<button class="btn btn-outline-secondary btn-sm">{{ $plan->is_active ? 'Deactivate' : 'Activate' }}</button></form>
<a href="{{ route('pm.plans.edit', $plan) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>
@endcan
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card"><div class="card-header">{{ $plan->plan_no }}</div><div class="card-body">
            <dl class="detail-list">
                <dt>Asset</dt><dd><a href="{{ route('assets.show', $plan->asset) }}">{{ $plan->asset->asset_code }} – {{ $plan->asset->name }}</a>{{ $plan->component ? ' › ' . $plan->component->name : '' }}</dd>
                <dt>Category</dt><dd>{{ $plan->category?->name ?? '-' }}</dd>
                <dt>Trigger</dt><dd>{{ \App\Models\PmPlan::TRIGGERS[$plan->trigger_type] }}</dd>
                <dt>Interval</dt><dd>{{ $plan->interval_days ? $plan->interval_days . ' days' : '' }}{{ $plan->interval_days && $plan->interval_hours ? ' or ' : '' }}{{ $plan->interval_hours ? ($plan->interval_hours + 0) . ' running hours' : '' }}</dd>
                <dt>Last Done</dt><dd>{{ show_date($plan->last_done_date) }}{{ $plan->last_done_hours !== null ? ' at ' . ($plan->last_done_hours + 0) . ' hrs' : '' }}</dd>
                <dt>Next Due</dt><dd class="fw-semibold {{ $plan->isOverdue() ? 'text-danger' : '' }}">{{ show_date($plan->next_due_date) }}{{ $plan->next_due_hours !== null ? ' or ' . ($plan->next_due_hours + 0) . ' hrs' : '' }} <span class="text-muted fw-normal small">(asset now at {{ $plan->asset->current_running_hours + 0 }} hrs)</span></dd>
                <dt>Team / Technician</dt><dd>{{ $plan->team?->name ?? '-' }} / {{ $plan->technician?->name ?? '-' }}</dd>
                <dt>Estimate</dt><dd>{{ $plan->estimated_duration_hours + 0 }} hrs · {{ money($plan->estimated_cost) }}</dd>
            </dl>
            @if($plan->remarks)<p class="small text-muted mb-0">{{ $plan->remarks }}</p>@endif
        </div></div>

        <div class="card"><div class="card-header">Schedules (due instances)</div><div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Generated</th><th>Due</th><th>Trigger</th><th>Work Order</th><th>Status</th><th></th></tr></thead>
            <tbody>@forelse($plan->schedules as $s)
            <tr><td class="small">{{ show_datetime($s->generated_at) }}</td><td class="small">{{ show_date($s->due_date) }} {{ $s->due_hours ? '/ ' . ($s->due_hours + 0) . ' h' : '' }}</td><td>{{ status_label($s->trigger_reason) }}</td><td>@if($s->workOrder)<a href="{{ route('work-orders.show', $s->workOrder) }}">{{ $s->workOrder->wo_no }}</a> {!! status_badge($s->workOrder->status) !!}@endif</td><td>{!! status_badge($s->status) !!}</td>
                <td class="text-end">@can('pm.pm.execute')@if($s->status !== 'completed' && $s->status !== 'skipped')<a href="{{ route('pm.schedules.complete', $s) }}" class="btn btn-sm btn-success"><i class="bi bi-list-check me-1"></i> Complete</a>@endif @endcan</td></tr>
            @empty<tr><td colspan="6" class="text-muted small text-center py-3">No schedules generated yet</td></tr>@endforelse</tbody></table></div></div>

        <div class="card"><div class="card-header">Completed PM History</div><div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Date</th><th>Hours</th><th>By</th><th>Checklist</th><th class="text-end">Cost</th><th>Remarks</th></tr></thead>
            <tbody>@forelse($plan->logs as $l)
            <tr><td><a href="{{ route('pm.logs.show', $l) }}">{{ show_date($l->completed_date) }}</a></td><td>{{ $l->running_hours_at_completion + 0 }}</td><td>{{ $l->completer->name }}</td><td class="small"><span class="text-success">{{ $l->items->where('result', 'ok')->count() }} OK</span> · <span class="text-danger">{{ $l->items->where('result', 'not_ok')->count() }} Not OK</span> · {{ $l->items->where('result', 'na')->count() }} N/A</td><td class="text-end">{{ money($l->actual_cost) }}</td><td class="small">{{ $l->remarks }}</td></tr>
            @empty<tr><td colspan="6" class="text-muted small text-center py-3">No completed PM yet</td></tr>@endforelse</tbody></table></div></div>
    </div>
    <div class="col-xl-4">
        <div class="card"><div class="card-header">Checklist: {{ $plan->checklist?->name ?? 'None' }}</div>
            <ol class="list-group list-group-numbered list-group-flush">@forelse($plan->checklist?->items ?? [] as $i)<li class="list-group-item small">{{ $i->item_text }}</li>@empty<li class="list-group-item text-muted small">No checklist attached</li>@endforelse</ol></div>
        <div class="card"><div class="card-header">History</div><div class="card-body">@include('partials.history', ['history' => $plan->history])</div></div>
    </div>
</div>
@endsection
