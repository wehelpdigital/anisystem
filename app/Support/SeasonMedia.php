<?php

namespace App\Support;

use App\Models\AsGalleryImage;
use App\Models\AsInlineNote;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleNote;
use App\Models\ScheduleAiMessage;
use App\Models\ScheduleMapSave;

/**
 * Every picture and video a season has, wherever it was made.
 *
 * The parts of the app each own one kind of record, and that is right: a note
 * owns its words, a drawing owns its strokes, a map owns its shapes, an album
 * owns nothing but an arrangement. But a picture made in any of them is still
 * a picture of this season, and "show me everything" was a question none of
 * them could answer alone.
 *
 * Nothing here is copied. This reads the same rows the modules write, so a
 * photo deleted in Notes is gone from here the moment it is gone there, and
 * every item carries the way back to the record it belongs to — a drawing
 * opens in the pad that can change it, a map in Maps, a photo in the note
 * that explains why it was taken.
 */
class SeasonMedia
{
    /**
     * Photo or clip, read off the file name.
     *
     * The name is all there is to go on: the attachment columns hold a flat
     * list of paths and nothing records which is which. So the list of
     * extensions has to be one list — it was three, and the odd one out was
     * AVI, which the uploader accepted and this filed as a picture.
     *
     * Keep in step with the attach bar's VID_RE and with what
     * PostHarvestController::storeVideo() lets past the validator.
     */
    public static function kindOf(?string $path): string
    {
        return preg_match('~\.(mp4|mov|webm|mkv|m4v|3gp|avi)$~i', (string) $path) ? 'video' : 'image';
    }

    /**
     * @return list<array{kind:string, source:string, title:string, url:string,
     *     posterUrl:?string, href:?string, when:?string, sortKey:int,
     *     editable:bool}>
     */
    public static function all($schedule, bool $includeAlbums = true): array
    {
        $out = [];

        $push = function (?array $m, string $source, string $title, $when, ?string $href, bool $editable = false, ?int $albumImageId = null) use (&$out) {
            $path = $m['path'] ?? null;
            if (! filled($path)) {
                return;
            }
            $type = (string) ($m['type'] ?? 'image');
            // A saved map is a picture of a plan; a drawing is a picture with
            // strokes behind it. Both belong in "everything" — they were the
            // two things a grower could never find again.
            if ($type !== 'video' && $type !== 'drawing' && $type !== 'map') {
                $type = 'image';
            }

            $out[] = [
                'kind' => $type,
                'source' => $source,
                'title' => $title !== '' ? $title : 'Untitled',
                'url' => MediaStore::url($path),
                'posterUrl' => ! empty($m['poster']) ? MediaStore::url($m['poster']) : null,
                'href' => $href,
                'when' => $when?->timezone('Asia/Manila')->format('M j, Y'),
                'sortKey' => (int) ($when?->timestamp ?? 0),
                'editable' => $editable,
                // Only an album picture belongs to the Gallery itself. Every
                // other item here is a view of something that lives in a note,
                // a drawing or a map, and is deleted where it lives — which is
                // why only this one carries a row to delete.
                'albumImageId' => $albumImageId,
            ];
        };

        $notesUrl = route('sm.notes', ['id' => $schedule->id]);
        $boardUrl = route('sm.activities', ['id' => $schedule->id]);

        // The notebook. A drawing lives in a note, so this is also where most
        // drawings come from — tagged as drawings, opening in the pad.
        foreach (AsScheduleNote::active()->where('croppingScheduleId', $schedule->id)->orderByDesc('id')->get() as $n) {
            if (filled($n->imagePath)) {
                $push(['type' => 'image', 'path' => $n->imagePath], 'Note', (string) $n->title, $n->updated_at, $notesUrl);
            }
            foreach ((array) ($n->media ?? []) as $i => $m) {
                $type = (string) ($m['type'] ?? '');
                $isMap = $type === 'map' || (bool) preg_match('~/map-[A-Za-z0-9]+\.png$~', (string) ($m['path'] ?? ''));
                $isDrawing = $type === 'drawing' || (bool) preg_match('~/board-[A-Za-z0-9]+\.png$~', (string) ($m['path'] ?? ''));

                if ($isMap) {
                    $push(['type' => 'map'] + $m, 'Map', (string) $n->title, $n->updated_at,
                        route('sm.maps', ['id' => $schedule->id]));
                } elseif ($isDrawing) {
                    $push(['type' => 'drawing'] + $m, 'Drawing', (string) $n->title, $n->updated_at,
                        route('sm.draw', ['id' => $schedule->id, 'open' => $n->id . ':' . $i]), true);
                } else {
                    $push($m, 'Note', (string) $n->title, $n->updated_at, $notesUrl);
                }
            }
        }

        // Notes pinned to a day on the board.
        foreach (AsInlineNote::active()->where('croppingScheduleId', $schedule->id)->orderByDesc('id')->get() as $n) {
            $label = $n->noteDate ? 'Note for ' . $n->noteDate->format('M j, Y') : 'Day note';
            foreach ((array) ($n->media ?? []) as $m) {
                $push($m, 'Day note', $label, $n->updated_at, $boardUrl);
            }
        }

        /* The day's own note — the single one attached to the date itself,
         * as distinct from the several that sit between its cards.
         *
         * Two different tables, and only one of them was being read. A photo
         * taken into a day's note therefore existed, opened from the board,
         * and was nowhere in the Gallery — which is exactly the report that
         * pictures saved in a day note never show up. */
        foreach (\App\Models\AsScheduleDateNote::active()
            ->where('croppingScheduleId', $schedule->id)->orderByDesc('id')->get() as $n) {
            $label = $n->noteDate ? 'Note for ' . $n->noteDate->format('M j, Y') : 'Day note';
            foreach ((array) ($n->media ?? []) as $m) {
                $push($m, 'Day note', $label, $n->updated_at, $boardUrl);
            }
        }

        /* What the harvest looked like.
         *
         * A post-harvest observation is the one record a grower is most
         * likely to want a picture of a year later — what the grain looked
         * like, what the buyer rejected — and its photos were not on this
         * shelf either. Same narrowing as the activities below: only rows
         * that actually carry one. */
        foreach (\App\Models\AsSchedulePostHarvest::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where(fn ($q) => $q->whereNotNull('imagePath')->orWhereNotNull('imagePaths'))
            ->orderByDesc('id')
            ->get(['id', 'title', 'observationDate', 'imagePath', 'imagePaths', 'updated_at']) as $ph) {
            $label = $ph->title ?: 'Post-harvest observation';
            $paths = array_filter(array_merge(
                [$ph->imagePath],
                is_array($ph->imagePaths) ? $ph->imagePaths : []
            ));
            foreach (array_unique($paths) as $path) {
                // An observation can carry a clip now — the attach bar offers
                // Record — and this column is a flat list of paths with nothing
                // to say which. Asked of the name, the same way the album
                // branch below does it; hardcoding 'image' rendered the clip as
                // a broken picture.
                $push(['type' => self::kindOf($path), 'path' => $path], 'Post-harvest', $label, $ph->updated_at,
                    route('sm.post-harvest', ['id' => $schedule->id]));
            }
        }

        // Reference photos attached to activities. Only the ones that carry a
        // picture: a season is mostly tasks with none, and hydrating all of
        // them to ask was the slowest part of opening this page.
        $withPhotos = AsScheduleActivity::active()
            ->where('croppingScheduleId', $schedule->id)
            ->where('isDraft', 0)
            ->where(fn ($q) => $q->whereNotNull('imagePath')->orWhereNotNull('imagePaths'))
            ->orderByDesc('id')
            ->get(['id', 'activityTitle', 'imagePath', 'imagePaths', 'updated_at']);
        foreach ($withPhotos as $a) {
            foreach ($a->imageList() as $img) {
                $push(['type' => 'image', 'path' => $img['path'] ?? null], 'Activity',
                    (string) $a->activityTitle, $a->updated_at, $boardUrl);
            }
        }

        // Frames sent to the AI technician — a photo of a leaf someone asked
        // about is still a photo of this season.
        foreach (ScheduleAiMessage::active()->where('scheduleId', $schedule->id)
            ->whereNotNull('imagePath')->orderByDesc('id')->get() as $m) {
            $push(['type' => 'image', 'path' => $m->imagePath], 'Asked the AI',
                mb_strimwidth(trim(strip_tags((string) $m->content)) ?: 'Sent to the AI technician', 0, 80, '…'),
                $m->updated_at, route('sm.ai', ['id' => $schedule->id]));
        }

        // Pictures put in an album on purpose — often the ones taken with no
        // note to put them in.
        if ($includeAlbums) {
            foreach (AsGalleryImage::where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)
                ->orderByDesc('id')->get() as $img) {
                $push(
                    // Quick Record can file a clip in an album, so the album
                    // does not only hold pictures.
                    ['type' => self::kindOf($img->path), 'path' => $img->path],
                    'Album',
                    (string) ($img->caption ?: 'In an album'),
                    $img->updated_at,
                    route('sm.gallery', ['id' => $schedule->id]),
                    false,
                    (int) $img->id,
                );
            }
        }

        usort($out, fn ($a, $b) => $b['sortKey'] <=> $a['sortKey']);

        // A ceiling, because this list rides INSIDE the gallery page as JSON.
        // Every item is a couple hundred bytes; a long season's thousand items
        // was a quarter-megabyte of blocking HTML on a 3G phone before one
        // picture had loaded. Six hundred newest is more than the shelves can
        // usefully show; anything older is still reachable where it lives —
        // its note, its album, its module.
        return array_slice($out, 0, 600);
    }

    /**
     * Which note a saved map or drawing belongs to, keyed by note id.
     *
     * Both modules show a tag saying "this is in a note", and both need the
     * note's title to say which.
     *
     * @return array<int, string>
     */
    public static function noteTitles($schedule): array
    {
        return AsScheduleNote::active()
            ->where('croppingScheduleId', $schedule->id)
            ->pluck('title', 'id')
            ->map(fn ($t) => (string) $t)
            ->all();
    }

    /** Saved maps with the note each one is attached to. */
    public static function mapSaves($schedule)
    {
        $titles = self::noteTitles($schedule);

        return ScheduleMapSave::active()
            ->where('scheduleId', $schedule->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($m) => [
                'id' => (int) $m->id,
                'title' => (string) $m->title,
                'noteId' => $m->noteId ? (int) $m->noteId : null,
                'noteTitle' => $m->noteId ? ($titles[$m->noteId] ?? null) : null,
                'shapes' => count(json_decode((string) $m->objects, true) ?: []),
                'when' => $m->created_at?->timezone('Asia/Manila')->format('M j, Y'),
            ])
            ->values()
            ->all();
    }
}
