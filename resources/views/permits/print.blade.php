<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>{{ $permit->permit_no }}</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{font-size:12px;padding:20px} .box{border:1px solid #333;padding:8px;min-height:50px} h5{margin:12px 0 6px;font-size:13px;text-transform:uppercase;border-bottom:1px solid #333}</style></head>
<body onload="window.print()">
<div class="d-flex justify-content-between border-bottom border-2 pb-2 mb-3"><div><h3 class="mb-0">{{ setting('pm.company_name') }}</h3><div>PERMIT TO WORK – {{ \App\Models\SafetyPermit::TYPES[$permit->permit_type] ?? $permit->permit_type }}</div></div><div class="text-end"><h3 class="mb-0">{{ $permit->permit_no }}</h3><div>Status: {{ status_label($permit->status) }}</div></div></div>
<table class="table table-bordered table-sm">
<tr><th width="18%">Asset</th><td>{{ $permit->asset->asset_code }} – {{ $permit->asset->name }}</td><th width="18%">Work Order</th><td>{{ $permit->workOrder?->wo_no ?? '-' }}</td></tr>
<tr><th>Location</th><td>{{ $permit->work_location }}</td><th>Validity</th><td>{{ show_datetime($permit->valid_from) }} to {{ show_datetime($permit->valid_until) }}</td></tr>
<tr><th>Issued By</th><td>{{ $permit->issuer->name }}</td><th>Approved By</th><td>{{ $permit->approver?->name ?? '________________' }} {{ $permit->approved_at ? show_datetime($permit->approved_at) : '' }}</td></tr>
</table>
<h5>Work Description</h5><div class="box">{{ $permit->work_description }}</div>
<h5>Hazards Identified</h5><div class="box">{{ $permit->hazards_identified }}</div>
<h5>Safety Precautions</h5><div class="box">{{ $permit->safety_precautions }}</div>
<h5>PPE Requirements</h5><div class="box">{{ $permit->ppe_requirements }}</div>
<h5>Lockout / Tagout</h5>
<table class="table table-bordered table-sm"><tr><th>Energy Source</th><th>Isolation Point</th><th>Lock</th><th>Tag</th><th>Isolated At / By</th><th>Verified</th><th>De-isolated At / By</th></tr>
@forelse($permit->lotoRecords as $l)<tr><td>{{ $l->energy_source }}</td><td>{{ $l->isolation_point }}</td><td>{{ $l->lock_number }}</td><td>{{ $l->tag_number }}</td><td>{{ show_datetime($l->isolated_at) }} {{ $l->isolator->name }}</td><td>{{ $l->verifier?->name }}</td><td>{{ $l->deisolated_at ? show_datetime($l->deisolated_at) . ' ' . $l->deisolator?->name : '' }}</td></tr>@empty<tr><td colspan="7">&nbsp;</td></tr><tr><td colspan="7">&nbsp;</td></tr>@endforelse</table>
<h5>Closure</h5><div class="box">{{ $permit->closure_remarks }}</div>
<div class="row mt-5 text-center"><div class="col-3"><div class="border-top pt-1">Permit Issuer</div></div><div class="col-3"><div class="border-top pt-1">Safety Officer</div></div><div class="col-3"><div class="border-top pt-1">Performing Authority</div></div><div class="col-3"><div class="border-top pt-1">Area In-charge</div></div></div>
</body></html>
