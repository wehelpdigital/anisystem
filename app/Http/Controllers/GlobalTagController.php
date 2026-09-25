<?php

namespace App\Http\Controllers;

use App\Models\AsCroppingSchedule;
use App\Models\AsScheduleTag;
use App\Models\AsScheduleTagLink;
use App\Support\TagItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Global Tags: every word a member has tied to anything, in one place.
 *
 * Two kinds of tag meet here and stay what they are:
 *
 *  - a SEASON tag is a row in as_schedule_tags, one per season, tied to
 *    things through as_schedule_tag_links (the season Tags module);
 *  - an OUTSIDE tag is a plain word in the JSON `tags` list of a row that
 *    belongs to no season: contacts, Protocol Builder protocols, the four
 *    analyses' saved runs (as_plant_analyses), and global notes
 *    (as_schedule_notes with croppingScheduleId 0).
 *
 * A word is one tag across all of them when it reads the same, ignoring
 * case and extra spaces. Rename and delete act on every place it appears.
 *
 * Whose things: the signed-in member's OWN — the seasons they own
 * (anisystemUserId) and the rows filed under their userId. A worker
 * standing in a boss's farm still sees only their own words here: the
 * boss's seasons are the boss's vocabulary, and a rename that reached
 * into them would be a write no grant hands out. (Contacts, protocols and
 * analyses are already filed under the person who made them, whatever hat
 * they wore, so this matches the tools themselves.)
 */
class GlobalTagController extends Controller
{
    /** The same word, however it was typed. */
    public static function key($s): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $s)));
    }

    /** What the four analysis kinds are called, where they open, and their face. */
    private const ANALYSES = [
        'when' => ['label' => 'When to Plant', 'route' => 'wtp.page', 'icon' => '📅'],
        'what' => ['label' => 'What to Plant', 'route' => 'whatp.page', 'icon' => '🌱'],
        'variety' => ['label' => 'Variety Research', 'route' => 'vary.page', 'icon' => '🔬'],
        'protocol' => ['label' => 'Crop Protocol Analysis', 'route' => 'proto.page', 'icon' => '📘'],
    ];

    public function page()
    {
        return view('tags.index', [
            'inWorker' => \App\Support\WorkerContext::inWorkerContext(),
        ]);
    }

    /** The cloud: every word, how many things wear it in seasons and outside. */
    public function list()
    {
        $seasons = $this->seasons();
        $acc = [];
        $add = function (string $name, string $where, int $n, $seasonId = null, ?string $place = null) use (&$acc) {
            $k = self::key($name);
            if ($k === '') return;
            if (! isset($acc[$k])) {
                $acc[$k] = ['name' => $name, 'key' => $k, 'season' => 0, 'global' => 0, 'seasons' => [], 'places' => []];
            }
            $acc[$k][$where] += $n;
            if ($seasonId) $acc[$k]['seasons'][(int) $seasonId] = ($acc[$k]['seasons'][(int) $seasonId] ?? 0) + $n;
            if ($place) $acc[$k]['places'][$place] = ($acc[$k]['places'][$place] ?? 0) + $n;
        };

        foreach ($this->seasonTags(array_keys($seasons), true) as $t) {
            $add((string) $t->name, 'season', (int) $t->links_count, $t->croppingScheduleId);
        }
        foreach ($this->outsideRows(false) as $r) {
            foreach ($r['tags'] as $w) {
                $add($w, 'global', 1, null, $r['place']);
            }
        }

        $tags = array_values(array_map(fn ($t) => [
            'name' => $t['name'],
            'key' => $t['key'],
            'season' => $t['season'],
            'global' => $t['global'],
            'count' => $t['season'] + $t['global'],
            'seasons' => count($t['seasons']),
            // per-season and per-place counts, so the page can narrow the
            // cloud to one season or one tool without asking again
            'bySeason' => (object) $t['seasons'],
            'byPlace' => (object) $t['places'],
        ], $acc));
        usort($tags, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

        return response()->json(['success' => true, 'message' => '', 'data' => [
            'tags' => $tags,
            'seasons' => array_values(array_map(fn ($s) => ['id' => (int) $s->id, 'title' => (string) $s->title], $seasons)),
        ]]);
    }

    /** Everything one word is tied to, each saying where it lives. */
    public function items(Request $request)
    {
        $key = self::key($request->query('name'));
        if ($key === '') {
            return response()->json(['success' => false, 'message' => 'Which tag?'], 422);
        }
        $seasons = $this->seasons();
        $name = null;

        // The season side: this word's tag row in each season, then its ties.
        $tags = $this->seasonTags(array_keys($seasons), false)
            ->filter(fn ($t) => self::key($t->name) === $key);
        $seasonOf = [];
        foreach ($tags as $t) {
            $seasonOf[(int) $t->id] = (int) $t->croppingScheduleId;
            $name = $name ?? (string) $t->name;
        }
        $bySeason = [];
        if ($seasonOf) {
            foreach (AsScheduleTagLink::whereIn('tagId', array_keys($seasonOf))->get(['tagId', 'kind', 'refId']) as $l) {
                $bySeason[$seasonOf[(int) $l->tagId]][$l->kind][] = (int) $l->refId;
            }
        }

        $items = [];
        foreach ($bySeason as $sid => $kinds) {
            $schedule = $seasons[$sid] ?? null;
            if (! $schedule) continue;
            foreach ($kinds as $kind => $refIds) {
                if (! in_array($kind, AsScheduleTagLink::KINDS, true)) continue;
                foreach (TagItems::describe($schedule, $kind, array_values(array_unique($refIds))) as $row) {
                    $row['where'] = 'season';
                    $row['seasonId'] = (int) $sid;
                    $row['place'] = (string) $schedule->title;
                    $items[] = $row;
                }
            }
        }

        // The outside side: rows whose word list carries it.
        foreach ($this->outsideRows(true) as $r) {
            $hit = null;
            foreach ($r['tags'] as $w) {
                if (self::key($w) === $key) { $hit = $w; break; }
            }
            if ($hit === null) continue;
            $name = $name ?? $hit;
            $items[] = $this->describeOutside($r, $hit);
        }

        // Seasons first (grouped by season, newest first inside), then the
        // things outside any season, newest first.
        usort($items, function ($x, $y) {
            $gx = $x['where'] === 'season' ? 0 : 1;
            $gy = $y['where'] === 'season' ? 0 : 1;
            if ($gx !== $gy) return $gx <=> $gy;
            if ($gx === 0 && $x['seasonId'] !== $y['seasonId']) return strnatcasecmp($x['place'], $y['place']) ?: ($x['seasonId'] <=> $y['seasonId']);
            return strcmp($y['when'] ?? '', $x['when'] ?? '');
        });

        $inSeasons = array_values(array_filter($items, fn ($i) => $i['where'] === 'season'));

        return response()->json(['success' => true, 'message' => '', 'data' => [
            'tag' => ['name' => $name ?? (string) $request->query('name'), 'key' => $key],
            'items' => $items,
            'counts' => [
                'season' => count($inSeasons),
                'seasons' => count(array_unique(array_column($inSeasons, 'seasonId'))),
                'global' => count($items) - count($inSeasons),
                'seasonTags' => count($seasonOf),
            ],
        ]]);
    }

    /** One word becomes another, everywhere it is worn. */
    public function rename(Request $request)
    {
        $key = self::key($request->input('name'));
        $to = mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $request->input('to'))), 0, 30);
        if ($key === '') {
            return response()->json(['success' => false, 'message' => 'Which tag?'], 422);
        }
        if ($to === '') {
            return response()->json(['success' => false, 'message' => 'Give the tag its new name.'], 422);
        }
        $toKey = self::key($to);

        $seasonIds = array_keys($this->seasons());
        $changed = ['seasonTags' => 0, 'global' => 0];

        DB::transaction(function () use ($key, $to, $toKey, $seasonIds, &$changed) {
            if ($seasonIds) {
                // Every tag row in these seasons that wears either name, dead
                // or alive — a retired row with the new name is revived rather
                // than doubled.
                $rows = AsScheduleTag::whereIn('croppingScheduleId', $seasonIds)
                    ->whereRaw('LOWER(name) IN (?, ?)', [$key, $toKey])
                    ->orderBy('id')
                    ->get(['id', 'croppingScheduleId', 'name', 'deleteStatus'])
                    ->groupBy('croppingScheduleId');

                foreach ($rows as $sid => $group) {
                    $sources = $group->filter(fn ($t) => (int) $t->deleteStatus === 1 && self::key($t->name) === $key)->values();
                    if ($sources->isEmpty()) continue;
                    if ($toKey === $key) {
                        // Only the spelling changes: one row keeps it, twins fold in.
                        $keep = $sources->first();
                    } else {
                        $keep = $group->first(fn ($t) => self::key($t->name) === $toKey && (int) $t->deleteStatus === 1)
                            ?? $group->first(fn ($t) => self::key($t->name) === $toKey)
                            ?? $sources->first();
                    }
                    AsScheduleTag::where('id', $keep->id)->update(['name' => $to, 'deleteStatus' => 1, 'updated_at' => now()]);
                    foreach ($sources as $s) {
                        if ((int) $s->id === (int) $keep->id) continue;
                        $this->foldInto((int) $s->id, (int) $keep->id);
                    }
                    $changed['seasonTags'] += $sources->count();
                }
            }

            $changed['global'] = $this->rewriteOutside($key, fn (array $tags) => array_map(
                fn ($w) => self::key($w) === $key ? $to : $w, $tags
            ));
        });

        return response()->json(['success' => true, 'message' => 'Tag renamed.', 'data' => [
            'name' => $to, 'key' => $toKey, 'changed' => $changed,
        ]]);
    }

    /** A word comes off everything. The things themselves stay. */
    public function destroy(Request $request)
    {
        $key = self::key($request->input('name'));
        if ($key === '') {
            return response()->json(['success' => false, 'message' => 'Which tag?'], 422);
        }
        $seasonIds = array_keys($this->seasons());
        $changed = ['seasonTags' => 0, 'links' => 0, 'global' => 0];

        DB::transaction(function () use ($key, $seasonIds, &$changed) {
            if ($seasonIds) {
                $ids = AsScheduleTag::whereIn('croppingScheduleId', $seasonIds)
                    ->where('deleteStatus', 1)
                    ->whereRaw('LOWER(name) = ?', [$key])
                    ->pluck('id')->all();
                if ($ids) {
                    // Same retirement the season module does: the row sleeps,
                    // its ties go.
                    AsScheduleTag::whereIn('id', $ids)->update(['deleteStatus' => 0, 'updated_at' => now()]);
                    $changed['links'] = AsScheduleTagLink::whereIn('tagId', $ids)->delete();
                    $changed['seasonTags'] = count($ids);
                }
            }
            $changed['global'] = $this->rewriteOutside($key, fn (array $tags) => array_values(array_filter(
                $tags, fn ($w) => self::key($w) !== $key
            )));
        });

        return response()->json(['success' => true, 'message' => 'Tag removed.', 'data' => ['changed' => $changed]]);
    }

    // ---------------------------------------------------------------------

    /** The member's own living seasons (closed and archived ones too), by id. */
    private function seasons(): array
    {
        return AsCroppingSchedule::active()
            ->where('anisystemUserId', (int) Auth::id())
            ->orderBy('title')
            ->get(['id', 'title', 'status'])
            ->keyBy('id')
            ->all();
    }

    private function seasonTags(array $seasonIds, bool $withCounts)
    {
        if (! $seasonIds) return collect();
        $q = AsScheduleTag::whereIn('croppingScheduleId', $seasonIds)->where('deleteStatus', 1);
        if ($withCounts) $q->withCount('links');

        return $q->orderBy('id')->get(['id', 'croppingScheduleId', 'name']);
    }

    private static function words($json): array
    {
        $list = is_array($json) ? $json : (json_decode((string) $json, true) ?: []);

        return array_values(array_filter((array) $list, fn ($w) => is_string($w) && trim($w) !== ''));
    }

    /**
     * The member's rows outside any season that wear at least one word:
     * [{place, table, id, tags, ...the fields describeOutside reads}].
     */
    private function outsideRows(bool $full): array
    {
        $uid = (int) Auth::id();
        $out = [];
        $worn = fn ($q) => $q->whereNotNull('tags')->where('tags', '!=', '')->where('tags', '!=', '[]');

        $cols = $full ? ['id', 'name', 'company', 'phone', 'tags', 'updated_at'] : ['id', 'tags'];
        foreach ($worn(DB::table('as_contacts')->where('userId', $uid)->where('deleteStatus', 1))->get($cols) as $r) {
            $out[] = ['place' => 'Contact list', 'kind' => 'contact', 'table' => 'as_contacts', 'row' => $r, 'tags' => self::words($r->tags)];
        }
        $cols = $full ? ['id', 'title', 'crop', 'variety', 'dayType', 'tags', 'updated_at'] : ['id', 'tags'];
        foreach ($worn(DB::table('as_protocols')->where('userId', $uid)->where('deleteStatus', 1))->get($cols) as $r) {
            $out[] = ['place' => 'Protocol Builder', 'kind' => 'protocol', 'table' => 'as_protocols', 'row' => $r, 'tags' => self::words($r->tags)];
        }
        $cols = $full ? ['id', 'kind', 'title', 'tags', 'created_at'] : ['id', 'kind', 'tags'];
        foreach ($worn(DB::table('as_plant_analyses')->where('userId', $uid)->where('deleteStatus', 1)->where('status', 'ready'))->get($cols) as $r) {
            $a = self::ANALYSES[$r->kind] ?? null;
            if (! $a) continue;
            $out[] = ['place' => $a['label'], 'kind' => 'analysis', 'table' => 'as_plant_analyses', 'row' => $r, 'tags' => self::words($r->tags)];
        }
        $cols = $full ? ['id', 'title', 'body', 'media', 'tags', 'updated_at'] : ['id', 'tags'];
        foreach ($worn(DB::table('as_schedule_notes')->where('userId', $uid)->where('deleteStatus', 1)
            ->where('croppingScheduleId', NotesHubController::GLOBAL_SCHEDULE_ID))->get($cols) as $r) {
            $out[] = ['place' => 'Global notes', 'kind' => 'gnote', 'table' => 'as_schedule_notes', 'row' => $r, 'tags' => self::words($r->tags)];
        }

        return array_values(array_filter($out, fn ($r) => ! empty($r['tags'])));
    }

    private function describeOutside(array $r, string $word): array
    {
        $row = $r['row'];
        $day = fn ($d) => $d ? \Carbon\Carbon::parse((string) $d)->format('M j, Y') : '';
        $base = ['where' => 'global', 'place' => $r['place'], 'kind' => $r['kind'], 'refId' => (int) $row->id];

        switch ($r['kind']) {
            case 'contact':
                return $base + ['icon' => '📇',
                    'title' => trim((string) $row->name) ?: 'Contact',
                    'sub' => trim(implode(' · ', array_filter([mb_substr(trim((string) $row->company), 0, 60), (string) $row->phone]))) ?: 'contact',
                    'when' => $row->updated_at ? substr((string) $row->updated_at, 0, 10) : null,
                    'url' => route('contacts.page', ['tag' => $word])];
            case 'protocol':
                return $base + ['icon' => '📋',
                    'title' => trim((string) $row->title) ?: 'Protocol',
                    'sub' => trim(implode(' · ', array_filter(['protocol', (string) $row->crop, (string) $row->variety, (string) $row->dayType]))),
                    'when' => $row->updated_at ? substr((string) $row->updated_at, 0, 10) : null,
                    'url' => route('pb.open', ['id' => $row->id])];
            case 'analysis':
                $a = self::ANALYSES[$row->kind];

                return array_merge($base, ['icon' => $a['icon'],
                    'title' => trim((string) $row->title) ?: $a['label'],
                    'sub' => 'saved run · ' . $day($row->created_at),
                    'when' => $row->created_at ? substr((string) $row->created_at, 0, 10) : null,
                    'url' => route($a['route'])]);
            default: // gnote
                $media = collect(is_array($row->media) ? $row->media : (json_decode((string) $row->media, true) ?: []));
                $voice = $media->contains(fn ($m) => is_array($m) && ($m['type'] ?? '') === 'audio');

                return $base + ['icon' => $voice ? '🎙️' : '📓',
                    'title' => trim((string) $row->title) ?: (mb_substr(trim(strip_tags((string) $row->body)), 0, 90) ?: ($voice ? 'Voice note' : 'Note')),
                    'sub' => ($voice ? 'voice note · ' : 'note · ') . $day($row->updated_at),
                    'when' => $row->updated_at ? substr((string) $row->updated_at, 0, 10) : null,
                    'url' => route('notes.hub')];
        }
    }

    /**
     * Rewrite the word list of every outside row that wears $key, through
     * $fn, tidied the way every tool tidies it. Returns how many rows moved.
     * updated_at is left alone on purpose: a housekeeping rename should not
     * shuffle every list that sorts by "last touched".
     */
    private function rewriteOutside(string $key, callable $fn): int
    {
        $n = 0;
        foreach ($this->outsideRows(false) as $r) {
            if (! collect($r['tags'])->contains(fn ($w) => self::key($w) === $key)) continue;
            $next = UserTagController::tidy($fn($r['tags']));
            DB::table($r['table'])->where('id', (int) $r['row']->id)->where('userId', (int) Auth::id())
                ->update(['tags' => json_encode($next)]);
            $n++;
        }

        return $n;
    }

    /** Move every tie from one season tag to another, then retire the first. */
    private function foldInto(int $fromId, int $toId): void
    {
        DB::statement(
            'INSERT IGNORE INTO as_schedule_tag_links (tagId, kind, refId, created_at, updated_at)
             SELECT ?, kind, refId, NOW(), NOW() FROM as_schedule_tag_links WHERE tagId = ?',
            [$toId, $fromId]
        );
        AsScheduleTagLink::where('tagId', $fromId)->delete();
        AsScheduleTag::where('id', $fromId)->update(['deleteStatus' => 0, 'updated_at' => now()]);
    }
}
