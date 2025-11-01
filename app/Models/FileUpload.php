<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class FileUpload extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uploadable_type',
        'uploadable_id',
        'user_id',
        'original_name',
        'stored_name',
        'file_path',
        'disk',
        'mime_type',
        'extension',
        'size',
        'storage_provider',
        'cloud_file_id',
        'cloud_url',
        'is_public',
        'thumbnail_path',
        'metadata',
    ];

    protected $casts = [
        'size' => 'integer',
        'is_public' => 'boolean',
        'metadata' => 'array',
    ];

    // File size limits (in MB)
    public const MAX_IMAGE_SIZE = 10;
    public const MAX_VIDEO_SIZE = 100;
    public const MAX_DOCUMENT_SIZE = 25;
    public const MAX_FILE_SIZE = 50;

    // Allowed MIME types
    public const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    public const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo', 'video/webm'];
    public const ALLOWED_DOCUMENT_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
    ];

    /**
     * Get the parent uploadable model.
     */
    public function uploadable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the file.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the file URL.
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->cloud_url) {
            return $this->cloud_url;
        }

        if ($this->storage_provider === 'local' || $this->storage_provider === null) {
            return Storage::disk($this->disk)->url($this->file_path);
        }

        return null;
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        
        return number_format($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    /**
     * Check if file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if file is a video.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if file is a document.
     */
    public function isDocument(): bool
    {
        return in_array($this->mime_type, self::ALLOWED_DOCUMENT_TYPES);
    }

    /**
     * Get thumbnail URL.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->thumbnail_path);
    }

    /**
     * Delete file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->storage_provider === 'google_drive' || $this->storage_provider === 'onedrive') {
            // Handle cloud deletion (implement in service)
            return true;
        }

        // Delete from local storage
        if (Storage::disk($this->disk)->exists($this->file_path)) {
            Storage::disk($this->disk)->delete($this->file_path);
        }

        // Delete thumbnail if exists
        if ($this->thumbnail_path && Storage::disk($this->disk)->exists($this->thumbnail_path)) {
            Storage::disk($this->disk)->delete($this->thumbnail_path);
        }

        return true;
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Delete file when model is force deleted
        static::forceDeleting(function ($fileUpload) {
            $fileUpload->deleteFile();
        });
    }
}
