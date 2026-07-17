<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleCriticalRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CriticalRuleController extends BaseScheduleController
{
    /**
     * Append a critical rule to the schedule's reminder list. Returns
     * the new row so the UI can add it to the list without a full-page
     * reload.
     */
    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'ruleText' => 'required|string|max:2000',
        ], [
            'ruleText.required' => 'Enter the rule text.',
            'ruleText.max'      => 'Rule text is too long (max 2000 chars).',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $maxOrder = (int) AsScheduleCriticalRule::active()
            ->where('croppingScheduleId', $schedule->id)
            ->max('sortOrder');

        $row = AsScheduleCriticalRule::create([
            'croppingScheduleId' => $schedule->id,
            'ruleText'           => trim($request->input('ruleText')),
            'sortOrder'          => $maxOrder + 1,
            'deleteStatus'       => 1,
        ]);

        return $this->jsonOk('Rule added.', ['data' => $row]);
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $validator = Validator::make($request->all(), [
            'ruleText' => 'required|string|max:2000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $row = AsScheduleCriticalRule::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$row) return $this->jsonFail('Rule not found.', 404);

        $row->update(['ruleText' => trim($request->input('ruleText'))]);
        return $this->jsonOk('Rule updated.', ['data' => $row->fresh()]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $row = AsScheduleCriticalRule::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$row) return $this->jsonFail('Rule not found.', 404);

        $row->update(['deleteStatus' => 0]);
        return $this->jsonOk('Rule deleted.');
    }

    /**
     * Batch sortOrder update from a drag-drop / arrow-button reorder.
     * Body: items: [{ id, sortOrder }, ...]
     */
    public function reorder(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'items'             => 'required|array|min:1',
            'items.*.id'        => 'required|integer',
            'items.*.sortOrder' => 'required|integer|min:0',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $items    = (array) $request->input('items');
        $ids      = array_map(fn ($it) => (int) $it['id'], $items);
        $validIds = AsScheduleCriticalRule::active()
            ->where('croppingScheduleId', $schedule->id)
            ->whereIn('id', $ids)
            ->pluck('id')->all();
        $validSet = array_flip($validIds);

        try {
            DB::transaction(function () use ($items, $validSet) {
                foreach ($items as $it) {
                    $id = (int) $it['id'];
                    if (!isset($validSet[$id])) continue;
                    AsScheduleCriticalRule::where('id', $id)
                        ->update(['sortOrder' => (int) $it['sortOrder']]);
                }
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to reorder: ' . $e->getMessage(), 500);
        }

        return $this->jsonOk('Order saved.', ['count' => count($items)]);
    }
}
