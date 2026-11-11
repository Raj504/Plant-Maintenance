<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Central place for file uploads. Every module calls this so file storage,
 * naming and metadata are handled the same way everywhere.
 * Files live in storage/app/public/pm/<folder>/ and are served through the
 * public disk symlink (php artisan storage:link).
 */
class AttachmentService
{
    public const ALLOWED_MIMES = 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,txt';
    public const MAX_KB = 10240; // 10 MB

    /** Validation rule string used by all form requests. */
    public static function rule(): string
    {
        return 'file|mimes:' . self::ALLOWED_MIMES . '|max:' . self::MAX_KB;
    }

    /**
     * Save one uploaded file against a model.
     */
    public static function store(Model $model, UploadedFile $file, string $category = 'general', ?string $title = null): Attachment
    {
        $folder = 'pm/' . strtolower(class_basename($model)) . '/' . $model->getKey();
        $path   = $file->store($folder, 'public');

        return $model->attachments()->create([
            'category'    => $category,
            'title'       => $title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'file_name'   => $file->getClientOriginalName(),
            'file_path'   => $path,
            'mime_type'   => $file->getClientMimeType(),
            'file_size'   => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);
    }

    /**
     * Save many files at once (from <input type="file" multiple name="attachments[]">).
     */
    public static function storeMany(Model $model, ?array $files, string $category = 'general'): void
    {
        foreach ($files ?? [] as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                self::store($model, $file, $category);
            }
        }
    }

    public static function delete(Attachment $attachment): void
    {
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
    }
}
