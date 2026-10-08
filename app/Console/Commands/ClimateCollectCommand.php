<?php

namespace App\Console\Commands;

use App\Support\ClimateStore;
use App\Support\PlaceSearch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The climate shelf's collector (2026-10-08), run by the scheduler:
 *
 *   climate:collect --enso      read NOAA's ENSO state and keep it (daily)
 *   climate:collect             a few weather cells a run (hourly): first
 *                               every Philippine province that has none yet
 *                               (found once on the map), then the cells whose
 *                               history is more than a week old, the most
 *                               used first
 *
 * NPK Plus then reads the season from the shelf, asking nothing outside.
 */
class ClimateCollectCommand extends Command
{
    protected $signature = 'climate:collect {--enso : read the ENSO state} {--limit=6 : weather cells a run}';

    protected $description = 'Collect ENSO and weather history for NPK Plus';

    public function handle(): int
    {
        if (! Schema::hasTable('as_weather_cells')) {
            $this->warn('The climate tables are not there yet.');

            return self::SUCCESS;
        }
        if ($this->option('enso')) {
            $e = ClimateStore::refreshEnso();
            $this->info($e ? 'ENSO: ' . $e['label'] . ' (ONI ' . $e['oni'] . ', ' . $e['season'] . ')' : 'ENSO could not be read.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $done = 0;
        // Every province, once: its cell, from its name on the map.
        $provinces = array_keys((array) json_decode((string) @file_get_contents(public_path('data/ph-locations.json')), true));
        $have = DB::table('as_weather_cells')->whereNotNull('label')->pluck('label')->map(fn ($l) => mb_strtolower($l))->all();
        foreach ($provinces as $p) {
            if ($done >= $limit) {
                break;
            }
            if (in_array(mb_strtolower($p), $have, true)) {
                continue;
            }
            $hit = PlaceSearch::find($p . ', Philippines')[0] ?? null;
            if (! $hit) {
                $this->line('Not found: ' . $p);
                continue;
            }
            $cell = ClimateStore::cell((float) $hit['lat'], (float) $hit['lng'], $p);
            if (! $cell->label) {
                DB::table('as_weather_cells')->where('id', $cell->id)->update(['label' => $p]);
            }
            if (! $cell->fetchedThrough) {
                $n = ClimateStore::fill($cell);
                $this->line($p . ': ' . $n . ' weeks');
                $done++;
            }
        }
        // Then the stale ones, the most used first.
        $stale = DB::table('as_weather_cells')->where(fn ($q) => $q->whereNull('fetchedThrough')->orWhere('fetchedThrough', '<', now('Asia/Manila')->subDays(14)->toDateString()))
            ->orderByRaw('lastUsedAt is null')->orderByDesc('lastUsedAt')->limit(max(0, $limit - $done))->get();
        foreach ($stale as $cell) {
            $n = ClimateStore::fill($cell);
            $this->line(($cell->label ?: $cell->cellKey) . ': ' . $n . ' weeks refreshed');
        }
        $this->info('Cells: ' . DB::table('as_weather_cells')->count() . ', filled ' . DB::table('as_weather_cells')->whereNotNull('fetchedThrough')->count());

        return self::SUCCESS;
    }
}
