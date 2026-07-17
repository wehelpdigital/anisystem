<?php

namespace App\Models;

class AsScheduleService extends BaseModel
{
    protected $table = 'as_schedule_services';

    protected $fillable = [
        'croppingScheduleId',
        'serviceName',
        'description',
        'serviceCost',
        'deleteStatus',
    ];

    protected $casts = [
        'serviceCost' => 'decimal:2',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }
}
