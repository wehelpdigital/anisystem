<?php

namespace App\Models;

/**
 * A member's own crop protocol — see the Protocol Builder.
 *
 * The crop, the count, the tags and Anee's review live here; the tasks,
 * materials, rules and files live on its versions (AsProtocolVersion),
 * `versionId` naming the one in use. `tasks`/`history`/`rev` on this row
 * are what the protocol held before versions came, kept only as the seed
 * of its Version 1.
 */
class AsProtocol extends BaseModel
{
    protected $table = 'as_protocols';

    protected $fillable = [
        'userId', 'title', 'description', 'tags', 'crop', 'variety', 'dayType',
        'tasks', 'history', 'rev', 'versionId',
        'analysis', 'analysisStatus', 'analysisError', 'analysisCredits', 'analysisAt', 'analysisBeatAt',
        'portedScheduleId', 'portedAt', 'deleteStatus',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tasks' => 'array',
            'tags' => 'array',
            'history' => 'array',
            'analysis' => 'array',
            'rev' => 'integer',
            'versionId' => 'integer',
            'analysisCredits' => 'float',
            'analysisAt' => 'datetime',
            'analysisBeatAt' => 'datetime',
            'portedAt' => 'datetime',
        ]);
    }
}
