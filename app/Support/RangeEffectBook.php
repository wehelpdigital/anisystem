<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Range Effect calendar's book (2026-10-29): how long an application keeps working, and how much each nutrient
 * and each threat matters at each growth stage.
 *
 * Four kinds of rows in `as_effect_book` (kind, ekey, data JSON, source file|anee, conf):
 *   ingredient  one active ingredient (the Compatibility Checker's dictionary names): targets, how it moves, onset,
 *               days of protection [lo, typ, hi], fade, rainfast hours, a farmer's note
 *   nutrient    one element (N P K Ca Mg S Zn B Fe Mn Cu Mo Si) by form (basal topdress foliar drench slow organic
 *               bio) and water (flooded upland): onset, peak, days [lo, typ, hi], fade
 *   source      the N sources' factors against urea (ammonium sulfate, nitrates, controlled release...)
 *   crit        one stage table (rice, rice_dsr_wet, corn_yellow ... and the patterns leafy, fruiting ...): for each
 *               stage label, each nutrient's and each threat's level 0 to 3 with a short reason
 *   late        per stage table, why a nutrient applied late is wasted
 *
 * The files in database/range-effect are the source (sync() writes them in; a data change is an edit of the JSON
 * and a migration that calls sync()). What Anee looks up for an ingredient the files lack is kept as a row of its
 * own (source 'anee') and read first after the files, so it is asked once. Read from the table when it is there,
 * from the files when it is not (a deploy whose migration has not run yet).
 */
class RangeEffectBook
{
    public const TABLE = 'as_effect_book';

    public const DIR = 'range-effect';

    private static ?array $rows = null;

    private static ?array $byNorm = null;

    /** True when the table is there. */
    public static function ready(): bool
    {
        static $ok = null;
        if ($ok === null) {
            try {
                $ok = Schema::hasTable(self::TABLE);
            } catch (\Throwable $e) {
                $ok = false;
            }
        }

        return $ok;
    }

    /** Every row, kind => key => data (memoised per request). */
    private static function all(): array
    {
        if (self::$rows !== null) {
            return self::$rows;
        }
        $out = [];
        if (self::ready()) {
            try {
                foreach (DB::table(self::TABLE)->where('deleteStatus', 1)->orderByRaw("source = 'file' desc")->get(['kind', 'ekey', 'data', 'source', 'conf']) as $r) {
                    $d = json_decode((string) $r->data, true);
                    if (! is_array($d) || isset($out[$r->kind][$r->ekey])) {
                        continue;
                    }
                    $out[$r->kind][$r->ekey] = $d + ['_src' => $r->source, '_conf' => $r->conf];
                }
            } catch (\Throwable $e) {
                report($e);
                $out = [];
            }
        }
        if (! $out) {
            $out = self::fromFiles();
        }

        return self::$rows = $out;
    }

    public static function forget(): void
    {
        self::$rows = null;
        self::$byNorm = null;
    }

    /** The files, shaped as the table's rows. */
    public static function fromFiles(): array
    {
        $read = fn (string $f) => json_decode((string) @file_get_contents(database_path(self::DIR . '/' . $f . '.json')), true) ?: [];
        $ing = $read('ingredients');
        $nut = $read('nutrients');
        $crit = $read('criticality');
        $out = ['ingredient' => [], 'nutrient' => [], 'source' => [], 'crit' => [], 'late' => []];
        foreach ((array) ($ing['ingredients'] ?? []) as $k => $v) {
            if (is_array($v)) {
                $out['ingredient'][$k] = $v + ['_src' => 'file', '_conf' => $v['conf'] ?? null];
            }
        }
        foreach ((array) ($nut['nutrients'] ?? []) as $k => $v) {
            if (is_array($v)) {
                $out['nutrient'][$k] = $v + ['_src' => 'file', '_conf' => null];
            }
        }
        if (! empty($nut['sources'])) {
            $out['source']['n'] = (array) $nut['sources'] + ['_src' => 'file', '_conf' => null];
        }
        foreach ((array) ($crit['crops'] ?? []) as $k => $v) {
            if (is_array($v)) {
                $out['crit'][$k] = $v + ['_src' => 'file', '_conf' => null];
            }
        }
        foreach ((array) ($crit['late'] ?? []) as $k => $v) {
            if (is_array($v)) {
                $out['late'][$k] = $v + ['_src' => 'file', '_conf' => null];
            }
        }

        return $out;
    }

    /**
     * Write the files into the table: file rows are replaced, a row Anee added stays (and gives way to a file row of
     * the same key). Returns counts per kind.
     */
    public static function sync(): array
    {
        if (! self::ready()) {
            return [];
        }
        $files = self::fromFiles();
        $now = now();
        $counts = [];
        foreach ($files as $kind => $rows) {
            foreach ($rows as $key => $data) {
                $conf = $data['_conf'] ?? null;
                unset($data['_src'], $data['_conf']);
                DB::table(self::TABLE)->updateOrInsert(['kind' => $kind, 'ekey' => mb_substr((string) $key, 0, 160)], [
                    'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'source' => 'file',
                    'conf' => is_string($conf) ? mb_substr($conf, 0, 8) : null, 'deleteStatus' => 1, 'updated_at' => $now, 'created_at' => $now,
                ]);
                $counts[$kind] = ($counts[$kind] ?? 0) + 1;
            }
        }
        self::forget();

        return $counts;
    }

    private static function norm(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($s));
    }

    /** One active ingredient's effect, by its dictionary name (or any spelling of it), or null. */
    public static function ingredient(string $name): ?array
    {
        $rows = self::all()['ingredient'] ?? [];
        if (isset($rows[$name])) {
            return ['name' => $name] + $rows[$name];
        }
        if (self::$byNorm === null) {
            self::$byNorm = [];
            foreach ($rows as $n => $r) {
                self::$byNorm[self::norm($n)] = $n;
            }
        }
        $n = self::$byNorm[self::norm($name)] ?? null;

        return $n !== null ? ['name' => $n] + $rows[$n] : null;
    }

    /** One element's profile for a form and water, or null. */
    public static function nutrient(string $el, string $form, string $water): ?array
    {
        $row = self::all()['nutrient'][$el] ?? null;
        if (! $row) {
            return null;
        }
        $f = $row[$form] ?? ($form === 'drench' ? ($row['foliar'] ?? null) : null) ?? ($row['topdress'] ?? $row['basal'] ?? null);
        if (! is_array($f)) {
            return null;
        }
        $w = $f[$water] ?? $f['upland'] ?? $f['flooded'] ?? null;

        return is_array($w) ? $w + ['note' => (string) ($f['note'] ?? '')] : null;
    }

    /** The N sources' factors against urea. */
    public static function sources(): array
    {
        $s = self::all()['source']['n'] ?? [];
        unset($s['_src'], $s['_conf']);

        return $s;
    }

    /** A stage table's levels: stage label => {nutrients: {}, threats: {}}. */
    public static function crit(string $table): array
    {
        $c = self::all()['crit'][$table] ?? [];
        unset($c['_src'], $c['_conf']);

        return $c;
    }

    /** Why a nutrient applied late is wasted, per element, for a stage table. */
    public static function late(string $table): array
    {
        $c = self::all()['late'][$table] ?? [];
        unset($c['_src'], $c['_conf']);

        return $c;
    }

    /** How many rows of each kind (the mother app, the check). */
    public static function counts(): array
    {
        return array_map('count', self::all());
    }

    /**
     * Keep what Anee found for an ingredient the book lacks, so it is never looked up again. A file row is never
     * changed. Returns true when kept.
     */
    public static function learnIngredient(string $name, array $data): bool
    {
        $name = trim($name);
        if ($name === '' || ! self::ready() || self::ingredient($name)) {
            return false;
        }
        $days = array_values(array_map('intval', (array) ($data['days'] ?? [])));
        if (count($days) !== 3 || $days[1] < 1 || $days[1] > 120) {
            return false;
        }
        sort($days);
        $row = [
            'targets' => array_values(array_intersect((array) ($data['targets'] ?? []), ['insect', 'mite', 'fungus', 'bacteria', 'weed', 'snail', 'rat', 'nematode', 'growth'])),
            'move' => (string) ($data['move'] ?? 'contact'), 'onset' => max(0, min(7, (int) ($data['onset'] ?? 0))), 'days' => $days,
            'fade' => ($data['fade'] ?? '') === 'sharp' ? 'sharp' : 'gradual', 'rainfast' => is_numeric($data['rainfast'] ?? null) ? (float) $data['rainfast'] : null,
            'note' => mb_substr(trim((string) ($data['note'] ?? '')), 0, 220), 'learnedAt' => now()->toDateString(), 'src' => array_slice((array) ($data['src'] ?? []), 0, 6),
        ];
        if (! $row['targets']) {
            return false;
        }
        DB::table(self::TABLE)->insertOrIgnore(['kind' => 'ingredient', 'ekey' => mb_substr($name, 0, 160), 'data' => json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'source' => 'anee', 'conf' => in_array($data['conf'] ?? '', ['high', 'med', 'low'], true) ? $data['conf'] : 'med', 'deleteStatus' => 1, 'created_at' => now(), 'updated_at' => now()]);
        self::forget();

        return true;
    }
}
