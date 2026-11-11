@extends('layouts.app')
@section('title', $calibration->instrument_code)
@section('page-title'){{ $calibration->instrument_code }} – {{ $calibration->instrument_name }} {!! status_badge($calibration->status, \App\Models\Calibration::STATUSES[$calibration->status] ?? null) !!}@endsection
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('calibrations.index') }}">Calibration</a></li><li class="breadcrumb-item active">{{ $calibration->instrument_code }}</li>@endsection
@section('page-actions')
@can('pm.compliance.manage')
@if($calibration->status !== 'under_calibration')<form method="POST" action="{{ route('calibrations.status', $calibration) }}">@csrf<input type="hidden" name="status" value="under_calibration"><button class="btn btn-outline-secondary btn-sm"><i class="bi bi-box-arrow-up me-1"></i> Send for Calibration</button></form>
@else<form method="POST" action="{{ route('calibrations.status', $calibration) }}">@csrf<input type="hidden" name="status" value="valid"><button class="btn btn-outline-secondary btn-sm">Mark Returned</button></form>@endif
<a href="{{ route('calibrations.edit', $calibration) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>
@endcan
@endsection
@section('content')
<div class="row g-3"><div class="col-xl-8">
    <div class="card"><div class="card-body"><dl class="detail-list">
        <dt>Asset / Department</dt><dd>{{ $calibration->asset?->name ?? '-' }} / {{ $calibration->department?->name ?? '-' }}</dd>
        <dt>Serial Number</dt><dd>{{ $calibration->serial_number ?? '-' }}</dd>
        <dt>Agency</dt><dd>{{ $calibration->agency ?? '-' }}</dd>
        <dt>Frequency</dt><dd>{{ $calibration->frequency_days }} days</dd>
        <dt>Last Calibration</dt><dd>{{ show_date($calibration->last_calibration_date) }} ({{ $calibration->result ? ucfirst($calibration->result) : '-' }})</dd>
        <dt>Next Due</dt><dd class="fw-semibold {{ $calibration->status === 'overdue' ? 'text-danger' : '' }}">{{ show_date($calibration->next_due_date) }}</dd>
        <dt>Certificate</dt><dd>{{ $calibration->certificate_no ?? '-' }}</dd>
    </dl>@if($calibration->remarks)<p class="small text-muted mb-0">{{ $calibration->remarks }}</p>@endif</div></div>

    @can('pm.compliance.manage')
    <div class="card border-primary"><div class="card-header bg-primary text-white">Record Calibration</div><div class="card-body"><form method="POST" action="{{ route('calibrations.records.store', $calibration) }}" enctype="multipart/form-data" class="row g-3">@csrf
        <div class="col-md-3"><label class="form-label required">Calibration Date</label><input type="date" name="calibration_date" class="form-control" value="{{ today()->toDateString() }}" required></div>
        <div class="col-md-3"><label class="form-label">Next Due</label><input type="date" name="next_due_date" class="form-control"><div class="form-text">Blank = + {{ $calibration->frequency_days }} days</div></div>
        <div class="col-md-3"><label class="form-label required">Result</label><select name="result" class="form-select"><option value="passed">Passed</option><option value="failed">Failed</option></select></div>
        <div class="col-md-3"><label class="form-label">Certificate No.</label><input name="certificate_no" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Agency</label><input name="agency" class="form-control" value="{{ $calibration->agency }}"></div>
        <div class="col-md-4"><label class="form-label">Remarks</label><input name="remarks" class="form-control"></div>
        <div class="col-md-4">@include('partials.upload-field', ['label' => 'Certificate'])</div>
        <div class="col-12"><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Calibration</button></div>
    </form></div></div>
    @endcan

    <div class="card"><div class="card-header">Calibration History</div><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Date</th><th>Result</th><th>Certificate</th><th>Agency</th><th>Next Due</th><th>Files</th><th>By</th></tr></thead>
        <tbody>@forelse($calibration->records as $r)<tr><td>{{ show_date($r->calibration_date) }}</td><td>{!! status_badge($r->result) !!}</td><td>{{ $r->certificate_no }}</td><td class="small">{{ $r->agency }}</td><td>{{ show_date($r->next_due_date) }}</td><td>@foreach($r->attachments as $f)<a href="{{ route('attachments.download', $f) }}" class="btn btn-sm btn-light"><i class="bi bi-file-earmark-arrow-down"></i></a>@endforeach</td><td class="small">{{ $r->recorder?->name }}</td></tr>@empty<tr><td colspan="7" class="text-muted small text-center py-3">No calibration records</td></tr>@endforelse</tbody></table></div></div>
</div>
<div class="col-xl-4"><div class="card"><div class="card-header">History</div><div class="card-body">@include('partials.history', ['history' => $calibration->history])</div></div></div></div>
@endsection
