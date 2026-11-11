@extends('layouts.app')
@section('title', 'Stock Ledger')
@section('page-title', 'Stock Ledger')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('spares.index') }}">Spares</a></li><li class="breadcrumb-item active">Ledger</li>@endsection
@section('page-actions')@can('pm.stock.issue')<a href="{{ route('stock.issue') }}" class="btn btn-primary btn-sm"><i class="bi bi-arrow-left-right me-1"></i> Issue / Return</a>@endcan @can('pm.reports.view')<a href="{{ route('reports.show', ['report' => 'stock-ledger', 'export' => 'csv']) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i> Export CSV</a>@endcan@endsection
@section('content')
<form class="filter-bar row g-2" method="GET">
    <div class="col-md-3"><select name="spare_id" class="form-select" data-autosubmit><option value="">All spares</option>@foreach($spares as $s)<option value="{{ $s->id }}" @selected(request('spare_id') == $s->id)>{{ $s->code }} - {{ $s->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><select name="type" class="form-select" data-autosubmit><option value="">All types</option>@foreach(\App\Models\StockTransaction::TYPES as $k => $v)<option value="{{ $k }}" @selected(request('type') == $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div>
    <div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div>
    <div class="col-md-2 d-flex gap-1"><button class="btn btn-primary"><i class="bi bi-search"></i></button><a href="{{ route('stock.ledger') }}" class="btn btn-light"><i class="bi bi-x"></i></a></div>
</form>
<div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Txn</th><th>Date</th><th>Spare</th><th>Type</th><th class="text-end">Qty</th><th class="text-end">Unit Cost</th><th class="text-end">Balance</th><th>Work Order / Ref</th><th>By</th><th>Remarks</th></tr></thead>
    <tbody>@forelse($transactions as $t)
    <tr><td class="small">{{ $t->txn_no }}</td><td class="small">{{ show_date($t->transaction_date) }}</td><td><a href="{{ route('spares.show', $t->spare) }}">{{ $t->spare->code }}</a> {{ $t->spare->name }}</td><td>{!! status_badge($t->isInward() ? 'success' : 'danger', \App\Models\StockTransaction::TYPES[$t->type]) !!}</td><td class="text-end {{ $t->isInward() ? 'text-success' : 'text-danger' }}">{{ $t->isInward() ? '+' : '−' }}{{ $t->quantity + 0 }}</td><td class="text-end">{{ money($t->unit_cost) }}</td><td class="text-end fw-semibold">{{ $t->balance_after + 0 }}</td><td class="small">@if($t->workOrder)<a href="{{ route('work-orders.show', $t->workOrder) }}">{{ $t->workOrder->wo_no }}</a>@else{{ $t->reference_no }}@endif</td><td class="small">{{ $t->user->name }}</td><td class="small text-muted">{{ $t->remarks }}</td></tr>
    @empty<tr><td colspan="10">@include('partials.empty', ['message' => 'No stock transactions.'])</td></tr>@endforelse</tbody></table></div>
    @if($transactions->hasPages())<div class="card-footer">{{ $transactions->links() }}</div>@endif
</div>
@endsection
