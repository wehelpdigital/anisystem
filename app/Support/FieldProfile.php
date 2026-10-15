<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A FIELD PROFILE (2026-10-15): one lot's soil, water and history, as a
 * farmer can see and feel them, asked once and reused by every analysis
 * that needs them (the Land Prep Planner first; When to Plant, What to
 * Plant, Variety Research, Crop Protocol, NPK Plus, Satellite Analysis and
 * Anee Auto next).
 *
 * The single question bank. A profile is a flat map of answers:
 *   texture, color, organic, rocks            (page soil)
 *   water[] drainage flooding salt            (page water)
 *   acidity phFrom phTo lastLimed hardpan slope (page acid)
 *   prevCrop harvested stubble lastPlowed newLand (page last)
 *   weeds[] level                             (page weeds)
 *   outbreaks[]  (pest:slug, disease:slug, other:key, or none) (page problems)
 *
 * The page shows it with the shared partial partials/field-profile
 * (window.aneeFieldProfile); FieldProfileController lists and saves the
 * farmer's profiles (as_field_profiles); legacy() maps a profile onto the
 * older fields the other analyses already read.
 */
final class FieldProfile
{
    /** The pages (groups), in order: key => [title, the line under it]. */
    public const GROUPS = [
        'soil' => ['The soil', 'What you see and feel in this lot. Skip what you do not know.'],
        'water' => ['The water', 'Where the water comes from and where it goes.'],
        'acid' => ['Acidity and the land', 'From a soil test, or from what you know of this lot.'],
        'last' => ['Last season', 'What grew here and what is left of it.'],
        'weeds' => ['Weeds and the field', 'What came up last season.'],
        'problems' => ['Pests, diseases and other troubles', 'Pick everything that hurt the last crop.'],
    ];

    /**
     * The questions: key => q (the question), why (one plain line on why it
     * is asked), short (its name on a summary), in (soil or history: where
     * an analysis that splits them keeps it), page, o (value => [label,
     * line, 'rice' when the option is for rice only]), only ('rice' for a
     * rice only question), multi (several answers), none (the answer that
     * clears the others), ph (a known pH range rides with it), sheet (the
     * answers come from the troubles sheet).
     */
    public const QUESTIONS = [
        'texture' => ['in' => 'soil', 'page' => 'soil', 'short' => 'Soil', 'q' => 'How does the soil feel when you squeeze it wet?',
            'why' => 'Clay holds water and needs a longer soak. Sand drains fast and dries out.', 'o' => [
                'sandy' => ['Gritty and loose', 'Falls apart in the hand (galas, sandy)'],
                'loam' => ['Soft and crumbly', 'Holds a loose ball (loam)'],
                'clay' => ['Sticky and heavy', 'Makes a ribbon between the fingers (lagkitin, clay)'],
                'heavy_clay' => ['Heavy clay that cracks', 'Opens wide cracks when it dries'],
            ]],
        'color' => ['in' => 'soil', 'page' => 'soil', 'short' => 'Color', 'q' => 'What color is the topsoil?',
            'why' => 'The color hints at drainage and acidity. Gray soil has stayed wet for a long time.', 'o' => [
                'dark' => ['Dark brown or black', ''], 'brown' => ['Brown', ''], 'red' => ['Red or yellow', ''],
                'gray' => ['Gray or bluish', 'Often wet'], 'pale' => ['Pale or whitish', ''],
            ]],
        'organic' => ['in' => 'soil', 'page' => 'soil', 'short' => 'Organic matter', 'q' => 'How much organic matter is in it?',
            'why' => 'Soil with little organic matter holds less water and food, so it needs straw, compost or manure.', 'o' => [
                'low' => ['Little', 'Pale, hard, few worms'], 'medium' => ['Some', ''], 'high' => ['A lot', 'Dark, soft, many worms'],
            ]],
        'rocks' => ['in' => 'soil', 'page' => 'soil', 'short' => 'Stones', 'q' => 'Are there rocks or stones?',
            'why' => 'Stones slow the plow and break the harrow, so many stones are picked out first.', 'o' => [
                'none' => ['None', ''], 'some' => ['Some', ''], 'many' => ['Many', ''],
            ]],
        'water' => ['in' => 'soil', 'page' => 'water', 'short' => 'Water', 'q' => 'Where does the field get its water?', 'multi' => true, 'none' => 'rainfed',
            'why' => 'The water decides when you can soak and plow. Pick every source this lot uses.', 'o' => [
                'irrigation' => ['Irrigation canal', 'National or communal system'], 'pump' => ['Pump or shallow well', ''],
                'river' => ['River, creek or spring', 'Led in by a small canal'], 'reservoir' => ['Small farm pond', 'A reservoir filled in the rains'],
                'rainfed' => ['Rain only', 'No other water'],
            ]],
        'drainage' => ['in' => 'soil', 'page' => 'water', 'short' => 'Drainage', 'q' => 'After heavy rain, how fast does the water go?',
            'why' => 'A field that stays wet drowns sown seed and bogs down the machines.', 'o' => [
                'fast' => ['Soaks in fast', 'Gone within hours'], 'day' => ['Within a day', 'Or I can drain it in a day'],
                'slow' => ['Slowly', 'Some spots stay wet for days'], 'stands' => ['It stands for days', 'Hard to drain'],
            ]],
        'flooding' => ['in' => 'history', 'page' => 'water', 'short' => 'Floods', 'q' => 'Does the field go under water?',
            'why' => 'A field that floods needs a backup seedbed or high beds, and a variety that can take it.', 'o' => [
                'never' => ['Never', ''], 'sometimes' => ['Sometimes', 'In a strong typhoon'], 'often' => ['Most wet seasons', ''],
            ]],
        'salt' => ['in' => 'soil', 'page' => 'water', 'short' => 'Salt', 'q' => 'Any signs of salt?',
            'why' => 'Salt stunts young plants. A salty field is flushed with good water and gets a salt tolerant variety.', 'o' => [
                'none' => ['None', ''], 'crust' => ['White crust in the dry season', ''], 'sea' => ['Sea water comes in at high tide', ''],
                'tips' => ['White leaf tips and stunted patches', 'On the last crop'], 'sodic' => ['A hard crust that seals', 'Water sits on top (sodium)'],
            ]],
        'acidity' => ['in' => 'soil', 'page' => 'acid', 'short' => 'Acidity', 'q' => 'Is the soil acidic?', 'ph' => true,
            'why' => 'Strongly acidic soil needs lime about a month before planting. A soil test says how much.', 'o' => [
                'untested' => ['Never tested', 'Or not sure'], 'none' => ['Not acidic', 'A test said so'],
                'mild' => ['Mildly acidic', 'pH about 5 to 5.5'], 'strong' => ['Strongly acidic', 'pH below 5'],
                'sulfate' => ['Acid sulfate', 'Coastal or swamp soil with yellow mottles'], 'alkaline' => ['Alkaline or limy', 'pH above 7.5'],
            ]],
        'lastLimed' => ['in' => 'history', 'page' => 'acid', 'short' => 'Last limed', 'q' => 'When was it last limed?',
            'why' => 'Lime lasts a few years. This tells Anee whether to lime again or test first.', 'o' => [
                'never' => ['Never', ''], 'under_year' => ['Within the year', ''], '1to3y' => ['1 to 3 years ago', ''], 'over3y' => ['Over 3 years ago', ''],
            ]],
        'hardpan' => ['in' => 'soil', 'page' => 'acid', 'short' => 'Hard layer', 'q' => 'Is there a hard layer under the plow depth?',
            'why' => 'A hard layer holds the water in a paddy, but it stops the roots of upland crops.', 'o' => [
                'yes' => ['Yes', 'The plow or a bar hits it'], 'no' => ['No', ''],
            ]],
        'slope' => ['in' => 'soil', 'page' => 'acid', 'short' => 'Slope', 'q' => 'How does the land lie?',
            'why' => 'Sloping land needs rows across the slope and strong dikes so the soil stays put.', 'o' => [
                'flat' => ['Flat', ''], 'gentle' => ['Gently sloping', ''], 'steep' => ['Steep', ''], 'terraced' => ['Terraced', ''],
            ]],
        'prevCrop' => ['in' => 'history', 'page' => 'last', 'short' => 'Last crop', 'q' => 'What grew here last?',
            'why' => 'The last crop decides what is left to clear and which pests may carry over.', 'o' => [
                'rice_straw' => ['Rice, straw left on the field', ''], 'rice_no_straw' => ['Rice, straw burned or taken away', ''],
                'corn' => ['Corn or sorghum', ''], 'legume' => ['Mungbean, peanut or soybean', ''], 'vegetable' => ['Vegetables', ''],
                'fallow' => ['Nothing, left to weeds', ''], 'new_land' => ['Newly opened land', 'From grass, bush or forest'],
            ]],
        'harvested' => ['in' => 'history', 'page' => 'last', 'short' => 'Harvested', 'q' => 'When was it harvested?',
            'why' => 'A rice field needs about 30 days with no rice before sowing, so the pests and diseases starve.', 'o' => [
                'under2w' => ['Less than 2 weeks ago', ''], '2to4w' => ['2 to 4 weeks ago', ''], '1to3m' => ['1 to 3 months ago', ''],
                'over3m' => ['Over 3 months ago', ''], 'not_yet' => ['Not harvested yet', ''],
            ]],
        'stubble' => ['in' => 'history', 'page' => 'last', 'short' => 'Left standing', 'q' => 'Is anything left standing?',
            'why' => 'Stubble and ratoon are plowed under early, or they keep the pests alive.', 'o' => [
                'none' => ['Nothing', 'Clean field'], 'stubble' => ['Stubble', ''], 'ratoon' => ['Ratoon or stray rice', 'Rice growing back', 'rice'],
            ]],
        'lastPlowed' => ['in' => 'history', 'page' => 'last', 'short' => 'Last plowed', 'q' => 'When was it last plowed?',
            'why' => 'Ground not plowed for a long time is hard and needs more passes.', 'o' => [
                'under3m' => ['Less than 3 months ago', ''], '3to12m' => ['3 to 12 months ago', ''], 'over_year' => ['Over a year ago', ''], 'never' => ['Never', ''],
            ]],
        'newLand' => ['in' => 'history', 'page' => 'last', 'short' => 'Idle', 'q' => 'Has the field been idle?',
            'why' => 'Idle or new land is cleared and roughly plowed first, before the regular work.', 'o' => [
                'no' => ['No, farmed every season', ''], 'idle' => ['Idle for a few seasons', ''],
            ]],
        'weeds' => ['in' => 'history', 'page' => 'weeds', 'short' => 'Weeds', 'q' => 'How weedy was it last season?', 'multi' => true, 'none' => 'few',
            'why' => 'Weedy rice and tough weeds need extra passes before planting. Pick every one that fits.', 'o' => [
                'few' => ['Few or none', 'The field was mostly clean'], 'many' => ['Many weeds', 'They covered much of the field'],
                'tough' => ['Tough weeds', 'Cogon or grasses that regrow from the roots'], 'sedges' => ['Many sedges', 'Nutsedge, umbrella sedge'],
                'weedy_rice' => ['Weedy rice', 'Ligaw na palay', 'rice'],
            ]],
        'level' => ['in' => 'history', 'page' => 'weeds', 'short' => 'Field level', 'q' => 'Last season, did some spots stay dry while others held deep water?', 'only' => 'rice',
            'why' => 'An uneven field wastes water and drowns seedlings in the low spots, so it gets extra leveling.', 'o' => [
                'level' => ['No, the water spread evenly', ''], 'some' => ['A few high and low spots', ''], 'uneven' => ['Very uneven', ''],
            ]],
        'outbreaks' => ['in' => 'history', 'page' => 'problems', 'short' => 'Troubles', 'q' => 'What troubled the last crop?', 'multi' => true, 'sheet' => true,
            'why' => 'Last season\'s pests and diseases shape the clean up before planting, like a longer rest, a clean seedbed or a rat campaign.', 'o' => []],
    ];

    /**
     * The other troubles of a last season, beside the catalogue's pests and
     * diseases: key => [label, line, icon, kind, only]. `kind` is how the rules
     * read it (a rat is a Rodent wherever it comes from); `only`: null for
     * every crop, 'rice', or 'grain' (rice, corn, sorghum, sugarcane).
     */
    public const OTHER = [
        'rats' => ['Rats', 'Burrows in the dikes, cut tillers or stalks', '🐀', 'Rodent', null],
        'birds' => ['Birds', 'They ate the seed or the grain', '🐦', 'Bird', null],
        'snails' => ['Golden apple snails', 'They ate the young seedlings', '🐌', 'Snail', 'rice'],
        'flood' => ['Flood damage', 'The crop went under water', '🌊', 'Other', null],
        'drought' => ['Drought', 'The field ran short of water', '☀️', 'Other', null],
        'lodging' => ['Lodging', 'The plants fell over before harvest', '🌾', 'Other', 'grain'],
        'salt' => ['Salt damage', 'White leaf tips and stunted patches', '🧂', 'Other', null],
        'zinc' => ['Zinc shortage', 'Dusty brown spots and stunting after planting', '🟤', 'Other', 'grain'],
        'nutrient' => ['Pale or yellow crop', 'Short of nitrogen or other plant food', '🍂', 'Other', null],
    ];

    /* ============================================================ the bank */

    private static function isRice(?string $crop): bool
    {
        return $crop !== null && str_starts_with($crop, 'rice');
    }

    private static function isGrain(string $crop): bool
    {
        return str_starts_with($crop, 'rice') || str_starts_with($crop, 'corn') || in_array($crop, ['sorghum', 'sugarcane'], true);
    }

    /** The questions a crop is asked (all of them for no crop): rice only questions and options dropped elsewhere. */
    public static function questions(?string $crop = null): array
    {
        $rice = self::isRice($crop);
        $out = [];
        foreach (self::QUESTIONS as $k => $q) {
            if ($crop !== null && ($q['only'] ?? null) === 'rice' && ! $rice) {
                continue;
            }
            if ($crop !== null && ! $rice) {
                $q['o'] = array_filter($q['o'], fn ($o) => ($o[2] ?? null) !== 'rice');
            }
            $out[$k] = $q;
        }

        return $out;
    }

    /** What the page needs to draw the questions: the groups and the questions (for a crop, or all with their scope). */
    public static function forPayload(?string $crop = null): array
    {
        return ['groups' => self::GROUPS, 'questions' => self::questions($crop)];
    }

    /**
     * A crop's troubles: the other troubles first (rats, birds and snails only
     * where the catalogue has none of that kind for the crop), then its pests
     * and diseases, each crop's worst first, with a photo where we have one.
     * Each {k: other:x | pest:slug | disease:slug, n, l, g, kind?, icon, img?}.
     */
    public static function troubles(string $crop, bool $photos = false): array
    {
        $crop = FieldCatalogue::cropKey($crop) ?? $crop;
        $rows = [];
        $kinds = [];
        foreach (['pests', 'diseases'] as $sec) {
            $live = $photos ? SitePages::inSection($sec)->keyBy('slug') : collect();
            foreach (FieldCatalogue::problems($sec) as $slug => $e) {
                if (! in_array($crop, (array) $e['crops'], true)) {
                    continue;
                }
                $kinds[$e['kind']] = true;
                $img = null;
                if ($photos) {
                    $page = $live->get($slug);
                    $h = $page && is_array($page->heroImage) ? $page->heroImage : (FieldCatalogue::photo($sec, $slug) ?? []);
                    $src = (string) ($h['thumb'] ?? '');
                    if ($src === '') {
                        $src = (string) ($h['src'] ?? '');
                        $twin = str_starts_with($src, '/images/') ? preg_replace('/\.(jpe?g|png|webp)$/i', '-480.webp', $src) : null;
                        if ($twin && is_file(public_path(ltrim($twin, '/')))) {
                            $src = $twin;
                        }
                    }
                    $img = SitePages::img($src);
                }
                $rows[] = array_filter(['k' => ($sec === 'pests' ? 'pest:' : 'disease:') . $slug, 'n' => $e['name'], 'l' => $e['local'], 'g' => $sec === 'pests' ? 'pest' : 'disease',
                    'kind' => $e['kind'], 'img' => $img, 'icon' => FieldBook::KIND_ICONS[$e['kind']] ?? '🐛', 'r' => (int) ($e['ranks'][$crop] ?? 999)], fn ($v) => $v !== null && $v !== '');
            }
        }
        usort($rows, fn ($a, $b) => [$a['g'] === 'disease', $a['r'], $a['n']] <=> [$b['g'] === 'disease', $b['r'], $b['n']]);
        $grain = self::isGrain($crop);
        $others = [];
        foreach (self::OTHER as $k => [$label, $line, $icon, $kind, $only]) {
            if (($only === 'rice' && ! self::isRice($crop)) || ($only === 'grain' && ! $grain) || ($kind !== 'Other' && isset($kinds[$kind]))) {
                continue;
            }
            $others[] = ['k' => 'other:' . $k, 'n' => $label, 'l' => $line, 'g' => 'other', 'icon' => $icon];
        }

        return array_merge($others, $rows);
    }

    /** One trouble key's facts: {key, kind, slug, name}, or null when it is not one. */
    public static function trouble(string $key): ?array
    {
        if (! preg_match('/^(pest|disease|other):([a-z0-9_-]+)$/', $key, $m)) {
            return null;
        }
        if ($m[1] === 'other') {
            $o = self::OTHER[$m[2]] ?? null;

            return $o ? ['key' => $key, 'kind' => $o[3], 'slug' => $m[2], 'name' => $o[0]] : null;
        }
        $e = FieldCatalogue::problem($m[1] === 'pest' ? 'pests' : 'diseases', $m[2]);

        return $e ? ['key' => $key, 'kind' => (string) $e['kind'], 'slug' => $m[2], 'name' => (string) $e['name']] : null;
    }

    /**
     * One profile, checked and cleaned: known keys and values only, multi
     * answers as lists ("none" wins), the troubles of this crop only, a pH
     * range from 3 to 10 (one number is a range of one). Accepts the flat
     * map, or {soil, history} as the Land Prep Planner kept them before, and
     * the old answers (a single water, a single weeds, one pH). A crop of
     * null keeps the rice questions; a crop drops what does not fit it.
     */
    public static function normalize(array $answers, ?string $crop = null): array
    {
        if (isset($answers['soil']) && is_array($answers['soil']) || isset($answers['history']) && is_array($answers['history'])) {
            $answers = array_merge((array) ($answers['history'] ?? []), (array) ($answers['soil'] ?? []), array_diff_key($answers, ['soil' => 1, 'history' => 1]));
        }
        $qs = self::questions($crop);
        $allowed = $crop !== null ? array_flip(array_column(self::troubles($crop), 'k')) : null;
        $out = [];
        foreach ($qs as $k => $q) {
            $v = $answers[$k] ?? null;
            if ($v === null || $v === '' || $v === []) {
                continue;
            }
            if ($k === 'outbreaks') {
                $vals = array_values(array_unique(array_filter((array) $v, fn ($x) => is_string($x) && ($x === 'none' || ($allowed !== null ? isset($allowed[$x]) : self::trouble($x) !== null)))));
                $vals = in_array('none', $vals, true) ? ['none'] : array_slice($vals, 0, 40);
                if ($vals) {
                    $out[$k] = $vals;
                }
                continue;
            }
            $ok = fn ($x) => is_string($x) && isset($q['o'][$x]);
            if (! empty($q['multi'])) {
                $vals = array_values(array_unique(array_filter((array) $v, $ok)));
                if (isset($q['none']) && in_array($q['none'], $vals, true)) {
                    $vals = [$q['none']];
                }
                if ($vals) {
                    $out[$k] = $vals;
                }
            } elseif ($ok($v)) {
                $out[$k] = $v;
            }
        }
        // The old Land Prep answers that are troubles now.
        $old = [];
        if (($answers['snails'] ?? '') === 'many') {
            $old[] = 'other:snails';
        }
        if (in_array($answers['ratsBirds'] ?? '', ['rats', 'both'], true)) {
            $old[] = 'other:rats';
        }
        if (in_array($answers['ratsBirds'] ?? '', ['birds', 'both'], true)) {
            $old[] = 'other:birds';
        }
        if (in_array($answers['zinc'] ?? '', ['mild', 'severe'], true)) {
            $old[] = 'other:zinc';
        }
        $map = ['tungro' => 'disease:rice-tungro', 'bph' => 'pest:brown-planthopper', 'blast' => 'disease:rice-blast'];
        if (isset($map[$answers['pests'] ?? ''])) {
            $old[] = $map[$answers['pests']];
        }
        if ($old && ! in_array('none', $out['outbreaks'] ?? [], true)) {
            $out['outbreaks'] = array_values(array_unique(array_merge($out['outbreaks'] ?? [], $old)));
        }
        // A known pH, as a range from a soil test.
        $lo = $answers['phFrom'] ?? $answers['phLow'] ?? $answers['ph'] ?? null;
        $hi = $answers['phTo'] ?? $answers['phHigh'] ?? $lo;
        $lo = is_numeric($lo) && $lo >= 3 && $lo <= 10 ? round((float) $lo, 1) : null;
        $hi = is_numeric($hi) && $hi >= 3 && $hi <= 10 ? round((float) $hi, 1) : $lo;
        if ($lo !== null || $hi !== null) {
            $out['phFrom'] = min($lo ?? $hi, $hi ?? $lo);
            $out['phTo'] = max($lo ?? $hi, $hi ?? $lo);
        }

        return $out;
    }

    /** A profile split the way the Land Prep Planner reads it: ['soil' => ..., 'history' => ...]. */
    public static function split(array $answers): array
    {
        $out = ['soil' => [], 'history' => []];
        foreach ($answers as $k => $v) {
            $in = self::QUESTIONS[$k]['in'] ?? (in_array($k, ['phFrom', 'phTo'], true) ? 'soil' : null);
            if ($in) {
                $out[$in][$k] = $v;
            }
        }

        return $out;
    }

    /** How many questions a profile answers. */
    public static function count(array $answers): int
    {
        return count(array_intersect_key($answers, self::QUESTIONS)) + (isset($answers['phFrom']) && ! isset($answers['acidity']) ? 1 : 0);
    }

    /** A profile in words, each answer as [short name, the answer]. */
    public static function pairs(array $answers, ?string $crop = null): array
    {
        $out = [];
        foreach (self::QUESTIONS as $k => $q) {
            $v = $answers[$k] ?? null;
            if ($v === null || $v === [] || $v === '') {
                continue;
            }
            if ($k === 'outbreaks') {
                $names = in_array('none', (array) $v, true) ? ['None'] : array_values(array_filter(array_map(fn ($x) => self::trouble((string) $x)['name'] ?? null, (array) $v)));
                if ($names) {
                    $out[] = [$q['short'], implode(', ', $names)];
                }
                continue;
            }
            $labels = array_values(array_filter(array_map(fn ($x) => $q['o'][$x][0] ?? null, (array) $v)));
            if ($labels) {
                $out[] = [$q['short'], implode(', ', $labels)];
            }
            if ($k === 'acidity' && isset($answers['phFrom'])) {
                $out[] = ['Known pH', self::phWords($answers)];
            }
        }
        if (isset($answers['phFrom']) && ! isset($answers['acidity'])) {
            $out[] = ['Known pH', self::phWords($answers)];
        }

        return $out;
    }

    private static function phWords(array $a): string
    {
        return (float) $a['phFrom'] === (float) ($a['phTo'] ?? $a['phFrom']) ? (string) $a['phFrom'] : $a['phFrom'] . ' to ' . $a['phTo'];
    }

    /** A profile as compact prompt text: "Soil: Sticky and heavy. Water: Irrigation canal, Pump. ..." */
    public static function words(array $answers, ?string $crop = null): string
    {
        $p = self::pairs($answers, $crop);

        return $p ? implode('. ', array_map(fn ($x) => $x[0] . ': ' . $x[1], $p)) . '.' : 'Nothing given.';
    }

    /**
     * A profile mapped onto the older fields the other analyses read, so a
     * profile answered once can fill them:
     *  - soilConditions: App\Support\SoilConditions keys (one pH word of
     *    acidic, neutral, alkaline, then sodic, saline, acid_sulfate), with
     *    phValue (the range's middle), phLow and phHigh;
     *  - soil: the texture key of What to Plant, Variety Research and Crop
     *    Protocol (WhatToPlantController::SOILS); phLevel (PH_LEVELS);
     *    soilExtra (SOIL_EXTRAS); lay (LAYS);
     *  - water: What to Plant and Crop Protocol (irrigated, limited,
     *    rainfed); waterNpk: NPK Plus and Satellite Analysis (irrigated,
     *    partial, rainfed); waterAuto: Anee Auto (AutoBook::WATER);
     *  - problemsWhen: When to Plant's PROBLEMS keys; problemsVariety:
     *    Variety Research's; problemsAuto and soilSignsAuto: Anee Auto's.
     * A key with nothing to say is null or an empty list.
     */
    public static function legacy(array $answers): array
    {
        $a = self::normalize($answers);
        $water = (array) ($a['water'] ?? []);
        $weeds = (array) ($a['weeds'] ?? []);
        $troubles = array_values(array_filter(array_map(fn ($k) => self::trouble((string) $k), (array) ($a['outbreaks'] ?? []))));
        $kinds = array_column($troubles, 'kind');
        $slugs = array_column($troubles, 'slug');
        $isPest = (bool) array_filter($troubles, fn ($t) => str_starts_with($t['key'], 'pest:'));
        $isDisease = (bool) array_filter($troubles, fn ($t) => str_starts_with($t['key'], 'disease:'));
        $had = fn (string $s) => in_array($s, $slugs, true);

        // pH: the tested range first, else the acidity answer.
        $lo = $a['phFrom'] ?? null;
        $hi = $a['phTo'] ?? null;
        $mid = $lo !== null ? round(((float) $lo + (float) ($hi ?? $lo)) / 2, 1) : null;
        $phWord = match (true) {
            $mid !== null && $mid < 6 => 'acidic',
            $mid !== null && $mid > 7.5 => 'alkaline',
            $mid !== null => 'neutral',
            in_array($a['acidity'] ?? '', ['mild', 'strong'], true) => 'acidic',
            ($a['acidity'] ?? '') === 'none' => 'neutral',
            ($a['acidity'] ?? '') === 'alkaline' => 'alkaline',
            default => null,
        };
        $salt = $a['salt'] ?? '';
        $saline = in_array($salt, ['crust', 'sea', 'tips'], true) || $had('salt');
        $sodic = $salt === 'sodic';
        $sulfate = ($a['acidity'] ?? '') === 'sulfate';
        $conditions = array_values(array_filter([$phWord, $sodic ? 'sodic' : null, $saline ? 'saline' : null, $sulfate ? 'acid_sulfate' : null]));
        $extras = array_values(array_filter([$sodic ? 'sodic' : null, $saline ? 'saline' : null, $sulfate ? 'acid_sulfate' : null]));

        $texture = $a['texture'] ?? '';
        $soil = match (true) {
            in_array($texture, ['clay', 'heavy_clay'], true) => 'clay',
            $texture === 'loam' => 'loam',
            $texture === 'sandy' => 'sandy',
            ($a['rocks'] ?? '') === 'many' => 'rocky',
            default => 'unsure',
        };
        $lay = match (true) {
            ($a['drainage'] ?? '') === 'stands' || ($a['flooding'] ?? '') === 'often' => 'low',
            ($a['slope'] ?? '') === 'steep' => 'steep',
            ($a['slope'] ?? '') === 'gentle' => 'gentle',
            in_array($a['slope'] ?? '', ['flat', 'terraced'], true) => 'flat',
            default => null,
        };

        // Water: a canal or a pump is reliable, a river or a pond partly, rain alone not.
        $reliable = (bool) array_intersect($water, ['irrigation', 'pump']);
        $partly = (bool) array_intersect($water, ['river', 'reservoir']);
        $drought = $had('drought');
        $w = match (true) {
            $reliable && ! $drought => 'irrigated',
            $reliable || $partly => 'limited',
            $water === ['rainfed'] => 'rainfed',
            default => null,
        };
        $auto = match (true) {
            in_array('irrigation', $water, true) => $drought ? 'canal_late' : 'canal_ok',
            in_array('pump', $water, true) => 'pump_own',
            in_array('river', $water, true) => 'river',
            in_array('reservoir', $water, true) => 'reservoir',
            $water === ['rainfed'] => 'rain_only',
            default => null,
        };

        $floods = in_array($a['flooding'] ?? '', ['sometimes', 'often'], true) || $had('flood');
        $weedy = (bool) array_intersect($weeds, ['many', 'tough', 'sedges', 'weedy_rice']);
        $poorDrain = in_array($a['drainage'] ?? '', ['slow', 'stands'], true);
        $limitedWater = $partly && ! $reliable || $drought;
        $when = array_values(array_filter([
            $floods ? 'floods' : null, $texture === 'heavy_clay' ? 'cracking' : null, $texture === 'sandy' ? 'sandy' : null,
            $limitedWater ? 'water_source' : null, $water === ['rainfed'] ? 'rainfed' : null, $salt === 'sea' ? 'sea' : null,
            $isPest ? 'pests' : null, $weedy ? 'weeds' : null, $poorDrain ? 'drainage' : null,
        ]));
        $variety = array_values(array_filter([
            $drought ? 'drought' : null, $floods ? 'floods' : null, $limitedWater ? 'water_source' : null, $saline ? 'salinity' : null,
            $phWord === 'acidic' ? 'acidic' : null, $phWord === 'alkaline' ? 'alkaline' : null, $sodic ? 'sodic' : null, $sulfate ? 'acid_sulfate' : null,
            $isPest ? 'pests' : null, $isDisease ? 'disease' : null, $weedy ? 'weeds' : null, $poorDrain ? 'drainage' : null,
            ($a['organic'] ?? '') === 'low' || $had('nutrient') ? 'low_fertility' : null, in_array($a['slope'] ?? '', ['gentle', 'steep'], true) ? 'slope' : null,
        ]));
        $autoProblems = array_values(array_filter([
            $weedy ? 'weeds' : null, $floods ? 'flood' : null, $drought ? 'drought' : null,
            array_diff($kinds, ['Rodent', 'Bird', 'Snail', 'Other', 'Fungus', 'Bacterium', 'Virus', 'Viroid', 'Water mold', 'Nematode']) ? 'insects' : null,
            array_intersect($kinds, ['Fungus', 'Bacterium', 'Virus', 'Viroid', 'Water mold', 'Nematode']) ? 'diseases' : null,
            in_array('Rodent', $kinds, true) ? 'rats' : null, in_array('Bird', $kinds, true) ? 'birds' : null, in_array('Snail', $kinds, true) ? 'snails' : null,
            $had('lodging') ? 'lodging' : null, $saline ? 'salt' : null, ($a['drainage'] ?? '') === 'stands' ? 'waterlog' : null,
        ]));
        $signs = array_values(array_filter([
            $texture === 'heavy_clay' ? 'cracks' : null, $texture === 'clay' ? 'sticky' : null, $texture === 'sandy' ? 'gritty' : null, $texture === 'loam' ? 'crumbly' : null,
            $salt === 'crust' ? 'white_crust' : null, $salt === 'sodic' ? 'black_crust' : null, $salt === 'sea' ? 'salty' : null, $salt === 'tips' ? 'burnt_tips' : null,
            $sulfate ? 'rusty' : null, ($a['color'] ?? '') === 'red' ? 'red_soil' : null,
            ($a['organic'] ?? '') === 'high' || ($a['color'] ?? '') === 'dark' ? 'dark_rich' : null, ($a['organic'] ?? '') === 'low' ? 'pale_hard' : null,
            ($a['drainage'] ?? '') === 'stands' ? 'stays_wet' : null,
        ]));

        return [
            'soilConditions' => $conditions, 'phValue' => $mid, 'phLow' => $lo, 'phHigh' => $hi ?? $lo,
            'soil' => $soil, 'phLevel' => $phWord ?? 'unsure', 'soilExtra' => $extras, 'lay' => $lay,
            'water' => $w, 'waterNpk' => $w === 'limited' ? 'partial' : $w, 'waterAuto' => $auto,
            'problemsWhen' => $when, 'problemsVariety' => $variety, 'problemsAuto' => $autoProblems, 'soilSignsAuto' => $signs,
        ];
    }

    /* ===================================================== saved profiles */

    /** Whether the profiles table is there yet (a page that runs before the migration simply keeps nothing). */
    public static function storeReady(): bool
    {
        static $ready = null;
        if ($ready === null) {
            try {
                $known = Cache::get('field-profiles:table');
                if ($known === null) {
                    // A table that is there stays there; a missing one is asked again in a minute (the migration may have just run).
                    $known = Schema::hasTable('as_field_profiles') ? 1 : 0;
                    Cache::put('field-profiles:table', $known, $known ? 86400 : 60);
                }
                $ready = (int) $known === 1;
            } catch (\Throwable $e) {
                $ready = false;
            }
        }

        return $ready;
    }

    /** One saved row as the page sees it. */
    public static function row(object $r): array
    {
        $answers = json_decode((string) $r->answers, true) ?: [];

        $row = ['id' => (int) $r->id, 'name' => (string) $r->name, 'crop' => $r->crop, 'cropLabel' => $r->crop ? (FieldCatalogue::crops()[$r->crop][1] ?? null) : null,
            'scheduleId' => $r->scheduleId !== null ? (int) $r->scheduleId : null, 'lotId' => $r->lotId !== null ? (int) $r->lotId : null,
            'answers' => $answers, 'count' => self::count($answers), 'updated' => Carbon::parse($r->updated_at ?: $r->created_at)->format('M j, Y')];

        // A saved profile is a farm lot (App\Support\FarmLots): its place, size and age ride along.
        return FarmLots::decorate($row, $r);
    }

    /** An owner's saved profiles, the newest first. */
    public static function saved(int $ownerId, int $limit = 100): array
    {
        if (! self::storeReady()) {
            return [];
        }

        return DB::table('as_field_profiles')->where('userId', $ownerId)->where('deleteStatus', 1)
            ->orderByDesc('updated_at')->orderByDesc('id')->limit($limit)->get()->map(fn ($r) => self::row($r))->all();
    }

    /**
     * Save one profile for an owner: an update of `$id` (theirs only), else
     * the profile already kept for the same season lot, else the one of the
     * same name with no lot, else a new row. Returns the saved row, or null
     * when there is no table yet or nothing to keep.
     */
    public static function store(int $ownerId, array $data, ?int $id = null): ?array
    {
        if (! self::storeReady()) {
            return null;
        }
        $crop = isset($data['crop']) && $data['crop'] !== '' ? (FieldCatalogue::cropKey((string) $data['crop']) ?? null) : null;
        $answers = self::normalize((array) ($data['answers'] ?? []), $crop);
        $name = mb_substr(trim((string) ($data['name'] ?? '')), 0, 120) ?: 'My field';
        $lotId = isset($data['lotId']) && is_numeric($data['lotId']) ? (int) $data['lotId'] : null;
        $scheduleId = isset($data['scheduleId']) && is_numeric($data['scheduleId']) ? (int) $data['scheduleId'] : null;
        if (! $answers && ! $id) {
            return null;
        }
        $q = DB::table('as_field_profiles')->where('userId', $ownerId)->where('deleteStatus', 1);
        $have = $id ? (clone $q)->where('id', $id)->first()
            : ($lotId ? (clone $q)->where('lotId', $lotId)->first() : (clone $q)->whereNull('lotId')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first());
        if ($id && ! $have) {
            return null;
        }
        $row = ['name' => $name, 'crop' => $crop, 'answers' => json_encode($answers, JSON_UNESCAPED_UNICODE), 'updated_at' => now()];
        if ($lotId) {
            $row['lotId'] = $lotId;
            $row['scheduleId'] = $scheduleId;
        }
        if ($have) {
            DB::table('as_field_profiles')->where('id', $have->id)->update($row);
            $rid = (int) $have->id;
        } else {
            $rid = (int) DB::table('as_field_profiles')->insertGetId($row + ['userId' => $ownerId, 'lotId' => $lotId, 'scheduleId' => $scheduleId, 'created_at' => now(), 'deleteStatus' => 1]);
        }

        return self::row(DB::table('as_field_profiles')->where('id', $rid)->first());
    }

    /**
     * Keep every lot of a plan as a saved profile (the Land Prep Planner calls
     * this when a plan is made): keyed by the season lot when the lot came
     * from a season, else by its name. Best effort, never in the way.
     */
    public static function rememberLots(int $ownerId, string $crop, array $lots): array
    {
        $ids = [];
        if (! self::storeReady()) {
            return $ids;
        }
        try {
            $seasonOf = [];
            $lotIds = array_values(array_filter(array_map(fn ($l) => isset($l['sourceLotId']) ? (int) $l['sourceLotId'] : null, $lots)));
            if ($lotIds) {
                $seasonOf = DB::table('as_schedule_lots')->whereIn('id', $lotIds)->pluck('croppingScheduleId', 'id')->all();
            }
            foreach ($lots as $l) {
                $answers = self::normalize(array_merge((array) ($l['history'] ?? []), (array) ($l['soil'] ?? []), (array) ($l['profile'] ?? [])), $crop);
                if (! $answers) {
                    continue;
                }
                $lotId = isset($l['sourceLotId']) ? (int) $l['sourceLotId'] : null;
                $saved = self::store($ownerId, ['name' => (string) ($l['name'] ?? 'My field'), 'crop' => $crop, 'answers' => $answers,
                    'lotId' => $lotId, 'scheduleId' => $lotId ? ($seasonOf[$lotId] ?? null) : null]);
                if ($saved) {
                    $ids[] = $saved['id'];
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $ids;
    }

    /* ======================================== in the other analyses (2026-10-15) */

    /**
     * The detailed answers an analysis was sent (its `fieldProfile` input),
     * checked for the analysis's crop: [] when none came. Never in the way.
     */
    public static function fromInput(mixed $raw, ?string $crop = null): array
    {
        if (is_string($raw) && $raw !== '') {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw) || ! $raw) {
            return [];
        }
        try {
            return self::normalize($raw, $crop !== '' ? $crop : null);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /** The answers as an analysis keeps them in its params: the profile and its words, or nothing. */
    public static function forParams(array $answers, ?string $crop = null): array
    {
        return $answers ? ['fieldProfile' => $answers, 'fieldProfileSaid' => self::pairs($answers, $crop)] : ['fieldProfile' => null, 'fieldProfileSaid' => []];
    }

    /** The prompt's "Field in detail" line, or '' when no detail was given. Compact: one line of words(). */
    public static function promptLine(array $answers, ?string $crop = null, string $lead = '- '): string
    {
        return $answers ? $lead . 'Field in detail (the farmer\'s own answers about this field, last season included): ' . self::words($answers, $crop) . "\n" : '';
    }

    /**
     * Keep an analysis's detailed answers for the next one, best effort: back
     * into the saved profile they came from when it is the farm's, is for the
     * same crop (or none) and the answers changed; else as a profile named
     * after the lot or the place. Returns the saved row's id, or null.
     */
    public static function keepFor(int $ownerId, array $answers, string $name, ?string $crop = null, mixed $fromId = null, ?int $lotId = null, ?int $scheduleId = null): ?int
    {
        if (! $answers || ! self::storeReady()) {
            return null;
        }
        try {
            $cropKey = $crop !== null && $crop !== '' ? FieldCatalogue::cropKey($crop) : null;
            if (is_numeric($fromId) && (int) $fromId > 0) {
                $had = DB::table('as_field_profiles')->where('id', (int) $fromId)->where('userId', $ownerId)->where('deleteStatus', 1)->first();
                if ($had && ($had->crop === null || $cropKey === null || $had->crop === $cropKey)) {
                    $old = self::normalize(json_decode((string) $had->answers, true) ?: [], $crop);
                    if (self::sameAnswers($old, $answers)) {
                        return (int) $had->id;
                    }
                    $row = self::store($ownerId, ['name' => (string) $had->name, 'crop' => $had->crop ?? $cropKey, 'answers' => $answers], (int) $had->id);

                    return $row['id'] ?? null;
                }
            }
            $row = self::store($ownerId, ['name' => $name, 'crop' => $cropKey, 'answers' => $answers, 'lotId' => $lotId, 'scheduleId' => $scheduleId]);

            return $row['id'] ?? null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Two profiles alike, whatever the order of their lists. */
    private static function sameAnswers(array $a, array $b): bool
    {
        $norm = function (array $x): array {
            ksort($x);
            foreach ($x as $k => $v) {
                if (is_array($v)) {
                    sort($v);
                    $x[$k] = $v;
                } elseif (is_float($v) || is_int($v)) {
                    $x[$k] = (string) round((float) $v, 1);
                }
            }

            return $x;
        };

        return $norm($a) === $norm($b);
    }
}
