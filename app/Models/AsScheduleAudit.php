<?php

namespace App\Models;

/**
 * One line of the season's diary of hands: who did what, where, when.
 * Written by {@see \App\Http\Middleware\RecordsScheduleActivity}.
 */
class AsScheduleAudit extends BaseModel
{
    protected $table = 'as_schedule_audits';

    public const UPDATED_AT = null;

    protected $fillable = [
        'croppingScheduleId', 'userId', 'routeName', 'method', 'label',
    ];
}
