<?php

namespace App\Models;

class CommunityGroupMessage extends BaseModel
{
    protected $table = 'as_community_group_messages';

    protected $fillable = ['groupId', 'userId', 'body', 'imagePath', 'videoPath', 'videoPoster', 'deleteStatus'];

    public function author()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
