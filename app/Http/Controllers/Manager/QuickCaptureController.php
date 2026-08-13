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

        $schedule = $this->schedule($request->input('scheduleId'));

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

        $schedule = $this->schedule($request->input('scheduleId'));

        $album = null;
        if ($request->filled('albumId')) {
            $album = \App\Models\AsGalleryAlbum::where('croppingScheduleId', $schedule->id)
                ->where('deleteStatus', 1)->find((int) $request->input('albumId'));
        }
        if (! $album) {
            // No album chosen — make one rather than refuse the pictures. The
            // typed name wins; failing that the capture's own title; failing
            // that, today.
            $name = filled($request->input('albumTitle'))
                ? trim((string) $request->input('albumTitle'))
                : (filled($request->input('title'))
                    ? trim((string) $request->input('title'))
                    : 'Quick capture — ' . Carbon::now()->format('M j, Y'));

            $album = \App\Models\AsGalleryAlbum::create([
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
