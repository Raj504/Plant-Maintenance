@extends('layouts.app')
@section('title', 'Issue / Return')
@section('page-title', 'Spare Issue & Return against Work Order')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('spares.index') }}">Spares</a></li><li class="breadcrumb-item active">Issue / Return</li>@endsection
@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-danger"><div class="card-header"><i class="bi bi-box-arrow-right me-1 text-danger"></i>Issue Spare</div><div class="card-body">
            <form method="POST" action="{{ route('stock.issue.store') }}" class="row g-3">@csrf
                <div class="col-12"><label class="form-label required">Work Order</label><select name="work_order_id" class="form-select" required><option value="">Select…</option>@foreach($workOrders as $w)<option value="{{ $w->id }}" @selected(old('work_order_id', $selectedWo) == $w->id)>{{ $w->wo_no }} – {{ $w->asset->name }} ({{ status_label($w->status) }})</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label required">Spare Part</label><select name="spare_id" class="form-select spare-select" required><option value="">Select…</option>@foreach($spares as $s)<option value="{{ $s->id }}" data-stock="{{ $s->current_stock + 0 }}" data-uom="{{ $s->uom }}">{{ $s->label }} — stock {{ $s->current_stock + 0 }} {{ $s->uom }}</option>@endforeach</select><div class="form-text stock-hint"></div></div>
                <div class="col-6"><label class="form-label required">Quantity</label><input type="number" step="0.01" name="quantity" class="form-control" required></div>
                <div class="col-6"><label class="form-label required">Date</label><input type="date" name="transaction_date" class="form-control" value="{{ today()->toDateString() }}" required></div>
                <div class="col-12"><input name="remarks" class="form-control" placeholder="Remarks"></div>
                <div class="col-12"><button class="btn btn-danger"><i class="bi bi-box-arrow-right me-1"></i> Issue</button></div>
            </form>
            <div class="small text-muted mt-2">The system blocks issuing more than the available stock. Short? <a href="{{ route('purchase-requests.create') }}">Raise a purchase requisition</a>.</div>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card border-success"><div class="card-header"><i class="bi bi-box-arrow-in-left me-1 text-success"></i>Return Unused Spare</div><div class="card-body">
            <form method="POST" action="{{ route('stock.return') }}" class="row g-3">@csrf
                <div class="col-12"><label class="form-label required">Work Order (original issue)</label><select name="work_order_id" class="form-select" required><option value="">Select…</option>@foreach($workOrders as $w)<option value="{{ $w->id }}" @selected(old('work_order_id', $selectedWo) == $w->id)>{{ $w->wo_no }} – {{ $w->asset->name }}</option>@endforeach</select></div>
                <div class="col-12"><label class="form-label required">Spare Part</label><select name="spare_id" class="form-select" required><option value="">Select…</option>@foreach($spares as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach</select></div>
                <div class="col-6"><label class="form-label required">Quantity</label><input type="number" step="0.01" name="quantity" class="form-control" required></div>
                <div class="col-6"><label class="form-label required">Date</label><input type="date" name="transaction_date" class="form-control" value="{{ today()->toDateString() }}" required></div>
                <div class="col-12"><input name="remarks" class="form-control" placeholder="Remarks"></div>
                <div class="col-12"><button class="btn btn-success"><i class="bi bi-box-arrow-in-left me-1"></i> Return</button></div>
            </form>
            <div class="small text-muted mt-2">Returns cannot exceed what was issued on that work order and stay linked to the original issue.</div>
        </div></div>
    </div>
    <div class="col-12"><div class="card"><div class="card-header">Recent Issues &amp; Returns</div><div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Txn</th><th>Date</th><th>Spare</th><th>Type</th><th class="text-end">Qty</th><th class="text-end">Balance</th><th>Work Order</th><th>By</th></tr></thead>
        <tbody>@foreach($recent as $t)<tr><td class="small">{{ $t->txn_no }}</td><td class="small">{{ show_date($t->transaction_date) }}</td><td>{{ $t->spare->label }}</td><td>{!! status_badge($t->type === 'issue' ? 'danger' : 'success', ucfirst($t->type)) !!}</td><td class="text-end">{{ $t->quantity + 0 }}</td><td class="text-end">{{ $t->balance_after + 0 }}</td><td>@if($t->workOrder)<a href="{{ route('work-orders.show', $t->workOrder) }}">{{ $t->workOrder->wo_no }}</a>@endif</td><td class="small">{{ $t->user->name }}</td></tr>@endforeach</tbody></table></div></div></div>
</div>
@endsection
@push('scripts')
<script>document.querySelectorAll('.spare-select').forEach(function(s){ s.addEventListener('change', function(){ var o=s.options[s.selectedIndex]; s.parentElement.querySelector('.stock-hint').textContent = o.dataset.stock !== undefined ? 'Available: ' + o.dataset.stock + ' ' + o.dataset.uom : ''; }); });</script>
@endpush
