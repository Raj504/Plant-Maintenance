@extends('layouts.app')
@section('title', 'Vendors')
@section('page-title', 'Maintenance Vendors')
@section('breadcrumb')<li class="breadcrumb-item active">Vendors</li>@endsection
@section('page-actions')@can('pm.vendors.manage')<a href="{{ route('vendors.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Vendor</a>@endcan@endsection
@section('content')
<form class="filter-bar row g-2" method="GET"><div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Name / code"></div><div class="col-md-2"><button class="btn btn-primary"><i class="bi bi-search"></i></button></div></form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Code</th><th>Vendor</th><th>Contact</th><th>Services</th><th class="text-end">Contracts</th><th class="text-end">Service Calls</th><th>SLA Rating</th><th>Status</th></tr></thead>
    <tbody>@forelse($vendors as $v)@php $r = $v->slaRating(); @endphp
    <tr><td class="fw-semibold">{{ $v->code }}</td><td><a href="{{ route('vendors.show', $v) }}">{{ $v->name }}</a></td><td class="small">{{ $v->contact_person }}<br>{{ $v->phone }}</td><td class="small">{{ $v->services }}</td><td class="text-end">{{ $v->contracts_count }}</td><td class="text-end">{{ $v->service_records_count }}</td><td>@if($r !== null)<span class="badge text-bg-{{ $r >= 80 ? 'success' : ($r >= 60 ? 'warning' : 'danger') }}">{{ $r }}%</span>@else<span class="text-muted small">n/a</span>@endif</td><td>{!! status_badge($v->is_active ? 'active' : 'inactive') !!}</td></tr>
    @empty<tr><td colspan="8">@include('partials.empty', ['message' => 'No vendors.'])</td></tr>@endforelse</tbody></table></div>
    @if($vendors->hasPages())<div class="card-footer">{{ $vendors->links() }}</div>@endif
</div>
@endsection
