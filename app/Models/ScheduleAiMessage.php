<?php

namespace App\Models;

/**
 * One turn in a schedule's shared "AI Technician" thread (Collab Room). `userId`
 * is the asker for user turns, the schedule owner for assistant turns.
 */
class ScheduleAiMessage extends BaseModel
{
    protected $table = 'as_schedule_ai_messages';

    protected $fillable = [
        'scheduleId',
        'sessionId',
        'userId',
        'role',
        'content',
        'imagePath',
        'creditsCharged',
        'deleteStatus',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
        'sessionId' => 'integer',
        'userId' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }
}
