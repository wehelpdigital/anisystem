<?php

namespace App\Models;

class CommunityGroup extends BaseModel
{
    protected $table = 'as_community_groups';

    protected $fillable = [
        'name', 'slug', 'description', 'coverImagePath', 'bannerImagePath', 'createdByUserId', 'deleteStatus',
    ];

    public function members()
    {
        return $this->hasMany(CommunityGroupMember::class, 'groupId')->where('as_community_group_members.deleteStatus', 1);
    }

    /**
     * Every reply under every topic in this discussion.
     *
     * hasManyThrough, so "how busy is this room" is one count query rather
     * than a walk over its posts — the list asks this for every card it draws.
     */
    public function replies()
    {
        return $this->hasManyThrough(
            CommunityGroupReply::class,
            CommunityGroupPost::class,
            'groupId',
            'postId',
        )->where('as_community_group_replies.deleteStatus', 1)
            ->where('as_community_group_posts.deleteStatus', 1);
    }

    public function posts()
    {
        return $this->hasMany(CommunityGroupPost::class, 'groupId')->where('as_community_group_posts.deleteStatus', 1);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'createdByUserId');
    }
}
