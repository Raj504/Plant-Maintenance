@extends('layouts.app')
@section('title', 'PM Logs')
@section('page-title', 'Completed PM Logs')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('pm.schedules.index') }}">PM Schedules</a></li><li class="breadcrumb-item active">Logs</li>@endsection
@section('content')
<form class="filter-bar row g-2" method="GET">
    <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
    <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
    <div class="col-md-2"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Date</th><th>Plan</th><th>Asset</th><th>Completed By</th><th>Results</th><th class="text-end">Hours</th><th class="text-end">Cost</th><th>WO</th></tr></thead>
    <tbody>@forelse($logs as $l)
    <tr><td><a href="{{ route('pm.logs.show', $l) }}">{{ show_date($l->completed_date) }}</a></td><td>{{ $l->plan->name }}</td><td>{{ $l->asset->name }}</td><td>{{ $l->completer->name }}</td>
        <td class="small"><span class="text-success">{{ $l->items->where('result', 'ok')->count() }} OK</span> · <span class="text-danger">{{ $l->items->where('result', 'not_ok')->count() }} Not OK</span></td>
        <td class="text-end">{{ $l->running_hours_at_completion + 0 }}</td><td class="text-end">{{ money($l->actual_cost) }}</td><td>@if($l->workOrder)<a href="{{ route('work-orders.show', $l->workOrder) }}">{{ $l->workOrder->wo_no }}</a>@endif</td></tr>
    @empty<tr><td colspan="8">@include('partials.empty', ['message' => 'No completed PM logs.'])</td></tr>@endforelse</tbody></table></div>
    @if($logs->hasPages())<div class="card-footer">{{ $logs->links() }}</div>@endif
</div>
@endsection
