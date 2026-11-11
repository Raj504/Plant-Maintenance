@extends('layouts.app')
@section('title', 'Assets')
@section('page-title', 'Asset Registry')
@section('breadcrumb')<li class="breadcrumb-item active">Assets</li>@endsection
@section('page-actions')
<a href="{{ route('assets.tree') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-diagram-3 me-1"></i> Hierarchy</a>
@can('pm.assets.create')<a href="{{ route('assets.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Asset</a>@endcan
@endsection

@section('content')
<form class="filter-bar row g-2 align-items-end" method="GET">
    <div class="col-md-3"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search code, name, serial…"></div>
    <div class="col-md-2">
        <select name="status" class="form-select" data-autosubmit><option value="">All statuses</option>
            @foreach(\App\Models\Asset::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') == $k)>{{ $v }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="criticality" class="form-select" data-autosubmit><option value="">All criticality</option>
            @foreach(\App\Models\Asset::CRITICALITIES as $k => $v)<option value="{{ $k }}" @selected(request('criticality') == $k)>{{ $v }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="department_id" class="form-select" data-autosubmit><option value="">All departments</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select name="category_id" class="form-select" data-autosubmit><option value="">All categories</option>
            @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-1 d-flex gap-1"><button class="btn btn-primary w-100"><i class="bi bi-search"></i></button><a href="{{ route('assets.index') }}" class="btn btn-light" title="Reset"><i class="bi bi-x"></i></a></div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Code</th><th>Asset</th><th>Category</th><th>Department</th><th>Parent</th><th>Criticality</th><th>Status</th><th class="text-end">Run Hrs</th><th></th></tr></thead>
            <tbody>
            @forelse($assets as $asset)
            <tr class="row-{{ $asset->criticality }}">
                <td><a href="{{ route('assets.show', $asset) }}" class="fw-semibold">{{ $asset->asset_code }}</a></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        @if($asset->image)<img src="{{ $asset->image_url }}" class="rounded" style="width:34px;height:34px;object-fit:cover">@endif
                        <div>{{ $asset->name }}<div class="text-muted small">{{ $asset->machine_number }} {{ $asset->location ? '· ' . $asset->location : '' }}</div></div>
                    </div>
                </td>
                <td>{{ $asset->category?->name }}</td>
                <td>{{ $asset->department?->name }}</td>
                <td class="small">{{ $asset->parent?->name }}</td>
                <td>{!! status_badge($asset->criticality) !!}</td>
                <td>{!! status_badge($asset->status, \App\Models\Asset::STATUSES[$asset->status] ?? null) !!}</td>
                <td class="text-end">{{ number_format($asset->current_running_hours, 0) }}</td>
                <td class="text-end text-nowrap">
                    <a href="{{ route('assets.show', $asset) }}" class="btn btn-sm btn-light" title="View"><i class="bi bi-eye"></i></a>
                    @can('pm.assets.edit')<a href="{{ route('assets.edit', $asset) }}" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>@endcan
                </td>
            </tr>
            @empty
            <tr><td colspan="9">@include('partials.empty', ['message' => 'No assets found. Create your first asset.'])</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($assets->hasPages())<div class="card-footer d-flex justify-content-between align-items-center"><small class="text-muted">{{ $assets->total() }} assets</small>{{ $assets->links() }}</div>@endif
</div>
@endsection
