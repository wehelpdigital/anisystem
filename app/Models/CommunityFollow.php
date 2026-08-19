<?php

namespace App\Models;

/**
 * One person deciding to keep up with another.
 *
 * Not a connection: a co-farmer link is mutual and asks permission, following
 * is one-sided and asks nobody. The pair is unique, so following again is the
 * same row waking up rather than a second row nobody can tell apart.
 */
class CommunityFollow extends BaseModel
{
    protected $table = 'as_community_follows';

    protected $fillable = ['followerUserId', 'followedUserId', 'deleteStatus'];

    public function follower()
    {
        return $this->belongsTo(User::class, 'followerUserId');
    }

    public function followed()
    {
        return $this->belongsTo(User::class, 'followedUserId');
    }
}
