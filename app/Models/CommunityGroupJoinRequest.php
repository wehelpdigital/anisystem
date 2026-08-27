<?php

namespace App\Models;

/**
 * Somebody waiting at the door of a private discussion.
 *
 * Deliberately NOT a row in as_community_group_members with a status: that
 * table is what the whole app means by "is this person in this room", and a
 * waiting stranger sitting in it would become a member everywhere that
 * question is asked. Waiting is a different state from being in.
 *
 * One row per person per room (unique index), so asking twice reuses the row
 * and a decided request is still on file when they ask again.
 */
class CommunityGroupJoinRequest extends BaseModel
{
    protected $table = 'as_community_group_join_requests';

    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const DECLINED = 'declined';

    protected $fillable = [
        'groupId', 'userId', 'status', 'decidedByUserId', 'decidedAt', 'deleteStatus',
    ];

    protected $casts = [
        'groupId' => 'integer',
        'userId' => 'integer',
        'decidedByUserId' => 'integer',
        'decidedAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    public function asker()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', self::PENDING);
    }
}
