<?php

namespace App\Models;

class CommunityGroupPost extends BaseModel
{
    protected $table = 'as_community_group_posts';

    protected $fillable = ['groupId', 'userId', 'title', 'body', 'imagePath', 'imagePaths',
        'isRestricted', 'restrictedReason', 'deleteStatus', 'videoPath', 'videoPoster', 'videoPaths'];

    protected $casts = ['isRestricted' => 'boolean', 'imagePaths' => 'array', 'videoPaths' => 'array'];

    /**
     * Every picture on this topic, first one first — the same helper the
     * answers and the wall's posts carry, so one renderer shape serves all.
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
     * Every clip, first one first. Each entry is ['video' => path,
     * 'poster' => path|null] — a clip picked out of the gallery has no
     * poster of its own, and the player showing its first frame is the
     * right answer to that.
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
        return $this->belongsTo(User::class, 'userId');
    }

    public function group()
    {
        return $this->belongsTo(CommunityGroup::class, 'groupId');
    }

    public function replies()
    {
        return $this->hasMany(CommunityGroupReply::class, 'postId')->where('as_community_group_replies.deleteStatus', 1);
    }
}
