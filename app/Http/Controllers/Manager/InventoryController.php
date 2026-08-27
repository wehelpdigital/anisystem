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

        return $this->jsonOk('ok', ['data' => [
            'items' => $this->itemsPayload($schedule->id),
            'moves' => $this->movesPayload($schedule->id),
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
                    'packSize' => $i->packSize ? (float) $i->packSize : null,
                    'packLabel' => $i->packLabel,
                    'lowAt' => $i->lowAt ? (float) $i->lowAt : null,
                    'note' => $i->note,
                    'onHand' => $have,
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
            'unit' => $m->item->unit ?? '',
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
        $opening = (float) $request->input('opening', 0);
        if ($opening > 0) {
            $this->stock->move($item, $opening, AsInventoryMove::OPEN, null, 'Opening stock');
        }

        return $this->jsonOk('Added to the inventory.', ['data' => $this->oneItem($item)]);
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
        $reason = $request->input('reason') ?: ($in ? AsInventoryMove::IN : AsInventoryMove::OUT);

        $move = $this->stock->move(
            $item,
            $in ? $qty : -$qty,
            $reason,
            $request->input('on'),
            $request->input('note'),
        );

        $have = $this->stock->onHand($item->id);

        return $this->jsonOk(
            ($in ? 'Added to ' : 'Taken from ') . $item->name . ' — ' . $item->say($have) . ' left.',
            ['data' => [
                'move' => $move ? $this->movesPayload($schedule->id)[0] ?? null : null,
                'item' => $this->oneItem($item),
            ]]
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
            'packSize' => $item->packSize ? (float) $item->packSize : null,
            'packLabel' => $item->packLabel,
            'lowAt' => $item->lowAt ? (float) $item->lowAt : null,
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

        return [
            'name' => trim((string) $request->input('name')),
            'kind' => isset(AsInventoryItem::KINDS[$kind]) ? $kind : 'other',
            'unit' => in_array($unit, AsInventoryItem::UNITS, true) ? $unit : 'kg',
            // A pack is only a pack if it has both a size and a name for it —
            // "50" on its own says nothing, and "bag" on its own is not a
            // quantity anybody can add up.
            'packSize' => $request->filled('packSize') && $request->filled('packLabel')
                ? (float) $request->input('packSize') : null,
            'packLabel' => $request->filled('packSize') && $request->filled('packLabel')
                ? trim((string) $request->input('packLabel')) : null,
            'lowAt' => $request->filled('lowAt') ? (float) $request->input('lowAt') : null,
            'note' => $request->filled('note') ? trim((string) $request->input('note')) : null,
        ];
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'kind' => 'nullable|string|max:30',
            'unit' => 'nullable|string|max:20',
            'packSize' => 'nullable|numeric|min:0.001|max:999999',
            'packLabel' => 'nullable|string|max:30',
            'lowAt' => 'nullable|numeric|min:0|max:9999999',
            'note' => 'nullable|string|max:500',
            'opening' => 'nullable|numeric|min:0|max:9999999',
        ];
    }
}
