@extends('layouts.app')
@section('title', 'Reports')
@section('page-title', 'Reports & Analytics')
@section('breadcrumb')<li class="breadcrumb-item active">Reports</li>@endsection
@section('content')
<div class="row g-3">
    @foreach($grouped as $group => $reports)
    <div class="col-xl-4 col-md-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi {{ ['Production' => 'bi-speedometer', 'Maintenance' => 'bi-tools', 'Preventive Maintenance' => 'bi-calendar-check', 'Downtime' => 'bi-alarm', 'Inventory' => 'bi-box-seam', 'Compliance' => 'bi-patch-check'][$group] ?? 'bi-bar-chart' }} me-1"></i>{{ $group }}</div>
            <div class="list-group list-group-flush">
                @foreach($reports as $r)
                <a href="{{ route('reports.show', $r['key']) }}" class="list-group-item list-group-item-action">
                    <div class="fw-semibold small">{{ $r['title'] }}</div>
                    <div class="text-muted" style="font-size:.75rem">{{ $r['description'] }}</div>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
