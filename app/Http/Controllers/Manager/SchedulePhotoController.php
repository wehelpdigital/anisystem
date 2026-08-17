<?php

namespace App\Http\Controllers\Manager;

use App\Events\SchedulePhotoPushed;
use App\Models\AsGalleryAlbum;
use App\Models\AsGalleryImage;
use App\Models\AsScheduleNote;
use App\Models\SchedulePhotoBoard;
use App\Models\SchedulePhotoEvent;
use App\Support\MediaStore;
use App\Support\ScheduleTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * The Collab Room's shared photo: pick or take a picture, and the whole team
 * draws over it together in realtime.
 *
 * Deliberately its own table and controller rather than a page of the
 * whiteboard. The whiteboard computes real things from its event table — the
 * board token, the release of an emptied board's note binding, the archive of
 * a room that went quiet — and photo strokes leaking into any of those is how
 * somebody circling a carabao rebinds a teammate's drawing note. Same channel,
 * same gates, separate rows.
 */
class SchedulePhotoController extends BaseScheduleController
{
    /** Photo + every stroke after `after`, for boot and for the poll. */
    public function state(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        if (! ScheduleTeam::canAccess($schedule, (int) Auth::id())) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $after = max(0, (int) $request->query('after', 0));
        $board = SchedulePhotoBoard::forSchedule($schedule->id);

        $rows = SchedulePhotoEvent::active()
            ->where('scheduleId', $schedule->id)
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(500)
            ->get();

        return $this->jsonOk('Photo state.', [
            'data' => [
                'photo' => $board->imagePath ? [
                    'url' => MediaStore::url($board->imagePath),
                    'setBy' => (int) $board->setBy,
                ] : null,
                // Moves when the photo changes, so a poll can tell "new strokes
                // arrived" from "the ground under them was swapped".
                'gen' => (string) ($board->imagePath ?? ''),
                'events' => $rows->map(fn ($e) => $this->shape($e))->all(),
                'maxId' => (int) ($rows->max('id') ?: $after),
                'canSave' => \App\Support\WorkerContext::canAddNotes(),
                // Only when the save sheet asks: the poll runs every second or
                // two and does not need the album shelf riding along on it.
                'albums' => $request->boolean('albums')
                    ? AsGalleryAlbum::where('croppingScheduleId', $schedule->id)
                        ->where('deleteStatus', 1)
                        ->orderBy('title')
                        ->get(['id', 'title'])
                        ->map(fn ($a) => ['id' => (int) $a->id, 'title' => (string) $a->title])
                        ->all()
                    : null,
            ],
        ]);
    }

    /** A new photo under everyone's pens: uploaded, captured, or picked. */
    public function setPhoto(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $path = null;
        if ($request->hasFile('photo')) {
            $v = Validator::make($request->all(), [
                'photo' => 'required|file|mimes:jpeg,jpg,png,webp|max:15360',
            ]);
            if ($v->fails()) {
                return $this->jsonFail('That is not a picture this can draw on — use a JPEG, PNG or WebP up to 15 MB.', 422);
            }
            $path = MediaStore::putFile($request->file('photo'), 'team-photos', $schedule->id);
        } else {
            // From the gallery picker: a MediaStore path we already keep,
            // never a URL — a stored URL would be fetched by every member's
            // browser on whoever's say-so, which is a different feature.
            $p = (string) $request->input('path', '');
            if ($p === '' || str_starts_with($p, 'http') || strlen($p) > 500) {
                return $this->jsonFail('Pick a photo, upload one, or take one.', 422);
            }
            if (! preg_match('/\.(jpe?g|png|webp)$/i', parse_url($p, PHP_URL_PATH) ?: $p)) {
                return $this->jsonFail('Only a photo can go under the pens — clips cannot be drawn over.', 422);
            }
            $path = $p;
        }
        if ($path === null) {
            return $this->jsonFail('Could not keep that photo.', 500);
        }

        // A new photo is a new sheet: whatever was drawn on the old one is
        // retired with it, not floated over an unrelated picture.
        SchedulePhotoEvent::where('scheduleId', $schedule->id)->update(['deleteStatus' => 0]);
        SchedulePhotoBoard::forSchedule($schedule->id)->update(['imagePath' => $path, 'setBy' => $meId]);

        $payload = ['action' => 'photo', 'url' => MediaStore::url($path), 'gen' => $path, 'actorUserId' => $meId];
        $this->emit($schedule->id, $payload);

        return $this->jsonOk('Photo is up — draw away.', ['data' => $payload]);
    }

    /** One stroke (possibly a continuation), or a clear. */
    public function push(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        if ($request->input('type') === 'clear') {
            SchedulePhotoEvent::where('scheduleId', $schedule->id)->update(['deleteStatus' => 0]);
            $this->emit($schedule->id, ['action' => 'clear', 'actorUserId' => $meId]);

            return $this->jsonOk('Cleared.', ['data' => ['id' => null]]);
        }

        $v = Validator::make($request->all(), [
            'points' => 'required|array|min:1|max:800',
            'points.*' => 'array|size:2',
            'points.*.*' => 'numeric',
            'color' => 'nullable|string|max:16',
            'width' => 'nullable|integer|min:1|max:200',
            'mode' => 'nullable|in:pen,eraser,line,arrow,rect,circle,text',
            'text' => 'nullable|string|max:500',
            'uid' => 'nullable|string|max:40',
        ]);
        if ($v->fails()) {
            return $this->jsonFail('Invalid stroke.', 422);
        }

        // Normalized to the PHOTO, not the screen: 0..1 across the image's own
        // pixels, so a phone and a laptop draw on the same spot of the same
        // carabao whatever their windows look like.
        $clamp = fn ($n) => max(0.0, min(1.0, (float) $n));
        $points = array_map(
            fn ($p) => [round($clamp($p[0]), 4), round($clamp($p[1]), 4)],
            $request->input('points')
        );

        $event = SchedulePhotoEvent::create([
            'scheduleId' => $schedule->id,
            'userId' => $meId,
            'type' => 'draw',
            'mode' => $request->input('mode', 'pen'),
            'color' => $request->input('color'),
            'width' => (int) $request->input('width', 4),
            'strokeUid' => $request->input('uid'),
            'points' => $points,
            'shapeText' => $request->input('text'),
            'deleteStatus' => 1,
        ]);

        $this->emit($schedule->id, ['action' => 'stroke', 'event' => $this->shape($event), 'actorUserId' => $meId]);

        return $this->jsonOk('Stroke.', ['data' => ['id' => (int) $event->id]]);
    }

    /**
     * Take back my own latest stroke. Mine only — undoing a teammate's line
     * from across the room is an argument, not a tool. Redo is the client
     * re-posting the stroke it took back, same as the map re-adds a shape.
     */
    public function undo(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }

        $uid = (string) $request->input('uid', '');
        $q = SchedulePhotoEvent::active()
            ->where('scheduleId', $schedule->id)
            ->where('userId', $meId)
            ->where('type', 'draw');
        // A streamed pen line is several rows sharing one uid; undoing it
        // means taking back the whole line, not its last flushed piece.
        $last = $q->clone()->orderByDesc('id')->first();
        if (! $last) {
            return $this->jsonOk('Nothing of yours to take back.', ['data' => ['ids' => []]]);
        }
        $ids = $last->strokeUid
            ? $q->clone()->where('strokeUid', $last->strokeUid)->pluck('id')->all()
            : [$last->id];
        SchedulePhotoEvent::whereIn('id', $ids)->update(['deleteStatus' => 0]);

        $this->emit($schedule->id, ['action' => 'remove', 'ids' => array_map('intval', $ids), 'actorUserId' => $meId]);

        return $this->jsonOk('Taken back.', ['data' => ['ids' => array_map('intval', $ids)]]);
    }

    /** File the drawn-over photo: a new note, the team album, or a chosen album. */
    public function save(Request $request)
    {
        $schedule = $this->schedule($request->query('scheduleId'));
        $meId = (int) Auth::id();
        if (! ScheduleTeam::canAccess($schedule, $meId)) {
            return $this->jsonFail('You are not part of this schedule team.', 403);
        }
        // Same line the whiteboard draws: membership lets you draw, filing the
        // result into the schedule's records is the note right. The gallery
        // rows this writes are team images riding the same permission — asking
        // for full edit rights here would mean a notes-only worker could draw
        // all afternoon and never keep anything, which defeats the room.
        if (! \App\Support\WorkerContext::canAddNotes()) {
            return $this->jsonFail('You are not allowed to save to this schedule.', 403);
        }

        $v = Validator::make($request->all(), [
            'image' => 'required|string',
            'dest' => 'required|in:note,gallery,album',
            'albumId' => 'required_if:dest,album|nullable|integer',
            'title' => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
        ]);
        if ($v->fails()) {
            return $this->jsonFail('Give the image a name first.', 422, ['errors' => $v->errors()]);
        }

        $binary = $this->decodeImage((string) $request->input('image'));
        if ($binary === null) {
            return $this->jsonFail('Could not read the image.', 422);
        }
        $path = MediaStore::putBinary($binary['bytes'], 'team-photos', $binary['ext'], $schedule->id);
        if ($path === null) {
            return $this->jsonFail('Could not keep that image.', 500);
        }

        $title = (string) $request->input('title');
        $desc = trim((string) $request->input('description', ''));
        $dest = (string) $request->input('dest');

        if ($dest === 'note') {
            $note = AsScheduleNote::create([
                'croppingScheduleId' => $schedule->id,
                'userId' => $meId,
                'title' => $title,
                'body' => trim($desc . "\n\nTeam image, drawn together in the Collab Room."),
                'media' => [['type' => 'image', 'path' => $path, 'team' => true]],
                'deleteStatus' => 1,
            ]);

            return $this->jsonOk('Saved to the notebook.', ['data' => ['noteId' => (int) $note->id]]);
        }

        if ($dest === 'album') {
            $album = AsGalleryAlbum::where('croppingScheduleId', $schedule->id)
                ->where('deleteStatus', 1)
                ->where('id', (int) $request->input('albumId'))
                ->first();
            if (! $album) {
                return $this->jsonFail('That album is not on this schedule.', 404);
            }
        } else {
            // "Just put it in the gallery." Every gallery picture lives in an
            // album, so the destination is the team's own — made once, reused
            // for every image the room saves after it.
            $album = AsGalleryAlbum::firstOrCreate(
                ['croppingScheduleId' => $schedule->id, 'title' => 'Team photos', 'deleteStatus' => 1],
                ['userId' => $meId, 'description' => 'Images the team drew together in the Collab Room.']
            );
        }

        // Appended after what the album already holds — the same seeding every
        // other writer into this table now does.
        $order = (int) AsGalleryImage::where('albumId', $album->id)
            ->where('deleteStatus', 1)->max('sortOrder');
        AsGalleryImage::create([
            'albumId' => $album->id,
            'croppingScheduleId' => $schedule->id,
            'userId' => $meId,
            'path' => $path,
            'caption' => $title,
            'description' => $desc,
            'isTeam' => 1,
            'sortOrder' => $order + 1,
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk(
            $dest === 'album' ? "Saved to “{$album->title}”." : 'Saved to the Gallery, in “Team photos”.',
            ['data' => ['albumId' => (int) $album->id]]
        );
    }

    /** The wire shape of one event — what state() lists and the broadcast carries. */
    private function shape(SchedulePhotoEvent $e): array
    {
        return [
            'id' => (int) $e->id,
            'userId' => (int) $e->userId,
            'mode' => $e->mode ?: 'pen',
            'color' => $e->color,
            'width' => (int) ($e->width ?: 4),
            'uid' => $e->strokeUid,
            'points' => $e->points ?: [],
            'text' => $e->shapeText,
        ];
    }

    /** Broadcast if realtime is configured; the poll carries it regardless. */
    private function emit(int $scheduleId, array $payload): void
    {
        try {
            $driver = config('broadcasting.default');
            $ready = in_array($driver, ['pusher', 'reverb', 'ably'], true)
                && filled(config("broadcasting.connections.$driver.key"));
            if ($ready) {
                broadcast(new SchedulePhotoPushed($scheduleId, $payload));
            }
        } catch (\Throwable $e) {
            // The poll is the fallback; a broadcast that failed is not an error.
        }
    }

    /** data:image/png|jpeg|webp;base64 → bytes + extension, or null. */
    private function decodeImage(string $dataUrl): ?array
    {
        if (! preg_match('#^data:image/(png|jpeg|webp);base64,(.+)$#s', $dataUrl, $m)) {
            return null;
        }
        $bytes = base64_decode($m[2], true);
        if ($bytes === false || strlen($bytes) < 100 || strlen($bytes) > 24 * 1024 * 1024) {
            return null;
        }

        return ['bytes' => $bytes, 'ext' => $m[1] === 'jpeg' ? 'jpg' : $m[1]];
    }
}
