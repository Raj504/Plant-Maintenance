@extends('layouts.app')
@section('title', $title)
@section('page-title', $title)
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li><li class="breadcrumb-item active">{{ $title }}</li>@endsection
@section('page-actions')
<a href="{{ route('reports.show', ['report' => $report] + request()->query() + ['export' => 'csv']) }}" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i> Excel / CSV</a>
<a href="{{ route('reports.show', ['report' => $report] + request()->query() + ['export' => 'pdf']) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
<button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
@endsection
@section('content')
<p class="text-muted small no-print">{{ $description }}</p>
<form class="filter-bar row g-2 align-items-end no-print" method="GET">
    <div class="col-md-2"><label class="form-label small">From</label><input type="date" name="from" value="{{ $filters['from'] }}" class="form-control"></div>
    <div class="col-md-2"><label class="form-label small">To</label><input type="date" name="to" value="{{ $filters['to'] }}" class="form-control"></div>
    <div class="col-md-3"><label class="form-label small">Department</label><select name="department_id" class="form-select"><option value="">All</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected($filters['department_id'] == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label small">Asset</label><select name="asset_id" class="form-select"><option value="">All</option>@foreach($assets as $a)<option value="{{ $a->id }}" @selected($filters['asset_id'] == $a->id)>{{ $a->asset_code }} - {{ $a->name }}</option>@endforeach</select></div>
    <div class="col-md-2 d-flex gap-1"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Apply</button><a href="{{ route('reports.show', $report) }}" class="btn btn-light"><i class="bi bi-x"></i></a></div>
</form>

@if($report === 'machine-capacity')
<div class="alert alert-light border small"><strong>How to read:</strong> Rated / hr = rated capacity ÷ rated hours (200 kg / 8 h = 25 kg/h). Expected output = rated/hr × hours run (25 × 5 = 125 kg). Actual / hr = actual output ÷ hours run (100 ÷ 5 = 20 kg/h). Efficiency = actual ÷ expected (100 ÷ 125 = <strong>80%</strong>). The totals block shows the overall output capacity per machine for the period.</div>
@endif

<div class="card">
    <div class="card-header"><span>{{ $title }} <small class="text-muted fw-normal">{{ show_date($filters['from']) }} – {{ show_date($filters['to']) }}</small></span><span class="badge text-bg-light border">{{ count($rows) }} rows</span></div>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead><tr>@foreach($columns as $key => $label)<th class="{{ in_array($key, ['estimated', 'approved', 'actual_cost', 'cost', 'value', 'stock', 'in', 'out', 'balance', 'qty', 'hours', 'downtime', 'labour', 'expected', 'actual', 'rated_hr', 'actual_hr', 'efficiency']) ? 'text-end' : '' }}">{{ $label }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse($rows as $row)
        <tr>@foreach($columns as $key => $label)@php $v = $row[$key] ?? ''; @endphp<td class="{{ is_numeric($v) ? 'text-end' : '' }}">{{ is_float($v) || (is_numeric($v) && str_contains((string) $v, '.')) ? number_format((float) $v, 2) : $v }}</td>@endforeach</tr>
        @empty
        <tr><td colspan="{{ count($columns) }}">@include('partials.empty', ['message' => 'No data for the selected filters.'])</td></tr>
        @endforelse
        </tbody>
        @if($totals)
        <tfoot class="table-light fw-semibold">@foreach($totals as $row)<tr>@foreach($columns as $key => $label)@php $v = $row[$key] ?? ''; @endphp<td class="{{ is_numeric($v) ? 'text-end' : '' }}">{{ is_float($v) || (is_numeric($v) && str_contains((string) $v, '.')) ? number_format((float) $v, 2) : $v }}</td>@endforeach</tr>@endforeach</tfoot>
        @endif
    </table></div>
</div>
@endsection
