<?php

namespace App\Models;

class AsScheduleLot extends BaseModel
{
    protected $table = 'as_schedule_lots';

    protected $fillable = [
        'croppingScheduleId',
        'lotName',
        'lotSize',
        'lotSizeUnit',
        'variety',
        'dayZeroDate',
        'notes',
        'deleteStatus',
    ];

    protected $casts = [
        'lotSize' => 'decimal:4',
        'dayZeroDate' => 'date:Y-m-d',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function activities()
    {
        return $this->belongsToMany(
            AsScheduleActivity::class,
            'as_schedule_activity_lots',
            'lotId',
            'activityId'
        );
    }
}
