@extends('layouts.app')
@section('title', 'Complete PM')
@section('page-title', 'Complete Preventive Maintenance')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('pm.schedules.index') }}">PM Schedules</a></li><li class="breadcrumb-item active">Complete</li>@endsection

@section('content')
@php $plan = $schedule->plan; @endphp
<form method="POST" action="{{ route('pm.schedules.complete.store', $schedule) }}" enctype="multipart/form-data">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card"><div class="card-header">{{ $plan->name }} — {{ $plan->asset->asset_code }} {{ $plan->asset->name }}</div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Due {{ show_date($schedule->due_date) }} {{ $schedule->due_hours ? '/ ' . ($schedule->due_hours + 0) . ' hrs' : '' }} · Trigger: {{ status_label($schedule->trigger_reason) }} @if($schedule->workOrder)· Work order <a href="{{ route('work-orders.show', $schedule->workOrder) }}">{{ $schedule->workOrder->wo_no }}</a>@endif</p>
                    <h6>Checklist</h6>
                    @forelse($plan->checklist?->items ?? [] as $i => $item)
                    <div class="checklist-row">
                        <div><input type="hidden" name="items[{{ $i }}][item_text]" value="{{ $item->item_text }}">{{ $loop->iteration }}. {{ $item->item_text }}</div>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="items[{{ $i }}][result]" id="ok{{ $i }}" value="ok" checked><label class="btn btn-outline-success" for="ok{{ $i }}">OK</label>
                            <input type="radio" class="btn-check" name="items[{{ $i }}][result]" id="nok{{ $i }}" value="not_ok"><label class="btn btn-outline-danger" for="nok{{ $i }}">Not OK</label>
                            <input type="radio" class="btn-check" name="items[{{ $i }}][result]" id="na{{ $i }}" value="na"><label class="btn btn-outline-secondary" for="na{{ $i }}">N/A</label>
                        </div>
                        <div><input name="items[{{ $i }}][remarks]" class="form-control form-control-sm" placeholder="Remarks"></div>
                    </div>
                    @empty<p class="text-muted small">No checklist attached to this plan.</p>@endforelse
                </div>
            </div>
            <div class="card"><div class="card-header">Completion Details</div><div class="card-body row g-3">
                <div class="col-md-3"><label class="form-label required">Completed Date</label><input type="date" name="completed_date" class="form-control" value="{{ today()->toDateString() }}" required></div>
                <div class="col-md-3"><label class="form-label">Running Hours Now</label><input type="number" step="0.5" name="running_hours_at_completion" class="form-control" value="{{ $plan->asset->current_running_hours }}"></div>
                <div class="col-md-3"><label class="form-label">Duration (hrs)</label><input type="number" step="0.25" name="duration_hours" class="form-control" value="{{ $plan->estimated_duration_hours }}"></div>
                <div class="col-md-3"><label class="form-label">Actual Cost</label><input type="number" step="0.01" name="actual_cost" class="form-control" value="{{ $plan->estimated_cost }}"></div>
                <div class="col-md-6"><label class="form-label">Observations</label><textarea name="observations" class="form-control" rows="3"></textarea></div>
                <div class="col-md-6"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="3"></textarea></div>
                <div class="col-12">@include('partials.upload-field', ['label' => 'Photographs / Evidence'])</div>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card"><div class="card-body d-grid gap-2"><button class="btn btn-success"><i class="bi bi-check2-all me-1"></i> Save PM Completion</button><a href="{{ route('pm.plans.show', $plan) }}" class="btn btn-light">Cancel</a></div></div>
            <div class="alert alert-info small">Saving will: store the checklist results permanently, mark the schedule completed, update the asset running hours, calculate the next due date / hours, and send the linked work order for verification.</div>
        </div>
    </div>
</form>
@endsection
