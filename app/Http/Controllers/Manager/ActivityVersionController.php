<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleActivity;
use App\Models\AsScheduleActivityItem;
use App\Models\AsScheduleActivityVersion;
use App\Models\AsScheduleDateNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Activity versions ("what-if" branches of the activity plan). Faithful
 * port of the mother app's ActivityVersionController: deep-clone forks
 * (rows + items + lot/worker pivots + date notes), Original protection,
 * and reactivation fallback on delete.
 */
class ActivityVersionController extends BaseScheduleController
{
    /**
     * Lean version listing for tab-strip refreshes.
     */
    public function index(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $versions = AsScheduleActivityVersion::active()
            ->forSchedule($schedule->id)
            ->orderBy('versionOrder', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'versionName', 'description', 'parentVersionId', 'isOriginal', 'isActive', 'versionOrder']);

        return $this->jsonOk('Versions loaded.', ['data' => $versions]);
    }

    /**
     * Create a new version. If sourceVersionId is provided, every active
     * activity in the source (drafts included) is deep-cloned into the new
     * version — full row + items + lot pivot + worker pivot — and the
     * source version's date notes are cloned too. Each clone remembers its
     * origin via sourceActivityId.
     *
     * If sourceVersionId is omitted, the new version starts empty.
     */
    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'versionName'     => 'required|string|max:120',
            'description'     => 'nullable|string|max:5000',
            'sourceVersionId' => 'nullable|integer',
            'setActive'       => 'nullable|boolean',
        ], [
            'versionName.required' => 'Give the new version a name (e.g. "Budget Cut V1").',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        // Resolve the source version (if any) and verify it belongs to this
        // schedule — never trust a versionId from the request payload alone.
        $source = null;
        if ($request->filled('sourceVersionId')) {
            $source = AsScheduleActivityVersion::active()
                ->forSchedule($schedule->id)
                ->where('id', (int) $request->sourceVersionId)
                ->first();
            if (!$source) {
                return $this->jsonFail('Source version not found for this schedule.', 404);
            }
        }

        $maxOrder = (int) AsScheduleActivityVersion::active()
            ->forSchedule($schedule->id)
            ->max('versionOrder');

        try {
            $newVersion = DB::transaction(function () use ($schedule, $source, $request, $maxOrder) {
                $version = AsScheduleActivityVersion::create([
                    'croppingScheduleId' => $schedule->id,
                    'versionName'        => trim($request->versionName),
                    'description'        => $request->description,
                    // Inherit the source version's global activity note so a
                    // fork starts with the same client-facing commentary.
                    'globalActivityNote' => $source ? $source->globalActivityNote : null,
                    'parentVersionId'    => $source ? $source->id : null,
                    'isOriginal'         => 0,
                    'isActive'           => 0,
                    'versionOrder'       => $maxOrder + 1,
                    'deleteStatus'       => 1,
                ]);

                if ($source) {
                    $this->cloneActivitiesIntoVersion($source->id, $version->id);
                }

                if ($request->boolean('setActive')) {
                    $this->activateVersion($schedule->id, $version->id);
                    $version->isActive = true;
                }

                return $version;
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to create version: ' . $e->getMessage(), 500);
        }

        return $this->jsonOk('Version created.', ['data' => $newVersion]);
    }

    /**
     * Rename / update description of an existing version. The Original flag
     * is immutable.
     */
    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $validator = Validator::make($request->all(), [
            'versionName' => 'required|string|max:120',
            'description' => 'nullable|string|max:5000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $version = AsScheduleActivityVersion::active()
            ->forSchedule($schedule->id)
            ->where('id', $id)
            ->first();
        if (!$version) return $this->jsonFail('Version not found.', 404);

        $version->update([
            'versionName' => trim($request->versionName),
            'description' => $request->description,
        ]);

        return $this->jsonOk('Version updated.', ['data' => $version]);
    }

    /**
     * Switch the schedule's active version (transactional single-active flip).
     */
    public function setActive(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $version = AsScheduleActivityVersion::active()
            ->forSchedule($schedule->id)
            ->where('id', $id)
            ->first();
        if (!$version) return $this->jsonFail('Version not found.', 404);

        try {
            DB::transaction(function () use ($schedule, $id) {
                $this->activateVersion($schedule->id, $id);
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to switch version: ' . $e->getMessage(), 500);
        }

        return $this->jsonOk('Active version switched.', ['data' => $version->fresh()]);
    }

    /**
     * Soft-delete a version and every activity inside it. The Original
     * version cannot be deleted. If the deleted version was active, the
     * Original is re-activated.
     */
    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $version = AsScheduleActivityVersion::active()
            ->forSchedule($schedule->id)
            ->where('id', $id)
            ->first();
        if (!$version) return $this->jsonFail('Version not found.', 404);
        if ($version->isOriginal) {
            return $this->jsonFail('The Original version cannot be deleted.', 422);
        }

        try {
            DB::transaction(function () use ($schedule, $version) {
                // Soft-delete every activity (and its items) in this version.
                $activityIds = AsScheduleActivity::where('versionId', $version->id)
                    ->where('deleteStatus', 1)
                    ->pluck('id');

                if ($activityIds->count()) {
                    AsScheduleActivity::whereIn('id', $activityIds)->update(['deleteStatus' => 0]);
                    AsScheduleActivityItem::whereIn('activityId', $activityIds)->update(['deleteStatus' => 0]);
                }

                $wasActive = $version->isActive;
                $version->update(['deleteStatus' => 0, 'isActive' => 0]);

                if ($wasActive) {
                    $fallback = AsScheduleActivityVersion::active()
                        ->forSchedule($schedule->id)
                        ->where('isOriginal', 1)
                        ->first();
                    if ($fallback) {
                        $this->activateVersion($schedule->id, $fallback->id);
                    }
                }
            });
        } catch (\Throwable $e) {
            return $this->jsonFail('Failed to delete version: ' . $e->getMessage(), 500);
        }

        return $this->jsonOk('Version deleted.');
    }

    /**
     * Save the version's global activity note (rich text shown above the
     * timeline / on printed documents). Empty input clears it. Operates on
     * the version targeted by ?id — not necessarily the active one.
     */
    public function setGlobalNote(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $validator = Validator::make($request->all(), [
            // Generous cap for rich-text content — the DB column is
            // MEDIUMTEXT so 500k chars is well within physical limits
            // while still preventing pathological payloads.
            'globalActivityNote' => 'nullable|string|max:500000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $version = AsScheduleActivityVersion::active()
            ->forSchedule($schedule->id)
            ->where('id', $id)
            ->first();
        if (!$version) return $this->jsonFail('Version not found.', 404);

        $content = trim((string) $request->input('globalActivityNote', ''));
        // Sanitize — rendered raw and shared with the admin app.
        $clean = $content === '' ? null : \App\Support\HtmlSanitizer::rich($content);
        $version->update(['globalActivityNote' => $clean]);

        return $this->jsonOk($content === '' ? 'Global note cleared.' : 'Global note saved.', [
            'data' => [
                'id'                 => $version->id,
                'globalActivityNote' => $version->globalActivityNote,
            ],
        ]);
    }

    /**
     * Deep-clone every active activity in $sourceVersionId into
     * $targetVersionId: replicate row, stamp new versionId +
     * sourceActivityId, clone items + lot/worker pivots. Then clone the
     * source version's date notes so the fork starts with the same
     * commentary.
     */
    private function cloneActivitiesIntoVersion(int $sourceVersionId, int $targetVersionId): void
    {
        $sources = AsScheduleActivity::where('versionId', $sourceVersionId)
            ->where('deleteStatus', 1)
            ->with(['items' => fn ($q) => $q->where('deleteStatus', 1), 'lots', 'workers'])
            ->get();

        foreach ($sources as $source) {
            $copy = $source->replicate();
            $copy->versionId = $targetVersionId;
            $copy->sourceActivityId = $source->id;
            $copy->save();

            foreach ($source->items as $item) {
                $itemCopy = $item->replicate();
                $itemCopy->activityId = $copy->id;
                $itemCopy->save();
            }

            $lotIds = $source->lots->pluck('id')->all();
            if (!empty($lotIds)) {
                $copy->lots()->sync($lotIds);
            }

            $workerIds = $source->workers->pluck('id')->all();
            if (!empty($workerIds)) {
                $copy->workers()->sync($workerIds);
            }
        }

        // Clone per-date notes attached to the source version.
        $sourceNotes = AsScheduleDateNote::active()
            ->forVersion($sourceVersionId)
            ->get();
        foreach ($sourceNotes as $note) {
            $noteCopy = $note->replicate();
            $noteCopy->versionId = $targetVersionId;
            $noteCopy->save();
        }
    }

    /**
     * Flip isActive=0 across every version for this schedule, then set
     * isActive=1 on the chosen version. Caller wraps in a transaction.
     */
    private function activateVersion(int $scheduleId, int $versionId): void
    {
        AsScheduleActivityVersion::where('croppingScheduleId', $scheduleId)
            ->update(['isActive' => 0]);
        AsScheduleActivityVersion::where('id', $versionId)
            ->where('croppingScheduleId', $scheduleId)
            ->update(['isActive' => 1]);
    }
}
