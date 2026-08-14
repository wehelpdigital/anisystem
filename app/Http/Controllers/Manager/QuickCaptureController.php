<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsScheduleNote;
use App\Support\HtmlSanitizer;
use App\Support\UploadHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Quick Capture — snap one or several photos, title them, describe them, and
 * drop the whole group into a schedule's notebook or a gallery album in one
 * go. (The "ask the AI Technician about this photo" path is handled
 * client-side via the existing ai.photo + ai.ask endpoints, so it lands in
 * the schedule's AI history.)
 *
 * A capture is one moment, so it becomes one note. Five photos of the same
 * flooded corner are five attachments on a single record, not five records
 * that each say a fifth of the story.
 */
class QuickCaptureController extends BaseScheduleController
{
    /** Save a captured photo group as one note on the chosen schedule. */
    public function storeNotes(Request $request)
    {
        // Asked before the upload is validated: someone who may not
        // write should hear that, not "capture a photo first".
        $schedule = $this->schedule($request->input('scheduleId'));
        $this->assertCanEdit();
        $this->assertUnlocked($schedule);

        $request->validate([
            'scheduleId' => 'required|integer',
            'title' => 'nullable|string|max:191',
            'note' => 'nullable|string|max:50000',
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192',
        ], [
            'images.required' => 'Capture at least one photo.',
            'images.*.max' => 'Each photo must be 8 MB or smaller.',
        ]);


        $body = filled($request->input('note'))
            ? HtmlSanitizer::rich($request->input('note'))
            : null;

        $title = filled($request->input('title'))
            ? trim((string) $request->input('title'))
            : 'Quick capture — ' . Carbon::now()->format('M j, Y g:i A');

        // One capture, one note. The photos ride along as its attachments —
        // the same shape the notes module writes when you attach several by
        // hand, so they open, zoom and download exactly the same way.
        $media = [];
        foreach ($request->file('images') as $file) {
            // The photo most likely to matter later was the one going straight
            // onto a disk that gets wiped on every deploy.
            $path = \App\Support\MediaStore::putFile($file, 'schedule-notes', $schedule->id);
            if ($path === null) {
                continue;
            }
            $media[] = ['type' => 'image', 'path' => $path];
        }

        if (! $media) {
            return $this->jsonFail('Nothing could be saved. Please try again.', 500);
        }

        $note = AsScheduleNote::create([
            'croppingScheduleId' => $schedule->id,
            'userId' => Auth::id(),
            'title' => $title,
            'body' => $body,
            // Left empty on purpose: imagePath is the legacy single-photo slot,
            // and a path in both places renders the first picture twice.
            'imagePath' => null,
            'media' => $media,
            'deleteStatus' => 1,
        ]);

        $count = count($media);

        return $this->jsonOk(
            $count . ' ' . str('photo')->plural($count) . ' saved in one note.',
            ['count' => $count, 'noteId' => $note->id, 'notesUrl' => route('sm.notes', ['id' => $schedule->id])]
        );
    }

    /**
     * Quick Record — one clip, named, filed.
     *
     * The photo path's sibling, and deliberately shorter: a video is one
     * thing, so there is no group to assemble and no AI question to ask (a
     * still is what the technician reads). Everything else is the same
     * shape — a note with the clip attached, or an album — because a
     * recording of a broken pump belongs beside the photo of it, not in a
     * place of its own.
     */
    public function storeClip(Request $request)
    {
        // Asked before the upload is validated: someone who may not
        // write should hear that, not "capture a photo first".
        $schedule = $this->schedule($request->input('scheduleId'));
        $this->assertCanEdit();
        $this->assertUnlocked($schedule);

        $request->validate([
            'scheduleId' => 'required|integer',
            'title' => 'nullable|string|max:191',
            'note' => 'nullable|string|max:50000',
            'target' => 'nullable|in:note,gallery',
            'albumId' => 'nullable|integer',
            'albumTitle' => 'nullable|string|max:191',
            // 300MB, the same ceiling the shared recorder enforces before it
            // will hand a clip over.
            'clip' => 'required|file|mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska,video/3gpp,video/x-m4v|max:307200',
        ], [
            'clip.required' => 'Record something first.',
            'clip.max' => 'That clip is larger than 300 MB — record a shorter one.',
        ]);


        $title = filled($request->input('title'))
            ? trim((string) $request->input('title'))
            : 'Quick record — ' . Carbon::now()->format('M j, Y g:i A');

        $body = filled($request->input('note'))
            ? HtmlSanitizer::rich($request->input('note'))
            : null;

        // Transcoded before it is stored, so a phone's 90MB minute becomes
        // something a farm connection can play back. Its complaints are
        // specific ("ffmpeg is missing", "that is not a video") and worth
        // more to the person than a generic failure would be.
        try {
            $stored = \App\Support\VideoOptimizer::storeCompressed($request->file('clip'), 'schedule-notes');
        } catch (\Throwable $e) {
            return $this->jsonFail($e->getMessage() ?: 'The clip could not be saved.', 422);
        }

        $media = [array_filter([
            'type' => 'video',
            'path' => $stored['video'],
            'poster' => $stored['poster'] ?? null,
        ], fn ($v) => $v !== null)];

        if ($request->input('target') === 'gallery') {
            $album = $this->albumFor($schedule, $request);
            \App\Models\AsGalleryImage::create([
                'albumId' => $album->id,
                'croppingScheduleId' => $schedule->id,
                'userId' => Auth::id(),
                'path' => $stored['video'],
                'caption' => $title,
                'deleteStatus' => 1,
            ]);

            return $this->jsonOk('Clip saved to "' . $album->title . '".', [
                'albumId' => $album->id,
                'galleryUrl' => route('sm.gallery', ['id' => $schedule->id]),
            ]);
        }

        $note = AsScheduleNote::create([
            'croppingScheduleId' => $schedule->id,
            'userId' => Auth::id(),
            'title' => $title,
            'body' => $body,
            'imagePath' => null,
            'media' => $media,
            'deleteStatus' => 1,
        ]);

        return $this->jsonOk('Clip saved as a note.', [
            'noteId' => $note->id,
            'notesUrl' => route('sm.notes', ['id' => $schedule->id]),
        ]);
    }

    /**
     * The album a capture is going into: the one chosen, or a new one.
     *
     * Never refuses the pictures for want of a name — the typed name wins,
     * then the capture's own title, then today's date. Losing a photo
     * because a field was blank is the worst outcome available here.
     */
    private function albumFor($schedule, Request $request): \App\Models\AsGalleryAlbum
    {
        if ($request->filled('albumId')) {
            $album = \App\Models\AsGalleryAlbum::where('croppingScheduleId', $schedule->id)
                ->where('deleteStatus', 1)->find((int) $request->input('albumId'));
            if ($album) {
                return $album;
            }
        }

        $name = filled($request->input('albumTitle'))
            ? trim((string) $request->input('albumTitle'))
            : (filled($request->input('title'))
                ? trim((string) $request->input('title'))
                : 'Quick capture — ' . Carbon::now()->format('M j, Y'));

        return \App\Models\AsGalleryAlbum::create([
            'croppingScheduleId' => $schedule->id,
            'userId' => Auth::id(),
            'title' => $name,
            'description' => filled($request->input('note'))
                ? Str::limit(trim(strip_tags((string) $request->input('note'))), 480)
                : null,
            'sortOrder' => 0,
            'deleteStatus' => 1,
        ]);
    }

    /** The albums a capture could go into, for the gallery picker. */
    public function albums(Request $request)
    {
        $schedule = $this->schedule($request->input('scheduleId'));

        $albums = \App\Models\AsGalleryAlbum::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->orderBy('sortOrder')->orderByDesc('id')
            ->get(['id', 'title'])
            ->map(fn ($a) => ['id' => $a->id, 'title' => $a->title])
            ->values()->all();

        return $this->jsonOk('', ['data' => ['albums' => $albums]]);
    }

    /** Save a captured photo group into a gallery album, new or existing. */
    public function storeGallery(Request $request)
    {
        // Asked before the upload is validated: someone who may not
        // write should hear that, not "capture a photo first".
        $schedule = $this->schedule($request->input('scheduleId'));
        $this->assertCanEdit();
        $this->assertUnlocked($schedule);

        $request->validate([
            'scheduleId' => 'required|integer',
            'albumId' => 'nullable|integer',
            'albumTitle' => 'nullable|string|max:191',
            'title' => 'nullable|string|max:191',
            'note' => 'nullable|string|max:50000',
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192',
        ], [
            'images.required' => 'Capture at least one photo.',
            'images.*.max' => 'Each photo must be 8 MB or smaller.',
        ]);


        $album = $this->albumFor($schedule, $request);

        $caption = filled($request->input('title'))
            ? Str::limit(trim((string) $request->input('title')), 250)
            : null;

        $added = 0;
        foreach ($request->file('images') as $file) {
            $path = \App\Support\MediaStore::putFile($file, 'gallery', $schedule->id);
            if ($path === null) {
                continue;
            }
            \App\Models\AsGalleryImage::create([
                'albumId' => $album->id,
                'croppingScheduleId' => $schedule->id,
                'userId' => Auth::id(),
                'path' => $path,
                'caption' => $caption,
                'deleteStatus' => 1,
            ]);
            $added++;
        }

        if (! $added) {
            return $this->jsonFail('Nothing could be saved. Please try again.', 500);
        }

        return $this->jsonOk(
            $added . ' ' . str('photo')->plural($added) . ' saved to \'' . $album->title . '\'.',
            [
                'count' => $added,
                'albumId' => $album->id,
                'galleryUrl' => route('sm.gallery', ['id' => $schedule->id]),
            ]
        );
    }
}
