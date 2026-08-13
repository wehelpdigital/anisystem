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
     * @return list<array{kind:string, source:string, title:string, url:string,
     *     posterUrl:?string, href:?string, when:?string, sortKey:int,
     *     editable:bool}>
     */
    public static function all($schedule, bool $includeAlbums = true): array
    {
        $out = [];

        $push = function (?array $m, string $source, string $title, $when, ?string $href, bool $editable = false) use (&$out) {
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

        // Reference photos attached to activities.
        foreach (AsScheduleActivity::active()->where('croppingScheduleId', $schedule->id)->where('isDraft', 0)->orderByDesc('id')->get() as $a) {
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
                $push(['type' => 'image', 'path' => $img->path], 'Album',
                    (string) ($img->caption ?: 'In an album'), $img->updated_at,
                    route('sm.gallery', ['id' => $schedule->id]));
            }
        }

        usort($out, fn ($a, $b) => $b['sortKey'] <=> $a['sortKey']);

        return $out;
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
