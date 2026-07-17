<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;

class AsScheduleAttachment extends BaseModel
{
    protected $table = 'as_schedule_attachments';

    protected $fillable = [
        'croppingScheduleId',
        'filename',
        'storagePath',
        'mimeType',
        'fileSize',
        'description',
        'sortOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'fileSize'     => 'integer',
        'sortOrder'    => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    /**
     * Public URL for displaying the attachment in the browser. Backed by
     * the `public` disk (storage/app/public + the public/storage
     * symlink). Returns null when the row points at a missing file.
     */
    public function getPublicUrl(): ?string
    {
        if (!$this->storagePath) return null;
        return Storage::disk('public')->url($this->storagePath);
    }

    /**
     * Absolute filesystem path — used by PDF generation to base64-embed
     * the image so the printed document is self-contained.
     */
    public function getAbsolutePath(): ?string
    {
        if (!$this->storagePath) return null;
        $abs = Storage::disk('public')->path($this->storagePath);
        return file_exists($abs) ? $abs : null;
    }

    public function isImage(): bool
    {
        return is_string($this->mimeType) && str_starts_with($this->mimeType, 'image/');
    }
}
