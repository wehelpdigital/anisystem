<?php

namespace App\Models;

class CommunityWallComment extends BaseModel
{
    protected $table = 'as_community_wall_comments';

    protected $fillable = ['wallPostId', 'parentId', 'userId', 'body', 'imagePath', 'imagePaths', 'videoPath', 'videoPoster', 'isDeleted', 'deleteStatus'];

    protected $casts = ['isDeleted' => 'boolean', 'isRestricted' => 'boolean', 'imagePaths' => 'array'];

    /**
     * Every picture on this comment, first one first.
     *
     * The twin of CommunityWallPost::shots(). A comment written before the
     * column existed — or on a deploy where the migration has not run — has
     * only imagePath, and that is a set of one rather than a special case.
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
