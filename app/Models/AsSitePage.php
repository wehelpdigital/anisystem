<?php

namespace App\Models;

/**
 * One page of the public site's guides, blog or feature pages: its address,
 * what search engines read, and the page as blocks (see App\Support\SitePages
 * for the block kinds and how they are drawn). Edited in the mother app's
 * block builder.
 */
class AsSitePage extends BaseModel
{
    protected $table = 'as_site_pages';

    protected $fillable = [
        'section', 'slug', 'lang', 'category', 'title', 'metaTitle', 'metaDescription', 'focusKeyword',
        'keywords', 'excerpt', 'heroImage', 'blocks', 'status', 'sortOrder', 'publishedAt', 'editedAt',
        'editedBy', 'seedHash', 'seedJson', 'deleteStatus', 'showIn',
    ];

    protected $casts = [
        'keywords' => 'array',
        'heroImage' => 'array',
        'blocks' => 'array',
        'publishedAt' => 'datetime',
        'editedAt' => 'datetime',
        'sortOrder' => 'integer',
        'deleteStatus' => 'integer',
    ];

    /**
     * Live on the public site. A blog post meant only for the members'
     * Technician's Blog (showIn = tech) is not: App\Support\TechBlog reads
     * those itself.
     */
    public function scopeLive($q)
    {
        return $q->where('deleteStatus', 1)->where('status', 'published')
            ->where(fn ($w) => $w->whereNull('showIn')->orWhere('showIn', '!=', 'tech'));
    }
}
