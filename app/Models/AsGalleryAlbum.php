<?php

namespace App\Models;

/** A named collection of pictures inside one schedule. */
class AsGalleryAlbum extends BaseModel
{
    protected $table = 'as_gallery_albums';

    protected $fillable = ['croppingScheduleId', 'userId', 'title', 'description', 'sortOrder', 'deleteStatus'];

    protected $casts = [
        'croppingScheduleId' => 'integer',
        'userId' => 'integer',
        'sortOrder' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function images()
    {
        return $this->hasMany(AsGalleryImage::class, 'albumId')
            ->where('deleteStatus', 1)
            ->orderBy('sortOrder')
            ->orderByDesc('id');
    }
}
