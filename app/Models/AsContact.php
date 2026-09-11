<?php

namespace App\Models;

/**
 * One entry in a member's own Contact List — the farm phonebook. Always
 * queried through the owner's userId; tags are a plain JSON list of words
 * the member chose ("Worker", "Tractor Rental", "Buyer"…).
 */
class AsContact extends BaseModel
{
    protected $table = 'as_contacts';

    protected $fillable = [
        'userId', 'name', 'phone', 'phones', 'email', 'emails', 'company',
        'address', 'address2', 'province', 'town', 'notes', 'tags', 'deleteStatus',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tags' => 'array',
            // A person rarely has one number. `phone`/`email` keep the first
            // of each list so the row's call/text/mail buttons and the SQL
            // search have a plain column to read — see the controller, which
            // mirrors them on every write.
            'phones' => 'array',
            'emails' => 'array',
        ]);
    }
}
