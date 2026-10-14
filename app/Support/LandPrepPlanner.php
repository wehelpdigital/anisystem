<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * THE LAND PREP PLANNER'S ARITHMETIC (2026-10-14).
 *
 * Everything Anee should not be trusted to count is counted here, in PHP:
 *
 *  - the base steps of a plan: rice by planting method from the PalayCheck
 *    System of PhilRice (database/land-prep/rice-methods.json, written in our
 *    own words), the other crops from App\Support\LandPrep::PLANS, each a
 *    window of days before (negative) or after the planting day;
 *  - the rules the farmer's answers trigger (a soil test and lime for strongly
 *    acid soil, a salt flush, a stale seedbed for weedy rice, a backup
 *    seedbed for flood prone fields), each tagged with its source;
 *  - the seed preparation of the chosen variety: the brand's own soak,
 *    water change and incubation hours (database/land-prep/seedprep.json),
 *    the PhilRice general practice when no brand instruction exists, and
 *    a plain "follow the seed bag" when nothing was researched;
 *  - the crew: every lot's plowing, harrowing, leveling and hand work laid
 *    on the calendar day by day, so no worker and no machine is in two
 *    places at once and each lot is ready on its planting day. Where the
 *    crew cannot keep up, the late steps are named, or (when the farmer
 *    allows it) the planting days are staggered until they can.
 *
 * Anee adds her judgement on top (LandPrepController): her own steps,
 * notes and an analysis of why, which run through the same scheduler.
 */
final class LandPrepPlanner
{
    /** Rice planting methods: short label, long label, line under it, icon. */
    public const RICE_METHODS = [
        'tpr_manual' => ['By hand', 'Transplanted by hand', 'Seedlings of 18 to 21 days from a wet seedbed', '🤲'],
        'tpr_mechanical' => ['By machine', 'Transplanted by machine', 'Trays or dapog, seedlings of 14 to 18 days', '🚜'],
        'dsr_wet_broadcast' => ['Broadcast', 'Wet seeded, broadcast (sabog tanim)', 'Sprouted seed scattered on puddled soil', '🌾'],
        'dsr_wet_drum' => ['Drum seeder', 'Wet seeded, drum seeder', 'Sprouted seed sown in rows with a drum seeder', '🛞'],
        'dsr_dry' => ['Dry seeded', 'Dry seeded', 'Dry seed drilled or dibbled into moist soil', '☀️'],
    ];

    /** How the other crops go in: short label, line, icon. */
    public const METHODS = [
        'direct' => ['Direct sown', 'Seed sown straight in the field', '🌱'],
        'transplant' => ['Transplanted seedlings', 'Raised in a nursery or trays first', '🪴'],
        'cuttings' => ['Cuttings, setts or tubers', 'Vine tips, stem pieces, rhizomes or seed tubers', '✂️'],
        'cloves' => ['Cloves or bulb sets', 'Planted straight in the beds', '🧄'],
        'setts' => ['Cane setts', 'Cane pieces with two or three buds', '🎋'],
        'suckers' => ['Suckers', 'Healthy shoots from a mother plant', '🌿'],
        'tissue' => ['Tissue cultured plantlets', 'Clean plantlets from a lab nursery', '🧪'],
        'grafted' => ['Grafted or marcotted seedlings', 'From an accredited nursery', '🌳'],
        'seedling' => ['Seedlings', 'Grown from seed or seednuts', '🌱'],
    ];

    /** The ways each plan group goes in, the usual one first. */
    private const GROUP_METHODS = [
        'corn' => ['direct'], 'legume' => ['direct'], 'vegetable' => ['transplant', 'direct'], 'cucurbit' => ['direct', 'transplant'],
        'root' => ['cuttings'], 'bulb' => ['transplant', 'direct'], 'sugarcane' => ['setts'], 'banana' => ['suckers', 'tissue'],
        'tree' => ['grafted', 'seedling'],
    ];

    /** Crops whose ways differ from their group's. */
    private const CROP_METHODS = [
        'carrot' => ['direct'], 'radish' => ['direct'], 'garlic' => ['cloves'], 'shallot' => ['cloves'], 'onion_spring' => ['direct', 'transplant'],
        'pineapple' => ['suckers'], 'papaya' => ['seedling'], 'coconut' => ['seedling'], 'oilpalm' => ['seedling'], 'bamboo' => ['cuttings'],
        'malunggay' => ['cuttings', 'seedling'], 'dragonfruit' => ['cuttings'], 'sayote' => ['cuttings'],
    ];

    /** Where a step's words come from, as the tag on screen says it. */
    public const TAGS = [
        'philrice' => 'PhilRice practice',
        'irri' => 'IRRI practice',
        'extension' => 'Extension practice',
        'anee' => 'Anee\'s suggestion',
    ];

    /** Why a suggestion is there. */
    public const REASONS = [
        'soil' => 'your soil', 'history' => 'the field\'s history', 'weather' => 'the weather', 'enso' => 'the ENSO outlook',
        'crew' => 'your crew', 'seed' => 'your seed', 'water' => 'your water', 'method' => 'how you plant',
    ];

    /**
     * Who does a step and how much of it there is: [kind, measure]. A
     * machine step counts machine days per hectare by the equipment
     * (MACHINE_RATES), a manual one person days per hectare.
     */
    private const WORK = [
        'dikes' => ['manual', 5], 'straw' => ['manual', 2], 'clean_field' => ['manual', 3], 'drain_canal' => ['manual', 2],
        'plow' => ['machine', 'plow'], 'first_plow' => ['machine', 'plow'], 'crack_plow' => ['machine', 'plow'], 'subsoil' => ['machine', 'plow'], 'deep_till' => ['machine', 'plow'],
        'harrow1' => ['machine', 'harrow'], 'harrow2' => ['machine', 'harrow'], 'harrow' => ['machine', 'harrow'], 'harrow_level' => ['machine', 'harrowlevel'],
        'level' => ['machine', 'level'], 'extra_level' => ['machine', 'level'], 'rough_level' => ['machine', 'roughlevel'], 'stale_kill' => ['machine', 'light'],
        'furrow' => ['machine', 'furrow'], 'ridges' => ['machine', 'furrow'],
        'canals' => ['manual', 2], 'manure' => ['manual', 1.5], 'lime' => ['manual', 1.5], 'gypsum' => ['manual', 1.5],
        'seedbed_site' => ['manual', 1], 'seedbed_beds' => ['manual', 1], 'nursery_fill' => ['manual', 3], 'sow_seedbed' => ['manual', 0.5], 'sow_nursery' => ['manual', 2],
        'backup_seedbed' => ['manual', 0.3], 'nursery' => ['manual', 1],
        'clear' => ['manual', 10], 'clear_land' => ['manual', 20], 'stones' => ['manual', 5], 'contour' => ['manual', 3],
        'beds' => ['manual', 25], 'mulch' => ['manual', 10], 'drainage' => ['manual', 6], 'holes' => ['manual', 15], 'fill' => ['manual', 10],
        'windbreak' => ['manual', 4], 'material' => ['manual', 2], 'snail_screens' => ['manual', 0.5], 'trellis' => ['manual', 15],
        'sucker_prep' => ['manual', 1], 'harden' => ['manual', 0.5],
    ];

    /** Machine days per hectare by what pulls the plow; "hand" is person days. */
    private const MACHINE_RATES = [
        'hand_tractor' => ['plow' => 2, 'harrow' => 1, 'harrowlevel' => 2, 'level' => 1, 'roughlevel' => 2, 'light' => 0.5, 'furrow' => 1],
        'tractor' => ['plow' => 0.35, 'harrow' => 0.25, 'harrowlevel' => 0.6, 'level' => 0.4, 'roughlevel' => 0.8, 'light' => 0.2, 'furrow' => 0.3],
        'carabao' => ['plow' => 5, 'harrow' => 2.5, 'harrowlevel' => 4.5, 'level' => 2, 'roughlevel' => 4, 'light' => 1.5, 'furrow' => 2],
        'hired' => ['plow' => 1, 'harrow' => 0.5, 'harrowlevel' => 1, 'level' => 0.5, 'roughlevel' => 1, 'light' => 0.3, 'furrow' => 0.5],
        'hand' => ['plow' => 30, 'harrow' => 15, 'harrowlevel' => 22, 'level' => 8, 'roughlevel' => 16, 'light' => 6, 'furrow' => 10],
    ];

    /** What pulls the plow, as the farmer picks it. */
    public const EQUIPMENT = [
        'hand_tractor' => ['Hand tractor', 'Kuliglig or power tiller', '🚜'],
        'tractor' => ['Four wheel tractor', 'Or a rotavator', '🚛'],
        'carabao' => ['Carabao', 'Plow and harrow pulled by a carabao', '🐃'],
        'hired' => ['Hired service', 'An operator brings the machine', '🤝'],
        'hand' => ['By hand only', 'Hoes and shovels, no machine', '🧑‍🌾'],
    ];

    /** A step may start only this many days after the one before it ends. */
    private const AFTER = [
        'harrow1' => ['plow', 5], 'harrow2' => ['harrow1', 5], 'level' => ['harrow2', 4], 'extra_level' => ['harrow2', 2],
        'harrow' => ['plow', 4], 'furrow' => ['harrow', 1], 'beds' => ['harrow', 1], 'ridges' => ['harrow', 1], 'mulch' => ['beds', 1],
        'canals' => ['level', 0], 'fill' => ['holes', 1], 'seedbed_beds' => ['seedbed_site', 1], 'harrow_level' => ['plow', 2],
        'stale_kill' => ['harrow_level', 7], 'rough_level' => ['first_plow', 3], 'plow' => ['first_plow', 10], 'stones' => ['plow', 1],
    ];

    /**
     * The steps whose lateness moves a planting day: the field work and the
     * seedbed. A day late on a dike or a sack of manure is said, not staggered for.
     */
    private const CRITICAL = ['plow', 'first_plow', 'crack_plow', 'deep_till', 'subsoil', 'harrow1', 'harrow2', 'harrow', 'harrow_level', 'level',
        'stale_kill', 'furrow', 'ridges', 'beds', 'holes', 'fill', 'seedbed_site', 'seedbed_beds', 'sow_seedbed', 'nursery_fill', 'sow_nursery', 'nursery', 'mulch'];

    /** Work that may start this many days before its window when the crew needs the room (a dike can be fixed early). */
    private const EARLY = [
        'dikes' => 14, 'straw' => 5, 'clear' => 14, 'clear_land' => 14, 'clean_field' => 14, 'drain_canal' => 14, 'windbreak' => 30,
        'holes' => 14, 'contour' => 7, 'snail_screens' => 4, 'drainage' => 14, 'nursery_fill' => 2, 'seedbed_site' => 3,
    ];

    /** People a planting day takes, per hectare, and the words for it. */
    private const PLANTING = [
        'transplant:tpr_manual' => [20, 'planters'], 'pull:tpr_manual' => [8, 'people pulling seedlings'],
        'transplant:tpr_mechanical' => [3, 'people with the transplanter'], 'sow:dsr_wet_broadcast' => [1, 'people sowing'],
        'sow:dsr_wet_drum' => [1.5, 'people with the drum seeder'], 'sow:dsr_dry' => [2, 'people sowing'],
        'plant:corn' => [4, 'people planting'], 'plant:legume' => [4, 'people planting'], 'plant:vegetable' => [12, 'people planting'],
        'plant:cucurbit' => [4, 'people planting'], 'plant:root' => [8, 'people planting'], 'plant:bulb' => [30, 'people planting'],
        'plant:sugarcane' => [10, 'people planting'], 'plant:banana' => [4, 'people planting'], 'plant:tree' => [3, 'people planting'],
    ];

    /** The activity type each step becomes in the Protocol Builder. */
    private const TYPES = [
        'manure' => 'fertilizer', 'seedbed_feed' => 'fertilizer', 'nursery_feed' => 'fertilizer', 'seedbed_zinc' => 'fertilizer', 'nursery_zinc' => 'fertilizer',
        'seed_soak' => 'seed_treatment', 'seed_incubate' => 'seed_treatment', 'seed_test' => 'seed_treatment', 'sprout_check' => 'seed_treatment',
        'material' => 'seed_treatment', 'salt_variety' => 'seed_treatment', 'sucker_prep' => 'seed_treatment', 'harden' => 'seed_treatment',
        'sow_seedbed' => 'planting', 'sow_nursery' => 'planting', 'nursery' => 'planting', 'seedbed_site' => 'planting', 'seedbed_beds' => 'planting',
        'nursery_fill' => 'planting', 'nursery_site' => 'planting', 'nursery_care' => 'planting', 'backup_seedbed' => 'planting', 'pull' => 'planting',
        'transplant' => 'planting', 'sow' => 'planting', 'plant' => 'planting', 'drum_setup' => 'planting', 'replant' => 'planting', 'water_replant' => 'planting',
        'seedbed_water' => 'irrigation', 'keep_wet' => 'irrigation', 'first_water' => 'irrigation', 'no_flood' => 'irrigation', 'soak_field' => 'irrigation',
        'weeds' => 'herbicide', 'weeds_pre' => 'herbicide', 'weeds_post' => 'herbicide', 'weeds_snails' => 'herbicide',
        'guard' => 'monitoring', 'rest' => 'monitoring', 'snail_screens' => 'monitoring',
        'calendar' => 'other', 'book_service' => 'other', 'soil_test' => 'service', 'trellis' => 'other',
    ];

    /** Steps that are the planting itself, by method family. */
    private const PLANT_KEYS = ['transplant', 'sow', 'plant'];

    /* ================================================================ books */

    /** database/land-prep/rice-methods.json: the five rice methods, the questions and their effects. */
    public static function riceBook(): array
    {
        return self::read('rice-methods.json');
    }

    /** database/land-prep/seedprep.json: seed preparation by hybrid brand, and the general practice. */
    public static function seedBook(): array
    {
        return self::read('seedprep.json');
    }

    private static function read(string $file): array
    {
        $path = database_path('land-prep/' . $file);
        $stamp = @filemtime($path) ?: 0;

        return Cache::remember('land-prep:' . $file . ':' . $stamp, 3600, function () use ($path) {
            $d = json_decode((string) @file_get_contents($path), true);

            return is_array($d) ? $d : [];
        });
    }

    /* ============================================================= the crop */

    /** The plan group a crop belongs to: 'rice' or a LandPrep::PLANS key. */
    public static function groupFor(string $crop): string
    {
        return str_starts_with($crop, 'rice') ? 'rice' : LandPrep::planFor($crop);
    }

    /** The planting methods a crop offers, the usual one first: key => [label, line, icon]. */
    public static function methodsFor(string $crop): array
    {
        if (str_starts_with($crop, 'rice')) {
            return array_map(fn ($m) => [$m[1], $m[2], $m[3]], self::RICE_METHODS);
        }
        $group = self::groupFor($crop);
        $keys = self::CROP_METHODS[$crop] ?? self::GROUP_METHODS[$group] ?? ['direct'];
        if ($group === 'vegetable' && ! isset(self::CROP_METHODS[$crop])) {
            // A crop the board counts from transplanting is usually transplanted.
            $keys = (CropCatalog::countersFor($crop)[0] ?? 'DAP') === 'DAT' ? ['transplant', 'direct'] : ['direct', 'transplant'];
        }
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = self::METHODS[$k];
        }

        return $out;
    }

    /** True when the method raises seedlings before the planting day. */
    public static function hasNursery(string $crop, string $method): bool
    {
        return in_array($method, ['tpr_manual', 'tpr_mechanical'], true)
            || ($method === 'transplant' && in_array(self::groupFor($crop), ['vegetable', 'cucurbit', 'bulb'], true));
    }

    /** What the planting day is called for this crop and method. */
    public static function dayWord(string $crop, string $method): string
    {
        return match (true) {
            in_array($method, ['tpr_manual', 'tpr_mechanical', 'transplant'], true) => 'transplanting',
            in_array($method, ['dsr_wet_broadcast', 'dsr_wet_drum', 'dsr_dry', 'direct'], true) => 'sowing',
            default => 'planting',
        };
    }

    /* ======================================================== the base plan */

    /**
     * The base steps for a crop and method, before the farmer's answers:
     * each [key, from, to, what, how, basis, tag, follows?, type, work].
     */
    public static function baseSteps(string $crop, string $method): array
    {
        if (str_starts_with($crop, 'rice')) {
            $m = self::riceBook()['methods'][$method] ?? self::riceBook()['methods']['tpr_manual'] ?? ['steps' => []];
            $out = [];
            foreach ((array) $m['steps'] as $s) {
                $out[] = self::step($s['key'], (int) $s['from'], (int) $s['to'], $s['what'], $s['how'], self::tagOf($s['basis'] ?? 'palaycheck'),
                    $s['follows'] ?? null, ($s['basis'] ?? '') === 'suggestion' ? 'method' : null);
            }

            return $out;
        }
        $group = self::groupFor($crop);
        $out = [];
        foreach (LandPrep::PLANS[$group]['steps'] ?? [] as $s) {
            $out[] = self::step($s[4] ?? self::slug($s[2]), $s[0], $s[1], $s[2], $s[3], 'extension');
        }

        return self::byMethod($out, $crop, $group, $method);
    }

    /** The other crops' plans, bent to how the crop goes in. */
    private static function byMethod(array $steps, string $crop, string $group, string $method): array
    {
        $drop = [];
        $add = [];
        $say = [];
        if ($group === 'vegetable' && $method === 'direct') {
            $drop[] = 'nursery';
            $say['plant'] = ['Sow the seed', 'Sow in rows or hills on the moist beds, a little deeper in sandy soil, and water gently. Thin the seedlings once they stand.'];
        }
        if ($group === 'cucurbit' && $method === 'transplant') {
            $add[] = self::step('nursery', -21, -14, 'Sow seedlings in pots or trays', 'Sow the seed in pots, plugs or seedling trays 2 to 3 weeks ahead. Gourds and melons dislike root disturbance, so move them with the whole root ball.', 'extension');
            $say['plant'] = ['Transplant the seedlings', 'Transplant in the late afternoon with the whole root ball and water right after.'];
        }
        if ($group === 'cucurbit' && $method === 'direct') {
            $say['plant'] = ['Sow the seed', 'Sow 2 to 3 seeds per hill on the moist beds and keep the strongest seedling.'];
        }
        if ($group === 'bulb') {
            if ($method !== 'transplant') {
                $drop[] = 'nursery';
            }
            $say['plant'] = match ($method) {
                'cloves' => ['Plant the cloves', 'Plant clean, firm cloves or sets pointed end up, about 2 cm deep, and water the beds.'],
                'direct' => ['Sow the seed', 'Sow the seed thinly in rows on the beds and water gently.'],
                default => ['Transplant the seedlings', 'Transplant seedlings of about 40 to 45 days, trimmed a little at the roots and leaves, and water right after.'],
            };
        }
        if ($group === 'root' && $method === 'direct') {
            $drop[] = 'material';
            $say['ridges'] = ['Beds or ridges', 'Make fine beds or low ridges so the roots grow straight and long.'];
            $say['plant'] = ['Sow the seed', 'Sow the seed thinly in rows on the moist beds and thin the seedlings once they stand.'];
        }
        if ($group === 'banana') {
            if ($method === 'tissue') {
                $add[] = self::step('harden', -14, -1, 'Harden the plantlets', 'Keep the tissue cultured plantlets in partial shade near the field and water them daily, so they are used to the sun and wind by planting day.', 'extension');
            } else {
                $add[] = self::step('sucker_prep', -7, -1, 'Prepare the suckers', 'Choose sword suckers from healthy mother plants, trim the roots and clean off the old soil, and check the corm for borer holes.', 'extension');
            }
        }
        if ($group === 'tree' && $method === 'seedling') {
            $say['plant'] = ['Plant the seedlings', 'Plant healthy seedlings or seednuts at the start of the rains, firm the soil and water them in.'];
        }
        $out = [];
        foreach ($steps as $s) {
            if (in_array($s['key'], $drop, true)) {
                continue;
            }
            if (isset($say[$s['key']])) {
                [$s['what'], $s['how']] = $say[$s['key']];
            }
            $out[] = $s;
        }
        $out = array_merge($out, $add);
        usort($out, fn ($a, $b) => [$a['from'], $a['to']] <=> [$b['from'], $b['to']]);

        return $out;
    }

    /** One step in the plan's own shape. */
    public static function step(string $key, int $from, int $to, string $what, string $how, string $tag, ?array $follows = null, ?string $reason = null, ?string $why = null): array
    {
        return array_filter([
            'key' => $key,
            'from' => min($from, $to),
            'to' => max($from, $to),
            'what' => $what,
            'how' => $how,
            'tag' => $tag,
            'reason' => $reason,
            'why' => $why,
            'follows' => $follows && isset($follows['step']) ? ['step' => (string) $follows['step'], 'from' => (int) ($follows['from'] ?? 0), 'to' => (int) ($follows['to'] ?? 0)] : null,
            'type' => self::TYPES[$key] ?? 'land_prep',
        ], fn ($v) => $v !== null);
    }

    private static function tagOf(string $basis): string
    {
        return match ($basis) {
            'palaycheck', 'philrice' => 'philrice',
            'irri' => 'irri',
            'suggestion', 'anee' => 'anee',
            default => 'extension',
        };
    }

    private static function slug(string $s): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($s)), '_') ?: 'step';
    }

    /* ================================================== the farmer's answers */

    /**
     * The plan bent by the farmer's answers: steps added, dropped or noted,
     * and the notes that go with them. Returns [steps, notes] where each
     * note is {level: info|warn, text, reason}.
     */
    public static function applyAnswers(array $steps, array $case): array
    {
        $crop = (string) $case['crop'];
        $method = (string) $case['method'];
        $rice = str_starts_with($crop, 'rice');
        $tpr = in_array($method, ['tpr_manual', 'tpr_mechanical'], true);
        $wet = in_array($method, ['dsr_wet_broadcast', 'dsr_wet_drum'], true);
        $dry = $method === 'dsr_dry';
        $s = (array) ($case['soil'] ?? []);
        $h = (array) ($case['history'] ?? []);
        $crew = (array) ($case['crew'] ?? []);
        $notes = [];
        $note = function (string $level, string $text, string $reason) use (&$notes): void {
            $notes[] = ['level' => $level, 'text' => $text, 'reason' => $reason];
        };
        $has = fn (string $k) => (bool) array_filter($steps, fn ($x) => $x['key'] === $k);
        $drop = function (string $k) use (&$steps): void {
            $steps = array_values(array_filter($steps, fn ($x) => $x['key'] !== $k));
        };
        $addNote = function (string $k, string $text) use (&$steps): void {
            foreach ($steps as &$x) {
                if ($x['key'] === $k) {
                    $x['rule'] = trim(($x['rule'] ?? '') . ' ' . $text);
                }
            }
            unset($x);
        };
        $add = function (array $step) use (&$steps, $has): void {
            if (! $has($step['key'])) {
                $steps[] = $step;
            }
        };
        $adds = self::riceAdds();

        // The soil's hardness: soften it before the plow only where it is hard.
        $hard = in_array($s['texture'] ?? '', ['clay', 'heavy_clay'], true) || ($s['hardpan'] ?? '') === 'yes'
            || in_array($h['lastPlowed'] ?? '', ['over_year', 'never'], true);
        if ($has('soften') && ! $hard) {
            $drop('soften');
        }

        /* ---- texture ---- */
        $tex = $s['texture'] ?? '';
        if (in_array($tex, ['clay', 'heavy_clay'], true)) {
            if ($rice && $tpr) {
                $addNote($tpr && $method === 'tpr_manual' ? 'seedbed_site' : 'nursery_site', 'Your soil is clay, so work in the organic fertilizer or carbonized rice hull here.');
            }
            $addNote('soak_field', 'Clay holds its clods: give it a day or two more of soaking.');
            if ($tex === 'heavy_clay') {
                $note('info', 'Heavy clay that cracks dry is slow to plow and harrow, so the crew times are stretched for it. Work it moist, never wet or bone dry.', 'soil');
            }
        }
        if ($tex === 'sandy') {
            if ($rice) {
                $note('info', 'Sandy soil lets water run out fast: puddle it well and pack the dikes firm so the field holds water.', 'soil');
                if ($dry) {
                    $note('warn', 'Dry seeding is not advised on sand or loamy sand: the seed dries out before it roots.', 'soil');
                }
            } else {
                $note('info', 'Sandy soil dries fast and loses fertilizer: work in compost and plan more frequent, smaller waterings.', 'soil');
            }
        }

        /* ---- water ---- */
        $water = $s['water'] ?? '';
        if ($water === 'rainfed') {
            $note('info', 'A rainfed field can only be plowed once the rains soften the soil. If the rains come late, every date moves with them, so watch the first heavy rains.', 'water');
            if ($rice && ! $dry) {
                $addNote('dikes', 'Fix the dikes before the rains so the first water stays in the field.');
            }
        } elseif ($water === 'pump' && $rice) {
            $note('info', 'On a pump, alternate wetting and drying later in the season saves fuel and water without losing yield.', 'water');
        }

        /* ---- drainage ---- */
        $drain = $s['drainage'] ?? '';
        if (in_array($drain, ['slow', 'stands'], true)) {
            if ($wet || $dry) {
                $note('warn', 'Water that stands for days drowns sown seed. Transplanting is safer on this field than direct seeding.', 'soil');
            }
            $add(self::step('drain_canal', -26, -22, 'Cut a drainage canal on the low side', 'Dig a canal along the lowest side of the field and check that the outlet runs free, so you can drain the field within a day when you need to.', 'anee', null, 'soil', 'Water stands on this field.'));
        }

        /* ---- hardpan ---- */
        if (($s['hardpan'] ?? '') === 'yes') {
            if ($rice) {
                $note('info', 'A hardpan under a rice paddy is useful: it holds the water in. Plow above it, do not break it.', 'soil');
            } elseif (! $has('subsoil')) {
                $add(self::step('deep_till', -28, -22, 'Break the hardpan', 'Run a subsoiler or a deep chisel plow across the field before the regular plowing, so roots and water can go down.', 'anee', null, 'soil', 'There is a hard layer under the plow depth.'));
            }
        }

        /* ---- stones, slope, organic matter, colour ---- */
        if (($s['rocks'] ?? '') === 'many') {
            $add(self::step('stones', -24, -20, 'Pick out the stones', 'Walk the plowed field and carry the big stones to the edges before harrowing. They break tines and leave lumps the leveling cannot fix.', 'anee', null, 'soil', 'The field has many stones.'));
        }
        if (($s['slope'] ?? '') === 'steep') {
            if (! $rice) {
                $add(self::step('contour', -21, -15, 'Lay out the rows on the contour', 'Mark the rows across the slope with an A frame, and leave grass strips every few meters so the rain does not carry the soil away.', 'anee', null, 'soil', 'The field is steep.'));
            } else {
                $note('info', 'On a steep field, keep each paddy small and level, with strong dikes between terraces.', 'soil');
            }
        }
        if (($s['organic'] ?? '') === 'low') {
            $note('info', 'Soil low in organic matter holds little water and food. Return the straw or residues and add compost or manure where you can.', 'soil');
        }
        $color = $s['color'] ?? '';
        if ($color === 'gray') {
            $note('info', 'Gray or bluish soil has been waterlogged for long. It needs drainage before an upland crop.', 'soil');
        } elseif ($color === 'red') {
            $note('info', 'Red or yellow soil is often acidic and low in phosphorus. A soil test tells you what it needs.', 'soil');
        }

        /* ---- acidity ---- */
        $acid = $s['acidity'] ?? '';
        $ph = isset($s['ph']) && is_numeric($s['ph']) ? (float) $s['ph'] : null;
        if ($ph !== null) {
            $acid = $ph < 5 ? 'strong' : ($ph <= 5.5 ? 'mild' : ($ph > 7.5 ? 'alkaline' : ($acid === 'untested' ? 'none' : $acid)));
        }
        $limedRecently = ($h['lastLimed'] ?? '') === 'under_year';
        if ($acid === 'untested') {
            $add(self::step('soil_test', -60, -45, 'Test the soil', 'Take a soil sample to the regional soils laboratory or check it with a soil test kit, early enough for the result to come back before plowing.', 'anee', null, 'soil', 'The soil has never been tested.'));
        }
        if ($acid === 'mild') {
            $note('info', $rice ? 'Mildly acidic soil: work the straw and organic fertilizer in early and keep the paddy flooded, which brings the pH up by itself.' : 'Mildly acidic soil: most crops grow, but a little lime by the soil test helps legumes and vegetables.', 'soil');
        }
        if ($acid === 'strong') {
            $add(self::step('soil_test', -60, -45, 'Test the soil', 'Take a soil sample to the regional soils laboratory or check it with a soil test kit, so you know how much lime it takes.', 'anee', null, 'soil', 'The soil is strongly acidic.'));
            $lime = $adds['acidity']['lime'] ?? null;
            $add($lime ? self::step('lime', -31, -30, $lime['what'], $lime['how'] . ' Avoid ammonium sulfate on this soil.', 'anee', $lime['follows'] ?? null, 'soil', 'The soil is strongly acidic.')
                : self::step('lime', -30, -14, 'Apply lime', 'Spread agricultural lime or dolomite at the rate the test gives and work it in.', 'anee', null, 'soil', 'The soil is strongly acidic.'));
            if ($limedRecently) {
                $addNote('lime', 'You limed within the year: test before you lime again.');
            }
        }
        if ($acid === 'sulfate') {
            $add(self::step('keep_wet_soil', -30, -1, 'Keep the soil wet', 'Do not let acid sulfate soil dry out and crack during preparation: drying releases the acid. Lime only as a soil test advises.', 'anee', null, 'soil', 'The soil is acid sulfate.'));
        }
        if ($acid === 'alkaline') {
            $note('info', 'Alkaline or limy soil locks up zinc and iron. Watch the young crop for pale or dusty brown leaves.', 'soil');
        }

        /* ---- salt and sodium ---- */
        $salt = $s['salt'] ?? '';
        if (in_array($salt, ['crust', 'sea', 'tips'], true)) {
            $add(self::step('salt_variety', $tpr ? -35 : -15, $tpr ? -25 : -5, 'Choose a salt tolerant variety', 'Pick a salt tolerant variety before you buy or soak the seed, not at planting time.', 'anee', null, 'soil', 'Salt shows on this field.'));
            $add(self::step('flush_salt', -21, -7, 'Flush the salt', $rice ? 'Flood the field with good water and drain it, two or three times, between plowing and the final harrowing. Keep water standing afterwards so the salt does not climb back.' : 'Flood the field with good water and drain it, two or three times, to wash the salt below the roots, then plant on raised beds.', 'anee', null, 'soil', 'Salt shows on this field.'));
            if ($wet || $dry) {
                $note('warn', 'Young rice is most sensitive to salt: strong transplanted seedlings do better here than direct seeding.', 'soil');
            }
        }
        if ($salt === 'sodic') {
            foreach (LandPrep::SOIL_STEPS['sodic'] as $x) {
                $add(self::step($x[4], $x[0], $x[1], $x[2], $x[3], 'anee', null, 'soil', 'The soil crusts and seals (sodium).'));
            }
        }

        /* ---- history: harvest, rest, previous crop ---- */
        $sowAt = self::sowOffset($steps, $method);
        $ago = ['under2w' => 7, '2to4w' => 21, '1to3m' => 60, 'over3m' => 120, 'not_yet' => null][$h['harvested'] ?? ''] ?? false;
        if ($rice && $ago !== false) {
            $first = self::firstPlanting($case);
            if ($first) {
                $daysToSow = (int) now('Asia/Manila')->startOfDay()->diffInDays(Carbon::parse($first)->addDays($sowAt), false);
                $rest = $ago === null ? null : $ago + $daysToSow;
                if ($ago === null) {
                    $note('warn', 'The last crop is not harvested yet. PhilRice asks for at least 30 days with no rice on the field before sowing: count from the harvest and move the planting day later if it falls short.', 'history');
                } elseif (($h['prevCrop'] ?? '') !== 'no_rice' && str_starts_with((string) ($h['prevCrop'] ?? 'rice'), 'rice') && $rest < 30) {
                    $note('warn', 'Your field gets only about ' . max(0, $rest) . ' days without rice before sowing. PhilRice asks for at least 30: plow the stubble and any ratoon under right away, or plant ' . (30 - max(0, $rest)) . ' days later.', 'history');
                    $addNote('rest', 'Short rest here: plow under the stubble and ratoon at once.');
                }
            }
        }
        $prev = $h['prevCrop'] ?? '';
        if ($prev === 'rice_no_straw' || $prev === 'corn' || $prev === 'legume' || $prev === 'vegetable' || $prev === 'fallow' || $prev === 'new_land') {
            $drop('straw');
        }
        if ($prev === 'corn') {
            $addNote('plow', 'Chop the corn or sorghum stalks first and plow a week early, so the tough residue rots.');
        }
        if ($prev === 'legume') {
            $note('info', 'After a legume the soil keeps some nitrogen, so the first nitrogen dose can be lighter.', 'history');
        }
        if ($prev === 'vegetable') {
            $add(self::step('clean_field', -35, -31, 'Clear the old vegetable field', 'Pull out the plastic mulch, stakes and trellis wire before plowing. Plastic left in the soil blocks water and roots for years.', 'anee', null, 'history', 'Vegetables grew here last.'));
        }
        if (($h['stubble'] ?? '') === 'ratoon' && $rice) {
            $addNote('rest', 'Ratoon or stray rice is growing: plow it under now so no rice grows through the rest period.');
        }
        if (in_array($h['newLand'] ?? '', ['idle', 'new'], true) || $prev === 'new_land') {
            foreach (['clear_land', 'first_plow', 'rough_level'] as $k) {
                $x = $adds['new_land'][$k] ?? null;
                if ($x) {
                    $add(self::step($k, (int) $x['from'], (int) $x['to'], $x['what'], $rice ? $x['how'] : str_replace(' under water', '', $x['how']), 'anee', null, 'history', 'The field is newly opened or was idle.'));
                }
            }
            $note('info', 'A new field has no hardpan yet and leaks water for a season or two. Expect more weeds the first season' . ($rice ? ', and transplant rather than direct seed.' : '.'), 'history');
        }
        if (in_array($h['lastPlowed'] ?? '', ['over_year', 'never'], true) && ! $has('first_plow')) {
            $addNote('plow', 'Not plowed in over a year: expect hard ground and tough roots. Soak it first or plow twice.');
        }

        /* ---- flooding ---- */
        if (in_array($h['flooding'] ?? '', ['sometimes', 'often'], true)) {
            if ($method === 'tpr_manual') {
                $x = $adds['flood_history']['backup_seedbed'] ?? null;
                if ($x) {
                    $add(self::step('backup_seedbed', -14, -14, $x['what'], $x['how'], 'anee', $x['follows'] ?? null, 'history', 'The field floods in the wet season.'));
                }
            }
            if ($rice) {
                $note('warn', 'For a field that floods, choose a submergence tolerant (Sub1) variety whose harvest escapes the flood months' . ($wet || $dry ? ', and transplant rather than direct seed.' : '.'), 'history');
            } else {
                $note('warn', 'This field floods: use high beds or ridges, and keep the drainage canals open through the season.', 'history');
            }
        }

        /* ---- weeds ---- */
        $weeds = $h['weeds'] ?? '';
        if ($weeds === 'many' && $rice && ! $dry) {
            $addNote('soak_field', 'Many weeds last season: give the field the full 7 days or more with water in between each pass.');
        }
        if ($weeds === 'weedy_rice' && $rice) {
            $x = $adds['weed_history']['stale_round'] ?? null;
            if ($x && ! $dry) {
                $add(self::step('stale_round', -8, -4, $x['what'], $x['how'], 'anee', $x['follows'] ?? null, 'history', 'Weedy rice or heavy sedges last season.'));
            }
            $note('warn', 'Weedy rice: ' . ($dry ? 'do not dry seed this field.' : ($wet ? 'transplant instead of direct seeding.' : 'transplanting is the right choice.')) . ' Use clean certified seed and clean the tractor, tools, canals and dikes so no weed seed rides in.', 'history');
        }
        if ($weeds === 'tough' && ! $rice) {
            $note('info', 'Tough weeds like cogon or nutsedge regrow from their roots: plow, let them sprout, and plow or harrow again before planting.', 'history');
        }

        /* ---- snails, rats, birds, pests ---- */
        if ($rice && ($h['snails'] ?? '') === 'many') {
            $x = $adds['snail_history']['snail_screens'] ?? null;
            if ($x) {
                $add(self::step('snail_screens', -3, -1, $x['what'], $x['how'], 'philrice', null, 'history', 'Many golden apple snails.'));
            }
            if ($tpr && ! $has('canals')) {
                $add(self::step('canals', -1, 0, 'Cut the small canals', 'Right after leveling, cut small canals about 25 cm wide and 5 cm deep along the dikes and through the middle. Snails gather there and are easy to pick.', 'philrice', null, 'history', 'Many golden apple snails.'));
            }
            $note('info', 'Many snails: keep the soil just saturated, not flooded, up to about 15 days after transplanting or 20 to 25 days after sowing' . ($tpr ? ', and transplant at the older end of the seedling age.' : '.'), 'history');
        }
        $rb = $h['ratsBirds'] ?? '';
        if (in_array($rb, ['rats', 'both'], true)) {
            $note('info', 'Rats: keep the dikes clean and no bigger than 15 by 20 cm, flood or dig out the burrows, and plant together with your neighbors. Join community baiting with sealed bait packs only.', 'history');
        }
        if (in_array($rb, ['birds', 'both'], true)) {
            $note('info', 'Birds: plan someone to chase them in the first week after sowing, and do not sow far ahead of or behind your neighbors.', 'history');
        }
        $pest = $h['pests'] ?? '';
        if ($rice && in_array($pest, ['tungro', 'bph', 'blast'], true)) {
            $note('warn', 'After ' . ['tungro' => 'tungro', 'bph' => 'brown planthopper', 'blast' => 'blast'][$pest] . ' the 30 day rest and planting together with your area are firm rules, and a resistant variety pays.'
                . ($pest === 'bph' ? ' Do not broadcast seed, transplant instead.' : '') . ($pest === 'blast' ? ' Plow the infected stubble under right after harvest.' : ''), 'history');
        }

        /* ---- seed source, field level, zinc ---- */
        if ($rice && in_array($h['seedSource'] ?? '', ['own', 'old'], true) && ! $dry) {
            $x = $adds['seed_source']['seed_test'] ?? null;
            if ($x) {
                $add(self::step('seed_test', -37, -27, $x['what'], $x['how'], 'philrice', $x['follows'] ?? null, 'seed', 'The seed is home kept.'));
            }
        }
        if ($rice && in_array($h['level'] ?? '', ['some', 'uneven'], true) && ! $dry) {
            $x = $adds['field_level']['extra_level'] ?? null;
            if ($x) {
                $add(self::step('extra_level', -7, -4, $x['what'], $x['how'], 'philrice', $x['follows'] ?? null, 'history', 'Some spots stayed dry while others held deep water.'));
            }
            if (($h['level'] ?? '') === 'uneven' && ($wet || $dry)) {
                $note('warn', 'A very uneven field drowns sown seed in its low spots. Level it well, or think of laser leveling, before direct seeding.', 'history');
            }
        }
        $zinc = $h['zinc'] ?? '';
        if ($rice && in_array($zinc, ['mild', 'severe'], true)) {
            $txt = $zinc === 'severe' ? 'Zinc shortage was bad here: use 2 kg of zinc sulfate on the seedbed, or dip the seedling roots in a 2 percent zinc oxide mix before transplanting.' : 'A mild zinc shortage: 0.5 kg of zinc sulfate on the seedbed is enough.';
            $addNote($method === 'tpr_mechanical' ? 'nursery_zinc' : 'seedbed_zinc', $txt);
            if ($wet) {
                $addNote('seed_soak', 'Zinc was short here: add 1 kg of zinc sulfate for every 40 kg of seed to the last soaking water.');
            }
        }

        /* ---- the crew's machine ---- */
        $eq = $crew['equipment'] ?? '';
        if ($eq === 'hired') {
            $x = $adds['power']['book_service'] ?? null;
            $add(self::step('book_service', -37, -37, $x['what'] ?? 'Book the tractor', $x['how'] ?? 'Call the operator and fix your plowing and harrowing days.', 'anee', $x['follows'] ?? ['step' => 'plow', 'from' => -7, 'to' => -7], 'crew', 'Operators are busiest when the whole area plants together.'));
        }
        if ($eq === 'carabao') {
            $note('info', 'With a carabao each pass takes longer, so the plowing starts at the early end of its window.', 'crew');
        }

        usort($steps, fn ($a, $b) => [$a['from'], $a['to']] <=> [$b['from'], $b['to']]);

        return [array_values($steps), $notes];
    }

    /** The rice book's "adds", by question then step key. */
    private static function riceAdds(): array
    {
        $out = [];
        foreach ((array) (self::riceBook()['soilQuestions'] ?? []) as $q) {
            foreach ((array) ($q['adds'] ?? []) as $a) {
                $out[$q['key']][$a['key']] = $a;
            }
        }

        return $out;
    }

    /** The day the seed is sown, against the planting day: the seedbed for transplanted crops, the planting day otherwise. */
    public static function sowOffset(array $steps, string $method): int
    {
        foreach ($steps as $s) {
            if (in_array($s['key'], ['sow_seedbed', 'sow_nursery', 'nursery'], true)) {
                return (int) $s['from'];
            }
        }

        return 0;
    }

    private static function firstPlanting(array $case): ?string
    {
        $dates = array_filter(array_map(fn ($l) => $l['planting'] ?? null, (array) ($case['lots'] ?? [])));
        sort($dates);

        return $dates[0] ?? null;
    }

    /* =================================================== seed preparation */

    /** A name as the matcher compares it: lower case, no spaces, dashes or dots. */
    public static function norm(?string $s): string
    {
        return preg_replace('/[\s\-\._]+/u', '', mb_strtolower(trim((string) $s)));
    }

    /**
     * The brand entry for a rice variety: the whole name, its local name or a
     * trade name on a brand's match list (never a part of one: Mestiso 1
     * must not match Mestiso 117, and the bare word Mestiso matches nothing),
     * then a brand word as a whole word, then the breeder. Null when none.
     */
    public static function brandFor(?array $record, string $typed = ''): ?array
    {
        $names = array_filter(array_merge(
            [$typed, $record['name'] ?? null, $record['localName'] ?? null],
            (array) ($record['tradeNames'] ?? [])
        ), fn ($n) => is_string($n) && trim($n) !== '' && self::norm($n) !== 'mestiso' && self::norm($n) !== 'mestizo');
        $keys = array_map([self::class, 'norm'], $names);
        $brands = (array) (self::seedBook()['brands'] ?? []);
        foreach ($brands as $b) {
            foreach ((array) ($b['match'] ?? []) as $m) {
                if (in_array(self::norm($m), $keys, true)) {
                    return $b;
                }
            }
        }
        $hay = ' ' . mb_strtolower(implode(' ', $names)) . ' ';
        foreach ($brands as $b) {
            foreach ((array) ($b['brandWords'] ?? []) as $w) {
                if ($w !== '' && preg_match('/(^|[^a-z0-9])' . preg_quote(mb_strtolower($w), '/') . '([^a-z0-9]|$)/u', $hay)) {
                    return $b;
                }
            }
        }
        $breeder = self::norm($record['breeder'] ?? '');
        if ($breeder !== '') {
            foreach ($brands as $b) {
                foreach ((array) ($b['breeders'] ?? []) as $br) {
                    if (self::norm($br) === $breeder) {
                        return $b;
                    }
                }
            }
        }

        return null;
    }

    /** "SL Agritech", "Longping": a seed company's short name. */
    public static function brandName(string $brand): string
    {
        $b = trim(preg_split('/\s*[(:]/', $brand)[0]);

        return ['Longping Tropical Rice Development' => 'Longping', 'PhilRice public hybrids' => 'PhilRice', 'SeedWorks US Agriseeds' => 'SeedWorks',
            'Bayer Arize' => 'Bayer (Arize)', 'Pioneer' => 'Pioneer (Corteva)'][$b] ?? $b;
    }

    /** A rice variety record by its name, local name or trade name (whole names only). */
    public static function riceRecord(string $name): ?array
    {
        $k = self::norm($name);
        if ($k === '' || $k === 'mestiso' || $k === 'mestizo') {
            return null;
        }
        foreach (RiceVarieties::all() as $v) {
            $all = array_merge([$v['name'] ?? '', $v['localName'] ?? ''], (array) ($v['tradeNames'] ?? []));
            foreach ($all as $n) {
                if (is_string($n) && $n !== '' && self::norm($n) === $k) {
                    return $v;
                }
            }
        }

        return null;
    }

    /**
     * How to prepare the seed for this crop, method and variety, with the
     * hours, and how sure the source is. `applies` false when nothing is
     * soaked (dry seeding, cuttings, suckers); `found` false when nobody
     * researched it, and then `say` tells the farmer to follow the seed bag.
     */
    public static function seedPrep(string $crop, string $method, string $variety, string $seedType): array
    {
        $label = CropCatalog::label($crop);
        if (! str_starts_with($crop, 'rice')) {
            $material = in_array($method, ['cuttings', 'cloves', 'setts', 'suckers', 'tissue', 'grafted'], true);

            return ['applies' => ! $material, 'found' => false, 'source' => 'none',
                'say' => $material ? 'No seed to prepare: the planting material steps are in the plan.'
                    : 'There is no researched seed preparation for ' . ($variety !== '' ? $variety : strtolower($label)) . '. Follow the instructions on the seed bag or from the seller.'];
        }
        if ($method === 'dsr_dry') {
            return ['applies' => false, 'found' => true, 'source' => 'method', 'say' => 'Dry seeded rice is sown dry. Do not soak it.'];
        }
        $book = self::seedBook();
        $record = $variety !== '' ? self::riceRecord($variety) : null;
        $type = $record ? (($record['type'] ?? '') === 'hybrid' ? 'hybrid' : 'inbred') : ($seedType === 'hybrid' ? 'hybrid' : ($seedType === 'inbred' ? 'inbred' : ''));
        $brand = ($variety !== '' && ($type === 'hybrid' || $type === '')) ? self::brandFor($record, $record ? '' : $variety) : null;
        if ($brand && $type === '') {
            $type = 'hybrid';
        }
        $direct = in_array($method, ['dsr_wet_broadcast', 'dsr_wet_drum'], true);
        if ($brand) {
            $src = $brand;
            $source = 'brand';
            $general = ($brand['confidence'] ?? '') === 'general';
        } elseif ($type === 'hybrid') {
            $src = $book['general']['hybrid'] ?? [];
            $source = 'general';
            $general = true;
        } elseif ($type === 'inbred') {
            $src = $book['general']['inbred'] ?? [];
            $source = 'philrice';
            $general = false;
        } else {
            return ['applies' => true, 'found' => false, 'source' => 'none', 'type' => '',
                'say' => 'Tell us whether the seed is inbred or hybrid to get its soaking hours. Until then, follow the instructions on the seed bag or from the seller.'];
        }
        $refresh = $src['refreshEveryHours'] ?? null;
        $refreshNote = null;
        if ($refresh === null) {
            $refreshNote = 'The seed company gives no water change schedule. In still water, PhilRice advises changing it every 6 hours.';
        }
        // Seed per hectare: PalayCheck's for inbred seed, the brand's own for a hybrid.
        $rates = (array) ($src['seedKgPerHa'] ?? []);
        $rate = match (true) {
            $type === 'inbred' && $method === 'dsr_wet_broadcast' => [60, 80],
            $type === 'inbred' && $method === 'dsr_wet_drum' => [40, 60],
            $type === 'inbred' && $method === 'tpr_manual' => [40, 40],
            $type === 'inbred' => [20, 40],
            $direct => $rates['directSeeded'] ?? null,
            default => $rates['transplanted'] ?? [15, 18],
        };
        $name = $brand ? self::brandName((string) $brand['brand']) : null;
        $say = match (true) {
            $source === 'brand' && ! $general && $name === 'PhilRice' => 'From PhilRice\'s own instructions for its hybrids.',
            $source === 'brand' && ! $general => 'From the seed company\'s own instructions (' . $name . ').',
            $source === 'brand' => 'No instruction from ' . $name . ' was found, so this is the general PhilRice practice for hybrid seed. The instructions on your seed bag come first.',
            $source === 'general' => 'This is the general PhilRice practice for hybrid seed. The instructions on your seed bag come first.',
            default => 'PhilRice practice for inbred seed.',
        };

        return [
            'applies' => true, 'found' => true, 'source' => $source, 'type' => $type,
            'brand' => $name, 'confidence' => $general ? 'general' : 'company',
            'record' => $record ? ['name' => $record['name'], 'local' => $record['localName'] ?? '', 'type' => $type, 'days' => (int) round((float) ($record['maturity'] ?? 0)) ?: null] : null,
            'soakHours' => (int) ($src['soakHours'] ?? 24), 'soakRange' => array_map('intval', (array) ($src['soakRange'] ?? [24, 24])),
            'refreshEveryHours' => $refresh !== null ? (int) $refresh : null, 'refreshPlanHours' => $refresh !== null ? (int) $refresh : 6, 'refreshNote' => $refreshNote,
            'incubateHours' => (int) ($src['incubateHours'] ?? 24), 'incubateRange' => array_map('intval', (array) ($src['incubateRange'] ?? [24, 36])),
            'sowWhen' => (string) ($src['sowWhen'] ?? ''),
            'steps' => array_values(array_filter((array) ($src['steps'] ?? []), 'is_string')),
            'seedKgPerHa' => $rate,
            'rateNote' => $rate ? null : 'No direct seeding rate for this hybrid was found. Ask the seller how much to sow per hectare.',
            'seedbed' => (string) ($src['seedbed'] ?? ''),
            'transplantAgeDays' => $src['transplantAgeDays'] ?? null,
            'floatRule' => $type === 'hybrid' ? 'Never throw away hybrid seeds that float. They are lighter but they still grow.' : null,
            'say' => $say,
        ];
    }

    /**
     * The seed preparation as timed reminders before a sowing day: start
     * the soak, change the water, drain and incubate, start checking, sow.
     * Sowing is planned for 7 in the morning.
     */
    public static function seedTimeline(array $prep, string $sowDate): array
    {
        if (empty($prep['found']) || empty($prep['applies']) || ! isset($prep['soakHours'])) {
            return [];
        }
        $sow = Carbon::parse($sowDate . ' 07:00:00', 'Asia/Manila');
        $inc = (int) $prep['incubateHours'];
        $soak = (int) $prep['soakHours'];
        $incStart = $sow->copy()->subHours($inc);
        $soakStart = $incStart->copy()->subHours($soak);
        $ev = [['at' => $soakStart->format('Y-m-d H:i'), 'kind' => 'soak', 'what' => 'Start soaking the seed', 'how' => 'Soak for ' . $soak . ' hours' . ($prep['soakRange'][0] !== $prep['soakRange'][1] ? ' (' . $prep['soakRange'][0] . ' to ' . $prep['soakRange'][1] . ' hours is fine)' : '') . ' in clean water, in a sack or net bag filled only halfway.']];
        $every = (int) ($prep['refreshPlanHours'] ?? 0);
        if ($every > 0) {
            for ($t = $soakStart->copy()->addHours($every); $t->lt($incStart); $t->addHours($every)) {
                $ev[] = ['at' => $t->format('Y-m-d H:i'), 'kind' => 'water', 'what' => 'Change the water', 'how' => $prep['refreshEveryHours'] !== null ? 'Drain the old water and pour in clean water, every ' . $every . ' hours while the seed soaks. Flowing water needs no change.' : 'PhilRice advises changing still water every 6 hours. Flowing water needs no change.'];
            }
        }
        $ev[] = ['at' => $incStart->format('Y-m-d H:i'), 'kind' => 'incubate', 'what' => 'Drain and start incubating', 'how' => 'Lift the bags onto a raised pallet in the shade, never the bare floor, cover them and keep them damp and aired. Do not stack the bags.'];
        $ev[] = ['at' => $sow->format('Y-m-d H:i'), 'kind' => 'sow', 'what' => 'Check the sprouts and sow', 'how' => ($prep['sowWhen'] ?: 'Sow when most seeds show a small white root.') . ' If they are not ready, wait: up to ' . $prep['incubateRange'][1] . ' hours of incubation is normal, longer in cool weather.'];

        return $ev;
    }

    /* ============================================================ the crew */

    /** Work for one step on one lot: [kind machine|manual|null, units]. */
    private static function workFor(string $key, float $area, array $crew, array $case, ?array $own = null): array
    {
        $w = $own ?? (self::WORK[$key] ?? null);
        if (! $w) {
            return [null, 0];
        }
        $eq = $crew['equipment'] ?? 'hand_tractor';
        $slow = 1.0;
        $tex = $case['soil']['texture'] ?? '';
        $slow *= ['clay' => 1.15, 'heavy_clay' => 1.35, 'sandy' => 0.85][$tex] ?? 1.0;
        if (($case['soil']['rocks'] ?? '') === 'many') {
            $slow *= 1.25;
        }
        if ($w[0] === 'machine') {
            $rates = self::MACHINE_RATES[$eq] ?? self::MACHINE_RATES['hand_tractor'];
            $units = (float) ($rates[$w[1]] ?? 1) * $area * $slow;
            if ($key === 'first_plow') {
                $units *= 1.5;
            }

            return [$eq === 'hand' ? 'manual' : 'machine', round(max($eq === 'hand' ? 0.5 : 0.25, $units), 2)];
        }

        return ['manual', round(max(0.25, (float) $w[1] * $area * ($w[1] >= 5 ? $slow : 1)), 2)];
    }

    /**
     * Lay every lot's crew work on the calendar.
     *
     * $lots: [{name, area (ha), planting (Y-m-d)}]; $crew: {workers,
     * equipment, machines, daysPerWeek, stagger}. Returns per lot each step's
     * dates, the crew calendar day by day, the conflicts, the planting days
     * and any planting day moved.
     */
    public static function schedule(array $steps, array $lots, array $crew, array $case, ?string $today = null): array
    {
        $today = $today ?: now('Asia/Manila')->toDateString();
        $plantings = array_map(fn ($l) => (string) $l['planting'], $lots);
        $asked = $plantings;
        $shift = array_fill(0, count($lots), 0);
        $run = function (array $plantings, ?array $only = null) use ($steps, $lots, $crew, $case, $today): array {
            $ls = [];
            $ps = [];
            foreach ($lots as $i => $l) {
                if ($only === null || in_array($i, $only, true)) {
                    $ls[] = $l + ['idx' => $i];
                    $ps[] = $plantings[$i];
                }
            }
            $r = self::simulate($steps, $ls, $ps, $crew, $case, $today);
            if ($r['conflicts']) {
                // A tight crew may start the work that waits for nobody (dikes, clearing) a little early.
                $early = self::simulate($steps, $ls, $ps, $crew, $case, $today, true);
                if (array_sum($early['late']) < array_sum($r['late'])) {
                    $r = $early;
                }
            }

            return $r;
        };
        /* Staggering, when the farmer allows it: lot by lot in planting
           order, each takes the smallest move (never more than 45 days)
           that lets the crew finish it and every lot before it on time, or
           the move that leaves the least lateness when none does. */
        if (! empty($crew['stagger']) && count($lots) > 0) {
            $order = array_keys($lots);
            usort($order, fn ($a, $b) => [$asked[$a], $a] <=> [$asked[$b], $b]);
            $placed = [];
            foreach ($order as $i) {
                $placed[] = $i;
                $best = 0;
                $bestLate = null;
                foreach ([0, 1, 2, 3, 4, 5, 7, 10, 14, 21, 30, 45] as $by) {
                    $try = $plantings;
                    $try[$i] = Carbon::parse($asked[$i])->addDays($by)->toDateString();
                    $late = array_sum($run($try, $placed)['late']);
                    if ($bestLate === null || $late < $bestLate) {
                        $best = $by;
                        $bestLate = $late;
                    }
                    if ($late === 0) {
                        break;
                    }
                }
                $plantings[$i] = Carbon::parse($asked[$i])->addDays($best)->toDateString();
                $shift[$i] = $best;
            }
        }
        $result = $run($plantings);
        $result['plantings'] = $plantings;
        $result['asked'] = $asked;
        $result['shift'] = $shift;
        unset($result['late']);

        return $result;
    }

    private static function simulate(array $steps, array $lots, array $plantings, array $crew, array $case, string $today, bool $early = false): array
    {
        $d0 = fn (string $iso) => (int) floor(Carbon::parse($iso, 'UTC')->timestamp / 86400);
        $iso = fn (int $n) => gmdate('Y-m-d', $n * 86400);
        $T = $d0($today);
        $W = max(1, (int) ($crew['workers'] ?? 2));
        $eq = (string) ($crew['equipment'] ?? 'hand_tractor');
        $M = $eq === 'hand' ? 0 : max(1, (int) ($crew['machines'] ?? 1));
        $hired = $eq === 'hired';
        $dpw = (int) ($crew['daysPerWeek'] ?? 6);
        $offDay = fn (int $n) => ($dpw <= 5 && in_array((int) gmdate('w', $n * 86400), [0, 6], true)) || ($dpw === 6 && (int) gmdate('w', $n * 86400) === 0);
        $keys = array_column($steps, 'key');

        // The jobs: one per crew step per lot.
        $jobs = [];
        foreach ($lots as $i => $lot) {
            $P = $d0($plantings[$i]);
            $area = max(0.01, (float) $lot['area']);
            foreach ($steps as $s) {
                if (isset($s['lots']) && is_array($s['lots']) && $s['lots'] && ! in_array($lot['idx'] ?? $i, $s['lots'], true)) {
                    continue;
                }
                [$kind, $units] = self::workFor($s['key'], $area, $crew, $case, isset($s['work']) && is_array($s['work']) ? $s['work'] : null);
                if (! $kind || in_array($s['key'], self::PLANT_KEYS, true) || $s['key'] === 'pull') {
                    continue;
                }
                // Work longer than its window starts early enough to end on time.
                $base = $kind === 'machine' ? $units : $units / $W;
                $span = (int) ceil($base) + (($dpw < 7 && $base > 1) ? 1 : 0);
                $es = min($P + (int) $s['from'], $P + (int) $s['to'] - $span + 1);
                $jobs[] = ['lot' => $i, 'key' => $s['key'], 'kind' => $kind, 'units' => $units, 'left' => $units,
                    'es' => $es - ($early ? (self::EARLY[$s['key']] ?? 0) : 0), 'le' => $P + (int) $s['to'], 'start' => null, 'end' => null, 'days' => []];
            }
        }
        $byKey = [];
        foreach ($jobs as $j => $job) {
            $byKey[$job['lot']][$job['key']] = $j;
        }
        $calendar = [];
        if ($jobs) {
            $from = min(array_column($jobs, 'es'));
            $from = max($from, $T);
            $until = max(array_column($jobs, 'le')) + 120;
            for ($d = $from; $d <= $until; $d++) {
                $open = array_filter(array_keys($jobs), fn ($j) => $jobs[$j]['left'] > 0.001);
                if (! $open) {
                    break;
                }
                if ($offDay($d)) {
                    continue;
                }
                // Ready: its window has opened (never before today) and what comes before it is done, with its gap.
                $ready = [];
                foreach ($open as $j) {
                    $job = $jobs[$j];
                    if (max($job['es'], $T) > $d) {
                        continue;
                    }
                    $after = self::AFTER[$job['key']] ?? null;
                    if ($after && in_array($after[0], $keys, true) && isset($byKey[$job['lot']][$after[0]])) {
                        $pre = $jobs[$byKey[$job['lot']][$after[0]]];
                        if ($pre['left'] > 0.001 || $pre['end'] === null || $pre['end'] + $after[1] > $d) {
                            continue;
                        }
                    }
                    $ready[] = $j;
                }
                // Most urgent first, machine and hand work alike; hand work
                // leaves one person for the machine while a machine job waits.
                usort($ready, fn ($a, $b) => [$jobs[$a]['le'], $jobs[$a]['lot'], $jobs[$a]['es']] <=> [$jobs[$b]['le'], $jobs[$b]['lot'], $jobs[$b]['es']]);
                $people = $W;
                $machines = $M;
                $today_items = [];
                $machineWaiting = function () use (&$ready, &$jobs, &$machines, &$taken): bool {
                    if ($machines < 1) {
                        return false;
                    }
                    foreach ($ready as $j) {
                        if ($jobs[$j]['kind'] === 'machine' && empty($taken[$j])) {
                            return true;
                        }
                    }

                    return false;
                };
                $taken = [];
                foreach ($ready as $j) {
                    if ($jobs[$j]['kind'] === 'machine') {
                        if ($machines < 1 || (! $hired && $people < 1)) {
                            continue;
                        }
                        $machines--;
                        if (! $hired) {
                            $people--;
                        }
                        $taken[$j] = true;
                        $use = min(1.0, $jobs[$j]['left']);
                        $jobs[$j]['left'] = round($jobs[$j]['left'] - $use, 3);
                        $jobs[$j]['start'] ??= $d;
                        $jobs[$j]['end'] = $d;
                        $jobs[$j]['days'][] = $d;
                        $today_items[] = ['lot' => $jobs[$j]['lot'], 'key' => $jobs[$j]['key'], 'people' => $hired ? 0 : 1, 'machine' => true];
                        continue;
                    }
                    $taken[$j] = true;
                    $spare = $people - ((! $hired && $machineWaiting()) ? 1 : 0);
                    if ($spare < 1) {
                        continue;
                    }
                    $need = (int) ceil($jobs[$j]['left'] - 0.001);
                    $give = max(1, min($spare, $need));
                    $people -= $give;
                    $jobs[$j]['left'] = round(max(0, $jobs[$j]['left'] - $give), 3);
                    $jobs[$j]['start'] ??= $d;
                    $jobs[$j]['end'] = $d;
                    $jobs[$j]['days'][] = $d;
                    $today_items[] = ['lot' => $jobs[$j]['lot'], 'key' => $jobs[$j]['key'], 'people' => $give, 'machine' => false];
                }
                if ($today_items) {
                    $calendar[$iso($d)] = $today_items;
                }
            }
        }

        // Each lot's steps with their dates.
        $perLot = [];
        $late = [];
        $conflicts = [];
        foreach ($lots as $i => $lot) {
            $P = $d0($plantings[$i]);
            $area = max(0.01, (float) $lot['area']);
            $rows = [];
            $actual = [];
            foreach ($steps as $s) {
                if (isset($s['lots']) && is_array($s['lots']) && $s['lots'] && ! in_array($lot['idx'] ?? $i, $s['lots'], true)) {
                    continue;
                }
                $row = ['key' => $s['key']];
                if (isset($byKey[$i][$s['key']])) {
                    $job = $jobs[$byKey[$i][$s['key']]];
                    $row['crew'] = $job['kind'];
                    $row['units'] = $job['units'];
                    if ($job['start'] !== null) {
                        $row['start'] = $iso($job['start']);
                        $row['end'] = $iso($job['end']);
                        $row['workDays'] = count($job['days']);
                        $actual[$s['key']] = [$job['start'], $job['end']];
                        $lateBy = $job['left'] > 0.001 ? 999 : max(0, $job['end'] - $job['le']);
                        if ($lateBy > 0) {
                            $row['late'] = $lateBy >= 999 ? null : $lateBy;
                            $row['lateWhy'] = $job['le'] < $T ? 'past' : 'crew';
                            $major = in_array($s['key'], self::CRITICAL, true);
                            if ($major) {
                                $late[$i] = max($late[$i] ?? 0, $lateBy >= 999 ? 30 : $lateBy);
                            }
                            $conflicts[] = ['lot' => $i, 'key' => $s['key'], 'late' => $lateBy >= 999 ? null : $lateBy, 'why' => $row['lateWhy'], 'major' => $major];
                        }
                    } else {
                        $row['start'] = null;
                        $late[$i] = max($late[$i] ?? 0, 30);
                        $conflicts[] = ['lot' => $i, 'key' => $s['key'], 'late' => null, 'why' => 'crew', 'major' => true];
                    }
                } elseif (in_array($s['key'], self::PLANT_KEYS, true) || $s['key'] === 'pull') {
                    $row['planting'] = true;
                }
                $rows[$s['key']] = $row + ['from' => $P + (int) $s['from'], 'to' => $P + (int) $s['to']];
            }
            // Anchored steps follow where their anchor actually landed.
            for ($pass = 0; $pass < 3; $pass++) {
                foreach ($steps as $s) {
                    $f = $s['follows'] ?? null;
                    if (! $f || ! isset($rows[$s['key']]) || isset($rows[$s['key']]['crew'])) {
                        continue;
                    }
                    $anchor = $actual[$f['step']] ?? (isset($rows[$f['step']]) ? [$rows[$f['step']]['from'], $rows[$f['step']]['to']] : null);
                    if (! $anchor) {
                        continue;
                    }
                    // Before the anchor counts from its first day, after it from its last.
                    $a = ((int) $f['from'] < 1 ? $anchor[0] : $anchor[1]) + (int) $f['from'];
                    $b = ((int) $f['to'] <= 0 ? $anchor[0] : $anchor[1]) + (int) $f['to'];
                    $rows[$s['key']]['from'] = min($a, $b);
                    $rows[$s['key']]['to'] = max($a, $b);
                    $actual[$s['key']] = [min($a, $b), max($a, $b)];
                }
            }
            $out = [];
            foreach ($rows as $k => $r) {
                $o = ['key' => $k, 'winFrom' => $iso($r['from']), 'winTo' => $iso($r['to'])];
                foreach (['crew', 'units', 'start', 'end', 'workDays', 'late', 'lateWhy', 'planting'] as $f) {
                    if (array_key_exists($f, $r)) {
                        $o[$f] = $r[$f];
                    }
                }
                if (! isset($r['crew']) && $r['to'] < $T) {
                    $o['past'] = true;
                } elseif (! isset($r['crew']) && $r['from'] < $T) {
                    $o['started'] = true;
                }
                $out[] = $o;
            }
            usort($out, fn ($a, $b) => [($a['start'] ?? $a['winFrom']), $a['winTo']] <=> [($b['start'] ?? $b['winFrom']), $b['winTo']]);
            $perLot[] = ['lot' => $i, 'planting' => $plantings[$i], 'steps' => $out, 'area' => $area];
        }

        // The planting days: how many hands each takes.
        $method = (string) ($case['method'] ?? '');
        $group = self::groupFor((string) ($case['crop'] ?? ''));
        $plantDays = [];
        foreach ($lots as $i => $lot) {
            $area = max(0.01, (float) $lot['area']);
            foreach ($steps as $s) {
                if (! in_array($s['key'], array_merge(self::PLANT_KEYS, ['pull']), true)) {
                    continue;
                }
                $rate = self::PLANTING[$s['key'] . ':' . $method] ?? self::PLANTING[$s['key'] . ':' . $group] ?? null;
                if (! $rate) {
                    continue;
                }
                $pd = $d0($plantings[$i]) + (int) $s['from'];
                $plantDays[] = ['lot' => $i, 'key' => $s['key'], 'date' => $iso($pd), 'people' => (int) max(1, ceil($rate[0] * $area)), 'words' => $rate[1]];
            }
        }
        ksort($calendar);

        return ['perLot' => $perLot, 'calendar' => $calendar, 'conflicts' => $conflicts, 'plantDays' => $plantDays, 'late' => $late + array_fill(0, count($lots), 0)];
    }

    /* ======================================== into the Protocol Builder */

    /**
     * The plan as Protocol Builder entries for a protocol counting with
     * `$counters` (its day type's counters, the first is the one that may
     * go below zero): a divider, then one task per step on the protocol's
     * own count. A transplanted plan counts the seedbed as DAS (the sowing is
     * DAS 0) and the field from the transplant (DAT 0); a protocol without
     * DAT keeps one count from its first counter.
     */
    public static function toProtocol(array $report, array $counters, int $planId): array
    {
        $steps = (array) ($report['steps'] ?? []);
        $sowAt = self::sowOffset($steps, (string) ($report['method']['key'] ?? ''));
        $dat = in_array('DAT', $counters, true) && $sowAt < 0;
        $first = $counters[0] ?? 'DAS';
        $word = (string) ($report['dayWord'] ?? 'planting');
        $where = function (int $d) use ($dat, $first, $sowAt): array {
            if ($dat) {
                return $d >= 0 ? ['DAT', $d] : ['DAS', $d - $sowAt];
            }
            if ($first === 'DAS' && $sowAt < 0) {
                return ['DAS', $d - $sowAt];
            }

            return [$first, $d];
        };
        $tagWord = self::TAGS;
        $out = [];
        [$c0, $d0] = $where((int) ($steps[0]['from'] ?? -30));
        $out[] = ['id' => 'lp' . $planId . '_div', 'kind' => 'divider', 'counter' => $c0, 'day' => $d0, 'label' => 'Land preparation', 'color' => 'amber', 'pos' => 0];
        $pos = 10;
        foreach ($steps as $s) {
            [$counter, $day] = $where((int) $s['from']);
            $span = (int) $s['to'] - (int) $s['from'];
            $win = $span > 0 ? 'Any day from ' . self::daysWords((int) $s['from'], $word) . ' to ' . self::daysWords((int) $s['to'], $word) . '.' : ucfirst(self::daysWords((int) $s['from'], $word)) . '.';
            $why = trim(($s['why'] ?? '') . ' ' . ($s['note'] ?? ''));
            $out[] = [
                'id' => 'lp' . $planId . '_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $s['key']),
                'counter' => $counter,
                'day' => max(-365, min(999, $day)),
                'title' => mb_substr((string) $s['what'], 0, 160),
                'subtitle' => mb_substr(($tagWord[$s['tag'] ?? 'extension'] ?? '') . (! empty($s['lotsWords']) ? ' · ' . $s['lotsWords'] : ''), 0, 200),
                'description' => mb_substr(trim((string) $s['how'] . (! empty($s['rule']) ? ' ' . $s['rule'] : '') . ($why !== '' ? "\n\nWhy: " . $why : '')), 0, 4000),
                'type' => self::TYPES[$s['key']] ?? ($s['type'] ?? 'land_prep'),
                'spray' => [],
                'groups' => [],
                'note' => mb_substr($win, 0, 2000),
                'priority' => in_array($s['key'], ['plow', 'level', 'transplant', 'sow', 'sow_seedbed', 'sow_nursery', 'plant', 'seed_soak'], true) ? 'high' : 'medium',
                'workers' => null,
                'pos' => $pos,
            ];
            $pos += 10;
        }

        return $out;
    }

    /** "21 days before transplanting", "the transplanting day", "3 days after transplanting". */
    public static function daysWords(int $d, string $word): string
    {
        return match (true) {
            $d === 0 => 'the ' . $word . ' day',
            $d < 0 => abs($d) . ' ' . (abs($d) === 1 ? 'day' : 'days') . ' before ' . $word,
            default => $d . ' ' . ($d === 1 ? 'day' : 'days') . ' after ' . $word,
        };
    }
}
