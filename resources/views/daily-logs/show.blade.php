@extends('layouts.app')
@section('title', $log->log_no)
@section('page-title'){{ $log->log_no }} – {{ $log->logType->name }} {!! $log->is_abnormal ? status_badge('danger', 'Abnormal') : '' !!}@endsection
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('daily-logs.index') }}">Daily Logs</a></li><li class="breadcrumb-item active">{{ $log->log_no }}</li>@endsection
@section('page-actions')@can('pm.logs.manage')<a href="{{ route('daily-logs.edit', $log) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i> Edit</a>@endcan@endsection
@section('content')
<div class="row g-3"><div class="col-lg-8">
    <div class="card"><div class="card-body"><dl class="detail-list">
        <dt>Date / Shift</dt><dd>{{ show_date($log->log_date) }} · {{ $log->shift?->name ?? '-' }}</dd>
        <dt>Department</dt><dd>{{ $log->department?->name ?? '-' }}</dd>
        <dt>Equipment</dt><dd>@if($log->asset)<a href="{{ route('assets.show', $log->asset) }}">{{ $log->asset->asset_code }} – {{ $log->asset->name }}</a>@else - @endif</dd>
        <dt>Operator</dt><dd>{{ $log->operator?->name ?? '-' }}</dd>
        @if($log->logType->has_meter_readings)
        <dt>Opening / Closing</dt><dd>{{ $log->opening_reading + 0 }} / {{ $log->closing_reading + 0 }} {{ $log->unit }}</dd>
        <dt>Consumption</dt><dd class="fw-semibold">{{ $log->consumption + 0 }} {{ $log->unit }}</dd>
        @endif
        @foreach($log->logType->fields ?? [] as $f)
        <dt>{{ $f['label'] }}</dt><dd>{{ $log->readings[$f['key']] ?? '-' }} {{ !empty($f['unit']) && isset($log->readings[$f['key']]) ? $f['unit'] : '' }}</dd>
        @endforeach
        <dt>Observations</dt><dd>{{ $log->observations ?? '-' }}</dd>
        <dt>Abnormal Conditions</dt><dd class="{{ $log->abnormal_conditions ? 'text-danger' : '' }}">{{ $log->abnormal_conditions ?? '-' }}</dd>
        <dt>Remarks</dt><dd>{{ $log->remarks ?? '-' }}</dd>
        <dt>Entered By</dt><dd>{{ $log->creator?->name }} · {{ show_datetime($log->created_at) }}</dd>
    </dl>@include('partials.attachments', ['attachments' => $log->attachments])</div></div>
</div>
<div class="col-lg-4"><div class="card"><div class="card-header">Audit</div><div class="card-body">@include('partials.history', ['history' => $log->history])</div></div></div></div>
@endsection
