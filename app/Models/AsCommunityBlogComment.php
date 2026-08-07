<?php

namespace App\Models;

class AsCommunityBlogComment extends BaseModel
{
    protected $table = 'as_community_blog_comments';

    protected $fillable = ['blogPostId', 'userId', 'body', 'imagePath', 'deleteStatus'];

    public function author()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
