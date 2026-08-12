<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleNote;
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

        $drawings = [];
        $notes = AsScheduleNote::active()
            ->where('croppingScheduleId', $schedule->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        foreach ($notes as $note) {
            foreach ($this->mediaOf($note) as $i => $m) {
                $type = (string) ($m['type'] ?? '');
                $path = (string) ($m['path'] ?? '');
                $team = (bool) preg_match(self::TEAM_FILE, $path);
                if ($type !== 'drawing' && ! $team) {
                    continue;
                }
                $drawings[] = [
                    'noteId' => (int) $note->id,
                    'index' => (int) $i,
                    'title' => (string) $note->title,
                    // Strokes are not sent with the list — a season of drawings
                    // would be megabytes of them. They come one at a time, when
                    // a drawing is actually opened for editing.
                    'editable' => $type === 'drawing' && ! empty($m['strokes']),
                    'team' => $team,
                    'url' => $path ? Storage::disk('public')->url($path) : null,
                    'when' => $note->updated_at?->timezone('Asia/Manila')->format('M j, Y'),
                ];
            }
        }

        return view('sm.draw', [
            'schedule' => $schedule,
            'drawings' => $drawings,
        ]);
    }

    /** The strokes behind one drawing, fetched when it is opened to be edited. */
    public function one(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $note = $this->note($schedule->id, (int) $request->query('noteId'));
        if (! $note) {
            return $this->jsonFail('That drawing is no longer here.', 404);
        }

        $media = $this->mediaOf($note);
        $i = (int) $request->query('index');
        if (! isset($media[$i])) {
            return $this->jsonFail('That drawing is no longer here.', 404);
        }

        return response()->json(['success' => true, 'data' => [
            'title' => (string) $note->title,
            'strokes' => $media[$i]['strokes'] ?? null,
            'url' => ($media[$i]['path'] ?? null) ? Storage::disk('public')->url($media[$i]['path']) : null,
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
            'image' => 'required|string',
            'editable' => 'nullable|boolean',
            'strokes' => 'nullable|array|max:4000',
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
        $path = 'schedule-notes/' . $schedule->id . '/draw-' . Str::random(20) . '.png';
        Storage::disk('public')->put($path, $binary);

        $entry = array_filter([
            'type' => $editable ? 'drawing' : 'image',
            'path' => $path,
            'strokes' => $strokes,
        ], fn ($v) => $v !== null);

        $noteId = (int) $request->input('noteId');
        $note = $noteId ? $this->note($schedule->id, $noteId) : null;

        if ($note) {
            $media = $this->mediaOf($note);
            $i = (int) $request->input('index');
            // The old picture goes with the old version: nothing else points at
            // it, and a season of superseded drawings is dead weight on disk.
            $old = $media[$i]['path'] ?? null;
            $media[$i] = $entry;
            $note->title = (string) $request->input('title');
            $note->media = array_values($media);
            $note->save();
            if ($old && $old !== $path) {
                Storage::disk('public')->delete($old);
            }
        } else {
            $note = AsScheduleNote::create([
                'croppingScheduleId' => $schedule->id,
                'userId' => \Illuminate\Support\Facades\Auth::id(),
                'title' => (string) $request->input('title'),
                'body' => null,
                'media' => [$entry],
                'deleteStatus' => 1,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Drawing saved.', 'data' => [
            'noteId' => (int) $note->id,
            'index' => (int) $request->input('index', 0),
            'url' => Storage::disk('public')->url($path),
            'editable' => $editable,
            'title' => (string) $note->title,
        ]]);
    }

    /** Remove a drawing — and the note with it when that was all it held. */
    public function remove(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $note = $this->note($schedule->id, (int) $request->input('noteId'));
        if (! $note) {
            return $this->jsonOk('Already gone.');
        }

        $media = $this->mediaOf($note);
        $i = (int) $request->input('index');
        $path = $media[$i]['path'] ?? null;
        unset($media[$i]);
        $media = array_values($media);

        // A note that was only ever this drawing has nothing left to say.
        if (empty($media) && blank(trim(strip_tags((string) $note->body)))) {
            $note->update(['deleteStatus' => 0]);
        } else {
            $note->media = $media ?: null;
            $note->save();
        }
        if ($path) {
            Storage::disk('public')->delete($path);
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

    /** @return array<int, array<string, mixed>> */
    private function mediaOf(AsScheduleNote $note): array
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
