<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $wo->wo_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{font-size:12px;padding:20px} .box{border:1px solid #333;padding:8px;min-height:60px} h5{margin:14px 0 6px;font-size:13px;text-transform:uppercase;border-bottom:1px solid #333} @media print{.no-print{display:none}}</style>
</head>
<body onload="window.print()">
<div class="d-flex justify-content-between align-items-start border-bottom border-2 pb-2 mb-3">
    <div><h3 class="mb-0">{{ setting('pm.company_name') }}</h3><div>Maintenance Work Order</div></div>
    <div class="text-end"><h3 class="mb-0">{{ $wo->wo_no }}</h3><div>Status: {{ status_label($wo->status) }} · Priority: {{ ucfirst($wo->priority) }}</div><div>Printed {{ now()->format('d M Y H:i') }}</div></div>
</div>
<table class="table table-bordered table-sm">
    <tr><th width="18%">Asset</th><td width="32%">{{ $wo->asset->asset_code }} – {{ $wo->asset->name }}</td><th width="18%">Location</th><td>{{ $wo->asset->location }}</td></tr>
    <tr><th>Request No.</th><td>{{ $wo->request?->request_no ?? status_label($wo->source) }}</td><th>Work Type</th><td>{{ status_label($wo->work_type) }}</td></tr>
    <tr><th>Team / Technician</th><td>{{ $wo->team?->name }} / {{ $wo->technician?->name ?? '________________' }}</td><th>Planned</th><td>{{ show_date($wo->planned_start) }} to {{ show_date($wo->planned_end) }}</td></tr>
    <tr><th>Actual Start / End</th><td>{{ show_datetime($wo->actual_start) }} / {{ show_datetime($wo->actual_end) }}</td><th>Permit Required</th><td>{{ $wo->permit_required ? 'YES – ' . $wo->permits->pluck('permit_no')->join(', ') : 'No' }}</td></tr>
</table>
<h5>Work Description</h5><div class="box">{{ $wo->description }}</div>
<h5>Safety Requirements</h5><div class="box">{{ $wo->safety_requirements ?? '' }}</div>
<h5>Actual Problem Found</h5><div class="box">{{ $wo->actual_problem_found }}</div>
<h5>Work Performed</h5><div class="box">{{ $wo->work_performed }}</div>
<div class="row">
    <div class="col-6"><h5>Parts Consumed</h5>
        <table class="table table-bordered table-sm"><tr><th>Spare</th><th>Type</th><th>Qty</th></tr>
        @forelse($wo->stockTransactions as $t)<tr><td>{{ $t->spare->name }}</td><td>{{ status_label($t->type) }}</td><td>{{ $t->quantity + 0 }}</td></tr>@empty<tr><td colspan="3">&nbsp;</td></tr><tr><td colspan="3">&nbsp;</td></tr>@endforelse</table></div>
    <div class="col-6"><h5>Labour</h5>
        <table class="table table-bordered table-sm"><tr><th>Name</th><th>Date</th><th>Hours</th></tr>
        @forelse($wo->labour as $l)<tr><td>{{ $l->user->name }}</td><td>{{ show_date($l->work_date) }}</td><td>{{ $l->hours + 0 }}</td></tr>@empty<tr><td colspan="3">&nbsp;</td></tr><tr><td colspan="3">&nbsp;</td></tr>@endforelse</table></div>
</div>
<table class="table table-bordered table-sm mt-2"><tr><th>Estimated Cost</th><td>{{ money($wo->estimated_cost) }}</td><th>Actual Cost</th><td>{{ money($wo->actual_cost) }}</td><th>Downtime</th><td>{{ $wo->downtime_hours + 0 }} hrs</td><th>Labour Hours</th><td>{{ $wo->labour_hours + 0 }}</td></tr></table>
<div class="row mt-5 text-center">
    <div class="col-4"><div class="border-top pt-1">Technician</div></div>
    <div class="col-4"><div class="border-top pt-1">Verified By ({{ $wo->verifier?->name }})</div></div>
    <div class="col-4"><div class="border-top pt-1">Production In-charge</div></div>
</div>
<button class="btn btn-primary mt-4 no-print" onclick="window.print()">Print</button>
</body>
</html>
