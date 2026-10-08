@extends('layouts.app')
@section('title', 'NPK Plus Calculator')
@section('page-title', 'NPK Plus')
@section('page-subtitle', 'Every nutrient in your fertilizer plan')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))
{{-- No phone tab bar here (the owner's call): the calculator has the whole screen. --}}
@section('body-class', 'hide-tabbar')

@section('content')
@include('partials.tag-sheet-css')
<style>
    /* ---- NPK PLUS (2026-10-07, the need model 2026-10-08) ----------------
       A wizard in five steps (crop, yield, planting, soil, fertilizers) with
       Back and Next; every choice is a tag that opens a sheet. What the plan
       gives reads in three tabs against what the crop NEEDS for the farmer's
       yield goal on their soil (App\Support\NpkModel): NPK as the bag reads
       them, the secondary, micro and beneficial elements, and Liebig's
       barrel. The house curve on every change, held still under reduced
       motion. */
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
    .np-hint.is-warn { color: #b45309; font-weight: 700; }
    .np-row { display: flex; gap: .5rem; } .np-row > * { min-width: 0; }
    .np-two { display: grid; gap: 0 .8rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 560px) { .np-two { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

    /* The wizard: five steps, Back and Next. */
    .np-wiz { padding: 0; overflow: hidden; }
    .np-wsteps { position: relative; display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .15rem; padding: .85rem .6rem .75rem; border-bottom: 1px solid var(--color-gray-100); }
    html.dark .np-wsteps { border-color: #2b3a1c; }
    .np-wline { position: absolute; left: 8.33%; right: 8.33%; top: 1.72rem; height: 2px; background: var(--color-gray-200); }
    .np-wline span { display: block; height: 100%; width: var(--p, 0%); background: var(--color-brand-600); transition: width .4s var(--np-ease); }
    html.dark .np-wline { background: #2b3a1c; }
    .np-ws { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; gap: .3rem; min-width: 0; padding: 0; border: 0; background: none; cursor: pointer;
        font-size: .66rem; font-weight: 800; color: var(--color-gray-400); transition: color .28s var(--np-ease); }
    .np-ws i { display: grid; place-items: center; width: 1.75rem; height: 1.75rem; border-radius: 999px; font-style: normal; font-size: .78rem; color: var(--color-gray-500);
        background: var(--color-gray-100); box-shadow: 0 0 0 3px var(--color-white); transition: background-color .28s var(--np-ease), color .28s var(--np-ease), transform .28s var(--np-ease); }
    html.dark .np-ws i { background: #1c2616; box-shadow: 0 0 0 3px #151b12; }
    .np-ws span { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .np-ws.is-on { color: var(--color-brand-700, #3d6823); }
    .np-ws.is-on i { color: #fff; background: var(--color-brand-600); transform: scale(1.1); }
    .np-ws.is-done i { color: #2d5016; background: #e4efd4; }
    html.dark .np-ws.is-done i { color: #a5c97e; background: #1c2c10; }
    .np-wbody { padding: 1rem 1.05rem .5rem; min-height: 15rem; }
    .np-wstep[hidden] { display: none; }
    .np-wstep.go-next { animation: npNext .34s var(--np-ease) both; }
    .np-wstep.go-back { animation: npBack .34s var(--np-ease) both; }
    @keyframes npNext { from { opacity: 0; transform: translateX(22px); } }
    @keyframes npBack { from { opacity: 0; transform: translateX(-22px); } }
    .np-wq { font-family: var(--font-heading); font-weight: 800; font-size: 1.08rem; line-height: 1.3; color: var(--color-gray-900); }
    .np-wnav { display: flex; align-items: center; gap: .6rem; padding: .8rem 1.05rem 1rem; border-top: 1px solid var(--color-gray-100); }
    html.dark .np-wnav { border-color: #2b3a1c; }
    .np-wnav small { flex: 1 1 auto; text-align: center; font-size: .72rem; font-weight: 700; color: var(--color-gray-400); white-space: nowrap; }
    .np-wnav .btn { min-width: 6.2rem; white-space: nowrap; }
    .np-goal { margin-top: .9rem; padding: .7rem .8rem; border-radius: .9rem; font-size: .8rem; line-height: 1.5; color: #24400f; background: #f3f8ec; border: 1px solid #c9e0ad; }
    html.dark .np-goal { color: #d5e3c5; background: #17220f; border-color: #2b3a1c; }
    .np-goal b { color: inherit; }
    .np-facts { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .7rem; }
    .np-facts span { padding: .3rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 700; color: var(--color-gray-600); background: var(--color-gray-100); animation: npIn .3s var(--np-ease) both; }
    html.dark .np-facts span { background: #1c2616; color: #b7c4aa; }
    .np-disc { margin-top: .9rem; display: flex; gap: .55rem; align-items: flex-start; padding: .7rem .8rem; border-radius: .9rem; font-size: .76rem; line-height: 1.55; color: #6b4a00;
        background: #fff8e1; border: 1px solid #f5d77a; }
    .np-disc svg { width: 1.1rem; height: 1.1rem; flex: none; margin-top: .1rem; color: #b45309; }
    html.dark .np-disc { color: #f3dc9a; background: #2a2208; border-color: #5c4a10; }

    /* A unit, or any small choice: a tag that opens a sheet. */
    .np-utag { display: inline-flex; align-items: center; justify-content: space-between; gap: .35rem; flex: none; min-width: 5.4rem; max-width: 60%; padding: 0 .7rem; border-radius: .75rem;
        font-size: .82rem; font-weight: 800; color: #3d6823; background: var(--color-brand-50, #f3f8ec); border: 1px solid var(--color-brand-200, #c9e0ad); cursor: pointer;
        transition: border-color .28s var(--np-ease), background-color .28s var(--np-ease), transform .28s var(--np-ease); }
    .np-utag:hover { border-color: var(--color-brand-500, #4a7c2a); transform: translateY(-1px); }
    .np-utag span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .np-utag svg { width: .85rem; height: .85rem; flex: none; color: var(--color-gray-400); }
    .np-utag.is-inline { min-height: 2.4rem; }
    .np-utag.is-wide { width: 100%; max-width: none; min-height: 2.6rem; }
    .np-utag.is-none span { color: var(--color-gray-500); font-weight: 600; }
    html.dark .np-utag { background: #1c2c10; border-color: #2f4a1a; color: #a5c97e; }
    .np-pills { display: flex; flex-wrap: wrap; gap: .35rem; }
    .np-pill { display: inline-flex; align-items: center; gap: .3rem; padding: .45rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 700; cursor: pointer;
        color: var(--color-gray-700); background: var(--color-white); border: 1px solid var(--color-gray-200);
        transition: background-color .28s var(--np-ease), border-color .28s var(--np-ease), color .28s var(--np-ease), transform .28s var(--np-ease); }
    .np-pill:hover { border-color: var(--color-brand-600); }
    .np-pill[aria-pressed="true"] { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    #npPickFoot[hidden], #npPickSearchBox[hidden] { display: none; }
    .np-psearch { position: relative; margin-bottom: .6rem; }
    .np-psearch svg { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); width: 1.05rem; height: 1.05rem; color: var(--color-gray-400); pointer-events: none; }
    .np-psearch .form-input { padding-left: 2.4rem; }
    /* The season, read from ten years of weather at the field. */
    .np-season { margin-top: .9rem; padding: .75rem .85rem; border-radius: .95rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    html.dark .np-season { background: #121a0d; border-color: #2b3a1c; }
    .np-season b { color: var(--color-gray-900); }
    .np-season .np-enso { display: inline-block; margin-top: .35rem; padding: .15rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #9a3412; background: #ffedd5; }
    .np-season .np-enso.is-wet { color: #1e40af; background: #dbeafe; }
    .np-season .np-enso.is-flat { color: #334155; background: #e2e8f0; }
    .np-date { width: 100%; justify-content: flex-start; }
    .lb-sky { margin-top: .9rem; }
    .lb-sky h4 { font-family: var(--font-heading); font-weight: 800; font-size: .9rem; color: var(--color-gray-900); }
    .lb-skyrow { display: grid; grid-template-columns: 2rem minmax(0, 1fr) auto; gap: .55rem; align-items: center; margin-top: .45rem; padding: .5rem .6rem; border-radius: .8rem;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    html.dark .lb-skyrow { background: #121a0d; border-color: #2b3a1c; }
    .lb-skyrow > i { display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: .6rem; font-style: normal; font-size: 1.05rem; background: #fef3c7; }
    .lb-skyrow.is-water > i { background: #dbeafe; } .lb-skyrow.is-temp > i { background: #fee2e2; }
    .lb-skyrow b { display: block; font-size: .82rem; color: var(--color-gray-900); }
    .lb-skyrow small { display: block; font-size: .7rem; line-height: 1.4; color: var(--color-gray-500); }
    .lb-skyrow em { font-style: normal; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: var(--color-gray-900); }
    .lb-rainyears { display: flex; align-items: flex-end; gap: 3px; height: 3.2rem; margin-top: .55rem; }
    .lb-rainyears span { flex: 1 1 0; min-width: 0; border-radius: 3px 3px 0 0; background: #93c5fd; position: relative; transition: height .6s var(--np-ease); }
    .lb-rainyears span.is-now { background: repeating-linear-gradient(135deg, #f97316 0 4px, #fdba74 4px 8px); }
    .lb-rainyears span b { position: absolute; left: 50%; bottom: -1.05rem; transform: translateX(-50%); font-size: .52rem; font-weight: 700; color: var(--color-gray-400); white-space: nowrap; }
    .lb-rainkey { margin-top: 1.25rem; font-size: .68rem; color: var(--color-gray-500); }

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
    .np-testbox { margin-top: 1rem; padding: .8rem; border-radius: .9rem; border: 1px dashed var(--color-gray-200); }
    html.dark .np-testbox { border-color: #2b3a1c; }

    /* What the plan gives: three tabs. */
    .np-otabs { position: relative; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: .8rem; padding: .25rem; border-radius: .95rem; background: var(--color-gray-100); }
    html.dark .np-otabs { background: #121a0d; }
    .np-otab { position: relative; z-index: 1; padding: .5rem .3rem; border: 0; background: none; border-radius: .75rem; font-size: .82rem; font-weight: 800; color: var(--color-gray-500); cursor: pointer;
        transition: color .28s var(--np-ease); }
    .np-otab.is-on { color: #fff; }
    .np-otab-ind { position: absolute; z-index: 0; top: .25rem; bottom: .25rem; left: .25rem; width: calc((100% - .5rem) / 3); border-radius: .75rem; background: var(--color-brand-600);
        transform: translateX(calc(var(--i, 0) * 100%)); transition: transform .28s var(--np-ease); box-shadow: 0 6px 14px -8px rgb(45 80 22 / .8); }
    #npOut.is-flash { animation: npFlash 1s var(--np-ease) both; }
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
    /* Plan against need: one bar per nutrient, the need a mark on it. */
    .np-nrows { display: grid; gap: .55rem; margin-top: .4rem; }
    .np-nrow { display: grid; grid-template-columns: 3rem minmax(0, 1fr) 4.4rem; gap: .55rem; align-items: center; }
    .np-nrow > span:first-child { font-size: .78rem; font-weight: 800; color: var(--color-gray-800); }
    .np-nbar { position: relative; height: .75rem; border-radius: 999px; background: var(--color-gray-100); }
    html.dark .np-nbar { background: #1c2616; }
    .np-nbar .have { position: absolute; left: 0; top: .12rem; bottom: .12rem; border-radius: 999px; background: #4a7c2a; transition: width .6s var(--np-ease); }
    .np-nbar .need { position: absolute; top: -.25rem; bottom: -.25rem; width: 3px; margin-left: -1.5px; border-radius: 2px; background: var(--color-gray-800); transition: left .6s var(--np-ease); }
    html.dark .np-nbar .need { background: #e8efe1; }
    .np-nrow small { grid-column: 2 / 4; margin-top: -.3rem; font-size: .7rem; color: var(--color-gray-500); }
    .np-nrow em { font-style: normal; text-align: right; font-size: .7rem; font-weight: 800; color: var(--color-gray-500); }
    .np-nrow.is-short .have { background: #f59e0b; } .np-nrow.is-over .have { background: #dc2626; }
    .np-nrow.is-short em { color: #b45309; } .np-nrow.is-over em { color: #b91c1c; } .np-nrow.is-right em { color: #2d5016; }
    html.dark .np-nrow.is-right em { color: #a5c97e; }
    .np-say { margin-top: .8rem; padding: .75rem .85rem; border-radius: .9rem; font-size: .84rem; line-height: 1.55; color: #24400f; background: #f3f8ec; border: 1px solid #c9e0ad; }
    html.dark .np-say { color: #d5e3c5; background: #17220f; border-color: #2b3a1c; }
    .np-src { margin-top: .7rem; font-size: .76rem; line-height: 1.55; color: var(--color-gray-600); }
    .np-src summary { cursor: pointer; font-weight: 800; color: var(--color-brand-700); }
    .np-src p, .np-src ul { margin-top: .4rem; }
    .np-src ul { padding-left: 1.1rem; list-style: disc; }
    .np-src li + li { margin-top: .25rem; }
    .np-src a { color: var(--color-brand-700); text-decoration: underline; overflow-wrap: anywhere; }
    .np-fine { margin-top: .8rem; font-size: .72rem; line-height: 1.55; color: var(--color-gray-500); }

    /* The micros tab: need and plan per element. */
    .np-mh { margin: 1rem 0 .4rem; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); }
    .np-mlist { display: grid; gap: .4rem; }
    .np-mrow { display: grid; grid-template-columns: 2.3rem minmax(0, 1fr) auto; gap: .6rem; align-items: center; padding: .55rem .65rem; border-radius: .85rem;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-100); animation: npIn .3s var(--np-ease) both; animation-delay: calc(var(--k, 0) * 35ms); }
    html.dark .np-mrow { background: #121a0d; border-color: #2b3a1c; }
    .np-mrow > i { display: grid; place-items: center; width: 2.3rem; height: 2.3rem; border-radius: .7rem; font-style: normal; font-family: var(--font-heading); font-weight: 800; font-size: .88rem; color: #fff;
        background: linear-gradient(135deg, #0f766e, #14b8a6); }
    .np-mrow.is-sec > i { background: linear-gradient(135deg, #a16207, #eab308); }
    .np-mrow.is-ben > i { background: linear-gradient(135deg, #475569, #94a3b8); }
    .np-mrow.is-noflag > i, .np-mrow.is-notneeded > i { opacity: .55; }
    .np-mrow b { display: block; font-size: .84rem; color: var(--color-gray-900); }
    .np-mrow small { display: block; font-size: .7rem; line-height: 1.4; color: var(--color-gray-500); }
    .np-mrow.is-short .np-mtxt small { color: #b45309; }
    .np-ben { display: inline-block; margin-left: .3rem; padding: .05rem .4rem; border-radius: 999px; font-size: .6rem; font-weight: 800; color: #475569; background: #e2e8f0; vertical-align: 1px; }
    html.dark .np-ben { color: #cbd5e1; background: #334155; }
    .np-mbar { position: relative; display: block; height: .45rem; margin-top: .35rem; border-radius: 999px; background: var(--color-gray-100); }
    html.dark .np-mbar { background: #1c2616; }
    .np-mbar .have { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 999px; background: #0f766e; transition: width .6s var(--np-ease); }
    .np-mbar .need { position: absolute; top: -.2rem; bottom: -.2rem; width: 2px; margin-left: -1px; background: var(--color-gray-700); }
    html.dark .np-mbar .need { background: #e8efe1; }
    .np-mrow.is-short .np-mbar .have { background: #f59e0b; } .np-mrow.is-over .np-mbar .have { background: #dc2626; }
    .np-mval { text-align: right; min-width: 4.6rem; }
    .np-mval em { display: block; font-style: normal; font-size: .7rem; font-weight: 800; color: var(--color-gray-500); }
    .np-mrow.is-short .np-mval em { color: #b45309; } .np-mrow.is-over .np-mval em { color: #b91c1c; } .np-mrow.is-right .np-mval em, .np-mrow.is-testok .np-mval em { color: #2d5016; }
    html.dark .np-mrow.is-right .np-mval em, html.dark .np-mrow.is-testok .np-mval em { color: #a5c97e; }
    .np-mrow small.np-down { color: var(--color-gray-500); font-style: italic; }

    /* Liebig's barrel: CSS in 3D, turned by a finger or a mouse. */
    .lb { margin-top: .4rem; }
    .lb-stage { position: relative; height: 24.5rem; display: grid; place-items: center; perspective: 900px; perspective-origin: 50% 28%; cursor: grab; touch-action: pan-y;
        user-select: none; -webkit-user-select: none; border-radius: 1rem; outline: none; overflow: hidden;
        background: radial-gradient(70% 45% at 50% 88%, rgb(74 124 42 / .16), transparent 70%), linear-gradient(180deg, rgb(125 211 252 / .1), transparent 55%); }
    .lb-stage:focus-visible { box-shadow: 0 0 0 3px rgb(74 124 42 / .45); }
    .lb-stage.is-grab { cursor: grabbing; }
    .lb-barrel { position: relative; width: 160px; height: 190px; margin-top: 1.6rem; transform-style: preserve-3d; transform: rotateX(-16deg) rotateY(-18deg); }
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
    /* Solid planks, never see-through: a plank that is not counted is pale
       raw wood, a plank NPK Plus cannot measure is weathered grey. */
    .lb-st.is-unknown .lb-out, .lb-st.is-unknown .lb-in { filter: saturate(.25) brightness(1.22); }
    .lb-st.is-dull .lb-out, .lb-st.is-dull .lb-in { filter: grayscale(1) brightness(.8); }
    .lb-st.is-limit .lb-out b { padding: 2px 0; background: rgb(180 83 9 / .85); }
    .lb-spill { position: absolute; right: 3px; top: -3px; bottom: 0; width: 8px; transform: translateZ(2px); backface-visibility: hidden; -webkit-backface-visibility: hidden;
        border-radius: 5px 5px 2px 2px; opacity: 0; transition: opacity .5s var(--np-ease) .9s;
        background: repeating-linear-gradient(180deg, rgb(147 205 255 / .95) 0 9px, rgb(56 140 225 / .85) 9px 18px); background-size: 100% 18px; animation: lbFlow .55s linear infinite; }
    .lb-st.is-spill .lb-spill { opacity: .92; }
    @keyframes lbFlow { to { background-position: 0 18px; } }
    /* Where the spill lands: a pool on the ground in front of the plank,
       spreading with rings, turning with the barrel. */
    .lb-pool { position: absolute; left: 50%; bottom: 0; width: 92px; height: 58px; margin-left: -40px; border-radius: 50%; pointer-events: none;
        transform: translateY(50%) translateZ(30px) rotateX(90deg) scale(.2); opacity: 0;
        background: radial-gradient(closest-side, rgb(160 214 255 / .92), rgb(70 150 225 / .78) 62%, rgb(56 140 225 / 0));
        transition: transform 1.1s var(--np-ease) 1.2s, opacity .6s var(--np-ease) 1.1s; }
    .lb-pool::before, .lb-pool::after { content: ""; position: absolute; left: 50%; top: 42%; width: 18px; height: 12px; margin: -6px 0 0 -9px; border-radius: 50%;
        border: 2px solid rgb(225 242 255 / .85); opacity: 0; animation: lbRing 1.6s ease-out infinite; }
    .lb-pool::after { animation-delay: .8s; }
    @keyframes lbRing { 0% { transform: scale(.4); opacity: .9; } 100% { transform: scale(3.4); opacity: 0; } }
    .lb-st.is-spill .lb-pool { opacity: 1; transform: translateY(50%) translateZ(30px) rotateX(90deg) scale(1); }
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
    .lb-barrel.is-rest .lb-spill, .lb-barrel.is-rest .lb-pool { opacity: 0 !important; transition: none; }
    .lb-tag { position: absolute; left: .7rem; top: .6rem; max-width: calc(100% - 4rem); padding: .3rem .6rem; border-radius: .7rem; font-size: .7rem; font-weight: 700; line-height: 1.35;
        color: #2d5016; background: rgb(255 255 255 / .85); border: 1px dashed rgb(74 124 42 / .6); pointer-events: none; }
    html.dark .lb-tag { color: #d5e3c5; background: rgb(21 27 18 / .85); }
    .lb-info { position: absolute; right: .6rem; top: .55rem; z-index: 2; display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 999px; cursor: pointer;
        font-family: Georgia, 'Times New Roman', serif; font-style: italic; font-weight: 700; font-size: 1.05rem; color: #2d5016; background: rgb(255 255 255 / .9);
        border: 1px solid rgb(74 124 42 / .5); box-shadow: 0 6px 14px -8px rgb(0 0 0 / .5); transition: transform .28s var(--np-ease); }
    .lb-info:hover { transform: scale(1.08); }
    html.dark .lb-info { color: #d5e3c5; background: rgb(21 27 18 / .9); }
    /* "The water is your yield": an arrow drawn by hand onto the stage. */
    .lb-arrow { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; overflow: visible; opacity: 0; transition: opacity .3s var(--np-ease); }
    .lb-arrow.is-on { opacity: 1; }
    .lb-arrow path { fill: none; stroke: #1d6fd1; stroke-width: 2.6; stroke-linecap: round; stroke-linejoin: round; }
    html.dark .lb-arrow path { stroke: #7cc0ff; }
    .lb-arrow .lb-ap { transition: stroke-dashoffset 1s var(--np-ease); }
    .lb-arrow .lb-ah { opacity: 0; transition: opacity .3s var(--np-ease) .85s; }
    .lb-arrow.is-drawn .lb-ah { opacity: 1; }
    .lb-alab b { display: block; margin-top: .15rem; font-size: .84rem; font-style: normal; line-height: 1.15; }
    .lb-alab { position: absolute; right: .45rem; top: 42%; width: 5.8rem; padding: .25rem .35rem; border-radius: .55rem; text-align: center; pointer-events: none;
        font-family: var(--font-heading); font-style: italic; font-weight: 800; font-size: .74rem; line-height: 1.15; color: #1d6fd1; background: rgb(255 255 255 / .82);
        transform: rotate(-5deg) scale(.9); opacity: 0; transition: opacity .4s var(--np-ease), transform .4s var(--np-ease); }
    .lb-alab.is-on { opacity: 1; transform: rotate(-5deg) scale(1); }
    html.dark .lb-alab { color: #7cc0ff; background: rgb(21 27 18 / .82); }
    .lb-turn { position: absolute; right: .7rem; bottom: .6rem; display: flex; align-items: center; gap: .3rem; font-size: .68rem; font-weight: 700; color: var(--color-gray-500);
        pointer-events: none; transition: opacity .5s var(--np-ease); }
    .lb-turn svg { width: 1rem; height: 1rem; animation: lbNudge 2.4s ease-in-out infinite; }
    @keyframes lbNudge { 50% { transform: translateX(4px); } }
    .lb-stage.was-turned .lb-turn { opacity: 0; }
    .lb-key { display: grid; gap: .3rem; margin-top: .7rem; }
    .lb-k { display: grid; grid-template-columns: .7rem minmax(0, 1fr) 3.2rem 4.4rem; gap: .45rem; align-items: center; width: 100%; padding: .35rem .45rem; border-radius: .6rem; text-align: left;
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
    .lb-k em { font-style: normal; font-size: .66rem; font-weight: 800; text-align: right; color: var(--color-gray-500); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lb-k.is-short em { color: #b45309; } .lb-k.is-over em { color: #b91c1c; } .lb-k.is-right em, .lb-k.is-fixed em { color: #2d5016; }
    .lb-reach { display: flex; align-items: baseline; gap: .55rem; margin-top: .7rem; font-size: .8rem; line-height: 1.45; color: var(--color-gray-600); }
    .lb-reach[hidden] { display: none; }
    .lb-reach b { flex: none; font-family: var(--font-heading); font-size: 1.7rem; font-weight: 800; line-height: 1; color: #1d6fd1; }
    html.dark .lb-reach b { color: #7cc0ff; }
    .np-law p + p { margin-top: .65rem; }
    .np-law p { font-size: .88rem; line-height: 1.6; color: var(--color-gray-700); }
    .np-law h4 { margin-top: 1rem; margin-bottom: .3rem; font-family: var(--font-heading); font-weight: 800; font-size: .95rem; color: var(--color-gray-900); }
    .np-law-fig { display: grid; place-items: center; margin-bottom: .8rem; padding: .8rem; border-radius: 1rem; background: linear-gradient(180deg, rgb(125 211 252 / .14), transparent); }
    .np-law-fig svg { width: 100%; max-width: 15rem; height: auto; }

    .np-acts { display: grid; gap: .5rem; margin-top: .9rem; }
    .np-anee { display: flex; align-items: center; justify-content: center; gap: .5rem; padding: .85rem 1rem; border-radius: .95rem; border: 0; cursor: pointer; font-weight: 800;
        color: #1a1a1a; background: linear-gradient(135deg, #f7d23a, #f5c518); transition: transform .28s var(--np-ease); }
    .np-anee:hover { transform: translateY(-1px); } .np-anee:disabled { opacity: .5; transform: none; cursor: default; }
    .np-anee img { width: 1.6rem; height: 1.6rem; border-radius: 999px; }
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
        .np-chem span, .np-line, .np-line.is-flash, #npOut.is-flash, .np-pane, .np-mrow, .np-wstep.go-next, .np-wstep.go-back, .np-facts span, .lb-spill, .lb-top, .lb-turn svg, .lb-pool::before, .lb-pool::after { animation: none; }
        .np-hero-fold, .np-hero-chev, .np-soil, .np-nbar .have, .np-nbar .need, .np-pill, .np-otab-ind, .lb-st, .lb-water, .lb-pool, .lb-kbar span, .np-utag, .np-qty, .np-add, .np-wline span, .np-ws i, .lb-arrow .lb-ap, .lb-alab { transition: none; }
    }
    html.sm-still .lb-spill, html.sm-still .lb-top, html.sm-still .np-chem span, html.sm-still .lb-pool::before, html.sm-still .lb-pool::after { animation: none; }
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
            <p>NPK Plus works out what your crop needs for your yield goal on your soil, adds up every nutrient in your plan, and shows the shortest plank in the barrel. Free, and as many times as you like.</p>
            <ol class="np-steps">
                <li>Pick the crop, your yield goal, what you plant and your soil, one step at a time.</li>
                <li>Add each fertilizer you plan to use, and how much.</li>
                <li>Read the NPK, the micronutrients and Liebig's barrel against the need. Anee can read it further.</li>
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
                <div class="np-card np-wiz" id="npWiz">
                    <div class="np-wsteps" id="npWSteps">
                        <span class="np-wline" aria-hidden="true"><span id="npWLine"></span></span>
                        <button type="button" class="np-ws is-on" data-s="0"><i>1</i><span>Crop</span></button>
                        <button type="button" class="np-ws" data-s="1"><i>2</i><span>Yield</span></button>
                        <button type="button" class="np-ws" data-s="2"><i>3</i><span>Planting</span></button>
                        <button type="button" class="np-ws" data-s="3"><i>4</i><span>Season</span></button>
                        <button type="button" class="np-ws" data-s="4"><i>5</i><span>Soil</span></button>
                        <button type="button" class="np-ws" data-s="5"><i>6</i><span>Fertilizers</span></button>
                    </div>
                    <div class="np-wbody">
                        <section class="np-wstep" data-s="0">
                            <p class="np-wq">What will you grow?</p>
                            <p class="np-sub">The need is worked out from this crop's numbers.</p>
                            <span class="np-label">Crop</span>
                            <button type="button" class="crop-tag" id="npCropBtn">
                                <span class="crop-tag-e" id="npCropIcon">🌱</span>
                                <span class="crop-tag-t is-none" id="npCropNow">Choose the crop</span>
                                <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                            </button>
                            <div class="np-facts" id="npFacts"></div>
                        </section>
                        <section class="np-wstep" data-s="1" hidden>
                            <p class="np-wq">The variety and your yield goal</p>
                            <p class="np-sub">The bigger the harvest you aim for, the more the crop takes from the field.</p>
                            <label class="np-label" for="npVariety">Variety <span>(optional)</span></label>
                            <input type="text" id="npVariety" class="form-input w-full" maxlength="80" autocomplete="off" placeholder="{{ \App\Support\Region::ph() ? 'Like NSIC Rc222' : 'Like Pioneer P1197' }}">
                            <div class="np-row" style="align-items:center;margin-top:.85rem"><span class="np-label" style="margin:0;flex:1 1 auto">Yields in</span>
                                <button type="button" class="np-utag is-inline" id="npYUnit" aria-label="Yield unit"><span>t/ha</span>{!! $chev !!}</button></div>
                            <div class="np-two">
                                <div>
                                    <label class="np-label" for="npPotential">Potential of the variety <span>(optional)</span></label>
                                    <input type="number" id="npPotential" class="form-input w-full" min="0" step="any" inputmode="decimal" placeholder="Like 8">
                                    <span class="np-hint">On the seed bag or the variety's description.</span>
                                </div>
                                <div>
                                    <label class="np-label" for="npTarget">Your yield goal <span>(optional)</span></label>
                                    <input type="number" id="npTarget" class="form-input w-full" min="0" step="any" inputmode="decimal" placeholder="Like 6">
                                    <span class="np-hint">Empty: 80% of the potential, the most a well run field usually reaches.</span>
                                </div>
                            </div>
                            <div class="np-goal" id="npGoal"></div>
                        </section>
                        <section class="np-wstep" data-s="2" hidden>
                            <p class="np-wq">What you plant, and where</p>
                            <p class="np-sub">Too thin a stand cannot reach the goal; NPK Plus counts for what it can.</p>
                            <label class="np-label" for="npSeed">Seeds or planting material <span>(optional)</span></label>
                            <div class="np-row"><input type="number" id="npSeed" class="form-input flex-1" min="0" step="any" inputmode="decimal" placeholder="Like 40">
                                <button type="button" class="np-utag is-inline" id="npSeedUnit" aria-label="What you plant"><span>kg</span>{!! $chev !!}</button></div>
                            <span class="np-hint" id="npSeedHint">How much you plant on the whole area.</span>
                            <label class="np-label" for="npGerm">Germination <span>(optional, on the seed tag)</span></label>
                            <div class="np-row" style="align-items:center"><input type="number" id="npGerm" class="form-input flex-1" min="1" max="100" step="1" inputmode="numeric" placeholder="Like 85">
                                <span class="np-hint" style="margin:0;flex:none;font-weight:800">%</span></div>
                            <span class="np-hint" id="npGermHint">Seed that does not sprout grows nothing: NPK Plus counts only the seed that will.</span>
                            <label class="np-label" for="npArea">Area</label>
                            <div class="np-row"><input type="number" id="npArea" class="form-input flex-1" min="0" step="any" value="1" inputmode="decimal">
                                <button type="button" class="np-utag is-inline" id="npAreaUnit" aria-label="Area unit"><span>ha</span>{!! $chev !!}</button></div>
                        </section>
                        <section class="np-wstep" data-s="3" hidden>
                            <p class="np-wq">Where, when and the water</p>
                            <p class="np-sub">The sun, the rain and the heat of your season come from ten years of weather at your field, tilted by ENSO now.</p>
                            @if (\App\Support\Region::ph())
                                <span class="np-label">Province</span>
                                <button type="button" class="np-utag is-wide is-none" id="npProvBtn"><span>Choose the province</span>{!! $chev !!}</button>
                                <span class="np-label">Town or city</span>
                                <button type="button" class="np-utag is-wide is-none" id="npTownBtn" disabled><span>Pick the province first</span>{!! $chev !!}</button>
                            @else
                                <label class="np-label" for="npTownIn">Town or city</label>
                                <input type="text" id="npTownIn" class="form-input w-full" maxlength="120" placeholder="Like Davis">
                                <label class="np-label" for="npProvIn">State or province</label>
                                <input type="text" id="npProvIn" class="form-input w-full" maxlength="120" placeholder="Like California">
                            @endif
                            <span class="np-label">Planting date</span>
                            @include('partials.date-tag', ['id' => 'npPlanted', 'empty' => 'Pick the planting date', 'class' => 'np-date'])
                            <span class="np-label">Water</span>
                            <button type="button" class="np-utag is-wide" id="npWaterBtn"><span>Irrigated</span>{!! $chev !!}</button>
                            <div class="np-season" id="npSeason">Pick the place and the planting date to read the season.</div>
                        </section>
                        <section class="np-wstep" data-s="4" hidden>
                            <p class="np-wq">Your soil</p>
                            <p class="np-sub">It decides how much the soil gives on its own and how much of the fertilizer the crop can catch.</p>
                            <span class="np-label">Soil type</span>
                            <button type="button" class="np-utag is-wide is-none" id="npTexture"><span>Not sure</span>{!! $chev !!}</button>
                            <label class="np-label" for="npPh">Soil pH <span>(if you know it)</span></label>
                            <input type="number" id="npPh" class="form-input w-full" min="2" max="12" step="0.1" inputmode="decimal" placeholder="Like 5.8">
                            <span class="np-label">What else is true of it <span>(optional)</span></span>
                            <button type="button" class="np-utag is-wide is-none" id="npConds"><span>None picked</span>{!! $chev !!}</button>
                            <span class="np-hint">Acidic, alkaline, sodic, salty, acid sulfate, low in organic matter, peat or waterlogged.</span>
                            <div class="np-testbox">
                                <label class="np-switch"><input type="checkbox" id="npSoilOn"> <span><b>I have a soil test</b><br><span class="text-xs text-gray-500 font-normal">It sharpens the need for nitrogen, phosphorus, potassium, sulfur, zinc and boron.</span></span></label>
                                <div class="np-soil" id="npSoil"><div>
                                    <div class="np-cgrid" id="npSoilGrid"></div>
                                    <div class="np-soilu">
                                        <label>P method <button type="button" class="np-utag" id="npPMethod"><span>Olsen</span>{!! $chev !!}</button></label>
                                        <label>K in <button type="button" class="np-utag" id="npKUnit"><span>ppm</span>{!! $chev !!}</button></label>
                                    </div>
                                </div></div>
                            </div>
                        </section>
                        <section class="np-wstep" data-s="5" hidden>
                            <p class="np-wq">Your fertilizers</p>
                            <p class="np-sub">Add each product you plan to use, and how much for the whole area. Not on the list? Add it from its label.</p>
                            <div class="np-lines" id="npLines"></div>
                            <button type="button" class="np-add" id="npAddBtn"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>Add a fertilizer</button>
                        </section>
                    </div>
                    <div class="np-wnav">
                        <button type="button" class="btn btn-white" id="npBack" disabled>Back</button>
                        <small id="npStepOf">Step 1 of 6</small>
                        <button type="button" class="btn btn-primary" id="npNext">Next</button>
                    </div>
                </div>
            </div>

            <div class="np-out">
                <div class="np-card" id="npOut">
                    <h3><i>✓</i>What your plan gives</h3>
                    <p class="np-sub" id="npOutSub">Add a fertilizer to see the totals.</p>
                    <div class="np-otabs" role="tablist" id="npOTabs" style="--i:0">
                        <span class="np-otab-ind" aria-hidden="true"></span>
                        <button type="button" class="np-otab is-on" role="tab" aria-selected="true" data-p="npk">NPK</button>
                        <button type="button" class="np-otab" role="tab" aria-selected="false" data-p="micro">Micros</button>
                        <button type="button" class="np-otab" role="tab" aria-selected="false" data-p="barrel">Barrel</button>
                    </div>

                    <div class="np-pane" id="npPaneNpk" role="tabpanel">
                        <div class="np-big"><div><small>N</small><b id="npN">0</b><i>kg per ha</i></div><div><small>P₂O₅</small><b id="npP">0</b><i>kg per ha</i></div><div><small>K₂O</small><b id="npK">0</b><i>kg per ha</i></div></div>
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
                                <svg class="lb-arrow" id="lbArrow" aria-hidden="true"><path class="lb-ap"/><path class="lb-ah"/></svg>
                                <span class="lb-alab" id="lbALab" aria-hidden="true">The water is your yield</span>
                                <span class="lb-tag" id="lbTag">The rim is everything the crop needs.</span>
                                <button type="button" class="lb-info" id="lbInfo" aria-label="What is Liebig's law of the minimum?">i</button>
                                <span class="lb-turn" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12h15m0 0l-4-4m4 4l-4 4"/></svg>Drag to turn</span>
                            </div>
                            <div class="lb-reach" id="lbReach" hidden></div>
                            <div class="lb-key" id="lbKey"></div>
                            <div class="lb-sky" id="lbSky" hidden></div>
                            <div id="lbSay"></div>
                        </div>
                    </div>

                    <div class="np-disc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                        <span>A guide, not a promise. The need and the yield still depend on the variety, and on your soil: how much of its own nutrients the crop can actually use. The weather and how and when the fertilizer goes on matter too. A soil test makes all of it surer.</span></div>
                    <div class="np-acts">
                        <button type="button" class="btn btn-white" id="npSave">Save this calculation</button>
                        <button type="button" class="np-anee" id="npAnee"><img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="">Analyze further with Anee</button>
                    </div>
                </div>
                <div class="np-rep" id="npRep" hidden></div>
            </div>
        </div>
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

{{-- One sheet for every small choice: units, the soil type, its conditions. --}}
<div class="sheet hidden" id="npPickSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="npPickTitle">Choose</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="form-hint mt-0 mb-3" id="npPickHint" hidden></p>
        <div class="np-psearch" id="npPickSearchBox" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" id="npPickQ" class="form-input" autocomplete="off" placeholder="Search">
        </div>
        <div class="dt-rows" id="npPickRows"></div>
    </div>
    <div class="sheet-footer" id="npPickFoot" hidden>
        <button type="button" class="btn btn-primary" data-sheet-close>Done</button>
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

{{-- Liebig's law of the minimum, behind the barrel's i. --}}
<div class="sheet hidden" id="npLawSheet" style="--sheet-width:32rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Liebig's law of the minimum</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body np-law">
        <div class="np-law-fig" aria-hidden="true">
            <svg viewBox="0 0 240 150">
                <ellipse cx="120" cy="137" rx="96" ry="9" fill="rgb(20 33 12 / .16)"/>
                <ellipse cx="158" cy="139" rx="26" ry="5" fill="#5aa7ec" opacity=".7"/>
                <g stroke="#6f4519" stroke-width="1.5">
                    <rect x="56" y="22" width="16" height="114" rx="2" fill="#b47a40"/><rect x="74" y="40" width="16" height="96" rx="2" fill="#b47a40"/><rect x="92" y="14" width="16" height="122" rx="2" fill="#b47a40"/>
                    <rect x="110" y="30" width="16" height="106" rx="2" fill="#b47a40"/><rect x="128" y="72" width="16" height="64" rx="2" fill="#d99a3a"/><rect x="146" y="26" width="16" height="110" rx="2" fill="#b47a40"/>
                    <rect x="164" y="44" width="16" height="92" rx="2" fill="#9ca3af" stroke="#6b7280"/>
                </g>
                <ellipse cx="119" cy="72" rx="62" ry="7" fill="#5aa7ec" opacity=".9"/>
                <path d="M142 72c5 2 12 10 14 64" stroke="#3b8fe0" stroke-width="4" fill="none" stroke-linecap="round"/>
                <rect x="54" y="106" width="128" height="6" fill="#4b5563"/><rect x="54" y="54" width="128" height="6" fill="#4b5563"/>
                <text x="136" y="96" text-anchor="middle" font-size="10" font-weight="800" fill="#7c2d12">P</text>
            </svg>
        </div>
        <p><b>A crop grows only as far as its scarcest need allows.</b> Plenty of everything else cannot make up for the one thing that is short.</p>
        <p>The idea was first written down by the German scientist Carl Sprengel in 1828 and made famous by the German chemist Justus von Liebig in the 1840s. It is often drawn as a barrel.</p>
        <h4>The barrel</h4>
        <p>Picture a barrel made of planks of different heights. Water fills it only up to the shortest plank; the rest spills over. Each plank is something the crop needs: nitrogen, phosphorus, potassium, the other nutrients, sunlight, water and the right temperature. The water is the yield.</p>
        <h4>What it means on your farm</h4>
        <p>Find the shortest plank and raise it first. If phosphorus is short, more urea will not raise the harvest; it only costs money. Once phosphorus is fixed, another plank becomes the shortest, and that one is next.</p>
        <h4>How NPK Plus draws it</h4>
        <p>Each nutrient plank is how much of your yield goal that nutrient allows: what your soil gives on its own, plus what your fertilizer adds. A plank taller than the rim is a nutrient given more than the crop needs; it holds no more water. Sun, water and temperature are grey: NPK Plus cannot measure them, but in a drought or a long cloudy spell they can be the shortest plank of all.</p>
        <p class="np-fine">It is a simple picture. In a real field the nutrients work together, the shortest plank can change during the season, and the variety and the soil decide how much each fertilizer really gives.</p>
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
        <p class="np-sub mt-0">Anee checks your plan against the place, your soil, the water, the season and ENSO, then says what to keep, cut, add or split, and when. Your soil from the wizard goes with it.</p>
        <label class="np-label" for="npLoc">Where is the field?</label>
        <input type="text" id="npLoc" class="form-input w-full" maxlength="160" placeholder="Town and province">
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
        save: @json(route('npk.save')), season: @json(route('npk.season')), ph: @json(asset('data/ph-locations.json')), list: @json(route('npk.list')), one: (id) => @json(url('/app/npk-plus/one')) + '/' + id,
        analyze: @json(route('npk.analyze')), job: (id) => @json(url('/app/npk-plus/job')) + '/' + id,
    };
    const NUTRIENTS = ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'Si', 'Cl', 'Co'];
    const NPK = ['N', 'P2O5', 'K2O'];
    const NAMES = { N: 'Nitrogen', P2O5: 'Phosphorus', K2O: 'Potassium', Ca: 'Calcium', Mg: 'Magnesium', S: 'Sulfur', Zn: 'Zinc', B: 'Boron', Fe: 'Iron', Mn: 'Manganese', Cu: 'Copper', Mo: 'Molybdenum', Si: 'Silicon', Cl: 'Chloride', Co: 'Cobalt' };
    const TICK = '<svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    const CHEV = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
    const PLUS = '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>';
    const still = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const lab = (n) => n === 'P2O5' ? 'P₂O₅' : n === 'K2O' ? 'K₂O' : n;
    const fmt = (v, dp = 1) => (Math.round(v * 10 ** dp) / 10 ** dp).toLocaleString(undefined, { maximumFractionDigits: dp });
    const span = (lo, hi) => fmt(lo) === fmt(hi) ? 'about ' + fmt(lo) : fmt(lo) + ' to ' + fmt(hi);
    const num = (id) => { const v = Number($(id).value); return isFinite(v) && v > 0 ? v : 0; };
    const clamp = (v, lo, hi) => Math.min(hi, Math.max(lo, v));
    const list = (a) => a.length > 1 ? a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1] : (a[0] || '');

    // Every small choice: [what the sheet says, what the tag says, a hint].
    const AREA_U = { ha: ['Hectares', 'ha', '10,000 square meters each'], sqm: ['Square meters', 'm²', 'For a small plot or a backyard garden'] };
    const Y_U = { t: ['Tons per hectare', 't/ha', 'How a variety\'s potential is usually written', 1], cav: ['Cavans per hectare', 'cavans/ha', 'Cavans of 50 kg, as palay and corn are sold', 0.05], kg: ['Kilograms per hectare', 'kg/ha', 'For small harvests', 0.001] };
    const SEED_U = { kg: ['Kilograms', 'kg', 'Seed by weight, or tubers, cloves and sets'], g: ['Grams', 'g', 'Small seed, like most vegetables'], seeds: ['Seeds', 'seeds', 'Counted one by one'],
        seedlings: ['Seedlings', 'seedlings', 'Transplants from a seedbed or a tray'], hills: ['Hills', 'hills', 'Planting holes, with one or more seeds each'],
        cuttings: ['Cuttings', 'cuttings', 'Stem cuttings or cane setts'], plants: ['Plants or trees', 'plants', 'Suckers, grafted seedlings and trees'] };
    const SEED_DEF = { cuttings: ['cassava', 'sweetpotato', 'sugarcane'], g: ['carrot', 'pechay', 'lettuce'], seeds: ['squash', 'cucumber', 'watermelon', 'ampalaya', 'okra'],
        seedlings: ['cabbage', 'broccoli', 'tomato', 'eggplant', 'chili', 'bellpepper', 'onion'], plants: ['pineapple', 'banana', 'papaya', 'mango', 'coconut', 'calamansi', 'coffee', 'cacao', 'taro'] };
    const P_METHOD = { olsen: ['Olsen', 'Olsen', 'The usual method for neutral and alkaline soils'], bray: ['Bray', 'Bray', 'The usual method for acid soils'] };
    const K_UNIT = { ppm: ['Parts per million', 'ppm', 'Milligrams per kilogram of soil'], meq: ['Milliequivalents per 100 g', 'meq/100 g', 'As some laboratories report potassium'] };
    const FORMS = { granular: 'Granular', powder: 'Powder', liquid: 'Liquid' };
    const PH_WORDS = ['acidic', 'neutral', 'alkaline'];
    // What raised a risk, in words.
    const FACTOR = { sandy: 'sandy soil', clay: 'clay soil', acid: 'acid soil', strongAcid: 'very acid soil', alkaline: 'alkaline soil', strongAlk: 'strongly alkaline soil', sodic: 'sodic soil',
        saline: 'salty soil', acid_sulfate: 'acid sulfate soil', low_om: 'low organic matter', peat: 'peat soil', waterlogged: 'waterlogged soil', flooded: 'a flooded paddy',
        calcareous: 'limy (calcareous) soil', high_p: 'heavy phosphorus in the plan locks it up', high_k: 'heavy potassium in the plan crowds it out',
        liming: 'the lime in the plan locks it up', excess_zn: 'heavy zinc in the plan crowds it out' };
    // The usual germination the seeding rates in the guides assume.
    const GERM_USUAL = 85;

    let OPT = null, M = null, calcId = null, RES = null;
    const st = { crop: null, cropPicked: false, lines: [], areaUnit: 'ha', yUnit: 't', seedUnit: 'kg', seedUnitSet: false, pMethod: 'olsen', kUnit: 'ppm', form: 'granular',
        texture: 'unsure', conds: [], step: 0, prov: null, town: null, water: 'irrigated', season: null };
    const areaHa = () => st.areaUnit === 'sqm' ? num('npArea') / 10000 : num('npArea');
    const tagSay = (id, dict, k) => { $(id).querySelector('span').textContent = dict[k][1]; };

    /* ---- the top card folds, and remembers ---- */
    const HERO_KEY = 'anee.npkHeroOpen';
    const hero = (on) => { $('npHero').classList.toggle('is-open', on); $('npHeroHead').setAttribute('aria-expanded', String(on)); $('npHeroSays').textContent = on ? 'Tap to fold' : 'Tap to see how it works'; };
    try { hero(localStorage.getItem(HERO_KEY) === '1'); } catch (_) { hero(false); }
    $('npHeroHead').addEventListener('click', () => { const on = !$('npHero').classList.contains('is-open'); hero(on); try { localStorage.setItem(HERO_KEY, on ? '1' : '0'); } catch (_) {} });

    /* ---- the wizard: one step at a time ---- */
    const STEPS = 6;
    const go = (n) => {
        n = clamp(n, 0, STEPS - 1);
        const cls = n >= st.step ? 'go-next' : 'go-back';
        document.querySelectorAll('.np-wstep').forEach((s, i) => {
            s.hidden = i !== n;
            s.classList.remove('go-next', 'go-back');
            if (i === n && !still()) { void s.offsetWidth; s.classList.add(cls); }
        });
        document.querySelectorAll('.np-ws').forEach((b, i) => { b.classList.toggle('is-on', i === n); b.classList.toggle('is-done', i < n); b.setAttribute('aria-current', i === n ? 'step' : 'false'); });
        $('npWLine').style.setProperty('--p', (n / (STEPS - 1) * 100) + '%');
        $('npBack').disabled = n === 0;
        $('npNext').textContent = n === STEPS - 1 ? 'See results' : 'Next';
        $('npStepOf').textContent = 'Step ' + (n + 1) + ' of ' + STEPS;
        st.step = n;
        if ($('npWiz').getBoundingClientRect().top < 70) $('npWiz').scrollIntoView({ behavior: still() ? 'auto' : 'smooth', block: 'start' });
    };
    $('npBack').addEventListener('click', () => go(st.step - 1));
    $('npNext').addEventListener('click', () => {
        if (st.step === 0 && !st.cropPicked) return window.toast?.('Choose the crop, or No crop for the totals only.', 'error');
        if (st.step === STEPS - 1) {
            $('npOut').scrollIntoView({ behavior: still() ? 'auto' : 'smooth', block: 'start' });
            $('npOut').classList.remove('is-flash'); void $('npOut').offsetWidth; $('npOut').classList.add('is-flash');
            return;
        }
        go(st.step + 1);
    });
    $('npWSteps').addEventListener('click', (e) => { const b = e.target.closest('.np-ws'); if (b) go(Number(b.dataset.s)); });

    /* ---- one sheet for every small choice, one or several ---- */
    let pk = null;
    const pick = (title, hint, dict, cur, cb, multi = false, search = false) => {
        $('npPickTitle').textContent = title;
        $('npPickHint').textContent = hint || ''; $('npPickHint').hidden = !hint;
        $('npPickSearchBox').hidden = !search; $('npPickQ').value = '';
        pk = { dict, multi, sel: multi ? [...cur] : cur, cb };
        if (search && !matchMedia('(hover: none)').matches) setTimeout(() => $('npPickQ').focus(), 280);
        paintPick();
        $('npPickFoot').hidden = !multi;
        window.openSheet('npPickSheet');
    };
    const paintPick = () => {
        const q = ($('npPickQ').value || '').trim().toLowerCase();
        $('npPickRows').innerHTML = Object.entries(pk.dict).filter(([, v]) => !q || (v[0] + ' ' + (v[1] || '') + ' ' + (v[2] || '')).toLowerCase().includes(q)).map(([k, v]) => {
            const on = pk.multi ? pk.sel.includes(k) : k === pk.sel;
            const hint = v.length > 2 ? v[2] : v[1];
            return '<button type="button" class="dt-row' + (on ? ' is-on' : '') + '" data-k="' + esc(k) + '" aria-pressed="' + on + '"><span class="dt-row-body"><b>' + esc(v[0]) + '</b>' + (hint ? '<i>' + esc(hint) + '</i>' : '') + '</span>' + TICK + '</button>';
        }).join('');
    };
    $('npPickQ').addEventListener('input', () => pk && paintPick());
    $('npPickRows').addEventListener('click', (e) => {
        const b = e.target.closest('.dt-row');
        if (!b || !pk) return;
        const k = b.dataset.k;
        if (pk.multi) {
            // The three pH words exclude one another; the rest ride with any.
            if (pk.sel.includes(k)) pk.sel = pk.sel.filter((x) => x !== k);
            else pk.sel = (PH_WORDS.includes(k) ? pk.sel.filter((x) => !PH_WORDS.includes(x)) : pk.sel).concat(k);
            paintPick();
            pk.cb(pk.sel);
            return;
        }
        $('npPickRows').querySelectorAll('.dt-row').forEach((x) => x.classList.toggle('is-on', x === b));
        const cb = pk.cb; pk = null;
        setTimeout(() => window.closeSheet('npPickSheet'), 160);
        cb(k);
    });

    /* ---- step 1: the crop ---- */
    const cropName = (c) => String(c.label || '').replace(' — ', ', ');
    const cropShort = (c) => cropName(c).split(' (')[0];
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
        $('npCropList').innerHTML = '<div class="crop-group" data-crop-group><button type="button" class="crop-row' + (st.cropPicked && !st.crop ? ' is-on' : '') + '" data-crop="" data-find="no crop none totals only">'
            + '<span class="crop-row-e">🧮</span><span class="crop-row-t"><b>No crop, just the totals</b><small>See what the fertilizers add up to</small></span></button></div>'
            + Object.entries(groups).map(([g, l]) => '<div class="crop-group" data-crop-group><p class="crop-group-h">' + esc(g) + '</p>' + l.map((c) => row(c, g)).join('') + '</div>').join('');
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
        if (st.step === 0) setTimeout(() => go(1), 380);
    });
    const setCrop = (k) => {
        st.crop = k; st.cropPicked = true;
        const c = cropOf(k);
        $('npCropIcon').textContent = c ? c.icon : '🧮';
        $('npCropNow').textContent = c ? cropName(c) : 'No crop, just the totals';
        $('npCropNow').classList.remove('is-none');
        if (c && !st.seedUnitSet) { st.seedUnit = Object.keys(SEED_DEF).find((u) => SEED_DEF[u].includes(c.key)) || 'kg'; tagSay('npSeedUnit', SEED_U, st.seedUnit); }
        const f = [];
        if (c) {
            const ty = c.typicalYield, R = c.recommendedPerHa || {};
            if (ty) f.push('Average ' + span(ty.lo, ty.hi) + ' ' + ty.unit);
            const g = NPK.filter((n) => Array.isArray(R[n])).map((n) => lab(n) + ' ' + span(R[n][0], R[n][1]).replace('about ', ''));
            if (g.length) f.push('Guide rate ' + g.join(', ') + ' kg per ha');
            if (c.legume) f.push('Makes its own nitrogen');
            if (c.treesPerHa) f.push(c.treesPerHa + ' trees per ha in the guide');
        }
        $('npFacts').innerHTML = f.map((x) => '<span>' + esc(x) + '</span>').join('');
        if (st.prov || ($('npProvIn') && $('npProvIn').value.trim())) seasonSoon();
        hints(); calc();
    };

    /* ---- steps 2 and 3: the goal, the stand, the area ---- */
    const seedKind = (u) => (u === 'kg' || u === 'g') ? 'w' : 'n';
    const seedCheck = (c) => {
        const v = num('npSeed'), a = areaHa();
        const out = { perHa: null, kind: seedKind(st.seedUnit), usual: null, sf: 1, pop: 1, words: '', warn: false };
        if (!v || !a) return out;
        out.perHa = (st.seedUnit === 'g' ? v / 1000 : v) / a;
        // Seed is counted at its germination against the usual 85% the guides
        // assume: 40 kg at 60% sprouts like 28 kg of normal seed. Seedlings,
        // cuttings and plants are already growing.
        out.germ = ['kg', 'g', 'seeds', 'hills'].includes(st.seedUnit) && num('npGerm') ? clamp(num('npGerm'), 1, 100) : null;
        out.raw = out.perHa;
        if (out.germ) out.perHa = out.perHa * out.germ / GERM_USUAL;
        const sd = c && M.seeding[c.key];
        const u = sd && sd[out.kind];
        const unitWord = out.kind === 'w' ? 'kg' : ((sd && sd.countWord) || 'plants');
        if (!u) { out.words = sd ? 'The usual for ' + cropShort(c).toLowerCase() + ' is given ' + (out.kind === 'w' ? 'as a count of plants' : 'in kilograms') + ', so this cannot be compared.' : ''; return out; }
        out.usual = u;
        const tree = c.treesPerHa || sd.countWord === 'trees' || sd.countWord === 'palms';
        out.words = (out.germ && Math.abs(out.germ - GERM_USUAL) >= 1 ? 'At ' + Math.round(out.germ) + '% germination it plants like ' + fmt(out.perHa, out.perHa >= 100 ? 0 : 1) + ' ' + unitWord + ' of seed at the usual ' + GERM_USUAL + '%. ' : '')
            + 'The usual is ' + span(u[0], u[1]) + ' ' + unitWord + ' per hectare.';
        if (tree && out.kind === 'n') {
            const base = c.treesPerHa || (u[0] + u[1]) / 2;
            out.pop = clamp(out.perHa / base, 0.3, 2);
            if (Math.abs(out.pop - 1) > 0.1) out.words += ' The need is sized to your ' + fmt(out.perHa, 0) + ' per hectare.';
        } else if (out.perHa < u[0] * 0.75) {
            out.sf = Math.max(0.3, out.perHa / (u[0] * 0.75));
            out.warn = true;
            out.words += ' Yours is thinner, so NPK Plus counts for ' + Math.round(out.sf * 100) + '% of the goal.';
        } else if (out.perHa > u[1] * 1.5) {
            out.warn = true;
            out.words += ' Yours is much more: crowded plants need no more fertilizer and give no more harvest.';
        }
        return out;
    };
    const goalOf = (c) => {
        const f = Y_U[st.yUnit][3];
        const P = num('npPotential') * f, T = num('npTarget') * f, ty = c && c.typicalYield;
        let Y = null, why = '', warn = '';
        if (T) {
            Y = P ? Math.min(T, P) : T;
            why = P && T > P ? 'your goal, held to the variety\'s potential' : 'your goal';
            if (P && T > 0.8 * P && T <= P) warn = 'Above 80% of the potential, each extra ton takes more fertilizer and more luck with the weather.';
        } else if (P) { Y = 0.8 * P; why = '80% of the variety\'s potential, the most a well run field usually reaches'; }
        else if (ty) { Y = ty.hi; why = 'the Philippine average, since no goal or potential was given'; }
        const sd = seedCheck(c);
        return { Y, Yeff: Y ? Y * sd.sf : null, why, warn, P, T, sd };
    };
    const hints = () => {
        if (!M) return;
        const c = cropOf(st.crop), g = goalOf(c), unit = Y_U[st.yUnit][1], f = Y_U[st.yUnit][3];
        $('npGoal').innerHTML = !c ? 'Pick the crop first; the goal is sized to it.'
            : g.Y ? 'NPK Plus counts for <b>' + fmt(g.Y / f) + ' ' + esc(unit) + '</b>' + (st.yUnit !== 't' ? ' (' + fmt(g.Y) + ' t/ha)' : '') + ': ' + esc(g.why) + '.'
                + (g.sd.sf < 1 ? ' Your stand is thin, so it counts for ' + fmt(g.Yeff / f) + ' ' + esc(unit) + '.' : '') + (g.warn ? '<br><span class="text-xs">' + esc(g.warn) + '</span>' : '')
            : 'No yield numbers for this crop: give your goal, or the guide rate is used as it is.';
        const s = num('npSeed'), a = areaHa(), sd = c && M.seeding[c.key];
        $('npSeedHint').textContent = s && a ? 'For the whole area: ' + fmt(g.sd.raw * (st.seedUnit === 'g' ? 1000 : 1), g.sd.raw >= 100 ? 0 : 1) + ' ' + SEED_U[st.seedUnit][1] + ' per hectare. ' + g.sd.words
            : 'How much you plant on the whole area.' + (sd && sd.note ? ' ' + sd.note : '');
        $('npSeedHint').classList.toggle('is-warn', !!g.sd.warn);
    };
    $('npYUnit').addEventListener('click', () => pick('Yields in', 'For the potential and your goal.', Y_U, st.yUnit, (k) => { st.yUnit = k; tagSay('npYUnit', Y_U, k); hints(); calc(); }));
    $('npSeedUnit').addEventListener('click', () => pick('What you plant', 'For the whole area.', SEED_U, st.seedUnit, (k) => { st.seedUnit = k; st.seedUnitSet = true; tagSay('npSeedUnit', SEED_U, k); hints(); calc(); }));
    $('npAreaUnit').addEventListener('click', () => pick('The area in', '', AREA_U, st.areaUnit, (k) => { st.areaUnit = k; tagSay('npAreaUnit', AREA_U, k); hints(); calc(); }));
    ['npArea', 'npPotential', 'npTarget', 'npSeed', 'npGerm', 'npVariety', 'npPh'].forEach((id) => $(id).addEventListener('input', () => { hints(); calc(); }));

    /* ---- step 4: the place, the planting date, the water, and the season ---- */
    const WATER_U = { irrigated: ['Irrigated', 'Irrigated', 'NIA, a pump or a canal, all season'], partial: ['Partly irrigated', 'Partly irrigated', 'Water some of the time, rain the rest'], rainfed: ['Rainfed only', 'Rainfed only', 'Only the rain'] };
    let PHL = null;
    const loadPh = async () => PHL ??= await (await fetch(U.ph)).json();
    const townName = (t) => String(t || '').replace(/\s*\(Capital\)\s*$/i, '');
    $('npProvBtn')?.addEventListener('click', async () => {
        if (!ready()) return;
        try { await loadPh(); } catch (_) { return window.toast?.('The list of provinces could not load.', 'error'); }
        pick('Choose the province', '', Object.fromEntries(Object.keys(PHL).sort((a, b) => a.localeCompare(b)).map((p) => [p, [p, p, PHL[p].length + ' towns and cities']])), st.prov, (k) => {
            if (k !== st.prov) { st.town = null; tagWide('npTownBtn', 'Choose the town', true); }
            st.prov = k; tagWide('npProvBtn', k, false);
            $('npTownBtn').disabled = false;
            if (!st.town) setTimeout(() => $('npTownBtn').click(), 260);
            seasonSoon();
        }, false, true);
    });
    $('npTownBtn')?.addEventListener('click', async () => {
        if (!st.prov) return;
        try { await loadPh(); } catch (_) { return; }
        pick('Choose the town or city', st.prov, Object.fromEntries((PHL[st.prov] || []).map((t) => [t, [townName(t), townName(t), /\(Capital\)/i.test(t) ? 'The capital' : '']])), st.town, (k) => {
            st.town = k; tagWide('npTownBtn', townName(k), false); seasonSoon();
        }, false, true);
    });
    const tagWide = (id, text, none) => { const b = $(id); if (!b) return; b.querySelector('span').textContent = text; b.classList.toggle('is-none', !!none); };
    $('npWaterBtn').addEventListener('click', () => pick('Where the water comes from', '', WATER_U, st.water, (k) => { st.water = k; tagWide('npWaterBtn', WATER_U[k][0], false); calc(); }));
    ['npTownIn', 'npProvIn'].forEach((id) => $(id)?.addEventListener('input', () => seasonSoon(900)));
    $('npPlanted').addEventListener('change', () => seasonSoon());
    const placeWords = () => $('npTownIn') ? [$('npTownIn').value.trim(), $('npProvIn').value.trim()] : [townName(st.town), st.prov || ''];
    const seasonDays = (c) => c ? (c.perennial ? 365 : (Number(c.maturity) || 110)) : 110;
    let seasonT = 0, seasonAsk = 0;
    const seasonSoon = (ms = 120) => { clearTimeout(seasonT); seasonT = setTimeout(loadSeason, ms); };
    const loadSeason = async () => {
        const [town, prov] = placeWords();
        if (!prov || (!town && !$('npTownIn'))) { st.season = null; paintSeason(); calc(); return; }
        const c = cropOf(st.crop), ask = ++seasonAsk;
        $('npSeason').innerHTML = 'Reading ten years of weather at ' + esc(town ? town + ', ' + prov : prov) + '…';
        try {
            const r = await window.api(U.season + '?' + new URLSearchParams({ town, province: prov, from: $('npPlanted').value || '', days: seasonDays(c) }).toString());
            if (ask !== seasonAsk) return;
            st.season = r.data;
        } catch (err) { if (ask !== seasonAsk) return; st.season = null; $('npSeason').textContent = err.message || 'The season could not be read.'; calc(); return; }
        paintSeason(); calc();
    };
    // How ENSO tilts this season (NpkModel::ENSO_TILT): full in October to May, half in the southwest monsoon, half again when the season is far off.
    const ensoTilt = (S) => {
        const e = S && S.enso;
        if (!e || e.phase === 'neutral' || !M.ensoTilt[e.phase]) return { rain: 1, sun: 1, temp: 0, e };
        const lvl = { weak: 0, moderate: 1, strong: 2, 'very strong': 3 }[e.strength] ?? 1, T = M.ensoTilt[e.phase];
        const months = (S.window && S.window.months) || [];
        const dry = months.length ? months.filter((m) => m >= 10 || m <= 5).length / months.length : 1;
        const w = (0.5 + 0.5 * dry) * ((S.monthsAhead || 0) > 9 ? 0.5 : 1);
        return { rain: 1 + T.rain[lvl] * w, sun: 1 + T.sun[lvl] * w, temp: T.temp[lvl] * w, e };
    };
    const paintSeason = () => {
        const S = st.season;
        if (!S) { $('npSeason').textContent = 'Pick the place and the planting date to read the season.'; return; }
        if (!S.avg) { $('npSeason').textContent = 'No weather history could be read for ' + S.place.label + '.'; return; }
        const t = ensoTilt(S), a = S.avg, from = new Date(S.window.from + 'T00:00:00'), to = new Date(from.getTime() + (S.window.days - 1) * 864e5);
        const md = (d) => d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        $('npSeason').innerHTML = '<b>' + esc(md(from)) + ' to ' + esc(md(to)) + '</b> (' + S.window.days + ' days) at ' + esc(S.place.label) + '. In the same weeks of the last ' + S.years.length + ' years: about <b>' + Math.round(a.rain) + ' mm</b> of rain, <b>' + a.rad + ' MJ</b> of sun a day, an average of <b>' + a.tmean + '°C</b>, and ' + Math.round(a.hot32) + ' days at 32°C or more.'
            + (t.e ? '<br><span class="np-enso' + (t.e.phase === 'la_nina' ? ' is-wet' : t.e.phase === 'neutral' ? ' is-flat' : '') + '">' + esc(t.e.label) + (t.e.phase !== 'neutral' ? ': rain about ' + Math.round(Math.abs(1 - t.rain) * 100) + '% ' + (t.rain < 1 ? 'lower' : 'higher') + ' this time' : '') + '</span>' : '');
    };
    // The season's three planks. Each is what the sun, the water or the heat
    // lets the variety carry, in tons (its potential cut by that one), set
    // against the goal: the same season at a lower goal is a taller plank,
    // never a smaller harvest (the owner's report, 2026-10-08).
    const potentialOf = (c, g) => {
        if (g.P) return { t: g.P, given: true };
        const Yg = M.guideYield[c.key] || (c.typicalYield ? c.typicalYield.hi * 1.25 : null);
        const t = Math.max(Yg ? Yg * (M.potentialX || 1.6) : 0, g.Yeff ? g.Yeff / 0.8 : 0);
        return t ? { t, given: false } : null;
    };
    const seasonPlanks = (c, g) => {
        const S = st.season;
        if (!c || !S || !S.avg) return null;
        const K = Object.assign({}, M.climate.default, M.climate[c.key] || {}), t = ensoTilt(S), a = S.avg, days = S.window.days;
        const rain = a.rain * t.rain, rad = a.rad * t.sun, tmean = a.tmean + t.temp, et0 = a.et0 * (t.rain < 1 ? 1 + (1 - t.rain) * 0.15 : 1);
        // Water: what the crop drinks against what it gets (FAO's yield response, ky).
        const need = et0 * K.kc + (K.extraMm || 0), eff = rain * 0.75;
        const supply = st.water === 'irrigated' ? need : st.water === 'partial' ? eff + 0.5 * Math.max(0, need - eff) : eff;
        const wr = Math.min(1, supply / Math.max(1, need)), water = clamp(1 - K.ky * (1 - wr), 0.1, 1);
        const driest = S.years.reduce((m, y) => Math.min(m, y.rain), Infinity) * t.rain * 0.75;
        const wrDry = Math.min(1, (st.water === 'irrigated' ? need : st.water === 'partial' ? driest + 0.5 * Math.max(0, need - driest) : driest) / Math.max(1, need));
        // Sun: a full potential wants about K.rad MJ a day; less sun lowers the yield the season can carry.
        const fs = clamp(rad / K.rad, 0.2, 1.1), fw = water;
        // Temperature: the mean against the crop's best range, and the days too hot for flowers and fruit.
        const dev = Math.max(0, K.opt[0] - tmean, tmean - K.opt[1]);
        const hotN = (K.hot >= 35 ? a.hot35 : K.hot >= 32 ? a.hot32 : a.hot30) * (t.e && t.e.phase === 'el_nino' ? 1.3 : t.e && t.e.phase === 'la_nina' ? 0.85 : 1);
        const ft = clamp((1 - 0.07 * dev) * (1 - 0.8 * Math.min(1, hotN / days)), 0.25, 1);
        // In tons: the potential each one allows; as planks: that against the goal.
        const pot = potentialOf(c, g), cap = pot ? { sun: pot.t * fs, water: pot.t * fw, temp: pot.t * ft } : null;
        const of = (k, f0) => (cap && g.Yeff ? cap[k] / g.Yeff : f0);
        return { sun: of('sun', Math.min(1, fs)), water: of('water', fw), temp: of('temp', ft), cap, pot, f: { sun: fs, water: fw, temp: ft },
            rain, rad, tmean, need, supply, wr, wrDry: clamp(1 - K.ky * (1 - wrDry), 0.1, 1), hotN, K, t };
    };

    /* ---- step 5: the soil ---- */
    const soilTag = () => {
        const t = $('npTexture'); t.querySelector('span').textContent = M.textures[st.texture][0]; t.classList.toggle('is-none', st.texture === 'unsure');
        const c = $('npConds'); c.querySelector('span').textContent = st.conds.length ? st.conds.map((k) => (M.conditions[k] || [k])[0]).join(', ') : 'None picked'; c.classList.toggle('is-none', !st.conds.length);
    };
    $('npTexture').addEventListener('click', () => ready() && pick('Soil type', 'Rub a moist pinch between your fingers.', M.textures, st.texture, (k) => { st.texture = k; soilTag(); calc(); }));
    $('npConds').addEventListener('click', () => ready() && pick('What else is true of your soil', 'Pick all that fit. Leave it empty if you are not sure.', M.conditions, st.conds, (sel) => { st.conds = sel; soilTag(); calc(); }, true));
    const SOIL = [['om', 'Organic matter %'], ['n', 'Total N %'], ['p', 'P ppm'], ['k', 'K'], ['zn', 'Zn ppm'], ['b', 'B ppm'], ['s', 'S ppm']];
    $('npSoilGrid').innerHTML = SOIL.map(([k, l]) => '<label>' + esc(l) + '<input type="number" class="form-input" step="any" min="0" inputmode="decimal" data-s="' + k + '"></label>').join('');
    $('npSoilOn').addEventListener('change', () => { $('npSoil').classList.toggle('is-on', $('npSoilOn').checked); calc(); });
    $('npSoil').addEventListener('input', () => calc());
    $('npPMethod').addEventListener('click', () => pick('How phosphorus was measured', 'It is printed on the soil test result.', P_METHOD, st.pMethod, (k) => { st.pMethod = k; tagSay('npPMethod', P_METHOD, k); calc(); }));
    $('npKUnit').addEventListener('click', () => pick('Potassium is given in', 'It is printed beside the number.', K_UNIT, st.kUnit, (k) => { st.kUnit = k; tagSay('npKUnit', K_UNIT, k); calc(); }));
    const phVal = () => { const v = Number($('npPh').value); return $('npPh').value !== '' && v >= 2 && v <= 12 ? v : null; };
    const soil = () => {
        const s = {};
        if ($('npSoilOn').checked) $('npSoilGrid').querySelectorAll('input').forEach((i) => { if (i.value !== '') s[i.dataset.s] = Number(i.value); });
        const ph = phVal(); if (ph != null) s.ph = ph;
        if (!Object.keys(s).length) return null;
        s.pMethod = st.pMethod; s.kUnit = st.kUnit;
        return s;
    };
    // Low, medium or high, from the usual soil test bands.
    const soilClass = (s) => {
        if (!s) return {};
        const out = {};
        if (s.p != null) out.P2O5 = s.pMethod === 'bray' ? (s.p < 15 ? 'low' : s.p <= 30 ? 'medium' : 'high') : (s.p < 10 ? 'low' : s.p <= 20 ? 'medium' : 'high');
        if (s.k != null) { const meq = s.kUnit === 'meq' ? s.k : s.k / 391; out.K2O = meq < 0.2 ? 'low' : meq <= 0.4 ? 'medium' : 'high'; }
        if (s.om != null) out.N = s.om < 2 ? 'low' : s.om <= 4 ? 'medium' : 'high';
        else if (s.n != null) out.N = s.n < 0.1 ? 'low' : s.n <= 0.2 ? 'medium' : 'high';
        ['Zn', 'B', 'S'].forEach((el) => {
            const v = s[el.toLowerCase()], crit = (M.elements[el] || {}).critical;
            if (v != null && crit) out[el] = v < crit ? 'low' : 'ok';
        });
        return out;
    };
    // What is true of this field, as the model's factors.
    const factors = (c) => {
        const f = new Set();
        if (st.texture === 'sandy' || st.texture === 'clay') f.add(st.texture);
        const ph = phVal();
        if (ph != null) { if (ph < 5) f.add('strongAcid'); else if (ph < 5.5) f.add('acid'); else if (ph > 8) f.add('strongAlk'); else if (ph > 7.3) f.add('alkaline'); }
        else { if (st.conds.includes('acidic')) f.add('acid'); if (st.conds.includes('alkaline')) f.add('alkaline'); }
        ['sodic', 'saline', 'acid_sulfate', 'low_om', 'peat', 'waterlogged'].forEach((k) => { if (st.conds.includes(k)) f.add(k); });
        // Strongly alkaline and NOT sodic reads as a limy soil, rich in calcium.
        // A sodic soil's high pH is sodium, which crowds the calcium off the clay.
        if (f.has('strongAlk') && !f.has('sodic')) f.add('calcareous');
        if (c && (c.key === 'rice' || c.key === 'rice_dsr_wet')) { f.add('flooded'); f.delete('waterlogged'); }
        return f;
    };

    /* ---- step 5: the fertilizers ---- */
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

    // Which product.
    let cat = 'all';
    const paintProducts = () => {
        const q = ($('npProdQ').value || '').trim().toLowerCase();
        $('npProdQX').classList.toggle('hidden', !q);
        // "16-16-8+9s" finds "16-16-8-9S": a plus between the grade and the sulfur reads as a dash.
        const qn = q.replace(/\s*\+\s*/g, '-');
        const hit = (p) => { if (cat !== 'all' && p.category !== cat) return false; if (!q) return true; const hay = (p.name + ' ' + (p.local || '') + ' ' + grade(p) + ' ' + Object.keys(p.pct).map((k) => NAMES[k] || k).join(' ')).toLowerCase(); return hay.includes(q) || hay.includes(qn); };
        const row = (p) => {
            const inPlan = st.lines.some((l) => l.id === p.id);
            return '<button type="button" class="np-prow' + (inPlan ? ' is-in' : '') + '" data-id="' + p.id + '"><span class="np-prow-g">' + esc(badge(p)) + '</span><span class="np-prow-t"><b>' + esc(p.name) + '</b><small>' + esc(comp(p)) + '</small></span>'
                + (inPlan ? '<em>In your plan</em>' : PLUS) + '</button>';
        };
        const l = OPT.products.filter(hit);
        if (!l.length) { $('npProdList').innerHTML = '<p class="crop-none">Nothing matches. Add it from its label below.</p>'; return; }
        $('npProdList').innerHTML = cat === 'all' && !q
            ? Object.entries(OPT.categories).map(([k, v]) => { const rows = l.filter((p) => p.category === k); return rows.length ? '<p class="crop-group-h">' + esc(v) + '</p>' + rows.map(row).join('') : ''; }).join('')
            : l.map(row).join('');
    };
    $('npAddBtn').addEventListener('click', () => { if (!ready()) return; paintProducts(); window.openSheet('npProdSheet'); });
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
        // Already in the plan: change its amount rather than add it twice.
        if (p) openQty(p, st.lines.findIndex((l) => l.id === p.id), true);
    });

    // How much.
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
        if (qty.unit === 'dose' && p.estimate) Object.entries(p.estimate).forEach(([n, v]) => give.push([n, v * amount * a]));
        else { const kg = kgOf(p, amount, qty.unit); Object.entries(p.pct).forEach(([n, v]) => give.push([n, kg * v / 100])); }
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
        const p = qty.p, amount = Number($('npQtyIn').value) || 0, kg = kgOf(p, amount, qty.unit), next = b.dataset.u;
        if (kg > 0 && !['dose', 'L'].includes(next) && !['dose', 'L'].includes(qty.unit)) $('npQtyIn').value = Math.round((next === 'bag' ? kg / (p.bagKg || 50) : next === 'g' ? kg * 1000 : kg) * 100) / 100;
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
    $('npCGrid').innerHTML = ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'Si'].map((n) => '<label>' + lab(n) + ' %<input type="number" class="form-input" min="0" max="100" step="any" inputmode="decimal" data-n="' + n + '"></label>').join('');
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

    /* ---- the need model (App\Support\NpkModel says where each number comes from) ---- */
    const needModel = (c, g, f, cls, perHa = {}) => {
        const out = { need: {}, raw: {}, how: {}, share: {}, adj: {}, scale: {}, whys: [], Yg: null, micro: {}, base: null };
        // How the soil sizes N, P2O5 and K2O, and the soil's own share.
        const base = Object.assign({}, M.soilShare, (c && M.soilShareCrop[c.key]) || {});
        const m = { N: 1, P2O5: 1, K2O: 1 }, sh = Object.assign({}, base), same = {};
        f.forEach((k) => {
            const a = M.adjust[k];
            if (!a) return;
            let used = false, skipped = false;
            NPK.forEach((n) => {
                // A factor that does not hold beside another (a sodic soil's high pH keeps phosphorus soluble).
                if (a[n] && a.unless && a.unless[n] && f.has(a.unless[n])) { skipped = true; return; }
                if (a[n]) {
                    if (n === 'P2O5' && a[n] > 1) out.pLock = true;
                    // Two conditions that lose it the same way (a sodic soil is an
                    // alkaline one: one loss of urea to the air) count once, the larger.
                    const s = a.same && a.same[n];
                    if (s) same[n + ':' + s] = Math.max(same[n + ':' + s] || 1, a[n]); else m[n] *= a[n];
                    used = true;
                }
                if (a.share && a.share[n]) sh[n] *= a.share[n];
            });
            if (used) out.whys.push(skipped && a.whyUnless ? a.whyUnless : a.why);
        });
        Object.entries(same).forEach(([key, v]) => { m[key.split(':')[0]] *= v; });
        NPK.forEach((n) => {
            const b = cls[n] === 'low' ? 'testLow' : cls[n] === 'high' ? 'testHigh' : null;
            if (!b) return;
            const a = M.adjust[b]; m[n] *= a[n]; sh[n] *= a.share[n];
            out.whys.push(a.why + ' in ' + NAMES[n].toLowerCase());
        });
        NPK.forEach((n) => { out.adj[n] = clamp(m[n], 0.5, 1.8); out.share[n] = clamp(sh[n], 0.3, 0.97); });
        out.base = base;
        if (!c) return out;
        const R = c.recommendedPerHa || {}, Up = c.uptakePerTon || {}, RE = Object.assign({}, M.recovery, c.efficiency || {});
        const Yg = M.guideYield[c.key] || (c.typicalYield ? c.typicalYield.hi * 1.25 : null);
        out.Yg = Yg;
        const fromTypical = !g.T && !g.P;
        NPK.forEach((n) => {
            let v = null;
            if (c.legume && n === 'N') { out.how.N = 'legume'; out.raw.N = Array.isArray(R.N) ? R.N[0] : 20; out.need.N = out.raw.N; out.share.N = 0.95; return; }
            if (Array.isArray(R[n])) {
                // Nitrogen follows the goal ton for ton; phosphorus and potassium
                // only part way (NpkModel::PK_BASE): part of their guide rate keeps
                // the soil's supply up whatever the harvest.
                const ratio = g.Yeff && Yg ? g.Yeff / Yg : 1, pk = M.pkBase ?? 0.5;
                out.scale[n] = n === 'N' ? ratio : pk + (1 - pk) * ratio;
                v = (R[n][0] + R[n][1]) / 2 * out.scale[n] * (fromTypical ? g.sd.pop : 1);
                out.how[n] = 'guide';
            } else if (Up[n] > 0 && g.Yeff) {
                v = c.removalOnly ? g.Yeff * Up[n] : g.Yeff * Up[n] * (1 - base[n]) / RE[n];
                out.how[n] = c.removalOnly ? 'removal' : 'uptake';
            }
            if (v != null) { out.raw[n] = v; out.need[n] = v * out.adj[n]; }
        });
        // Lockouts the plan itself makes: too much phosphorus ties up zinc and
        // iron, too much potassium crowds out magnesium and calcium, lime ties
        // up zinc, boron, manganese, copper and iron, too much zinc crowds out copper.
        const pf = new Set();
        if ((perHa.P2O5 || 0) > (out.need.P2O5 ? out.need.P2O5 * 1.5 : 100)) pf.add('high_p');
        if ((perHa.K2O || 0) > (out.need.K2O ? out.need.K2O * 1.5 : 150)) pf.add('high_k');
        if ((perHa.Ca || 0) >= 150) pf.add('liming');
        if ((perHa.Zn || 0) > 10) pf.add('excess_zn');
        out.lockouts = [...pf];
        const ff = new Set([...f, ...pf]);
        // Secondary, micro and beneficial elements: from a bag only where a shortfall is likely.
        const ys = c.typicalYield && g.Yeff ? clamp(g.Yeff / c.typicalYield.hi, 0.8, 1.4) : 1;
        Object.entries(M.elements).forEach(([el, e]) => {
            const why = [], down = [];
            let score = 0;
            Object.entries(e.weights || {}).forEach(([k, w]) => {
                if (!ff.has(k)) return;
                score += w;
                if (w > 0) why.push(FACTOR[k] || k); else if (e.down && e.down[k] && !down.includes(e.down[k])) down.push(e.down[k]);
            });
            const sens = (e.sensitive || []).includes(c.key);
            if (sens) { score += e.sensitiveWeight || 1; why.push(cropShort(c).split(',')[0].toLowerCase() + ' needs more of it'); }
            const L = M.levels;
            let level = score >= L.likely.at ? 'likely' : score >= L.watch.at ? 'watch' : 'none';
            if (e.beneficial && !sens) level = 'notneeded';
            if (cls[el] === 'low') { level = 'test'; why.unshift('your soil test reads low'); }
            else if (cls[el] === 'ok') level = 'testok';
            const lv = L[level] || { dose: 0, share: 1 };
            out.micro[el] = { level, why, down, need: e.base * lv.dose * ys, share: lv.share };
        });
        return out;
    };

    /* ---- the arithmetic ---- */
    const amt = (kg) => kg >= 1 ? fmt(kg, 1) + ' kg' : kg >= 0.001 ? fmt(kg * 1000, 0) + ' g' : fmt(kg * 1e6, 0) + ' mg';
    const calc = () => {
        if (!OPT) return;
        const area = areaHa();
        const tot = Object.fromEntries(NUTRIENTS.map((n) => [n, 0]));
        let estimated = false;
        const out = st.lines.map((l) => {
            const p = prodOf(l.id);
            if (!p) return null;
            const kg = kgOf(p, l.amount, l.unit);
            Object.entries(p.pct).forEach(([n, v]) => { tot[n] = (tot[n] || 0) + kg * v / 100; });
            if (p.estimate && l.unit === 'dose') { estimated = true; Object.entries(p.estimate).forEach(([n, v]) => { tot[n] = (tot[n] || 0) + v * l.amount * Math.max(area, 0); }); }
            return { id: p.id, name: p.name, grade: grade(p), amount: l.amount, unit: l.unit, kg: Math.round(kg * 100) / 100, pct: p.pct, estimate: p.estimate };
        }).filter(Boolean);
        const perHa = Object.fromEntries(Object.keys(tot).map((n) => [n, area > 0 ? tot[n] / area : 0]));
        $('npN').textContent = fmt(perHa.N, 0); $('npP').textContent = fmt(perHa.P2O5, 0); $('npK').textContent = fmt(perHa.K2O, 0);
        const npk = fmt(perHa.N, 0) + '-' + fmt(perHa.P2O5, 0) + '-' + fmt(perHa.K2O, 0);

        const c = cropOf(st.crop), g = goalOf(c), f = factors(c), cls = soilClass(soil()), md = needModel(c, g, f, cls, perHa);
        const yu = Y_U[st.yUnit], goalSaid = g.Yeff ? fmt(g.Yeff / yu[3]) + ' ' + yu[1] : '';
        $('npOutSub').textContent = (out.length ? 'For ' + fmt(area, 3) + ' ha' : 'Add a fertilizer to see the totals') + (c && goalSaid ? ', against the need for ' + goalSaid + ' of ' + cropShort(c).toLowerCase() : '') + '.' + (estimated ? ' Includes biofertilizer estimates.' : '');

        // NPK: the plan against the need, as the bag reads them.
        const states = {};
        const rows = NPK.map((n) => {
            const need = md.need[n], have = perHa[n];
            if (need == null) return '<div class="np-nrow"><span>' + lab(n) + '</span><span class="np-nbar"><span class="have" style="width:' + (have > 0 ? 100 : 0) + '%"></span></span><em>No need on file</em><small>Plan ' + fmt(have, 0) + ' kg per ha</small></div>';
            const r = need > 0 ? have / need : (have > 0 ? 9 : 1);
            const state = r < 0.9 ? 'short' : r > 1.3 ? 'over' : 'right';
            states[n] = { need: Math.round(need), plan: Math.round(have), state, how: md.how[n] };
            const scale = Math.max(need * 1.45, have * 1.05, 1);
            return '<div class="np-nrow is-' + state + '"><span>' + lab(n) + '</span><span class="np-nbar"><span class="have" style="width:' + Math.min(100, have / scale * 100) + '%"></span><span class="need" style="left:' + (need / scale * 100) + '%" title="Need"></span></span>'
                + '<em>' + (state === 'short' ? 'Short' : state === 'over' ? 'Too much' : 'Enough') + '</em><small>Plan ' + fmt(have, 0) + ' of a ' + fmt(need, 0) + ' kg need per ha' + (area > 0 && Math.abs(area - 1) > 0.001 ? ' · ' + fmt(need * area, 0) + ' kg for your ' + fmt(area, 2) + ' ha' : '') + '</small></div>';
        }).join('');
        if (c) {
            const R = c.recommendedPerHa || {};
            const guide = NPK.filter((n) => Array.isArray(R[n])).map((n) => lab(n) + ' ' + span(R[n][0], R[n][1]).replace('about ', ''));
            $('npNeed').innerHTML = '<h4 class="np-label">What ' + esc(cropShort(c).toLowerCase()) + ' needs from fertilizer' + (goalSaid ? ' for ' + esc(goalSaid) : '') + ' (the dark mark)</h4><div class="np-nrows">' + rows + '</div>'
                + (guide.length ? '<p class="np-fine mt-2">The official guide without a soil test: ' + esc(guide.join(', ')) + ' kg per ha. NPK Plus sizes it to your goal and your soil.</p>' : '')
                + method(c, g, md);
        } else {
            $('npNeed').innerHTML = '<div class="np-say">' + (st.cropPicked ? 'No crop picked, so these are the totals only.' : 'Pick the crop in step 1 to see what it needs for your goal.') + '</div>';
        }
        const short = NPK.filter((n) => states[n] && states[n].state === 'short'), over = NPK.filter((n) => states[n] && states[n].state === 'over');
        // Where the soil locks phosphorus up, a band beats more of it.
        const pLock = c && md.need.P2O5 && md.pLock;
        $('npSay').innerHTML = c && out.length && Object.keys(states).length
            ? '<div class="np-say">' + (short.length ? 'Short of ' + esc(list(short.map((n) => NAMES[n].toLowerCase()))) + ' for your goal. ' : '') + (over.length ? 'More ' + esc(list(over.map((n) => NAMES[n].toLowerCase()))) + ' than the crop needs: it costs money and can harm the crop. ' : '')
                + (!short.length && !over.length ? 'The plan meets the need for nitrogen, phosphorus and potassium. ' : '') + 'The Barrel tab shows what that means for the harvest.</div>'
                + (pLock ? '<div class="np-say"><b>On this soil, band the phosphorus.</b> Put it in a band beside and a little below the seed, or in the planting hole, instead of spreading it over the field: less of it touches the soil that locks it up. The need above counts the lock up as if it were spread, so a banded plan can do with less.</div>' : '')
            : '';

        const micro = micros(c, md, tot, perHa, area, out.length);
        const reach = barrel(c, md, states, perHa, out.length, g);

        const seedV = num('npSeed');
        RES = { perHa: Object.fromEntries(Object.entries(perHa).filter(([, v]) => v > 0).map(([k, v]) => [k, Math.round(v * 1000) / 1000])), total: Object.fromEntries(Object.entries(tot).filter(([, v]) => v > 0).map(([k, v]) => [k, Math.round(v * 1000) / 1000])),
            npkPerHa: npk, needs: states, micros: micro, reach, lines: out, areaHa: area, soilClass: cls,
            goal: g.Y ? { tPerHa: Math.round(g.Y * 100) / 100, counted: Math.round(g.Yeff * 100) / 100, why: g.why } : null,
            setup: { variety: $('npVariety').value.trim(), areaUnit: st.areaUnit, areaValue: num('npArea'), yUnit: st.yUnit,
                potential: num('npPotential') ? { value: num('npPotential'), unit: yu[1], key: st.yUnit, tPerHa: Math.round(num('npPotential') * yu[3] * 100) / 100 } : null,
                target: num('npTarget') ? { value: num('npTarget'), unit: yu[1], tPerHa: Math.round(num('npTarget') * yu[3] * 100) / 100 } : null,
                seed: seedV ? { amount: seedV, unit: SEED_U[st.seedUnit][1], key: st.seedUnit, perHa: g.sd.raw ? Math.round(g.sd.raw * 10) / 10 : null, germination: g.sd.germ || null } : null,
                texture: st.texture, conditions: st.conds.slice(), ph: phVal(), prov: st.prov, town: st.town, water: st.water, planted: $('npPlanted').value || null },
            season: (() => { const SP = seasonPlanks(c, g); return SP ? { place: st.season.place, window: st.season.window, enso: SP.t.e ? SP.t.e.label : null, rainMm: Math.round(SP.rain), sunMJ: SP.rad, tmean: SP.tmean,
                needMm: Math.round(SP.need), water: st.water, planks: { sun: Math.round(Math.min(1, SP.sun) * 100), water: Math.round(Math.min(1, SP.water) * 100), temp: Math.round(Math.min(1, SP.temp) * 100) },
                potential: SP.pot ? { tPerHa: Math.round(SP.pot.t * 10) / 10, given: SP.pot.given } : null,
                carriesTPerHa: SP.cap ? { sun: Math.round(SP.cap.sun * 10) / 10, water: Math.round(SP.cap.water * 10) / 10, temp: Math.round(SP.cap.temp * 10) / 10 } : null } : null; })() };
        $('npAnee').disabled = !out.length;
        $('npSave').disabled = !out.length;
    };

    // How the need was counted, in plain words.
    const method = (c, g, md) => {
        const li = [];
        li.push('<b>Yield goal:</b> ' + (g.Y ? fmt(g.Y) + ' t/ha, ' + esc(g.why) + '.' : 'none, so the guide rate is used as it is.') + (g.sd.sf < 1 ? ' A thin stand brings it to ' + fmt(g.Yeff) + ' t/ha.' : ''));
        const Up = c.uptakePerTon || {};
        if (g.Yeff && NPK.some((n) => Up[n] > 0)) li.push('<b>What the crop takes up:</b> at ' + fmt(g.Yeff) + ' t/ha, about ' + NPK.filter((n) => Up[n] > 0).map((n) => fmt(Up[n] * g.Yeff, 0) + ' kg ' + lab(n)).join(', ') + (c.removalOnly ? ' leaves with the harvest.' : ' in all, from the soil and the fertilizer together.'));
        NPK.forEach((n) => {
            const h = md.how[n], R = (c.recommendedPerHa || {})[n];
            if (h === 'guide') li.push('<b>' + lab(n) + ':</b> the guide rate (' + span(R[0], R[1]).replace('about ', '') + ' kg) is taken as written for about ' + fmt(md.Yg) + ' t/ha and sized to your goal'
                + (n === 'N' ? '' : ' part way (half of it keeps the soil\'s supply up whatever the harvest; the other half follows the tons the harvest carries away)')
                + ': ' + fmt(md.raw[n], 0) + ' kg' + (Math.abs(md.adj[n] - 1) > 0.01 ? ', then ' + (md.adj[n] > 1 ? 'up' : 'down') + ' ' + Math.round(Math.abs(md.adj[n] - 1) * 100) + '% for your soil: ' + fmt(md.need[n], 0) + ' kg' : '') + '.');
            else if (h === 'uptake') li.push('<b>' + lab(n) + ':</b> no guide rate on file, so from the uptake: the part the soil cannot give (' + Math.round((1 - md.base[n]) * 100) + '%), divided by the share of fertilizer the crop catches: ' + fmt(md.need[n], 0) + ' kg.');
            else if (h === 'removal') li.push('<b>' + lab(n) + ':</b> puts back what the harvest carries away: ' + fmt(md.need[n], 0) + ' kg. A soil test tells if more is needed.');
            else if (h === 'legume') li.push('<b>N:</b> a legume makes most of its own nitrogen, so only a starter: ' + fmt(md.need.N, 0) + ' kg.');
        });
        if (g.sd.germ) li.push('<b>Germination:</b> ' + Math.round(g.sd.germ) + '%, so only that share of the seed counts toward the stand (the guides\' seeding rates assume about ' + GERM_USUAL + '%).');
        li.push('<b>Your soil:</b> ' + (md.whys.length ? esc(md.whys.join('; ')) + '.' : 'nothing you said changes the count. A soil type, pH or soil test makes it surer.'));
        li.push('<b>Lockouts:</b> acid or alkaline soil locks up phosphorus (counted above), and alkaline, sodic or limy soil locks up zinc, iron, manganese and boron (the Micros tab).'
            + (md.lockouts && md.lockouts.length ? ' Your plan adds its own: ' + esc(list(md.lockouts.map((k) => ({ high_p: 'heavy phosphorus ties up zinc and iron', high_k: 'heavy potassium crowds out magnesium and calcium', liming: 'lime ties up zinc, boron, manganese, copper and iron', excess_zn: 'heavy zinc crowds out copper' })[k]))) + '.' : ''));
        li.push('<b>What the soil gives on its own:</b> about ' + NPK.map((n) => lab(n) + ' ' + Math.round(md.share[n] * 100) + '%').join(', ') + ' of the goal, as fields given none of that nutrient show in trials. The barrel\'s planks start there.');
        const src = (c.sources || []).map((s) => '<li><a href="' + esc(s.url) + '" target="_blank" rel="noopener">' + esc(s.label) + '</a></li>').join('');
        return '<details class="np-src"><summary>How NPK Plus counted the need</summary><ul>' + li.map((x) => '<li>' + x + '</li>').join('') + '</ul>'
            + (c.proxy ? '<p><b>Close stand in:</b> ' + esc(c.proxy) + '.</p>' : '')
            + (src ? '<p><b>The crop\'s sources:</b></p><ul>' + src + '</ul>' : '') + '</details>';
    };

    /* ---- the micros tab: each element's need per area, from the model ---- */
    const micros = (c, md, tot, perHa, area, has) => {
        const out = {};
        let k = 0;
        const row = (el) => {
            const e = M.elements[el] || {}, mm = md.micro[el], plan = perHa[el] || 0;
            if (!mm) return '';
            const need = mm.need;
            let state;
            if (need > 0) { const r = plan / need; state = r < 0.9 ? 'short' : r > (e.over || 5) ? 'over' : 'right'; }
            else state = plan > 0 ? 'extra' : mm.level === 'notneeded' ? 'notneeded' : mm.level === 'testok' ? 'testok' : 'noflag';
            out[el] = { need: Math.round(need * 1000) / 1000, plan: Math.round(plan * 1000) / 1000, level: mm.level, state };
            // "Not flagged" is not "the soil gives enough": it means nothing you
            // told NPK Plus points to a shortage. Only a soil test says enough.
            const say = { none: 'No shortage flagged: nothing you told NPK Plus points to one', notneeded: 'Not needed for this crop', watch: 'May run short', likely: 'Likely short',
                test: 'Short by your soil test', testok: 'Enough, by your soil test' }[mm.level]
                + (mm.why.length && (mm.level === 'watch' || mm.level === 'likely') ? ': ' + list(mm.why) : '') + '.';
            const down = mm.down && mm.down.length ? '<small class="np-down">' + esc(list(mm.down).charAt(0).toUpperCase() + list(mm.down).slice(1)) + '.</small>' : '';
            const fix = state === 'short' && e.fert ? ' Usual source: ' + e.fert + '.' : '';
            const warn = state === 'over' && e.toxic ? ' ' + e.toxic : '';
            const scale = Math.max(need * 1.6, plan * 1.05, 1e-9);
            const bar = need > 0 || plan > 0 ? '<span class="np-mbar"><span class="have" style="width:' + Math.min(100, plan / scale * 100) + '%"></span>' + (need > 0 ? '<span class="need" style="left:' + (need / scale * 100) + '%"></span>' : '') + '</span>' : '';
            const word = { short: 'Short', right: 'Enough', over: 'Too much', extra: 'Extra', noflag: 'Not flagged', testok: 'Enough', notneeded: 'Not needed' }[state];
            return '<div class="np-mrow is-' + state + (['Ca', 'Mg', 'S'].includes(el) ? ' is-sec' : '') + (e.beneficial ? ' is-ben' : '') + '" style="--k:' + (k++) + '"><i>' + el + '</i>'
                + '<span class="np-mtxt"><b>' + NAMES[el] + (e.beneficial ? '<span class="np-ben">Beneficial, not required</span>' : '') + '</b><small>' + esc(say + fix + warn) + '</small>' + down + bar + '</span>'
                + '<span class="np-mval"><em>' + word + '</em><small>' + (need > 0 ? 'Need ' + amt(need) + ' per ha' + (area > 0 && Math.abs(area - 1) > 0.001 ? ', ' + amt(need * area) + ' in all' : '') : 'Need none') + '</small><small>Plan ' + (plan > 0 ? amt(plan) + ' per ha' : 'none') + '</small></span></div>';
        };
        if (!c) {
            $('npMicro').innerHTML = '<div class="np-say">Pick the crop in step 1: the need for each element depends on the crop and your soil.</div>';
            return out;
        }
        const keys = Object.keys(M.elements);
        const sec = keys.filter((el) => ['Ca', 'Mg', 'S'].includes(el)), ben = keys.filter((el) => (M.elements[el] || {}).beneficial), mic = keys.filter((el) => !sec.includes(el) && !ben.includes(el));
        $('npMicro').innerHTML = (has ? '' : '<p class="np-sub">Add a fertilizer to set your plan against each need.</p>')
            + '<p class="np-mh">Secondary nutrients</p><div class="np-mlist">' + sec.map(row).join('') + '</div>'
            + '<p class="np-mh">Micronutrients</p><div class="np-mlist">' + mic.map(row).join('') + '</div>'
            + '<p class="np-mh">Beneficial elements</p><div class="np-mlist">' + ben.map(row).join('') + '</div>'
            + (tot.Cl > 0 ? '<p class="np-fine">Your plan also carries ' + amt(perHa.Cl) + ' of chloride per ha, from muriate of potash. Most crops take it well; on salty soil, sulfate of potash is the gentler source.</p>' : '')
            + (md.lockouts && md.lockouts.length ? '<div class="np-say"><b>Lockouts from the plan:</b> ' + esc(list(md.lockouts.map((k) => ({ high_p: 'the heavy phosphorus can tie up zinc and iron', high_k: 'the heavy potassium can crowd out magnesium and calcium', liming: 'the lime can tie up zinc, boron, manganese, copper and iron', excess_zn: 'the heavy zinc can crowd out copper' })[k]))) + '. They are counted in the needs above.</div>' : '')
            + '<div class="np-say">Each need is a corrective dose sized to your goal, counted only where your crop, your soil or your plan make a shortfall likely. "Not flagged" means nothing you told NPK Plus points to a shortage, not that the soil has enough: only a soil test can say that.</div>';
        return out;
    };

    /* ---- Liebig's barrel ----
       A crop grows only as far as its scarcest need allows, as a barrel holds
       water only up to its shortest plank. Each plank is the share of the
       yield goal that nutrient allows: what the soil gives on its own, plus
       what the plan covers of the rest. Sun, water and temperature are grey
       planks drawn full, because NPK Plus cannot measure them. */
    const STAVES = [
        { k: 'N', t: 'N' }, { k: 'P2O5', t: 'P₂O₅' }, { k: 'K2O', t: 'K₂O' }, { k: 'Ca', t: 'Ca' }, { k: 'Mg', t: 'Mg' }, { k: 'S', t: 'S' }, { k: 'Zn', t: 'Zn' }, { k: 'B', t: 'B' },
        { k: 'sun', t: 'Sun', name: 'Sunlight', dull: true }, { k: 'water', t: 'Water', name: 'Water', dull: true }, { k: 'temp', t: 'Temp', name: 'Temperature', dull: true },
    ];
    const BR = (() => {
        const n = STAVES.length, R = 80, H = 190, ang = 360 / n;
        const W = Math.ceil(2 * R * Math.tan(Math.PI / n)) + 1, RW = R - 4, WW = Math.ceil(2 * RW * Math.tan(Math.PI / n)) + 1;
        const el = $('lbBarrel'), stage = $('lbStage'), svg = $('lbArrow'), ap = svg.querySelector('.lb-ap'), ah = svg.querySelector('.lb-ah'), alab = $('lbALab');
        const disc = (cls, d, y) => '<div class="' + cls + '" style="width:' + d + 'px;height:' + d + 'px;margin-left:' + (-d / 2) + 'px;top:' + y + 'px"></div>';
        el.innerHTML = disc('lb-shadow', 2 * R * 1.7, H + 2) + disc('lb-floor', 2 * R, H)
            + STAVES.map((s, i) => '<div class="lb-st" style="width:' + W + 'px;margin-left:' + (-W / 2) + 'px;transform:rotateY(' + (i * ang) + 'deg) translateZ(' + R + 'px);--d:' + (i * 55) + 'ms">'
                + '<div class="lb-out"><b>' + s.t + '</b><small></small></div><div class="lb-in"></div><div class="lb-spill"></div><div class="lb-pool"></div></div>').join('')
            + '<div class="lb-water">' + STAVES.map((s, i) => '<div class="lb-wf" style="width:' + WW + 'px;margin-left:' + (-WW / 2) + 'px;transform:rotateY(' + (i * ang) + 'deg) translateZ(' + RW + 'px)"></div>').join('')
            + disc('lb-top', 2 * RW + 3, 0) + '</div>'
            + disc('lb-rim', 2 * R + 16, 0);
        const sts = [...el.querySelectorAll('.lb-st')], water = el.querySelector('.lb-water'), top = el.querySelector('.lb-top');
        let ry = -18, rx = -16, vel = 0, drag = null, aim = null, on = false, raf = 0, t0 = performance.now(), lim = -1, level = 0, arrowT = 0;
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
        // "The water is your yield": a hand drawn arrow from the words to the water.
        const drawArrow = (animate) => {
            const sr = stage.getBoundingClientRect(), tr = top.getBoundingClientRect(), lr = alab.getBoundingClientRect();
            if (!sr.width || level <= 2) { svg.classList.remove('is-on', 'is-drawn'); alab.classList.remove('is-on'); return; }
            const tx = tr.left + tr.width / 2 - sr.left, ty = tr.top + tr.height / 2 - sr.top;
            const sx = lr.left - sr.left - 3, sy = lr.top + lr.height * 0.35 - sr.top;
            const ex = tx + 12, ey = ty - 4, c1x = sx - 26, c1y = sy - 34, c2x = ex + 40, c2y = Math.min(sy, ey) - 40;
            svg.setAttribute('viewBox', '0 0 ' + sr.width + ' ' + sr.height);
            ap.setAttribute('d', 'M' + sx + ',' + sy + ' C' + c1x + ',' + c1y + ' ' + c2x + ',' + c2y + ' ' + ex + ',' + ey);
            const a = Math.atan2(ey - c2y, ex - c2x), L = 10;
            ah.setAttribute('d', 'M' + (ex - L * Math.cos(a - 0.5)) + ',' + (ey - L * Math.sin(a - 0.5)) + ' L' + ex + ',' + ey + ' L' + (ex - L * Math.cos(a + 0.5)) + ',' + (ey - L * Math.sin(a + 0.5)));
            const len = ap.getTotalLength();
            ap.style.strokeDasharray = len;
            alab.classList.add('is-on'); svg.classList.add('is-on');
            if (animate && !still()) {
                svg.classList.remove('is-drawn'); ap.style.transition = 'none'; ap.style.strokeDashoffset = len; void ap.getBoundingClientRect();
                requestAnimationFrame(() => { ap.style.transition = ''; ap.style.strokeDashoffset = 0; svg.classList.add('is-drawn'); });
            } else { ap.style.strokeDashoffset = 0; svg.classList.add('is-drawn'); }
        };
        const arrowSoon = (animate) => { clearTimeout(arrowT); if (!on) return; arrowT = setTimeout(() => drawArrow(animate), still() ? 30 : 1750); };
        stage.addEventListener('pointerdown', (e) => {
            if (e.button || e.target.closest('.lb-info')) return;
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
        const up = () => { if (!drag) return; drag = null; stage.classList.remove('is-grab'); drawArrow(false); };
        stage.addEventListener('pointerup', up); stage.addEventListener('pointercancel', up);
        stage.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') { e.preventDefault(); stage.classList.add('was-turned'); const t = (aim ?? ry) + (e.key === 'ArrowLeft' ? ang : -ang); if (still()) { ry = t; apply(); } else aim = t; }
        });
        window.addEventListener('resize', () => { if (on) drawArrow(false); });
        return {
            H, face,
            set(rows, levelPx, limIdx) {
                rows.forEach((r, i) => {
                    sts[i].style.height = r.px + 'px';
                    sts[i].className = 'lb-st is-' + r.state + (i === limIdx ? ' is-limit' : '') + (i === limIdx && levelPx < H - 1 && levelPx > 0 ? ' is-spill' : '');
                    sts[i].querySelector('small').textContent = r.pct;
                });
                const moved = Math.abs(levelPx - level) > 1;
                level = levelPx;
                water.style.setProperty('--f', Math.max(0.001, levelPx / H));
                if (limIdx !== lim && limIdx >= 0 && on) face(limIdx);
                lim = limIdx;
                if (moved) arrowSoon(false);
            },
            enter() {
                on = true;
                el.classList.add('is-rest');
                svg.classList.remove('is-on', 'is-drawn'); alab.classList.remove('is-on');
                void el.offsetWidth;
                requestAnimationFrame(() => requestAnimationFrame(() => el.classList.remove('is-rest')));
                if (lim >= 0) face(lim);
                if (!raf) raf = requestAnimationFrame(loop);
                arrowSoon(true);
            },
            leave() { on = false; clearTimeout(arrowT); },
        };
    })();
    const WORD = { short: 'Short', right: 'Enough', over: 'Too much', fixed: 'From the air', unknown: 'Not measured', dull: 'Not measured' };
    /* What each plank lets the crop carry, in tons, whatever the goal: the
       plank is that against the goal, so a lower goal is a taller plank and
       never a smaller harvest (the owner's report, 2026-10-08).
       N, P2O5, K2O: the plan feeds the yield whose need it meets (the need
       model run backwards); below the guide's good harvest the soil's own
       share carries part of it. Other elements: the soil alone carries its
       share of the variety's potential, the plan the rest. */
    const allowance = (c, md, g, pot) => {
        const G = g.Yeff, Yr = md.Yg, ys = (y) => (c.typicalYield ? clamp(y / c.typicalYield.hi, 0.8, 1.4) : 1);
        const scaleOf = (n) => {
            if (md.how[n] !== 'guide') return (y) => y;                      // uptake and removal: in step with the tons
            const pk = n === 'N' ? 0 : (M.pkBase ?? 0.5);
            return (y) => pk + (1 - pk) * y / Yr;
        };
        return {
            npk(n, plan) {
                if (!G || !Yr || md.need[n] == null || !(md.need[n] > 0)) return null;
                const sc = scaleOf(n), K = md.need[n] / sc(G), needAt = (y) => K * sc(y);
                const needYr = needAt(Yr), s0 = md.share[n];
                if (plan >= needYr) {
                    // the yield whose need equals the plan
                    if (md.how[n] === 'guide' && n !== 'N') { const pk = M.pkBase ?? 0.5; return Yr * (plan / K - pk) / (1 - pk); }
                    return md.how[n] === 'guide' ? Yr * plan / K : plan / K;
                }
                return s0 * Yr + (1 - s0) * Yr * plan / needYr;
            },
            micro(mm, plan) {
                if (!G || !pot || !(mm.need > 0)) return null;
                const needPot = mm.need * ys(pot.t) / ys(G);
                return pot.t * (mm.share + (1 - mm.share) * Math.min(1, plan / needPot));
            },
        };
    };
    const barrel = (c, md, states, perHa, has, g) => {
        const H = BR.H, rows = [], SP = seasonPlanks(c, g), pot = c ? potentialOf(c, g) : null, AL = c ? allowance(c, md, g, pot) : null, yuA = Y_U[st.yUnit];
        const tons = (t) => fmt(t / yuA[3]) + ' ' + yuA[1];
        STAVES.forEach((s) => {
            if (s.dull) {
                if (!SP) { rows.push({ state: 'dull', ratio: null, px: H, pct: '', note: 'Pick the place and the planting date' }); return; }
                const r = SP[s.k], state = r < 0.97 ? 'short' : 'right', yu0 = Y_U[st.yUnit];
                const note = (s.k === 'sun' ? fmt(SP.rad, 1) + ' MJ of sun a day against the ' + SP.K.rad + ' a full harvest wants'
                    : s.k === 'water' ? (st.water === 'irrigated' ? 'Irrigated: the crop gets the ' + Math.round(SP.need) + ' mm it drinks' : 'About ' + Math.round(SP.supply) + ' of the ' + Math.round(SP.need) + ' mm the crop drinks')
                    : fmt(SP.tmean, 1) + '°C on average, ' + Math.round(SP.hotN) + ' days too hot for flowers')
                    + (SP.cap ? ', enough for about ' + fmt(SP.cap[s.k] / yu0[3]) + ' ' + yu0[1] : '');
                rows.push({ state, ratio: r, px: Math.max(22, Math.round(H * Math.min(1.22, r))), pct: Math.round(Math.min(1, r) * 100) + '%', note, season: true });
                return;
            }
            const n = s.k;
            let state = 'unknown', ratio = null, note = c ? 'No numbers for this crop' : 'Pick the crop';
            if (c && NPK.includes(n)) {
                if (md.how.N === 'legume' && n === 'N') { state = 'fixed'; ratio = 1; note = 'A legume makes its own'; }
                else if (md.need[n] != null) {
                    const s0 = md.share[n], cover = md.need[n] > 0 ? perHa[n] / md.need[n] : 1, A = AL.npk(n, perHa[n] || 0);
                    ratio = A != null ? A / g.Yeff : s0 + (1 - s0) * Math.min(1, cover);
                    state = states[n] ? states[n].state : 'right';
                    // Short of the goal's need, but the soil's own share carries the rest of it.
                    if (state === 'short' && ratio >= 0.97) state = 'right';
                    if (state === 'over') ratio = Math.min(1.22, 1 + (cover - 1) * 0.25);
                    note = 'Plan covers ' + Math.round(Math.min(cover, 9.99) * 100) + '% of the need for your goal' + (A != null ? ', enough for about ' + tons(A) : ', soil ' + Math.round(s0 * 100) + '%');
                }
            } else if (c && md.micro[n]) {
                const mm = md.micro[n], plan = perHa[n] || 0;
                if (mm.need > 0) {
                    const cover = plan / mm.need, A = AL.micro(mm, plan);
                    ratio = A != null ? A / g.Yeff : mm.share + (1 - mm.share) * Math.min(1, cover);
                    state = cover > ((M.elements[n] || {}).over || 5) ? 'over' : ratio < 0.97 ? 'short' : 'right';
                    if (state === 'over') ratio = 1.1;
                    note = { watch: 'May run short', likely: 'Likely short', test: 'Low by your soil test' }[mm.level] + (plan > 0 ? ', plan covers ' + Math.round(Math.min(cover, 9.99) * 100) + '%' : ', none in plan')
                        + (A != null ? '; enough for about ' + tons(A) : '');
                } else if (mm.level === 'testok') { state = 'right'; ratio = 1; note = 'Enough, by your soil test'; }
                else { state = 'unknown'; ratio = null; note = 'No shortage flagged, not measured'; }
            }
            const px = state === 'unknown' ? H : Math.max(22, Math.round(H * Math.min(1.22, ratio)));
            // A plank taller than the goal holds the water at the rim, no higher.
            rows.push({ state, ratio, px, pct: ratio === null ? '?' : Math.round(Math.min(ratio, 1) * 100) + '%', note });
        });
        const measured = rows.map((r, i) => [r, i]).filter(([r]) => r.ratio !== null && r.state !== 'dull');
        // The season's sky and water, from ten years of weather.
        $('lbSky').hidden = !SP;
        if (SP) {
            const S = st.season, yrs = S.years, maxR = Math.max(1, ...yrs.map((y) => y.rain), SP.rain), yu0 = Y_U[st.yUnit];
            // Each row: what it lets the variety carry, in tons, and that against the goal.
            const carry = (k) => SP.cap ? ' Enough for about <b>' + esc(fmt(SP.cap[k] / yu0[3])) + ' ' + esc(yu0[1]) + '</b>.' : '';
            const pctOf = (k) => Math.round(Math.min(1, SP[k]) * 100) + '%';
            $('lbSky').innerHTML = '<h4>Your season\'s sun, water and heat</h4>'
                + (SP.pot ? '<p class="lb-rainkey" style="margin:0 0 .5rem">Each is what it lets ' + (SP.pot.given ? 'your variety\'s ' + esc(fmt(SP.pot.t / yu0[3])) + ' ' + esc(yu0[1]) + ' potential' : 'a good variety (about ' + esc(fmt(SP.pot.t / yu0[3])) + ' ' + esc(yu0[1]) + '; give the potential in step 2 to use yours)') + ' carry this season, set against your goal.</p>' : '')
                + '<div class="lb-skyrow"><i>☀️</i><span><b>Sun</b><small>' + fmt(SP.rad, 1) + ' MJ a day expected, against about ' + SP.K.rad + ' for a full harvest of ' + esc(cropShort(c).toLowerCase()) + (SP.t.sun !== 1 ? ', with ENSO' : '') + '.' + carry('sun') + '</small></span><em>' + pctOf('sun') + '</em></div>'
                + '<div class="lb-skyrow is-water"><i>💧</i><span><b>Water</b><small>' + Math.round(SP.rain) + ' mm of rain expected' + (SP.t.rain !== 1 ? ' (ENSO tilted)' : '') + '; the crop drinks about ' + Math.round(SP.need) + ' mm. ' + esc(WATER_U[st.water][0]) + '.' + (st.water !== 'irrigated' ? ' In the driest of the ' + yrs.length + ' years: ' + Math.round(SP.wrDry * 100) + '%.' : (SP.t.e && SP.t.e.phase === 'el_nino' && SP.t.rain < 0.8 ? ' In a strong El Niño, check that the canal will run all season.' : '')) + carry('water') + '</small></span><em>' + pctOf('water') + '</em></div>'
                + '<div class="lb-skyrow is-temp"><i>🌡️</i><span><b>Temperature</b><small>' + fmt(SP.tmean, 1) + '°C on average against ' + SP.K.opt[0] + ' to ' + SP.K.opt[1] + '°C best for it; about ' + Math.round(SP.hotN) + ' days at ' + SP.K.hot + '°C or more.' + carry('temp') + '</small></span><em>' + pctOf('temp') + '</em></div>'
                + '<div class="lb-rainyears" aria-label="Rain in the same weeks of each year">' + yrs.map((y) => '<span style="height:' + Math.max(4, y.rain / maxR * 100) + '%" title="' + y.year + ': ' + y.rain + ' mm"><b>' + String(y.year).slice(2) + '</b></span>').join('')
                + '<span class="is-now" style="height:' + Math.max(4, SP.rain / maxR * 100) + '%" title="Expected this season: ' + Math.round(SP.rain) + ' mm"><b>Now</b></span></div>'
                + '<p class="lb-rainkey">Rain in the same weeks of each of the last ' + yrs.length + ' years, and the striped bar for this season, tilted by ' + esc(SP.t.e ? SP.t.e.label.toLowerCase() : 'nothing') + '. Weather history: Open-Meteo. ENSO: NOAA.</p>';
        }
        let limIdx = -1, level = 0;
        if (has && measured.length) { [, limIdx] = measured.reduce((a, b) => (b[0].px < a[0].px ? b : a)); level = Math.min(H, rows[limIdx].px); }
        BR.set(rows, level, limIdx);
        const yu = Y_U[st.yUnit];
        $('lbTag').textContent = g.Yeff ? 'The rim is your goal, ' + fmt(g.Yeff / yu[3]) + ' ' + yu[1] + '.' : 'The rim is everything the crop needs.';
        $('lbKey').innerHTML = STAVES.map((s, i) => {
            const r = rows[i];
            return '<button type="button" class="lb-k is-' + r.state + '" data-i="' + i + '" title="' + esc(r.note) + '"><i></i><b>' + esc(s.name || NAMES[s.k]) + (s.dull ? '' : ' <small>' + s.t + '</small>') + '</b>'
                + '<span class="lb-kbar"><span style="width:' + (r.ratio === null ? (r.state === 'dull' ? 100 : 0) : Math.min(100, r.ratio * 100)) + '%"></span></span><em>' + (WORD[r.state] || '') + '</em></button>';
        }).join('');
        // The water, said: the share of the goal the plan reaches.
        const reach = limIdx >= 0 ? Math.round(Math.min(1, rows[limIdx].ratio) * 100) : null;
        $('lbReach').hidden = reach === null;
        if (reach !== null) $('lbReach').innerHTML = '<b>' + reach + '%</b><span>of your goal' + (g.Yeff ? ', near ' + fmt(g.Yeff * reach / 100 / yu[3]) + ' ' + esc(yu[1]) : '') + ', by this count. The water is your yield.</span>';
        $('lbALab').innerHTML = 'The water is your yield' + (reach !== null && g.Yeff ? '<b>about ' + esc(fmt(g.Yeff * reach / 100 / yu[3])) + ' ' + esc(yu[1]) + '</b>' : '');
        let say = '';
        const cName = c ? esc(cropShort(c).toLowerCase()) : '';
        if (!c) say = st.cropPicked ? 'No crop picked: the barrel needs the crop\'s numbers.' : 'Pick the crop in step 1: the planks are measured against what it needs.';
        else if (!has) say = 'Add a fertilizer in step 5, and the water rises to show how much of your goal the plan can reach.';
        else if (limIdx < 0) say = 'There are no numbers on file for ' + cName + ', so the planks cannot be measured.';
        else {
            const L = rows[limIdx], Ls = STAVES[limIdx];
            const seasonMax = SP && SP.cap ? Math.min(SP.cap.sun, SP.cap.water, SP.cap.temp) : null;
            say = reach >= 97
                ? 'Every plank NPK Plus can measure reaches the rim: on this plan, the nutrients are not what holds your goal back.'
                    + (seasonMax && g.Yeff && seasonMax >= g.Yeff ? ' The season itself could carry about ' + esc(fmt(seasonMax / yu[3])) + ' ' + esc(yu[1]) + ', so your goal of ' + esc(fmt(g.Yeff / yu[3])) + ' ' + esc(yu[1]) + ' sits within it.' : '')
                : 'The shortest plank is <b>' + esc(Ls.name || NAMES[Ls.k]) + '</b>' + (Ls.name ? '' : ' (' + Ls.t + ')') + ': ' + esc(L.note.toLowerCase()) + '. It holds the water at ' + reach + '% of your goal. Raise it first; more of the others will not raise the water.';
            const over = STAVES.filter((s, i) => rows[i].state === 'over').map((s) => NAMES[s.k].toLowerCase());
            if (over.length) say += ' Too much ' + esc(list(over)) + ': the taller plank holds no more water. It costs money' + (over.includes('nitrogen') ? ', and too much nitrogen can make the crop lodge and draw pests' : '') + '.';
            const unk = STAVES.filter((s, i) => rows[i].state === 'unknown').map((s) => s.t);
            if (unk.length) say += ' Not measured: ' + esc(unk.join(', ')) + '. Nothing you told NPK Plus points to a shortage of them; a soil test would measure them.';
        }
        say += SP ? ' Sun, water and temperature are read from ten years of weather at your field and the ENSO state now: a rough guide to the season, not a forecast of it.'
            : ' The grey planks, sun, water and temperature, can be the shortest too: pick the place and the planting date in step 4 and NPK Plus reads them from ten years of weather at your field.';
        $('lbSay').innerHTML = '<div class="np-say">' + say + '</div>';
        return reach;
    };
    $('lbKey').addEventListener('click', (e) => { const b = e.target.closest('.lb-k'); if (b) { $('lbStage').classList.add('was-turned'); BR.face(Number(b.dataset.i)); } });
    $('lbInfo').addEventListener('click', (e) => { e.stopPropagation(); window.openSheet('npLawSheet'); });

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

    /* ---- save, and Anee ---- */
    const save = async () => {
        if (!RES || !RES.lines.length) return null;
        const r = await window.api(U.save, { method: 'POST', body: { id: calcId, crop: st.crop, areaHa: RES.areaHa || 0.0001, targetYield: RES.goal ? RES.goal.tPerHa : null, lines: RES.lines, soil: soil(), result: RES } });
        calcId = r.data.id;
        return r;
    };
    $('npSave').addEventListener('click', async () => { try { const r = await save(); window.toast?.(r.message + ' It is on the Saved tab.', 'success'); } catch (err) { window.toast?.(err.message, 'error'); } });
    $('npAnee').addEventListener('click', () => {
        $('npRun').disabled = !OPT.canUse;
        if (!$('npLoc').value.trim()) { const [tw, pv] = placeWords(); if (pv) $('npLoc').value = (tw ? tw + ', ' : '') + pv; }
        $('npRunSays').textContent = OPT.canUse ? 'Ask Anee · ' + OPT.quote + ' credits' : 'Anee is not on your plan';
        $('npRunFine').innerHTML = OPT.canUse ? 'You have ' + (window.creditCoin ? window.creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString()) : OPT.balance) + '. Charged only when the reading is ready.' : esc(OPT.whyNot || '');
        window.openSheet('npAneeSheet');
    });
    const ans = { timing: 'split2' };
    const pills = (box, items, key) => {
        box.innerHTML = Object.entries(items).map(([k, v]) => '<button type="button" class="np-pill" data-k="' + esc(k) + '">' + esc(String(v).split(' —')[0]) + '</button>').join('');
        const paint = () => box.querySelectorAll('.np-pill').forEach((b) => b.setAttribute('aria-pressed', String(ans[key] === b.dataset.k)));
        box.addEventListener('click', (e) => { const b = e.target.closest('.np-pill'); if (!b) return; ans[key] = b.dataset.k; paint(); });
        paint();
    };
    $('npRun').addEventListener('click', async () => {
        if (!$('npLoc').value.trim()) { const [tw, pv] = placeWords(); if (pv) $('npLoc').value = (tw ? tw + ', ' : '') + pv; }
        if (!$('npLoc').value.trim()) return window.toast?.('Say where the field is.', 'error');
        $('npRun').disabled = true;
        try {
            await save();
            window.closeSheet('npAneeSheet');
            window.aneeWait.show({ title: 'Anee is checking your plan…', sub: 'The soil, the season and the timing. About a minute.', lines: ['Weighing each nutrient against the need…', 'Reading the soils of your area…', 'Checking the rains around planting…', 'Deciding what to split and when…'] });
            const r = await window.api(U.analyze, { method: 'POST', body: { calcId, location: $('npLoc').value.trim(), soilConditions: st.conds.filter((k) => k in (OPT.soilConditions || {})), phValue: phVal(),
                water: st.water, plantingDate: $('npPlanted').value || null, timing: ans.timing, variety: $('npVariety').value.trim(), notes: $('npNotes').value.trim() } });
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
            st.yUnit = (set.potential && Y_U[set.potential.key]) ? set.potential.key : (Y_U[set.yUnit] ? set.yUnit : 't');
            tagSay('npYUnit', Y_U, st.yUnit);
            $('npPotential').value = set.potential ? set.potential.value : '';
            $('npTarget').value = set.target ? set.target.value : (!set.potential && d.targetYield ? d.targetYield : '');
            const seed = set.seed;
            st.seedUnitSet = !!seed;
            if (seed && SEED_U[seed.key]) { st.seedUnit = seed.key; tagSay('npSeedUnit', SEED_U, st.seedUnit); }
            $('npSeed').value = seed ? seed.amount : '';
            $('npGerm').value = seed && seed.germination ? seed.germination : '';
            st.prov = set.prov || null; st.town = set.town || null; st.water = WATER_U[set.water] ? set.water : 'irrigated';
            if ($('npProvBtn')) { tagWide('npProvBtn', st.prov || 'Choose the province', !st.prov); tagWide('npTownBtn', st.town ? townName(st.town) : (st.prov ? 'Choose the town' : 'Pick the province first'), !st.town); $('npTownBtn').disabled = !st.prov; }
            tagWide('npWaterBtn', WATER_U[st.water][0], false);
            $('npPlanted').value = set.planted || '';
            st.season = null; paintSeason();
            if (st.prov) seasonSoon(400);
            st.texture = M.textures[set.texture] ? set.texture : 'unsure';
            st.conds = Array.isArray(set.conditions) ? set.conditions.filter((k) => M.conditions[k]) : [];
            const s = d.soil || {};
            $('npPh').value = set.ph != null ? set.ph : (s.ph != null ? s.ph : '');
            const hasTest = SOIL.some(([k]) => s[k] != null);
            $('npSoilOn').checked = hasTest; $('npSoil').classList.toggle('is-on', hasTest);
            $('npSoilGrid').querySelectorAll('input').forEach((i) => { i.value = s[i.dataset.s] != null ? s[i.dataset.s] : ''; });
            st.pMethod = s.pMethod === 'bray' ? 'bray' : 'olsen'; st.kUnit = s.kUnit === 'meq' ? 'meq' : 'ppm';
            tagSay('npPMethod', P_METHOD, st.pMethod); tagSay('npKUnit', K_UNIT, st.kUnit);
            soilTag();
            st.lines = (d.lines || []).filter((l) => OPT.products.some((p) => p.id === l.id)).map((l) => ({ id: l.id, amount: l.amount, unit: l.unit }));
            tab(false); lines(); setCrop(d.crop || null); go(5);
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
            OPT = r.data; M = OPT.model;
            $('npCats').innerHTML = '<button type="button" class="np-cat is-on" data-c="all">All</button>' + Object.entries(OPT.categories).map(([k, v]) => '<button type="button" class="np-cat" data-c="' + esc(k) + '">' + esc(v) + '</button>').join('');
            pills($('npTiming'), OPT.timing, 'timing');
            soilTag(); lines(); hints(); calc();
        } catch (err) { window.toast?.(err.message || 'Could not load the calculator.', 'error'); }
    };
    if (window.api) boot(); else window.addEventListener('load', boot, { once: true });
})();
</script>
@endpush
