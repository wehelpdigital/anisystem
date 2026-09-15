<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use Illuminate\Support\Facades\Cache;

/**
 * Who is in a schedule's Collab Room right now.
 *
 * Pusher is the room's, and only the room's. No page outside it opens a
 * socket any more (see resources/js/bootstrap.js), so a broadcast with
 * nobody else in the room is a message paid for and heard by no one -- and
 * on Pusher's plan the messages and the open sockets ARE the budget. Every
 * room event asks here before it goes out (HeardOnlyInTheRoom), and the
 * bell asks whether the person is in any room at all before it pushes.
 *
 * Presence is a cache key with a TTL per member, renewed by the room's own
 * polls (the docked chat every five seconds, the roster every thirty), so
 * it expires by itself when a tab closes and nobody has to sweep up.
 * Ninety seconds survives a missed beat without keeping a ghost in the
 * room for long.
 *
 * NOT ScheduleBoardPresence: that row answers "is a teammate mid-sketch on
 * the whiteboard" and BoardSession clears the canvas by it -- counting
 * someone reading the chat as board presence would quietly change when a
 * drawing is judged safe to archive.
 *
 * The cache has to be one the whole app shares: with more than one
 * instance behind the site, a file cache would keep each instance's own
 * idea of who is here, and a save landing on the other instance would go
 * out to nobody. CACHE_STORE=database is the safe setting there.
 */
class CollabPresence
{
    public const TTL_SECONDS = 90;

    /** This person is in this schedule's room, as of now. */
    public static function mark(int $scheduleId, int $userId): void
    {
        Cache::put('collab-here.' . $scheduleId . '.' . $userId, time(), self::TTL_SECONDS);
        // And in *a* room, which is what the bell needs to know: a
        // notification is pushed live only to someone holding a socket,
        // and only someone in a room holds one.
        Cache::put('collab-here-user.' . $userId, time(), self::TTL_SECONDS);
    }

    /** Whether this member's heartbeat for this room is still fresh. */
    public static function here(int $scheduleId, int $userId): bool
    {
        return Cache::has('collab-here.' . $scheduleId . '.' . $userId);
    }

    /** Whether this person is in any Collab Room at all. */
    public static function anywhere(int $userId): bool
    {
        return Cache::has('collab-here-user.' . $userId);
    }

    /**
     * Whether anyone OTHER than this person is in the schedule's room --
     * the question a sender asks, since it already has its own change.
     */
    public static function othersHere(int|AsCroppingSchedule $schedule, int $exceptUserId): bool
    {
        if (is_int($schedule)) {
            $schedule = AsCroppingSchedule::find($schedule);
        }
        if (! $schedule) {
            return false;
        }
        $sid = (int) $schedule->id;
        foreach (ScheduleTeam::memberIds($schedule) as $memberId) {
            if ((int) $memberId !== $exceptUserId && self::here($sid, (int) $memberId)) {
                return true;
            }
        }

        return false;
    }
}
