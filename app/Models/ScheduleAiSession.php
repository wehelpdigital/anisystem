<?php

namespace App\Models;

/**
 * One saved "AI Technician" conversation for a schedule (Collab Room). Sessions
 * are visible to the whole team; each holds a thread of ScheduleAiMessage rows.
 */
class ScheduleAiSession extends BaseModel
{
    protected $table = 'as_schedule_ai_sessions';

    protected $fillable = [
        'scheduleId',
        'title',
        'startedByUserId',
        'lastMessageAt',
        'deleteStatus',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
        'startedByUserId' => 'integer',
        'lastMessageAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    public function starter()
    {
        return $this->belongsTo(User::class, 'startedByUserId');
    }

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }
}
