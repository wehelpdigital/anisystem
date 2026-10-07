@extends('layouts.app')
@section('title', 'Satellite Analysis')
@section('page-title', 'Satellite Analysis')
@section('page-subtitle', 'Your field, read from space')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

@section('content')
@include('partials.tag-sheet-css')
@include('partials.weather-scenes')
<style>
    /* ---- SATELLITE ANALYSIS (2026-10-07) ---------------------------------
       A wizard that walks, a map you draw on, and a report in tabs. Every
       change rides the house curve and holds still under reduced motion. */
    :root { --sat-ease: cubic-bezier(.22,1,.36,1); }
    .sat-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .sat-tab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem; text-align: center;
        color: var(--color-gray-500); background: var(--color-white); border: 1px solid var(--color-gray-200); cursor: pointer;
        transition: background-color .28s var(--sat-ease), color .28s var(--sat-ease), border-color .28s var(--sat-ease); }
    .sat-tab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }

    .sat-hero { position: relative; overflow: hidden; border-radius: 1.2rem; padding: 1.1rem 1.15rem; margin-bottom: 1rem; color: #e8f1de;
        background: radial-gradient(120% 140% at 100% 0%, #3f6a22 0%, #1d3310 55%, #0f1d08 100%); }
    .sat-hero::after { content: ''; position: absolute; inset: 0; pointer-events: none;
        background-image: radial-gradient(circle at 20% 30%, rgb(255 255 255 / .55) 0 1px, transparent 1.5px), radial-gradient(circle at 70% 60%, rgb(255 255 255 / .4) 0 1px, transparent 1.5px), radial-gradient(circle at 40% 80%, rgb(255 255 255 / .35) 0 1px, transparent 1.5px);
        background-size: 140px 120px, 180px 160px, 110px 130px; opacity: .55; }
    .sat-hero h2 { position: relative; z-index: 1; font-family: var(--font-heading); font-weight: 800; font-size: 1.12rem; color: #fff; }
    .sat-hero p { position: relative; z-index: 1; margin-top: .3rem; font-size: .84rem; line-height: 1.55; color: #c9dcb5; max-width: 34rem; }
    .sat-orbit { position: absolute; right: -1.2rem; top: -1.6rem; width: 8.5rem; height: 8.5rem; border-radius: 999px; border: 1px dashed rgb(201 220 181 / .35); z-index: 0; }
    .sat-orbit i { position: absolute; top: -5px; left: 50%; width: 10px; height: 10px; border-radius: 3px; background: #f5c518; box-shadow: 0 0 12px #f5c518;
        transform-origin: 0 calc(4.25rem + 5px); animation: satOrbit 9s linear infinite; }
    @keyframes satOrbit { to { transform: rotate(360deg); } }
    @media (max-width: 639.98px) { .sat-orbit { width: 6rem; height: 6rem; right: -1.6rem; top: -2rem; opacity: .6; } .sat-orbit i { transform-origin: 0 calc(3rem + 5px); } }
    .sat-chips { position: relative; z-index: 1; display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .7rem; }
    .sat-chips span { font-size: .7rem; font-weight: 800; letter-spacing: .03em; padding: .25rem .55rem; border-radius: 999px; background: rgb(255 255 255 / .12); color: #e8f1de; }

    .sat-quote { display: flex; gap: .7rem; align-items: center; border-radius: .9rem; padding: .7rem .85rem; margin-bottom: 1rem; font-size: .84rem; color: #3d5226;
        background: linear-gradient(115deg, #f3f8ec, #e4efd4); border: 1px solid #cfe3b8; }
    .sat-quote b { color: #2d5016; }
    .sat-quote.is-warn { background: #fff8e6; border-color: #f5d98a; color: #6b4a00; }
    html.dark .sat-quote { background: #17220f; border-color: #2b3a1c; color: #c7d8b4; }
    html.dark .sat-quote b { color: #e8efe1; }

    /* The wizard */
    .sat-wiz { position: relative; }
    .sat-rail { display: flex; gap: .3rem; margin-bottom: 1rem; }
    .sat-rail i { flex: 1 1 0; height: .3rem; border-radius: 999px; background: var(--color-gray-200); overflow: hidden; position: relative; }
    .sat-rail i::after { content: ''; position: absolute; inset: 0; background: var(--color-brand-600); transform: scaleX(0); transform-origin: left; transition: transform .36s var(--sat-ease); }
    .sat-rail i.is-done::after, .sat-rail i.is-on::after { transform: scaleX(1); }
    .sat-step { display: none; }
    .sat-step.is-on { display: block; animation: satIn .34s var(--sat-ease) both; }
    .sat-step.is-back { animation-name: satInBack; }
    @keyframes satIn { from { opacity: 0; transform: translateX(14px); } }
    @keyframes satInBack { from { opacity: 0; transform: translateX(-14px); } }
    .sat-step h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.08rem; color: var(--color-gray-900); }
    .sat-sub { margin: .25rem 0 .8rem; font-size: .84rem; line-height: 1.55; color: var(--color-gray-500); }
    .sat-label { display: block; margin: .9rem 0 .35rem; font-size: .78rem; font-weight: 800; color: var(--color-gray-700); }
    .sat-row { display: flex; gap: .5rem; }
    .sat-row > * { min-width: 0; }
    .sat-pills { display: flex; flex-wrap: wrap; gap: .4rem; }
    .sat-pill { display: inline-flex; align-items: center; gap: .35rem; padding: .48rem .8rem; border-radius: 999px; font-size: .82rem; font-weight: 700;
        color: var(--color-gray-700); background: var(--color-white); border: 1px solid var(--color-gray-200); cursor: pointer; text-align: left;
        transition: background-color .28s var(--sat-ease), border-color .28s var(--sat-ease), color .28s var(--sat-ease), transform .28s var(--sat-ease); }
    .sat-pill:hover { border-color: var(--color-brand-300, #a8cc7e); }
    .sat-pill[aria-pressed="true"] { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .sat-pill small { font-weight: 600; opacity: .75; }
    .sat-crops { max-height: 17rem; overflow-y: auto; padding: .1rem; margin-top: .6rem; }
    .sat-places { display: grid; gap: .35rem; margin-top: .6rem; }
    .sat-place { display: flex; gap: .55rem; align-items: center; width: 100%; padding: .6rem .7rem; border-radius: .8rem; text-align: left; font-size: .84rem;
        color: var(--color-gray-800); background: var(--color-white); border: 1px solid var(--color-gray-200); cursor: pointer;
        transition: border-color .28s var(--sat-ease), background-color .28s var(--sat-ease); }
    .sat-place:hover, .sat-place.is-on { border-color: var(--color-brand-600); background: var(--color-brand-50, #f3f8ec); }
    .sat-place svg { flex: none; width: 1.1rem; height: 1.1rem; color: var(--color-brand-600); }
    .sat-picked { margin-top: .7rem; display: flex; gap: .5rem; align-items: center; font-size: .82rem; font-weight: 700; color: var(--color-brand-700, #3d6823); }
    .sat-picked[hidden] { display: none; }
    .sat-nav { display: flex; gap: .5rem; margin-top: 1.2rem; }
    /* A choice that opens a sheet. */
    .sat-choice { display: flex; align-items: center; justify-content: space-between; gap: .5rem; width: 100%; padding: .62rem .8rem; border-radius: .8rem; cursor: pointer; text-align: left;
        font-size: .9rem; font-weight: 700; color: #3d6823; background: var(--color-white); border: 1px solid var(--color-gray-200);
        transition: border-color .28s var(--sat-ease), background-color .28s var(--sat-ease), transform .28s var(--sat-ease); }
    .sat-choice:hover:not(:disabled) { border-color: var(--color-brand-300, #a8cc7e); background: var(--color-brand-50, #f3f8ec); }
    .sat-choice:disabled { opacity: .55; cursor: default; }
    .sat-choice span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sat-choice.is-none span { color: var(--color-gray-500); font-weight: 600; }
    .sat-choice-c { width: 1rem; height: 1rem; flex: none; color: var(--color-gray-400); }
    .sat-choice.is-unit { width: auto; flex: none; max-width: 52%; }
    html.dark .sat-choice { background: #1c2416; border-color: #2b3a1c; color: #a5c97e; }
    .sat-link { border: 0; background: none; padding: .48rem .2rem; font-size: .8rem; font-weight: 800; color: var(--color-brand-700, #3d6823); text-decoration: underline; cursor: pointer; }
    .sat-date { width: 100%; justify-content: flex-start; }
    .sat-psearch { position: relative; margin-bottom: .6rem; }
    .sat-psearch svg { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); width: 1.05rem; height: 1.05rem; color: var(--color-gray-400); pointer-events: none; }
    .sat-psearch .form-input { padding-left: 2.4rem; }
    .sat-pick-h { font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); margin: .8rem 0 .3rem; }
    #satPickFoot[hidden], #satPickSearchBox[hidden] { display: none; }
    /* The pH: a range on the soil's colours, acidic to alkaline. */
    .sat-switch { display: flex; align-items: center; gap: .55rem; font-size: .86rem; font-weight: 700; color: var(--color-gray-700); cursor: pointer; }
    .sat-switch input { width: 1.1rem; height: 1.1rem; accent-color: var(--color-brand-600); }
    .sat-ph { margin-top: .7rem; padding: .85rem .9rem .7rem; border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-gray-50); animation: satIn .3s var(--sat-ease) both; }
    .sat-ph[hidden] { display: none; }
    html.dark .sat-ph { background: #121a0d; border-color: #2b3a1c; }
    .sat-ph-read { display: flex; flex-wrap: wrap; align-items: baseline; gap: .2rem .6rem; }
    .sat-ph-read b { font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: var(--color-gray-900); }
    .sat-ph-read span { font-size: .8rem; font-weight: 700; color: var(--color-gray-500); }
    .sat-ph-range { position: relative; height: 2.6rem; margin-top: .5rem; }
    .sat-ph-track { position: absolute; left: 0; right: 0; top: 50%; height: .75rem; margin-top: -.375rem; border-radius: 999px;
        background: linear-gradient(90deg, #c0262d 0%, #e8590c 15.4%, #f2b705 30.8%, #94c11f 41.5%, #2f9e44 46.2%, #2f9e44 58.5%, #1c7ed6 69.2%, #5f3dc4 84.6%, #7b2cbf 100%); }
    .sat-ph-sel { position: absolute; top: 50%; height: 1.25rem; margin-top: -.625rem; border-radius: 999px; border: 2.5px solid #fff; box-shadow: 0 0 0 1px rgb(0 0 0 / .3), 0 6px 14px -6px rgb(0 0 0 / .5);
        transition: left .12s linear, width .12s linear; }
    .sat-ph-range input[type="range"] { position: absolute; left: 0; top: 0; width: 100%; height: 100%; margin: 0; background: none; pointer-events: none; -webkit-appearance: none; appearance: none; }
    .sat-ph-range input[type="range"]::-webkit-slider-runnable-track { background: transparent; height: 100%; }
    .sat-ph-range input[type="range"]::-moz-range-track { background: transparent; }
    .sat-ph-range input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; pointer-events: auto; width: 1.6rem; height: 1.6rem; margin-top: .5rem; border-radius: 999px; cursor: grab;
        background: #fff; border: 4px solid var(--c, #2f9e44); box-shadow: 0 3px 8px rgb(0 0 0 / .35); }
    .sat-ph-range input[type="range"]::-moz-range-thumb { pointer-events: auto; width: 1.1rem; height: 1.1rem; border-radius: 999px; cursor: grab; background: #fff; border: 4px solid var(--c, #2f9e44); box-shadow: 0 3px 8px rgb(0 0 0 / .35); }
    .sat-ph-range input[type="range"]:focus-visible::-webkit-slider-thumb { box-shadow: 0 0 0 4px rgb(74 124 42 / .35); }
    .sat-ph-scale { position: relative; height: 1.1rem; margin-top: .15rem; font-size: .68rem; font-weight: 800; }
    .sat-ph-scale span { position: absolute; transform: translateX(-50%); white-space: nowrap; }
    .sat-ph-scale .is-acid { color: #c2410c; } .sat-ph-scale .is-neutral { color: #2f9e44; } .sat-ph-scale .is-alk { color: #5f3dc4; }
    html.dark .sat-ph-scale .is-acid { color: #fb923c; } html.dark .sat-ph-scale .is-neutral { color: #69db7c; } html.dark .sat-ph-scale .is-alk { color: #b197fc; }
    .sat-ph-nums { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; margin-top: .6rem; }
    .sat-ph-nums label { display: grid; gap: .25rem; font-size: .72rem; font-weight: 800; color: var(--color-gray-600); }
    .sat-ph-err { margin-top: .4rem; font-size: .76rem; font-weight: 700; color: #b45309; }
    .sat-ph-err[hidden] { display: none; }

    /* Drawing the field */
    .sat-draw { position: relative; border-radius: 1rem; overflow: hidden; border: 1px solid var(--color-gray-200); background: #1d2a14; }
    .sat-draw-map { height: min(62vh, 30rem); }
    .sat-draw-bar { position: absolute; left: .6rem; right: .6rem; top: .6rem; display: flex; flex-wrap: wrap; gap: .35rem; z-index: 2; pointer-events: none; }
    .sat-draw-bar button { pointer-events: auto; display: inline-flex; align-items: center; gap: .35rem; padding: .42rem .7rem; border-radius: 999px; font-size: .78rem; font-weight: 800;
        color: #1f2a17; background: rgb(255 255 255 / .94); box-shadow: 0 6px 16px -8px rgb(0 0 0 / .6); cursor: pointer; transition: transform .28s var(--sat-ease); }
    .sat-draw-bar button:disabled { opacity: .45; cursor: default; }
    .sat-draw-bar button svg { width: .95rem; height: .95rem; }
    .sat-draw-area { position: absolute; left: .6rem; bottom: .6rem; z-index: 2; padding: .45rem .75rem; border-radius: .8rem; font-size: .8rem; font-weight: 800;
        color: #fff; background: rgb(15 29 8 / .82); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
    .sat-draw-hint { margin-top: .55rem; font-size: .8rem; color: var(--color-gray-500); line-height: 1.5; }

    .sat-review { display: grid; gap: .35rem; margin-top: .4rem; font-size: .84rem; color: var(--color-gray-700); }
    .sat-review div { display: flex; gap: .6rem; padding: .45rem 0; border-bottom: 1px dashed var(--color-gray-200); }
    .sat-review b { flex: 0 0 7.5rem; color: var(--color-gray-500); font-weight: 700; }
    .sat-run { margin-top: 1rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: .55rem; padding: .95rem 1rem; border-radius: 1rem;
        font-weight: 800; font-size: 1rem; color: #1a1a1a; background: linear-gradient(135deg, #f7d23a, #f5c518); box-shadow: 0 14px 30px -18px rgb(201 158 0 / .9);
        transition: transform .28s var(--sat-ease), box-shadow .28s var(--sat-ease); }
    .sat-run:hover { transform: translateY(-1px); }
    .sat-run:disabled { opacity: .55; transform: none; cursor: default; }
    .sat-run svg { width: 1.2rem; height: 1.2rem; }

    /* The report */
    .sat-view { position: fixed; inset: 0; z-index: 90; background: var(--color-gray-50); overflow-y: auto; -webkit-overflow-scrolling: touch;
        opacity: 0; transform: translateY(12px); transition: opacity .28s var(--sat-ease), transform .28s var(--sat-ease); }
    .sat-view[hidden] { display: none; }
    .sat-view.is-on { opacity: 1; transform: none; }
    html.sat-lock { overflow: hidden; }
    .sat-view-bar { position: sticky; top: 0; z-index: 5; display: flex; align-items: center; gap: .6rem; padding: .7rem .9rem; padding-top: max(.7rem, env(safe-area-inset-top));
        background: rgb(250 250 248 / .92); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border-bottom: 1px solid var(--color-gray-200); }
    .sat-view-bar b { flex: 1 1 auto; min-width: 0; font-size: .95rem; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sat-x { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-white); border: 1px solid var(--color-gray-200); color: var(--color-gray-700); cursor: pointer; }
    .sat-body { max-width: 52rem; margin: 0 auto; padding: 1rem 1rem calc(2.5rem + env(safe-area-inset-bottom)); }
    .sat-body > * { min-width: 0; }
    html.dark .sat-view { background: #0d110a; }
    html.dark .sat-view-bar { background: rgb(13 17 10 / .92); border-color: #2b3a1c; }

    .sat-top { display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr); align-items: center; border-radius: 1.2rem; padding: 1.1rem; color: #e8f1de;
        background: radial-gradient(120% 140% at 100% 0%, #3f6a22 0%, #1d3310 55%, #0f1d08 100%); }
    @media (min-width: 640px) { .sat-top { grid-template-columns: auto minmax(0, 1fr); } }
    .sat-ring { --v: 0; --c: #86b556; position: relative; width: 7.2rem; height: 7.2rem; border-radius: 999px; margin: 0 auto;
        background: conic-gradient(var(--c) calc(var(--v) * 1%), rgb(255 255 255 / .12) 0); display: grid; place-items: center; transition: --v .9s var(--sat-ease); }
    .sat-ring::before { content: ''; position: absolute; inset: .55rem; border-radius: 999px; background: #16270c; }
    .sat-ring div { position: relative; text-align: center; }
    .sat-ring b { display: block; font-family: var(--font-heading); font-size: 1.9rem; font-weight: 800; color: #fff; line-height: 1; }
    .sat-ring small { font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #c9dcb5; }
    .sat-top h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.2rem; line-height: 1.25; color: #fff; }
    .sat-top p { margin-top: .4rem; font-size: .86rem; line-height: 1.6; color: #d4e4c3; }
    .sat-when { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .7rem; }
    .sat-when span { display: inline-flex; align-items: center; gap: .3rem; min-width: 0; max-width: 100%; font-size: .7rem; font-weight: 800; padding: .28rem .55rem; border-radius: 999px; background: rgb(255 255 255 / .12); color: #f1f7ea; }
    .sat-when span svg { flex: none; }
    .sat-when span.is-warn { background: rgb(245 197 24 / .25); color: #ffe58a; }

    .sat-rtabs { position: sticky; top: 3.4rem; z-index: 4; display: flex; gap: .3rem; margin: 1rem -1rem 0; padding: .55rem 1rem; overflow-x: auto; scrollbar-width: none;
        background: rgb(250 250 248 / .92); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
    .sat-rtabs::-webkit-scrollbar { display: none; }
    html.dark .sat-rtabs { background: rgb(13 17 10 / .92); }
    .sat-rtab { flex: none; padding: .45rem .85rem; border-radius: 999px; font-size: .82rem; font-weight: 800; color: var(--color-gray-600);
        background: var(--color-white); border: 1px solid var(--color-gray-200); cursor: pointer; transition: background-color .28s var(--sat-ease), color .28s var(--sat-ease), border-color .28s var(--sat-ease); }
    .sat-rtab.is-on { background: var(--color-brand-600); color: #fff; border-color: var(--color-brand-600); }
    .sat-pane { display: none; padding-top: .9rem; }
    .sat-pane.is-on { display: grid; gap: .9rem; animation: satFade .34s var(--sat-ease) both; }
    .sat-pane > * { min-width: 0; }
    @keyframes satFade { from { opacity: 0; transform: translateY(8px); } }
    .sat-card { border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); padding: 1rem 1.05rem; }
    .sat-card h4 { display: flex; align-items: center; gap: .45rem; font-family: var(--font-heading); font-weight: 800; font-size: .98rem; color: var(--color-gray-900); }
    .sat-card h4 svg { width: 1.1rem; height: 1.1rem; color: var(--color-brand-600); }
    .sat-card p, .sat-card li { font-size: .86rem; line-height: 1.6; color: var(--color-gray-700); }
    .sat-card p + p { margin-top: .5rem; }
    .sat-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; }
    @media (min-width: 640px) { .sat-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .sat-stat { border-radius: .95rem; padding: .75rem .8rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .sat-stat small { display: block; font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); }
    .sat-stat b { display: block; margin-top: .2rem; font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; color: var(--color-gray-900); }
    .sat-stat i { display: block; font-style: normal; font-size: .74rem; color: var(--color-gray-500); margin-top: .1rem; }

    .sat-map-wrap { position: relative; border-radius: 1.1rem; overflow: hidden; border: 1px solid var(--color-gray-200); background: #1d2a14; }
    .sat-map { height: min(64vh, 32rem); }
    .sat-layers { position: absolute; left: .6rem; top: .6rem; right: .6rem; display: flex; flex-wrap: wrap; gap: .35rem; z-index: 2; pointer-events: none; }
    .sat-layers button { pointer-events: auto; padding: .4rem .7rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: #1f2a17; background: rgb(255 255 255 / .9);
        box-shadow: 0 6px 16px -8px rgb(0 0 0 / .6); cursor: pointer; transition: background-color .28s var(--sat-ease), color .28s var(--sat-ease); }
    .sat-layers button.is-on { background: #2d5016; color: #fff; }
    .sat-layers button:disabled { opacity: .45; cursor: default; }
    .sat-opacity { pointer-events: auto; display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .65rem; border-radius: 999px;
        font-size: .72rem; font-weight: 800; color: #fff; background: rgb(15 29 8 / .82); box-shadow: 0 6px 16px -8px rgb(0 0 0 / .6); }
    .sat-opacity input { width: 6.5rem; accent-color: #f5c518; }
    .sat-legend { position: absolute; left: .6rem; bottom: .6rem; z-index: 2; padding: .45rem .6rem; border-radius: .8rem; background: rgb(15 29 8 / .82); color: #fff; font-size: .68rem; font-weight: 800; }
    .sat-legend i { display: block; width: 9rem; height: .5rem; border-radius: 999px; margin: .25rem 0 .15rem;
        background: linear-gradient(90deg, #a50026, #d73027, #f46d43, #fdae61, #fee08b, #d9ef8b, #a6d96a, #66bd63, #1a9850, #006837); }
    .sat-legend span { display: flex; justify-content: space-between; opacity: .85; }
    .sat-legend[hidden] { display: none; }
    /* Which picture is on the map, and when the satellite took it. */
    .sat-shot { position: absolute; left: .6rem; top: 3.1rem; z-index: 2; max-width: calc(100% - 1.2rem); padding: .4rem .65rem; border-radius: .8rem; background: rgb(15 29 8 / .85); color: #fff;
        font-size: .74rem; line-height: 1.35; box-shadow: 0 8px 18px -10px rgb(0 0 0 / .8); pointer-events: none; }
    .sat-shot b { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #f5c518; }
    .sat-shot[hidden] { display: none; }
    .sat-shot i { display: block; margin-top: .15rem; font-style: normal; font-size: .64rem; font-weight: 700; color: #cfe3bd; }
    .sat-mapnote { display: flex; gap: .55rem; align-items: flex-start; padding: .65rem .8rem; border-radius: .9rem; font-size: .78rem; line-height: 1.5; color: #6b4a00; background: #fff8e1; border: 1px solid #f5d77a; }
    .sat-mapnote svg { flex: none; width: 1.05rem; height: 1.05rem; margin-top: .1rem; color: #b45309; }
    html.dark .sat-mapnote { color: #f3dc9a; background: #2a2208; border-color: #5c4a10; }

    .sat-grid9 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .3rem; max-width: 18rem; }
    .sat-grid9 div { aspect-ratio: 1.3; border-radius: .6rem; display: grid; place-items: center; text-align: center; font-size: .7rem; font-weight: 800; color: #fff; text-shadow: 0 1px 2px rgb(0 0 0 / .45); }
    .sat-grid9 div small { display: block; font-size: .6rem; font-weight: 700; opacity: .9; }
    .sat-bars { display: grid; gap: .45rem; }
    .sat-bar { display: grid; grid-template-columns: 4.2rem minmax(0, 1fr) 3rem; gap: .5rem; align-items: center; font-size: .78rem; font-weight: 700; color: var(--color-gray-600); }
    .sat-bar span { height: .55rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .sat-bar span i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #fdae61, #66bd63); transform-origin: left; animation: satGrow .9s var(--sat-ease) both; }
    @keyframes satGrow { from { transform: scaleX(0); } }
    .sat-chart { width: 100%; height: 9rem; display: block; }
    .sat-chart text { font-size: 10px; fill: var(--color-gray-500); }
    .sat-list { display: grid; gap: .55rem; }
    .sat-item { border-radius: .9rem; padding: .7rem .8rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    html.dark .sat-item { background: #121a0d; border-color: #2b3a1c; }
    .sat-item b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .sat-item p { margin-top: .2rem; font-size: .82rem; }
    .sat-tags { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .35rem; }
    .sat-tag { font-size: .66rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; padding: .16rem .45rem; border-radius: 999px; background: var(--color-gray-100); color: var(--color-gray-600); }
    .sat-tag.lv-high { background: #fde2e1; color: #b42318; }
    .sat-tag.lv-medium { background: #fff1c2; color: #8a5a00; }
    .sat-tag.lv-low { background: #e4efd4; color: #2d5016; }
    /* Five across, two rows, on a wide screen; on a phone two across, each a
       short card with the sky beside the words, so ten days never scroll sideways. */
    .sat-wx { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .45rem; }
    .sat-wxd { display: flex; flex-direction: column; align-items: center; gap: .15rem; min-width: 0; padding: .55rem .35rem .6rem; border-radius: .9rem; text-align: center;
        border: 1px solid var(--color-gray-100); font-size: .7rem; color: var(--color-gray-600); animation: satFade .4s var(--sat-ease) both; animation-delay: calc(var(--k, 0) * 45ms); }
    html.dark .sat-wxd { border-color: #2b3a1c; }
    .sat-wxd > b { font-size: .76rem; color: var(--color-gray-900); }
    .sat-wxd-md { display: none; }
    @media (max-width: 639.98px) { .sat-wxd-md { display: inline; } }
    .sat-wxd > small { font-size: .64rem; color: var(--color-gray-500); }
    .sat-wxd .wx-sky { margin: .1rem 0; }
    .sat-wxd > em { font-style: normal; font-size: .7rem; font-weight: 800; color: var(--color-gray-800); line-height: 1.2; min-height: 1.7rem; display: grid; place-items: center; }
    .sat-wxd > span { font-size: .72rem; font-weight: 700; color: var(--color-gray-700); }
    .sat-wxd > i { font-style: normal; font-size: .66rem; font-weight: 800; color: #2563eb; }
    html.dark .sat-wxd > i { color: #7cc0ff; }
    @media (max-width: 639.98px) {
        .sat-wx { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sat-wxd { display: grid; grid-template-columns: 2.6rem minmax(0, 1fr); grid-template-areas: 'sky day' 'sky what' 'sky temp' 'sky rain'; column-gap: .45rem; row-gap: 0;
            align-items: center; text-align: left; padding: .5rem .55rem; }
        .sat-wxd .wx-sky { grid-area: sky; width: 2.6rem !important; height: 2.6rem !important; margin: 0; }
        .sat-wxd > b { grid-area: day; }
        .sat-wxd > b small { font-weight: 600; color: var(--color-gray-500); }
        .sat-wxd > small { display: none; }
        .sat-wxd > em { grid-area: what; min-height: 0; display: block; font-size: .72rem; }
        .sat-wxd > span { grid-area: temp; font-size: .7rem; }
        .sat-wxd > i { grid-area: rain; }
    }
    @media (prefers-reduced-motion: reduce) { .sat-wxd { animation: none; } }
    .sat-table { width: 100%; font-size: .8rem; border-collapse: collapse; }
    .sat-table th, .sat-table td { text-align: left; padding: .45rem .4rem; border-bottom: 1px solid var(--color-gray-100); color: var(--color-gray-700); vertical-align: top; }
    .sat-table th { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: var(--color-gray-500); }
    .sat-actions li { display: flex; gap: .6rem; padding: .6rem 0; border-bottom: 1px dashed var(--color-gray-200); }
    .sat-actions li:last-child { border-bottom: 0; }
    .sat-actions em { flex: none; font-style: normal; font-size: .66rem; font-weight: 800; text-transform: uppercase; padding: .2rem .5rem; border-radius: 999px; height: fit-content;
        background: #fff1c2; color: #8a5a00; }
    .sat-actions em.p-now { background: #fde2e1; color: #b42318; }
    .sat-actions em.p-month { background: #e4efd4; color: #2d5016; }
    .sat-note { font-size: .78rem; line-height: 1.55; color: var(--color-gray-500); }

    /* minmax(0, 1fr): a long title is cut with an ellipsis, it no longer widens the page on a phone. */
    .sat-saved { display: grid; grid-template-columns: minmax(0, 1fr); gap: .55rem; }
    .sat-srow { display: flex; gap: .75rem; align-items: center; width: 100%; text-align: left; padding: .75rem .8rem; border-radius: 1rem; background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; transition: transform .28s var(--sat-ease), box-shadow .28s var(--sat-ease); }
    .sat-srow:hover { transform: translateY(-1px); box-shadow: 0 10px 24px -16px rgb(0 0 0 / .5); }
    .sat-srow .mini { --v: 0; flex: none; width: 3rem; height: 3rem; border-radius: 999px; display: grid; place-items: center; font-size: .82rem; font-weight: 800; color: var(--color-gray-900);
        background: radial-gradient(closest-side, var(--color-white) 72%, transparent 74% 100%), conic-gradient(#66bd63 calc(var(--v) * 1%), var(--color-gray-100) 0); }
    .sat-srow span { min-width: 0; flex: 1 1 auto; }
    .sat-srow b { display: block; font-size: .88rem; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sat-srow { min-width: 0; }
    .sat-srow small { display: block; font-size: .74rem; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    /* ---- the phone, redrawn (2026-10-07) ----------------------------------
       Short hero, the step's number, Back and Next held above the tab bar,
       a drawing map that fills the screen; in the report a compact head,
       tabs that stick under the bar and a map that takes what is left.
       Google's logo and terms stay clear at the bottom of every map. */
    .sat-hero .sat-more { display: inline; }
    @media (max-width: 639.98px) {
        .sat-hero { padding: .95rem 1rem; margin-bottom: .8rem; }
        .sat-hero h2 { font-size: 1.02rem; padding-right: 2.5rem; }
        .sat-hero .sat-more, .sat-hero .sat-chips { display: none; }
        .sat-tabs { margin-bottom: .8rem; }
        .sat-tab { padding: .55rem; font-size: .86rem; }
        .sat-quote { font-size: .8rem; margin-bottom: .8rem; }
        .sat-wiz.card { padding: 1rem; }
    }
    .sat-wiz { scroll-margin-top: calc(var(--app-head, 4rem) + .75rem); }
    .sat-stepno { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .45rem; font-size: .7rem; font-weight: 800;
        letter-spacing: .08em; text-transform: uppercase; color: var(--color-brand-600); }
    .sat-stepno span:last-child { color: var(--color-gray-400); letter-spacing: .02em; text-transform: none; font-weight: 700; }
    @media (max-width: 1023.98px) {
        .sat-nav { position: sticky; bottom: calc(3.5rem + env(safe-area-inset-bottom, 0px) + .6rem); z-index: 6; margin: 1rem -.45rem -.45rem; padding: .45rem;
            border-radius: 1.1rem; background: rgb(255 255 255 / .86); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 14px 30px -18px rgb(0 0 0 / .55), inset 0 0 0 1px var(--color-gray-200); }
        html.dark .sat-nav { background: rgb(21 27 18 / .88); }
        .sat-nav .btn { min-height: 2.9rem; }
    }
    .sat-draw-area { left: 50%; bottom: 1.9rem; transform: translateX(-50%); white-space: nowrap; box-shadow: 0 10px 24px -12px rgb(0 0 0 / .8); }
    @media (max-width: 1023.98px) {
        .sat-draw { margin: 0 -.45rem; }
        .sat-draw-map { height: max(20rem, calc(100svh - var(--app-head, 3.6rem) - 3.5rem - 10.5rem - env(safe-area-inset-bottom, 0px))); }
    }

    .sat-view { --sat-bar-h: 3.4rem; }
    .sat-rtabs { top: var(--sat-bar-h); -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 1.5rem), transparent);
        mask-image: linear-gradient(90deg, transparent 0, #000 1rem, #000 calc(100% - 1.5rem), transparent); }
    .sat-rtabs::after { content: ''; flex: none; width: .6rem; }
    .sat-top { grid-template-columns: auto minmax(0, 1fr); row-gap: .6rem; }
    .sat-top .sat-when { grid-column: 2; margin-top: -.3rem; }
    @media (max-width: 639.98px) {
        .sat-body { padding-top: .75rem; }
        .sat-top { gap: .85rem; padding: .9rem; border-radius: 1.1rem; }
        .sat-ring { width: 4.9rem; height: 4.9rem; }
        .sat-ring::before { inset: .42rem; }
        .sat-ring b { font-size: 1.45rem; }
        .sat-ring small { font-size: .58rem; }
        .sat-top h2 { font-size: 1rem; }
        .sat-top p { margin-top: .2rem; font-size: .76rem; }
        .sat-top .sat-when { grid-column: 1 / -1; margin-top: 0; flex-wrap: wrap; }
        .sat-when span { max-width: 100%; white-space: normal; line-height: 1.35; }
        .sat-stats { gap: .5rem; }
        .sat-stat { padding: .65rem .7rem; }
        .sat-stat b { font-size: 1.1rem; }
        .sat-card { padding: .9rem; }
    }
    .sat-map { height: min(68vh, 36rem); }
    @media (max-width: 1023.98px) {
        .sat-pane[data-p="map"] .sat-map-wrap { margin: 0 -1rem; border-radius: 0; border-left: 0; border-right: 0; }
        .sat-map { height: max(22rem, calc(100svh - var(--sat-bar-h) - 3.4rem - env(safe-area-inset-bottom, 0px))); }
        .sat-pane[data-p="map"] .sat-note { padding: 0 .1rem; }
    }
    .sat-layers { flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none; right: 0; padding-right: .6rem; }
    .sat-layers::-webkit-scrollbar { display: none; }
    .sat-layers button { flex: none; }
    .sat-opacity { position: absolute; right: .6rem; bottom: 1.9rem; z-index: 2; }
    .sat-opacity input { width: 5.5rem; }
    .sat-legend { bottom: 1.9rem; }
    @media (max-width: 379.98px) { .sat-legend i { width: 6.5rem; } .sat-opacity input { width: 4.2rem; } }
    @media (prefers-reduced-motion: reduce) {
        .sat-orbit i, .sat-step.is-on, .sat-pane.is-on, .sat-bar span i { animation: none; }
        .sat-view, .sat-tab, .sat-pill, .sat-run, .sat-srow, .sat-rail i::after { transition: none; }
    }
    html.sm-still .sat-orbit i, html.sm-still .sat-step.is-on, html.sm-still .sat-pane.is-on { animation: none; }
</style>

<div class="max-w-3xl mx-auto">
    <div class="sat-hero">
        <span class="sat-orbit" aria-hidden="true"><i></i></span>
        <h2>See your field from space, then ask Anee what it means</h2>
        <p>Draw your field and Anee reads the newest satellite picture and radar<span class="sat-more"> (Sentinel-2 crop greenness, and Sentinel-1, which sees through typhoon clouds), then weighs the weather, ENSO, the climate and the soil of your area</span>.</p>
        <div class="sat-chips"><span>Sentinel-2 · 10 m</span><span>Sentinel-1 radar</span><span>Last 5 to 10 days</span><span>Philippine time</span></div>
    </div>

    <div class="sat-tabs" role="tablist">
        <button type="button" class="sat-tab is-on" id="satTabGen" role="tab" aria-selected="true">New analysis</button>
        <button type="button" class="sat-tab" id="satTabSaved" role="tab" aria-selected="false">Saved</button>
    </div>

    <div id="satGen">
        <div class="sat-quote" id="satQuote" hidden></div>
        <div class="card p-5 sat-wiz" id="satWiz">
            <div class="sat-stepno"><span id="satStepNo">Step 1 of 7</span><span id="satStepName">The place</span></div>
            <div class="sat-rail" id="satRail" aria-hidden="true"></div>

            {{-- Every choice is a tag that opens a sheet (the owner's rule for the
                 wizards, 2026-10-08): the province and the town, the crop, how it
                 was planted, the water, the soil and the worries. --}}
            <section class="sat-step is-on" data-step="0">
                <h3>Where is the field?</h3>
                @if (\App\Support\Region::ph())
                    <p class="sat-sub">Pick the province and the town. The map opens there, and you draw the field on it.</p>
                    <span class="sat-label">Province</span>
                    <button type="button" class="sat-choice is-none" id="satProvBtn"><span>Choose the province</span><svg class="sat-choice-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
                    <span class="sat-label">Town or city</span>
                    <button type="button" class="sat-choice is-none" id="satTownBtn" disabled><span>Pick the province first</span><svg class="sat-choice-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
                    <div class="sat-row mt-3" style="flex-wrap:wrap">
                        <button type="button" class="sat-pill" id="satHere">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>
                            Use my current location
                        </button>
                        <button type="button" class="sat-link" id="satByName">Or find a barangay by name</button>
                    </div>
                    <div id="satByNameBox" hidden>
                        <div class="sat-row mt-2">
                            <input type="text" id="satPlaceQ" class="form-input flex-1" maxlength="120" placeholder="Like Barangay Bantug, Muñoz" autocomplete="off">
                            <button type="button" class="btn btn-white" id="satPlaceGo">Find</button>
                        </div>
                    </div>
                @else
                    <p class="sat-sub">Type the town or the county and the state, then pick it. Or use where you are standing now.</p>
                    <div class="sat-row">
                        <input type="text" id="satPlaceQ" class="form-input flex-1" maxlength="120" placeholder="Like Davis, California" autocomplete="off">
                        <button type="button" class="btn btn-white" id="satPlaceGo">Find</button>
                    </div>
                    <button type="button" class="sat-pill mt-2" id="satHere">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>
                        Use my current location
                    </button>
                @endif
                <div class="sat-places" id="satPlaces"></div>
                <p class="sat-picked" id="satPicked" hidden></p>
            </section>

            <section class="sat-step" data-step="1">
                <h3>What is growing there?</h3>
                <p class="sat-sub">The crop tells Anee how green and how tall the field should look by now.</p>
                <span class="sat-label">Crop</span>
                <button type="button" class="crop-tag" id="satCropBtn">
                    <span class="crop-tag-e" id="satCropIcon">🌱</span>
                    <span class="crop-tag-t is-none" id="satCropNow">Choose the crop</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
                <label class="sat-label" for="satVariety">Variety <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" id="satVariety" class="form-input" maxlength="80" placeholder="Like NSIC Rc 222, or a hybrid corn name">
            </section>

            <section class="sat-step" data-step="2">
                <h3>When and how was it planted?</h3>
                <p class="sat-sub">The age tells Anee how green the field should be right now.</p>
                <span class="sat-label">Planting date</span>
                @include('partials.date-tag', ['id' => 'satPlanted', 'empty' => 'Pick the planting date', 'class' => 'sat-date'])
                <span class="sat-label">How it was planted</span>
                <button type="button" class="sat-choice" id="satMethodBtn"><span>Transplanted</span><svg class="sat-choice-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
                <label class="sat-label" for="satDensity">Seeding rate or plant density <span class="text-gray-400 font-normal">(optional)</span></label>
                <div class="sat-row">
                    <input type="number" id="satDensity" class="form-input flex-1" min="1" step="any" inputmode="decimal" placeholder="Like 90000">
                    <button type="button" class="sat-choice is-unit" id="satDensityUnitBtn" aria-label="Unit"><span>seeds per ha</span><svg class="sat-choice-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
                </div>
            </section>

            <section class="sat-step" data-step="3">
                <h3>The ground and the water</h3>
                <p class="sat-sub">Pick what you know. Leave the rest.</p>
                <span class="sat-label">Water</span>
                <button type="button" class="sat-choice" id="satWaterBtn"><span>Irrigated</span><svg class="sat-choice-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
                <span class="sat-label">Soil</span>
                <button type="button" class="sat-choice is-none" id="satSoilBtn"><span>Not sure</span><svg class="sat-choice-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
                <span class="sat-label">Soil pH</span>
                <label class="sat-switch"><input type="checkbox" id="satPhOn"> <span>I know the pH, from a soil test</span></label>
                <div class="sat-ph" id="satPhBox" hidden>
                    <div class="sat-ph-read"><b id="satPhSay">pH 6 to 6.8</b><span id="satPhWord"></span></div>
                    <div class="sat-ph-range">
                        <span class="sat-ph-track" aria-hidden="true"></span>
                        <span class="sat-ph-sel" id="satPhSel" aria-hidden="true"></span>
                        <input type="range" id="satPhLo" min="3.5" max="10" step="0.1" value="6" aria-label="pH from">
                        <input type="range" id="satPhHi" min="3.5" max="10" step="0.1" value="6.8" aria-label="pH to">
                    </div>
                    <div class="sat-ph-scale" aria-hidden="true"><span class="is-acid" style="left:19%">Acidic</span><span class="is-neutral" style="left:52.3%">Neutral</span><span class="is-alk" style="left:80%">Alkaline</span></div>
                    <div class="sat-ph-nums">
                        <label>From <input type="number" id="satPhLoIn" class="form-input" min="3.5" max="10" step="0.1" inputmode="decimal" value="6"></label>
                        <label>To <input type="number" id="satPhHiIn" class="form-input" min="3.5" max="10" step="0.1" inputmode="decimal" value="6.8"></label>
                    </div>
                    <p class="sat-ph-err" id="satPhErr" hidden></p>
                    <p class="sat-note">Drag the two handles, or type the numbers. One test reading? Set both to it.</p>
                </div>
            </section>

            <section class="sat-step" data-step="4">
                <h3>Anything worrying you?</h3>
                <p class="sat-sub">Anee looks hardest where you point. Pick any that fit.</p>
                <span class="sat-label">What you have seen</span>
                <button type="button" class="sat-choice is-none" id="satConcernsBtn"><span>Nothing picked</span><svg class="sat-choice-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
                <label class="sat-label" for="satNotes">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="satNotes" class="form-textarea" rows="3" maxlength="800" placeholder="Like: the north end flooded last week"></textarea>
            </section>

            <section class="sat-step" data-step="5">
                <h3>Draw the field</h3>
                <p class="sat-sub">Tap each corner of the field on the map, in order around it. Drag a corner to fix it.</p>
                <div class="sat-draw">
                    <div class="sat-draw-map" id="satDrawMap"></div>
                    <div class="sat-draw-bar">
                        <button type="button" id="satUndo" disabled><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14L4 9l5-5M4 9h10a6 6 0 010 12h-3"/></svg>Undo</button>
                        <button type="button" id="satClear" disabled><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>Clear</button>
                        <button type="button" id="satFit"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>Center</button>
                    </div>
                    <div class="sat-draw-area" id="satArea">Tap the first corner</div>
                </div>
                <p class="sat-draw-hint">Three corners at least. Up to 2,000 hectares. The satellite pixel is 10 meters, so very small plots read less surely.</p>
            </section>

            <section class="sat-step" data-step="6">
                <h3>Ready to read the field</h3>
                <div class="sat-review" id="satReview"></div>
                <button type="button" class="sat-run" id="satRun">
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                    <span id="satRunSays">Run the satellite analysis</span>
                </button>
                <p class="text-xs text-gray-400 mt-2 text-center" id="satRunFine"></p>
            </section>

            @include('sm.partials.anee-wait')

            <div class="sat-nav">
                <button type="button" class="btn btn-white flex-1" id="satBack" disabled>Back</button>
                <button type="button" class="btn btn-primary flex-1" id="satNext">Next</button>
            </div>
        </div>
    </div>

    <div id="satSavedPane" hidden>
        <input type="search" id="satSavedQ" class="form-input mb-3" placeholder="Search your analyses" autocomplete="off">
        <div class="sat-saved" id="satSaved"></div>
        <p class="text-center text-sm text-gray-400 py-8" id="satSavedEmpty" hidden>No saved analyses yet. Run one and it lands here.</p>
        <button type="button" class="btn btn-white w-full mt-3" id="satSavedMore" hidden>Show more</button>
    </div>
</div>

{{-- One sheet for every choice: a list, searchable when long, one or several. --}}
<div class="sheet hidden" id="satPickSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="satPickTitle">Choose</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="form-hint mt-0 mb-3" id="satPickHint" hidden></p>
        <div class="sat-psearch" id="satPickSearchBox" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" id="satPickQ" class="form-input" autocomplete="off" placeholder="Search">
        </div>
        <div class="dt-rows" id="satPickRows"></div>
        <p class="sat-note text-center py-3" id="satPickNone" hidden>Nothing by that name.</p>
    </div>
    <div class="sheet-footer" id="satPickFoot" hidden>
        <button type="button" class="btn btn-primary" data-sheet-close>Done</button>
    </div>
</div>

<div class="sat-view" id="satView" hidden role="dialog" aria-modal="true" aria-label="Satellite analysis">
    <div class="sat-view-bar">
        <b id="satViewTitle">Satellite analysis</b>
        <button type="button" class="sat-x" id="satViewX" aria-label="Close"><svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
    </div>
    <div class="sat-body" id="satReport"></div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const U = {
        options: @json(route('sat.options')), places: @json(route('sat.places')), generate: @json(route('sat.generate')),
        job: (id) => @json(url('/app/satellite/job')) + '/' + id, list: @json(route('sat.list')),
        one: (id) => @json(url('/app/satellite/one')) + '/' + id, tiles: (id) => @json(url('/app/satellite/tiles')) + '/' + id,
        del: (id) => @json(url('/app/satellite')) + '/' + id,
    };
    let OPT = null;
    const st = { step: 0, place: null, crop: null, method: 'transplanted', water: 'irrigated', soil: [], concerns: [], ring: [], densityUnit: 'seeds_ha', prov: null, town: null };
    const STEPS = 7;

    /* Shops, stops and pins only clutter a field: the map keeps roads and places. */
    const QUIET = [{ featureType: 'poi', stylers: [{ visibility: 'off' }] }, { featureType: 'transit', stylers: [{ visibility: 'off' }] }];
    /* ---- Google Maps, loaded once for both maps on this page ---- */
    let mapsReady = null;
    const loadMaps = () => mapsReady ??= new Promise((resolve, reject) => {
        if (window.google && window.google.maps && window.google.maps.geometry) return resolve(window.google.maps);
        const cb = '__satMaps' + Date.now();
        window[cb] = () => resolve(window.google.maps);
        const s = document.createElement('script');
        s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(OPT.mapsKey) + '&libraries=geometry&callback=' + cb;
        s.async = true;
        s.onerror = () => reject(new Error('The map could not load.'));
        document.head.appendChild(s);
    });

    /* ---- the wizard ---- */
    const rail = $('satRail');
    rail.innerHTML = Array.from({ length: STEPS }, () => '<i></i>').join('');
    const show = (n, back = false) => {
        st.step = n;
        document.querySelectorAll('.sat-step').forEach((s) => {
            const on = Number(s.dataset.step) === n;
            s.classList.toggle('is-on', on);
            s.classList.toggle('is-back', on && back);
        });
        [...rail.children].forEach((i, k) => { i.classList.toggle('is-done', k < n); i.classList.toggle('is-on', k === n); });
        $('satBack').disabled = n === 0;
        $('satNext').hidden = n === STEPS - 1;
        $('satStepNo').textContent = 'Step ' + (n + 1) + ' of ' + STEPS;
        $('satStepName').textContent = ['The place', 'The crop', 'Planting', 'Ground and water', 'Your worries', 'Draw the field', 'Review'][n] || '';
        if (n === 5) initDraw();
        if (n === 6) review();
        // A step starts at its title: on a phone the last one may have left the page scrolled down.
        const wiz = $('satWiz');
        if (booted && wiz.getBoundingClientRect().top < (parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--app-head')) || 60)) {
            wiz.scrollIntoView({ block: 'start', behavior: reduce() ? 'auto' : 'smooth' });
        }
    };
    let booted = false;
    const TICK = '<svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    const words = (v) => String(v || '').split(' — ');
    const tagSay = (id, text, none) => { const b = $(id); if (!b) return; b.querySelector('span').textContent = text; b.classList.toggle('is-none', !!none); };

    /* ---- one sheet for every choice: a list, searchable when long, one or several ---- */
    let pk = null;
    const pick = ({ title, hint = '', items, cur, multi = false, search = false, exclusive = null, cb }) => {
        if (!window.openSheet) return window.toast?.('One moment, the page is still loading.', 'info');
        $('satPickTitle').textContent = title;
        $('satPickHint').textContent = hint; $('satPickHint').hidden = !hint;
        $('satPickSearchBox').hidden = !search; $('satPickQ').value = '';
        $('satPickFoot').hidden = !multi;
        pk = { items, multi, sel: multi ? [...(cur || [])] : cur, exclusive, cb };
        paintPick();
        window.openSheet('satPickSheet');
        if (search && !matchMedia('(hover: none)').matches) setTimeout(() => $('satPickQ').focus(), 280);
    };
    const paintPick = () => {
        const q = ($('satPickQ').value || '').trim().toLowerCase();
        let html = '', group = null, shown = 0;
        pk.items.forEach((it) => {
            if (q && !(it.label + ' ' + (it.sub || '') + ' ' + (it.group || '')).toLowerCase().includes(q)) return;
            if (it.group && it.group !== group) { group = it.group; html += '<p class="sat-pick-h">' + esc(group) + '</p>'; }
            const on = pk.multi ? pk.sel.includes(it.k) : pk.sel === it.k;
            html += '<button type="button" class="dt-row' + (on ? ' is-on' : '') + '" data-k="' + esc(it.k) + '" aria-pressed="' + on + '">' + (it.icon ? '<span class="dt-row-e">' + esc(it.icon) + '</span>' : '')
                + '<span class="dt-row-body"><b>' + esc(it.label) + '</b>' + (it.sub ? '<i>' + esc(it.sub) + '</i>' : '') + '</span>' + TICK + '</button>';
            shown++;
        });
        $('satPickRows').innerHTML = html;
        $('satPickNone').hidden = shown > 0;
    };
    $('satPickQ').addEventListener('input', () => pk && paintPick());
    $('satPickRows').addEventListener('click', (e) => {
        const b = e.target.closest('.dt-row');
        if (!b || !pk) return;
        const k = b.dataset.k;
        if (pk.multi) {
            pk.sel = pk.sel.includes(k) ? pk.sel.filter((x) => x !== k) : (pk.exclusive ? pk.exclusive(pk.sel, k) : pk.sel).concat(k);
            paintPick();
            pk.cb(pk.sel);
            return;
        }
        $('satPickRows').querySelectorAll('.dt-row').forEach((x) => x.classList.toggle('is-on', x === b));
        const cb = pk.cb; pk = null;
        setTimeout(() => window.closeSheet('satPickSheet'), 150);
        cb(k);
    });
    const listOf = (obj) => Object.entries(obj).map(([k, v]) => { const [label, sub] = words(v); return { k, label, sub: sub ? sub.charAt(0).toUpperCase() + sub.slice(1) : '' }; });

    /* the crop */
    const cropName = (c) => String(c.label || '').replace(' — ', ', ');
    const setCrop = (k) => {
        st.crop = k;
        const c = OPT.crops.find((x) => x.key === k);
        $('satCropIcon').textContent = c ? c.icon : '🌱';
        $('satCropNow').textContent = c ? cropName(c) : 'Choose the crop';
        $('satCropNow').classList.toggle('is-none', !c);
        // The usual way in for the crop: transplanted palay, seeded rows for the rest.
        st.method = c && c.perennial ? 'perennial' : (/^rice/.test(k) ? (/dsr|direct/.test(k) ? 'direct' : 'transplanted') : 'planted');
        tagSay('satMethodBtn', words(OPT.methods[st.method])[0]);
    };
    $('satCropBtn').addEventListener('click', () => {
        if (!OPT) return;
        pick({ title: 'Choose the crop', search: true, cur: st.crop, items: OPT.crops.map((c) => ({ k: c.key, label: cropName(c), icon: c.icon, group: c.group,
            sub: c.perennial ? 'Tree crop, counted by age' : (c.maturity ? c.maturity + ' days to harvest' : '') })), cb: (k) => { setCrop(k); if (st.step === 1) setTimeout(() => $('satVariety').focus({ preventScroll: true }), 300); } });
    });

    /* how it was planted, the water, the soil, the worries */
    const DENSITY = { seeds_ha: 'seeds per ha', plants_ha: 'plants per ha', kg_ha: 'kg seed per ha', hills_m2: 'hills per m²' };
    $('satMethodBtn').addEventListener('click', () => OPT && pick({ title: 'How it was planted', items: listOf(OPT.methods), cur: st.method, cb: (k) => { st.method = k; tagSay('satMethodBtn', words(OPT.methods[k])[0]); } }));
    $('satDensityUnitBtn').addEventListener('click', () => pick({ title: 'The rate is in', items: listOf(DENSITY), cur: st.densityUnit, cb: (k) => { st.densityUnit = k; tagSay('satDensityUnitBtn', DENSITY[k]); } }));
    $('satWaterBtn').addEventListener('click', () => OPT && pick({ title: 'Where the water comes from', items: listOf(OPT.water), cur: st.water, cb: (k) => { st.water = k; tagSay('satWaterBtn', words(OPT.water[k])[0]); } }));
    const PH_WORDS = ['acidic', 'neutral', 'alkaline'];
    const soilSay = () => tagSay('satSoilBtn', st.soil.length ? st.soil.map((k) => words(OPT.soilConditions[k])[0]).join(', ') : 'Not sure', !st.soil.length);
    $('satSoilBtn').addEventListener('click', () => OPT && pick({ title: 'What is true of the soil', hint: 'Pick all that fit. Leave it empty if you are not sure.', multi: true, cur: st.soil,
        items: listOf(Object.fromEntries(Object.entries(OPT.soilConditions).filter(([k]) => k !== 'unsure'))),
        // The three pH words exclude one another; sodium, salt and acid sulfate ride with any.
        exclusive: (sel, k) => PH_WORDS.includes(k) ? sel.filter((x) => !PH_WORDS.includes(x)) : sel,
        cb: (sel) => { st.soil = sel; soilSay(); } }));
    const concernsSay = () => tagSay('satConcernsBtn', st.concerns.length ? st.concerns.map((k) => words(OPT.concerns[k])[0]).join(', ') : 'Nothing picked', !st.concerns.length);
    $('satConcernsBtn').addEventListener('click', () => OPT && pick({ title: 'Anything worrying you?', hint: 'Pick any that fit.', multi: true, cur: st.concerns, items: listOf(OPT.concerns),
        exclusive: (sel, k) => k === 'none' ? [] : sel.filter((x) => x !== 'none'),
        cb: (sel) => { st.concerns = sel; concernsSay(); } }));

    /* the soil pH: a range from and to, on the soil's colours */
    const PH_MIN = 3.5, PH_MAX = 10;
    const phStops = [[3.5, [192, 38, 45]], [4.5, [232, 89, 12]], [5.5, [242, 183, 5]], [6.2, [148, 193, 31]], [6.5, [47, 158, 68]], [7.3, [47, 158, 68]], [8, [28, 126, 214]], [9, [95, 61, 196]], [10, [123, 44, 191]]];
    const phColor = (v) => {
        for (let i = 1; i < phStops.length; i++) {
            const [b, cb] = phStops[i], [a, ca] = phStops[i - 1];
            if (v <= b) { const t = (v - a) / (b - a); return 'rgb(' + ca.map((x, j) => Math.round(x + (cb[j] - x) * t)).join(' ') + ')'; }
        }
        return 'rgb(123 44 191)';
    };
    const phWord = (v) => v < 5 ? 'Strongly acidic' : v < 6 ? 'Acidic' : v < 6.5 ? 'Slightly acidic' : v <= 7.3 ? 'Neutral' : v <= 7.8 ? 'Slightly alkaline' : v < 8.5 ? 'Alkaline' : 'Strongly alkaline';
    const fx = (v) => String(Math.round(v * 10) / 10);
    const phPaint = (from) => {
        const lo = $('satPhLo'), hi = $('satPhHi');
        let a = Number(lo.value), b = Number(hi.value);
        // The handles never cross: the one being moved stops at the other.
        if (a > b) { if (from === 'hi') { b = a; hi.value = b; } else { a = b; lo.value = a; } }
        const pc = (v) => (v - PH_MIN) / (PH_MAX - PH_MIN) * 100;
        $('satPhSel').style.left = 'calc(' + pc(a) + '% - .65rem)';
        $('satPhSel').style.width = 'calc(' + (pc(b) - pc(a)) + '% + 1.3rem)';
        lo.style.setProperty('--c', phColor(a)); hi.style.setProperty('--c', phColor(b));
        // The low handle sits on top when both are at the top end, so it can still be pulled down.
        lo.style.zIndex = a >= PH_MAX - 0.3 ? 3 : 1;
        $('satPhSay').textContent = a === b ? 'pH ' + fx(a) : 'pH ' + fx(a) + ' to ' + fx(b);
        $('satPhWord').textContent = phWord(a) === phWord(b) ? phWord(a) : phWord(a) + ' to ' + phWord(b).toLowerCase();
        if (from !== 'num') { $('satPhLoIn').value = fx(a); $('satPhHiIn').value = fx(b); }
        st.ph = $('satPhOn').checked ? { lo: Math.round(a * 10) / 10, hi: Math.round(b * 10) / 10 } : null;
    };
    $('satPhLo').addEventListener('input', () => phPaint('lo'));
    $('satPhHi').addEventListener('input', () => phPaint('hi'));
    const phTyped = () => {
        const a = Number($('satPhLoIn').value), b = Number($('satPhHiIn').value);
        let why = '';
        if ($('satPhLoIn').value === '' || $('satPhHiIn').value === '' || !isFinite(a) || !isFinite(b)) why = 'Type both numbers.';
        else if (a < PH_MIN || b > PH_MAX || a > PH_MAX || b < PH_MIN) why = 'A soil pH is between ' + PH_MIN + ' and ' + PH_MAX + '.';
        else if (a > b) why = 'The first number should be the lower one.';
        $('satPhErr').textContent = why; $('satPhErr').hidden = !why;
        if (why) return;
        $('satPhLo').value = a; $('satPhHi').value = b;
        phPaint('num');
    };
    $('satPhLoIn').addEventListener('input', phTyped);
    $('satPhHiIn').addEventListener('input', phTyped);
    $('satPhOn').addEventListener('change', () => { $('satPhBox').hidden = !$('satPhOn').checked; $('satPhErr').hidden = true; phPaint('lo'); });
    phPaint('lo');

    /* the place */
    const setPlace = (p) => {
        st.place = p;
        $('satPicked').hidden = false;
        $('satPicked').innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>' + esc(p.label);
        st.ring = [];
    };
    const findPlace = async () => {
        const q = $('satPlaceQ').value.trim();
        if (q.length < 2) return;
        $('satPlaces').innerHTML = '<p class="sat-note">Looking…</p>';
        try {
            const r = await window.api(U.places + '?q=' + encodeURIComponent(q));
            const list = (r.data && r.data.places) || [];
            $('satPlaces').innerHTML = list.length ? list.map((p, i) => '<button type="button" class="sat-place" data-i="' + i + '"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.2-7-11a7 7 0 0114 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg><span>' + esc(p.label || p.name) + '</span></button>').join('')
                : '<p class="sat-note">No place by that name. Try the town and province.</p>';
            $('satPlaces').onclick = (e) => {
                const b = e.target.closest('.sat-place');
                if (!b) return;
                const p = list[Number(b.dataset.i)];
                $('satPlaces').querySelectorAll('.sat-place').forEach((x) => x.classList.toggle('is-on', x === b));
                setPlace({ label: p.label || p.name, lat: Number(p.lat), lng: Number(p.lng) });
            };
        } catch (err) { $('satPlaces').innerHTML = '<p class="sat-note">' + esc(err.message) + '</p>'; }
    };
    $('satPlaceGo').addEventListener('click', findPlace);
    /* In the Philippines: the province, then the town, each from its sheet;
       the map opens on the town (found through the same place search). */
    let PH = null;
    const loadPh = async () => PH ??= await (await fetch(@json(asset('data/ph-locations.json')))).json();
    const townName = (t) => String(t).replace(/\s*\(Capital\)\s*$/i, '');
    $('satProvBtn')?.addEventListener('click', async () => {
        try { await loadPh(); } catch (_) { return window.toast?.('The list of provinces could not load. Find the place by name instead.', 'error'); }
        pick({ title: 'Choose the province', search: true, cur: st.prov, items: Object.keys(PH).sort((a, b) => a.localeCompare(b)).map((p) => ({ k: p, label: p, sub: PH[p].length + ' towns and cities' })),
            cb: (k) => {
                if (k !== st.prov) { st.town = null; tagSay('satTownBtn', 'Choose the town', true); }
                st.prov = k; tagSay('satProvBtn', k);
                $('satTownBtn').disabled = false;
                if (!st.town) setTimeout(() => $('satTownBtn').click(), 260);
            } });
    });
    $('satTownBtn')?.addEventListener('click', async () => {
        if (!st.prov) return;
        try { await loadPh(); } catch (_) { return; }
        pick({ title: 'Choose the town or city', hint: st.prov, search: true, cur: st.town, items: (PH[st.prov] || []).map((t) => ({ k: t, label: townName(t), sub: /\(Capital\)/i.test(t) ? 'The capital' : '' })),
            cb: (k) => { st.town = k; tagSay('satTownBtn', townName(k)); locate(townName(k), st.prov); } });
    });
    const locate = async (town, prov) => {
        $('satPicked').hidden = false;
        $('satPicked').textContent = 'Finding ' + town + ' on the map…';
        const ask = async (q) => { const r = await window.api(U.places + '?q=' + encodeURIComponent(q)); return ((r.data && r.data.places) || [])[0] || null; };
        try {
            const p = (await ask(town + ', ' + prov + ', Philippines')) || (await ask(town + ', ' + prov)) || (await ask(prov + ', Philippines'));
            if (!p) throw new Error('That town could not be found on the map. Find it by name instead.');
            setPlace({ label: town + ', ' + prov, lat: Number(p.lat), lng: Number(p.lng) });
        } catch (err) { $('satPicked').hidden = true; window.toast?.(err.message, 'error'); }
    };
    $('satByName')?.addEventListener('click', () => { $('satByNameBox').hidden = false; $('satByName').hidden = true; $('satPlaceQ').focus(); });
    $('satPlaceQ').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); findPlace(); } });
    $('satHere').addEventListener('click', () => {
        if (!navigator.geolocation) return window.toast?.('This phone cannot share its location.', 'error');
        $('satHere').disabled = true;
        navigator.geolocation.getCurrentPosition((pos) => {
            $('satHere').disabled = false;
            const lat = pos.coords.latitude, lng = pos.coords.longitude;
            setPlace({ label: 'My location (' + lat.toFixed(4) + ', ' + lng.toFixed(4) + ')', lat, lng, here: true });
            if (!$('satPlaceQ').value.trim()) $('satPlaceQ').value = 'My location';
        }, () => { $('satHere').disabled = false; window.toast?.('Location was not shared. Type the place instead.', 'error'); }, { enableHighAccuracy: true, timeout: 12000 });
    });

    /* ---- drawing the field ---- */
    let dmap = null, dpoly = null, dmarks = [];
    const ringArea = () => {
        if (st.ring.length < 3 || !window.google) return 0;
        return google.maps.geometry.spherical.computeArea(st.ring.map((p) => new google.maps.LatLng(p.lat, p.lng))) / 10000;
    };
    const paintDraw = () => {
        dmarks.forEach((m) => m.setMap(null));
        dmarks = st.ring.map((p, i) => {
            const m = new google.maps.Marker({ position: p, map: dmap, draggable: true,
                icon: { path: google.maps.SymbolPath.CIRCLE, scale: 7, fillColor: i === 0 ? '#f5c518' : '#ffffff', fillOpacity: 1, strokeColor: '#1d3310', strokeWeight: 2 } });
            m.addListener('drag', (e) => { st.ring[i] = { lat: e.latLng.lat(), lng: e.latLng.lng() }; dpoly.setPath(st.ring); area(); });
            return m;
        });
        dpoly.setPath(st.ring);
        $('satUndo').disabled = !st.ring.length;
        $('satClear').disabled = !st.ring.length;
        area();
    };
    const area = () => {
        const ha = ringArea();
        $('satArea').textContent = st.ring.length < 3 ? (st.ring.length ? (3 - st.ring.length) + ' more corner' + (st.ring.length === 2 ? '' : 's') : 'Tap the first corner')
            : ha.toLocaleString(undefined, { maximumFractionDigits: ha < 10 ? 2 : 1 }) + ' ha · ' + st.ring.length + ' corners';
    };
    const initDraw = async () => {
        if (!st.place) return;
        try { await loadMaps(); } catch (err) { $('satArea').textContent = err.message; return; }
        if (!dmap) {
            dmap = new google.maps.Map($('satDrawMap'), { center: st.place, zoom: st.place.here ? 17 : 15, mapTypeId: 'hybrid', streetViewControl: false, fullscreenControl: false,
                mapTypeControl: false, cameraControl: false, clickableIcons: false, gestureHandling: 'greedy', tilt: 0, mapId: OPT.mapId || 'DEMO_MAP_ID', renderingType: 'VECTOR', headingInteractionEnabled: true, tiltInteractionEnabled: false, rotateControl: true, styles: QUIET });
            dpoly = new google.maps.Polygon({ map: dmap, strokeColor: '#f5c518', strokeWeight: 2.5, fillColor: '#f5c518', fillOpacity: .18, clickable: false });
            dmap.addListener('click', (e) => { if (st.ring.length < 300) { st.ring.push({ lat: e.latLng.lat(), lng: e.latLng.lng() }); paintDraw(); } });
        } else if (!st.ring.length) {
            dmap.setCenter(st.place);
        }
        paintDraw();
    };
    $('satUndo').addEventListener('click', () => { st.ring.pop(); paintDraw(); });
    $('satClear').addEventListener('click', () => { st.ring = []; paintDraw(); });
    $('satFit').addEventListener('click', () => {
        if (!dmap) return;
        if (st.ring.length) { const b = new google.maps.LatLngBounds(); st.ring.forEach((p) => b.extend(p)); dmap.fitBounds(b, 40); } else dmap.setCenter(st.place);
    });

    /* ---- review and run ---- */
    const label = (obj, k) => obj[k] || k;
    const review = () => {
        const c = OPT.crops.find((x) => x.key === st.crop);
        const rows = [
            ['Place', st.place ? st.place.label : ''], ['Crop', c ? c.icon + ' ' + cropName(c) + ($('satVariety').value.trim() ? ', ' + $('satVariety').value.trim() : '') : ''],
            ['Planted', ($('satPlanted').value || 'Not given') + ' · ' + words(label(OPT.methods, st.method))[0]],
            ['Water and soil', words(label(OPT.water, st.water))[0] + (st.soil.length ? ' · ' + st.soil.map((k) => words(OPT.soilConditions[k] || k)[0]).join(', ') : '') + (st.ph ? ' · pH ' + (st.ph.lo === st.ph.hi ? st.ph.lo : st.ph.lo + ' to ' + st.ph.hi) : '')],
            ['Field', ringArea().toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' ha, ' + st.ring.length + ' corners'],
        ];
        $('satReview').innerHTML = rows.map(([k, v]) => '<div><b>' + esc(k) + '</b><span>' + esc(v) + '</span></div>').join('');
        $('satRunFine').textContent = OPT.quote ? OPT.quote + ' credits, charged only when the report is ready. Nothing is charged if the satellites have nothing new.' : '';
        $('satRun').disabled = !OPT.canUse || !OPT.connected;
        $('satRunSays').textContent = !OPT.connected ? 'The satellite link is being set up' : (OPT.canUse ? 'Run the satellite analysis' : 'Anee is not on your plan');
    };
    const canNext = () => {
        if (st.step === 0 && !st.place) return st.town ? 'One moment, the town is still being found on the map.' : 'Pick the place first.';
        if (st.step === 1 && !st.crop) return 'Pick the crop.';
        if (st.step === 3 && $('satPhOn').checked && !$('satPhErr').hidden) return $('satPhErr').textContent;
        if (st.step === 5 && st.ring.length < 3) return 'Tap at least three corners of the field.';
        if (st.step === 5 && ringArea() > 2000) return 'That is over 2,000 hectares. Draw one field at a time.';
        return null;
    };
    $('satNext').addEventListener('click', () => { const why = canNext(); if (why) return window.toast?.(why, 'error'); show(st.step + 1); });
    $('satBack').addEventListener('click', () => show(Math.max(0, st.step - 1), true));

    const PHASES = {
        start: { label: 'Getting started', lo: 2, hi: 6, tau: 8 },
        satellite: { label: 'Reading Sentinel-2 and Sentinel-1', lo: 6, hi: 34, tau: 70 },
        context: { label: 'Gathering weather, ENSO and soil', lo: 34, hi: 40, tau: 15 },
        research: { label: 'Reading about your area', lo: 40, hi: 66, tau: 100 },
        document: { label: 'Writing the analysis', lo: 66, hi: 94, tau: 90 },
        'document-json': { label: 'Tidying the report', lo: 94, hi: 98, tau: 25 },
    };
    $('satRun').addEventListener('click', async () => {
        const ring = st.ring.map((p) => [Number(p.lng.toFixed(7)), Number(p.lat.toFixed(7))]);
        ring.push(ring[0]);
        const body = {
            location: st.place.label, crop: st.crop, variety: $('satVariety').value.trim(), plantedOn: $('satPlanted').value || null,
            method: st.method, density: $('satDensity').value ? Number($('satDensity').value) : null, densityUnit: st.densityUnit,
            water: st.water, soilConditions: st.soil, phValue: st.ph ? Math.round((st.ph.lo + st.ph.hi) / 2 * 10) / 10 : null, phLow: st.ph ? st.ph.lo : null, phHigh: st.ph ? st.ph.hi : null,
            concerns: st.concerns, notes: $('satNotes').value.trim(), polygon: { type: 'Polygon', coordinates: [ring] },
        };
        $('satRun').disabled = true;
        window.aneeWait.show({ title: 'Anee is reading your field…', sub: 'Satellite pictures, then the weather and the soil. About two minutes.',
            lines: ['Finding the newest clear Sentinel-2 picture…', 'Masking the clouds pixel by pixel…', 'Reading the radar through the clouds…', 'Comparing this week with the last three months…', 'Looking up the soil of your area…'] });
        try {
            const r = await window.api(U.generate, { method: 'POST', body });
            const d = r.data && r.data.status === 'ready' ? r.data : await window.aneeWait.poll({ id: r.data.id, job: U.job, phases: PHASES });
            await window.aneeWait.done({ title: 'Your field is read.', line: 'Here is what the satellites saw.' });
            OPT.balance = d.balance;
            quote();
            openReport(d);
        } catch (err) {
            window.aneeWait.fail();
            window.toast?.(err.message || 'The analysis did not finish. Nothing was charged.', 'error');
        } finally { $('satRun').disabled = false; }
    });

    const quote = () => {
        const q = $('satQuote');
        q.hidden = false;
        if (!OPT.connected) { q.classList.add('is-warn'); q.innerHTML = '<span>The satellite link is being set up. You can draw your field now, and runs open as soon as it is connected.</span>'; return; }
        if (!OPT.canUse) { q.classList.add('is-warn'); q.innerHTML = '<span>' + esc(OPT.whyNot || 'Anee is not on your plan.') + '</span>'; return; }
        q.innerHTML = '<span>One analysis costs <b>' + OPT.quote + ' credits</b>. You have ' + (window.creditCoin ? window.creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString()) : '<b>' + OPT.balance + '</b>') + '. Nothing is charged until the report is ready.</span>';
    };

    /* ---- the report ---- */
    let rmap = null, cur = null;
    const lv = (x) => ['low', 'medium', 'high'].includes(String(x).toLowerCase()) ? String(x).toLowerCase() : '';
    const ndviColor = (v) => {
        const pal = ['#a50026', '#d73027', '#f46d43', '#fdae61', '#fee08b', '#d9ef8b', '#a6d96a', '#66bd63', '#1a9850', '#006837'];
        return pal[Math.max(0, Math.min(9, Math.floor((Number(v) || 0) / 0.09)))];
    };
    const card = (title, inner, icon) => '<div class="sat-card"><h4>' + (icon || '') + esc(title) + '</h4><div class="mt-2">' + inner + '</div></div>';
    const p = (t) => t ? '<p>' + esc(t) + '</p>' : '';
    const chart = (rows, keys, colors, fmt) => {
        if (!rows || rows.length < 2) return '<p class="sat-note">Not enough passes in the last 90 days to draw a line.</p>';
        const W = 600, H = 140, pad = 26;
        const vals = rows.flatMap((r) => keys.map((k) => Number(r[k]))).filter((v) => !isNaN(v));
        let lo = Math.min(...vals), hi = Math.max(...vals);
        if (hi - lo < 0.05) { hi += 0.05; lo -= 0.05; }
        const x = (i) => pad + i * (W - pad * 2) / (rows.length - 1);
        const y = (v) => H - pad + 6 - (v - lo) / (hi - lo) * (H - pad * 1.6);
        const lines = keys.map((k, n) => '<polyline fill="none" stroke="' + colors[n] + '" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round" points="' + rows.map((r, i) => x(i).toFixed(1) + ',' + y(Number(r[k])).toFixed(1)).join(' ') + '"/>'
            + rows.map((r, i) => '<circle cx="' + x(i).toFixed(1) + '" cy="' + y(Number(r[k])).toFixed(1) + '" r="3" fill="' + colors[n] + '"><title>' + esc(r.date + ': ' + fmt(r[k])) + '</title></circle>').join('')).join('');
        return '<svg class="sat-chart" viewBox="0 0 ' + W + ' ' + H + '" preserveAspectRatio="none" role="img" aria-label="History chart">'
            + '<line x1="' + pad + '" x2="' + (W - pad) + '" y1="' + (H - pad + 6) + '" y2="' + (H - pad + 6) + '" stroke="currentColor" opacity=".15"/>' + lines
            + '<text x="' + pad + '" y="' + (H - 4) + '">' + esc(rows[0].date) + '</text><text x="' + (W - pad) + '" y="' + (H - 4) + '" text-anchor="end">' + esc(rows[rows.length - 1].date) + '</text>'
            + '<text x="2" y="14">' + esc(fmt(hi)) + '</text><text x="2" y="' + (H - pad + 4) + '">' + esc(fmt(lo)) + '</text></svg>';
    };
    const grid9 = (zones, color, fmt) => {
        if (!zones || !zones.length) return '<p class="sat-note">No zone reading.</p>';
        const cell = (r, c) => zones.find((z) => z.row === r && z.col === c);
        let h = '<div class="sat-grid9">';
        for (let r = 0; r < 3; r++) for (let c = 0; c < 3; c++) {
            const z = cell(r, c);
            const bg = z ? color(z.mean) : '';
            const light = /^#(fdae61|fee08b|d9ef8b|a6d96a)$/i.test(bg);
            h += z ? '<div style="background:' + bg + (light ? ';color:#1f2a17;text-shadow:none' : '') + '">' + esc(fmt(z.mean)) + '<small>' + esc(z.where) + '</small></div>' : '<div style="background:var(--color-gray-100)"></div>';
        }
        return h + '</div>';
    };
    const ICON = {
        eye: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>',
        leaf: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-9m0 0C12 7 8 5 4 5c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7z"/></svg>',
        radar: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 12l6-6M4.9 19.1a10 10 0 010-14.2M19.1 4.9a10 10 0 010 14.2M8 16a5.6 5.6 0 010-8M16 8a5.6 5.6 0 010 8"/></svg>',
        warn: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>',
        soil: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 15h18M3 19h18M7 11c0-3 2-6 5-6s5 3 5 6"/></svg>',
        check: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>',
        cloud: '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 18a4 4 0 01-.6-8A6 6 0 0118 8.5 4.5 4.5 0 0117.5 18H7z"/></svg>',
    };

    const openReport = (d) => {
        cur = d;
        const rep = d.report || {}, a = rep.anee || {}, s = rep.satellite || {}, s2 = s.sentinel2 || {}, s1 = s.sentinel1 || {}, cx = rep.context || {};
        const score = Math.max(0, Math.min(100, Number(a.healthScore) || 0));
        const ringColor = score >= 75 ? '#66bd63' : score >= 55 ? '#d9ef8b' : score >= 35 ? '#fdae61' : '#f46d43';
        const wx = cx.weather || {};
        const next = (wx.days || []).filter((x) => !x.past).slice(0, 10);
        const when = [];
        // When each picture was taken, said plainly, with how long ago (the owner could not find it).
        const ago = (t) => { const d = Math.round(Number((t || {}).ageDays)); return isNaN(d) ? '' : d <= 0 ? ' (today)' : d === 1 ? ' (yesterday)' : ' (' + d + ' days ago)'; };
        if (s2.available) when.push('<span>' + ICON.leaf.replace('<svg', '<svg width="12" height="12"') + ' Satellite photo taken ' + esc(s2.time.phText || s2.time.ph) + esc(ago(s2.time)) + '</span>');
        else when.push('<span class="is-warn">Clouds hid the field in the last photos' + (s2.lastClear && s2.lastClear.phText ? ' · last clear photo ' + esc(s2.lastClear.phText) + esc(ago(s2.lastClear)) : '') + '</span>');
        if (s1.available) when.push('<span>' + ICON.radar.replace('<svg', '<svg width="12" height="12"') + ' Radar pass ' + esc(s1.time.phText || s1.time.ph) + esc(ago(s1.time)) + '</span>');
        when.push('<span>' + esc(s.areaHa) + ' ha</span>');
        if (s.mode === 'radar-only') when.push('<span class="is-warn">Radar only (clouds)</span>');

        const tabs = [['overview', 'Overview'], ['map', 'Map'], ['health', 'Crop health'], ['radar', 'Radar'], ['threats', 'Threats and weather'], ['soil', 'Soil'], ['actions', 'What to do']];
        const H = a.health || {}, SP = a.spacing || {}, DZ = a.disease || {}, W = a.weather || {}, CL = a.climate || {}, SO = a.soil || {};
        const nd = s2.ndvi || {};
        const panes = {
            overview: '<div class="sat-stats">'
                + '<div class="sat-stat"><small>Greenness (NDVI)</small><b>' + (s2.available ? esc(nd.mean) : '–') + '</b><i>' + (s2.available ? 'of about 0.9 at full canopy' : 'clouds hid the field') + '</i></div>'
                + '<div class="sat-stat"><small>Radar VH</small><b>' + (s1.available ? esc(s1.vhDb) + ' dB' : '–') + '</b><i>' + (s1.available ? 'VV ' + esc(s1.vvDb) + ' dB' : 'no pass') + '</i></div>'
                + '<div class="sat-stat"><small>Rain, next 10 days</small><b>' + esc(wx.next10Rain ?? '–') + ' mm</b><i>last 30 days ' + esc(wx.past30Rain ?? '–') + ' mm</i></div>'
                + '<div class="sat-stat"><small>Confidence</small><b style="text-transform:capitalize">' + esc(a.confidence || '–') + '</b><i>' + esc((a.stage || {}).guess || '') + '</i></div></div>'
                + card('What Anee sees', p(a.summary) + p((a.stage || {}).basis ? 'Stage: ' + a.stage.guess + '. ' + a.stage.basis : ''), ICON.eye)
                + card('Stand and spacing', p(SP.reading) + p(SP.density) + '<p class="sat-note mt-2">' + esc(SP.note || '') + '</p>', ICON.leaf),
            map: '<div class="sat-mapnote"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>'
                + '<span><b>The latest photo is for analysis, like the heatmaps.</b> It is the satellite\'s 10 meter picture from the date shown, not a live view of how the field looks right now.</span></div>'
                + '<div class="sat-map-wrap"><div class="sat-map" id="satRMap"></div>'
                + '<div class="sat-layers"><button type="button" data-l="rgb" class="is-on">Latest photo</button><button type="button" data-l="ndvi">NDVI heatmap</button><button type="button" data-l="sar">Radar</button><button type="button" data-l="none">Map only</button>'
                + '</div><label class="sat-opacity">Layer <input type="range" id="satOp" min="0" max="100" value="100" aria-label="Layer opacity"></label>'
                + '<div class="sat-legend" id="satLegend" hidden>NDVI<i></i><span><em>0 bare</em><em>0.9 lush</em></span></div>'
                + '<div class="sat-shot" id="satShot" hidden></div></div>'
                + '<p class="sat-note">' + (s.source === 'planetary' ? 'Pictures from Copernicus Sentinel 2 and Sentinel 1, through Microsoft Planetary Computer.' : 'Heatmaps from Google Earth Engine.')
                + ' The latest photo is the field as the satellite saw it on the date shown. Each dot of it is 10 by 10 meters, so it looks softer than the base map around it, which is a sharper photo taken months or years ago.'
                + ' NDVI red is bare or stressed, deep green is a full, healthy canopy. Radar colors: green and yellow are dense canopy, blue and dark are water or bare soil.</p>',
            health: card('Crop health', p(H.reading) + p(H.ndviMeaning) + p(H.uniformity), ICON.leaf)
                + (s2.available ? card('How green, across the field', '<div class="sat-bars">' + [['Lowest 10%', nd.p10], ['Middle', nd.p50], ['Top 10%', nd.p90], ['Average', nd.mean]].map(([k, v]) => '<div class="sat-bar">' + esc(k) + '<span><i style="width:' + Math.max(2, Math.min(100, (Number(v) || 0) / 0.9 * 100)) + '%"></i></span><b>' + esc(v) + '</b></div>').join('') + '</div>'
                    + '<p class="sat-note mt-2">' + esc(Math.round((nd.lowShare || 0) * 100)) + ' percent of the field is clearly below the middle. Spread (std dev) ' + esc(nd.stdDev) + '.</p>'
                    + '<div class="mt-3">' + grid9(s2.zones, ndviColor, (v) => Number(v).toFixed(2)) + '</div>') : '')
                + ((H.weakZones || []).length ? card('Weak spots', '<div class="sat-list">' + H.weakZones.map((z) => '<div class="sat-item"><b>' + esc(z.where) + '</b><p>' + esc(z.what) + '</p><p><b style="display:inline">Likely:</b> ' + esc(z.likelyCause) + '</p><p><b style="display:inline">Check:</b> ' + esc(z.check) + '</p></div>').join('') + '</div>', ICON.warn) : '')
                + card('The last 90 days', chart((s.series || {}).ndvi, ['ndvi'], ['#1a9850'], (v) => Number(v).toFixed(2)) + p(H.trend), ICON.eye)
                + card('Disease risk: ' + (DZ.risk || 'unknown'), p(DZ.reading) + ((DZ.watchFor || []).length ? '<div class="sat-list mt-2">' + DZ.watchFor.map((w) => '<div class="sat-item"><b>' + esc(w.problem) + '</b><p>' + esc(w.why) + '</p><p><b style="display:inline">Sign:</b> ' + esc(w.sign) + ' · ' + esc(w.when) + '</p></div>').join('') + '</div>' : ''), ICON.warn),
            radar: card('What the radar says', p(H.radar) + (s1.available ? '<div class="sat-stats mt-3"><div class="sat-stat"><small>VV</small><b>' + esc(s1.vvDb) + ' dB</b><i>stems and ground</i></div><div class="sat-stat"><small>VH</small><b>' + esc(s1.vhDb) + ' dB</b><i>leaves and biomass</i></div><div class="sat-stat"><small>VV minus VH</small><b>' + esc(s1.vvMinusVhDb) + ' dB</b><i>narrows as canopy fills</i></div><div class="sat-stat"><small>Pass</small><b style="font-size:1rem;text-transform:capitalize">' + esc(String(s1.pass || '').toLowerCase()) + '</b><i>' + esc(s1.satellite || '') + '</i></div></div>'
                    + '<div class="mt-3">' + grid9(s1.zones, (v) => 'hsl(' + Math.max(0, Math.min(130, (Number(v) + 26) * 7)) + ' 55% 40%)', (v) => Number(v).toFixed(1) + ' dB') + '</div>' : '<p class="sat-note">No radar pass in the window.</p>'), ICON.radar)
                + card('Radar, last 90 days', chart((s.series || {}).radar, ['vv', 'vh'], ['#4c8ed9', '#f59e0b'], (v) => Number(v).toFixed(1) + ' dB') + '<p class="sat-note">Blue VV, amber VH.</p>', ICON.radar),
            threats: ((a.threats || []).length ? card('Threats to watch', '<div class="sat-list">' + a.threats.map((t) => '<div class="sat-item"><b>' + esc(t.threat) + '</b><div class="sat-tags"><span class="sat-tag lv-' + lv(t.likelihood) + '">Likely: ' + esc(t.likelihood) + '</span><span class="sat-tag lv-' + lv(t.severity) + '">Severity: ' + esc(t.severity) + '</span><span class="sat-tag">' + esc(t.when) + '</span></div><p>' + esc(t.why) + '</p><p><b style="display:inline">Do:</b> ' + esc(t.action) + '</p></div>').join('') + '</div>', ICON.warn) : '')
                + card('The next 10 days', '<div class="sat-wx">' + next.map((x, i) => {
                    const key = window.wxKeyFor ? window.wxKeyFor(x.code, false, x.tmax, x.gust ?? x.wind) : 'cloudy';
                    const dt = new Date(x.date + 'T00:00:00');
                    return '<div class="sat-wxd ' + (window.wxHue ? window.wxHue(key) : '') + '" style="--k:' + i + '"><b>' + esc(OPT && x.date === OPT.today ? 'Today' : dt.toLocaleDateString(undefined, { weekday: 'short' })) + '<small class="sat-wxd-md"> ' + esc(dt.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })) + '</small></b>'
                        + '<small>' + esc(dt.toLocaleDateString(undefined, { month: 'short', day: 'numeric' })) + '</small>' + (window.wxSky ? window.wxSky(key, 44) : '')
                        + '<em>' + esc(window.wxName ? window.wxName(key) : '') + '</em><span>' + esc(Math.round(x.tmax)) + '° / ' + esc(Math.round(x.tmin)) + '°</span>'
                        + '<i>' + esc(x.rain) + ' mm' + (x.pop != null ? ' · ' + esc(x.pop) + '%' : '') + '</i></div>';
                }).join('') + '</div>' + p(W.outlook) + p(W.rain) + p(W.heat) + p(W.wind), ICON.cloud)
                + card('ENSO and earlier years', p(CL.enso) + p(CL.history) + ((cx.climate || []).length ? '<table class="sat-table mt-2"><tr><th>Same weeks of</th><th>Rain</th><th>Days over 10 mm</th><th>Days 35°C+</th></tr>' + cx.climate.map((y) => '<tr><td>' + esc(String(y.from).slice(0, 4)) + '</td><td>' + esc(y.rain) + ' mm</td><td>' + esc(y.wetDays) + '</td><td>' + esc(y.hotDays) + '</td></tr>').join('') + '</table>' : ''), ICON.cloud),
            soil: card('The soil here', p(SO.reading) + p(SO.fit), ICON.soil)
                + ((SO.properties || []).length ? card('Soil numbers', '<table class="sat-table"><tr><th>What</th><th>Value</th><th>Meaning</th></tr>' + SO.properties.map((x) => '<tr><td>' + esc(x.name) + '</td><td>' + esc(x.value) + '</td><td>' + esc(x.meaning) + '</td></tr>').join('') + '</table><p class="sat-note mt-2">From the global soil map (ISRIC SoilGrids, 250 m), a modelled estimate. A soil test of your field is surer.</p>', ICON.soil) : '')
                + ((SO.studies || []).length ? card('Studies of soils in your area', '<div class="sat-list">' + SO.studies.map((x) => '<div class="sat-item"><b>' + esc(x.title) + '</b><p>' + esc(x.finding) + '</p></div>').join('') + '</div>', ICON.soil) : ''),
            actions: card('What to do', '<ul class="sat-actions">' + (a.actions || []).map((x) => { const pr = String(x.priority || '').toLowerCase(); return '<li><em class="' + (pr === 'now' ? 'p-now' : pr.includes('month') ? 'p-month' : '') + '">' + esc(x.priority) + '</em><span><b>' + esc(x.what) + '</b><br><span class="sat-note">' + esc(x.why) + '</span></span></li>'; }).join('') + '</ul>', ICON.check)
                + ((a.dataGaps || []).length ? card('What would make this surer', '<ul class="list-disc pl-5">' + a.dataGaps.map((g) => '<li>' + esc(g) + '</li>').join('') + '</ul>', ICON.eye) : '')
                + ((rep.webSources || []).length ? card('Additional sources of analysis', '<ul class="list-disc pl-5">' + [...new Set(rep.webSources.map((w) => w.title).filter(Boolean))].slice(0, 12).map((t) => '<li>' + esc(t) + '</li>').join('') + '</ul>', ICON.eye) : '')
                + '<p class="sat-note">Satellite readings are a guide. Walk the field to confirm before you spend on fertilizer or sprays.</p>',
        };
        $('satViewTitle').textContent = String(d.title || 'Satellite analysis').replace(/ — /g, ', ');
        $('satReport').innerHTML = '<div class="sat-top"><div class="sat-ring" style="--v:' + score + ';--c:' + ringColor + '"><div><b>' + score + '</b><small>' + esc(a.healthWord || '') + '</small></div></div>'
            + '<div><h2>' + esc(a.headline || '') + '</h2><p>' + esc(d.at ? 'Read ' + d.at : '') + '</p></div><div class="sat-when">' + when.join('') + '</div></div>'
            + '<div class="sat-rtabs" role="tablist">' + tabs.map(([k, l], i) => '<button type="button" class="sat-rtab' + (i ? '' : ' is-on') + '" data-t="' + k + '" role="tab">' + esc(l) + '</button>').join('') + '</div>'
            + tabs.map(([k], i) => '<div class="sat-pane' + (i ? '' : ' is-on') + '" data-p="' + k + '">' + panes[k] + '</div>').join('');
        rmap = null;
        const view = $('satView');
        view.hidden = false;
        document.documentElement.classList.add('sat-lock');
        void view.offsetWidth;
        view.classList.add('is-on');
        view.scrollTop = 0;
        view.style.setProperty('--sat-bar-h', view.querySelector('.sat-view-bar').offsetHeight + 'px');
    };
    $('satReport').addEventListener('click', (e) => {
        const t = e.target.closest('.sat-rtab');
        if (t) {
            document.querySelectorAll('.sat-rtab').forEach((b) => b.classList.toggle('is-on', b === t));
            document.querySelectorAll('.sat-pane').forEach((p) => p.classList.toggle('is-on', p.dataset.p === t.dataset.t));
            if (t.dataset.t === 'map') drawReportMap();
            t.scrollIntoView({ inline: 'center', block: 'nearest', behavior: reduce() ? 'auto' : 'smooth' });
            // The new tab starts at the tabs; the map takes the whole screen under them.
            const view = $('satView'), tabsEl = t.parentElement;
            const stuckAt = tabsEl.offsetTop - (view.querySelector('.sat-view-bar').offsetHeight || 0) - 4;
            if (view.scrollTop > stuckAt || (t.dataset.t === 'map' && innerWidth < 1024)) view.scrollTo({ top: Math.max(0, stuckAt), behavior: reduce() ? 'auto' : 'smooth' });
        }
        const l = e.target.closest('.sat-layers button');
        if (l && !l.disabled) setLayer(l.dataset.l);
    });
    $('satReport').addEventListener('input', (e) => { if (e.target.id === 'satOp' && rmap) { rmap.overlayMapTypes.forEach((o) => o.setOpacity(e.target.value / 100)); if (ground) ground.setOpacity(e.target.value / 100); } });
    const closeView = () => {
        const view = $('satView');
        view.classList.remove('is-on');
        document.documentElement.classList.remove('sat-lock');
        setTimeout(() => { view.hidden = true; }, reduce() ? 0 : 300);
    };
    $('satViewX').addEventListener('click', closeView);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !$('satView').hidden) closeView(); });

    let tiles = {}, pics = {}, cropBounds = null, ground = null, shots = {}, layer = 'rgb';
    const opacity = () => Number(($('satOp') || {}).value || 100) / 100;
    const overlay = (url) => new google.maps.ImageMapType({
        getTileUrl: (c, z) => url.replace('{x}', c.x).replace('{y}', c.y).replace('{z}', z),
        tileSize: new google.maps.Size(256, 256), opacity: opacity(), name: 'layer',
    });
    const setLayer = (k) => {
        layer = k;
        document.querySelectorAll('.sat-layers button').forEach((b) => b.classList.toggle('is-on', b.dataset.l === k));
        if ($('satLegend')) $('satLegend').hidden = k !== 'ndvi';
        // Which picture this is, and when it was taken.
        if ($('satShot')) { $('satShot').hidden = !shots[k]; $('satShot').innerHTML = shots[k] || ''; $('satShot').classList.toggle('is-up', k === 'ndvi'); }
        if (!rmap) return;
        rmap.overlayMapTypes.clear();
        if (ground) { ground.setMap(null); ground = null; }
        if (k === 'none') return;
        // One picture of the field and around it, smoothed by the browser (the
        // tiles are blocky 10 m squares when zoomed in this far); tiles otherwise.
        if (pics[k] && cropBounds) {
            const [w, so, e, n] = cropBounds;
            ground = new google.maps.GroundOverlay(pics[k], new google.maps.LatLngBounds({ lat: so, lng: w }, { lat: n, lng: e }), { opacity: opacity(), clickable: false });
            ground.setMap(rmap);
        } else if (tiles[k]) rmap.overlayMapTypes.push(overlay(tiles[k]));
    };
    const drawReportMap = async () => {
        if (rmap || !cur) return;
        try { await loadMaps(); } catch (err) { return; }
        const rep = cur.report || {}, s = rep.satellite || {};
        const ring = ((cur.params || {}).polygon || {}).coordinates?.[0] || [];
        const path = ring.map(([lng, lat]) => ({ lat, lng }));
        // A vector map, so it turns with two fingers like the Google Maps app (the compass puts north back up).
        rmap = new google.maps.Map($('satRMap'), { mapTypeId: 'hybrid', streetViewControl: false, fullscreenControl: false, cameraControl: false, mapTypeControl: false, clickableIcons: false, gestureHandling: 'greedy', tilt: 0, mapId: OPT.mapId || 'DEMO_MAP_ID', renderingType: 'VECTOR', headingInteractionEnabled: true, tiltInteractionEnabled: false, rotateControl: true, styles: QUIET });
        new google.maps.Polygon({ map: rmap, paths: path, strokeColor: '#f5c518', strokeWeight: 2, fillOpacity: 0, clickable: false });
        const b = new google.maps.LatLngBounds(); path.forEach((x) => b.extend(x)); rmap.fitBounds(b, 30);
        // Not closer than 17 at first: the satellite's 10 m dots blur into a smear when a small field fills the screen.
        google.maps.event.addListenerOnce(rmap, 'idle', () => { if (rmap.getZoom() > 17) rmap.setZoom(17); });
        const s2 = s.sentinel2 || {}, s1 = s.sentinel1 || {};
        tiles = Object.assign({}, s2.tiles || {}, s1.tiles || {});
        pics = Object.assign({}, s2.crops || {}, s1.crops || {});
        cropBounds = s.cropBounds || null;
        const ago = (t) => { const d = Math.round(Number((t || {}).ageDays)); return isNaN(d) ? '' : d <= 0 ? ', today' : d === 1 ? ', yesterday' : ', ' + d + ' days ago'; };
        const taken = (t) => t && t.phText ? esc(t.phText) + esc(ago(t)) : '';
        shots = {
            rgb: s2.available ? '<b>Latest satellite photo</b>' + taken(s2.time) + '<i>For analysis, not a live view</i>' : '',
            ndvi: s2.available ? '<b>Greenness from the photo of</b>' + taken(s2.time) : '',
            sar: s1.available ? '<b>Radar pass</b>' + taken(s1.time) : '',
        };
        // Earth Engine map ids last a few hours; an older report asks again (Planetary
        // Computer addresses do not expire). A report from before the smooth pictures asks for them.
        const age = (s.generatedAt && s.generatedAt.utc) ? (Date.now() - Date.parse(s.generatedAt.utc)) / 3.6e6 : 99;
        if (cur.savedId && ((age > 4 && s.source !== 'planetary') || (s.source === 'planetary' && !cropBounds))) {
            try {
                const r = await window.api(U.tiles(cur.savedId), { method: 'POST' });
                tiles = Object.assign(tiles, (r.data || {}).tiles || {});
                pics = Object.assign(pics, (r.data || {}).crops || {});
                cropBounds = (r.data || {}).cropBounds || cropBounds;
            } catch (_) {}
        }
        document.querySelectorAll('.sat-layers button').forEach((btn) => { if (btn.dataset.l !== 'none') btn.disabled = !tiles[btn.dataset.l] && !pics[btn.dataset.l]; });
        setLayer((tiles.rgb || pics.rgb) ? 'rgb' : (tiles.ndvi || pics.ndvi) ? 'ndvi' : (tiles.sar || pics.sar) ? 'sar' : 'none');
    };

    /* ---- saved ---- */
    let savedPage = 1, savedQ = '';
    const loadSaved = async (more = false) => {
        if (!more) { savedPage = 1; $('satSaved').innerHTML = ''; }
        try {
            const r = await window.api(U.list + '?page=' + savedPage + '&q=' + encodeURIComponent(savedQ));
            const rows = (r.data && r.data.rows) || [];
            $('satSaved').insertAdjacentHTML('beforeend', rows.map((x) => '<button type="button" class="sat-srow" data-id="' + x.id + '"><span class="mini" style="--v:' + (Number(x.score) || 0) + '">' + (x.score ?? '–') + '</span><span><b>' + esc(String(x.title || '').replace(/ — /g, ', ')) + '</b><small>' + esc(x.at) + (x.ndvi != null ? ' · NDVI ' + esc(x.ndvi) : '') + (x.mode === 'radar-only' ? ' · radar only' : '') + '</small></span></button>').join(''));
            $('satSavedEmpty').hidden = !!$('satSaved').children.length;
            $('satSavedMore').hidden = !r.data.hasMore;
        } catch (err) { window.toast?.(err.message, 'error'); }
    };
    $('satSaved').addEventListener('click', async (e) => {
        const b = e.target.closest('.sat-srow');
        if (!b) return;
        try { const r = await window.api(U.one(b.dataset.id)); openReport(r.data); } catch (err) { window.toast?.(err.message, 'error'); }
    });
    $('satSavedMore').addEventListener('click', () => { savedPage++; loadSaved(true); });
    let sq = null;
    $('satSavedQ').addEventListener('input', () => { clearTimeout(sq); sq = setTimeout(() => { savedQ = $('satSavedQ').value.trim(); loadSaved(); }, 300); });
    const tab = (saved) => {
        $('satTabGen').classList.toggle('is-on', !saved); $('satTabSaved').classList.toggle('is-on', saved);
        $('satTabGen').setAttribute('aria-selected', String(!saved)); $('satTabSaved').setAttribute('aria-selected', String(saved));
        $('satGen').hidden = saved; $('satSavedPane').hidden = !saved;
        if (saved) loadSaved();
    };
    $('satTabGen').addEventListener('click', () => tab(false));
    $('satTabSaved').addEventListener('click', () => tab(true));

    /* ---- boot (app.js is a module and lands after this script) ---- */
    const boot = async () => {
        try {
            const r = await window.api(U.options);
            OPT = r.data;
            quote();
            tagSay('satMethodBtn', words(OPT.methods[st.method])[0]);
            tagSay('satWaterBtn', words(OPT.water[st.water])[0]);
            soilSay(); concernsSay();
            $('satPlanted').max = OPT.today;
            show(0);
            booted = true;
            const open = new URLSearchParams(location.search).get('open');
            if (open) { const o = await window.api(U.one(open)); openReport(o.data); }
        } catch (err) { window.toast?.(err.message || 'Could not load the page.', 'error'); }
    };
    if (window.api) boot(); else window.addEventListener('load', boot, { once: true });
})();
</script>
@endpush
