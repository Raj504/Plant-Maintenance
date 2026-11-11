@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Maintenance Dashboard')

@section('page-actions')
<form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
    <input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm" style="width:150px">
    <input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm" style="width:150px">
    <select name="plant_id" class="form-select form-select-sm" style="width:150px">
        <option value="">All plants</option>
        @foreach($plants as $p)<option value="{{ $p->id }}" @selected($plantId == $p->id)>{{ $p->name }}</option>@endforeach
    </select>
    <select name="department_id" class="form-select form-select-sm" style="width:170px">
        <option value="">All departments</option>
        @foreach($departments as $d)<option value="{{ $d->id }}" @selected($departmentId == $d->id)>{{ $d->name }}</option>@endforeach
    </select>
    <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i></button>
</form>
@endsection

@section('content')
@php
$stat = [
    ['Total Assets', $cards['total_assets'], 'bi-cpu', 'primary', route('assets.index')],
    ['Active Machines', $cards['active_assets'], 'bi-check-circle', 'success', route('assets.index', ['status' => 'active'])],
    ['Breakdown Machines', $cards['breakdown_assets'], 'bi-exclamation-triangle', 'danger', route('assets.index', ['status' => 'breakdown'])],
    ['Open Requests', $cards['open_requests'], 'bi-exclamation-octagon', 'info', route('requests.index', ['open' => 1])],
    ['Pending Inspection', $cards['pending_inspection'], 'bi-search', 'warning', route('requests.index', ['status' => 'submitted'])],
    ['Pending Assessment', $cards['pending_assessment'], 'bi-clipboard-data', 'warning', route('requests.index', ['status' => 'assessment_pending'])],
    ['Pending Approval', $cards['pending_approval'], 'bi-hourglass-split', 'warning', route('requests.index', ['status' => 'approval_pending'])],
    ['Open Work Orders', $cards['open_work_orders'], 'bi-clipboard-check', 'primary', route('work-orders.index', ['open' => 1])],
    ['Overdue Work Orders', $cards['overdue_work_orders'], 'bi-alarm', 'danger', route('work-orders.index', ['overdue' => 1])],
    ['PM Due (7 days)', $cards['pm_due'], 'bi-calendar-check', 'info', route('pm.plans.index', ['due' => 'upcoming'])],
    ['PM Overdue', $cards['pm_overdue'], 'bi-calendar-x', 'danger', route('pm.plans.index', ['due' => 'overdue'])],
    ['Low Stock Items', $cards['low_stock'], 'bi-box-seam', 'warning', route('spares.index', ['low' => 1])],
    ['AMC Expiring', $cards['amc_expiring'], 'bi-file-earmark-text', 'warning', route('amc.index', ['expiring' => 1])],
    ['Compliance Due', $cards['compliance_due'], 'bi-patch-check', 'danger', route('compliances.index', ['due' => 1])],
    ['Calibration Overdue', $cards['calibration_overdue'], 'bi-rulers', 'danger', route('calibrations.index', ['status' => 'overdue'])],
];
@endphp
<div class="row g-3 mb-4">
    @foreach($stat as [$label, $value, $icon, $color, $link])
    <div class="col-6 col-md-4 col-xl-2">
        <a href="{{ $link }}" class="stat-card">
            <span class="stat-icon bg-soft-{{ $color }}"><i class="bi {{ $icon }}"></i></span>
            <span>
                <span class="stat-value d-block">{{ $value }}</span>
                <span class="stat-label">{{ $label }}</span>
            </span>
        </a>
    </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">Monthly Breakdowns &amp; Maintenance Cost</div>
            <div class="card-body"><canvas id="monthlyChart" height="110"></canvas></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Work Order Status</div>
            <div class="card-body d-flex align-items-center justify-content-center"><canvas id="woStatusChart" style="max-height:260px"></canvas></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Department-wise Requests</div>
            <div class="card-body"><canvas id="deptChart"></canvas></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Asset-wise Downtime (hrs)</div>
            <div class="card-body"><canvas id="downtimeChart"></canvas></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">Preventive vs Corrective</div>
            <div class="card-body d-flex align-items-center justify-content-center"><canvas id="pmVsChart" style="max-height:240px"></canvas></div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">Spare Parts Consumption</div>
            <div class="card-body"><canvas id="spareChart"></canvas></div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header"><span><i class="bi bi-exclamation-octagon text-danger me-1"></i>Critical Open Requests</span><a href="{{ route('requests.index', ['priority' => 'critical']) }}" class="small">View all</a></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Request</th><th>Asset</th><th>Status</th><th>Age</th></tr></thead>
                    <tbody>
                    @forelse($criticalRequests as $r)
                    <tr class="row-critical"><td><a href="{{ route('requests.show', $r) }}">{{ $r->request_no }}</a></td><td>{{ $r->asset->name }}</td><td>{!! status_badge($r->status) !!}</td><td class="text-muted small">{{ $r->request_date->diffForHumans() }}</td></tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No critical requests open</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header"><span><i class="bi bi-alarm text-danger me-1"></i>Overdue Work Orders</span><a href="{{ route('work-orders.index', ['overdue' => 1]) }}" class="small">View all</a></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>WO</th><th>Asset</th><th>Technician</th><th>Planned End</th></tr></thead>
                    <tbody>
                    @forelse($overdueWorkOrders as $w)
                    <tr><td><a href="{{ route('work-orders.show', $w) }}">{{ $w->wo_no }}</a></td><td>{{ $w->asset->name }}</td><td>{{ $w->technician?->name ?? '-' }}</td><td class="text-danger">{{ show_date($w->planned_end) }}</td></tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">Nothing overdue</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header"><span><i class="bi bi-calendar-check text-primary me-1"></i>Upcoming Preventive Maintenance</span><a href="{{ route('pm.plans.index') }}" class="small">View all</a></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Plan</th><th>Asset</th><th>Due</th><th></th></tr></thead>
                    <tbody>
                    @forelse($upcomingPm as $p)
                    <tr><td><a href="{{ route('pm.plans.show', $p) }}">{{ $p->name }}</a></td><td>{{ $p->asset->name }}</td><td class="{{ $p->next_due_date->isPast() ? 'text-danger fw-semibold' : '' }}">{{ show_date($p->next_due_date) }}</td><td>{!! $p->isOverdue() ? status_badge('overdue') : '' !!}</td></tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No PM plans</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header"><span><i class="bi bi-patch-check text-warning me-1"></i>Compliance Due / Overdue</span><a href="{{ route('compliances.index') }}" class="small">View all</a></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Inspection</th><th>Asset</th><th>Due</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($overdueCompliance as $c)
                    <tr><td><a href="{{ route('compliances.show', $c) }}">{{ $c->inspection_name }}</a></td><td>{{ $c->asset?->name }}</td><td>{{ show_date($c->next_due_date) }}</td><td>{!! status_badge($c->status) !!}</td></tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">All compliance up to date</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const palette = ['#0f4c81', '#f59e0b', '#16a34a', '#dc2626', '#7c3aed', '#0891b2', '#db2777', '#64748b'];
Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
Chart.defaults.font.size = 11;

new Chart(document.getElementById('monthlyChart'), {
    data: {
        labels: @json($monthlyChart['labels']),
        datasets: [
            { type: 'bar', label: 'Breakdowns', data: @json($monthlyChart['breakdowns']), backgroundColor: '#dc2626', yAxisID: 'y', borderRadius: 4 },
            { type: 'line', label: 'Maintenance cost', data: @json($monthlyChart['costs']), borderColor: '#0f4c81', backgroundColor: 'rgba(15,76,129,.1)', fill: true, tension: .3, yAxisID: 'y1' }
        ]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true, title: { display: true, text: 'Count' } }, y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Cost' } } } }
});
new Chart(document.getElementById('woStatusChart'), { type: 'doughnut', data: { labels: @json($woStatus->keys()), datasets: [{ data: @json($woStatus->values()), backgroundColor: palette }] }, options: { plugins: { legend: { position: 'bottom' } } } });
new Chart(document.getElementById('deptChart'), { type: 'bar', data: { labels: @json($deptRequests->keys()), datasets: [{ label: 'Requests', data: @json($deptRequests->values()), backgroundColor: '#0f4c81', borderRadius: 4 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } } });
new Chart(document.getElementById('downtimeChart'), { type: 'bar', data: { labels: @json($downtime->keys()), datasets: [{ label: 'Hours', data: @json($downtime->values()), backgroundColor: '#f59e0b', borderRadius: 4 }] }, options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });
new Chart(document.getElementById('pmVsChart'), { type: 'pie', data: { labels: @json(array_keys($pmVsCorrective)), datasets: [{ data: @json(array_values($pmVsCorrective)), backgroundColor: ['#16a34a', '#dc2626'] }] }, options: { plugins: { legend: { position: 'bottom' } } } });
new Chart(document.getElementById('spareChart'), { type: 'bar', data: { labels: @json($spareConsumption->keys()), datasets: [{ label: 'Qty issued', data: @json($spareConsumption->values()), backgroundColor: '#7c3aed', borderRadius: 4 }] }, options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } } });
</script>
@endpush
