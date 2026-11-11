@extends('layouts.app')
@section('title', 'Spare Parts')
@section('page-title', 'Spare Parts Master')
@section('breadcrumb')<li class="breadcrumb-item active">Spares</li>@endsection
@section('page-actions')
<a href="{{ route('stock.ledger') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-journal-text me-1"></i> Ledger</a>
@can('pm.stock.manage')<a href="{{ route('spares.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Spare</a>@endcan
@endsection
@section('content')
<form class="filter-bar row g-2" method="GET">
    <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Code / name"></div>
    <div class="col-md-3"><select name="category_id" class="form-select" data-autosubmit><option value="">All categories</option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><div class="form-check mt-2"><input type="checkbox" name="low" value="1" class="form-check-input" id="low" @checked(request('low')) data-autosubmit><label class="form-check-label" for="low">Low stock only</label></div></div>
    <div class="col-md-2 d-flex gap-1"><button class="btn btn-primary"><i class="bi bi-search"></i></button><a href="{{ route('spares.index') }}" class="btn btn-light"><i class="bi bi-x"></i></a></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Code</th><th>Spare Part</th><th>Category</th><th>UoM</th><th class="text-end">Stock</th><th class="text-end">Min / Reorder</th><th class="text-end">Unit Cost</th><th>Location</th><th>Vendor</th><th></th></tr></thead>
    <tbody>@forelse($spares as $s)
    <tr class="{{ $s->isLowStock() ? 'table-warning' : '' }}">
        <td><a href="{{ route('spares.show', $s) }}" class="fw-semibold">{{ $s->code }}</a></td>
        <td><div class="d-flex align-items-center gap-2">@if($s->image)<img src="{{ $s->image_url }}" class="rounded" style="width:30px;height:30px;object-fit:cover">@endif {{ $s->name }}</div></td>
        <td class="small">{{ $s->category?->name }}</td><td>{{ $s->uom }}</td>
        <td class="text-end fw-semibold">{{ $s->current_stock + 0 }} @if($s->isLowStock())<i class="bi bi-exclamation-triangle text-warning" title="At or below reorder level"></i>@endif</td>
        <td class="text-end small">{{ $s->min_stock + 0 }} / {{ $s->reorder_level + 0 }}</td><td class="text-end">{{ money($s->unit_cost) }}</td><td class="small">{{ $s->store_location }}</td><td class="small">{{ $s->preferredVendor?->name }}</td>
        <td class="text-end text-nowrap"><a href="{{ route('spares.show', $s) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>@can('pm.stock.manage')<a href="{{ route('spares.edit', $s) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>@endcan</td>
    </tr>
    @empty<tr><td colspan="10">@include('partials.empty', ['message' => 'No spare parts.'])</td></tr>@endforelse</tbody></table></div>
    @if($spares->hasPages())<div class="card-footer">{{ $spares->links() }}</div>@endif
</div>
@endsection
