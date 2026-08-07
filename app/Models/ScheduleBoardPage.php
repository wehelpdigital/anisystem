<?php

namespace App\Models;

/**
 * One page of a schedule's collaborative whiteboard. Each page has its own
 * orientation (landscape / portrait) and its own set of board events; the board
 * is a numbered stack of these pages (page 1..N).
 */
class ScheduleBoardPage extends BaseModel
{
    protected $table = 'as_schedule_board_pages';

    protected $fillable = [
        'scheduleId',
        'page',
        'orientation',
        'deleteStatus',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
        'page' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }
}
