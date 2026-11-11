{{-- Audit trail timeline. Usage: @include('partials.history', ['history' => $model->history]) --}}
@if($history->isEmpty())
<p class="text-muted small mb-0">No history yet.</p>
@else
<ul class="timeline">
    @foreach($history as $h)
    <li class="timeline-item">
        <span class="timeline-dot bg-{{ status_color($h->to_status ?? $h->action) }}"></span>
        <div class="timeline-body">
            <div class="d-flex justify-content-between flex-wrap gap-1">
                <strong class="small">{{ status_label($h->action) }}</strong>
                <span class="text-muted small">{{ show_datetime($h->created_at) }}</span>
            </div>
            @if($h->from_status || $h->to_status)
            <div class="small">{!! $h->from_status ? status_badge($h->from_status) : '' !!} {{ $h->from_status ? '→' : '' }} {!! $h->to_status ? status_badge($h->to_status) : '' !!}</div>
            @endif
            @if($h->remarks)<div class="small mt-1">{{ $h->remarks }}</div>@endif
            @if($h->changes)
            <details class="small mt-1"><summary class="text-muted">Changed fields</summary>
                <ul class="mb-0">
                    @foreach($h->changes as $field => $c)
                    <li><strong>{{ status_label($field) }}</strong>: {{ is_array($c) ? (($c['old'] ?? '-') . ' → ' . ($c['new'] ?? '-')) : $c }}</li>
                    @endforeach
                </ul>
            </details>
            @endif
            <div class="text-muted" style="font-size:.72rem"><i class="bi bi-person"></i> {{ $h->user?->name ?? 'System' }}</div>
        </div>
    </li>
    @endforeach
</ul>
@endif
