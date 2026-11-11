{{-- Multi-file upload input. Usage: @include('partials.upload-field', ['name' => 'attachments', 'label' => 'Photos / Documents']) --}}
@php $name = $name ?? 'attachments'; $label = $label ?? 'Attachments (photos / documents)'; @endphp
<div class="mb-3">
    <label class="form-label">{{ $label }}</label>
    <input type="file" name="{{ $name }}[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt">
    <div class="form-text">Images, PDF, Word, Excel. Max 10 MB per file. You can select several files.</div>
</div>
