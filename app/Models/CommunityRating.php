<?php

namespace App\Models;

/**
 * One member's 1-5 rating of a published cropping plan, with an optional short
 * review. Re-rating updates the existing row rather than adding another.
 */
class CommunityRating extends BaseModel
{
    protected $table = 'as_community_ratings';

    protected $fillable = [
        'croppingScheduleId',
        'anisystemUserId',
        'rating',
        'review',
        'deleteStatus',
    ];

    protected $casts = [
        'rating' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'anisystemUserId');
    }

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }
}
