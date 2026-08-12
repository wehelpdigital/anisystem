<?php

namespace App\Console\Commands;

use App\Models\AsCroppingSchedule;
use App\Services\DailyDigestService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Runs every hour and sends the digests whose hour has come round.
 *
 * Hourly rather than daily because each schedule picks its own send time, and
 * a farm that starts at five should not be told at eight. The last-sent date
 * is what makes a re-run — or a second worker on the same cron — harmless.
 */
class SendDailyDigests extends Command
{
    protected $signature = 'digests:send
                            {--schedule= : Only this schedule id}
                            {--force : Ignore the hour and the already-sent-today guard}';

    protected $description = "Email each schedule's workers and owner what is on today and tomorrow";

    public function handle(DailyDigestService $digests): int
    {
        $now = Carbon::now('Asia/Manila');
        $today = $now->copy()->startOfDay();

        $query = AsCroppingSchedule::active()
            ->where(fn ($q) => $q->where('notifyWorkersDaily', true)->orWhere('notifyOwnerDaily', true));

        if ($this->option('schedule')) {
            $query->where('id', (int) $this->option('schedule'));
        }

        $force = (bool) $this->option('force');
        $totalSent = 0;

        foreach ($query->with('workers')->get() as $schedule) {
            if (! $force) {
                if ((int) $schedule->notifyHour !== (int) $now->hour) {
                    continue;
                }
                if ($schedule->notifyLastSentDate
                    && $schedule->notifyLastSentDate->toDateString() === $today->toDateString()) {
                    continue;            // already gone out today
                }
            }

            $result = $digests->sendFor($schedule, $today);
            $schedule->forceFill(['notifyLastSentDate' => $today->toDateString()])->save();
            $totalSent += $result['sent'];

            $this->line(sprintf(
                '#%d %s — sent %d, skipped %d',
                $schedule->id, $schedule->title, $result['sent'], $result['skipped']
            ));
        }

        $this->info("Done. {$totalSent} message(s) sent.");

        return self::SUCCESS;
    }
}
