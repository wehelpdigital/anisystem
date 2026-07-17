<?php

namespace App\Models;

class AsScheduleDefaultGroupingLot extends BaseModel
{
    protected $table = 'as_schedule_default_grouping_lots';

    protected $fillable = [
        'defaultGroupingId',
        'lotId',
    ];

    public function grouping()
    {
        return $this->belongsTo(AsScheduleDefaultGrouping::class, 'defaultGroupingId');
    }

    public function lot()
    {
        return $this->belongsTo(AsScheduleLot::class, 'lotId');
    }
}
