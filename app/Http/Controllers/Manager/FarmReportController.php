<?php

namespace App\Http\Controllers\Manager;

use App\Models\AiSetting;
use App\Models\AsFarmReport;
use App\Models\AsInventoryItem;
use App\Models\AsInventoryMove;
use App\Services\AiClient;
use App\Services\AiCreditService;
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

        return $this->jsonOk('ok', ['data' => $this->profitFacts($schedule)]);
    }

    /** The profit arithmetic itself, reusable by the AI reports. */
    private function profitFacts(\App\Models\AsCroppingSchedule $schedule): array
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
            $blockers[] = 'No yield has been recorded in Observations yet — a profit report needs the harvest side. Add a Yield observation with the amount (and its selling price) first.';
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
    public const PRICE_SEASON = 300;
    public const PRICE_SOFAR = 200;

    public function seasonPage(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $schedule->load('lots');

        return view('sm.anee-report', ['schedule' => $schedule, 'kind' => 'season']);
    }

    public function sofarPage(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
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
            $blockers[] = 'This report runs on the AI Technician, which needs a Boss or Lifetime plan'
                . ((int) $payer->id === (int) Auth::id() ? '.' : ' on the farm owner\'s account.');
        }

        return $this->jsonOk('ok', ['data' => [
            'kind' => $kind,
            'price' => $kind === 'sofar' ? self::PRICE_SOFAR : self::PRICE_SEASON,
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
            $blockers[] = 'The season has no activities yet — there is nothing to analyze.';
        }
        if ($kind === 'season') {
            if ($schedule->status !== \App\Models\AsCroppingSchedule::STATUS_COMPLETED) {
                $blockers[] = 'The season is not marked completed yet. Finish it in the Hub first — a season read is a look BACK.';
            }
            if ($undone > 0) {
                $blockers[] = $undone . ' ' . ($undone === 1 ? 'activity is' : 'activities are') . ' not ticked done. Tick what happened (or delete what did not) so the read is honest.';
            }
            if ($harvestCount === 0) {
                $blockers[] = 'No observations yet. Add at least the yield in Observations — the harvest is half the story.';
            }
        } else {
            if ($undone === 0 && $schedule->activities->isNotEmpty()) {
                $warnings[] = 'Everything is ticked done — the full Season Report may serve you better than a mid-season look.';
            }
        }

        return [$blockers, $warnings];
    }

    public function aneeGenerate(Request $request)
    {
        $schedule = $this->schedule($request->input('scheduleId'));
        $kind = $request->input('kind') === 'sofar' ? 'sofar' : 'season';
        $lotId = (int) $request->input('lotId', 0);
        $payer = $this->aneePayer();
        $settings = AiSetting::current();
        $credits = app(AiCreditService::class);

        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->jsonFail('This report needs the AI Technician (Boss or Lifetime plan).', 403);
        }
        [$blockers] = $this->aneeChecks($schedule, $kind);
        if ($blockers !== []) {
            return $this->jsonFail('Not ready: ' . implode(' ', $blockers), 422);
        }

        $price = $kind === 'sofar' ? self::PRICE_SOFAR : self::PRICE_SEASON;
        $balance = $credits->balance($payer->id);
        if ($balance < $price && ! $credits->unlimited((int) $payer->id)) {
            return $this->jsonFail('You need ' . $price . ' credits for this report and have '
                . rtrim(rtrim(number_format($balance, 2), '0'), '.') . '.', 402, ['outOfCredits' => true]);
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
        $title = ($kind === 'sofar' ? 'Analyze So Far' : 'Anee Season Report') . ' — ' . $schedule->title
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

        $prompt = $this->aneePrompt($schedule, $kind, $lot);

        /* The gateway must not wait on the model — when-to-plant's walk. */
        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => [
                'pending' => true, 'id' => $row->id,
            ]])->send();
            fastcgi_finish_request();
            $this->runAneeJob($row->id, (int) $payer->id, $settings, $prompt, $price);
            exit;
        }

        @set_time_limit(300);
        $this->runAneeJob($row->id, (int) $payer->id, $settings, $prompt, $price);

        return $this->aneeJob($row->id);
    }

    /** The model call and the charge, off the request's clock. */
    private function runAneeJob(int $id, int $payerId, AiSetting $settings, string $prompt, int $price): void
    {
        $ai = app(AiClient::class);
        $credits = app(AiCreditService::class);
        try {
            $result = $ai->ask($settings, [], $prompt, null, 5000);
            if (! ($result['ok'] ?? false)) {
                sleep(3);
                $result = $ai->ask($settings, [], $prompt, null, 5000);
            }
            if (! ($result['ok'] ?? false)) {
                throw new \RuntimeException($result['error'] ?? 'The AI could not be reached. Nothing was charged.');
            }

            $report = $this->parseAneeReport((string) $result['text']);
            if ($report === null) {
                // History turns carry 'text', never 'content'.
                $retry = $ai->ask($settings, [
                    ['role' => 'user', 'text' => $prompt],
                    ['role' => 'assistant', 'text' => (string) $result['text']],
                ], 'That was not valid JSON. Return ONLY the JSON object described, with no fences and no commentary.', null, 5000);
                if ($retry['ok'] ?? false) {
                    $report = $this->parseAneeReport((string) $retry['text']);
                }
            }
            if ($report === null) {
                Log::warning('anee-report: unparsable answer', ['head' => mb_substr((string) $result['text'], 0, 400)]);
                throw new \RuntimeException('The report came back unreadable. Nothing was charged — please try again.');
            }

            $row = AsFarmReport::find($id);
            $credits->chargeAllowingNegative($payerId, (float) $price,
                ($row->kind === 'sofar' ? 'Analyze So Far report' : 'Anee Season Report') . ' — ' . mb_substr((string) $row->title, 0, 120));

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

    /** The saved shelf, per kind and schedule. */
    public function aneeList(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $kind = in_array($request->query('kind'), AsFarmReport::KINDS, true) ? $request->query('kind') : 'season';
        $rows = AsFarmReport::where('userId', Auth::id())
            ->where('croppingScheduleId', $schedule->id)
            ->where('kind', $kind)->where('status', 'ready')->where('deleteStatus', 1)
            ->orderByDesc('id')->limit(30)
            ->get(['id', 'title', 'credits', 'created_at']);

        return $this->jsonOk('ok', ['data' => ['rows' => $rows->map(fn ($r) => [
            'id' => $r->id, 'title' => $r->title, 'credits' => (float) $r->credits,
            'when' => $r->created_at?->format('M j, Y g:i A'),
        ])->values()]]);
    }

    /** One saved report, whole. */
    public function aneeOne(int $id)
    {
        $r = AsFarmReport::where('userId', Auth::id())->where('id', $id)
            ->where('status', 'ready')->where('deleteStatus', 1)->first();
        if (! $r) {
            return $this->jsonFail('That report is gone.', 404);
        }

        return $this->jsonOk('ok', ['data' => [
            'id' => $r->id, 'title' => $r->title, 'report' => $r->report,
            'credits' => (float) $r->credits, 'kind' => $r->kind,
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
                . ($h->pricePerUnit ? ' sold at ₱' . number_format((float) $h->pricePerUnit, 2) : ''))
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

        $title = 'Protocol — ' . $lot->lotName
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

        return $this->jsonOk('Protocol written and saved to the shelf.', ['data' => [
            'id' => $row->id, 'title' => $row->title, 'report' => $report, 'kind' => 'protocol',
        ]]);
    }

    private function protocolBodyText(string $title, array $r): string
    {
        $L = [];
        $L[] = 'FARM PROTOCOL — ' . $title;
        $L[] = str_repeat('=', 50);
        $L[] = trim(($r['crop'] ?? '') . (($r['variety'] ?? null) ? ' · ' . $r['variety'] : '')
            . (($r['size'] ?? null) ? ' · ' . $r['size'] : ''));
        $L[] = 'Day system: ' . $r['daySystem'] . (($r['zeroDate'] ?? null) ? ' (day zero: ' . $r['zeroDate'] . ')' : '');
        if ($r['yields']) {
            $L[] = 'PRODUCED: ' . implode('; ', $r['yields']);
        }
        $L[] = '';
        $L[] = 'THE STEPS';
        $L[] = str_repeat('-', 50);
        foreach ($r['steps'] as $s2) {
            $head = ($s2['dayLabel'] ?? ($s2['date'] ?? 'undated')) . ' — ' . $s2['title'];
            $bits = [];
            if ($s2['date']) $bits[] = $s2['date'] . ($s2['endDate'] ? '→' . $s2['endDate'] : '');
            if ($s2['time']) $bits[] = $s2['time'];
            if ($s2['crew']) $bits[] = $s2['crew'] . ' worker' . ($s2['crew'] === 1 ? '' : 's');
            if ($s2['wholeFarm']) $bits[] = 'whole-farm task';
            $L[] = $head . ($bits ? ' (' . implode(', ', $bits) . ')' : '');
            foreach ($s2['materials'] as $m) {
                $L[] = '    • ' . $m;
            }
        }
        if (($r['skippedPlanned'] ?? 0) > 0) {
            $L[] = '';
            $L[] = 'Note: ' . $r['skippedPlanned'] . ' planned but never-ticked activities are left out — this is what was actually done.';
        }

        return implode("\n", $L);
    }

    /* ============================ COMPARISON ============================ */

    public const PRICE_COMPARE = 30;

    public function comparePage(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));

        return view('sm.compare-report', ['schedule' => $schedule]);
    }

    /** Everything comparable: the user's saved reports across ALL seasons. */
    public function compareOptions(Request $request)
    {
        $this->schedule($request->query('id'));   // access check only
        $credits = app(AiCreditService::class);
        $payer = $this->aneePayer();
        $rows = AsFarmReport::where('userId', Auth::id())
            ->where('status', 'ready')->where('deleteStatus', 1)
            ->where('kind', '!=', 'compare')
            ->orderByDesc('id')->limit(100)
            ->get(['id', 'kind', 'title', 'created_at']);

        return $this->jsonOk('ok', ['data' => [
            'reports' => $rows->map(fn ($r) => [
                'id' => $r->id, 'kind' => $r->kind, 'title' => $r->title,
                'when' => $r->created_at?->format('M j, Y'),
            ])->values(),
            'price' => self::PRICE_COMPARE,
            'balance' => round($credits->balance($payer->id), 2),
            'unlimited' => $credits->unlimited((int) $payer->id),
            'canUseAi' => $payer->canUseAi() && AiSetting::current()->isUsable(),
        ]]);
    }

    /**
     * Two saved reports, side by side. By hand it is free and instant; with
     * Anee's read (30 credits) her verdict on the difference rides along —
     * the same job walk as her other reports.
     */
    public function compareGenerate(Request $request)
    {
        $schedule = $this->schedule($request->input('scheduleId'));
        $withAi = (bool) $request->boolean('withAi');
        $a = AsFarmReport::where('userId', Auth::id())->where('status', 'ready')
            ->where('deleteStatus', 1)->where('id', (int) $request->input('aId'))->first();
        $b = AsFarmReport::where('userId', Auth::id())->where('status', 'ready')
            ->where('deleteStatus', 1)->where('id', (int) $request->input('bId'))->first();
        if (! $a || ! $b || $a->id === $b->id) {
            return $this->jsonFail('Pick two different saved reports.', 422);
        }
        if ($a->kind !== $b->kind) {
            return $this->jsonFail('Compare two reports of the same type — apples with apples.', 422);
        }

        $meta = fn (AsFarmReport $r) => ['id' => $r->id, 'kind' => $r->kind, 'title' => $r->title, 'body' => (string) $r->body];
        $report = ['a' => $meta($a), 'b' => $meta($b), 'analysis' => null];
        $title = 'Comparison — ' . mb_substr($a->title, 0, 80) . ' vs ' . mb_substr($b->title, 0, 80);
        $body = "COMPARISON\n" . str_repeat('=', 50)
            . "\n\n### REPORT A ###\n" . mb_substr((string) $a->body, 0, 25000)
            . "\n\n### REPORT B ###\n" . mb_substr((string) $b->body, 0, 25000);

        if (! $withAi) {
            $row = AsFarmReport::create([
                'userId' => Auth::id(), 'croppingScheduleId' => $schedule->id,
                'kind' => 'compare', 'title' => mb_substr($title, 0, 190),
                'params' => ['aId' => $a->id, 'bId' => $b->id, 'withAi' => false],
                'report' => $report, 'body' => mb_substr($body, 0, 60000),
                'status' => 'ready', 'deleteStatus' => 1,
            ]);

            return $this->jsonOk('Comparison saved.', ['data' => [
                'id' => $row->id, 'title' => $row->title, 'report' => $report, 'kind' => 'compare',
            ]]);
        }

        $payer = $this->aneePayer();
        $settings = AiSetting::current();
        $credits = app(AiCreditService::class);
        if (! $payer->canUseAi() || ! $settings->isUsable()) {
            return $this->jsonFail('The AI analysis needs the AI Technician (Boss or Lifetime plan). You can still compare by hand.', 403);
        }
        $balance = $credits->balance($payer->id);
        if ($balance < self::PRICE_COMPARE && ! $credits->unlimited((int) $payer->id)) {
            return $this->jsonFail('You need ' . self::PRICE_COMPARE . ' credits for the AI analysis and have '
                . rtrim(rtrim(number_format($balance, 2), '0'), '.') . '. You can still compare by hand.', 402, ['outOfCredits' => true]);
        }

        $row = AsFarmReport::create([
            'userId' => Auth::id(), 'croppingScheduleId' => $schedule->id,
            'kind' => 'compare', 'title' => mb_substr($title, 0, 190),
            'params' => ['aId' => $a->id, 'bId' => $b->id, 'withAi' => true],
            'report' => $report, 'status' => 'pending', 'deleteStatus' => 1,
        ]);

        $prompt = 'You are an agricultural analyst for a Philippine smallholder farm. Below are two of the farm\'s own '
            . 'saved reports of the same kind. Compare them honestly and usefully — same warm, plain voice as a '
            . 'debrief between friends.'
            . "\n\n### REPORT A: " . $a->title . " ###\n" . mb_substr((string) $a->body, 0, 9000)
            . "\n\n### REPORT B: " . $b->title . " ###\n" . mb_substr((string) $b->body, 0, 9000)
            . "\n\nReturn ONLY a single JSON object, no fences, exactly this shape:\n"
            . '{"headline": string (one sentence on the biggest difference), "verdict": string (3-4 plain sentences), '
            . '"differences": [3-6 strings — the concrete differences that matter], '
            . '"betterInA": [1-4 strings — where A comes out ahead], "betterInB": [1-4 strings — where B comes out ahead], '
            . '"advice": [2-4 strings — what to carry forward from this comparison]}';

        if (function_exists('fastcgi_finish_request')) {
            ignore_user_abort(true);
            @set_time_limit(0);
            response()->json(['success' => true, 'message' => 'Working…', 'data' => [
                'pending' => true, 'id' => $row->id,
            ]])->send();
            fastcgi_finish_request();
            $this->runCompareJob($row->id, (int) $payer->id, $settings, $prompt, $body);
            exit;
        }
        @set_time_limit(300);
        $this->runCompareJob($row->id, (int) $payer->id, $settings, $prompt, $body);

        return $this->aneeJob($row->id);
    }

    private function runCompareJob(int $id, int $payerId, AiSetting $settings, string $prompt, string $plainBody): void
    {
        $ai = app(AiClient::class);
        $credits = app(AiCreditService::class);
        try {
            $result = $ai->ask($settings, [], $prompt, null, 2500);
            if (! ($result['ok'] ?? false)) {
                sleep(3);
                $result = $ai->ask($settings, [], $prompt, null, 2500);
            }
            if (! ($result['ok'] ?? false)) {
                throw new \RuntimeException($result['error'] ?? 'The AI could not be reached. Nothing was charged.');
            }
            $analysis = $this->parseAneeReport((string) $result['text']);
            if ($analysis === null) {
                $retry = $ai->ask($settings, [
                    ['role' => 'user', 'text' => $prompt],
                    ['role' => 'assistant', 'text' => (string) $result['text']],
                ], 'That was not valid JSON. Return ONLY the JSON object described, with no fences and no commentary.', null, 2500);
                if ($retry['ok'] ?? false) {
                    $analysis = $this->parseAneeReport((string) $retry['text']);
                }
            }
            if ($analysis === null) {
                Log::warning('compare-report: unparsable answer', ['head' => mb_substr((string) $result['text'], 0, 400)]);
                throw new \RuntimeException('The analysis came back unreadable. Nothing was charged — please try again.');
            }

            $row = AsFarmReport::find($id);
            $credits->chargeAllowingNegative($payerId, (float) self::PRICE_COMPARE,
                'Comparison analysis — ' . mb_substr((string) $row->title, 0, 140));

            $rep = $row->report;
            $rep['analysis'] = $analysis;
            $aiText = "\n\n### ANEE'S READ OF THE DIFFERENCE ###\n" . ($analysis['headline'] ?? '') . "\n" . ($analysis['verdict'] ?? '')
                . "\nDifferences: " . implode(' | ', (array) ($analysis['differences'] ?? []))
                . "\nBetter in A: " . implode(' | ', (array) ($analysis['betterInA'] ?? []))
                . "\nBetter in B: " . implode(' | ', (array) ($analysis['betterInB'] ?? []))
                . "\nAdvice: " . implode(' | ', (array) ($analysis['advice'] ?? []));
            $row->update([
                'report' => $rep,
                'body' => mb_substr($plainBody . $aiText, 0, 60000),
                'credits' => self::PRICE_COMPARE,
                'status' => 'ready',
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            report($e);
            AsFarmReport::where('id', $id)->update([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 500),
                'deleteStatus' => 0,
            ]);
        }
    }

    private function aneePayer(): \App\Models\User
    {
        return \App\Models\User::findOrFail((int) \App\Support\WorkerContext::effectiveOwnerId());
    }

    /* ----------------------- what Anee reads --------------------------- */

    private function aneePrompt(\App\Models\AsCroppingSchedule $schedule, string $kind, $lot): string
    {
        $ctx = [];
        // The whole season, in the same words the chat's season snapshot uses.
        $ctx[] = \App\Support\SeasonContext::text($schedule);

        // The money, from the profit engine's own arithmetic.
        $pf = $this->profitFacts($schedule);
        $money = 'THE MONEY (computed by the app): revenue ₱' . number_format($pf['revenue'], 2)
            . ', total cost ₱' . number_format($pf['cost'], 2)
            . ' (materials ₱' . number_format($pf['costCats']['materials'], 2)
            . ', labor ₱' . number_format($pf['costCats']['labor'], 2)
            . ', services ₱' . number_format($pf['costCats']['services'], 2)
            . ', day expenses ₱' . number_format($pf['costCats']['expense'], 2)
            . ', stock buys ₱' . number_format($pf['costCats']['purchase'], 2)
            . '), net ' . ($pf['profit'] >= 0 ? 'profit' : 'LOSS') . ' ₱' . number_format(abs($pf['profit']), 2)
            . ($pf['margin'] !== null ? ' (margin ' . $pf['margin'] . '%)' : '') . '.';
        foreach ($pf['lots'] as $l) {
            $money .= ' ' . $l['name'] . ': earned ₱' . number_format($l['revenue'], 2)
                . ', spent ₱' . number_format($l['cost'], 2)
                . ($l['costPerUnit'] !== null ? ', cost ₱' . number_format($l['costPerUnit'], 2) . ' per ' . $l['unit'] : '')
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
                    . ($h->pricePerUnit !== null ? ' at ₱' . number_format((float) $h->pricePerUnit, 2) : '')
                    . ($h->notes ? ' — ' . mb_substr((string) $h->notes, 0, 300) : '');
            })->implode(' | ');
        }

        // The sky's records: ENSO plus the season's own daily archive.
        $enso = $this->ensoFacts();
        if ($enso !== '') {
            $ctx[] = $enso;
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
        $crops = $schedule->lots->pluck('crop')->filter()->unique();
        if ($crops->isNotEmpty() && $kind === 'season') {
            $past = \App\Models\AsCroppingSchedule::where('anisystemUserId', $schedule->anisystemUserId)
                ->where('id', '!=', $schedule->id)
                ->where('status', \App\Models\AsCroppingSchedule::STATUS_COMPLETED)
                ->where('deleteStatus', 1)
                ->whereHas('lots', fn ($q) => $q->whereIn('crop', $crops))
                ->orderByDesc('id')->limit(3)->get();
            foreach ($past as $ps) {
                $ppf = $this->profitFacts($ps);
                $ctx[] = 'A PAST SEASON OF THE SAME CROP — ' . $ps->title . ': revenue ₱' . number_format($ppf['revenue'], 2)
                    . ', cost ₱' . number_format($ppf['cost'], 2) . ', net ₱' . number_format($ppf['profit'], 2)
                    . '. Compare honestly where it teaches something.';
            }
        }

        if ($lot) {
            $ctx[] = 'FOCUS: the farmer asked specifically about ' . $lot->lotName . '. Center the analysis there; mention the rest only where it bears on this lot.';
        }

        $schema = $kind === 'season'
            ? '{"headline": string (one warm sentence naming the season\'s verdict), "verdict": string (3-5 sentences, plain and unbiased — the season as it really went), "scores": {"overall": int 0-100, "planning": int, "execution": int, "costControl": int, "timing": int, "recordKeeping": int}, "strengths": [3-6 strings — what genuinely went well, be specific], "wentWrong": [2-6 strings — honest, specific, never cruel], "improvements": [3-6 strings — concrete next-season moves], "protocolChanges": [2-5 of {"change": string, "current": string (what was done, with its date or day-count), "suggested": string (what to do instead), "timing": string (say it in ' . $schedule->dayType . ' day-counts, e.g. "' . $schedule->dayType . ' 25-30"), "why": string}], "lacking": [1-4 strings — records or practices the season was missing], "weatherStory": string (what the sky actually did to this season — rain, dry runs, wind, ENSO — and where it explains a delay or a loss), "delays": string (where the crop ran late or early against its maturity, and the honest reasons — weather, herbicide setbacks, labor), "comparison": string (against the farmer\'s own past seasons if given, else against typical figures for the crop; one short paragraph), "encouragement": string (2-3 warm sentences — genuine, a little jolly, proud of what deserves pride, and certain the next season can be better), "nextSeason": [3-6 short checklist strings]}'
            : '{"headline": string (one warm sentence on where the season stands), "standing": "on-track" | "watch" | "rescue" (unbiased — say rescue when it is true), "verdict": string (3-5 sentences on the season as it stands today), "risks": [2-5 of {"risk": string, "severity": "low"|"moderate"|"high", "why": string}], "whatsNext": [3-7 of {"action": string, "when": string (a date or a ' . $schedule->dayType . ' day-count), "why": string, "urgency": "now"|"soon"|"routine"}], "lacking": [0-4 strings — what the records are missing that would sharpen this read], "weatherStory": string (what the recent sky and ENSO mean for the next few weeks here), "encouragement": string (2-3 warm sentences — honest about the hard parts, sure the farmer can land this)}';

        return 'You are an agricultural analyst for a Philippine smallholder farm, writing '
            . ($kind === 'season' ? 'a full season debrief now that the season is closed.' : 'a mid-season read of where things stand and what to do next.')
            . ' Everything below is the farm\'s own records, compiled by the app — treat the numbers as facts and the notes as the farmer\'s own words.'
            . ' Be unbiased: name what went wrong plainly. Be warm and a little jolly in tone — this is a debrief between friends, not an audit.'
            . ' Account for delays honestly: ENSO conditions, typhoons, drought spells, herbicide setbacks and labor gaps stretch a crop\'s calendar — use the weather records given before blaming the farmer.'
            . ' Say protocol timings in ' . $schedule->dayType . ' day-counts, not bare dates. Use plain language a farmer reads easily; short sentences. No emoji shortcodes like :name:.'
            . "\n\n=== THE RECORDS ===\n" . implode("\n\n", $ctx)
            . "\n\n=== YOUR ANSWER ===\nReturn ONLY a single JSON object, no fences, no commentary, exactly this shape:\n" . $schema;
    }

    /** NOAA's ONI, cached a day — the same facts when-to-plant reads. */
    private function ensoFacts(): string
    {
        try {
            return Cache::remember('wtp-oni-facts', 86400, function () {
                $txt = Http::timeout(6)->get('https://www.cpc.ncep.noaa.gov/data/indices/oni.ascii.txt')->body();
                $rows = array_values(array_filter(array_map('trim', explode("\n", $txt))));
                $parsed = [];
                foreach (array_slice($rows, -4) as $r) {
                    $p = preg_split('/\s+/', $r);
                    if (count($p) >= 4 && is_numeric($p[3])) {
                        $parsed[] = $p[0] . ' ' . $p[1] . ' anomaly ' . $p[3] . '°C';
                    }
                }
                if (! $parsed) {
                    return '';
                }
                preg_match('/(-?\d+(?:\.\d+)?)°C$/', end($parsed), $m);
                $last = (float) ($m[1] ?? 0);
                $state = $last >= 0.5 ? 'El Niño conditions'
                    : ($last <= -0.5 ? 'La Niña conditions' : 'ENSO-neutral conditions');

                return 'Observed ENSO state (NOAA CPC ONI, 3-month running anomalies, most recent last): '
                    . implode('; ', $parsed) . ' — i.e. currently ' . $state . '.';
            });
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * The season's own daily weather off Open-Meteo's archive, summarized
     * per month so the prompt carries a story, not 120 raw rows. The lot's
     * pin gives the coordinates; failing that, its town geocodes; failing
     * everything, the report simply says the sky's records were not there.
     */
    private function weatherHistory(\App\Models\AsCroppingSchedule $schedule): string
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
                return '';
            }

            $dates = $schedule->activities->pluck('targetDate')->filter();
            $start = $dates->min()?->format('Y-m-d') ?? now('Asia/Manila')->subMonths(4)->toDateString();
            $end = min($dates->max()?->format('Y-m-d') ?? now('Asia/Manila')->toDateString(),
                now('Asia/Manila')->subDays(3)->toDateString());
            if ($start >= $end) {
                return '';
            }

            $key = 'anee-wx-' . md5($lat . '|' . $lng . '|' . $start . '|' . $end);

            return Cache::remember($key, 86400, function () use ($lat, $lng, $start, $end) {
                $res = Http::timeout(20)->get('https://archive-api.open-meteo.com/v1/archive', [
                    'latitude' => $lat, 'longitude' => $lng,
                    'start_date' => $start, 'end_date' => $end,
                    'daily' => 'precipitation_sum,temperature_2m_max,temperature_2m_min,wind_speed_10m_max',
                    'timezone' => 'Asia/Manila',
                ])->json();
                $days = $res['daily']['time'] ?? [];
                if (! $days) {
                    return '';
                }
                $rain = $res['daily']['precipitation_sum'] ?? [];
                $tmax = $res['daily']['temperature_2m_max'] ?? [];
                $wind = $res['daily']['wind_speed_10m_max'] ?? [];
                $m = [];
                foreach ($days as $i => $d) {
                    $ym = substr($d, 0, 7);
                    $m[$ym] = $m[$ym] ?? ['rain' => 0.0, 'wet' => 0, 'dry' => 0, 'hot' => 0, 'windy' => 0, 'n' => 0];
                    $r = (float) ($rain[$i] ?? 0);
                    $m[$ym]['rain'] += $r;
                    $m[$ym]['wet'] += $r >= 1 ? 1 : 0;
                    $m[$ym]['dry'] += $r < 1 ? 1 : 0;
                    $m[$ym]['hot'] += ((float) ($tmax[$i] ?? 0)) >= 35 ? 1 : 0;
                    $m[$ym]['windy'] += ((float) ($wind[$i] ?? 0)) >= 40 ? 1 : 0;
                    $m[$ym]['n']++;
                }
                $bits = [];
                foreach ($m as $ym => $v) {
                    $bits[] = $ym . ': ' . round($v['rain']) . 'mm rain over ' . $v['wet'] . ' wet days, '
                        . $v['dry'] . ' dry days' . ($v['hot'] ? ', ' . $v['hot'] . ' days ≥35°C' : '')
                        . ($v['windy'] ? ', ' . $v['windy'] . ' windy days (≥40 km/h gusts)' : '');
                }

                return 'THE SKY OVER THE SEASON (Open-Meteo daily archive for the field, ' . $start . ' → ' . $end . '): '
                    . implode(' | ', $bits) . '.';
            });
        } catch (\Throwable $e) {
            return '';
        }
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
        $L[] = strtoupper($kind === 'sofar' ? 'ANALYZE SO FAR' : 'ANEE SEASON REPORT') . ' — ' . $title;
        $L[] = str_repeat('=', 50);
        $L[] = $r['headline'] ?? '';
        $L[] = $r['verdict'] ?? '';
        if ($kind === 'season') {
            $sc = $r['scores'] ?? [];
            if ($sc) {
                $L[] = 'Scores: ' . collect($sc)->map(fn ($v, $k) => $k . ' ' . $v . '/100')->implode(', ');
            }
            foreach ([['strengths', 'WHAT WENT WELL'], ['wentWrong', 'WHAT WENT WRONG'], ['improvements', 'WHAT TO IMPROVE'], ['lacking', 'WHAT WAS LACKING'], ['nextSeason', 'NEXT SEASON CHECKLIST']] as [$k, $h]) {
                if (! empty($r[$k])) {
                    $L[] = '';
                    $L[] = $h;
                    foreach ((array) $r[$k] as $x) { $L[] = ' - ' . (is_string($x) ? $x : json_encode($x)); }
                }
            }
            if (! empty($r['protocolChanges'])) {
                $L[] = '';
                $L[] = 'PROTOCOL CHANGES';
                foreach ((array) $r['protocolChanges'] as $p) {
                    $L[] = ' - ' . ($p['change'] ?? '') . ' — instead of "' . ($p['current'] ?? '') . '", do "' . ($p['suggested'] ?? '') . '" at ' . ($p['timing'] ?? '') . '. Why: ' . ($p['why'] ?? '');
                }
            }
            foreach ([['weatherStory', 'THE WEATHER'], ['delays', 'DELAYS'], ['comparison', 'AGAINST PAST SEASONS'], ['encouragement', 'A WORD FROM ANEE']] as [$k, $h]) {
                if (! empty($r[$k])) { $L[] = ''; $L[] = $h; $L[] = $r[$k]; }
            }
        } else {
            $L[] = 'Standing: ' . strtoupper((string) ($r['standing'] ?? ''));
            if (! empty($r['risks'])) {
                $L[] = '';
                $L[] = 'RISKS';
                foreach ((array) $r['risks'] as $x) { $L[] = ' - [' . ($x['severity'] ?? '') . '] ' . ($x['risk'] ?? '') . ' — ' . ($x['why'] ?? ''); }
            }
            if (! empty($r['whatsNext'])) {
                $L[] = '';
                $L[] = "WHAT'S NEXT";
                foreach ((array) $r['whatsNext'] as $x) { $L[] = ' - (' . ($x['urgency'] ?? '') . ') ' . ($x['action'] ?? '') . ' — ' . ($x['when'] ?? '') . '. ' . ($x['why'] ?? ''); }
            }
            foreach ([['lacking', 'WHAT THE RECORDS LACK'], ['weatherStory', 'THE WEATHER AHEAD'], ['encouragement', 'A WORD FROM ANEE']] as [$k, $h]) {
                if (! empty($r[$k])) {
                    $L[] = '';
                    $L[] = $h;
                    if (is_array($r[$k])) { foreach ($r[$k] as $x) { $L[] = ' - ' . $x; } }
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
}
