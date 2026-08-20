<?php

namespace App\Models;

class CommunityWallPost extends BaseModel
{
    protected $table = 'as_community_wall_posts';

    protected $fillable = ['wallUserId', 'authorUserId', 'body', 'sharedPostId', 'publicToken', 'imagePath', 'videoPath', 'videoPoster', 'isReel', 'durationSec', 'audioTitle', 'isRestricted', 'restrictedReason', 'deleteStatus'];

    protected $casts = ['isRestricted' => 'boolean', 'isReel' => 'boolean'];

    /**
     * The post this one is sharing, if any — loaded with its own author so a
     * shared card can be drawn without a second trip per post.
     */
    /**
     * Wall posts only — a story is not one of them.
     *
     * A story is stored as a wall post because that is how it gets reactions,
     * comments, bookmarks and shares for free. It is not READ as one: it
     * belongs in the rail at the top of the wall, and showing it twice (once
     * as a tile, once as a card with a tall video in it) is the wall telling
     * the same news two ways.
     */
    public function scopeWallOnly($query)
    {
        return $query->where(fn ($q) => $q->whereNull('isReel')->orWhere('isReel', 0));
    }

    public function sharedPost()
    {
        return $this->belongsTo(self::class, 'sharedPostId')->with('author');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'authorUserId');
    }

    public function comments()
    {
        return $this->hasMany(CommunityWallComment::class, 'wallPostId')->where('as_community_wall_comments.deleteStatus', 1);
    }
}
