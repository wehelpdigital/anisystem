<?php

namespace App\Models;

class ContactMessage extends BaseModel
{
    protected $table = 'anisystem_contact_messages';

    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message', 'isRead', 'deleteStatus',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'isRead' => 'boolean',
            'deleteStatus' => 'integer',
        ]);
    }
}
