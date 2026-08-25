<?php

namespace App\Models;

class CommunityWallPost extends BaseModel
{
    protected $table = 'as_community_wall_posts';

    protected $fillable = ['wallUserId', 'authorUserId', 'body', 'sharedPostId', 'publicToken', 'imagePath', 'imagePaths', 'videoPath', 'videoPoster', 'videoPaths', 'isReel', 'durationSec', 'audioTitle', 'isRestricted', 'restrictedReason', 'deleteStatus'];

    protected $casts = ['isRestricted' => 'boolean', 'isReel' => 'boolean', 'imagePaths' => 'array', 'videoPaths' => 'array'];

    /**
     * Every picture on this post.
     *
     * imagePath is the first one and always has been; imagePaths is the whole
     * list, and only a post with several carries it. Asked here rather than
     * in each of the three places that draw a post, so none of them has to
     * know which column a photo came from — or to notice that a deploy whose
     * release step skipped the migration has only the old one.
     *
     * @return array<int, string>
     */
    public function shots(): array
    {
        $many = array_values(array_filter((array) ($this->imagePaths ?? [])));
        if ($many) {
            return $many;
        }

        return $this->imagePath ? [$this->imagePath] : [];
    }

    /**
     * The post this one is sharing, if any — loaded with its own author so a
     * shared card can be drawn without a second trip per post.
     */
    /**
     * Wall posts only — a story is not one of them.
     *
     * A story is stored as a wall post because that is how it gets reactions,
     * comments, bookmarks and shares for free. It is not READ as one: it
     * belongs in the rail at the top of the wall, and showing it twice (once
     * as a tile, once as a card with a tall video in it) is the wall telling
     * the same news two ways.
     */
    public function scopeWallOnly($query)
    {
        return $query->where(fn ($q) => $q->whereNull('isReel')->orWhere('isReel', 0));
    }

    public function sharedPost()
    {
        return $this->belongsTo(self::class, 'sharedPostId')->with('author');
    }

    /**
     * Every clip on this post, first one first — the same helper comments,
     * replies and topics carry, so one renderer shape serves all. Each entry
     * is ['video' => path, 'poster' => path|null].
     */
    public function clips(): array
    {
        $many = array_values(array_filter((array) ($this->videoPaths ?? []), fn ($c) => ! empty($c['video'])));
        if ($many) {
            return $many;
        }

        return $this->videoPath ? [['video' => $this->videoPath, 'poster' => $this->videoPoster]] : [];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'authorUserId');
    }

    public function comments()
    {
        return $this->hasMany(CommunityWallComment::class, 'wallPostId')->where('as_community_wall_comments.deleteStatus', 1);
    }

    /**
     * When anything last happened to this post: it was written, somebody
     * answered it, or somebody reacted to it.
     *
     * Written once, here, because the wall both ORDERS by it and PAGES on it
     * — and MySQL will not take a select alias in a WHERE, so the same
     * expression has to appear twice in the same query. Two expressions that
     * have to agree and live apart do not stay agreed.
     */
    public static function lastActivitySql(): string
    {
        return 'GREATEST(
            as_community_wall_posts.created_at,
            COALESCE((SELECT MAX(c.created_at) FROM as_community_wall_comments c
                       WHERE c.wallPostId = as_community_wall_posts.id
                         AND c.deleteStatus = 1), as_community_wall_posts.created_at),
            COALESCE((SELECT MAX(r.created_at) FROM as_community_reactions r
                       WHERE r.targetId = as_community_wall_posts.id
                         AND r.targetType = \'wallpost\'), as_community_wall_posts.created_at)
        )';
    }

    /** Carry that moment on every row, under a name the query can order by. */
    public function scopeWithLastActivity($query)
    {
        /* addSelect, not select.
         *
         * select() REPLACES the column list, and every caller here asks for
         * the comment count before asking for the ordering — so this line
         * quietly threw that subquery away and the whole wall said "0
         * Comments" over threads ten deep. The number only appeared once the
         * sheet had been opened and counted them itself. */
        return $query
            ->addSelect('as_community_wall_posts.*')
            ->selectRaw(self::lastActivitySql() . ' as lastActivityAt');
    }

    /** The pager's cursor: everything quieter than the last row shown. */
    public function scopeQuieterThan($query, $moment)
    {
        return $query->whereRaw(self::lastActivitySql() . ' < ?', [$moment]);
    }
}
