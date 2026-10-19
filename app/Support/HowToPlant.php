<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * How to Plant (2026-10-19, the owner): how traditional Filipino farmers
 * plant each crop, step by step, with a clear word that it is the
 * traditional way and should be updated for a higher yield, a video where a
 * good one exists, and a door into the Protocol Builder.
 *
 * One guide per crop of the catalog: its season, planting material, one or
 * more ways in (transplanted, direct seeded, from cuttings ...), each with its
 * steps on a day count from the planting day (negative = before), the usual
 * spacing, water, fertilizer, care and harvest, common mistakes and what to
 * change for a higher yield. Researched crop by crop, written in our own
 * words, checked against the house rules by the scratchpad builder (no agency
 * named, no dashes, no copied runs, videos oEmbed accepts), shipped as
 * database/how-to-plant/guides/<crop>.json without the sources, and loaded
 * into as_how_to_plant by sync() from a migration. Read here, cached; with no
 * table yet the files answer.
 *
 * Rice's three ways live on rice: rice_dsr_wet and rice_dsr_dry open rice's
 * guide on their own way. The mixed "vegetables" key has no guide. The same
 * guides, without the video, are the public /how-to-plant pages
 * (database/how-to-plant/build_pages.py).
 */
final class HowToPlant
{
    public const TABLE = 'as_how_to_plant';

    private const CACHE = 'how-to-plant:v1:';

    private const TTL = 21600;

    /** The phases of a guide's timeline: label, the Tagalog beside it, an icon path. */
    public const PHASES = [
        'prep' => ['Before planting', 'Bago magtanim', 'M3 20h18M5 20V10l7-5 7 5v10M9 20v-5h6v5'],
        'nursery' => ['Nursery', 'Punlaan', 'M12 21v-8m0 0c0-3-2.5-5.5-6-5.5 0 3.5 2.5 5.5 6 5.5zm0 0c0-3 2.5-5.5 6-5.5 0 3.5-2.5 5.5-6 5.5zM5 21h14'],
        'planting' => ['Planting', 'Pagtatanim', 'M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z'],
        'care' => ['Care', 'Pag aalaga', 'M12 21s-7-4.4-7-10a4 4 0 017-2.6A4 4 0 0119 11c0 5.6-7 10-7 10z'],
        'harvest' => ['Harvest', 'Anihan', 'M5 9h14l-1.5 10a2 2 0 01-2 1.7h-7a2 2 0 01-2-1.7L5 9zm3 0V7a4 4 0 018 0v2'],
    ];

    /** What a step is, in words, and the Protocol Builder task type it becomes (AsScheduleActivity::ACTIVITY_TYPES). */
    public const TASKS = [
        'land_prep' => ['Land preparation', 'land_prep'],
        'seedbed' => ['Seedbed', 'planting'],
        'seed_treatment' => ['Seed treatment', 'seed_treatment'],
        'planting' => ['Planting', 'planting'],
        'replanting' => ['Replanting', 'planting'],
        'thinning' => ['Thinning', 'other'],
        'irrigation' => ['Water', 'irrigation'],
        'fertilizer' => ['Fertilizer', 'fertilizer'],
        'weeding' => ['Weeding', 'other'],
        'spraying_watch' => ['Scouting', 'monitoring'],
        'pruning' => ['Pruning', 'other'],
        'propping' => ['Propping', 'other'],
        'earthing_up' => ['Hilling up', 'other'],
        'harvest' => ['Harvest', 'harvest'],
        'postharvest' => ['After harvest', 'harvest'],
        'other' => ['Farm work', 'other'],
    ];

    /** Crop keys that read another crop's guide, opened on one of its ways. */
    public const SAME_AS = [
        'rice_dsr_wet' => ['rice', 'direct_wet'],
        'rice_dsr_dry' => ['rice', 'direct_dry'],
    ];

    /** A way that the Protocol Builder keeps under another crop key of the catalog. */
    private const WAY_CROP = [
        'rice' => ['direct_wet' => 'rice_dsr_wet', 'direct_dry' => 'rice_dsr_dry'],
    ];

    /** The temperate crops' shelves (FieldCatalogue leaves them out of its list). */
    private const INTL_SHELF = [
        'wheat' => 'corn', 'barley' => 'corn', 'oats' => 'corn', 'canola' => 'industrial', 'sunflower' => 'industrial',
        'sugarbeet' => 'roots', 'alfalfa' => 'legumes',
    ];

    private static array $memo = [];

    /* ------------------------------------------------------------ reading */

    /**
     * The crops that have a guide, shelf by shelf in the catalogue's order,
     * as the crop-pick partial wants them: key => [local, English, icon, shelf, null].
     */
    public static function crops(): array
    {
        if (isset(self::$memo['crops'])) {
            return self::$memo['crops'];
        }
        $index = self::index();
        $byShelf = array_fill_keys(array_keys(FieldCatalogue::SHELVES), []);
        foreach (CropCatalog::CROPS as $k => $c) {
            if (! isset($index[$k])) {
                continue;
            }
            $g = $index[$k];
            $shelf = self::shelfOf($k);
            $local = trim((string) ($g['local'] ?? '')) ?: (string) $g['name'];
            $byShelf[$shelf][$k] = [$local, (string) $g['name'], $c['icon'] ?? '🌱', $shelf, null];
        }

        return self::$memo['crops'] = array_merge(...array_values($byShelf));
    }

    /** The shelf a crop sits on in the picker and on the public hub. */
    public static function shelfOf(string $crop): string
    {
        return self::INTL_SHELF[$crop] ?? FieldCatalogue::shelfOf($crop);
    }

    /**
     * The light list of every guide (no steps): key => name, local, aka,
     * slug, video, ways [key, label, days], rev. Cached; one query.
     */
    public static function index(): array
    {
        if (isset(self::$memo['index'])) {
            return self::$memo['index'];
        }
        $build = function () {
            $out = [];
            foreach (self::rows() as $k => $g) {
                $out[$k] = [
                    'name' => $g['name'] ?? $k, 'local' => $g['local'] ?? '', 'aka' => $g['aka'] ?? [], 'slug' => $g['slug'] ?? str_replace('_', '-', $k),
                    'video' => ! empty($g['video']['youtubeId']), 'summary' => $g['summary'] ?? '',
                    // Whether a watering schedule is written for it (the guide's own or a way's).
                    'water' => (bool) self::watering($g) || (bool) array_filter((array) ($g['ways'] ?? []), fn ($w) => is_array($w) && self::watering([], $w)),
                    'ways' => array_map(fn ($w) => ['key' => $w['key'], 'label' => $w['label'], 'days' => $w['days'] ?? null], (array) ($g['ways'] ?? [])),
                    'rev' => $g['rev'] ?? '',
                ];
            }

            return $out;
        };
        try {
            $index = getenv('HTP_FILES') ? $build() : Cache::remember(self::CACHE . 'index', self::TTL, $build);
        } catch (\Throwable $e) {
            $index = $build();
        }

        return self::$memo['index'] = $index;
    }

    /** Whether a crop (or a key that reads another's guide) has a guide. */
    public static function has(?string $crop): bool
    {
        return self::resolve($crop) !== null;
    }

    /** [guide crop, way key or null] for a crop key, or null when no guide covers it. */
    public static function resolve(?string $crop): ?array
    {
        $crop = strtolower(trim((string) $crop));
        if ($crop === '') {
            return null;
        }
        if (isset(self::SAME_AS[$crop])) {
            [$crop, $way] = self::SAME_AS[$crop];

            return isset(self::index()[$crop]) ? [$crop, $way] : null;
        }

        return isset(self::index()[$crop]) ? [$crop, null] : null;
    }

    /** One crop's whole guide (no sources ever reach here), or null. */
    public static function guide(?string $crop): ?array
    {
        $r = self::resolve($crop);
        if (! $r) {
            return null;
        }
        $key = $r[0];
        if (array_key_exists('g:' . $key, self::$memo)) {
            return self::$memo['g:' . $key];
        }
        $read = function () use ($key) {
            if (self::ready()) {
                $row = DB::table(self::TABLE)->where('crop', $key)->where('deleteStatus', 1)->value('guide');
                if ($row) {
                    return json_decode((string) $row, true) ?: null;
                }
            }

            return self::file($key);
        };
        try {
            $g = getenv('HTP_FILES') ? $read() : Cache::remember(self::CACHE . 'g:' . $key . ':' . (self::index()[$key]['rev'] ?? ''), self::TTL, $read);
        } catch (\Throwable $e) {
            $g = $read();
        }

        return self::$memo['g:' . $key] = $g;
    }

    /** The way a crop key opens on: its own (rice_dsr_wet), else the guide's first. */
    public static function wayKey(array $guide, ?string $crop = null, ?string $want = null): string
    {
        $keys = array_column((array) ($guide['ways'] ?? []), 'key');
        foreach ([$want, self::resolve($crop)[1] ?? null] as $k) {
            if ($k !== null && in_array($k, $keys, true)) {
                return $k;
            }
        }

        return (string) ($keys[0] ?? '');
    }

    public static function way(array $guide, string $key): ?array
    {
        foreach ((array) ($guide['ways'] ?? []) as $w) {
            if (($w['key'] ?? '') === $key) {
                return $w;
            }
        }

        return null;
    }

    /** The table is there and holds the guides. */
    public static function ready(): bool
    {
        if (! array_key_exists('ready', self::$memo)) {
            // A local server started with HTP_FILES=1 reads the files in the
            // tree, to look at a build before it ships (local dev shares the
            // live database, whose table holds what shipped).
            if (getenv('HTP_FILES')) {
                return self::$memo['ready'] = false;
            }
            try {
                self::$memo['ready'] = Schema::hasTable(self::TABLE) && DB::table(self::TABLE)->where('deleteStatus', 1)->exists();
            } catch (\Throwable $e) {
                self::$memo['ready'] = false;
            }
        }

        return self::$memo['ready'];
    }

    /** Every live guide, from the table (or the files while it is not there). */
    private static function rows(): array
    {
        if (self::ready()) {
            $out = [];
            foreach (DB::table(self::TABLE)->where('deleteStatus', 1)->orderBy('sortOrder')->get(['crop', 'guide']) as $r) {
                $g = json_decode((string) $r->guide, true);
                if (is_array($g)) {
                    $out[$r->crop] = $g;
                }
            }
            if ($out) {
                return $out;
            }
        }

        return self::files();
    }

    /** The shipped files, crop => guide. */
    public static function files(): array
    {
        $out = [];
        foreach (glob(database_path('how-to-plant/guides/*.json')) ?: [] as $f) {
            $g = json_decode((string) @file_get_contents($f), true);
            if (is_array($g) && ! empty($g['crop']) && ! empty($g['ways'])) {
                unset($g['sources']);
                $out[$g['crop']] = $g;
            }
        }
        // In the catalogue's order.
        $order = array_flip(array_keys(CropCatalog::CROPS));
        uksort($out, fn ($a, $b) => ($order[$a] ?? 999) <=> ($order[$b] ?? 999));

        return $out;
    }

    private static function file(string $crop): ?array
    {
        if (! preg_match('/^[a-z0-9_]+$/', $crop)) {
            return null;
        }
        $g = json_decode((string) @file_get_contents(database_path('how-to-plant/guides/' . $crop . '.json')), true);
        if (! is_array($g)) {
            return null;
        }
        unset($g['sources']);

        return $g;
    }

    /* ------------------------------------------------------------ words */

    /** Whether a crop counts its life in months (a tree, a plantation crop). */
    public static function perennial(string $crop): bool
    {
        return CropStages::isPerennial($crop);
    }

    /** What day zero is on this way: transplanting, sowing or planting. */
    public static function dayWord(array $way): string
    {
        // The way's own name decides; its note only when the name says nothing
        // ("no transplanting shock" in a direct seeded way's note is no transplant).
        $name = strtolower(str_replace('_', ' ', ($way['key'] ?? '') . ' ' . ($way['label'] ?? '')));
        if (str_contains($name, 'transplant')) {
            return 'transplanting';
        }
        if (preg_match('/\b(direct|broadcast|sow|sown|seeded|seeding|drill|dibbl)/', $name)) {
            return 'sowing';
        }
        $note = strtolower((string) ($way['note'] ?? ''));
        if (preg_match('/day 0 is (?:the )?transplanting/', $note)) {
            return 'transplanting';
        }
        if (preg_match('/day 0 is (?:the )?sowing/', $note)) {
            return 'sowing';
        }

        return 'planting';
    }

    /** "21 days before transplanting", "Transplanting day", "Day 14", "Month 8", "Year 3". */
    public static function when(int $day, array $way, bool $months = false): string
    {
        $word = self::dayWord($way);
        if ($day === 0) {
            return ucfirst($word) . ' day';
        }
        $n = abs($day);
        if ($months && $n >= 60) {
            $m = (int) round($n / 30);
            $span = $m >= 24 && $m % 12 === 0 ? ($m / 12) . ' years' : $m . ' months';
            if ($day < 0) {
                return $span . ' before ' . $word;
            }

            return $m >= 24 && $m % 12 === 0 ? 'Year ' . ($m / 12) : 'Month ' . $m;
        }
        if ($day < 0) {
            return $n . ' ' . ($n === 1 ? 'day' : 'days') . ' before ' . $word;
        }

        return 'Day ' . $day;
    }

    /** The harvest in words: "About 95 days after transplanting", "About 36 months after planting". */
    public static function harvestWords(array $way, bool $months = false): ?string
    {
        $d = (int) ($way['days'] ?? 0);
        if ($d <= 0) {
            return null;
        }
        $word = self::dayWord($way);
        if ($months && $d >= 120) {
            $m = (int) round($d / 30);

            return 'About ' . ($m >= 24 && $m % 12 === 0 ? ($m / 12) . ' years' : $m . ' months') . ' after ' . $word;
        }

        return 'About ' . $d . ' days after ' . $word;
    }

    /**
     * A stage's stretch in words: "Day 7 to 40", "21 to 1 days before transplanting",
     * "Month 3 to 12", "Transplanting day to day 10". The twin of build_pages.py span_words.
     */
    public static function spanWords(int $from, int $to, array $way, bool $months = false): string
    {
        if ($from === $to) {
            return self::when($from, $way, $months);
        }
        $a = self::when($from, $way, $months);
        $b = self::when($to, $way, $months);
        foreach (['Day ', 'Month ', 'Year '] as $p) {
            if (str_starts_with($a, $p) && str_starts_with($b, $p)) {
                return $a . ' to ' . substr($b, strlen($p));
            }
        }
        // Months and years mixed: both ends in months ("Month 59 to 60", "Month 3 to 24").
        if (preg_match('/^(Month|Year) (\d+)$/', $a, $ma) && preg_match('/^(Month|Year) (\d+)$/', $b, $mb)) {
            $m = fn ($x) => $x[1] === 'Year' ? (int) $x[2] * 12 : (int) $x[2];

            return 'Month ' . $m($ma) . ' to ' . $m($mb);
        }
        $re = '/^(\d+) (days?|months|years) before (\w+)$/';
        if (preg_match($re, $a, $ma) && preg_match($re, $b, $mb) && $ma[3] === $mb[3] && rtrim($ma[2], 's') === rtrim($mb[2], 's')) {
            return $ma[1] . ' to ' . $mb[1] . ' ' . rtrim($ma[2], 's') . 's before ' . $ma[3];
        }

        return $a . ' to ' . lcfirst($b);
    }

    /* ------------------------------------------------------------ watering */

    /**
     * The watering a way follows (2026-10-19, the owner: "include watering
     * recommendations and schedules"): the way's own when it has one (rice's
     * paddy is not a direct seeded field), else the guide's. Null while none is
     * written. Shape: traditional, method, schedule [{stage, from, to, often,
     * amount, why}] on the guide's own day count, critical [lines], rainfed,
     * dryseason, wet, signs {dry, wet}, better [lines].
     */
    public static function watering(array $guide, ?array $way = null): ?array
    {
        $ok = fn ($w) => is_array($w) && (! empty($w['schedule']) || trim((string) ($w['traditional'] ?? '')) !== '');
        if ($way && $ok($way['watering'] ?? null)) {
            return $way['watering'];
        }

        return $ok($guide['watering'] ?? null) ? $guide['watering'] : null;
    }

    /** A stage that waters the seedbed or the nursery, not the field. */
    public const NURSERY_STAGE = '/seed ?beds?\b|nursery|punlaan|\btrays?\b|in (?:the )?(?:plastic )?bags|cutting bed|plant bed/i';

    /**
     * The schedule's stage a step's day falls in, as [index, stage], or null.
     * The seedbed and the field are watered side by side (the paddy soaks while
     * the beds stay wet), so a nursery step reads only the nursery's stages and
     * every other step only the field's. Where two overlap, the later one.
     */
    public static function stageAt(?array $water, int $day, ?string $phase = null, bool $nurseryWay = true): ?array
    {
        $hit = null;
        $best = 0;
        foreach (array_values((array) ($water['schedule'] ?? [])) as $i => $s) {
            if (! is_array($s) || $day < (int) ($s['from'] ?? 0) || $day > (int) ($s['to'] ?? 0)) {
                continue;
            }
            $name = (string) ($s['stage'] ?? '');
            $nur = (bool) preg_match(self::NURSERY_STAGE, $name);
            $prep = (bool) preg_match(self::PREP_STAGE, $name);
            // A nursery step: the seedbed's own stages first, else one before planting that is not the field's work.
            // Getting the field ready beside a nursery: only the field's stages. Anything else: never the nursery's.
            $score = match (true) {
                $phase === null => 1,
                $phase === 'nursery' => $nur ? 2 : ((! $prep && (int) ($s['to'] ?? 0) <= 0) ? 1 : 0),
                $phase === 'prep' && $nurseryWay => ($prep && ! $nur) ? 1 : 0,
                default => $nur ? 0 : 1,
            };
            if ($score > 0 && $score >= $best) {
                $hit = [$i, $s];
                $best = $score;
            }
        }

        return $hit;
    }

    /** A stage of getting the field ready (plowing, soaking the paddy, digging holes), not the nursery. */
    public const PREP_STAGE = '/plow|harrow|soak|level|land|field|clear|furrow|ridg|\bholes?\b|dig|prepar/i';

    /** Whether a way raises its plants in a nursery first (its stages before planting may be the seedbed's). */
    public static function nurseryWay(array $way): bool
    {
        return (bool) array_filter((array) ($way['steps'] ?? []), fn ($s) => is_array($s) && ($s['phase'] ?? '') === 'nursery');
    }

    /** Words a stage's name shares with every other, which say nothing about which one it is. */
    private const STAGE_WORDS = ['after', 'before', 'planting', 'transplanting', 'sowing', 'until', 'first', 'second', 'third', 'stage', 'stages',
        'month', 'months', 'weeks', 'years', 'early', 'later', 'young', 'plant', 'plants', 'water', 'field', 'harvest', 'every', 'trees'];

    /** Whether a stage is one the guide says must never go dry: its name, or a telling word of it, is in a critical line. */
    public static function critical(?array $water, array $stage): bool
    {
        $name = mb_strtolower(trim((string) ($stage['stage'] ?? '')));
        if ($name === '' || empty($water['critical'])) {
            return false;
        }
        $words = array_filter(preg_split('/[^\p{L}]+/u', $name) ?: [], fn ($x) => mb_strlen($x) >= 5 && ! in_array($x, self::STAGE_WORDS, true));
        foreach ((array) $water['critical'] as $line) {
            $line = mb_strtolower((string) $line);
            if (str_contains($line, $name)) {
                return true;
            }
            foreach ($words as $w) {
                if (preg_match('/\b' . preg_quote($w, '/') . '/u', $line)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** The way's name without its Tagalog: "Transplanted (lipat tanim)" gives "Transplanted". */
    public static function wayShort(array $way): string
    {
        return trim((string) preg_replace('/\s*\(.*$/u', '', (string) ($way['label'] ?? ''))) ?: (string) ($way['label'] ?? '');
    }

    /**
     * The tools that carry a "higher yield" line further, for this crop:
     * [label, url, tier key or null, why] per line index, at most two a line.
     */
    public static function tools(array $guide): array
    {
        $crop = (string) ($guide['crop'] ?? '');
        $fh = fn (string $tool) => Route::has('fh.page') ? route('fh.page', array_filter(['tool' => $tool, 'crop' => FieldCatalogue::cropKey($crop)])) : null;
        $seedCrops = class_exists(SeedSpacing::class) ? array_keys(SeedSpacing::CROPS) : [];
        $seedOk = in_array($crop, $seedCrops, true) || ($crop === 'banana') || str_starts_with($crop, 'rice');
        $T = [
            'npk' => ['NPK Plus Calculator', Route::has('npk.page') ? route('npk.page') : null, 'npkPlus'],
            'seed' => ['Seed Count and Distance', $seedOk && Route::has('seedcalc.page') ? route('seedcalc.page') : null, 'seedCount'],
            'variety' => ['Variety Finder', $fh('varieties'), 'fieldHelpers'],
            'pests' => ['Pest Finder', $fh('pests'), 'fieldHelpers'],
            'diseases' => ['Disease Finder', $fh('diseases'), 'fieldHelpers'],
            'weeds' => ['Weed Control Helper', $fh('weeds'), 'fieldHelpers'],
            'nutrients' => ['Nutrient Deficiency Finder', $fh('nutrients'), 'fieldHelpers'],
            'protocol' => ['Crop Protocol', Route::has('proto.page') ? route('proto.page') : null, 'aiAnalyses'],
            'landprep' => ['Land Prep Planner', Route::has('landprep.page') ? route('landprep.page') : null, 'aiAnalyses'],
            'lcc' => ['LCC Checker', str_starts_with($crop, 'rice') && Route::has('lcc.page') ? route('lcc.page') : null, null],
        ];
        $rules = [
            'lcc' => '/leaf colou?r|\blcc\b/i',
            'npk' => '/soil test|soil analysis|fertili[sz]|npk|nutrient|urea|potash|lime|manure|compost/i',
            'seed' => '/spacing|seed rate|seeding rate|seeds? a hill|seedlings? a hill|plant population|plants? a hectare|distance|too much seed|stand/i',
            'variety' => '/certified|variet|hybrid|clean seed|good seed|registered seed|planting material|suckers? from|disease free (?:seed|planting)|tissue cultured/i',
            'weeds' => '/\bweed/i',
            'pests' => '/\bpests?\b|insect|\bipm\b|scout|borer|worm|snail|rats?\b|integrated pest/i',
            'diseases' => '/disease|blight|wilt|rot\b|virus|fungus|fungal/i',
            'nutrients' => '/deficien|yellow(?:ing)? leaves/i',
            'landprep' => '/land prep|level(?:l)?ing|plow|harrow|raised bed|drainage canal/i',
            'protocol' => '/\bplan\b|schedule|calendar|on time|timing|record/i',
        ];
        $out = [];
        foreach ((array) ($guide['better'] ?? []) as $i => $line) {
            $got = [];
            foreach ($rules as $key => $re) {
                if (count($got) >= 2) {
                    break;
                }
                if (preg_match($re, (string) $line) && ! empty($T[$key][1])) {
                    $got[$key] = $T[$key];
                }
            }
            $out[$i] = array_values($got);
        }

        return $out;
    }

    /* ------------------------------------------------------------ into the Protocol Builder */

    /** The Protocol Builder crop for a way: rice's direct seeded ways are their own crops there. */
    public static function protocolCrop(array $guide, array $way): string
    {
        $crop = (string) ($guide['crop'] ?? '');

        return self::WAY_CROP[$crop][$way['key'] ?? ''] ?? $crop;
    }

    /** How the protocol counts its days for a way: DAT with a nursery before transplanting, DAS when sown, DAP planted, TREE for trees. */
    public static function wantDayType(string $crop, array $way): string
    {
        if (self::perennial($crop)) {
            return 'TREE';
        }
        $word = self::dayWord($way);
        $nursery = (bool) array_filter((array) ($way['steps'] ?? []), fn ($s) => ($s['phase'] ?? '') === 'nursery' && (int) ($s['day'] ?? 0) < 0);
        if ($word === 'transplanting' || ($nursery && in_array('DAT', CropCatalog::countersFor($crop), true))) {
            return 'DAT';
        }

        return $word === 'sowing' ? 'DAS' : 'DAP';
    }

    /**
     * A way's steps as Protocol Builder entries on the protocol's own count:
     * a divider, then one task per step on its day (the step's kind as the
     * task type, its phase as the subtitle, "From How to Plant (traditional
     * way)" in the description and its tip as the note). DAT protocols count
     * the seedbed in DAS from the sowing and the field from transplanting.
     * Steps past what the builder counts (day 999, a tree's later years) are
     * gathered in one note at the end.
     */
    public static function toProtocol(array $guide, array $way, string $dayType, array $counters): array
    {
        $steps = array_values((array) ($way['steps'] ?? []));
        $first = $counters[0] ?? 'DAS';
        $dat = $dayType === 'DAT' && in_array('DAT', $counters, true);
        // The seedbed's sowing: the earliest nursery step that sows (else the earliest nursery step).
        $sowAt = null;
        foreach ($steps as $s) {
            if (($s['phase'] ?? '') === 'nursery' && (int) $s['day'] < 0 && in_array($s['task'] ?? '', ['seedbed', 'planting'], true)) {
                $sowAt = $sowAt === null ? (int) $s['day'] : min($sowAt, (int) $s['day']);
            }
        }
        if ($sowAt === null) {
            foreach ($steps as $s) {
                if (($s['phase'] ?? '') === 'nursery' && (int) $s['day'] < 0) {
                    $sowAt = $sowAt === null ? (int) $s['day'] : min($sowAt, (int) $s['day']);
                }
            }
        }
        $where = function (int $d) use ($dat, $first, $sowAt): array {
            if ($dat && $sowAt !== null) {
                return $d >= 0 ? ['DAT', $d] : ['DAS', $d - $sowAt];
            }
            if ($dat) {
                return $d >= 0 ? ['DAT', $d] : ['DAS', $d];
            }

            return [$first, $d];
        };
        $months = self::perennial((string) ($guide['crop'] ?? ''));
        $from = 'From How to Plant (traditional way).';
        $out = [];
        $base = 'htp_' . preg_replace('/[^a-z0-9_]/', '', (string) ($guide['crop'] ?? '')) . '_' . preg_replace('/[^a-z0-9_]/', '', (string) ($way['key'] ?? ''));
        [$c0, $d0] = $where((int) ($steps[0]['day'] ?? 0));
        $out[] = ['id' => $base . '_div', 'kind' => 'divider', 'counter' => $c0, 'day' => max(-365, min(999, $d0)),
            'label' => mb_substr('How to Plant: ' . self::wayShort($way) . ', the traditional way', 0, 80), 'color' => 'amber', 'pos' => 0];
        $pos = 10;
        $later = [];
        foreach ($steps as $i => $s) {
            [$counter, $day] = $where((int) $s['day']);
            if ($day > 999) {
                $later[] = self::when((int) $s['day'], $way, $months) . ': ' . $s['title'] . '. ' . $s['how'];
                continue;
            }
            [$kindWord, $type] = self::TASKS[$s['task'] ?? 'other'] ?? self::TASKS['other'];
            $phase = self::PHASES[$s['phase'] ?? 'care'][0] ?? 'Care';
            $out[] = [
                'id' => $base . '_' . $i,
                'counter' => $counter,
                'day' => max(-365, min(999, $day)),
                'title' => mb_substr((string) $s['title'], 0, 160),
                'subtitle' => mb_substr($phase . ' · ' . $kindWord . ' · ' . self::when((int) $s['day'], $way, $months), 0, 200),
                'description' => mb_substr($from . "\n\n" . trim((string) $s['how']) . self::partsText($s), 0, 4000),
                'type' => $type,
                'spray' => [],
                'groups' => [],
                'note' => mb_substr((string) ($s['tip'] ?? ''), 0, 2000),
                'priority' => in_array($s['task'] ?? '', ['planting', 'harvest', 'seedbed'], true) ? 'high' : 'medium',
                'workers' => null,
                'pos' => $pos,
            ];
            $pos += 10;
        }
        // Watering (2026-10-19, the owner): one task per stage of the schedule,
        // on the stage's first day. The builder keeps no span on a task, so the
        // stage's last day is said in its subtitle, description and note.
        $water = self::watering($guide, $way);
        foreach (array_values((array) ($water['schedule'] ?? [])) as $i => $st) {
            if (! is_array($st) || trim((string) ($st['stage'] ?? '')) === '') {
                continue;
            }
            $fromD = (int) ($st['from'] ?? 0);
            $toD = max($fromD, (int) ($st['to'] ?? $fromD));
            [$counter, $day] = $where($fromD);
            $span = self::spanWords($fromD, $toD, $way, $months);
            $crit = self::critical($water, $st);
            $often = trim((string) ($st['often'] ?? ''));
            $much = trim((string) ($st['amount'] ?? ''));
            if ($day > 999) {
                $later[] = $span . ': Watering, ' . $st['stage'] . '. ' . trim($often . ' ' . $much);
                continue;
            }
            $until = $toD > $fromD ? 'Keep this up until ' . lcfirst(self::when($toD, $way, $months)) . '.' : '';
            $out[] = [
                'id' => $base . '_w' . $i,
                'counter' => $counter,
                'day' => max(-365, min(999, $day)),
                'title' => mb_substr('Watering: ' . $st['stage'], 0, 160),
                'subtitle' => mb_substr('Water · ' . $span, 0, 200),
                'description' => mb_substr($from . "\n\n" . 'Watering, ' . $st['stage'] . ': ' . $span . '.'
                    . ($often !== '' ? "\nHow often: " . $often : '') . ($much !== '' ? "\nHow much: " . $much : '')
                    . (! empty($st['why']) ? "\n\nWhy: " . trim((string) $st['why']) : '')
                    . ($crit ? "\n\nThis stage must never go dry." : ''), 0, 4000),
                'type' => 'irrigation',
                'spray' => [],
                'groups' => [],
                'note' => mb_substr(trim($until . ($crit ? ' Never let it go dry now.' : '')), 0, 2000),
                'priority' => $crit ? 'high' : 'medium',
                'workers' => null,
                'pos' => $pos,
            ];
            $pos += 10;
        }
        if ($later) {
            [$cl] = $where(999);
            $out[] = ['id' => $base . '_later', 'kind' => 'note', 'counter' => $cl, 'day' => 999, 'pos' => $pos,
                'text' => mb_substr("Later steps from How to Plant (traditional way), past what the count holds:\n" . implode("\n", $later), 0, 2000)];
        }

        return $out;
    }

    /** A step's parts as numbered lines for a task's description: "1. Soak the seed (24 hours): ...". */
    public static function partsText(array $step): string
    {
        $parts = array_values(array_filter((array) ($step['parts'] ?? []), 'is_array'));
        if (! $parts) {
            return '';
        }
        $lines = [];
        foreach ($parts as $i => $p) {
            $lines[] = ($i + 1) . '. ' . trim((string) ($p['title'] ?? '')) . (! empty($p['time']) ? ' (' . $p['time'] . ')' : '') . ': ' . trim((string) ($p['how'] ?? ''))
                . (! empty($p['tip']) ? ' Tip: ' . trim((string) $p['tip']) : '');
        }

        return "\n\nPart by part:\n" . implode("\n", $lines);
    }

    /* ------------------------------------------------------------ loading */

    /**
     * Load the files into the table: new and changed guides written, file
     * rows no longer shipped retired (deleteStatus 0), rows from anywhere
     * else left alone. Returns the counts.
     */
    public static function sync(): array
    {
        if (! Schema::hasTable(self::TABLE)) {
            return ['guides' => 0, 'retired' => 0];
        }
        $now = now();
        $theirs = DB::table(self::TABLE)->where('source', '!=', 'file')->pluck('crop')->flip();
        $rows = [];
        $i = 0;
        foreach (self::files() as $k => $g) {
            $i++;
            if (isset($theirs[$k])) {
                continue;
            }
            $rows[] = ['crop' => $k, 'slug' => mb_substr((string) ($g['slug'] ?? str_replace('_', '-', $k)), 0, 80),
                'name' => mb_substr((string) ($g['name'] ?? $k), 0, 120), 'local' => mb_substr((string) ($g['local'] ?? ''), 0, 120),
                'guide' => json_encode($g, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'hasVideo' => ! empty($g['video']['youtubeId']) ? 1 : 0,
                'rev' => mb_substr((string) ($g['rev'] ?? ''), 0, 20), 'sortOrder' => $i, 'source' => 'file', 'deleteStatus' => 1,
                'created_at' => $now, 'updated_at' => $now];
        }
        foreach (array_chunk($rows, 10) as $chunk) {
            DB::table(self::TABLE)->upsert($chunk, ['crop'], ['slug', 'name', 'local', 'guide', 'hasVideo', 'rev', 'sortOrder', 'deleteStatus', 'updated_at']);
        }
        $retired = DB::table(self::TABLE)->where('source', 'file')->where('deleteStatus', 1)
            ->whereNotIn('crop', array_column($rows, 'crop') ?: [''])->update(['deleteStatus' => 0, 'updated_at' => $now]);
        self::forget();

        return ['guides' => count($rows), 'retired' => $retired];
    }

    /** Drop the cached copies, after the rows change. */
    public static function forget(): void
    {
        try {
            Cache::forget(self::CACHE . 'index');
        } catch (\Throwable $e) {
            // no cache store: nothing kept
        }
        self::$memo = [];
    }
}
