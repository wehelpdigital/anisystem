<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The nutrient need as a book (2026-10-18, the owner: "pre-create a
 * multilevel database of nutrients requirement based on the yield target,
 * crop, etc. ... the results for providing nutrient requirement is not that
 * far off from each other and we can lessen the amount of tokens").
 *
 * One door for every module that states what a field needs: forField() runs
 * NPK Plus's need model (App\Support\NpkNeed, the page's needModel() twin)
 * and stamps it with the model's version, so the Crop Protocol, NPK Plus's
 * three readings, the Protocol Builder and the season chat all say the same
 * numbers for the same field, and no prompt asks Anee to look a rate up.
 *
 * The book itself (as_nutrient_needs) is the need model written out at its
 * standard levels, rebuilt whenever the model changes (version()):
 *   level 'grid'     every crop x its yield levels x soil type (loam, sandy,
 *                    clay) x pH band (none, strong acid, acid, alkaline,
 *                    strongly alkaline): N, P2O5, K2O and every secondary and
 *                    micro element with its level and kg
 *   level 'variety'  every variety on record with a yield figure, at its
 *                    usual goal (80 percent of its potential), on a loam of
 *                    no pH trouble
 * A standard field reads its row (standard()); a row and a live run of the
 * same field agree to the kilo, because the book is written by the same
 * code. What the book does not hold, it does not pretend to: the soil's
 * conditions (sodic, saline, acid sulfate, peat, low organic matter,
 * waterlogging), a soil test, the last crop, a thin stand and the field's
 * own answers move the need in too many ways to write out (millions of
 * rows), and the model counts them in a fraction of a millisecond, so those
 * fields are counted live by forField(). Water moves the need only through
 * the field's own answers (NpkNeed::fieldCap), so it is not an axis.
 */
final class NutrientBook
{
    use Concerns\ShelfConnection;

    public const TEXTURES = ['loam', 'sandy', 'clay'];

    /** pH bands as the model reads them (NpkNeed::factors): a value inside each band. 'none' is neutral or not known. */
    public const PH = ['none' => null, 'strong_acid' => 4.8, 'acid' => 5.2, 'alkaline' => 7.7, 'strong_alkaline' => 8.4];

    /** The files the need is counted from: a change to any is a new version. */
    private const SOURCES = ['NpkModel.php', 'NpkCrops.php', 'NpkNeed.php'];

    public static function ready(): bool
    {
        return (bool) Cache::remember('nutrient-book:' . self::conn() . ':ready', 600, fn () => self::schema()->hasTable('as_nutrient_needs'));
    }

    /** The need model's version: the first 12 characters of a hash of its three files. */
    public static function version(): string
    {
        static $v = null;
        if ($v === null) {
            $h = '';
            foreach (self::SOURCES as $f) {
                $h .= (string) @file_get_contents(__DIR__ . '/' . $f);
            }
            $v = substr(sha1(str_replace("\r\n", "\n", $h)), 0, 12);
        }

        return $v;
    }

    /** The band a pH falls in, as the model reads it. */
    public static function phBand(?float $ph): string
    {
        if ($ph === null || $ph < 2 || $ph > 12) {
            return 'none';
        }

        return match (true) {
            $ph < 5 => 'strong_acid', $ph < 5.5 => 'acid', $ph > 8 => 'strong_alkaline', $ph > 7.3 => 'alkaline', default => 'none',
        };
    }

    /**
     * A crop's yield levels in t/ha: from half its Philippine average to
     * past what a good variety reaches (the model's own potential, 1.6 x the
     * guide yield), in steps that suit the crop, and the average itself (the
     * goal the model takes when nothing is given).
     */
    public static function levels(string $crop): array
    {
        $c = NpkNeed::crop($crop);
        $ty = (float) ($c['typicalYield']['hi'] ?? 0);
        if ($ty <= 0) {
            return [];
        }
        $guide = NpkModel::GUIDE_YIELD[$crop] ?? $ty * 1.25;
        $top = max($guide * NpkModel::POTENTIAL_X, $ty * 2);
        $raw = $ty / 10;
        $step = collect([0.25, 0.5, 1, 2, 2.5, 5, 10, 20, 25, 50])->first(fn ($s) => $s >= $raw) ?? 50;
        $out = [round($ty, 2)];
        for ($y = ceil($ty * 0.5 / $step) * $step; $y <= $top + 1e-9; $y += $step) {
            $out[] = round($y, 2);
        }
        $out = array_values(array_unique($out));
        sort($out);

        return $out;
    }

    /**
     * The field's need, counted the one way every module counts it (NPK
     * Plus's model), with the model's version. $in is NpkNeed::run()'s input.
     */
    public static function forField(array $in): array
    {
        return NpkNeed::run($in) + ['version' => self::version()];
    }

    /**
     * A variety's potential in t/ha from its record, the way NPK Plus takes
     * it when the farmer picks the record: the highest trial yield, else the
     * average one. Null when the variety is not on record or has no figure.
     */
    public static function varietyPotential(?string $crop, ?string $variety): ?float
    {
        if (! $crop || ! trim((string) $variety)) {
            return null;
        }
        try {
            $r = VarietyBook::match($crop, $variety);
        } catch (\Throwable $e) {
            return null;
        }
        if (! $r || ! preg_match('#t/?ha|tons?#i', (string) ($r['yieldUnit'] ?? 't/ha'))) {
            return null;
        }
        $t = $r['yieldTop'] ?? null;
        $t = $t !== null && (float) $t > 0 ? (float) $t : ((float) ($r['yield'] ?? 0) ?: null);

        return $t !== null && $t > 0 && $t < 300 ? round($t, 2) : null;
    }

    /**
     * A lot's or a protocol's need from what the app knows of it: the crop
     * and its way in, the variety (its potential from the record, so the
     * goal is 80 percent of it as on NPK Plus), a target when one is set, and
     * the field's own answers (App\Support\FieldProfile) with the soil, pH and
     * water a reading was given. The answers are read exactly as the Crop
     * Protocol reads its own (CropProtocolController::needInput), so the
     * same field gets the same numbers in every module.
     *
     * $more: soilConditions, ph, water (irrigated, partial or rainfed), testP, testK.
     */
    public static function forLot(?string $crop, ?string $method = null, ?string $variety = null, array $answers = [], ?float $targetT = null, ?float $areaHa = null, array $more = []): array
    {
        $key = NpkNeed::cropKey($crop, $method);
        $L = $answers ? FieldProfile::legacy($answers) : [];
        $water = (string) ($more['water'] ?? ($L['water'] ?? 'irrigated'));
        $p = [
            'crop' => $key ?? $crop, 'method' => $method, 'potential' => $key ? self::varietyPotential($key, $variety) : null,
            'targetYield' => $targetT, 'yieldUnit' => 'tons', 'area' => $areaHa ?: 1,
            'soil' => (string) ($L['soil'] ?? ''), 'water' => $water === 'partial' ? 'limited' : $water,
            'soilConditions' => array_values(array_unique(array_merge((array) ($L['soilConditions'] ?? []), (array) ($more['soilConditions'] ?? [])))),
            'ph' => $more['ph'] ?? null, 'fieldProfile' => $answers,
            'testP' => $more['testP'] ?? 'unsure', 'testK' => $more['testK'] ?? 'unsure', 'prevCrop' => 'unsure', 'residue' => 'unsure',
        ];
        $run = self::forField(\App\Http\Controllers\CropProtocolController::needInput($p));
        $run['potential'] = $p['potential'];

        return $run;
    }

    /**
     * A need and its check against a plan, in lines for a prompt: what the
     * field needs and the app's verdict on each element, so Anee explains
     * the numbers instead of counting them again.
     */
    public static function promptLines(array $run, array $perHa, string $lead = ''): string
    {
        if (empty($run['known']) || ! $run['need']) {
            return $lead . "Need: this crop has no numbers in NPK Plus; judge it from the crop's guide and say so.\n";
        }
        $g = $run['goal'];
        $goal = $g['Y'] ? rtrim(rtrim(number_format((float) $g['Y'], 2, '.', ''), '0'), '.') . ' t/ha (' . $g['why']
            . (! empty($run['potential']) ? ', the variety\'s potential on record ' . $run['potential'] . ' t/ha' : '') . ')' : 'not set';
        // Silicon and cobalt help but are not required (NpkModel::ELEMENTS): said apart, never judged short.
        $isBen = fn ($el) => ! empty(NpkModel::ELEMENTS[$el]['beneficial']);
        $micro = NpkNeed::microWords(array_filter($run['micro'], fn ($m, $el) => ! $isBen($el), ARRAY_FILTER_USE_BOTH));
        $ben = collect($run['micro'])->filter(fn ($m, $el) => $isBen($el) && (float) ($m['need'] ?? 0) > 0)
            ->map(fn ($m, $el) => (NpkNeed::NAMES[$el] ?? $el) . ' ' . self::kg((float) $m['need']) . ' kg')->implode(', ');
        $rows = array_values(array_filter(NpkNeed::check($run, $perHa), fn ($r) => empty($r['beneficial'])));
        $say = ['short' => 'short', 'right' => 'right', 'over' => 'too much', 'extra' => 'given though not needed'];
        $verdicts = $perHa === [] ? '' : collect($rows)->filter(fn ($r) => isset($say[$r['state']]))
            ->map(fn ($r) => $r['key'] . ' ' . $say[$r['state']] . ' (' . self::kg($r['plan']) . ' of ' . ($r['need'] !== null ? self::kg((float) $r['need']) : 'no need') . ')')->implode('; ');

        return $lead . 'THE NEED, counted by the app with NPK Plus\'s need model (the official guide rate sized to the yield goal, the soil and the field), version ' . ($run['version'] ?? self::version())
            . ': yield goal ' . $goal . '; ' . NpkNeed::words($run['need']) . ($run['whys'] ? ' (moved by: ' . implode('; ', array_map('lcfirst', array_slice($run['whys'], 0, 4))) . ')' : '') . ".\n"
            . ($micro !== '' ? $lead . 'Secondary and micro elements likely short here, kg of the element per hectare: ' . $micro . ".\n" : '')
            . ($ben !== '' ? $lead . 'Beneficial, not required (never call them short): ' . $ben . ".\n" : '')
            . ($verdicts !== '' ? $lead . 'THE APP\'S VERDICT, the plan per hectare against that need (short under 90 percent, too much over 130 percent for N, P2O5 and K2O): ' . $verdicts . ".\n" : '');
    }

    /** A kilo figure said short: "120", "3.5", "0.4". */
    private static function kg(float $v): string
    {
        return $v >= 10 ? (string) (int) round($v) : rtrim(rtrim(number_format($v, $v >= 1 ? 1 : 2, '.', ''), '0'), '.');
    }

    /**
     * The app's verdicts laid over Anee's nutrient list: where the app has
     * a verdict for an element, its status is the app's (Anee's comment is
     * kept), so the reading and the free sheet never disagree.
     */
    public static function overlay(array $list, array $run, array $perHa): array
    {
        $by = [];
        foreach (NpkNeed::check($run, $perHa) as $r) {
            if (empty($r['beneficial']) && in_array($r['state'], ['short', 'right', 'over', 'extra'], true)) {
                $by[$r['key']] = $r['state'] === 'extra' ? 'over' : $r['state'];
            }
        }
        $alias = ['N' => 'N', 'NITROGEN' => 'N', 'P' => 'P2O5', 'P2O5' => 'P2O5', 'PHOSPHORUS' => 'P2O5', 'K' => 'K2O', 'K2O' => 'K2O', 'POTASSIUM' => 'K2O'];
        foreach ($list as &$n) {
            if (! is_array($n)) {
                continue;
            }
            $raw = strtoupper(trim((string) ($n['nutrient'] ?? '')));
            $k = $alias[$raw] ?? (array_key_exists(ucfirst(strtolower($raw)), NpkNeed::NAMES) ? ucfirst(strtolower($raw)) : (array_search(ucfirst(strtolower($raw)), NpkNeed::NAMES, true) ?: null));
            if ($k && isset($by[$k]) && ($n['status'] ?? '') !== $by[$k]) {
                $n['aneeStatus'] = $n['status'] ?? null;
                $n['status'] = $by[$k];
            }
        }
        unset($n);

        return $list;
    }

    /** The standard input for one cell of the book. */
    private static function input(string $crop, ?float $target, ?float $potential, string $texture, ?float $ph): array
    {
        return ['crop' => $crop, 'method' => null, 'potential' => $potential, 'target' => $target, 'areaHa' => 1, 'seed' => null,
            'texture' => $texture, 'conditions' => [], 'ph' => $ph, 'tests' => [], 'water' => 'irrigated', 'profile' => [], 'prev' => []];
    }

    /** A run, as a book row: the need, the micro elements with a need or a level, the reasons. */
    private static function row(array $run): array
    {
        $micros = [];
        foreach ((array) $run['micro'] as $el => $m) {
            if ((float) ($m['need'] ?? 0) > 0 || ! in_array($m['level'] ?? 'none', ['none', 'notneeded'], true)) {
                $micros[$el] = [$m['level'], round((float) $m['need'], 3)];
            }
        }

        return ['n' => $run['need']['N'] ?? null, 'p' => $run['need']['P2O5'] ?? null, 'k' => $run['need']['K2O'] ?? null,
            'micros' => json_encode($micros), 'whys' => json_encode(array_values(array_slice($run['whys'], 0, 6)))];
    }

    /** Whether the book holds the current version. */
    public static function built(): bool
    {
        return self::ready() && self::db()->table('as_nutrient_needs')->where('version', self::version())->exists();
    }

    /**
     * Write the book for the current version (the old versions are removed
     * once the new one is whole). Returns the rows written.
     */
    public static function build(?callable $say = null): int
    {
        if (! self::ready()) {
            return 0;
        }
        $v = self::version();
        self::db()->table('as_nutrient_needs')->where('version', $v)->delete();
        $now = now();
        $buf = [];
        $n = 0;
        $flush = function () use (&$buf, &$n) {
            foreach (array_chunk($buf, 400) as $chunk) {
                self::db()->table('as_nutrient_needs')->insert($chunk);
            }
            $n += count($buf);
            $buf = [];
        };
        foreach (array_keys(NpkCrops::TABLE) as $crop) {
            foreach (self::levels($crop) as $y) {
                foreach (self::TEXTURES as $tx) {
                    foreach (self::PH as $band => $ph) {
                        $run = NpkNeed::run(self::input($crop, $y, null, $tx, $ph));
                        $buf[] = ['version' => $v, 'level' => 'grid', 'crop' => $crop, 'variety' => null, 'potential' => null, 'yieldT' => $y,
                            'texture' => $tx, 'ph' => $band, 'created_at' => $now] + self::row($run);
                    }
                }
            }
            if (count($buf) >= 2000) {
                $flush();
            }
            if ($say) {
                $say($crop);
            }
        }
        // Every variety on record with a yield figure, at its usual goal.
        $seen = [];
        foreach (array_keys(NpkCrops::TABLE) as $crop) {
            // Rice sown in place shares rice's varieties: the book keeps them under rice.
            if (str_starts_with($crop, 'rice_')) {
                continue;
            }
            foreach (VarietyBook::rows($crop) as $r) {
                $name = trim((string) ($r['name'] ?? ''));
                $key = $crop . '|' . mb_strtolower($name);
                if ($name === '' || isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $pot = self::varietyPotential($crop, $name);
                if ($pot === null) {
                    continue;
                }
                $run = NpkNeed::run(self::input($crop, null, $pot, 'loam', null));
                $buf[] = ['version' => $v, 'level' => 'variety', 'crop' => $crop, 'variety' => mb_substr($name, 0, 120), 'potential' => $pot,
                    'yieldT' => round((float) $run['goal']['Yeff'], 2), 'texture' => 'loam', 'ph' => 'none', 'created_at' => $now] + self::row($run);
            }
        }
        $flush();
        self::db()->table('as_nutrient_needs')->where('version', '!=', $v)->delete();
        Cache::forget('nutrient-book:' . self::conn() . ':built');

        return $n;
    }

    /**
     * A standard field's row from the book: the crop at a yield the book
     * holds (or the variety's usual goal), on a soil type and a pH band.
     * Null when the book does not hold it (count it with forField()).
     */
    public static function standard(string $crop, ?float $yieldT = null, string $texture = 'loam', ?float $ph = null, ?string $variety = null): ?array
    {
        if (! self::ready()) {
            return null;
        }
        $crop = NpkNeed::cropKey($crop) ?? $crop;
        $texture = in_array($texture, self::TEXTURES, true) ? $texture : 'loam';
        $q = self::db()->table('as_nutrient_needs')->where('version', self::version())->where('crop', $crop);
        if ($variety !== null && $yieldT === null) {
            $q->where('level', 'variety')->where('variety', $variety);
        } else {
            $y = $yieldT ?? (float) (NpkNeed::crop($crop)['typicalYield']['hi'] ?? 0);
            $q->where('level', 'grid')->where('yieldT', round($y, 2))->where('texture', $texture)->where('ph', self::phBand($ph));
        }
        $r = $q->first();
        if (! $r) {
            return null;
        }

        return ['crop' => $r->crop, 'variety' => $r->variety, 'potential' => $r->potential !== null ? (float) $r->potential : null, 'yieldT' => (float) $r->yieldT,
            'texture' => $r->texture, 'ph' => $r->ph, 'need' => array_filter(['N' => $r->n, 'P2O5' => $r->p, 'K2O' => $r->k], fn ($v) => $v !== null),
            'micros' => json_decode((string) $r->micros, true) ?: [], 'whys' => json_decode((string) $r->whys, true) ?: [], 'version' => $r->version];
    }

    /**
     * A lot's need in one line for a chat (App\Support\SeasonContext): the
     * crop and the variety at its usual goal, before a soil test. Empty when
     * the crop has no numbers.
     */
    public static function lotLine(?string $crop, ?string $variety, ?string $method = null): string
    {
        $key = NpkNeed::cropKey($crop, $method);
        if (! $key) {
            return '';
        }
        try {
            $pot = self::varietyPotential($key, $variety);
            $run = NpkNeed::run(self::input($key, null, $pot, 'unsure', null));
        } catch (\Throwable $e) {
            return '';
        }
        if (! $run['need'] || empty($run['goal']['Y'])) {
            return '';
        }
        $micro = collect($run['micro'])->filter(fn ($m, $el) => in_array($m['level'], ['likely', 'watch'], true) && empty(NpkModel::ELEMENTS[$el]['beneficial']))
            ->map(fn ($m, $el) => (NpkNeed::NAMES[$el] ?? $el) . ' ' . (['likely' => 'likely short', 'watch' => 'may run short'][$m['level']]))->values()->implode(', ');

        return 'need by NPK Plus for ' . rtrim(rtrim(number_format((float) $run['goal']['Y'], 1, '.', ''), '0'), '.') . ' t/ha (' . ($pot ? '80 percent of the variety\'s best yield on record' : 'the Philippine average')
            . ', before a soil test; a lower goal needs less): ' . NpkNeed::words($run['need']) . ($micro !== '' ? '; ' . $micro : '');
    }
}
