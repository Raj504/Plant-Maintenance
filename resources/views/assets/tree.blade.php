@extends('layouts.app')
@section('title', 'Asset Hierarchy')
@section('page-title', 'Asset Hierarchy')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('assets.index') }}">Assets</a></li><li class="breadcrumb-item active">Hierarchy</li>@endsection
@section('page-actions')@can('pm.assets.create')<a href="{{ route('assets.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> New Asset</a>@endcan@endsection

@section('content')
<div class="card">
    <div class="card-body asset-tree">
        @if($roots->isEmpty())
        @include('partials.empty', ['message' => 'No assets yet.'])
        @else
        <ul>
            @foreach($roots as $node)
                @include('assets._tree-node', ['node' => $node])
            @endforeach
        </ul>
        @endif
    </div>
</div>
@endsection
