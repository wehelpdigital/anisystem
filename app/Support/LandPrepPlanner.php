<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * THE LAND PREP PLANNER'S ARITHMETIC (2026-10-14, round two 2026-10-15).
 *
 * Everything Anee should not be trusted to count is counted here, in PHP:
 *
 *  - the base steps of a plan: rice by planting method from the PalayCheck
 *    System of PhilRice (database/land-prep/rice-methods.json, written in our
 *    own words), the other crops from App\Support\LandPrep::PLANS, each a
 *    window of days before (negative) or after the planting day;
 *  - the rules the farmer's answers trigger, LOT BY LOT since round two
 *    (lots differ: one is clay and floods, the next is sandy and dry): a
 *    soil test and lime for strongly acid soil, a salt flush, a stale
 *    seedbed for weedy rice, a rat campaign, a break for a virus, and so on,
 *    each tagged with its source;
 *  - the seed preparation of the chosen variety: the brand's own soak,
 *    water change and incubation hours (database/land-prep/seedprep.json),
 *    Anee's own research for a variety not on our list, the PhilRice
 *    general practice when no brand instruction exists, and a plain
 *    "follow the seed bag" when nothing was found;
 *  - the crew: every lot's plowing, harrowing, leveling and hand work laid
 *    on the calendar day by day. Each worker has their own work days, so a
 *    day's hands are the workers who work that weekday. Machines are by type
 *    and count (database/land-prep/equipment.json): a step that needs a plow
 *    or a harrow takes any machine able to do it, the fastest first, and an
 *    owned machine needs one of the workers to drive it while a hired one
 *    comes with its driver and gets a "book it" reminder a week ahead.
 *    Where the crew cannot keep up, the late steps are named, or (when the
 *    farmer allows it) the planting days are staggered until they can;
 *  - the time check: how many days the recommended preparation needs before
 *    the planting day, against the days that are left, and the steps a short
 *    run squeezes or misses; and the crew a plan of this size needs.
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

    /** A lot's size as the farmer typed it: unit => [word, hectares per unit]. Only units that convert exactly. */
    public const UNITS = [
        'ha' => ['hectares', 1.0],
        'sqm' => ['square meters', 0.0001],
        'acre' => ['acres', 0.40468564],
    ];

    /** The other troubles of a last season (rats, flood damage, a zinc shortage): see FieldProfile::OTHER. */
    public const OTHER_PROBLEMS = FieldProfile::OTHER;

    /**
     * Who does a step and how much of it there is: [kind, measure]. A
     * machine step names the kind of work (a machine's rate for it is in
     * equipment.json), a manual one person days per hectare.
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
        'sucker_prep' => ['manual', 1], 'harden' => ['manual', 0.5], 'rat_campaign' => ['manual', 1], 'host_clear' => ['manual', 1.5],
    ];

    /** Person days per hectare for machine work done by hand, when no machine on the list can do it. */
    private const HAND_RATES = ['plow' => 30, 'harrow' => 15, 'harrowlevel' => 22, 'level' => 8, 'roughlevel' => 16, 'light' => 6, 'furrow' => 10];

    /** The old one-machine crew (before round two), as the machines it meant. */
    private const LEGACY_FLEET = [
        'hand_tractor' => ['hand_tractor'], 'tractor' => ['tractor_rotavator'], 'carabao' => ['carabao_plow', 'carabao_harrow'],
        'hired' => ['hand_tractor'], 'hand' => [],
    ];

    /** The old one-machine choices, for plans saved before round two. */
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
        'holes' => 14, 'contour' => 7, 'snail_screens' => 4, 'drainage' => 14, 'nursery_fill' => 2, 'seedbed_site' => 3, 'rat_campaign' => 7, 'host_clear' => 7,
    ];

    /** Steps that are no field work, so they do not count toward the days a preparation needs. */
    private const NOT_WORK = ['rest', 'calendar', 'soil_test', 'salt_variety', 'seed_test', 'book_service'];

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
        'guard' => 'monitoring', 'rest' => 'monitoring', 'snail_screens' => 'monitoring', 'rat_campaign' => 'monitoring',
        'calendar' => 'other', 'book_service' => 'other', 'soil_test' => 'service', 'trellis' => 'other',
    ];

    /** Steps that are the planting itself, by method family. */
    private const PLANT_KEYS = ['transplant', 'sow', 'plant'];

    /** A step's activity type on a season's board (2026-10-15: plans made into seasons). */
    public static function typeOf(string $key, ?string $fallback = null): string
    {
        $t = self::TYPES[$key] ?? ($fallback ?: 'land_prep');

        return isset(\App\Models\AsScheduleActivity::ACTIVITY_TYPES[$t]) ? $t : 'land_prep';
    }

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

    /** database/land-prep/equipment.json: the machines and animals, their work rates and photo credits. */
    public static function equipmentBook(): array
    {
        return self::read('equipment.json');
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

    /** Whether a crop counts as a grain for the "grain" troubles (lodging, zinc). */
    private static function isGrain(string $crop): bool
    {
        return str_starts_with($crop, 'rice') || str_starts_with($crop, 'corn') || in_array($crop, ['sorghum', 'sugarcane'], true);
    }

    /* ========================================================== the machines */

    /** The machines and animals a crop and method can use: key => item (name, local, line, rates, plants, photo). */
    public static function equipmentFor(string $crop, string $method): array
    {
        $rice = str_starts_with($crop, 'rice');
        $out = [];
        foreach ((array) (self::equipmentBook()['items'] ?? []) as $k => $e) {
            $for = $e['for'] ?? 'all';
            $ok = match (true) {
                is_array($for) => in_array($method, $for, true),
                $for === 'rice_wet' => $rice && $method !== 'dsr_dry',
                default => true,
            };
            if ($ok) {
                $out[$k] = $e;
            }
        }

        return $out;
    }

    /** A machine's name for a sentence: "hand tractor", "four wheel tractor with a rotavator". */
    public static function machineWord(string $type): string
    {
        $name = (string) (self::equipmentBook()['items'][$type]['name'] ?? $type);

        return $type === 'pagulong' ? $name : lcfirst($name);
    }

    /**
     * The crew in its round two shape, from either shape: workers as
     * [{name, days: [0..6, Sunday 0]}], the fleet as [{type, own, hired}],
     * and whether planting days may be staggered. A plan saved before round
     * two (workers as a number, one machine, days a week) reads the same.
     *
     * Since 2026-10-16 (the owner) a worker may carry their pay (pay, payUnit
     * day or half) and the roster person they are (globalId, Global
     * Workers), and a hired machine its service price (cost, costUnit ha,
     * day or job) and who is hired from the Contact List (hire {contactId,
     * name, phone}). All optional; costs() turns them into money.
     */
    public static function crew(array $c): array
    {
        $workers = [];
        if (is_array($c['workers'] ?? null)) {
            foreach (array_slice(array_values($c['workers']), 0, 100) as $i => $w) {
                $days = array_values(array_unique(array_filter(array_map('intval', (array) ($w['days'] ?? [])), fn ($d) => $d >= 0 && $d <= 6)));
                sort($days);
                if (! $days) {
                    continue;
                }
                $row = ['name' => mb_substr(trim((string) ($w['name'] ?? '')), 0, 40) ?: 'Worker ' . ($i + 1), 'days' => $days];
                $pay = is_numeric($w['pay'] ?? null) ? round(max(0, min(1000000, (float) $w['pay'])), 2) : 0.0;
                if ($pay > 0) {
                    $row['pay'] = $pay;
                    $row['payUnit'] = ($w['payUnit'] ?? 'day') === 'half' ? 'half' : 'day';
                }
                if ((int) ($w['globalId'] ?? 0) > 0) {
                    $row['globalId'] = (int) $w['globalId'];
                }
                $workers[] = $row;
            }
        } else {
            $n = max(1, min(200, (int) ($c['workers'] ?? 2)));
            $dpw = (int) ($c['daysPerWeek'] ?? 6);
            $days = $dpw >= 7 ? [0, 1, 2, 3, 4, 5, 6] : ($dpw <= 5 ? [1, 2, 3, 4, 5] : [1, 2, 3, 4, 5, 6]);
            for ($i = 0; $i < $n; $i++) {
                $workers[] = ['name' => 'Worker ' . ($i + 1), 'days' => $days];
            }
        }
        if (! $workers) {
            $workers[] = ['name' => 'Worker 1', 'days' => [1, 2, 3, 4, 5, 6]];
        }
        $items = (array) (self::equipmentBook()['items'] ?? []);
        $fleet = [];
        if (is_array($c['fleet'] ?? null)) {
            foreach ($c['fleet'] as $f) {
                $t = (string) ($f['type'] ?? '');
                $own = max(0, min(20, (int) ($f['own'] ?? 0)));
                $hired = max(0, min(20, (int) ($f['hired'] ?? 0)));
                if (isset($items[$t]) && $own + $hired > 0 && ! isset($fleet[$t])) {
                    $fleet[$t] = ['type' => $t, 'own' => $own, 'hired' => $hired];
                    if ($hired > 0) {
                        $cost = is_numeric($f['cost'] ?? null) ? round(max(0, min(10000000, (float) $f['cost'])), 2) : 0.0;
                        if ($cost > 0) {
                            $fleet[$t]['cost'] = $cost;
                            $fleet[$t]['costUnit'] = in_array($f['costUnit'] ?? '', ['ha', 'day', 'job'], true) ? $f['costUnit'] : 'ha';
                        }
                        $hire = is_array($f['hire'] ?? null) ? $f['hire'] : [];
                        $who = mb_substr(trim((string) ($hire['name'] ?? '')), 0, 80);
                        if ($who !== '') {
                            $fleet[$t]['hire'] = ['contactId' => (int) ($hire['contactId'] ?? 0) ?: null, 'name' => $who,
                                'phone' => mb_substr(trim((string) ($hire['phone'] ?? '')), 0, 40)];
                        }
                    }
                }
            }
        } elseif (isset($c['equipment'])) {
            $m = max(1, (int) ($c['machines'] ?? 1));
            $hired = $c['equipment'] === 'hired';
            foreach (self::LEGACY_FLEET[$c['equipment']] ?? [] as $t) {
                $fleet[$t] = ['type' => $t, 'own' => $hired ? 0 : $m, 'hired' => $hired ? $m : 0];
            }
        }

        return ['workers' => $workers, 'fleet' => array_values($fleet), 'stagger' => filter_var($c['stagger'] ?? false, FILTER_VALIDATE_BOOLEAN)];
    }

    /** The crew in words: "4 workers, 1 hand tractor and 1 hired four wheel tractor with a rotavator". */
    public static function crewWords(array $crew): array
    {
        $parts = [];
        foreach ($crew['fleet'] as $f) {
            $w = self::machineWord($f['type']);
            if ($f['own'] > 0) {
                $parts[] = $f['own'] . ' ' . $w;
            }
            if ($f['hired'] > 0) {
                $parts[] = $f['hired'] . ' hired ' . $w;
            }
        }
        $machines = $parts ? (count($parts) > 1 ? implode(', ', array_slice($parts, 0, -1)) . ' and ' . end($parts) : $parts[0]) : 'no machine, all by hand';
        $n = count($crew['workers']);
        $byDays = [];
        foreach ($crew['workers'] as $w) {
            $byDays[self::daysLabel($w['days'])][] = 1;
        }
        $days = count($byDays) === 1 ? array_key_first($byDays) : implode(', ', array_map(fn ($k, $v) => count($v) . ' ' . $k, array_keys($byDays), $byDays));

        return ['workers' => $n . ' ' . ($n === 1 ? 'worker' : 'workers'), 'machines' => $machines, 'days' => $days];
    }

    /** "Mon to Sat", "every day", "Mon, Wed, Fri". */
    public static function daysLabel(array $days): string
    {
        $names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        sort($days);
        if (count($days) === 7) {
            return 'every day';
        }
        if ($days === [1, 2, 3, 4, 5, 6]) {
            return 'Mon to Sat';
        }
        if ($days === [1, 2, 3, 4, 5]) {
            return 'Mon to Fri';
        }

        return implode(', ', array_map(fn ($d) => $names[$d], $days));
    }

    /** The machine units of a fleet that do land preparation, one per machine: [{type, hired, rates}]. */
    private static function units(array $fleet): array
    {
        $items = (array) (self::equipmentBook()['items'] ?? []);
        $out = [];
        foreach ($fleet as $f) {
            $rates = array_filter((array) ($items[$f['type']]['rates'] ?? []), fn ($r) => is_numeric($r) && $r > 0);
            if (! $rates) {
                continue;   // a planting machine does no land preparation
            }
            for ($i = 0; $i < (int) $f['own']; $i++) {
                $out[] = ['type' => $f['type'], 'hired' => false, 'rates' => $rates];
            }
            for ($i = 0; $i < (int) $f['hired']; $i++) {
                $out[] = ['type' => $f['type'], 'hired' => true, 'rates' => $rates];
            }
        }

        return $out;
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

    /* ==================================================== the whole plan */

    /**
     * A case in round two's shape from either shape: the crew normalised,
     * and every lot carrying its own soil and history (a plan saved before
     * round two had one set for the whole farm, so every lot shares it).
     */
    public static function normalize(array $case): array
    {
        $case['crew'] = self::crew((array) ($case['crew'] ?? []));
        $soil = (array) ($case['soil'] ?? []);
        $hist = (array) ($case['history'] ?? []);
        $case['lots'] = array_values((array) ($case['lots'] ?? []));
        foreach ($case['lots'] as $i => $l) {
            $case['lots'][$i]['soil'] = isset($l['soil']) && is_array($l['soil']) ? $l['soil'] : $soil;
            $case['lots'][$i]['history'] = isset($l['history']) && is_array($l['history']) ? $l['history'] : $hist;
            $case['lots'][$i]['name'] = (string) ($l['name'] ?? 'Lot ' . ($i + 1));
        }
        $case['seedSource'] = (string) ($case['seedSource'] ?? ($hist['seedSource'] ?? ''));
        $case['variety'] = (string) ($case['variety'] ?? '');
        $case['seedType'] = (string) ($case['seedType'] ?? '');

        return $case;
    }

    /** The lot fields that may be the lot's own (2026-10-16, the owner: "you can plant different crops per lot"). */
    public const LOT_CROP = ['crop', 'cropLabel', 'method', 'variety', 'varietyCustom', 'seedType', 'seedSource', 'research'];

    /** A case seen from one lot: the lot's own crop, method, variety and seed in place of the plan's, when it has them. */
    public static function forLot(array $case, int $i): array
    {
        $l = $case['lots'][$i] ?? [];
        foreach (self::LOT_CROP as $k) {
            if (array_key_exists($k, $l) && $l[$k] !== null && $l[$k] !== '') {
                $case[$k] = $l[$k];
            }
        }

        return $case;
    }

    /** Whether the lots grow more than one crop or plant more than one way. */
    public static function mixed(array $case): bool
    {
        $seen = [];
        foreach (array_keys((array) ($case['lots'] ?? [])) as $i) {
            $c = self::forLot($case, $i);
            $seen[$c['crop'] . '|' . $c['method']] = true;
        }

        return count($seen) > 1;
    }

    /** One lot's own steps and notes: the base plan of its crop bent by that lot's answers. */
    public static function lotSteps(array $case, int $i): array
    {
        $case = self::forLot($case, $i);
        $lot = $case['lots'][$i];
        $sub = $case;
        $sub['soil'] = (array) ($lot['soil'] ?? []);
        $sub['history'] = (array) ($lot['history'] ?? []);
        $sub['lots'] = [$lot];

        return self::applyAnswers(self::baseSteps($case['crop'], $case['method']), $sub);
    }

    /**
     * Everything the arithmetic says about a case: each lot's steps, the
     * steps of the whole farm (each marked with the lots it belongs to when
     * not all), the notes (each with its lots), the seed preparation, the
     * crew calendar and the bookings of hired machines. `$extra` are Anee's
     * steps, each for every lot or the lots it names.
     */
    public static function build(array $case, array $extra = []): array
    {
        $case = self::normalize($case);
        $lotSteps = [];
        $notes = [];
        foreach ($case['lots'] as $i => $lot) {
            [$steps, $lotNotes] = self::lotSteps($case, $i);
            foreach ($extra as $s) {
                if (! empty($s['lots']) && ! in_array($i, $s['lots'], true)) {
                    continue;
                }
                $steps = array_values(array_filter($steps, fn ($x) => $x['key'] !== $s['key']));
                $steps[] = $s;
            }
            usort($steps, fn ($a, $b) => [$a['from'], $a['to']] <=> [$b['from'], $b['to']]);
            $lotSteps[$i] = $steps;
            foreach ($lotNotes as $n) {
                $k = $n['level'] . '|' . $n['text'];
                $notes[$k] ??= $n + ['lots' => []];
                $notes[$k]['lots'][] = $i;
            }
        }
        $count = count($case['lots']);
        foreach ($notes as $k => $n) {
            $notes[$k]['lots'] = count(array_unique($n['lots'])) >= $count ? [] : array_values(array_unique($n['lots']));
        }
        // Each lot's seed preparation, from its own crop and variety (one look up for lots that share them).
        $preps = [];
        $byKey = [];
        foreach ($case['lots'] as $i => $lot) {
            $c = self::forLot($case, $i);
            $k = implode('|', [$c['crop'], $c['method'], $c['variety'], $c['seedType']]);
            $byKey[$k] ??= self::seedPrep($c['crop'], $c['method'], $c['variety'], $c['seedType'], $c['research'] ?? null);
            $preps[$i] = $byKey[$k];
            $case['lots'][$i]['dayWord'] = self::dayWord($c['crop'], $c['method']);
        }
        $prep = $preps[0] ?? self::seedPrep($case['crop'], $case['method'], $case['variety'], $case['seedType'], $case['research'] ?? null);
        // Too little time is never refused (2026-10-15, the owner): the early steps are squeezed into the days left.
        $squeeze = [];
        foreach ($case['lots'] as $i => $lot) {
            $p = $preps[$i] ?? $prep;
            $prepDays = ! empty($p['found']) && ! empty($p['applies']) && isset($p['soakHours']) ? (int) ceil(((int) $p['soakHours'] + (int) $p['incubateHours']) / 24) : 2;
            [$lotSteps[$i], $sq] = self::squeeze($lotSteps[$i], self::forLot($case, $i), $lot, $prepDays);
            if ($sq) {
                $squeeze[$i] = $sq;
                $case['lots'][$i]['gapScale'] = $sq['ratio'];
                foreach ($sq['notes'] as $n) {
                    $k = $n['level'] . '|' . $n['text'];
                    $notes[$k] ??= $n + ['lots' => []];
                    $notes[$k]['lots'][] = $i;
                }
            }
        }
        $sched = self::schedule($lotSteps, $case['lots'], $case['crew'], $case);
        [$lotSteps, $sched, $bookings] = self::bookings($lotSteps, $sched, $case);
        foreach ($notes as $k => $n) {
            $ls = array_values(array_unique($n['lots']));
            $notes[$k]['lots'] = count($ls) >= $count ? [] : $ls;
        }

        return [
            'case' => $case,
            'steps' => self::union($lotSteps, $case['lots']),
            'lotSteps' => $lotSteps,
            'squeeze' => $squeeze,
            'notes' => array_values($notes),
            'prep' => $prep,
            'preps' => $preps,
            'sched' => $sched,
            'bookings' => $bookings,
            'dayWord' => self::dayWord($case['crop'], $case['method']),
        ];
    }

    /**
     * The steps of the whole farm: each key once, from the first lot that has
     * it, marked with its lots when not every lot has it, and a lot's own
     * rule added where it differs.
     */
    public static function union(array $lotSteps, array $lots): array
    {
        $n = count($lots);
        $defs = [];
        $have = [];
        foreach ($lotSteps as $i => $steps) {
            foreach ($steps as $s) {
                $k = $s['key'];
                if (! isset($defs[$k])) {
                    $defs[$k] = $s;
                    unset($defs[$k]['lots'], $defs[$k]['lotsWords']);
                    $have[$k] = [$i];
                    continue;
                }
                $have[$k][] = $i;
                $r = trim((string) ($s['rule'] ?? ''));
                if ($r !== '' && ! str_contains((string) ($defs[$k]['rule'] ?? ''), $r)) {
                    $defs[$k]['rule'] = trim(($defs[$k]['rule'] ?? '') . ' For ' . ($lots[$i]['name'] ?? 'Lot ' . ($i + 1)) . ': ' . $r);
                }
            }
        }
        foreach ($defs as $k => $s) {
            $ls = array_values(array_unique($have[$k]));
            if ($n > 1 && count($ls) < $n) {
                $defs[$k]['lots'] = $ls;
                $defs[$k]['lotsWords'] = 'For ' . implode(', ', array_map(fn ($i) => $lots[$i]['name'] ?? 'Lot ' . ($i + 1), $ls));
            }
        }
        $out = array_values($defs);
        usort($out, fn ($a, $b) => [$a['from'], $a['to']] <=> [$b['from'], $b['to']]);

        return $out;
    }

    /* ================================================== the farmer's answers */

    /** A lot's answer as a list, whether it was saved as one value or several. */
    private static function many(array $from, string $key): array
    {
        $v = $from[$key] ?? [];

        return array_values(array_filter(is_array($v) ? $v : [$v], fn ($x) => is_string($x) && $x !== ''));
    }

    /**
     * The last season's troubles of a lot as {key, kind, slug, name}: the
     * catalogue's pests and diseases, the other troubles, and the answers of
     * a plan saved before round two (snails, rats and birds, one outbreak).
     */
    public static function troubles(array $h, string $crop): array
    {
        $keys = self::many($h, 'outbreaks');
        if (($h['snails'] ?? '') === 'many') {
            $keys[] = 'other:snails';
        }
        $rb = (string) ($h['ratsBirds'] ?? '');
        if (in_array($rb, ['rats', 'both'], true)) {
            $keys[] = 'other:rats';
        }
        if (in_array($rb, ['birds', 'both'], true)) {
            $keys[] = 'other:birds';
        }
        $old = ['tungro' => 'disease:rice-tungro', 'bph' => 'pest:brown-planthopper', 'blast' => 'disease:rice-blast'][$h['pests'] ?? ''] ?? null;
        if ($old) {
            $keys[] = $old;
        }
        $out = [];
        foreach (array_unique($keys) as $k) {
            $t = FieldProfile::trouble((string) $k);
            if ($t) {
                $out[] = $t;
            }
        }

        return $out;
    }

    /**
     * The plan bent by one lot's answers: steps added, dropped or noted,
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
        $crew = self::crew((array) ($case['crew'] ?? []));
        $notes = [];
        $note = function (string $level, string $text, string $reason) use (&$notes): void {
            foreach ($notes as $n) {
                if ($n['text'] === $text) {
                    return;
                }
            }
            $notes[] = ['level' => $level, 'text' => $text, 'reason' => $reason];
        };
        $has = fn (string $k) => (bool) array_filter($steps, fn ($x) => $x['key'] === $k);
        $drop = function (string $k) use (&$steps): void {
            $steps = array_values(array_filter($steps, fn ($x) => $x['key'] !== $k));
        };
        $addNote = function (string $k, string $text) use (&$steps): void {
            foreach ($steps as &$x) {
                if ($x['key'] === $k && ! str_contains((string) ($x['rule'] ?? ''), $text)) {
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
        $troubles = self::troubles($h, $crop);
        $kinds = array_column($troubles, 'kind');
        $slugs = array_column($troubles, 'slug');
        $hadKind = fn (string $k) => in_array($k, $kinds, true);
        $had = fn (string ...$ss) => (bool) array_intersect($ss, $slugs);

        // Other troubles that stand for an answer the farmer left blank.
        if ($had('flood') && empty($h['flooding'])) {
            $h['flooding'] = 'sometimes';
        }
        if ($had('salt') && empty($s['salt'])) {
            $s['salt'] = ['tips'];
        }

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
                $addNote($method === 'tpr_manual' ? 'seedbed_site' : 'nursery_site', 'Your soil is clay, so work in the organic fertilizer or carbonized rice hull here.');
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

        /* ---- water: one source or several ---- */
        $water = self::many($s, 'water');
        if ($water === ['rainfed']) {
            $note('info', 'A rainfed field can only be plowed once the rains soften the soil. If the rains come late, every date moves with them, so watch the first heavy rains.', 'water');
            if ($rice && ! $dry) {
                $addNote('dikes', 'Fix the dikes before the rains so the first water stays in the field.');
            }
        } elseif (in_array('pump', $water, true) && $rice) {
            $note('info', 'On a pump, alternate wetting and drying later in the season saves fuel and water without losing yield.', 'water');
        }
        if (in_array('river', $water, true) || in_array('reservoir', $water, true)) {
            $addNote('dikes', 'Clean the small canal from the river or the farm pond too, so the water reaches the field when you need it.');
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

        /* ---- acidity, with a known pH range ---- */
        $acid = $s['acidity'] ?? '';
        $lo = isset($s['phFrom']) && is_numeric($s['phFrom']) ? (float) $s['phFrom'] : (isset($s['ph']) && is_numeric($s['ph']) ? (float) $s['ph'] : null);
        $hi = isset($s['phTo']) && is_numeric($s['phTo']) ? (float) $s['phTo'] : $lo;
        if ($lo !== null && $acid !== 'sulfate') {
            $acid = $lo < 5 ? 'strong' : ($lo <= 5.5 ? 'mild' : ($hi !== null && $hi > 7.5 ? 'alkaline' : (in_array($acid, ['untested', ''], true) ? 'none' : $acid)));
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
        // Several signs at once since 2026-10-16 (an older single answer is a list of one).
        $salt = (array) ($s['salt'] ?? []);
        if (array_intersect($salt, ['crust', 'sea', 'tips'])) {
            $add(self::step('salt_variety', $tpr ? -35 : -15, $tpr ? -25 : -5, 'Choose a salt tolerant variety', 'Pick a salt tolerant variety before you buy or soak the seed, not at planting time.', 'anee', null, 'soil', 'Salt shows on this field.'));
            $add(self::step('flush_salt', -21, -7, 'Flush the salt', $rice ? 'Flood the field with good water and drain it, two or three times, between plowing and the final harrowing. Keep water standing afterwards so the salt does not climb back.' : 'Flood the field with good water and drain it, two or three times, to wash the salt below the roots, then plant on raised beds.', 'anee', null, 'soil', 'Salt shows on this field.'));
            if ($wet || $dry) {
                $note('warn', 'Young rice is most sensitive to salt: strong transplanted seedlings do better here than direct seeding.', 'soil');
            }
        }
        if (in_array('sodic', $salt, true)) {
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
                } elseif (str_starts_with((string) ($h['prevCrop'] ?? 'rice'), 'rice') && $rest < 30) {
                    $note('warn', 'Your field gets only about ' . max(0, $rest) . ' days without rice before sowing. PhilRice asks for at least 30: plow the stubble and any ratoon under right away, or plant ' . (30 - max(0, $rest)) . ' days later.', 'history');
                    $addNote('rest', 'Short rest here: plow under the stubble and ratoon at once.');
                }
            }
        }
        $prev = $h['prevCrop'] ?? '';
        if (in_array($prev, ['rice_no_straw', 'corn', 'legume', 'vegetable', 'fallow', 'new_land'], true)) {
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

        /* ---- weeds: one kind or several ---- */
        $weeds = self::many($h, 'weeds');
        $weedy = fn (string $k) => in_array($k, $weeds, true);
        if ($weedy('many') && $rice && ! $dry) {
            $addNote('soak_field', 'Many weeds last season: give the field the full 7 days or more with water in between each pass.');
        }
        if ($weedy('weedy_rice') && $rice) {
            $x = $adds['weed_history']['stale_round'] ?? null;
            if ($x && ! $dry) {
                $add(self::step('stale_round', -8, -4, $x['what'], $x['how'], 'anee', $x['follows'] ?? null, 'history', 'Weedy rice last season.'));
            }
            $note('warn', 'Weedy rice: ' . ($dry ? 'do not dry seed this field.' : ($wet ? 'transplant instead of direct seeding.' : 'transplanting is the right choice.')) . ' Use clean certified seed and clean the tractor, tools, canals and dikes so no weed seed rides in.', 'history');
        }
        if ($weedy('sedges')) {
            $note('info', $rice ? 'Many sedges: level the field well and keep 3 to 5 cm of water after planting. Sedges take over the high spots that dry out.' : 'Many sedges: harrow twice a week apart before planting so the new flush is killed, and keep the rows weeded early.', 'history');
        }
        if ($weedy('tough')) {
            $note('info', 'Tough weeds like cogon or nutsedge regrow from their roots: plow, let them sprout, and plow or harrow again before planting.', 'history');
        }

        /* ---- the last season's pests, diseases and other troubles ---- */
        if ($hadKind('Snail') && $rice) {
            $x = $adds['snail_history']['snail_screens'] ?? null;
            if ($x) {
                $add(self::step('snail_screens', -3, -1, $x['what'], $x['how'], 'philrice', null, 'history', 'Golden apple snails last season.'));
            }
            if ($tpr && ! $has('canals')) {
                $add(self::step('canals', -1, 0, 'Cut the small canals', 'Right after leveling, cut small canals about 25 cm wide and 5 cm deep along the dikes and through the middle. Snails gather there and are easy to pick.', 'philrice', null, 'history', 'Golden apple snails last season.'));
            }
            $note('info', 'Snails: keep the soil just saturated, not flooded, up to about 15 days after transplanting or 20 to 25 days after sowing' . ($tpr ? ', and transplant at the older end of the seedling age.' : '.'), 'history');
        }
        if ($hadKind('Rodent')) {
            $add(self::step('rat_campaign', -21, -10, 'Plan a rat campaign with your neighbors', 'Before planting, dig out or flood the burrows on the dikes and canals, trim the dikes to no more than 15 by 20 cm, and agree with your neighbors to work and plant together so the rats have no single field to feed on. Use sealed bait stations only, as your agriculture office advises.', 'anee', null, 'history', 'Rats damaged the last crop.'));
        }
        if ($hadKind('Bird')) {
            $note('info', 'Birds: plan someone to chase them in the first week after sowing, and do not sow far ahead of or behind your neighbors.', 'history');
        }
        $virus = $hadKind('Virus') || $hadKind('Viroid');
        if ($virus) {
            $add($rice
                ? self::step('host_clear', -40, -25, 'Break the virus cycle', 'Plow under the stubble, ratoon and stray rice right away and keep the field free of rice through the rest. Raise the seedbed away from fields that were sick, and plant together with your neighbors so the insects that carry the virus find no young rice.', 'anee', null, 'history', 'A virus disease hit the last crop.')
                : self::step('host_clear', -30, -20, 'Clear the old crop and the weeds around it', 'Pull out the old plants and the weeds on the borders that keep the virus and the insects that carry it, and bury or burn them away from the field. Use clean seedlings or planting material.', 'anee', null, 'history', 'A virus disease hit the last crop.'));
        }
        if ($rice && ($virus || $had('brown-planthopper', 'whitebacked-planthopper', 'rice-blast'))) {
            $note('warn', 'After ' . implode(' and ', array_map(fn ($t) => mb_strtolower($t['name']), array_filter($troubles, fn ($t) => in_array($t['kind'], ['Virus', 'Viroid'], true) || in_array($t['slug'], ['brown-planthopper', 'whitebacked-planthopper', 'rice-blast'], true))))
                . ' the 30 day rest and planting together with your area are firm rules, and a resistant variety pays.'
                . ($had('brown-planthopper', 'whitebacked-planthopper') && $wet ? ' Do not broadcast seed, transplant instead.' : '')
                . ($had('rice-blast') ? ' Plow the infected stubble under right after harvest.' : ''), 'history');
        }
        if ($had('rice-stem-borer')) {
            $addNote('plow', 'Stem borers last season: plow the stubble under early, the larvae live in it.');
        }
        if ($had('bakanae', 'bacterial-leaf-blight', 'bacterial-leaf-streak')) {
            $note('info', 'This disease rides on the seed and the water: use clean certified seed, do not overfeed nitrogen, and keep water from a sick field out of yours.', 'history');
        }
        if (! $rice && ($hadKind('Bacterium') || $hadKind('Water mold'))) {
            $note('info', 'A soil disease hit the last crop: plant on raised beds that drain well, and rotate to a crop of another family.', 'history');
        }
        if ($hadKind('Nematode')) {
            $note('info', 'Root knot nematodes stay in the soil: flood the field for a few weeks or plow it and leave it to the sun, and rotate with a crop they do not feed on.', 'history');
        }
        if ($had('white-grub', 'mole-cricket')) {
            $addNote('plow', 'White grubs or mole crickets last season: plow early and twice so the birds and the sun get them.');
        }
        if ($had('drought')) {
            $note('info', 'Drought last season: fix the canal and the water source before plowing' . ($rice ? ', choose a shorter variety, and keep alternate wetting and drying in mind.' : ', choose a shorter variety, and mulch to keep the soil moist.'), 'water');
        }
        if ($had('lodging')) {
            $note('info', 'The crop lodged last season: go easy on nitrogen, keep the right spacing, and choose a sturdier variety.', 'history');
        }
        // A hungry last crop (its leaves, or the old "pale crop" trouble), unless a soil test from the last two years already says what the soil lacks.
        $hungry = $had('nutrient') || array_intersect((array) ($h['cropLook'] ?? []), ['pale', 'purple', 'edges']);
        $tested = collect((array) ($s['tests'] ?? []))->contains(fn ($t) => is_array($t) && ! empty($t['date']) && $t['date'] >= now('Asia/Manila')->subYears(2)->toDateString());
        if ($hungry && ! $tested) {
            $add(self::step('soil_test', -60, -45, 'Test the soil', 'Take a soil sample to the regional soils laboratory or check it with a soil test kit, so this season\'s fertilizer fits what the soil lacks.', 'anee', null, 'soil', 'The last crop showed it was short of plant food.'));
        }

        /* ---- seed source (asked once for the whole farm) ---- */
        $seedSource = (string) ($case['seedSource'] ?? ($h['seedSource'] ?? ''));
        if ($rice && in_array($seedSource, ['own', 'old'], true) && ! $dry) {
            $x = $adds['seed_source']['seed_test'] ?? null;
            if ($x) {
                $add(self::step('seed_test', -37, -27, $x['what'], $x['how'], 'philrice', $x['follows'] ?? null, 'seed', 'The seed is home kept.'));
            }
        }

        /* ---- field level, zinc ---- */
        if ($rice && in_array($h['level'] ?? '', ['some', 'uneven'], true) && ! $dry) {
            $x = $adds['field_level']['extra_level'] ?? null;
            if ($x) {
                $add(self::step('extra_level', -7, -4, $x['what'], $x['how'], 'philrice', $x['follows'] ?? null, 'history', 'Some spots stayed dry while others held deep water.'));
            }
            if (($h['level'] ?? '') === 'uneven' && ($wet || $dry)) {
                $note('warn', 'A very uneven field drowns sown seed in its low spots. Level it well, or think of laser leveling, before direct seeding.', 'history');
            }
        }
        $zinc = (string) ($h['zinc'] ?? ($had('zinc') || in_array('zinc', (array) ($h['cropLook'] ?? []), true) ? 'zinc' : ''));
        if ($rice && in_array($zinc, ['mild', 'severe', 'zinc'], true)) {
            $txt = match ($zinc) {
                'severe' => 'Zinc shortage was bad here: use 2 kg of zinc sulfate on the seedbed, or dip the seedling roots in a 2 percent zinc oxide mix before transplanting.',
                'mild' => 'A mild zinc shortage: 0.5 kg of zinc sulfate on the seedbed is enough.',
                default => 'Zinc was short here: use 0.5 to 2 kg of zinc sulfate on the seedbed, the more the worse it was, or dip the seedling roots in a 2 percent zinc oxide mix before transplanting.',
            };
            $addNote($method === 'tpr_mechanical' ? 'nursery_zinc' : 'seedbed_zinc', $txt);
            if ($wet) {
                $addNote('seed_soak', 'Zinc was short here: add 1 kg of zinc sulfate for every 40 kg of seed to the last soaking water.');
            }
        } elseif (! $rice && $had('zinc')) {
            $note('info', 'Zinc was short here: a soil test tells how much zinc sulfate to work in before planting.', 'soil');
        }

        /* ---- the machines ---- */
        $types = array_column($crew['fleet'], 'type');
        $able = fn (string $cap) => (bool) array_filter(self::units($crew['fleet']), fn ($u) => isset($u['rates'][$cap]));
        if (! $types) {
            $note('info', 'With no machine on your list, the plowing and harrowing are by hand. That takes far longer, so the crew needs more days or more hands.', 'crew');
        } elseif (in_array('carabao_plow', $types, true) && ! array_intersect($types, ['hand_tractor', 'tractor_rotavator', 'tractor_disc'])) {
            $note('info', 'With a carabao each pass takes longer, so the plowing starts at the early end of its window.', 'crew');
        }
        if ($types && ! $able('plow') && $has('plow')) {
            $note('warn', 'None of your machines can plow, so the plan has the plowing done by hand. Hiring a plow for those days saves weeks of work.', 'crew');
        }
        if ($method === 'dsr_wet_drum' && ! in_array('drum_seeder', $types, true)) {
            $note('warn', 'You sow with a drum seeder but have none on your list. Borrow one from your agriculture office or hire one, and book it a week ahead.', 'crew');
        }
        if ($method === 'tpr_mechanical' && ! in_array('transplanter', $types, true)) {
            $note('warn', 'You plant by machine but have no transplanter on your list. Book the transplanter service a week ahead, together with the trays.', 'crew');
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

    /* ============================================================ the time */

    /**
     * Is there time? For each lot: the days the recommended preparation needs
     * before its planting day (its earliest field work, after that lot's
     * answers, so lime, a salt flush or a newly opened field count), the
     * days a rice field still needs to rest without rice, the days that are
     * left, and the steps a short run squeezes (its window already open) or
     * misses (its window already closed).
     */
    public static function timeCheck(array $case, ?string $today = null): array
    {
        $case = self::normalize($case);
        $T = Carbon::parse($today ?: now('Asia/Manila')->toDateString(), 'UTC')->startOfDay();
        $out = [];
        foreach ($case['lots'] as $i => $lot) {
            $lc = self::forLot($case, $i);
            $rice = str_starts_with((string) $lc['crop'], 'rice');
            [$steps] = self::lotSteps($case, $i);
            $work = array_values(array_filter($steps, fn ($s) => ! in_array($s['key'], self::NOT_WORK, true) && ! in_array($s['key'], self::PLANT_KEYS, true) && $s['key'] !== 'pull' && (int) $s['from'] < 0));
            usort($work, fn ($a, $b) => [$a['from'], $a['to']] <=> [$b['from'], $b['to']]);
            $needed = $work ? -(int) $work[0]['from'] : 0;
            $P = Carbon::parse((string) $lot['planting'], 'UTC')->startOfDay();
            $left = (int) $T->diffInDays($P, false);
            // The rest a rice field needs with no rice on it before sowing.
            $h = (array) $lot['history'];
            $rest = null;
            $restNotYet = false;
            $ago = ['under2w' => 7, '2to4w' => 21, '1to3m' => 60, 'over3m' => 120, 'not_yet' => null][$h['harvested'] ?? ''] ?? false;
            if ($rice && $ago !== false && str_starts_with((string) ($h['prevCrop'] ?? 'rice'), 'rice')) {
                if ($ago === null) {
                    $restNotYet = true;
                } else {
                    $rest = max(0, 30 - $ago - self::sowOffset($steps, (string) $lc['method']));
                }
            }
            $need = max($needed, (int) $rest);
            $hit = [];
            foreach ($work as $s) {
                $a = $left + (int) $s['from'];
                $b = $left + (int) $s['to'];
                if ($b < 0) {
                    $hit[] = ['key' => $s['key'], 'what' => $s['what'], 'state' => 'missed'];
                } elseif ($a < 0) {
                    $hit[] = ['key' => $s['key'], 'what' => $s['what'], 'state' => 'squeezed'];
                }
            }
            $out[] = [
                'lot' => $i, 'name' => $lot['name'], 'planting' => $P->toDateString(), 'left' => $left,
                'needed' => $needed, 'rest' => $rest, 'restNotYet' => $restNotYet, 'need' => $need,
                'short' => $left < $need, 'earliest' => $T->copy()->addDays($need)->toDateString(),
                'first' => $work ? ['what' => $work[0]['what'], 'from' => (int) $work[0]['from']] : null,
                'steps' => $hit,
            ];
        }

        return $out;
    }

    /** The seedbed and nursery steps: they move as one block with the sowing, so the seedlings keep their age. */
    private const SEEDBED = ['seed_test', 'seedbed_site', 'seedbed_beds', 'seed_soak', 'seed_incubate', 'sow_seedbed', 'seedbed_water', 'seedbed_zinc', 'seedbed_feed', 'backup_seedbed',
        'nursery_site', 'nursery_fill', 'sow_nursery', 'nursery_care', 'nursery_zinc', 'nursery_feed', 'nursery'];

    /** Waiting periods: the first to give way when time is short. */
    private const WAITS = ['rest', 'soak_field', 'stale_round', 'stale_wet', 'settle', 'soften'];

    /** The youngest seedlings worth transplanting, by method, in days. */
    private const MIN_AGE = ['tpr_manual' => 18, 'tpr_mechanical' => 14];

    /**
     * A lot's plan squeezed into the days it has left (2026-10-15, the owner:
     * too little time is a warning, never a refusal). Nothing is dated before
     * today. The seedbed moves as one block, as late as the days allow, so
     * the seedlings keep as much of their age as they can. Everything else
     * before the planting day keeps its order: the last stretch before
     * planting (the final harrowing and the leveling) stays as it is, and
     * the early part (the rest, the plowing and the waiting between passes)
     * is pressed into what is left, so the waiting gives way first. Every
     * step that moved says so, and why.
     *
     * Returns [steps, null] when there is time, else [steps, {left, needed,
     * ratio (how much the early gaps shrink, for the scheduler), age, ageWas,
     * count, notes}].
     */
    public static function squeeze(array $steps, array $case, array $lot, int $prepDays = 2, ?string $today = null): array
    {
        $T = Carbon::parse($today ?: now('Asia/Manila')->toDateString(), 'UTC')->startOfDay();
        $L = max(0, (int) $T->diffInDays(Carbon::parse((string) $lot['planting'], 'UTC')->startOfDay(), false));
        if (! array_filter($steps, fn ($s) => (int) $s['from'] < -$L)) {
            return [$steps, null];
        }
        $work = array_filter($steps, fn ($s) => ! in_array($s['key'], self::NOT_WORK, true) && ! in_array($s['key'], self::PLANT_KEYS, true) && $s['key'] !== 'pull' && (int) $s['from'] < 0);
        $needed = $work ? -(int) min(array_column($work, 'from')) : 0;
        if ($needed <= $L) {
            // The field work fits: only the waiting and the checks that should have begun already start today.
            foreach ($steps as $k => $s) {
                if ((int) $s['from'] < -$L) {
                    $was = [(int) $s['from'], (int) $s['to']];
                    $steps[$k]['from'] = -$L;
                    $steps[$k]['to'] = max(-$L, (int) $s['to']);
                    $steps[$k]['squeezed'] = ['was' => $was, 'state' => 'moved', 'why' => match ($s['key']) {
                        'rest' => 'The rest without rice is shorter than PhilRice advises. Plow the stubble and any ratoon under at once.',
                        'soil_test' => 'Test now, with a soil test kit if you can. A laboratory result may come after plowing.',
                        default => 'Its usual time has passed, so do it as soon as you can.',
                    }];
                }
            }
            usort($steps, fn ($a, $b) => [$a['from'], $a['to']] <=> [$b['from'], $b['to']]);

            return [$steps, null];
        }
        $N = max($needed, $L + 1);
        $K = (int) floor($L / 2);
        $ratio = ($L - $K) / max(1, $N - $K);
        // The early part [-N, -K] is pressed into [-L, -K]; the last K days stay.
        $f = function (int $o) use ($L, $N, $K, $ratio): int {
            if ($o >= -$K) {
                return $o;
            }
            if ($o <= -$N) {
                return -$L;
            }

            return (int) round(-$K + ($o + $K) * $ratio);
        };
        $word = self::dayWord((string) $case['crop'], (string) $case['method']);
        $method = (string) $case['method'];
        // The seedbed: sown as late as the soaking and sprouting allow from today, never later than it was.
        $sowKey = null;
        foreach ($steps as $s) {
            if (in_array($s['key'], ['sow_seedbed', 'sow_nursery', 'nursery'], true)) {
                $sowKey = $s['key'];
                break;
            }
        }
        $oldSow = null;
        $delta = 0;
        if ($sowKey) {
            foreach ($steps as $s) {
                if ($s['key'] === $sowKey) {
                    $oldSow = (int) $s['from'];
                }
            }
            $minSow = -$L + ($sowKey === 'nursery' ? 0 : max(1, $prepDays));
            $newSow = min(-1, max($oldSow, $minSow));
            $delta = max(0, $newSow - $oldSow);
        }
        $count = 0;
        foreach ($steps as $k => $s) {
            $from = (int) $s['from'];
            $to = (int) $s['to'];
            if ($from >= 0) {
                continue;
            }
            if ($sowKey && in_array($s['key'], self::SEEDBED, true)) {
                $nf = max(-$L, $from + $delta);
                $nt = min(max($nf, $to + $delta), max($nf, -1));
            } else {
                $nf = max(-$L, $f($from));
                $nt = $to >= 0 ? $to : max($nf, $f($to));
            }
            if ($nf === $from && $nt === $to) {
                continue;
            }
            $count++;
            $why = match (true) {
                $s['key'] === 'rest' => 'The rest without rice is shorter than PhilRice advises. Plow the stubble and any ratoon under at once.',
                $s['key'] === 'calendar' => 'The planting day is near, so check the area\'s schedule today.',
                $s['key'] === 'soil_test' => 'Test now, with a soil test kit if you can. A laboratory result may come after plowing.',
                $s['key'] === 'seed_test' => 'Little time to test the seed: sprout a small handful first and check that most come up, or use certified seed.',
                in_array($s['key'], self::SEEDBED, true) => 'Sown later than usual so it fits the days left. The seedlings will be younger at ' . $word . '.',
                in_array($s['key'], self::WAITS, true) => 'Shortened: less waiting for the clods to soften and the weeds to sprout.',
                default => 'Moved closer to the ' . $word . ' day: only ' . $L . ' ' . ($L === 1 ? 'day is' : 'days are') . ' left and the full preparation takes ' . $needed . '.',
            };
            $steps[$k]['from'] = $nf;
            $steps[$k]['to'] = $nt;
            $steps[$k]['squeezed'] = ['was' => [$from, $to], 'state' => ($nt - $nf) < ($to - $from) ? 'shortened' : 'moved', 'why' => $why];
        }
        usort($steps, fn ($a, $b) => [$a['from'], $a['to']] <=> [$b['from'], $b['to']]);
        $notes = [['level' => 'warn', 'reason' => 'crew', 'text' => 'Only ' . $L . ' ' . ($L === 1 ? 'day is' : 'days are') . ' left before ' . $word . ', and the full preparation needs ' . $needed
            . '. The plan presses the early steps together: plow first, keep every harrowing and the leveling, and shorten the waiting between them. Walk the field and check it carefully before you start.']];
        $age = null;
        if ($sowKey && $delta > 0) {
            $age = -($oldSow + $delta);
            $min = self::MIN_AGE[$method] ?? (int) ceil(-$oldSow * 0.75);
            if ($age < $min) {
                $notes[] = ['level' => 'warn', 'reason' => 'seed', 'text' => $age < 7
                    ? 'There is no time to raise seedlings before this day. Buy or borrow healthy seedlings, or ' . ($word === 'transplanting' ? 'transplant' : 'plant') . ' later.'
                    : 'The seedlings will be only ' . $age . ' days old at ' . $word . ', and ' . ($method === 'tpr_manual' ? 'PhilRice advises 18 to 21 days' : ($method === 'tpr_mechanical' ? 'PhilRice advises 14 to 18 days' : 'the usual age is ' . (-$oldSow) . ' days'))
                        . '. Move the day a little later if you can, or get older seedlings from a neighbor\'s seedbed.'];
            }
        }

        return [$steps, ['left' => $L, 'needed' => $needed, 'ratio' => round(max(0.1, min(1, $ratio)), 3), 'age' => $age, 'ageWas' => $oldSow !== null ? -$oldSow : null, 'count' => $count, 'notes' => $notes]];
    }

    /**
     * The crew a case needs: the fewest workers (working Monday to Saturday,
     * with the machines chosen) who get every lot ready on the day asked,
     * and the reason in plain words. Counted from today on the plan as it
     * will run, squeezed when the days are few (2026-10-16: it used to
     * pretend the whole window was still ahead, so it could say a crew was
     * enough that the forecast then showed late).
     */
    public static function recommendCrew(array $case): array
    {
        $case = self::normalize($case);
        $lots = $case['lots'];
        if (! $lots) {
            return ['workers' => null, 'reason' => ''];
        }
        $lotSteps = [];
        foreach ($lots as $i => $l) {
            [$lotSteps[$i]] = self::lotSteps($case, $i);
            $lots[$i]['idx'] = $i;
        }
        $built = self::build(array_merge($case, ['crew' => array_merge($case['crew'], ['stagger' => false])]));
        $units = self::units($case['crew']['fleet']);
        $owned = count(array_filter($units, fn ($u) => ! $u['hired']));
        $best = null;
        foreach ([1, 2, 3, 4, 5, 6, 7, 8, 10, 12, 15, 20, 25, 30, 40, 50, 60, 80, 100] as $n) {
            if ($n < min(2, $owned)) {
                continue;
            }
            $crew = $case['crew'];
            $crew['workers'] = array_fill(0, $n, ['name' => 'Worker', 'days' => [1, 2, 3, 4, 5, 6]]);
            $crew['stagger'] = false;
            $r = self::schedule($built['lotSteps'], $built['case']['lots'], $crew, $built['case']);
            if (! array_filter($r['conflicts'], fn ($c) => $c['why'] === 'crew' && $c['major'] !== false)) {
                $best = $n;
                break;
            }
        }
        // The hand work, in person days, and the weeks it spreads over.
        $hand = 0.0;
        $byHand = [];
        $span = 0;
        foreach ($lots as $i => $l) {
            $area = max(0.001, (float) $l['area']);
            $from = 0;
            foreach ($lotSteps[$i] as $s) {
                [$kind, $u, $cap, $fallback] = self::workFor($s['key'], $area, $units, (array) $l['soil']);
                if ($kind === 'manual' && ! in_array($s['key'], self::PLANT_KEYS, true)) {
                    $hand += $u;
                    $from = min($from, (int) $s['from']);
                    if ($fallback) {
                        $byHand[$cap] = true;
                    }
                }
            }
            $span = max($span, -$from);
        }
        $area = array_sum(array_map(fn ($l) => (float) $l['area'], $lots));
        $weeks = max(1, (int) round($span / 7));
        $word = self::dayWord($case['crop'], $case['method']);
        $plantPeople = 0;
        $plantWords = '';
        foreach ($lots as $i => $l) {
            $lc = self::forLot($case, $i);
            foreach ($lotSteps[$i] as $s) {
                $rate = self::PLANTING[$s['key'] . ':' . $lc['method']] ?? self::PLANTING[$s['key'] . ':' . self::groupFor($lc['crop'])] ?? null;
                if ($rate && in_array($s['key'], self::PLANT_KEYS, true)) {
                    $plantPeople = max($plantPeople, (int) ceil($rate[0] * (float) $l['area']));
                    $plantWords = $rate[1];
                }
            }
        }
        $ha = rtrim(rtrim(number_format($area, 2), '0'), '.');
        // The biggest hand jobs, by name.
        $jobWords = ['dikes' => 'the dikes', 'clear_land' => 'opening the new land', 'clear' => 'clearing the field', 'beds' => 'the beds', 'holes' => 'the planting holes', 'fill' => 'filling the holes',
            'seedbed_site' => 'the seedbed', 'seedbed_beds' => 'the seedbed', 'nursery_fill' => 'the nursery trays', 'mulch' => 'the mulch', 'straw' => 'the straw', 'drainage' => 'the drains'];
        $named = [];
        foreach ($lots as $i => $l) {
            foreach ($lotSteps[$i] as $s) {
                if (isset($jobWords[$s['key']]) && ! in_array($jobWords[$s['key']], $named, true)) {
                    $named[] = $jobWords[$s['key']];
                }
            }
        }
        $named = array_slice($named, 0, 2);
        $jobs = $named ? implode(', ', $named) . ' and the other hand work' : 'the hand work';
        $reason = $best === null
            ? 'Even 100 workers cannot finish ' . $ha . ' ha in time with these machines. Add a machine, hire a service, or give the lots later days.'
            : 'About ' . $best . ' ' . ($best === 1 ? 'worker' : 'workers') . ' for ' . $ha . ' ha: ' . $jobs . ' come to about ' . (int) round($hand) . ' person days in the ' . $weeks . ' ' . ($weeks === 1 ? 'week' : 'weeks') . ' before ' . $word
                . ($owned ? ', and each of your own machines needs a driver' : '')
                . ($byHand ? ', and with no machine for some of the field work it is done by hand' : '') . '.'
                . ($plantPeople > 1 ? ' The ' . $word . ' day itself takes about ' . $plantPeople . ' ' . $plantWords . ' on top.' : '');

        return ['workers' => $best, 'reason' => $reason, 'handDays' => (int) round($hand), 'weeks' => $weeks, 'area' => round($area, 3), 'planting' => $plantPeople];
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

    /** Whether a variety name is on our list for this crop (rice or the crop variety book). */
    public static function knownVariety(string $crop, string $name): bool
    {
        $name = trim($name);
        if ($name === '') {
            return true;
        }
        if (str_starts_with($crop, 'rice')) {
            return self::riceRecord($name) !== null;
        }
        $k = self::norm($name);
        foreach ((array) (CropVarieties::book()['crops'] ?? []) as $ck => $c) {
            if ($ck !== $crop && ! in_array($crop, (array) ($c['catalogue'] ?? []), true)) {
                continue;
            }
            foreach (CropVarieties::forCrop($ck) as $v) {
                foreach (array_merge([$v['name'] ?? '', $v['localName'] ?? ''], (array) ($v['aliases'] ?? [])) as $n) {
                    if (is_string($n) && $n !== '' && self::norm($n) === $k) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * How to prepare the seed for this crop, method and variety, with the
     * hours, and how sure the source is. `applies` false when nothing is
     * soaked (dry seeding, cuttings, suckers); `found` false when nobody
     * researched it, and then `say` tells the farmer to follow the seed bag.
     *
     * `$research` is Anee's web research on a variety not on our list (see
     * LandPrepController::research): its type, days and the company's own
     * soaking, water change and incubation hours win over the general
     * practice, and a failed research is said plainly.
     */
    public static function seedPrep(string $crop, string $method, string $variety, string $seedType, ?array $research = null): array
    {
        $asked = $research && ! empty($research['asked']);
        if (! $asked) {
            return self::seedPrepBase($crop, $method, $variety, $seedType);
        }
        $rice = str_starts_with($crop, 'rice');
        $found = ! empty($research['ok']) && ! empty($research['found']);
        $facts = array_filter([
            'days' => $research['days'] ?? null, 'type' => $research['type'] ?? null, 'company' => $research['company'] ?? null,
            'searched' => (bool) ($research['searched'] ?? false),
        ], fn ($v) => $v !== null && $v !== '');
        if (! $found) {
            $base = self::seedPrepBase($crop, $method, $variety, $seedType);
            $miss = 'Anee could not find this variety on the web just now, so follow the instructions on the seed bag.';

            return $base + ['research' => ['status' => 'failed'] + $facts, 'researchSay' => $miss];
        }
        $type = in_array($seedType, ['hybrid', 'inbred'], true) ? $seedType : (in_array($research['type'] ?? '', ['hybrid', 'inbred'], true) ? $research['type'] : '');
        $base = self::seedPrepBase($crop, $method, $variety, $type);
        $notes = trim((string) ($research['seedNotes'] ?? ''));
        if (! $rice || empty($base['applies'])) {
            return $base + ['research' => ['status' => 'found'] + $facts, 'researchSay' => $notes !== '' ? $notes : 'Anee found the variety but no seed instructions of its own. Follow the seed bag.'];
        }
        $soak = $research['soak'] ?? null;
        $inc = $research['incubate'] ?? null;
        if (! $soak && ! $inc) {
            $say = ($base['confidence'] ?? '') === 'company' && ! empty($base['brand'])
                ? 'Anee found this variety. Its soaking hours here are the seed company\'s own instructions (' . $base['brand'] . '). The seed bag comes first.'
                : 'Anee found this variety but not its own soaking hours, so these are the general hours for ' . ($type ?: 'rice') . ' seed. The seed bag comes first.';

            return $base + ['research' => ['status' => 'found'] + $facts, 'researchSay' => $say];
        }
        $general = $type === 'hybrid' ? (self::seedBook()['general']['hybrid'] ?? []) : (self::seedBook()['general']['inbred'] ?? []);
        $soak = $soak ?: [(int) ($general['soakHours'] ?? 24), (int) ($general['soakHours'] ?? 24)];
        $inc = $inc ?: array_map('intval', (array) ($general['incubateRange'] ?? [24, 36]));
        $refresh = $research['refresh'] ?? null;
        $out = array_merge($base ?: [], [
            'applies' => true, 'found' => true, 'source' => 'research', 'type' => $type, 'confidence' => 'research',
            'brand' => ! empty($research['company']) ? (string) $research['company'] : ($base['brand'] ?? null),
            'soakHours' => (int) $soak[0], 'soakRange' => [(int) $soak[0], (int) $soak[1]],
            'refreshEveryHours' => $refresh ? (int) $refresh : null, 'refreshPlanHours' => $refresh ? (int) $refresh : 6,
            'refreshNote' => $refresh ? null : 'No water change schedule was found for this variety. In still water, PhilRice advises changing it every 6 hours.',
            'incubateHours' => (int) $inc[0], 'incubateRange' => [(int) $inc[0], (int) $inc[1]],
            'say' => 'From Anee\'s research on this variety.' . ($notes !== '' ? ' ' . $notes : '') . ' The seed bag comes first if it says otherwise.',
            'research' => ['status' => 'found'] + $facts,
        ]);
        $out['floatRule'] = $type === 'hybrid' ? 'Never throw away hybrid seeds that float. They are lighter but they still grow.' : null;

        return $out;
    }

    /** The seed preparation from our own books, before any research. */
    private static function seedPrepBase(string $crop, string $method, string $variety, string $seedType): array
    {
        if (! str_starts_with($crop, 'rice')) {
            $material = in_array($method, ['cuttings', 'cloves', 'setts', 'suckers', 'tissue', 'grafted'], true);

            return ['applies' => ! $material, 'found' => false, 'source' => 'none',
                'say' => $material ? 'No seed to prepare: the planting material steps are in the plan.'
                    : 'There is no researched seed preparation for ' . ($variety !== '' ? $variety : FieldCatalogue::cropWord($crop)) . '. Follow the instructions on the seed bag or from the seller.'];
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
        $ev[] = ['at' => $sow->format('Y-m-d H:i'), 'kind' => 'sow', 'what' => 'Check the sprouts and sow', 'how' => (($prep['sowWhen'] ?? '') ?: 'Sow when most seeds show a small white root.') . ' If they are not ready, wait: up to ' . $prep['incubateRange'][1] . ' hours of incubation is normal, longer in cool weather.'];

        return $ev;
    }

    /* ============================================================ the crew */

    /**
     * Work for one step on one lot: [kind machine|manual|null, units, the
     * kind of machine work, done by hand for want of a machine]. A machine
     * step's units are hectares (slowed by heavy or stony soil), a manual
     * one's person days.
     */
    private static function workFor(string $key, float $area, array $units, array $soil, ?array $own = null): array
    {
        $w = $own ?? (self::WORK[$key] ?? null);
        if (! $w) {
            return [null, 0, null, false];
        }
        $slow = ['clay' => 1.15, 'heavy_clay' => 1.35, 'sandy' => 0.85][$soil['texture'] ?? ''] ?? 1.0;
        if (($soil['rocks'] ?? '') === 'many') {
            $slow *= 1.25;
        }
        if ($w[0] === 'machine') {
            $cap = (string) $w[1];
            $eff = $area * $slow * ($key === 'first_plow' ? 1.5 : 1.0);
            if (! array_filter($units, fn ($u) => isset($u['rates'][$cap]))) {
                return ['manual', round(max(0.5, (self::HAND_RATES[$cap] ?? 10) * $eff), 2), $cap, true];
            }

            return ['machine', round(max(0.001, $eff), 4), $cap, false];
        }

        return ['manual', round(max(0.25, (float) $w[1] * $area * ($w[1] >= 5 ? $slow : 1)), 2), null, false];
    }

    /**
     * Lay every lot's crew work on the calendar.
     *
     * $stepsByLot: each lot's own steps, by lot index; $lots: [{name, area
     * (ha), planting (Y-m-d), soil}]; $crew: {workers [{name, days}], fleet
     * [{type, own, hired}], stagger}. Returns per lot each step's dates, the
     * crew calendar day by day, the conflicts, the planting days, any
     * planting day moved, and the first day each hired machine is needed.
     */
    public static function schedule(array $stepsByLot, array $lots, array $crew, array $case, ?string $today = null): array
    {
        $today = $today ?: now('Asia/Manila')->toDateString();
        $crew = self::crew($crew);
        $lots = array_values($lots);
        $plantings = array_map(fn ($l) => (string) $l['planting'], $lots);
        $asked = $plantings;
        $shift = array_fill(0, count($lots), 0);
        $run = function (array $plantings, ?array $only = null) use ($stepsByLot, $lots, $crew, $case, $today): array {
            $ls = [];
            $ps = [];
            foreach ($lots as $i => $l) {
                if ($only === null || in_array($i, $only, true)) {
                    $ls[] = $l + ['idx' => $i];
                    $ps[] = $plantings[$i];
                }
            }
            $r = self::simulate($stepsByLot, $ls, $ps, $crew, $case, $today);
            if ($r['conflicts']) {
                // A tight crew may start the work that waits for nobody (dikes, clearing) a little early.
                $early = self::simulate($stepsByLot, $ls, $ps, $crew, $case, $today, true);
                if (array_sum($early['late']) < array_sum($r['late'])) {
                    $r = $early;
                }
            }

            return $r;
        };
        /* Staggering, when the farmer allows it: lot by lot in planting
           order, each takes the smallest move (never more than 45 days)
           that lets the crew finish it and every lot before it on time, or
           the move that leaves the least lateness when none does. A move
           found between two of the steps is narrowed to the very day.
           When the farmer set only the first lot's day and let Anee time
           the others (dateMode "first", 2026-10-16, the owner), that lot
           keeps its day: work it cannot finish is shown late instead. */
        if (! empty($crew['stagger']) && count($lots) > 0) {
            $order = array_keys($lots);
            usort($order, fn ($a, $b) => [$asked[$a], $a] <=> [$asked[$b], $b]);
            $pinned = ($case['dateMode'] ?? null) === 'first' ? $order[0] : null;
            $placed = [];
            foreach ($order as $i) {
                $placed[] = $i;
                $lateBy = function (int $by) use (&$plantings, $asked, $i, $placed, $run): int {
                    $try = $plantings;
                    $try[$i] = Carbon::parse($asked[$i])->addDays($by)->toDateString();

                    return array_sum($run($try, $placed)['late']);
                };
                $best = 0;
                $bestLate = null;
                $prev = 0;
                foreach ($i === $pinned ? [0] : [0, 1, 2, 3, 4, 5, 7, 10, 14, 21, 30, 45] as $by) {
                    $late = $lateBy($by);
                    if ($bestLate === null || $late < $bestLate) {
                        $best = $by;
                        $bestLate = $late;
                    }
                    if ($late === 0) {
                        // The first day in between that also works.
                        for ($d = $prev + 1; $d < $by; $d++) {
                            if ($lateBy($d) === 0) {
                                $best = $d;
                                break;
                            }
                        }
                        break;
                    }
                    $prev = $by;
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

    private static function simulate(array $stepsByLot, array $lots, array $plantings, array $crew, array $case, string $today, bool $early = false): array
    {
        $d0 = fn (string $iso) => (int) floor(Carbon::parse($iso, 'UTC')->timestamp / 86400);
        $iso = fn (int $n) => gmdate('Y-m-d', $n * 86400);
        $wd = fn (int $n) => (int) gmdate('w', $n * 86400);
        $T = $d0($today);
        // A day's hands are the workers who work that weekday.
        $cap = array_fill(0, 7, 0);
        foreach ($crew['workers'] as $w) {
            foreach ($w['days'] as $d) {
                $cap[(int) $d]++;
            }
        }
        $most = max(1, max($cap));
        $workDays = count(array_filter($cap));
        $units = self::units($crew['fleet']);

        // The jobs: one per crew step per lot.
        $jobs = [];
        $lotKeys = [];
        foreach ($lots as $i => $lot) {
            $idx = $lot['idx'] ?? $i;
            $steps = $stepsByLot[$idx] ?? [];
            $lotKeys[$i] = array_column($steps, 'key');
            $P = $d0($plantings[$i]);
            $area = max(0.001, (float) $lot['area']);
            foreach ($steps as $s) {
                if (isset($s['lots']) && is_array($s['lots']) && $s['lots'] && ! in_array($idx, $s['lots'], true)) {
                    continue;
                }
                [$kind, $amount, $capKey, $byHand] = self::workFor($s['key'], $area, $units, (array) ($lot['soil'] ?? []), isset($s['work']) && is_array($s['work']) ? $s['work'] : null);
                if (! $kind || in_array($s['key'], self::PLANT_KEYS, true) || $s['key'] === 'pull') {
                    continue;
                }
                // Work longer than its window starts early enough to end on time.
                if ($kind === 'machine') {
                    $perDay = 0.0;
                    foreach ($units as $u) {
                        if (isset($u['rates'][$capKey])) {
                            $perDay += 1 / $u['rates'][$capKey];
                        }
                    }
                    $base = $amount / max(0.001, $perDay);
                } else {
                    $base = $amount / $most;
                }
                $span = (int) ceil($base) + (($workDays < 7 && $base > 1) ? 1 : 0);
                $es = min($P + (int) $s['from'], $P + (int) $s['to'] - $span + 1);
                $jobs[] = ['lot' => $i, 'key' => $s['key'], 'kind' => $kind, 'cap' => $capKey, 'byHand' => $byHand, 'units' => $amount, 'left' => $amount,
                    'es' => $es - ($early ? (self::EARLY[$s['key']] ?? 0) : 0), 'le' => $P + (int) $s['to'], 'start' => null, 'end' => null, 'days' => [], 'used' => []];
            }
        }
        $byKey = [];
        foreach ($jobs as $j => $job) {
            $byKey[$job['lot']][$job['key']] = $j;
        }
        $calendar = [];
        $firstHired = [];
        // What the hired machines did, for the bill: days on the field and hectares of passes, per lot.
        $hiredUse = [];
        if ($jobs && array_sum($cap) > 0) {
            $from = max(min(array_column($jobs, 'es')), $T);
            $until = max(array_column($jobs, 'le')) + 120;
            for ($d = $from; $d <= $until; $d++) {
                $open = array_filter(array_keys($jobs), fn ($j) => $jobs[$j]['left'] > 0.0005);
                if (! $open) {
                    break;
                }
                $people = $cap[$wd($d)];
                if ($people < 1) {
                    continue;   // nobody works this weekday
                }
                // Ready: its window has opened (never before today) and what comes before it is done, with its gap.
                $ready = [];
                foreach ($open as $j) {
                    $job = $jobs[$j];
                    if (max($job['es'], $T) > $d) {
                        continue;
                    }
                    $after = self::AFTER[$job['key']] ?? null;
                    if ($after && in_array($after[0], $lotKeys[$job['lot']], true) && isset($byKey[$job['lot']][$after[0]])) {
                        $pre = $jobs[$byKey[$job['lot']][$after[0]]];
                        // A squeezed lot waits less between passes.
                        $gs = (float) ($lots[$job['lot']]['gapScale'] ?? 1);
                        $gap = $gs < 1 ? min($after[1], max(1, (int) floor($after[1] * $gs))) : $after[1];
                        if ($pre['left'] > 0.0005 || $pre['end'] === null || $pre['end'] + $gap > $d) {
                            continue;
                        }
                    }
                    $ready[] = $j;
                }
                // Most urgent first, machine and hand work alike.
                usort($ready, fn ($a, $b) => [$jobs[$a]['le'], $jobs[$a]['lot'], $jobs[$a]['es']] <=> [$jobs[$b]['le'], $jobs[$b]['lot'], $jobs[$b]['es']]);
                $free = array_keys($units);
                $served = [];
                $items = [];
                foreach ($ready as $j) {
                    if ($jobs[$j]['kind'] === 'machine') {
                        // Every free machine able to do it, the fastest first; an owned one needs a driver.
                        $c = $jobs[$j]['cap'];
                        $used = [];
                        $drivers = 0;
                        while ($jobs[$j]['left'] > 0.0005) {
                            $best = null;
                            foreach ($free as $fi => $u) {
                                if (! isset($units[$u]['rates'][$c]) || (! $units[$u]['hired'] && $people < 1)) {
                                    continue;
                                }
                                if ($best === null || $units[$u]['rates'][$c] < $units[$free[$best]]['rates'][$c]) {
                                    $best = $fi;
                                }
                            }
                            if ($best === null) {
                                break;
                            }
                            $u = $free[$best];
                            unset($free[$best]);
                            if (! $units[$u]['hired']) {
                                $people--;
                                $drivers++;
                            } else {
                                $firstHired[$units[$u]['type']] ??= ['day' => $d, 'lot' => $jobs[$j]['lot'], 'key' => $jobs[$j]['key']];
                                $ty = $units[$u]['type'];
                                $lt = $jobs[$j]['lot'];
                                $did = min($jobs[$j]['left'], 1 / $units[$u]['rates'][$c]);
                                $hiredUse[$ty]['days'] = ($hiredUse[$ty]['days'] ?? 0) + 1;
                                $hiredUse[$ty]['ha'] = ($hiredUse[$ty]['ha'] ?? 0) + $did;
                                $hiredUse[$ty]['lots'][$lt]['days'] = ($hiredUse[$ty]['lots'][$lt]['days'] ?? 0) + 1;
                                $hiredUse[$ty]['lots'][$lt]['ha'] = ($hiredUse[$ty]['lots'][$lt]['ha'] ?? 0) + $did;
                            }
                            $jobs[$j]['left'] = round(max(0, $jobs[$j]['left'] - 1 / $units[$u]['rates'][$c]), 4);
                            $used[] = $units[$u]['type'];
                        }
                        if ($used) {
                            $served[$j] = true;
                            $jobs[$j]['start'] ??= $d;
                            $jobs[$j]['end'] = $d;
                            $jobs[$j]['days'][] = $d;
                            $jobs[$j]['used'] = array_values(array_unique(array_merge($jobs[$j]['used'], $used)));
                            $items[] = ['lot' => $jobs[$j]['lot'], 'key' => $jobs[$j]['key'], 'people' => $drivers, 'machine' => true, 'units' => array_values(array_unique($used)), 'n' => count($used)];
                        }
                        continue;
                    }
                    // Hand work leaves a driver for a machine job still waiting for an owned machine.
                    $reserve = 0;
                    foreach ($ready as $j2) {
                        if ($jobs[$j2]['kind'] !== 'machine' || isset($served[$j2]) || $jobs[$j2]['left'] <= 0.0005) {
                            continue;
                        }
                        $ownFree = false;
                        $hireFree = false;
                        foreach ($free as $u) {
                            if (isset($units[$u]['rates'][$jobs[$j2]['cap']])) {
                                $units[$u]['hired'] ? $hireFree = true : $ownFree = true;
                            }
                        }
                        if ($ownFree && ! $hireFree) {
                            $reserve = 1;
                            break;
                        }
                    }
                    $spare = $people - $reserve;
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
                    $items[] = ['lot' => $jobs[$j]['lot'], 'key' => $jobs[$j]['key'], 'people' => $give, 'machine' => false];
                }
                if ($items) {
                    $calendar[$iso($d)] = $items;
                }
            }
        }

        // Each lot's steps with their dates.
        $perLot = [];
        $late = [];
        $conflicts = [];
        foreach ($lots as $i => $lot) {
            $idx = $lot['idx'] ?? $i;
            $steps = $stepsByLot[$idx] ?? [];
            $P = $d0($plantings[$i]);
            $area = max(0.001, (float) $lot['area']);
            $rows = [];
            $actual = [];
            foreach ($steps as $s) {
                if (isset($s['lots']) && is_array($s['lots']) && $s['lots'] && ! in_array($idx, $s['lots'], true)) {
                    continue;
                }
                $row = ['key' => $s['key']];
                if (isset($byKey[$i][$s['key']])) {
                    $job = $jobs[$byKey[$i][$s['key']]];
                    $row['crew'] = $job['kind'];
                    $row['units'] = $job['units'];
                    if ($job['used']) {
                        $row['machines'] = $job['used'];
                    }
                    if ($job['byHand']) {
                        $row['byHand'] = true;
                    }
                    if ($job['start'] !== null) {
                        $row['start'] = $iso($job['start']);
                        $row['end'] = $iso($job['end']);
                        $row['workDays'] = count($job['days']);
                        $actual[$s['key']] = [$job['start'], $job['end']];
                        $lateBy = $job['left'] > 0.0005 ? 999 : max(0, $job['end'] - $job['le']);
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
            // Nothing is dated before today (an anchor near today can pull a step behind it).
            foreach ($rows as $k => $r) {
                if (! isset($r['crew']) && $r['from'] < $T && $r['to'] >= $T - 400) {
                    $rows[$k]['from'] = $T;
                    $rows[$k]['to'] = max($r['to'], $T);
                }
            }
            $out = [];
            foreach ($rows as $k => $r) {
                $o = ['key' => $k, 'winFrom' => $iso($r['from']), 'winTo' => $iso($r['to'])];
                foreach (['crew', 'units', 'machines', 'byHand', 'start', 'end', 'workDays', 'late', 'lateWhy', 'planting'] as $f) {
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

        // The planting days: how many hands each takes, by the lot's own crop and method.
        $plantDays = [];
        foreach ($lots as $i => $lot) {
            $method = (string) (($lot['method'] ?? '') ?: ($case['method'] ?? ''));
            $group = self::groupFor((string) (($lot['crop'] ?? '') ?: ($case['crop'] ?? '')));
            $area = max(0.001, (float) $lot['area']);
            foreach ($stepsByLot[$lot['idx'] ?? $i] ?? [] as $s) {
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

        return ['perLot' => $perLot, 'calendar' => $calendar, 'conflicts' => $conflicts, 'plantDays' => $plantDays,
            'hiredFirst' => array_map(fn ($x) => ['day' => $iso($x['day']), 'lot' => $x['lot'], 'key' => $x['key']], $firstHired),
            'hiredUse' => $hiredUse,
            'late' => $late + array_fill(0, count($lots), 0)];
    }

    /**
     * WILL EVERY LOT BE READY ON ITS DAY? (2026-10-16, the owner: "show
     * warnings if there might be issues on the # of workers later on based
     * on what was chosen, or the # of days of the preparation, especially if
     * user chooses the specific day per lot".) The crew calendar run without
     * Anee, as the plan will run it. For each lot: the day it was asked, the
     * day it gets (Anee's, for the lots after the first when the farmer set
     * only the first lot's day), the work that ends after that day, and the
     * days it is short of the full preparation.
     *
     * When work runs late: `more`, the fewest workers (Monday to Saturday)
     * to add for nothing to run late, the days set the farmer's way; when no
     * count is enough, `everyDay`, the workers to add when the whole crew
     * also works Sundays (0: the crew as it is); whether machines hold the
     * work up (more hands do not help then); and, for a lot that keeps a day
     * it cannot make, `readyBy`, the day this crew could have it ready
     * instead. When Anee spaced the lots and nothing is late, `closer`, the
     * workers to add for every lot to go in on the first lot's day.
     */
    public static function forecast(array $case, ?string $today = null): array
    {
        $f = self::build($case);
        $case = $f['case'];
        $sched = $f['sched'];
        $lotSteps = $f['lotSteps'];
        $n = count($case['lots']);
        $mode = $n < 2 ? 'one' : (($case['dateMode'] ?? null) === 'first' ? 'first' : 'each');
        // The work that ends after the day, by the crew (not the work whose time has already passed).
        $lateOf = function (array $sched): array {
            $out = [];
            foreach ($sched['conflicts'] as $c) {
                if ($c['why'] === 'crew' && $c['major'] !== false) {
                    $out[$c['lot']][] = $c;
                }
            }

            return $out;
        };
        $late = $lateOf($sched);
        $what = [];
        foreach ($lotSteps as $i => $steps) {
            foreach ($steps as $s) {
                $what[$i][$s['key']] = $s['what'];
            }
        }
        $machine = [];
        foreach ($sched['perLot'] as $pl) {
            foreach ($pl['steps'] as $r) {
                if (($r['crew'] ?? null) === 'machine') {
                    $machine[$pl['lot']][$r['key']] = true;
                }
            }
        }
        // A lot that keeps a day it cannot make: the day the crew could have it ready.
        $readyBy = [];
        $keeps = array_filter(array_keys($late), fn ($i) => $mode !== 'first' || (int) $sched['shift'][$i] === 0);
        if ($keeps) {
            $alt = self::schedule($lotSteps, $case['lots'], array_merge($case['crew'], ['stagger' => true]), array_merge($case, ['dateMode' => null]), $today);
            $altLate = $lateOf($alt);
            foreach ($keeps as $i) {
                if ((int) $alt['shift'][$i] > 0 && empty($altLate[$i])) {
                    $readyBy[$i] = $alt['plantings'][$i];
                }
            }
        }
        // The fewest workers to add (from the list) for which the crew calendar has nothing late, found by
        // halving (a first lot day plan staggers on every try, so each try costs a full stagger).
        $onTime = fn (array $crew): bool => ! $lateOf(self::schedule($lotSteps, $case['lots'], $crew, $case, $today));
        $fewest = function (array $list, callable $crewFor) use ($onTime): ?int {
            if (! $onTime($crewFor(end($list)))) {
                return null;
            }
            [$lo, $hi] = [0, count($list) - 1];
            while ($lo < $hi) {
                $mid = intdiv($lo + $hi, 2);
                $onTime($crewFor($list[$mid])) ? $hi = $mid : $lo = $mid + 1;
            }

            return $list[$hi];
        };
        $added = function (int $k, bool $everyDay = false) use ($case): array {
            $crew = $case['crew'];
            if ($everyDay) {
                foreach ($crew['workers'] as $w => $x) {
                    $crew['workers'][$w]['days'] = [0, 1, 2, 3, 4, 5, 6];
                }
            }
            for ($w = 1; $w <= $k; $w++) {
                $crew['workers'][] = ['name' => 'Added ' . $w, 'days' => $everyDay ? [0, 1, 2, 3, 4, 5, 6] : [1, 2, 3, 4, 5, 6]];
            }

            return $crew;
        };
        // Workers to add, Monday to Saturday, for nothing to run late with the days set the farmer's way
        // (Anee still times the lots after the first when the farmer let her).
        $more = $late ? $fewest([1, 2, 3, 4, 5, 6, 8, 10, 12, 15, 20], fn ($k) => $added($k)) : null;
        // No count of Monday to Saturday hands is enough: is it the Sunday? (A Monday planting puts the leveling on one.)
        $everyDay = $late && $more === null ? $fewest([0, 2, 4, 6, 10, 20], fn ($k) => $added($k, true)) : null;
        // Anee spaced the lots: the workers that would let every lot go in on the first lot's day.
        $closer = $mode === 'first' && ! $late && array_filter($sched['shift'])
            ? $fewest([1, 2, 3, 4, 5, 6, 8, 10, 12, 15, 20], fn ($k) => array_merge($added($k), ['stagger' => false])) : null;
        $lots = [];
        foreach ($case['lots'] as $i => $l) {
            $rows = $late[$i] ?? [];
            usort($rows, fn ($a, $b) => ($b['late'] ?? 999) <=> ($a['late'] ?? 999));
            $sq = $f['squeeze'][$i] ?? null;
            $lots[] = [
                'lot' => $i, 'name' => $l['name'], 'dayWord' => $l['dayWord'] ?? self::dayWord($case['crop'], $case['method']),
                'asked' => $sched['asked'][$i], 'planting' => $sched['plantings'][$i], 'shift' => (int) $sched['shift'][$i],
                'late' => array_map(fn ($c) => ['key' => $c['key'], 'what' => $what[$i][$c['key']] ?? $c['key'], 'days' => $c['late'], 'machine' => ! empty($machine[$i][$c['key']])], array_slice($rows, 0, 3)),
                'lateCount' => count($rows),
                'short' => $sq ? ['left' => (int) $sq['left'], 'needed' => (int) $sq['needed']] : null,
                'readyBy' => $readyBy[$i] ?? null,
            ];
        }
        $anyMachine = (bool) array_filter($lots, fn ($l) => array_filter($l['late'], fn ($x) => $x['machine']));

        return [
            'mode' => $mode,
            'ok' => ! $late && ! array_filter($lots, fn ($l) => $l['short']),
            'late' => (bool) $late,
            'workers' => count($case['crew']['workers']),
            'more' => $more,
            'everyDay' => $everyDay,
            'closer' => $closer,
            'machines' => $anyMachine && $more === null && $everyDay === null,
            'lots' => $lots,
        ];
    }

    /**
     * WHAT THE PLAN COSTS (2026-10-16, the owner: "add the costings based on
     * the costing in the wizard so that user will know"), from the pay and
     * the service prices the farmer gave, nothing guessed:
     *
     *  - workers: each day of the crew calendar takes its people from the
     *    workers of that weekday in the order they were listed (worker 1
     *    first), a driver of an owned machine included; a worker's day is
     *    their day pay, or twice their half day pay;
     *  - the planting days: the people they need, at the average day pay of
     *    the workers who have one;
     *  - each hired machine: its price a hectare (every pass counts), a day
     *    on the field, or one price for the whole job; a planting machine
     *    covers the lots' area on their planting days.
     *
     * Per lot and in all. A worker or a machine with no price is counted in
     * days only and named in `missing`, so the total never pretends.
     *
     * @param  array  $lots  [{name, area (ha)}] in the schedule's order
     */
    public static function costs(array $crew, array $sched, array $lots): array
    {
        $n = count($lots);
        $per = array_fill(0, $n, ['labor' => 0.0, 'planting' => 0.0, 'hired' => 0.0]);
        $people = [];
        foreach ($crew['workers'] as $i => $w) {
            $rate = isset($w['pay']) ? (float) $w['pay'] * (($w['payUnit'] ?? 'day') === 'half' ? 2 : 1) : null;
            $people[$i] = ['name' => $w['name'], 'pay' => $w['pay'] ?? null, 'payUnit' => $w['payUnit'] ?? null, 'rate' => $rate, 'days' => 0, 'cost' => $rate === null ? null : 0.0];
        }
        foreach ((array) ($sched['calendar'] ?? []) as $date => $items) {
            $wd = (int) gmdate('w', strtotime($date . ' 00:00:00 UTC'));
            $free = array_values(array_filter(array_keys($crew['workers']), fn ($i) => in_array($wd, $crew['workers'][$i]['days'], true)));
            foreach ($items as $it) {
                $lot = (int) ($it['lot'] ?? 0);
                for ($k = 0; $k < (int) ($it['people'] ?? 0) && $free; $k++) {
                    $i = array_shift($free);
                    $people[$i]['days']++;
                    if ($people[$i]['rate'] !== null) {
                        $people[$i]['cost'] += $people[$i]['rate'];
                        if (isset($per[$lot])) {
                            $per[$lot]['labor'] += $people[$i]['rate'];
                        }
                    }
                }
            }
        }
        $rates = array_values(array_filter(array_column($people, 'rate'), fn ($r) => $r !== null));
        $avg = $rates ? array_sum($rates) / count($rates) : null;
        $plantPeople = 0;
        $plantDates = [];
        foreach ((array) ($sched['plantDays'] ?? []) as $p) {
            $plantPeople += (int) $p['people'];
            $plantDates[(int) $p['lot']][$p['date']] = true;
            if ($avg !== null && isset($per[(int) $p['lot']])) {
                $per[(int) $p['lot']]['planting'] += (int) $p['people'] * $avg;
            }
        }

        $items = (array) (self::equipmentBook()['items'] ?? []);
        $use = (array) ($sched['hiredUse'] ?? []);
        $area = array_map(fn ($l) => max(0.0, (float) ($l['area'] ?? 0)), array_values($lots));
        $hired = [];
        foreach ($crew['fleet'] as $f) {
            if ((int) $f['hired'] < 1) {
                continue;
            }
            $u = $use[$f['type']] ?? [];
            $lotsUse = [];
            if (! empty($items[$f['type']]['plants'])) {
                // A seeder or a transplanter works the planting days, outside the crew calendar.
                foreach ($area as $i => $a) {
                    $lotsUse[$i] = ['ha' => $a, 'days' => max(1, count($plantDates[$i] ?? []))];
                }
            } else {
                foreach ((array) ($u['lots'] ?? []) as $i => $x) {
                    $lotsUse[(int) $i] = ['ha' => (float) $x['ha'], 'days' => (int) $x['days']];
                }
            }
            $ha = array_sum(array_column($lotsUse, 'ha'));
            $days = array_sum(array_column($lotsUse, 'days'));
            $row = ['type' => $f['type'], 'name' => (string) ($items[$f['type']]['name'] ?? $f['type']), 'count' => (int) $f['hired'],
                'who' => $f['hire']['name'] ?? null, 'phone' => $f['hire']['phone'] ?? null,
                'ha' => round($ha, 2), 'days' => $days, 'amount' => $f['cost'] ?? null, 'unit' => $f['costUnit'] ?? null, 'cost' => null];
            if (isset($f['cost'])) {
                $unit = $f['costUnit'] ?? 'ha';
                $cost = $unit === 'job' ? (float) $f['cost'] : (float) $f['cost'] * ($unit === 'day' ? $days : $ha);
                $row['cost'] = round($cost, 2);
                // Each lot's share: by its hectares (a whole job and a hectare price) or its days.
                $basis = $unit === 'day' ? $days : $ha;
                if ($basis <= 0 && $unit === 'job') {
                    // A whole job price with no work on the calendar: shared by the lots' size.
                    $lotsUse = array_map(fn ($a) => ['ha' => $a, 'days' => 0], $area);
                    $basis = array_sum($area);
                }
                foreach ($lotsUse as $i => $x) {
                    if ($basis > 0 && isset($per[$i])) {
                        $per[$i]['hired'] += $cost * (($unit === 'day' ? $x['days'] : $x['ha']) / $basis);
                    }
                }
            }
            $hired[] = $row;
        }

        $labor = $rates ? array_sum(array_filter(array_column($people, 'cost'), fn ($c) => $c !== null)) : null;
        $planting = $avg !== null && $plantPeople > 0 ? $plantPeople * $avg : null;
        $hiredCost = array_sum(array_filter(array_column($hired, 'cost'), fn ($c) => $c !== null));
        $total = (float) ($labor ?? 0) + (float) ($planting ?? 0) + $hiredCost;
        $perLot = [];
        foreach ($per as $i => $p) {
            $perLot[] = ['lot' => $i, 'labor' => round($p['labor'], 2), 'planting' => round($p['planting'], 2), 'hired' => round($p['hired'], 2),
                'total' => round($p['labor'] + $p['planting'] + $p['hired'], 2), 'area' => $area[$i] ?? 0];
        }
        $sumArea = array_sum($area);

        return [
            'workers' => array_map(fn ($p) => ['name' => $p['name'], 'pay' => $p['pay'], 'payUnit' => $p['payUnit'], 'days' => $p['days'],
                'cost' => $p['cost'] === null ? null : round($p['cost'], 2)], array_values($people)),
            'labor' => $labor === null ? null : round($labor, 2),
            'planting' => $plantPeople > 0 ? ['people' => $plantPeople, 'rate' => $avg === null ? null : round($avg, 2), 'cost' => $planting === null ? null : round($planting, 2)] : null,
            'hired' => $hired,
            'perLot' => $perLot,
            'total' => round($total, 2),
            'perHa' => $sumArea > 0 && $total > 0 ? round($total / $sumArea, 2) : null,
            'missing' => [
                'pay' => count(array_filter($people, fn ($p) => $p['rate'] === null && $p['days'] > 0)),
                'hired' => count(array_filter($hired, fn ($h) => $h['cost'] === null)),
            ],
            'priced' => $total > 0,
        ];
    }

    /**
     * A "book it" reminder for every hired machine, a week before the day
     * the calendar first needs it (never before today), on the lot that
     * needs it first: a step in that lot's plan and a dated row, and the
     * list of bookings for the whole farm.
     */
    private static function bookings(array $lotSteps, array $sched, array $case): array
    {
        $items = (array) (self::equipmentBook()['items'] ?? []);
        $today = now('Asia/Manila')->toDateString();
        $lots = $case['lots'];
        $need = [];
        foreach ($sched['hiredFirst'] ?? [] as $type => $f) {
            $need[$type] = $f;
        }
        // Planting machines are needed on the first planting day of the lots planted that way.
        foreach ($case['crew']['fleet'] as $f) {
            if (! empty($items[$f['type']]['plants']) && $f['hired'] > 0 && $lots) {
                $for = $items[$f['type']]['for'] ?? 'all';
                $first = null;
                foreach ($sched['plantings'] as $i => $p) {
                    $m = (string) self::forLot($case, $i)['method'];
                    if (is_array($for) && ! in_array($m, $for, true)) {
                        continue;
                    }
                    if ($first === null || $p < $sched['plantings'][$first]) {
                        $first = $i;
                    }
                }
                if ($first !== null) {
                    $need[$f['type']] = ['day' => $sched['plantings'][$first], 'lot' => $first, 'key' => null];
                }
            }
        }
        $bookings = [];
        foreach ($need as $type => $f) {
            $i = (int) $f['lot'];
            if (! isset($lots[$i])) {
                continue;
            }
            $use = Carbon::parse($f['day'], 'UTC');
            $by = $use->copy()->subDays(7);
            if ($by->toDateString() < $today) {
                $by = Carbon::parse($today, 'UTC');
            }
            $P = Carbon::parse($sched['plantings'][$i], 'UTC');
            $off = (int) $P->diffInDays($by, false);
            $word = self::machineWord($type);
            $count = 0;
            foreach ($case['crew']['fleet'] as $x) {
                if ($x['type'] === $type) {
                    $count = (int) $x['hired'];
                }
            }
            $forWhat = '';
            if ($f['key']) {
                foreach ($lotSteps[$i] as $s) {
                    if ($s['key'] === $f['key']) {
                        $forWhat = mb_strtolower((string) $s['what']);
                    }
                }
            }
            $how = 'Call the operator now and fix the days. It is first needed on ' . $use->format('D M j') . ($forWhat !== '' ? ' (' . $forWhat . ', ' . $lots[$i]['name'] . ')' : ', the ' . self::dayWord((string) self::forLot($case, $i)['crop'], (string) self::forLot($case, $i)['method']) . ' day')
                . '. Operators are busiest when the whole area plants together, so book early.';
            $step = self::step('book_' . $type, $off, $off, 'Book the hired ' . $word, $how, 'anee', null, 'crew');
            $step['type'] = 'other';
            $lotSteps[$i] = array_values(array_filter($lotSteps[$i], fn ($x) => $x['key'] !== $step['key']));
            $lotSteps[$i][] = $step;
            usort($lotSteps[$i], fn ($a, $b) => [$a['from'], $a['to']] <=> [$b['from'], $b['to']]);
            foreach ($sched['perLot'] as $k => $pl) {
                if ($pl['lot'] === $i) {
                    $sched['perLot'][$k]['steps'][] = ['key' => $step['key'], 'winFrom' => $by->toDateString(), 'winTo' => $by->toDateString(), 'booking' => true];
                    usort($sched['perLot'][$k]['steps'], fn ($a, $b) => [($a['start'] ?? $a['winFrom']), $a['winTo']] <=> [($b['start'] ?? $b['winFrom']), $b['winTo']]);
                }
            }
            $bookings[] = ['type' => $type, 'name' => (string) ($items[$type]['name'] ?? $type), 'count' => $count, 'use' => $use->toDateString(), 'by' => $by->toDateString(), 'lot' => $i, 'for' => $forWhat];
        }
        usort($bookings, fn ($a, $b) => $a['by'] <=> $b['by']);

        return [$lotSteps, $sched, $bookings];
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
