<?php

namespace App\Support;

use App\Models\AsAutoSeason;
use App\Models\AsAutoTask;
use App\Models\AsInventoryItem;
use App\Models\AsInventoryMove;
use App\Services\InventoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Anee Auto's shed (2026-10-28, the owner: "ask the user if user wants to include inventory and price (if yes, then
 * there's a tab for inventory in which user needs to fulfill during the season)").
 *
 * The season's own inventory (the cropping schedule's items and moves, the same book the Inventory module keeps) read
 * against what the plan still needs: each product of every planned task, its amount parsed into a unit, summed by
 * product, set beside what is on hand, what is left to buy and what that costs at the farmer's prices. The farmer
 * fills in what they have and what they buy; a task marked done takes what it used off the shelf.
 *
 * Writes go through InventoryService (the one writer), so the board's Inventory module shows the same stock.
 */
final class AutoShed
{
    /** Words for units, as a plan or a farmer writes them, to the inventory's unit keys. */
    private const WORDS = [
        'kg' => 'kg', 'kgs' => 'kg', 'kilo' => 'kg', 'kilos' => 'kg', 'kilogram' => 'kg', 'kilograms' => 'kg',
        'g' => 'g', 'gm' => 'g', 'gms' => 'g', 'gram' => 'g', 'grams' => 'g',
        'l' => 'L', 'li' => 'L', 'lt' => 'L', 'liter' => 'L', 'liters' => 'L', 'litre' => 'L', 'litres' => 'L',
        'ml' => 'ml', 'cc' => 'ml', 'milliliter' => 'ml', 'milliliters' => 'ml', 'millilitre' => 'ml', 'millilitres' => 'ml',
        'sachet' => 'sachet', 'sachets' => 'sachet', 'pack' => 'sachet', 'packs' => 'sachet', 'packet' => 'sachet', 'packets' => 'sachet',
        'piece' => 'piece', 'pieces' => 'piece', 'pc' => 'piece', 'pcs' => 'piece',
    ];

    /** The season keeps a shed when the farmer said so on the first step (on by default). */
    public static function on(AsAutoSeason $auto): bool
    {
        return (bool) AutoOptions::of((array) $auto->answers)['inventory'];
    }

    /**
     * "1.5 bags", "300 g", "2 bags of 50 kg", "1-2 L": the amount in an inventory unit, the upper end of a range
     * (what to have on hand). A bag without its size takes the size the rate names, else 50 kg. Null when the
     * amount says no number or no unit the shed can count ("as the label says").
     *
     * @return array{qty: float, unit: string}|null
     */
    public static function parse(?string $amount, ?string $rate = ''): ?array
    {
        $t = mb_strtolower(trim((string) $amount));
        $t = (string) preg_replace('/(\d),(\d{3})\b/', '$1$2', $t);
        if (! preg_match('/(\d+(?:[.,]\d+)?)(?:\s*(?:-|–|to)\s*(\d+(?:[.,]\d+)?))?\s*([a-z]+)?/u', $t, $m)) {
            return null;
        }
        $qty = (float) str_replace(',', '.', ($m[2] ?? '') !== '' ? $m[2] : $m[1]);
        $word = $m[3] ?? '';
        if ($qty <= 0 || $word === '') {
            return null;
        }
        if (in_array($word, ['bag', 'bags', 'sack', 'sacks'], true)) {
            $size = preg_match('/(\d+)\s*(?:kg|kilo)/', $t . ' ' . mb_strtolower((string) $rate), $s) ? (int) $s[1] : ($word[0] === 's' ? 0 : 50);
            $key = ['50' => 'bag50', '40' => 'bag40', '25' => 'bag25', '20' => 'bag20'][(string) $size] ?? null;

            return $key ? ['qty' => $qty, 'unit' => $key] : ($word[0] === 's' ? ['qty' => $qty, 'unit' => 'sack'] : ['qty' => $qty * $size, 'unit' => 'kg']);
        }
        if (in_array($word, ['bottle', 'bottles'], true)) {
            return ['qty' => $qty, 'unit' => preg_match('/250\s*ml/', $t) ? 'bottle250' : 'bottle1'];
        }
        $unit = self::WORDS[$word] ?? null;

        return $unit ? ['qty' => $qty, 'unit' => $unit] : null;
    }

    /** The inventory kind a task's product belongs to (granular fertilizer, fungicide...). */
    public static function kindOf(string $taskKind, ?string $group, ?string $unit): string
    {
        $g = strtoupper((string) $group);
        if (str_contains($g, 'FRAC')) {
            return 'fungicide';
        }
        if (str_contains($g, 'HRAC')) {
            return 'herbicide';
        }
        if (str_contains($g, 'IRAC')) {
            return 'pesticide';
        }
        $volume = $unit && (AsInventoryItem::UNITS[$unit]['dim'] ?? '') === 'volume';

        return match ($taskKind) {
            'fertilizer' => $volume ? 'foliar' : 'granular',
            'foliar' => 'foliar',
            'herbicide' => 'herbicide',
            'fungicide' => 'fungicide',
            'insecticide', 'protect' => 'pesticide',
            'nursery', 'sowing', 'planting', 'transplant' => 'seed',
            default => 'other',
        };
    }

    /** A product's name, as words to compare: "Urea 46-0-0" and "urea" are the same thing on a shelf. */
    public static function words(string $name): array
    {
        $n = mb_strtolower((string) preg_replace('/\([^)]*\)/', ' ', $name));

        return array_values(array_filter(preg_split('/[^a-z0-9.\-]+/u', $n) ?: [], fn ($w) => $w !== '' && $w !== '-'));
    }

    /** The shed's item a product is: the same words, or one name's words all inside the other's. */
    public static function match(string $name, Collection $items): ?AsInventoryItem
    {
        $a = self::words($name);
        if (! $a) {
            return null;
        }
        $best = null;
        $bestScore = 0;
        foreach ($items as $it) {
            $b = self::words((string) $it->name);
            if (! $b) {
                continue;
            }
            $long = fn ($ws) => (bool) array_filter($ws, fn ($w) => mb_strlen($w) >= 4);
            $score = $a === $b ? 3 : (! array_diff($b, $a) && $long($b) ? 2 : (! array_diff($a, $b) && $long($a) ? 1 : 0));
            if ($score > $bestScore) {
                $best = $it;
                $bestScore = $score;
            }
        }

        return $best;
    }

    private static function items(AsAutoSeason $auto): Collection
    {
        return AsInventoryItem::where('croppingScheduleId', $auto->croppingScheduleId)->where('deleteStatus', 1)->orderBy('name')->get();
    }

    /** A quantity in a unit, in plain words: "1.5 bags (50 kg)", "300 g". */
    public static function say(float $qty, string $unit): string
    {
        $u = AsInventoryItem::UNITS[$unit] ?? null;
        $n = AsInventoryItem::trim(round($qty, 2));

        return $u ? $n . ' ' . (abs($qty - 1) < 0.0001 ? $u['one'] : $u['many']) . (isset($u['of']) ? ' (' . $u['of'] . ')' : '') : $n . ' ' . $unit;
    }

    /**
     * The shed against the plan: every product the season still needs, what is on hand, what to buy and its cost.
     *
     * @return array{rows: array, extra: array, totals: array, units: array}
     */
    public static function book(AsAutoSeason $auto): array
    {
        $items = self::items($auto);
        $have = app(InventoryService::class)->onHandFor((int) $auto->croppingScheduleId);
        $today = now('Asia/Manila')->startOfDay();
        $lots = \App\Models\AsScheduleLot::whereIn('id', AsAutoTask::where('autoId', $auto->id)->whereNotNull('lotId')->distinct()->pluck('lotId'))->pluck('lotName', 'id');
        $tasks = AsAutoTask::where('autoId', $auto->id)->where('deleteStatus', 1)->where('status', 'planned')->orderBy('plannedDate')->get();

        $needs = [];
        foreach ($tasks as $t) {
            foreach ((array) ($t->detail['products'] ?? []) as $p) {
                $name = trim((string) ($p['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $q = self::parse($p['amount'] ?? '', $p['rate'] ?? '');
                $item = self::match($name, $items);
                $key = $item ? 'i' . $item->id : 'n' . implode(' ', self::words($name));
                $n = &$needs[$key];
                $n ??= ['key' => $key, 'name' => $item?->name ?? $name, 'item' => $item, 'kind' => $item?->kind ?? self::kindOf((string) $t->kind, $p['group'] ?? null, $q['unit'] ?? null),
                    'unit' => $item?->unit ?? ($q['unit'] ?? null), 'still' => 0.0, 'counted' => 0, 'loose' => [], 'next' => null, 'tasks' => 0, 'lots' => []];
                $n['tasks']++;
                if ($t->lotId && isset($lots[$t->lotId])) {
                    $n['lots'][$lots[$t->lotId]] = true;
                }
                if ($t->plannedDate && (! $n['next'] || $t->plannedDate->lt($n['next']))) {
                    $n['next'] = $t->plannedDate->copy();
                }
                $n['unit'] ??= $q['unit'] ?? null;
                $in = $q && $n['unit'] ? AsInventoryItem::convert($q['qty'], $q['unit'], $n['unit']) : null;
                if ($in !== null) {
                    $n['still'] += $in;
                    $n['counted']++;
                } else {
                    $n['loose'][] = trim(($p['amount'] ?? '') ?: 'as the label says');
                }
                unset($n);
            }
        }

        $rows = [];
        $buyCost = 0.0;
        $unpriced = 0;
        foreach ($needs as $n) {
            $it = $n['item'];
            $onHand = $it ? (float) ($have[$it->id] ?? 0) : null;
            $unit = $n['unit'];
            $buy = $n['counted'] ? max(0.0, $n['still'] - (float) ($onHand ?? 0)) : null;
            $price = $it && $it->unitPrice !== null ? (float) $it->unitPrice : null;
            $cost = $buy !== null && $price !== null ? round($buy * $price, 2) : null;
            if ($buy > 0.0001) {
                $cost !== null ? $buyCost += $cost : $unpriced++;
            }
            $days = $n['next'] ? (int) $today->diffInDays($n['next'], false) : null;
            $rows[] = [
                'key' => $n['key'], 'itemId' => $it?->id, 'name' => $n['name'], 'kind' => $n['kind'], 'icon' => AsInventoryItem::KINDS[$n['kind']]['icon'] ?? '📦',
                'unit' => $unit, 'unitSays' => $unit ? AsInventoryItem::unitSays($unit, false) : null, 'unitOne' => $unit ? AsInventoryItem::unitSays($unit, true) : null, 'kin' => $unit ? AsInventoryItem::kin($unit) : array_slice(AsInventoryItem::unitsFor($n['kind']), 0, 6),
                'still' => $n['counted'] ? round($n['still'], 3) : null, 'stillSays' => $n['counted'] && $unit ? self::say($n['still'], $unit) : null, 'loose' => array_values(array_unique($n['loose'])),
                'have' => $onHand, 'haveSays' => $it ? $it->say((float) $onHand) : null,
                'buy' => $buy !== null ? round($buy, 3) : null, 'buySays' => $buy !== null && $buy > 0.0001 && $unit ? self::say($buy, $unit) : null,
                'price' => $price, 'cost' => $cost, 'next' => $n['next']?->format('M j'), 'days' => $days, 'tasks' => $n['tasks'], 'lots' => array_keys($n['lots']),
                'state' => $buy === null ? 'loose' : ($buy > 0.0001 ? ($days !== null && $days <= 7 ? 'soon' : 'short') : 'ok'),
            ];
        }
        $rank = ['soon' => 0, 'short' => 1, 'loose' => 2, 'ok' => 3];
        usort($rows, fn ($a, $b) => [$rank[$a['state']], $a['days'] ?? 9999] <=> [$rank[$b['state']], $b['days'] ?? 9999]);

        $used = collect($rows)->pluck('itemId')->filter()->all();
        $extra = $items->reject(fn ($it) => in_array($it->id, $used, true))->map(fn ($it) => ['itemId' => (int) $it->id, 'name' => $it->name, 'icon' => $it->icon(),
            'haveSays' => $it->say((float) ($have[$it->id] ?? 0)), 'price' => $it->unitPrice !== null ? (float) $it->unitPrice : null, 'unitSays' => AsInventoryItem::unitSays($it->unit, true)])->values();
        $worth = $items->sum(fn ($it) => $it->unitPrice !== null ? max(0, (float) ($have[$it->id] ?? 0)) * (float) $it->unitPrice : 0);
        $spent = AsAutoTask::where('autoId', $auto->id)->where('status', 'done')->get()->flatMap(fn ($t) => (array) (($t->doneData ?? [])['spent'] ?? []))->sum(fn ($s) => (float) ($s['cost'] ?? 0));

        return [
            'rows' => $rows, 'extra' => $extra,
            'totals' => ['buy' => round($buyCost, 2), 'unpriced' => $unpriced, 'worth' => round($worth, 2), 'spent' => round($spent, 2),
                'short' => count(array_filter($rows, fn ($r) => in_array($r['state'], ['soon', 'short'], true))), 'ready' => count(array_filter($rows, fn ($r) => $r['state'] === 'ok'))],
            'units' => collect(AsInventoryItem::UNITS)->map(fn ($u, $k) => [AsInventoryItem::unitSays($k, true), AsInventoryItem::unitSays($k, false)])->all(),
        ];
    }

    /**
     * What a task takes off the shelf, for the done sheet: each product found in the shed, the plan's amount in the
     * shelf's unit, and what is on hand. Products not in the shed are named so the farmer knows they are not counted.
     */
    public static function forTask(AsAutoTask $t, Collection $items, array $have): array
    {
        $out = ['take' => [], 'missing' => []];
        foreach ((array) ($t->detail['products'] ?? []) as $p) {
            $name = trim((string) ($p['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $it = self::match($name, $items);
            if (! $it) {
                $out['missing'][] = $name;
                continue;
            }
            $q = self::parse($p['amount'] ?? '', $p['rate'] ?? '');
            $qty = $q ? AsInventoryItem::convert($q['qty'], $q['unit'], $it->unit) : null;
            $out['take'][] = ['itemId' => (int) $it->id, 'name' => $it->name, 'qty' => $qty !== null ? round($qty, 3) : null, 'unitSays' => AsInventoryItem::unitSays($it->unit, false),
                'haveSays' => $it->say((float) ($have[$it->id] ?? 0))];
        }

        return $out;
    }

    /** The shed's items and their stock, once, for every task of a feed. */
    public static function shelf(AsAutoSeason $auto): array
    {
        return [self::items($auto), app(InventoryService::class)->onHandFor((int) $auto->croppingScheduleId)];
    }

    /**
     * A task done takes what it used off the shelf, once: each [itemId, qty] the farmer confirmed (in the item's
     * unit), as an OUT move dated the day it was done. What it cost (at the item's price) is kept on the task.
     *
     * @param  array<int, array{itemId:int, qty:float}>  $lines
     * @return array<int, array{itemId:int, qty:float, moveId:int, cost:float}>
     */
    public static function spend(AsAutoSeason $auto, AsAutoTask $t, array $lines, string $on): array
    {
        if (! empty(($t->doneData ?? [])['spent'])) {
            return (array) $t->doneData['spent'];
        }
        $items = self::items($auto)->keyBy('id');
        $svc = app(InventoryService::class);
        $out = [];
        foreach ($lines as $l) {
            $it = $items->get((int) ($l['itemId'] ?? 0));
            $qty = round((float) ($l['qty'] ?? 0), 3);
            if (! $it || $qty <= 0 || $qty > 100000) {
                continue;
            }
            $m = $svc->move($it, -$qty, AsInventoryMove::OUT, $on, 'Used: ' . mb_substr($t->title, 0, 150) . ' (Anee Auto)');
            if ($m) {
                $out[] = ['itemId' => (int) $it->id, 'qty' => $qty, 'moveId' => (int) $m->id, 'cost' => $it->unitPrice !== null ? round($qty * (float) $it->unitPrice, 2) : 0.0];
            }
        }

        return $out;
    }

    /**
     * The farmer fills the shed: an amount of a product they have or bought (in any unit of its kind), and what one
     * unit cost. A product not in the shed yet becomes an item whose book starts with this count; one already there
     * gets the amount as stock in, at its own price.
     *
     * @return array{item: AsInventoryItem, says: string}
     */
    public static function add(AsAutoSeason $auto, ?int $itemId, string $name, string $kind, string $unit, float $qty, ?float $price, string $on, bool $bought): array
    {
        $svc = app(InventoryService::class);
        $item = $itemId ? self::items($auto)->firstWhere('id', $itemId) : null;
        if ($itemId && ! $item) {
            throw new \InvalidArgumentException('That item is not in this season\'s shed.');
        }
        if (! $item) {
            $kind = isset(AsInventoryItem::KINDS[$kind]) ? $kind : 'other';
            $item = AsInventoryItem::create(['croppingScheduleId' => $auto->croppingScheduleId, 'name' => mb_substr(trim($name), 0, 190), 'kind' => $kind,
                'unit' => $unit, 'unitPrice' => $price, 'deleteStatus' => 1]);
            AsInventoryMove::create(['croppingScheduleId' => $auto->croppingScheduleId, 'itemId' => $item->id, 'delta' => 0, 'qtyBefore' => 0, 'qtyAfter' => 0,
                'reason' => AsInventoryMove::CREATED, 'happenedOn' => $on, 'byUserId' => Auth::id(), 'deleteStatus' => 1]);
            $open = $svc->startCount($item, $qty, $on, $bought ? 'Bought for Anee Auto' : 'Counted for Anee Auto');
            if ($open && $price !== null) {
                $open->update(['unitPrice' => $price]);
            }

            return ['item' => $item, 'says' => $item->say($qty)];
        }
        $book = AsInventoryItem::convert($qty, $unit, $item->unit);
        if ($book === null) {
            throw new \InvalidArgumentException($item->name . ' is counted in ' . AsInventoryItem::unitSays($item->unit, false) . '. Say the amount in a unit like that.');
        }
        $per = $price !== null ? InventoryService::pricePerBookUnit($price, $qty, $book) : null;
        $svc->move($item, $book, AsInventoryMove::IN, $on, $bought ? 'Bought for Anee Auto' : 'Counted for Anee Auto', null,
            $unit !== $item->unit ? ['qty' => $qty, 'unit' => $unit] : null, $per);
        if ($per !== null && $item->unitPrice === null) {
            $item->update(['unitPrice' => $per]);
        }

        return ['item' => $item, 'says' => $item->say($book)];
    }

    /** "₱1,450" */
    public static function peso(float $v): string
    {
        return '₱' . number_format($v, $v == floor($v) ? 0 : 2);
    }
}
