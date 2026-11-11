@extends('layouts.app')
@section('title', $r->request_no)
@section('page-title'){{ $r->request_no }} {!! status_badge($r->status, \App\Models\MaintenanceRequest::STATUSES[$r->status] ?? null) !!} {!! status_badge($r->priority) !!} @if($r->is_emergency)<span class="badge text-bg-danger">EMERGENCY</span>@endif @endsection
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('requests.index') }}">Requests</a></li><li class="breadcrumb-item active">{{ $r->request_no }}</li>@endsection
@section('page-actions')
@if($r->canBeEditedBy(auth()->user()))<a href="{{ route('requests.edit', $r) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>@endif
@if($r->isOpen() && ($r->requested_by === auth()->id() || auth()->user()->hasPermission('pm.requests.manage')))
<button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal"><i class="bi bi-x-circle me-1"></i> Cancel</button>
@endif
@can('pm.requests.emergency')
@if(in_array($r->status, ['submitted', 'inspection_pending', 'assessment_pending', 'approval_pending', 'sent_back']))
<button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#emergencyModal"><i class="bi bi-lightning-charge me-1"></i> Emergency</button>
@endif
@endcan
@endsection

@section('content')
@php
$steps = [
    ['Raised', 'bi-send', ['submitted']],
    ['Inspection', 'bi-search', ['inspection_pending']],
    ['Technical Assessment', 'bi-clipboard-data', ['assessment_pending']],
    ['Budget Approval', 'bi-cash-coin', ['approval_pending', 'sent_back']],
    ['Work Order', 'bi-clipboard-check', ['approved', 'work_order_created', 'in_progress']],
    ['Verification', 'bi-patch-check', ['verification_pending']],
    ['Closed', 'bi-check2-circle', ['closed']],
];
$order = ['submitted' => 0, 'inspection_pending' => 1, 'assessment_pending' => 2, 'approval_pending' => 3, 'sent_back' => 3, 'approved' => 4, 'work_order_created' => 4, 'in_progress' => 4, 'verification_pending' => 5, 'closed' => 6];
$current = $order[$r->status] ?? -1;
$dead = in_array($r->status, ['rejected', 'cancelled']);
@endphp
<div class="card"><div class="card-body py-3">
    <div class="stepper">
        @foreach($steps as $i => [$label, $icon, $statuses])
        <div class="step {{ $dead ? ($i < ($r->inspections->isNotEmpty() ? 2 : 1) ? 'done' : '') : ($i < $current ? 'done' : ($i == $current ? 'current' : '')) }}"><i class="bi {{ $icon }}"></i>{{ $label }}</div>
        @endforeach
        @if($dead)<div class="step rejected"><i class="bi bi-x-octagon"></i>{{ status_label($r->status) }}</div>@endif
    </div>
</div></div>

<div class="row g-3">
    <div class="col-xl-8">
        {{-- Request details --}}
        <div class="card">
            <div class="card-header">Request Details</div>
            <div class="card-body">
                <dl class="detail-list">
                    <dt>Asset</dt><dd><a href="{{ route('assets.show', $r->asset) }}">{{ $r->asset->asset_code }} – {{ $r->asset->name }}</a> {!! status_badge($r->asset->status, \App\Models\Asset::STATUSES[$r->asset->status] ?? null) !!}</dd>
                    <dt>Component</dt><dd>{{ $r->component ? $r->component->asset_code . ' – ' . $r->component->name : 'Whole machine' }}</dd>
                    <dt>Type</dt><dd>{{ \App\Models\MaintenanceRequest::TYPES[$r->request_type] ?? $r->request_type }}</dd>
                    <dt>Problem Category</dt><dd>{{ $r->problemCategory?->name ?? '-' }}</dd>
                    <dt>Requested By</dt><dd>{{ $r->requester->name }} <span class="text-muted small">({{ $r->requester->designation }})</span></dd>
                    <dt>Date &amp; Shift</dt><dd>{{ show_datetime($r->request_date) }} {{ $r->shift ? '· ' . $r->shift->name : '' }}</dd>
                    <dt>Department</dt><dd>{{ $r->department?->name ?? '-' }}</dd>
                    <dt>Impact</dt><dd>{!! $r->machine_stopped ? '<span class="badge text-bg-danger">Machine stopped</span>' : '<span class="badge text-bg-success">Machine running</span>' !!} {!! $r->production_affected ? '<span class="badge text-bg-warning">Production affected</span>' : '' !!}</dd>
                    @if($r->estimated_cost !== null)<dt>Estimated Cost</dt><dd>{{ money($r->estimated_cost) }}</dd>@endif
                    @if($r->approved_cost !== null)<dt>Approved Cost</dt><dd class="fw-semibold">{{ money($r->approved_cost) }}</dd>@endif
                </dl>
                <div class="border rounded p-3 bg-light"><strong class="small text-muted d-block mb-1">PROBLEM DESCRIPTION</strong>{{ $r->problem_description }}</div>
                @if($r->remarks)<div class="small text-muted mt-2"><strong>Remarks:</strong> {{ $r->remarks }}</div>@endif
                @if($r->attachments->isNotEmpty())<div class="mt-3">@include('partials.attachments', ['attachments' => $r->attachments, 'canDelete' => $r->canBeEditedBy(auth()->user())])</div>@endif
                @if($r->closure_remarks)<div class="alert alert-{{ $r->status === 'closed' ? 'success' : 'secondary' }} mt-3 mb-0 small"><strong>{{ status_label($r->status) }}:</strong> {{ $r->closure_remarks }} <span class="text-muted">— {{ show_datetime($r->closed_at) }}</span></div>@endif
            </div>
        </div>

        {{-- Emergency block --}}
        @if($r->is_emergency)
        <div class="card border-danger">
            <div class="card-header text-danger"><i class="bi bi-lightning-charge me-1"></i>Emergency Maintenance</div>
            <div class="card-body">
                <dl class="detail-list">
                    <dt>Reason</dt><dd>{{ $r->emergency_reason }}</dd>
                    <dt>Authorised By</dt><dd>{{ $r->emergencyApprover?->name }} · {{ show_datetime($r->emergency_approved_at) }}</dd>
                    <dt>Retrospective Review</dt><dd>{!! $r->emergency_reviewed ? status_badge('completed') . ' ' . e($r->emergency_review_remarks) : status_badge('pending') !!}</dd>
                </dl>
                @can('pm.requests.emergency')
                @if(!$r->emergency_reviewed)
                <form method="POST" action="{{ route('requests.emergency-review', $r) }}" class="row g-2">@csrf
                    <div class="col-md-9"><input name="remarks" class="form-control" placeholder="Technical and administrative review remarks" required></div>
                    <div class="col-md-3"><button class="btn btn-outline-danger w-100">Record Review</button></div>
                </form>
                @endif
                @endcan
            </div>
        </div>
        @endif

        {{-- STEP 2: Inspection --}}
        @can('pm.requests.inspect')
        @if(in_array($r->status, ['submitted', 'inspection_pending']))
        <div class="card border-primary">
            <div class="card-header bg-primary text-white"><span><i class="bi bi-search me-1"></i>Step 2 – Record Inspection</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('requests.inspection', $r) }}" enctype="multipart/form-data" class="row g-3">@csrf
                    <div class="col-md-4"><label class="form-label required">Inspection Date</label><input type="datetime-local" name="inspection_date" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                    <div class="col-md-4"><label class="form-label">Machine Condition</label><input name="machine_condition" class="form-control" placeholder="e.g. Running with vibration"></div>
                    <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input type="checkbox" name="machine_running" value="1" class="form-check-input" id="mrun" @checked(!$r->machine_stopped)><label class="form-check-label" for="mrun">Machine running</label></div></div>
                    <div class="col-md-6"><label class="form-label required">Actual Problem Found</label><textarea name="actual_problem" class="form-control" rows="3" required></textarea></div>
                    <div class="col-md-6"><label class="form-label">Root Cause (if known)</label><textarea name="root_cause" class="form-control" rows="3"></textarea></div>
                    <div class="col-md-3"><label class="form-label">Safety Risk</label><select name="safety_risk" class="form-select"><option value="none">None</option><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option></select></div>
                    <div class="col-md-3"><label class="form-label">Production Impact</label><select name="production_impact" class="form-select"><option value="none">None</option><option value="partial">Partial</option><option value="full">Full stop</option></select></div>
                    <div class="col-md-6"><label class="form-label">Temporary Solution Applied</label><input name="temporary_solution" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label">Required Action</label><textarea name="required_action" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-6"><label class="form-label">Inspection Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-6">@include('partials.upload-field', ['label' => 'Photographs'])</div>
                    <div class="col-md-6">
                        <label class="form-label required">Recommendation</label>
                        <select name="recommendation" class="form-select" data-toggle-target="#rejectReason" data-toggle-values="reject"><option value="proceed">Proceed to technical assessment</option><option value="reject">Reject request (invalid)</option></select>
                        <div id="rejectReason" class="mt-2"><input name="rejection_reason" class="form-control" placeholder="Reason for rejection (mandatory)"></div>
                    </div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Inspection</button></div>
                </form>
            </div>
        </div>
        @endif
        @endcan

        {{-- STEP 3: Technical assessment --}}
        @can('pm.requests.assess')
        @if($r->status === 'assessment_pending')
        <div class="card border-primary">
            <div class="card-header bg-primary text-white"><span><i class="bi bi-clipboard-data me-1"></i>Step 3 – Technical Assessment</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('requests.assessment', $r) }}" enctype="multipart/form-data" class="row g-3">@csrf
                    <div class="col-md-4"><label class="form-label required">Assessment Date</label><input type="datetime-local" name="assessment_date" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                    <div class="col-md-4"><label class="form-label required">Repair / Replacement</label><select name="action_required" class="form-select"><option value="repair">Repair</option><option value="replace">Replace</option><option value="both">Repair + Replace parts</option></select></div>
                    <div class="col-md-4"><label class="form-label">Required Manpower</label><input name="required_manpower" class="form-control" placeholder="e.g. 2 fitters, 4 hrs"></div>
                    <div class="col-md-6"><label class="form-label required">Nature of Failure</label><textarea name="failure_nature" class="form-control" rows="3" required></textarea></div>
                    <div class="col-md-6"><label class="form-label">Probable Root Cause</label><textarea name="probable_root_cause" class="form-control" rows="3"></textarea></div>
                    <div class="col-md-4"><div class="form-check form-switch mt-4"><input type="checkbox" name="external_vendor_required" value="1" class="form-check-input" id="ext" data-toggle-target="#vendorBox" data-toggle-values="1"><label class="form-check-label" for="ext">External vendor required</label></div></div>
                    <div class="col-md-4" id="vendorBox"><label class="form-label">Vendor</label><select name="vendor_id" class="form-select"><option value="">-</option>@foreach($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><div class="form-check form-switch mt-4"><input type="checkbox" name="permit_required" value="1" class="form-check-input" id="ptw"><label class="form-check-label" for="ptw">Permit to Work (PTW) required</label></div></div>

                    <div class="col-12"><label class="form-label">Required Spare Parts</label>
                        <div id="spareRows">
                            <template>
                                <div class="row g-2 mb-2 repeater-row">
                                    <div class="col-md-5"><select name="spares[__INDEX__][spare_id]" class="form-select form-select-sm"><option value="">- From store -</option>@foreach($spares as $s)<option value="{{ $s->id }}">{{ $s->label }} (stock {{ $s->current_stock + 0 }}, {{ money($s->unit_cost) }})</option>@endforeach</select></div>
                                    <div class="col-md-3"><input name="spares[__INDEX__][spare_name]" class="form-control form-control-sm" placeholder="or free text part name"></div>
                                    <div class="col-md-1"><input type="number" step="0.01" name="spares[__INDEX__][quantity]" class="form-control form-control-sm" placeholder="Qty" value="1"></div>
                                    <div class="col-md-2"><input type="number" step="0.01" name="spares[__INDEX__][estimated_cost]" class="form-control form-control-sm" placeholder="Cost"></div>
                                    <div class="col-md-1"><button type="button" class="btn btn-sm btn-light text-danger" data-repeater-remove><i class="bi bi-x"></i></button></div>
                                </div>
                            </template>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-repeater-add="#spareRows"><i class="bi bi-plus"></i> Add spare</button>
                    </div>

                    <div class="col-md-3"><label class="form-label">Est. Downtime (hrs)</label><input type="number" step="0.5" name="estimated_downtime_hours" class="form-control" value="0"></div>
                    <div class="col-md-3"><label class="form-label">Est. Labour Cost</label><input type="number" step="0.01" name="estimated_labour_cost" class="form-control" value="0" data-sum="est"></div>
                    <div class="col-md-3"><label class="form-label">Est. Material Cost</label><input type="number" step="0.01" name="estimated_material_cost" class="form-control" value="0" data-sum="est"></div>
                    <div class="col-md-3"><label class="form-label">Est. External Service Cost</label><input type="number" step="0.01" name="estimated_service_cost" class="form-control" value="0" data-sum="est"></div>
                    <div class="col-md-12"><div class="alert alert-light border mb-0">Total estimated cost: <strong>{{ setting('pm.currency_symbol', '₹') }} <span data-sum-total="est">0.00</span></strong></div></div>
                    <div class="col-md-8"><label class="form-label">Technical Recommendation</label><textarea name="recommendation" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-4">@include('partials.upload-field', ['label' => 'Attachments'])</div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save Assessment &amp; Send for Approval</button></div>
                </form>
            </div>
        </div>
        @endif
        @endcan

        {{-- STEP 4: Approval --}}
        @can('pm.requests.approve')
        @if($r->status === 'approval_pending')
        <div class="card border-warning">
            <div class="card-header bg-warning"><span><i class="bi bi-cash-coin me-1"></i>Step 4 – Budget / Administrative Approval</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('requests.approval', $r) }}" class="row g-3">@csrf
                    <div class="col-md-3"><label class="form-label">Estimated Cost</label><input class="form-control" value="{{ money($r->estimated_cost) }}" disabled></div>
                    <div class="col-md-3"><label class="form-label">Approved Cost</label><input type="number" step="0.01" name="approved_cost" class="form-control" value="{{ $r->estimated_cost }}"></div>
                    <div class="col-md-6"><label class="form-label required">Remarks</label><input name="remarks" class="form-control" required placeholder="Mandatory for every decision"></div>
                    <div class="col-12 d-flex gap-2 flex-wrap">
                        <button name="action" value="approved" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Approve &amp; Generate Work Order</button>
                        <button name="action" value="sent_back" class="btn btn-outline-warning"><i class="bi bi-arrow-counterclockwise me-1"></i> Send Back for Clarification</button>
                        <button name="action" value="rejected" class="btn btn-outline-danger"><i class="bi bi-x-lg me-1"></i> Reject</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
        @endcan

        {{-- Sent back: requester / expert re-submits --}}
        @if($r->status === 'sent_back' && ($r->requested_by === auth()->id() || auth()->user()->hasAnyPermission(['pm.requests.assess', 'pm.requests.manage'])))
        <div class="card border-warning">
            <div class="card-header bg-warning">Sent Back – Clarification Required</div>
            <div class="card-body">
                <p class="small">{{ $r->approvals->first()?->remarks }}</p>
                <form method="POST" action="{{ route('requests.resubmit', $r) }}" class="row g-2">@csrf
                    <div class="col-md-9"><input name="remarks" class="form-control" placeholder="Your clarification" required></div>
                    <div class="col-md-3"><button class="btn btn-warning w-100">Re-submit for Approval</button></div>
                </form>
            </div>
        </div>
        @endif

        {{-- Records --}}
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#t-insp">Inspections <span class="badge text-bg-light">{{ $r->inspections->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-assess">Assessments <span class="badge text-bg-light">{{ $r->assessments->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-appr">Approvals <span class="badge text-bg-light">{{ $r->approvals->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-wo">Work Orders <span class="badge text-bg-light">{{ $r->workOrders->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#t-hist">History</button></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="t-insp">
                @forelse($r->inspections as $i)
                <div class="card"><div class="card-body">
                    <div class="d-flex justify-content-between"><strong>{{ $i->inspector->name }}</strong><span class="text-muted small">{{ show_datetime($i->inspection_date) }}</span></div>
                    <dl class="detail-list mt-2">
                        <dt>Machine Condition</dt><dd>{{ $i->machine_condition ?? '-' }} · {{ $i->machine_running ? 'Running' : 'Stopped' }}</dd>
                        <dt>Safety / Production</dt><dd>Risk: {!! status_badge($i->safety_risk) !!} Impact: {{ status_label($i->production_impact) }}</dd>
                        <dt>Actual Problem</dt><dd>{{ $i->actual_problem }}</dd>
                        <dt>Root Cause</dt><dd>{{ $i->root_cause ?? '-' }}</dd>
                        <dt>Temporary Solution</dt><dd>{{ $i->temporary_solution ?? '-' }}</dd>
                        <dt>Required Action</dt><dd>{{ $i->required_action ?? '-' }}</dd>
                        <dt>Recommendation</dt><dd>{!! $i->recommendation === 'reject' ? status_badge('rejected') . ' ' . e($i->rejection_reason) : status_badge('approved', 'Proceed') !!}</dd>
                        <dt>Remarks</dt><dd>{{ $i->remarks ?? '-' }}</dd>
                    </dl>
                    @if($i->attachments->isNotEmpty())@include('partials.attachments', ['attachments' => $i->attachments])@endif
                </div></div>
                @empty<div class="card"><div class="card-body text-muted small">Not inspected yet.</div></div>@endforelse
            </div>
            <div class="tab-pane fade" id="t-assess">
                @forelse($r->assessments as $a)
                <div class="card"><div class="card-body">
                    <div class="d-flex justify-content-between"><strong>{{ $a->assessor->name }}</strong><span class="text-muted small">{{ show_datetime($a->assessment_date) }}</span></div>
                    <dl class="detail-list mt-2">
                        <dt>Nature of Failure</dt><dd>{{ $a->failure_nature }}</dd>
                        <dt>Probable Root Cause</dt><dd>{{ $a->probable_root_cause ?? '-' }}</dd>
                        <dt>Action</dt><dd>{{ status_label($a->action_required) }} · Manpower: {{ $a->required_manpower ?? '-' }}</dd>
                        <dt>External Vendor</dt><dd>{{ $a->external_vendor_required ? ($a->vendor?->name ?? 'Yes') : 'No' }} {!! $a->permit_required ? '<span class="badge text-bg-warning">PTW required</span>' : '' !!}</dd>
                        <dt>Est. Downtime</dt><dd>{{ $a->estimated_downtime_hours }} hrs</dd>
                        <dt>Estimated Cost</dt><dd>Labour {{ money($a->estimated_labour_cost) }} + Material {{ money($a->estimated_material_cost) }} + Service {{ money($a->estimated_service_cost) }} = <strong>{{ money($a->total_estimated_cost) }}</strong></dd>
                        <dt>Recommendation</dt><dd>{{ $a->recommendation ?? '-' }}</dd>
                    </dl>
                    @if($a->spares->isNotEmpty())
                    <table class="table table-sm mb-2"><thead><tr><th>Spare</th><th>Qty</th><th>Est. Cost</th><th>In Stock</th></tr></thead><tbody>
                        @foreach($a->spares as $s)<tr><td>{{ $s->spare ? $s->spare->label : $s->spare_name }}</td><td>{{ $s->quantity + 0 }}</td><td>{{ money($s->estimated_cost) }}</td><td>{{ $s->spare ? ($s->spare->current_stock + 0) : '-' }} @if($s->spare && $s->spare->current_stock < $s->quantity)<span class="badge text-bg-warning">Short</span>@endif</td></tr>@endforeach
                    </tbody></table>
                    @endif
                    @if($a->attachments->isNotEmpty())@include('partials.attachments', ['attachments' => $a->attachments])@endif
                </div></div>
                @empty<div class="card"><div class="card-body text-muted small">No technical assessment yet.</div></div>@endforelse
            </div>
            <div class="tab-pane fade" id="t-appr">
                <div class="card"><div class="table-responsive"><table class="table mb-0">
                    <thead><tr><th>Date</th><th>Stage</th><th>Approver</th><th>Decision</th><th class="text-end">Est. Cost</th><th class="text-end">Approved</th><th>Remarks</th></tr></thead>
                    <tbody>@forelse($r->approvals as $ap)
                    <tr><td class="text-nowrap small">{{ show_datetime($ap->acted_at) }}</td><td>{{ status_label($ap->stage) }}</td><td>{{ $ap->approver->name }}</td><td>{!! status_badge($ap->action) !!}</td><td class="text-end">{{ money($ap->estimated_cost) }}</td><td class="text-end">{{ $ap->approved_cost !== null ? money($ap->approved_cost) : '-' }}</td><td class="small">{{ $ap->remarks }}</td></tr>
                    @empty<tr><td colspan="7" class="text-muted small">No approval decisions yet.</td></tr>@endforelse</tbody></table></div></div>
            </div>
            <div class="tab-pane fade" id="t-wo">
                <div class="card"><div class="table-responsive"><table class="table mb-0">
                    <thead><tr><th>Work Order</th><th>Source</th><th>Technician</th><th>Planned</th><th>Status</th><th class="text-end">Actual Cost</th></tr></thead>
                    <tbody>@forelse($r->workOrders as $w)
                    <tr><td><a href="{{ route('work-orders.show', $w) }}" class="fw-semibold">{{ $w->wo_no }}</a></td><td>{{ status_label($w->source) }}</td><td>{{ $w->technician?->name ?? '-' }}</td><td class="small">{{ show_date($w->planned_start) }} – {{ show_date($w->planned_end) }}</td><td>{!! status_badge($w->status) !!}</td><td class="text-end">{{ money($w->actual_cost) }}</td></tr>
                    @empty<tr><td colspan="6" class="text-muted small">No work order yet. Generated automatically on approval.</td></tr>@endforelse</tbody></table></div></div>
            </div>
            <div class="tab-pane fade" id="t-hist"><div class="card"><div class="card-body">@include('partials.history', ['history' => $r->history])</div></div></div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">Asset Snapshot</div>
            <div class="card-body small">
                <dl class="detail-list">
                    <dt>Location</dt><dd>{{ $r->asset->location ?? '-' }}</dd>
                    <dt>Criticality</dt><dd>{!! status_badge($r->asset->criticality) !!}</dd>
                    <dt>Running Hours</dt><dd>{{ number_format($r->asset->current_running_hours) }}</dd>
                    <dt>Open Requests</dt><dd>{{ $r->asset->requests()->whereNotIn('status', ['closed', 'cancelled', 'rejected'])->count() }}</dd>
                </dl>
                <a href="{{ route('assets.show', $r->asset) }}" class="btn btn-sm btn-light w-100"><i class="bi bi-cpu me-1"></i> Open asset page</a>
            </div>
        </div>
        @if($r->purchaseRequests->isNotEmpty())
        <div class="card"><div class="card-header">Purchase Requisitions</div><ul class="list-group list-group-flush">@foreach($r->purchaseRequests as $pr)<li class="list-group-item d-flex justify-content-between"><a href="{{ route('purchase-requests.show', $pr) }}">{{ $pr->pr_no }} – {{ $pr->spare->name }}</a>{!! status_badge($pr->status) !!}</li>@endforeach</ul></div>
        @endif
        <div class="card">
            <div class="card-header">Timeline</div>
            <div class="card-body" style="max-height:420px;overflow-y:auto">@include('partials.history', ['history' => $r->history->take(8)])</div>
        </div>
    </div>
</div>

{{-- Modals --}}
<div class="modal fade" id="cancelModal"><div class="modal-dialog"><form method="POST" action="{{ route('requests.cancel', $r) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Cancel Request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label required">Reason</label><textarea name="reason" class="form-control" rows="3" required></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-danger">Cancel Request</button></div>
</form></div></div>

<div class="modal fade" id="emergencyModal"><div class="modal-dialog"><form method="POST" action="{{ route('requests.emergency', $r) }}" class="modal-content">@csrf
    <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="bi bi-lightning-charge me-1"></i>Emergency Maintenance</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <p class="small">This bypasses the normal inspection → assessment → approval sequence and generates a work order immediately. Use only when waiting would create a safety risk or major production loss. A retrospective review will be required and all actions are logged.</p>
        <label class="form-label required">Emergency Reason</label><textarea name="emergency_reason" class="form-control" rows="3" required minlength="10"></textarea>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-danger">Authorise Emergency Work</button></div>
</form></div></div>
@endsection
