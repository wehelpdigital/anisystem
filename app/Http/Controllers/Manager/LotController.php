<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Lots — store/update/destroy ported verbatim from the mother app,
 * plus page() rendering the AniSystem mobile-first module page.
 */
class LotController extends BaseScheduleController
{
    /**
     * Module page: GET /app/sm-lots?id={scheduleId}
     */
    public function page(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $schedule->load(['lots', 'defaultGroupings.lots']);

        return view('sm.lots', compact('schedule'));
    }

    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'lotName'     => 'required|string|max:255',
            'lotSize'     => 'required|numeric|min:0',
            'lotSizeUnit' => 'required|string|max:50',
            'variety'     => 'nullable|string|max:255',
            'dayZeroDate' => 'nullable|date',
            'notes'       => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $lot = AsScheduleLot::create([
            'croppingScheduleId' => $schedule->id,
            'lotName'            => $request->lotName,
            'lotSize'            => $request->lotSize,
            'lotSizeUnit'        => $request->lotSizeUnit,
            'variety'            => $request->filled('variety') ? trim($request->variety) : null,
            'dayZeroDate'        => $request->filled('dayZeroDate') ? $request->dayZeroDate : null,
            'notes'              => $request->notes,
            'deleteStatus'       => 1,
        ]);

        return $this->jsonOk('Lot added.', ['data' => $lot]);
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $lot = AsScheduleLot::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$lot) return $this->jsonFail('Lot not found.', 404);

        $validator = Validator::make($request->all(), [
            'lotName'     => 'required|string|max:255',
            'lotSize'     => 'required|numeric|min:0',
            'lotSizeUnit' => 'required|string|max:50',
            'variety'     => 'nullable|string|max:255',
            'dayZeroDate' => 'nullable|date',
            'notes'       => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $lot->update([
            'lotName'     => $request->lotName,
            'lotSize'     => $request->lotSize,
            'lotSizeUnit' => $request->lotSizeUnit,
            'variety'     => $request->filled('variety') ? trim($request->variety) : null,
            'dayZeroDate' => $request->filled('dayZeroDate') ? $request->dayZeroDate : null,
            'notes'       => $request->notes,
        ]);

        return $this->jsonOk('Lot updated.', ['data' => $lot]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $lot = AsScheduleLot::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$lot) return $this->jsonFail('Lot not found.', 404);

        $lot->update(['deleteStatus' => 0]);

        return $this->jsonOk('Lot deleted.');
    }
}
