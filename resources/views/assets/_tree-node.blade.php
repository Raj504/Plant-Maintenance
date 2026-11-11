<li>
    <a href="{{ route('assets.show', $node) }}" class="node">
        <i class="bi {{ $node->children->isNotEmpty() ? 'bi-diagram-2' : 'bi-cpu' }} text-primary"></i>
        <span class="fw-semibold">{{ $node->asset_code }}</span>
        <span>{{ $node->name }}</span>
        {!! status_badge($node->status, \App\Models\Asset::STATUSES[$node->status] ?? null) !!}
        <small class="text-muted">{{ $node->department?->name }}</small>
    </a>
    @if($node->children->isNotEmpty())
    <ul>
        @foreach($node->children as $child)
            @include('assets._tree-node', ['node' => $child])
        @endforeach
    </ul>
    @endif
</li>
