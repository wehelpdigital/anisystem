@extends('layouts.app')
@section('title', 'Crop Protocol Analysis')
{{-- A tool page: no bottom tab bar on a phone and no footer (2026-10-15, the owner). --}}
@section('body-class', 'hide-tabbar no-footer')
@section('page-title', 'Crop Protocol Analysis')
@section('page-subtitle', 'Your season, lot by lot')

@section('back', \App\Support\BackTo::url(route('app.dashboard')))
{{-- Back is the dashboard, or wherever the door that opened this page said
     it stood (?from=, App\Support\BackTo). --}}

{{-- CROP PROTOCOL for the farm's lots (2026-10-18, the owner: "just like the
     land prep module, you can select multiple lots and for each lot you select
     what crop to plant, the variety, month to plant, and how will you plant
     it, as well as the goal and yield ... how many workers you have ... auto
     adjust schedule if there's conflicting or warn if # of workers is
     lacking"). The walk: the lots (Global Lots, several), then for each lot
     what it grows and its goal, the field questions it has not answered, what
     fertilizer is sold near the farm, the crew (My workers, days off, the
     sprayers, who plants, and what happens when jobs collide), the place when
     no lot brings one, the review with the work calendar and the price. The
     report (format 4) is the whole farm and a tab a lot; older reports keep
     their own drawing (drawV2, drawV1). The arithmetic is the server's
     (CropProtocolController, App\Support\ProtocolCrew, ProtocolCare). --}}

@section('content')
<style>
    /* ---- CROP PROTOCOL ----------------------------------------------
       The fourth of the Quick Tools: the sisters' wizard walk, veil and
       shelf, the wtp-* dress copied whole — and a report that is a season
       laid out stage by stage, with the fertilizer drawn as bars and the
       shopping list added up. */
    .wtp-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .wtp-tab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem;
        text-align: center; color: var(--color-gray-500); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; }
    .wtp-tab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }

    .wtp-quote { border-radius: .9rem; margin-bottom: 1rem; overflow: hidden;
        background: linear-gradient(115deg, #f3f8ec, #e4efd4); border: 1px solid #cfe3b8; }
    .wtp-quote b { color: #2d5016; }
    .q-head { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left;
        padding: .7rem .9rem; cursor: pointer; }
    .q-head .q-ico { font-size: 1.15rem; flex: none; }
    .q-title { flex: 1 1 auto; min-width: 0; font-size: .84rem; font-weight: 800; color: #2d5016; }
    .q-hint { flex: none; font-size: .74rem; font-weight: 700; color: #3d5226; opacity: 0;
        transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-hint { opacity: .8; }
    .q-c { flex: none; width: 1rem; height: 1rem; color: #3d5226; opacity: .6;
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-c { transform: rotate(-90deg); }
    .q-body { overflow: hidden; opacity: 1;
        transition: max-height .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-body { max-height: 0; opacity: 0; }
    .q-body-in { overflow: hidden; min-height: 0; display: grid; gap: .5rem; padding: 0 .9rem; }
    .q-body-in::after { content: ''; height: .4rem; }
    .q-card { border-radius: .7rem; padding: .6rem .75rem; font-size: .82rem; color: #3d5226;
        line-height: 1.5; background: rgb(255 255 255 / .6); border: 1px solid rgb(207 227 184 / .8); }

    .wtp-wiz { position: relative; overflow: hidden; }
    /* Held until the options and the price card above are painted, then
       shown in one fade: before, the first step showed, and a moment later
       the price card landed on top of it and shoved it down the screen, so
       the step seemed to arrive twice. Gives up and shows itself after six
       seconds, so a page whose options never come is still usable. */
    .an-wait { visibility: hidden; position: relative; min-height: 14rem;
        animation: anWaitGiveUp 0s 6s forwards; }
    .an-wait::before { content: ''; visibility: visible; position: absolute; left: 50%; top: 5rem;
        width: 1.6rem; height: 1.6rem; margin-left: -.8rem; border-radius: 999px;
        border: 3px solid var(--color-brand-100, #dcebc9); border-top-color: var(--color-brand-600, #4e7a2a);
        animation: anWaitSpin .8s linear infinite, anWaitGone 0s 6s forwards; }
    @keyframes anWaitSpin { to { transform: rotate(360deg); } }
    @keyframes anWaitGone { to { opacity: 0; } }
    @keyframes anWaitGiveUp { to { visibility: visible; } }
    .an-in { animation: anIn .28s cubic-bezier(.22,1,.36,1) both; }
    @keyframes anIn { from { opacity: 0; } to { opacity: 1; } }
    @media (prefers-reduced-motion: reduce) { .an-in { animation: none; } .an-wait::before { animation: none; } }
    .wtp-step { display: none; }
    /* Steps fade, never slide (2026-10-16, the owner). */
    .wtp-step.is-on { display: block; animation: wtpIn .26s ease .1s both; }
    @keyframes wtpIn { from { opacity: 0; } to { opacity: 1; } }
    .wtp-step.is-back { animation-name: wtpBack; }
    @keyframes wtpBack { from { opacity: 0; } to { opacity: 1; } }
    .wtp-dots { display: flex; gap: .3rem; justify-content: center; margin: 1rem 0 .2rem; flex-wrap: wrap; }
    .wtp-dot { width: .5rem; height: .5rem; border-radius: 999px; background: var(--color-gray-200);
        transition: all .28s cubic-bezier(.22,1,.36,1); }
    .wtp-dot.is-on { width: 1.2rem; background: var(--color-brand-600); }
    .wtp-q { font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); margin-bottom: .2rem; }
    .wtp-sub { font-size: .8rem; color: var(--color-gray-500); margin-bottom: .8rem; }

    .wtp-choices { display: grid; gap: .5rem; }
    .wtp-choices.is-two { grid-template-columns: 1fr 1fr; }
    .wtp-choice { display: flex; align-items: center; gap: .7rem; padding: .75rem .85rem; border-radius: .9rem;
        border: 1.5px solid var(--color-gray-200); background: var(--color-white); cursor: pointer;
        text-align: left; font-weight: 700; color: var(--color-gray-800); font-size: .9rem;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .2s, background .2s; }
    .wtp-choice:hover { transform: translateY(-1px); }
    .wtp-choice.is-on { border-color: var(--color-brand-600); background: var(--color-brand-50); color: var(--color-brand-800); }
    .wtp-choice .c-e { font-size: 1.3rem; }
    .wtp-choice small { display: block; font-weight: 500; font-size: .72rem; color: var(--color-gray-500); }

    .wtp-probs { display: grid; grid-template-columns: 1fr; gap: .4rem; }
    @media (min-width: 640px) { .wtp-probs { grid-template-columns: 1fr 1fr; } }
    .wtp-prob { display: flex; align-items: center; gap: .55rem; padding: .55rem .7rem; border-radius: .7rem;
        border: 1.5px solid var(--color-gray-200); background: var(--color-white); cursor: pointer;
        font-size: .8rem; font-weight: 600; color: var(--color-gray-700);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .wtp-prob input { accent-color: #4a7c2a; width: 1rem; height: 1rem; flex: none; }
    .wtp-prob.is-on { border-color: var(--color-brand-500); background: var(--color-brand-50); }

    .wtp-nav { display: flex; gap: .6rem; margin-top: 1.1rem; }
    .wtp-run { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%;
        padding: .85rem 1rem; border-radius: 1rem; color: #fff; font-weight: 800; font-size: .95rem;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 30%, #3d6823 55%, #6b9f3d 80%, #8fc96a);
        background-size: 260% 100%; animation: wtpTide 5.5s ease-in-out infinite alternate;
        box-shadow: 0 10px 22px -12px rgb(61 104 35 / .65); }
    .wtp-run:disabled { opacity: .6; }
    @keyframes wtpTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }

    /* The crop tag and its sheet — the lot form's dress. */
    .crop-tag { display: flex; align-items: center; gap: .5rem; width: 100%;
        padding: .65rem .8rem; border-radius: .8rem; cursor: pointer; text-align: left;
        border: 1.5px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .2s, background .2s; }
    .crop-tag:hover { border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .crop-tag-e { font-size: 1.1rem; line-height: 1; flex: none; }
    .crop-tag-t { flex: 1 1 auto; min-width: 0; font-size: .9rem; font-weight: 700; color: #3d6823;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .crop-tag-t.is-none { color: var(--color-gray-400); font-weight: 500; }
    .crop-tag-c { width: 1rem; height: 1rem; flex: none; color: var(--color-gray-400); }
    html.dark .crop-tag { background: #1c2416; border-color: #2b3a1c; }
    html.dark .crop-tag-t { color: #a5c97e; }
    .crop-search { position: relative; margin-bottom: .6rem; }
    .crop-search svg { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%);
        width: 1.05rem; height: 1.05rem; color: var(--color-gray-400); pointer-events: none; }
    .crop-search .form-input { padding-left: 2.4rem; padding-right: 2.2rem; }
    .crop-search-x { position: absolute; right: .5rem; top: 50%; transform: translateY(-50%);
        width: 1.6rem; height: 1.6rem; border-radius: 999px; color: var(--color-gray-400); }
    .crop-search-x:hover { background: var(--color-gray-100); }
    .crop-group-h { font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        color: var(--color-gray-400); margin: .8rem 0 .25rem; }
    .crop-row { display: flex; align-items: center; gap: .65rem; width: 100%; text-align: left;
        padding: .5rem .6rem; border-radius: .7rem; cursor: pointer; }
    .crop-row:hover { background: var(--color-brand-50); }
    .crop-row-e { font-size: 1.25rem; line-height: 1; flex: none; }
    .crop-row-t { min-width: 0; }
    .crop-row-t b { display: block; font-size: .875rem; font-weight: 700; color: var(--color-gray-900); }
    .crop-row-t small { display: block; font-size: .7rem; color: var(--color-gray-400); }
    .crop-none { font-size: .8rem; color: var(--color-gray-400); text-align: center; padding: 1rem 0; }
    html.dark .crop-row:hover { background: #22301a; }
    html.dark .crop-row-t b { color: #e8efe1; }

    /* The target yield: a number and a unit that is a pair of pills. */
    .cp-yield { display: flex; gap: .5rem; align-items: stretch; }
    .cp-yield .form-input { flex: 1 1 auto; min-width: 0; }
    .cp-units { display: flex; border: 1.5px solid var(--color-gray-200); border-radius: .8rem; overflow: hidden; flex: none; }
    .cp-unit { padding: 0 .8rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-500); background: var(--color-white); cursor: pointer; }
    .cp-unit.is-on { background: var(--color-brand-600); color: #fff; }
    html.dark .cp-units { border-color: #2b3a1c; }
    html.dark .cp-unit { background: #151b12; color: #93a684; }
    html.dark .cp-unit.is-on { background: #4a7c2a; color: #fff; }
    .cp-area { position: relative; }
    .cp-area .form-input { padding-right: 5rem; }
    .cp-area-u { position: absolute; right: .9rem; top: 50%; transform: translateY(-50%); font-size: .8rem; font-weight: 700; color: var(--color-gray-400); pointer-events: none; }

    /* NPK Plus's questions, asked here too (2026-10-16, the owner): the
       variety from the records with its potential, how much is planted, a
       tested pH, and the need they make, shown before the run. */
    @keyframes cpRise { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
    /* The lot (2026-10-16, the owner): what it answered is not asked again. */
    .cp-blk[hidden] { display: none; }
    .cp-lotnote { display: flex; align-items: flex-start; gap: .5rem; margin: 0 0 .8rem; padding: .55rem .75rem; border-radius: .85rem; font-size: .8rem; line-height: 1.5;
        color: #24400f; background: #f3f8ec; border: 1px solid #d5e7c0; animation: cpRise .3s cubic-bezier(.22,1,.36,1) both; }
    .cp-lotnote[hidden] { display: none; }
    .cp-lotnote svg { flex: none; width: 1rem; height: 1rem; margin-top: .15rem; color: #4a7c2a; }
    .cp-lotline { display: block; margin-top: .35rem; }
    .cp-lotline button { font-weight: 800; color: #3d6823; text-decoration: underline; text-underline-offset: 3px; }
    .wtp-dot[hidden] { display: none; }
    html.dark .cp-lotnote { color: #cfe6b8; background: #17220f; border-color: #2b3a1c; }
    html.dark .cp-lotline button { color: #a5c97e; }
    @media (prefers-reduced-motion: reduce) { .cp-lotnote { animation: none; } }
    .wtp-varfacts, .cp-from { display: flex; align-items: flex-start; gap: .5rem; margin-top: .6rem; padding: .6rem .75rem; border-radius: .85rem; font-size: .8rem; line-height: 1.5;
        color: #24400f; background: #f3f8ec; border: 1px solid #d5e7c0; animation: cpRise .3s cubic-bezier(.22,1,.36,1) both; }
    .wtp-varfacts[hidden], .cp-from[hidden] { display: none; }
    .wtp-varfacts svg, .cp-from svg { flex: none; width: 1rem; height: 1rem; margin-top: .15rem; color: #4a7c2a; }
    .wtp-varfacts.is-own, .cp-from.is-own { color: var(--color-gray-600); background: var(--color-gray-50); border-color: var(--color-gray-200); }
    .wtp-varfacts.is-own svg, .cp-from.is-own svg { color: var(--color-gray-400); }
    .cp-from button { font-weight: 800; text-decoration: underline; text-underline-offset: 3px; color: #3d6823; }
    .cp-units.is-free { width: max-content; max-width: 100%; }
    .cp-units.is-free .cp-unit { padding: .55rem 1rem; }
    .cp-pills { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .55rem; }
    .cp-pill { padding: .38rem .75rem; border-radius: 999px; font-size: .78rem; font-weight: 700; line-height: 1.2; cursor: pointer;
        color: var(--color-gray-600); background: var(--color-white); border: 1.5px solid var(--color-gray-200);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .cp-pill.is-on { color: #fff; background: var(--color-brand-600); border-color: var(--color-brand-600); }
    .cp-two { display: grid; grid-template-columns: 1fr; gap: .75rem; margin-top: .75rem; }
    @media (min-width: 520px) { .cp-two { grid-template-columns: 1fr 1fr; } }
    .cp-two[hidden] { display: none; }
    .cp-hint { margin-top: .45rem; font-size: .78rem; line-height: 1.5; color: var(--color-gray-500); transition: color .28s cubic-bezier(.22,1,.36,1); }
    .cp-hint.is-warn { color: #92400e; }
    .cp-need { margin-top: 1rem; padding: .85rem .95rem; border-radius: 1rem; text-align: left; background: #f3f8ec; border: 1px solid #d5e7c0; animation: cpRise .3s cubic-bezier(.22,1,.36,1) both; }
    .cp-need[hidden] { display: none; }
    .cp-need-h { font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #3d6823; }
    .cp-need-n { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .55rem; }
    .cp-need-n span { flex: 1 1 0; min-width: 5.2rem; padding: .5rem .6rem; border-radius: .8rem; background: #fff; border: 1px solid #dfeccf; }
    .cp-need-n b { display: block; font-size: 1.15rem; font-weight: 800; line-height: 1.2; color: #1f2a17; }
    .cp-need-n small { font-size: .7rem; color: var(--color-gray-500); }
    .cp-need p { margin-top: .55rem; font-size: .8rem; line-height: 1.55; color: #3f4a37; }
    .cp-need ul { margin-top: .35rem; padding-left: 1.1rem; list-style: disc; font-size: .78rem; line-height: 1.5; color: var(--color-gray-600); }
    .cp-need.is-wait p { color: var(--color-gray-500); }
    html.dark .wtp-varfacts, html.dark .cp-from, html.dark .cp-need { color: #cfe6b8; background: #17220f; border-color: #2b3a1c; }
    html.dark .wtp-varfacts.is-own, html.dark .cp-from.is-own { color: #b9caa8; background: #121a0d; border-color: #2b3a1c; }
    html.dark .cp-from button, html.dark .cp-need-h { color: #a5c97e; }
    html.dark .cp-pill { color: #b9caa8; background: #151b12; border-color: #2b3a1c; }
    html.dark .cp-pill.is-on { color: #fff; background: #4a7c2a; border-color: #4a7c2a; }
    html.dark .cp-hint.is-warn { color: #fbbf24; }
    html.dark .cp-need-n span { background: #151b12; border-color: #2b3a1c; }
    html.dark .cp-need-n b { color: #e8efe1; }
    html.dark .cp-need p { color: #cdd8c0; }
    @media (prefers-reduced-motion: reduce) { .wtp-varfacts, .cp-from, .cp-need { animation: none; } .cp-pill, .cp-hint { transition: none; } }

    /* ---- THE REPORT ---- */
    .wtp-report { display: grid; gap: .9rem; }
    /* A grid item's automatic minimum is its content's width — a chart's
       scroll box would widen the whole report without this. */
    .wtp-report > * { min-width: 0; max-width: 100%; }
    /* The per-program painter holds two cards (stages, totals) in one grid
       child; without its own gap the totals card sat flush on the stages. */
    [data-cp3-fert] { display: grid; gap: .9rem; }
    [data-cp3-fert] > * { min-width: 0; max-width: 100%; }
    .wtp-hero { border-radius: 1.1rem; padding: 1.1rem 1.2rem; color: #fff;
        background: linear-gradient(130deg, #4a7c2a, #2d5016 70%); }
    .wtp-hero h2 { font-size: 1.15rem; font-weight: 800; margin-bottom: .15rem; }
    .wtp-hero .h-win { font-size: 1.25rem; font-weight: 800; letter-spacing: .01em; line-height: 1.3; }
    .wtp-hero .h-why { font-size: .84rem; opacity: .92; line-height: 1.55; margin-top: .45rem; }
    .wtp-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .6rem; }
    .wtp-chip { font-size: .68rem; font-weight: 700; padding: .18rem .55rem; border-radius: 999px;
        background: rgb(255 255 255 / .18); }

    .wtp-card { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        padding: 1rem 1.1rem; }
    .wtp-card h3 { font-weight: 800; font-size: .92rem; color: var(--color-gray-900); margin-bottom: .6rem; }
    .wtp-card h3 small { display: block; font-size: .72rem; font-weight: 500; color: var(--color-gray-500); margin-top: .1rem; }

    /* The clock is the crop: one line the reader cannot miss. */
    .cp-clock { display: flex; gap: .7rem; align-items: flex-start; border-radius: 1rem; padding: .85rem 1rem;
        background: #fffbeb; border: 1px solid #fde68a; color: #78350f; font-size: .82rem; line-height: 1.5; }
    .cp-clock b { color: #92400e; }
    /* The opening note: the protocol is a guide and the farm decides. A warm
       panel with a green accent so it reads as advice, not a warning. */
    .cp2-guide { position: relative; display: flex; gap: .8rem; align-items: flex-start; padding: 1rem 1.05rem 1rem 1.15rem; border-radius: 1.1rem; overflow: hidden;
        background: linear-gradient(135deg, #f4f9ee 0%, #fdfaf0 100%); border: 1px solid #d9e8c8; }
    .cp2-guide::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; background: linear-gradient(180deg, #5c8f34, #b7862b); }
    .cp2-guide-e { flex: none; font-size: 1.55rem; line-height: 1.1; filter: drop-shadow(0 1px 1px rgba(0,0,0,.08)); }
    .cp2-guide-t { min-width: 0; flex: 1 1 auto; }
    .cp2-guide-t b { display: block; font-family: var(--font-heading); font-size: 1rem; line-height: 1.25; color: #2f5219; margin-bottom: .3rem; }
    .cp2-guide-t p { font-size: .84rem; line-height: 1.58; color: #3f4a37; }
    .cp2-guide-tags { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .65rem; }
    .cp2-guide-tags span { font-size: .7rem; font-weight: 700; padding: .22rem .6rem; border-radius: 999px; background: rgba(255,255,255,.75); border: 1px solid #cfe0bc; color: #3d6823; white-space: nowrap; }
    html.dark .cp2-guide { background: linear-gradient(135deg, #17200f 0%, #221d10 100%); border-color: #2f3f1f; }
    html.dark .cp2-guide::before { background: linear-gradient(180deg, #6b9f3d, #c9962f); }
    html.dark .cp2-guide-t b { color: #cfe6b8; }
    html.dark .cp2-guide-t p { color: #b7c2ad; }
    html.dark .cp2-guide-tags span { background: rgba(255,255,255,.05); border-color: #3d5028; color: #a5c97e; }
    .cp-clock .e { font-size: 1.3rem; flex: none; }
    html.dark .cp-clock { background: #2a2413; border-color: #4a3d16; color: #e0c26a; }
    html.dark .cp-clock b { color: #fcd34d; }

    /* The season's stages: a rail of numbered stops. */
    .cp-stages { display: grid; gap: .55rem; }
    .cp-stage { display: flex; gap: .7rem; padding: .65rem .7rem; border-radius: .85rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100);
        opacity: 0; transform: translateY(6px); transition: all .45s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .cp-stage { opacity: 1; transform: none; }
    .cp-stage-n { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        font-size: .74rem; font-weight: 800; background: var(--color-brand-600); color: #fff; }
    .cp-stage-b { min-width: 0; flex: 1 1 auto; }
    .cp-stage-b b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .cp-stage-b .signs { font-size: .76rem; color: var(--color-gray-600); line-height: 1.45; margin-top: .1rem; }
    .cp-stage-b .hint { font-size: .68rem; color: var(--color-gray-400); margin-top: .1rem; }
    .cp-stage-b ul { margin-top: .4rem; padding-left: 1rem; font-size: .78rem; line-height: 1.5; color: var(--color-gray-700); }
    .cp-stage-b ul li { list-style: disc; }
    html.dark .cp-stage { background: #10150c; border-color: #222b1a; }
    html.dark .cp-stage-b b { color: #e8efe1; }
    html.dark .cp-stage-b .signs, html.dark .cp-stage-b ul { color: #b7c2ad; }

    /* THE FERTILIZER, DRAWN: a bar per application, its height the bags
       for the whole field, split by product. */
    .cp-chart { display: flex; gap: .4rem; align-items: flex-end; height: 9rem; padding: .5rem .2rem 0; border-bottom: 1px solid var(--color-gray-200); overflow-x: auto; scrollbar-width: none; }
    .cp-chart::-webkit-scrollbar, .cp-lbls::-webkit-scrollbar { display: none; }
    .cp-lbls { scrollbar-width: none; }
    .cp-col { flex: 1 1 0; min-width: 3.2rem; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
    .cp-col-val { font-size: .66rem; font-weight: 800; color: var(--color-gray-700); margin-bottom: .2rem; white-space: nowrap; }
    .cp-bar { width: 70%; max-width: 2.6rem; display: flex; flex-direction: column-reverse; border-radius: .35rem .35rem 0 0; overflow: hidden;
        transform-origin: bottom; transform: scaleY(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .cp-bar { transform: scaleY(1); }
    .cp-seg { width: 100%; }
    .cp-lbls { display: flex; gap: .4rem; padding: .3rem .2rem 0; overflow-x: auto; }
    .cp-lbl { flex: 1 1 0; min-width: 3.2rem; font-size: .6rem; font-weight: 700; color: var(--color-gray-500); text-align: center; line-height: 1.2; }
    .cp-legend { display: flex; flex-wrap: wrap; gap: .3rem .7rem; margin-top: .6rem; }
    .cp-legend span { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 700; color: var(--color-gray-600); }
    .cp-legend i { width: .7rem; height: .7rem; border-radius: .2rem; display: inline-block; flex: none; }
    /* On a phone the legend is a list, one product per row, not columns
       that break where the names happen to. */
    @media (max-width: 639px) { .cp-legend { flex-direction: column; align-items: flex-start; gap: .35rem; } }
    .cp-fert { margin-top: .8rem; display: grid; gap: .45rem; }
    .cp-fert-row { display: flex; gap: .6rem; align-items: flex-start; padding: .55rem .65rem; border-radius: .75rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .cp-fert-row .st { flex: none; width: 6.5rem; }
    .cp-fert-row .st b { display: block; font-size: .78rem; color: var(--color-gray-900); }
    .cp-fert-row .st i { display: block; font-style: normal; font-size: .66rem; color: var(--color-gray-400); line-height: 1.3; }
    .cp-fert-row .pr { flex: 1 1 auto; min-width: 0; display: grid; gap: .25rem; }
    .cp-fert-row .pr div { font-size: .78rem; color: var(--color-gray-700); line-height: 1.4; }
    .cp-fert-row .pr div b { color: var(--color-gray-900); }
    .cp-fert-row .pr div small { color: var(--color-gray-400); font-size: .68rem; }
    .cp-totals { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .7rem; }
    .cp-total { font-size: .72rem; font-weight: 800; padding: .3rem .6rem; border-radius: 999px; background: var(--color-brand-50); color: var(--color-brand-800); border: 1px solid var(--color-brand-200); }
    .cp-total i { font-style: normal; font-weight: 600; opacity: .7; }
    .cp-note { font-size: .76rem; color: var(--color-gray-500); line-height: 1.5; margin-top: .6rem; }
    html.dark .cp-chart { border-color: #2b3a1c; }
    html.dark .cp-col-val { color: #d5e3c5; }
    html.dark .cp-fert-row { background: #10150c; border-color: #222b1a; }
    html.dark .cp-fert-row .st b, html.dark .cp-fert-row .pr div b { color: #e8efe1; }
    html.dark .cp-fert-row .pr div { color: #b7c2ad; }
    html.dark .cp-total { background: #22301a; color: #cfe6b8; border-color: #3d5226; }
    html.dark .cp-legend span { color: #b7c2ad; }

    /* Protection: one card per pest / disease / weed, colour by kind. */
    .cp-prot { display: grid; gap: .5rem; }
    @media (min-width: 640px) { .cp-prot { grid-template-columns: 1fr 1fr; } }
    .cp-pc { border-radius: .8rem; padding: .6rem .7rem; border: 1px solid; font-size: .76rem; line-height: 1.45; }
    .cp-pc b { display: block; font-size: .84rem; margin-bottom: .1rem; }
    .cp-pc .tag { display: inline-block; font-size: .6rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; padding: .1rem .45rem; border-radius: 999px; margin-bottom: .3rem; }
    .cp-pc p { margin-top: .15rem; }
    .cp-pc p em { font-style: normal; font-weight: 700; }
    .cp-pc.is-insect { background: #fff7ed; border-color: #fed7aa; color: #7c2d12; }
    .cp-pc.is-insect .tag { background: #ffedd5; color: #9a3412; }
    .cp-pc.is-disease { background: #fdf2f8; border-color: #fbcfe8; color: #831843; }
    .cp-pc.is-disease .tag { background: #fce7f3; color: #9d174d; }
    .cp-pc.is-weed { background: #f0fdf4; border-color: #bbf7d0; color: #14532d; }
    .cp-pc.is-weed .tag { background: #dcfce7; color: #166534; }
    html.dark .cp-pc.is-insect { background: #2a1a0e; border-color: #5a3a1a; color: #fdba74; }
    html.dark .cp-pc.is-disease { background: #2a1020; border-color: #5a2040; color: #f9a8d4; }
    html.dark .cp-pc.is-weed { background: #10240f; border-color: #1e4a1e; color: #86efac; }
    html.dark .cp-pc .tag { background: rgb(255 255 255 / .1); color: inherit; }

    /* Water: a row per stage with a drop meter. */
    .cp-water { display: grid; gap: .45rem; }
    .cp-wrow { display: flex; gap: .6rem; padding: .55rem .65rem; border-radius: .75rem; background: #eff6ff; border: 1px solid #bfdbfe; }
    .cp-wrow .e { flex: none; font-size: 1.1rem; }
    .cp-wrow .t { min-width: 0; font-size: .78rem; color: #1e3a8a; line-height: 1.45; }
    .cp-wrow .t b { display: block; color: #1e40af; font-size: .82rem; }
    .cp-wrow .t i { display: block; font-style: normal; font-size: .7rem; color: #3b82f6; margin-top: .15rem; }
    html.dark .cp-wrow { background: #0f1a2e; border-color: #1e3a8a; }
    html.dark .cp-wrow .t { color: #bfdbfe; }
    html.dark .cp-wrow .t b { color: #dbeafe; }

    /* Yield: target against realistic, as two bars. */

    /* ---- the five programs: tag-like tabs over the fertilizer card ---- */
    .cp3-tabs { display: flex; gap: .4rem; overflow-x: auto; scrollbar-width: none; padding: .1rem .1rem .5rem; margin: -.1rem -.1rem .3rem; }
    .cp3-tabs::-webkit-scrollbar { display: none; }
    .cp3-tab { flex: none; display: flex; flex-direction: column; align-items: flex-start; gap: .05rem; padding: .4rem .75rem; border-radius: 999px; border: 1.5px solid var(--color-gray-200); background: var(--color-white); cursor: pointer; font: inherit; text-align: left;
        transition: border-color .2s, background .2s, transform .28s cubic-bezier(.22,1,.36,1); }
    .cp3-tab:hover { transform: translateY(-1px); }
    .cp3-tab b { font-size: .78rem; color: var(--color-gray-800); white-space: nowrap; }
    .cp3-tab small { font-size: .62rem; font-weight: 700; color: var(--color-gray-400); white-space: nowrap; }
    .cp3-tab.is-on { border-color: var(--color-brand-600); background: var(--color-brand-50); }
    .cp3-tab.is-on b { color: var(--color-brand-800); }
    .cp3-tab.is-on small { color: var(--color-brand-700); }
    .cp3-for { font-size: .76rem; color: var(--color-gray-600); line-height: 1.45; margin: 0 0 .6rem; padding: .5rem .65rem; border-radius: .7rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .cp3-orphans { margin-top: .5rem; padding: .5rem .6rem; border-radius: .7rem; background: var(--color-gray-50); font-size: .76rem; color: var(--color-gray-600); line-height: 1.5; }
    .cp3-orphans b { display: block; font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: var(--color-gray-400); margin-bottom: .2rem; }
    .cp3-gran { display: grid; gap: .7rem; }
    .cp3-gran h3 { margin-bottom: 0; }
    .cp3-gran-row { display: flex; gap: .65rem; align-items: flex-start; }
    .cp3-gran-row .e { flex: none; font-size: 1.2rem; line-height: 1.2; }
    .cp3-gran-row > div { min-width: 0; flex: 1 1 auto; }
    .cp3-gran-row b { display: block; font-size: .84rem; color: var(--color-gray-900); margin-bottom: .15rem; }
    .cp3-gran-row p { font-size: .82rem; line-height: 1.55; color: var(--color-gray-700); }
    /* The roads not taken: what a farmer here would have done, and why not on this ground. */
    .cp3-no { display: grid; gap: .4rem; margin-top: .3rem; }
    .cp3-no-row { padding: .45rem .6rem .45rem .75rem; border-left: 3px solid var(--color-gray-300); border-radius: 0 .6rem .6rem 0; background: var(--color-gray-50); }
    .cp3-no-row b { display: block; font-size: .8rem; color: var(--color-gray-800); margin: 0 0 .1rem; text-decoration: line-through; text-decoration-color: rgba(220, 38, 38, .55); text-decoration-thickness: 1.5px; }
    .cp3-no-row p { font-size: .78rem; line-height: 1.5; color: var(--color-gray-600); }
    html.dark .cp3-no-row { background: #10150c; border-left-color: #3d5028; }
    html.dark .cp3-no-row b { color: #d5e3c5; }
    html.dark .cp3-no-row p { color: #a3b095; }
    .wtp-choices.is-four { grid-template-columns: 1fr 1fr; }
    /* The granulars: a pick-many cloud of pills -- ten products fit a phone
       in four rows instead of ten. A tick appears on the chosen ones. */
    .wtp-choices.is-pills { display: flex; flex-wrap: wrap; gap: .4rem; }
    .wtp-choices.is-pills .wtp-choice { gap: .35rem; padding: .42rem .7rem; border-radius: 999px; font-size: .78rem; }
    .wtp-choices.is-pills .wtp-choice .c-e { font-size: .8rem; width: .95rem; height: .95rem; border-radius: 50%; border: 1.5px solid var(--color-gray-300); display: inline-flex; align-items: center; justify-content: center; color: transparent; transition: background .2s, border-color .2s, color .2s; }
    .wtp-choices.is-pills .wtp-choice.is-on .c-e { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .wtp-choices.is-pills .wtp-choice.is-on .c-e::before { content: '✓'; font-size: .62rem; font-weight: 800; line-height: 1; }
    html.dark .wtp-choices.is-pills .wtp-choice .c-e { border-color: #3d5028; }
    html.dark .wtp-choices.is-pills .wtp-choice.is-on .c-e { background: #6b9f3d; border-color: #6b9f3d; }
    /* The sub-question headings on the closer-look steps (as on What to Plant). */
    .wp-qh { font-size: .84rem; font-weight: 800; color: var(--color-gray-800); margin-bottom: .45rem; }
    .wp-qh small { display: block; font-weight: 500; font-size: .7rem; color: var(--color-gray-400); }
    html.dark .wp-qh { color: #e8efe1; }
    html.dark .cp3-tab { background: #151b12; border-color: #2b3a1c; }
    html.dark .cp3-tab b { color: #d5e3c5; }
    html.dark .cp3-tab small { color: #93a684; }
    html.dark .cp3-tab.is-on { background: #22301a; border-color: #6b9f3d; }
    html.dark .cp3-tab.is-on b { color: #cfe6b8; }
    html.dark .cp3-tab.is-on small { color: #a5c97e; }
    html.dark .cp3-for, html.dark .cp3-orphans { background: #10150c; border-color: #222b1a; color: #b7c2ad; }
    html.dark .cp3-gran-row b { color: #e8efe1; }
    html.dark .cp3-gran-row p { color: #b7c2ad; }
    @media (prefers-reduced-motion: reduce) { .cp3-tab { transition: none; } }

    /* ---- the two-part protocol ---- */
    .cp2-part { display: flex; align-items: center; gap: .6rem; margin: .4rem 0 -.3rem; }
    .cp2-part-n { flex: none; width: 1.9rem; height: 1.9rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; font-size: .85rem; background: #3d6823; color: #fff; }
    .cp2-part b { display: block; font-family: var(--font-heading); font-size: 1.05rem; color: var(--color-gray-900); line-height: 1.1; }
    .cp2-part small { display: block; font-size: .72rem; color: var(--color-gray-500); }
    .cp2-bg { display: grid; gap: .8rem; }
    .cp2-bg-row { display: flex; gap: .7rem; align-items: flex-start; }
    .cp2-bg-row + .cp2-bg-row { padding-top: .8rem; border-top: 1px solid var(--color-gray-100); }
    .cp2-bg-e { flex: none; font-size: 1.25rem; line-height: 1.2; }
    .cp2-bg-row > div { min-width: 0; flex: 1 1 auto; }
    .cp2-bg-row b { display: block; font-size: .9rem; color: var(--color-gray-900); margin-bottom: .2rem; }
    .cp2-bg-row b small { font-weight: 600; color: var(--color-gray-500); font-size: .72rem; }
    .cp2-bg-row p { font-size: .84rem; line-height: 1.55; color: var(--color-gray-700); }
    .cp2-dim { color: var(--color-gray-500) !important; font-size: .78rem !important; margin-top: .2rem; }
    .cp2-pills { display: flex; flex-wrap: wrap; gap: .35rem; margin: .4rem 0; }
    .cp2-pill { font-size: .7rem; font-weight: 700; padding: .22rem .6rem; border-radius: 999px; background: var(--color-brand-50); color: var(--color-brand-800); border: 1px solid var(--color-brand-100); }
    .cp2-pill.is-risk { background: #fff1e6; color: #9a3412; border-color: #fdd7b0; }
    .cp2-pill.is-src { background: var(--color-gray-100); color: var(--color-gray-600); border-color: var(--color-gray-200); }
    .wp-watch { font-size: .76rem; color: #92610e; margin-top: .3rem; }
    /* the stage picker */
    /* The chart fits the screen whatever the count of stages: the columns
       share the width (no minimum, no sideways scroll) and the labels are
       the stage numbers, which the rows under the chart spell out. */
    .cp2-chart { overflow: hidden; gap: .25rem; padding-left: 0; padding-right: 0; }
    .cp2-chart .cp2-col { min-width: 0; border: 0; background: transparent; padding: 0; cursor: pointer; border-radius: .5rem .5rem 0 0; transition: background .2s; }
    .cp2-chart .cp-bar { width: 72%; max-width: 2.4rem; }
    .cp2-chart .cp-col-val { font-size: .62rem; }
    .cp2-lbls { overflow: hidden; gap: .25rem; padding-left: 0; padding-right: 0; }
    .cp2-lbls .cp-lbl { min-width: 0; }
    .cp2-chart .cp2-col:hover { background: var(--color-gray-50); }
    .cp2-chart .cp2-col.is-sel { background: var(--color-brand-50); }
    .cp2-chart .cp2-col.is-sel .cp-col-val { color: var(--color-brand-800); }
    .cp2-lbls .cp-lbl.is-sel { color: var(--color-brand-800); }
    .cp2-detail { margin-top: .8rem; padding: .75rem .85rem; border-radius: .9rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-200); transition: opacity .28s; min-height: 4rem; }
    .cp2-detail.is-swap { opacity: 0; }
    .cp2-d-head { display: flex; align-items: center; gap: .6rem; }
    .cp2-d-head > span:nth-child(2) { flex: 1 1 auto; min-width: 0; }
    .cp2-d-head b { display: block; font-size: .92rem; color: var(--color-gray-900); }
    .cp2-d-head small { display: block; font-size: .68rem; color: var(--color-gray-400); }
    .cp2-d-nav { flex: none; display: flex; gap: .25rem; }
    .cp2-d-nav button { width: 1.8rem; height: 1.8rem; border-radius: 999px; border: 1px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-700); font-size: 1rem; line-height: 1; cursor: pointer; }
    .cp2-d-nav button:disabled { opacity: .3; cursor: default; }
    .cp2-d-signs { font-size: .8rem; color: var(--color-gray-600); line-height: 1.5; margin-top: .4rem; }
    .cp2-d-fert { display: grid; gap: .4rem; margin-top: .55rem; }
    .cp2-d-app { display: flex; gap: .5rem; align-items: flex-start; font-size: .82rem; line-height: 1.45; color: var(--color-gray-700); }
    .cp2-d-app i { flex: none; width: .7rem; height: .7rem; border-radius: .2rem; margin-top: .3rem; }
    .cp2-d-app > span { min-width: 0; flex: 1 1 auto; }
    .cp2-d-app b { color: var(--color-gray-900); }
    .cp2-d-app small { color: var(--color-gray-400); font-size: .7rem; white-space: nowrap; }
    .cp2-d-app em { display: block; font-style: normal; font-size: .76rem; color: var(--color-brand-800); }
    /* Label on its own line, the words under it, everything on one left
       edge -- a pill beside a paragraph left the text hanging off its side. */
    .cp2-d-obs { display: block; margin-top: .6rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); }
    .cp2-d-obs span { display: inline-flex; align-items: center; font-size: .66rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; padding: .18rem .5rem; border-radius: 999px; margin: 0 0 .25rem; }
    .cp2-d-obs .is-obs { background: #e6f2d8; color: #2f5219; }
    .cp2-d-obs .is-act { background: #fff1e6; color: #9a3412; }
    .cp2-d-obs p { margin: 0; }
    /* Every grid here is one bounded column: a track that may grow to its
       content is how a long row pushed the whole report sideways. */
    .cp2-rows, .cp2-tot, .cp2-bg, .cp2-shop, .cp2-d-fert { grid-template-columns: minmax(0, 1fr); }
    .cp2-rows { display: grid; gap: .25rem; margin-top: .6rem; }
    .cp2-row { display: grid; grid-template-columns: 1.5rem minmax(0, 1fr) auto; grid-template-areas: 'n t b' '. s s'; column-gap: .6rem; row-gap: 0; align-items: start; width: 100%; min-width: 0; max-width: 100%; text-align: left; border: 0; background: transparent; padding: .45rem .5rem; border-radius: .7rem; cursor: pointer; font: inherit; color: var(--color-gray-700); transition: background .2s; }
    .cp2-row:hover { background: var(--color-gray-50); }
    .cp2-row.is-sel { background: var(--color-brand-50); }
    .cp2-row-n { flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 800; background: var(--color-brand-100); color: var(--color-brand-800); }
    .cp2-row.is-sel .cp2-row-n { background: #3d6823; color: #fff; }
    /* A grid, not a row: the name and the bags share the first line, and
       the line under the name runs the full width beneath them both, so a
       second product is not squeezed into the gap beside the bags figure. */
    .cp2-row-t { display: contents; }
    .cp2-row-t b { grid-area: t; display: block; font-size: .82rem; line-height: 1.3; color: var(--color-gray-900); min-width: 0; }
    .cp2-row-b { grid-area: b; }
    .cp2-row-n { grid-area: n; }
    /* The line under the name: the day count as a pill, then what goes on.
       It wraps as it needs to; a clamp cut a second product mid-word. */
    .cp2-row-t small { grid-area: s; display: block; margin-top: .2rem; font-size: .68rem; line-height: 1.5; color: var(--color-gray-400); min-width: 0; }
    .cp2-row-t small .cp2-days { margin-right: .35rem; vertical-align: middle; }
    .cp2-row-b { flex: none; font-size: .74rem; font-weight: 800; color: var(--color-brand-800); padding-top: .1rem; }
    .cp2-row-n { margin-top: .05rem; }
    /* totals */
    .cp2-tot { display: grid; gap: .5rem; }
    .cp2-tot-row { display: grid; grid-template-columns: 1fr auto auto; grid-template-areas: "n b s" "tr tr tr"; align-items: center; gap: .15rem .5rem; font-size: .78rem; }
    .cp2-tot-row .n { grid-area: n; display: flex; align-items: center; gap: .35rem; font-weight: 700; color: var(--color-gray-800); min-width: 0; }
    .cp2-tot-row .n i { flex: none; width: .7rem; height: .7rem; border-radius: .2rem; }
    .cp2-tot-row b { grid-area: b; }
    .cp2-tot-row small { grid-area: s; }
    .cp2-tot-row .tr { grid-area: tr; height: .6rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .cp2-tot-row .tr span { display: block; height: 100%; border-radius: 999px; transform-origin: left; transform: scaleX(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .cp2-tot-row .tr span { transform: scaleX(1); }
    .cp2-tot-row b { color: var(--color-gray-900); white-space: nowrap; }
    .cp2-tot-row small { color: var(--color-gray-400); font-size: .68rem; white-space: nowrap; }
    /* The nutrient check: a bar of what the program delivers, a tick where the need sits. */
    .cp2-nut { margin-top: .9rem; padding-top: .7rem; border-top: 1px dashed var(--color-gray-200); display: grid; grid-template-columns: minmax(0, 1fr); gap: .45rem; }
    .cp2-nut-h b { display: block; font-size: .82rem; color: var(--color-gray-900); }
    .cp2-nut-h small { display: block; font-size: .68rem; color: var(--color-gray-400); line-height: 1.4; }
    .cp2-nut-row { display: grid; grid-template-columns: 2.6rem minmax(0, 1fr) auto; grid-template-areas: "l tr v" "vd vd vd"; align-items: center; gap: .1rem .5rem; font-size: .78rem; }
    .cp2-nut-row .l { grid-area: l; font-weight: 800; color: var(--color-gray-700); }
    .cp2-nut-row .tr { grid-area: tr; position: relative; height: .7rem; border-radius: 999px; background: var(--color-gray-100); overflow: visible; }
    .cp2-nut-row .have { display: block; height: 100%; border-radius: 999px; background: var(--color-brand-500); transform-origin: left; transform: scaleX(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .cp2-nut-row .have { transform: scaleX(1); }
    .cp2-nut-row .need { position: absolute; top: -.25rem; bottom: -.25rem; width: 3px; border-radius: 2px; background: var(--color-gray-800); transform: translateX(-50%); }
    .cp2-nut-row .v { grid-area: v; font-variant-numeric: tabular-nums; color: var(--color-gray-700); white-space: nowrap; }
    .cp2-nut-row .v b { color: var(--color-gray-900); }
    .cp2-nut-row .v small { display: block; font-size: .64rem; color: var(--color-gray-400); text-align: right; }
    .cp2-nut-row .vd { grid-area: vd; font-size: .7rem; font-weight: 700; color: var(--color-gray-500); padding-left: 3.1rem; }
    .cp2-nut-row.is-ok .vd { color: #166534; }
    .cp2-nut-row.is-short .vd { color: #b45309; }
    .cp2-nut-row.is-short .have { background: #f0b04a; }
    .cp2-nut-row.is-over .vd { color: var(--color-gray-500); }
    html.dark .cp2-nut { border-color: #2b3a1c; }
    html.dark .cp2-nut-h b, html.dark .cp2-nut-row .v b { color: #e8efe1; }
    html.dark .cp2-nut-row .l, html.dark .cp2-nut-row .v { color: #d5e3c5; }
    html.dark .cp2-nut-row .tr { background: #222b1a; }
    html.dark .cp2-nut-row .need { background: #e8efe1; }
    html.dark .cp2-nut-row.is-ok .vd { color: #86efac; }
    html.dark .cp2-nut-row.is-short .vd { color: #fcd34d; }
    /* The secondary and micro elements (2026-10-17, the owner): the same rows, NPK Plus's verdicts. */
    .cp2-nut-sub { margin-top: .35rem; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .cp2-nut-row.is-el .l { font-size: .74rem; }
    .cp2-nut-row.is-right .vd, .cp2-nut-row.is-testok .vd { color: #166534; }
    .cp2-nut-row.is-spray .vd { color: #1d4ed8; }
    .cp2-nut-row.is-spray .have { background: #93c5fd; }
    .cp2-nut-row.is-ben .l::after { content: '*'; color: var(--color-gray-400); }
    .cp2-nut-quiet { font-size: .7rem; line-height: 1.45; color: var(--color-gray-400); }
    html.dark .cp2-nut-row.is-right .vd, html.dark .cp2-nut-row.is-testok .vd { color: #86efac; }
    html.dark .cp2-nut-row.is-spray .vd { color: #93c5fd; }
    .cp-need-m { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .45rem; }
    .cp-need-m span { padding: .25rem .55rem; border-radius: 999px; font-size: .74rem; font-weight: 700; color: #1f2a17; background: #fff; border: 1px solid #dfeccf; }
    .cp-need-m span small { font-weight: 600; color: var(--color-gray-500); }
    html.dark .cp-need-m span { color: #e8efe1; background: #151b12; border-color: #2b3a1c; }
    .cp2-npk { display: flex; flex-wrap: wrap; align-items: baseline; gap: .5rem .9rem; margin-top: .7rem; font-size: .78rem; color: var(--color-gray-600); }
    .cp2-npk b { color: var(--color-gray-900); font-size: .9rem; }
    .cp2-npk small { flex-basis: 100%; font-size: .68rem; color: var(--color-gray-400); }
    .cp2-shop { margin-top: .7rem; }
    .cp2-days { font-style: normal; font-weight: 800; font-size: .66rem; color: var(--color-brand-700); background: var(--color-brand-50); border: 1px solid var(--color-brand-100); border-radius: 999px; padding: .08rem .45rem; white-space: nowrap; vertical-align: middle; }
    html.dark .cp2-days { color: #a5c97e; background: #22301a; border-color: #2b3a1c; }
    .cp2-defs { display: grid; grid-template-columns: minmax(0, 1fr); gap: .5rem; }
    .cp2-def { padding: .65rem .75rem; border-radius: .85rem; background: #f7fbf2; border: 1px solid #cfe3bd; opacity: 0; transform: translateY(6px); transition: all .45s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .cp2-def { opacity: 1; transform: none; }
    .cp2-def-h { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: .2rem; }
    .cp2-def-h b { font-size: .88rem; color: var(--color-gray-900); }
    .cp2-def-h .tag { font-size: .62rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--color-brand-800); background: var(--color-brand-100); padding: .12rem .5rem; border-radius: 999px; }
    .cp2-def p { font-size: .78rem; line-height: 1.5; color: var(--color-gray-700); margin-top: .15rem; }
    .cp2-def em { font-style: normal; font-weight: 700; color: var(--color-gray-500); }
    html.dark .cp2-def { background: rgb(107 159 61 / .1); border-color: #2f4d24; }
    html.dark .cp2-def-h b { color: #e8efe1; }
    html.dark .cp2-def-h .tag { color: #cfe6b5; background: #2f4d24; }
    html.dark .cp2-def p { color: #b7c2ad; }
    @media (prefers-reduced-motion: reduce) { .cp2-def { transition: none; transform: none; opacity: 1; } }
    .cp2-threats { display: grid; gap: .5rem; }
    .cp2-threats { grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 640px) { .cp2-threats { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .cp2-threat p.is-prod { color: var(--color-brand-800); }
    .cp2-threat p.is-prod em { color: var(--color-brand-700); }
    html.dark .cp2-threat p.is-prod { color: #a5c97e; }
    .cp2-foliars { display: grid; grid-template-columns: minmax(0, 1fr); gap: .45rem; }
    .cp2-foliar { display: flex; gap: .6rem; align-items: flex-start; padding: .6rem .7rem; border-radius: .8rem; background: #f4faee; border: 1px solid #cfe3bd; }
    .cp2-foliar .e { flex: none; font-size: 1.1rem; }
    .cp2-foliar b { display: block; font-size: .82rem; color: var(--color-gray-900); }
    .cp2-foliar small { display: block; font-size: .66rem; font-weight: 700; color: var(--color-brand-700); text-transform: uppercase; letter-spacing: .04em; }
    .cp2-foliar p { font-size: .76rem; color: var(--color-gray-600); line-height: 1.45; margin-top: .1rem; }
    html.dark .cp2-foliar { background: rgb(107 159 61 / .1); border-color: #2f4d24; }
    html.dark .cp2-foliar b { color: #e8efe1; }
    html.dark .cp2-foliar small { color: #a5c97e; }
    html.dark .cp2-foliar p { color: #b7c2ad; }
    .cp2-d-fol { display: block; margin-top: .6rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); }
    .cp2-d-fol span { display: inline-flex; align-items: center; font-size: .66rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; padding: .18rem .5rem; border-radius: 999px; margin: 0 0 .25rem; background: #e0f2fe; color: #075985; }
    .cp2-d-fol p { margin: 0; }
    html.dark .cp2-d-fol { color: #b7c2ad; }
    html.dark .cp2-d-fol span { background: #0c2a3a; color: #7dd3fc; }
    .cp2-threat { padding: .6rem .7rem; border-radius: .8rem; background: #fff7ed; border: 1px solid #fed7aa; }
    .cp2-threat .tag { display: inline-block; font-size: .62rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #9a3412; margin-bottom: .15rem; }
    .cp2-threat b { display: block; font-size: .84rem; color: var(--color-gray-900); }
    .cp2-threat p { font-size: .76rem; line-height: 1.45; color: var(--color-gray-700); margin-top: .15rem; }
    .cp2-threat em { font-style: normal; font-weight: 700; color: var(--color-gray-500); }
    html.dark .cp2-part b { color: #e8efe1; }
    html.dark .cp2-bg-row + .cp2-bg-row { border-color: #222b1a; }
    html.dark .cp2-bg-row b, html.dark .cp2-d-head b, html.dark .cp2-d-app b, html.dark .cp2-row-t b, html.dark .cp2-tot-row b, html.dark .cp2-npk b, html.dark .cp2-threat b { color: #e8efe1; }
    html.dark .cp2-bg-row p, html.dark .cp2-d-app, html.dark .cp2-d-obs, html.dark .cp2-row, html.dark .cp2-threat p { color: #b7c2ad; }
    html.dark .cp2-pill { background: #22301a; color: #cfe6b8; border-color: #2b3a1c; }
    html.dark .cp2-pill.is-risk { background: #3a2a0a; color: #fcd34d; border-color: #5a3d10; }
    html.dark .cp2-pill.is-src { background: #1c2416; color: #93a684; border-color: #2b3a1c; }
    html.dark .cp2-chart .cp2-col.is-sel, html.dark .cp2-row.is-sel { background: #22301a; }
    html.dark .cp2-detail { background: #10150c; border-color: #2b3a1c; }
    html.dark .cp2-d-nav button { background: #1c2416; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .cp2-d-app em { color: #a5c97e; }
    html.dark .cp2-d-obs .is-obs { background: #2f4d24; color: #cfe6b5; }
    html.dark .cp2-d-obs .is-act { background: #3a2a0a; color: #fcd34d; }
    html.dark .cp2-tot-row .tr { background: #222b1a; }
    html.dark .cp2-threat { background: #2a1f10; border-color: #4a3416; }
    html.dark .cp2-threat .tag { color: #fdba74; }
    html.dark .wp-watch { color: #e0b95c; }
    .va-link.is-plain { color: var(--color-gray-700); cursor: default; }
    .va-link.is-plain:hover { background: transparent; }
    html.dark .va-link.is-plain { color: #d5e3c5; }
    @media (prefers-reduced-motion: reduce) { .cp2-detail, .cp2-tot-row .tr span, .cp2-row, .cp2-chart .cp2-col { transition: none; } }
    .cp-yo { display: grid; gap: .45rem; margin-top: .3rem; }
    .cp-yo-row { display: flex; align-items: center; gap: .6rem; font-size: .78rem; color: var(--color-gray-600); }
    .cp-yo-row small { flex: none; width: 5rem; font-weight: 700; }
    .cp-yo-row .tr { flex: 1 1 auto; height: .8rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .cp-yo-row .tr span { display: block; height: 100%; border-radius: 999px; background: var(--color-brand-500); transform-origin: left; transform: scaleX(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .cp-yo-row .tr span.is-target { background: #f59e0b; }
    .wtp-report.is-drawn .cp-yo-row .tr span { transform: scaleX(1); }
    .cp-yo-row b { flex: none; min-width: 4.5rem; text-align: right; color: var(--color-gray-900); }
    html.dark .cp-yo-row .tr { background: #222b1a; }
    html.dark .cp-yo-row b { color: #e8efe1; }

    /* The shopping list. */
    .cp-shop { display: grid; gap: .3rem; }
    .cp-item { display: flex; align-items: center; gap: .6rem; padding: .5rem .6rem; border-radius: .7rem; border: 1px dashed var(--color-gray-200); font-size: .8rem; }
    .cp-item .n { flex: 1 1 auto; min-width: 0; color: var(--color-gray-800); }
    .cp-item .n small { display: block; font-size: .66rem; color: var(--color-gray-400); }
    .cp-item .q { flex: none; font-weight: 800; color: var(--color-gray-900); text-align: right; }
    .cp-item .q small { display: block; font-size: .64rem; font-weight: 600; color: var(--color-gray-400); }
    html.dark .cp-item { border-color: #2b3a1c; }
    html.dark .cp-item .n, html.dark .cp-item .q { color: #e8efe1; }

    .wtp-win-row { display: block; padding: .6rem .7rem; border-radius: .7rem; margin-bottom: .45rem; font-size: .84rem; line-height: 1.55; border: 1px solid; }
    .wtp-win-row.is-no { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
    .wtp-win-row.is-note { background: #f7fbf2; border-color: #cfe3b8; color: #2d5016; }
    html.dark .wtp-win-row.is-no { background: #2a1414; border-color: #4c1d1d; color: #fca5a5; }
    html.dark .wtp-win-row.is-note { background: #1c2913; border-color: #2b3a1c; color: #cfe6b8; }
    .cp-watch li { list-style: none; position: relative; padding-left: 1.4rem; font-size: .82rem; line-height: 1.5; color: var(--color-gray-700); margin-bottom: .3rem; }
    .cp-watch li::before { content: '👀'; position: absolute; left: 0; top: 0; font-size: .8rem; }
    html.dark .cp-watch li { color: #d5e3c5; }
    .va-links { display: grid; gap: .3rem; }
    .va-link { display: flex; align-items: center; gap: .5rem; font-size: .78rem; color: var(--color-brand-700); text-decoration: none; padding: .35rem .5rem; border-radius: .6rem; min-width: 0; }
    .va-link:hover { background: var(--color-brand-50); }
    .va-link .l-t { min-width: 0; flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .va-link .l-h { flex: none; font-size: .66rem; color: var(--color-gray-400); max-width: 9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    html.dark .va-link { color: #a5c97e; }
    html.dark .va-link:hover { background: #22301a; }

    .wtp-gap { font-size: .78rem; color: var(--color-gray-500); line-height: 1.55; }
    .wtp-gap li { list-style: disc; margin-left: 1.1rem; }
    .wtp-fine { font-size: .72rem; color: var(--color-gray-400); line-height: 1.55; }
    .wtp-plain { font-size: .875rem; line-height: 1.65; color: var(--color-gray-700); }
    html.dark .wtp-plain { color: #d5e3c5; }
    .wtp-acts { display: grid; gap: .5rem; }
    @media (min-width: 640px) { .wtp-acts { grid-template-columns: 1fr 1fr; } }
    .wtp-saved { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left;
        padding: .8rem .9rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
    .wtp-saved:hover { background: var(--color-gray-50); }
    .wtp-saved b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .wtp-saved small { color: var(--color-gray-400); font-size: .72rem; }
    .wtp-anee-face { width: 1.15rem; height: 1.15rem; border-radius: 999px; object-fit: cover; }

    /* Full screen when it lands. */
    .va-view { position: fixed; inset: 0; z-index: 90; background: var(--color-gray-50); overflow-y: auto; -webkit-overflow-scrolling: touch;
        opacity: 0; transform: translateY(12px); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .va-view.is-on { opacity: 1; transform: none; }
    .va-view-bar { position: sticky; top: 0; z-index: 2; display: flex; align-items: center; gap: .6rem; padding: .7rem .9rem;
        padding-top: max(.7rem, env(safe-area-inset-top)); background: rgb(250 250 248 / .92); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        border-bottom: 1px solid var(--color-gray-200); }
    .va-view-bar b { flex: 1 1 auto; min-width: 0; font-size: .95rem; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .va-view-x { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-white); border: 1px solid var(--color-gray-200); color: var(--color-gray-700); font-size: 1rem; cursor: pointer; }
    .va-view-body { max-width: 42rem; margin: 0 auto; padding: 1rem 1rem calc(2rem + env(safe-area-inset-bottom)); }
    html.va-view-lock { overflow: hidden; }
    html.dark .va-view { background: #0d110a; }
    html.dark .va-view-bar { background: rgb(13 17 10 / .92); border-color: #2b3a1c; }
    html.dark .va-view-bar b { color: #e8efe1; }
    html.dark .va-view-x { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }

    html.dark .wtp-tab { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .wtp-tab.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .wtp-quote { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; }
    html.dark .wtp-quote b { color: #cfe6b8; }
    html.dark .q-title { color: #cfe6b8; }
    html.dark .q-hint, html.dark .q-c { color: #a8bd93; }
    html.dark .q-card { background: rgb(255 255 255 / .05); border-color: #2b3a1c; color: #a8bd93; }
    html.dark .wtp-q { color: #e8efe1; }
    html.dark .wtp-choice, html.dark .wtp-prob { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .wtp-choice.is-on { background: #22301a; border-color: #6b9f3d; color: #cfe6b8; }
    html.dark .wtp-prob.is-on { background: #22301a; border-color: #6b9f3d; }
    html.dark .wtp-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .wtp-card h3 { color: #e8efe1; }
    html.dark .wtp-saved { border-color: #222b1a; }
    html.dark .wtp-saved:hover { background: #161e10; }
    html.dark .wtp-saved b { color: #e8efe1; }

    @media (max-width: 639px) {
        .wtp-hero { padding: .9rem 1rem; }
        .wtp-card { padding: .8rem .85rem; }
        .wtp-choices.is-two { grid-template-columns: 1fr; }
        .cp-fert-row { flex-direction: column; gap: .3rem; }
        .cp-fert-row .st { width: auto; }
    }
    @media (prefers-reduced-motion: reduce) {
        .wtp-step.is-on { animation: none; }
        .wtp-run { animation: none; }
        .cp-stage, .cp-bar, .cp-yo-row .tr span, .wtp-dot { transition: none; transform: none; opacity: 1; }
        .q-body, .q-c, .q-hint, .wtp-prob, .crop-tag, .crop-row, .va-view { transition: none; }
    }
</style>

<style>
    /* ---- THE FARM WALK (2026-10-18, the owner): the lots first, then each lot's crop and goal,
       the crew, the review. The Land Prep Planner's dress, prefix cq-. ---- */
    .cq, .cq-sheet, .va-view { --cq-e: cubic-bezier(.22,1,.36,1); --cq-g: #4a7c2a; --cq-gd: #2d5016; --cq-gl: #e4efd4; --cq-gx: #f4f9ee; }
    html.dark .cq, html.dark .cq-sheet, html.dark .va-view { --cq-gx: #1a2414; --cq-gl: #2b3a1c; --cq-gd: #cfe6b8; }
    .cq { max-width: 40rem; margin: 0 auto; min-width: 0; }
    .cq-quote { display: flex; align-items: center; gap: .7rem; padding: .75rem .9rem; margin-bottom: .9rem; border-radius: 1rem; background: linear-gradient(115deg, #f3f8ec, #e4efd4); border: 1px solid #cfe3b8; font-size: .82rem; line-height: 1.5; color: #3d5226; animation: cqPop .3s var(--cq-e) both; }
    .cq-quote img { flex: none; width: 2.4rem; height: 2.4rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px #fff; }
    .cq-quote b { color: var(--cq-gd); }
    .cq-quote.is-off { background: #fff7ed; border-color: #fed7aa; color: #9a3412; }
    .cq-wiz { position: relative; padding: 1.1rem 1.1rem 1rem; border-radius: 1.3rem; background: var(--color-white); border: 1px solid var(--color-gray-200); box-shadow: 0 18px 40px -32px rgb(20 33 12 / .45); min-width: 0; }
    .cq-head { display: flex; align-items: center; gap: .6rem; margin-bottom: .2rem; min-width: 0; }
    .cq-head small { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .7rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--cq-g); }
    .cq-head span { flex: none; margin-left: auto; font-size: .72rem; font-weight: 800; color: var(--color-gray-400); }
    .cq-bar { height: .32rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; margin-bottom: 1rem; }
    .cq-bar i { display: block; height: 100%; width: 10%; border-radius: 999px; background: linear-gradient(90deg, #86b556, var(--cq-g)); transition: width .45s var(--cq-e); }
    .cq-step { display: none; min-width: 0; }
    .cq-step.is-on { display: block; animation: cqIn .26s ease .1s both; }
    .cq-step.is-back { animation-name: cqBack; }
    .cq-step.is-still { animation: none; }
    @keyframes cqIn { from { opacity: 0; } }
    @keyframes cqBack { from { opacity: 0; } }
    @keyframes cqPop { from { opacity: 0; transform: translateY(8px); } }
    .cq-q { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; line-height: 1.25; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .cq-sub { margin: .3rem 0 1rem; font-size: .84rem; line-height: 1.5; color: var(--color-gray-500); }
    .cq-sub-s { margin: -.15rem 0 .55rem; font-size: .78rem; }
    .cq-nav { display: flex; gap: .6rem; margin-top: 1.2rem; }
    .cq-nav .btn { flex: 1 1 0; min-height: 3rem; font-size: .95rem; }
    .cq-label { display: block; margin: 1.05rem 0 .4rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-700); }
    .cq-label small { font-weight: 600; color: var(--color-gray-400); }
    .cq-choices { display: grid; gap: .55rem; }
    @media (min-width: 560px) { .cq-choices.is-2 { grid-template-columns: 1fr 1fr; } }
    .cq-choice { width: 100%; display: flex; align-items: center; gap: .75rem; padding: .75rem .8rem; border-radius: 1rem; text-align: left; background: var(--color-white); border: 1.5px solid var(--color-gray-200); min-width: 0;
        transition: border-color .28s var(--cq-e), background-color .28s var(--cq-e), transform .28s var(--cq-e), box-shadow .28s var(--cq-e); }
    .cq-choice:hover { border-color: #a8cc7e; transform: translateY(-1px); }
    .cq-choice.is-on { border-color: var(--cq-g); background: var(--cq-gx); box-shadow: 0 0 0 3px rgb(107 159 61 / .16); }
    .cq-choice .ce { flex: none; width: 2.5rem; height: 2.5rem; border-radius: .85rem; display: grid; place-items: center; font-size: 1.3rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .cq-choice.is-on .ce { background: #fff; border-color: #d2e5bd; }
    .cq-choice .ct { min-width: 0; flex: 1; }
    .cq-choice .ct b { display: block; font-size: .93rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
    .cq-choice .ct small { display: block; margin-top: .1rem; font-size: .76rem; line-height: 1.4; color: var(--color-gray-500); }
    .cq-choice .ck { flex: none; width: 1.4rem; height: 1.4rem; border-radius: 999px; display: grid; place-items: center; color: #3b2f00; background: #f5c518; opacity: 0; transform: scale(.5); transition: opacity .28s var(--cq-e), transform .28s var(--cq-e); }
    .cq-choice .ck svg { width: .8rem; height: .8rem; }
    .cq-choice.is-on .ck { opacity: 1; transform: none; }
    .cq-tag { width: 100%; display: flex; align-items: center; gap: .7rem; padding: .6rem .7rem .6rem .6rem; border-radius: 1rem; text-align: left; background: #f6f8f3; border: 1px solid #dfe8d4; color: #14210c; min-width: 0;
        transition: border-color .28s var(--cq-e), background-color .28s var(--cq-e), box-shadow .28s var(--cq-e); }
    .cq-tag:hover:not(:disabled) { border-color: #a8cc7e; background: #fff; box-shadow: 0 10px 24px -20px rgb(20 33 12 / .6); }
    .cq-tag:disabled { opacity: .55; cursor: default; }
    .cq-tag .ti { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .85rem; display: grid; place-items: center; font-size: 1.3rem; background: #fff; border: 1px solid #e5ebdf; }
    .cq-tag .tt { min-width: 0; flex: 1; }
    .cq-tag .tt b { display: block; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; line-height: 1.25; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cq-tag .tt small { display: block; font-size: .76rem; color: #6b7280; line-height: 1.35; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cq-tag .tc { flex: none; display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: var(--cq-gd); background: var(--cq-gl); }
    .cq-tag .tc svg { width: .8rem; height: .8rem; }
    .cq-tag.is-none .tt b { color: var(--color-gray-500); font-weight: 700; }
    .cq-tag.is-bump .ti { animation: cqBump .42s var(--cq-e); }
    @keyframes cqBump { 40% { transform: scale(1.14) rotate(-6deg); } }
    .cq-tags2 { display: grid; gap: .5rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 520px) { .cq-tags2 { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .cq-ferr { margin-top: .45rem; font-size: .78rem; font-weight: 700; line-height: 1.4; color: #b91c1c; animation: cqPop .28s var(--cq-e) both; }
    .is-shake { animation: cqShake .42s var(--cq-e) both !important; }
    @keyframes cqShake { 20% { transform: translateX(-6px); } 40% { transform: translateX(5px); } 60% { transform: translateX(-3px); } 80% { transform: translateX(2px); } }
    .cq-hint { margin-top: .8rem; padding: .7rem .8rem; border-radius: .9rem; font-size: .8rem; line-height: 1.5; color: #3d5226; background: var(--cq-gx); border: 1px solid #e1edd3; animation: cqPop .3s var(--cq-e) both; }
    .cq-hint.is-warn { color: #92400e; background: #fffbeb; border-color: #fde68a; }
    .cq-hint b { color: inherit; }
    .cq-pills { display: flex; flex-wrap: wrap; gap: .4rem; }
    .cq-habits { margin-top: .9rem; }
    .cq-habits .cq-sub { margin: .35rem 0 0; }
    .cq-habits .cq-label + .cq-pills { margin-top: .1rem; }
    .cq-habits > div + .cq-label { margin-top: .7rem; }
    .cq-pill { min-height: 2.4rem; padding: .3rem .85rem; border-radius: 999px; font-size: .82rem; font-weight: 700; color: var(--color-gray-700); background: var(--color-white); border: 1.5px solid var(--color-gray-200);
        transition: border-color .28s var(--cq-e), background-color .28s var(--cq-e), color .28s var(--cq-e); }
    .cq-pill:hover { border-color: #a8cc7e; }
    .cq-pill.is-on { color: #fff; background: var(--cq-g); border-color: var(--cq-g); }
    .cq-two { display: grid; grid-template-columns: minmax(0, 1fr); gap: .7rem; }
    @media (min-width: 520px) { .cq-two { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .cq-field .form-input { width: 100%; min-height: 2.9rem; font-size: 1rem; }
    .cq-field > label { display: block; margin-bottom: .3rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-700); }
    .cq-field > label small { font-weight: 600; color: var(--color-gray-400); }
    .cq-from { display: flex; align-items: flex-start; gap: .5rem; margin-top: .45rem; padding: .55rem .7rem; border-radius: .8rem; font-size: .78rem; line-height: 1.5; color: #24400f; background: #f3f8ec; border: 1px solid #d5e7c0; animation: cqPop .3s var(--cq-e) both; }
    .cq-from.is-own { color: var(--color-gray-600); background: var(--color-gray-50); border-color: var(--color-gray-200); }
    .cq-from button { font-weight: 800; text-decoration: underline; text-underline-offset: 3px; color: #3d6823; }
    .cq-from[hidden] { display: none; }
    /* "Same as Lot 1": the lots before this one, a tap each. */
    .cq-same { display: grid; gap: .45rem; margin: 0 0 .3rem; animation: cqIn .26s ease both; }
    .cq-same[hidden] { display: none; }
    .cq-spick { width: 100%; display: flex; align-items: center; gap: .65rem; padding: .6rem .75rem; border-radius: .95rem; text-align: left; background: var(--color-white); border: 1.5px dashed #c9dcb3; min-width: 0;
        transition: border-color .28s var(--cq-e), background-color .28s var(--cq-e), box-shadow .28s var(--cq-e); }
    .cq-spick:hover { border-color: #a8cc7e; }
    .cq-spick > i { flex: none; width: .7rem; height: .7rem; border-radius: 999px; background: var(--lc); }
    .cq-spick > span:not(.ck) { min-width: 0; flex: 1; }
    .cq-spick b { display: block; font-size: .86rem; font-weight: 800; line-height: 1.3; color: var(--color-gray-900); }
    .cq-spick small { display: block; margin-top: .05rem; font-size: .74rem; line-height: 1.4; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .cq-spick .ck { flex: none; width: 1.3rem; height: 1.3rem; border-radius: 999px; display: grid; place-items: center; color: #3b2f00; background: #f5c518; opacity: 0; transform: scale(.5); transition: opacity .28s var(--cq-e), transform .28s var(--cq-e); }
    .cq-spick .ck svg { width: .75rem; height: .75rem; }
    .cq-spick.is-on { border-style: solid; border-color: var(--cq-g); background: var(--cq-gx); box-shadow: 0 0 0 3px rgb(107 159 61 / .16); }
    .cq-spick.is-on .ck { opacity: 1; transform: none; }
    /* Whose page it is. */
    .cq-who { display: flex; align-items: center; gap: .5rem; margin-bottom: .75rem; padding: .45rem .65rem; border-radius: .9rem; background: color-mix(in srgb, var(--lc, #4a7c2a) 9%, #fff); border: 1px solid color-mix(in srgb, var(--lc, #4a7c2a) 26%, #fff); min-width: 0; }
    .cq-who > i { flex: none; width: .7rem; height: .7rem; border-radius: 999px; background: var(--lc); box-shadow: 0 0 0 3px color-mix(in srgb, var(--lc, #4a7c2a) 22%, transparent); }
    .cq-who b { flex: none; font-size: .78rem; font-weight: 900; color: #1f2937; }
    .cq-who span { min-width: 0; flex: 1; font-size: .78rem; font-weight: 700; color: #4b5563; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    html.dark .cq-who { background: color-mix(in srgb, var(--lc, #4a7c2a) 16%, #151b12); border-color: color-mix(in srgb, var(--lc, #4a7c2a) 35%, #151b12); }
    html.dark .cq-who b { color: #e8efe1; }
    html.dark .cq-who span { color: #b7c2ad; }

    /* The lots. */
    .cq-lots { display: grid; gap: .7rem; }
    .cq-lot { position: relative; padding: .8rem .8rem .85rem; border-radius: 1.1rem; background: #fbfcfa; border: 1px solid var(--color-gray-200); border-left: 4px solid var(--lc); animation: cqPop .3s var(--cq-e) both; min-width: 0;
        transition: opacity .22s var(--cq-e), transform .22s var(--cq-e); }
    .cq-lot.is-gone { opacity: 0; transform: scale(.97); }
    .cq-lot-top { display: flex; align-items: center; gap: .45rem; margin-bottom: .55rem; padding-right: 2.2rem; min-width: 0; }
    .cq-lot-top i { flex: none; width: .7rem; height: .7rem; border-radius: 999px; background: var(--lc); }
    .cq-lot-top b { flex: none; font-size: .86rem; font-weight: 900; color: var(--color-gray-800); }
    .cq-lot-top em { min-width: 0; font-style: normal; font-size: .72rem; font-weight: 700; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cq-lot-x { position: absolute; top: .55rem; right: .55rem; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: grid; place-items: center; color: var(--color-gray-400); transition: background-color .28s var(--cq-e), color .28s var(--cq-e); }
    .cq-lot-x:hover { color: #b91c1c; background: #fee2e2; }
    .cq-lot-x svg { width: .95rem; height: .95rem; }
    .cq-flot { display: flex; align-items: flex-start; gap: .65rem; min-width: 0; padding: .6rem .65rem; border-radius: .9rem; background: #fff; border: 1px solid #e3eadb; }
    .cq-flot > span:first-child { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .8rem; display: grid; place-items: center; font-size: 1.2rem; background: var(--cq-gx); border: 1px solid #e1edd3; }
    .cq-flot > span:first-child img { width: 1.5rem; height: 1.5rem; object-fit: contain; }
    .cq-flot > div { flex: 1; min-width: 0; }
    .cq-flot b { display: block; font-size: .95rem; font-weight: 800; line-height: 1.3; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .cq-flot small { display: block; margin-top: .05rem; font-size: .76rem; line-height: 1.4; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .cq-flot small.is-none { color: #b45309; font-weight: 700; }
    .cq-flchips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .35rem; }
    .cq-flchips span { padding: .1rem .5rem; border-radius: 999px; font-size: .68rem; font-weight: 800; color: #3d5226; background: #eef5e6; }
    .cq-flchips span.is-ask { color: #92400e; background: #fef3c7; }
    .cq-flchips span.is-old { color: #92400e; background: #fde68a; }
    .cq-fledit { flex: none; min-height: 2.2rem; padding: .25rem .7rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: var(--cq-gd); background: var(--cq-gl); transition: background-color .28s var(--cq-e); }
    .cq-fledit:hover { background: #d9e9c4; }
    .cq-flprev { margin-top: .45rem; font-size: .76rem; font-weight: 800; color: #3d6823; text-decoration: underline; text-underline-offset: 3px; }
    .cq-size { display: flex; align-items: center; gap: .5rem; margin-top: .6rem; }
    .cq-size label { flex: none; font-size: .78rem; font-weight: 800; color: var(--color-gray-700); }
    .cq-size .form-input { flex: 1; min-width: 0; min-height: 2.6rem; font-weight: 700; }
    .cq-size span { flex: none; font-size: .8rem; font-weight: 700; color: var(--color-gray-500); }
    .cq-lotsnone { display: flex; align-items: center; gap: .75rem; padding: 1rem; border-radius: 1.1rem; background: var(--color-gray-50); border: 1px dashed var(--color-gray-300); animation: cqPop .3s var(--cq-e) both; }
    .cq-lotsnone > span { flex: none; font-size: 1.6rem; }
    .cq-lotsnone b { display: block; font-size: .92rem; font-weight: 800; color: var(--color-gray-900); }
    .cq-lotsnone small { display: block; margin-top: .15rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-500); }
    .cq-lot-acts { display: grid; margin-top: .8rem; }
    .cq-lot-acts .btn { min-height: 2.9rem; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; font-size: .88rem; }
    .cq-lot-acts .btn svg { width: 1.05rem; height: 1.05rem; }
    .cq-total { margin-top: .6rem; font-size: .8rem; font-weight: 700; color: var(--color-gray-500); text-align: right; }

    /* The need of one lot, live on its goal page. */
    .cq-need { margin-top: 1rem; padding: .8rem .9rem; border-radius: 1rem; background: #f3f8ec; border: 1px solid #d5e7c0; animation: cqPop .3s var(--cq-e) both; transition: opacity .2s ease; min-width: 0; }
    .cq-need.is-busy { opacity: .55; }
    .cq-need-h { font-size: .7rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #3d6823; }
    .cq-need-n { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem; margin-top: .5rem; }
    .cq-need-n span { padding: .45rem .55rem; border-radius: .8rem; background: #fff; border: 1px solid #dfeccf; min-width: 0; }
    .cq-need-n b { display: block; font-size: 1.1rem; font-weight: 800; line-height: 1.2; color: #1f2a17; }
    .cq-need-n small { font-size: .68rem; color: var(--color-gray-500); }
    .cq-need-m { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .45rem; }
    .cq-need-m span { padding: .2rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 700; color: #1f2a17; background: #fff; border: 1px solid #dfeccf; }
    .cq-need p { margin-top: .5rem; font-size: .78rem; line-height: 1.5; color: #3f4a37; }
    html.dark .cq-need { background: #17220f; border-color: #2b3a1c; }
    html.dark .cq-need-h { color: #a5c97e; }
    html.dark .cq-need-n span, html.dark .cq-need-m span { background: #151b12; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .cq-need-n b { color: #e8efe1; }
    html.dark .cq-need p { color: #cdd8c0; }

    /* The crew. */
    .cq-stepper { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
    .cq-stepper button { width: 3rem; height: 3rem; border-radius: 1rem; font-size: 1.4rem; font-weight: 800; color: var(--cq-gd); background: var(--cq-gl); transition: transform .28s var(--cq-e), background-color .28s var(--cq-e); }
    .cq-stepper button:active { transform: scale(.94); }
    .cq-stepper output { min-width: 2.8rem; text-align: center; font-family: var(--font-heading); font-size: 1.45rem; font-weight: 800; color: var(--color-gray-900); }
    .cq-stepper span { font-size: .86rem; font-weight: 700; color: var(--color-gray-500); }
    .cq-wks { display: grid; gap: .55rem; margin-top: .8rem; }
    .cq-wk { padding: .65rem .7rem; border-radius: 1rem; background: #fbfcfa; border: 1px solid var(--color-gray-200); animation: cqPop .28s var(--cq-e) both; min-width: 0; }
    .cq-wk.is-roster { border-color: #cfe3b8; background: #f7fbf2; }
    .cq-wk-top { display: flex; align-items: center; gap: .55rem; min-width: 0; }
    .cq-wk-top > i { flex: none; width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center; font-style: normal; font-size: .78rem; font-weight: 900; color: #fff; background: #6b9f3d; }
    .cq-wk-top input { flex: 1; min-width: 0; min-height: 2.5rem; padding: .35rem .65rem; border-radius: .7rem; border: 1.5px solid #e1e7da; background: #fff; font-size: .95rem; font-weight: 700; color: #14210c; transition: border-color .28s var(--cq-e); }
    .cq-wk-top input:focus { outline: none; border-color: #86b556; }
    .cq-wk-top small { flex: none; font-size: .72rem; font-weight: 800; color: var(--color-gray-500); }
    .cq-wk-from { display: inline-flex; align-items: center; gap: .25rem; margin: .4rem 0 0 2.55rem; padding: .1rem .5rem; border-radius: 999px; font-size: .68rem; font-weight: 800; color: var(--cq-gd); background: var(--cq-gl); }
    .cq-d7 { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .3rem; margin-top: .5rem; }
    .cq-d7 button { min-height: 2.35rem; border-radius: .7rem; font-size: .76rem; font-weight: 800; color: var(--color-gray-500); background: #fff; border: 1.5px solid #e3e8dc; transition: background-color .28s var(--cq-e), color .28s var(--cq-e), border-color .28s var(--cq-e), transform .2s var(--cq-e); }
    .cq-d7 button:active { transform: scale(.94); }
    .cq-d7 button.is-on { color: #fff; background: var(--cq-g); border-color: var(--cq-g); }
    .cq-samed { display: flex; justify-content: flex-end; margin-top: .55rem; }
    .cq-samed button { min-height: 2.4rem; padding: .3rem .85rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--cq-gd); background: var(--cq-gl); }
    .cq-savenew { display: flex; align-items: flex-start; gap: .6rem; margin-top: .7rem; padding: .65rem .75rem; border-radius: .9rem; background: var(--cq-gx); border: 1px solid #e1edd3; cursor: pointer; animation: cqPop .28s var(--cq-e) both; }
    .cq-savenew input { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .15rem; accent-color: #4a7c2a; }
    .cq-savenew b { display: block; font-size: .84rem; font-weight: 800; color: var(--color-gray-900); }
    .cq-savenew small { display: block; font-size: .74rem; line-height: 1.4; color: var(--color-gray-500); }
    .cq-savenew[hidden] { display: none; }
    html.dark .cq-wk, html.dark .cq-lot { background: #12170f; border-color: #2b3a1c; }
    html.dark .cq-lot { border-left-color: var(--lc); }
    html.dark .cq-wk.is-roster { background: #18210f; border-color: #2f4220; }
    html.dark .cq-wk-top input { background: #151b12; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .cq-d7 button { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .cq-d7 button.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .cq-savenew { background: #18210f; border-color: #2b3a1c; }
    html.dark .cq-savenew b { color: #e8efe1; }

    /* Will the crew keep up? Before the run, from the server's calendar. */
    .cq-cast { margin-top: 1rem; transition: opacity .2s ease; }
    .cq-cast:empty { margin: 0; }
    .cq-cast.is-busy { opacity: .55; }
    .cq-cast-c { padding: .8rem .85rem; border-radius: 1.05rem; background: #f8faf6; border: 1px solid #dfe8d4; min-width: 0; animation: cqPop .3s var(--cq-e) both; }
    .cq-cast-c.is-warn { background: #fffdf5; border-color: #fde68a; }
    .cq-cast-h { display: flex; align-items: center; gap: .5rem; min-width: 0; }
    .cq-cast-h > span { flex: none; font-size: 1.1rem; }
    .cq-cast-h b { min-width: 0; font-size: .92rem; font-weight: 800; line-height: 1.3; color: var(--color-gray-900); }
    .cq-cast-c > p { margin-top: .2rem; font-size: .76rem; line-height: 1.45; color: var(--color-gray-500); }
    .cq-cast-rows { display: grid; gap: .35rem; margin-top: .6rem; }
    .cq-cast-row { display: grid; grid-template-columns: auto minmax(0, 1fr); align-items: start; gap: .5rem; font-size: .78rem; animation: cqPop .3s var(--cq-e) both; }
    .cq-cast-row > b { display: flex; align-items: center; gap: .35rem; padding-top: .42rem; font-weight: 800; color: var(--color-gray-700); white-space: nowrap; }
    .cq-cast-row > b i { width: .6rem; height: .6rem; border-radius: 999px; background: var(--lc); }
    .cq-cast-row > span { min-width: 0; padding: .4rem .55rem; border-radius: .6rem; font-weight: 700; line-height: 1.4; overflow-wrap: anywhere; }
    .cq-cast-row > span small { display: block; margin-top: .12rem; font-size: .72rem; font-weight: 600; }
    .cq-cast-row .is-ok { color: #2d5016; background: #e9f3dd; }
    .cq-cast-row .is-late { color: #991b1b; background: #fde8e8; }
    .cq-cast-row .is-moved { color: #5b21b6; background: #efe9fd; }
    .cq-cast-do { display: grid; gap: .4rem; margin-top: .7rem; }
    .cq-cast-do p { font-size: .78rem; font-weight: 700; line-height: 1.45; color: var(--color-gray-700); }
    .cq-cast-do .btn { justify-self: start; min-height: 2.5rem; padding: .3rem .95rem; font-size: .84rem; }
    html.dark .cq-cast-c { background: #12170f; border-color: #2b3a1c; }
    html.dark .cq-cast-c.is-warn { background: #2a210a; border-color: #4d3a0d; }
    html.dark .cq-cast-h b, html.dark .cq-cast-row > b, html.dark .cq-cast-do p { color: #e8efe1; }
    html.dark .cq-cast-c > p { color: #b7c2ad; }
    html.dark .cq-cast-row .is-ok { color: #cfe6b8; background: #22301a; }
    html.dark .cq-cast-row .is-late { color: #fca5a5; background: #3a1414; }
    html.dark .cq-cast-row .is-moved { color: #d8ccfb; background: #2a1f45; }

    /* Review. */
    .cq-rev { display: grid; border-radius: 1rem; overflow: hidden; border: 1px solid var(--color-gray-200); }
    .cq-rev button { display: flex; align-items: center; gap: .7rem; padding: .7rem .8rem; text-align: left; background: #fff; min-width: 0; transition: background-color .28s var(--cq-e); }
    .cq-rev button + button { border-top: 1px solid var(--color-gray-100); }
    .cq-rev button:hover { background: var(--cq-gx); }
    .cq-rev .re { flex: none; font-size: 1.15rem; width: 1.6rem; text-align: center; }
    .cq-rev .re i { display: inline-block; width: .8rem; height: .8rem; border-radius: 999px; background: var(--lc); }
    .cq-rev .rt { min-width: 0; flex: 1; }
    .cq-rev .rt small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-400); }
    .cq-rev .rt b { display: block; font-size: .88rem; font-weight: 700; color: var(--color-gray-900); line-height: 1.35; overflow-wrap: anywhere; }
    .cq-rev .rt em { display: block; font-style: normal; font-size: .74rem; color: var(--color-gray-500); line-height: 1.4; overflow-wrap: anywhere; }
    .cq-rev .rc { flex: none; font-size: .72rem; font-weight: 800; color: var(--cq-g); }
    .cq-price { margin-top: .9rem; padding: .75rem .85rem; border-radius: 1rem; background: #fffbeb; border: 1px solid #fde68a; font-size: .8rem; line-height: 1.5; color: #78350f; animation: cqPop .3s var(--cq-e) both; }
    .cq-price b { color: #92400e; }
    html.dark .cq-price { background: #2a2413; border-color: #4a3d16; color: #e0c26a; }
    html.dark .cq-price b { color: #fcd34d; }
    /* The important call to action is yellow (the owner, 2026-10-17). */
    .cq-run { margin-top: 1rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: .55rem; min-height: 3.3rem; padding: .85rem 1rem; border-radius: 1.1rem; color: #3b2f00; font-weight: 800; font-size: 1rem;
        background: #f5c518; box-shadow: 0 12px 26px -14px rgb(181 140 0 / .75); transition: background-color .28s var(--cq-e), transform .28s var(--cq-e), box-shadow .28s var(--cq-e); }
    .cq-run:hover:not(:disabled) { background: #ffd23f; transform: translateY(-1px); }
    .cq-run:disabled { opacity: .55; }
    .cq-run img { width: 1.6rem; height: 1.6rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px rgb(255 255 255 / .7); }
    .cq-run svg.arr { width: 1.1rem; height: 1.1rem; animation: cqNudge 1.6s ease-in-out infinite; }
    @keyframes cqNudge { 50% { transform: translateX(4px); } }
    .cq-fine { margin-top: .5rem; text-align: center; font-size: .74rem; color: var(--color-gray-400); }
    .cq-err { margin: .2rem 0 .8rem; padding: .8rem .95rem; border-radius: 1rem; background: #fef2f2; border: 1px solid #fecaca; color: #7f1d1d; animation: cqIn .34s var(--cq-e) both; }
    .cq-err b { display: block; font-size: .9rem; font-weight: 800; }
    .cq-err p { margin-top: .2rem; font-size: .84rem; line-height: 1.45; }
    .cq-err[hidden] { display: none; }
    .cq-fqhost { min-height: 10rem; }

    /* ---- the sheets (dialogs in the top layer) ---- */
    .cq-sheet { padding: 0; border: 0; background: transparent; max-width: none; max-height: none; width: 100%; height: 100%; margin: 0; inset: 0; overflow: hidden; }
    .cq-sheet::backdrop { background: rgb(10 18 6 / .5); animation: cqFade .28s var(--cq-e, ease); }
    .cq-sheet.is-closing::backdrop { animation: cqFade .22s ease reverse forwards; }
    .cq-sbox { position: absolute; left: 0; right: 0; bottom: 0; max-height: 90vh; max-height: 90dvh; display: flex; flex-direction: column; border-radius: 1.4rem 1.4rem 0 0; background: #fff;
        box-shadow: 0 -20px 50px -20px rgb(10 18 6 / .5); animation: cqUp .34s cubic-bezier(.22,1,.36,1); }
    .cq-sheet.is-closing .cq-sbox { animation: cqUp .24s cubic-bezier(.22,1,.36,1) reverse forwards; }
    @media (min-width: 640px) {
        .cq-sbox { left: 50%; top: 50%; bottom: auto; right: auto; width: min(32rem, calc(100vw - 2rem)); max-height: min(46rem, 88vh); border-radius: 1.4rem; transform: translate(-50%, -50%); animation-name: cqPopC; }
        .cq-sheet.is-closing .cq-sbox { animation-name: cqPopC; }
        .cq-sheet.is-small .cq-sbox { width: min(26rem, calc(100vw - 2rem)); }
    }
    .cq-sheet.is-tall .cq-sbox { height: 86vh; height: 86dvh; }
    @media (min-width: 640px) { .cq-sheet.is-tall .cq-sbox { height: min(44rem, 88vh); } }
    @keyframes cqFade { from { opacity: 0; } }
    @keyframes cqUp { from { transform: translateY(100%); } }
    @keyframes cqPopC { from { opacity: 0; transform: translate(-50%, -46%) scale(.97); } }
    .cq-sbox:focus { outline: none; }
    .cq-grip { width: 2.6rem; height: .3rem; margin: .55rem auto 0; border-radius: 999px; background: #d7dfcd; flex: none; }
    @media (min-width: 640px) { .cq-grip { display: none; } }
    .cq-shead { flex: none; display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem .55rem 1.15rem; }
    .cq-shead h2 { flex: 1; min-width: 0; font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; line-height: 1.3; color: #14210c; }
    .cq-shead h2 small { display: block; margin-top: .1rem; font-family: inherit; font-size: .76rem; font-weight: 600; line-height: 1.4; color: var(--color-gray-500); }
    .cq-x { flex: none; width: 2.4rem; height: 2.4rem; border-radius: 999px; display: grid; place-items: center; color: #4b5563; background: #f3f5ef; transition: background-color .28s var(--cq-e, ease); }
    .cq-x:hover { background: #e7ecdf; }
    .cq-x svg { width: 1.1rem; height: 1.1rem; }
    .cq-sfind { flex: none; margin: 0 1rem .6rem; display: flex; align-items: center; gap: .5rem; padding: 0 .8rem; border-radius: .9rem; background: #f6f8f3; border: 1px solid #e1e9d7; }
    .cq-sfind:focus-within { border-color: #86b556; background: #fff; }
    .cq-sfind svg { flex: none; width: 1.05rem; height: 1.05rem; color: #6b9f3d; }
    .cq-sfind input { flex: 1; min-width: 0; min-height: 2.75rem; border: 0; background: transparent; font-size: 1rem; color: #14210c; outline: none; box-shadow: none; }
    .cq-slist { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 0 .6rem 1rem; }
    .cq-sgh { position: sticky; top: 0; z-index: 1; display: block; padding: .6rem .55rem .4rem; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; background: #fff; }
    .cq-row { width: 100%; display: flex; align-items: center; gap: .6rem; padding: .55rem .55rem; border-radius: .9rem; text-align: left; color: #14210c; transition: background-color .28s var(--cq-e, ease); }
    .cq-row:hover { background: #f4f8ef; }
    .cq-row[aria-selected="true"] { background: #eef5e6; }
    .cq-row .rt { min-width: 0; flex: 1; }
    .cq-row .rt b { display: block; font-size: .92rem; font-weight: 700; line-height: 1.3; overflow-wrap: anywhere; }
    .cq-row .rt small { display: block; font-size: .74rem; color: #6b7280; line-height: 1.35; overflow-wrap: anywhere; }
    .cq-row .ri { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .75rem; display: grid; place-items: center; font-size: 1.1rem; background: #f6f8f3; }
    .cq-row[hidden] { display: none; }
    .cq-check { flex: none; width: 1.5rem; height: 1.5rem; border-radius: .45rem; display: grid; place-items: center; border: 2px solid var(--color-gray-300); color: #fff; transition: background-color .28s ease, border-color .28s ease; }
    .cq-check svg { width: .85rem; height: .85rem; opacity: 0; }
    .cq-row[aria-checked="true"] .cq-check { background: #4a7c2a; border-color: #4a7c2a; }
    .cq-row[aria-checked="true"] .cq-check svg { opacity: 1; }
    .cq-snone { padding: 1.4rem 1rem; text-align: center; font-size: .9rem; color: #6b7280; }
    .cq-sfoot { flex: none; display: flex; align-items: center; gap: .7rem; padding: .8rem 1rem calc(.9rem + env(safe-area-inset-bottom)); border-top: 1px solid var(--color-gray-100); }
    @media (min-width: 640px) { .cq-sfoot { padding-bottom: .9rem; } }
    .cq-sfoot .btn { flex: none; min-height: 2.9rem; padding-left: 1.1rem; padding-right: 1.1rem; }
    .cq-pickline { flex: 1; min-width: 0; }
    .cq-pickline b { display: block; font-weight: 800; font-size: .95rem; color: var(--color-gray-900); }
    .cq-pickline span { display: block; font-size: .78rem; color: var(--color-gray-500); }
    .cq-sbody { padding: 0 1.15rem 1rem; overflow-y: auto; min-height: 0; }
    /* The day of the month. */
    .cq-days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .3rem; }
    .cq-wkd { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .3rem; margin-bottom: .3rem; font-size: .66rem; font-weight: 800; text-align: center; color: var(--color-gray-400); }
    .cq-day { aspect-ratio: 1 / 1; min-height: 2.5rem; border-radius: .75rem; font-size: .9rem; font-weight: 800; color: var(--color-gray-800); background: #f6f8f3; border: 1.5px solid transparent; transition: background-color .28s var(--cq-e), border-color .28s var(--cq-e), color .28s var(--cq-e); }
    .cq-day:disabled { opacity: .3; cursor: default; }
    .cq-day.is-on { color: #fff; background: var(--cq-g); border-color: var(--cq-g); }
    .cq-day.is-sun { color: #b45309; }
    .cq-day.is-on.is-sun { color: #fff; }
    .cq-anyday { margin-bottom: .8rem; }
    html.dark .cq-sbox { background: #151b12; }
    html.dark .cq-shead h2, html.dark .cq-row, html.dark .cq-pickline b { color: #e8efe1; }
    html.dark .cq-sgh { background: #151b12; color: #a5c97e; }
    html.dark .cq-x { background: #1c2616; color: #d5e3c5; }
    html.dark .cq-sfind { background: #1c2616; border-color: #2b3a1c; }
    html.dark .cq-sfind input { color: #e8efe1; }
    html.dark .cq-row:hover, html.dark .cq-row[aria-selected="true"] { background: #1c2616; }
    html.dark .cq-row .ri { background: #1c2616; color: #d5e3c5; }
    html.dark .cq-day { background: #1c2616; color: #e8efe1; }
    html.dark .cq-wiz, html.dark .cq-flot { background: #151b12; border-color: #2b3a1c; }
    html.dark .cq-q, html.dark .cq-choice .ct b, html.dark .cq-rev .rt b, html.dark .cq-flot b, html.dark .cq-lotsnone b, html.dark .cq-stepper output, html.dark .cq-spick b { color: #e8efe1; }
    html.dark .cq-choice, html.dark .cq-pill, html.dark .cq-rev button, html.dark .cq-spick { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .cq-choice.is-on, html.dark .cq-spick.is-on { background: #22301a; border-color: #6b9f3d; }
    html.dark .cq-pill.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .cq-choice .ce { background: #1c2416; border-color: #2b3a1c; }
    html.dark .cq-tag { background: #1c2616; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .cq-tag .ti { background: #151b12; border-color: #2b3a1c; }
    html.dark .cq-tag .tc, html.dark .cq-fledit, html.dark .cq-samed button, html.dark .cq-stepper button { background: #2b3a1c; color: #d5e3c5; }
    html.dark .cq-lot-top b, html.dark .cq-label, html.dark .cq-field > label, html.dark .cq-size label { color: #d5e3c5; }
    html.dark .cq-quote { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .cq-quote b { color: #e8efe1; }
    html.dark .cq-quote.is-off { background: #2a1a0a; border-color: #4d2f0d; color: #fdba74; }
    html.dark .cq-lotsnone { background: #12170f; border-color: #2b3a1c; }
    html.dark .cq-hint { background: #17220f; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .cq-hint.is-warn { background: #2a210a; border-color: #4d3a0d; color: #fcd34d; }
    html.dark .cq-from { background: #17220f; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .cq-rev { border-color: #2b3a1c; }
    html.dark .cq-rev button + button { border-color: #222b1a; }
    html.dark .cq-rev button:hover { background: #1c2616; }

    /* ---- THE FARM REPORT (format 4) ---- */
    .cr-tabs { position: sticky; top: calc(3.2rem + env(safe-area-inset-top)); z-index: 3; display: flex; gap: .4rem; overflow-x: auto; scrollbar-width: none; margin: -.2rem -1rem .9rem; padding: .45rem 1rem;
        background: rgb(250 250 248 / .94); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border-bottom: 1px solid var(--color-gray-200); }
    .cr-tabs::-webkit-scrollbar { display: none; }
    .cr-tab { flex: none; display: inline-flex; align-items: center; gap: .4rem; min-height: 2.4rem; padding: .3rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--color-gray-600); background: var(--color-white); border: 1.5px solid var(--color-gray-200);
        transition: background-color .28s var(--cq-e), border-color .28s var(--cq-e), color .28s var(--cq-e); }
    .cr-tab i { width: .6rem; height: .6rem; border-radius: 999px; background: var(--lc, #4a7c2a); }
    .cr-tab.is-on { color: #fff; background: var(--lc, #4a7c2a); border-color: var(--lc, #4a7c2a); }
    .cr-tab.is-on i { background: #fff; }
    html.dark .cr-tabs { background: rgb(13 17 10 / .94); border-color: #2b3a1c; }
    html.dark .cr-tab { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .cr-tab.is-on { color: #fff; background: var(--lc, #4a7c2a); border-color: var(--lc, #4a7c2a); }
    .cr-pane { display: grid; gap: .9rem; min-width: 0; animation: cqIn .26s ease both; }
    .cr-pane > * { min-width: 0; max-width: 100%; }
    .cr-pane[hidden] { display: none; }
    .cr-hero { border-radius: 1.1rem; padding: 1rem 1.1rem; color: #fff; background: linear-gradient(130deg, var(--lc, #4a7c2a), #2d5016 75%); }
    .cr-hero small { display: block; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; opacity: .85; }
    .cr-hero h2 { font-size: 1.15rem; font-weight: 800; line-height: 1.3; margin-top: .15rem; }
    .cr-hero p { font-size: .84rem; line-height: 1.5; opacity: .92; margin-top: .35rem; }
    .cr-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .6rem; }
    .cr-chips span { font-size: .7rem; font-weight: 700; padding: .18rem .55rem; border-radius: 999px; background: rgb(255 255 255 / .18); }
    .cr-card { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white); padding: 1rem 1.05rem; min-width: 0; }
    .cr-card > h3 { font-weight: 800; font-size: .95rem; color: var(--color-gray-900); margin-bottom: .6rem; }
    .cr-card > h3 small { display: block; font-size: .74rem; font-weight: 500; color: var(--color-gray-500); margin-top: .1rem; }
    html.dark .cr-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .cr-card > h3 { color: #e8efe1; }
    .cr-say { font-size: .86rem; line-height: 1.6; color: var(--color-gray-700); }
    .cr-say + .cr-say { margin-top: .4rem; }
    html.dark .cr-say { color: #d5e3c5; }
    .cr-lines { display: grid; gap: .3rem; margin-top: .5rem; }
    .cr-lines p { position: relative; padding-left: 1rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); }
    .cr-lines p::before { content: ''; position: absolute; left: .2rem; top: .55rem; width: .35rem; height: .35rem; border-radius: 999px; background: var(--cq-g); }
    html.dark .cr-lines p { color: #cdd8c0; }
    /* The status of the calendar. */
    .cr-state { display: flex; gap: .65rem; align-items: flex-start; padding: .75rem .85rem; border-radius: .95rem; background: #ecf6e2; border: 1px solid #cfe3b8; color: #24400f; font-size: .82rem; line-height: 1.5; }
    .cr-state.is-warn { background: #fffbeb; border-color: #fde68a; color: #78350f; }
    .cr-state > span:first-child { flex: none; font-size: 1.15rem; line-height: 1.3; }
    .cr-state b { display: block; font-size: .9rem; }
    html.dark .cr-state { background: #17220f; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .cr-state.is-warn { background: #2a210a; border-color: #4d3a0d; color: #fcd34d; }
    /* The season strip: a row a lot, the jobs as marks, the busy days on top. */
    .cr-strip { position: relative; margin-top: .8rem; padding-top: 1.3rem; min-width: 0; }
    .cr-axis { position: absolute; left: 5.2rem; right: 0; top: 0; height: 1.1rem; }
    .cr-axis span { position: absolute; top: 0; font-size: .6rem; font-weight: 800; color: var(--color-gray-400); transform: translateX(-50%); white-space: nowrap; }
    .cr-axis i { position: absolute; top: .9rem; width: 3px; height: .5rem; border-radius: 2px; background: #ef4444; transform: translateX(-50%); }
    .cr-srow { display: grid; grid-template-columns: 5rem minmax(0, 1fr); align-items: center; gap: .2rem; min-height: 1.9rem; }
    .cr-srow > b { min-width: 0; font-size: .7rem; font-weight: 800; color: var(--color-gray-700); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: flex; align-items: center; gap: .3rem; }
    .cr-srow > b i { flex: none; width: .55rem; height: .55rem; border-radius: 999px; background: var(--lc); }
    .cr-track { position: relative; height: 1.15rem; border-radius: 999px; background: var(--color-gray-100); }
    .cr-span { position: absolute; top: 0; bottom: 0; border-radius: 999px; background: color-mix(in srgb, var(--lc) 26%, #fff); transform-origin: left; transform: scaleX(0); transition: transform .7s var(--cq-e); }
    .cr-mark { position: absolute; top: 50%; width: .62rem; height: .62rem; margin: -.31rem 0 0 -.31rem; border-radius: 999px; background: var(--lc); box-shadow: 0 0 0 2px #fff; opacity: 0; transition: opacity .4s ease .35s; }
    .cr-mark.is-plant { width: .85rem; height: .85rem; margin: -.425rem 0 0 -.425rem; border-radius: .2rem; }
    .cr-mark.is-harvest { background: #b45309; }
    .cr-mark.is-late { box-shadow: 0 0 0 2px #ef4444; }
    .is-drawn .cr-span { transform: scaleX(1); }
    .is-drawn .cr-mark { opacity: 1; }
    .cr-legend { display: flex; flex-wrap: wrap; gap: .3rem .8rem; margin-top: .6rem; font-size: .7rem; font-weight: 700; color: var(--color-gray-500); }
    .cr-legend span { display: inline-flex; align-items: center; gap: .3rem; }
    .cr-legend i { width: .6rem; height: .6rem; border-radius: 999px; background: #6b9f3d; }
    .cr-legend i.is-plant { border-radius: .15rem; }
    .cr-legend i.is-harvest { background: #b45309; }
    .cr-legend i.is-short { width: 3px; height: .6rem; border-radius: 2px; background: #ef4444; }
    html.dark .cr-track { background: #222b1a; }
    html.dark .cr-span { background: color-mix(in srgb, var(--lc) 40%, #151b12); }
    html.dark .cr-mark { box-shadow: 0 0 0 2px #151b12; }
    html.dark .cr-srow > b { color: #d5e3c5; }
    /* The busy days. */
    .cr-days { display: grid; gap: .45rem; margin-top: .8rem; }
    .cr-day { display: grid; grid-template-columns: 3rem minmax(0, 1fr); gap: .6rem; padding: .55rem .6rem; border-radius: .9rem; background: #fbfcfa; border: 1px solid var(--color-gray-100); animation: cqPop .3s var(--cq-e) both; }
    .cr-day.is-over { background: #fff5f5; border-color: #fecaca; }
    .cr-cal { display: flex; flex-direction: column; align-items: center; justify-content: center; border-radius: .7rem; background: #fff; border: 1px solid var(--color-gray-200); overflow: hidden; height: 3rem; }
    .cr-cal i { display: block; width: 100%; text-align: center; font-style: normal; font-size: .55rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #fff; background: var(--cq-g); padding: .08rem 0; }
    .cr-cal b { font-size: 1.05rem; font-weight: 800; line-height: 1.2; color: var(--color-gray-900); }
    .cr-cal small { font-size: .58rem; font-weight: 700; color: var(--color-gray-500); line-height: 1; }
    .cr-day.is-over .cr-cal i { background: #dc2626; }
    .cr-items { min-width: 0; display: grid; gap: .2rem; }
    .cr-item { display: flex; align-items: flex-start; gap: .4rem; font-size: .78rem; line-height: 1.4; color: var(--color-gray-700); min-width: 0; }
    .cr-item i { flex: none; width: .55rem; height: .55rem; border-radius: 999px; margin-top: .35rem; background: var(--lc); }
    .cr-item span { min-width: 0; overflow-wrap: anywhere; }
    .cr-item em { font-style: normal; font-weight: 800; color: var(--color-gray-500); }
    .cr-meter { display: flex; align-items: center; gap: .45rem; margin-top: .25rem; font-size: .7rem; font-weight: 800; color: var(--color-gray-500); }
    .cr-meter > span { flex: 1; height: .4rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .cr-meter > span i { display: block; height: 100%; border-radius: 999px; background: #6b9f3d; transform-origin: left; animation: crGrow .7s var(--cq-e) both; }
    .cr-day.is-over .cr-meter > span i { background: #ef4444; }
    .cr-day.is-over .cr-meter { color: #b91c1c; }
    @keyframes crGrow { from { transform: scaleX(0); } }
    html.dark .cr-day { background: #12170f; border-color: #222b1a; }
    html.dark .cr-day.is-over { background: #2a1414; border-color: #4c1d1d; }
    html.dark .cr-cal { background: #151b12; border-color: #2b3a1c; }
    html.dark .cr-cal b { color: #e8efe1; }
    html.dark .cr-item { color: #cdd8c0; }
    html.dark .cr-meter > span { background: #222b1a; }
    .cr-more { margin-top: .7rem; }
    /* The lots at a glance. */
    .cr-lotcards { display: grid; gap: .55rem; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 560px) { .cr-lotcards { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .cr-lotcard { display: flex; gap: .65rem; align-items: flex-start; width: 100%; padding: .7rem .75rem; border-radius: 1rem; text-align: left; background: #fbfcfa; border: 1px solid var(--color-gray-200); border-left: 4px solid var(--lc); min-width: 0;
        transition: border-color .28s var(--cq-e), transform .28s var(--cq-e), box-shadow .28s var(--cq-e); }
    .cr-lotcard:hover { transform: translateY(-1px); box-shadow: 0 12px 24px -20px rgb(20 33 12 / .6); }
    .cr-lotcard > span:first-child { flex: none; font-size: 1.4rem; line-height: 1.2; }
    .cr-lotcard > span + span { min-width: 0; flex: 1; }
    .cr-lotcard b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .cr-lotcard small { display: block; font-size: .74rem; line-height: 1.45; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .cr-ok { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .35rem; }
    .cr-ok em { font-style: normal; font-size: .66rem; font-weight: 800; padding: .1rem .45rem; border-radius: 999px; color: #2d5016; background: #e9f3dd; }
    .cr-ok em.is-warn { color: #92400e; background: #fef3c7; }
    .cr-ok em.is-bad { color: #991b1b; background: #fde8e8; }
    html.dark .cr-lotcard { background: #12170f; border-color: #2b3a1c; border-left-color: var(--lc); }
    html.dark .cr-lotcard b { color: #e8efe1; }
    /* What to buy. */
    .cr-buy { display: grid; gap: .3rem; }
    .cr-buyrow { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .2rem .6rem; align-items: center; padding: .5rem .6rem; border-radius: .75rem; border: 1px dashed var(--color-gray-200); font-size: .8rem; }
    .cr-buyrow b { color: var(--color-gray-900); font-weight: 800; overflow-wrap: anywhere; }
    .cr-buyrow span { font-weight: 800; color: var(--cq-gd); white-space: nowrap; text-align: right; }
    .cr-buyrow small { grid-column: 1 / -1; font-size: .7rem; color: var(--color-gray-500); }
    html.dark .cr-buyrow { border-color: #2b3a1c; }
    html.dark .cr-buyrow b { color: #e8efe1; }
    /* Protection: watch first. */
    .cr-warn { display: flex; gap: .7rem; align-items: flex-start; padding: .8rem .9rem; border-radius: 1rem; background: #fff7ed; border: 1px solid #fed7aa; color: #7c2d12; font-size: .8rem; line-height: 1.55; }
    .cr-warn > span:first-child { flex: none; font-size: 1.2rem; line-height: 1.2; }
    .cr-warn b { display: block; font-size: .88rem; margin-bottom: .1rem; }
    html.dark .cr-warn { background: #2a1a0e; border-color: #5a3a1a; color: #fdba74; }
    .cr-threats { display: grid; gap: .55rem; grid-template-columns: minmax(0, 1fr); margin-top: .7rem; }
    .cr-threat { padding: .7rem .8rem; border-radius: .95rem; border: 1px solid; min-width: 0; }
    .cr-threat.is-insect, .cr-threat.is-other { background: #fffaf3; border-color: #fde2bf; }
    .cr-threat.is-disease { background: #fdf4f9; border-color: #f7d0e5; }
    .cr-threat.is-weed { background: #f3fbf5; border-color: #c6ecd2; }
    .cr-th-h { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; }
    .cr-th-h b { font-size: .92rem; font-weight: 800; color: var(--color-gray-900); }
    .cr-kind { font-size: .6rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; padding: .12rem .45rem; border-radius: 999px; background: rgb(0 0 0 / .06); color: var(--color-gray-600); }
    .cr-had { font-size: .64rem; font-weight: 800; padding: .12rem .45rem; border-radius: 999px; background: #fde68a; color: #78350f; }
    .cr-stg { display: flex; flex-wrap: wrap; gap: .25rem; margin-top: .35rem; }
    .cr-stg span { font-size: .66rem; font-weight: 800; padding: .1rem .45rem; border-radius: 999px; background: #fff; border: 1px solid rgb(0 0 0 / .08); color: var(--color-gray-600); }
    .cr-threat p { margin-top: .3rem; font-size: .79rem; line-height: 1.5; color: var(--color-gray-700); }
    .cr-threat p em { font-style: normal; font-weight: 800; color: var(--color-gray-500); }
    .cr-ais { display: grid; gap: .35rem; margin-top: .45rem; }
    .cr-ai { padding: .45rem .55rem; border-radius: .7rem; background: #fff; border: 1px solid rgb(0 0 0 / .07); font-size: .78rem; line-height: 1.45; min-width: 0; }
    .cr-ai b { color: var(--color-gray-900); }
    .cr-ai .g { display: inline-block; margin-left: .3rem; font-size: .64rem; font-weight: 900; padding: .05rem .4rem; border-radius: 999px; background: #e0f2fe; color: #075985; vertical-align: middle; }
    .cr-ai small { display: block; margin-top: .1rem; font-size: .7rem; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .cr-ai .g.is-mark { background: #fef3c7; color: #92400e; }
    .cr-note { margin-top: .4rem; font-size: .74rem; font-weight: 700; color: #92400e; }
    html.dark .cr-threat.is-insect, html.dark .cr-threat.is-other { background: #241a0e; border-color: #4a3416; }
    html.dark .cr-threat.is-disease { background: #24101c; border-color: #4a2040; }
    html.dark .cr-threat.is-weed { background: #0f2214; border-color: #1e4a2a; }
    html.dark .cr-th-h b, html.dark .cr-ai b { color: #e8efe1; }
    html.dark .cr-threat p { color: #cdd8c0; }
    html.dark .cr-stg span, html.dark .cr-ai { background: rgb(255 255 255 / .04); border-color: rgb(255 255 255 / .08); color: #cdd8c0; }
    html.dark .cr-ai .g { background: #0c2a3a; color: #7dd3fc; }
    html.dark .cr-ai .g.is-mark { background: #3a2a0c; color: #fcd34d; }
    html.dark .cr-kind { background: rgb(255 255 255 / .08); color: #cdd8c0; }
    .cr-fold { margin-top: .6rem; }
    .cr-fold > summary { cursor: pointer; list-style: none; font-size: .82rem; font-weight: 800; color: var(--cq-gd); display: flex; align-items: center; gap: .35rem; }
    .cr-fold > summary::-webkit-details-marker { display: none; }
    .cr-fold > summary::before { content: '›'; display: inline-block; font-size: 1.1rem; transition: transform .28s var(--cq-e); }
    .cr-fold[open] > summary::before { transform: rotate(90deg); }
    .cr-fold > div { animation: cqPop .3s var(--cq-e) both; }
    /* The nutrition fixes. */
    .cr-fix { display: grid; gap: .45rem; }
    .cr-fixrow { display: flex; gap: .6rem; align-items: flex-start; padding: .55rem .65rem; border-radius: .85rem; background: #f7fbf2; border: 1px solid #d8eac4; min-width: 0; }
    .cr-fixrow > span:first-child { flex: none; min-width: 2.6rem; padding: .25rem .3rem; border-radius: .6rem; text-align: center; font-size: .74rem; font-weight: 900; color: #fff; background: var(--cq-g); }
    .cr-fixrow.is-spray > span:first-child { background: #0284c7; }
    .cr-fixrow > div { min-width: 0; flex: 1; font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); }
    .cr-fixrow b { color: var(--color-gray-900); }
    .cr-fixrow small { display: block; font-size: .72rem; color: var(--color-gray-500); }
    html.dark .cr-fixrow { background: #17220f; border-color: #2b3a1c; }
    html.dark .cr-fixrow > div { color: #cdd8c0; }
    html.dark .cr-fixrow b { color: #e8efe1; }
    /* A lot's jobs by date. */
    .cr-jobs { display: grid; gap: .4rem; }
    .cr-job { display: grid; grid-template-columns: 3rem minmax(0, 1fr); gap: .6rem; align-items: start; padding: .5rem .55rem; border-radius: .85rem; background: #fbfcfa; border: 1px solid var(--color-gray-100); animation: cqPop .3s var(--cq-e) both; }
    .cr-job.is-late { background: #fff5f5; border-color: #fecaca; }
    .cr-job b { display: block; font-size: .85rem; font-weight: 800; color: var(--color-gray-900); }
    .cr-job small { display: block; font-size: .74rem; line-height: 1.45; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .cr-job em { display: inline-block; margin-top: .2rem; font-style: normal; font-size: .66rem; font-weight: 800; padding: .08rem .45rem; border-radius: 999px; background: #efe9fd; color: #5b21b6; }
    .cr-job em.is-late { background: #fde8e8; color: #991b1b; }
    .cr-job em.is-hired { background: #e0f2fe; color: #075985; }
    html.dark .cr-job { background: #12170f; border-color: #222b1a; }
    html.dark .cr-job.is-late { background: #2a1414; border-color: #4c1d1d; }
    html.dark .cr-job b { color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) {
        .cq-step.is-on, .cq-quote, .cq-lot, .cq-wk, .cq-hint, .cq-from, .cq-need, .cq-cast-c, .cq-cast-row, .cq-price, .cq-err, .cr-pane, .cr-day, .cr-job, .cr-fold > div { animation: none !important; }
        .cq-choice, .cq-tag, .cq-pill, .cq-spick, .cq-day, .cq-run, .cr-tab, .cr-span, .cr-mark, .cr-lotcard { transition: none !important; }
        .cr-meter > span i { animation: none; }
        .cq-run svg.arr { animation: none; }
        .cr-span { transform: none; }
        .cr-mark { opacity: 1; }
    }
    @media (max-width: 380px) {
        .cq-wiz { padding: .95rem .85rem .9rem; }
        .cr-srow { grid-template-columns: 3.6rem minmax(0, 1fr); }
        .cr-axis { left: 3.8rem; }
    }
</style>

@php
    $xIcon = '<svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>';
    $chev = '<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';
    $find = '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>';
@endphp
@include('partials.field-profile')
@include('partials.farm-lots')
@include('partials.variety-pick')
@include('partials.month-pick')

<div class="cq" id="cqPage">
    <div class="wtp-tabs" role="tablist">
        <button type="button" class="wtp-tab is-on" id="cpTabGen">New protocol</button>
        <button type="button" class="wtp-tab" id="cpTabSaved">Saved</button>
    </div>

    <div id="cpGen" class="an-wait">
        <div class="cq-quote" id="cqQuote" hidden></div>

        <div class="cq-wiz" id="cqWiz">
            <div class="cq-head"><small id="cqStepName">Lots</small><span id="cqStepNo">Step 1</span></div>
            <div class="cq-bar" aria-hidden="true"><i id="cqBar"></i></div>

            {{-- 1. The lots: the protocol starts from the farm's lots (Global Lots), several at once. --}}
            <section class="cq-step is-on" data-screen="lots">
                <p class="cq-q">Which lots will you plant?{{ \App\Support\Tagalog::flag('lot') }}</p>
                <p class="cq-sub">One card a lot. Next, you pick what each lot will grow and your goal for it. Each lot brings its place, size, soil and water, and its soil test, so only what it lacks is asked.</p>
                <div class="cq-lots" id="cqLots"></div>
                <div class="cq-lot-acts">
                    <button type="button" class="btn btn-white" id="cqLotAdd"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>Add from your lots</button>
                </div>
                <p class="cq-total" id="cqLotTotal"></p>
            </section>

            {{-- 2. Per lot: what it grows (crop, how it goes in, variety, month and day). --}}
            <section class="cq-step" data-screen="plant">
                <div class="cq-who" id="cqPlantWho"></div>
                <p class="cq-q" id="cqPlantQ">What will you plant?</p>
                <p class="cq-sub" id="cqPlantSub">Pick the crop, how it goes in, its variety and the month.</p>
                <div class="cq-same" id="cqSamePick" hidden></div>
                <span class="cq-label">Crop</span>
                <button type="button" class="cq-tag is-none" id="cqCropTag" aria-haspopup="dialog">
                    <span class="ti" aria-hidden="true" id="cqCropIcon">🌱</span>
                    <span class="tt"><b id="cqCropName">Choose the crop</b><small id="cqCropLine">Opens the crop list</small></span>
                    <span class="tc">Change{!! $chev !!}</span>
                </button>
                <p class="cq-ferr" id="cqCropErr" hidden>Choose the crop first.</p>
                <div id="cqMethodBox" hidden>
                    <span class="cq-label" data-tl="planting-method">How will you plant it?</span>
                    <div class="cq-choices" id="cqMethods"></div>
                    <p class="cq-ferr" id="cqMethodErr" hidden>Pick how it goes in.</p>
                </div>
                <span class="cq-label">Which variety?{{ \App\Support\Tagalog::flag('variety') }} <small>(optional)</small></span>
                <button type="button" class="cq-tag is-none" id="cqVarTag" aria-haspopup="dialog">
                    <span class="ti" aria-hidden="true">🧬</span>
                    <span class="tt"><b id="cqVarName">I don't know yet</b><small id="cqVarLine">Pick from the records, or type the name on the seed bag</small></span>
                    <span class="tc">Change{!! $chev !!}</span>
                </button>
                <span class="cq-label">When will you plant?</span>
                <div class="cq-tags2">
                    <button type="button" class="cq-tag is-none" id="cqMonthTag" aria-haspopup="dialog">
                        <span class="ti" aria-hidden="true">🗓️</span>
                        <span class="tt"><b id="cqMonthName">Pick the month</b><small>The month sets the weather</small></span>
                        <span class="tc">Pick{!! $chev !!}</span>
                    </button>
                    <button type="button" class="cq-tag is-none" id="cqDayTag" aria-haspopup="dialog">
                        <span class="ti" aria-hidden="true">📅</span>
                        <span class="tt"><b id="cqDayName">Any day</b><small id="cqDayLine">Anee picks the day that suits your crew</small></span>
                        <span class="tc">Pick{!! $chev !!}</span>
                    </button>
                </div>
                <p class="cq-ferr" id="cqMonthErr" hidden>Pick the planting month.</p>
            </section>

            {{-- 3. Per lot: the goal, the target, the variety's potential, the stand. --}}
            <section class="cq-step" data-screen="goal">
                <div class="cq-who" id="cqGoalWho"></div>
                <p class="cq-q" id="cqGoalQ">Your goal</p>
                <p class="cq-sub">Your goal sets the rates: more for yield, less for cost. The fertilizer is sized to your target the way NPK Plus sizes it.</p>
                <div class="cq-same" id="cqSameGoal" hidden></div>
                <div class="cq-choices" id="cqGoals"></div>
                <span class="cq-label">Yields in</span>
                <div class="cq-pills" id="cqUnits" role="radiogroup" aria-label="Yields in"></div>
                <div class="cq-two" style="margin-top:.8rem">
                    <div class="cq-field">
                        <label for="cqPotential" data-tl="yield">Yield potential of the variety <small>(per hectare)</small></label>
                        <input type="number" id="cqPotential" class="form-input" min="0" step="any" inputmode="decimal" placeholder="On the seed bag">
                    </div>
                    <div class="cq-field">
                        <label for="cqTarget">Your target <small>(per hectare, optional)</small></label>
                        <input type="number" id="cqTarget" class="form-input" min="0" step="any" inputmode="decimal" placeholder="Your own goal">
                    </div>
                </div>
                <div class="cq-from" id="cqPotFrom" hidden></div>
                <span class="cq-label">How much will you plant? <small>(optional, for this lot)</small></span>
                <div class="cq-pills" id="cqSeedUnits" role="radiogroup" aria-label="What you plant"></div>
                <div class="cq-two" style="margin-top:.6rem">
                    <div class="cq-field">
                        <label for="cqSeed">Amount <small id="cqSeedU">(kg)</small></label>
                        <input type="number" id="cqSeed" class="form-input" min="0" step="any" inputmode="decimal" placeholder="e.g. 40">
                    </div>
                    <div class="cq-field" id="cqGermBox">
                        <label for="cqGerm" data-tl="germination-test">Germination <small>(percent, on the seed tag)</small></label>
                        <input type="number" id="cqGerm" class="form-input" min="1" max="100" step="any" inputmode="decimal" placeholder="e.g. 85">
                    </div>
                </div>
                <p class="cq-sub cq-sub-s" id="cqSeedHint" style="margin-top:.45rem"></p>
                {{-- How the lot is farmed (2026-10-19): asked here only when its last season page is answered already. --}}
                <div class="cq-habits" id="cqHabits" hidden></div>
                <div class="cq-need" id="cqNeed" hidden aria-live="polite"></div>
            </section>

            {{-- 4. A lot's field questions it has not answered (the shared kit, partials/field-profile). --}}
            <section class="cq-step" data-screen="lotq">
                <div class="cq-fqhost" id="cqFq"></div>
            </section>

            {{-- 5. Once for the farm: what can be bought near it, and a note. --}}
            <section class="cq-step" data-screen="buy">
                <p class="cq-q">What fertilizer is sold near you?</p>
                <p class="cq-sub">Pick all that apply. Anee builds every plan from these, and says so when a better one is not on your list.</p>
                <div class="wtp-choices is-pills" id="cqGranulars" role="group" aria-label="Fertilizer sold near you"></div>
                <label class="cq-label" for="cqNotes">Anything else Anee should know? <small>(optional)</small></label>
                <textarea id="cqNotes" class="form-textarea" rows="2" maxlength="500" placeholder="e.g. we have a pump but diesel is costly"></textarea>
            </section>

            {{-- 6. The crew: who works, on which days, and what happens when jobs collide. --}}
            <section class="cq-step" data-screen="workers">
                <p class="cq-q">Who will do the work?{{ \App\Support\Tagalog::flag('workers') }}</p>
                <p class="cq-sub">Pick your workers or set how many. Each keeps their own work days. Anee lays every lot's jobs over them, so no day asks more hands than you have.</p>
                <button type="button" class="cq-tag" id="cqRosterOpen" hidden style="margin-bottom:.8rem"><span class="ti" aria-hidden="true">👥</span><span class="tt"><b>Pick from My workers</b><small id="cqRosterLine">Your workers from every season</small></span><span class="tc">Pick{!! $chev !!}</span></button>
                <div class="cq-stepper">
                    <button type="button" data-wstep="-1" aria-label="One worker less">−</button>
                    <output id="cqWCount">2</output>
                    <button type="button" data-wstep="1" aria-label="One worker more">+</button>
                    <span id="cqWWord">workers</span>
                </div>
                <div class="cq-wks" id="cqWorkers"></div>
                <div class="cq-samed" id="cqSameDays"><button type="button" data-same="days">Same days for all</button></div>
                <label class="cq-savenew" id="cqSaveNew" hidden><input type="checkbox" id="cqSaveNewOn" checked><span><b>Add the new names to My workers</b><small>Next season you pick them instead of typing them again.</small></span></label>
                <p class="cq-ferr" id="cqWErr" hidden>Give at least one worker a work day.</p>
                <span class="cq-label">Knapsack sprayers you have</span>
                <div class="cq-stepper">
                    <button type="button" data-sstep="-1" aria-label="One sprayer less">−</button>
                    <output id="cqSCount">1</output>
                    <button type="button" data-sstep="1" aria-label="One sprayer more">+</button>
                    <span id="cqSWord">sprayer</span>
                </div>
                <span class="cq-label">Who plants and harvests?</span>
                <div class="cq-choices is-2" id="cqPlantBy">
                    <button type="button" class="cq-choice is-on" data-plantby="own"><span class="ce">🧑‍🌾</span><span class="ct"><b>My workers</b><small>They plant and harvest too, so those days count</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                    <button type="button" class="cq-choice" data-plantby="hired"><span class="ce">🤝</span><span class="ct"><b data-tl="hired">A hired crew</b><small>Planters and harvesters come for those days</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                </div>
                <span class="cq-label">When jobs on different lots fall on the same days</span>
                <div class="cq-choices" id="cqMode">
                    <button type="button" class="cq-choice is-on" data-mode="auto"><span class="ce">🔀</span><span class="ct"><b>Anee moves them</b><small>Each job moves only inside the days the crop still takes it well, and a lot with no day picked gets the first day that fits your crew.</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                    <button type="button" class="cq-choice" data-mode="keep"><span class="ce">📌</span><span class="ct"><b>Keep my dates and warn me</b><small>Nothing moves. Anee names each day your workers are not enough, and by how many.</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
                </div>
                <div class="cq-cast" id="cqCast" aria-live="polite"></div>
            </section>

            {{-- 7. The place, only when no lot brings one. --}}
            <section class="cq-step" data-screen="place">
                <p class="cq-q">Where are the lots?</p>
                <p class="cq-sub">Anee reads the forecast, the ENSO outlook{{ \App\Support\Tagalog::flag('enso-outlook') }} and twenty years of the area's weather there.</p>
                <label class="cq-label" for="cqPlace">Town and province</label>
                <input type="text" id="cqPlace" class="form-input" maxlength="160" placeholder="e.g. Science City of Muñoz, Nueva Ecija">
                <p class="cq-ferr" id="cqPlaceErr" hidden>Say where the lots are.</p>
            </section>

            {{-- 8. The review and the run. --}}
            <section class="cq-step" data-screen="review">
                <p class="cq-q">Ready for Anee</p>
                <p class="cq-sub">Tap a row to change it.</p>
                <div class="cq-rev" id="cqReview"></div>
                <div class="cq-cast" id="cqCastRev" aria-live="polite"></div>
                <div class="cq-price" id="cqPrice" hidden></div>
                <div class="cq-err" id="cqErr" role="alert" hidden><b>The protocol did not finish</b><p id="cqErrText"></p></div>
                <button type="button" class="cq-run" id="cpRun"><span id="cqRunFace"></span><span id="cpRunSays">Write my protocol</span><svg class="arr" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg></button>
                <p class="cq-fine" id="cpRunFine"></p>
            </section>

            <div class="cq-nav" id="cqNav">
                <button type="button" class="btn btn-white" id="cqBack">Back</button>
                <button type="button" class="btn btn-primary" id="cqNext">Next</button>
            </div>
            @include('sm.partials.anee-wait')
        </div>

        <div class="wtp-report mt-4" id="cpReport" hidden></div>
    </div>

    <div id="cpSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div id="cpSavedList"></div>
            <div id="cpSavedEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900">Nothing saved yet</p>
                <p class="text-sm text-gray-400">Finished protocols are saved here.</p>
            </div>
        </div>
    </div>
</div>

<div class="va-view" id="cpView" hidden role="dialog" aria-modal="true" aria-label="Crop protocol analysis">
    <div class="va-view-bar">
        <b id="cpViewTitle">Crop protocol analysis</b>
        <button type="button" class="va-view-x" id="cpViewX" aria-label="Close">✕</button>
    </div>
    <div class="va-view-body"><div class="wtp-report" id="cpViewReport"></div></div>
</div>

{{-- The crop list. --}}
<dialog class="cq-sheet is-tall" id="cqCropSheet" aria-labelledby="cqCropTitle">
    <div class="cq-sbox" tabindex="-1">
        <div class="cq-grip" aria-hidden="true"></div>
        <div class="cq-shead"><h2 id="cqCropTitle">Which crop?</h2><button type="button" class="cq-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <label class="cq-sfind">{!! $find !!}<span class="sr-only">Search a crop</span><input type="search" id="cqCropQ" placeholder="{{ \App\Support\Region::t('cropSearch') }}" autocomplete="off"></label>
        <div class="cq-slist" id="cqCropList" role="listbox" aria-label="Crops"></div>
    </div>
</dialog>

{{-- The day inside the month. --}}
<dialog class="cq-sheet is-small" id="cqDaySheet" aria-labelledby="cqDayTitle">
    <div class="cq-sbox" tabindex="-1">
        <div class="cq-grip" aria-hidden="true"></div>
        <div class="cq-shead"><h2 id="cqDayTitle">Which day?<small id="cqDaySub">Or leave it to Anee</small></h2><button type="button" class="cq-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <div class="cq-sbody">
            <button type="button" class="cq-choice cq-anyday" id="cqAnyDay"><span class="ce">✨</span><span class="ct"><b>Any day in the month</b><small>Anee picks the first day that suits your crew and your other lots</small></span><span class="ck"><svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span></button>
            <div class="cq-wkd" aria-hidden="true"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
            <div class="cq-days" id="cqDays"></div>
        </div>
    </div>
</dialog>

{{-- The farm's lots: tick several, or add a new one. --}}
<dialog class="cq-sheet is-tall" id="cqFarmSheet" aria-labelledby="cqFarmTitle">
    <div class="cq-sbox" tabindex="-1">
        <div class="cq-grip" aria-hidden="true"></div>
        <div class="cq-shead"><h2 id="cqFarmTitle">Your lots<small>Tick the lots you will plant. Their place, size and answers come along.</small></h2><button type="button" class="cq-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <div class="cq-slist" id="cqFarmList" style="padding-left:1rem;padding-right:1rem"></div>
        <div class="cq-sfoot">
            <div class="cq-pickline"><b id="cqFarmCount">None ticked</b><span>A lot already in the protocol is ticked</span></div>
            <button type="button" class="btn btn-primary" id="cqFarmUse" disabled>Add them</button>
        </div>
    </div>
</dialog>

{{-- My workers (Global Workers): tick who works. --}}
<dialog class="cq-sheet is-tall" id="cqRosterSheet" aria-labelledby="cqRosterTitle">
    <div class="cq-sbox" tabindex="-1">
        <div class="cq-grip" aria-hidden="true"></div>
        <div class="cq-shead"><h2 id="cqRosterTitle">My workers<small>Tick who will work. They bring their days off.</small></h2><button type="button" class="cq-x" data-close aria-label="Close">{!! $xIcon !!}</button></div>
        <label class="cq-sfind">{!! $find !!}<span class="sr-only">Search your workers</span><input type="search" id="cqRosterQ" placeholder="Search your workers" autocomplete="off" maxlength="60"></label>
        <div class="cq-slist" id="cqRosterList" role="listbox" aria-multiselectable="true" aria-label="Your workers"></div>
        <div class="cq-sfoot">
            <div class="cq-pickline"><b id="cqRosterCount">None ticked</b><span><a href="{{ route('workers.global') }}" target="_blank" rel="noopener" class="underline">Manage your workers</a></span></div>
            <button type="button" class="btn btn-primary" id="cqRosterUse">Use them</button>
        </div>
    </div>
</dialog>

<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const $id = $;
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const reduce = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const U = {
        options: @json(route('proto.options')),
        generate: @json(route('proto.generate')),
        need: @json(route('proto.need')),
        list: @json(route('proto.list')),
        one: (id) => @json(url('/app/crop-protocol/one')) + '/' + id,
        job: (id) => @json(url('/app/crop-protocol/job')) + '/' + id,
        del: (id) => @json(url('/app/crop-protocol')) + '/' + id,
        anee: @json(route('ai.index')),
        lots: @json(route('farmlots.index')), lotIcon: @json(asset('images/treasure-map.png')),
    };
    const META_URL = @json(route('proto.meta'));
    const LOT_COLORS = ['#4a7c2a', '#d97706', '#0284c7', '#7c3aed', '#db2777', '#0f766e', '#b45309', '#4f46e5'];
    const lc = (i) => LOT_COLORS[Math.max(0, i) % LOT_COLORS.length];
    const CHECK = '<svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    const XSM = '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>';
    const PLUS = '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>';
    const tlAt = (key) => (key ? ' data-tl="' + esc(key) + '"' : '');
    const clone = (o) => JSON.parse(JSON.stringify(o ?? null));
    const num = (v) => { const n = parseFloat(String(v ?? '').replace(/,/g, '').trim()); return Number.isFinite(n) ? n : 0; };
    const fmt2 = (v) => String(Math.round(Number(v) * 100) / 100);
    const kgFmt = (v) => Number(v || 0).toLocaleString();
    const andList = (a) => a.length < 2 ? (a[0] || '') : a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1];
    const DN = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const DN2 = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
    const dayFmt = (s, o) => new Date(String(s).slice(0, 10) + 'T00:00:00Z').toLocaleDateString('en-US', Object.assign({ timeZone: 'UTC' }, o || { weekday: 'short', month: 'short', day: 'numeric' }));
    const monthWord = (ym) => (ym && window.aneeMonthPick ? window.aneeMonthPick.label(ym) : (ym || ''));
    const shake = (el) => { if (!el || reduce()) return; el.classList.remove('is-shake'); void el.offsetWidth; el.classList.add('is-shake'); setTimeout(() => el.classList.remove('is-shake'), 450); };
    const bump = (el) => { if (!el) return; el.classList.remove('is-bump'); void el.offsetWidth; el.classList.add('is-bump'); };
    const daysWord = (d) => (d.length === 7 ? 'every day' : d.length === 0 ? 'no days' : (d.length === 6 && !d.includes(0) ? 'Mon to Sat' : d.length + (d.length === 1 ? ' day' : ' days')));

    let OPT = null;
    let FQ = null;
    let uid = 0;
    const newPick = () => ({ crop: '', method: null, variety: '', varRec: null, month: null, day: null });
    const newGoal = () => ({ priority: 'balanced', yieldUnit: null, potential: '', potTouched: false, target: '', seedUnit: null, seedTouched: false, seedAmount: '', germination: '' });
    const newLot = (o) => Object.assign({ uid: ++uid, farmLotId: null, lot: null, name: '', place: '', where: '', province: '', town: '', areaHa: '', areaFrom: '', profile: {}, base: {},
        copiedFrom: null, edited: false, savedFrom: null, stale: false, updated: '', pick: null, goal: null }, o || {});
    const newWorker = (o) => Object.assign({ uid: ++uid, name: '', days: [1, 2, 3, 4, 5, 6], globalId: null }, o || {});
    const S = { lots: [], workers: [newWorker(), newWorker()], sprayers: 1, plantBy: 'own', mode: 'auto', saveNew: true, granulars: [], notes: '', place: '' };
    let QUOTE = null;

    /* ---- what each lot grows and aims for: its own pick and goal, the lot before it as the default ---- */
    const cropOf = (k) => (OPT ? OPT.crops.find((c) => c.key === k) : null) || null;
    const cropShort = (k) => { const c = cropOf(k); return c ? String(c.label).replace(/\s*\([^)]*\)\s*$/, '').replace(' — ', ', ').replace(/ \/ /g, ' or ') : ''; };
    const pickOf = (i) => { const l = S.lots[i]; if (!l) return newPick(); if (l.pick) return l.pick; return i > 0 ? clone(pickOf(i - 1)) : newPick(); };
    // A lot not opened yet takes the goal of the lot before it; a target only for the same crop (140 cavans of rice is no corn target).
    const goalOf = (i) => {
        const l = S.lots[i];
        if (!l) return newGoal();
        if (l.goal) return l.goal;
        if (!i) return newGoal();
        const g = Object.assign(clone(goalOf(i - 1)), { potential: '', potTouched: false, seedAmount: '', seedTouched: false });
        if (pickOf(i).crop !== pickOf(i - 1).crop) g.target = '';
        return g;
    };
    const samePick = (a, b) => !!a && !!b && a.crop === b.crop && (a.method || null) === (b.method || null) && (a.variety || '') === (b.variety || '') && (a.month || '') === (b.month || '');
    const pickLine = (p) => [cropShort(p.crop), p.variety, p.month ? monthWord(p.month) : ''].filter(Boolean).join(' · ');
    const lotWord = (i) => { const l = S.lots[i] || {}; return (S.lots.length > 1 ? 'Lot ' + (i + 1) + ' · ' : '') + (l.name || 'Your lot'); };

    /* ---- the farm lots in the protocol ---- */
    const answeredVal = (x) => x !== undefined && x !== null && x !== '' && !(Array.isArray(x) && !x.length);
    const pageDone = (v, p, crop) => {
        const FP = window.aneeFieldProfile;
        if (!FP || !v) return false;
        const riceHere = String(crop || '').startsWith('rice');
        return Object.entries(FP.questions).some(([k, q]) => q.page === p && !(q.only === 'rice' && !riceHere) && answeredVal(v[k])) || (p === 'acid' && v.phFrom != null);
    };
    const fqCrop = (i) => { const c = cropOf(pickOf(i).crop); return c ? c.fq : null; };
    // The pages a lot is asked: only the ones its own answers do not cover yet.
    const askedPages = (i) => {
        const l = S.lots[i];
        if (!l || !window.aneeFieldProfile || !pickOf(i).crop) return [];
        const crop = fqCrop(i);
        return window.aneeFieldProfile.pagesFor(crop).filter((p) => !pageDone(l.base, p, crop));
    };
    const farmBits = (r) => ({ farmLotId: r.id, lot: r, name: r.name || '', base: clone(r.answers) || {}, province: r.province || '', town: r.town || '', where: r.where || '',
        place: r.where || r.place || '', stale: !!r.stale, updated: r.updated || '' });
    const fromFarm = (r) => newLot(Object.assign(farmBits(r), { areaHa: Number(r.areaHa) > 0 ? String(Math.round(Number(r.areaHa) * 10000) / 10000) : '', areaFrom: Number(r.areaHa) > 0 && window.aneeLots ? window.aneeLots.fmtSize(r) : '',
        profile: clone(r.answers) || {}, savedFrom: r.count ? r.name : null }));
    function refreshFarm(l, r, keep) {
        Object.assign(l, farmBits(r), keep ? {} : { profile: clone(r.answers) || {}, savedFrom: r.count ? r.name : null, copiedFrom: null, edited: false });
        if (Number(r.areaHa) > 0 && !num(l.areaHa)) l.areaHa = String(Math.round(Number(r.areaHa) * 10000) / 10000);
    }
    async function editFarm(l) {
        const L = window.aneeLots;
        if (!L || !l || !l.farmLotId) return null;
        const row = await L.edit(Object.assign({}, l.lot || {}, { id: l.farmLotId, name: l.name, answers: clone(l.profile) }), { crop: fqCrop(S.lots.indexOf(l)) });
        if (!row) return null;
        refreshFarm(l, row);
        return row;
    }

    /* ---------------- the walk: a flow of screens, a lot's pages once for every lot ---------------- */
    let FLOW = ['lots'];
    let pos = 0;
    function flow() {
        const f = ['lots'];
        S.lots.forEach((l, i) => {
            f.push('plant:' + i, 'goal:' + i);
            askedPages(i).forEach((p) => f.push('lot:' + i + ':' + p));
        });
        f.push('buy', 'workers');
        if (S.lots.length && !S.lots.some((l) => l.place)) f.push('place');
        f.push('review');
        return f;
    }
    const NAMES = { lots: 'Lots', plant: 'What to plant', goal: 'Goal', buy: 'Fertilizer you can buy', workers: 'Workers', place: 'Place', review: 'Review' };
    const screenOf = (id) => (String(id).startsWith('lot:') ? 'lotq' : String(id).split(':')[0]);
    const idxOf = (id) => Number(String(id).split(':')[1]) || 0;
    function show(n, back) {
        const was = FLOW[pos];
        commit(was);
        FLOW = flow();
        pos = Math.max(0, Math.min(FLOW.length - 1, n));
        const id = FLOW[pos], sc = screenOf(id);
        // One lot's page to the next lot's same page: faded out and in again like any step.
        if (was !== undefined && was !== id && sc === screenOf(was) && sc !== 'lotq') {
            const pg = document.querySelector('.cq-step[data-screen="' + sc + '"]');
            window.aneeStepOut?.(pg);
            pg.classList.remove('is-on'); void pg.offsetWidth;
        }
        if (was !== undefined && screenOf(was) !== sc) window.aneeStepOut?.(document.querySelector('.cq-step.is-on'));
        try { if (document.activeElement && document.activeElement !== document.body) document.activeElement.blur(); } catch (_) { }
        const same = screenOf(was) === sc && sc === 'lotq';
        document.querySelectorAll('#cqWiz .cq-step').forEach((s) => {
            const on = s.dataset.screen === sc;
            s.classList.toggle('is-on', on);
            s.classList.toggle('is-back', on && !!back);
            s.classList.toggle('is-still', on && same);
        });
        if (sc === 'lotq') {
            const [, i, p] = String(id).split(':');
            const li = Number(i);
            $('cqStepName').textContent = lotWord(li) + ' · ' + ((window.aneeFieldProfile.groups[p] || [p])[0]);
            FQ.setCrop(fqCrop(li));
            FQ.setLots(fqLots());
            FQ.show(li, p, back);
        } else if (sc === 'plant' || sc === 'goal') {
            $('cqStepName').textContent = (S.lots.length > 1 ? lotWord(idxOf(id)) + ' · ' : '') + NAMES[sc];
        } else {
            $('cqStepName').textContent = NAMES[sc] || '';
        }
        paintCount();
        $('cqNext').hidden = sc === 'review';
        if (sc === 'lots') paintLots();
        if (sc === 'plant') paintPlant(idxOf(id));
        if (sc === 'goal') paintGoal(idxOf(id));
        if (sc === 'buy') paintBuy();
        if (sc === 'workers') { paintWorkers(); loadPeople().then(paintRosterBtn); castSoon(true); }
        if (sc === 'place') $('cqPlace').value = S.place;
        if (sc === 'review') review();
        window.aneeStepTop?.($('cqWiz'));
        const top = $('cqWiz').getBoundingClientRect().top;
        if (top < 0 && !window.aneeStepTop) $('cqWiz').scrollIntoView({ behavior: 'auto', block: 'start' });
    }
    // What a page shows becomes the lot's own when the page is left.
    function commit(id) {
        if (id === undefined) return;
        const sc = screenOf(id), i = idxOf(id), l = S.lots[i];
        if (sc === 'plant' && l) l.pick = PAGE.pick ? PAGE.pick : pickOf(i);
        if (sc === 'goal' && l) { readGoal(); l.goal = PAGE.goal ? PAGE.goal : goalOf(i); }
        if (sc === 'buy') S.notes = $('cqNotes').value.trim();
        if (sc === 'place') S.place = $('cqPlace').value.trim();
    }
    function paintCount() {
        $('cqStepNo').textContent = 'Step ' + (pos + 1) + ' of ' + FLOW.length;
        $('cqBar').style.width = ((pos + 1) / FLOW.length * 100) + '%';
    }
    const goTo = (id, back) => { commit(FLOW[pos]); FLOW = flow(); const n = FLOW.indexOf(id); if (n >= 0) show(n, back); };
    let busy = false;
    async function next() {
        if (busy) return;
        busy = true;
        $('cqNext').disabled = true;
        try { if (await ready()) show(pos + 1); }
        finally { busy = false; $('cqNext').disabled = false; }
    }
    $('cqNext').addEventListener('click', next);
    $('cqBack').addEventListener('click', () => (pos === 0 ? window.aneeLeave() : show(pos - 1, true)));

    async function ready() {
        const id = FLOW[pos], sc = screenOf(id);
        if (sc === 'lots') return checkLots();
        if (sc === 'plant') {
            const p = PAGE.pick;
            if (!p.crop) { $('cqCropErr').hidden = false; shake($('cqCropTag')); return false; }
            if (!p.method) { $('cqMethodErr').hidden = false; shake($('cqMethods')); return false; }
            if (!p.month) { $('cqMonthErr').hidden = false; shake($('cqMonthTag')); return false; }
            return true;
        }
        if (sc === 'goal') {
            readGoal();
            const g = PAGE.goal;
            if (g.germination !== '' && !(num(g.germination) >= 1 && num(g.germination) <= 100)) { window.toast?.('Germination is a percent from 1 to 100.', 'error'); shake($('cqGerm')); return false; }
            return true;
        }
        if (sc === 'workers') { const ok = S.workers.some((w) => w.days.length); $('cqWErr').hidden = ok; if (!ok) shake($('cqWorkers')); return ok; }
        if (sc === 'place') {
            S.place = $('cqPlace').value.trim();
            $('cqPlaceErr').hidden = !!S.place;
            if (!S.place) shake($('cqPlace'));
            return !!S.place;
        }
        return true;
    }

    async function boot() {
        try { OPT = (await window.api(U.options, { method: 'GET' })).data; } catch (err) { window.toast?.(err.message, 'error'); }
        const gen = $('cpGen');
        if (gen && gen.classList.contains('an-wait')) {
            gen.classList.remove('an-wait');
            gen.classList.add('an-in');
            setTimeout(() => gen.classList.remove('an-in'), 400);
        }
        if (!OPT) return;
        paintQuote();
        paintCropList();
        FQ = window.aneeFieldProfile.mount($('cqFq'), {
            crop: null, lots: fqLots(), saved: false,
            onChange: (i, value, meta) => { const l = S.lots[i]; if (!l) return; l.profile = value; l.copiedFrom = meta.copiedFrom; l.edited = meta.edited; l.savedFrom = meta.from; },
            onSkip: (i) => { FLOW = flow(); const nx = FLOW.slice(pos + 1).find((id) => !String(id).startsWith('lot:' + i + ':')); if (nx) goTo(nx); },
        });
        show(0);
        const open = new URLSearchParams(location.search).get('open');
        if (open) openSaved(open);
    }
    const fqLots = () => S.lots.map((l, i) => ({ name: l.name || ('Lot ' + (i + 1)), value: l.profile, copiedFrom: l.copiedFrom, edited: l.edited, from: l.savedFrom }));

    /* ---- the price: by the lots, and the crops or varieties Anee reads up on ---- */
    const caseCount = () => new Set(S.lots.map((l, i) => { const p = pickOf(i); return [p.crop, p.method, String(p.variety || '').trim().toLowerCase()].join('|'); })).size;
    function quoteNow() {
        if (QUOTE != null) return QUOTE;
        const n = Math.max(1, S.lots.length), c = Math.max(0, caseCount() - 1);
        const row = OPT && OPT.quoteFor && OPT.quoteFor[n];
        return row && row[Math.min(c, 3)] != null ? Number(row[Math.min(c, 3)]) : Number(OPT && OPT.quote);
    }
    function paintQuote() {
        const q = $('cqQuote');
        if (!OPT.canUse) {
            q.className = 'cq-quote is-off';
            q.innerHTML = '<span>' + esc(OPT.whyNot || 'The analysis is not available right now.') + '</span>';
        } else {
            const pr = OPT.prices || {};
            q.className = 'cq-quote';
            q.innerHTML = (OPT.aneeFace ? '<img src="' + esc(OPT.aneeFace) + '" alt="">' : '')
                + '<span>Anee writes each lot\'s season by growth stage: fertilizer held to your target, the micronutrients, what to watch for, and one work calendar for all your lots. <b>' + esc(pr.base) + ' credits</b>' + (window.aneeTl ? window.aneeTl.flag('credits') : '')
                + ' for the first lot, ' + esc(pr.lot) + ' for each more lot, and ' + esc(pr.case) + ' for each more crop or variety she reads up on. You have '
                + (window.creditCoin ? window.creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString()) : '<b>' + esc(OPT.balance) + '</b>') + '. Nothing is charged until it is ready.</span>';
        }
        q.hidden = false;
    }

    /* ---------------- the lots ---------------- */
    function farmHead(l) {
        const FP = window.aneeFieldProfile, n = FP ? FP.count(l.profile) : 0;
        const i = S.lots.indexOf(l), ask = pickOf(i).crop ? askedPages(i).length : 0;
        return '<div class="cq-flot"><span aria-hidden="true"><img src="' + esc(U.lotIcon) + '" alt=""></span><div><b>' + esc(l.name) + '</b>'
            + (l.place ? '<small>' + esc(l.place) + '</small>' : '<small class="is-none">No place yet</small>')
            + '<span class="cq-flchips">' + (n ? '<span>' + n + (n === 1 ? ' answer' : ' answers') + '</span>' : '<span class="is-ask">No answers yet</span>')
            + (ask ? '<span class="is-ask">' + ask + (ask === 1 ? ' page' : ' pages') + ' of questions later</span>' : '')
            + (l.stale ? '<span class="is-old">Check it</span>' : '') + '</span></div>'
            + '<button type="button" class="cq-fledit" data-lot-edit="' + l.uid + '" aria-label="Edit ' + esc(l.name) + '">Edit</button></div>'
            // Preview answers and Edit answers (2026-10-19, the owner): each opens a sheet (partials/farm-lots).
            + (window.aneeLots && window.aneeLots.ansButtons ? window.aneeLots.ansButtons('data-lot-prev="' + l.uid + '"', 'data-lot-ans="' + l.uid + '"', 'is-under') : '');
    }
    function paintLots() {
        if (!S.lots.length) {
            $('cqLots').innerHTML = '<div class="cq-lotsnone"><span aria-hidden="true">🗺️</span><div><b>No lots in this protocol yet</b><small>Add them from your lots: each brings its place, size and answers. You can plan up to ' + esc(OPT ? OPT.lotMax : 8) + ' lots at once.</small></div></div>';
            total(); paintCount();
            return;
        }
        $('cqLots').innerHTML = S.lots.map((l, i) => '<div class="cq-lot" data-uid="' + l.uid + '" style="--lc:' + lc(i) + ';animation-delay:' + (i * 40) + 'ms">'
            + '<div class="cq-lot-top"><i></i><b>Lot ' + (i + 1) + '</b>' + (pickOf(i).crop && l.pick ? '<em>' + esc(pickLine(pickOf(i))) + '</em>' : '<em>from your lots</em>') + '</div>'
            + '<button type="button" class="cq-lot-x" data-lot-x="' + l.uid + '" aria-label="Remove lot ' + (i + 1) + '">' + XSM + '</button>'
            + farmHead(l)
            + '<div class="cq-size"><label for="cqLa' + l.uid + '">Size</label><input type="text" id="cqLa' + l.uid + '" class="form-input" data-area inputmode="decimal" maxlength="10" value="' + esc(l.areaHa) + '" autocomplete="off"><span>hectares' + (window.aneeTl ? window.aneeTl.flag('hectare') : '') + '</span></div>'
            + (l.areaFrom && !/ ha$/.test(l.areaFrom) ? '<p class="cq-sub cq-sub-s" style="margin-top:.3rem">The lot says ' + esc(l.areaFrom) + '.</p>' : '')
            + '</div>').join('');
        total();
        paintCount();
    }
    function total() {
        const a = S.lots.reduce((s, l) => s + num(l.areaHa), 0);
        $('cqLotTotal').textContent = S.lots.length ? S.lots.length + (S.lots.length === 1 ? ' lot' : ' lots') + (a > 0 ? ' · ' + (Math.round(a * 100) / 100).toLocaleString() + ' ha in all' : '') : '';
    }
    function checkLots() {
        if (!S.lots.length) { window.toast?.('Add a lot first, from your lots.', 'error'); shake($('cqLotAdd').parentNode); return false; }
        for (const l of S.lots) {
            const a = num(l.areaHa);
            if (!(a >= 0.001 && a <= 500)) {
                const inp = $('cqLa' + l.uid);
                window.toast?.('Type the size of ' + (l.name || 'the lot') + ' in hectares, like 1.5 or 0.25.', 'error');
                if (inp) { inp.scrollIntoView({ behavior: reduce() ? 'auto' : 'smooth', block: 'center' }); shake(inp.parentNode); }
                return false;
            }
        }
        return true;
    }
    $('cqLots').addEventListener('input', (e) => {
        const inp = e.target.closest('[data-area]');
        if (!inp) return;
        const l = S.lots.find((x) => String(x.uid) === inp.closest('.cq-lot').dataset.uid);
        if (l) { l.areaHa = inp.value.replace(/[^\d.,]/g, '').replace(',', '.'); total(); QUOTE = null; }
    });
    $('cqLots').addEventListener('click', async (e) => {
        const ed = e.target.closest('[data-lot-edit]');
        if (ed) { const l = S.lots.find((y) => String(y.uid) === ed.dataset.lotEdit); if (l && await editFarm(l)) { paintLots(); FQ && FQ.setLots(fqLots()); } return; }
        // The lot's answers in a sheet: Preview answers, or Edit answers. A save fires aneelots:changed (below).
        const pv = e.target.closest('[data-lot-prev], [data-lot-ans]');
        if (pv) {
            const l = S.lots.find((y) => String(y.uid) === (pv.dataset.lotPrev || pv.dataset.lotAns));
            if (!l || !window.aneeLots || !window.aneeLots.review) return;
            const lot = Object.assign({}, l.lot || {}, { id: l.farmLotId, name: l.name, answers: clone(l.profile), count: window.aneeFieldProfile ? window.aneeFieldProfile.count(l.profile) : 0 });
            const crop = fqCrop(S.lots.indexOf(l));
            if (pv.dataset.lotAns) await window.aneeLots.editAnswers(lot, { crop });
            else await window.aneeLots.review(lot, { crop, onEdit: async () => { if (await editFarm(l)) paintLots(); } });
            return;
        }
        const x = e.target.closest('[data-lot-x]');
        if (x) {
            const el = x.closest('.cq-lot');
            el.classList.add('is-gone');
            setTimeout(() => {
                const gone = S.lots.findIndex((l) => String(l.uid) === x.dataset.lotX);
                S.lots = S.lots.filter((l) => String(l.uid) !== x.dataset.lotX);
                S.lots.forEach((l) => { if (l.copiedFrom === gone) l.copiedFrom = null; else if (l.copiedFrom !== null && l.copiedFrom > gone) l.copiedFrom--; });
                QUOTE = null;
                paintLots(); if (FQ) FQ.setLots(fqLots());
            }, reduce() ? 0 : 220);
        }
    });
    const FARM = { picked: new Set(), rows: [] };
    const farmCount = () => { const n = FARM.picked.size; $('cqFarmCount').textContent = n ? n + (n === 1 ? ' lot ticked' : ' lots ticked') : 'None ticked'; $('cqFarmUse').disabled = !n; };
    async function paintFarm(fresh) {
        const L = window.aneeLots;
        FARM.rows = await L.rows(fresh);
        const inPlan = new Set(S.lots.map((l) => l.farmLotId).filter(Boolean));
        const card = (r, i) => {
            let h = L.card(r, inPlan.has(r.id) || FARM.picked.has(r.id), i).replace('role="radio"', 'role="checkbox"');
            if (inPlan.has(r.id)) h = h.replace('<button type="button" class="fl-card', '<button type="button" disabled class="fl-card').replace('<span class="fl-chips">', '<span class="fl-chips"><span class="fl-chip is-in">In the protocol</span>');
            return h;
        };
        $('cqFarmList').innerHTML = '<div class="fl-pick">' + (FARM.rows.length ? FARM.rows.map(card).join('')
            : '<div class="fl-empty">No lots yet. Add your first one: where it is, its size, and its soil and water. You answer it once, and every analysis reads it.</div>')
            + '<button type="button" class="fl-add" data-farm-new>' + PLUS + (FARM.rows.length ? 'Add a new lot' : 'Add a lot') + '</button>'
            + '<p class="fl-more"><span>Your lots are kept in Global Lots.</span><a href="' + esc(U.lots) + '" target="_blank" rel="noopener">Open Global Lots</a></p></div>';
    }
    $('cqLotAdd').addEventListener('click', async () => {
        const max = OPT ? OPT.lotMax : 8;
        if (S.lots.length >= max) { window.toast?.('Up to ' + max + ' lots in one protocol.', 'error'); return; }
        if (!window.aneeLots) { window.toast?.('Your lots could not load. Please try again.', 'error'); return; }
        FARM.picked.clear();
        farmCount();
        $('cqFarmList').innerHTML = '<p class="cq-snone">Loading your lots…</p>';
        sheetOpen($('cqFarmSheet'));
        try { await paintFarm(false); } catch (_) { $('cqFarmList').innerHTML = '<p class="cq-snone">Your lots could not load. Please try again.</p>'; }
    });
    $('cqFarmList').addEventListener('click', async (e) => {
        const L = window.aneeLots;
        if (!L) return;
        if (e.target.closest('[data-farm-new]')) {
            const row = await L.edit(null, { crop: null });
            if (row) { FARM.picked.add(row.id); await paintFarm(true); farmCount(); }
            return;
        }
        const c = e.target.closest('[data-fl-lot]');
        if (!c || c.disabled) return;
        const id = Number(c.getAttribute('data-fl-lot'));
        const r = FARM.rows.find((x) => x.id === id);
        if (!r) return;
        if (!r.where && !FARM.picked.has(id)) {
            const row = await L.edit(r, { crop: null, reason: 'place', ask: false });
            if (row) { FARM.picked.add(row.id); await paintFarm(true); farmCount(); }
            return;
        }
        if (FARM.picked.has(id)) FARM.picked.delete(id); else FARM.picked.add(id);
        const on = FARM.picked.has(id);
        c.classList.toggle('is-on', on);
        c.setAttribute('aria-checked', String(on));
        farmCount();
    });
    $('cqFarmUse').addEventListener('click', () => {
        const max = OPT ? OPT.lotMax : 8;
        let full = false;
        [...FARM.picked].map((id) => FARM.rows.find((r) => r.id === id)).filter(Boolean).forEach((r) => {
            if (S.lots.some((x) => x.farmLotId === r.id)) return;
            if (S.lots.length >= max) { full = true; return; }
            S.lots.push(fromFarm(r));
        });
        if (full) window.toast?.('Up to ' + max + ' lots in one protocol.', 'error');
        QUOTE = null;
        paintLots(); if (FQ) FQ.setLots(fqLots());
        sheetClose($('cqFarmSheet'));
    });
    window.addEventListener('aneelots:changed', (e) => {
        const r = e.detail && e.detail.lot;
        if (!r) return;
        S.lots.forEach((l) => { if (l.farmLotId === r.id) refreshFarm(l, r); });
        if (screenOf(FLOW[pos]) === 'lots') paintLots();
    });

    /* ---------------- what a lot grows: crop, way in, variety, month and day ---------------- */
    const PAGE = { i: 0, pick: null, goal: null };
    const whoHtml = (i) => '<i style="--lc:' + lc(i) + '"></i><b>' + (S.lots.length > 1 ? 'Lot ' + (i + 1) : 'Your lot') + '</b><span>' + esc([S.lots[i] ? S.lots[i].name : '', S.lots[i] ? S.lots[i].place : ''].filter(Boolean).join(' · ')) + '</span>';
    function paintPlant(i) {
        PAGE.i = i;
        const l = S.lots[i];
        PAGE.pick = clone(pickOf(i));
        const who = $('cqPlantWho');
        who.style.setProperty('--lc', lc(i));
        who.innerHTML = whoHtml(i);
        $('cqPlantQ').textContent = 'What will you plant on ' + (l && l.name ? l.name : 'this lot') + '?';
        $('cqPlantSub').textContent = i > 0 ? 'Pick what it grows, or tap the same as a lot before it.' : 'Pick the crop, how it goes in, its variety and the month. Each lot picks its own.';
        ['cqCropErr', 'cqMethodErr', 'cqMonthErr'].forEach((x) => { $(x).hidden = true; });
        paintPickTags();
    }
    function paintPickTags() {
        const p = PAGE.pick, c = cropOf(p.crop);
        const t = $('cqCropTag');
        t.classList.toggle('is-none', !c);
        $('cqCropIcon').textContent = c ? c.icon : '🌱';
        $('cqCropName').textContent = c ? cropShort(c.key) : 'Choose the crop';
        $('cqCropLine').textContent = c ? (c.perennial ? 'A tree crop, counted by its age' : (c.maturity ? 'About ' + c.maturity + ' days to harvest' : c.group)) : 'Opens the crop list';
        paintMethods();
        const vr = p.varRec;
        $('cqVarTag').classList.toggle('is-none', !p.variety);
        $('cqVarName').textContent = p.variety || "I don't know yet";
        $('cqVarLine').textContent = p.variety ? (vr && window.aneeVarietyPick ? ['On record', window.aneeVarietyPick.facts(vr)].filter(Boolean).join(' · ') : 'Your own name: Anee looks it up') : 'Pick from the records, or type the name on the seed bag';
        $('cqMonthTag').classList.toggle('is-none', !p.month);
        $('cqMonthName').textContent = p.month ? monthWord(p.month) : 'Pick the month';
        $('cqDayTag').disabled = !p.month;
        $('cqDayTag').classList.toggle('is-none', !p.day);
        $('cqDayName').textContent = p.day ? dayFmt(p.day, { weekday: 'long', month: 'short', day: 'numeric' }) : 'Any day';
        $('cqDayLine').textContent = p.day ? (S.mode === 'auto' ? 'Anee may move it up to two weeks if your crew is busy' : 'Kept as you set it') : (p.month ? 'Anee picks the day that suits your crew' : 'Pick the month first');
        paintSamePick();
    }
    function paintMethods() {
        const p = PAGE.pick, c = cropOf(p.crop);
        $('cqMethodBox').hidden = !c;
        if (!c) return;
        const keys = (c.methods && c.methods.length) ? c.methods : Object.keys(OPT.methods);
        if (p.method && !keys.includes(p.method)) p.method = null;
        if (keys.length === 1) p.method = keys[0];
        $('cqMethods').innerHTML = keys.map((k) => { const m = OPT.methods[k]; return m ? '<button type="button" class="cq-choice' + (p.method === k ? ' is-on' : '') + '" data-method="' + esc(k) + '"><span class="ce">' + esc(m.icon) + '</span><span class="ct"><b>' + esc(m.label) + '</b><small>' + esc(m.sub) + '</small></span><span class="ck">' + CHECK + '</span></button>' : ''; }).join('');
    }
    function paintSamePick() {
        const box = $('cqSamePick'), i = PAGE.i;
        if (!i) { box.hidden = true; box.innerHTML = ''; return; }
        const seen = [];
        const html = S.lots.slice(0, i).map((l, j) => {
            const p = pickOf(j);
            if (!p.crop || seen.some((q) => samePick(q, p))) return '';
            seen.push(p);
            const on = samePick(PAGE.pick, p);
            return '<button type="button" class="cq-spick' + (on ? ' is-on' : '') + '" data-spick="' + j + '" aria-pressed="' + on + '" style="--lc:' + lc(j) + '"><i></i><span><b>Same as ' + esc(lotWord(j)) + '</b><small>' + esc(pickLine(p)) + '</small></span><span class="ck">' + CHECK + '</span></button>';
        }).join('');
        box.innerHTML = html;
        box.hidden = !html;
    }
    $('cqSamePick').addEventListener('click', (e) => {
        const b = e.target.closest('[data-spick]');
        if (!b) return;
        PAGE.pick = clone(pickOf(Number(b.dataset.spick)));
        QUOTE = null;
        paintPickTags();
    });
    $('cqMethods').addEventListener('click', (e) => {
        const b = e.target.closest('[data-method]');
        if (!b) return;
        PAGE.pick.method = b.dataset.method;
        $('cqMethods').querySelectorAll('.cq-choice').forEach((c) => c.classList.toggle('is-on', c === b));
        $('cqMethodErr').hidden = true;
        QUOTE = null;
        paintSamePick();
    });
    // The crop list (a sheet: the tag opens the options).
    function paintCropList() {
        const groups = {};
        OPT.crops.forEach((c) => { (groups[c.group] = groups[c.group] || []).push(c); });
        $('cqCropList').innerHTML = Object.entries(groups).map(([g, list]) => '<div data-cgroup><b class="cq-sgh">' + esc(g) + '</b>' + list.map((c) => '<button type="button" class="cq-row" role="option" data-crop="' + esc(c.key) + '" data-find="' + esc((c.label + ' ' + g).toLowerCase()) + '"><span class="ri">' + esc(c.icon) + '</span><span class="rt"><b>' + esc(cropShort(c.key)) + '</b><small>' + esc(c.perennial ? 'Tree crop, counted by age' : (c.maturity ? c.maturity + ' days to harvest' : '')) + '</small></span></button>').join('') + '</div>').join('');
    }
    $('cqCropTag').addEventListener('click', () => {
        $('cqCropQ').value = '';
        cropSift();
        $('cqCropList').querySelectorAll('[data-crop]').forEach((r) => r.setAttribute('aria-selected', String(r.dataset.crop === PAGE.pick.crop)));
        sheetOpen($('cqCropSheet'), $('cqCropQ'));
    });
    const cropSift = () => {
        const q = $('cqCropQ').value.trim().toLowerCase();
        $('cqCropList').querySelectorAll('[data-cgroup]').forEach((g) => {
            let left = 0;
            g.querySelectorAll('[data-crop]').forEach((r) => { const hit = !q || r.dataset.find.includes(q); r.hidden = !hit; if (hit) left++; });
            g.hidden = !left;
        });
    };
    $('cqCropQ').addEventListener('input', cropSift);
    $('cqCropList').addEventListener('click', (e) => {
        const b = e.target.closest('[data-crop]');
        if (!b) return;
        const p = PAGE.pick;
        if (p.crop !== b.dataset.crop) { p.crop = b.dataset.crop; p.method = null; p.variety = ''; p.varRec = null; }
        $('cqCropErr').hidden = true;
        QUOTE = null;
        sheetClose($('cqCropSheet'));
        paintPickTags();
        bump($('cqCropTag'));
    });
    $('cqVarTag').addEventListener('click', async () => {
        if (!window.aneeVarietyPick) return;
        const p = PAGE.pick;
        if (!p.crop) { $('cqCropErr').hidden = false; shake($('cqCropTag')); return; }
        const l = S.lots[PAGE.i] || {};
        const L = (l.profile && Object.keys(l.profile).length && window.aneeLots) ? await window.aneeLots.legacy({ answers: l.profile }, null).catch(() => ({})) : {};
        const res = await window.aneeVarietyPick.open({ crop: p.crop, cropLabel: cropShort(p.crop).split(',')[0], location: l.place || '',
            water: L.water === 'rainfed' ? 'rainfed' : (L.water === 'limited' ? 'limited' : ''), value: { name: p.variety, record: p.varRec },
            ownNote: 'Not on record, so Anee looks it up from what is published. Type its potential on the goal page if the seed bag gives one.' });
        if (!res) return;
        p.variety = (res.name || '').trim();
        p.varRec = res.record || null;
        QUOTE = null;
        paintPickTags();
        bump($('cqVarTag'));
    });
    $('cqMonthTag').addEventListener('click', async () => {
        if (!window.aneeMonthPick) return;
        const k = await window.aneeMonthPick.open({ months: OPT.months, value: PAGE.pick.month, title: 'Which month will you plant?' });
        if (!k) return;
        if (PAGE.pick.day && !String(PAGE.pick.day).startsWith(k)) PAGE.pick.day = null;
        PAGE.pick.month = k;
        $('cqMonthErr').hidden = true;
        paintPickTags();
        bump($('cqMonthTag'));
    });
    // The day: inside the month, from tomorrow, or any day (Anee picks).
    function paintDays() {
        const p = PAGE.pick;
        const [y, m] = String(p.month).split('-').map(Number);
        const first = new Date(Date.UTC(y, m - 1, 1)), days = new Date(Date.UTC(y, m, 0)).getUTCDate();
        const tomorrow = new Date(String(OPT.today) + 'T00:00:00Z'); tomorrow.setUTCDate(tomorrow.getUTCDate() + 1);
        let h = '';
        for (let k = 0; k < first.getUTCDay(); k++) h += '<span></span>';
        for (let d = 1; d <= days; d++) {
            const ds = y + '-' + String(m).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            const dt = new Date(ds + 'T00:00:00Z');
            h += '<button type="button" class="cq-day' + (p.day === ds ? ' is-on' : '') + (dt.getUTCDay() === 0 ? ' is-sun' : '') + '" data-day="' + ds + '"' + (dt < tomorrow ? ' disabled' : '') + ' aria-label="' + esc(dayFmt(ds, { weekday: 'long', month: 'long', day: 'numeric' })) + '">' + d + '</button>';
        }
        $('cqDays').innerHTML = h;
        $('cqAnyDay').classList.toggle('is-on', !p.day);
        $('cqDayTitle').firstChild.textContent = 'Which day in ' + monthWord(p.month) + '?';
    }
    $('cqDayTag').addEventListener('click', () => { if (!PAGE.pick.month) return; paintDays(); sheetOpen($('cqDaySheet')); });
    $('cqAnyDay').addEventListener('click', () => { PAGE.pick.day = null; paintDays(); sheetClose($('cqDaySheet')); paintPickTags(); });
    $('cqDays').addEventListener('click', (e) => {
        const b = e.target.closest('[data-day]');
        if (!b || b.disabled) return;
        PAGE.pick.day = b.dataset.day;
        paintDays();
        sheetClose($('cqDaySheet'));
        paintPickTags();
        bump($('cqDayTag'));
    });

    /* ---------------- a lot's goal: the aim, the target, the potential, the stand ---------------- */
    const units = () => Object.keys((OPT && OPT.yieldUnits) || { tons: 'tons per hectare' });
    const tonsIn = (u) => Number(((OPT && OPT.yieldT) || {})[u]) || 1;
    const unitWord = (u) => String(((OPT && OPT.yieldUnits) || {})[u] || 'tons per hectare').replace(' per hectare', '');
    const recT = (r) => (r ? (r.top || r.avg || null) : null);
    function paintGoal(i) {
        PAGE.i = i;
        const l = S.lots[i], p = pickOf(i);
        PAGE.goal = clone(goalOf(i));
        const g = PAGE.goal;
        if (!g.yieldUnit || !units().includes(g.yieldUnit)) g.yieldUnit = units()[0];
        const c = cropOf(p.crop);
        if (!g.seedUnit || !g.seedTouched) g.seedUnit = (c && c.seedUnit) || 'kg';
        // The variety's potential from its record, while the farmer has not typed their own.
        const t = recT(p.varRec);
        if (t && (!g.potTouched || !g.potential)) { g.potential = fmt2(t / tonsIn(g.yieldUnit)); g.potTouched = false; }
        const who = $('cqGoalWho');
        who.style.setProperty('--lc', lc(i));
        who.innerHTML = whoHtml(i);
        $('cqGoalQ').textContent = 'Your goal on ' + (l && l.name ? l.name : 'this lot') + (c ? ', ' + cropShort(c.key).split(',')[0].toLowerCase() : '');
        $('cqGoals').innerHTML = Object.entries(OPT.priorities).map(([k, pr]) => '<button type="button" class="cq-choice' + (g.priority === k ? ' is-on' : '') + '" data-goal="' + esc(k) + '"><span class="ce">' + esc(pr.icon) + '</span><span class="ct"><b>' + esc(pr.label) + '</b><small>' + esc(pr.sub) + '</small></span><span class="ck">' + CHECK + '</span></button>').join('');
        $('cqUnits').innerHTML = units().map((k) => '<button type="button" class="cq-pill' + (k === g.yieldUnit ? ' is-on' : '') + '" data-unit="' + esc(k) + '" role="radio" aria-checked="' + (k === g.yieldUnit) + '"' + tlAt(k === 'cavans' ? 'cavans' : (k === 'tons' ? 't-ha' : '')) + '>' + esc(unitWord(k)) + '</button>').join('');
        $('cqPotential').value = g.potential;
        $('cqTarget').value = g.target;
        $('cqSeed').value = g.seedAmount;
        $('cqGerm').value = g.germination;
        potSay();
        paintSeedUnits();
        paintSameGoal();
        paintHabits();
        needSoon(true);
    }
    function readGoal() {
        if (!PAGE.goal || screenOf(FLOW[pos]) !== 'goal') return;
        const g = PAGE.goal;
        g.potential = $('cqPotential').value.trim();
        g.target = $('cqTarget').value.trim();
        g.seedAmount = $('cqSeed').value.trim();
        g.germination = $('cqGerm').value.trim();
    }
    function potSay() {
        const p = pickOf(PAGE.i), g = PAGE.goal, t = recT(p.varRec), box = $('cqPotFrom');
        if (!t) {
            box.className = 'cq-from is-own';
            box.innerHTML = p.variety ? '<span>' + esc(p.variety) + ' is not on record. Type its potential if the seed bag or the seller gives one. Without it, the goal is the usual yield here.</span>'
                : '<span>No variety named, so the goal is the usual yield for this crop here. Your target, if you type one, is held to the variety\'s potential.</span>';
            box.hidden = false;
            return;
        }
        const rec = fmt2(t / tonsIn(g.yieldUnit)) + ' ' + unitWord(g.yieldUnit), word = p.varRec.top ? 'its best trial yield' : 'its average trial yield';
        const own = g.potTouched && g.potential && Math.abs(num(g.potential) * tonsIn(g.yieldUnit) - t) > 0.05;
        box.className = 'cq-from' + (own ? ' is-own' : '');
        box.innerHTML = own ? '<span>Your own figure is used. The record says ' + esc(rec) + ', ' + word + '. <button type="button" data-pot-rec>Use the record\'s</button></span>'
            : '<span>From the record: <b>' + esc(rec) + '</b>, ' + word + ' for ' + esc(p.variety) + '. The fertilizer is sized to your target held to it, or 80 percent of it.</span>';
        box.hidden = false;
    }
    $('cqPotFrom').addEventListener('click', (e) => { if (!e.target.closest('[data-pot-rec]')) return; const t = recT(pickOf(PAGE.i).varRec); if (!t) return; PAGE.goal.potential = fmt2(t / tonsIn(PAGE.goal.yieldUnit)); PAGE.goal.potTouched = false; $('cqPotential').value = PAGE.goal.potential; potSay(); needSoon(); });
    $('cqPotential').addEventListener('input', () => { PAGE.goal.potTouched = true; PAGE.goal.potential = $('cqPotential').value.trim(); potSay(); needSoon(); });
    $('cqTarget').addEventListener('input', () => { PAGE.goal.target = $('cqTarget').value.trim(); needSoon(); });
    $('cqGoals').addEventListener('click', (e) => {
        const b = e.target.closest('[data-goal]');
        if (!b) return;
        PAGE.goal.priority = b.dataset.goal;
        $('cqGoals').querySelectorAll('.cq-choice').forEach((c) => c.classList.toggle('is-on', c === b));
        paintSameGoal();
    });
    /* How the lot is farmed (2026-10-19, the owner: the protocol counts what NPK Plus counts): two or three crops a
       year and zinc in the last two seasons, lot answers NpkNeed::run reads. Asked here only when the lot's last
       season page is answered already (the field questions step asks them otherwise); the answer rides in the
       lot's profile and goes back into the lot on the run. */
    const HABITS = ['cropsYear', 'zincGiven'];
    function paintHabits() {
        const i = PAGE.i, l = S.lots[i], FP = window.aneeFieldProfile, box = $('cqHabits');
        const keys = l && FP && FP.questions ? HABITS.filter((k) => FP.questions[k] && pageDone(l.base, 'last', fqCrop(i)) && !answeredVal((l.base || {})[k])) : [];
        box.hidden = !keys.length;
        if (!keys.length) { box.innerHTML = ''; return; }
        const v = l.profile || {};
        box.innerHTML = keys.map((k) => { const q = FP.questions[k]; return '<span class="cq-label">' + esc(q.q) + ' <small>(optional)</small></span>'
            + '<div class="cq-pills" role="radiogroup" aria-label="' + esc(q.short || q.q) + '">' + Object.entries(q.o || {}).map(([o, lab]) => '<button type="button" class="cq-pill' + (v[k] === o ? ' is-on' : '') + '" data-habit="' + esc(k) + '" data-v="' + esc(o) + '" role="radio" aria-checked="' + (v[k] === o) + '">' + esc(lab[0]) + '</button>').join('') + '</div>'
            + (q.why ? '<p class="cq-sub cq-sub-s">' + esc(q.why) + '</p>' : ''); }).join('');
    }
    $('cqHabits').addEventListener('click', (e) => {
        const b = e.target.closest('[data-habit]'), l = S.lots[PAGE.i];
        if (!b || !l) return;
        const k = b.dataset.habit;
        l.profile = l.profile || {};
        // A second press on the same answer takes it back.
        if (l.profile[k] === b.dataset.v) delete l.profile[k]; else l.profile[k] = b.dataset.v;
        b.parentElement.querySelectorAll('[data-habit]').forEach((x) => { const on = l.profile[k] === x.dataset.v; x.classList.toggle('is-on', on); x.setAttribute('aria-checked', String(on)); });
        needSoon();
    });
    $('cqUnits').addEventListener('click', (e) => {
        const b = e.target.closest('[data-unit]');
        if (!b) return;
        const g = PAGE.goal, was = g.yieldUnit;
        g.yieldUnit = b.dataset.unit;
        // The numbers follow the unit, so 140 cavans becomes 7 tons.
        const conv = (v) => (num(v) > 0 ? fmt2(num(v) * tonsIn(was) / tonsIn(g.yieldUnit)) : v);
        g.potential = conv($('cqPotential').value); g.target = conv($('cqTarget').value);
        $('cqPotential').value = g.potential; $('cqTarget').value = g.target;
        $('cqUnits').querySelectorAll('.cq-pill').forEach((u) => { u.classList.toggle('is-on', u === b); u.setAttribute('aria-checked', String(u === b)); });
        potSay(); paintSameGoal(); needSoon();
    });
    const npkKey = () => {
        const p = pickOf(PAGE.i), c = cropOf(p.crop);
        if (!c || !c.npk) return null;
        return c.npk === 'rice' && /^direct_(wet|dry)$/.test(p.method || '') && OPT.seeding['rice_dsr_' + p.method.slice(7)] ? 'rice_dsr_' + p.method.slice(7) : c.npk;
    };
    function paintSeedUnits() {
        const g = PAGE.goal;
        $('cqSeedUnits').innerHTML = Object.entries(OPT.seedUnits || {}).map(([k, u]) => '<button type="button" class="cq-pill' + (k === g.seedUnit ? ' is-on' : '') + '" data-seedunit="' + esc(k) + '" role="radio" aria-checked="' + (k === g.seedUnit) + '" title="' + esc(u[2]) + '">' + esc(u[0]) + '</button>').join('');
        $('cqSeedU').textContent = '(' + ((OPT.seedUnits[g.seedUnit] || [])[1] || g.seedUnit) + ')';
        $('cqGermBox').hidden = !['kg', 'g', 'seeds', 'hills'].includes(g.seedUnit);
        seedHint();
    }
    $('cqSeedUnits').addEventListener('click', (e) => { const b = e.target.closest('[data-seedunit]'); if (!b) return; PAGE.goal.seedUnit = b.dataset.seedunit; PAGE.goal.seedTouched = true; paintSeedUnits(); needSoon(); });
    const span = (u) => { const f = (x) => Number(x).toLocaleString(undefined, { maximumFractionDigits: 2 }); return u[0] === u[1] ? f(u[0]) : f(u[0]) + ' to ' + f(u[1]); };
    function seedHint() {
        const sd = OPT && OPT.seeding ? OPT.seeding[npkKey()] : null, g = PAGE.goal;
        const v = num($('cqSeed').value), a = num((S.lots[PAGE.i] || {}).areaHa), unit = g.seedUnit;
        const kind = unit === 'kg' || unit === 'g' ? 'w' : 'n', u = sd && sd[kind];
        const word = kind === 'w' ? 'kg' : ((sd && sd.countWord) || 'plants');
        let say = '', warn = false;
        if (v > 0 && a > 0) {
            let perHa = (unit === 'g' ? v / 1000 : v) / a;
            const raw = perHa, gm = num($('cqGerm').value);
            if (['kg', 'g', 'seeds', 'hills'].includes(unit) && gm > 0) perHa = perHa * Math.min(100, gm) / (OPT.germUsual || 85);
            say = 'That is ' + Number(raw).toLocaleString(undefined, { maximumFractionDigits: raw >= 100 ? 0 : 1 }) + ' ' + word + ' a hectare.';
            if (u) {
                say += ' The usual is ' + span(u) + ' ' + word + '.';
                if (perHa < u[0] * 0.75) { warn = true; say += ' Yours is thinner, so the need is counted for ' + Math.round(Math.max(0.3, perHa / (u[0] * 0.75)) * 100) + ' percent of the goal.'; }
                else if (perHa > u[1] * 1.5) { warn = true; say += ' Yours is much more: crowded plants need no more fertilizer and give no more harvest.'; }
            }
        } else {
            say = u ? 'The usual for this crop is ' + span(u) + ' ' + word + ' a hectare. Leave it empty if you are not sure.' : 'Leave it empty if you are not sure.';
        }
        const h = $('cqSeedHint');
        h.textContent = say;
        h.style.color = warn ? '#92400e' : '';
    }
    ['cqSeed', 'cqGerm'].forEach((id) => $(id).addEventListener('input', () => { seedHint(); needSoon(); }));
    function paintSameGoal() {
        const box = $('cqSameGoal'), i = PAGE.i;
        if (!i) { box.hidden = true; box.innerHTML = ''; return; }
        const g = PAGE.goal;
        const line = (x) => [(OPT.priorities[x.priority] || {}).label, x.target ? 'target ' + x.target + ' ' + unitWord(x.yieldUnit) : 'no target'].filter(Boolean).join(' · ');
        const same = (a, b) => a.priority === b.priority && String(a.target || '') === String(b.target || '') && a.yieldUnit === b.yieldUnit;
        const seen = [];
        const html = S.lots.slice(0, i).map((l, j) => {
            const x = goalOf(j);
            if (seen.some((y) => same(y, x))) return '';
            seen.push(x);
            const on = same(g, x);
            return '<button type="button" class="cq-spick' + (on ? ' is-on' : '') + '" data-sgoal="' + j + '" aria-pressed="' + on + '" style="--lc:' + lc(j) + '"><i></i><span><b>Same goal as ' + esc(lotWord(j)) + '</b><small>' + esc(line(x)) + '</small></span><span class="ck">' + CHECK + '</span></button>';
        }).join('');
        box.innerHTML = html;
        box.hidden = !html;
    }
    $('cqSameGoal').addEventListener('click', (e) => {
        const b = e.target.closest('[data-sgoal]');
        if (!b) return;
        const x = clone(goalOf(Number(b.dataset.sgoal))), g = PAGE.goal;
        Object.assign(g, { priority: x.priority, yieldUnit: x.yieldUnit, target: x.target });
        // The potential is the variety's: kept only for the same variety.
        if ((pickOf(Number(b.dataset.sgoal)).variety || '') === (pickOf(PAGE.i).variety || '')) Object.assign(g, { potential: x.potential, potTouched: x.potTouched });
        const keep = PAGE.i;
        S.lots[keep].goal = g;
        paintGoal(keep);
    });

    /* ---- the need of one lot, live: the same count the protocol is held to ---- */
    let needT = null, needAsk = 0;
    const needSoon = (now) => { clearTimeout(needT); needT = setTimeout(loadNeed, now ? 0 : 500); };
    async function loadNeed() {
        const box = $('cqNeed'), i = PAGE.i, ask = ++needAsk;
        if (screenOf(FLOW[pos]) !== 'goal') return;
        readGoal();
        box.classList.add('is-busy');
        let n = null;
        try { n = (await window.api(U.need, { method: 'POST', body: { lots: [lotBody(i, true)], crew: crewBody(), mode: S.mode } })).data.lots[0].need; } catch (_) { n = null; }
        if (ask !== needAsk) return;
        box.classList.remove('is-busy');
        if (!n || !n.known) {
            box.innerHTML = '<div class="cq-need-h">The fertilizer need</div><p>NPK Plus has no numbers for this crop yet, so Anee works the need out from the official guides for your region.</p>';
            box.hidden = false;
            return;
        }
        const g = PAGE.goal, t = (x) => fmt2(x / tonsIn(g.yieldUnit)) + ' ' + unitWord(g.yieldUnit) + ' a hectare';
        const lines = [];
        if (n.seed && n.seed.thin) lines.push('Your stand is thin, so the need is sized to the plants you will have.');
        if (n.goal && n.goal.capped) lines.push('The lot\'s water, salt or sodium hold the harvest lower, so the need is too.');
        if (n.credit && n.credit.N) lines.push(n.credit.N + ' kg less nitrogen for the legume before.');
        if (n.credit && n.credit.K2O) lines.push(n.credit.K2O + ' kg less potassium for the rice straw plowed back.');
        (n.whys || []).slice(0, 3).forEach((w) => lines.push(w + '.'));
        // The weather of these weeks and the ENSO outlook, as NPK Plus counts them (2026-10-19).
        if (n.season && n.season.words) lines.push(n.season.words);
        if (n.pLock) lines.push('This soil locks phosphorus: Anee places it in a band, not spread.');
        if (n.areaSoil && n.areaSoil.length) {
            const AS = { N: 'nitrogen', P2O5: 'phosphorus', K2O: 'potassium', ph: 'pH', texture: 'soil type', conditions: 'salt and organic matter' };
            lines.push('With no soil test for ' + andList(n.areaSoil.map((k) => AS[k] || k)) + ', the levels usual for your area are used. A soil test of this lot replaces them.');
        }
        const lvl = { watch: 'may run short', likely: 'likely short', test: 'low by the soil test' };
        const amt = (v) => { const x = Number(v) || 0; return x >= 1 ? (Math.round(x * 10) / 10) + ' kg' : Math.round(x * 1000) + ' g'; };
        const mics = Object.entries(n.micros || {}).filter(([, m]) => !m.beneficial);
        box.innerHTML = '<div class="cq-need-h">This lot\'s need a hectare, counted like NPK Plus' + (window.aneeTl ? window.aneeTl.flag('npk-plus') : '') + '</div>'
            + '<div class="cq-need-n"><span><b>' + kgFmt(n.need.N) + '</b><small>kg N</small></span><span><b>' + kgFmt(n.need.P2O5) + '</b><small>kg P₂O₅</small></span><span><b>' + kgFmt(n.need.K2O) + '</b><small>kg K₂O</small></span></div>'
            + (mics.length ? '<div class="cq-need-m">' + mics.map(([, m]) => '<span>' + esc(m.name) + ' ' + esc(amt(m.need)) + ' <small>' + esc(lvl[m.level] || '') + '</small></span>').join('') + '</div>' : '')
            + (n.goal ? '<p>For <b>' + esc(t(n.goal.eff)) + '</b>' + (Math.abs(n.goal.eff - n.goal.t) > 0.01 ? ' (' + esc(t(n.goal.t)) + ', ' + esc(n.goal.why) + ', less for this lot)' : ', ' + esc(n.goal.why)) + '. Every program is held to this, and what still falls short is added for you.</p>' : '')
            + (lines.length ? '<p>' + lines.map(esc).join(' ') + '</p>' : '');
        box.hidden = false;
    }

    /* ---------------- fertilizer sold near the farm, and a note ---------------- */
    function paintBuy() {
        $('cqGranulars').innerHTML = Object.entries(OPT.granulars || {}).map(([k, label]) => '<button type="button" class="wtp-choice' + (S.granulars.includes(k) ? ' is-on' : '') + '" data-granular="' + esc(k) + '" aria-pressed="' + S.granulars.includes(k) + '"><span class="c-e"></span><span>' + esc(label) + '</span></button>').join('');
        $('cqNotes').value = S.notes;
    }
    $('cqGranulars').addEventListener('click', (e) => {
        const b = e.target.closest('[data-granular]');
        if (!b) return;
        const k = b.dataset.granular;
        S.granulars = S.granulars.includes(k) ? S.granulars.filter((x) => x !== k) : [...S.granulars, k];
        b.classList.toggle('is-on', S.granulars.includes(k));
        b.setAttribute('aria-pressed', String(S.granulars.includes(k)));
    });

    /* ---------------- the crew ---------------- */
    let PEOPLE = null;
    const loadPeople = async () => {
        if (PEOPLE) return PEOPLE;
        try { PEOPLE = OPT.people ? (await window.api(OPT.people, { method: 'GET' })).data : null; } catch (_) { PEOPLE = null; }
        PEOPLE = PEOPLE || { roster: [], contacts: [], canSave: false };
        return PEOPLE;
    };
    function paintWorkers() {
        $('cqWCount').textContent = S.workers.length;
        $('cqWWord').textContent = S.workers.length === 1 ? 'worker' : 'workers';
        $('cqWorkers').innerHTML = S.workers.map((w, i) => '<div class="cq-wk' + (w.globalId ? ' is-roster' : '') + '" data-w="' + w.uid + '" style="animation-delay:' + Math.min(i, 8) * 30 + 'ms"><div class="cq-wk-top"><i>' + (i + 1) + '</i><input type="text" data-wname maxlength="40" value="' + esc(w.name) + '" placeholder="Name (optional)" aria-label="Name of worker ' + (i + 1) + '"><small data-wdays>' + daysWord(w.days) + '</small></div>'
            + (w.globalId ? '<span class="cq-wk-from">👥 From My workers</span>' : '')
            + '<div class="cq-d7" role="group" aria-label="Work days of worker ' + (i + 1) + '">' + DN2.map((n, d) => '<button type="button" data-wd="' + d + '" class="' + (w.days.includes(d) ? 'is-on' : '') + '" aria-pressed="' + w.days.includes(d) + '">' + n + '</button>').join('') + '</div></div>').join('');
        $('cqSameDays').hidden = S.workers.length < 2;
        $('cqSCount').textContent = S.sprayers;
        $('cqSWord').textContent = S.sprayers === 1 ? 'sprayer' : 'sprayers';
        document.querySelectorAll('#cqPlantBy [data-plantby]').forEach((b) => b.classList.toggle('is-on', b.dataset.plantby === S.plantBy));
        document.querySelectorAll('#cqMode [data-mode]').forEach((b) => b.classList.toggle('is-on', b.dataset.mode === S.mode));
        paintSaveNew();
        castSoon();
    }
    const paintSaveNew = () => { const any = !!(PEOPLE && PEOPLE.canSave) && S.workers.some((w) => !w.globalId && w.name.trim()); $('cqSaveNew').hidden = !any; };
    function paintRosterBtn() {
        const r = (PEOPLE && PEOPLE.roster) || [];
        $('cqRosterOpen').hidden = !r.length;
        const inPlan = S.workers.filter((w) => w.globalId).length;
        $('cqRosterLine').textContent = r.length + (r.length === 1 ? ' worker' : ' workers') + ' on your list' + (inPlan ? ', ' + inPlan + ' in this protocol' : '') + '. They bring their days off.';
        paintSaveNew();
    }
    $('cqSaveNewOn').addEventListener('change', (e) => { S.saveNew = e.target.checked; });
    let RPICK = new Set();
    function paintRoster() {
        const q = $('cqRosterQ').value.trim().toLowerCase();
        const rows = ((PEOPLE && PEOPLE.roster) || []).filter((g) => !q || (g.name + ' ' + g.phone).toLowerCase().includes(q));
        $('cqRosterList').innerHTML = rows.length ? rows.map((g) => '<button type="button" class="cq-row" role="option" data-gw="' + g.id + '" aria-checked="' + RPICK.has(g.id) + '" aria-selected="' + RPICK.has(g.id) + '"><span class="ri">' + esc((g.name || '?').trim().charAt(0).toUpperCase()) + '</span><span class="rt"><b>' + esc(g.name) + '</b><small>'
            + esc([(g.off || []).length ? 'Off ' + g.off.map((d) => DN[d]).join(', ') : 'Works every day', g.phone].filter(Boolean).join(' · ')) + '</small></span><span class="cq-check">' + CHECK + '</span></button>').join('')
            : '<p class="cq-snone">Nobody by that name.</p>';
        $('cqRosterCount').textContent = RPICK.size ? RPICK.size + ' ticked' : 'None ticked';
    }
    $('cqRosterOpen').addEventListener('click', async () => { await loadPeople(); RPICK = new Set(S.workers.filter((w) => w.globalId).map((w) => w.globalId)); $('cqRosterQ').value = ''; paintRoster(); sheetOpen($('cqRosterSheet'), $('cqRosterQ')); });
    $('cqRosterQ').addEventListener('input', paintRoster);
    $('cqRosterList').addEventListener('click', (e) => {
        const b = e.target.closest('[data-gw]');
        if (!b) return;
        const id = Number(b.dataset.gw);
        if (RPICK.has(id)) RPICK.delete(id); else RPICK.add(id);
        b.setAttribute('aria-checked', String(RPICK.has(id)));
        b.setAttribute('aria-selected', String(RPICK.has(id)));
        $('cqRosterCount').textContent = RPICK.size ? RPICK.size + ' ticked' : 'None ticked';
    });
    $('cqRosterUse').addEventListener('click', () => {
        const roster = (PEOPLE && PEOPLE.roster) || [];
        S.workers = S.workers.filter((w) => !w.globalId || RPICK.has(w.globalId));
        const have = new Set(S.workers.map((w) => w.globalId).filter(Boolean));
        let added = 0;
        roster.filter((g) => RPICK.has(g.id) && !have.has(g.id)).forEach((g) => {
            const days = [0, 1, 2, 3, 4, 5, 6].filter((d) => !(g.off || []).includes(d));
            const facts = { name: g.name, days: (g.off || []).length ? days : [1, 2, 3, 4, 5, 6], globalId: g.id };
            const empty = S.workers.find((w) => !w.globalId && !w.name.trim());
            if (empty) Object.assign(empty, facts); else if (S.workers.length < 100) S.workers.push(newWorker(facts));
            added++;
        });
        if (!S.workers.length) S.workers.push(newWorker());
        sheetClose($('cqRosterSheet'));
        paintWorkers();
        paintRosterBtn();
        if (S.workers.some((x) => x.days.length)) $('cqWErr').hidden = true;
        if (added) window.toast?.(added + (added === 1 ? ' worker' : ' workers') + ' from My workers. Check their days below.');
    });
    const setWorkers = (n) => {
        n = Math.max(1, Math.min(100, Math.round(n) || 1));
        while (S.workers.length < n) S.workers.push(newWorker({ days: (S.workers[S.workers.length - 1] || {}).days ? S.workers[S.workers.length - 1].days.slice() : [1, 2, 3, 4, 5, 6] }));
        if (S.workers.length > n) S.workers = S.workers.slice(0, n);
        paintWorkers();
    };
    document.querySelectorAll('[data-wstep]').forEach((b) => b.addEventListener('click', () => setWorkers(S.workers.length + Number(b.dataset.wstep))));
    document.querySelectorAll('[data-sstep]').forEach((b) => b.addEventListener('click', () => { S.sprayers = Math.max(1, Math.min(50, S.sprayers + Number(b.dataset.sstep))); paintWorkers(); }));
    $('cqWorkers').addEventListener('click', (e) => {
        const b = e.target.closest('[data-wd]');
        if (!b) return;
        const w = S.workers.find((x) => String(x.uid) === b.closest('.cq-wk').dataset.w);
        const d = Number(b.dataset.wd);
        w.days = w.days.includes(d) ? w.days.filter((x) => x !== d) : w.days.concat(d).sort();
        b.classList.toggle('is-on', w.days.includes(d));
        b.setAttribute('aria-pressed', String(w.days.includes(d)));
        b.closest('.cq-wk').querySelector('[data-wdays]').textContent = daysWord(w.days);
        if (S.workers.some((x) => x.days.length)) $('cqWErr').hidden = true;
    });
    $('cqWorkers').addEventListener('input', (e) => {
        const card = e.target.closest('.cq-wk');
        const w = card && S.workers.find((x) => String(x.uid) === card.dataset.w);
        if (w && e.target.closest('[data-wname]')) { w.name = e.target.value; paintSaveNew(); }
    });
    $('cqSameDays').addEventListener('click', () => { const d = S.workers[0].days.slice(); S.workers.forEach((w) => { w.days = d.slice(); }); paintWorkers(); window.toast?.('Every worker now works ' + (d.length === 7 ? 'every day' : d.map((x) => DN2[x]).join(', ')) + '.'); });
    $('cqPlantBy').addEventListener('click', (e) => { const b = e.target.closest('[data-plantby]'); if (!b) return; S.plantBy = b.dataset.plantby; paintWorkers(); });
    $('cqMode').addEventListener('click', (e) => { const b = e.target.closest('[data-mode]'); if (!b) return; S.mode = b.dataset.mode; paintWorkers(); });
    // Any change on the crew page asks the calendar again, after a pause.
    document.querySelector('.cq-step[data-screen="workers"]').addEventListener('click', (e) => { if (!e.target.closest('#cqCast')) castSoon(); });

    /* ---------------- the work calendar before the run (ProtocolCrew, no AI) ---------------- */
    const crewBody = () => ({ workers: S.workers.map((w) => ({ name: w.name.trim(), days: w.days, globalId: w.globalId })), sprayers: S.sprayers, plantBy: S.plantBy, saveNew: S.saveNew });
    function lotBody(i, fromPage) {
        const l = S.lots[i], p = fromPage && screenOf(FLOW[pos]) === 'plant' && PAGE.i === i ? PAGE.pick : pickOf(i);
        const g = fromPage && screenOf(FLOW[pos]) === 'goal' && PAGE.i === i ? PAGE.goal : goalOf(i);
        return { name: l.name, farmLotId: l.farmLotId, areaHa: num(l.areaHa) || null, place: l.place || S.place, province: l.province, town: l.town, profile: l.profile,
            crop: p.crop, method: p.method, variety: p.variety, days: p.varRec && p.varRec.days ? p.varRec.days : null, month: p.month, day: p.day,
            priority: g.priority, targetYield: num(g.target) > 0 ? num(g.target) : null, yieldUnit: g.yieldUnit || units()[0], potential: num(g.potential) > 0 ? num(g.potential) : null,
            seedAmount: num(g.seedAmount) > 0 ? num(g.seedAmount) : null, seedUnit: g.seedUnit, germination: num(g.germination) > 0 ? num(g.germination) : null };
    }
    const body = () => ({ lots: S.lots.map((_, i) => lotBody(i)), crew: crewBody(), mode: S.mode, granulars: S.granulars, notes: S.notes, location: S.place });
    let castT = null, castAsk = 0, castSig = '';
    const castSoon = (now) => { clearTimeout(castT); castT = setTimeout(() => paintCast('cqCast'), now ? 0 : 450); };
    const fadeSwap = (el, html) => { if (reduce() || !el.innerHTML) { el.innerHTML = html; return; } el.style.opacity = '0'; setTimeout(() => { el.innerHTML = html; el.style.opacity = ''; }, 180); };
    async function paintCast(hostId, needs) {
        const box = $(hostId);
        if (!S.lots.length) { box.innerHTML = ''; return null; }
        const b = Object.assign(body(), { needs: needs ? 1 : 0 });
        const key = hostId + JSON.stringify([b.lots.map((l) => [l.areaHa, l.crop, l.method, l.month, l.day, l.variety]), b.crew, b.mode, needs]);
        if (key === castSig && box.innerHTML) return null;
        castSig = key;
        const ask = ++castAsk;
        box.classList.add('is-busy');
        let d = null;
        try { d = (await window.api(U.need, { method: 'POST', body: b })).data; } catch (_) { d = null; }
        if (ask !== castAsk) return null;
        box.classList.remove('is-busy');
        if (!d) { castSig = ''; return null; }
        QUOTE = Number(d.quote) || null;
        fadeSwap(box, castHtml(d.schedule, hostId === 'cqCastRev'));
        return d;
    }
    function castHtml(s, onReview) {
        const many = s.lots.length > 1, late = s.late || [], short = s.short || [];
        const warn = !s.ok;
        const head = s.ok ? (many ? 'Your crew keeps up on every lot' : 'Your crew keeps up') : (s.mode === 'keep' ? (s.shortCount === 1 ? 'One day asks more workers than you have' : s.shortCount + ' days ask more workers than you have')
            : (s.lateCount === 1 ? 'One job cannot finish in its safe days' : s.lateCount + ' jobs cannot finish in their safe days'));
        const lotIdx = (l) => l.i;
        const rows = s.lots.map((l) => {
            const i = lotIdx(l), moved = l.shift > 0, isLate = late.some((x) => x.lot === i);
            const why = l.why && l.why.names && l.why.names.length ? ' The crew is busy then with ' + andList(l.why.names) + '.' : '';
            const main = 'Planted ' + dayFmt(l.planting) + (l.asked ? (moved ? ', ' + l.shift + ' days after your day' : ', your day') : (moved ? ', the first day that fits' : ''));
            return '<div class="cq-cast-row"><b><i style="--lc:' + lc(i) + '"></i>' + (many ? 'Lot ' + (i + 1) : 'Your lot') + '</b><span class="' + (isLate ? 'is-late' : (moved ? 'is-moved' : 'is-ok')) + '">' + esc((many ? l.name + ' · ' : '') + main)
                + (why || isLate ? '<small>' + esc((why + (isLate ? ' Some jobs on this lot still run short of hands.' : '')).trim()) + '</small>' : '') + '</span></div>';
        }).join('');
        const lines = [];
        short.slice(0, 5).forEach((d) => lines.push(dayFmt(d.date) + ': ' + d.need + ' workers needed, you have ' + d.cap + (d.sprayOver ? ' (and ' + d.sprayOver + ' more sprayer' + (d.sprayOver === 1 ? '' : 's') + ')' : '') + '. ' + andList(d.items.filter((x) => !x.hired).map((x) => (many ? 'Lot ' + (x.lot + 1) + ' ' : '') + x.title.toLowerCase()))));
        late.slice(0, 5).forEach((t) => lines.push((many ? 'Lot ' + (t.lot + 1) + ', ' : '') + t.title + ': it needs more hands to be done by ' + dayFmt(t.hi) + '.'));
        const tips = [];
        const use = (n) => (onReview ? '<button type="button" class="btn btn-white" data-jump="workers">Change the workers</button>' : '<button type="button" class="btn btn-primary" data-castuse="' + n + '">Use ' + (S.workers.length + n) + ' workers</button>');
        if (warn) {
            if (s.more) tips.push(['With ' + (S.workers.length + s.more) + ' workers working Monday to Saturday, ' + s.more + ' more than now, everything fits.', use(s.more)]);
            if (s.moreSprayers) tips.push([(s.more ? 'Or ' : '') + s.moreSprayers + ' more sprayer' + (s.moreSprayers === 1 ? '' : 's') + ' would do it.', onReview ? '' : '<button type="button" class="btn btn-white" data-castspray="' + s.moreSprayers + '">Use ' + (S.sprayers + s.moreSprayers) + ' sprayers</button>']);
            if (S.plantBy === 'own' && late.concat(short.flatMap((d) => d.items)).some((x) => /transplant|sow|plant|harvest/i.test(x.title || ''))) tips.push(['Most of it is the planting and the harvest. A hired crew for those days takes them off your workers.', onReview ? '' : '<button type="button" class="btn btn-white" data-casthired>A hired crew plants and harvests</button>']);
            if (S.mode === 'keep') tips.push(['Or let Anee move the jobs inside their safe days.', onReview ? '' : '<button type="button" class="btn btn-white" data-castauto>Let Anee move them</button>']);
        }
        return '<div class="cq-cast-c' + (warn ? ' is-warn' : '') + '"><div class="cq-cast-h"><span aria-hidden="true">' + (warn ? '⚠️' : '✅') + '</span><b>' + esc(head) + '</b></div>'
            + '<p>' + esc('Counted from the usual fertilizer days, with your ' + s.crew.workers + (s.crew.workers === 1 ? ' worker' : ' workers') + ' and ' + s.crew.sprayers + (s.crew.sprayers === 1 ? ' sprayer' : ' sprayers') + (s.crew.plantBy === 'hired' ? ', a hired crew planting and harvesting' : '') + '. The protocol lays out Anee\'s own program the same way.') + '</p>'
            + '<div class="cq-cast-rows">' + rows + '</div>'
            + (lines.length ? '<div class="cq-cast-do">' + lines.map((t) => '<p>' + esc(t) + '</p>').join('') + '</div>' : '')
            + (tips.length ? '<div class="cq-cast-do">' + tips.map(([t, b]) => '<p>' + esc(t) + '</p>' + b).join('') + '</div>' : '')
            + (s.peak ? '<p style="margin-top:.5rem">' + esc('The busiest day is ' + dayFmt(s.peak.date) + ', with ' + s.peak.workers + ' of your ' + s.peak.cap + ' workers busy. ' + s.workDays + ' days of field work in all.') + '</p>' : '') + '</div>';
    }
    $('cqCast').addEventListener('click', (e) => {
        const u = e.target.closest('[data-castuse]');
        if (u) { setWorkers(S.workers.length + Number(u.dataset.castuse)); window.toast?.('Set to ' + S.workers.length + ' workers. Check their days above.'); return; }
        const sp = e.target.closest('[data-castspray]');
        if (sp) { S.sprayers += Number(sp.dataset.castspray); paintWorkers(); return; }
        if (e.target.closest('[data-casthired]')) { S.plantBy = 'hired'; paintWorkers(); return; }
        if (e.target.closest('[data-castauto]')) { S.mode = 'auto'; paintWorkers(); }
    });
    $('cqCastRev').addEventListener('click', (e) => { const j = e.target.closest('[data-jump]'); if (j) goTo(j.dataset.jump, true); });

    /* ---------------- the review ---------------- */
    async function review() {
        const rows = [];
        S.lots.forEach((l, i) => {
            const p = pickOf(i), g = goalOf(i), c = cropOf(p.crop);
            const when = p.month ? monthWord(p.month) + (p.day ? ', ' + dayFmt(p.day, { month: 'short', day: 'numeric' }) : ', any day') : 'No month';
            rows.push('<button type="button" data-go="plant:' + i + '"><span class="re"><i style="--lc:' + lc(i) + '"></i></span><span class="rt"><small>' + esc(lotWord(i)) + ' · ' + esc(trimN(num(l.areaHa))) + ' ha</small><b>' + esc((c ? c.icon + ' ' + cropShort(c.key) : 'No crop') + (p.variety ? ' · ' + p.variety : '')) + '</b><em>'
                + esc([when, (OPT.methods[p.method] || {}).label, (OPT.priorities[g.priority] || {}).label, g.target ? 'target ' + g.target + ' ' + unitWord(g.yieldUnit) : ''].filter(Boolean).join(' · ')) + '</em></span><span class="rc">Change</span></button>');
            const n = window.aneeFieldProfile ? window.aneeFieldProfile.count(l.profile) : 0;
            rows.push('<button type="button" data-go="lots"><span class="re">🟤</span><span class="rt"><small>Its field</small><b>' + esc(n ? n + ' answers from the lot' : 'No answers yet') + '</b><em>' + esc(l.place || S.place || 'No place') + '. Its soil test and answers decide the fertilizer.</em></span><span class="rc">Change</span></button>');
        });
        rows.push('<button type="button" data-go="buy"><span class="re">🛒</span><span class="rt"><small>Fertilizer you can buy</small><b>' + esc(S.granulars.length ? S.granulars.map((k) => (OPT.granulars || {})[k] || k).join(', ') : 'Not said: the common ones sold here') + '</b>' + (S.notes ? '<em>' + esc(S.notes) + '</em>' : '') + '</span><span class="rc">Change</span></button>');
        const days = (() => { const all = new Set(S.workers.map((w) => w.days.join(','))); return all.size === 1 ? daysWord(S.workers[0].days) : 'their own days'; })();
        rows.push('<button type="button" data-go="workers"><span class="re">👷</span><span class="rt"><small>The crew</small><b>' + esc(S.workers.length + (S.workers.length === 1 ? ' worker, ' : ' workers, ') + days + ' · ' + S.sprayers + (S.sprayers === 1 ? ' sprayer' : ' sprayers')) + '</b><em>'
            + esc((S.plantBy === 'hired' ? 'A hired crew plants and harvests. ' : 'Your workers plant and harvest. ') + (S.mode === 'auto' ? 'Anee moves jobs inside their safe days when they collide.' : 'Your dates stay, and Anee names the short days.')) + '</em></span><span class="rc">Change</span></button>');
        if (FLOW.includes('place')) rows.push('<button type="button" data-go="place"><span class="re">📍</span><span class="rt"><small>Place</small><b>' + esc(S.place || 'Not said') + '</b></span><span class="rc">Change</span></button>');
        $('cqReview').innerHTML = rows.join('');
        $('cqErr').hidden = true;
        $('cqRunFace').innerHTML = OPT.aneeFace ? '<img src="' + esc(OPT.aneeFace) + '" alt="">' : '';
        paintRun();
        await paintCast('cqCastRev', true);
        paintRun();
    }
    function paintRun() {
        const q = quoteNow(), pr = OPT.prices || {};
        const extraLots = Math.max(0, S.lots.length - 1), extraCases = Math.max(0, caseCount() - 1);
        const box = $('cqPrice');
        if (OPT.canUse && q) {
            box.innerHTML = 'This protocol: <b>' + esc(q) + ' credits</b>' + (window.aneeTl ? window.aneeTl.flag('credits') : '') + '. ' + esc(pr.base + ' for the first lot'
                + (extraLots ? ', ' + pr.lot + ' each for ' + extraLots + (extraLots === 1 ? ' more lot' : ' more lots') : '')
                + (extraCases ? ', ' + pr.case + ' each for ' + extraCases + (extraCases === 1 ? ' more crop or variety' : ' more crops or varieties') + ' Anee reads up on' : '') + '.')
                + ' You have ' + (window.creditCoin ? window.creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString()) : esc(OPT.balance)) + '.';
            box.hidden = false;
        } else {
            box.hidden = true;
        }
        $('cpRunSays').textContent = OPT.canUse && q ? 'Write my protocol (' + q + ' credits)' : 'Write my protocol';
        $('cpRunFine').textContent = OPT.canUse ? 'Anee reads up on each crop, writes the farm part, then each lot. About ' + Math.max(3, 2 + S.lots.length + caseCount()) + ' minutes or more. You can leave this open.' : (OPT.whyNot || '');
        $('cpRun').disabled = !OPT.canUse;
    }
    $('cqReview').addEventListener('click', (e) => { const b = e.target.closest('[data-go]'); if (b) goTo(b.dataset.go, true); });

    /* ---------------- the run ---------------- */
    // The wait's bar, by the asks this run makes: each crop's research, the farm, each lot.
    function phasesFor(nLots, nCases) {
        const ph = { start: { label: 'Getting started', lo: 1, hi: 4, tau: 10 } };
        const r0 = 4, r1 = 28, f1 = 42, l1 = 96;
        for (let c = 0; c < nCases; c++) ph[c ? 'research' + (c + 1) : 'research'] = { label: 'Reading up on ' + (nCases > 1 ? 'crop ' + (c + 1) + ' of ' + nCases : 'your crop'), lo: r0 + (r1 - r0) * c / nCases, hi: r0 + (r1 - r0) * (c + 1) / nCases, tau: 80 };
        ph.farm = { label: 'Writing the farm part: stages and protection', lo: r1, hi: f1, tau: 90 };
        for (let i = 0; i < nLots; i++) ph['lot' + (i + 1)] = { label: 'Planning ' + (S.lots[i] ? S.lots[i].name : 'lot ' + (i + 1)) + (nLots > 1 ? ' (' + (i + 1) + ' of ' + nLots + ')' : ''), lo: f1 + (l1 - f1) * i / nLots, hi: f1 + (l1 - f1) * (i + 1) / nLots, tau: 80 };
        ph.compose = { label: 'Laying out the work calendar', lo: 96, hi: 99, tau: 8 };
        return ph;
    }
    let VIEW_MODE = null;
    $('cpRun').addEventListener('click', async () => {
        if (!OPT || !OPT.canUse) return;
        const nLots = S.lots.length, nCases = caseCount();
        const wiz = $('cqWiz');
        wiz.querySelectorAll('.cq-step, .cq-nav, .cq-head, .cq-bar').forEach((el) => { el.style.display = 'none'; });
        window.aneeWait.show({ title: 'Anee is writing your protocol…', lines: ['Reading up on each crop and variety…', 'Reading official fertilizer and pest advice for your region…', 'Checking the weather outlook and El Niño or La Niña…', 'Holding each lot to its need, like NPK Plus…', 'Laying every lot\'s jobs over your crew…'], sub: 'This takes several minutes for ' + nLots + (nLots === 1 ? ' lot' : ' lots') + '. It is our deepest analysis.' });
        $('cqErr').hidden = true;
        let landed = false;
        try {
            const b = body();
            const q = quoteNow();
            if (q) b.quote = q;
            const res = await window.api(U.generate, { method: 'POST', body: b });
            let data = res.data;
            if (data.pending) data = await window.aneeWait.poll({ id: data.id, job: U.job, phases: phasesFor(nLots, nCases), limit: 900 });
            OPT.balance = data.balance;
            landed = true;
            const item = { report: data.report, params: data.params, charged: data.charged, savedId: data.savedId };
            openView(item, 'fresh');
            await window.aneeWait.done({ title: 'Done!', line: data.charged + ' credits used. Find it under Saved.' });
            window.toast?.('Done. ' + data.charged + ' credits used. Find it under Saved.');
        } catch (err) {
            if (err && err.status === 409 && err.data && err.data.quote) { QUOTE = Number(err.data.quote); }
            $('cqErrText').textContent = (err && err.message) || 'Please try again.';
            $('cqErr').hidden = false;
            window.toast?.((err && err.message) || 'The protocol did not finish.', 'error');
        } finally {
            if (!landed) window.aneeWait.fail();
            wiz.querySelectorAll('.cq-step, .cq-nav, .cq-head, .cq-bar').forEach((el) => { el.style.display = ''; });
            show(pos);
            if (landed && VIEW_MODE === 'fresh' && !$('cpView').hidden) { wiz.hidden = true; $('cqQuote').hidden = true; }
            else paintRun();
        }
    });

    /* A finished run leaves a clean form (2026-10-17, the owner): back at the lots, nothing picked. */
    function resetWizard() {
        S.lots = [];
        S.workers = [newWorker(), newWorker()];
        Object.assign(S, { sprayers: 1, plantBy: 'own', mode: 'auto', saveNew: true, granulars: [], notes: '', place: '' });
        QUOTE = null;
        castSig = '';
        $('cqCast').innerHTML = ''; $('cqCastRev').innerHTML = ''; $('cqNeed').hidden = true;
        if (FQ) FQ.setLots(fqLots());
        FLOW = ['lots']; pos = 0;
        show(0);
    }
    function wizardBack() {
        $('cqWiz').hidden = false;
        if (OPT) $('cqQuote').hidden = false;
        $('cpReport').hidden = true;
        resetWizard();
        if (window.aneeStepTop) window.aneeStepTop($('cqWiz'));
    }

    /* ---------------- the sheets ---------------- */
    const sheetLock = () => document.documentElement.classList.toggle('cq-lock', !!document.querySelector('.cq-sheet[open]'));
    function sheetOpen(dlg, focus) {
        if (!dlg || dlg.open || !dlg.showModal) return;
        dlg.showModal();
        sheetLock();
        if (window.registerOverlay) window.registerOverlay('cq:' + dlg.id, () => sheetClose(dlg));
        if (focus && matchMedia('(pointer: fine)').matches) focus.focus(); else dlg.querySelector('.cq-sbox').focus({ preventScroll: true });
    }
    function sheetClose(dlg) {
        if (!dlg.open || dlg.classList.contains('is-closing')) return;
        if (window.unregisterOverlay) window.unregisterOverlay('cq:' + dlg.id);
        const done = () => { dlg.classList.remove('is-closing'); dlg.close(); sheetLock(); };
        if (reduce()) { done(); return; }
        dlg.classList.add('is-closing');
        setTimeout(done, 230);
    }
    document.querySelectorAll('.cq-sheet').forEach((dlg) => {
        dlg.addEventListener('click', (e) => { if (e.target === dlg || e.target.closest('[data-close]')) sheetClose(dlg); });
        dlg.addEventListener('cancel', (e) => { e.preventDefault(); sheetClose(dlg); });
    });


    /* ---------------- the report, full screen ---------------- */
    const bagAmt = (bags) => { const b = Number(bags) || 0, kg = b * 50; return b >= 1 ? trimN(b) + (b === 1 ? ' bag' : ' bags') : (kg >= 20 ? Math.round(kg) : Math.round(kg * 10) / 10) + ' kg'; };
    const elAmt = (v) => { const x = Number(v) || 0; return x >= 1 ? (Math.round(x * 10) / 10) + ' kg' : Math.round(x * 1000) + ' g'; };
    const kgAmt = (kg) => { const k = Number(kg) || 0; return k >= 50 ? trimN(k / 50) + (Math.abs(k / 50 - 1) < 0.05 ? ' bag' : ' bags') + ' (' + Math.round(k).toLocaleString() + ' kg)' : elAmt(k); };
    const sweepT = (t) => { const s = String(t || '').replace(/:[a-z0-9_-]+:/gi, '').replace(/\s{2,}/g, ' ').trim(); return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; };
    const dnum = (s) => { const [y, m, d] = String(s).slice(0, 10).split('-').map(Number); return Date.UTC(y, m - 1, d) / 864e5; };

    function openView(item, mode) {
        const view = $('cpView');
        VIEW_MODE = mode;
        const r = item.report || {};
        const crop = (OPT ? OPT.crops.find((c) => c.key === (item.params || {}).crop) : null) || {};
        $('cpViewTitle').textContent = Number(r.format) === 4 ? ((r.lots || []).length > 1 ? 'Farm protocol, ' + r.lots.length + ' lots' : ((r.lots || [])[0] ? r.lots[0].cropLabel + ' protocol' : 'Crop protocol')) : (crop.label ? crop.label + ' crop protocol' : 'Crop protocol analysis');
        const host = $('cpViewReport');
        host.classList.remove('is-drawn');
        drawReport(host, item, mode, true);
        view.hidden = false;
        document.documentElement.classList.add('va-view-lock');
        view.scrollTop = 0;
        if (window.registerOverlay) window.registerOverlay('cq:view', () => closeView());
        requestAnimationFrame(() => requestAnimationFrame(() => { view.classList.add('is-on'); host.classList.add('is-drawn'); }));
    }
    function closeView() {
        const view = $('cpView');
        if (view.hidden) return;
        if (window.unregisterOverlay) window.unregisterOverlay('cq:view');
        view.classList.remove('is-on');
        document.documentElement.classList.remove('va-view-lock');
        // Closing a fresh protocol brings the walk back, empty (2026-10-17, the owner); it is on the Saved tab.
        const wasFresh = VIEW_MODE === 'fresh';
        VIEW_MODE = null;
        setTimeout(() => { view.hidden = true; $('cpViewReport').innerHTML = ''; if (wasFresh) wizardBack(); }, reduce() ? 0 : 300);
    }
    $('cpViewX').addEventListener('click', closeView);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !document.querySelector('.cq-sheet[open], .fq-sheet[open]')) closeView(); });
    async function openSaved(id) {
        try {
            const res = await window.api(U.one(id), { method: 'GET' });
            openView({ report: res.data.report, params: res.data.params, charged: res.data.credits, savedId: res.data.id }, 'saved');
        } catch (err) { window.toast?.(err.message, 'error'); }
    }
    // Any report, drawn as a saved one (for checks without a run).
    window.cpOpenView = (item) => openView(item, 'saved');

    /* ---- THE FARM PROTOCOL (format 4): the whole farm, then a tab a lot ---- */
    function drawV4(hostEl, item, mode) {
        const r = item.report || {};
        const lots = r.lots || [];
        const tabs = '<div class="cr-tabs" role="tablist" aria-label="The farm and its lots">'
            + (lots.length > 1 ? '<button type="button" class="cr-tab is-on" role="tab" aria-selected="true" data-crtab="farm" style="--lc:#2d5016"><i></i>Whole farm</button>' : '')
            + lots.map((L, i) => '<button type="button" class="cr-tab' + (lots.length === 1 ? ' is-on' : '') + '" role="tab" aria-selected="' + (lots.length === 1) + '" data-crtab="lot' + i + '" style="--lc:' + lc(i) + '"><i></i>' + esc(lots.length > 1 ? L.name : 'This lot') + '</button>').join('')
            + (lots.length === 1 ? '<button type="button" class="cr-tab" role="tab" aria-selected="false" data-crtab="farm" style="--lc:#2d5016"><i></i>Calendar and more</button>' : '')
            + '</div>';
        hostEl.innerHTML = tabs
            + '<div class="cr-pane" data-pane="farm"' + (lots.length === 1 ? ' hidden' : '') + '>' + farmPane(r, item) + '</div>'
            + lots.map((L, i) => '<div class="cr-pane" data-pane="lot' + i + '"' + (lots.length === 1 ? '' : ' hidden') + '></div>').join('')
            + actionsHtml(mode);
        const painted = new Set();
        const paintLot = (i) => {
            if (painted.has(i)) return;
            painted.add(i);
            const pane = hostEl.querySelector('[data-pane="lot' + i + '"]');
            pane.innerHTML = lotPane(lots[i], r, i);
            paintProgram(pane.querySelector('[data-proghost]'), lots[i], hostEl);
        };
        if (lots.length === 1) paintLot(0);
        const go = (key) => {
            hostEl.querySelectorAll('[data-crtab]').forEach((t) => { const on = t.dataset.crtab === key; t.classList.toggle('is-on', on); t.setAttribute('aria-selected', String(on)); });
            if (key.startsWith('lot')) {
                const i = Number(key.slice(3));
                const fresh = !painted.has(i);
                if (fresh && hostEl.classList.contains('is-drawn')) hostEl.classList.remove('is-drawn');
                paintLot(i);
                if (!hostEl.classList.contains('is-drawn')) requestAnimationFrame(() => requestAnimationFrame(() => hostEl.classList.add('is-drawn')));
            }
            hostEl.querySelectorAll('[data-pane]').forEach((p) => { p.hidden = p.dataset.pane !== key; });
            const view = $('cpView'), tabsEl = hostEl.querySelector('.cr-tabs');
            if (view && tabsEl && view.scrollTop > tabsEl.offsetTop) view.scrollTo({ top: Math.max(0, tabsEl.offsetTop - 8), behavior: reduce() ? 'auto' : 'smooth' });
        };
        hostEl.querySelector('.cr-tabs').addEventListener('click', (e) => { const t = e.target.closest('[data-crtab]'); if (t) go(t.dataset.crtab); });
        hostEl.addEventListener('click', (e) => { const j = e.target.closest('[data-golot]'); if (j) go('lot' + j.dataset.golot); });
    }

    const lotName = (r, i) => ((r.lots || [])[i] || {}).name || ('Lot ' + (i + 1));
    function farmPane(r, item) {
        const lots = r.lots || [], bg = r.background || {}, wx = bg.weather || {}, s = r.schedule || {}, farm = r.farm || {};
        const crops = [...new Set(lots.map((L) => L.cropLabel.split(',')[0]))];
        const months = [...new Set(lots.map((L) => L.month))].sort();
        return '<div class="cr-hero" style="--lc:#4a7c2a"><small>' + esc(lots.length > 1 ? 'Your farm protocol' : 'Your crop protocol') + '</small><h2>' + esc(sweepT(r.headline) || 'Your season, lot by lot') + '</h2>'
            + '<div class="cr-chips"><span>' + lots.length + (lots.length === 1 ? ' lot' : ' lots') + ', ' + esc(trimN(farm.areaHa)) + ' ha</span><span>' + esc(crops.join(', ')) + '</span><span>🗓️ ' + esc(months.map(monthWord).join(' to ')) + '</span>'
            + '<span>📍 ' + esc(farm.location || '') + '</span><span>Confidence: ' + esc(r.confidence || 'moderate') + '</span>' + (item.charged ? '<span>' + esc(item.charged) + ' credits</span>' : '') + '</div></div>'
            + guideHtml()
            + '<div class="cr-card cp2-bg"><div class="cp2-bg-row"><span class="cp2-bg-e">📍</span><div><b>The place</b><p>' + esc(sweepT(bg.place)) + '</p></div></div>'
            + '<div class="cp2-bg-row"><span class="cp2-bg-e">🌤️</span><div><b>The weather ahead</b><p>' + esc(sweepT(wx.outlook)) + '</p>' + (wx.enso ? '<p class="cp2-dim">' + esc(sweepT(wx.enso)) + '</p>' : '')
            + (list(wx.risks).length ? '<div class="cp2-pills">' + list(wx.risks).map((x) => '<span class="cp2-pill is-risk">⚠️ ' + esc(sweepT(x)) + '</span>').join('') + '</div>' : '') + '</div></div>'
            + (bg.history ? '<div class="cp2-bg-row"><span class="cp2-bg-e">📜</span><div><b>Twenty years of these months</b><p>' + esc(sweepT(bg.history)) + '</p></div></div>' : '') + '</div>'
            + calendarCard(r)
            + (lots.length > 1 ? '<div class="cr-card"><h3>Your lots<small>Tap a lot for its fertilizer, protection and jobs</small></h3><div class="cr-lotcards">' + lots.map((L, i) => lotCard(L, i)).join('') + '</div></div>' : '')
            + buyCard(r)
            + (r.crops || []).map((c) => cropCard(c, r)).join('')
            + (list(r.workTips).length ? '<div class="cr-card"><h3>Running the work<small>across your lots</small></h3><div class="cr-lines">' + list(r.workTips).map((t) => '<p>' + esc(sweepT(t)) + '</p>').join('') + '</div></div>' : '')
            + '<div class="cr-card"><h3>In plain words</h3><p class="cr-say">' + esc(sweepT(r.summary)) + '</p>'
            + (list(r.dataGaps).length ? '<h3 class="mt-4">What Anee could not check</h3><ul class="wtp-gap">' + list(r.dataGaps).map((g) => '<li>' + esc(g) + '</li>').join('') + '</ul>' : '') + '</div>'
            + sourcesHtml(r);
    }

    /* The work calendar: the state, each lot's day, the season strip, the busy days. */
    function calendarCard(r) {
        const s = r.schedule || {}, lots = r.lots || [], crew = (r.farm || {}).crew || {};
        const tasks = s.tasks || [];
        const title = {};
        tasks.forEach((t) => { title[t.lot + '|' + t.key] = t.title; });
        const late = s.late || [], short = s.short || [];
        const ok = !late.length && !short.length;
        const workers = (crew.workers || []).length;
        const state = ok
            ? '<div class="cr-state"><span>✅</span><span><b>No day asks more hands than you have</b>' + esc((s.mode === 'auto' ? 'Anee placed every job inside the days the crop takes it well. ' : 'Your dates are kept, and they fit. ') + 'The busiest day is ' + (s.peak ? dayFmt(s.peak.date) + ', with ' + s.peak.workers + ' of your ' + s.peak.cap + ' workers.' : 'light.')) + '</span></div>'
            : '<div class="cr-state is-warn"><span>⚠️</span><span><b>' + esc(s.mode === 'keep' ? (short.length === 1 ? 'One day asks more workers than you have' : short.length + ' days ask more workers than you have') : (late.length === 1 ? 'One job runs past its safe days' : late.length + ' jobs run past their safe days')) + '</b>'
                + esc(s.more ? 'With ' + (workers + s.more) + ' workers working Monday to Saturday (' + s.more + ' more), everything fits.' : 'Hire help for the busy days, or move a planting day.')
                + (s.moreSprayers ? ' Or ' + s.moreSprayers + ' more sprayer' + (s.moreSprayers === 1 ? '' : 's') + '.' : '') + (crew.plantBy !== 'hired' ? ' A hired crew for planting and harvest also takes most of it off your workers.' : '') + '</span></div>';
        const lotRows = (s.lots ? Object.values(s.lots) : []).map((l) => {
            const i = l.i, moved = l.shift > 0;
            const why = l.why && l.why.by && l.why.by.length ? ' Your crew is busy then with ' + andList(l.why.by.map((b) => lotName(r, b.lot) + ' ' + String(title[b.lot + '|' + b.key] || '').toLowerCase())) + '.' : '';
            return '<div class="cq-cast-row"><b><i style="--lc:' + lc(i) + '"></i>' + esc(lotName(r, i)) + '</b><span class="' + (late.some((t) => t.lot === i) ? 'is-late' : (moved ? 'is-moved' : 'is-ok')) + '">' + esc('Planted ' + dayFmt(l.planting) + (l.asked ? (moved ? ', ' + l.shift + ' days after your day' : ', your day') : (moved ? ', the first day that fits' : ', the start of the month')))
                + (why ? '<small>' + esc(why.trim()) + '</small>' : '') + '</span></div>';
        }).join('');
        // The season strip.
        const t0 = dnum(s.start || (tasks[0] || {}).from), t1 = dnum(s.end || (tasks[0] || {}).to), span = Math.max(1, t1 - t0);
        const pct = (d) => Math.max(0, Math.min(100, (dnum(d) - t0) / span * 100));
        const axis = [];
        const d0 = new Date(t0 * 864e5);
        for (let m = new Date(Date.UTC(d0.getUTCFullYear(), d0.getUTCMonth() + 1, 1)); m.getTime() / 864e5 <= t1; m = new Date(Date.UTC(m.getUTCFullYear(), m.getUTCMonth() + 1, 1))) {
            axis.push('<span style="left:' + ((m.getTime() / 864e5 - t0) / span * 100).toFixed(2) + '%">' + m.toLocaleDateString('en-US', { timeZone: 'UTC', month: 'short' }) + '</span>');
        }
        const overDays = (s.days || []).filter((d) => d.over > 0 || d.sprayOver > 0);
        const ticks = overDays.map((d) => '<i style="left:' + pct(d.date).toFixed(2) + '%" title="' + esc(dayFmt(d.date) + ': short by ' + d.over) + '"></i>').join('');
        const strip = '<div class="cr-strip"><div class="cr-axis">' + axis.join('') + ticks + '</div>'
            + lots.map((L, i) => {
                const mine = tasks.filter((t) => t.lot === i);
                if (!mine.length) return '';
                const a = mine.reduce((m, t) => (t.from < m ? t.from : m), mine[0].from), b = mine.reduce((m, t) => (t.to > m ? t.to : m), mine[0].to);
                return '<div class="cr-srow" style="--lc:' + lc(i) + '"><b><i></i>' + esc(L.name) + '</b><div class="cr-track"><span class="cr-span" style="left:' + pct(a).toFixed(2) + '%;width:' + Math.max(1, pct(b) - pct(a)).toFixed(2) + '%"></span>'
                    + mine.map((t) => '<span class="cr-mark' + (t.kind === 'plant' ? ' is-plant' : (t.kind === 'harvest' ? ' is-harvest' : '')) + (t.late ? ' is-late' : '') + '" style="left:' + pct(t.from).toFixed(2) + '%" title="' + esc(t.title + ', ' + dayFmt(t.from)) + '"></span>').join('') + '</div></div>';
            }).join('')
            + '</div><div class="cr-legend"><span><i class="is-plant"></i>Planting</span><span><i></i>Fertilizer, sprays, weeding</span><span><i class="is-harvest"></i>Harvest</span>' + (overDays.length ? '<span><i class="is-short"></i>Short of hands</span>' : '') + '</div>';
        // The busy days, the short ones first in red.
        const days = (s.days || []).filter((d) => d.need > 0 || d.hired > 0);
        const dayRow = (d) => {
            const over = d.over > 0 || d.sprayOver > 0;
            const items = d.items.map((x) => '<div class="cr-item" style="--lc:' + lc(x.lot) + '"><i></i><span>' + esc((lots.length > 1 ? lotName(r, x.lot) + ': ' : '') + (title[x.lot + '|' + x.key] || x.key)) + ' <em>' + esc(x.hired ? 'hired crew, about ' + Math.ceil(x.pd) + ' people' : (x.pd >= 1 ? trimN(x.pd) + ' person days' : 'part of a day')) + '</em></span></div>').join('');
            const share = d.cap > 0 ? Math.min(100, d.need / d.cap * 100) : (d.need > 0 ? 100 : 0);
            return '<div class="cr-day' + (over ? ' is-over' : '') + '"><span class="cr-cal"><i>' + esc(dayFmt(d.date, { month: 'short' })) + '</i><b>' + esc(dayFmt(d.date, { day: 'numeric' })) + '</b><small>' + esc(dayFmt(d.date, { weekday: 'short' })) + '</small></span><div class="cr-items">' + items
                + (d.need > 0 ? '<div class="cr-meter"><span><i style="width:' + share.toFixed(0) + '%"></i></span>' + esc(over ? (d.over ? d.workers + ' needed, ' + d.cap + ' on hand: short by ' + d.over : 'Short of sprayers by ' + d.sprayOver) : d.workers + ' of ' + d.cap + ' workers') + '</div>' : '') + '</div></div>';
        };
        const first = days.slice(0, 10), rest = days.slice(10);
        return '<div class="cr-card"><h3>One work calendar for every lot' + (window.aneeTl ? window.aneeTl.flag('crew') : '') + '<small>' + esc(workers + (workers === 1 ? ' worker' : ' workers') + ', ' + (crew.sprayers || 1) + ((crew.sprayers || 1) === 1 ? ' sprayer' : ' sprayers') + (crew.plantBy === 'hired' ? ', a hired crew planting and harvesting' : '') + (s.mode === 'auto' ? '. Anee moves jobs inside their safe days.' : '. Your dates are kept.')) + '</small></h3>'
            + state + '<div class="cq-cast-rows" style="margin-top:.7rem">' + lotRows + '</div>' + strip
            + (days.length ? '<h3 style="margin:1rem 0 .1rem;font-size:.86rem">The work days<small>' + days.length + ' days with field work. A person day is one worker for one day.</small></h3><div class="cr-days">' + first.map(dayRow).join('') + '</div>'
                + (rest.length ? '<details class="cr-fold"><summary>Show the other ' + rest.length + ' days</summary><div class="cr-days">' + rest.map(dayRow).join('') + '</div></details>' : '') : '')
            + '<p class="cp-note">The days count your workers by the days each one works. Spraying for a pest or a disease is not on the calendar: do it only when the field shows the need, on a free day.</p></div>';
    }
    function lotCard(L, i) {
        const chk = ((L.rec || {}).npk || {}).check || [];
        const shortEl = (L.micro || []).filter((m) => m.state === 'short').length;
        return '<button type="button" class="cr-lotcard" data-golot="' + i + '" style="--lc:' + lc(i) + '"><span>' + esc(L.icon) + '</span><span><b>' + esc(L.name) + '</b><small>' + esc(L.cropLabel + (L.variety ? ' · ' + L.variety : '') + ' · ' + trimN(L.areaHa) + ' ha') + '</small>'
            + '<small>' + esc('Planted ' + (L.planting ? dayFmt(L.planting.planting) : monthWord(L.month)) + (L.harvest ? ', harvest about ' + dayFmt(L.harvest.usual, { month: 'short', day: 'numeric' }) : '')) + '</small>'
            + '<span class="cr-ok">' + chk.map((c) => '<em class="' + (c.verdict === 'short' ? 'is-warn' : (c.verdict === 'over' ? 'is-warn' : '')) + '">' + esc(c.label + (c.verdict === 'ok' ? ' on target' : (c.verdict === 'short' ? ' short' : (c.verdict === 'over' ? ' over' : '')))) + '</em>').join('')
            + (shortEl ? '<em class="is-warn">' + shortEl + (shortEl === 1 ? ' micro to add' : ' micros to add') + '</em>' : '') + ((L.tasks || []).some((t) => t.late) ? '<em class="is-bad">Short of hands</em>' : '') + '</span></span></button>';
    }
    function buyCard(r) {
        const b = r.buy || [];
        if (!b.length) return '';
        return '<div class="cr-card"><h3>What to buy' + ((r.lots || []).length > 1 ? ' for the whole farm' : '') + '<small>The recommended program of every lot, what meets its need, and the micronutrients. Amounts only, no prices.</small></h3><div class="cr-buy">'
            + b.map((x) => '<div class="cr-buyrow"><b>' + esc(x.product) + '</b><span>' + esc(kgAmt(x.kg)) + '</span>' + ((r.lots || []).length > 1 ? '<small>' + esc(x.lots.map((i) => lotName(r, i)).join(', ')) + '</small>' : '') + '</div>').join('') + '</div></div>';
    }
    function cropCard(c, r) {
        const v = c.varietyInfo || {}, rec = c.record || null;
        const lots = (c.lots || []).map((i) => lotName(r, i));
        return '<div class="cr-card"><h3>' + esc(c.icon + ' ' + c.cropLabel + (c.variety ? ' · ' + c.variety : '')) + '<small>' + esc(c.methodLabel + ((r.lots || []).length > 1 ? ' · ' + lots.join(', ') : '')) + '</small></h3>'
            + '<div class="cp2-bg"><div class="cp2-bg-row"><span class="cp2-bg-e">🧬</span><div><b>' + esc(v.name || c.variety || 'The variety') + (v.by ? ' <small>by ' + esc(v.by) + (v.released ? ', ' + esc(v.released) : '') + '</small>' : '') + '</b>'
            + (v.found === false ? '<p class="cp2-dim">Not found online. The numbers are Anee\'s best guess for it.</p>' : '')
            + '<div class="cp2-pills">' + (Number(v.maturityDays) > 0 ? '<span class="cp2-pill">⏱ ' + esc(Math.round(Number(v.maturityDays))) + ' days to maturity</span>' : '') + (v.yieldPotential ? '<span class="cp2-pill">🌾 ' + esc(v.yieldPotential) + '</span>' : '') + (v.season ? '<span class="cp2-pill">🗓️ ' + esc(v.season) + '</span>' : '') + '</div>'
            + (v.traits ? '<p>' + esc(sweepT(v.traits)) + '</p>' : '') + (v.caution ? '<p class="wp-watch">⚠️ ' + esc(sweepT(v.caution)) + '</p>' : '') + '</div></div></div>'
            + '<details class="cr-fold"><summary>Stage by stage: what to look for</summary><div class="cp-stages" style="margin-top:.6rem">' + (c.stages || []).filter((s) => !s.prep).map((s, i) => '<div class="cp-stage" style="opacity:1;transform:none"><span class="cp-stage-n">' + (i + 1) + '</span><span class="cp-stage-b"><b>' + esc(s.name) + ' <em class="cp2-days">' + esc(s.days) + '</em></b>'
                + (s.signs || s.what ? '<div class="signs">' + esc(sweepT(s.signs || s.what)) + '</div>' : '') + (s.observe ? '<div class="signs"><b style="display:inline">Look for:</b> ' + esc(sweepT(s.observe)) + '</div>' : '') + (s.intervene ? '<div class="signs"><b style="display:inline">If you see it:</b> ' + esc(sweepT(s.intervene)) + '</div>' : '') + '</span></div>').join('') + '</div></details>'
            + protHtml(c, r) + '</div>';
    }
    /* Protection, watch first: the threats, their thresholds, the ingredients by group with products from our book, the farmer's own call. */
    function protHtml(c, r, lotWatch) {
        const pr = c.protection || {}, th = pr.threats || [];
        const kindWord = { insect: 'Insect', disease: 'Disease', weed: 'Weeds', other: 'Other' };
        // The Taglish help sits on the first card only: once is enough on a page this long.
        const card = (t, k) => '<div class="cr-threat is-' + esc(t.kind) + '"><div class="cr-th-h"><b>' + esc(t.name) + '</b><span class="cr-kind">' + esc(kindWord[t.kind] || t.kind) + '</span>' + (t.had && t.had.length ? '<span class="cr-had">' + esc(t.had.join(', ') + ' had it last season') + '</span>' : '') + '</div>'
            + (t.stages && t.stages.length ? '<div class="cr-stg">' + t.stages.map((s) => '<span>' + esc(s.name) + '</span>').join('') + '</div>' : '')
            + (t.sign ? '<p><em>Look for:</em> ' + esc(sweepT(t.sign)) + '</p>' : '')
            + (t.threshold ? '<p><em' + (k ? '' : ' data-tl="economic-threshold"') + '>Act when:</em> ' + esc(sweepT(t.threshold)) + '</p>' : '')
            + (t.cultural ? '<p><em>First, without a spray:</em> ' + esc(sweepT(t.cultural)) + '</p>' : '')
            + (t.ai && t.ai.length ? '<div class="cr-ais">' + t.ai.map((a, j) => '<div class="cr-ai"><b' + (k || j ? '' : ' data-tl="active-ingredient"') + '>' + esc(a.name) + '</b>' + (a.group ? '<span class="g">' + esc(a.group) + '</span>' : '') + (a.products && a.products.length ? '<small>Sold as ' + esc(a.products.map((p) => p.brand + (p.company ? ' (' + p.company + ')' : '') + (p.with ? ' with ' + p.with : '')).join(', ')) + '</small>' : '') + '</div>').join('') + '</div>'
                : (t.byLabel && t.byLabel.length ? '<div class="cr-ais">' + t.byLabel.map((p) => '<div class="cr-ai"><b>' + esc(p.ai || p.brand) + '</b>' + (p.group ? '<span class="g">' + esc(p.group) + '</span>' : '') + '<small>Sold as ' + esc(p.brand + (p.company ? ' (' + p.company + ')' : '')) + '</small></div>').join('') + '</div>' : ''))
            + (t.rotate ? '<p><em' + (k ? '' : ' data-tl="group-rotation"') + '>A second spray:</em> ' + esc(sweepT(t.rotate)) + '</p>' : '')
            + (t.sameGroup ? '<p class="cr-note">Both are ' + esc(t.sameGroup) + ': use one, and switch to another group the next time.</p>' : '')
            + (t.noSpray ? '<p class="cr-note">' + esc(sweepT(t.noSpray)) + '</p>' : '')
            + (t.weather ? '<p><em>This season:</em> ' + esc(sweepT(t.weather)) + '</p>' : '') + '</div>';
        const weeds = c.weeds && (c.weeds.windows || []).length ? '<details class="cr-fold"><summary>The weed control windows</summary>' + (c.weeds.critical && c.weeds.critical.say ? '<p class="cp-note">' + esc(c.weeds.critical.say) + '</p>' : '') + '<div class="cr-threats">' + c.weeds.windows.map((w) => '<div class="cr-threat is-weed"><div class="cr-th-h"><b>' + esc(w.title) + '</b><span class="cr-kind">' + esc(w.timing === 'pre' ? 'Before the weeds' : (w.timing === 'prep' ? 'Land preparation' : 'Weeds up')) + '</span></div>'
            + (w.stage ? '<p><em>When:</em> ' + esc(w.stage) + '</p>' : '') + (w.steps || []).slice(0, 3).map((x) => '<p>' + esc(x) + '</p>').join('')
            + (w.ingredients && w.ingredients.length ? '<div class="cr-ais">' + w.ingredients.slice(0, 6).map((i) => '<div class="cr-ai"><b>' + esc(i.name) + '</b>' + (i.hrac && i.hrac.length ? '<span class="g">HRAC ' + esc(i.hrac.join(', ')) + '</span>' : '') + (i.mark ? '<span class="g is-mark">' + (i.mark === 'abroad' ? 'Not registered here' : 'Not on a label for this crop') + '</span>' : '') + '<small>' + esc((i.weeds || []).map((x) => ({ grass: 'grasses', sedge: 'sedges', broadleaf: 'broadleaves' })[x] || x).join(', ')) + (i.products && i.products.length ? ' · sold as ' + esc(i.products.map((p) => p.brand + (p.with ? ' with ' + p.with : '')).join(', ')) : '') + '</small></div>').join('') + '</div>' : '') + '</div>').join('') + '</div></details>' : '';
        return '<h3 style="margin:1rem 0 .5rem;font-size:.92rem;font-weight:800">Protection, watch first' + (window.aneeTl ? window.aneeTl.flag('ipm') : '') + '</h3>'
            + '<div class="cr-warn" role="note"><span>🧑‍🌾</span><span><b>Spraying is your call, always</b>' + esc(r.disclaimer || pr.disclaimer || '') + '</span></div>'
            + (pr.intro ? '<p class="cr-say" style="margin-top:.6rem">' + esc(sweepT(pr.intro)) + '</p>' : '')
            + (lotWatch && lotWatch.length ? '<div class="cr-lines">' + lotWatch.map((w) => '<p>' + esc(sweepT(w)) + '</p>').join('') + '</div>' : '')
            + (th.length ? '<div class="cr-threats">' + th.map(card).join('') + '</div>' : '<p class="cp-note">No threats came back for this crop.</p>') + weeds;
    }

    /* ---- one lot ---- */
    function lotPane(L, r, i) {
        const crop = (r.crops || []).find((c) => c.key === L.case) || null;
        const pl = L.planting || {}, n = L.need || {}, rec = L.rec || {};
        const unit = L.yieldWord || 'tons';
        const tIn = Number(((OPT && OPT.yieldT) || {})[L.yieldUnit]) || 1;
        const moved = pl.shift > 0;
        const g = (L.granulars || {});
        const yo = L.yield || {}, tN = numOf(yo.target), rN = numOf(yo.realistic), yMax = Math.max(tN || 0, rN || 0, 1);
        const tasks = (L.tasks || []).slice().sort((a, b) => (a.from < b.from ? -1 : (a.from > b.from ? 1 : 0)));
        const lvl = { watch: 'may run short', likely: 'likely short', test: 'low by your soil test' };
        const mics = Object.entries(n.micros || {}).filter(([, m]) => !m.beneficial);
        const fix = (L.fixes || []), mic = (L.micro || []);
        return '<div class="cr-hero" style="--lc:' + lc(i) + '"><small>' + esc(((r.lots || []).length > 1 ? 'Lot ' + (i + 1) + ' · ' : '') + L.name + ' · ' + trimN(L.areaHa) + ' ha') + '</small><h2>' + esc(L.icon + ' ' + L.cropLabel + (L.variety ? ' · ' + L.variety : '')) + '</h2>'
            + (L.intro ? '<p>' + esc(sweepT(L.intro)) + '</p>' : '')
            + '<div class="cr-chips"><span>' + esc(L.methodLabel) + '</span><span>🌱 ' + esc(pl.planting ? 'Planted ' + dayFmt(pl.planting) : monthWord(L.month)) + '</span>' + (L.harvest ? '<span>🌾 Harvest about ' + esc(dayFmt(L.harvest.usual, { month: 'short', day: 'numeric' })) + '</span>' : '')
            + '<span>' + esc(L.priorityLabel) + '</span>' + (L.targetYield ? '<span>🎯 ' + esc(trimN(L.targetYield) + ' ' + unit) + '</span>' : '') + '<span>📍 ' + esc(L.place) + '</span></div></div>'
            + (moved ? '<div class="cr-state is-warn"><span>📅</span><span><b>Planted ' + esc(dayFmt(pl.planting)) + ', ' + pl.shift + ' days after ' + (pl.asked ? 'your day' : 'the start of the month') + '</b>' + esc(pl.why && pl.why.by && pl.why.by.length ? 'Your crew is busy before it on other lots. Every job of this lot follows the new day.' : 'The first day that fits your crew. Every job of this lot follows it.') + '</span></div>' : '')
            + (n.known ? '<div class="cq-need"><div class="cq-need-h">This lot\'s need a hectare, counted like NPK Plus</div><div class="cq-need-n"><span><b>' + kgFmt(n.need.N) + '</b><small>kg N</small></span><span><b>' + kgFmt(n.need.P2O5) + '</b><small>kg P₂O₅</small></span><span><b>' + kgFmt(n.need.K2O) + '</b><small>kg K₂O</small></span></div>'
                + (mics.length ? '<div class="cq-need-m">' + mics.map(([, m]) => '<span>' + esc(m.name) + ' ' + esc(elAmt(m.need)) + ' <small>' + esc(lvl[m.level] || '') + '</small></span>').join('') + '</div>' : '')
                + (n.goal ? '<p>For <b>' + esc(fmt2(n.goal.eff / tIn) + ' ' + unit) + '</b> a hectare (' + esc(n.goal.why) + (Math.abs(n.goal.eff - n.goal.t) > 0.01 ? ', less for this lot\'s field' : '') + '), on this lot\'s soil, its soil test, its history' + (n.season ? ' and the weather of these weeks' : '') + '. Every program below is checked against it.</p>' : '')
                + (n.season && n.season.words ? '<p>' + esc(n.season.words) + '</p>' : '') + '</div>' : '')
            + '<div data-proghost></div>'
            + ((fix.length || mic.length) ? '<div class="cr-card"><h3>To reach your target' + (window.aneeTl ? window.aneeTl.flag('nutrient-deficiency') : '') + '<small>What the recommended program still leaves short of this lot\'s need, worked out like NPK Plus</small></h3><div class="cr-fix">'
                + fix.map((f) => '<div class="cr-fixrow"><span>' + esc(f.el === 'P2O5' ? 'P' : (f.el === 'K2O' ? 'K' : f.el)) + '</span><div><b>' + esc(bagAmt(f.bags) + ' ' + f.product) + '</b> for the lot (' + esc(f.bagsPerHa >= 1 ? trimN(f.bagsPerHa) + ' bags' : Math.round(f.kgPerHa) + ' kg') + ' a hectare) at ' + esc(f.stage.toLowerCase()) + '. <small>' + esc(f.label + ' short by ' + f.gap + ' kg a hectare. ' + (f.note || '')) + '</small></div></div>').join('')
                + mic.map((m) => m.pick === 'covered' ? '<div class="cr-fixrow"><span>' + esc(m.el) + '</span><div><b>' + esc(m.name) + ': already covered</b><small>' + esc(m.note || '') + '</small></div></div>'
                    : '<div class="cr-fixrow' + (m.pick === 'spray' ? ' is-spray' : '') + '"><span>' + esc(m.el) + '</span><div>' + (m.pick === 'spray' && m.spray ? '<b>' + esc(m.spray.name) + '</b> at ' + esc(String(m.spray.stage || '').toLowerCase()) + (m.soil ? '. Or ' + esc(m.soil.say) + ' at the last harrowing.' : '.') : '<b>' + esc(m.soil.say) + '</b> with the basal.' + (m.spray ? ' Or a ' + esc(m.spray.name.toLowerCase()) + ' at ' + esc(String(m.spray.stage || '').toLowerCase()) + '.' : ''))
                    + '<small>' + esc(m.name + ': the need is ' + elAmt(m.need) + ' a hectare, the program gives ' + elAmt(m.plan) + '. ' + (m.why && m.why.length ? m.why.join(', ') + '. ' : '') + (m.note || '')) + '</small></div></div>').join('')
                + '</div><p class="cp-note">Soil doses go on with the basal, blended or spread. A spray is named by its nutrient: ask the store for that foliar, and follow its label for the dose.</p></div>' : '')
            + ((g.soilLogic || g.timingLogic) ? '<div class="cr-card cp3-gran"><h3>Why these fertilizers<small>which bag and when, chosen for this lot\'s soil</small></h3>' + (g.soilLogic ? '<div class="cp3-gran-row"><span class="e">🧪</span><div><b>The soil decides the bag</b><p>' + esc(sweepT(g.soilLogic)) + '</p></div></div>' : '')
                + (g.timingLogic ? '<div class="cp3-gran-row"><span class="e">⏱️</span><div><b>The stage decides the amount</b><p>' + esc(sweepT(g.timingLogic)) + '</p></div></div>' : '')
                + ((g.rejected || []).filter((x) => x && x.what).length ? '<div class="cp3-gran-row"><span class="e">🚫</span><div><b>Considered but not used</b><div class="cp3-no">' + (g.rejected || []).filter((x) => x && x.what).map((x) => '<div class="cp3-no-row"><b>' + esc(sweepT(x.what)) + '</b>' + (x.why ? '<p>' + esc(sweepT(x.why)) + '</p>' : '') + '</div>').join('') + '</div></div></div>' : '')
                + (list(g.cautions).length ? '<div class="cp2-pills">' + list(g.cautions).map((x) => '<span class="cp2-pill is-risk">⚠️ ' + esc(sweepT(x)) + '</span>').join('') + '</div>' : '') + '</div>' : '')
            + ((L.deficiencies || []).length ? '<div class="cr-card"><h3>Nutrients this soil may lack<small>what to watch for, and why</small></h3><div class="cp2-defs">' + L.deficiencies.map((d) => '<div class="cp2-def" style="opacity:1;transform:none"><div class="cp2-def-h"><b>' + esc(d.nutrient || '') + '</b>' + (d.when ? '<span class="tag">' + esc(d.when) + '</span>' : '') + '</div>'
                + (d.why ? '<p>' + esc(sweepT(d.why)) + '</p>' : '') + (d.signs ? '<p><em>Signs:</em> ' + esc(sweepT(d.signs)) + '</p>' : '') + (d.action ? '<p><em>What to do:</em> ' + esc(sweepT(d.action)) + '</p>' : '') + '</div>').join('') + '</div></div>' : '')
            + (crop ? '<div class="cr-card">' + protHtml(crop, r, L.watch).replace(/^<h3 style="margin:1rem 0 .5rem;/, '<h3 style="margin:0 0 .5rem;') + '</div>' : '')
            + ((L.water || {}).plan ? '<div class="cr-card"><h3>Water</h3><p class="cr-say">' + esc(sweepT(L.water.plan)) + '</p><div class="cp2-pills">' + (L.water.ifDry ? '<span class="cp2-pill">☀️ If dry: ' + esc(sweepT(L.water.ifDry)) + '</span>' : '') + (L.water.ifWet ? '<span class="cp2-pill">🌧️ If wet: ' + esc(sweepT(L.water.ifWet)) + '</span>' : '') + '</div></div>' : '')
            + ((tN || rN || yo.note) ? '<div class="cr-card"><h3>Yield<small>your target and what this variety really gives here</small></h3><div class="cp-yo">' + (tN ? '<div class="cp-yo-row"><small>Target</small><div class="tr"><span class="is-target" style="width:' + Math.round(tN / yMax * 100) + '%"></span></div><b>' + esc(yo.target || '') + '</b></div>' : '')
                + (rN ? '<div class="cp-yo-row"><small>Realistic</small><div class="tr"><span style="width:' + Math.round(rN / yMax * 100) + '%"></span></div><b>' + esc(yo.realistic || '') + '</b></div>' : '') + '</div>' + (yo.note ? '<p class="cp-note">' + esc(sweepT(yo.note)) + '</p>' : '') + '</div>' : '')
            + (tasks.length ? '<div class="cr-card"><h3>This lot\'s jobs, by date<small>The days the work calendar gives them</small></h3><div class="cr-jobs">' + tasks.map((t) => {
                const days = (t.days || []).length, people = t.hired ? 'A hired crew, about ' + Math.ceil(t.pd) + ' people' : (t.pd >= 1 ? trimN(t.pd) + ' person days' + (days > 1 ? ' over ' + days + ' days' : '') : 'Part of a day');
                return '<div class="cr-job' + (t.late ? ' is-late' : '') + '"><span class="cr-cal"><i>' + esc(dayFmt(t.from, { month: 'short' })) + '</i><b>' + esc(dayFmt(t.from, { day: 'numeric' })) + '</b><small>' + esc(dayFmt(t.from, { weekday: 'short' })) + '</small></span><div><b>' + esc(t.title) + '</b>'
                    + (t.what ? '<small>' + esc(t.what) + '</small>' : '') + '<small>' + esc(people + (t.to !== t.from ? ', to ' + dayFmt(t.to, { month: 'short', day: 'numeric' }) : '')) + '</small>'
                    + (t.late ? '<em class="is-late">' + esc('Needs about ' + t.perDay + (t.perDay === 1 ? ' worker' : ' workers') + ' a day to be done by ' + dayFmt(t.hi, { month: 'short', day: 'numeric' })) + '</em>' : (t.moved ? '<em>' + esc('Moved ' + Math.abs(t.moved) + (Math.abs(t.moved) === 1 ? ' day ' : ' days ') + (t.moved > 0 ? 'later' : 'earlier') + ', inside its safe days') + '</em>' : ''))
                    + (t.hired ? ' <em class="is-hired">Hired</em>' : '') + '</div></div>';
            }).join('') + '</div></div>' : '')
            + ((L.field || []).length ? '<details class="cr-card cr-fold" style="margin-top:0"><summary>The lot\'s answers Anee read</summary><div class="cr-lines">' + L.field.map((p) => '<p>' + esc(p[0] + ': ' + p[1]) + '</p>').join('') + '</div></details>' : '');
    }

    /* A lot's fertilizer by stage, per program: the tabs, the chart, the rows, the detail, the totals, the nutrient check (drawV2's, for one lot). */
    function paintProgram(hostBox, L, viewHost) {
        if (!hostBox) return;
        const rec = L.rec || {};
        const stages = rec.stages || [];
        const programs = (rec.programs || []).length ? rec.programs : [{ name: 'Recommended', for: '', byStage: stages.map((st) => st.fertilizer || []), stageBags: stages.map((st) => Number(st.bags) || 0), totals: rec.totals || [], totalBags: rec.totalBags || 0, check: (rec.npk || {}).check || [], elements: (rec.npk || {}).elements || [], unknownProducts: [], orphans: [] }];
        const names = [...new Set(programs.flatMap((g) => (g.byStage || []).flat().concat(g.orphans || []).map((x) => x.product || 'Fertilizer')))];
        const colour = (nm) => PALETTE[Math.max(0, names.indexOf(nm)) % PALETTE.length];
        const npk = rec.npk || {};
        const area = Number(L.areaHa) || 1;
        const norm = (t) => String(t || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
        const sameStage = (a, b) => { const x = norm(a), y = norm(b); return !!x && !!y && (x === y || x.includes(y) || y.includes(x)); };
        let PROG = 0, SEL = Math.max(0, stages.findIndex((st) => (st.fertilizer || []).length));
        const verdictWord = (check, els) => {
            const v = (check || []).map((c) => c.verdict).concat((els || []).filter((e) => !e.beneficial).map((e) => ({ right: 'ok', testok: 'ok', spray: 'ok', short: 'short', over: 'over' })[e.state] || 'unchecked'));
            if (!v.length) return '';
            if (v.includes('short')) return ' · short';
            if (v.includes('over')) return ' · over';
            return v.every((x) => x === 'ok' || x === 'unchecked') ? ' · on target' : '';
        };
        const paint = () => {
            const g = programs[PROG] || programs[0];
            const byStage = g.byStage || [], stageBags = g.stageBags || [];
            const maxBags = Math.max(0.1, ...stageBags.map((b) => Number(b) || 0));
            const totals = g.totals || [], maxTotal = Math.max(0.1, ...totals.map((t) => Number(t.bags) || 0));
            const check = g.check || [];
            const els = g.elements || [];
            const shown = els.filter((e) => e.need !== null || Number(e.plan) > 0);
            const word = { right: '✅ enough', over: '↑ too much', extra: 'extra, no need flagged', testok: '✅ enough by your soil test' };
            hostBox.innerHTML = '<div class="cr-card" data-stages data-sel="' + SEL + '">'
                + (programs.length > 1 ? '<div class="cp3-tabs" role="tablist" aria-label="Fertilizer programs">' + programs.map((pg, i) => '<button type="button" class="cp3-tab' + (i === PROG ? ' is-on' : '') + '" role="tab" aria-selected="' + (i === PROG) + '" data-prog="' + i + '"><b>' + esc(pg.name || ('Option ' + (i + 1))) + '</b><small>' + esc(trimN(pg.totalBags || 0)) + ' bags' + verdictWord(pg.check, pg.elements) + '</small></button>').join('') + '</div>'
                    + (g.for ? '<p class="cp3-for">' + (PROG === 0 ? '⭐ Recommended: ' : '') + esc(sweepT(g.for)) + '</p>' : '') : '')
                + '<h3>Fertilizer by growth stage' + (window.aneeTl ? window.aneeTl.flag('split-application') : '') + '<small>50 kg bags for this lot (' + esc(trimN(area)) + ' ha), ' + esc(trimN(g.totalBags || 0)) + ' bags in all</small></h3>'
                + '<div class="cp-chart cp2-chart">' + stages.map((st, i) => '<button type="button" class="cp-col cp2-col" data-stage="' + i + '" aria-label="' + esc(st.stage || '') + '"><span class="cp-col-val">' + (Number(stageBags[i]) > 0 ? esc(trimN(stageBags[i])) : '·') + '</span><div class="cp-bar" style="height:' + Math.max(3, Math.round(((Number(stageBags[i]) || 0) / maxBags) * 100)) + '%">'
                    + (byStage[i] || []).map((x) => '<span class="cp-seg" style="height:' + Math.max(0, ((Number(x.totalBags) || 0) / Math.max(0.1, Number(stageBags[i]) || 0)) * 100) + '%;background:' + colour(x.product || 'Fertilizer') + '"></span>').join('') + '</div></button>').join('') + '</div>'
                + '<div class="cp-lbls cp2-lbls">' + stages.map((st, i) => '<span class="cp-lbl" data-lbl="' + i + '">' + (i + 1) + '</span>').join('') + '</div>'
                + (names.length ? '<div class="cp-legend">' + names.filter((nm) => (g.byStage || []).flat().concat(g.orphans || []).some((x) => (x.product || 'Fertilizer') === nm)).map((nm) => '<span><i style="background:' + colour(nm) + '"></i>' + esc(nm) + '</span>').join('') + '</div>' : '')
                + '<div class="cp2-detail" data-detail></div>'
                + '<div class="cp2-rows">' + stages.map((st, i) => '<button type="button" class="cp2-row" data-stage="' + i + '"><span class="cp2-row-n">' + (i + 1) + '</span><span class="cp2-row-t"><b>' + esc(st.stage || '') + '</b><small>' + (st.days ? '<em class="cp2-days">' + esc(st.days) + '</em>' : '')
                    + ((byStage[i] || []).length ? (byStage[i] || []).map((x) => esc(bagAmt(x.totalBags)) + ' ' + esc(x.product || '')).join(' · ') : 'nothing to apply') + '</small></span><span class="cp2-row-b">' + (Number(stageBags[i]) > 0 ? esc(trimN(stageBags[i])) + ' bags' : '') + '</span></button>').join('')
                + ((g.orphans || []).length ? '<div class="cp3-orphans"><b>Also in this program</b>' + g.orphans.map((x) => '<div>' + esc(bagAmt(x.totalBags)) + ' ' + esc(x.product || '') + ' · ' + esc(x.stage || '') + '</div>').join('') + '</div>' : '') + '</div></div>'
                + ((totals.length || check.length) ? '<div class="cr-card"><h3>Totals and the nutrient check<small>for this lot, amounts only' + (programs.length > 1 ? ' · ' + esc(g.name || '') : '') + '</small></h3>'
                    + (totals.length ? '<div class="cp2-tot">' + totals.map((t) => '<div class="cp2-tot-row"><span class="n"><i style="background:' + colour(t.product) + '"></i>' + esc(t.product) + '</span><div class="tr"><span style="width:' + Math.max(2, Math.round((Number(t.bags) / maxTotal) * 100)) + '%;background:' + colour(t.product) + '"></span></div><b>' + esc(bagAmt(t.bags)) + '</b><small>' + esc(Number(t.bagsPerHa) >= 1 ? trimN(t.bagsPerHa) : bagAmt(t.bagsPerHa)) + '/ha</small></div>').join('') + '</div>' : '')
                    + (check.length ? '<div class="cp2-nut"><div class="cp2-nut-h"><b>Nutrients for the season</b><small>what this program gives and what the target needs, in kg a hectare' + (area !== 1 ? ' (the whole lot in brackets)' : '') + '</small></div>'
                        + check.map((c) => { const top = Math.max(1, Number(c.have) || 0, Number(c.need) || 0); return '<div class="cp2-nut-row is-' + esc(c.verdict || 'unchecked') + '"><span class="l">' + esc(c.label) + '</span><div class="tr"><span class="have" style="width:' + Math.max(2, Math.round(((Number(c.have) || 0) / top) * 100)) + '%"></span>' + (c.need != null ? '<i class="need" style="left:' + Math.min(100, Math.round(((Number(c.need) || 0) / top) * 100)) + '%"></i>' : '') + '</div>'
                            + '<span class="v"><b>' + esc(trimN(c.have)) + '</b>' + (c.need != null ? ' / ' + esc(trimN(c.need)) : '') + (area !== 1 ? '<small>(' + esc(trimN(c.haveField)) + (c.needField != null ? ' / ' + esc(trimN(c.needField)) : '') + ')</small>' : '') + '</span>'
                            + '<span class="vd">' + (c.verdict === 'ok' ? '✅ on target' : (c.verdict === 'short' ? '⚠️ short by ' + esc(trimN(Math.abs(c.gap))) : (c.verdict === 'over' ? '↑ over by ' + esc(trimN(c.gap)) : 'not checked'))) + '</span></div>'; }).join('')
                        + (els.length ? '<p class="cp2-nut-sub">Secondary and micro elements</p>' + shown.map((e) => { const top = Math.max(1e-6, Number(e.plan) || 0, Number(e.need) || 0); return '<div class="cp2-nut-row is-el is-' + esc(e.state) + (e.beneficial ? ' is-ben' : '') + '"><span class="l" title="' + esc(e.name) + '">' + esc(e.key) + '</span><div class="tr"><span class="have" style="width:' + Math.max(2, Math.round(((Number(e.plan) || 0) / top) * 100)) + '%"></span>' + (e.need !== null ? '<i class="need" style="left:' + Math.min(100, Math.round(((Number(e.need) || 0) / top) * 100)) + '%"></i>' : '') + '</div>'
                            + '<span class="v"><b>' + esc(elAmt(e.plan)) + '</b>' + (e.need !== null ? ' / ' + esc(elAmt(e.need)) : '') + '</span><span class="vd">' + esc(e.name) + ': ' + (e.beneficial ? 'it helps, but is not required, so nothing is added' : e.state === 'short' ? '⚠️ short by ' + esc(elAmt(Math.max(0, Number(e.need) - Number(e.plan)))) + (PROG === 0 ? ', added below' : '') : (e.state === 'spray' ? '💧 given by the spray' + (e.sprayAt ? ' at ' + esc(e.sprayAt) : '') : esc(word[e.state] || ''))) + (e.beneficial ? ' (beneficial, not required)' : '') + '</span></div>'; }).join('') : '')
                        + ((g.unknownProducts || []).length ? '<p class="cp-note">Not counted (nutrient content unknown): ' + esc(g.unknownProducts.join(', ')) + '.</p>' : '') + '</div>' + (npk.note ? '<p class="cp-note">' + esc(sweepT(npk.note)) + '</p>' : '') : '') + '</div>' : '');
            wire();
            if (viewHost && viewHost.classList.contains('is-drawn')) requestAnimationFrame(() => hostBox.querySelectorAll('.cp-bar, .cp2-tot-row .tr span, .cp2-nut-row .have').forEach((el) => { el.style.transition = 'none'; el.style.transform = 'none'; }));
        };
        const wire = () => {
            const card = hostBox.querySelector('[data-stages]');
            if (!card) return;
            const g = programs[PROG] || programs[0];
            const detail = () => {
                const i = Math.max(0, Math.min(stages.length - 1, Number(card.dataset.sel) || 0));
                SEL = i;
                const st = Object.assign({}, stages[i] || {}, { fertilizer: (g.byStage || [])[i] || [] });
                card.querySelectorAll('[data-stage]').forEach((el) => el.classList.toggle('is-sel', Number(el.dataset.stage) === i));
                card.querySelectorAll('[data-lbl]').forEach((el) => el.classList.toggle('is-sel', Number(el.dataset.lbl) === i));
                const d = card.querySelector('[data-detail]');
                d.classList.add('is-swap');
                setTimeout(() => {
                    d.innerHTML = '<div class="cp2-d-head"><span class="cp2-row-n">' + (i + 1) + '</span><span><b>' + esc(st.stage || '') + '</b>' + (st.days ? '<small><em class="cp2-days">' + esc(st.days) + '</em></small>' : '') + '</span>'
                        + '<span class="cp2-d-nav"><button type="button" data-prev ' + (i === 0 ? 'disabled' : '') + ' aria-label="Previous stage">‹</button><button type="button" data-next ' + (i === stages.length - 1 ? 'disabled' : '') + ' aria-label="Next stage">›</button></span></div>'
                        + ((st.fertilizer || []).length ? '<div class="cp2-d-fert">' + st.fertilizer.map((x) => '<div class="cp2-d-app"><i style="background:' + colour(x.product || 'Fertilizer') + '"></i><span><b>' + esc(bagAmt(x.totalBags)) + ' ' + esc(x.product || '') + '</b> <small>(' + esc(Number(x.bagsPerHa) >= 1 ? trimN(x.bagsPerHa) : bagAmt(x.bagsPerHa)) + '/ha)</small>' + (x.purpose ? '<em>' + esc(sweepT(x.purpose)) + '</em>' : '') + '</span></div>').join('') + '</div>' : '<p class="cp2-dim">No fertilizer at this stage.</p>')
                        + (rec.foliars || []).filter((f) => sameStage(f.stage, st.stage)).map((f) => '<div class="cp2-d-fol"><span>🍃 Foliar spray</span><p><b>' + esc(foliarName(f.product)) + '</b>' + (f.why ? '. ' + esc(sweepT(f.why)) : '') + '</p></div>').join('');
                    d.classList.remove('is-swap');
                }, d.innerHTML ? 140 : 0);
            };
            card.addEventListener('click', (e) => {
                const tab = e.target.closest('[data-prog]');
                if (tab) { PROG = Number(tab.dataset.prog) || 0; paint(); return; }
                const b = e.target.closest('[data-stage]');
                if (b) { card.dataset.sel = b.dataset.stage; detail(); return; }
                if (e.target.closest('[data-prev]')) { card.dataset.sel = String(Math.max(0, Number(card.dataset.sel) - 1)); detail(); return; }
                if (e.target.closest('[data-next]')) { card.dataset.sel = String(Math.min(stages.length - 1, Number(card.dataset.sel) + 1)); detail(); }
            });
            detail();
        };
        paint();
    }


    const host = (u) => { try { const h = new URL(u).hostname.replace(/^www\./, ''); return /vertexaisearch\.cloud\.google\.com$/.test(h) ? '' : h; } catch (_) { return ''; } };
    const list = (xs) => (Array.isArray(xs) ? xs : []).filter((x) => x && String(x).trim());
    const PALETTE = ['#4a7c2a', '#8fc96a', '#b45309', '#2563eb', '#7c3aed', '#db2777', '#0891b2', '#65a30d'];
    const trimN = (n) => { const v = Number(n) || 0; return Number.isInteger(v) ? String(v) : String(Math.round(v * 10) / 10); };
    const numOf = (s) => { const m = String(s || '').replace(/,/g, '').match(/(\d+(\.\d+)?)/); return m ? Number(m[1]) : null; };

    /* Two shapes live on the shelf: protocols written before 2026-09-18
       (stages + fertilizer program + protection lists) and the two-part
       protocol (background + recommendation). Each is drawn by its own
       hand; the tail (attach, again, delete) is shared. */
    function drawReport(hostEl, item, mode, quiet) {
        const r = item.report || {};
        if (Number(r.format) === 4) drawV4(hostEl, item, mode);
        else if (r.recommendation && r.background) drawV2(hostEl, item, mode);
        else drawV1(hostEl, item, mode);
        finishDraw(hostEl, item, mode, quiet);
    }

    function drawV1(hostEl, item, mode) {
        const r = item.report || {};
        const p = item.params || {};
        // No emoji shortcodes, and no interjections -- a report saved before the
        // register was written may still open with "Aray," or "Naku!"; they go.
        const sweep = (t) => { const s = String(t || '').replace(/:[a-z0-9_-]+:/gi, '').replace(/(^|[.!?]\s+)(aray|naku|hala|grabe|whoa|wow|ooh|oh no|hay naku|sus|ay)\b[,!.]*\s*/gi, '$1').replace(/\s{2,}/g, ' ').trim(); return s.charAt(0).toUpperCase() + s.slice(1); };
        const crop = (OPT ? OPT.crops.find((c) => c.key === p.crop) : null) || {};
        const month = (OPT ? (OPT.months.find((m) => m.key === p.month) || {}).label : '') || p.month || '';
        const A = r.assumed || {};
        const fert = r.fertilizer || {};
        const prog = fert.program || [];
        const names = [...new Set(prog.flatMap((s) => (s.products || []).map((x) => x.name || 'Fertilizer')))];
        const colour = (n) => PALETTE[Math.max(0, names.indexOf(n)) % PALETTE.length];
        const maxBags = Math.max(0.1, ...prog.map((s) => Number(s.bags) || 0));
        const prot = r.protection || {};
        const yo = r.yieldOutlook || {};
        const tN = numOf(yo.target), rN = numOf(yo.realistic);
        const yMax = Math.max(tN || 0, rN || 0, 1);

        hostEl.innerHTML = `
            <div class="wtp-hero">
                <h2>${esc(crop.icon || '🌱')} ${esc(crop.label || 'Your crop')}${p.variety ? ' · ' + esc(p.variety) : (A.variety ? ' · ' + esc(A.variety) : '')}</h2>
                <p class="h-win">${esc(sweep(r.headline) || 'Your season, stage by stage')}</p>
                <p class="h-why">${esc(sweep(A.note || ''))}</p>
                <div class="wtp-chips">
                    <span class="wtp-chip">📍 ${esc(p.location || '')}</span>
                    <span class="wtp-chip">🗓️ ${esc(month)}</span>
                    <span class="wtp-chip">${esc(OPT?.methods?.[p.method]?.label || p.method || '')}</span>
                    <span class="wtp-chip">${esc(trimN(p.area))} ha</span>
                    ${A.maturityDays ? `<span class="wtp-chip">⏱️ ${esc(A.maturityDays)}</span>` : ''}
                    <span class="wtp-chip">${esc(OPT?.priorities?.[p.priority]?.label || '')}</span>
                    <span class="wtp-chip">Confidence: ${esc(r.confidence || 'moderate')}</span>
                    ${item.charged ? `<span class="wtp-chip">${item.charged} credits</span>` : ''}
                </div>
            </div>

            ${guideHtml()}

            <div class="cp-clock"><span class="e">👁️</span><span><b>Go by the crop, not the calendar.</b> Each step below follows a growth stage you can see in the field. The week counts are only a guide. Check every few days and act on what the plants and pests show.</span></div>

            ${(r.stages || []).length ? `
            <div class="wtp-card">
                <h3>Stage by stage <small>what to do at each stage</small></h3>
                <div class="cp-stages">
                    ${(r.stages || []).map((s, i) => `
                    <div class="cp-stage" style="transition-delay:${i * 60}ms">
                        <span class="cp-stage-n">${i + 1}</span>
                        <span class="cp-stage-b">
                            <b>${esc(s.stage || '')}</b>
                            ${s.signs ? `<div class="signs">${esc(sweep(s.signs))}</div>` : ''}
                            ${s.hint && s.hint !== 'n/a' ? `<div class="hint">${esc(s.hint)}</div>` : ''}
                            ${list(s.tasks).length ? `<ul>${list(s.tasks).map((t) => `<li>${esc(sweep(t))}</li>`).join('')}</ul>` : ''}
                        </span>
                    </div>`).join('')}
                </div>
            </div>` : ''}

            <div class="wtp-card">
                <h3>Fertilizer <small>50 kg bags for the whole field (${esc(trimN(p.area))} ha), ${esc(trimN(fert.totalBags || 0))} bags in all</small></h3>
                ${prog.length ? `
                <div class="cp-chart">
                    ${prog.map((s) => `<div class="cp-col"><span class="cp-col-val">${esc(trimN(s.bags))}</span><div class="cp-bar" style="height:${Math.max(4, Math.round(((Number(s.bags) || 0) / maxBags) * 100))}%">${(s.products || []).map((x) => `<span class="cp-seg" style="flex:${Math.max(0.01, Number(x.totalBags) || 0)};background:${colour(x.name || 'Fertilizer')}" title="${esc(x.name)} · ${esc(trimN(x.totalBags))} bags"></span>`).join('')}</div></div>`).join('')}
                </div>
                <div class="cp-lbls">${prog.map((s) => `<span class="cp-lbl">${esc(s.stage || '')}</span>`).join('')}</div>
                <div class="cp-legend">${names.map((n) => `<span><i style="background:${colour(n)}"></i>${esc(n)}</span>`).join('')}</div>
                <div class="cp-fert">
                    ${prog.map((s) => `
                    <div class="cp-fert-row">
                        <span class="st"><b>${esc(s.stage || '')}</b><i>${esc(s.timing || '')}</i></span>
                        <span class="pr">${(s.products || []).map((x) => `<div><b>${esc(trimN(x.totalBags))} ${Number(x.totalBags) === 1 ? 'bag' : 'bags'} ${esc(x.name || '')}</b> <small>(${esc(trimN(x.bagsPerHa))}/ha)</small>${x.why ? '. ' + esc(sweep(x.why)) : ''}</div>`).join('')}${s.note ? `<div><small>${esc(sweep(s.note))}</small></div>` : ''}</span>
                    </div>`).join('')}
                </div>
                <div class="cp-totals">${(fert.totals || []).map((t) => `<span class="cp-total">${esc(trimN(t.bags))} bags <i>${esc(t.name)}</i></span>`).join('')}</div>
                ${fert.note ? `<p class="cp-note">${esc(sweep(fert.note))}</p>` : ''}` : '<p class="wtp-plain">No fertilizer program came back.</p>'}
            </div>

            ${((prot.insects || []).length || (prot.diseases || []).length || (prot.weeds || []).length) ? `
            <div class="wtp-card">
                <h3>Crop protection <small>check first, spray only when needed, and keep these ready</small></h3>
                <div class="cp-prot">
                    ${(prot.insects || []).map((x) => `<div class="cp-pc is-insect"><span class="tag">Insect · ${esc(x.stage || '')}</span><b>${esc(x.pest || '')}</b>${x.watchFor ? `<p><em>Look for:</em> ${esc(sweep(x.watchFor))}</p>` : ''}${x.threshold ? `<p><em>Act when:</em> ${esc(sweep(x.threshold))}</p>` : ''}${x.action ? `<p><em>Do:</em> ${esc(sweep(x.action))}</p>` : ''}${x.prepare ? `<p><em>Prepare:</em> ${esc(sweep(x.prepare))}</p>` : ''}</div>`).join('')}
                    ${(prot.diseases || []).map((x) => `<div class="cp-pc is-disease"><span class="tag">Disease · ${esc(x.stage || '')}</span><b>${esc(x.disease || '')}</b>${x.watchFor ? `<p><em>Look for:</em> ${esc(sweep(x.watchFor))}</p>` : ''}${x.action ? `<p><em>Do:</em> ${esc(sweep(x.action))}</p>` : ''}${x.prepare ? `<p><em>Prepare:</em> ${esc(sweep(x.prepare))}</p>` : ''}</div>`).join('')}
                    ${(prot.weeds || []).map((x) => `<div class="cp-pc is-weed"><span class="tag">Weed · ${esc(x.when || '')}</span><b>${esc(x.weed || '')}</b>${x.control ? `<p>${esc(sweep(x.control))}</p>` : ''}</div>`).join('')}
                </div>
            </div>` : ''}

            ${(r.foliar || []).length ? `
            <div class="wtp-card">
                <h3>Foliar sprays and extras <small>small top ups, and if they pay off</small></h3>
                ${(r.foliar || []).map((f) => `<div class="wtp-win-row is-note"><b>${esc(f.product || '')}${f.stage ? ' · ' + esc(f.stage) : ''}${f.optional ? ' · optional' : ''}:</b> <span>${esc(sweep(f.why))}</span></div>`).join('')}
            </div>` : ''}

            ${(r.irrigation || []).length ? `
            <div class="wtp-card">
                <h3>Water <small>with ${esc((OPT?.waters?.[p.water] || '').toLowerCase())}</small></h3>
                <div class="cp-water">
                    ${(r.irrigation || []).map((w) => `<div class="cp-wrow"><span class="e">💧</span><span class="t"><b>${esc(w.stage || '')}</b>${esc(sweep(w.need || ''))}${w.how ? '. ' + esc(sweep(w.how)) : ''}${w.ifDry ? `<i>If dry: ${esc(sweep(w.ifDry))}</i>` : ''}${w.ifWet ? `<i>If wet: ${esc(sweep(w.ifWet))}</i>` : ''}</span></div>`).join('')}
                </div>
            </div>` : ''}

            ${r.weather ? `
            <div class="wtp-card">
                <h3>Weather this season</h3>
                <p class="wtp-plain">${esc(sweep(r.weather.outlook || ''))}</p>
                ${r.weather.enso ? `<p class="wtp-plain mt-2"><b>El Niño or La Niña:</b> ${esc(sweep(r.weather.enso))}</p>` : ''}
                ${list(r.weather.risks).length ? `<div class="mt-3">${list(r.weather.risks).map((x) => `<div class="wtp-win-row is-no"><b>⚠️</b> <span>${esc(x)}</span></div>`).join('')}</div>` : ''}
            </div>` : ''}

            <div class="wtp-card">
                <h3>Yield <small>your target and what this variety really gives here</small></h3>
                <div class="cp-yo">
                    ${tN ? `<div class="cp-yo-row"><small>Target</small><div class="tr"><span class="is-target" style="width:${Math.round((tN / yMax) * 100)}%"></span></div><b>${esc(yo.target || '')}</b></div>` : ''}
                    ${rN ? `<div class="cp-yo-row"><small>Realistic</small><div class="tr"><span style="width:${Math.round((rN / yMax) * 100)}%"></span></div><b>${esc(yo.realistic || '')}</b></div>` : ''}
                </div>
                ${yo.note ? `<p class="cp-note">${esc(sweep(yo.note))}</p>` : ''}
                ${!tN && !rN ? `<p class="wtp-plain">${esc(sweep(yo.realistic || yo.note || ''))}</p>` : ''}
            </div>

            ${(r.prepare || []).length ? `
            <div class="wtp-card">
                <h3>What to prepare <small>for the whole field, and when you need it</small></h3>
                <div class="cp-shop">
                    ${(r.prepare || []).map((x) => `<div class="cp-item"><span class="n">${esc(x.item || '')}<small>${esc(x.whenNeeded || '')}</small></span><span class="q">${esc(x.qty != null ? trimN(x.qty) : '')} ${esc(x.unit || '')}${x.estCost ? `<small>${esc(x.estCost)}</small>` : ''}</span></div>`).join('')}
                </div>
                ${r.prepareCost ? `<p class="cp-note"><b>${esc(r.prepareCost.sum)}</b> for the ${r.prepareCost.known === r.prepareCost.of ? 'whole list' : r.prepareCost.known + ' of ' + r.prepareCost.of + ' items with a price'}. These are rough shop prices, so yours will differ.</p>` : ''}
            </div>` : ''}

            ${list(r.watch).length ? `
            <div class="wtp-card">
                <h3>Watch out for <small>what often goes wrong here, and when to act</small></h3>
                <ul class="cp-watch">${list(r.watch).map((x) => `<li>${esc(sweep(x))}</li>`).join('')}</ul>
            </div>` : ''}

            <div class="wtp-card">
                <h3>In plain words</h3>
                <p class="wtp-plain">${esc(sweep(r.summary))}</p>
                ${(r.dataGaps || []).length ? `<h3 class="mt-4">What Anee could not check</h3><ul class="wtp-gap">${(r.dataGaps || []).map((g) => `<li>${esc(g)}</li>`).join('')}</ul>` : ''}
            </div>

            ${(r.webSources || []).length ? `
            <div class="wtp-card">
                <h3>📚 Other sources used</h3>
                <div class="va-links">${(() => { const seen = new Set(); return (r.webSources || []).map((x) => ({ name: x.title || host(x.url) || 'A published source', h: host(x.url) })).filter((x) => { const k = x.name.toLowerCase(); if (seen.has(k)) return false; seen.add(k); return true; }).map((x) => `<span class="va-link is-plain"><span class="l-t">${esc(x.name)}</span>${x.h && x.h !== x.name ? `<span class="l-h">${esc(x.h)}</span>` : ''}</span>`).join(''); })()}</div>
            </div>` : ''}

            <div class="wtp-acts">
                <button type="button" class="btn btn-primary w-full" data-cp-attach>${OPT && OPT.aneeFace ? `<img class="wtp-anee-face" src="${esc(OPT.aneeFace)}" alt="">` : '🤖'} Attach to Anee</button>
                ${mode === 'fresh' ? `<button type="button" class="btn btn-white w-full" data-cp-again>📋 Write another protocol</button>` : ''}
                <button type="button" class="btn btn-white w-full" data-cp-delete>🗑 Delete</button>
            </div>`;

    }

    /* ---- the shared tail: the actions under either drawing ---- */
    const actionsHtml = (mode) => `
            <div class="wtp-acts">
                <button type="button" class="btn btn-primary w-full" data-cp-attach>${OPT && OPT.aneeFace ? `<img class="wtp-anee-face" src="${esc(OPT.aneeFace)}" alt="">` : '🤖'} Attach to Anee</button>
                ${mode === 'fresh' ? `<button type="button" class="btn btn-white w-full" data-cp-again>📋 Write another protocol</button>` : ''}
                <button type="button" class="btn btn-white w-full" data-cp-delete>🗑 Delete</button>
            </div>`;
    const sourcesHtml = (r) => (r.webSources || []).length ? `
            <div class="wtp-card">
                <h3>📚 Other sources used</h3>
                <div class="va-links">${(() => { const seen = new Set(); return (r.webSources || []).map((x) => ({ name: x.title || host(x.url) || 'A published source', h: host(x.url) })).filter((x) => { const k = x.name.toLowerCase(); if (seen.has(k)) return false; seen.add(k); return true; }).map((x) => `<span class="va-link is-plain"><span class="l-t">${esc(x.name)}</span>${x.h && x.h !== x.name ? `<span class="l-h">${esc(x.h)}</span>` : ''}</span>`).join(''); })()}</div>
            </div>` : '';

    /* A foliar is named by its nutrient -- "Zinc spray", never the salt or
       the strength. The server rewrites new reports; this does the same for
       the ones saved before it did. */
    const NUTRIENTS = [['zinc', 'Zinc'], ['boron', 'Boron'], ['calcium', 'Calcium'], ['magnesium', 'Magnesium'], ['iron', 'Iron'], ['manganese', 'Manganese'], ['copper', 'Copper'], ['molybdenum', 'Molybdenum'], ['sulfur', 'Sulfur'], ['sulphur', 'Sulfur'], ['potassium', 'Potassium'], ['phosph', 'Phosphorus'], ['nitrogen', 'Nitrogen'], ['urea', 'Nitrogen'], ['silicon', 'Silicon'], ['seaweed', 'Seaweed'], ['amino', 'Amino acid'], ['micronutrient', 'Micronutrient']];
    const foliarName = (product) => {
        const t = String(product || '').toLowerCase();
        const found = [];
        NUTRIENTS.forEach(([needle, label]) => { if (t.includes(needle) && !found.includes(label)) found.push(label); });
        if (found.length) return (found.length > 1 ? found[0] + ' and ' + found[1].toLowerCase() : found[0]) + ' spray';
        return String(product || '').replace(/\b(sulfate|sulphate|heptahydrate|monohydrate|chelate|chelated|edta|solubor|borax|boric acid|nitrate|chloride|oxide|hydroxide)\b/gi, '').replace(/\d+(\.\d+)?\s*%/g, '').replace(/\s*or equivalent\.?$/i, '').replace(/\s{2,}/g, ' ').trim() || 'Foliar spray';
    };

    /* ---- the opening note: a guide, and the farm has the last word ---- */
    const guideHtml = () => `
            <div class="cp2-guide" role="note">
                <span class="cp2-guide-e">🧭</span>
                <div class="cp2-guide-t">
                    <b>Remember: this is just a guide.</b>
                    <p>Anee built this from your crop, the weather forecast, your soil and water, and your goal. She has not walked your field, so change it to fit what you know.</p>
                    <div class="cp2-guide-tags"><span>🕒 your timing</span><span>💧 your water</span><span>👩‍🌾 your labor &amp; tools</span><span>🛒 your market</span><span>🌱 what the field shows</span></div>
                </div>
            </div>`;

    /* ---- THE TWO-PART PROTOCOL: background, then the recommendation ---- */
    function drawV2(hostEl, item, mode) {
        const r = item.report || {};
        const p = item.params || {};
        // No emoji shortcodes, and no interjections -- a report saved before the
        // register was written may still open with "Aray," or "Naku!"; they go.
        const sweep = (t) => { const s = String(t || '').replace(/:[a-z0-9_-]+:/gi, '').replace(/(^|[.!?]\s+)(aray|naku|hala|grabe|whoa|wow|ooh|oh no|hay naku|sus|ay)\b[,!.]*\s*/gi, '$1').replace(/\s{2,}/g, ' ').trim(); return s.charAt(0).toUpperCase() + s.slice(1); };
        const crop = (OPT ? OPT.crops.find((c) => c.key === p.crop) : null) || {};
        const month = (OPT ? (OPT.months.find((m) => m.key === p.month) || {}).label : '') || p.month || '';
        const bg = r.background || {};
        const wx = bg.weather || {};
        const v = bg.variety || {};
        const rec = r.recommendation || {};
        const stages = rec.stages || [];
        // The five programs (older protocols have none: the stages' own
        // fertilizer stands as the one program). Every program's colours
        // come from one shared palette so a product keeps its colour across tabs.
        const programs = (rec.programs || []).length ? rec.programs : [{ name: 'Recommended', for: '', byStage: stages.map((st) => st.fertilizer || []), stageBags: stages.map((st) => Number(st.bags) || 0), totals: rec.totals || [], totalBags: rec.totalBags || 0, check: (rec.npk || {}).check || [], unknownProducts: (rec.npk || {}).unknownProducts || [], orphans: [] }];
        const names = [...new Set(programs.flatMap((g) => (g.byStage || []).flat().concat(g.orphans || []).map((x) => x.product || 'Fertilizer')))];
        const colour = (n) => PALETTE[Math.max(0, names.indexOf(n)) % PALETTE.length];
        const yo = rec.yield || {};
        const tN = numOf(yo.target), rN = numOf(yo.realistic);
        const yMax = Math.max(tN || 0, rN || 0, 1);
        const npk = rec.npk || {};
        const water = rec.water || {};
        const gran = rec.granulars || {};
        const firstFert = Math.max(0, stages.findIndex((st) => (st.fertilizer || []).length));
        const norm = (t) => String(t || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
        const sameStage = (a, b) => { const x = norm(a), y = norm(b); return !!x && !!y && (x === y || x.includes(y) || y.includes(x)); };

        hostEl.innerHTML = `
            <div class="wtp-hero">
                <h2>${esc(crop.icon || '🌱')} ${esc(crop.label || 'Your crop')}${(p.variety || v.name) ? ' · ' + esc(p.variety || v.name) : ''}</h2>
                <p class="h-win">${esc(sweep(r.headline) || 'Your season, stage by stage')}</p>
                <div class="wtp-chips">
                    <span class="wtp-chip">📍 ${esc(p.location || '')}</span>
                    <span class="wtp-chip">🗓️ ${esc(month)}</span>
                    <span class="wtp-chip">${esc(OPT?.methods?.[p.method]?.label || p.method || '')}</span>
                    <span class="wtp-chip">${esc(trimN(p.area))} ha</span>
                    ${Number(v.maturityDays) > 0 ? `<span class="wtp-chip">⏱️ ${esc(String(Math.round(Number(v.maturityDays))))} days</span>` : ''}
                    <span class="wtp-chip">${esc(OPT?.priorities?.[p.priority]?.label || '')}</span>
                    <span class="wtp-chip">Confidence: ${esc(r.confidence || 'moderate')}</span>
                    ${item.charged ? `<span class="wtp-chip">${item.charged} credits</span>` : ''}
                </div>
            </div>

            ${guideHtml()}

            <div class="cp2-part"><span class="cp2-part-n">1</span><span><b>Background</b><small>the place, the weather ahead, the variety</small></span></div>

            <div class="wtp-card cp2-bg">
                <div class="cp2-bg-row"><span class="cp2-bg-e">📍</span><div><b>The place</b><p>${esc(sweep(bg.place))}</p>${bg.field ? `<p class="cp2-dim">${esc(sweep(bg.field))}</p>` : ''}</div></div>
                <div class="cp2-bg-row"><span class="cp2-bg-e">🌤️</span><div><b>The weather ahead</b><p>${esc(sweep(wx.outlook))}</p>${wx.enso ? `<p class="cp2-dim">${esc(sweep(wx.enso))}</p>` : ''}
                    ${list(wx.risks).length ? `<div class="cp2-pills">${list(wx.risks).map((x) => `<span class="cp2-pill is-risk">⚠️ ${esc(sweep(x))}</span>`).join('')}</div>` : ''}</div></div>
                <div class="cp2-bg-row"><span class="cp2-bg-e">🧬</span><div><b>${esc(v.name || p.variety || 'The variety')}${v.by ? ` <small>by ${esc(v.by)}${v.released ? ', ' + esc(v.released) : ''}</small>` : ''}</b>
                    ${v.found === false ? `<p class="cp2-dim">Not found online. The numbers below are Anee's best guess for it.</p>` : ''}
                    <div class="cp2-pills">
                        ${Number(v.maturityDays) > 0 ? `<span class="cp2-pill">⏱ ${esc(String(Math.round(Number(v.maturityDays))))} days to maturity</span>` : ''}
                        ${v.yieldPotential ? `<span class="cp2-pill">🌾 ${esc(v.yieldPotential)}</span>` : ''}
                        ${v.season ? `<span class="cp2-pill">🗓️ ${esc(v.season)}</span>` : ''}
                        ${v.source ? `<span class="cp2-pill is-src">📚 ${esc(v.source)}</span>` : ''}
                    </div>
                    ${v.traits ? `<p>${esc(sweep(v.traits))}</p>` : ''}
                    ${v.caution ? `<p class="wp-watch">⚠️ ${esc(sweep(v.caution))}</p>` : ''}
                </div></div>
            </div>

            <div class="cp2-part"><span class="cp2-part-n">2</span><span><b>Recommendation</b><small>what to apply at each stage, and why</small></span></div>

            ${rec.intro ? `<div class="wtp-card"><p class="wtp-plain">${esc(sweep(rec.intro))}</p></div>` : ''}

            <div class="cp-clock"><span class="e">👁️</span><span><b>Go by the crop, not the calendar.</b> Tap a stage below to see what to apply and why.</span></div>

            ${(gran.soilLogic || gran.timingLogic) ? `
            <div class="wtp-card cp3-gran">
                <h3>Why these fertilizers <small>which bag and when, chosen for this soil</small></h3>
                ${gran.soilLogic ? `<div class="cp3-gran-row"><span class="e">🧪</span><div><b>The soil decides the bag</b><p>${esc(sweep(gran.soilLogic))}</p></div></div>` : ''}
                ${gran.timingLogic ? `<div class="cp3-gran-row"><span class="e">⏱️</span><div><b>The stage decides the amount</b><p>${esc(sweep(gran.timingLogic))}</p></div></div>` : ''}
                ${(gran.rejected || []).filter((r) => r && r.what).length ? `<div class="cp3-gran-row"><span class="e">🚫</span><div><b>Considered but not used</b>
                    <div class="cp3-no">${(gran.rejected || []).filter((r) => r && r.what).map((r) => `<div class="cp3-no-row"><b>${esc(sweep(r.what))}</b>${r.why ? `<p>${esc(sweep(r.why))}</p>` : ''}</div>`).join('')}</div></div></div>` : ''}
                ${list(gran.cautions).length ? `<div class="cp2-pills">${list(gran.cautions).map((x) => `<span class="cp2-pill is-risk">⚠️ ${esc(sweep(x))}</span>`).join('')}</div>` : ''}
            </div>` : ''}

            <div data-cp3-fert></div>

            ${'' /* the fertilizer and totals cards are painted below, per program */}
            ${(rec.deficiencies || []).length ? `
            <div class="wtp-card">
                <h3>Nutrients this soil may lack <small>${esc(String((OPT && OPT.soils && OPT.soils[p.soil]) || '').split(' — ')[0].toLowerCase() || 'this soil')}: what to watch for, and why</small></h3>
                <div class="cp2-defs">
                    ${(rec.deficiencies || []).map((d, i) => `<div class="cp2-def" style="transition-delay:${i * 60}ms"><div class="cp2-def-h"><b>${esc(d.nutrient || '')}</b>${d.when ? `<span class="tag">${esc(d.when)}</span>` : ''}</div>${d.why ? `<p>${esc(sweep(d.why))}</p>` : ''}${d.signs ? `<p><em>Signs:</em> ${esc(sweep(d.signs))}</p>` : ''}${d.action ? `<p><em>What to do:</em> ${esc(sweep(d.action))}</p>` : ''}</div>`).join('')}
                </div>
            </div>` : ''}

            ${(rec.threats || []).length ? `
            <div class="wtp-card">
                <h3>Threats to check <small>what to look for and what to do</small></h3>
                <div class="cp2-threats">
                    ${(rec.threats || []).map((t) => `<div class="cp2-threat"><span class="tag">${esc(t.stage || '')}</span><b>${esc(t.threat || '')}</b>${t.sign ? `<p><em>Look for:</em> ${esc(sweep(t.sign))}</p>` : ''}${t.action ? `<p><em>Then:</em> ${esc(sweep(t.action))}</p>` : ''}${t.product ? `<p class="is-prod"><em>Use:</em> ${esc(sweep(t.product))}</p>` : ''}</div>`).join('')}
                </div>
            </div>` : ''}

            ${(rec.foliars || []).length ? `
            <div class="wtp-card">
                <h3>Foliar sprays <small>only where they pay off on this soil</small></h3>
                <div class="cp2-foliars">
                    ${(rec.foliars || []).map((f) => `<div class="cp2-foliar"><span class="e">🍃</span><div><small>${esc(f.stage || '')}</small><b>${esc(foliarName(f.product))}</b>${f.why ? `<p>${esc(sweep(f.why))}</p>` : ''}</div></div>`).join('')}
                </div>
            </div>` : ''}

            ${water.plan ? `
            <div class="wtp-card">
                <h3>Water <small>with ${esc((OPT?.waters?.[p.water] || '').toLowerCase())}</small></h3>
                <p class="wtp-plain">${esc(sweep(water.plan))}</p>
                <div class="cp2-pills">${water.ifDry ? `<span class="cp2-pill">☀️ If dry: ${esc(sweep(water.ifDry))}</span>` : ''}${water.ifWet ? `<span class="cp2-pill">🌧️ If wet: ${esc(sweep(water.ifWet))}</span>` : ''}</div>
            </div>` : ''}

            ${(tN || rN || yo.note) ? `
            <div class="wtp-card">
                <h3>Yield <small>your target and what this variety really gives here</small></h3>
                <div class="cp-yo">
                    ${tN ? `<div class="cp-yo-row"><small>Target</small><div class="tr"><span class="is-target" style="width:${Math.round((tN / yMax) * 100)}%"></span></div><b>${esc(yo.target || '')}</b></div>` : ''}
                    ${rN ? `<div class="cp-yo-row"><small>Realistic</small><div class="tr"><span style="width:${Math.round((rN / yMax) * 100)}%"></span></div><b>${esc(yo.realistic || '')}</b></div>` : ''}
                </div>
                ${yo.note ? `<p class="cp-note">${esc(sweep(yo.note))}</p>` : ''}
            </div>` : ''}

            ${(p.fieldProfileSaid || []).length && window.aneeFieldCard ? `
            <div class="wtp-card">
                <h3>🌾 Your field in detail</h3>
                ${window.aneeFieldCard.said(p.fieldProfileSaid)}
            </div>` : ''}

            <div class="wtp-card">
                <h3>In plain words</h3>
                <p class="wtp-plain">${esc(sweep(r.summary))}</p>
                ${(r.dataGaps || []).length ? `<h3 class="mt-4">What Anee could not check</h3><ul class="wtp-gap">${(r.dataGaps || []).map((g) => `<li>${esc(g)}</li>`).join('')}</ul>` : ''}
            </div>

            ${sourcesHtml(r)}
            ${actionsHtml(mode)}`;

        /* THE FERTILIZER, PER PROGRAM: tag-like tabs choose one of the five
           programs; the chart, the rows, the detail panel, the totals and
           the nutrient check all repaint for it. The selected stage survives
           a tab change. */
        const fertHost = hostEl.querySelector('[data-cp3-fert]');
        let PROG = 0;
        let SEL = firstFert;
        const paintFert = () => {
            const g = programs[PROG] || programs[0];
            const byStage = g.byStage || [];
            const stageBags = g.stageBags || [];
            const maxBags = Math.max(0.1, ...stageBags.map((b) => Number(b) || 0));
            const totals = g.totals || [];
            const maxTotal = Math.max(0.1, ...totals.map((t) => Number(t.bags) || 0));
            const check = g.check || [];
            fertHost.innerHTML = `
            ${stages.length ? `
            <div class="wtp-card" data-cp2-stages data-sel="${SEL}">
                ${programs.length > 1 ? `<div class="cp3-tabs" role="tablist" aria-label="Fertilizer programs">${programs.map((pg, i) => `<button type="button" class="cp3-tab${i === PROG ? ' is-on' : ''}" role="tab" aria-selected="${i === PROG}" data-cp3-prog="${i}"><b>${esc(pg.name || ('Option ' + (i + 1)))}</b><small>${esc(trimN(pg.totalBags || 0))} bags${verdictWord(pg.check, pg.elements)}</small></button>`).join('')}</div>
                ${g.for ? `<p class="cp3-for">${i0(PROG) ? '⭐ Recommended: ' : ''}${esc(sweep(g.for))}</p>` : ''}` : ''}
                <h3>Fertilizer by growth stage <small>50 kg bags for the whole field (${esc(trimN(p.area))} ha), ${esc(trimN(g.totalBags || 0))} bags in all</small></h3>
                <div class="cp-chart cp2-chart">
                    ${stages.map((st, i) => `<button type="button" class="cp-col cp2-col" data-cp2-stage="${i}" aria-label="${esc(st.stage || '')}"><span class="cp-col-val">${Number(stageBags[i]) > 0 ? esc(trimN(stageBags[i])) : '·'}</span><div class="cp-bar" style="height:${Math.max(3, Math.round(((Number(stageBags[i]) || 0) / maxBags) * 100))}%">${(byStage[i] || []).map((x) => `<span class="cp-seg" style="height:${Math.max(0, ((Number(x.totalBags) || 0) / Math.max(0.1, Number(stageBags[i]) || 0)) * 100)}%;background:${colour(x.product || 'Fertilizer')}"></span>`).join('')}</div></button>`).join('')}
                </div>
                <div class="cp-lbls cp2-lbls">${stages.map((st, i) => `<span class="cp-lbl" data-cp2-lbl="${i}">${i + 1}</span>`).join('')}</div>
                ${names.length ? `<div class="cp-legend">${names.filter((n) => (g.byStage || []).flat().concat(g.orphans || []).some((x) => (x.product || 'Fertilizer') === n)).map((n) => `<span><i style="background:${colour(n)}"></i>${esc(n)}</span>`).join('')}</div>` : ''}
                <div class="cp2-detail" data-cp2-detail></div>
                <div class="cp2-rows">
                    ${stages.map((st, i) => `
                    <button type="button" class="cp2-row" data-cp2-stage="${i}">
                        <span class="cp2-row-n">${i + 1}</span>
                        <span class="cp2-row-t"><b>${esc(st.stage || '')}</b><small>${st.days ? `<em class="cp2-days">${esc(st.days)}</em>` : ''}${(byStage[i] || []).length ? (byStage[i] || []).map((x) => esc(bagAmt(x.totalBags)) + ' ' + esc(x.product || '')).join(' · ') : (st.observe ? 'watch only' : 'nothing to apply')}</small></span>
                        <span class="cp2-row-b">${Number(stageBags[i]) > 0 ? esc(trimN(stageBags[i])) + ' bags' : ''}</span>
                    </button>`).join('')}
                    ${(g.orphans || []).length ? `<div class="cp3-orphans"><b>Also in this program</b>${(g.orphans || []).map((x) => `<div>${esc(bagAmt(x.totalBags))} ${esc(x.product || '')} · ${esc(x.stage || '')}${x.purpose ? `. <small>${esc(sweep(x.purpose))}</small>` : ''}</div>`).join('')}</div>` : ''}
                </div>
            </div>` : ''}

            ${(totals.length || check.length) ? `
            <div class="wtp-card">
                <h3>Totals to use <small>for the whole field, amounts only${programs.length > 1 ? ' · ' + esc(g.name || '') : ''}</small></h3>
                ${totals.length ? `<div class="cp2-tot">
                    ${totals.map((t) => `<div class="cp2-tot-row"><span class="n"><i style="background:${colour(t.product)}"></i>${esc(t.product)}</span><div class="tr"><span style="width:${Math.max(2, Math.round((Number(t.bags) / maxTotal) * 100))}%;background:${colour(t.product)}"></span></div><b>${esc(bagAmt(t.bags))}</b><small>${esc(Number(t.bagsPerHa) >= 1 ? trimN(t.bagsPerHa) : bagAmt(t.bagsPerHa))}/ha</small></div>`).join('')}
                </div>` : ''}
                ${check.length ? `
                <div class="cp2-nut">
                    <div class="cp2-nut-h"><b>Nutrients for the season</b><small>what this program gives and what the target needs, in kg per hectare${Number(p.area) && Number(p.area) !== 1 ? ' (whole field in brackets)' : ''}</small></div>
                    ${check.map((c) => { const top = Math.max(1, Number(c.have) || 0, Number(c.need) || 0); return `
                    <div class="cp2-nut-row is-${esc(c.verdict || 'unchecked')}">
                        <span class="l">${esc(c.label)}</span>
                        <div class="tr"><span class="have" style="width:${Math.max(2, Math.round(((Number(c.have) || 0) / top) * 100))}%"></span>${c.need !== null && c.need !== undefined ? `<i class="need" style="left:${Math.min(100, Math.round(((Number(c.need) || 0) / top) * 100))}%"></i>` : ''}</div>
                        <span class="v"><b>${esc(trimN(c.have))}</b>${c.need !== null && c.need !== undefined ? ` / ${esc(trimN(c.need))}` : ''}${Number(p.area) && Number(p.area) !== 1 ? `<small>(${esc(trimN(c.haveField))}${c.needField !== null && c.needField !== undefined ? ' / ' + esc(trimN(c.needField)) : ''})</small>` : ''}</span>
                        <span class="vd">${c.verdict === 'ok' ? '✅ on target' : (c.verdict === 'short' ? `⚠️ short by ${esc(trimN(Math.abs(c.gap)))}` : (c.verdict === 'over' ? `↑ over by ${esc(trimN(c.gap))}` : 'not checked'))}</span>
                    </div>`; }).join('')}
                    ${(() => {
                        /* The secondary and micro elements (2026-10-17, the owner), as NPK Plus's Micros tab reads them:
                           a need where a shortfall is likely, the program's amount from each product's analysis. */
                        const els = g.elements || [];
                        const shown = els.filter((e) => e.need !== null || Number(e.plan) > 0);
                        if (!els.length) return '';
                        const word = { right: '✅ enough', short: '', over: '↑ too much', extra: 'extra, no need flagged', spray: '', testok: '✅ enough by your soil test' };
                        const quiet = els.filter((e) => e.need === null && !(Number(e.plan) > 0) && e.state === 'noflag').map((e) => e.name);
                        const ok = els.filter((e) => e.state === 'testok').map((e) => e.name);
                        return `<p class="cp2-nut-sub">Secondary and micro elements</p>`
                            + shown.map((e) => { const top = Math.max(1e-6, Number(e.plan) || 0, Number(e.need) || 0); return `
                    <div class="cp2-nut-row is-el is-${esc(e.state)}${e.beneficial ? ' is-ben' : ''}">
                        <span class="l" title="${esc(e.name)}">${esc(e.key)}</span>
                        <div class="tr"><span class="have" style="width:${Math.max(2, Math.round(((Number(e.plan) || 0) / top) * 100))}%"></span>${e.need !== null ? `<i class="need" style="left:${Math.min(100, Math.round(((Number(e.need) || 0) / top) * 100))}%"></i>` : ''}</div>
                        <span class="v"><b>${esc(elAmt(e.plan))}</b>${e.need !== null ? ` / ${esc(elAmt(e.need))}` : ''}${Number(p.area) && Number(p.area) !== 1 && e.need !== null ? `<small>(${esc(elAmt(e.planField))} / ${esc(elAmt(e.needField))})</small>` : ''}</span>
                        <span class="vd">${esc(e.name)}: ${e.state === 'short' ? `⚠️ short by ${esc(elAmt(Math.max(0, Number(e.need) - Number(e.plan))))}${e.fert ? '. Usual source: ' + esc(e.fert) : ''}` : (e.state === 'spray' ? `💧 given by the spray${e.sprayAt ? ' at ' + esc(e.sprayAt) : ''}` : esc(word[e.state] || ''))}${e.beneficial ? ' (beneficial, not required)' : ''}</span>
                    </div>`; }).join('')
                            + (quiet.length || ok.length ? `<p class="cp2-nut-quiet">${quiet.length ? 'No shortage flagged: ' + esc(quiet.join(', ')) + '. ' : ''}${ok.length ? 'Enough by your soil test: ' + esc(ok.join(', ')) + '. ' : ''}Counted the way NPK Plus counts them.</p>` : '');
                    })()}
                    ${(g.unknownProducts || []).length ? `<p class="cp-note">Not counted (nutrient content unknown): ${esc((g.unknownProducts || []).join(', '))}.</p>` : ''}
                </div>${npk.basis ? `<p class="cp-note">${esc(npk.basis)}</p>` : ''}${npk.note ? `<p class="cp-note">${esc(sweep(npk.note))}</p>` : ''}` : ''}
            </div>` : ''}`;
            wireStages();
            if (hostEl.classList.contains('is-drawn')) { requestAnimationFrame(() => fertHost.querySelectorAll('.cp-bar, .cp2-tot-row .tr span, .cp2-nut-row .have').forEach((el) => { el.style.transition = 'none'; el.style.transform = 'none'; })); }
        };
        const i0 = (i) => i === 0;
        // One word on the tab for the nutrient check: short beats over beats on target.
        const verdictWord = (check, els) => {
            const v = (check || []).map((c) => c.verdict).concat((els || []).filter((e) => !e.beneficial).map((e) => ({ right: 'ok', testok: 'ok', spray: 'ok', short: 'short', over: 'over' })[e.state] || 'unchecked'));
            if (!v.length) return '';
            if (v.includes('short')) return ' · short';
            if (v.includes('over')) return ' · over';
            return v.every((x) => x === 'ok' || x === 'unchecked') ? ' · on target' : '';
        };
        // The stage picker: the chart's bars and the rows both choose a
        // stage; the detail panel explains it.
        const wireStages = () => {
        const card = fertHost.querySelector('[data-cp2-stages]');
        if (card) {
            const g = programs[PROG] || programs[0];
            const paint = () => {
                const i = Math.max(0, Math.min(stages.length - 1, Number(card.dataset.sel) || 0));
                SEL = i;
                const st = Object.assign({}, stages[i] || {}, { fertilizer: (g.byStage || [])[i] || [] });
                card.querySelectorAll('[data-cp2-stage]').forEach((el) => el.classList.toggle('is-sel', Number(el.dataset.cp2Stage) === i));
                card.querySelectorAll('[data-cp2-lbl]').forEach((el) => el.classList.toggle('is-sel', Number(el.dataset.cp2Lbl) === i));
                const d = card.querySelector('[data-cp2-detail]');
                d.classList.add('is-swap');
                setTimeout(() => {
                    d.innerHTML = `
                        <div class="cp2-d-head"><span class="cp2-row-n">${i + 1}</span><span><b>${esc(st.stage || '')}</b>${st.days ? `<small><em class="cp2-days">${esc(st.days)}</em></small>` : (st.hint && st.hint !== 'n/a' ? `<small>${esc(st.hint)}</small>` : '')}</span>
                            <span class="cp2-d-nav"><button type="button" data-cp2-prev ${i === 0 ? 'disabled' : ''} aria-label="Previous stage">‹</button><button type="button" data-cp2-next ${i === stages.length - 1 ? 'disabled' : ''} aria-label="Next stage">›</button></span></div>
                        ${st.signs ? `<p class="cp2-d-signs">👁️ ${esc(sweep(st.signs))}</p>` : ''}
                        ${(st.fertilizer || []).length ? `<div class="cp2-d-fert">${(st.fertilizer || []).map((x) => `<div class="cp2-d-app"><i style="background:${colour(x.product || 'Fertilizer')}"></i><span><b>${esc(bagAmt(x.totalBags))} ${esc(x.product || '')}</b> <small>(${esc(Number(x.bagsPerHa) >= 1 ? trimN(x.bagsPerHa) : bagAmt(x.bagsPerHa))}/ha)</small>${x.purpose ? `<em>${esc(sweep(x.purpose))}</em>` : ''}</span></div>`).join('')}</div>` : `<p class="cp2-dim">No fertilizer at this stage.</p>`}
                        ${st.observe ? `<div class="cp2-d-obs"><span class="is-obs">🔎 Look for</span><p>${esc(sweep(st.observe))}</p></div>` : ''}
                        ${st.intervene ? `<div class="cp2-d-obs"><span class="is-act">🛠️ What to do</span><p>${esc(sweep(st.intervene))}</p></div>` : ''}
                        ${(rec.foliars || []).filter((f) => sameStage(f.stage, st.stage)).map((f) => `<div class="cp2-d-fol"><span>🍃 Foliar spray</span><p><b>${esc(foliarName(f.product))}</b>${f.why ? '. ' + esc(sweep(f.why)) : ''}</p></div>`).join('')}`;
                    d.classList.remove('is-swap');
                }, d.innerHTML ? 140 : 0);
            };
            card.addEventListener('click', (e) => {
                const tab = e.target.closest('[data-cp3-prog]');
                if (tab) { PROG = Number(tab.dataset.cp3Prog) || 0; paintFert(); return; }
                const b = e.target.closest('[data-cp2-stage]');
                if (b) { card.dataset.sel = b.dataset.cp2Stage; paint(); return; }
                if (e.target.closest('[data-cp2-prev]')) { card.dataset.sel = String(Math.max(0, Number(card.dataset.sel) - 1)); paint(); return; }
                if (e.target.closest('[data-cp2-next]')) { card.dataset.sel = String(Math.min(stages.length - 1, Number(card.dataset.sel) + 1)); paint(); }
            });
            paint();
        }
        };
        paintFert();
    }

    function finishDraw(hostEl, item, mode, quiet) {
        hostEl.hidden = false;
        if (!quiet) {
            requestAnimationFrame(() => requestAnimationFrame(() => hostEl.classList.add('is-drawn')));
            hostEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else if (hostEl.id === 'cpReport') {
            hostEl.classList.add('is-drawn');
        }

        hostEl.querySelector('[data-cp-attach]').addEventListener('click', () => { if (item.savedId) window.location.href = U.anee + '?analysis=' + item.savedId; });
        hostEl.querySelector('[data-cp-again]')?.addEventListener('click', () => { VIEW_MODE = null; closeView(); wizardBack(); });
        hostEl.querySelector('[data-cp-delete]').addEventListener('click', async () => {
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Delete this protocol?', message: 'Only the report is deleted. Credits already used are not returned.', confirmText: 'Delete', danger: true })
                : confirm('Delete this protocol?');
            if (!ok) return;
            try {
                const res = await api(U.del(item.savedId), { method: 'DELETE' });
                toast(res.message);
                VIEW_MODE = null;
                closeView();
                $id('cpReport').hidden = true;
                loadSaved().catch(() => {});
                if (mode === 'fresh') wizardBack();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    /* ---------------- saved ---------------- */
    let ROWS = [];
    let META_ID = null;
    async function loadSaved() {
        const res = await api(U.list + '?_=' + Date.now(), { method: 'GET' });
        ROWS = res.data.rows || [];
        $id('cpSavedList').innerHTML = ROWS.map((r) => `
            <button type="button" class="wtp-saved" data-saved="${r.id}">
                <span class="grow min-w-0"><b>${esc(r.title)}</b><small>${r.description ? esc(r.description) + ' · ' : ''}${esc(r.at)} · ${r.credits} credits</small>${window.userTags ? window.userTags.chips(r.tags) : ''}</span>
                <span role="button" tabindex="0" class="wtp-pen" data-meta="${r.id}" title="Edit name and description" aria-label="Edit ${esc(r.title)}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:.85rem;height:.85rem"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </span>
                <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>`).join('');
        $id('cpSavedEmpty').classList.toggle('hidden', ROWS.length > 0);
    }
    document.addEventListener('click', async (e) => {
        const saveBtn = e.target.closest('#cpMetaSave');
        if (saveBtn && META_ID !== null) {
            saveBtn.disabled = true;
            try {
                const res = await api(META_URL, { method: 'POST', body: { id: META_ID, title: $id('cpMetaTitle').value.trim(), description: $id('cpMetaDesc').value.trim(), tags: window.userTags ? window.userTags.value($id('cpMetaTags')) : [] } });
                toast(res.message);
                closeSheet('cpMetaSheet');
                loadSaved().catch(() => {});
            } catch (err) { toast(err.message, 'error'); }
            finally { saveBtn.disabled = false; }
            return;
        }
        const pen = e.target.closest('[data-meta]');
        if (pen && pen.closest('#cpSavedList')) {
            e.stopPropagation();
            const r = ROWS.find((x) => String(x.id) === pen.getAttribute('data-meta'));
            if (!r) return;
            META_ID = r.id;
            $id('cpMetaTitle').value = r.title || '';
            $id('cpMetaDesc').value = r.description || '';
            if (window.userTags) window.userTags.set($id('cpMetaTags'), r.tags || []);
            openSheet('cpMetaSheet');
            return;
        }
        const b = e.target.closest('[data-saved]');
        if (!b) return;
        try {
            const res = await api(U.one(b.getAttribute('data-saved')), { method: 'GET' });
            openView({ report: res.data.report, params: res.data.params, charged: res.data.credits, savedId: res.data.id }, 'saved');
        } catch (err) { toast(err.message, 'error'); }
    });



    const tab = (which) => {
        $id('cpGen').classList.toggle('hidden', which !== 'gen');
        $id('cpSavedPane').classList.toggle('hidden', which !== 'saved');
        $id('cpTabGen').classList.toggle('is-on', which === 'gen');
        $id('cpTabSaved').classList.toggle('is-on', which === 'saved');
        if (which === 'saved') loadSaved().catch((err) => toast(err.message, 'error'));
    };
    $id('cpTabGen').addEventListener('click', () => tab('gen'));
    $id('cpTabSaved').addEventListener('click', () => tab('saved'));

    // app.js (a module) defines window.api after this page's script has run.
    if (window.api) boot();
    else window.addEventListener('load', boot, { once: true });
})();
</script>
@endsection

@push('sheets')
@include('partials.user-tags')
<div class="sheet hidden" id="cpMetaSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Edit this protocol</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="cpMetaTitle">Name</label>
            <input type="text" id="cpMetaTitle" class="form-input" maxlength="191">
        </div>
        <div>
            <label class="form-label" for="cpMetaDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="cpMetaDesc" class="form-textarea" rows="3" maxlength="2000"></textarea>
        </div>
        <div>
            <span class="form-label">Tags <span class="text-gray-400 font-normal">(optional)</span></span>
            <div class="ut-mount" id="cpMetaTags"></div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-primary w-full" id="cpMetaSave">Save changes</button>
    </div>
</div>
@endpush

@push('head')
<style>
    .wtp-pen { flex: none; width: 1.6rem; height: 1.6rem; border-radius: .45rem; display: inline-flex;
        align-items: center; justify-content: center; color: var(--color-gray-400); }
    .wtp-pen:hover { color: var(--color-brand-700); background: var(--color-brand-50); }
    html.dark .wtp-pen:hover { background: rgb(107 159 61 / .18); color: #a5c97e; }
    html.cq-lock { overflow: hidden; }
</style>
@endpush
