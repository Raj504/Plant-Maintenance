@extends('layouts.app')
@section('title', 'PM Log')
@section('page-title', 'PM Log – ' . $log->plan->name)
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('pm.logs.index') }}">PM Logs</a></li><li class="breadcrumb-item active">{{ show_date($log->completed_date) }}</li>@endsection
@section('page-actions')<button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>@endsection
@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card"><div class="card-body">
            <dl class="detail-list">
                <dt>Asset</dt><dd><a href="{{ route('assets.show', $log->asset) }}">{{ $log->asset->asset_code }} – {{ $log->asset->name }}</a></dd>
                <dt>Plan</dt><dd><a href="{{ route('pm.plans.show', $log->plan) }}">{{ $log->plan->plan_no }} – {{ $log->plan->name }}</a></dd>
                <dt>Completed</dt><dd>{{ show_date($log->completed_date) }} by {{ $log->completer->name }}</dd>
                <dt>Running Hours</dt><dd>{{ $log->running_hours_at_completion + 0 }} hrs</dd>
                <dt>Duration / Cost</dt><dd>{{ $log->duration_hours + 0 }} hrs / {{ money($log->actual_cost) }}</dd>
                <dt>Work Order</dt><dd>@if($log->workOrder)<a href="{{ route('work-orders.show', $log->workOrder) }}">{{ $log->workOrder->wo_no }}</a>@else - @endif</dd>
            </dl>
            @if($log->observations)<p class="small"><strong>Observations:</strong> {{ $log->observations }}</p>@endif
            @if($log->remarks)<p class="small text-muted mb-0"><strong>Remarks:</strong> {{ $log->remarks }}</p>@endif
        </div></div>
        <div class="card"><div class="card-header">Checklist Results</div><div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>#</th><th>Check</th><th>Result</th><th>Remarks</th></tr></thead>
            <tbody>@forelse($log->items as $i)<tr><td>{{ $loop->iteration }}</td><td>{{ $i->item_text }}</td><td>{!! status_badge($i->result, strtoupper(str_replace('_', ' ', $i->result))) !!}</td><td class="small">{{ $i->remarks }}</td></tr>@empty<tr><td colspan="4" class="text-muted small">No checklist items</td></tr>@endforelse</tbody></table></div></div>
    </div>
    <div class="col-xl-4"><div class="card"><div class="card-header">Evidence</div><div class="card-body">@include('partials.attachments', ['attachments' => $log->attachments])</div></div></div>
</div>
@endsection
