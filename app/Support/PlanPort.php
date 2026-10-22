<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleActivityVersion;
use App\Models\AsScheduleDateNote;
use App\Models\AsScheduleLot;
use App\Models\AsScheduleNote;
use App\Models\AsScheduleWorker;
use App\Support\LandPrepPlanner as LP;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A saved plan, put into a cropping schedule (2026-10-22, the owner: "crop
 * protocol analysis, land prep analysis, you have an option to port it to the
 * protocol builder or cropping schedule").
 *
 * Both planners save a report; this reads either one as the same thing, LOTS
 * OF DATED WORK (plan()), and writes that work onto a season's board: a new
 * season made out of it (newSeason()), or a season the farmer already has
 * (intoSeason()), each plan lot onto a lot of that season that is not
 * harvested yet, or onto a new lot there.
 *
 * Into a season that already has work, the plan is first moved onto the
 * lot's own planting day (shifted()); work whose day has passed is left out,
 * and a plan whose part of the season is already over cannot go in at all
 * (over()). What stays is placed by the plan's days, by the crew's free days
 * (crewPlace(): a task keeps its day while a worker is free that day, else
 * the next day one is), or by Anee's dates (PlanPortController's check).
 *
 * An item is: key, title, html, type, priority, date (its first day), end,
 * lo and hi (the days it may move between), hands (workers a day; 0 for
 * hired work), anchor (the planting itself: never moved), zero and
 * transplant (the board's day zero and transplant marks), offset (days from
 * the planting), materials (what it uses, for the plan's lot: PlanMaterials).
 * A lot also carries `ha` and `recs` (its recommendations as HTML), and the
 * plan its own `recs` (2026-10-23, the owner: the materials needed and the
 * recommendations and notes go with the plan).
 */
class PlanPort
{
    public const SOURCES = [
        'landprep' => ['label' => 'Land Prep Planner', 'phase' => 'prep', 'work' => 'land preparation'],
        'protocol' => ['label' => 'Crop Protocol Analysis', 'phase' => 'season', 'work' => 'season'],
    ];

    /** The member's saved, finished plan of this kind, or null. */
    public static function row(string $source, int $id, int $userId): ?object
    {
        if (! isset(self::SOURCES[$source])) {
            return null;
        }

        return DB::table('as_plant_analyses')->where('userId', $userId)->where('id', $id)->where('kind', $source)
            ->where('deleteStatus', 1)->where('status', 'ready')->first();
    }

    /** The plan as lots of dated work. */
    public static function plan(string $source, object $row): array
    {
        return $source === 'landprep' ? self::fromLandPrep($row) : self::fromProtocol($row);
    }

    /* ============================================================ readers */

    private static function e($s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }

    private static function dayWord(?string $d): string
    {
        return $d ? Carbon::parse($d)->format('D, M j') : '';
    }

    private static function cropKey(string $crop): string
    {
        return CropStages::normalize($crop) ?? CropStages::normalize(CropCatalog::label($crop)) ?? $crop;
    }

    /**
     * The Land Prep Planner's plan: the steps of every lot on the day the
     * crew starts each one, the seed soak and incubation to the hour, the
     * bookings of hired machines, and Anee's reading. The same work and the
     * same words the planner's own "Make a cropping schedule" always wrote.
     */
    private static function fromLandPrep(object $r): array
    {
        $report = LP::plainReport(json_decode((string) $r->report, true) ?: []);
        $params = json_decode((string) $r->params, true) ?: [];
        $lots = array_values((array) ($report['lots'] ?? []));
        $crop = (string) ($params['crop'] ?? ($report['crop']['key'] ?? ''));
        $method = (string) ($params['method'] ?? ($report['method']['key'] ?? ''));
        $variety = trim((string) ($params['variety'] ?? ($report['variety'] ?? '')));
        $loc = (array) ($params['location'] ?? []);
        $units = ['ha' => 'hectare', 'sqm' => 'sqm', 'acre' => 'acre'];
        $tagWord = LP::TAGS;
        $out = ['source' => 'landprep', 'id' => (int) $r->id, 'title' => (string) $r->title, 'phase' => 'prep', 'lots' => [], 'note' => null,
            'legacy' => ! $lots || empty($report['perLot'])];
        $first = null;

        foreach ($lots as $i => $l) {
            $pl = (array) ($report['perLot'][$i] ?? []);
            $planting = (string) ($pl['planting'] ?? ($l['planting'] ?? ''));
            if ($planting === '') {
                continue;
            }
            $lCrop = (string) ($l['crop']['key'] ?? $crop);
            $lMethod = (string) ($l['method']['key'] ?? $method);
            $nursery = LP::hasNursery($lCrop, $lMethod);
            $dayType = $nursery ? 'DAT' : (in_array($lMethod, ['dsr_wet_broadcast', 'dsr_wet_drum', 'dsr_dry', 'direct'], true) ? 'DAS' : 'DAP');
            $lVariety = isset($l['crop']) ? trim((string) ($l['variety'] ?? '')) : $variety;
            $defs = [];
            foreach ((array) ($report['lotSteps'][$i] ?? $report['steps'] ?? []) as $s) {
                $defs[$s['key']] = $s;
            }
            $dates = [];
            foreach ((array) ($pl['steps'] ?? []) as $ps) {
                $dates[$ps['key']] = $ps;
            }
            $at = function (string $key) use ($dates, $defs, $planting): ?string {
                $d = $dates[$key] ?? null;
                if ($d) {
                    return (string) ($d['start'] ?? $d['winFrom'] ?? '') ?: null;
                }

                return isset($defs[$key]) ? Carbon::parse($planting)->addDays((int) $defs[$key]['from'])->toDateString() : null;
            };
            $lotHa = NpkPlan::hectares(($l['input'] ?? null) ?: ($l['area'] ?? 1), $units[$l['unit'] ?? 'ha'] ?? 'hectare') ?? 1.0;
            $sowAt = $nursery ? ($at('sow_seedbed') ?? $at('sow_nursery') ?? $at('nursery')) : null;
            $dayZero = $nursery ? ($sowAt ?? Carbon::parse($planting)->subDays(21)->toDateString()) : $planting;
            $off = fn (string $date) => (int) Carbon::parse($planting)->diffInDays(Carbon::parse($date), false);
            $items = [];
            foreach ($defs as $key => $s) {
                $date = $at($key);
                if (! $date) {
                    continue;
                }
                $d = $dates[$key] ?? [];
                $win = ! empty($d['winFrom']) && ! empty($d['winTo']) && $d['winFrom'] !== $d['winTo']
                    ? 'Any day from ' . self::dayWord($d['winFrom']) . ' to ' . self::dayWord($d['winTo']) . '.' : '';
                $crew = ! empty($d['workDays']) ? ($d['workDays'] . ' work ' . ((int) $d['workDays'] === 1 ? 'day' : 'days') . (($d['crew'] ?? '') === 'machine' ? ' with the machine' : (($d['crew'] ?? '') === 'manual' ? ' by hand' : '')) . (! empty($d['end']) && $d['end'] !== ($d['start'] ?? null) ? ', until ' . self::dayWord($d['end']) : '') . '.') : '';
                $why = trim(($s['why'] ?? '') . ' ' . ($s['note'] ?? '') . ' ' . ($s['rule'] ?? ''));
                $html = '<p>' . nl2br(self::e($s['how'] ?? '')) . '</p>'
                    . ($win !== '' ? '<p><strong>When:</strong> ' . self::e($win) . '</p>' : '')
                    . ($crew !== '' ? '<p><strong>Crew:</strong> ' . self::e($crew) . '</p>' : '')
                    . ($why !== '' ? '<p><strong>Why:</strong> ' . self::e($why) . '</p>' : '')
                    . '<p><em>' . self::e($tagWord[$s['tag'] ?? 'extension'] ?? 'Extension practice') . ', from the Land Prep Planner.</em></p>';
                $zero = $nursery ? in_array($key, ['sow_seedbed', 'sow_nursery', 'nursery'], true) && $date === $dayZero : in_array($key, ['sow', 'plant'], true);
                $tp = $nursery && $key === 'transplant';
                $workDays = max(1, (int) ($d['workDays'] ?? 1));
                $hands = ! empty($d['workDays']) && ($d['crew'] ?? '') !== 'machine' ? max(1, (int) ceil(((float) ($d['units'] ?? $workDays)) / $workDays - 0.01)) : (! empty($d['workDays']) ? 1 : 0);
                $items[] = [
                    'key' => 'lp_' . $key, 'title' => (string) ($s['what'] ?? $key), 'html' => $html,
                    'type' => LP::typeOf((string) $key, $s['type'] ?? null),
                    'priority' => in_array($key, ['plow', 'level', 'transplant', 'sow', 'sow_seedbed', 'sow_nursery', 'plant', 'seed_soak'], true) ? 'high' : 'medium',
                    'date' => $date, 'end' => (string) ($d['end'] ?? $date), 'lo' => $d['winFrom'] ?? null, 'hi' => $d['winTo'] ?? null,
                    'hands' => $hands, 'hired' => false, 'anchor' => $zero || $tp || ! empty($d['planting']), 'zero' => $zero, 'transplant' => $tp,
                    'offset' => $off($date),
                    'materials' => PlanMaterials::landPrepStep((string) $key, (string) ($s['how'] ?? ''), $report, (int) $i, $lotHa, $lMethod),
                ];
            }
            // The seed soak and incubation, to the hour.
            foreach ((array) ($report['seed']['timeline'] ?? []) as $tl) {
                if ((int) ($tl['lot'] ?? -1) !== $i) {
                    continue;
                }
                foreach (array_values((array) ($tl['events'] ?? [])) as $k => $ev) {
                    if (! in_array($ev['kind'] ?? '', ['soak', 'incubate'], true) || empty($ev['at'])) {
                        continue;
                    }
                    $t = Carbon::parse($ev['at']);
                    $items[] = ['key' => 'lp_seed_' . $k, 'title' => $ev['what'] . ' at ' . $t->format('g:i A'), 'html' => '<p>' . self::e($ev['how'] ?? '') . '</p>',
                        'type' => 'seed_treatment', 'priority' => 'high', 'date' => $t->toDateString(), 'end' => $t->toDateString(), 'lo' => null, 'hi' => null,
                        'hands' => 1, 'hired' => false, 'anchor' => true, 'zero' => false, 'transplant' => false, 'offset' => $off($t->toDateString()), 'materials' => []];
                }
            }
            // The hired machines, booked ahead.
            foreach (array_values((array) ($report['bookings'] ?? [])) as $k => $b) {
                if ((int) ($b['lot'] ?? -1) !== $i || empty($b['by'])) {
                    continue;
                }
                $items[] = ['key' => 'lp_book_' . $k, 'title' => 'Book the hired ' . mb_strtolower((string) $b['name']),
                    'html' => '<p>It is first needed on ' . self::e(self::dayWord($b['use'] ?? null)) . (($b['for'] ?? '') !== '' ? ' for ' . self::e($b['for']) : '') . '. Book it now so it comes on time.</p>',
                    'type' => 'reminder_checklist', 'priority' => 'high', 'date' => (string) $b['by'], 'end' => (string) $b['by'], 'lo' => null, 'hi' => (string) ($b['use'] ?? $b['by']),
                    'hands' => 0, 'hired' => true, 'anchor' => false, 'zero' => false, 'transplant' => false, 'offset' => $off((string) $b['by']), 'materials' => []];
            }
            usort($items, fn ($a, $b) => strcmp($a['date'], $b['date']));
            foreach ($items as $it) {
                $first = $first === null || $it['date'] < $first ? $it['date'] : $first;
            }
            $out['lots'][] = [
                'i' => $i, 'name' => mb_substr((string) ($l['name'] ?? ('Lot ' . ($i + 1))), 0, 255),
                'size' => (float) (($l['input'] ?? null) ?: ($l['area'] ?? 1)), 'unit' => $units[$l['unit'] ?? 'ha'] ?? 'hectare',
                'crop' => self::cropKey(isset($l['crop']) ? $lCrop : $crop), 'cropLabel' => preg_replace('/\s*,.*$/', '', (string) ($l['crop']['label'] ?? ($report['crop']['label'] ?? ($params['cropLabel'] ?? $crop)))),
                'variety' => $lVariety, 'dayType' => $dayType, 'planting' => $planting, 'dayZero' => $dayZero, 'transplant' => $nursery ? $planting : null,
                'loc' => ['town' => (string) ($loc['town'] ?? ''), 'province' => (string) ($loc['province'] ?? '')],
                'items' => $items, 'note' => null, 'ha' => $lotHa, 'recs' => '',
            ];
        }
        // The season's crop words, as the planner always wrote them: every lot's crop, or the plan's own label for one.
        $names = array_values(array_unique(array_map(fn ($l) => preg_replace('/\s*,.*$/', '', (string) ($l['crop']['label'] ?? ($report['crop']['label'] ?? ($params['cropLabel'] ?? $crop)))), $lots)));
        $out['cropType'] = count($names) > 1 ? mb_substr(implode(', ', array_slice($names, 0, -1)) . ' and ' . end($names), 0, 255) : (string) ($report['crop']['label'] ?? ($params['cropLabel'] ?? $crop));
        $out['variety'] = $variety;
        // Anee's reading of the plan, in the day book on the first day.
        $an = (array) ($report['anee'] ?? []);
        if ($first && ! empty($an['summary'])) {
            $risks = array_map(fn ($x) => '<li><strong>' . self::e($x['title'] ?? '') . ':</strong> ' . self::e($x['text'] ?? '') . '</li>', array_slice((array) ($an['risks'] ?? []), 0, 5));
            $out['note'] = ['date' => $first, 'html' => '<p><strong>Anee\'s reading of the land preparation</strong></p><p>' . self::e($an['summary']) . '</p>' . ($risks ? '<ul>' . implode('', $risks) . '</ul>' : '')];
        }
        $out['recs'] = self::landPrepRecs($report);

        return $out;
    }

    /** The Land Prep plan's recommendations and notes, as the season's Notes and the builder's Rules & notes keep them. */
    private static function landPrepRecs(array $report): string
    {
        $an = (array) ($report['anee'] ?? []);
        $list = fn (array $xs) => $xs ? '<ul>' . implode('', $xs) . '</ul>' : '';
        $h = '';
        if (trim((string) ($an['summary'] ?? '')) !== '') {
            $h .= '<p><strong>Anee\'s reading</strong></p><p>' . self::e($an['summary']) . '</p>';
        }
        $h .= $list(array_map(fn ($x) => '<li><strong>' . self::e($x['title'] ?? '') . ':</strong> ' . self::e($x['text'] ?? '') . '</li>', array_filter((array) ($an['analysis'] ?? []), 'is_array')));
        foreach (['crew' => 'The crew', 'weather' => 'The weather'] as $k => $label) {
            if (trim((string) ($an[$k] ?? '')) !== '') {
                $h .= '<p><strong>' . $label . ':</strong> ' . self::e($an[$k]) . '</p>';
            }
        }
        $risks = array_filter((array) ($an['risks'] ?? []), 'is_array');
        if ($risks) {
            $h .= '<p><strong>Risks to watch</strong></p>' . $list(array_map(fn ($x) => '<li><strong>' . self::e($x['title'] ?? '') . ':</strong> ' . self::e($x['text'] ?? '') . (! empty($x['when']) ? ' <em>(' . self::e($x['when']) . ')</em>' : '') . '</li>', $risks));
        }
        $rules = array_filter((array) ($report['rules'] ?? []), fn ($r) => is_array($r) && trim((string) ($r['text'] ?? '')) !== '');
        if ($rules) {
            $h .= '<p><strong>Notes for your field</strong></p>' . $list(array_map(fn ($r) => '<li>' . self::e($r['text']) . '</li>', $rules));
        }
        $seed = (array) ($report['seed'] ?? []);
        if (! empty($seed['steps'])) {
            $h .= '<p><strong>Getting the seed ready</strong>' . (! empty($seed['say']) ? ' (' . self::e($seed['say']) . ')' : '') . '</p><ol>' . implode('', array_map(fn ($x) => '<li>' . self::e($x) . '</li>', (array) $seed['steps'])) . '</ol>';
        }
        if ((array) ($an['dataGaps'] ?? [])) {
            $h .= '<p><strong>What would make the plan surer</strong></p>' . $list(array_map(fn ($x) => '<li>' . self::e($x) . '</li>', (array) $an['dataGaps']));
        }

        return $h;
    }

    /** The board's type for a Crop Protocol job. */
    private const PROTOCOL_TYPES = ['seedbed' => 'planting', 'plant' => 'planting', 'fert' => 'fertilizer', 'herb' => 'herbicide', 'weed' => 'other', 'spray' => 'foliar_spray', 'harvest' => 'harvest'];

    /**
     * The Crop Protocol Analysis: every lot's jobs on the days the crew works
     * them (the seedbed, the planting, each fertilizer with what goes on, the
     * weed control, the foliar sprays, the harvest), with the lot's own
     * reading, what to watch and the protection to scout for in the day book
     * on its planting day, and the farm's reading on the first day.
     */
    private static function fromProtocol(object $r): array
    {
        $report = GovSource::scrubAll(json_decode((string) $r->report, true) ?: []);
        $out = ['source' => 'protocol', 'id' => (int) $r->id, 'title' => (string) $r->title, 'phase' => 'season', 'lots' => [], 'note' => null,
            'legacy' => (int) ($report['format'] ?? 0) < 4];
        $crops = [];
        foreach ((array) ($report['crops'] ?? []) as $c) {
            $crops[(string) ($c['key'] ?? '')] = $c;
        }
        $first = null;
        foreach ((array) ($report['lots'] ?? []) as $L) {
            $tasks = (array) ($L['tasks'] ?? []);
            $planting = (string) (($L['planting']['planting'] ?? null) ?: (collect($tasks)->firstWhere('key', 'plant')['from'] ?? ''));
            if ($planting === '' || ! $tasks) {
                continue;
            }
            $counter = (string) ($L['counter'] ?? 'DAS');
            $dat = $counter === 'DAT';
            $seedAge = (int) ($L['seedAge'] ?? 0);
            $dayZero = $dat ? Carbon::parse($planting)->subDays(max(1, $seedAge ?: 21))->toDateString() : $planting;
            $items = [];
            foreach ($tasks as $t) {
                $kind = (string) ($t['kind'] ?? 'other');
                $key = (string) ($t['key'] ?? $kind);
                $date = (string) ($t['from'] ?? $t['usual'] ?? '');
                if ($date === '') {
                    continue;
                }
                $products = array_values(array_filter(array_map('strval', (array) ($t['products'] ?? []))));
                $lo = (string) ($t['lo'] ?? ''); $hi = (string) ($t['hi'] ?? '');
                $pd = (float) ($t['pd'] ?? 0);
                $html = ($products ? '' : (trim((string) ($t['what'] ?? '')) !== '' ? '<p>' . self::e($t['what']) . '</p>' : ''))
                    . ($products ? '<p><strong>What to apply:</strong></p><ul>' . implode('', array_map(fn ($p) => '<li>' . self::e($p) . '</li>', $products)) . '</ul>' : '')
                    . (! empty($t['stage']) ? '<p><strong>Stage:</strong> ' . self::e($t['stage']) . '</p>' : '')
                    . ($lo !== '' && $hi !== '' && $lo !== $hi ? '<p><strong>When:</strong> Any day from ' . self::e(self::dayWord($lo)) . ' to ' . self::e(self::dayWord($hi)) . '.</p>' : '')
                    . ($pd > 0 ? '<p><strong>Crew:</strong> About ' . self::e(rtrim(rtrim(number_format($pd, 1, '.', ''), '0'), '.')) . ' person ' . ($pd <= 1 ? 'day' : 'days')
                        . (! empty($t['who']) ? ', ' . self::e($t['who']) : '') . (! empty($t['hired']) ? '. Hired, so your own workers are free' : '') . '.</p>' : '')
                    . '<p><em>From the Crop Protocol Analysis.</em></p>';
                $zero = $dat ? $kind === 'seedbed' : $kind === 'plant';
                $tp = $dat && $kind === 'plant';
                $items[] = [
                    'key' => 'cp_' . $key, 'title' => mb_substr((string) ($t['title'] ?? $key), 0, 255), 'html' => $html,
                    'type' => self::PROTOCOL_TYPES[$kind] ?? 'other',
                    'priority' => in_array($kind, ['plant', 'seedbed', 'harvest'], true) || ($kind === 'fert' && (int) ($t['day'] ?? 99) <= 3) ? 'high' : 'medium',
                    'date' => $date, 'end' => (string) ($t['to'] ?? $date), 'lo' => $lo ?: null, 'hi' => $hi ?: null,
                    'hands' => ! empty($t['hired']) ? 0 : max(1, (int) ($t['perDay'] ?? 1)), 'hired' => ! empty($t['hired']),
                    'anchor' => $zero || $tp, 'zero' => $zero, 'transplant' => $tp, 'offset' => (int) ($t['day'] ?? 0),
                    'materials' => PlanMaterials::protocolTask($t, $L),
                ];
            }
            if (! $items) {
                continue;
            }
            usort($items, fn ($a, $b) => strcmp($a['date'], $b['date']));
            $first = $first === null || $items[0]['date'] < $first ? $items[0]['date'] : $first;
            // The lot's own reading, what to watch, and the threats to scout for, on its planting day.
            $crop = $crops[(string) ($L['case'] ?? '')] ?? null;
            $threats = array_slice(array_values(array_filter((array) ($crop['protection']['threats'] ?? []), 'is_array')), 0, 8);
            $note = '<p><strong>Anee\'s protocol for ' . self::e($L['name'] ?? 'this lot') . '</strong></p>'
                . (trim((string) ($L['intro'] ?? '')) !== '' ? '<p>' . self::e($L['intro']) . '</p>' : '')
                . ((array) ($L['watch'] ?? []) ? '<p><strong>Watch for:</strong></p><ul>' . implode('', array_map(fn ($w) => '<li>' . self::e($w) . '</li>', (array) $L['watch'])) . '</ul>' : '')
                . ($threats ? '<p><strong>Scout for:</strong></p><ul>' . implode('', array_map(fn ($x) => '<li><strong>' . self::e($x['name'] ?? '') . ':</strong> '
                    . self::e(trim((string) (($x['threshold'] ?? '') ?: ($x['sign'] ?? '')))) . '</li>', $threats)) . '</ul>' : '')
                . (! empty($report['disclaimer']) ? '<p><em>' . self::e($report['disclaimer']) . '</em></p>' : '');
            $out['lots'][] = [
                'i' => (int) ($L['i'] ?? count($out['lots'])), 'name' => mb_substr((string) ($L['name'] ?? 'Lot'), 0, 255),
                'size' => (float) ($L['areaHa'] ?? 1) ?: 1.0, 'unit' => 'hectare',
                'crop' => self::cropKey((string) ($L['crop'] ?? '')), 'cropLabel' => (string) ($L['cropLabel'] ?? ''), 'variety' => trim((string) ($L['variety'] ?? '')),
                'dayType' => $dat ? 'DAT' : (in_array($counter, ['DAS', 'DAP'], true) ? $counter : 'DAP'),
                'planting' => $planting, 'dayZero' => $dayZero, 'transplant' => $dat ? $planting : null,
                'loc' => ['town' => (string) ($L['place']['town'] ?? ''), 'province' => (string) ($L['place']['province'] ?? '')],
                'items' => $items, 'note' => ['date' => $planting, 'html' => $note],
                'ha' => (float) ($L['areaHa'] ?? 1) ?: 1.0, 'recs' => self::protocolRecs($L, $crop, $threats),
            ];
        }
        if ($first && trim((string) ($report['summary'] ?? '')) !== '') {
            $out['note'] = ['date' => $first, 'html' => '<p><strong>Anee\'s crop protocol</strong></p>'
                . (trim((string) ($report['headline'] ?? '')) !== '' ? '<p>' . self::e($report['headline']) . '</p>' : '')
                . '<p>' . self::e($report['summary']) . '</p>'
                . ((array) ($report['workTips'] ?? []) ? '<ul>' . implode('', array_map(fn ($w) => '<li>' . self::e($w) . '</li>', (array) $report['workTips'])) . '</ul>' : '')];
        }
        $out['recs'] = (trim((string) ($report['summary'] ?? '')) !== '' ? '<p><strong>Anee\'s crop protocol</strong></p>' . (trim((string) ($report['headline'] ?? '')) !== '' ? '<p>' . self::e($report['headline']) . '</p>' : '') . '<p>' . self::e($report['summary']) . '</p>' : '')
            . ((array) ($report['workTips'] ?? []) ? '<p><strong>Work tips</strong></p><ul>' . implode('', array_map(fn ($w) => '<li>' . self::e($w) . '</li>', (array) $report['workTips'])) . '</ul>' : '');
        $out['disclaimer'] = trim((string) ($report['disclaimer'] ?? ''));

        return $out;
    }

    /** A Crop Protocol lot's recommendations: the soil and the bags, what to watch and scout for, the water, the shortfalls, the yield. */
    private static function protocolRecs(array $L, ?array $crop, array $threats): string
    {
        $list = fn (array $xs) => $xs ? '<ul>' . implode('', $xs) . '</ul>' : '';
        $h = '<p><strong>' . self::e(($L['name'] ?? 'This lot') . ' · ' . trim(($L['cropLabel'] ?? '') . (! empty($L['variety']) ? ', ' . $L['variety'] : ''), ', ')) . '</strong></p>';
        if (trim((string) ($L['intro'] ?? '')) !== '') {
            $h .= '<p>' . self::e($L['intro']) . '</p>';
        }
        $g = (array) ($L['granulars'] ?? []);
        if (! empty($g['soilLogic']) || ! empty($g['timingLogic'])) {
            $h .= '<p><strong>Why these fertilizers</strong></p>' . $list(array_values(array_filter([
                ! empty($g['soilLogic']) ? '<li><strong>The soil decides the bag:</strong> ' . self::e($g['soilLogic']) . '</li>' : null,
                ! empty($g['timingLogic']) ? '<li><strong>The stage decides the amount:</strong> ' . self::e($g['timingLogic']) . '</li>' : null,
            ])));
        }
        $cautions = array_values(array_filter(array_map('strval', (array) ($g['cautions'] ?? []))));
        if ($cautions) {
            $h .= '<p><strong>Be careful</strong></p>' . $list(array_map(fn ($x) => '<li>' . self::e($x) . '</li>', $cautions));
        }
        $fol = array_filter((array) (($L['rec'] ?? [])['foliars'] ?? []), 'is_array');
        if ($fol) {
            $h .= '<p><strong>Foliar sprays</strong></p>' . $list(array_map(fn ($f) => '<li><strong>' . self::e($f['product'] ?? '') . '</strong>' . (! empty($f['stage']) ? ' at ' . self::e(mb_strtolower($f['stage'])) : '') . (! empty($f['why']) ? ': ' . self::e($f['why']) : '') . '</li>', $fol));
        }
        if ((array) ($L['watch'] ?? [])) {
            $h .= '<p><strong>Watch for</strong></p>' . $list(array_map(fn ($w) => '<li>' . self::e($w) . '</li>', (array) $L['watch']));
        }
        if ($threats) {
            $h .= '<p><strong>Scout for</strong></p>' . $list(array_map(fn ($x) => '<li><strong>' . self::e($x['name'] ?? '') . ':</strong> '
                . self::e(trim((string) (($x['threshold'] ?? '') ?: ($x['sign'] ?? '')))) . (! empty($x['cultural']) ? ' First, without a spray: ' . self::e($x['cultural']) : '') . '</li>', $threats));
        }
        $w = (array) ($L['water'] ?? []);
        if (! empty($w['plan'])) {
            $h .= '<p><strong>Water:</strong> ' . self::e($w['plan']) . '</p>' . $list(array_values(array_filter([
                ! empty($w['ifDry']) ? '<li><strong>If dry:</strong> ' . self::e($w['ifDry']) . '</li>' : null,
                ! empty($w['ifWet']) ? '<li><strong>If wet:</strong> ' . self::e($w['ifWet']) . '</li>' : null,
            ])));
        }
        $defs = array_filter((array) ($L['deficiencies'] ?? []), 'is_array');
        if ($defs) {
            $h .= '<p><strong>Nutrients this soil may lack</strong></p>' . $list(array_map(fn ($d) => '<li><strong>' . self::e($d['nutrient'] ?? '') . ':</strong> ' . self::e(trim(($d['signs'] ?? '') . ' ' . ($d['action'] ?? ''))) . '</li>', $defs));
        }
        $y = (array) ($L['yield'] ?? []);
        if (! empty($y['target']) || ! empty($y['realistic'])) {
            $h .= '<p><strong>Yield:</strong> ' . self::e(trim((! empty($y['target']) ? 'target ' . $y['target'] : '') . (! empty($y['realistic']) ? (! empty($y['target']) ? ', ' : '') . 'realistic ' . $y['realistic'] : ''))) . '.' . (! empty($y['note']) ? ' ' . self::e($y['note']) : '') . '</p>';
        }

        return $h;
    }

    /* ============================================================ seasons */

    /** The owner's seasons a plan can go into: open ones, with the lots that are not harvested. */
    public static function targets(int $ownerId): array
    {
        $seasons = AsCroppingSchedule::active()->where('anisystemUserId', $ownerId)
            ->whereNotIn('status', [AsCroppingSchedule::STATUS_COMPLETED, AsCroppingSchedule::STATUS_ARCHIVED])
            ->orderByDesc('id')->limit(60)->get(['id', 'title', 'cropType', 'status', 'dayType']);
        if ($seasons->isEmpty()) {
            return [];
        }
        $lots = AsScheduleLot::active()->whereIn('croppingScheduleId', $seasons->pluck('id'))->orderBy('id')
            ->get(['id', 'croppingScheduleId', 'lotName', 'crop', 'variety', 'dayType', 'dayZeroDate', 'transplantDate', 'harvestedAt'])->groupBy('croppingScheduleId');
        $counts = self::workCounts($seasons->pluck('id')->all());

        return $seasons->map(fn ($s) => [
            'id' => (int) $s->id, 'title' => (string) $s->title, 'crop' => (string) $s->cropType, 'work' => (int) ($counts[$s->id] ?? 0),
            'lots' => collect($lots->get($s->id, []))->map(fn ($l) => [
                'id' => (int) $l->id, 'name' => (string) $l->lotName, 'crop' => (string) $l->crop, 'cropLabel' => $l->crop ? (string) (CropStages::label($l->crop) ?: $l->crop) : '',
                'variety' => (string) $l->variety, 'planting' => self::plantingOf($l), 'harvested' => (bool) $l->harvestedAt,
            ])->values()->all(),
        ])->values()->all();
    }

    /** How many activities each season's board has (its version in use, drafts not counted). */
    private static function workCounts(array $ids): array
    {
        return DB::table('as_schedule_activities as a')
            ->join('as_schedule_activity_versions as v', function ($j) {
                $j->on('v.id', '=', 'a.versionId')->where('v.isActive', 1)->where('v.deleteStatus', 1);
            })
            ->whereIn('a.croppingScheduleId', $ids ?: [-1])->where('a.deleteStatus', 1)->where('a.isDraft', 0)
            ->groupBy('a.croppingScheduleId')->selectRaw('a.croppingScheduleId as s, count(*) as n')->pluck('n', 's')->all();
    }

    /** A season lot's planting day: its transplant on a DAT count, its day zero otherwise. */
    public static function plantingOf($lot): ?string
    {
        $d = ($lot->dayType ?? null) === 'DAT' ? ($lot->transplantDate ?? null) : ($lot->dayZeroDate ?? null);
        $d ??= $lot->dayZeroDate ?? null;

        return $d ? Carbon::parse($d)->toDateString() : null;
    }

    /** The season's version in use. */
    public static function version(AsCroppingSchedule $s): AsScheduleActivityVersion
    {
        $v = $s->versions()->get();

        return $v->firstWhere('isActive', true) ?? $v->firstWhere('isOriginal', true) ?? $v->first()
            ?? AsScheduleActivityVersion::create(['croppingScheduleId' => $s->id, 'versionName' => 'Original', 'isOriginal' => 1, 'isActive' => 1, 'versionOrder' => 0, 'deleteStatus' => 1]);
    }

    /**
     * Each mapped plan lot moved onto the season: its lot (or none, for a new
     * one), how far the plan moves to meet that lot's own planting day, and
     * every item's day there, with the ones already past marked.
     *
     * @param  array<int|string, int|string>  $map  plan lot index => season lot id, or 'new'
     */
    public static function shifted(array $plan, AsCroppingSchedule $s, array $map, ?string $today = null): array
    {
        $today ??= now('Asia/Manila')->toDateString();
        $lots = AsScheduleLot::active()->where('croppingScheduleId', $s->id)->get()->keyBy('id');
        $out = [];
        foreach ($plan['lots'] as $pl) {
            $to = $map[$pl['i']] ?? $map[(string) $pl['i']] ?? null;
            if ($to === null || $to === '' || $to === 'skip') {
                continue;
            }
            $lot = $to === 'new' ? null : $lots->get((int) $to);
            if ($to !== 'new' && ! $lot) {
                continue;
            }
            $anchor = $lot ? self::plantingOf($lot) : null;
            $delta = $anchor ? (int) Carbon::parse($pl['planting'])->diffInDays(Carbon::parse($anchor), false) : 0;
            $items = array_map(function ($it) use ($delta, $today) {
                $mv = fn (?string $d) => $d ? Carbon::parse($d)->addDays($delta)->toDateString() : null;
                $at = $mv($it['date']);

                return $it + ['at' => $at, 'atEnd' => $mv($it['end'] ?? $it['date']), 'atLo' => $mv($it['lo'] ?? null), 'atHi' => $mv($it['hi'] ?? null), 'past' => $at < $today];
            }, $pl['items']);
            $out[] = ['plan' => $pl, 'lot' => $lot, 'new' => $lot === null, 'anchor' => $anchor ?? $pl['planting'], 'delta' => $delta, 'items' => $items];
        }

        return $out;
    }

    /**
     * Why this plan cannot go onto this lot any more, or null. The owner's
     * example: land preparation for a lot that is already planted. A season
     * plan cannot go onto a lot whose work it covers is all behind it.
     */
    public static function over(array $sh, string $phase, ?string $today = null): ?string
    {
        $today ??= now('Asia/Manila')->toDateString();
        $lot = $sh['lot'];
        $name = $lot ? $lot->lotName : $sh['plan']['name'];
        if ($lot && $lot->harvestedAt) {
            return $name . ' is already harvested.';
        }
        if ($phase === 'prep') {
            $planted = $lot ? self::plantedOn($lot) : null;
            if ($planted) {
                return 'Land preparation is over for ' . $name . ': it was planted on ' . self::dayWord($planted) . '.';
            }
            if ($sh['anchor'] <= $today) {
                return 'Land preparation is over for ' . $name . ': its planting day, ' . self::dayWord($sh['anchor']) . ', has come.';
            }
        }
        $left = array_filter($sh['items'], fn ($it) => ! $it['past']);
        if (! $left) {
            return 'Every step of this plan for ' . $name . ' falls before today, so there is nothing left to add.';
        }

        return null;
    }

    /** The day a lot's planting was ticked done on the board, if it was. */
    private static function plantedOn(AsScheduleLot $lot): ?string
    {
        $a = AsScheduleActivity::where('croppingScheduleId', $lot->croppingScheduleId)->where('deleteStatus', 1)->where('isDone', 1)
            ->where(fn ($q) => $q->where('isTransplant', 1)->orWhere(fn ($w) => $w->where('isDayZero', 1)->where('activityType', 'planting')))
            ->whereHas('lots', fn ($q) => $q->where('as_schedule_lots.id', $lot->id))->orderBy('targetDate')->first(['targetDate']);

        return $a ? Carbon::parse($a->targetDate)->toDateString() : null;
    }

    /** Does the season's board already carry any work? */
    public static function hasWork(AsCroppingSchedule $s): bool
    {
        return (int) (self::workCounts([$s->id])[$s->id] ?? 0) > 0;
    }

    /**
     * The board around the new work: every activity of the season from a
     * week before the first new day to a week after the last, with its lots,
     * its workers and whether it is done. What Anee reads, and what the crew
     * placement counts.
     */
    public static function boardAround(AsCroppingSchedule $s, string $from, string $to): array
    {
        $v = self::version($s);
        $rows = AsScheduleActivity::where('croppingScheduleId', $s->id)->where('versionId', $v->id)->where('deleteStatus', 1)->where('isDraft', 0)
            ->whereBetween('targetDate', [Carbon::parse($from)->subDays(7)->toDateString(), Carbon::parse($to)->addDays(7)->toDateString()])
            ->with(['lots:as_schedule_lots.id,as_schedule_lots.lotName', 'workers:as_schedule_workers.id'])->orderBy('targetDate')->orderBy('sequenceOrder')->limit(400)
            ->get(['id', 'activityTitle', 'activityType', 'targetDate', 'targetEndDate', 'isDone', 'isDayZero', 'isTransplant']);

        return $rows->map(fn ($a) => [
            'id' => (int) $a->id, 'title' => (string) $a->activityTitle, 'type' => (string) $a->activityType,
            'date' => Carbon::parse($a->targetDate)->toDateString(), 'end' => $a->targetEndDate ? Carbon::parse($a->targetEndDate)->toDateString() : null,
            'done' => (bool) $a->isDone, 'lots' => $a->lots->pluck('lotName')->values()->all(), 'lotIds' => $a->lots->pluck('id')->map(fn ($x) => (int) $x)->values()->all(),
            'workers' => $a->workers->pluck('id')->map(fn ($x) => (int) $x)->values()->all(),
            'anchor' => (bool) ($a->isDayZero || $a->isTransplant),
        ])->values()->all();
    }

    /**
     * The crew of a season, day by day: who works it (its roster, less each
     * one's weekdays and dates off), and who the board already gives work to.
     *
     * @return array{workers:int, names:list<string>, free:callable}
     */
    public static function crew(AsCroppingSchedule $s, array $board): array
    {
        $ws = AsScheduleWorker::active()->where('croppingScheduleId', $s->id)->with(['offDays', 'offDates'])->get();
        $offDays = [];
        $offDates = [];
        foreach ($ws as $w) {
            $offDays[$w->id] = $w->offDays->pluck('dayOfWeek')->map(fn ($d) => (int) $d)->all();
            $offDates[$w->id] = $w->offDates->map(fn ($d) => Carbon::parse($d->offDate)->toDateString())->all();
        }
        $busy = [];
        foreach ($board as $a) {
            if ($a['done']) {
                continue;
            }
            $end = $a['end'] && $a['end'] > $a['date'] ? $a['end'] : $a['date'];
            for ($d = Carbon::parse($a['date']); $d->toDateString() <= $end; $d->addDay()) {
                foreach ($a['workers'] as $wid) {
                    $busy[$d->toDateString()][$wid] = true;
                }
            }
        }
        $ids = $ws->pluck('id')->map(fn ($x) => (int) $x)->all();
        $free = function (string $date) use ($ids, $offDays, $offDates, $busy): int {
            $dow = (int) Carbon::parse($date)->dayOfWeek;
            $n = 0;
            foreach ($ids as $id) {
                if (in_array($dow, $offDays[$id] ?? [], true) || in_array($date, $offDates[$id] ?? [], true) || isset($busy[$date][$id])) {
                    continue;
                }
                $n++;
            }

            return $n;
        };

        return ['workers' => count($ids), 'names' => $ws->map(fn ($w) => (string) $w->workerName)->values()->all(), 'free' => $free,
            'off' => $ws->map(fn ($w) => ['name' => (string) $w->workerName, 'days' => $offDays[$w->id] ?? [], 'dates' => $offDates[$w->id] ?? []])->values()->all()];
    }

    /**
     * By the crew's free days: each new task keeps its day while the season
     * still has a worker free for it that day (after what the board already
     * gives them, and the new work placed before it); else it goes to the
     * next day with enough free, up to its last safe day and at most two
     * weeks on. The planting, hired work and work no hand does stay put. A
     * season with no workers on it has nothing to count: the days stand.
     *
     * @return array{dates: array<string, array<string, string>>, moved: int, short: list<string>, workers: int}
     */
    public static function crewPlace(AsCroppingSchedule $s, array $shifted, array $board, ?string $today = null): array
    {
        $today ??= now('Asia/Manila')->toDateString();
        $crew = self::crew($s, $board);
        $dates = [];
        $moved = 0;
        $short = [];
        $used = [];
        $all = [];
        foreach ($shifted as $k => $sh) {
            foreach ($sh['items'] as $it) {
                if (! $it['past']) {
                    $all[] = [$k, $it];
                }
            }
        }
        usort($all, fn ($a, $b) => strcmp($a[1]['at'], $b[1]['at']));
        foreach ($all as [$k, $it]) {
            $len = max(0, (int) Carbon::parse($it['at'])->diffInDays(Carbon::parse($it['atEnd'] ?: $it['at']), false));
            $need = (int) $it['hands'];
            $date = $it['at'];
            if ($crew['workers'] > 0 && $need > 0 && ! $it['anchor'] && ! $it['hired']) {
                $fits = function (string $d) use ($len, $need, $crew, &$used): bool {
                    for ($x = Carbon::parse($d), $i = 0; $i <= $len; $i++, $x->addDay()) {
                        $day = $x->toDateString();
                        if ($crew['free']($day) - ($used[$day] ?? 0) < min($need, $crew['workers'])) {
                            return false;
                        }
                    }

                    return true;
                };
                $limit = $it['atHi'] && $it['atHi'] > $it['at'] ? $it['atHi'] : Carbon::parse($it['at'])->addDays(14)->toDateString();
                $limit = min($limit, Carbon::parse($it['at'])->addDays(14)->toDateString());
                $try = max($it['at'], $today);
                $found = null;
                for ($x = Carbon::parse($try); $x->toDateString() <= $limit; $x->addDay()) {
                    if ($fits($x->toDateString())) {
                        $found = $x->toDateString();
                        break;
                    }
                }
                if ($found === null) {
                    $short[] = $it['title'] . ' (' . $sh['plan']['name'] . ', ' . self::dayWord($it['at']) . ')';
                } elseif ($found !== $it['at']) {
                    $date = $found;
                    $moved++;
                }
            }
            $dates[$k][$it['key']] = $date;
            if ($need > 0 && ! $it['hired']) {
                for ($x = Carbon::parse($date), $i = 0; $i <= $len; $i++, $x->addDay()) {
                    $used[$x->toDateString()] = ($used[$x->toDateString()] ?? 0) + min($need, max(1, $crew['workers']));
                }
            }
        }

        return ['dates' => $dates, 'moved' => $moved, 'short' => $short, 'workers' => $crew['workers']];
    }

    /** The plan's own days (moved onto each lot's planting). */
    public static function planDates(array $shifted): array
    {
        $dates = [];
        foreach ($shifted as $k => $sh) {
            foreach ($sh['items'] as $it) {
                if (! $it['past']) {
                    $dates[$k][$it['key']] = $it['at'];
                }
            }
        }

        return $dates;
    }

    /* ============================================================ the builder */

    /** An activity's words as the builder's plain text. */
    public static function plain(string $html): string
    {
        $t = preg_replace(['#</p>\s*#i', '#<br\s*/?>#i', '#<li[^>]*>#i', '#</li>#i'], ["\n\n", "\n", '- ', "\n"], $html);
        $t = html_entity_decode(strip_tags((string) $t), ENT_QUOTES, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", $t));
    }

    /**
     * One plan lot as Protocol Builder entries (2026-10-22): a divider, then
     * every task on the protocol's own count, from the lot's days. "We will
     * just have anee think about where to apply it based on the DAS" (the
     * owner): no check is needed, since a protocol is a count, not a calendar,
     * and the builder already keeps its tasks in the order of their days. A
     * transplanted lot counts the seedbed as DAS (the sowing is DAS 0) and the
     * field from the transplant (DAT 0); work before the sowing is BEFORE
     * DAS. A protocol with one count keeps it.
     *
     * @param  list<string>  $counters  the protocol's day type's counters
     */
    public static function toBuilder(array $plan, array $pl, array $counters, string $prefix): array
    {
        $seed = $pl['transplant'] ? (int) Carbon::parse($pl['dayZero'])->diffInDays(Carbon::parse($pl['planting'])) : 0;
        $dat = in_array('DAT', $counters, true) && $seed > 0;
        $first = $counters[0] ?? 'DAS';
        $where = function (int $off) use ($dat, $first, $seed): array {
            if ($dat) {
                return $off >= 0 ? ['DAT', $off] : ['DAS', $off + $seed];
            }

            return [$first, $first === 'DAS' && $seed > 0 ? $off + $seed : $off];
        };
        $label = $plan['source'] === 'landprep' ? 'Land preparation' : 'Crop protocol' . ($pl['name'] !== '' ? ' · ' . $pl['name'] : '');
        $items = $pl['items'];
        if (! $items) {
            return [];
        }
        [$c0, $d0] = $where((int) $items[0]['offset']);
        $out = [['id' => $prefix . '_div', 'kind' => 'divider', 'counter' => $c0, 'day' => max(-365, min(999, $d0)), 'label' => mb_substr($label, 0, 80), 'color' => 'green', 'pos' => 0]];
        $pos = 10;
        foreach ($items as $it) {
            [$counter, $day] = $where((int) $it['offset']);
            // BEFORE the count is only before the first counter (the builder's own rule).
            if ($day < 0 && $counter !== $first) {
                $counter = $first;
            }
            $when = $it['lo'] && $it['hi'] && $it['lo'] !== $it['hi'] ? 'Any day from ' . self::dayWord($it['lo']) . ' to ' . self::dayWord($it['hi']) . ' in the plan.' : '';
            $out[] = [
                'id' => $prefix . '_' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $it['key']),
                'counter' => $counter,
                'day' => max(-365, min(999, $day)),
                'title' => mb_substr((string) $it['title'], 0, 160),
                'subtitle' => mb_substr('From the ' . self::SOURCES[$plan['source']]['label'], 0, 200),
                'description' => mb_substr(self::plain((string) $it['html']), 0, 4000),
                'type' => isset(AsScheduleActivity::ACTIVITY_TYPES[$it['type']]) ? $it['type'] : 'other',
                'spray' => [],
                'groups' => [],
                'note' => mb_substr($when, 0, 2000),
                'priority' => $it['priority'] === 'high' ? 'high' : 'medium',
                'workers' => $it['hands'] > 0 ? (int) $it['hands'] : null,
                'pos' => $pos,
            ];
            $pos += 10;
        }

        return $out;
    }

    /* ============================================================ writing */

    /**
     * One new season made out of the plan: a lot per plan lot (its name,
     * size, crop, variety and place, counted from its own day), every item an
     * activity on its day, and the readings in the day book.
     */
    public static function newSeason(array $plan, string $title, int $ownerId): array
    {
        $made = ['activities' => 0, 'lots' => 0, 'notes' => 0, 'materials' => 0];
        $lots = $plan['lots'];
        $cropNames = array_values(array_unique(array_filter(array_map(fn ($l) => (string) $l['cropLabel'], $lots))));
        $dayTypes = array_values(array_unique(array_column($lots, 'dayType')));
        $first = $lots[0] ?? null;
        $label = self::SOURCES[$plan['source']]['label'];
        $schedule = DB::transaction(function () use ($plan, $lots, $title, $ownerId, $cropNames, $dayTypes, $first, $label, &$made) {
            $schedule = AsCroppingSchedule::create([
                'anisystemUserId' => $ownerId,
                'usersId' => (int) config('anisystem.order_users_id', 1),
                'title' => mb_substr($title, 0, 255),
                'description' => mb_substr('Made from the ' . $label . ' plan "' . $plan['title'] . '". Every step is on the board on the day your crew starts it.', 0, 5000),
                'cropType' => $plan['cropType'] ?? (count($cropNames) > 1 ? mb_substr(implode(', ', array_slice($cropNames, 0, -1)) . ' and ' . end($cropNames), 0, 255) : ($cropNames[0] ?? null)),
                'cropVariety' => ($v = (string) ($plan['variety'] ?? ($first['variety'] ?? ''))) !== '' ? mb_substr($v, 0, 255) : null,
                'dayType' => count($dayTypes) === 1 ? $dayTypes[0] : (in_array('DAT', $dayTypes, true) ? 'DAT' : ($dayTypes[0] ?? 'DAS')),
                'status' => 'setup',
                'isActive' => 1,
                'deleteStatus' => 1,
            ]);
            $version = AsScheduleActivityVersion::create([
                'croppingScheduleId' => $schedule->id, 'versionName' => 'Original', 'isOriginal' => 1, 'isActive' => 1, 'versionOrder' => 0, 'deleteStatus' => 1,
            ]);
            $perDate = [];
            foreach ($lots as $pl) {
                $lot = self::makeLot($schedule, $pl);
                $made['lots']++;
                foreach ($pl['items'] as $it) {
                    $a = self::addActivity($schedule, $version, $lot, $it, $it['date'], $perDate);
                    $made['materials'] += PlanMaterials::onActivity($a, (array) ($it['materials'] ?? []), $label);
                    $made['activities']++;
                }
                if ($pl['note']) {
                    self::addNote($schedule, $version, $pl['note']['date'], $pl['note']['html']);
                    $made['notes']++;
                }
            }
            if ($plan['note']) {
                self::addNote($schedule, $version, $plan['note']['date'], $plan['note']['html']);
                $made['notes']++;
            }
            $made['notes'] += self::addRecs($schedule, $plan, $lots, $ownerId) ? 1 : 0;

            return $schedule;
        });

        return [$schedule, $made];
    }

    /**
     * The plan into a season the farmer already has: each mapped lot's work
     * on the days given (the plan's, the crew's or Anee's), the ones Anee
     * found already on the board left out, a new lot made where asked, the
     * lot's planting day set where it had none, and the readings in the day
     * book. One transaction.
     *
     * @param  array<int, array<string, string>>  $dates  shifted index => item key => date
     * @param  list<string>  $skip  item keys to leave out ("lot index:key")
     */
    public static function intoSeason(array $plan, AsCroppingSchedule $s, array $shifted, array $dates, array $skip = []): array
    {
        $made = ['activities' => 0, 'lots' => 0, 'notes' => 0, 'skipped' => 0, 'past' => 0, 'materials' => 0];
        $version = self::version($s);
        $label = self::SOURCES[$plan['source']]['label'];
        DB::transaction(function () use ($plan, $s, $shifted, $dates, $skip, $version, $label, &$made) {
            $perDate = [];
            foreach (AsScheduleActivity::where('croppingScheduleId', $s->id)->where('versionId', $version->id)->where('deleteStatus', 1)
                ->groupBy('targetDate')->selectRaw('DATE(targetDate) as d, count(*) as n')->pluck('n', 'd') as $d => $n) {
                $perDate[$d] = (int) $n;
            }
            foreach ($shifted as $k => $sh) {
                $pl = $sh['plan'];
                $lot = $sh['lot'];
                if (! $lot) {
                    $lot = self::makeLot($s, $pl);
                    $made['lots']++;
                } elseif (! self::plantingOf($lot)) {
                    // A lot with no day of its own takes the plan's, so its counts read on the board.
                    $lot->forceFill(array_filter(['dayType' => $lot->dayType ?: $pl['dayType'], 'dayZeroDate' => $pl['dayZero'],
                        'transplantDate' => $pl['transplant'], 'crop' => $lot->crop ?: ($pl['crop'] ?: null),
                        'variety' => $lot->variety ?: ($pl['variety'] !== '' ? mb_substr($pl['variety'], 0, 255) : null)], fn ($v) => $v !== null))->save();
                }
                // The plan's amounts are for its own lot: a season lot of another size takes them in proportion.
                $factor = 1.0;
                if ($sh['lot'] && ($own = NpkPlan::hectares($lot->lotSize, $lot->lotSizeUnit)) && ($pl['ha'] ?? 0) > 0) {
                    $factor = $own / (float) $pl['ha'];
                }
                $hasAnchor = AsScheduleActivity::where('croppingScheduleId', $s->id)->where('versionId', $version->id)->where('deleteStatus', 1)
                    ->where(fn ($q) => $q->where('isDayZero', 1)->orWhere('isTransplant', 1))
                    ->whereHas('lots', fn ($q) => $q->where('as_schedule_lots.id', $lot->id))->exists();
                foreach ($sh['items'] as $it) {
                    if ($it['past']) {
                        $made['past']++;

                        continue;
                    }
                    if (in_array($k . ':' . $it['key'], $skip, true)) {
                        $made['skipped']++;

                        continue;
                    }
                    // A lot that already has its day zero keeps the one it has.
                    if ($hasAnchor) {
                        $it['zero'] = false;
                        $it['transplant'] = false;
                    }
                    $a = self::addActivity($s, $version, $lot, $it, (string) ($dates[$k][$it['key']] ?? $it['at']), $perDate);
                    $made['materials'] += PlanMaterials::onActivity($a, PlanMaterials::scale((array) ($it['materials'] ?? []), $factor), $label);
                    $made['activities']++;
                }
                if ($pl['note']) {
                    $d = Carbon::parse($pl['note']['date'])->addDays((int) $sh['delta'])->toDateString();
                    self::addNote($s, $version, max($d, now('Asia/Manila')->toDateString()), $pl['note']['html']);
                    $made['notes']++;
                }
            }
            if ($plan['note'] && $shifted) {
                $d = Carbon::parse($plan['note']['date'])->addDays((int) $shifted[array_key_first($shifted)]['delta'])->toDateString();
                self::addNote($s, $version, max($d, now('Asia/Manila')->toDateString()), $plan['note']['html']);
                $made['notes']++;
            }
            if ($shifted) {
                $made['notes'] += self::addRecs($s, $plan, array_map(fn ($sh) => $sh['plan'], $shifted), (int) $s->anisystemUserId) ? 1 : 0;
            }
        });

        return $made;
    }

    private static function makeLot(AsCroppingSchedule $s, array $pl): AsScheduleLot
    {
        return AsScheduleLot::create(array_filter([
            'croppingScheduleId' => $s->id,
            'lotName' => mb_substr($pl['name'], 0, 255),
            'lotSize' => (float) $pl['size'] ?: 1,
            'lotSizeUnit' => $pl['unit'] ?: 'hectare',
            'variety' => $pl['variety'] !== '' ? mb_substr($pl['variety'], 0, 255) : null,
            'crop' => $pl['crop'] ?: null,
            'locTown' => ($pl['loc']['town'] ?? '') !== '' ? mb_substr($pl['loc']['town'], 0, 120) : null,
            'locProvince' => ($pl['loc']['province'] ?? '') !== '' ? mb_substr($pl['loc']['province'], 0, 120) : null,
            'dayType' => $pl['dayType'],
            'dayZeroDate' => $pl['dayZero'],
            'transplantDate' => $pl['transplant'],
            'deleteStatus' => 1,
        ], fn ($v) => $v !== null));
    }

    private static function addActivity(AsCroppingSchedule $s, AsScheduleActivityVersion $v, AsScheduleLot $lot, array $it, string $date, array &$perDate): AsScheduleActivity
    {
        $end = null;
        if (! empty($it['end']) && $it['end'] > $it['date']) {
            $end = Carbon::parse($date)->addDays((int) Carbon::parse($it['date'])->diffInDays(Carbon::parse($it['end'])))->toDateString();
        }
        $perDate[$date] = ($perDate[$date] ?? 0) + 1;
        $a = AsScheduleActivity::create([
            'croppingScheduleId' => $s->id, 'versionId' => $v->id,
            'activityTitle' => mb_substr((string) $it['title'], 0, 255), 'targetDate' => $date, 'targetEndDate' => $end,
            'priority' => $it['priority'], 'activityType' => isset(AsScheduleActivity::ACTIVITY_TYPES[$it['type']]) ? $it['type'] : 'other',
            'description' => HtmlSanitizer::rich((string) $it['html']), 'timeRequired' => 'n/a',
            'isDayZero' => (bool) $it['zero'], 'isTransplant' => (bool) $it['transplant'], 'isDraft' => false, 'isHidden' => false, 'isDone' => false,
            'workerChecklist' => false, 'workerSelfCheck' => false, 'sequenceOrder' => ($perDate[$date] - 1) * 10, 'deleteStatus' => 1,
        ]);
        $a->lots()->attach($lot->id);

        return $a;
    }

    /** The plan's recommendations and notes (the farm's, then each lot's) as one note in the season's Notes. */
    private static function addRecs(AsCroppingSchedule $s, array $plan, array $lots, int $userId): bool
    {
        $html = self::recsHtml($plan, $lots);
        if ($html === '') {
            return false;
        }
        AsScheduleNote::create([
            'croppingScheduleId' => $s->id, 'userId' => $userId,
            'title' => mb_substr('Recommendations: ' . $plan['title'], 0, 191),
            'body' => HtmlSanitizer::rich($html), 'sortOrder' => 0, 'deleteStatus' => 1,
        ]);

        return true;
    }

    /** The recommendations and notes of a plan for these lots, with each lot's materials, as HTML. */
    public static function recsHtml(array $plan, array $lots): string
    {
        $h = (string) ($plan['recs'] ?? '');
        foreach ($lots as $pl) {
            $h .= (string) ($pl['recs'] ?? '');
            $mats = PlanMaterials::totals((array) ($pl['items'] ?? []));
            if ($mats) {
                $h .= '<p><strong>Materials for ' . self::e($pl['name']) . ' (' . self::e(PlanMaterials::areaWords((float) ($pl['ha'] ?? 1))) . ')</strong></p><ul>'
                    . implode('', array_map(fn ($m) => '<li>' . self::e($m['name']) . ($m['qty'] !== null ? ': ' . self::e(PlanMaterials::say($m) . ($m['unit'] === 'piece' ? ' pieces' : '')) : '') . ($m['note'] !== '' ? ' <em>' . self::e($m['note']) . '</em>' : '') . '</li>', $mats)) . '</ul>';
            }
        }
        if (! empty($plan['disclaimer'])) {
            $h .= '<p><em>' . self::e($plan['disclaimer']) . '</em></p>';
        }

        return trim($h) === '' ? '' : '<p><em>From the ' . self::e(self::SOURCES[$plan['source']]['label']) . ': ' . self::e($plan['title']) . '.</em></p>' . $h;
    }

    private static function addNote(AsCroppingSchedule $s, AsScheduleActivityVersion $v, string $date, string $html): void
    {
        AsScheduleDateNote::create([
            'croppingScheduleId' => $s->id, 'versionId' => $v->id, 'noteDate' => $date,
            'noteContent' => HtmlSanitizer::rich($html), 'deleteStatus' => 1,
        ]);
    }
}
