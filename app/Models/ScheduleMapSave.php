<?php

namespace App\Models;

/**
 * A saved map: every shape as it stood, under a title, so it can be opened
 * and worked on again. `objects` is the JSON array of shapes exactly as the
 * client renders them; `noteId` points at the note carrying this map's
 * picture, when one exists.
 *
 * Maps are the grower's own (2026-09-30): scheduleId 0, owned by userId.
 * `originScheduleId` is the season it was first drawn in, and
 * as_schedule_map_links says which seasons use it (see App\Support\MapAccess).
 */
class ScheduleMapSave extends BaseModel
{
    protected $table = 'as_schedule_map_saves';

    protected $fillable = [
        'scheduleId', 'originScheduleId', 'userId', 'title', 'source', 'objects', 'noteId', 'deleteStatus',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
        'originScheduleId' => 'integer',
        'userId' => 'integer',
        'noteId' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('deleteStatus', 1);
    }
}
