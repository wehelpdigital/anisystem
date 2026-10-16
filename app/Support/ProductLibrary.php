<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The product book (2026-10-18, the owner: "create a huge db of the products
 * so that we can have a deep knowledge as well as the active ingredients").
 *
 * Every product sold in the Philippines that a farmer sprays or spreads, one
 * row each in as_products: the pesticides registered with the Fertilizer and
 * Pesticide Authority (insecticides, fungicides, herbicides, molluscicides,
 * nematicides, growth regulators, adjuvants), the registered fertilizers
 * (granular, soluble, foliar, amendments, biostimulants, biologicals), and the
 * plain materials (urea, ammosul, potash, lime, borax, potassium silicate and
 * the rest) with their chemistry. Each carries its active ingredients with
 * their strength, its formulation, its nutrients, the crops on its label with
 * the label rate and pre harvest interval, and what its label says about
 * mixing. The Compatibility Checker reads it (App\Support\CompatCheck); the
 * other field helpers and NPK Plus can read it the same way.
 *
 * The rows come from database/products/*.json (built from the FPA lists and
 * the companies' own pages; the build scripts and their audit files stay in
 * the build session's scratchpad), shipped by a migration that calls sync().
 * A product Anee looks up online for a farmer is added as source 'anee' and
 * is never touched by a sync. Until the table exists the files answer.
 *
 * Where a product came from (`sourceRef`) is kept for audit and never shown
 * to farmers (the owner's rule). The FPA registration number is public
 * registration data and may show on a product's card.
 */
final class ProductLibrary
{
    public const TABLE = 'as_products';

    /** The book's files, relative to database/products. */
    public const FILES = ['pesticides', 'fertilizers', 'materials', 'companies'];

    /** How each type reads on a card. */
    public const TYPES = [
        'insecticide' => 'Insecticide', 'fungicide' => 'Fungicide', 'herbicide' => 'Herbicide', 'molluscicide' => 'Molluscicide', 'nematicide' => 'Nematicide',
        'acaricide' => 'Miticide', 'rodenticide' => 'Rodenticide', 'pgr' => 'Growth regulator', 'adjuvant' => 'Spray helper', 'foliar' => 'Foliar fertilizer',
        'soluble' => 'Soluble fertilizer', 'granular' => 'Granular fertilizer', 'amendment' => 'Soil conditioner', 'biostimulant' => 'Biostimulant',
        'biological' => 'Biological', 'chemical' => 'Plain chemical', 'other' => 'Other',
    ];

    /** The shelves the search can be narrowed to. */
    public const SHELVES = [
        'sprays' => ['insecticide', 'fungicide', 'herbicide', 'molluscicide', 'nematicide', 'acaricide', 'pgr'],
        'fertilizers' => ['granular', 'soluble', 'foliar', 'amendment', 'biostimulant', 'biological'],
        'helpers' => ['adjuvant', 'chemical'],
    ];

    public static function ready(): bool
    {
        return (bool) Cache::remember('products:ready', 600, fn () => Schema::hasTable(self::TABLE));
    }

    /** One file's rows (the fallback and the sync's input). */
    public static function file(string $name): array
    {
        $path = database_path('products/' . $name . '.json');
        if (! is_file($path)) {
            return [];
        }
        $d = json_decode((string) file_get_contents($path), true);

        return is_array($d) ? (array) ($d['products'] ?? []) : [];
    }

    /** How a product goes on: sprayed, spread on the soil, set out as a bait, or put on the seed. */
    public static function applied(array $p): string
    {
        $t = $p['t'] ?? '';
        $f = strtoupper((string) ($p['f'] ?? ''));
        $group = CompatBook::FORM_GROUP[$f] ?? null;
        if ($t === 'rodenticide' || in_array($group, ['bait'], true)) {
            return 'bait';
        }
        if (in_array($group, ['seed'], true)) {
            return 'seed';
        }
        if (in_array($group, ['gran', 'dust'], true)) {
            return 'soil';
        }
        if (in_array($t, ['granular', 'amendment'], true) || ($p['form'] ?? '') === 'granular') {
            return 'soil';
        }

        return 'spray';
    }

    /** The words a search looks through: the brand, the company, every ingredient, the type, the grade. */
    public static function haystack(array $p): string
    {
        $bits = [$p['b'] ?? '', $p['c'] ?? '', self::TYPES[$p['t'] ?? ''] ?? '', implode(' ', (array) ($p['aka'] ?? []))];
        foreach ((array) ($p['ai'] ?? []) as $a) {
            $bits[] = $a['n'] ?? '';
            $bits[] = $a['raw'] ?? '';
        }
        foreach ((array) ($p['n'] ?? []) as $el => $v) {
            $bits[] = $el;
        }

        return mb_substr(self::norm(implode(' ', $bits)), 0, 1000);
    }

    public static function norm(string $s): string
    {
        $s = mb_strtolower($s);
        $s = str_replace(['-', '_', '/', ',', '.', '(', ')', '+'], ' ', $s);

        return trim((string) preg_replace('/\s+/', ' ', $s));
    }

    /**
     * Write the files' rows into the table: new and changed rows, and file
     * rows no longer in the files retired. Never touches a row Anee added.
     * Returns how many rows each file gave.
     */
    public static function sync(): array
    {
        $now = now();
        $enc = fn ($v) => $v === null ? null : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $theirs = DB::table(self::TABLE)->where('source', '!=', 'file')->pluck('pkey')->flip();
        $all = [];
        $n = [];
        foreach (self::FILES as $file) {
            $rows = self::file($file);
            $n[$file] = count($rows);
            foreach ($rows as $p) {
                if (empty($p['k']) || isset($theirs[$p['k']]) || isset($all[$p['k']])) {
                    continue;
                }
                $info = array_diff_key($p, array_flip(['k', 'b', 'c', 't', 'f', 'form', 'ai', 'n', 'tr', 'crops', 'uses', 'src']));
                $all[$p['k']] = [
                    'pkey' => mb_substr($p['k'], 0, 120), 'brand' => mb_substr((string) $p['b'], 0, 190), 'company' => mb_substr((string) ($p['c'] ?? ''), 0, 120),
                    'type' => mb_substr((string) ($p['t'] ?? 'other'), 0, 24), 'form' => mb_substr((string) (($p['f'] ?? '') !== '' ? $p['f'] : ($p['form'] ?? '')), 0, 16),
                    'applied' => self::applied($p), 'ai' => $enc($p['ai'] ?? []), 'nutrients' => $enc((object) ($p['n'] ?? [])), 'traits' => $enc($p['tr'] ?? []),
                    'crops' => $enc($p['crops'] ?? []), 'uses' => $enc($p['uses'] ?? []), 'info' => $enc((object) $info), 'search' => self::haystack($p),
                    'source' => 'file', 'sourceRef' => mb_substr(is_array($p['src'] ?? null) ? implode(' ', $p['src']) : (string) ($p['src'] ?? ''), 0, 255),
                    'status' => 1, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk(array_values($all), 200) as $chunk) {
            DB::table(self::TABLE)->upsert($chunk, ['pkey'],
                ['brand', 'company', 'type', 'form', 'applied', 'ai', 'nutrients', 'traits', 'crops', 'uses', 'info', 'search', 'sourceRef', 'status', 'updated_at']);
        }
        $keep = array_keys($all);
        DB::table(self::TABLE)->where('source', 'file')->where('status', 1)->orderBy('id')->select(['id', 'pkey'])
            ->chunkById(1000, function ($rs) use ($keep, $now) {
                $gone = $rs->filter(fn ($r) => ! in_array($r->pkey, $keep, true))->pluck('id')->all();
                if ($gone) {
                    DB::table(self::TABLE)->whereIn('id', $gone)->update(['status' => 0, 'updated_at' => $now]);
                }
            });
        self::forget();

        return $n;
    }

    public static function forget(): void
    {
        Cache::forget('products:ready');
        Cache::forget('products:index:' . self::version());
    }

    /** A fingerprint of the files, for caches. */
    public static function version(): string
    {
        static $v = null;
        if ($v === null) {
            $h = '';
            foreach (self::FILES as $f) {
                $p = database_path('products/' . $f . '.json');
                $h .= is_file($p) ? filesize($p) . ':' . filemtime($p) . ';' : '';
            }
            $v = substr(md5($h), 0, 10);
        }

        return $v;
    }

    /** A table row as the product array the checker reads. */
    public static function fromRow(object $r): array
    {
        $info = json_decode((string) $r->info, true) ?: [];

        return ['id' => (int) $r->id, 'k' => $r->pkey, 'b' => $r->brand, 'c' => $r->company, 't' => $r->type, 'f' => $r->form, 'applied' => $r->applied,
            'ai' => json_decode((string) $r->ai, true) ?: [], 'n' => json_decode((string) $r->nutrients, true) ?: [], 'tr' => json_decode((string) $r->traits, true) ?: [],
            'crops' => json_decode((string) $r->crops, true) ?: [], 'uses' => json_decode((string) $r->uses, true) ?: [], 'source' => $r->source] + $info;
    }

    /** Products by id (the table) or key (the files, when there is no table). */
    public static function many(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (! $ids) {
            return [];
        }
        if (self::ready()) {
            return DB::table(self::TABLE)->whereIn('id', $ids)->get()->mapWithKeys(fn ($r) => [(int) $r->id => self::fromRow($r)])->all();
        }

        return [];
    }

    public static function byKey(string $key): ?array
    {
        if (self::ready()) {
            $r = DB::table(self::TABLE)->where('pkey', $key)->where('status', 1)->first();

            return $r ? self::fromRow($r) : null;
        }
        foreach (self::FILES as $f) {
            foreach (self::file($f) as $p) {
                if (($p['k'] ?? '') === $key) {
                    return $p + ['applied' => self::applied($p)];
                }
            }
        }

        return null;
    }

    /**
     * Products whose brand, company or ingredients hold every word asked,
     * the brand's own matches first. $shelf narrows to sprays, fertilizers
     * or helpers; $crop puts products labelled for it first.
     */
    public static function search(string $q, ?string $shelf = null, ?string $crop = null, int $limit = 30): array
    {
        $words = array_values(array_filter(explode(' ', self::norm($q)), fn ($w) => mb_strlen($w) >= 1));
        if (! $words || ! self::ready()) {
            return [];
        }
        $qb = DB::table(self::TABLE)->where('status', 1);
        foreach (array_slice($words, 0, 6) as $w) {
            $qb->where('search', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $w) . '%');
        }
        if ($shelf && isset(self::SHELVES[$shelf])) {
            $qb->whereIn('type', self::SHELVES[$shelf]);
        }
        $first = $words[0];
        $rows = $qb->orderByRaw('case when lower(brand) like ? then 0 when lower(brand) like ? then 1 else 2 end', [$first . '%', '% ' . $first . '%'])
            ->orderByRaw("case when source = 'file' and company = '' then 0 else 1 end")->orderBy('brand')->limit(200)->get();
        $list = $rows->map(fn ($r) => self::fromRow($r))->all();
        if ($crop) {
            usort($list, fn ($a, $b) => (int) in_array($crop, $b['crops'], true) <=> (int) in_array($crop, $a['crops'], true));
        }

        return array_slice($list, 0, $limit);
    }

    /** One product shaped for a search row or a card: what it is, in a line or two, and the rate its label gives for the crop. */
    public static function card(array $p, ?string $crop = null): array
    {
        $ai = array_map(fn ($a) => trim(($a['n'] ?? '') . (isset($a['a']) ? ' ' . self::num((float) $a['a']) . ' ' . ($a['u'] ?? '') : '')), (array) ($p['ai'] ?? []));
        $use = null;
        if ($crop) {
            foreach ((array) ($p['uses'] ?? []) as $u) {
                if (($u[0] ?? '') === $crop) {
                    $use = $u;
                    break;
                }
            }
        }
        $grade = '';
        $nut = (array) ($p['n'] ?? []);
        if ($nut && (isset($nut['N']) || isset($nut['P2O5']) || isset($nut['K2O']))) {
            $grade = self::num((float) ($nut['N'] ?? 0)) . '-' . self::num((float) ($nut['P2O5'] ?? 0)) . '-' . self::num((float) ($nut['K2O'] ?? 0));
        }

        return [
            'id' => $p['id'] ?? null, 'k' => $p['k'], 'b' => $p['b'], 'c' => $p['c'] ?? '', 't' => $p['t'], 'type' => self::TYPES[$p['t']] ?? 'Product',
            'f' => $p['f'] ?? '', 'form' => $p['form'] ?? '', 'applied' => $p['applied'] ?? self::applied($p), 'ai' => $ai, 'grade' => $grade,
            'nut' => $nut ? array_map(fn ($v) => round((float) $v, 3), $nut) : null, 'onCrop' => $crop ? in_array($crop, (array) ($p['crops'] ?? []), true) : null,
            'rate' => $use ? ['text' => $use[2] ?? '', 'parsed' => $use[3] ?? null, 'phi' => $use[4] ?? '', 'for' => $use[1] ?? ''] : null,
            'anee' => ($p['source'] ?? 'file') === 'anee',
        ];
    }

    public static function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.');
    }

    /** How many rows of each type the book holds (the page's lead line, the report). */
    public static function counts(): array
    {
        if (! self::ready()) {
            return [];
        }

        return Cache::remember('products:counts:' . self::version(), 3600, fn () => DB::table(self::TABLE)->where('status', 1)
            ->selectRaw('type, count(*) as n')->groupBy('type')->pluck('n', 'type')->map(fn ($n) => (int) $n)->all());
    }
}
