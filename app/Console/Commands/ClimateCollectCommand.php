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
 *   climate:collect --enso      read NOAA's ENSO state and keep it, with the
 *                               whole ONI history (daily)
 *   climate:collect             a few weather cells a run (hourly): first
 *                               every Philippine province that has none yet
 *                               (found once on the map), then the cells that
 *                               still lack their twenty years or the extremes
 *                               (2026-10-18), then the cells whose history is
 *                               more than two weeks old; the most used first,
 *                               and never past the day's archive budget
 *
 * NPK Plus then reads the season from the shelf, asking nothing outside.
 */
class ClimateCollectCommand extends Command
{
    protected $signature = 'climate:collect {--enso : read the ENSO state} {--limit=6 : weather cells a run} {--whole=1 : cells a run brought to the twenty years}';

    protected $description = 'Collect ENSO and twenty years of weather history for the climate shelf';

    public function handle(): int
    {
        if (! Schema::hasTable('as_weather_cells')) {
            $this->warn('The climate tables are not there yet.');

            return self::SUCCESS;
        }
        if ($this->option('enso')) {
            // The ONI, its history and the advisory, kept for every analysis (they read the kept ones while fresh, 2026-10-19).
            $e = ClimateStore::refreshEnso();
            $this->info($e ? 'ENSO: ' . $e['label'] . ' (ONI ' . $e['oni'] . ', ' . $e['season'] . ')' : 'ENSO could not be read.');
            $a = \App\Support\EnsoOutlook::advisory();
            $this->line($a ? 'Advisory of ' . ($a['issuedOn'] ?? '?') . ': ' . ($a['alert'] ?? 'no alert') . ($a['nextOn'] ?? null ? ', next on ' . $a['nextOn'] : '') : 'No advisory kept.');
            $gone = \App\Support\OutlookShelf::prune();
            if ($gone) {
                $this->line('Outlooks past their use removed: ' . $gone);
            }

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));
        $done = 0;
        $full = ClimateStore::fullWeight();
        // Every province, once: its cell, from its name on the map.
        $provinces = array_keys((array) json_decode((string) @file_get_contents(public_path('data/ph-locations.json')), true));
        $have = DB::table('as_weather_cells')->whereNotNull('label')->pluck('label')->map(fn ($l) => mb_strtolower($l))->all();
        foreach ($provinces as $p) {
            if ($done >= $limit || ClimateStore::budgetLeft() < $full) {
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
        // Then the cells from before, brought to the twenty years and the
        // extremes, a few a run: one whole read is about 550 of the free
        // plan's 10,000 calls a day.
        if (ClimateStore::extremes()) {
            $whole = max(0, (int) $this->option('whole'));
            $old = DB::table('as_weather_cells')->whereNotNull('fetchedThrough')
                ->where(fn ($q) => $q->where('shape', '<', ClimateStore::SHAPE)->orWhereNull('fetchedFrom')->orWhere('fetchedFrom', '>', ClimateStore::start()->toDateString()))
                ->orderByRaw('lastUsedAt is null')->orderByDesc('lastUsedAt')->orderBy('id')->limit($whole)->get();
            foreach ($old as $cell) {
                if ($done >= $limit || ClimateStore::budgetLeft() < $full) {
                    $this->line('Archive budget for today is spent; the rest wait.');
                    break;
                }
                $n = ClimateStore::fill($cell);
                $this->line(($cell->label ?: $cell->cellKey) . ': ' . $n . ' weeks, twenty years');
                $done++;
            }
        }
        // Then the stale ones, the most used first.
        $stale = DB::table('as_weather_cells')->where(fn ($q) => $q->whereNull('fetchedThrough')->orWhere('fetchedThrough', '<', now('Asia/Manila')->subDays(14)->toDateString()))
            ->orderByRaw('lastUsedAt is null')->orderByDesc('lastUsedAt')->limit(max(0, $limit - $done))->get();
        foreach ($stale as $cell) {
            // A cell that still lacks its twenty years would be read whole: only while the budget allows.
            if (ClimateStore::lacks($cell) !== null && ClimateStore::budgetLeft() < $full) {
                continue;
            }
            $n = ClimateStore::fill($cell);
            $this->line(($cell->label ?: $cell->cellKey) . ': ' . $n . ' weeks refreshed');
        }
        $this->info('Cells: ' . DB::table('as_weather_cells')->count() . ', filled ' . DB::table('as_weather_cells')->whereNotNull('fetchedThrough')->count()
            . (ClimateStore::extremes() ? ', twenty years ' . DB::table('as_weather_cells')->where('shape', '>=', ClimateStore::SHAPE)->count() : '')
            . '; archive budget left today ' . ClimateStore::budgetLeft());

        return self::SUCCESS;
    }
}
