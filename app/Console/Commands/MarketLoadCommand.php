<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Puts the market price data in the database (2026-10-27): the items, the
 * monthly series and the local reports that database/market/build.py made
 * from the owner's collection, and the import and policy events with the
 * yearly import volumes (database/market/news.json). Each part is replaced
 * whole, so running it again after a rebuild is safe.
 *
 *   php artisan market:load            everything
 *   php artisan market:load --news     only the events and import volumes
 */
class MarketLoadCommand extends Command
{
    protected $signature = 'market:load {--news : Only the events and import volumes}';

    protected $description = 'Load the market price items, series, local reports and import events into the database';

    public function handle(): int
    {
        if (! Schema::hasTable('as_market_series')) {
            $this->error('Run the migration first.');

            return self::FAILURE;
        }
        $dir = database_path('market');
        if (! $this->option('news')) {
            $this->items($dir . '/items.json');
            $this->series($dir . '/series.json.gz');
            $this->local($dir . '/local.json.gz');
        }
        $this->news($dir . '/news.json');
        \App\Support\MarketBook::forget();

        return self::SUCCESS;
    }

    private function items(string $path): void
    {
        $rows = json_decode((string) file_get_contents($path), true) ?: [];
        $now = now();
        $put = array_map(fn ($i) => [
            'itemKey' => $i['key'], 'name' => $i['name'], 'cropKey' => $i['crop'], 'unit' => $i['unit'],
            'storeMonths' => (int) $i['store'], 'lossPct' => (float) $i['loss'], 'hasFarmgate' => (bool) $i['farmgate'], 'hasWholesale' => (bool) $i['wholesale'],
            'wsFactor' => (float) $i['wsFactor'], 'created_at' => $now, 'updated_at' => $now,
        ], $rows);
        DB::table('as_market_items')->upsert($put, ['itemKey'], ['name', 'cropKey', 'unit', 'storeMonths', 'lossPct', 'hasFarmgate', 'hasWholesale', 'wsFactor', 'updated_at']);
        DB::table('as_market_items')->whereNotIn('itemKey', array_column($rows, 'key'))->delete();
        $this->info('items: ' . count($put));
    }

    private function series(string $path): void
    {
        $rows = json_decode((string) gzdecode((string) file_get_contents($path)), true) ?: [];
        $now = now();
        $keys = [];
        $n = 0;
        foreach (array_chunk($rows, 150) as $chunk) {
            $put = [];
            foreach ($chunk as $s) {
                $months = count($s['v']);
                [$y, $m] = array_map('intval', explode('-', $s['s']));
                $last = ($y * 12 + $m - 1) + $months - 1;
                $put[] = [
                    'itemKey' => $s['i'], 'level' => $s['l'], 'geoKey' => $s['g'], 'geoLevel' => $s['gl'], 'geoName' => mb_substr($s['gn'], 0, 80),
                    'regionCode' => $s['rc'], 'regionName' => $s['rn'], 'startYm' => $s['s'], 'lastYm' => sprintf('%04d-%02d', intdiv($last, 12), $last % 12 + 1),
                    'points' => (int) $s['n'], 'monthly' => json_encode($s['v']), 'created_at' => $now, 'updated_at' => $now,
                ];
                $keys[] = $s['i'] . '|' . $s['l'] . '|' . $s['g'];
            }
            DB::table('as_market_series')->upsert($put, ['itemKey', 'level', 'geoKey'], ['geoLevel', 'geoName', 'regionCode', 'regionName', 'startYm', 'lastYm', 'points', 'monthly', 'updated_at']);
            $n += count($put);
            $this->output->write("\rseries: " . $n);
        }
        $this->newLine();
        // A series the new build no longer has goes.
        $have = array_flip($keys);
        $gone = DB::table('as_market_series')->get(['id', 'itemKey', 'level', 'geoKey'])->filter(fn ($r) => ! isset($have[$r->itemKey . '|' . $r->level . '|' . $r->geoKey]))->pluck('id');
        if ($gone->isNotEmpty()) {
            DB::table('as_market_series')->whereIn('id', $gone)->delete();
        }
        $this->info('series: ' . $n . ($gone->isNotEmpty() ? ', removed ' . $gone->count() : ''));
    }

    private function local(string $path): void
    {
        $rows = json_decode((string) gzdecode((string) file_get_contents($path)), true) ?: [];
        $now = now();
        DB::table('as_market_local')->delete();
        $n = 0;
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('as_market_local')->insert(array_map(fn ($r) => [
                'itemKey' => $r['i'], 'form' => mb_substr((string) $r['form'], 0, 40) ?: null, 'buyer' => mb_substr((string) $r['buyer'], 0, 60) ?: null,
                'price' => $r['p'], 'priceLo' => $r['lo'], 'priceHi' => $r['hi'], 'year' => $r['y'], 'month' => $r['m'], 'day' => $r['date'],
                'region' => $r['region'] ? mb_substr($r['region'], 0, 60) : null, 'province' => $r['province'] ? mb_substr($r['province'], 0, 80) : null,
                'town' => $r['town'] ? mb_substr($r['town'], 0, 80) : null, 'kind' => $r['kind'], 'fromImage' => (bool) $r['image'], 'selfReport' => (bool) $r['self'],
                'url' => $r['url'], 'created_at' => $now, 'updated_at' => $now,
            ], $chunk));
            $n += count($chunk);
        }
        $this->info('local reports: ' . $n);
    }

    private function news(string $path): void
    {
        if (! is_file($path)) {
            $this->line('news: none yet (database/market/news.json)');

            return;
        }
        $doc = json_decode((string) file_get_contents($path), true) ?: [];
        $now = now();
        $events = [];
        foreach ((array) ($doc['events'] ?? []) as $e) {
            $d = (string) ($e['date'] ?? '');
            if (! preg_match('/^(\d{4})(?:-(\d{2}))?(?:-(\d{2}))?$/', $d, $m)) {
                continue;
            }
            $precision = isset($m[3]) && $m[3] !== '' ? 'day' : (isset($m[2]) && $m[2] !== '' ? 'month' : 'year');
            $day = sprintf('%04d-%02d-%02d', $m[1], ($m[2] ?? '') !== '' ? $m[2] : 1, ($m[3] ?? '') !== '' ? $m[3] : 1);
            $end = (string) ($e['end_date'] ?? '');
            $endDay = preg_match('/^(\d{4})(?:-(\d{2}))?(?:-(\d{2}))?$/', $end, $x) ? sprintf('%04d-%02d-%02d', $x[1], ($x[2] ?? '') !== '' ? $x[2] : 1, ($x[3] ?? '') !== '' ? $x[3] : 1) : null;
            $events[] = [
                'eventKey' => mb_substr((string) $e['id'], 0, 20), 'crops' => json_encode(array_values((array) ($e['crop_keys'] ?? []))), 'day' => $day, 'precision' => $precision,
                'endDay' => $endDay, 'kind' => mb_substr((string) ($e['kind'] ?? 'other'), 0, 24), 'direction' => in_array($e['direction'] ?? '', ['up', 'down', 'mixed'], true) ? $e['direction'] : 'mixed',
                'title' => mb_substr((string) ($e['title'] ?? ''), 0, 160), 'summary' => (string) ($e['summary'] ?? ''),
                'importTons' => is_numeric($e['import_tons'] ?? null) ? $e['import_tons'] : null, 'tariffFrom' => is_numeric($e['tariff_from'] ?? null) ? $e['tariff_from'] : null,
                'tariffTo' => is_numeric($e['tariff_to'] ?? null) ? $e['tariff_to'] : null, 'farmgateNote' => $e['farmgate_note'] ?? null,
                'scope' => mb_substr((string) ($e['scope'] ?? 'national'), 0, 80), 'sources' => json_encode(array_values((array) ($e['sources'] ?? []))),
                'quote' => $e['quote'] ?? null, 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        // The researched events are replaced; the ones the daily news brought (keys "n" + a story's id) stay.
        DB::table('as_market_events')->where('eventKey', 'not like', 'n%')->delete();
        foreach (array_chunk($events, 100) as $chunk) {
            DB::table('as_market_events')->insert($chunk);
        }
        $imports = [];
        foreach ((array) ($doc['imports'] ?? []) as $i) {
            if (! is_numeric($i['tons'] ?? null) || ! (int) ($i['year'] ?? 0)) {
                continue;
            }
            $imports[] = ['cropKey' => mb_substr((string) $i['crop_key'], 0, 40), 'year' => (int) $i['year'], 'tons' => $i['tons'], 'basis' => mb_substr((string) ($i['basis'] ?? ''), 0, 80),
                'partialYear' => (bool) ($i['partial_year'] ?? false), 'source' => json_encode($i['source'] ?? null), 'quote' => $i['quote'] ?? null, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('as_market_imports')->delete();
        foreach (array_chunk($imports, 200) as $chunk) {
            DB::table('as_market_imports')->insert($chunk);
        }
        $this->info('events: ' . count($events) . ', import rows: ' . count($imports));
    }
}
