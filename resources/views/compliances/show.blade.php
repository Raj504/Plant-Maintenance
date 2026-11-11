@extends('layouts.app')
@section('title', $compliance->compliance_no)
@section('page-title'){{ $compliance->inspection_name }} {!! status_badge($compliance->status) !!}@endsection
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('compliances.index') }}">Compliance</a></li><li class="breadcrumb-item active">{{ $compliance->compliance_no }}</li>@endsection
@section('page-actions')@can('pm.compliance.manage')<a href="{{ route('compliances.edit', $compliance) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>@endcan@endsection
@section('content')
<div class="row g-3"><div class="col-xl-8">
    <div class="card"><div class="card-body"><dl class="detail-list">
        <dt>Number / Type</dt><dd>{{ $compliance->compliance_no }} · {{ $compliance->compliance_type }}</dd>
        <dt>Asset</dt><dd>@if($compliance->asset)<a href="{{ route('assets.show', $compliance->asset) }}">{{ $compliance->asset->asset_code }} – {{ $compliance->asset->name }}</a>@else - @endif</dd>
        <dt>Authority / Agency</dt><dd>{{ $compliance->authority ?? '-' }} / {{ $compliance->agency ?? '-' }}</dd>
        <dt>Department</dt><dd>{{ $compliance->department?->name ?? '-' }}</dd>
        <dt>Frequency</dt><dd>{{ $compliance->frequency_days ? $compliance->frequency_days . ' days' : '-' }}</dd>
        <dt>Last Inspection</dt><dd>{{ show_date($compliance->last_inspection_date) }} ({{ $compliance->result ? ucfirst($compliance->result) : '-' }})</dd>
        <dt>Next Due</dt><dd class="fw-semibold {{ $compliance->status === 'overdue' ? 'text-danger' : '' }}">{{ show_date($compliance->next_due_date) }}</dd>
        <dt>Current Certificate</dt><dd>{{ $compliance->certificate_no ?? '-' }}</dd>
    </dl>@if($compliance->remarks)<p class="small text-muted">{{ $compliance->remarks }}</p>@endif
    @if($compliance->attachments->isNotEmpty())@include('partials.attachments', ['attachments' => $compliance->attachments])@endif</div></div>

    @can('pm.compliance.manage')
    <div class="card border-primary"><div class="card-header bg-primary text-white">Record New Inspection</div><div class="card-body"><form method="POST" action="{{ route('compliances.records.store', $compliance) }}" enctype="multipart/form-data" class="row g-3">@csrf
        <div class="col-md-3"><label class="form-label required">Inspection Date</label><input type="date" name="inspection_date" class="form-control" value="{{ today()->toDateString() }}" required></div>
        <div class="col-md-3"><label class="form-label">Next Due Date</label><input type="date" name="next_due_date" class="form-control"><div class="form-text">Blank = + {{ $compliance->frequency_days ?? '?' }} days</div></div>
        <div class="col-md-3"><label class="form-label required">Result</label><select name="result" class="form-select"><option value="passed">Passed</option><option value="conditional">Conditional</option><option value="failed">Failed</option></select></div>
        <div class="col-md-3"><label class="form-label">Certificate No.</label><input name="certificate_no" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Agency</label><input name="agency" class="form-control" value="{{ $compliance->agency }}"></div>
        <div class="col-md-4"><label class="form-label">Remarks</label><input name="remarks" class="form-control"></div>
        <div class="col-md-4">@include('partials.upload-field', ['label' => 'Certificate / Report'])</div>
        <div class="col-12"><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Inspection</button></div>
    </form></div></div>
    @endcan

    <div class="card"><div class="card-header">Inspection History (old certificates retained)</div><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Date</th><th>Result</th><th>Certificate</th><th>Agency</th><th>Next Due</th><th>Files</th><th>By</th></tr></thead>
        <tbody>@forelse($compliance->records as $r)<tr><td>{{ show_date($r->inspection_date) }}</td><td>{!! status_badge($r->result) !!}</td><td>{{ $r->certificate_no }}</td><td class="small">{{ $r->agency }}</td><td>{{ show_date($r->next_due_date) }}</td><td>@foreach($r->attachments as $f)<a href="{{ route('attachments.download', $f) }}" class="btn btn-sm btn-light" title="{{ $f->file_name }}"><i class="bi bi-file-earmark-arrow-down"></i></a>@endforeach</td><td class="small">{{ $r->recorder?->name }}</td></tr>@empty<tr><td colspan="7" class="text-muted small text-center py-3">No inspections recorded</td></tr>@endforelse</tbody></table></div></div>
</div>
<div class="col-xl-4"><div class="card"><div class="card-header">History</div><div class="card-body">@include('partials.history', ['history' => $compliance->history])</div></div></div></div>
@endsection
