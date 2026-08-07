<?php

namespace App\Models;

class CommunityMessage extends BaseModel
{
    protected $table = 'as_community_messages';

    protected $fillable = ['senderId', 'recipientId', 'replyToId', 'body', 'imagePath', 'isRead', 'deleteStatus'];

    protected $casts = ['isRead' => 'boolean'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'senderId');
    }
}
