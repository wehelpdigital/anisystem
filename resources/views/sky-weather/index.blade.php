@extends('layouts.app')
@section('title', 'Satellite Weather')
@section('page-title', 'Satellite Weather')
@section('page-subtitle', 'Clouds, rain and typhoons over your farm')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

@section('content')
@include('partials.tag-sheet-css')
@include('partials.weather-scenes')
<style>
    /* ---- SATELLITE WEATHER (2026-10-07, redrawn the same day) -------------
       The sky over the farm, played back and fast forwarded; storms drawn
       with their cone; Anee's reading against a lot. On a phone the map is
       the whole screen between the top bar and the tab bar, every control
       sits on it in one glass layer, and Google's own marks stay clear at
       the bottom. House curve throughout, held still under reduced motion. */
    :root { --sk-ease: cubic-bezier(.22,1,.36,1); }
    .sk-wrap { max-width: 72rem; margin: 0 auto; }
    .sk-stage { position: relative; isolation: isolate; border-radius: 1.4rem; overflow: hidden; background: #0b1220; border: 1px solid #1e293b;
        box-shadow: 0 30px 60px -40px rgb(0 0 0 / .9); }
    .sk-map { height: clamp(30rem, 74vh, 48rem); }
    @media (max-width: 1023.98px) {
        .sk-stage { margin: -1rem -1rem 0; border-radius: 0; border: 0; box-shadow: none; }
        .sk-map { height: max(30rem, calc(100svh - var(--app-head, 3.6rem) - 3.5rem - env(safe-area-inset-bottom, 0px))); }
    }
    @media (min-width: 640px) and (max-width: 1023.98px) { .sk-stage { margin: -1rem -1.5rem 0; } }
    .sk-glass { color: #e2e8f0; background: rgb(12 18 32 / .8); backdrop-filter: blur(12px) saturate(1.3); -webkit-backdrop-filter: blur(12px) saturate(1.3);
        box-shadow: 0 10px 26px -14px rgb(0 0 0 / .9), inset 0 0 0 1px rgb(255 255 255 / .07); }

    /* The top: where, and whether a storm is near. */
    .sk-top { position: absolute; left: .65rem; right: .65rem; top: .65rem; z-index: 3; display: flex; gap: .45rem; align-items: center; pointer-events: none; }
    .sk-top > * { pointer-events: auto; }
    .sk-place { display: inline-flex; align-items: center; gap: .45rem; min-width: 0; flex: 0 1 auto; padding: .55rem .85rem; border-radius: 999px; border: 0; cursor: pointer;
        font-size: .82rem; font-weight: 800; transition: transform .28s var(--sk-ease); }
    .sk-place:active { transform: scale(.97); }
    .sk-place span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sk-place svg { flex: none; width: 1rem; height: 1rem; color: #f5c518; }
    .sk-place .sk-caret { width: .8rem; height: .8rem; color: #94a3b8; transition: transform .28s var(--sk-ease); }
    .sk-place[aria-expanded="true"] .sk-caret { transform: rotate(180deg); }
    .sk-badge { margin-left: auto; flex: none; max-width: 62%; display: inline-flex; align-items: center; gap: .4rem; padding: .55rem .8rem; border-radius: 999px; font-size: .76rem; font-weight: 800;
        color: #dcfce7; background: rgb(21 128 61 / .88); box-shadow: 0 8px 20px -10px rgb(0 0 0 / .8); transition: background-color .28s var(--sk-ease), color .28s var(--sk-ease); }
    .sk-badge span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sk-badge i { flex: none; width: .5rem; height: .5rem; border-radius: 999px; background: currentColor; }
    .sk-badge.is-warn { color: #fff; background: rgb(220 38 38 / .94); animation: skPulse 1.6s ease-in-out infinite; }
    .sk-badge.is-watch { color: #1a1a1a; background: rgb(245 197 24 / .96); }
    @keyframes skPulse { 50% { box-shadow: 0 0 0 .5rem rgb(220 38 38 / .25), 0 8px 20px -10px rgb(0 0 0 / .8); } }
    @media (max-width: 479.98px) { .sk-place, .sk-badge { font-size: .74rem; padding: .5rem .7rem; } }

    /* The layers: one row of chips, swiped on a phone. The two that play
       (clouds, radar) come first; the live overlays and the storms after. */
    .sk-chips { position: absolute; left: 0; right: 0; top: 3.35rem; z-index: 3; display: flex; align-items: center; gap: .35rem; padding: 0 .65rem; overflow-x: auto;
        scrollbar-width: none; -webkit-mask-image: linear-gradient(90deg, #000 calc(100% - 1.6rem), transparent); mask-image: linear-gradient(90deg, #000 calc(100% - 1.6rem), transparent); }
    .sk-chips::-webkit-scrollbar { display: none; }
    .sk-chips::after { content: ''; flex: none; width: 1rem; }
    .sk-lay { flex: none; display: inline-flex; align-items: center; gap: .35rem; padding: .38rem .7rem .38rem .45rem; border-radius: 999px; border: 0; cursor: pointer;
        font-size: .74rem; font-weight: 800; color: #cbd5e1; transition: background-color .28s var(--sk-ease), color .28s var(--sk-ease), transform .28s var(--sk-ease); }
    .sk-lay b { display: grid; place-items: center; width: 1.45rem; height: 1.45rem; border-radius: 999px; background: rgb(255 255 255 / .1); transition: background-color .28s var(--sk-ease); }
    .sk-lay svg { width: .85rem; height: .85rem; }
    .sk-lay:active { transform: scale(.96); }
    .sk-lay.is-on { color: #0f172a; background: #f5c518; }
    .sk-lay.is-on b { background: rgb(15 23 42 / .14); }
    .sk-lay:disabled { opacity: .4; cursor: default; }
    .sk-sep { flex: none; width: 1px; height: 1.3rem; margin: 0 .15rem; background: rgb(255 255 255 / .35); }

    /* The bottom: time, the mode and the layer's strength, in one panel.
       It stands clear of Google's logo and terms underneath it. */
    .sk-bottom { position: absolute; left: .65rem; right: .65rem; bottom: 1.85rem; z-index: 3; display: grid; gap: .45rem; pointer-events: none; }
    .sk-bottom > * { pointer-events: auto; }
    .sk-bar { display: grid; grid-template-columns: auto minmax(0, 1fr); grid-template-areas: "play time" "two two"; gap: .55rem .65rem; align-items: center;
        padding: .6rem .7rem; border-radius: 1.1rem; }
    @media (min-width: 640px) { .sk-bar { grid-template-columns: auto minmax(0, 1fr) auto; grid-template-areas: "play time two"; padding: .55rem .7rem; } }
    .sk-play { grid-area: play; display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: 999px; border: 0; cursor: pointer; background: #f5c518; color: #0f172a;
        box-shadow: 0 6px 16px -6px rgb(245 197 24 / .7); transition: transform .28s var(--sk-ease); }
    .sk-play:hover { transform: scale(1.06); }
    .sk-play svg { width: 1.05rem; height: 1.05rem; }
    .sk-play .i-pause, .sk-bar.is-playing .sk-play .i-play { display: none; }
    .sk-bar.is-playing .sk-play .i-pause { display: block; }
    .sk-time { grid-area: time; min-width: 0; display: grid; gap: .3rem; }
    .sk-time b { font-size: .8rem; font-weight: 800; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sk-time input, .sk-op input { width: 100%; height: 1.2rem; margin: 0; accent-color: #f5c518; cursor: pointer; }
    .sk-two { grid-area: two; display: flex; align-items: center; gap: .7rem; min-width: 0; }
    .sk-modes { flex: none; display: flex; gap: .2rem; padding: .2rem; border-radius: 999px; background: rgb(255 255 255 / .08); }
    .sk-modes button { padding: .38rem .75rem; border-radius: 999px; border: 0; cursor: pointer; font-size: .72rem; font-weight: 800; color: #cbd5e1; background: transparent;
        transition: background-color .28s var(--sk-ease), color .28s var(--sk-ease); }
    .sk-modes button.is-on { background: #e2e8f0; color: #0f172a; }
    .sk-op { flex: 1 1 auto; min-width: 0; display: flex; align-items: center; gap: .45rem; font-size: .68rem; font-weight: 800; color: #94a3b8; }
    .sk-op svg { flex: none; width: .95rem; height: .95rem; }
    @media (min-width: 640px) { .sk-op { flex: none; width: 8.5rem; } }
    .sk-read { display: flex; gap: .35rem; flex-wrap: wrap; opacity: 0; transform: translateY(6px); pointer-events: none; transition: opacity .28s var(--sk-ease), transform .28s var(--sk-ease); }
    .sk-read.is-on { opacity: 1; transform: none; }
    .sk-read span { padding: .32rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #e2e8f0; background: rgb(12 18 32 / .82);
        backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-shadow: 0 6px 16px -8px rgb(0 0 0 / .8); }
    .sk-read span b { color: #f5c518; }

    /* The farm picker, dropped from the place pill. */
    .sk-panel { position: absolute; left: .65rem; top: 3.35rem; z-index: 5; width: min(23rem, calc(100% - 1.3rem)); max-height: calc(100% - 4.6rem); overflow-y: auto; padding: .85rem;
        border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); box-shadow: 0 24px 50px -24px rgb(0 0 0 / .8);
        opacity: 0; transform: translateY(-8px) scale(.98); transform-origin: top left; pointer-events: none; transition: opacity .28s var(--sk-ease), transform .28s var(--sk-ease); }
    .sk-panel.is-on { opacity: 1; transform: none; pointer-events: auto; }
    .sk-panel h4 { font-size: .72rem; font-weight: 800; color: var(--color-gray-500); text-transform: uppercase; letter-spacing: .06em; margin: .7rem 0 .4rem; }
    .sk-panel h4:first-child { margin-top: 0; }
    .sk-opt { display: flex; gap: .6rem; align-items: center; width: 100%; padding: .6rem .65rem; border-radius: .8rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        text-align: left; font-size: .84rem; font-weight: 700; color: var(--color-gray-800); cursor: pointer; margin-bottom: .35rem; transition: border-color .28s var(--sk-ease), background-color .28s var(--sk-ease); }
    .sk-opt:hover { border-color: var(--color-brand-600); }
    .sk-opt svg { flex: none; color: var(--color-brand-600); }
    .sk-opt small { display: block; font-size: .72rem; font-weight: 500; color: var(--color-gray-500); }
    .sk-lots { max-height: 13rem; overflow-y: auto; }

    .sk-eye { position: absolute; width: 22px; height: 22px; margin: -11px 0 0 -11px; border-radius: 999px; background: radial-gradient(circle, #fff 0 20%, #ef4444 22% 55%, transparent 57%);
        pointer-events: none; }
    .sk-eye::before, .sk-eye::after { content: ''; position: absolute; inset: -10px; border-radius: 999px; border: 2px solid rgb(239 68 68 / .8); animation: skRing 2s ease-out infinite; }
    .sk-eye::after { animation-delay: 1s; }
    @keyframes skRing { from { transform: scale(.4); opacity: 1; } to { transform: scale(1.6); opacity: 0; } }
    .sk-eye-label { position: absolute; transform: translate(14px, -50%); white-space: nowrap; padding: .2rem .45rem; border-radius: .45rem; font-size: .7rem; font-weight: 800;
        color: #fff; background: rgb(127 29 29 / .9); pointer-events: none; }
    .sk-credit { margin-top: .5rem; font-size: .7rem; line-height: 1.5; color: var(--color-gray-400); }
    @media (max-width: 1023.98px) { .sk-credit { padding: 0 .1rem; } }

    /* Below the map. */
    .sk-grid { display: grid; gap: 1rem; margin-top: 1rem; grid-template-columns: minmax(0, 1fr); align-items: start; }
    @media (min-width: 1024px) { .sk-grid { grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); } .sk-ask { position: sticky; top: calc(var(--app-head, 4rem) + 1rem); } }
    .sk-col { display: grid; gap: 1rem; min-width: 0; }
    .sk-card { border-radius: 1.2rem; background: var(--color-white); border: 1px solid var(--color-gray-200); padding: 1rem 1.05rem; min-width: 0; }
    .sk-card h3 { display: flex; align-items: center; gap: .5rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.02rem; color: var(--color-gray-900); }
    .sk-card h3 svg { flex: none; width: 1.15rem; height: 1.15rem; color: var(--color-brand-600); }
    .sk-sub { margin-top: .25rem; font-size: .82rem; color: var(--color-gray-500); line-height: 1.5; }
    .sk-storm { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .2rem .8rem; align-items: start; margin-top: .65rem; padding: .75rem .85rem; border-radius: 1rem;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    html.dark .sk-storm { background: #121a0d; border-color: #2b3a1c; }
    .sk-storm.is-calm { grid-template-columns: auto minmax(0, 1fr); align-items: center; }
    .sk-storm.is-calm > i { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: 999px; background: #dcfce7; color: #15803d; }
    html.dark .sk-storm.is-calm > i { background: #14321d; color: #86efac; }
    .sk-storm.is-calm > i svg { width: 1.2rem; height: 1.2rem; }
    .sk-storm b { font-size: .92rem; color: var(--color-gray-900); }
    .sk-storm p { font-size: .8rem; color: var(--color-gray-600); line-height: 1.5; }
    .sk-km { grid-row: span 3; text-align: right; font-family: var(--font-heading); font-weight: 800; font-size: 1.2rem; line-height: 1.1; color: var(--color-gray-900); }
    .sk-km small { display: block; font-family: var(--font-body, inherit); font-size: .66rem; font-weight: 700; color: var(--color-gray-500); }
    .sk-km.is-near { color: #dc2626; }
    /* The next 10 days: cards that go on to the next line, never a panel
       that scrolls sideways. Five across on a wide screen; two across on a
       phone, each with its sky beside the words. The skies move. */
    .sk-days { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .45rem; margin-top: .75rem; }
    .sk-days > p { grid-column: 1 / -1; }
    .sk-day { display: flex; flex-direction: column; align-items: center; gap: .12rem; min-width: 0; padding: .55rem .35rem .6rem; border-radius: 1rem; text-align: center;
        border: 1px solid var(--color-gray-100); font-size: .7rem; color: var(--color-gray-600); animation: skUp .4s var(--sk-ease) both; animation-delay: calc(var(--i) * 40ms); }
    html.dark .sk-day { border-color: #2b3a1c; }
    .sk-day.is-today { box-shadow: inset 0 0 0 2px var(--color-brand-600); }
    @keyframes skUp { from { opacity: 0; transform: translateY(8px); } }
    .sk-day > b { font-size: .78rem; color: var(--color-gray-900); }
    .sk-day > b small { display: none; font-weight: 600; color: var(--color-gray-500); }
    .sk-day > small { font-size: .64rem; color: var(--color-gray-500); }
    .sk-day .wx-sky { margin: .1rem 0; }
    .sk-day > em { font-style: normal; font-size: .7rem; font-weight: 800; color: var(--color-gray-800); line-height: 1.2; min-height: 1.7rem; display: grid; place-items: center; }
    .sk-day .t { font-size: .78rem; font-weight: 800; color: var(--color-gray-900); }
    .sk-day .t small { font-weight: 600; color: var(--color-gray-500); }
    .sk-day .r { font-size: .68rem; font-weight: 800; color: #2563eb; }
    html.dark .sk-day .r { color: #93c5fd; }
    .sk-day .g { font-size: .62rem; font-weight: 800; color: #b45309; }
    @media (max-width: 639.98px) {
        .sk-days { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sk-day { display: grid; grid-template-columns: 2.6rem minmax(0, 1fr); grid-template-areas: 'sky day' 'sky what' 'sky temp' 'sky rain' 'sky gust'; column-gap: .45rem; row-gap: 0;
            align-items: center; text-align: left; padding: .5rem .55rem; }
        .sk-day .wx-sky { grid-area: sky; width: 2.6rem !important; height: 2.6rem !important; margin: 0; }
        .sk-day > b { grid-area: day; } .sk-day > b small { display: inline; }
        .sk-day > small { display: none; }
        .sk-day > em { grid-area: what; min-height: 0; display: block; }
        .sk-day .t { grid-area: temp; } .sk-day .r { grid-area: rain; } .sk-day .g { grid-area: gust; }
    }
    /* Which lot Anee checks: a tag that opens a sheet of the lots. */
    .sk-lottag { margin-top: .75rem; }
    .sk-lottag .crop-tag-t small { display: block; font-size: .72rem; font-weight: 600; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; }
    .sk-lot-h { font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); margin: .85rem 0 .3rem; }
    .sk-lot-h:first-child { margin-top: 0; }
    .sk-lotchips { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .3rem; }
    .sk-lotchips em { font-style: normal; font-size: .68rem; font-weight: 800; padding: .14rem .5rem; border-radius: 999px; color: #2d5016; background: #e4efd4; }
    .sk-lotchips em.is-stage { color: #7c4a03; background: #fef3c7; }
    .sk-lotchips em.is-day { color: #334155; background: #e2e8f0; }
    html.dark .sk-lotchips em { color: #a5c97e; background: #1c2c10; } html.dark .sk-lotchips em.is-stage { color: #fcd34d; background: #3a2a06; } html.dark .sk-lotchips em.is-day { color: #cbd5e1; background: #1e293b; }
    .sk-lotrow .dt-row-body i { margin-top: .3rem; }
    .sk-lotsearch { position: relative; margin-bottom: .6rem; }
    .sk-lotsearch svg { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); width: 1.05rem; height: 1.05rem; color: var(--color-gray-400); pointer-events: none; }
    .sk-lotsearch .form-input { padding-left: 2.4rem; }
    .sk-lotsearch[hidden] { display: none; }
    /* The forecast clouds and rain, drawn over the map while it plays. */
    .sk-fc { position: absolute; pointer-events: none; opacity: 0; transition: opacity .4s var(--sk-ease); }
    .sk-fc.is-on { opacity: 1; }
    .sk-run { margin-top: .8rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: .5rem; padding: .9rem 1rem; border-radius: 1rem; border: 0;
        font-weight: 800; color: #1a1a1a; background: linear-gradient(135deg, #f7d23a, #f5c518); box-shadow: 0 14px 30px -18px rgb(201 158 0 / .9); cursor: pointer; transition: transform .28s var(--sk-ease); }
    .sk-run img { width: 1.6rem; height: 1.6rem; border-radius: 999px; }
    .sk-run:hover { transform: translateY(-1px); }
    .sk-run:disabled { opacity: .55; transform: none; cursor: default; }
    .sk-fine { margin-top: .45rem; font-size: .74rem; color: var(--color-gray-500); text-align: center; }
    .sk-rep { margin-top: 1rem; scroll-margin-top: calc(var(--app-head, 4rem) + .5rem); }
    .sk-rep[hidden] { display: none; }
    .sk-rhead { border-radius: 1.2rem; padding: 1.05rem 1.1rem; color: #e2e8f0; background: radial-gradient(120% 140% at 100% 0%, #1e3a8a 0%, #0f172a 60%); animation: skUp .4s var(--sk-ease) both; }
    .sk-rhead.r-high, .sk-rhead.r-severe { background: radial-gradient(120% 140% at 100% 0%, #b91c1c 0%, #450a0a 65%); }
    .sk-rhead.r-moderate { background: radial-gradient(120% 140% at 100% 0%, #b45309 0%, #422006 65%); }
    .sk-rhead.r-low { background: radial-gradient(120% 140% at 100% 0%, #15803d 0%, #052e16 65%); }
    .sk-rhead small { font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; opacity: .85; }
    .sk-rhead h3 { margin-top: .25rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.15rem; color: #fff; line-height: 1.3; }
    .sk-rtabs { display: flex; gap: .3rem; margin-top: .8rem; overflow-x: auto; scrollbar-width: none; }
    .sk-rtabs::-webkit-scrollbar { display: none; }
    .sk-rtab { flex: none; padding: .45rem .85rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--color-gray-600); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; transition: background-color .28s var(--sk-ease), color .28s var(--sk-ease), border-color .28s var(--sk-ease); }
    .sk-rtab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .sk-pane { display: none; margin-top: .7rem; }
    .sk-pane.is-on { display: block; animation: skUp .34s var(--sk-ease) both; }
    .sk-pane p, .sk-pane li { font-size: .86rem; line-height: 1.6; color: var(--color-gray-700); }
    .sk-pane p + p { margin-top: .5rem; }
    .sk-acts li { display: flex; gap: .6rem; padding: .6rem 0; border-bottom: 1px dashed var(--color-gray-200); }
    .sk-acts li:last-child { border-bottom: 0; }
    .sk-acts em { flex: none; height: fit-content; font-style: normal; font-size: .66rem; font-weight: 800; text-transform: uppercase; padding: .2rem .5rem; border-radius: 999px; background: #fff1c2; color: #8a5a00; }
    /* The column is minmax(0, 1fr): a long headline is cut with an ellipsis
       inside the card instead of pushing the row out over its edge. */
    .sk-saved { display: grid; grid-template-columns: minmax(0, 1fr); gap: .45rem; margin-top: .6rem; }
    .sk-srow { display: flex; gap: .6rem; align-items: center; width: 100%; min-width: 0; padding: .65rem .75rem; border-radius: .9rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        text-align: left; cursor: pointer; font-size: .82rem; color: var(--color-gray-800); transition: transform .28s var(--sk-ease), border-color .28s var(--sk-ease); }
    .sk-srow:hover { transform: translateY(-1px); border-color: var(--color-brand-600); }
    .sk-srow i { flex: none; width: .65rem; height: .65rem; border-radius: 999px; background: #22c55e; }
    .sk-srow i.r-moderate { background: #f59e0b; } .sk-srow i.r-high, .sk-srow i.r-severe { background: #ef4444; }
    .sk-srow span { min-width: 0; flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 700; }
    .sk-srow small { flex: none; max-width: 40%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--color-gray-500); }
    @media (prefers-reduced-motion: reduce) {
        .sk-badge.is-warn, .sk-eye::before, .sk-eye::after, .sk-pane.is-on, .sk-day, .sk-rhead { animation: none; }
        .sk-panel, .sk-lay, .sk-run, .sk-play, .sk-read, .sk-place, .sk-srow { transition: none; }
    }
    html.sm-still .sk-badge.is-warn, html.sm-still .sk-eye::before, html.sm-still .sk-eye::after, html.sm-still .sk-day { animation: none; }
</style>

<div class="sk-wrap">
    <div class="sk-stage">
        <div class="sk-map" id="skMap"></div>
        <div class="sk-top">
            <button type="button" class="sk-place sk-glass" id="skPlaceBtn" aria-expanded="false" aria-controls="skPanel">
                <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.2-7-11a7 7 0 0114 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                <span id="skPlaceName">Choose your farm</span>
                <svg class="sk-caret" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <span class="sk-badge" id="skBadge" role="status"><i></i><span id="skBadgeText">Checking for storms…</span></span>
        </div>
        <div class="sk-chips" id="skLayers" role="toolbar" aria-label="Map layers">
            <button type="button" class="sk-lay sk-glass is-on" data-anim="clouds" title="Satellite clouds, the last three hours"><b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 18a4 4 0 01-.6-8A6 6 0 0118 8.5 4.5 4.5 0 0117.5 18H7z"/></svg></b><span>Clouds</span></button>
            <button type="button" class="sk-lay sk-glass" data-anim="radar" title="Rain radar, the last hours"><b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 12l6-6M4.9 19.1a10 10 0 010-14.2M19.1 4.9a10 10 0 010 14.2M8 16a5.6 5.6 0 010-8M16 8a5.6 5.6 0 010 8"/></svg></b><span>Rain radar</span></button>
            <i class="sk-sep" aria-hidden="true"></i>
            <button type="button" class="sk-lay sk-glass is-on" data-storms title="Typhoon tracks and cones"><b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 3c5 0 8 3 8 6-3-3-8-3-11 0 5-1 9 2 9 6 0 4-4 6-8 6 2-1 3-3 3-5-2 2-6 2-8 0 3 0 5-2 5-4-3 2-7 0-7-3 2 1 4 1 5 0-3-1-4-4-3-6 1 2 3 3 5 3-2-1-2-3-1-5z"/></svg></b><span>Typhoons</span></button>
            <button type="button" class="sk-lay sk-glass" data-owm="precipitation_new" title="Rain falling now"><b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M8 19l-1 2M12 19l-1 2M16 19l-1 2M7 15a4 4 0 01-.6-8A6 6 0 0118 5.5 4.5 4.5 0 0117.5 15H7z"/></svg></b><span>Rain now</span></button>
            <button type="button" class="sk-lay sk-glass" data-owm="clouds_new" title="Cloud cover now"><b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z"/></svg></b><span>Cloud cover</span></button>
            <button type="button" class="sk-lay sk-glass" data-owm="wind_new" title="Wind now"><b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 8h11a3 3 0 10-3-3M3 12h16a3 3 0 11-3 3M3 16h8"/></svg></b><span>Wind</span></button>
        </div>
        <div class="sk-panel" id="skPanel">
            <h4>Where is your farm?</h4>
            <button type="button" class="sk-opt" id="skHere"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg><span>Use my current location<small>From this phone's GPS</small></span></button>
            <div class="flex gap-2"><input type="text" id="skQ" class="form-input flex-1 min-w-0" placeholder="Town or province" autocomplete="off" enterkeyhint="search"><button type="button" class="btn btn-white" id="skFind">Find</button></div>
            <div id="skFound" class="mt-2"></div>
            <h4>Or one of your lots</h4>
            <div class="sk-lots" id="skLots"></div>
        </div>
        <div class="sk-bottom">
            <div class="sk-read" id="skRead" aria-live="polite"></div>
            <div class="sk-bar sk-glass" id="skBar">
                <button type="button" class="sk-play" id="skPlay" aria-label="Play">
                    <svg class="i-play" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg>
                    <svg class="i-pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h3.2v14H7zM13.8 5H17v14h-3.2z"/></svg>
                </button>
                <div class="sk-time"><b id="skTimeLabel">Loading the sky…</b><input type="range" id="skSlider" min="0" max="17" value="17" aria-label="Move through time"></div>
                <div class="sk-two">
                    <div class="sk-modes" role="tablist" aria-label="Past or forecast"><button type="button" class="is-on" data-mode="past">Past 3 h</button><button type="button" data-mode="future">Forecast</button></div>
                    <label class="sk-op" title="How strong the layer shows"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 4a8 8 0 010 16z" fill="currentColor"/></svg><input type="range" id="skOp" min="10" max="100" value="65" aria-label="Layer opacity"></label>
                </div>
            </div>
        </div>
    </div>
    <p class="sk-credit">Clouds: Himawari-9 infrared from NASA GIBS. Rain radar: RainViewer. Live layers: OpenWeatherMap. Typhoon tracks: GDACS. Forecast, and the forecast clouds and rain the map plays: the Open-Meteo model. Times are Philippine time.</p>

    <div class="sk-grid">
        <div class="sk-col">
            <div class="sk-card">
                <h3><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 3c5 0 8 3 8 6-3-3-8-3-11 0 5-1 9 2 9 6 0 4-4 6-8 6 2-1 3-3 3-5-2 2-6 2-8 0 3 0 5-2 5-4-3 2-7 0-7-3 2 1 4 1 5 0-3-1-4-4-3-6 1 2 3 3 5 3-2-1-2-3-1-5z"/></svg>Typhoons near your farm</h3>
                <p class="sk-sub">Distance from the eye to your farm, measured on the map. Inside 300 km is a direct threat.</p>
                <div id="skStorms"></div>
            </div>
            <div class="sk-card">
                <h3><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>The next 10 days at your farm</h3>
                <p class="sk-sub">The sky, the high and the low, the rain expected and its chance.</p>
                <div class="sk-days" id="skDays"><p class="sk-sub">Choose your farm to see its forecast.</p></div>
            </div>
        </div>
        <div class="sk-card sk-ask">
            <h3><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>Ask Anee what it means for your crop</h3>
            <p class="sk-sub">Anee reads the storms, the forecast, ENSO and the past five years, then checks them against one of your lots: its crop, age and stage.</p>
            <button type="button" class="crop-tag sk-lottag" id="skLotBtn">
                <span class="crop-tag-e" id="skLotIcon">🗺️</span>
                <span class="crop-tag-t" id="skLotNow">No lot, my farm in general</span>
                <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <button type="button" class="sk-run" id="skRun"><img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt=""><span id="skRunSays">Ask Anee</span></button>
            <p class="sk-fine" id="skFine"></p>
            <div class="mt-4">
                <b class="text-sm text-gray-700">Saved readings</b>
                <div class="sk-saved" id="skSaved"></div>
            </div>
        </div>
    </div>
    <div class="sk-rep" id="skRep" hidden></div>
    @include('sm.partials.anee-wait')
</div>

{{-- Which lot Anee checks the sky against. --}}
<div class="sheet hidden" id="skLotSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Which lot should Anee check?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="form-hint mt-0 mb-3">Anee weighs the storms and the rain against the lot's crop and how far along it is.</p>
        <div class="sk-lotsearch" id="skLotSearchBox" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" id="skLotQ" class="form-input" autocomplete="off" placeholder="Find a lot or a crop">
        </div>
        <div class="dt-rows" id="skLotRows"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const U = {
        options: @json(route('sky.options')), frames: @json(route('sky.frames')), storms: @json(route('sky.storms')), forecast: @json(route('sky.forecast')),
        tile: @json(url('/app/sky-weather/tile')), generate: @json(route('sky.generate')), job: (id) => @json(url('/app/sky-weather/job')) + '/' + id,
        list: @json(route('sky.list')), one: (id) => @json(url('/app/sky-weather/one')) + '/' + id, places: @json(route('sat.places')), grid: @json(route('sky.grid')),
    };
    const KEY = 'anee.skyPlace';
    let OPT = null, map = null, farm = null, farmMark = null, ring = null, FR = { clouds: [], radar: [] }, STORMS = [], FC = { hours: [], days: [] };
    let mode = 'past', anim = 'clouds', owm = null, frameIdx = 17, playing = null, opacity = 0.65;
    const animLayers = { clouds: [], radar: [] };
    let owmLayer = null, stormShapes = [], eyeOverlays = [], movingEye = null;

    /* A day's sky by its WMO weather code (Open-Meteo), as a small picture and a word. */
    const WX = {
        sun: '<svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4" fill="#fde68a"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>',
        part: '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="8" r="3.2" fill="#fde68a" stroke="#f59e0b"/><path d="M8 2.5v1.2M2.5 8h1.2M4.1 4.1l.9.9M11.9 4.1l-.9.9" stroke="#f59e0b"/><path d="M8.5 19a3.5 3.5 0 01-.4-7 5 5 0 019.6 1.2A3 3 0 0117.5 19h-9z" fill="#e2e8f0" stroke="#94a3b8"/></svg>',
        cloud: '<svg viewBox="0 0 24 24" fill="#e2e8f0" stroke="#94a3b8" stroke-width="1.8" stroke-linejoin="round"><path d="M7 18a4 4 0 01-.6-8A6 6 0 0118 8.5 4.5 4.5 0 0117.5 18H7z"/></svg>',
        rain: '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 15a4 4 0 01-.6-8A6 6 0 0118 5.5 4.5 4.5 0 0117.5 15H7z" fill="#cbd5e1" stroke="#64748b"/><path d="M8 18l-1 2.5M12 18l-1 2.5M16 18l-1 2.5" stroke="#3b82f6" stroke-width="2"/></svg>',
        storm: '<svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 15a4 4 0 01-.6-8A6 6 0 0118 5.5 4.5 4.5 0 0117.5 15H7z" fill="#94a3b8" stroke="#475569"/><path d="M12.5 14l-2.5 4h3l-2 4" stroke="#f59e0b" stroke-width="2"/></svg>',
        fog: '<svg viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"><path d="M4 9h16M3 13h18M5 17h14"/></svg>',
    };
    const wxKind = (code, rain) => {
        const c = Number(code);
        if (c >= 95) return 'storm';
        if ((c >= 51 && c <= 67) || (c >= 80 && c <= 82) || rain >= 2) return 'rain';
        if (c === 45 || c === 48) return 'fog';
        if (c === 3) return 'cloud';
        if (c === 1 || c === 2) return 'part';
        return Number.isFinite(c) ? 'sun' : (rain >= 0.5 ? 'rain' : 'part');
    };
    const wxIcon = (code, rain) => WX[wxKind(code, rain)];
    const wxWord = (code, rain) => ({ sun: 'Clear', part: 'Partly cloudy', cloud: 'Cloudy', rain: 'Rain', storm: 'Thunderstorms', fog: 'Fog' })[wxKind(code, rain)];

    /* Haversine, the great circle distance in kilometres. */
    const km = (a, b) => {
        const R = 6371, rad = (d) => d * Math.PI / 180;
        const dl = rad(b.lat - a.lat), dn = rad(b.lng - a.lng);
        const h = Math.sin(dl / 2) ** 2 + Math.cos(rad(a.lat)) * Math.cos(rad(b.lat)) * Math.sin(dn / 2) ** 2;
        return 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
    };

    /* ---- Google Maps ---- */
    const loadMaps = () => new Promise((resolve, reject) => {
        if (window.google && window.google.maps) return resolve(window.google.maps);
        const cb = '__skMaps' + Date.now();
        window[cb] = () => resolve(window.google.maps);
        const s = document.createElement('script');
        s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(OPT.mapsKey) + '&libraries=geometry&callback=' + cb;
        s.async = true; s.onerror = () => reject(new Error('The map could not load.'));
        document.head.appendChild(s);
    });

    /* Tiles that exist only up to a zoom (Himawari 6, radar 7): deeper in,
       the parent tile is cut and scaled, so the clouds stay on screen. */
    class ScaledTiles {
        constructor(url, nativeMax) { this.url = url; this.nativeMax = nativeMax; this.tileSize = new google.maps.Size(256, 256); this.maxZoom = 12; this.opacity = 0; this.tiles = new Set(); }
        getTile(c, z, doc) {
            const d = Math.max(0, z - this.nativeMax), zz = z - d, n = 1 << d;
            const div = doc.createElement('div');
            div.style.cssText = 'width:256px;height:256px;overflow:hidden;position:relative;transition:opacity .28s;opacity:' + this.opacity;
            const max = 1 << zz;
            const px = ((c.x >> d) % max + max) % max, py = c.y >> d;
            if (py < 0 || py >= max) return div;
            const img = doc.createElement('img');
            img.src = this.url.replace('{z}', zz).replace('{x}', px).replace('{y}', py);
            img.style.cssText = 'position:absolute;width:' + (256 * n) + 'px;height:' + (256 * n) + 'px;left:' + (-(((c.x % n) + n) % n) * 256) + 'px;top:' + (-(((c.y % n) + n) % n) * 256) + 'px;';
            img.referrerPolicy = 'no-referrer';
            img.onerror = () => { img.remove(); };
            div.appendChild(img);
            this.tiles.add(div);
            return div;
        }
        releaseTile(t) { this.tiles.delete(t); }
        setOpacity(o) { this.opacity = o; this.tiles.forEach((t) => { t.style.opacity = o; }); }
    }

    const initMap = async () => {
        await loadMaps();
        map = new google.maps.Map($('skMap'), {
            center: farm || { lat: 12.6, lng: 122.3 }, zoom: farm ? 6 : 5, mapTypeId: 'hybrid', streetViewControl: false, fullscreenControl: false, mapTypeControl: false,
            cameraControl: false, clickableIcons: false, gestureHandling: 'greedy', tilt: 0,
            styles: [{ featureType: 'poi', stylers: [{ visibility: 'off' }] }, { featureType: 'transit', stylers: [{ visibility: 'off' }] }],
        });
        map.addListener('click', () => panel(false));
    };

    /* ---- the farm ---- */
    const setFarm = (p, fly = true) => {
        farm = { lat: Number(p.lat), lng: Number(p.lng), label: p.label || 'My farm' };
        try { localStorage.setItem(KEY, JSON.stringify(farm)); } catch (_) {}
        $('skPlaceName').textContent = farm.label;
        panel(false);
        if (map) {
            farmMark?.setMap(null); ring?.setMap(null);
            farmMark = new google.maps.Marker({ position: farm, map, title: farm.label, zIndex: 50,
                icon: { path: 'M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7z', fillColor: '#f5c518', fillOpacity: 1, strokeColor: '#1f2937', strokeWeight: 1.5, scale: 1.6, anchor: new google.maps.Point(12, 22) } });
            ring = new google.maps.Circle({ map, center: farm, radius: 300000, strokeColor: '#f5c518', strokeOpacity: .7, strokeWeight: 1.5, fillOpacity: 0, clickable: false });
            if (fly) { map.panTo(farm); map.setZoom(6); }
        }
        loadStorms();
        loadForecast();
        if (mode === 'future') loadGrid().then(() => future(Number($('skSlider').value)));
    };
    const panel = (on) => { $('skPanel').classList.toggle('is-on', on); $('skPlaceBtn').setAttribute('aria-expanded', String(on)); };
    $('skPlaceBtn').addEventListener('click', () => panel(!$('skPanel').classList.contains('is-on')));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && $('skPanel').classList.contains('is-on')) panel(false); });
    $('skHere').addEventListener('click', () => {
        if (!navigator.geolocation) return window.toast?.('This phone cannot share its location.', 'error');
        navigator.geolocation.getCurrentPosition((pos) => setFarm({ lat: pos.coords.latitude, lng: pos.coords.longitude, label: 'My location' }),
            () => window.toast?.('Location was not shared. Search for the town instead.', 'error'), { enableHighAccuracy: false, timeout: 12000 });
    });
    const find = async () => {
        const q = $('skQ').value.trim();
        if (q.length < 2) return;
        $('skFound').innerHTML = '<p class="text-xs text-gray-400">Looking…</p>';
        try {
            const r = await window.api(U.places + '?q=' + encodeURIComponent(q));
            const list = (r.data && r.data.places) || [];
            $('skFound').innerHTML = list.map((p, i) => '<button type="button" class="sk-opt" data-i="' + i + '"><span>' + esc(p.label || p.name) + '</span></button>').join('') || '<p class="text-xs text-gray-400">No place by that name.</p>';
            $('skFound').onclick = (e) => { const b = e.target.closest('.sk-opt'); if (b) { const p = list[Number(b.dataset.i)]; setFarm({ lat: p.lat, lng: p.lng, label: p.label || p.name }); } };
        } catch (err) { $('skFound').innerHTML = '<p class="text-xs text-gray-400">' + esc(err.message) + '</p>'; }
    };
    $('skFind').addEventListener('click', find);
    $('skQ').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); find(); } });

    /* ---- layers ---- */
    const setOpacity = (o) => {
        opacity = o;
        $('skOp').value = Math.round(o * 100);
        if (owmLayer) owmLayer.setOpacity(o);
        paintFrame();
        if (fcOver && mode === 'future') fcOver.paint(fcT);
    };
    $('skOp').addEventListener('input', (e) => setOpacity(e.target.value / 100));
    const ensureAnim = (kind) => {
        if (animLayers[kind].length || !FR[kind].length) return;
        animLayers[kind] = FR[kind].map((f) => { const l = new ScaledTiles(f.url, kind === 'clouds' ? 6 : 7); map.overlayMapTypes.push(l); return l; });
    };
    const paintFrame = () => {
        Object.entries(animLayers).forEach(([k, list]) => list.forEach((l, i) => l.setOpacity(mode === 'past' && k === anim && i === frameIdx ? opacity : 0)));
        if (fcOver) { fcOver.show(mode === 'future'); if (mode === 'future') fcOver.paint(fcT); }
        const f = FR[anim][frameIdx];
        if (mode === 'past') $('skTimeLabel').textContent = f ? (anim === 'clouds' ? 'Clouds · ' : 'Rain radar · ') + f.ph : 'No pictures right now';
    };
    const setOwm = (layer) => {
        if (owmLayer) { const i = map.overlayMapTypes.getArray().indexOf(owmLayer); if (i >= 0) map.overlayMapTypes.removeAt(i); owmLayer = null; }
        owm = layer;
        if (layer && OPT.owm) {
            owmLayer = new google.maps.ImageMapType({ getTileUrl: (c, z) => { const n = 1 << z; return U.tile + '/' + layer + '/' + z + '/' + (((c.x % n) + n) % n) + '/' + c.y; }, tileSize: new google.maps.Size(256, 256), opacity, maxZoom: 12, name: layer });
            map.overlayMapTypes.push(owmLayer);
        }
    };
    $('skLayers').addEventListener('click', (e) => {
        const b = e.target.closest('.sk-lay');
        if (!b || b.disabled || !map) return;
        if (b.dataset.anim) {
            anim = b.dataset.anim;
            ensureAnim(anim);
            frameIdx = Math.max(0, FR[anim].length - 1);
            $('skSlider').max = Math.max(0, FR[anim].length - 1);
            if (mode === 'past') $('skSlider').value = frameIdx;
            document.querySelectorAll('.sk-lay[data-anim]').forEach((x) => x.classList.toggle('is-on', x === b));
            paintFrame();
        } else if (b.dataset.owm) {
            const on = owm !== b.dataset.owm;
            setOwm(on ? b.dataset.owm : null);
            document.querySelectorAll('.sk-lay[data-owm]').forEach((x) => x.classList.toggle('is-on', on && x === b));
        } else if ('storms' in b.dataset) {
            const on = !b.classList.contains('is-on');
            b.classList.toggle('is-on', on);
            stormShapes.forEach((s) => s.setMap(on ? map : null));
            eyeOverlays.forEach((o) => o.setMap(on ? map : null));
        }
    });

    /* ---- the forecast clouds and rain, played on the map ----
       Cloud cover and rain from the Open-Meteo model over a grid around the
       farm, every 3 hours for 5 days (sky.grid). Between two forecasts the
       picture is blended, the field is laid out on the map's own projection,
       and a little texture drifts through it, so it reads as weather moving
       across the islands rather than squares. */
    let FG = null, fgKey = '', fcOver = null, fcT = null, NOISE = null;
    const FW = 8;
    const loadGrid = async () => {
        if (!farm) return null;
        const key = Math.round(farm.lat / 2) + ',' + Math.round(farm.lng / 2);
        if (FG && fgKey === key) return FG;
        try {
            const r = await window.api(U.grid + '?lat=' + farm.lat + '&lng=' + farm.lng);
            const d = r.data || {};
            FG = (d.cloud || []).length ? Object.assign(d, { startMs: Date.parse(d.start), frames: d.cloud.length }) : null;
        } catch (_) { FG = null; }
        fgKey = key;
        return FG;
    };
    const makeNoise = (w, h) => {
        // Two octaves of value noise, smooth enough to look like cloud texture.
        const out = new Float32Array(w * h);
        [[6, 0.65], [14, 0.35]].forEach(([n, amp]) => {
            const gw = n + 1, gh = Math.ceil(n * h / w) + 1, g = Array.from({ length: gw * gh }, () => Math.random());
            for (let y = 0; y < h; y++) for (let x = 0; x < w; x++) {
                const gx = x / w * n, gy = y / h * (gh - 1), x0 = Math.floor(gx), y0 = Math.floor(gy), fx = gx - x0, fy = gy - y0;
                const a = g[y0 * gw + x0], b = g[y0 * gw + Math.min(gw - 1, x0 + 1)], c = g[Math.min(gh - 1, y0 + 1) * gw + x0], e = g[Math.min(gh - 1, y0 + 1) * gw + Math.min(gw - 1, x0 + 1)];
                const sx = fx * fx * (3 - 2 * fx), sy = fy * fy * (3 - 2 * fy);
                out[y * w + x] += amp * ((a * (1 - sx) + b * sx) * (1 - sy) + (c * (1 - sx) + e * sx) * sy);
            }
        });
        return out;
    };
    const merc = (lat) => Math.log(Math.tan(Math.PI / 4 + lat * Math.PI / 360));
    const unmerc = (m) => (2 * Math.atan(Math.exp(m)) - Math.PI / 2) * 180 / Math.PI;
    const RAMP = [[0, [96, 165, 250]], [0.35, [52, 211, 153]], [0.65, [250, 204, 21]], [1, [239, 68, 68]]];
    const rampAt = (v) => { for (let i = 1; i < RAMP.length; i++) { if (v <= RAMP[i][0]) { const [a, ca] = RAMP[i - 1], [b, cb] = RAMP[i], t = (v - a) / (b - a); return ca.map((x, j) => x + (cb[j] - x) * t); } } return RAMP[RAMP.length - 1][1]; };
    const small = document.createElement('canvas');
    const fieldAt = (t) => {
        const cols = FG.cols, rows = FG.rows, w = cols * FW, h = rows * FW;
        if (small.width !== w) { small.width = w; small.height = h; NOISE = makeNoise(w, h); }
        const k = Math.max(0, Math.min(FG.frames - 1, (t - FG.startMs) / (FG.stepHours * 3.6e6)));
        const k0 = Math.floor(k), k1 = Math.min(FG.frames - 1, k0 + 1), a = k - k0;
        const C0 = FG.cloud[k0], C1 = FG.cloud[k1], R0 = FG.rain[k0], R1 = FG.rain[k1];
        const half = FG.step / 2, S = FG.lat0 - half, N = FG.lat0 + (rows - 1) * FG.step + half, Wd = FG.lng0 - half, E = FG.lng0 + (cols - 1) * FG.step + half;
        const mN = merc(N), mS = merc(S), drift = Math.round((t / 3.6e6) * 0.7) % w;
        const sample = (A, B, gr, gc) => {
            const r0 = Math.floor(gr), c0 = Math.floor(gc), r1 = Math.min(rows - 1, r0 + 1), c1 = Math.min(cols - 1, c0 + 1), fr = gr - r0, fc = gc - c0;
            const v = (arr) => (arr[r0 * cols + c0] * (1 - fc) + arr[r0 * cols + c1] * fc) * (1 - fr) + (arr[r1 * cols + c0] * (1 - fc) + arr[r1 * cols + c1] * fc) * fr;
            return v(A) * (1 - a) + v(B) * a;
        };
        const ctx = small.getContext('2d'), img = ctx.createImageData(w, h), px = img.data;
        const clouds = anim !== 'radar';
        for (let y = 0; y < h; y++) {
            const lat = unmerc(mN + (mS - mN) * (y + 0.5) / h);
            const gr = Math.max(0, Math.min(rows - 1, (lat - FG.lat0) / FG.step));
            for (let x = 0; x < w; x++) {
                const lng = Wd + (E - Wd) * (x + 0.5) / w;
                const gc = Math.max(0, Math.min(cols - 1, (lng - FG.lng0) / FG.step));
                const nz = NOISE[y * w + ((x + drift) % w)];
                let r = 0, g = 0, b = 0, al = 0;
                if (clouds) {
                    const c = sample(C0, C1, gr, gc) / 100;
                    al = Math.min(1, Math.pow(c, 1.35) * (0.55 + 0.75 * nz)) * 0.88;
                    r = 238; g = 242; b = 247;
                }
                const mm = sample(R0, R1, gr, gc);
                if (mm >= 0.3) {
                    const iv = Math.min(1, Math.log1p(mm) / Math.log1p(25)), [rr, rg, rb] = rampAt(iv), ra = Math.min(0.9, 0.3 + 0.6 * iv) * (0.8 + 0.4 * nz);
                    const out = ra + al * (1 - ra);
                    r = (rr * ra + r * al * (1 - ra)) / out; g = (rg * ra + g * al * (1 - ra)) / out; b = (rb * ra + b * al * (1 - ra)) / out; al = out;
                }
                const i = (y * w + x) * 4;
                px[i] = r; px[i + 1] = g; px[i + 2] = b; px[i + 3] = Math.round(al * 255);
            }
        }
        ctx.putImageData(img, 0, 0);
        return { canvas: small, N, S, W: Wd, E };
    };
    let FcOverlay = null;
    const defineFc = () => {
        FcOverlay = class extends google.maps.OverlayView {
            onAdd() { this.cv = document.createElement('canvas'); this.cv.className = 'sk-fc'; this.getPanes().overlayLayer.appendChild(this.cv); this.show(this.on); }
            onRemove() { this.cv?.remove(); this.cv = null; }
            show(on) { this.on = on; if (this.cv) this.cv.classList.toggle('is-on', !!on && !!FG); }
            draw() { this.paint(fcT); }
            paint(t) {
                if (!this.cv || !FG || t == null || !this.on) { this.show(this.on); return; }
                const proj = this.getProjection();
                if (!proj) return;
                const div = map.getDiv(), w = div.offsetWidth, h = div.offsetHeight, dpr = Math.min(2, window.devicePixelRatio || 1);
                const d0 = proj.fromLatLngToDivPixel(proj.fromContainerPixelToLatLng(new google.maps.Point(0, 0)));
                this.cv.style.left = d0.x + 'px'; this.cv.style.top = d0.y + 'px';
                if (this.cv.width !== Math.round(w * dpr) || this.cv.height !== Math.round(h * dpr)) { this.cv.width = Math.round(w * dpr); this.cv.height = Math.round(h * dpr); this.cv.style.width = w + 'px'; this.cv.style.height = h + 'px'; }
                const f = fieldAt(t), ctx = this.cv.getContext('2d');
                const nw = proj.fromLatLngToContainerPixel(new google.maps.LatLng(f.N, f.W)), se = proj.fromLatLngToContainerPixel(new google.maps.LatLng(f.S, f.E));
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
                ctx.clearRect(0, 0, w, h);
                ctx.globalAlpha = Math.min(1, opacity + 0.15);
                ctx.imageSmoothingEnabled = true; ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(f.canvas, nw.x, nw.y, se.x - nw.x, se.y - nw.y);
                this.show(true);
            }
        };
        fcOver = new FcOverlay();
        fcOver.on = false;
        fcOver.setMap(map);
    };

    /* ---- time: the past three hours, or the storm's forecast ---- */
    const futureSpan = () => {
        const pts = STORMS.flatMap((s) => (s.points || []).filter((p) => p.forecast && p.utc).map((p) => Date.parse(p.utc)));
        const hours = FC.hours.length ? FC.hours.length : 72;
        const end = Math.max(Date.now() + hours * 3.6e6 * 0.98, ...pts);
        return { start: Date.now(), end };
    };
    const setMode = (m) => {
        mode = m;
        document.querySelectorAll('.sk-modes button').forEach((b) => b.classList.toggle('is-on', b.dataset.mode === m));
        stop();
        if (m === 'past') {
            $('skSlider').max = Math.max(0, FR[anim].length - 1);
            $('skSlider').value = frameIdx = Math.max(0, FR[anim].length - 1);
            $('skRead').classList.remove('is-on');
            moveEye(null);
        } else {
            $('skSlider').max = 100; $('skSlider').value = 0;
            $('skRead').classList.add('is-on');
            future(0);
            // The forecast clouds come in once their grid has landed.
            loadGrid().then(() => { if (mode === 'future') { future(Number($('skSlider').value)); paintFrame(); } });
        }
        paintFrame();
    };
    document.querySelectorAll('.sk-modes button').forEach((b) => b.addEventListener('click', () => setMode(b.dataset.mode)));
    const at = (t) => {
        // The storm's position at time t, between its forecast points.
        const s = STORMS.find((x) => x.current && (x.points || []).some((p) => p.forecast)) || STORMS.find((x) => (x.points || []).length);
        if (!s) return null;
        const pts = (s.points || []).filter((p) => p.utc).map((p) => ({ ...p, t: Date.parse(p.utc) }));
        if (!pts.length) return null;
        if (t <= pts[0].t) return { s, lat: pts[0].lat, lng: pts[0].lng };
        for (let i = 1; i < pts.length; i++) {
            if (t <= pts[i].t) { const r = (t - pts[i - 1].t) / (pts[i].t - pts[i - 1].t); return { s, lat: pts[i - 1].lat + (pts[i].lat - pts[i - 1].lat) * r, lng: pts[i - 1].lng + (pts[i].lng - pts[i - 1].lng) * r }; }
        }
        return null;
    };
    const future = (v) => {
        const { start, end } = futureSpan();
        const t = start + (end - start) * (v / 100);
        fcT = t;
        if (fcOver) fcOver.paint(t);
        const d = new Date(t);
        $('skTimeLabel').textContent = 'Forecast · ' + d.toLocaleString('en-PH', { timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
        const h = FC.hours.length ? FC.hours.reduce((best, x) => Math.abs(Date.parse(x.t + ':00+08:00') - t) < Math.abs(Date.parse(best.t + ':00+08:00') - t) ? x : best) : null;
        const pos = at(t);
        moveEye(pos);
        const bits = [];
        if (h) bits.push('<span>Rain <b>' + esc(h.rain ?? 0) + ' mm</b></span>', '<span>Gusts <b>' + esc(Math.round(h.gust ?? 0)) + ' km/h</b></span>', '<span>Cloud <b>' + esc(h.cloud ?? 0) + '%</b></span>');
        if (pos && farm) bits.push('<span>' + esc(pos.s.name) + ' <b>' + Math.round(km(farm, pos)) + ' km</b> away</span>');
        if (FG && t > FG.startMs + (FG.frames - 1) * FG.stepHours * 3.6e6) bits.push('<span>The forecast clouds end here</span>');
        $('skRead').innerHTML = bits.join('');
    };
    const moveEye = (pos) => {
        if (!map) return;
        if (!pos) { movingEye?.setMap(null); movingEye = null; return; }
        if (!movingEye) movingEye = new EyeOverlay(pos, pos.s.name + ' (forecast)');
        movingEye.setMap(map);
        movingEye.move(pos);
    };
    $('skSlider').addEventListener('input', (e) => {
        if (mode === 'past') { frameIdx = Number(e.target.value); paintFrame(); } else future(Number(e.target.value));
    });
    const stop = () => { clearInterval(playing); playing = null; $('skBar').classList.remove('is-playing'); };
    $('skPlay').addEventListener('click', () => {
        if (playing) return stop();
        if (mode === 'past') ensureAnim(anim);
        $('skBar').classList.add('is-playing');
        playing = setInterval(() => {
            const max = Number($('skSlider').max);
            let v = Number($('skSlider').value) + (mode === 'past' ? 1 : 1);
            if (v > max) v = 0;
            $('skSlider').value = v;
            if (mode === 'past') { frameIdx = v; paintFrame(); } else future(v);
        }, reduce() ? 1200 : (mode === 'past' ? 450 : 160));
    });

    /* ---- storms ---- */
    let EyeOverlay = null;
    const defineEye = () => {
        EyeOverlay = class extends google.maps.OverlayView {
            constructor(pos, label) { super(); this.pos = pos; this.label = label; }
            onAdd() {
                this.div = document.createElement('div');
                this.div.innerHTML = '<div class="sk-eye"></div><div class="sk-eye-label">' + esc(this.label) + '</div>';
                this.div.style.position = 'absolute';
                this.getPanes().overlayMouseTarget.appendChild(this.div);
            }
            draw() { if (!this.div) return; const p = this.getProjection().fromLatLngToDivPixel(new google.maps.LatLng(this.pos.lat, this.pos.lng)); if (p) { this.div.style.left = p.x + 'px'; this.div.style.top = p.y + 'px'; } }
            move(pos) { this.pos = pos; this.draw(); }
            onRemove() { this.div?.remove(); this.div = null; }
        };
    };
    const drawStorms = () => {
        stormShapes.forEach((s) => s.setMap(null)); eyeOverlays.forEach((o) => o.setMap(null));
        stormShapes = []; eyeOverlays = [];
        if (!map) return;
        const show = document.querySelector('.sk-lay[data-storms]').classList.contains('is-on');
        STORMS.forEach((s) => {
            const pts = s.points || [];
            if (s.cone && s.cone.coordinates) {
                const polys = s.cone.type === 'MultiPolygon' ? s.cone.coordinates : [s.cone.coordinates];
                polys.forEach((poly) => stormShapes.push(new google.maps.Polygon({ paths: poly.map((ringC) => ringC.map(([lng, lat]) => ({ lat, lng }))), strokeColor: '#ef4444', strokeOpacity: .45, strokeWeight: 1, fillColor: '#ef4444', fillOpacity: .13, clickable: false, map: show ? map : null })));
            }
            const past = pts.filter((p) => !p.forecast), fut = pts.filter((p) => p.forecast);
            if (past.length > 1) stormShapes.push(new google.maps.Polyline({ path: past, strokeColor: '#f87171', strokeOpacity: .95, strokeWeight: 2.5, clickable: false, map: show ? map : null }));
            if (fut.length) {
                const path = (past.length ? [past[past.length - 1]] : []).concat(fut);
                stormShapes.push(new google.maps.Polyline({ path, strokeOpacity: 0, clickable: false, map: show ? map : null,
                    icons: [{ icon: { path: 'M 0,-1 0,1', strokeOpacity: 1, strokeColor: '#fca5a5', strokeWeight: 2.5, scale: 2.5 }, offset: '0', repeat: '12px' }] }));
            }
            pts.forEach((p) => stormShapes.push(new google.maps.Marker({ position: p, map: show ? map : null, zIndex: 20,
                title: (p.forecast ? 'Forecast ' : '') + (p.ph ? new Date(p.ph).toLocaleString('en-PH', { month: 'short', day: 'numeric', hour: 'numeric' }) : '') + (p.cat ? ' · ' + p.cat : ''),
                icon: { path: google.maps.SymbolPath.CIRCLE, scale: p.forecast ? 4 : 3.5, fillColor: p.forecast ? '#fecaca' : '#ef4444', fillOpacity: 1, strokeColor: '#7f1d1d', strokeWeight: 1 } })));
            if (s.eye) { const o = new EyeOverlay(s.eye, s.name); if (show) o.setMap(map); eyeOverlays.push(o); }
        });
    };
    const badge = () => {
        const near = farm ? STORMS.filter((s) => s.eye).map((s) => ({ s, d: km(farm, s.eye), c: (s.points || []).filter((p) => p.forecast).reduce((m, p) => Math.min(m, km(farm, p)), Infinity) })) : [];
        const hot = near.filter((x) => x.s.current && x.d <= 300).sort((a, b) => a.d - b.d)[0];
        const coming = near.filter((x) => x.s.current && x.c <= 300).sort((a, b) => a.c - b.c)[0];
        const b = $('skBadge');
        b.classList.remove('is-warn', 'is-watch');
        if (hot) { b.classList.add('is-warn'); $('skBadgeText').textContent = hot.s.name + ' is ' + Math.round(hot.d) + ' km away'; }
        else if (coming) { b.classList.add('is-watch'); $('skBadgeText').textContent = coming.s.name + ' may pass within ' + Math.round(coming.c) + ' km'; }
        else $('skBadgeText').textContent = farm ? 'No typhoon within 300 km' : 'Choose your farm';
        $('skStorms').innerHTML = STORMS.length ? STORMS.map((s) => {
            const d = farm && s.eye ? km(farm, s.eye) : null;
            const close = farm ? (s.points || []).filter((p) => p.forecast).map((p) => ({ p, d: km(farm, p) })).sort((a, b) => a.d - b.d)[0] : null;
            return '<div class="sk-storm"><b>' + esc(s.name) + '</b>' + (d != null ? '<span class="sk-km' + (d <= 300 ? ' is-near' : '') + '">' + Math.round(d).toLocaleString() + ' km<small>from your farm</small></span>' : '<span></span>')
                + '<p>' + (s.current ? 'Active now' : 'Ended') + (s.alert ? ' · ' + esc(s.alert) + ' alert' : '') + (s.maxWindKmh ? ' · up to ' + Math.round(s.maxWindKmh) + ' km/h' : '') + '</p>'
                + (close && close.p.ph ? '<p>Closest forecast point: <b>' + Math.round(close.d) + ' km</b> on ' + esc(new Date(close.p.ph).toLocaleString('en-PH', { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric' })) + '</p>' : '')
                + '</div>';
        }).join('') : '<div class="sk-storm is-calm"><i><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></i><span><b>No typhoon near the Philippines</b><p>The track and cone of any storm that forms will show on the map, with its distance to your farm.</p></span></div>';
    };
    const loadStorms = async () => {
        try {
            const r = await window.api(U.storms + (farm ? '?lat=' + farm.lat + '&lng=' + farm.lng : ''));
            // Only storms that matter here: active ones within 4,000 km, or one
            // that ended near the farm in the last few days.
            STORMS = ((r.data && r.data.storms) || []).filter((s) => {
                if (!farm || !s.eye) return s.current;
                const d = km(farm, s.eye);
                return (s.current && d <= 4000) || d <= 1500;
            });
        } catch (_) { STORMS = []; }
        drawStorms();
        badge();
    };
    const loadForecast = async () => {
        if (!farm) return;
        try { const r = await window.api(U.forecast + '?lat=' + farm.lat + '&lng=' + farm.lng); FC = r.data || FC; } catch (_) {}
        const today = new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' });
        $('skDays').innerHTML = FC.days.length ? FC.days.slice(0, 10).map((d, i) => {
            const day = new Date(d.date + 'T00:00:00');
            const rain = Number(d.rain) || 0;
            const key = window.wxKeyFor ? window.wxKeyFor(d.code, false, d.tmax, d.gust) : null;
            const md = day.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
            return '<div class="sk-day ' + (key && window.wxHue ? window.wxHue(key) : '') + (d.date === today ? ' is-today' : '') + '" style="--i:' + i + '"><b>' + esc(d.date === today ? 'Today' : day.toLocaleDateString('en-PH', { weekday: 'short' }))
                + '<small> ' + esc(md) + '</small></b><small>' + esc(md) + '</small>' + (key && window.wxSky ? window.wxSky(key, 44) : wxIcon(d.code, rain))
                + '<em>' + esc(key && window.wxName ? window.wxName(key) : wxWord(d.code, rain)) + '</em>'
                + '<span class="t">' + esc(Math.round(d.tmax)) + '°' + (d.tmin != null ? ' <small>/ ' + esc(Math.round(d.tmin)) + '°</small>' : '') + '</span>'
                + '<span class="r">' + esc(rain >= 10 ? Math.round(rain) : rain) + ' mm' + (d.pop != null ? ' · ' + esc(Math.round(d.pop)) + '%' : '') + '</span>'
                + ((d.gust || 0) >= 50 ? '<span class="g">Gusts ' + Math.round(d.gust) + ' km/h</span>' : '') + '</div>';
        }).join('') : '<p class="sk-sub">The forecast did not load. Try again in a moment.</p>';
        if (mode === 'future') future(Number($('skSlider').value));
    };

    /* ---- which lot Anee checks ---- */
    let lotId = '';
    const TICK = '<svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    const lotDay = (l) => l.day != null && l.day >= 0 ? (l.counter || 'Day') + ' ' + l.day : '';
    const setLot = (id) => {
        lotId = id ? String(id) : '';
        const l = OPT.lots.find((x) => String(x.id) === lotId);
        $('skLotIcon').textContent = l ? (l.icon || '🌱') : '🗺️';
        $('skLotNow').innerHTML = l ? esc(l.name) + '<small>' + esc([l.crop, l.stage, lotDay(l)].filter(Boolean).join(' · ')) + '</small>' : 'No lot, my farm in general<small>Anee reads the sky for the whole farm</small>';
    };
    const paintLots = () => {
        const q = ($('skLotQ').value || '').trim().toLowerCase();
        const row = (l) => '<button type="button" class="dt-row sk-lotrow' + (String(l.id) === lotId ? ' is-on' : '') + '" data-id="' + l.id + '"><span class="dt-row-e">' + esc(l.icon || '🌱') + '</span>'
            + '<span class="dt-row-body"><b>' + esc(l.name) + '</b><span class="sk-lotchips"><em>' + esc(l.crop) + '</em>' + (l.stage ? '<em class="is-stage">' + esc(l.stage) + '</em>' : '') + (lotDay(l) ? '<em class="is-day">' + esc(lotDay(l)) + '</em>' : '') + '</span>'
            + (l.place ? '<i>' + esc(l.place) + '</i>' : (l.lat ? '<i>Pinned on its map</i>' : '')) + '</span>' + TICK + '</button>';
        const hit = (l) => !q || (l.name + ' ' + l.crop + ' ' + (l.season || '') + ' ' + (l.place || '')).toLowerCase().includes(q);
        const groups = {};
        OPT.lots.filter(hit).forEach((l) => { const g = l.season || 'Lots'; (groups[g] = groups[g] || []).push(l); });
        $('skLotRows').innerHTML = (q ? '' : '<button type="button" class="dt-row sk-lotrow' + (!lotId ? ' is-on' : '') + '" data-id=""><span class="dt-row-e">🗺️</span><span class="dt-row-body"><b>No lot, my farm in general</b><i>Anee reads the sky for the whole farm</i></span>' + TICK + '</button>')
            + Object.entries(groups).map(([g, list]) => '<p class="sk-lot-h">' + esc(g) + '</p>' + list.map(row).join('')).join('')
            + (!OPT.lots.length ? '<p class="text-xs text-gray-400 mt-2">Your lots show here once a season has them.</p>' : (q && !Object.keys(groups).length ? '<p class="text-xs text-gray-400 mt-2">No lot by that name.</p>' : ''));
    };
    $('skLotBtn').addEventListener('click', () => {
        if (!OPT || !window.openSheet) return;
        $('skLotSearchBox').hidden = OPT.lots.length < 7; $('skLotQ').value = '';
        paintLots();
        window.openSheet('skLotSheet');
    });
    $('skLotQ').addEventListener('input', paintLots);
    $('skLotRows').addEventListener('click', (e) => {
        const b = e.target.closest('.sk-lotrow');
        if (!b) return;
        setLot(b.dataset.id);
        $('skLotRows').querySelectorAll('.sk-lotrow').forEach((x) => x.classList.toggle('is-on', x === b));
        setTimeout(() => window.closeSheet('skLotSheet'), 150);
    });

    /* ---- Anee ---- */
    const quote = () => {
        $('skRun').disabled = !OPT.canUse;
        $('skRunSays').textContent = OPT.canUse ? 'Ask Anee · ' + OPT.quote + ' credits' : 'Anee is not on your plan';
        $('skFine').innerHTML = OPT.canUse ? 'You have ' + (window.creditCoin ? window.creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString()) : OPT.balance) + '. Charged only when the reading is ready.' : esc(OPT.whyNot || '');
    };
    $('skRun').addEventListener('click', async () => {
        if (!farm) { panel(true); return window.toast?.('Choose your farm first.', 'error'); }
        $('skRun').disabled = true;
        window.aneeWait.show({ title: 'Anee is reading the sky…', sub: 'The storms, the forecast and your lot. Under a minute.', lines: ['Tracing the typhoon tracks…', 'Measuring the distance to your farm…', 'Reading the next ten days…', 'Checking your crop\'s stage…'] });
        try {
            const r = await window.api(U.generate, { method: 'POST', body: { lat: farm.lat, lng: farm.lng, place: farm.label, lotId: lotId || null } });
            const d = r.data && r.data.status === 'ready' ? r.data : await window.aneeWait.poll({ id: r.data.id, job: U.job, phases: window.aneeWait.phases.plain });
            await window.aneeWait.done({ title: 'Here is the sky, read.', line: 'What it means for your crop.' });
            OPT.balance = d.balance; quote(); showReport(d); loadSaved();
        } catch (err) { window.aneeWait.fail(); window.toast?.(err.message || 'The reading did not finish. Nothing was charged.', 'error'); }
        finally { $('skRun').disabled = !OPT.canUse; }
    });
    const showReport = (d) => {
        const a = (d.report || {}).anee || {}, st = a.storm || {}, fl = a.forLot || {};
        const risk = String(a.risk || 'low').toLowerCase();
        const tabs = [['now', 'Overview'], ['storm', 'Storm'], ['days', 'Next days'], ['lot', 'Your lot'], ['do', 'What to do']];
        const panes = {
            now: '<p>' + esc(a.riskWhy) + '</p><p><b>Next 72 hours:</b> ' + esc(a.next72h) + '</p><p><b>Keep watching:</b> ' + esc(a.watch) + '</p><p class="text-xs text-gray-400">Confidence: ' + esc(a.confidence) + '</p>',
            storm: '<p><b>' + esc(st.status) + '</b></p><p>' + esc(st.reading) + '</p><p><b>Closest approach:</b> ' + esc(st.closestApproach) + '</p><p><b>Expect at the farm:</b> ' + esc(st.expect) + '</p>',
            days: '<p>' + esc(a.next10Days) + '</p><p>' + esc(a.climate) + '</p>',
            lot: '<p>' + esc(fl.reading) + '</p>' + ((fl.risks || []).length ? '<p><b>Risks</b></p><ul class="list-disc pl-5">' + fl.risks.map((x) => '<li>' + esc(x) + '</li>').join('') + '</ul>' : '') + ((fl.opportunities || []).length ? '<p class="mt-2"><b>Windows to use</b></p><ul class="list-disc pl-5">' + fl.opportunities.map((x) => '<li>' + esc(x) + '</li>').join('') + '</ul>' : ''),
            do: '<ul class="sk-acts">' + (a.actions || []).map((x) => '<li><em>' + esc(x.when) + '</em><span><b>' + esc(x.what) + '</b><br><span class="text-xs text-gray-500">' + esc(x.why) + '</span></span></li>').join('') + '</ul>',
        };
        $('skRep').hidden = false;
        $('skRep').innerHTML = '<div class="sk-rhead r-' + esc(risk) + '"><small>Risk: ' + esc(risk) + ' · ' + esc((d.report || {}).at || '') + '</small><h3>' + esc(a.headline) + '</h3></div>'
            + '<div class="sk-rtabs">' + tabs.map(([k, l], i) => '<button type="button" class="sk-rtab' + (i ? '' : ' is-on') + '" data-t="' + k + '">' + esc(l) + '</button>').join('') + '</div>'
            + tabs.map(([k], i) => '<div class="sk-card sk-pane' + (i ? '' : ' is-on') + '" data-p="' + k + '">' + panes[k] + '</div>').join('');
        $('skRep').scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'start' });
    };
    $('skRep').addEventListener('click', (e) => {
        const t = e.target.closest('.sk-rtab');
        if (!t) return;
        document.querySelectorAll('.sk-rtab').forEach((b) => b.classList.toggle('is-on', b === t));
        document.querySelectorAll('.sk-pane').forEach((p) => p.classList.toggle('is-on', p.dataset.p === t.dataset.t));
    });
    const loadSaved = async () => {
        try {
            const r = await window.api(U.list);
            const rows = (r.data && r.data.rows) || [];
            $('skSaved').innerHTML = rows.length ? rows.map((x) => '<button type="button" class="sk-srow" data-id="' + x.id + '"><i class="r-' + esc(String(x.risk || '').toLowerCase()) + '"></i><span>' + esc(x.headline || x.title) + '</span><small>' + esc(x.at) + '</small></button>').join('') : '<p class="text-xs text-gray-400 mt-1">Your readings will be kept here.</p>';
        } catch (_) {}
    };
    $('skSaved').addEventListener('click', async (e) => {
        const b = e.target.closest('.sk-srow');
        if (!b) return;
        try { const r = await window.api(U.one(b.dataset.id)); showReport(r.data); } catch (err) { window.toast?.(err.message, 'error'); }
    });

    /* ---- boot ---- */
    const boot = async () => {
        try {
            const r = await window.api(U.options);
            OPT = r.data;
            quote();
            setLot('');
            $('skLots').innerHTML = OPT.lots.filter((l) => (l.lat && l.lng) || l.place).map((l) => '<button type="button" class="sk-opt" data-lot="' + l.id + '"><span>' + esc(l.name) + '<small>' + esc(l.crop + (l.place ? ' · ' + l.place : '')) + '</small></span></button>').join('') || '<p class="text-xs text-gray-400">Pin a lot on its map and it shows here.</p>';
            $('skLots').addEventListener('click', (e) => {
                const b = e.target.closest('.sk-opt');
                if (!b) return;
                const l = OPT.lots.find((x) => String(x.id) === b.dataset.lot);
                if (!l) return;
                setLot(l.id);
                if (l.lat && l.lng) return setFarm({ lat: l.lat, lng: l.lng, label: l.name });
                // No pin on the lot yet: its town stands in for it.
                window.api(U.places + '?q=' + encodeURIComponent(l.place)).then((r) => {
                    const p = ((r.data || {}).places || [])[0];
                    if (p) setFarm({ lat: p.lat, lng: p.lng, label: l.name + ' · ' + l.place }); else window.toast?.('That lot has no pin yet. Search its town instead.', 'error');
                }).catch(() => {});
            });
            if (!OPT.owm) document.querySelectorAll('.sk-lay[data-owm]').forEach((b) => { b.disabled = true; });
            try { const f = await window.api(U.frames); FR = f.data || FR; } catch (_) {}
            await initMap();
            defineEye();
            defineFc();
            let saved = null;
            try { saved = JSON.parse(localStorage.getItem(KEY) || 'null'); } catch (_) {}
            if (saved && saved.lat) setFarm(saved, true); else { panel(true); loadStorms(); }
            ensureAnim('clouds');
            frameIdx = Math.max(0, FR.clouds.length - 1);
            $('skSlider').max = frameIdx; $('skSlider').value = frameIdx;
            paintFrame();
            loadSaved();
        } catch (err) { window.toast?.(err.message || 'Could not load the sky.', 'error'); }
    };
    if (window.api) boot(); else window.addEventListener('load', boot, { once: true });
})();
</script>
@endpush
