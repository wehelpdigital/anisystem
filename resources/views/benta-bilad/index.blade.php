@extends('layouts.app')
@section('title', 'Benta o Bilad Calculator')
@section('page-title', 'Benta o Bilad')
@section('page-subtitle', 'Sell it wet, or dry it first?')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))
@section('body-class', 'hide-tabbar no-footer')

{{-- Benta o Bilad Calculator (2026-10-19, the owner): should the palay be
     sold wet, straight from the harvester, or dried first? The farmer gives
     the harvest in sacks and the lot, what the harvester takes (a share of
     the sacks, a set number of sacks, or pesos), what the drying costs, and
     the wet and dry prices a kilo; the page counts both ways as they type and
     says which leaves more money, with the break even dry price.

     The arithmetic is App\Support\BentaBilad's, the same here in calc().
     Saved calculations live on the Saved tab (as_plant_analyses, 'bilad').
     Every choice is a tag that opens a sheet; nothing turns by itself. --}}
@push('head')
<style>
    .bb-wrap { --bb-e: cubic-bezier(.22,1,.36,1); max-width: 68rem; margin: 0 auto; padding-bottom: calc(2rem + env(safe-area-inset-bottom)); }
    .bb-wrap [hidden] { display: none !important; }

    /* ---- the hero: the sun over a sack, and the two roads ---- */
    .bb-hero { position: relative; overflow: hidden; display: flex; gap: 1rem; align-items: center; padding: 1.1rem 1.2rem 1.25rem; border-radius: 1.4rem; color: #fff7e0;
        background: radial-gradient(120% 150% at 100% 0%, #e9a23b 0%, #9a5a12 45%, #4a2a07 100%); }
    .bb-hero::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 5px; background: linear-gradient(90deg, #4a7c2a 0 50%, #f5c518 50% 100%); }
    .bb-art { position: relative; flex: none; width: 5.2rem; height: 5.2rem; }
    .bb-sun { position: absolute; right: -.2rem; top: -.1rem; width: 2.6rem; height: 2.6rem; }
    .bb-sun circle { fill: #ffd84d; }
    .bb-sun .bb-rays { transform-origin: 50% 50%; animation: bbSpin 14s linear infinite; }
    .bb-sun .bb-rays path { stroke: #ffd84d; stroke-width: 2.4; stroke-linecap: round; }
    .bb-sack { position: absolute; left: 0; bottom: 0; width: 4rem; height: 4rem; animation: bbBob 4.2s var(--bb-e) infinite; }
    @keyframes bbSpin { to { transform: rotate(360deg); } }
    @keyframes bbBob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-4px); } }
    .bb-hero h2 { font-family: var(--font-heading); font-size: 1.22rem; font-weight: 800; line-height: 1.2; color: #fff; }
    .bb-hero p { margin-top: .3rem; font-size: .88rem; line-height: 1.5; color: #fde9c4; }

    /* ---- the tabs ---- */
    .bb-tabs { position: relative; display: grid; grid-template-columns: 1fr 1fr; padding: .3rem; margin-top: 1rem; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .bb-tab-ind { position: absolute; top: .3rem; bottom: .3rem; left: .3rem; width: calc(50% - .3rem); border-radius: .75rem; background: #4a7c2a; box-shadow: 0 6px 14px -8px rgb(45 80 22 / .8); transition: transform .28s var(--bb-e); }
    .bb-tabs.is-saved .bb-tab-ind { transform: translateX(100%); }
    .bb-tab { position: relative; z-index: 1; min-height: 2.6rem; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; border-radius: .75rem; font-weight: 800; font-size: .9rem; color: var(--color-gray-600); transition: color .28s var(--bb-e); }
    .bb-tab[aria-selected="true"] { color: #fff; }
    .bb-tab-n { min-width: 1.35rem; padding: .05rem .4rem; border-radius: 999px; font-size: .7rem; font-weight: 900; color: #2d5016; background: #e4efd4; }
    .bb-tab-n:empty { display: none; }

    /* ---- the layout: the questions, and the answer beside them on a wide screen ---- */
    .bb-grid { display: grid; gap: 1rem; margin-top: 1rem; align-items: start; }
    @media (min-width: 1024px) { .bb-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } .bb-out { position: sticky; top: calc(var(--app-head, 64px) + 1rem); } }
    .bb-card { padding: 1rem 1.05rem 1.1rem; border-radius: 1.25rem; background: var(--color-white); border: 1px solid var(--color-gray-200); animation: bbIn .4s var(--bb-e) both; }
    .bb-card + .bb-card { margin-top: .85rem; }
    @keyframes bbIn { from { opacity: 0; transform: translateY(8px); } }
    .bb-card h3 { display: flex; align-items: center; gap: .55rem; font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; color: var(--color-gray-900); }
    .bb-card h3 i { flex: none; width: 1.9rem; height: 1.9rem; border-radius: .7rem; display: grid; place-items: center; font-style: normal; font-size: .82rem; font-weight: 900; color: #2d5016; background: #e9f2de; }
    .bb-card > p { margin-top: .25rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-500); }
    .bb-row { display: grid; gap: .7rem; margin-top: .85rem; }
    @media (min-width: 520px) { .bb-row.is-2 { grid-template-columns: 1fr 1fr; } .bb-row.is-3 { grid-template-columns: 1fr 1fr 1fr; } }
    .bb-f { display: grid; gap: .3rem; min-width: 0; }
    .bb-f > span { font-size: .78rem; font-weight: 800; color: var(--color-gray-700); }
    .bb-f > small { font-size: .72rem; line-height: 1.4; color: var(--color-gray-500); }
    .bb-in { position: relative; display: flex; align-items: center; }
    .bb-in input { width: 100%; min-width: 0; padding-right: 3.6rem; font-variant-numeric: tabular-nums; }
    .bb-in input.is-pre { padding-left: 1.7rem; }
    .bb-in em { position: absolute; right: .75rem; font-style: normal; font-size: .78rem; font-weight: 800; color: var(--color-gray-400); pointer-events: none; }
    .bb-in b { position: absolute; left: .75rem; font-size: .9rem; font-weight: 800; color: var(--color-gray-400); pointer-events: none; }
    .bb-pair { display: flex; gap: .5rem; min-width: 0; }
    .bb-pair .bb-in { flex: 1 1 auto; min-width: 0; }
    .bb-pair .bb-in input { padding-right: .8rem; }
    .bb-tag { flex: none; display: inline-flex; align-items: center; justify-content: space-between; gap: .4rem; min-height: 2.75rem; padding: 0 .75rem; border-radius: .75rem;
        border: 1.5px solid var(--color-gray-200); background: var(--color-gray-50, #f9fafb); font-size: .84rem; font-weight: 800; color: var(--color-gray-800); cursor: pointer;
        transition: border-color .28s var(--bb-e), transform .28s var(--bb-e), background-color .28s var(--bb-e); }
    .bb-tag:hover { border-color: #86b556; transform: translateY(-1px); }
    .bb-tag svg { width: .85rem; height: .85rem; color: var(--color-gray-400); }
    .bb-tag.is-wide { width: 100%; }
    .bb-tag small { font-weight: 600; color: var(--color-gray-500); }
    .bb-swap { animation: bbSwap .32s var(--bb-e) both; }
    .bb-range { display: flex; align-items: center; gap: .45rem; min-width: 0; }
    .bb-range .bb-in { flex: 1 1 0; min-width: 0; }
    .bb-range > i { font-style: normal; font-size: .8rem; font-weight: 700; color: var(--color-gray-500); flex: none; }
    .bb-vrange { margin-top: .55rem; font-size: .84rem; line-height: 1.45; opacity: .95; }
    .bb-vrange strong { font-weight: 800; }
    @keyframes bbSwap { from { opacity: 0; transform: translateY(-4px); } }

    /* ---- the answer ---- */
    .bb-out { min-width: 0; }
    .bb-empty { display: flex; gap: .8rem; align-items: center; padding: 1.1rem; border-radius: 1.25rem; border: 1.5px dashed var(--color-gray-300, #d1d5db); color: var(--color-gray-500); font-size: .86rem; line-height: 1.5; background: var(--color-white); }
    .bb-empty svg { flex: none; width: 2.6rem; height: 2.6rem; }
    .bb-verdict { position: relative; overflow: hidden; display: flex; gap: .9rem; align-items: center; padding: 1.05rem 1.1rem; border-radius: 1.3rem; color: #fff;
        background: linear-gradient(135deg, #3d6823, #1f3a0e); box-shadow: 0 18px 40px -26px rgb(20 33 12 / .9); }
    .bb-verdict.is-wet { background: linear-gradient(135deg, #1d6fd1, #0e3d78); }
    .bb-verdict.is-even { background: linear-gradient(135deg, #6b7280, #374151); }
    .bb-verdict.is-pop { animation: bbPop .5s var(--bb-e) both; }
    @keyframes bbPop { 0% { transform: scale(.96); opacity: .4; } 60% { transform: scale(1.015); opacity: 1; } 100% { transform: none; } }
    .bb-vic { flex: none; width: 3.2rem; height: 3.2rem; border-radius: 1rem; display: grid; place-items: center; background: rgb(255 255 255 / .16); }
    .bb-vic svg { width: 2rem; height: 2rem; }
    .bb-verdict.is-dry .bb-vic svg .bb-rays { transform-origin: 50% 50%; animation: bbSpin 10s linear infinite; }
    .bb-verdict small { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; opacity: .85; }
    .bb-verdict b { display: block; margin-top: .1rem; font-family: var(--font-heading); font-size: 1.18rem; font-weight: 800; line-height: 1.2; }
    .bb-verdict p { margin-top: .2rem; font-size: .84rem; line-height: 1.45; opacity: .92; }
    .bb-two { display: grid; grid-template-columns: 1fr 1fr; gap: .65rem; margin-top: .75rem; }
    .bb-way { position: relative; padding: .9rem .9rem 1rem; border-radius: 1.15rem; background: var(--color-white); border: 1.5px solid var(--color-gray-200);
        transition: border-color .28s var(--bb-e), box-shadow .28s var(--bb-e), transform .28s var(--bb-e); }
    .bb-way.is-win { border-color: #f5c518; box-shadow: 0 0 0 3px rgb(245 197 24 / .25), 0 18px 34px -24px rgb(120 80 0 / .7); transform: translateY(-2px); }
    .bb-way h4 { display: flex; align-items: center; gap: .4rem; font-size: .8rem; font-weight: 900; letter-spacing: .02em; color: var(--color-gray-700); }
    .bb-way h4 span { width: .6rem; height: .6rem; border-radius: 999px; background: #1d6fd1; }
    .bb-way.is-dry h4 span { background: #4a7c2a; }
    .bb-crown { position: absolute; top: -.6rem; right: .7rem; padding: .15rem .5rem; border-radius: 999px; font-size: .62rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase;
        color: #14210c; background: #f5c518; box-shadow: 0 0 0 2px var(--color-white); opacity: 0; transform: translateY(4px) scale(.9); transition: opacity .28s var(--bb-e), transform .28s var(--bb-e); }
    .bb-way.is-win .bb-crown { opacity: 1; transform: none; }
    .bb-net { margin-top: .35rem; font-family: var(--font-heading); font-size: clamp(1.25rem, 3.6vw, 1.6rem); font-weight: 800; line-height: 1.1; color: var(--color-gray-900); font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .bb-net.is-neg { color: #b91c1c; }
    .bb-way > small { display: block; font-size: .7rem; font-weight: 700; color: var(--color-gray-500); }
    .bb-lines { margin-top: .6rem; display: grid; gap: .25rem; font-size: .74rem; color: var(--color-gray-600); }
    .bb-lines div { display: flex; justify-content: space-between; gap: .5rem; }
    .bb-lines div b { font-weight: 800; color: var(--color-gray-800); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .bb-lines div.is-minus b { color: #b45309; }
    .bb-bars { margin-top: .75rem; padding: .85rem .95rem; border-radius: 1.15rem; background: var(--color-white); border: 1px solid var(--color-gray-200); display: grid; gap: .55rem; }
    .bb-bar { display: grid; grid-template-columns: 4.6rem minmax(0, 1fr); gap: .6rem; align-items: center; font-size: .74rem; font-weight: 800; color: var(--color-gray-600); }
    .bb-bar > i { position: relative; height: .9rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .bb-bar > i span { position: absolute; left: 0; top: 0; bottom: 0; width: 0; border-radius: 999px; background: linear-gradient(90deg, #5aa0ea, #1d6fd1); transition: width .7s var(--bb-e); }
    .bb-bar.is-dry > i span { background: linear-gradient(90deg, #86b556, #3d6823); }
    .bb-weight { margin-top: .75rem; padding: .85rem .95rem; border-radius: 1.15rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .bb-weight h5, .bb-be h5 { font-size: .78rem; font-weight: 900; color: var(--color-gray-800); }
    .bb-strip { display: flex; height: 1.1rem; margin-top: .55rem; border-radius: 999px; overflow: hidden; background: var(--color-gray-100); }
    .bb-strip span { height: 100%; transition: flex-basis .7s var(--bb-e); flex: 0 0 0%; }
    .bb-strip .s-dry { background: #e0a43a; } .bb-strip .s-water { background: #7cc0ff; } .bb-strip .s-loss { background: #f87171; }
    .bb-key { display: flex; flex-wrap: wrap; gap: .3rem .9rem; margin-top: .5rem; font-size: .72rem; color: var(--color-gray-600); }
    .bb-key i { display: inline-block; width: .6rem; height: .6rem; margin-right: .3rem; border-radius: 2px; vertical-align: -.05rem; }
    .bb-be { margin-top: .75rem; padding: .85rem .95rem; border-radius: 1.15rem; background: #fffbea; border: 1px solid #f6e3a1; }
    .bb-be p { margin-top: .25rem; font-size: .78rem; line-height: 1.5; color: #6b5212; }
    .bb-gauge { position: relative; height: .5rem; margin: 1.3rem .3rem .4rem; border-radius: 999px; background: linear-gradient(90deg, #5aa0ea 0 50%, #86b556 50% 100%); }
    .bb-gauge b { position: absolute; top: -1.15rem; transform: translateX(-50%); font-size: .64rem; font-weight: 900; color: #6b5212; white-space: nowrap; transition: left .7s var(--bb-e); }
    .bb-gauge u { position: absolute; top: -.25rem; width: 3px; height: 1rem; margin-left: -1.5px; border-radius: 2px; background: #14210c; transition: left .7s var(--bb-e); text-decoration: none; }
    .bb-gauge s { position: absolute; top: .65rem; transform: translateX(-50%); font-size: .64rem; font-weight: 800; color: #6b5212; white-space: nowrap; text-decoration: none; transition: left .7s var(--bb-e); }
    .bb-perha { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .5rem; margin-top: .75rem; }
    .bb-perha div { padding: .65rem .7rem; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); font-size: .68rem; font-weight: 700; color: var(--color-gray-500); }
    .bb-perha div b { display: block; margin-top: .15rem; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .bb-note { margin-top: .75rem; display: flex; gap: .55rem; align-items: flex-start; font-size: .76rem; line-height: 1.5; color: var(--color-gray-600); }
    .bb-note svg { flex: none; width: 1.1rem; height: 1.1rem; margin-top: .1rem; color: #1d6fd1; }
    .bb-how { margin-top: .75rem; font-size: .76rem; color: var(--color-gray-600); }
    .bb-how summary { cursor: pointer; font-weight: 800; color: var(--color-gray-700); }
    .bb-how ol { margin-top: .4rem; padding-left: 1.1rem; display: grid; gap: .25rem; list-style: decimal; line-height: 1.5; }
    .bb-acts { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1rem; }
    .bb-save { flex: 1 1 12rem; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 3rem; padding: 0 1.2rem; border-radius: 1rem;
        font-weight: 800; color: #14210c; background: #f5c518; box-shadow: 0 10px 22px -14px rgb(150 110 0 / .9); transition: transform .28s var(--bb-e), box-shadow .28s var(--bb-e), opacity .28s var(--bb-e); }
    .bb-save:hover { transform: translateY(-1px); box-shadow: 0 14px 26px -14px rgb(150 110 0 / .9); }
    .bb-save:disabled { opacity: .5; transform: none; cursor: default; }
    .bb-save svg { width: 1.1rem; height: 1.1rem; }
    .bb-reset { min-height: 3rem; padding: 0 1rem; border-radius: 1rem; font-weight: 800; color: var(--color-gray-600); border: 1.5px solid var(--color-gray-200); background: var(--color-white); }

    /* ---- the saved list ---- */
    .bb-list { display: grid; gap: .6rem; margin-top: 1rem; }
    .bb-srow { display: flex; align-items: center; gap: .75rem; padding: .75rem .8rem; border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); animation: bbIn .35s var(--bb-e) both;
        transition: opacity .28s var(--bb-e), transform .28s var(--bb-e), border-color .28s var(--bb-e); }
    .bb-srow.is-gone { opacity: 0; transform: translateX(12px); }
    .bb-srow:hover { border-color: #b9d39c; }
    .bb-date { flex: none; width: 2.9rem; padding: .3rem 0; border-radius: .8rem; text-align: center; background: #f3f8ec; color: #2d5016; line-height: 1; }
    .bb-date b { display: block; font-size: 1.05rem; font-weight: 900; } .bb-date small { font-size: .62rem; font-weight: 800; text-transform: uppercase; }
    .bb-sopen { flex: 1 1 auto; min-width: 0; text-align: left; }
    .bb-sopen b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .bb-sopen small { display: block; margin-top: .1rem; font-size: .74rem; color: var(--color-gray-500); }
    .bb-chip { display: inline-block; margin-right: .35rem; padding: .05rem .45rem; border-radius: 999px; font-size: .64rem; font-weight: 900; text-transform: uppercase; letter-spacing: .04em; color: #fff; background: #4a7c2a; }
    .bb-chip.is-wet { background: #1d6fd1; } .bb-chip.is-even { background: #6b7280; }
    .bb-sdel { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; color: var(--color-gray-400); }
    .bb-sdel:hover { color: #b91c1c; background: #fef2f2; }
    .bb-sdel svg { width: 1.05rem; height: 1.05rem; }
    .bb-sempty { margin-top: 1rem; padding: 1.4rem 1.1rem; border-radius: 1.2rem; text-align: center; font-size: .86rem; color: var(--color-gray-500); background: var(--color-white); border: 1.5px dashed var(--color-gray-200); }
    .bb-more { margin: .8rem auto 0; display: block; padding: .55rem 1.1rem; border-radius: .9rem; font-weight: 800; font-size: .84rem; color: #2d5016; background: #eef5e6; }

    /* ---- the choice sheet ---- */
    .bb-opts { display: grid; gap: .5rem; }
    .bb-opt { display: flex; align-items: center; gap: .75rem; width: 100%; padding: .8rem .9rem; border-radius: 1rem; text-align: left; border: 1.5px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .28s var(--bb-e), background-color .28s var(--bb-e), transform .28s var(--bb-e); }
    .bb-opt:hover { border-color: #b9d39c; transform: translateY(-1px); }
    .bb-opt.is-on { border-color: #4a7c2a; background: #f3f8ec; }
    .bb-opt span { flex: 1 1 auto; min-width: 0; }
    .bb-opt b { display: block; font-size: .92rem; font-weight: 800; color: var(--color-gray-900); }
    .bb-opt small { display: block; margin-top: .1rem; font-size: .76rem; color: var(--color-gray-500); }
    .bb-opt i { flex: none; width: 1.4rem; height: 1.4rem; border-radius: 999px; border: 2px solid #cbd5c1; display: grid; place-items: center; transition: background-color .28s var(--bb-e), border-color .28s var(--bb-e); }
    .bb-opt.is-on i { background: #4a7c2a; border-color: #4a7c2a; }
    .bb-opt i::after { content: ''; width: .45rem; height: .45rem; border-radius: 999px; background: #fff; transform: scale(0); transition: transform .28s var(--bb-e); }
    .bb-opt.is-on i::after { transform: scale(1); }

    /* ---- dark ---- */
    html.dark .bb-tabs, html.dark .bb-card, html.dark .bb-way, html.dark .bb-bars, html.dark .bb-weight, html.dark .bb-perha div, html.dark .bb-empty,
    html.dark .bb-srow, html.dark .bb-sempty, html.dark .bb-opt, html.dark .bb-reset { background: #151b12; border-color: #2b3a1c; }
    html.dark .bb-card h3, html.dark .bb-net, html.dark .bb-perha div b, html.dark .bb-sopen b, html.dark .bb-opt b, html.dark .bb-lines div b, html.dark .bb-weight h5 { color: #e8efe1; }
    html.dark .bb-card h3 i, html.dark .bb-date { background: #22301a; color: #cfe3bd; }
    html.dark .bb-tag { background: #1b2416; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .bb-bar > i, html.dark .bb-strip { background: #222c1b; }
    html.dark .bb-be { background: #2a240f; border-color: #4a3f17; } html.dark .bb-be p, html.dark .bb-be h5, html.dark .bb-gauge b, html.dark .bb-gauge s { color: #f3e2a7; }
    html.dark .bb-gauge u { background: #fff; }
    html.dark .bb-opt.is-on { background: #1f2d16; }
    html.dark .bb-way.is-win { box-shadow: 0 0 0 3px rgb(245 197 24 / .3); }
    html.dark .bb-tab-n { background: #2b3a1c; color: #d5e3c5; }
    html.dark .bb-more { background: #22301a; color: #cfe3bd; }

    @media (prefers-reduced-motion: reduce) {
        .bb-sun .bb-rays, .bb-sack, .bb-verdict.is-dry .bb-vic svg .bb-rays, .bb-card, .bb-srow, .bb-verdict.is-pop, .bb-swap { animation: none !important; }
        .bb-tab-ind, .bb-bar > i span, .bb-strip span, .bb-gauge b, .bb-gauge u, .bb-gauge s, .bb-way, .bb-crown { transition: none !important; }
    }
    html.sm-still .bb-sun .bb-rays, html.sm-still .bb-sack, html.sm-still .bb-verdict .bb-rays { animation: none !important; }
</style>
@endpush

@section('content')
@php
    $chev = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
    // Built here: the json directive reads one argument, and an inline array is cut at its first comma.
    $bbBoot = ['harvester' => $harvester, 'labor' => $labor, 'dryBy' => $dryBy, 'areaUnits' => $areaUnits, 'defaults' => $defaults, 'even' => $even, 'drySackKg' => $drySackKg,
        'urls' => ['save' => route('bilad.save'), 'list' => route('bilad.list'), 'one' => url('/app/benta-o-bilad/one'), 'del' => url('/app/benta-o-bilad')]];
@endphp
<script type="application/json" id="bbBoot">@json($bbBoot)</script>
<div class="bb-wrap">
    <section class="bb-hero">
        <div class="bb-art" aria-hidden="true">
            <svg class="bb-sun" viewBox="0 0 48 48"><g class="bb-rays"><path d="M24 3v6M24 39v6M3 24h6M39 24h6M9 9l4 4M35 35l4 4M39 9l-4 4M13 35l-4 4"/></g><circle cx="24" cy="24" r="9"/></svg>
            <svg class="bb-sack" viewBox="0 0 64 64"><path d="M20 14c4 3 20 3 24 0l-3 8H23z" fill="#c98b3c"/><path d="M23 22h18c8 6 12 16 11 26-1 8-8 12-20 12S13 56 12 48c-1-10 3-20 11-26z" fill="#e7b46a"/><path d="M23 22h18" stroke="#8a5a1c" stroke-width="2.2" stroke-linecap="round"/><path d="M26 38c2-2 4-2 6 0s4 2 6 0" stroke="#8a5a1c" stroke-width="2" fill="none" stroke-linecap="round"/><circle cx="25" cy="47" r="1.6" fill="#8a5a1c"/><circle cx="32" cy="49" r="1.6" fill="#8a5a1c"/><circle cx="39" cy="47" r="1.6" fill="#8a5a1c"/></svg>
        </div>
        <div>
            <h2>Benta o Bilad Calculator{{ \App\Support\Tagalog::flag('benta-o-bilad') }}</h2>
            <p>This calculator helps you decide if it is worth selling your harvest wet, straight from the harvester, or drying it first. Fill in your sacks, the harvester, the drying and both prices, and see which leaves you more money.</p>
        </div>
    </section>

    <div class="bb-tabs" id="bbTabs" role="tablist" aria-label="Benta o Bilad">
        <span class="bb-tab-ind" aria-hidden="true"></span>
        <button type="button" class="bb-tab" id="bbTabCalc" role="tab" aria-selected="true" aria-controls="bbPaneCalc">Calculator</button>
        <button type="button" class="bb-tab" id="bbTabSaved" role="tab" aria-selected="false" aria-controls="bbPaneSaved" tabindex="-1">Saved<span class="bb-tab-n" id="bbTabN"></span></button>
    </div>

    <div id="bbPaneCalc" role="tabpanel" aria-labelledby="bbTabCalc">
    <div class="bb-grid">
        <div class="bb-form">
            {{-- 1. The harvest --}}
            <section class="bb-card">
                <h3><i>1</i>Your harvest</h3>
                <p>Count the sacks as they came off the harvester, before any drying.</p>
                <div class="bb-row is-2">
                    <label class="bb-f"><span>Sacks harvested</span>
                        <span class="bb-in"><input type="number" class="form-input" id="bbSacks" min="0" step="any" inputmode="decimal" placeholder="Like 120"><em>sacks</em></span></label>
                    <label class="bb-f"><span>Weight of one sack</span>
                        <span class="bb-in"><input type="number" class="form-input" id="bbKg" min="10" max="150" step="any" inputmode="decimal" value="{{ $defaults['kgPerSack'] }}"><em>kg</em></span>
                        <small>A sack of fresh palay usually weighs 45 to 60 kg.</small></label>
                </div>
                <div class="bb-row">
                    <div class="bb-f"><span>Lot size</span>
                        <div class="bb-pair">
                            <span class="bb-in"><input type="number" class="form-input" id="bbArea" min="0" step="any" inputmode="decimal" placeholder="Like 1.5"></span>
                            <button type="button" class="bb-tag" id="bbAreaUnit" aria-haspopup="dialog"><span>Hectares</span>{!! $chev !!}</button>
                        </div>
                        <small>For your yield and money per hectare.</small></div>
                </div>
            </section>

            {{-- 2. The harvester --}}
            <section class="bb-card">
                <h3><i>2</i>The harvester</h3>
                <p>Paid the same whether you sell wet or dry, so it counts on both sides.</p>
                <div class="bb-row">
                    <div class="bb-f"><span>How the harvester is paid</span>
                        <button type="button" class="bb-tag is-wide" id="bbHMode" aria-haspopup="dialog"><span>A share of the sacks</span>{!! $chev !!}</button></div>
                    <label class="bb-f bb-swap" id="bbHBox"><span id="bbHLabel">Share of the sacks</span>
                        <span class="bb-in"><b id="bbHPre" hidden>₱</b><input type="number" class="form-input" id="bbH" min="0" step="any" inputmode="decimal" placeholder="Like 10"><em id="bbHUnit">%</em></span>
                        <small id="bbHHint">Like 10% when the harvester takes 1 sack in every 10.</small></label>
                </div>
            </section>

            {{-- 3. The drying --}}
            <section class="bb-card">
                <h3><i>3</i>Drying (bilad)</h3>
                <p>What it costs to dry the palay, and how much weight the drying takes out.</p>
                <div class="bb-row">
                    <div class="bb-f"><span>Drying labor</span>
                        <button type="button" class="bb-tag is-wide" id="bbLMode" aria-haspopup="dialog"><span>Per sack</span>{!! $chev !!}</button></div>
                </div>
                <div class="bb-row is-3 bb-swap" id="bbLBox">
                    <label class="bb-f" id="bbLRateBox"><span id="bbLRateLabel">Labor per sack</span>
                        <span class="bb-in"><b>₱</b><input type="number" class="form-input is-pre" id="bbLRate" min="0" step="any" inputmode="decimal" placeholder="Like 25"><em id="bbLRateUnit">a sack</em></span></label>
                    <label class="bb-f" id="bbLWorkersBox" hidden><span>Workers</span>
                        <span class="bb-in"><input type="number" class="form-input" id="bbLWorkers" min="0" step="1" inputmode="numeric" value="2"><em>people</em></span></label>
                    <label class="bb-f" id="bbLDaysBox" hidden><span>Days of drying</span>
                        <span class="bb-in"><input type="number" class="form-input" id="bbLDays" min="0" step="any" inputmode="decimal" value="2"><em>days</em></span></label>
                </div>
                <div class="bb-row">
                    <label class="bb-f"><span>Other drying costs</span>
                        <span class="bb-in"><b>₱</b><input type="number" class="form-input is-pre" id="bbOther" min="0" step="any" inputmode="decimal" placeholder="0"><em>in all</em></span>
                        <small>Hauling to the drying floor and back, the net or tarpaulin, meals, or a dryer's fee.</small></label>
                </div>
                {{-- How the weight the drying takes out is counted (2026-10-19, the owner): by moisture, or by the weight of a sack. --}}
                <div class="bb-row">
                    <div class="bb-f"><span>Count the drying</span>
                        <button type="button" class="bb-tag is-wide" id="bbDryBy" aria-haspopup="dialog"><span>By moisture</span>{!! $chev !!}</button></div>
                </div>
                <div class="bb-row is-2 bb-swap" id="bbByKg" hidden>
                    <label class="bb-f"><span>One sack when wet</span>
                        <span class="bb-in"><input type="number" class="form-input" id="bbKgWet" min="10" max="150" step="any" inputmode="decimal" value="{{ $defaults['kgPerSack'] }}"><em>kg</em></span>
                        <small>The same as the weight of one sack above.</small></label>
                    <div class="bb-f"><span>The same sack once dry</span>
                        <div class="bb-range">
                            <span class="bb-in"><input type="number" class="form-input" id="bbKgDryLo" min="1" max="150" step="any" inputmode="decimal" placeholder="Like 42" aria-label="Lightest dry sack, kg"><em>kg</em></span>
                            <i>to</i>
                            <span class="bb-in"><input type="number" class="form-input" id="bbKgDryHi" min="1" max="150" step="any" inputmode="decimal" placeholder="Like 45" aria-label="Heaviest dry sack, kg"><em>kg</em></span>
                        </div>
                        <small>Weigh a sack before and after drying. Spills and birds are already in it.</small></div>
                </div>
                <div class="bb-row is-3 bb-swap" id="bbByPct">
                    <label class="bb-f"><span>Moisture at harvest{{ \App\Support\Tagalog::flag('moisture-content') }}</span>
                        <span class="bb-in"><input type="number" class="form-input" id="bbMWet" min="10" max="45" step="any" inputmode="decimal" value="{{ $defaults['wetMoisture'] }}"><em>%</em></span>
                        <small>Fresh palay: 20 to 28%.</small></label>
                    <label class="bb-f"><span>Moisture when dry</span>
                        <span class="bb-in"><input type="number" class="form-input" id="bbMDry" min="5" max="25" step="any" inputmode="decimal" value="{{ $defaults['dryMoisture'] }}"><em>%</em></span>
                        <small>Buyers want about 14%.</small></label>
                    <label class="bb-f"><span>Lost while drying</span>
                        <span class="bb-in"><input type="number" class="form-input" id="bbLoss" min="0" max="30" step="any" inputmode="decimal" value="{{ $defaults['lossPct'] }}"><em>%</em></span>
                        <small>Spilled, birds, rain.</small></label>
                </div>
            </section>

            {{-- 4. The prices --}}
            <section class="bb-card">
                <h3><i>4</i>The prices</h3>
                <p>What a buyer pays you for one kilo, wet and dry.</p>
                <div class="bb-row is-2">
                    <label class="bb-f"><span>Wet palay (basa)</span>
                        <span class="bb-in"><b>₱</b><input type="number" class="form-input is-pre" id="bbPWet" min="0" step="any" inputmode="decimal" placeholder="Like 17"><em>a kg</em></span></label>
                    <label class="bb-f"><span>Dry palay (tuyo)</span>
                        <span class="bb-in"><b>₱</b><input type="number" class="form-input is-pre" id="bbPDry" min="0" step="any" inputmode="decimal" placeholder="Like 22"><em>a kg</em></span></label>
                </div>
            </section>
        </div>

        {{-- The answer, counted as you type. --}}
        <div class="bb-out" id="bbOut" aria-live="polite">
            <div class="bb-empty" id="bbEmpty">
                <svg viewBox="0 0 48 48" aria-hidden="true"><circle cx="34" cy="14" r="7" fill="#ffd84d"/><path d="M14 18h14c6 5 9 12 8 19-1 6-6 9-15 9s-14-3-15-9c-1-7 2-14 8-19z" fill="#e7b46a"/></svg>
                <span id="bbEmptySay">Fill in your sacks and both prices, and the answer shows here.</span>
            </div>
            <div id="bbRes" hidden>
                <div class="bb-verdict" id="bbVerdict">
                    <span class="bb-vic" id="bbVic"></span>
                    <span><small id="bbVKick">The answer</small><b id="bbVHead"></b><p id="bbVSay"></p><p class="bb-vrange" id="bbVRange" hidden></p></span>
                </div>
                <div class="bb-two">
                    <div class="bb-way is-wet" id="bbWayWet"><span class="bb-crown">More money</span>
                        <h4><span></span>Benta: sell it wet</h4>
                        <div class="bb-net" id="bbNetWet">₱0</div><small>left in your hand</small>
                        <div class="bb-lines" id="bbLinesWet"></div></div>
                    <div class="bb-way is-dry" id="bbWayDry"><span class="bb-crown">More money</span>
                        <h4><span></span>Bilad: dry, then sell</h4>
                        <div class="bb-net" id="bbNetDry">₱0</div><small>left in your hand</small>
                        <div class="bb-lines" id="bbLinesDry"></div></div>
                </div>
                <div class="bb-bars" aria-hidden="true">
                    <div class="bb-bar is-wet"><span>Benta</span><i><span id="bbBarWet"></span></i></div>
                    <div class="bb-bar is-dry"><span>Bilad</span><i><span id="bbBarDry"></span></i></div>
                </div>
                <div class="bb-weight">
                    <h5 id="bbWHead">What drying does to the weight</h5>
                    <div class="bb-strip" aria-hidden="true"><span class="s-dry" id="bbSDry"></span><span class="s-water" id="bbSWater"></span><span class="s-loss" id="bbSLoss"></span></div>
                    <div class="bb-key" id="bbWKey"></div>
                </div>
                <div class="bb-be">
                    <h5>Where drying starts to pay</h5>
                    <p id="bbBeSay"></p>
                    <div class="bb-gauge" aria-hidden="true"><b id="bbGLab">You</b><u id="bbGMark"></u><s id="bbGBe"></s></div>
                </div>
                <div class="bb-perha" id="bbPerHa" hidden></div>
                <p class="bb-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15zM8 21l1-2m3 2l1-2m3 2l1-2"/></svg>
                    <span>Drying takes one to three sunny days. If rain is coming before the palay is dry, selling it wet is the safer bet, whatever the numbers say.</span></p>
                <details class="bb-how"><summary>How it was counted</summary><ol id="bbHow"></ol></details>
                <div class="bb-acts">
                    <button type="button" class="bb-save" id="bbSave"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h11l3 3v13H5zM8 4v5h7V4M8 20v-6h8v6"/></svg>Save this calculation</button>
                    <button type="button" class="bb-reset" id="bbReset">Start over</button>
                </div>
            </div>
        </div>
    </div>
    </div>

    <section id="bbPaneSaved" role="tabpanel" aria-labelledby="bbTabSaved" hidden>
        <div class="bb-list" id="bbList"></div>
        <div class="bb-sempty" id="bbSEmpty" hidden>No saved calculations yet. Count one on the Calculator tab and save it, to look back at it later.</div>
        <button type="button" class="bb-more" id="bbMore" hidden>Show more</button>
    </section>
</div>

{{-- One sheet for every choice. --}}
<div class="sheet hidden" id="bbPickSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="bbPickTitle">Choose</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body"><div class="bb-opts" id="bbPickRows"></div></div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const B = JSON.parse($('bbBoot').textContent);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const still = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const toast = (m, t) => (window.toast ? window.toast(m, t) : alert(m));
    const peso = (v, d = 0) => (v < 0 ? '−' : '') + '₱' + Math.abs(v).toLocaleString('en-PH', { minimumFractionDigits: d, maximumFractionDigits: d });
    const num = (v, d = 0) => Number(v).toLocaleString('en-PH', { minimumFractionDigits: d, maximumFractionDigits: d });
    const val = (id) => { const s = $(id).value; return s === '' ? null : Number(s); };
    const st = { areaUnit: 'ha', hMode: 'pct', lMode: 'sack', dryBy: 'pct', last: null, verdict: null };
    const LS = 'anee.bilad.last';

    /* ---- the choice tags ---- */
    let pk = null;
    const pick = (title, dict, cur, cb) => {
        $('bbPickTitle').textContent = title;
        pk = cb;
        $('bbPickRows').innerHTML = Object.entries(dict).map(([k, v]) => '<button type="button" class="bb-opt' + (k === cur ? ' is-on' : '') + '" data-k="' + esc(k) + '"><span><b>' + esc(v[0]) + '</b>' + (v[1] ? '<small>' + esc(v[1]) + '</small>' : '') + '</span><i></i></button>').join('');
        window.openSheet('bbPickSheet');
    };
    $('bbPickRows').addEventListener('click', (e) => {
        const b = e.target.closest('.bb-opt');
        if (!b || !pk) return;
        $('bbPickRows').querySelectorAll('.bb-opt').forEach((x) => x.classList.toggle('is-on', x === b));
        const cb = pk; pk = null;
        setTimeout(() => window.closeSheet('bbPickSheet'), 160);
        cb(b.dataset.k);
    });
    const tag = (id, text) => { $(id).querySelector('span').textContent = text; };
    const swap = (el) => { if (still()) return; el.classList.remove('bb-swap'); void el.offsetWidth; el.classList.add('bb-swap'); };

    const AREA = { ha: ['Hectares', '10,000 square meters'], sqm: ['Square meters', 'For a small lot'] };
    $('bbAreaUnit').addEventListener('click', () => pick('Lot size in', AREA, st.areaUnit, (k) => { st.areaUnit = k; tag('bbAreaUnit', AREA[k][0]); calc(); }));

    const H_SAY = {
        pct: ['Share of the sacks', '%', false, 'Like 10% when the harvester takes 1 sack in every 10.', 'Like 10'],
        sacks: ['Sacks the harvester takes', 'sacks', false, 'Taken from the wet sacks at the field.', 'Like 12'],
        cash: ['Harvester fee in pesos', 'in all', true, 'For the whole field.', 'Like 6000'],
    };
    const paintH = () => {
        const s = H_SAY[st.hMode];
        tag('bbHMode', B.harvester[st.hMode][0]);
        $('bbHLabel').textContent = s[0]; $('bbHUnit').textContent = s[1]; $('bbHPre').hidden = !s[2]; $('bbH').classList.toggle('is-pre', s[2]);
        $('bbHHint').textContent = s[3]; $('bbH').placeholder = s[4];
        swap($('bbHBox'));
    };
    $('bbHMode').addEventListener('click', () => pick('How the harvester is paid', B.harvester, st.hMode, (k) => { if (k !== st.hMode) { st.hMode = k; $('bbH').value = ''; paintH(); calc(); } }));

    const L_SAY = { sack: ['Labor per sack', 'a sack', 'Like 25'], day: ['Wage a day', 'a person', 'Like 450'], total: ['Labor for the whole job', 'in all', 'Like 3000'] };
    const paintL = () => {
        const s = L_SAY[st.lMode];
        tag('bbLMode', B.labor[st.lMode][0]);
        $('bbLRateLabel').textContent = s[0]; $('bbLRateUnit').textContent = s[1]; $('bbLRate').placeholder = s[2];
        $('bbLWorkersBox').hidden = $('bbLDaysBox').hidden = st.lMode !== 'day';
        swap($('bbLBox'));
    };
    $('bbLMode').addEventListener('click', () => pick('How the drying labor is paid', B.labor, st.lMode, (k) => { if (k !== st.lMode) { st.lMode = k; $('bbLRate').value = ''; paintL(); calc(); } }));

    /* How the drying is counted (2026-10-19, the owner): by moisture, or by the weight of a sack wet and once dry.
       Switching to the sack's weight with nothing typed starts it at what the moisture figures give, a kilo either side. */
    const paintDry = (anim = true) => {
        tag('bbDryBy', B.dryBy[st.dryBy][0]);
        const kg = st.dryBy === 'kg';
        $('bbByKg').hidden = !kg; $('bbByPct').hidden = kg;
        if (anim) swap(kg ? $('bbByKg') : $('bbByPct'));
    };
    $('bbDryBy').addEventListener('click', () => pick('Count the drying', B.dryBy, st.dryBy, (k) => {
        if (k === st.dryBy) return;
        st.dryBy = k;
        if (k === 'kg' && val('bbKgDryLo') === null && val('bbKgDryHi') === null) {
            const W = val('bbKg') || B.defaults.kgPerSack, mw = val('bbMWet') ?? B.defaults.wetMoisture, md = val('bbMDry') ?? B.defaults.dryMoisture, ls = val('bbLoss') ?? B.defaults.lossPct;
            const d = mw > md ? W * (100 - mw) / (100 - md) * (1 - ls / 100) : null;
            if (d) { $('bbKgDryLo').value = Math.max(1, Math.round(d - 1)); $('bbKgDryHi').value = Math.round(d + 1); }
        }
        $('bbKgWet').value = $('bbKg').value;
        paintDry(); calc();
    }));

    /* ---- the arithmetic: App\Support\BentaBilad::clean() and compute(), the same ---- */
    const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
    const read = () => {
        const n = (id, lo, hi, def = null) => { const v = val(id); return v === null || !isFinite(v) ? def : clamp(v, lo, hi); };
        const c = {
            sacks: n('bbSacks', 0, 1000000), kgPerSack: n('bbKg', 10, 150, B.defaults.kgPerSack), area: n('bbArea', 0, 1000000), areaUnit: st.areaUnit,
            harvesterMode: st.hMode, harvester: n('bbH', 0, 100000000, 0), laborMode: st.lMode, laborRate: n('bbLRate', 0, 100000000, 0),
            laborWorkers: n('bbLWorkers', 0, 1000, 1), laborDays: n('bbLDays', 0, 365, 1), otherCost: n('bbOther', 0, 100000000, 0),
            wetMoisture: n('bbMWet', 10, 45, B.defaults.wetMoisture), dryMoisture: n('bbMDry', 5, 25, B.defaults.dryMoisture), lossPct: n('bbLoss', 0, 30, B.defaults.lossPct),
            dryBy: st.dryBy, dryKgLo: n('bbKgDryLo', 1, 150), dryKgHi: n('bbKgDryHi', 1, 150),
            priceWet: n('bbPWet', 0, 100000), priceDry: n('bbPDry', 0, 100000),
        };
        if (c.harvesterMode === 'pct') c.harvester = Math.min(90, c.harvester);
        if (c.dryBy === 'kg') {
            // The range reads either way round; one figure is a range of one.
            const lo = c.dryKgLo ?? c.dryKgHi, hi = c.dryKgHi ?? lo;
            c.dryKgLo = lo !== null ? Math.min(lo, hi) : null; c.dryKgHi = lo !== null ? Math.max(lo, hi) : null;
        }
        return c;
    };
    const missing = (c) => !c.sacks ? 'Fill in how many sacks you harvested.' : !c.priceWet || !c.priceDry ? 'Fill in both prices, wet and dry, for a kilo.'
        : c.dryBy === 'kg' ? (c.dryKgLo === null ? 'Fill in what one sack weighs once dry.' : c.dryKgHi >= c.kgPerSack ? 'A dry sack has to weigh less than the same sack wet.' : '')
        : c.dryMoisture >= c.wetMoisture ? 'The moisture when dry has to be below the moisture at harvest.' : '';
    const compute = (c) => {
        const H = c.sacks, W = c.kgPerSack, areaHa = c.area ? (c.areaUnit === 'sqm' ? c.area / 10000 : c.area) : null;
        const hSacks = c.harvesterMode === 'pct' ? H * c.harvester / 100 : c.harvesterMode === 'sacks' ? Math.min(H, c.harvester) : 0;
        const hCash = c.harvesterMode === 'cash' ? c.harvester : 0;
        const left = Math.max(0, H - hSacks), wetKg = left * W, byKg = c.dryBy === 'kg';
        const keep = byKg ? ((c.dryKgLo + c.dryKgHi) / 2) / W : (100 - c.wetMoisture) / (100 - c.dryMoisture);
        const afterWater = wetKg * keep, lossKg = byKg ? 0 : afterWater * c.lossPct / 100, dryKg = afterWater - lossKg;
        const labor = c.laborMode === 'sack' ? c.laborRate * left : c.laborMode === 'day' ? c.laborRate * c.laborWorkers * c.laborDays : c.laborRate;
        const dryCost = labor + c.otherCost, wetGross = wetKg * c.priceWet, dryGross = dryKg * c.priceDry;
        const netWet = wetGross - hCash, netDry = dryGross - dryCost - hCash, diff = netDry - netWet;
        const even = Math.max(B.even.pesos, B.even.share * Math.max(Math.abs(netWet), Math.abs(netDry)));
        return { verdict: diff > even ? 'dry' : diff < -even ? 'wet' : 'even', diff, hSacks, hCash, hWorth: hSacks * W * c.priceWet, left, wetKg, waterKg: wetKg - afterWater, lossKg, dryKg,
            drySacks: dryKg / B.drySackKg, shrinkPct: wetKg > 0 ? (1 - dryKg / wetKg) * 100 : null, labor, dryCost, wetGross, dryGross, netWet, netDry,
            breakEvenDry: dryKg > 0 ? (wetGross + dryCost) / dryKg : null, breakEvenWet: wetKg > 0 ? (dryGross - dryCost) / wetKg : null,
            areaHa, sacksPerHa: areaHa ? H / areaHa : null, netWetPerHa: areaHa ? netWet / areaHa : null, netDryPerHa: areaHa ? netDry / areaHa : null,
            breakEvenSackKg: left > 0 && c.priceDry > 0 ? (wetGross + dryCost) / c.priceDry / left : null,
            range: byKg && c.dryKgHi > c.dryKgLo ? { lo: end(compute(Object.assign({}, c, { dryKgHi: c.dryKgLo }))), hi: end(compute(Object.assign({}, c, { dryKgLo: c.dryKgHi }))) } : null };
    };
    const end = (r) => ({ verdict: r.verdict, diff: r.diff, dryKg: r.dryKg, netDry: r.netDry });

    /* ---- numbers that count to their new value ---- */
    const shown = new Map();
    const countTo = (el, to, fmt) => {
        const from = shown.has(el) ? shown.get(el) : 0;
        shown.set(el, to);
        if (still() || !isFinite(from) || Math.abs(to - from) < 0.5) { el.textContent = fmt(to); return; }
        const t0 = performance.now(), dur = 650;
        const step = (t) => {
            const k = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - k, 3);
            if (shown.get(el) !== to) return;
            el.textContent = fmt(from + (to - from) * e);
            if (k < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };

    const ICON = {
        dry: '<svg viewBox="0 0 48 48" fill="none"><g class="bb-rays" stroke="#ffd84d" stroke-width="2.6" stroke-linecap="round"><path d="M24 4v6M24 38v6M4 24h6M38 24h6M10 10l4 4M34 34l4 4M38 10l-4 4M14 34l-4 4"/></g><circle cx="24" cy="24" r="9" fill="#ffd84d"/></svg>',
        wet: '<svg viewBox="0 0 48 48" fill="none"><path d="M24 6c6 9 11 15 11 22a11 11 0 01-22 0c0-7 5-13 11-22z" fill="#cfe7ff"/><path d="M19 30a5 5 0 005 5" stroke="#1d6fd1" stroke-width="2.4" stroke-linecap="round"/></svg>',
        even: '<svg viewBox="0 0 48 48" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M24 8v32M12 40h24M10 16h28M10 16l-5 11a6 6 0 0010 0zM38 16l-5 11a6 6 0 0010 0z"/></svg>',
    };

    /* ---- paint the answer ---- */
    const calc = () => {
        const c = read(), why = missing(c);
        try { localStorage.setItem(LS, JSON.stringify(c)); } catch (e) {}
        $('bbEmpty').hidden = !why; $('bbRes').hidden = !!why;
        if (why) { $('bbEmptySay').textContent = why; st.last = null; return; }
        const r = compute(c);
        st.last = { c, r };
        // The verdict.
        const v = $('bbVerdict');
        if (st.verdict !== r.verdict) {
            v.className = 'bb-verdict is-' + r.verdict;
            if (!still()) { void v.offsetWidth; v.classList.add('is-pop'); }
            $('bbVic').innerHTML = ICON[r.verdict];
            st.verdict = r.verdict;
        }
        const gap = peso(Math.abs(r.diff));
        $('bbVKick').textContent = r.verdict === 'dry' ? 'Bilad wins' : r.verdict === 'wet' ? 'Benta wins' : 'Too close to call';
        $('bbVHead').textContent = r.verdict === 'dry' ? 'Dry it first, then sell' : r.verdict === 'wet' ? 'Sell it wet' : 'Both leave about the same';
        $('bbVSay').textContent = r.verdict === 'dry' ? 'Drying leaves you about ' + gap + ' more, after the drying costs.'
            : r.verdict === 'wet' ? 'Selling wet leaves you about ' + gap + ' more. The drying costs and the weight lost eat up the better dry price.'
            : 'The difference is only about ' + gap + '. Sell wet to save the work and the rain risk, or dry if you have the time and the sun.';
        // By the weight of a sack, as a range: both ends, and whether the answer turns inside it.
        const R = r.range, vr = $('bbVRange');
        if (R) {
            const kgW = (x) => num(x, Math.round(x * 10) % 10 ? 1 : 0);
            const endSay = (x, kg) => 'at ' + kgW(kg) + ' kg a dry sack, ' + (x.verdict === 'dry' ? 'drying leaves about ' + peso(Math.abs(x.diff)) + ' more' : x.verdict === 'wet' ? 'selling wet leaves about ' + peso(Math.abs(x.diff)) + ' more' : 'both leave about the same');
            vr.innerHTML = (R.lo.verdict !== R.hi.verdict ? '<strong>It turns inside your range.</strong> ' : '') + esc(endSay(R.lo, c.dryKgLo).replace(/^a/, 'A') + '; ' + endSay(R.hi, c.dryKgHi) + '.')
                + (r.breakEvenSackKg != null ? ' Drying pays once a dry sack weighs more than <strong>' + esc(num(r.breakEvenSackKg, 1)) + ' kg</strong>.' : '');
            vr.hidden = false;
        } else vr.hidden = true;
        // The two ways.
        $('bbWayWet').classList.toggle('is-win', r.verdict === 'wet'); $('bbWayDry').classList.toggle('is-win', r.verdict === 'dry');
        countTo($('bbNetWet'), r.netWet, (x) => peso(x)); countTo($('bbNetDry'), r.netDry, (x) => peso(x));
        $('bbNetWet').classList.toggle('is-neg', r.netWet < 0); $('bbNetDry').classList.toggle('is-neg', r.netDry < 0);
        const line = (k, v2, minus) => '<div' + (minus ? ' class="is-minus"' : '') + '><span>' + esc(k) + '</span><b>' + v2 + '</b></div>';
        const hLine = r.hCash > 0 ? line('Harvester', '−' + peso(r.hCash), true) : '';
        $('bbLinesWet').innerHTML = line(num(r.wetKg, 0) + ' kg × ' + peso(c.priceWet, 2), peso(r.wetGross)) + hLine;
        $('bbLinesDry').innerHTML = line(num(r.dryKg, 0) + ' kg × ' + peso(c.priceDry, 2), peso(r.dryGross))
            + (r.labor > 0 ? line('Drying labor', '−' + peso(r.labor), true) : '') + (c.otherCost > 0 ? line('Other drying costs', '−' + peso(c.otherCost), true) : '') + hLine;
        // The bars, against the larger of the two.
        const top = Math.max(1, r.netWet, r.netDry);
        $('bbBarWet').style.width = Math.max(0, r.netWet / top * 100) + '%'; $('bbBarDry').style.width = Math.max(0, r.netDry / top * 100) + '%';
        // The weight.
        const w = Math.max(1, r.wetKg);
        $('bbSDry').style.flexBasis = (r.dryKg / w * 100) + '%'; $('bbSWater').style.flexBasis = (r.waterKg / w * 100) + '%'; $('bbSLoss').style.flexBasis = (r.lossKg / w * 100) + '%';
        $('bbWHead').textContent = 'Drying takes ' + num(r.shrinkPct, 1) + '% off the weight';
        $('bbWKey').innerHTML = '<span><i style="background:#e0a43a"></i>' + num(r.dryKg, 0) + ' kg dry palay, about ' + num(r.drySacks, 1) + ' sacks of ' + B.drySackKg + ' kg</span>'
            + '<span><i style="background:#7cc0ff"></i>' + num(r.waterKg, 0) + ' kg of water dried out</span>'
            + (r.lossKg > 0 ? '<span><i style="background:#f87171"></i>' + num(r.lossKg, 0) + ' kg lost on the floor</span>' : '');
        // Where drying starts to pay.
        const be = r.breakEvenDry;
        $('bbBeSay').innerHTML = be != null
            ? 'Drying pays only if dry palay sells for more than <b>' + peso(be, 2) + '</b> a kilo. You put in ' + peso(c.priceDry, 2) + (c.priceDry >= be ? ', so drying is ahead by ' + peso(c.priceDry - be, 2) + ' a kilo.' : ', ' + peso(be - c.priceDry, 2) + ' a kilo short.')
                + (r.breakEvenWet != null && r.breakEvenWet > 0 ? ' Put the other way: selling wet wins once wet palay fetches more than ' + peso(r.breakEvenWet, 2) + ' a kilo.' : '')
            : 'No dry palay is left to sell after the harvester.';
        if (be != null) {
            const lo = Math.min(be, c.priceDry) * 0.85, hi = Math.max(be, c.priceDry) * 1.15, at = (x) => clamp((x - lo) / Math.max(0.01, hi - lo) * 100, 3, 97) + '%';
            $('bbGLab').style.left = at(c.priceDry); $('bbGLab').textContent = 'Your price ' + peso(c.priceDry, 2);
            $('bbGMark').style.left = at(be); $('bbGBe').style.left = at(be); $('bbGBe').textContent = 'Break even ' + peso(be, 2);
        }
        // Per hectare.
        $('bbPerHa').hidden = !r.areaHa;
        if (r.areaHa) $('bbPerHa').innerHTML = '<div>Yield<b>' + num(r.sacksPerHa, 1) + ' sacks/ha</b></div><div>Benta per ha<b>' + peso(r.netWetPerHa) + '</b></div><div>Bilad per ha<b>' + peso(r.netDryPerHa) + '</b></div>';
        // How it was counted.
        $('bbHow').innerHTML = [
            r.hSacks > 0 ? 'The harvester takes ' + num(r.hSacks, 1) + ' sacks at the field (worth about ' + peso(r.hWorth) + ' wet), so ' + num(r.left, 1) + ' sacks are yours.' : r.hCash > 0 ? 'The harvester is paid ' + peso(r.hCash) + ' in cash, the same whether you sell wet or dry.' : 'No harvester cost was put in.',
            num(r.left, 1) + ' sacks × ' + num(c.kgPerSack, 1) + ' kg = ' + num(r.wetKg, 0) + ' kg of wet palay.',
            c.dryBy === 'kg'
                ? 'A ' + num(c.kgPerSack, 1) + ' kg wet sack weighs ' + (c.dryKgHi > c.dryKgLo ? num(c.dryKgLo, 1) + ' to ' + num(c.dryKgHi, 1) + ' kg once dry, counted at ' + num((c.dryKgLo + c.dryKgHi) / 2, 1) + ' kg' : num(c.dryKgLo, 1) + ' kg once dry') + ', so drying keeps ' + num(r.dryKg / Math.max(1, r.wetKg) * 100, 1) + '% of the weight: ' + num(r.dryKg, 0) + ' kg of dry palay.'
                : 'Drying from ' + num(c.wetMoisture, 1) + '% to ' + num(c.dryMoisture, 1) + '% moisture keeps ' + num((100 - c.wetMoisture) / (100 - c.dryMoisture) * 100, 1) + '% of the weight, and ' + num(c.lossPct, 1) + '% more is lost while drying: ' + num(r.dryKg, 0) + ' kg of dry palay.',
            'Drying costs ' + peso(r.dryCost) + ': ' + peso(r.labor) + ' of labor' + (c.otherCost > 0 ? ' and ' + peso(c.otherCost) + ' more' : '') + '.',
            'Benta: ' + num(r.wetKg, 0) + ' kg × ' + peso(c.priceWet, 2) + (r.hCash ? ' less the harvester' : '') + ' = ' + peso(r.netWet) + '. Bilad: ' + num(r.dryKg, 0) + ' kg × ' + peso(c.priceDry, 2) + ' less the drying' + (r.hCash ? ' and the harvester' : '') + ' = ' + peso(r.netDry) + '.',
        ].map((x) => '<li>' + esc(x) + '</li>').join('');
    };
    ['bbSacks', 'bbKg', 'bbArea', 'bbH', 'bbLRate', 'bbLWorkers', 'bbLDays', 'bbOther', 'bbMWet', 'bbMDry', 'bbLoss', 'bbKgDryLo', 'bbKgDryHi', 'bbPWet', 'bbPDry'].forEach((id) => $(id).addEventListener('input', calc));
    // The wet sack is one figure in two places: the harvest card and the drying card.
    $('bbKg').addEventListener('input', () => { $('bbKgWet').value = $('bbKg').value; });
    $('bbKgWet').addEventListener('input', () => { $('bbKg').value = $('bbKgWet').value; calc(); });

    /* ---- put inputs back (a saved one, or the last one on this phone) ---- */
    const fill = (c) => {
        if (!c) return;
        const set = (id, v) => { $(id).value = v === null || v === undefined ? '' : v; };
        set('bbSacks', c.sacks); set('bbKg', c.kgPerSack); set('bbArea', c.area || ''); set('bbH', c.harvester || ''); set('bbLRate', c.laborRate || '');
        set('bbLWorkers', c.laborWorkers ?? 2); set('bbLDays', c.laborDays ?? 2); set('bbOther', c.otherCost || ''); set('bbMWet', c.wetMoisture); set('bbMDry', c.dryMoisture);
        set('bbLoss', c.lossPct); set('bbPWet', c.priceWet); set('bbPDry', c.priceDry); set('bbKgWet', c.kgPerSack);
        set('bbKgDryLo', c.dryKgLo ?? ''); set('bbKgDryHi', c.dryKgHi ?? ''); st.dryBy = c.dryBy === 'kg' ? 'kg' : 'pct'; paintDry(false);
        st.areaUnit = c.areaUnit === 'sqm' ? 'sqm' : 'ha'; tag('bbAreaUnit', AREA[st.areaUnit][0]);
        st.hMode = B.harvester[c.harvesterMode] ? c.harvesterMode : 'pct'; st.lMode = B.labor[c.laborMode] ? c.laborMode : 'sack';
        paintH(); paintL(); calc();
    };
    try { const last = JSON.parse(localStorage.getItem(LS) || 'null'); if (last && typeof last === 'object') fill(last); else { paintH(); paintL(); } } catch (e) { paintH(); paintL(); }
    $('bbReset').addEventListener('click', () => {
        ['bbSacks', 'bbArea', 'bbH', 'bbLRate', 'bbOther', 'bbPWet', 'bbPDry'].forEach((id) => { $(id).value = ''; });
        $('bbKg').value = $('bbKgWet').value = B.defaults.kgPerSack; $('bbMWet').value = B.defaults.wetMoisture; $('bbMDry').value = B.defaults.dryMoisture; $('bbLoss').value = B.defaults.lossPct;
        $('bbKgDryLo').value = $('bbKgDryHi').value = '';
        $('bbLWorkers').value = 2; $('bbLDays').value = 2; st.verdict = null; shown.clear();
        calc(); $('bbSacks').focus();
    });

    /* ---- the tabs and the saved list ---- */
    const LIST = { rows: [], page: 1, more: false };
    const tab = (saved) => {
        $('bbTabs').classList.toggle('is-saved', saved);
        $('bbTabCalc').setAttribute('aria-selected', String(!saved)); $('bbTabSaved').setAttribute('aria-selected', String(saved));
        $('bbTabCalc').tabIndex = saved ? -1 : 0; $('bbTabSaved').tabIndex = saved ? 0 : -1;
        $('bbPaneCalc').hidden = saved; $('bbPaneSaved').hidden = !saved;
        if (saved && !LIST.loaded) load(1);
    };
    $('bbTabCalc').addEventListener('click', () => tab(false));
    $('bbTabSaved').addEventListener('click', () => tab(true));
    const shelf = (d, append) => {
        LIST.rows = append ? LIST.rows.concat(d.history || []) : (d.history || []);
        LIST.more = !!d.hasMore; LIST.page = d.page || 1; LIST.loaded = true;
        $('bbTabN').textContent = LIST.rows.length ? (LIST.more ? LIST.rows.length + '+' : LIST.rows.length) : '';
        $('bbSEmpty').hidden = LIST.rows.length > 0; $('bbMore').hidden = !LIST.more;
        $('bbList').innerHTML = LIST.rows.map((r, i) => '<div class="bb-srow" style="animation-delay:' + Math.min(i, 8) * 40 + 'ms" data-id="' + r.id + '">'
            + '<span class="bb-date"><b>' + esc(r.day) + '</b><small>' + esc(r.month) + '</small></span>'
            + '<button type="button" class="bb-sopen" data-open="' + r.id + '"><b>' + esc(r.title) + '</b><small><span class="bb-chip is-' + esc(r.verdict) + '">' + ({ dry: 'Bilad', wet: 'Benta', even: 'Even' })[r.verdict] + '</span>'
            + (r.verdict === 'even' ? 'About the same' : 'About ' + peso(Math.abs(r.diff || 0)) + ' more') + ' · ' + esc(r.at) + '</small></button>'
            + '<button type="button" class="bb-sdel" data-del="' + r.id + '" aria-label="Delete"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg></button></div>').join('');
    };
    const load = async (page) => {
        try { const res = await window.api(B.urls.list + '?page=' + page, { method: 'GET' }); shelf(res.data, page > 1); }
        catch (err) { toast(err.message || 'The list could not load.', 'error'); }
    };
    $('bbMore').addEventListener('click', () => load(LIST.page + 1));
    $('bbList').addEventListener('click', async (e) => {
        const o = e.target.closest('[data-open]');
        if (o) {
            try { const res = await window.api(B.urls.one + '/' + o.dataset.open, { method: 'GET' }); fill(res.data.params); tab(false); window.scrollTo({ top: 0, behavior: still() ? 'auto' : 'smooth' }); }
            catch (err) { toast(err.message, 'error'); }
            return;
        }
        const d = e.target.closest('[data-del]');
        if (!d) return;
        const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this calculation?', message: 'It leaves your saved list for good.', confirmText: 'Delete' }) : confirm('Delete this calculation?');
        if (!ok) return;
        const row = d.closest('.bb-srow'); row && row.classList.add('is-gone');
        try { const res = await window.api(B.urls.del + '/' + d.dataset.del, { method: 'DELETE' }); setTimeout(() => shelf(res.data, false), still() ? 0 : 260); }
        catch (err) { row && row.classList.remove('is-gone'); toast(err.message, 'error'); }
    });
    $('bbSave').addEventListener('click', async () => {
        if (!st.last) return;
        const btn = $('bbSave');
        btn.disabled = true;
        try {
            const res = await window.api(B.urls.save, { method: 'POST', body: st.last.c });
            shelf(res.data, false);
            toast('Saved: ' + res.data.title, 'success');
        } catch (err) { toast(err.message || 'It could not be saved.', 'error'); }
        finally { btn.disabled = false; }
    });
    // The count on the Saved tab, without opening it.
    const boot = () => { if (window.api) load(1); else document.addEventListener('DOMContentLoaded', () => load(1), { once: true }); };
    boot();
})();
</script>
@endpush
