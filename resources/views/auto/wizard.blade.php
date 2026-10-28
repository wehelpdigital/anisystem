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
     variety is the shared sheet (partials/variety-pick).
     2026-10-28 (the owner): fifteen pages, the analyses before the decisions. After the lots come When to plant (each
     lot's planting window), the planting day, the varieties the farmer weighs, Anee's ranking of them (Variety
     Research's own run, AneeAutoController::varietyRank) and the variety chosen, then the workers and the rest. --}}
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
    /* The protocol, presented (2026-10-28). */
    .au-pr { display: grid; gap: .9rem; margin-top: .9rem; }
    .au-pr-wait { text-align: center; font-weight: 800; color: var(--color-gray-500); }
    .au-pr-wait span { display: inline-block; animation: auWinBob 1.2s ease-in-out infinite; }
    .au-pr-hero { display: flex; gap: .9rem; align-items: center; padding: 1.1rem; border-radius: 1.3rem; color: #eaf3df; background: radial-gradient(120% 160% at 100% 0%, #6aa33a 0%, #2d5016 50%, #10200a 100%); animation: auFade .4s var(--au-ease) both; }
    .au-pr-hero img { flex: none; width: 4.2rem; height: 4.2rem; border-radius: 1rem; background: #fff; padding: .2rem; animation: auWinBob 3s ease-in-out infinite; }
    .au-pr-hero > div > span:first-child { font-size: .68rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; color: #f5c518; }
    .au-pr-hero h3 { margin-top: .15rem; font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; color: #fff; }
    .au-pr-hero p { margin-top: .2rem; font-size: .82rem; line-height: 1.5; color: #cfe3b9; }
    .au-pr-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .6rem; }
    .au-pr-chips span { padding: .25rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: #fff; background: rgb(255 255 255 / .14); animation: auFade .35s var(--au-ease) both; animation-delay: calc(var(--k) * 70ms + .2s); }
    .au-pr-tabs { margin: 0; }
    .au-pr-strat { display: flex; gap: .6rem; align-items: flex-start; padding: .85rem 1rem; border-radius: 1rem; background: #eaf4fb; border: 1px solid #c6e0f2; color: #1e4b6b; font-size: .86rem; line-height: 1.55; }
    .au-pr-strat p { margin: 0; }
    .au-pr-c { margin: 0; }
    .au-pr-c h4 { display: flex; flex-wrap: wrap; align-items: baseline; gap: .1rem .6rem; margin: 0 0 .7rem; font-size: .95rem; font-weight: 800; color: var(--color-gray-900); }
    .au-pr-c h4 small { font-size: .74rem; font-weight: 600; color: var(--color-gray-500); }
    .au-pr-c h5 { margin: .8rem 0 .3rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-700); }
    .au-pr-c ul { margin: 0; padding-left: 1.1rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); }
    .au-pr-key { margin: .5rem 0 0; font-size: .72rem; color: var(--color-gray-500); }
    .au-pr-2 { display: grid; gap: .9rem; }
    @media (min-width: 900px) { .au-pr-2 { grid-template-columns: 1fr 1fr; } }
    .au-pg { --lab: 7.5rem; display: grid; }
    @media (max-width: 560px) { .au-pg { --lab: 2rem; } .au-pg-lab b { display: none; } }
    .au-pg-row { display: grid; grid-template-columns: var(--lab) minmax(0, 1fr); align-items: center; min-height: 1.9rem; }
    .au-pg-row + .au-pg-row { border-top: 1px solid var(--color-gray-100); }
    .au-pg-row.is-axis { border-top: 0; min-height: 1.4rem; }
    .au-pg-lab { display: flex; align-items: center; gap: .35rem; font-size: .74rem; font-weight: 800; color: var(--color-gray-700); }
    .au-pg-lab i { font-style: normal; }
    .au-pg-track { position: relative; height: 1.6rem; }
    .au-pg-st { position: absolute; top: .15rem; bottom: .15rem; border-radius: .35rem; background: var(--c); font-size: .58rem; font-weight: 800; color: #1f2a17; display: grid; place-items: center; overflow: hidden; white-space: nowrap; transform-origin: left; animation: auCrew .6s var(--au-ease) both; animation-delay: calc(var(--k) * 90ms); }
    .au-pg-dot { position: absolute; top: 50%; width: .7rem; height: .7rem; margin: -.35rem 0 0 -.35rem; border-radius: 999px; background: #4a7c2a; box-shadow: 0 0 0 2px #fff; animation: auPgPop .35s var(--au-ease) both; animation-delay: calc(.4s + var(--k) * 40ms); }
    @keyframes auPgPop { from { transform: scale(0); } }
    .au-pg-dot.is-prep { background: #b45309; } .au-pg-dot.is-plant { background: #65a30d; } .au-pg-dot.is-water { background: #0284c7; } .au-pg-dot.is-fert { background: #d4a106; }
    .au-pg-dot.is-weeds { background: #16a34a; } .au-pg-dot.is-protect { background: #db2777; } .au-pg-dot.is-scout { background: #7c3aed; } .au-pg-dot.is-harvest { background: #ea580c; }
    .au-pg-dot.is-crit { box-shadow: 0 0 0 2px #fff, 0 0 0 4px #1f2a17; }
    .au-pg-tick { position: absolute; top: .2rem; transform: translateX(-50%); font-size: .58rem; font-weight: 800; color: var(--color-gray-500); white-space: nowrap; }
    .au-pg-tick:first-child { transform: none; }
    .au-pw { display: flex; align-items: flex-end; gap: 3px; height: 7rem; }
    .au-pw-c { flex: 1 1 0; min-width: 0; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; gap: .2rem; }
    .au-pw-c i { display: block; width: 100%; height: calc(var(--h) * 85%); min-height: 3px; border-radius: 3px 3px 0 0; background: #8fc96a; transform-origin: bottom; animation: auWinBar .5s var(--au-ease) both; animation-delay: calc(var(--k) * 30ms); }
    .au-pw-c i.is-peak { background: #d97706; }
    .au-pw-c span { height: .8rem; font-size: .55rem; font-weight: 800; color: var(--color-gray-500); white-space: nowrap; }
    .au-pf { display: grid; gap: .45rem; }
    .au-pf-r { display: grid; grid-template-columns: 5.5rem minmax(0, 1fr) 3.4rem; gap: .5rem; align-items: center; animation: auFade .35s var(--au-ease) both; animation-delay: calc(var(--k) * 70ms); }
    .au-pf-l b { display: block; font-size: .76rem; font-weight: 800; color: var(--color-gray-800); }
    .au-pf-l small { display: block; font-size: .62rem; color: var(--color-gray-500); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .au-pf-b { display: flex; gap: 2px; height: 1.1rem; }
    .au-pf-b i { display: block; height: 100%; border-radius: .25rem; background: var(--c); transform-origin: left; animation: auCrew .7s var(--au-ease) both; }
    .au-pf-v { font-size: .74rem; font-weight: 800; color: var(--color-gray-700); text-align: right; }
    .au-pf-key { display: flex; flex-wrap: wrap; gap: .3rem .8rem; margin-top: .6rem; font-size: .7rem; color: var(--color-gray-600); }
    .au-pf-key span { display: inline-flex; align-items: center; gap: .3rem; }
    .au-pf-key i { width: .7rem; height: .7rem; border-radius: .2rem; background: var(--c); }
    .au-ps + .au-ps { margin-top: .4rem; }
    .au-pts { display: grid; gap: .5rem; padding: .3rem 0 .6rem; }
    .au-pt { padding: .7rem .8rem; border-radius: .9rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .au-pt.is-crit { border-color: #f3d98f; background: #fffbeb; }
    .au-pt-h { display: flex; gap: .55rem; align-items: flex-start; }
    .au-pt-i { flex: none; width: 2rem; height: 2rem; border-radius: .65rem; display: grid; place-items: center; font-size: 1rem; background: #fff; border: 1px solid var(--color-gray-200); }
    .au-pt-h b { display: block; font-size: .86rem; font-weight: 800; color: var(--color-gray-900); }
    .au-pt-h small { display: block; font-size: .7rem; color: var(--color-gray-500); }
    .au-pt p { margin: .4rem 0 0; font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); }
    .au-pt-p { display: grid; gap: .3rem; margin-top: .45rem; }
    .au-pt-p span { padding: .35rem .55rem; border-radius: .6rem; font-size: .72rem; line-height: 1.4; color: var(--color-gray-600); background: #fff; border: 1px solid var(--color-gray-200); }
    .au-pt-p span b { display: block; font-size: .78rem; color: var(--color-gray-900); }
    .au-pt-s { margin: .4rem 0 0; padding-left: 1.2rem; list-style: decimal; font-size: .76rem; line-height: 1.5; color: var(--color-gray-600); }
    .au-pt p.au-pt-y { font-size: .74rem; color: var(--color-gray-500); }
    .au-pt p.au-pt-sf { font-size: .72rem; color: #92400e; }
    .au-pt p.au-pt-mx { padding: .35rem .55rem; border-radius: .6rem; font-size: .74rem; font-weight: 700; color: #1f5f2a; background: #e9f6ea; }
    .au-pt p.au-pt-mx.is-care, .au-pt p.au-pt-mx.is-unknown { color: #6b4a00; background: #fff6dd; } .au-pt p.au-pt-mx.is-no { color: #9f1239; background: #ffe4e6; }
    .au-psp { display: grid; }
    .au-psp > div { display: grid; grid-template-columns: 6.5rem minmax(0, 1fr); gap: .6rem; padding: .45rem 0; font-size: .8rem; }
    .au-psp > div + div { border-top: 1px solid var(--color-gray-100); }
    .au-psp b { font-weight: 800; color: var(--color-gray-800); }
    .au-psp small { display: block; font-size: .72rem; color: var(--color-gray-500); }
    .au-pr-go { border-color: #f3d98f; }
    .au-pr-go .au-lock { margin-top: 0; }
    .au-easy { margin: .45rem 0 .2rem; padding: .55rem .75rem; border-radius: .85rem; font-size: .84rem; line-height: 1.45; color: #2d5016; background: #f1f8e8; border: 1px solid #d5e8bf; }
    html.dark .au-easy { color: #cfe6b8; background: #172412; border-color: #2b3a1c; }
    .au-pr-ok { display: flex; align-items: center; gap: .55rem; margin-top: .9rem; font-size: .88rem; font-weight: 800; color: var(--color-gray-800); cursor: pointer; }
    .au-pr-ok input { width: 1.2rem; height: 1.2rem; accent-color: #4a7c2a; }
    .au-pr-acts { display: flex; flex-wrap: wrap; align-items: center; gap: .8rem 1.2rem; margin-top: .9rem; }
    .au-big.is-gold { color: #3b2f00; background: #f5c518; box-shadow: 0 12px 26px -14px rgb(245 197 24 / .9); }
    .au-big[disabled] { opacity: .5; cursor: not-allowed; }
    /* The agreement: small type in a box read to its end, a hint that bounces until it is, then the tick. */
    .au-terms { position: relative; margin-top: .9rem; }
    .au-terms-box { max-height: 21rem; overflow-y: auto; overscroll-behavior: contain; padding: .9rem 1rem 1.4rem; border-radius: 1rem; font-size: .74rem; line-height: 1.6; color: var(--color-gray-600);
        background: var(--color-gray-50); border: 1px solid var(--color-gray-200); scroll-behavior: smooth; }
    .au-terms-box:focus-visible { outline: 2px solid #f5c518; outline-offset: 2px; }
    .au-terms-box h4 { font-family: var(--font-heading); font-size: .92rem; font-weight: 800; color: var(--color-gray-900); }
    .au-terms-box h5 { margin-top: .9rem; font-size: .78rem; font-weight: 800; color: var(--color-gray-800); }
    .au-terms-box p, .au-terms-box ul { margin-top: .35rem; }
    .au-terms-box ul { padding-left: 1.1rem; list-style: disc; }
    .au-terms-box li + li { margin-top: .2rem; }
    .au-terms-box a { font-weight: 700; color: var(--color-brand-700); text-decoration: underline; }
    .au-t-ver { font-size: .68rem; color: var(--color-gray-500); }
    .au-t-end { margin-top: 1rem !important; text-align: center; font-weight: 800; color: var(--color-gray-500); }
    .au-terms-hint { position: absolute; left: 50%; bottom: .7rem; translate: -50% 0; display: inline-flex; white-space: nowrap; align-items: center; gap: .35rem; padding: .4rem .85rem; border-radius: 999px;
        font-size: .76rem; font-weight: 800; color: #2b2100; background: #f5c518; box-shadow: 0 0 0 1px rgb(59 47 0 / .25), 0 10px 22px -10px rgb(0 0 0 / .55);
        transition: background-color .28s var(--au-ease), color .28s var(--au-ease), opacity .28s var(--au-ease); }
    .au-terms-hint svg { width: 1rem; height: 1rem; animation: auHint 1.3s var(--au-ease) infinite; }
    .au-terms-hint.is-read { color: #fff; background: #4a7c2a; box-shadow: 0 10px 22px -10px rgb(0 0 0 / .55); }
    .au-terms-hint.is-read svg { animation: auHintTick 1.3s var(--au-ease) infinite; }
    .au-terms-hint.is-done { opacity: 0; pointer-events: none; }
    @keyframes auHint { 50% { transform: translateY(4px); } }
    @keyframes auHintTick { 50% { transform: translateY(4px) scale(1.1); } }
    .au-terms-agree { display: flex; align-items: flex-start; gap: .6rem; margin-top: .9rem; padding: .75rem .85rem; border-radius: .95rem; font-size: .8rem; line-height: 1.5; font-weight: 700;
        color: var(--color-gray-800); background: #f6f9f1; border: 1.5px solid #cfe3b8; cursor: pointer; transition: opacity .28s var(--au-ease), border-color .28s var(--au-ease); }
    .au-terms-agree input { flex: none; width: 1.2rem; height: 1.2rem; margin-top: .1rem; accent-color: #4a7c2a; }
    .au-terms-agree a { color: var(--color-brand-700); text-decoration: underline; }
    .au-terms-agree.is-locked { opacity: .55; cursor: not-allowed; }
    .au-terms-agree:not(.is-locked) { border-color: #f5c518; animation: auAgree 1.6s var(--au-ease) 2; }
    @keyframes auAgree { 50% { box-shadow: 0 0 0 5px rgb(245 197 24 / .25); } }
    .au-terms-acts { display: flex; justify-content: flex-end; margin-top: .9rem; }
    html.dark .au-terms-box { background: #11170d; border-color: #2b3a1c; color: #b9c6ad; } html.dark .au-terms-box h4, html.dark .au-terms-box h5 { color: #eef3e8; }
    html.dark .au-terms-agree { background: #142010; border-color: #2b3a1c; color: #dbe5d0; }
    @media (prefers-reduced-motion: reduce) { .au-terms-hint svg, .au-terms-agree { animation: none !important; } .au-terms-box { scroll-behavior: auto; } }
    html.dark .au-pr-c h4, html.dark .au-pt-h b, html.dark .au-pt-p span b, html.dark .au-psp b, html.dark .au-pr-ok, html.dark .au-pf-l b { color: #eef3e8; }
    html.dark .au-pr-strat { background: #10202b; border-color: #1f3a4d; color: #9fc3dd; }
    html.dark .au-pt { background: #151c11; border-color: #232e1b; } html.dark .au-pt.is-crit { background: #2a2210; border-color: #5b4716; }
    html.dark .au-pt-i, html.dark .au-pt-p span { background: #10150c; border-color: #2b3a1c; } html.dark .au-pt p, html.dark .au-pt-p span, html.dark .au-pr-c ul { color: #b9c6ad; }
    html.dark .au-pg-row + .au-pg-row, html.dark .au-psp > div + div { border-top-color: #232e1b; } html.dark .au-pg-dot { box-shadow: 0 0 0 2px #161c12; }
    @media (prefers-reduced-motion: reduce) { .au-pr-hero, .au-pr-hero img, .au-pr-chips span, .au-pg-st, .au-pg-dot, .au-pw-c i, .au-pf-r, .au-pf-b i, .au-pr-wait span { animation: none !important; } }
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
    /* Pages 2 to 6 (2026-10-28): a card a lot, its name and crop in the head. */
    .au-pl .au-lot-h { padding: .7rem 1rem; }
    .au-pl-t { flex: 1; min-width: 0; display: grid; }
    .au-pl-t b { font-size: .98rem; font-weight: 800; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .au-pl-t small { font-size: .74rem; color: var(--color-gray-500); }
    .au-pl .au-lot-b { padding-top: .6rem; }
    .au-pl .au-win { margin-top: .1rem; }
    .au-dbest { display: flex; flex-wrap: wrap; gap: .4rem; }
    .au-dbest .au-pill small { font-weight: 800; opacity: .75; }
    .au-wb.is-on { border-color: #4a7c2a; background: #f1f7ea; }
    /* The best date, said first. */
    .au-wtop { display: flex; align-items: center; gap: .7rem; margin-top: .8rem; padding: .75rem .85rem; border-radius: 1rem; background: linear-gradient(135deg, #fff8dc, #fdf0c2); border: 1px solid #f1d98a; animation: auFade .35s var(--au-ease) both; }
    .au-wtop > span { flex: none; font-size: 1.5rem; line-height: 1; }
    .au-wtop > div { flex: 1; min-width: 0; display: grid; }
    .au-wtop small { font-size: .66rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #8a6400; }
    .au-wtop b { font-size: 1.05rem; font-weight: 800; color: #3d2f00; }
    .au-wtop em { font-style: normal; font-size: .74rem; color: #5c4a10; }
    .au-wtop .au-pill { flex: none; }
    .au-wb-best { margin-left: .3rem; padding: .05rem .4rem; border-radius: 999px; font-style: normal; font-size: .62rem; font-weight: 900; vertical-align: middle; color: #3d2f00; background: #f5c518; }
    html.dark .au-wtop { background: linear-gradient(135deg, #2a2410, #231d0b); border-color: #5b4d16; } html.dark .au-wtop small { color: #f3d27a; } html.dark .au-wtop b { color: #fff3c4; } html.dark .au-wtop em { color: #e8d79c; }
    .au-wday { margin: .6rem 0 0; font-size: .78rem; line-height: 1.45; color: var(--color-gray-600); }
    .au-wday b { color: var(--color-gray-900); }
    html.dark .au-wday { color: #b9c6ad; } html.dark .au-wday b { color: #eef3e8; }
    /* The varieties weighed: chips with a cross, a search and a typed name, the records' fits a tap away. */
    .au-vchips { display: flex; flex-wrap: wrap; gap: .4rem; min-height: 2.2rem; align-items: center; }
    .au-vchip { display: inline-flex; align-items: center; gap: .35rem; padding: .38rem .4rem .38rem .75rem; border-radius: 999px; font-size: .82rem; font-weight: 800; color: #2d5016; background: #e9f3dc; border: 1px solid #c9e0ad;
        animation: auFade .3s var(--au-ease) both; animation-delay: calc(var(--k) * 40ms); }
    .au-vchip button { width: 1.35rem; height: 1.35rem; border-radius: 999px; display: grid; place-items: center; font-size: .66rem; color: #2d5016; background: rgb(255 255 255 / .7); transition: background-color .28s var(--au-ease); }
    .au-vchip button:hover { background: #fff; }
    .au-vnone { margin: 0; font-size: .8rem; line-height: 1.45; color: var(--color-gray-500); }
    .au-pl .au-vchips { margin-top: .6rem; }
    /* What matters, in order: each row moves up or down, the weight it carries beside it. */
    .au-order { display: grid; gap: .4rem; margin: .2rem 0 0; padding: 0; list-style: none; }
    .au-or { display: flex; align-items: center; gap: .6rem; padding: .55rem .6rem; border-radius: .9rem; background: #fff; border: 1px solid #e3ecd8; transition: border-color .28s var(--au-ease), background-color .28s var(--au-ease); }
    .au-or:first-child { border-color: #9cc97a; background: #f6faf0; }
    .au-or.is-moved { animation: auOrMove .4s var(--au-ease); }
    @keyframes auOrMove { 0% { transform: scale(.98); background: #fff8dc; } 100% { transform: none; } }
    .au-or-n { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center; font-size: .8rem; font-weight: 900; color: #3d5226; background: #eef4e6; }
    .au-or:first-child .au-or-n { color: #fff; background: #4a7c2a; }
    .au-or-t { flex: 1; min-width: 0; display: grid; }
    .au-or-t b { font-size: .86rem; font-weight: 800; color: var(--color-gray-900); }
    .au-or-t small { font-size: .7rem; line-height: 1.35; color: var(--color-gray-500); }
    .au-or em { flex: none; font-style: normal; font-size: .7rem; font-weight: 900; color: #3d5226; }
    .au-or-b { flex: none; display: flex; gap: .25rem; }
    .au-or-b button { width: 2rem; height: 2rem; border-radius: .6rem; display: grid; place-items: center; color: #3d5226; background: #eef4e6; transition: background-color .28s var(--au-ease), opacity .28s var(--au-ease); }
    .au-or-b button:hover:not(:disabled) { background: #dcebc8; }
    .au-or-b button:disabled { opacity: .3; cursor: default; }
    .au-or-b svg { width: 1rem; height: 1rem; }
    html.dark .au-or { background: #10150c; border-color: #2b3a1c; } html.dark .au-or:first-child { background: #1c2914; border-color: #6b9f3d; } html.dark .au-or-t b { color: #eef3e8; }
    html.dark .au-or-n, html.dark .au-or-b button { background: #1c2616; color: #cfe6b8; } html.dark .au-or em { color: #cfe6b8; }
    /* Anee's ranking: her top pick, the five best as cards with their overall score, the rest folded. */
    .au-rk-top { display: flex; gap: .7rem; align-items: flex-start; padding: .85rem .9rem; border-radius: 1rem; background: linear-gradient(135deg, #fff8dc, #fdf0c2); border: 1px solid #f1d98a; }
    .au-rk-top > span { flex: none; font-size: 1.6rem; line-height: 1; }
    .au-rk-top small { display: block; font-size: .68rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #8a6400; }
    .au-rk-top b { display: block; margin-top: .1rem; font-size: 1.02rem; font-weight: 800; color: #3d2f00; }
    .au-rk-top p { margin: .25rem 0 0; font-size: .78rem; line-height: 1.5; color: #5c4a10; }
    .au-rk-by { margin: 0 0 .6rem; font-size: .74rem; line-height: 1.45; color: var(--color-gray-500); }
    .au-rk-by b { color: #3d5226; } html.dark .au-rk-by b { color: #cfe6b8; }
    .au-rk-lead { margin: .7rem 0 0; font-size: .86rem; font-weight: 700; line-height: 1.45; color: var(--color-gray-800); }
    .au-rk-bar { display: block; height: .38rem; border-radius: 999px; background: #eef4e6; overflow: hidden; }
    .au-rk-bar i { display: block; height: 100%; width: var(--w); border-radius: inherit; background: linear-gradient(90deg, #8fc96a, #4a7c2a); transform-origin: left; animation: auRkBar .8s var(--au-ease) both; animation-delay: calc(.15s + var(--k, 0) * 60ms); }
    @keyframes auRkBar { from { transform: scaleX(0); } }
    .au-vp-c span.is-mine { color: #8a6400; background: #fff4d6; }
    .au-rk-sum { margin: .8rem 0 0; font-size: .8rem; line-height: 1.55; color: var(--color-gray-700); }
    .au-rk-foot { margin: .5rem 0 0; font-size: .7rem; color: var(--color-gray-500); }
    .au-rk-acts { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin-top: .5rem; }
    .au-pl .au-rows { margin-top: .8rem; }
    .au-rk-rows { display: grid; gap: .55rem; }
    .au-rk-row { display: flex; gap: .6rem; align-items: flex-start; }
    .au-rk-row > span:nth-child(2) { flex: 1; min-width: 0; display: grid; gap: .1rem; }
    .au-rk-row b { font-size: .84rem; font-weight: 800; color: var(--color-gray-900); }
    .au-rk-row small { font-size: .72rem; color: var(--color-gray-500); }
    .au-rk-row em { font-style: normal; font-size: .74rem; line-height: 1.45; color: var(--color-gray-600); }
    html.dark .au-rk-row b { color: #eef3e8; } html.dark .au-rk-row em { color: #b9c6ad; }
    /* The pick: a radio card a ranked variety. */
    .au-pks { display: grid; gap: .45rem; margin-bottom: .4rem; }
    .au-pk { display: flex; align-items: center; gap: .65rem; width: 100%; padding: .65rem .75rem; border-radius: .95rem; text-align: left; background: #fff; border: 1.5px solid #e3ecd8;
        transition: border-color .28s var(--au-ease), background-color .28s var(--au-ease), box-shadow .28s var(--au-ease); animation: auFade .3s var(--au-ease) both; animation-delay: calc(var(--k) * 45ms); }
    .au-pk:hover { border-color: #9cc97a; }
    .au-pk.is-on { border-color: #4a7c2a; background: #f1f7ea; box-shadow: 0 10px 22px -18px rgb(45 80 22 / .8); }
    .au-pk-dot { flex: none; width: 1.15rem; height: 1.15rem; border-radius: 999px; border: 2px solid #b6cf9a; display: grid; place-items: center; transition: border-color .28s var(--au-ease); }
    .au-pk-dot::after { content: ''; width: .55rem; height: .55rem; border-radius: 999px; background: #4a7c2a; transform: scale(0); transition: transform .28s var(--au-ease); }
    .au-pk.is-on .au-pk-dot { border-color: #4a7c2a; } .au-pk.is-on .au-pk-dot::after { transform: scale(1); }
    .au-pk-t { flex: 1; min-width: 0; display: grid; }
    .au-pk-t b { font-size: .86rem; font-weight: 800; color: var(--color-gray-900); }
    .au-pk-t small { font-size: .72rem; color: var(--color-gray-500); }
    html.dark .au-pl-t b, html.dark .au-pk-t b, html.dark .au-rk-lead { color: #eef3e8; } html.dark .au-pl-t small, html.dark .au-pk-t small, html.dark .au-rk-foot { color: #9aa88d; }
    html.dark .au-vchip { color: #d6ecc0; background: #1c2914; border-color: #3f5a2a; } html.dark .au-vchip button { color: #d6ecc0; background: rgb(255 255 255 / .08); }
    html.dark .au-rk-top { background: linear-gradient(135deg, #2a2410, #231d0b); border-color: #5b4d16; } html.dark .au-rk-top small { color: #f3d27a; } html.dark .au-rk-top b { color: #fff3c4; } html.dark .au-rk-top p { color: #e8d79c; }
    html.dark .au-rk-bar { background: #1c2616; } html.dark .au-rk-sum { color: #c9d6bc; } html.dark .au-vp-c span.is-mine { color: #f3d27a; background: #2a2210; }
    html.dark .au-pk { background: #10150c; border-color: #2b3a1c; } html.dark .au-pk.is-on, html.dark .au-wb.is-on { background: #1c2914; border-color: #6b9f3d; } html.dark .au-pk-dot { border-color: #3f5a2a; }
    @media (prefers-reduced-motion: reduce) { .au-vchip, .au-rk-bar i, .au-pk, .au-or { animation: none !important; } .au-pk, .au-pk-dot, .au-pk-dot::after { transition: none !important; } }
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
            <div class="min-w-0"><h2 id="auTitleTop">{{ $schedule->title }}</h2><p id="auStepSay">Step 1 of 15</p></div>
            <span class="au-saved" id="auSaved">Saved</span>
        </div>
        <div class="au-rail" id="auRail"></div>
    </div>

    {{-- 0. The season --}}
    <section class="au-step" data-step="0">
        <div class="au-card">
            <h3 class="au-h">Your season</h3>
            <p class="au-easy">🧑‍🌾 <b>You will not have to figure out what to do each day. Anee tells you, you just follow.</b></p>
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
            <button type="button" aria-expanded="false"><span aria-hidden="true">💡</span>Not sure which crop to plant?{!! $chev !!}</button>
            <div class="au-fold-in"><div>
                <p>The planting day and the variety come next: Anee reads them for you on the pages after this one. For the crop itself, What to Plant answers first. Open it in a new tab, then come back: everything here is saved.</p>
                <div class="au-tools">
                    <a href="{{ route('whatp.page') }}" target="_blank" rel="noopener"><b>🌱 What to Plant</b>The crops your ground and season favour</a>
                </div>
            </div></div>
        </div>
        <div id="auLots"></div>
        <button type="button" class="au-add" id="auAddLot">+ Add another lot</button>
    </section>

    {{-- 2 to 6: the analyses come before the decisions, page by page (2026-10-28, the owner: "inside anee auto setup, the
         analysis about the variety and time to plant should happen before the planting day and the variety selection.
         also do it like page by page in the wizard like first the when to plant analysis based on the crop and lot
         selected ... then next page is variety analysis, then put the varieties as well you think you will plant. then
         anee analyzes it when you click continue ... then clicking next will have the selection of what crop variety to
         plant"). Each page holds a card a lot. --}}
    {{-- 2. When to plant --}}
    <section class="au-step" data-step="2">
        <div class="au-card">
            <h3 class="au-h">When to plant</h3>
            <p class="au-sub">Anee reads each lot's next four months: twenty years of its weather, storms and floods, the land preparation your farm can do, and the price at harvest, then names the best date. Look it over: you choose the day on the next page.</p>
        </div>
        <div id="auWhen"></div>
    </section>

    {{-- 3. The planting day --}}
    <section class="au-step" data-step="3">
        <div class="au-card">
            <h3 class="au-h">Your planting day</h3>
            <p class="au-sub">Pick the day each lot goes into the field. Anee's best weeks are a tap away, and the harvest day follows the day you pick.</p>
        </div>
        <div id="auDay"></div>
    </section>

    {{-- 4. The varieties the farmer is weighing --}}
    <section class="au-step" data-step="4">
        <div class="au-card">
            <h3 class="au-h">The varieties you are thinking of</h3>
            <p class="au-sub">Choose the ones you would plant, or leave it empty. Anee then ranks them against the best released for your place and planting day, the same research as Variety Ranking.</p>
            <span class="au-lab">Rank what matters to you <small>(first is what matters most)</small></span>
            <ol class="au-order" id="auOrder" aria-label="What matters, in order"></ol>
        </div>
        <div id="auVars"></div>
    </section>

    {{-- 5. Anee's ranking --}}
    <section class="au-step" data-step="5">
        <div id="auRank"></div>
    </section>

    {{-- 6. The variety --}}
    <section class="au-step" data-step="6">
        <div class="au-card">
            <h3 class="au-h">Choose your variety</h3>
            <p class="au-sub">Anee's top pick is chosen for you. Tap another to change it, or name your own. Then say how much you plant.</p>
        </div>
        <div id="auPickV"></div>
    </section>

    {{-- 7. The workers --}}
    <section class="au-step" data-step="7">
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

    {{-- 8. The soil. The field in detail that used to open this step is the
         farm's lots now (2026-10-16, the owner): a lot picked on step 1 brings
         its soil, water and last season, its answers tick the signs, and what
         it answered is hidden (data-q) under a line that says where it came
         from. A step whose questions are all answered is skipped. --}}
    <section class="au-step" data-step="8">
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

    {{-- 9. The water --}}
    <section class="au-step" data-step="9">
        <div class="au-card">
            <h3 class="au-h">Where your water really comes from</h3>
            <p class="au-sub">Pick the one closest to how it goes, not how it should go. Anee times the water and the fertilizer by it.</p>
            <div class="au-signs" id="auWater"></div>
            <label class="au-lab" for="auWaterNote">Anything more about the water <small>(optional)</small></label>
            <textarea id="auWaterNote" class="form-textarea w-full" rows="2" maxlength="400" placeholder="Like: the canal dries up in March; the pump is shared with two neighbours"></textarea>
        </div>
    </section>

    {{-- 10. The troubles --}}
    <section class="au-step" data-step="10">
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

    {{-- 11. Anee reads the farm --}}
    <section class="au-step" data-step="11">
        <div id="auReading"></div>
    </section>

    {{-- 12. The fertilizer --}}
    <section class="au-step" data-step="12">
        <div class="au-card">
            <h3 class="au-h">The fertilizer, lot by lot</h3>
            <p class="au-sub">Add the granular fertilizers you plan to use, and any foliar or micronutrients, in bags of 25 or 50 kg or in kilos. Anee shows what they give against what the crop needs on your soil, and plans the timing herself.</p>
            <div class="au-ltabs" id="auFTabs"></div>
            <div id="auFPane"></div>
        </div>
    </section>

    {{-- 13. The protocol --}}
    <section class="au-step" data-step="13">
        <div class="au-card">
            <h3 class="au-h">Your protocol</h3>
            <p class="au-sub" id="auStartSub">Anee reads everything again and writes the whole season: land preparation, water, every fertilizer at its moment, the protection you chose, harvest. You read it here and approve it before the season starts.</p>
            <div class="au-review" id="auReview"></div>
            <div class="text-center mt-5"><button type="button" class="au-big" id="auGenerate">✨ Write my protocol</button></div>
        </div>
        {{-- The protocol Anee wrote, presented before the farmer approves it (2026-10-28): the season at a glance, the work
             a week, the fertilizer at each application, every step with what to apply, the sprays and their safety. --}}
        <div id="auProto"></div>
    </section>

    {{-- 14. The agreement --}}
    <section class="au-step" data-step="14">
        <div class="au-card">
            <h3 class="au-h">Before Anee takes over</h3>
            <p class="au-sub">One last thing. Anee Auto is your guide, and you stay in charge of your farm. Read the agreement to the end, tick the box, and Anee starts your season.</p>
            <div class="au-terms">
                <div class="au-terms-box" id="auTermsBox" tabindex="0" role="document" aria-label="The Anee Auto agreement">@include('auto.partials.terms')</div>
                <button type="button" class="au-terms-hint" id="auTermsHint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-6-6l6 6 6-6"/></svg><span>Scroll down to read it all</span></button>
            </div>
            <label class="au-terms-agree is-locked" id="auTermsAgree" for="auTermsOk">
                <input type="checkbox" id="auTermsOk" disabled>
                <span>I have read the Anee Auto agreement to the end. I understand that Anee is a guide, that I can override her at any time, and that anee.io is not responsible for what happens on my farm. I agree to it, to the <a href="{{ url('/legal/eula') }}" target="_blank" rel="noopener">End User License Agreement</a> and to the <a href="{{ url('/legal/terms') }}" target="_blank" rel="noopener">Terms of Service</a>.</span>
            </label>
            <div class="au-terms-acts"><button type="button" class="btn btn-cta is-go" id="auStart" disabled>Agree and start Anee Auto</button></div>
        </div>
    </section>

    <div class="au-nav">
        <button type="button" class="btn btn-white" id="auBack">Back</button>
        <small id="auNavSay"></small>
        <button type="button" class="btn btn-cta" id="auNext">Next</button>
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
        <button type="button" class="btn btn-cta" data-sheet-close>Done</button>
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
        <button type="button" class="btn btn-cta" id="auPinSave">Save the pin</button>
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
        window: @json(route('auto.window', $auto->id)), varieties: @json(route('auto.varieties', $auto->id)), crew: @json(route('auto.crew', $auto->id)), rank: @json(route('auto.rank', $auto->id)),
        protocol: @json(route('auto.protocol', $auto->id)), approve: @json(route('auto.approve', $auto->id)),
        npk: @json(route('npk.page')) + '?frame=auto', buy: @json(route('ai.credits') . '?tab=buy'),
    };
    // The last page is the agreement (2026-10-28): read to the end and ticked before the season starts. The planting
    // window, the day, the varieties weighed, Anee's ranking and the pick come after the lots (the same day, the owner:
    // the analyses before the decisions). AneeAutoController::STEPS keeps the same order.
    const STEPS = ['Season', 'Lots', 'When to plant', 'Planting day', 'Varieties', 'Ranking', 'Your variety', 'Workers', 'Soil', 'Water', 'Troubles', 'Reading', 'Fertilizer', 'Protocol', 'Agreement'];
    const ST = { season: 0, lots: 1, when: 2, day: 3, vars: 4, rank: 5, pick: 6, workers: 7, soil: 8, water: 9, troubles: 10, reading: 11, fert: 12, proto: 13, agree: 14 };
    const AGREE = @json(\App\Support\Legal::AUTO_VERSION);
    // The protocol read and ticked; the agreement read to its end (the last two pages).
    let PROTO_OK = false, READ = false;
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
    // A step whose every question the lot answered is skipped, and so is its dot. Standing trees have no planting day
    // and no variety to weigh: a season of trees only skips those pages.
    const plantLots = () => S.lots.filter((l) => l.method !== 'tree');
    const stepHidden = (n) => ([ST.when, ST.day, ST.vars, ST.rank].includes(n) && !plantLots().length) || (n === ST.soil && HID.signs && HID.ph && HID.tested) || (n === ST.water && HID.water);
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
        const read = step === ST.reading && !S.analysis;
        $('auNext').classList.toggle('is-read', read);
        if (read) navLabel($('auNext'), '', '🔎 Read the farm', null);
        else if (step !== ST.fert) navLabel($('auNext'), 'Next: ', step === ST.troubles ? 'Anee reads it' : (step === ST.vars ? 'Anee ranks them' : STEPS[nextOf(step)]), 'next');
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
        if (step === ST.fert) { const at = S.lots.findIndex((x) => x.id === FLOT); if (at >= 0 && at < S.lots.length - 1) { FLOT = S.lots[at + 1].id; paintFert(); window.scrollTo({ top: 0, behavior: still() ? 'auto' : 'smooth' }); return; } }
        if (step === ST.reading && !S.analysis) return runReading();
        const gap = gapOf(step);
        if (gap) return window.toast?.(gap, 'error');
        // The varieties named: Anee ranks them before the next page (only what changed is ranked again).
        if (step === ST.vars && !ranksFresh()) return runRank();
        show(nextOf(step));
    });
    const gapOf = (n) => {
        if (n === ST.season && !$('auTitle').value.trim()) return 'Give the season a name.';
        if (n === ST.lots) { for (const l of S.lots) { const g = lotGap(l); if (g) return (l.name || 'A lot') + ': ' + g; } }
        if (n === ST.day) { for (const l of plantLots()) { const g = dayGap(l); if (g) return (l.name || 'A lot') + ': ' + g; } }
        if (n === ST.rank) { const l = plantLots().find((x) => !S.ranks[x.id] && !NORANK[x.id]); if (l) return 'Anee has not ranked the varieties of ' + (l.name || 'a lot') + ' yet: rank them again, or choose without a ranking.'; }
        if (n === ST.pick) { const l = plantLots().find((x) => !x.variety); if (l) return 'Choose the variety for ' + (l.name || 'each lot') + '.'; }
        if (n === ST.water && !S.answers.water) return 'Pick where the water comes from.';
        if (n === ST.reading && !S.analysis) return 'Let Anee read the farm first.';
        if (n === ST.proto && !S.protocolDraft) return 'Let Anee write your protocol first.';
        if (n === ST.proto && !PROTO_OK) return 'Read the protocol and tick that you are ready.';
        // The target is the farmer's pick, one for each lot, before the fertilizer is planned for it.
        if (n === ST.reading) { const l = S.lots.find((x) => !goalOf(x).pick); if (l) return 'Choose a goal for ' + (l.name || 'each lot') + ': it sets the fertilizer.'; }
        return null;
    };
    const paintStep = (n) => {
        if (n === ST.lots) paintLots();
        if (n === ST.when) paintWhen();
        if (n === ST.day) paintDay();
        if (n === ST.vars) paintVars();
        if (n === ST.rank) paintRank();
        if (n === ST.pick) paintPickV();
        if (n === ST.workers) { paintWorkers(); loadCrew(); }
        if (n === ST.soil) { paintSoil(); paintFrom(); }
        if (n === ST.water) paintWater();
        if (n === ST.troubles) { paintProblems(); paintFrom(); }
        if (n === ST.reading) paintReading();
        // A box short enough to need no scroll counts as read at once.
        if (n === ST.agree) requestAnimationFrame(() => termsEdge());
        if (n === ST.fert) paintFert();
        if (n === ST.proto) { paintReview(); paintStart(); }
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
        if (!(Number(l.size) > 0)) return 'say how big it is.';
        if (!l.pin && !(l.province && l.town)) return LINK[l.id] ? 'drop a pin, or give your lot its town with Edit the lot.' : 'pick its province and town, or drop a pin.';
        return null;
    };
    const saveLot = (l, extra = {}) => put(U.lot, Object.assign({ lotId: l.id, name: l.name, size: l.size, unit: l.unit || 'ha', crop: l.crop, variety: l.variety || '', method: l.method,
        fieldDate: l.method === 'tree' ? null : l.fieldDate, treeMonths: l.method === 'tree' ? l.treeMonths : null, province: l.province, town: l.town, barangay: l.barangay,
        lat: l.pin ? l.pin.lat : null, lng: l.pin ? l.pin.lng : null }, extra)).then((r) => { if (r && r.data) { l.sowDate = r.data.sowDate; l.method = r.data.method || l.method; } paintLotNotes(l); paintLotState(l); goalsSoon(null, 900); if (WIN[l.id] && WIN[l.id].key !== winKey(l)) loadWin(l); return r; });
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
            + '<div data-lacking></div>'
            + (fv ? '' : '<div class="au-row is-2"><div><span class="au-lab">Province</span><button type="button" class="au-tag' + (l.province ? '' : ' is-none') + '" data-act="prov"><span>' + esc(l.province || 'Choose the province') + '</span>' + CHEV + '</button></div>'
                + '<div><span class="au-lab">Town or city</span><button type="button" class="au-tag' + (l.town ? '' : ' is-none') + '" data-act="town"' + (l.province ? '' : ' disabled') + '><span>' + esc(l.town ? townName(l.town) : (l.province ? 'Choose the town' : 'Province first')) + '</span>' + CHEV + '</button></div></div>'
                + '<label class="au-lab">Barangay <small>(optional)</small></label><input type="text" class="form-input w-full" data-f="barangay" maxlength="120" value="' + esc(l.barangay || '') + '">')
            + (mapped ? '' : '<span class="au-lab">Pin on the map <small>(optional)</small></span><button type="button" class="au-pin' + (l.pin ? ' is-set' : '') + '" data-act="pin"><span aria-hidden="true">📍</span><span>' + (l.pin ? 'Pinned at ' + Number(l.pin.lat).toFixed(4) + ', ' + Number(l.pin.lng).toFixed(4) + '<small>Tap to move it</small>' : 'Drop a pin on the lot<small>Optional: the weather and the soil are then read at the exact spot</small>') + '</span></button>')
            // Standing trees: their variety is the one in the ground, asked here (the others are weighed on the pages after).
            + (l.method === 'tree' ? '<span class="au-lab">Variety of the trees <small>(optional, but it sharpens everything)</small></span><div data-var="' + l.id + '">' + varTag(l) + '</div>' : '')
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
        catch (err) { S.goalErr = true; if (step === ST.reading && S.analysis) paintGoalCards(); throw err; }
        const before = JSON.stringify(Object.fromEntries(Object.entries(S.goalOut).map(([k, o]) => [k, [o.plant, o.method]])));
        S.goalOut = (r.data && r.data.goals) || {};
        // The planting asked changes with the crop and its way in: then the block is painted again.
        const after = JSON.stringify(Object.fromEntries(Object.entries(S.goalOut).map(([k, o]) => [k, [o.plant, o.method]])));
        S.lots.forEach((x) => { if (before !== after) paintGoal(x); else paintGoalOut(x); });
        if (step === ST.reading && S.analysis) paintGoalCards();
        if (step === ST.reading) navSay();
        // The goal shows on the Fertilizer and Start steps too.
        if (step === ST.fert) paintFert();
        if (step === ST.proto) paintReview();
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
        // The planting window is fetched now, so the next page opens ready.
        S.lots.forEach((l) => { paintLacking(l); paintLotState(l); loadWin(l); });
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
        paintWin(l); paintLotState(l);
        if (step === ST.day) paintDayLot(l);
    };
    const wCls = (v) => v >= 75 ? 'is-good' : (v >= 60 ? 'is-ok' : (v >= 40 ? 'is-poor' : 'is-bad'));
    const wDay = (iso, y) => { const [yy, m, d] = iso.split('-').map(Number); return new Date(yy, m - 1, d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: y ? 'numeric' : undefined }); };
    const wPct = (rel) => { const d = Math.round((Number(rel) - 1) * 100); return d === 0 ? 'at a usual price' : (d > 0 ? d + '% above' : Math.abs(d) + '% below') + ' a usual harvest month'; };
    const winHtml = (l) => {
        if (l.method === 'tree') return '';
        const st = WIN[l.id];
        if (!winReady(l)) return '<div class="au-win-h"><span aria-hidden="true">📅</span><div><b>When to plant</b><small>Choose the crop and where the lot is, and Anee reads its best weeks for the year ahead.</small></div></div>';
        if (!st || st.loading) return '<div class="au-win-h"><span aria-hidden="true" class="au-win-spin">📅</span><div><b>When to plant</b><small>Anee is reading twenty years of this field\'s weather…</small></div></div><div class="au-wchart is-wait">' + '<i></i>'.repeat(18) + '</div>';
        const w = st.window;
        if (!w) return '<div class="au-win-h"><span aria-hidden="true">📅</span><div><b>When to plant</b><small>' + (st.failed ? 'It did not load. ' : 'This place has no weather record yet, so pick the day you know works. ') + '<button type="button" class="au-link" data-wretry>Try again</button></small></div></div>';
        const by = WMODE === 'price' && w.market ? 'priceScore' : 'score';
        const best = by === 'priceScore' ? w.bestPrice : w.best;
        let months = '', last = -1, lastAt = -9;
        // A month's name where it begins, never on top of the one before (the first week may be the end of a month).
        w.weeks.forEach((x, i) => { const m = Number(x.date.slice(5, 7)); if (m !== last) { if (i - lastAt >= 3) { months += '<span style="left:' + (i / w.weeks.length * 100).toFixed(2) + '%">' + new Date(2000, m - 1, 1).toLocaleDateString(undefined, { month: 'short' }) + '</span>'; lastAt = i; } last = m; } });
        const on = weekOf(l, w);
        // The best date said first, then the three best weeks from the nearest to the farthest (2026-10-28, the owner:
        // "the dates should be the nearest then the farthest arrangement ... the analysis should show the best date").
        const top = best[0], byDate = [...best].sort((a, b) => a - b), tx = top != null ? w.weeks[top] : null;
        return '<div class="au-win-h"><span aria-hidden="true">📅</span><div><b>When to plant</b><small>' + esc(w.place || 'This field') + ': ' + w.years + ' years of its weather and storms, read for ' + esc(cropName(l.crop).split(',')[0].toLowerCase()) + ', for the next four months. ' + esc(w.waterSays || '') + '</small></div></div>'
            + (tx ? '<div class="au-wtop"><span aria-hidden="true">⭐</span><div><small>The best date to plant' + (by === 'priceScore' ? ', with the price' : '') + '</small><b>' + esc(fmtDate(tx.date)) + '</b><em>' + (tx[by] ?? tx.score) + '/100 · harvest about ' + esc(wDay(tx.harvest, true)) + '</em></div>'
                + '<button type="button" class="au-pill" data-wuse="' + tx.date + '" aria-pressed="' + (top === on) + '">' + (top === on ? '✓ Your day' : 'Use this date') + '</button></div>' : '')
            + (w.market ? '<div class="au-wsw" role="tablist" aria-label="How the weeks are ranked"><i aria-hidden="true"' + (by === 'priceScore' ? ' class="is-price"' : '') + '></i><button type="button" role="tab" data-wmode="crop" aria-selected="' + (by === 'score') + '">🌦️ For the crop</button><button type="button" role="tab" data-wmode="price" aria-selected="' + (by === 'priceScore') + '">💰 With the harvest price</button></div>' : '')
            + '<div class="au-wchart' + (by === 'priceScore' ? ' is-price' : '') + '" role="img" aria-label="' + esc('Planting score by week for the next ' + w.weeks.length + ' weeks, best ' + (best.map((i) => wDay(w.weeks[i].date) + ' ' + w.weeks[i][by]).join(', '))) + '">'
            + w.weeks.map((x, i) => { const v = x[by] ?? x.score; return '<i class="' + wCls(v) + (x.prepShort ? ' is-prep' : '') + (i === on ? ' is-on' : '') + (best.includes(i) ? ' is-best' : '') + '" style="--h:' + Math.max(0.04, v / 100).toFixed(2) + ';--k:' + i + '" data-wk="' + i + '" title="' + esc(wDay(x.date, true) + ': ' + v + '/100 · harvest ' + wDay(x.harvest) + (x.prepShort ? ' · before the land preparation can finish' : '')) + '"></i>'; }).join('')
            + '</div><div class="au-wmonths" aria-hidden="true">' + months + '</div>'
            + (w.prepNeed ? '<p class="au-wkey"><i class="is-prep"></i>' + (st.prep && st.prep.on ? 'Faded weeks come before your land preparation can finish (' + esc(st.prep.why) + ', ready ' + wDay(w.earliest) + ').' : 'Faded weeks come before the field can be ready (' + esc(st.prep.why) + ').') + '</p>' : '')
            + '<div class="au-wbest">' + byDate.map((i, n) => { const x = w.weeks[i]; return '<div class="au-wb' + (i === top ? ' is-top' : '') + (i === on ? ' is-on' : '') + '" style="--k:' + n + '"><span class="au-wb-n">' + (i === top ? '⭐' : (n + 1)) + '</span><div><b>' + esc(wDay(x.date, true)) + (i === top ? ' <i class="au-wb-best">Best</i>' : '') + '</b>'
                + '<small>' + (by === 'priceScore' ? (x.priceScore ?? x.score) + '/100 with the price · ' + x.score + ' for the crop' : x.score + '/100') + ' · harvest about ' + esc(wDay(x.harvest)) + '</small>'
                + (by === 'priceScore' && x.rel ? '<small>💰 Sells ' + esc(wPct(x.rel)) + (x.p ? ', about ₱' + Number(x.p).toFixed(2) + (x.unit === 'kg' ? '/kg' : '') : '') + '</small>' : '')
                + '<small class="au-wb-why">' + esc(x.why && x.why.length ? 'Watch: ' + x.why.join('; ') : 'Few troubles in ' + w.years + ' years') + '</small></div>'
                + '<button type="button" class="au-link" data-wuse="' + x.date + '">' + (i === on ? '✓ Your week' : 'Choose this week') + '</button></div>'; }).join('') + '</div>'
            + (on >= 0 ? '<p class="au-wday">📅 Your planting day: <b>' + esc(fmtDate(l.fieldDate)) + '</b>. Change it on the next page.</p>' : '');
    };
    // The week of the 52 that a lot's planting day falls in, or -1.
    const weekOf = (l, w) => l.fieldDate ? w.weeks.findIndex((x, i) => l.fieldDate >= addDays(x.date, -3) && (i === w.weeks.length - 1 ? l.fieldDate < addDays(x.date, 4) : l.fieldDate < addDays(w.weeks[i + 1].date, -3))) : -1;
    // The day picked: its harvest, how it scores, and the land preparation before it.
    const winPickHtml = (l) => {
        const st = WIN[l.id], w = st && st.window, c = cropOf(l.crop);
        if (!l.fieldDate || l.method === 'tree') return '';
        const toH = w ? w.toHarvest : (c && c.maturity ? (l.method === 'transplanted' && String(l.crop).startsWith('rice') && c.nursery ? c.maturity - Math.round((c.nursery.min + c.nursery.max) / 2) : c.maturity) : null);
        let html = toH ? '<div class="au-note is-info au-wpick-h"><span>🌾</span><span><b>Harvest about ' + esc(fmtDate(addDays(l.fieldDate, toH))) + '</b>, ' + toH + ' days in the field' + (w ? (() => { const i = weekOf(l, w); const x = w.weeks[Math.max(0, i)]; return i < 0 ? '' : '. This week scores <b>' + x.score + '/100</b>' + (x.priceScore != null ? ' (' + x.priceScore + ' with the price)' : '') + (x.why && x.why.length ? ': watch ' + esc(x.why.join('; ')) : ', with few troubles on record'); })() : '') + '.</span></div>' : '';
        if (w && l.fieldDate < w.earliest) {
            html += '<div class="au-note is-warn"><span>⚠️</span><span><b>Too soon for the land preparation.</b> It needs ' + esc(st.prep.why) + ', so the field is ready about ' + esc(fmtDate(w.earliest)) + '. Pick that day or later, or plant on your day with a shortened land preparation: Anee squeezes the waiting between passes, at the cost of weeds that come back and a rougher seedbed.'
                + '<label class="au-wsq"><input type="checkbox" data-wsq' + (st.squeeze ? ' checked' : '') + '> Shorten the land preparation to fit my day</label></span></div>';
        }
        return html;
    };
    const paintWin = (l) => { const b = document.querySelector('[data-win="' + l.id + '"]'); if (b) b.innerHTML = winHtml(l); };
    // The day picked, said on both pages: its harvest under the day (3), its week lit in the chart and the cards (2).
    const paintWinPick = (l) => {
        const b = plEl('auDay', l.id)?.querySelector('[data-wpick]'); if (b) b.innerHTML = winPickHtml(l);
        paintWin(l);
        const w = WIN[l.id] && WIN[l.id].window; if (!w) return;
        plEl('auDay', l.id)?.querySelectorAll('[data-wuse]').forEach((x) => x.setAttribute('aria-pressed', String(l.fieldDate ? weekOf(l, w) === w.weeks.findIndex((y) => y.date === x.dataset.wuse) : false)));
    };
    const saveSqueeze = () => put(U.answers, { prepSqueeze: Object.fromEntries(Object.entries(WIN).filter(([, v]) => v && v.squeeze).map(([k]) => [k, true])) });
    // A week chosen on either page sets the day; the page never turns by itself (only Next does).
    const pickDay = (l, iso) => {
        l.fieldDate = iso < B.today ? B.today : iso;
        const inp = plEl('auDay', l.id)?.querySelector('[data-f="fieldDate"]'); if (inp) { inp.value = l.fieldDate; window.smDateTags?.(plEl('auDay', l.id)); }
        paintLotNotes(l); paintWinPick(l); later('lot' + l.id, () => saveLot(l), 50);
        if (step === ST.when) window.toast?.(fmtDate(l.fieldDate) + ' is the planting day for ' + (l.name || 'this lot') + '. You can change it on the next page.', 'success');
    };
    const onWin = (e) => {
        const box = e.target.closest('[data-pl]'); if (!box) return;
        const l = lotById(box.dataset.pl); if (!l) return;
        const mode = e.target.closest('[data-wmode]'), use = e.target.closest('[data-wuse]'), bar = e.target.closest('[data-wk]');
        if (e.target.closest('[data-wretry]')) { loadWin(l, true); return; }
        if (mode) { WMODE = mode.dataset.wmode; S.lots.forEach(paintWin); return; }
        if (use) { pickDay(l, use.dataset.wuse); return; }
        if (bar && WIN[l.id] && WIN[l.id].window) pickDay(l, WIN[l.id].window.weeks[Number(bar.dataset.wk)].date);
    };
    $('auWhen').addEventListener('click', onWin);
    $('auDay').addEventListener('click', onWin);
    $('auDay').addEventListener('change', (e) => {
        const box = e.target.closest('[data-pl]'); if (!box) return;
        const l = lotById(box.dataset.pl); if (!l) return;
        if (e.target.closest('[data-wsq]')) { if (WIN[l.id]) { WIN[l.id].squeeze = e.target.checked; later('squeeze', saveSqueeze, 200); } return; }
        if (e.target.dataset.f === 'fieldDate') { l.fieldDate = e.target.value; paintLotNotes(l); paintWinPick(l); later('lot' + l.id, () => saveLot(l), 50); }
    });
    $('auDay').addEventListener('input', (e) => {
        if (e.target.dataset.f !== 'fieldDate') return;
        const l = lotById(e.target.closest('[data-pl]')?.dataset.pl); if (!l) return;
        l.fieldDate = e.target.value; paintLotNotes(l); paintWinPick(l); later('lot' + l.id, () => saveLot(l), 50);
    });
    const lotEl = (id) => document.querySelector('.au-lot[data-lot="' + id + '"]');

    /* ---- 2 to 6: a card a lot on each page (2026-10-28) ---- */
    const plEl = (box, id) => $(box).querySelector('[data-pl="' + id + '"]');
    const plCard = (l, i, body) => '<div class="au-lot au-pl" data-pl="' + l.id + '" style="--i:' + i + '"><div class="au-lot-h"><span class="au-lot-n">' + (S.lots.indexOf(l) + 1) + '</span>'
        + '<div class="au-pl-t"><b>' + esc(l.name || 'Lot') + '</b><small>' + esc([(cropOf(l.crop)?.icon || '🌱') + ' ' + cropName(l.crop).split(',')[0], l.size ? fmt1(l.size) + ' ' + (l.unit === 'sqm' ? 'm²' : 'ha') : ''].filter(Boolean).join(' · ')) + '</small></div></div>'
        + '<div class="au-lot-b">' + body + '</div></div>';
    const noPlantCard = '<div class="au-card"><p class="au-sub">Standing trees have no planting day and no variety to weigh: Anee starts from where they are.</p></div>';

    // 2. When to plant: each lot's year ahead, read before any day is picked.
    const paintWhen = () => {
        const ls = plantLots();
        $('auWhen').innerHTML = ls.length ? ls.map((l, i) => plCard(l, i, '<div class="au-win" data-win="' + l.id + '">' + winHtml(l) + '</div>')).join('') : noPlantCard;
        ls.forEach((l) => loadWin(l));
    };

    // 3. The planting day: the date, Anee's best weeks a tap away, the harvest it brings, the land preparation it leaves.
    const dayGap = (l) => {
        if (l.method === 'tree') return null;
        if (!l.fieldDate) return 'pick the planting day.';
        const w = WIN[l.id];
        if (w && w.window && l.fieldDate < w.window.earliest && !w.squeeze) return 'the planting day comes before the land preparation can finish: pick a later day, or shorten the land preparation.';
        return null;
    };
    const dayHtml = (l) => {
        const st = WIN[l.id], w = st && st.window, on = w ? weekOf(l, w) : -1;
        const ranked = w ? ((WMODE === 'price' && w.market ? w.bestPrice : w.best) || []).slice(0, 3) : [], best = [...ranked].sort((a, b) => a - b);
        return '<span class="au-lab">' + dayLabel(l) + '</span><label class="date-tag w-full" data-empty="Pick the day"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span class="dt-text">Pick the day</span>'
            + '<input type="date" data-f="fieldDate" min="' + B.today + '" max="' + B.latest + '" value="' + (l.fieldDate || '') + '"></label>'
            + (best.length ? '<span class="au-lab">Anee\'s best weeks <small>(tap one to use it)</small></span><div class="au-dbest">' + best.map((i) => { const x = w.weeks[i]; return '<button type="button" class="au-pill" data-wuse="' + x.date + '" aria-pressed="' + (i === on) + '">' + (i === ranked[0] ? '⭐ ' : '') + esc(wDay(x.date, true)) + ' <small>' + (x[WMODE === 'price' && w.market ? 'priceScore' : 'score'] ?? x.score) + '/100</small></button>'; }).join('') + '</div>' : '')
            + (st && st.loading ? '<p class="au-wkey">Anee is reading this lot\'s best weeks…</p>' : '')
            + '<div data-wpick>' + winPickHtml(l) + '</div><div data-notes></div>';
    };
    const paintDayLot = (l) => { const b = plEl('auDay', l.id)?.querySelector('.au-lot-b'); if (!b) return; b.innerHTML = dayHtml(l); window.smDateTags?.(b); paintLotNotes(l); };
    const paintDay = () => {
        const ls = plantLots();
        $('auDay').innerHTML = ls.length ? ls.map((l, i) => plCard(l, i, dayHtml(l))).join('') : noPlantCard;
        window.smDateTags?.($('auDay'));
        ls.forEach((l) => { paintLotNotes(l); if (!WIN[l.id]) loadWin(l); });
    };

    /* 4. The varieties weighed (2026-10-28, the owner: "have a taglike button to select the varieties you are planning to
       plant ... when clicked shows a modal box to search and select or add custom ... have multiple selections ... remove
       the auto saying suited to your area. dont pre-empt it and the ranking will always be based on the main goal of the
       user"). One tag opens the shared variety sheet for several at once; none chosen, Anee picks the best ten herself. */
    S.vAsk = (S.answers.varietyAsk && !Array.isArray(S.answers.varietyAsk)) ? S.answers.varietyAsk : {};
    const askOf = (l) => (S.vAsk[l.id] = Array.isArray(S.vAsk[l.id]) ? S.vAsk[l.id] : []);
    /* What matters, ranked (the owner, the same day: "make it a rank question like which ranks first - yield, second
       protection ... and that should also update the analysis on the next steps"): first counts 40%, then 30, 20 and 10
       in Anee's ranking. Saved with the ranking it asks for. */
    const PRI = B.priorities || [];
    const orderOk = (o) => Array.isArray(o) && o.length === PRI.length && PRI.every((p) => o.includes(p.key));
    S.order = orderOk(S.answers.varietyOrder) ? [...S.answers.varietyOrder]
        : [S.answers.varietyFirst || 'yield', ...PRI.map((p) => p.key).filter((k) => k !== (S.answers.varietyFirst || 'yield'))];
    const WEIGHT = ['40%', '30%', '20%', '10%'];
    const paintOrder = (moved) => {
        $('auOrder').innerHTML = S.order.map((k, i) => { const p = PRI.find((x) => x.key === k) || { label: k, icon: '', sub: '' };
            return '<li class="au-or' + (k === moved ? ' is-moved' : '') + '" data-k="' + k + '"><span class="au-or-n">' + (i + 1) + '</span><span class="au-or-t"><b>' + p.icon + ' ' + esc(p.label) + '</b><small>' + esc(p.sub) + '</small></span><em>' + WEIGHT[i] + '</em>'
                + '<span class="au-or-b"><button type="button" data-up aria-label="Move ' + esc(p.label) + ' up"' + (i === 0 ? ' disabled' : '') + '><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 15l6-6 6 6"/></svg></button>'
                + '<button type="button" data-down aria-label="Move ' + esc(p.label) + ' down"' + (i === S.order.length - 1 ? ' disabled' : '') + '><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button></span></li>'; }).join('');
    };
    $('auOrder').addEventListener('click', (e) => {
        const b = e.target.closest('[data-up],[data-down]'); if (!b || b.disabled) return;
        const k = b.closest('[data-k]').dataset.k, i = S.order.indexOf(k), j = i + (b.matches('[data-up]') ? -1 : 1);
        if (j < 0 || j >= S.order.length) return;
        [S.order[i], S.order[j]] = [S.order[j], S.order[i]];
        paintOrder(k);
    });
    const varsHtml = (l) => {
        const ask = askOf(l);
        return '<span class="au-lab">The varieties you plan to plant <small>(optional, up to 8)</small></span>'
            + '<button type="button" class="au-tag' + (ask.length ? '' : ' is-none') + '" data-vpick><span>🌱 ' + (ask.length ? 'Change or add varieties · ' + ask.length + ' chosen' : 'Choose the varieties you plan to plant') + '</span>' + CHEV + '</button>'
            + '<div class="au-vchips">' + (ask.length ? ask.map((n, i) => '<span class="au-vchip" style="--k:' + i + '">' + esc(n) + '<button type="button" data-vdel="' + i + '" aria-label="Remove ' + esc(n) + '">✕</button></span>').join('')
                : '<p class="au-vnone">None yet. Leave it empty and Anee picks the best ten for this lot herself.</p>') + '</div>';
    };
    const paintVarsLot = (l) => { const b = plEl('auVars', l.id)?.querySelector('.au-lot-b'); if (b) b.innerHTML = varsHtml(l); };
    const paintVars = () => {
        paintOrder();
        const ls = plantLots();
        $('auVars').innerHTML = ls.length ? ls.map((l, i) => plCard(l, i, varsHtml(l))).join('') : noPlantCard;
    };
    $('auVars').addEventListener('click', async (e) => {
        const box = e.target.closest('[data-pl]'); if (!box) return;
        const l = lotById(box.dataset.pl); if (!l) return;
        const del = e.target.closest('[data-vdel]');
        if (del) { askOf(l).splice(Number(del.dataset.vdel), 1); paintVarsLot(l); return; }
        if (e.target.closest('[data-vpick]') && window.aneeVarietyPick) {
            const c = cropOf(l.crop) || {}, m = l.fieldDate ? Number(String(l.fieldDate).slice(5, 7)) : 0;
            const res = await window.aneeVarietyPick.open({ crop: l.crop || '', cropLabel: String(c.label || '').split(' (')[0].split(' — ')[0],
                location: [townName(l.town), l.province].filter(Boolean).join(', '), season: m ? (m >= 6 && m <= 10 ? 'wet' : 'dry') : '',
                multi: true, picked: askOf(l), max: 8, suited: false,
                ownNote: 'Not on record, so Anee looks it up when she ranks the varieties, and says plainly if nothing is found.' });
            if (res && Array.isArray(res.names)) { S.vAsk[l.id] = res.names.map((n) => String(n).slice(0, 60)).slice(0, 8); paintVarsLot(l); }
        }
    });

    // 5. Anee's ranking, Variety Research's own run for each lot (AneeAutoController::varietyRank).
    S.ranks = (B.ranks && !Array.isArray(B.ranks)) ? B.ranks : {};
    const NORANK = {};
    const sameNames = (a, b) => JSON.stringify((a || []).map((x) => String(x).toLowerCase())) === JSON.stringify((b || []).map((x) => String(x).toLowerCase()));
    // A lot's ranking still fits: made for the month it is planted, the varieties named now and what matters most.
    const rankFits = (l) => { const r = S.ranks[l.id]; return !!r && (!l.fieldDate || r.month === l.fieldDate.slice(0, 7)) && sameNames(r.asked, askOf(l)) && JSON.stringify(r.order || [r.first || 'yield']) === JSON.stringify(r.order ? S.order : [S.order[0]]); };
    const ranksFresh = () => plantLots().every((l) => rankFits(l));
    const runRank = async () => {
        await flush();
        window.aneeWait.show({ title: 'Anee is ranking the varieties…', sub: 'The same research as Variety Ranking: the newest releases, the trials, the derbies and real harvests near you, weighed for your planting day. A minute or two a lot.',
            stay: 'Stay on this page while she works. Nothing is charged: it is part of Anee Auto.',
            lines: ['Searching the newest releases', 'Reading the trials and the derbies', 'Weighing your planting month', 'Checking each seed against your troubles', 'Ranking them for your lots'] });
        try {
            const body = { varieties: Object.fromEntries(plantLots().map((l) => [l.id, askOf(l)])), priorities: S.order };
            const r = await window.api(U.rank, { method: 'POST', body });
            const d = r.data && r.data.status === 'ready' ? r.data : await window.aneeWait.poll({ id: ID, job: U.job, phases: window.aneeWait.phases.research, every: 3000, limit: 600 });
            S.ranks = (d.ranks && !Array.isArray(d.ranks)) ? d.ranks : {};
            S.answers.varietyOrder = [...S.order];
            await window.aneeWait.done({ title: 'Done!', line: 'Here is how the varieties rank for your lots.' });
            show(ST.rank);
        } catch (err) { window.aneeWait.fail(); window.toast?.(err.message, 'error'); }
    };
    const typeWord = (x) => x.type === 'hybrid' ? 'Hybrid' : 'Inbred';
    const rankHtml = (l) => {
        const rk = S.ranks[l.id], R = rk && rk.report;
        if (!R || !(R.ranking || []).length) return '<div class="au-note is-warn"><span>🔬</span><span><b>Not ranked yet.</b> Rank them again, or choose a variety without a ranking.'
            + '<span class="au-rk-acts"><button type="button" class="au-pill" data-rkagain>Rank again</button><button type="button" class="au-link" data-rkskip>Choose without a ranking</button></span></span></div>';
        const asked = (rk.asked || []).map((x) => x.toLowerCase());
        const mine = (v) => asked.some((a) => String(v).toLowerCase().includes(a) || a.includes(String(v).toLowerCase().split(' (')[0]));
        const top = R.topPick || {};
        const bar = (v) => '<span class="au-rk-bar" aria-hidden="true"><i style="--w:' + Math.max(4, Math.min(100, Number(v) || 0)) + '%"></i></span>';
        const card = (x, i) => '<div class="au-vp' + (i === 0 ? ' is-top' : '') + '" style="--k:' + i + '">'
            + '<div class="au-vp-h"><span class="au-vp-n">' + (i + 1) + '</span><div><b>' + esc(x.variety) + '</b>' + (x.by ? '<small>' + esc(x.by) + (x.released && x.released !== 'n/a' ? ', ' + esc(x.released) : '') + '</small>' : '') + '</div><span class="au-vp-s">' + (x.overall ?? '') + '</span></div>'
            + bar(x.overall)
            + '<div class="au-vp-c"><span>' + typeWord(x) + '</span>' + (x.maturityDays && x.maturityDays !== 'n/a' ? '<span>⏱ ' + esc(x.maturityDays) + '</span>' : '') + (x.yieldPotential && x.yieldPotential !== 'not published' ? '<span>🌾 ' + esc(x.yieldPotential) + '</span>' : '') + (mine(x.variety) ? '<span class="is-mine">You named it</span>' : '') + '</div>'
            + (x.fitNotes ? '<p class="au-vp-p">' + esc(x.fitNotes) + '</p>' : '')
            + ((x.strengths || []).length || (x.weaknesses || []).length ? '<ul class="au-vp-y">' + (x.strengths || []).filter(Boolean).slice(0, 2).map((t) => '<li>👍 ' + esc(t) + '</li>').join('') + (x.weaknesses || []).filter(Boolean).slice(0, 2).map((t) => '<li>⚠️ ' + esc(t) + '</li>').join('') + '</ul>' : '')
            + ((x.fieldResults || []).length ? '<ul class="au-vp-y">' + x.fieldResults.filter((f) => f && f.yield).slice(0, 2).map((f) => '<li>🏆 ' + esc([f.yield, f.where, f.when].filter(Boolean).join(', ')) + '</li>').join('') + '</ul>' : '')
            + (x.record && x.record.url ? '<div class="au-vp-a"><a class="au-link" href="' + esc(x.record.url) + '" target="_blank" rel="noopener">Its record</a></div>' : '') + '</div>';
        const all = R.ranking.slice(0, 10), shown = all.slice(0, 3), more = all.slice(3);
        const row = (x, i) => '<div class="au-rk-row"><span class="au-vp-n">' + (i + 1) + '</span><span><b>' + esc(x.variety) + '</b><small>' + esc([typeWord(x), x.maturityDays && x.maturityDays !== 'n/a' ? x.maturityDays : '', x.yieldPotential && x.yieldPotential !== 'not published' ? x.yieldPotential : ''].filter(Boolean).join(' · ')) + '</small>'
            + (x.fitNotes ? '<em>' + esc(x.fitNotes) + '</em>' : '') + '</span><span class="au-vp-s">' + (x.overall ?? '') + '</span></div>';
        const tok = String(top.variety || all[0].variety || '').toLowerCase().split(/[\s(]/)[0].replace(/[^a-z0-9]/g, '');
        const lead = R.headline && tok.length >= 3 && String(R.headline).toLowerCase().replace(/[^a-z0-9]/g, '').includes(tok) ? R.headline : '';
        const stale = !rankFits(l);
        // What she weighed it by, in the farmer's own order.
        const by = (rk.order && rk.order.length ? rk.order : [rk.first || 'yield']).map((k, i) => (i + 1) + ' ' + ((PRI.find((p) => p.key === k) || {}).label || k)).join(' · ');
        return '<p class="au-rk-by">Ranked by what matters to you: <b>' + esc(by) + '</b></p>' + (stale ? '<div class="au-note is-warn"><span>🔁</span><span>The planting day, the varieties or what matters most changed since this ranking. <button type="button" class="au-link" data-rkagain>Rank again</button></span></div>' : '')
            + '<div class="au-rk-top"><span aria-hidden="true">🏆</span><div><small>Anee\'s top pick for planting in ' + esc(rk.monthWords || '') + '</small><b>' + esc(top.variety || all[0].variety) + '</b>' + (top.why ? '<p>' + esc(top.why) + '</p>' : '') + '</div></div>'
            + (lead ? '<p class="au-rk-lead">' + esc(lead) + '</p>' : '')
            + '<div class="au-vpks">' + shown.map(card).join('') + '</div>'
            + (more.length ? '<div class="au-rows">' + acc('🔟', 'The rest of the ten', more.map((x) => x.variety).join(', '), '<div class="au-rk-rows">' + more.map((x, j) => row(x, j + 3)).join('') + '</div>', more.length) + '</div>' : '')
            + '<div class="au-rows">'
            + acc('🧑‍🌾', 'The varieties you named', first(((R.givenVarieties || [])[0] || {}).verdict), list((R.givenVarieties || []).filter((g) => g && g.variety).map((g) => '<b>' + esc(g.variety) + '</b>: ' + esc(g.verdict || ''))), (R.givenVarieties || []).length)
            + acc('🆕', 'The newest releases', ((R.newest || [])[0] || {}).variety, list((R.newest || []).filter((x) => x && x.variety).map((x) => '<b>' + esc(x.variety) + '</b>' + (x.year ? ' (' + esc(x.year) + ')' : '') + (x.note ? ': ' + esc(x.note) : ''))), (R.newest || []).length)
            + acc('🌦️', 'Your ground and season', first((R.conditions || {}).soil), para((R.conditions || {}).soil) + para((R.conditions || {}).weather) + list(((R.conditions || {}).risks || []).map(esc)))
            + acc('📝', 'How to grow the top pick here', first((R.management || [])[0]), list((R.management || []).map(esc)), (R.management || []).length)
            + '</div>'
            // Anee's own words name her first pick before the weighing; where the weighing moved another up, they would contradict the list.
            + (R.summary && lead ? '<p class="au-rk-sum">' + esc(R.summary) + '</p>' : '')
            + '<p class="au-rk-foot">Ranked ' + esc(rk.at || '') + (R.confidence ? ' · ' + esc(R.confidence) + ' confidence' : '') + '. Kept in your Variety Ranking too.</p>';
    };
    const paintRank = () => {
        const ls = plantLots();
        $('auRank').innerHTML = '<div class="au-card"><h3 class="au-h">Anee\'s ranking</h3><p class="au-sub">Each lot\'s varieties ranked for its place and planting day: what really harvests here, what guards against its troubles, what comes through its weather, and how soon it is ready. You choose on the next page.</p></div>'
            + (ls.length ? ls.map((l, i) => plCard(l, i, rankHtml(l))).join('') : noPlantCard);
    };
    $('auRank').addEventListener('click', (e) => {
        const h = e.target.closest('.au-acc-h');
        if (h) { const box = h.closest('.au-acc'), open = !box.classList.contains('is-open'); box.classList.toggle('is-open', open); h.setAttribute('aria-expanded', String(open)); return; }
        if (e.target.closest('[data-rkagain]')) { runRank(); return; }
        const sk = e.target.closest('[data-rkskip]');
        if (sk) { const l = lotById(sk.closest('[data-pl]').dataset.pl); if (l) { NORANK[l.id] = true; window.toast?.('Choose the variety for ' + (l.name || 'this lot') + ' on the next page.', 'success'); } }
    });

    // 6. The variety: one of Anee's ranked, the top chosen to start with, or the farmer's own; then how much is planted.
    const pickHtml = (l) => {
        const R = (S.ranks[l.id] || {}).report, ranked = ((R && R.ranking) || []).slice(0, 6);
        const opts = ranked.map((x, i) => '<button type="button" class="au-pk' + (l.variety === x.variety ? ' is-on' : '') + '" role="radio" aria-checked="' + (l.variety === x.variety) + '" data-pkv="' + esc(x.variety) + '" style="--k:' + i + '">'
            + '<span class="au-pk-dot" aria-hidden="true"></span><span class="au-pk-t"><b>' + esc(x.variety) + '</b><small>' + esc([i === 0 ? 'Anee\'s top pick' : '#' + (i + 1), typeWord(x), x.maturityDays && x.maturityDays !== 'n/a' ? x.maturityDays : ''].filter(Boolean).join(' · ')) + '</small></span><span class="au-vp-s">' + (x.overall ?? '') + '</span></button>').join('');
        const own = l.variety && !ranked.some((x) => x.variety === l.variety);
        const door = opts && !own ? '<button type="button" class="au-tag is-none" data-act="variety"><span>🧬 Search the records, or type your own</span>' + CHEV + '</button>' : varTag(l);
        return (opts ? '<div class="au-pks" role="radiogroup" aria-label="The variety for ' + esc(l.name || 'this lot') + '">' + opts + '</div>' : '')
            + '<span class="au-lab">' + (opts ? 'Or another variety' : 'The variety') + (own ? ' <small>(chosen)</small>' : '') + '</span><div data-var="' + l.id + '">' + door + '</div>'
            + '<div class="au-goal" data-goal="' + l.id + '">' + goalBlock(l) + '</div>';
    };
    const paintPickLot = (l) => { const b = plEl('auPickV', l.id)?.querySelector('.au-lot-b'); if (b) b.innerHTML = pickHtml(l); };
    const paintPickV = () => {
        // Anee's top pick is chosen where nothing is yet (a default the farmer sees and can change; it is saved now).
        S.lots.forEach((l) => {
            const top = (((S.ranks[l.id] || {}).report || {}).ranking || [])[0];
            if (l.method !== 'tree' && !l.variety && top && top.variety) { l.variety = String(top.variety).slice(0, 120); l.varRecord = null; later('lot' + l.id, () => saveLot(l), 60); }
        });
        $('auPickV').innerHTML = S.lots.map((l, i) => plCard(l, i, pickHtml(l))).join('');
    };
    $('auPickV').addEventListener('click', (e) => {
        const box = e.target.closest('[data-pl]'); if (!box) return;
        const l = lotById(box.dataset.pl); if (!l) return;
        const o = e.target.closest('[data-pkv]');
        if (o) { l.variety = o.dataset.pkv.slice(0, 120); l.varRecord = null; paintPickLot(l); later('lot' + l.id, () => saveLot(l), 50); return; }
        const a = e.target.closest('[data-act="variety"]'); if (a) pickVariety(l);
    });
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
    const paintLacking = (l) => { const box = lotEl(l.id)?.querySelector('[data-lacking]'); if (box) box.innerHTML = lackingHtml(l); };
    // Under the planting day: the land preparation it leaves, and the seedbed's day for a transplanted crop.
    const paintLotNotes = (l) => {
        const box = plEl('auDay', l.id)?.querySelector('[data-notes]');
        if (!box) return;
        const c = cropOf(l.crop);
        let html = '';
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
        later('lot' + l.id, () => saveLot(l), 700);
    });
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
        + '<div class="au-acts"><button type="button" class="btn btn-white" data-close>Not now</button><button type="button" class="btn btn-cta is-go" data-ok>Add a lot · ' + Number(S.perLot).toLocaleString() + '</button></div>', async (box) => {
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
        if (step !== ST.workers) return;
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
                + '<div class="au-gw-acts"><button type="button" class="btn btn-white btn-sm" data-gcancel>Cancel</button><button type="button" class="btn btn-cta btn-sm" data-gadd>Add to this season</button></div>'
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
        if (step === ST.lots) paintLots();
        later('links', saveLinks, 80);
        later('lot' + l.id, () => saveLot(l), 120);
        await applyLotProfile();
    }
    function unlinkLot(l) {
        delete LINK[l.id];
        if (step === ST.lots) paintLots();
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
        if (step === ST.lots) paintLots();
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
        if (JSON.stringify(soil) !== soilWas) { if (step === ST.soil) paintSoil(); later('soil', saveSoil, 300); }

        const w = BOOK.water[L.waterAuto] ? L.waterAuto : null;
        if ((HID.water || !S.answers.water || S.answers.water === AUTO.water) && (w || AUTO.water) && S.answers.water !== w) {
            S.answers.water = w; AUTO.water = w;
            if (step === ST.water) paintWater();
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
        if (JSON.stringify([S.answers.problems, S.answers.cropProblems]) !== probWas) { if (step === ST.troubles) paintProblems(); later('problems', saveProblems, 300); }
    }
    function afterApply() {
        navSay();
        paintFrom();
        if (step === ST.troubles) paintProblems();
        if (step === ST.proto) paintReview();
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
            water: w === 'partial' ? 'limited' : w, value: { name: l.variety || '', record: l.varRecord || null }, suited: false,
            ownNote: 'Not on record, so Anee looks it up when she reads the farm, and says plainly if nothing is found.' });
        if (!res) return;
        l.variety = String(res.name || '').slice(0, 120);
        l.varRecord = res.record ? Object.assign({ crop: l.crop }, res.record) : null;
        const box = document.querySelector('[data-var="' + l.id + '"]');
        if (box) {
            box.innerHTML = varTag(l);
            if (!still()) box.querySelector('.au-tag')?.classList.add('is-bump');
        }
        later('lot' + l.id, () => saveLot(l), 50);
        if (step === ST.pick) paintPickLot(l);
    }

    /* ---- 11. Anee reads the farm ---- */
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
            S.analysis = d.analysis; S.readingsLeft = Math.max(0, (S.readingsLeft || 3) - 1); S.step = Math.max(S.step, ST.fert);
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
        if (e.target.closest('[data-go-goal]')) show(ST.reading, true);
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
            + '<div class="au-acts"><button type="button" class="btn btn-white" data-fix>Change the plan</button><button type="button" class="btn btn-cta is-go" data-ok disabled>Go on with it</button></div>', async (box) => {
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
            await window.aneeWait.done({ title: 'Your protocol is written!', line: 'Read it, then approve it to start.' });
            S.protocolDraft = true; S.protocolStale = false; S.protocolRunsLeft = Math.max(0, (S.protocolRunsLeft || 1) - 1); PROTO = null;
            paintStart();
            $('auProto').scrollIntoView({ behavior: still() ? 'auto' : 'smooth', block: 'start' });
        } catch (err) { window.aneeWait.fail(); window.toast?.(err.message, 'error'); }
    });

    /* ---- the protocol, presented before it is approved (2026-10-28, the owner: "the last page before starting the
       initial anee auto daily process (phase where there's no turning back), is a presentation of the generated protocol
       (generate it via anee) somewhat similar to how it is presented in the protocol builder but better with explanation,
       what to apply in each step, so that user can approve it before continuing. make it look great with graphs"). ---- */
    let PROTO = null, PLOT = null;
    const GROUPS = [['prep', 'Land preparation', '🚜', ['land_prep']], ['plant', 'Planting', '🌱', ['nursery', 'sowing', 'transplant', 'planting']], ['water', 'Water', '💧', ['irrigation', 'drainage']],
        ['fert', 'Fertilizer', '🧪', ['fertilizer', 'foliar']], ['weeds', 'Weeds', '🌿', ['herbicide', 'weeding']], ['protect', 'Pests and diseases', '🛡️', ['insecticide', 'fungicide', 'protect']],
        ['scout', 'Walks', '👀', ['scouting']], ['harvest', 'Harvest', '🧺', ['harvest', 'post_harvest', 'prepare', 'other']]];
    const groupOf = (k) => (GROUPS.find((g) => g[3].includes(k)) || GROUPS[7])[0];
    // The kilos a fertilizer line says: bags (50 kg unless it says 25), kilos, grams.
    const kgOf = (amount) => { const a = String(amount || '').toLowerCase(); let m = a.match(/([\d.]+)\s*(?:bags?|sacks?)/); if (m) return Number(m[1]) * (/25\s*kg/.test(a) ? 25 : 50);
        m = a.match(/([\d.]+)\s*kg/); if (m) return Number(m[1]); m = a.match(/([\d.]+)\s*g\b/); return m ? Number(m[1]) / 1000 : 0; };
    const paintStart = async () => {
        $('auGenerate').closest('.text-center').hidden = !!S.protocolDraft;
        $('auStartSub').textContent = S.protocolDraft ? 'Anee wrote your season below. Read it, then approve it to start. Something to change first? Go back to that step: she writes it again.' : $('auStartSub').textContent;
        if (!S.protocolDraft) { $('auProto').innerHTML = ''; return; }
        if (!PROTO) {
            $('auProto').innerHTML = '<div class="au-card au-pr-wait"><span aria-hidden="true">📜</span> Opening your protocol…</div>';
            try { PROTO = (await apiGet(U.protocol)).data; } catch (_) { $('auProto').innerHTML = '<div class="au-card">It did not load. <button type="button" class="au-link" onclick="location.reload()">Try again</button></div>'; return; }
        }
        if (!PROTO.ready) { S.protocolDraft = false; return paintStart(); }
        if (!PLOT || !PROTO.lots.find((l) => l.id === PLOT)) PLOT = PROTO.lots[0] ? PROTO.lots[0].id : null;
        paintProto();
    };
    const paintProto = () => {
        const P = PROTO, L = P.lots.find((l) => l.id === PLOT) || P.lots[0], T = P.tasks.filter((t) => t.lotId === L.id);
        const all = P.tasks, sprays = all.filter((t) => ['herbicide', 'insecticide', 'fungicide', 'foliar'].includes(t.kind));
        const fertT = T.filter((t) => t.kind === 'fertilizer' || t.kind === 'foliar');
        const harvests = P.lots.map((l) => l.harvest).filter(Boolean).sort();
        const chips = [['📋', all.length + ' tasks'], ['🧪', all.filter((t) => t.kind === 'fertilizer').length + ' fertilizer days'], ['🛡️', sprays.length + ' sprays'], ['👀', all.filter((t) => t.kind === 'scouting').length + ' field walks'],
            harvests.length ? ['🧺', 'Harvest from ' + fmtDate(harvests[0])] : null].filter(Boolean);
        // The season at a glance: the stages as bands, each group of work as a row of dots on its day.
        const last = Math.max(L.harvest && L.field ? daysFrom(L.field, L.harvest) + 10 : 0, ...T.map((t) => t.day ?? 0), 30);
        const first = Math.min(-5, ...T.map((t) => t.day ?? 0));
        const span = last - first, X = (d) => ((d - first) / span * 100).toFixed(2) + '%';
        const tones = ['#d6ebbf', '#b5d98f', '#8fc96a', '#f0cf5c', '#e0a94a', '#cbb9f0', '#9fd0f5', '#f3c5a6'];
        const stagesSvg = L.stages.map((st, i) => { const to = (L.stages[i + 1] ? L.stages[i + 1].day : last); return '<span class="au-pg-st" style="left:' + X(st.day) + ';width:calc(' + X(to) + ' - ' + X(st.day) + ');--c:' + tones[i % tones.length] + ';--k:' + i + '" title="' + esc(st.name + ' from ' + fmtDate(st.date)) + '">' + esc(st.name) + '</span>'; }).join('');
        const rows = GROUPS.map((g) => { const ts = T.filter((t) => groupOf(t.kind) === g[0] && t.day != null); if (!ts.length) return ''; return '<div class="au-pg-row"><span class="au-pg-lab"><i aria-hidden="true">' + g[2] + '</i><b>' + g[1] + '</b></span><div class="au-pg-track">'
            + ts.map((t, i) => '<span class="au-pg-dot is-' + g[0] + (t.critical ? ' is-crit' : '') + '" style="left:' + X(t.day) + ';--k:' + i + '" title="' + esc((t.date ? fmtDate(t.date) + ': ' : '') + t.title) + '"></span>').join('') + '</div></div>'; }).join('');
        // The day marks under the chart, never on top of each other (fewer on a phone).
        const gap = innerWidth < 560 ? 18 : 9;
        const ticks = [first < 0 ? first : null, 0, ...L.stages.slice(1).map((s2) => s2.day), L.harvest ? daysFrom(L.field, L.harvest) : null].filter((d, i, a) => d != null && a.indexOf(d) === i)
            .reduce((kept, d) => { const at = (d - first) / span * 100; if (!kept.length || at - (kept[kept.length - 1] - first) / span * 100 >= gap) kept.push(d); return kept; }, []);
        // The work a week: the crew days of each week, for the lot.
        const weeks = {}; T.forEach((t) => { if (!t.date) return; const d = new Date(t.date + 'T00:00:00'); const mon = new Date(d); mon.setDate(d.getDate() - ((d.getDay() + 6) % 7)); const k = mon.toISOString().slice(0, 10); weeks[k] = (weeks[k] || 0) + Math.max(1, t.crew || 1); });
        const wk = Object.entries(weeks).sort(), wMax = Math.max(1, ...wk.map(([, v]) => v));
        // The fertilizer at each application, in kilos, stacked by product.
        const PCOL = ['#4a7c2a', '#d97706', '#0284c7', '#7c3aed', '#db2777', '#0f766e'];
        const prodNames = []; fertT.forEach((t) => t.products.forEach((p) => { if (kgOf(p.amount) > 0 && !prodNames.includes(p.name)) prodNames.push(p.name); }));
        const fMax = Math.max(1, ...fertT.map((t) => t.products.reduce((a, p) => a + kgOf(p.amount), 0)));
        const fertBars = fertT.filter((t) => t.products.some((p) => kgOf(p.amount) > 0));
        // Step by step, by the crop's stage.
        const stageOf = (d) => { if (d == null) return 'Any time'; if (d < 0) return 'Before planting'; let n = L.stages[0] ? L.stages[0].name : 'Growing'; L.stages.forEach((st) => { if (d >= st.day) n = st.name; }); return n; };
        const order = ['Before planting', ...L.stages.map((st) => st.name), 'Any time'];
        const byStage = {}; T.forEach((t) => { const k = stageOf(t.day); (byStage[k] = byStage[k] || []).push(t); });
        const taskCard = (t) => '<div class="au-pt' + (t.critical ? ' is-crit' : '') + '"><div class="au-pt-h"><span class="au-pt-i" aria-hidden="true">' + t.icon + '</span><div><b>' + esc(t.title) + '</b><small>' + esc((t.date ? fmtDate(t.date) : '') + (t.day != null ? ' · ' + L.counter + ' ' + t.day : '') + (t.crew > 1 ? ' · ' + t.crew + ' people' : '') + (t.critical ? ' · do not be late' : '')) + '</small></div></div>'
            + (t.summary ? '<p>' + esc(t.summary) + '</p>' : '')
            + (t.products.length ? '<div class="au-pt-p">' + t.products.map((p) => '<span><b>' + esc(p.name) + '</b>' + esc([p.group, p.rate, p.amount].filter(Boolean).join(' · ')) + '</span>').join('') + '</div>' : '')
            + (t.steps.length ? '<ol class="au-pt-s">' + t.steps.map((x) => '<li>' + esc(x) + '</li>').join('') + '</ol>' : '')
            + (t.why ? '<p class="au-pt-y"><b>Why:</b> ' + esc(t.why) + '</p>' : '')
            + (t.mix ? '<p class="au-pt-mx is-' + esc(t.mix.verdict) + '">🧪 ' + esc(t.mix.say) + '</p>' : '')
            + (t.mixWith ? '<p class="au-pt-mx is-' + esc(t.mixWith.verdict) + '">🤝 ' + esc(t.mixWith.say) + ' With: ' + esc(t.mixWith.tasks.map((x) => x.title).join(', ')) + '</p>' : '')
            + (t.safety.length ? '<p class="au-pt-sf">🦺 ' + esc(t.safety.join(' · ')) + '</p>' : '') + '</div>';
        $('auProto').innerHTML = '<div class="au-pr">'
            + '<div class="au-pr-hero"><img src="' + @json(\App\Support\AneeEmoji::url('thumbsup')) + '" alt=""><div><span>Written by Anee' + (P.generatedAt ? ' · ' + esc(P.generatedAt) : '') + '</span><h3>Your season, step by step</h3><p>Read it lot by lot. Approve it and Anee starts running it every morning; until then, nothing is fixed.</p>'
            + '<div class="au-pr-chips">' + chips.map((c, i) => '<span style="--k:' + i + '">' + c[0] + ' ' + esc(c[1]) + '</span>').join('') + '</div></div></div>'
            + (P.stale ? '<div class="au-note is-warn"><span>⚠️</span><span><b>You changed an answer after this protocol was written.</b> ' + (S.protocolRunsLeft ? 'Write it again so it follows your answers (' + S.protocolRunsLeft + ' left).' : 'It follows your earlier answers.') + '</span></div>' : '')
            + (P.lots.length > 1 ? '<div class="au-ltabs au-pr-tabs">' + P.lots.map((l) => '<button type="button" class="au-ltab' + (l.id === L.id ? ' is-on' : '') + '" data-plot="' + l.id + '">' + l.icon + ' ' + esc(l.name) + '</button>').join('') + '</div>' : '')
            + (L.strategy ? '<div class="au-pr-strat"><span aria-hidden="true">💬</span><p>' + esc(L.strategy) + '</p></div>' : '')
            + '<div class="au-card au-pr-c"><h4>🗓️ The season at a glance <small>' + esc(L.icon + ' ' + L.crop + (L.variety ? ', ' + L.variety : '')) + (L.field ? ' · ' + esc(fmtDate(L.field)) + (L.harvest ? ' to ' + esc(fmtDate(L.harvest)) : '') : '') + '</small></h4>'
                + '<div class="au-pg"><div class="au-pg-row is-stages"><span class="au-pg-lab"><i aria-hidden="true">🌾</i><b>Stages</b></span><div class="au-pg-track">' + stagesSvg + '</div></div>' + rows
                + '<div class="au-pg-row is-axis"><span class="au-pg-lab"></span><div class="au-pg-track">' + ticks.map((d) => '<span class="au-pg-tick" style="left:' + X(d) + '">' + esc(d === 0 ? 'Planting' : (d < 0 ? d + ' days' : L.counter + ' ' + d)) + '</span>').join('') + '</div></div></div>'
                + '<p class="au-pr-key">Each dot is a task on its day; a ringed dot must not be late.</p></div>'
            + '<div class="au-pr-2">'
            + '<div class="au-card au-pr-c"><h4>👷 The work, week by week <small>people a week on ' + esc(L.name) + '</small></h4><div class="au-pw">' + wk.map(([k, v], i) => '<div class="au-pw-c" title="' + esc('Week of ' + fmtDate(k) + ': ' + v + ' people') + '"><i style="--h:' + (v / wMax).toFixed(2) + ';--k:' + i + '"' + (v === wMax ? ' class="is-peak"' : '') + '></i><span>' + (i % 3 === 0 ? esc(fmtDate(k).replace(/^\w+, /, '')) : '') + '</span></div>').join('') + '</div>'
                + '<p class="au-pr-key">The tallest week is the busiest: plan the help for it.</p></div>'
            + '<div class="au-card au-pr-c"><h4>🧪 The fertilizer, when and how much <small>kilos for this lot</small></h4>' + (fertBars.length ? '<div class="au-pf">' + fertBars.map((t, i) => { const tot = t.products.reduce((a, p) => a + kgOf(p.amount), 0); return '<div class="au-pf-r" style="--k:' + i + '"><span class="au-pf-l"><b>' + esc(t.date ? fmtDate(t.date).replace(/^\w+, /, '') : '') + '</b><small>' + esc(stageOf(t.day)) + '</small></span><div class="au-pf-b">'
                    + t.products.filter((p) => kgOf(p.amount) > 0).map((p) => '<i style="width:' + (kgOf(p.amount) / fMax * 100).toFixed(1) + '%;--c:' + PCOL[prodNames.indexOf(p.name) % PCOL.length] + '" title="' + esc(p.name + ': ' + p.amount) + '"></i>').join('') + '</div><span class="au-pf-v">' + Math.round(tot) + ' kg</span></div>'; }).join('') + '</div>'
                + '<div class="au-pf-key">' + prodNames.map((n, i) => '<span><i style="--c:' + PCOL[i % PCOL.length] + '"></i>' + esc(n) + '</span>').join('') + '</div>' : '<p class="au-pr-key">No granular fertilizer amounts on this lot\'s tasks.</p>') + '</div>'
            + '</div>'
            + '<div class="au-card au-pr-c"><h4>📜 Step by step <small>what to do and what to apply, stage by stage</small></h4>'
            + order.filter((k) => byStage[k]).map((k, i) => '<div class="au-acc au-ps"><button type="button" class="au-acc-h" aria-expanded="' + (i === 0) + '"><span class="au-acc-t"><b>' + esc(k) + '</b><small>' + byStage[k].length + ' task' + (byStage[k].length > 1 ? 's' : '') + (byStage[k][0].date ? ' · from ' + esc(fmtDate(byStage[k][0].date)) : '') + '</small></span>' + CHEV + '</button><div class="au-acc-b"' + (i === 0 ? ' style="grid-template-rows:1fr"' : '') + '><div><div class="au-pts">' + byStage[k].map(taskCard).join('') + '</div></div></div></div>').join('')
            + '</div>'
            + (sprays.length || P.safety.length ? '<div class="au-card au-pr-c"><h4>🛡️ The sprays and their safety <small>all lots</small></h4>'
                + (sprays.length ? '<div class="au-psp">' + sprays.map((t) => '<div><b>' + esc(t.date ? fmtDate(t.date) : '') + '</b><span>' + t.icon + ' ' + esc(t.title) + (t.products.length ? '<small>' + esc(t.products.map((p) => p.name + (p.group ? ' (' + p.group + ')' : '') + (p.rate ? ', ' + p.rate : '')).join('; ')) + '</small>' : '') + '</span></div>').join('') + '</div>' : '')
                + (P.safety.length ? '<h5>Every time you spray</h5><ul>' + P.safety.map((x) => '<li>' + esc(x) + '</li>').join('') + '</ul>' : '')
                + (P.compatibility.length ? '<h5>Never mix</h5><ul>' + P.compatibility.map((x) => '<li>' + esc(x) + '</li>').join('') + '</ul>' : '') + '</div>' : '')
            + '<div class="au-card au-pr-go"><div class="au-lock"><span aria-hidden="true">🔒</span><span><b>Approving starts the season, and there is no going back.</b> The answers and the protocol are fixed from then on: Anee runs the season from them, every morning. You steer it by telling her what happened, what you saw and what cannot be done that day.</span></div>'
                + '<label class="au-pr-ok"><input type="checkbox" id="auProtoOk"' + (PROTO_OK ? ' checked' : '') + '> I have read the protocol and I am ready to start</label>'
                + '<div class="au-pr-acts"><button type="button" class="btn btn-cta is-go" id="auApprove"' + (PROTO_OK ? '' : ' disabled') + '>Next: the agreement</button>'
                + (S.protocolRunsLeft ? '<button type="button" class="au-link" id="auRewrite">Write it again (' + S.protocolRunsLeft + ' left)</button>' : '') + '</div></div>'
            + '</div>';
    };
    $('auProto').addEventListener('click', async (e) => {
        const t = e.target.closest('[data-plot]'); if (t) { PLOT = Number(t.dataset.plot); paintProto(); return; }
        const h = e.target.closest('.au-ps .au-acc-h');
        if (h) { const open = h.getAttribute('aria-expanded') !== 'true'; h.setAttribute('aria-expanded', String(open)); h.nextElementSibling.style.gridTemplateRows = open ? '1fr' : '0fr'; return; }
        if (e.target.closest('#auRewrite')) { $('auGenerate').click(); return; }
        if (e.target.closest('#auApprove')) { show(ST.agree); return; }
    });
    $('auProto').addEventListener('change', (e) => { if (e.target.id === 'auProtoOk') { PROTO_OK = e.target.checked; $('auApprove').disabled = !e.target.checked; } });

    /* ---- the agreement (2026-10-28, the owner: "before starting ... another wizard page about eula that anee is just a
       guide and user can still override the decisions of anee ... disable the continue if user did not check yet and
       if did not scrolled down the text yet. with animated arrow saying to scroll down and check"). The box must be
       read to its end before the tick opens, and the tick before Start. The version goes with the start. ---- */
    const termsBox = $('auTermsBox');
    const termsEdge = () => {
        if (READ || !termsBox) return;
        if (termsBox.scrollTop + termsBox.clientHeight >= termsBox.scrollHeight - 12) {
            READ = true;
            $('auTermsOk').disabled = false;
            $('auTermsAgree').classList.remove('is-locked');
            $('auTermsHint').classList.add('is-read');
            $('auTermsHint').querySelector('span').textContent = 'Read. Now tick the box to start';
        }
    };
    termsBox?.addEventListener('scroll', termsEdge, { passive: true });
    $('auTermsOk')?.addEventListener('change', (e) => { $('auStart').disabled = !e.target.checked; $('auTermsHint').classList.toggle('is-done', e.target.checked); });
    $('auTermsHint')?.addEventListener('click', () => { if (!READ) termsBox.scrollBy({ top: termsBox.clientHeight * .8, behavior: still() ? 'auto' : 'smooth' }); else $('auTermsOk').focus(); });
    $('auStart')?.addEventListener('click', async () => {
        const b = $('auStart');
        if (!READ || !$('auTermsOk').checked) return;
        {
            b.disabled = true;
            try {
                const r = await put(U.approve, { agree: AGREE }, 'POST');
                window.aneeWait.show({ title: 'Anee Auto is starting!', sub: 'Placing every task around the weather and your workers.', lines: ['Your season is locked in', 'Tomorrow at dawn, your first morning plan'] });
                await window.aneeWait.done({ title: 'Your season has started! 🎉', line: 'See you tomorrow morning with today\'s work.' });
                location.href = (r.data && r.data.url) || @json(route('auto.show', $auto->id));
            } catch (err) { b.disabled = false; }
        }
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
        if (step === ST.lots) paintLots();
        applyLotProfile();
    });
})();
</script>
@endpush
