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
    ): ?AsInventoryMove {
        if (abs($delta) < 0.0005) {
            return null;
        }

        return DB::transaction(function () use ($item, $delta, $reason, $on, $note, $activityId) {
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
                'happenedOn' => $on ?: now('Asia/Manila')->toDateString(),
                'note' => $note,
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
            // Negative: an activity takes stock out.
            if ($this->move($item, -$qty, AsInventoryMove::ACTIVITY, $on, null, $activityId)) {
                $written++;
            }
        }

        return $written;
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
