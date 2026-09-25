<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * A member's own tag vocabulary, gathered across everything that is
 * theirs: contacts, the four analysis shelves, the protocols they wrote,
 * global notes — and the tag words of the seasons they own, so one
 * vocabulary serves every picker. Both pickers read it (partials/user-tags
 * for the things outside a season, sm/partials/tag-picker for the
 * "from your other seasons and tools" words).
 */
class UserTagController extends Controller
{
    /** The words a row's tags column may hold, trimmed, bounded, deduped. */
    public static function tidy($tags): array
    {
        $out = [];
        $seen = [];
        foreach (array_slice((array) $tags, 0, 10) as $t) {
            $w = trim(preg_replace('/\s+/', ' ', (string) $t));
            if ($w === '') continue;
            $w = mb_substr($w, 0, 30);
            $k = mb_strtolower($w);
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $out[] = $w;
        }

        return $out;
    }

    /**
     * [{name, count, seasons, things}] — `seasons` is how many season things
     * wear the word (across the member's own seasons), `things` how many rows
     * outside a season do. One word however it was typed: the first spelling
     * met wins. Season words longer than a JSON tag may be (30) are left out,
     * since picking one here would save it cut short.
     */
    public function index()
    {
        $uid = (int) Auth::id();
        $acc = [];
        $add = function ($name, string $where, int $n) use (&$acc) {
            if (! is_string($name)) return;
            $name = trim(preg_replace('/\s+/u', ' ', $name));
            if ($name === '') return;
            $k = mb_strtolower($name);
            if (! isset($acc[$k])) $acc[$k] = ['name' => $name, 'seasons' => 0, 'things' => 0];
            $acc[$k][$where] += $n;
        };
        $tally = function ($json) use ($add) {
            $list = is_array($json) ? $json : (json_decode((string) $json, true) ?: []);
            foreach ((array) $list as $t) $add($t, 'things', 1);
        };
        foreach (DB::table('as_contacts')->where('userId', $uid)->where('deleteStatus', 1)->pluck('tags') as $j) $tally($j);
        foreach (DB::table('as_protocols')->where('userId', $uid)->where('deleteStatus', 1)->pluck('tags') as $j) $tally($j);
        foreach (DB::table('as_plant_analyses')->where('userId', $uid)->where('deleteStatus', 1)->pluck('tags') as $j) $tally($j);
        foreach (DB::table('as_schedule_notes')->where('userId', $uid)->where('deleteStatus', 1)
            ->where('croppingScheduleId', NotesHubController::GLOBAL_SCHEDULE_ID)->whereNotNull('tags')->pluck('tags') as $j) $tally($j);

        // The words of the seasons this member owns (one query: tags joined
        // to their seasons, each with how many things it ties).
        $seasonTags = DB::table('as_schedule_tags as t')
            ->join('as_cropping_schedules as s', 's.id', '=', 't.croppingScheduleId')
            ->where('s.anisystemUserId', $uid)->where('s.deleteStatus', 1)->where('t.deleteStatus', 1)
            ->selectRaw('t.name, (SELECT COUNT(*) FROM as_schedule_tag_links l WHERE l.tagId = t.id) AS n')
            ->get();
        foreach ($seasonTags as $t) {
            if (mb_strlen(trim((string) $t->name)) > 30) continue;
            $add((string) $t->name, 'seasons', (int) $t->n);
        }

        $tags = array_values(array_map(fn ($t) => [
            'name' => $t['name'],
            'count' => $t['seasons'] + $t['things'],
            'seasons' => $t['seasons'],
            'things' => $t['things'],
        ], $acc));
        usort($tags, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return response()->json(['success' => true, 'message' => '', 'data' => ['tags' => $tags]]);
    }
}
