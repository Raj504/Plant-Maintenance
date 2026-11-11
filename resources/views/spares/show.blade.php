@extends('layouts.app')
@section('title', $spare->code)
@section('page-title'){{ $spare->code }} – {{ $spare->name }} {!! $spare->isLowStock() ? status_badge('warning', 'Low Stock') : '' !!} {!! $spare->is_active ? '' : status_badge('inactive') !!}@endsection
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('spares.index') }}">Spares</a></li><li class="breadcrumb-item active">{{ $spare->code }}</li>@endsection
@section('page-actions')
@can('pm.purchase.manage')<a href="{{ route('purchase-requests.create', ['spare_id' => $spare->id]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-cart-plus me-1"></i> Raise Purchase Requisition</a>@endcan
@can('pm.stock.manage')<a href="{{ route('spares.edit', $spare) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>@endcan
@endsection
@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card"><div class="card-body"><div class="row g-3">
            <div class="col-md-3 text-center">@if($spare->image)<img src="{{ $spare->image_url }}" class="img-fluid rounded">@else<div class="bg-light rounded d-flex align-items-center justify-content-center" style="height:120px"><i class="bi bi-box-seam display-5 text-muted"></i></div>@endif</div>
            <div class="col-md-9">
                <div class="d-flex gap-4 mb-3">
                    <div><div class="stat-label">Current Stock</div><div class="stat-value {{ $spare->isLowStock() ? 'text-danger' : 'text-success' }}">{{ $spare->current_stock + 0 }} <small class="fs-6 text-muted">{{ $spare->uom }}</small></div></div>
                    <div><div class="stat-label">Reorder Level</div><div class="stat-value">{{ $spare->reorder_level + 0 }}</div></div>
                    <div><div class="stat-label">Min Stock</div><div class="stat-value">{{ $spare->min_stock + 0 }}</div></div>
                    <div><div class="stat-label">Stock Value</div><div class="stat-value">{{ money($spare->current_stock * $spare->unit_cost) }}</div></div>
                </div>
                <dl class="detail-list"><dt>Category</dt><dd>{{ $spare->category?->name ?? '-' }}</dd><dt>Unit Cost</dt><dd>{{ money($spare->unit_cost) }}</dd><dt>Location</dt><dd>{{ $spare->store_location ?? '-' }}</dd><dt>Preferred Vendor</dt><dd>{{ $spare->preferredVendor?->name ?? '-' }}</dd></dl>
                @if($spare->description)<p class="small mb-0">{{ $spare->description }}</p>@endif
            </div>
        </div></div></div>

        <div class="card"><div class="card-header">Stock Ledger</div><div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Txn</th><th>Date</th><th>Type</th><th class="text-end">In</th><th class="text-end">Out</th><th class="text-end">Balance</th><th>Reference</th><th>By</th><th>Remarks</th></tr></thead>
            <tbody>@forelse($transactions as $t)
            <tr><td class="small">{{ $t->txn_no }}</td><td class="small">{{ show_date($t->transaction_date) }}</td><td>{!! status_badge($t->isInward() ? 'success' : 'danger', \App\Models\StockTransaction::TYPES[$t->type]) !!}</td><td class="text-end text-success">{{ $t->isInward() ? $t->quantity + 0 : '' }}</td><td class="text-end text-danger">{{ !$t->isInward() ? $t->quantity + 0 : '' }}</td><td class="text-end fw-semibold">{{ $t->balance_after + 0 }}</td><td class="small">@if($t->workOrder)<a href="{{ route('work-orders.show', $t->workOrder) }}">{{ $t->workOrder->wo_no }}</a>@else{{ $t->reference_no }}@endif</td><td class="small">{{ $t->user->name }}</td><td class="small text-muted">{{ $t->remarks }}</td></tr>
            @empty<tr><td colspan="9" class="text-muted small text-center py-3">No transactions</td></tr>@endforelse</tbody></table></div>
            @if($transactions->hasPages())<div class="card-footer">{{ $transactions->links() }}</div>@endif
        </div>
    </div>
    <div class="col-xl-4">
        @can('pm.stock.receive')
        <div class="card"><div class="card-header">Receive Stock</div><div class="card-body"><form method="POST" action="{{ route('stock.receive') }}">@csrf<input type="hidden" name="spare_id" value="{{ $spare->id }}">
            <div class="row g-2">
                <div class="col-6"><label class="form-label small">Type</label><select name="type" class="form-select form-select-sm"><option value="receipt">Receipt</option><option value="opening">Opening Stock</option></select></div>
                <div class="col-6"><label class="form-label small">Date</label><input type="date" name="transaction_date" class="form-control form-control-sm" value="{{ today()->toDateString() }}" required></div>
                <div class="col-6"><label class="form-label small">Quantity</label><input type="number" step="0.01" name="quantity" class="form-control form-control-sm" required></div>
                <div class="col-6"><label class="form-label small">Unit Cost</label><input type="number" step="0.01" name="unit_cost" class="form-control form-control-sm" value="{{ $spare->unit_cost }}"></div>
                <div class="col-12"><label class="form-label small">Reference (PO / Invoice / PR)</label><input name="reference_no" class="form-control form-control-sm"></div>
                <div class="col-12"><input name="remarks" class="form-control form-control-sm" placeholder="Remarks"></div>
                <div class="col-12"><button class="btn btn-success btn-sm w-100"><i class="bi bi-box-arrow-in-down me-1"></i> Receive</button></div>
            </div></form></div></div>
        @endcan
        @can('pm.stock.adjust')
        <div class="card"><div class="card-header">Stock Adjustment</div><div class="card-body"><form method="POST" action="{{ route('stock.adjust') }}">@csrf<input type="hidden" name="spare_id" value="{{ $spare->id }}">
            <div class="row g-2">
                <div class="col-6"><label class="form-label small">Type</label><select name="type" class="form-select form-select-sm"><option value="adjustment_in">Adjustment In (+)</option><option value="adjustment_out">Adjustment Out (−)</option></select></div>
                <div class="col-6"><label class="form-label small">Date</label><input type="date" name="transaction_date" class="form-control form-control-sm" value="{{ today()->toDateString() }}" required></div>
                <div class="col-6"><label class="form-label small">Quantity</label><input type="number" step="0.01" name="quantity" class="form-control form-control-sm" required></div>
                <div class="col-6"><label class="form-label small">Reference</label><input name="reference_no" class="form-control form-control-sm" placeholder="Stock count"></div>
                <div class="col-12"><input name="remarks" class="form-control form-control-sm" placeholder="Reason (mandatory, audited)" required></div>
                <div class="col-12"><button class="btn btn-outline-dark btn-sm w-100"><i class="bi bi-sliders me-1"></i> Adjust</button></div>
            </div></form></div></div>
        @endcan
        <div class="card"><div class="card-header">Used On (BOM)</div><ul class="list-group list-group-flush">@forelse($spare->bomItems as $b)<li class="list-group-item small d-flex justify-content-between"><a href="{{ route('assets.show', $b->asset) }}">{{ $b->asset->asset_code }} – {{ $b->asset->name }}</a><span>{{ $b->quantity + 0 }} {{ $b->unit }}</span></li>@empty<li class="list-group-item text-muted small">Not in any BOM</li>@endforelse</ul></div>
        @if($spare->purchaseRequests->isNotEmpty())<div class="card"><div class="card-header">Purchase Requisitions</div><ul class="list-group list-group-flush">@foreach($spare->purchaseRequests->take(5) as $pr)<li class="list-group-item small d-flex justify-content-between"><a href="{{ route('purchase-requests.show', $pr) }}">{{ $pr->pr_no }}</a><span>{{ $pr->required_qty + 0 }} {!! status_badge($pr->status) !!}</span></li>@endforeach</ul></div>@endif
    </div>
</div>
@endsection
