<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsGalleryAlbum;
use App\Models\AsGalleryImage;
use App\Support\MediaStore;
use App\Support\SeasonMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * The Gallery: pictures a grower groups on purpose.
 *
 * The Media Box gathers everything the season produced, ordered by where each
 * picture came from. This is the other thing — albums a person makes and
 * names: "the flooded corner in August", "what the buyer rejected". An image
 * belongs to exactly one album, which is what makes moving it a single field
 * rather than a bookkeeping problem.
 */
class GalleryController extends BaseScheduleController
{
    public function page(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request, 'id');

        // Two questions, two tabs. "Everything this season has a picture of"
        // is answered by reading the modules; "the ones I put together" is
        // answered by the albums. They were two modules, and a grower had to
        // know which one a picture had gone into to find it again.
        $everything = \App\Support\SeasonMedia::all($schedule);

        $teamBox = $this->teamBox($schedule);

        return view('sm.gallery', [
            'schedule' => $schedule,
            'albums' => $this->albumsFor($schedule),
            'everything' => $everything,
            'teamBox' => $teamBox,
            'counts' => [
                'all' => count(array_filter($everything, fn ($m) => $m['kind'] !== 'video')),
                'videos' => count(array_filter($everything, fn ($m) => $m['kind'] === 'video')),
                'team' => count($teamBox),
            ],
        ]);
    }

    /**
     * The Team box: what the Collab Room made, rather than what the season
     * produced.
     *
     * Three things people ask for by the same question — "where is that
     * thing we did together?" — and which were previously scattered:
     * recordings had nowhere to go, whiteboard drawings looked like any
     * other drawing, and saved maps sat in Maps. Newest first, because a
     * team's most recent work is what a team is usually looking for.
     */
    private function teamBox($schedule): array
    {
        $rows = [];

        foreach (\App\Models\TeamRecording::active()->where('scheduleId', $schedule->id)
            ->with('author')->orderByDesc('id')->get() as $r) {
            $rows[] = [
                'kind' => 'Recording',
                'title' => $r->title,
                'note' => $r->description,
                'by' => optional($r->author)->full_name,
                'url' => MediaStore::url($r->path),
                'posterUrl' => $r->poster ? MediaStore::url($r->poster) : null,
                'href' => null,
                'video' => true,
                'when' => $r->created_at?->timezone('Asia/Manila')->format('M j, Y g:i A'),
                'sortKey' => (int) ($r->created_at?->timestamp ?? 0),
            ];
        }

        // The whiteboard saves itself as an ordinary schedule note; what
        // marks one is where its pictures were written. Reading the marker
        // beats keeping a second table that could disagree with the first.
        foreach (\App\Models\AsScheduleNote::active()->where('croppingScheduleId', $schedule->id)
            ->whereNotNull('media')->orderByDesc('id')->get() as $n) {
            foreach ((is_array($n->media) ? $n->media : []) as $item) {
                $path = (string) ($item['path'] ?? '');
                if ($path === '' || ! str_contains($path, 'board-')) {
                    continue;
                }
                $rows[] = [
                    'kind' => 'Drawing',
                    'title' => $n->title ?: 'Team whiteboard',
                    'note' => $n->body ? trim(strip_tags($n->body)) : null,
                    'by' => null,
                    'url' => MediaStore::url($path),
                    'posterUrl' => null,
                    'href' => route('sm.collab', ['id' => $schedule->id]),
                    'video' => false,
                    'when' => $n->created_at?->timezone('Asia/Manila')->format('M j, Y'),
                    'sortKey' => (int) ($n->created_at?->timestamp ?? 0),
                ];
            }
        }

        foreach (\App\Models\ScheduleMapSave::active()->where('scheduleId', $schedule->id)
            ->orderByDesc('id')->get() as $m) {
            $picture = null;
            if ($m->noteId) {
                $note = \App\Models\AsScheduleNote::active()->find($m->noteId);
                foreach ((is_array($note?->media) ? $note->media : []) as $item) {
                    if (! empty($item['path'])) {
                        $picture = $item['path'];
                        break;
                    }
                }
            }
            if (! $picture) {
                continue;
            }
            $rows[] = [
                'kind' => 'Map',
                'title' => $m->title ?: 'Saved map',
                'note' => null,
                'by' => null,
                'url' => MediaStore::url($picture),
                'posterUrl' => null,
                // A map opens the map, not its picture — the shapes are the
                // point and they are still editable where they live.
                'href' => route('sm.maps', ['id' => $schedule->id, 'save' => $m->id]),
                'video' => false,
                'when' => $m->created_at?->timezone('Asia/Manila')->format('M j, Y'),
                'sortKey' => (int) ($m->created_at?->timestamp ?? 0),
            ];
        }

        usort($rows, fn ($a, $b) => $b['sortKey'] <=> $a['sortKey']);

        return $rows;
    }

    /** Albums with their pictures, newest album first. */
    private function albumsFor($schedule): array
    {
        return AsGalleryAlbum::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->orderBy('sortOrder')
            ->orderByDesc('id')
            ->with('images')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'description' => $a->description,
                // An album holds whatever was put in it, and Quick Record can
                // put a video there. The row does not say which, so the file
                // name has to — and without asking, every clip was rendered
                // into an <img>, failed to load, and reported itself missing
                // while sitting perfectly safe on disk.
                'images' => $a->images->map(fn ($i) => [
                    'id' => $i->id,
                    'url' => MediaStore::url($i->path),
                    // What the capture called this one picture, and what it
                    // said about it. Both were asked for, written, and then
                    // shown nowhere — the caption only ever reached an <img
                    // alt>, and the description did not leave the database.
                    'caption' => $i->caption,
                    'description' => $i->description,
                    // Asked of the one shared list rather than a private copy
                    // of the regex: the copies drifted, and the extension they
                    // drifted over was AVI — accepted by the uploader, filed
                    // here as a picture, rendered into an <img> that could only
                    // fail. One list, one answer.
                    'kind' => SeasonMedia::kindOf($i->path),
                ])->values()->all(),
            ])
            ->values()->all();
    }

    // ---------------------------------------------------------- albums ----

    public function albumSave(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'id' => 'nullable|integer',
            'title' => 'required|string|max:191',
            'description' => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $album = $request->filled('id')
            ? AsGalleryAlbum::where('croppingScheduleId', $schedule->id)
                ->where('deleteStatus', 1)->find((int) $request->input('id'))
            : null;

        $payload = [
            'title' => trim((string) $request->input('title')),
            'description' => filled($request->input('description')) ? trim($request->input('description')) : null,
        ];

        if ($album) {
            $album->update($payload);
        } else {
            $album = AsGalleryAlbum::create($payload + [
                'croppingScheduleId' => $schedule->id,
                'userId' => Auth::id(),
                'deleteStatus' => 1,
            ]);
        }

        return $this->jsonOk($request->filled('id') ? 'Album updated.' : 'Album created.', [
            'data' => ['id' => $album->id, 'title' => $album->title, 'description' => $album->description],
        ]);
    }

    /**
     * Remove an album. Its pictures are not collateral: they move to another
     * album when one is named, and are only removed with it when the caller
     * says so out loud.
     */
    public function albumDestroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $album = AsGalleryAlbum::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->find((int) $request->input('id'));
        if (! $album) {
            return $this->jsonFail('Album not found.', 404);
        }

        $moveTo = (int) $request->input('moveTo');
        $target = $moveTo
            ? AsGalleryAlbum::where('croppingScheduleId', $schedule->id)
                ->where('deleteStatus', 1)->where('id', '!=', $album->id)->find($moveTo)
            : null;

        if ($target) {
            AsGalleryImage::where('albumId', $album->id)->update(['albumId' => $target->id]);
        } elseif ($request->boolean('withImages')) {
            AsGalleryImage::where('albumId', $album->id)->update(['deleteStatus' => 0]);
        } elseif (AsGalleryImage::where('albumId', $album->id)->where('deleteStatus', 1)->exists()) {
            return $this->jsonFail('That album still has pictures. Move them somewhere, or say to delete them too.', 422);
        }

        $album->update(['deleteStatus' => 0]);

        return $this->jsonOk('Album removed.');
    }

    // ---------------------------------------------------------- images ----

    public function imageStore(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'albumId' => 'required|integer',
            'images' => 'required|array|max:20',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:12288',
            'caption' => 'nullable|string|max:255',
        ], ['images.*.max' => 'Each picture must be 12 MB or smaller.']);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $album = AsGalleryAlbum::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->find((int) $request->input('albumId'));
        if (! $album) {
            return $this->jsonFail('Album not found.', 404);
        }

        // Where these land on the shelf. Left to the column default they all
        // took 0, and images() reads `sortOrder asc, id desc` — so every
        // picture added with + jumped ahead of a whole capture run, and a
        // multi-file + upload came out backwards among itself. Same counting
        // as Quick Capture: an upload is one act and reads as one run.
        $max = AsGalleryImage::where('albumId', $album->id)
            ->where('deleteStatus', 1)->max('sortOrder');
        $order = $max === null ? 0 : (int) $max + 1;

        $added = [];
        foreach ($request->file('images') as $file) {
            $path = MediaStore::putFile($file, 'gallery', $schedule->id);
            if ($path === null) {
                continue;
            }
            $image = AsGalleryImage::create([
                'albumId' => $album->id,
                'croppingScheduleId' => $schedule->id,
                'userId' => Auth::id(),
                'path' => $path,
                'caption' => filled($request->input('caption')) ? trim($request->input('caption')) : null,
                'sortOrder' => $order + count($added),
                'deleteStatus' => 1,
            ]);
            $added[] = ['id' => $image->id, 'url' => MediaStore::url($path), 'caption' => $image->caption];
        }

        if (! $added) {
            return $this->jsonFail('Nothing could be saved.', 500);
        }

        return $this->jsonOk(count($added) . ' ' . \Illuminate\Support\Str::plural('picture', count($added)) . ' added.', [
            'data' => ['albumId' => $album->id, 'images' => $added],
        ]);
    }

    /** Move pictures to another album — one field, however many are chosen. */
    public function imageMove(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|max:200',
            'ids.*' => 'integer',
            'albumId' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return $this->jsonFail($validator->errors()->first(), 422);
        }

        $album = AsGalleryAlbum::where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)->find((int) $request->input('albumId'));
        if (! $album) {
            return $this->jsonFail('That album is no longer here.', 404);
        }

        // In the order they read in now, so a set that was a run in the old
        // album is still a run in the new one. Pictures already in the target
        // are not "moved" and are left exactly where they sit.
        $rows = AsGalleryImage::where('croppingScheduleId', $schedule->id)
            ->whereIn('id', $request->input('ids'))
            ->where('deleteStatus', 1)
            ->where('albumId', '!=', $album->id)
            ->orderBy('sortOrder')
            ->orderByDesc('id')
            ->get();

        // Changing only albumId carried each picture's OLD ordinal into the
        // new album: a 0 from the front of one shelf is a 0 at the front of
        // this one, and images() reads `sortOrder asc, id desc` — so moved
        // pictures cut into the middle of somebody's capture run. Re-seeded at
        // the tail, which is where every other writer here puts a batch, and
        // where the page shows them the moment you press Move.
        $max = AsGalleryImage::where('albumId', $album->id)
            ->where('deleteStatus', 1)->max('sortOrder');
        $order = $max === null ? 0 : (int) $max + 1;

        $moved = 0;
        foreach ($rows as $row) {
            $row->update(['albumId' => $album->id, 'sortOrder' => $order + $moved]);
            $moved++;
        }

        return $this->jsonOk($moved . ' ' . \Illuminate\Support\Str::plural('picture', $moved) . ' moved to “' . $album->title . '”.', [
            'data' => ['albumId' => $album->id, 'moved' => $moved],
        ]);
    }

    public function imageDestroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $ids = (array) $request->input('ids', []);
        if (! $ids) {
            return $this->jsonFail('Nothing chosen.', 422);
        }

        $rows = AsGalleryImage::where('croppingScheduleId', $schedule->id)
            ->whereIn('id', $ids)->where('deleteStatus', 1)->get();

        foreach ($rows as $row) {
            $row->update(['deleteStatus' => 0]);
            // The file goes with it: a deleted picture nobody can reach is
            // just storage being paid for.
            MediaStore::delete($row->path);
        }

        return $this->jsonOk($rows->count() . ' ' . \Illuminate\Support\Str::plural('picture', $rows->count()) . ' deleted.');
    }
}
