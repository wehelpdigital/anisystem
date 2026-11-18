<?php

namespace App\Console\Commands;

use App\Http\Controllers\SkyWeatherController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Satellite Weather's batch (2026-10-12): the national forecast grid made
 * once per 3 hour block, before anyone asks, while the page is in use (opened
 * in the last two days). Opening the page then reads what is kept; a block
 * nobody prepared is fetched once on the first opening (SkyWeatherController::grid).
 * It ran only while a farm held a week pass until 2026-11-18, when Satellite
 * Weather became part of the plans.
 */
class SkyBatchCommand extends Command
{
    protected $signature = 'sky:batch {--force : Run even if nobody opened the page lately}';

    protected $description = 'Satellite Weather: fetch the national forecast grid for this 3 hour block, if the page was opened in the last two days';

    public function handle(): int
    {
        $last = (int) Cache::get('sky:lastOpened', 0);
        if (! $this->option('force') && $last < time() - 2 * 86400) {
            $this->line('Nobody opened Satellite Weather in two days: nothing fetched.');

            return self::SUCCESS;
        }
        [$lat, $lng] = SkyWeatherController::PH_CENTER;
        $g = SkyWeatherController::grid($lat, $lng, true);
        $this->info('National grid: ' . (count($g['cloud'] ?? []) ?: 0) . ' frames.');

        return self::SUCCESS;
    }
}
