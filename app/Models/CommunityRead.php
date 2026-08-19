<?php

namespace App\Models;

/**
 * When a member last looked at one thing: a discussion, or the blog.
 *
 * Deliberately generic — `kind` plus an optional `refId` — so a new corner of
 * the community earns a badge without earning a migration.
 */
class CommunityRead extends BaseModel
{
    protected $table = 'as_community_reads';

    protected $fillable = ['userId', 'kind', 'refId', 'lastReadAt'];

    protected $casts = ['lastReadAt' => 'datetime'];
}
