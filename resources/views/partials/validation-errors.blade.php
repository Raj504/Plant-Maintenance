@if($errors->count() > 1)
<div class="alert alert-danger alert-dismissible fade show">
    <strong>Please fix the following:</strong>
    <ul class="mb-0 mt-1">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
