<?php

namespace App\Models;

class AsScheduleDayExpense extends BaseModel
{
    protected $table = 'as_schedule_day_expenses';

    protected $fillable = [
        'croppingScheduleId',
        'versionId',
        'expenseDate',
        'amount',
        'note',
        'sortOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'expenseDate'  => 'date:Y-m-d',
        'amount'       => 'decimal:2',
        'sortOrder'    => 'integer',
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
