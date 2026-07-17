<?php

namespace App\Models;

class AsScheduleCriticalRule extends BaseModel
{
    protected $table = 'as_schedule_critical_rules';

    protected $fillable = [
        'croppingScheduleId',
        'ruleText',
        'sortOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'sortOrder'    => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }
}
