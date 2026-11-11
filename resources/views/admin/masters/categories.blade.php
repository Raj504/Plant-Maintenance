@extends('layouts.app')
@section('title', 'Categories')
@section('page-title', 'Masters')
@section('breadcrumb')<li class="breadcrumb-item active">Masters</li>@endsection
@section('content')
@include('admin.masters._nav')
<ul class="nav nav-tabs mb-3">@foreach(\App\Models\Category::TYPES as $k => $label)<li class="nav-item"><a class="nav-link {{ $type == $k ? 'active' : '' }}" href="{{ route('admin.categories.index', ['type' => $k]) }}">{{ $label }}</a></li>@endforeach</ul>
<div class="row g-3"><div class="col-lg-8"><div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead><tr><th>Name</th><th>Description</th><th>Status</th><th></th></tr></thead>
    <tbody>@forelse($categories as $c)<tr><td class="fw-semibold">{{ $c->name }}</td><td class="small">{{ $c->description }}</td><td>{!! status_badge($c->is_active ? 'active' : 'inactive') !!}</td><td class="text-end text-nowrap"><a href="{{ route('admin.categories.edit', $c) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>@if($c->is_active)<form method="POST" action="{{ route('admin.categories.destroy', $c) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger" title="Deactivate"><i class="bi bi-x-circle"></i></button></form>@endif</td></tr>@empty<tr><td colspan="4">@include('partials.empty')</td></tr>@endforelse</tbody></table></div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-header">{{ $category->exists ? 'Edit' : 'Add' }} {{ \App\Models\Category::TYPES[$type] }}</div><div class="card-body"><form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">@csrf @if($category->exists) @method('PUT') @endif
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="mb-2"><label class="form-label required">Name</label><input name="name" class="form-control" value="{{ old('name', $category->name) }}" required></div>
    <div class="mb-2"><label class="form-label">Description</label><input name="description" class="form-control" value="{{ old('description', $category->description) }}"></div>
    <div class="form-check form-switch mb-3"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="act" @checked(old('is_active', $category->is_active ?? true))><label class="form-check-label" for="act">Active</label></div>
    <button class="btn btn-primary w-100">Save</button>@if($category->exists)<a href="{{ route('admin.categories.index', ['type' => $type]) }}" class="btn btn-light w-100 mt-1">Cancel</a>@endif</form></div></div></div></div>
@endsection
