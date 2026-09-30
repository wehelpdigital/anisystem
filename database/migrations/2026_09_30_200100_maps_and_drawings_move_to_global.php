<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves the existing maps and Draw-module drawings to their owners' global
 * shelves (see 2026_09_30_200000 for the model). Data only, so it can be
 * rehearsed inside a transaction and rolled back.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $link = function (int $saveId, int $scheduleId) use ($now) {
            if ($saveId > 0 && $scheduleId > 0) {
                DB::table('as_schedule_map_links')->insertOrIgnore([
                    'saveId' => $saveId, 'scheduleId' => $scheduleId, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        };
        $owners = DB::table('as_cropping_schedules')->pluck('anisystemUserId', 'id');

        // 1. A season's live canvas with shapes nobody saved becomes a saved
        //    map of the season owner's (unless one of the season's saves is
        //    already exactly that).
        $canvases = DB::table('as_schedule_map_objects')->where('deleteStatus', 1)->where('scheduleId', '>', 0)
            ->orderBy('id')->get()->groupBy('scheduleId');
        foreach ($canvases as $sid => $rows) {
            $owner = (int) ($owners[$sid] ?? 0);
            if ($owner <= 0) {
                continue;
            }
            $shapes = $rows->map(fn ($o) => [
                'kind' => $o->kind, 'color' => $o->color, 'width' => (int) $o->width, 'font' => $o->font,
                'points' => json_decode((string) $o->points, true), 'label' => $o->label,
            ])->filter(fn ($o) => is_array($o['points']) && $o['points'])->values()->all();
            if (! $shapes) {
                continue;
            }
            $json = json_encode($shapes);
            $same = DB::table('as_schedule_map_saves')->where('deleteStatus', 1)
                ->where(fn ($q) => $q->where('scheduleId', $sid)->orWhere('originScheduleId', $sid))
                ->where('objects', $json)->exists();
            if ($same) {
                continue;
            }
            $title = trim((string) DB::table('as_cropping_schedules')->where('id', $sid)->value('title'));
            DB::table('as_schedule_map_saves')->insert([
                'scheduleId' => $sid,          // moved to 0 by step 2 like every other save
                'userId' => $owner,
                'title' => mb_substr(($title !== '' ? $title . ' — ' : '') . 'team map', 0, 180),
                'source' => 'team',
                'objects' => $json,
                'noteId' => null,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 2. Every season's saved maps go global; their picture notes follow.
        foreach (DB::table('as_schedule_map_saves')->where('scheduleId', '>', 0)->orderBy('id')->get() as $save) {
            $sid = (int) $save->scheduleId;
            DB::table('as_schedule_map_saves')->where('id', $save->id)->update([
                'originScheduleId' => $sid,
                'scheduleId' => 0,
            ]);
            // A deleted map keeps its old note where it was: only living
            // maps are linked, and only their notes move.
            if ((int) $save->deleteStatus !== 1) {
                continue;
            }
            $link((int) $save->id, $sid);
            if ($save->noteId) {
                DB::table('as_schedule_notes')->where('id', $save->noteId)->where('croppingScheduleId', $sid)
                    ->update(['croppingScheduleId' => 0, 'userId' => (int) $save->userId]);
            }
        }

        // 3. Seasons that already use a map through a lot, a tag or an
        //    activity's attachment are linked to it too.
        foreach (DB::table('as_schedule_lots')->whereNotNull('mapSaveId')->where('deleteStatus', 1)->get(['croppingScheduleId', 'mapSaveId']) as $lot) {
            $link((int) $lot->mapSaveId, (int) $lot->croppingScheduleId);
        }
        if (Schema::hasTable('as_schedule_tag_links')) {
            foreach (DB::table('as_schedule_tag_links as l')->join('as_schedule_tags as t', 't.id', '=', 'l.tagId')
                ->where('l.kind', 'map')->get(['l.refId', 't.croppingScheduleId']) as $row) {
                $link((int) $row->refId, (int) $row->croppingScheduleId);
            }
        }
        foreach (DB::table('as_schedule_activities')->where('tags', 'like', '%"map"%')->get(['id', 'croppingScheduleId', 'tags']) as $act) {
            $tags = json_decode((string) $act->tags, true);
            if (! is_array($tags)) {
                continue;
            }
            $changed = false;
            foreach ($tags as &$t) {
                if (($t['kind'] ?? '') !== 'map' || ! ctype_digit((string) ($t['ref'] ?? ''))) {
                    continue;
                }
                $link((int) $t['ref'], (int) $act->croppingScheduleId);
                // The attachment's link pointed at the season's Maps module,
                // which is gone; it opens the map where it lives now.
                $t['url'] = '/app/maps?save=' . (int) $t['ref'];
                $changed = true;
            }
            unset($t);
            if ($changed) {
                DB::table('as_schedule_activities')->where('id', $act->id)->update(['tags' => json_encode($tags, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
            }
        }

        // 4. Draw-module drawings: a notebook note holding drawings and
        //    nothing else (no words, no other media) moves to its owner's
        //    global notes.
        foreach (DB::table('as_schedule_notes')->where('deleteStatus', 1)->where('croppingScheduleId', '>', 0)
            ->where('media', 'like', '%"drawing"%')->get(['id', 'croppingScheduleId', 'userId', 'body', 'imagePath', 'media']) as $note) {
            $media = json_decode((string) $note->media, true);
            if (! is_array($media) || ! $media) {
                continue;
            }
            $onlyDrawings = collect($media)->every(fn ($m) => ($m['type'] ?? '') === 'drawing');
            $words = trim(strip_tags((string) $note->body));
            if (! $onlyDrawings || $words !== '' || filled($note->imagePath)) {
                continue;
            }
            $owner = (int) ($note->userId ?: ($owners[$note->croppingScheduleId] ?? 0));
            if ($owner > 0) {
                DB::table('as_schedule_notes')->where('id', $note->id)->update(['croppingScheduleId' => 0, 'userId' => $owner]);
            }
        }
    }

    public function down(): void
    {
        // Put saves back on the season they came from; links and the moved
        // notes follow the same record.
        foreach (DB::table('as_schedule_map_saves')->where('scheduleId', 0)->whereNotNull('originScheduleId')->get() as $save) {
            DB::table('as_schedule_map_saves')->where('id', $save->id)->update(['scheduleId' => $save->originScheduleId]);
            if ($save->noteId) {
                DB::table('as_schedule_notes')->where('id', $save->noteId)->where('croppingScheduleId', 0)
                    ->update(['croppingScheduleId' => $save->originScheduleId]);
            }
        }
        DB::table('as_schedule_map_links')->delete();
    }
};
