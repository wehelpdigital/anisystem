<?php

namespace App\Http\Controllers\Manager;

use App\Models\AsInlineNote;
use App\Models\AsScheduleActivity;
use App\Models\AsScheduleNote;
use App\Models\ScheduleAiMessage;
use App\Support\MediaStore;
use Illuminate\Http\Request;

/**
 * Media Box: every picture and every video this schedule has anywhere.
 *
 * A season's pictures end up scattered by whatever they were about — a note,
 * a day's note, a drawing, a saved map, an activity's reference photo, a
 * frame sent to the AI technician. Each of those is the right home for one
 * picture and the wrong place to look for all of them. This is the one place
 * that answers "show me everything we have", with the way back to wherever
 * each one lives.
 *
 * Nothing is copied or re-uploaded: this reads the same records the modules
 * write, so a photo deleted in Notes is gone from here the moment it is gone
 * there.
 */
class MediaBoxController extends BaseScheduleController
{
    public function page(Request $request)
    {
        // Same ?id= the other module pages take.
        $schedule = $this->scheduleFromRequest($request, 'id');

        return view('sm.media', [
            'schedule' => $schedule,
            'items' => $this->gather($schedule),
        ]);
    }

    /**
     * @return array<int, array{kind: string, url: string, posterUrl: ?string,
     *     source: string, title: string, when: ?string, href: ?string}>
     */
    private function gather($schedule): array
    {
        $out = [];
        $push = function (array $m, string $source, string $title, $when, ?string $href) use (&$out, $schedule) {
            $path = $m['path'] ?? null;
            if (! filled($path)) {
                return;
            }
            $type = $m['type'] ?? 'image';
            // A saved map is a place, not a picture of one — it belongs to the
            // Maps module and is no use in a gallery.
            if ($type === 'map' || preg_match('~/map-[A-Za-z0-9]+\.png$~', (string) $path)) {
                return;
            }
            $out[] = [
                'kind' => $type === 'video' ? 'video' : 'image',
                'isDrawing' => $type === 'drawing',
                'url' => MediaStore::url($path),
                'posterUrl' => ! empty($m['poster']) ? MediaStore::url($m['poster']) : null,
                'source' => $source,
                'title' => $title,
                'when' => $when?->timezone('Asia/Manila')->format('M j, Y'),
                'href' => $href,
            ];
        };

        // The notebook, including drawings (which are notes carrying a picture).
        foreach (AsScheduleNote::active()->where('croppingScheduleId', $schedule->id)->orderByDesc('id')->get() as $n) {
            $href = route('sm.notes', ['id' => $schedule->id]);
            if (filled($n->imagePath)) {
                $push(['type' => 'image', 'path' => $n->imagePath], 'Note', (string) $n->title, $n->updated_at, $href);
            }
            foreach ((array) ($n->media ?? []) as $i => $m) {
                $isDrawing = ($m['type'] ?? '') === 'drawing';
                $push(
                    $m,
                    $isDrawing ? 'Drawing' : 'Note',
                    (string) $n->title,
                    $n->updated_at,
                    $isDrawing
                        ? route('sm.draw', ['id' => $schedule->id, 'open' => $n->id . ':' . $i])
                        : $href
                );
            }
        }

        // Notes pinned to a day on the board.
        foreach (AsInlineNote::active()->where('croppingScheduleId', $schedule->id)->orderByDesc('id')->get() as $n) {
            $when = $n->noteDate?->format('M j, Y');
            foreach ((array) ($n->media ?? []) as $m) {
                $push($m, 'Day note', $when ? 'Note for ' . $when : 'Day note', $n->updated_at,
                    route('sm.activities', ['id' => $schedule->id]));
            }
        }

        // Reference photos attached to activities.
        foreach (AsScheduleActivity::active()->where('croppingScheduleId', $schedule->id)->where('isDraft', 0)->orderByDesc('id')->get() as $a) {
            foreach ($a->imageList() as $img) {
                $push(
                    ['type' => 'image', 'path' => $img['path'] ?? null],
                    'Activity',
                    (string) $a->activityTitle,
                    $a->updated_at,
                    route('sm.activities', ['id' => $schedule->id])
                );
            }
        }

        // Frames sent to the AI technician — a photo of a leaf someone asked
        // about is still a photo of this season.
        foreach (ScheduleAiMessage::active()->where('scheduleId', $schedule->id)
            ->whereNotNull('imagePath')->orderByDesc('id')->get() as $m) {
            $push(
                ['type' => 'image', 'path' => $m->imagePath],
                'Asked the AI',
                mb_strimwidth(trim(strip_tags((string) $m->content)) ?: 'Sent to the AI technician', 0, 80, '…'),
                $m->updated_at,
                route('sm.ai', ['id' => $schedule->id])
            );
        }

        return $out;
    }
}
