<?php

namespace App\Services;

use App\Models\AsEmailTask;
use Illuminate\Support\Facades\Log;

/**
 * The mail book, and the thing that empties it.
 *
 * Nothing in this app talks to Resend directly. A message becomes a row here
 * first, and then either goes at once — because somebody is standing there
 * waiting for a reset link — or waits for its hour, which is what makes a
 * daily blast possible at all.
 *
 * The row is written BEFORE the attempt, not after. A send that throws still
 * leaves a line in the book saying who it was for and what went wrong; the
 * alternative is an email that never arrives and no record that it was ever
 * meant to.
 */
class EmailQueue
{
    /** Resend's own ceiling is higher; this is about not hanging a cron. */
    public const PER_RUN = 50;

    /**
     * Put a message in the book.
     *
     * `when` null means "as soon as anything drains the queue". Pass a time
     * for anything that belongs to a particular morning.
     */
    public function queue(
        string $toEmail,
        string $toName,
        string $subject,
        string $bodyHtml,
        array $about = [],
        ?\DateTimeInterface $when = null
    ): ?AsEmailTask {
        $to = trim($toEmail);
        // Nothing is queued for an address that cannot receive it. A row that
        // can only ever fail is noise in a list somebody has to read.
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return AsEmailTask::create([
            'groupKey' => $about['groupKey'] ?? config('anisystem.mail_group', 'AniSystem'),
            'templateKey' => $about['templateKey'] ?? null,
            'toEmail' => $to,
            'toName' => $toName ?: null,
            'subject' => mb_substr($subject, 0, 255),
            'bodyHtml' => $bodyHtml,
            'status' => AsEmailTask::QUEUED,
            'sendAfter' => $when,
            'relatedType' => $about['relatedType'] ?? null,
            'relatedId' => $about['relatedId'] ?? null,
            'croppingScheduleId' => $about['croppingScheduleId'] ?? null,
            'createdByUserId' => $about['createdByUserId'] ?? auth()->id(),
            'deleteStatus' => 1,
        ]);
    }

    /**
     * Put it in the book and try it now.
     *
     * For anything a person is waiting on. Returns whether it left; the row
     * survives either way, and a failure is retried by the next cron run.
     */
    public function queueAndSend(
        string $toEmail,
        string $toName,
        string $subject,
        string $bodyHtml,
        array $about = []
    ): bool {
        $task = $this->queue($toEmail, $toName, $subject, $bodyHtml, $about);

        return $task ? $this->attempt($task) : false;
    }

    /**
     * Empty as much of the book as the caller will allow.
     *
     * Capped because a cron that decides to send four thousand emails is a
     * cron that times out halfway and leaves nobody able to say which half.
     *
     * @return array{tried: int, sent: int, failed: int}
     */
    public function drain(int $limit = self::PER_RUN): array
    {
        $limit = max(1, min($limit, 500));
        $rows = AsEmailTask::due()->limit($limit)->get();

        $out = ['tried' => 0, 'sent' => 0, 'failed' => 0];
        foreach ($rows as $task) {
            $out['tried']++;
            $this->attempt($task) ? $out['sent']++ : $out['failed']++;
        }

        return $out;
    }

    /**
     * One try at one row.
     *
     * The attempt is counted before the send, so a message that kills the
     * process mid-flight cannot be retried for ever.
     */
    public function attempt(AsEmailTask $task): bool
    {
        $task->attempts = (int) $task->attempts + 1;
        $task->save();

        /* One instance, held.
         *
         * ResendMailer keeps the reason for a failure on itself, and asking
         * the container for it a second time hands back a NEW one that has
         * never failed at anything — so every failure was recorded as the
         * useless "Send failed." instead of Resend's own sentence, which is
         * the entire answer to why nothing arrived. */
        $mailer = app(ResendMailer::class);
        $err = null;

        try {
            $id = $mailer->send(
                $task->toEmail,
                $task->toName ?: '',
                $task->subject,
                $task->bodyHtml
            );
        } catch (\Throwable $e) {
            $id = null;
            $err = $e->getMessage();
        }

        if (! empty($id)) {
            $task->forceFill([
                'status' => AsEmailTask::SENT,
                'providerId' => is_string($id) ? mb_substr($id, 0, 120) : null,
                'sentAt' => now(),
                'lastError' => null,
            ])->save();

            return true;
        }

        $task->forceFill([
            'status' => AsEmailTask::FAILED,
            'lastError' => mb_substr($err ?: ($mailer->lastError() ?: 'Send failed.'), 0, 2000),
        ])->save();

        Log::warning("EmailQueue: task {$task->id} to {$task->toEmail} failed: {$task->lastError}");

        return false;
    }
}
