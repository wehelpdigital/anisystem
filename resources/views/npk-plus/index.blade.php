@extends('layouts.app')
@section('title', 'NPK Plus Calculator')
@section('page-title', 'NPK Plus')
@section('page-subtitle', 'Every nutrient in your fertilizer plan')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))
{{-- No phone tab bar here (the owner's call): the calculator has the whole screen. --}}
@section('body-class', 'hide-tabbar no-footer')

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
    .np-fpc-sub { margin: -.2rem 0 .6rem; }
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
    .np-wstep.go-next { animation: npNext .26s ease .1s both; }
    .np-wstep.go-back { animation: npBack .26s ease .1s both; }
    /* Steps fade, never slide (2026-10-16, the owner). */
    @keyframes npNext { from { opacity: 0; } }
    @keyframes npBack { from { opacity: 0; } }
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
    .np-cpack { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    .np-cpack input { width: 7rem; }
    .np-pill { display: inline-flex; align-items: center; gap: .3rem; padding: .45rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 700; cursor: pointer;
        color: var(--color-gray-700); background: var(--color-white); border: 1px solid var(--color-gray-200);
        transition: background-color .28s var(--np-ease), border-color .28s var(--np-ease), color .28s var(--np-ease), transform .28s var(--np-ease); }
    .np-pill:hover { border-color: var(--color-brand-600); }
    .np-pill[aria-pressed="true"] { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    #npPickFoot[hidden], #npPickSearchBox[hidden] { display: none; }
    .np-psearch { position: relative; margin-bottom: .6rem; }
    .np-psearch svg { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); width: 1.05rem; height: 1.05rem; color: var(--color-gray-400); pointer-events: none; }
    .np-psearch .form-input { padding-left: 2.4rem; }
    /* The season, read from twenty years of weather at the field. */
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
    .np-ptabs { display: grid; grid-template-columns: 1fr 1fr; gap: .25rem; padding: .25rem; margin-bottom: .7rem; border-radius: .9rem; background: var(--color-gray-100); }
    .np-ptab { padding: .5rem .4rem; border-radius: .7rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-600); transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .np-ptab.is-on { background: var(--color-white); color: var(--color-brand-700); box-shadow: 0 4px 12px -8px rgb(15 23 42 / .5); }
    html.dark .np-ptabs { background: #1c2616; } html.dark .np-ptab.is-on { background: #2b3a1c; color: #e8efe1; }
    .np-phb { display: inline-block; margin-right: .3rem; padding: .05rem .4rem; border-radius: 999px; font-size: .62rem; font-weight: 800; letter-spacing: .03em; color: #3b2f00; background: #fde68a; vertical-align: 1px; }
    .np-phok { display: inline-block; margin-left: .3rem; font-size: .62rem; font-weight: 800; color: #2d5016; }
    #npPhList .np-prow, #npPhList .crop-group-h { animation: npPhIn .3s cubic-bezier(.22,1,.36,1) both; }
    @keyframes npPhIn { from { opacity: 0; transform: translateY(4px); } }
    @media (prefers-reduced-motion: reduce) { #npPhList .np-prow, #npPhList .crop-group-h { animation: none; } .np-ptab { transition: none; } }
    .np-cats { display: flex; gap: .3rem; overflow-x: auto; scrollbar-width: none; margin: 0 -.1rem .3rem; padding: .1rem; }
    .np-cats::-webkit-scrollbar { display: none; }
    .np-cat { flex: none; padding: .38rem .7rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: var(--color-gray-600); background: var(--color-gray-100); border: 0; cursor: pointer;
        transition: background-color .28s var(--np-ease), color .28s var(--np-ease); }
    .np-cat.is-on { background: #1f2937; color: #fff; }
    /* A mouse cannot slide the tab row: on a desktop it is one tag that opens the choices (2026-10-19). */
    .np-catbtn { display: none; }
    @media (min-width: 768px) and (pointer: fine) {
        .np-catbtn { display: inline-flex; width: 100%; max-width: none; min-height: 2.6rem; margin: 0 0 .55rem; }
        .np-catbtn span b { font-weight: 800; color: var(--color-gray-900); }
        html.dark .np-catbtn span b { color: #e8efe1; }
        #npCats, #npPhCats { display: none; }
    }
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
    .np-microsw { margin: .2rem 0 .8rem; padding: .55rem .7rem; border-radius: .9rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-200); }
    html.dark .np-microsw { background: #121a0d; border-color: #2b3a1c; }
    .np-otab.is-off { opacity: .55; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    @media (prefers-reduced-motion: reduce) { .np-otab.is-off { transition: none; } }
    .np-soilu { display: flex; flex-wrap: wrap; gap: .5rem .9rem; margin-top: .7rem; }
    .np-soilu label { display: flex; align-items: center; gap: .45rem; font-size: .74rem; font-weight: 800; color: var(--color-gray-600); }
    .np-soilu .np-utag { min-height: 2.1rem; min-width: 0; }
    .np-testbox { margin-top: 1rem; padding: .8rem; border-radius: .9rem; border: 1px dashed var(--color-gray-200); }
    /* The area's typical soil (2026-10-18): offered, never applied by itself. */
    .np-area { margin: .2rem 0 .9rem; padding: .85rem .9rem; border-radius: .95rem; border: 1px solid rgb(132 204 22 / .35); background: linear-gradient(180deg, rgb(132 204 22 / .1), rgb(132 204 22 / .03)); }
    html.dark .np-area { border-color: #3a5a1c; background: linear-gradient(180deg, rgb(132 204 22 / .12), transparent); }
    .np-area-h { font-family: var(--font-heading); font-weight: 800; font-size: .9rem; color: var(--color-gray-900); }
    .np-area-t { margin-top: .15rem; font-size: .78rem; color: var(--color-gray-500); }
    .np-area-l { margin: .5rem 0 0; padding-left: 1rem; list-style: disc; font-size: .82rem; line-height: 1.5; color: var(--color-gray-700); }
    .np-area-l li + li { margin-top: .2rem; }
    .np-area-b { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .7rem; }
    .np-area-use { max-width: 20rem; overflow: hidden; white-space: nowrap; padding: .5rem .85rem; border-radius: .7rem; border: 0; cursor: pointer; font-weight: 800; font-size: .82rem; color: #1a1a1a; background: linear-gradient(135deg, #f7d23a, #f5c518);
        transition: transform .28s var(--np-ease), opacity .28s var(--np-ease), max-width .28s var(--np-ease), padding .28s var(--np-ease); }
    .np-area-use:hover { transform: translateY(-1px); }
    .np-area-no { padding: .5rem .85rem; border-radius: .7rem; border: 1px solid var(--color-gray-200); background: transparent; cursor: pointer; font-weight: 700; font-size: .82rem; color: var(--color-gray-700); }
    .np-area.is-on .np-area-use { opacity: 0; max-width: 0; padding-left: 0; padding-right: 0; pointer-events: none; }
    .np-area-say:not([hidden]) { animation: npAreaIn .28s var(--np-ease); }
    @keyframes npAreaIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: none; } }
    .np-area-say { margin-top: .5rem; font-size: .78rem; font-weight: 700; color: #4d7c0f; }
    html.dark .np-area-say { color: #a3e635; }
    @media (prefers-reduced-motion: reduce) { .np-area-use { transition: none; } .np-area-say:not([hidden]) { animation: none; } }
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
    .np-mrow.is-helps .np-mval em { color: #2d5016; }
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
    .lb-st.is-unknown .lb-out, .lb-st.is-unknown .lb-in, .lb-st.is-noflag .lb-out, .lb-st.is-noflag .lb-in,
    .lb-st.is-notneeded .lb-out, .lb-st.is-notneeded .lb-in, .lb-st.is-extra .lb-out, .lb-st.is-extra .lb-in { filter: saturate(.25) brightness(1.22); }
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
    .lb-alab small { display: block; margin-top: .1rem; font-size: .7rem; font-style: normal; font-weight: 800; line-height: 1.15; }
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
    .lb-k.is-dull > i { filter: grayscale(1); opacity: .7; } .lb-k.is-unknown > i, .lb-k.is-noflag > i, .lb-k.is-notneeded > i, .lb-k.is-extra > i { opacity: .45; }
    .lb-k b { font-weight: 800; color: var(--color-gray-800); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lb-k b small { font-weight: 700; color: var(--color-gray-400); }
    .lb-kbar { position: relative; height: .4rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .lb-kbar span { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 999px; background: #4a7c2a; transition: width .6s var(--np-ease); }
    .lb-k.is-short .lb-kbar span { background: #f59e0b; } .lb-k.is-over .lb-kbar span { background: #dc2626; }
    .lb-k.is-dull .lb-kbar span, .lb-k.is-unknown .lb-kbar span, .lb-k.is-noflag .lb-kbar span, .lb-k.is-notneeded .lb-kbar span, .lb-k.is-extra .lb-kbar span { background: var(--color-gray-300, #cbd5e1); }
    .lb-k em { font-style: normal; font-size: .66rem; font-weight: 800; text-align: right; color: var(--color-gray-500); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lb-k.is-short em { color: #b45309; } .lb-k.is-over em { color: #b91c1c; } .lb-k.is-right em, .lb-k.is-fixed em, .lb-k.is-testok em { color: #2d5016; }
    .lb-reach { display: flex; align-items: baseline; gap: .55rem; margin-top: .7rem; font-size: .8rem; line-height: 1.45; color: var(--color-gray-600); }
    .lb-reach[hidden] { display: none; }
    .lb-reach b { flex: none; font-family: var(--font-heading); font-size: 1.7rem; font-weight: 800; line-height: 1; color: #1d6fd1; }
    html.dark .lb-reach b { color: #7cc0ff; }
    .lb-reach .lb-harvest { display: block; margin-top: .35rem; font-size: .76rem; color: var(--color-gray-500); }
    .lb-reach .lb-harvest b { display: inline; font-size: inherit; font-family: inherit; color: var(--color-gray-700); }
    html.dark .lb-reach .lb-harvest b { color: #e8efe1; }
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

    /* The variety tag and its sheet (2026-10-15): the records, or a name typed as sold. */
    .np-vtag span { font-weight: 800; }
    .np-vtag.is-rec span::before { content: '✓ '; color: var(--color-brand-600, #4a7c2a); }
    /* Where the potential came from: the record, or the farmer. */
    .np-potfrom { display: flex; align-items: flex-start; gap: .45rem; margin-top: .4rem; padding: .5rem .6rem; border-radius: .75rem; font-size: .74rem; line-height: 1.45;
        color: #24400f; background: #f3f8ec; border: 1px solid #d5e7c0; animation: npIn .3s var(--np-ease) both; }
    .np-potfrom[hidden] { display: none; }
    .np-potfrom svg { flex: none; width: .95rem; height: .95rem; margin-top: .1rem; color: #4a7c2a; }
    .np-potfrom button { font-weight: 800; color: var(--color-brand-700); text-decoration: underline; text-underline-offset: 3px; }
    .np-potfrom.is-own { color: #6b4a00; background: #fff8e1; border-color: #f5d77a; }
    .np-potfrom.is-own svg { color: #b45309; }
    /* The field in detail, in the soil step. */
    .np-fpc { margin: .9rem 0 .2rem; padding-bottom: .4rem; border-bottom: 1px dashed var(--color-gray-200); }
    .np-fpc-l { margin-top: 0; }
    /* Your field, counted: every adjustment as a plain line with its reason. */
    .np-field { margin-top: .9rem; padding: .8rem .85rem; border-radius: 1rem; background: linear-gradient(160deg, #fbfdf8, #f2f8ea); border: 1px solid #d5e7c0; animation: npIn .32s var(--np-ease) both; }
    .np-field h4 { display: flex; align-items: center; gap: .45rem; font-family: var(--font-heading); font-weight: 800; font-size: .92rem; color: #1f3a10; }
    .np-field h4 i { font-style: normal; }
    .np-field > p { margin-top: .2rem; font-size: .74rem; color: var(--color-gray-500); }
    .np-fl { display: grid; gap: .4rem; margin-top: .6rem; }
    .np-fli { display: flex; align-items: flex-start; gap: .55rem; padding: .5rem .6rem; border-radius: .8rem; background: var(--color-white); border: 1px solid #e3ecd8; font-size: .8rem; line-height: 1.5; color: var(--color-gray-700);
        animation: npIn .3s var(--np-ease) both; animation-delay: calc(var(--k, 0) * 40ms); }
    .np-fli > i { flex: none; width: 1.6rem; height: 1.6rem; border-radius: .55rem; display: grid; place-items: center; font-style: normal; font-size: .9rem; background: #f1f6ea; }
    .np-fli > span { min-width: 0; flex: 1 1 auto; }
    .np-fli b { color: var(--color-gray-900); }
    .np-fli em { display: inline-block; margin-left: .2rem; padding: .05rem .45rem; border-radius: 999px; font-style: normal; font-size: .68rem; font-weight: 800; white-space: nowrap; color: #2d5016; background: #e4efd4; }
    .np-fli em.is-up { color: #8a5a00; background: #fff1c2; }
    .np-fli em.is-tip { color: #334155; background: #e2e8f0; }
    .np-aneefield { margin: .8rem 0 0; padding: .55rem .7rem; border-radius: .8rem; font-size: .78rem; line-height: 1.5; color: #24400f; background: #f3f8ec; border: 1px solid #d5e7c0; }
    .np-aneefield[hidden] { display: none; }
    .np-free { display: flex; align-items: center; justify-content: center; gap: .4rem; margin-top: .6rem; font-size: .74rem; font-weight: 700; line-height: 1.4; text-align: center; color: var(--color-gray-500); }
    .np-free svg { flex: none; width: .9rem; height: .9rem; color: var(--color-brand-600, #4a7c2a); }
    html.dark .np-potfrom, html.dark .np-aneefield { color: #cfe6b8; background: #17220f; border-color: #2b3a1c; }
    html.dark .np-potfrom.is-own { color: #f3dc9a; background: #2a2208; border-color: #5c4a10; }
    html.dark .np-fpc { border-color: #2b3a1c; }
    html.dark .np-field { background: linear-gradient(160deg, #151c11, #18220f); border-color: #2b3a1c; }
    html.dark .np-field h4 { color: #e8efe1; }
    html.dark .np-fli { background: #10150c; border-color: #2b3a1c; color: #c9d6bc; }
    html.dark .np-fli b { color: #eef3e8; }
    html.dark .np-fli > i { background: #1c2616; }
    html.dark .np-fli em { color: #cfe6b8; background: #22301a; }
    html.dark .np-fli em.is-up { color: #f3dc9a; background: #3a2e0c; }
    html.dark .np-fli em.is-tip { color: #cbd5e1; background: #1e293b; }
    @media (prefers-reduced-motion: reduce) {
        .np-potfrom, .np-field, .np-fli { animation: none; }
    }

    /* The farm's lots (2026-10-16, the owner): the picker in step 3, and every
       question the picked lot answered folds away (its words say where they came from). */
    .np-wq2 { margin-top: 1.3rem; padding-top: 1rem; border-top: 1px dashed var(--color-gray-200); }
    html.dark .np-wq2 { border-color: #2b3a1c; }
    #npLots { margin-top: .2rem; }
    .np-nolot { display: inline-flex; margin-top: .55rem; padding: .3rem 0; font-size: .78rem; font-weight: 800; text-align: left; color: var(--color-brand-700, #3d6823);
        text-decoration: underline; text-underline-offset: 3px; animation: npIn .3s var(--np-ease) both; }
    .np-nolot[hidden] { display: none; }
    .np-lq { display: grid; grid-template-rows: 1fr; opacity: 1; transition: grid-template-rows .28s var(--np-ease), opacity .28s var(--np-ease); }
    .np-lq > div { min-height: 0; min-width: 0; overflow: hidden; padding: 3px; margin: -3px; }
    .np-lq.is-off { grid-template-rows: 0fr; opacity: 0; }
    .np-lotsay { display: flex; align-items: flex-start; gap: .5rem; margin: .8rem 0 .1rem; padding: .6rem .7rem; border-radius: .85rem; font-size: .8rem; line-height: 1.5;
        color: #24400f; background: #f3f8ec; border: 1px solid #d5e7c0; animation: npIn .3s var(--np-ease) both; }
    .np-lotsay[hidden] { display: none; }
    .np-lotsay > i { flex: none; font-style: normal; font-size: 1rem; line-height: 1.3; }
    .np-lotsay > span { min-width: 0; overflow-wrap: anywhere; }
    .np-lotsay b { color: inherit; }
    .np-lotsay button { font-weight: 800; color: var(--color-brand-700, #3d6823); text-decoration: underline; text-underline-offset: 3px; white-space: nowrap; }
    html.dark .np-lotsay { color: #cfe6b8; background: #17220f; border-color: #2b3a1c; }
    html.dark .np-lotsay button, html.dark .np-nolot { color: #a5c97e; }
    @media (prefers-reduced-motion: reduce) {
        .np-lq { transition: none; }
        .np-lotsay, .np-nolot { animation: none; }
    }
</style>

@php
    $chev = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
    // Inside Anee Auto (?frame=auto) the season's lot is handed over by the frame: no lot picker there.
    $npFrame = request()->query('frame') === 'auto';
@endphp
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
                        <button type="button" class="np-ws" data-s="2"><i>3</i><span>{{ $npFrame ? 'Planting' : 'Lot' }}</span></button>
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
                            {{-- The variety, two ways (2026-10-15, the owner): picked from the
                                 records (its trial yield fills the potential), or typed as sold. --}}
                            <span class="np-label">Variety <span>(optional)</span></span>
                            <button type="button" class="np-utag is-wide is-none np-vtag" id="npVarBtn"><span id="npVarSays">Pick from the records, or type your own</span>{!! $chev !!}</button>
                            <input type="hidden" id="npVariety" value="">
                            <span class="np-hint" id="npVarHint" hidden></span>
                            @include('partials.variety-pick')
                            <div class="np-row" style="align-items:center;margin-top:.85rem"><span class="np-label" style="margin:0;flex:1 1 auto">Yields in</span>
                                <button type="button" class="np-utag is-inline" id="npYUnit" aria-label="Yield unit"><span>t/ha</span>{!! $chev !!}</button></div>
                            <div class="np-two">
                                <div>
                                    <label class="np-label" for="npPotential">Potential of the variety <span>(optional)</span></label>
                                    <input type="number" id="npPotential" class="form-input w-full" min="0" step="any" inputmode="decimal" placeholder="Like 8">
                                    <span class="np-hint" id="npPotHint">On the seed bag or the variety's description.</span>
                                    <div class="np-potfrom" id="npPotFrom" hidden></div>
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
                            @if ($npFrame)
                            <p class="np-wq">What you plant, and where</p>
                            <p class="np-sub">Too thin a stand cannot reach the goal; NPK Plus counts for what it can.</p>
                            @else
                            {{-- The farm's lots (2026-10-16, the owner): a lot brings its place,
                                 size, soil and water (partials/farm-lots), so the steps after
                                 do not ask them again. Optional here: the calculator is free
                                 and works without one, exactly as before. --}}
                            <p class="np-wq">Which lot is it for?</p>
                            <p class="np-sub">Pick the lot, or add it. Its place, size, soil and water come with it, so they are not asked again. No lot? Go on, and the next steps ask them.</p>
                            <div id="npLots"></div>
                            <button type="button" class="np-nolot" id="npNoLot" hidden>Use no lot, and answer the steps by hand</button>
                            <p class="np-wq np-wq2">What you plant</p>
                            <p class="np-sub">Too thin a stand cannot reach the goal. NPK Plus counts for what it can.</p>
                            @endif
                            <label class="np-label" for="npSeed">Seeds or planting material <span>(optional)</span></label>
                            <div class="np-row"><input type="number" id="npSeed" class="form-input flex-1" min="0" step="any" inputmode="decimal" placeholder="Like 40">
                                <button type="button" class="np-utag is-inline" id="npSeedUnit" aria-label="What you plant"><span>kg</span>{!! $chev !!}</button></div>
                            <span class="np-hint" id="npSeedHint">How much you plant on the whole area.</span>
                            <label class="np-label" for="npGerm">Germination <span>(optional, on the seed tag)</span></label>
                            <div class="np-row" style="align-items:center"><input type="number" id="npGerm" class="form-input flex-1" min="1" max="100" step="1" inputmode="numeric" placeholder="Like 85">
                                <span class="np-hint" style="margin:0;flex:none;font-weight:800">%</span></div>
                            <span class="np-hint" id="npGermHint">Seed that does not sprout grows nothing: NPK Plus counts only the seed that will.</span>
                            <div class="np-lq" data-lq="area"><div>
                                <label class="np-label" for="npArea">Area</label>
                                <div class="np-row"><input type="number" id="npArea" class="form-input flex-1" min="0" step="any" value="1" inputmode="decimal">
                                    <button type="button" class="np-utag is-inline" id="npAreaUnit" aria-label="Area unit"><span>ha</span>{!! $chev !!}</button></div>
                            </div></div>
                            <div class="np-lotsay" data-lotsay="area" hidden></div>
                        </section>
                        <section class="np-wstep" data-s="3" hidden>
                            <p class="np-wq" id="npSeasonQ">Where, when and the water</p>
                            <p class="np-sub">The sun, the rain and the heat of your season come from twenty years of weather at your field, tilted by ENSO now.</p>
                            <div class="np-lotsay" data-lotsay="season" hidden></div>
                            <div class="np-lq" data-lq="place"><div>
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
                            </div></div>
                            <span class="np-label">Planting date</span>
                            @include('partials.date-tag', ['id' => 'npPlanted', 'empty' => 'Pick the planting date', 'class' => 'np-date'])
                            <div class="np-lq" data-lq="water"><div>
                                <span class="np-label">Water</span>
                                <button type="button" class="np-utag is-wide" id="npWaterBtn"><span>Irrigated</span>{!! $chev !!}</button>
                            </div></div>
                            <div class="np-season" id="npSeason">Pick the place and the planting date to read the season.</div>
                        </section>
                        <section class="np-wstep" data-s="4" hidden>
                            <p class="np-wq">Your soil</p>
                            <p class="np-sub">It decides how much the soil gives on its own and how much of the fertilizer the crop can catch.</p>
                            {{-- The field in detail (2026-10-15, the owner): the shared field
                                 questions (App\Support\FieldProfile), optional. They fill the soil
                                 and the water below and in step 4, and the calculator counts the
                                 rest (the last crop, the organic matter, the drainage, salt, zinc)
                                 as plain lines under the need. Anee reads them too. --}}
                            <div class="np-lotsay" data-lotsay="soil" hidden></div>
                            {{-- A lot's answers ARE the field in detail: with a lot picked the card
                                 folds away and carries the lot's answers, so the need counts them the same. --}}
                            <div class="np-lq" data-lq="fpc"><div>
                            <div class="np-fpc">
                                <span class="np-label np-fpc-l">Your field in detail <span>(optional)</span></span>
                                <p class="np-sub np-fpc-sub">The soil, the water and last season, one short page at a time. NPK Plus counts every answer, and shows how.</p>
                                <div id="npFpc"></div>
                                @include('partials.field-profile-card')
                            </div>
                            </div></div>
                            <div class="np-lq is-off" id="npAreaWrap"><div>
                                <div class="np-area" id="npAreaCard">
                                    <p class="np-area-h">Typical for your area</p>
                                    <p class="np-area-t" id="npAreaT"></p>
                                    <ul class="np-area-l" id="npAreaL"></ul>
                                    <p class="np-area-say" id="npAreaSay" hidden></p>
                                    <div class="np-area-b">
                                        <button type="button" class="np-area-use" id="npAreaUse">Use these for what I left blank</button>
                                        <button type="button" class="np-area-no" id="npAreaNo">Not now</button>
                                    </div>
                                </div>
                            </div></div>
                            <div class="np-lq" data-lq="texture"><div>
                                <span class="np-label">Soil type</span>
                                <button type="button" class="np-utag is-wide is-none" id="npTexture"><span>Not sure</span>{!! $chev !!}</button>
                            </div></div>
                            <div class="np-lq" data-lq="ph"><div>
                                <label class="np-label" for="npPh">Soil pH <span>(if you know it)</span></label>
                                <input type="number" id="npPh" class="form-input w-full" min="2" max="12" step="0.1" inputmode="decimal" placeholder="Like 5.8">
                            </div></div>
                            <div class="np-lq" data-lq="conds"><div>
                                <span class="np-label">What else is true of it <span>(optional)</span></span>
                                <button type="button" class="np-utag is-wide is-none" id="npConds"><span>None picked</span>{!! $chev !!}</button>
                                <span class="np-hint">Acidic, alkaline, sodic, salty, acid sulfate, low in organic matter, peat or waterlogged.</span>
                            </div></div>
                            {{-- 2026-10-19: two answers that move zinc and sulfur. --}}
                            <div class="np-lq" data-lq="habits"><div>
                                <span class="np-label">How the field is farmed <span>(optional)</span></span>
                                <label class="np-switch mt-1"><input type="checkbox" id="npIntensive"> <span><b>Two or three crops a year</b><br><span class="text-xs text-gray-500 font-normal">A field cropped that often runs short of zinc and sulfur sooner.</span></span></label>
                                <label class="np-switch mt-2"><input type="checkbox" id="npZincRecent"> <span><b>Zinc went on in the last two seasons</b><br><span class="text-xs text-gray-500 font-normal">A dose of zinc sulfate keeps working for two or three crops.</span></span></label>
                            </div></div>
                            <div class="np-testbox">
                                <label class="np-switch"><input type="checkbox" id="npSoilOn"> <span><b>I have a soil test</b><br><span class="text-xs text-gray-500 font-normal">It sharpens the need for nitrogen, phosphorus, potassium, sulfur, zinc, boron, iron, manganese and copper.</span></span></label>
                                <div class="np-soil" id="npSoil"><div>
                                    <div class="np-cgrid" id="npSoilGrid"></div>
                                    <div class="np-soilu">
                                        <label>P method <button type="button" class="np-utag" id="npPMethod"><span>Olsen</span>{!! $chev !!}</button></label>
                                        <label>K in <button type="button" class="np-utag" id="npKUnit"><span>ppm</span>{!! $chev !!}</button></label>
                                    </div>
                                    <p class="np-hint" id="npKitSay" hidden></p>
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
                        <button type="button" class="btn btn-white" id="npBack">Back</button>
                        <small id="npStepOf">Step 1 of 6</small>
                        <button type="button" class="btn btn-primary" id="npNext">Next</button>
                    </div>
                </div>
            </div>

            <div class="np-out">
                <div class="np-card" id="npOut">
                    <h3><i>✓</i>What your plan gives</h3>
                    <p class="np-sub" id="npOutSub">Add a fertilizer to see the totals.</p>
                    {{-- Leave the micronutrients out (2026-10-21, the owner): the Micros tab and the barrel then count N, P and K only. --}}
                    <label class="np-switch np-microsw"><input type="checkbox" id="npMicrosOn" checked> <span><b>Count the micronutrients</b><br><span class="text-xs text-gray-500 font-normal">Calcium, magnesium, sulfur, zinc, boron and the rest, in the Micros tab and the barrel. Off: nitrogen, phosphorus and potassium only.</span></span></label>
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
                        <button type="button" class="np-anee" id="npAnee"><img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt=""><span id="npAneeSays">Analyze further with Anee</span></button>
                    </div>
                    {{-- The owner's rule (2026-10-15): the calculator never uses credits; only Anee's reading does, when it is ready. --}}
                    <p class="np-free" id="npFree"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg><span>The calculator uses no credits. Credits are used only when you ask Anee to look further.</span></p>
                </div>
                <div class="np-rep" id="npRep" hidden></div>
            </div>
        </div>
    </div>

    @if (request()->query('frame') === 'auto')
    {{-- Inside Anee Auto (2026-10-12): the plan goes back to the season's lot. --}}
    <div class="np-frame-bar" id="npFrameBar">
        <span id="npFrameSay">Add the fertilizers you plan to use on this lot.</span>
        <button type="button" class="btn btn-primary" id="npFrameUse" disabled>Use this plan</button>
    </div>
    <style>
        .np-frame-bar { position: sticky; bottom: 0; z-index: 30; display: flex; align-items: center; gap: .7rem; margin: 1rem -1rem 0; padding: .75rem 1rem calc(.75rem + env(safe-area-inset-bottom));
            background: rgb(255 255 255 / .96); backdrop-filter: blur(8px); border-top: 1px solid var(--color-gray-200); box-shadow: 0 -12px 28px -20px rgb(0 0 0 / .45); }
        .np-frame-bar span { flex: 1; min-width: 0; font-size: .82rem; font-weight: 700; color: var(--color-gray-600); }
        .np-frame-bar .btn { flex: none; }
        html.dark .np-frame-bar { background: rgb(22 28 18 / .96); border-color: #2b3a1c; }
        html.is-frame .np-hero, html.is-frame .np-tabs, html.is-frame #npSave, html.is-frame #npAnee, html.is-frame #npFree { display: none !important; }
    </style>
    @endif

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
        {{-- Two shelves (2026-10-12): the common grades, and the real products
             on Philippine shelves (FPA registered, updated monthly). --}}
        <div class="np-ptabs" role="tablist">
            <button type="button" class="np-ptab is-on" role="tab" aria-selected="true" data-pt="common">Common products</button>
            <button type="button" class="np-ptab" role="tab" aria-selected="false" data-pt="ph">Philippine products</button>
        </div>
        <div id="npPhPane" hidden>
            <div class="crop-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                <input type="text" id="npPhQ" class="form-input" autocomplete="off" placeholder="Brand, product or grade, like Yara or 16-20-0">
            </div>
            <button type="button" class="np-utag np-catbtn" id="npPhCatBtn" aria-haspopup="dialog"><span>Showing: <b>All</b></span>{!! $chev !!}</button>
            <div class="np-cats" id="npPhCats"></div>
            <div id="npPhList"></div>
            <p class="np-fine mt-2" id="npPhNote">From the Fertilizer and Pesticide Authority's list of registered fertilizers, updated every month. Brands are shown so you can find them; NPK Plus does not recommend one over another.</p>
        </div>
        <div id="npCommonPane">
        <div class="crop-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" id="npProdQ" class="form-input" autocomplete="off" placeholder="Find a product, like urea or 14-14-14">
            <button type="button" class="crop-search-x hidden" id="npProdQX" aria-label="Clear">✕</button>
        </div>
        <button type="button" class="np-utag np-catbtn" id="npCatBtn" aria-haspopup="dialog"><span>Showing: <b>All</b></span>{!! $chev !!}</button>
        <div class="np-cats" id="npCats"></div>
        <div id="npProdList"></div>
        </div>
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
        {{-- The pack it is sold in: a bag, a sachet or a bottle (2026-10-12). --}}
        <label class="np-label" for="npCPack">One pack holds <span>(optional)</span></label>
        <div class="np-cpack">
            <input type="number" id="npCPack" class="form-input" min="0" step="any" inputmode="decimal" placeholder="50">
            <div class="np-pills" id="npCPackU"></div>
        </div>
        <span class="np-label" id="npCPctLab">Nutrients, percent by weight</span>
        <div class="np-cgrid" id="npCGrid"></div>
        <p class="np-sub" id="npCPctSub">As printed on the bag. Leave the rest empty.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-primary w-full" id="npCSave">Save and add</button>
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
        <p>Each nutrient plank is how much of that nutrient's need for your goal your plan covers: the same number the NPK and Micros tabs show, so a nutrient they call short is a short plank here. A plank taller than the rim is a nutrient given more than the crop needs; it holds no more water. Sun, water and temperature are how much of the crop's need the season meets, read from twenty years of weather at your field.</p>
        <p>The soil gives part of a missing nutrient on its own, so the harvest falls less than a short plank shows. NPK Plus says that harvest apart, under the barrel. What the plan leaves out is taken from the soil, and the next season starts poorer.</p>
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
        <div class="np-lq" data-lq="loc"><div>
            <label class="np-label" for="npLoc">Where is the field?</label>
            <input type="text" id="npLoc" class="form-input w-full" maxlength="160" placeholder="Town and province">
        </div></div>
        <div class="np-lotsay" data-lotsay="anee" hidden></div>
        {{-- The field in detail lives in step 5 (the calculator counts it); it goes to Anee whole. --}}
        <div class="np-aneefield" id="npAneeField" hidden></div>
        <span class="np-label">How will you apply it?</span>
        <div class="np-pills" id="npTiming"></div>
        <label class="np-label" for="npNotes">Anything else <span>(optional)</span></label>
        <textarea id="npNotes" class="form-textarea w-full" rows="2" maxlength="800" placeholder="Like: the field floods in August"></textarea>
        <button type="button" class="np-anee w-full mt-4" id="npRun"><span id="npRunSays">Ask Anee</span></button>
        <p class="np-fine text-center" id="npRunFine"></p>
    </div>
</div>
@include('sm.partials.anee-wait')
@unless ($npFrame)
    @include('partials.farm-lots')
@endunless
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
        phProducts: @json(route('npk.ph')), phUse: @json(route('npk.ph.use')),
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
        liming: 'the lime in the plan locks it up', excess_zn: 'heavy zinc in the plan crowds it out',
        high_rainfall: 'heavy rain over the season washes it out', drought: 'a dry season', intensive_cropping: 'two or three crops a year',
        upland: 'dry seeded rice on soil that is not flooded' };
    // The usual germination the seeding rates in the guides assume.
    const GERM_USUAL = 85;

    let OPT = null, M = null, calcId = null, RES = null;
    const st = { microsOn: true, crop: null, cropPicked: false, lines: [], areaUnit: 'ha', yUnit: 't', seedUnit: 'kg', seedUnitSet: false, pMethod: 'olsen', kUnit: 'ppm', form: 'granular',
        texture: 'unsure', conds: [], step: 0, prov: null, town: null, water: 'irrigated', season: null, varPick: null, areaSoil: null, area: null, areaNo: false };
    // The field in detail (partials/field-profile-card) and what the farmer set by hand (theirs wins over what it fills).
    let FPC = null;
    const TOUCH = {};
    // The farm's lot this calculation is for (partials/farm-lots), and its picker: none inside Anee Auto.
    let LOT = null, LOTP = null;
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
        if (n !== st.step) window.aneeStepOut?.([...document.querySelectorAll('.np-wstep')].find((s) => !s.hidden));
        document.querySelectorAll('.np-wstep').forEach((s, i) => {
            s.hidden = i !== n;
            s.classList.remove('go-next', 'go-back');
            if (i === n && !still()) { void s.offsetWidth; s.classList.add(cls); }
        });
        document.querySelectorAll('.np-ws').forEach((b, i) => { b.classList.toggle('is-on', i === n); b.classList.toggle('is-done', i < n); b.setAttribute('aria-current', i === n ? 'step' : 'false'); });
        $('npWLine').style.setProperty('--p', (n / (STEPS - 1) * 100) + '%');
        $('npBack').disabled = false;   // on the first page it goes back where they came from
        $('npNext').textContent = n === STEPS - 1 ? 'See results' : 'Next';
        $('npStepOf').textContent = 'Step ' + (n + 1) + ' of ' + STEPS;
        st.step = n;
        if ($('npWiz').getBoundingClientRect().top < 70) $('npWiz').scrollIntoView({ behavior: 'auto', block: 'start' });
    };
    $('npBack').addEventListener('click', () => (st.step === 0 ? window.aneeLeave() : go(st.step - 1)));
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
    });
    const setCrop = (k) => {
        // A variety picked from another crop's records does not belong to this one.
        if (st.varPick && st.varPick.source === 'record' && st.varPick.crop && st.varPick.crop !== k && typeof varSet === 'function') { st.crop = k; varSet(null, ''); }
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
    // What a yield weighs (NpkModel::YIELD_BASIS, 2026-10-20, the owner: dry or wet, and the other beside it).
    // A crop sold dried is counted dry, with its weight at harvest; any other crop fresh, as picked.
    const basisOf = (c) => {
        const b = c && M && M.yieldBasis ? M.yieldBasis[c.key] : null;
        if (!b) return null;
        const lo = b.mc ? (100 - b.mc[0]) / (100 - b.mc[1]) : b.x[0], hi = b.mc ? lo : b.x[1];
        return { dry: b.dry + (b.mc ? ' at ' + b.mc[0] + '% moisture' : ''), wet: b.wet + (b.mc ? ', about ' + b.mc[1] + '% moisture' : ''), lo, hi };
    };
    const tPer = (t) => fmt(t / Y_U[st.yUnit][3]) + '\u00a0' + Y_U[st.yUnit][1];
    const wetPer = (B, t) => B.hi - B.lo < 0.005 ? tPer(t * B.lo) : fmt(t * B.lo / Y_U[st.yUnit][3]) + ' to ' + tPer(t * B.hi);
    // " of dry palay at 14% moisture (about 6.8 t/ha of wet palay at harvest, about 24% moisture)", or ", fresh as picked".
    const basisSay = (c, t) => { const B = basisOf(c); return B ? ' of ' + B.dry + ' (about ' + wetPer(B, t) + ' of ' + B.wet + ')' : ', fresh as picked'; };
    const goalOf = (c) => {
        const f = Y_U[st.yUnit][3];
        const P = num('npPotential') * f, T = num('npTarget') * f, ty = c && c.typicalYield;
        let Y = null, why = '', warn = '';
        if (T) {
            // The target as set (2026-10-21, the owner: a record is not a ceiling); NpkNeed::goal is the twin.
            Y = T;
            why = 'your goal';
            if (P && T > P) warn = 'Above the variety\'s best on record (' + fmt(P) + ' t a hectare): it is counted as you set it, but it takes a full stand, heavy grain and everything going right.';
            else if (P && T > 0.8 * P) warn = 'Above 80% of the potential, each extra ton takes more fertilizer and more luck with the weather.';
        } else if (P) { Y = 0.8 * P; why = '80% of the variety\'s potential, the most a well run field usually reaches'; }
        else if (ty) { Y = ty.hi; why = 'the Philippine average, since no goal or potential was given'; }
        const sd = seedCheck(c);
        // Your field (NpkModel::FIELD caps): rain only, short water, salt or sodium hold the harvest
        // below the goal, and the need with it. A goal the farmer typed stays theirs.
        const cap = fieldCap(c), capped = !T && cap.k < 1;
        return { Y, Yeff: Y ? Y * sd.sf * (capped ? cap.k : 1) : null, why, warn, P, T, sd, cap, capped };
    };
    const hints = () => {
        if (!M) return;
        const c = cropOf(st.crop), g = goalOf(c), unit = Y_U[st.yUnit][1], f = Y_U[st.yUnit][3];
        $('npGoal').innerHTML = !c ? 'Pick the crop first; the goal is sized to it.'
            : g.Y ? 'NPK Plus counts for <b>' + fmt(g.Y / f) + ' ' + esc(unit) + '</b>' + (st.yUnit !== 't' ? ' (' + fmt(g.Y) + ' t/ha)' : '') + esc(basisSay(c, g.Y)) + ': ' + esc(g.why) + '.'
                + (g.sd.sf < 1 ? ' Your stand is thin, so it counts for ' + fmt(g.Yeff / f) + ' ' + esc(unit) + '.' : '')
                + (g.capped ? ' ' + esc(fieldWho()) + ' holds it to <b>' + fmt(g.Yeff / f) + ' ' + esc(unit) + '</b> (' + esc(capWords(g.cap)) + ').' : '')
                + (g.warn ? '<br><span class="text-xs">' + esc(g.warn) + '</span>' : '')
            : 'No yield numbers for this crop: give your goal, or the guide rate is used as it is.';
        const s = num('npSeed'), a = areaHa(), sd = c && M.seeding[c.key];
        $('npSeedHint').textContent = s && a ? 'For the whole area: ' + fmt(g.sd.raw * (st.seedUnit === 'g' ? 1000 : 1), g.sd.raw >= 100 ? 0 : 1) + ' ' + SEED_U[st.seedUnit][1] + ' per hectare. ' + g.sd.words
            : 'How much you plant on the whole area.' + (sd && sd.note ? ' ' + sd.note : '');
        $('npSeedHint').classList.toggle('is-warn', !!g.sd.warn);
    };
    $('npYUnit').addEventListener('click', () => pick('Yields in', 'For the potential and your goal.', Y_U, st.yUnit, (k) => { st.yUnit = k; tagSay('npYUnit', Y_U, k); potFill(false); potPaint(); hints(); calc(); }));

    /* ---- the variety, two ways (2026-10-15, the owner) ----
       Picked from the records (the shared sheet, partials/variety-pick,
       a plain lookup): its trial yield fills the potential, which stays the
       farmer's to change (a hybrid's own figure can differ from the trial's).
       Or typed as sold, not on record. st.varPick keeps which. */
    const OK_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    const recT = (p) => (p && p.source === 'record' ? (p.top || p.avg || null) : null);
    const varPaintTag = () => {
        const p = st.varPick, name = $('npVariety').value.trim();
        $('npVarSays').textContent = name || 'Pick from the records, or type your own';
        $('npVarBtn').classList.toggle('is-none', !name);
        $('npVarBtn').classList.toggle('is-rec', !!(p && p.source === 'record'));
        const h = $('npVarHint');
        h.hidden = !name;
        h.textContent = !name ? '' : (p && p.source === 'record'
            ? 'On record: ' + [p.type === 'hybrid' ? 'hybrid' : 'not a hybrid', p.days ? Math.round(p.days) + ' days' : '', p.year ? 'released ' + p.year : ''].filter(Boolean).join(', ') + '.'
            : 'Your own name, not on record.');
    };
    // The potential from the record, in the yield unit on screen: only while the farmer has not set their own.
    const potFill = (force) => {
        const t = recT(st.varPick);
        if (!t || (TOUCH.potential && !force && num('npPotential'))) return;
        $('npPotential').value = String(Math.round(t / Y_U[st.yUnit][3] * 100) / 100);
        TOUCH.potential = false;
    };
    const potOwn = () => { const t = recT(st.varPick); return !!(t && TOUCH.potential && num('npPotential') && Math.abs(num('npPotential') * Y_U[st.yUnit][3] - t) > 0.05); };
    const potPaint = () => {
        const p = st.varPick, t = recT(p), box = $('npPotFrom');
        $('npPotHint').hidden = !!t;
        if (!t) { box.hidden = true; return; }
        const rec = fmt(t / Y_U[st.yUnit][3], 2) + ' ' + Y_U[st.yUnit][1], word = p.top ? 'its best trial yield' : 'its average trial yield';
        const own = potOwn();
        box.classList.toggle('is-own', own);
        box.innerHTML = OK_ICON + (own
            ? '<span>Your own figure is used. The record says ' + esc(rec) + ', ' + word + '. <button type="button" data-pot-rec>Use the record\'s</button></span>'
            : '<span>From the record: <b>' + esc(rec) + '</b>, ' + word + '. Change it if your seed bag or seller says otherwise.</span>');
        box.hidden = false;
    };
    $('npPotential').addEventListener('input', () => { TOUCH.potential = true; potPaint(); });
    $('npPotFrom').addEventListener('click', (e) => { if (e.target.closest('[data-pot-rec]')) { potFill(true); potPaint(); hints(); calc(); } });
    const varSet = (pick, name) => {
        const wasRec = recT(st.varPick) && !potOwn();
        st.varPick = pick;
        $('npVariety').value = name || '';
        // A record's potential does not belong to another variety.
        if (wasRec && !recT(pick)) { $('npPotential').value = ''; TOUCH.potential = false; }
        if (recT(pick)) potFill(!num('npPotential'));
        varPaintTag(); potPaint(); hints(); calc();
    };
    // The shared variety sheet (partials/variety-pick): the records, or a name typed as sold.
    $('npVarBtn').addEventListener('click', async () => {
        if (!ready() || !window.aneeVarietyPick) return;
        const c = cropOf(st.crop), [town, prov] = placeWords();
        const p = st.varPick;
        const res = await window.aneeVarietyPick.open({ crop: st.crop || '', cropLabel: c ? cropShort(c) : '', location: [town, prov].filter(Boolean).join(', '),
            water: st.water === 'rainfed' ? 'rainfed' : st.water === 'partial' ? 'limited' : '',
            value: { name: $('npVariety').value.trim(), record: p && p.source === 'record' ? p : null },
            ownNote: 'Not on record, so NPK Plus has no trial yield for it. Type its potential on this step if the seed bag or the seller gives one.' });
        if (!res) return;
        if (!res.name) { varSet(null, ''); return; }
        varSet(res.record ? Object.assign({ source: 'record', crop: st.crop }, res.record) : { source: 'custom', name: res.name }, res.name);
    });
    $('npSeedUnit').addEventListener('click', () => pick('What you plant', 'For the whole area.', SEED_U, st.seedUnit, (k) => { st.seedUnit = k; st.seedUnitSet = true; tagSay('npSeedUnit', SEED_U, k); hints(); calc(); }));
    $('npAreaUnit').addEventListener('click', () => pick('The area in', '', AREA_U, st.areaUnit, (k) => { st.areaUnit = k; tagSay('npAreaUnit', AREA_U, k); hints(); calc(); }));
    ['npArea', 'npPotential', 'npTarget', 'npSeed', 'npGerm', 'npVariety', 'npPh'].forEach((id) => $(id).addEventListener('input', () => { hints(); calc(); }));

    /* ---- step 4: the place, the planting date, the water, and the season ---- */
    const WATER_U = { irrigated: ['Irrigated', 'Irrigated', 'A canal or a system that runs all season'], pump: ['Pump or well', 'Pump or well', 'You pump water whenever the crop needs it, even in a dry El Nino season'], partial: ['Partly irrigated', 'Partly irrigated', 'Water some of the time, rain the rest'], rainfed: ['Rainfed only', 'Rainfed only', 'Only the rain'] };
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
    $('npWaterBtn').addEventListener('click', () => pick('Where the water comes from', '', WATER_U, st.water, (k) => { st.water = k; TOUCH.water = true; tagWide('npWaterBtn', WATER_U[k][0], false); calc(); }));
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
        $('npSeason').innerHTML = 'Reading twenty years of weather at ' + esc(town ? town + ', ' + prov : prov) + '…';
        try {
            const r = await window.api(U.season + '?' + new URLSearchParams({ town, province: prov, from: $('npPlanted').value || '', days: seasonDays(c),
                crop: st.crop || '', barangay: (LOT && LOT.barangay) || '' }).toString());
            if (ask !== seasonAsk) return;
            st.season = r.data;
            areaGot(r.data && r.data.area);
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
        // A pump makes up most of what the rain leaves short (FIELD pumpCover), a part time canal half (NpkNeed::seasonWater).
        const cover = st.water === 'pump' ? M.field.pumpCover : st.water === 'partial' ? M.field.partialCover : 0;
        const supply = st.water === 'irrigated' ? need : eff + cover * Math.max(0, need - eff);
        const wr = Math.min(1, supply / Math.max(1, need)), water = clamp(1 - K.ky * (1 - wr), 0.1, 1);
        const driest = S.years.reduce((m, y) => Math.min(m, y.rain), Infinity) * t.rain * 0.75;
        const wrDry = Math.min(1, (st.water === 'irrigated' ? need : driest + cover * Math.max(0, need - driest)) / Math.max(1, need));
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
    $('npTexture').addEventListener('click', () => ready() && pick('Soil type', 'Rub a moist pinch between your fingers.', M.textures, st.texture, (k) => { st.texture = k; TOUCH.texture = true; soilTag(); calc(); }));
    $('npConds').addEventListener('click', () => ready() && pick('What else is true of your soil', 'Pick all that fit. Leave it empty if you are not sure.', M.conditions, st.conds, (sel) => { st.conds = sel; TOUCH.conds = true; soilTag(); calc(); }, true));
    $('npPh').addEventListener('input', () => { TOUCH.ph = true; });
    const SOIL = [['om', 'Organic matter %'], ['n', 'Total N %'], ['p', 'P ppm'], ['k', 'K'], ['zn', 'Zn ppm'], ['b', 'B ppm'], ['s', 'S ppm'], ['fe', 'Fe ppm'], ['mn', 'Mn ppm'], ['cu', 'Cu ppm']];
    $('npSoilGrid').innerHTML = SOIL.map(([k, l]) => '<label>' + esc(l) + '<input type="number" class="form-input" step="any" min="0" inputmode="decimal" data-s="' + k + '"></label>').join('');
    $('npSoilOn').addEventListener('change', () => { TOUCH.soilTest = true; $('npSoil').classList.toggle('is-on', $('npSoilOn').checked); calc(); });
    $('npIntensive').addEventListener('change', () => { st.intensive = $('npIntensive').checked; TOUCH.habits = true; calc(); });
    $('npZincRecent').addEventListener('change', () => { st.zincRecent = $('npZincRecent').checked; TOUCH.habits = true; calc(); });
    $('npSoil').addEventListener('input', () => { TOUCH.soilTest = true; calc(); });
    /* A soil test kit's levels (2026-10-16): a lot's log can hold a kit result, read
       as low, medium or high. They stand for a nutrient no number was typed for. */
    const kitSay = (from) => {
        const el = $('npKitSay'), k = st.kit || {};
        const lv = [['N', 'N'], ['P2O5', 'P'], ['K2O', 'K']].filter(([x]) => k[x]).map(([x, l]) => l + ' ' + k[x]);
        if (!from && !lv.length) { el.hidden = true; return; }
        el.textContent = (from ? 'From ' + from + '.' : '') + (lv.length ? ' Soil test kit levels: ' + lv.join(', ') + '.' : '');
        el.hidden = false;
    };
    $('npPMethod').addEventListener('click', () => pick('How phosphorus was measured', 'It is printed on the soil test result.', P_METHOD, st.pMethod, (k) => { st.pMethod = k; tagSay('npPMethod', P_METHOD, k); calc(); }));
    $('npKUnit').addEventListener('click', () => pick('Potassium is given in', 'It is printed beside the number.', K_UNIT, st.kUnit, (k) => { st.kUnit = k; tagSay('npKUnit', K_UNIT, k); calc(); }));
    const phVal = () => { const v = Number($('npPh').value); return $('npPh').value !== '' && v >= 2 && v <= 12 ? v : null; };
    /* The area's typical soil (2026-10-18, the soil atlas): offered for the
       answers left blank, never applied by itself (no auto-advance), and
       read last: a soil test number, then a kit level, then the area. The
       Crop Protocol fills the same unknowns the same way, so a lot with no
       soil test reaches the same need in both. */
    const TEX_W = { clay: 'clay', loam: 'loam', sandy: 'sandy' };
    const areaGot = (A) => {
        st.areaSoil = A || null;
        if (st.area && A) st.area.tests = Object.assign({}, A.tests || {});
        if (st.area && !A) areaStop(true);
        paintArea();
    };
    const paintArea = () => {
        const A = st.areaSoil, wrap = $('npAreaWrap');
        if (!A || st.areaNo) { wrap.classList.add('is-off'); return; }
        $('npAreaT').textContent = A.where + (A.samples ? ': what ' + A.samples + ' soil samples there usually show' + (A.year ? ' (' + A.year + ')' : '') : '') + '. Not a test of your field.';
        $('npAreaL').innerHTML = (A.lines || []).filter((l) => !/^Read for /.test(l)).slice(0, 5).map((l) => '<li>' + esc(l) + '</li>').join('');
        $('npAreaCard').classList.toggle('is-on', !!st.area);
        const say = $('npAreaSay');
        if (st.area) {
            const used = [];
            const t = st.area.tests || {};
            if (Object.keys(t).length) used.push('the N, P and K levels');
            if (st.area.filled && st.area.filled.texture) used.push('the soil type');
            if (st.area.filled && st.area.filled.ph) used.push('the pH');
            if (st.area.filled && st.area.filled.conds) used.push(st.area.filled.conds.map((k) => ({ saline: 'the salt', low_om: 'the low organic matter' })[k]).join(' and '));
            say.textContent = 'Using what is typical for your area for ' + (used.join(', ') || 'what you left blank') + '. Your own answers come first.';
            say.hidden = false;
            $('npAreaNo').textContent = 'Stop using them';
        } else { say.hidden = true; $('npAreaNo').textContent = 'Not now'; }
        wrap.classList.remove('is-off');
    };
    const areaUse = () => {
        const A = st.areaSoil;
        if (!A) return;
        const filled = {};
        if (st.texture === 'unsure' && TEX_W[A.texture] && M.textures[A.texture]) { st.texture = A.texture; filled.texture = A.texture; }
        if ($('npPh').value === '' && A.ph != null) { $('npPh').value = String(A.ph); filled.ph = A.ph; }
        // The area's salt and low organic matter, only when no condition was picked (SoilAtlas::fill's rule).
        if (!st.conds.length) {
            const add = (A.conditions || []).filter((k) => (k === 'saline' || k === 'low_om') && M.conditions[k]);
            if (add.length) { st.conds = add.slice(); filled.conds = add.slice(); }
        }
        st.area = { where: A.where, tests: Object.assign({}, A.tests || {}), filled };
        soilTag(); paintArea(); calc();
    };
    const areaStop = (quiet) => {
        const f = (st.area && st.area.filled) || {};
        if (f.texture && st.texture === f.texture) st.texture = 'unsure';
        if (f.ph != null && Number($('npPh').value) === Number(f.ph)) $('npPh').value = '';
        if (f.conds && st.conds.join(',') === f.conds.join(',')) st.conds = [];
        st.area = null;
        if (!quiet) { soilTag(); paintArea(); calc(); }
    };
    $('npAreaUse').addEventListener('click', areaUse);
    $('npAreaNo').addEventListener('click', () => { if (st.area) areaStop(); else { st.areaNo = true; paintArea(); } });
    const soil = (withArea = true) => {
        const s = {};
        if ($('npSoilOn').checked) $('npSoilGrid').querySelectorAll('input').forEach((i) => { if (i.value !== '') s[i.dataset.s] = Number(i.value); });
        const ph = phVal(); if (ph != null) s.ph = ph;
        if ($('npSoilOn').checked && st.kit && Object.keys(st.kit).length) s.levels = Object.assign({}, st.kit);
        if (withArea && st.area && st.area.tests && Object.keys(st.area.tests).length) s.levels = Object.assign({}, st.area.tests, s.levels || {});
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
        if (s.om != null) { out.N = s.om < 2 ? 'low' : s.om <= 4 ? 'medium' : 'high'; if (s.om < 2) out.OM = 'low'; }
        else if (s.n != null) out.N = s.n < 0.1 ? 'low' : s.n <= 0.2 ? 'medium' : 'high';
        ['Zn', 'B', 'S', 'Fe', 'Mn', 'Cu'].forEach((el) => {
            const v = s[el.toLowerCase()], crit = (M.elements[el] || {}).critical;
            if (v != null && crit) out[el] = v < crit ? 'low' : 'ok';
        });
        // A kit's level stands for a nutrient with no number.
        if (s.levels) ['N', 'P2O5', 'K2O'].forEach((k) => { if (!out[k] && ['low', 'medium', 'high'].includes(s.levels[k])) out[k] = s.levels[k]; });
        return out;
    };
    // What is true of this field, as the model's factors.
    const factors = (c, X = {}) => {
        const f = new Set();
        if (st.texture === 'sandy' || st.texture === 'clay') f.add(st.texture);
        const ph = phVal();
        if (ph != null) { if (ph < 5) f.add('strongAcid'); else if (ph < 5.5) f.add('acid'); else if (ph > 8) f.add('strongAlk'); else if (ph > 7.3) f.add('alkaline'); }
        else { if (st.conds.includes('acidic')) f.add('acid'); if (st.conds.includes('alkaline')) f.add('alkaline'); }
        ['sodic', 'saline', 'acid_sulfate', 'low_om', 'peat', 'waterlogged'].forEach((k) => { if (st.conds.includes(k)) f.add(k); });
        // A soil test's organic matter under 2% is low organic matter for the micros too (NpkNeed::run).
        if (soilClass(soil(false)).OM === 'low') f.add('low_om');
        // Strongly alkaline and NOT sodic reads as a limy soil, rich in calcium.
        // A sodic soil's high pH is sodium, which crowds the calcium off the clay.
        if (f.has('strongAlk') && !f.has('sodic')) f.add('calcareous');
        if (c && (c.key === 'rice' || c.key === 'rice_dsr_wet')) { f.add('flooded'); f.delete('waterlogged'); }
        // 2026-10-19 (NpkNeed::factors and moreFactors, the same rules): loam, a near neutral pH, dry seeded
        // rice; heavy rain or a dry season from the twenty years of weather; what the farmer tells.
        if (st.texture === 'loam') f.add('loam');
        if (ph != null ? (ph >= 6 && ph <= 7.3) : st.conds.includes('neutral')) f.add('neutral');
        if (c && c.key === 'rice_dsr_dry') f.add('upland');
        const SPx = X.SP;
        if (SPx && SPx.rain >= 1000 && st.texture !== 'clay' && !f.has('flooded')) f.add('high_rainfall');
        if (SPx && st.water !== 'irrigated' && SPx.wr < 0.7) f.add('drought');
        if (st.intensive) f.add('intensive_cropping');
        if (st.zincRecent) f.add('zinc_recent');
        if ((X.organicKg || 0) >= 1000) f.add('organic_inputs');
        return f;
    };

    /* ---- your field, counted (2026-10-15, the owner) ----
       The field in detail (step 5) as a layer on the model, never silent:
       its texture, pH, salt and water answers fill the simple fields above
       (so the soil factors count them), and what those cannot carry is
       counted here with NpkModel::FIELD's numbers: the goal the water, salt
       or sodium let the field carry (fieldCap, in goalOf), the nitrogen the
       organic matter or slow drainage adds or saves, the credits of a legume
       or of rice straw plowed back (fieldLayer, in needModel), a zinc shortage
       last season (the Micros). fieldLines says every one of them in words.
       Off, and the calculator is exactly as before, when no detail is given. */
    const FIELD = () => (M && M.field) || {};
    const fieldOn = () => !!(FPC && FPC.count());
    // Who gave the answers: the picked lot, or the field in detail of step 5.
    const fieldWho = () => (LOT ? LOT.name : 'Your field in detail');
    const isRiceCrop = (c) => !!(c && String(c.key).startsWith('rice'));
    const fieldCap = (c) => {
        const out = { k: 1, why: [] };
        if (!fieldOn() || !c) return out;
        const F = FIELD(), caps = F.caps || {}, kind = isRiceCrop(c) ? 'rice' : 'other';
        if (st.water === 'rainfed' && caps.rainfed) out.why.push(['rainfed', caps.rainfed[kind]]);
        else if (st.water === 'partial' && caps.partial) out.why.push(['partial', caps.partial[kind]]);
        if (st.conds.includes('saline') && caps.saline) out.why.push(['saline', caps.saline]);
        if (st.conds.includes('sodic') && caps.sodic) out.why.push(['sodic', caps.sodic]);
        out.k = Math.max(F.capFloor || 0.75, out.why.reduce((k, w) => k * w[1], 1));
        return out;
    };
    const CAP_WORD = { rainfed: 'rain only', partial: 'water that runs short at times', saline: 'salt in the soil', sodic: 'sodium in the soil' };
    const capWords = (cap) => list(cap.why.map((w) => CAP_WORD[w[0]] || w[0]));
    const fieldLayer = (c, f) => {
        const out = { on: fieldOn(), a: fieldOn() ? FPC.value() : {}, nMult: 1, credit: { N: 0, K2O: 0 }, zinc: false };
        if (!out.on || !c) return out;
        const F = FIELD(), a = out.a;
        if (a.organic === 'high' && !f.has('low_om')) out.nMult *= F.omHighN || 0.9;
        if (a.drainage === 'slow' && !f.has('flooded') && !f.has('waterlogged')) out.nMult *= F.slowN || 1.05;
        if (a.prevCrop === 'legume' && !c.legume) out.credit.N = F.legumeN || 25;
        if (a.prevCrop === 'rice_straw') out.credit.K2O = F.strawK || 25;
        out.zinc = (a.outbreaks || []).includes('other:zinc');
        return out;
    };
    // A factor's share, said plainly: "+15% N, +20% K₂O" (what needModel counted for it).
    const pctSay = (k, f, only) => {
        const a = M.adjust[k];
        if (!a) return '';
        return NPK.filter((n) => a[n] && (!only || only.includes(n)) && !(a.unless && a.unless[n] && f.has(a.unless[n])))
            .map((n) => (a[n] > 1 ? '+' : '−') + Math.round(Math.abs(a[n] - 1) * 100) + '% ' + lab(n)).join(', ');
    };
    const fieldLines = (c, g, f, md, fl) => {
        const L = [];
        if (!fl.on || !c) return L;
        const a = fl.a, F = FIELD(), ph = phVal(), phSay = ph != null ? ' (pH ' + fmt(ph) + ')' : '';
        const add = (i, lead, text, e, cls) => L.push({ i, lead, text, e: e || '', c: cls || (String(e || '').startsWith('+') ? 'is-up' : '') });
        const splits = (F.splits || {})[st.texture] || 2;
        // Texture.
        if (f.has('sandy')) add('🏖️', 'Sandy soil.', 'Nitrogen and potassium wash down, so more of each is counted. Split the nitrogen into ' + splits + ' doses or more, and the potassium into 2.', pctSay('sandy', f));
        else if (f.has('clay')) add('🧱', a.texture === 'heavy_clay' ? 'Heavy clay that cracks.' : 'Clay soil.', 'It holds potassium and nitrogen well, so a little less potassium is counted. The nitrogen can go in fewer, larger doses than on sandy soil.', pctSay('clay', f));
        // Acidity.
        const acidK = f.has('strongAcid') ? 'strongAcid' : f.has('acid') ? 'acid' : null, alkK = f.has('strongAlk') ? 'strongAlk' : f.has('alkaline') ? 'alkaline' : null;
        if (acidK) add('🍋', 'Acid soil' + phSay + '.', 'Phosphorus is locked up, so more of it is counted. Lime the field about a month before planting. A soil test says how much.', pctSay(acidK, f, ['P2O5']));
        if (alkK) add('🧂', 'Alkaline soil' + phSay + '.', 'Phosphorus is less available, and zinc and iron may run short (the Micros tab counts them). For nitrogen, ammonium sulfate suits this soil better than urea.', pctSay(alkK, f));
        if (f.has('acid_sulfate')) add('🟠', 'Acid sulfate soil.', 'Phosphorus is locked up and the soil sours when it dries. Keep it wet where you can, and lime it.', pctSay('acid_sulfate', f));
        // Organic matter.
        if (a.organic === 'high' && !f.has('low_om')) add('🌱', 'Rich in organic matter.', 'It gives some nitrogen of its own, so about ' + Math.round((1 - (F.omHighN || 0.9)) * 100) + '% less nitrogen is counted.', '−' + Math.round((1 - (F.omHighN || 0.9)) * 100) + '% N');
        else if (f.has('low_om')) add('🍂', 'Little organic matter.', 'It gives less nitrogen of its own, so more is counted. Plowing the straw back in helps over the seasons.', pctSay('low_om', f));
        // Last crop.
        if (md.credit && md.credit.N) add('🫘', 'Mungbean, peanut or soybean before.', 'They leave nitrogen in the soil, so ' + fmt(md.credit.N, 0) + ' kg less nitrogen per hectare is counted.', '−' + fmt(md.credit.N, 0) + ' kg N');
        else if (a.prevCrop === 'legume' && c.legume) add('🫘', 'A legume after a legume.', 'This crop makes its own nitrogen anyway, so no credit is taken. Rotating with a grain breaks the pests.', 'Tip', 'is-tip');
        if (md.credit && md.credit.K2O) add('🌾', 'Rice straw left on the field.', 'Plowed in, it gives back potassium, so ' + fmt(md.credit.K2O, 0) + ' kg less K₂O per hectare is counted. Plow it in early so it rots before planting.', '−' + fmt(md.credit.K2O, 0) + ' kg K₂O');
        else if (a.prevCrop === 'rice_no_straw') add('🔥', 'Straw burned or taken away.', 'No potassium comes back from it, so none is credited.', 'No credit', 'is-tip');
        // Water.
        const capOf = (k) => (g.cap && g.cap.why.find((w) => w[0] === k) || [])[1];
        const goalSay = (k) => g.T ? 'Your own goal is kept, but the field may not carry it.' : 'NPK Plus counts for ' + Math.round(k * 100) + '% of the goal, which lowers the need.';
        if (capOf('rainfed')) add('🌧️', 'Rain only.', 'The water, not the fertilizer, may set this harvest. ' + goalSay(capOf('rainfed')) + ' Split the nitrogen so a dry spell does not waste it.', g.T ? 'Tip' : 'Goal ' + Math.round(capOf('rainfed') * 100) + '%', g.T ? 'is-tip' : '');
        else if (capOf('partial')) add('🚿', 'Water runs short at times.', 'A dry spell can hold the harvest back. ' + goalSay(capOf('partial')), g.T ? 'Tip' : 'Goal ' + Math.round(capOf('partial') * 100) + '%', g.T ? 'is-tip' : '');
        if (f.has('flooded') && (a.drainage === 'stands' || a.drainage === 'slow')) add('💧', a.drainage === 'stands' ? 'Water stands for days.' : 'Slow to drain.', 'Put the nitrogen in small doses into shallow water, never onto a full flood, or much of it is lost.', 'Tip', 'is-tip');
        else if (f.has('waterlogged')) add('💧', 'Water stands for days.', 'Wet soil loses nitrogen, so more is counted. Split it into more doses and drain before each one.', pctSay('waterlogged', f));
        else if (a.drainage === 'slow' && !f.has('flooded')) add('💧', 'Slow to drain.', 'A little nitrogen is lost from wet soil, so about ' + Math.round(((F.slowN || 1.05) - 1) * 100) + '% more is counted. Split it into more doses.', '+' + Math.round(((F.slowN || 1.05) - 1) * 100) + '% N');
        if (a.flooding === 'often') add('🌊', 'The field floods most wet seasons.', 'Hold back the last nitrogen dose until the water goes down, or it washes away.', 'Tip', 'is-tip');
        // Salt and sodium.
        const capChip = (k, pct) => [!g.T && capOf(k) ? 'Goal ' + Math.round(capOf(k) * 100) + '%' : '', pct].filter(Boolean).join(', ');
        if (f.has('saline')) add('🧂', 'Salt in the soil.', (g.T ? 'Your own goal is kept, but salt may hold the harvest back.' : 'The harvest is held to about ' + Math.round((capOf('saline') || 1) * 100) + '% of the goal.') + ' Potassium helps the crop keep the salt out, so more is counted. Flush the field with good water before planting.', capChip('saline', pctSay('saline', f, ['K2O'])), 'is-up');
        if (f.has('sodic')) add('🧱', 'Sodium in the soil.', (g.T ? 'Your own goal is kept, but sodium may hold the harvest back.' : 'The harvest is held to about ' + Math.round((capOf('sodic') || 1) * 100) + '% of the goal.') + ' Sodium crowds out potassium and sends nitrogen into the air, so more of both is counted. Spread gypsum before planting to push the sodium out. A soil test says how much.', capChip('sodic', pctSay('sodic', f)), 'is-up');
        // Zinc.
        if (fl.zinc) add('🟤', 'Zinc ran short last season.', 'Zinc is counted as likely short (the Micros tab). Add zinc sulfate (the monohydrate, about 33% zinc) at land preparation, or a zinc foliar early in the season.', 'Zinc', 'is-up');
        return L;
    };
    const fieldBox = (L) => L.length ? '<div class="np-field"><h4><i aria-hidden="true">🌾</i>Your field, counted</h4><p>' + (LOT ? 'From the answers of ' + esc(LOT.name) + '.' : 'From your field in detail in step 5.') + ' Each line says what it changed and why.</p><div class="np-fl">'
        + L.map((l, k) => '<div class="np-fli" style="--k:' + k + '"><i aria-hidden="true">' + l.i + '</i><span><b>' + esc(l.lead) + '</b> ' + esc(l.text) + (l.e ? ' <em class="' + esc(l.c) + '">' + esc(l.e) + '</em>' : '') + '</span></div>').join('') + '</div></div>' : '';

    /* ---- step 5: the fertilizers ---- */
    const grade = (p) => p.local && /^\d/.test(p.local) ? p.local : (p.pct.N || p.pct.P2O5 || p.pct.K2O ? [p.pct.N || 0, p.pct.P2O5 || 0, p.pct.K2O || 0].join('-') : '');
    const badge = (p) => grade(p) || (Object.entries(p.pct)[0] ? Object.entries(p.pct)[0][0] + ' ' + Object.entries(p.pct)[0][1] + '%' : (p.estimate ? 'Bio' : ''));
    const comp = (p) => Object.entries(p.pct).map(([k, v]) => lab(k) + ' ' + v + '%').join(' · ') || (p.estimate ? 'About ' + Object.entries(p.estimate).map(([k, v]) => v + ' kg ' + lab(k)).join(', ') + ' per hectare dose (estimate)' : '');
    const prodOf = (id) => OPT.products.find((x) => String(x.id) === String(id));
    // Bags come in the product's own size and the other usual one (25 or 50 kg):
    // 'bag' is its own size, 'bag25' and 'bag50' the others (calcium nitrate is
    // sold both ways, 2026-10-12).
    const bagKgOf = (p, unit) => { const m = /^bag(\d+)$/.exec(unit || ''); return m ? Number(m[1]) : (p.bagKg || 50); };
    const isBag = (u) => /^bag(\d+)?$/.test(u || '');
    const unitList = (p) => {
        if (p.unit === 'dose') return [['dose', 'hectare doses']];
        // A liquid: by the bottle when its size is known, by the liter or the milliliter.
        if (p.unit === 'L') return [...(p.bagKg ? [['bag', (p.bagKg < 1 ? 'packs of ' + fmt(p.bagKg * 1000, 0) + ' mL' : 'packs of ' + fmt(p.bagKg) + ' L')]] : []), ['L', 'liters'], ['mL', 'milliliters']];
        // A small pack (a sachet or a 1 kg pack) is a pack, not a bag.
        if (p.bagKg && p.bagKg < 20) return [['bag', 'packs of ' + (p.bagKg < 1 ? fmt(p.bagKg * 1000, 0) + ' g' : fmt(p.bagKg) + ' kg')], ['kg', 'kilograms'], ['g', 'grams']];
        const own = p.bagKg || 50;
        return [['bag', 'bags of ' + fmt(own) + ' kg'], ...[25, 50].filter((k) => k !== own).map((k) => ['bag' + k, 'bags of ' + k + ' kg']), ['kg', 'kilograms'], ['g', 'grams']];
    };
    const defUnit = (p) => p.unit === 'dose' ? 'dose' : (p.unit === 'L' ? 'L' : (p.bagKg ? 'bag' : 'kg'));
    // A liquid's pack size (bagKg) is liters; its percent is grams per 100 mL, so a liter weighs in at its density (1 when not given).
    const kgOf = (p, amount, unit) => isBag(unit) ? amount * bagKgOf(p, unit) * (p.unit === 'L' ? (p.density || 1) : 1) : unit === 'g' ? amount / 1000 : unit === 'L' ? amount * (p.density || 1) : unit === 'mL' ? amount / 1000 * (p.density || 1) : unit === 'kg' ? amount : 0;
    const amountWords = (p, l) => {
        const a = fmt(l.amount, 2);
        if (isBag(l.unit) && (p.unit === 'L' || (p.bagKg && p.bagKg < 20 && l.unit === 'bag'))) {
            const size = p.unit === 'L' ? (p.bagKg < 1 ? fmt(p.bagKg * 1000, 0) + ' mL' : fmt(p.bagKg) + ' L') : (p.bagKg < 1 ? fmt(p.bagKg * 1000, 0) + ' g' : fmt(p.bagKg) + ' kg');
            return a + ' ' + (l.amount === 1 ? 'pack' : 'packs') + ' of ' + size;
        }
        if (isBag(l.unit)) return a + ' ' + (l.amount === 1 ? 'bag' : 'bags') + (l.unit !== 'bag' ? ' of ' + bagKgOf(p, l.unit) + ' kg' : '') + ' · ' + fmt(kgOf(p, l.amount, l.unit), 1) + ' kg';
        if (l.unit === 'dose') return a + ' hectare ' + (l.amount === 1 ? 'dose' : 'doses');
        return a + ' ' + ({ kg: 'kg', g: 'g', L: 'L', mL: 'mL' }[l.unit] || l.unit);
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
        // Every word of the search, in any order ("anhydrous calcium sulfate"); "16-16-8+9s"
        // finds "16-16-8-9S" (a plus between the grade and the sulfur reads as a dash).
        const words = q.replace(/\s*\+\s*/g, '-').replace(/sulphate/g, 'sulfate').split(/\s+/).filter(Boolean);
        const hit = (p) => { if (p.ph) return false; if (cat !== 'all' && p.category !== cat) return false; if (!q) return true; const hay = (p.name + ' ' + (p.local || '') + ' ' + grade(p) + ' ' + Object.keys(p.pct).map((k) => NAMES[k] || k).join(' ')).toLowerCase(); return words.every((w) => hay.includes(w)); };
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

    /* The Philippine products shelf: asked of the server (the FPA list runs
       past fifteen hundred), the hand checked ones first. A product chosen
       becomes one of NPK Plus's products and goes on to "How much?". */
    let phCat = '', phT = 0, phAsk = 0;
    const phTab = (which) => {
        document.querySelectorAll('#npProdSheet .np-ptab').forEach((b) => { const on = b.dataset.pt === which; b.classList.toggle('is-on', on); b.setAttribute('aria-selected', String(on)); });
        $('npPhPane').hidden = which !== 'ph';
        $('npCommonPane').hidden = which === 'ph';
        if (which === 'ph' && !$('npPhList').dataset.loaded) phLoad();
    };
    document.querySelectorAll('#npProdSheet .np-ptab').forEach((b) => b.addEventListener('click', () => phTab(b.dataset.pt)));
    const paintPhCats = () => { $('npPhCats').innerHTML = '<button type="button" class="np-cat is-on" data-c="">All</button>' + Object.entries(OPT.categories).filter(([k]) => k !== 'mine').map(([k, v]) => '<button type="button" class="np-cat" data-c="' + k + '">' + esc(v) + '</button>').join(''); };
    // The tab row and the desktop tag pick the same category, and each shows it.
    const catTag = (id, label) => { $(id).querySelector('span').innerHTML = 'Showing: <b>' + esc(label) + '</b>'; };
    const setPhCat = (c) => {
        phCat = c;
        $('npPhCats').querySelectorAll('.np-cat').forEach((x) => x.classList.toggle('is-on', x.dataset.c === c));
        catTag('npPhCatBtn', c ? OPT.categories[c] || c : 'All');
        phLoad();
    };
    $('npPhCats').addEventListener('click', (e) => {
        const b = e.target.closest('.np-cat');
        if (b) setPhCat(b.dataset.c);
    });
    $('npPhCatBtn').addEventListener('click', () => {
        const dict = Object.assign({ '': ['All'] }, Object.fromEntries(Object.entries(OPT.categories).filter(([k]) => k !== 'mine').map(([k, v]) => [k, [v]])));
        pick('Which products to show', '', dict, phCat, setPhCat);
    });
    $('npPhQ').addEventListener('input', () => { clearTimeout(phT); phT = setTimeout(phLoad, 280); });
    const phRow = (p) => {
        const nut = Object.entries(p.pct).filter(([, v]) => v > 0).map(([k, v]) => lab(k) + ' ' + (Math.round(v * 100) / 100) + '%').join(' · ');
        return '<button type="button" class="np-prow" data-ph="' + p.id + '"><span class="np-prow-g">' + esc(p.grade || (Object.keys(p.pct)[0] || '')) + '</span><span class="np-prow-t"><b><span class="np-phb">' + esc(p.brand || '') + '</span>' + esc(p.name) + '</b>'
            + '<small>' + esc(nut) + (p.packs && p.packs.length ? ' · ' + esc(p.packs.join(', ')) : '') + '</small><small>FPA ' + esc(p.reg || '') + (p.curated ? '<span class="np-phok">✓ checked</span>' : '') + '</small></span>' + PLUS + '</button>';
    };
    const phLoad = async () => {
        const ask = ++phAsk, q = $('npPhQ').value.trim();
        $('npPhList').innerHTML = '<p class="crop-none">Looking…</p>';
        try {
            const r = await window.api(U.phProducts + '?' + new URLSearchParams({ q, cat: phCat }).toString());
            if (ask !== phAsk) return;
            const rows = r.data.rows || [];
            $('npPhList').dataset.loaded = '1';
            if (!rows.length) { $('npPhList').innerHTML = '<p class="crop-none">No registered product by that name. Try the brand, the grade, or add it from its label below.</p>'; return; }
            const cats = OPT.categories;
            $('npPhList').innerHTML = !q && !phCat
                ? Object.entries(cats).map(([k, v]) => { const g = rows.filter((p) => p.category === k); return g.length ? '<p class="crop-group-h">' + esc(v) + '</p>' + g.map(phRow).join('') : ''; }).join('')
                : rows.map(phRow).join('');
            $('npPhNote').textContent = 'From the Fertilizer and Pesticide Authority\'s list of ' + Number(r.data.total || 0).toLocaleString() + ' registered fertilizers' + (r.data.asOf ? ' (as of ' + new Date(r.data.asOf + 'T00:00:00').toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric' }) + ')' : '') + ', updated every month. Brands are shown so you can find them; NPK Plus does not recommend one over another.';
        } catch (err) { if (ask === phAsk) $('npPhList').innerHTML = '<p class="crop-none">' + esc(err.message || 'The list could not load.') + '</p>'; }
    };
    $('npPhList').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-ph]');
        if (!b || b.disabled) return;
        b.disabled = true;
        try {
            const r = await window.api(U.phUse, { method: 'POST', body: { id: Number(b.dataset.ph) } });
            const p = r.data.product;
            const i = OPT.products.findIndex((x) => String(x.id) === String(p.id));
            if (i >= 0) OPT.products[i] = p; else OPT.products.push(p);
            openQty(p, st.lines.findIndex((l) => String(l.id) === String(p.id)), true);
        } catch (err) { window.toast?.(err.message, 'error'); }
        finally { b.disabled = false; }
    });
    $('npProdQ').addEventListener('input', paintProducts);
    $('npProdQX').addEventListener('click', () => { $('npProdQ').value = ''; paintProducts(); $('npProdQ').focus(); });
    const setCat = (c) => {
        cat = c;
        $('npCats').querySelectorAll('.np-cat').forEach((x) => x.classList.toggle('is-on', x.dataset.c === c));
        catTag('npCatBtn', c === 'all' ? 'All' : OPT.categories[c] || c);
        paintProducts();
    };
    $('npCats').addEventListener('click', (e) => {
        const b = e.target.closest('.np-cat');
        if (b) setCat(b.dataset.c);
    });
    $('npCatBtn').addEventListener('click', () => {
        const dict = Object.assign({ all: ['All'] }, Object.fromEntries(Object.entries(OPT.categories).map(([k, v]) => [k, [v]])));
        pick('Which products to show', '', dict, cat, setCat);
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
    const STEP = { bag: 0.5, bag25: 0.5, bag50: 0.5, kg: 5, g: 100, L: 0.5, mL: 50, dose: 0.5 };
    const openQty = (p, idx, fromList) => {
        const l = idx >= 0 ? st.lines[idx] : null;
        qty = { p, idx, fromList, unit: l ? l.unit : defUnit(p) };
        $('npQtyTitle').textContent = l ? 'Change the amount' : 'How much?';
        $('npQtyProd').innerHTML = '<span class="np-prow-g">' + esc(badge(p)) + '</span><span class="np-prow-t"><b>' + esc(p.name) + '</b><small>' + esc(comp(p)) + '</small>' + (p.note ? '<small class="mt-1">' + esc(p.note) + '</small>' : '') + '</span>';
        $('npQtyIn').value = l ? l.amount : (qty.unit === 'kg' ? 10 : 1);
        $('npQtyFor').textContent = 'For the whole area (' + fmt(areaHa(), 3) + ' ha)';
        $('npQtyOk').textContent = l ? 'Save the amount' : 'Add to the plan';
        // Back to the list when it came from there; no Cancel beside the X (2026-10-12).
        $('npQtyBack').textContent = 'Back to the list'; $('npQtyBack').hidden = !fromList;
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
        if (kg > 0 && next !== 'dose' && qty.unit !== 'dose') {
            const d = p.unit === 'L' ? (p.density || 1) : 1;
            $('npQtyIn').value = Math.round((isBag(next) ? kg / d / bagKgOf(p, next) : next === 'g' ? kg * 1000 : next === 'mL' ? kg / d * 1000 : next === 'L' ? kg / d : kg) * 100) / 100;
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
    $('npCGrid').innerHTML = ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'Si', 'Co'].map((n) => '<label>' + lab(n) + ' %<input type="number" class="form-input" min="0" max="100" step="any" inputmode="decimal" data-n="' + n + '"></label>').join('');
    const paintForm = () => {
        $('npCForm').innerHTML = Object.entries(FORMS).map(([k, v]) => '<button type="button" class="np-pill" data-f="' + k + '" aria-pressed="' + (k === st.form) + '">' + v + '</button>').join('');
        // A liquid is sold by volume, a solid by weight; the label's percent follows.
        const liquid = st.form === 'liquid', units = liquid ? [['mL', 'mL'], ['L', 'liters']] : [['g', 'grams'], ['kg', 'kg']];
        if (!units.some(([k]) => k === st.packU)) st.packU = liquid ? 'L' : 'kg';
        $('npCPackU').innerHTML = units.map(([k, v]) => '<button type="button" class="np-pill" data-pu="' + k + '" aria-pressed="' + (k === st.packU) + '">' + v + '</button>').join('');
        $('npCPack').placeholder = liquid ? '1' : '50';
        $('npCPctLab').textContent = liquid ? 'Nutrients, percent (grams per 100 mL, as printed)' : 'Nutrients, percent by weight';
        $('npCPctSub').textContent = liquid ? 'As printed on the bottle. Leave the rest empty.' : 'As printed on the bag or pack. Leave the rest empty.';
    };
    $('npCForm').addEventListener('click', (e) => { const b = e.target.closest('[data-f]'); if (b) { st.form = b.dataset.f; paintForm(); } });
    $('npCPackU').addEventListener('click', (e) => { const b = e.target.closest('[data-pu]'); if (b) { st.packU = b.dataset.pu; paintForm(); } });
    $('npCustomBtn').addEventListener('click', () => { paintForm(); window.openSheet('npCustomSheet'); });
    $('npCSave').addEventListener('click', async () => {
        const body = { name: $('npCName').value.trim(), form: st.form };
        if (Number($('npCPack').value) > 0) { body.pack = Number($('npCPack').value); body.packUnit = st.packU; }
        $('npCGrid').querySelectorAll('input').forEach((i) => { if (i.value) body[i.dataset.n] = Number(i.value); });
        if (!body.name) return window.toast?.('Give the product a name.', 'error');
        $('npCSave').disabled = true;
        try {
            const r = await window.api(U.product, { method: 'POST', body });
            const p = r.data.product;
            OPT.products.push(p);
            $('npCName').value = ''; $('npCPack').value = ''; $('npCGrid').querySelectorAll('input').forEach((i) => { i.value = ''; });
            window.closeSheet('npCustomSheet');
            paintProducts();
            window.toast?.(r.message, 'success');
            setTimeout(() => openQty(p, -1, true), 260);
        } catch (err) { window.toast?.(err.message, 'error'); }
        finally { $('npCSave').disabled = false; }
    });

    /* ---- the need model (App\Support\NpkModel says where each number comes from) ----
       App\Support\NpkNeed is its twin on the server (the Crop Protocol sizes its
       programs with it): change the arithmetic in both. */
    const needModel = (c, g, f, cls, perHa = {}, acidEq = 0, fl = null, limeEq = null) => {
        const out = { need: {}, raw: {}, how: {}, share: {}, adj: {}, scale: {}, whys: [], Yg: null, micro: {}, base: null, acid: null, credit: {} };
        // The plan's own acidity (kg of lime per hectare): how far it goes toward its full effect.
        const alk = ['alkaline', 'strongAlk', 'calcareous', 'sodic'].some((k) => f.has(k)), sour = ['strongAcid', 'acid', 'acid_sulfate'].some((k) => f.has(k));
        const au = acidEq > 0 ? clamp(acidEq / (M.acidAt || 400), 0, 1) : 0;
        if (Math.abs(acidEq) >= 50) out.acid = { eq: acidEq, u: au, alk, sour };
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
        // The soil test. A low N test and low organic matter are one shortfall (the soil's own nitrogen):
        // its multiplier joins the same group and they count once, the larger (2026-10-19).
        NPK.forEach((n) => {
            const b = cls[n] === 'low' ? 'testLow' : cls[n] === 'high' ? 'testHigh' : null;
            if (!b) return;
            const a = M.adjust[b], s = a.same && a.same[n];
            if (s) same[n + ':' + s] = Math.max(same[n + ':' + s] || 1, a[n]); else m[n] *= a[n];
            sh[n] *= a.share[n];
            out.whys.push(a.why + ' in ' + NAMES[n].toLowerCase());
        });
        Object.entries(same).forEach(([key, v]) => {
            const n = key.split(':')[0];
            // An acidifying plan on alkaline soil frees part of the phosphorus lock;
            // on acid soil it adds to it.
            if (n === 'P2O5' && key.endsWith(':pfix') && au > 0) {
                if (alk && v > 1) { v = 1 + (v - 1) * (1 - au * (M.acidUnlock ?? 0.5)); out.whys.push('the acid fertilizers in your plan free some of the locked phosphorus'); }
                else if (sour) { v = v + 0.1 * au; out.whys.push('the acid fertilizers in your plan make the acid soil sourer'); }
            }
            m[n] *= v;
        });
        // Your field: the nitrogen the organic matter or slow drainage adds or saves.
        if (fl && fl.on) m.N *= fl.nMult;
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
        // Your field: what the last crop left (a legume's nitrogen, rice straw's potassium), never more than part of the need.
        if (fl && fl.on) ['N', 'K2O'].forEach((n) => {
            const cr = fl.credit[n];
            if (!cr || out.need[n] == null || out.how[n] === 'legume') return;
            const cut = Math.min(cr, out.need[n] * ((M.field || {}).creditFloor ?? 0.5));
            out.need[n] -= cut; out.credit[n] = cut;
        });
        // Lockouts the plan itself makes: too much phosphorus ties up zinc and
        // iron, too much potassium crowds out magnesium and calcium, lime ties
        // up zinc, boron, manganese, copper and iron, too much zinc crowds out copper.
        const pf = new Set();
        if ((perHa.P2O5 || 0) > (out.need.P2O5 ? out.need.P2O5 * 1.5 : 100)) pf.add('high_p');
        if ((perHa.K2O || 0) > (out.need.K2O ? out.need.K2O * 1.5 : 150)) pf.add('high_k');
        // Lime in the plan: its liming materials, 300 kg of lime equivalent a hectare or more (no limeEq
        // given: the plan's net effect on the pH). Gypsum and calcium nitrate carry calcium, not lime.
        if ((limeEq != null ? limeEq : Math.max(0, -acidEq)) >= 300) pf.add('liming');
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
            // Acid around the roots on a high pH soil: zinc, iron, manganese and copper come
            // free again, and on sodic soil the soil's own lime turns to calcium.
            if (au > 0 && alk && (['Zn', 'Fe', 'Mn', 'Cu'].includes(el) || (el === 'Ca' && f.has('sodic')))) {
                score -= au;
                down.push(el === 'Ca' ? 'the acid fertilizers in your plan turn the soil\'s own lime into calcium' : 'the acid fertilizers in your plan free some of it around the roots');
            }
            const L = M.levels;
            let level = score >= L.likely.at ? 'likely' : score >= L.watch.at ? 'watch' : 'none';
            // Your field: a zinc shortage last season makes it likely this one too.
            if (fl && fl.zinc && el === 'Zn' && level !== 'likely') { level = 'likely'; why.unshift('zinc ran short last season'); }
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
        // What the plan does to the soil's acidity, in kg of lime per hectare (NpkModel::ACIDITY).
        const AC = M.acidity || { perN: {}, perS: {}, perKg: {}, defaultPerN: 1.8 };
        const acidEq = area > 0 ? st.lines.reduce((sum, l) => {
            const p = prodOf(l.id);
            if (!p || l.unit === 'dose') return sum;
            const kg = kgOf(p, l.amount, l.unit), slug = p.slug || '';
            if (AC.perKg[slug] != null) return sum + kg * AC.perKg[slug];
            const nKg = kg * (p.pct.N || 0) / 100, sKg = kg * (p.pct.S || 0) / 100;
            const perN = AC.perN[slug] != null ? AC.perN[slug] : (p.pct.S >= 20 && p.pct.N >= 18 ? 5.35 : AC.defaultPerN);
            return sum + nKg * perN + (AC.perS[slug] != null || (p.pct.S >= 80 && !(p.pct.N > 0)) ? sKg * (AC.perS[slug] ?? 3.1) : 0);
        }, 0) / area : 0;
        // The plan's liming materials, in kg of lime equivalent per hectare (NpkNeed::limeEq); a farmer's own
        // lime or dolomite counts by its name.
        const limeEq = area > 0 ? st.lines.reduce((sum, l) => {
            const p = prodOf(l.id);
            if (!p || l.unit === 'dose') return sum;
            const k = AC.perKg[p.slug || ''] ?? (/\b(lime|limestone|dolomit\w*|apog)\b/i.test(p.name || '') ? -0.9 : 0);
            return k < 0 ? sum + kgOf(p, l.amount, l.unit) * -k : sum;
        }, 0) / area : 0;
        // Manure or compost in the plan, kg per hectare (organic_inputs at a tonne or more).
        const organicKg = area > 0 ? st.lines.reduce((sum, l) => { const p = prodOf(l.id); return p && l.unit !== 'dose' && p.category === 'organic' ? sum + kgOf(p, l.amount, l.unit) : sum; }, 0) / area : 0;
        $('npN').textContent = fmt(perHa.N, 0); $('npP').textContent = fmt(perHa.P2O5, 0); $('npK').textContent = fmt(perHa.K2O, 0);
        const npk = fmt(perHa.N, 0) + '-' + fmt(perHa.P2O5, 0) + '-' + fmt(perHa.K2O, 0);

        const c = cropOf(st.crop), g = goalOf(c), f = factors(c, { SP: seasonPlanks(c, g), organicKg }), cls = soilClass(soil()), fl = fieldLayer(c, f), md = needModel(c, g, f, cls, perHa, acidEq, fl, limeEq);
        // A level the area filled is the area's, not a test the farmer made (the Crop Protocol says it the same way).
        if (st.area) {
            const own = soilClass(soil(false)), names = { N: 'nitrogen', P2O5: 'phosphorus', K2O: 'potassium' };
            const filled = Object.keys(st.area.tests || {}).filter((k) => names[k] && !own[k]).map((k) => names[k]);
            if (filled.length) {
                const re = new RegExp('^Your soil test reads\\b.*\\b(?:' + filled.join('|') + ')\\b', 'i');
                md.whys = (md.whys || []).map((w) => typeof w === 'string' && re.test(w) ? w.replace(/^Your soil test reads/i, 'Soil around here usually reads') : w);
            }
        }
        const FL = fieldLines(c, g, f, md, fl);
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
                + fieldBox(FL)
                + (guide.length ? '<p class="np-fine mt-2">The official guide without a soil test: ' + esc(guide.join(', ')) + ' kg per ha. NPK Plus sizes it to your goal and your soil.</p>' : '')
                + method(c, g, md);
        } else {
            $('npNeed').innerHTML = '<div class="np-say">' + (st.cropPicked ? 'No crop picked, so these are the totals only.' : 'Pick the crop in step 1 to see what it needs for your goal.') + '</div>';
        }
        const short = NPK.filter((n) => states[n] && states[n].state === 'short'), over = NPK.filter((n) => states[n] && states[n].state === 'over');
        // The plan's acidity, said once.
        const acidSay = md.acid ? (md.acid.eq > 0
            ? '<div class="np-say"><b>Your plan acidifies the soil</b> by about ' + fmt(md.acid.eq, 0) + ' kg of lime per hectare (ammonium and sulfur fertilizers). '
                + (md.acid.alk ? 'On this high pH soil that is welcome: around the roots it frees some of the phosphorus, zinc, iron and manganese the soil locks up' + (f.has('sodic') ? ', and turns the soil\'s own lime into the calcium that pushes sodium out' : '') + '. NPK Plus counts it above.'
                    : md.acid.sour ? 'On this acid soil it makes things worse: lime the field, and lean on less acidifying sources.' : 'Over many seasons it lowers the pH; a soil test every few years tells when to lime.') + '</div>'
            : '<div class="np-say"><b>Your plan raises the pH</b> by about ' + fmt(-md.acid.eq, 0) + ' kg of lime per hectare.' + (md.acid.alk ? ' On this high pH soil that locks up more phosphorus and zinc: choose ammonium or sulfate sources instead.' : '') + '</div>') : '';
        // Where the soil locks phosphorus up, a band beats more of it.
        const pLock = c && md.need.P2O5 && md.pLock;
        $('npSay').innerHTML = c && out.length && Object.keys(states).length
            ? '<div class="np-say">' + (short.length ? 'Short of ' + esc(list(short.map((n) => NAMES[n].toLowerCase()))) + ' for your goal. ' : '') + (over.length ? 'More ' + esc(list(over.map((n) => NAMES[n].toLowerCase()))) + ' than the crop needs: it costs money and can harm the crop. ' : '')
                + (!short.length && !over.length ? 'The plan meets the need for nitrogen, phosphorus and potassium. ' : '') + 'The Barrel tab shows what that means for the harvest.</div>'
                + acidSay + (pLock ? '<div class="np-say"><b>On this soil, band the phosphorus.</b> Put it in a band beside and a little below the seed, or in the planting hole, instead of spreading it over the field: less of it touches the soil that locks it up. The need above counts the lock up as if it were spread, so a banded plan can do with less.</div>' : '')
            : '';

        const micro = st.microsOn ? micros(c, md, tot, perHa, area, out.length) : microsOff();
        const { reach, harvest } = barrel(c, md, states, perHa, out.length, g, micro);

        const seedV = num('npSeed');
        RES = { perHa: Object.fromEntries(Object.entries(perHa).filter(([, v]) => v > 0).map(([k, v]) => [k, Math.round(v * 1000) / 1000])), total: Object.fromEntries(Object.entries(tot).filter(([, v]) => v > 0).map(([k, v]) => [k, Math.round(v * 1000) / 1000])),
            npkPerHa: npk, needs: states, micros: micro, reach, harvest, lines: out, areaHa: area, soilClass: cls,
            goal: g.Y ? { tPerHa: Math.round(g.Y * 100) / 100, counted: Math.round(g.Yeff * 100) / 100, why: g.why, fieldCap: g.capped ? Math.round(g.cap.k * 100) / 100 : null } : null,
            setup: { variety: $('npVariety').value.trim(), areaUnit: st.areaUnit, areaValue: num('npArea'), yUnit: st.yUnit,
                potential: num('npPotential') ? { value: num('npPotential'), unit: yu[1], key: st.yUnit, tPerHa: Math.round(num('npPotential') * yu[3] * 100) / 100 } : null,
                target: num('npTarget') ? { value: num('npTarget'), unit: yu[1], tPerHa: Math.round(num('npTarget') * yu[3] * 100) / 100 } : null,
                seed: seedV ? { amount: seedV, unit: SEED_U[st.seedUnit][1], key: st.seedUnit, perHa: g.sd.raw ? Math.round(g.sd.raw * 10) / 10 : null, germination: g.sd.germ || null } : null,
                texture: st.texture, conditions: st.conds.slice(), ph: phVal(), intensive: !!st.intensive, zincRecent: !!st.zincRecent, microsOn: !!st.microsOn, prov: st.prov, town: st.town, water: st.water, planted: $('npPlanted').value || null,
                // The variety's source (2026-10-15) and what the field in detail changed, in words.
                varietyPick: st.varPick ? Object.assign({}, st.varPick, recT(st.varPick) ? { potential: recT(st.varPick), potentialIs: st.varPick.top ? 'top' : 'avg', potentialFrom: potOwn() ? 'own' : 'record' } : {}) : null,
                fieldAdjust: FL.map((l) => l.lead + ' ' + l.text + (l.e ? ' [' + l.e + ']' : '')),
                // The farm's lot it was made for (2026-10-16): a saved calculation opens on it again.
                lotId: LOT ? LOT.id : null, lotName: LOT ? LOT.name : null,
                // The area's typical soil the farmer chose to use (2026-10-18).
                area: st.area ? { where: st.area.where, tests: st.area.tests, filled: st.area.filled } : null },
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
        li.push('<b>Yield goal:</b> ' + (g.Y ? fmt(g.Y) + ' t/ha, ' + esc(g.why) + '.' : 'none, so the guide rate is used as it is.') + (g.sd.sf < 1 ? ' A thin stand brings it to ' + fmt(g.Y * g.sd.sf) + ' t/ha.' : '')
            + (g.capped ? ' ' + esc(fieldWho()) + ' (' + esc(capWords(g.cap)) + ') holds it to ' + fmt(g.Yeff) + ' t/ha.' : ''));
        if (md.credit && (md.credit.N || md.credit.K2O)) li.push('<b>Credits from your field:</b> ' + [md.credit.N ? fmt(md.credit.N, 0) + ' kg N from the legume before' : '', md.credit.K2O ? fmt(md.credit.K2O, 0) + ' kg K₂O from the rice straw plowed back' : ''].filter(Boolean).join(', and ') + ', taken off the need above.');
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
    // One word per verdict, shared with the barrel's planks and key (2026-10-19).
    const MICRO_WORD = { short: 'Short', right: 'Enough', over: 'Too much', extra: 'Extra', noflag: 'Not flagged', testok: 'Enough', notneeded: 'Not needed' };
    // Left out (2026-10-21, the owner): the tab says so, and nothing is counted for the elements past N, P and K.
    const microsOff = () => {
        $('npMicro').innerHTML = '<div class="np-say">The micronutrients are left out: this plan counts nitrogen, phosphorus and potassium only, and the barrel has only their planks. Switch <b>Count the micronutrients</b> back on to see calcium, magnesium, sulfur, zinc, boron and the rest.</div>';
        return {};
    };
    const paintMicroTab = () => {
        const t = document.querySelector('#npOTabs [data-p="micro"]');
        if (!t) return;
        t.textContent = st.microsOn ? 'Micros' : 'Micros (off)';
        t.classList.toggle('is-off', !st.microsOn);
    };
    $('npMicrosOn').addEventListener('change', (e) => { st.microsOn = e.target.checked; paintMicroTab(); calc(); });
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
            out[el] = { need: Math.round(need * 1000) / 1000, plan: Math.round(plan * 1000) / 1000, level: mm.level, state, beneficial: !!e.beneficial };
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
            // A beneficial element is never "short": it would help (the barrel leaves it out the same way).
            const word = e.beneficial && state === 'short' ? 'Would help' : MICRO_WORD[state];
            return '<div class="np-mrow is-' + (e.beneficial && state === 'short' ? 'helps' : state) + (['Ca', 'Mg', 'S'].includes(el) ? ' is-sec' : '') + (e.beneficial ? ' is-ben' : '') + '" style="--k:' + (k++) + '"><i>' + el + '</i>'
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
    // N, P, K, the three secondaries, zinc and boron always stand; any other required element (iron,
    // manganese, copper, molybdenum) joins when the Micros tab has a verdict on it, so a shortage there is a
    // plank here too. Beneficial elements stay out: they help, they never hold the harvest back.
    const STAVES_BASE = [{ k: 'N', t: 'N' }, { k: 'P2O5', t: 'P₂O₅' }, { k: 'K2O', t: 'K₂O' }, { k: 'Ca', t: 'Ca' }, { k: 'Mg', t: 'Mg' }, { k: 'S', t: 'S' }, { k: 'Zn', t: 'Zn' }, { k: 'B', t: 'B' }];
    const STAVES_SKY = [{ k: 'sun', t: 'Sun', name: 'Sunlight', dull: true }, { k: 'water', t: 'Water', name: 'Water', dull: true }, { k: 'temp', t: 'Temp', name: 'Temperature', dull: true }];
    let STAVES = STAVES_BASE.concat(STAVES_SKY);
    const stavesFor = (micro) => {
        if (!st.microsOn) return STAVES_BASE.slice(0, 3).concat(STAVES_SKY);
        const has = STAVES_BASE.map((x) => x.k);
        const more = Object.keys(M.elements || {}).filter((el) => !has.includes(el) && !(M.elements[el] || {}).beneficial
            && micro && micro[el] && ['short', 'over', 'right', 'testok'].includes(micro[el].state));
        return STAVES_BASE.concat(more.map((el) => ({ k: el, t: el })), STAVES_SKY);
    };
    const BR = (() => {
        const R = 80, H = 190;
        const el = $('lbBarrel'), stage = $('lbStage'), svg = $('lbArrow'), ap = svg.querySelector('.lb-ap'), ah = svg.querySelector('.lb-ah'), alab = $('lbALab');
        const disc = (cls, d, y) => '<div class="' + cls + '" style="width:' + d + 'px;height:' + d + 'px;margin-left:' + (-d / 2) + 'px;top:' + y + 'px"></div>';
        let ry = -18, rx = -16, vel = 0, drag = null, aim = null, on = false, raf = 0, t0 = performance.now(), lim = -1, level = 0, arrowT = 0;
        let n = 0, ang = 0, built = '', sts = [], water = null, top = null;
        // The barrel is put together for a set of planks, and again only when the set changes.
        const build = (list) => {
            const sig = list.map((x) => x.k).join(',');
            if (sig === built) return;
            built = sig; n = list.length; ang = 360 / n; lim = -1;
            const W = Math.ceil(2 * R * Math.tan(Math.PI / n)) + 1, RW = R - 4, WW = Math.ceil(2 * RW * Math.tan(Math.PI / n)) + 1;
            el.innerHTML = disc('lb-shadow', 2 * R * 1.7, H + 2) + disc('lb-floor', 2 * R, H)
                + list.map((s, i) => '<div class="lb-st" style="width:' + W + 'px;margin-left:' + (-W / 2) + 'px;transform:rotateY(' + (i * ang) + 'deg) translateZ(' + R + 'px);--d:' + (i * 55) + 'ms">'
                    + '<div class="lb-out"><b>' + s.t + '</b><small></small></div><div class="lb-in"></div><div class="lb-spill"></div><div class="lb-pool"></div></div>').join('')
                + '<div class="lb-water">' + list.map((s, i) => '<div class="lb-wf" style="width:' + WW + 'px;margin-left:' + (-WW / 2) + 'px;transform:rotateY(' + (i * ang) + 'deg) translateZ(' + RW + 'px)"></div>').join('')
                + disc('lb-top', 2 * RW + 3, 0) + '</div>'
                + disc('lb-rim', 2 * R + 16, 0);
            sts = [...el.querySelectorAll('.lb-st')]; water = el.querySelector('.lb-water'); top = el.querySelector('.lb-top');
        };
        build(STAVES);
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
            H, face, build,
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
    const WORD = Object.assign({}, MICRO_WORD, { fixed: 'From the air', unknown: 'Not measured', dull: 'Not measured' });
    /* The planks (2026-10-19, the owner: "NPK does not adhere to the barrel").
       A nutrient's plank is the very number its own tab shows: the share of
       its need for your goal the plan covers, short or enough by the same
       line, so "Short" in a tab is always a short plank here. Sun, water and
       heat are the share of the crop's need the season meets (seasonPlanks).
       The water is the need met at the shortest plank. The harvest is said
       apart (yr): the soil gives part of a missing nutrient on its own, so a
       short plan costs less harvest than its plank shows, while it drains
       the soil for the next season. */
    const barrel = (c, md, states, perHa, has, g, micro) => {
        STAVES = stavesFor(micro);
        BR.build(STAVES);
        const H = BR.H, rows = [], SP = seasonPlanks(c, g);
        STAVES.forEach((s) => {
            if (s.dull) {
                if (!SP) { rows.push({ state: 'dull', ratio: null, px: H, pct: '', note: 'Pick the place and the planting date' }); return; }
                const r = SP[s.k], state = r < 0.97 ? 'short' : 'right', yu0 = Y_U[st.yUnit];
                const note = (s.k === 'sun' ? fmt(SP.rad, 1) + ' MJ of sun a day against the ' + SP.K.rad + ' a full harvest wants'
                    : s.k === 'water' ? (st.water === 'irrigated' ? 'Irrigated: the crop gets the ' + Math.round(SP.need) + ' mm it drinks' : (st.water === 'pump' ? 'The rain and your pump give about ' : 'About ') + Math.round(SP.supply) + ' of the ' + Math.round(SP.need) + ' mm the crop drinks')
                    : fmt(SP.tmean, 1) + '°C on average, ' + Math.round(SP.hotN) + ' days too hot for flowers')
                    + (SP.cap ? ', enough for about ' + fmt(SP.cap[s.k] / yu0[3]) + ' ' + yu0[1] : '');
                rows.push({ state, ratio: r, yr: r, px: Math.max(22, Math.round(H * Math.min(1.22, r))), pct: Math.round(Math.min(1, r) * 100) + '%', note, season: true });
                return;
            }
            const n = s.k;
            let state = 'unknown', ratio = null, yr = null, note = c ? 'No numbers for this crop' : 'Pick the crop';
            // A plank from its tab's verdict and cover: short at its cover, enough at the rim, too much above it.
            const plank = (st0, cover, over) => st0 === 'over' ? over : st0 === 'right' || st0 === 'testok' ? 1 : Math.min(1, cover);
            if (c && NPK.includes(n)) {
                if (md.how.N === 'legume' && n === 'N') { state = 'fixed'; ratio = 1; yr = 1; note = 'A legume makes its own'; }
                else if (md.need[n] != null) {
                    const s0 = md.share[n], cover = md.need[n] > 0 ? perHa[n] / md.need[n] : 1;
                    // The NPK tab's own verdict and number, so the two never disagree.
                    state = states[n] ? states[n].state : 'right';
                    ratio = plank(state, cover, Math.min(1.22, 1 + (cover - 1) * 0.25));
                    yr = state === 'short' ? s0 + (1 - s0) * Math.min(1, cover) : 1;
                    note = 'Plan covers ' + Math.round(Math.min(cover, 9.99) * 100) + '% of the need for your goal'
                        + (state === 'short' ? ' (the soil gives some on its own: the harvest keeps about ' + Math.round(yr * 100) + '%)' : '');
                }
            } else if (c && md.micro[n]) {
                // The Micros tab's own verdict (micros() above), so the two never disagree.
                const mm = md.micro[n], plan = perHa[n] || 0, v = (micro && micro[n]) || {};
                state = v.state || 'noflag';
                if (mm.need > 0) {
                    const cover = plan / mm.need;
                    ratio = plank(state, cover, 1.1);
                    yr = state === 'short' ? mm.share + (1 - mm.share) * Math.min(1, cover) : 1;
                    note = { watch: 'May run short', likely: 'Likely short', test: 'Short by your soil test' }[mm.level] + (plan > 0 ? ', plan covers ' + Math.round(Math.min(cover, 9.99) * 100) + '% of the need for your goal' : ', none in plan');
                } else if (state === 'testok') { ratio = 1; yr = 1; note = 'Enough, by your soil test'; }
                else {
                    // Not flagged, not needed or extra: no need to measure the plank against, drawn full and pale.
                    ratio = null;
                    note = { notneeded: 'Not needed for this crop', extra: 'Extra: the crop has no need for it here' }[state] || 'No shortage flagged: nothing you told NPK Plus points to one';
                }
            }
            const px = ratio === null ? H : Math.max(22, Math.round(H * Math.min(1.22, ratio)));
            rows.push({ state, ratio, yr, px, pct: ratio === null ? '?' : Math.round(Math.min(ratio, 1) * 100) + '%', note });
        });
        const measured = rows.map((r, i) => [r, i]).filter(([r]) => r.ratio !== null && r.state !== 'dull');
        // The season's sky and water, from twenty years of weather.
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
                + '<div class="lb-rainyears" aria-label="Rain in the same weeks of each year">' + yrs.map((y) => '<span style="height:' + Math.max(4, y.rain / maxR * 100) + '%" title="' + y.year + ': ' + y.rain + ' mm">' + (yrs.length <= 12 || y.year % 2 === 0 ? '<b>' + String(y.year).slice(2) + '</b>' : '') + '</span>').join('')
                + '<span class="is-now" style="height:' + Math.max(4, SP.rain / maxR * 100) + '%" title="Expected this season: ' + Math.round(SP.rain) + ' mm"><b>Now</b></span></div>'
                + '<p class="lb-rainkey">Rain in the same weeks of each of the last ' + yrs.length + ' years, and the striped bar for this season, tilted by ' + esc(SP.t.e ? SP.t.e.label.toLowerCase() : 'nothing') + '. Weather history: Open-Meteo. ENSO: NOAA.</p>';
        }
        let limIdx = -1, level = 0;
        if (has && measured.length) { [, limIdx] = measured.reduce((a, b) => (b[0].px < a[0].px ? b : a)); level = Math.min(H, rows[limIdx].px); }
        BR.set(rows, level, limIdx);
        const yu = Y_U[st.yUnit];
        const B = basisOf(c);
        $('lbTag').textContent = g.Yeff ? 'The rim is your goal, ' + fmt(g.Yeff / yu[3]) + ' ' + yu[1] + (B ? ' dry' : ' fresh') + '.' : 'The rim is everything the crop needs.';
        $('lbKey').innerHTML = STAVES.map((s, i) => {
            const r = rows[i];
            return '<button type="button" class="lb-k is-' + r.state + '" data-i="' + i + '" title="' + esc(r.note) + '"><i></i><b>' + esc(s.name || NAMES[s.k]) + (s.dull ? '' : ' <small>' + s.t + '</small>') + '</b>'
                + '<span class="lb-kbar"><span style="width:' + (r.ratio === null ? (r.state === 'dull' ? 100 : 0) : Math.min(100, r.ratio * 100)) + '%"></span></span><em>' + (WORD[r.state] || '') + '</em></button>';
        }).join('');
        // The water, said: the share of the need met at the shortest plank. And the harvest apart: the
        // soil gives part of a missing nutrient on its own, so it falls less than the water.
        const reach = limIdx >= 0 ? Math.round(Math.min(1, rows[limIdx].ratio) * 100) : null;
        const ys = measured.map(([r]) => r.yr).filter((v) => v != null);
        const harvest = limIdx >= 0 && ys.length ? Math.round(Math.min(1, ...ys) * 100) : null;
        const tons = (pc) => g.Yeff ? fmt(g.Yeff * pc / 100 / yu[3]) + ' ' + yu[1] : '';
        $('lbReach').hidden = reach === null;
        if (reach !== null) $('lbReach').innerHTML = '<b>' + reach + '%</b><span>of what your crop needs for your goal is met, at the shortest plank.'
            + (harvest !== null && harvest > reach ? '<small class="lb-harvest">The harvest: about <b>' + harvest + '%</b> of your goal' + (g.Yeff ? ', near ' + esc(tons(harvest) + basisSay(c, g.Yeff * harvest / 100)) : '') + ', because the soil gives part of what is missing on its own.</small>' : '') + '</span>';
        // The arrow says the need met and, under it, the yield it points to (2026-10-20, the owner: put the potential yield back).
        const yieldPc = harvest !== null ? Math.max(harvest, reach ?? 0) : reach;
        // The yield it points to: dry and wet for a crop sold dried, fresh for the rest (basisOf).
        const yt = yieldPc !== null && g.Yeff ? g.Yeff * yieldPc / 100 : null;
        $('lbALab').innerHTML = 'The water is the need met' + (reach !== null ? '<b>' + reach + '%</b>' : '')
            + (yt ? (B ? '<small>Dry about ' + esc(tPer(yt)) + '</small><small>Wet about ' + esc(wetPer(B, yt)) + '</small>' : '<small>Fresh about ' + esc(tPer(yt)) + '</small>') : '');
        let say = '';
        const cName = c ? esc(cropShort(c).toLowerCase()) : '';
        if (!c) say = st.cropPicked ? 'No crop picked: the barrel needs the crop\'s numbers.' : 'Pick the crop in step 1: the planks are measured against what it needs.';
        else if (!has) say = 'Add a fertilizer in step 5, and the water rises to show how much of the need your plan meets.';
        else if (limIdx < 0) say = 'There are no numbers on file for ' + cName + ', so the planks cannot be measured.';
        else {
            const L = rows[limIdx], Ls = STAVES[limIdx];
            const seasonMax = SP && SP.cap ? Math.min(SP.cap.sun, SP.cap.water, SP.cap.temp) : null;
            say = reach >= 97
                ? 'Every plank NPK Plus can measure reaches the rim: your plan meets the need for your goal.'
                    + (seasonMax && g.Yeff && seasonMax >= g.Yeff ? ' The season itself could carry about ' + esc(fmt(seasonMax / yu[3])) + ' ' + esc(yu[1]) + ', so your goal of ' + esc(fmt(g.Yeff / yu[3])) + ' ' + esc(yu[1]) + ' sits within it.' : '')
                : 'The shortest plank is <b>' + esc(Ls.name || NAMES[Ls.k]) + '</b>' + (Ls.name ? '' : ' (' + Ls.t + ')') + ': ' + esc(L.note.toLowerCase()) + '. ' + (reach === 0 ? 'Your plan gives none of it, so the water stays at the bottom.' : 'It holds the water at ' + reach + '% of the need.') + ' Raise it first; more of the others will not raise the water.'
                    + (harvest !== null && harvest > reach ? ' The soil gives part of what is missing on its own, so this season\'s harvest may still come near ' + harvest + '% of your goal' + (g.Yeff ? ' (' + esc(tons(harvest)) + ')' : '') + ', but what the plan leaves out is taken from the soil, and the next season starts poorer.' : '');
            const over = STAVES.filter((s, i) => rows[i].state === 'over').map((s) => NAMES[s.k].toLowerCase());
            if (over.length) say += ' Too much ' + esc(list(over)) + ': the taller plank holds no more water. It costs money' + (over.includes('nitrogen') ? ', and too much nitrogen can make the crop lodge and draw pests' : '') + '.';
            const unk = STAVES.filter((s, i) => rows[i].state === 'noflag').map((s) => s.t);
            if (unk.length) say += ' Not flagged: ' + esc(unk.join(', ')) + '. Nothing you told NPK Plus points to a shortage of them, as the Micros tab says; only a soil test can say they are enough.';
            const nn = STAVES.filter((s, i) => rows[i].state === 'notneeded').map((s) => s.t);
            if (nn.length) say += ' Not needed for this crop: ' + esc(nn.join(', ')) + '.';
        }
        say += SP ? ' Sun, water and temperature are read from twenty years of weather at your field and the ENSO state now: a rough guide to the season, not a forecast of it.'
            : ' The grey planks, sun, water and temperature, can be the shortest too: pick the place and the planting date in step 4 and NPK Plus reads them from twenty years of weather at your field.';
        $('lbSay').innerHTML = '<div class="np-say">' + say + '</div>';
        return { reach, harvest };
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
    /* The field in detail (partials/field-profile-card), in Anee's sheet: its
       answers fill the soil type, the soil's other conditions, the pH and the
       water the calculator still had as not sure (never one the farmer set),
       so the plan and Anee's reading count the same field. */
    const andList = (a) => a.length < 2 ? (a[0] || '') : a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1];
    function applyProfile(answers, L) {
        if (!OPT || !M) return '';
        const got = [];
        let moved = false;
        if (!TOUCH.texture) {
            const t = ['sandy', 'loam', 'clay'].includes(L.soil) && M.textures[L.soil] ? L.soil : 'unsure';
            if (t !== st.texture) { st.texture = t; moved = true; }
            if (t !== 'unsure') got.push('the soil type');
        }
        if (!TOUCH.conds) {
            const want = (L.soilConditions || []).filter((k) => M.conditions[k]);
            if (answers.organic === 'low' && M.conditions.low_om) want.push('low_om');
            if (answers.drainage === 'stands' && M.conditions.waterlogged) want.push('waterlogged');
            if (want.join() !== st.conds.join()) { st.conds = want; moved = true; }
            if (want.length) got.push('what else is true of the soil');
        }
        if (!TOUCH.ph) {
            const v = L.phValue != null ? String(L.phValue) : '';
            if (v !== $('npPh').value) { $('npPh').value = v; moved = true; }
            if (v) got.push('the pH');
        }
        if (!TOUCH.water) {
            const w = WATER_U[L.waterNpk] ? L.waterNpk : 'irrigated';
            if (w !== st.water) { st.water = w; tagWide('npWaterBtn', WATER_U[w][0], false); moved = true; }
            if (WATER_U[L.waterNpk]) got.push('the water');
        }
        // How the field is farmed (2026-10-19): the lot's crops a year and its last zinc set the two switches, the
        // answers NpkNeed::run reads for the Crop Protocol, so a lot counts the same in both.
        if (!TOUCH.habits) {
            const hi = ['two', 'three'].includes(answers.cropsYear), hz = answers.zincGiven === 'recent';
            if (hi !== !!st.intensive || hz !== !!st.zincRecent) { st.intensive = hi; st.zincRecent = hz; $('npIntensive').checked = hi; $('npZincRecent').checked = hz; moved = true; }
            if (answers.cropsYear || answers.zincGiven) got.push('how the field is farmed');
        }
        // The layer reads the answers whatever moved: the goal, the need and its lines follow at once.
        soilTag(); hints(); calc();
        // The water lives in step 4: it says when the details filled it.
        if (window.aneeFieldCard) window.aneeFieldCard.hint(document.querySelector('.np-wstep[data-s="3"]'), !TOUCH.water && !!WATER_U[L.waterNpk], '', $('npWaterBtn').previousElementSibling);
        return got.length ? 'Filled in for you: ' + andList(got) + '. NPK Plus counts the rest under the need, line by line.' : (FPC && FPC.count() ? 'NPK Plus counts your answers under the need, line by line.' : '');
    }
    function mountCard() {
        if (FPC || !window.aneeFieldCard || !$('npFpc')) return;
        FPC = window.aneeFieldCard.mount($('npFpc'), { crop: () => st.crop || null, title: () => { const [tw, pv] = placeWords(); return [tw, pv].filter(Boolean).join(', ') || $('npLoc').value.trim() || 'Your field'; }, onChange: applyProfile });
    }

    /* ---- the farm's lot (2026-10-16, the owner) ----
       Optional here (the calculator is free and works without one). A lot
       picked fills the place, the area, the water and the soil from its
       answers, and every question it answered folds away with a line that
       says where it came from. Its answers ride in the field in detail card
       (hidden), so the need counts them exactly as the card would. "Use no
       lot" unfolds them again, filled. Never inside Anee Auto. */
    const lotHas = (k) => !!(LOT && window.aneeLots && window.aneeLots.has(LOT, k));
    const lotHides = () => ({
        area: !!(LOT && Number(LOT.areaHa) > 0),
        place: !!(LOT && LOT.province && LOT.town),
        water: lotHas('water'),
        fpc: !!LOT,
        texture: lotHas('texture'),
        ph: lotHas('phFrom'),
        conds: (lotHas('acidity') || lotHas('phFrom')) && lotHas('salt'),
        habits: lotHas('cropsYear') && lotHas('zincGiven'),
        loc: !!(LOT && LOT.where),
    });
    const fold = (el, off) => {
        if (el.classList.contains('is-off') === off) return;
        el.classList.toggle('is-off', off);
        if (off) el.setAttribute('inert', ''); else el.removeAttribute('inert');
    };
    const LOT_PIN = '<i aria-hidden="true">📍</i>';
    const lotSay = (key, bits) => {
        const el = document.querySelector('[data-lotsay="' + key + '"]');
        if (!el) return;
        if (!LOT || !bits.length) { el.hidden = true; return; }
        el.innerHTML = LOT_PIN + '<span>From <b>' + esc(LOT.name) + '</b>: ' + esc(bits.join(' · ')) + '. <button type="button" data-lot-edit>Edit the lot</button></span>';
        el.hidden = false;
    };
    const areaWords = () => fmt(num('npArea'), 4) + ' ' + AREA_U[st.areaUnit][1];
    function paintLot() {
        const H = lotHides();
        document.querySelectorAll('[data-lq]').forEach((el) => fold(el, !!H[el.dataset.lq]));
        if ($('npNoLot')) $('npNoLot').hidden = !LOT;
        $('npSeasonQ').textContent = H.place && H.water ? 'When you plant' : (H.place ? 'When, and the water' : 'Where, when and the water');
        if (!LOT) { ['area', 'season', 'soil', 'anee'].forEach((k) => lotSay(k, [])); return; }
        const [tw, pv] = placeWords();
        lotSay('area', H.area ? [areaWords()] : []);
        lotSay('season', [H.place ? [tw, pv].filter(Boolean).join(', ') : '', H.water ? WATER_U[st.water][0] : ''].filter(Boolean));
        const n = FPC ? FPC.count() : 0;
        lotSay('soil', [H.texture && M ? M.textures[st.texture][0] : '', H.ph && phVal() != null ? 'pH ' + fmt(phVal()) : '',
            H.conds ? (st.conds.length ? st.conds.map((k) => (M.conditions[k] || [k])[0]).join(', ') : 'nothing else of note') : '',
            H.habits ? ({ one: 'one crop a year', two: 'two crops a year', three: 'three crops a year' }[LOT.answers.cropsYear] || '') + (LOT.answers.zincGiven === 'recent' ? ', zinc lately' : '') : '',
            n ? n + (n === 1 ? ' answer' : ' answers') + ', counted under the need' : 'no answers yet'].filter(Boolean));
        lotSay('anee', [LOT.place || LOT.where || '', n ? n + (n === 1 ? ' answer' : ' answers') + ' about its soil, water and last season' : ''].filter(Boolean));
    }
    async function applyLot(lot) {
        if (!lot || !OPT || !M) return;
        LOT = lot;
        // What the lot answered wins over what was set by hand: those questions fold away.
        const H = lotHides();
        if (H.texture) TOUCH.texture = false;
        if (H.water) TOUCH.water = false;
        if (H.ph) TOUCH.ph = false;
        if (H.conds) TOUCH.conds = false;
        if (H.habits) TOUCH.habits = false;
        if (H.place) {
            st.prov = lot.province; st.town = lot.town;
            if ($('npProvBtn')) { tagWide('npProvBtn', st.prov, false); tagWide('npTownBtn', townName(st.town), false); $('npTownBtn').disabled = false; }
            if ($('npTownIn')) { $('npTownIn').value = lot.town; $('npProvIn').value = lot.province; }
        }
        if (H.area) {
            const a = Number(lot.areaHa);
            st.areaUnit = lot.areaUnit === 'sqm' || a < 0.1 ? 'sqm' : 'ha';
            $('npArea').value = String(a < 0.1 ? Math.round(a * 10000) : Math.round(a * 10000) / 10000);
            tagSay('npAreaUnit', AREA_U, st.areaUnit);
        }
        if (H.loc) $('npLoc').value = lot.where;
        mountCard();
        // The lot's answers are the field in detail; applyProfile fills the simple questions from them.
        if (FPC) await FPC.set(lot.answers || {}, { id: lot.id, name: lot.name, answers: JSON.parse(JSON.stringify(lot.answers || {})), edited: false });
        // The lot's soil test log (2026-10-16): its newest results fill the soil test, unless it was typed here.
        const T = window.aneeFieldProfile && window.aneeFieldProfile.tests;
        const tl = T ? T.latest(T.list(lot.answers || {})) : null;
        if (tl && !TOUCH.soilTest) {
            const kit = {}; if (tl.nL) kit.N = tl.nL; if (tl.pL) kit.P2O5 = tl.pL; if (tl.kL) kit.K2O = tl.kL;
            if (SOIL.some(([k]) => tl[k] != null) || Object.keys(kit).length) {
                $('npSoilOn').checked = true; $('npSoil').classList.add('is-on');
                $('npSoilGrid').querySelectorAll('input').forEach((i) => { i.value = tl[i.dataset.s] != null ? tl[i.dataset.s] : ''; });
                st.pMethod = tl.pMethod === 'bray' ? 'bray' : 'olsen'; st.kUnit = tl.kUnit === 'meq' ? 'meq' : 'ppm';
                tagSay('npPMethod', P_METHOD, st.pMethod); tagSay('npKUnit', K_UNIT, st.kUnit);
                st.kit = Object.keys(kit).length ? kit : null;
                kitSay(lot.name + ', the soil test of ' + T.date(tl));
            }
        }
        if (LOT !== lot) return;
        if (H.place) seasonSoon();
        hints(); calc(); paintLot();
    }
    function clearLot() {
        if (!LOT) return;
        // The questions unfold, still filled from the lot: change what differs.
        LOT = null;
        if (LOTP) LOTP.set(null);
        paintLot(); hints(); calc();
    }
    function mountLots() {
        if (LOTP || !$('npLots') || !window.aneeLots) return;
        LOTP = window.aneeLots.picker($('npLots'), { crop: () => st.crop || null, onPick: (lot) => { if (lot) applyLot(lot); else clearLot(); } });
    }
    $('npNoLot')?.addEventListener('click', clearLot);
    document.addEventListener('click', (e) => {
        if (!e.target.closest('[data-lot-edit]') || !LOT || !window.aneeLots) return;
        // A save repaints the picker, and the picker hands the lot back (onPick): it is applied again.
        window.aneeLots.edit(LOT, { crop: st.crop || null });
    });

    const save = async () => {
        if (!RES || !RES.lines.length) return null;
        // A lot's answers are already kept in the lot: no second copy of them (lotId).
        const r = await window.api(U.save, { method: 'POST', body: { id: calcId, crop: st.crop, areaHa: RES.areaHa || 0.0001, targetYield: RES.goal ? RES.goal.tPerHa : null, lines: RES.lines, soil: soil(), result: RES, ...(FPC ? FPC.payload() : {}), lotId: LOT ? LOT.id : null, keepProfile: !document.documentElement.classList.contains('is-frame') && !LOT } });
        calcId = r.data.id;
        return r;
    };
    $('npSave').addEventListener('click', async () => { try { const r = await save(); window.toast?.(r.message + ' It is on the Saved tab.', 'success'); } catch (err) { window.toast?.(err.message, 'error'); } });
    $('npAnee').addEventListener('click', () => {
        $('npRun').disabled = !OPT.canUse;
        if (!$('npLoc').value.trim()) { const [tw, pv] = placeWords(); if (pv) $('npLoc').value = (tw ? tw + ', ' : '') + pv; }
        $('npRunSays').textContent = OPT.canUse ? 'Ask Anee · ' + OPT.quote + ' credits' : 'Anee is not on your plan';
        $('npRunFine').innerHTML = OPT.canUse ? 'You have ' + (window.creditCoin ? window.creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString()) : OPT.balance) + '. Charged only when the reading is ready.' : esc(OPT.whyNot || '');
        // The field in detail is answered in step 5; here it is said that it goes with the plan.
        const n = FPC ? FPC.count() : 0, box = $('npAneeField');
        // With a lot, its line says the place and the answers that go with it.
        if (LOT) { $('npLoc').value = LOT.where || $('npLoc').value; paintLot(); }
        box.hidden = !!LOT;
        box.textContent = n ? 'Your field in detail goes with it: ' + n + (n === 1 ? ' answer' : ' answers') + ' from step 5, and what the calculator counted for them.'
            : 'Tip: answer Your field in detail in step 5 first. The calculator counts it, and Anee reads it too.';
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
                water: st.water, plantingDate: $('npPlanted').value || null, timing: ans.timing, variety: $('npVariety').value.trim(), notes: $('npNotes').value.trim(), ...(FPC ? FPC.payload() : {}),
                lotId: LOT ? LOT.id : null, lotName: LOT ? LOT.name : null } });
            const d = r.data && r.data.status === 'ready' ? r.data : await window.aneeWait.poll({ id: r.data.id, job: U.job });
            await window.aneeWait.done({ title: 'Your plan, read.', line: 'Here is what to keep and what to change.' });
            OPT.balance = d.balance;
            showReport(d.report, d.params);
        } catch (err) { window.aneeWait.fail(); window.toast?.(err.message || 'The reading did not finish. Nothing was charged.', 'error'); }
        finally { $('npRun').disabled = !OPT.canUse; }
    });
    const showReport = (rep, params) => {
        const a = (rep || {}).anee || {};
        const field = params && (params.fieldProfileSaid || []).length && window.aneeFieldCard ? '<div class="np-card"><h3>Your field in detail</h3>' + window.aneeFieldCard.said(params.fieldProfileSaid) + '</div>' : '';
        const v = String(a.verdict || '').toLowerCase().replace(/\s+/g, '-');
        const sec = (t, inner) => inner ? '<div class="np-card"><h3>' + esc(t) + '</h3><div class="np-rlist">' + inner + '</div></div>' : '';
        $('npRep').hidden = false;
        $('npRep').innerHTML = '<div class="np-rhead v-' + esc(v) + '"><small>Anee\'s verdict: ' + esc(a.verdict) + ' · ' + esc(rep.at || '') + (params && params.lotName ? ' · ' + esc(params.lotName) : '') + '</small><h3>' + esc(a.headline) + '</h3><p>' + esc(a.summary) + '</p></div>'
            + sec('Nutrient by nutrient', (a.nutrients || []).map((x) => '<div class="np-ritem"><span class="np-st s-' + esc(String(x.status || '').toLowerCase()) + '">' + esc(x.status) + '</span><b>' + esc(x.nutrient) + '</b> · ' + esc(x.comment) + '</div>').join(''))
            + sec('When to apply each one', (a.timing || []).map((x) => '<div class="np-ritem"><b>' + esc(x.product) + '</b> · ' + esc(x.when) + '<br>' + esc(x.how) + ' <span class="text-gray-500">' + esc(x.why) + '</span></div>').join(''))
            + sec('What to change', (a.changes || []).map((x) => '<div class="np-ritem"><b>' + esc(x.change) + '</b><br><span class="text-gray-500">' + esc(x.why) + '</span></div>').join(''))
            + sec('Your soil, the season and the yield', ['soil', 'weather', 'biofertilizers', 'yield'].filter((k) => a[k]).map((k) => '<div class="np-ritem">' + esc(a[k]) + '</div>').join(''))
            + sec('Be careful', (a.warnings || []).filter(Boolean).map((w) => '<div class="np-ritem">' + esc(w) + '</div>').join(''))
            + field
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
            applyCalc(r.data);
        } catch (err) { window.toast?.(err.message, 'error'); }
    });
    /* A saved calculation, or one Anee Auto hands over (same shape), laid
       back into every step. */
    const applyCalc = (d) => {
        {
            const set = (d.result || {}).setup || {};
            calcId = d.id || null;
            $('npVariety').value = set.variety || '';
            // The variety's source: a record (its potential the record's, or the farmer's own), or a typed name.
            st.varPick = set.varietyPick && set.varietyPick.name ? set.varietyPick : (set.variety ? { source: 'custom', name: set.variety } : null);
            TOUCH.potential = !!(set.varietyPick && set.varietyPick.potentialFrom === 'own');
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
            st.intensive = !!set.intensive; $('npIntensive').checked = st.intensive;
            st.zincRecent = !!set.zincRecent; $('npZincRecent').checked = st.zincRecent;
            st.microsOn = set.microsOn !== false; $('npMicrosOn').checked = st.microsOn; paintMicroTab();
            st.area = set.area && typeof set.area === 'object' ? { where: set.area.where || '', tests: set.area.tests || {}, filled: set.area.filled || {} } : null;
            st.areaNo = false;
            const s = d.soil || {};
            $('npPh').value = set.ph != null ? set.ph : (s.ph != null ? s.ph : '');
            st.kit = s.levels && typeof s.levels === 'object' ? s.levels : null;
            const hasTest = SOIL.some(([k]) => s[k] != null) || !!st.kit;
            kitSay('');
            $('npSoilOn').checked = hasTest; $('npSoil').classList.toggle('is-on', hasTest);
            $('npSoilGrid').querySelectorAll('input').forEach((i) => { i.value = s[i.dataset.s] != null ? s[i.dataset.s] : ''; });
            st.pMethod = s.pMethod === 'bray' ? 'bray' : 'olsen'; st.kUnit = s.kUnit === 'meq' ? 'meq' : 'ppm';
            tagSay('npPMethod', P_METHOD, st.pMethod); tagSay('npKUnit', K_UNIT, st.kUnit);
            soilTag();
            st.lines = (d.lines || []).filter((l) => OPT.products.some((p) => p.id === l.id)).map((l) => ({ id: l.id, amount: l.amount, unit: l.unit }));
            tab(false); lines(); setCrop(d.crop || null); go(5);
            // A saved plan's answers are the farmer's: the field in detail fills none of them.
            Object.assign(TOUCH, { water: true, texture: st.texture !== 'unsure', conds: st.conds.length > 0, ph: phVal() != null, habits: true });
            varPaintTag(); potPaint();
            if (d.analysis) showReport(d.analysis.report, d.analysis.params); else $('npRep').hidden = true;
            mountCard();
            const fp = set.fieldProfile || (d.analysis && d.analysis.params && d.analysis.params.fieldProfile);
            if (LOTP && set.lotId) {
                // Made for a farm lot: it opens on that lot again, with its answers as they are now.
                LOT = null;
                LOTP.set(set.lotId).then(() => { if (!LOT) { if (FPC) FPC.set(fp || {}, null); paintLot(); } });
            } else {
                if (LOTP && LOT) { LOT = null; LOTP.set(null); paintLot(); }
                if (FPC) FPC.set(fp || {}, null);
            }
        }
    };
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
            paintPhCats();
            pills($('npTiming'), OPT.timing, 'timing');
            // The optional extra step, said with its price: the calculator itself is free.
            if (OPT.quote) $('npAneeSays').textContent = 'Analyze further with Anee · ' + OPT.quote + ' credits';
            soilTag(); lines(); hints(); calc();
            if (window.aneeFieldCard) mountCard(); else document.addEventListener('DOMContentLoaded', mountCard, { once: true });
            if (window.aneeLots) mountLots(); else document.addEventListener('DOMContentLoaded', mountLots, { once: true });
        } catch (err) { window.toast?.(err.message || 'Could not load the calculator.', 'error'); }
    };
    if (window.api) boot(); else window.addEventListener('load', boot, { once: true });

    /* ---- inside Anee Auto (2026-10-12) ----
       The season's window sends the lot (crop, variety, goal, area, place,
       planting day, water, soil) or the plan already made for it; the plan
       goes back with "Use this plan". Only to the same site's window. */
    if (document.documentElement.classList.contains('is-frame') && window.parent !== window) {
        const tell = (m) => window.parent.postMessage(m, location.origin);
        let waits = 0;
        const ready = () => { if (OPT) tell({ type: 'npk-ready' }); else if (waits++ < 80) setTimeout(ready, 150); };
        ready();
        window.addEventListener('message', async (e) => {
            if (e.origin !== location.origin || !e.data || e.data.type !== 'auto-prefill') return;
            const p = e.data.data || {};
            try {
                if (p.calcId) { applyCalc((await window.api(U.one(p.calcId))).data); }
                else {
                    applyCalc({ id: null, crop: p.crop || null, areaHa: p.areaHa || 1, targetYield: p.target || null, lines: [], soil: p.ph != null ? { ph: p.ph } : {},
                        result: { setup: { variety: p.variety || '', areaUnit: p.areaUnit || 'ha', areaValue: p.areaValue || 1, yUnit: 't',
                            potential: p.potential ? { value: p.potential, key: 't' } : null, target: p.target ? { value: p.target } : null,
                            prov: p.prov || null, town: p.town || null, water: p.water || 'irrigated', planted: p.planted || '', texture: p.texture || 'unsure', conditions: p.conditions || [], ph: p.ph != null ? p.ph : null } } });
                    // The season's field in detail (Anee Auto keeps one): the calculator counts it too.
                    if (p.fieldProfile && FPC) FPC.set(p.fieldProfile, null);
                }
                $('npFrameSay').textContent = p.say || 'Add the fertilizers you plan to use on this lot.';
            } catch (err) { window.toast?.(err.message, 'error'); }
        });
        const useBtn = $('npFrameUse');
        setInterval(() => { if (useBtn) useBtn.disabled = !(RES && RES.lines && RES.lines.length); }, 600);
        useBtn?.addEventListener('click', async () => {
            useBtn.disabled = true; useBtn.textContent = 'Saving…';
            try {
                const r = await save();
                tell({ type: 'npk-plan', calcId: r.data.id, needs: RES.needs, micros: RES.micros, reach: RES.reach, harvest: RES.harvest, npk: RES.npkPerHa, goal: RES.goal });
            } catch (err) { window.toast?.(err.message, 'error'); }
            useBtn.textContent = 'Use this plan';
        });
    }
})();
</script>
@endpush
