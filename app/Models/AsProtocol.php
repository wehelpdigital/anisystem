<?php

namespace App\Models;

/**
 * A member's own crop protocol — see the Protocol Builder.
 */
class AsProtocol extends BaseModel
{
    protected $table = 'as_protocols';

    protected $fillable = [
        'userId', 'title', 'description', 'crop', 'variety', 'dayType',
        'tasks', 'history', 'rev',
        'analysis', 'analysisStatus', 'analysisError', 'analysisCredits', 'analysisAt', 'analysisBeatAt',
        'portedScheduleId', 'portedAt', 'deleteStatus',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tasks' => 'array',
            'history' => 'array',
            'analysis' => 'array',
            'rev' => 'integer',
            'analysisCredits' => 'float',
            'analysisAt' => 'datetime',
            'analysisBeatAt' => 'datetime',
            'portedAt' => 'datetime',
        ]);
    }
}
