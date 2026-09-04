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
