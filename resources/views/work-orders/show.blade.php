@extends('layouts.app')
@section('title', $wo->wo_no)
@section('page-title'){{ $wo->wo_no }} {!! status_badge($wo->status) !!} {!! status_badge($wo->priority) !!} @if($wo->isOverdue())<span class="badge text-bg-danger"><i class="bi bi-alarm"></i> Overdue</span>@endif @if($wo->source === 'emergency')<span class="badge text-bg-danger">EMERGENCY</span>@endif @endsection
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('work-orders.index') }}">Work Orders</a></li><li class="breadcrumb-item active">{{ $wo->wo_no }}</li>@endsection
@section('page-actions')
<a href="{{ route('work-orders.print', $wo) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i> Print</a>
@can('pm.safety.manage')@if($wo->isOpen())<a href="{{ route('permits.create', ['work_order_id' => $wo->id]) }}" class="btn btn-outline-warning btn-sm"><i class="bi bi-shield-exclamation me-1"></i> Permit to Work</a>@endif @endcan
@can('pm.stock.issue')@if(in_array($wo->status, ['assigned', 'in_progress', 'on_hold']))<a href="{{ route('stock.issue', ['work_order_id' => $wo->id]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-arrow-right me-1"></i> Issue Spares</a>@endif @endcan
@can('pm.workorders.cancel')@if(in_array($wo->status, ['open', 'assigned', 'on_hold']))<button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="bi bi-x-circle me-1"></i> Cancel</button>@endif @endcan
@endsection

@section('content')
@php
$flow = ['open', 'assigned', 'in_progress', 'completed', 'verification_pending', 'closed'];
$idx = array_search($wo->status === 'completed' ? 'verification_pending' : $wo->status, $flow);
$techCan = auth()->user()->hasPermission('pm.workorders.execute') && (!$wo->assigned_to || $wo->assigned_to === auth()->id() || auth()->user()->hasAnyPermission(['pm.workorders.assign', 'pm.workorders.verify']) || $wo->team?->members->contains(auth()->user()));
@endphp
<div class="card"><div class="card-body py-3"><div class="stepper">
    @foreach(['Open' => 'bi-folder2-open', 'Assigned' => 'bi-person-check', 'In Progress' => 'bi-tools', 'Completed' => 'bi-check2', 'Verification' => 'bi-patch-check', 'Closed' => 'bi-lock'] as $label => $icon)
    @php $i = $loop->index; @endphp
    <div class="step {{ $wo->status === 'cancelled' ? '' : ($idx !== false && $i < $idx ? 'done' : ($i == $idx ? 'current' : '')) }}"><i class="bi {{ $icon }}"></i>{{ $label }}</div>
    @endforeach
    @if($wo->status === 'on_hold')<div class="step current bg-warning"><i class="bi bi-pause-circle"></i>On Hold</div>@endif
    @if($wo->status === 'cancelled')<div class="step rejected"><i class="bi bi-x-octagon"></i>Cancelled</div>@endif
</div></div></div>

@if($wo->permit_required && !$wo->permits->where('status', 'active')->count() && $wo->isOpen() && !in_array($wo->status, ['closed', 'verification_pending']))
<div class="alert alert-warning"><i class="bi bi-shield-exclamation me-1"></i> <strong>Permit to Work required.</strong> Work cannot start until a permit is approved and active. @foreach($wo->permits as $p)<a href="{{ route('permits.show', $p) }}">{{ $p->permit_no }}</a> {!! status_badge($p->status) !!} @endforeach @can('pm.safety.manage')<a href="{{ route('permits.create', ['work_order_id' => $wo->id]) }}">Create permit</a>@endcan</div>
@endif
@if($wo->status === 'on_hold')<div class="alert alert-warning"><i class="bi bi-pause-circle me-1"></i> <strong>On hold:</strong> {{ $wo->hold_reason }}</div>@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">Work Order Details</div>
            <div class="card-body">
                <dl class="detail-list">
                    <dt>Asset</dt><dd><a href="{{ route('assets.show', $wo->asset) }}">{{ $wo->asset->asset_code }} – {{ $wo->asset->name }}</a></dd>
                    <dt>Request</dt><dd>{!! $wo->request ? '<a href="' . route('requests.show', $wo->request) . '">' . $wo->request->request_no . '</a> · ' . e($wo->request->requester->name) : status_label($wo->source) !!}</dd>
                    <dt>Work Type</dt><dd>{{ status_label($wo->work_type) }} <span class="text-muted">({{ status_label($wo->source) }})</span></dd>
                    <dt>Department</dt><dd>{{ $wo->department?->name ?? '-' }}</dd>
                    <dt>Team / Technician</dt><dd>{{ $wo->team?->name ?? '-' }} / <strong>{{ $wo->technician?->name ?? 'Unassigned' }}</strong></dd>
                    <dt>Planned</dt><dd>{{ show_date($wo->planned_start) }} → {{ show_date($wo->planned_end) }}</dd>
                    <dt>Actual</dt><dd>{{ show_datetime($wo->actual_start) }} → {{ show_datetime($wo->actual_end) }}</dd>
                    <dt>Cost</dt><dd>Estimated {{ money($wo->estimated_cost) }} · Actual <strong>{{ money($wo->actual_cost) }}</strong></dd>
                    <dt>Labour / Downtime</dt><dd>{{ $wo->labour_hours + 0 }} hrs / {{ $wo->downtime_hours + 0 }} hrs</dd>
                    <dt>Safety</dt><dd>{!! $wo->permit_required ? '<span class="badge text-bg-warning">PTW required</span>' : '<span class="badge text-bg-light border">No permit needed</span>' !!} {{ $wo->safety_requirements }}</dd>
                    @if($wo->vendor)<dt>Vendor</dt><dd>{{ $wo->vendor->name }} {{ $wo->vendor_details }}</dd>@endif
                </dl>
                <div class="border rounded p-3 bg-light"><strong class="small text-muted d-block mb-1">WORK DESCRIPTION</strong>{{ $wo->description }}</div>
                @if($wo->request?->latestAssessment?->spares->isNotEmpty())
                <div class="mt-3"><strong class="small text-muted">REQUIRED SPARES (from assessment)</strong>
                    <ul class="mb-0 small">@foreach($wo->request->latestAssessment->spares as $s)<li>{{ $s->spare ? $s->spare->label : $s->spare_name }} × {{ $s->quantity + 0 }}</li>@endforeach</ul></div>
                @endif
                @if($wo->actual_problem_found)
                <hr>
                <dl class="detail-list">
                    <dt>Actual Problem Found</dt><dd>{{ $wo->actual_problem_found }}</dd>
                    <dt>Work Performed</dt><dd>{{ $wo->work_performed }}</dd>
                    <dt>Repair Remarks</dt><dd>{{ $wo->repair_remarks ?? '-' }}</dd>
                </dl>
                @endif
                @if($wo->status === 'closed')
                <div class="alert alert-success mb-0 small"><i class="bi bi-patch-check me-1"></i> Verified by <strong>{{ $wo->verifier?->name }}</strong> on {{ show_datetime($wo->verified_at) }}. Final machine status: {!! status_badge($wo->final_machine_status) !!} {{ $wo->final_remarks }}</div>
                @endif
            </div>
        </div>

        {{-- ACTION: Assign --}}
        @can('pm.workorders.assign')
        @if(in_array($wo->status, ['open', 'assigned', 'on_hold']))
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">{{ $wo->status === 'open' ? 'Assign Work Order' : 'Re-assign / Re-plan' }}</div>
            <div class="card-body"><form method="POST" action="{{ route('work-orders.assign', $wo) }}" class="row g-3">@csrf
                <div class="col-md-3"><label class="form-label">Team</label><select name="team_id" class="form-select"><option value="">-</option>@foreach($teams as $t)<option value="{{ $t->id }}" @selected($wo->team_id == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label required">Technician</label><select name="assigned_to" class="form-select" required><option value="">Select…</option>@foreach($technicians as $t)<option value="{{ $t->id }}" @selected($wo->assigned_to == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label required">Start</label><input type="date" name="planned_start" class="form-control" value="{{ $wo->planned_start?->toDateString() }}" required></div>
                <div class="col-md-2"><label class="form-label required">End</label><input type="date" name="planned_end" class="form-control" value="{{ $wo->planned_end?->toDateString() }}" required></div>
                <div class="col-md-2 d-flex align-items-end"><div class="form-check form-switch"><input type="checkbox" name="permit_required" value="1" class="form-check-input" id="pr" @checked($wo->permit_required)><label class="form-check-label" for="pr">PTW</label></div></div>
                <div class="col-md-9"><input name="safety_requirements" class="form-control" placeholder="Safety requirements / PPE" value="{{ $wo->safety_requirements }}"></div>
                <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-person-check me-1"></i> Assign</button></div>
            </form></div>
        </div>
        @endif
        @endcan

        {{-- ACTION: Technician execution --}}
        @if($techCan && in_array($wo->status, ['assigned', 'open', 'in_progress', 'on_hold']))
        <div class="card border-success">
            <div class="card-header bg-success text-white">Execution (Technician)</div>
            <div class="card-body">
                @if(in_array($wo->status, ['assigned', 'open', 'on_hold']))
                <form method="POST" action="{{ route('work-orders.start', $wo) }}" class="d-inline">@csrf<button class="btn btn-success"><i class="bi bi-play-fill me-1"></i> Start Work</button></form>
                @endif
                @if(in_array($wo->status, ['in_progress', 'assigned']))
                <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#holdModal"><i class="bi bi-pause me-1"></i> Put On Hold</button>
                @endif

                @if(in_array($wo->status, ['in_progress', 'on_hold']))
                <hr>
                <h6>Record Work &amp; Complete</h6>
                <form method="POST" action="{{ route('work-orders.complete', $wo) }}" enctype="multipart/form-data" class="row g-3">@csrf
                    <div class="col-md-6"><label class="form-label required">Actual Problem Found</label><textarea name="actual_problem_found" class="form-control" rows="3" required>{{ old('actual_problem_found', $wo->actual_problem_found) }}</textarea></div>
                    <div class="col-md-6"><label class="form-label required">Work Performed</label><textarea name="work_performed" class="form-control" rows="3" required>{{ old('work_performed', $wo->work_performed) }}</textarea></div>
                    <div class="col-md-3"><label class="form-label">Completion Date &amp; Time</label><input type="datetime-local" name="actual_end" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}"></div>
                    <div class="col-md-3"><label class="form-label">Downtime (hrs)</label><input type="number" step="0.25" name="downtime_hours" class="form-control" value="{{ old('downtime_hours', $wo->downtime_hours) }}"><div class="form-text">Blank = auto from start/end</div></div>
                    <div class="col-md-3"><label class="form-label">External Vendor</label><select name="vendor_id" class="form-select"><option value="">None</option>@foreach($vendors as $v)<option value="{{ $v->id }}" @selected($wo->vendor_id == $v->id)>{{ $v->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Vendor / Other Cost</label><input type="number" step="0.01" name="other_cost" class="form-control" value="0"><div class="form-text">Parts &amp; labour are added automatically</div></div>
                    <div class="col-md-6"><label class="form-label">Photographs Before Repair</label><input type="file" name="before_photos[]" class="form-control" multiple accept="image/*,.pdf"></div>
                    <div class="col-md-6"><label class="form-label">Photographs After Repair</label><input type="file" name="after_photos[]" class="form-control" multiple accept="image/*,.pdf"></div>
                    <div class="col-md-9"><label class="form-label">Repair Remarks</label><input name="repair_remarks" class="form-control" value="{{ old('repair_remarks', $wo->repair_remarks) }}"></div>
                    <div class="col-md-3 d-flex align-items-end"><button class="btn btn-success w-100"><i class="bi bi-check2-all me-1"></i> Complete &amp; Send for Verification</button></div>
                </form>
                @endif
            </div>
        </div>
        @endif

        {{-- ACTION: Verification --}}
        @can('pm.workorders.verify')
        @if(in_array($wo->status, ['verification_pending', 'completed']))
        <div class="card border-warning">
            <div class="card-header bg-warning">Testing &amp; Verification (Supervisor)</div>
            <div class="card-body"><form method="POST" action="{{ route('work-orders.verify', $wo) }}" class="row g-3">@csrf
                <div class="col-md-6">
                    @foreach(['machine_operation_ok' => 'Machine operation checked OK', 'safety_ok' => 'Safety condition OK (guards, isolation removed, housekeeping)', 'repair_quality_ok' => 'Repair quality acceptable', 'production_ready' => 'Ready for production'] as $k => $label)
                    <div class="form-check"><input type="checkbox" name="{{ $k }}" value="1" class="form-check-input" id="{{ $k }}" @checked(old($k))><label class="form-check-label" for="{{ $k }}">{{ $label }}</label></div>
                    @endforeach
                </div>
                <div class="col-md-6">
                    <label class="form-label">Test Results</label><textarea name="test_results" class="form-control" rows="2">{{ old('test_results') }}</textarea>
                    <label class="form-label mt-2">Remaining Observations</label><textarea name="observations" class="form-control" rows="2">{{ old('observations') }}</textarea>
                </div>
                <div class="col-md-3"><label class="form-label">Final Machine Status</label><select name="final_machine_status" class="form-select"><option value="active">Active</option><option value="idle">Idle</option><option value="under_maintenance">Under Maintenance</option><option value="breakdown">Breakdown</option></select></div>
                <div class="col-md-3"><label class="form-label">Actual Repair Cost</label><input type="number" step="0.01" name="actual_cost" class="form-control" value="{{ $wo->actual_cost }}"></div>
                <div class="col-md-6"><label class="form-label required">Final Remarks</label><input name="remarks" class="form-control" required value="{{ old('remarks') }}"></div>
                <div class="col-12 d-flex gap-2">
                    <button name="decision" value="approved" class="btn btn-success"><i class="bi bi-patch-check me-1"></i> Approve &amp; Close Work Order</button>
                    <button name="decision" value="sent_back" class="btn btn-outline-danger"><i class="bi bi-arrow-counterclockwise me-1"></i> Send Back for Additional Work</button>
                </div>
            </form></div>
        </div>
        @endif
        @endcan

        {{-- Tabs --}}
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#w-parts">Parts <span class="badge text-bg-light">{{ $wo->stockTransactions->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#w-labour">Labour <span class="badge text-bg-light">{{ $wo->labour->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#w-tasks">Tasks <span class="badge text-bg-light">{{ $wo->tasks->where('is_done', true)->count() }}/{{ $wo->tasks->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#w-photos">Photos &amp; Files <span class="badge text-bg-light">{{ $wo->attachments->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#w-permits">Permits <span class="badge text-bg-light">{{ $wo->permits->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#w-verif">Verifications <span class="badge text-bg-light">{{ $wo->verifications->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#w-vendor">Vendor</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#w-hist">History</button></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="w-parts">
                <div class="card">
                    <div class="table-responsive"><table class="table mb-0">
                        <thead><tr><th>Txn</th><th>Date</th><th>Spare</th><th>Type</th><th class="text-end">Qty</th><th class="text-end">Unit Cost</th><th class="text-end">Value</th><th>By</th></tr></thead>
                        <tbody>@forelse($wo->stockTransactions as $t)
                        <tr><td class="small">{{ $t->txn_no }}</td><td class="small">{{ show_date($t->transaction_date) }}</td><td>{{ $t->spare->label }}</td><td>{!! status_badge($t->type === 'issue' ? 'danger' : 'success', \App\Models\StockTransaction::TYPES[$t->type]) !!}</td><td class="text-end">{{ $t->quantity + 0 }}</td><td class="text-end">{{ money($t->unit_cost) }}</td><td class="text-end">{{ money($t->quantity * $t->unit_cost) }}</td><td class="small">{{ $t->user->name }}</td></tr>
                        @empty<tr><td colspan="8" class="text-muted small text-center py-3">No parts issued. @can('pm.stock.issue')<a href="{{ route('stock.issue', ['work_order_id' => $wo->id]) }}">Issue spares</a>@endcan</td></tr>@endforelse</tbody>
                        @if($wo->stockTransactions->isNotEmpty())<tfoot><tr><th colspan="6" class="text-end">Net parts cost</th><th class="text-end">{{ money($wo->partsCost()) }}</th><th></th></tr></tfoot>@endif
                    </table></div>
                    @if($wo->purchaseRequests->isNotEmpty())<div class="card-footer small">Purchase requisitions: @foreach($wo->purchaseRequests as $pr)<a href="{{ route('purchase-requests.show', $pr) }}">{{ $pr->pr_no }}</a> ({{ $pr->spare->name }}) {!! status_badge($pr->status) !!} @endforeach</div>@endif
                </div>
            </div>
            <div class="tab-pane fade" id="w-labour">
                <div class="card">
                    <div class="table-responsive"><table class="table mb-0">
                        <thead><tr><th>Date</th><th>Person</th><th class="text-end">Hours</th><th class="text-end">Rate</th><th class="text-end">Cost</th><th>Remarks</th></tr></thead>
                        <tbody>@forelse($wo->labour as $l)<tr><td>{{ show_date($l->work_date) }}</td><td>{{ $l->user->name }}</td><td class="text-end">{{ $l->hours + 0 }}</td><td class="text-end">{{ money($l->rate) }}</td><td class="text-end">{{ money($l->hours * $l->rate) }}</td><td class="small">{{ $l->remarks }}</td></tr>
                        @empty<tr><td colspan="6" class="text-muted small text-center py-3">No labour recorded</td></tr>@endforelse</tbody>
                        @if($wo->labour->isNotEmpty())<tfoot><tr><th colspan="2" class="text-end">Total</th><th class="text-end">{{ $wo->labour->sum('hours') + 0 }}</th><th></th><th class="text-end">{{ money($wo->labourCost()) }}</th><th></th></tr></tfoot>@endif
                    </table></div>
                    @if($techCan && $wo->isOpen())
                    <div class="card-footer"><form method="POST" action="{{ route('work-orders.labour.store', $wo) }}" class="row g-2 align-items-end">@csrf
                        <div class="col-md-3"><label class="form-label small">Person</label><select name="user_id" class="form-select form-select-sm" required>@foreach($technicians as $t)<option value="{{ $t->id }}" @selected($t->id == ($wo->assigned_to ?? auth()->id()))>{{ $t->name }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label small">Date</label><input type="date" name="work_date" class="form-control form-control-sm" value="{{ today()->toDateString() }}" required></div>
                        <div class="col-md-2"><label class="form-label small">Hours</label><input type="number" step="0.25" name="hours" class="form-control form-control-sm" required></div>
                        <div class="col-md-2"><label class="form-label small">Rate/hr</label><input type="number" step="0.01" name="rate" class="form-control form-control-sm" value="{{ setting('pm.labour_rate', 0) }}"></div>
                        <div class="col-md-2"><label class="form-label small">Remarks</label><input name="remarks" class="form-control form-control-sm"></div>
                        <div class="col-md-1"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus"></i></button></div>
                    </form></div>
                    @endif
                </div>
            </div>
            <div class="tab-pane fade" id="w-tasks">
                <div class="card">
                    <ul class="list-group list-group-flush">
                        @forelse($wo->tasks as $t)
                        <li class="list-group-item d-flex align-items-center gap-2">
                            @if($techCan && $wo->isOpen())<form method="POST" action="{{ route('work-orders.tasks.toggle', [$wo, $t]) }}">@csrf<button class="btn btn-sm {{ $t->is_done ? 'btn-success' : 'btn-outline-secondary' }}"><i class="bi {{ $t->is_done ? 'bi-check-lg' : 'bi-square' }}"></i></button></form>@else<i class="bi {{ $t->is_done ? 'bi-check-square-fill text-success' : 'bi-square' }}"></i>@endif
                            <span class="{{ $t->is_done ? 'text-decoration-line-through text-muted' : '' }}">{{ $t->description }}</span>
                            @if($t->is_done)<small class="text-muted ms-auto">{{ show_datetime($t->done_at) }}</small>@endif
                        </li>
                        @empty<li class="list-group-item text-muted small">No tasks</li>@endforelse
                    </ul>
                    @if($techCan && $wo->isOpen())<div class="card-footer"><form method="POST" action="{{ route('work-orders.tasks.store', $wo) }}" class="d-flex gap-2">@csrf<input name="description" class="form-control form-control-sm" placeholder="Add task…" required><button class="btn btn-sm btn-primary"><i class="bi bi-plus"></i></button></form></div>@endif
                </div>
            </div>
            <div class="tab-pane fade" id="w-photos">
                <div class="card"><div class="card-body">
                    @if($techCan && $wo->isOpen())
                    <form method="POST" action="{{ route('work-orders.photos.store', $wo) }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-3 border-bottom pb-3">@csrf
                        <div class="col-md-3"><select name="category" class="form-select form-select-sm"><option value="before_photo">Before repair</option><option value="after_photo">After repair</option><option value="document">Document</option></select></div>
                        <div class="col-md-7"><input type="file" name="attachments[]" class="form-control form-control-sm" multiple required></div>
                        <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-upload"></i> Upload</button></div>
                    </form>
                    @endif
                    @foreach(['before_photo' => 'Before Repair', 'after_photo' => 'After Repair', 'document' => 'Documents'] as $cat => $label)
                    @php $files = $wo->attachments->where('category', $cat); @endphp
                    @if($files->isNotEmpty())<h6 class="small text-muted text-uppercase mt-2">{{ $label }}</h6>@include('partials.attachments', ['attachments' => $files, 'canDelete' => $techCan && $wo->isOpen()])@endif
                    @endforeach
                    @if($wo->attachments->isEmpty())<p class="text-muted small mb-0">No photos uploaded.</p>@endif
                </div></div>
            </div>
            <div class="tab-pane fade" id="w-permits">
                <div class="card"><div class="table-responsive"><table class="table mb-0">
                    <thead><tr><th>Permit</th><th>Type</th><th>Valid</th><th>Status</th></tr></thead>
                    <tbody>@forelse($wo->permits as $p)<tr><td><a href="{{ route('permits.show', $p) }}">{{ $p->permit_no }}</a></td><td>{{ \App\Models\SafetyPermit::TYPES[$p->permit_type] ?? $p->permit_type }}</td><td class="small">{{ show_datetime($p->valid_from) }} – {{ show_datetime($p->valid_until) }}</td><td>{!! status_badge($p->status) !!}</td></tr>
                    @empty<tr><td colspan="4" class="text-muted small text-center py-3">No permits</td></tr>@endforelse</tbody></table></div></div>
            </div>
            <div class="tab-pane fade" id="w-verif">
                @forelse($wo->verifications as $v)
                <div class="card"><div class="card-body">
                    <div class="d-flex justify-content-between"><strong>{{ $v->verifier->name }}</strong><span>{!! status_badge($v->decision) !!} <span class="text-muted small">{{ show_datetime($v->verification_date) }}</span></span></div>
                    <div class="small mt-2">
                        {!! $v->machine_operation_ok ? '<span class="badge text-bg-success">Operation OK</span>' : '<span class="badge text-bg-danger">Operation not OK</span>' !!}
                        {!! $v->safety_ok ? '<span class="badge text-bg-success">Safety OK</span>' : '<span class="badge text-bg-danger">Safety not OK</span>' !!}
                        {!! $v->repair_quality_ok ? '<span class="badge text-bg-success">Quality OK</span>' : '<span class="badge text-bg-danger">Quality not OK</span>' !!}
                        {!! $v->production_ready ? '<span class="badge text-bg-success">Production ready</span>' : '' !!}
                    </div>
                    <dl class="detail-list mt-2"><dt>Test Results</dt><dd>{{ $v->test_results ?? '-' }}</dd><dt>Observations</dt><dd>{{ $v->observations ?? '-' }}</dd><dt>Remarks</dt><dd>{{ $v->remarks }}</dd></dl>
                </div></div>
                @empty<div class="card"><div class="card-body text-muted small">Not verified yet.</div></div>@endforelse
            </div>
            <div class="tab-pane fade" id="w-vendor">
                <div class="card">
                    <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Vendor</th><th>Date</th><th>Response</th><th>Resolution</th><th>Quality</th><th class="text-end">Cost</th><th>Feedback</th></tr></thead>
                    <tbody>@forelse($wo->vendorServices as $s)<tr><td>{{ $s->vendor->name }}</td><td>{{ show_date($s->service_date) }}</td><td>{{ $s->response_hours }}h {!! $s->response_within_sla ? '<i class="bi bi-check text-success"></i>' : '<i class="bi bi-x text-danger"></i>' !!}</td><td>{{ $s->resolution_hours }}h {!! $s->resolution_within_sla ? '<i class="bi bi-check text-success"></i>' : '<i class="bi bi-x text-danger"></i>' !!}</td><td>{{ str_repeat('★', $s->work_quality) }}</td><td class="text-end">{{ money($s->cost) }}</td><td class="small">{{ $s->feedback }}</td></tr>
                    @empty<tr><td colspan="7" class="text-muted small text-center py-3">No vendor service recorded</td></tr>@endforelse</tbody></table></div>
                    @canany(['pm.workorders.execute', 'pm.amc.manage'])
                    <div class="card-footer"><form method="POST" action="{{ route('work-orders.vendor-service.store', $wo) }}" class="row g-2 align-items-end">@csrf
                        <div class="col-md-3"><label class="form-label small">Vendor</label><select name="vendor_id" class="form-select form-select-sm" required>@foreach($vendors as $v)<option value="{{ $v->id }}" @selected($wo->vendor_id == $v->id)>{{ $v->name }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label small">Date</label><input type="date" name="service_date" class="form-control form-control-sm" value="{{ today()->toDateString() }}" required></div>
                        <div class="col-md-1"><label class="form-label small">Resp. hrs</label><input type="number" step="0.5" name="response_hours" class="form-control form-control-sm"></div>
                        <div class="col-md-1"><label class="form-label small">Resol. hrs</label><input type="number" step="0.5" name="resolution_hours" class="form-control form-control-sm"></div>
                        <div class="col-md-1"><label class="form-label small">Quality</label><select name="work_quality" class="form-select form-select-sm">@for($i = 5; $i >= 1; $i--)<option value="{{ $i }}" @selected($i == 4)>{{ $i }}</option>@endfor</select></div>
                        <div class="col-md-1"><label class="form-label small">Cost</label><input type="number" step="0.01" name="cost" class="form-control form-control-sm" value="0"></div>
                        <div class="col-md-2"><div class="form-check form-check-inline"><input type="checkbox" name="response_within_sla" value="1" class="form-check-input" id="rsla" checked><label class="form-check-label small" for="rsla">Resp SLA</label></div><div class="form-check form-check-inline"><input type="checkbox" name="resolution_within_sla" value="1" class="form-check-input" id="rrsla" checked><label class="form-check-label small" for="rrsla">Resol SLA</label></div><div class="form-check form-check-inline"><input type="checkbox" name="attended" value="1" class="form-check-input" id="att" checked><label class="form-check-label small" for="att">Attended</label></div><div class="form-check form-check-inline"><input type="checkbox" name="repeat_failure" value="1" class="form-check-input" id="rep"><label class="form-check-label small" for="rep">Repeat</label></div></div>
                        <div class="col-md-1"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus"></i></button></div>
                        <div class="col-12"><input name="feedback" class="form-control form-control-sm" placeholder="Feedback"></div>
                    </form></div>
                    @endcanany
                </div>
            </div>
            <div class="tab-pane fade" id="w-hist"><div class="card"><div class="card-body">@include('partials.history', ['history' => $wo->history])</div></div></div>
        </div>
    </div>

    <div class="col-xl-4">
        @if($wo->source === 'preventive' && $wo->pmSchedule)
        <div class="card border-info">
            <div class="card-header"><i class="bi bi-calendar-check me-1"></i>Preventive Maintenance</div>
            <div class="card-body small">
                <dl class="detail-list"><dt>Plan</dt><dd><a href="{{ route('pm.plans.show', $wo->pmSchedule->plan) }}">{{ $wo->pmSchedule->plan->name }}</a></dd><dt>Trigger</dt><dd>{{ status_label($wo->pmSchedule->trigger_reason) }}</dd><dt>Due</dt><dd>{{ show_date($wo->pmSchedule->due_date) }} {{ $wo->pmSchedule->due_hours ? '/ ' . $wo->pmSchedule->due_hours . ' hrs' : '' }}</dd><dt>Checklist</dt><dd>{{ $wo->pmSchedule->plan->checklist?->name ?? 'None' }}</dd></dl>
                @if($wo->pmLog)
                <div class="alert alert-success py-2 mb-0">Checklist completed on {{ show_date($wo->pmLog->completed_date) }}. <a href="{{ route('pm.logs.show', $wo->pmLog) }}">View</a></div>
                @elseif($techCan && $wo->isOpen())
                <a href="{{ route('pm.schedules.complete', $wo->pmSchedule) }}" class="btn btn-info w-100 text-white"><i class="bi bi-list-check me-1"></i> Fill PM Checklist</a>
                @endif
            </div>
        </div>
        @endif
        <div class="card">
            <div class="card-header">Cost Summary</div>
            <div class="card-body small">
                <dl class="detail-list">
                    <dt>Parts</dt><dd>{{ money($wo->partsCost()) }}</dd>
                    <dt>Labour</dt><dd>{{ money($wo->labourCost()) }}</dd>
                    <dt>Vendor services</dt><dd>{{ money($wo->vendorServices->sum('cost')) }}</dd>
                    <dt>Recorded actual</dt><dd class="fw-bold">{{ money($wo->actual_cost) }}</dd>
                    <dt>Estimated</dt><dd>{{ money($wo->estimated_cost) }}</dd>
                </dl>
            </div>
        </div>
        <div class="card"><div class="card-header">Timeline</div><div class="card-body" style="max-height:400px;overflow-y:auto">@include('partials.history', ['history' => $wo->history->take(8)])</div></div>
    </div>
</div>

<div class="modal fade" id="holdModal"><div class="modal-dialog"><form method="POST" action="{{ route('work-orders.hold', $wo) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Put On Hold</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label required">Reason (waiting for spares, permit, vendor…)</label><textarea name="hold_reason" class="form-control" rows="3" required></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-warning">Hold</button></div>
</form></div></div>
<div class="modal fade" id="cancelModal"><div class="modal-dialog"><form method="POST" action="{{ route('work-orders.cancel', $wo) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Cancel Work Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label required">Reason</label><textarea name="reason" class="form-control" rows="3" required></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-danger">Cancel Work Order</button></div>
</form></div></div>
@endsection
