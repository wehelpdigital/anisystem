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
        // Which reminder tick put this row here, so unticking can take
        // exactly that one away again.
        'sourceRef',
        'sortOrder',
        // Where this row's whole strip sits among the day's activities; every
        // row of one day carries the same number, null means "at the top".
        'blockSort',
        'deleteStatus',
    ];

    protected $casts = [
        'incomeDate'   => 'date:Y-m-d',
        'amount'       => 'decimal:2',
        'sortOrder'    => 'integer',
        'blockSort'    => 'integer',
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
