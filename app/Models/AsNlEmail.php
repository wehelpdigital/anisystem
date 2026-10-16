<?php

namespace App\Models;

use App\Support\Newsletter\Seg;

/**
 * One newsletter email (2026-10-17): its priority and place in that line,
 * its words (the builder's blocks, or an Acumbamail template by id), who it
 * is for, the day or hour it is bound to, and which segments have had it.
 *
 *   priority  high     the next daily send, or its own day (sendOn), or its
 *                      own exact time (sendAt, beside the day's email)
 *             news     today's farm news; gone at the end of its day
 *             regular  the long line, one a day, in order
 *   status    draft    kept out of the line
 *             queued   in the line
 *             partial  some of its segments had it, the rest wait
 *             sent     every segment it was for had it
 *             expired  its day passed before every segment had it
 */
class AsNlEmail extends BaseModel
{
    protected $table = 'as_nl_emails';

    public const PRIORITIES = ['high', 'news', 'regular'];

    public const STATUSES = ['draft', 'queued', 'partial', 'sent', 'expired'];

    /** Still waiting to go to someone. */
    public const LIVE = ['queued', 'partial'];

    protected $fillable = [
        'priority', 'position', 'kind', 'title', 'subject', 'preheader', 'blocks', 'acumbaTemplateId',
        'audience', 'sendOn', 'sendAt', 'newsDate', 'newsPageId', 'status', 'sentCells', 'lastSentAt',
        'note', 'meta', 'createdBy', 'updatedBy', 'deleteStatus',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'blocks' => 'array',
            'audience' => 'array',
            'sentCells' => 'array',
            'meta' => 'array',
            'sendOn' => 'date:Y-m-d',
            'sendAt' => 'datetime:Y-m-d H:i:s',
            'newsDate' => 'date:Y-m-d',
            'lastSentAt' => 'datetime:Y-m-d H:i:s',
            'position' => 'integer',
            'acumbaTemplateId' => 'integer',
            'newsPageId' => 'integer',
            'deleteStatus' => 'integer',
        ]);
    }

    /** The segments this email is for, cleaned ("all" when nothing says otherwise). */
    public function aud(): array
    {
        return Seg::normalize($this->audience);
    }

    public function sent(): array
    {
        return array_values(array_filter((array) $this->sentCells, 'is_string'));
    }

    public function isTimed(): bool
    {
        return $this->priority === 'high' && $this->sendAt !== null;
    }
}
