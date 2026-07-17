<?php

namespace App\Models;

class AsScheduleProgressMarker extends BaseModel
{
    protected $table = 'as_schedule_progress_markers';

    protected $fillable = [
        'croppingScheduleId',
        'versionId',
        'markerDate',
        'noteContent',
        'deleteStatus',
    ];

    protected $casts = [
        'markerDate' => 'date:Y-m-d',
        'deleteStatus' => 'integer',
        'versionId' => 'integer',
    ];

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
