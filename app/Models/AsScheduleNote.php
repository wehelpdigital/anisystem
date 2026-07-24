<?php

namespace App\Models;

/**
 * A free-form note attached to a cropping schedule — a title, rich-text body
 * and an optional photo. Distinct from per-date activity notes; these are the
 * grower's own running notebook for the plan.
 */
class AsScheduleNote extends BaseModel
{
    protected $table = 'as_schedule_notes';

    protected $fillable = [
        'croppingScheduleId',
        'userId',
        'title',
        'body',
        'imagePath',
        'sortOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'sortOrder' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }
}
