@extends('layouts.app')
@section('title', $plan->exists ? 'Edit PM Plan' : 'New PM Plan')
@section('page-title', $plan->exists ? 'Edit ' . $plan->plan_no : 'New Preventive Maintenance Plan')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('pm.plans.index') }}">PM Plans</a></li><li class="breadcrumb-item active">{{ $plan->exists ? 'Edit' : 'New' }}</li>@endsection

@section('content')
<form method="POST" action="{{ $plan->exists ? route('pm.plans.update', $plan) : route('pm.plans.store') }}">
    @csrf @if($plan->exists) @method('PUT') @endif
    <div class="row">
        <div class="col-lg-8">
            <div class="card"><div class="card-header">Plan</div><div class="card-body row g-3">
                <div class="col-md-6"><label class="form-label required">Asset</label><select name="asset_id" class="form-select" required><option value="">Select…</option>@foreach($assets as $a)<option value="{{ $a->id }}" @selected(old('asset_id', $plan->asset_id) == $a->id)>{{ $a->asset_code }} - {{ $a->name }} ({{ $a->current_running_hours + 0 }} h)</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label">Component (optional)</label><select name="component_id" class="form-select"><option value="">Whole machine</option>@foreach($assets as $a)@if($a->parent_id)<option value="{{ $a->id }}" @selected(old('component_id', $plan->component_id) == $a->id)>{{ $a->asset_code }} - {{ $a->name }}</option>@endif @endforeach</select></div>
                <div class="col-md-8"><label class="form-label required">Plan Name</label><input name="name" class="form-control" value="{{ old('name', $plan->name) }}" required placeholder="e.g. Compressor 500 hr service"></div>
                <div class="col-md-4"><label class="form-label">Maintenance Category</label><select name="category_id" class="form-select"><option value="">-</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $plan->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Team</label><select name="team_id" class="form-select"><option value="">-</option>@foreach($teams as $t)<option value="{{ $t->id }}" @selected(old('team_id', $plan->team_id) == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Technician</label><select name="assigned_to" class="form-select"><option value="">-</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('assigned_to', $plan->assigned_to) == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Checklist</label><select name="checklist_id" class="form-select"><option value="">None</option>@foreach($checklists as $c)<option value="{{ $c->id }}" @selected(old('checklist_id', $plan->checklist_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Estimated Duration (hrs)</label><input type="number" step="0.5" name="estimated_duration_hours" class="form-control" value="{{ old('estimated_duration_hours', $plan->estimated_duration_hours) }}"></div>
                <div class="col-md-4"><label class="form-label">Estimated Cost</label><input type="number" step="0.01" name="estimated_cost" class="form-control" value="{{ old('estimated_cost', $plan->estimated_cost ?? 0) }}"></div>
                <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="act" @checked(old('is_active', $plan->is_active ?? true))><label class="form-check-label" for="act">Active</label></div></div>
                <div class="col-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $plan->remarks) }}</textarea></div>
            </div></div>

            <div class="card"><div class="card-header">Scheduling Trigger</div><div class="card-body row g-3">
                <div class="col-md-12"><label class="form-label required">Trigger Type</label>
                    <select name="trigger_type" class="form-select" id="trigger">@foreach(\App\Models\PmPlan::TRIGGERS as $k => $v)<option value="{{ $k }}" @selected(old('trigger_type', $plan->trigger_type) == $k)>{{ $v }}</option>@endforeach</select>
                    <div class="form-text">"Both" = maintenance is due when EITHER the date OR the running hours is reached (whichever first). Only one work order is generated per cycle.</div></div>

                <div class="col-12" id="calBox"><div class="border rounded p-3 bg-light row g-3">
                    <div class="col-12"><strong class="small"><i class="bi bi-calendar3 me-1"></i>CALENDAR</strong></div>
                    <div class="col-md-3"><label class="form-label">Frequency</label><select name="frequency" class="form-select" id="frequency">@foreach(\App\Models\PmPlan::FREQUENCIES as $k => $days)<option value="{{ $k }}" @selected(old('frequency', $plan->frequency ?? 'monthly') == $k)>{{ ucfirst(str_replace('_', ' ', $k)) }}{{ $days ? " ($days days)" : '' }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Interval (days)</label><input type="number" name="interval_days" id="interval_days" class="form-control" value="{{ old('interval_days', $plan->interval_days) }}"></div>
                    <div class="col-md-3"><label class="form-label">Last Maintenance Date</label><input type="date" name="last_done_date" class="form-control" value="{{ old('last_done_date', $plan->last_done_date?->toDateString()) }}"></div>
                    <div class="col-md-3"><label class="form-label">Next Due Date</label><input type="date" name="next_due_date" class="form-control" value="{{ old('next_due_date', $plan->next_due_date?->toDateString()) }}"><div class="form-text">Blank = last done + interval</div></div>
                </div></div>

                <div class="col-12" id="hrsBox"><div class="border rounded p-3 bg-light row g-3">
                    <div class="col-12"><strong class="small"><i class="bi bi-speedometer me-1"></i>RUNNING HOURS</strong></div>
                    <div class="col-md-4"><label class="form-label">Interval (hours)</label><input type="number" step="0.5" name="interval_hours" class="form-control" value="{{ old('interval_hours', $plan->interval_hours) }}" placeholder="500"></div>
                    <div class="col-md-4"><label class="form-label">Last Recorded Hours at PM</label><input type="number" step="0.5" name="last_done_hours" class="form-control" value="{{ old('last_done_hours', $plan->last_done_hours) }}" placeholder="2000"></div>
                    <div class="col-md-4"><label class="form-label">Next Due Hours</label><input type="number" step="0.5" name="next_due_hours" class="form-control" value="{{ old('next_due_hours', $plan->next_due_hours) }}"><div class="form-text">Blank = last + interval (e.g. 2500)</div></div>
                </div></div>
            </div></div>
        </div>
        <div class="col-lg-4"><div class="card"><div class="card-body d-grid gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> {{ $plan->exists ? 'Update Plan' : 'Create Plan' }}</button><a href="{{ route('pm.plans.index') }}" class="btn btn-light">Cancel</a></div></div>
        <div class="alert alert-info small">After every completed PM the system recalculates the next due date (completed date + interval) and next due hours (hours at completion + interval). The scheduler command <code>pm:run-schedulers</code> generates work orders automatically when a plan becomes due.</div></div>
    </div>
</form>
@endsection
@push('scripts')
<script>
(function () {
    var t = document.getElementById('trigger'), cal = document.getElementById('calBox'), hrs = document.getElementById('hrsBox');
    var f = document.getElementById('frequency'), d = document.getElementById('interval_days');
    var days = @json(\App\Models\PmPlan::FREQUENCIES);
    function sync() { cal.style.display = (t.value === 'calendar' || t.value === 'both') ? '' : 'none'; hrs.style.display = (t.value === 'running_hours' || t.value === 'both') ? '' : 'none'; }
    function freq() { if (days[f.value]) { d.value = days[f.value]; d.readOnly = true; } else { d.readOnly = false; } }
    t.addEventListener('change', sync); f.addEventListener('change', freq); sync(); if (!d.value) freq();
})();
</script>
@endpush
