<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsInventoryItem;
use App\Models\AsInventoryMove;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * What the farm has, what it had, and where it went.
 *
 * Three views of one ledger, which is why they are three tabs and not three
 * pages: Management is the shelf you keep, Logs is every change in order, and
 * Totals is what is left. A farmer standing in the shed asks all three within
 * a minute of each other.
 */
class InventoryController extends BaseScheduleController
{
    public function __construct(private readonly InventoryService $stock)
    {
    }

    /** Module page: GET /app/sm-inventory?id={scheduleId} */
    public function page(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));

        $items = AsInventoryItem::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->orderBy('name')->get();
        $onHand = $this->stock->onHandFor($schedule->id);

        return view('sm.inventory', [
            'schedule' => $schedule,
            'items' => $items,
            'onHand' => $onHand,
            'moves' => $this->recentMoves($schedule->id),
        ]);
    }

    /** The log, newest first, with the item each line belongs to. */
    private function recentMoves(int $scheduleId, int $limit = 120)
    {
        $moves = AsInventoryMove::where('croppingScheduleId', $scheduleId)
            ->where('deleteStatus', 1)
            ->orderByDesc('happenedOn')->orderByDesc('id')
            ->limit($limit)->get();

        $items = AsInventoryItem::whereIn('id', $moves->pluck('itemId')->unique()->all() ?: [0])
            ->get()->keyBy('id');
        foreach ($moves as $m) {
            $m->setRelation('item', $items->get($m->itemId));
        }

        return $moves;
    }

    /** Everything the module and the pickers need, as JSON. */
    public function listAll(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));

        /* The season's past, for the Start question. A first count on a board
         * with ticked activities has to ask WHEN counting begins — and "from
         * the beginning" needs to know where the beginning is. */
        $done = \App\Models\AsScheduleActivity::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->where('isDone', 1)->count();
        $first = \App\Models\AsScheduleActivity::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->whereNotNull('targetDate')
            ->min('targetDate');

        return $this->jsonOk('ok', ['data' => [
            'items' => $this->itemsPayload($schedule->id),
            'moves' => $this->movesPayload($schedule->id),
            'doneActivities' => $done,
            'firstActivityDate' => $first ? substr((string) $first, 0, 10) : null,
        ]]);
    }

    private function itemsPayload(int $scheduleId): array
    {
        $onHand = $this->stock->onHandFor($scheduleId);

        return AsInventoryItem::where('croppingScheduleId', $scheduleId)
            ->where('deleteStatus', 1)->orderBy('name')->get()
            ->map(function ($i) use ($onHand) {
                $have = $onHand[$i->id] ?? 0.0;

                return [
                    'id' => $i->id,
                    'name' => $i->name,
                    'kind' => $i->kind,
                    'kindLabel' => $i->kindLabel(),
                    'icon' => $i->icon(),
                    'unit' => $i->unit,
                    // Said here, not worked out there: the browser prints the
                    // unit in four places and none of them should own a second
                    // copy of the vocabulary.
                    'unitLabel' => $i->unitLabel(),
                    'unitOne' => AsInventoryItem::unitSays($i->unit, true),
                    // The units a move may be typed in: this one's dimension,
                    // this one first. One entry means no picker to draw.
                    'kin' => AsInventoryItem::kin($i->unit),
                    // The same kin with its words, for pickers that store
                    // words: the label round-trips through unitKeyFromWords.
                    // toItem: one of THIS unit, in the item's own unit — the
                    // activity sheet uses it to restate the shed's price per
                    // whatever unit the line is typed in.
                    'kinSays' => array_map(
                        fn ($k) => [
                            'key' => $k,
                            'says' => AsInventoryItem::unitSays($k, false),
                            'toItem' => AsInventoryItem::convert(1, $k, $i->unit),
                        ],
                        AsInventoryItem::kin($i->unit)
                    ),
                    'lowAt' => $i->lowAt ? (float) $i->lowAt : null,
                    'unitPrice' => $i->unitPrice !== null ? (float) $i->unitPrice : null,
                    'note' => $i->note,
                    'onHand' => $have,
                    // Whether the book has begun — the first stock-in is asked
                    // its Start question only while this is false.
                    // The birth line does not count: an item whose only move
                    // is its own creation has not begun its book, and its
                    // first stock-in still gets the Start question.
                    'hasMoves' => $i->moves()->where('reason', '!=', AsInventoryMove::CREATED)->exists(),
                    'says' => $i->say($have),
                    // Below the line they said to watch for — the shelf marks
                    // it, and so does the picker in the activity sheet.
                    'isLow' => (float) $i->lowAt > 0 && $have <= (float) $i->lowAt,
                ];
            })->values()->all();
    }

    private function movesPayload(int $scheduleId): array
    {
        return $this->recentMoves($scheduleId)->map(fn ($m) => [
            'id' => $m->id,
            'itemId' => $m->itemId,
            'itemName' => $m->item->name ?? 'Removed item',
            'icon' => $m->item?->icon() ?? '📦',
            'unit' => $m->item ? $m->item->unitLabel() : '',
            'delta' => (float) $m->delta,
            'before' => (float) $m->qtyBefore,
            'after' => (float) $m->qtyAfter,
            'saysBefore' => $m->item ? $m->item->say((float) $m->qtyBefore) : (string) $m->qtyBefore,
            'saysAfter' => $m->item ? $m->item->say((float) $m->qtyAfter) : (string) $m->qtyAfter,
            'saysDelta' => $m->item ? $m->item->say(abs((float) $m->delta)) : (string) abs((float) $m->delta),
            'isIn' => $m->isIn(),
            'reason' => $m->reason,
            'reasonLabel' => $m->reasonLabel(),
            'reasonIcon' => $m->reasonIcon(),
            'activityId' => $m->activityId,
            'on' => $m->happenedOn?->format('Y-m-d'),
            'onSays' => $m->happenedOn?->format('M j, Y'),
            'note' => $m->note,
            // "typed as 20 kg" — only when the hand's unit was not the book's.
            'typedSays' => ($m->enteredQty !== null && $m->enteredUnit)
                ? AsInventoryItem::trim((float) $m->enteredQty) . ' ' . AsInventoryItem::unitSays($m->enteredUnit, abs((float) $m->enteredQty) == 1.0)
                : null,
            // The raw typed figures too, so an edit form opens showing what
            // the hand wrote rather than the book's conversion of it.
            'enteredQty' => $m->enteredQty !== null ? (float) $m->enteredQty : null,
            'enteredUnit' => $m->enteredUnit,
            'boardSort' => $m->boardSort,
        ])->values()->all();
    }

    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $v = Validator::make($request->all(), $this->rules());
        if ($v->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $v->errors()]);
        }

        $item = AsInventoryItem::create($this->fields($request) + [
            'croppingScheduleId' => $schedule->id,
            'deleteStatus' => 1,
        ]);

        /* An opening count is part of adding the thing, not a second errand.
         * Nobody adds Urea to a list in order to say they have none of it. */
        /* The birth certificate. Without it the log's first line was stock
         * arriving from nowhere; with it the book starts where the shed did.
         * delta 0 on purpose — joining the list is not stock — and written
         * directly because move() rightly refuses a move of nothing. */
        /* Dated to the chosen counting start when one was given — the birth
         * line is the start marker for a book that opens with no stock. */
        $countFrom = $request->input('countFrom');
        AsInventoryMove::create([
            'croppingScheduleId' => $schedule->id,
            'itemId' => $item->id,
            'delta' => 0,
            'qtyBefore' => 0,
            'qtyAfter' => 0,
            'reason' => AsInventoryMove::CREATED,
            'happenedOn' => $countFrom ?: now('Asia/Manila')->toDateString(),
            'byUserId' => \Illuminate\Support\Facades\Auth::id(),
            'deleteStatus' => 1,
        ]);

        $opening = (float) $request->input('opening', 0);
        if ($opening <= 0 && $countFrom) {
            /* No stock, but a start: the season's done activities that name
             * this item come off the count from that day. The balance can run
             * honestly negative — the farm used what nobody has recorded
             * receiving yet, and a book that hides that is lying politely. */
            $this->stock->startCount($item, 0, $countFrom);
        }
        if ($opening > 0) {
            /* The Start, not just a move: the book begins here. startCount
             * also adopts activity lines that name this item and spends what
             * done activities used from this day forward — a book started
             * mid-season owes the season its past. openingNote is about this
             * arrival and lands on the Start line; `note` stayed on the item. */
            $this->stock->startCount(
                $item,
                $opening,
                // The move sheet says `on`; the item sheet's Start chooser
                // says `countFrom`. Either names the day the count begins.
                $request->input('on') ?: $request->input('countFrom'),
                trim((string) $request->input('openingNote')) ?: null,
            );
        }

        return $this->jsonOk(
            $opening > 0
                ? $item->name . ' added — ' . $item->say($opening) . ' on hand.'
                : 'Added to the inventory.',
            ['data' => $this->oneItem($item->fresh())]
        );
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $item = $this->itemOf($schedule->id, $this->queryId($request));
        if (! $item) {
            return $this->jsonFail('Item not found.', 404);
        }
        $v = Validator::make($request->all(), $this->rules());
        if ($v->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $v->errors()]);
        }

        $item->update($this->fields($request));

        return $this->jsonOk('Saved.', ['data' => $this->oneItem($item->fresh())]);
    }

    /**
     * Take an item off the shelf.
     *
     * Its moves are left where they are: the log is a record of what
     * happened, and a season's history should not thin out because somebody
     * stopped stocking something. The log names it "Removed item" if the row
     * is ever read back.
     */
    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $item = $this->itemOf($schedule->id, $this->queryId($request));
        if (! $item) {
            return $this->jsonFail('Item not found.', 404);
        }
        $item->update(['deleteStatus' => 0]);

        return $this->jsonOk('Removed from the inventory.');
    }

    /**
     * Move stock by hand — a delivery, a use, an opening count, a correction.
     *
     * One endpoint for all four because they are one act with a sign on it,
     * and splitting them would mean four places that have to remember to
     * write down what the stock was before.
     */
    public function moveStock(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $v = Validator::make($request->all(), [
            'itemId' => 'required|integer',
            'qty' => 'required|numeric|min:0.001|max:9999999',
            // The unit the amount was TYPED in — any unit of the item's
            // dimension. Converted here, because arithmetic about stock is
            // the book's to do, not the browser's.
            'unit' => 'nullable|string|max:20',
            'direction' => 'required|in:in,out',
            'reason' => 'nullable|in:open,in,out,adjust',
            'on' => 'nullable|date',
            'note' => 'nullable|string|max:500',
        ]);
        if ($v->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $v->errors()]);
        }

        $item = $this->itemOf($schedule->id, (int) $request->input('itemId'));
        if (! $item) {
            return $this->jsonFail('Item not found.', 404);
        }

        $in = $request->input('direction') === 'in';
        $qty = abs((float) $request->input('qty'));

        /* Typed in kilos, counted in bags: convert on the way in. A unit the
         * item's dimension does not contain is refused with the reason said,
         * not silently miscounted. */
        $fromUnit = (string) ($request->input('unit') ?: $item->unit);
        $converted = AsInventoryItem::convert($qty, $fromUnit, $item->unit);
        if ($converted === null) {
            return $this->jsonFail(
                AsInventoryItem::unitSays($fromUnit, false) . ' cannot be counted into '
                    . AsInventoryItem::unitSays($item->unit, false) . ' — different kinds of amount.',
                422
            );
        }
        $qty = round($converted, 3);
        if ($qty < 0.001) {
            return $this->jsonFail('That amount rounds to nothing in ' . AsInventoryItem::unitSays($item->unit, false) . '.', 422);
        }
        $reason = $request->input('reason') ?: ($in ? AsInventoryMove::IN : AsInventoryMove::OUT);

        /* A first count phrased as a move is still the Start. The sheet sends
         * reason=open for an item whose book has not begun; that path runs
         * the whole beginning — adoption, retro-spend — not just one line. */
        // The typed figure rides along whenever it differed from the book's
        // unit — "−0.4 bags" is arithmetic, "typed as 20 kg" is memory.
        $entered = $fromUnit !== $item->unit
            ? ['qty' => abs((float) $request->input('qty')), 'unit' => $fromUnit]
            : null;

        $move = $reason === AsInventoryMove::OPEN
            ? $this->stock->startCount($item, $qty, $request->input('on'), $request->input('note'))
            : $this->stock->move(
                $item,
                $in ? $qty : -$qty,
                $reason,
                $request->input('on'),
                $request->input('note'),
                null,
                $entered,
            );
        if ($move && $entered && $reason === AsInventoryMove::OPEN) {
            // The Start goes through startCount, which has no entered slot;
            // the row is stamped after the fact rather than widening every
            // signature between here and there.
            $move->update($entered ? ['enteredQty' => $entered['qty'], 'enteredUnit' => $entered['unit']] : []);
        }

        $have = $this->stock->onHand($item->id);

        return $this->jsonOk(
            ($in ? 'Added to ' : 'Taken from ') . $item->name . ' — ' . $item->say($have) . ' left.',
            ['data' => [
                'move' => $move ? $this->movesPayload($schedule->id)[0] ?? null : null,
                'item' => $this->oneItem($item),
            ]]
        );
    }

    /**
     * Move the Start — a different day, a different amount, or both.
     *
     * The old Start and the old retroactive spends are removed and rebuilt
     * from the new answer; hand-typed deliveries and uses survive, because
     * they happened regardless of where the book opens. The ledger's
     * before/after readings are renumbered so the book agrees with itself.
     */
    public function restart(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $v = Validator::make($request->all(), [
            'itemId' => 'required|integer',
            // Zero allowed: a book may open with nothing on the shelf.
            'qty' => 'required|numeric|min:0|max:9999999',
            'on' => 'required|date',
        ]);
        if ($v->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $v->errors()]);
        }

        $item = $this->itemOf($schedule->id, (int) $request->input('itemId'));
        if (! $item) {
            return $this->jsonFail('Item not found.', 404);
        }

        $this->stock->restart($item, abs((float) $request->input('qty')), $request->input('on'));
        $have = $this->stock->onHand($item->id);

        return $this->jsonOk(
            $item->name . ' now starts on ' . date('M j, Y', strtotime($request->input('on')))
                . ' — ' . $item->say($have) . ' on hand after what the season used.',
            ['data' => ['item' => $this->oneItem($item)]]
        );
    }

    /** Undo one hand-typed move. Activity moves are undone by unticking. */
    public function deleteMove(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $move = AsInventoryMove::where('croppingScheduleId', $schedule->id)
            ->where('id', $this->queryId($request))
            ->where('deleteStatus', 1)->first();
        if (! $move) {
            return $this->jsonFail('That entry is gone already.', 404);
        }
        if ($move->reason === AsInventoryMove::ACTIVITY) {
            return $this->jsonFail(
                'This one came from an activity being marked done. Untick the activity to take it back.',
                422
            );
        }
        $move->update(['deleteStatus' => 0]);

        return $this->jsonOk('Entry removed.');
    }

    /**
     * Amend one hand-typed move — amount (typed in any kin unit), day, note.
     * With only `on`, it is a plain re-dating: the board's drag. Activity
     * lines belong to their activity, and the Start keeps its own editor
     * because its date decides what the season retro-spends.
     */
    public function updateMove(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $v = Validator::make($request->all(), [
            'id' => 'required|integer',
            'qty' => 'nullable|numeric|min:0.001|max:9999999',
            'unit' => 'nullable|string|max:20',
            'on' => 'nullable|date',
            'note' => 'nullable|string|max:500',
            'boardSort' => 'nullable|integer',
        ]);
        if ($v->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $v->errors()]);
        }

        $move = AsInventoryMove::where('croppingScheduleId', $schedule->id)
            ->where('id', (int) $request->input('id'))
            ->where('deleteStatus', 1)->first();
        if (! $move) {
            return $this->jsonFail('That entry is gone already.', 404);
        }

        /* A drop between the day's cards moves no figure, so the chain
         * stays untouched — which is also why the Start may take a seat
         * here even though its numbers are edited elsewhere. */
        if ($request->has('boardSort') && ! $request->filled('qty') && ! $request->filled('on') && ! $request->has('note')) {
            if (in_array($move->reason, [AsInventoryMove::ACTIVITY, AsInventoryMove::CREATED], true)) {
                return $this->jsonFail('Only hand-typed lines sit on the board.', 422);
            }
            $move->update(['boardSort' => $request->input('boardSort') === null ? null : (int) $request->input('boardSort')]);

            return $this->jsonOk('Entry placed.');
        }

        if (! in_array($move->reason, [AsInventoryMove::IN, AsInventoryMove::OUT, AsInventoryMove::ADJUST], true)) {
            return $this->jsonFail(
                $move->reason === AsInventoryMove::ACTIVITY
                    ? 'This one came from an activity being marked done. Edit the activity instead.'
                    : 'The Start is edited from its own editor — its date decides what the season used.',
                422
            );
        }
        $item = $this->itemOf($schedule->id, (int) $move->itemId);
        if (! $item) {
            return $this->jsonFail('Item not found.', 404);
        }

        $delta = (float) $move->delta;
        $entered = ($move->enteredQty !== null && $move->enteredUnit)
            ? ['qty' => (float) $move->enteredQty, 'unit' => $move->enteredUnit]
            : null;
        if ($request->filled('qty')) {
            $typed = abs((float) $request->input('qty'));
            $fromUnit = (string) ($request->input('unit') ?: $item->unit);
            $converted = AsInventoryItem::convert($typed, $fromUnit, $item->unit);
            if ($converted === null) {
                return $this->jsonFail(
                    AsInventoryItem::unitSays($fromUnit, false) . ' cannot be counted into '
                        . AsInventoryItem::unitSays($item->unit, false) . ' — different kinds of amount.',
                    422
                );
            }
            $qty = round($converted, 3);
            if ($qty < 0.001) {
                return $this->jsonFail('That amount rounds to nothing in ' . AsInventoryItem::unitSays($item->unit, false) . '.', 422);
            }
            // The sign is the line's own: an arrival stays an arrival.
            $delta = $delta < 0 ? -$qty : $qty;
            $entered = $fromUnit !== $item->unit ? ['qty' => $typed, 'unit' => $fromUnit] : null;
        }

        /* A line carried to another day gives its seat up — the number was
         * relative to neighbours it no longer has — unless the drop named a
         * new seat on the new day. */
        $dayChanged = $request->filled('on')
            && substr((string) $request->input('on'), 0, 10) !== $move->happenedOn?->format('Y-m-d');
        $boardSort = $request->has('boardSort')
            ? ($request->input('boardSort') === null ? null : (int) $request->input('boardSort'))
            : ($dayChanged ? null : $move->boardSort);

        $this->stock->amend(
            $move,
            $delta,
            $request->input('on') ?: null,
            $request->has('note') ? (trim((string) $request->input('note')) ?: null) : $move->note,
            $entered,
            $boardSort
        );

        return $this->jsonOk('Entry updated — ' . $item->say($this->stock->onHand($item->id)) . ' on hand.');
    }

    private function itemOf(int $scheduleId, $id): ?AsInventoryItem
    {
        return AsInventoryItem::where('croppingScheduleId', $scheduleId)
            ->where('id', $id)->where('deleteStatus', 1)->first();
    }

    private function oneItem(AsInventoryItem $item): array
    {
        $have = $this->stock->onHand($item->id);

        return [
            'id' => $item->id,
            'name' => $item->name,
            'kind' => $item->kind,
            'kindLabel' => $item->kindLabel(),
            'icon' => $item->icon(),
            'unit' => $item->unit,
            'unitLabel' => $item->unitLabel(),
            'unitOne' => AsInventoryItem::unitSays($item->unit, true),
            'lowAt' => $item->lowAt ? (float) $item->lowAt : null,
            'unitPrice' => $item->unitPrice !== null ? (float) $item->unitPrice : null,
            'note' => $item->note,
            'onHand' => $have,
            'says' => $item->say($have),
            'isLow' => (float) $item->lowAt > 0 && $have <= (float) $item->lowAt,
        ];
    }

    private function fields(Request $request): array
    {
        $kind = $request->input('kind');
        $unit = $request->input('unit');

        $kind = isset(AsInventoryItem::KINDS[$kind]) ? $kind : 'other';

        return [
            'name' => trim((string) $request->input('name')),
            'kind' => $kind,
            /* One unit, and it has to be one this kind is actually offered.
             * A unit the form never showed is a unit somebody typed at the
             * API, and the honest answer to that is the kind's own default
             * rather than a number counted in nothing. */
            'unit' => isset(AsInventoryItem::UNITS[$unit])
                ? $unit
                : (AsInventoryItem::unitsFor($kind)[0] ?? 'kg'),
            'lowAt' => $request->filled('lowAt') ? (float) $request->input('lowAt') : null,
            // What one unit costs, for the expense report this will feed.
            // Optional and per item: the farm buys the same bag at the same
            // price all season.
            'unitPrice' => $request->filled('unitPrice') ? (float) $request->input('unitPrice') : null,
            'note' => $request->filled('note') ? trim((string) $request->input('note')) : null,
        ];
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'kind' => 'nullable|string|max:30',
            'unit' => 'nullable|string|max:20',
            'lowAt' => 'nullable|numeric|min:0|max:9999999',
            'unitPrice' => 'nullable|numeric|min:0|max:99999999',
            'note' => 'nullable|string|max:500',
            'opening' => 'nullable|numeric|min:0|max:9999999',
            // The day the opening count was taken. Sent when an item is being
            // created from a day on the board, where the date is already known
            // and is the part people get wrong coming back to it on Friday.
            'on' => 'nullable|date',
            // When counting begins, for an item created with no stock on a
            // board that has a past. The opening's `on` wins when both come.
            'countFrom' => 'nullable|date',
            // About that arrival, not about the thing. See store().
            'openingNote' => 'nullable|string|max:500',
        ];
    }
}
