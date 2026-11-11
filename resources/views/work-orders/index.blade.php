@extends('layouts.app')
@section('title', 'Work Orders')
@section('page-title', 'Work Orders')
@section('breadcrumb')<li class="breadcrumb-item active">Work Orders</li>@endsection
@section('page-actions')@can('pm.workorders.create')<a href="{{ route('work-orders.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Manual Work Order</a>@endcan@endsection

@section('content')
<form class="filter-bar row g-2 align-items-end" method="GET">
    <div class="col-md-2"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="WO no / text"></div>
    <div class="col-md-2"><select name="status" class="form-select" data-autosubmit><option value="">All statuses</option>@foreach(\App\Models\WorkOrder::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') == $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="source" class="form-select" data-autosubmit><option value="">All sources</option>@foreach(['request' => 'From request', 'preventive' => 'Preventive', 'emergency' => 'Emergency', 'manual' => 'Manual'] as $k => $v)<option value="{{ $k }}" @selected(request('source') == $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="assigned_to" class="form-select" data-autosubmit><option value="">All technicians</option>@foreach($technicians as $t)<option value="{{ $t->id }}" @selected(request('assigned_to') == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="department_id" class="form-select" data-autosubmit><option value="">All departments</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
    <div class="col-md-2 d-flex gap-1"><button class="btn btn-primary"><i class="bi bi-search"></i></button><a href="{{ route('work-orders.index') }}" class="btn btn-light"><i class="bi bi-x"></i></a></div>
    <div class="col-12 d-flex gap-3 small">
        <a href="{{ route('work-orders.index', ['open' => 1]) }}">Open</a>
        <a href="{{ route('work-orders.index', ['mine' => 1]) }}">Assigned to me</a>
        <a href="{{ route('work-orders.index', ['overdue' => 1]) }}" class="text-danger">Overdue</a>
        <a href="{{ route('work-orders.index', ['status' => 'verification_pending']) }}">Verification pending</a>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>WO No.</th><th>Asset</th><th>Type</th><th>Priority</th><th>Technician</th><th>Planned</th><th>Status</th><th class="text-end">Cost</th><th></th></tr></thead>
            <tbody>
            @forelse($workOrders as $w)
            <tr class="row-{{ $w->priority }}">
                <td><a href="{{ route('work-orders.show', $w) }}" class="fw-semibold">{{ $w->wo_no }}</a><div class="text-muted small">{{ $w->request?->request_no ?? status_label($w->source) }}</div></td>
                <td>{{ $w->asset->name }}<div class="text-muted small">{{ $w->asset->asset_code }}</div></td>
                <td class="small">{{ status_label($w->work_type) }}</td>
                <td>{!! status_badge($w->priority) !!}</td>
                <td class="small">{{ $w->technician?->name ?? '-' }}<div class="text-muted">{{ $w->team?->name }}</div></td>
                <td class="small {{ $w->isOverdue() ? 'text-danger fw-semibold' : '' }}">{{ show_date($w->planned_start) }} – {{ show_date($w->planned_end) }} @if($w->isOverdue())<i class="bi bi-alarm"></i>@endif</td>
                <td>{!! status_badge($w->status) !!}</td>
                <td class="text-end">{{ money($w->actual_cost ?: $w->estimated_cost) }}</td>
                <td><a href="{{ route('work-orders.show', $w) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a></td>
            </tr>
            @empty
            <tr><td colspan="9">@include('partials.empty', ['message' => 'No work orders found.'])</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($workOrders->hasPages())<div class="card-footer d-flex justify-content-between align-items-center"><small class="text-muted">{{ $workOrders->total() }} work orders</small>{{ $workOrders->links() }}</div>@endif
</div>
@endsection
