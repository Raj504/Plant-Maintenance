@extends('layouts.app')
@section('title', 'Notifications')
@section('page-title', 'Notifications')
@section('page-actions')
<form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-outline-secondary btn-sm"><i class="bi bi-check2-all me-1"></i> Mark all read</button></form>
@endsection

@section('content')
<div class="card">
    <div class="list-group list-group-flush">
        @forelse($notifications as $n)
        <a href="{{ $n->link ?? '#' }}" class="list-group-item list-group-item-action d-flex gap-3 {{ $n->is_read ? '' : 'bg-light' }}"
           onclick="event.preventDefault(); document.getElementById('nf-{{ $n->id }}').submit();">
            <span class="dot mt-2 bg-{{ $n->level }}"></span>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between">
                    <strong class="{{ $n->is_read ? 'fw-normal' : '' }}">{{ $n->title }}</strong>
                    <small class="text-muted">{{ $n->created_at->diffForHumans() }}</small>
                </div>
                <div class="small text-muted">{{ $n->message }}</div>
            </div>
        </a>
        <form id="nf-{{ $n->id }}" method="POST" action="{{ route('notifications.read', $n) }}" class="d-none">@csrf</form>
        @empty
        @include('partials.empty', ['message' => 'No notifications yet.', 'icon' => 'bi-bell'])
        @endforelse
    </div>
    @if($notifications->hasPages())<div class="card-footer">{{ $notifications->links() }}</div>@endif
</div>
@endsection
