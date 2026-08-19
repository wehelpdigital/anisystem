<?php

namespace App\Support;

use App\Models\AsScheduleNote;
use App\Models\ScheduleMapSave;
use App\Models\TeamRecording;

/**
 * The Team box: what the Collab Room made, rather than what the season
 * produced.
 *
 * Three things people ask for with the same question — "where is that thing we
 * did together?" — and which were otherwise scattered: recordings had nowhere
 * to go, whiteboard drawings looked like any other drawing, and saved maps sat
 * in Maps. Newest first, because a team's most recent work is what a team is
 * usually looking for.
 *
 * It lived inside the Gallery module's controller until the Global Gallery
 * needed the same shelf across every season. One reading of "what the team
 * made" is the point: two would drift.
 */
class SeasonTeamBox
{
    /**
     * @return list<array<string,mixed>>
     */
    public static function for($schedule): array
    {
        $rows = [];

        foreach (TeamRecording::active()->where('scheduleId', $schedule->id)
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

        // The whiteboard saves itself as an ordinary schedule note; what marks
        // one is where its pictures were written. Reading the marker beats
        // keeping a second table that could disagree with the first.
        foreach (AsScheduleNote::active()->where('croppingScheduleId', $schedule->id)
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

        foreach (ScheduleMapSave::active()->where('scheduleId', $schedule->id)
            ->orderByDesc('id')->get() as $m) {
            $picture = null;
            if ($m->noteId) {
                $note = AsScheduleNote::active()->find($m->noteId);
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
}
