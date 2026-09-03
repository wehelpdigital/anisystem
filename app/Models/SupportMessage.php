<?php

namespace App\Models;

class SupportMessage extends BaseModel
{
    protected $table = 'as_support_messages';

    protected $fillable = ['ticketId', 'authorType', 'authorId', 'authorName', 'body', 'bodyFormat', 'deleteStatus'];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticketId');
    }
}
