<?php

namespace App\Support;

use App\Models\ScheduleBoardDraft;
use App\Models\ScheduleBoardEvent;
use App\Models\ScheduleBoardPage;
use App\Models\ScheduleBoardPresence;
use App\Models\ScheduleBoardState;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Drawing sessions on the team whiteboard.
 *
 * The board is no longer one endless canvas. A session ends when the room
 * empties; the next person to open it gets a clean page 1, and whatever was
 * there is archived so nothing is lost.
 *
 * The archive keeps strokes rather than a flattened image, which is what makes
 * a past drawing something you can reopen and carry on with — and what lets a
 * drawing saved to the notebook stay editable instead of freezing into a PNG.
 */
class BoardSession
{
    /**
     * Called when someone opens the drawing. Returns whether they got a fresh
     * canvas, and what was archived on the way.
     *
     * Joining a room where someone is already drawing must never clear it —
     * that would wipe a teammate's live work simply because you walked in — so
     * occupancy decides, not the act of opening.
     */
    public static function open(int $scheduleId, int $userId): array
    {
        ScheduleBoardPresence::touch_($scheduleId, $userId);

        // A drawing untouched for a day is a finished session whoever is
        // nominally "present": archive it and start clean. Within 24 hours the
        // team is plausibly coming back to it, so it stays.
        $latest = ScheduleBoardEvent::active()
            ->where('scheduleId', $scheduleId)
            ->max('created_at');
        $expired = $latest && Carbon::parse($latest)->lt(Carbon::now('Asia/Manila')->subDay());

        if (! $expired && ScheduleBoardPresence::othersPresent($scheduleId, $userId) > 0) {
            return ['fresh' => false, 'archived' => null];
        }

        $archived = self::archive($scheduleId, $userId);

        if ($archived !== null || self::hasAnyStrokes($scheduleId)) {
            self::clear($scheduleId);
        }

        return ['fresh' => true, 'archived' => $archived];
    }

    /**
     * Snapshot the canvas. Returns a description of what was kept, or null when
     * there was nothing worth keeping.
     *
     * A drawing that has not changed since it was last saved is skipped
     * entirely — otherwise every empty room would breed another copy of a
     * drawing already sitting in the notebook.
     */
    public static function archive(int $scheduleId, int $userId): ?array
    {
        $state = ScheduleBoardState::forSchedule($scheduleId);

        $events = ScheduleBoardEvent::active()
            ->where('scheduleId', $scheduleId)
            ->orderBy('id')
            ->get();

        if ($events->isEmpty()) {
            return null;
        }

        $maxId = (int) $events->max('id');
        if ($maxId <= (int) $state->savedUpToEventId) {
            return null; // untouched since the last save
        }

        // Markers are not a drawing. A board undone or cleared down to nothing
        // still has its undo/clear rows standing, and they are newer than the
        // last save, so everything above says "there is unsaved work here".
        // There isn't. Archiving them writes a payload whose entire content is
        // "somebody took it all back" — and when the canvas is bound to a note,
        // it writes that over the strokes behind it, in a row Past drawings
        // does not list, so nobody ever sees the drawing go. Unbound, it mints
        // a blank card instead. Either way: nothing drawable, nothing to keep.
        if (! $events->contains(fn ($e) => $e->type === 'draw')) {
            return null;
        }

        $payload = json_encode([
            'pages' => ScheduleBoardPage::active()
                ->where('scheduleId', $scheduleId)
                ->orderBy('page')
                ->get(['page', 'orientation'])
                ->map(fn ($p) => ['page' => (int) $p->page, 'orientation' => $p->orientation ?: 'landscape'])
                ->values()
                ->all(),
            'events' => $events->map(fn ($e) => [
                'page' => (int) $e->page,
                'type' => $e->type,
                'uid' => $e->strokeUid,
                'color' => $e->color,
                'width' => (int) $e->width,
                'mode' => $e->mode,
                'text' => $e->shapeText,
                'points' => json_decode((string) $e->points, true) ?: [],
            ])->values()->all(),
        ]);

        $pageCount = max(1, (int) ScheduleBoardPage::active()->where('scheduleId', $scheduleId)->max('page'));

        // Editing a reopened drawing writes back to it rather than spawning a
        // copy — that is what "saved drawings stay live" means in practice.
        $target = self::existingTarget($state);

        $attributes = [
            'scheduleId' => $scheduleId,
            'pageCount' => $pageCount,
            'payload' => $payload,
            'archivedByUserId' => $userId,
            'archivedAt' => Carbon::now('Asia/Manila'),
            'deleteStatus' => 1,
        ];

        if ($target) {
            $target->update($attributes);
            $draft = $target;
        } else {
            $draft = ScheduleBoardDraft::create($attributes + [
                'title' => 'Drawing ' . Carbon::now('Asia/Manila')->format('M j, g:i A'),
                'savedNoteId' => $state->currentNoteId,
            ]);
        }

        $state->update(['savedUpToEventId' => $maxId]);

        return [
            'id' => $draft->id,
            'title' => $draft->title,
            'noteId' => $draft->savedNoteId,
            // A drawing kept as a note is not offered again under Drafts.
            'isDraft' => $draft->savedNoteId === null,
        ];
    }

    /** The archive row this canvas should write back into, if it has one. */
    private static function existingTarget(ScheduleBoardState $state): ?ScheduleBoardDraft
    {
        if ($state->currentDraftId) {
            return ScheduleBoardDraft::active()->find($state->currentDraftId);
        }

        if ($state->currentNoteId) {
            return ScheduleBoardDraft::active()
                ->where('scheduleId', $state->scheduleId)
                ->where('savedNoteId', $state->currentNoteId)
                ->first();
        }

        return null;
    }

    /** Wipe the canvas back to a single empty landscape page. */
    public static function clear(int $scheduleId): void
    {
        DB::transaction(function () use ($scheduleId) {
            ScheduleBoardEvent::where('scheduleId', $scheduleId)->update(['deleteStatus' => 0]);
            ScheduleBoardPage::where('scheduleId', $scheduleId)->where('page', '>', 1)->update(['deleteStatus' => 0]);
            ScheduleBoardPage::updateOrCreate(
                ['scheduleId' => $scheduleId, 'page' => 1],
                ['orientation' => 'landscape', 'deleteStatus' => 1]
            );

            ScheduleBoardState::forSchedule($scheduleId)->update([
                'savedUpToEventId' => 0,
                'currentDraftId' => null,
                'currentNoteId' => null,
            ]);
        });
    }

    private static function hasAnyStrokes(int $scheduleId): bool
    {
        return ScheduleBoardEvent::active()->where('scheduleId', $scheduleId)->exists();
    }
}
