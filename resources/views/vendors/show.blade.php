@extends('layouts.app')
@section('title', $vendor->name)
@section('page-title'){{ $vendor->name }} {!! status_badge($vendor->is_active ? 'active' : 'inactive') !!}@endsection
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('vendors.index') }}">Vendors</a></li><li class="breadcrumb-item active">{{ $vendor->code }}</li>@endsection
@section('page-actions')@can('pm.vendors.manage')<a href="{{ route('vendors.edit', $vendor) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>@endcan@endsection
@section('content')
@php $services = $vendor->serviceRecords; $n = max(1, $services->count()); $rating = $vendor->slaRating(); @endphp
<div class="row g-3"><div class="col-lg-8">
    <div class="card"><div class="card-body"><dl class="detail-list"><dt>Code</dt><dd>{{ $vendor->code }}</dd><dt>Contact</dt><dd>{{ $vendor->contact_person }} · {{ $vendor->phone }} · {{ $vendor->email }}</dd><dt>GST</dt><dd>{{ $vendor->gst_number ?? '-' }}</dd><dt>Services</dt><dd>{{ $vendor->services ?? '-' }}</dd><dt>Address</dt><dd>{{ $vendor->address ?? '-' }}</dd></dl></div></div>
    <div class="card"><div class="card-header">Contracts</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>AMC</th><th>Period</th><th>Assets</th><th class="text-end">Value</th><th>Status</th></tr></thead><tbody>@forelse($vendor->contracts as $c)<tr><td><a href="{{ route('amc.show', $c) }}">{{ $c->amc_no }}</a> {{ $c->name }}</td><td class="small">{{ show_date($c->start_date) }} – {{ show_date($c->end_date) }}</td><td class="small">{{ $c->assets->pluck('asset_code')->join(', ') }}</td><td class="text-end">{{ money($c->contract_value) }}</td><td>{!! status_badge($c->status) !!}</td></tr>@empty<tr><td colspan="5" class="text-muted small text-center py-3">No contracts</td></tr>@endforelse</tbody></table></div></div>
    <div class="card"><div class="card-header">Service History</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Date</th><th>Reference</th><th>Response</th><th>Resolution</th><th>Quality</th><th class="text-end">Cost</th><th>Feedback</th></tr></thead><tbody>@forelse($services as $s)<tr><td>{{ show_date($s->service_date) }}</td><td class="small">{{ $s->workOrder?->wo_no ?? $s->contract?->amc_no }}</td><td>{{ $s->response_hours }}h {!! $s->response_within_sla ? '<i class="bi bi-check text-success"></i>' : '<i class="bi bi-x text-danger"></i>' !!}</td><td>{{ $s->resolution_hours }}h {!! $s->resolution_within_sla ? '<i class="bi bi-check text-success"></i>' : '<i class="bi bi-x text-danger"></i>' !!}</td><td>{{ str_repeat('★', $s->work_quality) }}</td><td class="text-end">{{ money($s->cost) }}</td><td class="small">{{ $s->feedback }}</td></tr>@empty<tr><td colspan="7" class="text-muted small text-center py-3">No service history</td></tr>@endforelse</tbody></table></div></div>
</div>
<div class="col-lg-4">
    <div class="card"><div class="card-header">SLA Performance</div><div class="card-body">
        <div class="text-center mb-3"><div class="display-5 fw-bold {{ $rating === null ? 'text-muted' : ($rating >= 80 ? 'text-success' : ($rating >= 60 ? 'text-warning' : 'text-danger')) }}">{{ $rating === null ? '-' : $rating . '%' }}</div><div class="small text-muted">Overall rating from {{ $services->count() }} service(s)</div></div>
        @foreach([['Response time compliance', $services->where('response_within_sla', true)->count() / $n * 100], ['Resolution time compliance', $services->where('resolution_within_sla', true)->count() / $n * 100], ['Work quality', ($services->avg('work_quality') ?? 0) / 5 * 100], ['Service attendance', $services->where('attended', true)->count() / $n * 100], ['No repeat failure', $services->where('repeat_failure', false)->count() / $n * 100]] as [$label, $pct])
        <div class="d-flex justify-content-between small"><span>{{ $label }}</span><span>{{ round($pct) }}%</span></div><div class="progress mb-2" style="height:6px"><div class="progress-bar bg-{{ $pct >= 80 ? 'success' : ($pct >= 60 ? 'warning' : 'danger') }}" style="width:{{ $pct }}%"></div></div>
        @endforeach
        <div class="small text-muted mt-2">Cost performance: total {{ money($services->sum('cost')) }}</div>
    </div></div>
</div></div>
@endsection
