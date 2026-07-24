<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleNote;
use App\Support\HtmlSanitizer;
use App\Support\UploadHelper;
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

        $file = $request->file('image');
        $ext = UploadHelper::safeExtension($file, ['jpg', 'jpeg', 'png', 'webp']);
        $stem = Str::uuid()->toString();
        $dir = 'schedule-notes/' . $schedule->id;

        try {
            Storage::disk('public')->putFileAs($dir, $file, $stem . '.' . $ext);
        } catch (\Throwable $e) {
            return $this->jsonFail('Photo upload failed: ' . $e->getMessage(), 500);
        }

        $path = $dir . '/' . $stem . '.' . $ext;

        return $this->jsonOk('Photo attached.', [
            'data' => ['path' => $path, 'url' => Storage::disk('public')->url($path)],
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
        ]);
        if ($validator->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $validator->errors()]);
        }

        $data = $validator->validated();
        // Body is client rich text → same allow-list the descriptions use.
        $data['body'] = filled($data['body'] ?? null) ? HtmlSanitizer::rich($data['body']) : null;
        $data['imagePath'] = $data['imagePath'] ?? null;

        return $data;
    }

    private function present(AsScheduleNote $n): array
    {
        return array_merge($n->toArray(), [
            'imageUrl' => $n->imagePath ? Storage::disk('public')->url($n->imagePath) : null,
            'updatedForHumans' => $n->updated_at?->diffForHumans(),
        ]);
    }
}
