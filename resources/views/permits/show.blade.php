@extends('layouts.app')
@section('title', $permit->permit_no)
@section('page-title'){{ $permit->permit_no }} {!! status_badge($permit->status, \App\Models\SafetyPermit::STATUSES[$permit->status] ?? null) !!} <span class="badge text-bg-light border">{{ \App\Models\SafetyPermit::TYPES[$permit->permit_type] ?? $permit->permit_type }}</span>@endsection
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('permits.index') }}">Permits</a></li><li class="breadcrumb-item active">{{ $permit->permit_no }}</li>@endsection
@section('page-actions')
<a href="{{ route('permits.print', $permit) }}" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i> Print</a>
@can('pm.safety.manage')
@if($permit->status === 'draft')<a href="{{ route('permits.edit', $permit) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a><form method="POST" action="{{ route('permits.issue', $permit) }}">@csrf<button class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i> Submit for Approval</button></form>@endif
@endcan
@can('pm.safety.approve')
@if($permit->status === 'issued')<form method="POST" action="{{ route('permits.approve', $permit) }}">@csrf<button class="btn btn-success btn-sm"><i class="bi bi-check-lg me-1"></i> Approve Permit</button></form>@endif
@if(in_array($permit->status, ['active', 'issued']))<button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#closeModal"><i class="bi bi-lock me-1"></i> Close Permit</button>@endif
@if(!in_array($permit->status, ['closed', 'cancelled']))<button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">Cancel</button>@endif
@endcan
@endsection

@section('content')
@if($permit->status === 'active' && $permit->valid_until->isPast())<div class="alert alert-danger"><i class="bi bi-alarm me-1"></i> This permit's validity has expired. Work must stop; re-validate by issuing a new permit.</div>@endif
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card"><div class="card-header">Permit Details</div><div class="card-body">
            <dl class="detail-list">
                <dt>Asset</dt><dd><a href="{{ route('assets.show', $permit->asset) }}">{{ $permit->asset->asset_code }} – {{ $permit->asset->name }}</a></dd>
                <dt>Work Order</dt><dd>@if($permit->workOrder)<a href="{{ route('work-orders.show', $permit->workOrder) }}">{{ $permit->workOrder->wo_no }}</a> {!! status_badge($permit->workOrder->status) !!}@else - @endif</dd>
                <dt>Location</dt><dd>{{ $permit->work_location ?? '-' }}</dd>
                <dt>Validity</dt><dd>{{ show_datetime($permit->valid_from) }} → {{ show_datetime($permit->valid_until) }}</dd>
                <dt>Issued By</dt><dd>{{ $permit->issuer->name }} · {{ show_datetime($permit->created_at) }}</dd>
                <dt>Approved By</dt><dd>{{ $permit->approver?->name ?? '-' }} {{ $permit->approved_at ? '· ' . show_datetime($permit->approved_at) : '' }}</dd>
                <dt>Work Description</dt><dd>{{ $permit->work_description }}</dd>
                <dt>Hazards</dt><dd>{{ $permit->hazards_identified ?? '-' }}</dd>
                <dt>Precautions</dt><dd>{{ $permit->safety_precautions ?? '-' }}</dd>
                <dt>PPE</dt><dd>{{ $permit->ppe_requirements ?? '-' }}</dd>
            </dl>
            @if($permit->closure_remarks)<div class="alert alert-secondary small mb-0"><strong>{{ status_label($permit->status) }}:</strong> {{ $permit->closure_remarks }} — {{ $permit->closer?->name }} {{ show_datetime($permit->closed_at) }}</div>@endif
        </div></div>

        <div class="card">
            <div class="card-header"><span><i class="bi bi-lock-fill me-1"></i>Lockout / Tagout Records</span>@if($permit->hasPendingIsolation())<span class="badge text-bg-danger">{{ $permit->lotoRecords->whereNull('deisolated_at')->count() }} isolation(s) in place</span>@else<span class="badge text-bg-success">All clear</span>@endif</div>
            <div class="table-responsive"><table class="table mb-0">
                <thead><tr><th>Energy Source</th><th>Isolation Point</th><th>Lock / Tag</th><th>Isolated</th><th>Verified By</th><th>De-isolated</th><th></th></tr></thead>
                <tbody>@forelse($permit->lotoRecords as $l)
                <tr class="{{ $l->deisolated_at ? '' : 'table-warning' }}">
                    <td>{{ $l->energy_source }}</td><td>{{ $l->isolation_point }}</td><td class="small">{{ $l->lock_number ?? '-' }} / {{ $l->tag_number ?? '-' }}</td>
                    <td class="small">{{ show_datetime($l->isolated_at) }}<br>{{ $l->isolator->name }}</td><td class="small">{{ $l->verifier?->name ?? '-' }}</td>
                    <td class="small">@if($l->deisolated_at){{ show_datetime($l->deisolated_at) }}<br>{{ $l->deisolator?->name }}@else<span class="badge text-bg-danger">Isolated</span>@endif</td>
                    <td class="text-end">@can('pm.safety.manage')@if(!$l->deisolated_at && !in_array($permit->status, ['closed', 'cancelled']))
                        <form method="POST" action="{{ route('permits.loto.deisolate', [$permit, $l]) }}" class="d-flex gap-1">@csrf<input type="hidden" name="deisolated_at" value="{{ now()->format('Y-m-d H:i:s') }}"><select name="deisolated_by" class="form-select form-select-sm" style="width:150px">@foreach($users as $u)<option value="{{ $u->id }}" @selected($u->id == auth()->id())>{{ $u->name }}</option>@endforeach</select><button class="btn btn-sm btn-success" title="De-isolate now"><i class="bi bi-unlock"></i></button></form>
                    @endif @endcan</td>
                </tr>
                @empty<tr><td colspan="7" class="text-muted small text-center py-3">No isolations recorded</td></tr>@endforelse</tbody></table></div>
            @can('pm.safety.manage')
            @if(!in_array($permit->status, ['closed', 'cancelled']))
            <div class="card-footer"><form method="POST" action="{{ route('permits.loto.store', $permit) }}" class="row g-2 align-items-end">@csrf
                <div class="col-md-2"><label class="form-label small">Energy Source</label><input name="energy_source" list="energy" class="form-control form-control-sm" required><datalist id="energy"><option>Electrical</option><option>Hydraulic</option><option>Pneumatic</option><option>Steam</option><option>Mechanical</option><option>Chemical</option><option>Thermal</option></datalist></div>
                <div class="col-md-2"><label class="form-label small">Isolation Point</label><input name="isolation_point" class="form-control form-control-sm" required placeholder="MCC-3 Feeder 7"></div>
                <div class="col-md-1"><label class="form-label small">Lock No</label><input name="lock_number" class="form-control form-control-sm"></div>
                <div class="col-md-1"><label class="form-label small">Tag No</label><input name="tag_number" class="form-control form-control-sm"></div>
                <div class="col-md-2"><label class="form-label small">Isolated At</label><input type="datetime-local" name="isolated_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}" required></div>
                <div class="col-md-2"><label class="form-label small">Isolated By</label><select name="isolated_by" class="form-select form-select-sm">@foreach($users as $u)<option value="{{ $u->id }}" @selected($u->id == auth()->id())>{{ $u->name }}</option>@endforeach</select></div>
                <div class="col-md-1"><label class="form-label small">Verified By</label><select name="verified_by" class="form-select form-select-sm"><option value="">-</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
                <div class="col-md-1"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-lock"></i></button></div>
            </form></div>
            @endif
            @endcan
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card"><div class="card-header">Documents</div><div class="card-body">@include('partials.attachments', ['attachments' => $permit->attachments, 'canDelete' => auth()->user()->hasPermission('pm.safety.approve')])</div></div>
        <div class="card"><div class="card-header">History</div><div class="card-body">@include('partials.history', ['history' => $permit->history])</div></div>
    </div>
</div>
<div class="modal fade" id="closeModal"><div class="modal-dialog"><form method="POST" action="{{ route('permits.close', $permit) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Close Permit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">@if($permit->hasPendingIsolation())<div class="alert alert-danger small">Isolations are still in place. De-isolate all energy sources first.</div>@endif<label class="form-label required">Closure Remarks (area cleared, guards replaced, machine handed back)</label><textarea name="closure_remarks" class="form-control" rows="3" required></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-dark" {{ $permit->hasPendingIsolation() ? 'disabled' : '' }}>Close Permit</button></div>
</form></div></div>
<div class="modal fade" id="cancelModal"><div class="modal-dialog"><form method="POST" action="{{ route('permits.cancel', $permit) }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Cancel Permit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><label class="form-label required">Reason</label><textarea name="closure_remarks" class="form-control" rows="3" required></textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Back</button><button class="btn btn-danger">Cancel Permit</button></div>
</form></div></div>
@endsection
