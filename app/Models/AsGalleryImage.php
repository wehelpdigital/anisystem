<?php

namespace App\Models;

/** One picture in an album. It belongs to exactly one, so moving it is a field. */
class AsGalleryImage extends BaseModel
{
    protected $table = 'as_gallery_images';

    protected $fillable = ['albumId', 'croppingScheduleId', 'userId', 'path', 'caption', 'description', 'isTeam', 'sortOrder', 'deleteStatus'];

    protected $casts = [
        'albumId' => 'integer',
        'croppingScheduleId' => 'integer',
        'userId' => 'integer',
        'sortOrder' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function album()
    {
        return $this->belongsTo(AsGalleryAlbum::class, 'albumId');
    }

    public function url(): ?string
    {
        return \App\Support\MediaStore::url($this->path);
    }
}
