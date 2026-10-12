<?php

namespace App\Console\Commands;

use App\Http\Controllers\SkyWeatherController;
use App\Support\SkyPass;
use Illuminate\Console\Command;

/**
 * Satellite Weather's batch (2026-10-12): the national forecast grid made
 * once per 3 hour block, before anyone asks, and only while some farm holds
 * a pass. Opening the page then reads what is kept; nothing is fetched per
 * visitor (the owner's rule: never render live, process only what paying
 * farms use).
 */
class SkyBatchCommand extends Command
{
    protected $signature = 'sky:batch {--force : Run even with no pass running}';

    protected $description = 'Satellite Weather: fetch the national forecast grid for this 3 hour block, if any farm holds a pass';

    public function handle(): int
    {
        if (! $this->option('force') && ! SkyPass::anyActive()) {
            $this->line('No pass running: nothing fetched.');

            return self::SUCCESS;
        }
        [$lat, $lng] = SkyWeatherController::PH_CENTER;
        $g = SkyWeatherController::grid($lat, $lng, true);
        $this->info('National grid: ' . (count($g['cloud'] ?? []) ?: 0) . ' frames.');

        return self::SUCCESS;
    }
}
