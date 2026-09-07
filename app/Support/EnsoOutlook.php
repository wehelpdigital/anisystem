<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The live ENSO picture, read from NOAA CPC's public products and cached
 * for a day: the OBSERVED state (the ONI table — 3-month running SST
 * anomalies) and the OFFICIAL FORECAST (the ENSO advisory's alert status
 * and synopsis, probabilities included). One reader for every analysis
 * that weighs the climate — when-to-plant, what-to-plant, and Anee's two
 * season reports.
 *
 * Each product fails to '' quietly and separately: a farm analysis must
 * never break because a NOAA page moved, and the prompts carry their own
 * honest-ignorance wording for the empty case.
 */
final class EnsoOutlook
{
    /** Observed state + official forecast, ready for a prompt. '' offline. */
    public static function forPrompt(): string
    {
        $bits = array_filter([self::observed(), self::forecast()]);
        if (! $bits) {
            return '';
        }

        return implode(' ', $bits)
            . ' For Philippine farming, El Niño typically tilts toward below-normal rainfall,'
            . ' drought and heat stress; La Niña toward above-normal rain, flooding and'
            . ' typhoon-season wetness — weigh the forecast probabilities accordingly.';
    }

    /** The ONI table's tail — what the ocean has actually been doing. */
    public static function observed(): string
    {
        try {
            return Cache::remember('enso-oni-facts', 86400, function () {
                $txt = Http::timeout(8)->get('https://www.cpc.ncep.noaa.gov/data/indices/oni.ascii.txt')->body();
                $rows = array_values(array_filter(array_map('trim', explode("\n", $txt))));
                $parsed = [];
                foreach (array_slice($rows, -4) as $r) {
                    $p = preg_split('/\s+/', $r);
                    if (count($p) >= 4 && is_numeric($p[3])) {
                        $parsed[] = $p[0] . ' ' . $p[1] . ' anomaly ' . $p[3] . '°C';
                    }
                }
                if (! $parsed) {
                    return '';
                }
                preg_match('/(-?\d+(?:\.\d+)?)°C$/', end($parsed), $m);
                $last = (float) ($m[1] ?? 0);
                $state = $last >= 0.5 ? 'El Niño conditions'
                    : ($last <= -0.5 ? 'La Niña conditions' : 'ENSO-neutral conditions');

                return 'Observed ENSO state (NOAA CPC ONI, 3-month running anomalies, most recent last): '
                    . implode('; ', $parsed) . ' — i.e. currently ' . $state . '.';
            });
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * The advisory's own words — alert status and synopsis, which carry the
     * official probabilities ("greater than 90% chance of…"). Scraped from
     * the discussion page; the two phrases it keys on have opened that page
     * for decades, and either missing simply drops this sentence.
     */
    public static function forecast(): string
    {
        try {
            return Cache::remember('enso-cpc-forecast', 86400, function () {
                $html = Http::timeout(8)->get('https://www.cpc.ncep.noaa.gov/products/analysis_monitoring/enso_advisory/ensodisc.shtml')->body();
                $text = html_entity_decode(strip_tags(str_ireplace('&nbsp;', ' ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = preg_replace('/\s+/u', ' ', $text) ?? '';

                $status = '';
                if (preg_match('/ENSO Alert System Status:\s*(.{3,60}?)\s*(?:Synopsis|$)/iu', $text, $m)) {
                    $status = trim($m[1]);
                }
                $synopsis = '';
                if (preg_match('/Synopsis:\s*(.{40,700})/iu', $text, $m)) {
                    // Figure references are page furniture, not forecast.
                    $synopsis = preg_replace('/\s*\[\s*Fig[^\]]*\]\s*/iu', ' ', $m[1]);
                    // Cut at the last finished sentence inside the window.
                    $dot = mb_strrpos(mb_substr($synopsis, 0, 500), '. ');
                    $synopsis = trim($dot !== false ? mb_substr($synopsis, 0, $dot + 1) : mb_substr($synopsis, 0, 500));
                }

                if ($status === '' && $synopsis === '') {
                    return '';
                }

                return 'Official ENSO forecast (NOAA CPC advisory'
                    . ($status !== '' ? ' — ' . $status : '') . '): '
                    . ($synopsis !== '' ? $synopsis : 'see status.');
            });
        } catch (\Throwable $e) {
            return '';
        }
    }
}
