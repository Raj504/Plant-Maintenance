@extends('layouts.app')
@section('title', 'Manual Work Order')
@section('page-title', 'Create Manual Work Order')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('work-orders.index') }}">Work Orders</a></li><li class="breadcrumb-item active">New</li>@endsection

@section('content')
<div class="alert alert-warning small"><i class="bi bi-info-circle me-1"></i> Manual work orders skip the request approval sequence. Use them for small planned jobs only. Breakdowns and cost-bearing work should go through a <a href="{{ route('requests.create') }}">maintenance request</a>.</div>
<form method="POST" action="{{ route('work-orders.store') }}">
    @csrf
    <div class="row">
        <div class="col-lg-8">
            <div class="card"><div class="card-body row g-3">
                <div class="col-md-6"><label class="form-label required">Asset</label><select name="asset_id" class="form-select" required><option value="">Select…</option>@foreach($assets as $a)<option value="{{ $a->id }}" @selected(old('asset_id') == $a->id)>{{ $a->asset_code }} - {{ $a->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Department</label><select name="department_id" class="form-select"><option value="">-</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label required">Work Type</label><select name="work_type" class="form-select">@foreach($types as $k => $v)<option value="{{ $k }}" @selected(old('work_type', 'corrective') == $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label required">Priority</label><select name="priority" class="form-select">@foreach(\App\Models\MaintenanceRequest::PRIORITIES as $k => $v)<option value="{{ $k }}" @selected(old('priority', 'medium') == $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Estimated Cost</label><input type="number" step="0.01" name="estimated_cost" class="form-control" value="{{ old('estimated_cost', 0) }}"></div>
                <div class="col-md-4"><label class="form-label">Team</label><select name="team_id" class="form-select"><option value="">-</option>@foreach($teams as $t)<option value="{{ $t->id }}" @selected(old('team_id') == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Technician</label><select name="assigned_to" class="form-select"><option value="">Assign later</option>@foreach($technicians as $t)<option value="{{ $t->id }}" @selected(old('assigned_to') == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label required">Planned Start</label><input type="date" name="planned_start" class="form-control" value="{{ old('planned_start', today()->toDateString()) }}" required></div>
                <div class="col-md-2"><label class="form-label required">Planned End</label><input type="date" name="planned_end" class="form-control" value="{{ old('planned_end', today()->addDays(3)->toDateString()) }}" required></div>
                <div class="col-12"><label class="form-label required">Work Description</label><textarea name="description" class="form-control" rows="4" required>{{ old('description') }}</textarea></div>
                <div class="col-md-8"><label class="form-label">Safety Requirements</label><textarea name="safety_requirements" class="form-control" rows="2">{{ old('safety_requirements') }}</textarea></div>
                <div class="col-md-4"><div class="form-check form-switch mt-4"><input type="checkbox" name="permit_required" value="1" class="form-check-input" id="ptw"><label class="form-check-label" for="ptw">Permit to Work required</label></div></div>
                <div class="col-12"><label class="form-label">Remarks</label><input name="remarks" class="form-control" value="{{ old('remarks') }}"></div>
            </div></div>
        </div>
        <div class="col-lg-4"><div class="card"><div class="card-body d-grid gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Create Work Order</button><a href="{{ route('work-orders.index') }}" class="btn btn-light">Cancel</a></div></div></div>
    </div>
</form>
@endsection
