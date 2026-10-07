@extends('layouts.app')
@section('title', 'NPK Plus Calculator')
@section('page-title', 'NPK Plus')
@section('page-subtitle', 'Every nutrient in your fertilizer plan')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

@section('content')
@include('partials.tag-sheet-css')
<style>
    /* ---- NPK PLUS (2026-10-07) -------------------------------------------
       A calculator that answers as you type. Every choice is a tag that opens
       a sheet (the owner's rule for this module, like the other analysis
       wizards): the crop, the units, the fertilizers and their amounts. What
       the plan gives reads in three tabs: N, P2O5 and K2O as the bag reads
       them, the secondary and micronutrients, and Liebig's barrel. The house
       curve on every change, held still under reduced motion. */
    :root { --np-ease: cubic-bezier(.22,1,.36,1); }
    .np-wrap { max-width: 72rem; margin: 0 auto; }

    /* The top card folds, so the calculator starts near the top of a phone. */
    .np-hero { position: relative; overflow: hidden; border-radius: 1.2rem; margin-bottom: 1rem; color: #3b2a00;
        background: radial-gradient(120% 140% at 100% 0%, #fde68a 0%, #f5c518 45%, #d4a106 100%); }
    .np-hero-head { display: flex; align-items: center; gap: .8rem; width: 100%; padding: .85rem 1rem; text-align: left; cursor: pointer; border: 0; background: none; color: inherit; }
    .np-chem { display: flex; gap: .25rem; flex: none; }
    .np-chem span { display: grid; place-items: center; width: 1.9rem; height: 1.9rem; border-radius: .55rem; font-family: var(--font-heading); font-weight: 800; font-size: .85rem; color: #fff;
        background: rgb(31 21 0 / .78); box-shadow: 0 8px 20px -10px rgb(0 0 0 / .6); animation: npBob 4s ease-in-out infinite; }
    .np-chem span:nth-child(2) { animation-delay: .4s; } .np-chem span:nth-child(3) { animation-delay: .8s; }
    @keyframes npBob { 50% { transform: translateY(-3px); } }
    .np-hero-t { min-width: 0; flex: 1 1 auto; }
    .np-hero-t b { display: block; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; line-height: 1.3; color: #1f1500; }
    .np-hero-t i { display: block; font-style: normal; font-size: .76rem; color: #5a4300; }
    .np-hero-chev { width: 1.1rem; height: 1.1rem; flex: none; color: #3b2a00; transition: transform .28s var(--np-ease); }
    .np-hero.is-open .np-hero-chev { transform: rotate(180deg); }
    .np-hero-fold { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .28s var(--np-ease); }
    .np-hero.is-open .np-hero-fold { grid-template-rows: 1fr; }
    .np-hero-fold > div { min-height: 0; overflow: hidden; }
    .np-hero-in { padding: 0 1rem 1rem; }
    .np-hero-in p { font-size: .84rem; line-height: 1.55; max-width: 40rem; color: #4a3600; }
    .np-steps { display: grid; gap: .4rem; margin-top: .7rem; counter-reset: np; }
    @media (min-width: 640px) { .np-steps { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .np-steps li { counter-increment: np; display: flex; gap: .5rem; align-items: flex-start; padding: .55rem .65rem; border-radius: .8rem; font-size: .78rem; line-height: 1.45; color: #3b2a00; background: rgb(255 255 255 / .42); }
    .np-steps li::before { content: counter(np); display: grid; place-items: center; flex: none; width: 1.3rem; height: 1.3rem; border-radius: 999px; font-size: .7rem; font-weight: 800; color: #fff; background: #3b2a00; }

    .np-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .np-tab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem; text-align: center; color: var(--color-gray-500); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; transition: background-color .28s var(--np-ease), color .28s var(--np-ease); }
    .np-tab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .np-grid { display: grid; gap: 1rem; grid-template-columns: minmax(0, 1fr); align-items: start; }
    @media (min-width: 1024px) { .np-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .np-card { border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); padding: 1rem 1.05rem; min-width: 0; }
    .np-card + .np-card { margin-top: 1rem; }
    .np-card h3 { display: flex; align-items: center; gap: .5rem; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: var(--color-gray-900); }
    .np-card h3 i { display: grid; place-items: center; flex: none; width: 1.6rem; height: 1.6rem; border-radius: .55rem; font-style: normal; font-size: .78rem; font-weight: 800;
        color: #fff; background: var(--color-brand-600); }
    .np-sub { margin-top: .25rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-500); }
    .np-label { display: block; margin: .85rem 0 .35rem; font-size: .76rem; font-weight: 800; color: var(--color-gray-700); }
    .np-label span { color: var(--color-gray-400); font-weight: 600; }
    .np-hint { display: block; margin-top: .3rem; font-size: .72rem; line-height: 1.45; color: var(--color-gray-500); }
    .np-row { display: flex; gap: .5rem; } .np-row > * { min-width: 0; }
    .np-two { display: grid; gap: 0 .8rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 560px) { .np-two { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

    /* A unit, or any small choice: a tag that opens a sheet. */
    .np-utag { display: inline-flex; align-items: center; justify-content: space-between; gap: .35rem; flex: none; min-width: 5.4rem; max-width: 55%; padding: 0 .7rem; border-radius: .75rem;
        font-size: .82rem; font-weight: 800; color: #3d6823; background: var(--color-brand-50, #f3f8ec); border: 1px solid var(--color-brand-200, #c9e0ad); cursor: pointer;
        transition: border-color .28s var(--np-ease), background-color .28s var(--np-ease), transform .28s var(--np-ease); }
    .np-utag:hover { border-color: var(--color-brand-500, #4a7c2a); transform: translateY(-1px); }
    .np-utag span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .np-utag svg { width: .85rem; height: .85rem; flex: none; color: var(--color-gray-400); }
    .np-utag.is-inline { min-height: 2.4rem; }
    html.dark .np-utag { background: #1c2c10; border-color: #2f4a1a; color: #a5c97e; }
    .np-pills { display: flex; flex-wrap: wrap; gap: .35rem; }
    .np-pill { display: inline-flex; align-items: center; gap: .3rem; padding: .45rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 700; cursor: pointer;
        color: var(--color-gray-700); background: var(--color-white); border: 1px solid var(--color-gray-200);
        transition: background-color .28s var(--np-ease), border-color .28s var(--np-ease), color .28s var(--np-ease), transform .28s var(--np-ease); }
    .np-pill:hover { border-color: var(--color-brand-600); }
    .np-pill[aria-pressed="true"] { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }

    /* The crop sheet: the same catalogue rows the other analyses use. */
    .crop-search { position: relative; margin-bottom: .6rem; }
    .crop-search svg { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); width: 1.05rem; height: 1.05rem; color: var(--color-gray-400); pointer-events: none; }
    .crop-search .form-input { padding-left: 2.4rem; padding-right: 2.2rem; }
    .crop-search-x { position: absolute; right: .5rem; top: 50%; transform: translateY(-50%); width: 1.6rem; height: 1.6rem; border-radius: 999px; color: var(--color-gray-400); }
    .crop-search-x:hover { background: var(--color-gray-100); }
    .crop-group-h { font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); margin: .8rem 0 .25rem; }
    .crop-row { display: flex; align-items: center; gap: .65rem; width: 100%; text-align: left; padding: .5rem .6rem; border-radius: .7rem; cursor: pointer;
        transition: background-color .28s var(--np-ease); }
    .crop-row:hover, .crop-row.is-on { background: var(--color-brand-50); }
    .crop-row-e { font-size: 1.25rem; line-height: 1; flex: none; }
    .crop-row-t { min-width: 0; }
    .crop-row-t b { display: block; font-size: .875rem; font-weight: 700; color: var(--color-gray-900); }
    .crop-row-t small { display: block; font-size: .7rem; color: var(--color-gray-400); }
    .crop-none { font-size: .8rem; color: var(--color-gray-400); text-align: center; padding: 1rem 0; }
    html.dark .crop-row:hover, html.dark .crop-row.is-on { background: #22301a; }
    html.dark .crop-row-t b { color: #e8efe1; }

    /* The fertilizers in the plan. */
    .np-lines { display: grid; gap: .5rem; margin-top: .8rem; }
    .np-line { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .45rem .7rem; align-items: center; padding: .7rem .75rem; border-radius: .9rem;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-100); animation: npIn .3s var(--np-ease) both; }
    .np-line.is-flash { animation: npFlash .9s var(--np-ease) both; }
    @keyframes npFlash { 0% { box-shadow: 0 0 0 0 rgb(74 124 42 / .45); } 100% { box-shadow: 0 0 0 10px rgb(74 124 42 / 0); } }
    html.dark .np-line { background: #121a0d; border-color: #2b3a1c; }
    .np-line b { display: block; font-size: .86rem; color: var(--color-gray-900); }
    .np-line small { display: block; font-size: .72rem; color: var(--color-gray-500); }
    .np-gb { display: inline-block; margin-left: .25rem; font-size: .68rem; font-weight: 800; padding: .08rem .4rem; border-radius: 999px; color: #2d5016; background: #e4efd4; vertical-align: 1px; }
    .np-qty { grid-column: 1 / -1; justify-self: start; display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 800;
        color: #3d6823; background: var(--color-white); border: 1px solid var(--color-brand-200, #c9e0ad); cursor: pointer; transition: border-color .28s var(--np-ease), transform .28s var(--np-ease); }
    .np-qty:hover { border-color: var(--color-brand-500, #4a7c2a); transform: translateY(-1px); }
    .np-qty svg { width: .8rem; height: .8rem; color: var(--color-gray-400); }
    html.dark .np-qty { background: #151b12; border-color: #2f4a1a; color: #a5c97e; }
    .np-x { width: 1.9rem; height: 1.9rem; border-radius: 999px; display: grid; place-items: center; border: 1px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-500); cursor: pointer; }
    .np-empty { padding: 1rem; border-radius: .9rem; text-align: center; font-size: .84rem; color: var(--color-gray-500); border: 1px dashed var(--color-gray-200); }
    .np-add { display: flex; align-items: center; justify-content: center; gap: .45rem; width: 100%; margin-top: .7rem; padding: .75rem 1rem; border-radius: .95rem; cursor: pointer;
        font-weight: 800; font-size: .9rem; color: #fff; background: var(--color-brand-600); border: 0; transition: transform .28s var(--np-ease), filter .28s var(--np-ease); }
    .np-add:hover { transform: translateY(-1px); filter: brightness(1.06); }
    .np-add svg { width: 1.05rem; height: 1.05rem; }

    /* The product sheet. */
    .np-cats { display: flex; gap: .3rem; overflow-x: auto; scrollbar-width: none; margin: 0 -.1rem .3rem; padding: .1rem; }
    .np-cats::-webkit-scrollbar { display: none; }
    .np-cat { flex: none; padding: .38rem .7rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: var(--color-gray-600); background: var(--color-gray-100); border: 0; cursor: pointer;
        transition: background-color .28s var(--np-ease), color .28s var(--np-ease); }
    .np-cat.is-on { background: #1f2937; color: #fff; }
    html.dark .np-cat { background: #1c2616; } html.dark .np-cat.is-on { background: #e8efe1; color: #151b12; }
    .np-prow { display: flex; align-items: center; gap: .65rem; width: 100%; padding: .55rem .6rem; border-radius: .8rem; text-align: left; cursor: pointer; border: 1px solid transparent;
        transition: background-color .28s var(--np-ease), border-color .28s var(--np-ease); }
    .np-prow:hover { background: var(--color-brand-50); }
    .np-prow.is-in { border-color: var(--color-brand-200, #c9e0ad); background: #f6faf0; }
    html.dark .np-prow:hover { background: #22301a; } html.dark .np-prow.is-in { background: #1a2712; border-color: #2f4a1a; }
    .np-prow-g { flex: none; width: 4.7rem; padding: .3rem .3rem; border-radius: .6rem; text-align: center; font-size: .68rem; font-weight: 800; color: #2d5016; background: #e4efd4; }
    .np-prow-t { min-width: 0; flex: 1 1 auto; }
    .np-prow-t b { display: block; font-size: .86rem; color: var(--color-gray-900); }
    .np-prow-t small { display: block; font-size: .7rem; line-height: 1.4; color: var(--color-gray-500); }
    html.dark .np-prow-t b { color: #e8efe1; }
    .np-prow em { flex: none; font-style: normal; font-size: .66rem; font-weight: 800; padding: .15rem .45rem; border-radius: 999px; color: #2d5016; background: #e4efd4; }
    .np-prow > svg { width: 1rem; height: 1rem; flex: none; color: var(--color-brand-600); }
    .np-prow.is-new { margin-top: .6rem; border: 1px dashed var(--color-gray-300, #cbd5e1); }
    .np-prow.is-new .np-prow-g { background: var(--color-gray-100); color: var(--color-gray-600); }

    /* The amount sheet. */
    .np-qprod { display: flex; gap: .65rem; align-items: flex-start; padding: .7rem; border-radius: .9rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    html.dark .np-qprod { background: #121a0d; border-color: #2b3a1c; }
    .np-qstep { display: grid; grid-template-columns: 3rem minmax(0, 1fr) 3rem; gap: .4rem; }
    .np-qstep button { border-radius: .8rem; font-size: 1.35rem; font-weight: 800; color: var(--color-brand-700, #3d6823); background: var(--color-brand-50); border: 1px solid var(--color-brand-200, #c9e0ad); cursor: pointer; }
    html.dark .np-qstep button { background: #1c2c10; border-color: #2f4a1a; color: #a5c97e; }
    .np-qstep input { text-align: center; font-size: 1.25rem; font-weight: 800; }
    .np-qsum { margin-top: .8rem; padding: .7rem .8rem; border-radius: .9rem; font-size: .82rem; line-height: 1.55; color: #24400f; background: #f3f8ec; border: 1px solid #c9e0ad; }
    html.dark .np-qsum { color: #d5e3c5; background: #17220f; border-color: #2b3a1c; }
    .np-cgrid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem; margin-top: .5rem; }
    @media (min-width: 560px) { .np-cgrid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .np-cgrid label { font-size: .7rem; font-weight: 800; color: var(--color-gray-600); }
    .np-cgrid input { margin-top: .15rem; }

    .np-soil { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .28s var(--np-ease); }
    .np-soil.is-on { grid-template-rows: 1fr; }
    .np-soil > div { min-height: 0; overflow: hidden; }
    .np-switch { display: flex; align-items: center; gap: .6rem; cursor: pointer; font-size: .84rem; font-weight: 700; color: var(--color-gray-700); }
    .np-switch input { width: 1.1rem; height: 1.1rem; accent-color: var(--color-brand-600); }
    .np-soilu { display: flex; flex-wrap: wrap; gap: .5rem .9rem; margin-top: .7rem; }
    .np-soilu label { display: flex; align-items: center; gap: .45rem; font-size: .74rem; font-weight: 800; color: var(--color-gray-600); }
    .np-soilu .np-utag { min-height: 2.1rem; min-width: 0; }

    /* What the plan gives: three tabs. */
    .np-otabs { position: relative; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: .8rem; padding: .25rem; border-radius: .95rem; background: var(--color-gray-100); }
    html.dark .np-otabs { background: #121a0d; }
    .np-otab { position: relative; z-index: 1; padding: .5rem .3rem; border: 0; background: none; border-radius: .75rem; font-size: .82rem; font-weight: 800; color: var(--color-gray-500); cursor: pointer;
        transition: color .28s var(--np-ease); }
    .np-otab.is-on { color: #fff; }
    .np-otab-ind { position: absolute; z-index: 0; top: .25rem; bottom: .25rem; left: .25rem; width: calc((100% - .5rem) / 3); border-radius: .75rem; background: var(--color-brand-600);
        transform: translateX(calc(var(--i, 0) * 100%)); transition: transform .28s var(--np-ease); box-shadow: 0 6px 14px -8px rgb(45 80 22 / .8); }
    .np-pane { animation: npIn .32s var(--np-ease) both; }
    .np-pane[hidden] { display: none; }
    @keyframes npIn { from { opacity: 0; transform: translateY(4px); } }
    .np-big { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .5rem; margin-top: .8rem; }
    .np-big div { border-radius: .95rem; padding: .7rem .5rem; text-align: center; color: #fff; }
    .np-big div:nth-child(1) { background: linear-gradient(135deg, #2d5016, #4a7c2a); }
    .np-big div:nth-child(2) { background: linear-gradient(135deg, #9a3412, #ea580c); }
    .np-big div:nth-child(3) { background: linear-gradient(135deg, #5b21b6, #8b5cf6); }
    .np-big small { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .04em; opacity: .9; }
    .np-big b { display: block; font-family: var(--font-heading); font-size: 1.45rem; font-weight: 800; line-height: 1.15; }
    .np-big i { display: block; font-style: normal; font-size: .68rem; opacity: .85; }
    .np-table { width: 100%; margin-top: .8rem; font-size: .8rem; border-collapse: collapse; }
    .np-table th, .np-table td { padding: .45rem .35rem; border-bottom: 1px solid var(--color-gray-100); text-align: right; color: var(--color-gray-700); }
    .np-table th:first-child, .np-table td:first-child { text-align: left; }
    .np-table th { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: var(--color-gray-500); }
    .np-need { display: grid; gap: .6rem; margin-top: .6rem; }
    .np-need-row { display: grid; grid-template-columns: 3.3rem minmax(0, 1fr) 4.3rem; gap: .5rem; align-items: center; font-size: .76rem; font-weight: 800; color: var(--color-gray-700); }
    .np-need-bar { position: relative; height: .7rem; border-radius: 999px; background: var(--color-gray-100); overflow: visible; }
    .np-need-bar .band { position: absolute; top: 0; bottom: 0; border-radius: 999px; background: rgb(102 189 99 / .28); }
    .np-need-bar .have { position: absolute; left: 0; top: .15rem; bottom: .15rem; border-radius: 999px; background: #4a7c2a; transition: width .5s var(--np-ease); }
    .np-need-row.is-short .have { background: #f59e0b; } .np-need-row.is-over .have { background: #dc2626; }
    .np-need-row em { font-style: normal; text-align: right; font-size: .7rem; }
    .np-need-row.is-short em { color: #b45309; } .np-need-row.is-over em { color: #b91c1c; } .np-need-row.is-right em { color: #2d5016; }
    .np-say { margin-top: .8rem; padding: .75rem .85rem; border-radius: .9rem; font-size: .84rem; line-height: 1.55; color: #24400f; background: #f3f8ec; border: 1px solid #c9e0ad; }
    html.dark .np-say { color: #d5e3c5; background: #17220f; border-color: #2b3a1c; }
    .np-src { margin-top: .7rem; font-size: .76rem; line-height: 1.55; color: var(--color-gray-600); }
    .np-src summary { cursor: pointer; font-weight: 800; color: var(--color-brand-700); }
    .np-src p, .np-src ul { margin-top: .4rem; }
    .np-src ul { padding-left: 1.1rem; list-style: disc; }
    .np-src a { color: var(--color-brand-700); text-decoration: underline; overflow-wrap: anywhere; }
    .np-fine { margin-top: .8rem; font-size: .72rem; line-height: 1.55; color: var(--color-gray-500); }

    /* The micros tab. */
    .np-mh { margin: 1rem 0 .4rem; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); }
    .np-mlist { display: grid; gap: .4rem; }
    .np-mrow { display: grid; grid-template-columns: 2.4rem minmax(0, 1fr) auto; gap: .65rem; align-items: center; padding: .55rem .65rem; border-radius: .85rem;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-100); animation: npIn .3s var(--np-ease) both; animation-delay: calc(var(--k, 0) * 35ms); }
    html.dark .np-mrow { background: #121a0d; border-color: #2b3a1c; }
    .np-mrow > i { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: .7rem; font-style: normal; font-family: var(--font-heading); font-weight: 800; font-size: .9rem; color: #fff;
        background: linear-gradient(135deg, #0f766e, #14b8a6); }
    .np-mrow.is-sec > i { background: linear-gradient(135deg, #a16207, #eab308); }
    .np-mrow.is-none > i { background: var(--color-gray-200); color: var(--color-gray-500); }
    .np-mrow b { display: block; font-size: .84rem; color: var(--color-gray-900); }
    .np-mrow small { display: block; font-size: .7rem; line-height: 1.4; color: var(--color-gray-500); }
    .np-mrow small.is-warn { color: #b45309; font-weight: 700; }
    .np-mval { text-align: right; }
    .np-mval b { font-family: var(--font-heading); font-size: .95rem; }
    .np-mrow.is-none .np-mval b { color: var(--color-gray-400); font-family: inherit; font-size: .76rem; }
    .np-mnone { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem; padding: .55rem .65rem; border-radius: .85rem; font-size: .74rem; font-weight: 700; color: var(--color-gray-500);
        border: 1px dashed var(--color-gray-200); animation: npIn .3s var(--np-ease) both; }
    .np-mnone span { padding: .15rem .5rem; border-radius: 999px; font-weight: 800; color: var(--color-gray-600); background: var(--color-gray-100); }
    html.dark .np-mnone span { background: #1c2616; color: #b7c4aa; }

    /* Liebig's barrel: CSS in 3D, turned by a finger or a mouse. */
    .lb { margin-top: .4rem; }
    .lb-stage { position: relative; height: 21rem; display: grid; place-items: center; perspective: 900px; perspective-origin: 50% 28%; cursor: grab; touch-action: pan-y;
        user-select: none; -webkit-user-select: none; border-radius: 1rem; outline: none;
        background: radial-gradient(70% 45% at 50% 88%, rgb(74 124 42 / .16), transparent 70%), linear-gradient(180deg, rgb(125 211 252 / .1), transparent 55%); }
    .lb-stage:focus-visible { box-shadow: 0 0 0 3px rgb(74 124 42 / .45); }
    .lb-stage.is-grab { cursor: grabbing; }
    .lb-barrel { position: relative; width: 160px; height: 190px; margin-top: 3.2rem; transform-style: preserve-3d; transform: rotateX(-16deg) rotateY(-18deg); }
    .lb-st { position: absolute; left: 50%; bottom: 0; height: 190px; transform-style: preserve-3d;
        transition: height .9s var(--np-ease); transition-delay: var(--d, 0ms); }
    .lb-out, .lb-in { position: absolute; inset: 0; backface-visibility: hidden; -webkit-backface-visibility: hidden; overflow: hidden; border-radius: 3px 3px 0 0; }
    .lb-out { background:
            linear-gradient(180deg, transparent calc(100% - 46px), #2f3439 calc(100% - 46px), #6b737c calc(100% - 41px), #2f3439 calc(100% - 37px), transparent calc(100% - 37px)),
            repeating-linear-gradient(90deg, transparent 0 6px, rgb(60 30 5 / .13) 6px 7px),
            linear-gradient(90deg, #6f4519 0%, #a86d34 16%, #c4874a 50%, #a86d34 84%, #6f4519 100%);
        box-shadow: inset 0 3px 0 rgb(255 255 255 / .18); }
    .lb-out::after { content: ""; position: absolute; left: 0; right: 0; bottom: 140px; height: 9px; background: linear-gradient(180deg, #2f3439, #6b737c 50%, #2f3439); }
    .lb-in { transform: rotateY(180deg); background: repeating-linear-gradient(90deg, transparent 0 6px, rgb(0 0 0 / .12) 6px 7px), linear-gradient(90deg, #4a2c0f, #6b4219 50%, #4a2c0f); }
    .lb-out b { position: absolute; left: 0; right: 0; bottom: 14px; text-align: center; font-family: var(--font-heading); font-size: .72rem; font-weight: 800; line-height: 1; color: #fff8e6;
        text-shadow: 0 1px 2px rgb(0 0 0 / .6); }
    .lb-out small { position: absolute; left: 0; right: 0; bottom: 3px; text-align: center; font-size: .56rem; font-weight: 800; line-height: 1; color: #ffe9b8; text-shadow: 0 1px 2px rgb(0 0 0 / .6); }
    .lb-st.is-short .lb-out { box-shadow: inset 0 4px 0 #f59e0b; }
    .lb-st.is-short .lb-out b { color: #fde68a; }
    .lb-st.is-over .lb-out { box-shadow: inset 0 5px 0 #dc2626; }
    /* Solid planks, never see-through: a plank that is not checked is pale
       raw wood, a plank NPK Plus cannot measure is weathered grey. */
    .lb-st.is-unknown .lb-out, .lb-st.is-unknown .lb-in { filter: saturate(.25) brightness(1.22); }
    .lb-st.is-dull .lb-out, .lb-st.is-dull .lb-in { filter: grayscale(1) brightness(.8); }
    .lb-st.is-limit .lb-out b { padding: 2px 0; background: rgb(180 83 9 / .85); }
    .lb-spill { position: absolute; right: 3px; top: -3px; bottom: 0; width: 8px; transform: translateZ(2px); backface-visibility: hidden; -webkit-backface-visibility: hidden;
        border-radius: 5px 5px 2px 2px; opacity: 0; transition: opacity .5s var(--np-ease) .9s;
        background: repeating-linear-gradient(180deg, rgb(147 205 255 / .95) 0 9px, rgb(56 140 225 / .85) 9px 18px); background-size: 100% 18px; animation: lbFlow .55s linear infinite; }
    .lb-st.is-spill .lb-spill { opacity: .92; }
    @keyframes lbFlow { to { background-position: 0 18px; } }
    .lb-water { position: absolute; inset: 0; transform-style: preserve-3d; transform-origin: 50% 100%; transform: scaleY(var(--f, .001));
        transition: transform 1.25s var(--np-ease) .35s; }
    .lb-wf { position: absolute; left: 50%; bottom: 0; height: 190px; background: linear-gradient(180deg, rgb(120 190 245 / .5), rgb(30 100 190 / .7)); }
    .lb-top { position: absolute; left: 50%; top: 0; border-radius: 999px; transform: translateY(-50%) rotateX(90deg);
        background: radial-gradient(circle at 38% 40%, rgb(214 240 255 / .95), rgb(96 172 236 / .9) 45%, rgb(36 108 200 / .92) 80%); background-size: 160% 160%;
        animation: lbShine 4.5s ease-in-out infinite alternate; }
    @keyframes lbShine { to { background-position: 100% 60%; } }
    .lb-floor, .lb-shadow, .lb-rim { position: absolute; left: 50%; border-radius: 999px; transform: translateY(-50%) rotateX(90deg); }
    .lb-floor { background: radial-gradient(circle, #6b4219, #4a2c0f); }
    .lb-shadow { background: radial-gradient(closest-side, rgb(20 33 12 / .38), rgb(20 33 12 / 0)); }
    .lb-rim { border: 2px dashed rgb(74 124 42 / .7); }
    html.dark .lb-rim { border-color: rgb(165 201 126 / .7); }
    .lb-barrel.is-rest .lb-st { height: 22px !important; transition: none; }
    .lb-barrel.is-rest .lb-water { transform: scaleY(.001) !important; transition: none; }
    .lb-barrel.is-rest .lb-spill { opacity: 0 !important; transition: none; }
    .lb-tag { position: absolute; left: .7rem; top: .6rem; max-width: calc(100% - 1.4rem); padding: .3rem .6rem; border-radius: .7rem; font-size: .7rem; font-weight: 700; line-height: 1.35;
        color: #2d5016; background: rgb(255 255 255 / .85); border: 1px dashed rgb(74 124 42 / .6); pointer-events: none; }
    html.dark .lb-tag { color: #d5e3c5; background: rgb(21 27 18 / .85); }
    .lb-turn { position: absolute; right: .7rem; bottom: .6rem; display: flex; align-items: center; gap: .3rem; font-size: .68rem; font-weight: 700; color: var(--color-gray-500);
        pointer-events: none; transition: opacity .5s var(--np-ease); }
    .lb-turn svg { width: 1rem; height: 1rem; animation: lbNudge 2.4s ease-in-out infinite; }
    @keyframes lbNudge { 50% { transform: translateX(4px); } }
    .lb-stage.was-turned .lb-turn { opacity: 0; }
    .lb-key { display: grid; gap: .3rem; margin-top: .7rem; }
    @media (min-width: 560px) { .lb-key { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .3rem .6rem; } }
    .lb-k { display: grid; grid-template-columns: .7rem minmax(0, 1fr) 3.4rem auto; gap: .45rem; align-items: center; width: 100%; padding: .35rem .45rem; border-radius: .6rem; text-align: left;
        font-size: .74rem; cursor: pointer; border: 0; background: none; transition: background-color .28s var(--np-ease); }
    .lb-k:hover { background: var(--color-gray-50); }
    html.dark .lb-k:hover { background: #121a0d; }
    .lb-k > i { width: .7rem; height: 1.1rem; border-radius: 2px; background: linear-gradient(90deg, #6f4519, #c4874a, #6f4519); }
    .lb-k.is-dull > i { filter: grayscale(1); opacity: .7; } .lb-k.is-unknown > i { opacity: .45; }
    .lb-k b { font-weight: 800; color: var(--color-gray-800); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lb-k b small { font-weight: 700; color: var(--color-gray-400); }
    .lb-kbar { position: relative; height: .4rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .lb-kbar span { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 999px; background: #4a7c2a; transition: width .6s var(--np-ease); }
    .lb-k.is-short .lb-kbar span { background: #f59e0b; } .lb-k.is-over .lb-kbar span { background: #dc2626; }
    .lb-k.is-dull .lb-kbar span, .lb-k.is-unknown .lb-kbar span { background: var(--color-gray-300, #cbd5e1); }
    .lb-k em { font-style: normal; font-size: .66rem; font-weight: 800; text-align: right; color: var(--color-gray-500); white-space: nowrap; }
    .lb-k.is-short em { color: #b45309; } .lb-k.is-over em { color: #b91c1c; } .lb-k.is-right em, .lb-k.is-added em, .lb-k.is-fixed em { color: #2d5016; }

    .np-acts { display: grid; gap: .5rem; margin-top: .9rem; }
    .np-anee { display: flex; align-items: center; justify-content: center; gap: .5rem; padding: .85rem 1rem; border-radius: .95rem; border: 0; cursor: pointer; font-weight: 800;
        color: #1a1a1a; background: linear-gradient(135deg, #f7d23a, #f5c518); transition: transform .28s var(--np-ease); }
    .np-anee:hover { transform: translateY(-1px); } .np-anee:disabled { opacity: .5; transform: none; cursor: default; }
    .np-anee img { width: 1.6rem; height: 1.6rem; border-radius: 999px; }
    .np-mbar { position: fixed; left: 0; right: 0; bottom: calc(4.2rem + env(safe-area-inset-bottom)); z-index: 30; display: flex; gap: .5rem; align-items: center; justify-content: space-between;
        margin: 0 .8rem; padding: .6rem .8rem; border-radius: 1rem; color: #fff; background: rgb(21 33 12 / .94); box-shadow: 0 14px 30px -16px rgb(0 0 0 / .8);
        transform: translateY(150%); transition: transform .32s var(--np-ease); }
    .np-mbar.is-on { transform: none; }
    .np-mbar b { font-family: var(--font-heading); font-size: 1rem; }
    .np-mbar button { padding: .45rem .8rem; border-radius: 999px; border: 0; font-size: .76rem; font-weight: 800; color: #1a1a1a; background: #f5c518; cursor: pointer; }
    @media (min-width: 1024px) { .np-mbar { display: none; } }
    .np-rep { margin-top: 1rem; }
    .np-rep[hidden] { display: none; }
    .np-rhead { border-radius: 1.1rem; padding: 1rem 1.1rem; color: #fff; background: radial-gradient(120% 140% at 100% 0%, #4a7c2a 0%, #14250a 65%); }
    .np-rhead.v-needs-changes, .np-rhead.v-unbalanced { background: radial-gradient(120% 140% at 100% 0%, #b45309 0%, #3d1d02 65%); }
    .np-rhead small { font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; opacity: .85; }
    .np-rhead h3 { margin-top: .2rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.12rem; color: #fff; }
    .np-rhead p { margin-top: .35rem; font-size: .84rem; line-height: 1.55; opacity: .92; }
    .np-rlist { display: grid; gap: .45rem; margin-top: .5rem; }
    .np-ritem { padding: .6rem .7rem; border-radius: .8rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); font-size: .84rem; color: var(--color-gray-700); line-height: 1.5; }
    html.dark .np-ritem { background: #121a0d; border-color: #2b3a1c; }
    .np-ritem b { color: var(--color-gray-900); }
    .np-st { display: inline-block; margin-right: .35rem; font-size: .64rem; font-weight: 800; text-transform: uppercase; padding: .12rem .45rem; border-radius: 999px; background: #e4efd4; color: #2d5016; }
    .np-st.s-short { background: #fff1c2; color: #8a5a00; } .np-st.s-over { background: #fde2e1; color: #b42318; }
    .np-saved { display: grid; gap: .5rem; }
    .np-srow { display: flex; gap: .7rem; align-items: center; width: 100%; padding: .7rem .8rem; border-radius: .95rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        text-align: left; cursor: pointer; transition: transform .28s var(--np-ease); }
    .np-srow:hover { transform: translateY(-1px); }
    .np-srow span { min-width: 0; flex: 1 1 auto; }
    .np-srow b { display: block; font-size: .86rem; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .np-srow small { font-size: .74rem; color: var(--color-gray-500); }
    .np-srow em { flex: none; font-style: normal; font-size: .7rem; font-weight: 800; padding: .2rem .5rem; border-radius: 999px; background: #fef3c7; color: #8a5a00; }
    @media (prefers-reduced-motion: reduce) {
        .np-chem span, .np-line, .np-line.is-flash, .np-pane, .np-mrow, .lb-spill, .lb-top, .lb-turn svg { animation: none; }
        .np-hero-fold, .np-hero-chev, .np-soil, .np-mbar, .np-need-bar .have, .np-pill, .np-otab-ind, .lb-st, .lb-water, .lb-kbar span, .np-utag, .np-qty, .np-add { transition: none; }
    }
    html.sm-still .lb-spill, html.sm-still .lb-top, html.sm-still .np-chem span { animation: none; }
</style>

@php $chev = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>'; @endphp
<div class="np-wrap">
    <section class="np-hero" id="npHero">
        <button type="button" class="np-hero-head" id="npHeroHead" aria-expanded="false" aria-controls="npHeroBody">
            <span class="np-chem" aria-hidden="true"><span>N</span><span>P</span><span>K</span></span>
            <span class="np-hero-t"><b>Know exactly what your fertilizer gives your crop</b><i id="npHeroSays">Tap to see how it works</i></span>
            <svg class="np-hero-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
        </button>
        <div class="np-hero-fold" id="npHeroBody"><div><div class="np-hero-in">
            <p>NPK Plus adds up every nutrient in your plan, checks it against what the crop needs, and shows the shortest plank in the barrel. Free, and as many times as you like.</p>
            <ol class="np-steps">
                <li>Pick the crop, then say the variety, what you plant and the area.</li>
                <li>Add each fertilizer you plan to use, and how much.</li>
                <li>Read the NPK, the micronutrients and Liebig's barrel. Anee can read it further.</li>
            </ol>
        </div></div></div>
    </section>

    <div class="np-tabs" role="tablist">
        <button type="button" class="np-tab is-on" id="npTabCalc" role="tab">Calculator</button>
        <button type="button" class="np-tab" id="npTabSaved" role="tab">Saved</button>
    </div>

    <div id="npCalc">
        <div class="np-grid">
            <div>
                <div class="np-card">
                    <h3><i>1</i>Your crop and field</h3>
                    <span class="np-label">Crop</span>
                    <button type="button" class="crop-tag" id="npCropBtn">
                        <span class="crop-tag-e" id="npCropIcon">🌱</span>
                        <span class="crop-tag-t is-none" id="npCropNow">Choose the crop</span>
                        <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <div class="np-two">
                        <div>
                            <label class="np-label" for="npVariety">Variety <span>(optional)</span></label>
                            <input type="text" id="npVariety" class="form-input w-full" maxlength="80" autocomplete="off" placeholder="{{ \App\Support\Region::ph() ? 'Like NSIC Rc222' : 'Like Pioneer P1197' }}">
                            <span class="np-hint">The name on the seed bag.</span>
                        </div>
                        <div>
                            <label class="np-label" for="npPotential">Potential yield of the variety <span>(optional)</span></label>
                            <div class="np-row"><input type="number" id="npPotential" class="form-input flex-1" min="0" step="any" inputmode="decimal" placeholder="Like 8">
                                <button type="button" class="np-utag is-inline" id="npPotUnit" aria-label="Yield unit"><span>t/ha</span>{!! $chev !!}</button></div>
                            <span class="np-hint" id="npPotHint">On the seed bag or the variety's description.</span>
                        </div>
                        <div>
                            <label class="np-label" for="npSeed">Seeds or planting material <span>(optional)</span></label>
                            <div class="np-row"><input type="number" id="npSeed" class="form-input flex-1" min="0" step="any" inputmode="decimal" placeholder="Like 40">
                                <button type="button" class="np-utag is-inline" id="npSeedUnit" aria-label="What you plant"><span>kg</span>{!! $chev !!}</button></div>
                            <span class="np-hint" id="npSeedHint">How much you plant on the whole area.</span>
                        </div>
                        <div>
                            <label class="np-label" for="npArea">Area</label>
                            <div class="np-row"><input type="number" id="npArea" class="form-input flex-1" min="0" step="any" value="1" inputmode="decimal">
                                <button type="button" class="np-utag is-inline" id="npAreaUnit" aria-label="Area unit"><span>ha</span>{!! $chev !!}</button></div>
                        </div>
                    </div>
                </div>

                <div class="np-card">
                    <h3><i>2</i>Your fertilizers</h3>
                    <p class="np-sub">Add each product you plan to use, and how much for the whole area. Not on the list? Add it from its label.</p>
                    <div class="np-lines" id="npLines"></div>
                    <button type="button" class="np-add" id="npAddBtn"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>Add a fertilizer</button>
                </div>

                <div class="np-card">
                    <label class="np-switch"><input type="checkbox" id="npSoilOn"> <span><b>I have a soil test</b><br><span class="text-xs text-gray-500 font-normal">The result decides whether your soil needs the low or the high end of the rate, and fills more planks of the barrel.</span></span></label>
                    <div class="np-soil" id="npSoil"><div>
                        <div class="np-cgrid" id="npSoilGrid"></div>
                        <div class="np-soilu">
                            <label>P method <button type="button" class="np-utag" id="npPMethod"><span>Olsen</span>{!! $chev !!}</button></label>
                            <label>K in <button type="button" class="np-utag" id="npKUnit"><span>ppm</span>{!! $chev !!}</button></label>
                        </div>
                    </div></div>
                </div>
            </div>

            <div class="np-out">
                <div class="np-card" id="npOut">
                    <h3><i>3</i>What your plan gives</h3>
                    <p class="np-sub" id="npOutSub">Add a fertilizer to see the totals.</p>
                    <div class="np-otabs" role="tablist" id="npOTabs" style="--i:0">
                        <span class="np-otab-ind" aria-hidden="true"></span>
                        <button type="button" class="np-otab is-on" role="tab" aria-selected="true" data-p="npk">NPK</button>
                        <button type="button" class="np-otab" role="tab" aria-selected="false" data-p="micro">Micros</button>
                        <button type="button" class="np-otab" role="tab" aria-selected="false" data-p="barrel">Barrel</button>
                    </div>

                    <div class="np-pane" id="npPaneNpk" role="tabpanel">
                        <div class="np-big"><div><small>N</small><b id="npN">0</b><i>kg per ha</i></div><div><small>P₂O₅</small><b id="npP">0</b><i>kg per ha</i></div><div><small>K₂O</small><b id="npK">0</b><i>kg per ha</i></div></div>
                        <div id="npTable"></div>
                        <div id="npNeed"></div>
                        <div id="npSay"></div>
                    </div>

                    <div class="np-pane" id="npPaneMicro" role="tabpanel" hidden>
                        <div id="npMicro"></div>
                    </div>

                    <div class="np-pane" id="npPaneBarrel" role="tabpanel" hidden>
                        <div class="lb">
                            <div class="lb-stage" id="lbStage" tabindex="0" role="group" aria-label="Liebig's barrel. Drag, or use the arrow keys, to turn it.">
                                <div class="lb-barrel is-rest" id="lbBarrel"></div>
                                <span class="lb-tag" id="lbTag">The rim is everything the crop needs.</span>
                                <span class="lb-turn" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12h15m0 0l-4-4m4 4l-4 4"/></svg>Drag to turn</span>
                            </div>
                            <div class="lb-key" id="lbKey"></div>
                            <div id="lbSay"></div>
                        </div>
                    </div>

                    <p class="np-fine">A guide, not a promise. The crop's real need and the yield it gives depend on the weather, the variety, the soil type and how and when the fertilizer goes on. Biofertilizer values are field trial estimates that vary a lot. A soil test makes this surer.</p>
                    <div class="np-acts">
                        <button type="button" class="btn btn-white" id="npSave">Save this calculation</button>
                        <button type="button" class="np-anee" id="npAnee"><img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="">Analyze further with Anee</button>
                    </div>
                </div>
                <div class="np-rep" id="npRep" hidden></div>
            </div>
        </div>
        <div class="np-mbar" id="npMbar"><span>N P₂O₅ K₂O per ha <b id="npMbarV">0-0-0</b></span><button type="button" id="npMbarGo">See the totals</button></div>
    </div>

    <div id="npSavedPane" hidden>
        <input type="search" id="npSavedQ" class="form-input mb-3" placeholder="Search your calculations" autocomplete="off">
        <div class="np-saved" id="npSaved"></div>
        <p class="text-center text-sm text-gray-400 py-8" id="npSavedEmpty" hidden>Saved calculations land here.</p>
        <button type="button" class="btn btn-white w-full mt-3" id="npSavedMore" hidden>Show more</button>
    </div>
</div>

{{-- The crop: the whole table, searchable, in the catalogue's groups. --}}
<div class="sheet hidden" id="npCropSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Choose a crop</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="crop-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" id="npCropSearch" class="form-input" autocomplete="off" placeholder="{{ \App\Support\Region::t('cropSearch') }}">
            <button type="button" class="crop-search-x hidden" id="npCropSearchX" aria-label="Clear">✕</button>
        </div>
        <div id="npCropList"></div>
        <p class="crop-none hidden" id="npCropNone">No numbers for that crop yet. Pick the closest one, or No crop to see the totals only.</p>
    </div>
</div>

{{-- One sheet for every small choice: units, the soil test's method. --}}
<div class="sheet hidden" id="npPickSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="npPickTitle">Choose</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="form-hint mt-0 mb-3" id="npPickHint" hidden></p>
        <div class="dt-rows" id="npPickRows"></div>
    </div>
</div>

{{-- Add a fertilizer, step one: which product. --}}
<div class="sheet hidden" id="npProdSheet" style="--sheet-width:32rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Add a fertilizer</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="crop-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" id="npProdQ" class="form-input" autocomplete="off" placeholder="Find a product, like urea or 14-14-14">
            <button type="button" class="crop-search-x hidden" id="npProdQX" aria-label="Clear">✕</button>
        </div>
        <div class="np-cats" id="npCats"></div>
        <div id="npProdList"></div>
        <button type="button" class="np-prow is-new" id="npCustomBtn"><span class="np-prow-g">Label</span><span class="np-prow-t"><b>A product not on the list</b><small>Type what its label says, once. It stays in My products.</small></span>
            <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg></button>
    </div>
</div>

{{-- Add a fertilizer, step two: how much. --}}
<div class="sheet hidden" id="npQtySheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="npQtyTitle">How much?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="np-qprod" id="npQtyProd"></div>
        <label class="np-label" for="npQtyIn" id="npQtyFor">For the whole area</label>
        <div class="np-qstep">
            <button type="button" id="npQtyMinus" aria-label="Less">−</button>
            <input type="number" id="npQtyIn" class="form-input" min="0" step="any" inputmode="decimal">
            <button type="button" id="npQtyPlus" aria-label="More">+</button>
        </div>
        <span class="np-label">In</span>
        <div class="np-pills" id="npQtyUnits"></div>
        <div class="np-qsum" id="npQtySum"></div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" id="npQtyBack">Back to the list</button>
        <button type="button" class="btn btn-primary" id="npQtyOk">Add to the plan</button>
    </div>
</div>

{{-- A product from its label. --}}
<div class="sheet hidden" id="npCustomSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">What the label says</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <label class="np-label mt-0" for="npCName">Product name</label>
        <input type="text" id="npCName" class="form-input w-full" maxlength="120" placeholder="Like Complete 14-14-14">
        <span class="np-label">It comes as</span>
        <div class="np-pills" id="npCForm"></div>
        <div id="npCDensityBox" hidden>
            <label class="np-label" for="npCDensity">Kilograms per liter <span>(optional, 1 if not printed)</span></label>
            <input type="number" id="npCDensity" class="form-input w-full" min="0.5" max="2.5" step="any" inputmode="decimal" placeholder="1">
        </div>
        <span class="np-label">Nutrients, percent by weight</span>
        <div class="np-cgrid" id="npCGrid"></div>
        <p class="np-sub">As printed on the bag. Leave the rest empty.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="npCSave">Save and add</button>
    </div>
</div>

{{-- Anee's reading: the field around the plan. --}}
<div class="sheet hidden" id="npAneeSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Tell Anee about the field</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="np-sub mt-0">Anee checks your plan against the place, the soil, the water, the season and ENSO, then says what to keep, cut, add or split, and when.</p>
        <label class="np-label" for="npLoc">Where is the field?</label>
        <input type="text" id="npLoc" class="form-input w-full" maxlength="160" placeholder="Town and province">
        <span class="np-label">The soil</span>
        <div class="np-pills" id="npSoilCond"></div>
        <label class="np-label" for="npPh">Soil pH <span>(optional)</span></label>
        <input type="number" id="npPh" class="form-input w-full" min="2" max="12" step="0.1" inputmode="decimal">
        <span class="np-label">Water</span>
        <div class="np-pills" id="npWater"></div>
        <label class="np-label" for="npPlant">Planting date <span>(optional)</span></label>
        <input type="date" id="npPlant" class="form-input w-full">
        <span class="np-label">How will you apply it?</span>
        <div class="np-pills" id="npTiming"></div>
        <label class="np-label" for="npNotes">Anything else <span>(optional)</span></label>
        <textarea id="npNotes" class="form-textarea w-full" rows="2" maxlength="800" placeholder="Like: the field floods in August"></textarea>
        <button type="button" class="np-anee w-full mt-4" id="npRun"><span id="npRunSays">Ask Anee</span></button>
        <p class="np-fine text-center" id="npRunFine"></p>
    </div>
</div>
@include('sm.partials.anee-wait')
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const U = {
        options: @json(route('npk.options')), product: @json(route('npk.product')),
        save: @json(route('npk.save')), list: @json(route('npk.list')), one: (id) => @json(url('/app/npk-plus/one')) + '/' + id,
        analyze: @json(route('npk.analyze')), job: (id) => @json(url('/app/npk-plus/job')) + '/' + id,
    };
    const NUTRIENTS = ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'Si', 'Cl'];
    const NAMES = { N: 'Nitrogen', P2O5: 'Phosphorus', K2O: 'Potassium', Ca: 'Calcium', Mg: 'Magnesium', S: 'Sulfur', Zn: 'Zinc', B: 'Boron', Fe: 'Iron', Mn: 'Manganese', Cu: 'Copper', Mo: 'Molybdenum', Si: 'Silicon', Cl: 'Chloride' };
    const OXIDE = { Ca: ['CaO', 1.3992], Mg: ['MgO', 1.6583] };
    const TICK = '<svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    const CHEV = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
    const PLUS = '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>';
    const still = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const lab = (n) => n === 'P2O5' ? 'P₂O₅' : n === 'K2O' ? 'K₂O' : n;
    const fmt = (v, dp = 1) => (Math.round(v * 10 ** dp) / 10 ** dp).toLocaleString(undefined, { maximumFractionDigits: dp });
    const span = (lo, hi) => fmt(lo) === fmt(hi) ? 'about ' + fmt(lo) : fmt(lo) + ' to ' + fmt(hi);
    const num = (id) => { const v = Number($(id).value); return isFinite(v) && v > 0 ? v : 0; };

    // Every small choice: [what the sheet says, what the tag says, a hint].
    const AREA_U = { ha: ['Hectares', 'ha', '10,000 square meters each'], sqm: ['Square meters', 'm²', 'For a small plot or a backyard garden'] };
    const POT_U = { t: ['Tons per hectare', 't/ha', 'How a variety\'s potential is usually written', 1], cav: ['Cavans per hectare', 'cavans/ha', 'Cavans of 50 kg, as palay and corn are sold', 0.05], kg: ['Kilograms per hectare', 'kg/ha', 'For small harvests', 0.001] };
    const SEED_U = { kg: ['Kilograms', 'kg', 'Seed by weight, or tubers, cloves and sets'], g: ['Grams', 'g', 'Small seed, like most vegetables'], seeds: ['Seeds', 'seeds', 'Counted one by one'],
        seedlings: ['Seedlings', 'seedlings', 'Transplants from a seedbed or a tray'], hills: ['Hills', 'hills', 'Planting holes, with one or more seeds each'],
        cuttings: ['Cuttings', 'cuttings', 'Stem cuttings or cane setts'], plants: ['Plants or trees', 'plants', 'Suckers, grafted seedlings and trees'] };
    const SEED_DEF = { cuttings: ['cassava', 'sweetpotato', 'sugarcane'], g: ['carrot', 'pechay', 'lettuce'], seeds: ['squash', 'cucumber', 'watermelon', 'ampalaya', 'okra'],
        seedlings: ['cabbage', 'broccoli', 'tomato', 'eggplant', 'chili', 'bellpepper', 'onion'], plants: ['pineapple', 'banana', 'papaya', 'mango', 'coconut', 'calamansi', 'coffee', 'cacao'] };
    const P_METHOD = { olsen: ['Olsen', 'Olsen', 'The usual method for neutral and alkaline soils'], bray: ['Bray', 'Bray', 'The usual method for acid soils'] };
    const K_UNIT = { ppm: ['Parts per million', 'ppm', 'Milligrams per kilogram of soil'], meq: ['Milliequivalents per 100 g', 'meq/100 g', 'As some laboratories report potassium'] };
    const FORMS = { granular: 'Granular', powder: 'Powder', liquid: 'Liquid' };

    let OPT = null, calcId = null, RES = null;
    const st = { crop: null, lines: [], areaUnit: 'ha', potUnit: 't', seedUnit: 'kg', seedUnitSet: false, pMethod: 'olsen', kUnit: 'ppm', form: 'granular' };
    const areaHa = () => st.areaUnit === 'sqm' ? num('npArea') / 10000 : num('npArea');
    const tagSay = (id, dict, k) => { $(id).querySelector('span').textContent = dict[k][1]; };

    /* ---- the top card folds, and remembers ---- */
    const HERO_KEY = 'anee.npkHeroOpen';
    const hero = (on) => { $('npHero').classList.toggle('is-open', on); $('npHeroHead').setAttribute('aria-expanded', String(on)); $('npHeroSays').textContent = on ? 'Tap to fold' : 'Tap to see how it works'; };
    try { hero(localStorage.getItem(HERO_KEY) === '1'); } catch (_) { hero(false); }
    $('npHeroHead').addEventListener('click', () => { const on = !$('npHero').classList.contains('is-open'); hero(on); try { localStorage.setItem(HERO_KEY, on ? '1' : '0'); } catch (_) {} });

    /* ---- one sheet for every small choice ---- */
    let pickCb = null;
    const pick = (title, hint, dict, cur, cb) => {
        $('npPickTitle').textContent = title;
        $('npPickHint').textContent = hint || ''; $('npPickHint').hidden = !hint;
        $('npPickRows').innerHTML = Object.entries(dict).map(([k, v]) => '<button type="button" class="dt-row' + (k === cur ? ' is-on' : '') + '" data-k="' + esc(k) + '"><span class="dt-row-body"><b>' + esc(v[0]) + '</b>' + (v[2] ? '<i>' + esc(v[2]) + '</i>' : '') + '</span>' + TICK + '</button>').join('');
        pickCb = cb;
        window.openSheet('npPickSheet');
    };
    $('npPickRows').addEventListener('click', (e) => {
        const b = e.target.closest('.dt-row');
        if (!b) return;
        $('npPickRows').querySelectorAll('.dt-row').forEach((x) => x.classList.toggle('is-on', x === b));
        const cb = pickCb; pickCb = null;
        setTimeout(() => window.closeSheet('npPickSheet'), 160);
        if (cb) cb(b.dataset.k);
    });

    /* ---- the crop ---- */
    const cropName = (c) => String(c.label || '').replace(' — ', ', ');
    const cropOf = (k) => (OPT ? OPT.crops.find((c) => c.key === k) : null) || null;
    const paintCrops = () => {
        const groups = {};
        OPT.crops.forEach((c) => { const g = c.group || 'Other crops'; (groups[g] = groups[g] || []).push(c); });
        const row = (c, g) => {
            const ty = c.typicalYield;
            const sub = ty ? 'Philippine average ' + span(ty.lo, ty.hi) + ' ' + ty.unit : '';
            return '<button type="button" class="crop-row' + (st.crop === c.key ? ' is-on' : '') + '" data-crop="' + esc(c.key) + '" data-find="' + esc((cropName(c) + ' ' + g).toLowerCase()) + '">'
                + '<span class="crop-row-e">' + esc(c.icon) + '</span><span class="crop-row-t"><b>' + esc(cropName(c)) + '</b>' + (sub ? '<small>' + esc(sub) + '</small>' : '') + '</span></button>';
        };
        $('npCropList').innerHTML = '<div class="crop-group" data-crop-group><button type="button" class="crop-row' + (!st.crop ? ' is-on' : '') + '" data-crop="" data-find="no crop none totals only">'
            + '<span class="crop-row-e">🧮</span><span class="crop-row-t"><b>No crop, just the totals</b><small>See what the fertilizers add up to</small></span></button></div>'
            + Object.entries(groups).map(([g, list]) => '<div class="crop-group" data-crop-group><p class="crop-group-h">' + esc(g) + '</p>' + list.map((c) => row(c, g)).join('') + '</div>').join('');
        cropSift();
    };
    const cropSift = () => {
        const q = ($('npCropSearch').value || '').trim().toLowerCase();
        $('npCropSearchX').classList.toggle('hidden', !q);
        let shown = 0;
        document.querySelectorAll('#npCropList [data-crop-group]').forEach((g) => {
            let left = 0;
            g.querySelectorAll('.crop-row').forEach((r) => { const hit = !q || (r.dataset.find || '').includes(q); r.hidden = !hit; if (hit) left++; });
            g.hidden = !left; shown += left;
        });
        $('npCropNone').classList.toggle('hidden', shown > 0);
    };
    $('npCropSearch').addEventListener('input', cropSift);
    $('npCropSearchX').addEventListener('click', () => { $('npCropSearch').value = ''; cropSift(); $('npCropSearch').focus(); });
    const ready = () => { if (!OPT) window.toast?.('One moment, the calculator is still loading.', 'info'); return !!OPT; };
    $('npCropBtn').addEventListener('click', () => {
        if (!ready()) return;
        paintCrops();
        window.openSheet('npCropSheet');
        if (!matchMedia('(hover: none)').matches) setTimeout(() => $('npCropSearch').focus(), 280);
    });
    $('npCropList').addEventListener('click', (e) => {
        const r = e.target.closest('.crop-row');
        if (!r) return;
        setCrop(r.dataset.crop || null);
        setTimeout(() => window.closeSheet('npCropSheet'), 120);
    });
    const setCrop = (k) => {
        st.crop = k;
        const c = cropOf(k);
        $('npCropIcon').textContent = c ? c.icon : '🌱';
        $('npCropNow').textContent = c ? cropName(c) : 'Choose the crop';
        $('npCropNow').classList.toggle('is-none', !c);
        if (c && !st.seedUnitSet) {
            st.seedUnit = Object.keys(SEED_DEF).find((u) => SEED_DEF[u].includes(c.key)) || 'kg';
            tagSay('npSeedUnit', SEED_U, st.seedUnit);
        }
        hints();
        calc();
    };

    /* ---- the variety, what is planted, and the area ---- */
    const hints = () => {
        const c = cropOf(st.crop), ty = c && c.typicalYield;
        $('npPotHint').textContent = ty ? 'Philippine average for ' + cropName(c).split(' (')[0].toLowerCase() + ': ' + span(ty.lo, ty.hi) + ' ' + ty.unit + '. A good variety in a good season gives more.'
            : 'On the seed bag or the variety\'s description.';
        const s = num('npSeed'), a = areaHa();
        $('npSeedHint').textContent = s && a ? 'For the whole area. That is ' + fmt(s / a, s / a >= 100 ? 0 : 1) + ' ' + SEED_U[st.seedUnit][1] + ' per hectare.' : 'How much you plant on the whole area.';
    };
    $('npPotUnit').addEventListener('click', () => pick('The yield in', 'The rim of the barrel stands for this.', POT_U, st.potUnit, (k) => { st.potUnit = k; tagSay('npPotUnit', POT_U, k); calc(); }));
    $('npSeedUnit').addEventListener('click', () => pick('What you plant', 'For the whole area.', SEED_U, st.seedUnit, (k) => { st.seedUnit = k; st.seedUnitSet = true; tagSay('npSeedUnit', SEED_U, k); hints(); calc(); }));
    $('npAreaUnit').addEventListener('click', () => pick('The area in', '', AREA_U, st.areaUnit, (k) => { st.areaUnit = k; tagSay('npAreaUnit', AREA_U, k); hints(); calc(); }));
    ['npArea', 'npPotential', 'npSeed', 'npVariety'].forEach((id) => $(id).addEventListener('input', () => { hints(); calc(); }));

    /* ---- the fertilizers ---- */
    const grade = (p) => p.local && /^\d/.test(p.local) ? p.local : (p.pct.N || p.pct.P2O5 || p.pct.K2O ? [p.pct.N || 0, p.pct.P2O5 || 0, p.pct.K2O || 0].join('-') : '');
    const badge = (p) => grade(p) || (Object.entries(p.pct)[0] ? Object.entries(p.pct)[0][0] + ' ' + Object.entries(p.pct)[0][1] + '%' : (p.estimate ? 'Bio' : ''));
    const comp = (p) => Object.entries(p.pct).map(([k, v]) => lab(k) + ' ' + v + '%').join(' · ') || (p.estimate ? 'About ' + Object.entries(p.estimate).map(([k, v]) => v + ' kg ' + lab(k)).join(', ') + ' per hectare dose (estimate)' : '');
    const prodOf = (id) => OPT.products.find((x) => String(x.id) === String(id));
    const unitList = (p) => p.unit === 'dose' ? [['dose', 'hectare doses']] : (p.unit === 'L' ? [['L', 'liters']] : [['bag', 'bags of ' + (p.bagKg || 50) + ' kg'], ['kg', 'kilograms'], ['g', 'grams']]);
    const defUnit = (p) => p.unit === 'dose' ? 'dose' : (p.unit === 'L' ? 'L' : (p.bagKg ? 'bag' : 'kg'));
    const kgOf = (p, amount, unit) => unit === 'bag' ? amount * (p.bagKg || 50) : unit === 'g' ? amount / 1000 : unit === 'L' ? amount * (p.density || 1) : unit === 'kg' ? amount : 0;
    const amountWords = (p, l) => {
        const a = fmt(l.amount, 2);
        if (l.unit === 'bag') return a + ' ' + (l.amount === 1 ? 'bag' : 'bags') + ' · ' + fmt(kgOf(p, l.amount, 'bag'), 1) + ' kg';
        if (l.unit === 'dose') return a + ' hectare ' + (l.amount === 1 ? 'dose' : 'doses');
        return a + ' ' + ({ kg: 'kg', g: 'g', L: 'L' }[l.unit] || l.unit);
    };
    const lines = (flash = -1) => {
        $('npLines').innerHTML = st.lines.length ? st.lines.map((l, i) => {
            const p = prodOf(l.id);
            if (!p) return '';
            return '<div class="np-line' + (i === flash ? ' is-flash' : '') + '"><div><b>' + esc(p.name) + (grade(p) ? '<span class="np-gb">' + esc(grade(p)) + '</span>' : '') + '</b><small>' + esc(comp(p)) + '</small></div>'
                + '<button type="button" class="np-x" data-rm="' + i + '" aria-label="Remove ' + esc(p.name) + '">✕</button>'
                + '<button type="button" class="np-qty" data-q="' + i + '" aria-label="Change how much">' + esc(amountWords(p, l)) + CHEV + '</button></div>';
        }).join('') : '<div class="np-empty">No fertilizer yet. Add the first one below.</div>';
        $('npAddBtn').lastChild.textContent = st.lines.length ? 'Add another fertilizer' : 'Add a fertilizer';
    };
    $('npLines').addEventListener('click', (e) => {
        const r = e.target.closest('[data-rm]');
        if (r) { st.lines.splice(Number(r.dataset.rm), 1); lines(); calc(); return; }
        const q = e.target.closest('[data-q]');
        if (q) { const l = st.lines[Number(q.dataset.q)]; const p = l && prodOf(l.id); if (p) openQty(p, Number(q.dataset.q), false); }
    });

    // Step one: which product.
    let cat = 'all';
    const paintProducts = () => {
        const q = ($('npProdQ').value || '').trim().toLowerCase();
        $('npProdQX').classList.toggle('hidden', !q);
        const hit = (p) => (cat === 'all' || p.category === cat) && (!q || (p.name + ' ' + (p.local || '') + ' ' + grade(p) + ' ' + Object.keys(p.pct).map((k) => NAMES[k] || k).join(' ')).toLowerCase().includes(q));
        const row = (p) => {
            const inPlan = st.lines.some((l) => l.id === p.id);
            return '<button type="button" class="np-prow' + (inPlan ? ' is-in' : '') + '" data-id="' + p.id + '"><span class="np-prow-g">' + esc(badge(p)) + '</span><span class="np-prow-t"><b>' + esc(p.name) + '</b><small>' + esc(comp(p)) + '</small></span>'
                + (inPlan ? '<em>In your plan</em>' : PLUS) + '</button>';
        };
        const list = OPT.products.filter(hit);
        if (!list.length) { $('npProdList').innerHTML = '<p class="crop-none">Nothing matches. Add it from its label below.</p>'; return; }
        if (cat === 'all' && !q) {
            $('npProdList').innerHTML = Object.entries(OPT.categories).map(([k, v]) => { const rows = list.filter((p) => p.category === k); return rows.length ? '<p class="crop-group-h">' + esc(v) + '</p>' + rows.map(row).join('') : ''; }).join('');
        } else {
            $('npProdList').innerHTML = list.map(row).join('');
        }
    };
    $('npAddBtn').addEventListener('click', () => {
        if (!ready()) return;
        paintProducts();
        window.openSheet('npProdSheet');
    });
    $('npProdQ').addEventListener('input', paintProducts);
    $('npProdQX').addEventListener('click', () => { $('npProdQ').value = ''; paintProducts(); $('npProdQ').focus(); });
    $('npCats').addEventListener('click', (e) => {
        const b = e.target.closest('.np-cat');
        if (!b) return;
        cat = b.dataset.c;
        $('npCats').querySelectorAll('.np-cat').forEach((x) => x.classList.toggle('is-on', x === b));
        paintProducts();
    });
    $('npProdList').addEventListener('click', (e) => {
        const b = e.target.closest('.np-prow');
        if (!b) return;
        const p = prodOf(b.dataset.id);
        if (!p) return;
        // Already in the plan: change its amount rather than add it twice.
        openQty(p, st.lines.findIndex((l) => l.id === p.id), true);
    });

    // Step two: how much.
    let qty = null;
    const STEP = { bag: 0.5, kg: 5, g: 100, L: 0.5, dose: 0.5 };
    const openQty = (p, idx, fromList) => {
        const l = idx >= 0 ? st.lines[idx] : null;
        qty = { p, idx, fromList, unit: l ? l.unit : defUnit(p) };
        $('npQtyTitle').textContent = l ? 'Change the amount' : 'How much?';
        $('npQtyProd').innerHTML = '<span class="np-prow-g">' + esc(badge(p)) + '</span><span class="np-prow-t"><b>' + esc(p.name) + '</b><small>' + esc(comp(p)) + '</small>' + (p.note ? '<small class="mt-1">' + esc(p.note) + '</small>' : '') + '</span>';
        $('npQtyIn').value = l ? l.amount : (qty.unit === 'kg' ? 10 : 1);
        $('npQtyFor').textContent = 'For the whole area (' + fmt(areaHa(), 3) + ' ha)';
        $('npQtyOk').textContent = l ? 'Save the amount' : 'Add to the plan';
        $('npQtyBack').textContent = fromList ? 'Back to the list' : 'Cancel';
        paintQty();
        window.openSheet('npQtySheet');
    };
    const paintQty = () => {
        const p = qty.p;
        $('npQtyUnits').innerHTML = unitList(p).map(([v, t]) => '<button type="button" class="np-pill" data-u="' + v + '" aria-pressed="' + (v === qty.unit) + '">' + esc(t) + '</button>').join('');
        const amount = Number($('npQtyIn').value) || 0, a = areaHa();
        const give = [];
        if (qty.unit === 'dose' && p.estimate) {
            Object.entries(p.estimate).forEach(([n, v]) => give.push([n, v * amount * a]));
        } else {
            const kg = kgOf(p, amount, qty.unit);
            Object.entries(p.pct).forEach(([n, v]) => give.push([n, kg * v / 100]));
        }
        const kg = qty.unit === 'dose' ? 0 : kgOf(p, amount, qty.unit);
        const words = give.filter(([, v]) => v > 0).map(([n, v]) => (v < 1 ? fmt(v * 1000, 0) + ' g ' : fmt(v, 1) + ' kg ') + lab(n));
        $('npQtySum').innerHTML = amount > 0
            ? (kg ? 'That is <b>' + fmt(kg, 1) + ' kg</b> of fertilizer. ' : '') + (words.length ? 'It gives ' + esc(words.join(', ')) + ' for the whole area' + (a > 0 && a !== 1 ? ', or ' + esc(give.filter(([, v]) => v > 0).map(([n, v]) => fmt(v / a, 1) + ' kg ' + lab(n)).join(', ')) + ' per hectare' : '') + '.' : '')
                + (qty.unit === 'dose' ? ' A field trial estimate; it varies a lot.' : '')
            : 'Type how much you will use.';
    };
    $('npQtyIn').addEventListener('input', paintQty);
    $('npQtyUnits').addEventListener('click', (e) => {
        const b = e.target.closest('[data-u]');
        if (!b) return;
        // Keep the same weight when the unit changes: 2 bags become 100 kg.
        const p = qty.p, amount = Number($('npQtyIn').value) || 0, kg = kgOf(p, amount, qty.unit);
        const next = b.dataset.u;
        if (kg > 0 && next !== 'dose' && next !== 'L' && qty.unit !== 'dose' && qty.unit !== 'L') {
            const v = next === 'bag' ? kg / (p.bagKg || 50) : next === 'g' ? kg * 1000 : kg;
            $('npQtyIn').value = Math.round(v * 100) / 100;
        }
        qty.unit = next;
        paintQty();
    });
    const nudge = (d) => { const s = STEP[qty.unit] || 1; $('npQtyIn').value = Math.max(0, Math.round(((Number($('npQtyIn').value) || 0) + d * s) * 100) / 100); paintQty(); };
    $('npQtyMinus').addEventListener('click', () => nudge(-1));
    $('npQtyPlus').addEventListener('click', () => nudge(1));
    $('npQtyBack').addEventListener('click', () => window.closeSheet('npQtySheet'));
    $('npQtyOk').addEventListener('click', () => {
        if (!qty) return;
        const amount = Number($('npQtyIn').value) || 0;
        if (!(amount > 0)) return window.toast?.('Type how much you will use.', 'error');
        let at = qty.idx;
        if (at >= 0) { st.lines[at].amount = amount; st.lines[at].unit = qty.unit; }
        else { st.lines.push({ id: qty.p.id, amount, unit: qty.unit }); at = st.lines.length - 1; }
        window.closeSheet('npQtySheet');
        if (qty.fromList) window.closeSheet('npProdSheet');
        lines(at); calc();
        setTimeout(() => $('npLines').children[at]?.scrollIntoView({ behavior: still() ? 'auto' : 'smooth', block: 'nearest' }), 280);
    });

    // A product from its label.
    $('npCGrid').innerHTML = NUTRIENTS.filter((n) => n !== 'Cl').map((n) => '<label>' + lab(n) + ' %<input type="number" class="form-input" min="0" max="100" step="any" inputmode="decimal" data-n="' + n + '"></label>').join('');
    const paintForm = () => {
        $('npCForm').innerHTML = Object.entries(FORMS).map(([k, v]) => '<button type="button" class="np-pill" data-f="' + k + '" aria-pressed="' + (k === st.form) + '">' + v + '</button>').join('');
        $('npCDensityBox').hidden = st.form !== 'liquid';
    };
    $('npCForm').addEventListener('click', (e) => { const b = e.target.closest('[data-f]'); if (b) { st.form = b.dataset.f; paintForm(); } });
    $('npCustomBtn').addEventListener('click', () => { paintForm(); window.openSheet('npCustomSheet'); });
    $('npCSave').addEventListener('click', async () => {
        const body = { name: $('npCName').value.trim(), form: st.form };
        if (st.form === 'liquid' && $('npCDensity').value) body.density = Number($('npCDensity').value);
        $('npCGrid').querySelectorAll('input').forEach((i) => { if (i.value) body[i.dataset.n] = Number(i.value); });
        if (!body.name) return window.toast?.('Give the product a name.', 'error');
        $('npCSave').disabled = true;
        try {
            const r = await window.api(U.product, { method: 'POST', body });
            const p = r.data.product;
            OPT.products.push(p);
            $('npCName').value = ''; $('npCDensity').value = ''; $('npCGrid').querySelectorAll('input').forEach((i) => { i.value = ''; });
            window.closeSheet('npCustomSheet');
            paintProducts();
            window.toast?.(r.message, 'success');
            setTimeout(() => openQty(p, -1, true), 260);
        } catch (err) { window.toast?.(err.message, 'error'); }
        finally { $('npCSave').disabled = false; }
    });

    /* ---- the soil test ---- */
    const SOIL = [['ph', 'pH'], ['om', 'Organic matter %'], ['n', 'Total N %'], ['p', 'P ppm'], ['k', 'K'], ['zn', 'Zn ppm'], ['b', 'B ppm'], ['s', 'S ppm']];
    $('npSoilGrid').innerHTML = SOIL.map(([k, l]) => '<label>' + esc(l) + '<input type="number" class="form-input" step="any" min="0" inputmode="decimal" data-s="' + k + '"></label>').join('');
    $('npSoilOn').addEventListener('change', () => { $('npSoil').classList.toggle('is-on', $('npSoilOn').checked); calc(); });
    $('npSoil').addEventListener('input', () => calc());
    $('npPMethod').addEventListener('click', () => pick('How phosphorus was measured', 'It is printed on the soil test result.', P_METHOD, st.pMethod, (k) => { st.pMethod = k; tagSay('npPMethod', P_METHOD, k); calc(); }));
    $('npKUnit').addEventListener('click', () => pick('Potassium is given in', 'It is printed beside the number.', K_UNIT, st.kUnit, (k) => { st.kUnit = k; tagSay('npKUnit', K_UNIT, k); calc(); }));
    const soil = () => {
        if (!$('npSoilOn').checked) return null;
        const s = {};
        $('npSoilGrid').querySelectorAll('input').forEach((i) => { if (i.value !== '') s[i.dataset.s] = Number(i.value); });
        s.pMethod = st.pMethod; s.kUnit = st.kUnit;
        return Object.keys(s).length > 2 ? s : null;
    };
    // Low, medium or high, from the usual soil test bands.
    const soilClass = (s) => {
        if (!s) return {};
        const out = {};
        if (s.p != null) out.P2O5 = s.pMethod === 'bray' ? (s.p < 15 ? 'low' : s.p <= 30 ? 'medium' : 'high') : (s.p < 10 ? 'low' : s.p <= 20 ? 'medium' : 'high');
        if (s.k != null) { const meq = s.kUnit === 'meq' ? s.k : s.k / 391; out.K2O = meq < 0.2 ? 'low' : meq <= 0.4 ? 'medium' : 'high'; }
        if (s.om != null) out.N = s.om < 2 ? 'low' : s.om <= 4 ? 'medium' : 'high';
        else if (s.n != null) out.N = s.n < 0.1 ? 'low' : s.n <= 0.2 ? 'medium' : 'high';
        if (s.zn != null) out.Zn = s.zn < 0.8 ? 'low' : 'ok';
        if (s.b != null) out.B = s.b < 0.5 ? 'low' : 'ok';
        if (s.s != null) out.S = s.s < 10 ? 'low' : 'ok';
        if (s.ph != null) { out.Ca = s.ph < 5.5 ? 'low' : 'ok'; out.Mg = out.Ca; }
        return out;
    };

    /* ---- the arithmetic ---- */
    const calc = () => {
        if (!OPT) return;
        const area = areaHa();
        const tot = Object.fromEntries(NUTRIENTS.map((n) => [n, 0]));
        let estimated = false;
        const out = st.lines.map((l) => {
            const p = prodOf(l.id);
            if (!p) return null;
            const kg = kgOf(p, l.amount, l.unit);
            Object.entries(p.pct).forEach(([n, v]) => { tot[n] += kg * v / 100; });
            if (p.estimate && l.unit === 'dose') { estimated = true; Object.entries(p.estimate).forEach(([n, v]) => { tot[n] += v * l.amount * Math.max(area, 0); }); }
            return { id: p.id, name: p.name, grade: grade(p), amount: l.amount, unit: l.unit, kg: Math.round(kg * 100) / 100, pct: p.pct, estimate: p.estimate };
        }).filter(Boolean);
        const perHa = Object.fromEntries(NUTRIENTS.map((n) => [n, area > 0 ? tot[n] / area : 0]));
        $('npN').textContent = fmt(perHa.N, 0); $('npP').textContent = fmt(perHa.P2O5, 0); $('npK').textContent = fmt(perHa.K2O, 0);
        const npk = fmt(perHa.N, 0) + '-' + fmt(perHa.P2O5, 0) + '-' + fmt(perHa.K2O, 0);
        $('npMbarV').textContent = npk;
        $('npMbar').classList.toggle('is-on', out.length > 0);
        $('npOutSub').textContent = out.length ? 'For ' + fmt(area, 3) + ' ha.' + (estimated ? ' Includes biofertilizer estimates.' : '') : 'Add a fertilizer to see the totals.';

        // NPK: as the bag reads them, N, P2O5 and K2O.
        $('npTable').innerHTML = out.length ? '<table class="np-table"><tr><th>Nutrient</th><th>Per hectare</th><th>Whole area</th></tr>'
            + ['N', 'P2O5', 'K2O'].map((n) => '<tr><td><b>' + NAMES[n] + '</b> <span class="text-gray-400">as ' + lab(n) + '</span></td><td>' + fmt(perHa[n]) + ' kg</td><td>' + fmt(tot[n]) + ' kg</td></tr>').join('')
            + '</table><p class="np-fine mt-2">As on the bag: nitrogen as N, phosphorus as P₂O₅ and potassium as K₂O. The rates the crop guides give are in the same terms.</p>' : '';

        const crop = cropOf(st.crop);
        const cls = soilClass(soil());
        const potV = num('npPotential'), potT = potV * POT_U[st.potUnit][3];
        let needHtml = '', say = '';
        const support = {}, needs = {};
        if (crop && out.length) {
            const name = esc(cropName(crop).split(' (')[0]);
            const rec = crop.recommendedPerHa || {};
            const bars = ['N', 'P2O5', 'K2O'].filter((n) => Array.isArray(rec[n])).map((n) => {
                let [lo, hi] = rec[n];
                if (cls[n] === 'low') lo = (lo + hi) / 2; else if (cls[n] === 'high') hi = (lo + hi) / 2;
                const have = perHa[n];
                const state = have < lo * 0.9 ? 'short' : have > hi * 1.15 ? 'over' : 'right';
                needs[n] = { usual: [Math.round(lo), Math.round(hi)], plan: Math.round(have), state };
                const scale = Math.max(hi * 1.5, have * 1.05, 1);
                return '<div class="np-need-row is-' + state + '" title="Usual rate ' + fmt(lo, 0) + (hi !== lo ? ' to ' + fmt(hi, 0) : '') + ' kg per ha"><span>' + lab(n) + '</span><span class="np-need-bar"><span class="band" style="left:' + (lo / scale * 100) + '%;width:' + (Math.max(hi - lo, scale * 0.015) / scale * 100) + '%"></span><span class="have" style="width:' + Math.min(100, have / scale * 100) + '%"></span></span><em>' + (state === 'short' ? 'Short' : state === 'over' ? 'Too much' : 'Right') + '</em></div>';
            }).join('');
            needHtml = bars ? '<h4 class="np-label">Against the usual rate for ' + name + ' (green band)' + (crop.treesPerHa ? ', at ' + crop.treesPerHa + ' trees per hectare' : '') + (Object.keys(cls).length ? ', adjusted by your soil test' : '') + '</h4><div class="np-need">' + bars + '</div>' : '';
            // What the fertilizer alone can grow: each nutrient, times the share
            // the crop recovers, over what a ton of harvest takes up.
            const up = crop.uptakePerTon || {};
            const eff = Object.assign({ N: 0.4, P2O5: 0.2, K2O: 0.5 }, crop.efficiency || {});
            const use = ['N', 'P2O5', 'K2O'].filter((n) => up[n] > 0 && !(n === 'N' && crop.legume));
            use.forEach((n) => { support[n] = perHa[n] * eff[n] / up[n]; });
            const ty = crop.typicalYield || null;
            const unit = ty ? esc(ty.unit) : 't/ha';
            const tyWords = ty ? ' Typical yields in the Philippines (national average): ' + span(ty.lo, ty.hi) + ' ' + unit + '.' : '';
            if (use.length && !crop.removalOnly) {
                const lim = use.reduce((a, b) => support[a] <= support[b] ? a : b);
                const parts = use.map((n) => '<b>' + fmt(support[n]) + ' t/ha</b> (' + NAMES[n].toLowerCase() + ')');
                say = '<div class="np-say">From this fertilizer alone, the crop can take up enough to grow about '
                    + (parts.length > 1 ? parts.slice(0, -1).join(', ') + ' and ' + parts[parts.length - 1] : parts[0]) + '. '
                    + (use.length > 1 ? NAMES[lim] + ' runs out first. ' : '')
                    + 'Your soil gives its own share on top of this, often most of the harvest, so the real yield is higher; a soil test tells how much.' + tyWords
                    + (crop.legume ? ' A legume makes most of its own nitrogen from the air, so nitrogen is left out of this count.' : '')
                    + '</div>';
                if (potT > 0) {
                    say += '<div class="np-say">To reach the variety\'s potential of <b>' + fmt(potT) + ' t/ha</b> the crop takes up about '
                        + use.map((n) => fmt(potT * up[n], 0) + ' kg ' + lab(n)).join(', ') + ' per hectare in all. This plan gives it about '
                        + use.map((n) => fmt(perHa[n] * eff[n], 0) + ' kg ' + lab(n)).join(', ') + ' of that; the rest has to come from the soil.</div>';
                }
            } else if (crop.removalOnly) {
                say = '<div class="np-say">For ' + name + ' the published numbers only count what the harvest carries away, not what the plant needs to grow, so NPK Plus does not turn this plan into a yield. Use the usual rate above as the guide.' + tyWords + '</div>';
            } else if (tyWords) {
                say = '<div class="np-say">' + tyWords.trim() + '</div>';
            }
            const src = (crop.sources || []).map((s) => '<li><a href="' + esc(s.url) + '" target="_blank" rel="noopener">' + esc(s.label) + '</a></li>').join('');
            if (crop.about || src) {
                say += '<details class="np-src"><summary>Where these numbers come from</summary>' + (crop.proxy ? '<p><b>Close stand in:</b> ' + esc(crop.proxy) + '.</p>' : '')
                    + (crop.about ? '<p>' + esc(crop.about.replace(/ — /g, ', ')) + '</p>' : '') + (src ? '<ul>' + src + '</ul>' : '')
                    + '<p>Where a source gives no recovery rate, NPK Plus assumes the crop recovers 40% of the nitrogen, 20% of the phosphorus and 50% of the potassium in the first season.</p></details>';
            }
        } else if (out.length && !crop) {
            say = '<div class="np-say">Pick the crop to see if this is enough and what yield it can feed.</div>';
        }
        $('npNeed').innerHTML = needHtml;
        $('npSay').innerHTML = say;

        micros(tot, perHa, cls, out.length);
        barrel(crop, perHa, tot, needs, cls, out.length, potT);

        const seedV = num('npSeed');
        RES = { perHa: Object.fromEntries(Object.entries(perHa).filter(([, v]) => v > 0).map(([k, v]) => [k, Math.round(v * 100) / 100])), total: Object.fromEntries(Object.entries(tot).filter(([, v]) => v > 0).map(([k, v]) => [k, Math.round(v * 100) / 100])),
            npkPerHa: npk, needs, support: Object.fromEntries(Object.entries(support).map(([k, v]) => [k, Math.round(v * 10) / 10])), lines: out, areaHa: area, soilClass: cls,
            setup: { variety: $('npVariety').value.trim(), areaUnit: st.areaUnit, areaValue: num('npArea'),
                potential: potV ? { value: potV, unit: POT_U[st.potUnit][1], key: st.potUnit, tPerHa: Math.round(potT * 100) / 100 } : null,
                seed: seedV ? { amount: seedV, unit: SEED_U[st.seedUnit][1], key: st.seedUnit, perHa: area > 0 ? Math.round(seedV / area * 10) / 10 : null } : null } };
        $('npAnee').disabled = !out.length;
        $('npSave').disabled = !out.length;
    };

    /* ---- the micros tab ---- */
    const amt = (kg) => kg >= 1 ? fmt(kg, 1) + ' kg' : fmt(kg * 1000, 0) + ' g';
    const micros = (tot, perHa, cls, has) => {
        const row = (n, sec, k) => {
            const v = tot[n] || 0, on = v > 0;
            let note = on ? (OXIDE[n] ? 'Or ' + amt(perHa[n] * OXIDE[n][1]) + ' ' + OXIDE[n][0] + ' per ha, as some labels write it' : 'In your plan') : 'None in your plan';
            let warn = false;
            if (cls[n] === 'low' && !on) { warn = true; note = n === 'Ca' || n === 'Mg' ? 'Your soil is acid; acid soil is usually short of it. Lime or dolomite adds it.' : 'Your soil test reads low and the plan has none.'; }
            else if (cls[n] === 'low') note = 'Your soil test reads low, and the plan adds some.';
            else if (cls[n] === 'ok' && !on) note = 'Your soil test reads enough.';
            return '<div class="np-mrow' + (sec ? ' is-sec' : '') + (on ? '' : ' is-none') + '" style="--k:' + k + '"><i>' + n + '</i><span><b>' + NAMES[n] + '</b><small' + (warn ? ' class="is-warn"' : '') + '>' + esc(note) + '</small></span>'
                + '<span class="np-mval">' + (on ? '<b>' + amt(perHa[n]) + '</b><small>per ha · ' + amt(v) + ' in all</small>' : '<b>None</b>') + '</span></div>';
        };
        // What the plan carries gets a row; what it does not is one line of
        // chips, unless the soil test says it is missing.
        let k = 0;
        const group = (title, list, sec) => {
            const full = list.filter((n) => tot[n] > 0 || cls[n] === 'low' || cls[n] === 'ok'), none = list.filter((n) => !full.includes(n));
            return '<p class="np-mh">' + title + '</p><div class="np-mlist">' + full.map((n) => row(n, sec, k++)).join('')
                + (none.length ? '<div class="np-mnone" style="--k:' + (k++) + '">None in your plan:' + none.map((n) => '<span title="' + NAMES[n] + '">' + n + '</span>').join('') + '</div>' : '') + '</div>';
        };
        $('npMicro').innerHTML = (has ? '' : '<p class="np-sub">Add a fertilizer to see the rest of what it carries.</p>')
            + group('Secondary nutrients', ['Ca', 'Mg', 'S'], true)
            + group('Micronutrients', ['Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo'].concat(['Si', 'Cl'].filter((n) => tot[n] > 0)), false)
            + '<div class="np-say">Crops need these in small amounts and most soils give enough. In Philippine fields the usual shortfalls are zinc in flooded rice, boron in vegetables and fruit, and sulfur where no ammonium sulfate or other sulfur source goes on. A soil test tells which yours lacks.</div>';
    };

    /* ---- Liebig's barrel ----
       A crop grows only as far as its scarcest need allows, as a barrel holds
       water only up to its shortest plank. Each nutrient plank is as tall as
       the share of the need the plan covers; sun, water and temperature are
       grey planks drawn full, because NPK Plus cannot measure them. */
    const STAVES = [
        { k: 'N', t: 'N' }, { k: 'P2O5', t: 'P₂O₅' }, { k: 'K2O', t: 'K₂O' }, { k: 'Ca', t: 'Ca' }, { k: 'Mg', t: 'Mg' }, { k: 'S', t: 'S' }, { k: 'Zn', t: 'Zn' }, { k: 'B', t: 'B' },
        { k: 'sun', t: 'Sun', name: 'Sunlight', dull: true }, { k: 'water', t: 'Water', name: 'Water', dull: true }, { k: 'temp', t: 'Temp', name: 'Temperature', dull: true },
    ];
    const BR = (() => {
        const n = STAVES.length, R = 80, H = 190, ang = 360 / n;
        const W = Math.ceil(2 * R * Math.tan(Math.PI / n)) + 1, RW = R - 4, WW = Math.ceil(2 * RW * Math.tan(Math.PI / n)) + 1;
        const el = $('lbBarrel'), stage = $('lbStage');
        const disc = (cls, d, y, extra = '') => '<div class="' + cls + '" style="width:' + d + 'px;height:' + d + 'px;margin-left:' + (-d / 2) + 'px;top:' + y + 'px;' + extra + '"></div>';
        el.innerHTML = disc('lb-shadow', 2 * R * 1.7, H + 2) + disc('lb-floor', 2 * R, H)
            + STAVES.map((s, i) => '<div class="lb-st" style="width:' + W + 'px;margin-left:' + (-W / 2) + 'px;transform:rotateY(' + (i * ang) + 'deg) translateZ(' + R + 'px);--d:' + (i * 55) + 'ms">'
                + '<div class="lb-out"><b>' + s.t + '</b><small></small></div><div class="lb-in"></div><div class="lb-spill"></div></div>').join('')
            + '<div class="lb-water">' + STAVES.map((s, i) => '<div class="lb-wf" style="width:' + WW + 'px;margin-left:' + (-WW / 2) + 'px;transform:rotateY(' + (i * ang) + 'deg) translateZ(' + RW + 'px)"></div>').join('')
            + disc('lb-top', 2 * RW + 3, 0) + '</div>'
            + disc('lb-rim', 2 * R + 16, 0);
        const sts = [...el.querySelectorAll('.lb-st')], water = el.querySelector('.lb-water');
        let ry = -18, rx = -16, vel = 0, drag = null, aim = null, on = false, raf = 0, t0 = performance.now(), lim = -1;
        const apply = () => {
            const idle = !drag && aim === null && Math.abs(vel) < 0.05 && !still();
            if (!idle) t0 = performance.now();
            const sway = idle ? Math.sin((performance.now() - t0) / 1700) * 7 : 0;
            el.style.transform = 'rotateX(' + rx + 'deg) rotateY(' + (ry + sway) + 'deg)';
        };
        const loop = () => {
            raf = 0;
            if (!on) return;
            if (!drag) {
                if (aim !== null) { const d = aim - ry; ry += d * 0.09; if (Math.abs(d) < 0.25) { ry = aim; aim = null; } }
                else if (Math.abs(vel) > 0.05) { ry += vel; vel *= 0.93; }
            }
            apply();
            raf = requestAnimationFrame(loop);
        };
        const face = (i) => {
            const target = -i * ang, d = ((target - ry) % 360 + 540) % 360 - 180;
            if (still()) { ry += d; aim = null; apply(); } else aim = ry + d;
        };
        stage.addEventListener('pointerdown', (e) => {
            if (e.button) return;
            drag = { x: e.clientX, y: e.clientY }; vel = 0; aim = null;
            stage.classList.add('is-grab', 'was-turned');
            try { stage.setPointerCapture(e.pointerId); } catch (_) {}
        });
        stage.addEventListener('pointermove', (e) => {
            if (!drag) return;
            const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
            drag.x = e.clientX; drag.y = e.clientY;
            ry += dx * 0.55; vel = still() ? 0 : dx * 0.55;
            if (e.pointerType === 'mouse') rx = Math.max(-42, Math.min(-4, rx - dy * 0.3));
            apply();
        });
        const up = () => { drag = null; stage.classList.remove('is-grab'); };
        stage.addEventListener('pointerup', up); stage.addEventListener('pointercancel', up);
        stage.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') { e.preventDefault(); stage.classList.add('was-turned'); const t = (aim ?? ry) + (e.key === 'ArrowLeft' ? ang : -ang); if (still()) { ry = t; apply(); } else aim = t; }
        });
        return {
            H, face,
            set(rows, levelPx, limIdx) {
                rows.forEach((r, i) => {
                    sts[i].style.height = r.px + 'px';
                    sts[i].className = 'lb-st is-' + r.state + (i === limIdx ? ' is-limit' : '') + (i === limIdx && levelPx < H - 1 && levelPx > 0 ? ' is-spill' : '');
                    sts[i].querySelector('small').textContent = r.pct;
                });
                water.style.setProperty('--f', Math.max(0.001, levelPx / H));
                if (limIdx !== lim && limIdx >= 0 && on) face(limIdx);
                lim = limIdx;
            },
            enter() {
                on = true;
                el.classList.add('is-rest');
                void el.offsetWidth;
                requestAnimationFrame(() => requestAnimationFrame(() => el.classList.remove('is-rest')));
                if (lim >= 0) face(lim);
                if (!raf) raf = requestAnimationFrame(loop);
            },
            leave() { on = false; },
        };
    })();
    const WORD = { short: 'Short', right: 'Enough', over: 'Too much', added: 'Added', fixed: 'From the air', unknown: 'Not checked', dull: 'Not measured' };
    const barrel = (crop, perHa, tot, needs, cls, has, potT) => {
        const H = BR.H, rows = [];
        STAVES.forEach((s) => {
            if (s.dull) { rows.push({ state: 'dull', ratio: null, px: H, pct: '', note: 'Not measured here' }); return; }
            const n = s.k, rec = crop && crop.recommendedPerHa ? crop.recommendedPerHa[n] : null;
            let state = 'unknown', ratio = null, note = 'Not checked';
            if (['N', 'P2O5', 'K2O'].includes(n)) {
                if (crop && crop.legume && n === 'N') { state = 'fixed'; ratio = 1; note = 'A legume makes its own'; }
                else if (needs[n]) {
                    const lo = needs[n].usual[0];
                    ratio = lo > 0 ? perHa[n] / lo : (needs[n].state === 'over' ? 1.2 : 1);
                    state = needs[n].state;
                    if (state === 'right') ratio = Math.min(1, ratio);
                    note = fmt(perHa[n], 0) + ' of ' + (needs[n].usual[0] === needs[n].usual[1] ? needs[n].usual[0] : needs[n].usual[0] + ' to ' + needs[n].usual[1]) + ' kg per ha';
                } else if (crop && !rec) note = 'No usual rate on file';
            } else {
                const in1 = tot[n] > 0;
                if (cls[n] === 'low') { state = in1 ? 'added' : 'short'; ratio = in1 ? 1 : (n === 'Ca' || n === 'Mg' ? 0.5 : 0.4); note = in1 ? 'Low in your soil, added' : (n === 'Ca' || n === 'Mg' ? 'Acid soil, none in plan' : 'Low in your soil, none in plan'); }
                else if (cls[n] === 'ok') { state = 'right'; ratio = 1; note = n === 'Ca' || n === 'Mg' ? 'Soil pH is fine' : 'Enough in your soil'; }
                else if (in1) { state = 'added'; ratio = 1; note = 'In your plan'; }
            }
            const px = state === 'unknown' ? H : state === 'over' ? Math.round(H * Math.min(1.22, Math.max(1.08, ratio))) : Math.max(22, Math.round(H * Math.min(1, ratio)));
            rows.push({ state, ratio, px, pct: ratio === null ? '?' : Math.round(Math.min(ratio, 9.99) * 100) + '%', note });
        });
        const measured = rows.map((r, i) => [r, i]).filter(([r]) => r.ratio !== null && r.state !== 'dull');
        let limIdx = -1, level = 0;
        if (has && measured.length) {
            [, limIdx] = measured.reduce((a, b) => (b[0].px < a[0].px ? b : a));
            level = Math.min(H, rows[limIdx].px);
        }
        BR.set(rows, level, limIdx);
        $('lbTag').textContent = potT > 0 ? 'The rim is the variety\'s potential, ' + fmt(potT) + ' t/ha.' : 'The rim is everything the crop needs.';
        $('lbKey').innerHTML = STAVES.map((s, i) => {
            const r = rows[i];
            return '<button type="button" class="lb-k is-' + r.state + '" data-i="' + i + '" title="' + esc(r.note) + '"><i></i><b>' + esc(s.name || NAMES[s.k]) + (s.dull ? '' : ' <small>' + s.t + '</small>') + '</b>'
                + '<span class="lb-kbar"><span style="width:' + (r.ratio === null ? (r.state === 'dull' ? 100 : 0) : Math.min(100, r.ratio * 100)) + '%"></span></span><em>' + (WORD[r.state] || '') + '</em></button>';
        }).join('');
        // The words under it.
        let say = '';
        const cName = crop ? esc(cropName(crop).split(' (')[0].toLowerCase()) : '';
        if (!has) say = 'Add a fertilizer, and the planks rise to show how much of each need the plan covers.';
        else if (!crop) say = 'Pick the crop: the nutrient planks are measured against its usual rates.';
        else if (limIdx < 0) say = 'There is no usual rate on file for ' + cName + ', so the nutrient planks cannot be measured. The Micros tab and Anee\'s reading can still help.';
        else {
            const L = rows[limIdx], Ls = STAVES[limIdx];
            say = 'A barrel holds water only up to its shortest plank, and a crop grows only as far as its scarcest need allows. ';
            say += L.px < BR.H * 0.9
                ? 'Here the shortest plank is <b>' + esc(NAMES[Ls.k]) + '</b> (' + Ls.t + '), at ' + L.pct + ' (' + esc(L.note.toLowerCase()) + '). Raise it first; more of the others will not raise the water.'
                : 'Every plank NPK Plus can measure reaches the rim, so on this plan the fertilizer is not what holds the harvest back.';
            const over = STAVES.filter((s, i) => rows[i].state === 'over').map((s) => NAMES[s.k].toLowerCase());
            if (over.length) say += ' Too much ' + over.join(' and ') + ': the taller plank holds no more water. It costs money' + (over.includes('nitrogen') ? ', and too much nitrogen can make the crop lodge and draw pests' : '') + '.';
            const unk = STAVES.filter((s, i) => rows[i].state === 'unknown').map((s) => s.t);
            if (unk.length) say += ' Not checked: ' + unk.join(', ') + '. A soil test measures them.';
        }
        say += ' The grey planks, sun, water and temperature, can be the shortest too. NPK Plus cannot measure them; Anee\'s reading weighs the season.';
        $('lbSay').innerHTML = '<div class="np-say">' + say + '</div>';
    };
    $('lbKey').addEventListener('click', (e) => { const b = e.target.closest('.lb-k'); if (b) { $('lbStage').classList.add('was-turned'); BR.face(Number(b.dataset.i)); } });

    /* ---- the three tabs ---- */
    const PANES = { npk: 'npPaneNpk', micro: 'npPaneMicro', barrel: 'npPaneBarrel' };
    $('npOTabs').addEventListener('click', (e) => {
        const b = e.target.closest('.np-otab');
        if (!b) return;
        const keys = Object.keys(PANES), k = b.dataset.p;
        $('npOTabs').style.setProperty('--i', keys.indexOf(k));
        $('npOTabs').querySelectorAll('.np-otab').forEach((x) => { x.classList.toggle('is-on', x === b); x.setAttribute('aria-selected', String(x === b)); });
        keys.forEach((x) => { $(PANES[x]).hidden = x !== k; });
        if (k === 'barrel') BR.enter(); else BR.leave();
    });
    $('npMbarGo').addEventListener('click', () => $('npOut').scrollIntoView({ behavior: still() ? 'auto' : 'smooth', block: 'start' }));

    /* ---- save, and Anee ---- */
    const save = async () => {
        if (!RES || !RES.lines.length) return null;
        const pot = RES.setup.potential;
        const r = await window.api(U.save, { method: 'POST', body: { id: calcId, crop: st.crop, areaHa: RES.areaHa || 0.0001, targetYield: pot ? pot.tPerHa : null, lines: RES.lines, soil: soil(), result: RES } });
        calcId = r.data.id;
        return r;
    };
    $('npSave').addEventListener('click', async () => { try { const r = await save(); window.toast?.(r.message + ' It is on the Saved tab.', 'success'); } catch (err) { window.toast?.(err.message, 'error'); } });
    $('npAnee').addEventListener('click', () => {
        $('npRun').disabled = !OPT.canUse;
        $('npRunSays').textContent = OPT.canUse ? 'Ask Anee · ' + OPT.quote + ' credits' : 'Anee is not on your plan';
        $('npRunFine').innerHTML = OPT.canUse ? 'You have ' + (window.creditCoin ? window.creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString()) : OPT.balance) + '. Charged only when the reading is ready.' : esc(OPT.whyNot || '');
        window.openSheet('npAneeSheet');
    });
    const ans = { soil: [], water: 'irrigated', timing: 'split2' };
    const pills = (box, items, multi, key) => {
        box.innerHTML = Object.entries(items).map(([k, v]) => '<button type="button" class="np-pill" data-k="' + esc(k) + '">' + esc(String(v).split(' —')[0]) + '</button>').join('');
        const paint = () => box.querySelectorAll('.np-pill').forEach((b) => b.setAttribute('aria-pressed', String(multi ? ans[key].includes(b.dataset.k) : ans[key] === b.dataset.k)));
        box.addEventListener('click', (e) => { const b = e.target.closest('.np-pill'); if (!b) return; if (multi) ans[key] = ans[key].includes(b.dataset.k) ? ans[key].filter((x) => x !== b.dataset.k) : ans[key].concat(b.dataset.k); else ans[key] = b.dataset.k; paint(); });
        paint();
    };
    $('npRun').addEventListener('click', async () => {
        if (!$('npLoc').value.trim()) return window.toast?.('Say where the field is.', 'error');
        $('npRun').disabled = true;
        try {
            await save();
            window.closeSheet('npAneeSheet');
            window.aneeWait.show({ title: 'Anee is checking your plan…', sub: 'The soil, the season and the timing. About a minute.', lines: ['Weighing each nutrient against the crop…', 'Reading the soils of your area…', 'Checking the rains around planting…', 'Deciding what to split and when…'] });
            const r = await window.api(U.analyze, { method: 'POST', body: { calcId, location: $('npLoc').value.trim(), soilConditions: ans.soil, phValue: $('npPh').value ? Number($('npPh').value) : null,
                water: ans.water, plantingDate: $('npPlant').value || null, timing: ans.timing, variety: $('npVariety').value.trim(), notes: $('npNotes').value.trim() } });
            const d = r.data && r.data.status === 'ready' ? r.data : await window.aneeWait.poll({ id: r.data.id, job: U.job });
            await window.aneeWait.done({ title: 'Your plan, read.', line: 'Here is what to keep and what to change.' });
            OPT.balance = d.balance;
            showReport(d.report);
        } catch (err) { window.aneeWait.fail(); window.toast?.(err.message || 'The reading did not finish. Nothing was charged.', 'error'); }
        finally { $('npRun').disabled = !OPT.canUse; }
    });
    const showReport = (rep) => {
        const a = (rep || {}).anee || {};
        const v = String(a.verdict || '').toLowerCase().replace(/\s+/g, '-');
        const sec = (t, inner) => inner ? '<div class="np-card"><h3>' + esc(t) + '</h3><div class="np-rlist">' + inner + '</div></div>' : '';
        $('npRep').hidden = false;
        $('npRep').innerHTML = '<div class="np-rhead v-' + esc(v) + '"><small>Anee\'s verdict: ' + esc(a.verdict) + ' · ' + esc(rep.at || '') + '</small><h3>' + esc(a.headline) + '</h3><p>' + esc(a.summary) + '</p></div>'
            + sec('Nutrient by nutrient', (a.nutrients || []).map((x) => '<div class="np-ritem"><span class="np-st s-' + esc(String(x.status || '').toLowerCase()) + '">' + esc(x.status) + '</span><b>' + esc(x.nutrient) + '</b> · ' + esc(x.comment) + '</div>').join(''))
            + sec('When to apply each one', (a.timing || []).map((x) => '<div class="np-ritem"><b>' + esc(x.product) + '</b> · ' + esc(x.when) + '<br>' + esc(x.how) + ' <span class="text-gray-500">' + esc(x.why) + '</span></div>').join(''))
            + sec('What to change', (a.changes || []).map((x) => '<div class="np-ritem"><b>' + esc(x.change) + '</b><br><span class="text-gray-500">' + esc(x.why) + '</span></div>').join(''))
            + sec('Your soil, the season and the yield', ['soil', 'weather', 'biofertilizers', 'yield'].filter((k) => a[k]).map((k) => '<div class="np-ritem">' + esc(a[k]) + '</div>').join(''))
            + sec('Be careful', (a.warnings || []).filter(Boolean).map((w) => '<div class="np-ritem">' + esc(w) + '</div>').join(''))
            + '<p class="np-fine">Confidence: ' + esc(a.confidence || '') + '. A guide, not a promise: the weather, the variety and the soil still decide the harvest.</p>';
        $('npRep').scrollIntoView({ behavior: still() ? 'auto' : 'smooth', block: 'start' });
    };

    /* ---- saved ---- */
    let savedPage = 1, savedQ = '';
    const loadSaved = async (more = false) => {
        if (!more) { savedPage = 1; $('npSaved').innerHTML = ''; }
        try {
            const r = await window.api(U.list + '?page=' + savedPage + '&q=' + encodeURIComponent(savedQ));
            $('npSaved').insertAdjacentHTML('beforeend', (r.data.rows || []).map((x) => '<button type="button" class="np-srow" data-id="' + x.id + '"><span><b>' + esc(String(x.title || '').replace(/ — /g, ', ')) + '</b><small>' + esc(x.at) + (x.npk ? ' · ' + esc(x.npk) + ' per ha' : '') + '</small></span>' + (x.analyzed ? '<em>Read by Anee</em>' : '') + '</button>').join(''));
            $('npSavedEmpty').hidden = !!$('npSaved').children.length;
            $('npSavedMore').hidden = !r.data.hasMore;
        } catch (err) { window.toast?.(err.message, 'error'); }
    };
    $('npSaved').addEventListener('click', async (e) => {
        const b = e.target.closest('.np-srow');
        if (!b) return;
        try {
            const r = await window.api(U.one(b.dataset.id));
            const d = r.data, set = (d.result || {}).setup || {};
            calcId = d.id;
            $('npVariety').value = set.variety || '';
            st.areaUnit = set.areaUnit === 'sqm' ? 'sqm' : 'ha';
            $('npArea').value = set.areaValue || (st.areaUnit === 'sqm' ? d.areaHa * 10000 : d.areaHa);
            tagSay('npAreaUnit', AREA_U, st.areaUnit);
            const pot = set.potential;
            st.potUnit = pot && POT_U[pot.key] ? pot.key : 't';
            $('npPotential').value = pot ? pot.value : (d.targetYield ?? '');
            tagSay('npPotUnit', POT_U, st.potUnit);
            const seed = set.seed;
            st.seedUnitSet = !!seed;
            if (seed && SEED_U[seed.key]) { st.seedUnit = seed.key; tagSay('npSeedUnit', SEED_U, st.seedUnit); }
            $('npSeed').value = seed ? seed.amount : '';
            const s = d.soil;
            $('npSoilOn').checked = !!s; $('npSoil').classList.toggle('is-on', !!s);
            $('npSoilGrid').querySelectorAll('input').forEach((i) => { i.value = s && s[i.dataset.s] != null ? s[i.dataset.s] : ''; });
            if (s) { st.pMethod = s.pMethod === 'bray' ? 'bray' : 'olsen'; st.kUnit = s.kUnit === 'meq' ? 'meq' : 'ppm'; tagSay('npPMethod', P_METHOD, st.pMethod); tagSay('npKUnit', K_UNIT, st.kUnit); }
            st.lines = (d.lines || []).filter((l) => OPT.products.some((p) => p.id === l.id)).map((l) => ({ id: l.id, amount: l.amount, unit: l.unit }));
            tab(false); lines(); setCrop(d.crop || null);
            if (d.analysis) showReport(d.analysis.report); else $('npRep').hidden = true;
        } catch (err) { window.toast?.(err.message, 'error'); }
    });
    $('npSavedMore').addEventListener('click', () => { savedPage++; loadSaved(true); });
    let sq = null;
    $('npSavedQ').addEventListener('input', () => { clearTimeout(sq); sq = setTimeout(() => { savedQ = $('npSavedQ').value.trim(); loadSaved(); }, 300); });
    const tab = (saved) => {
        $('npTabCalc').classList.toggle('is-on', !saved); $('npTabSaved').classList.toggle('is-on', saved);
        $('npCalc').hidden = saved; $('npSavedPane').hidden = !saved;
        if (saved) { BR.leave(); loadSaved(); } else if (!$('npPaneBarrel').hidden) BR.enter();
    };
    $('npTabCalc').addEventListener('click', () => tab(false));
    $('npTabSaved').addEventListener('click', () => tab(true));

    const boot = async () => {
        try {
            const r = await window.api(U.options);
            OPT = r.data;
            $('npCats').innerHTML = '<button type="button" class="np-cat is-on" data-c="all">All</button>' + Object.entries(OPT.categories).map(([k, v]) => '<button type="button" class="np-cat" data-c="' + esc(k) + '">' + esc(v) + '</button>').join('');
            pills($('npSoilCond'), OPT.soilConditions, true, 'soil');
            pills($('npWater'), OPT.water, false, 'water');
            pills($('npTiming'), OPT.timing, false, 'timing');
            lines(); hints(); calc();
        } catch (err) { window.toast?.(err.message || 'Could not load the calculator.', 'error'); }
    };
    if (window.api) boot(); else window.addEventListener('load', boot, { once: true });
})();
</script>
@endpush
