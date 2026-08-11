<?php

namespace App\Models;

/**
 * A named snapshot of the whole Collab Room map — every shape as it stood,
 * saved under a title so the team can reload that plan later. `objects` is
 * the JSON array of shapes exactly as the client renders them; `noteId`
 * points at the notebook note carrying this map's picture, when one exists.
 */
class ScheduleMapSave extends BaseModel
{
    protected $table = 'as_schedule_map_saves';

    protected $fillable = [
        'scheduleId', 'userId', 'title', 'objects', 'noteId', 'deleteStatus',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
        'userId' => 'integer',
        'noteId' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('deleteStatus', 1);
    }
}
