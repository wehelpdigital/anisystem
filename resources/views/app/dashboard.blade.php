@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Your farm at a glance')

{{-- Reuse the shared community design system so the avatars / hues on this
     page match the plaza exactly (extend the partial, don't reinvent). --}}
@push('head')
@include('community.partials.plaza-css')
<style>
    /* Feed + sidebar shell (mirrors the wall page): the co-farmer feed is the
       main column, AI + discussions ride a sticky rail on wide screens and fold
       below the feed on tablet/mobile. */
    .dash-shell { display: grid; grid-template-columns: 1fr; gap: 1.25rem; align-items: start; }
    @media (min-width: 1024px) {
        .dash-shell { grid-template-columns: minmax(0, 1fr) 20rem; }
        .dash-side { position: sticky; top: 5rem; }
    }
    /* Weather: shimmering skeleton while it loads, then the forecast fades up. */
    :root { --wx-sk1: #edf0f3; --wx-sk2: #dfe4ea; }
    html.dark { --wx-sk1: #1b2417; --wx-sk2: #27331d; }
    .wx-loading { margin-top: .5rem; padding-top: .5rem; border-top: 1px solid var(--color-gray-100); }
    html.dark .wx-loading { border-color: #2b3a1c; }
    .wx-skel { background: linear-gradient(90deg, var(--wx-sk1) 25%, var(--wx-sk2) 50%, var(--wx-sk1) 75%);
        background-size: 200% 100%; border-radius: .5rem; animation: wxShimmer 1.3s linear infinite; }
    .wx-skel-line { height: .72rem; width: 45%; border-radius: 999px; margin-bottom: .55rem; }
    .wx-skel-row { display: flex; gap: .3rem; }
    .wx-skel-cell { flex: 1 1 0; height: 3.7rem; }
    @keyframes wxShimmer { from { background-position: 200% 0; } to { background-position: -200% 0; } }
    .dash-wx.wx-ready > * { animation: wxReveal .45s cubic-bezier(.22, 1, .36, 1) both; }
    @keyframes wxReveal { from { opacity: 0; transform: translateY(7px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) {
        .wx-skel { animation: none; opacity: .6; }
        .dash-wx.wx-ready > * { animation: none; }
    }
    /* "Today" weather cell — no box, just green text (the theme doesn't remap
       the green ramp, so both modes are set explicitly here). */
    .wx-today-label { color: #15803d; }
    html.dark .wx-today-label { color: #86efac; }

    /* ---- the greeting and the account's numbers, in the same calm
       language as the schedules shelf: a white card, one green hairline,
       the hour drawn rather than shouted. ---- */
    .dash-hero { display: flex; align-items: center; gap: .85rem; flex-wrap: wrap;
        padding: 1.05rem 1.25rem; border-radius: 1.1rem; position: relative; overflow: hidden;
        background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .dash-hero::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 3px;
        background: linear-gradient(90deg, #6b9f3d, #b8d38e 55%, transparent); }
    .dash-hero-mark { width: 3rem; height: 3rem; border-radius: 999px; flex-shrink: 0;
        display: inline-flex; align-items: center; justify-content: center; }
    .dash-hero-mark svg { width: 1.55rem; height: 1.55rem; }
    .tod-morning { background: linear-gradient(135deg, #fff7e0, #fbe6ae); color: #d97706; }
    .tod-afternoon { background: linear-gradient(135deg, #e8f4fd, #cde7fa); color: #0284c7; }
    .tod-evening { background: linear-gradient(135deg, #e9e7fb, #d5d2f2); color: #6d28d9; }
    html.dark .tod-morning { color: #fbbf24; }
    html.dark .tod-afternoon { color: #7dd3fc; }
    html.dark .tod-evening { color: #c4b5fd; }
    html.dark .tod-morning, html.dark .tod-afternoon, html.dark .tod-evening { background: rgb(255 255 255 / .07); }
    .dash-hero-h { font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800;
        color: var(--color-gray-900); line-height: 1.2; letter-spacing: -.01em; }
    .dash-hero-p { font-size: .82rem; color: var(--color-gray-500); margin-top: .2rem; }
    /* Enough air that the greeting reads as a welcome rather than a header. */
    .dash-hero { padding: 1.25rem 1.35rem; }
    .dash-hero-mark { width: 3.35rem; height: 3.35rem; }
    .dash-hero-mark svg { width: 1.7rem; height: 1.7rem; }
    .dash-hero-warn { display: inline-flex; align-items: center; gap: .3rem; margin-top: .35rem;
        font-size: .78rem; font-weight: 700; color: #b45309; }
    .dash-hero-warn svg { width: .85rem; height: .85rem; }
    .dash-hero-state { flex-shrink: 0; margin-left: auto; }
    .dash-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .75rem;
        border-radius: 999px; font-size: .74rem; font-weight: 700;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-200); color: var(--color-gray-600); }
    .dash-chip.is-ok { background: #f0f7e8; border-color: #cfe3b8; color: #3d6823; }
    .dash-chip.is-warn { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }
    html.dark .dash-hero { background: #151b12; border-color: #2b3a1c; }
    html.dark .dash-hero-h { color: #e8efe1; }
    html.dark .dash-hero-p { color: #a8bd93; }
    html.dark .dash-chip { background: rgb(255 255 255 / .05); border-color: #2b3a1c; color: #cdd8c0; }
    html.dark .dash-chip.is-ok { background: rgb(61 104 35 / .22); border-color: #3f5626; color: #bfe19a; }
    html.dark .dash-chip.is-warn { background: rgb(154 52 18 / .2); border-color: rgb(154 52 18 / .5); color: #fdba74; }

    /* ---- what a season has next: a date block, then the work. It used to
       be a bordered panel inside a bordered card, with a calendar emoji and
       bullet dots doing the job the layout should do. ---- */
    .dn-next { display: flex; align-items: stretch; gap: .7rem; padding: .6rem .7rem;
        border-radius: .8rem; text-decoration: none; background: var(--color-gray-50);
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .dn-next:hover { background: var(--color-gray-100); }
    .dn-next.is-today { background: #f0f7e8; }
    .dn-next.is-today:hover { background: #e4efd4; }
    .dn-when { flex: 0 0 3.1rem; display: flex; flex-direction: column; align-items: center;
        justify-content: center; border-radius: .6rem; padding: .3rem 0;
        background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .dn-when b { font-size: .82rem; font-weight: 800; line-height: 1.1; color: var(--color-gray-700); }
    .dn-when i { font-style: normal; font-size: .6rem; font-weight: 700; color: var(--color-gray-400); }
    .dn-next.is-today .dn-when { background: #4a7c2a; border-color: #4a7c2a; }
    .dn-next.is-today .dn-when b, .dn-next.is-today .dn-when i { color: #fff; }
    .dn-next.is-today .dn-when i { opacity: .85; }
    .dn-what { min-width: 0; flex: 1 1 auto; display: flex; flex-direction: column; gap: .1rem; justify-content: center; }
    .dn-lead { font-size: .68rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
        color: #3d6823; }
    .dn-lead em { font-style: normal; font-weight: 600; color: var(--color-gray-400); text-transform: none; letter-spacing: 0; }
    .dn-next:not(.is-today) .dn-lead { color: var(--color-gray-500); }
    .dn-task { font-size: .82rem; font-weight: 600; color: var(--color-gray-800); line-height: 1.35;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dn-more { font-size: .68rem; font-weight: 700; color: var(--color-gray-400); }
    .dn-go { width: 1.1rem; height: 1.1rem; flex: none; align-self: center; color: var(--color-gray-300); }
    .dn-next:hover .dn-go { color: #6b9f3d; }
    .dn-quiet { font-size: .78rem; color: var(--color-gray-400); }
    html.dark .dn-next { background: rgb(255 255 255 / .04); }
    html.dark .dn-next:hover { background: rgb(255 255 255 / .08); }
    html.dark .dn-next.is-today { background: rgb(61 104 35 / .22); }
    html.dark .dn-when { background: #151b12; border-color: #2b3a1c; }
    html.dark .dn-when b { color: #e8efe1; }
    html.dark .dn-task { color: #cdd8c0; }
    @media (prefers-reduced-motion: reduce) { .dn-next { transition: none; } }

    /* The composer: a field you can see yourself writing in. */
    .dash-comp { padding: .85rem !important; }
    .dash-comp-box { min-height: 6.5rem; font-size: .85rem; line-height: 1.55; resize: vertical; }
    .dash-comp-box::placeholder { font-size: .82rem; }

    .dash-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; }
    .dash-stat { display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: .1rem; padding: .8rem .6rem; border-radius: .9rem; text-align: center; text-decoration: none;
        background: var(--color-white); border: 1px solid var(--color-gray-200);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    a.dash-stat:hover { border-color: #cfe3b8; transform: translateY(-1px); }
    .dash-stat b { font-size: 1.35rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.1; }
    .dash-stat .dash-stat-word { font-size: .95rem; }
    .dash-stat i { font-style: normal; font-size: .62rem; font-weight: 700; letter-spacing: .06em;
        text-transform: uppercase; color: var(--color-gray-400); }
    .dash-stat.is-lead { background: #f0f7e8; border-color: #cfe3b8; }
    .dash-stat.is-lead b { color: #3d6823; }
    .dash-stat.is-lead i { color: #6b9f3d; }
    .dash-stat.is-warn b { color: #c2410c; }
    html.dark .dash-stat { background: #151b12; border-color: #2b3a1c; }
    html.dark .dash-stat b { color: #e8efe1; }
    html.dark .dash-stat.is-lead { background: rgb(61 104 35 / .22); border-color: #3f5626; }
    html.dark .dash-stat.is-lead b { color: #bfe19a; }
    @media (max-width: 480px) {
        .dash-hero { padding: .9rem 1rem; gap: .7rem; }
        .dash-hero-state { margin-left: 0; flex-basis: 100%; }
        .dash-stat b { font-size: 1.15rem; }
    }
    @media (prefers-reduced-motion: reduce) { .dash-stat { transition: none; } }
    {{-- .wall-act composer buttons live in plaza-css (shared). --}}
</style>
@endpush

@section('content')
@php
    $isSuperAdmin = $user->isSuperAdmin();   // mother-site admin — full access, no plan needed
    $status = $subscription?->effective_status;
    $daysRemaining = $subscription?->daysRemaining();
    $isActive = $status === \App\Models\Subscription::STATUS_ACTIVE;
    $expiringSoon = $isActive && $daysRemaining !== null && $daysRemaining <= 7;

    $scheduleBadge = fn (?string $s) => match ($s) {
        'draft' => ['badge-gray', 'Draft'],
        'setup' => ['badge-yellow', 'Setting up'],
        'generated' => ['badge-green', 'Generated'],
        'completed' => ['badge-blue', 'Completed'],
        'archived' => ['badge-gray', 'Archived'],
        default => ['badge-gray', ucfirst((string) $s)],
    };

    $snippet = fn (?string $body, int $len = 90) => \Illuminate\Support\Str::limit(strip_tags((string) $body), $len);
@endphp

<div class="space-y-5 md:space-y-6">

    {{-- The greeting, in the same language as every other page: a calm card
         with the hour drawn on it, not a slab of green. --}}
    @php
        $__h = (int) now('Asia/Manila')->format('G');
        [$__greet, $__tod] = $__h < 12
            ? ['Magandang umaga', 'tod-morning']
            : ($__h < 18 ? ['Magandang hapon', 'tod-afternoon'] : ['Magandang gabi', 'tod-evening']);
    @endphp
    <div class="dash-hero">
        <span class="dash-hero-mark {{ $__tod }}" aria-hidden="true">
            @if ($__tod === 'tod-morning')
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v2M5.3 6.7l1.4 1.4M18.7 6.7l-1.4 1.4M8 15a4 4 0 118 0M2 15h2m16 0h2M3 19h18"/></svg>
            @elseif ($__tod === 'tod-afternoon')
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4l1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4m11.4-11.4l1.4-1.4"/></svg>
            @else
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            @endif
        </span>
        <div class="min-w-0 grow">
            <h2 class="dash-hero-h">{{ $__greet }}, {{ \Illuminate\Support\Str::title($user->firstName ?: 'kaibigan') }}</h2>
            <p class="dash-hero-p">{{ now('Asia/Manila')->format('l, F j') }} — {{ $scheduleCount === 0 ? 'no seasons planned yet.' : $scheduleCount . ' ' . \Illuminate\Support\Str::plural('season', $scheduleCount) . ' on the shelf.' }}</p>
            @if ($expiringSoon)
                <a href="{{ route('purchase.plans') }}" class="dash-hero-warn">
                    Renew before your subscription expires
                    <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endif
        </div>
        <div class="dash-hero-state">
            @if ($isSuperAdmin)
                <span class="dash-chip is-ok">🛡️ Admin access</span>
            @elseif ($isActive)
                <span class="dash-chip {{ $expiringSoon ? 'is-warn' : 'is-ok' }}">
                    {{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', (int) $daysRemaining) }} left
                </span>
            @elseif ($status === 'pending')
                <span class="dash-chip is-warn">Verification pending</span>
            @else
                <a href="{{ route('purchase.plans') }}" class="btn btn-primary btn-sm">Subscribe now</a>
            @endif
        </div>
    </div>

    {{-- What the account holds, as labelled tiles rather than three cards
         each shouting a different size of number. --}}
    <div class="dash-stats">
        <a href="{{ route('sm.index') }}" class="dash-stat is-lead">
            <b>{{ number_format($scheduleCount) }}</b>
            <i>{{ \Illuminate\Support\Str::plural('Schedule', $scheduleCount) }}</i>
        </a>
        <div class="dash-stat">
            <b class="dash-stat-word">{{ $isSuperAdmin ? 'Admin' : ($isActive ? $subscription->planName : '—') }}</b>
            <i>Active plan</i>
        </div>
        <div class="dash-stat {{ $expiringSoon ? 'is-warn' : '' }}">
            <b>{{ $isSuperAdmin ? '∞' : ($isActive && $daysRemaining !== null ? number_format($daysRemaining) : '—') }}</b>
            <i>Days left</i>
        </div>
    </div>

    {{-- My Cropping Schedules (top — the primary workspace) --}}
    <div>
        <div class="flex items-center justify-between gap-3 mb-3 px-1">
            <h2 class="text-base md:text-lg font-bold text-gray-900">🌾 My Cropping Schedules</h2>
            @if ($latestSchedules->isNotEmpty())
                <a href="{{ route('sm.index') }}" class="text-sm font-bold text-brand-700 hover:underline shrink-0">View all</a>
            @endif
        </div>

        @if ($latestSchedules->isEmpty())
            <div class="card">
                <div class="card-body text-center py-10 md:py-14">
                    <svg class="w-24 h-24 mx-auto mb-4 text-brand-300" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 13c0-3 2-5.5 5.5-5.5.2 2.8-1.6 5.5-5.5 5.5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 13c0-3-2-5.5-5.5-5.5C6.3 10.3 8.1 13 12 13z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 21h16"/>
                    </svg>
                    @if ($scheduleCount > 0)
                        <h3 class="text-lg font-bold text-gray-900">No active schedules</h3>
                        <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">
                            None of your schedules have activities within 6 months of today. Open all schedules to review or update them.
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-2 mt-5">
                            <a href="{{ route('sm.index') }}" class="btn btn-white btn-lg">View all schedules</a>
                            <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg">+ New Schedule</a>
                        </div>
                    @else
                        <h3 class="text-lg font-bold text-gray-900">Plant your first schedule</h3>
                        <p class="text-sm text-gray-500 mt-1 max-w-xs mx-auto">
                            Create a cropping schedule to plan your lots, workers, activities and irrigation for the season.
                        </p>
                        <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg mt-5">+ New Cropping Schedule</a>
                    @endif
                </div>
            </div>
        @else
            {{-- One active schedule fills the row; two or more go side by side. --}}
            <div class="grid gap-3 {{ $latestSchedules->count() > 1 ? 'sm:grid-cols-2' : 'grid-cols-1' }}">
                @foreach ($latestSchedules as $schedule)
                    @php
                        [$sBadge, $sLabel] = $scheduleBadge($schedule->status);
                        $next = $scheduleNext[$schedule->id] ?? null;
                    @endphp
                    <div class="card card-hover">
                        <div class="card-body !p-4 flex flex-col gap-3 h-full">
                            <h3 class="font-bold text-gray-900 leading-snug">{{ $schedule->title }}</h3>

                            {{-- What is next on THIS season: today's work, or
                                 the nearest day that has any. It reads as a
                                 strip — when, then what — rather than a
                                 bordered box repeating the card it sits in. --}}
                            @if ($next)
                                <a href="{{ route('sm.activities', ['id' => $schedule->id]) }}"
                                   class="dn-next {{ $next['isToday'] ? 'is-today' : '' }}">
                                    <span class="dn-when">
                                        <b>{{ $next['isToday'] ? 'Today' : $next['date']->format('D') }}</b>
                                        <i>{{ $next['isToday'] ? $next['date']->format('M j') : $next['date']->format('M j') }}</i>
                                    </span>
                                    <span class="dn-what">
                                        <span class="dn-lead">
                                            {{ $next['activities']->count() }} {{ \Illuminate\Support\Str::plural('task', $next['activities']->count()) }}
                                            @unless ($next['isToday'])
                                                <em>· in {{ $next['daysAway'] }} {{ \Illuminate\Support\Str::plural('day', $next['daysAway']) }}</em>
                                            @endunless
                                        </span>
                                        @foreach ($next['activities']->take(2) as $act)
                                            <span class="dn-task">{{ $act->activityTitle }}</span>
                                        @endforeach
                                        @if ($next['moreCount'] > 0)
                                            <span class="dn-more">+{{ $next['moreCount'] }} more</span>
                                        @endif
                                    </span>
                                    <svg class="dn-go" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            @else
                                <p class="dn-quiet">Nothing planned on this season yet.</p>
                            @endif

                            {{-- Local weather for this schedule's lot location(s).
                                 Filled by JS from the deduped /app/weather feed. --}}
                            @if ($scheduleHasLocation[$schedule->id] ?? false)
                                <div data-weather-for="{{ $schedule->id }}" class="dash-wx">
                                    <div class="wx-loading" role="status" aria-label="Loading">
                                        <div class="wx-skel wx-skel-line"></div>
                                        <div class="wx-skel-row">
                                            <div class="wx-skel wx-skel-cell"></div>
                                            <div class="wx-skel wx-skel-cell"></div>
                                            <div class="wx-skel wx-skel-cell"></div>
                                            <div class="wx-skel wx-skel-cell"></div>
                                            <div class="wx-skel wx-skel-cell"></div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('sm.lots', ['id' => $schedule->id]) }}" class="inline-flex items-center gap-1 text-[0.688rem] font-semibold text-brand-600 hover:text-brand-700">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Add a lot address for local weather
                                </a>
                            @endif

                            <div class="flex items-center justify-between gap-3 mt-auto pt-1">
                                <div class="min-w-0 flex items-center gap-2 flex-wrap">
                                    <p class="text-xs text-gray-500 shrink-0">Created {{ $schedule->created_at?->format('M j, Y') }}</p>
                                    <span class="badge {{ $sBadge }} shrink-0">{{ $sLabel }}</span>
                                </div>
                                <a href="{{ route('sm.hub', ['id' => $schedule->id]) }}" class="btn btn-outline btn-sm shrink-0">Open</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('sm.create') }}" class="btn btn-primary btn-lg w-full mt-4">+ New Cropping Schedule</a>
        @endif
    </div>

    {{-- ===================== Feed + sidebar shell ===================== --}}
    <div class="dash-shell">

        {{-- MAIN COLUMN: support (if any) + your co-farmers' wall feed --}}
        <div class="min-w-0 space-y-5 md:space-y-6">

            @if ($openTickets->isNotEmpty())
                <section class="card">
                    <div class="card-body !p-4">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <h2 class="text-sm md:text-base font-bold text-gray-900">🛟 Open support tickets</h2>
                            <a href="{{ route('support.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 shrink-0">All tickets →</a>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach ($openTickets as $ticket)
                                @php $tAnswered = $ticket->status === 'answered'; $tWhen = $ticket->lastReplyAt ?? $ticket->created_at; @endphp
                                <a href="{{ route('support.show', ['id' => $ticket->id]) }}" class="block px-1 py-2.5 hover:bg-gray-50 transition">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="font-semibold text-gray-900 text-sm leading-snug min-w-0 line-clamp-1">{{ $ticket->subject }}</p>
                                        <span class="badge {{ $tAnswered ? 'badge-green' : 'badge-yellow' }} shrink-0">{{ $tAnswered ? 'Answered' : 'Open' }}</span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        @if ($ticket->category)<span class="font-semibold text-gray-500">{{ ucfirst($ticket->category) }}</span> · @endif
                                        Last reply {{ optional($tWhen)->diffForHumans() }}
                                    </p>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            <div>
                <div class="flex items-center justify-between gap-3 mb-3 px-1">
                    <h2 class="text-base md:text-lg font-bold text-gray-900">🌾 Community Wall</h2>
                    <a href="{{ route('community.index') }}" class="text-sm font-bold text-brand-700 hover:underline shrink-0">See more</a>
                </div>

                {{-- Post to your wall — appears here, in /app/community and on your
                     profile wall (they're one wall). Supports photo + video. --}}
                <div id="dashComposer" data-video-host class="card mb-5">
                    <div class="card-body dash-comp">
                        <div class="flex items-start gap-2.5">
                            <span class="avatar avatar-md {{ \App\Support\CommunityAvatar::hue(auth()->user()->full_name ?? '?') }} shrink-0">{{ auth()->user()->initials ?? '?' }}</span>
                            <div class="min-w-0 grow">
                                {{-- Room to actually write in, in a size that
                                     fits more words on a phone: the old box
                                     was two lines of large type. --}}
                                <textarea id="dashPostBody" data-mentionable data-preview="#dashPreview" rows="4" maxlength="5000" class="form-textarea w-full dash-comp-box" placeholder="Share something with your co-farmers — a question, a photo of the field, what the weather did…"></textarea>
                                <div id="dashPreview" class="cp-preview" style="display:none"><span class="cp-label">Preview</span><div class="cp-body"></div></div>

                                <div id="dashImageChip" class="hidden mt-2 items-center gap-2 text-xs font-semibold text-gray-600">
                                    <img src="" id="dashImageThumb" class="w-10 h-10 rounded-lg object-cover" alt="">
                                    <button type="button" id="dashImageClear" class="text-red-600 font-bold">Remove photo</button>
                                </div>
                                <span class="js-video-chip mt-2 items-center gap-2 text-xs font-semibold text-gray-600" style="display:none">
                                    <span class="js-video-name"></span>
                                    <button type="button" class="js-video-clear text-red-600 font-bold">Remove</button>
                                </span>

                                <div class="flex items-center justify-between gap-2 mt-2 flex-wrap">
                                    <div class="flex items-center gap-1 flex-wrap">
                                        <label class="wall-act cursor-pointer" title="Add a photo" aria-label="Add a photo">
                                            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <input type="file" id="dashImage" accept="image/*" class="hidden">
                                        </label>
                                        <button type="button" class="wall-act js-video-attach" title="Upload a video" aria-label="Upload a video">
                                            <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </button>
                                        <button type="button" class="wall-act js-video-record" title="Record a video" aria-label="Record a video">
                                            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4.5" fill="currentColor"/></svg>
                                        </button>
                                        <input type="file" class="js-video-file hidden" accept="video/*">
                                        <button type="button" class="wall-act js-emoji-btn" data-target="dashPostBody" title="Add an emoji" aria-label="Add an emoji">
                                            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                    </div>
                                    <button type="button" id="dashPostBtn" class="btn btn-primary btn-sm shrink-0">Post</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="dashWallFeed" data-animate-list>
                    @foreach ($connectedWall as $post)
                        @include('community.partials.feed-post', ['post' => $post, 'friendIds' => $friendIds])
                    @endforeach
                </div>
                <a href="{{ route('community.index') }}" id="dashWallEmpty" class="card card-hover block {{ $connectedWall->isEmpty() ? '' : 'hidden' }}">
                    <div class="card-body text-center py-8">
                        <div class="text-3xl mb-2">🌱</div>
                        <h3 class="font-bold text-gray-900">Meet your co-farmers</h3>
                        <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">Share an update above, or connect with other farmers to see their posts here.</p>
                    </div>
                </a>
            </div>

        </div>

        {{-- SIDEBAR: AI Technician + Latest Discussions --}}
        <aside class="dash-side space-y-4">

            @if ($canUseAi)
                <section class="card">
                    <div class="card-body !p-4">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex items-baseline gap-2 min-w-0">
                                <h2 class="text-sm font-bold text-gray-900 shrink-0">🤖 AI Technician</h2>
                                <a href="{{ route('ai.credits') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 whitespace-nowrap" title="Buy AI credits">⚡ {{ number_format((int) $aiBalance) }}</a>
                            </div>
                            <a href="{{ route('ai.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 shrink-0">Open →</a>
                        </div>
                        @if ($recentChats->isEmpty())
                            <a href="{{ route('ai.index') }}" class="flex items-center gap-3 px-1 py-2 rounded-lg hover:bg-gray-50 transition">
                                <span class="text-2xl leading-none">💬</span>
                                <div class="min-w-0">
                                    <p class="font-bold text-gray-900 text-sm">Start a conversation</p>
                                    <p class="text-xs text-gray-500">Ask about pests, fertilizer, planting…</p>
                                </div>
                            </a>
                        @else
                            <div class="divide-y divide-gray-100">
                                @foreach ($recentChats as $chat)
                                    <a href="{{ route('ai.index', ['c' => $chat->id, 'scheduleId' => $chat->croppingScheduleId]) }}" class="flex items-start gap-2.5 px-1 py-2.5 hover:bg-gray-50 transition">
                                        <span class="text-lg leading-none mt-0.5">💬</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 text-sm leading-snug line-clamp-1">{{ $chat->title ?: 'Untitled chat' }}</p>
                                            <p class="text-xs text-gray-400">Updated {{ $chat->updated_at?->diffForHumans() }}</p>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            <section class="card">
                <div class="card-body !p-4">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <h2 class="text-sm font-bold text-gray-900">💬 Latest Discussions</h2>
                        <a href="{{ route('community.groups.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 shrink-0">See more →</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse ($latestDiscussions as $d)
                            <a href="{{ route('community.groups.show', ['id' => $d->groupId]) }}" class="block px-1 py-2.5 hover:bg-gray-50 transition">
                                <p class="text-xs text-gray-400 leading-tight truncate">
                                    <span class="font-semibold text-brand-700">{{ optional($d->group)->name }}</span>
                                    · {{ optional($d->author)->full_name }}
                                </p>
                                @if ($d->title)
                                    <p class="font-bold text-gray-900 text-sm leading-snug mt-0.5 line-clamp-2">{{ $d->title }}</p>
                                @else
                                    <p class="text-sm text-gray-700 leading-snug mt-0.5 line-clamp-2">{{ $snippet($d->body) }}</p>
                                @endif
                                <p class="text-xs text-gray-400 mt-1">
                                    💬 {{ $d->replies?->count() ?? 0 }} {{ \Illuminate\Support\Str::plural('reply', $d->replies?->count() ?? 0) }}
                                    · {{ $d->created_at?->diffForHumans() }}
                                </p>
                            </a>
                        @empty
                            <a href="{{ route('community.groups.index') }}" class="block px-1 py-4 text-center text-sm text-gray-400 hover:text-brand-700">Join a discussion group →</a>
                        @endforelse
                    </div>
                </div>
            </section>

        </aside>

    </div>

    {{-- Latest from the Technician's Blog --}}
    @if (!empty($latestBlog) && $latestBlog->isNotEmpty())
        <div>
            <div class="flex items-center justify-between gap-3 mb-3 px-1">
                <h2 class="text-base md:text-lg font-bold text-gray-900">📰 From the Technician's Blog</h2>
                <a href="{{ route('community.blog') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 shrink-0">See all →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach ($latestBlog as $article)
                    <a href="{{ route('community.blog.show', ['id' => $article->id]) }}" class="card card-hover overflow-hidden block">
                        <div style="aspect-ratio:16/9;background:linear-gradient(120deg,var(--color-brand-100),var(--color-brand-50));overflow:hidden;">
                            @if ($article->coverUrl())
                                <img src="{{ $article->coverUrl() }}" alt="" loading="lazy" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-3xl">🌾</div>
                            @endif
                        </div>
                        <div class="p-3">
                            <span class="font-bold text-gray-900 text-sm leading-tight block">{{ \Illuminate\Support\Str::limit($article->title, 60) }}</span>
                            @if ($article->publishedAt)<span class="text-xs text-gray-400">{{ $article->publishedAt->format('M j, Y') }}</span>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

</div>

@include('community.partials.wall-comments-modal')
@endsection

@push('scripts')
{{-- The co-farmer feed reuses the wall's post cards, so it needs the same
     community JS: reactions, comment form, emoji, photo, mentions, modal. --}}
@include('community.partials.emoji-js')
@include('community.partials.lightbox-js')
@include('community.partials.comment-tools-js')
@include('community.partials.react-js')
@include('community.partials.mention-js')
@include('community.partials.wall-comment-js')
@include('community.partials.video-js')
@include('community.partials.composer-preview-js')

{{-- Dashboard wall composer — posts to your own wall (the same wall shown in
     /app/community and on your profile). Photo + video (upload or record). --}}
<script>
(() => {
    const host = document.getElementById('dashComposer');
    if (!host) return;
    const body = document.getElementById('dashPostBody');
    const btn = document.getElementById('dashPostBtn');
    const feed = document.getElementById('dashWallFeed');
    const empty = document.getElementById('dashWallEmpty');
    const imageInput = document.getElementById('dashImage');
    const imgChip = document.getElementById('dashImageChip');
    const POST_URL = @json(route('community.wall.post', ['userId' => auth()->id()]));
    const csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';

    // Photo chip
    imageInput?.addEventListener('change', () => {
        const f = imageInput.files[0];
        if (f) { document.getElementById('dashImageThumb').src = URL.createObjectURL(f); imgChip.style.display = 'flex'; }
        else imgChip.style.display = 'none';
    });
    document.getElementById('dashImageClear')?.addEventListener('click', () => { imageInput.value = ''; imgChip.style.display = 'none'; });

    // Grow the textarea with content.
    body?.addEventListener('input', () => { body.style.height = 'auto'; body.style.height = Math.min(body.scrollHeight, 200) + 'px'; });

    btn?.addEventListener('click', async () => {
        const text = body.value.trim();
        const img = imageInput.files[0];
        const vid = window.plazaVideoFile ? window.plazaVideoFile(host) : null;
        if (!text && !img && !vid) { toast('Write something or add a photo/video.', 'error'); return; }
        const prev = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = vid ? 'Uploading…' : 'Posting…';
        try {
            const fd = new FormData();
            if (text) fd.append('body', text);
            if (img) fd.append('image', img);
            if (vid) fd.append('video', vid);
            fd.append('render', 'feed');
            const res = await fetch(POST_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, body: fd });
            const data = await res.json();
            if (!data.success) { toast(data.message || 'Could not post.', 'error'); return; }
            if (feed && data.data?.html) {
                feed.insertAdjacentHTML('afterbegin', data.data.html);
                const added = feed.firstElementChild;
                if (added) { added.classList.add('plaza-comment-enter'); added.addEventListener('animationend', () => added.classList.remove('plaza-comment-enter'), { once: true }); }
            }
            if (empty) empty.classList.add('hidden');
            body.value = ''; body.style.height = 'auto';
            imageInput.value = ''; imgChip.style.display = 'none';
            window.plazaClearVideo && window.plazaClearVideo(host);
            toast('Posted to your wall.');
        } catch (_) { toast('Network error — try again.', 'error'); }
        finally { btn.disabled = false; btn.innerHTML = prev; }
    });
})();
</script>

{{-- Per-schedule weather: one deduped fetch fills every schedule card's
     [data-weather-for] slot. Fetched after paint so Open-Meteo never blocks the
     dashboard; each card degrades to nothing on failure. --}}
<script>
(() => {
    const slots = Array.from(document.querySelectorAll('[data-weather-for]'));
    if (!slots.length) return;
    const esc = window.escapeHtml || ((s) => String(s == null ? '' : s));

    // The forecast always starts on today, so day index 0 is today — mark it by
    // index rather than a (possibly cached) flag, so the green marker is reliable.
    function dayCell(d, isToday) {
        const today = isToday || d.isToday;
        // No box — today is simply shown in green (theme-aware via .wx-today-label).
        return `<div class="flex-1 min-w-0 text-center rounded-lg px-1 py-1.5">
            <p class="text-[0.625rem] font-bold ${today ? 'wx-today-label' : 'text-gray-500'} truncate">${esc(today ? 'Today' : d.dow)}</p>
            <div class="text-xl leading-none my-0.5" title="${esc(d.text)}">${d.emoji}</div>
            <p class="text-[0.688rem] font-bold ${today ? 'wx-today-label' : 'text-gray-800'}">${d.max != null ? d.max + '°' : '–'}<span class="text-gray-400 font-medium">${d.min != null ? '/' + d.min + '°' : ''}</span></p>
            ${d.pop != null ? `<p class="text-[0.562rem] font-semibold text-blue-500">💧${d.pop}%</p>` : ''}
        </div>`;
    }
    function locBlock(loc) {
        if (!loc) return '';
        if (loc.ok === false) {
            return `<p class="text-[0.688rem] text-gray-400 mt-2 pt-2 border-t border-gray-100">🌦️ Weather unavailable for ${esc(loc.place || 'this location')}</p>`;
        }
        return `<div class="mt-2 pt-2 border-t border-gray-100">
            <p class="text-[0.688rem] font-semibold text-gray-500 mb-1.5 truncate">📍 ${esc(loc.place)}</p>
            <div class="flex gap-1">${loc.days.map((d, i) => dayCell(d, i === 0)).join('')}</div>
        </div>`;
    }

    async function load() {
        let data;
        try {
            const res = await fetch(@json(route('app.weather')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            data = await res.json();
        } catch (_) { slots.forEach((s) => { s.style.display = 'none'; }); return; }

        if (!data || !data.success || !data.data) { slots.forEach((s) => { s.style.display = 'none'; }); return; }
        const locations = data.data.locations || {};
        const schedules = data.data.schedules || {};

        slots.forEach((slot) => {
            const id = slot.getAttribute('data-weather-for');
            const keys = schedules[id] || [];
            if (!keys.length) { slot.style.display = 'none'; return; }
            const html = keys.map((k) => locBlock(locations[k])).join('');
            if (html) { slot.innerHTML = html; slot.classList.add('wx-ready'); }   // fades the forecast up
            else slot.style.display = 'none';
        });
    }
    load();
})();
</script>
@endpush
