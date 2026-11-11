@extends('layouts.app')
@section('title', 'New Requisition')
@section('page-title', 'Raise Purchase Requisition')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('purchase-requests.index') }}">Purchase Requisitions</a></li><li class="breadcrumb-item active">New</li>@endsection
@section('content')
<form method="POST" action="{{ route('purchase-requests.store') }}"><div class="row"><div class="col-lg-7"><div class="card"><div class="card-body row g-3">@csrf
    <div class="col-12"><label class="form-label required">Spare Part</label><select name="spare_id" class="form-select" required><option value="">Select…</option>@foreach($spares as $s)<option value="{{ $s->id }}" @selected(old('spare_id', $preset['spare_id'] ?? null) == $s->id)>{{ $s->label }} — stock {{ $s->current_stock + 0 }} {{ $s->uom }} (reorder {{ $s->reorder_level + 0 }})</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label required">Required Quantity</label><input type="number" step="0.01" name="required_qty" class="form-control" value="{{ old('required_qty', $preset['required_qty'] ?? '') }}" required></div>
    <div class="col-md-4"><label class="form-label">Required By</label><input type="date" name="required_by_date" class="form-control" value="{{ old('required_by_date') }}"></div>
    <div class="col-md-4"><label class="form-label">Vendor</label><select name="vendor_id" class="form-select"><option value="">Preferred vendor</option>@foreach($vendors as $v)<option value="{{ $v->id }}" @selected(old('vendor_id') == $v->id)>{{ $v->name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Link Work Order</label><select name="work_order_id" class="form-select"><option value="">-</option>@foreach($workOrders as $w)<option value="{{ $w->id }}" @selected(old('work_order_id', $preset['work_order_id'] ?? null) == $w->id)>{{ $w->wo_no }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Link Maintenance Request</label><select name="request_id" class="form-select"><option value="">-</option>@foreach($requests as $r)<option value="{{ $r->id }}" @selected(old('request_id', $preset['request_id'] ?? null) == $r->id)>{{ $r->request_no }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea></div>
    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-cart-plus me-1"></i> Raise Requisition</button> <a href="{{ route('purchase-requests.index') }}" class="btn btn-light">Cancel</a></div>
</div></div></div></div></form>
@endsection
