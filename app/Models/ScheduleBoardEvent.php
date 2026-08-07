<?php

namespace App\Models;

/**
 * One event on a schedule's collaborative whiteboard: either a draw segment
 * (a batch of points for a stroke) or a "clear" marker. The board is rebuilt by
 * replaying active events; a clear soft-deletes everything before it.
 */
class ScheduleBoardEvent extends BaseModel
{
    protected $table = 'as_schedule_board_events';

    protected $fillable = [
        'scheduleId',
        'page',
        'userId',
        'type',
        'strokeUid',
        'color',
        'width',
        'mode',
        'shapeText',
        'points',
        'deleteStatus',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
        'page' => 'integer',
        'userId' => 'integer',
        'width' => 'integer',
        'points' => 'array',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }
}
