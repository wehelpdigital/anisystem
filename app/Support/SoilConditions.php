<?php

namespace App\Support;

/**
 * What the soil is like chemically, in the words every analysis asks it in
 * (When to Plant, What to Plant, Variety Research, Crop Protocol). The
 * owner, 2026-09-29: each questionnaire asks whether the soil is acidic,
 * alkaline, sodic and so on, and each analysis weighs the answer.
 *
 * A soil can be more than one of these at once -- alkaline AND sodic is the
 * usual pair, saline AND alkaline another, and an acid sulfate soil is acidic
 * by nature -- so the answer is a list. The three pH words exclude one
 * another; sodium, salt and acid sulfate are their own axes and ride with
 * any of them. Crop Protocol asked this first; its keys are these keys.
 */
class SoilConditions
{
    public const OPTIONS = [
        'unsure' => 'Not sure / never tested',
        'acidic' => 'Acidic — low pH (moss, ferns, poor legumes)',
        'neutral' => 'Around neutral',
        'alkaline' => 'Alkaline — high pH (pale young leaves, white crust)',
        'sodic' => 'Sodic — high sodium (crusts, seals, water sits, dispersive)',
        'saline' => 'Saline — salty (white crust, burnt leaf tips, brackish water)',
        'acid_sulfate' => 'Acid sulfate — very sour (yellow mottles, rusty red water, old mangrove or swamp land)',
    ];

    public const PH_WORDS = ['acidic', 'neutral', 'alkaline'];

    /**
     * A clean list: known keys only, 'unsure' dropped, one pH word at most
     * (the last one named wins), the other axes free to ride with it. A
     * string (an old single answer) is one item.
     *
     * @return list<string>
     */
    public static function normalize(mixed $raw): array
    {
        $keys = is_array($raw) ? $raw : (is_string($raw) && $raw !== '' ? [$raw] : []);
        $out = [];
        $ph = null;
        foreach ($keys as $k) {
            $k = (string) $k;
            if ($k === 'unsure' || ! array_key_exists($k, self::OPTIONS)) {
                continue;
            }
            if (in_array($k, self::PH_WORDS, true)) {
                $ph = $k;

                continue;
            }
            $out[$k] = true;
        }
        $list = array_keys($out);
        if ($ph) {
            array_unshift($list, $ph);
        }

        return $list;
    }

    /** A tested pH, if one was given and it is a pH at all. */
    public static function phValue(mixed $raw): ?float
    {
        if ($raw === null || $raw === '' || ! is_numeric($raw)) {
            return null;
        }
        $v = round((float) $raw, 1);

        return ($v >= 2 && $v <= 12) ? $v : null;
    }

    /** The conditions in words, for a prompt or a summary line. */
    public static function words(array $list, ?float $ph = null): string
    {
        $said = implode('; AND ', array_map(fn ($k) => self::OPTIONS[$k] ?? $k, $list));
        if ($ph !== null) {
            $said = ($said !== '' ? $said . '; ' : '') . 'tested pH ' . rtrim(rtrim(number_format($ph, 1), '0'), '.');
        }

        return $said !== '' ? $said : 'not known (never tested)';
    }

    /** Short words for a summary chip: "Acidic, sodic · pH 5.2". */
    public static function short(array $list, ?float $ph = null): string
    {
        $names = ['acidic' => 'Acidic', 'neutral' => 'Neutral', 'alkaline' => 'Alkaline', 'sodic' => 'Sodic', 'saline' => 'Saline', 'acid_sulfate' => 'Acid sulfate'];
        $bits = array_map(fn ($k) => $names[$k] ?? $k, $list);
        $s = implode(', ', $bits);
        if ($ph !== null) {
            $s .= ($s !== '' ? ' · ' : '') . 'pH ' . rtrim(rtrim(number_format($ph, 1), '0'), '.');
        }

        return $s;
    }

    /**
     * What each condition means for the analysis, so the model weighs it the
     * same way everywhere: the lead time a correction needs before planting,
     * which weather makes it worse at emergence, and what it does to crop and
     * variety choice. Only the lines for the conditions given.
     */
    public static function guidance(array $list): string
    {
        $g = [
            'acidic' => 'ACIDIC: aluminium and manganese toxicity, phosphorus locked up, legumes and many vegetables struggle; agricultural lime or dolomite is applied and worked in 2 to 4 weeks BEFORE planting, so the window starts after that lead time; acid-tolerant crops and varieties are favoured.',
            'alkaline' => 'ALKALINE: zinc and iron deficiency (worst in young, flooded rice), phosphorus fixed; zinc sulfate or foliar zinc early, acidifying fertilizers (ammonium sulfate), organic matter; favour varieties tolerant of high pH.',
            'sodic' => 'SODIC: high exchangeable sodium, the soil disperses, crusts and seals, water sits and seedlings struggle to emerge; gypsum applied and leached with good water BEFORE planting (weeks), so the window starts after it; avoid sowing just before heavy rain (crusting, waterlogging at emergence); favour sodicity-tolerant crops and varieties.',
            'saline' => 'SALINE: salts pull water from roots, burnt leaf tips, poor stand; salts concentrate in the late dry season and are flushed by good rain or irrigation, so plant after the first leaching rains or a pre-plant flush, not in the driest weeks; transplant older seedlings where the crop allows; favour salt-tolerant crops and varieties (for rice, the saline-tolerant NSIC lines).',
            'acid_sulfate' => 'ACID SULFATE: pyrite in the subsoil turns to sulfuric acid when it dries and meets air; keep it wet, do not let it dry and crack between crops, flush the first acid water out, lime; the risky time is the first rains after a dry spell, when the acid is washed up; favour tolerant crops and varieties.',
            'neutral' => 'NEUTRAL: no pH limitation; say so where it helps.',
        ];
        $lines = array_values(array_filter(array_map(fn ($k) => $g[$k] ?? null, $list)));

        return implode("\n", $lines);
    }
}
