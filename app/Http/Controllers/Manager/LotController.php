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
     * How this lot counts its days.
     *
     * 'DAT' is the two-phase one — DAS from day zero, then a fresh DAT count
     * from the transplant date. 'DAS' is direct seeding (DSR), a single count
     * that never flips. 'DAP' is everything planted rather than sown. Anything
     * unrecognised falls back to the two-phase count, which is what the single
     * old "DAS / DAT" option meant.
     */
    private static function dayType($value): string
    {
        $v = strtoupper(trim((string) $value));

        return in_array($v, ['DAP', 'DAS', 'DAT', 'TREE'], true) ? $v : 'DAT';
    }

    /**
     * The two facts that only apply to one kind of crop each.
     *
     * Days to maturity belongs to something that is going to be harvested
     * once; a planting date belongs to something that will still be here
     * next season. Storing both would leave whichever one no longer applies
     * sitting in the row, quietly wrong, waiting to be read — so the one
     * that does not apply is cleared when the crop is set.
     */
    private function cropTiming(Request $request): array
    {
        $crop = \App\Support\CropStages::normalize($request->input('crop'));
        $tree = $crop && \App\Support\CropStages::isPerennial($crop);

        $days = (int) $request->input('daysToMaturity', 0);

        return [
            'crop' => $crop,
            'daysToMaturity' => (! $tree && $days > 0) ? min(999, $days) : null,
            'treePlantedAt' => $tree && $request->filled('treePlantedAt')
                ? $request->input('treePlantedAt') : null,
            // A tree is not counted in days from anything, so its lot says so.
            'dayType' => $tree ? 'TREE' : self::dayType($request->input('dayType')),
        ];
    }

    /** A lot as the module reads it, crop spelled out for the card badge. */
    private function lotPayload($lot): array
    {
        return $lot->toArray() + [
            'cropLabel' => \App\Support\CropStages::label($lot->crop),
            'cropIcon' => \App\Support\CropStages::icon($lot->crop),
            'cropIsTree' => \App\Support\CropStages::isPerennial($lot->crop),
            // What the stages will actually be read against, whether the lot
            // said so itself or the crop's own figure stood in.
            'maturityDays' => $lot->maturityDays(),
            'treeAgeMonths' => $lot->treeAgeMonths(),
            // Where it is, and the one link that acts on that.
            'pinned' => $lot->isPinned(),
            'mapsHref' => $lot->mapsHref(),
        ];
    }

    /**
     * Module page: GET /app/sm-lots?id={scheduleId}
     */
    public function page(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $schedule->load(['lots', 'defaultGroupings.lots']);

        return view('sm.lots', compact('schedule'));
    }

    /**
     * Put a lot on the map, or take it off.
     *
     * Called from the map itself, the moment a pin goes down while a lot is
     * being attached — not from a form. Standing in a field with a phone,
     * the gesture is "drop it here", and anything between that gesture and
     * the record is a step that gets skipped.
     *
     * Sending no coordinates unpins the lot, which is how somebody undoes a
     * pin dropped on the wrong side of the road.
     */
    public function pin(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $data = Validator::make($request->all(), [
            'lotId' => 'required|integer',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'label' => 'nullable|string|max:120',
            'mapSaveId' => 'nullable|integer',
        ])->validate();

        $lot = AsScheduleLot::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->find($data['lotId']);
        if (! $lot) {
            return $this->jsonFail('That lot is not on this schedule.', 404);
        }

        // Both or neither. Half a coordinate is a lot that claims to be
        // findable and is not, which is worse than one that never claimed it.
        $has = isset($data['lat'], $data['lng']) && $data['lat'] !== null && $data['lng'] !== null;
        $lot->update([
            'pinLat' => $has ? $data['lat'] : null,
            'pinLng' => $has ? $data['lng'] : null,
            'pinLabel' => $has ? ($data['label'] ?? null) : null,
            'mapSaveId' => $has ? ($data['mapSaveId'] ?? $lot->mapSaveId) : null,
        ]);

        return $this->jsonOk($has ? 'Lot pinned on the map.' : 'Pin removed.',
            ['data' => $this->lotPayload($lot->fresh())]);
    }

    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $lot = AsScheduleLot::create(array_merge(
            [
                'croppingScheduleId' => $schedule->id,
                'lotName'            => $request->lotName,
                'lotSize'            => $request->lotSize,
                'lotSizeUnit'        => $request->lotSizeUnit,
                'variety'            => $request->filled('variety') ? trim($request->variety) : null,
                'notes'              => $request->notes,
                'deleteStatus'       => 1,
            ],
            $this->cropTiming($request),
            $this->addressFields($request)
        ));

        return $this->jsonOk('Lot added.', ['data' => $this->lotPayload($lot)]);
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $lot = AsScheduleLot::active()->where('croppingScheduleId', $schedule->id)->where('id', $id)->first();
        if (!$lot) return $this->jsonFail('Lot not found.', 404);

        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $lot->update(array_merge(
            [
                'lotName'     => $request->lotName,
                'lotSize'     => $request->lotSize,
                'lotSizeUnit' => $request->lotSizeUnit,
                'variety'     => $request->filled('variety') ? trim($request->variety) : null,
                'notes'       => $request->notes,
            ],
            $this->cropTiming($request),
            $this->addressFields($request)
        ));

        return $this->jsonOk('Lot updated.', ['data' => $this->lotPayload($lot)]);
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

    /** Validation shared by store/update. */
    private function rules(): array
    {
        return [
            'lotName'     => 'required|string|max:255',
            'lotSize'     => 'required|numeric|min:0',
            'lotSizeUnit' => 'required|string|max:50',
            'variety'     => 'nullable|string|max:255',
            // A key from the crop catalogue; anything else is simply not a
            // crop we have stages for, so it is dropped rather than stored.
            'crop'        => 'nullable|string|max:60',
            // Optional: left empty, the crop's own typical figure stands.
            'daysToMaturity' => 'nullable|integer|min:1|max:999',
            'treePlantedAt' => 'nullable|date',
            'locBarangay' => 'nullable|string|max:120',
            'locZone'     => 'nullable|string|max:60',
            'locTown'     => 'nullable|string|max:120',
            'locProvince' => 'nullable|string|max:120',
            'dayType'     => 'nullable|in:DAP,DAS,DAT,TREE',
            'notes'       => 'nullable|string|max:2000',
        ];
    }

    /** Normalised address columns (trimmed, blanks → null). */
    private function addressFields(Request $request): array
    {
        return collect(['locBarangay', 'locZone', 'locTown', 'locProvince'])
            ->mapWithKeys(fn ($f) => [$f => $request->filled($f) ? trim($request->input($f)) : null])
            ->all();
    }
}
