<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * The yield parts of each variety (2026-10-19, the owner: "research one by
 * one ... each variety of crops and check the details like # of tillers,
 * spikelets, grains, grain weight range, etc to add up to the computation
 * for potential yield based on the # of stand and spacing. all of these
 * data, save this to the database to save tokens everytime this tool is
 * being used").
 *
 * For the Seed Count and Distance Calculator (App\Support\SeedSpacing): a
 * variety's own figures for the parts its yield model multiplies, so the
 * potential follows the variety and not only the crop.
 *
 *  - tiller (rice, transplanted and direct seeded): productive tillers a
 *    hill, grains (spikelets) a panicle, the filled share %, the weight of
 *    1,000 grains g (tgw), and rarely the most panicles a square meter.
 *  - ear (corn and sorghum): ears (heads) a plant, and the grain an ear g
 *    (the fresh ear for sweet corn).
 *  - plant (the rest): the harvest a plant, in the crop's own unit (g, kg
 *    or a count, as SeedSpacing::CROPS[$key]['yield']['unit']).
 *
 * Each part is a number or [low, high], and carries where it rests (by:
 * papers = the registration trials, seller = the seed company's sheet,
 * breeder = the breeder's description, study = a trial that measured it,
 * type = the typical figure of its kind, the median of the varieties of
 * that kind here). `fit` is the variety's recorded yield over the potential
 * its parts give at the crop's usual spacing, counted by the calculator's
 * own arithmetic: near 1 the parts agree with the record; multiply a
 * potential by it to land on the record. Null when no yield is on record.
 * `extra` keeps the traits that explain the parts (panicle length, height,
 * ear length and rows, kernel weight, shelling, head or bulb weight ...).
 *
 * The rules: numbers and names only (no sentence from any source is kept),
 * never a source named (no agency, institute, university or registry; a
 * private seed company that sells a variety stays only as a fact about
 * it, and is not stored here); for the calculations only, never a public
 * page. The seed files are database/variety-yield/*.json, built in the
 * research session's scratchpad (never committed), loaded into
 * as_variety_yield_parts by the migration through sync(). Read through
 * App\Support\Concerns\ShelfConnection; until the table exists the files
 * answer.
 */
final class VarietyYield
{
    use Concerns\ShelfConnection;

    public const TABLE = 'as_variety_yield_parts';

    /** The seed files, relative to database/. */
    public const FILES = ['variety-yield/rice.json', 'variety-yield/corn.json', 'variety-yield/crops.json'];

    /** The variety book (VarietyBook's app crop key) a calculator crop reads, where it is not its own key. */
    public const BOOK = ['rice_dsr' => 'rice', 'corn_white' => 'corn_glutinous', 'bushsitaw' => 'stringbean', 'banana_saba' => 'banana', 'banana_cavendish' => 'banana'];

    /** The parts each yield model takes: SeedSpacing::CROPS[$key]['yield'] keys. */
    public const PARTS = [
        'tiller' => ['tillers', 'grains', 'filled', 'tgw', 'ceiling'],
        'ear' => ['ears', 'perEar'],
        'plant' => ['perPlant', 'unit'],
    ];

    public const BASES = ['papers', 'seller', 'breeder', 'study', 'type'];

    /** A measurement switch (before and after runs): true reads nothing. */
    public static bool $off = false;

    /** Rows by calculator crop, per request. */
    private static array $rows = [];

    /** Name keys to row positions, by calculator crop, per request. */
    private static array $index = [];

    private static ?bool $ready = null;

    /* ====================================================== the reader */

    /**
     * A variety's own yield parts for a calculator crop, or null when the
     * variety is unknown (the calculator keeps the crop's own figures).
     * Names are matched the way VarietyBook::match matches them ("NSIC Rc
     * 222", "Rc222" and "Tubigan 18" are one variety). A variety the books
     * know but with no parts of its own gets its kind's typical parts
     * (basis 'type'), named as the variety.
     *
     * ['name' => 'NSIC Rc 222', 'crop' => 'rice', 'type' => 'inbred', 'model' => 'tiller',
     *  'tillers' => 14, 'grains' => [118, 132], 'filled' => 84, 'tgw' => 23.1,
     *  'basis' => 'papers', 'by' => ['tillers' => 'papers', ...], 'fit' => 0.86,
     *  'quality' => 4, 'extra' => ['panicleCm' => 24, 'heightCm' => 101, ...]]
     */
    public static function for(string $seedCropKey, ?string $varietyName): ?array
    {
        $name = trim((string) $varietyName);
        if (self::$off || $name === '' || mb_strlen($name) > 160) {
            return null;
        }
        if ($row = self::find($seedCropKey, $name)) {
            return self::shape($row);
        }
        // The books know it by another name, or it has no parts of its own yet.
        try {
            $rec = VarietyBook::match(self::bookKey($seedCropKey), $name);
        } catch (\Throwable $e) {
            report($e);
            $rec = null;
        }
        if (! $rec) {
            return null;
        }
        if ($row = self::find($seedCropKey, (string) $rec['name'])) {
            return self::shape($row);
        }
        $typical = self::typical($seedCropKey, $rec);

        return $typical ? ['name' => (string) $rec['name']] + self::shape($typical) : null;
    }

    /**
     * The varieties with parts of their own for a calculator crop, for a
     * picker: name, type, basis, fit, quality and the parts, by name.
     */
    public static function list(string $seedCropKey): array
    {
        $out = [];
        foreach (self::all()[$seedCropKey] ?? [] as $r) {
            if (str_starts_with($r['nameKey'], '*')) {
                continue;
            }
            $s = self::shape($r);
            unset($s['extra'], $s['by'], $s['crop']);
            $out[] = $s;
        }
        usort($out, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return $out;
    }

    /** The row a written name means on a calculator crop's shelf, or null. */
    private static function find(string $crop, string $name): ?array
    {
        $rows = self::all()[$crop] ?? [];
        if (! $rows) {
            return null;
        }
        if (! isset(self::$index[$crop])) {
            $ix = [];
            $exact = [];
            foreach ($rows as $i => $r) {
                if (str_starts_with($r['nameKey'], '*')) {
                    continue;
                }
                foreach (array_merge([$r['name']], $r['aliases']) as $n) {
                    $exact[self::flat((string) $n)] ??= $i;
                    // A seller variety whose loose keys meet a book variety's is found by its exact name only.
                    if (! empty($r['extra']['matchExact'])) {
                        continue;
                    }
                    foreach (VarietyBook::nameKeys((string) $n) as $k) {
                        $ix[$k] ??= $i;
                    }
                }
            }
            self::$index[$crop] = ['keys' => $ix, 'exact' => $exact];
        }
        // The name as written first ("Sugar Baby F1" is the hybrid, "Sugar Baby" the old open pollinated
        // one, though the name keys drop the F1), then the name keys VarietyBook matches by.
        if (isset(self::$index[$crop]['exact'][$f = self::flat($name)]) && $f !== '') {
            return $rows[self::$index[$crop]['exact'][$f]];
        }
        $parts = array_merge([$name], preg_split('/\s*[()\/,;]\s*|\s+or\s+|\s+aka\s+/i', $name) ?: []);
        foreach ($parts as $part) {
            foreach (VarietyBook::nameKeys((string) $part) as $k) {
                if (isset(self::$index[$crop]['keys'][$k])) {
                    return $rows[self::$index[$crop]['keys'][$k]];
                }
            }
        }

        return null;
    }

    /** A name in lowercase letters and digits only, nothing dropped ("Sugar Baby F1" -> sugarbabyf1). */
    private static function flat(string $s): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', strtr(mb_strtolower($s), ['ñ' => 'n']));
    }

    /** The typical parts of a record's kind on a calculator crop's shelf (rows keyed '*hybrid', '*inbred' ...). */
    private static function typical(string $crop, array $rec): ?array
    {
        $kind = strtolower((string) ($rec['kind'] ?? ''));
        $type = ($rec['type'] ?? '') === 'hybrid' || str_contains($kind, 'hybrid') ? 'hybrid'
            : (str_starts_with($crop, 'rice') ? 'inbred' : (str_contains($kind, 'open pollinated') ? 'open pollinated' : 'variety'));
        foreach (self::all()[$crop] ?? [] as $r) {
            if ($r['nameKey'] === '*' . $type) {
                return $r;
            }
        }

        return null;
    }

    /** VarietyBook's app crop key for a calculator crop. */
    public static function bookKey(string $seedCropKey): string
    {
        return self::BOOK[$seedCropKey] ?? $seedCropKey;
    }

    /** A row as the calculator reads it: the parts flat, then what they rest on. */
    private static function shape(array $r): array
    {
        $out = ['name' => $r['name'], 'crop' => $r['crop'], 'type' => $r['type'], 'model' => $r['model']];
        foreach ((array) $r['parts'] as $k => $v) {
            $out[$k] = $v;
        }

        return $out + [
            'basis' => $r['basis'],
            'by' => (array) $r['by'],
            'fit' => $r['fit'],
            'quality' => $r['quality'],
            'extra' => (array) $r['extra'],
        ];
    }

    /* ===================================================== the reading */

    public static function ready(): bool
    {
        if (self::$ready === null) {
            try {
                self::$ready = (bool) Cache::remember('variety-yield:' . self::conn() . ':ready', 600, fn () => self::schema()->hasTable(self::TABLE));
            } catch (\Throwable $e) {
                self::$ready = false;
            }
        }

        return self::$ready;
    }

    /** The seed files' stamp: a new file (a deploy) reads the table afresh. */
    private static function stamp(): string
    {
        $m = 0;
        foreach (self::FILES as $f) {
            $m = max($m, (int) @filemtime(database_path($f)));
        }

        return (string) $m;
    }

    /** The table's last write, kept five minutes: the data version of the cached rows. */
    private static function version(): string
    {
        return (string) Cache::remember('variety-yield:' . self::conn() . ':version', 300,
            fn () => (string) (self::db()->table(self::TABLE)->max('updated_at') ?? '0') . ':' . self::db()->table(self::TABLE)->count());
    }

    /** Every row of the seed files, decoded (the fallback and sync's input). */
    public static function fileRows(?array $files = null): array
    {
        $out = [];
        foreach ($files ?? array_map(fn ($f) => database_path($f), self::FILES) as $file) {
            $d = json_decode((string) @file_get_contents($file), true);
            $rows = is_array($d['rows'] ?? null) ? $d['rows'] : (is_array($d) && array_is_list($d) ? $d : []);
            foreach ($rows as $r) {
                if (is_array($r) && ! empty($r['crop']) && ! empty($r['nameKey'])) {
                    $out[] = $r;
                }
            }
        }

        return $out;
    }

    /**
     * Every live row by calculator crop: ONE query for the whole table, kept
     * six hours under the seed files' stamp and the table's version, and per
     * request. The files answer until the table exists.
     */
    public static function all(): array
    {
        if (self::$off) {
            return [];
        }
        $id = self::conn();
        if (isset(self::$rows[$id])) {
            return self::$rows[$id];
        }
        $group = function (array $rows): array {
            $out = [];
            foreach ($rows as $r) {
                $out[(string) $r['crop']][] = $r;
            }

            return $out;
        };
        $fromFiles = fn () => $group(array_map(fn ($r) => self::norm($r), self::fileRows()));
        try {
            $by = self::ready()
                ? Cache::remember('variety-yield:' . $id . ':all:' . self::stamp() . ':' . self::version(), 21600, fn () => $group(
                    self::db()->table(self::TABLE)->where('status', 1)->orderBy('sortOrder')->get()->map(fn ($r) => self::decode($r))->all()))
                : $fromFiles();
        } catch (\Throwable $e) {
            report($e);
            $by = $fromFiles();
        }
        self::$index = [];

        return self::$rows[$id] = $by;
    }

    /** Forget what this request read (tests, sync). */
    public static function forget(): void
    {
        self::$rows = [];
        self::$index = [];
        self::$ready = null;
    }

    /* ======================================================== the load */

    /**
     * Load the seed files into the table, in batches, by (crop, nameKey):
     * new rows go in, changed rows are replaced (a hash per row), rows gone
     * from the files are retired (status 0). Idempotent; returns counts.
     */
    public static function sync(?array $files = null, int $batch = 100): array
    {
        $rows = self::fileRows($files);
        if (! $rows || ! self::schema()->hasTable(self::TABLE)) {
            return ['rows' => 0, 'written' => 0, 'retired' => 0];
        }
        $now = now()->format('Y-m-d H:i:s');
        $db = self::db();
        $have = [];
        foreach ($db->table(self::TABLE)->get(['crop', 'nameKey', 'hash', 'status']) as $h) {
            $have[$h->crop . '|' . $h->nameKey] = (int) $h->status === 1 ? $h->hash : '';
        }
        $keys = [];
        $todo = [];
        foreach ($rows as $i => $r) {
            $rec = self::encode($r, $i);
            $k = $rec['crop'] . '|' . $rec['nameKey'];
            if (isset($keys[$k])) {
                continue;
            }
            $keys[$k] = true;
            if (($have[$k] ?? null) === $rec['hash']) {
                continue;
            }
            $todo[] = $rec + ['created_at' => $now, 'updated_at' => $now];
        }
        $cols = array_values(array_diff(array_keys(self::encode($rows[0], 0)), ['crop', 'nameKey']));
        $cols[] = 'updated_at';
        foreach (array_chunk($todo, $batch) as $chunk) {
            $db->table(self::TABLE)->upsert($chunk, ['crop', 'nameKey'], $cols);
        }
        $retired = 0;
        foreach ($db->table(self::TABLE)->where('status', 1)->get(['id', 'crop', 'nameKey']) as $r) {
            if (! isset($keys[$r->crop . '|' . $r->nameKey])) {
                $retired += $db->table(self::TABLE)->where('id', $r->id)->update(['status' => 0, 'updated_at' => $now]);
            }
        }
        self::forget();
        Cache::forget('variety-yield:' . self::conn() . ':version');
        Cache::forget('variety-yield:' . self::conn() . ':ready');

        return ['rows' => count($keys), 'written' => count($todo), 'retired' => $retired];
    }

    /** A seed row as table columns. */
    private static function encode(array $r, int $i): array
    {
        $j = fn ($v) => $v === null || $v === [] ? null : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $rec = [
            'crop' => mb_substr((string) $r['crop'], 0, 24),
            'nameKey' => mb_substr((string) $r['nameKey'], 0, 120),
            'bookCrop' => mb_substr((string) ($r['bookCrop'] ?? $r['crop']), 0, 40),
            'name' => mb_substr((string) $r['name'], 0, 160),
            'aliases' => $j(array_values(array_filter((array) ($r['aliases'] ?? []), 'is_string'))),
            'type' => mb_substr((string) ($r['type'] ?? ''), 0, 24),
            'model' => mb_substr((string) ($r['model'] ?? ''), 0, 8),
            'parts' => $j((array) ($r['parts'] ?? [])) ?? '{}',
            'by' => $j($r['by'] ?? null),
            'extra' => $j($r['extra'] ?? null),
            'fit' => isset($r['fit']) && is_numeric($r['fit']) ? round((float) $r['fit'], 3) : null,
            'basis' => mb_substr((string) ($r['basis'] ?? ''), 0, 12),
            'quality' => (int) ($r['quality'] ?? 0),
            'sortOrder' => $i,
            'status' => 1,
        ];
        $rec['hash'] = md5(json_encode($rec));

        return $rec;
    }

    /** A table row as the reader holds it. */
    private static function decode(object $r): array
    {
        $j = fn ($v) => is_string($v) ? (json_decode($v, true) ?: []) : (array) ($v ?? []);

        return self::norm([
            'crop' => (string) $r->crop, 'nameKey' => (string) $r->nameKey, 'bookCrop' => (string) $r->bookCrop, 'name' => (string) $r->name,
            'aliases' => $j($r->aliases), 'type' => (string) $r->type, 'model' => (string) $r->model, 'parts' => $j($r->parts),
            'by' => $j($r->by), 'extra' => $j($r->extra), 'fit' => $r->fit, 'basis' => (string) $r->basis, 'quality' => $r->quality,
        ]);
    }

    /** One shape, from the table or the files. */
    private static function norm(array $r): array
    {
        return [
            'crop' => (string) $r['crop'], 'nameKey' => (string) $r['nameKey'], 'bookCrop' => (string) ($r['bookCrop'] ?? $r['crop']),
            'name' => (string) $r['name'], 'aliases' => array_values(array_filter((array) ($r['aliases'] ?? []), 'is_string')),
            'type' => (string) ($r['type'] ?? ''), 'model' => (string) ($r['model'] ?? ''), 'parts' => (array) ($r['parts'] ?? []),
            'by' => (array) ($r['by'] ?? []), 'extra' => (array) ($r['extra'] ?? []),
            'fit' => isset($r['fit']) && is_numeric($r['fit']) ? (float) $r['fit'] : null,
            'basis' => (string) ($r['basis'] ?? ''), 'quality' => (int) ($r['quality'] ?? 0),
        ];
    }
}
