<?php

namespace App\Models;

class CommunityWallComment extends BaseModel
{
    protected $table = 'as_community_wall_comments';

    protected $fillable = ['wallPostId', 'parentId', 'userId', 'body', 'imagePath', 'videoPath', 'videoPoster', 'isDeleted', 'deleteStatus'];

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
