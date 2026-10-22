<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * A Crop Protocol lot's watering and protection schedules, on its own days
 * (2026-10-23, the owner: "in the output of the crop protocol analysis,
 * irrigation/watering schedule should be included. and thus will be ported
 * as well when asked to port. also the recommendation schedules to spray
 * protection should be included as well and ported").
 *
 * Both are worked out from what the report already holds, on read, so every
 * saved report has them and no new paid ask is needed:
 *   - watering: the crop's watering schedule from How to Plant (our own,
 *     per crop and way in), stretched to the lot's days to harvest, each
 *     stage dated from the lot's planting, with the season's advice
 *     (rain only, a dry season, a wet one) and the stages that must never
 *     go dry;
 *   - protection: the threats the report found for the crop, gathered by the
 *     growth stage they come in, each stage dated: what to look for, when to
 *     act, what to spray if it is passed (the ingredients and products the
 *     report already chose), and what to do first without a spray.
 * The protection schedule is a calendar to check by, not to spray by: the
 * report's disclaimer goes with it everywhere.
 */
final class ProtocolSchedules
{
    /** Every lot of a format 4 report gets `watering` and `protect`. */
    public static function fill($report, ?array $params = null)
    {
        if (! is_array($report) || (int) ($report['format'] ?? 0) < 4 || empty($report['lots'])) {
            return $report;
        }
        $crops = [];
        foreach ((array) ($report['crops'] ?? []) as $c) {
            if (is_array($c)) {
                $crops[(string) ($c['key'] ?? '')] = $c;
            }
        }
        foreach ($report['lots'] as $i => $L) {
            if (! is_array($L)) {
                continue;
            }
            try {
                $crop = $crops[(string) ($L['case'] ?? '')] ?? null;
                $report['lots'][$i]['watering'] = self::watering($L, self::waterOf($L, $params, (int) $i));
                $report['lots'][$i]['protect'] = $crop ? self::protect($L, $crop) : [];
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $report;
    }

    /** The lot's planting day (day 0 of its count), or null. */
    private static function day0(array $L): ?string
    {
        $p = (string) (($L['planting']['planting'] ?? null) ?: '');
        if ($p === '') {
            foreach ((array) ($L['tasks'] ?? []) as $t) {
                if (($t['kind'] ?? '') === 'plant' && ! empty($t['from'])) {
                    $p = (string) $t['from'];
                }
            }
        }

        return $p !== '' ? $p : null;
    }

    private static function at(string $day0, int $d): string
    {
        return Carbon::parse($day0)->addDays($d)->toDateString();
    }

    /** Where the lot's water comes from: rainfed, or what it is irrigated by. */
    private static function waterOf(array $L, ?array $params, int $i): string
    {
        $w = is_string($L['need']['season']['water'] ?? null) ? $L['need']['season']['water'] : '';
        if ($w === '' && $params) {
            $lp = $params['lots'][$i] ?? null;
            $v = is_array($lp) ? ($lp['water'] ?? ($lp['profile']['water'] ?? '')) : '';
            // The field answers keep a pick as a list ("water": ["pump"]).
            $w = (string) (is_array($v) ? (reset($v) ?: '') : $v);
        }
        if ($w === '') {
            foreach ((array) ($L['field'] ?? []) as $f) {
                if (is_array($f) && ($f[0] ?? '') === 'Water') {
                    $w = preg_match('/rain/i', (string) ($f[1] ?? '')) ? 'rainfed' : 'irrigated';
                }
            }
        }

        return $w === '' ? 'irrigated' : $w;
    }

    /* ============================================================ watering */

    /**
     * The lot's watering schedule, or null when the crop has none written:
     * {method, season: [kind, words], rows: [{key, stage, from, to, date, end,
     * often, amount, why, critical, nursery, prep, task}], critical, signs}.
     */
    public static function watering(array $L, string $water = 'irrigated'): ?array
    {
        $day0 = self::day0($L);
        $guide = $day0 ? HowToPlant::guide((string) ($L['crop'] ?? '')) : null;
        if (! $guide) {
            return null;
        }
        $way = HowToPlant::way($guide, HowToPlant::wayKey($guide, (string) ($L['crop'] ?? ''), (string) ($L['method'] ?? '')));
        $w = HowToPlant::watering($guide, $way);
        if (! $w || empty($w['schedule'])) {
            return null;
        }
        // The guide's days are for its usual variety: the field's stretch to the lot's own days to harvest.
        $wayDays = (int) ($way['days'] ?? 0);
        $lotDays = (int) ($L['harvestDays'] ?? 0);
        $f = $wayDays > 0 && $lotDays > 0 ? $lotDays / $wayDays : 1.0;
        $f = $f >= 0.6 && $f <= 1.6 ? $f : 1.0;
        $scale = fn (int $d) => $d > 0 ? (int) round($d * $f) : $d;
        $rows = [];
        foreach (array_values((array) $w['schedule']) as $k => $s) {
            if (! is_array($s) || trim((string) ($s['stage'] ?? '')) === '') {
                continue;
            }
            $from = $scale((int) ($s['from'] ?? 0));
            $to = max($from, $scale((int) ($s['to'] ?? $from)));
            $name = trim((string) $s['stage']);
            $rows[] = [
                'key' => 'water' . $k, 'stage' => $name, 'from' => $from, 'to' => $to,
                'date' => self::at($day0, $from), 'end' => self::at($day0, $to),
                'often' => trim((string) ($s['often'] ?? '')), 'amount' => trim((string) ($s['amount'] ?? '')), 'why' => trim((string) ($s['why'] ?? '')),
                'critical' => HowToPlant::critical($w, $s),
                'nursery' => (bool) preg_match(HowToPlant::NURSERY_STAGE, $name) && $to <= 0,
                'prep' => ! preg_match(HowToPlant::NURSERY_STAGE, $name) && $to <= 0 && (bool) preg_match(HowToPlant::PREP_STAGE, $name),
                'task' => self::waterTask((string) ($s['often'] ?? ''), (string) ($s['amount'] ?? '')),
            ];
        }
        if (! $rows) {
            return null;
        }
        return [
            'method' => trim((string) ($w['method'] ?? '')),
            'season' => self::seasonTip($L, $w, $water, $day0),
            'rows' => $rows,
            'critical' => array_values(array_filter(array_map('strval', (array) ($w['critical'] ?? [])))),
            'signs' => array_filter(['dry' => trim((string) ($w['signs']['dry'] ?? '')), 'wet' => trim((string) ($w['signs']['wet'] ?? ''))]),
        ];
    }

    /** The board's water task a stage reads as (AsScheduleActivity::WATER_TASKS), from its "how often" and "how much". */
    private static function waterTask(string $often, string $amount): string
    {
        $o = mb_strtolower(trim($often));
        $a = mb_strtolower(trim($amount));

        return match (true) {
            (bool) preg_match('/open the dike|^drain\b|drain the field|^let the water (?:out|drain)/', $o) || (bool) preg_match('/^no standing water\b.*\bdrain\b/', $a) => 'drain',
            (bool) preg_match('/^stop\b|^usually no watering|^no (?:watering|irrigation)/', $o) || (bool) preg_match('/^no water\b/', $a) => 'no_irrigation',
            (bool) preg_match('/flooded|keep (?:the )?water|water standing|top it up|thin layer|cm of standing water/', $o . ' ' . $a) => 'maintain',
            default => 'irrigate',
        };
    }

    /**
     * The season's advice: rain only, a dry season or a wet one. The lot's
     * own season reading (twenty years of rain and the ENSO outlook) when the
     * report has one; else the month (the dry months are December to May).
     */
    private static function seasonTip(array $L, array $w, string $water, string $day0): array
    {
        $sn = (array) ($L['need']['season'] ?? []);
        $rain = (float) ($sn['rainMm'] ?? 0);
        $need = (float) ($sn['needMm'] ?? 0);
        if ($need > 0) {
            $dry = ! empty($sn['dry']) || $rain / $need < 0.5;
            $wet = ! empty($sn['heavy']) || $rain / $need >= 1.0;
        } else {
            $m = (int) Carbon::parse($day0)->month;
            $dry = in_array($m, [12, 1, 2, 3, 4, 5], true);
            $wet = in_array($m, [6, 7, 8, 9, 10], true);
        }
        $rainfed = in_array($water, ['rainfed', 'rain'], true);
        $kind = $rainfed ? 'rainfed' : ($wet && ! $dry ? 'wet' : 'dryseason');
        $say = trim((string) ($w[$kind] ?? ''));
        $title = ['rainfed' => 'On the rain only', 'dryseason' => 'In the dry months', 'wet' => 'In the rainy months'][$kind];
        // A rainfed lot in a wet season still needs its drains; an irrigated one in the dry months, its water ready.
        $also = $rainfed && $wet ? trim((string) ($w['wet'] ?? '')) : '';

        return ['kind' => $kind, 'title' => $title, 'say' => $say, 'also' => $also,
            'words' => trim((string) ($sn['words'] ?? ''))];
    }

    /* ============================================================ protection */

    /**
     * The lot's protection schedule: the crop's threats (insects, diseases,
     * rats and the like; weeds have their own jobs) by the stage they come in,
     * each stage dated. [{key, stage, from, to, date, end, kind, threats:
     * [{name, kind, sign, threshold, cultural, sprays: [{name, group, sold}], noSpray, rotate}]}]
     */
    public static function protect(array $L, array $crop): array
    {
        $day0 = self::day0($L);
        $threats = array_values(array_filter((array) ($crop['protection']['threats'] ?? []), fn ($t) => is_array($t) && ($t['kind'] ?? '') !== 'weed'));
        if (! $day0 || ! $threats) {
            return [];
        }
        $harvest = (int) ($L['harvestDays'] ?? 0);
        $byStage = [];
        foreach ($threats as $t) {
            foreach ((array) ($t['stages'] ?? []) as $s) {
                if (! is_array($s) || ! isset($s['from'])) {
                    continue;
                }
                $from = (int) $s['from'];
                $to = max($from, (int) ($s['to'] ?? $from));
                if ($harvest > 0 && $from > $harvest) {
                    continue;
                }
                $k = $from . ':' . $to . ':' . ($s['name'] ?? '');
                $byStage[$k] ??= ['stage' => (string) ($s['name'] ?? ''), 'from' => $from, 'to' => $harvest > 0 ? min($to, $harvest) : $to, 'threats' => []];
                $byStage[$k]['threats'][] = self::threat($t);
            }
        }
        uasort($byStage, fn ($a, $b) => [$a['from'], $a['to']] <=> [$b['from'], $b['to']]);
        $rows = [];
        foreach (array_values($byStage) as $n => $s) {
            $kinds = array_column($s['threats'], 'kind');
            $rows[] = $s + [
                'key' => 'protect' . $n,
                'date' => self::at($day0, $s['from']), 'end' => self::at($day0, $s['to']),
                // The board's task type: insects first (the commonest spray), a disease stage a fungicide, rats and others a check.
                'type' => in_array('insect', $kinds, true) ? 'pesticide' : (in_array('disease', $kinds, true) ? 'fungicide' : 'monitoring'),
            ];
        }

        return $rows;
    }

    private static function threat(array $t): array
    {
        $sprays = [];
        foreach (array_slice((array) ($t['ai'] ?? []), 0, 3) as $a) {
            if (! is_array($a) || trim((string) ($a['name'] ?? '')) === '') {
                continue;
            }
            $sprays[] = ['name' => (string) $a['name'], 'group' => (string) ($a['group'] ?? ''),
                'sold' => array_values(array_slice(array_filter(array_map(fn ($p) => is_array($p) ? trim((string) ($p['brand'] ?? '')) : '', (array) ($a['products'] ?? []))), 0, 3))];
        }
        if (! $sprays) {
            foreach (array_slice((array) ($t['byLabel'] ?? []), 0, 3) as $p) {
                if (is_array($p) && trim((string) ($p['ai'] ?? $p['brand'] ?? '')) !== '') {
                    $sprays[] = ['name' => (string) ($p['ai'] ?: $p['brand']), 'group' => (string) ($p['group'] ?? ''), 'sold' => array_filter([(string) ($p['brand'] ?? '')])];
                }
            }
        }

        return [
            'name' => (string) ($t['name'] ?? ''), 'kind' => (string) ($t['kind'] ?? 'other'),
            // "Act when: act when you see ..." said once.
            'sign' => trim((string) ($t['sign'] ?? '')), 'threshold' => ucfirst(trim((string) preg_replace('/^act (?:when|if)\s*:?\s*/i', '', trim((string) ($t['threshold'] ?? ''))))),
            'cultural' => trim((string) ($t['cultural'] ?? '')), 'sprays' => $sprays,
            'noSpray' => trim((string) ($t['noSpray'] ?? '')), 'rotate' => trim((string) ($t['rotate'] ?? '')),
        ];
    }

    /* ============================================================ words for the board */

    private static function e($s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }

    private static function dayWord(string $d): string
    {
        return Carbon::parse($d)->format('M j');
    }

    /** One watering stage as a board activity's words. */
    public static function waterHtml(array $row, array $watering, bool $first): string
    {
        $span = $row['date'] === $row['end'] ? self::dayWord($row['date']) : self::dayWord($row['date']) . ' to ' . self::dayWord($row['end']);
        $sn = (array) ($watering['season'] ?? []);

        return '<p><strong>Watering, ' . self::e($row['stage']) . ':</strong> ' . self::e($span) . '.</p>'
            . ($row['often'] !== '' ? '<p><strong>How often:</strong> ' . self::e($row['often']) . '.</p>' : '')
            . ($row['amount'] !== '' ? '<p><strong>How much:</strong> ' . self::e($row['amount']) . '.</p>' : '')
            . ($row['why'] !== '' ? '<p><strong>Why:</strong> ' . self::e($row['why']) . '</p>' : '')
            . ($row['critical'] ? '<p><strong>This stage must never go dry.</strong></p>' : '')
            . ($first && ($sn['say'] ?? '') !== '' ? '<p><strong>' . self::e($sn['title'] ?? 'This season') . ':</strong> ' . self::e($sn['say']) . '</p>' : '')
            . ($first && ($watering['method'] ?? '') !== '' ? '<p><strong>The usual way:</strong> ' . self::e($watering['method']) . '</p>' : '')
            . '<p><em>From the Crop Protocol Analysis.</em></p>';
    }

    /** One protection stage as a board activity's words, with the disclaimer. */
    public static function protectHtml(array $row, string $intro, string $disclaimer): string
    {
        $span = $row['date'] === $row['end'] ? self::dayWord($row['date']) : self::dayWord($row['date']) . ' to ' . self::dayWord($row['end']);
        $h = '<p><strong>Check the crop, ' . self::e($row['stage']) . ':</strong> ' . self::e($span) . '. ' . self::e($intro !== '' ? $intro : 'Walk the field twice a week and look under the leaves.') . '</p><ul>';
        foreach ($row['threats'] as $t) {
            $h .= '<li><strong>' . self::e($t['name']) . '.</strong>'
                . ($t['sign'] !== '' ? ' Look for: ' . self::e($t['sign']) : '')
                . ($t['threshold'] !== '' ? ' <strong>Act when:</strong> ' . self::e($t['threshold']) : '')
                . ($t['cultural'] !== '' ? ' First, without a spray: ' . self::e($t['cultural']) : '')
                . ($t['sprays'] ? ' <strong>If you must spray:</strong> ' . self::e(implode('; ', array_map(fn ($s) => $s['name'] . ($s['group'] !== '' ? ' (' . $s['group'] . ')' : '') . ($s['sold'] ? ', sold as ' . implode(', ', $s['sold']) : ''), $t['sprays']))) . '.' : '')
                . ($t['noSpray'] !== '' ? ' ' . self::e($t['noSpray']) : '')
                . '</li>';
        }

        return $h . '</ul>' . ($disclaimer !== '' ? '<p><em>' . self::e($disclaimer) . '</em></p>' : '') . '<p><em>From the Crop Protocol Analysis.</em></p>';
    }

    /** The board's title of a protection stage: what to check for. */
    public static function protectTitle(array $row): string
    {
        $names = array_values(array_unique(array_column($row['threats'], 'name')));
        $list = count($names) > 3 ? implode(', ', array_slice($names, 0, 3)) . ' and more' : (count($names) > 1 ? implode(', ', array_slice($names, 0, -1)) . ' and ' . end($names) : ($names[0] ?? 'pests'));

        return 'Check and protect: ' . $list;
    }
}
