<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The market price shelf (2026-10-27, the owner: "give the potential
 * forecast of the upcoming price for the year and next year based on the
 * historical data ... show when to sell or trade the output of crops based
 * on its market price"). The one door to the price tables (see the
 * migration 2026_10_27_100000_market_prices):
 *
 *  - catalog() and places(): the crops with prices, and the places each has;
 *  - outlook(): one item at one place: its past, the months of its year, its
 *    forecast to the end of next year with the likely range (MarketForecast),
 *    the trader's price beside the farmer's, the import and policy events
 *    with what the price did after each, the yearly imports, and the latest
 *    local reports near the place;
 *  - sellPlan(): sell at harvest, or keep it and sell later;
 *  - forPrompt() and forHarvest(): the same in a few lines for When to Plant
 *    and What to Plant, and a crop's price at a harvest month.
 *
 * A place without a long enough series of its own reads its region's, then
 * the country's, and says so. Read only; `php artisan market:load` fills it.
 * Never names where the records come from (App\Support\GovSource).
 */
final class MarketBook
{
    private static array $memo = [];

    public static function ready(): bool
    {
        return self::$memo['ready'] ??= (bool) rescue(fn () => Schema::hasTable('as_market_series') && DB::table('as_market_series')->exists(), false, false);
    }

    /** After a load: every kept outlook is read again. */
    public static function forget(): void
    {
        Cache::forever('market:ver', time());
        self::$memo = [];
    }

    private static function ver(): int
    {
        return (int) Cache::get('market:ver', 1);
    }

    /** Month index (year * 12 + month - 1) of a "YYYY-MM". */
    public static function idx(string $ym): int
    {
        [$y, $m] = array_map('intval', explode('-', $ym));

        return $y * 12 + $m - 1;
    }

    public static function ym(int $idx): string
    {
        return sprintf('%04d-%02d', intdiv($idx, 12), $idx % 12 + 1);
    }

    /** All the items, by key. */
    public static function items(): array
    {
        return self::$memo['items'] ??= Cache::remember('market:items:' . self::ver(), 86400, fn () => DB::table('as_market_items')->orderBy('id')->get()
            ->mapWithKeys(fn ($r) => [$r->itemKey => ['key' => $r->itemKey, 'name' => $r->name, 'crop' => $r->cropKey, 'unit' => $r->unit,
                'store' => (int) $r->storeMonths, 'loss' => (float) $r->lossPct, 'wholesale' => (bool) $r->hasWholesale, 'wsFactor' => (float) $r->wsFactor]])->all());
    }

    /** The crops with prices, in the app's crop order, each with its items (the first is the one most farmers sell). */
    public static function catalog(): array
    {
        return self::$memo['catalog'] ??= Cache::remember('market:catalog:' . self::ver(), 86400, function () {
            $have = DB::table('as_market_series')->where('level', 'f')->selectRaw('itemKey, max(lastYm) as last, count(*) as places')->groupBy('itemKey')->get()->keyBy('itemKey');
            $byCrop = [];
            foreach (self::items() as $k => $it) {
                if (! $it['crop'] || ! isset($have[$k])) {
                    continue;
                }
                $byCrop[$it['crop']][] = $it + ['last' => $have[$k]->last, 'places' => (int) $have[$k]->places];
            }
            $out = [];
            foreach (array_keys(CropStages::crops()) as $crop) {
                if (isset($byCrop[$crop])) {
                    $out[] = ['crop' => $crop, 'label' => CropCatalog::label($crop), 'icon' => CropStages::icon($crop), 'items' => $byCrop[$crop]];
                }
            }

            return $out;
        });
    }

    /** The places an item has farmgate prices for: the country, the regions, the provinces. */
    public static function places(string $item): array
    {
        return Cache::remember('market:places:' . $item . ':' . self::ver(), 86400, fn () => DB::table('as_market_series')->where('itemKey', $item)->where('level', 'f')
            ->orderByRaw("FIELD(geoLevel, 'national', 'region', 'province')")->orderBy('regionName')->orderBy('geoName')
            ->get(['geoKey', 'geoName', 'geoLevel', 'regionName', 'lastYm', 'points'])
            ->map(fn ($r) => ['key' => $r->geoKey, 'name' => $r->geoName, 'level' => $r->geoLevel, 'region' => $r->regionName, 'last' => $r->lastYm, 'points' => (int) $r->points])->values()->all());
    }

    /** One series, as month index => price. */
    public static function series(string $item, string $level, string $geo): ?array
    {
        $key = $item . '|' . $level . '|' . $geo;
        if (array_key_exists($key, self::$memo['series'] ?? [])) {
            return self::$memo['series'][$key];
        }
        $r = DB::table('as_market_series')->where('itemKey', $item)->where('level', $level)->where('geoKey', $geo)->first();
        $out = null;
        if ($r) {
            $out = ['geo' => $r->geoKey, 'name' => $r->geoName, 'level' => $r->geoLevel, 'region' => $r->regionName, 'regionCode' => $r->regionCode, 'last' => $r->lastYm, 'v' => []];
            $i = self::idx($r->startYm);
            foreach ((array) json_decode((string) $r->monthly, true) as $k => $p) {
                if ($p !== null && $p > 0) {
                    $out['v'][$i + $k] = (float) $p;
                }
            }
        }

        return self::$memo['series'][$key] = $out;
    }

    /** Whether a series is long and fresh enough to stand on its own. */
    private static function enough(?array $s): bool
    {
        if (! $s || ! $s['v']) {
            return false;
        }
        $now = self::nowIdx();
        $last = max(array_keys($s['v']));
        $recent = count(array_filter(array_keys($s['v']), fn ($t) => $t > $now - 120));

        return $last >= $now - 30 && $recent >= 48;
    }

    public static function nowIdx(): int
    {
        $n = Carbon::now('Asia/Manila');

        return $n->year * 12 + $n->month - 1;
    }

    /** A province's key from a place as written ("Alcala, Pangasinan"), or null. */
    public static function provinceKey(?string $where): ?string
    {
        $where = trim((string) $where);
        if ($where === '') {
            return null;
        }
        $parts = array_reverse(array_map('trim', explode(',', $where)));
        try {
            array_unshift($parts, (string) (ClimateRecord::placeKey($where, 'PH')['place'] ?? ''));
        } catch (\Throwable $e) {
            // The words themselves are read.
        }
        $known = self::$memo['provinces'] ??= Cache::remember('market:provinces:' . self::ver(), 86400, fn () => DB::table('as_market_series')->where('geoLevel', 'province')->distinct()->pluck('geoKey')->all());
        foreach ($parts as $p) {
            $k = trim(preg_replace('/[^a-z0-9]+/', '_', mb_strtolower(preg_replace('/\s*\(.*\)|^province of\s+/i', '', $p))), '_');
            if ($k !== '' && in_array($k, $known, true)) {
                return $k;
            }
        }

        return null;
    }

    /**
     * The series to read for an item at a place: the province's own, else its region's, else the country's.
     * [series, why] where why says what stood in, or ''.
     */
    public static function pick(string $item, ?string $geo): ?array
    {
        $geo = $geo ?: 'PH';
        $own = self::series($item, 'f', $geo);
        if (self::enough($own)) {
            return [$own, ''];
        }
        if ($own && $own['level'] === 'province' && $own['regionCode']) {
            $reg = self::series($item, 'f', $own['regionCode']);
            if (self::enough($reg)) {
                return [$reg, ($own['name'] ?? 'This province') . ' has too few recent prices of its own, so its region\'s are read.'];
            }
        }
        $ph = self::series($item, 'f', 'PH');
        if (self::enough($ph) || ($ph && $ph['v'])) {
            return [$ph, $geo === 'PH' ? '' : ($own ? ($own['name'] . ' has too few recent prices of its own, so the country\'s are read.') : 'No prices are kept for that place, so the country\'s are read.')];
        }

        return $own && $own['v'] ? [$own, ''] : null;
    }

    /** One item at one place, everything the page shows. Kept for half a day. */
    public static function outlook(string $item, ?string $geo = null): ?array
    {
        $it = self::items()[$item] ?? null;
        if (! $it || ! self::ready()) {
            return null;
        }
        $now = self::nowIdx();

        return Cache::remember('market:outlook:' . $item . ':' . ($geo ?: 'PH') . ':' . $now . ':' . self::ver(), 43200, function () use ($it, $item, $geo, $now) {
            $picked = self::pick($item, $geo);
            if (! $picked) {
                return null;
            }
            [$s, $why] = $picked;
            $v = $s['v'];
            $last = max(array_keys($v));
            $thisYear = intdiv($now, 12);
            $to = ($thisYear + 1) * 12 + 11;
            $f = MarketForecast::run($v, $to);
            $months = [];
            for ($t = $last - 71; $t <= $last; $t++) {
                if (isset($v[$t])) {
                    $months[] = ['ym' => self::ym($t), 'p' => round($v[$t], 2)];
                }
            }
            $annual = [];
            foreach ($v as $t => $p) {
                $annual[intdiv($t, 12)][] = $p;
            }
            $annualOut = [];
            foreach ($annual as $y => $ps) {
                $annualOut[] = ['y' => $y, 'avg' => round(array_sum($ps) / count($ps), 2), 'n' => count($ps)];
            }
            $fc = [];
            foreach ((array) ($f['forecast'] ?? []) as $t => $x) {
                $fc[] = ['ym' => self::ym($t)] + $x;
            }
            // The years in one number each: last year's price, this year's (the months known and the rest forecast), next year's.
            $yearAvg = function (int $y) use ($v, $f) {
                $ps = [];
                $lo = [];
                $hi = [];
                $known = 0;
                for ($t = $y * 12; $t < $y * 12 + 12; $t++) {
                    if (isset($v[$t])) {
                        $ps[] = $v[$t];
                        $lo[] = $v[$t];
                        $hi[] = $v[$t];
                        $known++;
                    } elseif (isset($f['forecast'][$t])) {
                        $ps[] = $f['forecast'][$t]['p'];
                        $lo[] = $f['forecast'][$t]['lo'];
                        $hi[] = $f['forecast'][$t]['hi'];
                    }
                }

                return $ps ? ['y' => $y, 'avg' => round(array_sum($ps) / count($ps), 2), 'lo' => round(array_sum($lo) / count($lo), 2),
                    'hi' => round(array_sum($hi) / count($hi), 2), 'known' => $known, 'months' => count($ps)] : null;
            };
            $seasonal = [];
            if ($f) {
                foreach ($f['seasonal'] as $m => $x) {
                    $seasonal[] = ['m' => $m + 1, 'idx' => $x, 'years' => $f['seasonalN'][$m], 'sure' => $f['consistency'][$m]];
                }
            }
            $order = $seasonal;
            usort($order, fn ($a, $b) => $b['idx'] <=> $a['idx']);
            $lp = $v[$last];
            $yoy = isset($v[$last - 12]) ? round($lp / $v[$last - 12] - 1, 4) : null;

            return [
                'item' => $it + ['cropLabel' => CropCatalog::label($it['crop'])],
                'place' => ['key' => $s['geo'], 'name' => $s['geo'] === 'PH' ? 'the Philippines' : $s['name'], 'level' => $s['level'], 'region' => $s['region'], 'asked' => $geo ?: 'PH', 'why' => $why],
                'data' => ['from' => self::ym(min(array_keys($v))), 'last' => self::ym($last), 'points' => count($v), 'stale' => $last < $now - 6],
                'latest' => ['ym' => self::ym($last), 'p' => round($lp, 2), 'yoy' => $yoy],
                'history' => $months,
                'annual' => $annualOut,
                'forecast' => $fc,
                'years' => ['last' => $yearAvg($thisYear - 1), 'this' => $yearAvg($thisYear), 'next' => $yearAvg($thisYear + 1)],
                'seasonal' => $seasonal,
                'best' => array_map(fn ($x) => $x['m'], array_slice($order, 0, 3)),
                'worst' => array_map(fn ($x) => $x['m'], array_slice(array_reverse($order), 0, 3)),
                'swing' => $f['spread'] ?? null,
                'growth' => $f['growth'] ?? null,
                'gap' => $f['gap'] ?? null,
                'halfLife' => $f['halfLife'] ?? null,
                'accuracy' => $f['accuracy'] ?? null,
                'boomBust' => $f['boomBust'] ?? null,
                'wholesale' => self::spread($item, $s, $it),
                'events' => self::events((string) $it['crop'], $item),
                'imports' => self::imports((string) $it['crop']),
                'local' => self::local($item, $s),
                'rank' => self::rank($item),
            ];
        });
    }

    /** The trader's price beside the farmer's, the last twelve months, in the farmer's product. */
    private static function spread(string $item, array $s, array $it): ?array
    {
        if (! $it['wholesale']) {
            return null;
        }
        foreach (array_unique(array_filter([$s['geo'], $s['regionCode'], 'PH'])) as $g) {
            $w = self::series($item, 'w', $g);
            if (! $w || ! $w['v']) {
                continue;
            }
            $both = array_intersect_key($s['v'], $w['v']);
            // Both prices of the same recent months, or none.
            if (count($both) < 6 || max(array_keys($both)) < self::nowIdx() - 24) {
                continue;
            }
            $ks = array_slice(array_keys($both), -12);
            $farm = array_sum(array_map(fn ($t) => $s['v'][$t], $ks)) / count($ks);
            $trade = array_sum(array_map(fn ($t) => $w['v'][$t], $ks)) / count($ks);
            $equiv = $trade * $it['wsFactor'];

            return ['farm' => round($farm, 2), 'trade' => round($trade, 2), 'equiv' => round($equiv, 2), 'share' => $equiv > 0 ? round($farm / $equiv, 3) : null,
                'months' => count($ks), 'place' => $g === 'PH' ? 'the Philippines' : $w['name'], 'milled' => $it['wsFactor'] < 0.99];
        }

        return null;
    }

    /**
     * The import and policy events of a crop, newest first, with what the price did after each:
     * its price without its season 3 and 6 months on, against the month before.
     */
    public static function events(string $crop, ?string $item = null): array
    {
        if (! Schema::hasTable('as_market_events')) {
            return [];
        }
        $rows = DB::table('as_market_events')->whereJsonContains('crops', $crop)->orderByDesc('day')->limit(60)->get();
        $ph = $item ? self::series($item, 'f', 'PH') : null;
        $fit = $ph && count($ph['v']) >= 36 ? MarketForecast::fit($ph['v'], max(array_keys($ph['v']))) : null;
        $move = function (string $day, int $k) use ($ph, $fit): ?float {
            if (! $ph || ! $fit) {
                return null;
            }
            $e = self::idx(substr($day, 0, 7));
            $a = $ph['v'][$e - 1] ?? null;
            $b = $ph['v'][$e + $k] ?? null;
            if (! $a || ! $b) {
                return null;
            }

            return round(($b / $fit['s'][($e + $k) % 12]) / ($a / $fit['s'][($e - 1) % 12]) - 1, 4);
        };

        return $rows->map(fn ($r) => [
            'key' => $r->eventKey, 'day' => $r->day, 'precision' => $r->precision, 'end' => $r->endDay, 'kind' => $r->kind, 'direction' => $r->direction,
            'title' => $r->title, 'summary' => $r->summary, 'tons' => $r->importTons !== null ? (float) $r->importTons : null,
            'tariff' => $r->tariffTo !== null ? ['from' => (float) $r->tariffFrom, 'to' => (float) $r->tariffTo] : null, 'farmgate' => $r->farmgateNote, 'scope' => $r->scope,
            // Newsrooms only: no agency, ours or abroad (USDA and the like are kept for the record, never shown).
            'sources' => array_values(array_map(fn ($x) => ['publisher' => (string) ($x['publisher'] ?? ''), 'url' => (string) ($x['url'] ?? '')],
                array_filter(GovSource::keep((array) json_decode((string) $r->sources, true)), fn ($x) => ($x['type'] ?? 'news') !== 'government'
                    && ! preg_match('/\busda\b|foreign agricultural service|\.gov\b|tariff commission/i', ($x['publisher'] ?? '') . ' ' . ($x['url'] ?? ''))))),
            'move3' => $move($r->day, 3), 'move6' => $move($r->day, 6),
            'ahead' => $r->day > Carbon::now('Asia/Manila')->toDateString(),
        ])->values()->all();
    }

    /** A crop's yearly imports, one figure a year (the larger where two sources differ is not chosen: the first kept). */
    public static function imports(string $crop): array
    {
        if (! Schema::hasTable('as_market_imports')) {
            return [];
        }
        $out = [];
        foreach (DB::table('as_market_imports')->where('cropKey', $crop)->orderBy('year')->orderBy('id')->get() as $r) {
            $out[$r->year] ??= ['y' => (int) $r->year, 'tons' => (float) $r->tons, 'basis' => self::basisWords((string) $r->basis), 'partial' => (bool) $r->partialYear];
        }

        return array_values($out);
    }

    /** What an import figure counts, in plain words that name no agency. */
    private static function basisWords(string $b): string
    {
        $b = mb_strtolower($b);

        return match (true) {
            str_contains($b, 'marketing year'), str_contains($b, 'trade year') => 'a trade year estimate, not the calendar year',
            (bool) preg_match('/clearance|permit|approved|authori[sz]ed|allowed/', $b) => 'the volume allowed in, not all of it arrived',
            str_contains($b, 'purchases') => 'government buying only',
            str_contains($b, 'arrivals') => 'arrivals',
            (bool) preg_match('/customs|import data|trade data/', $b) => 'customs records',
            default => 'as reported',
        };
    }

    /** The latest local reports for the item near the place (its province, else its region), newest first. */
    private static function local(string $item, array $s): array
    {
        if (! in_array($item, ['palay', 'corn_yellow', 'corn_white', 'cassava_food', 'copra_resecada', 'onion_red', 'onion_white', 'soybean'], true)) {
            return [];
        }
        $q = DB::table('as_market_local')->where('itemKey', $item)->whereNotNull('day')->where('day', '>=', Carbon::now('Asia/Manila')->subMonths(18)->toDateString());
        if ($s['level'] === 'province') {
            $q->where('province', $s['name']);
        } elseif ($s['level'] === 'region') {
            $q->where('region', 'like', '%' . $s['name'] . '%');
        }

        return $q->orderByDesc('day')->limit(12)->get()->map(fn ($r) => [
            'day' => $r->day, 'town' => $r->town, 'province' => $r->province, 'p' => (float) $r->price, 'lo' => $r->priceLo !== null ? (float) $r->priceLo : null,
            'hi' => $r->priceHi !== null ? (float) $r->priceHi : null, 'form' => $r->form, 'buyer' => self::buyerWords((string) $r->buyer), 'farmer' => (bool) $r->selfReport,
        ])->values()->all();
    }

    private static function buyerWords(string $b): string
    {
        return match (true) {
            str_contains($b, 'NFA') => 'government buying price',
            str_contains($b, 'trader') => 'traders buying',
            str_contains($b, 'miller') => 'millers buying',
            str_contains($b, 'co-op') => 'cooperative buying',
            str_contains($b, 'LGU') => 'town buying program',
            str_contains($b, 'support') => 'support price',
            default => 'farmers selling',
        };
    }

    /** How dear the item is among all the crops sold by the kilo: its price lately and its place (0 cheapest, 1 dearest). */
    public static function rank(string $item): ?array
    {
        $all = self::$memo['ranks'] ??= Cache::remember('market:ranks:' . self::ver() . ':' . self::nowIdx(), 86400, function () {
            // One read of every national series.
            $out = [];
            $items = self::items();
            foreach (DB::table('as_market_series')->where('level', 'f')->where('geoKey', 'PH')->get(['itemKey', 'startYm', 'monthly']) as $r) {
                if (($items[$r->itemKey]['unit'] ?? '') !== 'kg') {
                    continue;
                }
                $i0 = self::idx($r->startYm);
                $v = [];
                foreach ((array) json_decode((string) $r->monthly, true) as $k => $p) {
                    if ($p !== null && $p > 0) {
                        $v[$i0 + $k] = (float) $p;
                    }
                }
                $ks = array_slice(array_keys($v), -12);
                if (! $ks || max($ks) < self::nowIdx() - 30) {
                    continue;
                }
                $out[$r->itemKey] = array_sum(array_map(fn ($t) => $v[$t], $ks)) / count($ks);
            }

            return $out;
        });
        if (! isset($all[$item])) {
            return null;
        }
        $below = count(array_filter($all, fn ($p) => $p < $all[$item]));

        return ['p' => round($all[$item], 2), 'pct' => round($below / max(1, count($all) - 1), 3), 'of' => count($all)];
    }

    /**
     * Sell at harvest, or keep it: for each month it can wait (the item's storage months), the price expected
     * then less what keeping costs (the weight and quality it loses, and the money's own cost, 1% a month).
     * $harvestIdx = the harvest month's index. Returns the harvest month, the best month and every month weighed.
     */
    public static function sellPlan(array $o, int $harvestIdx, ?float $qty = null): ?array
    {
        $byYm = [];
        foreach ($o['forecast'] as $x) {
            $byYm[$x['ym']] = $x;
        }
        $season = [];
        foreach ($o['seasonal'] as $x) {
            $season[$x['m']] = $x['idx'];
        }
        $known = [];
        foreach ($o['history'] as $x) {
            $known[$x['ym']] = $x['p'];
        }
        $level = $o['years']['next']['avg'] ?? $o['latest']['p'];
        $price = function (int $t) use ($byYm, $known, $season, $level) {
            $ym = self::ym($t);
            if (isset($byYm[$ym])) {
                return [$byYm[$ym]['p'], $byYm[$ym]['lo'], $byYm[$ym]['hi'], 'forecast'];
            }
            if (isset($known[$ym])) {
                return [$known[$ym], $known[$ym], $known[$ym], 'known'];
            }
            $p = $level * ($season[$t % 12 + 1] ?? 1);

            return [$p, $p * 0.8, $p * 1.2, 'usual'];
        };
        $store = (int) $o['item']['store'];
        $loss = (float) $o['item']['loss'] / 100;
        $rows = [];
        for ($k = 0; $k <= $store; $k++) {
            [$p, $lo, $hi, $how] = $price($harvestIdx + $k);
            $keep = (1 - $loss) ** $k * (1 - 0.01 * $k);
            $rows[] = ['ym' => self::ym($harvestIdx + $k), 'wait' => $k, 'p' => round($p, 2), 'lo' => round($lo, 2), 'hi' => round($hi, 2), 'net' => round($p * $keep, 2), 'how' => $how,
                'money' => $qty ? round($p * $keep * $qty) : null];
        }
        $best = $rows[0];
        foreach ($rows as $r) {
            if ($r['net'] > $best['net'] * 1.03) {
                $best = $r;
            }
        }

        return ['harvest' => $rows[0], 'best' => $best, 'gain' => round($best['net'] / max(0.01, $rows[0]['p']) - 1, 4), 'rows' => $rows, 'store' => $store, 'loss' => $o['item']['loss']];
    }

    /**
     * A light read of many crops at one place for What to Plant (2026-10-27): each crop's main item, its price
     * lately (the last twelve months on record), the months of its year, its long run and its place among
     * the crops, without the forecast's range. Three reads of the table in all (the province's series, the
     * regions', the country's) and kept a day. crop key => brief.
     */
    public static function briefs(array $crops, ?string $where): array
    {
        if (! self::ready()) {
            return [];
        }
        $prov = self::provinceKey($where);
        $items = [];
        foreach ($crops as $c) {
            if ($i = self::mainItem($c)) {
                $items[$c] = $i;
            }
        }
        if (! $items) {
            return [];
        }
        $now = self::nowIdx();
        $key = 'market:briefs:' . md5(json_encode([$items, $prov])) . ':' . $now . ':' . self::ver();

        return Cache::remember($key, 86400, function () use ($items, $prov) {
            $read = fn ($q) => $q->where('level', 'f')->whereIn('itemKey', array_values(array_unique($items)))->get()
                ->mapWithKeys(fn ($r) => [$r->itemKey . '|' . $r->geoKey => $r]);
            $rows = $read(DB::table('as_market_series')->whereIn('geoKey', array_values(array_filter([$prov, 'PH']))));
            $regions = $rows->filter(fn ($r) => $r->geoLevel === 'province' && $r->regionCode)->pluck('regionCode')->unique()->values()->all();
            if ($regions) {
                $rows = $rows->merge($read(DB::table('as_market_series')->whereIn('geoKey', $regions)));
            }
            $series = function ($r) {
                $v = [];
                $i0 = self::idx($r->startYm);
                foreach ((array) json_decode((string) $r->monthly, true) as $k => $p) {
                    if ($p !== null && $p > 0) {
                        $v[$i0 + $k] = (float) $p;
                    }
                }

                return ['geo' => $r->geoKey, 'name' => $r->geoKey === 'PH' ? 'the Philippines' : $r->geoName, 'level' => $r->geoLevel, 'regionCode' => $r->regionCode, 'v' => $v];
            };
            $out = [];
            foreach ($items as $crop => $item) {
                $pick = null;
                $p = $prov ? ($rows[$item . '|' . $prov] ?? null) : null;
                foreach (array_filter([$p, $p && $p->regionCode ? ($rows[$item . '|' . $p->regionCode] ?? null) : null, $rows[$item . '|PH'] ?? null]) as $r) {
                    $s = $series($r);
                    if (self::enough($s)) {
                        $pick = $s;
                        break;
                    }
                }
                if (! $pick || count($pick['v']) < 36) {
                    continue;
                }
                $last = max(array_keys($pick['v']));
                $fit = MarketForecast::fit(array_filter($pick['v'], fn ($t) => $t > $last - 192, ARRAY_FILTER_USE_KEY), $last);
                if (! $fit) {
                    continue;
                }
                $ks = array_slice(array_keys($pick['v']), -12);
                $order = $fit['s'];
                arsort($order);
                $it = self::items()[$item];
                $out[$crop] = ['item' => $item, 'name' => $it['name'], 'unit' => $it['unit'], 'store' => $it['store'], 'place' => $pick['name'], 'level' => $pick['level'],
                    'last' => self::ym($last), 'p' => round($pick['v'][$last], 2), 'avg12' => round(array_sum(array_map(fn ($t) => $pick['v'][$t], $ks)) / count($ks), 2),
                    'season' => array_map(fn ($x) => round($x, 3), $fit['s']), 'best' => array_map(fn ($m) => $m + 1, array_slice(array_keys($order), 0, 3)),
                    'worst' => array_map(fn ($m) => $m + 1, array_slice(array_reverse(array_keys($order)), 0, 3)), 'growth' => round(exp(12 * $fit['b']) - 1, 3),
                    'rank' => self::rank($item)];
            }

            return $out;
        });
    }

    /**
     * The crops of What to Plant's pool in one line each, for its prompt: the price lately, the best and the glut
     * months, the harvest month of a planting at $startIdx with its place in the year, and how dear it is. '' without prices.
     */
    public static function poolLines(array $crops, ?string $where, int $startIdx): string
    {
        $b = self::briefs($crops, $where);
        if (! $b) {
            return '';
        }
        $mn = fn (int $m) => date('M', mktime(0, 0, 0, $m, 1));
        $pct = fn (float $x) => ($x >= 0 ? '+' : '') . round($x * 100) . '%';
        $lines = [];
        $done = [];
        foreach ($b as $crop => $x) {
            // One line an item: the ways of planting rice share the palay price.
            if (isset($done[$x['item']])) {
                continue;
            }
            $done[$x['item']] = true;
            $days = (int) (CropCatalog::maturity($crop) ?: 0);
            $harvest = ! CropStages::isPerennial($crop) && $days > 0 ? $startIdx + (int) round($days / 30.4) : null;
            $lines[] = '- ' . preg_replace('/,\s*(transplanted|yellow \/ field)\b/i', '', CropCatalog::label($crop)) . ': P' . number_format($x['avg12'], 2) . ($x['unit'] === 'kg' ? '/kg' : ' a ' . $x['unit'])
                . ' (' . $x['place'] . '); best ' . implode(', ', array_map($mn, $x['best'])) . '; glut ' . implode(', ', array_map($mn, $x['worst']))
                . ($harvest !== null ? '; harvest ~' . $mn($harvest % 12 + 1) . ' ' . $pct($x['season'][$harvest % 12] - 1) : '')
                . ($x['rank'] ? '; dearer than ' . round($x['rank']['pct'] * 100) . '%' : '') . ($x['store'] ? '; keeps ' . $x['store'] . ' mo' : '; fresh only') . '.';
        }
        $lines[] = '(harvest ~Mon +x% = the month a planting at the start lands in, against that crop\'s yearly average price; dearer than y% = among all crops sold by the kilo)';

        return implode("\n", $lines) . "\n";
    }

    /** The crop key the price shelf files a crop under (rice sown in place is rice, corn is yellow corn). */
    public static function cropOf(?string $crop): ?string
    {
        $crop = NpkNeed::cropKey($crop) ?? CropStages::normalize((string) $crop) ?? $crop;

        return ['rice_dsr_wet' => 'rice', 'rice_dsr_dry' => 'rice', 'corn' => 'corn_yellow'][$crop] ?? $crop;
    }

    /** The item most farmers of a crop sell (the first of its items with prices), or null. */
    public static function mainItem(?string $crop): ?string
    {
        $crop = self::cropOf($crop);
        foreach (self::catalog() as $c) {
            if ($c['crop'] === $crop) {
                return $c['items'][0]['key'];
            }
        }

        return null;
    }

    /**
     * A crop's price at a harvest month for When to Plant and What to Plant: the usual month's place against the
     * year, the forecast there when it is within reach, and the best and worst months. Null without prices.
     */
    public static function forHarvest(?string $crop, ?string $where, int $harvestIdx): ?array
    {
        $item = self::mainItem($crop);
        if (! $item || ! self::ready()) {
            return null;
        }
        $o = self::outlook($item, self::provinceKey($where));
        if (! $o || ! $o['seasonal']) {
            return null;
        }
        $ym = self::ym($harvestIdx);
        $fc = null;
        foreach ($o['forecast'] as $x) {
            if ($x['ym'] === $ym) {
                $fc = $x;
            }
        }
        $idx = $o['seasonal'][$harvestIdx % 12]['idx'] ?? 1;

        return ['item' => $o['item']['name'], 'unit' => $o['item']['unit'], 'place' => $o['place']['name'], 'month' => $harvestIdx % 12 + 1, 'index' => $idx,
            'p' => $fc['p'] ?? round(($o['years']['next']['avg'] ?? $o['latest']['p']) * $idx, 2), 'lo' => $fc['lo'] ?? null, 'hi' => $fc['hi'] ?? null, 'forecast' => (bool) $fc,
            'best' => $o['best'], 'worst' => $o['worst'], 'latest' => $o['latest'], 'store' => $o['item']['store'], 'rank' => $o['rank']];
    }

    /**
     * The market in a few lines for a prompt: the crop's farmgate price lately, its months, where the forecast
     * goes, how dear it is among crops, and the latest import and policy events. $harvests = month indexes the
     * analysis is weighing (each said with its price). '' without prices.
     */
    public static function forPrompt(?string $crop, ?string $where, array $harvests = [], bool $events = true): string
    {
        $item = self::mainItem($crop);
        if (! $item || ! self::ready()) {
            return '';
        }
        try {
            $o = self::outlook($item, self::provinceKey($where));
        } catch (\Throwable $e) {
            report($e);

            return '';
        }
        if (! $o || ! $o['seasonal']) {
            return '';
        }
        $mn = fn (int $m) => date('M', mktime(0, 0, 0, $m, 1));
        $pct = fn (?float $x) => $x === null ? '' : ($x >= 0 ? '+' : '') . round($x * 100) . '%';
        $u = $o['item']['unit'] === 'kg' ? '/kg' : ' a ' . $o['item']['unit'];
        $line = '- ' . $o['item']['name'] . ', farmgate in ' . $o['place']['name'] . ': ' . 'P' . number_format($o['latest']['p'], 2) . $u . ' in ' . date('M Y', mktime(0, 0, 0, (int) substr($o['latest']['ym'], 5), 1, (int) substr($o['latest']['ym'], 0, 4)))
            . ($o['latest']['yoy'] !== null ? ' (' . $pct($o['latest']['yoy']) . ' on a year before)' : '')
            . '. Months of the year against its average: ' . implode(', ', array_map(fn ($x) => $mn($x['m']) . ' ' . round($x['idx'] * 100), $o['seasonal']))
            . '. Best months to sell: ' . implode(', ', array_map($mn, $o['best'])) . '; harvest glut months: ' . implode(', ', array_map($mn, $o['worst'])) . '.';
        if ($o['years']['next']) {
            $line .= ' Forecast average ' . $o['years']['next']['y'] . ': P' . number_format($o['years']['next']['avg'], 2) . $u . ' (likely P' . number_format($o['years']['next']['lo'], 2) . ' to P' . number_format($o['years']['next']['hi'], 2) . ')';
            $line .= $o['growth'] !== null ? ', long run ' . $pct($o['growth']) . ' a year.' : '.';
        }
        if ($o['rank']) {
            $line .= ' Among ' . $o['rank']['of'] . ' crops sold by the kilo it is dearer than ' . round($o['rank']['pct'] * 100) . '% of them.';
        }
        if ($o['boomBust'] && $o['boomBust']['r'] <= -0.25) {
            $line .= ' A year of high prices is often followed by a low one (many plant it after a good year).';
        }
        foreach ($harvests as $h) {
            $f = null;
            foreach ($o['forecast'] as $x) {
                if ($x['ym'] === self::ym($h)) {
                    $f = $x;
                }
            }
            $i = $o['seasonal'][$h % 12]['idx'] ?? 1;
            $line .= "\n  - Harvest in " . date('M Y', mktime(0, 0, 0, $h % 12 + 1, 1, intdiv($h, 12))) . ': usually ' . $pct($i - 1) . ' against the year'
                . ($f ? ', forecast P' . number_format($f['p'], 2) . $u . ' (P' . number_format($f['lo'], 2) . ' to P' . number_format($f['hi'], 2) . ')' : '') . '.';
        }
        if ($events) {
            $ev = array_slice(array_filter($o['events'], fn ($e) => $e['day'] >= Carbon::now('Asia/Manila')->subYears(3)->toDateString()), 0, 4);
            foreach ($ev as $e) {
                $line .= "\n  - " . substr($e['day'], 0, $e['precision'] === 'day' ? 10 : 7) . ': ' . $e['title'] . ' (prices ' . ($e['direction'] === 'down' ? 'pushed down' : ($e['direction'] === 'up' ? 'pushed up' : 'moved')) . ')'
                    . ($e['move6'] !== null ? ', farmgate ' . $pct($e['move6']) . ' six months on' : '') . '.';
            }
        }

        return $line . "\n";
    }
}
