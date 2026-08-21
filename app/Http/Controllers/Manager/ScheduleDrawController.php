<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleNote;
use App\Support\DrawStrokes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * The Draw module: the same pad the note editor and the Collab Room use, but
 * standing on its own like Maps.
 *
 * A drawing is not a new kind of record — it is a note whose attachment is a
 * picture the pad made. Keeping it that way means drawings are already in the
 * notebook, already searchable, already deletable, and the two modules never
 * disagree about what exists. This controller only lists them and writes them
 * back.
 */
class ScheduleDrawController extends BaseScheduleController
{
    /** A picture the team whiteboard saved, known by the name that save writes. */
    private const TEAM_FILE = '~/board-[A-Za-z0-9]+\.png$~';

    public function page(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request, 'id');
        // Drawing is a per-worker permission now; WorkerModuleAccess asks it
        // for every sm.draw* route, this page included.

        $drawings = [];
        $collect = function ($holder, string $source, string $title, string $words, ?string $noteHref) use (&$drawings) {
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
                    // Which shelf the note lives on — the board's sticky and
                    // day notes hold drawings too, and "every drawing in this
                    // schedule" has to mean all of them.
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
                    'when' => $holder->updated_at?->timezone('Asia/Manila')->format('M j, Y'),
                    'sortKey' => $holder->updated_at?->timestamp ?? 0,
                    // Every drawing lives in a note; this is the way back to
                    // the words that say why it was drawn.
                    'noteHref' => $noteHref,
                ];
            }
        };

        foreach (AsScheduleNote::active()->where('croppingScheduleId', $schedule->id)->orderByDesc('id')->limit(200)->get() as $note) {
            $collect($note, 'note', (string) $note->title, trim(strip_tags((string) $note->body)),
                route('sm.notes', ['id' => $schedule->id, 'open' => $note->id]));
        }
        foreach (\App\Models\AsInlineNote::active()->where('croppingScheduleId', $schedule->id)->orderByDesc('id')->limit(200)->get() as $note) {
            $collect($note, 'inline', (string) ($note->title ?: 'Note on the board'), trim(strip_tags((string) $note->content)),
                route('sm.activities', ['id' => $schedule->id]));
        }
        foreach (\App\Models\AsScheduleDateNote::active()->where('croppingScheduleId', $schedule->id)->orderByDesc('id')->limit(200)->get() as $note) {
            $collect($note, 'date', 'Note for ' . $note->noteDate->format('M j, Y'), trim(strip_tags((string) $note->noteContent)),
                route('sm.activities', ['id' => $schedule->id]));
        }

        // One shelf, newest first, wherever each drawing lives.
        usort($drawings, fn ($a, $b) => $b['sortKey'] <=> $a['sortKey']);

        return view('sm.draw', [
            'schedule' => $schedule,
            'drawings' => $drawings,
        ]);
    }

    /** The strokes behind one drawing, fetched when it is opened to be edited. */
    public function one(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $note = $this->holder($schedule->id, (string) $request->query('source'), (int) $request->query('noteId'));
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
        $schedule = $this->scheduleFromRequest($request);

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
        $path = \App\Support\MediaStore::putBinary($binary, 'drawings', 'png', $schedule->id);
        if ($path === null) {
            return $this->jsonFail('Could not keep that drawing.', 500);
        }

        $entry = array_filter([
            'type' => $editable ? 'drawing' : 'image',
            'path' => $path,
            'strokes' => $strokes,
        ], fn ($v) => $v !== null);

        $noteId = (int) $request->input('noteId');
        $source = (string) $request->input('source');
        $note = $noteId ? $this->holder($schedule->id, $source, $noteId) : null;

        if ($note) {
            $media = $this->mediaOf($note);
            $i = (int) $request->input('index');
            // The old picture goes with the old version: nothing else points at
            // it, and a season of superseded drawings is dead weight on disk.
            $old = $media[$i]['path'] ?? null;
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
        } else {
            $note = AsScheduleNote::create([
                'croppingScheduleId' => $schedule->id,
                'userId' => \Illuminate\Support\Facades\Auth::id(),
                'title' => (string) $request->input('title'),
                'body' => $this->drawingBody((string) $request->input('note')),
                'media' => [$entry],
                'deleteStatus' => 1,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Drawing saved.', 'data' => [
            'noteId' => (int) $note->id,
            'index' => (int) $request->input('index', 0),
            'url' => \App\Support\MediaStore::url($path),
            'editable' => $editable,
            'pages' => DrawStrokes::pageCount($strokes),
            'title' => (string) $note->title,
            'note' => trim(strip_tags((string) $note->body)),
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
        $schedule = $this->scheduleFromRequest($request);
        $note = $this->holder($schedule->id, (string) $request->input('source'), (int) $request->input('noteId'));
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
        return $id ? AsScheduleNote::active()
            ->where('croppingScheduleId', $scheduleId)
            ->where('id', $id)
            ->first() : null;
    }

    /**
     * The record holding a drawing, whichever shelf it lives on: the notebook
     * ('note', the default), a sticky note on the board ('inline'), or a
     * day's own note ('date').
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
