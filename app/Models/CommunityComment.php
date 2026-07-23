<?php

namespace App\Models;

/**
 * A question or comment left on a published cropping plan. A reply carries the
 * id of the comment it answers in `parentId`; top-level entries leave it null.
 */
class CommunityComment extends BaseModel
{
    protected $table = 'as_community_comments';

    protected $fillable = [
        'croppingScheduleId',
        'anisystemUserId',
        'parentId',
        'body',
        'isQuestion',
        'deleteStatus',
    ];

    protected $casts = [
        'parentId' => 'integer',
        'isQuestion' => 'boolean',
        'deleteStatus' => 'integer',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'anisystemUserId');
    }

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parentId')
            ->where('as_community_comments.deleteStatus', 1)
            ->orderBy('id');
    }
}
