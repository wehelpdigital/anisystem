@extends('layouts.app')
@section('title', 'Hatian Calculator')
@section('page-title', 'Hatian')
@section('page-subtitle', 'Your farm manager\'s share and yours, fairly')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))
@section('body-class', 'hide-tabbar no-footer')

{{-- Hatian Calculator (2026-10-26, the owner): the harvest shared between the
     owner and the farm manager. The manager's share is set as a percent, a
     number of sacks or a weight in kilos, and is counted from the GROSS
     harvest; the owner's home food comes out of the owner's share only. The
     sacks weigh between a light and a heavy figure the farmer sets, so every
     share is said in sacks and in kilos, with both ends.

     The arithmetic is App\Support\Hatian's, the same here in compute().
     Saved calculations live on the Saved tab (as_plant_analyses, 'hatian').
     Every choice is a tag that opens a sheet; nothing turns by itself. --}}
@push('head')
<style>
    .ht-wrap { --ht-e: cubic-bezier(.22,1,.36,1); --ht-m: #1d6fd1; --ht-h: #d9902a; --ht-o: #3d7a23; max-width: 68rem; margin: 0 auto; padding-bottom: calc(2rem + env(safe-area-inset-bottom)); }
    .ht-wrap [hidden] { display: none !important; }

    /* ---- the hero: two piles of sacks and the scale between them ---- */
    .ht-hero { position: relative; overflow: hidden; display: flex; gap: 1rem; align-items: center; padding: 1.1rem 1.2rem 1.25rem; border-radius: 1.4rem; color: #eaf3ff;
        background: radial-gradient(120% 150% at 100% 0%, #3f8be0 0%, #1f4f8f 45%, #0f2747 100%); }
    .ht-hero::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 5px; background: linear-gradient(90deg, var(--ht-m) 0 30%, var(--ht-h) 30% 45%, var(--ht-o) 45% 100%); }
    .ht-art { flex: none; width: 5.4rem; height: 5.4rem; }
    .ht-art .ht-beam { transform-origin: 32px 20px; animation: htTilt 5s var(--ht-e) infinite; }
    @keyframes htTilt { 0%, 100% { transform: rotate(-4deg); } 50% { transform: rotate(4deg); } }
    .ht-hero h2 { font-family: var(--font-heading); font-size: 1.22rem; font-weight: 800; line-height: 1.2; color: #fff; }
    .ht-hero p { margin-top: .3rem; font-size: .88rem; line-height: 1.5; color: #d6e6fb; }

    /* ---- the tabs ---- */
    .ht-tabs { position: relative; display: grid; grid-template-columns: 1fr 1fr; padding: .3rem; margin-top: 1rem; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .ht-tab-ind { position: absolute; top: .3rem; bottom: .3rem; left: .3rem; width: calc(50% - .3rem); border-radius: .75rem; background: #4a7c2a; box-shadow: 0 6px 14px -8px rgb(45 80 22 / .8); transition: transform .28s var(--ht-e); }
    .ht-tabs.is-saved .ht-tab-ind { transform: translateX(100%); }
    .ht-tab { position: relative; z-index: 1; min-height: 2.6rem; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; border-radius: .75rem; font-weight: 800; font-size: .9rem; color: var(--color-gray-600); transition: color .28s var(--ht-e); }
    .ht-tab[aria-selected="true"] { color: #fff; }
    .ht-tab-n { min-width: 1.35rem; padding: .05rem .4rem; border-radius: 999px; font-size: .7rem; font-weight: 900; color: #2d5016; background: #e4efd4; }
    .ht-tab-n:empty { display: none; }

    /* ---- the questions, and the answer beside them on a wide screen ---- */
    .ht-grid { display: grid; gap: 1rem; margin-top: 1rem; align-items: start; }
    @media (min-width: 1024px) { .ht-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .ht-card { padding: 1rem 1.05rem 1.1rem; border-radius: 1.25rem; background: var(--color-white); border: 1px solid var(--color-gray-200); animation: htIn .4s var(--ht-e) both; }
    .ht-card + .ht-card { margin-top: .85rem; }
    @keyframes htIn { from { opacity: 0; transform: translateY(8px); } }
    .ht-card h3 { display: flex; align-items: center; gap: .55rem; font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; color: var(--color-gray-900); }
    .ht-card h3 i { flex: none; width: 1.9rem; height: 1.9rem; border-radius: .7rem; display: grid; place-items: center; font-style: normal; font-size: .82rem; font-weight: 900; color: #2d5016; background: #e9f2de; }
    .ht-card > p { margin-top: .25rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-500); }
    .ht-row { display: grid; gap: .85rem; margin-top: .85rem; }
    .ht-f { display: grid; gap: .3rem; min-width: 0; }
    .ht-f > span:not(.ht-in) { font-size: .78rem; font-weight: 800; color: var(--color-gray-700); }
    .ht-f > small { font-size: .72rem; line-height: 1.4; color: var(--color-gray-500); }
    .ht-in { position: relative; display: flex; align-items: center; }
    .ht-in input { width: 100%; min-width: 0; padding-right: 3.6rem; font-variant-numeric: tabular-nums; }
    .ht-in input.is-pre { padding-left: 1.7rem; }
    .ht-in input.is-text { padding-right: .9rem; }
    .ht-in em { position: absolute; right: .75rem; font-style: normal; font-size: .78rem; font-weight: 800; color: var(--color-gray-400); pointer-events: none; }
    .ht-in b { position: absolute; left: .75rem; font-size: .9rem; font-weight: 800; color: var(--color-gray-400); pointer-events: none; }
    .ht-range { display: flex; align-items: center; gap: .45rem; min-width: 0; }
    .ht-range .ht-in { flex: 1 1 0; min-width: 0; }
    .ht-range > i { font-style: normal; font-size: .8rem; font-weight: 700; color: var(--color-gray-500); flex: none; }
    .ht-tag { display: inline-flex; align-items: center; justify-content: space-between; gap: .4rem; width: 100%; min-height: 2.75rem; padding: 0 .75rem; border-radius: .75rem;
        border: 1.5px solid var(--color-gray-200); background: var(--color-gray-50, #f9fafb); font-size: .84rem; font-weight: 800; color: var(--color-gray-800); cursor: pointer;
        transition: border-color .28s var(--ht-e), transform .28s var(--ht-e); }
    .ht-tag:hover { border-color: #86b556; transform: translateY(-1px); }
    .ht-tag svg { width: .85rem; height: .85rem; color: var(--color-gray-400); }
    .ht-chips { display: flex; flex-wrap: wrap; gap: .35rem; }
    .ht-chip { padding: .38rem .7rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: var(--color-gray-700); background: var(--color-white); border: 1.5px solid var(--color-gray-200);
        transition: background-color .28s var(--ht-e), border-color .28s var(--ht-e), color .28s var(--ht-e); }
    .ht-chip:hover { border-color: #86b556; }
    .ht-chip.is-on { color: #fff; background: #4a7c2a; border-color: #4a7c2a; }
    .ht-swap { animation: htSwap .32s var(--ht-e) both; }
    @keyframes htSwap { from { opacity: 0; transform: translateY(-4px); } }

    /* ---- the answer ---- */
    .ht-out { min-width: 0; }
    .ht-empty { display: flex; gap: .8rem; align-items: center; padding: 1.1rem; border-radius: 1.25rem; border: 1.5px dashed var(--color-gray-300, #d1d5db); color: var(--color-gray-500); font-size: .86rem; line-height: 1.5; background: var(--color-white); }
    .ht-empty svg { flex: none; width: 2.6rem; height: 2.6rem; }
    .ht-empty.is-bad { border-color: #fca5a5; color: #b91c1c; background: #fef2f2; }
    .ht-sum { position: relative; overflow: hidden; padding: 1.05rem 1.1rem; border-radius: 1.3rem; color: #fff; background: linear-gradient(135deg, #2b5f9e, #12345e); box-shadow: 0 18px 40px -26px rgb(15 39 71 / .9); }
    .ht-sum small { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; opacity: .85; }
    .ht-sum b { display: block; margin-top: .15rem; font-family: var(--font-heading); font-size: 1.12rem; font-weight: 800; line-height: 1.3; }
    .ht-bar { display: flex; height: 1.4rem; margin-top: .85rem; border-radius: 999px; overflow: hidden; background: rgb(255 255 255 / .18); }
    .ht-bar span { height: 100%; flex: 0 0 0%; transition: flex-basis .7s var(--ht-e); display: flex; align-items: center; justify-content: center; font-size: .66rem; font-weight: 900; color: #fff; overflow: hidden; white-space: nowrap; }
    .ht-bar .s-m { background: #5aa0ea; } .ht-bar .s-h { background: #f0a93a; } .ht-bar .s-o { background: #6fb04a; }
    .ht-key { display: flex; flex-wrap: wrap; gap: .3rem .9rem; margin-top: .55rem; font-size: .72rem; color: #d6e6fb; }
    .ht-key i { display: inline-block; width: .6rem; height: .6rem; margin-right: .3rem; border-radius: 2px; vertical-align: -.05rem; }
    .ht-shares { display: grid; gap: .65rem; margin-top: .75rem; }
    @media (min-width: 640px) { .ht-shares { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    /* Beside the questions the column is narrow: one share a row, its figures to the right. */
    @media (min-width: 1024px) {
        .ht-shares { grid-template-columns: minmax(0, 1fr); }
        .ht-share { display: grid; grid-template-columns: minmax(0, 10rem) minmax(0, 1fr); column-gap: 1.1rem; align-items: start; }
        .ht-share .ht-lines { grid-column: 2; grid-row: 1 / span 2; margin-top: 0; }
    }
    .ht-share { position: relative; padding: .9rem .9rem 1rem; border-radius: 1.15rem; background: var(--color-white); border: 1.5px solid var(--color-gray-200); border-top: 4px solid var(--c); animation: htIn .4s var(--ht-e) both; }
    .ht-share h4 { font-size: .78rem; font-weight: 900; letter-spacing: .02em; color: var(--color-gray-700); overflow-wrap: anywhere; }
    .ht-big { margin-top: .3rem; font-family: var(--font-heading); font-size: clamp(1.3rem, 3.8vw, 1.65rem); font-weight: 800; line-height: 1.1; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .ht-big small { font-size: .78rem; font-weight: 800; color: var(--color-gray-500); }
    .ht-lines { margin-top: .55rem; display: grid; gap: .25rem; font-size: .74rem; color: var(--color-gray-600); }
    .ht-lines div { display: flex; justify-content: space-between; gap: .5rem; }
    .ht-lines div b { font-weight: 800; color: var(--color-gray-800); font-variant-numeric: tabular-nums; text-align: right; }
    .ht-box { margin-top: .75rem; padding: .85rem .95rem; border-radius: 1.15rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .ht-box h5 { display: flex; align-items: center; gap: .45rem; font-size: .82rem; font-weight: 900; color: var(--color-gray-800); }
    .ht-box h5 svg { flex: none; width: 1.05rem; height: 1.05rem; color: var(--ht-m); }
    .ht-box p, .ht-box li { margin-top: .3rem; font-size: .8rem; line-height: 1.55; color: var(--color-gray-600); }
    .ht-box p b, .ht-box li b { color: var(--color-gray-900); }
    .ht-box ul { margin-top: .2rem; padding-left: 1.05rem; list-style: disc; }
    .ht-box.is-warn { background: #fffbea; border-color: #f6e3a1; } .ht-box.is-warn h5 svg { color: #b45309; }
    .ht-how { margin-top: .75rem; font-size: .76rem; color: var(--color-gray-600); }
    .ht-how summary { cursor: pointer; font-weight: 800; color: var(--color-gray-700); }
    .ht-how ol { margin-top: .4rem; padding-left: 1.1rem; display: grid; gap: .25rem; list-style: decimal; line-height: 1.5; }
    .ht-acts { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1rem; }
    .ht-save { flex: 1 1 12rem; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 3rem; padding: 0 1.2rem; border-radius: 1rem;
        font-weight: 800; color: #14210c; background: #f5c518; box-shadow: 0 10px 22px -14px rgb(150 110 0 / .9); transition: transform .28s var(--ht-e), opacity .28s var(--ht-e); }
    .ht-save:hover { transform: translateY(-1px); } .ht-save:disabled { opacity: .5; transform: none; cursor: default; }
    .ht-save svg { width: 1.1rem; height: 1.1rem; }
    .ht-reset { min-height: 3rem; padding: 0 1rem; border-radius: 1rem; font-weight: 800; color: var(--color-gray-600); border: 1.5px solid var(--color-gray-200); background: var(--color-white); }

    /* ---- the saved list ---- */
    .ht-list { display: grid; gap: .6rem; margin-top: 1rem; }
    .ht-srow { display: flex; align-items: center; gap: .75rem; padding: .75rem .8rem; border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); animation: htIn .35s var(--ht-e) both;
        transition: opacity .28s var(--ht-e), transform .28s var(--ht-e), border-color .28s var(--ht-e); }
    .ht-srow.is-gone { opacity: 0; transform: translateX(12px); }
    .ht-srow:hover { border-color: #b9d39c; }
    .ht-date { flex: none; width: 2.9rem; padding: .3rem 0; border-radius: .8rem; text-align: center; background: #eaf2fc; color: #1f4f8f; line-height: 1; }
    .ht-date b { display: block; font-size: 1.05rem; font-weight: 900; } .ht-date small { font-size: .62rem; font-weight: 800; text-transform: uppercase; }
    .ht-sopen { flex: 1 1 auto; min-width: 0; text-align: left; }
    .ht-sopen b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .ht-sopen small { display: block; margin-top: .1rem; font-size: .74rem; color: var(--color-gray-500); }
    .ht-sdel { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; color: var(--color-gray-400); }
    .ht-sdel:hover { color: #b91c1c; background: #fef2f2; }
    .ht-sdel svg { width: 1.05rem; height: 1.05rem; }
    .ht-sempty { margin-top: 1rem; padding: 1.4rem 1.1rem; border-radius: 1.2rem; text-align: center; font-size: .86rem; color: var(--color-gray-500); background: var(--color-white); border: 1.5px dashed var(--color-gray-200); }
    .ht-more { margin: .8rem auto 0; display: block; padding: .55rem 1.1rem; border-radius: .9rem; font-weight: 800; font-size: .84rem; color: #2d5016; background: #eef5e6; }

    /* ---- the choice sheet ---- */
    .ht-opts { display: grid; gap: .5rem; }
    .ht-opt { display: flex; align-items: center; gap: .75rem; width: 100%; padding: .8rem .9rem; border-radius: 1rem; text-align: left; border: 1.5px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .28s var(--ht-e), background-color .28s var(--ht-e), transform .28s var(--ht-e); }
    .ht-opt:hover { border-color: #b9d39c; transform: translateY(-1px); }
    .ht-opt.is-on { border-color: #4a7c2a; background: #f3f8ec; }
    .ht-opt span { flex: 1 1 auto; min-width: 0; }
    .ht-opt b { display: block; font-size: .92rem; font-weight: 800; color: var(--color-gray-900); }
    .ht-opt small { display: block; margin-top: .1rem; font-size: .76rem; color: var(--color-gray-500); }
    .ht-opt i { flex: none; width: 1.4rem; height: 1.4rem; border-radius: 999px; border: 2px solid #cbd5c1; display: grid; place-items: center; transition: background-color .28s var(--ht-e), border-color .28s var(--ht-e); }
    .ht-opt.is-on i { background: #4a7c2a; border-color: #4a7c2a; }
    .ht-opt i::after { content: ''; width: .45rem; height: .45rem; border-radius: 999px; background: #fff; transform: scale(0); transition: transform .28s var(--ht-e); }
    .ht-opt.is-on i::after { transform: scale(1); }

    /* ---- dark ---- */
    html.dark .ht-tabs, html.dark .ht-card, html.dark .ht-share, html.dark .ht-box, html.dark .ht-empty, html.dark .ht-srow, html.dark .ht-sempty, html.dark .ht-opt, html.dark .ht-reset, html.dark .ht-chip { background: #151b12; border-color: #2b3a1c; }
    html.dark .ht-share { border-top-color: var(--c); }
    html.dark .ht-card h3, html.dark .ht-big, html.dark .ht-sopen b, html.dark .ht-opt b, html.dark .ht-lines div b, html.dark .ht-box h5, html.dark .ht-box p b, html.dark .ht-box li b { color: #e8efe1; }
    html.dark .ht-box p, html.dark .ht-box li { color: #c5d2b8; }
    html.dark .ht-card h3 i { background: #22301a; color: #cfe3bd; }
    html.dark .ht-date { background: #13243a; color: #bcd6f5; }
    html.dark .ht-tag { background: #1b2416; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .ht-chip { color: #d5e3c5; } html.dark .ht-chip.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .ht-box.is-warn { background: #2a240f; border-color: #4a3f17; }
    html.dark .ht-empty.is-bad { background: #2a1414; border-color: #4c1d1d; color: #fca5a5; }
    html.dark .ht-opt.is-on { background: #1f2d16; }
    html.dark .ht-tab-n { background: #2b3a1c; color: #d5e3c5; }
    html.dark .ht-more { background: #22301a; color: #cfe3bd; }

    @media (prefers-reduced-motion: reduce) {
        .ht-art .ht-beam, .ht-card, .ht-share, .ht-srow, .ht-swap { animation: none !important; }
        .ht-tab-ind, .ht-bar span, .ht-chip, .ht-tag { transition: none !important; }
    }
    html.sm-still .ht-art .ht-beam { animation: none !important; }
</style>
@endpush

@section('content')
@php
    $chev = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
    // Built here: the json directive reads one argument, and an inline array is cut at its first comma.
    $htBoot = ['modes' => $modes, 'quick' => $quick, 'defaults' => $defaults,
        'urls' => ['save' => route('hatian.save'), 'list' => route('hatian.list'), 'one' => url('/app/hatian/one'), 'del' => url('/app/hatian')]];
@endphp
<script type="application/json" id="htBoot">@json($htBoot)</script>
<div class="ht-wrap">
    <section class="ht-hero">
        <svg class="ht-art" viewBox="0 0 64 64" aria-hidden="true">
            <path d="M32 18v36M22 58h20" stroke="#d6e6fb" stroke-width="2.6" stroke-linecap="round"/>
            <g class="ht-beam">
                <path d="M10 20h44" stroke="#fff" stroke-width="2.8" stroke-linecap="round"/>
                <path d="M12 20l-6 14h12zM52 20l-6 14h12z" fill="none" stroke="#d6e6fb" stroke-width="1.6" stroke-linejoin="round"/>
                <path d="M4 34a8 4 0 0016 0zM44 34a8 4 0 0016 0z" fill="#f5c518"/>
                <path d="M8 31c1-2 7-2 8 0M48 31c1-2 7-2 8 0" stroke="#e7b46a" stroke-width="3" stroke-linecap="round"/>
            </g>
            <circle cx="32" cy="18" r="3" fill="#fff"/>
        </svg>
        <div>
            <h2>Hatian Calculator{{ \App\Support\Tagalog::flag('hatian') }}</h2>
            <p>Share the harvest fairly with your farm manager. Set the share as a percent, a number of sacks or kilos, keep sacks for your home food, and see what each side gets, in sacks and in kilos.</p>
        </div>
    </section>

    <div class="ht-tabs" id="htTabs" role="tablist" aria-label="Hatian">
        <span class="ht-tab-ind" aria-hidden="true"></span>
        <button type="button" class="ht-tab" id="htTabCalc" role="tab" aria-selected="true" aria-controls="htPaneCalc">Calculator</button>
        <button type="button" class="ht-tab" id="htTabSaved" role="tab" aria-selected="false" aria-controls="htPaneSaved" tabindex="-1">Saved<span class="ht-tab-n" id="htTabN"></span></button>
    </div>

    <div id="htPaneCalc" role="tabpanel" aria-labelledby="htTabCalc">
    <div class="ht-grid">
        <div class="ht-form">
            {{-- 1. The harvest --}}
            <section class="ht-card">
                <h3><i>1</i>Your harvest</h3>
                <p>All the sacks, before anything is shared. This is the gross harvest.</p>
                <div class="ht-row">
                    <label class="ht-f"><span>Sacks harvested</span>
                        <span class="ht-in"><input type="number" class="form-input" id="htSacks" min="0" step="any" inputmode="decimal" placeholder="Like 120"><em>sacks</em></span></label>
                    <div class="ht-f"><span>Weight of one sack</span>
                        <div class="ht-range">
                            <span class="ht-in"><input type="number" class="form-input" id="htKgMin" min="1" max="150" step="any" inputmode="decimal" value="{{ $defaults['kgMin'] }}" aria-label="The lightest sack, kg"><em>kg</em></span>
                            <i>to</i>
                            <span class="ht-in"><input type="number" class="form-input" id="htKgMax" min="1" max="150" step="any" inputmode="decimal" value="{{ $defaults['kgMax'] }}" aria-label="The heaviest sack, kg"><em>kg</em></span>
                        </div>
                        <small>Sacks are never the same. Weigh a few, the lightest and the heaviest. Put the same number twice if they all weigh the same.</small></div>
                </div>
            </section>

            {{-- 2. The farm manager --}}
            <section class="ht-card">
                <h3><i>2</i>Your farm manager's share</h3>
                <p>Counted from the whole harvest, before your home food.</p>
                <div class="ht-row">
                    <label class="ht-f"><span>Farm manager's name <small style="font-weight:600;color:var(--color-gray-400)">(optional)</small></span>
                        <span class="ht-in"><input type="text" class="form-input is-text" id="htManager" maxlength="60" placeholder="Like Mang Tonyo" autocomplete="off"></span></label>
                    <div class="ht-f"><span>How the share is set</span>
                        <button type="button" class="ht-tag" id="htMode" aria-haspopup="dialog"><span>A percent of the harvest</span>{!! $chev !!}</button></div>
                    <label class="ht-f ht-swap" id="htShareBox"><span id="htShareLabel">Share of the harvest</span>
                        <span class="ht-in"><input type="number" class="form-input" id="htShare" min="0" step="any" inputmode="decimal" placeholder="Like 10"><em id="htShareUnit">%</em></span>
                        <small id="htShareHint">Like 10% when your farm manager gets 1 sack in every 10.</small></label>
                    <div class="ht-chips" id="htQuick" aria-label="Shares people use"></div>
                </div>
            </section>

            {{-- 3. Home food --}}
            <section class="ht-card">
                <h3><i>3</i>Your home food</h3>
                <p>The sacks you keep to eat. They come out of your share only.</p>
                <div class="ht-row">
                    <label class="ht-f"><span>Sacks for your home food</span>
                        <span class="ht-in"><input type="number" class="form-input" id="htHome" min="0" step="any" inputmode="decimal" placeholder="Like 10"><em>sacks</em></span>
                        <small>Leave it empty if you keep none.</small></label>
                </div>
            </section>

            {{-- 4. The price --}}
            <section class="ht-card">
                <h3><i>4</i>Price <small style="font-family:inherit;font-size:.74rem;font-weight:700;color:var(--color-gray-400)">(optional)</small></h3>
                <p>To see what each share is worth.</p>
                <div class="ht-row">
                    <label class="ht-f"><span>Price a kilo</span>
                        <span class="ht-in"><b>₱</b><input type="number" class="form-input is-pre" id="htPrice" min="0" step="any" inputmode="decimal" placeholder="Like 17"><em>a kg</em></span></label>
                </div>
            </section>
        </div>

        {{-- The answer, counted as you type. --}}
        <div class="ht-out" id="htOut" aria-live="polite">
            <div class="ht-empty" id="htEmpty">
                <svg viewBox="0 0 48 48" aria-hidden="true"><path d="M24 10v30M14 42h20M8 14h32" stroke="#93b4dc" stroke-width="2.6" stroke-linecap="round"/><path d="M4 26a6 3 0 0012 0zM32 26a6 3 0 0012 0z" fill="#f5c518"/></svg>
                <span id="htEmptySay">Fill in your sacks and the farm manager's share, and the hatian shows here.</span>
            </div>
            <div id="htRes" hidden>
                <div class="ht-sum">
                    <small>The hatian</small>
                    <b id="htHead"></b>
                    <div class="ht-bar" aria-hidden="true"><span class="s-m" id="htBM"></span><span class="s-h" id="htBH"></span><span class="s-o" id="htBO"></span></div>
                    <div class="ht-key" id="htKey"></div>
                </div>
                <div class="ht-shares">
                    <div class="ht-share" style="--c:var(--ht-m)"><h4 id="htMName">Farm manager</h4><div class="ht-big" id="htMBig"></div><div class="ht-lines" id="htMLines"></div></div>
                    <div class="ht-share" style="--c:var(--ht-h)"><h4>Your home food</h4><div class="ht-big" id="htHBig"></div><div class="ht-lines" id="htHLines"></div></div>
                    <div class="ht-share" style="--c:var(--ht-o)"><h4>Yours to sell or keep</h4><div class="ht-big" id="htOBig"></div><div class="ht-lines" id="htOLines"></div></div>
                </div>
                <div class="ht-box">
                    <h5><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7a5 5 0 0110 0v4M5 11h14v9H5z"/></svg>Handing it over</h5>
                    <p id="htHand"></p>
                </div>
                <div class="ht-box">
                    <h5><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M5 21h14M4 7h16M4 7l-3 7a4 4 0 006 0zM20 7l-3 7a4 4 0 006 0z"/></svg>Why it is fair</h5>
                    <ul id="htFair"></ul>
                </div>
                <div class="ht-box is-warn" id="htWarn" hidden>
                    <h5><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>Check this</h5>
                    <p id="htWarnSay"></p>
                </div>
                <details class="ht-how"><summary>How it was counted</summary><ol id="htHow"></ol></details>
                <div class="ht-acts">
                    <button type="button" class="ht-save" id="htSave"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h11l3 3v13H5zM8 4v5h7V4M8 20v-6h8v6"/></svg>Save this calculation</button>
                    <button type="button" class="ht-reset" id="htReset">Start over</button>
                </div>
            </div>
        </div>
    </div>
    </div>

    <section id="htPaneSaved" role="tabpanel" aria-labelledby="htTabSaved" hidden>
        <div class="ht-list" id="htList"></div>
        <div class="ht-sempty" id="htSEmpty" hidden>No saved calculations yet. Count one on the Calculator tab and save it, to look back at it later.</div>
        <button type="button" class="ht-more" id="htMore" hidden>Show more</button>
    </section>
</div>

<div class="sheet hidden" id="htPickSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="htPickTitle">Choose</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body"><div class="ht-opts" id="htPickRows"></div></div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const B = JSON.parse($('htBoot').textContent);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const still = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const toast = (m, t) => (window.toast ? window.toast(m, t) : alert(m));
    const num = (v, d = 0) => Number(v).toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: d });
    // A light end can go below nothing (a set weight on light sacks); it is said as nothing, the warning says why.
    const peso = (v) => '₱' + Math.round(Math.max(0, v)).toLocaleString('en-PH');
    const val = (id) => { const s = $(id).value; return s === '' ? null : Number(s); };
    const sk = (n) => num(n, 2) + (Math.abs(n - 1) < 1e-9 ? ' sack' : ' sacks');
    const kg = (n) => num(Math.max(0, n), n >= 100 ? 0 : 1) + ' kg';
    const kgRange = (a) => (Math.abs(a.hi - a.lo) < 0.05 ? kg(a.kg) : kg(a.lo) + ' to ' + kg(a.hi));
    // Two ends as one figure when they round the same (a few kilos of a big harvest).
    const rng = (a, b, d = 1) => { const x = num(a, d), y = num(b, d); return x === y ? x : x + ' to ' + y; };
    const st = { mode: 'pct', last: null };
    const LS = 'anee.hatian.last';

    /* ---- the choice sheet ---- */
    let pk = null;
    const pick = (title, dict, cur, cb) => {
        $('htPickTitle').textContent = title;
        pk = cb;
        $('htPickRows').innerHTML = Object.entries(dict).map(([k, v]) => '<button type="button" class="ht-opt' + (k === cur ? ' is-on' : '') + '" data-k="' + esc(k) + '"><span><b>' + esc(v[0]) + '</b>' + (v[1] ? '<small>' + esc(v[1]) + '</small>' : '') + '</span><i></i></button>').join('');
        window.openSheet('htPickSheet');
    };
    $('htPickRows').addEventListener('click', (e) => {
        const b = e.target.closest('.ht-opt');
        if (!b || !pk) return;
        $('htPickRows').querySelectorAll('.ht-opt').forEach((x) => x.classList.toggle('is-on', x === b));
        const cb = pk; pk = null;
        setTimeout(() => window.closeSheet('htPickSheet'), 160);
        cb(b.dataset.k);
    });
    const swap = (el) => { if (still()) return; el.classList.remove('ht-swap'); void el.offsetWidth; el.classList.add('ht-swap'); };

    const SAY = {
        pct: ['Share of the harvest', '%', 'Like 10% when your farm manager gets 1 sack in every 10.', 'Like 10'],
        sacks: ['Sacks for your farm manager', 'sacks', 'The same number of sacks whatever the harvest.', 'Like 12'],
        kg: ['Kilos for your farm manager', 'kg', 'Weighed out, whatever the sacks weigh.', 'Like 600'],
    };
    const paintMode = (anim = true) => {
        const s = SAY[st.mode];
        $('htMode').querySelector('span').textContent = B.modes[st.mode][0];
        $('htShareLabel').textContent = s[0]; $('htShareUnit').textContent = s[1]; $('htShareHint').textContent = s[2]; $('htShare').placeholder = s[3];
        $('htQuick').hidden = st.mode !== 'pct';
        paintQuick();
        if (anim) swap($('htShareBox'));
    };
    const paintQuick = () => {
        const v = val('htShare');
        $('htQuick').innerHTML = B.quick.map(([p, w]) => '<button type="button" class="ht-chip' + (v !== null && Math.abs(v - p) < 0.01 ? ' is-on' : '') + '" data-q="' + p + '">' + esc(w) + '</button>').join('');
    };
    $('htMode').addEventListener('click', () => pick('How the share is set', B.modes, st.mode, (k) => { if (k !== st.mode) { st.mode = k; $('htShare').value = ''; paintMode(); calc(); } }));
    $('htQuick').addEventListener('click', (e) => { const c = e.target.closest('[data-q]'); if (!c) return; $('htShare').value = c.dataset.q; paintQuick(); calc(); });

    /* ---- the arithmetic: App\Support\Hatian::clean(), compute() and problem(), the same ---- */
    const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
    const read = () => {
        const n = (id, lo, hi, def = null) => { const v = val(id); return v === null || !isFinite(v) ? def : clamp(v, lo, hi); };
        let lo = n('htKgMin', 1, 150), hi = n('htKgMax', 1, 150);
        if (lo === null && hi === null) { lo = B.defaults.kgMin; hi = B.defaults.kgMax; } else { lo = lo ?? hi; hi = hi ?? lo; }
        return { sacks: n('htSacks', 0, 1000000), kgMin: Math.min(lo, hi), kgMax: Math.max(lo, hi), mode: st.mode,
            share: n('htShare', 0, st.mode === 'pct' ? 100 : 100000000), home: n('htHome', 0, 1000000, 0), price: n('htPrice', 0, 100000),
            manager: $('htManager').value.trim().slice(0, 60) };
    };
    const compute = (c) => {
        const S = c.sacks, lo = c.kgMin, hi = c.kgMax, avg = (lo + hi) / 2, x = c.share;
        const gross = { sacks: S, kg: S * avg, lo: S * lo, hi: S * hi };
        let m;
        if (c.mode === 'pct') { const p = x / 100; m = { sacks: S * p, kg: gross.kg * p, lo: gross.lo * p, hi: gross.hi * p, pct: x, pctLo: x, pctHi: x }; }
        else if (c.mode === 'sacks') m = { sacks: x, kg: x * avg, lo: x * lo, hi: x * hi, pct: x / S * 100, pctLo: x / S * 100, pctHi: x / S * 100 };
        else m = { sacks: x / avg, sacksLo: x / hi, sacksHi: x / lo, kg: x, lo: x, hi: x, pct: x / gross.kg * 100, pctLo: x / gross.hi * 100, pctHi: x / gross.lo * 100 };
        const o = { sacks: S - m.sacks, kg: gross.kg - m.kg, lo: gross.lo - m.lo, hi: gross.hi - m.hi };
        const H = c.home, h = { sacks: H, kg: H * avg, lo: H * lo, hi: H * hi };
        const left = { sacks: o.sacks - H, kg: o.kg - h.kg, lo: o.lo - h.lo, hi: o.hi - h.hi };
        const pctOf = (k) => (gross.kg > 0 ? k / gross.kg * 100 : 0);
        const after = c.mode === 'pct' ? { sacks: Math.max(0, S - H) * x / 100 } : null;
        const whole = Math.floor(m.sacks + 1e-9), part = m.sacks - whole;
        const hand = { whole, part, partKg: part * avg, partLo: part * lo, partHi: part * hi, up: part > 1e-6 ? whole + 1 : whole, backKg: part > 1e-6 ? (1 - part) * avg : 0 };
        const worth = (a) => (c.price ? { kg: a.kg * c.price, lo: a.lo * c.price, hi: a.hi * c.price } : null);
        return { avg, even: hi - lo < 1e-9, gross, manager: Object.assign(m, { worth: worth(m) }),
            owner: Object.assign(o, { pct: pctOf(o.kg), worth: worth(o) }), home: Object.assign(h, { pct: pctOf(h.kg), worth: worth(h) }),
            left: Object.assign(left, { pct: pctOf(left.kg), worth: worth(left) }),
            after: after ? Object.assign(after, { less: m.sacks - after.sacks, lessKg: (m.sacks - after.sacks) * avg }) : null,
            hand, tightAtLight: c.mode === 'kg' && left.lo < -1e-6 };
    };
    const problem = (c, r) => (r.manager.sacks > c.sacks + 1e-9 || r.manager.kg > r.gross.kg + 1e-9 ? 'The farm manager\'s share is more than the whole harvest.'
        : c.home > r.owner.sacks + 1e-9 ? 'Your home food is more than your share. Your share is ' + sk(r.owner.sacks) + '.' : '');

    /* ---- paint the answer ---- */
    const calc = () => {
        const c = read();
        try { localStorage.setItem(LS, JSON.stringify(c)); } catch (e) {}
        const empty = !c.sacks ? 'Fill in how many sacks you harvested.' : !c.share ? 'Fill in the farm manager\'s share.' : '';
        let r = null, bad = '';
        if (!empty) { r = compute(c); bad = problem(c, r); }
        $('htEmpty').hidden = !(empty || bad); $('htEmpty').classList.toggle('is-bad', !!bad); $('htRes').hidden = !!(empty || bad);
        if (empty || bad) { $('htEmptySay').textContent = empty || bad; st.last = null; return; }
        st.last = { c, r };
        const whoShort = c.manager || 'Farm manager';
        $('htHead').textContent = 'From ' + sk(c.sacks) + ': ' + whoShort + ' ' + sk(r.manager.sacks) + (c.home > 0 ? ', your home food ' + sk(c.home) : '') + ', and ' + sk(r.left.sacks) + ' left to you.';
        // The bar: the whole harvest, in its three parts.
        const seg = (id, pct, label) => { $(id).style.flexBasis = Math.max(0, pct) + '%'; $(id).textContent = pct >= 9 ? num(pct, 0) + '%' : ''; $(id).title = label; };
        seg('htBM', r.manager.pct, whoShort); seg('htBH', r.home.pct, 'Home food'); seg('htBO', r.left.pct, 'Yours');
        $('htKey').innerHTML = '<span><i style="background:#5aa0ea"></i>' + esc(whoShort) + ' ' + num(r.manager.pct, 1) + '%</span>'
            + (c.home > 0 ? '<span><i style="background:#f0a93a"></i>Home food ' + num(r.home.pct, 1) + '%</span>' : '')
            + '<span><i style="background:#6fb04a"></i>Yours ' + num(r.left.pct, 1) + '%</span>';
        // The three shares.
        const line = (k, v) => '<div><span>' + esc(k) + '</span><b>' + esc(v) + '</b></div>';
        const big = (n) => num(n, 2) + ' <small>' + (Math.abs(n - 1) < 1e-9 ? 'sack' : 'sacks') + '</small>';
        $('htMName').textContent = c.manager ? c.manager + ', farm manager' : 'Farm manager';
        $('htMBig').innerHTML = big(r.manager.sacks);
        $('htMLines').innerHTML = line('Weight', c.mode === 'kg' ? kg(r.manager.kg) + ', weighed' : (r.even ? kg(r.manager.kg) : 'about ' + kg(r.manager.kg)))
            + (c.mode === 'kg' && !r.even ? line('In sacks', rng(r.manager.sacksLo, r.manager.sacksHi)) : (!r.even ? line('Range', kgRange(r.manager)) : ''))
            + line('Of the harvest', c.mode === 'kg' && !r.even ? rng(r.manager.pctLo, r.manager.pctHi) + '%' : num(r.manager.pct, 1) + '%')
            + (r.manager.worth ? line('Worth', c.mode === 'kg' || r.even ? peso(r.manager.worth.kg) : peso(r.manager.worth.lo) + ' to ' + peso(r.manager.worth.hi)) : '');
        $('htHBig').innerHTML = big(r.home.sacks);
        $('htHLines').innerHTML = (c.home > 0 ? line('Weight', r.even ? kg(r.home.kg) : 'about ' + kg(r.home.kg)) + (!r.even ? line('Range', kgRange(r.home)) : '')
            + line('Of the harvest', num(r.home.pct, 1) + '%') + (r.home.worth ? line('Worth', r.even ? peso(r.home.worth.kg) : peso(r.home.worth.lo) + ' to ' + peso(r.home.worth.hi)) : '') : line('None kept', ''));
        $('htOBig').innerHTML = big(r.left.sacks);
        $('htOLines').innerHTML = line('Weight', r.even ? kg(r.left.kg) : 'about ' + kg(r.left.kg)) + (!r.even ? line('Range', kgRange(r.left)) : '')
            + line('Of the harvest', num(r.left.pct, 1) + '%') + (r.left.worth ? line('Worth', r.even ? peso(r.left.worth.kg) : peso(r.left.worth.lo) + ' to ' + peso(r.left.worth.hi)) : '')
            + line('Your share in all', sk(r.owner.sacks));
        // Handing it over: whole sacks, and the part of one more in kilos.
        const H2 = r.hand;
        $('htHand').innerHTML = c.mode === 'kg'
            ? 'Weigh out <b>' + esc(kg(c.share)) + '</b>. That is about ' + esc(sk(r.manager.sacks)) + (r.even || !rng(r.manager.sacksLo, r.manager.sacksHi).includes(' to ') ? '' : ', anywhere from ' + esc(rng(r.manager.sacksLo, r.manager.sacksHi)) + ' sacks by how heavy they are') + '.'
            : H2.part < 1e-6 ? 'Give <b>' + esc(sk(H2.whole)) + '</b>. It comes out in whole sacks.'
            : 'Give <b>' + esc(sk(H2.whole)) + '</b> and about <b>' + esc(kg(H2.partKg)) + '</b> more from the next sack' + (r.even ? '' : ' (' + esc(kg(H2.partLo)) + ' to ' + esc(kg(H2.partHi)) + ')') + ', weighed out.'
              + ' Or give ' + esc(sk(H2.up)) + ' and take back about ' + esc(kg(H2.backKg)) + (c.price ? ', or ' + esc(peso(H2.backKg * c.price)) + ' at your price' : '') + '.';
        // Why it is fair.
        const fair = [];
        if (c.mode === 'pct') {
            fair.push('Counted from the whole harvest of ' + esc(sk(c.sacks)) + ', before your home food. Your home food comes out of your own share only.');
            if (c.home > 0 && r.after) fair.push('Had it been counted after your home food, ' + esc(c.manager || 'your farm manager') + ' would get only ' + esc(sk(r.after.sacks)) + ', <b>' + esc(sk(r.after.less)) + '</b> (about ' + esc(kg(r.after.lessKg)) + ') less. Counting from the whole harvest keeps that in the share.');
        } else {
            fair.push('A set share stays the same whatever you keep for home food. It is <b>' + esc(c.mode === 'kg' && !r.even ? rng(r.manager.pctLo, r.manager.pctHi) : num(r.manager.pct, 1)) + '%</b> of the whole harvest.');
            fair.push('Your home food comes out of your own share only.');
        }
        if (!r.even && c.mode !== 'kg') fair.push('Your sacks weigh ' + esc(num(c.kgMin, 1)) + ' to ' + esc(num(c.kgMax, 1)) + ' kg. Whole sacks are fair only if you weigh them, or take turns picking the sacks so the heavy and the light ones are shared.');
        if (r.even) fair.push('Every sack weighs ' + esc(kg(c.kgMin)) + ', so a sack is the same for both of you.');
        $('htFair').innerHTML = fair.map((x) => '<li>' + x + '</li>').join('');
        // Check this.
        const warn = r.tightAtLight ? 'If your sacks are on the light side, ' + esc(kg(c.share)) + ' for your farm manager and your home food may not leave you anything. Weigh the harvest first.'
            : r.left.sacks < 1e-6 ? 'Nothing is left for you after the farm manager\'s share and your home food.' : '';
        $('htWarn').hidden = !warn; $('htWarnSay').innerHTML = warn;
        // How it was counted.
        $('htHow').innerHTML = [
            sk(c.sacks) + ' at ' + (r.even ? num(c.kgMin, 1) + ' kg' : num(c.kgMin, 1) + ' to ' + num(c.kgMax, 1) + ' kg, counted at ' + num(r.avg, 1) + ' kg') + ' a sack: ' + (r.even ? kg(r.gross.kg) : 'about ' + kg(r.gross.kg) + ' (' + kgRange(r.gross) + ')') + ' in all.',
            c.mode === 'pct' ? whoShort + ': ' + num(c.share, 2) + '% of ' + sk(c.sacks) + ' = ' + sk(r.manager.sacks) + '.'
                : c.mode === 'sacks' ? whoShort + ': ' + sk(c.share) + ', that is ' + num(r.manager.pct, 1) + '% of ' + sk(c.sacks) + '.'
                : whoShort + ': ' + kg(c.share) + ', about ' + sk(r.manager.sacks) + ' at ' + num(r.avg, 1) + ' kg a sack.',
            'Your share: ' + sk(c.sacks) + ' less ' + sk(r.manager.sacks) + ' = ' + sk(r.owner.sacks) + '.',
            c.home > 0 ? 'Your home food, ' + sk(c.home) + ', from your share: ' + sk(r.owner.sacks) + ' less ' + sk(c.home) + ' = ' + sk(r.left.sacks) + ' left to you.' : 'No home food set aside: all ' + sk(r.owner.sacks) + ' are yours.',
            c.price ? 'At ₱' + num(c.price, 2) + ' a kilo: ' + whoShort + ' ' + peso(r.manager.worth.kg) + (c.home > 0 ? ', home food ' + peso(r.home.worth.kg) : '') + ', yours ' + peso(r.left.worth.kg) + '.' : null,
        ].filter(Boolean).map((x) => '<li>' + esc(x) + '</li>').join('');
    };
    ['htSacks', 'htKgMin', 'htKgMax', 'htShare', 'htHome', 'htPrice', 'htManager'].forEach((id) => $(id).addEventListener('input', () => { if (id === 'htShare') paintQuick(); calc(); }));

    /* ---- put inputs back (a saved one, or the last one on this phone) ---- */
    const fill = (c) => {
        if (!c) return;
        const set = (id, v) => { $(id).value = v === null || v === undefined ? '' : v; };
        set('htSacks', c.sacks); set('htKgMin', c.kgMin ?? B.defaults.kgMin); set('htKgMax', c.kgMax ?? B.defaults.kgMax); set('htShare', c.share);
        set('htHome', c.home || ''); set('htPrice', c.price ?? ''); set('htManager', c.manager || '');
        st.mode = B.modes[c.mode] ? c.mode : 'pct';
        paintMode(false); calc();
    };
    try { const last = JSON.parse(localStorage.getItem(LS) || 'null'); if (last && typeof last === 'object') fill(last); else paintMode(false); } catch (e) { paintMode(false); }
    $('htReset').addEventListener('click', () => {
        ['htSacks', 'htShare', 'htHome', 'htPrice', 'htManager'].forEach((id) => { $(id).value = ''; });
        $('htKgMin').value = B.defaults.kgMin; $('htKgMax').value = B.defaults.kgMax;
        paintQuick(); calc(); $('htSacks').focus();
    });

    /* ---- the tabs and the saved list ---- */
    const LIST = { rows: [], page: 1, more: false };
    const tab = (saved) => {
        $('htTabs').classList.toggle('is-saved', saved);
        $('htTabCalc').setAttribute('aria-selected', String(!saved)); $('htTabSaved').setAttribute('aria-selected', String(saved));
        $('htTabCalc').tabIndex = saved ? -1 : 0; $('htTabSaved').tabIndex = saved ? 0 : -1;
        $('htPaneCalc').hidden = saved; $('htPaneSaved').hidden = !saved;
        if (saved && !LIST.loaded) load(1);
    };
    $('htTabCalc').addEventListener('click', () => tab(false));
    $('htTabSaved').addEventListener('click', () => tab(true));
    const shelf = (d, append) => {
        LIST.rows = append ? LIST.rows.concat(d.history || []) : (d.history || []);
        LIST.more = !!d.hasMore; LIST.page = d.page || 1; LIST.loaded = true;
        $('htTabN').textContent = LIST.rows.length ? (LIST.more ? LIST.rows.length + '+' : LIST.rows.length) : '';
        $('htSEmpty').hidden = LIST.rows.length > 0; $('htMore').hidden = !LIST.more;
        $('htList').innerHTML = LIST.rows.map((r, i) => '<div class="ht-srow" style="animation-delay:' + Math.min(i, 8) * 40 + 'ms" data-id="' + r.id + '">'
            + '<span class="ht-date"><b>' + esc(r.day) + '</b><small>' + esc(r.month) + '</small></span>'
            + '<button type="button" class="ht-sopen" data-open="' + r.id + '"><b>' + esc(r.title) + '</b><small>'
            + esc((r.manager != null ? 'Farm manager ' + num(r.manager, 2) : '') + (r.home ? ' · home ' + num(r.home, 2) : '') + (r.left != null ? ' · yours ' + num(r.left, 2) + ' sacks' : '') + ' · ' + r.at) + '</small></button>'
            + '<button type="button" class="ht-sdel" data-del="' + r.id + '" aria-label="Move to the Trash" title="Move to the Trash"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg></button></div>').join('');
    };
    const load = async (page) => {
        try { const res = await window.api(B.urls.list + '?page=' + page, { method: 'GET' }); shelf(res.data, page > 1); }
        catch (err) { toast(err.message || 'The list could not load.', 'error'); }
    };
    $('htMore').addEventListener('click', () => load(LIST.page + 1));
    $('htList').addEventListener('click', async (e) => {
        const o = e.target.closest('[data-open]');
        if (o) {
            try { const res = await window.api(B.urls.one + '/' + o.dataset.open, { method: 'GET' }); fill(res.data.params); tab(false); window.scrollTo({ top: 0, behavior: still() ? 'auto' : 'smooth' }); }
            catch (err) { toast(err.message, 'error'); }
            return;
        }
        const d = e.target.closest('[data-del]');
        if (!d) return;
        const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this calculation?', message: 'It goes to the Trash. You can restore it there for 30 days.', confirmText: 'Delete' }) : confirm('Delete this calculation?');
        if (!ok) return;
        const row = d.closest('.ht-srow'); row && row.classList.add('is-gone');
        try { const res = await window.api(B.urls.del + '/' + d.dataset.del, { method: 'DELETE' }); setTimeout(() => shelf(res.data, false), still() ? 0 : 260); }
        catch (err) { row && row.classList.remove('is-gone'); toast(err.message, 'error'); }
    });
    $('htSave').addEventListener('click', async () => {
        if (!st.last) return;
        const btn = $('htSave');
        btn.disabled = true;
        try {
            const res = await window.api(B.urls.save, { method: 'POST', body: st.last.c });
            shelf(res.data, false);
            toast('Saved: ' + res.data.title, 'success');
        } catch (err) { toast(err.message || 'It could not be saved.', 'error'); }
        finally { btn.disabled = false; }
    });
    const boot = () => { if (window.api) load(1); else document.addEventListener('DOMContentLoaded', () => load(1), { once: true }); };
    boot();
})();
</script>
@endpush
