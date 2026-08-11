<?php

namespace App\Models;

/**
 * Money a day brought in — a service sold, produce sold on the side. Mirrors
 * AsScheduleDayExpense so the two read the same way in reports, but lives in
 * its own table: every expense total sums that one, and an income row hiding
 * among them would quietly corrupt each.
 */
class AsScheduleDayIncome extends BaseModel
{
    protected $table = 'as_schedule_day_incomes';

    protected $fillable = [
        'croppingScheduleId',
        'versionId',
        'incomeDate',
        'amount',
        'title',
        'note',
        'sortOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'incomeDate'   => 'date:Y-m-d',
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
}
