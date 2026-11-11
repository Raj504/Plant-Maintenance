@extends('layouts.app')
@section('title', $checklist->exists ? 'Edit Checklist' : 'New Checklist')
@section('page-title', $checklist->exists ? 'Edit Checklist' : 'New Checklist')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('pm.checklists.index') }}">Checklists</a></li><li class="breadcrumb-item active">{{ $checklist->exists ? 'Edit' : 'New' }}</li>@endsection
@section('content')
<form method="POST" action="{{ $checklist->exists ? route('pm.checklists.update', $checklist) : route('pm.checklists.store') }}">
    @csrf @if($checklist->exists) @method('PUT') @endif
    <div class="row"><div class="col-lg-8">
        <div class="card"><div class="card-body row g-3">
            <div class="col-md-8"><label class="form-label required">Name</label><input name="name" class="form-control" value="{{ old('name', $checklist->name) }}" required></div>
            <div class="col-md-4 d-flex align-items-end"><div class="form-check form-switch"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="act" @checked(old('is_active', $checklist->is_active ?? true))><label class="form-check-label" for="act">Active</label></div></div>
            <div class="col-12"><label class="form-label">Description</label><input name="description" class="form-control" value="{{ old('description', $checklist->description) }}"></div>
            <div class="col-12"><label class="form-label required">Checklist Items (one per line)</label>
                <div id="itemRows">
                    <template><div class="input-group input-group-sm mb-2 repeater-row"><span class="input-group-text">#</span><input name="items[]" class="form-control" placeholder="e.g. Check vibration"><button type="button" class="btn btn-outline-danger" data-repeater-remove><i class="bi bi-x"></i></button></div></template>
                    @foreach(old('items', $checklist->items->pluck('item_text')->all() ?: ['', '', '']) as $item)
                    <div class="input-group input-group-sm mb-2 repeater-row"><span class="input-group-text">{{ $loop->iteration }}</span><input name="items[]" class="form-control" value="{{ $item }}" placeholder="e.g. Check vibration"><button type="button" class="btn btn-outline-danger" data-repeater-remove><i class="bi bi-x"></i></button></div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-repeater-add="#itemRows"><i class="bi bi-plus"></i> Add item</button>
            </div>
        </div></div>
    </div>
    <div class="col-lg-4"><div class="card"><div class="card-body d-grid gap-2"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Checklist</button><a href="{{ route('pm.checklists.index') }}" class="btn btn-light">Cancel</a></div></div></div></div>
</form>
@endsection
