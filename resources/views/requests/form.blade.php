@extends('layouts.app')
@php $r = $maintenanceRequest; @endphp
@section('title', $r->exists ? 'Edit Request' : 'Raise Request')
@section('page-title', $r->exists ? 'Edit Request ' . $r->request_no : 'Raise Maintenance Request')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('requests.index') }}">Requests</a></li><li class="breadcrumb-item active">{{ $r->exists ? 'Edit' : 'New' }}</li>@endsection

@section('content')
<form method="POST" action="{{ $r->exists ? route('requests.update', $r) : route('requests.store') }}" enctype="multipart/form-data">
    @csrf @if($r->exists) @method('PUT') @endif
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">Machine &amp; Problem</div>
                <div class="card-body row g-3">
                    <div class="col-md-6"><label class="form-label required">Machine / Asset</label>
                        <select name="asset_id" id="asset_id" class="form-select" required>
                            <option value="">Select machine…</option>
                            @foreach($assets as $a)<option value="{{ $a->id }}" data-dept="{{ $a->department_id }}" @selected(old('asset_id', $r->asset_id) == $a->id)>{{ $a->asset_code }} - {{ $a->name }}</option>@endforeach
                        </select></div>
                    <div class="col-md-6"><label class="form-label">Component (optional)</label>
                        <select name="component_id" id="component_id" class="form-select">
                            <option value="">Whole machine</option>
                            @foreach($assets as $a)@if($a->parent_id)<option value="{{ $a->id }}" data-parent="{{ $a->parent_id }}" @selected(old('component_id', $r->component_id) == $a->id)>{{ $a->asset_code }} - {{ $a->name }}</option>@endif @endforeach
                        </select>
                        <div class="form-text">Only components of the selected machine are listed.</div></div>
                    <div class="col-md-4"><label class="form-label required">Request Type</label>
                        <select name="request_type" class="form-select" required>@foreach(\App\Models\MaintenanceRequest::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('request_type', $r->request_type) == $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label required">Priority</label>
                        <select name="priority" class="form-select" required>@foreach(\App\Models\MaintenanceRequest::PRIORITIES as $k => $v)<option value="{{ $k }}" @selected(old('priority', $r->priority) == $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Problem Category</label>
                        <select name="problem_category_id" class="form-select"><option value="">-</option>@foreach($problems as $p)<option value="{{ $p->id }}" @selected(old('problem_category_id', $r->problem_category_id) == $p->id)>{{ $p->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Department</label>
                        <select name="department_id" id="department_id" class="form-select"><option value="">Same as machine</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $r->department_id) == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Shift</label>
                        <select name="shift_id" class="form-select"><option value="">-</option>@foreach($shifts as $s)<option value="{{ $s->id }}" @selected(old('shift_id', $r->shift_id) == $s->id)>{{ $s->name }} ({{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }})</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label required">Problem Description</label><textarea name="problem_description" class="form-control" rows="4" required placeholder="Describe what is wrong, what you observed, any noise / smell / leakage…">{{ old('problem_description', $r->problem_description) }}</textarea></div>
                    <div class="col-md-6">
                        <div class="form-check form-switch"><input type="checkbox" name="machine_stopped" value="1" class="form-check-input" id="ms" @checked(old('machine_stopped', $r->machine_stopped))><label for="ms" class="form-check-label">Machine Stopped?</label></div>
                        <div class="form-check form-switch"><input type="checkbox" name="production_affected" value="1" class="form-check-input" id="pa" @checked(old('production_affected', $r->production_affected))><label for="pa" class="form-check-label">Production Affected?</label></div>
                    </div>
                    <div class="col-md-6"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $r->remarks) }}</textarea></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">Photograph / Attachment</div>
                <div class="card-body">@include('partials.upload-field', ['label' => 'Photos of the problem, documents'])</div>
            </div>
            <div class="card"><div class="card-body d-grid gap-2">
                <button class="btn btn-primary"><i class="bi bi-send me-1"></i> {{ $r->exists ? 'Update Request' : 'Submit Request' }}</button>
                <a href="{{ $r->exists ? route('requests.show', $r) : route('requests.index') }}" class="btn btn-light">Cancel</a>
            </div></div>
            <div class="alert alert-info small"><i class="bi bi-info-circle me-1"></i> After submission the request goes to the <strong>Maintenance Inspector</strong>, then <strong>Technical Expert</strong>, then <strong>Budget Approver</strong>. A work order is generated automatically once approved.</div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
// Filter the component dropdown by the selected machine and default the department
(function () {
    var asset = document.getElementById('asset_id'), comp = document.getElementById('component_id'), dept = document.getElementById('department_id');
    function sync() {
        var selected = asset.options[asset.selectedIndex];
        Array.from(comp.options).forEach(function (o) { if (!o.value) return; o.hidden = o.dataset.parent !== asset.value; });
        if (comp.options[comp.selectedIndex] && comp.options[comp.selectedIndex].hidden) comp.value = '';
        if (selected && selected.dataset.dept && !dept.dataset.touched) dept.value = selected.dataset.dept;
    }
    asset.addEventListener('change', sync); dept.addEventListener('change', function () { dept.dataset.touched = 1; }); sync();
})();
</script>
@endpush
