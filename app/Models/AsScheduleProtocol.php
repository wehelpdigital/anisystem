<?php

namespace App\Models;

class AsScheduleProtocol extends BaseModel
{
    protected $table = 'as_schedule_protocols';

    protected $fillable = [
        'croppingScheduleId',
        'protocolType',
        'protocolContent',
        'protocolFile',
        'protocolFileOriginalName',
        'deleteStatus',
    ];

    protected $casts = [
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }
}
