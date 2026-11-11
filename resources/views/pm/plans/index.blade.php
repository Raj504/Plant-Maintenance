@extends('layouts.app')
@section('title', 'PM Plans')
@section('page-title', 'Preventive Maintenance Plans')
@section('breadcrumb')<li class="breadcrumb-item active">PM Plans</li>@endsection
@section('page-actions')
@can('pm.pm.manage')
<form method="POST" action="{{ route('pm.run-scheduler') }}">@csrf<button class="btn btn-outline-secondary btn-sm" title="Generate work orders for all due plans"><i class="bi bi-arrow-repeat me-1"></i> Run Due Check</button></form>
<a href="{{ route('pm.plans.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Plan</a>
@endcan
@endsection

@section('content')
<form class="filter-bar row g-2 align-items-end" method="GET">
    <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Plan name / number"></div>
    <div class="col-md-3"><select name="asset_id" class="form-select" data-autosubmit><option value="">All assets</option>@foreach($assets as $a)<option value="{{ $a->id }}" @selected(request('asset_id') == $a->id)>{{ $a->asset_code }} - {{ $a->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="trigger_type" class="form-select" data-autosubmit><option value="">All triggers</option>@foreach(\App\Models\PmPlan::TRIGGERS as $k => $v)<option value="{{ $k }}" @selected(request('trigger_type') == $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="due" class="form-select" data-autosubmit><option value="">All</option><option value="overdue" @selected(request('due') == 'overdue')>Overdue</option><option value="upcoming" @selected(request('due') == 'upcoming')>Due in 7 days</option></select></div>
    <div class="col-md-2 d-flex gap-1"><button class="btn btn-primary"><i class="bi bi-search"></i></button><a href="{{ route('pm.plans.index') }}" class="btn btn-light"><i class="bi bi-x"></i></a></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Plan</th><th>Asset</th><th>Trigger</th><th>Interval</th><th>Last Done</th><th>Next Due</th><th>Technician</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @forelse($plans as $p)
    <tr>
        <td><a href="{{ route('pm.plans.show', $p) }}" class="fw-semibold">{{ $p->name }}</a><div class="text-muted small">{{ $p->plan_no }}</div></td>
        <td>{{ $p->asset->name }}@if($p->component)<div class="text-muted small">{{ $p->component->name }}</div>@endif</td>
        <td class="small">{{ \App\Models\PmPlan::TRIGGERS[$p->trigger_type] }}</td>
        <td class="small">{{ $p->interval_days ? $p->interval_days . ' days' : '' }}{{ $p->interval_days && $p->interval_hours ? ' / ' : '' }}{{ $p->interval_hours ? $p->interval_hours + 0 . ' hrs' : '' }}</td>
        <td class="small">{{ show_date($p->last_done_date) }}{{ $p->last_done_hours !== null ? ' / ' . ($p->last_done_hours + 0) . ' h' : '' }}</td>
        <td class="small {{ $p->isOverdue() ? 'text-danger fw-semibold' : '' }}">{{ show_date($p->next_due_date) }}{{ $p->next_due_hours !== null ? ' / ' . ($p->next_due_hours + 0) . ' h' : '' }}<div class="text-muted">now {{ $p->asset->current_running_hours + 0 }} h</div></td>
        <td class="small">{{ $p->technician?->name ?? $p->team?->name ?? '-' }}</td>
        <td>@if(!$p->is_active){!! status_badge('inactive') !!}@elseif($p->openSchedule)<a href="{{ route('work-orders.show', $p->openSchedule->work_order_id) }}">{!! status_badge('in_progress', 'WO Open') !!}</a>@elseif($p->isOverdue()){!! status_badge('overdue') !!}@else{!! status_badge('active', 'Scheduled') !!}@endif</td>
        <td class="text-end text-nowrap"><a href="{{ route('pm.plans.show', $p) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>@can('pm.pm.manage')<a href="{{ route('pm.plans.edit', $p) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>@endcan</td>
    </tr>
    @empty<tr><td colspan="9">@include('partials.empty', ['message' => 'No PM plans yet.'])</td></tr>@endforelse
    </tbody></table></div>
    @if($plans->hasPages())<div class="card-footer">{{ $plans->links() }}</div>@endif
</div>
@endsection
