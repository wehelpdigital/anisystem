@extends('layouts.app')
@section('title', 'Heat Map Forecast')
@section('page-title', 'Heat Map Forecast')
@section('page-subtitle', "The week's heat over your lot")
@section('back', \App\Support\BackTo::url(route('app.dashboard')))
{{-- No bottom bar on a phone: the map is the whole screen (the tool pages' rule, 2026-10-15). --}}
@section('body-class', 'hide-tabbar no-footer')

{{-- HEAT MAP FORECAST (2026-11-17, the owner: "create me another feature under the your tools and this feature is
     called heat map forecast ... first there's a wizard like the other modules that asks which lot to select and or
     add new one if there's nothing in there, or link to the global maps. then next page is the google map showing your
     lot area, with colors based on temperature, legend, and a draggable opacity for the colors. map should be zoomed
     in, rotated or moved using gestures just like a normal google map ... have another tab to show the forecasted
     temperature and guides based on the future dates, as well as a play button in the map showing the next days
     temperature. make it look good especially in mobile").

     Step 1: a lot of Global Lots (partials/farm-lots, with "Set the pin" and "Draw the boundary" from
     partials/lot-map), or a saved map of Global Maps. Step 2: the map and the forecast, in two tabs.

     The colors: one fixed scale from 14 to 40°C, so a color means the same heat on every day (blue cool, a pale
     cream around 27°C, then orange to deep red), and every color on the map is also said in numbers (the lot's badge,
     a tap anywhere, the numbers when zoomed out). The heat is drawn as Google ground overlays, so it turns and
     tilts with the vector map. Numbers: App\Support\HeatForecast; the guide: App\Support\HeatGuide. --}}
@push('head')
<style>
    .hm-wrap { --hm-e: cubic-bezier(.22,1,.36,1); max-width: 72rem; margin: 0 auto; padding-bottom: calc(1.5rem + env(safe-area-inset-bottom)); }
    .hm-wrap [hidden] { display: none !important; }
    .hm-in { animation: hmIn .3s ease .1s both; }
    @keyframes hmIn { from { opacity: 0; } }
    @keyframes hmUp { from { opacity: 0; transform: translateY(8px); } }

    /* ---- step 1: the place ---- */
    .hm-pick { max-width: 46rem; margin: 0 auto; }
    .hm-hero { position: relative; overflow: hidden; display: flex; gap: 1rem; align-items: center; padding: 1.1rem 1.2rem 1.3rem; border-radius: 1.4rem; color: #fff3ea;
        background: radial-gradient(120% 150% at 100% 0%, #f2a65a 0%, #cf4a2c 38%, #3a0d08 100%); }
    .hm-hero::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 5px; background: linear-gradient(90deg, #2f6db5, #b9d4ee, #ede9df, #f2a65a, #cf4a2c, #6b0f1a); }
    .hm-art { flex: none; width: 5.6rem; height: 5.6rem; }
    .hm-art .hm-ring { transform-origin: 32px 34px; animation: hmRing 3.2s var(--hm-e) infinite; }
    .hm-art .hm-ring.b { animation-delay: 1.6s; }
    @keyframes hmRing { from { transform: scale(.35); opacity: .9; } to { transform: scale(1.35); opacity: 0; } }
    .hm-art .hm-merc { transform-origin: 44px 44px; animation: hmMerc 4.5s ease-in-out infinite; }
    @keyframes hmMerc { 0%, 100% { transform: scaleY(.55); } 50% { transform: scaleY(1); } }
    .hm-hero h2 { position: relative; z-index: 1; font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; line-height: 1.25; color: #fff; }
    .hm-hero p { position: relative; z-index: 1; margin-top: .3rem; font-size: .86rem; line-height: 1.55; color: #ffe2cf; }
    .hm-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .65rem; }
    .hm-chips span { font-size: .7rem; font-weight: 800; padding: .25rem .6rem; border-radius: 999px; background: rgb(255 255 255 / .14); color: #fff3ea; }
    @media (max-width: 479.98px) { .hm-art { width: 4.4rem; height: 4.4rem; } .hm-hero { padding: 1rem; gap: .8rem; } .hm-hero h2 { font-size: 1.06rem; } }

    .hm-wiz { margin-top: 1rem; padding: 1.15rem 1.15rem 0; border-radius: 1.3rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .hm-stepno { display: flex; justify-content: space-between; gap: .5rem; font-size: .72rem; font-weight: 800; color: var(--color-gray-500); text-transform: uppercase; letter-spacing: .05em; }
    .hm-rail { display: flex; gap: .3rem; margin: .45rem 0 1rem; }
    .hm-rail i { flex: 1 1 0; height: .3rem; border-radius: 999px; background: var(--color-gray-200); position: relative; overflow: hidden; }
    .hm-rail i::after { content: ''; position: absolute; inset: 0; background: #cf4a2c; transform: scaleX(0); transform-origin: left; transition: transform .36s var(--hm-e); }
    .hm-rail i.is-on::after { transform: scaleX(1); }
    .hm-wiz h3 { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: var(--color-gray-900); }
    .hm-sub { margin: .25rem 0 .9rem; font-size: .84rem; line-height: 1.55; color: var(--color-gray-500); }

    .hm-src { position: relative; display: grid; grid-template-columns: 1fr 1fr; padding: .3rem; margin-bottom: .9rem; border-radius: 1rem; background: var(--color-gray-100); }
    .hm-src-ind { position: absolute; top: .3rem; bottom: .3rem; left: .3rem; width: calc(50% - .3rem); border-radius: .75rem; background: var(--color-white); box-shadow: 0 4px 12px -6px rgb(0 0 0 / .35);
        transition: transform .28s var(--hm-e); }
    .hm-src.is-map .hm-src-ind { transform: translateX(100%); }
    .hm-src button { position: relative; z-index: 1; min-height: 2.6rem; display: inline-flex; align-items: center; justify-content: center; gap: .45rem; font-size: .88rem; font-weight: 800;
        color: var(--color-gray-500); transition: color .28s var(--hm-e); }
    .hm-src button[aria-selected="true"] { color: var(--color-gray-900); }
    .hm-src img { width: 1.15rem; height: 1.15rem; object-fit: contain; }
    .hm-pane { animation: hmIn .26s ease both; }

    .hm-at { display: grid; gap: .6rem; margin-top: .9rem; padding: .8rem .85rem; border-radius: 1rem; background: #fff7f0; border: 1px solid #f6dcc8; animation: hmUp .3s var(--hm-e) both; }
    .hm-at p { display: flex; gap: .55rem; align-items: flex-start; font-size: .84rem; line-height: 1.5; color: #6a2a10; }
    .hm-at p svg { flex: none; width: 1.1rem; height: 1.1rem; margin-top: .15rem; color: #cf4a2c; }
    .hm-at p b { color: #4a1606; }
    .hm-at-acts { display: flex; flex-wrap: wrap; gap: .4rem; }
    .hm-pill { display: inline-flex; align-items: center; gap: .4rem; min-height: 2.5rem; padding: .4rem .95rem; border-radius: 999px; font-size: .82rem; font-weight: 800; color: #8a3410;
        background: var(--color-white); border: 1.5px solid #f0c3a4; transition: background-color .28s var(--hm-e), border-color .28s var(--hm-e), transform .28s var(--hm-e); }
    .hm-pill:hover { border-color: #e57a3c; transform: translateY(-1px); }
    .hm-pill.is-main { color: #3b2f00; background: #f5c518; border-color: #f5c518; }
    .hm-pill svg { width: .95rem; height: .95rem; }
    html.dark .hm-at { background: #24140c; border-color: #4a2a18; }
    html.dark .hm-at p { color: #f3c9ad; } html.dark .hm-at p b { color: #ffe6d4; }
    html.dark .hm-pill:not(.is-main) { background: #1b130e; color: #f3b48c; border-color: #5a3420; }

    .hm-maps { display: grid; gap: .55rem; }
    @media (min-width: 640px) { .hm-maps { grid-template-columns: 1fr 1fr; } }
    .hm-mcard { position: relative; display: flex; align-items: center; gap: .75rem; width: 100%; padding: .6rem; border-radius: 1.1rem; text-align: left; background: var(--color-white);
        border: 1.5px solid var(--color-gray-200); transition: border-color .28s var(--hm-e), background-color .28s var(--hm-e), transform .28s var(--hm-e); animation: hmUp .32s var(--hm-e) both; }
    .hm-mcard:hover { border-color: #f0a56a; transform: translateY(-1px); }
    .hm-mcard.is-on { border-color: #cf4a2c; background: #fff6ef; box-shadow: 0 0 0 3px rgb(207 74 44 / .14); }
    .hm-mthumb { flex: none; width: 3.6rem; height: 3.6rem; border-radius: .8rem; overflow: hidden; display: grid; place-items: center; background: #e9efe4; }
    /* The picture lies over the map mark; with no picture (or one that fails) the mark shows. */
    .hm-mthumb > * { grid-area: 1 / 1; }
    .hm-mthumb img { width: 100%; height: 100%; object-fit: cover; }
    .hm-mthumb svg { width: 1.6rem; height: 1.6rem; color: #4a7c2a; }
    .hm-mcard span { min-width: 0; flex: 1 1 auto; }
    .hm-mcard b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .hm-mcard small { display: block; margin-top: .1rem; font-size: .74rem; color: var(--color-gray-500); }
    .hm-mcheck { flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: #cf4a2c; transform: scale(0); transition: transform .28s var(--hm-e); }
    .hm-mcheck svg { width: .85rem; height: .85rem; }
    .hm-mcard.is-on .hm-mcheck { transform: scale(1); }
    html.dark .hm-mcard.is-on { background: #2a160c; }
    html.dark .hm-mthumb { background: #1b2416; }
    .hm-empty { padding: 1.1rem 1rem; border-radius: 1.1rem; font-size: .86rem; line-height: 1.55; color: var(--color-gray-600); background: var(--color-gray-50); border: 1px dashed var(--color-gray-200); }
    .hm-empty a, .hm-more a { font-weight: 800; color: #b8441f; text-decoration: underline; text-underline-offset: 2px; }
    .hm-more { display: flex; flex-wrap: wrap; gap: .25rem .6rem; justify-content: space-between; margin-top: .7rem; font-size: .78rem; color: var(--color-gray-500); }
    .hm-wait { padding: 1rem; font-size: .84rem; color: var(--color-gray-500); }

    .hm-nav { position: sticky; bottom: 0; z-index: 2; display: flex; gap: .5rem; margin: 1.1rem -1.15rem 0; padding: .8rem 1.15rem calc(.8rem + env(safe-area-inset-bottom));
        border-top: 1px solid var(--color-gray-100); border-radius: 0 0 1.3rem 1.3rem; background: var(--color-white); }
    .hm-nav .btn { min-height: 3rem; }
    .hm-nav .btn-cta { flex: 1 1 auto; }

    /* ---- step 2: the view ---- */
    .hm-vhead { display: flex; align-items: center; gap: .6rem; margin-bottom: .7rem; }
    @media (max-width: 1023.98px) {
        .hm-vhead { margin: 0 -1rem; padding: .5rem 1rem; background: var(--color-white); border-bottom: 1px solid var(--color-gray-200); }
        html.hm-onview main { padding-top: 0 !important; }
    }
    @media (min-width: 640px) and (max-width: 1023.98px) { .hm-vhead { margin: 0 -1.5rem; padding: .55rem 1.5rem; } }
    html.hm-maptab main { padding-bottom: 0 !important; }
    .hm-change { flex: 0 1 auto; min-width: 0; display: inline-flex; align-items: center; gap: .4rem; min-height: 2.6rem; padding: .3rem .8rem .3rem .45rem; border-radius: 999px;
        background: var(--color-gray-100); text-align: left; transition: background-color .28s var(--hm-e); }
    .hm-change:hover { background: var(--color-gray-200); }
    .hm-change > svg { flex: none; width: 1.1rem; height: 1.1rem; color: var(--color-gray-600); }
    .hm-change span { min-width: 0; display: grid; line-height: 1.15; }
    .hm-change b { font-size: .86rem; font-weight: 800; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hm-change small { font-size: .68rem; font-weight: 700; color: var(--color-gray-500); }
    .hm-tabs { position: relative; flex: none; display: grid; grid-template-columns: repeat(3, 1fr); margin-left: auto; padding: .25rem; border-radius: 999px; background: var(--color-gray-100); }
    .hm-tab-ind { position: absolute; top: .25rem; bottom: .25rem; left: .25rem; width: calc((100% - .5rem) / 3); border-radius: 999px; background: #cf4a2c; transition: transform .28s var(--hm-e); }
    .hm-tabs.is-days .hm-tab-ind { transform: translateX(100%); }
    .hm-tabs.is-months .hm-tab-ind { transform: translateX(200%); }
    .hm-tab { position: relative; z-index: 1; min-height: 2.3rem; padding: 0 .95rem; display: inline-flex; align-items: center; justify-content: center; gap: .35rem; font-size: .8rem; font-weight: 800;
        color: var(--color-gray-600); white-space: nowrap; transition: color .28s var(--hm-e); }
    .hm-tab svg { width: .95rem; height: .95rem; }
    .hm-tab[aria-selected="true"] { color: #fff; }
    /* A phone: the place on one line, the three tabs across the whole width under it. */
    @media (max-width: 719.98px) {
        .hm-vhead { flex-wrap: wrap; gap: .45rem; }
        .hm-change { flex: 1 1 100%; min-height: 2.3rem; padding: .2rem .7rem .2rem .4rem; }
        .hm-change span { display: flex; align-items: baseline; gap: .5rem; }
        .hm-change small { flex: none; }
        .hm-tabs { flex: 1 1 100%; margin-left: 0; }
        .hm-tab { padding: 0 .4rem; font-size: .78rem; }
        .hm-tab .hm-tlong { display: none; }
    }
    @media (min-width: 720px) { .hm-tab .hm-tshort { display: none; } }

    .hm-stage { position: relative; isolation: isolate; overflow: hidden; border-radius: 1.4rem; background: #0f1309; border: 1px solid var(--color-gray-200); box-shadow: 0 30px 60px -40px rgb(0 0 0 / .9); }
    .hm-map { height: var(--hm-h, calc(100svh - var(--app-head, 4rem) - 4.5rem)); min-height: 22rem; }
    @media (max-width: 1023.98px) { .hm-stage { margin: 0 -1rem; border-radius: 0; border: 0; box-shadow: none; } }
    @media (min-width: 640px) and (max-width: 1023.98px) { .hm-stage { margin: 0 -1.5rem; } }
    .hm-glass { color: #f3f1ec; background: rgb(22 16 12 / .8); backdrop-filter: blur(12px) saturate(1.3); -webkit-backdrop-filter: blur(12px) saturate(1.3);
        box-shadow: 0 10px 26px -14px rgb(0 0 0 / .9), inset 0 0 0 1px rgb(255 255 255 / .08); }

    /* The top: how fresh the forecast is, and the map's own buttons. */
    .hm-asof { position: absolute; left: .65rem; top: .65rem; z-index: 3; max-width: calc(100% - 4.6rem); display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .75rem;
        border-radius: 999px; font-size: .72rem; font-weight: 800; transition: opacity .28s var(--hm-e); }
    .hm-asof i { flex: none; width: .5rem; height: .5rem; border-radius: 999px; background: #86efac; box-shadow: 0 0 0 3px rgb(134 239 172 / .25); }
    .hm-asof.is-stale i { background: #fbbf24; box-shadow: 0 0 0 3px rgb(251 191 36 / .25); }
    .hm-asof span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hm-tools { position: absolute; right: .65rem; top: .65rem; z-index: 3; display: grid; gap: .4rem; }
    .hm-tool { width: 2.75rem; height: 2.75rem; border-radius: 999px; display: grid; place-items: center; transition: transform .28s var(--hm-e), background-color .28s var(--hm-e); }
    .hm-tool:active { transform: scale(.94); }
    .hm-tool svg { width: 1.15rem; height: 1.15rem; }
    .hm-tool.is-on { background: #f5c518; color: #2b2100; }

    /* The bottom: play, the day, the lot's reading, the modes, the opacity and the legend, in one panel that folds.
       It stands clear of Google's logo and terms under it. */
    .hm-panel { position: absolute; left: .65rem; right: .65rem; bottom: 1.85rem; z-index: 3; padding: .7rem .75rem .65rem; border-radius: 1.2rem; display: grid; gap: .55rem;
        transition: opacity .28s var(--hm-e), transform .28s var(--hm-e); }
    @media (min-width: 768px) { .hm-panel { left: 50%; right: auto; width: min(40rem, calc(100% - 7rem)); transform: translateX(-50%); } }
    .hm-panel.is-off { opacity: 0; pointer-events: none; }
    .hm-fold { position: absolute; top: -.85rem; left: 50%; width: 3rem; height: 1.6rem; margin-left: -1.5rem; border-radius: 999px; display: grid; place-items: center; color: #e7e2d8; }
    .hm-fold svg { width: .9rem; height: .9rem; transition: transform .28s var(--hm-e); }
    .hm-panel.is-folded .hm-fold svg { transform: rotate(180deg); }
    .hm-r1 { display: grid; grid-template-columns: auto minmax(0, 1fr) auto; gap: .65rem; align-items: center; }
    .hm-play { width: 2.85rem; height: 2.85rem; border-radius: 999px; display: grid; place-items: center; color: #2b2100; background: #f5c518; box-shadow: 0 6px 16px -6px rgb(245 197 24 / .75);
        transition: transform .28s var(--hm-e); }
    .hm-play:hover { transform: scale(1.06); }
    .hm-play svg { width: 1.1rem; height: 1.1rem; }
    .hm-play .i-pause, .hm-panel.is-playing .hm-play .i-play { display: none; }
    .hm-panel.is-playing .hm-play .i-pause { display: block; }
    .hm-when { min-width: 0; }
    .hm-when b { display: block; font-size: .92rem; font-weight: 800; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hm-when small { display: block; margin-top: .05rem; font-size: .7rem; font-weight: 700; color: #c9c1b4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hm-read { display: flex; align-items: center; gap: .5rem; padding: .3rem .35rem .3rem .3rem; border-radius: .95rem; background: rgb(255 255 255 / .07); }
    .hm-read i { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .75rem; display: grid; place-items: center; font-style: normal; font-size: .95rem; font-weight: 900;
        font-variant-numeric: tabular-nums; transition: background-color .28s var(--hm-e), color .28s var(--hm-e); }
    .hm-read span { display: grid; line-height: 1.15; max-width: 6.5rem; }
    .hm-read b { font-size: .72rem; font-weight: 800; color: #fff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hm-read small { font-size: .66rem; font-weight: 700; color: #c9c1b4; white-space: nowrap; }
    /* On a phone the time needs the room: the reading keeps its number and "Feels like", the lot's name goes. */
    @media (max-width: 479.98px) { .hm-read b { display: none; } .hm-read span { max-width: 4.6rem; } .hm-read small { white-space: normal; line-height: 1.2; } }
    @media (max-width: 359.98px) { .hm-read span { display: none; } }
    .hm-more2 { display: grid; gap: .55rem; max-height: 16rem; transition: max-height .3s var(--hm-e), opacity .24s var(--hm-e); }
    .hm-panel.is-folded .hm-more2 { max-height: 0; opacity: 0; overflow: hidden; pointer-events: none; }
    .hm-scrub { position: relative; padding-bottom: 1.05rem; }
    .hm-scrub input { display: block; width: 100%; height: 1.4rem; margin: 0; accent-color: #f5c518; cursor: pointer; }
    .hm-ticks { position: absolute; left: .55rem; right: .55rem; bottom: 0; height: 1rem; pointer-events: none; }
    .hm-ticks span { position: absolute; top: 0; transform: translateX(-50%); font-size: .62rem; font-weight: 800; color: #a9a294; white-space: nowrap; transition: color .28s var(--hm-e); }
    .hm-ticks span.is-on { color: #f5c518; }
    .hm-r3 { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .8rem; }
    .hm-modes { flex: none; display: flex; gap: .15rem; padding: .2rem; border-radius: 999px; background: rgb(255 255 255 / .08); }
    .hm-modes button { padding: .4rem .72rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #d6cfc3; transition: background-color .28s var(--hm-e), color .28s var(--hm-e); }
    .hm-modes button.is-on { background: #f3efe7; color: #1d150e; }
    .hm-op { flex: 1 1 8rem; min-width: 7rem; display: flex; align-items: center; gap: .45rem; font-size: .68rem; font-weight: 800; color: #c9c1b4; }
    .hm-op svg { flex: none; width: 1rem; height: 1rem; }
    .hm-op input { flex: 1 1 auto; min-width: 0; height: 1.3rem; margin: 0; accent-color: #f5c518; cursor: pointer; }
    .hm-op output { flex: none; min-width: 2.2rem; text-align: right; font-variant-numeric: tabular-nums; color: #f3f1ec; }
    .hm-legend { display: grid; gap: .25rem; }
    .hm-lbar { position: relative; height: .6rem; border-radius: 999px; box-shadow: inset 0 0 0 1px rgb(255 255 255 / .18); }
    .hm-lmark { position: absolute; top: -.25rem; width: .2rem; height: 1.1rem; margin-left: -.1rem; border-radius: 2px; background: #fff; box-shadow: 0 0 0 1.5px rgb(0 0 0 / .6); transition: left .28s var(--hm-e); }
    .hm-lticks { position: relative; height: .85rem; }
    .hm-lticks span { position: absolute; top: 0; transform: translateX(-50%); font-size: .62rem; font-weight: 800; color: #c9c1b4; font-variant-numeric: tabular-nums; }
    .hm-lticks span:first-child { transform: none; } .hm-lticks span:last-child { transform: translateX(-100%); }

    .hm-veil { position: absolute; inset: 0; z-index: 6; display: grid; place-items: center; padding: 1rem; background: rgb(15 19 9 / .55); backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px);
        transition: opacity .3s var(--hm-e); }
    .hm-veil.is-off { opacity: 0; pointer-events: none; }
    .hm-veil > div { display: grid; justify-items: center; gap: .6rem; max-width: 19rem; padding: 1.1rem 1.2rem; border-radius: 1.2rem; text-align: center; }
    .hm-veil b { font-size: .9rem; font-weight: 800; color: #fff; line-height: 1.4; }
    .hm-veil small { font-size: .76rem; color: #c9c1b4; line-height: 1.45; }
    .hm-veil .btn { margin-top: .2rem; }
    .hm-therm { width: 2.6rem; height: 3.6rem; }
    .hm-therm .hm-merc { transform-origin: 20px 46px; animation: hmMerc 1.8s ease-in-out infinite; }
    .hm-credit { margin-top: .55rem; font-size: .7rem; line-height: 1.5; color: var(--color-gray-400); }
    @media (max-width: 1023.98px) { .hm-credit { padding: 0 .1rem; } }

    /* ---- the forecast tab ---- */
    .hm-days { display: grid; gap: .8rem; padding-top: .8rem; }
    .hm-card { padding: 1rem 1.05rem; border-radius: 1.25rem; background: var(--color-white); border: 1px solid var(--color-gray-200); min-width: 0; animation: hmUp .4s var(--hm-e) both; }
    .hm-card h3 { font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; color: var(--color-gray-900); }
    .hm-card > p { margin-top: .2rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-500); }
    .hm-sum { position: relative; overflow: hidden; padding: 1.05rem 1.1rem; border-radius: 1.35rem; color: #fff; animation: hmUp .45s var(--hm-e) both;
        background: linear-gradient(135deg, #8f2a17, #3a0d08); box-shadow: 0 18px 40px -26px rgb(58 13 8 / .95); }
    .hm-sum small { display: block; font-size: .68rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; opacity: .85; }
    .hm-sum b { display: block; margin-top: .2rem; font-family: var(--font-heading); font-size: clamp(1.05rem, 3.4vw, 1.28rem); font-weight: 800; line-height: 1.3; }
    .hm-sum p { margin-top: .4rem; font-size: .82rem; line-height: 1.55; color: #ffd9c6; }
    .hm-sum-tags { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .6rem; }
    .hm-sum-tags span { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; background: rgb(255 255 255 / .14); }
    .hm-strip { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(5.4rem, 1fr); gap: .45rem; overflow-x: auto; scroll-snap-type: x mandatory; padding: .15rem .1rem .35rem;
        scrollbar-width: none; }
    .hm-strip::-webkit-scrollbar { display: none; }
    @media (max-width: 639.98px) { .hm-strip { margin: 0 -1rem; padding: .15rem 1rem .35rem; scroll-padding: 0 1rem; } }
    .hm-dchip { scroll-snap-align: start; display: grid; justify-items: center; gap: .15rem; padding: .55rem .35rem .5rem; border-radius: 1rem; text-align: center; background: var(--color-white);
        border: 1.5px solid var(--color-gray-200); transition: border-color .28s var(--hm-e), background-color .28s var(--hm-e), transform .28s var(--hm-e); animation: hmUp .38s var(--hm-e) both;
        animation-delay: calc(var(--i, 0) * 35ms); }
    .hm-dchip:hover { transform: translateY(-1px); border-color: #f0a56a; }
    .hm-dchip.is-on { border-color: #cf4a2c; background: #fff6ef; box-shadow: 0 0 0 3px rgb(207 74 44 / .14); }
    .hm-dchip > b { font-size: .76rem; font-weight: 800; color: var(--color-gray-900); }
    .hm-dchip > small { font-size: .64rem; font-weight: 700; color: var(--color-gray-500); }
    .hm-dchip .hm-t { font-size: .84rem; font-weight: 900; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .hm-dchip .hm-t small { font-weight: 700; color: var(--color-gray-500); }
    .hm-dchip .hm-bar { width: 78%; height: .3rem; border-radius: 999px; }
    .hm-dchip em { font-style: normal; font-size: .62rem; font-weight: 800; color: var(--color-gray-600); }
    html.dark .hm-dchip.is-on { background: #2a160c; }

    .hm-lv { display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .65rem; border-radius: 999px; font-size: .74rem; font-weight: 800; }
    .hm-lv svg { width: .85rem; height: .85rem; }
    .lv-cool { background: #dbe8f7; color: #1b3f7a; } .lv-mild { background: #e4efd4; color: #2d5016; } .lv-warm { background: #fdf0d5; color: #7a4a00; }
    .lv-hot { background: #fde2cc; color: #8a3410; } .lv-veryhot { background: #f9d0c7; color: #8f1d12; } .lv-extreme { background: #7a1e14; color: #fff; }
    .pp-ok { background: #e4efd4; color: #2d5016; } .pp-caution { background: #fdf0d5; color: #7a4a00; } .pp-xcaution { background: #fde2cc; color: #8a3410; }
    .pp-danger { background: #f9d0c7; color: #8f1d12; } .pp-extreme { background: #7a1e14; color: #fff; }
    html.dark .lv-cool { background: #13253f; color: #a9c8ef; } html.dark .lv-mild, html.dark .pp-ok { background: #1b2a12; color: #b9dc98; }
    html.dark .lv-warm, html.dark .pp-caution { background: #2f240c; color: #f6cf7d; } html.dark .lv-hot, html.dark .pp-xcaution { background: #36190b; color: #f8b48a; }
    html.dark .lv-veryhot, html.dark .pp-danger { background: #3d130e; color: #f7a093; }

    .hm-dtop { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: .5rem .8rem; }
    .hm-dtop h3 { display: flex; align-items: center; gap: .55rem; }
    .hm-dtop h3 .wx-sky { flex: none; }
    .hm-dtop h3 small { display: block; font-family: var(--font-body, inherit); font-size: .74rem; font-weight: 700; color: var(--color-gray-500); }
    .hm-say { margin-top: .55rem; font-size: .88rem; line-height: 1.55; color: var(--color-gray-700); }
    .hm-say b { color: var(--color-gray-900); }
    .hm-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem; margin-top: .75rem; }
    @media (max-width: 479.98px) { .hm-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .hm-stat { padding: .55rem .65rem; border-radius: .9rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); min-width: 0; }
    .hm-stat small { display: block; font-size: .64rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--color-gray-500); }
    .hm-stat b { display: block; margin-top: .1rem; font-size: 1.05rem; font-weight: 900; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .hm-stat em { display: block; font-style: normal; font-size: .7rem; color: var(--color-gray-500); }
    /* ---- the heat grids: the work by day, by hour and by month, and the weather by month and week ---- */
    .hm-hgw { position: relative; margin-top: .75rem; }
    .hm-hgw.is-scroll { overflow-x: auto; padding-bottom: .4rem; scrollbar-width: thin; -webkit-overflow-scrolling: touch; }
    .hm-hg { display: grid; grid-template-columns: var(--lab, 7rem) repeat(var(--n), minmax(var(--cw, 1.9rem), var(--cmax, 1fr))); gap: 3px; align-items: stretch; }
    .hm-hgw.is-scroll .hm-hg { width: max-content; min-width: 100%; }
    /* A phone and twelve months: each row's name on its own line above its squares, so the year fits the width. */
    .hm-hg.is-stack { grid-template-columns: repeat(var(--n), minmax(0, 1fr)); row-gap: 2px; }
    .hm-hg.is-stack > .hm-hl { grid-column: 1 / -1; position: static; padding: .5rem 0 .15rem; }
    .hm-hg.is-stack > .hm-hl:first-child { display: none; }
    .hm-hg.is-stack > .hm-hl b { white-space: normal; }
    .hm-hg.is-stack > .hm-hl small { display: inline; margin-left: .35rem; }
    .hm-hl { position: sticky; left: 0; z-index: 2; display: flex; align-items: center; gap: .35rem; min-width: 0; padding-right: .4rem; font-size: .72rem; line-height: 1.2; color: var(--color-gray-700); background: var(--color-white); }
    .hm-hl svg { flex: none; width: .95rem; height: .95rem; color: #b8441f; }
    .hm-hl b { min-width: 0; font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hm-hl small { display: block; font-size: .62rem; font-weight: 700; color: var(--color-gray-400); white-space: nowrap; }
    .hm-hh { display: grid; align-content: end; justify-items: center; padding-bottom: .2rem; font-size: .66rem; font-weight: 800; line-height: 1.1; text-align: center; color: var(--color-gray-500); white-space: nowrap; }
    .hm-hh small { font-size: .58rem; font-weight: 700; color: var(--color-gray-400); }
    .hm-hh svg { width: .95rem; height: .95rem; color: #b8441f; }
    .hm-hh.is-on, .hm-hh.is-on small { color: #b8441f; }
    .hm-hc { position: relative; height: var(--ch, 2rem); min-width: 0; border: 0; border-radius: .42rem; display: grid; place-items: center; padding: 0; font-size: var(--cf, .7rem); font-weight: 800;
        font-variant-numeric: tabular-nums; line-height: 1; transition: transform .2s var(--hm-e), box-shadow .2s var(--hm-e), opacity .2s var(--hm-e);
        animation: hmCell .42s var(--hm-e) both; animation-delay: calc(var(--d, 0) * 16ms); }
    @keyframes hmCell { from { opacity: 0; transform: scale(.6); } }
    .hm-hc svg { width: .8rem; height: .8rem; }
    button.hm-hc { cursor: pointer; }
    button.hm-hc:hover, button.hm-hc:focus-visible { transform: scale(1.08); z-index: 1; outline: none; box-shadow: 0 0 0 2px var(--color-white), 0 0 0 4px #1d150e; }
    .hm-hc.is-col { box-shadow: 0 0 0 2px var(--color-white), 0 0 0 3.5px #cf4a2c; }
    .hm-hc.is-past { opacity: .32; }
    .hm-hc.is-pick { box-shadow: 0 0 0 2px var(--color-white), 0 0 0 4px #1d150e; z-index: 1; }
    .r-g { background: #3f8f3a; color: #fff; }
    .r-f { background: #e3ac2f; color: #2b2100; }
    .r-p { background: #cf4a2c; color: #fff; }
    .r-x { color: transparent; background: repeating-linear-gradient(45deg, var(--color-gray-100) 0 3px, var(--color-gray-200) 3px 6px); }
    .r-n { color: var(--color-gray-300); background: transparent; box-shadow: inset 0 0 0 1px var(--color-gray-200); }
    .hm-key { display: flex; flex-wrap: wrap; gap: .35rem .9rem; margin-top: .6rem; font-size: .72rem; font-weight: 700; color: var(--color-gray-600); }
    .hm-key span { display: inline-flex; align-items: center; gap: .35rem; }
    .hm-key i { width: 1rem; height: 1rem; border-radius: .3rem; display: grid; place-items: center; }
    .hm-key i svg { width: .65rem; height: .65rem; }
    .hm-hnote { min-height: 2.6rem; margin-top: .55rem; padding: .55rem .7rem; border-radius: .85rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .hm-hnote b { color: var(--color-gray-900); }
    .hm-hnote.is-fresh { animation: hmIn .25s ease both; }
    .hm-sec-h { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; }
    .hm-seg { display: inline-flex; gap: .15rem; padding: .2rem; border-radius: 999px; background: var(--color-gray-100); }
    .hm-seg button { padding: .35rem .8rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: var(--color-gray-600); transition: background-color .28s var(--hm-e), color .28s var(--hm-e); }
    .hm-seg button[aria-pressed="true"] { background: var(--color-white); color: var(--color-gray-900); box-shadow: 0 3px 10px -6px rgb(0 0 0 / .45); }

    /* The day's work, as a list. */
    .hm-work { display: grid; gap: .45rem; margin-top: .85rem; }
    .hm-work h4, .hm-hours h4 { font-size: .74rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); }
    .hm-wk { display: grid; grid-template-columns: 2.2rem minmax(0, 1fr) auto; gap: .6rem; align-items: center; padding: .6rem .65rem; border-radius: .95rem; background: var(--color-gray-50);
        border: 1px solid var(--color-gray-100); animation: hmUp .35s var(--hm-e) both; animation-delay: calc(var(--i, 0) * 45ms); }
    .hm-wk > i { width: 2.2rem; height: 2.2rem; border-radius: .75rem; display: grid; place-items: center; color: #b8441f; background: #fde9dc; }
    .hm-wk > i svg { width: 1.15rem; height: 1.15rem; }
    .hm-wk b { display: block; font-size: .86rem; font-weight: 800; color: var(--color-gray-900); }
    .hm-wk p { margin-top: .1rem; font-size: .78rem; line-height: 1.45; color: var(--color-gray-600); }
    .hm-rate { display: inline-flex; align-items: center; gap: .3rem; padding: .28rem .6rem .28rem .45rem; border-radius: 999px; font-size: .72rem; font-weight: 800; white-space: nowrap; }
    .hm-rate svg { width: .75rem; height: .75rem; }
    .hm-hours { margin-top: 1rem; }
    html.dark .hm-wk > i { background: #36190b; color: #f8b48a; }

    /* The year. */
    .hm-yr { background: linear-gradient(135deg, #17324f, #0d1b2c); box-shadow: 0 18px 40px -26px rgb(13 27 44 / .95); }
    .hm-yr p { color: #cfe0f2; }
    .hm-wait2 { display: flex; gap: .8rem; align-items: center; }
    .hm-wait2 .hm-therm { flex: none; }
    .hm-best { display: grid; gap: .4rem; margin-top: .7rem; }
    .hm-best div { display: grid; grid-template-columns: 9rem minmax(0, 1fr); gap: .5rem; align-items: baseline; font-size: .78rem; color: var(--color-gray-600); }
    .hm-best b { font-weight: 800; color: var(--color-gray-800); }
    .hm-best span { display: flex; flex-wrap: wrap; gap: .25rem; }
    .hm-best em { font-style: normal; padding: .1rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #1f5a1d; background: #dcefd9; }
    .hm-best i { font-style: normal; color: var(--color-gray-400); }
    @media (max-width: 479.98px) { .hm-best div { grid-template-columns: 1fr; gap: .2rem; } }
    html.dark .hm-best em { background: #1b2a12; color: #b9dc98; }

    .hm-chart { position: relative; margin-top: .8rem; touch-action: pan-y; }
    .hm-chart svg { display: block; width: 100%; height: auto; overflow: visible; }
    .hm-chart text { font-family: inherit; font-size: 11px; fill: var(--color-gray-500); }
    .hm-chart text.is-ref { fill: #a82424; font-weight: 800; }
    .hm-chart .g { stroke: var(--color-gray-200); stroke-width: 1; shape-rendering: crispEdges; }
    .hm-chart .ref { stroke: #a82424; stroke-width: 1; stroke-dasharray: 4 4; opacity: .7; }
    .hm-chart .now { stroke: var(--color-gray-400); stroke-width: 1; stroke-dasharray: 2 3; }
    .hm-chart .lt { fill: none; stroke: #e57a3c; stroke-width: 2.4; stroke-linejoin: round; stroke-linecap: round; }
    .hm-chart .lf { fill: none; stroke: #9b2a20; stroke-width: 2; stroke-dasharray: 5 4; stroke-linecap: round; }
    .hm-chart .ar { fill: #e57a3c; opacity: .12; }
    .hm-chart .cross { stroke: var(--color-gray-500); stroke-width: 1; }
    .hm-chart .dot { stroke: var(--color-white); stroke-width: 2; }
    .hm-chart .draw { stroke-dasharray: var(--len); stroke-dashoffset: var(--len); animation: hmDraw 1.1s var(--hm-e) forwards; }
    .hm-chart .lf.draw { stroke-dasharray: 5 4; animation: none; }
    @keyframes hmDraw { to { stroke-dashoffset: 0; } }
    .hm-ckey { display: flex; flex-wrap: wrap; gap: .3rem 1rem; margin-top: .55rem; font-size: .74rem; color: var(--color-gray-600); }
    .hm-ckey span { display: inline-flex; align-items: center; gap: .4rem; }
    .hm-ckey i { display: inline-block; width: 1.1rem; height: 0; border-top: 2.4px solid #e57a3c; }
    .hm-ckey i.is-f { border-top: 2px dashed #9b2a20; }
    html.dark .hm-chart .lf { stroke: #f08a7a; }
    html.dark .hm-ckey i.is-f { border-top-color: #f08a7a; }
    html.dark .hm-chart text.is-ref { fill: #f08a7a; }
    html.dark .hm-chart .ref { stroke: #f08a7a; }
    .hm-tip { position: absolute; z-index: 4; top: 0; min-width: 8.5rem; padding: .45rem .6rem; border-radius: .7rem; pointer-events: none; font-size: .74rem; line-height: 1.4;
        background: var(--color-white); color: var(--color-gray-700); border: 1px solid var(--color-gray-200); box-shadow: 0 12px 28px -16px rgb(0 0 0 / .45); transition: opacity .15s; }
    .hm-tip b { display: block; font-size: .8rem; color: var(--color-gray-900); }
    .hm-tip[hidden] { display: none; }

    .hm-guides { display: grid; gap: .5rem; margin-top: .8rem; }
    .hm-guides h4 { font-size: .74rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); }
    .hm-g { display: grid; grid-template-columns: 2.3rem minmax(0, 1fr); gap: .65rem; align-items: start; padding: .65rem .7rem; border-radius: 1rem; background: var(--color-gray-50);
        border: 1px solid var(--color-gray-100); animation: hmUp .35s var(--hm-e) both; animation-delay: calc(var(--i, 0) * 45ms); }
    .hm-g > i { width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; color: #b8441f; background: #fde9dc; }
    .hm-g > i svg { width: 1.2rem; height: 1.2rem; }
    .hm-g.is-crop > i { color: #2d5016; background: #e4efd4; }
    .hm-g.is-calm > i { color: #2f6db5; background: #dbe8f7; }
    .hm-g p { font-size: .84rem; line-height: 1.55; color: var(--color-gray-700); }
    .hm-g p b { color: var(--color-gray-900); }
    html.dark .hm-g > i { background: #36190b; color: #f8b48a; } html.dark .hm-g.is-crop > i { background: #1b2a12; color: #b9dc98; } html.dark .hm-g.is-calm > i { background: #13253f; color: #a9c8ef; }
    .hm-dacts { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .9rem; }
    .hm-dacts .btn { min-height: 2.9rem; }
    .hm-week { display: grid; gap: .35rem; margin-top: .7rem; }
    .hm-wrow { display: grid; grid-template-columns: 4.6rem minmax(0, 1fr) 2.4rem; align-items: center; gap: .55rem; font-size: .76rem; color: var(--color-gray-600); }
    .hm-wrow > b { font-weight: 800; color: var(--color-gray-800); }
    .hm-wrow > em { font-style: normal; font-weight: 800; text-align: right; color: var(--color-gray-800); font-variant-numeric: tabular-nums; }
    .hm-wtrack { position: relative; height: .8rem; border-radius: 999px; background: var(--color-gray-100); }
    .hm-wbar { position: absolute; top: 0; bottom: 0; border-radius: 999px; transform-origin: left; animation: hmGrow .7s var(--hm-e) both; animation-delay: calc(var(--i, 0) * 50ms); }
    @keyframes hmGrow { from { transform: scaleX(0); } }
    .hm-wrow small { color: var(--color-gray-500); font-weight: 700; }

    /* ---- the info sheet ---- */
    .hm-info { display: grid; gap: .7rem; }
    .hm-info section { display: grid; grid-template-columns: 2.2rem minmax(0, 1fr); gap: .65rem; align-items: start; }
    .hm-info section > i { width: 2.2rem; height: 2.2rem; border-radius: .75rem; display: grid; place-items: center; color: #b8441f; background: #fde9dc; }
    .hm-info section > i svg { width: 1.1rem; height: 1.1rem; }
    .hm-info h4 { font-size: .88rem; font-weight: 800; color: var(--color-gray-900); }
    .hm-info p { margin-top: .1rem; font-size: .82rem; line-height: 1.55; color: var(--color-gray-600); }
    .hm-info .hm-ibar { height: .65rem; margin-top: .45rem; border-radius: 999px; }
    .hm-info .hm-iticks { display: flex; justify-content: space-between; margin-top: .2rem; font-size: .66rem; font-weight: 800; color: var(--color-gray-500); }
    html.dark .hm-info section > i { background: #36190b; color: #f8b48a; }

    @media (prefers-reduced-motion: reduce) {
        .hm-in, .hm-pane, .hm-at, .hm-mcard, .hm-card, .hm-sum, .hm-dchip, .hm-g, .hm-wbar, .hm-wk, .hm-hc, .hm-hnote, .hm-art .hm-ring, .hm-art .hm-merc, .hm-therm .hm-merc { animation: none !important; }
        .hm-chart .draw { animation: none; stroke-dashoffset: 0; }
        .hm-src-ind, .hm-tab-ind, .hm-rail i::after, .hm-more2, .hm-panel, .hm-lmark, .hm-mcheck { transition: none !important; }
    }
    html.sm-still .hm-art .hm-ring, html.sm-still .hm-art .hm-merc, html.sm-still .hm-therm .hm-merc { animation: none; }
</style>
@endpush

@section('content')
@include('partials.weather-scenes')
@php
    // Built here: the json directive reads one argument.
    $hmBoot = [
        'key' => (string) config('services.google_maps.key'),
        'mapId' => (string) config('services.google_maps.map_id'),
        'pin' => asset('images/map-pin.png'),
        'urls' => ['forecast' => route('heatmap.forecast'), 'year' => route('heatmap.year'), 'maps' => route('heatmap.maps'), 'mapsPage' => route('maps.page'), 'lotsPage' => route('farmlots.index')],
        'lot' => (int) request()->query('lot') ?: null,
        'map' => (int) request()->query('map') ?: null,
    ];
    $hmIco = [
        'chev' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>',
        'pin' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.2 7-11.5A7 7 0 005 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>',
    ];
@endphp
<script type="application/json" id="hmBoot">@json($hmBoot)</script>

<div class="hm-wrap" id="hmWrap">
    {{-- ================= Step 1: the place ================= --}}
    <section class="hm-pick" id="hmPick">
        <div class="hm-hero">
            <svg class="hm-art" viewBox="0 0 64 64" aria-hidden="true">
                <circle class="hm-ring" cx="32" cy="34" r="22" fill="none" stroke="#ffd2b3" stroke-width="1.6"/>
                <circle class="hm-ring b" cx="32" cy="34" r="22" fill="none" stroke="#ffd2b3" stroke-width="1.6"/>
                <path d="M10 44c6-9 14-12 22-10s14-4 22-12" fill="none" stroke="rgb(255 255 255 / .28)" stroke-width="1.4"/>
                <path d="M10 52c7-6 15-7 22-5s15-2 22-8" fill="none" stroke="rgb(255 255 255 / .2)" stroke-width="1.4"/>
                <path d="M44 8a6 6 0 016 6v19.6a10 10 0 11-12 0V14a6 6 0 016-6z" fill="#fff" stroke="#5a1408" stroke-width="2"/>
                <rect class="hm-merc" x="41.6" y="18" width="4.8" height="26" rx="2.4" fill="#cf4a2c"/>
                <circle cx="44" cy="42" r="6" fill="#cf4a2c"/>
                <path d="M14 30c0-6 4.5-10 9-10 0 6-4.5 10-9 10z" fill="#9ccc65"/>
            </svg>
            <div>
                <h2>See the week's heat over your lot</h2>
                <p>The temperature around your lot, painted on the map for every day and hour of the week, the best days and hours to plant, spray, fertilize, work, water and dry, and the year there month by month.</p>
                <div class="hm-chips"><span>7 days</span><span>Hour by hour</span><span>12 months</span><span>Philippine time</span></div>
            </div>
        </div>

        <div class="hm-wiz" id="hmWiz">
            <div class="hm-stepno"><span>Step 1 of 2</span><span>The place</span></div>
            <div class="hm-rail" aria-hidden="true"><i class="is-on"></i><i></i></div>
            <h3>Where should the map open?</h3>
            <p class="hm-sub">Pick one of your lots, or a map you drew in Global Maps. The heat is painted around it.</p>

            <div class="hm-src" id="hmSrc" role="tablist" aria-label="Where the map opens">
                <span class="hm-src-ind" aria-hidden="true"></span>
                <button type="button" role="tab" aria-selected="true" data-src="lot"><img src="{{ asset('images/treasure-map.png') }}" alt="">My lots</button>
                <button type="button" role="tab" aria-selected="false" data-src="map"><img src="{{ asset('images/location-marker.png') }}" alt="">My maps</button>
            </div>

            <div class="hm-pane" data-pane="lot">
                <div id="hmLots"></div>
                <div class="hm-at" id="hmLotAt" hidden></div>
            </div>
            <div class="hm-pane" data-pane="map" hidden>
                <div class="hm-maps" id="hmMaps"><p class="hm-wait">Loading your maps…</p></div>
                <p class="hm-more"><span>Your maps are kept in Global Maps.</span><a href="{{ route('maps.page') }}">Open Global Maps</a></p>
            </div>

            <div class="hm-nav">
                <button type="button" class="btn btn-white" id="hmBack">Back</button>
                <button type="button" class="btn btn-cta is-go" id="hmNext" disabled>Show the heat map</button>
            </div>
        </div>
    </section>

    {{-- ================= Step 2: the map and the forecast ================= --}}
    <section class="hm-view" id="hmView" hidden aria-label="Heat map">
        <div class="hm-vhead">
            <button type="button" class="hm-change" id="hmChange" aria-label="Change the lot or map">
                {!! $hmIco['chev'] !!}
                <span><b id="hmPlaceName">Your lot</b><small>Step 2 of 2 · Change</small></span>
            </button>
            <div class="hm-tabs" id="hmTabs" role="tablist">
                <span class="hm-tab-ind" aria-hidden="true"></span>
                <button type="button" class="hm-tab" role="tab" aria-selected="true" data-tab="map">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4L3 6.5v13.5l6-2.5 6 2.5 6-2.5V4l-6 2.5L9 4zm0 0v13.5m6-11v13.5"/></svg>
                    <span class="hm-tlong">Heat map</span><span class="hm-tshort">Map</span>
                </button>
                <button type="button" class="hm-tab" role="tab" aria-selected="false" data-tab="days">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 2"/></svg>
                    <span class="hm-tlong">Days and hours</span><span class="hm-tshort">Days</span>
                </button>
                <button type="button" class="hm-tab" role="tab" aria-selected="false" data-tab="months">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path stroke-linecap="round" d="M8 3v4m8-4v4M3.5 10h17"/><path d="M7.5 13.5h2v2h-2zm3.5 0h2v2h-2zm3.5 0h2v2h-2z" fill="currentColor" stroke="none"/></svg>
                    <span class="hm-tlong">Months</span><span class="hm-tshort">Months</span>
                </button>
            </div>
        </div>

        <div class="hm-tp" data-tp="map">
            <div class="hm-stage" id="hmStage">
                <div class="hm-map" id="hmMap" role="application" aria-label="Map of the heat around your lot"></div>

                <div class="hm-asof hm-glass" id="hmAsOf" hidden><i></i><span></span></div>
                <div class="hm-tools">
                    <button type="button" class="hm-tool hm-glass" id="hmInfoBtn" aria-label="How to read the heat map" title="How to read the heat map">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M9.6 9.3a2.5 2.5 0 114.2 1.9c-.9.7-1.8 1.2-1.8 2.4M12 16.8v.2"/></svg>
                    </button>
                    <button type="button" class="hm-tool hm-glass" id="hmFitBtn" aria-label="Back to the lot" title="Back to the lot">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="3.2"/><path stroke-linecap="round" d="M12 2.5v3m0 13v3M2.5 12h3m13 0h3"/><circle cx="12" cy="12" r="7"/></svg>
                    </button>
                    <button type="button" class="hm-tool hm-glass" id="hmAreaBtn" aria-label="See the whole heat area" title="See the whole heat area">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
                    </button>
                    <button type="button" class="hm-tool hm-glass" id="hmTypeBtn" aria-label="Show the street map" title="Street map or satellite">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l9 5-9 5-9-5 9-5zm-9 9l9 5 9-5M3 16l9 5 9-5"/></svg>
                    </button>
                </div>

                <div class="hm-panel hm-glass is-off" id="hmPanel">
                    <button type="button" class="hm-fold hm-glass" id="hmFold" aria-label="Fold the controls" aria-expanded="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="hm-r1">
                        <button type="button" class="hm-play" id="hmPlay" aria-label="Play the days">
                            <svg class="i-play" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13a1 1 0 001.5.86l10.5-6.5a1 1 0 000-1.72L9.5 4.64A1 1 0 008 5.5z"/></svg>
                            <svg class="i-pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6.5" y="5" width="4" height="14" rx="1.2"/><rect x="13.5" y="5" width="4" height="14" rx="1.2"/></svg>
                        </button>
                        <div class="hm-when" aria-live="polite"><b id="hmWhen">Today</b><small id="hmWhenSub">Hottest of the day</small></div>
                        <div class="hm-read" id="hmRead"><i id="hmReadT">--</i><span><b id="hmReadName">Your lot</b><small id="hmReadSub"></small></span></div>
                    </div>
                    <div class="hm-more2" id="hmMore">
                        <div class="hm-scrub">
                            <input type="range" id="hmScrub" min="0" max="6" step="1" value="0" aria-label="The day on the map">
                            <div class="hm-ticks" id="hmTicks" aria-hidden="true"></div>
                        </div>
                        <div class="hm-r3">
                            <div class="hm-modes" id="hmModes" role="radiogroup" aria-label="What the colors show">
                                <button type="button" role="radio" aria-checked="true" class="is-on" data-mode="high">Highest</button>
                                <button type="button" role="radio" aria-checked="false" data-mode="low">Lowest</button>
                                <button type="button" role="radio" aria-checked="false" data-mode="hour">Hour by hour</button>
                            </div>
                            <label class="hm-op">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.8"/></svg>
                                <input type="range" id="hmOp" min="10" max="100" step="1" value="62" aria-label="How strong the colors are">
                                <output id="hmOpOut" for="hmOp">62%</output>
                            </label>
                        </div>
                        <div class="hm-legend" aria-label="Color scale in degrees Celsius">
                            <div class="hm-lbar" id="hmLbar"><span class="hm-lmark" id="hmLmark" hidden></span></div>
                            <div class="hm-lticks" id="hmLticks"></div>
                        </div>
                    </div>
                </div>

                <div class="hm-veil" id="hmVeil">
                    <div class="hm-glass">
                        <svg class="hm-therm" viewBox="0 0 40 56" aria-hidden="true"><path d="M20 4a6 6 0 016 6v24.6a10 10 0 11-12 0V10a6 6 0 016-6z" fill="#fff" stroke="#5a1408" stroke-width="2"/><rect class="hm-merc" x="17.6" y="14" width="4.8" height="30" rx="2.4" fill="#cf4a2c"/><circle cx="20" cy="43" r="6" fill="#cf4a2c"/></svg>
                        <b id="hmVeilSay">Reading the week's heat…</b>
                        <small id="hmVeilSub">The temperature at about 80 spots around your lot, every hour of the week.</small>
                        <button type="button" class="btn btn-cta" id="hmRetry" hidden>Try again</button>
                    </div>
                </div>
            </div>
            <p class="hm-credit">Map: Google. Temperatures: the Open-Meteo forecast model, for about 80 spots in the 35 km around your lot, corrected for the height of the land and refreshed every 3 hours. Times are Philippine time.</p>
        </div>

        <div class="hm-tp" data-tp="days" hidden>
            <div class="hm-days" id="hmDays"></div>
        </div>

        <div class="hm-tp" data-tp="months" hidden>
            <div class="hm-days" id="hmMonths"></div>
        </div>
    </section>
</div>

{{-- The farm's lots: the picker, the lot sheet, and the field questions with it. --}}
@include('partials.farm-lots')
{{-- A lot's pin and boundary, each in its own full screen map (it waits for this page's Google script, never doubles it). --}}
@include('partials.lot-map')

{{-- How to read the map. --}}
<div class="sheet hidden" id="hmInfo" style="--sheet-width:32rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">How to read the heat map</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="hm-info">
            <section>
                <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M12 3a4 4 0 014 4v7.5a5.5 5.5 0 11-8 0V7a4 4 0 014-4z"/><path stroke-linecap="round" d="M12 8v8"/></svg></i>
                <div>
                    <h4>The colors are the temperature</h4>
                    <p>Blue is cool, the pale cream is about 27°C, orange is hot and deep red is very hot. A color means the same heat on every day, so the days can be compared.</p>
                    <div class="hm-ibar" id="hmInfoBar"></div>
                    <div class="hm-iticks"><span>14°C</span><span>20°C</span><span>27°C</span><span>33°C</span><span>40°C</span></div>
                </div>
            </section>
            <section>
                <i><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13a1 1 0 001.5.86l10.5-6.5a1 1 0 000-1.72L9.5 4.64A1 1 0 008 5.5z"/></svg></i>
                <div><h4>Play the week</h4><p><b>Highest</b> shows each day's hottest, <b>Lowest</b> each night's coolest, and <b>Hour by hour</b> every three hours. Press play, or drag the slider to a day.</p></div>
            </section>
            <section>
                <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="2.8"/></svg></i>
                <div><h4>See the fields under the colors</h4><p>Drag the eye slider to make the colors lighter or stronger.</p></div>
            </section>
            <section>
                <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.2 7-11.5A7 7 0 005 9.5C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg></i>
                <div><h4>Tap anywhere for the number</h4><p>A tap on the map reads the temperature at that spot. Your lot always wears its own number.</p></div>
            </section>
            <section>
                <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg></i>
                <div><h4>Zoom out to see the area</h4><p>The colors cover about 35 km around your lot. On flat land they are close to one color; hills, mountains and the sea show cooler. Pinch to zoom, turn and tilt with two fingers, and tap the compass to put north back up.</p></div>
            </section>
            <section>
                <i><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19h16M7 16V9m5 7V5m5 11v-4"/></svg></i>
                <div><h4>Where the numbers come from</h4><p>A weather forecast model gives the temperature for each spot, corrected for the height of the land, and it is refreshed every 3 hours. Open <b>Days and hours</b> for the best days and hours for planting, spraying, fertilizing, field work, watering and drying, and <b>Months</b> for the year there from twenty years of weather.</p></div>
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const B = JSON.parse($('hmBoot').textContent);
    const U = B.urls;
    const PICK_KEY = 'anee.heatPick';
    const st = { step: 0, src: 'lot', lot: null, map: null, maps: null, tab: 'map', mode: 'high', i: 0, day: 0, playing: false, folded: false, loadedFor: null };
    let D = null;        // the forecast answer: place, week (with the work of each day), today

    /* ---------------- the color scale: one for every day ---------------- */
    const T0 = 14, T1 = 40;
    const STOPS = [[14, '#1b3f7a'], [18, '#2f6db5'], [22, '#6fa3dc'], [25, '#b9d4ee'], [27, '#ede9df'], [29, '#f8cf8f'], [31, '#f2a65a'], [33, '#e57a3c'], [35, '#cf4a2c'], [37, '#a82424'], [40, '#6b0f1a']];
    const hexRgb = (h) => [parseInt(h.slice(1, 3), 16), parseInt(h.slice(3, 5), 16), parseInt(h.slice(5, 7), 16)];
    const LUT = [];
    for (let k = 0; k <= (T1 - T0) * 10; k++) {
        const t = T0 + k / 10;
        let j = 0;
        while (j < STOPS.length - 2 && t > STOPS[j + 1][0]) j++;
        const [a, ca] = STOPS[j], [b, cb] = STOPS[j + 1];
        const f = Math.max(0, Math.min(1, (t - a) / (b - a)));
        const A = hexRgb(ca), Bc = hexRgb(cb);
        LUT.push([0, 1, 2].map((n) => Math.round(A[n] + (Bc[n] - A[n]) * f)));
    }
    const rgbOf = (t) => LUT[Math.max(0, Math.min(LUT.length - 1, Math.round((t - T0) * 10)))];
    const cssOf = (t) => 'rgb(' + rgbOf(t).join(',') + ')';
    const inkOn = (t) => (t >= 33.5 || t <= 19) ? '#fff' : '#1d150e';
    const GRAD = 'linear-gradient(90deg, ' + STOPS.map(([v, c]) => c + ' ' + ((v - T0) / (T1 - T0) * 100).toFixed(1) + '%').join(', ') + ')';
    const pctOf = (t) => Math.max(0, Math.min(100, (t - T0) / (T1 - T0) * 100));
    $('hmLbar').style.background = GRAD;
    $('hmInfoBar').style.background = GRAD;
    $('hmLticks').innerHTML = [14, 20, 25, 30, 35, 40].map((v) => '<span style="left:' + pctOf(v) + '%">' + v + '°' + (v === 40 ? 'C' : '') + '</span>').join('');

    /* ---------------- dates, said the farmer's way (Philippine time, from the strings) ---------------- */
    const MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const DOW = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const DOWL = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const ymd = (s) => String(s).slice(0, 10).split('-').map(Number);
    const utcOf = (s, h = 0) => { const [y, m, d] = ymd(s); return Date.UTC(y, m - 1, d, h); };
    const dowOf = (s) => new Date(utcOf(s)).getUTCDay();
    const addDay = (s, n) => new Date(utcOf(s) + n * 864e5).toISOString().slice(0, 10);
    const hourOf = (at) => Number(String(at).slice(11, 13)) || 0;
    const hourWord = (h) => ((h % 12) || 12) + (h < 12 ? ' AM' : ' PM');
    const dayWord = (s, long) => {
        if (D && s === D.today) return 'Today';
        if (D && s === addDay(D.today, 1)) return 'Tomorrow';
        const [, m, d] = ymd(s);
        return (long ? DOWL : DOW)[dowOf(s)] + ', ' + MON[m - 1] + ' ' + d;
    };
    const dateLong = (s) => { const [, m, d] = ymd(s); return DOWL[dowOf(s)] + ', ' + MON[m - 1] + ' ' + d; };
    const deg = (v, dp = 0) => v == null ? '--' : (dp ? Number(v).toFixed(dp) : Math.round(v)) + '°';

    /* ================= Step 1: the place ================= */
    let picker = null;
    const remember = () => { try { localStorage.setItem(PICK_KEY, JSON.stringify(st.src === 'lot' ? { src: 'lot', id: st.lot?.id } : { src: 'map', id: st.map?.id })); } catch (_) {} };
    const recall = () => { try { return JSON.parse(localStorage.getItem(PICK_KEY) || 'null'); } catch (_) { return null; } };
    const canGo = () => (st.src === 'lot' ? !!(st.lot && st.lot.where) : !!st.map);
    const paintNext = () => { $('hmNext').disabled = !canGo(); };

    function setSrc(src) {
        st.src = src;
        $('hmSrc').classList.toggle('is-map', src === 'map');
        $('hmSrc').querySelectorAll('[data-src]').forEach((b) => b.setAttribute('aria-selected', String(b.dataset.src === src)));
        document.querySelectorAll('#hmWiz .hm-pane').forEach((p) => { p.hidden = p.dataset.pane !== src; });
        if (src === 'map' && !st.maps) loadMapList();
        paintNext();
    }
    $('hmSrc').addEventListener('click', (e) => { const b = e.target.closest('[data-src]'); if (b) setSrc(b.dataset.src); });

    const PEN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L18.5 9.5a2.1 2.1 0 00-4-4L4 16v4zM13.5 6.5l4 4"/></svg>';
    const PIN = @json($hmIco['pin']);
    // Where the map opens for the picked lot, and how to put it right on the lot.
    function paintLotAt() {
        const box = $('hmLotAt'), lot = st.lot;
        if (!lot) { box.hidden = true; return; }
        const has = Array.isArray(lot.boundary) && lot.boundary.length >= 3, pin = !!lot.pin;
        const town = lot.town || lot.where || lot.place || 'its town';
        const say = has ? 'The map opens on the boundary of <b>' + esc(lot.name) + '</b>, with its own temperature on it.'
            : pin ? 'The map opens at the pin of <b>' + esc(lot.name) + '</b>, with its own temperature on it.'
            : '<b>' + esc(lot.name) + '</b> has no pin yet, so the map opens on ' + esc(String(town).replace(/\s*\(Capital\)\s*$/i, '')) + '. Set its pin for the temperature right on the lot.';
        box.innerHTML = '<p>' + PIN + '<span>' + say + '</span></p><div class="hm-at-acts">'
            + (window.aneeLotMap ? '<button type="button" class="hm-pill' + (pin || has ? '' : ' is-main') + '" data-lm="pin">' + PIN + (pin ? 'Move the pin' : 'Set the pin') + '</button>'
                + '<button type="button" class="hm-pill" data-lm="draw">' + PEN + (has ? 'Redraw the boundary' : 'Draw the boundary') + '</button>' : '')
            + '</div>';
        box.hidden = false;
    }
    $('hmLotAt').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-lm]');
        if (!b || !st.lot || !window.aneeLotMap) return;
        // A saved pin or boundary comes back through the picker (aneelots:changed), and is applied there.
        try { await window.aneeLotMap[b.dataset.lm === 'pin' ? 'pin' : 'draw'](st.lot); } catch (err) { window.toast?.(err.message, 'error'); }
    });
    function lotPicked(lot) {
        st.lot = lot || null;
        if (st.lot && D && D.place && D.place.kind === 'lot' && D.place.id === st.lot.id) {
            // The same lot with a new pin or boundary: the map must read it again.
            const was = D.place;
            if (JSON.stringify(was.ring || null) !== JSON.stringify(st.lot.boundary && st.lot.boundary.length >= 3 ? st.lot.boundary : null)
                || JSON.stringify(was.pin || null) !== JSON.stringify(st.lot.pin || null)) st.loadedFor = null;
        }
        paintLotAt();
        paintNext();
    }
    function mountLots() {
        if (picker || !window.aneeLots) return;
        const r = recall();
        const want = B.lot || (!B.map && r && r.src === 'lot' ? r.id : null);
        picker = window.aneeLots.picker($('hmLots'), { crop: () => null, value: want || null, onPick: lotPicked });
        // A lot to start on is shown as picked; only Next moves on (the owner's rule: a pick never turns the page).
        if (want) picker.set(want).then(() => { const v = picker.value(); if (v) lotPicked(v); }).catch(() => {});
    }

    const MAP_MARK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 4L3 6.5v13.5l6-2.5 6 2.5 6-2.5V4l-6 2.5L9 4zm0 0v13.5m6-11v13.5"/></svg>';
    const CHECK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
    async function loadMapList() {
        const box = $('hmMaps');
        box.innerHTML = '<p class="hm-wait">Loading your maps…</p>';
        try {
            const r = await window.api(U.maps);
            st.maps = r.data.maps || [];
        } catch (err) {
            st.maps = null;
            box.innerHTML = '<div class="hm-empty">' + esc(err.message || 'Your maps could not be loaded.') + ' <a href="#" data-maps-retry>Try again</a></div>';
            return;
        }
        paintMapList();
    }
    function paintMapList() {
        const box = $('hmMaps'), list = st.maps || [];
        if (!list.length) {
            box.innerHTML = '<div class="hm-empty">No saved maps with a field on them yet. Draw your field in Global Maps, save it, and it shows here. <a href="' + esc(U.mapsPage) + '">Open Global Maps</a></div>';
            return;
        }
        box.innerHTML = list.map((m, i) => '<button type="button" class="hm-mcard' + (st.map && st.map.id === m.id ? ' is-on' : '') + '" data-map="' + m.id + '" style="animation-delay:' + (i * 35) + 'ms" aria-pressed="' + (st.map && st.map.id === m.id) + '">'
            + '<span class="hm-mthumb">' + MAP_MARK + (m.thumbUrl ? '<img src="' + esc(m.thumbUrl) + '" alt="" loading="lazy" onerror="this.remove()">' : '') + '</span>'
            + '<span><b>' + esc(m.title) + '</b><small>' + esc([m.count + (m.count === 1 ? ' shape' : ' shapes'), m.season, m.when, m.by ? 'by ' + m.by : null].filter(Boolean).join(' · ')) + '</small></span>'
            + '<span class="hm-mcheck">' + CHECK + '</span></button>').join('');
    }
    $('hmMaps').addEventListener('click', (e) => {
        if (e.target.closest('[data-maps-retry]')) { e.preventDefault(); loadMapList(); return; }
        const c = e.target.closest('[data-map]');
        if (!c || !st.maps) return;
        const m = st.maps.find((x) => x.id === Number(c.dataset.map));
        st.map = m || null;
        $('hmMaps').querySelectorAll('.hm-mcard').forEach((x) => { const on = Number(x.dataset.map) === st.map?.id; x.classList.toggle('is-on', on); x.setAttribute('aria-pressed', String(on)); });
        paintNext();
    });

    $('hmBack').addEventListener('click', () => window.aneeLeave());
    $('hmNext').addEventListener('click', () => { if (canGo()) { remember(); show(1); } });

    /* ================= the two steps ================= */
    function show(n) {
        if (n === st.step) return;
        if (n === 1) window.aneeStepOut?.($('hmWiz'));
        st.step = n;
        $('hmPick').hidden = n !== 0;
        $('hmView').hidden = n !== 1;
        const el = n === 1 ? $('hmView') : $('hmPick');
        if (!reduce()) { el.classList.remove('hm-in'); void el.offsetWidth; el.classList.add('hm-in'); }
        document.documentElement.classList.toggle('hm-onview', n === 1);
        document.documentElement.classList.toggle('hm-maptab', n === 1 && st.tab === 'map');
        window.scrollTo({ top: 0, behavior: 'auto' });
        if (n === 1) {
            // The phone's Back walks back to the place (Back walks back, 2026-11-03).
            window.registerOverlay?.('heatmap:view', () => show(0));
            setTab('map');
            openView();
        } else {
            window.unregisterOverlay?.('heatmap:view');
            stop();
        }
    }
    $('hmChange').addEventListener('click', () => show(0));

    function setTab(t) {
        st.tab = t;
        $('hmTabs').classList.toggle('is-days', t === 'days');
        $('hmTabs').classList.toggle('is-months', t === 'months');
        $('hmTabs').querySelectorAll('[data-tab]').forEach((b) => b.setAttribute('aria-selected', String(b.dataset.tab === t)));
        document.querySelectorAll('#hmView .hm-tp').forEach((p) => { p.hidden = p.dataset.tp !== t; });
        document.documentElement.classList.toggle('hm-maptab', t === 'map' && st.step === 1);
        const el = document.querySelector('#hmView .hm-tp[data-tp="' + t + '"]');
        if (el && !reduce()) { el.classList.remove('hm-in'); void el.offsetWidth; el.classList.add('hm-in'); }
        window.scrollTo({ top: 0, behavior: 'auto' });
        if (t === 'map') requestAnimationFrame(fit);
        else { stop(); if (t === 'days' && D) paintDays(); if (t === 'months') openMonths(); }
    }
    $('hmTabs').addEventListener('click', (e) => { const b = e.target.closest('[data-tab]'); if (b) setTab(b.dataset.tab); });

    // The map fills what is left of the screen under the tabs, so nothing scrolls on its tab.
    const fit = () => {
        const stg = $('hmStage');
        if (!stg || st.step !== 1 || st.tab !== 'map') return;
        const top = stg.getBoundingClientRect().top + window.scrollY;
        const gap = innerWidth >= 1024 ? 40 : 0;
        document.documentElement.style.setProperty('--hm-h', Math.max(360, Math.round(innerHeight - top - gap)) + 'px');
    };
    addEventListener('resize', () => { fit(); if (st.tab === 'days' && D) drawChart(); });

    /* ================= Google Maps ================= */
    let mapsP = null;
    const mapsOk = () => !!(window.google && window.google.maps && window.google.maps.Map && window.google.maps.geometry);
    // Loaded once; another surface on the page loading it (the lot's pin map) is waited for, never doubled.
    const loadMaps = () => mapsP ??= new Promise((resolve, reject) => {
        if (mapsOk()) return resolve();
        const wait = () => { let n = 0; const t = setInterval(() => { if (mapsOk()) { clearInterval(t); resolve(); } else if (++n > 200) { clearInterval(t); mapsP = null; reject(new Error('Google Maps did not load. Check the connection, then try again.')); } }, 100); };
        if (document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]')) return wait();
        const cb = '__hmMaps' + Date.now();
        window[cb] = () => resolve();
        const s = document.createElement('script');
        s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(B.key) + '&libraries=geometry&v=weekly&loading=async&callback=' + cb;
        s.async = true;
        s.onerror = () => { mapsP = null; reject(new Error('Google Maps did not load. Check the connection, then try again.')); };
        document.head.appendChild(s);
    });
    const prevAuth = window.gm_authFailure;
    let mapsRefused = false;
    window.gm_authFailure = function () {
        try { if (typeof prevAuth === 'function') prevAuth(); } catch (_) {}
        mapsRefused = true;
        if (st.step === 1) veil('The map cannot be shown right now.', 'Google Maps refused this site. Please tell us. The days, the hours and the months still work: open Days and hours.', true);
    };

    let map = null, ground = null, fadeTok = 0, lotMarks = [], tapMark = null, tapTimer = 0, gridMarks = [], shapeMarks = [];
    const G = () => window.google.maps;
    function ensureMap() {
        if (map) return map;
        const fine = matchMedia('(pointer: fine)').matches;
        map = new (G().Map)($('hmMap'), {
            center: { lat: 12.88, lng: 121.77 }, zoom: 6, mapTypeId: 'hybrid',
            // A vector map (a Map ID) turns and tilts with two fingers like the Google Maps app; one finger pans.
            mapId: B.mapId || 'DEMO_MAP_ID', renderingType: 'VECTOR', headingInteractionEnabled: true, tiltInteractionEnabled: true,
            gestureHandling: 'greedy', clickableIcons: false, keyboardShortcuts: true,
            streetViewControl: false, fullscreenControl: false, mapTypeControl: false, cameraControl: false,
            zoomControl: fine, zoomControlOptions: { position: G().ControlPosition.RIGHT_CENTER },
            rotateControl: true, rotateControlOptions: { position: G().ControlPosition.RIGHT_CENTER },
        });
        map.addListener('click', (e) => tapRead(e.latLng));
        map.addListener('zoom_changed', () => paintGridMarks());
        return map;
    }

    function veil(say, sub, error, retry) {
        const v = $('hmVeil');
        if (say === false) { v.classList.add('is-off'); return; }
        $('hmVeilSay').textContent = say;
        $('hmVeilSub').textContent = sub || '';
        $('hmVeilSub').hidden = !sub;
        $('hmRetry').hidden = !retry;
        v.querySelector('.hm-therm').style.display = error ? 'none' : '';
        v.classList.remove('is-off');
    }
    $('hmRetry').addEventListener('click', () => { st.loadedFor = null; openView(); });

    async function openView() {
        const want = st.src === 'lot' ? 'lot=' + st.lot.id : 'map=' + st.map.id;
        const name = st.src === 'lot' ? st.lot.name : st.map.title;
        $('hmPlaceName').textContent = name;
        requestAnimationFrame(fit);
        if (st.loadedFor === want && D) { veil(false); return; }
        $('hmPanel').classList.add('is-off');
        $('hmAsOf').hidden = true;
        veil('Reading the week\'s heat around ' + name + '…', 'The temperature at about 80 spots around it, every hour of the week.');
        stop();
        try { await loadMaps(); } catch (err) { veil('The map could not load.', err.message, true, true); return; }
        ensureMap();
        let r;
        try {
            r = await window.api(U.forecast + '?' + want);
        } catch (err) {
            if (err.tierLock) { veil('The Heat Map Forecast is not on this plan.', err.message, true); return; }
            veil(err.data && err.data.noPlace ? 'This lot is not on the map yet.' : 'The forecast did not come.', err.message, true, !(err.data && err.data.noPlace));
            if (err.data && err.data.noPlace) $('hmVeilSub').textContent += ' Tap Change, then Set the pin.';
            return;
        }
        if ((st.src === 'lot' ? 'lot=' + st.lot?.id : 'map=' + st.map?.id) !== want) return;   // changed meanwhile
        D = r.data;
        st.loadedFor = want;
        Y = null; yearFor = null; yearPick = null; clearTimeout(yearT);
        FRAMES.clear();
        build();
        if (!mapsRefused) veil(false);
        else veil('The map cannot be shown right now.', 'Google Maps refused this site. Please tell us. The days, the hours and the months still work: open Days and hours.', true);
    }

    /* ================= the heat ================= */
    const IMG = 192;
    const FRAMES = new Map();   // painted frames by key: a picture's data URL
    let grid = null, imgBounds = null;
    // A smooth surface through the square's points (cubic, Catmull and Rom), clamped at its edges.
    function sampler(vals) {
        const N = grid.size;
        const ok = vals.filter((v) => v != null);
        const mean = ok.length ? ok.reduce((a, b) => a + b, 0) / ok.length : 27;
        const g = vals.map((v) => (v == null ? mean : v));
        const at = (r, c) => g[Math.max(0, Math.min(N - 1, r)) * N + Math.max(0, Math.min(N - 1, c))];
        const cub = (p0, p1, p2, p3, x) => p1 + 0.5 * x * (p2 - p0 + x * (2 * p0 - 5 * p1 + 4 * p2 - p3 + x * (3 * (p1 - p2) + p3 - p0)));
        return (v, u) => {
            const r = Math.floor(v), c = Math.floor(u), fy = v - r, fx = u - c;
            const row = (k) => cub(at(r + k, c - 1), at(r + k, c), at(r + k, c + 1), at(r + k, c + 2), fx);
            return cub(row(-1), row(0), row(1), row(2), fy);
        };
    }
    function paint(vals) {
        const N = grid.size, f = sampler(vals);
        const cv = document.createElement('canvas');
        cv.width = cv.height = IMG;
        const cx = cv.getContext('2d');
        const img = cx.createImageData(IMG, IMG), d = img.data;
        for (let y = 0; y < IMG; y++) {
            const v = (IMG - y - 0.5) / IMG * N - 0.5;
            for (let x = 0; x < IMG; x++) {
                const u = (x + 0.5) / IMG * N - 0.5;
                const [r, g, b] = rgbOf(f(v, u));
                // The square's edge fades out over about one point, so it never ends on a hard line.
                const e = Math.min(u + 0.5, N - 0.5 - u, v + 0.5, N - 0.5 - v) / 1.1;
                const a = e >= 1 ? 1 : Math.max(0, e * e * (3 - 2 * e));
                const k = (y * IMG + x) * 4;
                d[k] = r; d[k + 1] = g; d[k + 2] = b; d[k + 3] = Math.round(240 * a);
            }
        }
        cx.putImageData(img, 0, 0);
        return cv.toDataURL('image/png');
    }
    const valueAt = (vals, lat, lng) => {
        if (!grid) return null;
        const N = grid.size, u = (lng - grid.lng0) / grid.step, v = (lat - grid.lat0) / grid.step;
        if (u < -0.5 || u > N - 0.5 || v < -0.5 || v > N - 0.5) return null;
        return sampler(vals)(v, u);
    };

    // The frames of a mode: each day's high, each night's low, or every third hour.
    function framesOf(mode) {
        const W = D.week;
        if (mode === 'hour') return W.frames.map((f) => ({ key: 'x' + f.at, vals: f.t, date: f.at.slice(0, 10), h: hourOf(f.at), at: f.at }));
        return W.days.map((d) => ({ key: mode[0] + d.date, vals: mode === 'low' ? d.min : d.max, date: d.date }));
    }
    let FL = [];
    const lotDayIdx = (date) => (D.week.lot.days || []).findIndex((x) => x.date === date);
    const lotHourIdx = (at) => { const from = D.week.lot.hours.from; return Math.round((utcOf(at, hourOf(at)) - utcOf(from, hourOf(from))) / 36e5); };
    // The lot's own reading for a frame: its own spot, not the square's.
    function lotValue(f) {
        const L = D.week.lot;
        if (st.mode === 'hour') { const k = lotHourIdx(f.at); return { t: L.hours.t[k] ?? null, f: L.hours.f[k] ?? null }; }
        const d = L.days[lotDayIdx(f.date)] || {};
        return st.mode === 'low' ? { t: d.min ?? null, f: null } : { t: d.max ?? null, f: d.fmax ?? null };
    }

    function build() {
        const W = D.week, P = D.place;
        grid = W.grid;
        const N = grid.size, h = grid.step / 2;
        imgBounds = new (G().LatLngBounds)({ lat: grid.lat0 - h, lng: grid.lng0 - h }, { lat: grid.lat0 + (N - 1) * grid.step + h, lng: grid.lng0 + (N - 1) * grid.step + h });
        if (ground) { ground.setMap(null); ground = null; }
        drawPlace();
        fitPlace();
        // The day the farmer was reading stays; a new place starts today.
        st.mode = st.mode || 'high';
        setMode(st.mode, true);
        const asof = $('hmAsOf');
        const at = new Date(W.fetchedAt);
        asof.querySelector('span').textContent = (W.stale ? 'Last forecast, ' : 'Forecast as of ') + at.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        asof.classList.toggle('is-stale', !!W.stale);
        asof.hidden = false;
        $('hmReadName').textContent = P.name;
        $('hmPanel').classList.remove('is-off');
        if (st.tab === 'days') paintDays();
        // Paint the rest of the frames while the farmer looks, so play runs smooth.
        warm();
    }
    let gen = 0;
    function warm() {
        const mine = ++gen;
        // The mode on screen first, then the others.
        const todo = [st.mode, ...['high', 'low', 'hour'].filter((m) => m !== st.mode)].flatMap((m) => framesOf(m)).filter((f) => !FRAMES.has(f.key));
        const idle = window.requestIdleCallback || ((fn) => setTimeout(() => fn({ timeRemaining: () => 8 }), 60));
        const go = (dl) => {
            if (mine !== gen) return;   // a newer place: its own warm runs
            while (todo.length && dl.timeRemaining() > 4) { const f = todo.shift(); if (!FRAMES.has(f.key)) FRAMES.set(f.key, paint(f.vals)); }
            if (todo.length) idle(go);
        };
        idle(go);
    }
    const urlOf = (f) => { if (!FRAMES.has(f.key)) FRAMES.set(f.key, paint(f.vals)); return FRAMES.get(f.key); };
    const op = () => Number($('hmOp').value) / 100;

    function showFrame(i) {
        if (!FL.length) return;
        st.i = Math.max(0, Math.min(FL.length - 1, i));
        const f = FL[st.i];
        $('hmScrub').value = String(st.i);
        st.day = Math.max(0, lotDayIdx(f.date));
        paintPanel(f);
        const next = new (G().GroundOverlay)(urlOf(f), imgBounds, { opacity: 0, clickable: false, map });
        const prev = ground, tok = ++fadeTok, to = op();
        ground = next;
        if (!prev || reduce()) { next.setOpacity(to); if (prev) prev.setMap(null); paintGridMarks(); return; }
        const t0 = performance.now(), dur = 280, from = prev.getOpacity();
        const step = (now) => {
            if (tok !== fadeTok) { prev.setMap(null); return; }
            const k = Math.min(1, (now - t0) / dur), e = k * (2 - k);
            next.setOpacity(to * e);
            prev.setOpacity(from * (1 - e));
            if (k < 1) requestAnimationFrame(step); else prev.setMap(null);
        };
        requestAnimationFrame(step);
        paintGridMarks();
    }
    function paintPanel(f) {
        const v = lotValue(f);
        // Short enough for a phone: "Fri, 12 PM" over "Nov 20, hour by hour".
        const [, fm, fd] = ymd(f.date);
        const near = f.date === D.today || f.date === addDay(D.today, 1);
        $('hmWhen').textContent = st.mode === 'hour' ? (near ? dayWord(f.date) : DOW[dowOf(f.date)]) + ', ' + hourWord(f.h) : dayWord(f.date);
        $('hmWhenSub').textContent = st.mode === 'hour' ? (near ? 'Hour by hour' : MON[fm - 1] + ' ' + fd + ', hour by hour') : st.mode === 'low' ? 'The coolest of the night' : 'The hottest of the day';
        const t = v.t;
        const sw = $('hmReadT');
        sw.textContent = deg(t);
        sw.style.background = t == null ? '#3f3a33' : cssOf(t);
        sw.style.color = t == null ? '#fff' : inkOn(t);
        $('hmReadSub').textContent = v.f != null ? 'Feels like ' + deg(v.f) : (t != null ? levelWord(t) : '');
        $('hmScrub').setAttribute('aria-valuetext', $('hmWhen').textContent + ', ' + deg(t) + ' at ' + D.place.name);
        const mk = $('hmLmark');
        mk.hidden = t == null;
        if (t != null) mk.style.left = pctOf(t) + '%';
        $('hmTicks').querySelectorAll('span').forEach((s) => s.classList.toggle('is-on', s.dataset.date === f.date));
        paintLotMarks(t);
    }
    const levelWord = (t) => t >= 37 ? 'Extreme heat' : t >= 35 ? 'Very hot' : t >= 33 ? 'Hot' : t >= 30 ? 'Warm' : t >= 25 ? 'Mild' : 'Cool';

    function setMode(mode, keepDay) {
        const date = FL.length ? FL[st.i]?.date : null;
        st.mode = mode;
        $('hmModes').querySelectorAll('[data-mode]').forEach((b) => { const on = b.dataset.mode === mode; b.classList.toggle('is-on', on); b.setAttribute('aria-checked', String(on)); });
        FL = framesOf(mode);
        const sc = $('hmScrub');
        sc.max = String(Math.max(0, FL.length - 1));
        sc.setAttribute('aria-label', mode === 'hour' ? 'The hour on the map' : 'The day on the map');
        // The day ticks under the slider: a label at each day's first frame.
        const seen = new Set();
        $('hmTicks').innerHTML = FL.map((f, i) => {
            if (seen.has(f.date)) return '';
            seen.add(f.date);
            const pos = FL.length > 1 ? i / (FL.length - 1) * 100 : 50;
            return '<span data-date="' + f.date + '" style="left:' + pos + '%">' + (f.date === D.today ? 'Today' : DOW[dowOf(f.date)]) + '</span>';
        }).join('');
        // Keep the day that was on. Into hours: today opens on the hour we are in, another day on its hottest hour.
        let i = 0;
        const want = keepDay && date ? date : (D.week.lot.days[st.day] || {}).date;
        if (want && mode === 'hour') {
            const same = FL.map((f, k) => ({ f, k })).filter((x) => x.f.date === want);
            const nowH = new Date().getHours();
            const now = want === D.today ? same.find((x) => x.f.h <= nowH && x.f.h + 3 > nowH) : null;
            const hot = same.reduce((a, x) => ((lotValue(x.f).t ?? -99) > (a ? lotValue(a.f).t ?? -99 : -100) ? x : a), null);
            i = now ? now.k : hot ? hot.k : 0;
        } else if (want) {
            i = Math.max(0, FL.findIndex((f) => f.date === want));
        }
        showFrame(i);
    }
    $('hmModes').addEventListener('click', (e) => { const b = e.target.closest('[data-mode]'); if (b && D && b.dataset.mode !== st.mode) setMode(b.dataset.mode, true); });
    $('hmScrub').addEventListener('input', (e) => { if (!D) return; stop(); showFrame(Number(e.target.value)); });
    $('hmOp').addEventListener('input', (e) => {
        $('hmOpOut').textContent = e.target.value + '%';
        fadeTok++;   // a fade in flight gives way to the hand
        if (ground) ground.setOpacity(op());
        try { localStorage.setItem('anee.heatOp', e.target.value); } catch (_) {}
    });
    try { const o = Number(localStorage.getItem('anee.heatOp')); if (o >= 10 && o <= 100) { $('hmOp').value = String(o); $('hmOpOut').textContent = o + '%'; } } catch (_) {}

    /* ---- play: day by day, or every third hour; it goes round again after a breath ---- */
    let playT = 0;
    function play() {
        if (!D || !FL.length) return;
        st.playing = true;
        $('hmPanel').classList.add('is-playing');
        $('hmPlay').setAttribute('aria-label', 'Pause');
        if (st.i >= FL.length - 1) showFrame(0);
        const tick = () => {
            if (!st.playing) return;
            const last = st.i >= FL.length - 1;
            if (last) { playT = setTimeout(() => { if (st.playing) { showFrame(0); playT = setTimeout(tick, st.mode === 'hour' ? 700 : 1300); } }, 1400); return; }
            showFrame(st.i + 1);
            playT = setTimeout(tick, st.mode === 'hour' ? 700 : 1300);
        };
        playT = setTimeout(tick, st.mode === 'hour' ? 500 : 900);
    }
    function stop() {
        st.playing = false;
        clearTimeout(playT);
        $('hmPanel').classList.remove('is-playing');
        $('hmPlay').setAttribute('aria-label', 'Play the days');
    }
    $('hmPlay').addEventListener('click', () => (st.playing ? stop() : play()));
    document.addEventListener('visibilitychange', () => { if (document.hidden) stop(); });

    $('hmFold').addEventListener('click', () => {
        st.folded = !st.folded;
        $('hmPanel').classList.toggle('is-folded', st.folded);
        $('hmFold').setAttribute('aria-expanded', String(!st.folded));
        $('hmFold').setAttribute('aria-label', st.folded ? 'Show the controls' : 'Fold the controls');
    });

    /* ---- the lot, the map's shapes, and the numbers on the map ---- */
    const svgUrl = (svg) => 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
    const FONT = 'system-ui,-apple-system,Segoe UI,Roboto,sans-serif';
    function badge(t, lift) {
        const txt = t == null ? '--' : Math.round(t) + '°';
        const bg = t == null ? '#3f3a33' : cssOf(t), ink = t == null ? '#fff' : inkOn(t);
        const w = 60, h = 42;
        const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + w + '" height="' + h + '" viewBox="0 0 ' + w + ' ' + h + '">'
            + '<path d="M' + (w / 2 - 6) + ' 31 L' + (w / 2) + ' 39 L' + (w / 2 + 6) + ' 31 Z" fill="#fff"/>'
            + '<rect x="3" y="2.5" width="' + (w - 6) + '" height="30" rx="15" fill="' + bg + '" stroke="#fff" stroke-width="2.6"/>'
            + '<text x="' + (w / 2) + '" y="23" text-anchor="middle" font-family="' + FONT + '" font-size="17" font-weight="800" fill="' + ink + '">' + txt + '</text></svg>';
        return { url: svgUrl(svg), scaledSize: new (G().Size)(w, h), anchor: new (G().Point)(w / 2, 39 + (lift || 0)) };
    }
    function label(t, small) {
        const txt = (t == null ? '--' : (small ? Math.round(t) : t.toFixed(1)) + '°') + (small ? '' : 'C here');
        const w = small ? 36 : 86, h = small ? 20 : 26;
        const svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + w + '" height="' + h + '" viewBox="0 0 ' + w + ' ' + h + '">'
            + '<rect x="1" y="1" width="' + (w - 2) + '" height="' + (h - 2) + '" rx="' + ((h - 2) / 2) + '" fill="rgb(22,16,12)" fill-opacity=".82" stroke="' + (t == null ? '#999' : cssOf(t)) + '" stroke-width="2"/>'
            + '<text x="' + (w / 2) + '" y="' + (small ? 14 : 17.5) + '" text-anchor="middle" font-family="' + FONT + '" font-size="' + (small ? 11 : 12.5) + '" font-weight="800" fill="#fff">' + txt + '</text></svg>';
        return { url: svgUrl(svg), scaledSize: new (G().Size)(w, h), anchor: new (G().Point)(w / 2, small ? h / 2 : h + 6) };
    }
    let badgeMark = null;
    function drawPlace() {
        [...lotMarks, ...shapeMarks].forEach((m) => m.setMap(null));
        lotMarks = []; shapeMarks = [];
        if (badgeMark) { badgeMark.setMap(null); badgeMark = null; }
        const P = D.place;
        const ring = (P.ring || []).map(([lat, lng]) => ({ lat, lng }));
        if (ring.length >= 3) {
            // A soft dark halo under a white line: the lot reads on any color under it.
            lotMarks.push(new (G().Polygon)({ map, paths: ring, strokeColor: '#000', strokeOpacity: .35, strokeWeight: 6, fillOpacity: 0, clickable: false, zIndex: 900 }));
            lotMarks.push(new (G().Polygon)({ map, paths: ring, strokeColor: '#fff', strokeOpacity: 1, strokeWeight: 2.5, fillColor: '#fff', fillOpacity: .06, clickable: false, zIndex: 901 }));
        }
        let lift = 0;
        if (P.pin && ring.length < 3) {
            lotMarks.push(new (G().Marker)({ map, position: P.pin, icon: { url: B.pin, scaledSize: new (G().Size)(26, 33), anchor: new (G().Point)(13, 33) }, clickable: false, zIndex: 950 }));
            lift = 33;
        }
        (P.shapes || []).forEach((s) => {
            const path = s.points.map(([lat, lng]) => ({ lat, lng }));
            if (s.type === 'closed') {
                shapeMarks.push(new (G().Polygon)({ map, paths: path, strokeColor: '#000', strokeOpacity: .3, strokeWeight: 5, fillOpacity: 0, clickable: false, zIndex: 900 }));
                shapeMarks.push(new (G().Polygon)({ map, paths: path, strokeColor: s.color, strokeOpacity: 1, strokeWeight: 2.5, fillColor: s.color, fillOpacity: .08, clickable: false, zIndex: 901 }));
            } else if (s.type === 'line') {
                shapeMarks.push(new (G().Polyline)({ map, path, strokeColor: s.color, strokeOpacity: .95, strokeWeight: 3, clickable: false, zIndex: 901 }));
            } else {
                shapeMarks.push(new (G().Marker)({ map, position: path[0], icon: { url: B.pin, scaledSize: new (G().Size)(22, 28), anchor: new (G().Point)(11, 28) }, clickable: false, title: s.label || '', zIndex: 940 }));
            }
        });
        badgeMark = new (G().Marker)({ map, position: { lat: P.lat, lng: P.lng }, icon: badge(null, lift), clickable: false, zIndex: 1000, title: P.name });
        badgeMark.__lift = lift;
    }
    function paintLotMarks(t) { if (badgeMark) badgeMark.setIcon(badge(t, badgeMark.__lift)); }
    function fitPlace() {
        const P = D.place, g = G();
        const ring = (P.ring || []).map(([lat, lng]) => ({ lat, lng }));
        if (P.bounds) {
            map.fitBounds(new g.LatLngBounds({ lat: P.bounds[0], lng: P.bounds[1] }, { lat: P.bounds[2], lng: P.bounds[3] }), { top: 70, bottom: 200, left: 40, right: 60 });
        } else if (ring.length >= 3) {
            const b = new g.LatLngBounds();
            ring.forEach((p) => b.extend(p));
            map.fitBounds(b, { top: 80, bottom: 210, left: 40, right: 60 });
        } else {
            map.setCenter({ lat: P.lat, lng: P.lng });
            map.setZoom({ pin: 16, barangay: 15, town: 13, province: 10 }[P.how] || 15);
            return;
        }
        // One small field must not zoom past what the satellite pictures hold.
        g.event.addListenerOnce(map, 'idle', () => { if (map.getZoom() > 18) map.setZoom(18); });
    }
    $('hmFitBtn').addEventListener('click', () => { if (D) { map.setHeading?.(0); map.setTilt?.(0); fitPlace(); } });
    // The whole square, clear of the panel at the bottom and the buttons at the side.
    const panelRoom = () => ($('hmPanel').classList.contains('is-off') ? 20 : Math.round($('hmPanel').offsetHeight + 40));
    $('hmAreaBtn').addEventListener('click', () => { if (imgBounds) map.fitBounds(imgBounds, { top: 50, bottom: panelRoom(), left: 10, right: 60 }); });
    $('hmTypeBtn').addEventListener('click', () => {
        if (!map) return;
        const road = map.getMapTypeId() !== 'roadmap';
        map.setMapTypeId(road ? 'roadmap' : 'hybrid');
        $('hmTypeBtn').classList.toggle('is-on', road);
        $('hmTypeBtn').setAttribute('aria-label', road ? 'Show the satellite map' : 'Show the street map');
    });
    $('hmInfoBtn').addEventListener('click', () => window.openSheet?.('hmInfo'));

    // A tap reads the temperature at that spot, for a few seconds.
    function tapRead(ll) {
        if (!D || !FL.length) return;
        const t = valueAt(FL[st.i].vals, ll.lat(), ll.lng());
        clearTimeout(tapTimer);
        if (t == null) { window.toast?.('That spot is outside the heat area. Zoom in closer to your lot.', 'info'); return; }
        if (!tapMark) tapMark = new (G().Marker)({ map, clickable: false, zIndex: 1100 });
        tapMark.setPosition(ll);
        tapMark.setIcon(label(t, false));
        tapMark.setMap(map);
        tapTimer = setTimeout(() => tapMark && tapMark.setMap(null), 4500);
    }
    // Zoomed out, the square says its numbers at every other point.
    function paintGridMarks() {
        if (!map || !grid || !FL.length) return;
        const z = map.getZoom() || 0, show = z <= 11;
        const N = grid.size, vals = FL[st.i].vals;
        if (!show) { gridMarks.forEach((m) => m.setMap(null)); return; }
        const pts = [];
        const mid = (N - 1) / 2;
        // Every other point, but not the middle one: the lot's own badge stands there.
        for (let r = 0; r < N; r += 2) for (let c = 0; c < N; c += 2) if (!(r === mid && c === mid && D.place.kind === 'lot')) pts.push([r, c]);
        pts.forEach(([r, c], k) => {
            let m = gridMarks[k];
            if (!m) { m = new (G().Marker)({ clickable: false, zIndex: 800 }); gridMarks[k] = m; }
            m.setPosition({ lat: grid.lat0 + r * grid.step, lng: grid.lng0 + c * grid.step });
            m.setIcon(label(vals[r * N + c], true));
            if (m.getMap() !== map) m.setMap(map);
        });
    }

    /* ================= the forecast and guides ================= */
    const ICON = {
        people: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5a2.75 2.75 0 100-5.5 2.75 2.75 0 000 5.5zM6.5 21v-4.5a5.5 5.5 0 0111 0V21M17.5 4.5l1.5-1.5M19.5 7.5h2"/>',
        spray: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 8h5l1 3v9.5a1 1 0 01-1 1H9a1 1 0 01-1-1V11l1-3zM10 8V5h3M13 5h3l2 2M19 3.5v.01M21 6v.01M19.5 9v.01"/>',
        water: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3s6 6.5 6 11a6 6 0 01-12 0c0-4.5 6-11 6-11z"/><path stroke-linecap="round" d="M9.5 14.5a2.5 2.5 0 002.5 2.5"/>',
        fert: '<path stroke-linecap="round" stroke-linejoin="round" d="M7 7.5h10l2 12.5a1 1 0 01-1 1H6a1 1 0 01-1-1L7 7.5zM9 7.5V5.5a3 3 0 016 0v2M9.5 13h5M10.5 16.5h3"/>',
        seedling: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8M12 13c0-4 3-6.5 7.5-6.5 0 4-3 6.5-7.5 6.5zM12 15.5c0-3-2.2-5.5-6.5-5.5 0 3 2.2 5.5 6.5 5.5zM8 21h8"/>',
        moon: '<path stroke-linecap="round" stroke-linejoin="round" d="M20 14.5A8 8 0 119.5 4a6.5 6.5 0 0010.5 10.5z"/>',
        animal: '<circle cx="6" cy="10" r="2"/><circle cx="10" cy="5.5" r="2"/><circle cx="14" cy="5.5" r="2"/><circle cx="18" cy="10" r="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 11.5c-3 0-5.5 3.5-5.5 6a2.5 2.5 0 003.5 2.2 4.6 4.6 0 014 0 2.5 2.5 0 003.5-2.2c0-2.5-2.5-6-5.5-6z"/>',
        ok: '<circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 12.5l2.7 2.7L16.5 9.5"/>',
        rain: '<path stroke-linecap="round" stroke-linejoin="round" d="M7 15a4 4 0 01-.5-7.97A5.5 5.5 0 0117 7.5a3.75 3.75 0 01.5 7.5H7zM8 18.5l-1 2M12 18.5l-1 2M16 18.5l-1 2"/>',
        crop: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21V9M12 9c0-3 1.5-5 4-6M12 12c-2-2.5-5-3-7-2.5.5 2.5 3.5 4.5 7 4.5M12 16c2-2.5 5-3 7-2.5-.5 2.5-3.5 4.5-7 4.5"/>',
        sun: '<circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2.5v2m0 15v2M2.5 12h2m15 0h2M5.3 5.3l1.4 1.4m10.6 10.6l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4"/>',
    };
    const ico = (k) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">' + (ICON[k] || ICON.sun) + '</svg>';
    const uvWord = (u) => u == null ? 'Not given' : u >= 11 ? 'Extreme' : u >= 8 ? 'Very high' : u >= 6 ? 'High' : u >= 3 ? 'Moderate' : 'Low';
    const bold = (s) => esc(s).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>');

    /* ---- the work, drawn the same way everywhere: good, fair, poor, each with its mark, never color alone ---- */
    const RATE = { g: 'Good', f: 'Fair', p: 'Poor', x: 'Past', n: '' };
    const MARK = { g: '<path d="M6 12.5l4 4L18 8"/>', f: '<path d="M7 12h10"/>', p: '<path d="M7.5 7.5l9 9m0-9l-9 9"/>' };
    const mark = (r) => (MARK[r] ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + MARK[r] + '</svg>' : '');
    const SHORT = { plant: 'Planting', spray: 'Spraying', fert: 'Fertilizing', labor: 'Field work', water: 'Watering', dry: 'Drying', plantRain: 'Plant, rain only', plantWater: 'Plant, irrigated' };
    const KEY = '<div class="hm-key" aria-hidden="true">' + ['g', 'f', 'p'].map((r) => '<span><i class="r-' + r + '">' + mark(r) + '</i>' + RATE[r] + '</span>').join('') + '</div>';
    const narrow = () => ($('hmView').clientWidth || innerWidth) < 560;
    const hourShort = (h) => (h === 12 ? 'noon' : (h % 12 || 12) + (h < 12 ? 'a' : 'p'));

    // A heat grid: rows down the side, columns across. cell(ri, ci) -> { tag, cls, style, html, attrs, label }.
    function heatGrid(rows, cols, cell, o) {
        o = o || {};
        let h = '<div class="hm-hgw' + (o.scroll ? ' is-scroll' : '') + '"' + (o.id ? ' id="' + o.id + '"' : '') + '><div class="hm-hg' + (o.stack ? ' is-stack' : '') + '" role="table" aria-label="' + esc(o.label || '') + '" style="--n:' + cols.length
            + ';--lab:' + (o.lab || '7rem') + ';--cw:' + (o.cw || '1.9rem') + ';--cmax:' + (o.cmax || '1fr') + ';--ch:' + (o.ch || '2rem') + ';--cf:' + (o.cf || '.7rem') + '">';
        h += '<span class="hm-hl" role="columnheader"></span>' + cols.map((c) => '<span class="hm-hh' + (c.on ? ' is-on' : '') + '" role="columnheader">' + c.html + '</span>').join('');
        rows.forEach((r, ri) => {
            h += '<span class="hm-hl' + (r.wrap ? ' is-wrap' : '') + '" role="rowheader">' + r.html + '</span>';
            cols.forEach((c, ci) => {
                const x = cell(ri, ci) || {};
                const tag = x.tag || 'span';
                h += '<' + tag + (tag === 'button' ? ' type="button"' : '') + ' class="hm-hc ' + (x.cls || '') + '" role="cell" style="--d:' + Math.min(60, ri + ci) + ';' + (x.style || '') + '" ' + (x.attrs || '')
                    + (x.label ? ' aria-label="' + esc(x.label) + '"' + (tag === 'span' ? ' title="' + esc(x.label) + '"' : '') : '') + '>' + (x.html ?? '') + '</' + tag + '>';
            });
        });

        return h + '</div></div>';
    }

    // One hue for amounts, faint for a little and strong for a lot: rain in blues, hot days in reds, storms in purples.
    // On a dark page the ramp runs from the page's own dark up to bright, so "more" is still the stronger color.
    const SEQ = { blue: ['#eef4fb', '#6fa3dc', '#173d6e'], red: ['#fdeee8', '#e57a3c', '#8f1d12'], purple: ['#f3effa', '#9b7fd0', '#3f2a6b'] };
    const SEQ_DARK = { blue: ['#1b2633', '#2f6db5', '#9cc6f2'], red: ['#2d1a14', '#b8441f', '#f6a27c'], purple: ['#251d33', '#6e52a8', '#c6b2ee'] };
    function seq(name, k) {
        k = Math.max(0, Math.min(1, k || 0));
        const dark = document.documentElement.classList.contains('dark');
        const stops = (dark ? SEQ_DARK : SEQ)[name];
        const [a, b] = k < 0.5 ? [hexRgb(stops[0]), hexRgb(stops[1])] : [hexRgb(stops[1]), hexRgb(stops[2])];
        const f = k < 0.5 ? k * 2 : (k - 0.5) * 2;
        const c = [0, 1, 2].map((i) => Math.round(a[i] + (b[i] - a[i]) * f));
        const ink = dark ? (k > 0.62 ? '#1d150e' : '#f3f1ec') : (k > 0.55 ? '#fff' : '#1d150e');

        return 'background:rgb(' + c.join(',') + ');color:' + ink;
    }
    const tempCell = (t) => (t == null ? 'background:transparent;color:inherit' : 'background:' + cssOf(t) + ';color:' + inkOn(t));

    /* ---- the days and hours ---- */
    function dayHours(date) {
        const L = D.week.lot.hours, out = [];
        const base = Math.round((utcOf(date) - utcOf(L.from, hourOf(L.from))) / 36e5);
        for (let h = 0; h < 24; h++) { const k = base + h; if (k >= 0 && k < L.t.length && L.t[k] != null) out.push({ h, t: L.t[k], f: L.f[k], rh: L.h[k], pp: L.pp ? L.pp[k] : null, p: L.p ? L.p[k] : null, w: L.w ? L.w[k] : null }); }
        return out;
    }
    const workKeys = () => Object.keys(D.work || {});
    const bestDays = (k) => D.week.lot.days.filter((d) => d.work && d.work[k] && d.work[k].r === 'g').map((d) => (d.date === D.today ? 'Today' : DOW[dowOf(d.date)]));

    function paintDays() {
        const L = D.week.lot, days = L.days || [];
        if (!days.length) { $('hmDays').innerHTML = '<div class="hm-card"><h3>No forecast for this place yet</h3><p>Please try again in a minute.</p></div>'; return; }
        st.day = Math.max(0, Math.min(days.length - 1, st.day));
        const hot = days.reduce((a, d) => ((d.max ?? -99) > (a.max ?? -99) ? d : a), days[0]);
        const warmN = days.reduce((a, d) => ((d.min ?? -99) > (a.min ?? -99) ? d : a), days[0]);
        const feels = days.reduce((a, d) => ((d.fmax ?? -99) > (a.fmax ?? -99) ? d : a), days[0]);
        const P = D.place;
        const sprayDays = bestDays('spray'), plantDays = bestDays('plant');
        const keys = workKeys();
        $('hmDays').innerHTML =
            '<section class="hm-sum"><small>The week at ' + esc(P.name) + '</small>'
            + '<b>The hottest is ' + esc(dayWord(hot.date, true)) + ', up to ' + deg(hot.max) + 'C' + (hot.guide?.peak ? ' around ' + esc(hot.guide.peak) : '') + '.</b>'
            + '<p>' + (sprayDays.length ? 'Good spray days: ' + esc(sprayDays.join(', ')) + '. ' : 'No easy spray day this week: look for the best hours below. ')
            + (plantDays.length ? 'Good planting days: ' + esc(plantDays.join(', ')) + '.' : '') + '</p>'
            + '<div class="hm-sum-tags"><span>Warmest night ' + deg(warmN.min) + ', ' + esc(dayWord(warmN.date)) + '</span>'
            + (feels.fmax != null ? '<span>Feels like up to ' + deg(feels.fmax) + 'C, ' + esc(dayWord(feels.date)) + '</span>' : '')
            + (P.how && !['pin', 'boundary', 'map'].includes(P.how) ? '<span>No pin yet, so read at its ' + esc(P.how) + '</span>' : '') + '</div></section>'
            // The week's work at a glance: every kind of work against every day.
            + '<section class="hm-card"><h3>The best days for the work</h3><p>Tap a day to see its best hours.</p>'
            + heatGrid(keys.map((k) => ({ html: ico(D.work[k].icon) + '<b>' + esc(SHORT[k] || D.work[k].name) + '</b>' })),
                days.map((d, i) => ({ on: i === st.day, html: esc(d.date === D.today ? 'Today' : DOW[dowOf(d.date)]) + '<small>' + ymd(d.date)[2] + '</small>' })),
                (ri, ci) => {
                    const k = keys[ri], d = days[ci], w = (d.work || {})[k] || {}, r = w.r || 'n';
                    return { tag: 'button', cls: 'r-' + r + (ci === st.day ? ' is-col' : ''), attrs: 'data-day="' + ci + '"', html: mark(r),
                        label: (SHORT[k] || '') + ', ' + dayWord(d.date) + ': ' + (RATE[r] || 'no hours left') + (w.best ? ', ' + w.best : '') };
                }, { label: 'The best days for the work', lab: narrow() ? '6.4rem' : '8.5rem', cw: '1.9rem', cmax: '6.5rem', ch: narrow() ? '1.9rem' : '2.2rem' })
            + KEY + '</section>'
            + '<div class="hm-strip" id="hmStrip" role="tablist" aria-label="The days">' + days.map((d, i) => '<button type="button" class="hm-dchip' + (i === st.day ? ' is-on' : '') + '" role="tab" aria-selected="' + (i === st.day) + '" data-day="' + i + '" style="--i:' + i + '">'
                + '<b>' + esc(d.date === D.today ? 'Today' : DOW[dowOf(d.date)]) + '</b><small>' + esc(MON[ymd(d.date)[1] - 1] + ' ' + ymd(d.date)[2]) + '</small>'
                + (window.wxSky ? window.wxSky(window.wxKeyFor ? window.wxKeyFor(d.code, false, d.max) : 'cloudy', 30) : '')
                + '<span class="hm-t">' + deg(d.max) + ' <small>' + deg(d.min) + '</small></span>'
                + '<span class="hm-bar" style="background:' + (d.max != null ? cssOf(d.max) : '#ccc') + '"></span>'
                + '<em>' + esc(d.guide?.tag || '') + '</em></button>').join('') + '</div>'
            + '<section class="hm-card" id="hmDayCard"></section>'
            + '<section class="hm-card"><h3>The week, high and low</h3><p>Each bar runs from the night\'s low to the day\'s high, in the map\'s colors.</p><div class="hm-week" id="hmWeek"></div></section>';
        paintWeek();
        paintDay();
    }
    function paintWeek() {
        const days = D.week.lot.days;
        const lo = Math.floor(Math.min(...days.map((d) => d.min ?? 99)) - 1), hi = Math.ceil(Math.max(...days.map((d) => d.max ?? -99)) + 1);
        const x = (v) => (v - lo) / Math.max(1, hi - lo) * 100;
        $('hmWeek').innerHTML = days.map((d, i) => '<div class="hm-wrow"><b>' + esc(dayWord(d.date)) + '</b><div class="hm-wtrack" role="img" aria-label="' + esc(dayWord(d.date) + ': low ' + deg(d.min) + 'C, high ' + deg(d.max) + 'C') + '">'
            + (d.min != null && d.max != null ? '<span class="hm-wbar" style="--i:' + i + ';left:' + x(d.min) + '%;width:' + Math.max(2, x(d.max) - x(d.min)) + '%;background:linear-gradient(90deg,' + [0, .25, .5, .75, 1].map((k) => cssOf(d.min + (d.max - d.min) * k)).join(',') + ')"></span>' : '')
            + '</div><em>' + deg(d.max) + '<small> ' + deg(d.min) + '</small></em></div>').join('');
    }
    function paintDay() {
        const d = D.week.lot.days[st.day];
        if (!d) return;
        const g = d.guide || {}, lv = g.level || 'mild';
        const hours = dayHours(d.date);
        const peakH = hours.reduce((a, x) => (x.t > (a?.t ?? -99) ? x : a), null);
        const keys = workKeys();
        $('hmDayCard').innerHTML =
            '<div class="hm-dtop"><h3>' + (window.wxSky ? window.wxSky(window.wxKeyFor ? window.wxKeyFor(d.code, false, d.max) : 'cloudy', 40) : '')
            + '<span>' + esc(dayWord(d.date, true)) + '<small>' + esc(dateLong(d.date)) + '</small></span></h3>'
            + '<span class="hm-lv lv-' + esc(lv) + '">' + ico('sun') + esc(g.title || '') + '</span></div>'
            + '<p class="hm-say">' + esc(g.say || '') + (g.peak ? ' The heat peaks around <b>' + esc(g.peak) + '</b>.' : '') + '</p>'
            + '<div class="hm-stats">'
            + '<div class="hm-stat"><small>High</small><b>' + deg(d.max) + 'C</b><em>' + esc(g.peak ? 'around ' + g.peak : '') + '</em></div>'
            + '<div class="hm-stat"><small>Low</small><b>' + deg(d.min) + 'C</b><em>at night</em></div>'
            + '<div class="hm-stat"><small>Feels like' + (window.aneeTl ? window.aneeTl.flag('feels-like') : '') + '</small><b>' + deg(d.fmax) + 'C</b><em><span class="hm-lv pp-' + esc(g.people?.key || 'ok') + '" style="padding:.05rem .45rem;font-size:.66rem">' + esc(g.people?.words || '') + '</span></em></div>'
            + '<div class="hm-stat"><small>Humidity</small><b>' + (peakH && peakH.rh != null ? peakH.rh + '%' : '--') + '</b><em>at the hottest hour</em></div>'
            + '<div class="hm-stat"><small>Rain</small><b>' + (d.pop != null ? d.pop + '%' : '--') + '</b><em>' + (d.rain >= 0.5 ? 'about ' + Math.round(d.rain) + ' mm' : (d.pop >= 70 ? 'likely' : d.pop >= 40 ? 'maybe' : 'unlikely')) + '</em></div>'
            + '<div class="hm-stat"><small>Sun strength</small><b>' + esc(uvWord(d.uv)) + '</b><em>' + (d.uv != null ? 'UV ' + Math.round(d.uv) : '') + '</em></div>'
            + '</div>'
            + '<div class="hm-work"><h4>The work on this day</h4>' + keys.map((k, i) => {
                const w = (d.work || {})[k] || {}, r = w.r || 'n';
                return '<div class="hm-wk" style="--i:' + i + '"><i>' + ico(D.work[k].icon) + '</i><div><b>' + esc(D.work[k].name) + '</b><p>' + esc(w.say || '') + '</p></div>'
                    + (r !== 'n' ? '<span class="hm-rate r-' + r + '">' + mark(r) + RATE[r] + '</span>' : '') + '</div>';
            }).join('') + '</div>'
            + '<div class="hm-hours"><h4>Hour by hour</h4>' + hourGrid(d, hours) + KEY
            + '<p class="hm-hnote" id="hmHourNote" aria-live="polite">Tap an hour for its reading.</p></div>'
            + '<div class="hm-chart" id="hmChart"></div>'
            + '<div class="hm-ckey"><span><i></i>Temperature</span><span><i class="is-f"></i>Feels like</span></div>'
            + ((g.lines || []).length ? '<div class="hm-guides"><h4>Heat notes</h4>' + g.lines.map((l, i) => '<div class="hm-g" style="--i:' + i + '"><i>' + ico(l.i) + '</i><p><b>' + esc(l.b) + '</b> ' + esc(l.t) + '</p></div>').join('') + '</div>' : '')
            + '<div class="hm-dacts"><button type="button" class="btn btn-cta is-go" data-onmap>See this day on the map</button></div>';
        drawChart();
    }
    // The day's hours from 6 AM to 6 PM: the temperature, what it feels like, the rain chance, then every kind of work.
    function hourGrid(d, hours) {
        const keys = workKeys(), any = (d.work || {})[keys[0]] || {}, from = any.from ?? 6, n = (any.hours || '').length || 13;
        const at = (h) => hours.find((x) => x.h === h) || {};
        const isPast = (h) => d.date === D.today && h < (D.nowHour ?? -1);
        const cols = Array.from({ length: n }, (_, i) => ({ html: (from + i) % 3 === 0 ? esc(hourShort(from + i)) : '' }));
        const rows = [{ k: 't', html: '<b>Temperature</b>' }, { k: 'f', html: '<b>Feels like</b>' }, { k: 'pp', html: '<b>Rain chance</b>' }]
            .concat(keys.map((k) => ({ k, html: ico(D.work[k].icon) + '<b>' + esc(SHORT[k] || D.work[k].name) + '</b>' })));
        hourGridData = { d, hours, keys, from, rows };

        return heatGrid(rows, cols, (ri, ci) => {
            const h = from + ci, x = at(h), row = rows[ri], past = isPast(h) ? ' is-past' : '';
            const a = 'data-hr="' + h + '" data-row="' + ri + '"';
            if (row.k === 't' || row.k === 'f') { const v = x[row.k]; return { tag: 'button', cls: past.trim(), attrs: a, style: tempCell(v), html: v == null ? '' : Math.round(v), label: hourWord(h) + ', ' + (row.k === 't' ? 'temperature ' : 'feels like ') + deg(v) + 'C' }; }
            if (row.k === 'pp') { const v = x.pp; return { tag: 'button', cls: past.trim(), attrs: a, style: seq('blue', (v || 0) / 100), html: v == null ? '' : v, label: hourWord(h) + ', rain chance ' + (v ?? '--') + '%' }; }
            const w = (d.work || {})[row.k] || {}, r = (w.hours || '')[ci] || 'n';
            return { tag: 'button', cls: 'r-' + r + past, attrs: a, html: mark(r), label: hourWord(h) + ', ' + (SHORT[row.k] || '') + ': ' + (RATE[r] || 'not its hours') + ((w.why || [])[ci] ? ', ' + w.why[ci] : '') };
        }, { label: 'Hour by hour on ' + dayWord(d.date), lab: narrow() ? '5.6rem' : '8rem', cw: narrow() ? '1.05rem' : '1.7rem', ch: narrow() ? '1.55rem' : '1.9rem', cf: narrow() ? '.58rem' : '.68rem' });
    }
    let hourGridData = null;
    function hourNote(btn) {
        const G = hourGridData;
        if (!G || !btn) return;
        const h = Number(btn.dataset.hr), row = G.rows[Number(btn.dataset.row)], x = G.hours.find((q) => q.h === h) || {};
        const ci = h - G.from;
        let say = '<b>' + esc(hourWord(h)) + '</b> · ' + deg(x.t) + 'C, feels like ' + deg(x.f) + 'C'
            + (x.rh != null ? ', humidity ' + x.rh + '%' : '') + (x.pp != null ? ', rain chance ' + x.pp + '%' : '') + (x.w != null ? ', wind ' + x.w + ' km/h' : '') + '.';
        if (row && G.keys.includes(row.k)) {
            const w = (G.d.work || {})[row.k] || {}, r = (w.hours || '')[ci] || 'n';
            say = '<b>' + esc(hourWord(h)) + ', ' + esc((D.work[row.k] || {}).name || '') + ': ' + esc(RATE[r] || 'not its hours') + '.</b> '
                + (w.why && w.why[ci] ? esc(w.why[ci].charAt(0).toUpperCase() + w.why[ci].slice(1)) + '. ' : (r === 'g' ? 'A good hour for it. ' : '')) + say.replace(/^<b>[^<]*<\/b> · /, '');
        }
        const el = $('hmHourNote');
        el.innerHTML = say;
        el.classList.remove('is-fresh'); void el.offsetWidth; el.classList.add('is-fresh');
        document.querySelectorAll('#hmDayCard .hm-hc.is-pick').forEach((c) => c.classList.remove('is-pick'));
        btn.classList.add('is-pick');
    }
    $('hmDays').addEventListener('click', (e) => {
        const hr = e.target.closest('[data-hr]');
        if (hr) { hourNote(hr); return; }
        const c = e.target.closest('[data-day]');
        if (c) {
            st.day = Number(c.dataset.day);
            const fromGrid = !!c.closest('.hm-hg');
            paintDays();
            if (fromGrid) $('hmDayCard')?.scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'start' });
            return;
        }
        if (e.target.closest('[data-onmap]')) {
            const date = D.week.lot.days[st.day]?.date;
            setTab('map');
            if (st.mode === 'hour') setMode('hour', false);
            else { const i = FL.findIndex((f) => f.date === date); if (i >= 0) showFrame(i); }
        }
    });

    /* ---- the months: twenty years of the place's weather, and the work it suits ---- */
    let Y = null, yearFor = null, yearTry = 0, yearT = 0, yearView = 'months', yearPick = null;
    const MONS = MON;
    const wantNow = () => (st.src === 'lot' ? 'lot=' + st.lot?.id : 'map=' + st.map?.id);
    async function openMonths() {
        const want = wantNow();
        clearTimeout(yearT);
        if (Y && yearFor === want) { paintMonths(); return; }
        if (yearFor !== want) { yearTry = 0; yearPick = null; }
        if (!Y || yearFor !== want) $('hmMonths').innerHTML = '<section class="hm-card"><div class="hm-wait2">' + document.querySelector('.hm-therm').outerHTML + '<div><h3>Reading twenty years of weather…</h3><p>Every week since ' + (new Date().getFullYear() - 20) + ' at this place.</p></div></div></section>';
        let r;
        try {
            r = await window.api(U.year + '?' + want);
        } catch (err) {
            $('hmMonths').innerHTML = '<section class="hm-card"><h3>The year could not be read just now</h3><p>' + esc(err.message) + '</p><div class="hm-dacts"><button type="button" class="btn btn-white" data-year-retry>Try again</button></div></section>';
            return;
        }
        if (wantNow() !== want) return;
        if (r.data.year && r.data.year.pending) {
            yearTry++;
            $('hmMonths').innerHTML = '<section class="hm-card"><div class="hm-wait2">' + document.querySelector('.hm-therm').outerHTML + '<div><h3>Gathering twenty years of weather for this place</h3><p>It is the first time anyone looks here, so it takes about a minute. This page fills in by itself.</p></div></div></section>';
            if (yearTry < 10 && st.tab === 'months') yearT = setTimeout(openMonths, 15000);
            return;
        }
        Y = r.data;
        yearFor = want;
        paintMonths();
    }
    function paintMonths() {
        const M = Y.year || {}, months = M.months || [];
        if (!months.length) { $('hmMonths').innerHTML = '<section class="hm-card"><h3>No weather history for this place yet</h3><p>Please check again later.</p></section>'; return; }
        const cur = Y.month, F = M.facts || {}, wk = Object.keys(Y.work || {});
        const mName = (m) => MONS[m - 1];
        const longM = (m) => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'][m - 1];
        const nar = narrow();
        const monthCols = months.map((m) => ({ on: m.m === cur, html: nar ? esc(mName(m.m).charAt(0)) : esc(mName(m.m)) }));
        const work = M.work || {};
        let h = '<section class="hm-sum hm-yr"><small>The year at ' + esc(Y.place.name) + '</small>'
            + '<b>' + esc(longM(F.hottest?.m || 4)) + ' is the hottest, ' + esc(longM(F.wettest?.m || 8)) + ' the wettest.</b>'
            + '<p>Twenty years of weather here, ' + esc(M.span || '') + ', month by month: when the heat, the rain and the storms come, and the months each kind of work goes well.</p>'
            + '<div class="hm-sum-tags">'
            + (F.hottest ? '<span>Hottest: ' + esc(mName(F.hottest.m)) + ', highs about ' + Math.round(F.hottest.v) + '°C</span>' : '')
            + (F.coolest ? '<span>Coolest: ' + esc(mName(F.coolest.m)) + ', about ' + Math.round(F.coolest.v) + '°C</span>' : '')
            + (F.wettest ? '<span>Wettest: ' + esc(mName(F.wettest.m)) + ', about ' + F.wettest.v + ' mm</span>' : '')
            + (F.driest ? '<span>Driest: ' + esc(mName(F.driest.m)) + ', about ' + F.driest.v + ' mm</span>' : '')
            + ((F.storms || []).length ? '<span>Storm months: ' + esc(F.storms.map(mName).join(', ')) + '</span>' : '') + '</div></section>';

        // The work, month by month.
        h += '<section class="hm-card"><h3>The best months for the work</h3><p>From how hot and how wet each month usually is here. Tap a month for the reason.</p>'
            + heatGrid(wk.map((k) => ({ html: ico(Y.work[k].icon) + '<b>' + esc(SHORT[k] || Y.work[k].name) + '</b>' })), monthCols, (ri, ci) => {
                const k = wk[ri], m = months[ci].m, x = (work[k] || {})[m] || {}, r = x.r || 'n';
                const pick = yearPick && yearPick[0] === k && yearPick[1] === m;
                return { tag: 'button', cls: 'r-' + r + (m === cur ? ' is-col' : '') + (pick ? ' is-pick' : ''), attrs: 'data-mk="' + k + '" data-m="' + m + '"', html: mark(r),
                    label: Y.work[k].name + ' in ' + longM(m) + ': ' + (RATE[r] || '') + '. ' + (x.why || '') };
            }, { label: 'The best months for the work', stack: nar, lab: '9rem', cw: '2rem', ch: nar ? '1.9rem' : '2.1rem' })
            + KEY + '<p class="hm-hnote" id="hmMonthNote" aria-live="polite">' + monthNote() + '</p>'
            + '<div class="hm-best">' + wk.map((k) => '<div><b>' + esc(Y.work[k].name) + '</b><span>' + ((M.best || {})[k] || []).map((m) => '<em>' + esc(mName(m)) + '</em>').join('') + (((M.best || {})[k] || []).length ? '' : '<i>No month is easy: pick the best days in Days and hours.</i>') + '</span></div>').join('') + '</div></section>';

        // The weather, month by month or week by week.
        h += '<section class="hm-card"><div class="hm-sec-h"><h3>The weather here</h3><div class="hm-seg" role="group" aria-label="Months or weeks">'
            + '<button type="button" data-yv="months" aria-pressed="' + (yearView === 'months') + '">Months</button><button type="button" data-yv="weeks" aria-pressed="' + (yearView === 'weeks') + '">Weeks</button></div></div>'
            + '<p>The usual of the last twenty years. Each square says its number; the deeper the color, the more of it.</p>'
            + (yearView === 'weeks' ? weeksGrid(M) : monthsWeather(months, monthCols, nar))
            + '<p class="hm-credit">From the weather records of the last twenty years for the 25 km around this place. A usual year: any one year can be wetter, drier or hotter.</p></section>';
        $('hmMonths').innerHTML = h;
        if (yearView === 'weeks') {
            const box = $('hmWeeksBox'), on = box && box.querySelector('.hm-hh.is-on');
            if (box && on) box.scrollLeft = Math.max(0, on.offsetLeft - box.clientWidth / 2);
        }
    }
    function monthNote() {
        if (!yearPick || !Y) return 'Tap a square to see why.';
        const [k, m] = yearPick, x = ((Y.year.work || {})[k] || {})[m] || {};
        const longM = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'][m - 1];
        return '<b>' + esc(Y.work[k].name) + ' in ' + esc(longM) + ': ' + esc(RATE[x.r] || '') + '.</b> ' + esc(x.why || '');
    }
    const WROWS = [
        { k: 'high', name: 'Hottest of the day', unit: '°C', paint: (v) => tempCell(v), fmt: (v) => Math.round(v) },
        { k: 'mean', name: 'Day average', unit: '°C', paint: (v) => tempCell(v), fmt: (v) => Math.round(v) },
        { k: 'hot35', name: 'Days at 35°C or more', unit: 'days', paint: (v, mx) => seq('red', v / Math.max(4, mx)), fmt: (v) => (v < 0.5 ? '0' : Math.round(v)) },
        { k: 'rain', name: 'Rain', unit: 'mm', paint: (v, mx) => seq('blue', v / Math.max(1, mx)), fmt: (v) => Math.round(v) },
        { k: 'rainy', name: 'Rainy days', unit: 'days', paint: (v, mx) => seq('blue', v / Math.max(1, mx)), fmt: (v) => Math.round(v) },
        { k: 'storms', name: 'Storm years', unit: 'of 20', paint: (v) => seq('purple', v / 8), fmt: (v) => v, months: true },
    ];
    function monthsWeather(months, cols, nar) {
        const rows = WROWS.filter((r) => months.some((m) => m[r.k] != null));
        return heatGrid(rows.map((r) => ({ wrap: true, html: '<b>' + esc(r.name) + '<small>' + esc(r.unit) + '</small></b>' })), cols, (ri, ci) => {
            const r = rows[ri], v = months[ci][r.k], mx = Math.max(...months.map((m) => m[r.k] || 0));
            return { style: v == null ? '' : r.paint(v, mx), cls: months[ci].m === Y.month ? 'is-col' : '', html: v == null ? '' : r.fmt(v), label: r.name + ', ' + MONS[months[ci].m - 1] + ': ' + (v == null ? 'none' : r.fmt(v) + ' ' + r.unit) };
        }, { label: 'The weather month by month', stack: nar, lab: '9rem', cw: '2rem', ch: nar ? '1.9rem' : '2.1rem', cf: nar ? '.6rem' : '.7rem' });
    }
    function weeksGrid(M) {
        const weeks = M.weeks || [], rows = WROWS.filter((r) => !r.months && weeks.some((w) => w[r.k] != null));
        const thisWeek = Math.min(52, Math.floor((Date.now() - Date.UTC(new Date().getFullYear(), 0, 1)) / 864e5 / 7) + 1);
        const unitW = { hot35: 'days a week', rain: 'mm a week', rainy: 'days a week' };
        const cols = weeks.map((w, i) => ({ on: w.w === thisWeek, html: i === 0 || weeks[i - 1].m !== w.m ? esc(MONS[w.m - 1]) : '' }));
        return heatGrid(rows.map((r) => ({ wrap: true, html: '<b>' + esc(r.name) + '<small>' + esc(unitW[r.k] || r.unit) + '</small></b>' })), cols, (ri, ci) => {
            const r = rows[ri], v = weeks[ci][r.k], mx = Math.max(...weeks.map((w) => w[r.k] || 0));
            return { style: v == null ? '' : r.paint(v, mx), cls: weeks[ci].w === thisWeek ? 'is-col' : '', html: v == null ? '' : r.fmt(v), label: r.name + ', week ' + weeks[ci].w + ' (' + MONS[weeks[ci].m - 1] + '): ' + (v == null ? 'none' : r.fmt(v) + ' ' + (unitW[r.k] || r.unit)) };
        }, { label: 'The weather week by week', scroll: true, id: 'hmWeeksBox', lab: narrow() ? '6.2rem' : '9rem', cw: '1.6rem', ch: '1.9rem', cf: '.6rem' });
    }
    $('hmMonths').addEventListener('click', (e) => {
        if (e.target.closest('[data-year-retry]')) { openMonths(); return; }
        const v = e.target.closest('[data-yv]');
        if (v) { yearView = v.dataset.yv; paintMonths(); return; }
        const c = e.target.closest('[data-mk]');
        if (c) {
            yearPick = [c.dataset.mk, Number(c.dataset.m)];
            $('hmMonths').querySelectorAll('.hm-hc.is-pick').forEach((x) => x.classList.remove('is-pick'));
            c.classList.add('is-pick');
            const el = $('hmMonthNote');
            el.innerHTML = monthNote();
            el.classList.remove('is-fresh'); void el.offsetWidth; el.classList.add('is-fresh');
        }
    });
    // The grids change shape between a phone and a wider screen.
    let gridW = 0, gridT = 0;
    addEventListener('resize', () => {
        clearTimeout(gridT);
        gridT = setTimeout(() => {
            const w = narrow();
            if (w === gridW) return;
            gridW = w;
            if (st.tab === 'days' && D) paintDays();
            if (st.tab === 'months' && Y) paintMonths();
        }, 200);
    });
    gridW = narrow();

    // One day, hour by hour: the temperature and what it feels like, with the 35°C line that hurts flowering crops.
    function drawChart() {
        const box = $('hmChart');
        const d = D && D.week.lot.days[st.day];
        if (!box || !d) return;
        const hs = dayHours(d.date);
        if (hs.length < 2) { box.innerHTML = ''; return; }
        const Wd = Math.max(300, Math.round(box.clientWidth || 600)), Hd = Wd < 480 ? 210 : 240;
        const pl = 34, pr = 10, pt = 14, pb = 26;
        const all = hs.flatMap((x) => [x.t, x.f]).filter((v) => v != null);
        let lo = Math.floor(Math.min(...all)) - 1, hi = Math.ceil(Math.max(...all)) + 1;
        if (hi >= 31 && hi < 36) hi = 36;
        const sx = (h) => pl + h / 23 * (Wd - pl - pr), sy = (v) => pt + (hi - v) / (hi - lo) * (Hd - pt - pb);
        const span = hi - lo, stepY = span > 16 ? 5 : span > 8 ? 2 : 1;
        let g = '';
        for (let v = Math.ceil(lo / stepY) * stepY; v <= hi; v += stepY) g += '<line class="g" x1="' + pl + '" x2="' + (Wd - pr) + '" y1="' + sy(v) + '" y2="' + sy(v) + '"/><text x="' + (pl - 6) + '" y="' + (sy(v) + 4) + '" text-anchor="end">' + v + '°</text>';
        [0, 6, 12, 18, 23].forEach((h) => { g += '<text x="' + sx(h) + '" y="' + (Hd - 6) + '" text-anchor="' + (h === 0 ? 'start' : h === 23 ? 'end' : 'middle') + '">' + hourWord(h) + '</text>'; });
        // Its words sit at the night end, where the curves run low and never cross them.
        const ref = hi >= 35 && lo <= 35 ? '<line class="ref" x1="' + pl + '" x2="' + (Wd - pr) + '" y1="' + sy(35) + '" y2="' + sy(35) + '"/><text class="is-ref" x="' + (pl + 6) + '" y="' + (sy(35) - 5) + '">35°C, flowers suffer</text>' : '';
        const line = (k) => hs.filter((x) => x[k] != null).map((x, i) => (i ? 'L' : 'M') + sx(x.h).toFixed(1) + ' ' + sy(x[k]).toFixed(1)).join(' ');
        const lt = line('t'), lf = line('f');
        const area = lt + ' L' + sx(hs[hs.length - 1].h).toFixed(1) + ' ' + (Hd - pb) + ' L' + sx(hs[0].h).toFixed(1) + ' ' + (Hd - pb) + ' Z';
        const nowH = d.date === D.today ? new Date().getHours() + new Date().getMinutes() / 60 : null;
        const now = nowH != null ? '<line class="now" x1="' + sx(nowH) + '" x2="' + sx(nowH) + '" y1="' + pt + '" y2="' + (Hd - pb) + '"/><text x="' + (sx(nowH) + 4) + '" y="' + (pt + 9) + '">Now</text>' : '';
        const pk = hs.reduce((a, x) => (x.t > a.t ? x : a), hs[0]);
        box.innerHTML = '<svg viewBox="0 0 ' + Wd + ' ' + Hd + '" role="img" aria-label="' + esc('The temperature through ' + dayWord(d.date) + ', from ' + deg(d.min) + ' to ' + deg(d.max)) + '">' + g + ref + now
            + '<path class="ar" d="' + area + '"/><path class="lf" d="' + lf + '"/><path class="lt draw" d="' + lt + '" pathLength="1" style="--len:1"/>'
            + '<circle class="dot" cx="' + sx(pk.h) + '" cy="' + sy(pk.t) + '" r="4.5" fill="#e57a3c"/><text x="' + sx(pk.h) + '" y="' + (sy(pk.t) - 9) + '" text-anchor="middle" style="fill:var(--color-gray-800);font-weight:800">' + deg(pk.t) + '</text>'
            + '<g class="hm-cross" style="display:none"><line class="cross" y1="' + pt + '" y2="' + (Hd - pb) + '"/><circle class="dot" r="4" fill="#e57a3c"/><circle class="dot" r="3.5" fill="#9b2a20"/></g>'
            + '<rect x="' + pl + '" y="' + pt + '" width="' + (Wd - pl - pr) + '" height="' + (Hd - pt - pb) + '" fill="transparent" data-hit/></svg><div class="hm-tip" hidden></div>';
        const svg = box.querySelector('svg'), cross = svg.querySelector('.hm-cross'), tip = box.querySelector('.hm-tip');
        const [ln, c1, c2] = cross.children;
        const at = (ev) => {
            const r = svg.getBoundingClientRect(), x = (ev.clientX - r.left) / r.width * Wd;
            const h = Math.max(0, Math.min(23, Math.round((x - pl) / (Wd - pl - pr) * 23)));
            const p = hs.find((q) => q.h === h) || hs.reduce((a, q) => (Math.abs(q.h - h) < Math.abs(a.h - h) ? q : a), hs[0]);
            cross.style.display = '';
            ln.setAttribute('x1', sx(p.h)); ln.setAttribute('x2', sx(p.h));
            c1.setAttribute('cx', sx(p.h)); c1.setAttribute('cy', sy(p.t));
            if (p.f != null) { c2.setAttribute('cx', sx(p.h)); c2.setAttribute('cy', sy(p.f)); c2.style.display = ''; } else c2.style.display = 'none';
            tip.innerHTML = '<b>' + esc(hourWord(p.h)) + '</b>' + deg(p.t, 1) + 'C' + (p.f != null ? '<br>Feels like ' + deg(p.f, 1) + 'C' : '') + (p.rh != null ? '<br>Humidity ' + p.rh + '%' : '');
            tip.hidden = false;
            const px = sx(p.h) / Wd * r.width;
            tip.style.left = Math.max(0, Math.min(r.width - tip.offsetWidth, px + (px > r.width / 2 ? -tip.offsetWidth - 10 : 10))) + 'px';
        };
        const off = () => { cross.style.display = 'none'; tip.hidden = true; };
        svg.addEventListener('pointermove', at);
        svg.addEventListener('pointerdown', at);
        svg.addEventListener('pointerleave', off);
        svg.addEventListener('pointercancel', off);
    }

    /* ================= boot ================= */
    // After the app's own script (window.api, the sheets), which loads deferred: DOMContentLoaded waits for it.
    const boot = () => {
        // The lot or map to start on: from the address, else the last one used here. Shown picked; only Next moves on.
        const r0 = recall();
        if (B.map || (!B.lot && r0 && r0.src === 'map')) {
            setSrc('map');
            const want = Number(B.map || r0.id);
            const wait = setInterval(() => { if (st.maps) { clearInterval(wait); const m = st.maps.find((x) => x.id === want); if (m) { st.map = m; paintMapList(); paintNext(); } } }, 120);
            setTimeout(() => clearInterval(wait), 15000);
        }
        mountLots();
        paintNext();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true }); else boot();
})();
</script>
@endpush
