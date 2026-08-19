<?php

namespace App\Models;

/**
 * A post somebody kept for later. Private by design: nothing about a bookmark
 * is visible to whoever wrote the post.
 *
 * `targetType` is a type rather than a table so a saved discussion or blog
 * article costs a row, not a migration.
 */
class CommunityBookmark extends BaseModel
{
    protected $table = 'as_community_bookmarks';

    protected $fillable = ['userId', 'targetType', 'targetId', 'deleteStatus'];

    public const TYPE_WALL = 'wall';
}
