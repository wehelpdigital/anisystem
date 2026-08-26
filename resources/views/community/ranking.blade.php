{{-- The community's ladder.

     A hundred levels climbed on points, a new title every tenth, told in
     three tabs: who stands
     where (Rankings), what earns points and how many you have banked from
     each (Tasks), and the whole ladder with every threshold (Guide).

     Everything here is computed — see App\Support\CommunityRank — so the
     page is a reading, not a machine. The showing-off is the feature, which
     is why it moves: the board cascades in, the numbers count up, the
     progress bar fills, and a member who has climbed since their last visit
     gets told so.

     Expects: $rows, $myPoints, $myRank, $myNext, $myNextTitle, $myProgress,
     $myPosition, $breakdown, $actions, $titles, $levels, $maxLevel. --}}
@extends('layouts.app')

@section('title', 'Community Rankings')
@section('body-class', 'plaza-ground rk-full')
@section('page-title', 'Community Rankings')
@section('page-subtitle', 'Ang hagdan ng bukid')
@section('back', route('community.index'))

@section('content')
@include('community.partials.plaza-css')
@include('community.partials.nav', ['active' => 'ranking'])

<div class="rk-wrap">

    {{-- ================= You, on the ladder ================= --}}
    {{-- The face first, the pill it earned under it, the name under that:
         a member's own plate, on a slowly breathing gradient with its own
         edge (see .rk-me). --}}
    <div class="rk-me card" id="rkMe" data-rank-n="{{ $myRank['n'] }}" data-rank-name="{{ $myRank['name'] }}">
        <div class="rk-me-face">
            @include('community.partials.avatar', ['user' => $me, 'size' => 'avatar-lg', 'link' => false])
        </div>
        <div class="rk-me-badge rankb rankb-big rankb-a{{ $myRank['arc'] }}" id="rkMeBadge">
            <span class="rankb-e">{{ $myRank['emoji'] }}</span>
            <span class="rankb-lv">Lv {{ $myRank['n'] }}</span>
            <span class="rankb-t">{{ $myRank['name'] }}</span>
        </div>
        <p class="rk-me-name">{{ $me->full_name }}</p>
        <p class="rk-me-en">Level {{ $myRank['n'] }} of {{ $maxLevel }}</p>
        <p class="rk-me-pts"><b id="rkMePts" data-count="{{ $myPoints }}">0</b> <span>points</span>
            @if ($myPosition > 0)
                <span class="rk-me-pos">Rank: {{ $myPosition }}</span>
            @endif
        </p>
        @if ($capped)
            {{-- The bar has stopped for a reason, and the reason is said. --}}
            <p class="rk-me-gate">🔒 You are at the free summit — Level {{ $freeCap }}.
                <a href="{{ url('/account/subscription') }}">Subscribe</a> to keep climbing; everything you do keeps counting the moment you do.</p>
        @elseif ($myNext)
            <div class="rk-bar" role="progressbar" aria-valuemin="{{ $myRank['min'] }}" aria-valuemax="{{ $myNext['min'] }}" aria-valuenow="{{ $myPoints }}">
                <span id="rkBarFill" data-to="{{ round($myProgress * 100, 1) }}"></span>
            </div>
            <p class="rk-me-next">{{ number_format($myNext['min'] - $myPoints) }} pts to <b>Level {{ $myNext['n'] }}</b></p>
            @if ($myNextTitle)
                <p class="rk-me-title">Next title: <b>{{ $myNextTitle['emoji'] }} {{ $myNextTitle['name'] }}</b> at Level {{ $myNextTitle['n'] }} ({{ number_format($myNextTitle['min']) }} pts)</p>
            @endif
        @else
            <p class="rk-me-next">🐉 Nasa tuktok ka na — the ladder has nothing above you.</p>
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
                        <span class="rankb rankb-a{{ $r['rank']['arc'] }}"><span class="rankb-e">{{ $r['rank']['emoji'] }}</span><span class="rankb-lv">Lv {{ $r['rank']['n'] }}</span><span class="rankb-t">{{ $r['rank']['name'] }}</span></span>
                        <b class="rk-step-pts">{{ number_format($r['points']) }}</b>
                        <span class="rk-block"></span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="rk-board card" data-animate-rows>
            @forelse ($rows as $i => $r)
                @continue(count($rows) >= 3 && $i < 3)
                @continue($i >= 15)
                @php $ru = $r['user']; @endphp
                {{-- One line per climber: rank, face, name, level, points —
                     the board is a scoreboard, not a directory. Rows 13-15
                     fade toward the dots below, saying "it keeps going". --}}
                <div class="rk-row rk-fade-{{ max(0, $i - 11) }} {{ (int) $ru->id === (int) auth()->id() ? 'is-me' : '' }}" style="--i: {{ min($i, 30) }}">
                    <span class="rk-pos">Rank: {{ $i + 1 }}</span>
                    @include('community.partials.avatar', ['user' => $ru, 'size' => 'avatar-sm'])
                    <span class="rk-row-mid">
                        <span class="rk-row-head">
                            <a class="rk-row-name" href="{{ route('community.connect.profile', ['userId' => $ru->id]) }}">{{ $ru->full_name }}</a>
                            <span class="rankb rankb-a{{ $r['rank']['arc'] }}"><span class="rankb-e">{{ $r['rank']['emoji'] }}</span><span class="rankb-lv">Lv {{ $r['rank']['n'] }}</span><span class="rankb-t">{{ $r['rank']['name'] }}</span></span>
                        </span>
                    </span>
                    @if ((int) $ru->id === (int) auth()->id())<span class="rk-you">Ikaw</span>@endif
                    <b class="rk-row-pts">{{ number_format($r['points']) }}</b>
                </div>
            @empty
                <div class="p-8 text-center">
                    <div class="empty-tile">🌱</div>
                    <p class="font-bold text-gray-900" style="font-family:var(--font-heading)">Walang nakaakyat pa</p>
                    <p class="text-sm text-gray-500 mt-1">Be the first on the board — write a post, answer a co-farmer, ask the AI Technician.</p>
                </div>
            @endforelse
            {{-- Standing below the fold: three dots walk down to where YOU
                 are, so the board always ends with your own line. --}}
            @if ($myPosition > 15 || $myPosition <= 0)
                <div class="rk-dots" aria-hidden="true"><i></i><i></i><i></i></div>
                <div class="rk-row is-me rk-merow">
                    <span class="rk-pos">Rank: {{ $myPosition > 0 ? $myPosition : '—' }}</span>
                    @include('community.partials.avatar', ['user' => $me, 'size' => 'avatar-sm', 'link' => false])
                    <span class="rk-row-mid">
                        <span class="rk-row-head">
                            <span class="rk-row-name">{{ $me->full_name }}</span>
                            <span class="rankb rankb-a{{ $myRank['arc'] }}"><span class="rankb-e">{{ $myRank['emoji'] }}</span><span class="rankb-lv">Lv {{ $myRank['n'] }}</span><span class="rankb-t">{{ $myRank['name'] }}</span></span>
                        </span>
                    </span>
                    <span class="rk-you">Ikaw</span>
                    <b class="rk-row-pts">{{ number_format($myPoints) }}</b>
                </div>
            @endif
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
                    <div class="rk-task" style="--i: {{ $loop->parent->index * 4 + $loop->index }}">
                        <span class="rk-task-e">{{ $a['emoji'] }}</span>
                        <span class="rk-task-mid">
                            <span class="rk-task-l">{{ $a['label'] }}</span>
                            <span class="rk-task-how">{{ $a['how'] }}</span>
                        </span>
                        {{-- Just what the task pays — the running arithmetic
                             lives on the Rankings tab, not here. --}}
                        <span class="rk-task-pts">+{{ $a['pts'] }} pts</span>
                    </div>
                @endforeach
            </div>
        @endforeach
        <p class="rk-foot">Points land on their own — do the thing, and the ladder notices within a few minutes.</p>
    </div>

    {{-- ---------------- Guide ---------------- --}}
    <div data-rk-panel="guide" class="hidden" id="guide">
        {{-- The endpoints come from the ladder itself, so a renamed title can
             never leave this sentence telling an old story. --}}
        <p class="rk-guide-intro">A hundred levels, ten titles — every tenth level hands you a new name
            to wear, from {{ $titles[1]['name'] }} to {{ $titles[10]['name'] }}. Each level costs more than the one before it.</p>
        {{-- The wrapped boxes on the ladder: nobody is told what is inside,
             only that there is one waiting — that is the whole point of a
             mystery. The gift wiggles (see .rk-gift) so the eye finds it. --}}
        <p class="rk-guide-gift"><span class="rk-gift" aria-hidden="true">🎁</span>
            A <b>mystery prize</b> waits at <b>Level 40</b> — and more at <b>Level 50</b>, <b>60</b>, <b>80</b> and <b>100</b>. What is inside is only found out by the one who gets there.</p>
        @unless ($unlocked)
            <p class="rk-guide-gate">🔒 The free road ends at <b>Level {{ $freeCap }}</b>.
                <a href="{{ url('/account/subscription') }}">Subscribe</a> to climb past it — every point you keep earning starts counting again the moment you do.</p>
        @endunless
        @foreach ($titles as $arcN => $title)
            @php
                $lo = ($arcN - 1) * 10 + 1;
                $hi = $arcN * 10;
                $mine = $myRank['arc'] === $arcN;
            @endphp
            <div class="rk-arc card rk-arc-{{ $arcN }} {{ $mine ? 'is-mine' : '' }}" data-animate-rows>
                <h3 class="rk-arc-h">
                    <span class="rk-arc-e">{{ $title['emoji'] }}</span>
                    <span class="rk-arc-mid">
                        <b>{{ $title['name'] }}</b>
                        <i>Levels {{ $lo }}–{{ $hi }} · from {{ $levels[$lo - 1] === 0 ? 'the start' : number_format($levels[$lo - 1]) . ' pts' }}</i>
                    </span>
                    @if ($mine)<span class="rk-you">Ikaw</span>@endif
                </h3>
                <div class="rk-lvls">
                    @for ($n = $lo; $n <= $hi; $n++)
                        @php $gifted = in_array($n, [40, 50, 60, 80, 100], true); @endphp
                        <span class="rk-lvl {{ $n === $myRank['n'] ? 'is-you' : ($myPoints >= $levels[$n - 1] ? 'is-passed' : '') }} {{ $gifted ? 'has-gift' : '' }}" style="--i: {{ $n - $lo }}">
                            @if ($gifted)<span class="rk-gift" title="A mystery prize waits here" aria-label="Mystery prize">🎁</span>@endif
                            <b>Lv {{ $n }}</b>
                            <i>{{ $levels[$n - 1] === 0 ? 'Start' : number_format($levels[$n - 1]) }}</i>
                        </span>
                    @endfor
                </div>
            </div>
            @if ($arcN === 5)
                {{-- The Level 50 gift, named: halfway up the whole ladder,
                     the house itself signs your name. --}}
                <div class="card rk-cert" data-animate-rows>
                    <div class="rk-cert-seal" aria-hidden="true">
                        <img src="{{ asset('images/logo.png') }}" alt="">
                    </div>
                    <div class="rk-cert-txt">
                        <i class="rk-cert-kicker">The Level 50 prize</i>
                        <b>Harvest Hero Certification</b>
                        <span>Reach Level 50 and AniSenso certifies you a <b>Harvest Hero</b> — an official certification carrying the AniSenso seal, with your name on it, yours to show wherever farmers gather.</span>
                    </div>
                    <span class="rk-gift rk-cert-gift" aria-hidden="true">🎁</span>
                </div>
            @endif
        @endforeach
    </div>
</div>
@endsection

@push('styles')
<style>
    .rk-wrap { max-width: 40rem; margin: 0 auto; }

    /* The ladder is a destination, not a hallway: the bottom tab bar steps
       away so the whole screen is the board. The back arrow up top is the
       way home. */
    body.rk-full .tabbar { display: none; }
    body.rk-full { padding-bottom: 0; }
    body.rk-full main { padding-bottom: 1.5rem; }

    /* ---- You, on the ladder ---- */
    .rk-me { padding: 1.35rem 1rem 1.2rem; text-align: center; margin-bottom: 1rem;
        animation: rkRise .45s cubic-bezier(.22,1,.36,1) both;
        position: relative; overflow: hidden;
        border: 1.5px solid rgb(107 159 61 / .35);
        background: linear-gradient(115deg, #f4faee, #e2f0d4 30%, #f7fbf2 55%, #dcecca 80%, #eef6e4);
        background-size: 260% 260%;
        animation: rkRise .45s cubic-bezier(.22,1,.36,1) both, rkBreathe 9s ease-in-out infinite; }
    @keyframes rkBreathe { 0%, 100% { background-position: 0% 40%; } 50% { background-position: 100% 60%; } }
    html.dark .rk-me { border-color: rgb(143 194 103 / .3);
        background: linear-gradient(115deg, #1c2415, #243019 30%, #1a2113 55%, #2a381e 80%, #202a17);
        background-size: 260% 260%; }
    .rk-me-face { display: flex; justify-content: center; margin-bottom: .6rem;
        animation: rkBadgePop .55s cubic-bezier(.22,1,.36,1) .05s both; }
    .rk-me-face .avatar { box-shadow: 0 8px 26px rgb(40 70 15 / .3), 0 0 0 3px #fff; }
    html.dark .rk-me-face .avatar { box-shadow: 0 8px 26px rgb(0 0 0 / .5), 0 0 0 3px rgb(255 255 255 / .14); }
    .rk-me-name { margin-top: .45rem; font-size: 1rem; font-weight: 800; color: var(--color-gray-900);
        font-family: var(--font-heading); }
    .rk-me-gate { margin: .8rem auto 0; max-width: 24rem; font-size: .76rem; font-weight: 600;
        color: var(--color-gray-600); background: rgb(255 255 255 / .65); border: 1px dashed rgb(107 159 61 / .5);
        border-radius: .8rem; padding: .55rem .8rem; line-height: 1.5; }
    .rk-me-gate a { color: var(--color-brand-700); font-weight: 800; text-decoration: underline; }
    html.dark .rk-me-gate { background: rgb(0 0 0 / .25); color: #cdd8c2; }
    html.dark .rk-me-gate a { color: #a3d284; }
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
    /* The fill is a moving gradient, not a flat green: the ladder's bars all
       breathe the same sweep (rkFlow), so progress reads as something alive. */
    .rk-bar span { display: block; height: 100%; width: 0; border-radius: 999px;
        background: linear-gradient(90deg, #6b9f3d, #a9d383, #4a7c2a, #8fc267, #6b9f3d);
        background-size: 300% 100%;
        animation: rkFlow 3.2s linear infinite;
        transition: width 1.1s cubic-bezier(.22,1,.36,1) .35s; }
    @keyframes rkFlow { from { background-position: 0% 0; } to { background-position: 300% 0; } }
    .rk-me-next { margin-top: .45rem; font-size: .74rem; font-weight: 600; color: var(--color-gray-500); }
    .rk-me-next b { color: var(--color-gray-800); }
    .rk-me-title { margin-top: .2rem; font-size: .7rem; font-weight: 600; color: var(--color-gray-400); }
    .rk-me-title b { color: var(--color-gray-600); }
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
    /* The podium in the house's own greens — deepest for first, lighter
       down the steps — each block a living gradient on the shared sweep. */
    .rk-block { width: 100%; border-radius: .6rem .6rem 0 0; transform-origin: bottom;
        background-size: 100% 260%;
        animation: rkBlock .55s cubic-bezier(.22,1,.36,1) both, rkBlockFlow 7s ease-in-out infinite alternate; }
    .rk-step-1 .rk-block { height: 4.2rem; animation-delay: .35s, 0s;
        background-image: linear-gradient(180deg, #a9d383, #4a7c2a 45%, #2f5219 80%, #6b9f3d); }
    .rk-step-2 .rk-block { height: 2.9rem; animation-delay: .2s, .8s;
        background-image: linear-gradient(180deg, #c4e0a5, #6b9f3d 45%, #4a7c2a 80%, #8fc267); }
    .rk-step-3 .rk-block { height: 2.1rem; animation-delay: .5s, 1.6s;
        background-image: linear-gradient(180deg, #ddedc8, #8fc267 45%, #6b9f3d 80%, #a9d383); }
    @keyframes rkBlock { from { transform: scaleY(0); } }
    @keyframes rkBlockFlow { from { background-position: 0% 0%; } to { background-position: 0% 100%; } }
    .rk-step.is-me .rk-step-name::after { content: ' · Ikaw'; color: var(--color-brand-700); }

    /* ---- The board ---- */
    .rk-board { padding: .35rem; margin-bottom: .5rem; }
    .rk-row { display: flex; align-items: center; gap: .6rem; padding: .5rem .6rem; border-radius: .7rem; }
    [data-animate-rows] .rk-row, [data-animate-rows] .rk-task {
        animation: rkRise .4s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i, 0) * 45ms); }
    @keyframes rkRise { from { opacity: 0; transform: translateY(.55rem); } }
    .rk-row:nth-child(even) { background: color-mix(in srgb, var(--color-gray-100) 55%, transparent); }
    .rk-row.is-me { background: var(--color-brand-50); box-shadow: inset 0 0 0 1.5px var(--color-brand-300); }
    .rk-pos { flex: none; min-width: 3.4rem; text-align: center; font-size: .66rem; font-weight: 800;
        color: var(--color-gray-400); font-variant-numeric: tabular-nums;
        text-transform: uppercase; letter-spacing: .02em; }
    .rk-row-mid { display: flex; flex-direction: column; gap: .15rem; min-width: 0; flex: 1 1 auto; }
    .rk-row-head { display: flex; align-items: center; gap: .45rem; min-width: 0; flex-wrap: wrap; row-gap: .15rem; }
    /* The board trails off: its last rows fade toward the dots that walk
       down to your own line. */
    .rk-row.rk-fade-1 { opacity: .7; }
    .rk-row.rk-fade-2 { opacity: .45; }
    .rk-row.rk-fade-3 { opacity: .22; }
    .rk-dots { display: flex; flex-direction: column; align-items: center; gap: .3rem; padding: .5rem 0 .4rem; }
    .rk-dots i { width: .35rem; height: .35rem; border-radius: 999px; background: var(--color-brand-400);
        animation: rkDotWalk 1.6s ease-in-out infinite; }
    .rk-dots i:nth-child(2) { animation-delay: .25s; }
    .rk-dots i:nth-child(3) { animation-delay: .5s; }
    @keyframes rkDotWalk { 0%, 100% { transform: translateY(0); opacity: .35; }
        50% { transform: translateY(.3rem); opacity: 1; } }
    .rk-merow { border: 1.5px dashed var(--color-brand-300); }
    @media (prefers-reduced-motion: reduce) {
        .rk-dots i { animation: none; opacity: .7; }
        .rk-block { animation: rkBlock .01s both; }
    }
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
    /* One question per row — do this, earn that — striped like a ledger:
       every second row wears a wash of the house's dark green, so the eye
       walks the table without losing its line. */
    .rk-task { display: flex; align-items: center; gap: .65rem; padding: .6rem .6rem;
        border-radius: .7rem; }
    .rk-task:nth-of-type(even) { background: rgb(47 82 25 / .08); }
    .rk-task-e { flex: none; width: 2.1rem; height: 2.1rem; border-radius: .65rem; display: inline-flex;
        align-items: center; justify-content: center; background: var(--color-gray-100); font-size: 1rem; }
    .rk-task:nth-of-type(even) .rk-task-e { background: rgb(255 255 255 / .75); }
    .rk-task-mid { min-width: 0; flex: 1 1 auto; }
    .rk-task-l { display: block; font-size: .82rem; font-weight: 700; color: var(--color-gray-900); }
    .rk-task-how { display: block; font-size: .7rem; color: var(--color-gray-500); line-height: 1.4; margin-top: .1rem; }
    /* The one number the row exists to say, worn as a solid dark-green pill. */
    .rk-task-pts { flex: none; padding: .3rem .65rem; border-radius: 999px; color: #fff;
        background: linear-gradient(120deg, #2f5219, #4a7c2a 60%, #3d6823);
        font-size: .72rem; font-weight: 800; white-space: nowrap;
        box-shadow: 0 2px 6px rgb(47 82 25 / .3); font-variant-numeric: tabular-nums; }
    html.dark .rk-task:nth-of-type(even) { background: rgb(143 194 103 / .08); }
    html.dark .rk-task:nth-of-type(even) .rk-task-e { background: rgb(0 0 0 / .3); }

    /* ---- Guide ---- */
    .rk-guide-intro { font-size: .82rem; color: var(--color-gray-600); line-height: 1.55; margin-bottom: .9rem; }
    /* The wrapped box: it wiggles now and then and throws a soft shine, so
       the eye finds it without a single word of chrome. */
    .rk-gift { display: inline-block; font-size: 1.05rem; line-height: 1;
        animation: rkGift 2.8s cubic-bezier(.22,1,.36,1) infinite;
        filter: drop-shadow(0 0 6px rgb(217 169 47 / .55)); }
    @keyframes rkGift {
        0%, 62%, 100% { transform: rotate(0) scale(1); }
        66% { transform: rotate(-12deg) scale(1.18); }
        70% { transform: rotate(10deg) scale(1.18); }
        74% { transform: rotate(-7deg) scale(1.12); }
        78% { transform: rotate(4deg) scale(1.06); }
        82% { transform: rotate(0) scale(1); } }
    .rk-guide-gift { display: block; font-size: .8rem; line-height: 1.55;
        color: var(--color-gray-700); background: linear-gradient(115deg, #fdf6e0, #faedc4);
        border: 1px solid rgb(217 169 47 / .4); border-radius: .8rem; padding: .65rem .8rem; margin-bottom: .9rem; }
    .rk-guide-gift .rk-gift { font-size: 1.3rem; margin-right: .45rem; vertical-align: -.25rem; }
    html.dark .rk-guide-gift { background: linear-gradient(115deg, #2a2410, #332b12); color: #e3d9b8;
        border-color: rgb(217 169 47 / .3); }
    .rk-guide-gate { font-size: .78rem; line-height: 1.55; color: var(--color-gray-600);
        border: 1px dashed rgb(107 159 61 / .5); border-radius: .8rem; padding: .6rem .8rem; margin-bottom: .9rem; }
    .rk-guide-gate a { color: var(--color-brand-700); font-weight: 800; text-decoration: underline; }
    html.dark .rk-guide-gate { color: #cdd8c2; }
    html.dark .rk-guide-gate a { color: #a3d284; }
    /* The certificate card: the seal, the name of the prize, and the words
       — on a quiet parchment-toned gradient with the house's gold edge. */
    .rk-cert { display: flex; align-items: center; gap: .9rem; padding: 1rem;
        margin: -.35rem 0 .85rem; position: relative; overflow: hidden;
        border: 1.5px solid rgb(217 169 47 / .45);
        background: linear-gradient(115deg, #fdfaf0, #faf3d9 45%, #fdf9ec 75%, #f7eecb);
        background-size: 220% 220%; animation: rkBreathe 10s ease-in-out infinite; }
    html.dark .rk-cert { border-color: rgb(217 169 47 / .3);
        background: linear-gradient(115deg, #2a2410, #332b12 45%, #292312 75%, #362d13);
        background-size: 220% 220%; }
    .rk-cert-seal { flex: none; width: 3.4rem; height: 3.4rem; border-radius: 999px;
        background: #fff; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 14px rgb(160 120 20 / .3), 0 0 0 2.5px rgb(217 169 47 / .5); }
    .rk-cert-seal img { width: 2.5rem; height: auto; }
    .rk-cert-txt { min-width: 0; flex: 1 1 auto; }
    .rk-cert-kicker { display: block; font-style: normal; font-size: .64rem; font-weight: 800;
        letter-spacing: .08em; text-transform: uppercase; color: #b8860b; }
    .rk-cert-txt > b { display: block; font-family: var(--font-heading); font-size: 1rem;
        font-weight: 800; color: var(--color-gray-900); margin-top: .15rem; }
    .rk-cert-txt > span { display: block; font-size: .78rem; color: var(--color-gray-600);
        line-height: 1.55; margin-top: .3rem; }
    html.dark .rk-cert-txt > span { color: #d8ceac; }
    .rk-cert-gift { position: absolute; top: .6rem; right: .7rem; font-size: 1.2rem; }

    .rk-lvl { position: relative; }
    .rk-lvl.has-gift { box-shadow: inset 0 0 0 1.5px rgb(217 169 47 / .55); background: #fdf6e0; }
    html.dark .rk-lvl.has-gift { background: #2a2410; }
    .rk-lvl.has-gift .rk-gift { position: absolute; top: -.55rem; right: -.3rem; font-size: .95rem; }
    .rk-arc { padding: .8rem .85rem .85rem; margin-bottom: .85rem; }
    .rk-arc.is-mine { box-shadow: inset 0 0 0 1.5px var(--color-brand-300), var(--shadow-card); }
    .rk-arc-h { display: flex; align-items: center; gap: .6rem; margin-bottom: .55rem; }
    .rk-arc-e { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .65rem; display: inline-flex;
        align-items: center; justify-content: center; background: var(--color-gray-100); font-size: 1.15rem; }
    .rk-arc-10 .rk-arc-e { background: #fdf3d8; }
    .rk-arc-mid { min-width: 0; flex: 1 1 auto; }
    .rk-arc-mid b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900);
        font-family: var(--font-heading); }
    .rk-arc-mid i { font-style: normal; font-size: .7rem; font-weight: 600; color: var(--color-gray-500); }
    /* Ten levels to a title, five to a row: the decade reads as one card. */
    .rk-lvls { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .35rem; }
    .rk-lvl { display: flex; flex-direction: column; align-items: center; padding: .35rem .1rem;
        border-radius: .55rem; background: color-mix(in srgb, var(--color-gray-100) 65%, transparent); }
    [data-animate-rows] .rk-lvl { animation: rkRise .4s cubic-bezier(.22,1,.36,1) both;
        animation-delay: calc(var(--i, 0) * 35ms); }
    .rk-lvl b { font-size: .7rem; font-weight: 800; color: var(--color-gray-700); font-variant-numeric: tabular-nums; }
    .rk-lvl i { font-style: normal; font-size: .62rem; color: var(--color-gray-400); font-variant-numeric: tabular-nums; }
    .rk-lvl.is-passed { background: var(--color-brand-50); }
    .rk-lvl.is-passed b { color: var(--color-brand-700); }
    .rk-lvl.is-passed i::after { content: ' ✓'; color: var(--color-brand-600); }
    .rk-lvl.is-you { background: var(--color-brand-600); animation: rkPulse 2.4s ease-in-out infinite; }
    .rk-lvl.is-you b, .rk-lvl.is-you i { color: #fff; }
    @keyframes rkPulse { 0%, 100% { box-shadow: 0 0 0 0 rgb(107 159 61 / .45); } 50% { box-shadow: 0 0 0 .4rem rgb(107 159 61 / 0); } }

        html.dark .rk-row:nth-child(even) { background: rgb(255 255 255 / .03); }
    html.dark .rk-row.is-me { background: rgb(61 104 35 / .25); }
    html.dark .rk-task-e, html.dark .rk-arc-e, html.dark .rk-lvl { background: rgb(255 255 255 / .07); }
    html.dark .rk-lvl.is-passed { background: rgb(61 104 35 / .3); }
    html.dark .rk-lvl.is-you { background: var(--color-brand-600); }

    @media (prefers-reduced-motion: reduce) {
        .rk-me, .rk-me-badge, .rk-me-face, .rk-me.is-up .rk-me-badge, .rk-medal, .rk-block,
        [data-animate-rows] .rk-row, [data-animate-rows] .rk-task, [data-animate-rows] .rk-lvl,
        [data-rk-panel]:not(.hidden), .rk-lvl.is-you, .rk-bar span, .rk-gift { animation: none; }
        .rk-me { background-position: 50% 50%; }
        .rk-bar span { transition: none;
            background: linear-gradient(90deg, #6b9f3d, #4a7c2a); }
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
            // A new decade is a new NAME; inside a decade the number is the news.
            const title = me.getAttribute('data-rank-name');
            const newTitle = Math.ceil(now / 10) > Math.ceil(seen / 10);
            window.smToast?.(newTitle
                ? 'Bagong titulo! You are now a ' + title + ' — Level ' + now + ' 🎉'
                : 'Level up! Level ' + now + ' na 🎉');
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
        // (The tasks tab carries no running numbers any more — each row just
        // says what it pays.)
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
        setTimeout(() => document.querySelector('.rk-lvl.is-you')?.scrollIntoView({ block: 'center', behavior: still ? 'auto' : 'smooth' }), 350);
    }
});
</script>
@endpush
