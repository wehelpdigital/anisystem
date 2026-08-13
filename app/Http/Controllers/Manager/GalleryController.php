<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsGalleryAlbum;
use App\Models\AsGalleryImage;
use App\Support\MediaStore;
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

        return view('sm.gallery', [
            'schedule' => $schedule,
            'albums' => $this->albumsFor($schedule),
        ]);
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
                'images' => $a->images->map(fn ($i) => [
                    'id' => $i->id,
                    'url' => MediaStore::url($i->path),
                    'caption' => $i->caption,
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

        $moved = AsGalleryImage::where('croppingScheduleId', $schedule->id)
            ->whereIn('id', $request->input('ids'))
            ->where('deleteStatus', 1)
            ->update(['albumId' => $album->id]);

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
