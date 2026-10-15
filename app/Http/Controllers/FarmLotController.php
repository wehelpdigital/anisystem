<?php

namespace App\Http\Controllers;

use App\Support\FarmLots;
use App\Support\FieldCatalogue;
use App\Support\FieldProfile;
use App\Support\WorkerContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Global Lots (2026-10-16, the owner): the farm's lots, each with its place,
 * size and soil, water and last season answers (App\Support\FarmLots), kept
 * here and picked by every analysis. The farm owner's lots
 * (WorkerContext::effectiveOwnerId), so a worker sees the farm's.
 */
class FarmLotController extends Controller
{
    public function page()
    {
        return view('farm-lots.index');
    }

    /** The farm's lots, the newest first, and how many are due for a check. */
    public function list()
    {
        $owner = WorkerContext::effectiveOwnerId();

        return $this->json(true, 'ok', [
            'rows' => FieldProfile::saved($owner, 200),
            'ready' => FarmLots::ready(),
            'stale' => FarmLots::staleCount($owner),
            'staleDays' => FarmLots::STALE_DAYS,
        ]);
    }

    public function save(Request $request)
    {
        return $this->keep($request, null);
    }

    public function update(Request $request, int $id)
    {
        return $this->keep($request, $id);
    }

    /** "Still correct": the six months start again, nothing else changes. */
    public function confirm(int $id)
    {
        $row = FarmLots::confirm(WorkerContext::effectiveOwnerId(), $id);

        return $row ? $this->json(true, 'Marked as checked. The next check is due on ' . $row['staleOn'] . '.', ['row' => $row])
            : $this->json(false, 'That lot is gone.', [], 404);
    }

    public function destroy(int $id)
    {
        return FarmLots::remove(WorkerContext::effectiveOwnerId(), $id)
            ? $this->json(true, 'Lot deleted.')
            : $this->json(false, 'That lot is gone.', [], 404);
    }

    /** The place pickers: provinces, the towns of a province, the barangays of a town. */
    public function places(Request $request)
    {
        try {
            $items = FarmLots::places($request->query('province'), $request->query('town'));
        } catch (\Throwable $e) {
            report($e);

            return $this->json(false, 'The places could not load. Please try again.', [], 503);
        }

        return $this->json(true, 'ok', ['items' => $items]);
    }

    private function keep(Request $request, ?int $id)
    {
        $v = Validator::make($request->all(), [
            'name' => ($id ? 'sometimes|' : '') . 'required|string|max:120',
            'province' => 'nullable|string|max:120',
            'town' => 'nullable|string|max:120',
            'barangay' => 'nullable|string|max:160',
            'areaHa' => 'nullable|numeric|min:0|max:100000',
            'crop' => 'nullable|string|max:40',
            'answers' => 'nullable|array',
            'lotId' => 'nullable|integer',
            'scheduleId' => 'nullable|integer',
        ]);
        if ($v->fails()) {
            return $this->json(false, $v->errors()->first(), [], 422);
        }
        if (! FarmLots::ready()) {
            return $this->json(false, 'Lots are being set up. Please try again in a few minutes.', [], 503);
        }
        if ($request->filled('crop') && ! isset(FieldCatalogue::crops()[FieldCatalogue::cropKey((string) $request->input('crop')) ?? ''])) {
            return $this->json(false, 'Pick a crop from the list.', [], 422);
        }
        $keys = array_values(array_filter(['name', 'province', 'town', 'barangay', 'areaHa', 'crop', 'answers', 'lotId', 'scheduleId'], fn ($k) => $request->exists($k)));
        $row = FarmLots::save(WorkerContext::effectiveOwnerId(), $request->only($keys), $id);
        if (! $row) {
            return $this->json(false, 'That lot is gone.', [], 404);
        }

        return $this->json(true, $id ? 'Lot saved.' : 'Lot added.', ['row' => $row]);
    }

    private function json(bool $ok, string $message, array $data = [], int $status = 200)
    {
        return response()->json(['success' => $ok, 'message' => $message, 'data' => $data], $status);
    }
}
