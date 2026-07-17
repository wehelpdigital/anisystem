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

    protected $fillable = [
        'croppingScheduleId',
        'versionId',
        'sourceActivityId',
        'activityTitle',
        'targetDate',
        'targetEndDate',
        'priority',
        'activityType',
        'isDayZero',
        'isDraft',
        'isHidden',
        'description',
        'imagePath',
        'timeRequired',
        'sequenceOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'targetDate' => 'date:Y-m-d',
        'targetEndDate' => 'date:Y-m-d',
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
}
