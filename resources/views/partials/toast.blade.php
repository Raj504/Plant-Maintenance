{{-- Toast notifications. Controllers call flash_toast('success', 'Saved!').
     Validation errors are also shown as a toast so the user always gets feedback. --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090">
    @if(session('toast'))
    @php $t = session('toast'); $color = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'primary'][$t['type']] ?? 'primary'; @endphp
    <div class="toast align-items-center text-bg-{{ $color }} border-0 pm-toast" role="alert" data-bs-delay="4500">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi {{ ['success' => 'bi-check-circle', 'error' => 'bi-x-circle', 'warning' => 'bi-exclamation-triangle', 'info' => 'bi-info-circle'][$t['type']] ?? 'bi-info-circle' }} me-2"></i>{{ $t['message'] }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="toast align-items-center text-bg-danger border-0 pm-toast" role="alert" data-bs-delay="6000">
        <div class="d-flex">
            <div class="toast-body"><i class="bi bi-x-circle me-2"></i>{{ $errors->first() }}@if($errors->count() > 1) <small>(+{{ $errors->count() - 1 }} more)</small>@endif</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    @endif
</div>
