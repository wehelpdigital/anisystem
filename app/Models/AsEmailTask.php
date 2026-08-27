<?php

namespace App\Models;

/**
 * One email this system means to send.
 *
 * Every message goes through a row here — the reset link somebody is waiting
 * on, the day's tasks a worker gets in the morning, the one an owner sent by
 * hand off a day card. The mother app lists them, the cron drains them, and a
 * failure keeps its reason instead of vanishing.
 */
class AsEmailTask extends BaseModel
{
    protected $table = 'as_email_tasks';

    public const QUEUED = 'queued';
    public const SENT = 'sent';
    public const FAILED = 'failed';

    /** After this many tries a row stops being picked up; the reason stays. */
    public const MAX_ATTEMPTS = 4;

    protected $fillable = [
        'groupKey', 'templateKey', 'toEmail', 'toName', 'subject', 'bodyHtml',
        'status', 'attempts', 'lastError', 'providerId', 'sendAfter', 'sentAt',
        'relatedType', 'relatedId', 'croppingScheduleId', 'createdByUserId',
        'deleteStatus',
    ];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'attempts' => 'integer',
            'sendAfter' => 'datetime',
            'sentAt' => 'datetime',
            'deleteStatus' => 'integer',
        ]);
    }

    /**
     * What the cron asks for: due, still owed, oldest first.
     *
     * A row that has run out of attempts is left alone — it stays in the book
     * with its reason so somebody can see what went wrong, rather than being
     * retried into the same wall every morning for a month.
     */
    public function scopeDue($q, ?\DateTimeInterface $now = null)
    {
        $now = $now ?: now();

        return $q->where('deleteStatus', 1)
            ->whereIn('status', [self::QUEUED, self::FAILED])
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->where(fn ($w) => $w->whereNull('sendAfter')->orWhere('sendAfter', '<=', $now))
            ->orderBy('sendAfter')
            ->orderBy('id');
    }

    /** A short word for the mother app's list. */
    public function statusLabel(): string
    {
        return match ($this->status) {
            self::SENT => 'Sent',
            self::FAILED => $this->attempts >= self::MAX_ATTEMPTS ? 'Given up' : 'Failed — will retry',
            default => $this->sendAfter && $this->sendAfter->isFuture() ? 'Scheduled' : 'Waiting',
        };
    }
}
