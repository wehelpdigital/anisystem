<?php

namespace App\Models;

/**
 * A co-farmer connection (friend request). Stored one-directional:
 * `userId` sent the request to `friendUserId`. `accepted` means the two are
 * mutually connected regardless of who asked.
 */
class CommunityConnection extends BaseModel
{
    protected $table = 'as_community_connections';

    protected $fillable = [
        'userId', 'friendUserId', 'status', 'respondedAt', 'deleteStatus',
    ];

    protected $casts = [
        'respondedAt' => 'datetime',
    ];

    /** The live row between two users in either direction, if any. */
    public static function between(int $a, int $b): ?self
    {
        return static::active()
            ->where(function ($q) use ($a, $b) {
                $q->where('userId', $a)->where('friendUserId', $b);
            })
            ->orWhere(function ($q) use ($a, $b) {
                $q->where('userId', $b)->where('friendUserId', $a);
            })
            ->first();
    }

    /**
     * Relationship of $otherId as seen by $viewerId:
     * 'self' | 'connected' | 'pending_out' (viewer asked) |
     * 'pending_in' (viewer was asked) | 'none'.
     */
    public static function statusFor(int $viewerId, int $otherId): string
    {
        if ($viewerId === $otherId) {
            return 'self';
        }
        $row = static::between($viewerId, $otherId);
        if (! $row) {
            return 'none';
        }
        if ($row->status === 'accepted') {
            return 'connected';
        }
        if ($row->status === 'pending') {
            return (int) $row->userId === $viewerId ? 'pending_out' : 'pending_in';
        }

        return 'none'; // declined → treat as connectable again
    }

    /** IDs of everyone $userId is accepted-connected to. */
    public static function connectedIds(int $userId): array
    {
        return static::active()
            ->where('status', 'accepted')
            ->where(fn ($q) => $q->where('userId', $userId)->orWhere('friendUserId', $userId))
            ->get()
            ->map(fn ($r) => (int) $r->userId === $userId ? (int) $r->friendUserId : (int) $r->userId)
            ->unique()
            ->values()
            ->all();
    }
}
