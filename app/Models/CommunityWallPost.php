<?php

namespace App\Models;

class CommunityWallPost extends BaseModel
{
    protected $table = 'as_community_wall_posts';

    protected $fillable = ['wallUserId', 'authorUserId', 'body', 'imagePath', 'videoPath', 'videoPoster', 'isRestricted', 'restrictedReason', 'deleteStatus'];

    protected $casts = ['isRestricted' => 'boolean'];

    public function author()
    {
        return $this->belongsTo(User::class, 'authorUserId');
    }

    public function comments()
    {
        return $this->hasMany(CommunityWallComment::class, 'wallPostId')->where('as_community_wall_comments.deleteStatus', 1);
    }
}
