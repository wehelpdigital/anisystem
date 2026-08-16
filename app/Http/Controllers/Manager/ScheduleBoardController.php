<?php

namespace App\Http\Controllers\Manager;

use App\Events\BoardStrokePushed;
use App\Events\ScheduleBoardPagePushed;
use App\Models\AsScheduleNote;
use App\Models\ScheduleBoardDraft;
use App\Models\ScheduleBoardEvent;
use App\Models\ScheduleBoardPage;
use App\Models\ScheduleBoardPresence;
use App\Models\ScheduleBoardState;
use App\Support\BoardSession;
use App\Support\ScheduleTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * The schedule team's collaborative whiteboard. Strokes are persisted to
 * `as_schedule_board_events` (so late joiners rebuild the board and a reconcile
 * poll can recover missed events) AND broadcast live over Pusher for instant
 * collaboration. A "clear" soft-deletes everything before it.
 *
 * The board is a numbered stack of pages (`as_schedule_board_pages`); every
 * event belongs to a page, and each page has its own orientation.
 */
class ScheduleBoardController extends BaseScheduleController
{
    /**
     * Opening the drawing. Starts a fresh page 1 when the room is empty,
     * archiving whatever was there, or joins the live canvas when a teammate
     * is already drawing on it.
     */
    public function open(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $result = BoardSession::open($schedule->id, $meId);

        if ($result['fresh']) {
            // Anyone still holding the old canvas repaints from scratch.
            $this->emitPage($schedule->id, [
                'action' => 'reset',
                'page' => 1,
                'pages' => $this->pageList($schedule->id),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'fresh' => $result['fresh'],
                'archived' => $result['archived'],
                'pages' => $this->pageList($schedule->id),
                'draftCount' => $this->draftCount($schedule->id),
            ],
        ]);
    }

    /** Keep the room occupancy fresh while the drawing is open. */
    public function heartbeat(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        ScheduleBoardPresence::touch_($schedule->id, $meId);

        return response()->json(['success' => true, 'data' => ['ok' => true]]);
    }

    /** Past drawings that were never kept as notes. */
    public function drafts(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $drafts = ScheduleBoardDraft::active()
            ->where('scheduleId', $schedule->id)
            ->whereNull('savedNoteId')
            ->orderByDesc('archivedAt')
            ->limit(60)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'drafts' => $drafts->map(fn ($d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'pageCount' => (int) $d->pageCount,
                    'archivedAt' => $d->archivedAt ? $d->archivedAt->format('M j, Y g:i A') : null,
                    // The strokes themselves — the list paints its own previews
                    // with the board's renderer, and reopening needs them anyway.
                    'strokes' => $d->strokes(),
                ])->values()->all(),
            ],
        ]);
    }

    /**
     * Put a past drawing back on the canvas. From here on it is the live board,
     * and further edits update that same drawing rather than making a copy.
     */
    public function openDraft(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        // Same gate as push(): anyone on the team who can draw can reopen.
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $draft = ScheduleBoardDraft::active()
            ->where('scheduleId', $schedule->id)
            ->find($request->input('id'));

        if (! $draft) {
            return $this->jsonFail('That drawing is no longer available.', 404);
        }

        // Whatever is on the canvas now is itself a session — keep it before
        // replacing it, or reopening an old drawing would discard a new one.
        BoardSession::archive($schedule->id, $meId);
        BoardSession::clear($schedule->id);
        $maxId = $this->restore($schedule->id, $meId, $draft->strokes());

        ScheduleBoardState::forSchedule($schedule->id)->update([
            'savedUpToEventId' => $maxId,
            'currentDraftId' => $draft->savedNoteId ? null : $draft->id,
            'currentNoteId' => $draft->savedNoteId,
        ]);

        $this->emitPage($schedule->id, [
            'action' => 'reset',
            'page' => 1,
            'pages' => $this->pageList($schedule->id),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Opened “' . $draft->title . '”. Changes now update this drawing.',
            'data' => ['pages' => $this->pageList($schedule->id)],
        ]);
    }

    /** Write archived strokes back onto the live board. */
    private function restore(int $scheduleId, int $userId, array $strokes): int
    {
        foreach (($strokes['pages'] ?? []) as $p) {
            ScheduleBoardPage::updateOrCreate(
                ['scheduleId' => $scheduleId, 'page' => max(1, (int) ($p['page'] ?? 1))],
                ['orientation' => ($p['orientation'] ?? 'landscape') === 'portrait' ? 'portrait' : 'landscape', 'deleteStatus' => 1]
            );
        }

        $maxId = 0;
        foreach (($strokes['events'] ?? []) as $e) {
            $row = ScheduleBoardEvent::create([
                'scheduleId' => $scheduleId,
                'page' => max(1, (int) ($e['page'] ?? 1)),
                'userId' => $userId,
                'type' => $e['type'] ?? 'draw',
                'strokeUid' => $e['uid'] ?? null,
                'color' => $e['color'] ?? null,
                'width' => (int) ($e['width'] ?? 4),
                'mode' => $e['mode'] ?? 'pen',
                'shapeText' => $e['text'] ?? null,
                'points' => json_encode($e['points'] ?? []),
                'deleteStatus' => 1,
            ]);
            $maxId = (int) $row->id;
        }

        return $maxId;
    }

    private function draftCount(int $scheduleId): int
    {
        return ScheduleBoardDraft::active()
            ->where('scheduleId', $scheduleId)
            ->whereNull('savedNoteId')
            ->count();
    }

    /** Rebuild / reconcile: active events on a page after `?after=<id>`. */
    public function events(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $page = max(1, (int) $request->query('page', 1));
        $after = (int) $request->query('after', 0);
        $rows = ScheduleBoardEvent::active()
            ->where('scheduleId', $schedule->id)
            ->where('page', $page)
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(2000)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'events' => $rows->map(fn ($e) => $this->shape($e))->all(),
                'maxId' => $rows->max('id') ?: $after,
                'board' => $this->boardToken($schedule->id),
            ],
        ]);
    }

    /**
     * Which drawing the board is currently holding, in one string.
     *
     * The client needs this to answer a question it cannot answer alone: is the
     * board I started exporting still the board I am about to write into? Note
     * that this is not "has the canvas been repainted" — a resize repaints every
     * stroke and changes nothing. What changes the answer is the binding (which
     * note or draft the next save updates) and the session (a clear or a reset
     * retires everything standing and starts over), so those two are what this
     * says. It also travels on the events poll, which is the only channel a
     * client without a realtime key has — otherwise a reset reaches nobody.
     */
    private function boardToken(int $scheduleId): string
    {
        $state = ScheduleBoardState::forSchedule($scheduleId);
        // The oldest stroke still standing. A clear retires everything before
        // its marker and a reset retires the lot, so this moves for either and
        // for nothing else.
        $oldest = (int) ScheduleBoardEvent::active()->where('scheduleId', $scheduleId)->min('id');

        return ((int) $state->currentNoteId) . ':' . ((int) $state->currentDraftId) . ':' . $oldest;
    }

    /** The board's page list (orientations), ensuring page 1 always exists. */
    public function pages(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        return response()->json(['success' => true, 'data' => ['pages' => $this->pageList($schedule->id)]]);
    }

    /** Add a new page (landscape or portrait); broadcast it to the team. */
    public function createPage(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $this->pageList($schedule->id); // ensure page 1 exists first
        $orientation = $request->input('orientation') === 'portrait' ? 'portrait' : 'landscape';
        $next = (int) ScheduleBoardPage::active()->where('scheduleId', $schedule->id)->max('page') + 1;

        ScheduleBoardPage::create([
            'scheduleId' => $schedule->id,
            'page' => $next,
            'orientation' => $orientation,
            'deleteStatus' => 1,
        ]);

        $pages = $this->pageList($schedule->id);
        $this->emitPage($schedule->id, ['action' => 'created', 'page' => $next, 'pages' => $pages]);

        return response()->json(['success' => true, 'data' => ['page' => $next, 'pages' => $pages]]);
    }

    /** Push a draw segment or a clear on a page; persists then broadcasts. */
    public function push(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $page = max(1, (int) $request->input('page', 1));

        if ($request->input('type') === 'clear') {
            $event = ScheduleBoardEvent::create([
                'scheduleId' => $schedule->id, 'page' => $page, 'userId' => $meId,
                'type' => 'clear', 'deleteStatus' => 1,
            ]);
            // A clear wipes this page: retire everything on it before this marker.
            ScheduleBoardEvent::where('scheduleId', $schedule->id)
                ->where('page', $page)
                ->where('id', '<', $event->id)
                ->update(['deleteStatus' => 0]);

            $this->releaseEmptiedBoard($schedule->id);

            return $this->emit($schedule->id, $event);
        }

        $validator = Validator::make($request->all(), [
            'points' => 'required|array|min:1|max:800',
            'points.*' => 'array|size:2',
            'points.*.*' => 'numeric',
            'color' => 'nullable|string|max:16',
            'width' => 'nullable|integer|min:1|max:200',
            'mode' => 'nullable|in:pen,eraser,line,arrow,rect,circle,text',
            'text' => 'nullable|string|max:500',
            'uid' => 'nullable|string|max:40',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Invalid stroke.', 422);
        }

        // Points are normalized 0..1 floats; keep 4 decimals (sub-pixel on a 4K
        // board) so the JSON stays compact without visibly quantizing strokes.
        $clamp = fn ($v) => max(0.0, min(1.0, (float) $v));
        $points = array_map(
            fn ($p) => [round($clamp($p[0]), 4), round($clamp($p[1]), 4)],
            $request->input('points')
        );
        $allowed = ['pen', 'eraser', 'line', 'arrow', 'rect', 'circle', 'text'];
        $mode = in_array($request->input('mode'), $allowed, true) ? $request->input('mode') : 'pen';

        $event = ScheduleBoardEvent::create([
            'scheduleId' => $schedule->id,
            'page' => $page,
            'userId' => $meId,
            'type' => 'draw',
            'strokeUid' => $request->input('uid'),
            'color' => $request->input('color') ?: '#111827',
            'width' => (int) ($request->input('width') ?: 4),
            'mode' => $mode,
            'shapeText' => $mode === 'text' ? mb_substr((string) $request->input('text'), 0, 500) : null,
            'points' => $points,
            'deleteStatus' => 1,
        ]);

        return $this->emit($schedule->id, $event);
    }

    /**
     * A board emptied down to nothing stops being the drawing it was saved as.
     *
     * Two doors lead here and both had to be closed: the Clear button, and
     * undoing your way back to a blank page one stroke at a time. The board
     * does not know which one you used and it does not matter — what matters is
     * that nothing is standing.
     *
     * The canvas stays bound to its note so that later strokes update that note
     * instead of filing another copy of the same picture. But once nothing is
     * left standing on any page, whatever gets drawn next is a different
     * drawing — and the autosave two seconds later would hand it the old note:
     * same row, new images, the kept ones deleted to make room, and nobody ever
     * asked for that. Letting go here costs a second note in the notebook;
     * holding on costs the first one.
     *
     * What the old note keeps is its picture, in the notebook where it was
     * filed. The strokes are archived beside it, but nothing offers them back:
     * Past drawings lists only drawings that were never saved. So this is a
     * one-way door for the drawing and a kept picture for the note — say it
     * plainly rather than implying an undo that no screen can do.
     *
     * Emptying one page of a multi-page drawing is an edit of that drawing, not
     * the start of a new one, so the note survives it.
     */
    private function releaseEmptiedBoard(int $scheduleId): void
    {
        $stillDrawn = ScheduleBoardEvent::active()
            ->where('scheduleId', $scheduleId)
            ->where('type', 'draw')
            ->exists();

        if ($stillDrawn) {
            return;
        }

        // Nothing is standing but the markers that took it all down. Say they
        // are accounted for: zeroing this would tell the archive there is
        // unsaved work here, and the next empty room would mint a past drawing
        // whose entire content is "someone cleared the board" — a blank card in
        // the list, offering nothing to reopen. This runs whether or not the
        // canvas was bound to anything, because the blank card is minted just
        // as happily for a drawing that was never saved.
        $accounted = (int) ScheduleBoardEvent::active()
            ->where('scheduleId', $scheduleId)
            ->max('id');

        ScheduleBoardState::forSchedule($scheduleId)->update([
            'currentNoteId' => null,
            'currentDraftId' => null,
            'savedUpToEventId' => $accounted,
        ]);
    }

    /**
     * Take back my last stroke on this page — or put it back.
     *
     * The board is a shared log, so undo is scoped to the person doing it: it
     * retires my most recent live stroke and nobody else's. A removal cannot
     * be expressed as an increment, and the sync is incremental, so a marker
     * row goes on the log to tell the room to repaint. Only the newest marker
     * per page is kept live — an older one has already done its work, and the
     * repaint it asks for is the same repaint.
     */
    public function undo(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $page = max(1, (int) $request->input('page', 1));
        $mine = fn () => ScheduleBoardEvent::where('scheduleId', $schedule->id)
            ->where('page', $page)
            ->where('userId', $meId)
            ->where('type', 'draw');

        if ($request->boolean('redo')) {
            $id = (int) $request->input('id');
            $event = $mine()->where('id', $id)->where('deleteStatus', 0)->first();
            if (! $event) {
                return $this->jsonFail('That stroke cannot come back.', 404);
            }
            // A clear since then wiped the page for everyone; bringing one
            // stroke back through it would be a ghost only I can explain.
            $wiped = ScheduleBoardEvent::active()
                ->where('scheduleId', $schedule->id)
                ->where('page', $page)
                ->where('type', 'clear')
                ->where('id', '>', $id)
                ->exists();
            if ($wiped) {
                return $this->jsonFail('The board was cleared since then.', 409);
            }
            $event->update(['deleteStatus' => 1]);
        } else {
            $event = $mine()->where('deleteStatus', 1)->orderByDesc('id')->first();
            if (! $event) {
                return response()->json(['success' => true, 'data' => ['id' => null]]);
            }
            $event->update(['deleteStatus' => 0]);
        }

        ScheduleBoardEvent::where('scheduleId', $schedule->id)
            ->where('page', $page)
            ->where('type', 'undo')
            ->update(['deleteStatus' => 0]);
        $marker = ScheduleBoardEvent::create([
            'scheduleId' => $schedule->id, 'page' => $page, 'userId' => $meId,
            'type' => 'undo', 'deleteStatus' => 1,
        ]);

        // No releaseEmptiedBoard() here, deliberately. Undoing to nothing does
        // look like an emptied board, and the harm it used to do — the archive
        // writing "somebody took it all back" over the strokes behind a saved
        // note — is real. But that harm is now stopped where it happens, in
        // BoardSession::archive(), which refuses to keep a payload with no
        // stroke in it. Releasing the binding here as well cost more than it
        // saved: redo puts the stroke back but nothing puts the binding back,
        // so an undo/redo — the most ordinary pair of keystrokes there is —
        // left the canvas unbound and the next autosave filed a second note
        // holding the same picture as the first.
        $this->broadcastEvent($schedule->id, $marker);

        return response()->json(['success' => true, 'data' => ['id' => (int) $event->id]]);
    }

    /**
     * Save an exported page image into the schedule's notebook as a note.
     *
     * Two callers, one road: the Save button, and the board's autosave (`auto`)
     * every couple of seconds of quiet while someone draws. A board that has
     * already become a note updates that note rather than filing another copy
     * of the same drawing every time it changes — which is what makes an
     * autosave bearable at all.
     */
    public function saveToNotes(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        // This one leaves the room: it writes a real note into the schedule's
        // notebook, which is the thing the note endpoints gate. Membership
        // says you may draw on the board, not that you may file the drawing.
        if (! \App\Support\WorkerContext::canAddNotes()) {
            return $this->jsonFail('You are not allowed to write notes on this schedule.', 403);
        }

        $auto = $request->boolean('auto');

        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:20',
            'images.*' => 'required|string',
            'title' => 'nullable|string|max:180',
            'description' => 'nullable|string|max:5000',
            'board' => 'nullable|string|max:80',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Nothing to save.', 422);
        }

        // An untouched canvas exports as white paper. Autosaving that would put
        // a blank note in the notebook for every room somebody opened and left,
        // so the strokes themselves decide, not the pixels the browser sent.
        if ($auto && ! ScheduleBoardEvent::active()->where('scheduleId', $schedule->id)->where('type', 'draw')->exists()) {
            return response()->json([
                'success' => true,
                'message' => 'Nothing drawn yet.',
                'data' => ['noteId' => null, 'count' => 0, 'skipped' => true],
            ]);
        }

        $media = [];
        foreach ($request->input('images') as $i => $dataUrl) {
            $binary = $this->decodeImage($dataUrl);
            if ($binary === null) {
                continue;
            }
            $path = \App\Support\MediaStore::putBinary($binary, 'board', 'png', $schedule->id, 'board-');
            if ($path !== null) {
                $media[] = ['type' => 'image', 'path' => $path, 'poster' => null];
            }
        }

        if (empty($media)) {
            return $this->jsonFail('Could not read the board image.', 422);
        }

        $title = trim((string) $request->input('title'));
        $description = trim((string) $request->input('description'));
        $body = $description !== ''
            ? \App\Support\HtmlSanitizer::rich('<p>' . nl2br(e($description)) . '</p>')
            : null;

        // Which drawing this is a picture of. The client checked before it sent,
        // but that check was seconds of wall-clock ago: a fetch per page to
        // export, then several megabytes of base64 on the wire, and the board
        // can become a different drawing at any point in that. So the caller
        // names the board it drew and we answer for the board we have — because
        // the alternative is this save rebinding the live canvas to a note it
        // has nothing to do with, deleting that note's PNGs to make room, and
        // filing the current strokes into its draft, all silently. The files
        // just written go with the rejection rather than living on as orphans.
        $board = (string) $request->input('board', '');
        if ($board !== '' && $board !== $this->boardToken($schedule->id)) {
            foreach ($media as $m) {
                \App\Support\MediaStore::delete($m['path'] ?? null);
            }

            return $this->jsonFail(
                'The board became a different drawing while that was saving — nothing was written.',
                409,
                ['data' => ['code' => 'board-moved']]
            );
        }

        $state = ScheduleBoardState::forSchedule($schedule->id);
        $note = $state->currentNoteId
            ? AsScheduleNote::active()->where('croppingScheduleId', $schedule->id)->find($state->currentNoteId)
            : null;

        if ($note) {
            // Same drawing, newer picture. The title and the words belong to
            // whoever wrote them — an autosave has nothing to say about either,
            // and an empty box in the Save dialog is not an instruction to
            // forget what was there.
            $fields = ['media' => $this->replaceBoardImages($note, $media)];
            if ($title !== '') {
                $fields['title'] = mb_substr($title, 0, 180);
            }
            if ($description !== '') {
                $fields['body'] = $body;
            }
            $note->update($fields);
        } else {
            $note = AsScheduleNote::create([
                'croppingScheduleId' => $schedule->id,
                'userId' => $meId,
                'title' => mb_substr($title ?: 'Team whiteboard', 0, 180),
                'body' => $body,
                'media' => $media,
                'deleteStatus' => 1,
            ]);
        }

        // A reopened past drawing becomes the note it was just saved as, rather
        // than staying in Past drawings as a second copy of the same picture.
        if ($state->currentDraftId) {
            ScheduleBoardDraft::active()
                ->where('scheduleId', $schedule->id)
                ->where('id', $state->currentDraftId)
                ->update(['savedNoteId' => $note->id]);
        }

        // Bind the canvas to the note it just became. The images are a flat
        // picture; archiving alongside them keeps the strokes, so reopening the
        // note gives back a drawing you can still change rather than a JPEG of
        // one — and later edits update this note instead of piling up drafts.
        $state->update(['currentNoteId' => $note->id, 'currentDraftId' => null, 'savedUpToEventId' => 0]);
        BoardSession::archive($schedule->id, $meId);

        // Everyone looking at this canvas gets the same "Saved" mark: the board
        // is shared, so its being safe is shared news — and a teammate who sees
        // it has nothing left of their own to send.
        $this->emitPage($schedule->id, [
            'action' => 'saved',
            'auto' => $auto,
            'by' => $meId,
            'noteId' => (int) $note->id,
            'pages' => $this->pageList($schedule->id),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Saved ' . count($media) . ' page' . (count($media) === 1 ? '' : 's') . ' to the schedule notebook.',
            'data' => ['noteId' => $note->id, 'count' => count($media), 'auto' => $auto],
        ]);
    }

    /**
     * Swap this note's board exports for the new ones, and forget the files the
     * old ones used — an autosave every fifteen seconds would otherwise leave a
     * landfill of superseded PNGs behind it.
     *
     * Page for page, and only as far as the request reached. A sender's page
     * list is only as fresh as the last broadcast it got, and without a realtime
     * key there is no broadcast — so a client can quite honestly send four pages
     * for a board that now has five. Deleting the fifth because this save did
     * not mention it would take a teammate's page off the note and its PNG off
     * the disk, on the word of a client that never heard about it. A stale
     * picture of page five is a nuisance; no picture of page five is somebody's
     * afternoon. So a short list replaces a short prefix and leaves the rest
     * standing for whoever does know about them.
     *
     * Anything on the note that this endpoint did not put there (a photo added
     * in the notebook) is kept and never deleted: an unrecognised path costs an
     * orphan file, while guessing wrong costs somebody's picture.
     *
     * @param  array<int, array<string, mixed>>  $fresh
     * @return array<int, array<string, mixed>>
     */
    private function replaceBoardImages(AsScheduleNote $note, array $fresh): array
    {
        $existing = is_array($note->media) ? $note->media : [];
        $isBoardShot = fn ($m) => is_array($m)
            && str_starts_with(basename((string) ($m['path'] ?? '')), 'board-');

        $shots = array_values(array_filter($existing, $isBoardShot));
        $others = array_values(array_filter($existing, fn ($m) => ! $isBoardShot($m)));

        foreach (array_slice($shots, 0, count($fresh)) as $old) {
            \App\Support\MediaStore::delete($old['path'] ?? null);
        }

        return array_values(array_merge($fresh, array_slice($shots, count($fresh)), $others));
    }

    /** Decode a `data:image/png;base64,...` URL into raw bytes (or null). */
    private function decodeImage(string $dataUrl): ?string
    {
        if (! preg_match('#^data:image/(png|jpeg|webp);base64,#i', $dataUrl)) {
            return null;
        }
        $b64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        // Guard against absurd payloads (~18 MB decoded).
        if (strlen($b64) > 25_000_000) {
            return null;
        }
        $bin = base64_decode($b64, true);

        return $bin === false ? null : $bin;
    }

    /** The page list, guaranteeing a default landscape page 1 exists. */
    private function pageList(int $scheduleId): array
    {
        $pages = ScheduleBoardPage::active()
            ->where('scheduleId', $scheduleId)
            ->orderBy('page')
            ->get(['page', 'orientation']);

        if ($pages->isEmpty() || ! $pages->contains('page', 1)) {
            ScheduleBoardPage::firstOrCreate(
                ['scheduleId' => $scheduleId, 'page' => 1],
                ['orientation' => 'landscape', 'deleteStatus' => 1]
            );
            $pages = ScheduleBoardPage::active()
                ->where('scheduleId', $scheduleId)
                ->orderBy('page')
                ->get(['page', 'orientation']);
        }

        return $pages->map(fn ($p) => [
            'page' => (int) $p->page,
            'orientation' => $p->orientation ?: 'landscape',
        ])->values()->all();
    }

    /** Broadcast (best-effort) + return the shaped event. */
    private function emit(int $scheduleId, ScheduleBoardEvent $event)
    {
        return response()->json(['success' => true, 'data' => [
            'event' => $this->broadcastEvent($scheduleId, $event),
        ]]);
    }

    /** @return array<string, mixed> the shaped event, broadcast on the way out */
    private function broadcastEvent(int $scheduleId, ScheduleBoardEvent $event): array
    {
        $shaped = $this->shape($event);

        // Broadcast only when a realtime driver is actually configured, and never
        // let a transport error break the save — the reconcile poll still delivers.
        if ($this->realtimeReady()) {
            try {
                broadcast(new BoardStrokePushed($scheduleId, $shaped));
            } catch (\Throwable $e) {
                // swallow — persistence already succeeded
            }
        }

        return $shaped;
    }

    /** Broadcast a page-list change (best-effort). */
    private function emitPage(int $scheduleId, array $payload): void
    {
        if (! $this->realtimeReady()) {
            return;
        }
        try {
            broadcast(new ScheduleBoardPagePushed($scheduleId, $payload));
        } catch (\Throwable $e) {
            // swallow — the pages poll / next fetch recovers it
        }
    }

    private function realtimeReady(): bool
    {
        $driver = config('broadcasting.default');

        return in_array($driver, ['pusher', 'reverb', 'ably'], true)
            && filled(config("broadcasting.connections.$driver.key"));
    }

    private function shape(ScheduleBoardEvent $e): array
    {
        return [
            'id' => $e->id,
            'page' => (int) $e->page,
            'userId' => (int) $e->userId,
            'type' => $e->type,
            'uid' => $e->strokeUid,
            'color' => $e->color,
            'width' => $e->width,
            'mode' => $e->mode,
            'text' => $e->shapeText,
            'points' => $e->points ?: [],
        ];
    }
}
