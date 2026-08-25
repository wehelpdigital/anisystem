{{-- The community's ladder.

     Fifty farming ranks climbed on points, told in three tabs: who stands
     where (Rankings), what earns points and how many you have banked from
     each (Tasks), and the whole ladder with every threshold (Guide).

     Everything here is computed — see App\Support\CommunityRank — so the
     page is a reading, not a machine. The showing-off is the feature, which
     is why it moves: the board cascades in, the numbers count up, the
     progress bar fills, and a member who has climbed since their last visit
     gets told so.

     Expects: $rows, $myPoints, $myRank, $myNext, $myProgress, $myPosition,
     $breakdown, $actions, $tiers, $arcs. --}}
@extends('layouts.app')

@section('title', 'Community Ranking')
@section('body-class', 'plaza-ground')
@section('page-title', 'Community Ranking')
@section('page-subtitle', 'Ang hagdan ng bukid')
@section('back', route('community.index'))

@section('content')
@include('community.partials.plaza-css')
@include('community.partials.nav', ['active' => 'ranking'])

<div class="rk-wrap">

    {{-- ================= You, on the ladder ================= --}}
    <div class="rk-me card" id="rkMe" data-rank-n="{{ $myRank['n'] }}" data-rank-name="{{ $myRank['name'] }}">
        <div class="rk-me-badge rankb rankb-big rankb-a{{ $myRank['arc'] }}" id="rkMeBadge">
            <span class="rankb-e">{{ $myRank['emoji'] }}</span>
            <span class="rankb-t">{{ $myRank['name'] }}</span>
        </div>
        <p class="rk-me-en">{{ $myRank['en'] }} · Rank {{ $myRank['n'] }} of {{ count($tiers) }}</p>
        <p class="rk-me-pts"><b id="rkMePts" data-count="{{ $myPoints }}">0</b> <span>points</span>
            @if ($myPosition > 0)
                <span class="rk-me-pos">#{{ $myPosition }} on the board</span>
            @endif
        </p>
        @if ($myNext)
            <div class="rk-bar" role="progressbar" aria-valuemin="{{ $myRank['min'] }}" aria-valuemax="{{ $myNext['min'] }}" aria-valuenow="{{ $myPoints }}">
                <span id="rkBarFill" data-to="{{ round($myProgress * 100, 1) }}"></span>
            </div>
            <p class="rk-me-next">{{ number_format($myNext['min'] - $myPoints) }} pts to
                <b>{{ $myNext['emoji'] }} {{ $myNext['name'] }}</b></p>
        @else
            <p class="rk-me-next">🏵️ Nasa tuktok ka na — the ladder has nothing above you.</p>
        @endif
    </div>

    {{-- ================= The three tabs ================= --}}
    <div class="rk-tabs flex gap-1 p-1 rounded-xl bg-gray-100 mb-4" role="tablist" id="rkTabs">
        <button type="button" class="rk-tab is-active" data-tab="rankings" aria-selected="true">🏆 Rankings</button>
        <button type="button" class="rk-tab" data-tab="tasks" aria-selected="false">✅ Tasks</button>
        <button type="button" class="rk-tab" data-tab="guide" aria-selected="false">📖 Guide</button>
    </div>

    {{-- ---------------- Rankings ---------------- --}}
    <div data-rk-panel="rankings">
        @if (count($rows) >= 3)
            {{-- The podium: second, first, third — the shape everybody reads
                 at a glance. The blocks rise; the medals land on top. --}}
            <div class="rk-podium">
                @foreach ([1, 0, 2] as $p)
                    @php $r = $rows[$p]; @endphp
                    <div class="rk-step rk-step-{{ $p + 1 }} {{ (int) $r['user']->id === (int) auth()->id() ? 'is-me' : '' }}">
                        <span class="rk-medal">{{ ['🥇', '🥈', '🥉'][$p] }}</span>
                        @include('community.partials.avatar', ['user' => $r['user'], 'size' => 'avatar-md'])
                        <a class="rk-step-name" href="{{ route('community.connect.profile', ['userId' => $r['user']->id]) }}">{{ $r['user']->full_name }}</a>
                        <span class="rankb rankb-a{{ $r['rank']['arc'] }}"><span class="rankb-e">{{ $r['rank']['emoji'] }}</span><span class="rankb-t">{{ $r['rank']['name'] }}</span></span>
                        <b class="rk-step-pts">{{ number_format($r['points']) }}</b>
                        <span class="rk-block"></span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="rk-board card" data-animate-rows>
            @forelse ($rows as $i => $r)
                @continue(count($rows) >= 3 && $i < 3)
                <div class="rk-row {{ (int) $r['user']->id === (int) auth()->id() ? 'is-me' : '' }}" style="--i: {{ min($i, 30) }}">
                    <span class="rk-pos">{{ $i + 1 }}</span>
                    @include('community.partials.avatar', ['user' => $r['user'], 'size' => 'avatar-sm'])
                    <span class="rk-row-mid">
                        <a class="rk-row-name" href="{{ route('community.connect.profile', ['userId' => $r['user']->id]) }}">{{ $r['user']->full_name }}</a>
                        <span class="rankb rankb-a{{ $r['rank']['arc'] }}"><span class="rankb-e">{{ $r['rank']['emoji'] }}</span><span class="rankb-t">{{ $r['rank']['name'] }}</span></span>
                    </span>
                    @if ((int) $r['user']->id === (int) auth()->id())<span class="rk-you">Ikaw</span>@endif
                    <b class="rk-row-pts">{{ number_format($r['points']) }}</b>
                </div>
            @empty
                <div class="p-8 text-center">
                    <div class="empty-tile">🌱</div>
                    <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Walang nakaakyat pa</p>
                    <p class="text-sm text-gray-500 mt-1">Be the first on the board — write a post, answer a co-farmer, ask the AI Technician.</p>
                </div>
            @endforelse
        </div>
        @if (count($rows) > 0)
            <p class="rk-foot">Top {{ count($rows) }} · counted from everything you have done in the community, refreshed every few minutes.</p>
        @endif
    </div>

    {{-- ---------------- Tasks ---------------- --}}
    <div data-rk-panel="tasks" class="hidden">
        @php
            $groups = [];
            foreach ($actions as $key => $a) { $groups[$a['group']][$key] = $a; }
        @endphp
        @foreach ($groups as $groupName => $groupActions)
            <div class="rk-group card" data-animate-rows>
                <h3 class="rk-group-h">{{ $groupName }}</h3>
                @foreach ($groupActions as $key => $a)
                    @php $n = (int) ($breakdown[$key] ?? 0); @endphp
                    <div class="rk-task" style="--i: {{ $loop->parent->index * 4 + $loop->index }}">
                        <span class="rk-task-e">{{ $a['emoji'] }}</span>
                        <span class="rk-task-mid">
                            <span class="rk-task-l">{{ $a['label'] }}</span>
                            <span class="rk-task-how">{{ $a['how'] }}</span>
                        </span>
                        <span class="rk-task-pts">+{{ $a['pts'] }}</span>
                        <span class="rk-task-tally {{ $n > 0 ? 'has-some' : '' }}">
                            <b data-count="{{ $n * $a['pts'] }}">{{ number_format($n * $a['pts']) }}</b>
                            <i>{{ number_format($n) }}×</i>
                        </span>
                    </div>
                @endforeach
            </div>
        @endforeach
        <p class="rk-foot">Points land on their own — do the thing, and the ladder notices within a few minutes.</p>
    </div>

    {{-- ---------------- Guide ---------------- --}}
    <div data-rk-panel="guide" class="hidden" id="guide">
        <p class="rk-guide-intro">Fifty ranks, ten arcs — a farming life from a seed in the palm to a
            legend of the fields. Every rank is steeper than the one before it.</p>
        @foreach ($arcs as $arcN => $arcName)
            <div class="rk-arc card rk-arc-{{ $arcN }}" data-animate-rows>
                <h3 class="rk-arc-h"><span class="rk-arc-n">{{ ['I','II','III','IV','V','VI','VII','VIII','IX','X'][$arcN - 1] }}</span> {{ $arcName }}</h3>
                @foreach (array_slice($tiers, ($arcN - 1) * 5, 5) as $j => $tier)
                    @php $n = ($arcN - 1) * 5 + $j + 1; @endphp
                    <div class="rk-tier {{ $n === $myRank['n'] ? 'is-you' : ($myPoints >= $tier['min'] ? 'is-passed' : '') }}" style="--i: {{ $j }}">
                        <span class="rk-tier-n">{{ $n }}</span>
                        <span class="rk-tier-e">{{ $tier['emoji'] }}</span>
                        <span class="rk-tier-mid">
                            <b>{{ $tier['name'] }}</b>
                            <i>{{ $tier['en'] }}</i>
                        </span>
                        @if ($n === $myRank['n'])<span class="rk-you">Ikaw</span>@endif
                        <span class="rk-tier-min">{{ $tier['min'] === 0 ? 'Start' : number_format($tier['min']) . ' pts' }}</span>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('styles')
<style>
    .rk-wrap { max-width: 40rem; margin: 0 auto; }

    /* ---- You, on the ladder ---- */
    .rk-me { padding: 1.25rem 1rem 1.1rem; text-align: center; margin-bottom: 1rem;
        animation: rkRise .45s cubic-bezier(.22,1,.36,1) both; }
    .rk-me-badge { animation: rkBadgePop .55s cubic-bezier(.22,1,.36,1) .15s both; }
    @keyframes rkBadgePop { from { opacity: 0; transform: scale(.6); } 60% { transform: scale(1.12); } to { opacity: 1; transform: scale(1); } }
    .rk-me-en { font-size: .74rem; font-weight: 700; color: var(--color-gray-500); margin-top: .4rem; }
    .rk-me-pts { margin-top: .3rem; font-size: .8rem; color: var(--color-gray-500); }
    .rk-me-pts b { font-size: 1.5rem; font-weight: 800; color: var(--color-gray-900);
        font-variant-numeric: tabular-nums; font-family: var(--font-heading); }
    .rk-me-pos { display: inline-block; margin-left: .5rem; padding: .12rem .5rem; border-radius: 999px;
        background: var(--color-brand-50); color: var(--color-brand-700); font-size: .68rem; font-weight: 800; }
    .rk-bar { height: .5rem; border-radius: 999px; background: var(--color-gray-200);
        overflow: hidden; margin: .8rem auto 0; max-width: 22rem; }
    .rk-bar span { display: block; height: 100%; width: 0; border-radius: 999px;
        background: linear-gradient(90deg, #6b9f3d, #4a7c2a);
        transition: width 1.1s cubic-bezier(.22,1,.36,1) .35s; }
    .rk-me-next { margin-top: .45rem; font-size: .74rem; font-weight: 600; color: var(--color-gray-500); }
    .rk-me-next b { color: var(--color-gray-800); }
    /* The climb, celebrated: only when JS finds a higher rank than last visit. */
    .rk-me.is-up .rk-me-badge { animation: rkClimb 1s cubic-bezier(.22,1,.36,1) .2s both; }
    @keyframes rkClimb { 0% { opacity: 0; transform: translateY(1.4rem) scale(.6); }
        45% { transform: translateY(-.35rem) scale(1.18); } 65% { transform: translateY(0) scale(.96); }
        80% { transform: scale(1.05); } 100% { opacity: 1; transform: scale(1); } }

    /* ---- Tabs (the profile page's language) ---- */
    .rk-tabs { overflow-x: auto; scrollbar-width: none; }
    .rk-tabs::-webkit-scrollbar { display: none; }
    .rk-tab { flex: 1; white-space: nowrap; min-height: 2.75rem; padding: .55rem .75rem; border: 0;
        background: transparent; border-radius: .6rem; font-size: .85rem; font-weight: 700;
        color: var(--color-gray-500); cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .rk-tab.is-active { background: #fff; color: #1f2937; box-shadow: 0 1px 2px rgba(0,0,0,.08); }
    html.dark .rk-tabs { background: #1a2213; }
    html.dark .rk-tab.is-active { background: #26301c; color: #e8efe1; }
    [data-rk-panel].hidden { display: none; }
    [data-rk-panel]:not(.hidden) { animation: rkPanelIn .28s cubic-bezier(.22,1,.36,1); }
    @keyframes rkPanelIn { from { opacity: 0; transform: translateY(.4rem); } }

    /* ---- The podium ---- */
    .rk-podium { display: flex; align-items: flex-end; justify-content: center; gap: .6rem; margin-bottom: 1rem; }
    .rk-step { display: flex; flex-direction: column; align-items: center; gap: .3rem; min-width: 0;
        flex: 1 1 0; max-width: 11rem; text-align: center; }
    .rk-medal { font-size: 1.5rem; line-height: 1; animation: rkMedal .6s cubic-bezier(.22,1,.36,1) both; }
    .rk-step-1 .rk-medal { animation-delay: .55s; } .rk-step-2 .rk-medal { animation-delay: .4s; } .rk-step-3 .rk-medal { animation-delay: .7s; }
    @keyframes rkMedal { from { opacity: 0; transform: translateY(-1rem) scale(.5); } 70% { transform: translateY(.1rem) scale(1.15); } to { opacity: 1; transform: none; } }
    .rk-step-name { font-size: .78rem; font-weight: 800; color: var(--color-gray-900); max-width: 100%;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rk-step-name:hover { color: var(--color-brand-700); }
    .rk-step-pts { font-size: .82rem; font-variant-numeric: tabular-nums; color: var(--color-gray-700); }
    .rk-block { width: 100%; border-radius: .6rem .6rem 0 0; transform-origin: bottom;
        background: linear-gradient(180deg, #a9d383, #6b9f3d); animation: rkBlock .55s cubic-bezier(.22,1,.36,1) both; }
    .rk-step-1 .rk-block { height: 4.2rem; animation-delay: .35s; background: linear-gradient(180deg, #f7d878, #d9a92f); }
    .rk-step-2 .rk-block { height: 2.9rem; animation-delay: .2s; background: linear-gradient(180deg, #d7dee2, #a9b4bb); }
    .rk-step-3 .rk-block { height: 2.1rem; animation-delay: .5s; background: linear-gradient(180deg, #ecc99f, #c78d4e); }
    @keyframes rkBlock { from { transform: scaleY(0); } }
    .rk-step.is-me .rk-step-name::after { content: ' · Ikaw'; color: var(--color-brand-700); }

    /* ---- The board ---- */
    .rk-board { padding: .35rem; margin-bottom: .5rem; }
    .rk-row { display: flex; align-items: center; gap: .6rem; padding: .5rem .6rem; border-radius: .7rem; }
    [data-animate-rows] .rk-row, [data-animate-rows] .rk-task, [data-animate-rows] .rk-tier {
        animation: rkRise .4s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i, 0) * 45ms); }
    @keyframes rkRise { from { opacity: 0; transform: translateY(.55rem); } }
    .rk-row:nth-child(even) { background: color-mix(in srgb, var(--color-gray-100) 55%, transparent); }
    .rk-row.is-me { background: var(--color-brand-50); box-shadow: inset 0 0 0 1.5px var(--color-brand-300); }
    .rk-pos { flex: none; width: 1.9rem; text-align: center; font-size: .78rem; font-weight: 800;
        color: var(--color-gray-400); font-variant-numeric: tabular-nums; }
    .rk-row-mid { display: flex; align-items: center; gap: .45rem; min-width: 0; flex: 1 1 auto; flex-wrap: wrap; row-gap: .15rem; }
    .rk-row-name { font-size: .85rem; font-weight: 700; color: var(--color-gray-900);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; }
    .rk-row-name:hover { color: var(--color-brand-700); }
    .rk-you { flex: none; padding: .1rem .45rem; border-radius: 999px; background: var(--color-brand-600);
        color: #fff; font-size: .62rem; font-weight: 800; }
    .rk-row-pts { flex: none; font-size: .85rem; font-weight: 800; color: var(--color-gray-800);
        font-variant-numeric: tabular-nums; }
    .rk-foot { font-size: .72rem; font-weight: 600; color: var(--color-gray-400); text-align: center; margin: .75rem 0 1rem; }

    /* ---- Tasks ---- */
    .rk-group { padding: .8rem .85rem .5rem; margin-bottom: .85rem; }
    .rk-group-h { font-size: .72rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase;
        color: var(--color-gray-400); margin-bottom: .35rem; }
    .rk-task { display: flex; align-items: center; gap: .6rem; padding: .5rem 0; }
    .rk-task + .rk-task { border-top: 1px solid var(--color-gray-100); }
    .rk-task-e { flex: none; width: 2rem; height: 2rem; border-radius: .6rem; display: inline-flex;
        align-items: center; justify-content: center; background: var(--color-gray-100); font-size: 1rem; }
    .rk-task-mid { min-width: 0; flex: 1 1 auto; }
    .rk-task-l { display: block; font-size: .82rem; font-weight: 700; color: var(--color-gray-900); }
    .rk-task-how { display: block; font-size: .7rem; color: var(--color-gray-500); line-height: 1.4; margin-top: .1rem; }
    .rk-task-pts { flex: none; padding: .12rem .45rem; border-radius: 999px; background: var(--color-brand-50);
        color: var(--color-brand-700); font-size: .68rem; font-weight: 800; }
    .rk-task-tally { flex: none; text-align: right; min-width: 3.4rem; }
    .rk-task-tally b { display: block; font-size: .85rem; font-weight: 800; color: var(--color-gray-300);
        font-variant-numeric: tabular-nums; }
    .rk-task-tally.has-some b { color: var(--color-gray-900); }
    .rk-task-tally i { font-style: normal; font-size: .64rem; color: var(--color-gray-400); }

    /* ---- Guide ---- */
    .rk-guide-intro { font-size: .82rem; color: var(--color-gray-600); line-height: 1.55; margin-bottom: .9rem; }
    .rk-arc { padding: .8rem .85rem .5rem; margin-bottom: .85rem; }
    .rk-arc-h { font-size: .8rem; font-weight: 800; color: var(--color-gray-800); margin-bottom: .35rem;
        display: flex; align-items: center; gap: .5rem; }
    .rk-arc-n { flex: none; width: 1.7rem; height: 1.7rem; border-radius: .5rem; display: inline-flex;
        align-items: center; justify-content: center; background: var(--color-gray-100);
        font-size: .72rem; color: var(--color-gray-500); }
    .rk-arc-10 .rk-arc-n { background: #fdf3d8; color: #8a6100; }
    .rk-tier { display: flex; align-items: center; gap: .55rem; padding: .45rem .35rem; border-radius: .6rem; }
    .rk-tier + .rk-tier { border-top: 1px solid var(--color-gray-100); }
    .rk-tier-n { flex: none; width: 1.6rem; text-align: center; font-size: .72rem; font-weight: 800;
        color: var(--color-gray-400); font-variant-numeric: tabular-nums; }
    .rk-tier-e { flex: none; font-size: 1.05rem; }
    .rk-tier-mid { min-width: 0; flex: 1 1 auto; }
    .rk-tier-mid b { display: block; font-size: .82rem; font-weight: 800; color: var(--color-gray-900); }
    .rk-tier-mid i { font-style: normal; font-size: .68rem; color: var(--color-gray-500); }
    .rk-tier-min { flex: none; font-size: .72rem; font-weight: 700; color: var(--color-gray-500);
        font-variant-numeric: tabular-nums; }
    .rk-tier.is-passed .rk-tier-min::after { content: ' ✓'; color: var(--color-brand-600); }
    .rk-tier.is-you { background: var(--color-brand-50); box-shadow: inset 0 0 0 1.5px var(--color-brand-300); border-top-color: transparent; }
    .rk-tier.is-you + .rk-tier { border-top-color: transparent; }
    .rk-tier.is-you .rk-you { animation: rkPulse 2.4s ease-in-out infinite; }
    @keyframes rkPulse { 0%, 100% { box-shadow: 0 0 0 0 rgb(107 159 61 / .45); } 50% { box-shadow: 0 0 0 .45rem rgb(107 159 61 / 0); } }

    html.dark .rk-row:nth-child(even) { background: rgb(255 255 255 / .03); }
    html.dark .rk-row.is-me, html.dark .rk-tier.is-you { background: rgb(61 104 35 / .25); }
    html.dark .rk-task-e, html.dark .rk-arc-n { background: rgb(255 255 255 / .07); }
    html.dark .rk-task + .rk-task, html.dark .rk-tier + .rk-tier { border-top-color: rgb(255 255 255 / .06); }
    html.dark .rk-task-tally b { color: #e8efe1; }
    html.dark .rk-task-tally:not(.has-some) b { color: #4a5540; }

    @media (prefers-reduced-motion: reduce) {
        .rk-me, .rk-me-badge, .rk-me.is-up .rk-me-badge, .rk-medal, .rk-block,
        [data-animate-rows] .rk-row, [data-animate-rows] .rk-task, [data-animate-rows] .rk-tier,
        [data-rk-panel]:not(.hidden), .rk-tier.is-you .rk-you { animation: none; }
        .rk-bar span { transition: none; }
    }
</style>
@endpush

@push('scripts')
<script>
/* The ladder moves so the standing feels earned: numbers count up, the bar
 * fills to where you actually are, the board cascades in, and a rank climbed
 * since the last visit gets its moment. All of it honours reduced motion by
 * skipping straight to the finished state. */
document.addEventListener('DOMContentLoaded', () => {
    const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---- numbers that count up ---- */
    function countUp(el) {
        const to = parseInt(el.getAttribute('data-count') || '0', 10);
        if (still || to <= 0) { el.textContent = to.toLocaleString(); return; }
        const t0 = performance.now();
        const dur = Math.min(1200, 500 + to);
        const tick = (t) => {
            const p = Math.min(1, (t - t0) / dur);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(to * eased).toLocaleString();
            if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    }
    countUp(document.getElementById('rkMePts'));

    /* ---- the bar fills after the page has painted its empty state ---- */
    const fill = document.getElementById('rkBarFill');
    if (fill) requestAnimationFrame(() => { fill.style.width = fill.getAttribute('data-to') + '%'; });

    /* ---- the climb, noticed ----
     * The rank you last SAW is this browser's memory; a higher one now is
     * news worth a bounce and a word. Written back either way, so the moment
     * happens once. */
    const me = document.getElementById('rkMe');
    try {
        const now = parseInt(me.getAttribute('data-rank-n') || '1', 10);
        const seen = parseInt(localStorage.getItem('as-rank-seen') || '0', 10);
        if (seen && now > seen && !still) {
            me.classList.add('is-up');
            window.smToast?.('Umakyat ka! You are now ' + me.getAttribute('data-rank-name') + ' 🎉');
        }
        localStorage.setItem('as-rank-seen', String(now));
    } catch (_) { /* no storage, no ceremony */ }

    /* ---- tabs, hash-linked so a badge can send somebody to #guide ---- */
    const show = (tab) => {
        document.querySelectorAll('#rkTabs .rk-tab').forEach((b) => {
            const on = b.getAttribute('data-tab') === tab;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        document.querySelectorAll('[data-rk-panel]').forEach((p) => {
            p.classList.toggle('hidden', p.getAttribute('data-rk-panel') !== tab);
        });
        // The tasks tab's tallies count up the first time it is seen.
        if (tab === 'tasks' && !show.counted) {
            show.counted = true;
            document.querySelectorAll('[data-rk-panel="tasks"] [data-count]').forEach(countUp);
        }
    };
    document.getElementById('rkTabs')?.addEventListener('click', (e) => {
        const btn = e.target.closest('.rk-tab');
        if (!btn) return;
        const tab = btn.getAttribute('data-tab');
        show(tab);
        history.replaceState(null, '', '#' + tab);
    });
    const want = (location.hash || '').replace('#', '');
    if (['rankings', 'tasks', 'guide'].includes(want)) show(want);

    /* ---- inside the guide, start the reader at their own rank ---- */
    if (want === 'guide') {
        setTimeout(() => document.querySelector('.rk-tier.is-you')?.scrollIntoView({ block: 'center', behavior: still ? 'auto' : 'smooth' }), 350);
    }
});
</script>
@endpush
