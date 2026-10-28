<?php

namespace App\Models;

/**
 * An Anee Auto season (2026-10-12): the plan Anee keeps for one cropping
 * schedule. See App\Http\Controllers\AneeAutoController.
 */
class AsAutoSeason extends BaseModel
{
    protected $table = 'as_auto_seasons';

    protected $fillable = ['userId', 'croppingScheduleId', 'status', 'step', 'lotsPaid', 'creditsCharged', 'answers', 'analysis', 'analysisAt', 'analysisRuns',
        'plan', 'generatedAt', 'job', 'jobState', 'jobAt', 'plannedAt', 'briefDate', 'emailDate', 'seenAt', 'nudgedAt', 'deleteStatus'];

    protected $casts = [
        'answers' => 'array', 'analysis' => 'array', 'plan' => 'array', 'jobState' => 'array',
        'analysisAt' => 'datetime', 'generatedAt' => 'datetime', 'jobAt' => 'datetime', 'plannedAt' => 'datetime', 'seenAt' => 'datetime', 'nudgedAt' => 'datetime',
        'briefDate' => 'date:Y-m-d', 'emailDate' => 'date:Y-m-d', 'creditsCharged' => 'float', 'lotsPaid' => 'integer', 'step' => 'integer',
    ];

    public const SETTING_UP = 'setting_up';
    public const ACTIVE = 'active';
    public const FINISHED = 'finished';

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function autoLots()
    {
        return $this->hasMany(AsAutoLot::class, 'autoId');
    }

    public function tasks()
    {
        return $this->hasMany(AsAutoTask::class, 'autoId')->where('deleteStatus', 1);
    }

    public function isLive(): bool
    {
        return $this->status === self::ACTIVE || $this->status === self::FINISHED;
    }
}
