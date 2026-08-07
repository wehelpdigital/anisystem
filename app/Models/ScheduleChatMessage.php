<?php

namespace App\Models;

/**
 * One message in a schedule's team group chat (scoped by scheduleId). Private
 * 1:1 worker messages are NOT stored here — those reuse CommunityMessage so the
 * history is shared with the community DM.
 */
class ScheduleChatMessage extends BaseModel
{
    protected $table = 'as_schedule_messages';

    protected $fillable = [
        'scheduleId',
        'userId',
        'body',
        'imagePath',
        'deleteStatus',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
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
