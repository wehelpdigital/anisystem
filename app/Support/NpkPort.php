<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * An NPK Plus calculation as a plan PlanPort can put to work (2026-10-24, the
 * owner: "add a port to protocol builder and cropping schedule the results of
 * npk plus. make sure it also adds the notes, and the inventories or
 * materials").
 *
 * The calculator gives the bags for the whole lot, not their days, so the
 * days are worked out here from the crop's own stages (CropStages):
 *   - lime, sulfur and manure at land preparation, gypsum a week before;
 *   - the bags that carry phosphorus or potassium, and the soil micronutrients,
 *     as the basal at planting;
 *   - the bags of nitrogen alone split into side dressings (two, three on sandy
 *     soil, NpkModel::FIELD splits): the first in the middle of the first
 *     growing stage, the next at the start of the last one before flowering,
 *     a third at the first flower; on sandy soil the potash goes half and half;
 *   - foliar feeds at the peak of growth, inoculants on the seed.
 * Each application carries its bags as materials (the inventory to buy), and
 * the plan's words (the need, the soil, Anee's reading when there is one) go
 * into the Notes and the Rules & notes.
 */
final class NpkPort
{
    public static function row(int $id, int $userId): ?object
    {
        return DB::table('as_npk_calcs')->where('userId', $userId)->where('id', $id)->where('deleteStatus', 1)->first();
    }

    private static function e($s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }

    private static function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, $v >= 10 ? 1 : 2, '.', ','), '0'), '.');
    }

    /** The plan: one lot (the calculation's field) of dated applications. */
    public static function plan(object $r): array
    {
        $res = json_decode((string) $r->result, true) ?: [];
        $set = (array) ($res['setup'] ?? []);
        $lines = array_values(array_filter((array) ($res['lines'] ?? json_decode((string) $r->lines, true) ?? []), 'is_array'));
        $crop = (string) ($r->crop ?: '');
        $key = CropStages::normalize($crop) ?? $crop;
        $ha = max(0.0001, (float) ($res['areaHa'] ?? $r->areaHa ?? 1));
        $perennial = $key !== '' && CropCatalog::isPerennial($key);
        $counters = $key !== '' ? CropCatalog::countersFor($key) : ['DAP'];
        $counter = $perennial ? 'DAP' : ($counters[0] ?? 'DAP');
        $dat = $counter === 'DAT';
        $planting = self::plantingDay($set, $res);
        $dayZero = $dat ? Carbon::parse($planting)->subDays(21)->toDateString() : $planting;
        $at = fn (int $d) => Carbon::parse($planting)->addDays($d)->toDateString();
        $label = $key !== '' ? str_replace(' — ', ', ', (string) (CropCatalog::label($key) ?: $crop)) : 'the crop';
        $lotName = trim((string) ($set['lotName'] ?? '')) ?: 'Field';
        $texture = (string) ($set['texture'] ?? 'unsure');
        $splits = (int) (NpkModel::FIELD['splits'][$texture] ?? 2);
        $rows = $perennial || $key === '' ? [] : CropStages::stagesFor($key, $counter);
        [$nDays, $nStages] = self::nDays($rows, $splits, $perennial);
        $peak = self::peak($rows);
        $hands = max(1, (int) ceil($ha));

        // Each bag to its application.
        $cats = self::categories(array_column($lines, 'id'));
        $apps = [];   // key => ['day', 'title', 'stage', 'type', 'how', 'why', 'lines' => [[line, share]]]
        $put = function (string $k, array $a, array $line, float $share) use (&$apps) {
            $apps[$k] ??= $a + ['lines' => []];
            $apps[$k]['lines'][] = [$line, $share];
        };
        $basal = ['day' => 0, 'title' => 'Basal fertilizer at planting', 'stage' => 'At planting', 'type' => 'fertilizer',
            'how' => $key === 'rice' || str_starts_with($key, 'rice_') ? 'Broadcast it on the last harrowing and work it into the mud.' : 'Band it about 5 cm beside and below the seed, or spread it and work it into the soil at the last harrowing. Keep it off the seed.',
            'why' => 'Phosphorus and potassium move little in the soil, so they go in where the young roots will be.'];
        foreach ($lines as $l) {
            $name = (string) ($l['name'] ?? 'Fertilizer');
            $pct = (array) ($l['pct'] ?? []);
            $cat = $cats[(int) ($l['id'] ?? 0)] ?? '';
            $unit = strtolower((string) ($l['unit'] ?? ''));
            $nOnly = ($pct['N'] ?? 0) > 0 && ($pct['P2O5'] ?? 0) <= 0 && ($pct['K2O'] ?? 0) <= 0;
            if (preg_match('/\blime\b|dolomit|apog|calcit/i', $name) || ($cat === 'secondary' && preg_match('/sulfur/i', $name) && ($pct['S'] ?? 0) >= 80)) {
                $put('lime', ['day' => -14, 'lo' => -21, 'hi' => -10, 'title' => 'Spread the ' . (preg_match('/sulfur/i', $name) ? 'sulfur' : 'lime') . ' at land preparation', 'stage' => 'Land preparation', 'type' => 'fertilizer',
                    'how' => 'Spread it evenly and plow or harrow it in, two weeks or more before planting.', 'why' => 'It moves the soil\'s pH so the crop can take up what the other bags give.'], $l, 1);
            } elseif (preg_match('/gypsum|anhydrite/i', $name)) {
                $put('gypsum', ['day' => -7, 'lo' => -10, 'hi' => -3, 'title' => 'Spread the gypsum before planting', 'stage' => 'Land preparation', 'type' => 'fertilizer',
                    'how' => 'Spread it before the last harrowing.', 'why' => 'Its calcium pushes sodium out and keeps a crusting soil open.'], $l, 1);
            } elseif ($cat === 'organic' && ! in_array($unit, ['l', 'ml'], true)) {
                $put('organic', ['day' => -14, 'lo' => -21, 'hi' => -7, 'title' => 'Work in the manure or compost', 'stage' => 'Land preparation', 'type' => 'fertilizer',
                    'how' => 'Spread it and plow it in, two weeks or more before planting, so it breaks down first.', 'why' => 'It feeds the soil life and holds water and nutrients for the crop.'], $l, 1);
            } elseif ($cat === 'bio' || in_array($unit, ['dose', 'ha'], true) || preg_match('/inoculant|rhizobium|azospir|azotobac|mycorrh|trichoderma|bacill/i', $name)) {
                // On the seed at sowing: for a transplanted crop, the seedbed's.
                $put('bio', ['day' => $dat ? -21 : 0, 'lo' => $dat ? -22 : -1, 'hi' => $dat ? -21 : 0, 'title' => 'Treat the seed with the inoculant', 'stage' => $dat ? 'Seedbed sowing' : 'At sowing', 'type' => 'seed_treatment',
                    'how' => 'Mix it with the seed in the shade, just before sowing, as its label says.', 'why' => 'The live microbes help the roots take up nitrogen and phosphorus.'], $l, 1);
            } elseif ($cat === 'foliar' || in_array($unit, ['l', 'ml'], true) || preg_match('/foliar|water soluble|emulsion|liquid/i', $name)) {
                $put('foliar', ['day' => $peak[0], 'lo' => $peak[0] - 3, 'hi' => $peak[0] + 4, 'title' => 'Foliar feeding', 'stage' => $peak[1], 'type' => 'foliar_spray',
                    'how' => 'Spray it on the leaves early in the morning or late in the afternoon, at the rate on its label.', 'why' => 'The leaves take it up fast when the crop grows hardest.'], $l, 1);
            } elseif ($nOnly && ! $perennial) {
                foreach ($nDays as $k => $d) {
                    $put('n' . ($k + 1), ['day' => $d, 'lo' => $d - 4, 'hi' => $d + 4, 'title' => self::ordinal($k + 1) . ' side dressing of nitrogen', 'stage' => $nStages[$k], 'type' => 'fertilizer',
                        'how' => $key === 'rice' || str_starts_with($key, 'rice_') ? 'Broadcast it into shallow water, 2 to 3 cm, and keep the water in for a few days.' : 'Put it along the rows a hand away from the plants and cover it with soil, or hill up right after. Water it in if the soil is dry.',
                        'why' => 'Nitrogen is taken up fast and lost fast, so it goes on in doses, each when the crop is about to grow hardest.'], $l, 1 / count($nDays));
                }
            } elseif ($nOnly && $perennial) {
                // Trees: half with the rest at the start, half three months on.
                $put('basal', ['day' => 0, 'title' => 'Fertilizer at the start of the program', 'stage' => 'Program start', 'type' => 'fertilizer',
                    'how' => 'Spread it in a ring under the edge of the canopy and cover it lightly.', 'why' => 'The roots that feed are under the edge of the canopy.'], $l, 0.5);
                $put('n2', ['day' => 90, 'lo' => 80, 'hi' => 100, 'title' => 'Second dose of nitrogen', 'stage' => 'Three months on', 'type' => 'fertilizer',
                    'how' => 'Spread it in a ring under the edge of the canopy and cover it lightly.', 'why' => 'Split doses feed the trees longer and lose less.'], $l, 0.5);
            } elseif ($texture === 'sandy' && ($pct['K2O'] ?? 0) > 0 && ($pct['P2O5'] ?? 0) <= 0 && ($pct['N'] ?? 0) <= 0 && ! $perennial && $nDays) {
                // Potash on sand washes down: half at planting, half with the first side dressing.
                $put('basal', $basal, $l, 0.5);
                $put('n1', ['day' => $nDays[0], 'lo' => $nDays[0] - 4, 'hi' => $nDays[0] + 4, 'title' => 'First side dressing of nitrogen', 'stage' => $nStages[0], 'type' => 'fertilizer',
                    'how' => 'Put it along the rows a hand away from the plants and cover it with soil.', 'why' => 'On sandy soil the potash washes down too, so half of it goes now.'], $l, 0.5);
            } else {
                $put('basal', $perennial ? ['day' => 0, 'title' => 'Fertilizer at the start of the program', 'stage' => 'Program start', 'type' => 'fertilizer',
                    'how' => 'Spread it in a ring under the edge of the canopy and cover it lightly.', 'why' => 'The roots that feed are under the edge of the canopy.'] : $basal, $l, 1);
            }
        }

        // The planting itself (the board's day zero), with the seed when the calculator counted it.
        $items = [];
        $seed = (array) ($set['seed'] ?? []);
        $seedMat = [];
        if (! $perennial && (float) ($seed['amount'] ?? 0) > 0) {
            $isKg = in_array(strtolower((string) ($seed['unit'] ?? '')), ['kg', 'g'], true);
            $qty = $isKg ? (float) $seed['amount'] * (strtolower((string) $seed['unit']) === 'g' ? 0.001 : 1) : round((float) $seed['amount']);
            $seedMat[] = ['name' => 'Seed' . (trim((string) ($set['variety'] ?? '')) !== '' ? ': ' . trim((string) $set['variety']) : ' of ' . $label), 'kind' => 'seed', 'qty' => $qty, 'unit' => $isKg ? 'kg' : 'piece', 'note' => 'For ' . PlanMaterials::areaWords($ha) . '.'];
        }
        if (! $perennial) {
            if ($dat) {
                $items[] = self::item('seedbed', 'Sow the seedbed', 'planting', 'high', $at(-21), null, null, '<p>Sow the seedbed for ' . self::e($lotName) . ', about 21 days before transplanting.</p>', 0, true, true, false, -21, $seedMat);
                $seedMat = [];
            }
            $items[] = self::item('plant', $dat ? 'Transplant' : 'Planting', 'planting', 'high', $planting, null, null, '<p>' . ($dat ? 'Transplanting' : 'Planting') . ' day for ' . self::e($lotName) . '. Every fertilizer day of this plan counts from it.</p>', 0, true, ! $dat, $dat, 0, $seedMat);
        }
        foreach ($apps as $k => $a) {
            $mats = [];
            $li = [];
            foreach ($a['lines'] as [$l, $share]) {
                $kg = (float) ($l['kg'] ?? 0) * $share;
                $unit = strtolower((string) ($l['unit'] ?? 'kg'));
                $amount = (float) ($l['amount'] ?? 0) * $share;
                $name = trim((string) ($l['name'] ?? 'Fertilizer')) . (trim((string) ($l['grade'] ?? '')) !== '' && ! str_contains((string) $l['name'], (string) $l['grade']) ? ' ' . trim((string) $l['grade']) : '');
                $say = $unit === 'bag' ? self::n($amount) . ($amount == 1 ? ' bag' : ' bags') . ($kg > 0 ? ' (' . self::n($kg) . ' kg)' : '')
                    : ($kg > 0 ? self::n($kg) . ' kg' : self::n($amount) . ' ' . ($unit === 'dose' ? ($amount == 1 ? 'dose' : 'doses') : $unit));
                $li[] = '<li>' . self::e($say . ' ' . $name) . '</li>';
                $mats[] = ['name' => $name, 'kind' => $a['type'] === 'foliar_spray' ? 'foliar' : ($a['type'] === 'seed_treatment' ? 'bio' : (preg_match('/manure|compost|vermi|organic|chicken|bokashi/i', $name) ? 'bio' : 'fertilizer')),
                    'qty' => $kg > 0 ? round($kg, 3) : null, 'unit' => 'kg', 'note' => $kg > 0 ? '' : self::n($amount) . ' ' . $unit];
            }
            $html = '<p><strong>What to apply</strong> on ' . self::e($lotName) . ' (' . self::e(PlanMaterials::areaWords($ha)) . '):</p><ul>' . implode('', $li) . '</ul>'
                . '<p><strong>How:</strong> ' . self::e($a['how']) . '</p><p><strong>Why:</strong> ' . self::e($a['why']) . '</p>'
                . '<p><strong>Stage:</strong> ' . self::e($a['stage']) . '</p><p><em>From NPK Plus: ' . self::e($r->title) . '.</em></p>';
            $d = (int) $a['day'];
            $items[] = self::item('np_' . $k, $a['title'], $a['type'], $k === 'basal' ? 'high' : 'medium', $at($d), isset($a['lo']) ? $at((int) $a['lo']) : null, isset($a['hi']) ? $at((int) $a['hi']) : null,
                $html, $hands, false, false, false, $d, $mats);
        }
        usort($items, fn ($a, $b) => strcmp($a['date'], $b['date']) ?: ($a['anchor'] ? -1 : ($b['anchor'] ? 1 : 0)));

        return [
            'source' => 'npk', 'id' => (int) $r->id, 'title' => (string) $r->title, 'phase' => 'season', 'note' => null, 'legacy' => false,
            'cropType' => $key !== '' ? $label : null, 'variety' => trim((string) ($set['variety'] ?? '')),
            'lots' => [[
                'i' => 0, 'name' => mb_substr($lotName, 0, 255), 'size' => $ha, 'unit' => 'hectare', 'crop' => $key, 'cropLabel' => $key !== '' ? $label : '',
                'variety' => trim((string) ($set['variety'] ?? '')), 'dayType' => $dat ? 'DAT' : ($counter === 'DAS' ? 'DAS' : 'DAP'),
                'planting' => $planting, 'dayZero' => $dayZero, 'transplant' => $dat ? $planting : null,
                'loc' => ['town' => (string) ($set['town'] ?? ''), 'province' => (string) ($set['prov'] ?? '')],
                'items' => $items, 'note' => null, 'ha' => $ha, 'recs' => '',
            ]],
            'recs' => self::recs($r, $res, $label, $lotName, $ha, $nStages, $splits, $perennial),
            'disclaimer' => 'A guide, not a promise: the days follow the crop\'s usual stages. Move them to what you see in the field, and follow each product\'s label.',
        ];
    }

    private static function item(string $key, string $title, string $type, string $prio, string $date, ?string $lo, ?string $hi, string $html, int $hands, bool $anchor, bool $zero, bool $tp, int $offset, array $mats): array
    {
        return ['key' => str_starts_with($key, 'np_') ? $key : 'np_' . $key, 'title' => mb_substr($title, 0, 255), 'html' => $html, 'type' => $type, 'priority' => $prio,
            'date' => $date, 'end' => $date, 'lo' => $lo, 'hi' => $hi, 'hands' => $hands, 'hired' => false,
            'anchor' => $anchor, 'zero' => $zero, 'transplant' => $tp, 'offset' => $offset, 'materials' => $mats];
    }

    /** The planting day: the calculator's, else the start of its season window, else a week from today. */
    private static function plantingDay(array $set, array $res): string
    {
        foreach ([$set['planted'] ?? null, $res['season']['window']['from'] ?? null] as $d) {
            if (is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                return $d;
            }
        }

        return now('Asia/Manila')->addDays(7)->toDateString();
    }

    /** Each product's shelf (nitrogen, phosphate, foliar, bio...) by its id. */
    private static function categories(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        return $ids ? DB::table('as_npk_products')->whereIn('id', $ids)->pluck('category', 'id')->map(fn ($c) => (string) $c)->all() : [];
    }

    /**
     * The side dressing days and their stages: the middle of the first growing
     * stage, the start of the last one before the first flower, and on a third
     * dose the first flower. A crop with no stages: days 21, 42 and 63.
     */
    private static function nDays(array $rows, int $splits, bool $perennial): array
    {
        if ($perennial) {
            return [[], []];
        }
        $repro = '/flower|bloom|tassel|silk|heading|fruit|pod\b|grain|fill|bulb|bulking|root initiation|shooting|bunch|bearing/i';
        if (count($rows) < 3) {
            $days = array_slice([21, 42, 63], 0, max(1, $splits));

            return [$days, array_map(fn ($d) => 'About day ' . $d, $days)];
        }
        $first = null;
        foreach ($rows as $k => $r) {
            if ($k > 0 && preg_match($repro, (string) $r[1])) {
                $first = $k;
                break;
            }
        }
        $first ??= count($rows) - 1;
        $one = $rows[1];
        $oneEnd = (int) (($rows[2][0] ?? ($one[0] + 14)) - 1);
        $d1 = (int) round(($one[0] + $oneEnd) / 2);
        // The second a third of the way into the peak stage (corn about day 35, rice at panicle initiation).
        $pk = max(1, $first - 1);
        $peakRow = $rows[$pk];
        $peakEnd = (int) (($rows[$pk + 1][0] ?? ($peakRow[0] + 15)) - 1);
        $p2 = (int) $peakRow[0] + (int) round(($peakEnd - (int) $peakRow[0]) / 3);
        $d2 = $p2 > $d1 + 7 ? $p2 : (int) $rows[$first][0];
        $s2 = $p2 > $d1 + 7 ? (string) $peakRow[1] : (string) $rows[$first][1];
        $days = [$d1, $d2];
        $stages = [(string) $one[1], $s2];
        if ($splits >= 3) {
            $d3 = (int) $rows[$first][0];
            if ($d3 <= $d2) {
                $d3 = $d2 + 14;
            }
            $days[] = $d3;
            $stages[] = (string) $rows[$first][1];
        }

        return [$days, $stages];
    }

    /** The day and stage of the crop's peak of growth: the start of the last growing stage before the first flower. */
    private static function peak(array $rows): array
    {
        if (count($rows) < 2) {
            return [30, 'About day 30'];
        }
        $repro = '/flower|bloom|tassel|silk|heading|fruit|pod\b|grain|fill|bulb|bulking|root initiation|shooting|bunch|bearing/i';
        $at = $rows[1];
        foreach ($rows as $k => $r) {
            if ($k > 0 && preg_match($repro, (string) $r[1])) {
                break;
            }
            if ($k > 0) {
                $at = $r;
            }
        }

        return [(int) $at[0], (string) $at[1]];
    }

    private static function ordinal(int $n): string
    {
        return ['First', 'Second', 'Third', 'Fourth'][$n - 1] ?? 'Next';
    }

    /** The plan's notes: the need it meets, the soil, the bags in all, how the days were set, and Anee's reading. */
    private static function recs(object $r, array $res, string $label, string $lotName, float $ha, array $nStages, int $splits, bool $perennial): string
    {
        $set = (array) ($res['setup'] ?? []);
        $h = '<p><em>From NPK Plus: ' . self::e($r->title) . '.</em></p>';
        $goal = (float) ($res['goal']['tPerHa'] ?? $r->targetYield ?? 0);
        $h .= '<p><strong>The plan</strong></p><p>' . self::e(ucfirst($label) . (trim((string) ($set['variety'] ?? '')) !== '' ? ', ' . trim((string) $set['variety']) : '') . ' on ' . $lotName . ', ' . PlanMaterials::areaWords($ha)
            . ($goal > 0 ? ', for ' . self::n($goal) . ' tons a hectare' : '') . '. It gives ' . ($res['npkPerHa'] ?? '') . ' (N, P₂O₅, K₂O) a hectare.') . '</p>';
        $state = ['short' => 'short of', 'right' => 'meets', 'over' => 'more than'];
        $needs = array_filter((array) ($res['needs'] ?? []), 'is_array');
        if ($needs) {
            $h .= '<ul>' . implode('', array_map(fn ($el, $n) => '<li>' . self::e(str_replace(['P2O5', 'K2O'], ['P₂O₅', 'K₂O'], $el) . ': ' . self::n((float) ($n['plan'] ?? 0)) . ' kg a hectare, ' . ($state[$n['state'] ?? ''] ?? 'against') . ' the ' . self::n((float) ($n['need'] ?? 0)) . ' kg it needs') . '</li>', array_keys($needs), $needs)) . '</ul>';
        }
        $short = array_keys(array_filter((array) ($res['micros'] ?? []), fn ($m) => is_array($m) && empty($m['beneficial']) && ($m['state'] ?? '') === 'short'));
        if ($short) {
            $h .= '<p><strong>Still short</strong></p><p>' . self::e(implode(', ', $short)) . ': add a product that carries ' . (count($short) === 1 ? 'it' : 'them') . ', or a foliar spray.</p>';
        }
        $lines = array_values(array_filter((array) ($res['lines'] ?? []), 'is_array'));
        if ($lines) {
            $unitSay = fn (float $n, string $u) => in_array($u, ['bag', 'dose', 'pack'], true) ? ($n == 1 ? $u : $u . 's') : $u;
            $h .= '<p><strong>The fertilizers in all</strong></p><ul>' . implode('', array_map(fn ($l) => '<li>' . self::e(self::n((float) ($l['amount'] ?? 0)) . ' ' . $unitSay((float) ($l['amount'] ?? 0), (string) ($l['unit'] ?? '')) . ' ' . ($l['name'] ?? '') . ((float) ($l['kg'] ?? 0) > 0 && ($l['unit'] ?? '') === 'bag' ? ' (' . self::n((float) $l['kg']) . ' kg)' : '')) . '</li>', $lines)) . '</ul>';
        }
        $soil = array_filter([
            ! empty($set['ph']) ? 'pH ' . $set['ph'] : null,
            ! empty($set['texture']) && $set['texture'] !== 'unsure' ? $set['texture'] . ' soil' : null,
            ! empty($set['conditions']) ? implode(', ', array_map(fn ($c) => ['low_om' => 'low organic matter', 'acid_sulfate' => 'acid sulfate'][(string) $c] ?? str_replace('_', ' ', (string) $c), (array) $set['conditions'])) : null,
        ]);
        if ($soil) {
            $words = implode('; ', $soil);
            $h .= '<p><strong>The soil</strong></p><p>' . self::e(str_starts_with($words, 'pH') ? $words : ucfirst($words)) . '.</p>';
        }
        $h .= '<p><strong>How the days were set</strong></p><p>' . self::e($perennial
            ? 'The bags go on at the start of the program, and the nitrogen in two doses, the second three months on.'
            : 'Lime, sulfur and manure go in at land preparation. The bags with phosphorus or potassium, and the soil micronutrients, go on as the basal at planting. The nitrogen alone is split into ' . count($nStages) . ' side dressings, at ' . implode(', then ', array_map('mb_strtolower', $nStages)) . ($splits >= 3 ? ', since sandy soil loses nitrogen fast' : '') . '. Move them to what you see in the field.') . '</p>';
        // Anee's reading, when the calculation has one.
        $an = ! empty($r->analysisId) ? DB::table('as_plant_analyses')->where('id', (int) $r->analysisId)->where('status', 'ready')->value('report') : null;
        $a = (array) ((json_decode((string) $an, true) ?: [])['anee'] ?? []);
        if ($a) {
            $h .= '<p><strong>Anee\'s reading</strong></p>' . (! empty($a['headline']) ? '<p>' . self::e($a['headline']) . '</p>' : '') . (! empty($a['summary']) ? '<p>' . self::e($a['summary']) . '</p>' : '');
            if (! empty($a['timing'])) {
                $h .= '<p><strong>Anee\'s timing</strong></p><ul>' . implode('', array_map(fn ($t) => '<li>' . self::e(trim(($t['when'] ?? '') . ': ' . ($t['product'] ?? '') . '. ' . ($t['how'] ?? ''))) . '</li>', array_filter((array) $a['timing'], 'is_array'))) . '</ul>';
            }
            if (! empty($a['changes'])) {
                $h .= '<p><strong>Anee would change</strong></p><ul>' . implode('', array_map(fn ($c) => '<li>' . self::e(trim(($c['change'] ?? '') . ' ' . ($c['why'] ?? ''))) . '</li>', array_filter((array) $a['changes'], 'is_array'))) . '</ul>';
            }
            if (! empty($a['warnings'])) {
                $h .= '<p><strong>Be careful</strong></p><ul>' . implode('', array_map(fn ($w) => '<li>' . self::e($w) . '</li>', (array) $a['warnings'])) . '</ul>';
            }
        }

        return GovSource::scrubAll($h);
    }
}
