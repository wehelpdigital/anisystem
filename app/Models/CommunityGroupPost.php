<?php

namespace App\Models;

class CommunityGroupPost extends BaseModel
{
    protected $table = 'as_community_group_posts';

    protected $fillable = ['groupId', 'userId', 'title', 'body', 'imagePath', 'isRestricted', 'restrictedReason', 'deleteStatus', 'videoPath', 'videoPoster',];

    protected $casts = ['isRestricted' => 'boolean'];

    public function author()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function group()
    {
        return $this->belongsTo(CommunityGroup::class, 'groupId');
    }

    public function replies()
    {
        return $this->hasMany(CommunityGroupReply::class, 'postId')->where('as_community_group_replies.deleteStatus', 1);
    }
}
