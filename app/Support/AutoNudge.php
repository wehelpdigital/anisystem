<?php

namespace App\Support;

use App\Models\AsAutoSeason;
use App\Models\AsAutoTask;
use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleLot;
use App\Models\User;
use App\Services\MailService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Schema;

/**
 * Anee asks a quiet farmer back (2026-10-28, the owner: "if user is not active in the anee auto, user should be
 * receiving email asking user reminder (have this email template in the mother app editable), and a notice in the
 * dashboard").
 *
 * A running season the farmer has not opened or answered for IDLE_DAYS, with work waiting, gets one email (the
 * auto_inactive template, edited in the mother app) and a bell notice, then no more than one every GAP_DAYS while it
 * stays quiet. The dashboard shows a notice of its own for any running season quiet for NOTICE_DAYS (forDashboard).
 * Opening the season, its feed, or any answer marks it seen (seen()).
 */
final class AutoNudge
{
    public const IDLE_DAYS = 3;

    public const GAP_DAYS = 3;

    public const NOTICE_DAYS = 2;

    public static function ready(): bool
    {
        static $ok = null;

        return $ok ??= Schema::hasTable('as_auto_seasons') && Schema::hasColumn('as_auto_seasons', 'seenAt');
    }

    /** The farmer is here: marked at most every ten minutes (the feed asks often). */
    public static function seen(AsAutoSeason $auto): void
    {
        if (! self::ready() || $auto->status !== AsAutoSeason::ACTIVE) {
            return;
        }
        if (! $auto->seenAt || $auto->seenAt->lt(now()->subMinutes(10))) {
            $auto->forceFill(['seenAt' => now()])->saveQuietly();
        }
    }

    /** When the farmer was last here: seen, else when the season started. */
    public static function lastHere(AsAutoSeason $auto): ?\Carbon\Carbon
    {
        return $auto->seenAt ?? $auto->generatedAt ?? $auto->updated_at;
    }

    /** The work waiting on a season: what was due by today and is not done. */
    public static function waiting(AsAutoSeason $auto)
    {
        return AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->where('status', 'planned')
            ->where('plannedDate', '<=', now('Asia/Manila')->addDays(2)->toDateString())->orderBy('plannedDate')->get();
    }

    /** The scheduler's daily pass: the email and the bell for every quiet season. Returns how many were asked back. */
    public static function run(): int
    {
        if (! self::ready()) {
            return 0;
        }
        $n = 0;
        $mail = app(MailService::class);
        $bell = app(NotificationService::class);
        foreach (AsAutoSeason::where('status', AsAutoSeason::ACTIVE)->where('deleteStatus', 1)->get() as $auto) {
            try {
                $last = self::lastHere($auto);
                if (! $last || $last->gt(now()->subDays(self::IDLE_DAYS))) {
                    continue;
                }
                if ($auto->nudgedAt && ($auto->nudgedAt->gt(now()->subDays(self::GAP_DAYS)) || $auto->nudgedAt->gt($last) && $auto->nudgedAt->gt(now()->subDays(self::GAP_DAYS * 2)))) {
                    continue;
                }
                $waiting = self::waiting($auto);
                if ($waiting->isEmpty()) {
                    continue;
                }
                $owner = User::find($auto->userId);
                $schedule = AsCroppingSchedule::find($auto->croppingScheduleId);
                if (! $owner || ! $schedule) {
                    continue;
                }
                $days = (int) $last->diffInDays(now());
                $lots = AsScheduleLot::whereIn('id', $waiting->pluck('lotId')->filter()->unique())->pluck('lotName', 'id');
                $list = EmailSkin::taskList($waiting->take(5)->map(fn (AsAutoTask $t) => EmailSkin::taskRow(
                    e(trim((AutoBook::KINDS[$t->kind][1] ?? '') . ' ' . $t->title)),
                    e(implode(' · ', array_filter([$t->lotId ? ($lots[$t->lotId] ?? null) : null, $t->plannedDate ? $t->plannedDate->format('D, M j') : null]))),
                    e((string) $t->summary),
                    $t->critical ? 'Cannot wait' : ''
                ))->implode(''));
                $url = route('auto.show', $auto->id);
                if ($owner->email) {
                    $mail->sendTemplate('auto_inactive', $owner->email, (string) ($owner->full_name ?: 'farmer'), [
                        'recipient_name' => e(explode(' ', trim((string) $owner->full_name))[0] ?: 'kaibigan'), 'season_title' => e((string) $schedule->title),
                        'days_away' => (string) $days, 'waiting_count' => (string) $waiting->count(), 'tasks_list' => $list, 'auto_url' => $url, 'app_name' => 'anee.io',
                    ], ['scheduleId' => $schedule->id]);
                }
                $bell->notify((int) $auto->userId, 'auto_inactive', 'Anee misses you on ' . $schedule->title,
                    $waiting->count() . ' task' . ($waiting->count() > 1 ? 's are' : ' is') . ' waiting. Tell her what was done, or what cannot be done, and she plans the next days around it.',
                    route('auto.show', $auto->id, false));
                $auto->forceFill(['nudgedAt' => now()])->saveQuietly();
                $n++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $n;
    }

    /**
     * The dashboard's notice: the farmer's running seasons quiet for NOTICE_DAYS, each with its work waiting.
     *
     * @return list<array{id: int, title: string, days: int, waiting: int, next: ?string, url: string}>
     */
    public static function forDashboard(int $userId): array
    {
        if (! self::ready()) {
            return [];
        }
        $out = [];
        foreach (AsAutoSeason::where('userId', $userId)->where('status', AsAutoSeason::ACTIVE)->where('deleteStatus', 1)->get() as $auto) {
            $last = self::lastHere($auto);
            if (! $last || $last->gt(now()->subDays(self::NOTICE_DAYS))) {
                continue;
            }
            $waiting = self::waiting($auto);
            if ($waiting->isEmpty()) {
                continue;
            }
            $out[] = ['id' => (int) $auto->id, 'title' => (string) (AsCroppingSchedule::find($auto->croppingScheduleId)?->title ?? 'Your season'), 'days' => (int) $last->diffInDays(now()),
                'waiting' => $waiting->count(), 'next' => $waiting->first()?->title, 'url' => route('auto.show', $auto->id)];
        }

        return $out;
    }
}
