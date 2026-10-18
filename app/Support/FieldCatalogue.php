<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The field catalogue (2026-10-08): the pests, diseases and weeds of every
 * crop anee.io knows, and the weed control plans by crop and age. Rice came
 * first (2026-10-06); now each crop of the catalog has its own.
 *
 * The rows live in the database (as_crop_problems, as_weeds, as_weed_plans),
 * loaded from database/field-catalogue/*.json by a migration (sync()), the
 * way the site pages are. A row the files brought carries source = file;
 * the sync never touches any other. Read through here, cached; with no
 * table yet (a fresh copy, a test database) the files themselves answer.
 *
 * Used by the public hubs (/pests, /diseases, /weeds), their finders and
 * the weed control helper, the short fact sheet of an entry that has no
 * profile page yet, and the app's Field helpers.
 */
final class FieldCatalogue
{
    /** The hubs' shelves, one per crop family: label, local words, hue, icon path. */
    public const SHELVES = [
        'rice' => ['Rice', 'Palay', 98, 'M12 21V9m0 0c0-3 1.5-5.5 4-7m-4 7C12 6 10.5 3.5 8 2m4 13c2-2 4.5-3 7-3m-7 3c-2-2-4.5-3-7-3'],
        'corn' => ['Corn and Sorghum', 'Mais, batad', 45, 'M12 21c-3 0-4.5-4-4.5-9S9 3 12 3s4.5 4 4.5 9-1.5 9-4.5 9zm-2.5-14h5m-5.5 4h6m-6 4h6'],
        'legumes' => ['Legumes', 'Monggo, mani, sitaw', 75, 'M4 20C4 11 11 4 20 4c0 9-7 16-16 16zm4.5-4.5h.01M12 12h.01m3.5-3.5h.01'],
        'roots' => ['Root Crops', 'Kamote, kamoteng kahoy, gabi', 15, 'M12 3v4m-3-2.5L12 7l3-2.5M7 12.5C7 9.5 9.5 7 12 7s5 2.5 5 5.5c0 4.5-2.5 8.5-5 8.5s-5-4-5-8.5z'],
        'vegetables' => ['Vegetables', 'Gulay', 140, 'M7 21c-2-4-1-9 3-12m4 12c2-4 1-9-3-12M12 9c0-3 2-6 5-6-1 3-2.5 5-5 6zm0 0c0-3-2-6-5-6 1 3 2.5 5 5 6z'],
        'onions' => ['Onion and Garlic', 'Sibuyas, bawang', 300, 'M12 3c0 3-6 6-6 11a6 6 0 0012 0c0-5-6-8-6-11zm-2 17.5c.6-3 .6-6 0-9m4 9c-.6-3-.6-6 0-9'],
        'industrial' => ['Sugarcane and Industrial Crops', 'Tubo, tabako, abaka', 190, 'M9 21V3m0 5c2-3 5-4 9-4M9 12.5c2-2 5-3 9-2M9 17c2-1.5 4.5-2 7.5-1.5M6 8h6m-6 4.5h6M6 17h6'],
        'fruits' => ['Fruits and Plantation Crops', 'Niyog, saging, mangga', 25, 'M12 8c-4 0-7 3-7 7s3 6 7 6 7-2 7-6-3-7-7-7zm0 0c0-2 1-4 3-5'],
    ];

    /** A crop's shelf by its group in the crop catalog (rice and corn are their own). */
    private const SHELF_OF_GROUP = [
        'Legumes' => 'legumes', 'Root crops' => 'roots', 'Leafy vegetables' => 'vegetables', 'Fruit vegetables' => 'vegetables',
        'Onions & garlic' => 'onions', 'Industrial crops' => 'industrial', 'Fruit trees' => 'fruits', 'Other' => 'vegetables',
    ];

    private const CACHE = 'field-catalogue:v3:';

    private const TTL = 21600;

    private static array $memo = [];

    /** The shelf a crop sits on. */
    public static function shelfOf(string $crop): string
    {
        if (str_starts_with($crop, 'rice')) {
            return 'rice';
        }
        if (str_starts_with($crop, 'corn') || $crop === 'sorghum') {
            return 'corn';
        }

        return self::SHELF_OF_GROUP[CropCatalog::CROPS[$crop]['group'] ?? ''] ?? 'fruits';
    }

    /**
     * Every crop the finders and the helper offer, shelf by shelf, each as
     * [local name, English name, icon, shelf]. The three rices of the crop
     * catalog are one here (how the rice was planted is the helper's own
     * question), and the temperate crops are not offered at home.
     */
    public static function crops(): array
    {
        if (isset(self::$memo['crops'])) {
            return self::$memo['crops'];
        }
        $byShelf = array_fill_keys(array_keys(self::SHELVES), []);
        foreach (CropCatalog::CROPS as $k => $c) {
            if (! empty($c['intl']) || $k === 'vegetables' || str_starts_with($k, 'rice_')) {
                continue;
            }
            [$local, $en] = self::names($k, (string) ($c['label'] ?? $k));
            $shelf = self::shelfOf($k);
            $byShelf[$shelf][$k] = [$local, $en, $c['icon'] ?? '', $shelf];
        }

        return self::$memo['crops'] = array_merge(...array_values($byShelf));
    }

    /** One crop key as the catalogue keeps it: every rice is rice, a bare corn is field corn. */
    public static function cropKey(?string $key): ?string
    {
        $key = strtolower(trim((string) $key));
        if ($key === '') {
            return null;
        }
        if (str_starts_with($key, 'rice')) {
            return 'rice';
        }
        if ($key === 'corn') {
            return 'corn_yellow';
        }

        return isset(self::crops()[$key]) ? $key : null;
    }

    /** A crop's name for a button: "Mais na malagkit" over "Corn, white or glutinous". */
    private static function names(string $key, string $label): array
    {
        if ($key === 'rice') {
            return ['Palay', 'Rice'];
        }
        preg_match('/^(.*?)\s*(?:\(([^()]*)\))?\s*$/u', $label, $m);
        $en = str_replace([' — ', ' / ', ' - '], [', ', ' or ', ', '], trim($m[1] ?? $label));
        $local = trim($m[2] ?? '');

        return [$local !== '' ? $local : $en, $en];
    }

    /**
     * The photo of an entry that has no profile page of its own (2026-10-14):
     * {src, thumb, alt, credit, page} from database/field-catalogue/photos.json,
     * freely licensed only (CC0, public domain, CC BY, CC BY SA), each with
     * its author and licence. Null when there is none yet.
     */
    public static function photo(string $section, string $slug): ?array
    {
        static $all = null;
        $all ??= json_decode((string) @file_get_contents(database_path('field-catalogue/photos.json')), true) ?: [];
        $p = $all[$section][$slug] ?? null;

        return $p && ! empty($p['src']) ? $p : null;
    }

    /** One section's pests or diseases, in the hub's order: slug => facts. */
    public static function problems(string $section): array
    {
        return in_array($section, ['pests', 'diseases'], true) ? (self::load('problems')[$section] ?? []) : [];
    }

    public static function problem(string $section, string $slug): ?array
    {
        return self::problems($section)[$slug] ?? null;
    }

    /** Every weed, in the hub's order: slug => facts. */
    public static function weeds(): array
    {
        return self::load('weeds');
    }

    public static function weed(string $slug): ?array
    {
        return self::weeds()[$slug] ?? null;
    }

    /**
     * The weed control plans: key => label, local, crops, after, windows
     * (key => window), ingredients, prevent (what keeps weeds out before and
     * through the season) and critical (crop or '*' => {from, to, say}, the
     * stretch when weeds cost the most yield).
     */
    public static function weedPlans(): array
    {
        return self::load('plans');
    }

    /**
     * One window's active ingredients for one crop and weed group, as the
     * helper and the Crop Protocol show them (2026-10-18): a name scoped to
     * other crops (`only`) is left out; when any is on a product registered
     * in the Philippines for this crop (`on`), only those are given; when
     * none is, the rest come marked: 'abroad' (no product registered in the
     * Philippines) or 'other' (registered here for other crops only).
     * Returns [[name, mark|null], ...]. The helper's JS does the same.
     */
    public static function forCrop(array $window, string $crop, string $group): array
    {
        $on = (array) ($window['on'] ?? []);
        $only = (array) ($window['only'] ?? []);
        $abroad = array_flip((array) ($window['abroad'] ?? []));
        $cand = array_values(array_filter((array) ($window[$group] ?? []), fn ($n) => ! isset($only[$n]) || in_array($crop, (array) $only[$n], true)));
        if (! $on) {
            // A plan written before the registrations were read: every name as it stands.
            return array_map(fn ($n) => [$n, null], $cand);
        }
        $reg = array_values(array_filter($cand, fn ($n) => in_array($crop, (array) ($on[$n] ?? []), true)));
        if ($reg) {
            return array_map(fn ($n) => [$n, null], $reg);
        }

        return array_map(fn ($n) => [$n, isset($abroad[$n]) ? 'abroad' : 'other'], $cand);
    }

    /** A crop's weed free stretch in one plan: {from?, to?, say}, or null. */
    public static function critical(array $plan, string $crop): ?array
    {
        $c = (array) ($plan['critical'] ?? []);

        return $c[$crop] ?? $c['*'] ?? null;
    }

    /** Every active ingredient the plans name, with its HRAC group or groups. */
    public static function ingredients(): array
    {
        $all = [];
        foreach (self::weedPlans() as $p) {
            $all += (array) ($p['ingredients'] ?? []);
        }
        ksort($all, SORT_NATURAL | SORT_FLAG_CASE);

        return $all;
    }

    /**
     * Who sells it (2026-10-12): a Shopee search and a Lazada tag page for one
     * active ingredient, its bracket notes and "plus" dropped. Not a
     * recommendation; the pages say so. The finders' JS builds the same.
     */
    public static function shopLinks(string $ingredient): array
    {
        $q = trim((string) preg_replace('/\s+/', ' ', preg_replace(['/\s*\([^)]*\)/', '/\s+plus\s+/i', '/\b2,4 D\b/'], ['', ' ', '2,4-D'], $ingredient)));
        $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($q)), '-');

        return ['name' => $q, 'shopee' => 'https://shopee.ph/search?keyword=' . rawurlencode(mb_strtolower($q)), 'lazada' => 'https://www.lazada.com.ph/tag/' . $slug . '/'];
    }

    /** The crop's names for a sentence: "sweet potato", "rice". */
    public static function cropWord(string $crop): string
    {
        $en = self::crops()[$crop][1] ?? $crop;

        return mb_strtolower(preg_replace('/,.*$/', '', $en));
    }

    /* ---------------------------------------------------------------- */

    private static function load(string $what): array
    {
        if (isset(self::$memo[$what])) {
            return self::$memo[$what];
        }
        $data = null;
        try {
            $data = Cache::remember(self::CACHE . $what, self::TTL, fn () => self::fromDb($what));
        } catch (\Throwable $e) {
            $data = null;   // no table yet, or no database: the files answer
        }

        return self::$memo[$what] = $data ?: self::fromFiles($what);
    }

    private static function fromDb(string $what): ?array
    {
        $table = ['problems' => 'as_crop_problems', 'weeds' => 'as_weeds', 'plans' => 'as_weed_plans'][$what];
        $rows = DB::table($table)->where('deleteStatus', 1)->orderBy('sortOrder')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return null;
        }
        $j = fn ($v) => is_string($v) ? (json_decode($v, true) ?: []) : (array) ($v ?? []);
        $out = [];
        foreach ($rows as $r) {
            if ($what === 'problems') {
                $out[$r->section][$r->slug] = self::problemShape(['name' => $r->name, 'sci' => $r->sci, 'shelf' => $r->shelf, 'kind' => $r->kind,
                    'crops' => $j($r->crops), 'parts' => $j($r->parts), 'signs' => $j($r->signs), 'local' => $r->local, 'hint' => $r->hint,
                    'ai' => $j($r->ai), 'no' => $r->noSpray, 'sources' => $j($r->sources), 'ranks' => $j($r->ranks)]);
            } elseif ($what === 'weeds') {
                // stop and named came with the registrations (2026-10-18); a row from before has neither.
                $out[$r->slug] = self::weedShape(['name' => $r->name, 'sci' => $r->sci, 'group' => $r->wgroup, 'life' => $r->life, 'local' => $r->local,
                    'hint' => $r->hint, 'crops' => $j($r->crops), 'sources' => $j($r->sources),
                    'stop' => $r->stop ?? '', 'named' => $j($r->named ?? null)]);
            } else {
                $out[$r->planKey] = self::planShape(['label' => $r->label, 'local' => $r->local, 'crops' => $j($r->crops), 'after' => $r->after,
                    'windows' => $j($r->windows), 'ingredients' => $j($r->ingredients), 'sources' => $j($r->sources),
                    'prevent' => $j($r->prevent ?? null), 'critical' => $j($r->critical ?? null)]);
            }
        }

        return $out;
    }

    private static function fromFiles(string $what): array
    {
        $f = self::file($what);
        $out = [];
        if ($what === 'problems') {
            foreach (['pests', 'diseases'] as $sec) {
                foreach ($f[$sec] ?? [] as $e) {
                    $out[$sec][$e['slug']] = self::problemShape($e);
                }
            }
        } elseif ($what === 'weeds') {
            foreach ($f as $e) {
                $out[$e['slug']] = self::weedShape($e);
            }
        } else {
            foreach ($f['plans'] ?? [] as $p) {
                $out[$p['key']] = self::planShape($p + ['ingredients' => self::planIngredients($p, $f['ingredients'] ?? [])]);
            }
        }

        return $out;
    }

    /** The JSON a file holds: problems, weeds or plans. */
    private static function file(string $what): array
    {
        $name = ['problems' => 'problems', 'weeds' => 'weeds', 'plans' => 'weed-plans'][$what];

        return json_decode((string) @file_get_contents(database_path('field-catalogue/' . $name . '.json')), true) ?: [];
    }

    /** The groups of each ingredient one plan names, out of the shared list. */
    private static function planIngredients(array $plan, array $all): array
    {
        $mine = [];
        foreach ($plan['windows'] ?? [] as $w) {
            foreach (['grasses', 'sedges', 'broadleaves'] as $g) {
                foreach ($w[$g] ?? [] as $name) {
                    $mine[$name] = $all[$name] ?? [];
                }
            }
        }

        return $mine;
    }

    /* A source naming an agency, a state university or a research institute
     * never shows (the owner, 2026-10-17): the shapes keep only the others,
     * so the hubs, fact sheets and field helpers all read the same. */
    private static function problemShape(array $e): array
    {
        return ['name' => (string) $e['name'], 'sci' => (string) ($e['sci'] ?? ''), 'group' => (string) ($e['shelf'] ?? 'fruits'), 'kind' => (string) ($e['kind'] ?? 'Insect'),
            'crops' => array_values((array) ($e['crops'] ?? [])), 'parts' => array_values((array) ($e['parts'] ?? [])), 'signs' => array_values((array) ($e['signs'] ?? [])),
            'local' => (string) ($e['local'] ?? ''), 'hint' => (string) ($e['hint'] ?? ''), 'ai' => array_values((array) ($e['ai'] ?? [])),
            'no' => (string) ($e['no'] ?? ''), 'sources' => GovSource::keep((array) ($e['sources'] ?? [])),
            // crop => its place in that crop's own order of importance
            'ranks' => array_map('intval', (array) ($e['ranks'] ?? []))];
    }

    private static function weedShape(array $e): array
    {
        return ['name' => (string) $e['name'], 'sci' => (string) ($e['sci'] ?? ''), 'group' => (string) $e['group'], 'life' => (string) ($e['life'] ?? ''),
            'local' => (string) ($e['local'] ?? ''), 'hint' => (string) ($e['hint'] ?? ''), 'crops' => array_values((array) ($e['crops'] ?? [])),
            'sources' => GovSource::keep((array) ($e['sources'] ?? [])),
            // How to keep it out without a spray (2026-10-18), and the active ingredients whose
            // Philippine labels name this weed: [[ingredient, [HRAC groups], [crops]], ...].
            'stop' => (string) ($e['stop'] ?? ''), 'named' => array_values((array) ($e['named'] ?? []))];
    }

    private static function planShape(array $p): array
    {
        $windows = [];
        foreach ((array) ($p['windows'] ?? []) as $w) {
            // label: as researched; short: the age button; title: the answer's heading;
            // on: name => the plan's crops a Philippine registration names; abroad: no
            // registration here; only: name => the crops it is meant for (forCrop()).
            $windows[$w['key']] = ['label' => (string) $w['label'], 'short' => (string) ($w['short'] ?? $w['label']), 'title' => (string) ($w['title'] ?? $w['label']),
                'timing' => (string) ($w['timing'] ?? 'post'), 'stage' => (string) ($w['stage'] ?? ''),
                'steps' => array_values((array) ($w['steps'] ?? [])), 'grasses' => array_values((array) ($w['grasses'] ?? [])),
                'sedges' => array_values((array) ($w['sedges'] ?? [])), 'broadleaves' => array_values((array) ($w['broadleaves'] ?? [])),
                'on' => (object) ($w['on'] ?? []), 'abroad' => array_values((array) ($w['abroad'] ?? [])), 'only' => (object) ($w['only'] ?? [])];
        }

        return ['label' => (string) $p['label'], 'local' => (string) ($p['local'] ?? ''), 'crops' => array_values((array) ($p['crops'] ?? [])),
            'after' => (string) ($p['after'] ?? 'after planting'), 'windows' => $windows, 'ingredients' => (array) ($p['ingredients'] ?? []),
            'sources' => GovSource::keep((array) ($p['sources'] ?? [])),
            'prevent' => array_values(array_filter(array_map('strval', (array) ($p['prevent'] ?? [])))), 'critical' => (array) ($p['critical'] ?? [])];
    }

    /* ---------------------------------------------------------------- */

    /**
     * Load the files into the tables: new and changed rows written, the
     * file rows no longer shipped retired (deleteStatus 0), rows from
     * anywhere else left alone. Returns the counts.
     */
    public static function sync(): array
    {
        $now = now();
        $enc = fn ($v) => json_encode(array_values((array) $v), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $n = ['problems' => 0, 'weeds' => 0, 'plans' => 0];

        // Pests and diseases.
        $f = self::file('problems');
        $theirs = DB::table('as_crop_problems')->where('source', '!=', 'file')->get(['section', 'slug'])->map(fn ($r) => $r->section . '/' . $r->slug)->flip();
        foreach (['pests', 'diseases'] as $sec) {
            $rows = [];
            foreach ($f[$sec] ?? [] as $i => $e) {
                if (isset($theirs[$sec . '/' . $e['slug']])) {
                    continue;
                }
                $rows[] = ['section' => $sec, 'slug' => $e['slug'], 'name' => $e['name'], 'sci' => $e['sci'] ?? '', 'shelf' => $e['shelf'], 'kind' => $e['kind'],
                    'crops' => $enc($e['crops'] ?? []), 'parts' => $enc($e['parts'] ?? []), 'signs' => $enc($e['signs'] ?? []), 'local' => $e['local'] ?? '',
                    'hint' => $e['hint'] ?? '', 'ai' => $enc($e['ai'] ?? []), 'noSpray' => $e['no'] ?? '', 'sources' => $enc($e['sources'] ?? []),
                    'ranks' => json_encode((object) ($e['ranks'] ?? []), JSON_UNESCAPED_UNICODE), 'sortOrder' => $i, 'source' => 'file', 'deleteStatus' => 1, 'created_at' => $now, 'updated_at' => $now];
            }
            foreach (array_chunk($rows, 40) as $chunk) {
                DB::table('as_crop_problems')->upsert($chunk, ['section', 'slug'],
                    ['name', 'sci', 'shelf', 'kind', 'crops', 'parts', 'signs', 'local', 'hint', 'ai', 'noSpray', 'sources', 'ranks', 'sortOrder', 'deleteStatus', 'updated_at']);
            }
            DB::table('as_crop_problems')->where('source', 'file')->where('section', $sec)
                ->whereNotIn('slug', array_column($rows, 'slug') ?: [''])->update(['deleteStatus' => 0, 'updated_at' => $now]);
            $n['problems'] += count($rows);
        }

        // Weeds.
        $theirs = DB::table('as_weeds')->where('source', '!=', 'file')->pluck('slug')->flip();
        // stop and named (2026-10-18) are written once their columns exist.
        $wMore = Schema::hasColumn('as_weeds', 'named');
        $rows = [];
        foreach (self::file('weeds') as $i => $e) {
            if (isset($theirs[$e['slug']])) {
                continue;
            }
            $rows[] = ['slug' => $e['slug'], 'name' => $e['name'], 'sci' => $e['sci'] ?? '', 'wgroup' => $e['group'], 'life' => $e['life'] ?? '',
                'local' => $e['local'] ?? '', 'hint' => $e['hint'] ?? '', 'crops' => $enc($e['crops'] ?? []), 'sources' => $enc($e['sources'] ?? []),
                'sortOrder' => $i, 'source' => 'file', 'deleteStatus' => 1, 'created_at' => $now, 'updated_at' => $now]
                + ($wMore ? ['stop' => (string) ($e['stop'] ?? ''), 'named' => $enc($e['named'] ?? [])] : []);
        }
        foreach (array_chunk($rows, 40) as $chunk) {
            DB::table('as_weeds')->upsert($chunk, ['slug'], array_merge(['name', 'sci', 'wgroup', 'life', 'local', 'hint', 'crops', 'sources', 'sortOrder', 'deleteStatus', 'updated_at'],
                $wMore ? ['stop', 'named'] : []));
        }
        DB::table('as_weeds')->where('source', 'file')->whereNotIn('slug', array_column($rows, 'slug') ?: [''])->update(['deleteStatus' => 0, 'updated_at' => $now]);
        $n['weeds'] = count($rows);

        // Weed control plans.
        $f = self::file('plans');
        $theirs = DB::table('as_weed_plans')->where('source', '!=', 'file')->pluck('planKey')->flip();
        // prevent and critical (2026-10-18) are written once their columns exist.
        $pMore = Schema::hasColumn('as_weed_plans', 'critical');
        $rows = [];
        foreach ($f['plans'] ?? [] as $i => $p) {
            if (isset($theirs[$p['key']])) {
                continue;
            }
            $rows[] = ['planKey' => $p['key'], 'label' => $p['label'], 'local' => $p['local'] ?? '', 'crops' => $enc($p['crops'] ?? []), 'after' => $p['after'] ?? 'after planting',
                'windows' => $enc($p['windows'] ?? []), 'ingredients' => json_encode((object) self::planIngredients($p, $f['ingredients'] ?? []), JSON_UNESCAPED_UNICODE),
                'sources' => $enc($p['sources'] ?? []), 'sortOrder' => $i, 'source' => 'file', 'deleteStatus' => 1, 'created_at' => $now, 'updated_at' => $now]
                + ($pMore ? ['prevent' => $enc($p['prevent'] ?? []), 'critical' => json_encode((object) ($p['critical'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)] : []);
        }
        foreach (array_chunk($rows, 20) as $chunk) {
            DB::table('as_weed_plans')->upsert($chunk, ['planKey'], array_merge(['label', 'local', 'crops', 'after', 'windows', 'ingredients', 'sources', 'sortOrder', 'deleteStatus', 'updated_at'],
                $pMore ? ['prevent', 'critical'] : []));
        }
        DB::table('as_weed_plans')->where('source', 'file')->whereNotIn('planKey', array_column($rows, 'planKey') ?: [''])->update(['deleteStatus' => 0, 'updated_at' => $now]);
        $n['plans'] = count($rows);

        self::forget();

        return $n;
    }

    /** Drop the cached copies, after the rows change. */
    public static function forget(): void
    {
        foreach (['problems', 'weeds', 'plans'] as $what) {
            try {
                Cache::forget(self::CACHE . $what);
            } catch (\Throwable $e) {
                // no cache store: nothing kept
            }
        }
        self::$memo = [];
    }
}
