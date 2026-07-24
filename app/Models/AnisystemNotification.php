<?php

namespace App\Models;

/**
 * A single in-app notification for an AniSystem client — expiry reminders,
 * community comments/replies on their shared plans, connection requests, etc.
 * Surfaced through the top-bar bell.
 */
class AnisystemNotification extends BaseModel
{
    protected $table = 'anisystem_notifications';

    protected $fillable = [
        'userId',
        'type',
        'title',
        'body',
        'url',
        'actorUserId',
        'croppingScheduleId',
        'readAt',
        'deleteStatus',
    ];

    protected $casts = [
        'userId' => 'integer',
        'actorUserId' => 'integer',
        'croppingScheduleId' => 'integer',
        'readAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    public function scopeForUser($query, $userId)
    {
        return $query->where('userId', $userId);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('readAt');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actorUserId');
    }
}
