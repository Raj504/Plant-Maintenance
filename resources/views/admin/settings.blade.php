@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Module Settings')
@section('breadcrumb')<li class="breadcrumb-item active">Settings</li>@endsection
@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}">@csrf @method('PUT')
<div class="row g-3">
    @foreach($settings as $group => $items)
    <div class="col-lg-6"><div class="card h-100"><div class="card-header text-capitalize">{{ $group }}</div><div class="card-body">
        @foreach($items as $s)
        <div class="mb-3"><label class="form-label">{{ $s->label ?? $s->key }} <small class="text-muted">({{ $s->key }})</small></label><input name="settings[{{ $s->key }}]" class="form-control" value="{{ old('settings.' . $s->key, $s->value) }}"></div>
        @endforeach
    </div></div></div>
    @endforeach
</div>
<button class="btn btn-primary mt-3"><i class="bi bi-save me-1"></i> Save Settings</button>
</form>
@endsection
