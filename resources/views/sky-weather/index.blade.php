@extends('layouts.app')
@section('title', 'Satellite Weather')
@section('page-title', 'Satellite Weather')
@section('page-subtitle', 'Clouds, rain and typhoons over your farm')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

@section('content')
<style>
    /* ---- SATELLITE WEATHER (2026-10-07) ----------------------------------
       The sky over the farm, played back and fast forwarded; storms drawn
       with their cone; Anee's reading against a lot. House curve throughout,
       held still under reduced motion. */
    :root { --sk-ease: cubic-bezier(.22,1,.36,1); }
    .sk-wrap { max-width: 64rem; margin: 0 auto; }
    .sk-stage { position: relative; border-radius: 1.3rem; overflow: hidden; background: #0b1220; border: 1px solid #1e293b;
        box-shadow: 0 30px 60px -40px rgb(0 0 0 / .9); }
    .sk-map { height: clamp(26rem, 68vh, 44rem); }
    .sk-top { position: absolute; left: .7rem; right: .7rem; top: .7rem; z-index: 3; display: flex; gap: .45rem; align-items: flex-start; pointer-events: none; }
    .sk-top > * { pointer-events: auto; }
    .sk-place { display: inline-flex; align-items: center; gap: .45rem; max-width: 60%; padding: .5rem .8rem; border-radius: 999px; border: 0; cursor: pointer;
        font-size: .82rem; font-weight: 800; color: #fff; background: rgb(15 23 42 / .82); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        box-shadow: 0 8px 20px -10px rgb(0 0 0 / .8); }
    .sk-place span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sk-place svg { flex: none; width: 1rem; height: 1rem; color: #f5c518; }
    .sk-badge { margin-left: auto; display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .8rem; border-radius: 999px; font-size: .78rem; font-weight: 800;
        color: #dcfce7; background: rgb(21 128 61 / .85); box-shadow: 0 8px 20px -10px rgb(0 0 0 / .8); transition: background-color .28s var(--sk-ease); }
    .sk-badge i { width: .55rem; height: .55rem; border-radius: 999px; background: currentColor; }
    .sk-badge.is-warn { color: #fff; background: rgb(220 38 38 / .92); animation: skPulse 1.6s ease-in-out infinite; }
    .sk-badge.is-watch { color: #1a1a1a; background: rgb(245 197 24 / .95); }
    @keyframes skPulse { 50% { box-shadow: 0 0 0 .5rem rgb(220 38 38 / .25), 0 8px 20px -10px rgb(0 0 0 / .8); } }
    .sk-side { position: absolute; left: .7rem; top: 3.6rem; z-index: 3; display: grid; gap: .3rem; }
    .sk-lay { display: flex; align-items: center; gap: .45rem; padding: .42rem .65rem .42rem .45rem; border-radius: .8rem; border: 0; cursor: pointer; text-align: left;
        font-size: .74rem; font-weight: 800; color: #cbd5e1; background: rgb(15 23 42 / .78); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        transition: background-color .28s var(--sk-ease), color .28s var(--sk-ease); }
    .sk-lay b { display: grid; place-items: center; width: 1.6rem; height: 1.6rem; border-radius: .55rem; background: rgb(255 255 255 / .08); }
    .sk-lay svg { width: .95rem; height: .95rem; }
    .sk-lay.is-on { color: #0f172a; background: #f5c518; }
    .sk-lay.is-on b { background: rgb(15 23 42 / .12); }
    .sk-lay:disabled { opacity: .45; cursor: default; }
    @media (max-width: 639.98px) { .sk-lay span { display: none; } .sk-lay { padding: .4rem; } .sk-side { top: 3.4rem; } }
    .sk-bottom { position: absolute; left: .7rem; right: .7rem; bottom: .7rem; z-index: 3; display: grid; gap: .45rem; }
    .sk-bar { display: flex; align-items: center; gap: .55rem; padding: .5rem .65rem; border-radius: 1rem; color: #e2e8f0;
        background: rgb(15 23 42 / .86); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); box-shadow: 0 12px 28px -14px rgb(0 0 0 / .9); }
    .sk-play { flex: none; display: grid; place-items: center; width: 2.3rem; height: 2.3rem; border-radius: 999px; border: 0; cursor: pointer; background: #f5c518; color: #0f172a;
        transition: transform .28s var(--sk-ease); }
    .sk-play:hover { transform: scale(1.06); }
    .sk-play svg { width: 1rem; height: 1rem; }
    .sk-play .i-pause, .sk-bar.is-playing .sk-play .i-play { display: none; }
    .sk-bar.is-playing .sk-play .i-pause { display: block; }
    .sk-modes { flex: none; display: flex; gap: .2rem; padding: .2rem; border-radius: 999px; background: rgb(255 255 255 / .08); }
    .sk-modes button { padding: .3rem .6rem; border-radius: 999px; border: 0; cursor: pointer; font-size: .7rem; font-weight: 800; color: #cbd5e1; background: transparent;
        transition: background-color .28s var(--sk-ease), color .28s var(--sk-ease); }
    .sk-modes button.is-on { background: #e2e8f0; color: #0f172a; }
    .sk-time { flex: 1 1 auto; min-width: 0; display: grid; gap: .25rem; }
    .sk-time b { font-size: .78rem; font-weight: 800; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sk-time input { width: 100%; accent-color: #f5c518; margin: 0; }
    .sk-op { display: none; align-items: center; gap: .35rem; flex: none; font-size: .68rem; font-weight: 800; color: #94a3b8; }
    .sk-op input { width: 5.5rem; accent-color: #f5c518; }
    @media (min-width: 640px) { .sk-op { display: inline-flex; } }
    .sk-op-m { display: flex; align-items: center; gap: .45rem; font-size: .68rem; font-weight: 800; color: #94a3b8; padding: 0 .3rem; }
    .sk-op-m input { flex: 1 1 auto; accent-color: #f5c518; }
    @media (min-width: 640px) { .sk-op-m { display: none; } }
    .sk-read { display: none; gap: .4rem; flex-wrap: wrap; }
    .sk-read.is-on { display: flex; }
    .sk-read span { padding: .3rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 800; color: #e2e8f0; background: rgb(15 23 42 / .82); }
    .sk-read span b { color: #f5c518; }
    .sk-panel { position: absolute; left: .7rem; top: 3.6rem; z-index: 4; width: min(22rem, calc(100% - 1.4rem)); padding: .8rem; border-radius: 1rem; background: var(--color-white);
        border: 1px solid var(--color-gray-200); box-shadow: 0 24px 50px -24px rgb(0 0 0 / .8); opacity: 0; transform: translateY(-6px); pointer-events: none;
        transition: opacity .28s var(--sk-ease), transform .28s var(--sk-ease); }
    .sk-panel.is-on { opacity: 1; transform: none; pointer-events: auto; }
    .sk-panel h4 { font-size: .78rem; font-weight: 800; color: var(--color-gray-500); text-transform: uppercase; letter-spacing: .05em; margin: .5rem 0 .35rem; }
    .sk-panel h4:first-child { margin-top: 0; }
    .sk-opt { display: flex; gap: .55rem; align-items: center; width: 100%; padding: .55rem .6rem; border-radius: .7rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        text-align: left; font-size: .82rem; color: var(--color-gray-800); cursor: pointer; margin-bottom: .3rem; transition: border-color .28s var(--sk-ease); }
    .sk-opt:hover { border-color: var(--color-brand-600); }
    .sk-opt small { display: block; font-size: .72rem; color: var(--color-gray-500); }
    .sk-lots { max-height: 12rem; overflow-y: auto; }
    .sk-eye { position: absolute; width: 22px; height: 22px; margin: -11px 0 0 -11px; border-radius: 999px; background: radial-gradient(circle, #fff 0 20%, #ef4444 22% 55%, transparent 57%);
        pointer-events: none; }
    .sk-eye::before, .sk-eye::after { content: ''; position: absolute; inset: -10px; border-radius: 999px; border: 2px solid rgb(239 68 68 / .8); animation: skRing 2s ease-out infinite; }
    .sk-eye::after { animation-delay: 1s; }
    @keyframes skRing { from { transform: scale(.4); opacity: 1; } to { transform: scale(1.6); opacity: 0; } }
    .sk-eye-label { position: absolute; transform: translate(14px, -50%); white-space: nowrap; padding: .2rem .45rem; border-radius: .45rem; font-size: .7rem; font-weight: 800;
        color: #fff; background: rgb(127 29 29 / .9); pointer-events: none; }

    .sk-grid { display: grid; gap: 1rem; margin-top: 1rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 900px) { .sk-grid { grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr); } }
    .sk-card { border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); padding: 1rem 1.05rem; min-width: 0; }
    .sk-card h3 { display: flex; align-items: center; gap: .45rem; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: var(--color-gray-900); }
    .sk-card h3 svg { width: 1.1rem; height: 1.1rem; color: var(--color-brand-600); }
    .sk-sub { margin-top: .25rem; font-size: .82rem; color: var(--color-gray-500); line-height: 1.5; }
    .sk-storm { margin-top: .6rem; padding: .7rem .8rem; border-radius: .9rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    html.dark .sk-storm { background: #121a0d; border-color: #2b3a1c; }
    .sk-storm b { font-size: .92rem; color: var(--color-gray-900); }
    .sk-storm p { margin-top: .2rem; font-size: .8rem; color: var(--color-gray-600); line-height: 1.5; }
    .sk-km { float: right; font-family: var(--font-heading); font-weight: 800; font-size: 1.05rem; color: var(--color-gray-900); }
    .sk-km.is-near { color: #dc2626; }
    .sk-days { display: grid; grid-template-columns: repeat(10, minmax(2.8rem, 1fr)); gap: .3rem; margin-top: .7rem; overflow-x: auto; padding-bottom: .2rem; }
    .sk-days div { border-radius: .75rem; padding: .45rem .2rem; text-align: center; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); font-size: .66rem; color: var(--color-gray-600); }
    html.dark .sk-days div { background: #121a0d; border-color: #2b3a1c; }
    .sk-days b { display: block; font-size: .72rem; color: var(--color-gray-900); }
    .sk-days i { display: block; margin: .3rem auto .2rem; width: .5rem; border-radius: 999px; background: #4c8ed9; min-height: 2px; }
    .sk-days em { display: block; font-style: normal; font-size: .62rem; color: #b45309; }
    .sk-ask select { margin-top: .6rem; }
    .sk-run { margin-top: .8rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: .5rem; padding: .85rem 1rem; border-radius: .95rem; border: 0;
        font-weight: 800; color: #1a1a1a; background: linear-gradient(135deg, #f7d23a, #f5c518); cursor: pointer; transition: transform .28s var(--sk-ease); }
    .sk-run:hover { transform: translateY(-1px); }
    .sk-run:disabled { opacity: .55; transform: none; cursor: default; }
    .sk-fine { margin-top: .45rem; font-size: .74rem; color: var(--color-gray-500); text-align: center; }
    .sk-rep { margin-top: 1rem; }
    .sk-rep[hidden] { display: none; }
    .sk-rhead { border-radius: 1.1rem; padding: 1rem 1.1rem; color: #e2e8f0; background: radial-gradient(120% 140% at 100% 0%, #1e3a8a 0%, #0f172a 60%); }
    .sk-rhead.r-high, .sk-rhead.r-severe { background: radial-gradient(120% 140% at 100% 0%, #b91c1c 0%, #450a0a 65%); }
    .sk-rhead.r-moderate { background: radial-gradient(120% 140% at 100% 0%, #b45309 0%, #422006 65%); }
    .sk-rhead.r-low { background: radial-gradient(120% 140% at 100% 0%, #15803d 0%, #052e16 65%); }
    .sk-rhead small { font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; opacity: .85; }
    .sk-rhead h3 { margin-top: .2rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.15rem; color: #fff; line-height: 1.3; }
    .sk-rhead p { margin-top: .35rem; font-size: .84rem; line-height: 1.55; opacity: .9; }
    .sk-rtabs { display: flex; gap: .3rem; margin-top: .8rem; overflow-x: auto; scrollbar-width: none; }
    .sk-rtabs::-webkit-scrollbar { display: none; }
    .sk-rtab { flex: none; padding: .42rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--color-gray-600); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; transition: background-color .28s var(--sk-ease), color .28s var(--sk-ease); }
    .sk-rtab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .sk-pane { display: none; margin-top: .7rem; }
    .sk-pane.is-on { display: block; animation: skFade .34s var(--sk-ease) both; }
    @keyframes skFade { from { opacity: 0; transform: translateY(6px); } }
    .sk-pane p, .sk-pane li { font-size: .86rem; line-height: 1.6; color: var(--color-gray-700); }
    .sk-pane p + p { margin-top: .5rem; }
    .sk-acts li { display: flex; gap: .6rem; padding: .55rem 0; border-bottom: 1px dashed var(--color-gray-200); }
    .sk-acts em { flex: none; height: fit-content; font-style: normal; font-size: .66rem; font-weight: 800; text-transform: uppercase; padding: .2rem .5rem; border-radius: 999px; background: #fff1c2; color: #8a5a00; }
    .sk-saved { display: grid; gap: .45rem; margin-top: .6rem; }
    .sk-srow { display: flex; gap: .6rem; align-items: center; width: 100%; padding: .6rem .7rem; border-radius: .85rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        text-align: left; cursor: pointer; font-size: .82rem; color: var(--color-gray-800); }
    .sk-srow i { flex: none; width: .65rem; height: .65rem; border-radius: 999px; background: #22c55e; }
    .sk-srow i.r-moderate { background: #f59e0b; } .sk-srow i.r-high, .sk-srow i.r-severe { background: #ef4444; }
    .sk-srow span { min-width: 0; flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .sk-srow small { flex: none; color: var(--color-gray-500); }
    @media (prefers-reduced-motion: reduce) {
        .sk-badge.is-warn, .sk-eye::before, .sk-eye::after, .sk-pane.is-on { animation: none; }
        .sk-panel, .sk-lay, .sk-run, .sk-play { transition: none; }
    }
    html.sm-still .sk-badge.is-warn, html.sm-still .sk-eye::before, html.sm-still .sk-eye::after { animation: none; }
</style>

<div class="sk-wrap">
    <div class="sk-stage">
        <div class="sk-map" id="skMap"></div>
        <div class="sk-top">
            <button type="button" class="sk-place" id="skPlaceBtn" aria-expanded="false">
                <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.2-7-11a7 7 0 0114 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                <span id="skPlaceName">Choose your farm</span>
            </button>
            <span class="sk-badge" id="skBadge"><i></i><span id="skBadgeText">Checking for storms…</span></span>
        </div>
        <div class="sk-side" id="skLayers">
            <button type="button" class="sk-lay is-on" data-anim="clouds"><b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 18a4 4 0 01-.6-8A6 6 0 0118 8.5 4.5 4.5 0 0117.5 18H7z"/></svg></b><span>Satellite clouds</span></button>
            <button type="button" class="sk-lay" data-anim="radar"><b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 12l6-6M4.9 19.1a10 10 0 010-14.2M19.1 4.9a10 10 0 010 14.2M8 16a5.6 5.6 0 010-8M16 8a5.6 5.6 0 010 8"/></svg></b><span>Rain radar</span></button>
            <button type="button" class="sk-lay" data-owm="clouds_new"><b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.9-9.95A5.5 5.5 0 006.5 8 4.5 4.5 0 003 15z"/></svg></b><span>Cloud cover now</span></button>
            <button type="button" class="sk-lay" data-owm="precipitation_new"><b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M8 19l-1 2M12 19l-1 2M16 19l-1 2M7 15a4 4 0 01-.6-8A6 6 0 0118 5.5 4.5 4.5 0 0117.5 15H7z"/></svg></b><span>Rain now</span></button>
            <button type="button" class="sk-lay" data-owm="wind_new"><b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 8h11a3 3 0 10-3-3M3 12h16a3 3 0 11-3 3M3 16h8"/></svg></b><span>Wind</span></button>
            <button type="button" class="sk-lay is-on" data-storms><b><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 3c5 0 8 3 8 6-3-3-8-3-11 0 5-1 9 2 9 6 0 4-4 6-8 6 2-1 3-3 3-5-2 2-6 2-8 0 3 0 5-2 5-4-3 2-7 0-7-3 2 1 4 1 5 0-3-1-4-4-3-6 1 2 3 3 5 3-2-1-2-3-1-5z"/></svg></b><span>Typhoons</span></button>
        </div>
        <div class="sk-panel" id="skPanel">
            <h4>Where is your farm?</h4>
            <button type="button" class="sk-opt" id="skHere"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg><span>Use my current location<small>From this phone's GPS</small></span></button>
            <div class="flex gap-2"><input type="text" id="skQ" class="form-input flex-1" placeholder="Town or province" autocomplete="off"><button type="button" class="btn btn-white" id="skFind">Find</button></div>
            <div id="skFound" class="mt-2"></div>
            <h4>Or one of your lots</h4>
            <div class="sk-lots" id="skLots"></div>
        </div>
        <div class="sk-bottom">
            <div class="sk-read" id="skRead"></div>
            <div class="sk-bar" id="skBar">
                <button type="button" class="sk-play" id="skPlay" aria-label="Play">
                    <svg class="i-play" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg>
                    <svg class="i-pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 5h3.2v14H7zM13.8 5H17v14h-3.2z"/></svg>
                </button>
                <div class="sk-time"><b id="skTimeLabel">Loading the sky…</b><input type="range" id="skSlider" min="0" max="17" value="17" aria-label="Move through time"></div>
                <div class="sk-modes" role="tablist"><button type="button" class="is-on" data-mode="past">Past 3 h</button><button type="button" data-mode="future">Forecast</button></div>
                <label class="sk-op">Layer <input type="range" id="skOp" min="10" max="100" value="65" aria-label="Layer opacity"></label>
            </div>
            <label class="sk-op-m">Layer opacity <input type="range" id="skOpM" min="10" max="100" value="65" aria-label="Layer opacity"></label>
        </div>
    </div>
    <p class="text-xs text-gray-400 mt-2">Clouds: Himawari-9 infrared from NASA GIBS. Rain radar: RainViewer. Live layers: OpenWeatherMap. Typhoon tracks: GDACS. Forecast: Open-Meteo. Times are Philippine time.</p>

    <div class="sk-grid">
        <div class="sk-card">
            <h3><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 3c5 0 8 3 8 6-3-3-8-3-11 0 5-1 9 2 9 6 0 4-4 6-8 6 2-1 3-3 3-5-2 2-6 2-8 0 3 0 5-2 5-4-3 2-7 0-7-3 2 1 4 1 5 0-3-1-4-4-3-6 1 2 3 3 5 3-2-1-2-3-1-5z"/></svg>Typhoons near your farm</h3>
            <p class="sk-sub">Distance from the eye to your farm, measured on the map. Inside 300 km is a direct threat.</p>
            <div id="skStorms"></div>
            <h3 class="mt-4"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 18a4 4 0 01-.6-8A6 6 0 0118 8.5 4.5 4.5 0 0117.5 18H7z"/></svg>The next 10 days at your farm</h3>
            <div class="sk-days" id="skDays"></div>
        </div>
        <div class="sk-card sk-ask">
            <h3><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>Ask Anee what it means for your crop</h3>
            <p class="sk-sub">Anee reads the storms, the forecast, ENSO and the past five years, then checks them against one of your lots: its crop, age and stage.</p>
            <select id="skLot" class="form-select"><option value="">No lot, my farm in general</option></select>
            <button type="button" class="sk-run" id="skRun"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg><span id="skRunSays">Ask Anee</span></button>
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
        list: @json(route('sky.list')), one: (id) => @json(url('/app/sky-weather/one')) + '/' + id, places: @json(route('sat.places')),
    };
    const KEY = 'anee.skyPlace';
    let OPT = null, map = null, farm = null, farmMark = null, ring = null, FR = { clouds: [], radar: [] }, STORMS = [], FC = { hours: [], days: [] };
    let mode = 'past', anim = 'clouds', owm = null, frameIdx = 17, playing = null, opacity = 0.65;
    const animLayers = { clouds: [], radar: [] };
    let owmLayer = null, stormShapes = [], eyeOverlays = [], movingEye = null;

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
    };
    const panel = (on) => { $('skPanel').classList.toggle('is-on', on); $('skPlaceBtn').setAttribute('aria-expanded', String(on)); };
    $('skPlaceBtn').addEventListener('click', () => panel(!$('skPanel').classList.contains('is-on')));
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
        $('skOp').value = $('skOpM').value = Math.round(o * 100);
        if (owmLayer) owmLayer.setOpacity(o);
        paintFrame();
    };
    $('skOp').addEventListener('input', (e) => setOpacity(e.target.value / 100));
    $('skOpM').addEventListener('input', (e) => setOpacity(e.target.value / 100));
    const ensureAnim = (kind) => {
        if (animLayers[kind].length || !FR[kind].length) return;
        animLayers[kind] = FR[kind].map((f) => { const l = new ScaledTiles(f.url, kind === 'clouds' ? 6 : 7); map.overlayMapTypes.push(l); return l; });
    };
    const paintFrame = () => {
        Object.entries(animLayers).forEach(([k, list]) => list.forEach((l, i) => l.setOpacity(mode === 'past' && k === anim && i === frameIdx ? opacity : (mode === 'future' && k === anim && i === list.length - 1 ? opacity : 0))));
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
        const d = new Date(t);
        $('skTimeLabel').textContent = 'Forecast · ' + d.toLocaleString('en-PH', { timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
        const h = FC.hours.reduce((best, x) => Math.abs(Date.parse(x.t + ':00+08:00') - t) < Math.abs(Date.parse((best || x).t + ':00+08:00') - t) ? x : best, null);
        const pos = at(t);
        moveEye(pos);
        const bits = [];
        if (h) bits.push('<span>Rain <b>' + esc(h.rain ?? 0) + ' mm</b></span>', '<span>Gusts <b>' + esc(Math.round(h.gust ?? 0)) + ' km/h</b></span>', '<span>Cloud <b>' + esc(h.cloud ?? 0) + '%</b></span>');
        if (pos && farm) bits.push('<span>' + esc(pos.s.name) + ' <b>' + Math.round(km(farm, pos)) + ' km</b> away</span>');
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
            return '<div class="sk-storm">' + (d != null ? '<span class="sk-km' + (d <= 300 ? ' is-near' : '') + '">' + Math.round(d).toLocaleString() + ' km</span>' : '')
                + '<b>' + esc(s.name) + '</b><p>' + (s.current ? 'Active now' : 'Ended') + (s.alert ? ' · ' + esc(s.alert) + ' alert' : '') + (s.maxWindKmh ? ' · up to ' + Math.round(s.maxWindKmh) + ' km/h' : '') + '</p>'
                + (close && close.p.ph ? '<p>Closest forecast point: <b>' + Math.round(close.d) + ' km</b> on ' + esc(new Date(close.p.ph).toLocaleString('en-PH', { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric' })) + '</p>' : '')
                + '</div>';
        }).join('') : '<div class="sk-storm"><b>No active typhoon on the GDACS list</b><p>The tracks of any storm that forms will show on the map here.</p></div>';
    };
    const loadStorms = async () => {
        try {
            const r = await window.api(U.storms + (farm ? '?lat=' + farm.lat + '&lng=' + farm.lng : ''));
            STORMS = (r.data && r.data.storms) || [];
        } catch (_) { STORMS = []; }
        drawStorms();
        badge();
    };
    const loadForecast = async () => {
        if (!farm) return;
        try { const r = await window.api(U.forecast + '?lat=' + farm.lat + '&lng=' + farm.lng); FC = r.data || FC; } catch (_) {}
        const maxR = Math.max(1, ...FC.days.map((d) => Number(d.rain) || 0));
        $('skDays').innerHTML = FC.days.map((d) => '<div><b>' + esc(new Date(d.date + 'T00:00:00').toLocaleDateString('en-PH', { weekday: 'short' })) + '</b>' + esc(Math.round(d.tmax)) + '°'
            + '<i style="height:' + Math.max(2, (Number(d.rain) || 0) / maxR * 44) + 'px"></i>' + esc(d.rain) + ' mm' + ((d.gust || 0) >= 60 ? '<em>' + Math.round(d.gust) + ' km/h</em>' : '') + '</div>').join('');
        if (mode === 'future') future(Number($('skSlider').value));
    };

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
            const r = await window.api(U.generate, { method: 'POST', body: { lat: farm.lat, lng: farm.lng, place: farm.label, lotId: $('skLot').value || null } });
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
            $('skLot').insertAdjacentHTML('beforeend', OPT.lots.map((l) => '<option value="' + l.id + '">' + esc(l.name + ' · ' + l.crop + (l.stage ? ' · ' + l.stage : '') + (l.season ? ' (' + l.season + ')' : '')) + '</option>').join(''));
            $('skLots').innerHTML = OPT.lots.filter((l) => (l.lat && l.lng) || l.place).map((l) => '<button type="button" class="sk-opt" data-lot="' + l.id + '"><span>' + esc(l.name) + '<small>' + esc(l.crop + (l.place ? ' · ' + l.place : '')) + '</small></span></button>').join('') || '<p class="text-xs text-gray-400">Pin a lot on its map and it shows here.</p>';
            $('skLots').addEventListener('click', (e) => {
                const b = e.target.closest('.sk-opt');
                if (!b) return;
                const l = OPT.lots.find((x) => String(x.id) === b.dataset.lot);
                if (!l) return;
                $('skLot').value = String(l.id);
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
