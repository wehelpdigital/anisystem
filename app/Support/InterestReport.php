<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The admin panel's Interests report (2026-11-23, the owner: "a report module as well to have a general understanding
 * of what they are interestred with"): what people read and used over a period, counted from as_page_views
 * (App\Support\PageViews), for everyone, members or guests.
 *
 * People are counted once however many pages they open (a member by account, a guest by browser), so a section one
 * keen reader opened fifty times does not outrank one that thirty people each opened once. Kept for ten minutes per
 * period and audience: the live database answers each question in about a quarter of a second, and there are a
 * dozen of them.
 */
final class InterestReport
{
    public const PERIODS = [7, 30, 90, 365];

    public const AUDIENCES = ['all', 'members', 'guests'];

    /** Everything the page shows, for one period and one audience. */
    public static function build(int $days, string $who, bool $fresh = false): array
    {
        $days = in_array($days, self::PERIODS, true) ? $days : 30;
        $who = in_array($who, self::AUDIENCES, true) ? $who : 'all';
        $key = 'interests.report.' . $days . '.' . $who;
        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, 600, fn () => self::compute($days, $who));
    }

    private static function compute(int $days, string $who): array
    {
        $since = now()->subDays($days - 1)->startOfDay();
        $person = "COALESCE(CONCAT('u', v.userId), CONCAT('v', v.vid))";
        $base = function () use ($since, $who) {
            $q = DB::table(PageViews::TABLE . ' as v')->where('v.viewedAt', '>=', $since);
            if ($who === 'members') {
                $q->whereNotNull('v.userId');
            } elseif ($who === 'guests') {
                $q->whereNull('v.userId');
            }

            return $q;
        };
        $num = fn ($v) => (int) $v;

        $tot = $base()->selectRaw("COUNT(*) views, COUNT(DISTINCT $person) people, COUNT(DISTINCT v.userId) members")->first();
        $people = max(1, (int) $tot->people);

        // The days, weeks or months of the period, every one of them shown, the empty ones too.
        [$bucketSql, $step, $fmt] = match (true) {
            $days <= 30 => ['DATE(v.viewedAt)', 'day', 'M j'],
            $days <= 90 => ['DATE_SUB(DATE(v.viewedAt), INTERVAL WEEKDAY(v.viewedAt) DAY)', 'week', 'M j'],
            default => ["DATE_FORMAT(v.viewedAt, '%Y-%m-01')", 'month', 'M'],
        };
        $byBucket = $base()->selectRaw("$bucketSql b, COUNT(*) n, COUNT(DISTINCT $person) p")->groupBy('b')->get()->keyBy('b');
        $start = match ($step) {
            'day' => $since->copy(),
            'week' => $since->copy()->startOfWeek(Carbon::MONDAY),
            default => $since->copy()->startOfMonth(),
        };
        $trend = [];
        for ($d = $start->copy(); $d->lte(now()); $d->add(1, $step)) {
            $k = $d->format('Y-m-d');
            $trend[] = ['label' => $d->format($fmt), 'views' => (int) ($byBucket[$k]->n ?? 0), 'people' => (int) ($byBucket[$k]->p ?? 0)];
        }

        $share = fn ($p) => round(100 * (int) $p / $people);
        $sections = $base()->where('v.area', 'site')->selectRaw("v.kind, COUNT(*) n, COUNT(DISTINCT $person) p")
            ->groupBy('v.kind')->orderByDesc('p')->orderByDesc('n')->limit(16)->get()
            ->map(fn ($r) => ['key' => $r->kind, 'label' => PageViews::kindLabel($r->kind), 'views' => $num($r->n), 'people' => $num($r->p), 'share' => $share($r->p)])->all();

        $crops = $base()->whereNotNull('v.crop')->selectRaw("v.crop, COUNT(*) n, COUNT(DISTINCT $person) p")
            ->groupBy('v.crop')->orderByDesc('p')->orderByDesc('n')->limit(12)->get()
            ->map(fn ($r) => ['label' => $r->crop, 'views' => $num($r->n), 'people' => $num($r->p), 'share' => $share($r->p)])->all();

        $tools = $base()->where('v.area', 'app')->selectRaw("v.topic, COUNT(*) n, COUNT(DISTINCT $person) p")
            ->groupBy('v.topic')->orderByDesc('p')->orderByDesc('n')->limit(15)->get();
        $toolPeople = max(1, (int) $base()->where('v.area', 'app')->selectRaw("COUNT(DISTINCT $person) p")->value('p'));
        $tools = $tools->map(fn ($r) => ['label' => PageViews::toolLabel($r->topic), 'views' => $num($r->n), 'people' => $num($r->p),
            'share' => round(100 * (int) $r->p / $toolPeople)])->all();

        $pages = $base()->where('v.area', 'site')->selectRaw("v.path, MAX(v.title) title, MAX(v.kind) kind, COUNT(*) n, COUNT(DISTINCT $person) p")
            ->groupBy('v.path')->orderByDesc('p')->orderByDesc('n')->limit(15)->get()
            ->map(fn ($r) => ['path' => $r->path, 'title' => $r->title ?: $r->path, 'what' => PageViews::kindLabel($r->kind),
                'views' => $num($r->n), 'people' => $num($r->p), 'link' => self::linkable($r->path)])->all();

        $searches = $base()->whereNotNull('v.search')->selectRaw("v.search, COUNT(*) n, COUNT(DISTINCT $person) p")
            ->groupBy('v.search')->orderByDesc('p')->orderByDesc('n')->limit(20)->get()
            ->map(fn ($r) => ['words' => $r->search, 'times' => $num($r->n), 'people' => $num($r->p)])->all();

        $referrers = $base()->whereNotNull('v.referrer')->selectRaw("v.referrer, COUNT(DISTINCT $person) p")
            ->groupBy('v.referrer')->orderByDesc('p')->limit(8)->get()
            ->map(fn ($r) => ['label' => $r->referrer, 'people' => $num($r->p), 'share' => $share($r->p)])->all();

        // Devices by pages, not people: one person reads on a phone and a computer, and shares of people would add
        // past a hundred.
        $devices = $base()->whereNotNull('v.device')->selectRaw('v.device, COUNT(*) n')
            ->groupBy('v.device')->orderByDesc('n')->get()
            ->map(fn ($r) => ['label' => $r->device, 'views' => $num($r->n), 'share' => round(100 * (int) $r->n / max(1, (int) $tot->views))])->all();

        return [
            'days' => $days,
            'who' => $who,
            'since' => $since->format('M j, Y'),
            'asOf' => now()->format('M j, g:i A'),
            'step' => $step,
            'totals' => [
                'views' => (int) $tot->views,
                'people' => (int) $tot->people,
                'members' => (int) $tot->members,
                'perPerson' => $tot->people ? round($tot->views / $tot->people, 1) : 0,
            ],
            'trend' => $trend,
            'sections' => $sections,
            'crops' => $crops,
            'tools' => $tools,
            'pages' => $pages,
            'searches' => $searches,
            'referrers' => $referrers,
            'devices' => $devices,
            'signups' => self::beforeSignup($since),
            'members' => $who === 'guests' ? [] : self::activeMembers($base),
        ];
    }

    /**
     * What the people who signed up in the period read before they had an account: the first page they ever opened,
     * and the pages most of them passed through.
     */
    private static function beforeSignup(Carbon $since): array
    {
        $joined = fn () => DB::table(PageViews::TABLE . ' as v')
            ->join('anisystem_users as u', 'u.id', '=', 'v.userId')
            ->where('u.created_at', '>=', $since)
            ->whereColumn('v.viewedAt', '<', 'u.created_at');

        $who = (int) $joined()->selectRaw('COUNT(DISTINCT v.userId) n')->value('n');
        $total = (int) DB::table('anisystem_users')->where('created_at', '>=', $since)->where('deleteStatus', 1)->count();
        $pages = $joined()->selectRaw('v.path, MAX(v.title) title, MAX(v.kind) kind, COUNT(DISTINCT v.userId) p, COUNT(*) n')
            ->groupBy('v.path')->orderByDesc('p')->orderByDesc('n')->limit(10)->get()
            ->map(fn ($r) => ['path' => $r->path, 'title' => $r->title ?: $r->path, 'what' => PageViews::kindLabel($r->kind),
                'people' => (int) $r->p, 'link' => self::linkable($r->path)])->all();
        // Each new member's earliest page, picked here rather than by joining the table to itself: the trails are
        // short, and one pass over them reads the same.
        $firsts = [];
        $seen = [];
        foreach ($joined()->orderBy('v.id')->limit(20000)->get(['v.userId', 'v.path', 'v.title', 'v.kind']) as $r) {
            if (isset($seen[$r->userId])) {
                continue;
            }
            $seen[$r->userId] = true;
            $firsts[$r->path] ??= ['path' => $r->path, 'title' => $r->title ?: $r->path, 'what' => PageViews::kindLabel($r->kind),
                'people' => 0, 'link' => self::linkable($r->path)];
            $firsts[$r->path]['people']++;
        }
        usort($firsts, fn ($a, $b) => $b['people'] <=> $a['people']);
        $firsts = array_slice($firsts, 0, 8);

        return ['tracked' => $who, 'signups' => $total, 'pages' => $pages, 'firsts' => $firsts];
    }

    /** The members who opened the most, each with the thing they open most and the crop they read about most. */
    private static function activeMembers(\Closure $base): array
    {
        $rows = $base()->whereNotNull('v.userId')
            ->selectRaw('v.userId, COUNT(*) n, COUNT(DISTINCT DATE(v.viewedAt)) d, MAX(v.viewedAt) last')
            ->groupBy('v.userId')->orderByDesc('n')->limit(12)->get();
        if ($rows->isEmpty()) {
            return [];
        }
        $ids = $rows->pluck('userId')->all();
        $top = [];
        $base()->whereIn('v.userId', $ids)
            ->selectRaw("v.userId, CASE WHEN v.area = 'app' THEN CONCAT('app|', COALESCE(v.topic, '')) ELSE CONCAT('site|', v.kind) END k, COUNT(*) n")
            ->groupBy('v.userId', 'k')->orderByDesc('n')->get()
            ->each(function ($r) use (&$top) {
                $top[$r->userId] ??= $r->k;
            });
        $crop = [];
        $base()->whereIn('v.userId', $ids)->whereNotNull('v.crop')
            ->selectRaw('v.userId, v.crop, COUNT(*) n')->groupBy('v.userId', 'v.crop')->orderByDesc('n')->get()
            ->each(function ($r) use (&$crop) {
                $crop[$r->userId] ??= $r->crop;
            });
        $users = DB::table('anisystem_users')->whereIn('id', $ids)->get(['id', 'firstName', 'lastName', 'email'])->keyBy('id');

        return $rows->map(function ($r) use ($users, $top, $crop) {
            $u = $users[$r->userId] ?? null;
            [$area, $what] = array_pad(explode('|', (string) ($top[$r->userId] ?? ''), 2), 2, '');

            return [
                'id' => (int) $r->userId,
                'name' => $u ? (trim(($u->firstName ?? '') . ' ' . ($u->lastName ?? '')) ?: $u->email) : 'Removed account',
                'email' => $u->email ?? null,
                'views' => (int) $r->n,
                'days' => (int) $r->d,
                'last' => Carbon::parse($r->last)->diffForHumans(),
                'top' => $area === 'app' ? PageViews::toolLabel($what) : ($area === 'site' ? PageViews::kindLabel($what) : null),
                'crop' => $crop[$r->userId] ?? null,
            ];
        })->all();
    }

    /** A public page the admin can open from the report (never a folded id or a secret's door). */
    private static function linkable(string $path): ?string
    {
        return str_contains($path, ':id') || in_array($path, ['/ask-anee/answer', '/ask-anee/question', '/s'], true) ? null : url($path);
    }
}
