<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsFarmReport;
use App\Models\AsInventoryItem;
use App\Models\AsInventoryMove;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $v = Validator::make($request->all(), [
            'kind' => 'required|in:labor,expenses,profit',
            'title' => 'required|string|max:180',
            'body' => 'required|string|max:60000',
            'params' => 'nullable|array',
        ]);
        if ($v->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $v->errors()]);
        }

        $row = AsFarmReport::create([
            'userId' => Auth::id(),
            'croppingScheduleId' => $schedule->id,
            'kind' => $request->input('kind'),
            'title' => trim($request->input('title')),
            'params' => $request->input('params') ?: null,
            'body' => $request->input('body'),
            'status' => 'ready',
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('Report frozen for the chat.', ['data' => ['id' => $row->id]]);
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
        $schedule->load('lots');

        return view('sm.expenses-report', ['schedule' => $schedule]);
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

        $from = $this->isoOrNull($request->query('from'));
        $to = $this->isoOrNull($request->query('to'));
        $lotIds = array_values(array_filter(array_map('intval', (array) $request->query('lotIds', []))));
        $cats = array_values(array_intersect((array) $request->query('cats', []),
            ['materials', 'labor', 'services', 'expense', 'purchase', 'income']));
        $invKind = (string) $request->query('invKind', '');
        $status = in_array($request->query('status'), ['done', 'pending'], true) ? $request->query('status') : 'all';

        $schedule->load(['activities.items', 'activities.workers', 'activities.lots', 'dayExpenses', 'dayIncomes']);
        $invItems = AsInventoryItem::where('croppingScheduleId', $schedule->id)->get()->keyBy('id');

        $rows = [];
        $push = function (array $r) use (&$rows, $from, $to, $lotIds, $cats, $status) {
            if ($from && (! $r['on'] || $r['on'] < $from)) return;
            if ($to && (! $r['on'] || $r['on'] > $to)) return;
            if ($lotIds && ! array_intersect($lotIds, $r['lotIds'])) return;
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
                if ($invKind !== '' && (! $inv || $inv->kind !== $invKind)) {
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
                    if ($invKind !== '') continue;   // services carry no inventory kind
                    $push($row + ['cat' => 'services']);
                } else {
                    $push($row + ['cat' => 'materials']);
                }
            }

            if ($invKind === '') {
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
                            'label' => 'Labor — ' . $a->activityTitle,
                            'meta' => $a->workers->count() . ' ' . ($a->workers->count() === 1 ? 'worker' : 'workers')
                                . ' · ' . ($a->timeRequired === 'whole' ? 'whole day' : 'half day')
                                . ($rangeDays > 1 ? ' × ' . $rangeDays . ' days' : ''),
                            'amount' => round($labor, 2),
                        ]);
                    }
                }
            }
        }

        if ($invKind === '') {
            foreach ($schedule->dayExpenses as $e) {
                $push([
                    'on' => $e->expenseDate ? substr((string) $e->expenseDate, 0, 10) : null,
                    'done' => null, 'lotIds' => [], 'cat' => 'expense',
                    'label' => trim((string) $e->note) !== '' ? (string) $e->note : 'Day expense',
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

        // Hand purchases: stock-ins with a price and NO activity — a linked
        // purchase's material line is already in the list above.
        $buys = AsInventoryMove::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->where('reason', AsInventoryMove::IN)
            ->whereNull('activityId')->whereNotNull('unitPrice')->get();
        foreach ($buys as $m) {
            $item = $invItems->get((int) $m->itemId);
            if ($invKind !== '' && (! $item || $item->kind !== $invKind)) continue;
            $push([
                'on' => $m->happenedOn ? substr((string) $m->happenedOn, 0, 10) : null,
                'done' => null, 'lotIds' => [], 'cat' => 'purchase',
                'label' => 'Stock bought — ' . ($item?->name ?? 'item #' . $m->itemId),
                'meta' => ($item ? $item->say((float) $m->delta) : rtrim(rtrim(number_format((float) $m->delta, 3), '0'), '.'))
                    . ' at ₱' . number_format((float) $m->unitPrice, 2) . ' each',
                'amount' => round((float) $m->delta * (float) $m->unitPrice, 2),
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
        ]]);
    }

    /* ================================ PROFIT ============================ */

    public function profitPage(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));

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
            $blockers[] = 'No yield has been recorded in Post-harvest yet — a profit report needs the harvest side. Add a Yield observation with the amount (and its selling price) first.';
        }
        if ($undone->count() > 0) {
            $warnings[] = $undone->count() . ' ' . ($undone->count() === 1 ? 'activity is' : 'activities are')
                . ' not ticked done — their planned costs are still counted, so the figures read as the full plan, not only what happened.';
        }
        $unpriced = $yieldRows->filter(fn ($h) => $h->pricePerUnit === null || (float) $h->pricePerUnit <= 0);
        if ($unpriced->count() > 0) {
            $warnings[] = $unpriced->count() . ' yield ' . ($unpriced->count() === 1 ? 'row has' : 'rows have')
                . ' no selling price — that harvest counts ₱0 revenue until a price is put on it.';
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
                    . ' to maturity — delays (weather, replanting, late harvest) stretch costs, worth a look.';
            } elseif ($schedule->status === \App\Models\AsCroppingSchedule::STATUS_COMPLETED && $ran < max(1, $expected - 15)) {
                $warnings[] = $lot->lotName . ': the season closed at about ' . $ran . ' days against a typical '
                    . $expected . ' to maturity — an early cut usually means yield was left in the field.';
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
            ->where('deleteStatus', 1)->where('reason', AsInventoryMove::IN)
            ->whereNull('activityId')->whereNotNull('unitPrice')->get();
        foreach ($buys as $m) {
            $amt = round((float) $m->delta * (float) $m->unitPrice, 2);
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

        return $this->jsonOk('ok', ['data' => [
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
        ]]);
    }

    private function isoOrNull($v): ?string
    {
        $v = (string) $v;

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
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
}
