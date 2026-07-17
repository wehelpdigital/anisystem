<?php

namespace App\Models;

class AsScheduleDefaultGrouping extends BaseModel
{
    protected $table = 'as_schedule_default_groupings';

    protected $fillable = [
        'croppingScheduleId',
        'groupName',
        'staggerDays',
        'startDate',
        'groupOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'staggerDays' => 'integer',
        'startDate' => 'date:Y-m-d',
        'groupOrder' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function lots()
    {
        return $this->belongsToMany(
            AsScheduleLot::class,
            'as_schedule_default_grouping_lots',
            'defaultGroupingId',
            'lotId'
        );
    }

    public function groupingLots()
    {
        return $this->hasMany(AsScheduleDefaultGroupingLot::class, 'defaultGroupingId');
    }
}
