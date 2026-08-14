<?php

namespace App\Http\Controllers;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleDateNote;
use App\Models\AsScheduleNote;
use App\Support\HtmlSanitizer;
use App\Support\MediaOptimizer;
use App\Support\VideoOptimizer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * The all-in-one Notes hub: every note the grower has anywhere — free-standing
 * "global" notes, per-schedule notebook notes, and per-day activity notes —
 * gathered in one place, each tagged by kind and linking back to where it
 * lives. New notes created here are global (not tied to a schedule) and can
 * carry a drawing.
 *
 * Global notes reuse AsScheduleNote with croppingScheduleId = 0.
 */
class NotesHubController extends Controller
{
    private const GLOBAL_SCHEDULE_ID = 0;

    public function index(Request $request)
    {
        $userId = (int) Auth::id();
        $scheduleIds = AsCroppingSchedule::active()->forClient($userId)->pluck('id', 'id');
        $titles = AsCroppingSchedule::active()->forClient($userId)->pluck('title', 'id');

        $items = collect();

        // Global notes (this hub's own).
        foreach (AsScheduleNote::active()->where('userId', $userId)->where('croppingScheduleId', self::GLOBAL_SCHEDULE_ID)->orderByDesc('id')->get() as $n) {
            $items->push($this->row($n->id, 'global', $n->title, $n->body, $n->imagePath, 'Global note', null, $n->updated_at, $n->media));
        }

        // Per-schedule notebook notes.
        foreach (AsScheduleNote::active()->whereIn('croppingScheduleId', $scheduleIds->keys())->where('croppingScheduleId', '!=', self::GLOBAL_SCHEDULE_ID)->orderByDesc('id')->get() as $n) {
            $items->push($this->row(
                $n->id, 'schedule', $n->title, $n->body, $n->imagePath,
                $titles[$n->croppingScheduleId] ?? 'Schedule',
                route('sm.notes', ['id' => $n->croppingScheduleId]),
                $n->updated_at, $n->media
            ));
        }

        // Per-day activity notes.
        foreach (AsScheduleDateNote::active()->whereIn('croppingScheduleId', $scheduleIds->keys())->orderByDesc('id')->get() as $n) {
            $date = $n->noteDate?->format('M j, Y');
            $items->push($this->row(
                $n->id, 'day', ($titles[$n->croppingScheduleId] ?? 'Schedule') . ' — ' . $date, $n->noteContent, null,
                'Day note · ' . $date,
                route('sm.activities', ['id' => $n->croppingScheduleId]),
                $n->updated_at, $n->media
            ));
        }

        // The notes people actually write on a day.
        //
        // The board's +note has made these since the day a day could hold more
        // than one, so a farm's whole working record — every photo taken in a
        // field, every clip of a broken pump — lived in a table this hub had
        // never heard of. "All my notes" listed everything except the notes.
        foreach (\App\Models\AsInlineNote::active()->whereIn('croppingScheduleId', $scheduleIds->keys())->orderByDesc('id')->get() as $n) {
            $date = $n->noteDate?->format('M j, Y');
            $items->push($this->row(
                $n->id,
                'day',
                $n->title ?: (($titles[$n->croppingScheduleId] ?? 'Schedule') . ' — ' . $date),
                $n->content,
                null,
                ($titles[$n->croppingScheduleId] ?? 'Schedule') . ' · ' . $date,
                route('sm.activities', ['id' => $n->croppingScheduleId]),
                $n->updated_at,
                $n->media
            ));
        }

        $all = $items->sortByDesc(fn ($i) => $i['ts'])->values();

        // One shelf out of three tables, so the page is cut here rather than
        // in SQL. A season's notes are a page at a time either way — the
        // whole list arriving at once is what made this screen a scroll.
        $perPage = 15;
        $page = max(1, (int) $request->query('page', 1));
        $notes = new \Illuminate\Pagination\LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // The infinite scroller asks for one page's worth of cards and
        // nothing else — no layout, no scripts, just the next stretch.
        if ($request->boolean('rows')) {
            return response()->view('sm.partials.notes-hub-rows', ['notes' => $notes]);
        }

        return view('sm.notes-hub', ['notes' => $notes]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:191',
            'body' => 'nullable|string|max:20000',
            'imagePath' => 'nullable|string|max:500',
            'media'          => 'nullable|array|max:20',
            'media.*.type'   => 'required_with:media|in:image,video',
            'media.*.path'   => 'required_with:media|string|max:500',
            'media.*.poster' => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $body = HtmlSanitizer::rich($request->input('body'));
        $media = $this->normalizeMedia($request->input('media'));
        $hasBody = filled(trim(strip_tags($body))) || str_contains($body, '<img');
        $title = trim((string) $request->input('title'));
        if ($title === '' && ! $hasBody && ! $request->filled('imagePath') && empty($media)) {
            return response()->json(['success' => false, 'message' => 'Write something, draw, or add a photo/video.'], 422);
        }
        // No title field in the modal editor — derive one from the body text.
        if ($title === '') {
            $title = Str::limit(trim(strip_tags($body)), 60, '') ?: 'Untitled note';
        }

        $note = AsScheduleNote::create([
            'croppingScheduleId' => self::GLOBAL_SCHEDULE_ID,
            'userId' => (int) Auth::id(),
            'title' => $title,
            'body' => $body,
            'imagePath' => $request->input('imagePath'),
            'media' => $media,
            'deleteStatus' => 1,
        ]);

        return response()->json(['success' => true, 'message' => 'Note saved.', 'data' => ['id' => $note->id]]);
    }

    /** Photo for a global note — compressed to WebP. */
    public function imageUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Pick a photo (max 8 MB).', 'errors' => $validator->errors()], 422);
        }
        try {
            $path = MediaOptimizer::storeImageAsWebp($request->file('image'), 'notes/photos', 1600, 82);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Photo upload failed: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'data' => ['type' => 'image', 'path' => $path, 'url' => \App\Support\MediaStore::url($path)]]);
    }

    /** Video for a global note — compressed to ≤720p H.264 with a poster. */
    public function videoUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska,video/3gpp,video/x-msvideo|max:307200',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Pick a video (max 300 MB).', 'errors' => $validator->errors()], 422);
        }
        try {
            $out = VideoOptimizer::storeCompressed($request->file('video'), 'notes/videos');
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Video processing failed: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'data' => [
            'type' => 'video',
            'path' => $out['video'],
            'poster' => $out['poster'] ?? null,
            'url' => \App\Support\MediaStore::url($out['video']),
            'posterUrl' => ! empty($out['poster']) ? \App\Support\MediaStore::url($out['poster']) : null,
        ]]);
    }

    private function normalizeMedia($media): ?array
    {
        return collect(is_array($media) ? $media : [])
            ->filter(fn ($m) => in_array($m['type'] ?? '', ['image', 'video'], true) && filled($m['path'] ?? null))
            ->map(fn ($m) => array_filter([
                'type' => $m['type'], 'path' => $m['path'], 'poster' => $m['poster'] ?? null,
            ], fn ($v) => $v !== null))
            ->values()->all() ?: null;
    }

    private function mediaWithUrls($media): array
    {
        return collect(is_array($media) ? $media : [])
            ->map(fn ($m) => empty($m['path']) ? null : [
                'type' => $m['type'] ?? 'image',
                'url' => \App\Support\MediaStore::url($m['path']),
                'posterUrl' => ! empty($m['poster']) ? \App\Support\MediaStore::url($m['poster']) : null,
            ])
            ->filter()->values()->all();
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->query('id');
        $note = AsScheduleNote::active()
            ->where('id', $id)
            ->where('userId', (int) Auth::id())
            ->where('croppingScheduleId', self::GLOBAL_SCHEDULE_ID)
            ->first();
        if (! $note) {
            return response()->json(['success' => false, 'message' => 'Note not found.'], 404);
        }
        $note->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Note deleted.']);
    }

    /** Accept a base64 PNG data URL from the drawing pad → store as WebP. */
    public function drawUpload(Request $request)
    {
        $data = (string) $request->input('image', '');
        if (! preg_match('#^data:image/png;base64,#', $data)) {
            return response()->json(['success' => false, 'message' => 'Invalid drawing.'], 422);
        }
        $binary = base64_decode(substr($data, strpos($data, ',') + 1), true);
        if ($binary === false || strlen($binary) > 6_000_000) {
            return response()->json(['success' => false, 'message' => 'Drawing too large.'], 422);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'draw') . '.png';
        file_put_contents($tmp, $binary);
        try {
            $uploaded = new UploadedFile($tmp, 'drawing.png', 'image/png', null, true);
            $path = MediaOptimizer::storeImageAsWebp($uploaded, 'notes/drawings', 1400, 88);
        } finally {
            @unlink($tmp);
        }

        return response()->json([
            'success' => true,
            'data' => ['path' => $path, 'url' => \App\Support\MediaStore::url($path)],
        ]);
    }

    private function row($id, string $type, ?string $title, ?string $body, ?string $imagePath, string $address, ?string $url, $ts, $media = null): array
    {
        return [
            'id' => $id,
            'type' => $type,
            'title' => $title ?: 'Untitled',
            'body' => $body,
            'imageUrl' => $imagePath ? \App\Support\MediaStore::url($imagePath) : null,
            'media' => $this->mediaWithUrls($media),
            'address' => $address,
            'url' => $url,
            'ts' => $ts,
            'when' => $ts?->diffForHumans(),
        ];
    }
}
