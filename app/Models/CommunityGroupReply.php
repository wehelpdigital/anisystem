<?php

namespace App\Models;

class CommunityGroupReply extends BaseModel
{
    protected $table = 'as_community_group_replies';

    protected $fillable = ['postId', 'parentId', 'userId', 'body', 'imagePath', 'imagePaths',
        'videoPath', 'videoPoster', 'videoPaths', 'isDeleted', 'deleteStatus'];

    protected $casts = ['isDeleted' => 'boolean', 'isRestricted' => 'boolean',
        'imagePaths' => 'array', 'videoPaths' => 'array'];

    /**
     * Every clip on this answer, first one first.
     *
     * Each entry is ['video' => path, 'poster' => path|null] — a clip picked
     * out of the gallery has no poster of its own, and a player showing its
     * own first frame is the right answer to that rather than a stored blank.
     */
    public function clips(): array
    {
        $many = array_values(array_filter((array) ($this->videoPaths ?? []), fn ($c) => ! empty($c['video'])));
        if ($many) {
            return $many;
        }

        return $this->videoPath ? [['video' => $this->videoPath, 'poster' => $this->videoPoster]] : [];
    }

    /**
     * Every picture on this answer, first one first — the twin of
     * CommunityWallComment::shots(). An answer written before the column
     * existed has only imagePath, and that is a set of one.
     */
    public function shots(): array
    {
        $many = array_values(array_filter((array) ($this->imagePaths ?? [])));
        if ($many) {
            return $many;
        }

        return $this->imagePath ? [$this->imagePath] : [];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parentId')->where('deleteStatus', 1);
    }
}
