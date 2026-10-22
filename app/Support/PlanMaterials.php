<?php

namespace App\Support;

use App\Models\AsInventoryItem;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleActivityItem;

/**
 * What a ported plan needs, carried with it (2026-10-23, the owner: "when
 * porting land prep and the crop protocol analysis to protocol builder or
 * cropping schedule, the inventory or materials needed should also be ported
 * or added as well as recommendations and notes").
 *
 * A material is [name, kind (the Protocol Builder's kinds), qty (or null when
 * the plan gives no rate, like a herbicide "as its label says"), unit (kg or
 * piece), note], always for the plan's own lot. The Crop Protocol reads its
 * jobs: each fertilizer job's products as written ("20 kg Complete
 * 14-14-14"), the seed from the lot's stand, the herbicide and the foliar
 * sprays by name. The Land Prep Planner writes its inputs into the words of
 * fixed steps (LandPrep's templates), so the rates of those steps are kept
 * here per hectare, and the seed comes from the plan's own amounts.
 *
 * Into a season: each material is a line on its activity and, when it has a
 * quantity, an item on the season's inventory with nothing on hand yet, so
 * the inventory says what is still to buy. Into the Protocol Builder: the
 * protocol's materials, and an "apply" group on each task drawing on them.
 */
class PlanMaterials
{
    /** The Land Prep steps whose words name an input, per hectare: [name, kind, qty a hectare, unit, note]. */
    private const LAND_PREP = [
        'manure' => [['Organic fertilizer (chicken manure, vermicompost or compost)', 'bio', 500, 'kg', 'About 10 bags of 50 kg a hectare.']],
        'seedbed_feed' => [['Complete 14-14-14', 'fertilizer', 3, 'kg', '2 to 4 kg for the seedbed of a hectare.']],
        'nursery_feed' => [['Complete 14-14-14', 'fertilizer', 3, 'kg', '2 to 4 kg for the nursery of a hectare.']],
        'seedbed_zinc' => [['Zinc sulfate', 'fertilizer', 1.5, 'kg', '1 to 2 kg for the seedbed of a hectare, where zinc is short.']],
        'nursery_zinc' => [['Zinc sulfate', 'fertilizer', 1.5, 'kg', '1 to 2 kg for the nursery of a hectare, where zinc is short.']],
        'lime' => [['Agricultural lime or dolomite', 'other', null, 'kg', 'At the rate your soil test gives.']],
        'gypsum' => [['Gypsum', 'other', null, 'kg', 'At the rate your soil test gives.']],
        'weeds' => [['Pre emergence herbicide', 'herbicide', null, '', 'Only where weeds are heavy. Follow its label for the rate.']],
        'weeds_snails' => [['Pre emergence herbicide', 'herbicide', null, '', 'Only where weeds are heavy. Follow its label for the rate.']],
    ];

    /** The Protocol Builder's kinds, as the season's inventory files them. */
    private const SHED_KIND = ['fertilizer' => 'granular', 'bio' => 'granular', 'foliar' => 'foliar', 'herbicide' => 'herbicide', 'molluscicide' => 'molluscicide',
        'insecticide' => 'pesticide', 'fungicide' => 'fungicide', 'seed' => 'seed', 'tool' => 'tool'];

    /* ============================================================ reading */

    /** A Crop Protocol job's materials, for its lot. */
    public static function protocolTask(array $t, array $L): array
    {
        $kind = (string) ($t['kind'] ?? 'other');
        $out = [];
        if ($kind === 'fert') {
            foreach ((array) ($t['products'] ?? []) as $p) {
                if ($m = self::readAmount((string) $p)) {
                    $out[] = $m + ['kind' => self::fertKind($m['name']), 'note' => ''];
                }
            }
        }
        $dat = ($L['counter'] ?? '') === 'DAT';
        if (($kind === 'plant' && ! $dat) || ($kind === 'seedbed' && $dat)) {
            if ($seed = self::protocolSeed($L)) {
                $out[] = $seed;
            }
        }
        if ($kind === 'herb') {
            $out[] = ['name' => mb_substr(trim((string) ($t['title'] ?? '')) ?: 'Herbicide', 0, 120), 'kind' => 'herbicide', 'qty' => null, 'unit' => '', 'note' => 'Follow its label for the rate.'];
        }
        if ($kind === 'spray') {
            $stage = mb_strtolower(trim((string) ($t['stage'] ?? '')));
            $names = [];
            foreach ((array) (($L['rec'] ?? [])['foliars'] ?? []) as $f) {
                $fs = mb_strtolower(trim((string) ($f['stage'] ?? '')));
                if ($stage === '' || $fs === '' || str_contains($fs, $stage) || str_contains($stage, $fs)) {
                    $names[] = trim((string) ($f['product'] ?? ''));
                }
            }
            $names = array_values(array_unique(array_filter($names))) ?: [trim((string) ($t['title'] ?? '')) ?: 'Foliar spray'];
            foreach ($names as $n) {
                $out[] = ['name' => mb_substr($n, 0, 120), 'kind' => 'foliar', 'qty' => null, 'unit' => '', 'note' => 'Follow its label for the dose.'];
            }
        }

        return $out;
    }

    /** The seed a Crop Protocol lot needs, from the stand it was counted for. */
    private static function protocolSeed(array $L): ?array
    {
        $s = (array) (($L['need'] ?? [])['seed'] ?? []);
        $ha = (float) ($L['areaHa'] ?? 1) ?: 1.0;
        $per = (float) ($s['perHa'] ?? 0);
        if ($per <= 0) {
            return null;
        }
        $what = trim((string) ($L['variety'] ?? '')) ?: trim((string) ($L['cropLabel'] ?? 'the crop'));
        if (($s['unit'] ?? '') === 'kg') {
            return ['name' => mb_substr('Seed: ' . $what, 0, 120), 'kind' => 'seed', 'qty' => round($per * $ha, 2), 'unit' => 'kg', 'note' => 'About ' . self::n($per) . ' kg a hectare.'];
        }

        return ['name' => mb_substr('Seed: ' . $what, 0, 120), 'kind' => 'seed', 'qty' => round($per * $ha), 'unit' => 'piece',
            'note' => 'One seed for each of about ' . number_format($per) . ' ' . ($s['unit'] ?? 'plants') . ' a hectare; buy a little more for the ones that do not come up.'];
    }

    /** A Land Prep step's materials, for its lot of `$ha` hectares. */
    public static function landPrepStep(string $key, string $how, array $report, int $lot, float $ha, string $method): array
    {
        $out = [];
        foreach (self::LAND_PREP[$key] ?? [] as [$name, $kind, $perHa, $unit, $note]) {
            $out[] = ['name' => $name, 'kind' => $kind, 'qty' => $perHa === null ? null : round($perHa * $ha, 2), 'unit' => $unit, 'note' => $note];
        }
        if (in_array($key, ['weeds', 'weeds_snails'], true) && preg_match('/snail/i', $how)) {
            $out[] = ['name' => 'Molluscicide for golden apple snails', 'kind' => 'molluscicide', 'qty' => null, 'unit' => '', 'note' => 'Only where snails are many. Follow its label for the rate.'];
        }
        // Trays only for a machine transplanter, which plants from them.
        if ($key === 'nursery_site' && str_contains($method, 'machine') && preg_match('/(\d+) to (\d+) trays/i', $how, $m)) {
            $out[] = ['name' => 'Seedling trays (28 by 58 cm)', 'kind' => 'tool', 'qty' => round(((int) $m[1] + (int) $m[2]) / 2 * $ha), 'unit' => 'piece', 'note' => $m[1] . ' to ' . $m[2] . ' trays a hectare.'];
        }
        if (in_array($key, ['seed_soak', 'sow', 'sow_seedbed', 'sow_nursery'], true) && ($seed = self::landPrepSeed($key, $how, $report, $lot, $ha))) {
            $out[] = $seed;
        }

        return $out;
    }

    /** The seed of a Land Prep lot: the plan's own amount, the inbred's upper and the hybrid's lower figure. */
    private static function landPrepSeed(string $key, string $how, array $report, int $lot, float $ha): ?array
    {
        $seed = (array) ($report['seed'] ?? []);
        // The seed goes on the soak when the plan has one, else on the sowing.
        $steps = array_column((array) ($report['steps'] ?? []), 'key');
        if ($key !== 'seed_soak' && in_array('seed_soak', $steps, true)) {
            return null;
        }
        $variety = trim((string) ($report['variety'] ?? '')) ?: trim((string) ($report['crop']['label'] ?? 'the crop'));
        $hybrid = ($seed['type'] ?? '') === 'hybrid';
        foreach ((array) ($seed['amounts'] ?? []) as $a) {
            if ((int) ($a['lot'] ?? -1) === $lot && (float) ($a['hi'] ?? 0) > 0) {
                return ['name' => mb_substr('Seed: ' . preg_replace('/\s*,.*$/', '', $variety), 0, 120), 'kind' => 'seed', 'qty' => (float) ($hybrid ? $a['lo'] : $a['hi']), 'unit' => 'kg',
                    'note' => self::n((float) $a['lo']) . ' to ' . self::n((float) $a['hi']) . ' kg for this lot.'];
            }
        }
        if (preg_match('/(\d+) to (\d+) kg (?:of (?:good )?seed )?per hectare/i', $how, $m)) {
            return ['name' => mb_substr('Seed: ' . preg_replace('/\s*,.*$/', '', $variety), 0, 120), 'kind' => 'seed', 'qty' => round((float) ($hybrid ? $m[1] : $m[2]) * $ha, 2), 'unit' => 'kg',
                'note' => $m[1] . ' to ' . $m[2] . ' kg a hectare.'];
        }

        return null;
    }

    /** "20 kg Complete 14-14-14", "2 bags Urea 46-0-0", "500 g Zinc sulfate" read as kilos of a product. */
    public static function readAmount(string $p): ?array
    {
        if (! preg_match('/^\s*([\d.,]+)\s*(kg|kilos?|g|grams?|bags?|sacks?)\b\.?\s+(?:of\s+)?(.+)$/iu', trim($p), $m)) {
            return null;
        }
        $q = (float) str_replace(',', '', $m[1]);
        $u = mb_strtolower($m[2]);
        $kg = str_starts_with($u, 'bag') || str_starts_with($u, 'sack') ? $q * 50 : (in_array($u, ['g', 'gram', 'grams'], true) ? $q / 1000 : $q);
        $name = trim(preg_replace('/\s+/', ' ', $m[3]));

        return $name === '' || $kg <= 0 ? null : ['name' => mb_substr($name, 0, 120), 'qty' => round($kg, 3), 'unit' => 'kg'];
    }

    private static function fertKind(string $name): string
    {
        return preg_match('/manure|compost|vermi|organic|chicken|bokashi/i', $name) ? 'bio' : 'fertilizer';
    }

    /** The materials for a lot of another size: the quantities follow the land. */
    public static function scale(array $mats, float $factor): array
    {
        if (abs($factor - 1) < 1e-6) {
            return $mats;
        }

        return array_map(fn ($m) => $m['qty'] === null ? $m : array_merge($m, ['qty' => round($m['qty'] * $factor, $m['unit'] === 'piece' ? 0 : 3)]), $mats);
    }

    /** Every material of a lot's items, added up by name and unit, in the order first met. */
    public static function totals(array $items): array
    {
        $out = [];
        foreach ($items as $it) {
            foreach ((array) ($it['materials'] ?? []) as $m) {
                $k = mb_strtolower($m['name']) . '|' . $m['unit'];
                if (! isset($out[$k])) {
                    $out[$k] = $m;
                } elseif ($m['qty'] !== null) {
                    $out[$k]['qty'] = round(($out[$k]['qty'] ?? 0) + $m['qty'], 3);
                }
            }
        }

        return array_values($out);
    }

    /** "1,000 m²", "1.5 hectares". */
    public static function areaWords(float $ha): string
    {
        return $ha < 1 ? number_format(round($ha * 10000)) . ' m²' : self::n($ha) . ($ha == 1 ? ' hectare' : ' hectares');
    }

    private static function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ','), '0'), '.');
    }

    public static function say(array $m): string
    {
        return $m['qty'] === null ? '' : ($m['unit'] === 'piece' ? number_format($m['qty']) : self::n((float) $m['qty']) . ' ' . $m['unit']);
    }

    /* ============================================================ writing */

    /**
     * An activity's materials on the board: a line each, and for each with a
     * quantity an item in the season's inventory (made with nothing on hand,
     * or the one already there by that name), so the inventory counts it as
     * needed and still to buy. Returns how many lines were written.
     */
    public static function onActivity(AsScheduleActivity $a, array $mats, string $label): int
    {
        $lines = 0;
        $stamp = now('Asia/Manila');
        // A material with no rate (a herbicide "as its label says") is no line to count: the activity's words name it.
        $byLabel = array_values(array_filter($mats, fn ($m) => $m['qty'] === null || $m['qty'] <= 0));
        $said = mb_strtolower(strip_tags((string) $a->description));
        $unsaid = array_values(array_filter($byLabel, fn ($m) => ! str_contains($said, mb_strtolower($m['name']))));
        if ($unsaid) {
            $a->forceFill(['description' => HtmlSanitizer::rich((string) $a->description . '<p><strong>What to apply:</strong> '
                . htmlspecialchars(implode(', ', array_map(fn ($m) => $m['name'], $unsaid)), ENT_QUOTES, 'UTF-8') . ', as its label says.</p>')])->save();
        }
        foreach ($mats as $m) {
            if ($m['qty'] === null || $m['qty'] <= 0) {
                continue;
            }
            $itemId = null;
            if ($m['qty'] !== null && $m['qty'] > 0) {
                $shed = AsInventoryItem::where('croppingScheduleId', $a->croppingScheduleId)->where('deleteStatus', 1)->where('name', $m['name'])->first()
                    ?? AsInventoryItem::create(['croppingScheduleId' => $a->croppingScheduleId, 'name' => $m['name'], 'kind' => self::SHED_KIND[$m['kind']] ?? 'other',
                        'unit' => $m['unit'] === 'piece' ? 'piece' : 'kg', 'note' => mb_substr('Needed by the ' . $label . ' plan.', 0, 500), 'deleteStatus' => 1]);
                $itemId = $shed->id;
            }
            AsScheduleActivityItem::create([
                'activityId' => $a->id, 'itemType' => 'custom', 'itemName' => mb_substr($m['name'], 0, 255),
                'quantity' => $m['qty'], 'unitOfMeasure' => $m['qty'] === null ? null : ($m['unit'] === 'piece' ? 'piece' : $m['unit']),
                'inventoryItemId' => $itemId, 'newBuy' => 0,
                'notes' => mb_substr(trim('From the ' . $label . '. ' . $m['note']), 0, 190),
                'deleteStatus' => 1, 'created_at' => $stamp, 'updated_at' => $stamp,
            ]);
            $lines++;
        }

        return $lines;
    }

    /**
     * The Protocol Builder's side: the protocol's materials (added to the ones
     * it has; one already there by name is drawn on, not doubled) and an
     * "apply" group on each of the plan's tasks, keyed by the task's id.
     *
     * @param  array<string, array>  $byTask  task id => its materials
     * @return array{0: array, 1: array, 2: int} [tasks, materials, materials added]
     */
    public static function intoBuilder(array $tasks, array $materials, array $byTask, string $forWhom): array
    {
        $byName = [];
        foreach ($materials as $m) {
            $byName[mb_strtolower((string) ($m['name'] ?? '')) . '|' . ($m['unit'] ?? '')] = (string) $m['id'];
        }
        $added = 0;
        $pos = $materials ? max(array_map(fn ($m) => (int) ($m['pos'] ?? 0), $materials)) + 10 : 0;
        foreach ($tasks as &$t) {
            $mats = $byTask[(string) ($t['id'] ?? '')] ?? [];
            if (! $mats || (isset($t['kind']) && $t['kind'] !== 'task')) {
                continue;
            }
            $items = [];
            foreach ($mats as $m) {
                $iid = 'i' . substr(md5(($t['id'] ?? '') . $m['name']), 0, 8);
                if ($m['qty'] === null) {
                    $items[] = ['id' => $iid, 'name' => $m['name'], 'kind' => $m['kind'], 'amount' => 'As its label says'];
                    continue;
                }
                $bu = $m['unit'] === 'piece' ? 'pieces' : $m['unit'];
                $k = mb_strtolower($m['name']) . '|' . $bu;
                if (! isset($byName[$k])) {
                    $mid = 'm' . substr(md5($k . $forWhom), 0, 8);
                    $materials[] = ['id' => $mid, 'name' => $m['name'], 'kind' => $m['kind'], 'qty' => 0.0, 'unit' => $bu,
                        'note' => mb_substr('Needed by ' . $forWhom . '.', 0, 500), 'pos' => $pos];
                    $pos += 10;
                    $byName[$k] = $mid;
                    $added++;
                }
                $mid = $byName[$k];
                // A material the plan made carries what its tasks draw, so it reads as bought, not short.
                foreach ($materials as &$mm) {
                    if ($mm['id'] === $mid && str_starts_with((string) ($mm['note'] ?? ''), 'Needed by ' . $forWhom)) {
                        $mm['qty'] = round((float) $mm['qty'] + $m['qty'], 3);
                    }
                }
                unset($mm);
                $items[] = ['id' => $iid, 'name' => $m['name'], 'kind' => $m['kind'], 'amount' => self::say($m) . ($m['unit'] === 'piece' ? ' pieces' : ''), 'materialId' => $mid, 'qty' => $m['qty']];
            }
            if ($items) {
                $t['groups'] = array_merge((array) ($t['groups'] ?? []), [['id' => 'g' . substr(md5((string) ($t['id'] ?? '')), 0, 8), 'title' => 'What to apply', 'perKnapsack' => false, 'loads' => null, 'items' => $items]]);
            }
        }
        unset($t);

        return [$tasks, $materials, $added];
    }
}
