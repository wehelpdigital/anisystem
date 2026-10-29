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

    /** The connection the book is read through: the app's own, unless config('products.connection') names a rehearsal. */
    public static function conn(): string
    {
        $c = (string) (config('products.connection') ?: '');
        if ($c === 'products_local' && ! config('database.connections.products_local')) {
            config(['database.connections.products_local' => array_merge((array) config('database.connections.mysql'), (array) config('products.local'), ['url' => null, 'options' => [], 'strict' => true])]);
        }

        return $c !== '' ? $c : DB::getDefaultConnection();
    }

    public static function db(): \Illuminate\Database\Connection
    {
        return DB::connection(self::conn());
    }

    public static function ready(): bool
    {
        return (bool) Cache::remember('products:ready:' . self::conn(), 600, fn () => Schema::connection(self::conn())->hasTable(self::TABLE));
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
        $theirs = self::db()->table(self::TABLE)->where('source', '!=', 'file')->pluck('pkey')->flip();
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
                    'pkey' => mb_substr($p['k'], 0, 120), 'brand' => mb_substr((string) $p['b'], 0, 190), 'brandKey' => mb_substr(self::key((string) $p['b']), 0, 190), 'company' => mb_substr((string) ($p['c'] ?? ''), 0, 120),
                    'type' => mb_substr((string) ($p['t'] ?? 'other'), 0, 24), 'form' => mb_substr((string) (($p['f'] ?? '') !== '' ? $p['f'] : ($p['form'] ?? '')), 0, 16),
                    'applied' => self::applied($p), 'ai' => $enc($p['ai'] ?? []), 'nutrients' => $enc((object) ($p['n'] ?? [])), 'traits' => $enc($p['tr'] ?? []),
                    'crops' => $enc($p['crops'] ?? []), 'uses' => $enc($p['uses'] ?? []), 'info' => $enc((object) $info), 'search' => self::haystack($p),
                    'source' => 'file', 'sourceRef' => mb_substr(is_array($p['src'] ?? null) ? implode(' ', $p['src']) : (string) ($p['src'] ?? ''), 0, 255),
                    'status' => 1, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk(array_values($all), 200) as $chunk) {
            self::db()->table(self::TABLE)->upsert($chunk, ['pkey'],
                ['brand', 'brandKey', 'company', 'type', 'form', 'applied', 'ai', 'nutrients', 'traits', 'crops', 'uses', 'info', 'search', 'sourceRef', 'status', 'updated_at']);
        }
        // The other names a file row carries (an old brand, a local word) become aliases, so find() knows them.
        $aka = [];
        foreach (self::FILES as $file) {
            foreach (self::file($file) as $p) {
                if (! empty($p['aka']) && isset($all[$p['k'] ?? ''])) {
                    $aka[$p['k']] = (array) $p['aka'];
                }
            }
        }
        if ($aka && self::aliasesReady()) {
            foreach (array_chunk(array_keys($aka), 300) as $keys) {
                foreach (self::db()->table(self::TABLE)->whereIn('pkey', $keys)->pluck('id', 'pkey') as $pk => $id) {
                    self::alias((int) $id, $aka[$pk], 'file');
                }
            }
        }
        $keep = array_keys($all);
        self::db()->table(self::TABLE)->where('source', 'file')->where('status', 1)->orderBy('id')->select(['id', 'pkey'])
            ->chunkById(1000, function ($rs) use ($keep, $now) {
                $gone = $rs->filter(fn ($r) => ! in_array($r->pkey, $keep, true))->pluck('id')->all();
                if ($gone) {
                    self::db()->table(self::TABLE)->whereIn('id', $gone)->update(['status' => 0, 'updated_at' => $now]);
                }
            });
        self::forget();

        return $n;
    }

    public static function forget(): void
    {
        Cache::forget('products:ready:' . self::conn());
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
            return self::db()->table(self::TABLE)->whereIn('id', $ids)->get()->mapWithKeys(fn ($r) => [(int) $r->id => self::fromRow($r)])->all();
        }

        return [];
    }

    public static function byKey(string $key): ?array
    {
        if (self::ready()) {
            $r = self::db()->table(self::TABLE)->where('pkey', $key)->where('status', 1)->first();

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

    /* ================================================================ learning */

    public const ALIASES = 'as_product_aliases';

    /** A name squeezed for matching: letters and digits only ("S&P Topdex 50 EC" = "sandptopdex50ec"). */
    public static function key(string $s): string
    {
        $s = mb_strtolower($s);
        $s = str_replace('&', 'and', $s);

        return (string) preg_replace('/[^a-z0-9]+/', '', $s);
    }

    private static function aliasesReady(): bool
    {
        return (bool) Cache::remember('products:aliases:' . self::conn(), 600, fn () => Schema::connection(self::conn())->hasTable(self::ALIASES));
    }

    /**
     * The book's product a name means, or null: the brand as written (letters
     * and digits compared, any spacing or case), then a learned alias. Rows
     * from the files win over rows Anee added, and live rows over retired ones.
     * The row comes back as fromRow() shapes it (id, k, b, c, t, f, ai, n, tr,
     * crops, uses, source, and the info fields).
     */
    public static function find(string $name): ?array
    {
        $k = self::key($name);
        if (strlen($k) < 2 || ! self::ready()) {
            return null;
        }
        try {
            // The book's own rows first, and the plain material before a brand of the same name.
            $r = self::db()->table(self::TABLE)->where('brandKey', mb_substr($k, 0, 190))->where('status', 1)
                ->orderByRaw("source = 'file' desc")->orderByRaw("company = '' desc")->orderBy('id')->first();
            if ($r) {
                return self::fromRow($r);
            }
            if (self::aliasesReady()) {
                $pid = self::db()->table(self::ALIASES)->where('aliasKey', $k)->orderBy('id')->value('productId');
                if ($pid) {
                    $r = self::db()->table(self::TABLE)->where('id', $pid)->where('status', 1)->first();

                    return $r ? self::fromRow($r) : null;
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    /**
     * Keep a product Anee found online (or any module researched), so the
     * next farmer who names it finds it in the book and nothing is looked up
     * again. Returns the product's id, or null when the row says too little
     * to keep (no brand, low confidence, or neither ingredients nor an
     * analysis) or the book is not there yet.
     *
     * A product the book already holds from its files (the FPA lists, the
     * companies' labels) is never changed: the name it was asked by becomes
     * an alias of that row instead. A product Anee added before is filled in
     * (missing facts, more mixing notes, the higher confidence), never emptied.
     *
     * $row: brand (required), company, type (a TYPES key), formulation (EC,
     * WP...), form (granular|soluble|liquid, for fertilizers), ai [{name,
     * amount, unit}], nutrients {N: %, P2O5: %...}, crops [crop keys], rates
     * [{crop, text, amount: [lo, hi], unit, per, phi}], mixing [plain lines],
     * flags [the rules' flags, for an ingredient the book does not know],
     * aliases [other names it is called by], confidence high|medium|low,
     * sources [{title, url}] (kept for audit, never shown), ownerId (who paid).
     * $via: the module that learned it (compat, npk, protocol, builder...).
     */
    public static function learn(array $row, string $via): ?int
    {
        $brand = trim((string) preg_replace('/\s+/', ' ', (string) ($row['brand'] ?? '')));
        $conf = in_array($row['confidence'] ?? '', ['high', 'medium', 'low'], true) ? $row['confidence'] : 'low';
        $ai = [];
        foreach (array_slice((array) ($row['ai'] ?? []), 0, 8) as $a) {
            $n = trim((string) ($a['name'] ?? $a['n'] ?? ''));
            if ($n === '') {
                continue;
            }
            $ing = CompatBook::ingredient($n);
            $amt = $a['amount'] ?? $a['a'] ?? null;
            $ai[] = array_filter(['n' => $ing['name'] ?? mb_substr($n, 0, 80), 'raw' => $ing ? null : mb_substr($n, 0, 80),
                'a' => is_numeric($amt) && (float) $amt > 0 ? (float) $amt : null, 'u' => in_array($a['unit'] ?? $a['u'] ?? '', ['g/L', 'g/kg', '%', 'mL/L', 'CFU/g', 'CFU/mL'], true) ? ($a['unit'] ?? $a['u']) : null],
                fn ($x) => $x !== null);
        }
        $nut = [];
        foreach ((array) ($row['nutrients'] ?? []) as $el => $v) {
            if (in_array($el, ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'SiO2', 'Cl'], true) && is_numeric($v) && (float) $v > 0 && (float) $v <= 100) {
                $nut[$el] = round((float) $v, 4);
            }
        }
        if (mb_strlen($brand) < 2 || $conf === 'low' || (! $ai && ! $nut) || ! self::ready()) {
            return null;
        }
        $type = array_key_exists($row['type'] ?? '', self::TYPES) ? $row['type'] : ($nut ? 'foliar' : 'other');
        $form = strtoupper((string) preg_replace('/[^A-Za-z]/', '', (string) ($row['formulation'] ?? '')));
        $form = isset(CompatBook::FORM_GROUP[$form]) ? $form : '';
        $fform = in_array($row['form'] ?? '', ['granular', 'soluble', 'liquid'], true) ? $row['form'] : '';
        $aliases = array_values(array_unique(array_filter(array_map(fn ($x) => trim((string) $x), (array) ($row['aliases'] ?? [])), fn ($x) => mb_strlen($x) >= 2)));
        $mix = array_values(array_filter(array_map(fn ($x) => mb_substr(trim((string) preg_replace(['/\s+[-–—]\s+/u', '/[–—]/u'], [', ', ' to '], (string) $x)), 0, 240), array_slice((array) ($row['mixing'] ?? []), 0, 8))));
        $uses = [];
        foreach (array_slice((array) ($row['rates'] ?? []), 0, 20) as $r) {
            $crop = FieldCatalogue::cropKey((string) ($r['crop'] ?? '')) ?: mb_substr((string) ($r['crop'] ?? ''), 0, 40);
            $amt = (array) ($r['amount'] ?? []);
            $parsed = $amt && in_array($r['unit'] ?? '', ['mL', 'L', 'g', 'kg', 'tbsp', 'tsp'], true) && in_array($r['per'] ?? '', ['16L', '20L', '200L', '1L', 'ha', 'plant'], true)
                ? [(float) $amt[0], (float) ($amt[1] ?? $amt[0]), $r['unit'], $r['per']] : null;
            $uses[] = [$crop, '', mb_substr((string) ($r['text'] ?? ''), 0, 200), $parsed, mb_substr((string) ($r['phi'] ?? ''), 0, 80)];
        }
        $crops = array_values(array_unique(array_filter(array_merge((array) ($row['crops'] ?? []), array_column($uses, 0)), fn ($c) => is_string($c) && isset(FieldCatalogue::crops()[$c]))));
        $src = array_values(array_filter(array_map(fn ($s) => is_array($s) ? trim(($s['title'] ?? '') . ' ' . ($s['url'] ?? '')) : (string) $s, (array) ($row['sources'] ?? []))));
        $now = now();
        $db = self::db();

        $existing = self::find($brand);
        if (! $existing) {
            foreach ($aliases as $al) {
                if ($existing = self::find($al)) {
                    break;
                }
            }
        }
        if ($existing && ($existing['source'] ?? 'file') !== 'anee') {
            // A product of the files: never changed. The names it was asked by point at it.
            self::alias((int) $existing['id'], array_merge([$brand], $aliases), $via);

            return (int) $existing['id'];
        }
        $p = ['k' => '', 'b' => $brand, 'c' => (string) ($row['company'] ?? ''), 't' => $type, 'f' => $form, 'form' => $fform, 'ai' => $ai, 'n' => $nut, 'aka' => $aliases];
        $traits = $nut ? FertTraits::infer($brand, $nut, $fform) : [];
        $enc = fn ($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($existing) {
            // Anee's own row from before: filled in, never emptied.
            $r = $db->table(self::TABLE)->where('id', $existing['id'])->first();
            $info = json_decode((string) $r->info, true) ?: [];
            $rank = ['low' => 0, 'medium' => 1, 'high' => 2];
            $info['mix'] = array_values(array_unique(array_merge((array) ($info['mix'] ?? []), $mix)));
            $info['aka'] = array_values(array_unique(array_merge((array) ($info['aka'] ?? []), $aliases)));
            $info['via'] = array_values(array_unique(array_merge((array) ($info['via'] ?? []), [$via])));
            $info['aiFlags'] = array_values(array_unique(array_merge((array) ($info['aiFlags'] ?? []), (array) ($row['flags'] ?? []))));
            $info['confidence'] = ($rank[$conf] ?? 0) > ($rank[$info['confidence'] ?? 'low'] ?? 0) ? $conf : ($info['confidence'] ?? $conf);
            $info['learnedAt'] = $now->toDateString();
            $oldAi = json_decode((string) $r->ai, true) ?: [];
            $oldN = json_decode((string) $r->nutrients, true) ?: [];
            $oldUses = json_decode((string) $r->uses, true) ?: [];
            $have = array_column($oldUses, 0);
            foreach ($uses as $u) {
                if (! in_array($u[0], $have, true)) {
                    $oldUses[] = $u;
                }
            }
            $newAi = $oldAi ?: $ai;
            $newN = $oldN ?: $nut;
            $db->table(self::TABLE)->where('id', $r->id)->update([
                'company' => $r->company !== '' ? $r->company : mb_substr($p['c'], 0, 120), 'form' => $r->form !== '' ? $r->form : ($form ?: $fform),
                'ai' => $enc($newAi), 'nutrients' => $enc((object) $newN), 'traits' => $enc($newN ? FertTraits::infer($r->brand, $newN, $fform) : []),
                'crops' => $enc(array_values(array_unique(array_merge(json_decode((string) $r->crops, true) ?: [], $crops)))), 'uses' => $enc(array_slice($oldUses, 0, 30)),
                'info' => $enc((object) $info), 'search' => self::haystack(['b' => $r->brand, 'c' => $r->company ?: $p['c'], 't' => $r->type, 'ai' => $newAi, 'n' => $newN, 'aka' => $info['aka']]),
                'sourceRef' => mb_substr(trim($r->sourceRef . ' ' . implode(' ', $src)), 0, 255), 'updated_at' => $now,
            ]);
            self::alias((int) $r->id, $aliases, $via);

            return (int) $r->id;
        }
        $info = ['mix' => $mix, 'aka' => $aliases, 'via' => [$via], 'aiFlags' => array_values(array_filter((array) ($row['flags'] ?? []), 'is_string')),
            'confidence' => $conf, 'learnedAt' => $now->toDateString(), 'sources' => array_slice($src, 0, 12)];
        $pkey = 'anee-' . substr(\Illuminate\Support\Str::slug($brand), 0, 80) . '-' . substr(md5(self::key($brand) . '|' . self::key((string) ($row['company'] ?? ''))), 0, 6);
        try {
            $id = (int) $db->table(self::TABLE)->insertGetId([
                'pkey' => $pkey, 'brand' => mb_substr($brand, 0, 190), 'brandKey' => mb_substr(self::key($brand), 0, 190), 'company' => mb_substr($p['c'], 0, 120), 'type' => $type, 'form' => $form ?: $fform,
                'applied' => self::applied($p), 'ai' => $enc($ai), 'nutrients' => $enc((object) $nut), 'traits' => $enc($traits), 'crops' => $enc($crops), 'uses' => $enc($uses),
                'info' => $enc((object) $info), 'search' => self::haystack($p), 'source' => 'anee', 'sourceRef' => mb_substr(implode(' ', $src), 0, 255),
                'ownerId' => isset($row['ownerId']) ? (int) $row['ownerId'] : null, 'status' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // The same key from a run a moment ago: that row is the answer.
            $id = (int) $db->table(self::TABLE)->where('pkey', $pkey)->value('id');
        }
        self::alias($id, $aliases, $via);
        Cache::forget('products:counts:' . self::conn() . ':' . self::version());

        return $id ?: null;
    }

    /** Point other names at a product (kept apart from the rows, so a sync never loses them). */
    public static function alias(int $productId, array $names, string $via = ''): void
    {
        if ($productId <= 0 || ! self::aliasesReady()) {
            return;
        }
        $rows = [];
        foreach (array_unique($names) as $n) {
            $n = trim((string) $n);
            $k = self::key($n);
            if (strlen($k) >= 2) {
                $rows[$k] = ['productId' => $productId, 'alias' => mb_substr($n, 0, 190), 'aliasKey' => mb_substr($k, 0, 190), 'via' => mb_substr($via, 0, 24), 'created_at' => now(), 'updated_at' => now()];
            }
        }
        if ($rows) {
            try {
                self::db()->table(self::ALIASES)->upsert(array_values($rows), ['productId', 'aliasKey'], ['alias', 'updated_at']);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Whether a product belongs in a spray (a tank mix) or in a granular pass
     * (spread or broadcast together). Sprays: every product that goes on in
     * water (liquids, WP, WG, SC, EC, foliar and soluble fertilizers, spray
     * helpers), and a plain fertilizer that is also sprayed (urea). Granulars:
     * granular fertilizers, soil conditioners (lime, dolomite, gypsum) and
     * pesticide granules, and a soluble powder also put on the soil (borax,
     * zinc sulfate). A liquid is never a granule; a drip only acid is no spray.
     * A bait or a seed treatment is neither: it goes on alone.
     */
    public static function fits(array $p, string $mode): bool
    {
        $applied = $p['applied'] ?? self::applied($p);
        if (in_array($applied, ['bait', 'seed'], true)) {
            return false;
        }
        $use = (array) ($p['use'] ?? []);
        if ($mode === 'granular') {
            // A plain material says how it is used; the form column holds "liquid" for a row with no formulation code.
            $form = strtolower((string) (($p['form'] ?? '') ?: ($p['f'] ?? '')));
            if ($form === 'liquid' || in_array(CompatBook::FORM_GROUP[strtoupper((string) ($p['f'] ?? ''))] ?? '', ['ec', 'flow', 'sl'], true)) {
                return false;
            }
            if ($use) {
                return in_array('soil', $use, true);
            }

            // A soluble powder or crystal (zinc sulfate, borax) also goes on the soil.
            return $applied === 'soil' || in_array($p['t'] ?? '', ['granular', 'amendment', 'soluble'], true);
        }
        if ($use) {
            return in_array('foliar', $use, true);
        }

        return $applied === 'spray';
    }

    /**
     * Products whose brand, company or ingredients hold every word asked,
     * the brand's own matches first, and products a learned alias points at.
     * $shelf narrows to sprays, fertilizers or helpers; $crop puts products
     * labelled for it first; $mode (spray or granular) puts the products
     * that fit it first.
     */

    public static function search(string $q, ?string $shelf = null, ?string $crop = null, int $limit = 30, ?string $mode = null): array
    {
        $words = array_values(array_filter(explode(' ', self::norm($q)), fn ($w) => mb_strlen($w) >= 1));
        if (! $words || ! self::ready()) {
            return [];
        }
        $qb = self::db()->table(self::TABLE)->where('status', 1);
        $byAlias = [];
        if (self::aliasesReady() && strlen(self::key($q)) >= 3) {
            $byAlias = self::db()->table(self::ALIASES)->where('aliasKey', 'like', self::key($q) . '%')->limit(20)->pluck('productId')->all();
        }
        $qb->where(function ($w) use ($words, $byAlias) {
            $w->where(function ($x) use ($words) {
                foreach (array_slice($words, 0, 6) as $word) {
                    $x->where('search', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $word) . '%');
                }
            });
            if ($byAlias) {
                $w->orWhereIn('id', $byAlias);
            }
        });
        if ($shelf && isset(self::SHELVES[$shelf])) {
            $qb->whereIn('type', self::SHELVES[$shelf]);
        }
        $first = $words[0];
        $rows = $qb->orderByRaw('case when lower(brand) like ? then 0 when lower(brand) like ? then 1 else 2 end', [$first . '%', '% ' . $first . '%'])
            ->orderByRaw("case when source = 'file' and company = '' then 0 else 1 end")->orderBy('brand')->limit(200)->get();
        $list = $rows->map(fn ($r) => self::fromRow($r))->all();
        // The brand that starts with the word first, a plain material before a branded one, then a product labelled for the crop.
        $phrase = self::norm($q);
        $rank = function (array $p) use ($first, $crop, $phrase, $mode): array {
            $b = self::norm($p['b']);
            $starts = str_starts_with($b, $phrase) ? 0 : (str_contains($b, $phrase) ? 1 : (str_starts_with($b, $first) ? 2 : (str_contains($b, ' ' . $first) ? 3 : 4)));

            return [$mode && ! self::fits($p, $mode) ? 1 : 0, $starts, ($p['source'] ?? '') === 'file' && ($p['c'] ?? '') === '' ? 0 : 1, $crop && in_array($crop, (array) $p['crops'], true) ? 0 : 1, $b];
        };
        usort($list, fn ($a, $b) => $rank($a) <=> $rank($b));

        return array_slice($list, 0, $limit);
    }

    /**
     * A shelf looked along with nothing typed (the Coverage Planner's picker, 2026-10-30): the products of these types,
     * the plain materials first ("Urea", "Muriate of potash"), then those labelled for the crop, by name.
     */
    public static function browse(array $types, ?string $crop = null, int $limit = 60): array
    {
        if (! $types || ! self::ready()) {
            return [];
        }
        try {
            $qb = self::db()->table(self::TABLE)->where('status', 1)->whereIn('type', $types)->orderByRaw("case when company = '' then 0 else 1 end");
            if ($crop) {
                $qb->orderByRaw('case when crops like ? then 0 else 1 end', ['%"' . str_replace(['%', '_', '"'], '', $crop) . '"%']);
            }

            return $qb->orderBy('brand')->limit($limit)->get()->map(fn ($r) => self::fromRow($r))->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
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
            'fit' => ['spray' => self::fits($p, 'spray'), 'granular' => self::fits($p, 'granular')],
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

        return Cache::remember('products:counts:' . self::conn() . ':' . self::version(), 3600, fn () => self::db()->table(self::TABLE)->where('status', 1)
            ->selectRaw('type, count(*) as n')->groupBy('type')->pluck('n', 'type')->map(fn ($n) => (int) $n)->all());
    }
}
