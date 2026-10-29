<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Lots — store/update/destroy ported verbatim from the mother app,
 * plus page() rendering the anee.io mobile-first module page.
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
            // The stage table the Activities board reads this lot against, so
            // a board already open learns a new or re-cropped lot in place.
            'boardStages' => \App\Support\CropStages::boardTable($lot->crop, $lot->maturityDays()),
            // Where it is, and the one link that acts on that.
            'pinned' => $lot->isPinned(),
            'mapsHref' => $lot->mapsHref(),
            'harvestedSays' => $lot->harvestedAt?->format('M j, Y'),
            // Its farm lot in Global Lots, where its field answers live (App\Support\LotSync).
            'farmLot' => $this->farmLot ?? null,
        ];
    }

    /** The farm lot of the lot just saved, for its card. */
    private ?array $farmLot = null;

    /**
     * Module page: GET /app/sm-lots?id={scheduleId}
     */
    public function page(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        // Every lot of the season is one of the farm's lots, with the answers kept there (2026-10-30, the owner).
        $farmLots = \App\Support\LotSync::season($schedule);
        $schedule->load(['lots', 'defaultGroupings.lots']);

        return view('sm.lots', compact('schedule', 'farmLots'));
    }

    /**
     * One lot's own map.
     *
     * A page of its own rather than the Maps module with a lot's name bolted
     * to the top of it. The drawing engine is shared — there is one of those
     * in this app and there is going to go on being one — but nothing around
     * it is: no team heading, no live-position sharing, no clear-for-
     * everybody, no shelf of the team's plans. One lot, one question, one
     * Save.
     */
    public function map(Request $request)
    {
        $schedule = $this->schedule($request->query('id'));
        $lot = AsScheduleLot::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->findOrFail((int) $request->query('lot'));

        /* Where Back goes.
         *
         * Whatever opened this map said where it was standing, and that is
         * where the arrow returns to — the shell with the Lots pane open, or
         * the standalone Lots page, whichever it actually was.
         *
         * Only a path on this site is honoured. A `from` that can be anything
         * is an open redirect wearing a back arrow, and it is one line to not
         * have one.
         */
        $from = (string) $request->query('from', '');
        $backTo = (str_starts_with($from, '/') && ! str_starts_with($from, '//'))
            ? $from
            : route('sm.lots', ['id' => $schedule->id]);

        return view('sm.lot-map', [
            'backTo' => $backTo,
            'schedule' => $schedule,
            'lot' => $lot,
            // What the map itself needs to know about the errand: which lot,
            // and whether it already has a place.
            'attachLot' => [
                'id' => $lot->id,
                'name' => $lot->lotName,
                'pinned' => $lot->isPinned(),
                'lat' => $lot->pinLat,
                'lng' => $lot->pinLng,
                'label' => $lot->pinLabel,
                'mapSaveId' => $lot->mapSaveId,
            ],
        ]);
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
    /**
     * Tie a saved map to a lot WITHOUT touching its pin. The save flow
     * calls this for a lot that has no coordinates yet — the pin endpoint
     * reads "no lat/lng" as "remove the pin", which also wiped the link.
     */
    public function linkMap(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $data = Validator::make($request->all(), [
            'lotId' => 'required|integer',
            'mapSaveId' => 'required|integer',
        ])->validate();

        $lot = AsScheduleLot::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->find($data['lotId']);
        if (! $lot) {
            return $this->jsonFail('That lot is not in this season.', 404);
        }
        // Maps are their owners' now: any map the caller may open (their
        // own, or one this farm already uses) can be worn by a lot.
        $save = \App\Support\MapAccess::viewable((int) $data['mapSaveId']);
        if (! $save) {
            return $this->jsonFail('That saved map no longer exists.', 404);
        }

        $lot->update(['mapSaveId' => $save->id]);
        // The season uses it now, so its team can open it from the lot.
        \App\Support\MapAccess::link((int) $save->id, $schedule->id);

        return $this->jsonOk('Map attached to ' . $lot->lotName . '.', ['data' => $this->lotPayload($lot->fresh())]);
    }

    /**
     * The maps a lot may wear, for the Attach-a-map picker: the season's own
     * and every map of the caller's (see MapAccess::choices), newest first.
     */
    public function mapChoices(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $rows = \App\Support\MapAccess::choices($schedule->id)->orderByDesc('id')->limit(200)->get();

        return $this->jsonOk('Loaded.', ['data' => ['maps' => \App\Support\MapAccess::rows($rows)]]);
    }

    /**
     * Detach a lot's map: the lot forgets it. The map itself is its owner's
     * and may be worn by other lots and seasons, so it stays on their Maps
     * shelf; the pin (the lot's place on the ground) stays too.
     */
    public function detachMap(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $lot = AsScheduleLot::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->find((int) $request->input('lotId'));
        if (! $lot) {
            return $this->jsonFail('That lot is not in this season.', 404);
        }
        $lot->update(['mapSaveId' => null]);

        return $this->jsonOk('Map removed from ' . $lot->lotName . '. It is still in your Maps.', ['data' => $this->lotPayload($lot->fresh())]);
    }

    /**
     * The farmer's delay counter: the crop in this lot stands so many days
     * behind the calendar's count. Every activity's day chip then shows the
     * delayed count beside the calendar's; nothing else moves. Null (or 0)
     * clears it.
     */
    public function delay(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $data = Validator::make($request->all(), [
            'lotId' => 'required|integer',
            'delayDays' => 'nullable|integer|min:0|max:365',
        ])->validate();

        $lot = AsScheduleLot::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->find($data['lotId']);
        if (! $lot) {
            return $this->jsonFail('That lot is not in this season.', 404);
        }

        $days = (int) ($data['delayDays'] ?? 0);
        $lot->update(['delayDays' => $days > 0 ? $days : null]);

        return $this->jsonOk($days > 0 ? 'Delay set: ' . $days . ' day' . ($days === 1 ? '' : 's') . ' behind.' : 'Delay removed.',
            ['data' => $this->lotPayload($lot->fresh())]);
    }

    /**
     * Mark a lot harvested, with its harvest record (2026-10-22, the owner).
     *
     * The board's Harvest sheet sends the day and every harvest question the
     * Observations module asks (PostHarvestFields::HARVEST): what came off
     * the field, how good it was, the drying, the sale. Each part answered is
     * filed as its own observation on this lot, dated the harvest day, and
     * the lot keeps their ids so taking the mark back takes them too.
     *
     * Two pens: the lot is the plan's (assertCanEdit, through the resolver)
     * and the record is the Observations module's. A closed season refuses.
     */
    public function harvest(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        if (! \App\Support\WorkerContext::canWriteModule('observations')) {
            return $this->jsonFail('The farm owner has not given you Observations, where the harvest is written.', 403);
        }

        $rules = [
            'lotId' => 'required|integer',
            'harvestedAt' => 'required|date|before_or_equal:' . now('Asia/Manila')->addDay()->toDateString(),
            'notes' => 'nullable|string|max:5000',
            'answers' => 'nullable|array',
            'answers.yield.yieldAmount' => 'required|numeric|gt:0|max:99999999',
            'answers.yield.yieldUnit' => 'required|string|max:24',
        ];
        foreach (\App\Support\PostHarvestFields::HARVEST as $cat) {
            foreach (\App\Support\PostHarvestFields::for($cat) as $f) {
                $key = 'answers.' . $cat . '.' . $f['key'];
                $rules[$key] ??= match ($f['type']) {
                    'number', 'money' => 'nullable|numeric|min:0|max:99999999',
                    'percent' => 'nullable|numeric|min:0|max:100',
                    'select' => ['nullable', \Illuminate\Validation\Rule::in(array_keys($f['options'] ?? []))],
                    'unit' => 'nullable|string|max:24',
                    'textarea' => 'nullable|string|max:2000',
                    default => 'nullable|string|max:191',
                };
            }
        }
        $data = Validator::make($request->all(), $rules, [
            'answers.yield.yieldAmount.required' => 'Say how much was harvested.',
            'answers.yield.yieldAmount.gt' => 'Say how much was harvested.',
            'answers.yield.yieldUnit.required' => 'Say what the harvest was measured in (kg, sacks, cavans).',
            'harvestedAt.required' => 'Pick the day it was harvested.',
            'harvestedAt.before_or_equal' => 'The harvest day cannot be in the future.',
        ])->validate();

        $lot = AsScheduleLot::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->find($data['lotId']);
        if (! $lot) {
            return $this->jsonFail('That lot is not in this season.', 404);
        }
        if ($lot->isHarvested()) {
            return $this->jsonFail($lot->lotName . ' is already marked harvested.', 409);
        }

        $titles = [
            'yield' => 'harvest',
            'quality' => 'quality',
            'storage' => 'drying and storage',
            'market' => 'sale',
        ];
        $notes = trim((string) ($data['notes'] ?? ''));
        $ids = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $schedule, $lot, $titles, $notes) {
            $ids = [];
            foreach (\App\Support\PostHarvestFields::HARVEST as $cat) {
                [$columns, $details] = \App\Support\PostHarvestFields::split($cat, (array) ($data['answers'][$cat] ?? []));
                if (! $columns && ! $details && ! ($cat === 'yield' && $notes !== '')) {
                    continue;
                }
                // A sale said without its unit is in the harvest's unit.
                if ($cat === 'market' && isset($columns['yieldAmount']) && empty($columns['yieldUnit'])) {
                    $columns['yieldUnit'] = (string) ($data['answers']['yield']['yieldUnit'] ?? '');
                }
                $row = \App\Models\AsSchedulePostHarvest::create($columns + [
                    'croppingScheduleId' => $schedule->id,
                    'lotId' => $lot->id,
                    'observationDate' => $data['harvestedAt'],
                    'title' => mb_substr($lot->lotName . ' ' . $titles[$cat], 0, 191),
                    'category' => $cat,
                    'details' => $details ?: null,
                    // Words typed plainly, kept as the rich text the module's
                    // editor reads, on the harvest row where they were asked.
                    'notes' => $cat === 'yield' && $notes !== ''
                        ? \App\Support\HtmlSanitizer::rich('<p>' . nl2br(e($notes), false) . '</p>')
                        : null,
                    'deleteStatus' => 1,
                ]);
                $ids[] = (int) $row->id;
            }
            $lot->update(['harvestedAt' => $data['harvestedAt'], 'harvestObservationIds' => $ids]);

            return $ids;
        });

        return $this->jsonOk($lot->lotName . ' is harvested. ' . count($ids) . ' ' . (count($ids) === 1 ? 'observation' : 'observations') . ' saved.',
            ['data' => $this->harvestPayload($lot->fresh())]);
    }

    /**
     * Take the harvested mark back: the lot goes back to growing, and the
     * observations its harvest record wrote leave Observations with it, so a
     * lot marked by mistake and marked again is never counted twice. Rows
     * written by hand in the module are not touched.
     */
    public function unharvest(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        if (! \App\Support\WorkerContext::canWriteModule('observations')) {
            return $this->jsonFail('The farm owner has not given you Observations, where the harvest is written.', 403);
        }
        $data = Validator::make($request->all(), ['lotId' => 'required|integer'])->validate();

        $lot = AsScheduleLot::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->find($data['lotId']);
        if (! $lot) {
            return $this->jsonFail('That lot is not in this season.', 404);
        }

        $ids = array_map('intval', (array) ($lot->harvestObservationIds ?? []));
        $removed = $ids ? \App\Models\AsSchedulePostHarvest::where('croppingScheduleId', $schedule->id)
            ->where('lotId', $lot->id)->whereIn('id', $ids)->where('deleteStatus', 1)
            ->update(['deleteStatus' => 0]) : 0;
        $lot->update(['harvestedAt' => null, 'harvestObservationIds' => null]);

        return $this->jsonOk($lot->lotName . ' is back to growing.' . ($removed ? ' ' . $removed . ' harvest ' . ($removed === 1 ? 'observation' : 'observations') . ' removed.' : ''),
            ['data' => $this->harvestPayload($lot->fresh())]);
    }

    /** What the Harvest sheet repaints a lot's row from. */
    private function harvestPayload(AsScheduleLot $lot): array
    {
        return [
            'lotId' => (int) $lot->id,
            'harvestedAt' => $lot->harvestedAt?->format('Y-m-d'),
            'harvestedSays' => $lot->harvestedAt?->format('M j, Y'),
            'observations' => count((array) ($lot->harvestObservationIds ?? [])),
        ];
    }

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
            return $this->jsonFail('That lot is not in this season.', 404);
        }

        // Both or neither. Half a coordinate is a lot that claims to be
        // findable and is not, which is worse than one that never claimed it.
        $has = isset($data['lat'], $data['lng']) && $data['lat'] !== null && $data['lng'] !== null;
        /* A pin and a map are two different things a lot can have.
         *
         * Removing the pin used to take the map with it, which made "this
         * lot is no longer at that spot" also mean "and lose the drawing of
         * it". A lot's map is its own file with its own link (linkMap /
         * detachMap); the pin says where the lot is and nothing else. */
        $lot->update([
            'pinLat' => $has ? $data['lat'] : null,
            'pinLng' => $has ? $data['lng'] : null,
            'pinLabel' => $has ? ($data['label'] ?? null) : null,
            'mapSaveId' => $data['mapSaveId'] ?? $lot->mapSaveId,
        ]);

        return $this->jsonOk($has ? 'Lot pinned on the map.' : 'Pin removed.',
            ['data' => $this->lotPayload($lot->fresh())]);
    }

    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        // The tier's lot cap, judged by the schedule owner's plan.
        $lotCap = \App\Support\Tier::scheduleLimit($schedule, 'lotsPerSchedule');
        if ($lotCap !== null && AsScheduleLot::active()->where('croppingScheduleId', $schedule->id)->count() >= $lotCap) {
            \App\Support\Tier::scheduleDenyFor($schedule, 'lotsPerSchedule', 'This plan allows up to ' . $lotCap . ' lot' . ($lotCap == 1 ? '' : 's') . ' per season. Move up to {plan} for more.');
        }

        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->jsonFail('Please check what you entered.', 422, ['errors' => $validator->errors()]);
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

        if ($request->has('tags')) {
            \App\Support\ScheduleTags::sync($schedule, 'lot', (int) $lot->id, $request->input('tags', []));
        }
        $this->farmLot = \App\Support\LotSync::card(\App\Support\LotSync::afterSave($schedule, $lot->fresh()));

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
            return $this->jsonFail('Please check what you entered.', 422, ['errors' => $validator->errors()]);
        }

        /* What the lot IS was settled when it was made.
         *
         * cropTiming() answers four things and three of them — the crop, the
         * day counter and the days to maturity — are what every date on this
         * season's board was derived from. Changing one now would not move the
         * plan; it would quietly change what the plan means, mid-season, on
         * work already done. So they are not read here.
         *
         * The trees' age is the exception and is kept: nobody knows the day an
         * orchard went in, and correcting it moves nothing that was planned in
         * days.
         *
         * Ignored rather than refused — a client posting the whole form it has
         * is not misbehaving, and failing the save over a field nobody meant to
         * touch is the worse answer. The reply carries what is really stored.
         */
        $lot->update(array_merge(
            [
                'lotName'     => $request->lotName,
                'lotSize'     => $request->lotSize,
                'lotSizeUnit' => $request->lotSizeUnit,
                'variety'     => $request->filled('variety') ? trim($request->variety) : null,
                'notes'       => $request->notes,
            ],
            \App\Support\CropStages::isPerennial($lot->crop) && $request->filled('treePlantedAt')
                ? ['treePlantedAt' => $request->input('treePlantedAt')]
                : [],
            $this->addressFields($request)
        ));

        if ($request->has('tags')) {
            \App\Support\ScheduleTags::sync($schedule, 'lot', (int) $lot->id, $request->input('tags', []));
        }
        $this->farmLot = \App\Support\LotSync::card(\App\Support\LotSync::afterSave($schedule, $lot->fresh()));

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

    /** A season lot with a name like "Lot 1" put into Global Lots when the farmer asks, from its card. */
    public function toFarm(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        if (\App\Support\WorkerContext::inWorkerContext()) {
            return $this->jsonFail('The farm owner keeps the lots.', 403);
        }
        $lot = AsScheduleLot::active()->where('croppingScheduleId', $schedule->id)->where('id', (int) $request->input('lotId'))->first();
        if (! $lot) {
            return $this->jsonFail('Lot not found.', 404);
        }
        $this->farmLot = \App\Support\LotSync::card(\App\Support\LotSync::afterSave($schedule, $lot, true));
        if (! $this->farmLot) {
            return $this->jsonFail('It could not be added to Global Lots. Please try again.', 422);
        }

        return $this->jsonOk('Added to Global Lots.', ['data' => $this->lotPayload($lot->fresh())]);
    }

    /** The farm's lots not in this season yet, for "Add from your lots". */
    public function farmChoices(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        if (\App\Support\WorkerContext::inWorkerContext()) {
            return $this->jsonFail('The farm owner adds lots.', 403);
        }
        $rows = array_map(fn ($f) => ['id' => (int) $f['id'], 'name' => (string) $f['name'], 'place' => (string) ($f['place'] ?? ''), 'areaHa' => $f['areaHa'] ?? null,
            'areaUnit' => $f['areaUnit'] ?? 'ha', 'got' => \App\Support\LotSync::card($f)['got'] ?? 0], \App\Support\LotSync::notIn($schedule));

        return $this->jsonOk('ok', ['data' => ['lots' => $rows]]);
    }

    /** Farm lots put into this season as lots of it, linked to their farm lot (and so to its answers). */
    public function fromFarm(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        if (\App\Support\WorkerContext::inWorkerContext()) {
            return $this->jsonFail('The farm owner adds lots.', 403);
        }
        $ids = array_values(array_filter(array_map('intval', (array) $request->input('ids', []))));
        if (! $ids) {
            return $this->jsonFail('Pick at least one lot.', 422);
        }
        $lotCap = \App\Support\Tier::scheduleLimit($schedule, 'lotsPerSchedule');
        $have = AsScheduleLot::active()->where('croppingScheduleId', $schedule->id)->count();
        if ($lotCap !== null && $have + count($ids) > $lotCap) {
            \App\Support\Tier::scheduleDenyFor($schedule, 'lotsPerSchedule', 'This plan allows up to ' . $lotCap . ' lot' . ($lotCap == 1 ? '' : 's') . ' per season. Move up to {plan} for more.');
        }
        // The new lots grow what this season's lots grow, counted the way they are counted.
        $lots = AsScheduleLot::active()->where('croppingScheduleId', $schedule->id)->get(['crop', 'dayType']);
        $crop = $lots->pluck('crop')->filter()->countBy()->sortDesc()->keys()->first() ?: \App\Support\CropStages::normalize($schedule->cropType);
        $dayType = $lots->pluck('dayType')->filter()->countBy()->sortDesc()->keys()->first() ?: ($schedule->dayType ?: 'DAT');
        $made = \App\Support\LotSync::addToSeason($schedule, $ids, $crop ?: null, $dayType);
        $farm = \App\Support\LotSync::season($schedule);
        $out = [];
        foreach ($made as $lot) {
            $this->farmLot = \App\Support\LotSync::card($farm[(int) $lot->id] ?? null);
            $out[] = $this->lotPayload($lot->fresh());
        }

        return $this->jsonOk(count($out) === 1 ? 'Lot added.' : count($out) . ' lots added.', ['data' => ['lots' => $out]]);
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
