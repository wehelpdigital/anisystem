<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Anee Auto's book (2026-10-12): the crop facts it plans with and the
 * words its wizard and calendar speak.
 *
 * The crop facts are researched, one file per release
 * (database/anee-auto/crops.json): how long the land takes to prepare, how
 * long a seedling stays in the seedbed, the preventive sprays a season
 * usually needs by active ingredient and stage, and the safety rules that
 * come with them, each with its sources. The rest is the wizard's
 * vocabulary: what a soil looks like and what that means, where the water
 * comes from, what went wrong before, why a task could not be done.
 */
final class AutoBook
{
    /** What the soil does, and what each sign points to. */
    public const SOIL_SIGNS = [
        'cracks' => ['Wide, deep cracks when it dries', 'Clay soil', ['texture' => 'clay']],
        'sticky' => ['Sticky and slick when wet, clings to boots', 'Clay soil', ['texture' => 'clay']],
        'gritty' => ['Gritty; water sinks in fast and it dries within a day', 'Sandy soil', ['texture' => 'sandy']],
        'crumbly' => ['Soft and crumbly; holds water but still drains', 'Loam', ['texture' => 'loam']],
        'white_crust' => ['A white powdery crust on top when it dries', 'Salt at the surface', ['salt' => 1]],
        'black_crust' => ['A dark, slick crust; puddles stay muddy and cloudy for days', 'Sodium in the soil', ['sodic' => 2]],
        'hard_clods' => ['Dries into hard, cement like clods; water soaks in very slowly', 'Dispersed soil', ['sodic' => 1]],
        'salty' => ['Near the sea, or the water tastes salty', 'Salty ground or water', ['saline' => 2]],
        'burnt_tips' => ['Leaf tips burn brown even with water around', 'Salt stress', ['saline' => 1]],
        'rusty' => ['Rusty orange stains or scum on the water; yellow streaks when dug', 'Acid sulfate soil', ['acid_sulfate' => 2]],
        'sour_smell' => ['Smells sour, or of rotten eggs, when dug', 'Acid sulfate soil', ['acid_sulfate' => 1]],
        'red_soil' => ['Red or yellow brown soil, on a slope or upland', 'Weathered, acidic soil', ['acidic' => 1]],
        'ferns' => ['Ferns, cogon and moss grow well around it', 'Acidic soil', ['acidic' => 1]],
        'fizz' => ['White specks or stones that fizz with vinegar', 'Limy (calcareous) soil', ['alkaline' => 2]],
        'yellow_young' => ['Young leaves turn yellow or striped between the veins', 'Iron or zinc locked up by a high pH', ['alkaline' => 1]],
        'dark_rich' => ['Dark and rich, plenty of earthworms', 'Good organic matter', []],
        'pale_hard' => ['Pale and hard, crusts after rain', 'Low organic matter', ['low_om' => 1]],
        'stays_wet' => ['Water stands for days after rain', 'Poor drainage', ['waterlogged' => 1]],
    ];

    public const PH = [
        'unsure' => ['I do not know', 'Anee reads it from the signs above'],
        'acidic' => ['Acidic', 'Below 6.5, or a test said so'],
        'neutral' => ['Around neutral', 'About 6.5 to 7.5'],
        'alkaline' => ['Alkaline', 'Above 7.5, or a test said so'],
    ];

    /** Where the water comes from, as it really is. [label, hint, NPK Plus water key] */
    public const WATER = [
        'canal_ok' => ['Irrigation canal, and the water comes on time', 'NIA or a communal system that rarely fails us', 'irrigated'],
        'canal_late' => ['Irrigation canal, but the water comes late or runs short', 'We are at the tail end, or the schedule is not kept', 'partial'],
        'pump_own' => ['Our own pump or well, whenever we need it', 'Shallow tube well, deep well or our own engine pump', 'irrigated'],
        'pump_rain' => ['We have a pump, but mostly wait for the rain', 'The pump only runs when it is really dry: fuel costs', 'partial'],
        'pump_shared' => ['We rent or share a pump, by turns', 'Water when our turn comes', 'partial'],
        'river' => ['We pump from a river, creek or pond nearby', 'Water is there unless the dry months take it', 'partial'],
        'reservoir' => ['A small farm reservoir or water impounding', 'Stored rain for the dry spells', 'partial'],
        'rain_only' => ['No pump nearby; we wait for the rain', 'Fully rainfed', 'rainfed'],
    ];

    /** What hurt the yield in past seasons. */
    public const PROBLEMS = [
        'weeds' => ['Weeds took over', '🌿'],
        'flood' => ['Floods', '🌊'],
        'drought' => ['Drought, not enough rain', '☀️'],
        'pump_dry' => ['The pump or well ran dry', '🚱'],
        'typhoon' => ['Typhoon damage', '🌀'],
        'insects' => ['Insect pests', '🐛'],
        'diseases' => ['Plant diseases', '🍂'],
        'rats' => ['Rats', '🐀'],
        'birds' => ['Birds at harvest', '🐦'],
        'snails' => ['Golden apple snails', '🐌'],
        'lodging' => ['Plants fell over before harvest', '🌾'],
        'salt' => ['Salty water or soil', '🧂'],
        'poor_stand' => ['Poor germination, a thin stand', '🌱'],
        'late_fert' => ['Fertilizer came late or was too little', '🧪'],
        'labor' => ['Not enough workers at the right time', '👥'],
        'waterlog' => ['Water stayed too long in the field', '💧'],
        'heat' => ['Too much heat at flowering', '🔥'],
    ];

    /** Why a task could not be done today. [label, emoji, days to wait at least, weather says so] */
    public const CANT = [
        'rain' => ['It is raining or too wet', '🌧️', 1, true],
        'wind' => ['Too windy to spray', '💨', 1, true],
        'flooded' => ['The field is flooded', '🌊', 2, true],
        'workers' => ['No workers today', '👥', 1, false],
        'supplies' => ['Supplies not bought yet', '🛒', 2, false],
        'money' => ['No budget yet', '💸', 3, false],
        'machine' => ['No tractor or machine available', '🚜', 2, false],
        'sick' => ['Sick, or a family matter', '🤒', 1, false],
        'other' => ['Something else', '✍️', 1, false],
    ];

    /** Going on with an unbalanced plan, and why (saved for the owner). */
    public const IMBALANCE = [
        'budget' => 'Budget: this is what we can afford now',
        'supply' => 'These are what the store has',
        'manure' => 'We add manure or compost, not counted here',
        'later' => 'We will add more later in the season',
        'habit' => 'This has worked for us before',
        'technician' => 'A technician or a dealer advised this',
        'other' => 'Another reason',
    ];

    /** The kinds of task on the calendar: [label, emoji, weather rule, colour]. */
    public const KINDS = [
        'land_prep' => ['Land preparation', '🚜', 'dry', 'amber'],
        'nursery' => ['Seedbed and seedlings', '🌱', 'any', 'leaf'],
        'sowing' => ['Sowing', '🌾', 'dry', 'leaf'],
        'transplant' => ['Transplanting', '🤲', 'any', 'leaf'],
        'planting' => ['Planting', '🌳', 'any', 'leaf'],
        'irrigation' => ['Water', '💧', 'any', 'sky'],
        'drainage' => ['Drain', '🕳️', 'any', 'sky'],
        'fertilizer' => ['Fertilizer', '🧪', 'fert', 'gold'],
        'foliar' => ['Foliar feed', '🍃', 'spray', 'gold'],
        'herbicide' => ['Weed control', '🌿', 'spray', 'rose'],
        'insecticide' => ['Insect control', '🐛', 'spray', 'rose'],
        'fungicide' => ['Disease control', '🍄', 'spray', 'rose'],
        'weeding' => ['Hand weeding', '✋', 'any', 'leaf'],
        'scouting' => ['Walk the field', '👀', 'any', 'sky'],
        'protect' => ['Protect the crop', '🛡️', 'any', 'amber'],
        'prepare' => ['Get ready', '🛒', 'any', 'amber'],
        'harvest' => ['Harvest', '🧺', 'dry', 'gold'],
        'post_harvest' => ['After the harvest', '📦', 'dry', 'amber'],
        'other' => ['Farm work', '🧑‍🌾', 'any', 'leaf'],
    ];

    private static ?array $book = null;

    /** @return array<string, array> every crop's facts, keyed by CropCatalog key */
    public static function crops(): array
    {
        if (self::$book === null) {
            $raw = json_decode((string) @file_get_contents(database_path('anee-auto/crops.json')), true);
            self::$book = is_array($raw['crops'] ?? null) ? $raw['crops'] : [];
        }

        return self::$book;
    }

    public static function crop(?string $key): ?array
    {
        return $key ? (self::crops()[$key] ?? null) : null;
    }

    /** How many days the land takes to prepare: [least, usual], with the source. */
    public static function landPrep(?string $key): array
    {
        $c = self::crop($key);
        $d = $c['landPrepDays'] ?? [14, 21];

        return ['min' => (int) ($d[0] ?? 14), 'max' => (int) ($d[1] ?? $d[0] ?? 21), 'note' => $c['landPrepNote'] ?? null, 'source' => $c['landPrepSource'] ?? null];
    }

    /** Days a seedling stays in the seedbed, when the crop is transplanted. */
    public static function nursery(?string $key): ?array
    {
        $d = self::crop($key)['nurseryDays'] ?? null;

        return is_array($d) && $d ? ['min' => (int) $d[0], 'max' => (int) ($d[1] ?? $d[0])] : null;
    }

    /** The ways a crop goes into the field, as the wizard offers them. */
    public static function methods(?string $key): array
    {
        if (! $key) {
            return [];
        }
        if (CropCatalog::isPerennial($key)) {
            return ['tree' => 'Trees already standing', 'planted' => 'Planting new trees'];
        }
        $out = [];
        foreach (CropCatalog::countersFor($key) as $c) {
            $out += match ($c) {
                'DAT' => ['transplanted' => 'Transplanted from a seedbed (DAT)'],
                'DAS' => ['direct' => 'Direct seeded in the field (DAS)'],
                default => ['planted' => 'Planted in the field (DAP)'],
            };
        }

        return $out;
    }

    /** The day counter a method keeps on the lot. */
    public static function counterOf(string $method): string
    {
        return match ($method) {
            'transplanted' => 'DAT',
            'direct' => 'DAS',
            'tree' => 'TREE',
            default => 'DAP',
        };
    }

    /**
     * What the soil signs say: a texture, the conditions NPK Plus counts,
     * a pH word and how sure. Signs that point two ways are weighed; a pH
     * the farmer knows wins over the signs, except that sodium and salt
     * stay what the signs say.
     */
    public static function readSoil(array $signs, ?string $ph, ?float $tested = null): array
    {
        $signs = array_values(array_intersect(array_keys(self::SOIL_SIGNS), $signs));
        $w = ['clay' => 0, 'sandy' => 0, 'loam' => 0];
        $score = ['salt' => 0, 'sodic' => 0, 'saline' => 0, 'acid_sulfate' => 0, 'acidic' => 0, 'alkaline' => 0, 'low_om' => 0, 'waterlogged' => 0];
        $why = [];
        foreach ($signs as $s) {
            [, $means, $ev] = self::SOIL_SIGNS[$s];
            foreach ($ev as $k => $v) {
                if ($k === 'texture') {
                    $w[$v] += 1;
                } else {
                    $score[$k] += (int) $v;
                }
            }
            $why[] = $means;
        }
        arsort($w);
        $texture = reset($w) > 0 ? array_key_first($w) : 'unsure';

        $conds = [];
        // White crust alone is salt; with a dark slick crust or hard clods it is sodium.
        if ($score['sodic'] >= 2 || ($score['salt'] && $score['sodic'] >= 1)) {
            $conds[] = 'sodic';
        }
        if ($score['saline'] >= 1 || ($score['salt'] && ! in_array('sodic', $conds, true))) {
            $conds[] = 'saline';
        }
        if ($score['acid_sulfate'] >= 1) {
            $conds[] = 'acid_sulfate';
        }
        if ($score['low_om'] >= 1) {
            $conds[] = 'low_om';
        }
        if ($score['waterlogged'] >= 1) {
            $conds[] = 'waterlogged';
        }

        $word = in_array($ph, ['acidic', 'neutral', 'alkaline'], true) ? $ph : null;
        if ($tested !== null && $tested >= 2 && $tested <= 12) {
            $word = $tested < 6.5 ? 'acidic' : ($tested > 7.5 ? 'alkaline' : 'neutral');
        }
        $sure = $word !== null;
        if ($word === null) {
            if (in_array('sodic', $conds, true) || $score['alkaline'] >= 1) {
                $word = 'alkaline';
            } elseif ($score['acid_sulfate'] || $score['acidic'] >= 1) {
                $word = 'acidic';
            }
        }
        if ($word) {
            array_unshift($conds, $word);
        }

        return ['texture' => $texture, 'conditions' => array_values(array_unique($conds)), 'ph' => $word ?: 'unsure', 'phSure' => $sure, 'tested' => $tested,
            'signs' => $signs, 'why' => array_values(array_unique($why))];
    }

    /** The lot's crop problems from the field catalogue, worst first, for the wizard to offer. */
    public static function cropProblems(array $crops, int $limit = 10): array
    {
        $keys = array_values(array_filter(array_map(fn ($c) => FieldCatalogue::cropKey($c) ?? $c, $crops)));
        if (! $keys) {
            return [];
        }
        $out = [];
        foreach (['pests', 'diseases'] as $section) {
            foreach (FieldCatalogue::problems($section) as $slug => $p) {
                $hit = array_intersect($keys, (array) ($p['crops'] ?? []));
                if (! $hit) {
                    continue;
                }
                $rank = 99;
                foreach ($hit as $k) {
                    $rank = min($rank, (int) (($p['ranks'][$k] ?? 0) ?: 50));
                }
                $out[] = ['key' => $section . ':' . $slug, 'name' => $p['name'], 'kind' => $section === 'pests' ? 'pest' : 'disease', 'rank' => $rank];
            }
        }
        usort($out, fn ($a, $b) => [$a['rank'], $a['name']] <=> [$b['rank'], $b['name']]);

        return array_slice($out, 0, $limit);
    }

    /**
     * The crop's book in words for Anee's brief: land preparation, nursery,
     * the preventive sprays by stage with their active ingredients, the
     * safety rules.
     */
    public static function forPrompt(?string $key): string
    {
        $c = self::crop($key);
        if (! $c) {
            return 'No crop book entry: plan from general agronomy and say so.';
        }
        $lp = self::landPrep($key);
        $lines = ['Land preparation: ' . $lp['min'] . ' to ' . $lp['max'] . ' days before it goes in the field' . ($lp['note'] ? ' (' . $lp['note'] . ')' : '') . '.'];
        if ($n = self::nursery($key)) {
            $lines[] = 'Seedbed: seedlings are transplanted ' . $n['min'] . ' to ' . $n['max'] . ' days after sowing.';
        }
        foreach ((array) ($c['preventive'] ?? []) as $p) {
            $when = is_array($p['daysAfterPlanting'] ?? null) ? 'day ' . implode(' to ', $p['daysAfterPlanting']) : (string) ($p['daysAfterPlanting'] ?? '');
            $lines[] = 'Preventive, ' . ($p['stage'] ?? '') . ' (' . $when . '): ' . ($p['what'] ?? '') . ($p['activeIngredients'] ? '; active ingredients ' . implode(', ', $p['activeIngredients']) : '') . '. ' . ($p['why'] ?? '');
        }
        foreach ((array) ($c['safety'] ?? []) as $s) {
            $lines[] = 'Safety: ' . $s;
        }

        return implode("\n", $lines);
    }

    /** The newest a planting date may be: the end of next year. */
    public static function latestDate(): Carbon
    {
        return now('Asia/Manila')->addYear()->endOfYear()->startOfDay();
    }
}
