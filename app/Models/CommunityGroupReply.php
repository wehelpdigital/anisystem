<?php

namespace App\Models;

class CommunityGroupReply extends BaseModel
{
    protected $table = 'as_community_group_replies';

    protected $fillable = ['postId', 'userId', 'body', 'deleteStatus'];

    public function author()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
