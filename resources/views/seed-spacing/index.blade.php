@extends('layouts.app')
@section('title', 'Seed Count and Distance Calculator')
@section('page-title', 'Seed Count and Distance')
@section('page-subtitle', 'Spacing, seeds, stand and yield')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))
@section('body-class', 'hide-tabbar no-footer')

{{-- Seed Count and Distance Calculator (2026-10-19, the owner): play with the
     distance between rows and between plants and see the seeds a hectare or
     a lot needs (a range, with the replant reserve and the spread of seed
     sizes), the final stand after germination and thinning, and the yield it
     can carry; or the other way round: the spacing for the seeds you have, or
     the spacing and seeds for the stand you want. Warnings say when the stand
     is thin, crowded or in the danger zone, when a hill holds too few or too
     many seeds, and when the spacing or the germination is off.

     The arithmetic is App\Support\SeedSpacing's, the same here between the
     calc:start and calc:end marks. Saved calculations live on the Saved tab
     (as_plant_analyses, 'seedcalc'). Every choice is a tag that opens a
     sheet; nothing changes mode or turns a page by itself. No Anee. --}}
@push('head')
<style>
    .sc-wrap { --sc-e: cubic-bezier(.22,1,.36,1); max-width: 70rem; margin: 0 auto; padding-bottom: calc(5rem + env(safe-area-inset-bottom)); }
    .sc-wrap [hidden], .sc-dock[hidden] { display: none !important; }

    /* ---- the hero: seeds dropping into a row, and the tape between them ---- */
    .sc-hero { position: relative; overflow: hidden; display: flex; gap: 1rem; align-items: center; padding: 1.1rem 1.2rem 1.35rem; border-radius: 1.4rem; color: #eef7e4;
        background: radial-gradient(120% 150% at 100% 0%, #8cc152 0%, #3f7a22 48%, #1c3a0c 100%); }
    .sc-hero::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 6px;
        background: repeating-linear-gradient(90deg, #f5c518 0 2px, transparent 2px 12px), #6b4a1f; }
    .sc-art { position: relative; flex: none; width: 5.6rem; height: 5.2rem; }
    .sc-art svg { width: 100%; height: 100%; overflow: visible; }
    .sc-drop { animation: scDrop 2.8s var(--sc-e) infinite; }
    .sc-drop.is-2 { animation-delay: .9s; } .sc-drop.is-3 { animation-delay: 1.8s; }
    @keyframes scDrop { 0% { transform: translateY(-14px); opacity: 0; } 25% { opacity: 1; } 55% { transform: translateY(0); opacity: 1; } 80%, 100% { transform: translateY(0); opacity: 0; } }
    .sc-sprout { transform-origin: 50% 100%; animation: scGrow 4.2s var(--sc-e) infinite; }
    @keyframes scGrow { 0%, 100% { transform: scaleY(.9); } 50% { transform: scaleY(1.06); } }
    .sc-hero h2 { font-family: var(--font-heading); font-size: 1.22rem; font-weight: 800; line-height: 1.2; color: #fff; }
    .sc-hero p { margin-top: .3rem; font-size: .88rem; line-height: 1.5; color: #dcefc9; }

    /* ---- the tabs ---- */
    .sc-tabs { position: relative; display: grid; grid-template-columns: 1fr 1fr; padding: .3rem; margin-top: 1rem; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .sc-tab-ind { position: absolute; top: .3rem; bottom: .3rem; left: .3rem; width: calc(50% - .3rem); border-radius: .75rem; background: #4a7c2a; box-shadow: 0 6px 14px -8px rgb(45 80 22 / .8); transition: transform .28s var(--sc-e); }
    .sc-tabs.is-saved .sc-tab-ind { transform: translateX(100%); }
    .sc-tab { position: relative; z-index: 1; min-height: 2.6rem; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; border-radius: .75rem; font-weight: 800; font-size: .9rem; color: var(--color-gray-600); transition: color .28s var(--sc-e); }
    .sc-tab[aria-selected="true"] { color: #fff; }
    .sc-tab-n { min-width: 1.35rem; padding: .05rem .4rem; border-radius: 999px; font-size: .7rem; font-weight: 900; color: #2d5016; background: #e4efd4; }
    .sc-tab-n:empty { display: none; }

    /* ---- the layout: the questions, and the answer beside them on a wide screen ---- */
    .sc-grid { display: grid; gap: 1rem; margin-top: 1rem; align-items: start; }
    @media (min-width: 1024px) { .sc-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr); } .sc-out { position: sticky; top: calc(var(--app-head, 64px) + 1rem); max-height: calc(100vh - var(--app-head, 64px) - 2rem); overflow-y: auto; overscroll-behavior: contain; scrollbar-width: thin; padding-bottom: .5rem; } }
    .sc-card { padding: 1rem 1.05rem 1.1rem; border-radius: 1.25rem; background: var(--color-white); border: 1px solid var(--color-gray-200); animation: scIn .4s var(--sc-e) both; }
    .sc-card + .sc-card { margin-top: .85rem; }
    @keyframes scIn { from { opacity: 0; transform: translateY(8px); } }
    .sc-card h3 { display: flex; align-items: center; gap: .55rem; font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; color: var(--color-gray-900); }
    .sc-card h3 i { flex: none; width: 1.9rem; height: 1.9rem; border-radius: .7rem; display: grid; place-items: center; font-style: normal; font-size: .82rem; font-weight: 900; color: #2d5016; background: #e9f2de; }
    .sc-card > p { margin-top: .25rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-500); }
    .sc-row { display: grid; gap: .7rem; margin-top: .85rem; }
    @media (min-width: 520px) { .sc-row.is-2 { grid-template-columns: 1fr 1fr; } .sc-row.is-3 { grid-template-columns: 1fr 1fr 1fr; } }
    .sc-f { display: grid; gap: .3rem; min-width: 0; align-content: start; }
    .sc-f > span { font-size: .78rem; font-weight: 800; color: var(--color-gray-700); }
    .sc-f > small, .sc-fold > div > small { font-size: .72rem; line-height: 1.4; color: var(--color-gray-500); }
    .sc-fold { display: grid; grid-template-rows: 1fr; opacity: 1; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .sc-fold > div { display: grid; gap: .3rem; min-height: 0; min-width: 0; overflow: hidden; align-content: start; }
    .sc-fold > div > span { font-size: .78rem; font-weight: 800; color: var(--color-gray-700); }
    .sc-fold.is-off { grid-template-rows: 0fr; opacity: 0; }
    @media (prefers-reduced-motion: reduce) { .sc-fold { transition: none; } }
    .sc-in { position: relative; display: flex; align-items: center; }
    .sc-in input { width: 100%; min-width: 0; padding-right: 3.9rem; font-variant-numeric: tabular-nums; transition: background-color .28s var(--sc-e), border-color .28s var(--sc-e); }
    .sc-in input[readonly] { background: #f3f8ec; border-color: #c9dfb1; color: #2d5016; font-weight: 800; }
    .sc-in em { position: absolute; right: .75rem; font-style: normal; font-size: .76rem; font-weight: 800; color: var(--color-gray-400); pointer-events: none; }
    .sc-pair { display: flex; gap: .5rem; min-width: 0; }
    .sc-pair .sc-in { flex: 1 1 auto; min-width: 0; }
    .sc-pair .sc-in input { padding-right: .8rem; }
    .sc-tags { display: flex; flex-wrap: wrap; gap: .5rem; }
    .sc-tag { flex: none; display: inline-flex; align-items: center; justify-content: space-between; gap: .4rem; min-height: 2.75rem; padding: 0 .75rem; border-radius: .75rem; text-align: left;
        border: 1.5px solid var(--color-gray-200); background: var(--color-gray-50, #f9fafb); font-size: .84rem; font-weight: 800; color: var(--color-gray-800); cursor: pointer;
        transition: border-color .28s var(--sc-e), transform .28s var(--sc-e), background-color .28s var(--sc-e); }
    .sc-tag:hover { border-color: #86b556; transform: translateY(-1px); }
    .sc-tag svg { flex: none; width: .85rem; height: .85rem; color: var(--color-gray-400); }
    .sc-tag.is-wide { width: 100%; }
    .sc-tag > span { min-width: 0; }
    .sc-tag small { display: block; font-size: .72rem; font-weight: 600; color: var(--color-gray-500); }
    .sc-tag.is-crop { min-height: 3.3rem; }
    .sc-swap { animation: scSwap .32s var(--sc-e) both; }
    @keyframes scSwap { from { opacity: 0; transform: translateY(-4px); } }
    .sc-usual { display: flex; gap: .45rem; align-items: flex-start; margin-top: .75rem; padding: .6rem .7rem; border-radius: .9rem; background: #f6faf0; font-size: .76rem; line-height: 1.5; color: #3f5a2a; }
    .sc-usual svg { flex: none; width: 1rem; height: 1rem; margin-top: .12rem; color: #4a7c2a; }
    .sc-test { margin-top: .8rem; font-size: .78rem; color: var(--color-gray-600); }
    .sc-test summary { cursor: pointer; font-weight: 800; color: #2d5016; }
    .sc-test ol { margin-top: .45rem; padding-left: 1.1rem; display: grid; gap: .3rem; list-style: decimal; line-height: 1.5; }

    /* ---- the answer ---- */
    .sc-out { min-width: 0; }
    .sc-empty { display: flex; gap: .8rem; align-items: center; padding: 1.1rem; border-radius: 1.25rem; border: 1.5px dashed var(--color-gray-300, #d1d5db); color: var(--color-gray-500); font-size: .86rem; line-height: 1.5; background: var(--color-white); }
    .sc-empty svg { flex: none; width: 2.6rem; height: 2.6rem; }
    .sc-head { position: relative; overflow: hidden; display: flex; gap: .9rem; align-items: center; padding: 1.05rem 1.1rem; border-radius: 1.3rem; color: #fff;
        background: linear-gradient(135deg, #3d6823, #1f3a0e); box-shadow: 0 18px 40px -26px rgb(20 33 12 / .9); transition: background .45s var(--sc-e); }
    .sc-head.is-warn { background: linear-gradient(135deg, #a16207, #5b3705); }
    .sc-head.is-bad { background: linear-gradient(135deg, #b91c1c, #6b1010); }
    .sc-head.is-pop { animation: scPop .5s var(--sc-e) both; }
    @keyframes scPop { 0% { transform: scale(.97); opacity: .5; } 60% { transform: scale(1.012); opacity: 1; } 100% { transform: none; } }
    .sc-hic { flex: none; width: 3.2rem; height: 3.2rem; border-radius: 1rem; display: grid; place-items: center; background: rgb(255 255 255 / .16); }
    .sc-hic svg { width: 2rem; height: 2rem; }
    .sc-head small { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; opacity: .85; }
    .sc-head b { display: block; margin-top: .1rem; font-family: var(--font-heading); font-size: clamp(1.25rem, 3.8vw, 1.6rem); font-weight: 800; line-height: 1.15; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .sc-head p { margin-top: .25rem; font-size: .82rem; line-height: 1.45; opacity: .92; }
    .sc-box { margin-top: .75rem; padding: .9rem .95rem; border-radius: 1.15rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .sc-box h5 { display: flex; align-items: center; gap: .4rem; font-size: .8rem; font-weight: 900; color: var(--color-gray-800); }
    .sc-big { margin-top: .3rem; display: flex; flex-wrap: wrap; align-items: baseline; gap: .2rem .6rem; }
    .sc-big b { font-family: var(--font-heading); font-size: clamp(1.25rem, 3.4vw, 1.55rem); font-weight: 800; line-height: 1.1; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .sc-big span { font-size: .78rem; font-weight: 700; color: var(--color-gray-500); }
    .sc-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .18rem .55rem; border-radius: 999px; font-size: .68rem; font-weight: 900; letter-spacing: .03em; text-transform: uppercase; white-space: nowrap;
        transition: background-color .35s var(--sc-e), color .35s var(--sc-e); }
    .sc-chip.is-good { background: #e3f1d4; color: #2d5016; } .sc-chip.is-warn { background: #fdf0d2; color: #8a4b05; } .sc-chip.is-bad { background: #fde2e2; color: #9b1c1c; } .sc-chip.is-info { background: #e3eefc; color: #1550a0; }
    .sc-gauge { position: relative; margin: 1.6rem .2rem .2rem; }
    .sc-gbar { display: flex; height: .62rem; border-radius: 999px; overflow: hidden; }
    .sc-gbar span { height: 100%; transition: flex-basis .7s var(--sc-e); }
    .sc-gbar .z-sparse { background: #f3a3a3; } .sc-gbar .z-thin { background: #f8d48a; } .sc-gbar .z-good { background: #8fc463; } .sc-gbar .z-crowded { background: #f8d48a; } .sc-gbar .z-danger { background: #ee7a7a; }
    .sc-gmark { position: absolute; top: -1.35rem; transform: translateX(-50%); display: grid; justify-items: center; transition: left .7s var(--sc-e); pointer-events: none; }
    .sc-gmark b { font-size: .64rem; font-weight: 900; color: var(--color-gray-800); white-space: nowrap; }
    .sc-gmark i { width: 3px; height: 1.25rem; margin-top: .05rem; border-radius: 2px; background: #14210c; }
    .sc-gscale { position: relative; height: 1rem; margin-top: .3rem; font-size: .62rem; font-weight: 800; color: var(--color-gray-500); }
    .sc-gscale span { position: absolute; transform: translateX(-50%); white-space: nowrap; transition: left .7s var(--sc-e); }
    .sc-say { margin-top: .6rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); }
    .sc-field { margin-top: .6rem; border-radius: .9rem; overflow: hidden; background: #efe3cf; color: #4a3517; }
    .sc-field svg { display: block; width: 100%; height: auto; }
    .sc-field .f-in { animation: scFade .45s var(--sc-e) both; }
    @keyframes scFade { from { opacity: 0; } }
    .sc-key { display: flex; flex-wrap: wrap; gap: .3rem .9rem; margin-top: .5rem; font-size: .72rem; color: var(--color-gray-600); }
    .sc-key i { display: inline-block; width: .65rem; height: .65rem; margin-right: .3rem; border-radius: 999px; vertical-align: -.06rem; }
    .sc-flags { display: grid; gap: .5rem; margin-top: .75rem; }
    .sc-flag { display: flex; gap: .65rem; align-items: flex-start; padding: .7rem .8rem; border-radius: 1rem; border: 1.5px solid; animation: scIn .35s var(--sc-e) both; }
    .sc-flag.is-good { background: #f3f9ec; border-color: #cfe5b8; } .sc-flag.is-warn { background: #fffaf0; border-color: #f4d79a; } .sc-flag.is-bad { background: #fff5f5; border-color: #f5b5b5; } .sc-flag.is-info { background: #f3f8ff; border-color: #bfd6f6; }
    .sc-flag > i { flex: none; width: 1.7rem; height: 1.7rem; border-radius: .6rem; display: grid; place-items: center; font-style: normal; }
    .sc-flag.is-good > i { background: #4a7c2a; color: #fff; } .sc-flag.is-warn > i { background: #e19b17; color: #fff; } .sc-flag.is-bad > i { background: #c62828; color: #fff; } .sc-flag.is-info > i { background: #1d6fd1; color: #fff; }
    .sc-flag > i svg { width: 1rem; height: 1rem; }
    .sc-flag b { display: block; font-size: .84rem; font-weight: 900; color: var(--color-gray-900); }
    .sc-flag p { margin-top: .12rem; font-size: .76rem; line-height: 1.5; color: var(--color-gray-700); }
    .sc-nums { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-top: .75rem; }
    @media (min-width: 520px) { .sc-nums { grid-template-columns: 1fr 1fr 1fr; } }
    .sc-nums div { padding: .65rem .7rem; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); font-size: .68rem; font-weight: 700; color: var(--color-gray-500); min-width: 0; }
    .sc-nums div b { display: block; margin-top: .15rem; font-size: .95rem; font-weight: 800; color: var(--color-gray-900); font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .sc-nums div small { display: block; margin-top: .1rem; font-size: .66rem; font-weight: 600; color: var(--color-gray-500); }
    .sc-yield { background: #fffbea; border-color: #f6e3a1; }
    .sc-yield h5, .sc-yield .sc-big b { color: #4a3500; }
    .sc-yield .sc-big span, .sc-yield p { color: #6b5212; }
    .sc-yield p { margin-top: .35rem; font-size: .76rem; line-height: 1.5; }
    .sc-trial { margin-top: .85rem; padding-top: .75rem; border-top: 1px dashed #ecd99a; animation: scIn .35s var(--sc-e) both; }
    .sc-ybar { position: relative; height: .7rem; margin-top: .65rem; border-radius: 999px; background: #f6e8bb; overflow: hidden; }
    .sc-ybar span { position: absolute; top: 0; bottom: 0; left: 0; border-radius: 999px; background: linear-gradient(90deg, #f5c518, #e0a43a); transition: left .7s var(--sc-e), width .7s var(--sc-e); }
    .sc-how { margin-top: .75rem; font-size: .76rem; color: var(--color-gray-600); }
    .sc-how summary { cursor: pointer; font-weight: 800; color: var(--color-gray-700); }
    .sc-how ol { margin-top: .4rem; padding-left: 1.1rem; display: grid; gap: .25rem; list-style: decimal; line-height: 1.5; }
    .sc-acts { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1rem; }
    .sc-save { flex: 1 1 12rem; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 3rem; padding: 0 1.2rem; border-radius: 1rem;
        font-weight: 800; color: #3b2f00; background: #f5c518; box-shadow: 0 10px 22px -14px rgb(150 110 0 / .9); transition: transform .28s var(--sc-e), box-shadow .28s var(--sc-e), opacity .28s var(--sc-e), background-color .28s var(--sc-e); }
    .sc-save:hover { background: #ffd23f; transform: translateY(-1px); box-shadow: 0 14px 26px -14px rgb(150 110 0 / .9); }
    .sc-save:disabled { opacity: .5; transform: none; cursor: default; }
    .sc-save svg { width: 1.1rem; height: 1.1rem; }
    .sc-reset { min-height: 3rem; padding: 0 1rem; border-radius: 1rem; font-weight: 800; color: var(--color-gray-600); border: 1.5px solid var(--color-gray-200); background: var(--color-white); }

    /* ---- the lot and the season (2026-10-19) ---- */
    .sc-opt-w { margin-left: .3rem; font-size: .68rem; font-weight: 700; color: var(--color-gray-400); }
    .sc-x { width: 2.75rem; justify-content: center; padding: 0; }
    .sc-pair > .sc-tag.is-wide { width: auto; flex: 1 1 auto; min-width: 0; }
    .sc-nolot { justify-self: start; margin-top: .1rem; font-size: .76rem; font-weight: 800; color: #2d5016; text-decoration: underline; text-underline-offset: 3px; }
    .sc-fs-lot { margin-top: .35rem; font-size: .78rem; line-height: 1.5; color: var(--color-gray-600); }
    .sc-fs-season { margin-top: .55rem; padding: .7rem .8rem; border-radius: .9rem; background: #f3f8ff; font-size: .78rem; line-height: 1.6; color: #24405f; transition: opacity .28s var(--sc-e); }
    .sc-fs-season:empty { display: none; }
    .sc-fs-season.is-busy { opacity: .6; }
    .sc-enso { display: inline-block; margin-top: .35rem; padding: .12rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 800; background: #fdf0d2; color: #8a4b05; }
    .sc-enso.is-wet { background: #e3eefc; color: #1550a0; }
    .sc-fs-g { margin-top: .75rem; font-size: .68rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-500); }
    .sc-pl { display: flex; gap: .6rem; align-items: flex-start; margin-top: .45rem; font-size: .8rem; line-height: 1.45; color: var(--color-gray-800); animation: scIn .35s var(--sc-e) both; }
    .sc-pl > b { flex: none; min-width: 3.2rem; padding: .12rem .4rem; border-radius: .55rem; text-align: center; font-size: .72rem; font-weight: 900; background: #fdf0d2; color: #8a4b05; font-variant-numeric: tabular-nums; }
    .sc-pl > b.is-bad { background: #fde2e2; color: #9b1c1c; }
    .sc-pl small { display: block; margin-top: .05rem; font-size: .72rem; color: var(--color-gray-500); }
    .sc-pl i { font-style: normal; font-size: .62rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--color-gray-400); margin-left: .3rem; }
    .sc-fs-note { margin-top: .55rem; font-size: .74rem; line-height: 1.5; color: var(--color-gray-500); }
    .sc-fs-cmp { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-top: .85rem; }
    .sc-fs-cmp:empty { display: none; }
    .sc-fs-cmp div { padding: .6rem .7rem; border-radius: .9rem; border: 1px solid var(--color-gray-200); font-size: .68rem; font-weight: 700; color: var(--color-gray-500); min-width: 0; }
    .sc-fs-cmp div b { display: block; margin-top: .12rem; font-size: .92rem; font-weight: 800; color: var(--color-gray-900); font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .sc-fs-cmp div span { display: block; margin-top: .1rem; font-size: .7rem; font-weight: 600; color: var(--color-gray-500); }

    /* ---- the seed, said back; the range (2026-10-19) ---- */
    .sc-seedl { display: grid; gap: .45rem; margin-top: .8rem; }
    .sc-seedl li { display: flex; gap: .55rem; align-items: flex-start; padding: .55rem .7rem; border-radius: .85rem; background: #f6faf0; font-size: .78rem; line-height: 1.45; color: #34502a; animation: scIn .3s var(--sc-e) both; }
    .sc-seedl li b { flex: none; min-width: 3rem; font-variant-numeric: tabular-nums; color: #2d5016; }
    .sc-seedl li small { display: block; font-size: .7rem; color: var(--color-gray-500); }
    .sc-rg { display: grid; gap: .45rem; margin-top: .55rem; }
    .sc-rg div { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .1rem .6rem; padding: .55rem .7rem; border-radius: .85rem; background: var(--color-gray-50, #f9fafb); }
    .sc-rg div span { font-size: .74rem; font-weight: 800; color: var(--color-gray-600); }
    .sc-rg div b { font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .sc-rg div b.is-swap { animation: scSwap .35s var(--sc-e) both; }
    .sc-two2 { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-top: .6rem; }
    .sc-two2 > div { padding: .65rem .7rem; border-radius: .95rem; border: 1.5px solid var(--color-gray-200); min-width: 0; transition: border-color .28s var(--sc-e); }
    .sc-two2 > div.is-you { border-color: #e8c35a; background: #fffbea; }
    .sc-two2 h6 { font-size: .68rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); }
    .sc-two2 p { margin-top: .3rem; font-size: .76rem; line-height: 1.4; color: var(--color-gray-700); }
    .sc-two2 p b { font-weight: 800; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .sc-two2 ul { margin-top: .4rem; display: grid; gap: .2rem; font-size: .7rem; line-height: 1.4; color: var(--color-gray-500); }

    /* ---- what to buy: the bags, cans or packs (2026-10-19) ---- */
    .sc-buy { border-color: #d8e6c4; background: linear-gradient(180deg, #f8fbf3, var(--color-white)); animation: scIn .28s var(--sc-e) both; }
    .sc-buy-row { display: flex; align-items: center; gap: .7rem; margin-top: .45rem; }
    .sc-buy-ic { flex: none; width: 2.7rem; height: 2.7rem; border-radius: .9rem; display: grid; place-items: center; background: #eaf3df; color: #3f7a22; }
    .sc-buy-ic svg { width: 1.6rem; height: 1.6rem; }
    .sc-buy .sc-big { margin-top: 0; }
    .sc-buy .sc-big b.is-swap { animation: scSwap .28s var(--sc-e) both; }
    .sc-buy #scPack { margin-top: .7rem; }

    /* ---- the phone dock: the answer in one line while the form scrolls ---- */
    .sc-dock { position: fixed; left: .75rem; right: .75rem; bottom: calc(.75rem + env(safe-area-inset-bottom)); z-index: 40; display: flex; align-items: center; gap: .6rem; padding: .55rem .6rem .55rem .9rem; border-radius: 1.1rem;
        color: #fff; background: rgb(28 52 14 / .96); box-shadow: 0 14px 34px -14px rgb(0 0 0 / .55); transition: opacity .28s var(--sc-e), transform .28s var(--sc-e); }
    .sc-dock.is-off { opacity: 0; transform: translateY(1.2rem); pointer-events: none; }
    .sc-dock span { flex: 1 1 auto; min-width: 0; font-size: .8rem; line-height: 1.3; }
    .sc-dock span b { display: block; font-size: .95rem; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sc-dock button { flex: none; min-height: 2.4rem; padding: 0 .9rem; border-radius: .8rem; font-weight: 800; font-size: .82rem; color: #3b2f00; background: #f5c518; }
    @media (min-width: 1024px) { .sc-dock { display: none !important; } }

    /* ---- the saved list ---- */
    .sc-list { display: grid; gap: .6rem; margin-top: 1rem; }
    .sc-srow { display: flex; align-items: center; gap: .75rem; padding: .75rem .8rem; border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); animation: scIn .35s var(--sc-e) both;
        transition: opacity .28s var(--sc-e), transform .28s var(--sc-e), border-color .28s var(--sc-e); }
    .sc-srow.is-gone { opacity: 0; transform: translateX(12px); }
    .sc-srow:hover { border-color: #b9d39c; }
    .sc-date { flex: none; width: 2.9rem; padding: .3rem 0; border-radius: .8rem; text-align: center; background: #f3f8ec; color: #2d5016; line-height: 1; }
    .sc-date b { display: block; font-size: 1.05rem; font-weight: 900; } .sc-date small { font-size: .62rem; font-weight: 800; text-transform: uppercase; }
    .sc-sopen { flex: 1 1 auto; min-width: 0; text-align: left; }
    .sc-sopen b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .sc-sopen small { display: block; margin-top: .15rem; font-size: .74rem; color: var(--color-gray-500); }
    .sc-sopen .sc-chip { margin-right: .35rem; }
    .sc-sdel { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; color: var(--color-gray-400); }
    .sc-sdel:hover { color: #b91c1c; background: #fef2f2; }
    .sc-sdel svg { width: 1.05rem; height: 1.05rem; }
    .sc-sempty { margin-top: 1rem; padding: 1.4rem 1.1rem; border-radius: 1.2rem; text-align: center; font-size: .86rem; color: var(--color-gray-500); background: var(--color-white); border: 1.5px dashed var(--color-gray-200); }
    .sc-more { margin: .8rem auto 0; display: block; padding: .55rem 1.1rem; border-radius: .9rem; font-weight: 800; font-size: .84rem; color: #2d5016; background: #eef5e6; }

    /* ---- the choice sheets ---- */
    .sc-opts { display: grid; gap: .5rem; }
    .sc-opt { display: flex; align-items: center; gap: .75rem; width: 100%; padding: .75rem .9rem; border-radius: 1rem; text-align: left; border: 1.5px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .28s var(--sc-e), background-color .28s var(--sc-e), transform .28s var(--sc-e), opacity .28s var(--sc-e); }
    .sc-opt:hover { border-color: #b9d39c; transform: translateY(-1px); }
    .sc-opt.is-on { border-color: #4a7c2a; background: #f3f8ec; }
    .sc-opt span { flex: 1 1 auto; min-width: 0; }
    .sc-opt b { display: block; font-size: .92rem; font-weight: 800; color: var(--color-gray-900); }
    .sc-opt small { display: block; margin-top: .1rem; font-size: .76rem; color: var(--color-gray-500); }
    .sc-opt > i { flex: none; width: 1.4rem; height: 1.4rem; border-radius: 999px; border: 2px solid #cbd5c1; display: grid; place-items: center; transition: background-color .28s var(--sc-e), border-color .28s var(--sc-e); }
    .sc-opt.is-on > i { background: #4a7c2a; border-color: #4a7c2a; }
    .sc-opt > i::after { content: ''; width: .45rem; height: .45rem; border-radius: 999px; background: #fff; transform: scale(0); transition: transform .28s var(--sc-e); }
    .sc-opt.is-on > i::after { transform: scale(1); }
    .sc-ghead { margin: .5rem 0 0; font-size: .7rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-500); }
    .sc-ghead:first-child { margin-top: 0; }
    .sc-find { position: sticky; top: 0; z-index: 1; padding-bottom: .6rem; background: var(--color-white); }
    .sc-find input { width: 100%; }
    .sc-none { padding: 1rem; text-align: center; font-size: .84rem; color: var(--color-gray-500); }

    /* ---- dark ---- */
    html.dark .sc-tabs, html.dark .sc-card, html.dark .sc-box, html.dark .sc-nums div, html.dark .sc-empty, html.dark .sc-srow, html.dark .sc-sempty, html.dark .sc-opt, html.dark .sc-reset, html.dark .sc-find { background: #151b12; border-color: #2b3a1c; }
    html.dark .sc-card h3, html.dark .sc-big b, html.dark .sc-nums div b, html.dark .sc-sopen b, html.dark .sc-opt b, html.dark .sc-box h5, html.dark .sc-flag b, html.dark .sc-gmark b { color: #e8efe1; }
    html.dark .sc-card h3 i, html.dark .sc-date { background: #22301a; color: #cfe3bd; }
    html.dark .sc-tag { background: #1b2416; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .sc-in input[readonly] { background: #1f2d16; border-color: #3c5527; color: #cfe3bd; }
    html.dark .sc-usual { background: #1b2416; color: #cfe3bd; } html.dark .sc-usual svg { color: #9cc874; }
    html.dark .sc-test summary { color: #b9d99c; }
    html.dark .sc-gmark i { background: #fff; }
    html.dark .sc-field { background: #2a2216; color: #f0dfbf; }
    html.dark .sc-flag.is-good { background: #17220f; border-color: #2f4a1c; } html.dark .sc-flag.is-warn { background: #2a210c; border-color: #5a4515; }
    html.dark .sc-flag.is-bad { background: #2a1212; border-color: #5c2424; } html.dark .sc-flag.is-info { background: #101d2e; border-color: #23426a; }
    html.dark .sc-flag p { color: #c9d4bf; }
    html.dark .sc-chip.is-good { background: #263a17; color: #cfe8b3; } html.dark .sc-chip.is-warn { background: #3d2f0d; color: #f6d68a; } html.dark .sc-chip.is-bad { background: #431818; color: #f7b4b4; } html.dark .sc-chip.is-info { background: #13294a; color: #a9c8f5; }
    html.dark .sc-yield { background: #2a240f; border-color: #4a3f17; } html.dark .sc-yield h5, html.dark .sc-yield .sc-big b { color: #f6e7b3; } html.dark .sc-yield .sc-big span, html.dark .sc-yield p { color: #e5d391; }
    html.dark .sc-ybar { background: #3d3415; }
    html.dark .sc-trial { border-top-color: #5a4c1c; }
    html.dark .sc-opt.is-on { background: #1f2d16; }
    html.dark .sc-tab-n { background: #2b3a1c; color: #d5e3c5; }
    html.dark .sc-more { background: #22301a; color: #cfe3bd; }
    html.dark .sc-fs-season { background: #101d2e; color: #c8daf3; }
    html.dark .sc-pl { color: #d5dfcb; }
    html.dark .sc-pl > b { background: #3d2f0d; color: #f6d68a; } html.dark .sc-pl > b.is-bad { background: #431818; color: #f7b4b4; }
    html.dark .sc-enso { background: #3d2f0d; color: #f6d68a; } html.dark .sc-enso.is-wet { background: #13294a; color: #a9c8f5; }
    html.dark .sc-fs-cmp div { border-color: #2b3a1c; } html.dark .sc-fs-cmp div b { color: #e8efe1; }
    html.dark .sc-nolot { color: #b9d99c; }
    html.dark .sc-seedl li { background: #1b2416; color: #cfe3bd; } html.dark .sc-seedl li b { color: #d9efc4; }
    html.dark .sc-rg div { background: #1b2416; } html.dark .sc-rg div b { color: #e8efe1; }
    html.dark .sc-two2 > div { border-color: #2b3a1c; } html.dark .sc-two2 > div.is-you { border-color: #6b5a1f; background: #2a240f; }
    html.dark .sc-two2 p { color: #c9d4bf; } html.dark .sc-two2 p b { color: #e8efe1; }
    html.dark .sc-buy { background: #151b12; border-color: #2f4a1c; } html.dark .sc-buy-ic { background: #22301a; color: #b9d99c; }

    @media (prefers-reduced-motion: reduce) {
        .sc-drop, .sc-sprout, .sc-card, .sc-srow, .sc-head.is-pop, .sc-swap, .sc-flag, .sc-field .f-in, .sc-pl { animation: none !important; }
        .sc-fs-season { transition: none !important; }
        .sc-trial, .sc-seedl li, .sc-rg div b.is-swap { animation: none !important; }
        .sc-two2 > div { transition: none !important; }
        .sc-buy, .sc-buy .sc-big b.is-swap { animation: none !important; }
        .sc-tab-ind, .sc-gbar span, .sc-gmark, .sc-gscale span, .sc-ybar span, .sc-dock, .sc-head, .sc-chip { transition: none !important; }
    }
    html.sm-still .sc-drop, html.sm-still .sc-sprout, html.sm-still .sc-field .f-in { animation: none !important; }
</style>
@endpush

@section('content')
@php
    $chev = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
    $infoIco = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v5M12 8h.01"/></svg>';
    // Built here: the json directive reads one argument, and an inline array is cut at its first comma.
    $scBoot = $boot + ['urls' => ['save' => route('seedcalc.save'), 'list' => route('seedcalc.list'), 'one' => url('/app/seed-count-distance/one'), 'del' => url('/app/seed-count-distance'),
        'variety' => route('seedcalc.variety'), 'context' => route('seedcalc.context')]];
@endphp
@include('partials.variety-pick')
@include('partials.farm-lots')
@include('partials.month-pick')
<script type="application/json" id="scBoot">@json($scBoot)</script>
<div class="sc-wrap">
    <section class="sc-hero">
        <div class="sc-art" aria-hidden="true">
            <svg viewBox="0 0 90 84">
                <path d="M2 66h86v16H2z" fill="#6b4a1f"/>
                <path d="M2 66c8-5 14-5 22 0s14 5 22 0 14-5 22 0 12 4 20 0v4H2z" fill="#86602c"/>
                <g class="sc-sprout"><path d="M18 66V52" stroke="#bfe58f" stroke-width="2.6" stroke-linecap="round"/><path d="M18 56c-6 0-9-4-9-9 6 0 9 4 9 9zM18 54c5-1 8-5 8-10-6 1-8 5-8 10z" fill="#9fd36a"/></g>
                <g class="sc-sprout" style="animation-delay:.6s"><path d="M45 66V50" stroke="#bfe58f" stroke-width="2.6" stroke-linecap="round"/><path d="M45 55c-7 0-10-5-10-10 7 0 10 5 10 10zM45 52c6-1 9-6 9-11-7 1-9 6-9 11z" fill="#9fd36a"/></g>
                <ellipse class="sc-drop" cx="72" cy="61" rx="3.2" ry="4.2" fill="#f5c518"/>
                <ellipse class="sc-drop is-2" cx="72" cy="61" rx="3.2" ry="4.2" fill="#f5c518"/>
                <path d="M18 76h54" stroke="#f5c518" stroke-width="2" stroke-linecap="round"/><path d="M18 72v8M72 72v8M45 73.5v5" stroke="#f5c518" stroke-width="2" stroke-linecap="round"/>
                <circle cx="66" cy="14" r="8" fill="#ffe27a"/>
            </svg>
        </div>
        <div>
            <h2>Seed Count and Distance Calculator{{ \App\Support\Tagalog::flag('seed-count-distance') }}</h2>
            <p>Play with the distance between rows and between plants, and see how many seeds your field needs, how many plants will stand, and the harvest they can carry. Or start from the seeds you have, or the stand you want, and see the spacing.</p>
        </div>
    </section>

    <div class="sc-tabs" id="scTabs" role="tablist" aria-label="Seed Count and Distance">
        <span class="sc-tab-ind" aria-hidden="true"></span>
        <button type="button" class="sc-tab" id="scTabCalc" role="tab" aria-selected="true" aria-controls="scPaneCalc">Calculator</button>
        <button type="button" class="sc-tab" id="scTabSaved" role="tab" aria-selected="false" aria-controls="scPaneSaved" tabindex="-1">Saved<span class="sc-tab-n" id="scTabN"></span></button>
    </div>

    <div id="scPaneCalc" role="tabpanel" aria-labelledby="scTabCalc">
    <div class="sc-grid">
        <div class="sc-form">
            {{-- 1. The crop --}}
            <section class="sc-card">
                <h3><i>1</i>Your crop</h3>
                <div class="sc-row is-2">
                    <button type="button" class="sc-tag is-wide is-crop" id="scCrop" aria-haspopup="dialog"><span><span class="sc-tag-t">Yellow corn, hybrid</span><small>Mais</small></span>{!! $chev !!}</button>
                    <button type="button" class="sc-tag is-wide is-crop" id="scVariety" aria-haspopup="dialog"><span><span class="sc-tag-t">Any variety</span><small>Optional: pick yours or type its name</small></span>{!! $chev !!}</button>
                </div>
                {{-- The lot (2026-10-19, the owner): its size, soil and water count in. Optional. --}}
                <div class="sc-row">
                    <div class="sc-f"><span>Which lot is it for?<small class="sc-opt-w">Optional</small></span>
                        <div id="scLots"></div>
                        <button type="button" class="sc-nolot" id="scNoLot" hidden>Use no lot</button>
                        <small>Its size, soil and water count in the stand and the harvest.</small></div>
                </div>
                <div class="sc-row is-2">
                    <div class="sc-f" id="scMethodF"><span>How it is planted</span>
                        <button type="button" class="sc-tag is-wide" id="scMethod" aria-haspopup="dialog"><span>Sown where it grows</span>{!! $chev !!}</button></div>
                    {{-- Asked only without a lot that has its size (2026-10-21, the owner): a lot's size is in its answers. --}}
                    <div class="sc-f sc-fold" id="scAreaF"><div><span>Lot size</span>
                        <div class="sc-pair">
                            <span class="sc-in"><input type="number" class="form-input" id="scArea" min="0" step="any" inputmode="decimal" placeholder="Like 0.5"></span>
                            <button type="button" class="sc-tag" id="scAreaUnit" aria-haspopup="dialog"><span>Hectares</span>{!! $chev !!}</button>
                        </div>
                        <small>Leave it empty to count one hectare.</small></div></div>
                </div>
                {{-- When it is planted: with a lot, twenty years of its weather, the ENSO outlook and the forecast. --}}
                <div class="sc-row is-2">
                    <div class="sc-f"><span>Planting month<small class="sc-opt-w">Optional</small></span>
                        <div class="sc-pair">
                            <button type="button" class="sc-tag is-wide" id="scMonth" aria-haspopup="dialog"><span>Not set</span>{!! $chev !!}</button>
                            <button type="button" class="sc-tag sc-x" id="scMonthX" aria-label="Clear the month" hidden>✕</button>
                        </div>
                        <small>With a lot, the month reads twenty years of its weather.</small></div>
                    <label class="sc-f"><span>Planting day<small class="sc-opt-w">Optional</small></span>
                        <span class="sc-in"><input type="number" class="form-input" id="scDay" min="1" max="31" step="1" inputmode="numeric" placeholder="15"></span>
                        <small>Empty: the 15th of the month.</small></label>
                </div>
                <p class="sc-usual" id="scCropNote">{!! $infoIco !!}<span></span></p>
            </section>

            {{-- 2. What to work out --}}
            <section class="sc-card">
                <h3><i>2</i>What do you want to work out?</h3>
                <div class="sc-row">
                    <button type="button" class="sc-tag is-wide" id="scMode" aria-haspopup="dialog"><span><span class="sc-tag-t">Seeds from my spacing</span><small id="scModeSay">Set the rows and the distance between plants</small></span>{!! $chev !!}</button>
                </div>
            </section>

            {{-- 3. The spacing, the seeds or the stand --}}
            <section class="sc-card">
                <h3><i>3</i><span id="scSpaceTitle">Your spacing</span>{{ \App\Support\Tagalog::flag('plant-spacing') }}</h3>
                <p id="scSpaceSay">Distances are from the center of one plant to the next.</p>
                <div class="sc-row bb-swap" id="scAmountBox" hidden>
                    <div class="sc-f"><span id="scAmountLabel">Seeds you have</span>
                        <div class="sc-pair">
                            <span class="sc-in"><input type="number" class="form-input" id="scAmount" min="0" step="any" inputmode="decimal" placeholder="Like 20"></span>
                            <button type="button" class="sc-tag" id="scAmountUnit" aria-haspopup="dialog"><span>Kilos</span>{!! $chev !!}</button>
                            <button type="button" class="sc-tag" id="scAmountPer" aria-haspopup="dialog"><span>A hectare</span>{!! $chev !!}</button>
                        </div>
                        <small id="scAmountHint"></small></div>
                </div>
                <div class="sc-row" id="scTargetBox" hidden>
                    <div class="sc-f"><span id="scTargetLabel">Plants you want at harvest</span>
                        <div class="sc-pair">
                            <span class="sc-in"><input type="number" class="form-input" id="scTarget" min="0" step="any" inputmode="numeric"><em id="scTargetUnit">plants</em></span>
                            <button type="button" class="sc-tag" id="scTargetPer" aria-haspopup="dialog"><span>A hectare</span>{!! $chev !!}</button>
                        </div>
                        <small id="scTargetHint"></small></div>
                </div>
                <div class="sc-row is-2">
                    <div class="sc-f"><span>Layout</span>
                        <button type="button" class="sc-tag is-wide" id="scLayout" aria-haspopup="dialog"><span>In rows</span>{!! $chev !!}</button></div>
                    <div class="sc-f" id="scRowSetF" hidden><span>Rows</span>
                        <button type="button" class="sc-tag is-wide" id="scRowSet" aria-haspopup="dialog"><span>I set my rows</span>{!! $chev !!}</button></div>
                </div>
                <div class="sc-row is-2" id="scSpaceBox">
                    <label class="sc-f" id="scRowF"><span>Between rows</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scRow" min="1" step="any" inputmode="decimal"><em>cm</em></span></label>
                    <label class="sc-f" id="scBedF" hidden><span>Bed and path, center to center</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scBed" min="10" step="any" inputmode="decimal"><em>cm</em></span></label>
                    <label class="sc-f" id="scBedRowsF" hidden><span>Rows on each bed</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scBedRows" min="1" max="12" step="1" inputmode="numeric"><em>rows</em></span></label>
                    <label class="sc-f" id="scPlantF"><span id="scPlantLabel">Between plants in the row</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scPlant" min="0.5" step="any" inputmode="decimal"><em>cm</em></span></label>
                </div>
                <p class="sc-usual" id="scUsual">{!! $infoIco !!}<span></span></p>
            </section>

            {{-- 4. A hill --}}
            <section class="sc-card">
                <h3><i>4</i><span id="scHillTitle">Seeds a hill</span>{{ \App\Support\Tagalog::flag('hill') }}</h3>
                <p id="scHillSay">A hill is one planting spot: the seeds dropped in one hole.</p>
                <div class="sc-row is-2">
                    <div class="sc-f"><span>How many a hill</span>
                        <button type="button" class="sc-tag is-wide" id="scHillMode" aria-haspopup="dialog"><span>Suggested for my germination</span>{!! $chev !!}</button></div>
                    <label class="sc-f"><span id="scPerHillLabel">Seeds a hill</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scPerHill" min="1" max="20" step="1" inputmode="numeric"><em id="scPerHillUnit">seeds</em></span></label>
                </div>
                <div class="sc-row is-2" id="scKeepBox">
                    <label class="sc-f"><span>Plants kept a hill after thinning{{ \App\Support\Tagalog::flag('thinning') }}</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scKeep" min="1" max="20" step="1" inputmode="numeric" placeholder="All"><em>plants</em></span>
                        <small id="scKeepHint">Leave it empty to keep all that come up.</small></label>
                </div>
            </section>

            {{-- 5. The seed (2026-10-19, the owner): germination and losses are no longer typed. They come
                 from the variety, else the kind of seed, else the crop, and are said back here. --}}
            <section class="sc-card">
                <h3><i>5</i>Your seed</h3>
                <p id="scSeedSay">What was counted for germination and losses. They come from your variety, else the kind of seed, else the usual for the crop.</p>
                <div class="sc-row is-2">
                    <div class="sc-f" id="scSeedTypeF"><span>What seed is it?</span>
                        <button type="button" class="sc-tag is-wide" id="scSeedType" aria-haspopup="dialog"><span><span class="sc-tag-t">Hybrid seed</span><small>A new bag each season</small></span>{!! $chev !!}</button></div>
                    <label class="sc-f" id="scWeightF"><span id="scWeightLabel">Weight of 1,000 seeds{{ \App\Support\Tagalog::flag('thousand-seed-weight') }}</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scWeight" min="0" step="any" inputmode="decimal"><em>g</em></span>
                        <small id="scWeightHint"></small></label>
                </div>
                <ul class="sc-seedl" id="scSeedLines"></ul>
                <details class="sc-test" id="scTest">
                    <summary>How to test your seeds at home{{ \App\Support\Tagalog::flag('germination-test') }}</summary>
                    <ol id="scTestSteps"></ol>
                </details>
            </section>

            {{-- 6. The harvest --}}
            <section class="sc-card">
                <h3><i>6</i>The harvest</h3>
                <p id="scYieldSay">For the potential yield. Change these to what your own field shows.</p>
                <div class="sc-row is-3" id="scTillerBox">
                    <label class="sc-f"><span>Productive tillers a hill{{ \App\Support\Tagalog::flag('productive-tillers') }}</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scTillers" min="0" step="any" inputmode="decimal"><em>tillers</em></span>
                        <small>The tillers that carry a panicle.</small></label>
                    <label class="sc-f"><span>Grains a panicle</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scGrains" min="1" step="any" inputmode="decimal"><em>grains</em></span></label>
                    <label class="sc-f"><span>Filled grains</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scFilled" min="1" max="100" step="any" inputmode="decimal"><em>%</em></span></label>
                </div>
                <div class="sc-row is-2" id="scEarBox" hidden>
                    <label class="sc-f"><span id="scEarsLabel">Ears a plant</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scEars" min="0" step="any" inputmode="decimal"><em id="scEarsUnit">ears</em></span>
                        <small id="scEarsHint">Under 1 when some plants carry none.</small></label>
                    <label class="sc-f"><span id="scPerEarLabel">Grain on one ear</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scPerEar" min="0" step="any" inputmode="decimal"><em>g</em></span>
                        <small id="scPerEarHint"></small></label>
                </div>
                <div class="sc-row is-2" id="scPlantYBox" hidden>
                    <label class="sc-f"><span id="scPerPlantLabel">Harvest from one plant</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scPerPlant" min="0" step="any" inputmode="decimal"><em id="scPerPlantUnit">kg</em></span>
                        <small id="scPerPlantHint"></small></label>
                </div>
                {{-- Sugarcane (2026-10-20): the clumps x the tillers a clump x the share that grows into millable cane x a stalk's weight. --}}
                <div class="sc-row is-3" id="scStalkBox" hidden>
                    <label class="sc-f"><span>Tillers a clump</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scStalks" min="0" step="any" inputmode="decimal"><em>tillers</em></span>
                        <small>Every shoot one sett sends up, counted when the clump is fullest, about 4 months after planting.</small></label>
                    <label class="sc-f"><span>Grow into millable cane</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scMillable" min="1" max="100" step="any" inputmode="decimal"><em>%</em></span>
                        <small>The tillers that become stalks the mill takes. The rest die back in the shade.</small></label>
                    <label class="sc-f"><span>Weight of one stalk</span>
                        <span class="sc-in"><input type="number" class="form-input" id="scStalkKg" min="0" step="any" inputmode="decimal"><em>kg</em></span>
                        <small>A millable stalk, topped and stripped, as the mill weighs it.</small></label>
                </div>
                <p class="sc-usual" id="scHLossLine">{!! $infoIco !!}<span></span></p>
            </section>
        </div>

        {{-- The answer, counted as you type. --}}
        <div class="sc-out" id="scOut" aria-live="polite">
            <div class="sc-empty" id="scEmpty" hidden>
                <svg viewBox="0 0 48 48" aria-hidden="true"><path d="M4 36h40v8H4z" fill="#a47a3f"/><path d="M14 36V24m0 0c-5 0-8-3-8-8 5 0 8 3 8 8zm0 0c4 0 7-3 7-8-4 0-7 3-7 8z" stroke="#4a7c2a" stroke-width="2.4" fill="#9fd36a" stroke-linecap="round"/><ellipse cx="34" cy="30" rx="3" ry="4" fill="#f5c518"/></svg>
                <span id="scEmptySay">Fill in the spacing and the answer shows here.</span>
            </div>
            <div id="scRes" hidden>
                <div class="sc-head" id="scHead">
                    <span class="sc-hic" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none"><path d="M6 38h36" stroke="#f5c518" stroke-width="3" stroke-linecap="round"/><path d="M6 33v10M42 33v10M24 35v6" stroke="#f5c518" stroke-width="2.6" stroke-linecap="round"/><path d="M14 30V18m0 0c-5 0-8-3-8-8 5 0 8 3 8 8zm0 0c4 0 7-3 7-8-4 0-7 3-7 8zM34 30V18m0 0c-5 0-8-3-8-8 5 0 8 3 8 8zm0 0c4 0 7-3 7-8-4 0-7 3-7 8z" stroke="#d9f2bd" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span><small id="scHKick">Seeds you need</small><b id="scHBig"></b><p id="scHSub"></p></span>
                </div>

                {{-- The range (2026-10-19, the owner): a usual field against your field this season. --}}
                <div class="sc-box sc-range" id="scRange">
                    <h5 id="scRangeTitle">From a usual field to yours this season</h5>
                    <div class="sc-rg" id="scRangeRows"></div>
                    <div class="sc-two2" id="scRangeTwo"></div>
                    <p class="sc-fs-note" id="scRangeNote"></p>
                </div>

                {{-- What to buy (2026-10-19, the owner): the bags, cans or packs, rounded up, from the seed count or the kilos. --}}
                <div class="sc-box sc-buy" id="scBuy" hidden>
                    <h5 id="scBuyTitle">What to buy</h5>
                    <div class="sc-buy-row">
                        <span class="sc-buy-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4h8l-1.2 3H9.2z"/><path d="M9.2 7C5.8 9 4.5 12.6 5.2 16.4 5.7 19 7.6 20.5 10 20.5h4c2.4 0 4.3-1.5 4.8-4.1.7-3.8-.6-7.4-4-9.4"/><path d="M9.5 13.5h5M10.5 16.5h3"/></svg></span>
                        <div class="sc-big"><b id="scBuyBig"></b><span id="scBuySub"></span></div>
                    </div>
                    <p class="sc-say" id="scBuySay"></p>
                    <button type="button" class="sc-tag is-wide" id="scPack" aria-haspopup="dialog" hidden><span><span class="sc-tag-t">Bag</span><small>Sold in other sizes too: tap to pick yours</small></span>{!! $chev !!}</button>
                </div>

                <div class="sc-box" id="scStandBox">
                    <h5><span id="scStandTitle">Final stand</span>{{ \App\Support\Tagalog::flag('final-stand') }} <span class="sc-chip is-good" id="scStandChip">Good</span></h5>
                    <div class="sc-big"><b id="scStandN">0</b><span id="scStandSub">plants a hectare</span></div>
                    <div class="sc-gauge" aria-hidden="true">
                        <div class="sc-gmark" id="scGMark"><b id="scGLab">You</b><i></i></div>
                        <div class="sc-gbar"><span class="z-sparse" id="scZ1"></span><span class="z-thin" id="scZ2"></span><span class="z-good" id="scZ3"></span><span class="z-crowded" id="scZ4"></span><span class="z-danger" id="scZ5"></span></div>
                        <div class="sc-gscale"><span id="scGLo"></span><span id="scGHi"></span></div>
                    </div>
                    <p class="sc-say" id="scEmptyLine"></p>
                </div>

                {{-- The field and the season, counted (2026-10-19). --}}
                <div class="sc-box" id="scFS" hidden>
                    <h5>Your field and season</h5>
                    <p class="sc-fs-lot" id="scFSLot"></p>
                    <div class="sc-fs-season" id="scFSSeason"></div>
                    <div id="scFSLines"></div>
                    <div class="sc-fs-cmp" id="scFSCmp"></div>
                </div>

                <div class="sc-box">
                    <h5 id="scFieldTitle">Your field, drawn to scale</h5>
                    <div class="sc-field" id="scField"></div>
                    <div class="sc-key" id="scFieldKey"></div>
                </div>

                <div class="sc-flags" id="scFlags"></div>

                <div class="sc-nums" id="scNums"></div>

                <div class="sc-box sc-yield" id="scYield">
                    <h5 id="scYTitle">Potential harvest</h5>
                    <div class="sc-big"><b id="scYBig"></b><span id="scYSub"></span></div>
                    <div class="sc-ybar" aria-hidden="true"><span id="scYBar"></span></div>
                    <p id="scYSay"></p>
                    <div class="sc-trial" id="scTrial" hidden>
                        <h5>What this variety gave in its trials</h5>
                        <div class="sc-big"><b id="scTrialBig"></b><span id="scTrialSub"></span></div>
                        <p id="scTrialSay" hidden></p>
                    </div>
                </div>

                <details class="sc-how"><summary>How it was counted</summary><ol id="scHow"></ol></details>
                <div class="sc-acts">
                    <button type="button" class="sc-save" id="scSave"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h11l3 3v13H5zM8 4v5h7V4M8 20v-6h8v6"/></svg>Save this calculation</button>
                    <button type="button" class="sc-reset" id="scReset">Start over</button>
                </div>
            </div>
        </div>
    </div>
    </div>

    <section id="scPaneSaved" role="tabpanel" aria-labelledby="scTabSaved" hidden>
        <div class="sc-list" id="scList"></div>
        <div class="sc-sempty" id="scSEmpty" hidden>No saved calculations yet. Work one out on the Calculator tab and save it, to look back at it later.</div>
        <button type="button" class="sc-more" id="scMore" hidden>Show more</button>
    </section>
</div>

<div class="sc-dock is-off" id="scDock" hidden>
    <span><b id="scDockBig"></b><span id="scDockSub"></span></span>
    <button type="button" id="scDockGo">See it</button>
</div>

{{-- One sheet for every choice, and one for the crops. --}}
<div class="sheet hidden" id="scPickSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="scPickTitle">Choose</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body"><div class="sc-opts" id="scPickRows"></div></div>
</div>
<div class="sheet hidden" id="scCropSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Choose your crop</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="sc-find"><input type="search" class="form-input" id="scCropFind" placeholder="Find a crop" autocomplete="off"></div>
        <div class="sc-opts" id="scCropRows"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const B = JSON.parse($('scBoot').textContent);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const still = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const toast = (m, t) => (window.toast ? window.toast(m, t) : alert(m));
    const num = (v, d = 0) => Number(v).toLocaleString('en-PH', { minimumFractionDigits: d, maximumFractionDigits: d });
    // A count shown as people read it: 1,234 or 1.2 million.
    const many = (v) => !isFinite(v) ? '0' : v >= 1e6 ? num(v / 1e6, v >= 1e7 ? 1 : 2) + ' million' : num(Math.round(v));
    const cmTxt = (v) => num(v, v < 10 ? 1 : 0);
    // A number with no trailing zeros ("4", "9.1", "1.42"), and a [low, high] the same way.
    const dec = (v, d) => Number(v).toLocaleString('en-PH', { maximumFractionDigits: d });
    const drng = (v, d) => v ? (Math.abs(v[0] - v[1]) < 1e-9 ? dec(v[0], d) : dec(v[0], d) + ' to ' + dec(v[1], d)) : '';
    const kgTxt = (v) => v >= 100 ? num(v, 0) : v >= 10 ? num(v, 1) : v >= 1 ? num(v, 2) : num(v, 3);
    const massTxt = (kg) => kg >= 1000 ? num(kg / 1000, kg >= 10000 ? 1 : 2) + ' t' : kgTxt(kg) + ' kg';
    const pct = (v) => num(v * 100, v < 0.1 ? 1 : 0) + '%';
    const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1);
    const val = (id) => { const s = $(id).value; return s === '' ? null : Number(s); };
    const LS = 'anee.seedcalc.last';
    const st = { crop: 'corn_yellow', method: 'direct', layout: 'rows', mode: 'seeds', areaUnit: 'ha', rowSet: 'set', amountUnit: 'kg', amountPer: 'ha', targetPer: 'ha', hillMode: 'auto', last: null, tone: null,
        variety: '', vRec: null, vy: null, trial: null, seedType: null, lotId: null, lot: null, month: null, ctx: null, ctxBusy: false, pack: null };
    const crop = () => B.crops[st.crop];

    /* calc:start (App\Support\SeedSpacing: read, missing, enough, suggest, parts, core, compute; App\Support\SeedPenalty and SeasonFactors) */
    const K = B.k;
    const perHillFor = (C, m) => (C[m] && C[m].perHill) || C.perHill;
    const weighs = (C, m) => m === 'piece' ? !!(C.pieceWt && C.pieceWt.length) : !!(C.tgw && C.tgw.length);
    const choose = (n, x) => { let r = 1; for (let i = 1; i <= x; i++) r = r * (n - x + i) / i; return r; };
    const keptMean = (n, p, k) => { let s = 0; for (let x = 0; x <= n; x++) s += choose(n, x) * Math.pow(p, x) * Math.pow(1 - p, n - x) * Math.min(x, k); return s; };
    const shortShare = (n, p, k) => { let s = 0; for (let x = 0; x < Math.min(k, n + 1); x++) s += choose(n, x) * Math.pow(p, x) * Math.pow(1 - p, n - x); return s; };
    const enough = (n, p, keep, ok = K.shortOk) => Math.pow(1 - p, n) <= ok + 1e-9 && (keep === null || keep < 2 || shortShare(n, p, keep) <= K.shortPart + 1e-9);
    const suggest = (C, m, p, keep) => {
        const ph = perHillFor(C, m);
        if (m === 'transplant') return { n: ph[1], ok: true };
        const top = Math.max(ph[2], keep ?? 1), ok = C.oneSeed ? K.shortOne : K.shortOk;
        for (let n = Math.max(keep ?? 1, ph[0]); n <= top; n++) {
            if (enough(n, p, keep, ok)) return { n: keep === null ? Math.max(n, ph[1]) : n, ok: true };
        }
        return { n: top, ok: false };
    };
    const spanOf = (o) => Array.isArray(o) && o.length >= 2 && isFinite(Number(o[0])) && isFinite(Number(o[1])) && o[0] !== null && o[1] !== null
        ? [Math.min(Number(o[0]), Number(o[1])), Math.max(Number(o[0]), Number(o[1]))] : (o !== null && o !== undefined && o !== '' && !Array.isArray(o) && isFinite(Number(o)) ? [Number(o), Number(o)] : null);
    // Sugarcane's tillers a clump and stalk weight: the variety's part, else what the shelf keeps beside its parts (SeedSpacing::STALK_EXTRA).
    const partsOf = (c, vy) => {
        const Y = B.crops[c.crop].yield, out = {}, ex = vy && vy.extra && typeof vy.extra === 'object' && !Array.isArray(vy.extra) ? vy.extra : {};
        B.yieldKeys.forEach((k) => {
            if (c[k] !== null && c[k] !== undefined) { out[k] = [c[k], c[k]]; return; }
            let v = spanOf(vy ? vy[k] : null) || (B.stalkExtra[k] ? spanOf(ex[B.stalkExtra[k]]) : null);
            if (!v && k === 'perPlant' && vy) {
                const f = spanOf(vy.fruits), w = spanOf(vy.fruitWt), u = Y.unit || 'kg';
                if (f && (u === 'count' || w)) v = u === 'count' ? f : [f[0] * w[0] / (u === 'kg' ? 1000 : 1), f[1] * w[1] / (u === 'kg' ? 1000 : 1)];
            }
            out[k] = v || (Y[k] !== undefined ? spanOf(Y[k]) : null);
        });
        return out;
    };
    const areaHaOf = (c) => c.area ? (c.areaUnit === 'sqm' ? c.area / 10000 : c.area) : null;
    const readIn = (inp) => {
        const key = B.crops[inp.crop] ? inp.crop : 'corn_yellow', C = B.crops[key];
        const n = (k, lo, hi, def = null) => { const v = inp[k]; return v === null || v === undefined || v === '' || !isFinite(Number(v)) ? def : Math.max(lo, Math.min(hi, Number(v))); };
        const pk = (k, dict, def) => Object.prototype.hasOwnProperty.call(dict, inp[k]) ? inp[k] : def;
        const method = C.methods.includes(inp.method) ? inp.method : C.methods[0];
        const bed = C.bed || K.bed, ph = perHillFor(C, method), Y = C.yield;
        const keepV = n('keep', 1, 20, C.keep === null ? null : C.keep);
        const vname = String(inp.variety ?? '').replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim();
        const c = {
            crop: key, method, variety: vname === '' ? null : vname.slice(0, 120),
            lotId: inp.lotId !== null && inp.lotId !== undefined && inp.lotId !== '' && isFinite(Number(inp.lotId)) && Math.trunc(Number(inp.lotId)) > 0 ? Math.trunc(Number(inp.lotId)) : null,
            month: /^\d{4}-(0[1-9]|1[0-2])$/.test(String(inp.month ?? '')) ? String(inp.month) : null,
            day: (() => { const d = n('day', 1, 31); return d === null ? null : Math.round(d); })(),
            layout: pk('layout', B.layouts, C.layout), mode: pk('mode', B.modes, 'seeds'),
            area: n('area', 0, 1000000), areaUnit: pk('areaUnit', B.areaUnits, 'ha'),
            row: n('row', 1, 5000, C.row[1]), plant: n('plant', 0.5, 5000, C.plant[1]),
            bed: n('bed', 10, 1000, bed[0]), bedRows: Math.round(n('bedRows', 1, 12, bed[1])),
            rowSet: pk('rowSet', B.rowSet, 'set'),
            amount: n('amount', 0, 1e12), amountUnit: pk('amountUnit', { count: 1, kg: 1, g: 1 }, 'count'), amountPer: pk('amountPer', B.per, 'ha'),
            target: n('target', 0, 1e9), targetPer: pk('targetPer', B.per, 'ha'),
            hillMode: pk('hillMode', B.hillMode, 'auto'), perHill: Math.round(n('perHill', 1, 20, ph[1])),
            keep: keepV === null ? null : Math.round(keepV),
            seedType: B.seedKinds[key] && Object.prototype.hasOwnProperty.call(B.seedKinds[key].types, inp.seedType ?? '') ? String(inp.seedType) : null,
            weight: n('weight', 0.01, 10000000),
            pack: /^(kg|g|seeds)-\d+(\.\d+)?-[a-z]{1,12}$/.test(String(inp.pack ?? '')) ? String(inp.pack) : null,
            tillers: n('tillers', 0, 100), grains: n('grains', 1, 500), filled: n('filled', 1, 100),
            ears: n('ears', 0, 5), perEar: n('perEar', 0, 5000), perPlant: n('perPlant', 0, 100000),
            stalks: n('stalks', 0, 100), millable: n('millable', 1, 100), stalkKg: n('stalkKg', 0, 20),
        };
        if (c.amountUnit !== 'count' && !weighs(C, method)) c.amountUnit = 'count';
        return c;
    };
    const missingOf = (c) => {
        const a = areaHaOf(c);
        if (c.mode === 'spacing') {
            if (!c.amount) return 'Fill in how many seeds you have.';
            if (c.amountPer === 'lot' && !a) return 'Fill in the lot size, or say the seeds are for a hectare.';
        } else if (c.mode === 'stand') {
            if (!c.target) return 'Fill in the stand you want.';
            if (c.targetPer === 'lot' && !a) return 'Fill in the lot size, or say the stand is for a hectare.';
        }
        return '';
    };
    // SeedSpacing::packs and packPick: the variety's packs first, then the crop's; the farmer's pick, else the usual one.
    const isNum = (v) => typeof v === 'number' ? isFinite(v) : typeof v === 'string' && v.trim() !== '' && isFinite(Number(v));
    const packNum = (v) => v.toFixed(4).replace(/0+$/, '').replace(/\.$/, '');
    const packOf = (p, from) => {
        if (!p || typeof p !== 'object' || Array.isArray(p)) return null;
        const kind = String(p.kind ?? ''), size0 = p.size;
        if (!['kg', 'g', 'seeds'].includes(kind) || !isNum(size0) || Number(size0) <= 0 || Number(size0) > 1e7) return null;
        const size = Number(size0);
        let label = String(p.label ?? '').toLowerCase().replace(/[^a-z]/g, '').slice(0, 12);
        label = B.packLabels.includes(label) ? label : 'pack';
        const what = typeof p.what === 'string' && /^[A-Za-z ]{1,30}$/.test(p.what) ? p.what : null;
        return { id: kind + '-' + packNum(size) + '-' + label, kind, size, label, word: kind === 'seeds' && p.word === 'kernels' ? 'kernels' : 'seeds',
            about: isNum(p.about) && Number(p.about) > 0 ? Number(p.about) : null, what, seed: [].concat(p.seed ?? []).filter((x) => typeof x === 'string'), from };
    };
    const packsOf = (c, vy) => {
        const C = B.crops[c.crop], m = c.method;
        if (m === 'piece' || (m === 'transplant' && !(C.tgw && C.tgw.length))) return [];
        const v = vy ? vy.pack : null, own = Array.isArray(v) ? v.slice(0, 8) : (v && typeof v === 'object' ? [v] : []);
        const out = [], seen = {};
        [[own, 'variety'], [C.packs || [], 'crop']].forEach(([list, from]) => list.forEach((p0) => {
            const p = packOf(p0, from);
            if (p && !seen[p.id] && (p.kind === 'seeds' || weighs(C, m))) { seen[p.id] = 1; out.push(p); }
        }));
        return out;
    };
    const packPick = (list, c) => {
        if (!list.length) return null;
        const hit = list.find((p) => p.id === c.pack);
        if (hit) return hit;
        if (list[0].from === 'variety') return list[0];
        const t = c.seed ? c.seed.type : null;
        return list.find((p) => t !== null && t !== undefined && p.seed.includes(t)) || list[0];
    };
    const core = (c, vy, pen, forceN) => {
        const C = B.crops[c.crop], Y = C.yield, m = c.method, areaHa = areaHaOf(c);
        const g = c.germ / 100, L = c.loss / 100, fit = c.fit / 100;
        let res = c.reserve / 100;
        const nursery = m === 'transplant' && !!(C.tgw && C.tgw.length), hasGerm = m !== 'transplant' || nursery;
        // The field and the season at emergence lower the chance (SeedPenalty); the seedbed's temperature, its germination.
        const pCome = (m === 'transplant' ? 1 - L : g) * pen.emerge.k, post = m === 'transplant' ? 1 : 1 - L, pLive = pCome * post, gBed = g * pen.seedbed.k;
        const sug = suggest(C, m, pCome, c.keep);
        const n = forceN ?? (c.hillMode === 'auto' ? sug.n : c.perHill);
        const k = Math.min(c.keep ?? n, n);
        let kept = keptMean(n, pCome, k) * post;
        const empty = Math.pow(1 - pCome, n), short = c.keep !== null && c.keep >= 2 ? shortShare(n, pCome, Math.min(c.keep, n)) : empty, filled = 1 - Math.pow(1 - pLive, n);
        // Sown one a hill, the gaps replanted within a week from the reserve: the reserve covers the empty share, and the hills replanted count.
        const replant = !!C.oneSeed && m === 'direct' && n === 1;
        if (replant) { res = Math.max(res, empty); kept = (pCome + (1 - pCome) * pCome) * post; }
        const byHills = C.standBy === 'hills', perHillStand = byHills ? filled : kept;
        let wts = null;
        if (weighs(C, m)) {
            const own = c.weight ? (m === 'piece' ? c.weight : c.weight / 1000) : null;
            const vw = m === 'piece' ? null : spanOf(vy ? vy.tgw : null);
            const sp = m === 'piece' ? C.pieceWt : (vw || C.tgw).map((v) => v / 1000);
            wts = own ? { lo: own, mid: own, hi: own, own: true } : { lo: sp[0], mid: (sp[0] + sp[1]) / 2, hi: sp[1], own: false };
        }
        const usualRow = C.row[1];
        let row = c.row, plant = c.plant, square = null, plantSpan = null, given = null;
        if (c.mode !== 'seeds') {
            const hillsFor = (unit) => {
                if (c.mode === 'spacing') {
                    const count = c.amountUnit === 'count' ? c.amount : (c.amountUnit === 'kg' ? c.amount * 1000 : c.amount) / unit;
                    const perHa = c.amountPer === 'lot' ? count / areaHa : count;
                    const field = nursery ? perHa * gBed * fit : perHa;
                    return { count, hills: field / (n * (1 + res)) };
                }
                const perHa = c.targetPer === 'lot' ? c.target / areaHa : c.target;
                return { count: null, hills: perHa / Math.max(1e-9, perHillStand) };
            };
            const solve = (hills) => {
                const a = 1e8 / Math.max(1e-9, hills);
                if (c.layout === 'triangle') return { plant: Math.sqrt(a / K.tri), row: c.row, square: null };
                if (c.layout === 'beds') return { plant: a / (c.bed / c.bedRows), row: c.row, square: null };
                const r = c.rowSet === 'free' ? usualRow : c.row;
                return { plant: a / r, row: r, square: c.rowSet === 'free' ? Math.sqrt(a) : null };
            };
            const mid = hillsFor(wts ? wts.mid : null), s = solve(mid.hills);
            plant = s.plant; row = s.row; square = s.square; given = mid.count;
            if (c.mode === 'spacing' && c.amountUnit !== 'count' && wts && !wts.own && wts.lo < wts.hi) {
                plantSpan = [solve(hillsFor(wts.hi).hills).plant, solve(hillsFor(wts.lo).hills).plant].sort((a, b) => a - b);
            }
        }
        const effRow = c.layout === 'beds' ? c.bed / c.bedRows : c.layout === 'triangle' ? plant * K.tri : row;
        const areaHill = effRow * plant, hillsHa = 1e8 / areaHill;
        const fieldHa = hillsHa * n, buyHa = nursery ? fieldHa / (gBed * fit) : fieldHa;
        const needLo = buyHa, needHi = buyHa * (1 + res);
        const kgLo = wts ? needLo * wts.lo / 1000 : null, kgHi = wts ? needHi * wts.hi / 1000 : null;
        // The packs to buy: a pack sold by count from the seeds (the seed size does not matter), a weighed one from the kilos.
        const pk = packPick(packsOf(c, vy), c);
        let packLo = null, packHi = null, packEach = null;
        if (pk && pk.kind === 'seeds') { packLo = needLo / pk.size; packHi = needHi / pk.size; packEach = wts ? pk.size * wts.mid / 1000 : null; }
        else if (pk && wts) { const kgP = pk.kind === 'kg' ? pk.size : pk.size / 1000; packLo = kgLo / kgP; packHi = kgHi / kgP; packEach = kgP * 1000 / wts.mid; }
        const plantsHa = hillsHa * kept, standHa = hillsHa * perHillStand;
        const [lo, hi] = C.stand;
        const status = standHa < lo * K.thin ? 'sparse' : standHa < lo ? 'thin' : standHa <= hi ? 'good' : standHa <= hi * K.crowd ? 'crowded' : 'danger';
        const at = (s) => c.layout === 'triangle' ? Math.sqrt(1e8 * perHillStand / (s * K.tri)) : 1e8 * perHillStand / (s * effRow);
        const goodPlant = [at(hi), at(lo)];
        const P = partsOf(c, vy), keepShare = 1 - c.harvestLoss / 100;
        let kgHa = null, kgHaLo = null, pieces = null, piecesLo = null, panM2 = null, panM2Lo = null, stalks = null, stalksLo = null, capped = false, plateau = false, plantsY = plantsHa, gw = null;
        if (Y.model === 'tiller') {
            gw = c.weight ? [c.weight, c.weight] : (spanOf(vy ? vy.tgw : null) || [(C.tgw[0] + C.tgw[1]) / 2, (C.tgw[0] + C.tgw[1]) / 2]);
            const raw = hillsHa * filled * P.tillers[1] / 10000;
            panM2 = Math.min(raw, P.ceiling[1]); panM2Lo = Math.min(hillsHa * filled * P.tillers[0] / 10000, P.ceiling[0]); capped = raw > P.ceiling[1];
            kgHa = panM2 * P.grains[1] * P.filled[1] * pen.flower / 100 * gw[1] / 1000 * 10;
            kgHaLo = panM2Lo * P.grains[0] * P.filled[0] * pen.flower / 100 * gw[0] / 1000 * 10 * keepShare;
        } else {
            if (plantsHa > hi) { plantsY = hi; plateau = true; }
            if (Y.model === 'ear') {
                kgHa = plantsY * P.ears[1] * P.perEar[1] / 1000; kgHaLo = plantsY * P.ears[0] * P.perEar[0] / 1000 * keepShare;
                if (Y.fresh) { pieces = plantsY * P.ears[1]; piecesLo = plantsY * P.ears[0] * keepShare; }
            } else if (Y.model === 'stalk') {
                // Sugarcane: the clumps x the tillers a clump x the millable share, no more stalks than a field carries, x the weight of a stalk.
                const max = Y.stalkMax, raw = plantsY * P.stalks[1] * P.millable[1] / 100;
                stalks = Math.min(raw, max); stalksLo = Math.min(plantsY * P.stalks[0] * P.millable[0] / 100, max); capped = raw > max;
                pieces = stalks; piecesLo = stalksLo * keepShare;
                kgHa = stalks * P.stalkKg[1]; kgHaLo = stalksLo * P.stalkKg[0] * keepShare;
            } else if ((Y.unit || 'kg') === 'count') { pieces = plantsY * P.perPlant[1]; piecesLo = plantsY * P.perPlant[0] * keepShare; }
            else { const div = (Y.unit || 'kg') === 'g' ? 1000 : 1; kgHa = plantsY * P.perPlant[1] / div; kgHaLo = plantsY * P.perPlant[0] / div * keepShare; }
        }
        const GK = pen.growth.k;
        if (kgHa !== null) { kgHa *= GK; kgHaLo *= GK; }
        if (pieces !== null) { pieces *= GK; piecesLo *= GK; }
        const flags = [['stand-' + status, { sparse: 'bad', thin: 'warn', good: 'good', crowded: 'warn', danger: 'bad' }[status]]];
        const phMax = perHillFor(C, m)[2];
        if (c.crop === 'rice' && n > K.riceMax) flags.push(['rice-seedlings', n >= K.riceBad ? 'bad' : 'warn']);
        else if (m !== 'transplant') {
            if (!enough(n, pCome, c.keep, C.oneSeed ? K.shortOne : K.shortOk)) flags.push(['hill-short', empty > K.shortBad ? 'bad' : 'warn']);
            else if (c.keep !== null && n > sug.n + 1) flags.push(['hill-extra', 'warn']);
            else if (c.keep === null && n > phMax) flags.push(['hill-crowded', 'warn']);
            else flags.push(['hill-ok', 'good']);
        } else if (n > phMax) flags.push(['hill-crowded', 'warn']);
        if (c.keep !== null && n > c.keep) flags.push(['hill-thin', 'info']);
        if (c.layout === 'rows') { if (row < C.row[0]) flags.push(['row-close', 'warn']); else if (row > C.row[2]) flags.push(['row-wide', 'info']); }
        if (plant < C.plant[0]) flags.push(['plant-close', 'warn']); else if (plant > C.plant[2]) flags.push(['plant-wide', 'info']);
        if (hasGerm && C.germ) { if (c.germ < c.germMin) flags.push(['germ-low', c.germ < c.germMin - 15 ? 'bad' : 'warn']); else if (c.germ > 98) flags.push(['germ-high', 'info']); }
        if (capped) flags.push([Y.model === 'stalk' ? 'stalk-cap' : 'tiller-cap', 'warn']);
        if (plateau) flags.push(['yield-plateau', 'info']);
        const lot = (v) => v === null || !areaHa ? null : v * areaHa;
        return {
            _row: row, _plant: plant, penK: { emerge: pen.emerge.k, seedbed: pen.seedbed.k, growth: pen.growth.k, flower: pen.flower },
            status, flags, variety: c.variety, varietyYield: !!vy, basis: vy ? vy.basis || null : null,
            areaHa, row, plant, effRow, square, plantSpan, given, areaHill, hillsHa, hillsLot: lot(hillsHa),
            perHill: n, suggest: sug.n, suggestOk: sug.ok, kept: k, reserve: res * 100,
            replant: replant ? { share: empty, after: Math.pow(1 - pCome, 2), seedsHa: hillsHa * empty, kgHa: wts ? hillsHa * empty * wts.mid / 1000 : null } : null, fieldHa, buyLo: needLo, buyHi: needHi, buyLotLo: lot(needLo), buyLotHi: lot(needHi),
            kgLo, kgHi, kgLotLo: lot(kgLo), kgLotHi: lot(kgHi),
            pack: packLo === null ? null : (({ seed, ...rest }) => rest)(pk), packLo, packHi, packLotLo: lot(packLo), packLotHi: lot(packHi), packEach,
            nursery, emptyShare: empty, shortShare: short, keptPerHill: kept,
            plantsHa, plantsLot: lot(plantsHa), plantsM2: plantsHa / 10000, standHa, standLot: lot(standHa), goodPlant,
            parts: Object.assign({}, P, { gw }), panM2, panM2Lo, capped, plateau, stalksHa: stalks, stalksHaLo: stalksLo,
            kgHaHi: kgHa, kgHaLo, yLotHi: lot(kgHa), yLotLo: lot(kgHaLo),
            piecesHa: pieces, piecesHaLo: piecesLo, piecesLot: lot(pieces), piecesLotLo: lot(piecesLo),
            // For the page only.
            filled, pLive, wts, hasGerm, byHills, usualRow,
        };
    };
    /* ---- the field and the season: App\Support\SeedPenalty and SeasonFactors, the same ---- */
    const PK = B.pen.k;
    const PEN0 = { emerge: { k: 1, lines: [] }, seedbed: { k: 1, lines: [] }, growth: { k: 1, lines: [] }, flower: 1, notes: [], W: null, SF: null };
    // NpkNeed::ensoTilt: full from October to May, half in the southwest monsoon, half again more than nine months out.
    const ensoTiltOf = (enso, win) => {
        const T = enso ? B.pen.ensoTilt[enso.phase || ''] : null;
        if (!enso || enso.phase === 'neutral' || !T) return { rain: 1, sun: 1, temp: 0 };
        const lvl = { weak: 0, moderate: 1, strong: 2, 'very strong': 3 }[enso.strength || ''] ?? 1;
        const months = (win && win.months) || [];
        const dry = months.length ? months.filter((mo) => mo >= 10 || mo <= 5).length / months.length : 1;
        const w = (0.5 + 0.5 * dry) * (Number((win && win.monthsAhead) || 0) > 9 ? 0.5 : 1);
        return { rain: 1 + T.rain[lvl] * w, sun: 1 + T.sun[lvl] * w, temp: T.temp[lvl] * w };
    };
    /* What a pump or well makes up of the rain's shortfall (2026-10-21, NpkNeed::pumpCover and NPK Plus's twin): all
       year a little more, in the months it runs low (every dry season, or only in a very dry year) a quarter, by the
       share of the season's days in them. */
    const MONTH_N = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const monthWords = (ms) => {
        if (!ms.length) return '';
        const run = ms.length > 2 && ms.every((m, i) => !i || m === ms[i - 1] % 12 + 1), w = ms.map((m) => MONTH_N[m - 1]);
        return run ? w[0] + ' to ' + w[w.length - 1] : (w.length < 2 ? w[0] : w.slice(0, -1).join(', ') + ' and ' + w[w.length - 1]);
    };
    const pumpCover = (pump, win, enso, driest = false) => {
        const F = B.pen.field, year = ['always', 'badYears', 'everyYear', 'unsure'].includes((pump || {}).year) ? pump.year : null;
        const base = year === 'always' ? F.pumpAlways : F.pumpCover, out = { cover: base, low: [], lowDays: 0, year, given: false };
        const dryYear = !!enso && enso.phase === 'el_nino' && ['moderate', 'strong', 'very strong'].includes(enso.strength);
        if (!['badYears', 'everyYear'].includes(year) || (year === 'badYears' && !dryYear && !driest) || !win || !win.from) return out;
        let stops = [].concat(pump.stops || []).map(Number).filter((m) => m >= 1 && m <= 12);
        out.given = stops.length > 0;
        if (!stops.length) stops = F.pumpDryMonths;
        const days = Math.max(1, Number(win.days) || 1), [y0, m0, d0] = String(win.from).split('-').map(Number), low = [];
        let n = 0;
        for (let d = 0; d < days; d++) {
            const m = new Date(y0, m0 - 1, d0 + d).getMonth() + 1;
            if (stops.includes(m)) { n++; if (!low.includes(m)) low.push(m); }
        }
        const share = n / days;
        return { cover: base * (1 - share) + F.pumpLow * share, low, lowDays: n, year, given: out.given };
    };
    // SeasonFactors::of, NPK Plus's seasonPlanks() factors: sun, water, temperature.
    const seasonFactorsOf = (ckey, hist, win, enso, water, pump = null) => {
        if (!hist || !hist.avg) return null;
        const K = Object.assign({}, B.pen.climate.default, B.pen.climate[ckey] || {}), t = ensoTiltOf(enso, win), a = hist.avg, days = Math.max(1, Math.trunc(Number(win.days || 1)));
        const cl = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
        const rain = a.rain * t.rain, rad = a.rad * t.sun, tmean = a.tmean + t.temp, et0 = a.et0 * (t.rain < 1 ? 1 + (1 - t.rain) * 0.15 : 1);
        const need = et0 * K.kc + (K.extraMm || 0), eff = rain * 0.75;
        // A pump or a well makes up most of what the rain leaves short, less in the months it runs low; a part time source half (NpkModel::FIELD).
        const pc = water === 'pump' ? pumpCover(pump, win, enso) : null;
        const cover = pc ? pc.cover : water === 'partial' ? B.pen.field.partialCover : 0;
        const supply = water === 'irrigated' ? need : eff + cover * Math.max(0, need - eff);
        const wr = Math.min(1, supply / Math.max(1, need)), fw = cl(1 - K.ky * (1 - wr), 0.1, 1), fs = cl(rad / K.rad, 0.2, 1.1);
        const dev = Math.max(0, K.opt[0] - tmean, tmean - K.opt[1]), phase = (enso && enso.phase) || null;
        const hotRaw = K.hot >= 35 ? (a.hot35 || 0) : K.hot >= 32 ? (a.hot32 || 0) : (a.hot30 || 0);
        const hotN = hotRaw * (phase === 'el_nino' ? 1.3 : phase === 'la_nina' ? 0.85 : 1);
        const ft = cl((1 - 0.07 * dev) * (1 - 0.8 * Math.min(1, hotN / days)), 0.25, 1);
        return { pump: pc, sun: fs, water: fw, temp: ft, rain, rad, tmean, need, supply, wr, hotN, dev, tilt: t, K };
    };
    // SeedPenalty::weather: the chance of a heavy or a dry start, the mean and the warm part of the day.
    const weatherOf = (season) => {
        if (!season || !season.emerge || !(season.emerge.years || []).length) return null;
        const t = ensoTiltOf(season.enso || null, season.ewindow), ys = season.emerge.years, n = ys.length;
        let heavy = ys.filter((y) => y.rain * t.rain >= PK.HEAVY_MM).length / n, dry = ys.filter((y) => y.rain * t.rain < PK.DRY_MM).length / n;
        let tm = season.emerge.avg.tmean + t.temp, tx = season.emerge.avg.tmax + t.temp;
        const rain = season.emerge.avg.rain * t.rain;
        let fc = null;
        const F = season.forecast || [];
        if (F.length) {
            const nF = Math.min(PK.EMERGE_DAYS, F.length);
            const tmF = F.reduce((s, d) => s + (d.max + d.min) / 2, 0) / F.length, txF = F.reduce((s, d) => s + d.max, 0) / F.length;
            tm = (tm * (PK.EMERGE_DAYS - nF) + tmF * nF) / PK.EMERGE_DAYS; tx = (tx * (PK.EMERGE_DAYS - nF) + txF * nF) / PK.EMERGE_DAYS;
            const wk = F.slice(0, 7).filter((d) => d.mm !== null && d.mm !== undefined);
            const sum = wk.reduce((s, d) => s + d.mm, 0), max = wk.length ? Math.max(...wk.map((d) => d.mm)) : 0;
            fc = { days: nF, tm: tmF, tx: txF, mmDays: wk.length, sum, max, from: F[0].date, to: F[F.length - 1].date };
            if (wk.length >= 5) {
                if (max >= PK.FC_HEAVY_DAY || sum >= PK.FC_HEAVY_WEEK) { heavy = Math.max(heavy, PK.FC_HEAVY_P); fc.heavy = true; }
                if (sum < PK.FC_DRY_WEEK) { dry = Math.max(dry, PK.FC_DRY_P); fc.dry = true; }
            }
        }
        return { heavy, dry, tm, tw: (tm + tx) / 2, tx, rain, years: n, tilt: t, fc };
    };
    const cardinalOf = (T, t) => { const [b, o1, o2, x] = T; if (t <= b || t >= x) return 0; if (t < o1) return (t - b) / (o1 - b); if (t > o2) return (x - t) / (x - o2); return 1; };
    // NpkNeed::fieldCap's caps (NpkModel::FIELD): water, salt, sodium. A pump or a well, like a canal, has no water cap.
    const fieldCapOf = (key, on, water, conds) => {
        if (!on) return [];
        const caps = B.pen.field.caps, kind = key.startsWith('rice') ? 'rice' : 'other', why = [];
        if (water === 'rainfed') why.push(['rainfed', caps.rainfed[kind]]); else if (water === 'partial') why.push(['partial', caps.partial[kind]]);
        if (conds.includes('saline')) why.push(['saline', caps.saline]);
        if (conds.includes('sodic')) why.push(['sodic', caps.sodic]);
        return why;
    };
    // SeedPenalty::of: each line [code, factor, from, a number for its words].
    const penOf = (C, key, m, f, season) => {
        const E = [], S = [], G = [], notes = [];
        const rice = key.startsWith('rice'), tp = m === 'transplant', pc = m === 'piece', dir = m === 'direct';
        const hasTgw = !!(C.tgw && C.tgw.length), nursery = tp && hasTgw, small = !pc && hasTgw && C.tgw[1] < 10;
        const W = weatherOf(season), water = f ? f.water : null, wk = water || 'irrigated';
        const wt = (k, w) => 1 - w * (1 - k);
        const add = (L, code, k, from, v = null) => { if (k < 0.9995) L.push([code, k, from, v]); };
        const wetOf = (ff) => ff.drainage ?? (ff.signs.some((x) => x === 'rust' || x === 'smell') ? 'slow' : null);
        if (f) {
            if (water === null) notes.push('nowater');
            const sea = f.salt.includes('sea');
            const ec = f.ec ?? ((f.saline || f.waterSalty) ? (sea ? PK.SALT_EC_SEA : PK.SALT_EC_SIGNS) : null);
            if (ec !== null) { const [thr, slope] = B.pen.salt[key] || B.pen.salt.default; add(E, 'salt', Math.max(PK.SALT_FLOOR, 1 - PK.SALT_EMERGE * slope / 100 * Math.max(0, ec - thr)), 'lot', ec); }
            if (f.sodic && dir && !rice) add(E, 'sodic', small ? PK.SODIC_SMALL : PK.SODIC_LARGE, 'lot');
            const dr = wetOf(f), wW = tp ? 0.7 : 1;
            if (!rice) {
                if (dr === 'stands') add(E, 'wet', wt(PK.WET_STANDS, wW), 'lot', 'stands');
                else if (dr === 'slow') add(E, 'wet', wt(PK.WET_SLOW, wW), 'lot', 'slow');
                const mo = season ? Number(String(season.from).slice(5, 7)) : null;
                if (f.flooding === 'often' && mo !== null && mo >= 6 && mo <= 11) add(E, 'flood', wt(PK.FLOOD_WET, wW), 'lot');
            } else if (key === 'rice_dsr' && dr === 'stands') add(E, 'dsrwater', PK.DSR_STANDS, 'lot');
            const strong = (f.ph !== null && f.ph < PK.ACID_PH) || (f.ph === null && f.acidity === 'strong');
            if (f.sulfate) add(E, 'sulfate', wt(rice ? PK.ACID_SULFATE_RICE : PK.ACID_SULFATE, tp ? 0.5 : 1), 'lot');
            else if (strong && !rice) add(E, 'acid', wt(PK.ACID_STRONG, tp ? 0.5 : 1), 'lot', f.ph);
            const crusty = ['silt', 'clay', 'heavy_clay'].includes(f.texture || '') && dir && !rice && !f.sodic;
            if (crusty) { if (W) add(E, 'crust', 1 - (small ? PK.CRUST_SMALL : PK.CRUST_LARGE) * W.heavy, 'season', W.heavy); else add(E, 'crust', small ? PK.CRUST_FLAT_SMALL : PK.CRUST_FLAT_LARGE, 'lot'); }
            if (!W && f.texture === 'sandy' && water === 'rainfed' && (dir || pc)) add(E, 'sandy', PK.SANDY_DRY_FLAT, 'lot');
        }
        if (W) {
            const poor = !!f && (['slow', 'stands'].includes(f.drainage || '') || ['clay', 'heavy_clay'].includes(f.texture || ''));
            const wR = (tp ? 0.33 : pc ? 0.7 : 1) * (poor ? 1.3 : 1);
            add(E, 'heavy', Math.max(0, 1 - PK.RAIN_ROT * W.heavy * wR), W.fc && W.fc.heavy ? 'forecast' : 'season', W.heavy);
            // A dry start: only where the field waits on the rain (a pump or a well waters at sowing).
            if (wk !== 'irrigated' && wk !== 'pump' && !(rice && tp && wk === 'partial')) {
                const tex = f ? f.texture : null, mult = tex === 'sandy' ? 1.4 : ['clay', 'heavy_clay'].includes(tex) ? 0.8 : 1;
                const wD = (wk === 'partial' ? 0.5 : 1) * (tp ? 0.7 : 1) * mult;
                add(E, 'dry', Math.max(0, 1 - PK.DRY_LOSS * W.dry * wD), W.fc && W.fc.dry ? 'forecast' : 'season', W.dry);
            }
            const T = B.pen.germT[key];
            if (T && (dir || pc || nursery)) {
                const cool = W.tm < T[1] ? cardinalOf(T, W.tm) : 1, hot = W.tw > T[2] ? cardinalOf(T, W.tw) : 1;
                const k = 1 - PK.TEMP_SOFT * (1 - Math.min(cool, hot));
                add(nursery ? S : E, hot < cool ? 'hot' : 'cool', k, W.fc ? 'forecast' : 'season', hot < cool ? W.tw : W.tm);
            }
        }
        const capWater = season && season.hist && season.hist.avg ? 'irrigated' : wk;
        const conds = f ? [(f.saline || f.waterSalty) ? 'saline' : null, f.sodic ? 'sodic' : null].filter(Boolean) : [];
        fieldCapOf(key, !!f, capWater, conds).forEach((w) => add(G, 'cap-' + w[0], w[1], 'lot'));
        const SF = season ? seasonFactorsOf(B.pen.climateKey[key] || key, season.hist, season.window, season.enso || null, wk, f ? f.pump || null : null) : null;
        if (SF) {
            const min = Math.min(1, SF.sun, SF.water, SF.temp), which = min === SF.water ? 'water' : min === SF.temp ? 'temp' : 'sun';
            add(G, 'season-' + which, min, 'season', which === 'water' ? SF.wr : which === 'temp' ? SF.tmean : SF.rad);
        }
        if (f) {
            if (B.pen.roots.includes(key)) {
                const kr = Math.min(f.hardpan === 'yes' ? PK.ROOT_HARDPAN : 1, f.depth === 'shallow' ? PK.ROOT_SHALLOW : 1);
                add(G, f.depth === 'shallow' && kr === PK.ROOT_SHALLOW ? 'shallow' : 'hardpan', kr, 'lot');
            } else if (f.hardpan === 'yes' && wk === 'rainfed' && !rice) add(G, 'hardpandry', PK.HARDPAN_DRY, 'lot');
            if (!rice) {
                const dr = wetOf(f);
                if (dr === 'stands') add(G, 'growwet', PK.GROW_STANDS, 'lot', 'stands'); else if (dr === 'slow') add(G, 'growwet', PK.GROW_SLOW, 'lot', 'slow');
            }
        }
        let flower = 1;
        if (rice && season && season.flower && season.flower.avg && SF) {
            const usual = SF.hotN * PK.FLOWER_DAYS / Math.max(1, Math.trunc(Number(season.days))), phase = season.enso ? season.enso.phase : null;
            const hotF = (season.flower.avg.hot35 || 0) * (phase === 'el_nino' ? 1.3 : phase === 'la_nina' ? 0.85 : 1);
            const excess = Math.max(0, hotF - usual);
            flower = Math.max(PK.FLOWER_FLOOR, 1 - PK.FLOWER_HEAT * excess);
            if (flower < 0.9995) G.push(['flower', flower, 'season', excess]);
        }
        const prod = (L, floor, skip = []) => {
            let k = 1, cap = 1;
            L.forEach((l) => { if (skip.includes(l[0])) return; if (l[0].startsWith('cap-')) cap *= l[1]; else k *= l[1]; });
            return Math.max(floor, k * Math.max(B.pen.field.capFloor, cap));
        };
        return { emerge: { k: prod(E, PK.EMERGE_FLOOR), lines: E }, seedbed: { k: prod(S, PK.EMERGE_FLOOR), lines: S }, growth: { k: prod(G, PK.GROWTH_FLOOR, ['flower']), lines: G }, flower, notes, W, SF };
    };
    // SeedSpacing::withSeed: germination, its least, the seedbed fit and the losses from the variety, else the seed's kind, else the crop.
    const withSeed = (c, vy) => {
        const C = B.crops[c.crop], kinds = B.seedKinds[c.crop] || null, types = (kinds && kinds.types) || {};
        const type = Object.prototype.hasOwnProperty.call(types, c.seedType ?? '') ? c.seedType : (kinds ? kinds.default : null);
        const mid = (v) => { const sp = spanOf(v); return sp ? (sp[0] + sp[1]) / 2 : null; };
        const vg = mid(vy ? vy.germ : null), tg = type !== null && types[type] ? types[type][0] : null;
        const germ = vg ?? tg ?? (C.germ || [100])[0];
        const vm = mid(vy ? vy.germMin : null), dk = kinds ? kinds.default : null;
        const germMin = vm ?? (dk !== null && types[dk] && types[dk][1] !== undefined ? types[dk][1] : (C.germ || [100, 100])[1]);
        const vf = mid(vy ? vy.seedFit : null), fit = vf ?? (C.fit ?? 100);
        const vl = mid(vy ? vy.loss : null), loss = vl ?? C.loss;
        const hold = (v, lo, hi) => Math.max(lo, Math.min(hi, Number(v)));
        const out = { germ: hold(germ, 1, 100), germMin: hold(germMin, 1, 100), fit: hold(fit, 1, 100), loss: hold(loss, 0, 90), reserve: C.reserve, harvestLoss: C.yield.loss };
        const seed = Object.assign({}, out, { type, from: { germ: vg !== null ? 'variety' : tg !== null ? 'type' : 'crop', germMin: vm !== null ? 'variety' : 'crop', fit: vf !== null ? 'variety' : 'crop', loss: vl !== null ? 'variety' : 'crop' } });
        return Object.assign({}, out, c, { seed });
    };
    // SeedSpacing::compute: this field and season, and the same inputs on a usual field.
    const compute = (c0, vy, ctx) => {
        const C = B.crops[c0.crop], c = withSeed(c0, vy);
        const pen = ctx && (ctx.facts || ctx.season) ? penOf(C, c.crop, c.method, ctx.facts || null, ctx.season || null) : PEN0;
        const r = core(c, vy, pen, null);
        let usual = null;
        if (pen.emerge.lines.length || pen.seedbed.lines.length || pen.growth.lines.length) {
            const b = core(c, vy, PEN0, null);
            usual = {};
            B.usualKeys.forEach((k) => { usual[k] = b[k]; });
        }
        delete r._row; delete r._plant;
        return Object.assign(r, { pen, usual, seed: c.seed, packs: packsOf(c, vy) });
    };
    /* calc:end */

    /* ---- words for the crop and the method ---- */
    const units = (C, m) => m === 'transplant' ? ['seedling', 'seedlings'] : m === 'piece' ? (C.piece || ['piece', 'pieces']) : ['seed', 'seeds'];
    const standWord = (C) => C.standBy === 'hills' ? 'hills' : 'plants';
    const shortName = (C) => C.name.split(',')[0].toLowerCase();
    const span = (a) => cmTxt(a[0]) + ' to ' + cmTxt(a[2]) + ' cm';

    /* ---- the choice tags ---- */
    let pk = null;
    const pick = (title, dict, cur, cb) => {
        $('scPickTitle').textContent = title;
        pk = cb;
        $('scPickRows').innerHTML = Object.entries(dict).map(([k, v]) => '<button type="button" class="sc-opt' + (k === cur ? ' is-on' : '') + '" data-k="' + esc(k) + '"><span><b>' + esc(v[0]) + '</b>' + (v[1] ? '<small>' + esc(v[1]) + '</small>' : '') + '</span><i></i></button>').join('');
        window.openSheet('scPickSheet');
    };
    $('scPickRows').addEventListener('click', (e) => {
        const b = e.target.closest('.sc-opt');
        if (!b || !pk) return;
        $('scPickRows').querySelectorAll('.sc-opt').forEach((x) => x.classList.toggle('is-on', x === b));
        const cb = pk; pk = null;
        setTimeout(() => window.closeSheet('scPickSheet'), 160);
        cb(b.dataset.k);
    });
    const tag = (id, text, sub) => {
        const el = $(id), t = el.querySelector('.sc-tag-t') || el.querySelector('span');
        t.textContent = text;
        const s = el.querySelector('small');
        if (s && sub !== undefined) s.textContent = sub;
    };
    const swap = (el) => { if (!el || still()) return; el.classList.remove('sc-swap'); void el.offsetWidth; el.classList.add('sc-swap'); };

    /* ---- the crop sheet: grouped, with a find box ---- */
    const cropRows = (q) => {
        const qq = q.trim().toLowerCase();
        let html = '';
        Object.entries(B.groups).forEach(([gk, gname]) => {
            const rows = Object.entries(B.crops).filter(([, C]) => C.group === gk && (!qq || (C.name + ' ' + C.local).toLowerCase().includes(qq)));
            if (!rows.length) return;
            html += '<p class="sc-ghead">' + esc(gname) + '</p>' + rows.map(([k, C]) => '<button type="button" class="sc-opt' + (k === st.crop ? ' is-on' : '') + '" data-k="' + esc(k) + '"><span><b>' + esc(C.name) + '</b><small>' + esc(C.local) + '</small></span><i></i></button>').join('');
        });
        $('scCropRows').innerHTML = html || '<p class="sc-none">No crop by that name yet.</p>';
    };
    $('scCrop').addEventListener('click', () => { $('scCropFind').value = ''; cropRows(''); window.openSheet('scCropSheet'); });
    $('scCropFind').addEventListener('input', (e) => cropRows(e.target.value));
    $('scCropRows').addEventListener('click', (e) => {
        const b = e.target.closest('.sc-opt');
        if (!b) return;
        $('scCropRows').querySelectorAll('.sc-opt').forEach((x) => x.classList.toggle('is-on', x === b));
        setTimeout(() => window.closeSheet('scCropSheet'), 160);
        if (b.dataset.k !== st.crop) { setCrop(b.dataset.k); calc(); }
    });

    /* ---- a crop's starting numbers ---- */
    const setVal = (id, v) => { $(id).value = v === null || v === undefined ? '' : v; };
    const fillCropDefaults = () => {
        const C = crop(), bed = C.bed || K.bed, Y = C.yield, ph = perHillFor(C, st.method);
        setVal('scRow', C.row[1]); setVal('scPlant', C.plant[1]); setVal('scBed', bed[0]); setVal('scBedRows', bed[1]);
        setVal('scPerHill', ph[1]); setVal('scKeep', C.keep);
        setVal('scWeight', '');
        // The yield parts start empty: empty counts the variety's own or the crop's usual (the faint numbers).
        ['scTillers', 'scGrains', 'scFilled', 'scEars', 'scPerEar', 'scPerPlant', 'scStalks', 'scMillable', 'scStalkKg'].forEach((id) => setVal(id, ''));
        setVal('scTarget', '');
        if (!weighs(C, st.method)) st.amountUnit = 'count';
        else if (st.amountUnit === 'count' && $('scAmount').value === '') st.amountUnit = st.method === 'piece' ? 'kg' : 'kg';
    };
    const setCrop = (key) => {
        st.crop = B.crops[key] ? key : 'corn_yellow';
        const C = crop();
        st.method = C.methods[0]; st.layout = C.layout; st.hillMode = 'auto';
        st.variety = ''; st.vRec = null; st.vy = null; st.trial = null; st.seedType = null; st.pack = null;
        fillCropDefaults();
        paint(true);
        if (st.lotId || st.month) ctxSoon(0);
    };

    /* ---- show what the mode, layout and method need ---- */
    const paint = (fresh) => {
        const C = crop(), m = st.method, L = st.layout, M = st.mode, U = units(C, m), nursery = m === 'transplant' && !!(C.tgw && C.tgw.length);
        tag('scCrop', C.name, C.local);
        tag('scVariety', st.variety || 'Any variety', st.variety ? (hasParts(st.vy) ? 'Its own yield parts are counted' : 'Counted with the usual parts for ' + shortName(C)) : 'Optional: pick yours or type its name');
        $('scCropNote').querySelector('span').textContent = C.note || '';
        $('scMethodF').hidden = C.methods.length < 2;
        tag('scMethod', B.methods[m][0]);
        tag('scAreaUnit', B.areaUnits[st.areaUnit][0]);
        tag('scMode', B.modes[M][0], B.modes[M][1]);
        tag('scLayout', B.layouts[L][0]);
        tag('scRowSet', B.rowSet[st.rowSet][0]);
        // 3. The spacing, the seeds or the stand.
        $('scSpaceTitle').textContent = M === 'seeds' ? 'Your spacing' : M === 'spacing' ? 'Your seeds' : 'The stand you want';
        $('scSpaceSay').textContent = M === 'seeds' ? 'Distances are from the center of one plant to the next.' : M === 'spacing' ? 'Say what you have, and how your field is laid out: the distance between plants comes out.' : 'Say how many you want standing at harvest, and how your field is laid out.';
        $('scAmountBox').hidden = M !== 'spacing';
        $('scTargetBox').hidden = M !== 'stand';
        const w = weighs(C, m);
        $('scAmountUnit').hidden = !w;
        const AU = { count: cap(nursery ? 'seeds' : U[1]), kg: 'Kilos', g: 'Grams' };
        tag('scAmountUnit', AU[st.amountUnit] || AU.count);
        tag('scAmountPer', B.per[st.amountPer][0]); tag('scTargetPer', B.per[st.targetPer][0]);
        $('scAmountLabel').textContent = cap(nursery ? 'seeds' : U[1]) + ' you have';
        $('scAmount').placeholder = st.amountUnit === 'count' ? 'Like 50000' : st.amountUnit === 'g' ? 'Like 250' : 'Like 20';
        $('scAmountHint').textContent = nursery ? 'The seeds for the seedbed: the seedlings that come of them are counted for you.' : (w && st.amountUnit !== 'count' ? 'Kilos turn into ' + U[1] + ' by their weight (step 5).' : '');
        $('scTargetLabel').textContent = C.standBy === 'hills' ? 'Hills you want at harvest' : 'Plants you want at harvest';
        $('scTargetUnit').textContent = standWord(C);
        $('scTarget').placeholder = 'Like ' + num(Math.round((C.stand[0] + C.stand[1]) / 2 / 100) * 100);
        $('scTargetHint').textContent = 'The good range for ' + shortName(C) + ': ' + num(C.stand[0]) + ' to ' + num(C.stand[1]) + ' ' + standWord(C) + ' a hectare.';
        $('scRowSetF').hidden = M === 'seeds' || L !== 'rows';
        $('scRowF').hidden = !(L === 'rows' && (M === 'seeds' || st.rowSet === 'set'));
        $('scPlantF').hidden = M !== 'seeds';
        $('scBedF').hidden = $('scBedRowsF').hidden = L !== 'beds';
        $('scPlantLabel').textContent = L === 'triangle' ? 'Between plants, every way' : 'Between plants in the row';
        $('scSpaceBox').hidden = [$('scRowF'), $('scBedF'), $('scBedRowsF'), $('scPlantF')].every((x) => x.hidden);
        const bed = C.bed || K.bed;
        $('scUsual').querySelector('span').textContent = L === 'beds'
            ? 'Usual for ' + shortName(C) + ': beds of about ' + bed[0] + ' cm with their path, ' + bed[1] + ' rows a bed, plants ' + span(C.plant) + ' apart.'
            : L === 'triangle' ? 'In a triangle every plant stands the same distance from its six neighbors, and the rows sit closer: about 15% more plants than a square of the same distance.'
            : 'Usual for ' + shortName(C) + ': rows ' + span(C.row) + ' apart, plants ' + span(C.plant) + ' apart in the row.';
        // 4. A hill.
        $('scHillTitle').textContent = cap(U[1]) + ' a hill';
        $('scHillSay').textContent = m === 'transplant' ? 'A hill is one planting spot: the seedlings set in one hole.' : m === 'piece' ? 'A hill is one planting spot: the ' + U[1] + ' set in one hole.' : 'A hill is one planting spot: the seeds dropped in one hole.';
        $('scPerHillLabel').textContent = cap(U[1]) + ' a hill';
        $('scPerHillUnit').textContent = U[1];
        tag('scHillMode', B.hillMode[st.hillMode][0]);
        $('scPerHill').readOnly = st.hillMode === 'auto';
        $('scKeepBox').hidden = m === 'transplant' && perHillFor(C, m)[2] <= 1 && (val('scPerHill') || 1) <= 1;
        $('scKeepHint').textContent = C.keep === null ? 'Leave it empty to keep all that come up; ' + shortName(C) + ' is not usually thinned.' : 'Usual for ' + shortName(C) + ': ' + C.keep + '.';
        // 5. Germination and losses.
        const hasGerm = m !== 'transplant' || nursery;
        const kinds = B.seedKinds[st.crop];
        $('scSeedTypeF').hidden = !(kinds && hasGerm && m !== 'piece');
        if (kinds) { const tk = kinds.types[st.seedType] ? st.seedType : kinds.default; tag('scSeedType', B.seedTypes[tk][0], B.seedTypes[tk][1]); }
        $('scWeightF').hidden = !w;
        $('scWeightLabel').firstChild.textContent = m === 'piece' ? 'Weight of one ' + U[0] : 'Weight of 1,000 seeds';
        const wr = m === 'piece' ? C.pieceWt : (spanOf(st.vy ? st.vy.tgw : null) || C.tgw);
        $('scWeight').placeholder = wr ? num(wr[0], wr[0] < 10 ? 1 : 0) + ' to ' + num(wr[1], wr[1] < 10 ? 1 : 0) : '';
        $('scWeightHint').textContent = !wr ? '' : m === 'piece' ? 'Weigh 10 and divide by 10. Empty: counted from ' + num(wr[0]) + ' to ' + num(wr[1]) + ' g.' : 'Weigh 100 seeds and times 10. Empty: counted from the usual spread of seed sizes.';
        $('scTest').hidden = !(hasGerm && C.germ && m !== 'piece');
        $('scTestSteps').innerHTML = [
            'Count out 100 seeds from the bag you will sow.',
            'Lay them on a wet paper towel or a clean cloth, roll it up loosely, and keep it damp in a plastic bag in the shade.',
            'After ' + (C.testDays || '5 to 7') + ' days, unroll it and count the seeds that pushed out a root.',
            'That count is your germination in percent. Under ' + (C.germ ? C.germ[1] : 80) + '%, put one more seed in each hill or get fresher seed.',
        ].map((x) => '<li>' + esc(x) + '</li>').join('');
        // 6. The harvest.
        const Y = C.yield;
        $('scTillerBox').hidden = Y.model !== 'tiller';
        $('scEarBox').hidden = Y.model !== 'ear';
        $('scPlantYBox').hidden = Y.model !== 'plant';
        $('scStalkBox').hidden = Y.model !== 'stalk';
        if (Y.model === 'ear') {
            const wd = Y.word || 'ears';
            $('scEarsLabel').textContent = cap(wd) + ' a plant'; $('scEarsUnit').textContent = wd;
            $('scPerEarLabel').textContent = Y.fresh ? 'Weight of one ' + wd.replace(/s$/, '') : 'Grain on one ' + wd.replace(/s$/, '');
            $('scPerEarHint').textContent = Y.fresh ? 'With the husk, as it is sold.' : 'Dry grain, shelled.';
        }
        if (Y.model === 'plant') {
            const u = Y.unit || 'kg';
            $('scPerPlantLabel').textContent = u === 'count' ? cap(Y.what) + ' from one ' + (Y.perYear ? 'tree a year' : 'plant') : 'Harvest from one ' + (Y.perYear ? 'tree a year' : 'plant');
            $('scPerPlantUnit').textContent = u === 'count' ? Y.what : u;
            $('scPerPlantHint').textContent = cap(Y.what) + (Y.perYear ? ', a year at full bearing.' : ', over the whole season.');
        }
        const Pd = partsOf(readIn({ crop: st.crop, method: st.method }), st.vy), whose = hasParts(st.vy) ? 'the own numbers of ' + st.variety : 'the usual for ' + shortName(C);
        const ph = (id, key, d) => { const v = Pd[key]; $(id).placeholder = v ? (v[0] === v[1] ? num(v[0], d) : num(v[0], d) + ' to ' + num(v[1], d)) : ''; };
        ph('scTillers', 'tillers', 0); ph('scGrains', 'grains', 0); ph('scFilled', 'filled', 0); ph('scEars', 'ears', 2); ph('scPerEar', 'perEar', 0);
        ph('scPerPlant', 'perPlant', (Y.unit || 'kg') === 'kg' && Pd.perPlant && Pd.perPlant[0] < 1 ? 3 : 1);
        $('scStalks').placeholder = drng(Pd.stalks, 2); $('scMillable').placeholder = drng(Pd.millable, 0); $('scStalkKg').placeholder = drng(Pd.stalkKg, 2);
        $('scYieldSay').textContent = 'Leave a box empty to count ' + whose + ' (the faint numbers), or type what your own field shows.';
        if (fresh) ['scSpaceBox', 'scAmountBox', 'scTargetBox', { tiller: 'scTillerBox', ear: 'scEarBox', plant: 'scPlantYBox', stalk: 'scStalkBox' }[Y.model]].forEach((id) => swap($(id)));
    };

    $('scMethod').addEventListener('click', () => {
        const C = crop(), d = {};
        C.methods.forEach((k) => { d[k] = B.methods[k]; });
        pick('How it is planted', d, st.method, (k) => { if (k !== st.method) { st.method = k; st.hillMode = 'auto'; setVal('scPerHill', perHillFor(C, k)[1]); if (!weighs(C, k)) st.amountUnit = 'count'; paint(true); calc(); } });
    });
    /* ---- the variety: the shared pick (the records, or a typed name), then its own yield parts ---- */
    const loadVariety = async () => {
        st.vy = null; st.trial = null;
        if (!st.variety) return;
        try {
            const res = await window.api(B.urls.variety + '?' + new URLSearchParams({ crop: st.crop, name: st.variety }), { method: 'GET' });
            st.vy = (res.data && res.data.yield) || null;
            st.trial = (res.data && res.data.trial) || null;
        } catch (e) { st.vy = null; }
    };
    $('scVariety').addEventListener('click', async () => {
        if (!window.aneeVarietyPick) return;
        const C = crop(), was = st.crop;
        const got = await window.aneeVarietyPick.open({ crop: C.book || st.crop, cropLabel: C.name, value: { name: st.variety, record: st.vRec } });
        if (got === null || got === undefined || st.crop !== was) return;
        st.variety = got.name || ''; st.vRec = got.record || null; st.pack = null;
        // A variety's kind sets the seed's kind: hybrid, or inbred and open pollinated.
        const kk = B.seedKinds[st.crop], vt = st.vRec && st.vRec.type;
        if (kk && vt === 'hybrid' && kk.types.hybrid) st.seedType = 'hybrid';
        else if (kk && vt === 'inbred' && kk.types.opv) st.seedType = 'opv';
        await loadVariety();
        paint(); calc();
    });
    $('scAreaUnit').addEventListener('click', () => pick('Lot size in', { ha: ['Hectares', '10,000 square meters'], sqm: ['Square meters', 'For a small lot'] }, st.areaUnit, (k) => { st.areaUnit = k; paint(); calc(); }));
    $('scMode').addEventListener('click', () => pick('What do you want to work out?', B.modes, st.mode, (k) => { if (k !== st.mode) { st.mode = k; paint(true); calc(); } }));
    $('scLayout').addEventListener('click', () => pick('How the field is laid out', B.layouts, st.layout, (k) => { if (k !== st.layout) { st.layout = k; paint(true); calc(); } }));
    $('scRowSet').addEventListener('click', () => pick('Rows', B.rowSet, st.rowSet, (k) => { st.rowSet = k; paint(true); calc(); }));
    $('scAmountUnit').addEventListener('click', () => {
        const C = crop(), U = units(C, st.method), nursery = st.method === 'transplant' && !!(C.tgw && C.tgw.length);
        pick('Your amount in', { count: [cap(nursery ? 'seeds' : U[1]), 'Counted one by one'], kg: ['Kilos', 'Weighed'], g: ['Grams', 'For small seed'] }, st.amountUnit, (k) => { st.amountUnit = k; paint(); calc(); });
    });
    $('scAmountPer').addEventListener('click', () => pick('The amount is for', B.per, st.amountPer, (k) => { st.amountPer = k; paint(); calc(); }));
    $('scTargetPer').addEventListener('click', () => pick('The stand is for', B.per, st.targetPer, (k) => { st.targetPer = k; paint(); calc(); }));
    $('scHillMode').addEventListener('click', () => pick('How many a hill', B.hillMode, st.hillMode, (k) => { st.hillMode = k; paint(); calc(); }));

    /* ---- numbers that count to their new value ---- */
    const shown = new Map();
    const countTo = (el, to, fmt) => {
        const from = shown.has(el) ? shown.get(el) : 0;
        shown.set(el, to);
        if (still() || !isFinite(from) || !isFinite(to) || Math.abs(to - from) < 0.5) { el.textContent = fmt(to); return; }
        const t0 = performance.now(), dur = 650;
        const step = (t) => {
            const k = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - k, 3);
            if (shown.get(el) !== to) return;
            el.textContent = fmt(from + (to - from) * e);
            if (k < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };

    /* ---- the flags, in words ---- */
    const flagIco = {
        good: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
        warn: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" d="M12 7v6M12 17h.01"/></svg>',
        bad: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" d="M12 6v8M12 18h.01"/></svg>',
        info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" d="M12 11v6M12 7h.01"/></svg>',
    };
    const oneIn = (share, tp) => share <= 0.005 ? (tp ? 'almost every hill keeps its plant' : 'almost every hill comes up') : 'about 1 hill in ' + num(Math.max(1, Math.round(1 / share))) + (tp ? ' is left empty' : ' comes up empty');
    const sayFlag = (code, c, r) => {
        const C = B.crops[c.crop], U = units(C, c.method), W = standWord(C), lo = num(C.stand[0]), hi = num(C.stand[1]);
        const gp = cmTxt(r.goodPlant[0]) + ' to ' + cmTxt(r.goodPlant[1]) + ' cm', where = c.layout === 'triangle' ? 'every way' : 'in the row';
        const nm = shortName(C), k = c.keep;
        const crowdHarm = C.group === 'grain' && c.crop.startsWith('rice') ? 'more sheath blight and planthoppers, and stems that lodge'
            : c.crop.startsWith('corn') || c.crop === 'sorghum' ? 'barren stalks with small or no ' + (C.yield.word || 'ears') + ', and stalks that lodge'
            : C.group === 'tree' ? 'tall, shaded trees that bear less and are harder to spray and harvest'
            : 'smaller fruit and roots, more leaf disease, and plants that are harder to spray';
        switch (code) {
            case 'stand-sparse': return ['Very thin stand', many(r.standHa) + ' ' + W + ' a hectare is well under the ' + lo + ' to ' + hi + ' that fill a field of ' + nm + '. Much of the ground grows weeds instead of crop. Set the plants about ' + gp + ' apart ' + where + ', or put one more ' + U[0] + ' in each hill.'];
            case 'stand-thin': return ['A bit thin', 'Under the good range of ' + lo + ' to ' + hi + ' a hectare. Plants with room make up some of it, but the field is not full. About ' + gp + ' between plants ' + where + ' fills it.'];
            case 'stand-good': return ['Good stand', many(r.standHa) + ' ' + W + ' a hectare sits in the good range for ' + nm + ', ' + lo + ' to ' + hi + '.'];
            case 'stand-crowded': return ['Crowded', 'Over the top of the good range (' + hi + ' a hectare). The plants start to fight for light, water and fertilizer. About ' + gp + ' between plants ' + where + ' brings it back.'];
            case 'stand-danger': return ['Danger zone of crowding', many(r.standHa) + ' ' + W + ' a hectare is far past the ' + hi + ' a field of ' + nm + ' carries. Expect ' + crowdHarm + ', and seed spent for nothing. Widen to about ' + gp + ' between plants ' + where + ', or use fewer ' + U[1] + ' a hill.'];
            case 'hill-short': return ['Too few ' + U[1] + ' a hill', 'At ' + num(c.germ) + '% ' + (c.method === 'piece' ? 'taking root' : 'germination') + ', ' + r.perHill + ' ' + (r.perHill === 1 ? U[0] : U[1]) + ' a hill: ' + oneIn(r.emptyShare) + (k && k > 1 ? ', and ' + pct(r.shortShare) + ' of hills come up with fewer than ' + k : '') + '. '
                + (r.suggestOk ? 'Sow ' + r.suggest + ' a hill and thin to ' + (k || 1) + ', or keep some seed to fill the gaps in the first week.' : 'Even ' + r.suggest + ' a hill leaves gaps at this germination: get fresher seed, or fill the gaps by hand.')];
            case 'hill-extra': {
                const extraKg = r.wts ? (r.perHill - r.suggest) * r.hillsHa * (r.wts.mid) / 1000 / (r.nursery ? (c.germ / 100) * (c.fit / 100) : 1) : null;
                return ['More seed than needed', r.suggest + ' a hill already leaves fewer than 1 hill in 10 short at ' + num(c.germ) + '%. The ' + (r.perHill - r.suggest) + ' extra a hill' + (extraKg ? ' cost about ' + kgTxt(extraKg) + ' kg of seed a hectare' : ' cost seed') + ', and more thinning work.'];
            }
            case 'hill-crowded': return c.method === 'transplant' ? ['Too many in one hole', 'One ' + U[0] + ' a hole is enough for ' + nm + ': two in one hole fight for the same spot and both stay small.']
                : ['Crowded hills', 'More than ' + perHillFor(C, c.method)[2] + ' ' + U[1] + ' in one hill fight each other for the same spot. Use ' + perHillFor(C, c.method)[2] + ' or fewer, or close the spacing instead.'];
            case 'hill-ok': return c.method === 'piece' ? ['Right for the take', r.perHill + ' ' + (r.perHill === 1 ? U[0] : U[1]) + ' a hill at ' + num(c.germ) + '% taking root: ' + oneIn(r.emptyShare) + '. Keep a few spare ' + U[1] + ' to fill the gaps.']
                : r.replant ? ['Right for one seed a hill', '1 seed a hill at ' + num(c.germ) + '% germination: ' + oneIn(r.replant.share) + '. Replant those within a week: about ' + many(r.replant.seedsHa) + ' seeds' + (r.replant.kgHa ? ' (' + kgTxt(r.replant.kgHa) + ' kg)' : '') + ' a hectare are kept for it, and the stand counts them.']
                : ['Right for your germination', r.perHill + ' ' + (r.perHill === 1 ? 'seed' : 'seeds') + ' a hill at ' + num(c.germ) + '% germination: ' + oneIn(r.emptyShare) + '.'];
            case 'hill-thin': return ['Thin to ' + k + ' a hill', C.thinNote || 'One to two weeks after they come up, keep the ' + (k === 1 ? 'strongest' : k + ' strongest') + ' and pinch or cut the rest at the soil. Do not pull them: it loosens the roots of the plant you keep.'];
            case 'rice-seedlings': return ['More seedlings than pays', r.perHill + ' seedlings a hill do not make more panicles: a hill fills out to about the same tillers either way, while the seed and the seedbed cost more and the hill gets crowded. Use 1 to 2 for hybrids, 2 to 3 for inbreds.'];
            case 'row-close': return ['Rows closer than usual', 'The usual rows for ' + nm + ' are ' + span(C.row) + ' apart. Closer rows are hard to weed and spray between, and stay wet longer, which disease likes.'];
            case 'row-wide': return ['Rows wider than usual', 'Wider than the usual ' + span(C.row) + ': the ground between rows grows weeds unless you plant something there too.'];
            case 'plant-close': return ['Plants closer than usual', 'Closer than the usual ' + span(C.plant) + ' between plants. Check the stand above: close plants shade each other and stay small.'];
            case 'plant-wide': return ['Plants farther apart than usual', 'Farther apart than the usual ' + span(C.plant) + '. Each plant has room, but the field holds fewer.'];
            case 'germ-low': return ['Low germination', (r.seed && r.seed.type === 'saved' ? 'Seed saved from your own harvest usually comes up about ' + num(c.germ) + '%, under the ' + num(c.germMin) + '% good ' + nm + ' seed shows'
                + ((B.seedKinds[c.crop] || {}).default === 'hybrid' ? ', and saved hybrid seed also yields less than its parents' : '') + '.' : 'Good ' + nm + ' seed comes up ' + num(c.germMin) + '% or more.')
                + ' Test a handful before you sow (the steps are under Your seed), or buy fresh seed.'];
            case 'germ-high': return ['Very high germination', 'Few seed lots come up that well in the field: the number on the pack is from a lab test. Count on a few points less.'];
            case 'tiller-cap': return ['More tillers than a field holds', num(r.parts.tillers[1]) + ' tillers a hill at this spacing would be ' + num(r.hillsHa * r.filled * r.parts.tillers[1] / 10000) + ' panicles a square meter; a field rarely carries more than about ' + num(r.parts.ceiling[1]) + '. The yield counts ' + num(r.parts.ceiling[1]) + '.'];
            case 'stalk-cap': {
                const max = C.yield.stalkMax, raw = (r.plateau ? C.stand[1] : r.plantsHa) * r.parts.stalks[1] * r.parts.millable[1] / 100;
                return ['More stalks than a field holds', dec(r.parts.stalks[1], 2) + ' tillers a clump at this stand would make ' + many(raw) + ' millable stalks a hectare; a field rarely carries more than about ' + num(max) + ', and the rest die back in the shade. The yield counts ' + num(max) + '.'];
            }
            case 'yield-plateau': return ['Past the good range', 'Plants past ' + hi + ' a hectare do not add to the harvest: they share the same light, water and food. The yield counts ' + hi + ' plants.'];
        }
        return null;
    };

    /* ---- the field, drawn to scale ---- */
    const hash = (i, j) => { const x = Math.sin(i * 12.9898 + j * 78.233) * 43758.5453; return x - Math.floor(x); };
    const drawField = (c, r) => {
        const C = B.crops[c.crop], L = c.layout, er = r.effRow, p = r.plant;
        const rowsN = Math.max(2, Math.min(8, Math.floor(Math.sqrt(240 * p / (1.8 * er)))));
        let H, beds = 0;
        if (L === 'beds') { beds = 2; H = c.bed * beds; } else H = er * rowsN;
        const W = H * 1.8, band = H * 0.16, stroke = W / 400, rad = Math.max(Math.min(p, er) * 0.3, W / 160);
        const gapShare = r.replant ? Math.max(0, 1 - r.keptPerHill) : Math.pow(1 - r.pLive, r.perHill), dots = Math.min(3, Math.max(1, Math.round(r.keptPerHill / Math.max(0.01, 1 - gapShare)))) || 1;
        let s = '<svg viewBox="0 0 ' + W.toFixed(2) + ' ' + (H + band).toFixed(2) + '" role="img" aria-label="The spacing drawn to scale"><g class="f-in">';
        const rowsY = [];
        if (L === 'beds') {
            const path = Math.min(c.bed * 0.35, 30), bw = c.bed - path;
            for (let b = 0; b < beds; b++) {
                const y0 = b * c.bed + path / 2;
                s += '<rect x="0" y="' + y0.toFixed(2) + '" width="' + W.toFixed(2) + '" height="' + bw.toFixed(2) + '" fill="#c9a26b" opacity=".55"/>';
                for (let i = 0; i < c.bedRows; i++) rowsY.push(y0 + bw * (i + 0.5) / c.bedRows);
            }
        } else for (let i = 0; i < rowsN; i++) rowsY.push(er * (i + 0.5));
        rowsY.forEach((y) => { s += '<path d="M0 ' + y.toFixed(2) + 'H' + W.toFixed(2) + '" stroke="#b58a4f" stroke-width="' + (stroke * 1.4).toFixed(2) + '" opacity=".55"/>'; });
        let total = 0, gaps = 0;
        rowsY.forEach((y, i) => {
            const off = L === 'triangle' && i % 2 ? p / 2 : 0;
            for (let x = p / 2 + off, j = 0; x < W && j < 120; x += p, j++) {
                total++;
                if (hash(i + 1, j + 1) < gapShare) { gaps++; s += '<circle cx="' + x.toFixed(2) + '" cy="' + y.toFixed(2) + '" r="' + rad.toFixed(2) + '" fill="none" stroke="#e8590c" stroke-width="' + (stroke * 1.8).toFixed(2) + '" stroke-dasharray="' + (stroke * 2.2).toFixed(2) + '"/>'; continue; }
                if (dots === 1) s += '<circle cx="' + x.toFixed(2) + '" cy="' + y.toFixed(2) + '" r="' + rad.toFixed(2) + '" fill="#3f7a22"/>';
                else for (let d = 0; d < dots; d++) { const a = d / dots * Math.PI * 2, rr = rad * 0.45; s += '<circle cx="' + (x + Math.cos(a) * rr).toFixed(2) + '" cy="' + (y + Math.sin(a) * rr).toFixed(2) + '" r="' + (rad * 0.6).toFixed(2) + '" fill="#3f7a22"/>'; }
            }
        });
        // A scale bar near a quarter of the width.
        const nice = [5, 10, 20, 25, 50, 100, 200, 500, 1000, 2000, 5000];
        const bar = nice.reduce((b, v) => (v <= W / 3.2 ? v : b), nice[0]);
        const by = H + band * 0.6, bx = W * 0.03, tick = band * 0.36;
        s += '<path d="M0 ' + H.toFixed(2) + 'H' + W.toFixed(2) + '" stroke="currentColor" stroke-width="' + stroke.toFixed(2) + '" opacity=".25"/>';
        s += '<path d="M' + bx.toFixed(2) + ' ' + by.toFixed(2) + 'h' + bar + 'M' + bx.toFixed(2) + ' ' + (by - tick / 2).toFixed(2) + 'v' + tick.toFixed(2) + 'M' + (bx + bar).toFixed(2) + ' ' + (by - tick / 2).toFixed(2) + 'v' + tick.toFixed(2) + '" stroke="currentColor" stroke-width="' + (stroke * 2).toFixed(2) + '"/>';
        s += '<text x="' + (bx + bar + W * 0.015).toFixed(2) + '" y="' + (by + band * 0.15).toFixed(2) + '" font-size="' + (band * 0.42).toFixed(2) + '" font-weight="800" fill="currentColor">' + (bar >= 100 ? bar / 100 + ' m' : bar + ' cm') + '</text>';
        s += '</g></svg>';
        $('scField').innerHTML = s;
        const wM = W / 100, hM = H / 100;
        $('scFieldTitle').textContent = 'A patch of ' + num(wM, wM < 10 ? 1 : 0) + ' by ' + num(hM, hM < 10 ? 1 : 0) + ' m, to scale';
        $('scFieldKey').innerHTML = '<span><i style="background:#3f7a22"></i>A hill with ' + (dots === 1 ? 'its plant' : 'its ' + dots + ' plants') + '</span>'
            + (gaps ? '<span><i style="background:transparent;border:1.5px dashed #e8590c"></i>Empty at harvest, about ' + pct(gapShare) + '</span>' : '')
            + '<span><i style="background:#b58a4f;border-radius:1px"></i>' + (L === 'beds' ? 'Rows on the beds' : 'A row') + '</span>';
    };

    /* ---- paint the answer ---- */
    const calc = () => {
        const C = crop();
        const raw = {
            crop: st.crop, method: st.method, variety: st.variety, lotId: st.lotId, month: st.month, day: val('scDay'), layout: st.layout, mode: st.mode, area: val('scArea'), areaUnit: st.areaUnit,
            row: val('scRow'), plant: val('scPlant'), bed: val('scBed'), bedRows: val('scBedRows'), rowSet: st.rowSet,
            amount: val('scAmount'), amountUnit: st.amountUnit, amountPer: st.amountPer, target: val('scTarget'), targetPer: st.targetPer,
            hillMode: st.hillMode, perHill: val('scPerHill'), keep: val('scKeep'), seedType: st.seedType, pack: st.pack,
            weight: val('scWeight'), tillers: val('scTillers'), grains: val('scGrains'), filled: val('scFilled'), ears: val('scEars'), perEar: val('scPerEar'),
            perPlant: val('scPerPlant'), stalks: val('scStalks'), millable: val('scMillable'), stalkKg: val('scStalkKg'),
        };
        const c = withSeed(readIn(raw), st.vy), why = missingOf(c);
        try { localStorage.setItem(LS, JSON.stringify(raw)); } catch (e) {}
        $('scEmpty').hidden = !why; $('scRes').hidden = !!why;
        if (why) { $('scEmptySay').textContent = why; st.last = null; dock(); return; }
        const r = compute(c, st.vy, st.ctx);
        st.last = { c: raw, r };
        // The suggested count shows in its box.
        if (st.hillMode === 'auto' && Number($('scPerHill').value) !== r.perHill) $('scPerHill').value = r.perHill;
        $('scKeepBox').hidden = c.method === 'transplant' && perHillFor(C, c.method)[2] <= 1 && r.perHill <= 1;
        const rng = (v, d = 0) => v ? (Math.abs(v[0] - v[1]) < 1e-9 ? num(v[0], d) : num(v[0], d) + ' to ' + num(v[1], d)) : '';
        const U = units(C, c.method), W = standWord(C), lot = r.areaHa ? (r.areaHa >= 0.1 ? num(r.areaHa, r.areaHa < 10 ? 2 : 1) + ' ha' : num(r.areaHa * 10000) + ' m²') : '';
        const buyWord = r.nursery ? 'seeds' : U[1];
        const seedsRange = (a, b) => many(a) + ' to ' + many(b);
        // Under a kilo, seed reads in grams.
        const kgRange = (a, b) => (a === null ? '' : b < 1 ? num(a * 1000) + ' to ' + num(b * 1000) + ' g' : kgTxt(a) + ' to ' + kgTxt(b) + ' kg');
        const spaceTxt = () => c.layout === 'triangle' ? cmTxt(r.plant) + ' cm, in triangles' : c.layout === 'beds' ? cmTxt(r.plant) + ' cm, ' + c.bedRows + ' rows a bed' : cmTxt(r.row) + ' × ' + cmTxt(r.plant) + ' cm';
        // The headline.
        const worst = r.flags.some((f) => f[1] === 'bad') ? 'bad' : r.flags.some((f) => f[1] === 'warn') ? 'warn' : 'good';
        const head = $('scHead');
        if (st.tone !== worst) { head.className = 'sc-head is-' + worst; if (!still()) { void head.offsetWidth; head.classList.add('is-pop'); } st.tone = worst; }
        const kgLine = r.kgLo !== null ? kgRange(r.kgLo, r.kgHi) + ' of ' + (r.nursery ? 'seed' : U[1]) : seedsRange(r.buyLo, r.buyHi) + ' ' + buyWord;
        const bg = bagsOf(r), bagLine = bg ? 'That is ' + bg.haTxt + ' of ' + packSize(r.pack) + ' a hectare. ' : '';
        const lotLine = lot ? ' For your ' + lot + ': ' + (r.kgLotLo !== null ? kgRange(r.kgLotLo, r.kgLotHi) : seedsRange(r.buyLotLo, r.buyLotHi) + ' ' + buyWord) + (bg && bg.lotTxt ? ', ' + bg.lotTxt : '') + '.' : '';
        if (c.mode === 'seeds') {
            $('scHKick').textContent = cap(r.nursery ? 'seed' : U[1]) + ' you need, a hectare';
            $('scHBig').textContent = r.kgLo !== null ? kgRange(r.kgLo, r.kgHi) : seedsRange(r.buyLo, r.buyHi);
            $('scHSub').textContent = (r.kgLo !== null ? seedsRange(r.buyLo, r.buyHi) + ' ' + buyWord + '. ' : '') + bagLine + (r.nursery ? 'Sown in the seedbed for ' + many(r.fieldHa) + ' seedlings. ' : '') + 'The top keeps ' + num(r.reserve) + '% for replanting' + (r.replant ? ' the empty hills' : '') + '.' + lotLine;
        } else if (c.mode === 'spacing') {
            $('scHKick').textContent = 'The spacing your ' + buyWord + ' fill';
            $('scHBig').textContent = spaceTxt();
            $('scHSub').textContent = (r.square && Math.abs(r.square - r.row) > 0.5 ? 'Or ' + cmTxt(r.square) + ' × ' + cmTxt(r.square) + ' cm square. ' : '') + (r.plantSpan ? 'By seed size, ' + cmTxt(r.plantSpan[0]) + ' to ' + cmTxt(r.plantSpan[1]) + ' cm between plants. ' : '')
                + (c.amountUnit !== 'count' && r.given ? 'Your ' + num(c.amount, c.amount < 10 ? 2 : 0) + ' ' + (c.amountUnit === 'kg' ? 'kg' : 'g') + ' is about ' + many(r.given) + ' ' + buyWord + '. ' : '') + num(r.reserve) + '% kept for replanting.';
        } else {
            $('scHKick').textContent = 'For ' + many(c.targetPer === 'lot' && r.areaHa ? c.target / r.areaHa : c.target) + ' ' + W + ' a hectare';
            $('scHBig').textContent = spaceTxt();
            $('scHSub').textContent = (r.square && Math.abs(r.square - r.row) > 0.5 ? 'Or ' + cmTxt(r.square) + ' × ' + cmTxt(r.square) + ' cm square. ' : '') + 'And ' + kgLine + ' a hectare. ' + bagLine + lotLine;
        }
        // The stand.
        const [lo, hi] = C.stand, chipTxt = { sparse: 'Very thin', thin: 'A bit thin', good: 'Good', crowded: 'Crowded', danger: 'Danger zone' }[r.status];
        const chipTone = { sparse: 'bad', thin: 'warn', good: 'good', crowded: 'warn', danger: 'bad' }[r.status];
        $('scStandChip').className = 'sc-chip is-' + chipTone; $('scStandChip').textContent = chipTxt;
        $('scStandTitle').textContent = r.byHills ? 'Hills at harvest' : 'Final stand';
        countTo($('scStandN'), r.standHa, (x) => many(x));
        $('scStandSub').textContent = W + ' a hectare' + (r.byHills ? ', ' + many(r.plantsHa) + ' plants' : '') + (lot ? ' · ' + many(r.standLot) + ' on your ' + lot : '');
        const top = Math.max(hi * 1.6, Math.min(r.standHa * 1.08, hi * 3));
        const z = [lo * K.thin, lo - lo * K.thin, hi - lo, hi * K.crowd - hi, top - hi * K.crowd].map((v) => Math.max(0, v) / top * 100);
        ['scZ1', 'scZ2', 'scZ3', 'scZ4', 'scZ5'].forEach((id, i) => { $(id).style.flexBasis = z[i] + '%'; });
        const at = (v) => Math.max(2, Math.min(98, v / top * 100)) + '%';
        $('scGMark').style.left = at(r.standHa); $('scGLab').textContent = 'You: ' + many(r.standHa);
        $('scGLo').style.left = at(lo); $('scGLo').textContent = many(lo); $('scGHi').style.left = at(hi); $('scGHi').textContent = many(hi);
        $('scEmptyLine').textContent = (r.replant ? 'With 1 seed a hill at ' + num(c.germ) + '% germination, ' + oneIn(r.replant.share) + ': replant those within a week (about ' + many(r.replant.seedsHa) + ' seeds a hectare). The stand counts them, so '
                + (r.replant.after <= 0.005 ? 'almost every hill is filled. ' : 'about 1 hill in ' + num(Math.round(1 / r.replant.after)) + ' stays empty. ')
            : c.method === 'direct' ? 'With ' + r.perHill + ' ' + (r.perHill === 1 ? 'seed' : 'seeds') + ' a hill at ' + num(c.germ) + '% germination, ' + oneIn(r.emptyShare) + '. '
            : c.method === 'piece' ? 'With ' + r.perHill + ' ' + (r.perHill === 1 ? U[0] : U[1]) + ' a hill at ' + num(c.germ) + '% taking root, ' + oneIn(r.emptyShare) + '. '
            : 'With ' + num(c.loss) + '% lost after transplanting, ' + oneIn(r.emptyShare, true) + '. ')
            + (r.byHills ? '' : 'Each hill keeps ' + num(r.keptPerHill, 2) + ' plants on average' + (c.keep !== null && r.perHill > c.keep ? ' after thinning to ' + c.keep : '') + '.')
            + (r.usual && r.usual.standHa > r.standHa + 0.5 ? ' Your field and season take about ' + pctOff(r.standHa / r.usual.standHa) + ' of the stand: ' + many(r.usual.standHa) + ' on a usual field.' : '');
        drawField(c, r);
        paintFS(c, r);
        paintSeed(c, r);
        paintRange(c, r);
        paintBuy(c, r, lot);
        // The flags.
        const order = { bad: 0, warn: 1, info: 2, good: 3 };
        $('scFlags').innerHTML = r.flags.slice().sort((a, b) => order[a[1]] - order[b[1]]).map((f, i) => {
            const t = sayFlag(f[0], c, r);
            return t ? '<div class="sc-flag is-' + f[1] + '" style="animation-delay:' + Math.min(i, 6) * 45 + 'ms"><i>' + flagIco[f[1]] + '</i><span><b>' + esc(t[0]) + '</b><p>' + esc(t[1]) + '</p></span></div>' : '';
        }).join('');
        // The numbers.
        const cells = [
            ['Hills a hectare', many(r.hillsHa), lot ? many(r.hillsLot) + ' on your lot' : ''],
            [cap(W) + ' a square meter', num(r.standHa / 10000, r.standHa / 10000 < 10 ? 1 : 0), 'at harvest'],
            ['Ground for one hill', r.areaHill >= 10000 ? num(r.areaHill / 10000, 2) + ' m²' : num(r.areaHill) + ' cm²', c.layout === 'beds' ? 'paths counted' : ''],
            [cap(U[1]) + ' a hill', String(r.perHill), r.kept < r.perHill ? 'thin to ' + r.kept : st.hillMode === 'auto' ? 'suggested' : 'your own'],
            r.nursery ? ['Seedlings to plant', many(r.fieldHa), 'a hectare'] : ['Empty hills', pct(r.emptyShare), c.method === 'transplant' ? 'after planting out' : 'when they come up'],
            r.kgLo !== null ? ['Seeds a hectare', seedsRange(r.buyLo, r.buyHi), r.wts && r.wts.own ? 'by your seed weight' : 'by the usual seed sizes'] : ['With the reserve', many(r.buyHi), 'a hectare'],
        ];
        if (r.panM2 !== null) cells.push(['Panicles a square meter', rng([r.panM2Lo, r.panM2]), r.capped ? 'at the most a field holds' : rng(r.parts.tillers) + ' tillers a hill']);
        if (r.stalksHa !== null) cells.push(['Millable stalks a hectare', rng([r.stalksHaLo, r.stalksHa]), r.capped ? 'at the most a field holds' : drng(r.parts.stalks, 2) + ' tillers a clump']);
        $('scNums').innerHTML = cells.map((x) => '<div>' + esc(x[0]) + '<b>' + esc(x[1]) + '</b>' + (x[2] ? '<small>' + esc(x[2]) + '</small>' : '') + '</div>').join('');
        // The harvest.
        const Y = C.yield, perYr = Y.perYear ? ' a year' : '';
        let big = '', sub = '', say = '';
        if (r.kgHaHi !== null) {
            big = (r.kgHaHi >= 1000 ? num(r.kgHaLo / 1000, 1) + ' to ' + num(r.kgHaHi / 1000, 1) + ' t' : kgTxt(r.kgHaLo) + ' to ' + kgTxt(r.kgHaHi) + ' kg');
            sub = Y.what + ' a hectare' + perYr + (Y.sack ? ' · ' + num(r.kgHaLo / Y.sack) + ' to ' + num(r.kgHaHi / Y.sack) + ' cavans of ' + Y.sack + ' kg' : '') + (r.piecesHa !== null ? ' · ' + many(r.piecesHaLo) + ' to ' + many(r.piecesHa) + ' ' + (Y.word || 'pieces') : '');
            say = (lot ? 'For your ' + lot + ': ' + massTxt(r.yLotLo) + ' to ' + massTxt(r.yLotHi) + perYr + '. ' : '');
        } else if (r.piecesHa !== null) {
            big = many(r.piecesHaLo) + ' to ' + many(r.piecesHa);
            sub = Y.what + ' a hectare' + perYr;
            say = lot ? 'For your ' + lot + ': ' + many(r.piecesLotLo) + ' to ' + many(r.piecesLot) + perYr + '. ' : '';
        }
        $('scYBig').textContent = big; $('scYSub').textContent = sub;
        const T = st.trial;
        $('scYTitle').textContent = (T ? 'What this stand can carry' : 'Potential harvest') + (Y.perYear ? ', at full bearing' : '');
        $('scTrial').hidden = !T;
        if (T) {
            // What the variety gave in its trials, beside the count: never multiplied into it.
            const unit = String(T.unit || 't/ha').toLowerCase(), toKg = unit === 't/ha' ? 1000 : unit === 'kg/ha' ? 1 : null;
            const aKg = toKg ? T.avg * toKg : null, tKg = toKg && T.top ? T.top * toKg : null;
            $('scTrialBig').textContent = toKg ? massTxt(aKg) : num(T.avg, 1) + ' ' + T.unit;
            $('scTrialSub').textContent = (toKg ? Y.what + ' a hectare on average' : 'on average') + (tKg ? ', up to ' + massTxt(tKg) + ' at best' : T.top && !toKg ? ', up to ' + num(T.top, 1) + ' at best' : '')
                + (lot && toKg ? ' · ' + massTxt(aKg * r.areaHa) + ' on your ' + lot : '');
            let ts = '';
            if (toKg && r.kgHaHi) {
                const q = (r.kgHaLo + r.kgHaHi) / 2 / aKg;
                if (q > 1.35) ts = 'The count assumes every hill does its best. The trials show a real field, so a harvest nearer the trial figure is more likely.';
                else if (q < 0.75) ts = 'The count is well under the trials: check the stand, and the numbers you typed.';
            }
            $('scTrialSay').textContent = ts; $('scTrialSay').hidden = !ts;
        }
        const yIdeal = r.usual ? (r.kgHaHi !== null && r.usual.kgHaHi ? r.kgHaHi / r.usual.kgHaHi : r.piecesHa !== null && r.usual.piecesHa ? r.piecesHa / r.usual.piecesHa : 1) : 1;
        // Sugarcane, said plainly: "About 2.3 millable stalks a clump of the 4 tillers, about 1.5 kg each."
        const midOf = (v) => (v[0] + v[1]) / 2;
        const caneSay = Y.model !== 'stalk' ? '' : (() => {
            const t = midOf(r.parts.stalks), m = t * midOf(r.parts.millable) / 100;
            return 'About ' + dec(m, 1) + ' millable ' + (dec(m, 1) === '1' ? 'stalk' : 'stalks') + ' a clump of the ' + dec(t, 1) + ' ' + (dec(t, 1) === '1' ? 'tiller' : 'tillers') + ', about ' + dec(midOf(r.parts.stalkKg), 2) + ' kg each'
                + (r.capped ? '. That is more than a field carries, so the count stops at ' + num(Y.stalkMax) + ' millable stalks a hectare' : '') + '. ';
        })();
        $('scYSay').textContent = (yIdeal < 0.995 ? 'Your field and season take about ' + pctOff(yIdeal) + ' of it. ' : '') + (hasParts(st.vy) ? 'Counted with the own yield parts of ' + st.variety + ', from its records. ' : '') + say + caneSay + 'The top is what the ' + (Y.model === 'stalk' ? 'clumps' : r.byHills ? 'hills' : 'plants') + ' can carry'
            + (Y.model === 'tiller' ? ' (' + rng([r.panM2Lo, r.panM2]) + ' panicles a square meter × ' + rng(r.parts.grains) + ' grains × ' + rng(r.parts.filled) + '% filled)' : Y.model === 'stalk' ? ' (' + rng([r.stalksHaLo, r.stalksHa]) + ' millable stalks a hectare)' : '') + '; the bottom ' + (Object.values(r.parts).some((v) => v && Math.abs(v[0] - v[1]) > 1e-9) ? 'counts the low end of each part and takes off' : 'takes off') + ' the ' + num(c.harvestLoss) + '% lost at harvest. Weather, pests and fertilizer decide where in between.';
        const yHi = r.kgHaHi ?? r.piecesHa ?? 0, yLo = r.kgHaLo ?? r.piecesHaLo ?? 0, ySc = Math.max(1, yHi * 1.12);
        $('scYBar').style.left = (yLo / ySc * 100) + '%'; $('scYBar').style.width = Math.max(3, (yHi - yLo) / ySc * 100) + '%';
        $('scYield').hidden = !big;
        // How it was counted.
        const steps = [
            c.layout === 'beds' ? 'A bed and its path of ' + cmTxt(c.bed) + ' cm hold ' + c.bedRows + ' rows, so each row has ' + cmTxt(r.effRow) + ' cm of ground. × ' + cmTxt(r.plant) + ' cm between plants = ' + num(r.areaHill) + ' cm² a hill.'
                : c.layout === 'triangle' ? 'In a triangle of ' + cmTxt(r.plant) + ' cm the rows sit ' + cmTxt(r.effRow) + ' cm apart (0.866 of the side), so a hill takes ' + num(r.areaHill) + ' cm².'
                : cmTxt(r.row) + ' cm between rows × ' + cmTxt(r.plant) + ' cm between plants = ' + num(r.areaHill) + ' cm² a hill.',
            'A hectare is 100,000,000 cm², so it holds ' + many(r.hillsHa) + ' hills.',
            many(r.hillsHa) + ' hills × ' + r.perHill + ' ' + (r.perHill === 1 ? U[0] : U[1]) + ' = ' + many(r.fieldHa) + ' ' + U[1] + '.' + (r.nursery ? ' The seedbed gives ' + num(c.germ) + '% × ' + num(c.fit) + '% of its seeds as seedlings fit to plant, so sow ' + many(r.buyLo) + ' seeds.' : ''),
            'Plus ' + num(r.reserve) + '% for replanting' + (r.replant ? ' the empty hills' : '') + ': ' + many(r.buyHi) + '.' + (r.kgLo !== null ? ' At ' + (r.wts.own ? num(r.wts.mid * (c.method === 'piece' ? 1 : 1000), 1) + ' g' : num(r.wts.lo * (c.method === 'piece' ? 1 : 1000), 1) + ' to ' + num(r.wts.hi * (c.method === 'piece' ? 1 : 1000), 1) + ' g') + (c.method === 'piece' ? ' a ' + U[0] : ' for 1,000 seeds') + ', that is ' + kgRange(r.kgLo, r.kgHi) + '.' : ''),
            ...(bg && c.mode !== 'spacing' ? ['A ' + r.pack.label + ' holds ' + packSize(r.pack) + ': ' + (r.pack.kind === 'seeds' ? many(r.buyLo) + ' to ' + many(r.buyHi) + ' seeds' : kgRange(r.kgLo, r.kgHi)) + ' ÷ '
                + (r.pack.kind === 'seeds' ? num(r.pack.size) : num(r.pack.size, r.pack.size % 1 ? 1 : 0) + ' ' + r.pack.kind) + ' = ' + rangeTxt(r.packLo, r.packHi, f2) + ' ' + packLabel(r.pack, 2) + ', so ' + num(upN(r.packHi)) + ' ' + packLabel(r.pack, upN(r.packHi)) + '.'] : []),
            (c.method === 'transplant' ? 'Each seedling lives with a chance of ' + num(100 - c.loss) + '%' : 'Each ' + U[0] + ' comes up with a chance of ' + num(c.germ) + '%') + '; a hill with none is empty (' + pct(r.emptyShare) + ').' + (c.keep !== null && r.perHill > c.keep ? ' Thinned to ' + c.keep + ', a hill keeps ' + num(r.keptPerHill, 2) + ' plants on average' : ' A hill keeps ' + num(r.keptPerHill, 2) + ' plants on average') + (c.method !== 'transplant' ? ', after ' + num(c.loss) + '% lost later.' : '.'),
            (r.byHills ? many(r.hillsHa) + ' hills × ' + pct(r.filled) + ' with plants = ' + many(r.standHa) + ' hills at harvest.' : many(r.hillsHa) + ' hills × ' + num(r.keptPerHill, 2) + ' = ' + many(r.plantsHa) + ' plants at harvest.') + ' The good range is ' + num(lo) + ' to ' + num(hi) + '.',
        ];
        if (r.kgHaHi !== null || r.piecesHa !== null) {
            steps.push(Y.model === 'tiller' ? rng([r.panM2Lo, r.panM2]) + ' panicles a square meter × ' + rng(r.parts.grains) + ' grains × ' + rng(r.parts.filled) + '% filled × ' + rng(r.parts.gw, 1) + ' g for 1,000 grains = up to ' + massTxt(r.kgHaHi) + ' a hectare.'
                : Y.model === 'ear' ? many(r.plateau ? hi : r.plantsHa) + ' plants × ' + rng(r.parts.ears, 2) + ' ' + (Y.word || 'ears') + ' × ' + rng(r.parts.perEar) + ' g = up to ' + massTxt(r.kgHaHi) + ' a hectare.'
                : Y.model === 'stalk' ? many(r.plateau ? hi : r.plantsHa) + ' clumps × ' + drng(r.parts.stalks, 2) + ' tillers × ' + drng(r.parts.millable, 1) + '% that grow into millable cane = ' + rng([r.stalksHaLo, r.stalksHa]) + ' millable stalks'
                    + (r.capped ? ' (a field carries about ' + num(Y.stalkMax) + ' at the most)' : '') + ' × ' + drng(r.parts.stalkKg, 2) + ' kg = up to ' + massTxt(r.kgHaHi) + ' a hectare.'
                : many(r.plateau ? hi : r.plantsHa) + ' plants × ' + rng(r.parts.perPlant, r.parts.perPlant[1] < 1 ? 3 : 1) + ' ' + ((Y.unit || 'kg') === 'count' ? Y.what : Y.unit) + ' = up to ' + (r.kgHaHi !== null ? massTxt(r.kgHaHi) : many(r.piecesHa) + ' ' + Y.what) + ' a hectare' + perYr + '.');
        }
        $('scHow').innerHTML = steps.map((x) => '<li>' + esc(x) + '</li>').join('');
        // The dock.
        $('scDockBig').textContent = c.mode === 'seeds' ? $('scHBig').textContent : spaceTxt();
        $('scDockSub').textContent = many(r.standHa) + ' ' + W + ' a hectare · ' + chipTxt + (bg && c.mode !== 'spacing' ? ' · ' + (bg.lotTxt || bg.haTxt) : '');
        dock();
    };
    ['scArea', 'scRow', 'scPlant', 'scBed', 'scBedRows', 'scAmount', 'scTarget', 'scPerHill', 'scKeep', 'scWeight',
        'scTillers', 'scGrains', 'scFilled', 'scEars', 'scPerEar', 'scPerPlant', 'scStalks', 'scMillable', 'scStalkKg'].forEach((id) => $(id).addEventListener('input', calc));

    /* ---- the phone dock: shown while the answer is out of sight ---- */
    let resSeen = false;
    const dock = () => {
        const d = $('scDock'), on = !!st.last && !resSeen && !$('scPaneCalc').hidden;
        d.hidden = false;
        d.classList.toggle('is-off', !on);
    };
    try {
        new IntersectionObserver((es) => { resSeen = es.some((e) => e.isIntersecting); dock(); }, { threshold: 0.05 }).observe($('scOut'));
    } catch (e) {}
    $('scDockGo').addEventListener('click', () => $('scOut').scrollIntoView({ behavior: still() ? 'auto' : 'smooth', block: 'start' }));


    /* ---- the lot and the planting month (2026-10-19, the owner): the field and the season count in ---- */
    let LOTP = null, ctxAsk = 0, ctxT = 0;
    const MONTHS = (() => { const out = [], d = new Date(); d.setDate(1); for (let i = 0; i < 18; i++) { out.push(d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0')); d.setMonth(d.getMonth() + 1); } return out; })();
    const monthWord = (ym) => (ym && window.aneeMonthPick ? window.aneeMonthPick.label(ym) : ym || 'Not set');
    function paintWhen() { tag('scMonth', st.month ? monthWord(st.month) : 'Not set'); $('scMonthX').hidden = !st.month; $('scNoLot').hidden = !st.lotId; }
    function ctxSoon(ms = 250) { clearTimeout(ctxT); ctxT = setTimeout(loadCtx, ms); }
    async function loadCtx() {
        paintWhen();
        if (!st.lotId && !st.month) { st.ctx = null; st.ctxBusy = false; calc(); return; }
        const ask = ++ctxAsk;
        st.ctxBusy = true; calc();
        try {
            const q = new URLSearchParams({ crop: st.crop, lotId: st.lotId || '', month: st.month || '', day: $('scDay').value || '' }).toString();
            const res = await window.api(B.urls.context + '?' + q, { method: 'GET' });
            if (ask !== ctxAsk) return;
            st.ctx = res.data || null;
            if (st.ctx && st.lotId && !st.ctx.lot) st.lotId = null;
        } catch (e) { if (ask !== ctxAsk) return; st.ctx = null; }
        st.ctxBusy = false; calc();
    }
    // The size field folds away while the lot in use has its own size.
    const paintAreaF = () => {
        const off = !!(st.lot && Number(st.lot.areaHa) > 0), f = $('scAreaF');
        f.classList.toggle('is-off', off);
        f.toggleAttribute('inert', off);
    };
    const useLotArea = (lot) => {
        const a = Number(lot && lot.areaHa);
        if (!(a > 0)) return;
        st.areaUnit = lot.areaUnit === 'sqm' || a < 0.1 ? 'sqm' : 'ha';
        $('scArea').value = String(st.areaUnit === 'sqm' ? Math.round(a * 10000) : Math.round(a * 10000) / 10000);
        tag('scAreaUnit', B.areaUnits[st.areaUnit][0]);
    };
    const mountLots = () => {
        if (LOTP || !window.aneeLots) return;
        LOTP = window.aneeLots.picker($('scLots'), { crop: () => crop().book || st.crop, value: st.lotId || null, onPick: (lot) => {
            const was = st.lotId;
            st.lotId = lot ? lot.id : null; st.lot = lot || null;
            if (lot && (lot.id !== was || Number(lot.areaHa) > 0)) useLotArea(lot);
            paintAreaF();
            ctxSoon(0);
        } });
    };
    if (window.aneeLots) mountLots(); else document.addEventListener('DOMContentLoaded', mountLots, { once: true });
    $('scNoLot').addEventListener('click', () => { st.lotId = null; st.lot = null; if (LOTP) LOTP.set(null); paintAreaF(); ctxSoon(0); });
    $('scMonth').addEventListener('click', async () => {
        if (!window.aneeMonthPick) return;
        const k = await window.aneeMonthPick.open({ months: MONTHS, value: st.month, title: 'Which month will you plant?' });
        if (!k || k === st.month) return;
        st.month = k; ctxSoon(0);
    });
    $('scMonthX').addEventListener('click', () => { st.month = null; ctxSoon(0); });
    $('scDay').addEventListener('input', () => ctxSoon(700));

    /* ---- the field and the season, in words ---- */
    const pctOff = (k) => Math.max(1, Math.round((1 - k) * 100)) + '%';
    const isoDay = (iso, add = 0) => { const d = new Date(iso + 'T00:00:00'); d.setDate(d.getDate() + add); return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' }); };
    const lcFirst = (s) => s ? s.charAt(0).toLowerCase() + s.slice(1) : s;
    const factsWords = (f) => {
        if (!f) return 'no answers about its soil and water yet';
        const T = { sandy: 'sandy', loam: 'loam', silt: 'silty', clay: 'clay', heavy_clay: 'heavy clay that cracks' };
        const bits = [T[f.texture] || '', { slow: 'drains slowly', stands: 'water stands for days' }[f.drainage] || '', f.flooding === 'often' ? 'floods most wet seasons' : '',
            f.saline || f.waterSalty ? 'signs of salt' : '', f.sodic ? 'a sodic crust' : '', f.sulfate ? 'acid sulfate' : f.ph !== null ? 'pH ' + num(f.ph, 1) : f.acidity === 'strong' ? 'strongly acidic' : '',
            f.hardpan === 'yes' ? 'a hardpan' : '', f.depth === 'shallow' ? 'shallow over rock' : '',
            { irrigated: 'watered all season', pump: 'watered from a pump or a well', partial: 'water part of the time', rainfed: 'rain only' }[f.water] || 'its water not answered'];
        return bits.filter(Boolean).join(', ');
    };
    const penWords = (l, g, c, r) => {
        const C = crop(), nm = shortName(C), [code, k, from, v] = l, f = st.ctx && st.ctx.facts, W = r.pen.W, SF = r.pen.SF, yrs = W ? W.years : 20;
        const eff = 'About ' + pctOff(k) + ' ' + (g === 'growth' ? (code === 'flower' ? 'fewer filled grains' : 'less harvest') : g === 'seedbed' ? 'fewer come up in the seedbed, so sow more' : c.method === 'transplant' ? 'fewer take after planting out' : 'fewer come up');
        const seasonNext = ' Pick the month to count your season.';
        switch (code) {
            case 'salt': return ['Salt, about ' + num(v, 1) + ' dS/m' + (f && f.ec !== null ? ', by your soil test' : f && f.salt.includes('sea') ? ', sea water at high tide' : ', from the signs of salt'), eff + (c.method === 'transplant' ? ': young seedlings feel salt the most.' : ': seeds stand salt better than seedlings, but not this much.')];
            case 'sodic': return ['A sodic crust that seals the soil', eff + ': small seeds push through it worst.'];
            case 'wet': return [v === 'stands' ? 'Water stands for days after rain' : 'Some spots stay wet for days', eff + (c.method === 'transplant' ? ': young plants drown and their roots rot.' : ': seeds rot and young plants drown.')];
            case 'flood': return ['Floods most wet seasons, planted in the flood months', eff + '.'];
            case 'dsrwater': return ['Water stands on the field', eff + ': direct seeded rice wants a drained surface its first week.'];
            case 'sulfate': return ['Acid sulfate soil', eff + ': hard on young roots.'];
            case 'acid': return ['Strongly acidic soil' + (v !== null && v !== undefined ? ', pH ' + num(v, 1) : ''), eff + ': aluminum stunts young roots. Lime helps.'];
            case 'crust': return ['Clay or silt that crusts after a hard rain', from === 'lot' ? eff + ', at an average chance of a hard rain.' + seasonNext : eff + ': ' + pct(v) + ' of the last ' + yrs + ' years brought one in the first three weeks.'];
            case 'sandy': return ['Sandy soil on rain only', eff + ': seeds dry out before they root.' + seasonNext];
            case 'heavy': return from === 'forecast' ? ['Heavy rain in the forecast', eff + ': the forecast shows a downpour in your first week. Sow after it if you can.']
                : ['A heavy start', eff + ': ' + pct(v) + ' of the last ' + yrs + ' years brought 300 mm or more in the first three weeks.'];
            case 'dry': return from === 'forecast' ? ['Almost no rain in the forecast', eff + ': your first week looks dry. Water the rows, or sow after a rain.']
                : ['A dry start', eff + ': ' + pct(v) + ' of the last ' + yrs + ' years brought under 30 mm in the first three weeks, and the field waits on the rain.'];
            case 'hot': return ['Too hot for ' + nm + ' seed, about ' + num(v, 1) + '°C in the warm part of the day', eff + (g === 'seedbed' ? ': shade the seedbed, or sow in a cooler month.' : '.')];
            case 'cool': return ['Cool for ' + nm + ' seed, about ' + num(v, 1) + '°C on average', eff + ': it comes up slowly, and more of it rots.'];
            case 'cap-rainfed': return ['Rain only', eff + ': with no other water, the rain decides.' + seasonNext];
            case 'cap-partial': return ['Water only part of the time', eff + '.' + seasonNext];
            case 'cap-saline': return ['Salty soil', eff + ', as NPK Plus counts it.'];
            case 'cap-sodic': return ['Sodic soil', eff + ', as NPK Plus counts it.'];
            case 'season-water': return ["The season's water", eff + ': the rain' + (f && f.water === 'pump' ? ' and your pump meet' : f && f.water === 'partial' ? ' and the water source meet' : ' meets') + ' about ' + pct(v) + ' of what the crop drinks'
                + (SF && SF.pump && SF.pump.low.length ? ', with the pump low in ' + monthWords(SF.pump.low) + (SF.pump.given ? '' : ', the usual dry months') : '') + '.'];
            case 'season-sun': return ["The season's sun", eff + ': about ' + num(v, 1) + ' MJ a day, under the ' + (SF ? SF.K.rad : 17) + ' a full crop wants.'];
            case 'season-temp': return ["The season's heat", eff + ': about ' + num(v, 1) + '°C on average' + (SF && SF.hotN >= 1 ? ', with about ' + num(SF.hotN) + ' days too hot for its flowers' : '') + '.'];
            case 'hardpan': return ['A hard layer under the plow depth', eff + ': roots and tubers fork and stay small.'];
            case 'shallow': return ['Rock or gravel within a hand span', eff + ': no room for roots and tubers.'];
            case 'hardpandry': return ['A hardpan under a crop on rain only', eff + ': shallow roots run dry sooner.'];
            case 'growwet': return [v === 'stands' ? 'Water standing for days through the season' : 'Slow drainage through the season', eff + '.'];
            case 'flower': return ['Heat at flowering', eff + ': about ' + num(v, 1) + ' more days of 35°C or more than usual when it flowers.'];
        }
        return [code, eff + '.'];
    };
    function paintFS(c, r) {
        const box = $('scFS'), X = st.ctx, on = !!(st.lotId || st.month);
        box.hidden = !on;
        if (!on) return;
        const C = crop(), lot = X && X.lot, S = X && X.season, P = r.pen;
        $('scFSLot').innerHTML = lot ? 'From <b>' + esc(lot.name) + '</b>' + (lot.where ? ' (' + esc(lot.where) + ')' : '') + ': ' + esc(factsWords(X.facts)) + '.'
            : st.lotId ? 'Reading the lot…' : 'No lot picked, so no soil or water is counted.';
        let sh = '';
        if (S && S.hist && S.hist.avg) {
            const a = S.hist.avg, t = ensoTiltOf(S.enso || null, S.window);
            sh = '<b>' + esc(isoDay(S.from)) + ' to ' + esc(isoDay(S.from, S.days - 1)) + '</b> (' + S.days + ' days) at ' + esc(S.place) + '. In the same weeks of the last ' + S.hist.years.length + ' years: about <b>' + num(a.rain) + ' mm</b> of rain, <b>'
                + num(a.rad, 1) + ' MJ</b> of sun a day, an average of <b>' + num(a.tmean, 1) + '°C</b>, and ' + num(a.hot32) + ' days at 32°C or more.';
            if (S.enso && S.enso.phase && S.enso.phase !== 'neutral' && S.enso.label) {
                sh += '<br><span class="sc-enso' + (S.enso.phase === 'la_nina' ? ' is-wet' : '') + '">With ' + esc(lcFirst(S.enso.label)) + ' ahead, about ' + Math.round(Math.abs(1 - t.rain) * 100) + '% ' + (t.rain < 1 ? 'less' : 'more') + ' rain'
                    + (Math.abs(t.temp) >= 0.05 ? ' and ' + num(Math.abs(t.temp), 1) + '°C ' + (t.temp > 0 ? 'warmer' : 'cooler') : '') + '</span>';
            }
            const fc = P.W && P.W.fc;
            if (fc) sh += '<br>Forecast for your first days, ' + esc(isoDay(fc.from)) + ' to ' + esc(isoDay(fc.to)) + ': ' + (fc.mmDays ? 'about ' + num(fc.sum) + ' mm of rain in the first week, ' : '') + 'highs near ' + num(fc.tx) + '°C, an average near ' + num(fc.tm) + '°C.';
        } else if (st.ctxBusy && st.month && st.lotId) sh = 'Reading twenty years of weather at the lot…';
        else if (st.month) sh = ({ nolot: 'Pick a lot to read the weather where it is.', noplace: 'This lot has no town yet: add it to the lot to read its weather.', nohistory: 'No weather history could be read for this place yet.' })[X && X.why] || (st.lotId ? '' : 'Pick a lot to read the weather where it is.');
        else sh = 'Pick the planting month to count the season too.';
        const sv = $('scFSSeason');
        sv.innerHTML = sh; sv.classList.toggle('is-busy', !!st.ctxBusy);
        let lh = '';
        [['emerge', c.method === 'transplant' ? 'When they are planted out' : 'When they come up'], ['seedbed', 'In the seedbed'], ['growth', 'While it grows']].forEach(([g, title]) => {
            const L = P[g].lines;
            if (!L.length) return;
            lh += '<p class="sc-fs-g">' + title + '</p>' + L.map((l, i) => {
                const w = penWords(l, g, c, r), src = l[2] === 'lot' ? 'from your lot' : l[2] === 'forecast' ? 'the forecast' : 'the season';
                return '<div class="sc-pl" style="animation-delay:' + Math.min(i, 6) * 40 + 'ms"><b class="' + (1 - l[1] >= 0.15 ? 'is-bad' : '') + '">−' + pctOff(l[1]) + '</b><span>' + esc(w[0]) + '<i>' + src + '</i><small>' + esc(w[1]) + '</small></span></div>';
            }).join('');
            const k = g === 'growth' ? P.growth.k * P.flower : P[g].k;
            if (L.length > 1) lh += '<p class="sc-fs-note">Together: about ' + pctOff(k) + ' ' + (g === 'growth' ? 'less harvest' : 'fewer') + ', held to ' + (g === 'growth' ? 'at most 60%' : 'at most half') + '.</p>';
        });
        if (P.notes.includes('nowater')) lh += '<p class="sc-fs-note">The lot does not say where its water comes from, so it is counted as watered all season.</p>';
        if (!lh && (lot || S)) lh = '<p class="sc-fs-note">Nothing in this lot or season takes from the stand or the harvest.</p>';
        $('scFSLines').innerHTML = lh;
        const Y = C.yield, yt = (lo, hi) => lo === null || lo === undefined ? 'None' : hi >= 1000 ? num(lo / 1000, 1) + ' to ' + num(hi / 1000, 1) + ' t' : kgTxt(lo) + ' to ' + kgTxt(hi) + ' kg';
        const yr = (R) => R.kgHaHi !== null && R.kgHaHi !== undefined ? yt(R.kgHaLo, R.kgHaHi) : R.piecesHa !== null && R.piecesHa !== undefined ? many(R.piecesHaLo) + ' to ' + many(R.piecesHa) + ' ' + (Y.word || Y.what) : 'None';
        $('scFSCmp').innerHTML = r.usual ? '<div>Final stand<b>' + many(r.standHa) + '</b><span>' + many(r.usual.standHa) + ' on a usual field</span></div>'
            + '<div>Harvest a hectare<b>' + yr(r) + '</b><span>' + yr(r.usual) + ' on a usual field</span></div>' : '';
    }


    /* ---- the seed, said back; the range from a usual field to yours (2026-10-19, the owner) ---- */
    $('scSeedType').addEventListener('click', () => {
        const kk = B.seedKinds[st.crop];
        if (!kk) return;
        const d = {};
        Object.keys(kk.types).forEach((t) => { d[t] = [B.seedTypes[t][0], B.seedTypes[t][1] + ': about ' + kk.types[t][0] + '% come up']; });
        pick('What seed is it?', d, kk.types[st.seedType] ? st.seedType : kk.default, (k) => { st.seedType = k; paint(); calc(); });
    });
    function paintSeed(c, r) {
        const C = crop(), U = units(C, c.method), nm = shortName(C), S = r.seed, m = c.method, nursery = r.nursery, li = [];
        const who = (k) => S.from[k] === 'variety' ? 'from the figures for ' + st.variety : (k === 'germ' && S.from.germ === 'type' ? 'usual for ' + B.seedTypes[S.type][2] + ' of ' + nm : 'usual for ' + nm);
        const row = (v, t, sub) => '<li><b>' + esc(v) + '</b><span>' + esc(t) + (sub ? '<small>' + esc(sub) + '</small>' : '') + '</span></li>';
        if (m === 'piece') li.push(row(num(S.germ) + '%', cap(U[1]) + ' that take root, ' + who('germ') + '.'));
        else if (m !== 'transplant' || nursery) li.push(row(num(S.germ) + '%', (nursery ? 'Germination in the seedbed, ' : 'Germination, ') + who('germ') + '.', S.type === 'saved' ? '' : 'Good seed shows ' + num(S.germMin) + '% or more.'));
        if (nursery) li.push(row(num(S.fit) + '%', 'Seedlings fit to plant, of those that come up, ' + who('fit') + '.'));
        li.push(row(num(S.loss) + '%', (m === 'transplant' ? 'Lost after transplanting, ' : 'Lost after they come up, to pests, birds and damping off, ') + who('loss') + '.'));
        li.push(row(num(r.reserve) + '%', 'Kept for replanting' + (r.replant ? ', sized to the hills that come up empty' : '') + '.'));
        $('scSeedLines').innerHTML = li.join('');
        $('scHLossLine').querySelector('span').textContent = 'Lost at harvest: ' + num(S.harvestLoss) + '%, usual for ' + nm + ' (left in the field, spilled or sorted out).';
    }
    const rangeTxt = (a, b, f) => { const lo = Math.min(a, b), hi = Math.max(a, b); return Math.abs(hi - lo) < 1e-9 * Math.max(1, hi) || f(lo) === f(hi) ? f(lo) : f(lo) + ' to ' + f(hi); };
    const yieldOf = (R) => R.kgHaHi !== null && R.kgHaHi !== undefined ? [R.kgHaLo, R.kgHaHi, true] : R.piecesHa !== null && R.piecesHa !== undefined ? [R.piecesHaLo, R.piecesHa, false] : null;
    const massRange = (lo, hi) => hi >= 1000 ? num(lo / 1000, 1) + ' to ' + num(hi / 1000, 1) + ' t' : hi < 1 ? num(lo * 1000) + ' to ' + num(hi * 1000) + ' g' : kgTxt(lo) + ' to ' + kgTxt(hi) + ' kg';
    let rangeLast = {};
    function paintRange(c, r) {
        const C = crop(), W = standWord(C), U = units(C, c.method), Y = C.yield, u = r.usual || r, P = r.pen;
        const seedWord = r.nursery || c.method === 'direct' ? 'seed' : U[1];
        const seeds = r.kgLo !== null && u.kgLo !== null ? massRange(Math.min(r.kgLo, u.kgLo), Math.max(r.kgHi, u.kgHi)) + ' of ' + seedWord : many(Math.min(r.buyLo, u.buyLo)) + ' to ' + many(Math.max(r.buyHi, u.buyHi)) + ' ' + (r.nursery ? 'seeds' : U[1]);
        const yf = yieldOf(r), yu = yieldOf(u);
        const harvest = yf && yu ? (yf[2] ? massRange(Math.min(yf[0], yu[0]), Math.max(yf[1], yu[1])) + ' ' + Y.what : many(Math.min(yf[0], yu[0])) + ' to ' + many(Math.max(yf[1], yu[1])) + ' ' + (Y.word || Y.what)) + (Y.perYear ? ' a year' : '') : 'None';
        const rows = [['Final stand', rangeTxt(r.standHa, u.standHa, many) + ' ' + W + ' a hectare'], [cap(r.nursery ? 'seed' : U[1]) + ' you need', seeds + ' a hectare'], ['Harvest', harvest + ' a hectare']];
        const bg = c.mode !== 'spacing' ? bagsOf(r) : null;
        if (bg) rows.splice(2, 0, [cap(packLabel(r.pack, 2)) + ' to buy', bg.haTxt + ' of ' + packSize(r.pack) + ' a hectare']);
        $('scRangeRows').innerHTML = rows.map(([k, v]) => '<div><span>' + esc(k) + '</span><b class="' + (rangeLast[k] !== undefined && rangeLast[k] !== v && !still() ? 'is-swap' : '') + '">' + esc(v) + '</b></div>').join('');
        rows.forEach(([k, v]) => { rangeLast[k] = v; });
        const one = (R, title, you, why) => '<div' + (you ? ' class="is-you"' : '') + '><h6>' + esc(title) + '</h6><p><b>' + esc(many(R.standHa)) + '</b> ' + esc(W) + '</p><p><b>' + esc(R.kgLo !== null && R.kgLo !== undefined ? massRange(R.kgLo, R.kgHi) : many(R.buyLo) + ' to ' + many(R.buyHi)) + '</b> of seed</p><p><b>'
            + esc((() => { const y = yieldOf(R); return y ? (y[2] ? massRange(y[0], y[1]) : many(y[0]) + ' to ' + many(y[1])) : 'None'; })()) + '</b> harvest</p><ul>' + why.map((x) => '<li>' + esc(x) + '</li>').join('') + '</ul></div>';
        const S = r.seed;
        const usualWhy = [num(S.germ) + '% germination, ' + num(S.loss) + '% lost', 'No soil, water or weather taken off'];
        const lines = [].concat(P.emerge.lines.map((l) => [l, 'emerge']), P.seedbed.lines.map((l) => [l, 'seedbed']), P.growth.lines.map((l) => [l, 'growth'])).sort((a, b) => a[0][1] - b[0][1]);
        const has = !!r.usual;
        const yourWhy = has ? lines.slice(0, 3).map(([l, g]) => penWords(l, g, c, r)[0] + ' ' + String.fromCharCode(8722) + pctOff(l[1])) : [st.lotId || st.month ? 'Nothing here takes from the stand or the harvest' : 'Same as a usual field until you pick a lot and a month'];
        $('scRangeTwo').innerHTML = one(u, 'On a usual field', false, usualWhy) + one(r, 'On your field this season', true, yourWhy);
        $('scRangeNote').textContent = has ? (lines.length > 3 ? 'And ' + (lines.length - 3) + ' more reasons under Your field and season.' : 'The reasons are under Your field and season.')
            : st.lotId && st.month ? 'Your lot and this season take nothing off: both answers are the same.'
            : 'Pick ' + (st.lotId ? 'a planting month' : st.month ? 'a lot' : 'a lot and a planting month') + ' to count its soil, water, weather and forecast, and see the range between the two.';
    }

    /* ---- what to buy: the bags, cans or packs, rounded up (2026-10-19, the owner) ---- */
    const packLabel = (p, n) => n === 1 ? p.label : (['pouch', 'box'].includes(p.label) ? p.label + 'es' : p.label + 's');
    const packSize = (p) => (p.kind === 'seeds' ? num(p.size) + ' ' + p.word : num(p.size, p.size % 1 ? 1 : 0) + ' ' + p.kind) + (p.what ? ' ' + p.what : '');
    const nice = (v) => { if (!(v > 0)) return 0; const d = Math.pow(10, Math.max(0, Math.floor(Math.log10(v)) - 1)); return Math.round(v / d) * d; };
    const upN = (x) => Math.max(1, Math.ceil(x - 1e-9));
    const f2 = (v) => num(v, v < 10 ? 2 : 1);
    // The packs a hectare and for the lot, from a usual field to yours.
    const bagsOf = (r) => {
        if (!r.pack || r.packHi === null || r.packHi === undefined) return null;
        const u = r.usual && r.usual.packHi !== null && r.usual.packHi !== undefined ? r.usual : r;
        const ha = [Math.min(r.packLo, u.packLo), Math.max(r.packHi, u.packHi)];
        const lot = r.areaHa && r.packLotHi !== null ? [Math.min(r.packLotLo, u.packLotLo ?? r.packLotLo), Math.max(r.packLotHi, u.packLotHi ?? r.packLotHi)] : null;
        const words = (a) => { const lo = upN(a[0]), hi = upN(a[1]); return (lo === hi ? num(hi) : num(lo) + ' to ' + num(hi)) + ' ' + packLabel(r.pack, hi); };
        return { ha, lot, haTxt: words(ha), lotTxt: lot ? words(lot) : null };
    };
    const hasParts = (vy) => !!vy && (['tillers', 'grains', 'filled', 'tgw', 'ceiling', 'ears', 'perEar', 'perPlant', 'fruits', 'fruitWt', 'stalks', 'millable', 'stalkKg'].some((k) => vy[k] !== undefined && vy[k] !== null)
        || (!!vy.extra && typeof vy.extra === 'object' && Object.values(B.stalkExtra).some((k) => vy.extra[k] !== undefined && vy.extra[k] !== null)));
    let buyLast = '';
    function paintBuy(c, r, lotWord) {
        const box = $('scBuy'), bg = bagsOf(r), on = !!bg && c.mode !== 'spacing';
        box.hidden = !on;
        if (!on) { buyLast = ''; return; }
        const C = crop(), p = r.pack, list = r.packs || [];
        $('scBuyTitle').textContent = 'What to buy' + (bg.lot ? ', for your ' + lotWord : ', a hectare');
        const big = bg.lot ? bg.lotTxt : bg.haTxt, el = $('scBuyBig');
        el.textContent = big;
        if (buyLast && buyLast !== big && !still()) { el.classList.remove('is-swap'); void el.offsetWidth; el.classList.add('is-swap'); }
        buyLast = big;
        const each = p.kind === 'seeds' ? (r.packEach ? ', about ' + num(r.packEach, 1) + ' kg each' : '')
            : p.about ? ', about ' + num(p.about) + ' seeds each' : r.packEach ? ', about ' + num(nice(r.packEach)) + ' seeds each' : '';
        $('scBuySub').textContent = 'of ' + packSize(p) + each;
        const area = (a) => a >= 0.1 ? num(a, 2) + ' ha' : num(a * 10000) + ' m²';
        const spread = r.wts && !r.wts.own && r.wts.lo < r.wts.hi;
        $('scBuySay').textContent = 'The seed comes to ' + rangeTxt(bg.ha[0], bg.ha[1], f2) + ' ' + packLabel(p, 2) + ' a hectare'
            + (bg.lot ? ' (' + rangeTxt(bg.lot[0], bg.lot[1], f2) + ' for your ' + lotWord + ')' : '') + ', rounded up to whole ' + packLabel(p, 2) + '. '
            + (p.kind === 'seeds' ? cap(packLabel(p, 2)) + ' sold by the seed count go by the seeds, so the size of the seed does not change them. '
                : spread ? 'The low end is small seed with nothing spare; the top is large seed with the replant reserve. ' : 'The top keeps the replant reserve. ')
            + 'One ' + p.label + ' plants about ' + rangeTxt(1 / bg.ha[1], 1 / bg.ha[0], area) + ' at this spacing.'
            + (bg.lot ? ' A hectare takes ' + bg.haTxt + '.' : '')
            + (p.from === 'variety' ? ' This is the ' + p.label + ' ' + st.variety + ' is sold in.' : '');
        const tagB = $('scPack');
        tagB.hidden = list.length < 2;
        if (list.length > 1) tag('scPack', cap(p.label) + ' of ' + packSize(p), (p.from === 'variety' ? 'How ' + st.variety + ' is sold' : 'Usual for ' + shortName(C)) + '. Tap to pick another size');
    }
    $('scPack').addEventListener('click', () => {
        const r = st.last && st.last.r;
        if (!r || !r.packs || r.packs.length < 2) return;
        const C = crop(), d = {};
        r.packs.forEach((p) => {
            d[p.id] = [cap(p.label) + ' of ' + packSize(p), (p.from === 'variety' ? 'How ' + st.variety + ' is sold' : 'Usual for ' + shortName(C))
                + (p.kind === 'seeds' ? '. Sold by the seed count' : p.about ? '. About ' + num(p.about) + ' seeds' : '. Sold by weight')];
        });
        pick('Which pack will you buy?', d, r.pack ? r.pack.id : null, (k) => { st.pack = k; calc(); });
    });

    /* ---- put inputs back (a saved one, or the last one on this phone) ---- */
    const fill = (c) => {
        if (!c || !B.crops[c.crop]) return false;
        st.crop = c.crop;
        const C = crop();
        st.method = C.methods.includes(c.method) ? c.method : C.methods[0];
        st.layout = B.layouts[c.layout] ? c.layout : C.layout;
        st.mode = B.modes[c.mode] ? c.mode : 'seeds';
        st.areaUnit = c.areaUnit === 'sqm' ? 'sqm' : 'ha';
        st.rowSet = c.rowSet === 'free' ? 'free' : 'set';
        st.amountUnit = ['count', 'kg', 'g'].includes(c.amountUnit) ? c.amountUnit : 'kg';
        st.amountPer = c.amountPer === 'lot' ? 'lot' : 'ha'; st.targetPer = c.targetPer === 'lot' ? 'lot' : 'ha';
        st.hillMode = c.hillMode === 'set' ? 'set' : 'auto';
        st.variety = typeof c.variety === 'string' ? c.variety : ''; st.vRec = null; st.vy = null; st.trial = null;
        st.seedType = B.seedKinds[c.crop] && B.seedKinds[c.crop].types[c.seedType] ? c.seedType : null;
        st.pack = typeof c.pack === 'string' && /^(kg|g|seeds)-\d+(\.\d+)?-[a-z]{1,12}$/.test(c.pack) ? c.pack : null;
        st.lotId = Number(c.lotId) > 0 ? Number(c.lotId) : null; st.lot = null; st.ctx = null;
        st.month = /^\d{4}-(0[1-9]|1[0-2])$/.test(String(c.month || '')) ? c.month : null;
        fillCropDefaults();
        const map = { area: 'scArea', row: 'scRow', plant: 'scPlant', bed: 'scBed', bedRows: 'scBedRows', amount: 'scAmount', target: 'scTarget', perHill: 'scPerHill', keep: 'scKeep',
            weight: 'scWeight', tillers: 'scTillers', grains: 'scGrains', filled: 'scFilled', ears: 'scEars',
            perEar: 'scPerEar', perPlant: 'scPerPlant', stalks: 'scStalks', millable: 'scMillable', stalkKg: 'scStalkKg' };
        Object.entries(map).forEach(([k, id]) => { if (Object.prototype.hasOwnProperty.call(c, k)) setVal(id, c[k]); });
        st.tone = null; shown.clear();
        paint(true); calc();
        if (st.variety) loadVariety().then(() => { paint(); calc(); });
        setVal('scDay', c.day ?? ''); paintWhen();
        if (LOTP) LOTP.set(st.lotId);
        if (st.lotId || st.month) ctxSoon(0);
        return true;
    };
    const start = () => { setCrop('corn_yellow'); calc(); };
    try { const last = JSON.parse(localStorage.getItem(LS) || 'null'); if (!(last && typeof last === 'object' && fill(last))) start(); } catch (e) { start(); }
    $('scReset').addEventListener('click', () => { const k = st.crop; st.mode = 'seeds'; st.month = null; setVal('scDay', ''); paintWhen(); st.rowSet = 'set'; st.amountPer = st.targetPer = 'ha'; setVal('scArea', ''); setVal('scAmount', ''); st.tone = null; shown.clear(); setCrop(k); calc(); window.scrollTo({ top: 0, behavior: still() ? 'auto' : 'smooth' }); });

    /* ---- the tabs and the saved list ---- */
    const LIST = { rows: [], page: 1, more: false };
    const tab = (saved) => {
        $('scTabs').classList.toggle('is-saved', saved);
        $('scTabCalc').setAttribute('aria-selected', String(!saved)); $('scTabSaved').setAttribute('aria-selected', String(saved));
        $('scTabCalc').tabIndex = saved ? -1 : 0; $('scTabSaved').tabIndex = saved ? 0 : -1;
        $('scPaneCalc').hidden = saved; $('scPaneSaved').hidden = !saved;
        if (saved && !LIST.loaded) load(1);
        dock();
    };
    $('scTabCalc').addEventListener('click', () => tab(false));
    $('scTabSaved').addEventListener('click', () => tab(true));
    const STAT = { sparse: ['Very thin', 'bad'], thin: ['A bit thin', 'warn'], good: ['Good stand', 'good'], crowded: ['Crowded', 'warn'], danger: ['Danger zone', 'bad'] };
    const shelf = (d, append) => {
        LIST.rows = append ? LIST.rows.concat(d.history || []) : (d.history || []);
        LIST.more = !!d.hasMore; LIST.page = d.page || 1; LIST.loaded = true;
        $('scTabN').textContent = LIST.rows.length ? (LIST.more ? LIST.rows.length + '+' : LIST.rows.length) : '';
        $('scSEmpty').hidden = LIST.rows.length > 0; $('scMore').hidden = !LIST.more;
        $('scList').innerHTML = LIST.rows.map((r, i) => {
            const s = STAT[r.status] || STAT.good;
            return '<div class="sc-srow" style="animation-delay:' + Math.min(i, 8) * 40 + 'ms" data-id="' + r.id + '">'
                + '<span class="sc-date"><b>' + esc(r.day) + '</b><small>' + esc(r.month) + '</small></span>'
                + '<button type="button" class="sc-sopen" data-open="' + r.id + '"><b>' + esc(r.title) + '</b><small><span class="sc-chip is-' + s[1] + '">' + s[0] + '</span>' + esc((B.modes[r.mode] || B.modes.seeds)[0]) + ' · ' + esc(r.at) + '</small></button>'
                + '<button type="button" class="sc-sdel" data-del="' + r.id + '" aria-label="Delete"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg></button></div>';
        }).join('');
    };
    const load = async (page) => {
        try { const res = await window.api(B.urls.list + '?page=' + page, { method: 'GET' }); shelf(res.data, page > 1); }
        catch (err) { toast(err.message || 'The list could not load.', 'error'); }
    };
    $('scMore').addEventListener('click', () => load(LIST.page + 1));
    $('scList').addEventListener('click', async (e) => {
        const o = e.target.closest('[data-open]');
        if (o) {
            try { const res = await window.api(B.urls.one + '/' + o.dataset.open, { method: 'GET' }); fill(res.data.params); tab(false); window.scrollTo({ top: 0, behavior: still() ? 'auto' : 'smooth' }); }
            catch (err) { toast(err.message, 'error'); }
            return;
        }
        const d = e.target.closest('[data-del]');
        if (!d) return;
        const id = d.dataset.del, row = d.closest('.sc-srow');
        const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this calculation?', message: 'It leaves your saved list for good.', confirmText: 'Delete' }) : confirm('Delete this calculation?');
        if (!ok) return;
        row && row.classList.add('is-gone');
        try { const res = await window.api(B.urls.del + '/' + id, { method: 'DELETE' }); setTimeout(() => shelf(res.data, false), still() ? 0 : 260); }
        catch (err) { row && row.classList.remove('is-gone'); toast(err.message, 'error'); }
    });
    $('scSave').addEventListener('click', async () => {
        if (!st.last) return;
        const btn = $('scSave');
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
