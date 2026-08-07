<?php

namespace App\Models;

class AsCommunityBlogPost extends BaseModel
{
    protected $table = 'as_community_blog_posts';

    protected $fillable = [
        'title', 'slug', 'coverImagePath', 'excerpt', 'body',
        'authorName', 'isPublished', 'publishedAt', 'viewCount', 'deleteStatus',
    ];

    protected $casts = [
        'isPublished' => 'boolean',
        'publishedAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    public function comments()
    {
        return $this->hasMany(AsCommunityBlogComment::class, 'blogPostId')
            ->where('as_community_blog_comments.deleteStatus', 1);
    }

    public function scopePublished($q)
    {
        return $q->where('isPublished', 1);
    }

    public function coverUrl(): ?string
    {
        return $this->coverImagePath
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->coverImagePath)
            : null;
    }
}
