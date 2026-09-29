<?php

namespace App\Models;

/**
 * One "Analyze by Anee" run on a protocol, kept on the builder's Analyses
 * tab: the review itself, the version it read, and what it cost. The
 * latest also rides on the protocol row (see the Protocol Builder).
 */
class AsProtocolAnalysis extends BaseModel
{
    protected $table = 'as_protocol_analyses';

    protected $fillable = [
        'protocolId', 'userId', 'versionId', 'versionName', 'score', 'analysis', 'credits', 'deleteStatus',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'analysis' => 'array',
            'score' => 'integer',
            'credits' => 'decimal:2',
            'deleteStatus' => 'integer',
        ]);
    }
}
