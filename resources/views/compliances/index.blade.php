@extends('layouts.app')
@section('title', 'Statutory Compliance')
@section('page-title', 'Statutory Compliance Register')
@section('breadcrumb')<li class="breadcrumb-item active">Compliance</li>@endsection
@section('page-actions')@can('pm.compliance.manage')<a href="{{ route('compliances.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Compliance Item</a>@endcan@endsection
@section('content')
<form class="filter-bar row g-2" method="GET">
    <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Name / number / certificate"></div>
    <div class="col-md-2"><select name="status" class="form-select" data-autosubmit><option value="">All statuses</option>@foreach(['valid', 'due_soon', 'overdue', 'failed'] as $s)<option value="{{ $s }}" @selected(request('status') == $s)>{{ status_label($s) }}</option>@endforeach</select></div>
    <div class="col-md-3"><select name="compliance_type" class="form-select" data-autosubmit><option value="">All types</option>@foreach($types as $t)<option value="{{ $t->name }}" @selected(request('compliance_type') == $t->name)>{{ $t->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="department_id" class="form-select" data-autosubmit><option value="">All departments</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
    <div class="col-md-2 d-flex gap-1"><button class="btn btn-primary"><i class="bi bi-search"></i></button><a href="{{ route('compliances.index') }}" class="btn btn-light"><i class="bi bi-x"></i></a></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>No.</th><th>Inspection</th><th>Type</th><th>Asset</th><th>Authority / Agency</th><th>Last</th><th>Next Due</th><th>Certificate</th><th>Status</th></tr></thead>
    <tbody>@forelse($compliances as $c)
    <tr class="{{ $c->status === 'overdue' ? 'table-danger' : ($c->status === 'due_soon' ? 'table-warning' : '') }}"><td><a href="{{ route('compliances.show', $c) }}" class="fw-semibold">{{ $c->compliance_no }}</a></td><td>{{ $c->inspection_name }}</td><td class="small">{{ $c->compliance_type }}</td><td class="small">{{ $c->asset?->name }}</td><td class="small">{{ $c->authority }}<br><span class="text-muted">{{ $c->agency }}</span></td><td class="small">{{ show_date($c->last_inspection_date) }}</td><td class="fw-semibold">{{ show_date($c->next_due_date) }}</td><td class="small">{{ $c->certificate_no }}</td><td>{!! status_badge($c->status) !!}</td></tr>
    @empty<tr><td colspan="9">@include('partials.empty', ['message' => 'No compliance records.'])</td></tr>@endforelse</tbody></table></div>
    @if($compliances->hasPages())<div class="card-footer">{{ $compliances->links() }}</div>@endif
</div>
@endsection
