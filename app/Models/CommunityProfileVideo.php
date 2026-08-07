<?php

namespace App\Models;

/**
 * A video in a member's profile album. Stored already-compressed (≤720p MP4)
 * with an optional poster frame; see App\Support\VideoOptimizer.
 */
class CommunityProfileVideo extends BaseModel
{
    protected $table = 'as_community_profile_videos';

    protected $fillable = [
        'userId',
        'videoPath',
        'posterPath',
        'caption',
        'deleteStatus',
    ];

    protected $casts = [
        'userId' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }
}
