<?php

namespace App\Models;

class AsTutorial extends BaseModel
{
    protected $table = 'as_tutorials';

    protected $fillable = [
        'title', 'category', 'youtubeId', 'coverImagePath',
        'description', 'sortOrder', 'isPublished', 'deleteStatus',
    ];

    protected $casts = [
        'isPublished' => 'boolean',
        'sortOrder' => 'integer',
    ];

    public function scopePublished($q)
    {
        return $q->where('isPublished', 1);
    }

    /** Cover image: uploaded one, else the YouTube thumbnail, else null. */
    public function coverUrl(): ?string
    {
        if ($this->coverImagePath) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->coverImagePath);
        }
        if ($this->youtubeId) {
            return 'https://i.ytimg.com/vi/' . $this->youtubeId . '/hqdefault.jpg';
        }

        return null;
    }
}
