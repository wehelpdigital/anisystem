@extends('layouts.app')
@section('title', 'Anee Auto setup')
@section('body-class', 'hide-tabbar no-footer')
@section('page-title', 'Anee Auto')
@section('page-subtitle', 'Setting up your season')
@section('back', \App\Support\BackTo::url(route('sm.index')))

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
    /* No footer on Anee Auto (2026-10-28, the owner): the buttons sit at the foot of the screen, even on a short step. */
    main:has(> .au) { display: flex; flex-direction: column; padding-bottom: 0; }
    main > .au { width: 100%; flex: 1 0 auto; display: flex; flex-direction: column; }
    main > .au > .au-nav { margin-top: auto; }
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
    /* Steps fade, never slide (2026-10-16, the owner). */
    .au-step.is-on { display: block; animation: auIn .26s ease .1s both; }
    .au-step.is-back { animation-name: auInBack; }
    @keyframes auIn { from { opacity: 0; } }
    @keyframes auInBack { from { opacity: 0; } }
    .au-card { border-radius: 1.2rem; background: var(--color-white); border: 1px solid var(--color-gray-200); padding: 1.05rem 1.1rem; margin-bottom: .9rem; }
    .au-note.is-ask { background: #eef2ff; border-color: #c7d2fe; color: #312e81; }
    html.dark .au-note.is-ask { background: #1b1f3a; border-color: #2f3770; color: #c7d2fe; }
    /* A worker's pay, and the crew check (2026-10-28). */
    .au-pay { display: grid; gap: .3rem; margin-top: .6rem; }
    .au-pay > span:first-child { font-size: .72rem; font-weight: 800; color: var(--color-gray-500); }
    .au-pay > span small { font-weight: 600; }
    .au-pay-in { display: flex; align-items: center; gap: .4rem; max-width: 12rem; }
    .au-pay-in b { font-size: .9rem; color: var(--color-gray-500); }
    .au-pay-ask { font-size: .72rem; font-weight: 700; color: #92400e; }
    .au-crew { display: grid; gap: .6rem; }
    .au-cr { padding: .7rem .8rem; border-radius: .95rem; background: #f6faf1; border: 1px solid #e0ebd3; animation: auFade .35s var(--au-ease) both; animation-delay: calc(var(--k) * 60ms); }
    .au-cr.is-short { background: #fff8e8; border-color: #f3d98f; }
    .au-cr-h { display: flex; flex-wrap: wrap; align-items: baseline; gap: .1rem .6rem; }
    .au-cr-h b { font-size: .86rem; font-weight: 800; color: var(--color-gray-900); }
    .au-cr-h small { flex: 1; min-width: 10rem; font-size: .72rem; color: var(--color-gray-500); }
    .au-cr-h span { font-size: .72rem; font-weight: 900; color: #3d6823; }
    .au-cr.is-short .au-cr-h span { color: #b45309; }
    .au-cr-bar { position: relative; height: 8px; margin: .45rem 0 .4rem; border-radius: 999px; background: #e3ecd8; overflow: visible; }
    .au-cr-bar i { display: block; height: 100%; width: var(--w); border-radius: inherit; background: #4a7c2a; transform-origin: left; animation: auCrew .8s var(--au-ease) both; }
    .au-cr.is-short .au-cr-bar i { background: linear-gradient(90deg, #4a7c2a, #f0b04a 70%, #ef7676); }
    .au-cr-bar em { position: absolute; top: -3px; bottom: -3px; width: 2px; background: #1f2a17; }
    @keyframes auCrew { from { transform: scaleX(0); } }
    .au-cr p { margin: 0; font-size: .76rem; line-height: 1.5; color: var(--color-gray-600); }
    html.dark .au-cr { background: #151c11; border-color: #2b3a1c; } html.dark .au-cr.is-short { background: #2a2210; border-color: #5b4716; }
    html.dark .au-cr-h b { color: #eef3e8; } html.dark .au-cr p { color: #b9c6ad; } html.dark .au-cr-bar { background: #26301e; } html.dark .au-cr-bar em { background: #f5c518; }
    @media (prefers-reduced-motion: reduce) { .au-cr, .au-cr-bar i { animation: none !important; } }
    /* Anee's picks of variety (2026-10-28). */
    .au-vpk:empty { display: none; }
    .au-vpk { margin-top: .85rem; }
    .au-vpk-h { display: flex; gap: .6rem; align-items: flex-start; }
    .au-vpk-h > span { flex: none; font-size: 1.25rem; line-height: 1.2; }
    .au-vpk-h > div { flex: 1; min-width: 0; }
    .au-vpk-h b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); }
    .au-vpk-h small { display: block; margin-top: .1rem; font-size: .74rem; line-height: 1.45; color: var(--color-gray-500); }
    .au-vpk-line { display: flex; align-items: center; gap: .5rem; width: 100%; padding: .55rem .75rem; border-radius: .9rem; text-align: left; font-size: .8rem; color: var(--color-gray-600); background: #f6faf1; border: 1px solid #e0ebd3; }
    .au-vpk-line > span:nth-child(2) { flex: 1; min-width: 0; }
    .au-vpk-line svg { flex: none; width: 1rem; height: 1rem; }
    .au-vpks { display: grid; gap: .5rem; margin-top: .7rem; }
    @media (min-width: 720px) { .au-vpks { grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr)); } }
    .au-vp { display: grid; gap: .4rem; align-content: start; padding: .75rem .8rem; border-radius: 1rem; background: #fff; border: 1px solid #e3ecd8; animation: auFade .35s var(--au-ease) both; animation-delay: calc(var(--k) * 60ms);
        transition: border-color .28s var(--au-ease), box-shadow .28s var(--au-ease); }
    .au-vp.is-top { border-color: #9cc97a; box-shadow: 0 10px 22px -18px rgb(45 80 22 / .7); }
    .au-vp.is-on { border-color: #4a7c2a; background: #f1f7ea; }
    .au-vp-h { display: flex; align-items: center; gap: .55rem; }
    .au-vp-h > div { flex: 1; min-width: 0; }
    .au-vp-h b { display: block; font-size: .88rem; font-weight: 800; color: var(--color-gray-900); }
    .au-vp-h small { display: block; font-size: .7rem; color: var(--color-gray-500); }
    .au-vp-n { flex: none; width: 1.55rem; height: 1.55rem; border-radius: 999px; display: grid; place-items: center; font-size: .75rem; font-weight: 900; color: #3d5226; background: #eef4e6; }
    .au-vp.is-top .au-vp-n { color: #fff; background: #4a7c2a; }
    .au-vp-s { flex: none; padding: .12rem .45rem; border-radius: 999px; font-size: .7rem; font-weight: 900; color: #3d5226; background: #e7f2da; }
    .au-vp-c { display: flex; flex-wrap: wrap; gap: .3rem; }
    .au-vp-c span { padding: .12rem .5rem; border-radius: 999px; font-size: .68rem; font-weight: 700; color: #3d5226; background: #eef4e6; }
    .au-vp-p, .au-vp-w { margin: 0; font-size: .74rem; line-height: 1.45; color: var(--color-gray-700); }
    .au-vp-w { color: #92400e; }
    .au-vp-y { margin: 0; padding: 0; list-style: none; font-size: .72rem; line-height: 1.45; color: var(--color-gray-500); }
    .au-vp-y li { padding: .2rem 0; }
    .au-vp-a { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: .1rem; }
    .au-vpk-more { margin: .5rem 0 0; font-size: .76rem; }
    html.dark .au-vpk-h b, html.dark .au-vp-h b { color: #eef3e8; } html.dark .au-vpk-h small { color: #9aa88d; }
    html.dark .au-vpk-line { background: #151c11; border-color: #2b3a1c; color: #b9c6ad; }
    html.dark .au-vp { background: #10150c; border-color: #2b3a1c; } html.dark .au-vp.is-on { background: #1c2914; border-color: #6b9f3d; }
    html.dark .au-vp-c span, html.dark .au-vp-s, html.dark .au-vp-n { background: #1c2616; color: #cfe6b8; } html.dark .au-vp-p { color: #c9d6bc; } html.dark .au-vp-w { color: #fbbf24; }
    @media (prefers-reduced-motion: reduce) { .au-vp { animation: none !important; transition: none !important; } }

    /* When to plant: the year's weeks as bars, the three best, the day picked (2026-10-28). */
    .au-win { margin-top: .85rem; padding: .85rem; border-radius: 1rem; background: linear-gradient(160deg, #fbfdf8, #f2f8ea); border: 1px solid #e0ebd3; }
    .au-win:empty { display: none; }
    .au-win-h { display: flex; gap: .6rem; align-items: flex-start; }
    .au-win-h > span { flex: none; font-size: 1.3rem; line-height: 1.2; }
    .au-win-h b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); }
    .au-win-h small { display: block; margin-top: .1rem; font-size: .74rem; line-height: 1.45; color: var(--color-gray-500); }
    .au-win-spin { animation: auWinBob 1.2s ease-in-out infinite; }
    @keyframes auWinBob { 50% { transform: translateY(-3px) rotate(-6deg); } }
    .au-wsw { position: relative; display: grid; grid-template-columns: 1fr 1fr; margin: .7rem 0 .2rem; padding: .2rem; border-radius: 999px; background: #e8f1dc; }
    .au-wsw button { position: relative; z-index: 1; padding: .38rem .4rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: #4d6338; transition: color .28s var(--au-ease); }
    .au-wsw button[aria-selected="true"] { color: #fff; }
    .au-wsw > i { position: absolute; top: .2rem; bottom: .2rem; left: .2rem; width: calc(50% - .2rem); border-radius: 999px; background: #4a7c2a; transition: transform .32s var(--au-ease), background-color .32s var(--au-ease); }
    .au-wsw > i.is-price { transform: translateX(100%); background: #b7791f; }
    .au-wchart { display: flex; align-items: flex-end; gap: 2px; height: 5.2rem; margin-top: .8rem; padding-top: .3rem; }
    .au-wchart i { flex: 1 1 0; min-width: 0; height: calc(var(--h) * 100%); min-height: 3px; border-radius: 3px 3px 0 0; background: var(--color-gray-200); cursor: pointer;
        transform-origin: bottom; animation: auWinBar .5s var(--au-ease) both; animation-delay: calc(var(--k) * 8ms); transition: height .45s var(--au-ease), background-color .3s var(--au-ease), opacity .3s var(--au-ease); }
    @keyframes auWinBar { from { transform: scaleY(0); } to { transform: none; } }
    .au-wchart i.is-good { background: #4a7c2a; } .au-wchart i.is-ok { background: #8fc96a; } .au-wchart i.is-poor { background: #f0b04a; } .au-wchart i.is-bad { background: #ef7676; }
    .au-wchart.is-price i.is-good { background: #b7791f; } .au-wchart.is-price i.is-ok { background: #e0b04f; }
    .au-wchart i.is-prep { opacity: .3; }
    .au-wchart i.is-best { box-shadow: inset 0 3px 0 #f5c518; }
    .au-wchart i.is-on { outline: 2px solid #1f3a10; outline-offset: 1px; }
    .au-wchart i:hover { opacity: .8; }
    .au-wchart.is-wait i { height: 30%; background: #e3ecd8; animation: auWinWait 1.4s ease-in-out infinite; animation-delay: calc(var(--k, 0) * 20ms); }
    @keyframes auWinWait { 50% { opacity: .4; } }
    .au-wmonths { position: relative; height: 1rem; margin-top: .2rem; }
    .au-wmonths span { position: absolute; top: 0; font-size: .58rem; font-weight: 800; color: var(--color-gray-500); white-space: nowrap; }
    .au-wkey { display: flex; align-items: center; gap: .4rem; margin: .45rem 0 0; font-size: .7rem; line-height: 1.4; color: var(--color-gray-500); }
    .au-wkey i { flex: none; width: .7rem; height: .7rem; border-radius: 2px; background: #4a7c2a; opacity: .3; }
    .au-wbest { display: grid; gap: .45rem; margin-top: .75rem; }
    @media (min-width: 720px) { .au-wbest { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .au-wb { position: relative; display: flex; gap: .55rem; align-items: flex-start; padding: .65rem .7rem; border-radius: .9rem; background: #fff; border: 1px solid #e3ecd8; animation: auFade .35s var(--au-ease) both; animation-delay: calc(var(--k) * 70ms); }
    .au-wb.is-top { border-color: #9cc97a; box-shadow: 0 10px 22px -18px rgb(45 80 22 / .7); }
    .au-wb-n { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center; font-size: .78rem; font-weight: 900; color: #3d5226; background: #eef4e6; }
    .au-wb.is-top .au-wb-n { color: #fff; background: #4a7c2a; }
    .au-wb > div { flex: 1; min-width: 0; display: grid; gap: .1rem; }
    .au-wb b { font-size: .86rem; font-weight: 800; color: var(--color-gray-900); }
    .au-wb small { font-size: .72rem; line-height: 1.4; color: var(--color-gray-600); }
    .au-wb small.au-wb-why { color: var(--color-gray-500); }
    .au-wb .au-link { position: absolute; right: .6rem; top: .55rem; font-size: .72rem; }
    @media (min-width: 720px) { .au-wb { flex-direction: column; } .au-wb .au-link { position: static; justify-self: start; margin-top: .2rem; } }
    .au-wpick-h { margin-top: .7rem; }
    .au-wsq { display: flex; align-items: center; gap: .45rem; margin-top: .45rem; font-weight: 800; cursor: pointer; }
    .au-wsq input { width: 1.05rem; height: 1.05rem; accent-color: #b45309; }
    html.dark .au-win { background: linear-gradient(160deg, #151c11, #18220f); border-color: #2b3a1c; }
    html.dark .au-win-h b, html.dark .au-wb b { color: #eef3e8; } html.dark .au-win-h small { color: #9aa88d; }
    html.dark .au-wsw { background: #1c2616; } html.dark .au-wsw button { color: #a5c97e; } html.dark .au-wsw button[aria-selected="true"] { color: #fff; }
    html.dark .au-wchart i { background: #2b3a1c; } html.dark .au-wchart i.is-on { outline-color: #f5c518; }
    html.dark .au-wb { background: #10150c; border-color: #2b3a1c; } html.dark .au-wb small { color: #b9c6ad; } html.dark .au-wb-n { background: #1c2616; color: #cfe6b8; }
    @media (prefers-reduced-motion: reduce) { .au-wchart i, .au-wb, .au-win-spin, .au-wchart.is-wait i { animation: none !important; } .au-wchart i, .au-wsw > i, .au-wsw button { transition: none !important; } }

    /* How Anee runs the season: switches, the machines, the protection (2026-10-28). */
    .au-sws { display: grid; }
    .au-sw { display: flex; align-items: center; gap: .75rem; width: 100%; padding: .75rem .1rem; text-align: left; border-radius: .8rem; transition: background-color .28s var(--au-ease); }
    .au-sw + .au-sw { border-top: 1px solid var(--color-gray-100); }
    .au-sw:hover { background: var(--color-gray-50); }
    .au-sw-i { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .8rem; display: grid; place-items: center; font-size: 1.2rem; background: #f1f7ea; transition: transform .28s var(--au-ease), filter .28s var(--au-ease); }
    .au-sw[aria-checked="false"] .au-sw-i { filter: grayscale(.9); opacity: .7; }
    .au-sw:active .au-sw-i { transform: scale(.92); }
    .au-sw-t { flex: 1; min-width: 0; }
    .au-sw-t b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); }
    .au-sw-t small { display: block; margin-top: .1rem; font-size: .76rem; line-height: 1.45; color: var(--color-gray-500); }
    .au-sw-k { flex: none; position: relative; width: 2.6rem; height: 1.5rem; border-radius: 999px; background: var(--color-gray-300); transition: background-color .28s var(--au-ease); }
    .au-sw-k i { position: absolute; top: .15rem; left: .15rem; width: 1.2rem; height: 1.2rem; border-radius: 999px; background: #fff; box-shadow: 0 2px 6px rgb(0 0 0 / .2); transition: transform .28s var(--au-ease); }
    .au-sw[aria-checked="true"] .au-sw-k { background: var(--color-brand-600); }
    .au-sw[aria-checked="true"] .au-sw-k i { transform: translateX(1.1rem); }
    .au-fleet { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .32s var(--au-ease); }
    .au-fleet.is-on { grid-template-rows: 1fr; }
    .au-fleet > div { min-height: 0; overflow: hidden; }
    .au-fleet-in { margin: .2rem 0 .6rem; padding: .8rem; border-radius: 1rem; background: #f6faf1; border: 1px solid #e0ebd3; }
    .au-mach { display: flex; align-items: center; gap: .6rem; padding: .5rem 0; }
    .au-mach + .au-mach { border-top: 1px solid #e3ecd8; }
    .au-mach img { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .7rem; object-fit: cover; background: #fff; }
    .au-mach > span:nth-child(2) { flex: 1; min-width: 0; font-size: .82rem; font-weight: 800; color: var(--color-gray-800); line-height: 1.3; }
    .au-mach > span small { display: block; font-size: .7rem; font-weight: 600; color: var(--color-gray-500); }
    .au-seg { flex: none; display: inline-flex; padding: .15rem; border-radius: 999px; background: #fff; border: 1px solid var(--color-gray-200); }
    .au-seg button { padding: .3rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: var(--color-gray-500); transition: background-color .28s var(--au-ease), color .28s var(--au-ease); }
    .au-seg button[aria-pressed="true"] { background: var(--color-brand-600); color: #fff; }
    .au-prot { display: grid; gap: .5rem; }
    @media (min-width: 720px) { .au-prot { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .au-pc { position: relative; display: grid; gap: .25rem; align-content: start; padding: .8rem .85rem; border-radius: 1rem; text-align: left; border: 1.5px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .28s var(--au-ease), background-color .28s var(--au-ease), transform .28s var(--au-ease), box-shadow .28s var(--au-ease); cursor: pointer; }
    .au-pc:hover { transform: translateY(-2px); }
    .au-pc[aria-checked="true"] { border-color: var(--color-brand-600); background: #f1f7ea; box-shadow: 0 10px 22px -16px rgb(45 80 22 / .6); }
    .au-pc > span { font-size: 1.35rem; }
    .au-pc b { font-size: .88rem; font-weight: 800; color: var(--color-gray-900); }
    .au-pc small { font-size: .74rem; line-height: 1.45; color: var(--color-gray-500); }
    .au-pc em { position: absolute; top: .55rem; right: .6rem; padding: .08rem .5rem; border-radius: 999px; font-style: normal; font-size: .62rem; font-weight: 900; color: #3b2f00; background: #f5c518; }
    html.dark .au-sw + .au-sw { border-top-color: #232e1b; } html.dark .au-sw:hover { background: #1a2114; } html.dark .au-sw-i { background: #1c2616; }
    html.dark .au-sw-t b, html.dark .au-pc b, html.dark .au-mach > span:nth-child(2) { color: #eef3e8; } html.dark .au-sw-t small, html.dark .au-pc small { color: #9aa88d; }
    html.dark .au-sw-k { background: #3a4630; } html.dark .au-fleet-in { background: #141a10; border-color: #2b3a1c; } html.dark .au-mach + .au-mach { border-top-color: #232e1b; }
    html.dark .au-seg { background: #10150c; border-color: #2b3a1c; } html.dark .au-pc { background: #161c12; border-color: #2b3a1c; } html.dark .au-pc[aria-checked="true"] { background: #1c2914; border-color: #6b9f3d; }
    @media (prefers-reduced-motion: reduce) { .au-sw, .au-sw-i, .au-sw-k, .au-sw-k i, .au-fleet, .au-seg button, .au-pc { transition: none; } }
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
    /* One line each, on any phone (2026-10-28, the owner: "fix back to workers button and anee reads it button it looks
       bad in mobile"): an arrow and the step's name, "Back to" and "Next:" only where there is room. */
    .au-nav .btn { min-width: 0; min-height: 2.85rem; display: inline-flex; align-items: center; justify-content: center; gap: .35rem; white-space: nowrap; }
    .au-nav .btn > span { min-width: 0; overflow: hidden; text-overflow: ellipsis; }
    .au-nav .btn svg { flex: none; width: 1.05rem; height: 1.05rem; }
    .au-nav #auBack { flex: 0 1 auto; }
    .au-nav #auNext { flex: 0 1 auto; margin-left: auto; transition: background-color .28s var(--au-ease), box-shadow .28s var(--au-ease); }
    .au-nav #auNext.is-read { background: #f5c518; border-color: #f5c518; color: #1f2a17; }
    .au-nav #auNext.is-read:hover { background: #e9b90c; border-color: #e9b90c; }
    @media (max-width: 639.98px) {
        .au-nav-w { display: none; }
        .au-nav .btn { padding-left: .95rem; padding-right: .95rem; }
    }
    .au-nav [hidden] { display: none !important; }
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
    .au-x { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; color: var(--color-gray-400); display: grid; place-items: center; }
    .au-x:hover { background: #fde8e8; color: #b91c1c; }
    /* The farm's workers come from one button and a list (2026-10-28, the owner:
       "have a taglike button that when clicked shows a modal list of the workers to add"). */
    .au-gwopen { margin-bottom: .8rem; color: var(--color-brand-700); background: #f8fbf4; border: 1.5px dashed #b7d597; }
    .au-gwopen[hidden] { display: none; }
    .au-gwopen em { font-style: normal; min-width: 1.35rem; padding: .05rem .4rem; border-radius: 999px; font-size: .72rem; text-align: center; color: #fff; background: var(--color-brand-600); }
    .au-gw { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white); margin-bottom: .5rem; transition: border-color .28s var(--au-ease), background-color .28s var(--au-ease); }
    .au-gw.is-open { border-color: var(--color-brand-500, #6b9f3d); background: #fbfdf8; }
    .au-gw-h { display: flex; align-items: center; gap: .65rem; width: 100%; padding: .65rem .75rem; text-align: left; }
    .au-gw-h:disabled { cursor: default; }
    .au-gw-i { flex: none; width: 2.1rem; height: 2.1rem; border-radius: 999px; display: grid; place-items: center; font-weight: 800; color: #2d5016; background: #e4efd4; }
    .au-gw-t { flex: 1; min-width: 0; }
    .au-gw-t b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .au-gw-t small { display: block; margin-top: .1rem; font-size: .74rem; line-height: 1.4; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .au-gw-s { flex: none; padding: .25rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: var(--color-brand-700); background: #f3f8ec; border: 1px solid #cfe3b8;
        transition: background-color .28s var(--au-ease), border-color .28s var(--au-ease), color .28s var(--au-ease); }
    .au-gw.is-in .au-gw-s { color: #fff; background: var(--color-brand-600); border-color: var(--color-brand-600); }
    .au-gw-f { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .28s var(--au-ease); }
    .au-gw.is-open .au-gw-f { grid-template-rows: 1fr; }
    .au-gw-f > div { overflow: hidden; }
    .au-gw-in { display: grid; gap: .55rem; padding: .1rem .75rem .8rem; }
    .au-gw-l { display: grid; gap: .25rem; font-size: .74rem; font-weight: 800; color: var(--color-gray-600); }
    .au-gw-l small { font-weight: 600; color: var(--color-gray-400); }
    .au-gw-err { font-size: .78rem; font-weight: 700; color: #b91c1c; }
    .au-gw-err[hidden] { display: none; }
    .au-gw-acts { display: flex; gap: .5rem; justify-content: flex-end; }
    html.dark .au-gwopen { background: #1a2114; border-color: #3f5a2a; color: #c5e09f; }
    html.dark .au-gw { background: #161c12; border-color: #2b3a1c; }
    html.dark .au-gw.is-open { background: #1a2216; border-color: #4a7c2a; }
    html.dark .au-gw-t b { color: #eef3e8; }
    html.dark .au-gw-i, html.dark .au-gw-s { background: #1c2616; border-color: #2b3a1c; color: #c5e09f; }
    html.dark .au-gw.is-in .au-gw-s { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    html.dark .au-gw-l { color: #b9c5ad; }
    html.dark .au-gw-err { color: #fca5a5; }

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
    /* How much a lot plants (on its card), then its three goals with their fertilizer (on the Reading step), AutoGoal 2026-10-28. */
    .au-goal { margin-top: 1rem; padding-top: .3rem; border-top: 1px dashed var(--color-gray-200); }
    .au-row.is-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; }
    .au-gl { display: grid; gap: .25rem; font-size: .74rem; font-weight: 800; color: var(--color-gray-600); }
    .au-gl small { font-weight: 600; color: var(--color-gray-400); }
    .au-gl > i { font-style: normal; }
    .au-gl > span { position: relative; display: block; }
    .au-gl input { width: 100%; padding-right: 2.3rem; }
    .au-gl em { position: absolute; right: .7rem; top: 50%; transform: translateY(-50%); font-style: normal; font-size: .76rem; font-weight: 700; color: var(--color-gray-400); pointer-events: none; }
    .au-stand { display: flex; gap: .5rem; margin-top: .7rem; padding: .55rem .7rem; border-radius: .85rem; font-size: .78rem; line-height: 1.45; color: #2d4a17; background: #f3f8ec; border: 1px solid #dcebc8; animation: auFade .3s var(--au-ease) both; }
    .au-rec { display: flex; align-items: flex-start; gap: .5rem; margin: .1rem 0 .6rem; padding: .55rem .7rem; border-radius: .85rem; background: #eaf4fb; border: 1px solid #c6e0f2; font-size: .76rem; line-height: 1.45; color: #305a77; animation: auFade .3s var(--au-ease) both; }
    .au-rec > span:nth-child(2) { flex: 1; min-width: 0; display: grid; gap: .1rem; }
    .au-rec b { color: #1e4b6b; font-weight: 800; }
    .au-rec .au-link { flex: none; color: #1e4b6b; }
    html.dark .au-rec { background: #10202b; border-color: #1f3a4d; color: #9fc3dd; } html.dark .au-rec b, html.dark .au-rec .au-link { color: #d4e7f5; }
    .au-stand.is-warn { color: #6b4a00; background: #fff6dd; border-color: #f3d98f; transition: background-color .28s var(--au-ease), color .28s var(--au-ease); }
    html.dark .au-stand.is-warn { background: #2a2210; border-color: #5b4716; color: #f3d9a4; }
    .au-goals { margin: .75rem 0 .2rem; display: grid; gap: .5rem; }
    .au-goals-h b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); }
    .au-goals-h small { display: block; font-size: .76rem; line-height: 1.45; color: var(--color-gray-500); }
    .au-goals-wait { font-size: .82rem; color: var(--color-gray-500); }
    .au-goals-g { display: grid; gap: .5rem; }
    @media (min-width: 768px) { .au-goals-g { grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: start; } }
    /* On a phone a lot's pane sits flat in its step's card: no box in a box eating the width. */
    @media (max-width: 639.98px) { #auFPane > .au-rl { border: 0; border-top: 1px dashed var(--color-gray-200); border-radius: 0; padding: .9rem 0 0; } }
    .au-gc { display: grid; gap: .4rem; width: 100%; padding: .7rem .8rem; border-radius: 1rem; text-align: left; cursor: pointer; border: 1.5px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .28s var(--au-ease), background-color .28s var(--au-ease), box-shadow .28s var(--au-ease), transform .2s var(--au-ease); animation: auFade .3s var(--au-ease) both; }
    .au-gc:active { transform: scale(.99); }
    .au-gc:hover { border-color: #b7d597; }
    .au-gc:focus-visible { outline: 3px solid rgb(107 159 61 / .45); outline-offset: 2px; }
    .au-gc-top { display: flex; align-items: center; gap: .5rem; }
    .au-gc-top > span { flex: none; font-size: 1.05rem; }
    .au-gc-top b { flex: 1; min-width: 0; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); }
    .au-gc-top em { flex: none; font-style: normal; font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: #2d5016; }
    .au-gc.is-traditional .au-gc-top em { color: var(--color-gray-500); }
    .au-gc.is-risky .au-gc-top em { color: #8a5a00; } .au-gc.is-high .au-gc-top em { color: #6b4e00; }
    .au-gc-top i { flex: none; width: 1.25rem; height: 1.25rem; border-radius: 999px; border: 2px solid var(--color-gray-300); display: grid; place-items: center; transition: background-color .28s var(--au-ease), border-color .28s var(--au-ease); }
    .au-gc-top i::after { content: '✓'; font-style: normal; font-size: .7rem; font-weight: 900; color: #fff; opacity: 0; transition: opacity .2s; }
    .au-gc-lot { margin-top: -.3rem; text-align: right; font-size: .72rem; font-weight: 700; color: var(--color-gray-500); }
    .au-gc-say { font-size: .78rem; line-height: 1.45; color: var(--color-gray-600); }
    .au-gc-t { width: 100%; border-collapse: collapse; font-size: .76rem; }
    .au-gc-t th { padding: .3rem .5rem; font-size: .64rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--color-gray-400); text-align: right; background: var(--color-gray-50); }
    .au-gc-t th:first-child { text-align: left; border-radius: .6rem 0 0 .6rem; } .au-gc-t th:last-child { border-radius: 0 .6rem .6rem 0; }
    .au-gc-t td { padding: .3rem .5rem; border-bottom: 1px solid var(--color-gray-100); text-align: right; font-weight: 800; color: var(--color-gray-900); white-space: nowrap; }
    .au-gc-t td:first-child { text-align: left; font-weight: 600; color: var(--color-gray-700); white-space: normal; }
    .au-gc-t tr:last-child td { border-bottom: 0; }
    .au-gc-t tr.is-micro td:first-child { color: #2d5016; }
    .au-gc-t td small { display: inline-block; margin-left: .25rem; padding: 0 .35rem; border-radius: 999px; font-size: .62rem; font-weight: 800; color: #2d5016; background: #e4efd4; }
    .au-gc-unfed { padding: .4rem .55rem; border-radius: .7rem; font-size: .74rem; line-height: 1.45; color: #6b4a00; background: #fff6dd; border: 1px solid #f3d98f; }
    .au-gc-bags { display: grid; gap: .1rem; padding: .5rem .6rem; border-radius: .8rem; background: #f8fbf4; border: 1px solid #dcebc8; }
    .au-gc-bags > b { font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #3d6823; margin-bottom: .15rem; }
    .au-gc-bags > div { display: flex; align-items: baseline; gap: .6rem; padding: .28rem 0; font-size: .78rem; }
    .au-gc-bags > div + div { border-top: 1px solid #e4efd4; }
    .au-gc-bags em { flex: none; min-width: 5.2rem; font-style: normal; font-weight: 800; color: var(--color-gray-900); }
    .au-gc-bags span { flex: 1; min-width: 0; color: var(--color-gray-700); }
    .au-gc-bags span small { display: inline-block; margin-left: .2rem; padding: 0 .35rem; border-radius: 999px; font-size: .64rem; font-weight: 800; color: #2d5016; background: #e4efd4; }
    .au-gc-bags span i { display: block; font-style: normal; font-size: .7rem; color: var(--color-gray-500); }
    .au-gc-bags > p { margin-top: .2rem; font-size: .7rem; line-height: 1.4; color: var(--color-gray-500); }
    html.dark .au-gc-bags { background: #17220f; border-color: #2b3a1c; } html.dark .au-gc-bags > b { color: #a5c97e; } html.dark .au-gc-bags em { color: #eef3e8; }
    html.dark .au-gc-bags span { color: #c7d0bd; } html.dark .au-gc-bags > div + div { border-color: #2b3a1c; } html.dark .au-gc-bags span small { background: #1c2616; color: #c5e09f; }
    .au-gc-as { font-size: .72rem; line-height: 1.45; color: var(--color-gray-500); }
    .au-gc-add { display: flex; gap: .45rem; padding: .4rem .55rem; border-radius: .7rem; font-size: .74rem; line-height: 1.45; color: #2d4a17; background: #f3f8ec; border: 1px solid #dcebc8; }
    .au-gc-add b { color: #1d330d; }
    .au-goals-disc { display: flex; gap: .5rem; margin-top: .2rem; padding: .6rem .7rem; border-radius: .9rem; font-size: .74rem; line-height: 1.5; color: #6b4a00; background: #fff6dd; border: 1px solid #f3d98f; }
    .au-goals-disc b { color: #4a3300; }
    @media (min-width: 768px) { .au-goals-disc { grid-column: 1 / -1; } }
    /* How much the lot plants, on the Reading step: one row that opens the lot card's own inputs. */
    .au-plant { margin: .7rem 0 .2rem; border-radius: 1rem; border: 1px solid #dcebc8; background: #f8fbf4; overflow: hidden; }
    .au-plant-h { display: flex; align-items: center; gap: .6rem; width: 100%; padding: .6rem .75rem; text-align: left; }
    .au-plant-h > span:first-child { flex: none; font-size: 1.05rem; }
    .au-plant-h > span:nth-child(2) { flex: 1; min-width: 0; display: grid; }
    .au-plant-h b { font-size: .86rem; font-weight: 800; color: var(--color-gray-900); }
    .au-plant-h small { font-size: .74rem; line-height: 1.4; color: var(--color-gray-600); }
    .au-plant-h em { flex: none; font-style: normal; font-size: .78rem; font-weight: 800; color: var(--color-brand-700, #3d6823); text-decoration: underline; text-underline-offset: 3px; }
    .au-plant.is-open .au-acc-b { grid-template-rows: 1fr; }
    .au-goal.is-inline { margin: 0; padding: 0 .75rem .8rem; border-top: 0; }
    .au-goal.is-inline > .au-lab:first-child { display: none; }
    html.dark .au-gc-add { background: #17220f; border-color: #2b3a1c; color: #c5e09f; } html.dark .au-gc-add b { color: #e3f0d2; }
    html.dark .au-goals-disc { background: #2a2210; border-color: #5b4716; color: #f3d9a4; } html.dark .au-goals-disc b { color: #fbe7bd; }
    html.dark .au-plant { background: #17220f; border-color: #2b3a1c; } html.dark .au-plant-h b { color: #eef3e8; } html.dark .au-plant-h small { color: #b9c5ad; } html.dark .au-plant-h em { color: #a5c97e; }
    html.dark .au-gc-t td small { background: #1c2616; color: #c5e09f; }
    .au-gc[aria-checked="true"] { border-color: var(--color-brand-600, #4a7c2a); background: #f6faf0; box-shadow: 0 8px 20px -14px rgb(61 104 35 / .7); }
    .au-gc[aria-checked="true"] .au-gc-top i { background: var(--color-brand-600, #4a7c2a); border-color: var(--color-brand-600, #4a7c2a); }
    .au-gc[aria-checked="true"] .au-gc-top i::after { opacity: 1; }
    .au-steps { display: grid; gap: .1rem; margin-top: .45rem; }
    .au-steps > div { display: flex; align-items: baseline; gap: .5rem; padding: .3rem 0; border-top: 1px solid rgb(0 0 0 / .06); font-size: .78rem; line-height: 1.4; color: #4a3b0a; }
    .au-steps > div > span:first-child { flex: none; width: 1.2rem; text-align: center; }
    .au-steps > div > span:nth-child(2) { flex: 1; min-width: 0; }
    .au-steps > div > b { flex: none; font-weight: 800; color: #3d2e00; white-space: nowrap; }
    .au-acc-in .au-steps { margin: 0 0 .5rem; padding: .35rem .7rem; border-radius: .8rem; background: #fdf8e8; border: 1px solid #f0e2a8; }
    .au-acc-in .au-steps > div:first-child { border-top: 0; }
    html.dark .au-goal { border-color: #2b3a1c; }
    html.dark .au-gl { color: #b9c5ad; }
    html.dark .au-stand { background: #17220f; border-color: #2b3a1c; color: #c5e09f; }
    html.dark .au-goals-h b, html.dark .au-gc-top b, html.dark .au-gc-t td { color: #eef3e8; }
    html.dark .au-gc { background: #161c12; border-color: #2b3a1c; } html.dark .au-gc[aria-checked="true"] { background: #1c2616; border-color: #6b9f3d; }
    html.dark .au-gc-say, html.dark .au-gc-t td:first-child { color: #b9c5ad; } html.dark .au-gc-t th { background: #1a2114; } html.dark .au-gc-t td { border-color: #2b3a1c; }
    html.dark .au-gc-t tr.is-micro td:first-child { color: #c5e09f; } html.dark .au-gc-unfed { background: #2a2210; border-color: #5b4716; color: #f3d9a4; }
    html.dark .au-gc-top em { color: #c5e09f; } html.dark .au-gc.is-risky .au-gc-top em, html.dark .au-gc.is-high .au-gc-top em { color: #f0dfa0; }
    html.dark .au-acc-in .au-steps { background: #2a2410; border-color: #5b4d16; }
    html.dark .au-steps > div > b { color: #fff3c4; }
    html.dark .au-steps > div { color: #eadcae; border-color: rgb(255 255 255 / .06); }

    /* The reading, one row per item, each folded until tapped (2026-10-28, the owner: "make it 1 row per item and make
       it expandable (contracted by default) and make it look good"). */
    .au-rl > p.au-rl-lead { margin-top: .35rem; color: var(--color-gray-600); }
    .au-rows { margin-top: .7rem; border-top: 1px solid var(--color-gray-100); }
    .au-rows > :last-child { border-bottom: 0; }
    .au-kv { display: flex; align-items: center; gap: .7rem; padding: .62rem .1rem; border-bottom: 1px solid var(--color-gray-100); font-size: .86rem; }
    .au-kv > span:first-child { flex: none; width: 1.9rem; height: 1.9rem; border-radius: .65rem; display: grid; place-items: center; background: #f3f8ec; font-size: .95rem; }
    .au-kv > span:nth-child(2) { flex: 1; min-width: 0; color: var(--color-gray-600); font-weight: 600; }
    .au-kv > b { flex: none; font-weight: 800; color: var(--color-gray-900); }
    .au-kv > b.is-none { font-weight: 600; color: var(--color-gray-400); }
    .au-acc { border-bottom: 1px solid var(--color-gray-100); }
    .au-acc-h { display: flex; align-items: center; gap: .7rem; width: 100%; padding: .62rem .1rem; text-align: left; border-radius: .7rem; transition: background-color .28s var(--au-ease); }
    .au-acc-h:hover { background: #f8fbf4; }
    .au-acc-i { flex: none; width: 1.9rem; height: 1.9rem; border-radius: .65rem; display: grid; place-items: center; background: #f3f8ec; font-size: .95rem; }
    .au-acc-t { flex: 1; min-width: 0; display: grid; }
    .au-acc-t b { font-size: .88rem; font-weight: 800; color: var(--color-gray-900); }
    .au-acc-t small { font-size: .76rem; line-height: 1.4; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        transition: opacity .28s var(--au-ease); }
    .au-acc-n { flex: none; min-width: 1.4rem; padding: .05rem .45rem; border-radius: 999px; font-size: .7rem; font-weight: 800; text-align: center; color: #2d5016; background: #e4efd4; }
    .au-acc-n.is-warn { color: #8a5a00; background: #fff4d6; }
    .au-acc-h > svg { flex: none; width: 1rem; height: 1rem; color: var(--color-gray-400); transition: transform .28s var(--au-ease); }
    .au-acc.is-open .au-acc-h > svg { transform: rotate(180deg); }
    .au-acc.is-open .au-acc-t small { opacity: 0; }
    .au-acc-b { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .28s var(--au-ease); }
    .au-acc.is-open .au-acc-b { grid-template-rows: 1fr; }
    .au-acc-b > div { overflow: hidden; }
    .au-acc-in { padding: 0 .1rem .85rem 2.6rem; }
    .au-acc-in p + p, .au-acc-in p + ul, .au-acc-in ul + p, .au-acc-in h6 { margin-top: .45rem; }
    .au-acc-in h6 { font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .au-acc-in ul { margin: .15rem 0 0 1rem; list-style: disc; }
    .au-acc-in li + li { margin-top: .2rem; }
    /* A thin line between the items of every list in the wizard (2026-10-28, the owner: "add a thin line between the items
       for easier readability. do this for all lists in the wizard"). */
    .au ul > li + li { margin-top: .4rem; padding-top: .4rem; border-top: 1px solid var(--color-gray-200); }
    html.dark .au ul > li + li { border-top-color: #2b3a1c; }
    .au-acc-in ul.is-plain { margin-left: 0; list-style: none; }
    .au-risk-i { padding: .5rem .65rem; border-radius: .8rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .au-risk-i + .au-risk-i { margin-top: .4rem; }
    .au-risk-i b { display: block; font-size: .84rem; color: var(--color-gray-900); }
    .au-risk-i small { display: block; margin-top: .2rem; font-size: .74rem; line-height: 1.45; color: var(--color-gray-500); }
    @media (max-width: 639.98px) { .au-acc-in { padding-left: .1rem; } }

    /* Fertilizer */
    /* Every lot's tab in sight: they wrap instead of running off the screen (2026-10-28, the owner: the fertilizer lot by lot looked bad on a phone). */
    .au-ltabs { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: .8rem; padding-bottom: .1rem; }
    .au-fgoal { display: flex; align-items: center; gap: .65rem; margin: .7rem 0 .2rem; padding: .65rem .75rem; border-radius: 1rem; background: linear-gradient(115deg, #f3f8ec, #fff); border: 1px solid #cfe3b8; }
    .au-fgoal > span:first-child { flex: none; font-size: 1.2rem; }
    .au-fgoal > span:nth-child(2) { flex: 1; min-width: 0; display: grid; }
    .au-fgoal small { font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .au-fgoal b { font-size: .9rem; font-weight: 800; color: var(--color-gray-900); }
    .au-fgoal em { font-style: normal; font-size: .74rem; line-height: 1.4; color: var(--color-gray-600); }
    .au-fgoal em b { font-size: .74rem; }
    .au-fgoal .au-link { flex: none; }
    .au-fcta { margin-top: 1rem; text-align: center; }
    .au-fcta .au-big { max-width: 100%; white-space: normal; }
    .au-fcta p { margin: .5rem auto 0; max-width: 26rem; font-size: .74rem; line-height: 1.45; color: var(--color-gray-500); }
    .au-flines { display: grid; }
    .au-flines > div { display: flex; align-items: baseline; gap: .7rem; padding: .45rem .1rem; border-bottom: 1px solid var(--color-gray-100); font-size: .84rem; }
    .au-flines > div:last-child { border-bottom: 0; }
    .au-flines b { flex: none; min-width: 4.4rem; font-weight: 800; color: var(--color-gray-900); }
    .au-flines span { flex: 1; min-width: 0; color: var(--color-gray-700); }
    .au-flines small { display: inline-block; margin-left: .2rem; padding: 0 .4rem; border-radius: 999px; font-size: .7rem; font-weight: 800; color: #2d5016; background: #eef6e4; }
    html.dark .au-fgoal { background: #17220f; border-color: #2b3a1c; } html.dark .au-fgoal b { color: #eef3e8; } html.dark .au-fgoal em { color: #b9c5ad; }
    html.dark .au-flines > div { border-color: #2b3a1c; } html.dark .au-flines b { color: #eef3e8; } html.dark .au-flines span { color: #c7d0bd; } html.dark .au-flines small { background: #1c2616; color: #c5e09f; }
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
    /* On a phone each answer sits under its name, so a long lot name does not squeeze it into a column. */
    @media (max-width: 639.98px) {
        .au-review div { flex-direction: column; gap: .15rem; }
        .au-review b { flex: none; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    }
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
    html.dark .au-rows, html.dark .au-kv, html.dark .au-acc { border-color: #2b3a1c; }
    html.dark .au-kv > span:first-child, html.dark .au-acc-i { background: #1c2616; }
    html.dark .au-kv > b, html.dark .au-acc-t b, html.dark .au-risk-i b { color: #eef3e8; }
    html.dark .au-kv > span:nth-child(2), html.dark .au-rl > p.au-rl-lead { color: #b9c5ad; }
    html.dark .au-acc-h:hover { background: #1a2216; }
    html.dark .au-acc-n { background: #1c2616; color: #c5e09f; } html.dark .au-acc-n.is-warn { background: #2a2210; color: #f3d9a4; }
    html.dark .au-risk-i { background: #1a2114; border-color: #2b3a1c; }
    html.dark .au-yield { background: #2a2410; border-color: #5b4d16; } html.dark .au-yield p { color: #f0dfa0; } html.dark .au-yield p b { color: #fff3c4; }
    html.dark .au-add { background: #1a2114; border-color: #3f5a2a; color: #c5e09f; }
    html.dark .au-bar-t { background: #26301e; } html.dark .au-bar-t i { background: #f1f5e9; }
    @media (prefers-reduced-motion: reduce) {
        .au-top::after, .au-top img, .au-reading-cta img, .au-big, .au-step.is-on, .au-lot, .au-w, .au-rl, .au-reads div, .au-note, .au-risk, .au-q, .au-from, .au-tag.is-bump { animation: none; }
        .au-frame, .au-modal, .au-modal > div, .au-bar-t span, .au-gauge .fg, .au-fold-in, .au-rail button i::after, .au-link, .au-gw, .au-gw-s, .au-gw-f, .au-acc-b, .au-acc-h, .au-acc-h > svg, .au-acc-t small, .au-gc, .au-gc-top i, .au-stand { transition: none; animation: none; }
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
        {{-- How Anee runs it (App\Support\AutoOptions, 2026-10-28): asked before the lots, so the land preparation the
             farmer can do is known when the planting day is picked. --}}
        <div class="au-card" id="auOpts"></div>
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
            <button type="button" class="au-pill au-gwopen" id="auGwOpen" hidden></button>
            <div id="auWorkers"></div>
            <button type="button" class="au-add" id="auAddWorker">+ Add a worker</button>
            <p class="au-note is-info" id="auWNote" hidden></p>
        </div>
        {{-- Is the crew enough (App\Support\AutoCrew, 2026-10-28): the planting and the harvest of every lot against who comes. --}}
        <div class="au-card" id="auCrew" hidden></div>
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

{{-- The farm's workers (Global Workers). A worker is tapped open, their
     name, email and phone can be put right, then they are added; what is put
     right is kept on the farm's list too (AsGlobalWorker::forCard). --}}
<div class="sheet hidden" id="auGwSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header"><h3 class="sheet-title">Your workers</h3><button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button></div>
    <div class="sheet-body">
        <p class="text-sm text-gray-500 mb-3">Tap a worker to add them to this season. You can fix their name, email, phone or days first, and the change is kept on your workers list too.</p>
        <input type="search" id="auGwQ" class="form-input mb-3" placeholder="Search your workers" autocomplete="off" maxlength="60">
        <div id="auGwRows"></div>
    </div>
    <div class="sheet-footer">
        <a href="{{ route('workers.global') }}" target="_blank" rel="noopener" class="btn btn-white">Manage your workers</a>
        <button type="button" class="btn btn-primary" data-sheet-close>Done</button>
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
@include('partials.work-days')

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
        analyze: @json(route('auto.analyze', $auto->id)), generate: @json(route('auto.generate', $auto->id)), job: () => @json(route('auto.job', $auto->id)), goal: @json(route('auto.goal', $auto->id)),
        fert: @json(route('auto.fert', $auto->id)), imbalance: @json(route('auto.imbalance', $auto->id)), ph: @json(asset('data/ph-locations.json')),
        window: @json(route('auto.window', $auto->id)), varieties: @json(route('auto.varieties', $auto->id)), crew: @json(route('auto.crew', $auto->id)),
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
    // Runs what waits now. Only what it runs is taken off the list: a save asked while it runs keeps its own turn
    // (the goals asked at load were lost when the list was emptied after the wait).
    const flush = async () => {
        for (const k of Object.keys(timers)) { clearTimeout(timers[k]); }
        const run = Object.entries(pending);
        run.forEach(([k]) => { delete pending[k]; });
        await Promise.all(run.map(([, f]) => f().catch(() => {})));
    };
    const pending = {};
    const later = (key, fn, ms) => { pending[key] = fn; soon(key, async () => { const f = pending[key]; delete pending[key]; if (f) await f(); }, ms); };
    window.addEventListener('beforeunload', () => { Object.values(pending).forEach((f) => f().catch(() => {})); });

    /* ---- the rail and the steps ---- */
    $('auRail').innerHTML = STEPS.map((s, i) => '<button type="button" data-go="' + i + '"><i></i><span>' + s + '</span></button>').join('');
    const reached = () => Math.max(Number(S.step) || 0, step);
    // A nav button's words: the lead ("Back to ", "Next: ") shows only where there is room; an arrow on the side it goes.
    const ARROW = { back: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/></svg>',
        next: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>' };
    const navLabel = (b, lead, word, dir) => {
        b.setAttribute('aria-label', (lead + word).replace(/^🔎 /, ''));
        b.innerHTML = (dir === 'back' ? ARROW.back : '') + '<span>' + (lead ? '<span class="au-nav-w">' + esc(lead) + '</span>' : '') + esc(word) + '</span>' + (dir === 'next' ? ARROW.next : '');
    };
    // The rail, the step count and the two buttons, over the steps still asked.
    const navSay = () => {
        const shown = STEPS.map((_, i) => i).filter((i) => !stepHidden(i));
        document.querySelectorAll('#auRail button').forEach((b, i) => { b.hidden = stepHidden(i); b.classList.toggle('is-on', i === step); b.classList.toggle('is-done', i < step); b.disabled = i > reached(); });
        $('auStepSay').textContent = 'Step ' + (Math.max(0, shown.indexOf(step)) + 1) + ' of ' + shown.length + ' · ' + STEPS[step];
        const p = prevOf(step);
        // The first step has no Back: the header's arrow leaves the setup (no seasons page since 2026-10-27, the owner).
        $('auBack').hidden = p < 0;
        if (p >= 0) navLabel($('auBack'), 'Back to ', STEPS[p], 'back');
        $('auNext').hidden = step === STEPS.length - 1;
        // The reading is the next thing to do on its own step (2026-10-28, the owner: "if read the farm is important then
        // make it the next step, not next:fertilizer"): until Anee has read the farm, Next reads it.
        const read = step === 6 && !S.analysis;
        $('auNext').classList.toggle('is-read', read);
        if (read) navLabel($('auNext'), '', '🔎 Read the farm', null);
        else if (step !== 7) navLabel($('auNext'), 'Next: ', step === 5 ? 'Anee reads it' : STEPS[nextOf(step)], 'next');
    };
    const show = async (n, back = false) => {
        n = Math.max(0, Math.min(STEPS.length - 1, n));
        if (stepHidden(n)) n = back ? Math.max(0, prevOf(n)) : nextOf(n);
        await flush();
        if (n !== step) window.aneeStepOut?.(document.querySelector('.au-step.is-on'));
        step = n;
        document.querySelectorAll('.au-step').forEach((el) => { const on = Number(el.dataset.step) === n; el.classList.toggle('is-on', on); el.classList.toggle('is-back', on && back); });
        navSay();
        $('auNavSay').textContent = '';
        if (n > (Number(S.step) || 0)) { S.step = n; put(U.answers, { step: n }).catch(() => {}); }
        paintStep(n);
        window.scrollTo({ top: 0, behavior: 'auto' });
    };
    $('auRail').addEventListener('click', (e) => { const b = e.target.closest('[data-go]'); if (b && !b.disabled) show(Number(b.dataset.go), Number(b.dataset.go) < step); });
    $('auBack').addEventListener('click', () => { const p = prevOf(step); if (p >= 0) show(p, true); });
    $('auNext').addEventListener('click', () => {
        // The fertilizer step walks the lots one by one before it moves on.
        if (step === 7) { const at = S.lots.findIndex((x) => x.id === FLOT); if (at >= 0 && at < S.lots.length - 1) { FLOT = S.lots[at + 1].id; paintFert(); window.scrollTo({ top: 0, behavior: still() ? 'auto' : 'smooth' }); return; } }
        if (step === 6 && !S.analysis) return runReading();
        const gap = gapOf(step);
        if (gap) return window.toast?.(gap, 'error');
        show(nextOf(step));
    });
    const gapOf = (n) => {
        if (n === 0 && !$('auTitle').value.trim()) return 'Give the season a name.';
        if (n === 1) { for (const l of S.lots) { const g = lotGap(l); if (g) return (l.name || 'A lot') + ': ' + g; } }
        if (n === 4 && !S.answers.water) return 'Pick where the water comes from.';
        if (n === 6 && !S.analysis) return 'Let Anee read the farm first.';
        // The target is the farmer's pick, one for each lot, before the fertilizer is planned for it.
        if (n === 6) { const l = S.lots.find((x) => !goalOf(x).pick); if (l) return 'Choose a goal for ' + (l.name || 'each lot') + ': it sets the fertilizer.'; }
        return null;
    };
    const paintStep = (n) => {
        if (n === 1) paintLots();
        if (n === 2) { paintWorkers(); loadCrew(); }
        if (n === 3) { paintSoil(); paintFrom(); }
        if (n === 4) paintWater();
        if (n === 5) { paintProblems(); paintFrom(); }
        if (n === 6) paintReading();
        if (n === 7) paintFert();
        if (n === 8) paintReview();
    };

    /* ---- 0. how Anee runs the season (App\Support\AutoOptions, 2026-10-28, the owner: "ask the user if user wants to
       include inventory and price ..., include land prep ..., irrigation schedules ..., and worker payments"; "let the
       user select" the protection; "mixing sprays that are compatible to save labor"). Saved the moment it changes. ---- */
    const OB = B.optionsBook;
    S.options = B.options;
    const saveOptions = () => put(U.answers, { options: S.options });
    const fleetOf = (k) => (S.options.fleet || []).find((f) => f.type === k) || null;
    const paintOptions = () => {
        const o = S.options;
        $('auOpts').innerHTML = '<h3 class="au-h">How Anee runs this season</h3><p class="au-sub">Choose what Anee takes on. Whatever you leave off, she still advises on.</p>'
            + '<div class="au-sws">' + Object.entries(OB.switches).map(([k, w]) => '<button type="button" class="au-sw" role="switch" aria-checked="' + !!o[k] + '" data-opt="' + k + '"><span class="au-sw-i" aria-hidden="true">' + w.icon + '</span>'
                + '<span class="au-sw-t"><b>' + esc(w.title) + '</b><small>' + esc(o[k] ? w.on : w.off) + '</small></span><span class="au-sw-k" aria-hidden="true"><i></i></span></button>'
                + (k === 'landPrep' ? '<div class="au-fleet' + (o.landPrep ? ' is-on' : '') + '" id="auFleet"><div><div class="au-fleet-in"><span class="au-lab" style="margin-top:0">What do you have for land preparation?</span>'
                    + OB.machines.map((m) => { const f = fleetOf(m.key), v = f ? (f.own ? 'own' : 'hire') : 'none';
                        return '<div class="au-mach">' + (m.photo ? '<img src="' + esc(m.photo) + '" alt="" loading="lazy">' : '<img alt="" src="data:image/gif;base64,R0lGODlhAQABAAAAACw=">') + '<span>' + esc(m.name) + (m.local ? '<small>' + esc(m.local) + '</small>' : '') + '</span>'
                            + '<span class="au-seg" role="group" aria-label="' + esc(m.name) + '">' + [['none', 'No'], ['own', 'Own'], ['hire', 'Hire']].map(([x, t]) => '<button type="button" data-mach="' + m.key + '" data-v="' + x + '" aria-pressed="' + (v === x) + '">' + t + '</button>').join('') + '</span></div>'; }).join('')
                    + '<span class="au-lab">Can you hire a tractor or an operator when you need one?</span><div class="au-pills">' + Object.entries(OB.hire).map(([x, t]) => '<button type="button" class="au-pill" data-hire="' + x + '" aria-pressed="' + (o.hire === x) + '">' + esc(t) + '</button>').join('') + '</div>'
                    + '</div></div></div>' : '')).join('') + '</div>'
            + '<span class="au-lab">How Anee protects the crop</span><div class="au-prot" role="radiogroup" aria-label="How Anee protects the crop">'
            + Object.entries(OB.protection).map(([k, p]) => '<div class="au-pc" role="radio" tabindex="0" aria-checked="' + (o.protection === k) + '" data-prot="' + k + '">' + (k === 'smart' ? '<em>Recommended</em>' : '') + '<span aria-hidden="true">' + p.icon + '</span><b>' + esc(p.title) + '</b><small>' + esc(p.line) + '</small></div>').join('')
            + '</div>';
    };
    $('auOpts').addEventListener('click', (e) => {
        const sw = e.target.closest('[data-opt]'), m = e.target.closest('[data-mach]'), h = e.target.closest('[data-hire]'), pc = e.target.closest('[data-prot]');
        if (sw) {
            const k = sw.dataset.opt; S.options[k] = !S.options[k];
            sw.setAttribute('aria-checked', S.options[k] ? 'true' : 'false');
            sw.querySelector('small').textContent = S.options[k] ? OB.switches[k].on : OB.switches[k].off;
            if (k === 'landPrep') $('auFleet')?.classList.toggle('is-on', S.options.landPrep);
            later('options', saveOptions, 400); return;
        }
        if (m) {
            const k = m.dataset.mach, v = m.dataset.v;
            S.options.fleet = (S.options.fleet || []).filter((f) => f.type !== k);
            if (v !== 'none') S.options.fleet.push({ type: k, own: v === 'own' ? 1 : 0, hired: v === 'hire' ? 1 : 0 });
            m.parentElement.querySelectorAll('button').forEach((b) => b.setAttribute('aria-pressed', b === m ? 'true' : 'false'));
            later('options', saveOptions, 400); return;
        }
        if (h) { S.options.hire = h.dataset.hire; h.parentElement.querySelectorAll('[data-hire]').forEach((b) => b.setAttribute('aria-pressed', b === h ? 'true' : 'false')); later('options', saveOptions, 400); return; }
        if (pc) { S.options.protection = pc.dataset.prot; $('auOpts').querySelectorAll('[data-prot]').forEach((b) => b.setAttribute('aria-checked', b === pc ? 'true' : 'false')); later('options', saveOptions, 300); }
    });
    $('auOpts').addEventListener('keydown', (e) => { const pc = e.target.closest('[data-prot]'); if (pc && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); pc.click(); } });
    paintOptions();

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
        { const w = WIN[l.id]; if (w && w.window && l.fieldDate && l.fieldDate < w.window.earliest && !w.squeeze) return 'the planting day comes before the land preparation can finish: pick a later day, or shorten the land preparation.'; }
        if (!(Number(l.size) > 0)) return 'say how big it is.';
        if (!l.pin && !(l.province && l.town)) return LINK[l.id] ? 'drop a pin, or give your lot its town with Edit the lot.' : 'pick its province and town, or drop a pin.';
        return null;
    };
    const saveLot = (l, extra = {}) => put(U.lot, Object.assign({ lotId: l.id, name: l.name, size: l.size, unit: l.unit || 'ha', crop: l.crop, variety: l.variety || '', method: l.method,
        fieldDate: l.method === 'tree' ? null : l.fieldDate, treeMonths: l.method === 'tree' ? l.treeMonths : null, province: l.province, town: l.town, barangay: l.barangay,
        lat: l.pin ? l.pin.lat : null, lng: l.pin ? l.pin.lng : null }, extra)).then((r) => { if (r && r.data) { l.sowDate = r.data.sowDate; l.method = r.data.method || l.method; } paintLotNotes(l); paintLotState(l); goalsSoon(null, 900); if (WIN[l.id] && WIN[l.id].key !== winKey(l)) loadWin(l); if (VPK[l.id] && VPK[l.id].key !== vpkKey(l)) loadVpk(l); return r; });
    const methodsOf = (l) => (cropOf(l.crop) || {}).methods || {};
    /* The farm lot this lot was filled from: the loaded row, or (until the
       farm's lots load) what was copied from it into this lot. */
    const farmRow = (l) => { const id = LINK[l.id]; return id && FLROWS ? FLROWS.find((r) => r.id === id) || null : null; };
    const farmView = (l) => {
        if (!LINK[l.id]) return null;
        if (FLROWS) return farmRow(l);
        return { id: LINK[l.id], name: l.name, place: [l.barangay, townName(l.town), l.province].filter(Boolean).join(', '), areaHa: l.unit === 'ha' ? l.size : null, pin: l.pin || null };
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
        // A lot already on its map brings its spot along: no second pin is asked.
        const mapped = !!(fv && farmPin(fv));
        return '<div class="au-lot" data-lot="' + l.id + '" style="--i:' + i + '">'
            + '<div class="au-lot-h"><span class="au-lot-n">' + (i + 1) + '</span><input type="text" data-f="name" value="' + esc(l.name) + '" maxlength="255" aria-label="Lot name"' + (fv ? ' readonly title="The name of your lot. Edit the lot to change it."' : '') + '><span class="au-lot-st" data-st></span></div>'
            + '<div class="au-lot-b">'
            + '<span class="au-lab">Which of your lots is it? <small>(optional)</small></span>'
            + (fv ? '<button type="button" class="au-pin is-set" data-act="farm"><span aria-hidden="true">🗺️</span><span><b>' + esc(fv.name) + '</b><small>' + esc([fv.place, Number(fv.areaHa) > 0 ? (window.aneeLots && window.aneeLots.fmtSize ? window.aneeLots.fmtSize(fv) : fmtHa(fv.areaHa)) : '', mapped ? 'On the map' : ''].filter(Boolean).join(' · ')) + '</small></span></button>'
                + '<div class="au-flot-acts"><button type="button" class="au-link" data-act="farm">Change</button><button type="button" class="au-link" data-act="farmEdit">Edit the lot</button></div>'
                // Preview answers and Edit answers (2026-10-19, the owner): each opens a sheet (partials/farm-lots).
                + (window.aneeLots && window.aneeLots.ansButtons ? window.aneeLots.ansButtons('data-act="farmPrev"', 'data-act="farmAns"', 'is-under') : '')
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
            + '</div><div class="au-vpk" data-vpk="' + l.id + '">' + vpkHtml(l) + '</div>'
            + (l.method === 'tree' ? '' : '<div class="au-win" data-win="' + l.id + '">' + winHtml(l) + '</div>') + '<div data-notes></div>'
            + (fv ? '' : '<div class="au-row is-2"><div><span class="au-lab">Province</span><button type="button" class="au-tag' + (l.province ? '' : ' is-none') + '" data-act="prov"><span>' + esc(l.province || 'Choose the province') + '</span>' + CHEV + '</button></div>'
                + '<div><span class="au-lab">Town or city</span><button type="button" class="au-tag' + (l.town ? '' : ' is-none') + '" data-act="town"' + (l.province ? '' : ' disabled') + '><span>' + esc(l.town ? townName(l.town) : (l.province ? 'Choose the town' : 'Province first')) + '</span>' + CHEV + '</button></div></div>'
                + '<label class="au-lab">Barangay <small>(optional)</small></label><input type="text" class="form-input w-full" data-f="barangay" maxlength="120" value="' + esc(l.barangay || '') + '">')
            + (mapped ? '' : '<span class="au-lab">Pin on the map <small>(optional)</small></span><button type="button" class="au-pin' + (l.pin ? ' is-set' : '') + '" data-act="pin"><span aria-hidden="true">📍</span><span>' + (l.pin ? 'Pinned at ' + Number(l.pin.lat).toFixed(4) + ', ' + Number(l.pin.lng).toFixed(4) + '<small>Tap to move it</small>' : 'Drop a pin on the lot<small>Optional: the weather and the soil are then read at the exact spot</small>') + '</span></button>')
            + '<div class="au-goal" data-goal="' + l.id + '">' + goalBlock(l) + '</div>'
            + '</div></div>';
    };

    /* ---- how much each lot plants, then its goal (App\Support\AutoGoal, 2026-10-28, the owner: "first, user should be
       asked how much to plant, the spacing if needed (all optional). this is before the analysis of anee. then based on
       the calculation of npk calculator plus the anee analysis, show realistic to achieve then achievable but risky (then
       show the high potential). then user selects about it (each selection will have its own requirement for
       fertilizer first so that user can choose what is the target)"). The lot card asks the planting; the Reading step
       shows the three goals, each with the fertilizer it takes, and the farmer picks one. ---- */
    const LEVELS = @json(collect(\App\Support\AutoGoal::LEVELS)->map(fn ($l) => ['label' => $l[0], 'icon' => $l[1], 'say' => $l[4]]));
    S.goalIn = (S.answers.goals && !Array.isArray(S.answers.goals)) ? S.answers.goals : {};
    // How each lot's planting is asked, from the page's first paint; the goals themselves come with the count.
    S.goalOut = Object.fromEntries(S.lots.filter((l) => l.plant).map((l) => [l.id, { plant: l.plant, method: l.protoMethod, recommend: l.recommend || null }]));
    const goalOf = (l) => (S.goalIn[l.id] = S.goalIn[l.id] || {});
    const fmt1 = (v) => String(Math.round(Number(v) * 10) / 10);
    const goalBlock = (l) => {
        if (!l.crop) return '';
        const g = goalOf(l), out = S.goalOut[l.id] || {}, pl = out.plant || {};
        const sp = pl.spacing, units = pl.units || ['kg'], by = g.by || (sp ? 'spacing' : 'amount');
        const unit = units.includes(g.unit) ? g.unit : units[0], m = sp && sp.meters;
        const val = (v, f) => v ? Number(v) / f : '';
        const ph = (r) => r ? String(m ? r[1] / 100 : r[1]) : '';
        const germOn = pl.germ === 'seed' ? (by === 'spacing' || ['kg', 'g', 'seeds', 'hills'].includes(unit)) : (pl.germ === 'seedlings' ? by !== 'spacing' && ['kg', 'g', 'seeds'].includes(unit) : false);
        return '<span class="au-lab">How much you plant <small>(optional: it sets the harvest the lot can carry)</small></span>'
            + '<div data-rec>' + recHtml(l) + '</div>'
            + '<div class="au-pills">' + (sp ? '<button type="button" class="au-pill" data-by="spacing" aria-pressed="' + (by === 'spacing') + '">By spacing</button>' : '') + '<button type="button" class="au-pill" data-by="amount" aria-pressed="' + (by !== 'spacing' || !sp) + '">By amount</button></div>'
            + (by === 'spacing' && sp
                ? '<div class="au-row is-3 mt-2"><label class="au-gl">Between rows<span><input type="number" class="form-input" data-gf="row" min="0" step="any" inputmode="decimal" value="' + val(g.row, m ? 100 : 1) + '" placeholder="' + ph(sp.row) + '"><em>' + (m ? 'm' : 'cm') + '</em></span></label>'
                    + '<label class="au-gl">Between ' + (sp.perHill && sp.perHill[1] > 1 ? 'hills' : 'plants') + '<span><input type="number" class="form-input" data-gf="plant" min="0" step="any" inputmode="decimal" value="' + val(g.plant, m ? 100 : 1) + '" placeholder="' + ph(sp.plant) + '"><em>' + (m ? 'm' : 'cm') + '</em></span></label>'
                    + '<label class="au-gl">' + (['transplanted', 'nursery'].includes(out.method) ? 'Seedlings' : sp.word === 'seeds' ? 'Seeds' : 'Plants') + ' a hill<span><input type="number" class="form-input" data-gf="perHill" min="1" max="50" step="1" inputmode="numeric" value="' + (g.perHill || '') + '" placeholder="' + (sp.perHill ? sp.perHill[1] : 1) + '"></span></label></div>'
                    + (sp.note ? '<p class="text-xs text-gray-500 mt-1">' + esc(sp.note) + '</p>' : '')
                : '<div class="flex gap-2 mt-2"><input type="number" class="form-input flex-1" data-gf="amount" min="0" step="any" inputmode="decimal" value="' + (g.amount || '') + '" placeholder="For this lot">'
                    + '<button type="button" class="au-pill" data-unit aria-label="Change the unit">' + esc((pl.unitWords || {})[unit] || unit) + '</button></div>')
            + (germOn ? '<label class="au-gl mt-2"><i>Germination on the seed bag <small>(optional)</small></i><span><input type="number" class="form-input" data-gf="germ" min="1" max="100" step="1" inputmode="numeric" value="' + (g.germ || '') + '" placeholder="Like 90"><em>%</em></span></label>' : '')
            + '<p class="au-stand' + standCls(l) + '" data-goalout>' + standHtml(l) + '</p>';
    };
    // The usual planting for the crop, for a farmer who is not sure (2026-10-28, the owner: "in seeds and spacing, just to
    // guide the user if he doesn't know just add a recommendation part"): AutoGoal::recommend, with a button that fills it in.
    const recHtml = (l) => {
        const r = (S.goalOut[l.id] || {}).recommend, pl = (S.goalOut[l.id] || {}).plant || {};
        if (!r || !r.say) return '';
        const f = r.meters ? 100 : 1, u = r.meters ? ' m' : ' cm';
        const w = String(r.word || 'seeds'), each = r.perHill === 1 ? w.replace(/(ies)$/, 'y').replace(/s$/, '') : w;
        const head = r.by === 'spacing' ? fmt1(r.row / f) + ' by ' + fmt1(r.plant / f) + u + ', ' + r.perHill + ' ' + each + ' a hill'
            : (r.by === 'amount' ? fmt1(r.amount) + ' ' + ((pl.unitWords || {})[r.unit] || r.unit) + ' for this lot' : '');
        return '<div class="au-rec"><span aria-hidden="true">💡</span><span><b>Not sure? The usual for this crop' + (head ? ': ' + esc(head) : '') + '</b><small>' + esc(r.say) + '</small></span>'
            + (head ? '<button type="button" class="au-link" data-rec-use>Use this</button>' : '') + '</div>';
    };
    // A stand off the usual range says so in yellow: thin, crowded or far too crowded moves the goals.
    const standCls = (l) => ['sparse', 'thin', 'crowded', 'danger'].includes((S.goalOut[l.id] || {}).standStatus) ? ' is-warn' : '';
    const standHtml = (l) => {
        const o = S.goalOut[l.id], after = S.analysis ? ' The goals are counted from it.' : ' The goals come after Anee reads the farm.';
        if (o && o.standSay) return '<span aria-hidden="true">🌱</span><span>' + esc(o.standSay) + (/goals/.test(o.standSay) ? '' : after) + '</span>';
        return '<span aria-hidden="true">🌱</span><span>Not given: a full usual stand is counted.' + after + '</span>';
    };
    // The planting in a few words, for the row that opens it on the Reading step.
    const plantLine = (l) => {
        const g = goalOf(l), pl = (S.goalOut[l.id] || {}).plant || {}, m = pl.spacing && pl.spacing.meters, f = m ? 100 : 1;
        const said = g.by === 'spacing' && g.row && g.plant ? fmt1(g.row / f) + ' by ' + fmt1(g.plant / f) + (m ? ' m' : ' cm') + (g.perHill ? ', ' + g.perHill + ' a hill' : '')
            : (g.by !== 'spacing' && g.amount ? fmt1(g.amount) + ' ' + ((pl.unitWords || {})[g.unit || (pl.units || ['kg'])[0]] || g.unit || 'kg') + ' for the lot' : '');
        const st = (S.goalOut[l.id] || {}).standSay;
        return said ? said + (st ? ' · ' + st : '') : 'Not given: a full usual stand is counted. Tap to say how much you plant.';
    };
    // A lot's planting block can sit in two places (its lot card, and the Reading step above its goals): data-goal = the lot.
    const goalBoxes = (l) => document.querySelectorAll('[data-goal="' + l.id + '"]');
    const paintGoalOut = (l) => {
        goalBoxes(l).forEach((b) => {
            const o = b.querySelector('[data-goalout]'); if (o) { o.innerHTML = standHtml(l); o.className = 'au-stand' + standCls(l); }
            const rc = b.querySelector('[data-rec]'); if (rc) rc.innerHTML = recHtml(l);
        });
        document.querySelectorAll('[data-plantsay="' + l.id + '"]').forEach((x) => { x.textContent = plantLine(l); });
    };
    const paintGoal = (l) => { goalBoxes(l).forEach((b) => { b.innerHTML = goalBlock(l); }); paintGoalOut(l); };
    // Saves a lot's planting or its pick (when given) and brings every lot's goals back counted.
    const saveGoals = async (l) => {
        let r;
        // A count that fails says so on the goal cards, with a way to try again, instead of counting forever.
        try { r = await put(U.goal, l ? { lotId: l.id, goal: goalOf(l) } : {}, 'POST'); S.goalErr = false; }
        catch (err) { S.goalErr = true; if (step === 6 && S.analysis) paintGoalCards(); throw err; }
        const before = JSON.stringify(Object.fromEntries(Object.entries(S.goalOut).map(([k, o]) => [k, [o.plant, o.method]])));
        S.goalOut = (r.data && r.data.goals) || {};
        // The planting asked changes with the crop and its way in: then the block is painted again.
        const after = JSON.stringify(Object.fromEntries(Object.entries(S.goalOut).map(([k, o]) => [k, [o.plant, o.method]])));
        S.lots.forEach((x) => { if (before !== after) paintGoal(x); else paintGoalOut(x); });
        if (step === 6 && S.analysis) paintGoalCards();
        if (step === 6) navSay();
        // The goal shows on the Fertilizer and Start steps too.
        if (step === 7) paintFert();
        if (step === 8) paintReview();
    };
    const goalsSoon = (l, ms = 700) => later(l ? 'goal' + l.id : 'goals', () => saveGoals(l), ms);
    document.addEventListener('click', (e) => {
        const box = e.target.closest('[data-goal]'); if (!box) return;
        const l = lotById(box.dataset.goal); if (!l) return;
        const g = goalOf(l), out = S.goalOut[l.id] || {};
        const by = e.target.closest('[data-by]'), un = e.target.closest('[data-unit]'), use = e.target.closest('[data-rec-use]');
        if (use && out.recommend) {
            const r = out.recommend;
            if (r.by === 'spacing') Object.assign(g, { by: 'spacing', row: r.row, plant: r.plant, perHill: r.perHill });
            if (r.by === 'amount') Object.assign(g, { by: 'amount', amount: r.amount, unit: r.unit });
            paintGoal(l); goalsSoon(l, 100);
            return;
        }
        if (by && g.by !== by.dataset.by) { g.by = by.dataset.by; paintGoal(l); goalsSoon(l, 300); }
        if (un) { const us = (out.plant || {}).units || ['kg']; g.unit = us[(us.indexOf(g.unit && us.includes(g.unit) ? g.unit : us[0]) + 1) % us.length]; paintGoal(l); goalsSoon(l, 300); }
    });
    document.addEventListener('input', (e) => {
        const f = e.target.dataset.gf; if (!f) return;
        const box = e.target.closest('[data-goal]'); if (!box) return;
        const l = lotById(box.dataset.goal); if (!l) return;
        const g = goalOf(l), m = ((S.goalOut[l.id] || {}).plant || {}).spacing?.meters;
        const v = e.target.value === '' ? null : Number(e.target.value);
        g[f] = v === null ? null : ((f === 'row' || f === 'plant') && m ? v * 100 : v);
        if (f === 'amount' || f === 'row' || f === 'plant') g.by = g.by || (f === 'amount' ? 'amount' : 'spacing');
        document.querySelectorAll('[data-plantsay="' + l.id + '"]').forEach((x) => { x.textContent = plantLine(l); });
        goalsSoon(l, 800);
    });
    const dayLabel = (l) => ({ transplanted: 'Transplanting day (DAT 0)', direct: 'Sowing day in the field (DAS 0)', planted: 'Planting day (DAP 0)' })[l.method] || 'Planting day';
    const paintLots = () => {
        $('auLots').innerHTML = S.lots.map(lotCard).join('');
        S.lots.forEach((l) => { paintLotNotes(l); paintLotState(l); loadWin(l); loadVpk(l); });
        window.smDateTags?.($('auLots'));
        const cap = S.lotCap;
        $('auAddLot').hidden = cap != null && S.lots.length >= cap;
        $('auAddLot').textContent = '+ Add another lot · ' + Number(S.perLot).toLocaleString() + ' credits';
    };
    /* ---- when to plant each lot (App\Support\PlantWindow, 2026-10-28, the owner: "anee should be able to provide an
       insight ... when is the best date to plant (always from the current date to 1 year range), before the user selects
       a day ... also have 2 considerations, the price forecast upon harvest, and the usual ... upon selecting the date,
       have a dynamic calculation on when it will be harvested"). Fifty two weeks scored from the field's own twenty years,
       the three best as cards, the land preparation the farm can do, the harvest day of the day picked. ---- */
    const WIN = {};
    let WMODE = 'crop';
    // window.api comes with app.js, a module that runs after this script: a read asked while the page boots waits for it.
    const apiGet = async (url) => { if (!window.api) await new Promise((r) => document.readyState === 'complete' ? setTimeout(r, 300) : window.addEventListener('load', r, { once: true })); return window.api(url); };
    const winKey = (l) => [l.crop, l.method, l.variety || '', l.province || '', l.town || '', l.pin ? l.pin.lat + ',' + l.pin.lng : ''].join('|');
    const winReady = (l) => l.crop && l.method && l.method !== 'tree' && (l.pin || (l.province && l.town) || LINK[l.id]);
    const loadWin = async (l, force) => {
        if (!winReady(l)) { const b = document.querySelector('[data-win="' + l.id + '"]'); if (b) b.innerHTML = winHtml(l); return; }
        const k = winKey(l);
        if (!force && WIN[l.id] && WIN[l.id].key === k) { paintWin(l); return; }
        WIN[l.id] = { key: k, loading: true };
        paintWin(l);
        try {
            const r = await apiGet(U.window + '?lotId=' + l.id);
            WIN[l.id] = Object.assign({ key: k }, r.data || {});
        } catch (_) { WIN[l.id] = { key: k, failed: true }; }
        paintWin(l); paintLotNotes(l); paintLotState(l);
    };
    const wCls = (v) => v >= 75 ? 'is-good' : (v >= 60 ? 'is-ok' : (v >= 40 ? 'is-poor' : 'is-bad'));
    const wDay = (iso, y) => { const [yy, m, d] = iso.split('-').map(Number); return new Date(yy, m - 1, d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: y ? 'numeric' : undefined }); };
    const wPct = (rel) => { const d = Math.round((Number(rel) - 1) * 100); return d === 0 ? 'at a usual price' : (d > 0 ? d + '% above' : Math.abs(d) + '% below') + ' a usual harvest month'; };
    const winHtml = (l) => {
        if (l.method === 'tree') return '';
        const st = WIN[l.id];
        if (!winReady(l)) return '<div class="au-win-h"><span aria-hidden="true">📅</span><div><b>When to plant</b><small>Choose the crop and where the lot is, and Anee reads its best weeks for the year ahead.</small></div></div>';
        if (!st || st.loading) return '<div class="au-win-h"><span aria-hidden="true" class="au-win-spin">📅</span><div><b>When to plant</b><small>Anee is reading twenty years of this field\'s weather…</small></div></div><div class="au-wchart is-wait">' + '<i></i>'.repeat(52) + '</div>';
        const w = st.window;
        if (!w) return '<div class="au-win-h"><span aria-hidden="true">📅</span><div><b>When to plant</b><small>' + (st.failed ? 'It did not load. ' : 'This place has no weather record yet, so pick the day you know works. ') + '<button type="button" class="au-link" data-wretry>Try again</button></small></div></div>';
        const by = WMODE === 'price' && w.market ? 'priceScore' : 'score';
        const best = by === 'priceScore' ? w.bestPrice : w.best;
        let months = '', last = -1, lastAt = -9;
        // A month's name where it begins, never on top of the one before (the first week may be the end of a month).
        w.weeks.forEach((x, i) => { const m = Number(x.date.slice(5, 7)); if (m !== last) { if (i - lastAt >= 3) { months += '<span style="left:' + (i / 52 * 100).toFixed(2) + '%">' + new Date(2000, m - 1, 1).toLocaleDateString(undefined, { month: 'short' }) + '</span>'; lastAt = i; } last = m; } });
        const on = l.fieldDate ? w.weeks.findIndex((x, i) => l.fieldDate >= addDays(x.date, -3) && (i === 51 || l.fieldDate < addDays(w.weeks[i + 1].date, -3))) : -1;
        return '<div class="au-win-h"><span aria-hidden="true">📅</span><div><b>When to plant</b><small>' + esc(w.place || 'This field') + ': ' + w.years + ' years of its weather and storms, read for ' + esc(cropName(l.crop).split(',')[0].toLowerCase()) + ', from today to a year ahead. ' + esc(w.waterSays || '') + '</small></div></div>'
            + (w.market ? '<div class="au-wsw" role="tablist" aria-label="How the weeks are ranked"><i aria-hidden="true"' + (by === 'priceScore' ? ' class="is-price"' : '') + '></i><button type="button" role="tab" data-wmode="crop" aria-selected="' + (by === 'score') + '">🌦️ For the crop</button><button type="button" role="tab" data-wmode="price" aria-selected="' + (by === 'priceScore') + '">💰 With the harvest price</button></div>' : '')
            + '<div class="au-wchart' + (by === 'priceScore' ? ' is-price' : '') + '" role="img" aria-label="' + esc('Planting score by week for the next 52 weeks, best ' + (best.map((i) => wDay(w.weeks[i].date) + ' ' + w.weeks[i][by]).join(', '))) + '">'
            + w.weeks.map((x, i) => { const v = x[by] ?? x.score; return '<i class="' + wCls(v) + (x.prepShort ? ' is-prep' : '') + (i === on ? ' is-on' : '') + (best.includes(i) ? ' is-best' : '') + '" style="--h:' + Math.max(0.04, v / 100).toFixed(2) + ';--k:' + i + '" data-wk="' + i + '" title="' + esc(wDay(x.date, true) + ': ' + v + '/100 · harvest ' + wDay(x.harvest) + (x.prepShort ? ' · before the land preparation can finish' : '')) + '"></i>'; }).join('')
            + '</div><div class="au-wmonths" aria-hidden="true">' + months + '</div>'
            + (w.prepNeed ? '<p class="au-wkey"><i class="is-prep"></i>' + (st.prep && st.prep.on ? 'Faded weeks come before your land preparation can finish (' + esc(st.prep.why) + ', ready ' + wDay(w.earliest) + ').' : 'Faded weeks come before the field can be ready (' + esc(st.prep.why) + ').') + '</p>' : '')
            + '<div class="au-wbest">' + best.map((i, n) => { const x = w.weeks[i]; return '<div class="au-wb' + (n === 0 ? ' is-top' : '') + '" style="--k:' + n + '"><span class="au-wb-n">' + (n + 1) + '</span><div><b>' + esc(wDay(x.date, true)) + '</b>'
                + '<small>' + (by === 'priceScore' ? (x.priceScore ?? x.score) + '/100 with the price · ' + x.score + ' for the crop' : x.score + '/100') + ' · harvest about ' + esc(wDay(x.harvest)) + '</small>'
                + (by === 'priceScore' && x.rel ? '<small>💰 Sells ' + esc(wPct(x.rel)) + (x.p ? ', about ₱' + Number(x.p).toFixed(2) + (x.unit === 'kg' ? '/kg' : '') : '') + '</small>' : '')
                + '<small class="au-wb-why">' + esc(x.why && x.why.length ? 'Watch: ' + x.why.join('; ') : 'Few troubles in ' + w.years + ' years') + '</small></div>'
                + '<button type="button" class="au-link" data-wuse="' + x.date + '">Plant this week</button></div>'; }).join('') + '</div>'
            + '<div data-wpick>' + winPickHtml(l) + '</div>';
    };
    // The day picked: its harvest, how it scores, and the land preparation before it.
    const winPickHtml = (l) => {
        const st = WIN[l.id], w = st && st.window, c = cropOf(l.crop);
        if (!l.fieldDate || l.method === 'tree') return '';
        const toH = w ? w.toHarvest : (c && c.maturity ? (l.method === 'transplanted' && String(l.crop).startsWith('rice') && c.nursery ? c.maturity - Math.round((c.nursery.min + c.nursery.max) / 2) : c.maturity) : null);
        let html = toH ? '<div class="au-note is-info au-wpick-h"><span>🌾</span><span><b>Harvest about ' + esc(fmtDate(addDays(l.fieldDate, toH))) + '</b>, ' + toH + ' days in the field' + (w ? (() => { const i = w.weeks.findIndex((x, j) => l.fieldDate >= addDays(x.date, -3) && (j === 51 || l.fieldDate < addDays(w.weeks[j + 1].date, -3))); const x = w.weeks[Math.max(0, i)]; return i < 0 ? '' : '. This week scores <b>' + x.score + '/100</b>' + (x.priceScore != null ? ' (' + x.priceScore + ' with the price)' : '') + (x.why && x.why.length ? ': watch ' + esc(x.why.join('; ')) : ', with few troubles on record'); })() : '') + '.</span></div>' : '';
        if (w && l.fieldDate < w.earliest) {
            html += '<div class="au-note is-warn"><span>⚠️</span><span><b>Too soon for the land preparation.</b> It needs ' + esc(st.prep.why) + ', so the field is ready about ' + esc(fmtDate(w.earliest)) + '. Pick that day or later, or plant on your day with a shortened land preparation: Anee squeezes the waiting between passes, at the cost of weeds that come back and a rougher seedbed.'
                + '<label class="au-wsq"><input type="checkbox" data-wsq' + (st.squeeze ? ' checked' : '') + '> Shorten the land preparation to fit my day</label></span></div>';
        }
        return html;
    };
    const paintWin = (l) => { const b = document.querySelector('[data-win="' + l.id + '"]'); if (b) b.innerHTML = winHtml(l); };
    const paintWinPick = (l) => { const b = document.querySelector('[data-win="' + l.id + '"] [data-wpick]'); if (b) b.innerHTML = winPickHtml(l); else paintWin(l);
        const w = WIN[l.id] && WIN[l.id].window; if (w) { const on = l.fieldDate ? w.weeks.findIndex((x, i) => l.fieldDate >= addDays(x.date, -3) && (i === 51 || l.fieldDate < addDays(w.weeks[i + 1].date, -3))) : -1;
            document.querySelectorAll('[data-win="' + l.id + '"] .au-wchart i').forEach((el, i) => el.classList.toggle('is-on', i === on)); } };
    const saveSqueeze = () => put(U.answers, { prepSqueeze: Object.fromEntries(Object.entries(WIN).filter(([, v]) => v && v.squeeze).map(([k]) => [k, true])) });
    $('auLots').addEventListener('click', (e) => {
        const box = e.target.closest('[data-win]'); if (!box) return;
        const l = lotById(box.dataset.win); if (!l) return;
        const mode = e.target.closest('[data-wmode]'), use = e.target.closest('[data-wuse]'), bar = e.target.closest('[data-wk]');
        if (e.target.closest('[data-wretry]')) { loadWin(l, true); return; }
        if (mode) { WMODE = mode.dataset.wmode; S.lots.forEach(paintWin); return; }
        const pickDay = (iso) => {
            l.fieldDate = iso < B.today ? B.today : iso;
            const inp = lotEl(l.id)?.querySelector('[data-f="fieldDate"]'); if (inp) { inp.value = l.fieldDate; window.smDateTags?.(lotEl(l.id)); inp.dispatchEvent(new Event('input', { bubbles: true })); }
            paintLotNotes(l); paintWinPick(l); paintLotState(l); later('lot' + l.id, () => saveLot(l), 50);
            lotEl(l.id)?.querySelector('[data-wpick]')?.scrollIntoView({ block: 'nearest', behavior: still() ? 'auto' : 'smooth' });
        };
        if (use) { pickDay(use.dataset.wuse); return; }
        if (bar) { const w = WIN[l.id].window; pickDay(w.weeks[Number(bar.dataset.wk)].date); }
    });
    $('auLots').addEventListener('change', (e) => {
        const sq = e.target.closest('[data-wsq]'); if (!sq) return;
        const l = lotById(e.target.closest('[data-win]').dataset.win); if (!l || !WIN[l.id]) return;
        WIN[l.id].squeeze = sq.checked; paintLotState(l); later('squeeze', saveSqueeze, 200);
    });
    /* ---- Anee's picks of variety, before one is chosen (2026-10-28, the owner: "do an analysis first (similar to the
       variety ranking) so that the user can have an idea for the ranking of what the variety to choose and present it
       before selecting a variety"). The records that fit the lot, weighed by what each seed guards against. ---- */
    const VPK = {};
    const vpkKey = (l) => [l.crop, l.province || '', l.town || '', l.fieldDate ? l.fieldDate.slice(0, 7) : ''].join('|');
    const loadVpk = async (l) => {
        if (!l.crop) { const b = document.querySelector('[data-vpk="' + l.id + '"]'); if (b) b.innerHTML = ''; return; }
        const k = vpkKey(l);
        if (VPK[l.id] && VPK[l.id].key === k) { paintVpk(l); return; }
        VPK[l.id] = { key: k, loading: true, open: VPK[l.id] ? VPK[l.id].open : false };
        paintVpk(l);
        try { const r = await apiGet(U.varieties + '?lotId=' + l.id); Object.assign(VPK[l.id], r.data || {}, { loading: false }); }
        catch (_) { Object.assign(VPK[l.id], { loading: false, picks: [] }); }
        paintVpk(l);
    };
    const vpkHtml = (l) => {
        const st = VPK[l.id];
        if (!l.crop || !st) return '';
        if (st.loading) return '<div class="au-vpk-h"><span aria-hidden="true">🔬</span><b>Anee is ranking the varieties for this lot…</b></div>';
        const ps = st.picks || [];
        if (!ps.length) return '';
        const folded = !!l.variety && !st.open;
        if (folded) return '<button type="button" class="au-vpk-line" data-vpkopen><span aria-hidden="true">🔬</span><span>Anee\'s picks for this lot: <b>' + esc(ps.slice(0, 3).map((p) => p.alias || p.name).join(', ')) + '</b></span>' + CHEV + '</button>';
        const unit = (p) => p.unit === 't/ha' ? ' t/ha' : ' ' + (p.unit || '');
        return '<div class="au-vpk-h"><span aria-hidden="true">🔬</span><div><b>Anee\'s picks for this lot</b><small>From the records of every registered variety: what fits this ground, this ' + esc(st.season || '') + ' season and the troubles it had, and what each seed guards against. Tap one to use it, or choose your own above.</small></div>'
            + (l.variety ? '<button type="button" class="au-link" data-vpkclose>Fold</button>' : '') + '</div>'
            + '<div class="au-vpks">' + ps.map((p, i) => '<div class="au-vp' + (i === 0 ? ' is-top' : '') + (l.variety === p.name ? ' is-on' : '') + '" style="--k:' + i + '">'
                + '<div class="au-vp-h"><span class="au-vp-n">' + (i + 1) + '</span><div><b>' + esc(p.name) + '</b>' + (p.alias ? '<small>' + esc(p.alias) + '</small>' : '') + '</div><span class="au-vp-s">' + p.score + '</span></div>'
                + '<div class="au-vp-c">' + (p.days ? '<span>⏱ ' + Math.round(p.days) + ' ' + esc(p.daysUnit || 'days') + '</span>' : '') + (p.yield ? '<span>🌾 ' + fmt1(p.yield) + unit(p) + (p.top ? ', top ' + fmt1(p.top) : '') + '</span>' : '') + '<span>' + esc(p.type === 'hybrid' ? 'Hybrid' : (p.kind || 'Inbred')) + '</span></div>'
                + (p.protection ? '<p class="au-vp-p">🛡️ ' + esc(p.protection) + '</p>' : '') + (p.weak && p.weak.length ? '<p class="au-vp-w">⚠️ Weak against ' + esc(p.weak.join(', ')) + '</p>' : '')
                + (p.why && p.why.length ? '<ul class="au-vp-y">' + p.why.map((w) => '<li>' + esc(w) + '</li>').join('') + '</ul>' : '')
                + '<div class="au-vp-a"><button type="button" class="au-pill" data-vpuse="' + esc(p.name) + '" aria-pressed="' + (l.variety === p.name) + '">' + (l.variety === p.name ? '✓ Chosen' : 'Use this variety') + '</button>'
                + (p.url ? '<a class="au-link" href="' + esc(p.url) + '" target="_blank" rel="noopener">Its record</a>' : '') + '</div></div>').join('') + '</div>'
            + (st.finder ? '<p class="au-vpk-more"><a class="au-link" href="' + esc(st.finder) + '" target="_blank" rel="noopener">Compare every variety in the finder</a></p>' : '');
    };
    const paintVpk = (l) => { const b = document.querySelector('[data-vpk="' + l.id + '"]'); if (b) b.innerHTML = vpkHtml(l); };
    $('auLots').addEventListener('click', (e) => {
        const box = e.target.closest('[data-vpk]'); if (!box) return;
        const l = lotById(box.dataset.vpk); if (!l || !VPK[l.id]) return;
        if (e.target.closest('[data-vpkopen]')) { VPK[l.id].open = true; paintVpk(l); return; }
        if (e.target.closest('[data-vpkclose]')) { VPK[l.id].open = false; paintVpk(l); return; }
        const use = e.target.closest('[data-vpuse]');
        if (use) {
            l.variety = use.dataset.vpuse; l.varRecord = null;
            const v = lotEl(l.id)?.querySelector('[data-var]'); if (v) v.innerHTML = varTag(l);
            VPK[l.id].open = false; paintVpk(l); paintLotState(l);
            later('lot' + l.id, () => saveLot(l), 50);
            window.toast?.(l.variety + ' chosen for ' + (l.name || 'this lot') + '.', 'success');
        }
    });
    const lotEl = (id) => document.querySelector('.au-lot[data-lot="' + id + '"]');
    const lotById = (id) => S.lots.find((l) => String(l.id) === String(id));
    const paintLotState = (l) => {
        const el = lotEl(l.id)?.querySelector('[data-st]');
        if (!el) return;
        const g = lotGap(l);
        el.textContent = g ? 'Not yet' : 'Ready ✓';
        el.classList.toggle('is-ok', !g);
    };
    /* A lot Anee knows little about asks for more (2026-10-28, the owner: "if the lot selected has no useful information
       or feels lacking, ask the user to answer more"): the questions that move the advice most, the ones not answered. */
    const KEYQ = ['texture', 'organic', 'water', 'drainage', 'flooding', 'salt', 'acidity', 'elevation', 'prevCrop', 'harvested', 'cropLook', 'outbreaks'];
    const lackingHtml = (l) => {
        const FP = window.aneeFieldProfile;
        if (!FP || !LINK[l.id] || !FLROWS) return '';
        const fv = farmRow(l);
        if (!fv) return '';
        const a = fv.answers || {};
        const miss = KEYQ.filter((k) => FP.questions[k] && !filled(a, k));
        if (miss.length < 4) return '';
        return '<div class="au-note is-ask"><span>🙋</span><span><b>Anee knows little about ' + esc(fv.name || 'this lot') + ' yet.</b> ' + (KEYQ.length - miss.length) + ' of the ' + KEYQ.length + ' answers that matter most are in. '
            + 'Still open: ' + esc(miss.slice(0, 6).map((k) => FP.questions[k].short.toLowerCase()).join(', ')) + (miss.length > 6 ? ' and more' : '') + '. A couple of minutes makes the planting days, the varieties and the plan sharper.'
            + '<button type="button" class="au-pill" data-act="farmAns" style="margin-top:.5rem">Answer them now</button></span></div>';
    };
    const paintLotNotes = (l) => {
        const box = lotEl(l.id)?.querySelector('[data-notes]');
        if (!box) return;
        const c = cropOf(l.crop);
        let html = lackingHtml(l);
        if (c && l.fieldDate && l.method !== 'tree') {
            const left = daysFrom(B.today, l.fieldDate), p = c.prep || { min: 14, max: 21 };
            const src = p.source ? ' <a href="' + esc(p.source.url) + '" target="_blank" rel="noopener">' + esc(String(p.source.label).split(' (')[0]) + '</a>' : '';
            // The land preparation is said by the planting window, from the farm's own machines (2026-10-28); until it loads, the crop book's days.
            if (!(WIN[l.id] && WIN[l.id].window)) {
                if (left < p.min) html += '<div class="au-note is-warn"><span>⚠️</span><span><b>Land preparation needs about ' + p.min + (p.max > p.min ? ' to ' + p.max : '') + ' days</b> for ' + esc(cropName(l.crop).split(',')[0].toLowerCase()) + (p.note ? ': ' + esc(p.note.charAt(0).toLowerCase() + p.note.slice(1)) : '.') + ' Your day leaves ' + Math.max(0, left) + '. Anee will squeeze it in, but a later day gives the field a better start.' + src + '</span></div>';
                else html += '<div class="au-note is-ok"><span>✅</span><span>' + left + ' days to prepare the land, enough for the ' + p.min + (p.max > p.min ? ' to ' + p.max : '') + ' it needs.' + src + '</span></div>';
            }
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
        if (f === 'fieldDate') { paintLotNotes(l); paintWinPick(l); }
        later('lot' + l.id, () => saveLot(l), f === 'fieldDate' ? 50 : 700);
    });
    $('auLots').addEventListener('change', (e) => { if (e.target.dataset.f === 'fieldDate') { const l = lotById(e.target.closest('.au-lot').dataset.lot); if (l) { l.fieldDate = e.target.value; paintLotNotes(l); paintWinPick(l); paintLotState(l); later('lot' + l.id, () => saveLot(l), 50); } } });
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
        // The linked lot's answers in a sheet (partials/farm-lots). A save fires aneelots:changed, which refills the lots.
        if ((act === 'farmPrev' || act === 'farmAns') && window.aneeLots && window.aneeLots.review) {
            if (!FLROWS) await loadFarm();
            const fv = farmRow(l);
            if (fv && act === 'farmAns') await window.aneeLots.editAnswers(fv, { crop: l.crop || null });
            else if (fv) await window.aneeLots.review(fv, { crop: l.crop || null, onEdit: () => editFarm(l) });
        }
        if (act === 'variety') pickVariety(l, a);
        if (act === 'prov') { try { await loadPh(); } catch (_) { return; } pick('Choose the province', Object.keys(PHL).sort().map((p) => ({ key: p, label: p, hint: PHL[p].length + ' towns and cities' })), l.province, async (k) => { if (k !== l.province) l.town = null; l.province = k; await saveLot(l).catch(() => {}); paintLots(); }); }
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
    // A worker's week: each day whole, a morning, an afternoon or off (partials/work-days, 2026-10-28).
    const WD = window.workDays;
    S.crewList = S.canWorkers ? S.workers.map((w) => ({ id: w.id, globalWorkerId: w.globalWorkerId, name: w.name, email: w.email || '', phone: w.phone || '', off: w.off || [], parts: WD.partsOf(w.parts), pay: w.pay ?? null }))
        : (S.answers.crew || []).map((c) => ({ name: c.name, off: c.off || [], parts: WD.partsOf(c.parts) }));
    const saveWorkers = () => put(U.workers, { workers: S.crewList.filter((w) => (w.name || '').trim()).map((w) => ({ id: w.id || null, globalWorkerId: w.globalWorkerId || null, name: w.name.trim(), email: w.email || null, phone: w.phone || null, off: w.off, parts: w.parts, pay: Number(w.pay) > 0 ? Number(w.pay) : null })) })
        .then((r) => {
            (r.data && r.data.workers || []).forEach((w) => { const row = S.crewList.find((c) => c.id === w.id) || S.crewList.find((c) => !c.id && (c.name || '').trim() === w.name); if (row) { row.id = w.id; row.globalWorkerId = w.globalWorkerId || row.globalWorkerId; } });
            // The server wrote each one's name, contact and week to the farm's list (2026-10-28, the owner: "settings for
            // selected workers should be synced in the global workers"): the list in the sheet says the same, new faces too.
            S.crewList.filter((w) => w.globalWorkerId && (w.name || '').trim()).forEach((w) => {
                const facts = { name: w.name.trim(), email: w.email || '', phone: w.phone || '', off: [...w.off], parts: { ...w.parts } };
                const g = S.roster.find((x) => String(x.id) === String(w.globalWorkerId));
                if (g) Object.assign(g, facts); else if (S.canWorkers) S.roster.push({ id: w.globalWorkerId, ...facts });
            });
            S.roster.sort((a, b) => String(a.name).localeCompare(String(b.name)));
            if (S.canWorkers) paintGwOpen();
            loadCrew();
        });
    /* Is the crew enough: the busiest stretches (planting, harvest) of every lot against who comes those days. */
    const loadCrew = async () => {
        if (step !== 2) return;
        try {
            const d = (await apiGet(U.crew)).data || {};
            const box = $('auCrew');
            box.hidden = !(d.peaks && d.peaks.length);
            if (box.hidden) return;
            const bad = d.peaks.filter((p) => !p.ok).length;
            box.innerHTML = '<h3 class="au-h">' + (bad ? '👷 Your crew, at the busiest times' : '👍 Your crew is enough') + '</h3><p class="au-sub">' + esc(d.say) + ' The planting and the harvest are the busiest days: each must be done within a few days.</p>'
                + '<div class="au-crew">' + d.peaks.map((p, i) => { const w = Math.min(100, Math.round(100 * p.window / Math.max(p.window, p.days))); return '<div class="au-cr' + (p.ok ? '' : ' is-short') + '" style="--k:' + i + '">'
                    + '<div class="au-cr-h"><b>' + esc(p.what.charAt(0).toUpperCase() + p.what.slice(1)) + '</b><small>' + esc(p.lots.join(', ')) + ' · ' + esc(fmtDate(p.from)) + '</small><span>' + (p.ok ? '✓ Enough' : '+' + p.more + ' needed') + '</span></div>'
                    + '<div class="au-cr-bar" aria-hidden="true"><i style="--w:' + (p.ok ? Math.round(100 * p.days / p.window) : 100) + '%"></i>' + (p.ok ? '' : '<em style="left:' + w + '%"></em>') + '</div>'
                    + '<p>' + esc(p.say) + '</p></div>'; }).join('') + '</div>';
        } catch (_) { /* the check is a help, never a block */ }
    };
    const gwIn = (g) => S.crewList.some((w) => String(w.globalWorkerId) === String(g.id));
    const paintGwOpen = () => {
        const left = S.roster.filter((g) => !gwIn(g)).length;
        $('auGwOpen').hidden = !(S.canWorkers && S.roster.length);
        $('auGwOpen').innerHTML = left ? '<span aria-hidden="true">👥</span>Add from your workers<em>' + left + '</em>' : '<span aria-hidden="true">👥</span>All your workers are in';
    };
    const paintWorkers = () => {
        $('auWSub').textContent = (S.canWorkers ? 'Anee gives out the work by who can come, and emails each one their day.' : 'Anee gives out the work by who can come.')
            + ' Tap a day to set it: a whole day, the morning only, the afternoon only, or a day off.';
        $('auWNote').hidden = S.canWorkers;
        $('auWNote').textContent = 'On your plan the helpers are kept for planning only. Worker cards, their logins and their morning emails come with Solo Farmer.';
        paintGwOpen();
        $('auWorkers').innerHTML = S.crewList.map((w, i) => '<div class="au-w" data-w="' + i + '"><div class="au-w-top"><input type="text" class="form-input" data-wf="name" value="' + esc(w.name) + '" placeholder="Name" maxlength="255">'
            + '<button type="button" class="au-x" data-wx aria-label="Remove">✕</button></div>'
            + (S.canWorkers ? '<div class="au-row is-2"><input type="email" class="form-input" data-wf="email" value="' + esc(w.email) + '" placeholder="Email, for the morning list (optional)"><input type="tel" class="form-input" data-wf="phone" value="' + esc(w.phone) + '" placeholder="Phone (optional)"></div>' : '')
            + '<div><span class="text-xs font-bold text-gray-500">Days they work</span><div class="mt-1">' + WD.html(w.off, w.parts, w.name) + '</div></div>'
            + '<label class="au-pay"><span>Pay for a whole day' + (S.options.payments ? '' : ' <small>(optional: Anee counts the labor cost)</small>') + '</span><span class="au-pay-in"><b>₱</b><input type="number" class="form-input" data-wf="pay" min="0" step="10" inputmode="decimal" value="' + (w.pay ?? '') + '" placeholder="Like 450"></span>'
            + (S.options.payments && !(Number(w.pay) > 0) ? '<small class="au-pay-ask">Anee counts their pay for payday: add their daily pay.</small>' : '') + '</label></div>').join('')
            || '<p class="text-sm text-gray-500 mb-3">No workers yet. A farm worked by the family alone is fine: Anee plans for a small crew.</p>';
    };
    // The farm's workers sheet: one worker open at a time, their details put right there, then added.
    let GW = null; // { id, name, email, phone, off, parts } of the worker open in the sheet
    const gwSay = (g) => [g.email, g.phone, WD.words(g.off, g.parts) || 'Works every day'].filter(Boolean).join(' · ');
    const gwRow = (g) => {
        const isIn = gwIn(g), open = !isIn && GW && GW.id === g.id, d = open ? GW : g;
        const say = gwSay(g);
        return '<div class="au-gw' + (isIn ? ' is-in' : '') + (open ? ' is-open' : '') + '" data-gw="' + g.id + '">'
            + '<button type="button" class="au-gw-h"' + (isIn ? ' disabled' : ' aria-expanded="' + !!open + '"') + '><span class="au-gw-i" aria-hidden="true">' + esc((g.name || '?').trim().charAt(0).toUpperCase()) + '</span>'
            + '<span class="au-gw-t"><b>' + esc(g.name) + '</b><small>' + esc(say) + '</small></span><span class="au-gw-s">' + (isIn ? '✓ In this season' : '+ Add') + '</span></button>'
            + (isIn ? '' : '<div class="au-gw-f"' + (open ? '' : ' inert') + '><div><div class="au-gw-in">'
                + '<label class="au-gw-l">Name<input type="text" class="form-input" data-gf="name" value="' + esc(d.name) + '" maxlength="255" autocomplete="off"></label>'
                + '<div class="au-row is-2"><label class="au-gw-l"><span>Email <small>(for the morning list)</small></span><input type="email" class="form-input" data-gf="email" value="' + esc(d.email || '') + '" maxlength="255" autocomplete="off" placeholder="Optional"></label>'
                + '<label class="au-gw-l">Phone<input type="tel" class="form-input" data-gf="phone" value="' + esc(d.phone || '') + '" maxlength="32" autocomplete="off" placeholder="Optional"></label></div>'
                + '<div class="au-gw-l"><span>Days they work <small>(tap a day to change it)</small></span>' + WD.html(d.off, d.parts, d.name) + '</div>'
                + '<p class="au-gw-err" hidden></p>'
                + '<div class="au-gw-acts"><button type="button" class="btn btn-white btn-sm" data-gcancel>Cancel</button><button type="button" class="btn btn-primary btn-sm" data-gadd>Add to this season</button></div>'
                + '</div></div></div>')
            + '</div>';
    };
    const paintGw = () => {
        const q = $('auGwQ').value.trim().toLowerCase();
        $('auGwRows').innerHTML = S.roster.filter((g) => !q || [g.name, g.email, g.phone].join(' ').toLowerCase().includes(q)).map(gwRow).join('')
            || '<p class="text-sm text-gray-500">Nobody by that name.</p>';
    };
    // Opens or closes a row in place, so its fold slides instead of the list being painted over.
    const gwFold = (box, open) => {
        box.classList.toggle('is-open', open);
        box.querySelector('.au-gw-h').setAttribute('aria-expanded', String(open));
        box.querySelector('.au-gw-f').toggleAttribute('inert', !open);
    };
    $('auGwOpen').addEventListener('click', () => {
        GW = null; $('auGwQ').value = '';
        paintGw();
        window.openSheet('auGwSheet');
    });
    $('auGwQ').addEventListener('input', paintGw);
    $('auGwRows').addEventListener('input', (e) => { const f = e.target.dataset.gf; if (f && GW) GW[f] = e.target.value; });
    $('auGwRows').addEventListener('keydown', (e) => { if (e.key === 'Enter' && e.target.dataset.gf) { e.preventDefault(); e.target.closest('.au-gw')?.querySelector('[data-gadd]')?.click(); } });
    $('auGwRows').addEventListener('click', async (e) => {
        const box = e.target.closest('[data-gw]'); if (!box) return;
        const g = S.roster.find((x) => String(x.id) === box.dataset.gw); if (!g || gwIn(g)) return;
        if (e.target.closest('.au-gw-h')) {
            const was = $('auGwRows').querySelector('.au-gw.is-open');
            if (was && was !== box) gwFold(was, false);
            if (GW && GW.id === g.id) { GW = null; gwFold(box, false); return; }
            GW = { id: g.id, name: g.name, email: g.email || '', phone: g.phone || '', off: (g.off || []).map(Number), parts: WD.partsOf(g.parts) };
            ['name', 'email', 'phone'].forEach((k) => { box.querySelector('[data-gf="' + k + '"]').value = GW[k]; });
            box.querySelector('[data-wd]').outerHTML = WD.html(GW.off, GW.parts, GW.name);
            box.querySelector('.au-gw-err').hidden = true;
            gwFold(box, true);
            return;
        }
        if (e.target.closest('[data-gcancel]')) { GW = null; gwFold(box, false); return; }
        const add = e.target.closest('[data-gadd]'); if (!add || !GW) return;
        const err = box.querySelector('.au-gw-err');
        const mail = box.querySelector('[data-gf="email"]');
        const name = GW.name.trim(), email = GW.email.trim(), phone = GW.phone.trim();
        err.hidden = true;
        if (!name) { err.textContent = 'Each worker needs a name.'; err.hidden = false; return; }
        if (email && !mail.checkValidity()) { err.textContent = 'That email does not look right.'; err.hidden = false; return; }
        const row = { id: null, globalWorkerId: g.id, name, email, phone, off: [...GW.off], parts: { ...GW.parts }, pay: g.pay ?? null };
        S.crewList.push(row);
        add.disabled = true;
        try { await saveWorkers(); } catch (_) {
            S.crewList.splice(S.crewList.indexOf(row), 1);
            add.disabled = false;
            return;
        }
        // Who they are and their week were put right on their card, and the server carried them to the farm's list.
        Object.assign(g, { name, email, phone, off: [...row.off], parts: { ...row.parts } });
        GW = null;
        gwFold(box, false);
        box.classList.add('is-in');
        const h = box.querySelector('.au-gw-h');
        h.disabled = true; h.removeAttribute('aria-expanded');
        box.querySelector('.au-gw-t b').textContent = name;
        box.querySelector('.au-gw-t small').textContent = gwSay(g);
        box.querySelector('.au-gw-s').textContent = '✓ In this season';
        paintWorkers();
        window.toast?.(name + ' is in this season. Check their days below.');
    });
    $('auGwRows').addEventListener('workdays:change', (e) => { if (!GW) return; const r = WD.read(e.target); GW.off = r.off; GW.parts = r.parts; });
    $('auAddWorker').addEventListener('click', () => { S.crewList.push({ id: null, name: '', email: '', phone: '', off: [0], parts: {} }); paintWorkers(); $('auWorkers').lastElementChild?.querySelector('input')?.focus(); });
    $('auWorkers').addEventListener('input', (e) => { const row = e.target.closest('[data-w]'); if (!row) return; S.crewList[Number(row.dataset.w)][e.target.dataset.wf] = e.target.value; later('workers', saveWorkers, 900); });
    $('auWorkers').addEventListener('click', (e) => {
        const row = e.target.closest('[data-w]'); if (!row) return;
        const w = S.crewList[Number(row.dataset.w)];
        if (e.target.closest('[data-wx]')) { S.crewList.splice(Number(row.dataset.w), 1); paintWorkers(); later('workers', saveWorkers, 200); return; }
    });
    $('auWorkers').addEventListener('workdays:change', (e) => {
        const row = e.target.closest('[data-w]'); if (!row) return;
        const w = S.crewList[Number(row.dataset.w)], r = WD.read(e.target);
        w.off = r.off; w.parts = r.parts;
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
    /* Where a farm lot sits on its map: its pin, or the middle of the outline
       drawn for it. A lot that has one is never asked for a pin again (the
       owner, 2026-10-28: "it asks me to drop a pin on the map even though the
       selected map has already a pin dropped and location mapped or drawn"). */
    const farmPin = (fl) => {
        const p = fl && fl.pin;
        if (p && Number.isFinite(Number(p.lat)) && Number.isFinite(Number(p.lng))) return { lat: +Number(p.lat).toFixed(7), lng: +Number(p.lng).toFixed(7) };
        const b = fl && Array.isArray(fl.boundary) && fl.boundary.length >= 3 ? fl.boundary : null;
        if (!b) return null;
        const mid = (i) => +(b.reduce((s, x) => s + Number(x[i]), 0) / b.length).toFixed(7);
        return { lat: mid(0), lng: mid(1) };
    };
    function fillFromFarm(l, fl) {
        const now = () => JSON.stringify([l.name, l.size, l.unit, l.province, l.town, l.barangay, l.pin ? [Number(l.pin.lat), Number(l.pin.lng)] : null]);
        const was = now();
        if (fl.name) l.name = String(fl.name).slice(0, 255);
        if (Number(fl.areaHa) > 0) { l.size = Number(fl.areaHa); l.unit = 'ha'; }
        if (fl.province && fl.town) { l.province = fl.province; l.town = fl.town; l.barangay = String(fl.barangay || '').slice(0, 120); }
        const at = farmPin(fl);
        if (at) l.pin = at;
        return now() !== was;
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
    const READS = [['🌾', 'Your varieties, looked up on the web: maturity, yield, weak points'], ['🌏', 'ENSO and the season ahead, with the seasonal outlook'], ['🌦️', 'Sixteen days of weather at each lot'], ['🧪', 'Your soil from its signs and the soil model at the spot'], ['🐛', 'Your past troubles, to get ahead of them']];
    // One row per item: an icon, the name and its first sentence; a tap unfolds the rest. All folded at first.
    const first = (s) => String(s || '').split(/(?<=[.!?])\s+/)[0];
    const list = (xs, plain) => (xs || []).length ? '<ul' + (plain ? ' class="is-plain"' : '') + '>' + xs.map((x) => '<li>' + x + '</li>').join('') + '</ul>' : '';
    const acc = (icon, title, teaser, body, count, warn) => body ? '<div class="au-acc"><button type="button" class="au-acc-h" aria-expanded="false"><span class="au-acc-i" aria-hidden="true">' + icon + '</span>'
        + '<span class="au-acc-t"><b>' + esc(title) + '</b>' + (teaser ? '<small>' + esc(teaser) + '</small>' : '') + '</span>'
        + (count ? '<span class="au-acc-n' + (warn ? ' is-warn' : '') + '">' + count + '</span>' : '') + CHEV + '</button>'
        + '<div class="au-acc-b"><div><div class="au-acc-in">' + body + '</div></div></div></div>' : '';
    const para = (s) => s ? '<p>' + esc(s) + '</p>' : '';
    const kv = (icon, label, value) => '<div class="au-kv"><span aria-hidden="true">' + icon + '</span><span>' + esc(label) + '</span><b' + (value ? '' : ' class="is-none"') + '>' + esc(value || 'not found') + '</b></div>';
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
            + (sea.outlook ? '<p class="au-rl-lead">' + esc(sea.outlook) + '</p>' : '')
            + '<div class="au-rows">'
            + acc('🌊', 'ENSO', first(sea.enso), para(sea.enso))
            + acc('🌧️', 'Rain', first(sea.rain), para(sea.rain))
            + acc('🌀', 'Typhoons', first(sea.typhoon), para(sea.typhoon))
            + acc('🌡️', 'Heat', first(sea.heat), para(sea.heat))
            + acc('👀', 'Watch for', first((sea.watch || [])[0]), list((sea.watch || []).map(esc)), (sea.watch || []).length)
            + '</div></div>'
            + (A.lots || []).map((r, i) => {
                const v = r.variety || {}, so = r.soil || {}, g = r.goal || {}, f = r.fertilizer || {}, risks = r.risks || [];
                return '<div class="au-rl" style="--i:' + (i + 1) + '"><h4>🌾 ' + esc(lotName(r.lotId)) + (v.name ? ' · ' + esc(v.name) : '') + '</h4>'
                    // The three goals, each with its fertilizer; the farmer picks the target (AutoGoal, counted again as answers change).
                    // How much the lot plants, open to change right here: the goals below are counted again from it.
                    + plantBox(r.lotId)
                    + '<div class="au-goals" data-goals="' + r.lotId + '">' + goalCards(r.lotId, g) + '</div>'
                    + '<div class="au-rows">'
                    + kv('⏳', 'Matures in', v.maturityDays ? v.maturityDays + ' days' : '')
                    + kv('📊', 'Usual harvest', v.averageTPerHa ? v.averageTPerHa + ' t/ha' : '')
                    + '<div data-goalsteps="' + r.lotId + '">' + goalSteps(r.lotId, g) + '</div>'
                    + acc('🧬', 'The variety', v.season ? first(v.season) : first((v.traits || [])[0]),
                        (v.by ? '<p><b>' + esc(v.by) + '</b></p>' : '') + para(v.season) + list((v.traits || []).map(esc)) + ((v.cautions || []).length ? '<h6>Careful</h6>' + list(v.cautions.map((t) => '⚠️ ' + esc(t)), true) : ''),
                        (v.cautions || []).length ? (v.cautions.length === 1 ? '1 caution' : v.cautions.length + ' cautions') : 0, true)
                    + acc('🧪', 'The soil', first(so.reading), para(so.reading)
                        + ((so.lockouts || []).length ? '<h6>Locked up in this soil</h6>' + list(so.lockouts.map((x) => '🔒 ' + esc(x)), true) : '')
                        + ((so.unlocks || []).length ? '<h6>What frees them</h6>' + list(so.unlocks.map((x) => '🔓 ' + esc(x)), true) : ''))
                    + acc('🚜', 'Land preparation', first(r.landPrep), para(r.landPrep))
                    + acc('🌱', 'The fertilizer that matters', first(f.focus), para(f.focus)
                        + ((f.critical || []).length ? '<h6>When it counts</h6>' + list(f.critical.map((c) => '<b>' + esc(c.stage) + ':</b> ' + esc(c.why))) : '')
                        + ((f.avoid || []).length ? '<h6>Keep away from</h6>' + list(f.avoid.map(esc)) : ''))
                    + acc('🛡️', 'Risks to get ahead of', risks.map((x) => x.risk).filter(Boolean).join(', '),
                        risks.map((x) => '<div class="au-risk-i"><b>' + esc(x.risk) + '</b>' + (x.prevent ? '<p>' + esc(x.prevent) + '</p>' : '') + (x.why ? '<small>' + esc(x.why) + '</small>' : '') + '</div>').join(''),
                        risks.length, true)
                    + '</div></div>';
            }).join('')
            + (A.water || A.workforce || (A.dataGaps || []).length ? '<div class="au-rl" style="--i:' + ((A.lots || []).length + 1) + '"><h4>💧 Water and hands</h4><div class="au-rows">'
                + acc('💧', 'Water', first(A.water), para(A.water))
                + acc('👥', 'Your workers', first(A.workforce), para(A.workforce))
                + acc('🔍', 'What would make it sharper', first((A.dataGaps || [])[0]), list((A.dataGaps || []).map(esc)), (A.dataGaps || []).length)
                + '</div></div>' : '')
            + '<p class="text-center text-xs text-gray-400 mb-3">Read ' + esc(A.at || '') + (A.searched ? ', with what Anee found on the web' : '') + '. ' + (S.readingsLeft > 0 ? '<button type="button" class="underline font-bold text-brand-700" id="auReread">Read it again</button> (changed an answer?)' : '') + '</p>';
        $('auReread')?.addEventListener('click', runReading);
    };
    // A lot's goals as cards: the yield, what it rests on (Anee's words when she gave them), and a table of what it
    // takes, N, P and K, then the secondary and micro elements fed for it (2026-10-28, the owner: "in higher yields add
    // the micros like say it in the table as well for nutrients"). A card is a radio: it holds a table, so not a button.
    const NUT = { N: 'N', P2O5: 'P₂O₅', K2O: 'K₂O' };
    const NUTW = { N: 'Nitrogen (N)', P2O5: 'Phosphorus (P₂O₅)', K2O: 'Potassium (K₂O)' };
    const needLine = (need) => Object.entries(NUT).map(([k, w]) => w + ' <b>' + Math.round(Number(need[k] || 0)) + '</b>').join(' · ');
    const kg = (v) => { const n = Number(v) || 0; return n >= 10 ? String(Math.round(n)) : fmt1(n); };
    // A goal's fertilizer in 50 kg bags (and kilos for the small ones), a hectare and for the lot.
    const bagsHtml = (lv, one) => {
        const rows = lv.bags || [];
        if (!rows.length) return '';
        const amt = (b, lot) => { const bags = lot ? b.bagsLot : b.bagsHa, kgs = lot ? b.kgLot : b.kgHa;
            return b.micro ? kg(kgs) + ' kg' + (bags ? ' (about ' + fmt1(bags) + (bags === 1 ? ' bag)' : ' bags)') : '') : fmt1(bags) + (bags === 1 ? ' bag' : ' bags'); };
        return '<div class="au-gc-bags"><b>In bags of 50 kg, an estimate</b>'
            + rows.map((b) => '<div><em>' + esc(amt(b, false)) + '</em><span>' + esc(b.name) + (b.grade ? ' <small>' + esc(b.grade) + '</small>' : '')
                + (one ? '' : '<i>' + esc(amt(b, true)) + ' for this lot</i>') + (b.note ? '<i>' + esc(b.note) + '</i>' : '') + '</span></div>').join('')
            + '<p>One way to meet it with fertilizers sold everywhere. On the Fertilizer step you plan with your own.</p></div>';
    };
    const goalCards = (lotId, rg) => {
        const go = S.goalOut[lotId], l = lotById(lotId);
        if (!go || !go.levels || !l) return S.goalErr ? '<p class="au-goals-wait">The goals could not be counted just now. <button type="button" class="au-link" data-goal-retry>Try again</button></p>' : '<p class="au-goals-wait">Counting the goals…</p>';
        const pick = goalOf(l).pick, words = (rg && rg.words) || {}, lotHa = Number(go.areaHa) || 1, one = Math.abs(lotHa - 1) < 0.001;
        const row = (name, ha, lot, cls) => '<tr' + (cls ? ' class="' + cls + '"' : '') + '><td>' + name + '</td><td>' + kg(ha) + '</td>' + (one ? '' : '<td>' + kg(lot) + '</td>') + '</tr>';
        return '<div class="au-goals-h"><b>Choose your goal</b><small>Each one takes its own fertilizer. The one you pick is the target Anee plans the season for.</small></div>'
            + '<div class="au-goals-g" role="radiogroup" aria-label="Your goal for ' + esc(l.name || 'this lot') + '">'
            + Object.entries(go.levels).map(([k, lv]) => '<div class="au-gc is-' + k + '" role="radio" tabindex="0" data-level="' + k + '" aria-checked="' + (pick === k) + '">'
                + '<div class="au-gc-top"><span aria-hidden="true">' + lv.icon + '</span><b>' + esc(lv.label) + '</b><em>' + fmt1(lv.t) + ' t/ha</em><i aria-hidden="true"></i></div>'
                + (one ? '' : '<div class="au-gc-lot">About ' + fmt1(lv.lotT) + ' t on this lot</div>')
                + '<p class="au-gc-say">' + esc(words[k] || lv.say) + '</p>'
                + '<table class="au-gc-t"><thead><tr><th>What it takes</th><th>kg a hectare</th>' + (one ? '' : '<th>this lot</th>') + '</tr></thead><tbody>'
                + Object.entries(NUTW).map(([n, w]) => row(w, (lv.need || {})[n], (lv.lotNeed || {})[n])).join('')
                + (lv.micros || []).map((m) => row(esc(m.name) + (m.likely ? ' <small>fixes a shortage</small>' : ''), m.need, m.lot, 'is-micro')).join('')
                + '</tbody></table>'
                // The same need in bags of the fertilizers sold everywhere, an estimate (AutoGoal::bags).
                + bagsHtml(lv, one)
                // A crop with no fertilizer figures of its own is counted as a close one (AutoGoal::PROXY): said plainly.
                + (lv.needAs ? '<p class="au-gc-as">Counted as for ' + esc(lv.needAs) + ', a close crop: this one has no fertilizer figures of its own yet.</p>' : '')
                // What its micronutrients add: left unfed, the scarcest of them would hold the harvest to what the soil gives alone.
                + (lv.without ? '<p class="au-gc-add"><span aria-hidden="true">🧪</span><span>' + (k === 'high' ? 'The complete micro protocol adds' : 'These micronutrients add') + ' about <b>' + fmt1(lv.t - lv.without) + ' t/ha</b>. Without them, about ' + fmt1(lv.without) + ' t/ha.</span></p>' : '')
                + ((lv.unfed || []).length ? '<p class="au-gc-unfed">No micronutrients: ' + esc(lv.unfed.map((x) => x.toLowerCase()).join(' and ')) + (lv.unfed.length > 1 ? ' stay' : ' stays') + ' short, so the harvest is not secured.</p>' : '')
                + '</div>').join('') + '</div>'
            // An estimate, said plainly (2026-10-28, the owner).
            + '<p class="au-goals-disc"><span aria-hidden="true">⚠️</span><span><b>These are estimates, not a promise.</b> They are counted from the variety\'s record, your planting, your soil and water, and the season\'s outlook. The real harvest still depends on the weather, pests and diseases, the seed, and how well and on time the work is done.</span></p>';
    };
    // How much the lot plants, on the Reading step: a row that opens the same inputs as the lot card.
    const PLANT_OPEN = {};
    const plantBox = (lotId) => {
        const l = lotById(lotId); if (!l || !l.crop) return '';
        const open = !!PLANT_OPEN[lotId];
        return '<div class="au-plant' + (open ? ' is-open' : '') + '"><button type="button" class="au-plant-h" data-plant-toggle="' + lotId + '" aria-expanded="' + open + '"><span aria-hidden="true">🌱</span>'
            + '<span><b>How much you plant</b><small data-plantsay="' + lotId + '">' + esc(plantLine(l)) + '</small></span><em>' + (open ? 'Done' : 'Change') + '</em></button>'
            + '<div class="au-acc-b"' + (open ? '' : ' inert') + '><div><div class="au-goal is-inline" data-goal="' + lotId + '">' + goalBlock(l) + '</div></div></div></div>';
    };
    const goalSteps = (lotId, rg) => {
        const go = S.goalOut[lotId], steps = (go && go.steps) || (rg && rg.steps) || [];
        return steps.length ? acc('📐', 'How the goals are counted', first(rg && rg.why) || 'The variety\'s best, your stand, the water and the soil',
            '<div class="au-steps">' + steps.map((s) => '<div><span aria-hidden="true">' + s[0] + '</span><span>' + esc(s[1]) + '</span><b>' + esc(s[2]) + '</b></div>').join('') + '</div>'
            + para(rg && rg.why) + '<p class="text-xs text-gray-500">Change how much you plant above: the goals are counted again.</p>') : '';
    };
    // Only the goal cards and their steps are painted again when the count comes back, so open rows stay open.
    const paintGoalCards = () => ((S.analysis || {}).lots || []).forEach((r) => {
        const box = document.querySelector('[data-goals="' + r.lotId + '"]');
        if (box) box.innerHTML = goalCards(r.lotId, r.goal || {});
        const st = document.querySelector('[data-goalsteps="' + r.lotId + '"]');
        if (st && !st.querySelector('.au-acc.is-open')) st.innerHTML = goalSteps(r.lotId, r.goal || {});
    });
    const pickGoal = (c) => {
        const l = lotById(c.closest('[data-goals]').dataset.goals); if (!l) return;
        goalOf(l).pick = c.dataset.level;
        c.parentElement.querySelectorAll('[data-level]').forEach((b) => b.setAttribute('aria-checked', String(b === c)));
        navSay();
        goalsSoon(l, 100);
    };
    $('auReading').addEventListener('click', (e) => {
        const t = e.target.closest('[data-plant-toggle]');
        if (t) {
            const id = t.dataset.plantToggle, box = t.closest('.au-plant'), open = !box.classList.contains('is-open');
            PLANT_OPEN[id] = open; box.classList.toggle('is-open', open); t.setAttribute('aria-expanded', String(open));
            box.querySelector('.au-acc-b').toggleAttribute('inert', !open); t.querySelector('em').textContent = open ? 'Done' : 'Change';
            return;
        }
        const c = e.target.closest('[data-level]'); if (c) pickGoal(c);
        if (e.target.closest('[data-goal-retry]')) { S.goalErr = false; paintGoalCards(); goalsSoon(null, 0); }
    });
    $('auReading').addEventListener('keydown', (e) => {
        const c = e.target.closest('[data-level]'); if (!c) return;
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pickGoal(c); }
        if (['ArrowDown', 'ArrowUp', 'ArrowRight', 'ArrowLeft'].includes(e.key)) {
            const all = [...c.parentElement.querySelectorAll('[data-level]')], i = all.indexOf(c) + (['ArrowDown', 'ArrowRight'].includes(e.key) ? 1 : -1);
            if (all[i]) { e.preventDefault(); all[i].focus(); }
        }
    });
    $('auReading').addEventListener('click', (e) => {
        const h = e.target.closest('.au-acc-h'); if (!h) return;
        const box = h.closest('.au-acc'), open = !box.classList.contains('is-open');
        box.classList.toggle('is-open', open);
        h.setAttribute('aria-expanded', String(open));
    });
    const runReading = async () => {
        await flush();
        window.aneeWait.show({ title: 'Anee is reading your farm…', sub: 'The varieties, the season, the soil and the water. A minute or two.', stay: 'Stay on this page while she reads. Nothing is charged: it is part of Anee Auto.',
            lines: ['Looking up your variety', 'Reading the ENSO outlook', 'Checking the weather at each lot', 'Reading the soil from its signs', 'Weighing your past troubles'] });
        try {
            let r = await window.api(U.analyze, { method: 'POST' });
            const d = r.data && r.data.status === 'ready' ? r.data : await window.aneeWait.poll({ id: ID, job: U.job, phases: window.aneeWait.phases.research, every: 3000, limit: 260 });
            S.analysis = d.analysis; S.readingsLeft = Math.max(0, (S.readingsLeft || 3) - 1); S.step = Math.max(S.step, 7);
            await window.aneeWait.done({ title: 'Done!', line: 'Here is how I read your farm.' });
            paintReading(); navSay();
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
        // The lot first, said plainly (2026-10-28, the owner: "fix the layout in all the steps like in the fertilizer lot by
        // lot, the layout is bad especially in mobile"): its goal, then the plan, then Anee's advice folded, one row each.
        let html = '<div class="au-rl" style="--i:0"><h4>' + (cropOf(l.crop)?.icon || '🌱') + ' ' + esc(l.name) + '</h4>'
            + '<p class="au-rl-lead">' + esc([cropName(l.crop).split(',')[0], l.size ? l.size + ' ' + (l.unit === 'sqm' ? 'm²' : 'ha') : ''].filter(Boolean).join(' · ')) + '</p>';
        // The goal picked on the Reading step: the fertilizer is planned for it.
        const pk = goalOf(l).pick, lv = pk && ((S.goalOut[l.id] || {}).levels || {})[pk];
        if (lv) html += '<div class="au-fgoal"><span aria-hidden="true">' + lv.icon + '</span><span><small>Your goal</small><b>' + esc(lv.label) + '</b><em>' + fmt1(lv.t) + ' t/ha · ' + needLine(lv.need) + ' kg a hectare</em></span>'
            + '<button type="button" class="au-link" data-go-goal>Change</button></div>';
        const tips = '<div class="au-rows">'
            + acc('🧑‍🌾', 'Anee\'s advice for this soil', first(fr.focus), para(fr.focus))
            + acc('👍', 'Good sources for this soil', first((fr.goodSources || [])[0]), list((fr.goodSources || []).map(esc)), (fr.goodSources || []).length)
            + acc('✋', 'Keep away from', first((fr.avoid || [])[0]), list((fr.avoid || []).map(esc)), (fr.avoid || []).length, true)
            + acc('🧪', 'Micronutrients and foliars worth adding', first((fr.micros || [])[0]), list((fr.micros || []).map(esc)), (fr.micros || []).length)
            + '</div>';
        if (!l.fert) {
            html += tips + '<div class="au-fcta"><button type="button" class="au-big" data-open-fert>🧪 Plan the fertilizer</button><p>Common grades, Yara, MAP, DAP, Philippine products, or your own label with its NPK, sulfur, calcium and trace elements.</p></div>';
        } else {
            const f = l.fert, goal = (f.goal || {}).tPerHa || (lv && lv.t) || null, reach = f.reach != null ? Number(f.reach) : null;
            const qty = (x) => { const a = Number(x.amount) || 0, u = String(x.unit || 'kg'); return fmt1(a) + ' ' + (u === 'bag' ? (a === 1 ? 'bag' : 'bags') : u); };
            if ((f.lines || []).length) html += '<h5>Your plan</h5><div class="au-flines">' + f.lines.map((x) => '<div><b>' + esc(qty(x)) + '</b><span>' + esc(x.name || '') + (x.grade ? ' <small>' + esc(x.grade) + '</small>' : '') + '</span></div>').join('') + '</div>';
            html += '<h5>What the plan gives against the need</h5><div class="au-bars">' + ['N', 'P2O5', 'K2O'].map((k) => {
                const n = (f.needs || {})[k]; if (!n) return '';
                const scale = Math.max(n.need * 1.45, n.plan * 1.05, 1);
                return '<div class="au-bar is-' + (n.state === 'right' ? 'ok' : n.state) + '"><b>' + LAB[k] + '</b><span class="au-bar-t"><span data-w="' + Math.min(100, n.plan / scale * 100) + '"></span><i style="left:' + (n.need / scale * 100) + '%"></i></span><em>' + (n.state === 'short' ? 'Short' : n.state === 'over' ? 'Too much' : 'Enough') + '</em></div>';
            }).join('') + '</div><p class="text-xs text-gray-500 mt-1">The dark mark is the need for your goal; the bar is your plan, in kg a hectare (' + esc(f.npkPerHa || '') + ').</p>';
            // Only what matters: an element short, too much or just right (an element the plan carries with no need is left out).
            const micro = Object.entries(f.micros || {}).filter(([, m]) => ['short', 'over', 'right'].includes(m.state));
            if (micro.length) html += '<h5>Secondary and micronutrients</h5><div class="au-chips">' + micro.map(([k, m]) => '<span class="au-chip' + (m.state === 'short' ? ' is-warn' : m.state === 'over' ? ' is-bad' : '') + '">' + esc(MICRO_NAME[k] || k) + ' · ' + ({ short: 'short', over: 'too much', right: 'enough' })[m.state] + '</span>').join('') + '</div>';
            if (reach != null) {
                const t = goal ? Math.round(goal * reach / 10) / 10 : null;
                html += '<div class="au-yield"><span class="au-gauge"><svg viewBox="0 0 100 100"><circle class="bg" cx="50" cy="50" r="42" pathLength="1"/><circle class="fg" cx="50" cy="50" r="42" pathLength="1" data-reach="' + Math.min(100, reach) + '"/></svg><b>' + Math.round(reach) + '%</b></span>'
                    + '<p>' + (t ? 'The plan carries about <b>' + t + ' t/ha</b> of the ' + fmt1(goal) + ' t/ha it was made for.' : 'The plan reaches <b>' + Math.round(reach) + '%</b> of the goal.') + ' The shortest plank sets the harvest; the others cannot raise it.</p></div>';
            }
            const rk = risks(f);
            html += rk.length ? '<h5>Risks from this plan</h5>' + rk.map((r, i) => '<div class="au-risk" style="animation-delay:' + i * 60 + 'ms"><span>' + r[0] + '</span><span>' + r[1] + '</span></div>').join('') : '<div class="au-note is-ok"><span>✅</span><span>Balanced for your goal. Anee times each application for the moments the crop needs it most.</span></div>';
            if (l.imbalance) html += '<div class="au-note is-info"><span>📝</span><span>You chose to go on with this plan: ' + esc(BOOK.imbalance[l.imbalanceReason] || 'noted') + '. Anee plans with it and leans on timing and foliars to make the most of it.</span></div>';
            html += '<button type="button" class="btn btn-white w-full mt-4" data-open-fert>Change the plan</button>'
                + '<h5>Anee\'s advice</h5>' + tips
                + '<p class="au-disc">These bars and the barrel are a guide, not a promise. The real need and harvest depend on the variety, how much of its own nutrients your soil gives, the weather, and how and when the fertilizer goes on. A soil test makes all of it surer.</p>';
        }
        $('auFPane').innerHTML = html + '</div>';
        requestAnimationFrame(() => {
            $('auFPane').querySelectorAll('[data-w]').forEach((s) => { s.style.width = s.dataset.w + '%'; });
            $('auFPane').querySelectorAll('[data-reach]').forEach((c) => { c.style.strokeDashoffset = String(1 - Number(c.dataset.reach) / 100); });
        });
        if (S.lots.findIndex((x) => x.id === FLOT) < S.lots.length - 1) navLabel($('auNext'), '', 'Next lot', 'next'); else navLabel($('auNext'), 'Next: ', 'Start', 'next');
    };
    const MICRO_NAME = { Ca: 'Calcium', Mg: 'Magnesium', S: 'Sulfur', Zn: 'Zinc', B: 'Boron', Fe: 'Iron', Mn: 'Manganese', Cu: 'Copper', Mo: 'Molybdenum', Si: 'Silicon', Co: 'Cobalt' };
    $('auFTabs').addEventListener('click', (e) => { const b = e.target.closest('[data-l]'); if (b) { FLOT = Number(b.dataset.l); paintFert(); } });
    $('auFPane').addEventListener('click', (e) => {
        if (e.target.closest('[data-open-fert]')) openFert(lotById(FLOT));
        if (e.target.closest('[data-go-goal]')) show(6, true);
        const h = e.target.closest('.au-acc-h');
        if (h) { const box = h.closest('.au-acc'), open = !box.classList.contains('is-open'); box.classList.toggle('is-open', open); h.setAttribute('aria-expanded', String(open)); }
    });

    /* NPK Plus, in a window of its own, prefilled from the lot and the reading. */
    const NPK_CONDS = ['acidic', 'neutral', 'alkaline', 'sodic', 'saline', 'acid_sulfate', 'low_om', 'peat', 'waterlogged'];
    const openFert = (l) => {
        const read = ((S.analysis || {}).lots || []).find((r) => r.lotId === l.id) || {};
        const sr = S.answers.soilRead || {};
        const soilTex = (read.soil || {}).texture && read.soil.texture !== 'unsure' ? read.soil.texture : (sr.texture || 'unsure');
        const conds = [...new Set([...(sr.conditions || []), ...((read.soil || {}).conditions || []), ...(((read.soil || {}).ph && read.soil.ph !== 'unsure') ? [read.soil.ph] : [])])].filter((c) => NPK_CONDS.includes(c));
        const pre = { calcId: l.calcId || null, crop: l.crop, variety: l.variety || ((read.variety || {}).name || ''), areaValue: Number(l.size) || 1, areaUnit: l.unit === 'sqm' ? 'sqm' : 'ha',
            areaHa: l.unit === 'sqm' ? (Number(l.size) || 0) / 10000 : (Number(l.size) || 1),
            // The goal as the app counts it (AutoGoal), the farmer's own target when typed, and how much they plant.
            potential: (S.goalOut[l.id] || {}).potential || (read.variety || {}).yieldPotentialTPerHa || null,
            target: (S.goalOut[l.id] || {}).tPerHa || (((S.goalOut[l.id] || {}).levels || {}).realistic || {}).t || null,
            seed: (() => {
                const g = goalOf(l);
                if (g.by === 'spacing' && g.row && g.plant) return { by: 'spacing', spacing: { row: g.row, plant: g.plant, perHill: g.perHill || null }, germination: g.germ || null };
                return g.amount ? { by: 'amount', amount: g.amount, key: g.unit || (((S.goalOut[l.id] || {}).plant || {}).units || ['kg'])[0], germination: g.germ || null } : null;
            })(),
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
            + (() => { const pk = goalOf(l).pick, lv = pk && ((S.goalOut[l.id] || {}).levels || {})[pk]; return lv ? ' · goal ' + esc(lv.label.toLowerCase()) + ', ' + fmt1(lv.t) + ' t/ha' : ''; })()
            + ' · ' + (l.fert ? (l.imbalance ? 'fertilizer plan kept as is' : 'fertilizer planned') : '<span class="text-amber-700 font-bold">no fertilizer plan yet</span>') + '</span></div>').join('')
            + '<div><b>Workers</b><span>' + (S.crewList.length ? esc(S.crewList.map((w) => w.name).filter(Boolean).join(', ')) : 'The family') + '</span></div>'
            + '<div><b>How Anee runs it</b><span>' + esc((OB.protection[S.options.protection] || {}).title || '') + ' · ' + esc(Object.entries(OB.switches).filter(([k]) => S.options[k]).map(([, w]) => w.title.toLowerCase()).join(', ') || 'no extras') + '</span></div>'
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
    // Every lot's goal, counted by the app (AutoGoal). window.api comes with app.js, a module that runs after this
    // script: until then the count waits for the page to finish loading.
    if (window.api) goalsSoon(null, 0); else document.addEventListener('DOMContentLoaded', () => goalsSoon(null, 0), { once: true });
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
