<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The live ENSO picture for every analysis that weighs the climate: the
 * OBSERVED state (NOAA CPC's ONI, 3 month running sea surface anomalies) and
 * the OFFICIAL FORECAST (the monthly ENSO advisory's alert and synopsis, the
 * odds included).
 *
 * Kept, not fetched (2026-10-19, the owner: "save those forecasts as always
 * (get the latest if there's a forecast ...)"): the daily collector
 * (climate:collect --enso) keeps the ONI reading (as_enso_readings), the
 * whole ONI history (as_enso_seasons) and each advisory (as_enso_advisories,
 * one row per advisory, kept for good). Every reader here reads the latest
 * kept one while it is Freshness::ENSO_DAYS fresh, and reads NOAA itself only
 * when nothing fresh is kept (and then keeps what it read). Before, each of
 * observed(), state() and forecast() read NOAA on its own once a day, beside
 * the collector: five reads a day of two pages.
 *
 * Each product fails to '' quietly and separately: a farm analysis must
 * never break because a NOAA page moved, and the prompts carry their own
 * honest-ignorance wording for the empty case.
 */
final class EnsoOutlook
{
    use Concerns\ShelfConnection;

    public const ONI_URL = 'https://www.cpc.ncep.noaa.gov/data/indices/oni.ascii.txt';

    public const ADVISORY_URL = 'https://www.cpc.ncep.noaa.gov/products/analysis_monitoring/enso_advisory/ensodisc.shtml';

    /** Observed state + official forecast, ready for a prompt. '' offline. */
    public static function forPrompt(): string
    {
        $bits = array_filter([self::observed(), self::forecast()]);
        if (! $bits) {
            return '';
        }

        return implode(' ', $bits)
            . (\App\Support\Region::ph() ? ' For Philippine farming, El Niño typically tilts toward below-normal rainfall,' : ' For farming in ' . \App\Support\Region::name() . ', ENSO\'s local effect depends on the region — say plainly what this state has historically meant for THIS location\'s rainfall and temperature, and where it has little effect say so. In the Philippines El Niño typically tilts toward below-normal rainfall,')
            . ' drought and heat stress; La Niña toward above-normal rain, flooding and'
            . ' typhoon-season wetness — weigh the forecast probabilities accordingly.';
    }

    /**
     * The same in one short line (2026-10-19), for an ask that only needs
     * the gist beside a fuller block elsewhere in the run (a Crop Protocol
     * lot after its farm, a realign, a chat about the weather): the state,
     * its ONI and season, the advisory's synopsis and date. '' offline.
     */
    public static function brief(): string
    {
        $s = self::kept('as_enso_readings') ? ClimateStore::enso() : self::state();
        $a = self::advisory();
        if (! $s && ! $a) {
            return '';
        }
        $line = 'ENSO: ' . ($s ? $s['label'] . ' (ONI ' . sprintf('%+.2f', $s['oni']) . (! empty($s['season']) ? ', ' . $s['season'] : '') . ')' : 'state not read');
        if ($a && ($a['synopsis'] ?? '') !== '') {
            $line .= '; official outlook' . (! empty($a['issuedOn']) ? ' of ' . Carbon::parse($a['issuedOn'])->format('M j, Y') : '') . ': ' . $a['synopsis'];
        }

        return rtrim($line, '.') . '. In the Philippines El Niño leans dry and hot, La Niña wet and stormy.';
    }

    /**
     * The state as numbers, for arithmetic (NPK Plus's sun and water,
     * 2026-10-08): the latest ONI, El Niño, La Niña or neutral, and how
     * strong. Null when NOAA could not be read.
     *
     * @return array{oni: float, phase: string, strength: string, label: string}|null
     */
    public static function state(): ?array
    {
        if (self::kept('as_enso_readings')) {
            $e = ClimateStore::enso();

            return $e ? ['oni' => (float) $e['oni'], 'phase' => (string) $e['phase'], 'strength' => (string) $e['strength'], 'label' => (string) $e['label']] : null;
        }
        try {
            $oni = Cache::remember('enso-oni-last', 86400, function () {
                $txt = Http::timeout(8)->get(self::ONI_URL)->body();
                $rows = array_values(array_filter(array_map('trim', explode("\n", $txt))));
                for ($i = count($rows) - 1; $i >= 0; $i--) {
                    $p = preg_split('/\s+/', $rows[$i]);
                    if (count($p) >= 4 && is_numeric($p[3])) {
                        return (float) $p[3];
                    }
                }

                return null;
            });
        } catch (\Throwable $e) {
            return null;
        }
        if ($oni === null) {
            return null;
        }
        $a = abs($oni);
        $strength = $a >= 2 ? 'very strong' : ($a >= 1.5 ? 'strong' : ($a >= 1 ? 'moderate' : 'weak'));
        $phase = $oni >= 0.5 ? 'el_nino' : ($oni <= -0.5 ? 'la_nina' : 'neutral');

        return ['oni' => $oni, 'phase' => $phase, 'strength' => $phase === 'neutral' ? '' : $strength,
            'label' => $phase === 'neutral' ? 'ENSO neutral' : ('A ' . $strength . ' ' . ($phase === 'el_nino' ? 'El Niño' : 'La Niña'))];
    }

    /** The ONI table's tail — what the ocean has actually been doing. */
    public static function observed(): string
    {
        // The kept history, while the kept reading is fresh (ClimateStore::enso reads NOAA again when it is not).
        if (self::kept('as_enso_seasons') && self::kept('as_enso_readings') && ClimateStore::enso()) {
            $rows = Cache::remember(self::cacheKey('tail'), 600, fn () => self::db()->table('as_enso_seasons')
                ->orderByDesc('year')->orderByDesc('mid')->limit(4)->get(['year', 'season', 'oni'])->map(fn ($r) => (array) $r)->reverse()->values()->all());
            if (count($rows) === 4) {
                $parsed = array_map(fn ($r) => $r['season'] . ' ' . $r['year'] . ' anomaly ' . sprintf('%.2f', (float) $r['oni']) . '°C', $rows);

                return self::observedWords($parsed, (float) end($rows)['oni']);
            }
        }
        try {
            return Cache::remember('enso-oni-facts', 86400, function () {
                $txt = Http::timeout(8)->get(self::ONI_URL)->body();
                $rows = array_values(array_filter(array_map('trim', explode("\n", $txt))));
                $parsed = [];
                $last = 0.0;
                foreach (array_slice($rows, -4) as $r) {
                    $p = preg_split('/\s+/', $r);
                    if (count($p) >= 4 && is_numeric($p[3])) {
                        $parsed[] = $p[0] . ' ' . $p[1] . ' anomaly ' . $p[3] . '°C';
                        $last = (float) $p[3];
                    }
                }

                return $parsed ? self::observedWords($parsed, $last) : '';
            });
        } catch (\Throwable $e) {
            return '';
        }
    }

    private static function observedWords(array $parsed, float $last): string
    {
        $state = $last >= 0.5 ? 'El Niño conditions' : ($last <= -0.5 ? 'La Niña conditions' : 'ENSO-neutral conditions');

        return 'Observed ENSO state (NOAA CPC ONI, 3-month running anomalies, most recent last): '
            . implode('; ', $parsed) . ' — i.e. currently ' . $state . '.';
    }

    /**
     * The advisory's own words — alert status and synopsis, which carry the
     * official odds ("greater than an 83% chance ..."). The latest kept
     * advisory while it was checked within Freshness::ENSO_DAYS, else read
     * from the discussion page now and kept.
     */
    public static function forecast(): string
    {
        $a = self::advisory();

        return $a ? (string) $a['text'] : '';
    }

    /**
     * The latest advisory as data: issuedOn, alert, synopsis, odds, nextOn,
     * text, checkedAt. Null when none is kept and NOAA cannot be read.
     */
    public static function advisory(): ?array
    {
        if (! self::kept('as_enso_advisories')) {
            // No table yet (before the migration): a day in the cache, as before; a failure is tried again in half an hour.
            $hit = Cache::get('enso-cpc-advisory');
            if (is_array($hit)) {
                return $hit ?: null;
            }
            $read = self::readAdvisory();
            Cache::put('enso-cpc-advisory', $read ?: [], $read ? 86400 : 1800);

            return $read;
        }
        $row = Cache::remember(self::cacheKey('advisory'), 600, fn () => (array) (self::db()->table('as_enso_advisories')->orderByDesc('issuedOn')->first() ?? []));
        if ($row && Freshness::ensoFresh($row['checkedAt'] ?? $row['fetchedAt'] ?? null)) {
            return $row;
        }
        // Nothing fresh kept: read it now, at most once in half an hour if NOAA does not answer.
        if (Cache::add(self::cacheKey('advisory:tried'), 1, now()->addMinutes(30))) {
            $read = self::readAdvisory();
            if ($read) {
                return self::keepAdvisory($read);
            }
        }

        return $row ?: null;
    }

    /**
     * Read the discussion page: the date it was issued, the alert, the
     * synopsis (the bold sentence after "Synopsis:"), the sentences of the
     * discussion that give odds, and the date of the next one. Null when the
     * page cannot be read or has neither an alert nor a synopsis.
     */
    public static function readAdvisory(): ?array
    {
        try {
            $html = (string) Http::timeout(15)->get(self::ADVISORY_URL)->body();
        } catch (\Throwable $e) {
            Log::warning('enso: advisory not read', ['e' => $e->getMessage()]);

            return null;
        }
        if ($html === '') {
            return null;
        }
        if (! mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'Windows-1252');
        }
        $clean = fn (string $s) => trim((string) preg_replace('/\s+/u', ' ', preg_replace('/\s*\[\s*Fig[^\]]*\]\s*/iu', ' ', html_entity_decode(strip_tags(str_ireplace('&nbsp;', ' ', $s)), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? ''));
        $text = $clean($html);

        $alert = preg_match('/ENSO Alert System Status:\s*(.{3,60}?)\s*(?:Synopsis|$)/iu', $text, $m) ? trim($m[1]) : '';
        $synopsis = '';
        if (preg_match('~Synopsis:\s*(?:</u>)?(?:\s|&nbsp;)*<strong>(.*?)</strong>~isu', $html, $m)) {
            $synopsis = $clean($m[1]);
        }
        if ($synopsis === '' && preg_match('/Synopsis:\s*(.{40,700})/iu', $text, $m)) {
            // The page without its bold synopsis: the first finished sentences inside 500 characters, as before.
            $dot = mb_strrpos(mb_substr($m[1], 0, 500), '. ');
            $synopsis = trim($dot !== false ? mb_substr($m[1], 0, $dot + 1) : mb_substr($m[1], 0, 500));
        }
        if ($alert === '' && $synopsis === '') {
            return null;
        }
        $date = function (string $re) use ($text): ?string {
            if (! preg_match($re, $text, $m)) {
                return null;
            }
            try {
                return Carbon::parse($m[1])->toDateString();
            } catch (\Throwable $e) {
                return null;
            }
        };
        $issued = $date('/NCEP\/NWS\s+(\d{1,2}\s+[A-Z][a-z]+\s+\d{4})/u') ?? now('UTC')->toDateString();
        $next = $date('/next ENSO Diagnostics? Discussion is scheduled for\s+(\d{1,2}\s+[A-Z][a-z]+\s+\d{4})/iu');
        // The sentences of the discussion that give odds, after the synopsis.
        $body = mb_substr($text, (int) (mb_strpos($text, $synopsis !== '' ? mb_substr($synopsis, 0, 40) : 'Synopsis') ?: 0) + mb_strlen($synopsis));
        $body = mb_substr($body, 0, (int) (mb_stripos($body, 'This discussion is a consolidated') ?: mb_strlen($body)));
        $odds = [];
        foreach (preg_split('/(?<=[.!?])\s+(?=[A-Z])/u', $body) ?: [] as $s) {
            if (preg_match('/\d{1,3}\s?% (?:chance|probability)|chances? (?:is|of|are)[^.]{0,120}\d{1,3}\s?%/iu', $s) && ! str_starts_with(trim($s), 'In summary')) {
                $odds[] = trim($s);
            }
        }

        return [
            'issuedOn' => $issued, 'alert' => $alert !== '' ? mb_substr($alert, 0, 80) : null, 'synopsis' => $synopsis !== '' ? $synopsis : null,
            'odds' => $odds ? mb_substr(implode(' ', array_slice(array_values(array_unique($odds)), 0, 3)), 0, 1200) : null, 'nextOn' => $next,
            'text' => 'Official ENSO forecast (NOAA CPC advisory' . ($alert !== '' ? ' — ' . $alert : '') . '): ' . ($synopsis !== '' ? $synopsis : 'see status.'),
        ];
    }

    /** Keep an advisory read now: a new one is a new row, the same one is marked checked. Returns the kept row. */
    public static function keepAdvisory(array $a): array
    {
        $now = now();
        $a += ['fetchedAt' => $now, 'checkedAt' => $now];
        $a['checkedAt'] = $now;
        if (! self::kept('as_enso_advisories')) {
            return $a;
        }
        try {
            $have = self::db()->table('as_enso_advisories')->where('issuedOn', $a['issuedOn'])->first();
            if (! $have) {
                // A page whose date could not be read is the latest advisory again when it says the same.
                $latest = self::db()->table('as_enso_advisories')->orderByDesc('issuedOn')->first();
                if ($latest && (string) $latest->synopsis === (string) ($a['synopsis'] ?? '') && (string) $latest->alert === (string) ($a['alert'] ?? '')) {
                    $have = $latest;
                    $a['issuedOn'] = substr((string) $latest->issuedOn, 0, 10);
                }
            }
            $cols = array_intersect_key($a, array_flip(['alert', 'synopsis', 'odds', 'nextOn', 'text']));
            if ($have) {
                self::db()->table('as_enso_advisories')->where('id', $have->id)->update($cols + ['checkedAt' => $now, 'updated_at' => $now]);
            } else {
                self::db()->table('as_enso_advisories')->insert($cols + ['issuedOn' => $a['issuedOn'], 'fetchedAt' => $now, 'checkedAt' => $now, 'created_at' => $now, 'updated_at' => $now]);
            }
            Cache::forget(self::cacheKey('advisory'));
            $row = (array) self::db()->table('as_enso_advisories')->where('issuedOn', $a['issuedOn'])->first();

            return $row ?: $a;
        } catch (\Throwable $e) {
            Log::warning('enso: advisory not kept', ['e' => $e->getMessage()]);

            return $a;
        }
    }

    /**
     * What the kept ENSO picture is, as a key (the advisory's date and the
     * ONI season): text written on one reading is reused only while it is
     * still the latest (OutlookShelf). '' when nothing is known.
     */
    public static function key(): string
    {
        $s = self::kept('as_enso_readings') ? ClimateStore::enso() : self::state();
        $a = self::advisory();
        if (! $s && ! $a) {
            return '';
        }

        return ($a['issuedOn'] ?? '-') . '|' . ($s['season'] ?? ($s ? sprintf('%.2f', $s['oni']) : '-'));
    }

    /** Whether a kept table is there (cached ten minutes, per connection). */
    private static function kept(string $table): bool
    {
        return (bool) Cache::remember(self::cacheKey('has:' . $table), 600, fn () => self::schema()->hasTable($table));
    }

    private static function cacheKey(string $k): string
    {
        return 'enso:' . self::conn() . ':' . $k;
    }
}
