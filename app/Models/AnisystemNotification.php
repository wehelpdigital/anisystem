<?php

namespace App\Models;

/**
 * A single in-app notification for an anee.io client — expiry reminders,
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

    /**
     * The rows the bell shows. While the Community is closed (App\Support\Community, 2026-11-24), news from it
     * (comments, reactions, cofarmers, messages, rankings: anything that opens /app/community outside the Tech Blog)
     * waits out of sight; it shows again when the Community opens.
     */
    public function scopeShown($query)
    {
        if (\App\Support\Community::open()) {
            return $query;
        }

        return $query->where(fn ($q) => $q->whereNull('url')
            ->orWhere('url', 'not like', '%/app/community%')
            ->orWhere('url', 'like', '%/app/community/blog%'));
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
