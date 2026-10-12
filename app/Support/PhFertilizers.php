<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Philippine fertilizer products (2026-10-12): the real products on the
 * shelves, for NPK Plus's "Philippine products" tab.
 *
 * Two sources, one table (as_ph_fertilizers):
 *   - database/fertilizers/ph-products.json: the popular products, checked by
 *     hand (brand, form, pack sizes), loaded by a migration (curated = 1);
 *   - the Fertilizer and Pesticide Authority's list of active registered
 *     fertilizers (an Excel file on fpa.da.gov.ph), read by the monthly
 *     fertilizers:fpa-sync command: every registered product, its company,
 *     guaranteed analysis, registration number and expiry. A product is one
 *     row per company and name (the FPA lists one registration per import
 *     lot); the latest expiry is kept. A curated row keeps its hand checked
 *     form and packs and takes the FPA's analysis and expiry.
 *
 * A product chosen in NPK Plus becomes an as_npk_products row (slug ph-{id})
 * the first time anyone uses it, so a saved plan keeps pointing at it.
 */
final class PhFertilizers
{
    public const PAGE = 'https://fpa.da.gov.ph/resources/reports/registered-products/';

    public const NUTRIENTS = ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'Si', 'Cl'];

    /** Oxide on a label → the element NPK Plus counts (P and K stay as oxides). */
    private const OXIDE = ['CAO' => ['Ca', 0.715], 'MGO' => ['Mg', 0.603], 'SO3' => ['S', 0.4005], 'SO4' => ['S', 0.334], 'ZNO' => ['Zn', 0.803],
        'B2O3' => ['B', 0.311], 'MNO' => ['Mn', 0.775], 'SIO2' => ['Si', 0.467], 'FE2O3' => ['Fe', 0.699], 'CUO' => ['Cu', 0.799], 'MOO3' => ['Mo', 0.667], 'P' => ['P2O5', 2.291], 'K' => ['K2O', 1.205]];

    public static function key(string $company, string $name): string
    {
        return Str::limit(mb_strtolower(trim(preg_replace('/\s+/', ' ', $company))) . '|' . mb_strtolower(trim(preg_replace('/\s+/', ' ', $name))), 190, '');
    }

    /** "Atlas Fertilizer Corporation" → "Atlas". */
    public static function brandOf(string $company): string
    {
        $b = preg_replace('/\b(incorporated|inc|corporation|corp|company|co|philippines|phils?|ltd|limited|enterprises?|trading|agri ?products|fertilizers?|industries|international|marketing|chemicals?|holdings|development|venture)\b\.?/i', '', $company);
        $b = trim(preg_replace('/[\s,.]+$/', '', preg_replace('/\s+/', ' ', (string) $b)), " ,.-");

        return $b !== '' ? Str::limit($b, 60, '') : Str::limit($company, 60, '');
    }

    /**
     * A guaranteed analysis as the FPA writes it ("N=14%, P₂O₅=14%, K₂O=14%",
     * oxides, ppm, g/L, ranges) → percent of each nutrient NPK Plus counts.
     *
     * @return array{0: array<string, float>, 1: string} [pct, basis w/w or w/v]
     */
    public static function parseAnalysis(string $text, string $name = ''): array
    {
        $t = strtr($text, ['₂' => '2', '₃' => '3', '₄' => '4', '₅' => '5', '₁' => '1', '：' => ':', '％' => '%']);
        $basis = preg_match('/w\s*\/\s*v|g\s*\/\s*l|g\s*per\s*l/i', $t) ? 'w/v' : 'w/w';
        $pct = [];
        $re = '/\b(total\s+nitrogen|total\s+n|nitrogen|n|p2o5|phosphorus|k2o|potash|potassium|cao|ca|calcium|mgo|mg|magnesium|so3|so4|s|sulfur|sulphur|zno|zn|zinc|b2o3|b|boron|fe2o3|fe|iron|mno|mn|manganese|cuo|cu|copper|moo3|mo|molybdenum|sio2|si|silicon|cl|chloride|chlorine)\b\s*(?:\([^)]*\))?\s*[=:]?\s*(\d+(?:\.\d+)?)\s*(?:%?\s*(?:-|to)\s*\d+(?:\.\d+)?)?\s*(%|ppm|g\s*\/\s*l|mg\s*\/\s*l)?/i';
        if (preg_match_all($re, $t, $all, PREG_SET_ORDER)) {
            foreach ($all as $m) {
                $k = strtoupper(preg_replace('/\s+/', ' ', $m[1]));
                $v = (float) $m[2];
                $unit = strtolower(preg_replace('/\s+/', '', $m[3] ?? '%'));
                if ($unit === 'ppm' || $unit === 'mg/l') {
                    $v /= 10000;
                } elseif ($unit === 'g/l') {
                    $v /= 10;
                }
                $map = ['TOTAL NITROGEN' => 'N', 'TOTAL N' => 'N', 'NITROGEN' => 'N', 'N' => 'N', 'P2O5' => 'P2O5', 'PHOSPHORUS' => 'P2O5', 'K2O' => 'K2O', 'POTASH' => 'K2O',
                    'POTASSIUM' => 'K2O', 'CA' => 'Ca', 'CALCIUM' => 'Ca', 'MG' => 'Mg', 'MAGNESIUM' => 'Mg', 'S' => 'S', 'SULFUR' => 'S', 'SULPHUR' => 'S', 'ZN' => 'Zn',
                    'ZINC' => 'Zn', 'B' => 'B', 'BORON' => 'B', 'FE' => 'Fe', 'IRON' => 'Fe', 'MN' => 'Mn', 'MANGANESE' => 'Mn', 'CU' => 'Cu', 'COPPER' => 'Cu', 'MO' => 'Mo',
                    'MOLYBDENUM' => 'Mo', 'SI' => 'Si', 'SILICON' => 'Si', 'CL' => 'Cl', 'CHLORIDE' => 'Cl', 'CHLORINE' => 'Cl'];
                if (isset(self::OXIDE[$k]) && ! in_array($k, ['P', 'K'], true)) {
                    [$el, $f] = self::OXIDE[$k];
                    $v *= $f;
                } elseif (isset($map[$k])) {
                    $el = $map[$k];
                } else {
                    continue;
                }
                if ($v > 0 && $v <= 100 && ! isset($pct[$el])) {
                    $pct[$el] = round($v, 4);
                }
            }
        }
        // No analysis words, a grade in the name: "16-20-0", "14-14-14 +11S".
        if (! array_intersect_key($pct, ['N' => 1, 'P2O5' => 1, 'K2O' => 1]) && preg_match('/(?<![\d.])(\d{1,2}(?:\.\d)?)\s*-\s*(\d{1,2}(?:\.\d)?)\s*-\s*(\d{1,2}(?:\.\d)?)(?![\d.])/', $name . ' ' . $t, $g)) {
            foreach (['N' => $g[1], 'P2O5' => $g[2], 'K2O' => $g[3]] as $el => $v) {
                if ((float) $v > 0) {
                    $pct[$el] = (float) $v;
                }
            }
        }
        if (array_sum($pct) > 100) {
            $pct = array_map(fn ($v) => round($v * 100 / array_sum($pct), 4), $pct);
        }

        return [$pct, $basis];
    }

    /** Which shelf of NPK Plus a product sits on. */
    public static function category(string $type, array $pct, string $name, string $basis): string
    {
        $type = mb_strtolower($type);
        $n = mb_strtolower($name);
        if (str_contains($type, 'bio')) {
            return 'bio';
        }
        // "Inorganic Fertilizer" holds the word organic too.
        if ((str_contains($type, 'organic') && ! str_contains($type, 'inorganic')) || str_contains($type, 'soil conditioner')) {
            return 'organic';
        }
        if ($basis === 'w/v' || preg_match('/foliar|liquid|soluble|\bwsf\b|\bsl\b|spray|drip|fertigation/i', $n)) {
            return 'foliar';
        }
        $N = $pct['N'] ?? 0; $P = $pct['P2O5'] ?? 0; $K = $pct['K2O'] ?? 0;
        $majors = ($N > 0) + ($P > 0) + ($K > 0);
        if ($majors >= 2 && $K > 0) {
            return 'complete';
        }
        if ($P > 0) {
            return 'phosphate';
        }
        if ($K > 0) {
            return 'potash';
        }
        if ($N > 0) {
            return 'nitrogen';
        }
        $sec = ($pct['Ca'] ?? 0) + ($pct['Mg'] ?? 0) + ($pct['S'] ?? 0) + ($pct['Si'] ?? 0);

        return $sec >= 3 ? 'secondary' : 'micro';
    }

    public static function form(string $name, string $basis, string $category): string
    {
        $n = mb_strtolower($name);
        if ($basis === 'w/v' || preg_match('/liquid|\bsl\b|\bec\b|flowable|suspension|solution/', $n)) {
            return 'liquid';
        }
        if (preg_match('/soluble|\bwsf\b|crystal|foliar/', $n) || $category === 'foliar') {
            return 'crystal';
        }
        if (preg_match('/powder|\bwp\b|\bdp\b/', $n) || in_array($category, ['bio', 'micro'], true)) {
            return 'powder';
        }

        return 'granular';
    }

    /* ---------------------------------------------------------------- */

    /** The rows of the first sheet of an .xlsx (no library: the file is zip and XML). */
    public static function readXlsx(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }
        $strings = [];
        if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $x = simplexml_load_string($ss);
            foreach ($x->si as $si) {
                $strings[] = isset($si->t) ? (string) $si->t : implode('', array_map(fn ($r) => (string) $r->t, iterator_to_array($si->r, false)));
            }
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheet === false) {
            return [];
        }
        $x = simplexml_load_string($sheet);
        $rows = [];
        foreach ($x->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $col = preg_replace('/\d+/', '', $ref);
                $type = (string) $c['t'];
                $v = isset($c->v) ? (string) $c->v : (isset($c->is->t) ? (string) $c->is->t : '');
                $cells[$col] = $type === 's' ? ($strings[(int) $v] ?? '') : $v;
            }
            $rows[] = $cells;
        }

        return $rows;
    }

    /**
     * Load the FPA's list from an .xlsx file. Returns the counts.
     *
     * @return array{rows:int,products:int,added:int,updated:int}
     */
    public static function syncFromFile(string $path, ?string $asOf = null): array
    {
        $rows = self::readXlsx($path);
        $head = null;
        $cols = [];
        $products = [];
        foreach ($rows as $r) {
            if ($head === null) {
                $up = array_map(fn ($v) => strtoupper(trim((string) $v)), $r);
                if (in_array('REGISTRATION NO.', $up, true) || preg_grep('/REGISTRATION/', $up)) {
                    $head = true;
                    foreach ($up as $col => $label) {
                        $key = match (true) {
                            str_contains($label, 'COMPANY') => 'company', str_contains($label, 'PRODUCT NAME') => 'name', str_contains($label, 'TYPE') => 'type',
                            str_contains($label, 'GUARANTEED') => 'analysis', str_contains($label, 'CROP') => 'crops', str_contains($label, 'REGISTRATION') => 'reg',
                            str_contains($label, 'EXPIRY') => 'expiry', default => null,
                        };
                        if ($key) {
                            $cols[$key] = $col;
                        }
                    }
                }
                continue;
            }
            $get = fn ($k) => trim(preg_replace('/\s+/', ' ', (string) ($r[$cols[$k] ?? ''] ?? '')));
            $company = $get('company');
            $name = $get('name');
            if ($company === '' || $name === '') {
                continue;
            }
            $exp = $get('expiry');
            $expiry = is_numeric($exp) ? \Carbon\Carbon::create(1899, 12, 30)->addDays((int) $exp)->toDateString() : (strtotime($exp) ? date('Y-m-d', strtotime($exp)) : null);
            $key = self::key($company, $name);
            // One product per company and name: the registration that runs longest.
            if (isset($products[$key]) && ($products[$key]['expiry'] ?? '') >= ($expiry ?? '')) {
                continue;
            }
            $products[$key] = ['company' => $company, 'name' => $name, 'type' => $get('type'), 'analysis' => $get('analysis'), 'reg' => $get('reg'), 'expiry' => $expiry];
        }
        $added = 0;
        $updated = 0;
        $now = now();
        foreach (array_chunk($products, 100, true) as $chunk) {
            $have = DB::table('as_ph_fertilizers')->whereIn('regKey', array_keys($chunk))->get()->keyBy('regKey');
            foreach ($chunk as $key => $p) {
                [$pct, $basis] = self::parseAnalysis($p['analysis'], $p['name']);
                $row = ['fpaName' => Str::limit($p['name'], 190, ''), 'company' => Str::limit($p['company'], 190, ''), 'fpaReg' => Str::limit($p['reg'], 60, ''),
                    'fpaExpiry' => $p['expiry'], 'fpaType' => Str::limit($p['type'], 80, ''), 'analysis' => $p['analysis'], 'asOf' => $asOf, 'active' => 1, 'updated_at' => $now];
                if ($old = $have->get($key)) {
                    // A hand checked product keeps its shelf, form and packs; it takes
                    // the FPA's analysis only when it parses into something.
                    if (! $old->curated || $pct) {
                        $row['pct'] = json_encode($pct);
                        $row['basis'] = $basis;
                    }
                    DB::table('as_ph_fertilizers')->where('id', $old->id)->update($row);
                    $updated++;
                } elseif ($pct) {
                    $cat = self::category($p['type'], $pct, $p['name'], $basis);
                    DB::table('as_ph_fertilizers')->insert($row + ['regKey' => $key, 'brand' => self::brandOf($p['company']), 'name' => Str::limit($p['name'], 190, ''),
                        'category' => $cat, 'form' => self::form($p['name'], $basis, $cat), 'pct' => json_encode($pct), 'basis' => $basis, 'packs' => null,
                        'densityKgL' => null, 'curated' => 0, 'created_at' => $now]);
                    $added++;
                }
            }
        }
        // A registration the FPA no longer lists has lapsed: off the shelf.
        if ($products) {
            DB::table('as_ph_fertilizers')->where('curated', 0)->whereNotIn('regKey', array_keys($products))->update(['active' => 0, 'updated_at' => $now]);
        }

        return ['rows' => count($rows), 'products' => count($products), 'added' => $added, 'updated' => $updated];
    }

    /**
     * The newest list on the FPA's page, if it changed since the last sync.
     *
     * @return array{changed:bool,url:?string,asOf:?string,result?:array,error?:string}
     */
    public static function fetchLatest(bool $force = false): array
    {
        $ua = ['User-Agent' => 'Mozilla/5.0 (compatible; anee.io fertilizer list; +https://anee.io)'];
        try {
            $page = Http::withHeaders($ua)->timeout(30)->get(self::PAGE);
        } catch (\Throwable $e) {
            return ['changed' => false, 'url' => null, 'asOf' => null, 'error' => 'The FPA page could not be read: ' . $e->getMessage()];
        }
        if (! $page->ok()) {
            return ['changed' => false, 'url' => null, 'asOf' => null, 'error' => 'The FPA page answered ' . $page->status() . '.'];
        }
        // Only a file on the FPA's own host (the page also links a mirror that is not).
        if (! preg_match('#https?://fpa\.da\.gov\.ph/wp-content/uploads/[^"\'\s]*FERTILIZER[^"\'\s]*\.xlsx#i', $page->body(), $m)) {
            return ['changed' => false, 'url' => null, 'asOf' => null, 'error' => 'No fertilizer list (.xlsx) on the FPA page.'];
        }
        $url = html_entity_decode($m[0]);
        $asOf = null;
        if (preg_match('/AS[-_ ]OF[-_ ]([0-9.\-_]+|[A-Z]+[-_ ]\d{1,2}[-_ ]\d{4})/i', $url, $d)) {
            $ts = strtotime(str_replace(['.', '_'], ['-', ' '], $d[1]));
            $asOf = $ts ? date('Y-m-d', $ts) : null;
        }
        $last = (string) \App\Models\AsSiteSetting::get('fpa.fertilizers.url', '');
        if (! $force && $last === $url && DB::table('as_ph_fertilizers')->where('curated', 0)->exists()) {
            return ['changed' => false, 'url' => $url, 'asOf' => $asOf];
        }
        $tmp = tempnam(sys_get_temp_dir(), 'fpa');
        try {
            $file = Http::withHeaders($ua)->timeout(120)->sink($tmp)->get($url);
            if (! $file->ok()) {
                return ['changed' => false, 'url' => $url, 'asOf' => $asOf, 'error' => 'The list answered ' . $file->status() . '.'];
            }
            $result = self::syncFromFile($tmp, $asOf);
        } finally {
            @unlink($tmp);
        }
        if (($result['products'] ?? 0) > 0) {
            \App\Models\AsSiteSetting::put('fpa.fertilizers.url', $url);
            \App\Models\AsSiteSetting::put('fpa.fertilizers.synced', now()->toIso8601String());
        }

        return ['changed' => true, 'url' => $url, 'asOf' => $asOf, 'result' => $result];
    }

    /** The hand checked products from the repo's JSON, into the table. */
    public static function seedCurated(): int
    {
        $data = json_decode((string) @file_get_contents(database_path('fertilizers/ph-products.json')), true) ?: [];
        $now = now();
        $n = 0;
        foreach ($data['products'] ?? [] as $p) {
            $key = self::key((string) ($p['fpaCompany'] ?? $p['brand']), (string) ($p['fpaName'] ?? $p['name']));
            $pct = array_filter(array_map('floatval', (array) ($p['pct'] ?? [])), fn ($v) => $v > 0);
            DB::table('as_ph_fertilizers')->upsert([[
                'regKey' => $key, 'company' => (string) ($p['fpaCompany'] ?? $p['brand']), 'brand' => (string) $p['brand'], 'name' => (string) $p['name'],
                'fpaName' => (string) ($p['fpaName'] ?? $p['name']), 'fpaReg' => (string) ($p['fpaReg'] ?? ''), 'fpaExpiry' => $p['fpaExpiry'] ?? null, 'fpaType' => (string) ($p['fpaType'] ?? ''),
                'category' => (string) $p['category'], 'form' => (string) $p['form'], 'analysis' => (string) ($p['fpaAnalysis'] ?? ''), 'pct' => json_encode($pct),
                'packs' => json_encode(array_values((array) ($p['packs'] ?? []))), 'densityKgL' => $p['densityKgL'] ?? null, 'basis' => (string) ($p['basis'] ?? 'w/w'),
                'curated' => 1, 'active' => 1, 'asOf' => null, 'created_at' => $now, 'updated_at' => $now,
            ]], ['regKey'], ['company', 'brand', 'name', 'fpaName', 'fpaReg', 'fpaExpiry', 'fpaType', 'category', 'form', 'analysis', 'pct', 'packs', 'densityKgL', 'basis', 'curated', 'active', 'updated_at']);
            $n++;
        }

        return $n;
    }

    /* ---------------------------------------------------------------- */

    /** A row as the tab lists it. */
    public static function row(object $r): array
    {
        $pct = json_decode((string) $r->pct, true) ?: [];
        $grade = ($pct['N'] ?? 0) || ($pct['P2O5'] ?? 0) || ($pct['K2O'] ?? 0)
            ? implode('-', array_map(fn ($k) => rtrim(rtrim(number_format((float) ($pct[$k] ?? 0), 2, '.', ''), '0'), '.'), ['N', 'P2O5', 'K2O'])) : '';

        return ['id' => (int) $r->id, 'brand' => $r->brand, 'name' => $r->name, 'company' => $r->company, 'category' => $r->category, 'form' => $r->form,
            'pct' => $pct, 'grade' => $grade, 'packs' => json_decode((string) $r->packs, true) ?: [], 'reg' => $r->fpaReg, 'expiry' => $r->fpaExpiry, 'curated' => (bool) $r->curated];
    }

    /** The tab's list: the hand checked ones first, then every registered match. */
    public static function search(string $q = '', string $category = '', int $limit = 60): array
    {
        $qb = DB::table('as_ph_fertilizers')->where('active', 1);
        if ($category !== '') {
            $qb->where('category', $category);
        }
        foreach (array_filter(explode(' ', mb_strtolower(trim(preg_replace('/\s*\+\s*/', '-', $q))))) as $w) {
            $qb->where(fn ($x) => $x->where('name', 'like', '%' . $w . '%')->orWhere('brand', 'like', '%' . $w . '%')->orWhere('company', 'like', '%' . $w . '%')
                ->orWhere('analysis', 'like', '%' . $w . '%')->orWhere('fpaReg', 'like', '%' . $w . '%'));
        }
        if (trim($q) === '') {
            $qb->where('curated', 1);
        }

        return $qb->orderByDesc('curated')->orderBy('brand')->orderBy('name')->limit($limit)->get()->map(fn ($r) => self::row($r))->all();
    }

    /** The NPK Plus product for a Philippine product, made the first time anyone uses it. */
    public static function product(int $id): ?object
    {
        $r = DB::table('as_ph_fertilizers')->where('id', $id)->where('active', 1)->first();
        if (! $r) {
            return null;
        }
        $slug = 'ph-' . $r->id;
        $pct = json_decode((string) $r->pct, true) ?: [];
        $packs = json_decode((string) $r->packs, true) ?: [];
        $liquid = $r->form === 'liquid';
        // The first pack sold: "50 kg", "25 kg", "1 kg", "500 g", "1 L", "500 mL".
        $pack = null;
        foreach ($packs as $p) {
            if (preg_match('/([\d.]+)\s*(kg|g|l|ml)\b/i', (string) $p, $m)) {
                $v = (float) $m[1];
                $u = strtolower($m[2]);
                $pack = in_array($u, ['g', 'ml'], true) ? $v / 1000 : $v;
                break;
            }
        }
        $row = ['userId' => null, 'slug' => $slug, 'name' => Str::limit(($r->brand && ! Str::startsWith(mb_strtolower($r->name), mb_strtolower($r->brand)) ? $r->brand . ' ' : '') . $r->name, 120, ''),
            'local' => null, 'category' => $r->category, 'form' => $liquid ? 'liquid' : ($r->form === 'granular' ? 'granular' : 'powder'), 'unit' => $liquid ? 'L' : 'kg',
            'bagKg' => $pack ?? ($liquid ? null : 50), 'densityKgL' => $liquid ? ((float) $r->densityKgL ?: 1) : null,
            'note' => 'FPA reg. ' . $r->fpaReg . ($r->company ? ', ' . $r->company : ''), 'sortOrder' => 900, 'deleteStatus' => 1, 'updated_at' => now()];
        foreach (self::NUTRIENTS as $n) {
            $row[$n] = round((float) ($pct[$n] ?? 0), 3);
        }
        $have = DB::table('as_npk_products')->whereNull('userId')->where('slug', $slug)->first();
        if ($have) {
            DB::table('as_npk_products')->where('id', $have->id)->update($row);

            return DB::table('as_npk_products')->find($have->id);
        }
        $id = DB::table('as_npk_products')->insertGetId($row + ['created_at' => now()]);

        return DB::table('as_npk_products')->find($id);
    }
}
