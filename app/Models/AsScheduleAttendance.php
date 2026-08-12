<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One worker, one day, present or not.
 *
 * Absence is what gets written down; a worker with no row for a day was there
 * as planned.
 */
class AsScheduleAttendance extends Model
{
    protected $table = 'as_schedule_attendance';

    protected $fillable = [
        'croppingScheduleId',
        'workerId',
        'workDate',
        'isPresent',
        'note',
        'markedByUserId',
    ];

    protected $casts = [
        'isPresent' => 'boolean',
        'workDate' => 'date:Y-m-d',
    ];

    /** Worker ids marked absent on a day, as a lookup. */
    public static function absentOn(int $scheduleId, string $date): array
    {
        return static::where('croppingScheduleId', $scheduleId)
            ->whereDate('workDate', $date)
            ->where('isPresent', false)
            ->pluck('workerId')
            ->all();
    }
}
