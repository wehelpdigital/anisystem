<?php

namespace App\Models;

class CommunityGroupReply extends BaseModel
{
    protected $table = 'as_community_group_replies';

    protected $fillable = ['postId', 'parentId', 'userId', 'body', 'imagePath', 'isDeleted', 'deleteStatus'];

    protected $casts = ['isDeleted' => 'boolean', 'isRestricted' => 'boolean'];

    public function author()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parentId')->where('deleteStatus', 1);
    }
}
