<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The admin panel's Activity page (2026-11-26, the owner: "a general report for it in the admin panel for us to know
 * what are they trying to do"). Members only, our own team left out unless asked for.
 *
 * Read from three places: the deeds (as_user_actions, App\Support\UserActions), the pages (as_page_views), and the
 * records themselves (seasons, lots, tasks, Anee's ledger, payments) for the first steps of new accounts, which go
 * back further than the counting does. Small numbers, so the people behind each count are named: the point is to know
 * who to talk to. Kept for five minutes per period.
 */
final class ActivityReport
{
    public const PERIODS = [7, 30, 90];

    /** A visit ends after this long without a page or a deed. */
    private const GAP = 1800;

    public static function build(int $days, bool $withTeam, bool $fresh = false): array
    {
        $days = in_array($days, self::PERIODS, true) ? $days : 30;
        $key = 'activity.report.' . $days . ($withTeam ? '.team' : '');
        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, 300, fn () => self::compute($days, $withTeam));
    }

    private static function compute(int $days, bool $withTeam): array
    {
        $since = now()->subDays($days - 1)->startOfDay();
        $out = $withTeam ? [] : TeamAccounts::ids();
        $ready = self::ready();

        $acts = $ready ? DB::table(UserActions::TABLE)->where('createdAt', '>=', $since)
            ->when($out, fn ($q) => $q->whereNotIn('userId', $out))
            ->orderBy('createdAt')->limit(60000)
            ->get(['userId', 'kind', 'name', 'label', 'area', 'path', 'detail', 'createdAt']) : collect();
        $views = DB::table(PageViews::TABLE)->where('viewedAt', '>=', $since)->whereNotNull('userId')
            ->when($out, fn ($q) => $q->whereNotIn('userId', $out))
            ->orderBy('viewedAt')->limit(100000)
            ->get(['userId', 'area', 'topic', 'title', 'path', 'viewedAt']);

        // Everything one person did, in order: their visits and the last thing before they left.
        $items = [];
        foreach ($views as $v) {
            $items[(int) $v->userId][] = [strtotime($v->viewedAt), 'view', $v->area === 'app' ? PageViews::toolLabel($v->topic) : ($v->title ?: $v->path)];
        }
        foreach ($acts as $a) {
            $items[(int) $a->userId][] = [strtotime($a->createdAt), $a->kind, $a->label];
        }
        $visits = 0;
        $lengths = [];
        $lastThing = [];
        foreach ($items as $uid => &$list) {
            usort($list, fn ($x, $y) => $x[0] <=> $y[0]);
            $start = $prev = null;
            foreach ($list as $it) {
                if ($prev === null || $it[0] - $prev > self::GAP) {
                    if ($start !== null) {
                        $lengths[] = $prev - $start;
                    }
                    $visits++;
                    $start = $it[0];
                }
                $prev = $it[0];
            }
            $lengths[] = $prev - $start;
            $lastThing[$uid] = end($list);
        }
        unset($list);
        sort($lengths);
        $median = $lengths ? $lengths[intdiv(count($lengths), 2)] : 0;

        $ids = array_keys($items);
        $newIds = DB::table('anisystem_users')->where('created_at', '>=', $since)->where('deleteStatus', 1)
            ->when($out, fn ($q) => $q->whereNotIn('id', $out))->pluck('id')->map(fn ($v) => (int) $v)->all();
        $names = self::names(array_merge($ids, $newIds));
        $who = fn (array $uids) => array_values(array_map(fn ($id) => ['id' => $id, 'name' => $names[$id] ?? ('Account #' . $id)], array_slice(array_values(array_unique($uids)), 0, 40)));

        $byKind = $acts->groupBy('kind');
        $people = fn ($rows) => collect($rows)->pluck('userId')->unique()->count();

        // What they tried: every deed, the ones that went through and the ones that did not, by how many people.
        $tried = $acts->whereIn('kind', ['did', 'refused', 'failed'])->where('name', '!=', 'js')->groupBy('label')->map(function ($rows, $label) use ($who) {
            $bad = $rows->whereIn('kind', ['refused', 'failed']);

            return [
                'label' => $label, 'area' => $rows->first()->area, 'people' => $rows->pluck('userId')->unique()->count(),
                'times' => $rows->count(), 'no' => $bad->count(), 'who' => $who($rows->pluck('userId')->all()),
            ];
        })->sortByDesc(fn ($x) => [$x['people'], $x['times']])->values()->take(30)->all();

        // Windows opened, and how often something was saved within fifteen minutes after.
        $didAt = [];
        foreach ($byKind->get('did', collect()) as $a) {
            $didAt[(int) $a->userId][] = strtotime($a->createdAt);
        }
        $opened = $byKind->get('opened', collect())->groupBy('label')->map(function ($rows, $label) use ($didAt, $who) {
            $followed = 0;
            foreach ($rows as $r) {
                $t = strtotime($r->createdAt);
                foreach ($didAt[(int) $r->userId] ?? [] as $d) {
                    if ($d >= $t && $d - $t <= 900) {
                        $followed++;
                        break;
                    }
                }
            }

            return ['label' => $label, 'area' => $rows->first()->area, 'people' => $rows->pluck('userId')->unique()->count(), 'times' => $rows->count(),
                'followed' => $followed, 'who' => $who($rows->pluck('userId')->all())];
        })->sortByDesc(fn ($x) => [$x['people'], $x['times']])->values()->take(20)->all();

        $locked = $byKind->get('locked', collect())->groupBy('label')->map(fn ($rows, $label) => [
            'label' => $label, 'area' => $rows->first()->area, 'people' => $rows->pluck('userId')->unique()->count(),
            'times' => $rows->count(), 'who' => $who($rows->pluck('userId')->all()),
        ])->sortByDesc(fn ($x) => [$x['people'], $x['times']])->values()->take(15)->all();

        $stuck = $acts->whereIn('kind', ['refused', 'failed'])->groupBy(fn ($a) => $a->label . '|' . $a->detail)->map(fn ($rows) => [
            'label' => $rows->first()->label, 'detail' => $rows->first()->detail, 'kind' => $rows->first()->kind,
            'area' => $rows->first()->area, 'people' => $rows->pluck('userId')->unique()->count(), 'times' => $rows->count(),
            'last' => Carbon::parse($rows->last()->createdAt)->diffForHumans(), 'who' => $who($rows->pluck('userId')->all()),
        ])->sortByDesc(fn ($x) => [$x['people'], $x['times']])->values()->take(20)->all();

        $areas = $byKind->get('did', collect())->groupBy('area')->map(fn ($rows, $area) => [
            'label' => $area ?: 'Other', 'people' => $rows->pluck('userId')->unique()->count(), 'times' => $rows->count(),
        ])->sortByDesc('people')->values()->all();
        $maxArea = max(1, $ids ? count($ids) : 1);
        foreach ($areas as &$x) {
            $x['share'] = round($x['people'] / $maxArea * 100);
        }
        unset($x);

        $screens = $views->where('area', 'app')->groupBy(fn ($v) => PageViews::toolLabel($v->topic))->map(fn ($rows, $label) => [
            'label' => $label, 'people' => $rows->pluck('userId')->unique()->count(), 'views' => $rows->count(),
        ])->sortByDesc(fn ($x) => [$x['people'], $x['views']])->values()->take(14)->all();
        $appPeople = max(1, $views->where('area', 'app')->pluck('userId')->unique()->count());
        foreach ($screens as &$x) {
            $x['share'] = round($x['people'] / $appPeople * 100);
        }
        unset($x);

        // The last thing each person saw or did before they went, by how many people.
        $left = [];
        foreach ($lastThing as $uid => $it) {
            $left[$it[2]][] = $uid;
        }
        $leftList = collect($left)->map(fn ($uids, $label) => ['label' => $label, 'people' => count($uids), 'who' => $who($uids)])
            ->sortByDesc('people')->values()->take(12)->all();

        // The newest lines, everyone together.
        $feed = collect();
        foreach ($acts->sortByDesc('createdAt')->take(60) as $a) {
            $feed->push(['ts' => strtotime($a->createdAt), 'kind' => $a->kind, 'label' => $a->label, 'detail' => $a->detail, 'area' => $a->area, 'id' => (int) $a->userId]);
        }
        foreach ($views->sortByDesc('viewedAt')->take(60) as $v) {
            $feed->push(['ts' => strtotime($v->viewedAt), 'kind' => 'view', 'label' => $v->area === 'app' ? PageViews::toolLabel($v->topic) : ($v->title ?: $v->path), 'detail' => null, 'area' => $v->area === 'app' ? 'App' : 'Website', 'id' => (int) $v->userId]);
        }
        $feed = $feed->sortByDesc('ts')->take(50)->map(fn ($f) => $f + ['name' => $names[$f['id']] ?? ('Account #' . $f['id']), 'when' => Carbon::createFromTimestamp($f['ts'], config('app.timezone'))->diffForHumans()])->values()->all();

        return [
            'ready' => $ready,
            'days' => $days,
            'team' => $withTeam,
            'leftOut' => count($out),
            'since' => $since->format('M j, Y'),
            'asOf' => now()->format('g:i A'),
            'totals' => [
                'people' => count($ids),
                'doers' => $people($byKind->get('did', [])),
                'did' => $byKind->get('did', collect())->count(),
                'visits' => $visits,
                'median' => $median,
                'lockedPeople' => $people($byKind->get('locked', [])),
                'stuck' => $acts->whereIn('kind', ['refused', 'failed'])->count(),
                'stuckPeople' => $people($acts->whereIn('kind', ['refused', 'failed'])),
            ],
            'steps' => self::firstSteps($newIds, $since, $who),
            'tried' => $tried,
            'opened' => $opened,
            'locked' => $locked,
            'stuck' => $stuck,
            'areas' => $areas,
            'screens' => $screens,
            'left' => $leftList,
            'feed' => $feed,
        ];
    }

    /**
     * How far the accounts made in the period got: signed up, confirmed, came in, started a season, added a lot, added
     * a task, came back another day, used Anee, paid. Read from the records, so it holds for accounts older than the
     * counting. Each step names who did the step before it and not this one.
     */
    private static function firstSteps(array $ids, Carbon $since, callable $who): array
    {
        if (! $ids) {
            return ['total' => 0, 'list' => []];
        }
        $users = DB::table('anisystem_users')->whereIn('id', $ids)->get(['id', 'emailVerifiedAt', 'status', 'created_at', 'lastSeenAt'])->keyBy('id');
        $set = fn ($vals) => array_fill_keys(array_map('intval', collect($vals)->all()), true);
        $seasons = DB::table('as_cropping_schedules')->whereIn('anisystemUserId', $ids)->get(['id', 'anisystemUserId']);
        $owner = $seasons->pluck('anisystemUserId', 'id')->all();
        $sids = array_keys($owner);
        $lots = $set(DB::table('as_schedule_lots')->whereIn('croppingScheduleId', $sids ?: [0])->pluck('croppingScheduleId')->map(fn ($s) => $owner[$s] ?? 0));
        try {
            $lots += $set(DB::table('as_field_profiles')->whereIn('userId', $ids)->pluck('userId'));
        } catch (\Throwable $e) {
        }
        $tasks = $set(DB::table('as_schedule_activities')->whereIn('croppingScheduleId', $sids ?: [0])->pluck('croppingScheduleId')->map(fn ($s) => $owner[$s] ?? 0));
        $viewDays = DB::table(PageViews::TABLE)->whereIn('userId', $ids)->selectRaw('userId, COUNT(DISTINCT DATE(viewedAt)) d, SUM(area = \'app\') app')->groupBy('userId')->get()->keyBy('userId');
        $actDays = collect();
        $aneeActs = [];
        if (self::ready()) {
            $actDays = DB::table(UserActions::TABLE)->whereIn('userId', $ids)->selectRaw('userId, COUNT(DISTINCT DATE(createdAt)) d')->groupBy('userId')->get()->keyBy('userId');
            $aneeActs = $set(DB::table(UserActions::TABLE)->whereIn('userId', $ids)->where('kind', 'did')->whereIn('area', ['Anee', 'Anee Auto'])->pluck('userId'));
        }
        $anee = $aneeActs + $set(DB::table('as_ai_usage')->whereIn('payerId', $ids)->pluck('payerId'))
            + $set(DB::table('as_ai_usage')->whereIn('userId', $ids)->pluck('userId'));
        try {
            $anee += $set(DB::table('as_ai_ask_jobs')->whereIn('userId', $ids)->pluck('userId'));
        } catch (\Throwable $e) {
        }
        $paid = $set(DB::table('anisystem_subscriptions')->whereIn('userId', $ids)->where('price', '>', 0)->pluck('userId'))
            + $set(DB::table('anisystem_ai_credit_purchases')->whereIn('userId', $ids)->whereNotNull('grantedAt')->pluck('userId'))
            + $set(DB::table('as_orders')->whereIn('userId', $ids)->where('status', 'approved')->pluck('userId'));

        $did = [
            'Signed up' => fn ($u) => true,
            'Confirmed their email' => fn ($u) => ! empty($u->emailVerifiedAt) && $u->status !== 'pending',
            'Came into the app' => fn ($u) => in_array((int) $u->id, array_map('intval', $owner), true) || (int) ($viewDays[$u->id]->app ?? 0) > 0
                || isset($actDays[$u->id]) || ($u->lastSeenAt && strtotime($u->lastSeenAt) - strtotime($u->created_at) > 120),
            'Started a season' => fn ($u) => in_array($u->id, array_map('intval', $owner), true),
            'Added a lot' => fn ($u) => isset($lots[$u->id]),
            'Added a task' => fn ($u) => isset($tasks[$u->id]),
            'Came back another day' => fn ($u) => max((int) ($viewDays[$u->id]->d ?? 0), (int) ($actDays[$u->id]->d ?? 0)) > 1
                || ($u->lastSeenAt && substr((string) $u->lastSeenAt, 0, 10) !== substr((string) $u->created_at, 0, 10)),
            'Used Anee' => fn ($u) => isset($anee[$u->id]),
            'Paid' => fn ($u) => isset($paid[$u->id]),
        ];
        $list = [];
        $prev = null;
        foreach ($did as $label => $test) {
            $yes = [];
            foreach ($users as $u) {
                if ($test($u)) {
                    $yes[] = (int) $u->id;
                }
            }
            $list[] = [
                'label' => $label,
                'n' => count($yes),
                'share' => round(count($yes) / max(1, count($users)) * 100),
                // Did the step before, not this one: the people this step lost.
                'lost' => $prev === null ? [] : $who(array_values(array_diff($prev, $yes))),
            ];
            $prev = $yes;
        }

        return ['total' => count($users), 'list' => $list];
    }

    /** Whether the deeds are being kept yet (asked of the table itself: the schema list misses a temporary one). */
    private static function ready(): bool
    {
        try {
            DB::table(UserActions::TABLE)->limit(1)->exists();

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** id => the name the panel shows. */
    private static function names(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if (! $ids) {
            return [];
        }

        return DB::table('anisystem_users')->whereIn('id', $ids)->get(['id', 'firstName', 'lastName', 'email'])
            ->mapWithKeys(fn ($u) => [(int) $u->id => trim(($u->firstName ?? '') . ' ' . ($u->lastName ?? '')) ?: strstr((string) $u->email, '@', true) ?: ('Account #' . $u->id)])
            ->all();
    }
}
