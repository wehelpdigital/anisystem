<?php

namespace App\Models;

/**
 * A photo in a member's profile album (account photos, not wall/plan images).
 */
class CommunityProfilePhoto extends BaseModel
{
    protected $table = 'as_community_profile_photos';

    protected $fillable = [
        'userId',
        'imagePath',
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
