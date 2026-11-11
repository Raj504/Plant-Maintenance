@extends('layouts.app')
@section('title', $handover->handover_no)
@section('page-title'){{ $handover->handover_no }} {!! status_badge($handover->status) !!}@endsection
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('handovers.index') }}">Shift Handover</a></li><li class="breadcrumb-item active">{{ $handover->handover_no }}</li>@endsection
@section('page-actions')<button class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>@endsection
@section('content')
<div class="row g-3"><div class="col-lg-8">
    <div class="card"><div class="card-body">
        <dl class="detail-list">
            <dt>Date</dt><dd>{{ show_date($handover->handover_date) }}</dd>
            <dt>Shift</dt><dd>{{ $handover->fromShift->name }} → {{ $handover->toShift->name }}</dd>
            <dt>Department</dt><dd>{{ $handover->department?->name ?? '-' }}</dd>
            <dt>Handed Over By</dt><dd>{{ $handover->handedOverBy->name }} · {{ show_datetime($handover->created_at) }}</dd>
            <dt>Received By</dt><dd>{{ $handover->receivedBy?->name ?? '-' }} {{ $handover->acknowledged_at ? '· ' . show_datetime($handover->acknowledged_at) : '' }}</dd>
        </dl>
        @foreach(['pending_work_orders' => 'Pending Work Orders', 'breakdown_status' => 'Breakdown Status', 'machines_under_observation' => 'Machines Under Observation', 'temporary_repairs' => 'Temporary Repairs', 'safety_issues' => 'Safety Issues', 'spare_shortage' => 'Spare Parts Shortage', 'instructions' => 'Important Instructions', 'remarks' => 'Remarks'] as $field => $label)
        @if($handover->$field)<div class="mb-2"><strong class="small text-muted text-uppercase">{{ $label }}</strong><div class="border rounded p-2 bg-light small" style="white-space:pre-line">{{ $handover->$field }}</div></div>@endif
        @endforeach
        @if($handover->acknowledgement_remarks)<div class="alert alert-success small mb-0"><strong>Acknowledgement:</strong> {{ $handover->acknowledgement_remarks }}</div>@endif
    </div></div>
    @can('pm.handover.manage')
    @if($handover->status === 'pending' && $handover->handed_over_by !== auth()->id())
    <div class="card border-success"><div class="card-header bg-success text-white">Acknowledge Handover</div><div class="card-body"><form method="POST" action="{{ route('handovers.acknowledge', $handover) }}" class="row g-2">@csrf
        <div class="col-md-9"><input name="acknowledgement_remarks" class="form-control" placeholder="Remarks (optional)"></div>
        <div class="col-md-3"><button class="btn btn-success w-100"><i class="bi bi-check2 me-1"></i> I have received</button></div></form></div></div>
    @endif
    @endcan
</div>
<div class="col-lg-4"><div class="card"><div class="card-header">History</div><div class="card-body">@include('partials.history', ['history' => $handover->history])</div></div></div></div>
@endsection
