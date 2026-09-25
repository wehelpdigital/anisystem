<?php

namespace App\Models;

/**
 * One version of a member's protocol ("Wet season", "Dry season") — its
 * tasks, its materials, its rules-and-notes document and files, and its
 * own undo/redo with its own rev. See the Protocol Builder.
 */
class AsProtocolVersion extends BaseModel
{
    protected $table = 'as_protocol_versions';

    protected $fillable = [
        'protocolId', 'userId', 'name',
        'tasks', 'materials', 'rules', 'files', 'history', 'rev',
        'sortOrder', 'deleteStatus',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tasks' => 'array',
            'materials' => 'array',
            'files' => 'array',
            'history' => 'array',
            'rev' => 'integer',
            'sortOrder' => 'integer',
        ]);
    }
}
