<?php

namespace App\Models;

class AsScheduleDateNote extends BaseModel
{
    protected $table = 'as_schedule_date_notes';

    protected $fillable = [
        'croppingScheduleId',
        'versionId',
        'noteDate',
        'noteContent',
        'deleteStatus',
    ];

    protected $casts = [
        'noteDate'     => 'date:Y-m-d',
        'deleteStatus' => 'integer',
    ];

    public function scopeForSchedule($q, $scheduleId)
    {
        return $q->where('croppingScheduleId', $scheduleId);
    }

    public function scopeForVersion($q, $versionId)
    {
        return $q->where('versionId', $versionId);
    }

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function version()
    {
        return $this->belongsTo(AsScheduleActivityVersion::class, 'versionId');
    }
}
