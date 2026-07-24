<?php

namespace App\Models;

class CommunityGroupPost extends BaseModel
{
    protected $table = 'as_community_group_posts';

    protected $fillable = ['groupId', 'userId', 'title', 'body', 'imagePath', 'deleteStatus'];

    public function author()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function replies()
    {
        return $this->hasMany(CommunityGroupReply::class, 'postId')->where('as_community_group_replies.deleteStatus', 1);
    }
}
