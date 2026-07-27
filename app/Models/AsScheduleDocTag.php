<?php

namespace App\Models;

/**
 * A reusable custom documentation tag for a schedule (e.g. "Mixing Chart",
 * "Field Map"). Introduction and Critical Rule are built-in types, so they
 * are not stored here — only user-added tags are.
 */
class AsScheduleDocTag extends BaseModel
{
    protected $table = 'as_schedule_doc_tags';

    protected $fillable = [
        'croppingScheduleId',
        'name',
        'sortOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'sortOrder' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function entries()
    {
        return $this->hasMany(AsScheduleDocEntry::class, 'tagId');
    }
}
