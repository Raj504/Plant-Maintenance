@extends('layouts.app')
@section('title', 'AMC Contracts')
@section('page-title', 'Annual Maintenance Contracts')
@section('breadcrumb')<li class="breadcrumb-item active">AMC</li>@endsection
@section('page-actions')@can('pm.amc.manage')<a href="{{ route('amc.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Contract</a>@endcan@endsection
@section('content')
<form class="filter-bar row g-2" method="GET">
    <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="AMC no / name"></div>
    <div class="col-md-2"><select name="status" class="form-select" data-autosubmit><option value="">All statuses</option>@foreach(['active', 'expired', 'terminated'] as $s)<option value="{{ $s }}" @selected(request('status') == $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
    <div class="col-md-3"><select name="vendor_id" class="form-select" data-autosubmit><option value="">All vendors</option>@foreach($vendors as $v)<option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><div class="form-check mt-2"><input type="checkbox" name="expiring" value="1" class="form-check-input" id="exp" @checked(request('expiring')) data-autosubmit><label class="form-check-label" for="exp">Expiring in {{ $days }} days</label></div></div>
    <div class="col-md-2 d-flex gap-1"><button class="btn btn-primary"><i class="bi bi-search"></i></button><a href="{{ route('amc.index') }}" class="btn btn-light"><i class="bi bi-x"></i></a></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>AMC</th><th>Vendor</th><th>Assets</th><th>Period</th><th>Days Left</th><th class="text-end">Value</th><th>Frequency</th><th>Status</th></tr></thead>
    <tbody>@forelse($contracts as $c)
    @php $left = $c->daysToExpiry(); @endphp
    <tr class="{{ $c->status === 'active' && $left <= $days ? 'table-warning' : '' }}"><td><a href="{{ route('amc.show', $c) }}" class="fw-semibold">{{ $c->amc_no }}</a><div class="small text-muted">{{ $c->name }}</div></td><td>{{ $c->vendor->name }}</td><td class="small">{{ $c->assets->pluck('name')->join(', ') }}</td><td class="small">{{ show_date($c->start_date) }} – {{ show_date($c->end_date) }}</td><td class="{{ $left < 0 ? 'text-danger' : ($left <= $days ? 'text-warning fw-semibold' : '') }}">{{ $left < 0 ? 'Expired ' . abs($left) . 'd ago' : $left . ' days' }}</td><td class="text-end">{{ money($c->contract_value) }}</td><td class="small">{{ status_label($c->service_frequency) }}</td><td>{!! status_badge($c->status) !!}</td></tr>
    @empty<tr><td colspan="8">@include('partials.empty', ['message' => 'No AMC contracts.'])</td></tr>@endforelse</tbody></table></div>
    @if($contracts->hasPages())<div class="card-footer">{{ $contracts->links() }}</div>@endif
</div>
@endsection
