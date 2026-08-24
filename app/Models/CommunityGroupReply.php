<?php

namespace App\Models;

class CommunityGroupReply extends BaseModel
{
    protected $table = 'as_community_group_replies';

    protected $fillable = ['postId', 'parentId', 'userId', 'body', 'imagePath', 'imagePaths', 'isDeleted', 'deleteStatus'];

    protected $casts = ['isDeleted' => 'boolean', 'isRestricted' => 'boolean', 'imagePaths' => 'array'];

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
