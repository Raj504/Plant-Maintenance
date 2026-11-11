{{-- Reusable list of uploaded files.
     Usage: @include('partials.attachments', ['attachments' => $model->attachments, 'canDelete' => true]) --}}
@php $canDelete = $canDelete ?? false; @endphp
@if($attachments->isEmpty())
<p class="text-muted small mb-0">No files uploaded.</p>
@else
<div class="row g-2">
    @foreach($attachments as $file)
    <div class="col-6 col-md-4 col-xl-3">
        <div class="attachment-card">
            @if($file->is_image)
            <a href="{{ $file->url }}" target="_blank" class="attachment-thumb"><img src="{{ $file->url }}" alt="{{ $file->title }}"></a>
            @else
            <a href="{{ route('attachments.download', $file) }}" class="attachment-thumb attachment-file"><i class="bi bi-file-earmark-text"></i></a>
            @endif
            <div class="attachment-meta">
                <div class="text-truncate small fw-semibold" title="{{ $file->file_name }}">{{ $file->title ?? $file->file_name }}</div>
                <div class="text-muted" style="font-size:.7rem">{{ status_label($file->category) }} · {{ $file->human_size }} · {{ $file->uploader?->name }}</div>
            </div>
            <div class="attachment-actions">
                <a href="{{ route('attachments.download', $file) }}" class="btn btn-sm btn-light" title="Download"><i class="bi bi-download"></i></a>
                @if($canDelete)
                <form method="POST" action="{{ route('attachments.destroy', $file) }}" data-confirm="Delete this file?">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-light text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
