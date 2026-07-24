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

    public function isAvailableOn(\Carbon\Carbon $date): bool
    {
        $dow = (int) $date->dayOfWeek; // 0=Sunday
        if ($this->offDays()->where('dayOfWeek', $dow)->exists()) {
            return false;
        }
        if ($this->offDates()->where('offDate', $date->format('Y-m-d'))->exists()) {
            return false;
        }
        return true;
    }
}
