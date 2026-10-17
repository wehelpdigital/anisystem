<?php

namespace App\Console\Commands;

use App\Support\HazardBook;
use App\Support\HazardFeed;
use Illuminate\Console\Command;

/**
 * hazards:refresh (2026-10-18): keep the hazard book (App\Support\HazardBook)
 * fresh from the same open data it was built from, with no paid AI. Monthly
 * by the scheduler: the running year's storms, weather, reported disasters
 * and quakes, then the year lines again (the running year is read again
 * well before ClimateRecord's 45 days, so no analysis falls back to a web
 * search).
 *
 *   hazards:refresh                 everything
 *   hazards:refresh --only=storms   storms, weather, reports or quakes (comma list)
 *   hazards:refresh --load          load the committed book (database/hazards) first
 *   hazards:refresh --rollup        only the year lines again
 */
class HazardsRefreshCommand extends Command
{
    protected $signature = 'hazards:refresh {--only= : storms,weather,reports,quakes} {--load : load the committed book first} {--rollup : only rebuild the year lines}';

    protected $description = 'Add the new storms and events of the running year to the hazard book';

    public function handle(): int
    {
        if (! HazardBook::tables()) {
            $this->warn('The hazard book tables are not there yet.');

            return self::SUCCESS;
        }
        $say = fn (string $m) => $this->line($m);
        if ($this->option('load') || ! HazardBook::ready()) {
            $this->info('Loading the committed book: ' . json_encode(HazardBook::load(null, $say)));
        }
        if ($this->option('rollup')) {
            $this->info('Year lines: ' . HazardBook::rollup(null, $say));

            return self::SUCCESS;
        }
        $parts = $this->option('only') ? array_map('trim', explode(',', (string) $this->option('only'))) : ['storms', 'weather', 'reports', 'quakes'];
        $out = HazardFeed::run($parts, $say);
        $this->info('Refreshed: ' . json_encode($out));

        return self::SUCCESS;
    }
}
