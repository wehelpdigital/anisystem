<?php

namespace App\Models;

/**
 * A farm report on the shelf — a frozen copy of a computed report (labor,
 * expenses, profit) or an AI-written one (season, sofar). The plain-text
 * `body` is what an Anee chat reads when the report is attached; the JSON
 * `report` is what the AI kinds are drawn from on screen.
 */
class AsFarmReport extends BaseModel
{
    protected $table = 'as_farm_reports';

    public const KINDS = ['labor', 'expenses', 'profit', 'season', 'sofar', 'protocol', 'compare'];

    protected $fillable = [
        'userId', 'croppingScheduleId', 'kind', 'title',
        'params', 'body', 'report', 'credits',
        'status', 'error', 'deleteStatus',
    ];

    protected $casts = [
        'userId' => 'integer',
        'croppingScheduleId' => 'integer',
        'params' => 'array',
        'report' => 'array',
        'credits' => 'decimal:2',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }
}
