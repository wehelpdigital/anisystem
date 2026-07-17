<?php

namespace App\Models;

class AsScheduleIrrigation extends BaseModel
{
    protected $table = 'as_schedule_irrigations';

    /**
     * Canonical irrigation task-type catalog.
     *
     * Each entry pairs a slug (DB value) with a label, color, and icon so
     * controllers / views / the calendar bands all pull the same visual
     * vocabulary. Edit here and every consumer updates automatically.
     */
    public const TASK_TYPES = [
        'irrigate'      => 'Irrigate',
        'maintain'      => 'Maintain Water Level',
        'overflow'      => 'Overflow / Flush',
        'drain'         => 'Drain / Stop Irrigate',
        // Actively removing standing water from the field (e.g. opening
        // drainage channels, pumping out). Distinct from `drain` which is
        // just "stop adding water" — this is the deliberate water-out act.
        'drain_water'   => 'Drain Water',
        // No water management on this window — field is left dry on
        // purpose (e.g. between cropping phases, weed control prep).
        'no_irrigation' => 'No Irrigation',
        // Water level allowed to drop naturally via evaporation /
        // percolation. Passive — no drainage channels opened, just time.
        'let_subside'   => 'Let Subside',
    ];

    public const TASK_TYPE_COLORS = [
        'irrigate'      => '#1976d2', // active water-in blue
        'maintain'      => '#0097a7', // steady teal
        'overflow'      => '#f4a82a', // alert amber for flush/excess
        'drain'         => '#6b7280', // off-state slate
        'drain_water'   => '#8b5a2b', // earthy brown — water out, mud showing
        'no_irrigation' => '#4a5568', // dark muted gray — dry / inactive
        'let_subside'   => '#7e96a8', // dusty blue-gray — water passively receding
    ];

    public const TASK_TYPE_ICONS = [
        'irrigate'      => '💧',  // single drop = active fill
        'maintain'      => '≈',   // wavy = steady water surface
        'overflow'      => '🌊',  // big wave = overflow/flush
        'drain'         => '▾',   // small down triangle = drain off
        'drain_water'   => '⇣',   // double-stroke down arrow = actively draining
        'no_irrigation' => '∅',   // empty-set glyph = no action
        'let_subside'   => '↓',   // soft down arrow = water naturally receding
    ];

    /**
     * Resolve the visual meta (label / color / icon) for a task-type slug,
     * falling back to 'irrigate' for null or unknown values so callers
     * never have to defensively check.
     */
    public static function taskTypeMeta(?string $slug): array
    {
        $key = (is_string($slug) && isset(self::TASK_TYPES[$slug])) ? $slug : 'irrigate';
        return [
            'slug'  => $key,
            'label' => self::TASK_TYPES[$key],
            'color' => self::TASK_TYPE_COLORS[$key],
            'icon'  => self::TASK_TYPE_ICONS[$key],
        ];
    }

    protected $fillable = [
        'croppingScheduleId',
        'irrigationTitle',
        'description',
        'startDay',
        'endDay',
        'dayMode',
        'startDate',
        'endDate',
        'sortOrder',
        'priority',
        'taskType',
        'assignedWorkerId',
        'timeRequired',
        'deleteStatus',
    ];

    protected $casts = [
        'startDay' => 'integer',
        'endDay' => 'integer',
        'startDate' => 'date:Y-m-d',
        'endDate' => 'date:Y-m-d',
        'priority' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function assignedWorker()
    {
        return $this->belongsTo(AsScheduleWorker::class, 'assignedWorkerId');
    }

    /**
     * Many-to-many: workers assigned to this irrigation. Replaces the
     * legacy single `assignedWorkerId` column (which is still backfilled
     * on save for backward compat but no longer the read source).
     */
    public function workers()
    {
        return $this->belongsToMany(
            AsScheduleWorker::class,
            'as_schedule_irrigation_workers',
            'irrigationId',
            'workerId'
        );
    }

    /**
     * Many-to-many: lots this irrigation pertains to. Empty = applies to
     * every lot on the schedule (or to whichever lots the default
     * groupings drive on the calendar). When set, the irrigation is
     * understood to target those specific lots.
     */
    public function lots()
    {
        return $this->belongsToMany(
            AsScheduleLot::class,
            'as_schedule_irrigation_lots',
            'irrigationId',
            'lotId'
        );
    }
}
