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

        $notes = AsScheduleNote::active()
            ->where('croppingScheduleId', $schedule->id)
            ->orderByDesc('id')
            ->get();

        return view('sm.notes', ['schedule' => $schedule, 'notes' => $notes]);
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
            $path = MediaOptimizer::storeImageAsWebp($request->file('image'), 'schedule-notes/' . $schedule->id, 1600, 82);
        } catch (\Throwable $e) {
            return $this->jsonFail('Photo upload failed: ' . $e->getMessage(), 500);
        }

        return $this->jsonOk('Photo attached.', [
            'data' => ['type' => 'image', 'path' => $path, 'url' => Storage::disk('public')->url($path)],
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

        return $this->jsonOk('Video attached.', [
            'data' => [
                'type' => 'video',
                'path' => $out['video'],
                'poster' => $out['poster'] ?? null,
                'url' => Storage::disk('public')->url($out['video']),
                'posterUrl' => ! empty($out['poster']) ? Storage::disk('public')->url($out['poster']) : null,
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
            'media.*.type' => 'required_with:media|in:image,video',
            'media.*.path' => 'required_with:media|string|max:500',
            'media.*.poster' => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $data = $validator->validated();
        // Body is client rich text → same allow-list the descriptions use.
        $data['body'] = filled($data['body'] ?? null) ? HtmlSanitizer::rich($data['body']) : null;
        $data['imagePath'] = $data['imagePath'] ?? null;
        $data['media'] = collect($data['media'] ?? [])
            ->filter(fn ($m) => in_array($m['type'] ?? '', ['image', 'video'], true) && filled($m['path'] ?? null))
            ->map(fn ($m) => array_filter([
                'type' => $m['type'],
                'path' => $m['path'],
                'poster' => $m['poster'] ?? null,
            ], fn ($v) => $v !== null))
            ->values()->all() ?: null;

        return $data;
    }

    private function present(AsScheduleNote $n): array
    {
        return array_merge($n->toArray(), [
            'imageUrl' => $n->imagePath ? Storage::disk('public')->url($n->imagePath) : null,
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
                'url' => ! empty($m['path']) ? Storage::disk('public')->url($m['path']) : null,
                'posterUrl' => ! empty($m['poster']) ? Storage::disk('public')->url($m['poster']) : null,
            ])
            ->filter(fn ($m) => $m['url'])
            ->values()->all();
    }
}
