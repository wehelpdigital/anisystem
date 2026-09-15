<?php

namespace App\Models;

/**
 * One run of "Realign by Anee" on one lot: what the calendar said, what
 * she found, what it cost. The lot itself keeps the applied answer
 * (growthShiftDays, growthRealign); these rows are the history.
 */
class AsGrowthRealign extends BaseModel
{
    protected $table = 'as_growth_realigns';

    protected $fillable = [
        'croppingScheduleId', 'lotId', 'userId', 'asOf', 'calendarDay', 'calendarStage',
        'status', 'credits', 'result', 'error', 'deleteStatus',
    ];

    protected function casts(): array
    {
        return parent::casts() + [
            'asOf' => 'date:Y-m-d',
            'calendarDay' => 'integer',
            'credits' => 'decimal:2',
            'result' => 'array',
        ];
    }
}
