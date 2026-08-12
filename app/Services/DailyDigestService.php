<?php

namespace App\Services;

use App\Mail\ScheduleDayDigest;
use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * The morning email: what is on today, and what is coming tomorrow.
 *
 * A worker is sent only the work they are actually on — a list of everyone
 * else's jobs is noise, and noise is what makes people stop reading these.
 * The owner gets the whole day.
 *
 * Sending goes through {@see AniSystemMailer}, which uses the SMTP credentials
 * configured in the mother app rather than this app's own mail config.
 */
class DailyDigestService
{
    public function __construct(private AniSystemMailer $mailer)
    {
    }

    /**
     * Send one schedule's digest.
     *
     * @return array{sent:int,skipped:int} how many messages went out
     */
    public function sendFor(AsCroppingSchedule $schedule, ?Carbon $today = null, bool $ownerOnly = false): array
    {
        $today = ($today ?: Carbon::now('Asia/Manila'))->copy()->startOfDay();
        $tomorrow = $today->copy()->addDay();

        $activities = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('isDraft', 0)
            ->where('isHidden', 0)
            ->whereIn('targetDate', [$today->toDateString(), $tomorrow->toDateString()])
            ->with(['lots', 'workers'])
            ->orderBy('targetDate')
            ->orderBy('sequenceOrder')
            ->get();

        // Nothing on either day is worth nobody's inbox.
        if ($activities->isEmpty()) {
            return ['sent' => 0, 'skipped' => 0];
        }

        $sent = 0;
        $skipped = 0;

        if ($schedule->notifyOwnerDaily || $ownerOnly) {
            $owner = User::find($schedule->anisystemUserId ?: $schedule->usersId);
            if ($owner && filled($owner->email)) {
                $this->deliver($schedule, $owner->email, $owner->full_name ?? 'there', $activities, $today, $tomorrow)
                    ? $sent++ : $skipped++;
            } else {
                $skipped++;
            }
        }

        if ($schedule->notifyWorkersDaily && ! $ownerOnly) {
            foreach ($schedule->workers as $worker) {
                if (blank($worker->email)) {
                    $skipped++;          // no address on file; not an error
                    continue;
                }
                $theirs = $activities->filter(
                    fn ($a) => $a->workers->contains(fn ($w) => (int) $w->id === (int) $worker->id)
                );
                if ($theirs->isEmpty()) {
                    continue;            // nothing of theirs on either day
                }
                $this->deliver($schedule, $worker->email, $worker->workerName, $theirs, $today, $tomorrow)
                    ? $sent++ : $skipped++;
            }
        }

        return ['sent' => $sent, 'skipped' => $skipped];
    }

    /** One message. A transport failure is logged, never thrown at the runner. */
    private function deliver(
        AsCroppingSchedule $schedule,
        string $email,
        string $name,
        $activities,
        Carbon $today,
        Carbon $tomorrow
    ): bool {
        try {
            $this->mailer->send($email, $name, new ScheduleDayDigest(
                scheduleTitle: (string) $schedule->title,
                dateLabel: $today->format('l, M j'),
                workerName: $name,
                activities: $this->shape($activities, $today, $tomorrow),
                publicUrl: null,
            ));

            return true;
        } catch (\Throwable $e) {
            // One bad address must not stop the rest of the farm being told.
            Log::warning('Daily digest failed', [
                'schedule' => $schedule->id, 'to' => $email, 'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Flatten to what the template needs, with the day each activity belongs
     * to spelled out — "today" and "tomorrow" in one list is only useful if
     * you can tell which is which.
     *
     * @return array<int, array{title:string,tags:string,description:?string}>
     */
    private function shape($activities, Carbon $today, Carbon $tomorrow): array
    {
        return $activities->map(function ($a) use ($today, $tomorrow) {
            $when = $a->targetDate?->toDateString() === $today->toDateString()
                ? 'Today'
                : ($a->targetDate?->toDateString() === $tomorrow->toDateString() ? 'Tomorrow' : '');
            $lots = $a->lots->pluck('lotName')->filter()->implode(', ');

            return [
                'title' => (string) $a->activityTitle,
                'tags' => trim($when . ($lots ? ' · ' . $lots : '')),
                'description' => filled($a->description) ? strip_tags((string) $a->description) : null,
            ];
        })->values()->all();
    }
}
