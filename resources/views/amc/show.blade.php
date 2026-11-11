@extends('layouts.app')
@section('title', $amc->amc_no)
@section('page-title'){{ $amc->amc_no }} – {{ $amc->name }} {!! status_badge($amc->status) !!} @if($amc->status === 'active' && $amc->daysToExpiry() <= (int) setting('pm.amc_expiry_alert_days', 30))<span class="badge text-bg-warning">Expires in {{ $amc->daysToExpiry() }} days</span>@endif @endsection
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('amc.index') }}">AMC</a></li><li class="breadcrumb-item active">{{ $amc->amc_no }}</li>@endsection
@section('page-actions')@can('pm.amc.manage')<a href="{{ route('amc.edit', $amc) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>@endcan@endsection
@section('content')
<div class="row g-3"><div class="col-xl-8">
    <div class="card"><div class="card-body"><dl class="detail-list">
        <dt>Vendor</dt><dd><a href="{{ route('vendors.show', $amc->vendor) }}">{{ $amc->vendor->name }}</a> · {{ $amc->contact_person }} {{ $amc->contact_number }}</dd>
        <dt>Period</dt><dd>{{ show_date($amc->start_date) }} → {{ show_date($amc->end_date) }}</dd>
        <dt>Value / Terms</dt><dd>{{ money($amc->contract_value) }} · {{ $amc->payment_terms }}</dd>
        <dt>Service Frequency</dt><dd>{{ status_label($amc->service_frequency) }}</dd>
        <dt>SLA</dt><dd>Response {{ $amc->response_time_hours ?? '-' }} hrs · Resolution {{ $amc->resolution_time_hours ?? '-' }} hrs</dd>
        <dt>Covered Assets</dt><dd>@foreach($amc->assets as $a)<a href="{{ route('assets.show', $a) }}" class="badge text-bg-light border">{{ $a->asset_code }}</a> @endforeach</dd>
        <dt>Scope</dt><dd>{{ $amc->scope_of_work ?? '-' }}</dd>
        <dt>Conditions</dt><dd>{{ $amc->service_conditions ?? '-' }}</dd>
    </dl>@include('partials.attachments', ['attachments' => $amc->attachments, 'canDelete' => auth()->user()->hasPermission('pm.amc.manage')])</div></div>

    <div class="card"><div class="card-header">Scheduled Vendor Visits</div><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Scheduled</th><th>Actual</th><th>Status</th><th>Work Done</th><th></th></tr></thead>
        <tbody>@forelse($amc->visits as $v)
        <tr class="{{ $v->status === 'scheduled' && $v->scheduled_date->isPast() ? 'table-danger' : '' }}"><td>{{ show_date($v->scheduled_date) }}</td><td>{{ show_date($v->actual_date) }}</td><td>{!! status_badge($v->status) !!} {{ $v->status === 'scheduled' && $v->scheduled_date->isPast() ? '(overdue)' : '' }}</td><td class="small">{{ $v->work_done }}</td>
            <td class="text-end">@can('pm.amc.manage')@if($v->status === 'scheduled')<form method="POST" action="{{ route('amc.visits.complete', [$amc, $v]) }}" class="d-flex gap-1 justify-content-end">@csrf<input type="date" name="actual_date" value="{{ today()->toDateString() }}" class="form-control form-control-sm" style="width:140px"><input name="work_done" class="form-control form-control-sm" placeholder="Work done" style="width:160px"><button name="status" value="completed" class="btn btn-sm btn-success" title="Completed"><i class="bi bi-check"></i></button><button name="status" value="missed" class="btn btn-sm btn-outline-danger" title="Missed"><i class="bi bi-x"></i></button></form>@endif @endcan</td></tr>
        @empty<tr><td colspan="5" class="text-muted small text-center py-3">No visits scheduled</td></tr>@endforelse</tbody></table></div>
        @can('pm.amc.manage')<div class="card-footer"><form method="POST" action="{{ route('amc.visits.store', $amc) }}" class="d-flex gap-2">@csrf<input type="date" name="scheduled_date" class="form-control form-control-sm" style="width:160px" required><input name="remarks" class="form-control form-control-sm" placeholder="Remarks"><button class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> Add visit</button></form></div>@endcan
    </div>

    <div class="card"><div class="card-header">Vendor Service History</div><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Date</th><th>Work Order</th><th>Response</th><th>Resolution</th><th>Quality</th><th>Attended</th><th class="text-end">Cost</th><th>Feedback</th></tr></thead>
        <tbody>@forelse($amc->services as $s)<tr><td>{{ show_date($s->service_date) }}</td><td>@if($s->workOrder)<a href="{{ route('work-orders.show', $s->workOrder) }}">{{ $s->workOrder->wo_no }}</a>@endif</td><td>{{ $s->response_hours }}h {!! $s->response_within_sla ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>' !!}</td><td>{{ $s->resolution_hours }}h {!! $s->resolution_within_sla ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-danger"></i>' !!}</td><td>{{ str_repeat('★', $s->work_quality) }}</td><td>{!! $s->attended ? '<i class="bi bi-check text-success"></i>' : '<i class="bi bi-x text-danger"></i>' !!} {!! $s->repeat_failure ? '<span class="badge text-bg-danger">Repeat</span>' : '' !!}</td><td class="text-end">{{ money($s->cost) }}</td><td class="small">{{ $s->feedback }}</td></tr>
        @empty<tr><td colspan="8" class="text-muted small text-center py-3">No service recorded</td></tr>@endforelse</tbody></table></div>
        @can('pm.amc.manage')<div class="card-footer"><form method="POST" action="{{ route('amc.services.store', $amc) }}" class="row g-2 align-items-end">@csrf
            <div class="col-md-2"><label class="form-label small">Date</label><input type="date" name="service_date" class="form-control form-control-sm" value="{{ today()->toDateString() }}" required></div>
            <div class="col-md-2"><label class="form-label small">Response hrs</label><input type="number" step="0.5" name="response_hours" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label small">Resolution hrs</label><input type="number" step="0.5" name="resolution_hours" class="form-control form-control-sm"></div>
            <div class="col-md-1"><label class="form-label small">Quality</label><select name="work_quality" class="form-select form-select-sm">@for($i = 5; $i >= 1; $i--)<option value="{{ $i }}" @selected($i == 4)>{{ $i }}</option>@endfor</select></div>
            <div class="col-md-2"><label class="form-label small">Cost</label><input type="number" step="0.01" name="cost" class="form-control form-control-sm" value="0"></div>
            <div class="col-md-2"><div class="form-check"><input type="checkbox" name="attended" value="1" class="form-check-input" id="att" checked><label class="form-check-label small" for="att">Attended</label></div><div class="form-check"><input type="checkbox" name="repeat_failure" value="1" class="form-check-input" id="rep"><label class="form-check-label small" for="rep">Repeat failure</label></div></div>
            <div class="col-md-1"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus"></i></button></div>
            <div class="col-12"><input name="feedback" class="form-control form-control-sm" placeholder="Feedback"></div>
        </form></div>@endcan
    </div>
</div>
<div class="col-xl-4">
    <div class="card"><div class="card-header">Vendor SLA Rating</div><div class="card-body text-center">@php $rating = $amc->vendor->slaRating(); @endphp<div class="display-5 fw-bold {{ $rating === null ? 'text-muted' : ($rating >= 80 ? 'text-success' : ($rating >= 60 ? 'text-warning' : 'text-danger')) }}">{{ $rating === null ? '-' : $rating . '%' }}</div><div class="small text-muted">Response 20 · Resolution 20 · Quality 30 · Attendance 15 · No repeat failure 15</div></div></div>
    <div class="card"><div class="card-header">History</div><div class="card-body">@include('partials.history', ['history' => $amc->history])</div></div>
</div></div>
@endsection
