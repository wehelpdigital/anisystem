@extends('layouts.app')
@section('title', 'What to Plant')
{{-- A tool page: no bottom tab bar on a phone and no footer (2026-10-15, the owner). --}}
@section('body-class', 'hide-tabbar no-footer')
@section('page-title', 'What to Plant')
@section('page-subtitle', 'Crops that fit your field')

@section('back', \App\Support\BackTo::url(route('app.dashboard')))
{{-- Back is the dashboard, or wherever the door that opened this page said
     it stood (?from=, App\Support\BackTo). It used to be history.back()
     whenever the referrer was on this site, which after a reload, a
     redirect or a page's own link went back to this page itself. --}}


@section('content')
<style>
    /* ---- WHAT TO PLANT -------------------------------------------------
       The sister of When to Plant: same wizard walk, same veil, same
       shelf — but the answer is a ranked list of crops, not a window.
       The wtp-* dress is copied whole so the two read as one family. */
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
    .wtp-dots { display: flex; gap: .35rem; justify-content: center; margin: 1rem 0 .2rem; }
    .wtp-dot { width: .5rem; height: .5rem; border-radius: 999px; background: var(--color-gray-200);
        transition: all .28s cubic-bezier(.22,1,.36,1); }
    .wtp-dot.is-on { width: 1.4rem; background: var(--color-brand-600); }
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

    .wtp-wait { position: fixed; inset: 0; z-index: 110; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: .6rem; padding: 2rem 1.2rem; text-align: center;
        background: rgb(250 250 248 / .98); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        opacity: 0; visibility: hidden; pointer-events: none;
        transition: opacity .28s cubic-bezier(.22,1,.36,1), visibility .28s; }
    .wtp-wait.is-on { opacity: 1; visibility: visible; pointer-events: auto; }
    .wtp-wait .w-spin { width: 2.4rem; height: 2.4rem; border-radius: 999px; border: 3px solid var(--color-brand-200);
        border-top-color: var(--color-brand-600); animation: wtpSpin .8s linear infinite; }
    @keyframes wtpSpin { to { transform: rotate(360deg); } }
    .wtp-wait p { font-size: .85rem; color: var(--color-gray-500); max-width: 22rem; }
    .wtp-wait .w-stay { font-size: .8rem; font-weight: 700; color: #b45309; }
    html.dark .wtp-wait { background: rgb(13 17 9 / .98); }
    html.dark .wtp-wait p b { color: #e8efe1; }
    html.dark .wtp-wait .w-stay { color: #fbbf24; }

    /* ---- THE REPORT ---- */
    .wtp-report { display: grid; gap: .9rem; }
    /* A grid item's automatic minimum is its content's width — the timeline's
       scroll box would widen the whole report without this. */
    .wtp-report > * { min-width: 0; max-width: 100%; }
    .wtp-hero { border-radius: 1.1rem; padding: 1.1rem 1.2rem; color: #fff;
        background: linear-gradient(130deg, #4a7c2a, #2d5016 70%); }
    .wtp-hero h2 { font-size: 1.15rem; font-weight: 800; margin-bottom: .15rem; }
    .wtp-hero .h-win { font-size: 1.5rem; font-weight: 800; letter-spacing: .01em; }
    .wtp-hero .h-why { font-size: .84rem; opacity: .92; line-height: 1.55; margin-top: .45rem; }
    .wtp-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .6rem; }
    .wtp-chip { font-size: .68rem; font-weight: 700; padding: .18rem .55rem; border-radius: 999px;
        background: rgb(255 255 255 / .18); }

    .wtp-card { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        padding: 1rem 1.1rem; }
    .wtp-card h3 { font-weight: 800; font-size: .92rem; color: var(--color-gray-900); margin-bottom: .6rem; }

    /* The ranked shelf: one row per crop, a score bar that grows. */
    .wp-rec { padding: .7rem 0; border-bottom: 1px solid var(--color-gray-100); min-width: 0; overflow: hidden;
        opacity: 0; transform: translateY(6px); transition: all .45s cubic-bezier(.22,1,.36,1); }
    .wp-rec:last-child { border-bottom: 0; padding-bottom: .2rem; }
    .wtp-report.is-drawn .wp-rec { opacity: 1; transform: none; }
    .wp-rec-top { display: flex; align-items: baseline; gap: .5rem; }
    .wp-rank { flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex;
        align-items: center; justify-content: center; font-size: .74rem; font-weight: 800;
        background: var(--color-brand-100); color: var(--color-brand-800); align-self: center; }
    .wp-rec-top b { font-size: .92rem; color: var(--color-gray-900); }
    .wp-cat { font-size: .64rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase;
        padding: .12rem .45rem; border-radius: 999px; background: var(--color-gray-100); color: var(--color-gray-500); }
    .wp-win { margin-left: auto; font-size: .72rem; font-weight: 700; color: var(--color-brand-700); white-space: nowrap; }
    .wp-track { height: 7px; border-radius: 999px; background: var(--color-gray-100); margin: .4rem 0 .35rem; overflow: hidden; }
    .wp-fill { display: block; height: 100%; border-radius: 999px; background: var(--color-brand-500);
        transform-origin: left; transform: scaleX(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .wp-fill { transform: scaleX(1); }
    .wp-why { font-size: .8rem; color: var(--color-gray-600); line-height: 1.55; }
    .wp-watch { font-size: .74rem; color: #92610e; margin-top: .15rem; }
    /* A crop's market line (2026-10-27). */
    .wp-mk { display: inline-flex; flex-wrap: wrap; gap: .2rem; align-items: center; }
    .wp-mk a { font-weight: 800; text-decoration: underline; text-underline-offset: 2px; }
    /* The varieties on record that fit, under each crop (2026-10-14). */
    .wp-vars { margin-top: .5rem; padding: .5rem .6rem; border-radius: .8rem; background: #f4f9ee; border: 1px solid #e1edd3; }
    .wp-vars-h { display: block; font-size: .64rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #4a7c2a; }
    .wp-vars-l { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .35rem; }
    .wp-vars-l a { display: inline-flex; flex-direction: column; padding: .3rem .55rem; border-radius: .6rem; background: #fff; border: 1px solid #dbe8cc;
        font-size: .74rem; font-weight: 800; color: #1f3410; text-decoration: none; transition: border-color .28s cubic-bezier(.22,1,.36,1); }
    .wp-vars-l a:hover { border-color: #86b556; }
    .wp-vars-l a small { font-size: .64rem; font-weight: 600; color: var(--color-gray-500); }
    .wp-vars-a { display: flex; flex-wrap: wrap; gap: .3rem .9rem; margin-top: .4rem; }
    .wp-vars-a a { font-size: .72rem; font-weight: 800; color: var(--color-brand-700); text-decoration: underline; text-underline-offset: 3px; }
    .h-vars { margin-top: .5rem; font-size: .78rem; opacity: .95; }
    .h-vars a { color: inherit; font-weight: 800; text-decoration: underline; text-underline-offset: 3px; }
    html.dark .wp-vars { background: #1a2414; border-color: #2b3a1c; }
    html.dark .wp-vars-h { color: #a5c97e; }
    html.dark .wp-vars-l a { background: #151b12; border-color: #2b3a1c; color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) { .wp-vars-l a { transition: none; } }
    html.dark .wp-rec { border-color: #222b1a; }
    html.dark .wp-rec-top b { color: #e8efe1; }
    html.dark .wp-cat { background: #222b1a; color: #93a684; }
    html.dark .wp-track { background: #222b1a; }
    html.dark .wp-why { color: #b7c2ad; }
    html.dark .wp-watch { color: #e0b95c; }

    .wtp-win-row { display: block; padding: .6rem .7rem; border-radius: .7rem; margin-bottom: .45rem;
        font-size: .84rem; line-height: 1.55; border: 1px solid; }
    .wtp-win-row b { margin-right: .3rem; }
    .wtp-win-row.is-no { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
    html.dark .wtp-win-row.is-no { background: #2a1414; border-color: #4c1d1d; color: #fca5a5; }

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

    /* Full screen when it lands (the sisters' view): the tabs and the
       wizard are out of sight until the farmer closes it. */
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
    .wtp-shelf-search { position: relative; padding: .7rem .8rem; border-bottom: 1px solid var(--color-gray-100); }
    .wtp-shelf-search svg { position: absolute; left: 1.55rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: var(--color-gray-400); pointer-events: none; }
    .wtp-shelf-search .form-input { padding-left: 2.3rem; }
    .wtp-shelf-more { text-align: center; font-size: .74rem; color: var(--color-gray-400); padding: .8rem; }
    html.dark .wtp-shelf-search { border-color: #222b1a; }
    .wp-qh { font-size: .84rem; font-weight: 800; color: var(--color-gray-800); margin-bottom: .45rem; }
    .wp-qh small { display: block; font-weight: 500; font-size: .7rem; color: var(--color-gray-400); }
    .wp-ph-in { margin-top: .5rem; }
    html.dark .wp-qh { color: #e8efe1; }
    .wp-loc-country { margin-bottom: .8rem; }
    .wp-loc-country .form-label { margin-bottom: .3rem; }
    /* The lot (2026-10-16, the owner): what it answered is not asked again.
       A [hidden] block loses to its own display rule without these. */
    .wp-loc-country[hidden], .wtp-lotq[hidden], .wtp-prob[hidden] { display: none; }
    .wtp-lotnote { display: flex; align-items: flex-start; gap: .5rem; margin: 0 0 .8rem; padding: .55rem .75rem; border-radius: .85rem; font-size: .8rem; line-height: 1.5;
        color: #24400f; background: #f3f8ec; border: 1px solid #d5e7c0; animation: wtpLotIn .3s cubic-bezier(.22,1,.36,1) both; }
    .wtp-lotnote[hidden] { display: none; }
    .wtp-lotnote svg { flex: none; width: 1rem; height: 1rem; margin-top: .15rem; color: #4a7c2a; }
    @keyframes wtpLotIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }
    .wtp-lotline { display: block; margin-top: .35rem; }
    .wtp-lotline button { font-weight: 800; color: #3d6823; text-decoration: underline; text-underline-offset: 3px; }
    /* A step the lot answered whole has no dot: it folds away. */
    .wtp-dot.is-skip { width: 0; margin-left: -.35rem; opacity: 0; }
    html.dark .wtp-lotnote { color: #cfe6b8; background: #17220f; border-color: #2b3a1c; }
    html.dark .wtp-lotnote svg { color: #a5c97e; }
    html.dark .wtp-lotline button { color: #a5c97e; }
    @media (prefers-reduced-motion: reduce) { .wtp-lotnote { animation: none; } }

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


    /* ---- what matters: a list the farmer reorders ---- */
    .wp-prio { display: grid; gap: .45rem; }
    .wp-prio-row { display: flex; align-items: center; gap: .6rem; padding: .6rem .7rem; border-radius: .9rem;
        border: 1.5px solid var(--color-gray-200); background: var(--color-white); cursor: grab;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .2s, background .2s, opacity .2s; }
    .wp-prio-row.is-drag { opacity: .45; }
    .wp-prio-row.is-over { border-color: var(--color-brand-500); background: var(--color-brand-50); }
    .wp-prio-n { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        font-size: .74rem; font-weight: 800; background: var(--color-brand-100); color: var(--color-brand-800); }
    .wp-prio-row:first-child .wp-prio-n { background: var(--color-brand-600); color: #fff; }
    .wp-prio-t { flex: 1 1 auto; min-width: 0; font-size: .86rem; font-weight: 700; color: var(--color-gray-800); line-height: 1.3; }
    .wp-prio-t small { display: block; font-weight: 500; font-size: .7rem; color: var(--color-gray-500); }
    .wp-prio-b { flex: none; display: flex; flex-direction: column; gap: .15rem; }
    .wp-prio-b button { width: 1.7rem; height: 1.35rem; border-radius: .45rem; border: 1px solid var(--color-gray-200); background: var(--color-gray-50);
        color: var(--color-gray-600); font-size: .7rem; line-height: 1; cursor: pointer; }
    .wp-prio-b button:disabled { opacity: .3; cursor: default; }
    html.dark .wp-prio-row { background: #151b12; border-color: #2b3a1c; }
    html.dark .wp-prio-row.is-over { background: #22301a; border-color: #6b9f3d; }
    html.dark .wp-prio-t { color: #d5e3c5; }
    html.dark .wp-prio-b button { background: #1c2416; border-color: #2b3a1c; color: #b7c2ad; }

    /* ---- crops in mind: a tag that opens the book, chips for the picks ---- */
    .crop-tag { display: flex; align-items: center; gap: .5rem; width: 100%; padding: .65rem .8rem; border-radius: .8rem; cursor: pointer; text-align: left;
        border: 1.5px solid var(--color-gray-200); background: var(--color-white); transition: border-color .2s, background .2s; }
    .crop-tag:hover { border-color: var(--color-brand-300); background: var(--color-brand-50); }
    .crop-tag-e { font-size: 1.1rem; line-height: 1; flex: none; }
    .crop-tag-t { flex: 1 1 auto; min-width: 0; font-size: .9rem; font-weight: 700; color: #3d6823; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .crop-tag-c { width: 1rem; height: 1rem; flex: none; color: var(--color-gray-400); }
    html.dark .crop-tag { background: #1c2416; border-color: #2b3a1c; }
    html.dark .crop-tag-t { color: #a5c97e; }
    .wp-asked { display: flex; flex-wrap: wrap; gap: .4rem; margin: .6rem 0 .2rem; }
    .wp-asked:empty { margin: 0; }
    .wp-asked span { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .4rem .3rem .6rem; border-radius: 999px;
        background: var(--color-brand-50); border: 1px solid var(--color-brand-200); color: var(--color-brand-800); font-size: .78rem; font-weight: 700; }
    .wp-asked button { width: 1.2rem; height: 1.2rem; border-radius: 999px; background: rgb(0 0 0 / .08); font-size: .7rem; line-height: 1; cursor: pointer; }
    html.dark .wp-asked span { background: #22301a; border-color: #2b3a1c; color: #cfe6b8; }
    .crop-search { position: relative; margin-bottom: .6rem; }
    .crop-search svg { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); width: 1.05rem; height: 1.05rem; color: var(--color-gray-400); pointer-events: none; }
    .crop-search .form-input { padding-left: 2.4rem; }
    .crop-group-h { font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); margin: .8rem 0 .25rem; }
    .crop-row { display: flex; align-items: center; gap: .65rem; width: 100%; text-align: left; padding: .5rem .6rem; border-radius: .7rem; cursor: pointer;
        border: 1.5px solid transparent; transition: background .2s, border-color .2s; }
    .crop-row:hover { background: var(--color-brand-50); }
    .crop-row.is-on { background: var(--color-brand-50); border-color: var(--color-brand-500); }
    .crop-row-e { font-size: 1.25rem; line-height: 1; flex: none; }
    .crop-row-t { min-width: 0; flex: 1 1 auto; }
    .crop-row-t b { display: block; font-size: .875rem; font-weight: 700; color: var(--color-gray-900); }
    .crop-row-t small { display: block; font-size: .7rem; color: var(--color-gray-400); }
    .crop-row-k { flex: none; font-size: .9rem; color: var(--color-brand-700); opacity: 0; transition: opacity .2s; }
    .crop-row.is-on .crop-row-k { opacity: 1; }
    .crop-none { font-size: .8rem; color: var(--color-gray-400); text-align: center; padding: 1rem 0; }
    html.dark .crop-row:hover, html.dark .crop-row.is-on { background: #22301a; }
    html.dark .crop-row-t b { color: #e8efe1; }

    /* ---- the ranking rows: the harvest, its risk, the six fits ---- */
    .wp-ask { font-size: .62rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; padding: .12rem .45rem; border-radius: 999px;
        background: #fef3c7; color: #92400e; white-space: nowrap; }
    .wp-hv { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem .6rem; margin-top: .3rem; font-size: .74rem; color: var(--color-gray-600); }
    .wp-hr { display: inline-flex; align-items: center; gap: .3rem; padding: .12rem .5rem; border-radius: 999px; font-weight: 700; font-size: .7rem; }
    .wp-hr.is-low { background: #dcfce7; color: #166534; }
    .wp-hr.is-mid { background: #fef3c7; color: #92400e; }
    .wp-hr.is-high { background: #fee2e2; color: #991b1b; }
    .wp-hr-note { font-size: .72rem; color: var(--color-gray-500); margin-top: .1rem; line-height: 1.45; }
    .wp-fit { display: flex; gap: .35rem; margin-top: .45rem; min-width: 0; }
    .wp-fit-k { flex: 1 1 0; min-width: 0; display: flex; flex-direction: column; align-items: center; gap: .2rem; overflow: hidden; }
    .wp-fit-k i { display: block; width: 100%; max-width: 2.4rem; height: 1.7rem; border-radius: .3rem; background: var(--color-gray-100); position: relative; overflow: hidden; }
    .wp-fit-k i::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: var(--v, 0%); background: var(--color-brand-500); border-radius: .3rem .3rem 0 0;
        transform: scaleY(0); transform-origin: bottom; transition: transform .6s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .wp-fit-k i::after { transform: scaleY(1); }
    .wp-fit-k:first-child i::after { background: var(--color-brand-700); }
    .wp-fit-k small { font-size: .58rem; font-weight: 700; color: var(--color-gray-500); white-space: nowrap; max-width: 100%; overflow: hidden; text-overflow: ellipsis; }
    .wp-fit-k b { font-size: .6rem; color: var(--color-gray-700); }
    .wp-prio-line { font-size: .74rem; color: var(--color-gray-500); margin: -.3rem 0 .6rem; line-height: 1.5; }
    .wp-prio-line b { color: var(--color-brand-700); }
    html.dark .wp-hv { color: #b7c2ad; }
    html.dark .wp-hr.is-low { background: #14301c; color: #86efac; }
    html.dark .wp-hr.is-mid { background: #3a2a0a; color: #fcd34d; }
    html.dark .wp-hr.is-high { background: #3b1414; color: #fca5a5; }
    html.dark .wp-ask { background: #3a2a0a; color: #fcd34d; }
    html.dark .wp-fit-k i { background: #222b1a; }
    html.dark .wp-fit-k small { color: #93a684; }
    html.dark .wp-fit-k b { color: #d5e3c5; }
    html.dark .wp-prio-line { color: #93a684; }
    html.dark .wp-prio-line b { color: #a5c97e; }

    /* ---- from planting to harvest, against the year's risks ---- */
    /* Slides by finger or by mouse-drag; no scrollbar is drawn, and the last
       row keeps clear of where one would sit. */
    .wp-tl { position: relative; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; -ms-overflow-style: none; cursor: grab; }
    .wp-tl::-webkit-scrollbar { display: none; }
    .wp-tl.is-dragging { cursor: grabbing; user-select: none; }
    .wp-tl-in { position: relative; min-width: 31rem; padding-bottom: .8rem; }
    .wp-tl-bg { position: absolute; top: 0; bottom: .8rem; left: 7.4rem; right: 0; display: grid; grid-template-columns: repeat(12, 1fr); gap: 2px; pointer-events: none; }
    .wp-tl-bg i { display: block; border-radius: .3rem; background: rgb(74 124 42 / .07); }
    .wp-tl-bg i.is-mid { background: rgb(240 176 74 / .22); }
    .wp-tl-bg i.is-high { background: rgb(239 118 118 / .25); }
    .wp-tl-row { position: relative; display: grid; grid-template-columns: 7.4rem repeat(12, 1fr); gap: 2px; align-items: center; min-height: 2.1rem; }
    .wp-tl-row + .wp-tl-row { border-top: 1px solid rgb(0 0 0 / .07); }
    .wp-tl-row.is-head { min-height: 2.2rem; }
    .wp-tl-row.is-head + .wp-tl-row { border-top-color: rgb(0 0 0 / .12); }
    .wp-tl-row.is-head .wp-tl-lbl { display: block; }
    /* The label: the rank in its own narrow column, the name beside it and
       free to wrap, the local name and the days under it in smaller type,
       all lined up with the name, never with the number. */
    .wp-tl-lbl { display: flex; align-items: flex-start; gap: .3rem; padding: .3rem .45rem .3rem 0; font-size: .7rem; font-weight: 700; color: var(--color-gray-700); min-width: 0; }
    .wp-tl-lbl .wp-tl-n { flex: none; width: 1.15rem; text-align: right; font-style: normal; font-weight: 800; color: var(--color-gray-500); line-height: 1.25; }
    .wp-tl-lbl .wp-tl-t { flex: 1 1 auto; min-width: 0; line-height: 1.25; }
    .wp-tl-lbl b { display: block; font-weight: 700; overflow-wrap: anywhere; }
    .wp-tl-lbl small { display: block; font-weight: 500; font-size: .6rem; color: var(--color-gray-400); line-height: 1.3; overflow-wrap: anywhere; }
    .wp-tl-lbl small.wp-tl-alt { font-size: .62rem; color: var(--color-gray-500); }
    .wp-tl-bar.is-surprise { background: repeating-linear-gradient(135deg, #c9a4e8 0 6px, #b48ad9 6px 12px); }
    .wp-tl-legend i.is-surprise { width: 1.2rem; height: .55rem; border-radius: 999px; background: repeating-linear-gradient(135deg, #c9a4e8 0 4px, #b48ad9 4px 8px); }
    html.dark .wp-tl-row + .wp-tl-row { border-top-color: rgb(255 255 255 / .07); }
    html.dark .wp-tl-row.is-head + .wp-tl-row { border-top-color: rgb(255 255 255 / .14); }
    html.dark .wp-tl-lbl .wp-tl-n { color: #93a684; }
    html.dark .wp-tl-lbl small.wp-tl-alt { color: #a8bd93; }

    /* ---- surprise me: the unusual crops the ground argues for ---- */
    .wp-sur { padding: .7rem .8rem; border-radius: .9rem; margin-bottom: .5rem; border: 1px solid #e9d5ff;
        background: linear-gradient(120deg, #faf5ff, #f5f3ff); opacity: 0; transform: translateY(6px); transition: all .45s cubic-bezier(.22,1,.36,1); }
    .wp-sur:last-child { margin-bottom: 0; }
    .wtp-report.is-drawn .wp-sur { opacity: 1; transform: none; }
    .wp-sur-top { display: flex; align-items: baseline; gap: .5rem; flex-wrap: wrap; }
    .wp-sur-top b { font-size: .9rem; color: #4c1d95; }
    .wp-sur-why { font-size: .8rem; color: #4b5563; line-height: 1.55; margin-top: .25rem; }
    html.dark .wp-sur { background: linear-gradient(120deg, #1e1530, #1a1628); border-color: #3b2a5e; }
    html.dark .wp-sur-top b { color: #d8c5f7; }
    html.dark .wp-sur-why { color: #b7c2ad; }

    /* ---- families left out: toggles, never all of them ---- */
    .wp-left-out { font-size: .74rem; color: var(--color-gray-500); margin: -.3rem 0 .6rem; }
    html.dark .wp-left-out { color: #93a684; }
    .wp-tl-m { text-align: center; font-size: .58rem; font-weight: 700; color: var(--color-gray-500); line-height: 1.2; }
    .wp-tl-m i { display: block; font-style: normal; font-size: .8rem; }
    .wp-tl-bar { position: relative; height: .95rem; border-radius: 999px; background: var(--color-brand-400);
        transform: scaleX(0); transform-origin: left; transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .wp-tl-bar { transform: scaleX(1); }
    .wp-tl-bar.is-asked { background: repeating-linear-gradient(135deg, #a3c98a 0 6px, #86b26a 6px 12px); }
    .wp-tl-bar.is-more::after { content: '›'; position: absolute; right: .3rem; top: -.2rem; color: #fff; font-weight: 800; font-size: .8rem; }
    .wp-tl-dot { position: absolute; right: -.15rem; top: 50%; width: 1.05rem; height: 1.05rem; border-radius: 999px; transform: translateY(-50%);
        border: 2px solid #fff; box-shadow: 0 1px 3px rgb(0 0 0 / .25); }
    .wp-tl-dot.is-low { background: #22c55e; } .wp-tl-dot.is-mid { background: #f0b04a; } .wp-tl-dot.is-high { background: #ef4444; }
    .wp-tl-legend { display: flex; flex-wrap: wrap; gap: .3rem .8rem; margin-top: .6rem; font-size: .68rem; font-weight: 700; color: var(--color-gray-600); }
    .wp-tl-legend span { display: inline-flex; align-items: center; gap: .3rem; }
    .wp-tl-legend i { display: inline-block; width: .75rem; height: .75rem; border-radius: 999px; }
    .wp-tl-legend i.is-bar { width: 1.2rem; height: .55rem; background: var(--color-brand-400); }
    .wp-tl-legend i.is-tint { border-radius: .2rem; background: rgb(240 176 74 / .35); }
    html.dark .wp-tl-lbl { color: #d5e3c5; }
    html.dark .wp-tl-lbl b { color: #d5e3c5; }
    html.dark .wp-tl-m { color: #93a684; }
    html.dark .wp-tl-bg i { background: rgb(143 201 106 / .08); }
    html.dark .wp-tl-bg i.is-mid { background: rgb(240 176 74 / .26); }
    html.dark .wp-tl-bg i.is-high { background: rgb(239 118 118 / .34); }
    html.dark .wp-tl-legend { color: #b7c2ad; }
    html.dark .wp-tl-dot { border-color: #151b12; }

    @media (max-width: 639px) {
        .wtp-hero { padding: .9rem 1rem; }
        .wtp-hero .h-win { font-size: 1.15rem; }
        .wtp-card { padding: .8rem .85rem; }
        .wtp-choices.is-two { grid-template-columns: 1fr; }
        .wp-win { display: none; }
    }

    @media (prefers-reduced-motion: reduce) {
        .wtp-step.is-on { animation: none; }
        .wtp-run { animation: none; }
        .wp-rec, .wp-fill, .wtp-dot, .wp-tl-bar, .wp-fit-k i::after, .wp-sur { transition: none; transform: none; opacity: 1; }
        .wp-prio-row, .crop-row, .crop-tag { transition: none; }
        .wtp-wait, .q-body, .q-c, .q-hint, .wtp-prob { transition: none; }
    }
</style>

<div class="max-w-2xl mx-auto">
    <div class="wtp-tabs" role="tablist">
        <button type="button" class="wtp-tab is-on" id="wpTabGen">Generate</button>
        <button type="button" class="wtp-tab" id="wpTabSaved">Saved</button>
    </div>

    <div id="wpGen" class="an-wait">
        <div class="wtp-quote" id="wpQuote" hidden>
            <button type="button" class="q-head" id="wpQuoteHead" aria-expanded="true">
                <span class="q-ico">🔎</span>
                <span class="q-title">Before you run one</span>
                <span class="q-hint" id="wpQuoteHint"></span>
                <svg class="q-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="q-body">
                <div class="q-body-in">
                    <div class="q-card" id="wpQuoteCost"></div>
                    <div class="q-card">Anee checks your land, water, budget and market against the crops grown in <span id="wpQuoteCountry">{{ \App\Support\Region::ph() ? 'the Philippines' : \App\Support\Region::name() }}</span>. She ranks the ones that fit your field best, with when each is harvested. Treat it as a guide.</div>
                </div>
            </div>
        </div>

        <div class="card p-5 wtp-wiz" id="wpWiz">
            {{-- Step 1: the lot (2026-10-16, the owner), picked from the
                 farm's lots (partials/farm-lots) or added. Its town and
                 province are where the weather and the markets are read, and
                 its size, soil, water and last season are not asked again on
                 the steps after it. The field's country is the farmer's own
                 (their account's): the picker stays for a farm abroad and is
                 out of sight for one at home. It decides whose climate, crops
                 and agencies the analysis reads. --}}
            <section class="wtp-step is-on" data-step="0">
                <p class="wtp-q">Which lot is it for?</p>
                <p class="wtp-sub">Pick the lot, or add it. Its place, size, soil and water come with it, so they are not asked again.</p>
                <div class="wp-loc-country" @if (\App\Support\Region::ph()) hidden @endif>
                    <label class="form-label">Country</label>
                    @include('partials.country-pick', ['id' => 'wpCountry', 'name' => 'country', 'value' => \App\Support\Region::code()])
                </div>
                <div id="wpLots"></div>
                @include('partials.farm-lots')
            </section>
            {{-- Step 2: when they want to begin --}}
            <section class="wtp-step" data-step="1">
                <p class="wtp-q">When do you plan to start?</p>
                <p class="wtp-sub">The month you would prepare and plant. The list is built around it.</p>
                <button type="button" class="crop-tag" id="wpMonthBtn" aria-haspopup="dialog">
                    <span class="crop-tag-e">🗓️</span>
                    <span class="crop-tag-t is-none" id="wpMonthNow">Pick the month and year</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
                @include('partials.month-pick')
            </section>
            {{-- "Your field in detail" (2026-10-15) was a step here: the lot
                 carries those answers now (step 1). The steps below hide
                 each question the lot answered (data-lot), and a step left
                 with none (data-blocks-only) is skipped. --}}
            {{-- Step 3: the soil, the lot's once it said how the soil feels --}}
            <section class="wtp-step" data-step="2" data-blocks-only>
                <p class="wtp-q">What is the soil like?</p>
                <div class="wtp-lotq" data-lot="soil">
                    <p class="wtp-sub">Go by feel. No test needed.</p>
                    <div class="wtp-choices" id="wpSoils"></div>
                </div>
            </section>
            {{-- Step 4: the water, the lot's once it said where it comes from --}}
            <section class="wtp-step" data-step="3" data-blocks-only>
                <p class="wtp-q">What water does the field get?</p>
                <div class="wtp-lotq" data-lot="water">
                    <p class="wtp-sub">Water matters most here.</p>
                    <div class="wtp-choices" id="wpWaters"></div>
                </div>
            </section>
            {{-- Step 5: more about the ground -- pH, salt, the lay of the land.
                 Every one optional; each one moves the ranking. They are the
                 lot's once it answered them. How the water looks, the
                 elevation and the sun are only the lot's (2026-10-16, the
                 owner): legacy() hands them over, no step asks them. --}}
            <section class="wtp-step" data-step="4" data-blocks-only>
                <p class="wtp-q">More about the land</p>
                <p class="wtp-sub">Answer what you know and skip the rest. Each answer makes the list better.</p>
                <div class="wtp-lotq mb-4" data-lot="ph">
                    <p class="wp-qh">Soil pH <small>from a test kit, or by the signs</small></p>
                    <div class="wtp-choices" id="wpPhs"></div>
                    <div class="wp-ph-in" id="wpPhIn" hidden>
                        <label class="form-label text-xs" for="wpPhValue">Test result <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" id="wpPhValue" class="form-input" maxlength="24" autocomplete="off" placeholder="e.g. 5.8, or 5.5 to 6.2">
                    </div>
                </div>
                <div class="wtp-lotq mb-4" data-lot="extra">
                    <p class="wp-qh">Sodium, salt or acid sulfate? <small>pick all that apply</small></p>
                    <div class="wtp-choices" id="wpSoilExtras"></div>
                </div>
                <div class="wtp-lotq" data-lot="lay">
                    <p class="wp-qh">Slope of the land</p>
                    <div class="wtp-choices is-two" id="wpLays"></div>
                </div>
            </section>
            {{-- Step 6: more about the farm -- history, hands, money, market.
                 The last crop is the lot's once it answered it. --}}
            <section class="wtp-step" data-step="5">
                <p class="wtp-q">More about the farm</p>
                <p class="wtp-sub">Also optional. Past crops and your means show what is realistic.</p>
                <div class="wtp-lotq mb-3" data-lot="prevCrop">
                    <label class="form-label" for="wpPrevCrop">What grew there last? <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" id="wpPrevCrop" class="form-input" maxlength="120" placeholder="e.g. rice, then left idle">
                </div>
                <label class="form-label" for="wpGrewWell">What grew well before? <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" id="wpGrewWell" class="form-input" maxlength="160" placeholder="e.g. onions did well, tomatoes got blight">
                <p class="wp-qh mt-4">Workers and machines</p>
                <div class="wtp-choices" id="wpLabors"></div>
                <p class="wp-qh mt-4">Budget for inputs</p>
                <div class="wtp-choices" id="wpBudgets"></div>
                <p class="wp-qh mt-4">Where will you sell? <small>pick all that apply</small></p>
                <div class="wtp-choices" id="wpMarkets"></div>
            </section>
            {{-- "What problems does the land have?" was a step here: the lot
                 asks it now (2026-10-16, the owner), and legacy() turns its
                 answers into this page's problem keys (state.problems). --}}
            {{-- Step 7: the aim and the area (the lot's size, once it has one) --}}
            <section class="wtp-step" data-step="6">
                <p class="wtp-q">What is the harvest for?</p>
                <p class="wtp-sub">Crops to sell and crops for the family are not the same.</p>
                <div class="wtp-choices" id="wpAims"></div>
                <div class="wtp-lotq" data-lot="area">
                    <label class="form-label mt-4" for="wpArea">How big is the field? <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" id="wpArea" class="form-input" maxlength="60" placeholder="e.g. half a hectare, 800 sqm">
                </div>
                <label class="form-label mt-3" for="wpNotes">Anything else? <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="wpNotes" class="form-textarea" rows="2" maxlength="400" placeholder="e.g. thinking of ube, and my neighbor grows good onions"></textarea>
            </section>
            {{-- Step 8: what matters most -- a list the farmer reorders --}}
            <section class="wtp-step" data-step="7">
                <p class="wtp-q">What matters most to you?</p>
                <p class="wtp-sub">Put them in your order. The top one counts most.</p>
                <div class="wp-prio" id="wpPrio"></div>
            </section>
            {{-- Step 9: families the farmer would rather not plant -- never all of them --}}
            <section class="wtp-step" data-step="8">
                <p class="wtp-q">Anything you'd rather not plant?</p>
                <p class="wtp-sub">Optional. Pick crop groups to skip. At least one must stay.</p>
                <div class="wtp-choices" id="wpExcludes"></div>
            </section>
            {{-- Step 10: crops the farmer has in mind -- optional, each ranked honestly --}}
            <section class="wtp-step" data-step="9">
                <p class="wtp-q">Any crops you have in mind?</p>
                <p class="wtp-sub">Optional. Anee ranks each one with her own picks, even if it fits poorly.</p>
                <button type="button" class="crop-tag" id="wpCropBtn">
                    <span class="crop-tag-e">🌱</span>
                    <span class="crop-tag-t" id="wpCropNow">Add a crop from the list</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="wp-asked" id="wpAsked"></div>
                <label class="form-label mt-3" for="wpCropsOther">Crops not on the list <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" id="wpCropsOther" class="form-input" maxlength="160" placeholder="e.g. ampalaya, ube (use commas)">
            </section>
            {{-- Step 11: the decision --}}
            <section class="wtp-step" data-step="10">
                <p class="wtp-q">Ready to run it?</p>
                <p class="wtp-sub">Check your answers. Tap a row to change it.</p>
                <div id="wpReview"></div>
                @include('partials.run-review')
                <button type="button" class="wtp-run" id="wpRun">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c0-4 1-7 4-9M12 21c0-5-2-8-6-9m6 9V8m0 0c0-2.5 1.5-4 4-4 0 2.5-1.5 4-4 4zm0 0C12 5.5 10.5 4 6.5 4c0 2.5 1.5 4 5.5 4z"/></svg>
                    <span id="wpRunSays">Run the analysis</span>
                </button>
                <p class="text-xs text-gray-400 mt-2 text-center" id="wpRunFine"></p>
            </section>

            {{-- The wait: Anee's face at work, shared by every AI run. --}}
            @include('sm.partials.anee-wait')

            <div class="wtp-dots" id="wpDots"></div>
            <div class="wtp-nav" id="wpNav">
                <button type="button" class="btn btn-white flex-1" id="wpBack">Back</button>
                <button type="button" class="btn btn-primary flex-1" id="wpNext">Next</button>
            </div>
        </div>

        <div class="wtp-report mt-4" id="wpReport" hidden></div>
    </div>

    <div id="wpSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div class="wtp-shelf-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                <input type="search" id="wpSavedSearch" class="form-input" placeholder="Search your analyses" autocomplete="off" aria-label="Search saved analyses">
            </div>
            <div id="wpSavedList"></div>
            <div class="wtp-shelf-more" id="wpSavedMore" hidden>Loading more…</div>
            <div id="wpSavedEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900">Nothing saved yet</p>
                <p class="text-sm text-gray-400">Every finished analysis is saved here.</p>
            </div>
        </div>
        <div class="wtp-report mt-4" id="wpSavedReport" hidden></div>
    </div>

    <div class="va-view" id="wpView" hidden role="dialog" aria-modal="true" aria-label="What to plant analysis">
        <div class="va-view-bar">
            <b id="wpViewTitle">What to plant</b>
            <button type="button" class="va-view-x" id="wpViewX" aria-label="Close">✕</button>
        </div>
        <div class="va-view-body"><div class="wtp-report" id="wpViewReport"></div></div>
    </div>
</div>

{{-- The crop book, searchable, pick as many as you like (up to eight). --}}
<div class="sheet hidden" id="wpCropSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Choose your crops</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="crop-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" id="wpCropSearch" class="form-input" autocomplete="off" placeholder="{{ \App\Support\Region::t('cropSearch') }}">
        </div>
        <div id="wpCropList"></div>
        <p class="crop-none hidden" id="wpCropNone">No match. Type it under “Crops not on the list” instead.</p>
    </div>
</div>

<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const U = {
        options: '{{ route('whatp.options') }}',
        generate: '{{ route('whatp.generate') }}',
        list: '{{ route('whatp.list') }}',
        one: (id) => '{{ url('/app/what-to-plant/one') }}/' + id,
        job: (id) => '{{ url('/app/what-to-plant/job') }}/' + id,
        del: (id) => '{{ url('/app/what-to-plant') }}/' + id,
        anee: '{{ route('ai.index') }}',
        vary: '{{ route('vary.page') }}',
    };
    const WP_META_URL = '{{ route('whatp.meta') }}';
    const CAT_E = { 'Grain': '🌾', 'Vegetable': '🥬', 'Root crop': '🍠', 'Legume': '🫘', 'Fruit / tree': '🌳' };

    let OPT = null;
    const state = { location: '', startMonth: null, soil: null, water: null, aim: null, area: '', notes: '', problems: [], country: '', ph: 'unsure', phValue: '', soilExtra: [], waterLook: [], lay: null, elevation: null, sun: null, prevCrop: '', grewWell: '', labor: null, budget: null, market: [], priorities: [], cropsAsked: [], cropsOther: '', exclude: [], lot: null };
    const RULES = () => (window.ANEE_REGION_RULES || {});
    const rulesFor = (code) => RULES()[code] || RULES()['*'] || {};
    const countryName = (code) => (code === 'PH' ? 'the Philippines' : (rulesFor(code).name || code || ''));
    let step = 0;
    const STEPS = 11;
    /* The lot's answers (partials/farm-lots, FieldProfile::legacy) are the
       problems, how the water looks, the elevation and the sun, and fill the
       soil, the water, the pH and salt, the slope and the last crop, each
       unless the farmer has set it by hand. What the lot
       answered is hidden and keeps the lot's answer. LIT: the steps where a
       question still shown was filled in from the lot. */
    const TOUCH = {};
    let LIT = {};
    const andList = (a) => a.length < 2 ? (a[0] || '') : a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1];
    const stepEl = (n) => document.querySelector('#wpWiz .wtp-step[data-step="' + n + '"]');
    const lightUp = (hostId, attr, val) => document.querySelectorAll('#' + hostId + ' .wtp-choice').forEach((c) => {
        const k = c.getAttribute('data-' + attr);
        c.classList.toggle('is-on', Array.isArray(val) ? (val.length ? val.includes(k) : k === 'unsure') : k === val);
    });
    let AUTO_PREV = '';
    function applyProfile(answers, L) {
        if (!OPT) return '';
        const got = [];
        LIT = {};
        // blk: the lot block the answer sits in; a hidden one is not "filled in", it is the lot's.
        const one = (key, hostId, attr, val, word, at, blk) => {
            if (TOUCH[key]) return;
            state[key] = val;
            lightUp(hostId, attr, val);
            const on = Array.isArray(val) ? val.length > 0 : (val !== null && val !== 'unsure');
            if (on) { got.push(word); if (!lotHas(blk)) LIT[at] = true; }
        };
        one('soil', 'wpSoils', 'soil', L.soil && L.soil !== 'unsure' ? L.soil : null, 'the soil', 2, 'soil');
        one('water', 'wpWaters', 'water', L.water || null, 'the water', 3, 'water');
        one('ph', 'wpPhs', 'ph', L.phLevel || 'unsure', 'the pH', 4, 'ph');
        if (!TOUCH.phValue) {
            const v = L.phLow != null ? (L.phHigh != null && L.phHigh !== L.phLow ? L.phLow + ' to ' + L.phHigh : String(L.phLow)) : '';
            $id('wpPhValue').value = v;
            state.phValue = v;
            if (v && !lotHas('ph')) LIT[4] = true;
        }
        $id('wpPhIn').hidden = state.ph === 'unsure';
        one('soilExtra', 'wpSoilExtras', 'soilextra', (L.soilExtra || []).filter((k) => (OPT.soilExtras || {})[k]), 'salt or sodium', 4, 'extra');
        one('lay', 'wpLays', 'lay', L.lay || null, 'the slope', 4, 'lay');
        if (!TOUCH.prevCrop) {
            const q = window.aneeFieldProfile && window.aneeFieldProfile.questions.prevCrop;
            const said = answers.prevCrop && q && q.o[answers.prevCrop] ? q.o[answers.prevCrop][0] : '';
            if (!$id('wpPrevCrop').value.trim() || $id('wpPrevCrop').value === AUTO_PREV) {
                $id('wpPrevCrop').value = said;
                state.prevCrop = said;
                AUTO_PREV = said;
                if (said) { got.push('the last crop'); if (!lotHas('prevCrop')) LIT[5] = true; }
            }
        }
        // Only the lot says these (2026-10-16, the owner): no step asks them here.
        const want = (L.problemsWhen || []).filter((k) => OPT.problems[k]);
        if ((L.problemsVariety || []).includes('slope') && OPT.problems.slope) want.push('slope');
        state.problems = want;
        state.waterLook = (L.waterLook || []).filter((k) => (OPT.waterLooks || {})[k]);
        state.elevation = (OPT.elevations || {})[L.elevation] ? L.elevation : null;
        state.sun = (OPT.suns || {})[L.sun] ? L.sun : null;
        return got.length ? 'Filled in for you: ' + andList(got) + '.' : '';
    }

    /* ---- the lot (partials/farm-lots, 2026-10-16, the owner) ----
       Picked on the first step. Each block with data-lot is a question the
       lot may already have answered: then it is hidden, and a step whose
       every block is hidden (data-blocks-only) is skipped by Next and Back,
       its dot folded away. The problems, the water's look, the elevation
       and the sun are not asked here at all (MISSING names the ones the lot
       has not answered yet). */
    let LOTS = null;
    const LOT_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    const lotA = () => (state.lot && state.lot.answers) || {};
    const lotHasKey = (k) => { const v = lotA()[k]; return v !== undefined && v !== null && v !== '' && !(Array.isArray(v) && !v.length); };
    const LOT_WORD = { soil: 'the soil', water: 'the water', ph: 'the pH', extra: 'any salt', lay: 'the slope', prevCrop: 'the last crop', area: 'the size' };
    // The lot's answers this page reads and never asks, as the review names a missing one.
    const MISSING = { flooding: 'the floods', drainage: 'the drainage', salt: 'the salt', weeds: 'the weeds', outbreaks: 'the last troubles',
        waterLook: 'how the water looks', elevation: 'the elevation', sun: 'the sunlight', hazards: 'a river or the wind' };
    const lotPh = () => (lotHasKey('acidity') && lotA().acidity !== 'untested') || lotHasKey('phFrom');
    function lotHas(k) {
        if (!state.lot || !k || k === '-') return false;
        if (k === 'soil') return lotHasKey('texture') || lotA().rocks === 'many';
        // "Never tested" does not say the pH; sodium and salt need the salt answer and the acidity (acid sulfate).
        if (k === 'ph') return lotPh();
        if (k === 'extra') return lotPh() && lotHasKey('salt');
        if (k === 'lay') return lotHasKey('slope');
        if (k === 'area') return Number(state.lot.areaHa) > 0;
        return lotHasKey(k);
    }
    const lotWordsOf = (k) => [LOT_WORD[k]].filter(Boolean);
    function skipped(n) {
        const sec = stepEl(n);
        if (!sec || !sec.hasAttribute('data-blocks-only')) return false;
        const blocks = [...sec.querySelectorAll('[data-lot]')];
        return blocks.length > 0 && blocks.every((b) => b.hidden);
    }
    function paintLot() {
        document.querySelectorAll('#wpWiz [data-lot]').forEach((b) => { b.hidden = lotHas(b.getAttribute('data-lot')); });
        // Each step says what its lot answered for it, and what it filled in.
        document.querySelectorAll('#wpWiz .wtp-step').forEach((sec) => {
            const n = Number(sec.getAttribute('data-step'));
            const words = [...new Set([...sec.querySelectorAll('[data-lot]')].filter((b) => b.hidden).flatMap((b) => lotWordsOf(b.getAttribute('data-lot'))))];
            const fill = !!LIT[n];
            let note = sec.querySelector(':scope > .wtp-lotnote');
            if (!state.lot || (!words.length && !fill) || skipped(n)) { if (note) note.hidden = true; return; }
            if (!note) { note = document.createElement('p'); note.className = 'wtp-lotnote'; sec.querySelector(':scope > .wtp-q').after(note); }
            const name = '<b>' + esc(state.lot.name) + '</b>';
            note.innerHTML = LOT_ICON + '<span>' + (words.length
                ? 'From ' + name + ': ' + esc(andList(words)) + '.' + (fill ? ' Some answers below are filled in from it too. Change any that is not right.' : '')
                : 'Filled in from ' + name + '. Change anything that is not right.') + '</span>';
            note.hidden = false;
        });
        document.querySelectorAll('#wpDots .wtp-dot').forEach((d, i) => d.classList.toggle('is-skip', skipped(i)));
    }
    let AUTO_AREA = '';
    async function applyLot() {
        const lot = state.lot;
        paintLot();
        if (!OPT) return;
        // What the lot settles is the lot's: a hand set answer under a hidden block gives way.
        [['soil', ['soil']], ['water', ['water']], ['ph', ['ph', 'phValue']], ['extra', ['soilExtra']], ['lay', ['lay']], ['prevCrop', ['prevCrop']]]
            .forEach(([blk, keys]) => { if (lotHas(blk)) keys.forEach((k) => { delete TOUCH[k]; }); });
        if (lotHas('prevCrop')) $id('wpPrevCrop').value = '';
        // The size: the lot's, said in the farmer's words ("1.5 ha"), or what they type.
        const area = $id('wpArea');
        if (lotHas('area')) { area.value = window.aneeLots.fmtHa(lot.areaHa); AUTO_AREA = area.value; }
        else if (area.value === AUTO_AREA) { area.value = ''; AUTO_AREA = ''; }
        state.area = area.value.trim();
        let L = {};
        if (lot) { try { L = await window.aneeLots.legacy(lot, null); } catch (_) { L = {}; } }
        if (state.lot !== lot) return;
        applyProfile(lot ? lot.answers || {} : {}, L);
        paintLot();
    }
    function pickLot(lot) {
        const was = state.lot ? state.lot.id : null;
        state.lot = lot || null;
        state.location = lot ? (lot.where || lot.place || '') : '';
        // Another lot is another field: what was set by hand for the last one gives way.
        if (!lot || lot.id !== was) Object.keys(TOUCH).forEach((k) => { delete TOUCH[k]; });
        return applyLot().then(() => { if (step === STEPS - 1 && state.lot) review(); });
    }
    function mountLots() {
        if (LOTS || !window.aneeLots) return;
        LOTS = window.aneeLots.picker($id('wpLots'), { crop: null, onPick: pickLot });
    }
    /* The lot's answers in a few plain words, for the review line. */
    const SAY = { 'texture.sandy': 'gritty sandy soil', 'texture.loam': 'soft crumbly loam', 'texture.clay': 'sticky clay', 'texture.heavy_clay': 'heavy clay that cracks',
        'rocks.none': 'no stones', 'rocks.some': 'some stones', 'rocks.many': 'many stones',
        'water.irrigation': 'irrigation canal', 'water.pump': 'pump or well', 'water.river': 'river or spring water', 'water.reservoir': 'farm pond', 'water.rainfed': 'rain only',
        'flooding.never': 'never floods', 'flooding.sometimes': 'floods sometimes', 'flooding.often': 'floods most wet seasons',
        'drainage.fast': 'drains fast', 'drainage.day': 'drains within a day', 'drainage.slow': 'drains slowly', 'drainage.stands': 'water stands for days',
        'salt.none': 'no salt', 'salt.crust': 'white salt crust', 'salt.sea': 'sea water at high tide', 'salt.tips': 'salt burned leaf tips', 'salt.sodic': 'sodic crust',
        'acidity.untested': 'pH never tested', 'acidity.none': 'not acidic', 'acidity.mild': 'mildly acidic', 'acidity.strong': 'strongly acidic',
        'acidity.sulfate': 'acid sulfate soil', 'acidity.alkaline': 'alkaline soil',
        'slope.flat': 'flat land', 'slope.gentle': 'gently sloping', 'slope.steep': 'steep land', 'slope.terraced': 'terraced',
        'prevCrop.rice_straw': 'rice last with the straw left', 'prevCrop.rice_no_straw': 'rice last with the straw gone', 'prevCrop.corn': 'corn or sorghum last',
        'prevCrop.legume': 'beans or peanuts last', 'prevCrop.vegetable': 'vegetables last', 'prevCrop.fallow': 'left to weeds last season', 'prevCrop.new_land': 'newly opened land',
        'weeds.few': 'few weeds', 'waterLook.unsure': 'water look not known',
        'elevation.lowland': 'lowland', 'elevation.upland': 'upland', 'elevation.highland': 'highland',
        'sun.full': 'full sun', 'sun.part': 'shaded part of the day', 'sun.shade': 'mostly shaded',
        'hazards.river': 'a river overflows into it', 'hazards.wind': 'strong winds', 'hazards.none': 'no river or wind trouble' };
    function lotSays(keys) {
        const Q = (window.aneeFieldProfile && window.aneeFieldProfile.questions) || {};
        const a = lotA(), out = [];
        keys.forEach((k) => {
            if (k === 'phFrom') {
                if (lotHasKey('phFrom')) out.push('pH ' + (lotHasKey('phTo') && String(a.phTo) !== String(a.phFrom) ? a.phFrom + ' to ' + a.phTo : a.phFrom));
                return;
            }
            // The soil test log: the newest test's day, and the pH it gave.
            if (k === 'tests') {
                const T = window.aneeFieldProfile && window.aneeFieldProfile.tests;
                const l = T ? T.list(a) : [];
                if (l.length) out.push('soil test ' + T.date(l[0]).replace('Date not known', 'with no date') + (a.phFrom != null ? ', pH ' + (a.phTo != null && String(a.phTo) !== String(a.phFrom) ? a.phFrom + ' to ' + a.phTo : a.phFrom) : '') + (l.length > 1 ? ' (' + l.length + ' in the log)' : ''));
                return;
            }
            if (!lotHasKey(k)) return;
            if (k === 'outbreaks') {
                const l = [].concat(a.outbreaks).filter((x) => x !== 'none');
                out.push(l.length ? l.length + (l.length === 1 ? ' trouble' : ' troubles') + ' last season' : 'no troubles last season');
                return;
            }
            const q = Q[k] || {};
            out.push([].concat(a[k]).map((x) => SAY[k + '.' + x] || String(((q.o || {})[x] || [x])[0]).toLowerCase()).join(' and '));
        });
        return out;
    }
    // The lot for the review: what it says, and what it has not said yet.
    function lotReview() {
        const lot = state.lot;
        if (!lot) return null;
        return { name: lot.name, place: lot.place || state.location, size: window.aneeLots ? window.aneeLots.fmtSize(lot) : '',
            said: lotSays(['texture', 'rocks', 'water', 'waterLook', 'acidity', 'tests', 'salt', 'slope', 'elevation', 'sun', 'hazards', 'prevCrop', 'flooding', 'drainage', 'weeds', 'outbreaks']),
            missing: Object.keys(MISSING).filter((k) => !lotHasKey(k)).map((k) => MISSING[k]) };
    }
    const lotPayload = () => {
        if (!state.lot || !window.aneeLots) return {};
        const P = window.aneeLots.payload(state.lot);
        return { lotId: P.lotId || null, lotName: state.lot.name || null, fieldProfile: P.fieldProfile || null, fieldProfileFrom: P.fieldProfileFrom || null };
    };
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const PRIO_E = { profit: '💰', survival: '🛡️', ease: '🧰', speed: '⏱️', market: '🏪', food: '🍚' };
    // One word per fit, for the little bars under each ranked crop.
    const PRIO_SHORT = { profit: 'Profit', survival: 'Hardy', ease: 'Easy', speed: 'Quick', market: 'Market', food: 'Food' };
    const shortOf = (label) => String(label || '').split(' — ')[0];

    async function boot() {
        try {
            const res = await api(U.options, { method: 'GET' });
            OPT = res.data;
            paintOptions();
        } catch (err) { toast(err.message, 'error'); }
        if (window.aneeLots) mountLots(); else document.addEventListener('DOMContentLoaded', mountLots, { once: true });
        // Painted: the price card and the wizard arrive together, once.
        const gen = $id('wpGen');
        if (gen && gen.classList.contains('an-wait')) {
            gen.classList.remove('an-wait');
            gen.classList.add('an-in');
            setTimeout(() => gen.classList.remove('an-in'), 400);
        }
    }

    function paintOptions() {
        state.country = state.country || OPT.country || ((window.ANEE_REGION || {}).code) || 'PH';
        const soilIcons = { clay: '🧱', loam: '🟤', sandy: '🏖️', silty: '🌊', rocky: '⛰️', unsure: '🤷' };
        $id('wpSoils').innerHTML = Object.entries(OPT.soils).map(([k, label]) => { const [n, sub] = String(label).split(' — '); return `
            <button type="button" class="wtp-choice" data-soil="${k}"><span class="c-e">${soilIcons[k] || '🟫'}</span><span>${esc(n)}${sub ? `<small>${esc(sub)}</small>` : ''}</span></button>`; }).join('');
        const waterIcons = { irrigated: '🚰', limited: '🚿', rainfed: '🌧️' };
        $id('wpWaters').innerHTML = Object.entries(OPT.waters).map(([k, label]) => `
            <button type="button" class="wtp-choice" data-water="${k}"><span class="c-e">${waterIcons[k] || '💧'}</span><span>${esc(label)}</span></button>`).join('');
        const group = (hostId, attr, table, icons, preset) => {
            // A list preset is a pick-many group: each listed key lights, and
            // "unsure" lights while the list is empty.
            const lit = (k) => Array.isArray(preset) ? (preset.length ? preset.includes(k) : k === 'unsure') : preset === k;
            $id(hostId).innerHTML = Object.entries(table || {}).map(([k, label]) => { const [n, sub] = String(label).split(' — '); return `
            <button type="button" class="wtp-choice${lit(k) ? ' is-on' : ''}" data-${attr}="${esc(k)}"><span class="c-e">${icons[k] || '•'}</span><span>${esc(n)}${sub ? `<small>${esc(sub)}</small>` : ''}</span></button>`; }).join('');
        };
        group('wpPhs', 'ph', OPT.phLevels, { unsure: '🤷', acidic: '🍋', neutral: '⚖️', alkaline: '🧂' }, state.ph);
        group('wpSoilExtras', 'soilextra', OPT.soilExtras, { unsure: '🤷', sodic: '🧱', saline: '🌊', acid_sulfate: '🟠' }, state.soilExtra);
        group('wpLays', 'lay', OPT.lays, { flat: '▬', gentle: '⛰️', steep: '🏔️', low: '🕳️' }, state.lay);
        group('wpLabors', 'labor', OPT.labors, { hand: '🧑‍🌾', some: '🛠️', mech: '🚜' }, state.labor);
        group('wpBudgets', 'budget', OPT.budgets, { tight: '🪙', moderate: '💵', invest: '💰' }, state.budget);
        group('wpMarkets', 'market', OPT.markets, { farm: '🏠', town: '🏪', city: '🏙️', contract: '🤝' }, state.market);
        if (!state.priorities.length) state.priorities = Object.keys(OPT.priorities || {});
        paintPrio();
        group('wpExcludes', 'exclude', OPT.families, { grain: '🌾', vegetable: '🥬', root: '🍠', legume: '🫘', tree: '🌳' }, state.exclude);
        const aimIcons = { sell: '🏪', family: '🍚', both: '⚖️' };
        $id('wpAims').innerHTML = Object.entries(OPT.aims).map(([k, label]) => { const [n, sub] = String(label).split(' — '); return `
            <button type="button" class="wtp-choice" data-aim="${k}"><span class="c-e">${aimIcons[k] || '🌱'}</span><span>${esc(n)}${sub ? `<small>${esc(sub)}</small>` : ''}</span></button>`; }).join('');
        $id('wpDots').innerHTML = Array.from({ length: STEPS }, (_, i) => `<span class="wtp-dot${i === 0 ? ' is-on' : ''}"></span>`).join('');
        // A lot picked before the options came is filled in now.
        applyLot();
        paintQuote();
    }

    /* ---- what matters: rows the farmer moves with the arrows or by dragging ---- */
    function paintPrio() {
        const host = $id('wpPrio');
        const n = state.priorities.length;
        host.innerHTML = state.priorities.map((k, i) => { const [name, sub] = String((OPT.priorities || {})[k] || k).split(' — '); return `
            <div class="wp-prio-row" draggable="true" data-prio="${esc(k)}">
                <span class="wp-prio-n">${i + 1}</span>
                <span class="wp-prio-t">${PRIO_E[k] || '•'} ${esc(name)}${sub ? `<small>${esc(sub)}</small>` : ''}</span>
                <span class="wp-prio-b">
                    <button type="button" data-prio-up${i === 0 ? ' disabled' : ''} aria-label="Move up">▲</button>
                    <button type="button" data-prio-down${i === n - 1 ? ' disabled' : ''} aria-label="Move down">▼</button>
                </span>
            </div>`; }).join('');
    }
    const movePrio = (k, to) => {
        const from = state.priorities.indexOf(k);
        if (from < 0 || to < 0 || to >= state.priorities.length || from === to) return;
        state.priorities.splice(to, 0, state.priorities.splice(from, 1)[0]);
        paintPrio();
    };
    $id('wpPrio').addEventListener('click', (e) => {
        const row = e.target.closest('.wp-prio-row');
        if (!row) return;
        const k = row.getAttribute('data-prio');
        const i = state.priorities.indexOf(k);
        if (e.target.closest('[data-prio-up]')) movePrio(k, i - 1);
        else if (e.target.closest('[data-prio-down]')) movePrio(k, i + 1);
    });
    let dragKey = null;
    $id('wpPrio').addEventListener('dragstart', (e) => { const row = e.target.closest('.wp-prio-row'); if (!row) return; dragKey = row.getAttribute('data-prio'); row.classList.add('is-drag'); try { e.dataTransfer.setData('text/plain', dragKey); e.dataTransfer.effectAllowed = 'move'; } catch (_) { /* fine */ } });
    $id('wpPrio').addEventListener('dragover', (e) => { const row = e.target.closest('.wp-prio-row'); if (!row || !dragKey) return; e.preventDefault(); document.querySelectorAll('#wpPrio .wp-prio-row').forEach((r) => r.classList.toggle('is-over', r === row)); });
    $id('wpPrio').addEventListener('dragleave', (e) => { const row = e.target.closest('.wp-prio-row'); if (row) row.classList.remove('is-over'); });
    $id('wpPrio').addEventListener('drop', (e) => { const row = e.target.closest('.wp-prio-row'); if (!row || !dragKey) return; e.preventDefault(); const to = state.priorities.indexOf(row.getAttribute('data-prio')); movePrio(dragKey, to); dragKey = null; });
    $id('wpPrio').addEventListener('dragend', () => { dragKey = null; document.querySelectorAll('#wpPrio .wp-prio-row').forEach((r) => r.classList.remove('is-drag', 'is-over')); });

    /* ---- crops in mind: the book for the FIELD's country, picked many at a time ---- */
    const bookFor = () => (OPT.crops || []).filter((c) => state.country !== 'PH' || !c.intl);
    function paintCropSheet() {
        const groups = {};
        bookFor().forEach((c) => { (groups[c.group] = groups[c.group] || []).push(c); });
        $id('wpCropList').innerHTML = Object.entries(groups).map(([g, list]) => `
            <div class="crop-group" data-crop-group>
                <p class="crop-group-h">${esc(g)}</p>
                ${list.map((c) => `
                    <button type="button" class="crop-row${state.cropsAsked.includes(c.key) ? ' is-on' : ''}" data-crop="${esc(c.key)}" data-find="${esc((c.label + ' ' + g).toLowerCase())}">
                        <span class="crop-row-e">${esc(c.icon)}</span>
                        <span class="crop-row-t"><b>${esc(c.label)}</b><small>${c.perennial ? 'Tree crop, years to first harvest' : (c.maturity ? c.maturity + ' days to harvest' : '')}</small></span>
                        <span class="crop-row-k">✓</span>
                    </button>`).join('')}
            </div>`).join('');
        cropSift();
    }
    function paintAsked() {
        const book = OPT.crops || [];
        $id('wpAsked').innerHTML = state.cropsAsked.map((k) => { const c = book.find((x) => x.key === k) || {}; return `<span>${esc(c.icon || '🌱')} ${esc(c.label || k)}<button type="button" data-asked-x="${esc(k)}" aria-label="Remove">✕</button></span>`; }).join('');
        $id('wpCropNow').textContent = state.cropsAsked.length ? `${state.cropsAsked.length} chosen. Add another` : 'Add a crop from the list';
    }
    $id('wpCropBtn').addEventListener('click', () => {
        paintCropSheet();
        if (window.openSheet) window.openSheet('wpCropSheet');
        if (!window.matchMedia('(hover: none)').matches) setTimeout(() => $id('wpCropSearch')?.focus(), 280);
    });
    $id('wpCropList').addEventListener('click', (e) => {
        const row = e.target.closest('.crop-row');
        if (!row) return;
        const k = row.getAttribute('data-crop');
        if (state.cropsAsked.includes(k)) state.cropsAsked = state.cropsAsked.filter((x) => x !== k);
        else if (state.cropsAsked.length >= 8) { toast('Up to eight crops per analysis.', 'error'); return; }
        else state.cropsAsked.push(k);
        row.classList.toggle('is-on', state.cropsAsked.includes(k));
        paintAsked();
    });
    $id('wpAsked').addEventListener('click', (e) => {
        const b = e.target.closest('[data-asked-x]');
        if (!b) return;
        state.cropsAsked = state.cropsAsked.filter((x) => x !== b.getAttribute('data-asked-x'));
        paintAsked();
    });
    const cropSift = () => {
        const q = ($id('wpCropSearch').value || '').trim().toLowerCase();
        let shown = 0;
        document.querySelectorAll('#wpCropList [data-crop-group]').forEach((g) => {
            let left = 0;
            g.querySelectorAll('.crop-row').forEach((r) => { const hit = !q || (r.getAttribute('data-find') || '').includes(q); r.hidden = !hit; if (hit) left++; });
            g.hidden = left === 0;
            shown += left;
        });
        $id('wpCropNone').classList.toggle('hidden', shown > 0);
    };
    $id('wpCropSearch').addEventListener('input', cropSift);

    const QUOTE_MIN_KEY = 'anee-whatp-quote-min';
    let quoteMin = false;
    try { quoteMin = localStorage.getItem(QUOTE_MIN_KEY) === '1'; } catch (_) { /* opens full */ }

    function paintQuote() {
        const q = $id('wpQuote');
        if (!OPT) return;
        if (!OPT.canUse) {
            $id('wpQuoteCost').innerHTML = esc(OPT.whyNot || 'The analysis is not available right now.');
            $id('wpQuoteHint').textContent = '';
            q.classList.remove('is-min');
            q.hidden = false;
            return;
        }
        if (!OPT.quote) { q.hidden = true; return; }
        q.classList.toggle('is-min', quoteMin);
        $id('wpQuoteHead').setAttribute('aria-expanded', quoteMin ? 'false' : 'true');
        $id('wpQuoteCost').innerHTML = `One analysis costs <b>${OPT.quote} credits</b>. You have ${creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString())}. Nothing is charged until you press Run.`;
        $id('wpQuoteHint').textContent = `${OPT.quote} credits`;
        q.hidden = false;
    }
    $id('wpQuoteHead').addEventListener('click', () => {
        quoteMin = !quoteMin;
        try { localStorage.setItem(QUOTE_MIN_KEY, quoteMin ? '1' : '0'); } catch (_) { /* not remembered */ }
        paintQuote();
    });

    /* ---------------- the walk ---------------- */
    function show(n, backwards) {
        const was = document.querySelector('.wtp-step.is-on'), from = step;
        step = Math.max(0, Math.min(STEPS - 1, n));
        // Over the steps the lot answered whole, the way the farmer is going.
        while (step > 0 && step < STEPS - 1 && skipped(step)) step += backwards ? -1 : 1;
        if (step !== from) window.aneeStepOut?.(was);
        document.querySelectorAll('.wtp-step').forEach((s) => {
            const on = Number(s.getAttribute('data-step')) === step;
            s.classList.toggle('is-on', on);
            s.classList.toggle('is-back', on && !!backwards);
        });
        if (step !== from) window.aneeStepTop?.($id('wpWiz'));
        document.querySelectorAll('.wtp-dot').forEach((d, i) => d.classList.toggle('is-on', i <= step));
        $id('wpBack').disabled = false;   // on the first page it goes back where they came from
        $id('wpNext').style.display = step === STEPS - 1 ? 'none' : '';
        if (step === STEPS - 1) review();
    }

    function stepReady() {
        switch (step) {
            case 0: return (!!state.lot && !!state.location) || (toast('Pick the lot, or add one.', 'error'), false);
            case 1: return !!state.startMonth || (toast('Pick the month you would start.', 'error'), false);
            case 2: return !!state.soil || (toast('Pick the soil closest to yours.', 'error'), false);
            case 3: return !!state.water || (toast('Pick the water the field gets.', 'error'), false);
            case 4: state.phValue = $id('wpPhValue').value.trim(); return true;
            case 5: state.prevCrop = $id('wpPrevCrop').value.trim(); state.grewWell = $id('wpGrewWell').value.trim(); return true;
            case 6: state.area = $id('wpArea').value.trim(); state.notes = $id('wpNotes').value.trim();
                return !!state.aim || (toast('Pick what the harvest is for.', 'error'), false);
            case 9: state.cropsOther = $id('wpCropsOther').value.trim(); return true;
            case STEPS - 1: {
                // The run itself: a step the lot skipped must still have its answer.
                const back = !state.lot || !state.location ? 0 : !state.startMonth ? 1 : !state.soil ? 2 : !state.water ? 3 : !state.aim ? 6 : -1;
                if (back < 0) return true;
                toast({ 0: 'Pick the lot, or add one.', 1: 'Pick the month you would start.', 2: 'Pick the soil closest to yours.', 3: 'Pick the water the field gets.', 6: 'Pick what the harvest is for.' }[back], 'error');
                // A step the lot answered whole cannot be shown, so its lot is.
                setTimeout(() => show(skipped(back) ? 0 : back, true), 250);
                return false;
            }
            default: return true;
        }
    }

    // A list label's first words: "Floods / standing water after rain" is "Floods".
    const shortLabel = (l) => String(l || '').split(/ \/ | \(| — /)[0];
    function review() {
        const month = (OPT.months.find((m) => m.key === state.startMonth) || {}).label || '';
        const lot = state.lot;
        const of = (table, k) => (k && k !== 'unsure' ? shortLabel((OPT[table] || {})[k] || k) : '');
        const land = [of('elevations', state.elevation), of('suns', state.sun), ...state.waterLook.map((k) => 'water ' + of('waterLooks', k).toLowerCase())].filter(Boolean);
        const ground = [state.ph && state.ph !== 'unsure' ? of('phLevels', state.ph) + (state.phValue ? ' (' + state.phValue + ')' : '') : '', ...state.soilExtra.map((k) => of('soilExtras', k)), of('lays', state.lay)].filter(Boolean);
        const farm = [state.prevCrop ? 'Last crop: ' + state.prevCrop : '', state.grewWell ? 'Grew well: ' + state.grewWell : '', of('labors', state.labor), of('budgets', state.budget), ...state.market.map((k) => of('markets', k))].filter(Boolean);
        const book = bookFor();
        const asked = [...state.cropsAsked.map((k) => shortLabel((book.find((c) => c.key === k) || {}).label || k)), ...(state.cropsOther ? state.cropsOther.split(',').map((x) => x.trim()).filter(Boolean) : [])];
        $id('wpReview').innerHTML = window.aneeReview.html({
            icon: '🌱', kicker: 'Crops that fit your field', title: lot ? lot.name : state.location, sub: 'Starting ' + month + (state.area ? ' · ' + state.area : ''),
            rows: [
                { e: '🗓️', k: 'Starting', v: month, go: 1 },
                state.country && state.country !== (OPT.country || '') ? { e: '🌍', k: 'Country', v: rulesFor(state.country).name || state.country } : null,
                { e: '🟤', k: 'Soil', v: shortOf(OPT.soils[state.soil]), go: skipped(2) ? null : 2, lot: skipped(2) },
                { e: '💧', k: 'Water', v: OPT.waters[state.water], go: skipped(3) ? null : 3, lot: skipped(3) },
                { e: '⛰️', k: 'The land', v: land, none: 'Not in the lot', lot: true },
                { e: '🧪', k: 'Acidity, salt and slope', v: ground, none: 'Not given', go: skipped(4) ? null : 4, lot: skipped(4) },
                { e: '⚠️', k: 'Field problems', v: state.problems.map((k) => shortLabel(OPT.problems[k] || k)), none: 'None in the lot', lot: true },
                { e: '🧑‍🌾', k: 'The farm', v: farm, none: 'Not given', go: 5 },
                { e: '🎯', k: 'The harvest is for', v: shortOf(OPT.aims[state.aim]), go: 6 },
                { e: '📐', k: 'Field size', v: state.area, none: 'Not given', go: 6 },
                { e: '⭐', k: 'What matters most', v: state.priorities.slice(0, 3).map((k, i) => (i + 1) + '. ' + shortOf((OPT.priorities || {})[k] || k)), go: 7 },
                { e: '🚫', k: 'Not planting', v: state.exclude.map((k) => shortOf((OPT.families || {})[k] || k)), none: 'Nothing left out', go: 8 },
                { e: '📝', k: 'Crops you have in mind', v: asked, none: 'None, Anee picks', go: 9 },
            ],
            lot: lotReview(),
        });
        $id('wpRunSays').textContent = OPT.canUse && OPT.quote ? `Run the analysis (${OPT.quote} credits)` : 'Run the analysis';
        $id('wpRunFine').textContent = OPT.canUse
            ? 'Paid from your AI credits. It shows in your credit log.'
            : (OPT.whyNot || '');
        $id('wpRun').disabled = !OPT.canUse;
    }

    $id('wpCountry')?.addEventListener('country:change', (e) => {
        const code = e.detail && e.detail.code;
        const r = e.detail && e.detail.rules;
        if (!code || !r) return;
        state.country = code;
        const qc = $id('wpQuoteCountry');
        if (qc) qc.textContent = countryName(code);
        // A crop picked for one country may not be in the other's book.
        const keep = new Set(bookFor().map((c) => c.key));
        state.cropsAsked = state.cropsAsked.filter((k) => keep.has(k));
        paintAsked();
    });
    $id('wpNext').addEventListener('click', () => { if (stepReady()) show(step + 1); });
    $id('wpBack').addEventListener('click', () => (step === 0 ? window.aneeLeave() : show(step - 1, true)));
    // The review's "Edit the lot": its sheet, then the steps follow what changed.
    $id('wpReview').addEventListener('click', async (e) => {
        const go = e.target.closest('[data-rv-go]');
        if (go) { show(Number(go.getAttribute('data-rv-go')), true); return; }
        if (!e.target.closest('[data-lot-edit]') || !state.lot || !window.aneeLots) return;
        const row = await window.aneeLots.edit(state.lot, { crop: null, ask: false });
        if (row) pickLot(row);
    });
    const pickWire = (hostId, attr, key) => {
        $id(hostId).addEventListener('click', (e) => {
            const b = e.target.closest(`[data-${attr}]`);
            if (!b) return;
            TOUCH[key] = true;
            state[key] = b.getAttribute(`data-${attr}`);
            document.querySelectorAll(`#${hostId} .wtp-choice`).forEach((c) => c.classList.toggle('is-on', c === b));
        });
    };
    // The month: a tag that opens the calendar (partials/month-pick, 2026-10-16, the owner).
    $id('wpMonthBtn').addEventListener('click', async () => {
        if (!OPT || !window.aneeMonthPick) return;
        const k = await window.aneeMonthPick.open({ months: OPT.months, value: state.startMonth, title: 'Which month will you start?' });
        if (!k) return;
        state.startMonth = k;
        const t = $id('wpMonthNow');
        t.textContent = window.aneeMonthPick.label(k);
        t.classList.remove('is-none');
    });
    pickWire('wpSoils', 'soil', 'soil');
    pickWire('wpWaters', 'water', 'water');
    pickWire('wpAims', 'aim', 'aim');
    pickWire('wpPhs', 'ph', 'ph');
    // A pick-many group: answers toggle and gather in a list; "unsure" (where
    // the group has one) clears the list and stands alone.
    const manyWire = (hostId, attr, key) => {
        $id(hostId).addEventListener('click', (e) => {
            const b = e.target.closest(`[data-${attr}]`);
            if (!b) return;
            const k = b.getAttribute(`data-${attr}`);
            TOUCH[key] = true;
            const list = Array.isArray(state[key]) ? state[key] : [];
            state[key] = k === 'unsure' ? [] : (list.includes(k) ? list.filter((x) => x !== k) : [...list, k]);
            document.querySelectorAll(`#${hostId} .wtp-choice`).forEach((c) => {
                const ck = c.getAttribute(`data-${attr}`);
                c.classList.toggle('is-on', ck === 'unsure' ? state[key].length === 0 : state[key].includes(ck));
            });
        });
    };
    manyWire('wpSoilExtras', 'soilextra', 'soilExtra');
    pickWire('wpLays', 'lay', 'lay');
    pickWire('wpLabors', 'labor', 'labor');
    pickWire('wpBudgets', 'budget', 'budget');
    manyWire('wpMarkets', 'market', 'market');
    $id('wpExcludes').addEventListener('click', (e) => {
        const b = e.target.closest('[data-exclude]');
        if (!b) return;
        const k = b.getAttribute('data-exclude');
        const all = Object.keys((OPT && OPT.families) || {});
        if (state.exclude.includes(k)) state.exclude = state.exclude.filter((x) => x !== k);
        else if (state.exclude.length >= all.length - 1) { toast('Keep at least one crop group.', 'error'); return; }
        else state.exclude = [...state.exclude, k];
        document.querySelectorAll('#wpExcludes .wtp-choice').forEach((c) => c.classList.toggle('is-on', state.exclude.includes(c.getAttribute('data-exclude'))));
    });
    // A tested pH can be typed once the farmer says the soil is anything but "not sure".
    $id('wpPhs').addEventListener('click', () => { $id('wpPhIn').hidden = state.ph === 'unsure'; if (state.ph === 'unsure') $id('wpPhValue').value = ''; });
    $id('wpPhValue').addEventListener('input', () => { TOUCH.phValue = true; });
    $id('wpPrevCrop').addEventListener('input', () => { TOUCH.prevCrop = true; });

    /* ---------------- the run ---------------- */
    $id('wpRun').addEventListener('click', async () => {
        if (!stepReady()) return;
        const wiz = $id('wpWiz');
        wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = 'none');
        window.aneeWait.show({ title: 'Anee is checking your land…', lines: ['Soil, water and local weather…', 'Looking at crops grown in ' + countryName(state.country) + '…', 'Checking harvest dates and the weather then…', 'Ranking by what matters to you…'], sub: 'Usually under a minute.' });
        $id('wpReport').hidden = true;
        let landed = false;
        try {
            const res = await api(U.generate, { method: 'POST', body: {
                location: state.location, startMonth: state.startMonth, soil: state.soil,
                water: state.water, aim: state.aim, area: state.area, notes: state.notes,
                problems: state.problems, country: state.country,
                ph: state.ph, phValue: state.phValue || null, soilExtra: state.soilExtra, waterLook: state.waterLook, lay: state.lay, elevation: state.elevation, sun: state.sun,
                prevCrop: state.prevCrop, grewWell: state.grewWell, labor: state.labor, budget: state.budget, market: state.market,
                priorities: state.priorities, cropsAsked: state.cropsAsked, cropsOther: state.cropsOther, exclude: state.exclude, ...lotPayload(),
            } });
            let data = res.data;
            if (data.pending) {
                // The shared poll: the bar, the clock and the hang check ride along.
                data = await window.aneeWait.poll({ id: data.id || res.data.id, job: U.job, phases: window.aneeWait.phases.research });
            }
            OPT.balance = data.balance;
            landed = true;
            // Full screen first: the tabs and the wizard wait behind it. A
            // slip in drawing must not strand the veil: the result is saved.
            try { openView({ report: data.report, params: data.params, charged: data.charged, savedId: data.savedId }, 'fresh'); }
            catch (drawErr) { console.error(drawErr); toast('Your analysis is in the Saved tab, but it could not be shown here.', 'error'); }
            await window.aneeWait.done({ title: 'Done!', line: `${data.charged} credits used. Find it in the Saved tab.` });
            toast(`Done. ${data.charged} credits used. Find it in the Saved tab.`);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            if (!landed) window.aneeWait.fail();
            wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = '');
            show(step);
            // Only while the fresh view is still up: closed during the veil's last
            // moments (Escape), the wizard has already come back and stays.
            if (landed && VIEW_MODE === 'fresh' && !$id('wpView').hidden) { wiz.hidden = true; $id('wpQuote').hidden = true; }
        }
    });

    /* Full screen when it lands, and for anything opened from the shelf. */
    let VIEW_MODE = null;
    function openView(item, mode) {
        const view = $id('wpView');
        VIEW_MODE = mode;
        const top = ((item.report || {}).topPick || {}).crop;
        $id('wpViewTitle').textContent = 'What to plant' + (top ? ': ' + top : '');
        const host = $id('wpViewReport');
        host.classList.remove('is-drawn');
        drawReport(host, item, mode, true);
        view.hidden = false;
        document.documentElement.classList.add('va-view-lock');
        view.scrollTop = 0;
        requestAnimationFrame(() => requestAnimationFrame(() => { view.classList.add('is-on'); host.classList.add('is-drawn'); }));
    }
    function closeView() {
        const view = $id('wpView');
        if (view.hidden) return;
        view.classList.remove('is-on');
        document.documentElement.classList.remove('va-view-lock');
        const wasFresh = VIEW_MODE === 'fresh';
        VIEW_MODE = null;
        setTimeout(() => { view.hidden = true; $id('wpViewReport').innerHTML = ''; if (wasFresh) wizardBack(); }, 300);
    }
    $id('wpViewX').addEventListener('click', closeView);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeView(); });

    /* A finished run leaves a clean form (2026-10-17, the owner): the lot,
       the month, every choice, the typed words and the crops in mind all
       cleared, the order of what matters as it came, back at the first
       step as if the page had just opened. The options, the lots and the
       price stay loaded; the field's country stays, a setting more than an
       answer. */
    function resetWizard() {
        Object.assign(state, { location: '', startMonth: null, soil: null, water: null, aim: null, area: '', notes: '', problems: [], ph: 'unsure', phValue: '',
            soilExtra: [], waterLook: [], lay: null, elevation: null, sun: null, prevCrop: '', grewWell: '', labor: null, budget: null, market: [],
            priorities: [], cropsAsked: [], cropsOther: '', exclude: [], lot: null });
        Object.keys(TOUCH).forEach((k) => { delete TOUCH[k]; });
        LIT = {};
        AUTO_PREV = '';
        AUTO_AREA = '';
        if (LOTS) LOTS.set(null);
        ['wpPhValue', 'wpPrevCrop', 'wpGrewWell', 'wpArea', 'wpNotes', 'wpCropsOther', 'wpCropSearch'].forEach((id) => { if ($id(id)) $id(id).value = ''; });
        $id('wpPhIn').hidden = true;
        const now = $id('wpMonthNow');
        now.textContent = 'Pick the month and year';
        now.classList.add('is-none');
        // The choices drawn again, none lit, the order of what matters as it came.
        if (OPT) { paintOptions(); paintAsked(); }
        paintLot();
        show(0);
    }

    // Every way back from a fresh result: the X, Escape, "Run another analysis", Delete.
    function wizardBack() {
        $id('wpWiz').hidden = false;
        if (OPT && OPT.canUse && OPT.quote) $id('wpQuote').hidden = false;
        $id('wpReport').hidden = true;
        $id('wpReport').classList.remove('is-drawn');
        // Shown first, so the last step fades out where it stood and the first fades in.
        resetWizard();
        if (window.aneeStepTop) window.aneeStepTop($id('wpWiz'));
        else $id('wpWiz').scrollIntoView({ behavior: 'auto', block: 'start' });
    }

    /* ---------------- the harvest: words for a risk score, the timeline ---------------- */
    const riskBand = (v) => (Number(v) < 30 ? 'low' : (Number(v) < 60 ? 'mid' : 'high'));
    const riskWord = (v) => ({ low: 'Low', mid: 'Medium', high: 'High' }[riskBand(v)]);
    const riskIcon = (kind) => ({ flood: '🌊', storm: '🌀', drought: '☀️', heat: '🔥', frost: '❄️' }[kind] || '🌤️');
    /* From planting to harvest, drawn against the year's typical risks: one
       bar per crop from its planting month to its harvest month, a dot at
       the harvest coloured by the risk then, and the twelve columns tinted
       by the worst of storm/flood/drought/heat/frost that month. The year
       starts at the farmer's start month so it reads left to right. */
    function timeline(r, p) {
        // The ranked crops, then the surprises, each drawn only when it has months.
        const rows = [...(r.recommendations || []), ...(r.surprises || []).map((x) => ({ ...x, surprise: true }))].filter((x) => x.plantMonth && x.harvestMonth);
        if (!rows.length) return '';
        // "Mungbean (Monggo)" → the name, and the local name for a smaller line under it.
        const nameOf = (crop) => { const m = String(crop || '').match(/^(.*?)\s*(\([^()]*\))\s*$/); return m ? [m[1], m[2]] : [String(crop || ''), '']; };
        const start = Number(String(p.startMonth || '').split('-')[1]) || rows[0].plantMonth || 1;
        const col = (m) => ((Number(m) - start) % 12 + 12) % 12; // 0..11 from the start month
        const mr = {};
        (r.monthRisk || []).forEach((m) => { if (m && m.month) mr[Number(m.month)] = m; });
        const worst = (m) => { const x = mr[m] || {}; const kinds = [['storm', x.storm], ['flood', x.flood], ['drought', x.drought], ['heat', x.heat], ['frost', x.frost]].map(([k, v]) => [k, Number(v) || 0]).sort((a, b) => b[1] - a[1]); return kinds[0]; };
        const months = Array.from({ length: 12 }, (_, i) => ((start - 1 + i) % 12) + 1);
        return `
            <div class="wtp-card">
                <h3>Planting to harvest, with the year's risks</h3>
                <div class="wp-tl"><div class="wp-tl-in">
                    <div class="wp-tl-bg">${months.map((m) => { const [k, v] = worst(m); return `<i class="is-${riskBand(v)}" title="${esc(MONTHS[m - 1])}: ${esc(k)} ${v}/100"></i>`; }).join('')}</div>
                    <div class="wp-tl-row is-head"><span class="wp-tl-lbl">Crop<small>plant › harvest</small></span>${months.map((m) => { const [k, v] = worst(m); return `<span class="wp-tl-m"><i>${v >= 30 ? riskIcon(k) : ''}</i>${esc(MONTHS[m - 1])}</span>`; }).join('')}</div>
                    ${rows.map((x) => { const a = col(x.plantMonth); let span = col(x.harvestMonth) - a + 1; const more = span <= 0 || (Number(x.daysToHarvest) || 0) > 365; if (span <= 0) span = 12 - a; const rk = x.harvestRisk || {}; return `
                    <div class="wp-tl-row">
                        <span class="wp-tl-lbl" title="${esc(x.crop || '')}"><i class="wp-tl-n">${x.surprise ? '🎁' : esc(String(x.rank || '')) + '.'}</i><span class="wp-tl-t"><b>${esc(nameOf(x.crop)[0])}</b>${nameOf(x.crop)[1] ? `<small class="wp-tl-alt">${esc(nameOf(x.crop)[1])}</small>` : ''}<small>${x.daysToHarvest ? '~' + esc(String(Math.round(Number(x.daysToHarvest)))) + ' d' : ''}${x.farmerAsked ? ' · you asked' : ''}${x.surprise ? ' · surprise' : ''}</small></span></span>
                        <span class="wp-tl-bar${x.farmerAsked ? ' is-asked' : ''}${x.surprise ? ' is-surprise' : ''}${more ? ' is-more' : ''}" style="grid-column:${a + 2} / span ${span}" title="${esc(x.crop || '')}: plant ${esc(MONTHS[Number(x.plantMonth) - 1] || '')}, harvest ${esc(MONTHS[Number(x.harvestMonth) - 1] || '')}${rk.note ? '. ' + esc(rk.note) : ''}">${rk.score != null && !more ? `<i class="wp-tl-dot is-${riskBand(rk.score)}"></i>` : ''}</span>
                    </div>`; }).join('')}
                </div></div>
                <div class="wp-tl-legend">
                    <span><i class="is-bar"></i> growing</span>
                    <span><i class="wp-tl-dot is-low" style="position:static;transform:none"></i> calm harvest</span>
                    <span><i class="wp-tl-dot is-mid" style="position:static;transform:none"></i> some risk</span>
                    <span><i class="wp-tl-dot is-high" style="position:static;transform:none"></i> risky harvest</span>
                    ${(r.surprises || []).some((x) => x.plantMonth && x.harvestMonth) ? `<span><i class="is-surprise"></i> surprise pick</span>` : ''}
                    <span><i class="is-tint"></i> months with storms, floods, drought, heat or frost</span>
                </div>
            </div>`;
    }

    /* The chart slides under a mouse too: press, drag, let go. */
    document.addEventListener('pointerdown', (e) => {
        if (e.pointerType !== 'mouse' || e.button !== 0) return;
        const box = e.target.closest('.wp-tl');
        if (!box || box.scrollWidth <= box.clientWidth) return;
        const x0 = e.clientX, left0 = box.scrollLeft;
        let moved = false;
        const move = (ev) => { const dx = ev.clientX - x0; if (Math.abs(dx) > 3) { moved = true; box.classList.add('is-dragging'); } box.scrollLeft = left0 - dx; };
        const up = () => { document.removeEventListener('pointermove', move); document.removeEventListener('pointerup', up); box.classList.remove('is-dragging'); if (moved) { const eat = (ev) => { ev.stopPropagation(); ev.preventDefault(); }; box.addEventListener('click', eat, { capture: true, once: true }); setTimeout(() => box.removeEventListener('click', eat, { capture: true }), 0); } };
        document.addEventListener('pointermove', move);
        document.addEventListener('pointerup', up);
    });

    /* ---------------- the report, drawn ---------------- */
    function drawReport(host, item, mode, quiet) {
        const r = item.report || {};
        const p = item.params || {};
        // No emoji shortcodes, and no interjections -- a report saved before the
        // register was written may still open with "Aray," or "Naku!"; they go.
        const sweep = (t) => { const s = String(t || '').replace(/:[a-z0-9_-]+:/gi, '').replace(/(^|[.!?]\s+)(aray|naku|hala|grabe|whoa|wow|ooh|oh no|hay naku|sus|ay)\b[,!.]*\s*/gi, '$1').replace(/\s{2,}/g, ' ').trim(); return s.charAt(0).toUpperCase() + s.slice(1); };
        const top = r.topPick || {};
        const month = (OPT ? (OPT.months.find((m) => m.key === p.startMonth) || {}).label : '') || p.startMonth || '';
        /* The registered varieties that fit, under a crop (2026-10-14): its
           top three from the variety records, all of them on the public
           site, and Variety Research opened on the crop. */
        const varFacts = (f) => [f.type === 'hybrid' ? 'hybrid' : '', f.days ? Math.round(f.days) + ' ' + (f.daysUnit || 'days') : '',
            f.yield ? (Math.round(f.yield * 100) / 100) + ' ' + (f.yieldUnit || 't/ha') : ''].filter(Boolean).join(' · ');
        const varLine = (x) => {
            const v = x.varieties;
            if (!v || !(v.top || []).length) return '';
            return `<div class="wp-vars">
                <span class="wp-vars-h">Varieties on record that fit your field</span>
                <div class="wp-vars-l">${v.top.map((f) => `<a href="${esc(f.url)}" target="_blank" rel="noopener">${esc(f.name)}${f.alias ? ' · ' + esc(f.alias) : ''}${varFacts(f) ? `<small>${esc(varFacts(f))}</small>` : ''}</a>`).join('')}</div>
                <div class="wp-vars-a"><a href="${esc(U.vary)}?crop=${encodeURIComponent(v.cropKey)}">Compare varieties with Anee ›</a>${v.finder ? `<a href="${esc(v.finder)}" target="_blank" rel="noopener">All ${esc(String(v.count))} on record ›</a>` : ''}</div>
            </div>`;
        };
        /* The market of a crop (2026-10-27): its farmgate price lately and where its harvest month sits against
           its year, worked out on read from the price shelf (WhatToPlantController::withMarket). */
        const MKM = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const mkLine = (x) => { const m = x.market; if (!m) return '';
            const d = m.harvest === null || m.harvest === undefined ? null : Math.round(m.harvest * 100);
            return `<span class="wp-mk">💰 About ₱${esc(Number(m.p).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }))}${m.unit === 'kg' ? '/kg' : ''} lately${d !== null && d !== 0 ? ` · harvest ${d > 0 ? '+' : '−'}${Math.abs(d)}% against its year` : ''}${m.url ? ` · <a href="${esc(m.url)}">forecast ›</a>` : ''}</span>`; };
        // The farmer's priorities in their order (older rows have none: no fit bars, no line).
        const prio = (Array.isArray(p.priorities) && p.priorities.length && (r.recommendations || []).some((x) => x.fit)) ? p.priorities : [];

        host.innerHTML = `
            <div class="wtp-hero">
                <h2>${esc(CAT_E[top.category] || '🌱')} Best for your field</h2>
                <p class="h-win">${esc(top.crop || '')}</p>
                ${top.varieties && (top.varieties.top || []).length ? `<p class="h-vars">Varieties to try: ${top.varieties.top.map((f) => `<a href="${esc(f.url)}" target="_blank" rel="noopener">${esc(f.name)}${f.alias ? ' (' + esc(f.alias) + ')' : ''}</a>`).join(', ')}</p>` : ''}
                <p class="h-why">${esc(sweep(top.why))}${top.window ? ' Plant it ' + esc(top.window) + '.' : ''}${top.daysToHarvest ? ' Harvest in about ' + esc(String(Math.round(Number(top.daysToHarvest)))) + ' days' + (top.harvestWindow ? ' (' + esc(top.harvestWindow) + ')' : '') + (top.harvestRisk && top.harvestRisk.note ? '. ' + esc(sweep(top.harvestRisk.note)) : '.') : ''}</p>
                <div class="wtp-chips">
                    ${top.harvestRisk && top.harvestRisk.score != null ? `<span class="wtp-chip">${riskWord(top.harvestRisk.score)} risk at harvest</span>` : ''}
                    ${top.market ? `<span class="wtp-chip">💰 About ₱${esc(Number(top.market.p).toFixed(2))}${top.market.unit === 'kg' ? '/kg' : ''} lately</span>` : ''}
                    <span class="wtp-chip">📍 ${p.lotName ? esc(p.lotName) + ' · ' : ''}${esc(p.location || '')}${p.country && p.country !== (OPT && OPT.country) ? ' · ' + esc(rulesFor(p.country).name || p.country) : ''}</span>
                    <span class="wtp-chip">🗓️ ${esc(month)}</span>
                    ${p.ph && p.ph !== 'unsure' ? `<span class="wtp-chip">pH ${esc(p.phValue || String((OPT && OPT.phLevels && OPT.phLevels[p.ph]) || p.ph).split(' — ')[0].toLowerCase())}</span>` : ''}
                    ${[].concat(p.soilExtra || []).filter((k) => k && k !== 'unsure').map((k) => `<span class="wtp-chip">🧪 ${esc(String((OPT && OPT.soilExtras && OPT.soilExtras[k]) || k).split(' — ')[0])}</span>`).join('')}
                    ${[].concat(p.waterLook || []).filter((k) => k && k !== 'unsure').map((k) => `<span class="wtp-chip">💧 ${esc(String((OPT && OPT.waterLooks && OPT.waterLooks[k]) || k).split(' — ')[0])}</span>`).join('')}
                    ${p.elevation ? `<span class="wtp-chip">${esc(String((OPT && OPT.elevations && OPT.elevations[p.elevation]) || p.elevation).split(' — ')[0])}</span>` : ''}
                    <span class="wtp-chip">Confidence: ${esc(r.confidence || 'moderate')}</span>
                    ${item.charged ? `<span class="wtp-chip">${item.charged} credits</span>` : ''}
                </div>
            </div>

            <div class="wtp-card">
                <h3>Best crops, in order</h3>
                ${prio.length ? `<p class="wp-prio-line">Scored by what matters to you: ${prio.map((k, i) => `${i ? ' › ' : ''}<b>${PRIO_E[k] || ''} ${esc(shortOf((OPT && OPT.priorities && OPT.priorities[k]) || k))}</b>`).join('')}</p>` : ''}
                ${Array.isArray(p.exclude) && p.exclude.length ? `<p class="wp-left-out">You left out: ${esc(p.exclude.map((k) => shortOf((OPT && OPT.families && OPT.families[k]) || k)).join(', '))}.</p>` : ''}
                ${(r.recommendations || []).map((x, i) => `
                    <div class="wp-rec" style="transition-delay:${Math.min(i, 10) * 70}ms">
                        <div class="wp-rec-top">
                            <span class="wp-rank">${esc(String(x.rank || i + 1))}</span>
                            <b>${esc(CAT_E[x.category] || '🌱')} ${esc(x.crop || '')}</b>
                            <span class="wp-cat">${esc(x.category || '')}</span>
                            ${x.farmerAsked ? `<span class="wp-ask">You asked</span>` : ''}
                            ${x.window ? `<span class="wp-win">${esc(x.window)}</span>` : ''}
                        </div>
                        <div class="wp-track"><span class="wp-fill" style="width:${Math.max(4, Math.min(100, Number(x.score) || 0))}%;transition-delay:${120 + Math.min(i, 10) * 70}ms"></span></div>
                        <p class="wp-why">${esc(sweep(x.why))}</p>
                        ${x.watch ? `<p class="wp-watch">⚠️ ${esc(x.watch)}</p>` : ''}
                        ${x.daysToHarvest || (x.harvestRisk && x.harvestRisk.score != null) || x.market ? `
                        <div class="wp-hv">
                            ${x.daysToHarvest ? `<span>⏱ Harvest in ~${esc(String(Math.round(Number(x.daysToHarvest))))} days${x.harvestWindow ? ' · ' + esc(x.harvestWindow) : ''}</span>` : ''}
                            ${x.harvestRisk && x.harvestRisk.score != null ? `<span class="wp-hr is-${riskBand(x.harvestRisk.score)}">${riskIcon(x.harvestRisk.kind)} ${riskWord(x.harvestRisk.score)} risk at harvest${x.harvestRisk.kind && x.harvestRisk.kind !== 'none' ? ' · ' + esc(x.harvestRisk.kind) : ''}</span>` : ''}
                            ${mkLine(x)}
                        </div>
                        ${x.harvestRisk && x.harvestRisk.note ? `<p class="wp-hr-note">${esc(sweep(x.harvestRisk.note))}</p>` : ''}` : ''}
                        ${x.fit && prio.length ? `<div class="wp-fit">${prio.map((k) => `<span class="wp-fit-k" title="${esc(shortOf((OPT && OPT.priorities && OPT.priorities[k]) || k))}: ${esc(String(Math.round(Number(x.fit[k]) || 0)))}/100"><i style="--v:${Math.max(0, Math.min(100, Number(x.fit[k]) || 0))}%"></i><small>${PRIO_E[k] || ''} ${esc(PRIO_SHORT[k] || shortOf((OPT && OPT.priorities && OPT.priorities[k]) || k))}</small><b>${esc(String(Math.round(Number(x.fit[k]) || 0)))}</b></span>`).join('')}</div>` : ''}
                        ${varLine(x)}
                    </div>`).join('')}
            </div>

            ${(r.surprises || []).length ? `
            <div class="wtp-card">
                <h3>🎁 Surprise picks</h3>
                <p class="wp-prio-line">Not common here, but they fit your land, the weather and what matters to you.</p>
                ${(r.surprises || []).map((x, i) => `
                    <div class="wp-sur" style="transition-delay:${i * 80}ms">
                        <div class="wp-sur-top">
                            <b>${esc(CAT_E[x.category] || '✨')} ${esc(x.crop || '')}</b>
                            <span class="wp-cat">${esc(x.category || '')}</span>
                            ${x.window ? `<span class="wp-win">${esc(x.window)}</span>` : ''}
                        </div>
                        <p class="wp-sur-why">${esc(sweep(x.why))}</p>
                        ${x.daysToHarvest || (x.harvestRisk && x.harvestRisk.score != null) || x.market ? `
                        <div class="wp-hv">
                            ${x.daysToHarvest ? `<span>⏱ Harvest in ~${esc(String(Math.round(Number(x.daysToHarvest))))} days${x.harvestWindow ? ' · ' + esc(x.harvestWindow) : ''}</span>` : ''}
                            ${x.harvestRisk && x.harvestRisk.score != null ? `<span class="wp-hr is-${riskBand(x.harvestRisk.score)}">${riskIcon(x.harvestRisk.kind)} ${riskWord(x.harvestRisk.score)} risk at harvest${x.harvestRisk.kind && x.harvestRisk.kind !== 'none' ? ' · ' + esc(x.harvestRisk.kind) : ''}</span>` : ''}
                            ${mkLine(x)}
                        </div>
                        ${x.harvestRisk && x.harvestRisk.note ? `<p class="wp-hr-note">${esc(sweep(x.harvestRisk.note))}</p>` : ''}` : ''}
                        ${varLine(x)}
                    </div>`).join('')}
            </div>` : ''}

            ${timeline(r, p)}

            ${(r.avoid || []).length ? `
            <div class="wtp-card">
                <h3>Better to avoid here</h3>
                ${(r.avoid || []).map((a) => `
                    <div class="wtp-win-row is-no"><b>⛔ ${esc(a.crop || '')}:</b><span>${esc(a.why || '')}</span></div>`).join('')}
            </div>` : ''}

            ${(p.fieldProfileSaid || []).length && window.aneeFieldCard ? `
            <div class="wtp-card">
                <h3>🌾 ${p.lotName ? 'The lot: ' + esc(p.lotName) : 'Your field in detail'}</h3>
                ${window.aneeFieldCard.said(p.fieldProfileSaid)}
            </div>` : ''}

            <div class="wtp-card">
                <h3>In plain words</h3>
                <p class="wtp-plain">${esc(sweep(r.summary))}</p>
                ${(r.dataGaps || []).length ? `
                    <h3 class="mt-4">What Anee could not know</h3>
                    <ul class="wtp-gap">${(r.dataGaps || []).map((g) => `<li>${esc(g)}</li>`).join('')}</ul>` : ''}
            </div>

            <div class="wtp-card">
                <h3>🧭 A guide, not a promise</h3>
                <p class="wtp-fine">No analysis knows your soil like a soil test, or how the season will turn out. This is a shortlist of crops that match what you told us, which beats planting by habit. Once you choose, When to Plant can help with the timing.</p>
            </div>

            <div class="wtp-acts">
                <button type="button" class="btn btn-primary w-full" data-wp-attach>
                    ${OPT && OPT.aneeFace ? `<img class="wtp-anee-face" src="${esc(OPT.aneeFace)}" alt="">` : '🤖'} Attach to Anee
                </button>
                ${mode === 'fresh' ? `<button type="button" class="btn btn-white w-full" data-wp-again>🌱 Run another analysis</button>` : ''}
                <button type="button" class="btn btn-white w-full" data-wp-delete>🗑 Delete</button>
            </div>`;

        host.hidden = false;
        if (!quiet) {
            requestAnimationFrame(() => requestAnimationFrame(() => host.classList.add('is-drawn')));
            host.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        host.querySelector('[data-wp-attach]').addEventListener('click', () => {
            if (item.savedId) window.location.href = U.anee + '?analysis=' + item.savedId;
        });
        host.querySelector('[data-wp-again]')?.addEventListener('click', () => { VIEW_MODE = null; closeView(); wizardBack(); });
        host.querySelector('[data-wp-delete]').addEventListener('click', async () => {
            const delId = item.savedId;
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Delete this analysis?', message: 'It goes to the Trash, where you can restore it for 30 days. The credits it used are not returned.', confirmText: 'Delete', danger: true })
                : confirm('Delete this analysis?');
            if (!ok) return;
            try {
                const res = await api(U.del(delId), { method: 'DELETE' });
                toast(res.message);
                host.hidden = true;
                VIEW_MODE = null;
                closeView();
                loadSaved().catch(() => {});
                if (mode === 'fresh') wizardBack();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    /* ---------------- saved ----------------
       A page at a time (twenty), more as the farmer scrolls, and a search
       that asks the server -- a shelf of a hundred reports must not arrive
       whole, and a name must be findable. */
    const SHELF = { page: 1, hasMore: false, q: '', busy: false };
    const rowHtml = (r) => `
            <button type="button" class="wtp-saved" data-saved="${r.id}" data-trash-row>
                <span class="grow min-w-0"><b>${esc(r.title)}</b><small>${r.description ? esc(r.description) + ' · ' : ''}${esc(r.at)} · ${r.credits} credits</small>${window.userTags ? window.userTags.chips(r.tags) : ''}</span>
                <span role="button" tabindex="0" class="wtp-pen" data-meta="${r.id}" title="Edit name and description" aria-label="Edit ${esc(r.title)}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:.85rem;height:.85rem"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </span>${window.aneeTrash ? window.aneeTrash.btn(r.id, r.title) : ''}
                <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>`;
    async function loadSaved(more) {
        if (SHELF.busy) return;
        SHELF.busy = true;
        try {
            const page = more ? SHELF.page + 1 : 1;
            const res = await api(U.list + '?page=' + page + '&q=' + encodeURIComponent(SHELF.q) + '&_=' + Date.now(), { method: 'GET' });
            const rows = res.data.rows || [];
            SHELF.page = page;
            SHELF.hasMore = !!res.data.hasMore;
            WP_ROWS = more ? WP_ROWS.concat(rows) : rows;
            const html = rows.map(rowHtml).join('');
            if (more) $id('wpSavedList').insertAdjacentHTML('beforeend', html); else $id('wpSavedList').innerHTML = html;
            $id('wpSavedEmpty').classList.toggle('hidden', WP_ROWS.length > 0);
            $id('wpSavedEmpty').querySelector('p.font-bold').textContent = SHELF.q ? 'No matches' : 'Nothing saved yet';
            $id('wpSavedMore').hidden = !SHELF.hasMore;
        } finally { SHELF.busy = false; }
    }
    let shelfTimer = null;
    $id('wpSavedSearch').addEventListener('input', () => {
        clearTimeout(shelfTimer);
        shelfTimer = setTimeout(() => { SHELF.q = $id('wpSavedSearch').value.trim(); loadSaved(false).catch((err) => toast(err.message, 'error')); }, 280);
    });
    if ('IntersectionObserver' in window) {
        new IntersectionObserver((entries) => { if (entries.some((e) => e.isIntersecting) && SHELF.hasMore && !SHELF.busy) loadSaved(true).catch(() => {}); }, { rootMargin: '200px' }).observe($id('wpSavedMore'));
    }

    let WP_ROWS = [];
    let WP_META_ID = null;
    document.addEventListener('click', async (e) => {
        const saveBtn = e.target.closest('#wpMetaSave');
        if (saveBtn && WP_META_ID !== null) {
            saveBtn.disabled = true;
            try {
                const res = await api(WP_META_URL, { method: 'POST', body: {
                    id: WP_META_ID,
                    title: $id('wpMetaTitle').value.trim(),
                    description: $id('wpMetaDesc').value.trim(),
                    tags: window.userTags ? window.userTags.value($id('wpMetaTags')) : [],
                } });
                toast(res.message);
                closeSheet('wpMetaSheet');
                loadSaved().catch(() => {});
            } catch (err) { toast(err.message, 'error'); }
            finally { saveBtn.disabled = false; }
            return;
        }
        // The bin on a saved row (2026-10-23): to the Trash, through the tool's own delete.
        const bin = e.target.closest('[data-trash]');
        if (bin && bin.closest('#wpSavedList')) {
            e.stopPropagation();
            const row = bin.closest('.wtp-saved');
            window.aneeTrash.run(bin, U.del(bin.dataset.trash), { title: row ? (row.querySelector('b') || {}).textContent : '', what: 'analysis', paid: true, onDone: () => loadSaved(false).catch(() => {}) });
            return;
        }
        const pen = e.target.closest('[data-meta]');
        if (pen && pen.closest('#wpSavedList')) {
            e.stopPropagation();
            const r = WP_ROWS.find((x) => String(x.id) === pen.getAttribute('data-meta'));
            if (!r) return;
            WP_META_ID = r.id;
            $id('wpMetaTitle').value = r.title || '';
            $id('wpMetaDesc').value = r.description || '';
            if (window.userTags) window.userTags.set($id('wpMetaTags'), r.tags || []);
            openSheet('wpMetaSheet');
            return;
        }
        const b = e.target.closest('[data-saved]');
        if (!b) return;
        try {
            const res = await api(U.one(b.getAttribute('data-saved')), { method: 'GET' });
            openView({
                report: res.data.report, params: res.data.params,
                charged: res.data.credits, savedId: res.data.id,
            }, 'saved');
        } catch (err) { toast(err.message, 'error'); }
    });

    /* ---------------- tabs ---------------- */
    const tab = (which) => {
        $id('wpGen').classList.toggle('hidden', which !== 'gen');
        $id('wpSavedPane').classList.toggle('hidden', which !== 'saved');
        $id('wpTabGen').classList.toggle('is-on', which === 'gen');
        $id('wpTabSaved').classList.toggle('is-on', which === 'saved');
        if (which === 'saved') loadSaved().catch((err) => toast(err.message, 'error'));
    };
    $id('wpTabGen').addEventListener('click', () => tab('gen'));
    $id('wpTabSaved').addEventListener('click', () => tab('saved'));

    if (window.api) boot();
    else window.addEventListener('load', boot, { once: true });
})();
</script>
@endsection

@push('sheets')
{{-- Rename a saved analysis and describe it in your own words. --}}
@include('partials.user-tags')
@include('partials.trash-row')
<div class="sheet hidden" id="wpMetaSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Edit this analysis</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="wpMetaTitle">Name</label>
            <input type="text" id="wpMetaTitle" class="form-input" maxlength="191">
        </div>
        <div>
            <label class="form-label" for="wpMetaDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="wpMetaDesc" class="form-textarea" rows="3" maxlength="2000"></textarea>
        </div>
        <div>
            <span class="form-label">Tags <span class="text-gray-400 font-normal">(optional)</span></span>
            <div class="ut-mount" id="wpMetaTags"></div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="wpMetaSave">Save changes</button>
    </div>
</div>
@endpush

@push('head')
<style>
    .wtp-pen { flex: none; width: 1.6rem; height: 1.6rem; border-radius: .45rem; display: inline-flex;
        align-items: center; justify-content: center; color: var(--color-gray-400); }
    .wtp-pen:hover { color: var(--color-brand-700); background: var(--color-brand-50); }
    html.dark .wtp-pen:hover { background: rgb(107 159 61 / .18); color: #a5c97e; }
</style>
@endpush
