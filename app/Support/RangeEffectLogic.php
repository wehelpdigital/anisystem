<?php

namespace App\Support;

use App\Http\Controllers\CropProtocolController;
use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleLot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Range Effect's deeper reading (2026-10-30, the owner: "since we have information about the soil, about the lot
 * size, and crop, also in the range effect have a measurement per day ... if the amount is enough not only the
 * coverage range. note that foliar effect is different on granular ... check the coverage based on the soil like
 * for example, if you acidify using ammosul, then calcium also is unlocked in sodic alkaline ... how each things are
 * connected. also these options ... should be a selectable option in the filtering. have a warning as well that
 * this is a guide only").
 *
 * Four parts, each one the farmer can switch off (opts):
 *   amt     How much: the field's need of the element (NPK Plus's need model, NpkNeed, for this lot's crop, variety,
 *           soil and place), spread over the season by how much each stage takes, against what each product gives
 *           a day (its kg a hectare spread over the days it works, none while it is still in the soil). A leaf spray
 *           is weighed the same way, so a spray that cannot carry the need shows it.
 *   soil    The lot's soil (its field answers, else the area's typical soil): what the soil does to the need (a soil
 *           that locks phosphorus needs more of it from the bag) and to how long a nutrient lasts (sandy soil washes
 *           nitrogen and potassium down sooner).
 *   links   What one product does to another nutrient, NpkNeed run with the season's products as its plan against
 *           NpkNeed without them: the acid of ammonium fertilizers freeing zinc, iron and manganese on a high pH soil
 *           and turning a sodic soil's own lime into calcium; lime freeing phosphorus on an acid soil but locking
 *           zinc and boron where it is not needed; heavy phosphorus tying up zinc; heavy potassium crowding out
 *           magnesium and calcium; gypsum pushing sodium out. Each from the day its cause goes on.
 *   health  What nutrients do to the crop's guard against pests and diseases (in RangeEffect::health): nitrogen
 *           still strong late, or very heavy, makes soft lush leaves; potassium and silicon help the crop hold out.
 * Every number is a guide: the books, the farmer's answers and the products as read, never a soil test.
 */
final class RangeEffectLogic
{
    public const OPTS = ['amt', 'soil', 'links', 'health'];

    /** How much of the season's take falls on a day, by how much its stage matters for the element (0 to 3). */
    private const TAKE = [0.04, 0.6, 1.0, 1.5];

    /** How long an element keeps working on a soil, against the book's days (written for a loam). */
    private const SOIL_DAYS = [
        'sandy' => ['N' => 0.75, 'K2O' => 0.75, 'S' => 0.75, 'B' => 0.7, 'Mg' => 0.8, 'Ca' => 0.85],
        'clay' => ['K2O' => 1.15, 'P2O5' => 1.1, 'N' => 1.05, 'Mg' => 1.1],
        'peat' => ['N' => 1.1, 'Cu' => 0.8, 'Zn' => 0.9],
        'high_rainfall' => ['N' => 0.85, 'K2O' => 0.9, 'B' => 0.85, 'S' => 0.85, 'Mg' => 0.9],
        'low_om' => ['N' => 0.9, 'K2O' => 0.9, 'B' => 0.9],
    ];

    private const SOIL_DAYS_WORD = [
        'sandy' => 'Sandy soil lets it wash down below the roots sooner',
        'clay' => 'Clay holds it longer',
        'peat' => 'Peat holds and releases it differently',
        'high_rainfall' => 'Heavy rain over the season washes it down sooner',
        'low_om' => 'Soil low in organic matter holds less of it',
    ];

    /** The element as it is written: P2O5 and K2O with their small numbers. */
    public const SAY = ['P2O5' => 'P₂O₅', 'K2O' => 'K₂O'];

    /** A plan lockout and the elements it holds back (NpkModel::ELEMENTS weights). */
    private const LOCK_WORD = [
        'high_p' => 'heavy phosphorus ties it up', 'high_k' => 'heavy potassium crowds it out at the roots', 'liming' => 'the lime locks it up as the pH rises',
        'excess_zn' => 'heavy zinc crowds it out', 'high_ca' => 'heavy calcium crowds it out', 'high_mg' => 'heavy magnesium crowds it out',
        'high_s' => 'heavy sulfate holds it back', 'high_nh4' => 'heavy ammonium competes with it at the roots',
    ];

    /** The options as asked: every part on unless switched off. */
    public static function opts(array $in): array
    {
        $out = [];
        foreach (self::OPTS as $k) {
            $out[$k] = ! array_key_exists($k, $in) || filter_var($in[$k], FILTER_VALIDATE_BOOLEAN);
        }

        return $out;
    }

    /* =============================================================== the lot's soil */

    /**
     * What the app knows of a lot's soil: NpkNeed's input (the lot's field answers, the area's typical soil where
     * the lot left a gap, the place's season), the soil's factors, and the facts in words.
     */
    public static function soil(AsCroppingSchedule $s, AsScheduleLot $lot, array $c): array
    {
        static $memo = [];
        $custom = $c['custom'] ?? null;
        $mk = $s->id . ':' . $lot->id . ($custom ? ':' . md5(json_encode($custom)) : '');
        if (isset($memo[$mk])) {
            return $memo[$mk];
        }
        // The lot's field answers, from its farm lot in Global Lots (LotSync), else a profile the season lot kept; a what-if
        // lot's own answers and place instead.
        $answers = $custom ? $custom['answers'] : (FieldProfile::storeReady() ? LotSync::answersFor($s, $lot) : []);
        $rice = $c['crop'] && str_starts_with((string) $c['crop'], 'rice');
        $method = $rice && $c['counter'] === 'DAS' ? 'direct_wet' : null;
        $key = NpkNeed::cropKey($c['crop'], $method);
        $place = $custom && $custom['place'] !== '' ? $custom['place'] : trim(implode(', ', array_filter([$lot->locBarangay, $lot->locTown, $lot->locProvince])));
        $L = $answers ? FieldProfile::legacy($answers) : [];
        $in = null;
        if ($key) {
            $p = ['crop' => $key, 'method' => $method, 'potential' => NutrientBook::varietyPotential($key, $lot->variety), 'targetYield' => null, 'yieldUnit' => 'tons',
                'area' => $c['ha'] ?: 1, 'soil' => (string) ($L['soil'] ?? ''), 'water' => ($L['water'] ?? null) ?: 'irrigated', 'soilConditions' => (array) ($L['soilConditions'] ?? []),
                'fieldProfile' => $answers, 'testP' => 'unsure', 'testK' => 'unsure', 'prevCrop' => 'unsure', 'residue' => 'unsure', 'location' => $place,
                'day' => $c['anchor']?->toDateString()];
            try {
                $in = CropProtocolController::needInput($p);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        $texture = (string) ($in['texture'] ?? (in_array($L['soil'] ?? '', ['sandy', 'clay', 'loam'], true) ? $L['soil'] : 'unsure'));
        $conds = (array) ($in['conditions'] ?? ($L['soilConditions'] ?? []));
        $ph = isset($in['ph']) && $in['ph'] !== null ? (float) $in['ph'] : (isset($L['phValue']) ? (float) $L['phValue'] : null);
        $f = NpkNeed::factors($key ? NpkNeed::crop($key) : null, $texture, $conds, $ph);
        if ($in) {
            $f = NpkNeed::moreFactors($f, $in, $texture, (string) ($in['water'] ?? 'irrigated'));
        }
        $from = $answers ? (! empty($in['areaSoil']) ? 'both' : 'answers') : (! empty($in['areaSoil']) ? 'area' : 'none');

        // Its farm lot in Global Lots, and an answered one nearby when it has no answers of its own.
        $farm = ! $custom && FarmLots::ready() ? LotSync::farmOf($s, $lot) : null;
        $near = $farm && ! $answers ? LotSync::nearAnswered($s, $farm) : null;

        return $memo[$mk] = ['in' => $in, 'key' => $key, 'f' => array_values($f), 'from' => $from, 'custom' => $custom ? $custom['name'] : null,
            'farm' => $farm ? ['id' => (int) $farm['id'], 'name' => (string) $farm['name']] : null,
            'near' => $near ? ['id' => (int) $near['id'], 'name' => (string) $near['name'], 'same' => $near['same']] : null,
            'facts' => ['say' => self::soilWords($texture, $ph, $f), 'from' => $from, 'texture' => $texture, 'ph' => $ph, 'factors' => array_values($f), 'place' => $place]];
    }

    /** The soil in a line: "Clay, pH about 5.4 (acid), a flooded paddy". */
    private static function soilWords(string $texture, ?float $ph, array $f): string
    {
        $bits = [];
        $bits[] = ['sandy' => 'Sandy soil', 'clay' => 'Clay soil', 'loam' => 'Loam'][$texture] ?? 'Soil type not known';
        if ($ph !== null) {
            $bits[] = 'pH about ' . rtrim(rtrim(number_format($ph, 1, '.', ''), '0'), '.');
        }
        foreach (['strongAcid' => 'very acid', 'acid' => 'acid', 'neutral' => 'near neutral', 'alkaline' => 'alkaline', 'strongAlk' => 'strongly alkaline', 'calcareous' => 'limy',
            'sodic' => 'sodic', 'saline' => 'salty', 'acid_sulfate' => 'acid sulfate', 'low_om' => 'low in organic matter', 'peat' => 'peat', 'flooded' => 'a flooded paddy',
            'high_rainfall' => 'heavy rain in the season', 'drought' => 'a dry season ahead'] as $k => $w) {
            if (in_array($k, $f, true)) {
                $bits[] = $w;
            }
        }

        return implode(', ', $bits);
    }

    /** How long an element lasts on this soil, against the book's days: the factors' product, held to a sensible band. */
    public static function daysMult(string $el, array $f): float
    {
        $m = 1.0;
        foreach (self::SOIL_DAYS as $k => $by) {
            if (isset($by[$el]) && in_array($k, $f, true)) {
                $m *= $by[$el];
            }
        }

        return max(0.55, min(1.35, $m));
    }

    /* =============================================================== the reading */

    /**
     * The deeper reading of one nutrient over the days: the need and what is given each day (amt), and the notes
     * that say how the soil and the products connect (soil, links). $info: per day {label, in, date}.
     *
     * @return array{amt: ?array, notes: list<array>, others: list<array>}
     */
    public static function read(array $days, array $apps, array $check, array $c, array $soil, array $opts, array $info): array
    {
        $out = ['amt' => null, 'notes' => [], 'others' => []];
        if ($check['kind'] !== 'nutrient') {
            return $out;
        }
        $el = $check['el'];
        $say = self::SAY[$el] ?? $el;
        $word = $check['word'];
        $rel = array_values(array_filter($apps, fn ($a) => $a['curve'] !== null));

        // The need, without and with the season's products, and on this soil or on a soil no one has described.
        $runs = self::needRuns($soil, $apps, $opts);
        $base = $runs['base'];
        $plan = $runs['plan'];

        // The soil: what it is, how long things last on it, what it does to the need.
        if ($opts['soil']) {
            $out['notes'][] = self::soilFactNote($soil);
            $dm = self::daysMult($el, $soil['f']);
            if (abs($dm - 1) >= 0.04) {
                $ws = [];
                foreach (self::SOIL_DAYS as $k => $by) {
                    if (isset($by[$el]) && in_array($k, $soil['f'], true)) {
                        $ws[] = self::SOIL_DAYS_WORD[$k];
                    }
                }
                $out['notes'][] = ['k' => 'soil', 'icon' => $dm < 1 ? '💧' : '🧱', 'tone' => $dm < 1 ? 'warn' : 'good',
                    'title' => ucfirst($word) . ' lasts ' . ($dm < 1 ? 'less' : 'longer') . ' here',
                    'text' => implode('; ', $ws) . '. Its days are counted about ' . (int) round(abs(1 - $dm) * 100) . ' percent ' . ($dm < 1 ? 'shorter' : 'longer') . ' than the book\'s.'];
            }
            if ($base) {
                foreach (self::soilNeedNotes($base, $el, $word, $say) as $n) {
                    $out['notes'][] = $n;
                }
            }
        }

        // The links: what the season's products do to this nutrient, from the day their cause goes on.
        $causes = [];
        if ($opts['links'] && $base && $plan) {
            [$linkNotes, $causes] = self::linkNotes($base, $plan, $apps, $el, $word);
            foreach ($linkNotes as $n) {
                $out['notes'][] = $n;
            }
            $out['others'] = self::otherLinks($base, $plan, $el);
        }

        // How much: the need spread over the season, what each product gives a day.
        if ($opts['amt']) {
            $out['amt'] = self::amounts($days, $apps, $el, $say, $word, $base, $plan, $causes, $info);
            foreach (self::foliarNotes($rel, $el, $say, $out['amt'], $c) as $n) {
                $out['notes'][] = $n;
            }
        }

        return $out;
    }

    /** NpkNeed for the lot: without the season's products (base) and, for the links, with them as its plan. */
    private static function needRuns(array $soil, array $apps, array $opts): array
    {
        $in = $soil['in'];
        if (! $in) {
            return ['base' => null, 'plan' => null];
        }
        // With the soil switched off, the need for a field nobody has described (loam, no pH, no conditions, no test).
        if (! $opts['soil']) {
            $in = array_merge($in, ['texture' => 'unsure', 'conditions' => [], 'ph' => null, 'tests' => [], 'profile' => [], 'areaZinc' => null, 'season' => null]);
            unset($in['areaSoil']);
        }
        try {
            $base = NpkNeed::run($in);
        } catch (\Throwable $e) {
            report($e);

            return ['base' => null, 'plan' => null];
        }
        $plan = null;
        if ($opts['links']) {
            $perHa = [];
            $lines = [];
            foreach ($apps as $a) {
                foreach ($a['nut'] as $n => $v) {
                    if ($v !== null) {
                        $perHa[$n] = ($perHa[$n] ?? 0) + (float) $v;
                    }
                }
                if (($a['prodKgHa'] ?? null) && ! empty($a['pct'])) {
                    $lines[] = [(string) ($a['slug'] ?? ''), (float) $a['prodKgHa'], (array) $a['pct'], (string) $a['as']];
                }
            }
            if ($perHa || $lines) {
                try {
                    $plan = NpkNeed::run($in + ['plan' => ['perHa' => $perHa] + NpkNeed::planFacts($lines)]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return ['base' => $base, 'plan' => $plan];
    }

    /** The season's need of an element, kg a hectare from the bag: N, P2O5 and K2O, or a secondary or micro element. */
    private static function needOf(?array $run, string $el): ?float
    {
        if (! $run || empty($run['known'])) {
            return null;
        }
        if (in_array($el, NpkNeed::NPK, true)) {
            return isset($run['need'][$el]) ? (float) $run['need'][$el] : null;
        }

        return isset($run['micro'][$el]) ? (float) ($run['micro'][$el]['need'] ?? 0) : null;
    }

    /** The soil in a note: what it is and where the app learned it. */
    private static function soilFactNote(array $soil): array
    {
        $from = $soil['from'];
        $farm = $soil['farm'] ?? null;
        $near = $soil['near'] ?? null;
        $name = $farm ? $farm['name'] : 'This lot';
        // A what-if lot: its own answers, nothing to answer in Global Lots.
        if (! empty($soil['custom'])) {
            $text = match ($from) {
                'answers' => 'From the answers of ' . $soil['custom'] . ', the what-if lot. It is not a lot of yours: only this reading uses it.',
                'both' => 'From the answers of ' . $soil['custom'] . ', the what-if lot, with the area\'s typical soil where a question is open.',
                'area' => $soil['custom'] . ' has no answers yet, so this is the area\'s typical soil. Answer its questions to see your own.',
                default => $soil['custom'] . ' has no answers and its place has no soil record, so the soil is not counted.',
            };

            return ['k' => 'soil', 'icon' => '🧪', 'tone' => in_array($from, ['area', 'none'], true) ? 'warn' : 'info', 'title' => 'The what-if soil: ' . $soil['facts']['say'], 'text' => $text, 'acts' => []];
        }
        // A lot with no answers says which lot in Global Lots to answer (2026-10-30, the owner read the area's pH 6.2 on a
        // lot believed answered: it was a lot new to Global Lots), and offers an answered lot nearby.
        $text = match ($from) {
            'answers' => 'From your answers about ' . $name . ' in Global Lots.',
            'both' => 'From your answers about ' . $name . ' in Global Lots, with the area\'s typical soil where a question is still open.',
            'area' => $name . ' has no answers in Global Lots yet, so this is the area\'s typical soil, from the soil records of the place.'
                . ($near ? ' ' . $near['name'] . ', in the same ' . $near['same'] . ', is answered: if it is the same kind of ground, use its answers.' : ' Answer its questions once, or add a soil test, to make this yours.'),
            default => $name . ' has no answers in Global Lots and its place has no soil record, so the soil is not counted. Answer its questions once to count it.',
        };
        $acts = [];
        if ($farm && in_array($from, ['area', 'none'], true)) {
            $acts[] = ['label' => 'Answer ' . $farm['name'] . '\'s questions', 'href' => route('farmlots.index') . '?lot=' . $farm['id'] . '&answers=1'];
            if ($near) {
                $acts[] = ['label' => 'Use ' . $near['name'] . '\'s answers', 'copy' => ['to' => $farm['id'], 'from' => $near['id']]];
            }
        } elseif ($farm) {
            $acts[] = ['label' => 'See or change the answers', 'href' => route('farmlots.index') . '?lot=' . $farm['id'] . '&answers=1'];
        }

        return ['k' => 'soil', 'icon' => '🪨', 'tone' => in_array($from, ['area', 'none'], true) ? 'warn' : 'info', 'title' => 'Your soil: ' . $soil['facts']['say'], 'text' => $text, 'acts' => $acts];
    }

    /** What the soil does to the need of the element: NpkModel::ADJUST for N, P2O5 and K2O; the soil's share for the rest. */
    private static function soilNeedNotes(array $base, string $el, string $word, string $say): array
    {
        $out = [];
        if (in_array($el, NpkNeed::NPK, true)) {
            foreach ((array) ($base['factors'] ?? []) as $k) {
                $a = NpkModel::ADJUST[$k] ?? null;
                if (! $a || ! isset($a[$el]) || (isset($a['unless'][$el]) && in_array($a['unless'][$el], (array) $base['factors'], true))) {
                    continue;
                }
                $m = (float) $a[$el];
                $out[] = ['k' => 'soil', 'icon' => $m > 1 ? '🔒' : '🌱', 'tone' => $m > 1 ? 'warn' : 'good', 'title' => $a['why'],
                    'text' => 'So this field needs about ' . (int) round(abs($m - 1) * 100) . ' percent ' . ($m > 1 ? 'more' : 'less') . ' ' . $word . ' from the bag than an average field.'];
            }
            if (isset($base['need'][$el])) {
                $out[] = ['k' => 'soil', 'icon' => '🎯', 'tone' => 'info', 'title' => 'This field needs about ' . self::kg((float) $base['need'][$el]) . ' kg ' . $say . ' a hectare from the bag this season',
                    'text' => 'Counted the NPK Plus way for this crop' . (! empty($base['goal']['Y']) ? ', a goal of about ' . self::kg((float) $base['goal']['Y']) . ' t a hectare (' . $base['goal']['why'] . ')' : '')
                        . ' and this soil. The soil gives the rest on its own.'];
            }

            return $out;
        }
        $mm = $base['micro'][$el] ?? null;
        if ($mm && ($s = NpkNeed::soilSay($mm)) !== '') {
            $need = (float) ($mm['need'] ?? 0);
            $out[] = ['k' => 'soil', 'icon' => $need > 0 ? '🔒' : '🌱', 'tone' => $need > 0 ? 'warn' : 'good',
                'title' => $need > 0 ? ucfirst($word) . ' may run short here: about ' . self::kg($need) . ' kg ' . $say . ' a hectare from the bag' : 'The soil likely gives the ' . $word . ' this crop needs',
                'text' => $s];
        } elseif (! empty(NpkModel::ELEMENTS[$el]['beneficial'])) {
            $out[] = ['k' => 'soil', 'icon' => '🌱', 'tone' => 'info', 'title' => ucfirst($word) . ' helps but is not required', 'text' => 'This crop does well without it on most soils.'];
        }

        return $out;
    }

    /**
     * The links: NpkNeed with the products against without them. Each difference is said with the products that
     * cause it and the day the first goes on. Returns [notes, causes]; causes: [{kind, from, to}] the need shifts in.
     */
    private static function linkNotes(array $base, array $plan, array $apps, string $el, string $word): array
    {
        $notes = [];
        $causes = [];
        $f = (array) ($base['factors'] ?? []);
        $alk = (bool) array_intersect(['alkaline', 'strongAlk', 'calcareous', 'sodic'], $f);
        $sour = (bool) array_intersect(['strongAcid', 'acid', 'acid_sulfate'], $f);
        $sodic = in_array('sodic', $f, true);
        // The products by what they do to the soil (NpkNeed::acidityOf), each with its day.
        $kinds = ['acid' => [], 'nitrate' => [], 'lime' => [], 'gypsum' => []];
        foreach ($apps as $a) {
            if (empty($a['pct']) || ! ($a['prodKgHa'] ?? 0)) {
                continue;
            }
            $ac = NpkNeed::acidityOf((string) ($a['slug'] ?? ''), (string) $a['as'], (array) $a['pct']);
            $nKg = (float) $a['prodKgHa'] * (float) ($a['pct']['N'] ?? 0) / 100;
            $eq = $ac['perKg'] !== null ? (float) $a['prodKgHa'] * $ac['perKg'] : $nKg * $ac['perN'] + (float) $a['prodKgHa'] * (float) ($a['pct']['S'] ?? 0) / 100 * $ac['perS'];
            if ($ac['perKg'] !== null && $ac['perKg'] < 0) {
                $kinds['lime'][] = $a;
            } elseif ($eq > 5) {
                $kinds['acid'][] = $a;
            } elseif ($eq < -5) {
                $kinds['nitrate'][] = $a;
            }
            if (preg_match('/gypsum|calcium sulfate|calcium sulphate/i', (string) $a['as'])) {
                $kinds['gypsum'][] = $a;
            }
        }
        $names = function (array $list): string {
            $n = array_values(array_unique(array_map(fn ($a) => $a['as'] . ' (' . $a['date']->format('M j') . ')', $list)));

            return count($n) < 3 ? implode(' and ', $n) : implode(', ', array_slice($n, 0, 2)) . ' and ' . (count($n) - 2) . ' more';
        };
        $first = fn (array $list) => $list ? min(array_map(fn ($a) => $a['date']->toDateString(), $list)) : null;
        // The acid's window: about four weeks after each goes on.
        $acidCause = fn () => array_map(fn ($a) => ['kind' => 'acid', 'from' => $a['date']->toDateString(), 'to' => $a['date']->copy()->addDays(28)->toDateString()], $kinds['acid']);
        $pa = $plan['acid'] ?? null;
        $u = (float) ($pa['u'] ?? 0);

        $needB = self::needOf($base, $el);
        $needP = self::needOf($plan, $el);
        $delta = $needB !== null && $needP !== null ? $needP - $needB : 0.0;
        $shift = abs($delta) >= max(0.05, 0.03 * max(1.0, (float) $needB))
            ? ' Counted here: about ' . self::kg(abs($delta)) . ' kg ' . (self::SAY[$el] ?? $el) . ' a hectare ' . ($delta > 0 ? 'more' : 'less') . ' from the bag.' : '';

        // The acid of ammonium fertilizers (and urea once it turns to ammonium) around the roots.
        if ($kinds['acid'] && $u > 0.02) {
            $who = $names($kinds['acid']);
            $text = null;
            $tone = 'good';
            if ($el === 'Ca' && $sodic) {
                $text = $who . ' sour the soil around the roots for a few weeks after each goes on. On this sodic soil the acid turns the soil\'s own lime into calcium, and that calcium pushes sodium out: more calcium is within the crop\'s reach.';
            } elseif ($alk && in_array($el, ['Zn', 'Fe', 'Mn', 'Cu', 'B'], true)) {
                $text = $who . ' sour the soil around the roots for a few weeks after each goes on. On this high pH soil that frees some of the ' . $word . ' the soil had locked up.';
            } elseif (in_array('neutral', $f, true) && in_array($el, ['Zn', 'Fe', 'Mn'], true)) {
                $text = $who . ' sour the soil around the roots for a few weeks after each goes on. On this near neutral soil that frees a little of the ' . $word . '.';
            } elseif ($alk && $el === 'P2O5') {
                $text = $who . ' sour the soil around the roots. On this high pH soil that frees some of the phosphorus held as calcium phosphate.';
            } elseif ($sour && $el === 'Mo') {
                $text = $who . ' make this acid soil sourer around the roots, and molybdenum locks up harder.';
                $tone = 'warn';
            } elseif ($sour && in_array($el, ['Ca', 'Mg'], true)) {
                $text = $who . ' make this acid soil sourer, and the acid washes ' . $word . ' down out of reach.';
                $tone = 'warn';
            } elseif ($el === 'K2O' && ($sour || in_array('sandy', $f, true))) {
                $text = 'The acid from ' . $who . ' washes some potassium down with the nitrate in this ' . ($sour ? 'acid' : 'sandy') . ' soil.';
                $tone = 'warn';
            } elseif ($sour && $el === 'P2O5') {
                $text = $who . ' make this acid soil sourer, and it locks up a little more phosphorus.';
                $tone = 'warn';
            }
            if ($text) {
                $notes[] = ['k' => 'links', 'icon' => '🧪', 'tone' => $tone, 'title' => 'The acid of your ammonium fertilizers', 'text' => $text . $shift, 'from' => $first($kinds['acid'])];
                $causes = array_merge($causes, $acidCause());
            }
        }
        // Lime: on an acid soil it frees phosphorus and molybdenum; where it is not needed it locks the micros.
        if ($kinds['lime']) {
            $who = $names($kinds['lime']);
            $text = null;
            $tone = 'good';
            if ($sour && in_array($el, ['P2O5', 'Mo'], true)) {
                $text = $who . ' raises this acid soil\'s pH, which frees ' . ($el === 'Mo' ? 'molybdenum' : 'some of the phosphorus the acid had locked') . '.';
            } elseif (in_array($el, ['Ca', 'Mg'], true)) {
                $text = $who . ' brings ' . $word . ' with it' . ($el === 'Mg' ? ' when it is dolomite' : '') . '.';
            } elseif (in_array($el, ['Zn', 'B', 'Mn', 'Cu', 'Fe'], true) && ! $sour) {
                $text = $who . ' pushes the pH up on a soil that is not acid, and ' . $word . ' locks up as it rises.';
                $tone = 'warn';
            } elseif ($alk && $el === 'P2O5') {
                $text = $who . ' pushes this high pH higher and locks a little more phosphorus.';
                $tone = 'warn';
            }
            if ($text) {
                $notes[] = ['k' => 'links', 'icon' => '🪨', 'tone' => $tone, 'title' => 'The lime in your season', 'text' => $text . $shift, 'from' => $first($kinds['lime'])];
                $causes[] = ['kind' => 'lime', 'from' => $first($kinds['lime']), 'to' => null];
            }
        }
        // Gypsum on sodic soil pushes the sodium out; anywhere it brings calcium and sulfur without moving the pH.
        if ($kinds['gypsum'] && ($sodic && in_array($el, ['Zn', 'Fe', 'Mn', 'Ca'], true))) {
            $notes[] = ['k' => 'links', 'icon' => '🧂', 'tone' => 'good', 'title' => 'Gypsum on a sodic soil',
                'text' => $names($kinds['gypsum']) . ' pushes sodium off the soil, which frees some ' . $word . ' the sodium held, without raising the pH.' . $shift,
                'from' => $first($kinds['gypsum'])];
            $causes[] = ['kind' => 'gypsum', 'from' => $first($kinds['gypsum']), 'to' => null];
        }
        // Nitrates push the pH up around the roots: on a high pH soil that locks the micros a little more.
        if ($kinds['nitrate'] && $alk && in_array($el, ['Zn', 'Fe', 'Mn', 'B', 'P2O5'], true)) {
            $notes[] = ['k' => 'links', 'icon' => '⚗️', 'tone' => 'warn', 'title' => 'Nitrate fertilizer on a high pH soil',
                'text' => $names($kinds['nitrate']) . ' raises the pH around the roots a little, and on this soil ' . $word . ' locks up a little more.' . $shift,
                'from' => $first($kinds['nitrate'])];
        }
        // The plan's own lockouts by amount (NpkNeed::model): from the day the products that make them go on.
        $by = ['high_p' => 'P2O5', 'high_k' => 'K2O', 'excess_zn' => 'Zn', 'high_ca' => 'Ca', 'high_mg' => 'Mg', 'high_s' => 'S', 'high_nh4' => 'N'];
        foreach ((array) ($plan['lockouts'] ?? []) as $lk) {
            $w = (float) (NpkModel::ELEMENTS[$el]['weights'][$lk] ?? 0);
            if ($w <= 0 || $lk === 'liming' || ! isset($by[$lk])) {
                continue;
            }
            $src = array_values(array_filter($apps, fn ($a) => (float) ($a['nut'][$by[$lk]] ?? 0) > 0));
            if (! $src) {
                continue;
            }
            $notes[] = ['k' => 'links', 'icon' => '⚖️', 'tone' => 'warn', 'title' => ucfirst($word) . ': ' . self::LOCK_WORD[$lk],
                'text' => 'The season\'s ' . (NpkNeed::NAMES[$by[$lk]] ?? $by[$lk]) . ' from ' . $names($src) . ' is heavy for this field, and ' . self::LOCK_WORD[$lk] . '.' . $shift,
                'from' => $first($src)];
            $causes[] = ['kind' => $lk, 'from' => $first($src), 'to' => null];
        }

        return [$notes, $causes];
    }

    /** Other nutrients the season's products move, for a line each: element, which way, and why. */
    private static function otherLinks(array $base, array $plan, string $el): array
    {
        $out = [];
        $rank = ['notneeded' => 0, 'testok' => 0, 'none' => 1, 'watch' => 2, 'likely' => 3, 'test' => 4];
        foreach ((array) ($plan['micro'] ?? []) as $e => $mp) {
            if ($e === $el || ! isset($base['micro'][$e]) || ! empty(NpkModel::ELEMENTS[$e]['beneficial'])) {
                continue;
            }
            $mb = $base['micro'][$e];
            $up = ($rank[$mp['level']] ?? 1) - ($rank[$mb['level']] ?? 1);
            $ds = (float) ($mp['share'] ?? 1) - (float) ($mb['share'] ?? 1);
            if ($up === 0 && abs($ds) < 0.03) {
                continue;
            }
            $worse = $up > 0 || $ds < 0;
            $why = $worse ? array_values(array_diff((array) ($mp['why'] ?? []), (array) ($mb['why'] ?? []))) : array_values(array_diff((array) ($mp['down'] ?? []), (array) ($mb['down'] ?? [])));
            $out[] = ['el' => $e, 'name' => NpkNeed::NAMES[$e] ?? $e, 'way' => $worse ? 'less' : 'more', 'why' => (string) ($why[0] ?? ($worse ? 'the season\'s products hold it back' : 'the season\'s products free some of it')),
                'key' => isset(RangeEffect::NUTRIENTS[$e]) ? 'n:' . $e : null];
        }
        foreach (NpkNeed::NPK as $e) {
            if ($e === $el || ! isset($base['need'][$e], $plan['need'][$e]) || ! $base['need'][$e]) {
                continue;
            }
            $d = ((float) $plan['need'][$e] - (float) $base['need'][$e]) / (float) $base['need'][$e];
            if (abs($d) < 0.03) {
                continue;
            }
            $k = ['N' => 'N', 'P2O5' => 'P', 'K2O' => 'K'][$e];
            $out[] = ['el' => $e, 'name' => NpkNeed::NAMES[$e], 'way' => $d > 0 ? 'less' : 'more', 'why' => $d > 0 ? 'the season\'s products lock or wash part of it' : 'the season\'s products free some of it', 'key' => 'n:' . $k];
        }

        return array_slice($out, 0, 8);
    }

    /**
     * How much a day: the need spread over the crop's days by what each stage takes, against what each product
     * gives (its kg a hectare spread over its working days by its curve; none while it is still in the soil).
     */
    private static function amounts(array $days, array $apps, string $el, string $say, string $word, ?array $base, ?array $plan, array $causes, array $info): array
    {
        $n = count($days);
        $need = self::needOf($base, $el);
        $needPlan = $plan ? self::needOf($plan, $el) : $need;
        $out = ['el' => $el, 'say' => $say, 'word' => $word, 'known' => $need !== null];
        // What each product gives a day.
        $give = array_fill(0, $n, 0.0);
        $applied = 0.0;
        $first = $n ? Carbon::parse($days[0]['d']) : null;
        foreach ($apps as $a) {
            $k = $a['curve'];
            if (! $k || ! $first) {
                continue;
            }
            $kg = $a['nut'][$el] ?? null;
            if ($kg === null) {
                // Carried, amount not read: one usual dose.
                $kg = ($a['form'] === 'foliar' ? RangeEffect::REF_FOLIAR : RangeEffect::REF_SOIL)[$el] ?? 0;
            }
            $kg = (float) $kg;
            if ($kg <= 0) {
                continue;
            }
            $applied += $kg;
            $shape = [];
            $sum = 0.0;
            for ($t = 0; $t <= (int) $k['hi']; $t++) {
                $v = RangeEffect::level($k, $t) / max(0.0001, (float) $k['amp']);
                $shape[$t] = $v;
                $sum += $v;
            }
            if ($sum <= 0) {
                continue;
            }
            $off = (int) $first->diffInDays($a['date'], false);
            foreach ($shape as $t => $v) {
                $i = $off + $t;
                if ($i >= 0 && $i < $n) {
                    $give[$i] += $kg * $v / $sum;
                }
            }
        }
        if ($need === null) {
            return $out + ['g' => array_map(fn ($v) => round($v, 3), $give), 'applied' => round($applied, 2)];
        }
        // The need over the crop's days, by what each stage takes; the links' shift from the day its cause starts.
        // Where the book says how much each stage takes this element, a stage that takes none gets almost none of
        // the need; where it says nothing, every day of the crop takes alike.
        $hasCrit = false;
        foreach ($days as $i => $d) {
            $hasCrit = $hasCrit || (! empty($info[$i]['in']) && (int) $d['c'] > 0);
        }
        $w = [];
        $sw = 0.0;
        foreach ($days as $i => $d) {
            $w[$i] = ! empty($info[$i]['in']) ? ($hasCrit ? self::TAKE[max(0, min(3, (int) $d['c']))] : 1.0) : 0.0;
            $sw += $w[$i];
        }
        $delta = $needPlan !== null ? $needPlan - $need : 0.0;
        $needD = [];
        $state = [];
        $givenIn = 0.0;
        $needT = 0.0;
        $runs = ['short' => [], 'low' => []];
        $open = [];
        foreach ($days as $i => $d) {
            $on = 0;
            foreach ($causes as $cz) {
                if ($cz['from'] && $d['d'] >= $cz['from'] && ($cz['to'] === null || $d['d'] <= $cz['to'])) {
                    $on = 1;
                    break;
                }
            }
            $season = max(0.0, $need + $delta * $on);
            $nd = $sw > 0 ? $season * $w[$i] / $sw : 0.0;
            $needD[$i] = round($nd, 3);
            $g = $give[$i];
            if (! empty($info[$i]['in'])) {
                $givenIn += $g;
                $needT += $nd;
            }
            // Outside the crop nothing is judged; on a stage that takes none, what is given is extra.
            $s = '';
            if (empty($info[$i]['in'])) {
                $s = '';
            } elseif ($hasCrit && (int) $d['c'] === 0) {
                $s = $g > 0.0005 ? 'extra' : '';
            } elseif ($nd > 0.0005) {
                $r = $g / $nd;
                $s = $r >= 0.9 ? 'met' : ($r >= 0.5 ? 'low' : 'short');
            } elseif ($g > 0.0005) {
                $s = 'extra';
            }
            $state[$i] = $s;
            // Runs of short and low days on the critical ones, for the notes.
            foreach (['short', 'low'] as $rk) {
                if ($s === $rk && (int) $d['c'] >= 2) {
                    $open[$rk] ??= ['from' => $d['d'], 'to' => $d['d'], 'n' => 0];
                    $open[$rk]['to'] = $d['d'];
                    $open[$rk]['n']++;
                } elseif (isset($open[$rk])) {
                    $runs[$rk][] = $open[$rk];
                    unset($open[$rk]);
                }
            }
        }
        foreach ($open as $rk => $r) {
            $runs[$rk][] = $r;
        }

        return $out + [
            'need' => round($needT, 1), 'needBook' => round($need, 1), 'needPlan' => $needPlan !== null ? round($needPlan, 1) : null, 'given' => round($givenIn, 1), 'applied' => round($applied, 1),
            'pct' => $needT > 0 ? (int) round(100 * $givenIn / $needT) : null, 'soilGives' => $needT <= 0.0005,
            'g' => array_map(fn ($v) => round($v, 3), $give), 'n' => array_values($needD), 's' => array_values($state), 'in' => array_map(fn ($x) => empty($x['in']) ? 0 : 1, $info),
            'short' => array_slice($runs['short'], 0, 4), 'low' => array_slice($runs['low'], 0, 3),
            'how' => $base['how'][$el] ?? (isset($base['micro'][$el]) ? 'micro' : null), 'goal' => $base['goal']['Y'] ?? null, 'goalWhy' => $base['goal']['why'] ?? '',
        ];
    }

    /** A leaf spray weighed against the need: it tops up for a week or two, it cannot carry the crop. */
    private static function foliarNotes(array $rel, string $el, string $say, ?array $amt, array $c): array
    {
        $out = [];
        $need = (float) ($amt['needBook'] ?? 0);
        foreach ($rel as $a) {
            if ($a['form'] !== 'foliar' || ! isset($a['nut'][$el])) {
                continue;
            }
            $kg = $a['nut'][$el] === null ? (RangeEffect::REF_FOLIAR[$el] ?? 0) : (float) $a['nut'][$el];
            $rice = $c['crop'] && str_starts_with((string) $c['crop'], 'rice');
            if ($el === 'Si') {
                $text = 'A silicon spray (' . $a['as'] . ', ' . $a['date']->format('M j') . ') gives about ' . self::kg($kg) . ' kg a hectare, taken in by the leaves and gone in a week or two. '
                    . ($rice ? 'Rice takes a lot of silicon from the soil over a season, far more than any spray can carry; silicon from the soil (rice straw or hull ash put back, or calcium silicate) is what fills it. ' : '')
                    . 'A spray mostly toughens the leaves for a while against blast and sucking pests.';
            } elseif ($need > 0 && $kg < 0.15 * $need) {
                $text = 'A leaf spray (' . $a['as'] . ', ' . $a['date']->format('M j') . ') gives about ' . self::kg($kg) . ' kg ' . $say . ' a hectare, against the field\'s ' . self::kg($need)
                    . ' for the season. The leaves take it in within a day or two and it is gone in about a week: it tops the crop up, it cannot carry it. The bulk has to come through the soil.';
            } else {
                $text = 'A leaf spray (' . $a['as'] . ', ' . $a['date']->format('M j') . ') acts within a day or two and fades in about a week; granules in the soil feed for weeks. Time sprays to the days that matter.';
            }
            $out[] = ['k' => 'amt', 'icon' => '💧', 'tone' => 'info', 'title' => 'A spray is not a granule', 'text' => $text, 'from' => $a['date']->toDateString()];
            if (count($out) >= 2) {
                break;
            }
        }

        return $out;
    }

    /** A kilo figure said short: "120", "3.5", "0.4". */
    public static function kg(float $v): string
    {
        return $v >= 10 ? (string) (int) round($v) : rtrim(rtrim(number_format($v, $v >= 1 ? 1 : 2, '.', ''), '0'), '.');
    }
}
