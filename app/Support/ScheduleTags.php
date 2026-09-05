<?php

namespace App\Support;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleTag;
use App\Models\AsScheduleTagLink;

/**
 * The one place tags are tied and untied.
 *
 * Every save endpoint that carries the tag picker calls sync() with
 * whatever the form sent; the picker itself creates new tags the moment
 * the user types them (through TagController::store), so by the time a
 * form is submitted its tags are always ids.
 */
class ScheduleTags
{
    /** Tie a thing to exactly this set of the schedule's tags. */
    public static function sync(AsCroppingSchedule $schedule, string $kind, int $refId, $tagIds): void
    {
        if (! in_array($kind, AsScheduleTagLink::KINDS, true) || $refId <= 0) {
            return;
        }
        $ids = collect(is_array($tagIds) ? $tagIds : [])
            ->map(fn ($v) => (int) $v)->filter()->unique();

        // Only the schedule's own living tags count — a stray id from
        // another season quietly falls out here.
        $ids = $ids->isEmpty() ? $ids : AsScheduleTag::forSchedule($schedule->id)
            ->where('deleteStatus', 1)->whereIn('id', $ids)->pluck('id');

        $have = AsScheduleTagLink::where('kind', $kind)->where('refId', $refId)
            ->whereIn('tagId', AsScheduleTag::forSchedule($schedule->id)->select('id'))
            ->pluck('tagId');

        $drop = $have->diff($ids);
        if ($drop->isNotEmpty()) {
            AsScheduleTagLink::where('kind', $kind)->where('refId', $refId)
                ->whereIn('tagId', $drop)->delete();
        }
        foreach ($ids->diff($have) as $tagId) {
            AsScheduleTagLink::firstOrCreate(['tagId' => $tagId, 'kind' => $kind, 'refId' => $refId]);
        }
    }

    /** One thing's tags, for painting an edit form. */
    public static function for(int $scheduleId, string $kind, int $refId): array
    {
        return AsScheduleTagLink::where('kind', $kind)->where('refId', $refId)
            ->whereIn('tagId', AsScheduleTag::forSchedule($scheduleId)->where('deleteStatus', 1)->select('id'))
            ->pluck('tagId')->map(fn ($v) => (int) $v)->all();
    }

    /**
     * Tags for many things at once: [refId => [{id, name}, ...]].
     * What the board and the mirror read to paint chips and to filter.
     */
    public static function forMany(int $scheduleId, string $kind, array $refIds): array
    {
        if (! $refIds) {
            return [];
        }
        $rows = AsScheduleTagLink::where('kind', $kind)->whereIn('refId', $refIds)
            ->join('as_schedule_tags', 'as_schedule_tags.id', '=', 'as_schedule_tag_links.tagId')
            ->where('as_schedule_tags.croppingScheduleId', $scheduleId)
            ->where('as_schedule_tags.deleteStatus', 1)
            ->get(['as_schedule_tag_links.refId', 'as_schedule_tags.id', 'as_schedule_tags.name']);

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->refId][] = ['id' => (int) $r->id, 'name' => $r->name];
        }

        return $out;
    }
}
