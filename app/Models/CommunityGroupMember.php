<?php

namespace App\Models;

/**
 * One person's place in one discussion.
 *
 * There is exactly one row per person per room (unique groupId+userId), which
 * is why leaving sets deleteStatus to 0 rather than deleting: coming back
 * reuses the row. `removedAt` is what tells walking out apart from being shown
 * out, and a room consults it before letting somebody back in.
 */
class CommunityGroupMember extends BaseModel
{
    protected $table = 'as_community_group_members';

    /** The one who started the room. Cannot be removed or demoted. */
    public const OWNER = 'owner';

    /** A deputy: lets people in, puts people out, appoints nobody. */
    public const MODERATOR = 'moderator';

    public const MEMBER = 'member';

    protected $fillable = [
        'groupId', 'userId', 'role', 'removedAt', 'removedReason', 'removedByUserId', 'deleteStatus',
    ];

    protected $casts = [
        'groupId' => 'integer',
        'userId' => 'integer',
        'removedAt' => 'datetime',
        'removedByUserId' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    /** Shown out rather than walked out — a standing bar on coming back. */
    public function wasRemoved(): bool
    {
        return (int) $this->deleteStatus === 0 && $this->removedAt !== null;
    }
}
