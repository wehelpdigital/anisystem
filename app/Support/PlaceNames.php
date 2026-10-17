<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * One matcher for place names (2026-10-18): a province, town or barangay
 * written any way ("COTABATO (NORTH COTABATO)", "City of Urdaneta",
 * "Science City of Muñoz", "Sto. Rosario", "Zone 3 Pob.") onto the app's own
 * gazetteer, as_locations, the names every lot stores (FarmLots::places()).
 *
 * Exact key first, then a close match (similarity 0.88 or more) inside the
 * parent only; it never guesses across provinces. The build scripts that
 * turn outside tables into our rows use its Python twin (the soil atlas
 * session's scratchpad scripts/placematch.py): change both together.
 *
 *   PlaceNames::norm('City of Urdaneta') === PlaceNames::norm('Urdaneta City')
 *   PlaceNames::province('North Cotabato')          // 'Cotabato'
 *   PlaceNames::town('Nueva Ecija', 'Science City of Muñoz') // 'Muñoz City'
 *   PlaceNames::resolve('Nueva Ecija', 'Munoz', null) // ['province'=>..,'town'=>..,'barangay'=>null,'level'=>'town']
 */
final class PlaceNames
{
    private const ROMAN = ['i' => '1', 'ii' => '2', 'iii' => '3', 'iv' => '4', 'v' => '5', 'vi' => '6', 'vii' => '7', 'viii' => '8',
        'ix' => '9', 'x' => '10', 'xi' => '11', 'xii' => '12'];

    private const WORDS = [
        '/\bsto\b\.?/' => 'santo', '/\bsta\b\.?/' => 'santa', '/\bsn\b\.?/' => 'san', '/\bgen\b\.?/' => 'general',
        '/\bpob\b\.?/' => 'poblacion', '/\bbrgy\b\.?/' => '', '/\bbarangay\b/' => '', '/\bbgy\b\.?/' => '', '/\bmun\b\.?/' => '',
        '/\bmunicipality of\b/' => '', '/\bprovince of\b/' => '', '/&/' => ' and ', '/\bproper\b/' => '',
    ];

    /** Province names written another way, onto ours. */
    public const PROVINCE_ALIAS = [
        'cotabato north cotabato' => 'Cotabato', 'north cotabato' => 'Cotabato', 'davao de oro' => 'Compostela Valley',
        'davao de oro compostela valley' => 'Compostela Valley', 'compostela valley' => 'Compostela Valley',
        'city of davao' => 'Davao Del Sur', 'davao city' => 'Davao Del Sur', 'city of zamboanga' => 'Zamboanga Del Sur',
        'zamboanga city' => 'Zamboanga Del Sur', 'city of cotabato' => 'Cotabato City', 'city of isabela' => 'Isabela City',
        'city of general santos dadiangas' => 'South Cotabato', 'general santos city' => 'South Cotabato',
        'western samar' => 'Samar', 'samar western samar' => 'Samar', 'mindoro occidental' => 'Occidental Mindoro',
        'mindoro oriental' => 'Oriental Mindoro', 'mt province' => 'Mountain Province', 'mountain' => 'Mountain Province',
        'ncr city of manila first district' => 'Manila City', 'city of manila' => 'Manila City', 'manila' => 'Manila City',
        'maguindanao del norte' => 'Maguindanao', 'maguindanao del sur' => 'Maguindanao', 'tawitawi' => 'Tawi-tawi',
    ];

    /** Towns written another way, by province and key. */
    private const TOWN_ALIAS = [
        'Bohol|getafe' => 'Jetafe', 'Davao Del Norte|islandgardensamal' => 'Samal City', 'Davao Del Norte|islandgardenofsamal' => 'Samal City',
    ];

    /** The comparison key: lowercase, no accents, no parentheticals, "City of X" = "X City" = "X", no spaces. */
    public static function norm(?string $s, bool $keepParen = false): string
    {
        if ($s === null || trim($s) === '') {
            return '';
        }
        $s = mb_strtolower(self::ascii($s));
        $s = $keepParen ? str_replace(['(', ')'], ' ', $s) : preg_replace('/\([^)]*\)/', ' ', $s);
        $s = preg_replace('/\bscience\s+city\s+of\s+/', ' ', $s);
        $s = preg_replace('/\bcity\s+of\s+/', ' ', $s);
        $s = preg_replace('/\b(city|capital)\b/', ' ', $s);
        foreach (self::WORDS as $re => $to) {
            $s = preg_replace($re, ' ' . $to . ' ', $s);
        }
        $s = preg_replace('/[^a-z0-9 ]+/', ' ', $s);
        $out = '';
        foreach (preg_split('/\s+/', trim($s)) as $t) {
            $out .= self::ROMAN[$t] ?? $t;
        }

        return $out;
    }

    /** A province key keeps "city" ("Isabela City" is not "Isabela"). */
    public static function provinceKey(?string $s): string
    {
        $t = mb_strtolower(self::ascii((string) $s));
        $t = preg_replace('/\([^)]*\)/', ' ', $t);
        $t = preg_replace('/^ph:/', '', $t);
        $t = preg_replace('/\bprovince of\b/', ' ', $t);
        $t = preg_replace('/[^a-z0-9 ]+/', ' ', $t);

        return str_replace(' ', '', trim($t));
    }

    /** Our province name, or null. */
    public static function province(?string $raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        $low = trim(preg_replace('/\s+/', ' ', str_replace(['(', ')'], ' ', mb_strtolower(self::ascii(preg_replace('/^ph:/i', '', $raw))))));
        foreach ([$low, trim(preg_replace('/[^a-z ]/', '', $low))] as $cand) {
            if (isset(self::PROVINCE_ALIAS[$cand])) {
                return self::PROVINCE_ALIAS[$cand];
            }
        }
        $all = self::provinces();
        $k = self::provinceKey($raw);
        if (isset($all[$k])) {
            return $all[$k];
        }

        return self::close($k, $all, 0.9);
    }

    /** Our town or city name inside that province, or null. */
    public static function town(?string $province, ?string $raw): ?string
    {
        $province = self::province($province) ?? $province;
        if (! $province || trim((string) $raw) === '') {
            return null;
        }
        $towns = self::towns($province);
        $k = self::norm($raw);
        if (isset(self::TOWN_ALIAS[$province . '|' . $k])) {
            return self::TOWN_ALIAS[$province . '|' . $k];
        }
        if ($hit = self::close($k, $towns, 0.86)) {
            return $hit;
        }
        if (preg_match_all('/\(([^)]*)\)/', (string) $raw, $m)) {
            foreach ($m[1] as $p) {
                if (isset($towns[self::norm($p)])) {
                    return $towns[self::norm($p)];
                }
            }
        }

        return null;
    }

    /** Our barangay name inside that town, or null. */
    public static function barangay(?string $province, ?string $town, ?string $raw): ?string
    {
        $province = self::province($province) ?? $province;
        $town = $province ? (self::town($province, $town) ?? $town) : $town;
        if (! $province || ! $town || trim((string) $raw) === '') {
            return null;
        }
        $b = self::barangays($province, $town);
        if (! $b) {
            return null;
        }
        $k = self::norm($raw);
        if ($hit = self::close($k, $b, 0.86)) {
            return $hit;
        }
        $kp = self::norm($raw, true);
        if (isset($b[$kp])) {
            return $b[$kp];
        }
        $k3 = str_replace('poblacion', '', $k);
        foreach ($b as $ck => $name) {
            if ($k3 !== '' && str_replace('poblacion', '', $ck) === $k3) {
                return $name;
            }
        }

        return null;
    }

    /**
     * The three names at once, each matched inside its parent:
     * ['province', 'town', 'barangay', 'level' => the deepest one matched, or null].
     */
    public static function resolve(?string $province, ?string $town = null, ?string $barangay = null): array
    {
        $p = self::province($province);
        $t = $p ? self::town($p, $town) : null;
        $b = ($p && $t) ? self::barangay($p, $t, $barangay) : null;

        return ['province' => $p, 'town' => $t, 'barangay' => $b, 'level' => $b ? 'barangay' : ($t ? 'town' : ($p ? 'province' : null))];
    }

    /** "Barangay, Town, Province" or "Town, Province" as a lot writes it. */
    public static function fromWhere(?string $where): array
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', (string) $where)), fn ($p) => $p !== '' && ! preg_match('/^philippines$/i', $p)));
        $n = count($parts);
        if (! $n) {
            return self::resolve(null);
        }
        if ($n === 1) {
            $p = self::province($parts[0]);

            return $p ? self::resolve($p) : self::resolve(null);
        }
        // The last part that is a province, then the town and barangay before it.
        for ($i = $n - 1; $i >= 0; $i--) {
            if ($p = self::province($parts[$i])) {
                $town = $parts[$i - 1] ?? null;
                $brgy = $parts[$i - 2] ?? null;

                return self::resolve($p, $town, $brgy);
            }
        }

        return self::resolve(null);
    }

    /* ---------------------------------------------------------------- data */

    /** @return array<string, string> key => our province name */
    private static function provinces(): array
    {
        return Cache::remember('placenames:provinces:v1', 86400, function () {
            $out = [];
            foreach (self::rows(fn ($q) => $q->where('type', 'province'), ['name']) as $r) {
                $out[self::provinceKey($r->name)] = (string) $r->name;
            }

            return $out;
        });
    }

    /** @return array<string, string> */
    private static function towns(string $province): array
    {
        return Cache::remember('placenames:towns:v1:' . md5($province), 86400, function () use ($province) {
            $out = [];
            foreach (self::rows(fn ($q) => $q->where('type', 'city')->where('province', $province), ['name']) as $r) {
                $out[self::norm($r->name)] = (string) $r->name;
            }

            return $out;
        });
    }

    /** @return array<string, string> */
    private static function barangays(string $province, string $town): array
    {
        return Cache::remember('placenames:brgy:v1:' . md5($province . '|' . $town), 86400, function () use ($province, $town) {
            $out = [];
            foreach (self::rows(fn ($q) => $q->where('type', 'barangay')->where('province', $province)->where('city', $town), ['name']) as $r) {
                $out[self::norm($r->name)] ??= (string) $r->name;
            }

            return $out;
        });
    }

    private static function rows(\Closure $where, array $cols): array
    {
        try {
            return $where(DB::table('as_locations'))->get($cols)->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /** Exact key, else the one clearly closest key (similarity >= $cut). */
    private static function close(string $key, array $cands, float $cut): ?string
    {
        if ($key === '') {
            return null;
        }
        if (isset($cands[$key])) {
            return $cands[$key];
        }
        $best = [];
        foreach ($cands as $ck => $name) {
            similar_text($key, (string) $ck, $pct);
            $r = $pct / 100;
            if ($r >= $cut) {
                $best[] = [$r, $name];
            }
        }
        if (! $best) {
            return null;
        }
        usort($best, fn ($a, $b) => $b[0] <=> $a[0]);
        if (count($best) === 1 || $best[0][0] > $best[1][0] + 0.03) {
            return $best[0][1];
        }

        return null;
    }

    private static function ascii(string $s): string
    {
        $s = strtr($s, ['ñ' => 'n', 'Ñ' => 'N', 'ň' => 'n', 'Ň' => 'N']);
        if (class_exists(\Normalizer::class)) {
            $n = \Normalizer::normalize($s, \Normalizer::FORM_D);
            if (is_string($n)) {
                $s = preg_replace('/\p{Mn}+/u', '', $n);
            }
        }

        return $s;
    }
}
