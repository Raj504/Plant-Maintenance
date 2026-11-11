{{-- Empty state for tables --}}
<div class="text-center text-muted py-5">
    <i class="bi {{ $icon ?? 'bi-inbox' }} display-6 d-block mb-2 opacity-50"></i>
    {{ $message ?? 'No records found.' }}
</div>
