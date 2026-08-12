<?php

namespace App\Models;

class AsScheduleWorker extends BaseModel
{
    protected $table = 'as_schedule_workers';

    /**
     * Canonical catalog of worker skills. Keys are the slugs stored in the
     * JSON column, values are the human-readable labels rendered in the UI.
     * Single source of truth — controller validation, view rendering, and
     * any future filter logic all read from here.
     */
    public const SKILLS = [
        'manager'             => 'Manager',
        'spray'               => 'Spray',
        'broadcast_granulars' => 'Broadcast Granulars',
        'operate_machine'     => 'Operate Machine',
        'harrowing'           => 'Harrowing (Pagsusuyod)',
    ];

    protected $fillable = [
        'croppingScheduleId',
        'workerName',
        'email',
        'phone',
        'costPerHalfDay',
        'priority',
        'skills',
        'notes',
        'deleteStatus',
    ];

    protected $casts = [
        'costPerHalfDay' => 'decimal:2',
        'priority' => 'integer',
        'skills' => 'array',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function offDates()
    {
        return $this->hasMany(AsScheduleWorkerOffDate::class, 'workerId');
    }

    public function offDays()
    {
        return $this->hasMany(AsScheduleWorkerOffDay::class, 'workerId');
    }

    public function activities()
    {
        return $this->belongsToMany(
            AsScheduleActivity::class,
            'as_schedule_activity_workers',
            'workerId',
            'activityId'
        );
    }

    /**
     * Every worker's days off in one schedule, as
     * [workerId => ['days' => [0..6], 'dates' => ['Y-m-d', …]]].
     *
     * Memoised per schedule: a board draws hundreds of worker names, and
     * asking the database twice per name per card is the difference between
     * two queries and two hundred.
     */
    public static function offMapFor(int $scheduleId): array
    {
        static $cache = [];
        if (isset($cache[$scheduleId])) {
            return $cache[$scheduleId];
        }

        $ids = static::where('croppingScheduleId', $scheduleId)->pluck('id');
        $map = [];
        foreach ($ids as $id) {
            $map[(int) $id] = ['days' => [], 'dates' => []];
        }
        foreach (AsScheduleWorkerOffDay::whereIn('workerId', $ids)->get() as $row) {
            $map[(int) $row->workerId]['days'][] = (int) $row->dayOfWeek;
        }
        foreach (AsScheduleWorkerOffDate::whereIn('workerId', $ids)->get() as $row) {
            $map[(int) $row->workerId]['dates'][] = $row->offDate?->format('Y-m-d');
        }

        return $cache[$scheduleId] = $map;
    }

    /** Is this worker free to be put on work that day? */
    public function isAvailableOn(\Carbon\Carbon $date): bool
    {
        $off = static::offMapFor((int) $this->croppingScheduleId)[(int) $this->id]
            ?? ['days' => [], 'dates' => []];

        return ! in_array((int) $date->dayOfWeek, $off['days'], true)   // 0 = Sunday
            && ! in_array($date->format('Y-m-d'), $off['dates'], true);
    }
}
