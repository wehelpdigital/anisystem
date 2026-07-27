<?php

namespace App\Models;

class AsScheduleActivity extends BaseModel
{
    protected $table = 'as_schedule_activities';

    /**
     * Canonical catalog of activity types. Keys are the slugs stored in the
     * activityType column, values are the human-readable labels rendered in
     * the UI. Single source of truth — controller validation, view rendering,
     * the modal select, and the auto-categorizer all read from here.
     */
    public const ACTIVITY_TYPES = [
        'equipment_prep' => 'Equipment Preparation',
        'land_prep'      => 'Land Preparation',
        'seed_treatment' => 'Seed Treatment',
        'planting'       => 'Planting',
        'irrigation'     => 'Irrigation',
        'fertilizer'     => 'Fertilizer (Granular)',
        'foliar_spray'   => 'Foliar Spray',
        'microbial'      => 'Microbial / Bio',
        'harvest'        => 'Harvest',
        'monitoring'     => 'Monitoring',
        'other'          => 'Other',
    ];

    /**
     * Water-task catalog for irrigation-type activities (activityType =
     * 'irrigation'). slug => label, plus a color for the card badge.
     */
    public const WATER_TASKS = [
        'irrigate'      => 'Irrigate',
        'maintain'      => 'Maintain water',
        'overflow'      => 'Overflow',
        'drain'         => 'Drain',
        'drain_water'   => 'Drain water',
        'no_irrigation' => 'No irrigation',
        'let_subside'   => 'Let subside',
    ];

    public const WATER_TASK_COLORS = [
        'irrigate'      => '#2f8fd8',
        'maintain'      => '#1aa3a3',
        'overflow'      => '#7c6bd6',
        'drain'         => '#c1873b',
        'drain_water'   => '#c1873b',
        'no_irrigation' => '#8a95a8',
        'let_subside'   => '#5a8f4c',
    ];

    protected $fillable = [
        'croppingScheduleId',
        'versionId',
        'sourceActivityId',
        'activityTitle',
        'targetDate',
        'targetEndDate',
        'priority',
        'activityType',
        'waterTask',
        'isDayZero',
        'isDraft',
        'isHidden',
        'description',
        'imagePath',
        'imagePaths',
        'timeRequired',
        'sequenceOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'targetDate' => 'date:Y-m-d',
        'targetEndDate' => 'date:Y-m-d',
        'imagePaths' => 'array',
        'isDayZero' => 'boolean',
        'isDraft' => 'boolean',
        'isHidden' => 'boolean',
        'sequenceOrder' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function version()
    {
        return $this->belongsTo(AsScheduleActivityVersion::class, 'versionId');
    }

    public function sourceActivity()
    {
        return $this->belongsTo(self::class, 'sourceActivityId');
    }

    public function scopeForVersion($q, $versionId)
    {
        return $q->where('versionId', $versionId);
    }

    public function items()
    {
        return $this->hasMany(AsScheduleActivityItem::class, 'activityId')->where('as_schedule_activity_items.deleteStatus', 1);
    }

    public function lots()
    {
        return $this->belongsToMany(
            AsScheduleLot::class,
            'as_schedule_activity_lots',
            'activityId',
            'lotId'
        );
    }

    public function workers()
    {
        return $this->belongsToMany(
            AsScheduleWorker::class,
            'as_schedule_activity_workers',
            'activityId',
            'workerId'
        );
    }

    /**
     * Public URL for the activity's reference image (or null if none).
     * Path is stored relative to the `public` disk so asset('storage/...')
     * gives the publicly-accessible URL after `storage:link` has run.
     */
    public function imageUrl(): ?string
    {
        if (empty($this->imagePath)) return null;
        return asset('storage/' . ltrim($this->imagePath, '/'));
    }

    /**
     * Absolute filesystem path for embedding via base64 (used by
     * server-rendered printable documents where remote URLs would
     * round-trip via headless Chrome). Returns null if file is missing.
     */
    public function imageAbsolutePath(): ?string
    {
        if (empty($this->imagePath)) return null;
        $full = storage_path('app/public/' . ltrim($this->imagePath, '/'));
        return file_exists($full) ? $full : null;
    }

    /**
     * All reference-image paths — the JSON list if present, else the single
     * legacy imagePath. Always an array (possibly empty).
     *
     * @return array<int, string>
     */
    public function imagePathList(): array
    {
        if (! empty($this->imagePaths) && is_array($this->imagePaths)) {
            return array_values(array_filter($this->imagePaths));
        }
        return array_values(array_filter([$this->imagePath]));
    }

    /**
     * Reference images as [{path, url}] for the front-end.
     *
     * @return array<int, array{path:string, url:string}>
     */
    public function imageList(): array
    {
        return array_map(fn ($p) => [
            'path' => $p,
            'url' => asset('storage/' . ltrim($p, '/')),
        ], $this->imagePathList());
    }

    /** Label + color for an irrigation activity's water task (or null). */
    public function waterTaskMeta(): ?array
    {
        if ($this->activityType !== 'irrigation') return null;
        $slug = $this->waterTask && isset(self::WATER_TASKS[$this->waterTask]) ? $this->waterTask : 'irrigate';
        return [
            'slug' => $slug,
            'label' => self::WATER_TASKS[$slug],
            'color' => self::WATER_TASK_COLORS[$slug] ?? '#2f8fd8',
        ];
    }
}
