<?php

namespace App\Models;

class AsCommunityBlogPost extends BaseModel
{
    protected $table = 'as_community_blog_posts';

    protected $fillable = [
        'title', 'slug', 'coverImagePath', 'coverPaths', 'excerpt', 'body',
        'authorName', 'isPublished', 'publishedAt', 'viewCount', 'deleteStatus',
    ];

    protected $casts = [
        'isPublished' => 'boolean',
        'publishedAt' => 'datetime',
        'deleteStatus' => 'integer',
        'coverPaths' => 'array',
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

    /**
     * Every cover this story wears, as [url, motherUrl] pairs, first first.
     *
     * coverPaths is the whole wardrobe; an article that has only ever had
     * its one column still answers with that one, so the card never has to
     * ask which era the row is from. The mother URL rides along for the
     * same reason coverUrlOnMother() exists: the files are uploaded over
     * there, and a fresh deploy here may not hold the local copy.
     *
     * @return list<array{url: string, mother: ?string}>
     */
    public function covers(): array
    {
        $paths = array_values(array_filter(array_map('strval', (array) ($this->coverPaths ?? []))));
        if ($paths === [] && $this->coverImagePath) {
            $paths = [(string) $this->coverImagePath];
        }
        $base = rtrim((string) config('mother.url'), '/');

        return array_map(fn ($p) => [
            'url' => \App\Support\MediaStore::url($p),
            'mother' => ($base !== '' && ! \App\Support\MediaStore::isRemote($p))
                ? $base . '/storage/' . ltrim($p, '/')
                : null,
        ], $paths);
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
