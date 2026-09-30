<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleNote;
use App\Support\DrawStrokes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Draw, in Global and Quick Tools (2026-09-30): the same pad the note editor
 * and the Collab Room use, standing on its own like Maps.
 *
 * A drawing is not a new kind of record — it is a note whose attachment is a
 * picture the pad made. Keeping it that way means drawings are already in a
 * notebook, already searchable, already deletable, and the two never
 * disagree about what exists. This controller only lists them and writes
 * them back.
 *
 * A drawing made HERE is a note of your own (Global Notes, croppingScheduleId
 * 0). A drawing made inside a season -- in a note, on a day of the board, on
 * the team whiteboard -- stays where it was drawn, and this page lists it
 * with the season's name on it. Every request names whose note it means with
 * `scheduleId` (0 for your own) and `source` (note | inline | date).
 */
class ScheduleDrawController extends BaseScheduleController
{
    /** A picture the team whiteboard saved, known by the name that save writes. */
    private const TEAM_FILE = '~/board-[A-Za-z0-9]+\.png$~';

    public function page(Request $request)
    {
        $me = (int) \Illuminate\Support\Facades\Auth::id();
        $drawings = [];
        $collect = function ($holder, int $scheduleId, ?string $season, string $source, string $title, string $words, ?string $noteHref) use (&$drawings) {
            foreach ($this->mediaOf($holder) as $i => $m) {
                $type = (string) ($m['type'] ?? '');
                $path = (string) ($m['path'] ?? '');
                $team = (bool) preg_match(self::TEAM_FILE, $path);
                if ($type !== 'drawing' && ! $team) {
                    continue;
                }
                $drawings[] = [
                    'noteId' => (int) $holder->id,
                    'index' => (int) $i,
                    // Whose shelf the note is on: 0 for your own, else the
                    // season's -- and which shelf of it, since the board's
                    // sticky and day notes hold drawings too.
                    'scheduleId' => $scheduleId,
                    'season' => $season,
                    'source' => $source,
                    'title' => $title,
                    // What the drawing is about, so the grid can say more than
                    // a filename and a date.
                    'note' => $words,
                    // Strokes are not sent with the list — a season of drawings
                    // would be megabytes of them. They come one at a time, when
                    // a drawing is actually opened for editing.
                    'editable' => $type === 'drawing' && ! empty($m['strokes']),
                    // How many sheets it turned out to be, so the card can say
                    // so without the strokes being sent to work it out.
                    'pages' => DrawStrokes::pageCount($m['strokes'] ?? null),
                    'team' => $team,
                    'url' => \App\Support\MediaStore::url($path),
                    // Always answerable (see thumb()): the stamp or the picture
                    // while its file lives, else the drawing redrawn from its
                    // strokes. A team whiteboard has no strokes; its file is it.
                    'thumb' => $team
                        ? \App\Support\MediaStore::url($m['thumb'] ?? $path)
                        : route('sm.draw.thumb', ['scheduleId' => $scheduleId, 'source' => $source, 'noteId' => (int) $holder->id, 'index' => (int) $i, 'v' => $holder->updated_at?->timestamp]),
                    'when' => $holder->updated_at?->timezone('Asia/Manila')->format('M j, Y'),
                    'sortKey' => $holder->updated_at?->timestamp ?? 0,
                    // Every drawing lives in a note; this is the way back to
                    // the words that say why it was drawn.
                    'noteHref' => $noteHref,
                ];
            }
        };

        // Your own drawings.
        foreach (AsScheduleNote::active()->where('croppingScheduleId', 0)->where('userId', $me)
            ->where('media', 'like', '%"drawing"%')->orderByDesc('id')->limit(300)->get() as $note) {
            $collect($note, 0, null, 'note', (string) $note->title, trim(strip_tags((string) $note->body)),
                route('notes.hub', ['open' => $note->id]));
        }

        // And the ones drawn inside the seasons you work in, where they stay.
        if (\App\Support\WorkerContext::canView() && \App\Support\WorkerContext::canUseModule('draw')) {
            $seasons = \App\Models\AsCroppingSchedule::active()
                ->forClient(\App\Support\WorkerContext::effectiveOwnerId())
                ->pluck('title', 'id');
            $ids = $seasons->keys()->all();
            if ($ids) {
                foreach (AsScheduleNote::active()->whereIn('croppingScheduleId', $ids)->orderByDesc('id')->limit(300)->get() as $note) {
                    $collect($note, (int) $note->croppingScheduleId, $seasons[$note->croppingScheduleId] ?? null, 'note',
                        (string) $note->title, trim(strip_tags((string) $note->body)),
                        route('sm.notes', ['id' => $note->croppingScheduleId, 'open' => $note->id]));
                }
                foreach (\App\Models\AsInlineNote::active()->whereIn('croppingScheduleId', $ids)->orderByDesc('id')->limit(300)->get() as $note) {
                    $collect($note, (int) $note->croppingScheduleId, $seasons[$note->croppingScheduleId] ?? null, 'inline',
                        (string) ($note->title ?: 'Note on the board'), trim(strip_tags((string) $note->content)),
                        route('sm.activities', ['id' => $note->croppingScheduleId]));
                }
                foreach (\App\Models\AsScheduleDateNote::active()->whereIn('croppingScheduleId', $ids)->orderByDesc('id')->limit(300)->get() as $note) {
                    $collect($note, (int) $note->croppingScheduleId, $seasons[$note->croppingScheduleId] ?? null, 'date',
                        'Note for ' . $note->noteDate->format('M j, Y'), trim(strip_tags((string) $note->noteContent)),
                        route('sm.activities', ['id' => $note->croppingScheduleId]));
                }
            }
        }

        // One shelf, newest first, wherever each drawing lives.
        usort($drawings, fn ($a, $b) => $b['sortKey'] <=> $a['sortKey']);

        return view('sm.draw', [
            'schedule' => null,
            'drawings' => $drawings,
        ]);
    }

    /** The season Draw module's old address: the global page, same drawing. */
    public function legacyPage(Request $request)
    {
        return redirect()->route('draw.page', array_filter(['open' => (string) $request->query('open')]));
    }

    /**
     * A drawing's picture for its tile, always.
     *
     * The stamp (or the picture) while its file still answers. When the file
     * is gone -- some did not survive the moves between hosts -- and the
     * drawing kept its strokes, it is painted again from them
     * (App\Support\DrawThumb) and the note is mended with the new files, so
     * the Gallery and every chip get it back too.
     */
    public function thumb(Request $request)
    {
        $note = $this->holderFor($request, false);
        $i = (int) $request->query('index');
        $media = $note ? $this->mediaOf($note) : [];
        $m = $media[$i] ?? null;
        if (! $m) {
            return $this->jsonFail('That drawing is no longer here.', 404);
        }

        foreach (array_filter([$m['thumb'] ?? null, $m['path'] ?? null]) as $p) {
            if (\App\Support\MediaStore::exists($p)) {
                return redirect()->away(\App\Support\MediaStore::url($p))->header('Cache-Control', 'private, max-age=3600');
            }
        }

        $png = \App\Support\DrawThumb::png($m['strokes'] ?? null, 480, 360);
        if (! $png) {
            return $this->jsonFail('That drawing has no picture and nothing to redraw it from.', 404);
        }
        $this->mend($note, $i);

        return response($png, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'private, max-age=3600']);
    }

    /**
     * Give a drawing whose files were lost new ones, painted from its strokes.
     * Best effort: a storage wall or a failed upload leaves the note as it was.
     */
    private function mend($note, int $i): void
    {
        try {
            $media = $this->mediaOf($note);
            $m = $media[$i] ?? null;
            if (! $m || empty($m['strokes'])) {
                return;
            }
            $scope = (int) ($note->croppingScheduleId ?? 0) ?: 'u' . (int) ($note->userId ?? 0);
            $changed = false;
            if (! \App\Support\MediaStore::exists($m['path'] ?? null)) {
                $full = \App\Support\DrawThumb::png($m['strokes'], 1600, 1200);
                $path = $full ? \App\Support\MediaStore::putBinary($full, 'drawings', 'png', $scope) : null;
                if ($path) {
                    $m['path'] = $path;
                    $changed = true;
                }
            }
            if (! \App\Support\MediaStore::exists($m['thumb'] ?? null)) {
                $small = \App\Support\DrawThumb::png($m['strokes'], 480, 360);
                $thumb = $small ? \App\Support\MediaStore::putBinary($small, 'drawings', 'png', $scope, 'thumb-') : null;
                if ($thumb) {
                    $m['thumb'] = $thumb;
                    $changed = true;
                }
            }
            if ($changed) {
                $media[$i] = $m;
                $note->media = array_values($media);
                $note->save();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::info('Could not mend a drawing: ' . $e->getMessage());
        }
    }

    /** The strokes behind one drawing, fetched when it is opened to be edited. */
    public function one(Request $request)
    {
        $note = $this->holderFor($request, false);
        if (! $note) {
            return $this->jsonFail('That drawing is no longer here.', 404);
        }

        $media = $this->mediaOf($note);
        $i = (int) $request->query('index');
        if (! isset($media[$i])) {
            return $this->jsonFail('That drawing is no longer here.', 404);
        }

        return response()->json(['success' => true, 'data' => [
            'title' => (string) ($note->title ?? ''),
            'note' => trim(strip_tags((string) ($note->body ?? $note->content ?? $note->noteContent ?? ''))),
            'strokes' => $media[$i]['strokes'] ?? null,
            'url' => \App\Support\MediaStore::url($media[$i]['path'] ?? null),
        ]]);
    }

    /**
     * Keep a drawing: a new note, or a new version of one already saved.
     *
     * `editable` decides what it becomes — a flat picture to look at, or a
     * drawing that carries its strokes and can be reopened. Both are the same
     * PNG; only the second costs anything extra to store.
     */
    public function save(Request $request)
    {
        // A season's drawing is written back where it lives, by someone who
        // may write there; a new one is always a note of your own.
        $sid = (int) $request->query('scheduleId');
        $schedule = $sid > 0 ? $this->scheduleFromRequest($request) : null;
        $me = (int) \Illuminate\Support\Facades\Auth::id();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:191',
            // A drawing is a note, so it gets to say what it is about.
            'note' => 'nullable|string|max:2000',
            'image' => 'required|string',
            'editable' => 'nullable|boolean',
            // max:4000 counts the top level, which for a paged drawing is the
            // page list — the rule is what counts the objects inside them.
            'strokes' => ['nullable', 'array', 'max:4000', DrawStrokes::rule()],
            'noteId' => 'nullable|integer',
            'index' => 'nullable|integer|min:0',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $binary = $this->decodeDataUrlImage((string) $request->input('image'));
        if ($binary === null) {
            return $this->jsonFail('Could not read the drawing.', 422);
        }

        $editable = $request->boolean('editable');
        $strokes = $editable ? ($request->input('strokes') ?: null) : null;
        $scope = $schedule ? $schedule->id : 'u' . $me;
        $path = \App\Support\MediaStore::putBinary($binary, 'drawings', 'png', $scope);
        if ($path === null) {
            return $this->jsonFail('Could not keep that drawing.', 500);
        }

        // A stamp-sized copy beside the picture, for every shelf that lists
        // it (the pad's grid, the Gallery, the tag sheet) -- so a row of
        // thumbnails is a few kilobytes, not the season's canvases.
        $thumbBin = \App\Support\ImageThumb::png($binary, 480, 360);
        $thumb = $thumbBin ? \App\Support\MediaStore::putBinary($thumbBin, 'drawings', 'png', $scope, 'thumb-') : null;

        $entry = array_filter([
            'type' => $editable ? 'drawing' : 'image',
            'path' => $path,
            'thumb' => $thumb,
            'strokes' => $strokes,
        ], fn ($v) => $v !== null);

        $noteId = (int) $request->input('noteId');
        $source = (string) $request->input('source');
        $note = $noteId ? $this->holder($schedule?->id ?? 0, $source, $noteId) : null;

        if ($note) {
            $media = $this->mediaOf($note);
            $i = (int) $request->input('index');
            // The old picture goes with the old version: nothing else points at
            // it, and a season of superseded drawings is dead weight on disk.
            $old = $media[$i]['path'] ?? null;
            $oldThumb = $media[$i]['thumb'] ?? null;
            $media[$i] = $entry;
            // Only a notebook note takes its words from the pad's save sheet.
            // A board note's words belong to the board's own editor, and a day
            // note has no title at all — for those, only the picture moves.
            if ($note instanceof AsScheduleNote) {
                $note->title = (string) $request->input('title');
                if ($request->has('note')) {
                    $note->body = $this->drawingBody((string) $request->input('note'));
                }
            }
            $note->media = array_values($media);
            $note->save();
            if ($old && $old !== $path) {
                \App\Support\MediaStore::delete($old);
            }
            if ($oldThumb && $oldThumb !== $thumb) {
                \App\Support\MediaStore::delete($oldThumb);
            }
        } else {
            $note = AsScheduleNote::create([
                'croppingScheduleId' => 0,
                'userId' => $me,
                'title' => (string) $request->input('title'),
                'body' => $this->drawingBody((string) $request->input('note')),
                'media' => [$entry],
                'deleteStatus' => 1,
            ]);
        }

        // Tags are a season's words, so only a season's drawing wears them.
        if ($schedule && $request->has('tags') && (int) ($note->croppingScheduleId ?? 0) === $schedule->id) {
            \App\Support\ScheduleTags::sync($schedule, 'note', (int) $note->id, $request->input('tags', []));
        }

        return response()->json(['success' => true, 'message' => 'Drawing saved.', 'data' => [
            'noteId' => (int) $note->id,
            'index' => (int) $request->input('index', 0),
            'url' => \App\Support\MediaStore::url($path),
            'editable' => $editable,
            'pages' => DrawStrokes::pageCount($strokes),
            'title' => (string) $note->title,
            'note' => trim(strip_tags((string) ($note->body ?? $note->content ?? $note->noteContent ?? ''))),
            'scheduleId' => (int) ($note->croppingScheduleId ?? 0),
            'noteHref' => $note instanceof AsScheduleNote
                ? ((int) $note->croppingScheduleId > 0
                    ? route('sm.notes', ['id' => $note->croppingScheduleId, 'open' => $note->id])
                    : route('notes.hub', ['open' => $note->id]))
                : null,
        ]]);
    }

    /** The description, kept as the note body — plain text, one paragraph. */
    private function drawingBody(string $text): ?string
    {
        $text = trim($text);

        return $text === '' ? null : '<p>' . nl2br(e($text)) . '</p>';
    }

    /** Remove a drawing — and the note with it when that was all it held. */
    public function remove(Request $request)
    {
        $note = $this->holderFor($request, true);
        if (! $note) {
            return $this->jsonOk('Already gone.');
        }

        $media = $this->mediaOf($note);
        $i = (int) $request->input('index');
        $path = $media[$i]['path'] ?? null;
        unset($media[$i]);
        $media = array_values($media);

        // A note that was only ever this drawing has nothing left to say.
        $words = trim(strip_tags((string) ($note->body ?? $note->content ?? $note->noteContent ?? '')));
        if (empty($media) && blank($words)) {
            $note->update(['deleteStatus' => 0]);
        } else {
            $note->media = $media ?: null;
            $note->save();
        }
        if ($path) {
            \App\Support\MediaStore::delete($path);
        }

        return $this->jsonOk('Drawing deleted.');
    }

    // ------------------------------------------------------------------

    private function note(int $scheduleId, int $id): ?AsScheduleNote
    {
        if (! $id) {
            return null;
        }
        $q = AsScheduleNote::active()->where('croppingScheduleId', $scheduleId)->where('id', $id);
        // Your own notes are yours alone.
        if ($scheduleId === 0) {
            $q->where('userId', (int) \Illuminate\Support\Facades\Auth::id());
        }

        return $q->first();
    }

    /**
     * The note a request means: ?scheduleId= (0 or absent for your own),
     * &source=, &noteId=. A season's note is reached through the season, so
     * its access and (for a write) its edit and lock rules still hold.
     */
    private function holderFor(Request $request, bool $writing)
    {
        $sid = (int) $request->query('scheduleId');
        if ($sid > 0) {
            $schedule = $writing ? $this->scheduleFromRequest($request) : $this->schedule($sid);
            $sid = $schedule->id;
        }
        $source = (string) ($request->input('source') ?? $request->query('source'));
        $id = (int) ($request->input('noteId') ?? $request->query('noteId'));

        return $this->holder($sid, $sid > 0 ? $source : 'note', $id);
    }

    /**
     * The record holding a drawing, whichever shelf it lives on: the notebook
     * ('note', the default -- scheduleId 0 is your own), a sticky note on the
     * board ('inline'), or a day's own note ('date').
     */
    private function holder(int $scheduleId, string $source, int $id)
    {
        if (! $id) {
            return null;
        }

        return match ($source) {
            'inline' => \App\Models\AsInlineNote::active()
                ->where('croppingScheduleId', $scheduleId)->where('id', $id)->first(),
            'date' => \App\Models\AsScheduleDateNote::active()
                ->where('croppingScheduleId', $scheduleId)->where('id', $id)->first(),
            default => $this->note($scheduleId, $id),
        };
    }

    /** @return array<int, array<string, mixed>> */
    private function mediaOf($note): array
    {
        $media = $note->media;
        if (is_string($media)) {
            $media = json_decode($media, true);
        }

        return is_array($media) ? array_values($media) : [];
    }

    private function decodeDataUrlImage(string $dataUrl): ?string
    {
        if (! preg_match('~^data:image/(png|jpe?g|webp);base64,~i', $dataUrl)) {
            return null;
        }
        $binary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);

        // 12MB of canvas is already far more than a pad ever produces.
        return ($binary === false || strlen($binary) > 12_000_000) ? null : $binary;
    }
}
