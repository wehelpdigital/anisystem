<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleActivityVersion;
use App\Models\AsScheduleProgressMarker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Bookmark/progress markers in the activities timeline. Each marker is a
 * horizontal line positioned at a specific date that lets the user remember
 * "where I left off yesterday" — with an optional note attached.
 *
 * Markers are version-scoped (same convention as date notes) so each
 * fork can carry its own resume-here pins. Faithful port of the mother
 * app's MarkerController.
 */
class MarkerController extends BaseScheduleController
{
    /**
     * Create OR update — markers are keyed by (schedule, version, date)
     * so calling this endpoint twice for the same date upserts the note.
     */
    public function save(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'markerDate'  => 'required|date',
            'noteContent' => 'nullable|string|max:5000',
            'versionId'   => 'nullable|integer',
        ], [
            'markerDate.required' => 'Marker date is required.',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        // A submitted versionId must belong to THIS schedule — never trust a
        // client-supplied id that could reference another tenant's version.
        $versionId = $this->resolveActiveVersionId($schedule->id);
        if ($request->filled('versionId')) {
            $requested = (int) $request->input('versionId');
            $ownsVersion = AsScheduleActivityVersion::active()
                ->forSchedule($schedule->id)
                ->where('id', $requested)
                ->exists();
            if ($ownsVersion) {
                $versionId = $requested;
            }
        }

        $marker = AsScheduleProgressMarker::active()
            ->where('croppingScheduleId', $schedule->id)
            ->when($versionId, fn ($q) => $q->where('versionId', $versionId), fn ($q) => $q->whereNull('versionId'))
            ->whereDate('markerDate', $request->input('markerDate'))
            ->first();

        if ($marker) {
            $marker->update(['noteContent' => $request->input('noteContent')]);
        } else {
            $marker = AsScheduleProgressMarker::create([
                'croppingScheduleId' => $schedule->id,
                'versionId'          => $versionId,
                'markerDate'         => $request->input('markerDate'),
                'noteContent'        => $request->input('noteContent'),
                'deleteStatus'       => 1,
            ]);
        }

        return $this->jsonOk('Marker saved.', [
            'data' => [
                'id'          => $marker->id,
                'markerDate'  => $marker->markerDate?->format('Y-m-d'),
                'noteContent' => $marker->noteContent,
                'versionId'   => $marker->versionId,
            ],
        ]);
    }

    /**
     * Soft-delete a marker by id (within the schedule for ownership safety).
     */
    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);
        $marker = AsScheduleProgressMarker::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('id', $id)
            ->first();
        if (!$marker) return $this->jsonFail('Marker not found.', 404);
        $marker->update(['deleteStatus' => 0]);
        return $this->jsonOk('Marker removed.');
    }

    /**
     * Resolve the schedule's currently-active version id. Mirrors the
     * private helper in ActivityController so markers land on the same
     * fork the user is editing.
     */
    protected function resolveActiveVersionId(int $scheduleId): ?int
    {
        $active = AsScheduleActivityVersion::active()
            ->forSchedule($scheduleId)
            ->where('isActive', 1)
            ->orderBy('id', 'asc')
            ->first();
        if ($active) return (int) $active->id;

        $fallback = AsScheduleActivityVersion::active()
            ->forSchedule($scheduleId)
            ->orderBy('isOriginal', 'desc')
            ->orderBy('id', 'asc')
            ->first();
        return $fallback ? (int) $fallback->id : null;
    }
}
