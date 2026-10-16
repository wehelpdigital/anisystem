<?php

namespace App\Support;

/**
 * What the Crop Protocol reads from the app's own shelves instead of
 * researching it (2026-10-18, the owner: every module uses this data, for
 * output that is more accurate and costs fewer real tokens). The ONE place
 * where a run's soil, variety and hazard facts are gathered for the asks:
 *
 *  - soil():    the area's typical fertility, pH and salinity by place name
 *               (App\Support\SoilAtlas::for / ::words). The lot's own answers
 *               and its soil test always win; the area only fills what is
 *               unknown (fillNeed()).
 *  - variety(): the registration trial facts. VarietyBook::find() already
 *               carries them as the record's `trials` (and promptLines() says
 *               them compactly); trialWords($rec, true) is the full block.
 *  - hazards(): twenty years of typhoons, floods and droughts by town.
 *
 * Every reader is guarded (class_exists / method_exists, try/catch), so a
 * shelf that is not deployed yet reads as nothing and the protocol runs as
 * before. Each answer is ['lines' => prompt text, 'data' => the raw facts],
 * or null. What a farmer reads never names where a fact comes from
 * (GovSource): the lines are scrubbed here, and the prompts say the rule.
 */
final class ProtocolFacts
{
    /** One request's reads, by place and crop (the live need card asks often). */
    private static array $memo = [];

    /**
     * Everything for one run, read while the request is open:
     * ['lots' => [i => ['soil' => ?array, 'place' => key]], 'places' => [key => ['name' => place, 'hazards' => ?array]], 'cases' => [key => ['variety' => ?array]]].
     */
    public static function gather(array $case, array $cases, array $records, array $focusByPlace = []): array
    {
        $out = ['lots' => [], 'places' => [], 'cases' => []];
        foreach ($case['lots'] as $l) {
            $key = self::placeKey((string) $l['place']);
            $out['lots'][$l['i']] = ['soil' => self::soil((string) $l['place'], (string) $l['crop']), 'place' => $key];
            if (! isset($out['places'][$key]) && count($out['places']) < 3) {
                $focus = array_map('intval', array_keys((array) ($focusByPlace[$l['place']] ?? [])));
                sort($focus);
                $out['places'][$key] = ['name' => (string) $l['place'], 'hazards' => self::hazards((string) $l['place'], $focus)];
            }
        }
        foreach ($cases as $c) {
            $out['cases'][$c['key']] = ['variety' => self::variety($records[$c['lots'][0]] ?? null)];
        }

        return $out;
    }

    /** The province and town a place string names ("Science City of Munoz, Nueva Ecija"). */
    public static function placeParts(string $place): array
    {
        $province = ClimateRecord::placeKey($place)['place'];
        $parts = array_values(array_filter(array_map('trim', explode(',', $place)), fn ($p) => $p !== '' && ! preg_match('/^philippines$/i', $p)));
        $town = null;
        foreach ($parts as $p) {
            if (mb_strtolower($p) !== mb_strtolower($province)) {
                $town = $p; // the first part that is not the province
                break;
            }
        }

        return ['province' => $province, 'town' => $town];
    }

    /* ================================================================ soil */

    /**
     * The area's typical soil for one crop: ['lines', 'data', 'tests' (NpkNeed
     * vocabulary), 'texture', 'phMid', 'conditions'], or null.
     */
    public static function soil(string $place, string $crop): ?array
    {
        if ($place === '' || ! class_exists(SoilAtlas::class) || ! method_exists(SoilAtlas::class, 'for')) {
            return null;
        }
        $cropKey = FieldCatalogue::cropKey($crop) ?? $crop;
        $memo = 'soil|' . mb_strtolower($place) . '|' . $cropKey;
        if (array_key_exists($memo, self::$memo)) {
            return self::$memo[$memo];
        }
        $got = null;
        try {
            $pp = self::placeParts($place);
            $a = SoilAtlas::for($pp['province'], $pp['town'], null, $cropKey);
            if (is_array($a) && $a) {
                $words = method_exists(SoilAtlas::class, 'words') ? (string) SoilAtlas::words($a, 'prompt') : '';
                $got = [
                    'lines' => self::clean($words),
                    'data' => $a,
                    'tests' => array_filter((array) ($a['tests'] ?? []), fn ($v) => in_array($v, ['low', 'medium', 'high'], true)),
                    'texture' => in_array($a['texture']['typical'] ?? null, ['clay', 'loam', 'sandy'], true) ? $a['texture']['typical'] : null,
                    'phMid' => isset($a['phMid']) && is_numeric($a['phMid']) ? (float) $a['phMid'] : null,
                    'conditions' => array_values(array_map('strval', (array) ($a['conditions'] ?? []))),
                ];
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return self::$memo[$memo] = $got;
    }

    /**
     * NPK Plus's inputs with the area's typical soil filling only what the
     * lot left unknown: a nutrient its soil test does not have, the texture
     * when the lot is unsure, the pH when none is known. Says what it filled
     * in `areaSoil`, so the report and the prompt can call it typical for
     * the area, never a test of this field.
     */
    public static function fillNeed(array $in, string $place): array
    {
        $s = self::soil($place, (string) ($in['crop'] ?? ''));
        if (! $s) {
            return $in;
        }
        $filled = [];
        foreach ($s['tests'] as $el => $level) {
            if (in_array($el, ['N', 'P2O5', 'K2O'], true) && ! isset($in['tests'][$el])) {
                $in['tests'][$el] = $level;
                $filled[] = $el;
            }
        }
        if (($in['texture'] ?? 'unsure') === 'unsure' && $s['texture']) {
            $in['texture'] = $s['texture'];
            $filled[] = 'texture';
        }
        if (($in['ph'] ?? null) === null && $s['phMid'] !== null) {
            $in['ph'] = $s['phMid'];
            $filled[] = 'ph';
        }
        if ($filled) {
            $in['areaSoil'] = $filled;
        }

        return $in;
    }

    /* ============================================================= variety */

    /**
     * The variety's registration trial facts, from the record VarietyBook::find
     * returned: ['lines' => the full block, 'data' => trials, 'covered' => true
     * when its ratings and stresses are there, so research need not look them
     * up], or null.
     */
    public static function variety(?array $rec): ?array
    {
        if (! $rec || empty($rec['trials']) || ! is_array($rec['trials'])) {
            return null;
        }
        $t = $rec['trials'];
        $lines = '';
        try {
            $lines = method_exists(VarietyBook::class, 'trialWords') ? (string) VarietyBook::trialWords($rec, true) : '';
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'lines' => self::clean($lines),
            'data' => $t,
            'covered' => ! empty($t['ratings']) && (! empty($t['stress']) || ! empty($t['days'])),
        ];
    }

    /* ============================================================= hazards */

    /**
     * Twenty years of typhoons, floods and droughts for a town
     * (App\Support\HazardBook): ['lines' => the ready block, its focus months
     * marked and their storms first, 'data' => the summary], or null. Kept
     * beside the ClimateStore weather record, never instead of it.
     *
     * @param  list<int>  $focus  the month numbers the crops stand in the field
     */
    public static function hazards(string $place, array $focus = []): ?array
    {
        if ($place === '' || ! class_exists(HazardBook::class) || ! (method_exists(HazardBook::class, 'facts') || method_exists(HazardBook::class, 'words'))) {
            return null;
        }
        $memo = 'hazard|' . mb_strtolower($place) . '|' . implode(',', $focus);
        if (array_key_exists($memo, self::$memo)) {
            return self::$memo[$memo];
        }
        $got = null;
        try {
            if (method_exists(HazardBook::class, 'facts')) {
                // The one door the hazard shelf offers: ['lines', 'data'] or null.
                $f = HazardBook::facts($place, array_values($focus));
                $words = is_array($f) ? trim((string) ($f['lines'] ?? '')) : '';
                $data = is_array($f) ? ($f['data'] ?? null) : null;
            } else {
                $pp = method_exists(HazardBook::class, 'fromLocation') ? (array) HazardBook::fromLocation($place) : self::placeParts($place);
                $words = trim((string) HazardBook::words($pp['province'] ?? null, $pp['town'] ?? null, array_values($focus), 2400));
                $data = method_exists(HazardBook::class, 'summary') ? HazardBook::summary($pp['province'] ?? null, $pp['town'] ?? null) : null;
            }
            if ($words !== '') {
                $got = ['lines' => self::clean($words), 'data' => is_array($data) ? $data : null];
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return self::$memo[$memo] = $got;
    }

    /* ============================================================== prompts */

    /** The soil block of one lot's prompt, said as typical for the area ('' when unknown). */
    public static function soilLines(?array $facts): string
    {
        $w = trim((string) ($facts['lines'] ?? ''));

        return $w === '' ? '' : $w . "\n" . '(The lot\'s own answers and its soil test above always win over this; it only fills what they leave unknown.)' . "\n";
    }

    /** The hazard block for a place ('' when unknown). */
    public static function hazardLines(?array $facts, string $place): string
    {
        $w = trim((string) ($facts['lines'] ?? ''));

        return $w === '' ? '' : '- Twenty years of typhoons, floods and droughts at ' . $place . ":\n" . $w . "\n";
    }

    /** The variety's trial block ('' when unknown). */
    public static function varietyLines(?array $facts): string
    {
        $w = trim((string) ($facts['lines'] ?? ''));

        return $w === '' ? '' : '- Its trial facts (take them as known): ' . $w . "\n";
    }

    /** No source names in what reaches a prompt or a report. */
    private static function clean(string $s): string
    {
        $s = trim($s);
        if ($s === '' || ! class_exists(GovSource::class) || ! method_exists(GovSource::class, 'scrub')) {
            return $s;
        }
        try {
            return (string) GovSource::scrub($s);
        } catch (\Throwable) {
            return $s;
        }
    }

    private static function placeKey(string $place): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $place)));
    }
}
