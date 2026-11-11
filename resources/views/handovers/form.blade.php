@extends('layouts.app')
@section('title', 'New Handover')
@section('page-title', 'New Shift Handover')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('handovers.index') }}">Shift Handover</a></li><li class="breadcrumb-item active">New</li>@endsection
@section('content')
<form method="POST" action="{{ route('handovers.store') }}"><div class="row"><div class="col-lg-8">
    <div class="card"><div class="card-body row g-3">@csrf
        <div class="col-md-3"><label class="form-label required">Date</label><input type="date" name="handover_date" class="form-control" value="{{ old('handover_date', $handover->handover_date->toDateString()) }}" required></div>
        <div class="col-md-3"><label class="form-label required">From Shift</label><select name="from_shift_id" class="form-select" required>@foreach($shifts as $s)<option value="{{ $s->id }}" @selected(old('from_shift_id') == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label required">To Shift</label><select name="to_shift_id" class="form-select" required>@foreach($shifts as $s)<option value="{{ $s->id }}" @selected(old('to_shift_id', $shifts->skip(1)->first()?->id) == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">-</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', auth()->user()->department_id) == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Handed Over By</label><input class="form-control" value="{{ auth()->user()->name }}" disabled></div>
        <div class="col-md-6"><label class="form-label">Receiving Person</label><select name="received_by" class="form-select"><option value="">Anyone on next shift</option>@foreach($users as $u)@if($u->id !== auth()->id())<option value="{{ $u->id }}" @selected(old('received_by') == $u->id)>{{ $u->name }}</option>@endif @endforeach</select></div>
        @foreach(['pending_work_orders' => 'Pending Work Orders', 'breakdown_status' => 'Breakdown Status', 'machines_under_observation' => 'Machines Under Observation', 'temporary_repairs' => 'Temporary Repairs', 'safety_issues' => 'Safety Issues', 'spare_shortage' => 'Spare Parts Shortage', 'instructions' => 'Important Instructions', 'remarks' => 'Remarks'] as $field => $label)
        <div class="col-md-6"><label class="form-label">{{ $label }}</label><textarea name="{{ $field }}" class="form-control" rows="3">{{ old($field, $handover->$field) }}</textarea></div>
        @endforeach
    </div></div>
</div>
<div class="col-lg-4"><div class="card"><div class="card-body d-grid gap-2"><button class="btn btn-primary"><i class="bi bi-send me-1"></i> Submit Handover</button><a href="{{ route('handovers.index') }}" class="btn btn-light">Cancel</a></div></div>
<div class="alert alert-info small">Pending work orders, breakdown machines and spare shortages are pre-filled from live data. Edit as needed. The receiving person must acknowledge the handover.</div></div></div></form>
@endsection
