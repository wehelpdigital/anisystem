<?php

namespace App\Services;

use App\Models\AsInventoryItem;
use App\Models\AsInventoryMove;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * The one place stock changes.
 *
 * Everything that moves stock — a hand-typed use, a delivery, an activity
 * being ticked done — comes through move(). That is not tidiness for its own
 * sake: every move has to record what the stock was before and after it, and
 * a second writer that forgot would leave a log that reads as if the numbers
 * jumped.
 *
 * On-hand is the sum of the moves, never a stored total. A running column is
 * faster and is wrong the first time anything writes one without the other,
 * and the one thing a stock figure must not be is quietly wrong.
 */
class InventoryService
{
    /** What one item has on hand right now. */
    public function onHand(int $itemId): float
    {
        return (float) AsInventoryMove::where('itemId', $itemId)
            ->where('deleteStatus', 1)
            ->sum('delta');
    }

    /**
     * On-hand for every item on a schedule, as itemId => quantity.
     *
     * One grouped query rather than one per item: the module draws a whole
     * shelf at once, and so does the picker in the activity sheet.
     */
    public function onHandFor(int $scheduleId): array
    {
        return AsInventoryMove::where('croppingScheduleId', $scheduleId)
            ->where('deleteStatus', 1)
            ->groupBy('itemId')
            ->selectRaw('itemId, SUM(delta) as total')
            ->pluck('total', 'itemId')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * Move stock, and write down what it was either side.
     *
     * @param  float  $delta  signed, in the item's base unit. Out is negative.
     * @return AsInventoryMove|null null when the delta is nothing at all —
     *                              a move of zero is a line in the log that
     *                              says nothing happened.
     */
    public function move(
        AsInventoryItem $item,
        float $delta,
        string $reason,
        ?string $on = null,
        ?string $note = null,
        ?int $activityId = null,
        ?array $entered = null,
        ?float $unitPrice = null,
    ): ?AsInventoryMove {
        if (abs($delta) < 0.0005) {
            return null;
        }

        return DB::transaction(function () use ($item, $delta, $reason, $on, $note, $activityId, $entered, $unitPrice) {
            /* Read inside the transaction and lock the item's rows, so two
             * people ticking two activities at the same moment cannot both
             * read the same "before" and write two moves that each claim the
             * stock went from 84 to 72. */
            $before = (float) AsInventoryMove::where('itemId', $item->id)
                ->where('deleteStatus', 1)
                ->lockForUpdate()
                ->sum('delta');

            return AsInventoryMove::create([
                'croppingScheduleId' => $item->croppingScheduleId,
                'itemId' => $item->id,
                'delta' => $delta,
                'qtyBefore' => $before,
                'qtyAfter' => $before + $delta,
                'reason' => $reason,
                'activityId' => $activityId,
                // What the hand typed, when it differed from the book's unit.
                'enteredQty' => $entered['qty'] ?? null,
                'enteredUnit' => $entered['unit'] ?? null,
                'happenedOn' => $on ?: now('Asia/Manila')->toDateString(),
                'note' => $note,
                // What one unit cost on THIS move — a fresh bag bought dear
                // keeps its price beside the shelf's older, cheaper stock.
                'unitPrice' => $unitPrice,
                'byUserId' => Auth::id(),
                'deleteStatus' => 1,
            ]);
        });
    }

    /**
     * Spend what an activity uses, once.
     *
     * Called when an activity is ticked done. Idempotent on purpose: a double
     * tap, a retried request or a re-save must not spend the stock twice, so
     * an activity that already has moves against it is left alone.
     *
     * @param  array<int, array{itemId:int, qty:float}>  $lines
     * @return int how many moves were written
     */
    public function spendForActivity(int $scheduleId, int $activityId, array $lines, ?string $on = null): int
    {
        $already = AsInventoryMove::where('activityId', $activityId)
            ->where('reason', AsInventoryMove::ACTIVITY)
            ->where('deleteStatus', 1)
            ->exists();
        if ($already || $lines === []) {
            return 0;
        }

        $items = AsInventoryItem::where('croppingScheduleId', $scheduleId)
            ->where('deleteStatus', 1)
            ->whereIn('id', array_column($lines, 'itemId'))
            ->get()->keyBy('id');

        $written = 0;
        foreach ($lines as $line) {
            $item = $items->get((int) $line['itemId']);
            $qty = (float) ($line['qty'] ?? 0);
            if (! $item || $qty <= 0) {
                continue;
            }
            /* The line may be measured in a kin unit — 100 kg against a
             * bag-counted book. Its words resolve to a unit and the amount
             * converts; words that resolve to nothing (or to another
             * dimension) are taken as the item's own unit, which is what
             * every line meant before units grew dimensions. */
            $typed = $qty;
            $fromKey = AsInventoryItem::unitKeyFromWords($line['unit'] ?? null);
            $entered = null;
            if ($fromKey !== null) {
                $converted = AsInventoryItem::convert($qty, $fromKey, $item->unit);
                if ($converted !== null) {
                    $qty = round($converted, 3);
                    if ($fromKey !== $item->unit) {
                        $entered = ['qty' => $typed, 'unit' => $fromKey];
                    }
                }
            }
            if ($qty <= 0) {
                continue;
            }
            // Negative: an activity takes stock out.
            if ($this->move($item, -$qty, AsInventoryMove::ACTIVITY, $on, null, $activityId, $entered)) {
                $written++;
            }
        }

        return $written;
    }

    /**
     * The purchases an activity declares, kept in step with its rows.
     *
     * A material line marked newBuy is a delivery: stock the activity brings
     * in (at its own price) and then uses. Replace-all like the rows
     * themselves — every save voids the activity's previous stock-ins and
     * posts the current ones, so an edit that changes quantity or price, or
     * withdraws the purchase, never leaves a stale delivery in the book.
     * Chains rebuild for every touched item, because voiding a mid-chain
     * move leaves later before/after figures lying (amend()'s precedent).
     *
     * @param  array<int, array{itemId:int, qty:float, unit:?string, price:?float}>  $lines
     */
    public function syncActivityPurchases(int $scheduleId, int $activityId, array $lines, ?string $on, string $label = ''): int
    {
        $touched = AsInventoryMove::where('activityId', $activityId)
            ->where('reason', AsInventoryMove::IN)
            ->where('deleteStatus', 1)
            ->pluck('itemId')->all();
        if ($touched === [] && $lines === []) {
            return 0;
        }
        AsInventoryMove::where('activityId', $activityId)
            ->where('reason', AsInventoryMove::IN)
            ->where('deleteStatus', 1)
            ->update(['deleteStatus' => 0]);

        $items = AsInventoryItem::where('croppingScheduleId', $scheduleId)
            ->where('deleteStatus', 1)
            ->whereIn('id', array_column($lines, 'itemId'))
            ->get()->keyBy('id');

        $written = 0;
        foreach ($lines as $line) {
            $item = $items->get((int) $line['itemId']);
            $qty = (float) ($line['qty'] ?? 0);
            if (! $item || $qty <= 0) {
                continue;
            }
            // Typed in a kin unit, counted in the book's — same arithmetic
            // the spend does, so the in and the out cancel to the gram.
            $typed = $qty;
            $fromKey = AsInventoryItem::unitKeyFromWords($line['unit'] ?? null);
            $entered = null;
            if ($fromKey !== null) {
                $converted = AsInventoryItem::convert($qty, $fromKey, $item->unit);
                if ($converted !== null) {
                    $qty = round($converted, 3);
                    if ($fromKey !== $item->unit) {
                        $entered = ['qty' => $typed, 'unit' => $fromKey];
                    }
                }
            }
            if ($qty <= 0) {
                continue;
            }
            $price = isset($line['price']) && $line['price'] !== null && $line['price'] !== '' ? (float) $line['price'] : null;
            $note = 'Bought for ' . ($label !== '' ? '“' . mb_substr($label, 0, 80) . '”' : 'an activity')
                . ($price !== null ? ' at ₱' . number_format($price, 2) . ' each' : '');
            if ($this->move($item, $qty, AsInventoryMove::IN, $on, $note, $activityId, $entered, $price)) {
                $written++;
                $touched[] = $item->id;
            }
        }
        foreach (array_unique($touched) as $itemId) {
            $this->rebuildChain((int) $itemId);
        }

        return $written;
    }

    /** An activity that leaves takes its declared purchases with it. */
    public function dropActivityPurchases(int $activityId): void
    {
        $touched = AsInventoryMove::where('activityId', $activityId)
            ->where('reason', AsInventoryMove::IN)
            ->where('deleteStatus', 1)
            ->pluck('itemId')->all();
        if ($touched === []) {
            return;
        }
        AsInventoryMove::where('activityId', $activityId)
            ->where('reason', AsInventoryMove::IN)
            ->where('deleteStatus', 1)
            ->update(['deleteStatus' => 0]);
        foreach (array_unique($touched) as $itemId) {
            $this->rebuildChain((int) $itemId);
        }
    }

    /**
     * Take back what an activity spent, when it is unticked.
     *
     * The moves are removed rather than reversed with an opposite move. A
     * reversal would be the honest thing for a delivery somebody actually
     * received; this is a record of something that turns out not to have
     * happened, and leaving both halves in the log would have a farmer
     * reading two lines to work out that nothing changed.
     */
    public function unspendForActivity(int $activityId): int
    {
        return AsInventoryMove::where('activityId', $activityId)
            ->where('reason', AsInventoryMove::ACTIVITY)
            ->where('deleteStatus', 1)
            ->update(['deleteStatus' => 0]);
    }

    /**
     * Begin (or re-begin) an item's book at a chosen day.
     *
     * Writes the Start as an OPEN move, adopts activity lines that name this
     * item but are not yet linked to it, and spends what done activities used
     * from that day forward. Called when an item is created with a first
     * count, and by restart() after wiping the old answer.
     */
    public function startCount(AsInventoryItem $item, float $qty, ?string $on, ?string $note = null): ?AsInventoryMove
    {
        $on = $on ?: now('Asia/Manila')->toDateString();
        $open = $this->move($item, $qty, AsInventoryMove::OPEN, $on, $note);
        $this->adoptLines($item);
        $this->spendItemForDoneActivities($item, $on);
        $this->rebuildChain($item->id);

        return $open;
    }

    /**
     * Change when the book begins, or how much it began with.
     *
     * The old Start and the old retroactive spends are REMOVED, not reversed:
     * they are a record of an answer the user has just said was wrong.
     * Hand-typed moves (in/out/adjust) survive untouched — those describe
     * deliveries and uses that happened regardless of where the book opens.
     */
    public function restart(AsInventoryItem $item, float $qty, ?string $on): void
    {
        AsInventoryMove::where('itemId', $item->id)
            ->whereIn('reason', [AsInventoryMove::OPEN, AsInventoryMove::ACTIVITY])
            ->where('deleteStatus', 1)
            ->update(['deleteStatus' => 0]);

        $this->startCount($item, $qty, $on);

        /* A start with no stock has no OPEN line, so the birth line is the
         * start marker — its date moves with the answer, because it is the
         * only line the pencil can live on. With stock, the birth line stays
         * where it was: when the item joined the list is its own true fact. */
        if ($qty <= 0) {
            AsInventoryMove::where('itemId', $item->id)
                ->where('reason', AsInventoryMove::CREATED)
                ->where('deleteStatus', 1)
                ->update(['happenedOn' => $on ?: now('Asia/Manila')->toDateString()]);
            $this->rebuildChain($item->id);
        }
    }

    /**
     * Amend one hand-typed line: its amount, its day, its note, and what the
     * hand typed. The chain is rebuilt because the line's place in time may
     * have moved — before/after figures are a record only while the line
     * stands where it was written. (rebuildChain's callers: startCount,
     * restart, and this.)
     */
    public function amend(AsInventoryMove $move, float $delta, ?string $on, ?string $note, ?array $entered, ?int $boardSort = null): void
    {
        DB::transaction(function () use ($move, $delta, $on, $note, $entered, $boardSort) {
            $move->update([
                'delta' => $delta,
                'happenedOn' => $on ?: $move->happenedOn,
                'note' => $note,
                'enteredQty' => $entered['qty'] ?? null,
                'enteredUnit' => $entered['unit'] ?? null,
                'boardSort' => $boardSort,
            ]);
            $this->rebuildChain($move->itemId);
        });
    }

    /**
     * Link existing activity lines to this item by name.
     *
     * An activity written before the item existed named its material as free
     * text. When the item arrives, lines whose name matches (case-blind,
     * within the season) become its lines — that is what lets a book started
     * mid-season know what was already spent.
     */
    private function adoptLines(AsInventoryItem $item): int
    {
        return DB::table('as_schedule_activity_items')
            ->join('as_schedule_activities', 'as_schedule_activities.id', '=', 'as_schedule_activity_items.activityId')
            ->where('as_schedule_activities.croppingScheduleId', $item->croppingScheduleId)
            ->where('as_schedule_activities.deleteStatus', 1)
            ->where('as_schedule_activity_items.deleteStatus', 1)
            ->whereNull('as_schedule_activity_items.inventoryItemId')
            ->whereRaw('LOWER(TRIM(as_schedule_activity_items.itemName)) = ?', [mb_strtolower(trim($item->name))])
            ->update(['as_schedule_activity_items.inventoryItemId' => $item->id]);
    }

    /**
     * Spend this one item for every done activity from a date forward.
     *
     * Not spendForActivity(): that one is idempotent per ACTIVITY, which is
     * right when a tick arrives and wrong here — an activity that used two
     * items must not be skipped because the other item's move survived a
     * restart. Idempotent per (activity, item) instead.
     */
    private function spendItemForDoneActivities(AsInventoryItem $item, string $fromDate): int
    {
        /* Line by line rather than a SQL sum, because each line carries its
         * own unit now and 100 kg + 2 bags is not 102 of anything until both
         * are converted into the item's unit. */
        $lines = DB::table('as_schedule_activity_items as l')
            ->join('as_schedule_activities as a', 'a.id', '=', 'l.activityId')
            ->where('a.croppingScheduleId', $item->croppingScheduleId)
            ->where('a.deleteStatus', 1)
            ->where('a.isDone', 1)
            ->whereNotNull('a.targetDate')
            ->where('a.targetDate', '>=', $fromDate)
            ->where('l.deleteStatus', 1)
            ->where('l.inventoryItemId', $item->id)
            ->where('l.quantity', '>', 0)
            ->orderBy('a.targetDate')
            ->get(['a.id as activityId', 'a.targetDate', 'l.quantity', 'l.unitOfMeasure']);

        $rows = [];
        foreach ($lines as $l) {
            $qty = (float) $l->quantity;
            $fromKey = AsInventoryItem::unitKeyFromWords($l->unitOfMeasure);
            if ($fromKey !== null) {
                $converted = AsInventoryItem::convert($qty, $fromKey, $item->unit);
                if ($converted !== null) {
                    $qty = round($converted, 3);
                }
            }
            if (! isset($rows[$l->activityId])) {
                $rows[$l->activityId] = (object) ['activityId' => $l->activityId, 'targetDate' => $l->targetDate, 'qty' => 0.0];
            }
            $rows[$l->activityId]->qty += $qty;
        }
        $rows = array_values($rows);

        $written = 0;
        foreach ($rows as $r) {
            $already = AsInventoryMove::where('activityId', $r->activityId)
                ->where('itemId', $item->id)
                ->where('reason', AsInventoryMove::ACTIVITY)
                ->where('deleteStatus', 1)
                ->exists();
            if ($already) {
                continue;
            }
            if ($this->move($item, -1 * (float) $r->qty, AsInventoryMove::ACTIVITY,
                substr((string) $r->targetDate, 0, 10), null, (int) $r->activityId)) {
                $written++;
            }
        }

        return $written;
    }

    /**
     * Renumber one item's ledger so every line's before/after is true again.
     *
     * A restart removes moves from the middle of the book and inserts others
     * before lines that already existed; the stored readings then describe an
     * order that no longer exists. This deliberately bends "the log is never
     * recomputed" — a restart IS the user saying the old record was wrong,
     * and a book whose lines disagree with their own totals is worse than one
     * that was corrected. Nothing outside restart/startCount calls this.
     */
    private function rebuildChain(int $itemId): void
    {
        $running = 0.0;
        $moves = AsInventoryMove::where('itemId', $itemId)
            ->where('deleteStatus', 1)
            ->orderBy('happenedOn')->orderBy('id')
            ->get();
        foreach ($moves as $m) {
            $before = $running;
            $running = round($running + (float) $m->delta, 3);
            if ((float) $m->qtyBefore !== $before || (float) $m->qtyAfter !== $running) {
                $m->update(['qtyBefore' => $before, 'qtyAfter' => $running]);
            }
        }
    }

    /** Items whose stock has fallen to the level they said to watch for. */
    public function runningLow(int $scheduleId): array
    {
        $onHand = $this->onHandFor($scheduleId);
        $low = [];
        foreach (AsInventoryItem::where('croppingScheduleId', $scheduleId)->where('deleteStatus', 1)->get() as $item) {
            $threshold = (float) $item->lowAt;
            if ($threshold <= 0) {
                continue;
            }
            $have = $onHand[$item->id] ?? 0.0;
            if ($have <= $threshold) {
                $low[] = ['item' => $item, 'have' => $have, 'lowAt' => $threshold];
            }
        }

        return $low;
    }
}
