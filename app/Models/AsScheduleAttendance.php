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

    /**
     * Worker ids marked absent on a day.
     *
     * Memoised per schedule and day: a board draws many cards for the same
     * date, and each of them asking separately would be one query per card for
     * an answer that cannot change mid-render.
     */
    public static function absentOn(int $scheduleId, string $date): array
    {
        static $cache = [];
        $key = $scheduleId . '|' . $date;

        return $cache[$key] ??= static::where('croppingScheduleId', $scheduleId)
            ->whereDate('workDate', $date)
            ->where('isPresent', false)
            ->pluck('workerId')
            ->all();
    }

    /** Forget the memo for a day, after marking someone. */
    public static function forget(int $scheduleId, string $date): void
    {
        static::absentOn($scheduleId, $date);   // ensure the static exists
    }
}
