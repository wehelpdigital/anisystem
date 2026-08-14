<?php

namespace App\Models;

/**
 * A mark that one person has seen one team message.
 *
 * Kept as its own row rather than a list on the message: five phones can
 * each add their own mark at the same moment without any of them
 * overwriting the others, and "who has seen this" is then a plain query.
 */
class ScheduleChatRead extends BaseModel
{
    protected $table = 'as_schedule_message_reads';

    protected $fillable = [
        'messageId',
        'userId',
        'seenAt',
    ];

    protected $casts = [
        'messageId' => 'integer',
        'userId' => 'integer',
        'seenAt' => 'datetime',
    ];

    public function reader()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
