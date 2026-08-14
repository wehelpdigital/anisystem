<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleNote;
use App\Support\HtmlSanitizer;
use App\Support\MediaOptimizer;
use App\Support\UploadHelper;
use App\Support\VideoOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * The grower's notebook for a cropping schedule: titled rich-text notes, each
 * with an optional photo.
 */
class NoteController extends BaseScheduleController
{
    public function page(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request, 'id');

        // A season's notebook runs to hundreds; it arrives a page at a time.
        $notes = AsScheduleNote::active()
            ->where('croppingScheduleId', $schedule->id)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Saved team maps belong here too: a map the team named and kept is a
        // record of the season like any note, and hunting for it inside the
        // map's own tool menu is a detour.
        $saves = \App\Models\ScheduleMapSave::active()
            ->where('scheduleId', $schedule->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get();
        $savers = \App\Models\User::whereIn('id', $saves->pluck('userId')->unique())->get()->keyBy('id');
        $mapSaves = $saves->map(fn ($m) => [
            'id' => (int) $m->id,
            'noteId' => $m->noteId ? (int) $m->noteId : null,
            'title' => $m->title,
            'shapes' => count(json_decode((string) $m->objects, true) ?: []),
            'by' => (string) \Illuminate\Support\Str::of(optional($savers->get($m->userId))->full_name ?? 'Someone')->explode(' ')->first(),
            'when' => $m->created_at?->timezone('Asia/Manila')->format('M j, Y'),
        ]);

        // The scroller asks for cards alone — no layout, no scripts.
        if ($request->boolean('rows')) {
            return response()->view('sm.partials.notes-rows', [
                'notes' => $notes,
                'schedule' => $schedule,
                'mapSaves' => $mapSaves,
            ]);
        }

        return view('sm.notes', [
            'schedule' => $schedule,
            'notes' => $notes,
            'mapSaves' => $mapSaves,
        ]);
    }

    public function store(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $data = $this->validated($request);
        if (! is_array($data)) {
            return $data;
        }

        $note = AsScheduleNote::create($data + [
            'croppingScheduleId' => $schedule->id,
            'userId' => Auth::id(),
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('Note saved.', ['data' => $this->present($note)]);
    }

    public function update(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $note = $this->find($schedule->id, $this->queryId($request));
        if (! $note) {
            return $this->jsonFail('Note not found.', 404);
        }

        $data = $this->validated($request);
        if (! is_array($data)) {
            return $data;
        }

        $note->update($data);

        return $this->jsonOk('Note updated.', ['data' => $this->present($note->fresh())]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $note = $this->find($schedule->id, $this->queryId($request));
        if (! $note) {
            return $this->jsonFail('Note not found.', 404);
        }

        $note->update(['deleteStatus' => 0]);

        return $this->jsonOk('Note deleted.');
    }

    public function uploadImage(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ], [
            'image.required' => 'Pick a photo first.',
            'image.max' => 'Photo is too large — max 8 MB.',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        // Re-encode + downscale to WebP so a phone photo doesn't cost megabytes.
        try {
            $local = MediaOptimizer::storeImageAsWebp($request->file('image'), 'schedule-notes/' . $schedule->id, 1600, 82);
        } catch (\Throwable $e) {
            return $this->jsonFail('Photo upload failed: ' . $e->getMessage(), 500);
        }

        // Shrink first, then hand the small version to the store that keeps
        // things — no point shipping a 6MB phone photo across to be filed.
        $path = $local;
        if (\App\Support\MediaStore::enabled()) {
            $ext = pathinfo($local, PATHINFO_EXTENSION) ?: 'webp';
            $kept = \App\Support\MediaStore::putBinary(
                Storage::disk('public')->get($local), 'notes', $ext, $schedule->id
            );
            if ($kept !== null && $kept !== $local) {
                Storage::disk('public')->delete($local);
                $path = $kept;
            }
        }

        return $this->jsonOk('Photo attached.', [
            'data' => ['type' => 'image', 'path' => $path, 'url' => \App\Support\MediaStore::url($path)],
        ]);
    }

    /** Attach or record a video — compressed to ≤720p H.264 with a poster. */
    public function uploadVideo(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska,video/3gpp,video/x-msvideo|max:307200',
        ], [
            'video.required' => 'Pick a video first.',
            'video.max' => 'Video is too large — max 300 MB.',
            'video.mimetypes' => 'That file is not a supported video.',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        try {
            $out = VideoOptimizer::storeCompressed($request->file('video'), 'schedule-notes/' . $schedule->id . '/videos');
        } catch (\Throwable $e) {
            return $this->jsonFail('Video processing failed: ' . $e->getMessage(), 500);
        }

        // Compress here, keep there. The clip and its poster are handed to the
        // mother app once they are small, so a phone video costs one upload
        // across rather than its full size, and both survive a deploy.
        foreach (['video', 'poster'] as $part) {
            $local = $out[$part] ?? null;
            if (! $local || ! \App\Support\MediaStore::enabled()) {
                continue;
            }
            $kept = \App\Support\MediaStore::putBinary(
                Storage::disk('public')->get($local),
                'notes',
                pathinfo($local, PATHINFO_EXTENSION) ?: ($part === 'poster' ? 'jpg' : 'mp4'),
                $schedule->id
            );
            if ($kept && $kept !== $local) {
                Storage::disk('public')->delete($local);
                $out[$part] = $kept;
            }
        }

        return $this->jsonOk('Video attached.', [
            'data' => [
                'type' => 'video',
                'path' => $out['video'],
                'poster' => $out['poster'] ?? null,
                'url' => \App\Support\MediaStore::url($out['video']),
                'posterUrl' => ! empty($out['poster']) ? \App\Support\MediaStore::url($out['poster']) : null,
            ],
        ]);
    }

    // ------------------------------------------------------------------

    private function find(int $scheduleId, int $id): ?AsScheduleNote
    {
        return AsScheduleNote::active()
            ->where('croppingScheduleId', $scheduleId)
            ->where('id', $id)
            ->first();
    }

    /**
     * @return array<string, mixed>|\Illuminate\Http\JsonResponse
     */
    private function validated(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:191',
            'body' => 'nullable|string|max:50000',
            'imagePath' => 'nullable|string|max:500',
            'media' => 'nullable|array|max:20',
            // 'drawing' is an image that also carries its strokes, so it can be
            // reopened and edited; 'map' is a saved map picture.
            'media.*.type' => 'required_with:media|in:image,video,drawing,map',
            'media.*.path' => 'required_with:media|string|max:500',
            'media.*.poster' => 'nullable|string|max:500',
            'media.*.strokes' => 'nullable|array|max:4000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $data = $validator->validated();
        // Body is client rich text → same allow-list the descriptions use.
        $data['body'] = filled($data['body'] ?? null) ? HtmlSanitizer::rich($data['body']) : null;
        $data['imagePath'] = $data['imagePath'] ?? null;
        $data['media'] = collect($data['media'] ?? [])
            ->filter(fn ($m) => in_array($m['type'] ?? '', ['image', 'video', 'drawing', 'map'], true) && filled($m['path'] ?? null))
            ->map(fn ($m) => array_filter([
                'type' => $m['type'],
                'path' => $m['path'],
                'poster' => $m['poster'] ?? null,
                // Kept with the note rather than in a file: strokes are what
                // makes a drawing reopenable, and the disk is not permanent.
                'strokes' => ($m['type'] ?? '') === 'drawing' ? ($m['strokes'] ?? null) : null,
            ], fn ($v) => $v !== null))
            ->values()->all() ?: null;

        return $data;
    }

    private function present(AsScheduleNote $n): array
    {
        return array_merge($n->toArray(), [
            'imageUrl' => $n->imagePath ? \App\Support\MediaStore::url($n->imagePath) : null,
            'media' => $this->mediaWithUrls($n->media),
            'updatedForHumans' => $n->updated_at?->diffForHumans(),
        ]);
    }

    /** Resolve each stored media item's path/poster to a public URL. */
    private function mediaWithUrls($media): array
    {
        return collect(is_array($media) ? $media : [])
            ->map(fn ($m) => [
                'type' => $m['type'] ?? 'image',
                'path' => $m['path'] ?? null,
                'poster' => $m['poster'] ?? null,
                // Without the strokes a reopened drawing would come back as a
                // flat picture — editable is the whole point of the type.
                'strokes' => $m['strokes'] ?? null,
                'url' => ! empty($m['path']) ? \App\Support\MediaStore::url($m['path']) : null,
                'posterUrl' => ! empty($m['poster']) ? \App\Support\MediaStore::url($m['poster']) : null,
            ])
            ->filter(fn ($m) => $m['url'])
            ->values()->all();
    }
}
