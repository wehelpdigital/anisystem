<?php

namespace App\Console\Commands;

use App\Models\AsAutoSeason;
use App\Services\AutoMorning;
use Illuminate\Console\Command;

/**
 * Anee Auto's morning for every running season (2026-10-12): the plan
 * placed again with the new forecast, the day said in the chat, and the
 * owner and the workers mailed. Runs at five, Manila time; a season already
 * done today is skipped, so a second run is harmless.
 */
class AutoDailyCommand extends Command
{
    protected $signature = 'auto:daily {--season= : Only this Anee Auto season} {--no-mail : Plan and brief, but send no email}';

    protected $description = 'Anee Auto: re-plan each running season, brief the farmer and mail the day';

    public function handle(AutoMorning $morning): int
    {
        $q = AsAutoSeason::where('status', AsAutoSeason::ACTIVE)->where('deleteStatus', 1);
        if ($this->option('season')) {
            $q->where('id', (int) $this->option('season'));
        }
        $n = 0;
        foreach ($q->orderBy('id')->get() as $auto) {
            try {
                $r = $morning->run($auto, ! $this->option('no-mail'));
                $this->line('#' . $auto->id . ' moved ' . $r['moved'] . ', mailed ' . $r['emails']);
                $n++;
            } catch (\Throwable $e) {
                report($e);
                $this->error('#' . $auto->id . ' ' . $e->getMessage());
            }
        }
        $this->info($n . ' season(s).');

        return self::SUCCESS;
    }
}
