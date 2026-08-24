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
        $lostPhotos = 0;
        foreach ($request->file('images') as $file) {
            // The photo most likely to matter later was the one going straight
            // onto a disk that gets wiped on every deploy.
            $path = \App\Support\MediaStore::putFile($file, 'schedule-notes', $schedule->id);
            // Counted, not swallowed. The gallery destination learned this the
            // hard way — five taken, two written, a green tick and "2 photos
            // saved" — and the notebook is the same sheet losing the same
            // photos to the same full or read-only disk.
            if ($path === null) {
                $lostPhotos++;

                continue;
            }
            $media[] = ['type' => 'image', 'path' => $path];
        }

        if (! $media) {
            return $this->jsonFail('Nothing could be saved. Please try again.', 500);
        }

        $trouble = $lostPhotos
            ? $lostPhotos . ' ' . str('photo')->plural($lostPhotos) . ' could not be saved.'
            : null;

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

        $message = $count . ' ' . str('photo')->plural($count) . ' saved in one note.';
        // Said in the message itself, not only in a flag: this reply's reader
        // renders the tick from `success` and the words from `message`, so a
        // photo that did not arrive has to be in the sentence to be seen at all.
        if ($trouble) {
            $message .= ' ' . $trouble;
        }

        return $this->jsonOk($message, [
            'count' => $count,
            'noteId' => $note->id,
            'notesUrl' => route('sm.notes', ['id' => $schedule->id]),
            // The same two keys the gallery destination returns, so both sheets
            // can be told apart by whoever reads them next.
            'partial' => $trouble !== null,
            'trouble' => $trouble,
        ]);
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
            'clip' => 'required|file|mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska,video/3gpp,video/x-m4v|max:2097152',
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

        // The words with the markup stripped — what a caption field can hold.
        $plainBody = $body !== null ? trim(strip_tags($body)) : '';

        $media = [array_filter([
            'type' => 'video',
            'path' => $stored['video'],
            'poster' => $stored['poster'] ?? null,
            // The clip's own name rides on the media entry, so the note's
            // chip can say what the video is instead of the word "Video".
            'title' => $title,
            'description' => $plainBody !== '' ? $plainBody : null,
        ], fn ($v) => $v !== null)];

        if ($request->input('target') === 'gallery') {
            $album = $this->albumFor($schedule, $request);
            // Same counting as the photo capture and the gallery's own +
            // button. Left to the column default this clip took 0, and
            // albums read `sortOrder asc, id desc` — so one recording made at
            // the end of a walk sat at the head of the album, above the
            // photos it belongs behind.
            $max = \App\Models\AsGalleryImage::where('albumId', $album->id)
                ->where('deleteStatus', 1)->max('sortOrder');
            $image = new \App\Models\AsGalleryImage([
                'albumId' => $album->id,
                'croppingScheduleId' => $schedule->id,
                'userId' => Auth::id(),
                'path' => $stored['video'],
                'caption' => $title,
                'sortOrder' => $max === null ? 0 : (int) $max + 1,
                'deleteStatus' => 1,
            ]);
            // The description asked for at record time belongs on the album
            // row too — the photos' path keeps it, this one used to drop it.
            // Assigned rather than mass-filled: the column is newer than
            // $fillable, and fill() drops strangers without saying so.
            if (\Illuminate\Support\Facades\Schema::hasColumn('as_gallery_images', 'description')) {
                $image->description = $plainBody !== '' ? $plainBody : null;
            }
            $image->save();

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

        // An album asked about on its own beats one inferred from the note.
        // Quick Record never asks, so the note stays the fallback there and
        // its albums keep the description they have always had.
        $about = filled($request->input('albumDescription'))
            ? trim((string) $request->input('albumDescription'))
            : (filled($request->input('note'))
                ? trim(strip_tags((string) $request->input('note')))
                : null);

        return \App\Models\AsGalleryAlbum::create([
            'croppingScheduleId' => $schedule->id,
            'userId' => Auth::id(),
            'title' => $name,
            'description' => $about !== null ? Str::limit($about, 480) : null,
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

    /**
     * Save a captured group into a gallery album, new or existing.
     *
     * An album is a shelf, not a story: the group shares a name and a
     * description, and every item on it may carry its own as well. Photos and
     * clips arrive in separate buckets because they are checked and stored by
     * different rules, but they are one list to the person who captured them —
     * `images[3]` and `clips[3]` cannot both exist, and `titles[3]` names
     * whichever one did.
     */
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
            'albumDescription' => 'nullable|string|max:2000',
            'title' => 'nullable|string|max:191',
            'note' => 'nullable|string|max:50000',
            // Neither kind is required on its own — an album may be all
            // photos, all clips, or a mix — only that something arrived.
            'images' => 'array|max:10|required_without:clips',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192',
            // The same 300MB ceiling and mime list the recorder enforces.
            'clips' => 'array|max:4|required_without:images',
            'clips.*' => 'file|mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska,video/3gpp,video/x-m4v|max:2097152',
            'titles' => 'nullable|array',
            'titles.*' => 'nullable|string|max:191',
            'descriptions' => 'nullable|array',
            'descriptions.*' => 'nullable|string|max:2000',
        ], [
            'images.required_without' => 'Capture at least one photo or clip.',
            'clips.required_without' => 'Capture at least one photo or clip.',
            'images.*.max' => 'Each photo must be 8 MB or smaller.',
            'clips.*.max' => 'Each clip must be 300 MB or smaller.',
        ]);


        $titles = (array) $request->input('titles', []);
        $descriptions = (array) $request->input('descriptions', []);

        // What an unnamed item falls back to: the capture's own title, which
        // is what every picture in a group used to be captioned with.
        $groupCaption = filled($request->input('title'))
            ? Str::limit(trim((string) $request->input('title')), 250)
            : null;

        // Keyed by the index the browser sent, so the two buckets can be put
        // back into the order they were captured in.
        $stored = [];
        $clipTrouble = null;
        $lostPhotos = 0;

        foreach ((array) $request->file('images', []) as $i => $file) {
            $path = \App\Support\MediaStore::putFile($file, 'gallery', $schedule->id);
            // A photo that will not store is exactly as lost as a clip that
            // will not convert, and this loop used to swallow it: five taken,
            // two written, a green tick and "2 photos saved". Counted, so the
            // reply below can say what did not arrive. Disks fill and disks go
            // read-only, and this app runs on one that is thrown away nightly.
            if ($path === null) {
                $lostPhotos++;

                continue;
            }
            $stored[$i] = ['path' => $path, 'kind' => 'image'];
        }

        foreach ((array) $request->file('clips', []) as $i => $file) {
            // Transcoded on the way in like every other clip in the app, so a
            // phone's 90MB minute is something a farm connection can play. A
            // clip that will not convert must not take the photos down with
            // it — its complaint is carried to the end and reported there.
            try {
                $clip = \App\Support\VideoOptimizer::storeCompressed($file, 'gallery');
            } catch (\Throwable $e) {
                $clipTrouble = $clipTrouble ?: ($e->getMessage() ?: 'A clip could not be saved.');

                continue;
            }
            $stored[$i] = ['path' => $clip['video'], 'kind' => 'video'];
        }

        ksort($stored);

        // One account of everything that did not make it, whichever bucket it
        // was in. Photos first because they are counted and clips speak for
        // themselves.
        $trouble = implode(' ', array_filter([
            $lostPhotos
                ? $lostPhotos . ' ' . str('photo')->plural($lostPhotos) . ' could not be saved.'
                : null,
            $clipTrouble,
        ])) ?: null;

        if (! $stored) {
            return $this->jsonFail($trouble ?: 'Nothing could be saved. Please try again.', 500);
        }

        // Only once there is something to put in it: a capture that lost
        // everything on the way in used to leave a brand new empty album
        // behind, which then had to be found and deleted by hand.
        $album = $this->albumFor($schedule, $request);

        // Asked once, not per picture: the description column arrives with a
        // migration, and a server that has the code but not yet the column
        // should drop the descriptions, not the whole capture.
        $canDescribe = \Illuminate\Support\Facades\Schema::hasColumn('as_gallery_images', 'description');

        // Where this capture starts in the album's order. Counting from zero
        // every time tore an existing album in half: everything already on the
        // shelf sits at 0, so the first new picture joined that block and the
        // rest sank below it. A capture is one walk and reads as one run.
        $max = \App\Models\AsGalleryImage::where('albumId', $album->id)
            ->where('deleteStatus', 1)->max('sortOrder');
        $order = $max === null ? 0 : (int) $max + 1;

        $added = 0;
        $counts = ['image' => 0, 'video' => 0];
        foreach ($stored as $i => $item) {
            $caption = filled($titles[$i] ?? null)
                ? Str::limit(trim((string) $titles[$i]), 250)
                : $groupCaption;

            $image = new \App\Models\AsGalleryImage([
                'albumId' => $album->id,
                'croppingScheduleId' => $schedule->id,
                'userId' => Auth::id(),
                'path' => $item['path'],
                'caption' => $caption,
                // The album reads back in the order the walk happened, rather
                // than newest-first inside a single capture.
                'sortOrder' => $order + $added,
                'deleteStatus' => 1,
            ]);
            // Assigned rather than mass-filled: `description` is newer than
            // the model's $fillable list, and fill() drops what it does not
            // recognise without saying so.
            if ($canDescribe) {
                $image->description = filled($descriptions[$i] ?? null)
                    ? trim((string) $descriptions[$i])
                    : null;
            }
            $image->save();

            $counts[$item['kind']]++;
            $added++;
        }

        $parts = [];
        if ($counts['image']) {
            $parts[] = $counts['image'] . ' ' . str('photo')->plural($counts['image']);
        }
        if ($counts['video']) {
            $parts[] = $counts['video'] . ' ' . str('clip')->plural($counts['video']);
        }

        $message = implode(' and ', $parts) . ' saved to \'' . $album->title . '\'.';
        if ($trouble) {
            $message .= ' ' . $trouble;
        }

        return $this->jsonOk($message, [
            'count' => $added,
            'albumId' => $album->id,
            // Some of it arrived and some of it did not, which is neither a
            // success nor a failure. Said out loud, because a green tick over
            // "Could not process the video" is how a lost clip goes unnoticed
            // until the day somebody goes looking for it.
            'partial' => $trouble !== null,
            'trouble' => $trouble,
            'galleryUrl' => route('sm.gallery', ['id' => $schedule->id]),
        ]);
    }
}
