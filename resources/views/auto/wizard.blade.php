@extends('layouts.app')
@section('title', 'Anee Auto setup')
@section('body-class', 'hide-tabbar')
@section('page-title', 'Anee Auto')
@section('page-subtitle', 'Setting up your season')
@section('back', route('auto.index'))

{{-- ANEE AUTO, THE WIZARD (2026-10-12). Nine steps, each saved as it is
     typed (the season already exists, in setup): the season, the lots, the
     workers, the soil, the water, the troubles, Anee's first reading, the
     fertilizer per lot (NPK Plus, framed), and the start. Back always says
     where it goes. See App\Http\Controllers\AneeAutoController.
     2026-10-16 (the owner): a lot can be filled from one of the farm's lots
     (partials/farm-lots, saved as answers.farmLots); what that lot answered
     is not asked again, and a step left with nothing to ask is skipped. The
     variety is the shared sheet (partials/variety-pick). --}}
@section('content')
@include('partials.tag-sheet-css')
@include('partials.user-tags')
<div hidden>@include('partials.date-tag', ['id' => 'auDateBoot'])</div>
<style>
    :root { --au-ease: cubic-bezier(.22,1,.36,1); }
    .au { max-width: 52rem; margin: 0 auto; }
    .au-top { position: relative; overflow: hidden; border-radius: 1.3rem; padding: 1rem 1.1rem .9rem; color: #e8f1de; margin-bottom: 1rem;
        background: radial-gradient(130% 160% at 100% 0%, #5d9431 0%, #24410f 55%, #10200a 100%); }
    .au-top::after { content: ''; position: absolute; inset: 0; pointer-events: none; opacity: .5;
        background-image: radial-gradient(circle at 15% 30%, rgb(255 255 255 / .5) 0 1px, transparent 1.5px), radial-gradient(circle at 75% 70%, rgb(255 255 255 / .35) 0 1px, transparent 1.5px);
        background-size: 120px 110px, 170px 150px; animation: auDrift 28s linear infinite; }
    @keyframes auDrift { to { background-position: 120px 110px, -170px 150px; } }
    .au-top-row { position: relative; z-index: 1; display: flex; align-items: center; gap: .8rem; }
    .au-top img { width: 3rem; height: 3rem; flex: none; border-radius: 999px; background: rgb(255 255 255 / .14); padding: .15rem; animation: auBob 3.8s ease-in-out infinite; }
    @keyframes auBob { 50% { transform: translateY(-3px) rotate(-3deg); } }
    .au-top h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; color: #fff; line-height: 1.25; }
    .au-top p { font-size: .8rem; color: #cfe3b9; }
    .au-saved { margin-left: auto; flex: none; padding: .25rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 800; color: #e8f1de; background: rgb(255 255 255 / .12); transition: background-color .28s var(--au-ease); }
    .au-saved.is-busy { background: rgb(245 197 24 / .3); }
    .au-rail { position: relative; z-index: 1; display: flex; gap: .25rem; margin-top: .85rem; }
    .au-rail button { flex: 1 1 0; min-width: 0; display: flex; flex-direction: column; align-items: center; gap: .3rem; padding: 0; color: #b9d19f; font-size: .6rem; font-weight: 800; letter-spacing: .02em; }
    .au-rail button i { display: block; width: 100%; height: .28rem; border-radius: 999px; background: rgb(255 255 255 / .18); position: relative; overflow: hidden; }
    .au-rail button i::after { content: ''; position: absolute; inset: 0; background: #f5c518; transform: scaleX(0); transform-origin: left; transition: transform .4s var(--au-ease); }
    .au-rail button.is-done i::after, .au-rail button.is-on i::after { transform: scaleX(1); }
    .au-rail button.is-on { color: #fff; }
    .au-rail button span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
    .au-rail button:disabled { cursor: default; }
    @media (max-width: 639.98px) { .au-rail button span { display: none; } }

    .au-step { display: none; }
    .au-step.is-on { display: block; animation: auIn .36s var(--au-ease) both; }
    .au-step.is-back { animation-name: auInBack; }
    @keyframes auIn { from { opacity: 0; transform: translateX(16px); } }
    @keyframes auInBack { from { opacity: 0; transform: translateX(-16px); } }
    .au-card { border-radius: 1.2rem; background: var(--color-white); border: 1px solid var(--color-gray-200); padding: 1.05rem 1.1rem; margin-bottom: .9rem; }
    .au-h { font-family: var(--font-heading); font-weight: 800; font-size: 1.12rem; color: var(--color-gray-900); }
    .au-sub { margin: .25rem 0 .9rem; font-size: .85rem; line-height: 1.55; color: var(--color-gray-500); }
    .au-lab { display: block; margin: .85rem 0 .35rem; font-size: .78rem; font-weight: 800; color: var(--color-gray-700); }
    .au-lab small { font-weight: 600; color: var(--color-gray-400); }
    .au-row { display: grid; gap: .7rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 640px) { .au-row.is-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .au-pills { display: flex; flex-wrap: wrap; gap: .4rem; }
    .au-pill { display: inline-flex; align-items: center; gap: .35rem; padding: .48rem .8rem; border-radius: 999px; font-size: .82rem; font-weight: 700; text-align: left;
        color: var(--color-gray-700); background: var(--color-white); border: 1px solid var(--color-gray-200);
        transition: background-color .28s var(--au-ease), border-color .28s var(--au-ease), color .28s var(--au-ease), transform .2s var(--au-ease); }
    .au-pill:hover { border-color: var(--color-brand-300, #a8cc7e); }
    .au-pill:active { transform: scale(.97); }
    .au-pill[aria-pressed="true"] { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .au-tag { display: flex; align-items: center; justify-content: space-between; gap: .5rem; width: 100%; padding: .62rem .8rem; border-radius: .8rem; text-align: left;
        font-size: .9rem; font-weight: 700; color: #3d6823; background: var(--color-white); border: 1px solid var(--color-gray-200); transition: border-color .28s var(--au-ease), background-color .28s var(--au-ease); }
    .au-tag:hover:not(:disabled) { border-color: var(--color-brand-300, #a8cc7e); background: var(--color-brand-50, #f3f8ec); }
    .au-tag.is-none { color: var(--color-gray-500); font-weight: 600; }
    .au-tag:disabled { opacity: .55; }
    .au-tag span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .au-tag svg { width: 1rem; height: 1rem; flex: none; color: var(--color-gray-400); }
    .au-nav { position: sticky; bottom: 0; z-index: 20; display: flex; align-items: center; gap: .6rem; margin: 1rem -1rem 0; padding: .7rem 1rem calc(.7rem + env(safe-area-inset-bottom));
        background: rgb(249 250 251 / .94); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border-top: 1px solid var(--color-gray-200); }
    .au-nav .btn { min-width: 0; }
    .au-nav #auNext { margin-left: auto; }
    .au-nav small { font-size: .74rem; color: var(--color-gray-500); font-weight: 700; }
    html.dark .au-nav { background: rgb(16 20 13 / .94); border-color: #2b3a1c; }

    /* Lots */
    .au-lot { position: relative; border-radius: 1.2rem; border: 1px solid var(--color-gray-200); background: var(--color-white); margin-bottom: .9rem; overflow: hidden;
        animation: auIn .34s var(--au-ease) both; animation-delay: calc(var(--i, 0) * 60ms); }
    .au-lot-h { display: flex; align-items: center; gap: .6rem; padding: .8rem 1rem; background: linear-gradient(115deg, #f3f8ec, #fff); border-bottom: 1px solid var(--color-gray-100); }
    .au-lot-n { flex: none; width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center; font-weight: 800; color: #fff; background: var(--color-brand-600); }
    .au-lot-h input { flex: 1; min-width: 0; border: 0; background: transparent; font-weight: 800; font-size: 1rem; color: var(--color-gray-900); padding: .2rem 0; }
    .au-lot-h input:focus { outline: none; }
    .au-lot-st { flex: none; font-size: .7rem; font-weight: 800; padding: .22rem .55rem; border-radius: 999px; color: #8a5a00; background: #fff4d6; transition: background-color .28s var(--au-ease), color .28s var(--au-ease); }
    .au-lot-st.is-ok { color: #2d5016; background: #e4efd4; }
    .au-lot-b { padding: .2rem 1rem 1rem; }
    .au-note { display: flex; gap: .55rem; margin-top: .6rem; padding: .65rem .8rem; border-radius: .85rem; font-size: .8rem; line-height: 1.5; animation: auIn .3s var(--au-ease) both; }
    .au-note.is-warn { color: #6b4a00; background: #fff6dd; border: 1px solid #f3d98f; }
    .au-note.is-ok { color: #2d5016; background: #eef6e4; border: 1px solid #cfe3b8; }
    .au-note.is-info { color: #1e4b6b; background: #eaf4fb; border: 1px solid #c6e0f2; }
    .au-note a { font-weight: 800; text-decoration: underline; }
    .au-tree { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    .au-pin { display: flex; align-items: center; gap: .6rem; width: 100%; padding: .65rem .8rem; border-radius: .9rem; text-align: left; font-size: .84rem; font-weight: 700; color: var(--color-gray-700);
        border: 1.5px dashed var(--color-gray-300); background: transparent; transition: border-color .28s var(--au-ease), background-color .28s var(--au-ease); }
    .au-pin:hover { border-color: var(--color-brand-400, #8fbf5f); background: var(--color-brand-50, #f3f8ec); }
    .au-pin.is-set { border-style: solid; border-color: #b7d597; background: #f3f8ec; color: #2d5016; }
    .au-pin small { display: block; font-weight: 600; color: var(--color-gray-500); }
    .au-add { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; padding: .85rem; border-radius: 1rem; font-weight: 800; color: var(--color-brand-700);
        border: 1.5px dashed #b7d597; background: #f8fbf4; transition: background-color .28s var(--au-ease); }
    .au-add:hover { background: #eef6e4; }
    .au-fold { border-radius: 1rem; border: 1px solid #c6e0f2; background: #f2f8fd; margin-bottom: .9rem; overflow: hidden; }
    .au-fold > button { display: flex; align-items: center; gap: .6rem; width: 100%; padding: .75rem .9rem; text-align: left; font-size: .86rem; font-weight: 800; color: #1e4b6b; }
    .au-fold > button svg { margin-left: auto; width: 1rem; height: 1rem; transition: transform .28s var(--au-ease); }
    .au-fold.is-open > button svg { transform: rotate(180deg); }
    .au-fold-in { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .32s var(--au-ease); }
    .au-fold.is-open .au-fold-in { grid-template-rows: 1fr; }
    .au-fold-in > div { overflow: hidden; }
    .au-fold-in p { padding: 0 .9rem; font-size: .82rem; line-height: 1.55; color: #305a77; }
    .au-tools { display: grid; gap: .45rem; padding: .7rem .9rem .9rem; }
    @media (min-width: 640px) { .au-tools { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .au-tools a { display: block; padding: .65rem .75rem; border-radius: .85rem; background: #fff; border: 1px solid #c6e0f2; font-size: .8rem; color: #305a77; transition: transform .28s var(--au-ease), border-color .28s var(--au-ease); }
    .au-tools a:hover { transform: translateY(-2px); border-color: #8cbde0; }
    .au-tools a b { display: block; font-size: .86rem; color: #1e4b6b; }

    /* Workers */
    .au-w { display: grid; gap: .5rem; padding: .8rem; border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white); margin-bottom: .6rem; animation: auIn .3s var(--au-ease) both; }
    .au-w-top { display: flex; gap: .5rem; align-items: center; }
    .au-w-top input { flex: 1; min-width: 0; }
    .au-days { display: flex; gap: .3rem; flex-wrap: wrap; }
    .au-day { width: 2.6rem; padding: .4rem 0; border-radius: .7rem; font-size: .74rem; font-weight: 800; color: var(--color-gray-400); background: var(--color-gray-100);
        transition: background-color .28s var(--au-ease), color .28s var(--au-ease); }
    .au-day[aria-pressed="true"] { color: #fff; background: var(--color-brand-600); }
    .au-x { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; color: var(--color-gray-400); display: grid; place-items: center; }
    .au-x:hover { background: #fde8e8; color: #b91c1c; }
    .au-roster { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: .8rem; }

    /* Soil, water, troubles */
    .au-signs { display: grid; gap: .5rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 640px) { .au-signs { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .au-sign { display: flex; gap: .6rem; align-items: flex-start; padding: .7rem .8rem; border-radius: .95rem; text-align: left; border: 1px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .28s var(--au-ease), background-color .28s var(--au-ease), transform .2s var(--au-ease); }
    .au-sign:active { transform: scale(.985); }
    .au-sign i { flex: none; width: 1.3rem; height: 1.3rem; margin-top: .05rem; border-radius: .4rem; border: 2px solid var(--color-gray-300); display: grid; place-items: center; transition: background-color .28s var(--au-ease), border-color .28s var(--au-ease); }
    .au-sign i::after { content: '✓'; font-style: normal; font-size: .75rem; font-weight: 900; color: #fff; opacity: 0; transition: opacity .2s; }
    .au-sign b { display: block; font-size: .86rem; font-weight: 700; color: var(--color-gray-800); line-height: 1.35; }
    .au-sign small { display: block; margin-top: .15rem; font-size: .72rem; color: var(--color-gray-500); }
    .au-sign[aria-pressed="true"] { border-color: var(--color-brand-500, #6b9f3d); background: #f3f8ec; }
    .au-sign[aria-pressed="true"] i { background: var(--color-brand-600); border-color: var(--color-brand-600); }
    .au-sign[aria-pressed="true"] i::after { opacity: 1; }
    .au-sign.is-radio i { border-radius: 999px; }
    .au-read { margin-top: .9rem; padding: .85rem .95rem; border-radius: 1rem; background: linear-gradient(115deg, #f3f8ec, #e4efd4); border: 1px solid #cfe3b8; font-size: .84rem; color: #2d4a17; line-height: 1.55; }
    .au-read b { color: #1d330d; }
    .au-read .au-chips { margin-top: .4rem; }
    .au-chips { display: flex; flex-wrap: wrap; gap: .3rem; }
    .au-chip { padding: .18rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #2d5016; background: #fff; border: 1px solid #cfe3b8; }
    .au-chip.is-warn { color: #8a5a00; background: #fff4d6; border-color: #f3d98f; }
    .au-chip.is-bad { color: #9f1d1d; background: #fde8e8; border-color: #f5c2c2; }

    /* The reading */
    .au-reading-cta { text-align: center; padding: 1.6rem 1.2rem; }
    .au-reading-cta img { width: 5.5rem; height: 5.5rem; margin: 0 auto .6rem; border-radius: 999px; animation: auBob 3.4s ease-in-out infinite; }
    .au-reads { display: grid; gap: .45rem; margin: .9rem auto 1rem; max-width: 26rem; text-align: left; }
    .au-reads div { display: flex; gap: .55rem; align-items: center; font-size: .84rem; color: var(--color-gray-700); animation: auIn .4s var(--au-ease) both; animation-delay: calc(var(--i) * 90ms); }
    .au-reads span { flex: none; width: 2rem; height: 2rem; border-radius: .7rem; display: grid; place-items: center; background: #f3f8ec; }
    .au-big { display: inline-flex; align-items: center; gap: .5rem; padding: .85rem 1.4rem; border-radius: 999px; font-weight: 800; font-size: .98rem; color: #fff;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 35%, #3d6823 60%, #6b9f3d); background-size: 220% 100%; animation: auTide 5s ease-in-out infinite alternate;
        box-shadow: 0 12px 26px -14px rgb(61 104 35 / .8); transition: transform .2s var(--au-ease); }
    .au-big:active { transform: scale(.98); }
    .au-big:disabled { opacity: .55; animation: none; }
    @keyframes auTide { to { background-position: 100% 50%; } }
    .au-rl { border-radius: 1.1rem; border: 1px solid var(--color-gray-200); background: var(--color-white); padding: .95rem 1rem; margin-bottom: .8rem; animation: auIn .38s var(--au-ease) both; animation-delay: calc(var(--i, 0) * 80ms); }
    .au-rl h4 { font-weight: 800; font-size: .95rem; color: var(--color-gray-900); display: flex; align-items: center; gap: .45rem; }
    .au-rl h5 { margin: .75rem 0 .25rem; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .au-rl p, .au-rl li { font-size: .84rem; line-height: 1.55; color: var(--color-gray-700); }
    .au-rl ul { margin: .2rem 0 0 1rem; list-style: disc; }
    .au-facts { display: grid; gap: .4rem; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: .5rem; }
    @media (min-width: 640px) { .au-facts { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .au-facts div { padding: .5rem .6rem; border-radius: .8rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .au-facts small { display: block; font-size: .62rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .au-facts b { font-size: .92rem; color: var(--color-gray-900); }

    /* Fertilizer */
    .au-ltabs { display: flex; gap: .35rem; overflow-x: auto; scrollbar-width: none; margin-bottom: .8rem; padding-bottom: .1rem; }
    .au-ltabs::-webkit-scrollbar { display: none; }
    .au-ltab { flex: none; display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .85rem; border-radius: 999px; font-size: .82rem; font-weight: 800; color: var(--color-gray-600);
        background: var(--color-white); border: 1px solid var(--color-gray-200); transition: background-color .28s var(--au-ease), color .28s var(--au-ease), border-color .28s var(--au-ease); }
    .au-ltab.is-on { color: #fff; background: var(--color-brand-600); border-color: var(--color-brand-600); }
    .au-ltab em { font-style: normal; font-size: .7rem; }
    .au-bars { display: grid; gap: .55rem; margin-top: .4rem; }
    .au-bar { display: grid; grid-template-columns: 3rem minmax(0, 1fr) 5.5rem; gap: .6rem; align-items: center; font-size: .78rem; }
    .au-bar > b { font-weight: 800; color: var(--color-gray-800); }
    .au-bar-t { position: relative; height: .7rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .au-bar-t span { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 999px; background: #6b9f3d; width: 0; transition: width .9s var(--au-ease); }
    .au-bar-t i { position: absolute; top: -2px; bottom: -2px; width: 3px; border-radius: 2px; background: #1f2a17; }
    .au-bar.is-short .au-bar-t span { background: #e8a33a; } .au-bar.is-over .au-bar-t span { background: #d9534f; }
    .au-bar em { font-style: normal; font-weight: 800; text-align: right; color: #2d5016; } .au-bar.is-short em { color: #b45309; } .au-bar.is-over em { color: #b91c1c; }
    .au-yield { display: flex; align-items: center; gap: 1rem; margin-top: .9rem; padding: .8rem .9rem; border-radius: 1rem; background: linear-gradient(115deg, #fdf8e8, #f6edc8); border: 1px solid #ecd98d; }
    .au-gauge { position: relative; flex: none; width: 4.6rem; height: 4.6rem; }
    .au-gauge svg { width: 100%; height: 100%; transform: rotate(-90deg); }
    .au-gauge circle { fill: none; stroke-width: 9; }
    .au-gauge .bg { stroke: rgb(0 0 0 / .08); }
    .au-gauge .fg { stroke: #c99a06; stroke-linecap: round; stroke-dasharray: 1 1; stroke-dashoffset: 1; transition: stroke-dashoffset 1.2s var(--au-ease); }
    .au-gauge b { position: absolute; inset: 0; display: grid; place-items: center; font-weight: 800; font-size: .95rem; color: #6b4e00; }
    .au-yield p { font-size: .82rem; line-height: 1.5; color: #5b4500; }
    .au-yield p b { color: #3d2e00; font-size: 1rem; }
    .au-risk { display: flex; gap: .5rem; margin-top: .45rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); animation: auIn .3s var(--au-ease) both; }
    .au-disc { margin-top: .9rem; font-size: .72rem; line-height: 1.5; color: var(--color-gray-400); }
    .au-frame { position: fixed; inset: 0; z-index: 220; display: flex; flex-direction: column; background: var(--color-gray-50); transform: translateY(100%); transition: transform .4s var(--au-ease); }
    .au-frame.is-on { transform: none; }
    .au-frame[hidden] { display: none; }
    .au-frame-h { display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem; padding-top: calc(.7rem + env(safe-area-inset-top)); background: #1d330d; color: #fff; }
    .au-frame-h b { flex: 1; min-width: 0; font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .au-frame-h button { width: 2.2rem; height: 2.2rem; border-radius: 999px; display: grid; place-items: center; background: rgb(255 255 255 / .12); }
    .au-frame iframe { flex: 1; width: 100%; border: 0; background: var(--color-gray-50); }
    .au-modal { position: fixed; inset: 0; z-index: 230; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgb(12 20 8 / .55); opacity: 0; transition: opacity .28s var(--au-ease); }
    .au-modal.is-on { opacity: 1; }
    .au-modal[hidden] { display: none; }
    .au-modal > div { width: 100%; max-width: 26rem; max-height: 90vh; overflow-y: auto; border-radius: 1.3rem; background: var(--color-white); padding: 1.1rem 1.15rem; transform: translateY(12px); transition: transform .32s var(--au-ease); }
    .au-modal.is-on > div { transform: none; }
    .au-modal h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.08rem; color: var(--color-gray-900); }
    .au-modal p { margin: .35rem 0 .8rem; font-size: .84rem; line-height: 1.55; color: var(--color-gray-600); }
    .au-modal .au-acts { display: flex; gap: .5rem; margin-top: 1rem; }
    .au-modal .au-acts .btn { flex: 1; }
    .au-map { height: min(60vh, 26rem); border-radius: 1rem; overflow: hidden; background: #1d2a14; }
    .au-review { display: grid; gap: .5rem; }
    .au-review div { display: flex; gap: .7rem; padding: .55rem 0; border-bottom: 1px dashed var(--color-gray-200); font-size: .84rem; }
    .au-review b { flex: 0 0 7rem; color: var(--color-gray-500); font-weight: 700; }
    .au-review span { color: var(--color-gray-800); min-width: 0; }
    .au-lock { display: flex; gap: .6rem; margin-top: 1rem; padding: .8rem .9rem; border-radius: 1rem; background: #fff6dd; border: 1px solid #f3d98f; color: #6b4a00; font-size: .82rem; line-height: 1.5; }

    /* The farm's lots (2026-10-16, the owner): a lot of the season can come
       from one of the farm's lots (partials/farm-lots), and what that lot has
       answered is not asked again. */
    .au-rail button[hidden], .au-q[hidden], .au-from[hidden], .au-varfact[hidden] { display: none; }
    .au-q { animation: auFade .3s var(--au-ease) both; }
    @keyframes auFade { from { opacity: 0; transform: translateY(4px); } }
    .au-from { margin: .1rem 0 .9rem; padding: .7rem .8rem; border-radius: .95rem; background: linear-gradient(115deg, #f3f8ec, #fff); border: 1px solid #cfe3b8; animation: auIn .3s var(--au-ease) both; }
    .au-from p { display: flex; flex-wrap: wrap; align-items: center; gap: .2rem .7rem; margin-bottom: .5rem; font-size: .82rem; line-height: 1.45; color: #2d4a17; }
    .au-from p b { font-weight: 800; }
    .au-link { padding: .2rem 0; font-size: .8rem; font-weight: 800; color: var(--color-brand-700, #3d6823); text-decoration: underline; text-underline-offset: 3px; transition: color .28s var(--au-ease); }
    .au-link:hover { color: var(--color-brand-600, #4a7c2a); }
    .au-pin b { display: block; font-weight: 800; overflow-wrap: anywhere; }
    .au-pin small { overflow-wrap: anywhere; }
    .au-flot-acts { display: flex; flex-wrap: wrap; gap: .2rem 1rem; margin-top: .35rem; }
    .au-lot-h input[readonly] { cursor: default; }
    .au-varfact { display: block; margin-top: .3rem; font-size: .72rem; line-height: 1.45; color: var(--color-gray-500); }
    .au-tag.is-bump { animation: auBump .4s var(--au-ease); }
    @keyframes auBump { 40% { transform: scale(1.02); } }
    html.dark .au-pin.is-set { background: #1c2616; border-color: #3d5a24; color: #c5e09f; }
    html.dark .au-pin small { color: #9aa78d; }
    html.dark .au-from { background: #17220f; border-color: #2b3a1c; }
    html.dark .au-from p { color: #c5e09f; }
    html.dark .au-link { color: #a5c97e; }

    html.dark .au-card, html.dark .au-lot, html.dark .au-w, html.dark .au-sign, html.dark .au-rl, html.dark .au-pill, html.dark .au-tag, html.dark .au-ltab:not(.is-on), html.dark .au-modal > div { background: #161c12; border-color: #2b3a1c; }
    html.dark .au-lot-h { background: linear-gradient(115deg, #1c2616, #161c12); border-color: #2b3a1c; }
    html.dark .au-lot-h input, html.dark .au-h, html.dark .au-sign b, html.dark .au-rl h4, html.dark .au-modal h3 { color: #eef3e8; }
    html.dark .au-sign[aria-pressed="true"] { background: #1c2616; }
    html.dark .au-pill[aria-pressed="true"], html.dark .au-ltab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    html.dark .au-read { background: #17220f; border-color: #2b3a1c; color: #c7d8b4; } html.dark .au-read b { color: #e8efe1; }
    html.dark .au-note.is-warn, html.dark .au-lock { background: #2a2210; border-color: #5b4716; color: #f3d9a4; }
    html.dark .au-note.is-ok { background: #17220f; border-color: #2b3a1c; color: #c5e09f; }
    html.dark .au-note.is-info, html.dark .au-fold { background: #10202b; border-color: #1f3a4d; color: #b5d3ea; }
    html.dark .au-fold > button, html.dark .au-fold-in p { color: #b5d3ea; }
    html.dark .au-tools a { background: #0d1a23; border-color: #1f3a4d; color: #9fc3dd; } html.dark .au-tools a b { color: #d4e7f5; }
    html.dark .au-facts div { background: #1a2114; border-color: #2b3a1c; } html.dark .au-facts b { color: #eef3e8; }
    html.dark .au-yield { background: #2a2410; border-color: #5b4d16; } html.dark .au-yield p { color: #f0dfa0; } html.dark .au-yield p b { color: #fff3c4; }
    html.dark .au-add { background: #1a2114; border-color: #3f5a2a; color: #c5e09f; }
    html.dark .au-bar-t { background: #26301e; } html.dark .au-bar-t i { background: #f1f5e9; }
    @media (prefers-reduced-motion: reduce) {
        .au-top::after, .au-top img, .au-reading-cta img, .au-big, .au-step.is-on, .au-lot, .au-w, .au-rl, .au-reads div, .au-note, .au-risk, .au-q, .au-from, .au-tag.is-bump { animation: none; }
        .au-frame, .au-modal, .au-modal > div, .au-bar-t span, .au-gauge .fg, .au-fold-in, .au-rail button i::after, .au-link { transition: none; }
    }
</style>

@php $chev = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>'; @endphp
<div class="au">
    <div class="au-top">
        <div class="au-top-row">
            <img src="{{ \App\Support\AneeEmoji::url('salute') }}" alt="">
            <div class="min-w-0"><h2 id="auTitleTop">{{ $schedule->title }}</h2><p id="auStepSay">Step 1 of 9</p></div>
            <span class="au-saved" id="auSaved">Saved</span>
        </div>
        <div class="au-rail" id="auRail"></div>
    </div>

    {{-- 0. The season --}}
    <section class="au-step" data-step="0">
        <div class="au-card">
            <h3 class="au-h">Your season</h3>
            <p class="au-sub">A name you will know it by, and anything Anee should keep in mind about it.</p>
            <label class="au-lab" for="auTitle">Season name</label>
            <input type="text" id="auTitle" class="form-input w-full" maxlength="255" placeholder="Like Wet season palay 2026">
            <label class="au-lab" for="auDesc">Description <small>(optional)</small></label>
            <textarea id="auDesc" class="form-textarea w-full" rows="3" maxlength="5000" placeholder="Like: the family farm by the river, two lots of palay and one of corn"></textarea>
            <span class="au-lab">Tags <small>(optional)</small></span>
            <div class="ut-mount" id="auTags"></div>
        </div>
    </section>

    {{-- 1. The lots --}}
    <section class="au-step" data-step="1">
        <div class="au-fold" id="auHelpFold">
            <button type="button" aria-expanded="false"><span aria-hidden="true">💡</span>Not sure about the variety, the planting day or the crop?{!! $chev !!}</button>
            <div class="au-fold-in"><div>
                <p>Anee's quick tools answer those first. Open one in a new tab, then come back: everything here is saved.</p>
                <div class="au-tools">
                    <a href="{{ route('whatp.page') }}" target="_blank" rel="noopener"><b>🌱 What to Plant</b>The crops your ground and season favour</a>
                    <a href="{{ route('wtp.page') }}" target="_blank" rel="noopener"><b>📅 When to Plant</b>The best weeks for your crop and place</a>
                    <a href="{{ route('vary.page') }}" target="_blank" rel="noopener"><b>🔬 Variety Ranking</b>Varieties compared for your farm</a>
                </div>
            </div></div>
        </div>
        <div id="auLots"></div>
        <button type="button" class="au-add" id="auAddLot">+ Add another lot</button>
    </section>

    {{-- 2. The workers --}}
    <section class="au-step" data-step="2">
        <div class="au-card">
            <h3 class="au-h">Who works the farm</h3>
            <p class="au-sub" id="auWSub">Anee gives out the work by who can come. Tap the days each one can work.</p>
            <div class="au-roster" id="auRoster"></div>
            <div id="auWorkers"></div>
            <button type="button" class="au-add" id="auAddWorker">+ Add a worker</button>
            <p class="au-note is-info" id="auWNote" hidden></p>
        </div>
    </section>

    {{-- 3. The soil. The field in detail that used to open this step is the
         farm's lots now (2026-10-16, the owner): a lot picked on step 1 brings
         its soil, water and last season, its answers tick the signs, and what
         it answered is hidden (data-q) under a line that says where it came
         from. A step whose questions are all answered is skipped. --}}
    <section class="au-step" data-step="3">
        <div class="au-card">
            <h3 class="au-h">Your soil, as you see it</h3>
            <div class="au-from" id="auSoilFrom" hidden></div>
            <div class="au-q" data-q="signs">
                <p class="au-sub">Tick everything that sounds like your field. From these Anee reads the soil type, the salt and the sodium, and which way the pH leans.</p>
                <div class="au-signs" id="auSigns"></div>
            </div>
            <div class="au-q" data-q="ph">
                <span class="au-lab">Is the soil acidic or alkaline?</span>
                <div class="au-pills" id="auPh"></div>
            </div>
            <div class="au-q" data-q="tested">
                <label class="au-lab" for="auTested">A soil test pH, if you have one <small>(optional)</small></label>
                <input type="number" id="auTested" class="form-input" style="max-width:9rem" min="2" max="12" step="0.1" inputmode="decimal" placeholder="Like 7.8">
            </div>
            <div class="au-read" id="auSoilRead" hidden></div>
        </div>
    </section>

    {{-- 4. The water --}}
    <section class="au-step" data-step="4">
        <div class="au-card">
            <h3 class="au-h">Where your water really comes from</h3>
            <p class="au-sub">Pick the one closest to how it goes, not how it should go. Anee times the water and the fertilizer by it.</p>
            <div class="au-signs" id="auWater"></div>
            <label class="au-lab" for="auWaterNote">Anything more about the water <small>(optional)</small></label>
            <textarea id="auWaterNote" class="form-textarea w-full" rows="2" maxlength="400" placeholder="Like: the canal dries up in March; the pump is shared with two neighbours"></textarea>
        </div>
    </section>

    {{-- 5. The troubles --}}
    <section class="au-step" data-step="5">
        <div class="au-card">
            <h3 class="au-h">What hurt your harvest before</h3>
            <div class="au-from" id="auProbFrom" hidden></div>
            <p class="au-sub" id="auProbSub">Pick the troubles of past seasons that cost you the most. Anee plans to get ahead of them.</p>
            <div class="au-pills" id="auProblems"></div>
            <div class="au-q" data-q="cropprob">
                <span class="au-lab" id="auCropProbLab" hidden>Pests and diseases you have seen on these crops</span>
                <div class="au-pills" id="auCropProblems"></div>
            </div>
            <label class="au-lab" for="auNotes">Anything else Anee should know <small>(optional)</small></label>
            <textarea id="auNotes" class="form-textarea w-full" rows="2" maxlength="2000" placeholder="Like: rats come from the creek side every season"></textarea>
        </div>
    </section>

    {{-- 6. Anee reads the farm --}}
    <section class="au-step" data-step="6">
        <div id="auReading"></div>
    </section>

    {{-- 7. The fertilizer --}}
    <section class="au-step" data-step="7">
        <div class="au-card">
            <h3 class="au-h">The fertilizer, lot by lot</h3>
            <p class="au-sub">Add the granular fertilizers you plan to use, and any foliar or micronutrients, in bags of 25 or 50 kg or in kilos. Anee shows what they give against what the crop needs on your soil, and plans the timing herself.</p>
            <div class="au-ltabs" id="auFTabs"></div>
            <div id="auFPane"></div>
        </div>
    </section>

    {{-- 8. Start --}}
    <section class="au-step" data-step="8">
        <div class="au-card">
            <h3 class="au-h">Ready when you are</h3>
            <p class="au-sub">Anee reads everything again and writes the whole season: land preparation, water, every fertilizer at its moment, prevention first, harvest. Then she keeps it on track every day.</p>
            <div class="au-review" id="auReview"></div>
            <div class="au-lock"><span aria-hidden="true">🔒</span><span><b>After this, the answers cannot be changed.</b> Anee plans from them; from then on you steer the season by telling her what happened and what cannot be done.</span></div>
            <div class="text-center mt-5"><button type="button" class="au-big" id="auGenerate">✨ Start Anee Auto</button></div>
        </div>
    </section>

    <div class="au-nav">
        <button type="button" class="btn btn-white" id="auBack">Back</button>
        <small id="auNavSay"></small>
        <button type="button" class="btn btn-primary" id="auNext">Next</button>
    </div>
</div>

{{-- One sheet for every pick: crops, provinces, towns. --}}
<div class="sheet hidden" id="auPickSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header"><h3 class="sheet-title" id="auPickTitle">Choose</h3><button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button></div>
    <div class="sheet-body">
        <input type="search" id="auPickQ" class="form-input mb-3" placeholder="Search" autocomplete="off">
        <div class="dt-rows" id="auPickRows"></div>
    </div>
</div>

{{-- The lot's pin. --}}
<div class="sheet hidden" id="auPinSheet" style="--sheet-width:40rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header"><h3 class="sheet-title" id="auPinTitle">Drop a pin on the lot</h3><button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button></div>
    <div class="sheet-body">
        <p class="text-sm text-gray-500 mb-3">Optional, and it makes Anee's reading sharper: the weather, the soil model and the season are read at the exact spot. Tap the middle of the lot.</p>
        <div class="au-map" id="auMap"></div>
        <p class="text-xs text-gray-500 mt-2" id="auPinSay">No pin yet.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-white" id="auPinMe">📍 Where I am</button>
        <button type="button" class="btn btn-white" id="auPinClear">Remove</button>
        <button type="button" class="btn btn-primary" id="auPinSave">Save the pin</button>
    </div>
</div>

{{-- Which of the farm's lots a lot of this season is (partials/farm-lots).
     A dialog like the lot sheet, so the lot sheet stacks over it. --}}
<dialog class="fq-sheet is-small" id="auFarmSheet" aria-labelledby="auFarmTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><h2 id="auFarmTitle">Which lot is it?<small>Pick the lot, or add it. Its place, size, soil and water come with it, so they are not asked again.</small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="fq-body"><div id="auFarmPick"></div></div>
        <div class="fq-foot"><button type="button" class="btn btn-white" id="auFarmHand">Fill it by hand</button><span></span><button type="button" class="btn btn-white" data-fq-close>Close</button></div>
    </div>
</dialog>
@include('partials.farm-lots')
@include('partials.variety-pick')

<div class="au-modal" id="auModal" hidden><div id="auModalBox"></div></div>

<div class="au-frame" id="auFrame" hidden>
    <div class="au-frame-h"><b id="auFrameTitle">Fertilizer</b><button type="button" id="auFrameClose" aria-label="Close">✕</button></div>
    <iframe id="auFrameIf" title="NPK Plus" src="about:blank"></iframe>
</div>

@include('sm.partials.anee-wait')
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const still = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    const B = @json($boot);
    const ID = B.id;
    const U = {
        state: @json(route('auto.state', $auto->id)), basics: @json(route('auto.basics', $auto->id)), lot: @json(route('auto.lot', $auto->id)), lotAdd: @json(route('auto.lot.add', $auto->id)),
        workers: @json(route('auto.workers', $auto->id)), answers: @json(route('auto.answers', $auto->id)), problems: @json(route('auto.problems', $auto->id)),
        analyze: @json(route('auto.analyze', $auto->id)), generate: @json(route('auto.generate', $auto->id)), job: () => @json(route('auto.job', $auto->id)),
        fert: @json(route('auto.fert', $auto->id)), imbalance: @json(route('auto.imbalance', $auto->id)), ph: @json(asset('data/ph-locations.json')),
        npk: @json(route('npk.page')) + '?frame=auto', buy: @json(route('ai.credits') . '?tab=buy'),
    };
    const STEPS = ['Season', 'Lots', 'Workers', 'Soil', 'Water', 'Troubles', 'Reading', 'Fertilizer', 'Start'];
    const DOW = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const CHEV = @json($chev);
    const BOOK = B.book;
    const CROPS = B.crops;
    const cropOf = (k) => CROPS.find((c) => c.key === k) || null;
    const cropName = (k) => { const c = cropOf(k); return c ? String(c.label).replace(/ — /g, ', ') : ''; };
    const fmtDate = (iso) => { if (!iso) return ''; const [y, m, d] = iso.split('-').map(Number); return new Date(y, m - 1, d).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: y === new Date().getFullYear() ? undefined : 'numeric' }); };
    const daysFrom = (a, b) => Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000);
    const addDays = (iso, n) => { const d = new Date(iso + 'T00:00:00'); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10); };

    let S = B;
    S.answers = Array.isArray(S.answers) ? {} : (S.answers || {});
    let step = Math.min(Number(S.step) || 0, STEPS.length - 1);

    /* ---- the farm's lots (partials/farm-lots, 2026-10-16) ----
       LINK: this season's lot id to the farm lot it was filled from (saved as
       answers.farmLots). The first linked lot that has answers is the farm's
       field profile (SRC), and what it answered is hidden (HID). Until the
       farm's lots load, the profile saved with the season stands in. */
    const LINK = {};
    (Array.isArray(S.answers.farmLots) ? S.answers.farmLots : []).forEach((x) => { if (x && x.lotId && x.farmLotId) LINK[x.lotId] = Number(x.farmLotId); });
    const EVER = new Set(Object.values(LINK));
    let FLROWS = null;
    const NOHID = { signs: false, ph: false, tested: false, water: false, problems: false };
    const filled = (a, k) => { const v = a && a[k]; return v !== undefined && v !== null && v !== '' && !(Array.isArray(v) && !v.length); };
    const hidFrom = (a, L) => !a ? Object.assign({}, NOHID) : (() => {
        const tex = filled(a, 'texture') || a.rocks === 'many', acid = filled(a, 'acidity') || filled(a, 'phFrom');
        return { signs: tex && filled(a, 'salt') && acid, ph: acid, tested: acid, water: filled(a, 'water') && (!L || !!BOOK.water[L.waterAuto]), problems: filled(a, 'outbreaks') };
    })();
    const fpFrom = S.answers.fieldProfileFrom;
    let SRC = fpFrom && S.answers.fieldProfile && Object.values(LINK).includes(Number(fpFrom.id)) ? { id: Number(fpFrom.id), name: fpFrom.name || 'your lot', answers: S.answers.fieldProfile } : null;
    let HID = hidFrom(SRC && SRC.answers);
    // A step whose every question the lot answered is skipped, and so is its dot.
    const stepHidden = (n) => (n === 3 && HID.signs && HID.ph && HID.tested) || (n === 4 && HID.water);
    const nextOf = (n) => { for (let i = n + 1; i < STEPS.length; i++) if (!stepHidden(i)) return i; return n; };
    const prevOf = (n) => { for (let i = n - 1; i >= 0; i--) if (!stepHidden(i)) return i; return -1; };

    /* ---- saving, quietly and often ---- */
    let busy = 0;
    const savedSay = () => { $('auSaved').textContent = busy ? 'Saving…' : 'Saved'; $('auSaved').classList.toggle('is-busy', !!busy); };
    const timers = {};
    const put = async (url, body, method = 'PUT') => {
        busy++; savedSay();
        try { return await window.api(url, { method, body }); }
        catch (err) { window.toast?.(err.message, 'error'); throw err; }
        finally { busy--; savedSay(); }
    };
    const soon = (key, fn, ms = 650) => { clearTimeout(timers[key]); timers[key] = setTimeout(() => fn().catch(() => {}), ms); };
    const flush = async () => { for (const k of Object.keys(timers)) { clearTimeout(timers[k]); } await Promise.all(Object.values(pending).map((f) => f().catch(() => {}))); for (const k in pending) delete pending[k]; };
    const pending = {};
    const later = (key, fn, ms) => { pending[key] = fn; soon(key, async () => { const f = pending[key]; delete pending[key]; if (f) await f(); }, ms); };
    window.addEventListener('beforeunload', () => { Object.values(pending).forEach((f) => f().catch(() => {})); });

    /* ---- the rail and the steps ---- */
    $('auRail').innerHTML = STEPS.map((s, i) => '<button type="button" data-go="' + i + '"><i></i><span>' + s + '</span></button>').join('');
    const reached = () => Math.max(Number(S.step) || 0, step);
    // The rail, the step count and the two buttons, over the steps still asked.
    const navSay = () => {
        const shown = STEPS.map((_, i) => i).filter((i) => !stepHidden(i));
        document.querySelectorAll('#auRail button').forEach((b, i) => { b.hidden = stepHidden(i); b.classList.toggle('is-on', i === step); b.classList.toggle('is-done', i < step); b.disabled = i > reached(); });
        $('auStepSay').textContent = 'Step ' + (Math.max(0, shown.indexOf(step)) + 1) + ' of ' + shown.length + ' · ' + STEPS[step];
        const p = prevOf(step);
        $('auBack').textContent = p < 0 ? 'My seasons' : 'Back to ' + STEPS[p];
        $('auNext').hidden = step === STEPS.length - 1;
        if (step !== 7) $('auNext').textContent = step === 5 ? 'Next: Anee reads it' : step === 6 ? 'Next: Fertilizer' : 'Next: ' + STEPS[nextOf(step)];
    };
    const show = async (n, back = false) => {
        n = Math.max(0, Math.min(STEPS.length - 1, n));
        if (stepHidden(n)) n = back ? Math.max(0, prevOf(n)) : nextOf(n);
        await flush();
        step = n;
        document.querySelectorAll('.au-step').forEach((el) => { const on = Number(el.dataset.step) === n; el.classList.toggle('is-on', on); el.classList.toggle('is-back', on && back); });
        navSay();
        $('auNavSay').textContent = '';
        if (n > (Number(S.step) || 0)) { S.step = n; put(U.answers, { step: n }).catch(() => {}); }
        paintStep(n);
        window.scrollTo({ top: 0, behavior: still() ? 'auto' : 'smooth' });
    };
    $('auRail').addEventListener('click', (e) => { const b = e.target.closest('[data-go]'); if (b && !b.disabled) show(Number(b.dataset.go), Number(b.dataset.go) < step); });
    $('auBack').addEventListener('click', () => { const p = prevOf(step); if (p < 0) location.href = @json(route('auto.index')); else show(p, true); });
    $('auNext').addEventListener('click', () => {
        // The fertilizer step walks the lots one by one before it moves on.
        if (step === 7) { const at = S.lots.findIndex((x) => x.id === FLOT); if (at >= 0 && at < S.lots.length - 1) { FLOT = S.lots[at + 1].id; paintFert(); window.scrollTo({ top: 0, behavior: still() ? 'auto' : 'smooth' }); return; } }
        const gap = gapOf(step);
        if (gap) return window.toast?.(gap, 'error');
        show(nextOf(step));
    });
    const gapOf = (n) => {
        if (n === 0 && !$('auTitle').value.trim()) return 'Give the season a name.';
        if (n === 1) { for (const l of S.lots) { const g = lotGap(l); if (g) return (l.name || 'A lot') + ': ' + g; } }
        if (n === 4 && !S.answers.water) return 'Pick where the water comes from.';
        if (n === 6 && !S.analysis) return 'Let Anee read the farm first.';
        return null;
    };
    const paintStep = (n) => {
        if (n === 1) paintLots();
        if (n === 2) paintWorkers();
        if (n === 3) { paintSoil(); paintFrom(); }
        if (n === 4) paintWater();
        if (n === 5) { paintProblems(); paintFrom(); }
        if (n === 6) paintReading();
        if (n === 7) paintFert();
        if (n === 8) paintReview();
    };

    /* ---- 0. the season ---- */
    $('auTitle').value = S.title || ''; $('auDesc').value = S.description || '';
    const saveBasics = () => put(U.basics, { title: $('auTitle').value.trim() || 'Anee Auto season', description: $('auDesc').value, tags: window.userTags ? window.userTags.value($('auTags')) : (S.answers.tags || []) });
    ['auTitle', 'auDesc'].forEach((id) => $(id).addEventListener('input', () => { $('auTitleTop').textContent = $('auTitle').value.trim() || 'Anee Auto'; later('basics', saveBasics, 700); }));
    const mountTags = () => { if (!window.userTags) return setTimeout(mountTags, 120); window.userTags.mount($('auTags'), S.answers.tags || []); };
    mountTags();
    new MutationObserver(() => later('basics', saveBasics, 500)).observe($('auTags'), { childList: true, subtree: true });

    /* ---- one pick sheet ---- */
    let PK = null;
    const pick = (title, rows, cur, cb) => {
        $('auPickTitle').textContent = title; $('auPickQ').value = '';
        PK = { rows, cur, cb };
        paintPick();
        window.openSheet('auPickSheet');
        if (!matchMedia('(hover: none)').matches) setTimeout(() => $('auPickQ').focus(), 260);
    };
    const paintPick = () => {
        const q = $('auPickQ').value.trim().toLowerCase();
        let lastGroup = null;
        $('auPickRows').innerHTML = PK.rows.filter((r) => !q || (r.label + ' ' + (r.hint || '')).toLowerCase().includes(q)).map((r) => {
            const head = r.group && r.group !== lastGroup ? '<p class="text-xs font-extrabold uppercase tracking-wide text-gray-400 mt-3 mb-1">' + esc(lastGroup = r.group) + '</p>' : '';
            return head + '<button type="button" class="dt-row' + (r.key === PK.cur ? ' is-on' : '') + '" data-k="' + esc(r.key) + '">' + (r.icon ? '<span class="dt-row-e">' + r.icon + '</span>' : '')
                + '<span class="dt-row-body"><b>' + esc(r.label) + '</b>' + (r.hint ? '<i>' + esc(r.hint) + '</i>' : '') + '</span></button>';
        }).join('') || '<p class="text-sm text-gray-500">Nothing by that name.</p>';
    };
    $('auPickQ').addEventListener('input', () => PK && paintPick());
    $('auPickRows').addEventListener('click', (e) => { const b = e.target.closest('[data-k]'); if (!b || !PK) return; const cb = PK.cb; window.closeSheet('auPickSheet'); setTimeout(() => cb(b.dataset.k), 120); });
    let PHL = null;
    const loadPh = async () => PHL ??= await (await fetch(U.ph)).json();
    const townName = (t) => String(t || '').replace(/\s*\(Capital\)\s*$/i, '');

    /* ---- 1. the lots ---- */
    const lotGap = (l) => {
        if (!l.crop) return 'choose the crop.';
        if (!l.method) return 'say how it goes into the field.';
        if (l.method !== 'tree' && !l.fieldDate) return 'pick the planting day.';
        if (!(Number(l.size) > 0)) return 'say how big it is.';
        if (!l.pin && !(l.province && l.town)) return 'pick its province and town, or drop a pin.';
        return null;
    };
    const saveLot = (l, extra = {}) => put(U.lot, Object.assign({ lotId: l.id, name: l.name, size: l.size, unit: l.unit || 'ha', crop: l.crop, variety: l.variety || '', method: l.method,
        fieldDate: l.method === 'tree' ? null : l.fieldDate, treeMonths: l.method === 'tree' ? l.treeMonths : null, province: l.province, town: l.town, barangay: l.barangay,
        lat: l.pin ? l.pin.lat : null, lng: l.pin ? l.pin.lng : null }, extra)).then((r) => { if (r && r.data) { l.sowDate = r.data.sowDate; l.method = r.data.method || l.method; } paintLotNotes(l); paintLotState(l); return r; });
    const methodsOf = (l) => (cropOf(l.crop) || {}).methods || {};
    /* The farm lot this lot was filled from: the loaded row, or (until the
       farm's lots load) what was copied from it into this lot. */
    const farmRow = (l) => { const id = LINK[l.id]; return id && FLROWS ? FLROWS.find((r) => r.id === id) || null : null; };
    const farmView = (l) => {
        if (!LINK[l.id]) return null;
        if (FLROWS) return farmRow(l);
        return { id: LINK[l.id], name: l.name, place: [l.barangay, townName(l.town), l.province].filter(Boolean).join(', '), areaHa: l.unit === 'ha' ? l.size : null };
    };
    const fmtHa = (a) => String(window.aneeLots ? window.aneeLots.fmtHa(a) : (Number(a) ? a + ' ha' : '')).replace(' ha', ' ha').replace(' sq m', ' sq m');
    /* The variety, two ways (partials/variety-pick, 2026-10-16): searched in
       the records for the lot's crop, or typed as sold. The name is what is
       saved; a record picked this visit also shows its facts. */
    const VAR_NONE = 'Pick from the records, or type your own';
    const varTag = (l) => '<button type="button" class="au-tag' + (l.variety ? '' : ' is-none') + '" data-act="variety"><span>' + (l.varRecord ? '📗 ' : '🧬 ') + esc(l.variety || VAR_NONE) + '</span>' + CHEV + '</button>'
        + '<small class="au-varfact"' + (l.varRecord ? '' : ' hidden') + '>' + (l.varRecord && window.aneeVarietyPick ? esc('On record: ' + window.aneeVarietyPick.facts(l.varRecord)) : '') + '</small>';
    const lotCard = (l, i) => {
        const c = cropOf(l.crop);
        const ms = methodsOf(l);
        const fv = farmView(l);
        const askSize = !(fv && Number(fv.areaHa) > 0);
        return '<div class="au-lot" data-lot="' + l.id + '" style="--i:' + i + '">'
            + '<div class="au-lot-h"><span class="au-lot-n">' + (i + 1) + '</span><input type="text" data-f="name" value="' + esc(l.name) + '" maxlength="255" aria-label="Lot name"' + (fv ? ' readonly title="The name of your lot. Edit the lot to change it."' : '') + '><span class="au-lot-st" data-st></span></div>'
            + '<div class="au-lot-b">'
            + '<span class="au-lab">Which of your lots is it? <small>(optional)</small></span>'
            + (fv ? '<button type="button" class="au-pin is-set" data-act="farm"><span aria-hidden="true">🗺️</span><span><b>' + esc(fv.name) + '</b><small>' + esc([fv.place, Number(fv.areaHa) > 0 ? (window.aneeLots && window.aneeLots.fmtSize ? window.aneeLots.fmtSize(fv) : fmtHa(fv.areaHa)) : ''].filter(Boolean).join(' · ')) + '</small></span></button>'
                + '<div class="au-flot-acts"><button type="button" class="au-link" data-act="farm">Change</button><button type="button" class="au-link" data-act="farmPrev" aria-expanded="false">See its answers</button><button type="button" class="au-link" data-act="farmEdit">Edit the lot</button></div><div class="au-flprev" data-farmprev></div>'
                : '<button type="button" class="au-pin" data-act="farm"><span aria-hidden="true">🗺️</span><span>Use one of your lots<small>Its name, size, place, soil and water come with it, so they are not asked again</small></span></button>')
            + '<div class="au-row' + (askSize ? ' is-2' : '') + '"><div><span class="au-lab">Crop</span><button type="button" class="au-tag' + (c ? '' : ' is-none') + '" data-act="crop"><span>' + (c ? c.icon + ' ' + esc(cropName(l.crop)) : 'Choose the crop') + '</span>' + CHEV + '</button></div>'
            + (askSize ? '<div><span class="au-lab">Size</span><div class="flex gap-2"><input type="number" class="form-input flex-1" data-f="size" min="0" step="any" inputmode="decimal" value="' + (l.size ?? '') + '" placeholder="Like 1.5">'
                + '<button type="button" class="au-pill" data-act="unit">' + (l.unit === 'sqm' ? 'm²' : 'ha') + '</button></div></div>' : '') + '</div>'
            + (Object.keys(ms).length ? '<span class="au-lab">How it goes into the field</span><div class="au-pills">' + Object.entries(ms).map(([k, v]) => '<button type="button" class="au-pill" data-method="' + k + '" aria-pressed="' + (l.method === k) + '">' + esc(v) + '</button>').join('') + '</div>' : '')
            + (l.method === 'tree' ? '<span class="au-lab">How old are the trees?</span><div class="au-tree"><label class="text-xs font-bold text-gray-500">Years<input type="number" class="form-input" data-f="treeY" min="0" max="120" value="' + (l.treeMonths != null ? Math.floor(l.treeMonths / 12) : '') + '"></label>'
                + '<label class="text-xs font-bold text-gray-500">Months<input type="number" class="form-input" data-f="treeM" min="0" max="11" value="' + (l.treeMonths != null ? l.treeMonths % 12 : '') + '"></label></div>' : '')
            + '<div class="au-row is-2"><div><span class="au-lab">Variety <small>(optional, but it sharpens everything)</small></span><div data-var>' + varTag(l) + '</div></div>'
            + (l.method === 'tree' ? '<div></div>' : '<div><span class="au-lab" data-daylab>' + dayLabel(l) + '</span><label class="date-tag w-full" data-empty="Pick the day"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span class="dt-text">Pick the day</span>'
                + '<input type="date" data-f="fieldDate" min="' + B.today + '" max="' + B.latest + '" value="' + (l.fieldDate || '') + '"></label></div>')
            + '</div><div data-notes></div>'
            + (fv ? '' : '<div class="au-row is-2"><div><span class="au-lab">Province</span><button type="button" class="au-tag' + (l.province ? '' : ' is-none') + '" data-act="prov"><span>' + esc(l.province || 'Choose the province') + '</span>' + CHEV + '</button></div>'
                + '<div><span class="au-lab">Town or city</span><button type="button" class="au-tag' + (l.town ? '' : ' is-none') + '" data-act="town"' + (l.province ? '' : ' disabled') + '><span>' + esc(l.town ? townName(l.town) : (l.province ? 'Choose the town' : 'Province first')) + '</span>' + CHEV + '</button></div></div>'
                + '<label class="au-lab">Barangay <small>(optional)</small></label><input type="text" class="form-input w-full" data-f="barangay" maxlength="120" value="' + esc(l.barangay || '') + '">')
            + '<span class="au-lab">Pin on the map <small>(optional)</small></span><button type="button" class="au-pin' + (l.pin ? ' is-set' : '') + '" data-act="pin"><span aria-hidden="true">📍</span><span>' + (l.pin ? 'Pinned at ' + l.pin.lat.toFixed(4) + ', ' + l.pin.lng.toFixed(4) + '<small>Tap to move it</small>' : 'Drop a pin on the lot<small>Optional: the weather and the soil are then read at the exact spot</small>') + '</span></button>'
            + '</div></div>';
    };
    const dayLabel = (l) => ({ transplanted: 'Transplanting day (DAT 0)', direct: 'Sowing day in the field (DAS 0)', planted: 'Planting day (DAP 0)' })[l.method] || 'Planting day';
    const paintLots = () => {
        $('auLots').innerHTML = S.lots.map(lotCard).join('');
        S.lots.forEach((l) => { paintLotNotes(l); paintLotState(l); });
        window.smDateTags?.($('auLots'));
        const cap = S.lotCap;
        $('auAddLot').hidden = cap != null && S.lots.length >= cap;
        $('auAddLot').textContent = '+ Add another lot · ' + Number(S.perLot).toLocaleString() + ' credits';
    };
    const lotEl = (id) => document.querySelector('.au-lot[data-lot="' + id + '"]');
    const lotById = (id) => S.lots.find((l) => String(l.id) === String(id));
    const paintLotState = (l) => {
        const el = lotEl(l.id)?.querySelector('[data-st]');
        if (!el) return;
        const g = lotGap(l);
        el.textContent = g ? 'Not yet' : 'Ready ✓';
        el.classList.toggle('is-ok', !g);
    };
    const paintLotNotes = (l) => {
        const box = lotEl(l.id)?.querySelector('[data-notes]');
        if (!box) return;
        const c = cropOf(l.crop);
        let html = '';
        if (c && l.fieldDate && l.method !== 'tree') {
            const left = daysFrom(B.today, l.fieldDate), p = c.prep || { min: 14, max: 21 };
            const src = p.source ? ' <a href="' + esc(p.source.url) + '" target="_blank" rel="noopener">' + esc(String(p.source.label).split(' (')[0]) + '</a>' : '';
            if (left < p.min) html += '<div class="au-note is-warn"><span>⚠️</span><span><b>Land preparation needs about ' + p.min + (p.max > p.min ? ' to ' + p.max : '') + ' days</b> for ' + esc(cropName(l.crop).split(',')[0].toLowerCase()) + (p.note ? ': ' + esc(p.note.charAt(0).toLowerCase() + p.note.slice(1)) : '.') + ' Your day leaves ' + Math.max(0, left) + '. Anee will squeeze it in, but a later day gives the field a better start.' + src + '</span></div>';
            else html += '<div class="au-note is-ok"><span>✅</span><span>' + left + ' days to prepare the land, enough for the ' + p.min + (p.max > p.min ? ' to ' + p.max : '') + ' it needs.' + src + '</span></div>';
            if (l.method === 'transplanted' && c.nursery) {
                const sow = addDays(l.fieldDate, -Math.round((c.nursery.min + c.nursery.max) / 2));
                html += '<div class="au-note is-info"><span>🌱</span><span>The seedbed is sown about <b>' + fmtDate(sow) + '</b> (DAS 0), ' + c.nursery.min + ' to ' + c.nursery.max + ' days before transplanting.' + (sow < B.today ? ' That day has passed: Anee will plan older seedlings or a quicker seedbed, or pick a later day.' : '') + '</span></div>';
            }
        }
        box.innerHTML = html;
    };
    $('auLots').addEventListener('input', (e) => {
        const card = e.target.closest('.au-lot'); if (!card) return;
        const l = lotById(card.dataset.lot), f = e.target.dataset.f; if (!l || !f) return;
        if (f === 'treeY' || f === 'treeM') {
            const y = Number(card.querySelector('[data-f="treeY"]').value) || 0, m = Number(card.querySelector('[data-f="treeM"]').value) || 0;
            l.treeMonths = y * 12 + Math.min(11, m);
        } else l[f] = e.target.value;
        paintLotState(l);
        if (f === 'fieldDate') paintLotNotes(l);
        later('lot' + l.id, () => saveLot(l), f === 'fieldDate' ? 50 : 700);
    });
    $('auLots').addEventListener('change', (e) => { if (e.target.dataset.f === 'fieldDate') { const l = lotById(e.target.closest('.au-lot').dataset.lot); if (l) { l.fieldDate = e.target.value; paintLotNotes(l); later('lot' + l.id, () => saveLot(l), 50); } } });
    $('auLots').addEventListener('click', async (e) => {
        const card = e.target.closest('.au-lot'); if (!card) return;
        const l = lotById(card.dataset.lot);
        const m = e.target.closest('[data-method]');
        if (m) { l.method = m.dataset.method; await saveLot(l).catch(() => {}); paintLots(); return; }
        const a = e.target.closest('[data-act]'); if (!a) return;
        const act = a.dataset.act;
        if (act === 'unit') { l.unit = l.unit === 'sqm' ? 'ha' : 'sqm'; a.textContent = l.unit === 'sqm' ? 'm²' : 'ha'; later('lot' + l.id, () => saveLot(l), 50); }
        if (act === 'crop') pick('Choose the crop', CROPS.map((c) => ({ key: c.key, label: String(c.label).replace(/ — /g, ', '), icon: c.icon, group: c.group, hint: c.tree ? 'A tree crop' : (c.maturity ? 'About ' + c.maturity + ' days' : '') })), l.crop, async (k) => {
            l.crop = k; const ms = Object.keys(methodsOf(l)); if (!ms.includes(l.method)) l.method = ms[0] || null;
            // A variety from another crop's records does not belong to this one.
            if (l.varRecord && l.varRecord.crop !== k) { l.variety = ''; l.varRecord = null; }
            await saveLot(l).catch(() => {}); paintLots();
            // The troubles are read for the first crop.
            if (SRC) applyLotProfile();
        });
        if (act === 'farm') openFarm(l);
        if (act === 'farmEdit') editFarm(l);
        // The linked lot's answers as cards to slide through (partials/farm-lots).
        if (act === 'farmPrev' && window.aneeLots && window.aneeLots.togglePreview) {
            const fv = farmView(l);
            const host = a.parentNode && a.parentNode.nextElementSibling && a.parentNode.nextElementSibling.matches('[data-farmprev]') ? a.parentNode.nextElementSibling : null;
            if (fv && host) {
                const open = window.aneeLots.togglePreview(host, fv, { crop: l.crop || null, onEdit: () => editFarm(l) });
                a.textContent = open ? 'Hide its answers' : 'See its answers';
                a.setAttribute('aria-expanded', String(open));
            }
        }
        if (act === 'variety') pickVariety(l, a);
        if (act === 'prov') { try { await loadPh(); } catch (_) { return; } pick('Choose the province', Object.keys(PHL).sort().map((p) => ({ key: p, label: p, hint: PHL[p].length + ' towns and cities' })), l.province, async (k) => { if (k !== l.province) l.town = null; l.province = k; await saveLot(l).catch(() => {}); paintLots(); setTimeout(() => lotEl(l.id)?.querySelector('[data-act="town"]')?.click(), 300); }); }
        if (act === 'town' && l.province) { await loadPh(); pick('Choose the town or city', (PHL[l.province] || []).map((t) => ({ key: t, label: townName(t), hint: /\(Capital\)/i.test(t) ? 'The capital' : '' })), l.town, async (k) => { l.town = k; await saveLot(l).catch(() => {}); paintLots(); }); }
        if (act === 'pin') openPin(l);
    });
    $('auAddLot').addEventListener('click', () => modal('<h3>Add another lot?</h3><p>Every lot Anee runs is ' + Number(S.perLot).toLocaleString() + ' credits for the season, taken now and not returned. You have ' + (S.unlimited ? 'unlimited credits' : Number(Math.floor(S.balance)).toLocaleString() + ' credits') + '.</p>'
        + '<div class="au-acts"><button type="button" class="btn btn-white" data-close>Not now</button><button type="button" class="btn btn-primary" data-ok>Add a lot · ' + Number(S.perLot).toLocaleString() + '</button></div>', async (box) => {
        try { const r = await put(U.lotAdd, { agree: true }, 'POST'); S.lots = r.data.state.lots; S.balance = r.data.state.balance; S.lotsPaid = r.data.state.lotsPaid; closeModal(); paintLots(); window.toast?.('Lot added.', 'success'); }
        catch (err) { if (err.data && err.data.outOfCredits) box.querySelector('p').innerHTML += ' <a class="underline font-bold" href="' + U.buy + '">Buy credits</a>'; }
    }));
    // The help card folds.
    $('auHelpFold').querySelector('button').addEventListener('click', () => { const f = $('auHelpFold'); f.classList.toggle('is-open'); f.querySelector('button').setAttribute('aria-expanded', String(f.classList.contains('is-open'))); });

    /* ---- the pin ---- */
    let MAP = null, MARK = null, PINLOT = null, PINAT = null, mapsP = null;
    const loadMaps = () => mapsP ??= new Promise((res, rej) => {
        if (window.google && window.google.maps) return res(window.google.maps);
        if (!B.mapsKey) return rej(new Error('The map is not set up yet.'));
        const cb = '__auMaps' + Date.now(); window[cb] = () => res(window.google.maps);
        const s = document.createElement('script'); s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(B.mapsKey) + '&callback=' + cb; s.async = true;
        s.onerror = () => rej(new Error('The map could not load.')); document.head.appendChild(s);
    });
    const pinSay = () => { $('auPinSay').textContent = PINAT ? 'Pinned at ' + PINAT.lat.toFixed(5) + ', ' + PINAT.lng.toFixed(5) + '.' : 'No pin yet. Tap the lot on the map.'; };
    const setMark = (at) => { PINAT = at; if (MARK) MARK.setMap(null); MARK = at ? new google.maps.Marker({ map: MAP, position: at, animation: google.maps.Animation.DROP }) : null; pinSay(); };
    const openPin = async (l) => {
        PINLOT = l; PINAT = l.pin ? { lat: l.pin.lat, lng: l.pin.lng } : null;
        $('auPinTitle').textContent = 'Drop a pin on ' + (l.name || 'the lot');
        window.openSheet('auPinSheet'); pinSay();
        try { await loadMaps(); } catch (err) { $('auMap').innerHTML = '<p class="p-4 text-sm text-white">' + esc(err.message) + '</p>'; return; }
        const center = PINAT || { lat: 12.8797, lng: 121.774 };
        if (!MAP) {
            MAP = new google.maps.Map($('auMap'), { center, zoom: PINAT ? 16 : 6, mapTypeId: 'hybrid', streetViewControl: false, fullscreenControl: false, mapTypeControl: false, clickableIcons: false, gestureHandling: 'greedy' });
            MAP.addListener('click', (ev) => setMark({ lat: ev.latLng.lat(), lng: ev.latLng.lng() }));
        } else { MAP.setCenter(center); MAP.setZoom(PINAT ? 16 : 6); }
        setMark(PINAT);
    };
    $('auPinMe').addEventListener('click', () => {
        if (!navigator.geolocation) return window.toast?.('This phone cannot share where it is.', 'error');
        navigator.geolocation.getCurrentPosition((p) => { const at = { lat: p.coords.latitude, lng: p.coords.longitude }; if (MAP) { MAP.setCenter(at); MAP.setZoom(17); setMark(at); } }, () => window.toast?.('Could not tell where you are.', 'error'), { enableHighAccuracy: true, timeout: 12000 });
    });
    $('auPinClear').addEventListener('click', () => { if (MAP) setMark(null); else { PINAT = null; pinSay(); } });
    $('auPinSave').addEventListener('click', async () => {
        if (!PINLOT) return;
        PINLOT.pin = PINAT ? { lat: PINAT.lat, lng: PINAT.lng } : null;
        try { await saveLot(PINLOT, PINAT ? {} : { clearPin: true }); window.closeSheet('auPinSheet'); paintLots(); } catch (_) {}
    });

    /* ---- 2. the workers ---- */
    S.crewList = S.canWorkers ? S.workers.map((w) => ({ id: w.id, globalWorkerId: w.globalWorkerId, name: w.name, email: w.email || '', phone: w.phone || '', off: w.off || [] }))
        : (S.answers.crew || []).map((c) => ({ name: c.name, off: c.off || [] }));
    const saveWorkers = () => put(U.workers, { workers: S.crewList.filter((w) => (w.name || '').trim()).map((w) => ({ id: w.id || null, globalWorkerId: w.globalWorkerId || null, name: w.name.trim(), email: w.email || null, phone: w.phone || null, off: w.off })) })
        .then((r) => { (r.data && r.data.workers || []).forEach((w) => { const row = S.crewList.find((c) => c.id === w.id) || S.crewList.find((c) => !c.id && (c.name || '').trim() === w.name); if (row) { row.id = w.id; row.globalWorkerId = w.globalWorkerId || row.globalWorkerId; } }); });
    const paintWorkers = () => {
        $('auWSub').textContent = S.canWorkers ? 'Anee gives out the work by who can come, and emails each one their day. Tap the days each one can work.' : 'Anee gives out the work by who can come. Tap the days each helper can work.';
        $('auWNote').hidden = S.canWorkers;
        $('auWNote').textContent = 'On your plan the helpers are kept for planning only. Worker cards, their logins and their morning emails come with Solo Farmer.';
        const inIds = S.crewList.map((w) => w.globalWorkerId).filter(Boolean);
        $('auRoster').innerHTML = S.canWorkers && S.roster.length ? '<span class="au-lab w-full" style="margin-top:0">From your workers</span>' + S.roster.map((g) => '<button type="button" class="au-pill" data-g="' + g.id + '" aria-pressed="' + inIds.includes(g.id) + '">' + (inIds.includes(g.id) ? '✓ ' : '+ ') + esc(g.name) + '</button>').join('') : '';
        $('auWorkers').innerHTML = S.crewList.map((w, i) => '<div class="au-w" data-w="' + i + '"><div class="au-w-top"><input type="text" class="form-input" data-wf="name" value="' + esc(w.name) + '" placeholder="Name" maxlength="255">'
            + '<button type="button" class="au-x" data-wx aria-label="Remove">✕</button></div>'
            + (S.canWorkers ? '<div class="au-row is-2"><input type="email" class="form-input" data-wf="email" value="' + esc(w.email) + '" placeholder="Email, for the morning list (optional)"><input type="tel" class="form-input" data-wf="phone" value="' + esc(w.phone) + '" placeholder="Phone (optional)"></div>' : '')
            + '<div><span class="text-xs font-bold text-gray-500">Days they can work</span><div class="au-days mt-1">' + DOW.map((d, k) => '<button type="button" class="au-day" data-dow="' + k + '" aria-pressed="' + !w.off.includes(k) + '">' + d + '</button>').join('') + '</div></div></div>').join('')
            || '<p class="text-sm text-gray-500 mb-3">No workers yet. A farm worked by the family alone is fine: Anee plans for a small crew.</p>';
    };
    $('auRoster').addEventListener('click', (e) => {
        const b = e.target.closest('[data-g]'); if (!b) return;
        const g = S.roster.find((x) => String(x.id) === b.dataset.g);
        const at = S.crewList.findIndex((w) => String(w.globalWorkerId) === String(g.id));
        if (at >= 0) S.crewList.splice(at, 1); else S.crewList.push({ id: null, globalWorkerId: g.id, name: g.name, email: g.email || '', phone: g.phone || '', off: (g.off || []).map(Number) });
        paintWorkers(); later('workers', saveWorkers, 300);
    });
    $('auAddWorker').addEventListener('click', () => { S.crewList.push({ id: null, name: '', email: '', phone: '', off: [0] }); paintWorkers(); $('auWorkers').lastElementChild?.querySelector('input')?.focus(); });
    $('auWorkers').addEventListener('input', (e) => { const row = e.target.closest('[data-w]'); if (!row) return; S.crewList[Number(row.dataset.w)][e.target.dataset.wf] = e.target.value; later('workers', saveWorkers, 900); });
    $('auWorkers').addEventListener('click', (e) => {
        const row = e.target.closest('[data-w]'); if (!row) return;
        const w = S.crewList[Number(row.dataset.w)];
        if (e.target.closest('[data-wx]')) { S.crewList.splice(Number(row.dataset.w), 1); paintWorkers(); later('workers', saveWorkers, 200); return; }
        const d = e.target.closest('[data-dow]'); if (!d) return;
        const k = Number(d.dataset.dow);
        w.off = w.off.includes(k) ? w.off.filter((x) => x !== k) : [...w.off, k];
        d.setAttribute('aria-pressed', String(!w.off.includes(k)));
        later('workers', saveWorkers, 600);
    });

    /* ---- 3. the soil ---- */
    S.answers.soil = S.answers.soil || { signs: [], ph: 'unsure', tested: null };
    const saveSoil = () => put(U.answers, { soil: S.answers.soil }).then((r) => { S.answers.soilRead = r.data.soilRead; paintSoilRead(); });
    const paintSoil = () => {
        const sg = S.answers.soil.signs || [];
        $('auSigns').innerHTML = Object.entries(BOOK.signs).map(([k, v]) => '<button type="button" class="au-sign" data-sign="' + k + '" aria-pressed="' + sg.includes(k) + '"><i></i><span><b>' + esc(v[0]) + '</b><small>' + esc(v[1]) + '</small></span></button>').join('');
        $('auPh').innerHTML = Object.entries(BOOK.ph).map(([k, v]) => '<button type="button" class="au-pill" data-ph="' + k + '" aria-pressed="' + ((S.answers.soil.ph || 'unsure') === k) + '" title="' + esc(v[1]) + '">' + esc(v[0]) + '</button>').join('');
        $('auTested').value = S.answers.soil.tested ?? '';
        paintSoilRead();
    };
    const TEX = { clay: 'clay', loam: 'loam', sandy: 'sandy', unsure: 'not sure of the texture' };
    const COND = { acidic: 'acidic', neutral: 'near neutral', alkaline: 'alkaline', sodic: 'sodic (sodium)', saline: 'saline (salty)', acid_sulfate: 'acid sulfate', low_om: 'low in organic matter', waterlogged: 'poorly drained' };
    const paintSoilRead = () => {
        const r = S.answers.soilRead;
        if (!r || (!r.signs.length && r.ph === 'unsure')) { $('auSoilRead').hidden = true; return; }
        $('auSoilRead').hidden = false;
        const bad = ['sodic', 'saline', 'acid_sulfate'];
        $('auSoilRead').innerHTML = '<b>Anee\'s first read:</b> ' + esc(TEX[r.texture] || r.texture) + (r.conditions.length ? ', ' + esc(r.conditions.map((c) => COND[c] || c).join(', ')) : '') + '.'
            + (r.phSure ? '' : ' The pH is read from the signs; a soil test would make it sure.')
            + '<div class="au-chips">' + [r.texture].concat(r.conditions).filter((x) => x && x !== 'unsure').map((c) => '<span class="au-chip' + (bad.includes(c) ? ' is-bad' : (c === 'alkaline' || c === 'acidic' ? ' is-warn' : '')) + '">' + esc(COND[c] || TEX[c] || c) + '</span>').join('') + '</div>';
    };
    $('auSigns').addEventListener('click', (e) => { const b = e.target.closest('[data-sign]'); if (!b) return; const k = b.dataset.sign, sg = S.answers.soil.signs; S.answers.soil.signs = sg.includes(k) ? sg.filter((x) => x !== k) : [...sg, k]; b.setAttribute('aria-pressed', String(S.answers.soil.signs.includes(k))); later('soil', saveSoil, 400); });
    $('auPh').addEventListener('click', (e) => { const b = e.target.closest('[data-ph]'); if (!b) return; S.answers.soil.ph = b.dataset.ph; $('auPh').querySelectorAll('[data-ph]').forEach((x) => x.setAttribute('aria-pressed', String(x === b))); later('soil', saveSoil, 300); });
    $('auTested').addEventListener('input', () => { const v = Number($('auTested').value); S.answers.soil.tested = $('auTested').value !== '' && v >= 2 && v <= 12 ? v : null; later('soil', saveSoil, 800); });

    /* ---- 4. the water ---- */
    // From S, not from the box: a lot can fill the water before this step is painted.
    const saveWater = () => put(U.answers, { water: S.answers.water, waterNote: S.answers.waterNote || '' });
    const paintWater = () => {
        $('auWater').innerHTML = Object.entries(BOOK.water).map(([k, v]) => '<button type="button" class="au-sign is-radio" data-water="' + k + '" aria-pressed="' + (S.answers.water === k) + '"><i></i><span><b>' + esc(v[0]) + '</b><small>' + esc(v[1]) + '</small></span></button>').join('');
        $('auWaterNote').value = S.answers.waterNote || '';
    };
    $('auWater').addEventListener('click', (e) => { const b = e.target.closest('[data-water]'); if (!b) return; S.answers.water = b.dataset.water; $('auWater').querySelectorAll('[data-water]').forEach((x) => x.setAttribute('aria-pressed', String(x === b))); later('water', saveWater, 200); });
    $('auWaterNote').addEventListener('input', () => { S.answers.waterNote = $('auWaterNote').value; later('water', saveWater, 800); });

    /* ---- 5. the troubles ---- */
    S.answers.problems = S.answers.problems || []; S.answers.cropProblems = S.answers.cropProblems || [];
    const saveProblems = () => put(U.answers, { problems: S.answers.problems, cropProblems: S.answers.cropProblems, notes: S.answers.notes || '' });
    // The troubles a lot's last season speaks for. The rest (a typhoon, late fertilizer, few hands...) are still asked.
    const COVERED = ['weeds', 'flood', 'drought', 'insects', 'diseases', 'rats', 'birds', 'snails', 'lodging', 'salt', 'waterlog'];
    const paintProblems = async () => {
        $('auProblems').innerHTML = Object.entries(BOOK.problems).filter(([k]) => !HID.problems || !COVERED.includes(k)).map(([k, v]) =>'<button type="button" class="au-pill" data-p="' + k + '" aria-pressed="' + S.answers.problems.includes(k) + '">' + v[1] + ' ' + esc(v[0]) + '</button>').join('');
        $('auNotes').value = S.answers.notes || '';
        try {
            const r = await window.api(U.problems);
            const rows = r.data.rows || [];
            $('auCropProbLab').hidden = !rows.length;
            $('auCropProblems').innerHTML = rows.map((p) => '<button type="button" class="au-pill" data-cp="' + esc(p.key) + '" aria-pressed="' + S.answers.cropProblems.includes(p.key) + '">' + (p.kind === 'pest' ? '🐛 ' : '🍂 ') + esc(p.name) + '</button>').join('');
        } catch (_) {}
    };
    $('auProblems').addEventListener('click', (e) => { const b = e.target.closest('[data-p]'); if (!b) return; const k = b.dataset.p; S.answers.problems = S.answers.problems.includes(k) ? S.answers.problems.filter((x) => x !== k) : [...S.answers.problems, k]; b.setAttribute('aria-pressed', String(S.answers.problems.includes(k))); later('problems', saveProblems, 400); });
    $('auCropProblems').addEventListener('click', (e) => { const b = e.target.closest('[data-cp]'); if (!b) return; const k = b.dataset.cp; S.answers.cropProblems = S.answers.cropProblems.includes(k) ? S.answers.cropProblems.filter((x) => x !== k) : [...S.answers.cropProblems, k]; b.setAttribute('aria-pressed', String(S.answers.cropProblems.includes(k))); later('problems', saveProblems, 400); });
    $('auNotes').addEventListener('input', () => { S.answers.notes = $('auNotes').value; later('problems', saveProblems, 900); });

    /* ---- the farm's lots: a lot of this season filled from one of them ----
       (partials/farm-lots, 2026-10-16, the owner). The lot brings its name,
       size and place to its card, and the first linked lot with answers is
       the farm's field profile: it fills the soil signs, the pH, the water
       and the troubles, and what it answered is hidden (HID) under a line
       that says where it came from. A question it does not answer is still
       asked, filled while it is empty or still holds what was filled (AUTO
       keeps what was filled this visit); never over what the farmer picked. */
    const AUTO = {};
    const sameList = (a, b) => JSON.stringify([...(a || [])].sort()) === JSON.stringify([...(b || [])].sort());
    let CP_ROWS = null;
    const cropProblemRows = () => CP_ROWS ??= window.api(U.problems).then((r) => (r.data && r.data.rows) || []).catch(() => { CP_ROWS = null; return []; });
    const firstCrop = () => ((S.lots || []).find((l) => l.crop) || {}).crop || null;

    async function loadFarm() {
        const rows = window.aneeLots ? await window.aneeLots.rows() : [];
        // An empty answer while lots are linked is a load that failed: keep what is shown.
        if (rows.length || !Object.keys(LINK).length) FLROWS = rows;
        return FLROWS;
    }
    function fillFromFarm(l, fl) {
        const was = JSON.stringify([l.name, l.size, l.unit, l.province, l.town, l.barangay]);
        if (fl.name) l.name = String(fl.name).slice(0, 255);
        if (Number(fl.areaHa) > 0) { l.size = Number(fl.areaHa); l.unit = 'ha'; }
        if (fl.province && fl.town) { l.province = fl.province; l.town = fl.town; l.barangay = String(fl.barangay || '').slice(0, 120); }
        return JSON.stringify([l.name, l.size, l.unit, l.province, l.town, l.barangay]) !== was;
    }
    const saveLinks = () => put(U.answers, { farmLots: Object.entries(LINK).map(([lotId, farmLotId]) => ({ lotId: Number(lotId), farmLotId })) });
    async function linkLot(l, fl) {
        await loadFarm();
        FLROWS = (FLROWS || []).filter((r) => r.id !== fl.id).concat([fl]);
        LINK[l.id] = fl.id;
        EVER.add(fl.id);
        fillFromFarm(l, fl);
        if (step === 1) paintLots();
        later('links', saveLinks, 80);
        later('lot' + l.id, () => saveLot(l), 120);
        await applyLotProfile();
    }
    function unlinkLot(l) {
        delete LINK[l.id];
        if (step === 1) paintLots();
        later('links', saveLinks, 80);
        applyLotProfile();
    }

    /* The sheet that asks which lot: aneeLots.picker, in a dialog like the lot sheet's. */
    let PICK = null, LINKFOR = null, MUTE = false;
    const fqLock = () => document.documentElement.classList.toggle('fq-lock', !!document.querySelector('.fq-sheet[open]'));
    function farmOpen() {
        const d = $('auFarmSheet');
        if (d.open) return;
        d.showModal();
        fqLock();
        window.registerOverlay?.('fq:auFarmSheet', farmClose);
        d.querySelector('.fq-box').focus({ preventScroll: true });
    }
    function farmClose() {
        const d = $('auFarmSheet');
        if (!d.open || d.classList.contains('is-closing')) return;
        window.unregisterOverlay?.('fq:auFarmSheet');
        const done = () => { d.classList.remove('is-closing'); d.close(); fqLock(); LINKFOR = null; };
        if (still()) { done(); return; }
        d.classList.add('is-closing');
        setTimeout(done, 230);
    }
    $('auFarmSheet').addEventListener('click', (e) => { if (e.target === $('auFarmSheet') || e.target.closest('[data-fq-close]')) farmClose(); });
    $('auFarmSheet').addEventListener('cancel', (e) => { e.preventDefault(); farmClose(); });
    async function onFarmPick(lot) {
        const d = $('auFarmSheet');
        if (MUTE || !lot || !LINKFOR || !d.open || d.classList.contains('is-closing')) return;
        const l = LINKFOR;
        farmClose();
        await linkLot(l, lot);
    }
    async function openFarm(l) {
        if (!window.aneeLots) return;
        LINKFOR = l;
        $('auFarmHand').hidden = !LINK[l.id];
        MUTE = true;
        try {
            if (!PICK) PICK = window.aneeLots.picker($('auFarmPick'), { crop: () => (LINKFOR && LINKFOR.crop) || firstCrop(), value: LINK[l.id] || null, onPick: onFarmPick });
            else await PICK.set(LINK[l.id] || null);
        } finally { MUTE = false; }
        farmOpen();
    }
    $('auFarmHand').addEventListener('click', () => { const l = LINKFOR; farmClose(); if (l) unlinkLot(l); });
    async function editFarm(l) {
        if (!window.aneeLots) return;
        if (!FLROWS) await loadFarm();
        const fl = farmRow(l);
        if (!fl) { openFarm(l); return; }
        // A save fires aneelots:changed, which refills the lots below.
        await window.aneeLots.edit(fl, { crop: l.crop || null });
    }
    async function editSource() {
        if (!window.aneeLots || !SRC) return;
        if (!FLROWS) await loadFarm();
        const fl = (FLROWS || []).find((r) => r.id === SRC.id);
        if (fl) await window.aneeLots.edit(fl, { crop: firstCrop() });
    }
    document.addEventListener('click', (e) => { if (e.target.closest('[data-au-editlot]')) editSource(); });
    // A lot saved anywhere on the page (or in Global Lots' sheet): refill what came from it.
    window.addEventListener('aneelots:changed', async () => {
        if (!Object.keys(LINK).length) return;
        await loadFarm();
        S.lots.forEach((l) => { const fl = farmRow(l); if (fl && fillFromFarm(l, fl)) later('lot' + l.id, () => saveLot(l), 200); });
        if (step === 1) paintLots();
        applyLotProfile();
    });

    /* The farm's field profile, from the first linked lot that has answers. */
    let APPLY = Promise.resolve();
    const applyLotProfile = () => (APPLY = APPLY.then(applyNow).catch((err) => console.error(err)));
    async function applyNow() {
        if (!FLROWS) return;
        let fl = null;
        for (const l of S.lots) { const r = farmRow(l); if (r && r.count) { fl = r; break; } }
        if (!fl) {
            if (!FLROWS.length && Object.keys(LINK).length) return;
            HID = Object.assign({}, NOHID);
            SRC = null;
            // The profile came from a lot no longer linked: it goes with it.
            const had = S.answers.fieldProfileFrom, hid = had ? Number(had.id) : 0;
            if (hid && EVER.has(hid) && !Object.values(LINK).includes(hid)) {
                S.answers.fieldProfile = null; S.answers.fieldProfileFrom = null;
                later('fieldProfile', () => put(U.answers, { fieldProfile: null, fieldProfileFrom: null }), 200);
            }
            afterApply();
            return;
        }
        const L = await window.aneeLots.legacy(fl, firstCrop());
        HID = hidFrom(fl.answers, L);
        SRC = { id: fl.id, name: fl.name, answers: fl.answers };
        const pay = window.aneeLots.payload(fl);
        const from = { id: fl.id, name: String(fl.name || '').slice(0, 120) };
        if (JSON.stringify([S.answers.fieldProfile || null, S.answers.fieldProfileFrom || null]) !== JSON.stringify([pay.fieldProfile, from])) {
            S.answers.fieldProfile = pay.fieldProfile;
            S.answers.fieldProfileFrom = from;
            later('fieldProfile', () => put(U.answers, { fieldProfile: S.answers.fieldProfile, fieldProfileFrom: S.answers.fieldProfileFrom }), 300);
        }
        await fillAnswers(fl.answers || {}, L);
        afterApply();
    }
    async function fillAnswers(answers, L) {
        const soil = S.answers.soil, soilWas = JSON.stringify(soil);
        const signs = (L.soilSignsAuto || []).filter((k) => BOOK.signs[k]);
        if (HID.signs || !soil.signs.length || sameList(soil.signs, AUTO.signs)) { soil.signs = signs; AUTO.signs = signs.slice(); }
        const ph = BOOK.ph[L.phLevel] ? L.phLevel : 'unsure';
        if (HID.ph || (soil.ph || 'unsure') === 'unsure' || soil.ph === AUTO.ph) { soil.ph = ph; AUTO.ph = ph; }
        if (HID.tested || soil.tested == null || soil.tested === AUTO.tested) { soil.tested = L.phValue ?? null; AUTO.tested = soil.tested; }
        if (JSON.stringify(soil) !== soilWas) { if (step === 3) paintSoil(); later('soil', saveSoil, 300); }

        const w = BOOK.water[L.waterAuto] ? L.waterAuto : null;
        if ((HID.water || !S.answers.water || S.answers.water === AUTO.water) && (w || AUTO.water) && S.answers.water !== w) {
            S.answers.water = w; AUTO.water = w;
            if (step === 4) paintWater();
            later('water', saveWater, 300);
        }

        const probWas = JSON.stringify([S.answers.problems, S.answers.cropProblems]);
        const auto = (L.problemsAuto || []).filter((k) => BOOK.problems[k]);
        if (HID.problems) { S.answers.problems = [...new Set(auto.concat(S.answers.problems.filter((k) => !COVERED.includes(k))))]; AUTO.problems = auto.slice(); }
        else if (!S.answers.problems.length || sameList(S.answers.problems, AUTO.problems)) { S.answers.problems = auto; AUTO.problems = auto.slice(); }
        if (HID.problems || !S.answers.cropProblems.length || sameList(S.answers.cropProblems, AUTO.cropProblems)) {
            // The crops' own pests and diseases the lot names, where this step offers them.
            const offered = new Set((await cropProblemRows()).map((r) => r.key));
            S.answers.cropProblems = [].concat(answers.outbreaks || []).map((k) => k.replace(/^pest:/, 'pests:').replace(/^disease:/, 'diseases:')).filter((k) => offered.has(k));
            AUTO.cropProblems = S.answers.cropProblems.slice();
        }
        if (JSON.stringify([S.answers.problems, S.answers.cropProblems]) !== probWas) { if (step === 5) paintProblems(); later('problems', saveProblems, 300); }
    }
    function afterApply() {
        navSay();
        paintFrom();
        if (step === 5) paintProblems();
        if (step === 8) paintReview();
    }

    /* Where the hidden answers came from: "From North field", the answers, and Edit the lot. */
    const pairsFor = (a, keys) => {
        const FC = window.aneeFieldCard, Q = (window.aneeFieldProfile || {}).questions || {};
        if (!FC || !a) return [];
        const shorts = new Set(keys.map((k) => (Q[k] || {}).short).filter(Boolean).concat(keys.includes('acidity') ? ['Known pH'] : []));
        return FC.pairs(a, {}).filter((p) => shorts.has(p[0]));
    };
    const fromBox = (box, keys) => {
        if (!box) return;
        if (!SRC || !keys.length) { box.hidden = true; return; }
        box.innerHTML = '<p><span>From <b>' + esc(SRC.name) + '</b>, your lot</span><button type="button" class="au-link" data-au-editlot>Edit the lot</button></p>'
            + (window.aneeFieldCard ? window.aneeFieldCard.said(pairsFor(SRC.answers, keys)) : '');
        box.hidden = false;
    };
    function paintFrom() {
        const by = { signs: 'signs', ph: 'ph', tested: 'tested', cropprob: 'problems' };
        document.querySelectorAll('.au [data-q]').forEach((el) => { el.hidden = !!HID[by[el.dataset.q]]; });
        const a = (SRC && SRC.answers) || {};
        fromBox($('auSoilFrom'), [...(HID.signs ? ['texture', 'salt'].concat(filled(a, 'texture') ? [] : ['rocks']) : []), ...(HID.ph ? ['acidity'] : [])]);
        fromBox($('auProbFrom'), HID.problems ? ['outbreaks', 'weeds', 'flooding', 'drainage'] : []);
        $('auProbSub').textContent = HID.problems ? 'Anything else that cost you a harvest? Your lot already says what troubled its last crop.' : 'Pick the troubles of past seasons that cost you the most. Anee plans to get ahead of them.';
    }

    /* The variety sheet, for one lot. */
    async function pickVariety(l) {
        if (!window.aneeVarietyPick) return;
        const c = cropOf(l.crop) || {};
        const w = (BOOK.water[S.answers.water] || [])[2] || '';
        const m = l.fieldDate ? Number(String(l.fieldDate).slice(5, 7)) : 0;
        const res = await window.aneeVarietyPick.open({ crop: l.crop || '', cropLabel: String(c.label || '').split(' (')[0].split(' — ')[0],
            location: [townName(l.town), l.province].filter(Boolean).join(', '), season: m ? (m >= 6 && m <= 10 ? 'wet' : 'dry') : '',
            water: w === 'partial' ? 'limited' : w, value: { name: l.variety || '', record: l.varRecord || null },
            ownNote: 'Not on record, so Anee looks it up when she reads the farm, and says plainly if nothing is found.' });
        if (!res) return;
        l.variety = String(res.name || '').slice(0, 120);
        l.varRecord = res.record ? Object.assign({ crop: l.crop }, res.record) : null;
        const box = lotEl(l.id)?.querySelector('[data-var]');
        if (box) {
            box.innerHTML = varTag(l);
            if (!still()) box.querySelector('.au-tag')?.classList.add('is-bump');
        }
        later('lot' + l.id, () => saveLot(l), 50);
    }

    /* ---- 6. Anee reads the farm ---- */
    const READS = [['🌾', 'Your varieties, looked up on the web: maturity, yield, weak points'], ['🌏', 'ENSO and the season ahead, with PAGASA\'s outlook'], ['🌦️', 'Sixteen days of weather at each lot'], ['🧪', 'Your soil from its signs and the soil model at the spot'], ['🐛', 'Your past troubles, to get ahead of them']];
    const paintReading = () => {
        const A = S.analysis;
        if (!A) {
            $('auReading').innerHTML = '<div class="au-card au-reading-cta"><img src="' + @json(\App\Support\AneeEmoji::url('thinking')) + '" alt="">'
                + '<h3 class="au-h">Let Anee read your farm</h3><p class="au-sub" style="margin-bottom:0">Included in Anee Auto. It takes a minute or two.</p>'
                + '<div class="au-reads">' + READS.map((r, i) => '<div style="--i:' + i + '"><span>' + r[0] + '</span>' + esc(r[1]) + '</div>').join('') + '</div>'
                + '<button type="button" class="au-big" id="auRead">🔎 Read the farm</button></div>';
            $('auRead').addEventListener('click', runReading);
            return;
        }
        const lotName = (id) => (S.lots.find((l) => l.id === id) || {}).name || 'Lot';
        const sea = A.season || {};
        $('auReading').innerHTML = '<div class="au-rl" style="--i:0"><h4>🌏 ' + esc(A.headline || 'The season ahead') + '</h4>'
            + (sea.outlook ? '<p class="mt-1">' + esc(sea.outlook) + '</p>' : '')
            + '<div class="au-facts">' + [['ENSO', sea.enso], ['Rain', sea.rain], ['Typhoons', sea.typhoon], ['Heat', sea.heat]].filter((x) => x[1]).map((x) => '<div><small>' + x[0] + '</small><b class="text-xs font-semibold">' + esc(x[1]) + '</b></div>').join('') + '</div>'
            + ((sea.watch || []).length ? '<h5>Watch for</h5><ul>' + sea.watch.map((w) => '<li>' + esc(w) + '</li>').join('') + '</ul>' : '') + '</div>'
            + (A.lots || []).map((r, i) => {
                const v = r.variety || {}, so = r.soil || {}, g = r.goal || {}, f = r.fertilizer || {};
                return '<div class="au-rl" style="--i:' + (i + 1) + '"><h4>🌾 ' + esc(lotName(r.lotId)) + (v.name ? ' · ' + esc(v.name) : '') + '</h4>'
                    + '<div class="au-facts"><div><small>Matures in</small><b>' + (v.maturityDays ? v.maturityDays + ' days' : 'not found') + '</b></div><div><small>Potential</small><b>' + (v.yieldPotentialTPerHa ? v.yieldPotentialTPerHa + ' t/ha' : 'not found') + '</b></div>'
                    + '<div><small>Usual</small><b>' + (v.averageTPerHa ? v.averageTPerHa + ' t/ha' : 'not found') + '</b></div><div><small>Realistic goal</small><b>' + (g.tPerHa ? g.tPerHa + ' t/ha' : 'not set') + '</b></div></div>'
                    + (v.traits && v.traits.length ? '<h5>The variety</h5><ul>' + v.traits.slice(0, 5).map((t) => '<li>' + esc(t) + '</li>').join('') + (v.cautions || []).slice(0, 3).map((t) => '<li>⚠️ ' + esc(t) + '</li>').join('') + '</ul>' : '')
                    + (so.reading ? '<h5>The soil</h5><p>' + esc(so.reading) + '</p>' + ((so.lockouts || []).length ? '<div class="au-chips mt-1">' + so.lockouts.map((x) => '<span class="au-chip is-warn">🔒 ' + esc(x) + '</span>').join('') + (so.unlocks || []).map((x) => '<span class="au-chip">🔓 ' + esc(x) + '</span>').join('') + '</div>' : '') : '')
                    + (f.focus ? '<h5>The fertilizer that matters</h5><p>' + esc(f.focus) + '</p>' + ((f.critical || []).length ? '<ul>' + f.critical.map((c) => '<li><b>' + esc(c.stage) + ':</b> ' + esc(c.why) + '</li>').join('') + '</ul>' : '') : '')
                    + ((r.risks || []).length ? '<h5>Risks to get ahead of</h5><ul>' + r.risks.slice(0, 5).map((x) => '<li><b>' + esc(x.risk) + '.</b> ' + esc(x.prevent || x.why || '') + '</li>').join('') + '</ul>' : '')
                    + (g.why ? '<p class="mt-2 text-xs text-gray-500">' + esc(g.why) + '</p>' : '') + '</div>';
            }).join('')
            + (A.water || A.workforce ? '<div class="au-rl"><h4>💧 Water and hands</h4>' + (A.water ? '<p>' + esc(A.water) + '</p>' : '') + (A.workforce ? '<p class="mt-1">' + esc(A.workforce) + '</p>' : '') + '</div>' : '')
            + '<p class="text-center text-xs text-gray-400 mb-3">Read ' + esc(A.at || '') + (A.searched ? ', with what Anee found on the web' : '') + '. ' + (S.readingsLeft > 0 ? '<button type="button" class="underline font-bold text-brand-700" id="auReread">Read it again</button> (changed an answer?)' : '') + '</p>';
        $('auReread')?.addEventListener('click', runReading);
    };
    const runReading = async () => {
        await flush();
        window.aneeWait.show({ title: 'Anee is reading your farm…', sub: 'The varieties, the season, the soil and the water. A minute or two.', stay: 'Stay on this page while she reads. Nothing is charged: it is part of Anee Auto.',
            lines: ['Looking up your variety', 'Reading the ENSO outlook', 'Checking the weather at each lot', 'Reading the soil from its signs', 'Weighing your past troubles'] });
        try {
            let r = await window.api(U.analyze, { method: 'POST' });
            const d = r.data && r.data.status === 'ready' ? r.data : await window.aneeWait.poll({ id: ID, job: U.job, phases: window.aneeWait.phases.research, every: 3000, limit: 260 });
            S.analysis = d.analysis; S.readingsLeft = Math.max(0, (S.readingsLeft || 3) - 1); S.step = Math.max(S.step, 7);
            await window.aneeWait.done({ title: 'Done!', line: 'Here is how I read your farm.' });
            paintReading();
        } catch (err) { window.aneeWait.fail(); window.toast?.(err.message, 'error'); }
    };

    /* ---- 7. the fertilizer ---- */
    let FLOT = null;
    const NAMES = { N: 'Nitrogen', P2O5: 'Phosphorus', K2O: 'Potassium' };
    const LAB = { N: 'N', P2O5: 'P₂O₅', K2O: 'K₂O' };
    const MICRO = { Zn: 'Zinc: on high pH or long flooded soil, plants stay stunted and bronze (khaira)', B: 'Boron: poor flowering and fruit set', Ca: 'Calcium: blossom end rot and tip burn', S: 'Sulfur: pale young leaves, less protein',
        Mg: 'Magnesium: yellowing between the veins of old leaves', Si: 'Silicon: weaker stems and more blast in rice', Fe: 'Iron: young leaves pale on alkaline soil', Mn: 'Manganese: pale patches on young leaves', Cu: 'Copper: weak heads, poor grain', Mo: 'Molybdenum: legumes fix less nitrogen' };
    const risks = (fert) => {
        const n = fert.needs || {}, out = [];
        const st = (k) => (n[k] || {}).state;
        if (st('N') === 'over') out.push(['🌾', 'Too much nitrogen: the crop grows soft and tall and may <b>lodge</b> (fall over), and draws <b>planthoppers, leaf folders, blast and sheath blight</b>.']);
        if (st('N') === 'short') out.push(['🍂', 'Short of nitrogen: fewer tillers or leaves, a pale crop and a lower harvest.']);
        if (st('P2O5') === 'short') out.push(['🌱', 'Short of phosphorus: weak roots, slow early growth and a later harvest.']);
        if (st('P2O5') === 'over') out.push(['🔒', 'Too much phosphorus ties up zinc and iron.']);
        if (st('K2O') === 'short') out.push(['🪵', 'Short of potassium: weak stems that <b>lodge</b>, more <b>sheath blight and stem rot</b>, and poorly filled grain or fruit.']);
        if (st('K2O') === 'over') out.push(['⚖️', 'Too much potassium crowds out magnesium and calcium.']);
        if (st('N') === 'over' && st('K2O') === 'short') out.push(['⚠️', '<b>High lodging risk:</b> lots of nitrogen with too little potassium is the classic recipe for a flattened field before harvest.']);
        Object.entries(fert.micros || {}).forEach(([k, m]) => { if (m.state === 'short' && MICRO[k]) out.push(['🧪', 'Short of ' + MICRO[k] + '.']); });
        return out;
    };
    const paintFert = () => {
        if (!FLOT || !S.lots.some((l) => l.id === FLOT)) FLOT = S.lots[0]?.id;
        $('auFTabs').innerHTML = S.lots.map((l) => '<button type="button" class="au-ltab' + (l.id === FLOT ? ' is-on' : '') + '" data-l="' + l.id + '">' + esc(l.name || 'Lot') + ' <em>' + (l.fert ? (l.imbalance ? '⚖️' : '✅') : '○') + '</em></button>').join('');
        const l = lotById(FLOT);
        if (!l) { $('auFPane').innerHTML = ''; return; }
        const read = ((S.analysis || {}).lots || []).find((r) => r.lotId === l.id) || {};
        const fr = read.fertilizer || {};
        let html = '<div class="au-rl" style="--i:0"><h4>' + (cropOf(l.crop)?.icon || '🌱') + ' ' + esc(l.name) + ' · ' + esc(cropName(l.crop).split(',')[0]) + (l.size ? ' · ' + l.size + ' ' + (l.unit === 'sqm' ? 'm²' : 'ha') : '') + '</h4>';
        if (fr.focus) html += '<h5>Anee\'s advice for this soil</h5><p>' + esc(fr.focus) + '</p>'
            + ((fr.goodSources || []).length ? '<div class="au-chips mt-1">' + fr.goodSources.map((x) => '<span class="au-chip">👍 ' + esc(x) + '</span>').join('') + (fr.avoid || []).map((x) => '<span class="au-chip is-warn">✋ ' + esc(x) + '</span>').join('') + '</div>' : '')
            + ((fr.micros || []).length ? '<p class="mt-2 text-sm"><b>Micronutrients and foliars worth adding:</b> ' + esc(fr.micros.join(', ')) + '</p>' : '');
        if (!l.fert) {
            html += '<div class="text-center mt-4"><button type="button" class="au-big" data-open-fert>🧪 Plan the fertilizer for ' + esc(l.name) + '</button><p class="text-xs text-gray-500 mt-2">Common grades, Yara, MAP, DAP, Philippine products, or your own label with its NPK, sulfur, calcium and trace elements.</p></div>';
        } else {
            const f = l.fert, goal = (f.goal || {}).tPerHa || read.goal?.tPerHa || null, reach = f.reach != null ? Number(f.reach) : null;
            html += '<h5>What the plan gives against the need</h5><div class="au-bars">' + ['N', 'P2O5', 'K2O'].map((k) => {
                const n = (f.needs || {})[k]; if (!n) return '';
                const scale = Math.max(n.need * 1.45, n.plan * 1.05, 1);
                return '<div class="au-bar is-' + (n.state === 'right' ? 'ok' : n.state) + '"><b>' + LAB[k] + '</b><span class="au-bar-t"><span data-w="' + Math.min(100, n.plan / scale * 100) + '"></span><i style="left:' + (n.need / scale * 100) + '%"></i></span><em>' + (n.state === 'short' ? 'Short' : n.state === 'over' ? 'Too much' : 'Enough') + '</em></div>';
            }).join('') + '</div><p class="text-xs text-gray-500 mt-1">The dark mark is the need for your goal; the bar is your plan, in kg per hectare (' + esc(f.npkPerHa || '') + ').</p>';
            const micro = Object.entries(f.micros || {}).filter(([, m]) => ['short', 'over', 'right', 'extra'].includes(m.state));
            if (micro.length) html += '<h5>Secondary and micronutrients</h5><div class="au-chips">' + micro.map(([k, m]) => '<span class="au-chip' + (m.state === 'short' ? ' is-warn' : m.state === 'over' ? ' is-bad' : '') + '">' + esc(k) + ' · ' + ({ short: 'short', over: 'too much', right: 'enough', extra: 'extra' })[m.state] + '</span>').join('') + '</div>';
            if (reach != null) {
                const t = goal ? Math.round(goal * reach / 10) / 10 : null;
                html += '<div class="au-yield"><span class="au-gauge"><svg viewBox="0 0 100 100"><circle class="bg" cx="50" cy="50" r="42" pathLength="1"/><circle class="fg" cx="50" cy="50" r="42" pathLength="1" data-reach="' + Math.min(100, reach) + '"/></svg><b>' + Math.round(reach) + '%</b></span>'
                    + '<p>' + (t ? 'The barrel holds about <b>' + t + ' t/ha</b> of your ' + goal + ' t/ha goal.' : 'The plan reaches <b>' + Math.round(reach) + '%</b> of the goal.') + ' The shortest plank sets the harvest; the others cannot raise it.</p></div>';
            }
            const rk = risks(f);
            html += rk.length ? '<h5>Risks from this plan</h5>' + rk.map((r, i) => '<div class="au-risk" style="animation-delay:' + i * 60 + 'ms"><span>' + r[0] + '</span><span>' + r[1] + '</span></div>').join('') : '<div class="au-note is-ok"><span>✅</span><span>Balanced for your goal. Anee times each application for the moments the crop needs it most.</span></div>';
            if (l.imbalance) html += '<div class="au-note is-info"><span>📝</span><span>You chose to go on with this plan: ' + esc(BOOK.imbalance[l.imbalanceReason] || 'noted') + '. Anee plans with it and leans on timing and foliars to make the most of it.</span></div>';
            html += '<div class="flex gap-2 mt-4"><button type="button" class="btn btn-white flex-1" data-open-fert>Change the plan</button></div>'
                + '<p class="au-disc">These bars and the barrel are a guide, not a promise. The real need and harvest depend on the variety, how much of its own nutrients your soil gives, the weather, and how and when the fertilizer goes on. A soil test makes all of it surer.</p>';
        }
        $('auFPane').innerHTML = html + '</div>';
        requestAnimationFrame(() => {
            $('auFPane').querySelectorAll('[data-w]').forEach((s) => { s.style.width = s.dataset.w + '%'; });
            $('auFPane').querySelectorAll('[data-reach]').forEach((c) => { c.style.strokeDashoffset = String(1 - Number(c.dataset.reach) / 100); });
        });
        $('auNext').textContent = S.lots.findIndex((x) => x.id === FLOT) < S.lots.length - 1 ? 'Next lot' : 'Next: Start';
    };
    $('auFTabs').addEventListener('click', (e) => { const b = e.target.closest('[data-l]'); if (b) { FLOT = Number(b.dataset.l); paintFert(); } });
    $('auFPane').addEventListener('click', (e) => { if (e.target.closest('[data-open-fert]')) openFert(lotById(FLOT)); });

    /* NPK Plus, in a window of its own, prefilled from the lot and the reading. */
    const NPK_CONDS = ['acidic', 'neutral', 'alkaline', 'sodic', 'saline', 'acid_sulfate', 'low_om', 'peat', 'waterlogged'];
    const openFert = (l) => {
        const read = ((S.analysis || {}).lots || []).find((r) => r.lotId === l.id) || {};
        const sr = S.answers.soilRead || {};
        const soilTex = (read.soil || {}).texture && read.soil.texture !== 'unsure' ? read.soil.texture : (sr.texture || 'unsure');
        const conds = [...new Set([...(sr.conditions || []), ...((read.soil || {}).conditions || []), ...(((read.soil || {}).ph && read.soil.ph !== 'unsure') ? [read.soil.ph] : [])])].filter((c) => NPK_CONDS.includes(c));
        const pre = { calcId: l.calcId || null, crop: l.crop, variety: l.variety || ((read.variety || {}).name || ''), areaValue: Number(l.size) || 1, areaUnit: l.unit === 'sqm' ? 'sqm' : 'ha',
            areaHa: l.unit === 'sqm' ? (Number(l.size) || 0) / 10000 : (Number(l.size) || 1), potential: (read.variety || {}).yieldPotentialTPerHa || null, target: (read.goal || {}).tPerHa || null,
            prov: l.province || null, town: l.town || null, water: (BOOK.water[S.answers.water] || [])[2] || 'irrigated', planted: l.fieldDate || '', texture: soilTex, conditions: conds,
            ph: (S.answers.soil || {}).tested ?? null, say: 'Add the fertilizers you plan for ' + (l.name || 'this lot') + ', then Use this plan.',
            // The field in detail: NPK Plus counts it in the need too (2026-10-15).
            fieldProfile: S.answers.fieldProfile || null };
        $('auFrameTitle').textContent = 'Fertilizer for ' + (l.name || 'the lot');
        const fr = $('auFrame'), ifr = $('auFrameIf');
        fr.hidden = false; void fr.offsetWidth; fr.classList.add('is-on');
        document.documentElement.style.overflow = 'hidden';
        const onMsg = async (e) => {
            if (e.origin !== location.origin || !e.data) return;
            if (e.data.type === 'npk-ready') ifr.contentWindow.postMessage({ type: 'auto-prefill', data: pre }, location.origin);
            if (e.data.type === 'npk-plan') {
                try {
                    const r = await put(U.fert, { lotId: l.id, calcId: e.data.calcId });
                    l.calcId = e.data.calcId; l.fert = r.data.fert; if (r.data.balanced) { l.imbalance = false; l.imbalanceReason = null; }
                    closeFert(); paintFert();
                    if (!r.data.balanced) askImbalance(l, r.data.off || []);
                    else window.toast?.('Plan saved for ' + l.name + '.', 'success');
                } catch (_) {}
            }
        };
        window.removeEventListener('message', window.__auMsg || (() => {}));
        window.__auMsg = onMsg; window.addEventListener('message', onMsg);
        ifr.src = U.npk + '&lot=' + l.id + '&t=' + Date.now();
    };
    const closeFert = () => { const fr = $('auFrame'); fr.classList.remove('is-on'); document.documentElement.style.overflow = ''; setTimeout(() => { fr.hidden = true; $('auFrameIf').src = 'about:blank'; }, 400); };
    $('auFrameClose').addEventListener('click', closeFert);
    const askImbalance = (l, off) => {
        const words = off.map((k) => NAMES[k] || k).join(', ');
        modal('<h3>This plan is off balance</h3><p>' + (words ? 'For ' + esc(l.name) + ', ' + esc(words) + ' ' + (off.length > 1 ? 'are' : 'is') + ' not where the crop needs ' + (off.length > 1 ? 'them' : 'it') + '. ' : '') + 'You can go back and add or change a product, or go on with it. If you go on, tell Anee why: she plans around it.</p>'
            + '<div class="dt-rows" id="auImbRows">' + Object.entries(BOOK.imbalance).map(([k, v]) => '<button type="button" class="dt-row" data-r="' + k + '"><span class="dt-row-body"><b>' + esc(v) + '</b></span></button>').join('') + '</div>'
            + '<textarea class="form-textarea w-full mt-3" id="auImbNote" rows="2" maxlength="1000" placeholder="Anything to add (optional)"></textarea>'
            + '<div class="au-acts"><button type="button" class="btn btn-white" data-fix>Change the plan</button><button type="button" class="btn btn-primary" data-ok disabled>Go on with it</button></div>', async (box) => {
            const r = box.querySelector('.dt-row.is-on'); if (!r) return;
            try { await put(U.imbalance, { lotId: l.id, reason: r.dataset.r, note: box.querySelector('#auImbNote').value }); l.imbalance = true; l.imbalanceReason = r.dataset.r; closeModal(); paintFert(); } catch (_) {}
        }, (box) => {
            box.querySelector('#auImbRows').addEventListener('click', (e) => { const b = e.target.closest('[data-r]'); if (!b) return; box.querySelectorAll('[data-r]').forEach((x) => x.classList.toggle('is-on', x === b)); box.querySelector('[data-ok]').disabled = false; });
            box.querySelector('[data-fix]').addEventListener('click', () => { closeModal(); openFert(l); });
        });
    };

    /* ---- 8. start ---- */
    const paintReview = () => {
        const sr = S.answers.soilRead || {};
        $('auReview').innerHTML = S.lots.map((l) => '<div><b>' + esc(l.name) + '</b><span>' + (cropOf(l.crop)?.icon || '') + ' ' + esc(cropName(l.crop)) + (l.variety ? ', ' + esc(l.variety) : '') + ' · ' + (l.method === 'tree' ? 'standing trees' : esc(dayLabel(l).split(' (')[0].toLowerCase()) + ' ' + fmtDate(l.fieldDate))
            + ' · ' + (l.fert ? (l.imbalance ? 'fertilizer plan kept as is' : 'fertilizer planned') : '<span class="text-amber-700 font-bold">no fertilizer plan yet</span>') + '</span></div>').join('')
            + '<div><b>Workers</b><span>' + (S.crewList.length ? esc(S.crewList.map((w) => w.name).filter(Boolean).join(', ')) : 'The family') + '</span></div>'
            + '<div><b>Soil</b><span>' + esc([TEX[sr.texture] || 'not sure'].concat((sr.conditions || []).map((c) => COND[c] || c)).join(', ')) + '</span></div>'
            + '<div><b>Water</b><span>' + esc((BOOK.water[S.answers.water] || ['not said'])[0]) + '</span></div>'
            + (SRC ? '<div><b>From your lot</b><span>' + esc(SRC.name) + ' gave ' + esc(andWords([HID.signs || HID.ph ? 'the soil' : '', HID.water ? 'the water' : '', HID.problems ? 'last season\'s troubles' : ''].filter(Boolean)) || 'its answers') + ' · <button type="button" class="au-link" data-au-editlot>Edit the lot</button></span></div>'
                : (S.answers.fieldProfile && window.aneeFieldProfile && window.aneeFieldProfile.count(S.answers.fieldProfile) ? '<div><b>Field in detail</b><span>' + window.aneeFieldProfile.count(S.answers.fieldProfile) + ' answers about the soil, the water and last season</span></div>' : ''));
    };
    const andWords = (a) => a.length < 2 ? (a[0] || '') : a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1];
    $('auGenerate').addEventListener('click', async () => {
        await flush();
        const missing = S.lots.find((l) => !l.fert && !l.imbalance);
        if (missing) return window.toast?.(missing.name + ' has no fertilizer plan yet.', 'error');
        const n = S.lots.length, phases = { start: { label: 'Reading everything again', lo: 2, hi: 8, tau: 12 } };
        S.lots.forEach((l, i) => { phases['lot-' + (i + 1)] = { label: 'Planning ' + l.name, lo: 8 + i * 84 / n, hi: 8 + (i + 1) * 84 / n, tau: 80 }; });
        phases.placing = { label: 'Placing the days around the weather and the workers', lo: 92, hi: 98, tau: 15 };
        window.aneeWait.show({ title: 'Anee is planning your season…', sub: 'Every task, lot by lot, then placed around the weather and your workers.', stay: 'Stay on this page while she plans. It is part of Anee Auto.',
            lines: ['Splitting your fertilizer at the critical moments', 'Timing the water to your source', 'Planning prevention before trouble', 'Checking every spray for safety', 'Fitting the work to your workers'] });
        try {
            const r = await window.api(U.generate, { method: 'POST' });
            if (!(r.data && r.data.status === 'ready')) await window.aneeWait.poll({ id: ID, job: U.job, phases, every: 3500, limit: 500 });
            await window.aneeWait.done({ title: 'Your season is planned!', line: 'I will keep it on track every day.' });
            location.href = @json(route('auto.show', $auto->id));
        } catch (err) { window.aneeWait.fail(); window.toast?.(err.message, 'error'); }
    });

    /* ---- a modal ---- */
    const modal = (html, onOk, onMount) => {
        const m = $('auModal'), box = $('auModalBox');
        box.innerHTML = html; m.hidden = false; void m.offsetWidth; m.classList.add('is-on');
        box.querySelectorAll('[data-close]').forEach((b) => b.addEventListener('click', closeModal));
        box.querySelector('[data-ok]')?.addEventListener('click', () => onOk && onOk(box));
        onMount && onMount(box);
    };
    const closeModal = () => { const m = $('auModal'); m.classList.remove('is-on'); setTimeout(() => { m.hidden = true; }, 280); };
    $('auModal').addEventListener('click', (e) => { if (e.target === $('auModal')) closeModal(); });

    // Resume where the farmer left off.
    $('auTitleTop').textContent = S.title || 'Anee Auto';
    show(step);
    // The farm's lots this season was filled from, as they are now.
    if (Object.keys(LINK).length) loadFarm().then(() => {
        if (!FLROWS) return;
        S.lots.forEach((l) => { const fl = farmRow(l); if (fl && fillFromFarm(l, fl)) later('lot' + l.id, () => saveLot(l), 200); });
        if (step === 1) paintLots();
        applyLotProfile();
    });
})();
</script>
@endpush
