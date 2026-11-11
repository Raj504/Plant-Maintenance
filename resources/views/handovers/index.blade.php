@extends('layouts.app')
@section('title', 'Shift Handover')
@section('page-title', 'Shift Handover')
@section('breadcrumb')<li class="breadcrumb-item active">Shift Handover</li>@endsection
@section('page-actions')@can('pm.handover.manage')<a href="{{ route('handovers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Handover</a>@endcan@endsection
@section('content')
<form class="filter-bar row g-2" method="GET">
    <div class="col-md-2"><select name="status" class="form-select" data-autosubmit><option value="">All</option><option value="pending" @selected(request('status') == 'pending')>Pending</option><option value="acknowledged" @selected(request('status') == 'acknowledged')>Acknowledged</option></select></div>
    <div class="col-md-3"><select name="department_id" class="form-select" data-autosubmit><option value="">All departments</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
    <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
    <div class="col-md-1"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Handover</th><th>Date</th><th>From → To</th><th>Department</th><th>Handed Over By</th><th>Received By</th><th>Status</th></tr></thead>
    <tbody>@forelse($handovers as $h)
    <tr><td><a href="{{ route('handovers.show', $h) }}" class="fw-semibold">{{ $h->handover_no }}</a></td><td>{{ show_date($h->handover_date) }}</td><td>{{ $h->fromShift->name }} → {{ $h->toShift->name }}</td><td>{{ $h->department?->name }}</td><td>{{ $h->handedOverBy->name }}</td><td>{{ $h->receivedBy?->name ?? '-' }}</td><td>{!! status_badge($h->status) !!}</td></tr>
    @empty<tr><td colspan="7">@include('partials.empty', ['message' => 'No handovers.'])</td></tr>@endforelse</tbody></table></div>
    @if($handovers->hasPages())<div class="card-footer">{{ $handovers->links() }}</div>@endif
</div>
@endsection
