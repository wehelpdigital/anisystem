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
