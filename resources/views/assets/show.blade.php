@extends('layouts.app')
@section('title', $asset->asset_code)
@section('page-title'){{ $asset->asset_code }} – {{ $asset->name }} {!! status_badge($asset->status, \App\Models\Asset::STATUSES[$asset->status] ?? null) !!} {!! status_badge($asset->criticality) !!}@endsection
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li><li class="breadcrumb-item active">{{ $asset->asset_code }}</li>@endsection
@section('page-actions')
@can('pm.requests.create')<a href="{{ route('requests.create', ['asset_id' => $asset->id]) }}" class="btn btn-outline-danger btn-sm"><i class="bi bi-exclamation-octagon me-1"></i> Raise Request</a>@endcan
@can('pm.assets.edit')<a href="{{ route('assets.edit', $asset) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>@endcan
@can('pm.assets.delete')<form method="POST" action="{{ route('assets.destroy', $asset) }}" data-confirm="Delete this asset?">@csrf @method('DELETE')<button class="btn btn-outline-secondary btn-sm"><i class="bi bi-trash"></i></button></form>@endcan
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3 text-center">
                        @if($asset->image)
                        <img src="{{ $asset->image_url }}" class="img-fluid rounded mb-2" alt="">
                        @else
                        <div class="bg-light rounded d-flex align-items-center justify-content-center mb-2" style="height:140px"><i class="bi bi-cpu display-4 text-muted"></i></div>
                        @endif
                        <div class="qr-box" id="qrcode" title="Scan to open this asset"></div>
                        <div class="small text-muted mt-1">QR: {{ $asset->asset_code }}</div>
                        <button class="btn btn-sm btn-light mt-1 no-print" onclick="window.print()"><i class="bi bi-printer"></i> Print label</button>
                    </div>
                    <div class="col-md-9">
                        <div class="text-muted small mb-2"><i class="bi bi-diagram-3"></i> {{ $asset->path }}</div>
                        <dl class="detail-list">
                            <dt>Category</dt><dd>{{ $asset->category?->name ?? '-' }}</dd>
                            <dt>Department / Plant</dt><dd>{{ $asset->department?->name ?? '-' }} / {{ $asset->plant?->name ?? '-' }}</dd>
                            <dt>Location</dt><dd>{{ $asset->location ?? '-' }}</dd>
                            <dt>Machine No.</dt><dd>{{ $asset->machine_number ?? '-' }}</dd>
                            <dt>Manufacturer / Model</dt><dd>{{ $asset->manufacturer ?? '-' }} / {{ $asset->model_number ?? '-' }}</dd>
                            <dt>Serial Number</dt><dd>{{ $asset->serial_number ?? '-' }}</dd>
                            <dt>Installed / Commissioned</dt><dd>{{ show_date($asset->installation_date) }} / {{ show_date($asset->commissioning_date) }}</dd>
                            <dt>Warranty Expiry</dt><dd>{{ show_date($asset->warranty_expiry_date) }} @if($asset->warranty_expiry_date && $asset->warranty_expiry_date->isPast())<span class="badge text-bg-secondary">Expired</span>@endif</dd>
                            <dt>Running Hours</dt><dd class="fw-semibold">{{ number_format($asset->current_running_hours, 1) }} hrs</dd>
                            <dt>Expected Life</dt><dd>{{ $asset->expected_life_years ? $asset->expected_life_years . ' years' : '-' }}</dd>
                            <dt>Rated Capacity</dt><dd>{{ $asset->rated_capacity ? number_format($asset->rated_capacity, 2) . ' ' . $asset->capacity_unit . ' per ' . $asset->capacity_hours . ' hrs (' . number_format($asset->ratedPerHour(), 2) . ' ' . $asset->capacity_unit . '/hr)' : '-' }}</dd>
                            <dt>Open Requests</dt><dd>{{ $asset->requests->where('status', '!=', 'closed')->whereNotIn('status', ['cancelled', 'rejected'])->count() }}</dd>
                        </dl>
                        @if($asset->specifications)<div class="small"><strong>Specifications:</strong> {{ $asset->specifications }}</div>@endif
                        @if($asset->remarks)<div class="small text-muted mt-1">{{ $asset->remarks }}</div>@endif
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3 no-print" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-history">Maintenance History</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pm">PM Plans <span class="badge text-bg-light">{{ $asset->pmPlans->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bom">Spare Parts BOM <span class="badge text-bg-light">{{ $asset->bomItems->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-readings">Running Hours</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-capacity">Capacity Log</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs">Documents <span class="badge text-bg-light">{{ $asset->attachments->count() }}</span></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-audit">Audit</button></li>
        </ul>

        <div class="tab-content">
            {{-- Maintenance history --}}
            <div class="tab-pane fade show active" id="tab-history">
                <div class="card">
                    <div class="card-header">Maintenance Requests</div>
                    <div class="table-responsive"><table class="table table-hover mb-0">
                        <thead><tr><th>Request</th><th>Date</th><th>Type</th><th>Priority</th><th>Problem</th><th>Status</th></tr></thead>
                        <tbody>@forelse($asset->requests as $r)
                        <tr><td><a href="{{ route('requests.show', $r) }}">{{ $r->request_no }}</a></td><td>{{ show_date($r->request_date) }}</td><td>{{ \App\Models\MaintenanceRequest::TYPES[$r->request_type] ?? $r->request_type }}</td><td>{!! status_badge($r->priority) !!}</td><td class="small">{{ \Illuminate\Support\Str::limit($r->problem_description, 60) }}</td><td>{!! status_badge($r->status) !!}</td></tr>
                        @empty<tr><td colspan="6" class="text-muted text-center py-3">No requests raised for this asset</td></tr>@endforelse</tbody></table></div>
                </div>
                <div class="card">
                    <div class="card-header">Work Orders</div>
                    <div class="table-responsive"><table class="table table-hover mb-0">
                        <thead><tr><th>WO</th><th>Type</th><th>Technician</th><th>Planned</th><th>Status</th><th class="text-end">Cost</th><th class="text-end">Downtime</th></tr></thead>
                        <tbody>@forelse($asset->workOrders as $w)
                        <tr><td><a href="{{ route('work-orders.show', $w) }}">{{ $w->wo_no }}</a></td><td>{{ status_label($w->work_type) }}</td><td>{{ $w->technician?->name ?? '-' }}</td><td>{{ show_date($w->planned_start) }} – {{ show_date($w->planned_end) }}</td><td>{!! status_badge($w->status) !!}</td><td class="text-end">{{ money($w->actual_cost) }}</td><td class="text-end">{{ $w->downtime_hours }} h</td></tr>
                        @empty<tr><td colspan="7" class="text-muted text-center py-3">No work orders</td></tr>@endforelse</tbody></table></div>
                </div>
                @if($asset->compliances->isNotEmpty() || $asset->amcContracts->isNotEmpty())
                <div class="row g-3">
                    <div class="col-md-6"><div class="card h-100"><div class="card-header">Statutory Compliance</div><ul class="list-group list-group-flush">@foreach($asset->compliances as $c)<li class="list-group-item d-flex justify-content-between"><a href="{{ route('compliances.show', $c) }}">{{ $c->inspection_name }}</a><span>{{ show_date($c->next_due_date) }} {!! status_badge($c->status) !!}</span></li>@endforeach</ul></div></div>
                    <div class="col-md-6"><div class="card h-100"><div class="card-header">AMC Contracts</div><ul class="list-group list-group-flush">@foreach($asset->amcContracts as $a)<li class="list-group-item d-flex justify-content-between"><a href="{{ route('amc.show', $a) }}">{{ $a->amc_no }} – {{ $a->vendor->name }}</a><span>till {{ show_date($a->end_date) }}</span></li>@endforeach</ul></div></div>
                </div>
                @endif
            </div>

            {{-- PM plans --}}
            <div class="tab-pane fade" id="tab-pm">
                <div class="card">
                    <div class="card-header">Preventive Maintenance Plans @can('pm.pm.manage')<a href="{{ route('pm.plans.create', ['asset_id' => $asset->id]) }}" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> Add Plan</a>@endcan</div>
                    <div class="table-responsive"><table class="table table-hover mb-0">
                        <thead><tr><th>Plan</th><th>Trigger</th><th>Interval</th><th>Next Due</th><th>Technician</th><th>Status</th></tr></thead>
                        <tbody>@forelse($asset->pmPlans as $p)
                        <tr><td><a href="{{ route('pm.plans.show', $p) }}">{{ $p->name }}</a><div class="text-muted small">{{ $p->plan_no }}</div></td><td>{{ \App\Models\PmPlan::TRIGGERS[$p->trigger_type] }}</td><td class="small">{{ $p->interval_days ? $p->interval_days . ' days' : '' }} {{ $p->interval_hours ? '/ ' . $p->interval_hours . ' hrs' : '' }}</td><td>{{ show_date($p->next_due_date) }} {{ $p->next_due_hours ? '/ ' . number_format($p->next_due_hours) . ' hrs' : '' }}</td><td>{{ $p->technician?->name ?? '-' }}</td><td>{!! $p->isOverdue() ? status_badge('overdue') : ($p->is_active ? status_badge('active') : status_badge('inactive')) !!}</td></tr>
                        @empty<tr><td colspan="6" class="text-muted text-center py-3">No PM plans</td></tr>@endforelse</tbody></table></div>
                </div>
            </div>

            {{-- BOM --}}
            <div class="tab-pane fade" id="tab-bom">
                <div class="card">
                    <div class="card-header">Bill of Materials (spares used by this machine)</div>
                    <div class="table-responsive"><table class="table table-hover mb-0">
                        <thead><tr><th>Spare</th><th>Qty</th><th>Criticality</th><th class="text-end">In Stock</th><th>Vendor</th><th>Replace every</th><th></th></tr></thead>
                        <tbody>@forelse($asset->bomItems as $b)
                        <tr class="{{ $b->spare->current_stock < $b->quantity ? 'table-warning' : '' }}">
                            <td><a href="{{ route('spares.show', $b->spare) }}">{{ $b->spare->code }}</a> {{ $b->spare->name }}</td><td>{{ $b->quantity + 0 }} {{ $b->unit }}</td><td>{!! status_badge($b->criticality) !!}</td>
                            <td class="text-end">{{ $b->spare->current_stock + 0 }} @if($b->spare->current_stock < $b->quantity)<i class="bi bi-exclamation-triangle text-warning" title="Shortage"></i>@endif</td>
                            <td>{{ $b->preferredVendor?->name ?? '-' }}</td><td>{{ $b->replacement_interval_days ? $b->replacement_interval_days . ' days' : '-' }}</td>
                            <td class="text-end">@can('pm.stock.manage')<form method="POST" action="{{ route('assets.bom.destroy', [$asset, $b]) }}" data-confirm="Remove from BOM?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-x"></i></button></form>@endcan</td>
                        </tr>
                        @empty<tr><td colspan="7" class="text-muted text-center py-3">No BOM defined</td></tr>@endforelse</tbody></table></div>
                    @can('pm.stock.manage')
                    <div class="card-footer">
                        <form method="POST" action="{{ route('assets.bom.store', $asset) }}" class="row g-2 align-items-end">@csrf
                            <div class="col-md-4"><label class="form-label small">Spare</label><select name="spare_id" class="form-select form-select-sm" required><option value="">Select…</option>@foreach($spares as $s)<option value="{{ $s->id }}">{{ $s->label }} (stock {{ $s->current_stock + 0 }})</option>@endforeach</select></div>
                            <div class="col-md-1"><label class="form-label small">Qty</label><input type="number" step="0.01" name="quantity" class="form-control form-control-sm" value="1" required></div>
                            <div class="col-md-2"><label class="form-label small">Criticality</label><select name="criticality" class="form-select form-select-sm">@foreach(\App\Models\Asset::CRITICALITIES as $k => $v)<option value="{{ $k }}" @selected($k == 'medium')>{{ $v }}</option>@endforeach</select></div>
                            <div class="col-md-2"><label class="form-label small">Vendor</label><select name="preferred_vendor_id" class="form-select form-select-sm"><option value="">-</option>@foreach($vendors as $v)<option value="{{ $v->id }}">{{ $v->name }}</option>@endforeach</select></div>
                            <div class="col-md-2"><label class="form-label small">Replace every (days)</label><input type="number" name="replacement_interval_days" class="form-control form-control-sm"></div>
                            <div class="col-md-1"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus"></i></button></div>
                        </form>
                    </div>
                    @endcan
                </div>
            </div>

            {{-- Readings --}}
            <div class="tab-pane fade" id="tab-readings">
                <div class="card">
                    <div class="card-header">Running Hours / Meter Readings</div>
                    @can('pm.assets.readings')
                    <div class="card-body border-bottom">
                        <form method="POST" action="{{ route('assets.readings.store', $asset) }}" class="row g-2 align-items-end">@csrf
                            <div class="col-md-3"><label class="form-label small">Date</label><input type="date" name="reading_date" value="{{ today()->toDateString() }}" class="form-control form-control-sm" required></div>
                            <div class="col-md-2"><label class="form-label small">Type</label><select name="reading_type" class="form-select form-select-sm"><option value="running_hours">Running hours</option><option value="meter">Meter</option></select></div>
                            <div class="col-md-2"><label class="form-label small">Value (cumulative)</label><input type="number" step="0.01" name="value" class="form-control form-control-sm" value="{{ $asset->current_running_hours }}" required></div>
                            <div class="col-md-4"><label class="form-label small">Remarks</label><input name="remarks" class="form-control form-control-sm"></div>
                            <div class="col-md-1"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus"></i></button></div>
                        </form>
                    </div>
                    @endcan
                    <div class="table-responsive"><table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Type</th><th class="text-end">Value</th><th>Recorded by</th><th>Remarks</th></tr></thead>
                        <tbody>@forelse($asset->readings as $r)
                        <tr><td>{{ show_date($r->reading_date) }}</td><td>{{ status_label($r->reading_type) }}</td><td class="text-end fw-semibold">{{ number_format($r->value, 2) }}</td><td>{{ $r->recorder?->name }}</td><td class="small text-muted">{{ $r->remarks }}</td></tr>
                        @empty<tr><td colspan="5" class="text-muted text-center py-3">No readings</td></tr>@endforelse</tbody></table></div>
                </div>
            </div>

            {{-- Capacity --}}
            <div class="tab-pane fade" id="tab-capacity">
                <div class="card">
                    <div class="card-header">Production / Capacity Log <a href="{{ route('reports.show', ['report' => 'machine-capacity', 'asset_id' => $asset->id]) }}" class="small">Capacity report</a></div>
                    @if(!$asset->rated_capacity)
                    <div class="alert alert-warning m-3 mb-0 small">Set the rated capacity on this asset (Edit) to calculate efficiency.</div>
                    @endif
                    @can('pm.assets.readings')
                    <div class="card-body border-bottom">
                        <form method="POST" action="{{ route('assets.capacity.store', $asset) }}" class="row g-2 align-items-end">@csrf
                            <div class="col-md-2"><label class="form-label small">Date</label><input type="date" name="log_date" value="{{ today()->toDateString() }}" class="form-control form-control-sm" required></div>
                            <div class="col-md-2"><label class="form-label small">Shift</label><select name="shift_id" class="form-select form-select-sm"><option value="">-</option>@foreach($shifts as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
                            <div class="col-md-2"><label class="form-label small">Hours Run</label><input type="number" step="0.1" name="hours_run" class="form-control form-control-sm" required placeholder="5"></div>
                            <div class="col-md-2"><label class="form-label small">Output ({{ $asset->capacity_unit ?? 'qty' }})</label><input type="number" step="0.01" name="output_quantity" class="form-control form-control-sm" required placeholder="100"></div>
                            <div class="col-md-3"><label class="form-label small">Operator</label><select name="operator_id" class="form-select form-select-sm"><option value="">-</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
                            <div class="col-md-1"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-plus"></i></button></div>
                        </form>
                    </div>
                    @endcan
                    <div class="table-responsive"><table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Shift</th><th class="text-end">Hours</th><th class="text-end">Expected</th><th class="text-end">Actual</th><th class="text-end">Actual / hr</th><th class="text-end">Efficiency</th><th>Operator</th></tr></thead>
                        <tbody>@forelse($asset->capacityLogs as $c)
                        <tr><td>{{ show_date($c->log_date) }}</td><td>{{ $c->shift?->name }}</td><td class="text-end">{{ $c->hours_run + 0 }}</td><td class="text-end text-muted">{{ $c->expectedOutput() !== null ? number_format($c->expectedOutput(), 1) : '-' }}</td><td class="text-end fw-semibold">{{ $c->output_quantity + 0 }} {{ $c->unit }}</td><td class="text-end">{{ $c->actualPerHour() }}</td>
                            <td class="text-end">@if($c->efficiency() !== null)<span class="badge text-bg-{{ $c->efficiency() >= 90 ? 'success' : ($c->efficiency() >= 70 ? 'warning' : 'danger') }}">{{ $c->efficiency() }}%</span>@else - @endif</td><td>{{ $c->operator?->name }}</td></tr>
                        @empty<tr><td colspan="8" class="text-muted text-center py-3">No production logs</td></tr>@endforelse</tbody></table></div>
                </div>
            </div>

            {{-- Documents --}}
            <div class="tab-pane fade" id="tab-docs">
                <div class="card">
                    <div class="card-header">Documents &amp; Photos</div>
                    <div class="card-body">
                        @can('pm.assets.edit')
                        <form method="POST" action="{{ route('assets.documents.store', $asset) }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-3 border-bottom pb-3">@csrf
                            <div class="col-md-3"><label class="form-label small">Document type</label>
                                <select name="category" class="form-select form-select-sm">@foreach(['manual' => 'Machine Manual', 'drawing' => 'Technical Drawing', 'purchase' => 'Purchase Document', 'warranty' => 'Warranty', 'installation' => 'Installation Report', 'inspection' => 'Inspection Report', 'electrical' => 'Electrical Diagram', 'safety' => 'Safety Document', 'photo' => 'Maintenance Photograph', 'document' => 'Other'] as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                            <div class="col-md-7"><label class="form-label small">Files</label><input type="file" name="attachments[]" class="form-control form-control-sm" multiple required></div>
                            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100"><i class="bi bi-upload me-1"></i> Upload</button></div>
                        </form>
                        @endcan
                        @include('partials.attachments', ['attachments' => $asset->attachments, 'canDelete' => auth()->user()->hasPermission('pm.assets.edit')])
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-audit"><div class="card"><div class="card-body">@include('partials.history', ['history' => $asset->history])</div></div></div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">Components</div>
            <ul class="list-group list-group-flush">
                @forelse($asset->children as $child)
                <li class="list-group-item d-flex justify-content-between align-items-center"><a href="{{ route('assets.show', $child) }}">{{ $child->asset_code }} – {{ $child->name }}</a>{!! status_badge($child->status, \App\Models\Asset::STATUSES[$child->status] ?? null) !!}</li>
                @empty<li class="list-group-item text-muted small">No sub-components. @can('pm.assets.create')<a href="{{ route('assets.create', ['parent_id' => $asset->id]) }}">Add one</a>@endcan</li>@endforelse
            </ul>
        </div>
        <div class="card">
            <div class="card-header">Quick Stats</div>
            <div class="card-body">
                @php $closed = $asset->workOrders->where('status', 'closed'); @endphp
                <dl class="detail-list">
                    <dt>Total Work Orders</dt><dd>{{ $asset->workOrders->count() }}</dd>
                    <dt>Breakdowns</dt><dd>{{ $asset->requests->where('request_type', 'breakdown')->count() }}</dd>
                    <dt>Total Downtime</dt><dd>{{ number_format($closed->sum('downtime_hours'), 1) }} hrs</dd>
                    <dt>Maintenance Cost</dt><dd>{{ money($closed->sum('actual_cost')) }}</dd>
                    <dt>Last Maintenance</dt><dd>{{ show_date($closed->max('closed_at')) }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>new QRCode(document.getElementById('qrcode'), { text: @json(route('assets.scan', $asset->asset_code)), width: 110, height: 110 });</script>
@endpush
