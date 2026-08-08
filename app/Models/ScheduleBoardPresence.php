<?php

namespace App\Models;

use Carbon\Carbon;

/**
 * Who is in a schedule's drawing room right now.
 *
 * The global `users.lastSeenAt` cannot answer this: someone browsing the
 * dashboard is online but not drawing. Opening the board clears it for a fresh
 * drawing, so "is a teammate mid-sketch in THIS room" is the question that
 * stops one member wiping another's live canvas by walking in.
 */
class ScheduleBoardPresence extends BaseModel
{
    protected $table = 'as_schedule_board_presence';

    /** A heartbeat older than this means they have left the room. */
    public const STALE_AFTER_SECONDS = 45;

    protected $fillable = [
        'scheduleId',
        'userId',
        'lastSeenAt',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
        'userId' => 'integer',
        'lastSeenAt' => 'datetime',
    ];

    /** Record that this member is still in the room. */
    public static function touch_(int $scheduleId, int $userId): void
    {
        static::updateOrCreate(
            ['scheduleId' => $scheduleId, 'userId' => $userId],
            ['lastSeenAt' => Carbon::now('Asia/Manila')]
        );
    }

    /** Members other than me whose heartbeat is still fresh. */
    public static function othersPresent(int $scheduleId, int $exceptUserId): int
    {
        return static::where('scheduleId', $scheduleId)
            ->where('userId', '!=', $exceptUserId)
            ->where('lastSeenAt', '>=', Carbon::now('Asia/Manila')->subSeconds(self::STALE_AFTER_SECONDS))
            ->count();
    }
}
