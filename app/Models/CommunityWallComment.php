<?php

namespace App\Models;

class CommunityWallComment extends BaseModel
{
    protected $table = 'as_community_wall_comments';

    protected $fillable = ['wallPostId', 'userId', 'body', 'deleteStatus'];

    public function author()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
