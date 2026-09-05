<?php

namespace App\Models;

class AsScheduleTag extends BaseModel
{
    protected $table = 'as_schedule_tags';

    protected $fillable = [
        'croppingScheduleId',
        'userId',
        'name',
        'deleteStatus',
    ];

    protected $casts = [
        'deleteStatus' => 'integer',
    ];

    public function scopeForSchedule($q, $scheduleId)
    {
        return $q->where('croppingScheduleId', $scheduleId);
    }

    public function links()
    {
        return $this->hasMany(AsScheduleTagLink::class, 'tagId');
    }

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }
}
