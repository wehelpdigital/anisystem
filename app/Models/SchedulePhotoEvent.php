<?php

namespace App\Models;

/**
 * One stroke on the Collab Room's shared photo, or the clear that retired a
 * batch of them. Rebuilt by replay, exactly like the whiteboard — but in its
 * own table, so nothing the whiteboard computes from ITS events (the board
 * token, the emptied-board release, the archive) can be moved by a photo.
 */
class SchedulePhotoEvent extends BaseModel
{
    protected $table = 'as_schedule_photo_events';

    protected $fillable = [
        'scheduleId', 'userId', 'type', 'mode', 'color', 'width',
        'strokeUid', 'points', 'shapeText', 'deleteStatus',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
        'userId' => 'integer',
        'width' => 'integer',
        'points' => 'array',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('deleteStatus', 1);
    }
}
