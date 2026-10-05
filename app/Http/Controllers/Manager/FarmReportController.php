<?php

namespace App\Http\Controllers\Manager;

use App\Models\AiSetting;
use App\Models\AsFarmReport;
use App\Models\AsInventoryItem;
use App\Models\AsInventoryMove;
use App\Services\AiClient;
use App\Services\AiCreditService;
use App\Support\AiPrices;
use App\Support\AiUsage;
use App\Support\Tier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * The farm's report shelf: freezing a computed report so it can ride into an
 * Anee chat, and (in later kinds) the AI-written season reads.
 *
 * The attach walk mirrors when-to-plant exactly: snapshot → the composer
 * boots with ?freport=ID → preview() weighs the tokens → ask() folds
 * contextFor() into the priced prompt.
 */
class FarmReportController extends BaseScheduleController
{
    /**
     * Freeze a computed report (labor / expenses / profit) as a shelf row.
     * The body is the report SAID IN TEXT — the same rendering Copy-as-Text
     * gives the farmer — because what Anee reads should be what they saw.
     */
    public function snapshot(Request $request)
    {
        $schedule = $this->schedule($request->input('scheduleId'));

        // Writing to the shelf is edit work; a view-level worker reads it.
        if (! \App\Support\WorkerContext::canWriteModule('reports')) {
            return $this->jsonFail('Only the owner, or a worker allowed to edit Reports, can make and save reports.', 403);
        }

        $v = Validator::make($request->all(), [
            'kind' => 'required|in:labor,expenses,profit',
            'title' => 'required|string|max:180',
            'body' => 'required|string|max:60000',
            'params' => 'nullable|array',
            // The computed dataset, so the shelf can redraw the report
            // exactly as it looked — not only say it in text.
            'report' => 'nullable|array',
        ]);
        if ($v->fails()) {
            return $this->jsonFail('Please check what you entered.', 422, ['errors' => $v->errors()]);
        }

        $row = AsFarmReport::create([
            'userId' => Auth::id(),
            'croppingScheduleId' => $schedule->id,
            'kind' => $request->input('kind'),
            'title' => trim($request->input('title')),
            'params' => $request->input('params') ?: null,
            'report' => $request->input('report') ?: null,
            'body' => $request->input('body'),
            'status' => 'ready',
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('Report saved.', ['data' => ['id' => $row->id]]);
    }

    /** What an attached report adds to a question — the composer's estimate. */
    public function preview(int $id)
    {
        $ctx = self::contextFor($id, (int) Auth::id());
        if (! $ctx) {
            return $this->jsonFail('That report is gone.', 404);
        }

        return $this->jsonOk('ok', ['data' => [
            'id' => $id,
            'title' => $ctx['title'],
            'tokens' => (int) ceil(mb_strlen($ctx['text']) / 4),
        ]]);
    }

    /* =============================== EXPENSES =========================== */

    public function expensesPage(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $this->guardReports($schedule);
        $schedule->load(['lots', 'activities.lots']);

        // Each lot's own count word, so the day-count range can say DAS,
        // DAT, DAP or a tree's age the way the chosen lots keep it.
        return view('sm.expenses-report', ['schedule' => $schedule, 'lotCounters' => $this->lotCounters($schedule)]);
    }

    /**
     * The count each lot keeps, as a word: AGE for a tree (months since
     * planting), DAT once the lot has a transplant (its column or a ticked
     * transplant activity -- LotCalendar::effectiveAnchors), else the lot's
     * own day type, else the season's.
     *
     * @return array<int, string>
     */
    private function lotCounters(\App\Models\AsCroppingSchedule $schedule, ?array $transplant = null): array
    {
        if ($transplant === null) {
            [, $transplant] = \App\Support\LotCalendar::effectiveAnchors($schedule);
        }
        $out = [];
        foreach ($schedule->lots as $lot) {
            $type = strtoupper((string) ($lot->dayType ?: ''));
            if ($type === 'TREE' || \App\Support\CropStages::isPerennial($lot->crop ?? null)) {
                $out[$lot->id] = 'AGE';
            } elseif (isset($transplant[$lot->id]) && $type !== 'DAP') {
                $out[$lot->id] = 'DAT';
            } else {
                $out[$lot->id] = $type !== '' ? $type : strtoupper((string) ($schedule->dayType ?: 'DAS'));
            }
        }

        return $out;
    }

    /**
     * Every peso of the season, one row each, filtered server-side.
     *
     * Categories: materials (activity item lines that are not services),
     * labor (the labor report's own arithmetic, per activity), services
     * (service lines + a service activity's own price), expense (the day
     * book), purchase (hand stock-ins that carry a price — activity-linked
     * purchase moves are EXCLUDED, their material line already counts).
     * Income (the day book's) rides beside them so the page can say a net.
     */
    public function expensesData(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $this->guardReports($schedule);

        $from = $this->isoOrNull($request->query('from'));
        $to = $this->isoOrNull($request->query('to'));
        $lotIds = array_values(array_filter(array_map('intval', (array) $request->query('lotIds', []))));
        $cats = array_values(array_intersect((array) $request->query('cats', []),
            ['materials', 'labor', 'services', 'expense', 'purchase', 'income']));
        $invKind = (string) $request->query('invKind', '');
        $status = in_array($request->query('status'), ['done', 'pending'], true) ? $request->query('status') : 'all';

        $schedule->load(['lots', 'activities.items', 'activities.workers', 'activities.lots', 'dayExpenses', 'dayIncomes']);
        $invItems = AsInventoryItem::where('croppingScheduleId', $schedule->id)->get()->keyBy('id');

        // A stretch of the crop's own clock (the labor report's "DAS 0 to
        // 45"), read on EACH ROW'S OWN LOTS: every lot counts from its own
        // anchor -- day zero, its transplant for a DAT lot, planting in
        // months for a tree -- and a row is in when any of its lots (the
        // chosen ones, if lots are chosen) reads inside the window. A row
        // with no lot, or no anchored lot, has no count to read, so it sits
        // outside a day-count range -- the labor report's rule.
        $hasDayMin = is_numeric($request->query('dayMin'));
        $hasDayMax = is_numeric($request->query('dayMax'));
        $dayMin = $hasDayMin ? (int) $request->query('dayMin') : PHP_INT_MIN;
        $dayMax = $hasDayMax ? (int) $request->query('dayMax') : PHP_INT_MAX;
        $dayOn = $hasDayMin || $hasDayMax;
        $inDays = fn (array $r) => true;
        if ($dayOn) {
            [$dz, $tp] = \App\Support\LotCalendar::effectiveAnchors($schedule);
            $counters = $this->lotCounters($schedule, $tp);
            $lotsById = $schedule->lots->keyBy('id');
            $clock = function (int $lotId, string $on) use ($dz, $tp, $counters, $lotsById): ?int {
                $lot = $lotsById->get($lotId);
                if (! $lot) return null;
                $day = \Carbon\Carbon::parse($on)->startOfDay();
                $counter = $counters[$lotId] ?? 'DAS';
                if ($counter === 'AGE') {
                    if (! $lot->treePlantedAt) return null;
                    $planted = \Carbon\Carbon::parse($lot->treePlantedAt)->startOfDay();

                    return (int) floor($planted->diffInMonths($day, false));
                }
                $anchor = ($counter === 'DAT' && isset($tp[$lotId])) ? $tp[$lotId] : ($dz[$lotId] ?? null);
                if (! $anchor) return null;

                return (int) round($anchor->copy()->startOfDay()->diffInDays($day, false));
            };
            $inDays = function (array $r) use ($clock, $lotIds, $dayMin, $dayMax): bool {
                if (! $r['on'] || ! $r['lotIds']) return false;
                $considered = $lotIds ? array_values(array_intersect($r['lotIds'], $lotIds)) : $r['lotIds'];
                foreach ($considered as $lid) {
                    $d = $clock((int) $lid, $r['on']);
                    if ($d !== null && $d >= $dayMin && $d <= $dayMax) return true;
                }

                return false;
            };
        }

        $rows = [];
        $push = function (array $r) use (&$rows, $from, $to, $lotIds, $cats, $status, $inDays) {
            if ($from && (! $r['on'] || $r['on'] < $from)) return;
            if ($to && (! $r['on'] || $r['on'] > $to)) return;
            if ($lotIds && ! array_intersect($lotIds, $r['lotIds'])) return;
            if (! $inDays($r)) return;
            if ($cats && ! in_array($r['cat'], $cats, true)) return;
            if ($status !== 'all' && $r['done'] !== null && $r['done'] !== ($status === 'done')) return;
            $rows[] = $r;
        };

        foreach ($schedule->activities as $a) {
            $on = $a->targetDate?->format('Y-m-d');
            $aLots = $a->lots->pluck('id')->map(fn ($i) => (int) $i)->all();
            $done = (bool) $a->isDone;

            foreach ($a->items as $it) {
                $amount = round((float) $it->unitPrice * (float) $it->quantity, 2);
                $inv = $it->inventoryItemId ? $invItems->get((int) $it->inventoryItemId) : null;
                // '__none' = no inventory: the shed's own stock stays out
                // (linked material lines, stock buys); hand-typed materials,
                // services, labor and the day book stay in.
                if ($invKind === '__none') {
                    if ($inv) continue;
                } elseif ($invKind !== '' && (! $inv || $inv->kind !== $invKind)) {
                    if ($it->itemType !== 'service') continue;
                }
                $row = [
                    'on' => $on, 'done' => $done, 'lotIds' => $aLots,
                    'label' => (string) $it->itemName,
                    'meta' => rtrim(rtrim(number_format((float) $it->quantity, 2), '0'), '.') . ($it->unitOfMeasure ? ' ' . $it->unitOfMeasure : '') . ' · ' . $a->activityTitle,
                    'amount' => $amount,
                    'invKind' => $inv?->kind,
                ];
                if ($it->itemType === 'service') {
                    if ($invKind !== '' && $invKind !== '__none') continue;   // services carry no inventory kind
                    $push($row + ['cat' => 'services']);
                } else {
                    $push($row + ['cat' => 'materials']);
                }
            }

            if ($invKind === '' || $invKind === '__none') {
                if ((float) ($a->servicePrice ?? 0) > 0) {
                    $push([
                        'on' => $on, 'done' => $done, 'lotIds' => $aLots, 'cat' => 'services',
                        'label' => $a->activityTitle, 'meta' => 'Service activity',
                        'amount' => round((float) $a->servicePrice, 2),
                    ]);
                }

                // Labor — the labor report's own arithmetic, per activity.
                $units = match ($a->timeRequired) { 'whole' => 2, 'half' => 1, default => 0 };
                if ($units > 0 && $a->workers->count()) {
                    $start = $a->targetDate;
                    $end = $a->targetEndDate ?: $a->targetDate;
                    $rangeDays = ($start && $end) ? max(1, (int) $start->diffInDays($end) + 1) : 1;
                    $labor = 0.0;
                    foreach ($a->workers as $w) {
                        $labor += (float) $w->costPerHalfDay * $units * $rangeDays;
                    }
                    if ($labor > 0) {
                        $push([
                            'on' => $on, 'done' => $done, 'lotIds' => $aLots, 'cat' => 'labor',
                            'label' => 'Labor: ' . $a->activityTitle,
                            'meta' => $a->workers->count() . ' ' . ($a->workers->count() === 1 ? 'worker' : 'workers')
                                . ' · ' . ($a->timeRequired === 'whole' ? 'whole day' : 'half day')
                                . ($rangeDays > 1 ? ' × ' . $rangeDays . ' days' : ''),
                            'amount' => round($labor, 2),
                        ]);
                    }
                }
            }
        }

        if ($invKind === '' || $invKind === '__none') {
            foreach ($schedule->dayExpenses as $e) {
                $push([
                    'on' => $e->expenseDate ? substr((string) $e->expenseDate, 0, 10) : null,
                    'done' => null, 'lotIds' => [], 'cat' => 'expense',
                    'label' => trim((string) $e->note) !== '' ? (string) $e->note : 'Extra expense',
                    'meta' => 'Day book', 'amount' => round((float) $e->amount, 2),
                ]);
            }
            foreach ($schedule->dayIncomes as $i) {
                $push([
                    'on' => $i->incomeDate ? substr((string) $i->incomeDate, 0, 10) : null,
                    'done' => null, 'lotIds' => [], 'cat' => 'income',
                    'label' => trim((string) $i->title) !== '' ? (string) $i->title : 'Income',
                    'meta' => 'Day book', 'amount' => round((float) $i->amount, 2),
                ]);
            }
        }

        // Hand purchases: stock-ins and opening counts with a price and NO
        // activity — a linked purchase's material line is already in the
        // list above. The same reading the board's day cash makes
        // (AsInventoryMove::cost), so the two agree to the peso.
        $buys = AsInventoryMove::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->whereIn('reason', [AsInventoryMove::IN, AsInventoryMove::OPEN])
            ->whereNull('activityId')->get();
        foreach ($buys as $m) {
            $item = $invItems->get((int) $m->itemId);
            $amount = $m->cost($item);
            if ($amount <= 0) continue;
            if ($invKind === '__none') continue;
            if ($invKind !== '' && (! $item || $item->kind !== $invKind)) continue;
            $price = $m->unitPrice !== null ? (float) $m->unitPrice : (float) ($item?->unitPrice ?? 0);
            $push([
                'on' => $m->happenedOn ? substr((string) $m->happenedOn, 0, 10) : null,
                'done' => null, 'lotIds' => [], 'cat' => 'purchase',
                'label' => ($m->reason === AsInventoryMove::OPEN ? 'Opening stock: ' : 'Stock bought: ') . ($item?->name ?? 'item #' . $m->itemId),
                'meta' => ($item ? $item->say((float) $m->delta) : rtrim(rtrim(number_format((float) $m->delta, 3), '0'), '.'))
                    . ' at ' . \App\Support\Region::symbol() . number_format($price, 2) . ' each',
                'amount' => $amount,
                'invKind' => $item?->kind,
            ]);
        }

        usort($rows, fn ($x, $y) => strcmp($y['on'] ?? '', $x['on'] ?? ''));

        // Aggregates over the FILTERED rows: what you see is what is added.
        $totals = ['materials' => 0.0, 'labor' => 0.0, 'services' => 0.0, 'expense' => 0.0, 'purchase' => 0.0, 'income' => 0.0];
        $perMonth = [];
        $perLot = [];
        foreach ($rows as $r) {
            $totals[$r['cat']] += $r['amount'];
            $ym = $r['on'] ? substr($r['on'], 0, 7) : '—';
            $perMonth[$ym] = $perMonth[$ym] ?? ['spend' => 0.0, 'income' => 0.0];
            $perMonth[$ym][$r['cat'] === 'income' ? 'income' : 'spend'] += $r['amount'];
            if ($r['cat'] !== 'income') {
                $ls = $r['lotIds'] ?: [0];
                $share = $r['amount'] / count($ls);
                foreach ($ls as $lid) {
                    $perLot[$lid] = round(($perLot[$lid] ?? 0) + $share, 2);
                }
            }
        }
        ksort($perMonth);
        $spend = $totals['materials'] + $totals['labor'] + $totals['services'] + $totals['expense'] + $totals['purchase'];

        return $this->jsonOk('ok', ['data' => [
            'scheduleTitle' => $schedule->title,
            'rows' => array_slice($rows, 0, 800),
            'rowCount' => count($rows),
            'totals' => array_map(fn ($v) => round($v, 2), $totals),
            'spend' => round($spend, 2),
            'net' => round($totals['income'] - $spend, 2),
            'perMonth' => $perMonth,
            'perLot' => $perLot,
            'filters' => [
                'dayMin' => $hasDayMin ? $dayMin : null,
                'dayMax' => $hasDayMax ? $dayMax : null,
                'from' => $from,
                'to' => $to,
            ],
        ]]);
    }

    /* ================================ PROFIT ============================ */

    public function profitPage(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $this->guardReports($schedule);

        return view('sm.profit-report', ['schedule' => $schedule]);
    }

    /**
     * Money-out against money-in, per lot and whole.
     *
     * Costs are the Expenses Report's buckets (materials, labor, services,
     * day expenses, hand stock buys), shared evenly across an activity's
     * lots; the season-wide ones sit in a General bucket. Revenue is the
     * post-harvest module's yield rows × their prices, per lot, plus the
     * day book's income (General). The report refuses to pretend: no yield
     * rows means no profit report, only the checklist of what is missing.
     */
    public function profitData(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $this->guardReports($schedule);

        return $this->jsonOk('ok', ['data' => $this->profitFacts($schedule)]);
    }

    /** The profit arithmetic itself, reusable by the AI reports. */
    /** profitFacts per schedule, once a request: the season report asks for it three times. */
    private array $pfMemo = [];

    private function profitFacts(\App\Models\AsCroppingSchedule $schedule): array
    {
        return $this->pfMemo[$schedule->id] ??= $this->profitFactsFresh($schedule);
    }

    private function profitFactsFresh(\App\Models\AsCroppingSchedule $schedule): array
    {
        $schedule->load(['activities.items', 'activities.workers', 'activities.lots', 'dayExpenses', 'dayIncomes', 'lots']);

        $harvests = \App\Models\AsSchedulePostHarvest::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->get();
        $yieldRows = $harvests->filter(fn ($h) => $h->yieldAmount !== null && (float) $h->yieldAmount > 0)->values();

        /* ---- validations: what the report cannot honestly be made without,
         *      and what it will grumble about but still compute. ---- */
        $undone = $schedule->activities->filter(fn ($a) => ! $a->isDone);
        $blockers = [];
        $warnings = [];
        if ($yieldRows->isEmpty()) {
            $blockers[] = 'No harvest is recorded in Observations yet, and a profit report needs it. First add a Yield observation with the amount and its selling price.';
        }
        if ($undone->count() > 0) {
            $warnings[] = $undone->count() . ' ' . ($undone->count() === 1 ? 'activity is' : 'activities are')
                . ' not ticked done. Their planned costs are still counted, so the numbers show the full plan, not only what was done.';
        }
        $unpriced = $yieldRows->filter(fn ($h) => $h->pricePerUnit === null || (float) $h->pricePerUnit <= 0);
        if ($unpriced->count() > 0) {
            $warnings[] = $unpriced->count() . ' yield ' . ($unpriced->count() === 1 ? 'row has' : 'rows have')
                . ' no selling price. That harvest counts as ' . \App\Support\Region::symbol() . '0 income until you add a price.';
        }

        /* ---- crop-day sanity, per lot: how long the crop actually ran
         *      against what the catalogue (or the lot itself) expects. ---- */
        $lastDone = $schedule->activities->filter(fn ($a) => $a->isDone && $a->targetDate)
            ->max(fn ($a) => $a->targetDate->format('Y-m-d'));
        foreach ($schedule->lots as $lot) {
            if (! $lot->crop || ! $lot->dayZeroDate) continue;
            $expected = (int) ($lot->daysToMaturity ?: (\App\Support\CropCatalog::CROPS[$lot->crop]['maturity'] ?? 0));
            if ($expected <= 0) continue;
            $endIso = $lastDone ?: now('Asia/Manila')->toDateString();
            $ran = (int) \Carbon\Carbon::parse((string) $lot->dayZeroDate)->diffInDays(\Carbon\Carbon::parse($endIso), false);
            if ($ran <= 0) continue;
            if ($ran > $expected + 15) {
                $warnings[] = $lot->lotName . ': the crop ran about ' . $ran . ' days against a typical ' . $expected
                    . ' to maturity. Delays (weather, replanting, late harvest) add to costs, so it is worth a look.';
            } elseif ($schedule->isLocked() && $ran < max(1, $expected - 15)) {
                $warnings[] = $lot->lotName . ': the season closed at about ' . $ran . ' days against a typical '
                    . $expected . ' to maturity. Harvesting early usually means some harvest was left in the field.';
            }
        }

        /* ---- costs, shared across each activity's lots ---- */
        $invItems = AsInventoryItem::where('croppingScheduleId', $schedule->id)->get()->keyBy('id');
        $costCats = ['materials' => 0.0, 'labor' => 0.0, 'services' => 0.0, 'expense' => 0.0, 'purchase' => 0.0];
        $lotCost = [];   // lotId (0 = general) => cost
        $spread = function (float $amount, array $lotIds) use (&$lotCost) {
            $ls = $lotIds ?: [0];
            $share = $amount / count($ls);
            foreach ($ls as $lid) {
                $lotCost[$lid] = ($lotCost[$lid] ?? 0) + $share;
            }
        };
        foreach ($schedule->activities as $a) {
            $aLots = $a->lots->pluck('id')->map(fn ($i) => (int) $i)->all();
            foreach ($a->items as $it) {
                $amount = round((float) $it->unitPrice * (float) $it->quantity, 2);
                if ($amount <= 0) continue;
                $costCats[$it->itemType === 'service' ? 'services' : 'materials'] += $amount;
                $spread($amount, $aLots);
            }
            if ((float) ($a->servicePrice ?? 0) > 0) {
                $costCats['services'] += (float) $a->servicePrice;
                $spread((float) $a->servicePrice, $aLots);
            }
            $units = match ($a->timeRequired) { 'whole' => 2, 'half' => 1, default => 0 };
            if ($units > 0 && $a->workers->count()) {
                $start = $a->targetDate;
                $end = $a->targetEndDate ?: $a->targetDate;
                $rangeDays = ($start && $end) ? max(1, (int) $start->diffInDays($end) + 1) : 1;
                $labor = 0.0;
                foreach ($a->workers as $w) {
                    $labor += (float) $w->costPerHalfDay * $units * $rangeDays;
                }
                if ($labor > 0) { $costCats['labor'] += $labor; $spread($labor, $aLots); }
            }
        }
        foreach ($schedule->dayExpenses as $e) {
            $costCats['expense'] += (float) $e->amount;
            $spread((float) $e->amount, []);
        }
        $buys = AsInventoryMove::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->whereIn('reason', [AsInventoryMove::IN, AsInventoryMove::OPEN])
            ->whereNull('activityId')->get();
        $buyItems = \App\Models\AsInventoryItem::whereIn('id', $buys->pluck('itemId')->unique()->all() ?: [0])->get()->keyBy('id');
        foreach ($buys as $m) {
            $amt = $m->cost($buyItems->get((int) $m->itemId));
            if ($amt <= 0) continue;
            $costCats['purchase'] += $amt;
            $spread($amt, []);
        }
        $totalCost = round(array_sum($costCats), 2);

        /* ---- revenue, per lot ---- */
        $lotRevenue = [];   // lotId (0 = general) => amount
        $lotYield = [];     // lotId => [unit => qty]
        foreach ($yieldRows as $h) {
            $lid = (int) ($h->lotId ?: 0);
            $rev = round((float) $h->yieldAmount * (float) ($h->pricePerUnit ?? 0), 2);
            $lotRevenue[$lid] = ($lotRevenue[$lid] ?? 0) + $rev;
            $unit = $h->yieldUnit ?: 'units';
            $lotYield[$lid][$unit] = ($lotYield[$lid][$unit] ?? 0) + (float) $h->yieldAmount;
        }
        $dayIncome = round((float) $schedule->dayIncomes->sum('amount'), 2);
        if ($dayIncome > 0) {
            $lotRevenue[0] = ($lotRevenue[0] ?? 0) + $dayIncome;
        }
        $totalRevenue = round(array_sum($lotRevenue), 2);

        /* ---- per-lot cards ---- */
        $lots = [];
        $lotIds = array_unique(array_merge(array_keys($lotCost), array_keys($lotRevenue)));
        sort($lotIds);
        foreach ($lotIds as $lid) {
            $lot = $lid === 0 ? null : $schedule->lots->firstWhere('id', $lid);
            $cost = round($lotCost[$lid] ?? 0, 2);
            $rev = round($lotRevenue[$lid] ?? 0, 2);
            $yields = collect($lotYield[$lid] ?? [])->map(fn ($q, $u) => rtrim(rtrim(number_format($q, 2), '0'), '.') . ' ' . $u)->values()->all();
            $yieldQty = count($lotYield[$lid] ?? []) === 1 ? (float) array_values($lotYield[$lid])[0] : null;
            $lots[] = [
                'id' => $lid,
                'name' => $lid === 0 ? 'General (whole season)' : ($lot?->lotName ?? 'Lot #' . $lid),
                'crop' => $lid === 0 ? null : ($lot?->crop ? (\App\Support\CropStages::label($lot->crop) ?: $lot->crop) : null),
                'size' => $lot && $lot->lotSize ? rtrim(rtrim(number_format((float) $lot->lotSize, 2), '0'), '.') . ' ' . ($lot->lotSizeUnit ?: '') : null,
                'yield' => $yields,
                'revenue' => $rev,
                'cost' => $cost,
                'profit' => round($rev - $cost, 2),
                'margin' => $rev > 0 ? round((($rev - $cost) / $rev) * 100, 1) : null,
                'costPerUnit' => ($yieldQty && $yieldQty > 0 && $cost > 0) ? round($cost / $yieldQty, 2) : null,
                'unit' => $yieldQty ? array_keys($lotYield[$lid])[0] : null,
            ];
        }

        return [
            'scheduleTitle' => $schedule->title,
            'status' => $schedule->status,
            'blocked' => $blockers !== [],
            'blockers' => $blockers,
            'warnings' => $warnings,
            'revenue' => $totalRevenue,
            'dayIncome' => $dayIncome,
            'cost' => $totalCost,
            'costCats' => array_map(fn ($v) => round($v, 2), $costCats),
            'profit' => round($totalRevenue - $totalCost, 2),
            'margin' => $totalRevenue > 0 ? round((($totalRevenue - $totalCost) / $totalRevenue) * 100, 1) : null,
            'lots' => $lots,
            'harvestNotes' => $harvests->where('category', '!=', 'yield')->count(),
        ];
    }

    private function isoOrNull($v): ?string
    {
        $v = (string) $v;

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }

    /* ========================= ANEE'S OWN REPORTS ======================= */

    /** The flat prices, said before anything is spent — the owner set them. */
    public const PRICE_SEASON = AiPrices::DEFAULTS['season'];
    public const PRICE_SOFAR = AiPrices::DEFAULTS['sofar'];

    public function seasonPage(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $this->guardReports($schedule);
        $schedule->load('lots');

        return view('sm.anee-report', ['schedule' => $schedule, 'kind' => 'season']);
    }

    public function sofarPage(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $this->guardReports($schedule);
        $schedule->load('lots');

        return view('sm.anee-report', ['schedule' => $schedule, 'kind' => 'sofar']);
    }

    /** Readiness: what blocks a run, what only footnotes it, and the wallet. */
    public function aneeStatus(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $kind = $request->query('kind') === 'sofar' ? 'sofar' : 'season';
        $payer = $this->aneePayer();
        $settings = AiSetting::current();
        $credits = app(AiCreditService::class);

        [$blockers, $warnings] = $this->aneeChecks($schedule, $kind);
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            $blockers[] = $payer->canUseAi()
                ? 'Anee is not switched on yet. Please check back soon.'
                : Tier::aneeNeeds($payer, 'This report');
        }

        return $this->jsonOk('ok', ['data' => [
            'kind' => $kind,
            'price' => AiPrices::of($kind === 'sofar' ? 'sofar' : 'season'),
            'balance' => round($credits->balance($payer->id), 2),
            'unlimited' => $credits->unlimited((int) $payer->id),
            'blockers' => $blockers,
            'warnings' => $warnings,
            'ready' => $blockers === [],
        ]]);
    }

    /**
     * What honestly stops a run, and what only rides as a footnote.
     * The season read needs a FINISHED season; so-far only needs a season
     * with something in it.
     */
    private function aneeChecks(\App\Models\AsCroppingSchedule $schedule, string $kind): array
    {
        $schedule->loadMissing('activities');
        $blockers = [];
        $warnings = [];
        $undone = $schedule->activities->filter(fn ($a) => ! $a->isDone)->count();
        $harvestCount = \App\Models\AsSchedulePostHarvest::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->count();

        if ($schedule->activities->isEmpty()) {
            $blockers[] = 'The season has no activities yet, so there is nothing to look at.';
        }
        if ($kind === 'season') {
            if (! $schedule->isLocked()) {
                $blockers[] = 'The season is not closed yet. Close it in the Hub first, because this report looks back at a finished season.';
            }
            if ($undone > 0) {
                $blockers[] = $undone . ' ' . ($undone === 1 ? 'activity is' : 'activities are') . ' not ticked done. Tick what was done (or delete what was not) so the report is true.';
            }
            if ($harvestCount === 0) {
                $blockers[] = 'No observations yet. Add at least the yield in Observations, because the harvest is half the story.';
            }
        } else {
            if ($undone === 0 && $schedule->activities->isNotEmpty()) {
                $warnings[] = 'Everything is ticked done. The full Season Report may help you more than a look at the season so far.';
            }
        }

        return [$blockers, $warnings];
    }

    public function aneeGenerate(Request $request)
    {
        $schedule = $this->schedule($request->input('scheduleId'));
        $this->guardReports($schedule);
        $kind = $request->input('kind') === 'sofar' ? 'sofar' : 'season';
        $lotId = (int) $request->input('lotId', 0);
        $payer = $this->aneePayer();
        $settings = AiSetting::current();
        $credits = app(AiCreditService::class);

        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->jsonFail($payer->canUseAi()
                ? 'Anee is not switched on yet. Please check back soon.'
                : Tier::aneeNeeds($payer, 'This report'), 403);
        }
        [$blockers] = $this->aneeChecks($schedule, $kind);
        if ($blockers !== []) {
            return $this->jsonFail('Not ready: ' . implode(' ', $blockers), 422);
        }

        $price = AiPrices::of($kind === 'sofar' ? 'sofar' : 'season');
        $balance = $credits->balance($payer->id);
        if ($balance < $price && ! $credits->unlimited((int) $payer->id)) {
            return $this->jsonFail('You need ' . $price . ' credits for this report and have '
                . number_format((int) floor($balance)) . '.', 402, ['outOfCredits' => true]);
        }

        /* One in flight at a time — a double press must not buy two. */
        $standing = AsFarmReport::where('userId', Auth::id())->where('kind', $kind)
            ->where('status', 'pending')->where('deleteStatus', 1)
            ->where('created_at', '>', now()->subMinutes(10))
            ->orderByDesc('id')->first();
        if ($standing) {
            return $this->jsonOk('Already working on it.', ['data' => ['pending' => true, 'id' => $standing->id]]);
        }

        $lot = $lotId ? $schedule->lots()->where('id', $lotId)->first() : null;
        // Short: the shelf already sits inside the season, so the title says
        // the kind, the lot when one was asked for, and the day.
        $title = ($kind === 'sofar' ? 'So far' : 'Season report')
            . ($lot ? ' · ' . $lot->lotName : '') . ' · ' . now('Asia/Manila')->format('M j, Y');
        $row = AsFarmReport::create([
            'userId' => Auth::id(),
            'croppingScheduleId' => $schedule->id,
            'kind' => $kind,
            'title' => mb_substr($title, 0, 190),
            'params' => ['lotId' => $lotId ?: null],
            'status' => 'pending',
            'deleteStatus' => 1,
        ]);

        // The season report's graphs are the app's arithmetic, worked out
        // once here: Anee reads them in words and the report keeps them.
        $facts = null;
        try {
            $facts = $kind === 'season' ? $this->seasonFacts($schedule) : $this->sofarFacts($schedule, $lot);
        } catch (\Throwable $e) {
            report($e);
        }
        $prompt = $this->aneePrompt($schedule, $kind, $lot, $facts);

        /* The gateway must not wait on the model — when-to-plant's walk. */
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => [
                'pending' => true, 'id' => $row->id,
            ]])->send();
            fastcgi_finish_request();
            $this->runAneeJob($row->id, (int) $payer->id, $settings, $prompt, $price, $facts);
            exit;
        }

        @set_time_limit(300);
        $this->runAneeJob($row->id, (int) $payer->id, $settings, $prompt, $price, $facts);

        return $this->aneeJob($row->id);
    }

    /** The model call and the charge, off the request's clock. */
    private function runAneeJob(int $id, int $payerId, AiSetting $settings, string $prompt, int $price, ?array $facts = null): void
    {
        $ai = app(AiClient::class);
        $credits = app(AiCreditService::class);
        try {
            // The season report says more now (a reason per score, the money,
            // the harvest against a typical farm, the moments, the savings).
            $maxOut = 8000;
            $result = $ai->askForJson($settings, $prompt, $maxOut, fn (string $t) => $this->parseAneeReport($t));
            $report = $result['data'];
            if ($report === null) {
                Log::warning('anee-report: unparsable answer', ['head' => mb_substr((string) $result['text'], 0, 400)]);
                throw new \RuntimeException($result['error'] ?? 'The report could not be read. Nothing was charged. Please try again.');
            }

            $row = AsFarmReport::find($id);
            if ($facts !== null) {
                $report['facts'] = $facts;
            } elseif ($row->kind === 'sofar') {
                // The graphs draw the app's own arithmetic, not the model's.
                try {
                    $sch = \App\Models\AsCroppingSchedule::find($row->croppingScheduleId);
                    $lotId = (int) (($row->params ?? [])['lotId'] ?? 0);
                    $lotRow = $lotId && $sch ? $sch->lots()->where('id', $lotId)->first() : null;
                    if ($sch) $report['facts'] = $this->sofarFacts($sch, $lotRow);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            $note = AiUsage::record($row->kind === 'sofar' ? 'sofar' : 'season', (int) $row->userId, $payerId, $id, $settings, $result, (int) $price);
            $credits->chargeAllowingNegative($payerId, (float) $price,
                mb_substr(($row->kind === 'sofar' ? 'Analyze So Far report' : 'Anee Season Report') . ': ' . mb_substr((string) $row->title, 0, 120) . $note, 0, 250));

            $row->update([
                'report' => $report,
                'body' => mb_substr($this->aneeBodyText($row->kind, $row->title, $report), 0, 60000),
                'credits' => $price,
                'status' => 'ready',
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            report($e);
            AsFarmReport::where('id', $id)->update([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 500),
                'deleteStatus' => 0,   // failed runs delist themselves
            ]);
        }
    }

    /** Where a job stands — polled by the page until ready or failed. */
    public function aneeJob(int $id)
    {
        $r = AsFarmReport::where('userId', Auth::id())->where('id', $id)->first();
        if (! $r) {
            return $this->jsonFail('That report is gone.', 404);
        }
        if ($r->status === 'failed') {
            return $this->jsonFail($r->error ?: 'The report failed. Nothing was charged.', 422);
        }
        if ($r->status !== 'ready') {
            return $this->jsonOk('Working…', ['data' => ['pending' => true, 'id' => $r->id, 'status' => 'pending']]);
        }

        return $this->jsonOk('ok', ['data' => [
            'status' => 'ready', 'id' => $r->id, 'title' => $r->title,
            'report' => $r->report, 'credits' => (float) $r->credits,
            'kind' => $r->kind, 'savedId' => $r->id,
        ]]);
    }

    /**
     * The saved shelf, per kind and schedule — the whole team's rows, not
     * only the asker's. A worker given view access to Reports reads what
     * the owner saved; that is the point of saving. Renaming and deleting
     * stay with the row's author (see aneeMeta / aneeDelete).
     */
    public function aneeList(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $kind = in_array($request->query('kind'), AsFarmReport::KINDS, true) ? $request->query('kind') : 'season';
        $rows = AsFarmReport::where('croppingScheduleId', $schedule->id)
            ->where('kind', $kind)->where('status', 'ready')->where('deleteStatus', 1)
            ->orderByDesc('id')->limit(30)
            ->get(['id', 'userId', 'title', 'description', 'credits', 'created_at']);

        return $this->jsonOk('ok', ['data' => ['rows' => $rows->map(fn ($r) => [
            'id' => $r->id, 'title' => $r->title, 'description' => $r->description,
            'credits' => (float) $r->credits,
            'when' => $r->created_at?->format('M j, Y g:i A'),
            'mine' => (int) $r->userId === (int) Auth::id(),
        ])->values()]]);
    }

    /**
     * Rename a saved report, describe it, retie its tags. The shelf stops
     * being a list of machine-written titles.
     */
    public function aneeMeta(Request $request)
    {
        $r = AsFarmReport::where('userId', Auth::id())
            ->where('id', (int) $request->input('id'))
            ->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->jsonFail('That saved report no longer exists.', 404);
        }
        $schedule = $this->schedule($r->croppingScheduleId);

        $v = Validator::make($request->all(), [
            'title' => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
            'tags' => 'nullable',
        ]);
        if ($v->fails()) {
            return $this->jsonFail($v->errors()->first(), 422);
        }

        $r->update([
            'title' => trim((string) $request->input('title')),
            'description' => filled($request->input('description')) ? trim((string) $request->input('description')) : null,
        ]);

        if ($request->has('tags')) {
            \App\Support\ScheduleTags::sync($schedule, 'report', (int) $r->id, $request->input('tags', []));
        }

        return $this->jsonOk('Report updated.', ['data' => [
            'id' => (int) $r->id, 'title' => $r->title, 'description' => $r->description,
        ]]);
    }

    /** One saved report, whole. Anyone who can stand on the schedule reads it. */
    public function aneeOne(int $id)
    {
        $r = AsFarmReport::where('id', $id)
            ->where('status', 'ready')->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->jsonFail('That report is gone.', 404);
        }
        // Throws if the asker has no standing on the report's schedule.
        $schedule = $this->schedule($r->croppingScheduleId);

        // A season report written before it carried its graphs gets them
        // the first time it is opened, worked out from the closed season's
        // records and kept with it from then on.
        if ($r->kind === 'season' && (int) (($r->report['facts'] ?? [])['v'] ?? 0) < 2) {
            try {
                $r->report = array_merge((array) $r->report, ['facts' => $this->seasonFacts($schedule)]);
                $r->save();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $this->jsonOk('ok', ['data' => [
            'id' => $r->id, 'title' => $r->title, 'report' => $r->report,
            // What the report was narrowed to, so a saved one can say so.
            'params' => $r->params,
            'body' => $r->body,
            'credits' => (float) $r->credits, 'kind' => $r->kind,
            'mine' => (int) $r->userId === (int) Auth::id(),
        ]]);
    }

    public function aneeDelete(int $id)
    {
        AsFarmReport::where('userId', Auth::id())->where('id', $id)->update(['deleteStatus' => 0]);

        return $this->jsonOk('Report removed.');
    }

    /* ========================= VIEW AS PROTOCOL ========================= */

    public function protocolPage(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $this->guardReports($schedule);
        $schedule->load('lots');

        return view('sm.protocol-report', ['schedule' => $schedule]);
    }

    /**
     * A lot's season, said as a recipe: every DONE activity keyed to the
     * lot's own day count, with materials and crew, ending on the yield it
     * produced. Saved to the shelf the moment it is made — a protocol that
     * worked is exactly the thing to keep and to hand to Anee.
     */
    public function protocolGenerate(Request $request)
    {
        $schedule = $this->schedule($request->input('scheduleId'));
        $lot = $schedule->lots()->where('id', (int) $request->input('lotId'))->first();
        if (! $lot) {
            return $this->jsonFail('Pick a lot first.', 422);
        }
        $schedule->load(['activities.items', 'activities.workers', 'activities.lots']);

        $dayType = $lot->dayType ?: ($schedule->dayType ?: 'DAS');
        // Day zero the way the activities board counts it: the lot's own
        // date, or the earliest activity ticked "this is day zero" that
        // covers this lot — whichever is earliest. Many farms never fill
        // the lot column and anchor purely by that tick.
        $zero = $lot->dayZeroDate ? \Carbon\Carbon::parse((string) $lot->dayZeroDate) : null;
        foreach ($schedule->activities as $a) {
            if (! $a->isDayZero || ! $a->targetDate) continue;
            if (! $a->lots->contains('id', $lot->id)) continue;
            $d = \Carbon\Carbon::parse((string) $a->targetDate->format('Y-m-d'));
            if (! $zero || $d->lt($zero)) $zero = $d;
        }

        $steps = [];
        $skippedPlanned = 0;
        foreach ($schedule->activities as $a) {
            $touches = $a->lots->isEmpty() || $a->lots->contains('id', $lot->id);
            if (! $touches) continue;
            if (! $a->isDone) { $skippedPlanned++; continue; }
            $day = ($zero && $a->targetDate) ? (int) $zero->diffInDays($a->targetDate, false) : null;
            $steps[] = [
                'day' => $day,
                'dayLabel' => $day === null ? null : $dayType . ' ' . ($day > 0 ? '+' : '') . $day,
                'date' => $a->targetDate?->format('M j'),
                'endDate' => ($a->targetEndDate && $a->targetDate && ! $a->targetEndDate->equalTo($a->targetDate))
                    ? $a->targetEndDate->format('M j') : null,
                'title' => (string) $a->activityTitle,
                'type' => (string) ($a->activityType ?: ''),
                'time' => $a->timeRequired === 'whole' ? 'whole day' : ($a->timeRequired === 'half' ? 'half day' : null),
                'crew' => $a->workers->count(),
                'wholeFarm' => $a->lots->isEmpty(),
                'materials' => $a->items->map(fn ($it) => trim(
                    rtrim(rtrim(number_format((float) $it->quantity, 2), '0'), '.')
                    . ($it->unitOfMeasure ? ' ' . $it->unitOfMeasure : '') . ' ' . $it->itemName
                ))->values()->all(),
            ];
        }
        usort($steps, function ($x, $y) {
            if ($x['day'] === null && $y['day'] === null) return 0;
            if ($x['day'] === null) return 1;
            if ($y['day'] === null) return -1;

            return $x['day'] <=> $y['day'];
        });

        // The payoff line: what this recipe actually produced.
        $yields = \App\Models\AsSchedulePostHarvest::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->where('lotId', $lot->id)
            ->whereNotNull('yieldAmount')->get()
            ->map(fn ($h) => rtrim(rtrim(number_format((float) $h->yieldAmount, 2), '0'), '.')
                . ' ' . ($h->yieldUnit ?: '')
                . ($h->pricePerUnit ? ' sold at ' . \App\Support\Region::symbol() . number_format((float) $h->pricePerUnit, 2) : ''))
            ->values()->all();

        $dates = array_values(array_filter(array_map(fn ($s2) => $s2['day'], $steps), fn ($v) => $v !== null));
        $report = [
            'lot' => $lot->lotName,
            'crop' => $lot->crop ? (\App\Support\CropStages::label($lot->crop) ?: $lot->crop) : null,
            'variety' => $lot->variety,
            'size' => $lot->lotSize ? rtrim(rtrim(number_format((float) $lot->lotSize, 2), '0'), '.') . ' ' . ($lot->lotSizeUnit ?: '') : null,
            'daySystem' => $dayType,
            'zeroDate' => $zero?->format('M j, Y'),
            'span' => $dates ? ($dayType . ' ' . min($dates) . ' → ' . $dayType . ' +' . max($dates)) : null,
            'steps' => $steps,
            'yields' => $yields,
            'skippedPlanned' => $skippedPlanned,
            'schedule' => $schedule->title,
        ];

        $title = 'Protocol: ' . $lot->lotName
            . ($lot->variety ? ' (' . $lot->variety . ')' : ($report['crop'] ? ' (' . $report['crop'] . ')' : ''))
            . ' · ' . $schedule->title;
        $row = AsFarmReport::create([
            'userId' => Auth::id(),
            'croppingScheduleId' => $schedule->id,
            'kind' => 'protocol',
            'title' => mb_substr($title, 0, 190),
            'params' => ['lotId' => $lot->id],
            'report' => $report,
            'body' => mb_substr($this->protocolBodyText($title, $report), 0, 60000),
            'status' => 'ready',
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('Protocol written and saved with your reports.', ['data' => [
            'id' => $row->id, 'title' => $row->title, 'report' => $report, 'kind' => 'protocol',
        ]]);
    }

    private function protocolBodyText(string $title, array $r): string
    {
        $L = [];
        $L[] = 'FARM PROTOCOL: ' . $title;
        $L[] = str_repeat('=', 50);
        $L[] = trim(($r['crop'] ?? '') . (($r['variety'] ?? null) ? ' · ' . $r['variety'] : '')
            . (($r['size'] ?? null) ? ' · ' . $r['size'] : ''));
        $L[] = 'Day count: ' . $r['daySystem'] . (($r['zeroDate'] ?? null) ? ' (day zero: ' . $r['zeroDate'] . ')' : '');
        if ($r['yields']) {
            $L[] = 'PRODUCED: ' . implode('; ', $r['yields']);
        }
        $L[] = '';
        $L[] = 'THE STEPS';
        $L[] = str_repeat('-', 50);
        foreach ($r['steps'] as $s2) {
            $head = ($s2['dayLabel'] ?? ($s2['date'] ?? 'no date')) . ': ' . $s2['title'];
            $bits = [];
            if ($s2['date']) $bits[] = $s2['date'] . ($s2['endDate'] ? '→' . $s2['endDate'] : '');
            if ($s2['time']) $bits[] = $s2['time'];
            if ($s2['crew']) $bits[] = $s2['crew'] . ' worker' . ($s2['crew'] === 1 ? '' : 's');
            if ($s2['wholeFarm']) $bits[] = 'whole farm task';
            $L[] = $head . ($bits ? ' (' . implode(', ', $bits) . ')' : '');
            foreach ($s2['materials'] as $m) {
                $L[] = '    • ' . $m;
            }
        }
        if (($r['skippedPlanned'] ?? 0) > 0) {
            $L[] = '';
            $L[] = 'Note: ' . $r['skippedPlanned'] . ' planned activities that were never ticked are left out. This is what was really done.';
        }

        return implode("\n", $L);
    }

    private function aneePayer(): \App\Models\User
    {
        return \App\Models\User::findOrFail((int) \App\Support\WorkerContext::effectiveOwnerId());
    }

    /* ----------------------- what Anee reads --------------------------- */

    private function aneePrompt(\App\Models\AsCroppingSchedule $schedule, string $kind, $lot, ?array $facts = null): string
    {
        $ctx = [];
        // The whole season, in the same words the chat's season snapshot uses.
        $ctx[] = \App\Support\SeasonContext::text($schedule);

        // The money, from the profit engine's own arithmetic.
        $pf = $this->profitFacts($schedule);
        $money = 'THE MONEY (computed by the app): revenue ' . \App\Support\Region::symbol() . number_format($pf['revenue'], 2)
            . ', total cost ' . \App\Support\Region::symbol() . number_format($pf['cost'], 2)
            . ' (materials ' . \App\Support\Region::symbol() . number_format($pf['costCats']['materials'], 2)
            . ', labor ' . \App\Support\Region::symbol() . number_format($pf['costCats']['labor'], 2)
            . ', services ' . \App\Support\Region::symbol() . number_format($pf['costCats']['services'], 2)
            . ', extra expenses (the day book) ' . \App\Support\Region::symbol() . number_format($pf['costCats']['expense'], 2)
            . ', stock buys ' . \App\Support\Region::symbol() . number_format($pf['costCats']['purchase'], 2)
            . '), net ' . ($pf['profit'] >= 0 ? 'profit' : 'LOSS') . ' ' . \App\Support\Region::symbol() . number_format(abs($pf['profit']), 2)
            . ($pf['margin'] !== null ? ' (margin ' . $pf['margin'] . '%)' : '') . '.';
        foreach ($pf['lots'] as $l) {
            $money .= ' ' . $l['name'] . ': earned ' . \App\Support\Region::symbol() . number_format($l['revenue'], 2)
                . ', spent ' . \App\Support\Region::symbol() . number_format($l['cost'], 2)
                . ($l['costPerUnit'] !== null ? ', cost ' . \App\Support\Region::symbol() . number_format($l['costPerUnit'], 2) . ' per ' . $l['unit'] : '')
                . (($l['yield'] ?? []) ? ', yield ' . implode(', ', $l['yield']) : '') . '.';
        }
        $ctx[] = $money;
        if ($pf['warnings']) {
            $ctx[] = 'APP WARNINGS: ' . implode(' | ', $pf['warnings']);
        }

        // The crops against their own clocks.
        $lots = $lot ? collect([$lot]) : $schedule->lots;
        $clock = [];
        foreach ($lots as $L) {
            if (! $L->crop) continue;
            $maturity = (int) ($L->daysToMaturity ?: (\App\Support\CropCatalog::CROPS[$L->crop]['maturity'] ?? 0));
            $line = $L->lotName . ': ' . (\App\Support\CropStages::label($L->crop) ?: $L->crop)
                . ($L->variety ? ' (' . $L->variety . ')' : '')
                . ($L->dayZeroDate ? ', day zero ' . substr((string) $L->dayZeroDate, 0, 10) : '')
                . ($maturity ? ', typical maturity ' . $maturity . ' days' : '');
            if ($L->dayZeroDate) {
                $ran = (int) \Carbon\Carbon::parse((string) $L->dayZeroDate)->diffInDays(now('Asia/Manila'), false);
                $line .= ', ' . $ran . ' days elapsed to today';
            }
            $clock[] = $line;
        }
        if ($clock) {
            $ctx[] = "THE CROPS' CLOCKS: " . implode(' | ', $clock)
                . ' (Research what the named variety is known for — maturity, lodging, pest tolerance — and use it where it helps; say when you are unsure.)';
        }

        // Post-harvest observations, verbatim-ish.
        $ph = \App\Models\AsSchedulePostHarvest::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->orderBy('observationDate')->get();
        if ($ph->isNotEmpty()) {
            $ctx[] = 'POST-HARVEST NOTES: ' . $ph->map(function ($h) {
                return '[' . ($h->category ?: 'other') . '] ' . ($h->title ?: '')
                    . ($h->yieldAmount !== null ? ' — ' . rtrim(rtrim(number_format((float) $h->yieldAmount, 2), '0'), '.') . ' ' . ($h->yieldUnit ?: '') : '')
                    . ($h->pricePerUnit !== null ? ' at ' . \App\Support\Region::symbol() . number_format((float) $h->pricePerUnit, 2) : '')
                    . ($h->notes ? ' — ' . mb_substr((string) $h->notes, 0, 300) : '');
            })->implode(' | ');
        }

        // The sky's records: the live ENSO picture (observed state AND the
        // official NOAA CPC forecast) plus the season's own daily archive.
        $enso = \App\Support\EnsoOutlook::forPrompt();
        if ($enso !== '') {
            $ctx[] = 'THE ENSO PICTURE (the Pacific climate driver — weigh it in the read'
                . ($kind === 'season' ? ' and in next-season advice' : ' and in every forward-looking call') . '): ' . $enso;
        }
        $weather = $this->weatherHistory($schedule);
        if ($weather !== '') {
            $ctx[] = $weather;
        }

        // The photos' words (up to 20; their text, not their pixels).
        $shots = \App\Models\AsGalleryImage::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->orderByRaw("(CASE WHEN COALESCE(description,'') != '' OR COALESCE(caption,'') != '' THEN 0 ELSE 1 END)")
            ->orderByDesc('id')->limit(20)->get();
        if ($shots->isNotEmpty()) {
            $ctx[] = 'SEASON PHOTOS (their captions and notes — judge relevance from the words): '
                . $shots->map(fn ($g) => '[' . ($g->created_at?->format('M j') ?? '') . '] '
                    . trim(($g->caption ? $g->caption . '. ' : '') . ($g->description ?: '')) ?: 'untitled photo')
                ->implode(' | ');
        }

        // Past seasons of the same crop, for the comparison.
        if ($kind === 'season') {
            foreach ($this->pastSeasons($schedule) as $ps) {
                $ppf = $this->profitFacts($ps);
                $ctx[] = 'A PAST SEASON OF THE SAME CROP — ' . $ps->title . ': revenue ' . \App\Support\Region::symbol() . number_format($ppf['revenue'], 2)
                    . ', cost ' . \App\Support\Region::symbol() . number_format($ppf['cost'], 2) . ', net ' . \App\Support\Region::symbol() . number_format($ppf['profit'], 2)
                    . '. Compare honestly where it teaches something.';
            }
        }

        // The season report's graphs, said in words, so what Anee writes
        // agrees with what the farmer sees drawn beside it.
        if ($kind === 'season' && $facts) {
            $ctx[] = $this->seasonFactsText($facts);
        }
        if ($kind === 'sofar' && $facts) {
            $ctx[] = $this->sofarFactsText($facts);
        }

        if ($lot) {
            $ctx[] = 'FOCUS: the farmer asked specifically about ' . $lot->lotName . '. Center the analysis there; mention the rest only where it bears on this lot.';
        }

        $schema = $kind === 'season'
            ? '{"headline": string (one warm sentence naming the season\'s verdict), "verdict": string (3-5 sentences, plain and unbiased — the season as it really went), "scores": {"overall": int 0-100, "planning": int, "execution": int, "costControl": int, "timing": int, "recordKeeping": int}, "strengths": [3-6 strings — what genuinely went well, be specific], "wentWrong": [2-6 strings — honest, specific, never cruel], "improvements": [3-6 strings — concrete next-season moves], "protocolChanges": [2-5 of {"change": string, "current": string (what was done, with its date or day-count), "suggested": string (what to do instead), "timing": string (say it in ' . $schedule->dayType . ' day-counts, e.g. "' . $schedule->dayType . ' 25-30"), "why": string}], "lacking": [1-4 strings — records or practices the season was missing], "weatherStory": string (what the sky actually did to this season — rain, dry runs, wind, ENSO — and where it explains a delay or a loss), "delays": string (where the crop ran late or early against its maturity, and the honest reasons — weather, herbicide setbacks, labor), "comparison": string (against the farmer\'s own past seasons if given, else against typical figures for the crop; one short paragraph), "scoreWhy": {"overall": string, "planning": string, "execution": string, "costControl": string, "timing": string, "recordKeeping": string} (one short plain sentence each: why that score), "moneyStory": string (2-3 short sentences on the money: what came in, where most of it went and what that means for each sack or kilo sold; use the computed figures), "harvest": {"typical": string (a typical yield for this crop, and the variety if you know it, on farms in this part of the country, per hectare and in the farmer\'s own unit where you can, e.g. "80 to 100 sacks per hectare"; say "unknown" if you cannot say), "verdict": "above" | "typical" | "below" | "unknown", "note": string (one or two sentences setting the farm\'s own per-hectare harvest against that)}, "workStory": string (one or two sentences on the work: what took the most effort and money, and whether it paid off), "moments": [4-8 of {"when": string (a short date and its day-count, e.g. "Feb 25 · ' . $schedule->dayType . ' 41"), "what": string (one short sentence), "mood": "good" | "bad" | "neutral"}] (the turning points of the season, in date order), "savings": [2-4 of {"what": string (a cost from the records), "idea": string (how to spend less on it or get more from it next season), "save": string (a rough amount saved per season, e.g. "about ' . \App\Support\Region::symbol() . '3,000")}], "encouragement": string (2-3 warm sentences — genuine, a little jolly, proud of what deserves pride, and certain the next season can be better), "nextSeason": [3-6 short checklist strings]}'
            : '{"headline": string (at most 10 words on where the season stands — a title, not a sentence), "standing": "on-track" | "watch" | "rescue" (unbiased — say rescue when it is true), "score": int 0-100 (how well the season stands today, all things weighed), "verdict": string (2-4 sentences on the season as it stands today), "scores": {"protocol": int 0-100 (how faithfully the plan has been followed so far), "timing": int 0-100 (how well the work sits on the crop\'s calendar), "weather": int 0-100 (how kindly the sky has treated the crop and what is ahead), "money": int 0-100 (how the spend runs against a sensible budget for this crop), "records": int 0-100 (how complete the records are)}, "good": [2-5 of {"point": string (short), "why": string (one sentence)}], "bad": [1-5 of {"point": string (short), "why": string (one sentence), "fix": string (what to do about it)}], "protocol": {"summary": string (2-3 sentences on the plan so far — done against planned, to today), "followed": [0-5 short strings — what was done as planned], "missed": [0-5 short strings — what was skipped, late or never planned that the crop needed], "drift": string (one sentence: how far the work has drifted from the plan and what it costs)}, "timing": {"summary": string (2-3 sentences on where the crop is on its own clock against the work done), "stage": string (the growth stage it is in now), "daysBehind": int or null (days the work runs behind the crop, 0 when on time, negative when ahead)}, "weather": {"summary": string (2-3 sentences on what the sky has done to the crop so far), "outlook": string (what the next few weeks and ENSO mean here), "risks": [0-4 short strings]}, "money": {"summary": string (2-3 sentences on the spend so far against the crop and the season), "verdict": "lean"|"fair"|"heavy"}, "risks": [2-5 of {"risk": string, "severity": "low"|"moderate"|"high", "why": string}], "whatsNext": [3-7 of {"action": string, "when": string (a date or a ' . $schedule->dayType . ' day-count), "why": string, "urgency": "now"|"soon"|"routine"}], "lacking": [0-4 strings — what the records are missing that would sharpen this read], "scoreWhy": {"protocol": string, "timing": string, "weather": string, "money": string, "records": string} (one short plain sentence each: why that score), "cropNow": [1-4 of {"lot": string (the lot\'s name, or "The whole farm"), "needs": string (what the crop needs in the stage it is in now, one or two short sentences), "watch": string (the pest, disease or weather to watch for at this stage)}], "harvestOutlook": {"when": string (when harvest is likely, a short date range), "expect": string (a fair harvest to expect per hectare if things go on as they are, in the farmer\'s own unit where you can, or "unknown"), "note": string (one or two sentences: what could raise it or lower it from here)}, "encouragement": string (2-3 warm sentences — honest about the hard parts, sure the farmer can land this)}';

        return 'You are an agricultural analyst for a smallholder farm in ' . \App\Support\Region::name() . ', writing '
            . ($kind === 'season' ? 'a full season debrief now that the season is closed.' : 'a mid-season read of where things stand and what to do next. Judge the PROTOCOL SO FAR from the activity list: what was planned up to today and what was actually ticked done, what was skipped or late, and what the crop needed that was never planned; judge the TIMING of that work against the crop\'s own stage today; judge the WEATHER\'s part so far and ahead; judge the MONEY so far against what this crop and stage usually cost. Say plainly what is good and what is bad.')
            . ' Everything below is the farm\'s own records, compiled by the app — treat the numbers as facts and the notes as the farmer\'s own words.'
            . ' Be unbiased: name what went wrong plainly. Be warm and a little jolly in tone — this is a debrief between friends, not an audit.'
            . ' Account for delays honestly: ENSO conditions, typhoons, drought spells, herbicide setbacks and labor gaps stretch a crop\'s calendar — use the weather records given before blaming the farmer.'
            . ' Say protocol timings in ' . $schedule->dayType . ' day-counts, not bare dates. Use plain language a farmer reads easily; short sentences. No emoji shortcodes like :name:.'
            . ' ' . \App\Support\Region::promptBlock()
            . "\n\n=== THE RECORDS ===\n" . implode("\n\n", $ctx)
            . "\n\n=== YOUR ANSWER ===\nReturn ONLY a single JSON object, no fences, no commentary, exactly this shape:\n" . $schema;
    }

    /** The so-far report's graphs, said in words for Anee's prompt. */
    private function sofarFactsText(array $f): string
    {
        $sym = \App\Support\Region::symbol();
        $peso = fn ($n) => $sym . number_format((float) $n, 0);
        $L = ['THE GRAPHS IN THIS REPORT (computed by the app; your words sit beside them, so use these same figures):'];
        foreach ((array) ($f['lots'] ?? []) as $l) {
            $L[] = $l['name'] . ' (' . $l['crop'] . '): ' . ($l['day'] !== null ? $l['counter'] . ' ' . $l['day'] : 'no day zero yet')
                . ($l['stage'] ? ', in ' . $l['stage'] . ($l['stageNo'] ? ' (stage ' . $l['stageNo'] . ' of ' . $l['stages'] . ')' : '') : '')
                . (! empty($l['next']) ? ', next ' . $l['next']['label'] . ' in ' . $l['next']['inDays'] . ' days' : '')
                . ($l['harvestOn'] ? ', harvest due about ' . $l['harvestOn'] . ($l['daysLeft'] !== null ? ' (' . $l['daysLeft'] . ' days from today)' : '') : '') . '.';
        }
        $M = (array) ($f['money'] ?? []);
        if ($M) {
            $L[] = 'Money actually spent so far (work ticked done, extra expenses and stock buys to today): ' . $peso($M['cost'])
                . '; still to spend on the work planned but not done: ' . $peso($M['planned']) . '; the whole plan: ' . $peso($M['plan']) . '.'
                . ((float) ($M['general'] ?? 0) > 0 ? ' Whole-farm costs not counted in this lot: ' . $peso($M['general']) . '.' : '');
        }
        $P = (array) ($f['plan'] ?? []);
        if ($P) {
            $L[] = 'The plan: ' . $P['done'] . ' of ' . $P['planned'] . ' activities due by today are done, ' . $P['overdue'] . ' overdue, ' . $P['coming'] . ' due in the next 14 days, ' . $P['doneAll'] . ' of ' . $P['total'] . ' done overall.';
        }
        if (! empty($f['overdue'])) {
            $L[] = 'Overdue: ' . implode('; ', array_map(fn ($o) => $o['title'] . ' (due ' . $o['date'] . ', ' . $o['late'] . ' days late' . ($o['lots'] ? ', ' . $o['lots'] : '') . ')', $f['overdue'])) . '.';
        }
        if (! empty($f['coming'])) {
            $L[] = 'Coming up: ' . implode('; ', array_map(fn ($o) => $o['title'] . ' (' . $o['date'] . ($o['lots'] ? ', ' . $o['lots'] : '') . ')', $f['coming'])) . '.';
        }

        return implode("\n", $L);
    }

    /** The season report's graphs, said in words for Anee's prompt. */
    private function seasonFactsText(array $f): string
    {
        $sym = \App\Support\Region::symbol();
        $peso = fn ($n) => $sym . number_format((float) $n, 0);
        $names = ['materials' => 'materials', 'labor' => 'labor', 'services' => 'services', 'expense' => 'extra expenses', 'purchase' => 'stock buys'];
        $L = ['THE GRAPHS IN THIS REPORT (computed by the app; your words sit beside them, so use these same figures):'];
        if (! empty($f['months'])) {
            $L[] = 'Money out by month: ' . implode(' | ', array_map(function ($m) use ($peso, $names) {
                $parts = [];
                foreach ($names as $k => $label) {
                    if ((float) ($m[$k] ?? 0) > 0) $parts[] = $label . ' ' . $peso($m[$k]);
                }
                return $m['label'] . ' ' . $peso($m['total']) . ($parts ? ' (' . implode(', ', $parts) . ')' : '');
            }, $f['months'])) . '.';
        }
        foreach ((array) ($f['lots'] ?? []) as $l) {
            $bits = [];
            if ($l['size']) $bits[] = $l['size'];
            if ($l['yield']) $bits[] = 'harvest ' . implode(', ', $l['yield']);
            if ($l['perHa'] !== null) $bits[] = $l['perHa'] . ' ' . $l['unit'] . ' per hectare';
            $bits[] = 'earned ' . $peso($l['revenue']) . ', spent ' . $peso($l['cost']);
            if ($l['daysRan']) {
                $bits[] = $l['daysRan'] . ' days from day zero to harvest' . ($l['atHarvest'] ? ' (' . $l['atHarvest'] . ' at harvest)' : '')
                    . ($l['maturity'] ? ' against a typical ' . $l['maturity'] : '');
            }
            $L[] = $l['name'] . ($l['crop'] ? ' (' . $l['crop'] . ($l['variety'] ? ', ' . $l['variety'] : '') . ')' : '') . ': ' . implode('; ', $bits) . '.';
        }
        if ((float) ($f['money']['general'] ?? 0) > 0) {
            $L[] = 'Costs that belong to the whole farm rather than one lot: ' . $peso($f['money']['general']) . '.';
        }
        $w = (array) ($f['work'] ?? []);
        if ($w) {
            $L[] = 'The work: ' . $w['total'] . ' activities (' . $w['done'] . ' done), ' . $w['workerDays'] . ' worker-days by ' . $w['workerCount'] . ' workers. By kind: '
                . implode(', ', array_map(fn ($t) => $t['label'] . ' ' . $t['count'] . ' (' . $peso($t['cost']) . ')', (array) $w['types'])) . '.';
            if (! empty($w['workers'])) {
                $L[] = 'Workers: ' . implode(', ', array_map(fn ($x) => $x['name'] . ' ' . $x['days'] . ' days (' . $peso($x['pay']) . ')', $w['workers'])) . '.';
            }
        }

        return implode("\n", $L);
    }

    /**
     * The season's own daily weather off Open-Meteo's archive, summarized
     * per month so the prompt carries a story, not 120 raw rows. The lot's
     * pin gives the coordinates; failing that, its town geocodes; failing
     * everything, the report simply says the sky's records were not there.
     */
    private function weatherHistory(\App\Models\AsCroppingSchedule $schedule): string
    {
        $w = $this->weatherMonths($schedule);
        if (! $w) {
            return '';
        }
        $bits = [];
        foreach ($w['months'] as $v) {
            $bits[] = $v['ym'] . ': ' . round($v['rain']) . 'mm rain over ' . $v['wet'] . ' wet days, '
                . $v['dry'] . ' dry days' . ($v['hot'] ? ', ' . $v['hot'] . ' days ≥35°C' : '')
                . ($v['windy'] ? ', ' . $v['windy'] . ' windy days (≥40 km/h gusts)' : '');
        }

        return 'THE SKY OVER THE SEASON (Open-Meteo daily archive for the field, ' . $w['from'] . ' → ' . $w['to'] . '): '
            . implode(' | ', $bits) . '.';
    }

    /**
     * The same archive as numbers, month by month, for the season report's
     * rain chart as well as the prompt. Null when there is no place to ask
     * about or the archive does not answer (a failure is not cached).
     *
     * @return array{from:string,to:string,months:array<int,array>}|null
     */
    private function weatherMonths(\App\Models\AsCroppingSchedule $schedule): ?array
    {
        try {
            $lat = null;
            $lng = null;
            foreach ($schedule->lots as $L) {
                if ($L->pinLat && $L->pinLng) { $lat = (float) $L->pinLat; $lng = (float) $L->pinLng; break; }
            }
            if ($lat === null) {
                foreach ($schedule->lots as $L) {
                    $place = trim(implode(', ', array_filter([(string) $L->locTown, (string) $L->locProvince])));
                    if ($place !== '') {
                        $geo = app(\App\Services\WeatherService::class)->geocode($place);
                        if ($geo) { $lat = (float) $geo['lat']; $lng = (float) $geo['lng']; break; }
                    }
                }
            }
            if ($lat === null) {
                return null;
            }

            $dates = $schedule->activities->pluck('targetDate')->filter();
            $start = $dates->min()?->format('Y-m-d') ?? now('Asia/Manila')->subMonths(4)->toDateString();
            $end = min($dates->max()?->format('Y-m-d') ?? now('Asia/Manila')->toDateString(),
                now('Asia/Manila')->subDays(3)->toDateString());
            if ($start >= $end) {
                return null;
            }

            $key = 'anee-wxm-' . md5($lat . '|' . $lng . '|' . $start . '|' . $end);

            return Cache::remember($key, 86400, function () use ($lat, $lng, $start, $end) {
                $res = Http::timeout(20)->get('https://archive-api.open-meteo.com/v1/archive', [
                    'latitude' => $lat, 'longitude' => $lng,
                    'start_date' => $start, 'end_date' => $end,
                    'daily' => 'precipitation_sum,temperature_2m_max,temperature_2m_min,wind_speed_10m_max',
                    'timezone' => 'Asia/Manila',
                ])->json();
                $days = $res['daily']['time'] ?? [];
                if (! $days) {
                    return null;
                }
                $rain = $res['daily']['precipitation_sum'] ?? [];
                $tmax = $res['daily']['temperature_2m_max'] ?? [];
                $wind = $res['daily']['wind_speed_10m_max'] ?? [];
                $m = [];
                foreach ($days as $i => $d) {
                    $ym = substr($d, 0, 7);
                    $m[$ym] = $m[$ym] ?? ['ym' => $ym, 'rain' => 0.0, 'wet' => 0, 'dry' => 0, 'hot' => 0, 'windy' => 0, 'n' => 0, 'tmax' => 0.0];
                    $r = (float) ($rain[$i] ?? 0);
                    $m[$ym]['rain'] += $r;
                    $m[$ym]['wet'] += $r >= 1 ? 1 : 0;
                    $m[$ym]['dry'] += $r < 1 ? 1 : 0;
                    $m[$ym]['hot'] += ((float) ($tmax[$i] ?? 0)) >= 35 ? 1 : 0;
                    $m[$ym]['windy'] += ((float) ($wind[$i] ?? 0)) >= 40 ? 1 : 0;
                    $m[$ym]['tmax'] += (float) ($tmax[$i] ?? 0);
                    $m[$ym]['n']++;
                }
                foreach ($m as &$v) {
                    $v['rain'] = round($v['rain'], 1);
                    $v['tmax'] = $v['n'] ? round($v['tmax'] / $v['n'], 1) : null;
                }
                unset($v);

                return ['from' => $start, 'to' => $end, 'months' => array_values($m)];
            });
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * What the season report's graphs draw, every figure the app's own
     * arithmetic (the so-far rule: the picture is the app's, the words are
     * Anee's). The money and when it went, lot by lot with the harvest per
     * hectare, the work by kind and by worker, each crop's days against its
     * typical maturity, the sky month by month, and the farmer's past
     * seasons of the same crop. The money adds up to profitFacts' totals:
     * the same lines, the same labor formula, only sorted by month.
     */
    private function seasonFacts(\App\Models\AsCroppingSchedule $schedule): array
    {
        $pf = $this->profitFacts($schedule);
        $cats = array_keys($pf['costCats']);
        $blank = array_fill_keys($cats, 0.0);

        /* ---- the money by month, and the work by kind and by worker ---- */
        $months = [];
        $undated = 0.0;
        $addMonth = function (?string $iso, string $cat, float $amt) use (&$months, &$undated, $blank) {
            if ($amt <= 0) return;
            if (! $iso) { $undated += $amt; return; }
            $ym = substr($iso, 0, 7);
            $months[$ym] = $months[$ym] ?? $blank;
            $months[$ym][$cat] += $amt;
        };
        $types = [];
        $workers = [];
        $workerDays = 0.0;
        $done = 0;
        foreach ($schedule->activities as $a) {
            if ($a->isDone) $done++;
            $iso = $a->targetDate?->format('Y-m-d');
            $lines = $this->activityLines($a);
            foreach (['materials', 'services', 'labor'] as $k) {
                $addMonth($iso, $k, $lines[$k]);
            }
            foreach ($lines['workers'] as $w) {
                $workerDays += $w['days'];
                $workers[$w['id']] = $workers[$w['id']] ?? ['name' => $w['name'], 'days' => 0.0, 'pay' => 0.0];
                $workers[$w['id']]['days'] += $w['days'];
                $workers[$w['id']]['pay'] += $w['pay'];
            }
            $cost = $lines['materials'] + $lines['services'] + $lines['labor'];
            $slug = $a->activityType ?: 'other';
            $types[$slug] = $types[$slug] ?? [
                'label' => \App\Models\AsScheduleActivity::ACTIVITY_TYPES[$slug] ?? ucfirst(str_replace('_', ' ', $slug)),
                'count' => 0, 'cost' => 0.0,
            ];
            $types[$slug]['count']++;
            $types[$slug]['cost'] += $cost;
        }
        foreach ($schedule->dayExpenses as $e) {
            $addMonth($e->expenseDate?->format('Y-m-d'), 'expense', (float) $e->amount);
        }
        // Stock bought by hand, the purchase line of profitFacts, by the day it came in.
        $buys = AsInventoryMove::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->whereIn('reason', [AsInventoryMove::IN, AsInventoryMove::OPEN])
            ->whereNull('activityId')->get();
        $buyItems = AsInventoryItem::whereIn('id', $buys->pluck('itemId')->unique()->all() ?: [0])->get()->keyBy('id');
        foreach ($buys as $m) {
            $addMonth($m->happenedOn?->format('Y-m-d') ?: $m->created_at?->format('Y-m-d'), 'purchase', $m->cost($buyItems->get((int) $m->itemId)));
        }
        ksort($months);
        $multiYear = count(array_unique(array_map(fn ($ym) => substr($ym, 0, 4), array_keys($months)))) > 1;
        $monthRows = [];
        foreach ($months as $ym => $v) {
            $c = \Carbon\Carbon::createFromFormat('Y-m-d', $ym . '-01');
            $monthRows[] = ['ym' => $ym, 'label' => $c->format($multiYear ? 'M y' : 'M')]
                + array_map(fn ($x) => round($x, 2), $v) + ['total' => round(array_sum($v), 2)];
        }
        uasort($types, fn ($x, $y) => [$y['count'], $y['cost']] <=> [$x['count'], $x['cost']]);
        uasort($workers, fn ($x, $y) => $y['days'] <=> $x['days']);

        /* ---- the harvest, per lot and in all ---- */
        $yieldRows = \App\Models\AsSchedulePostHarvest::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->where('yieldAmount', '>', 0)->get();
        $qty = fn (float $q) => rtrim(rtrim(number_format($q, 2), '0'), '.');
        $harvest = [];
        foreach ($yieldRows->groupBy(fn ($h) => $h->yieldUnit ?: 'units') as $unit => $rows) {
            $harvest[] = $qty((float) $rows->sum('yieldAmount')) . ' ' . $unit;
        }

        /* ---- lot by lot: money, harvest per hectare, days to harvest ---- */
        [$dz, $tp] = \App\Support\LotCalendar::effectiveAnchors($schedule);
        $lastDone = $schedule->activities->filter(fn ($a) => $a->isDone && $a->targetDate)
            ->max(fn ($a) => $a->targetDate->format('Y-m-d'));
        $pfLots = collect($pf['lots'])->keyBy('id');
        $lotRows = [];
        foreach ($schedule->lots as $L) {
            $pl = $pfLots->get($L->id) ?? [];
            $ha = $this->hectares($L);
            $lotYield = $yieldRows->where('lotId', $L->id);
            $units = $lotYield->groupBy(fn ($h) => $h->yieldUnit ?: 'units');
            $one = $units->count() === 1 ? (float) $units->first()->sum('yieldAmount') : null;
            $unit = $units->count() === 1 ? (string) $units->keys()->first() : null;

            // The crop's run: its day zero to the last harvest done on it (or,
            // lacking one, its last yield row, or the season's last done work).
            $harvestDone = $schedule->activities->filter(fn ($a) => $a->isDone && $a->targetDate
                && array_intersect(['harvest', 'harvesting'], $a->typeSlugs())
                && ($a->lots->isEmpty() || $a->lots->contains('id', $L->id)))
                ->max(fn ($a) => $a->targetDate->format('Y-m-d'));
            $endIso = $harvestDone ?: ($lotYield->max(fn ($h) => $h->observationDate?->format('Y-m-d')) ?: $lastDone);
            $zero = $dz[$L->id] ?? null;
            $daysRan = ($zero && $endIso) ? (int) $zero->copy()->startOfDay()->diffInDays(\Carbon\Carbon::parse($endIso)->startOfDay(), false) : null;
            $atHarvest = null;
            if ($endIso && $L->crop) {
                try { $atHarvest = \App\Support\LotCalendar::ageOf($L, \Carbon\Carbon::parse($endIso), $zero, $tp[$L->id] ?? null); } catch (\Throwable $e) { $atHarvest = null; }
            }
            $maturity = (int) ($L->daysToMaturity ?: (\App\Support\CropCatalog::CROPS[$L->crop]['maturity'] ?? 0));

            $lotRows[] = [
                'name' => $L->lotName,
                'crop' => $L->crop ? (\App\Support\CropStages::label($L->crop) ?: $L->crop) : null,
                'icon' => $L->crop ? \App\Support\CropStages::icon($L->crop) : '🌱',
                'variety' => $L->variety ?: null,
                'size' => $L->lotSize ? $qty((float) $L->lotSize) . ' ' . ($L->lotSizeUnit === 'hectare' ? 'ha' : ($L->lotSizeUnit ?: '')) : null,
                'hectares' => $ha,
                'yield' => $units->map(fn ($rows, $u) => $qty((float) $rows->sum('yieldAmount')) . ' ' . $u)->values()->all(),
                'yieldQty' => $one,
                'unit' => $unit,
                'perHa' => ($one && $ha) ? round($one / $ha, 1) : null,
                'revenue' => (float) ($pl['revenue'] ?? 0),
                'cost' => (float) ($pl['cost'] ?? 0),
                'profit' => (float) ($pl['profit'] ?? 0),
                'margin' => $pl['margin'] ?? null,
                'costPerUnit' => $pl['costPerUnit'] ?? null,
                'daysRan' => ($daysRan !== null && $daysRan > 0) ? $daysRan : null,
                'maturity' => $maturity ?: null,
                'atHarvest' => $atHarvest && ($atHarvest['counter'] ?? '') !== 'AGE' ? trim($atHarvest['counter'] . ' ' . $atHarvest['day']) : null,
                'endDate' => $endIso ? \Carbon\Carbon::parse($endIso)->format('M j') : null,
            ];
        }
        $general = $pfLots->get(0);

        /* ---- the farmer's own past seasons of the same crop ---- */
        $past = [[
            'title' => $schedule->title, 'this' => true,
            'revenue' => $pf['revenue'], 'cost' => $pf['cost'], 'profit' => $pf['profit'], 'margin' => $pf['margin'],
        ]];
        foreach ($this->pastSeasons($schedule) as $ps) {
            $ppf = $this->profitFacts($ps);
            $past[] = ['title' => $ps->title, 'this' => false,
                'revenue' => $ppf['revenue'], 'cost' => $ppf['cost'], 'profit' => $ppf['profit'], 'margin' => $ppf['margin']];
        }

        $dates = $schedule->activities->pluck('targetDate')->filter();
        $from = $dates->min();
        $to = $dates->max();

        return [
            'v' => 2,
            'asOf' => now('Asia/Manila')->format('M j, Y'),
            'span' => ($from && $to) ? [
                'from' => $from->format('M j, Y'), 'to' => $to->format('M j, Y'),
                'days' => (int) $from->diffInDays($to) + 1,
            ] : null,
            'money' => [
                'revenue' => $pf['revenue'], 'cost' => $pf['cost'], 'profit' => $pf['profit'], 'margin' => $pf['margin'],
                'dayIncome' => $pf['dayIncome'], 'cats' => $pf['costCats'],
                'general' => $general ? (float) $general['cost'] : 0.0,
            ],
            'months' => $monthRows,
            'undated' => round($undated, 2),
            'harvest' => $harvest,
            'lots' => $lotRows,
            'work' => [
                'total' => $schedule->activities->count(),
                'done' => $done,
                'workerDays' => round($workerDays, 1),
                'types' => array_values(array_map(fn ($t) => ['label' => $t['label'], 'count' => $t['count'], 'cost' => round($t['cost'], 2)], $types)),
                'workers' => array_values(array_map(fn ($w) => ['name' => $w['name'], 'days' => round($w['days'], 1), 'pay' => round($w['pay'], 2)], array_slice($workers, 0, 6, true))),
                'workerCount' => count($workers),
            ],
            'weather' => $this->weatherMonths($schedule),
            'past' => $past,
        ];
    }

    /** A lot's size in hectares, or null when it has none or an unknown unit. */
    private function hectares($lot): ?float
    {
        $size = (float) ($lot->lotSize ?? 0);
        if ($size <= 0) {
            return null;
        }

        return match (strtolower((string) $lot->lotSizeUnit)) {
            'hectare', 'ha', 'hectares', '' => $size,
            'sqm', 'm2' => $size / 10000,
            'acre', 'ac', 'acres' => $size * 0.404686,
            default => null,
        };
    }

    /** Up to three closed seasons of the same crop, newest first, for the comparison. */
    private function pastSeasons(\App\Models\AsCroppingSchedule $schedule)
    {
        $crops = $schedule->lots->pluck('crop')->filter()->unique();
        if ($crops->isEmpty()) {
            return collect();
        }

        return \App\Models\AsCroppingSchedule::where('anisystemUserId', $schedule->anisystemUserId)
            ->where('id', '!=', $schedule->id)
            ->whereIn('status', [\App\Models\AsCroppingSchedule::STATUS_COMPLETED, \App\Models\AsCroppingSchedule::STATUS_ARCHIVED])
            ->where('deleteStatus', 1)
            ->whereHas('lots', fn ($q) => $q->whereIn('crop', $crops))
            ->orderByDesc('id')->limit(3)->get();
    }

    /**
     * What the so-far graphs draw: each lot on its own clock (the stage it
     * is in, the next one, when harvest should come), the plan to today
     * with what is overdue and what is coming, the money SPENT so far
     * against what the rest of the plan will cost, month by month, the
     * work by kind and by worker, and the sky so far. All of it computed
     * here, so the picture is the app's and not the model's.
     *
     * v2 (2026-09-29): money.cost is what has really been spent to today
     * (work ticked done, extra expenses and stock buys dated to today);
     * the first version counted the whole plan there.
     */
    private function sofarFacts(\App\Models\AsCroppingSchedule $schedule, $lot): array
    {
        $today = now('Asia/Manila')->startOfDay();
        $pf = $this->profitFacts($schedule);   // loads every relation used below
        $lots = $lot ? collect([$lot]) : $schedule->lots;
        [$dz, $tp] = \App\Support\LotCalendar::effectiveAnchors($schedule);

        /* ---- each lot on its own clock ---- */
        $lotRows = [];
        foreach ($lots as $L) {
            if (! $L->crop) continue;
            $age = null;
            try { $age = \App\Support\LotCalendar::ageOf($L, $today, $dz[$L->id] ?? null, $tp[$L->id] ?? null); } catch (\Throwable $e) { $age = null; }
            $maturity = (int) ($L->daysToMaturity ?: (\App\Support\CropCatalog::CROPS[$L->crop]['maturity'] ?? 0));
            $day = $age['day'] ?? null;
            $counter = $age['counter'] ?? ($L->dayType ?: 'DAS');
            $stage = ($day !== null && $counter !== 'AGE') ? \App\Support\CropStages::stageFor($L->crop, max(0, (int) $day), $counter, $maturity ?: null) : null;
            // Harvest is reckoned from day zero, the count maturity is kept in.
            $zero = $dz[$L->id] ?? null;
            $harvestOn = ($zero && $maturity && $counter !== 'AGE') ? $zero->copy()->startOfDay()->addDays($maturity) : null;
            $lotRows[] = [
                'name' => $L->lotName,
                'crop' => \App\Support\CropStages::label($L->crop) ?: $L->crop,
                'icon' => \App\Support\CropStages::icon($L->crop),
                'counter' => $counter,
                'day' => $day,
                'maturity' => $maturity ?: null,
                'pct' => ($day !== null && $maturity && $counter !== 'AGE') ? max(0, min(100, (int) round($day / $maturity * 100))) : null,
                'stage' => $stage['label'] ?? null,
                'stageNo' => isset($stage['index']) ? $stage['index'] + 1 : null,
                'stages' => $stage['count'] ?? null,
                'needs' => $stage['needs'] ?? null,
                'next' => $stage['next'] ?? null,
                'harvestOn' => $harvestOn?->format('M j, Y'),
                'daysLeft' => $harvestOn ? (int) $today->diffInDays($harvestOn, false) : null,
            ];
        }

        /* ---- the plan and the money, activity by activity ---- */
        $acts = $schedule->activities;
        if ($lot) {
            $acts = $acts->filter(fn ($a) => $a->lots->isEmpty() || $a->lots->contains('id', $lot->id));
        }
        $cats = array_keys($pf['costCats']);
        $spent = array_fill_keys($cats, 0.0);
        $planned = 0.0;
        $months = [];
        $month = function (?\Carbon\Carbon $d, string $key, float $amt) use (&$months) {
            if (! $d || $amt <= 0) return;
            $ym = $d->format('Y-m');
            $months[$ym] = $months[$ym] ?? ['spent' => 0.0, 'planned' => 0.0];
            $months[$ym][$key] += $amt;
        };
        $plan = ['planned' => 0, 'done' => 0, 'overdue' => 0, 'coming' => 0, 'total' => $acts->count(), 'doneAll' => 0];
        $types = [];
        $workers = [];
        $workerDays = 0.0;
        $overdue = [];
        $coming = [];
        $lotNames = fn ($a) => $a->lots->pluck('lotName')->filter()->implode(', ');
        foreach ($acts as $a) {
            $d = $a->targetDate ? \Carbon\Carbon::parse($a->targetDate)->startOfDay() : null;
            $done = (bool) $a->isDone;
            if ($done) $plan['doneAll']++;
            if ($d && $d->lte($today)) { $plan['planned']++; if ($done) $plan['done']++; elseif ($d->lt($today)) $plan['overdue']++; }
            elseif ($d && ! $done && $d->lte($today->copy()->addDays(14))) { $plan['coming']++; }

            if (! $done && $d && $d->lt($today)) {
                $overdue[] = ['title' => (string) $a->activityTitle, 'date' => $d->format('M j'), 'late' => (int) $d->diffInDays($today), 'lots' => $lotNames($a)];
            } elseif (! $done && $d && $d->lte($today->copy()->addDays(14))) {
                $coming[] = ['title' => (string) $a->activityTitle, 'date' => $d->format('M j'), 'in' => (int) $today->diffInDays($d), 'lots' => $lotNames($a)];
            }

            // A lot's share of an activity, the profit engine's rule: split
            // evenly across the lots it covers. Whole-farm work is not a lot's.
            $share = 1.0;
            if ($lot) {
                $share = $a->lots->contains('id', $lot->id) ? 1 / max(1, $a->lots->count()) : 0.0;
            }
            $lines = $this->activityLines($a);
            $amt = ($lines['materials'] + $lines['services'] + $lines['labor']) * $share;
            if ($done) {
                foreach (['materials', 'services', 'labor'] as $k) $spent[$k] += $lines[$k] * $share;
                $month($d, 'spent', $amt);
                foreach ($lines['workers'] as $w) {
                    $workers[$w['id']] = $workers[$w['id']] ?? ['name' => $w['name'], 'days' => 0.0, 'pay' => 0.0];
                    $workers[$w['id']]['days'] += $w['days'];
                    $workers[$w['id']]['pay'] += $w['pay'];
                    $workerDays += $w['days'];
                }
            } else {
                $planned += $amt;
                $month($d, 'planned', $amt);
            }

            $slug = $a->activityType ?: 'other';
            $types[$slug] = $types[$slug] ?? [
                'label' => \App\Models\AsScheduleActivity::ACTIVITY_TYPES[$slug] ?? ucfirst(str_replace('_', ' ', $slug)),
                'done' => 0, 'total' => 0,
            ];
            $types[$slug]['total']++;
            if ($done) $types[$slug]['done']++;
        }

        // Extra expenses and stock buys belong to the whole farm: counted
        // for the whole season, said apart when one lot was asked about.
        $general = 0.0;
        foreach ($schedule->dayExpenses as $e) {
            $d = $e->expenseDate ? \Carbon\Carbon::parse($e->expenseDate)->startOfDay() : null;
            $amt = (float) $e->amount;
            if ($lot) { $general += $amt; continue; }
            if (! $d || $d->lte($today)) { $spent['expense'] += $amt; $month($d, 'spent', $amt); }
            else { $planned += $amt; $month($d, 'planned', $amt); }
        }
        $buys = AsInventoryMove::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->whereIn('reason', [AsInventoryMove::IN, AsInventoryMove::OPEN])
            ->whereNull('activityId')->get();
        $buyItems = AsInventoryItem::whereIn('id', $buys->pluck('itemId')->unique()->all() ?: [0])->get()->keyBy('id');
        foreach ($buys as $m) {
            $amt = $m->cost($buyItems->get((int) $m->itemId));
            if ($amt <= 0) continue;
            if ($lot) { $general += $amt; continue; }
            $spent['purchase'] += $amt;
            $month($m->happenedOn ? \Carbon\Carbon::parse($m->happenedOn) : $m->created_at, 'spent', $amt);
        }

        ksort($months);
        $multiYear = count(array_unique(array_map(fn ($ym) => substr($ym, 0, 4), array_keys($months)))) > 1;
        $monthRows = [];
        foreach ($months as $ym => $v) {
            $monthRows[] = [
                'ym' => $ym,
                'label' => \Carbon\Carbon::createFromFormat('Y-m-d', $ym . '-01')->format($multiYear ? 'M y' : 'M'),
                'spent' => round($v['spent'], 2), 'planned' => round($v['planned'], 2),
                'total' => round($v['spent'] + $v['planned'], 2),
            ];
        }
        uasort($workers, fn ($x, $y) => $y['days'] <=> $x['days']);
        uasort($types, fn ($x, $y) => [$y['total'], $y['done']] <=> [$x['total'], $x['done']]);
        usort($overdue, fn ($x, $y) => $y['late'] <=> $x['late']);
        usort($coming, fn ($x, $y) => $x['in'] <=> $y['in']);

        $spentTotal = round(array_sum($spent), 2);
        $revenue = $pf['revenue'];
        if ($lot) {
            $revenue = (float) (collect($pf['lots'])->firstWhere('id', $lot->id)['revenue'] ?? 0);
        }

        return [
            'v' => 2,
            'asOf' => $today->format('M j, Y'),
            'lots' => $lotRows,
            'plan' => $plan,
            'money' => [
                'cost' => $spentTotal,
                'revenue' => round($revenue, 2),
                'profit' => round($revenue - $spentTotal, 2),
                'cats' => array_map(fn ($v) => round($v, 2), $spent),
                'planned' => round($planned, 2),
                'plan' => round($spentTotal + $planned, 2),
                'general' => round($general, 2),
            ],
            'months' => $monthRows,
            'work' => [
                'types' => array_values($types),
                'workers' => array_values(array_map(fn ($w) => ['name' => $w['name'], 'days' => round($w['days'], 1), 'pay' => round($w['pay'], 2)], array_slice($workers, 0, 6, true))),
                'workerCount' => count($workers),
                'workerDays' => round($workerDays, 1),
            ],
            'overdue' => array_slice($overdue, 0, 6),
            'coming' => array_slice($coming, 0, 8),
            'weather' => $this->weatherMonths($schedule),
        ];
    }

    /**
     * One activity's money, the profit engine's own formula: its materials,
     * its services (lines and the flat service price), and its labor, with
     * each worker's days and pay (a whole day is two halves, over every day
     * the activity spans).
     */
    private function activityLines($a): array
    {
        $out = ['materials' => 0.0, 'services' => 0.0, 'labor' => 0.0, 'workers' => []];
        foreach ($a->items as $it) {
            $amount = round((float) $it->unitPrice * (float) $it->quantity, 2);
            if ($amount <= 0) continue;
            $out[$it->itemType === 'service' ? 'services' : 'materials'] += $amount;
        }
        if ((float) ($a->servicePrice ?? 0) > 0) {
            $out['services'] += (float) $a->servicePrice;
        }
        $units = match ($a->timeRequired) { 'whole' => 2, 'half' => 1, default => 0 };
        if ($units > 0 && $a->workers->count()) {
            $start = $a->targetDate;
            $end = $a->targetEndDate ?: $a->targetDate;
            $rangeDays = ($start && $end) ? max(1, (int) $start->diffInDays($end) + 1) : 1;
            foreach ($a->workers as $w) {
                $pay = (float) $w->costPerHalfDay * $units * $rangeDays;
                $out['labor'] += $pay;
                $out['workers'][] = ['id' => (int) $w->id, 'name' => trim((string) $w->workerName) ?: 'A worker', 'days' => $units / 2 * $rangeDays, 'pay' => $pay];
            }
        }

        return $out;
    }

    /** Strict-JSON parse: fences stripped, must decode to an object. */
    private function parseAneeReport(string $text): ?array
    {
        $t = trim($text);
        $t = preg_replace('/^```(?:json)?\s*/i', '', $t);
        $t = preg_replace('/\s*```$/', '', $t);
        $start = strpos($t, '{');
        $endPos = strrpos($t, '}');
        if ($start === false || $endPos === false || $endPos <= $start) {
            return null;
        }
        $obj = json_decode(substr($t, $start, $endPos - $start + 1), true);
        if (! is_array($obj) || ! isset($obj['headline'])) {
            return null;
        }
        // Sweep persona shortcodes out of every string, wherever it hides.
        array_walk_recursive($obj, function (&$v) {
            if (is_string($v)) {
                $v = trim(preg_replace('/\s{2,}/', ' ', preg_replace('/:[a-z0-9_-]+:/i', '', $v)));
            }
        });

        return $obj;
    }

    /** The report said in plain text — the attach body and the copy text. */
    private function aneeBodyText(string $kind, string $title, array $r): string
    {
        $L = [];
        $L[] = strtoupper($kind === 'sofar' ? 'ANALYZE SO FAR' : 'ANEE SEASON REPORT') . ': ' . $title;
        $L[] = str_repeat('=', 50);
        $L[] = $r['headline'] ?? '';
        $L[] = $r['verdict'] ?? '';
        if ($kind === 'season') {
            $sc = $r['scores'] ?? [];
            if ($sc) {
                $L[] = 'Scores: ' . collect($sc)->map(fn ($v, $k) => $k . ' ' . $v . '/100')->implode(', ');
            }
            foreach ((array) ($r['scoreWhy'] ?? []) as $k => $why) {
                if (is_string($why) && $why !== '') $L[] = ' • ' . $k . ': ' . $why;
            }
            // The figures the graphs draw, so a reader of the words has them too.
            $F = (array) ($r['facts'] ?? []);
            if (! empty($F['money'])) {
                $sym = \App\Support\Region::symbol();
                $M = $F['money'];
                $L[] = '';
                $L[] = 'THE NUMBERS';
                $L[] = 'Earned ' . $sym . number_format((float) $M['revenue'], 2) . ', spent ' . $sym . number_format((float) $M['cost'], 2)
                    . ', ' . ((float) $M['profit'] >= 0 ? 'net profit ' : 'loss ') . $sym . number_format(abs((float) $M['profit']), 2)
                    . ($M['margin'] !== null ? ' (margin ' . $M['margin'] . '%)' : '') . '.';
                if (! empty($F['harvest'])) $L[] = 'Harvest: ' . implode(', ', $F['harvest']) . '.';
                foreach ((array) ($F['lots'] ?? []) as $l) {
                    $L[] = ' • ' . $l['name'] . ': earned ' . $sym . number_format((float) $l['revenue'], 2) . ', spent ' . $sym . number_format((float) $l['cost'], 2)
                        . ($l['yield'] ? ', harvest ' . implode(', ', $l['yield']) : '')
                        . ($l['perHa'] !== null ? ' (' . $l['perHa'] . ' ' . $l['unit'] . ' per hectare)' : '')
                        . ($l['daysRan'] ? ', ' . $l['daysRan'] . ' days to harvest' . ($l['maturity'] ? ' against a typical ' . $l['maturity'] : '') : '') . '.';
                }
            }
            if (! empty($r['moneyStory'])) { $L[] = ''; $L[] = 'THE MONEY'; $L[] = $r['moneyStory']; }
            if (! empty($r['harvest']) && is_array($r['harvest'])) {
                $L[] = '';
                $L[] = 'THE HARVEST AGAINST A TYPICAL FARM';
                if (! empty($r['harvest']['typical'])) $L[] = 'Typical: ' . $r['harvest']['typical'] . (! empty($r['harvest']['verdict']) ? ' (this season: ' . $r['harvest']['verdict'] . ')' : '');
                if (! empty($r['harvest']['note'])) $L[] = $r['harvest']['note'];
            }
            if (! empty($r['workStory'])) { $L[] = ''; $L[] = 'THE WORK'; $L[] = $r['workStory']; }
            if (! empty($r['moments'])) {
                $L[] = '';
                $L[] = 'THE SEASON IN MOMENTS';
                foreach ((array) $r['moments'] as $m) { $L[] = ' • ' . ($m['when'] ?? '') . ': ' . ($m['what'] ?? '') . (! empty($m['mood']) ? ' [' . $m['mood'] . ']' : ''); }
            }
            if (! empty($r['savings'])) {
                $L[] = '';
                $L[] = 'WHERE YOU CAN SAVE';
                foreach ((array) $r['savings'] as $x) { $L[] = ' • ' . ($x['what'] ?? '') . ': ' . ($x['idea'] ?? '') . (! empty($x['save']) ? ' (' . $x['save'] . ')' : ''); }
            }
            foreach ([['strengths', 'WHAT WENT WELL'], ['wentWrong', 'WHAT WENT WRONG'], ['improvements', 'WHAT TO IMPROVE'], ['lacking', 'WHAT WAS LACKING'], ['nextSeason', 'NEXT SEASON CHECKLIST']] as [$k, $h]) {
                if (! empty($r[$k])) {
                    $L[] = '';
                    $L[] = $h;
                    foreach ((array) $r[$k] as $x) { $L[] = ' • ' . (is_string($x) ? $x : json_encode($x)); }
                }
            }
            if (! empty($r['protocolChanges'])) {
                $L[] = '';
                $L[] = 'PROTOCOL CHANGES';
                foreach ((array) $r['protocolChanges'] as $p) {
                    $L[] = ' • ' . ($p['change'] ?? '') . ': instead of "' . ($p['current'] ?? '') . '", do "' . ($p['suggested'] ?? '') . '" at ' . ($p['timing'] ?? '') . '. Why: ' . ($p['why'] ?? '');
                }
            }
            foreach ([['weatherStory', 'THE WEATHER'], ['delays', 'DELAYS'], ['comparison', 'AGAINST PAST SEASONS'], ['encouragement', 'A WORD FROM ANEE']] as [$k, $h]) {
                if (! empty($r[$k])) { $L[] = ''; $L[] = $h; $L[] = $r[$k]; }
            }
        } else {
            $L[] = 'Standing: ' . strtoupper((string) ($r['standing'] ?? '')) . (isset($r['score']) ? ', ' . (int) $r['score'] . '/100' : '');
            if (! empty($r['scores']) && is_array($r['scores'])) {
                $L[] = 'Scores: ' . implode(', ', array_map(fn ($k, $v) => $k . ' ' . (int) $v, array_keys($r['scores']), $r['scores']));
            }
            foreach ((array) ($r['scoreWhy'] ?? []) as $k => $why) {
                if (is_string($why) && $why !== '') $L[] = ' • ' . $k . ': ' . $why;
            }
            // The figures the graphs draw (facts v2), so the words carry them too.
            $F = (array) ($r['facts'] ?? []);
            if ((int) ($F['v'] ?? 0) >= 2) {
                $sym = \App\Support\Region::symbol();
                $L[] = '';
                $L[] = 'THE NUMBERS (as of ' . ($F['asOf'] ?? '') . ')';
                foreach ((array) ($F['lots'] ?? []) as $l) {
                    $L[] = ' • ' . $l['name'] . ': ' . ($l['day'] !== null ? $l['counter'] . ' ' . $l['day'] : 'no day zero')
                        . ($l['stage'] ? ', ' . $l['stage'] : '')
                        . (! empty($l['next']) ? ', next ' . $l['next']['label'] . ' in ' . $l['next']['inDays'] . ' days' : '')
                        . ($l['harvestOn'] ? ', harvest about ' . $l['harvestOn'] : '') . '.';
                }
                $M = (array) ($F['money'] ?? []);
                if ($M) {
                    $L[] = 'Spent so far ' . $sym . number_format((float) $M['cost'], 2) . ', still to spend ' . $sym . number_format((float) ($M['planned'] ?? 0), 2)
                        . ', the whole plan ' . $sym . number_format((float) ($M['plan'] ?? 0), 2) . '.';
                }
                foreach ((array) ($F['overdue'] ?? []) as $o) { $L[] = ' • Overdue: ' . $o['title'] . ' (due ' . $o['date'] . ', ' . $o['late'] . ' days late)'; }
                foreach ((array) ($F['coming'] ?? []) as $o) { $L[] = ' • Coming: ' . $o['title'] . ' (' . $o['date'] . ')'; }
            }
            if (! empty($r['cropNow'])) {
                $L[] = '';
                $L[] = 'WHAT THE CROP NEEDS NOW';
                foreach ((array) $r['cropNow'] as $x) { $L[] = ' • ' . ($x['lot'] ?? '') . ': ' . ($x['needs'] ?? '') . (! empty($x['watch']) ? ' Watch for: ' . $x['watch'] : ''); }
            }
            if (! empty($r['harvestOutlook']) && is_array($r['harvestOutlook'])) {
                $H = $r['harvestOutlook'];
                $L[] = '';
                $L[] = 'THE HARVEST AHEAD';
                if (! empty($H['when'])) $L[] = 'Likely: ' . $H['when'];
                if (! empty($H['expect'])) $L[] = 'A fair harvest to expect: ' . $H['expect'];
                if (! empty($H['note'])) $L[] = $H['note'];
            }
            if (! empty($r['good'])) {
                $L[] = '';
                $L[] = "WHAT'S GOOD";
                foreach ((array) $r['good'] as $x) { $L[] = ' • ' . ($x['point'] ?? '') . ': ' . ($x['why'] ?? ''); }
            }
            if (! empty($r['bad'])) {
                $L[] = '';
                $L[] = 'WHAT NEEDS WORK';
                foreach ((array) $r['bad'] as $x) { $L[] = ' • ' . ($x['point'] ?? '') . ': ' . ($x['why'] ?? '') . (isset($x['fix']) ? ' Fix: ' . $x['fix'] : ''); }
            }
            if (! empty($r['protocol']) && is_array($r['protocol'])) {
                $L[] = '';
                $L[] = 'THE PROTOCOL SO FAR';
                if (! empty($r['protocol']['summary'])) $L[] = $r['protocol']['summary'];
                foreach ((array) ($r['protocol']['followed'] ?? []) as $x) { $L[] = ' ✓ ' . $x; }
                foreach ((array) ($r['protocol']['missed'] ?? []) as $x) { $L[] = ' ✗ ' . $x; }
                if (! empty($r['protocol']['drift'])) $L[] = $r['protocol']['drift'];
            }
            foreach ([['timing', 'TIMING'], ['weather', 'THE WEATHER'], ['money', 'THE MONEY']] as [$k, $h]) {
                if (! empty($r[$k]) && is_array($r[$k])) {
                    $L[] = '';
                    $L[] = $h;
                    foreach (['summary', 'stage', 'outlook', 'verdict'] as $f) { if (! empty($r[$k][$f])) $L[] = ($f === 'summary' ? '' : ucfirst($f) . ': ') . $r[$k][$f]; }
                    if (isset($r[$k]['daysBehind']) && $r[$k]['daysBehind'] !== null) $L[] = 'Days behind: ' . (int) $r[$k]['daysBehind'];
                    foreach ((array) ($r[$k]['risks'] ?? []) as $x) { $L[] = ' • ' . $x; }
                }
            }
            if (! empty($r['risks'])) {
                $L[] = '';
                $L[] = 'RISKS';
                foreach ((array) $r['risks'] as $x) { $L[] = ' • [' . ($x['severity'] ?? '') . '] ' . ($x['risk'] ?? '') . ': ' . ($x['why'] ?? ''); }
            }
            if (! empty($r['whatsNext'])) {
                $L[] = '';
                $L[] = "WHAT'S NEXT";
                foreach ((array) $r['whatsNext'] as $x) { $L[] = ' • (' . ($x['urgency'] ?? '') . ') ' . ($x['action'] ?? '') . ', ' . ($x['when'] ?? '') . '. ' . ($x['why'] ?? ''); }
            }
            foreach ([['lacking', 'WHAT THE RECORDS LACK'], ['weatherStory', 'THE WEATHER AHEAD'], ['encouragement', 'A WORD FROM ANEE']] as [$k, $h]) {
                if (! empty($r[$k])) {
                    $L[] = '';
                    $L[] = $h;
                    if (is_array($r[$k])) { foreach ($r[$k] as $x) { $L[] = ' • ' . $x; } }
                    else { $L[] = $r[$k]; }
                }
            }
        }

        return implode("\n", array_map('strval', $L));
    }

    /**
     * The attached report as prompt context. Static, like the when-to-plant
     * twin, so AiController::ask can fold it in without a request cycle.
     */
    public static function contextFor(int $id, int $userId): ?array
    {
        $r = AsFarmReport::where('userId', $userId)
            ->where('id', $id)
            ->where('deleteStatus', 1)
            ->where('status', 'ready')
            ->first();
        if (! $r || trim((string) $r->body) === '') {
            return null;
        }
        $text = "\n\n--- ATTACHED: Farm report (the farmer generated this from their own season's records; treat it as shared context) ---\n"
            . 'Report: ' . $r->title . "\n"
            . $r->body
            . "\n--- END OF ATTACHED REPORT ---\n";

        return ['title' => $r->title, 'text' => $text];
    }

    /**
     * All reports beyond Labor belong to the paid tiers, judged by the
     * schedule owner's plan.
     */
    private function guardReports($schedule): void
    {
        if (! \App\Support\Tier::scheduleCan($schedule, 'reportsAll')) {
            \App\Support\Tier::scheduleDenyFor($schedule, 'reportsAll', 'All the reports come with {plan}. Every plan includes the Labor report.');
        }
    }
}
