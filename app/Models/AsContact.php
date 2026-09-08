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
        'userId', 'name', 'phone', 'email', 'company', 'address', 'notes', 'tags', 'deleteStatus',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tags' => 'array',
        ]);
    }
}
