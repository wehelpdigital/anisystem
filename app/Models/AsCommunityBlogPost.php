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
            ? \App\Support\MediaStore::url($this->coverImagePath)
            : null;
    }

    /**
     * The same cover, as the mother site serves it.
     *
     * The blog is written over there, so its pictures are uploaded onto that
     * disk. A plain path resolves against THIS app'"'"'s public storage, which on
     * a fresh deploy holds none of them — hence covers that were fine
     * yesterday and broken today. This is where the file actually is, used as
     * the fallback when the local copy does not answer.
     */
    public function coverUrlOnMother(): ?string
    {
        if (blank($this->coverImagePath) || \App\Support\MediaStore::isRemote($this->coverImagePath)) {
            return null;
        }

        $base = rtrim((string) config('mother.url'), '/');

        return $base === '' ? null : $base . '/storage/' . ltrim($this->coverImagePath, '/');
    }
}
