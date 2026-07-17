<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleDefaultGrouping;
use App\Models\AsScheduleDefaultGroupingLot;
use App\Models\AsScheduleLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Default lot groupings — ported verbatim from the mother app.
 * Replace-all semantics with HARD delete (groupings are config, not history).
 */
class DefaultGroupingController extends BaseScheduleController
{
    /**
     * Replace this schedule's default groupings with the submitted list.
     *
     * Body shape:
     *   groupings: [
     *     { name: "Group 1", staggerDays: 0, startDate: null, lotIds: [1, 2] },
     *     ...
     *   ]
     */
    public function save(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'groupings'                 => 'nullable|array',
            'groupings.*.name'          => 'required_with:groupings|string|max:255',
            'groupings.*.staggerDays'   => 'nullable|integer|min:0',
            'groupings.*.startDate'     => 'nullable|date',
            'groupings.*.lotIds'        => 'nullable|array',
            'groupings.*.lotIds.*'      => 'integer',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $validLotIds = AsScheduleLot::active()
            ->where('croppingScheduleId', $schedule->id)
            ->pluck('id')->all();

        try {
            DB::transaction(function () use ($schedule, $request, $validLotIds) {
                // Hard-delete prior defaults — they're config, not historical data.
                $existingIds = AsScheduleDefaultGrouping::where('croppingScheduleId', $schedule->id)
                    ->pluck('id');
                AsScheduleDefaultGroupingLot::whereIn('defaultGroupingId', $existingIds)->delete();
                AsScheduleDefaultGrouping::whereIn('id', $existingIds)->delete();

                foreach ((array) $request->input('groupings', []) as $idx => $g) {
                    if (empty($g['name'])) continue;

                    $startDate = !empty($g['startDate']) ? $g['startDate'] : null;
                    $grouping = AsScheduleDefaultGrouping::create([
                        'croppingScheduleId' => $schedule->id,
                        'groupName'          => $g['name'],
                        'staggerDays'        => $startDate ? 0 : (int) ($g['staggerDays'] ?? 0),
                        'startDate'          => $startDate,
                        'groupOrder'         => $idx,
                        'deleteStatus'       => 1,
                    ]);

                    foreach ((array) ($g['lotIds'] ?? []) as $lotId) {
                        if (!in_array((int) $lotId, $validLotIds, true)) continue;
                        AsScheduleDefaultGroupingLot::create([
                            'defaultGroupingId' => $grouping->id,
                            'lotId'             => (int) $lotId,
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to save default groupings: ' . $e->getMessage(), 500);
        }

        $fresh = $schedule->fresh(['defaultGroupings.lots']);

        return $this->jsonOk('Default groupings saved.', [
            'data' => $fresh->defaultGroupings->map(function ($g) {
                return [
                    'id'          => $g->id,
                    'groupName'   => $g->groupName,
                    'staggerDays' => $g->staggerDays,
                    'startDate'   => $g->startDate ? $g->startDate->format('Y-m-d') : null,
                    'groupOrder'  => $g->groupOrder,
                    'lotIds'      => $g->lots->pluck('id'),
                ];
            }),
        ]);
    }
}
