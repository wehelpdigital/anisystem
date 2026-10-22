@extends('layouts.app')
@section('title', 'Variety Ranking')
{{-- A tool page: no bottom tab bar on a phone and no footer (2026-10-15, the owner). --}}
@section('body-class', 'hide-tabbar no-footer')
@section('page-title', 'Variety Ranking')
@section('page-subtitle', 'Find the best variety for your field')

@section('back', \App\Support\BackTo::url(route('app.dashboard')))
{{-- Back is the dashboard, or wherever the door that opened this page said
     it stood (?from=, App\Support\BackTo). It used to be history.back()
     whenever the referrer was on this site, which after a reload, a
     redirect or a page's own link went back to this page itself. --}}


@section('content')
<style>
    /* ---- VARIETY RESEARCH ----------------------------------------------
       The third of the Quick Tools: the sisters' wizard walk, veil and
       shelf, and the wtp-* dress copied whole so the three read as one
       family — but this one searches the web, and its answer is a ranked
       comparison with four bars per variety. */
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
    @media (max-width: 639px) { .wtp-choices.is-two { grid-template-columns: 1fr; } }
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

    /* The lot (2026-10-16): what it answered is hidden here, and the review
       says where the rest came from. */
    .va-blk[hidden], .wtp-prob[hidden] { display: none; }
    /* A step the lot answered whole has no dot: it folds away. */
    .wtp-dot.is-skip { width: 0; margin-left: -.35rem; opacity: 0; }
    .va-pop { animation: vaPop .28s cubic-bezier(.22,1,.36,1) both; }
    @keyframes vaPop { from { opacity: 0; transform: translateY(6px); } }
    .va-lot { margin: .9rem 0 1rem; padding: .8rem .85rem; border-radius: 1rem; background: linear-gradient(160deg, #fbfdf8, #f2f8ea); border: 1px solid #e0ebd3; }
    .va-lot[hidden] { display: none; }
    .va-lot-h { display: flex; flex-wrap: wrap; align-items: baseline; gap: .15rem .5rem; margin-bottom: .55rem; font-size: .86rem; line-height: 1.4; }
    .va-lot-h b { font-weight: 800; color: #2d5016; }
    .va-lot-h span { min-width: 0; font-size: .76rem; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .va-lot-none { font-size: .8rem; line-height: 1.5; color: var(--color-gray-500); }
    .va-lot-edit { display: inline-flex; align-items: center; gap: .4rem; min-height: 2.5rem; margin-top: .7rem; padding: .4rem .95rem; border-radius: 999px; font-size: .82rem; font-weight: 800;
        color: #3d6823; background: var(--color-white); border: 1.5px solid #b9d39c; cursor: pointer;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .va-lot-edit:hover { background: #f3f8ec; border-color: #86b556; }
    .va-lot-edit svg { width: .95rem; height: .95rem; }
    html.dark .va-lot { background: #151b12; border-color: #2b3a1c; }
    html.dark .va-lot-h b { color: #a5c97e; }
    html.dark .va-lot-edit { background: #10150d; color: #a5c97e; border-color: #3d5a26; }
    html.dark .va-lot-edit:hover { background: #1c2914; }
    @media (prefers-reduced-motion: reduce) { .va-pop { animation: none; } .va-lot-edit { transition: none; } }
    html.sm-still .va-pop { animation: none; }

    .wtp-nav { display: flex; gap: .6rem; margin-top: 1.1rem; }
    .wtp-run { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%;
        padding: .85rem 1rem; border-radius: 1rem; color: #fff; font-weight: 800; font-size: .95rem;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 30%, #3d6823 55%, #6b9f3d 80%, #8fc96a);
        background-size: 260% 100%; animation: wtpTide 5.5s ease-in-out infinite alternate;
        box-shadow: 0 10px 22px -12px rgb(61 104 35 / .65); }
    .wtp-run:disabled { opacity: .6; }
    @keyframes wtpTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }

    /* The crop tag and its sheet — the lot form's dress, copied whole so a
       farmer meets the same picker everywhere a crop is chosen. */
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

    /* The varieties the farmer is weighing: typed one at a time, worn as chips. */
    .va-add { display: flex; gap: .5rem; }
    .va-add .form-input { flex: 1 1 auto; min-width: 0; }
    .va-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .7rem; }
    .va-chips:empty { display: none; }
    .va-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .5rem .35rem .7rem;
        border-radius: 999px; font-size: .8rem; font-weight: 700; color: var(--color-brand-800);
        background: var(--color-brand-50); border: 1px solid var(--color-brand-200);
        animation: vaChipIn .28s cubic-bezier(.22,1,.36,1) both; }
    @keyframes vaChipIn { from { opacity: 0; transform: scale(.85); } to { opacity: 1; transform: none; } }
    .va-chip button { width: 1.25rem; height: 1.25rem; border-radius: 999px; display: inline-flex; align-items: center;
        justify-content: center; color: var(--color-brand-700); font-size: .8rem; line-height: 1; }
    .va-chip button:hover { background: rgb(74 124 42 / .15); }
    .va-none { font-size: .78rem; color: var(--color-gray-400); font-style: italic; }
    .va-anee { display: flex; align-items: center; gap: .6rem; margin-top: .6rem; padding: .6rem .75rem; border-radius: .8rem;
        background: #fdf7e6; border: 1px solid #f3dfa4; font-size: .78rem; color: #7a5a12; line-height: 1.45; }
    .va-anee img { width: 1.6rem; height: 1.6rem; border-radius: 999px; object-fit: cover; flex: none; }
    html.dark .va-chip { background: #22301a; border-color: #3d5226; color: #cfe6b8; }
    html.dark .va-chip button { color: #a5c97e; }
    html.dark .va-anee { background: #2a2413; border-color: #4a3d16; color: #e0c26a; }

    /* The priorities, ranked: an ordered list with arrows that swap rows;
       the row slides to its new place rather than jumping. */
    .va-rank { display: grid; gap: .45rem; }
    .va-row { display: flex; align-items: center; gap: .6rem; padding: .6rem .7rem; border-radius: .9rem;
        border: 1.5px solid var(--color-gray-200); background: var(--color-white);
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s, background .28s; }
    .va-row.is-moving { transition: none; }
    .va-row .r-n { flex: none; width: 1.7rem; height: 1.7rem; border-radius: 999px; display: inline-flex;
        align-items: center; justify-content: center; font-size: .8rem; font-weight: 800;
        background: var(--color-brand-600); color: #fff; }
    .va-row:nth-child(2) .r-n { background: var(--color-brand-500); }
    .va-row:nth-child(3) .r-n { background: var(--color-brand-400); }
    .va-row:nth-child(4) .r-n { background: var(--color-gray-400); }
    .va-row .r-e { font-size: 1.25rem; flex: none; }
    .va-row .r-t { flex: 1 1 auto; min-width: 0; }
    .va-row .r-t b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .va-row .r-t small { display: block; font-size: .7rem; color: var(--color-gray-500); line-height: 1.35; }
    .va-row .r-w { flex: none; font-size: .68rem; font-weight: 800; color: var(--color-brand-700);
        background: var(--color-brand-50); padding: .15rem .45rem; border-radius: 999px; }
    .va-row .r-btns { flex: none; display: flex; flex-direction: column; gap: .15rem; }
    .va-row .r-btns button { width: 1.6rem; height: 1.35rem; border-radius: .4rem; display: inline-flex; align-items: center;
        justify-content: center; color: var(--color-gray-500); background: var(--color-gray-100); }
    .va-row .r-btns button:hover:not(:disabled) { background: var(--color-brand-100); color: var(--color-brand-800); }
    .va-row .r-btns button:disabled { opacity: .3; }
    .va-row .r-btns svg { width: .8rem; height: .8rem; }
    html.dark .va-row { background: #151b12; border-color: #2b3a1c; }
    html.dark .va-row .r-t b { color: #e8efe1; }
    html.dark .va-row .r-btns button { background: #222b1a; color: #93a684; }
    html.dark .va-row .r-w { background: #22301a; color: #a5c97e; }

    /* ---- THE REPORT ---- */
    .wtp-report { display: grid; gap: .9rem; }
    /* A grid item's automatic minimum is its content's width — a chart's
       scroll box would widen the whole report without this. */
    .wtp-report > * { min-width: 0; max-width: 100%; }
    .wtp-hero { border-radius: 1.1rem; padding: 1.1rem 1.2rem; color: #fff;
        background: linear-gradient(130deg, #4a7c2a, #2d5016 70%); }
    .wtp-hero h2 { font-size: 1.15rem; font-weight: 800; margin-bottom: .15rem; }
    .wtp-hero .h-win { font-size: 1.5rem; font-weight: 800; letter-spacing: .01em; }
    .wtp-hero .h-by { font-size: .78rem; opacity: .85; margin-top: .1rem; }
    .wtp-hero .h-best { display: flex; flex-wrap: wrap; gap: .3rem .9rem; margin-top: .55rem; font-size: .78rem; opacity: .95; }
    .wtp-hero .h-best b { font-weight: 800; }
    .va-type { display: inline-block; vertical-align: middle; font-size: .58rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        padding: .1rem .4rem; border-radius: 999px; background: #fef3c7; color: #92400e; margin-left: .3rem; }
    html.dark .va-type { background: #3b2f0e; color: #fcd34d; }
    .va-group-sub { font-size: .76rem; color: var(--color-gray-500); margin: -.3rem 0 .5rem; line-height: 1.45; }

    /* THE REPORT, FULL SCREEN when it lands: nothing else on the page —
       not the tabs, not the wizard — until the ✕. The same report is then
       left on the page underneath. */
    .va-view { position: fixed; inset: 0; z-index: 90; background: var(--color-gray-50); overflow-y: auto; -webkit-overflow-scrolling: touch;
        opacity: 0; transform: translateY(12px); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .va-view.is-on { opacity: 1; transform: none; }
    .va-view-bar { position: sticky; top: 0; z-index: 2; display: flex; align-items: center; gap: .6rem; padding: .7rem .9rem;
        padding-top: max(.7rem, env(safe-area-inset-top)); background: rgb(250 250 248 / .92); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        border-bottom: 1px solid var(--color-gray-200); }
    .va-view-bar b { flex: 1 1 auto; min-width: 0; font-size: .95rem; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .va-view-x { flex: none; width: 2.2rem; height: 2.2rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-white); border: 1px solid var(--color-gray-200); color: var(--color-gray-700); font-size: 1rem; cursor: pointer; }
    .va-view-x:hover { background: var(--color-gray-100); }
    .va-view-body { max-width: 42rem; margin: 0 auto; padding: 1rem 1rem calc(2rem + env(safe-area-inset-bottom)); }
    html.va-view-lock { overflow: hidden; }
    html.dark .va-view { background: #0d110a; }
    html.dark .va-view-bar { background: rgb(13 17 10 / .92); border-color: #2b3a1c; }
    html.dark .va-view-bar b { color: #e8efe1; }
    html.dark .va-view-x { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    @media (prefers-reduced-motion: reduce) { .va-view { transition: none; transform: none; } }
    .wtp-hero .h-why { font-size: .84rem; opacity: .92; line-height: 1.55; margin-top: .45rem; }
    .wtp-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .6rem; }
    .wtp-chip { font-size: .68rem; font-weight: 700; padding: .18rem .55rem; border-radius: 999px;
        background: rgb(255 255 255 / .18); }

    .wtp-card { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        padding: 1rem 1.1rem; }
    .wtp-card h3 { font-weight: 800; font-size: .92rem; color: var(--color-gray-900); margin-bottom: .6rem; }

    /* What the farmer said matters, worn as an ordered strip. */
    .va-order { display: flex; flex-wrap: wrap; gap: .35rem; }
    .va-order span { font-size: .72rem; font-weight: 700; padding: .25rem .6rem; border-radius: 999px;
        background: var(--color-brand-50); color: var(--color-brand-800); border: 1px solid var(--color-brand-200); }
    .va-order span i { font-style: normal; opacity: .65; margin-left: .25rem; }

    /* One variety: name, breeder, the four bars and the overall. */
    .va-rec { padding: .8rem 0; border-bottom: 1px solid var(--color-gray-100);
        opacity: 0; transform: translateY(6px); transition: all .45s cubic-bezier(.22,1,.36,1); }
    .va-rec:last-child { border-bottom: 0; padding-bottom: .2rem; }
    .wtp-report.is-drawn .va-rec { opacity: 1; transform: none; }
    .va-rec-top { display: flex; align-items: center; gap: .5rem; }
    .wp-rank { flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: inline-flex;
        align-items: center; justify-content: center; font-size: .74rem; font-weight: 800;
        background: var(--color-brand-100); color: var(--color-brand-800); }
    .va-rec-top .v-name { min-width: 0; flex: 1 1 auto; }
    .va-rec-top .v-name b { display: block; font-size: .92rem; color: var(--color-gray-900); }
    .va-rec-top .v-name small { display: block; font-size: .7rem; color: var(--color-gray-400); }
    .va-overall { flex: none; text-align: center; line-height: 1; }
    .va-overall b { display: block; font-size: 1.15rem; font-weight: 800; color: var(--color-brand-700); }
    .va-overall small { font-size: .58rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .va-facts { display: flex; flex-wrap: wrap; gap: .3rem; margin: .45rem 0 .5rem; }
    .va-fact { font-size: .66rem; font-weight: 700; padding: .14rem .5rem; border-radius: 999px;
        background: var(--color-gray-100); color: var(--color-gray-600); }
    .va-bars { display: grid; grid-template-columns: repeat(2, 1fr); gap: .35rem .8rem; }
    @media (min-width: 640px) { .va-bars { grid-template-columns: repeat(4, 1fr); } }
    .va-bar small { display: flex; justify-content: space-between; font-size: .62rem; font-weight: 700; color: var(--color-gray-500); }
    .va-bar small i { font-style: normal; color: var(--color-gray-700); }
    .wp-track { height: 6px; border-radius: 999px; background: var(--color-gray-100); margin-top: .2rem; overflow: hidden; }
    .wp-fill { display: block; height: 100%; border-radius: 999px; background: var(--color-brand-500);
        transform-origin: left; transform: scaleX(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wp-fill.is-top { background: var(--color-brand-700); }
    .wtp-report.is-drawn .wp-fill { transform: scaleX(1); }
    .va-pm { display: grid; gap: .35rem; margin-top: .55rem; }
    @media (min-width: 640px) { .va-pm { grid-template-columns: 1fr 1fr; } }
    .va-pm ul { font-size: .76rem; line-height: 1.5; padding-left: 1rem; }
    .va-pm .is-plus li { list-style: '✓ '; color: #2d5016; }
    .va-pm .is-minus li { list-style: '✗ '; color: #92400e; }
    .wp-why { font-size: .8rem; color: var(--color-gray-600); line-height: 1.55; margin-top: .45rem; }
    /* Real harvests (2026-10-17, the owner: "we need real people data"): derbies and farmers. */
    .va-field { display: grid; gap: .3rem; margin: .1rem 0 .55rem; }
    .va-field > b { font-size: .7rem; font-weight: 800; letter-spacing: .02em; color: #2d5016; }
    .va-fr { display: block; padding: .35rem .55rem; border-radius: .6rem; font-size: .74rem; font-weight: 700; line-height: 1.4; color: #1f3510; background: #f1f8e9; border: 1px solid #dcebc9; }
    .va-fr em { font-style: normal; font-size: .62rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; margin-right: .3rem; padding: .05rem .4rem; border-radius: 999px; color: #fff; background: #6b9f3d; }
    .va-fr.is-farmer em { background: #b45309; }
    .va-fr.is-trial em { background: #2563eb; }
    .va-fr small { display: block; margin-top: .1rem; font-size: .64rem; font-weight: 600; color: var(--color-gray-500); }
    .va-derby { padding: .7rem .75rem; border-radius: .9rem; background: #fffdf5; border: 1px solid #f5e3a8; }
    .va-derby + .va-derby { margin-top: .55rem; }
    .va-derby > b { display: block; font-size: .86rem; font-weight: 800; color: #713f12; }
    .va-derby > small { display: block; font-size: .7rem; color: #a16207; margin-top: .1rem; }
    .va-derby ol { margin: .45rem 0 0; padding: 0; list-style: none; counter-reset: dv; display: grid; gap: .2rem; }
    .va-derby li { counter-increment: dv; display: flex; gap: .5rem; align-items: baseline; font-size: .78rem; color: #422006; }
    .va-derby li::before { content: counter(dv) '.'; flex: none; width: 1.3rem; font-weight: 800; color: #a16207; }
    .va-derby li span { flex: 1; min-width: 0; overflow-wrap: anywhere; }
    .va-derby li b { flex: none; font-weight: 800; }
    .va-in { display: inline-block; margin-left: .3rem; padding: .02rem .45rem; border-radius: 999px; font-size: .62rem; font-weight: 800; vertical-align: middle; color: #166534; background: #dcfce7; }
    .va-in.is-out { color: #6b7280; background: #f3f4f6; }
    html.dark .va-field > b { color: #a5c97e; }
    html.dark .va-fr { color: #dce8cf; background: #1a2414; border-color: #2b3a1c; }
    html.dark .va-derby { background: #241d10; border-color: #5c4a24; }
    html.dark .va-derby > b, html.dark .va-derby li { color: #fde68a; }
    html.dark .va-derby > small, html.dark .va-derby li::before { color: #e0c26a; }
    html.dark .va-in { color: #bbf7d0; background: #14351f; }
    html.dark .va-in.is-out { color: #b7c2ad; background: #22301a; }
    /* The variety records (2026-10-14): a ranked variety's own record, the
       by-the-numbers shortlist, and the wizard's picks from the records. */
    .va-onfile { display: flex; flex-wrap: wrap; align-items: baseline; gap: .2rem .45rem; margin-top: .5rem; padding: .45rem .6rem; border-radius: .7rem;
        font-size: .72rem; line-height: 1.45; color: var(--color-gray-600); background: #f4f9ee; border: 1px solid #e1edd3; text-decoration: none;
        transition: border-color .28s cubic-bezier(.22,1,.36,1); }
    .va-onfile:hover { border-color: #a8cc7e; }
    .va-onfile b { color: #2d5016; font-weight: 800; }
    .va-onfile i { margin-left: auto; font-style: normal; font-weight: 800; color: var(--color-brand-700); white-space: nowrap; }
    .va-file { display: grid; gap: .5rem; margin-top: .7rem; }
    @media (min-width: 640px) { .va-file { grid-template-columns: 1fr 1fr; } }
    .va-file-row { display: flex; flex-direction: column; gap: .25rem; padding: .65rem .75rem; border-radius: .9rem; background: #fff; border: 1px solid var(--color-gray-100);
        text-decoration: none; color: inherit; transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .va-file-row:hover { border-color: #a8cc7e; transform: translateY(-2px); }
    .va-file-row b { font-size: .86rem; color: var(--color-gray-900); }
    .va-file-row small { font-size: .7rem; color: var(--color-gray-500); }
    .va-file-row .f-in { align-self: flex-start; font-size: .6rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; padding: .1rem .45rem; border-radius: 999px; color: #2d5016; background: #e4f1d6; }
    .va-file-all { display: inline-block; margin-top: .7rem; font-size: .78rem; font-weight: 800; color: var(--color-brand-700); text-decoration: underline; text-underline-offset: 3px; }
    /* The two ways to add a variety (2026-10-15). */
    .va-ways-h { margin-top: 1rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-700); }
    .va-ways-h + .va-ways { margin-top: .45rem; }
    html.dark .va-ways-h { color: #d5e3c5; }
    .va-ways { display: grid; gap: .7rem; margin-top: .9rem; }
    .va-way { display: flex; align-items: center; gap: .75rem; width: 100%; padding: .75rem .85rem; border-radius: 1rem; text-align: left;
        background: linear-gradient(135deg, #f3f9ec, #fff); border: 1.5px solid #cfe5b8; cursor: pointer;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .va-way:hover { border-color: #86b556; transform: translateY(-1px); box-shadow: 0 10px 22px -16px rgb(45 80 22 / .5); }
    .va-way-i { flex: none; width: 2.5rem; height: 2.5rem; border-radius: .8rem; display: grid; place-items: center; color: #fff; background: #4a7c2a; }
    .va-way-i svg { width: 1.2rem; height: 1.2rem; }
    .va-way-t { min-width: 0; display: grid; gap: .1rem; }
    .va-way-t b { font-size: .92rem; font-weight: 800; color: var(--color-gray-900); }
    .va-way-t small { font-size: .74rem; color: var(--color-gray-500); }
    .va-own-l { display: block; margin-bottom: .35rem; font-size: .74rem; font-weight: 800; color: var(--color-gray-600); }
    .va-find-filt { display: flex; gap: .35rem; margin-top: .6rem; }
    .va-find-filt button { padding: .3rem .75rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: var(--color-gray-600); background: var(--color-gray-100);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .va-find-filt button[aria-pressed="true"] { color: #fff; background: #4a7c2a; }
    .va-find-n { margin: .6rem 0 .3rem; font-size: .72rem; font-weight: 700; color: var(--color-gray-500); }
    .va-find-list { display: grid; gap: .35rem; padding-bottom: .5rem; }
    .va-find-r { display: flex; align-items: center; gap: .7rem; width: 100%; padding: .55rem .65rem; border-radius: .85rem; text-align: left;
        background: #fff; border: 1px solid var(--color-gray-200); cursor: pointer; transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .va-find-r:hover { border-color: #a8cc7e; }
    .va-find-r[aria-pressed="true"] { border-color: #4a7c2a; background: #f1f8e9; }
    .va-find-r .t { flex: 1 1 auto; min-width: 0; display: grid; gap: .08rem; }
    .va-find-r .t b { font-size: .86rem; font-weight: 800; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .va-find-r .t small { font-size: .7rem; color: #8a6a00; font-weight: 700; overflow-wrap: anywhere; }
    .va-find-r .t span { font-size: .7rem; color: var(--color-gray-500); }
    .va-find-r .ck { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: var(--color-gray-200);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .va-find-r .ck svg { width: .85rem; height: .85rem; opacity: 0; transition: opacity .2s ease; }
    .va-find-r[aria-pressed="true"] .ck { background: #4a7c2a; transform: scale(1.05); }
    .va-find-r[aria-pressed="true"] .ck svg { opacity: 1; }
    .va-find-more { justify-self: center; margin-top: .3rem; padding: .45rem 1rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--color-brand-700); background: var(--color-brand-50); }
    .va-find-foot { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .7rem 1rem calc(.7rem + env(safe-area-inset-bottom));
        border-top: 1px solid var(--color-gray-100); background: var(--color-white); }
    .va-find-foot span { font-size: .8rem; font-weight: 700; color: var(--color-gray-600); }
    html.dark .va-way { background: linear-gradient(135deg, #1c2616, #151b12); border-color: #2f4220; }
    html.dark .va-way-t b, html.dark .va-find-r .t b { color: #e8efe1; }
    html.dark .va-find-r { background: #151b12; border-color: #2b3a1c; }
    html.dark .va-find-r[aria-pressed="true"] { background: #1f2b15; border-color: #6b9f3d; }
    html.dark .va-find-r .t small { color: #e0c26a; }
    html.dark .va-find-filt button { background: #1f2a17; color: #b9caa8; }
    html.dark .va-find-filt button[aria-pressed="true"] { background: #4a7c2a; color: #fff; }
    html.dark .va-find-foot { background: #11160d; border-color: #222b1a; }
    html.dark .va-own-l { color: #b9caa8; }
    @media (prefers-reduced-motion: reduce) { .va-way, .va-find-r, .va-find-r .ck, .va-find-filt button { transition: none; } .va-way:hover { transform: none; } }
    html.dark .va-onfile { background: #1a2414; border-color: #2b3a1c; color: #b7c2ad; }
    html.dark .va-onfile b { color: #a5c97e; }
    html.dark .va-file-row { background: #151b12; border-color: #2b3a1c; }
    html.dark .va-file-row b { color: #e8efe1; }
    html.dark .va-file-row .f-in { background: #22301a; color: #cfe3b8; }
    @media (prefers-reduced-motion: reduce) { .va-onfile, .va-file-row { transition: none; } .va-file-row:hover { transform: none; } }
    html.dark .va-rec { border-color: #222b1a; }
    html.dark .va-rec-top .v-name b { color: #e8efe1; }
    html.dark .va-fact { background: #222b1a; color: #93a684; }
    html.dark .wp-track { background: #222b1a; }
    html.dark .wp-why { color: #b7c2ad; }
    html.dark .va-bar small i { color: #d5e3c5; }
    html.dark .va-pm .is-plus li { color: #a5c97e; }
    html.dark .va-pm .is-minus li { color: #e0b95c; }
    html.dark .va-order span { background: #22301a; color: #cfe6b8; border-color: #3d5226; }

    .wtp-win-row { display: block; padding: .6rem .7rem; border-radius: .7rem; margin-bottom: .45rem;
        font-size: .84rem; line-height: 1.55; border: 1px solid; }
    .wtp-win-row b { margin-right: .3rem; }
    .wtp-win-row.is-note { background: #f7fbf2; border-color: #cfe3b8; color: #2d5016; }
    .wtp-win-row.is-new { background: #eff6ff; border-color: #bfdbfe; color: #1e3a8a; }
    .wtp-win-row.is-no { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
    html.dark .wtp-win-row.is-note { background: #1c2913; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .wtp-win-row.is-new { background: #14203a; border-color: #1e3a8a; color: #bfdbfe; }
    html.dark .wtp-win-row.is-no { background: #2a1414; border-color: #4c1d1d; color: #fca5a5; }

    .va-cond { display: grid; gap: .5rem; }
    @media (min-width: 640px) { .va-cond { grid-template-columns: 1fr 1fr; } }
    .va-cond-box { border-radius: .8rem; padding: .65rem .75rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .va-cond-box b { display: block; font-size: .72rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--color-gray-500); margin-bottom: .2rem; }
    .va-cond-box p { font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); }
    .va-cond-box.is-wide { grid-column: 1 / -1; }
    .va-cond-box ul { font-size: .8rem; line-height: 1.5; color: #7f1d1d; padding-left: 1rem; }
    .va-cond-box ul li { list-style: disc; }
    html.dark .va-cond-box { background: #10150c; border-color: #222b1a; }
    html.dark .va-cond-box p { color: #d5e3c5; }
    html.dark .va-cond-box ul { color: #fca5a5; }

    .va-links { display: grid; gap: .3rem; }
    .va-link { display: flex; align-items: center; gap: .5rem; font-size: .78rem; color: var(--color-brand-700);
        text-decoration: none; padding: .35rem .5rem; border-radius: .6rem; min-width: 0; }
    .va-link:hover { background: var(--color-brand-50); }
    .va-link.is-plain { color: var(--color-gray-700); cursor: default; }
    .va-link.is-plain:hover { background: transparent; }
    html.dark .va-link.is-plain { color: #d5e3c5; }
    .va-link .l-t { min-width: 0; flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .va-link .l-h { flex: none; font-size: .66rem; color: var(--color-gray-400); max-width: 9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .va-nosearch { font-size: .78rem; color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: .7rem; padding: .55rem .7rem; }
    html.dark .va-link { color: #a5c97e; }
    html.dark .va-link:hover { background: #22301a; }
    html.dark .va-nosearch { background: #2a2413; border-color: #4a3d16; color: #e0c26a; }

    .wtp-gap { font-size: .78rem; color: var(--color-gray-500); line-height: 1.55; }
    .wtp-gap li { list-style: disc; margin-left: 1.1rem; }
    .wtp-fine { font-size: .72rem; color: var(--color-gray-400); line-height: 1.55; }
    .wtp-plain { font-size: .875rem; line-height: 1.65; color: var(--color-gray-700); }
    .va-mgmt li { list-style: none; position: relative; padding-left: 1.4rem; font-size: .82rem; line-height: 1.5; color: var(--color-gray-700); margin-bottom: .3rem; }
    .va-mgmt li::before { content: '🌱'; position: absolute; left: 0; top: 0; font-size: .8rem; }
    html.dark .wtp-plain, html.dark .va-mgmt li { color: #d5e3c5; }

    .wtp-acts { display: grid; gap: .5rem; }
    @media (min-width: 640px) { .wtp-acts { grid-template-columns: 1fr 1fr; } }

    .wtp-saved { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left;
        padding: .8rem .9rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
    .wtp-saved:hover { background: var(--color-gray-50); }
    .wtp-saved b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .wtp-saved small { color: var(--color-gray-400); font-size: .72rem; }

    .wtp-anee-face { width: 1.15rem; height: 1.15rem; border-radius: 999px; object-fit: cover; }

    html.dark .wtp-tab { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .wtp-tab.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .wtp-quote { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; }
    html.dark .wtp-quote b { color: #cfe6b8; }
    html.dark .q-title { color: #cfe6b8; }
    html.dark .q-hint, html.dark .q-c { color: #a8bd93; }
    html.dark .q-card { background: rgb(255 255 255 / .05); border-color: #2b3a1c; color: #a8bd93; }
    html.dark .wtp-q { color: #e8efe1; }
    .wp-qh { font-size: .84rem; font-weight: 800; color: var(--color-gray-800); margin-bottom: .45rem; }
    .wp-qh small { display: block; font-weight: 500; font-size: .7rem; color: var(--color-gray-400); }
    html.dark .wp-qh { color: #e8efe1; }
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
        .wtp-hero .h-win { font-size: 1.15rem; }
        .wtp-card { padding: .8rem .85rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .wtp-step.is-on { animation: none; }
        .wtp-run { animation: none; }
        .va-rec, .wp-fill, .wtp-dot { transition: none; transform: none; opacity: 1; }
        .q-body, .q-c, .q-hint, .wtp-prob, .va-row, .crop-tag, .crop-row { transition: none; }
        .va-chip { animation: none; }
    }
</style>

<div class="max-w-2xl mx-auto">
    <div class="wtp-tabs" role="tablist">
        <button type="button" class="wtp-tab is-on" id="vaTabGen">Generate</button>
        <button type="button" class="wtp-tab" id="vaTabSaved">Saved</button>
    </div>

    <div id="vaGen" class="an-wait">
        <div class="wtp-quote" id="vaQuote" hidden>
            <button type="button" class="q-head" id="vaQuoteHead" aria-expanded="true">
                <span class="q-ico">🔬</span>
                <span class="q-title">Before you run one</span>
                <span class="q-hint" id="vaQuoteHint"></span>
                <svg class="q-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="q-body">
                <div class="q-body-in">
                    <div class="q-card" id="vaQuoteCost"></div>
                    <div class="q-card">Anee looks up the newest {{ \App\Support\Region::ph() ? 'Philippine' : \App\Support\Region::name() }} varieties, their trial yields, the latest derbies and real farmers' harvests, and their resistance. She ranks ten of them for your field and the coming weather, by what matters most to you. Treat it as a guide.</div>
                </div>
            </div>
        </div>

        <div class="card p-5 wtp-wiz" id="vaWiz">
            {{-- Step 1: the lot (2026-10-16, the owner). It replaces "Where is
                 the field?": the lot brings its place, its size and its field
                 answers (Global Lots, partials/farm-lots), and the questions it
                 answered are hidden on the steps after it. The field is in the
                 farmer's own country (the lots carry no other). --}}
            <section class="wtp-step is-on" data-step="0">
                <p class="wtp-q">Which lot is it for?</p>
                <p class="wtp-sub">Pick the lot, or add it. Its place, size, soil and water come with it, so they are not asked again.</p>
                <div id="vaLots"></div>
            </section>
            {{-- Step 2: when it will be planted -- a month and its year, from a
                 tag that opens the calendar (partials/month-pick; 2026-10-16,
                 the owner). The season it falls in is worked out from it,
                 unseen (seasonOf, VarietyAnalysisController::seasonOfMonth). --}}
            <section class="wtp-step" data-step="1">
                <p class="wtp-q">When will you plant it?</p>
                <p class="wtp-sub">The month you will sow or transplant. Anee checks the weather outlook for the months the crop stands in the field.</p>
                <button type="button" class="crop-tag" id="vaMonthBtn" aria-haspopup="dialog">
                    <span class="crop-tag-e">🗓️</span>
                    <span class="crop-tag-t is-none" id="vaMonthNow">Pick the month and year</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
                @include('partials.month-pick')
            </section>
            {{-- Step 3: the soil. Skipped when the lot says how it feels.
                 Lowland, upland or highland is the lot's question now
                 (2026-10-16, the owner): legacy() hands it over. --}}
            <section class="wtp-step" data-step="2" id="vaSoilStep">
                <div class="va-blk" id="vaSoilBlk">
                    <p class="wtp-q">What is the soil like?</p>
                    <p class="wtp-sub">Pick what it feels like. No soil test needed.</p>
                    <div class="wtp-choices" id="vaSoils"></div>
                </div>
            </section>
            {{-- Step 4: the crop --}}
            <section class="wtp-step" data-step="3">
                <p class="wtp-q">Which crop?</p>
                <p class="wtp-sub">The same crop list your lots use.</p>
                <button type="button" class="crop-tag" id="vaCropBtn">
                    <span class="crop-tag-e" id="vaCropIcon">🌱</span>
                    <span class="crop-tag-t is-none" id="vaCropNow">Choose the crop</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </section>
            {{-- Step 5: the varieties --}}
            <section class="wtp-step" data-step="4">
                <p class="wtp-q">Which varieties are you comparing?{{ \App\Support\Tagalog::flag('compare-varieties') }}</p>
                {{-- Said plainly (2026-10-16, the owner: "right now its not that clear"). --}}
                <p class="wtp-sub">Thinking of planting some varieties? Add them here, up to eight, and Anee compares them on your field and ranks them. No varieties in mind? Leave it empty and Anee picks the top yielding ones.</p>
                <div class="va-chips" id="vaChips"></div>
                {{-- Two ways to add (2026-10-15, the owner): search every variety on record, or type your own.
                     The ones on record that suit the field are no longer offered here (2026-10-16, the owner:
                     they gave the answer away before the run); the results name them. --}}
                <p class="va-ways-h">Add a variety to compare</p>
                <div class="va-ways">
                    <button type="button" class="va-way" id="vaSearchBtn" hidden>
                        <span class="va-way-i" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"><circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4.2-4.2"/></svg></span>
                        <span class="va-way-t"><b>Search the records</b><small id="vaSearchSub">Every variety on record for this crop</small></span>
                    </button>
                    <div class="va-own">
                        <label class="va-own-l" for="vaVarietyIn">Not on record? Type its name as sold</label>
                        <div class="va-add">
                            <input type="text" id="vaVarietyIn" class="form-input" maxlength="60" placeholder="e.g. {{ \App\Support\Region::ph() ? 'a new hybrid' : 'Pioneer P1197, DKC64-34' }}" autocomplete="off">
                            <button type="button" class="btn btn-primary" id="vaVarietyAdd">Add</button>
                        </div>
                    </div>
                </div>
                <div class="va-anee" id="vaAneePicks"><span id="vaAneeFaceSlot">🤖</span><span>No varieties yet. <b>Anee will pick the top yielding released varieties</b> for your crop and field, and compare them.</span></div>
            </section>
            {{-- Step 6: the priorities --}}
            <section class="wtp-step" data-step="5">
                <p class="wtp-q">What matters most to you?</p>
                <p class="wtp-sub">Put them in order. The first counts 40%, then 30%, 20% and 10%.</p>
                <div class="va-rank" id="vaRank"></div>
            </section>
            {{-- Step 7: anything else. "What problems does this field have?"
                 was asked here: the lot asks it now (2026-10-16, the owner),
                 and legacy() turns its answers into state.problems. --}}
            <section class="wtp-step" data-step="6">
                <p class="wtp-q">Anything else Anee should know?</p>
                <p class="wtp-sub">Optional. The field's problems come from your lot.</p>
                <textarea id="vaNotes" class="form-textarea" rows="2" maxlength="400" placeholder="e.g. we transplant late, the buyer wants long grain"></textarea>
            </section>
            {{-- Step 8: the decision --}}
            <section class="wtp-step" data-step="7">
                <p class="wtp-q">Ready to run it?</p>
                <p class="wtp-sub">Check your answers. Tap a row to change it.</p>
                <div id="vaReview"></div>
                @include('partials.run-review')
                <button type="button" class="wtp-run" id="vaRun">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                    <span id="vaRunSays">Run the research</span>
                </button>
                <p class="text-xs text-gray-400 mt-2 text-center" id="vaRunFine"></p>
            </section>

            {{-- The wait: Anee's face at work, shared by every AI run. --}}
            @include('sm.partials.anee-wait')

            <div class="wtp-dots" id="vaDots"></div>
            <div class="wtp-nav" id="vaNav">
                <button type="button" class="btn btn-white flex-1" id="vaBack">Back</button>
                <button type="button" class="btn btn-primary flex-1" id="vaNext">Next</button>
            </div>
        </div>

        <div class="wtp-report mt-4" id="vaReport" hidden></div>
    </div>

    {{-- The report, full screen, when one lands or a saved one is opened. --}}
    <div class="va-view" id="vaView" hidden role="dialog" aria-modal="true" aria-label="Variety Ranking">
        <div class="va-view-bar">
            <b id="vaViewTitle">Variety Ranking</b>
            <button type="button" class="va-view-x" id="vaViewX" aria-label="Close">✕</button>
        </div>
        <div class="va-view-body"><div class="wtp-report" id="vaViewReport"></div></div>
    </div>

    <div id="vaSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div id="vaSavedList"></div>
            <div id="vaSavedEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900">Nothing saved yet</p>
                <p class="text-sm text-gray-400">Each research you run is saved here.</p>
            </div>
        </div>
        <div class="wtp-report mt-4" id="vaSavedReport" hidden></div>
    </div>
</div>

{{-- The farm's lots: the picker, the lot sheet, and the field questions with it. --}}
@include('partials.farm-lots')

{{-- The crop sheet sits here, not on the 'sheets' stack: that stack prints
     after this script has run, and the script wires the sheet by id. --}}
{{-- The crop catalogue, searchable — the same sheet the lot form and the sisters use. --}}
{{-- Every variety on record for the crop, searched by name, local or trade name; tap to add or take away. --}}
<div class="sheet hidden" id="vaFindSheet" style="--sheet-width:32rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="vaFindTitle">Varieties on record</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="crop-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="search" id="vaFindQ" class="form-input" autocomplete="off" placeholder="Search a name, like Rc 222 or Mestiso 20">
        </div>
        <div class="va-find-filt" id="vaFindFilt" role="group" aria-label="Kind">
            <button type="button" data-ft="" aria-pressed="true">All</button>
            <button type="button" data-ft="hybrid" aria-pressed="false">Hybrid</button>
            <button type="button" data-ft="inbred" aria-pressed="false">Not hybrid</button>
        </div>
        <p class="va-find-n" id="vaFindN"></p>
        <div class="va-find-list" id="vaFindList"></div>
        <p class="crop-none hidden" id="vaFindNone">Nothing on record by that name. Close this and type it under "Not on record".</p>
    </div>
    <div class="va-find-foot"><span id="vaFindPicked">None picked yet</span><button type="button" class="btn btn-primary" data-sheet-close>Done</button></div>
</div>

<div class="sheet hidden" id="vaCropSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Choose a crop</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="crop-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" id="vaCropSearch" class="form-input" autocomplete="off"
                   placeholder="{{ \App\Support\Region::t('cropSearch') }}">
            <button type="button" class="crop-search-x hidden" id="vaCropSearchX" aria-label="Clear">✕</button>
        </div>
        <div id="vaCropList"></div>
        <p class="crop-none hidden" id="vaCropNone">No match. Try the local name, or pick mixed vegetables (Gulay).</p>
    </div>
</div>

<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    /* No government body, state university or research institute is named to
     * the farmer (the owner, 2026-10-18): a public breeder (PhilRice, IRRI,
     * UPLB, the BPI) or a derby's public host is left out, saved reports too;
     * a seed company stays. The twin of App\Support\GovSource::isGov. */
    const GOV_W = /philrice|phil\s+rice|bswm|bureau\s+of|department\s+of\s+agriculture|pagasa|philmech|philfida|pcaarrd|uplb|state\s+university|university\s+of\s+the\s+philippines|international\s+rice|\birri\b|\bfao\b|cimmyt|world\s*veg|avrdc|tnau|nsic\s+registry|variety\s+registry/i;
    const GOV_C = /\b(?:DA|BPI(?!\s*-?\s*(?:[A-Z][a-z]{0,2}\s*-?\s*)?\d)|ATI|NIA|PCA|PSA|SRA|DOST|UPLB|CLSU|VSU|CMU|ISU|BSU|MMSU|USM|CvSU|IPB)\b/;
    const notGov = (s) => { const t = String(s ?? '').trim(); return t && !GOV_W.test(t) && !GOV_C.test(t) ? t : ''; };
    const U = {
        options: '{{ route('vary.options') }}',
        generate: '{{ route('vary.generate') }}',
        list: '{{ route('vary.list') }}',
        one: (id) => '{{ url('/app/variety-research/one') }}/' + id,
        job: (id) => '{{ url('/app/variety-research/job') }}/' + id,
        del: (id) => '{{ url('/app/variety-research') }}/' + id,
        anee: '{{ route('ai.index') }}',
        book: '{{ route('vary.book') }}',
    };
    const VA_META_URL = '{{ route('vary.meta') }}';

    let OPT = null;
    const state = { location: '', country: '', month: null, year: null, season: null, soil: null, elevation: null, crop: '', varieties: [], priorities: ['yield', 'protection', 'survival', 'quickness'], problems: [], notes: '' };
    const RULES = () => (window.ANEE_REGION_RULES || {});
    const rulesFor = (code) => RULES()[code] || RULES()['*'] || {};
    // The seasons on offer are the FIELD's country's.
    const seasonsFor = (code) => { const r = rulesFor(code); return (r.seasons && Object.keys(r.seasons).length) ? r.seasons : ((OPT && OPT.seasons) || {}); };
    const seasonSaid = (season, year, country) => {
        const y = Number(year) || 0;
        const label = seasonsFor(country || state.country || (OPT && OPT.country))[season] || ((OPT && OPT.seasons && OPT.seasons[season]) || '');
        const crosses = season === 'dry' || season === 'winter';
        return crosses && y ? `${label} ${y} to ${y + 1}` : `${label} ${y || ''}`.trim();
    };
    /* The season a planting month falls in where the field is (the server's
       VarietyAnalysisController::seasonOfMonth): in the Philippines the wet
       season is planted May to October, the dry November to February (it
       carries the year it starts in), the third crop March and April. */
    const seasonOf = (key) => {
        const [y, m] = String(key || '').split('-').map(Number);
        if (!y || !m) return [null, null];
        const keys = Object.keys(seasonsFor(state.country));
        const has = (k) => keys.includes(k);
        let out;
        if (has('wet') && has('dry')) out = m >= 5 && m <= 10 ? ['wet', y] : m >= 11 ? ['dry', y] : m <= 2 ? ['dry', y - 1] : [has('third') ? 'third' : 'dry', has('third') ? y : y - 1];
        else out = m >= 3 && m <= 5 ? ['spring', y] : m >= 6 && m <= 8 ? ['summer', y] : m >= 9 && m <= 11 ? ['autumn', y] : ['winter', m === 12 ? y : y - 1];
        if (!has(out[0])) out[0] = keys[0] || out[0];
        return out;
    };
    const monthLabel = (key) => ((OPT && OPT.months) || []).find((m) => m.key === key)?.label || key || '';
    let step = 0;
    const STEPS = 8;
    /* ---- the lot (2026-10-16, the owner): partials/farm-lots. Its answers
       are the field in detail. A simple question the lot answers is filled
       from it and hidden; the rest stay, ticked from the lot where it gives
       a hint, until the farmer changes them by hand. ---- */
    let LOTS = null;
    const LOT = { lot: null, L: {}, n: 0 };
    let soilByLot = false, cropByLot = false;
    const lotHas = (...keys) => !!LOT.lot && !!window.aneeLots && keys.some((k) => window.aneeLots.has(LOT.lot, k));
    const soilFromLot = () => lotHas('texture') || ((LOT.lot && LOT.lot.answers) || {}).rocks === 'many';
    // The soil step goes when the lot answered it.
    const skipped = (n) => n === 2 && soilFromLot();
    const stillMotion = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    // Hide or show a block; one that comes back fades in rather than snapping.
    const reveal = (el, on) => {
        if (!el || el.hidden === !on) return;
        el.hidden = !on;
        if (on && !stillMotion()) { el.classList.remove('va-pop'); void el.offsetWidth; el.classList.add('va-pop'); }
    };
    function applyProfile(answers, L) {
        if (!OPT) return;
        L = L || {};
        // The soil: the lot's, and hidden, when the lot says how it feels.
        const soilHid = soilFromLot();
        if (soilHid) { state.soil = OPT.soils[L.soil] ? L.soil : 'unsure'; soilByLot = true; }
        else if (soilByLot) { state.soil = null; soilByLot = false; }
        document.querySelectorAll('#vaSoils .wtp-choice').forEach((c) => c.classList.toggle('is-on', c.getAttribute('data-soil') === state.soil));
        reveal($id('vaSoilBlk'), !soilHid);
        // Only the lot says these (2026-10-16, the owner): no step asks them here.
        state.elevation = (OPT.elevations || {})[L.elevation] ? L.elevation : null;
        const want = [];
        (L.problemsVariety || []).filter((k) => OPT.problems[k]).forEach((k) => { if (!(PROB_FOES[k] || []).some((f) => want.includes(f))) want.push(k); });
        state.problems = want;
        document.querySelectorAll('#vaDots .wtp-dot').forEach((d, i) => d.classList.toggle('is-skip', skipped(i)));
    }
    // The lot picked, saved or changed: its place, its crop and its answers.
    async function lotPicked(lot) {
        LOT.lot = lot || null;
        state.location = lot ? (lot.where || lot.place || '') : '';
        // The crop usually on the lot, until the farmer picks one.
        if (lot && lot.crop && OPT && (!state.crop || cropByLot) && state.crop !== lot.crop && OPT.crops.some((c) => c.key === lot.crop)) {
            setCrop(lot.crop, true);
            return;
        }
        await lotApply();
        if (state.crop) loadBook();
    }
    async function lotApply() {
        const lot = LOT.lot, n = ++LOT.n;
        const L = lot && window.aneeLots ? await window.aneeLots.legacy(lot, state.crop || lot.crop) : {};
        if (n !== LOT.n) return;
        LOT.L = L;
        applyProfile(lot ? lot.answers : {}, L);
        if (step === STEPS - 1) review();
    }
    function mountLots() {
        if (LOTS || !window.aneeLots) return;
        LOTS = window.aneeLots.picker($id('vaLots'), { crop: () => state.crop || null, onPick: lotPicked });
    }
    /* "From North field": the answers that stood in for questions here, and a way to change them. */
    const SAID_KEYS = ['texture', 'rocks', 'water', 'acidity', 'phFrom', 'salt', 'flooding', 'drainage', 'organic', 'slope', 'elevation', 'hazards', 'weeds', 'outbreaks'];
    // The lot's answers this page reads and never asks, named when the lot has not given them.
    const MISSING = { flooding: 'the floods', drainage: 'the drainage', salt: 'the salt', acidity: 'the acidity', organic: 'the organic matter', slope: 'the slope',
        elevation: 'the elevation', hazards: 'a river or the wind', weeds: 'the weeds', outbreaks: 'the last troubles' };
    const andList = (a) => a.length < 2 ? (a[0] || '') : a.slice(0, -1).join(', ') + ' and ' + a[a.length - 1];
    // The lot for the review: its answers as chips, and what it has not said yet.
    function lotReview() {
        const lot = LOT.lot;
        if (!lot || !window.aneeLots) return null;
        const A = lot.answers || {}, sub = {};
        SAID_KEYS.forEach((k) => { if (window.aneeLots.has(lot, k)) sub[k] = A[k]; });
        if (sub.phFrom != null && A.phTo != null) sub.phTo = A.phTo;
        // The stones only when they stood for the soil.
        if (sub.rocks !== undefined && (sub.texture !== undefined || sub.rocks !== 'many')) delete sub.rocks;
        const pairs = window.aneeFieldCard && window.aneeFieldProfile ? window.aneeFieldCard.pairs(sub, {}) : [];
        return { name: lot.name, place: lot.place || state.location, size: window.aneeLots.fmtSize(lot),
            said: pairs.map(([k, v]) => k + ': ' + v),
            missing: Object.keys(MISSING).filter((k) => !window.aneeLots.has(lot, k) && !(k === 'acidity' && window.aneeLots.has(lot, 'phFrom'))).map((k) => MISSING[k]) };
    }
    $id('vaReview').addEventListener('click', (e) => {
        const go = e.target.closest('[data-rv-go]');
        if (go) { show(Number(go.getAttribute('data-rv-go')), true); return; }
        if (!e.target.closest('[data-lot-edit]') || !LOT.lot || !window.aneeLots) return;
        // A saved lot comes back through the picker (aneelots:changed) and is applied there.
        window.aneeLots.edit(LOT.lot, { crop: state.crop || null });
    });

    async function boot() {
        try {
            const res = await api(U.options, { method: 'GET' });
            OPT = res.data;
            paintOptions();
            const pre = new URLSearchParams(location.search).get('crop');
            if (pre && (OPT.crops || []).some((c) => c.key === pre)) setCrop(pre);
        } catch (err) { toast(err.message, 'error'); }
        if (window.aneeLots) mountLots(); else document.addEventListener('DOMContentLoaded', mountLots, { once: true });
        // Painted: the price card and the wizard arrive together, once.
        const gen = $id('vaGen');
        if (gen && gen.classList.contains('an-wait')) {
            gen.classList.remove('an-wait');
            gen.classList.add('an-in');
            setTimeout(() => gen.classList.remove('an-in'), 400);
        }
    }

    function paintOptions() {
        state.country = state.country || OPT.country || ((window.ANEE_REGION || {}).code) || 'PH';
        const soilIcons = { clay: '🧱', loam: '🟤', sandy: '🏖️', silty: '🌊', rocky: '⛰️', unsure: '🤷' };
        $id('vaSoils').innerHTML = Object.entries(OPT.soils).map(([k, label]) => `
            <button type="button" class="wtp-choice" data-soil="${k}"><span class="c-e">${soilIcons[k] || '🟫'}</span><span>${esc(String(label).split(' — ')[0])}${String(label).includes(' — ') ? `<small>${esc(String(label).split(' — ').slice(1).join(' — '))}</small>` : ''}</span></button>`).join('');
        const groups = {};
        OPT.crops.forEach((c) => { (groups[c.group] = groups[c.group] || []).push(c); });
        $id('vaCropList').innerHTML = Object.entries(groups).map(([g, list]) => `
            <div class="crop-group" data-crop-group>
                <p class="crop-group-h">${esc(g)}</p>
                ${list.map((c) => `
                    <button type="button" class="crop-row" data-crop="${esc(c.key)}" data-find="${esc((c.label + ' ' + g).toLowerCase())}">
                        <span class="crop-row-e">${esc(c.icon)}</span>
                        <span class="crop-row-t">
                            <b>${esc(c.label)}</b>
                            <small>${c.perennial ? 'Tree crop, counted by age' : (c.maturity ? c.maturity + ' days to harvest' : '')}</small>
                        </span>
                    </button>`).join('')}
            </div>`).join('');
        $id('vaDots').innerHTML = Array.from({ length: STEPS }, (_, i) => `<span class="wtp-dot${i === 0 ? ' is-on' : ''}"></span>`).join('');
        if (OPT.aneeFace) $id('vaAneeFaceSlot').innerHTML = `<img src="${esc(OPT.aneeFace)}" alt="">`;
        paintRank();
        paintChips();
        paintQuote();
    }

    const QUOTE_MIN_KEY = 'anee-vary-quote-min';
    let quoteMin = false;
    try { quoteMin = localStorage.getItem(QUOTE_MIN_KEY) === '1'; } catch (_) { /* opens full */ }

    function paintQuote() {
        const q = $id('vaQuote');
        if (!OPT) return;
        if (!OPT.canUse) {
            $id('vaQuoteCost').innerHTML = esc(OPT.whyNot || 'The analysis is not available right now.');
            $id('vaQuoteHint').textContent = '';
            q.classList.remove('is-min');
            q.hidden = false;
            return;
        }
        if (!OPT.quote) { q.hidden = true; return; }
        q.classList.toggle('is-min', quoteMin);
        $id('vaQuoteHead').setAttribute('aria-expanded', quoteMin ? 'false' : 'true');
        $id('vaQuoteCost').innerHTML = `One variety research costs <b>${OPT.quote} credits</b>. You have ${creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString())}. Nothing is charged until you press Run.`;
        $id('vaQuoteHint').textContent = `${OPT.quote} credits`;
        q.hidden = false;
    }
    $id('vaQuoteHead').addEventListener('click', () => {
        quoteMin = !quoteMin;
        try { localStorage.setItem(QUOTE_MIN_KEY, quoteMin ? '1' : '0'); } catch (_) { /* not remembered */ }
        paintQuote();
    });

    /* ---------------- the walk ---------------- */
    function show(n, backwards) {
        const was = document.querySelector('#vaWiz .wtp-step.is-on'), from = step;
        step = Math.max(0, Math.min(STEPS - 1, n));
        // Over a step the lot answered whole, the way the farmer is going.
        while (step > 0 && step < STEPS - 1 && skipped(step)) step += backwards ? -1 : 1;
        if (step !== from) window.aneeStepOut?.(was);
        document.querySelectorAll('#vaWiz .wtp-step').forEach((s) => {
            const on = Number(s.getAttribute('data-step')) === step;
            s.classList.toggle('is-on', on);
            s.classList.toggle('is-back', on && !!backwards);
        });
        if (step !== from) window.aneeStepTop?.($id('vaWiz'));
        document.querySelectorAll('#vaDots .wtp-dot').forEach((d, i) => d.classList.toggle('is-on', i <= step));
        $id('vaBack').disabled = false;   // on the first page it goes back where they came from
        $id('vaNext').style.display = step === STEPS - 1 ? 'none' : '';
        // The field is focused on a desk, where a cursor is a courtesy; on a
        // phone the keypad would land on the step before it is read.
        if (step === 4 && window.matchMedia('(min-width: 640px)').matches) setTimeout(() => $id('vaVarietyIn')?.focus({ preventScroll: true }), 300);
        if (step === STEPS - 1) review();
    }

    function stepReady() {
        switch (step) {
            case 0: if (!LOT.lot) { toast('Pick the lot, or add one.', 'error'); return false; }
                state.location = LOT.lot.where || LOT.lot.place || '';
                return !!state.location || (toast('Tap the lot to say where it is.', 'error'), false);
            case 1: return !!state.month || (toast('Pick the month you will plant.', 'error'), false);
            case 2: return !!state.soil || (toast('Pick the soil closest to yours.', 'error'), false);
            case 3: return !!state.crop || (toast('Choose a crop.', 'error'), false);
            case 4: addVariety(); return true;
            case 6: state.notes = $id('vaNotes').value.trim(); return true;
            default: return true;
        }
    }

    // A list label's first words: "Floods / standing water after rain" is "Floods".
    const shortLabel = (l) => String(l || '').split(/ \/ | \(| — /)[0];
    function review() {
        const crop = OPT.crops.find((c) => c.key === state.crop) || {};
        const season = state.month ? monthLabel(state.month) : seasonSaid(state.season, state.year);
        $id('vaReview').innerHTML = window.aneeReview.html({
            icon: crop.icon || '🌱', kicker: 'The varieties that fit', title: String(crop.label || '').replace(' — ', ', '),
            sub: season + (LOT.lot ? ' · ' + LOT.lot.name : ''),
            rows: [
                { e: '🗓️', k: 'Planting month', v: season, go: 1 },
                { e: '🟤', k: 'Soil', v: shortLabel(OPT.soils[state.soil]), go: skipped(2) ? null : 2, lot: skipped(2) },
                { e: '⛰️', k: 'The land', v: state.elevation ? shortLabel((OPT.elevations || {})[state.elevation] || state.elevation) : '', none: 'Not in the lot', lot: true },
                { e: crop.icon || '🌱', k: 'Crop', v: crop.label, go: 3 },
                { e: '🏷️', k: 'Varieties to compare', v: state.varieties, none: 'Anee picks the top yielding ones', go: 4 },
                { e: '⭐', k: 'What matters most', v: state.priorities.map((k, i) => (i + 1) + '. ' + (OPT.priorities[k]?.label || k)), go: 5 },
                { e: '⚠️', k: 'Field problems', v: state.problems.map((k) => shortLabel(OPT.problems[k] || k)), none: 'None in the lot', lot: true },
                { e: '📝', k: 'Your note', v: state.notes, none: 'No note', go: 6 },
            ],
            lot: lotReview(),
        });
        $id('vaRunSays').textContent = OPT.canUse && OPT.quote ? `Run the research (${OPT.quote} credits)` : 'Run the research';
        $id('vaRunFine').textContent = OPT.canUse
            ? 'Paid from your AI credits. You can see it in your credit log.'
            : (OPT.whyNot || '');
        $id('vaRun').disabled = !OPT.canUse;
    }

    $id('vaNext').addEventListener('click', () => { if (stepReady()) show(step + 1); });
    $id('vaBack').addEventListener('click', () => (step === 0 ? window.aneeLeave() : show(step - 1, true)));
    $id('vaMonthBtn').addEventListener('click', async () => {
        if (!OPT || !window.aneeMonthPick) return;
        const k = await window.aneeMonthPick.open({ months: OPT.months, value: state.month, title: 'Which month will you plant?' });
        if (!k) return;
        state.month = k;
        [state.season, state.year] = seasonOf(k);
        const t = $id('vaMonthNow');
        t.textContent = monthLabel(k);
        t.classList.remove('is-none');
        if (state.crop) loadBook();
    });
    $id('vaSoils').addEventListener('click', (e) => {
        const b = e.target.closest('[data-soil]');
        if (!b) return;
        state.soil = b.getAttribute('data-soil');
        soilByLot = false;
        document.querySelectorAll('#vaSoils .wtp-choice').forEach((c) => c.classList.toggle('is-on', c === b));
    });
    /* Some troubles cannot share a field: a soil is acidic or alkaline,
       not both. Of the lot's answers, the first of a pair stands. */
    const PROB_FOES = { acidic: ['alkaline'], alkaline: ['acidic'], drought: ['floods'], floods: ['drought'] };
    /* ---- the crop: the tag opens the sheet, the sheet fills the tag ---- */
    $id('vaCropBtn').addEventListener('click', () => {
        openSheet('vaCropSheet');
        if (window.matchMedia('(min-width: 640px)').matches) {
            setTimeout(() => $id('vaCropSearch')?.focus(), 280);
        }
    });
    function setCrop(key, byLot) {
        state.crop = key;
        cropByLot = !!byLot;
        const c = OPT.crops.find((x) => x.key === state.crop) || {};
        $id('vaCropIcon').textContent = c.icon || '🌱';
        const now = $id('vaCropNow');
        now.textContent = c.label ? String(c.label).replace(' — ', ', ') : 'Choose the crop';
        now.classList.remove('is-none');
        // The lot's troubles are read by the crop's own list: read them again.
        lotApply();
        loadBook();
    }
    $id('vaCropSheet').addEventListener('click', (e) => {
        const row = e.target.closest('.crop-row');
        if (!row) return;
        setCrop(row.getAttribute('data-crop'));
        closeSheet('vaCropSheet');
    });

    /* ---- the variety records (2026-10-14): every name on file to type
       from, and the ones that fit the field said so far, to add with a tap ---- */
    let BOOK = { crop: '', names: [], picks: [], count: 0, finder: null };
    const fmtN = (x) => String(Math.round(Number(x) * 100) / 100);
    async function loadBook() {
        const crop = state.crop;
        if (!crop) return;
        const q = new URLSearchParams({ crop, country: state.country || '', location: state.location || '', elevation: state.elevation || '', season: state.season || '' });
        try {
            const res = await api(U.book + '?' + q.toString(), { method: 'GET' });
            if (state.crop !== crop) return;
            BOOK = Object.assign({ crop }, res.data || {});
        } catch (err) {
            BOOK = { crop, names: [], picks: [], count: 0, finder: null };
        }
        paintBook();
    }
    const asAdded = (r) => r.alias && (r.name + ' (' + r.alias + ')').length <= 60 ? r.name + ' (' + r.alias + ')' : r.name;
    function paintBook() {
        // The search door shows when this crop has records (the Philippines' registry). The ones that
        // suit the field are not offered here (2026-10-16, the owner): the results name them.
        $id('vaSearchBtn').hidden = !BOOK.count;
        $id('vaSearchSub').textContent = BOOK.count ? 'All ' + BOOK.count + ' on record for this crop, by name, local or trade name' : '';
    }
    const cropSift = () => {
        const q = ($id('vaCropSearch').value || '').trim().toLowerCase();
        $id('vaCropSearchX').classList.toggle('hidden', !q);
        let shown = 0;
        document.querySelectorAll('#vaCropList [data-crop-group]').forEach((g) => {
            let left = 0;
            g.querySelectorAll('.crop-row').forEach((r) => {
                const hit = !q || (r.getAttribute('data-find') || '').includes(q);
                r.hidden = !hit;
                if (hit) left++;
            });
            g.hidden = left === 0;
            shown += left;
        });
        $id('vaCropNone').classList.toggle('hidden', shown > 0);
    };
    $id('vaCropSearch').addEventListener('input', cropSift);
    $id('vaCropSearchX').addEventListener('click', () => {
        $id('vaCropSearch').value = '';
        cropSift();
        $id('vaCropSearch').focus();
    });

    /* ---- every variety on record, searched in a sheet (2026-10-15, the owner) ---- */
    const FIND = { crop: '', rows: null, q: '', type: '', shown: 60 };
    const norm = (s) => String(s || '').toLowerCase().replace(/[\s\-._]+/g, '');
    const CHECK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>';
    const isPicked = (r) => state.varieties.some((v) => v.toLowerCase().startsWith(r[0].toLowerCase()));
    async function openFind() {
        openSheet('vaFindSheet');
        const crop = state.crop;
        $id('vaFindTitle').textContent = (((((OPT && OPT.crops) || []).find((x) => x.key === crop) || {}).label || 'Crop').split(' (')[0].split(' — ')[0]) + ' varieties on record';
        if (FIND.crop !== crop || !FIND.rows) {
            FIND.crop = crop; FIND.rows = null; FIND.shown = 60;
            $id('vaFindList').innerHTML = '<p class="va-find-n">Loading the records…</p>';
            try {
                const q = new URLSearchParams({ crop, country: state.country || '', all: 1 });
                const res = await api(U.book + '?' + q.toString(), { method: 'GET' });
                if (FIND.crop !== crop) return;
                FIND.rows = (res.data && res.data.all) || [];
            } catch (err) {
                FIND.rows = [];
                $id('vaFindList').innerHTML = '<p class="va-find-n">The records could not be loaded. Close this and try again.</p>';
                return;
            }
        }
        paintFind();
        if (matchMedia('(pointer: fine)').matches) $id('vaFindQ').focus();
    }
    function paintFind() {
        if (!FIND.rows) return;
        const q = norm(FIND.q);
        const hits = FIND.rows.filter((r) => (!FIND.type || (FIND.type === 'hybrid' ? r[2] === 'hybrid' : r[2] !== 'hybrid'))
            && (!q || norm(r[0] + ' ' + (r[1] || '') + ' ' + (r[9] || '')).includes(q)));
        $id('vaFindN').textContent = hits.length + ' of ' + FIND.rows.length + (FIND.q ? ' match' : ' on record') + (hits.length > FIND.shown ? ', showing the first ' + FIND.shown : '');
        $id('vaFindNone').classList.toggle('hidden', hits.length > 0);
        $id('vaFindList').innerHTML = hits.slice(0, FIND.shown).map((r, i) => {
            const facts = [r[2] === 'hybrid' ? 'Hybrid' : '', r[3] ? Math.round(r[3]) + ' ' + (r[4] || 'days') : '', r[5] ? fmtN(r[5]) + ' ' + (r[6] || 't/ha') : '', r[8] ? 'Released ' + r[8] : ''].filter(Boolean).join(' · ');
            return '<button type="button" class="va-find-r" data-find="' + esc(r[0]) + '" data-alias="' + esc(r[1] || '') + '" aria-pressed="' + isPicked(r) + '">'
                + '<span class="t"><b>' + esc(r[0]) + '</b>' + (r[1] ? '<small>' + esc(r[1]) + '</small>' : '') + (facts ? '<span>' + esc(facts) + '</span>' : '') + '</span>'
                + '<span class="ck" aria-hidden="true">' + CHECK + '</span></button>';
        }).join('') + (hits.length > FIND.shown ? '<button type="button" class="va-find-more" id="vaFindMore">Show ' + Math.min(60, hits.length - FIND.shown) + ' more</button>' : '');
        paintPicked();
    }
    const paintPicked = () => { $id('vaFindPicked').textContent = state.varieties.length ? state.varieties.length + ' of 8 picked' : 'None picked yet'; };
    $id('vaSearchBtn').addEventListener('click', openFind);
    let findT = null;
    $id('vaFindQ').addEventListener('input', (e) => { clearTimeout(findT); findT = setTimeout(() => { FIND.q = e.target.value.trim(); FIND.shown = 60; paintFind(); }, 120); });
    $id('vaFindFilt').addEventListener('click', (e) => {
        const b = e.target.closest('[data-ft]');
        if (!b) return;
        FIND.type = b.dataset.ft; FIND.shown = 60;
        $id('vaFindFilt').querySelectorAll('[data-ft]').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
        paintFind();
    });
    $id('vaFindList').addEventListener('click', (e) => {
        if (e.target.closest('#vaFindMore')) { FIND.shown += 60; paintFind(); return; }
        const b = e.target.closest('[data-find]');
        if (!b) return;
        const name = b.dataset.find, alias = b.dataset.alias;
        const at = state.varieties.findIndex((v) => v.toLowerCase().startsWith(name.toLowerCase()));
        if (at >= 0) {
            state.varieties.splice(at, 1);
        } else {
            if (state.varieties.length >= 8) { toast('You can add up to eight.', 'error'); return; }
            state.varieties.push(alias && (name + ' (' + alias + ')').length <= 60 ? name + ' (' + alias + ')' : name);
        }
        b.setAttribute('aria-pressed', String(at < 0));
        paintChips();
        paintPicked();
    });

    /* ---- the varieties: typed, added, worn as chips; empty means Anee picks ---- */
    function addVariety() {
        const inp = $id('vaVarietyIn');
        const v = inp.value.trim().replace(/\s{2,}/g, ' ');
        if (!v) return false;
        if (state.varieties.length >= 8) { toast('You can add up to eight.', 'error'); return false; }
        if (state.varieties.some((x) => x.toLowerCase() === v.toLowerCase())) { toast('That one is already on the list.', 'error'); inp.value = ''; return false; }
        state.varieties.push(v);
        inp.value = '';
        paintChips();
        return true;
    }
    function paintChips() {
        $id('vaChips').innerHTML = state.varieties.map((v, i) => `
            <span class="va-chip">${esc(v)}<button type="button" data-drop="${i}" aria-label="Remove ${esc(v)}">✕</button></span>`).join('');
        $id('vaAneePicks').hidden = state.varieties.length > 0;
        if (typeof paintBook === 'function' && BOOK.crop === state.crop) paintBook();
    }
    $id('vaVarietyAdd').addEventListener('click', () => { addVariety(); $id('vaVarietyIn').focus(); });
    $id('vaVarietyIn').addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); addVariety(); }
    });
    $id('vaChips').addEventListener('click', (e) => {
        const b = e.target.closest('[data-drop]');
        if (!b) return;
        state.varieties.splice(Number(b.getAttribute('data-drop')), 1);
        paintChips();
    });

    /* ---- the priorities: an order the arrows change, rows sliding ---- */
    function paintRank() {
        $id('vaRank').innerHTML = state.priorities.map((k, i) => {
            const p = OPT.priorities[k] || { label: k, sub: '', icon: '•' };
            return `<div class="va-row" data-key="${k}">
                <span class="r-n">${i + 1}</span>
                <span class="r-e">${p.icon}</span>
                <span class="r-t"><b>${esc(p.label)}</b><small>${esc(p.sub)}</small></span>
                <span class="r-w">${Math.round((OPT.weights[i] || 0) * 100)}%</span>
                <span class="r-btns">
                    <button type="button" data-move="-1" ${i === 0 ? 'disabled' : ''} aria-label="Move ${esc(p.label)} up"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg></button>
                    <button type="button" data-move="1" ${i === state.priorities.length - 1 ? 'disabled' : ''} aria-label="Move ${esc(p.label)} down"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></button>
                </span>
            </div>`;
        }).join('');
    }
    $id('vaRank').addEventListener('click', (e) => {
        const b = e.target.closest('[data-move]');
        if (!b || b.disabled) return;
        const row = b.closest('.va-row');
        const from = state.priorities.indexOf(row.dataset.key);
        const to = from + Number(b.getAttribute('data-move'));
        if (to < 0 || to >= state.priorities.length) return;
        // FLIP: remember where every row was, reorder, then let each slide
        // from its old place to its new one.
        const rows = [...$id('vaRank').children];
        const before = new Map(rows.map((r) => [r.dataset.key, r.getBoundingClientRect().top]));
        [state.priorities[from], state.priorities[to]] = [state.priorities[to], state.priorities[from]];
        paintRank();
        const after = [...$id('vaRank').children];
        after.forEach((r) => {
            const dy = (before.get(r.dataset.key) ?? 0) - r.getBoundingClientRect().top;
            if (!dy) return;
            r.classList.add('is-moving');
            r.style.transform = `translateY(${dy}px)`;
            requestAnimationFrame(() => requestAnimationFrame(() => { r.classList.remove('is-moving'); r.style.transform = ''; }));
        });
        const again = $id('vaRank').querySelector(`.va-row[data-key="${row.dataset.key}"] [data-move="${b.getAttribute('data-move')}"]`);
        if (again && !again.disabled) again.focus({ preventScroll: true });
    });

    /* ---------------- the run ---------------- */
    $id('vaRun').addEventListener('click', async () => {
        if (!stepReady()) return;
        const wiz = $id('vaWiz');
        wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = 'none');
        window.aneeWait.show({ title: 'Anee is researching…', lines: ['Reading the newest ' + ((window.ANEE_REGION || {}).ph === false ? (window.ANEE_REGION.name + ' ') : 'Philippine ') + 'varieties and trials…', 'Checking hybrids from top seed companies…', 'Looking up the latest derbies and farmers\' harvests…', 'Checking resistance and days to harvest…', 'Matching each variety to your soil and weather…', 'Ranking by what matters most to you…'], sub: 'This takes a minute or two.' });
        $id('vaReport').hidden = true;
        let landed = false;
        try {
            const res = await api(U.generate, { method: 'POST', body: {
                // The lot: its id, and its answers as the field in detail.
                ...(window.aneeLots ? window.aneeLots.payload(LOT.lot) : {}), lotName: LOT.lot ? LOT.lot.name : null,
                location: state.location, country: state.country, month: state.month, year: state.year, season: state.season,
                soil: state.soil, elevation: state.elevation, crop: state.crop,
                varieties: state.varieties, priorities: state.priorities,
                problems: state.problems, notes: state.notes,
            } });
            let data = res.data;
            if (data.pending) {
                // The shared poll: the bar, the clock and the hang check ride
                // along, and a poll that cannot reach the server is retried
                // rather than taken for the job failing.
                data = await window.aneeWait.poll({ id: data.id || res.data.id, job: U.job, phases: window.aneeWait.phases.research });
            }
            OPT.balance = data.balance;
            landed = true;
            const item = { report: data.report, params: data.params, charged: data.charged, savedId: data.savedId };
            // A slip in drawing must not strand the veil: the result is on
            // the shelf either way, and the wait still lifts.
            try { drawReport($id('vaReport'), item, 'fresh', true); openView(item, 'fresh'); }
            catch (drawErr) { console.error(drawErr); toast('It is saved on the Saved tab, but this page could not show it.', 'error'); }
            await window.aneeWait.done({ title: 'Done!', line: `${data.charged} credits used. Saved on the Saved tab.` });
            toast(`Done. ${data.charged} credits used. Saved on the Saved tab.`);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            if (!landed) window.aneeWait.fail();
            wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = '');
            show(step);
            if (landed) { wiz.hidden = true; $id('vaQuote').hidden = true; }
        }
    });

    /* The report full screen: the tabs and the wizard are out of sight
       until the ✕; the page's own copy of the report stays underneath. */
    let VIEW_MODE = null;   // 'fresh' right after a run, 'saved' from the shelf
    function openView(item, mode) {
        VIEW_MODE = mode || null;
        const view = $id('vaView');
        const crop = (OPT ? OPT.crops.find((c) => c.key === (item.params || {}).crop) : null) || {};
        $id('vaViewTitle').textContent = crop.label ? crop.label + ' variety ranking' : 'Variety Ranking';
        const host = $id('vaViewReport');
        host.classList.remove('is-drawn');
        drawReport(host, item, mode, true);
        view.hidden = false;
        document.documentElement.classList.add('va-view-lock');
        view.scrollTop = 0;
        requestAnimationFrame(() => requestAnimationFrame(() => { view.classList.add('is-on'); host.classList.add('is-drawn'); }));
    }
    function closeView() {
        const view = $id('vaView');
        if (view.hidden) return;
        view.classList.remove('is-on');
        document.documentElement.classList.remove('va-view-lock');
        // A fresh result was drawn over a hidden wizard: closing it brings
        // the wizard back, or the Generate tab stands empty.
        const wasFresh = VIEW_MODE === 'fresh';
        VIEW_MODE = null;
        setTimeout(() => { view.hidden = true; $id('vaViewReport').innerHTML = ''; if (wasFresh) { resetWizard(); wizardBack(); } }, 300);
    }
    $id('vaViewX').addEventListener('click', closeView);
    /* After a fresh result is closed the wizard starts over, as on a fresh
       page load (2026-10-17, the owner: "the wizard should be back to 0 or
       reset"). The options and the lots list stay loaded. */
    function resetWizard() {
        Object.assign(state, { location: '', month: null, year: null, season: null, soil: null, elevation: null, crop: '', varieties: [],
            priorities: ['yield', 'protection', 'survival', 'quickness'], problems: [], notes: '' });
        LOT.lot = null; LOT.L = {}; LOT.n++;
        soilByLot = false; cropByLot = false;
        LOTS?.set(null);
        BOOK = { crop: '', names: [], picks: [], count: 0, finder: null };
        $id('vaSearchBtn').hidden = true;
        const m = $id('vaMonthNow');
        m.textContent = 'Pick the month and year'; m.classList.add('is-none');
        $id('vaCropIcon').textContent = '🌱';
        const c = $id('vaCropNow');
        c.textContent = 'Choose the crop'; c.classList.add('is-none');
        document.querySelectorAll('#vaSoils .wtp-choice').forEach((b) => b.classList.remove('is-on'));
        reveal($id('vaSoilBlk'), true);
        $id('vaVarietyIn').value = '';
        $id('vaNotes').value = '';
        paintChips();
        paintRank();
        document.querySelectorAll('#vaDots .wtp-dot').forEach((d) => d.classList.remove('is-skip'));
    }
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeView(); });

    function wizardBack() {
        $id('vaWiz').hidden = false;
        if (OPT && OPT.canUse && OPT.quote) $id('vaQuote').hidden = false;
        $id('vaReport').hidden = true;
        $id('vaReport').classList.remove('is-drawn');
        show(0);
        $id('vaWiz').scrollIntoView({ behavior: 'auto', block: 'start' });
    }

    /* ---------------- the report, drawn ---------------- */
    // Google hands its grounding pages back through a redirect door; the
    // title is then the site itself, and the door is not worth reading out.
    // The host a source came from, or nothing for a search redirect: the
    // card names sources, it does not say how they were found.
    const host = (u) => { try { const h = new URL(u).hostname.replace(/^www\./, ''); return /vertexaisearch\.cloud\.google\.com$/.test(h) ? '' : h; } catch (_) { return ''; } };
    function drawReport(hostEl, item, mode, quiet) {
        const r = item.report || {};
        const p = item.params || {};
        // No emoji shortcodes, and no interjections -- a report saved before the
        // register was written may still open with "Aray," or "Naku!"; they go.
        const sweep = (t) => { const s = String(t || '').replace(/:[a-z0-9_-]+:/gi, '').replace(/(^|[.!?]\s+)(aray|naku|hala|grabe|whoa|wow|ooh|oh no|hay naku|sus|ay)\b[,!.]*\s*/gi, '$1').replace(/\s{2,}/g, ' ').trim(); return s.charAt(0).toUpperCase() + s.slice(1); };
        const top = r.topPick || {};
        const crop = (OPT ? OPT.crops.find((c) => c.key === p.crop) : null) || {};
        const order = Array.isArray(p.priorities) ? p.priorities : Object.keys(OPT ? OPT.priorities : {});
        const PR = (OPT && OPT.priorities) || {};
        const weightOf = (k) => (r.weights && r.weights[k] != null) ? Math.round(r.weights[k] * 100) : Math.round(((OPT && OPT.weights[order.indexOf(k)]) || 0) * 100);
        const topKey = order[0];
        const list = (xs) => (Array.isArray(xs) ? xs : []).filter((x) => x && String(x).trim());
        // A variety's record in a line: its year, days, trial yield and where it is for.
        const recLine = (f) => [f.type === 'hybrid' ? 'Hybrid' : (f.kind ? f.kind.charAt(0).toUpperCase() + f.kind.slice(1) : ''),
            f.year ? 'registered ' + f.year : (f.registered === false ? 'not registered' : ''),
            f.days ? Math.round(f.days) + ' ' + (f.daysUnit || 'days') : '',
            f.yield ? (Math.round(f.yield * 100) / 100) + ' ' + (f.yieldUnit || 't/ha') + (f.registered === false ? ' (seller)' : ' in trials') : '',
            f.where || ''].filter(Boolean).join(' · ');
        const goodAgainst = (f) => Object.entries(f.ratings || {}).filter(([, c]) => ['HR', 'R', 'MR', 'T'].includes(c)).slice(0, 3).map(([k]) => k).join(', ');
        const onFile = (f) => `<a class="va-onfile" href="${esc(f.url)}" target="_blank" rel="noopener"><b>📋 On record:</b><span>${esc(recLine(f))}</span><i>See its record ›</i></a>`;

        hostEl.innerHTML = `
            <div class="wtp-hero">
                <h2>${esc(crop.icon || '🌱')} ${esc(r.headline || ('Best ' + (crop.label || 'variety') + ' for your field'))}</h2>
                <p class="h-win">${esc(top.variety || '')}</p>
                ${notGov(top.by) ? `<p class="h-by">${esc(notGov(top.by))}</p>` : ''}
                <p class="h-why">${esc(sweep(top.why))}</p>
                ${(r.bestHybrid && r.bestInbred) ? `<p class="h-best"><span>Best hybrid: <b>${esc(r.bestHybrid)}</b></span><span>Best inbred: <b>${esc(r.bestInbred)}</b></span></p>` : ''}
                <div class="wtp-chips">
                    <span class="wtp-chip">📍 ${esc((p.lotName ? p.lotName + ', ' : '') + (p.location || ''))}</span>
                    <span class="wtp-chip">${esc(crop.label || '')}</span>
                    <span class="wtp-chip">Confidence: ${esc(r.confidence || 'moderate')}</span>
                    ${r.searched ? '<span class="wtp-chip">🔎 Deep analysis</span>' : ''}
                    ${item.charged ? `<span class="wtp-chip">${item.charged} credits</span>` : ''}
                </div>
            </div>

            <div class="wtp-card">
                <h3>Ranked by what matters to you</h3>
                <div class="va-order">${order.map((k, i) => `<span>${i + 1}. ${esc(PR[k]?.label || k)}<i>${weightOf(k)}%</i></span>`).join('')}</div>
            </div>

            ${(() => {
                /* Hybrids and inbreds ranked apart: the two are bought,
                   priced and grown differently, and a farmer chooses
                   between them before choosing within them. One card when
                   only one kind came back. */
                const all = r.ranking || [];
                const hybrids = all.filter((x) => x.type === 'hybrid');
                const inbreds = all.filter((x) => x.type !== 'hybrid');
                const groups = (hybrids.length && inbreds.length)
                    ? [['Hybrids', 'Buy new seed each season. Usually higher yield, but the seed costs more.', hybrids], ['Inbred and open pollinated', 'Seed you can save and replant. These are the public {{ \App\Support\Region::ph() ? 'NSIC registered' : 'registered' }} varieties.', inbreds]]
                    : [['The comparison, best first', '', all]];
                let delay = 0;
                return groups.map(([title, sub, rows]) => `
                <div class="wtp-card">
                    <h3>${esc(title)}</h3>
                    ${sub ? `<p class="va-group-sub">${esc(sub)}</p>` : ''}
                    ${rows.map((x, i) => {
                        const sc = x.scores || {};
                        const d = delay++;
                        return `
                        <div class="va-rec" style="transition-delay:${d * 70}ms">
                            <div class="va-rec-top">
                                <span class="wp-rank">${esc(String(x.rank || i + 1))}</span>
                                <span class="v-name"><b>${esc(x.variety || '')}${x.type === 'hybrid' ? ' <span class="va-type">hybrid</span>' : ''}</b>${notGov(x.by) ? `<small>${esc(notGov(x.by))}${x.released && x.released !== 'n/a' ? ' · released ' + esc(x.released) : ''}</small>` : (x.released && x.released !== 'n/a' ? `<small>released ${esc(x.released)}</small>` : '')}</span>
                                <span class="va-overall"><b>${esc(String(x.overall ?? ''))}</b><small>overall</small></span>
                            </div>
                            <div class="va-facts">
                                ${x.maturityDays && x.maturityDays !== 'n/a' ? `<span class="va-fact">⏱️ ${esc(x.maturityDays)}</span>` : ''}
                                ${x.yieldPotential && !/not published/i.test(x.yieldPotential) ? `<span class="va-fact">🌾 ${esc(x.yieldPotential)}</span>` : ''}
                            </div>
                            <div class="va-bars">
                                ${order.map((k) => `<div class="va-bar"><small>${esc(PR[k]?.label || k)}<i>${esc(String(Math.round(Number(sc[k]) || 0)))}</i></small><div class="wp-track"><span class="wp-fill${k === topKey ? ' is-top' : ''}" style="width:${Math.max(3, Math.min(100, Number(sc[k]) || 0))}%;transition-delay:${120 + d * 70}ms"></span></div></div>`).join('')}
                            </div>
                            ${(list(x.strengths).length || list(x.weaknesses).length) ? `<div class="va-pm">
                                ${list(x.strengths).length ? `<ul class="is-plus">${list(x.strengths).map((s) => `<li>${esc(s)}</li>`).join('')}</ul>` : ''}
                                ${list(x.weaknesses).length ? `<ul class="is-minus">${list(x.weaknesses).map((s) => `<li>${esc(s)}</li>`).join('')}</ul>` : ''}
                            </div>` : ''}
                            ${(() => {
                                // The derbies' and the farmers' own harvests of it (2026-10-17, the owner). No source on
                // a harvest, a derby or a ranked variety (2026-10-18): only "Sources Anee read" lists them.
                                const fr = (Array.isArray(x.fieldResults) ? x.fieldResults : []).filter((f) => f && String(f.yield || '').trim()).slice(0, 3);
                                return fr.length ? `<div class="va-field"><b>🌾 Real harvests</b>${fr.map((f) => `<span class="va-fr is-${esc(f.kind || 'derby')}"><em>${esc(({ derby: 'Derby', farmer: 'Farmer', trial: 'Trial' })[f.kind] || 'Harvest')}</em>${esc(f.yield)}${f.where ? ' · ' + esc(f.where) : ''}${f.when ? ' · ' + esc(f.when) : ''}</span>`).join('')}</div>` : '';
                            })()}
                            ${x.fitNotes ? `<p class="wp-why">${esc(sweep(x.fitNotes))}</p>` : ''}
                            ${x.record ? onFile(x.record) : ''}
                        </div>`;
                    }).join('')}
                </div>`).join('');
            })()}

            ${(r.givenVarieties || []).length ? `
            <div class="wtp-card">
                <h3>Your varieties, one by one</h3>
                ${(r.givenVarieties || []).map((g) => `
                    <div class="wtp-win-row is-note"><b>${esc(g.variety || '')}:</b><span>${esc(sweep(g.verdict))}${g.record ? ` <a class="va-file-all" href="${esc(g.record.url)}" target="_blank" rel="noopener">Its record ›</a>` : ''}</span></div>`).join('')}
            </div>` : ''}

            ${(r.newest || []).length ? `
            <div class="wtp-card">
                <h3>Newest in ${(window.ANEE_REGION || {}).ph === false ? (window.ANEE_REGION.name || 'your country') : 'the Philippines'}</h3>
                ${(r.newest || []).map((n) => `
                    <div class="wtp-win-row is-new"><b>🆕 ${esc(n.variety || '')}${n.year ? ' (' + esc(n.year) + ')' : ''}${notGov(n.by) ? ' · ' + esc(notGov(n.by)) : ''}:${n.inRanking === true ? '<span class="va-in">In the ranking</span>' : (n.inRanking === false ? '<span class="va-in is-out">Not ranked</span>' : '')}</b><span>${esc(sweep(n.note))}${n.record ? ` <a class="va-file-all" href="${esc(n.record.url)}" target="_blank" rel="noopener">Its record ›</a>` : ''}</span></div>`).join('')}
            </div>` : ''}

            ${(() => {
                // The newest derbies and yield contests found (2026-10-17): the nearest first.
                const ds = (Array.isArray(r.derbies) ? r.derbies : []).filter((d) => d && (d.results || []).length);
                return ds.length ? `
            <div class="wtp-card">
                <h3>🏆 Derbies and farmers' harvests</h3>
                <p class="va-group-sub">The newest field results Anee found, the nearest first. Real harvests count most in the yield score.</p>
                ${ds.map((d) => `<div class="va-derby"><b>${esc(d.name || 'Variety derby')}</b><small>${esc([d.where, d.when, notGov(d.by)].filter(Boolean).join(' · '))}</small>
                    <ol>${(d.results || []).slice(0, 8).map((x) => `<li><span>${esc(x.variety || '')}</span><b>${esc(x.yield || '')}</b></li>`).join('')}</ol>
                    </div>`).join('')}
            </div>` : '';
            })()}

            ${r.records && (r.records.shortlist || []).length ? `
            <div class="wtp-card">
                <h3>📋 By the numbers, from the variety records</h3>
                <p class="va-group-sub">The ${esc(String(r.records.shortlist.length))} varieties on file that fit your field best by trial days, yield, ratings and where they are recommended. A plain count from the NSIC and PhilRice records, set beside Anee's ranking.</p>
                <div class="va-file">${r.records.shortlist.map((f) => `
                    <a class="va-file-row" href="${esc(f.url)}" target="_blank" rel="noopener">
                        ${f.inRanking ? '<span class="f-in">In Anee\'s ranking</span>' : ''}
                        <b>${esc(f.name)}${f.alias ? ' · ' + esc(f.alias) : ''}</b>
                        <small>${esc(recLine(f))}</small>
                        ${goodAgainst(f) ? `<small>Holds up against ${esc(goodAgainst(f))}</small>` : ''}
                    </a>`).join('')}</div>
                ${r.records.finder ? `<a class="va-file-all" href="${esc(r.records.finder)}" target="_blank" rel="noopener">All ${esc(String(r.records.count))} varieties on record ›</a>` : ''}
            </div>` : ''}

            ${r.conditions ? `
            <div class="wtp-card">
                <h3>What your field and weather need</h3>
                <div class="va-cond">
                    <div class="va-cond-box"><b>Soil</b><p>${esc(sweep(r.conditions.soil))}</p></div>
                    <div class="va-cond-box"><b>Weather</b><p>${esc(sweep(r.conditions.weather))}</p></div>
                    ${list(r.conditions.risks).length ? `<div class="va-cond-box is-wide"><b>Risks to plan for</b><ul>${list(r.conditions.risks).map((s) => `<li>${esc(s)}</li>`).join('')}</ul></div>` : ''}
                </div>
            </div>` : ''}

            ${list(r.management).length ? `
            <div class="wtp-card">
                <h3>How to grow the top pick</h3>
                <ul class="va-mgmt">${list(r.management).map((s) => `<li>${esc(sweep(s))}</li>`).join('')}</ul>
            </div>` : ''}

            ${(p.fieldProfileSaid || []).length && window.aneeFieldCard ? `
            <div class="wtp-card">
                <h3>🌾 Your field in detail</h3>
                ${window.aneeFieldCard.said(p.fieldProfileSaid)}
            </div>` : ''}

            <div class="wtp-card">
                <h3>In plain words</h3>
                <p class="wtp-plain">${esc(sweep(r.summary))}</p>
                ${(r.dataGaps || []).length ? `
                    <h3 class="mt-4">What Anee could not confirm</h3>
                    <ul class="wtp-gap">${(r.dataGaps || []).map((g) => `<li>${esc(g)}</li>`).join('')}</ul>` : ''}
            </div>

            <div class="wtp-card">
                <h3>📚 Sources Anee read</h3>
                ${(r.webSources || []).length ? `<div class="va-links">${(() => { const seen = new Set(); return (r.webSources || []).map((s) => ({ name: s.title || host(s.url) || 'A published source', host: host(s.url) })).filter((x) => { const k = x.name.toLowerCase(); if (seen.has(k)) return false; seen.add(k); return true; }).map((x) => `
                    <span class="va-link is-plain"><span class="l-t">${esc(x.name)}</span>${x.host && x.host !== x.name ? `<span class="l-h">${esc(x.host)}</span>` : ''}</span>`).join(''); })()}</div>`
                    : (r.searched ? '<p class="wtp-fine">The list of pages did not come back this time.</p>'
                        : '<p class="va-nosearch">Anee could not reach the web this time. This answer comes from what she already knows, so it may miss the newest varieties. Try again later.</p>')}
            </div>

            <div class="wtp-card">
                <h3>🧭 A guide, not a promise</h3>
                <p class="wtp-fine">Trial and derby yields come from well kept plots, and your field will differ. This is a shortlist of varieties that match what you told us. Before you decide, ask the nearest DA or PhilRice office which seed you can get near you.</p>
            </div>

            <div class="wtp-acts">
                <button type="button" class="btn btn-primary w-full" data-va-attach>
                    ${OPT && OPT.aneeFace ? `<img class="wtp-anee-face" src="${esc(OPT.aneeFace)}" alt="">` : '🤖'} Attach to Anee
                </button>
                ${mode === 'fresh' ? `<button type="button" class="btn btn-white w-full" data-va-again>🔬 Run another research</button>` : ''}
                <button type="button" class="btn btn-white w-full" data-va-delete>🗑 Delete</button>
            </div>`;

        hostEl.hidden = false;
        if (!quiet) {
            requestAnimationFrame(() => requestAnimationFrame(() => hostEl.classList.add('is-drawn')));
            hostEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else if (hostEl.id === 'vaReport') {
            hostEl.classList.add('is-drawn');
        }

        hostEl.querySelector('[data-va-attach]').addEventListener('click', () => {
            if (item.savedId) window.location.href = U.anee + '?analysis=' + item.savedId;
        });
        hostEl.querySelector('[data-va-again]')?.addEventListener('click', () => { if (VIEW_MODE === 'fresh') { closeView(); return; } closeView(); resetWizard(); wizardBack(); });
        hostEl.querySelector('[data-va-delete]').addEventListener('click', async () => {
            const delId = item.savedId;
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Delete this research?', message: 'It goes to the Trash, where you can restore it for 30 days. The credits it used are not returned.', confirmText: 'Delete', danger: true })
                : confirm('Delete this research?');
            if (!ok) return;
            try {
                const res = await api(U.del(delId), { method: 'DELETE' });
                toast(res.message);
                hostEl.hidden = true;
                closeView();
                $id('vaReport').hidden = true; $id('vaSavedReport').hidden = true;
                loadSaved().catch(() => {});
                if (mode === 'fresh') wizardBack();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    /* ---------------- saved ---------------- */
    async function loadSaved() {
        const res = await api(U.list + '?_=' + Date.now(), { method: 'GET' });
        const rows = res.data.rows || [];
        VA_ROWS = rows;
        $id('vaSavedList').innerHTML = rows.map((r) => `
            <button type="button" class="wtp-saved" data-saved="${r.id}" data-trash-row>
                <span class="grow min-w-0"><b>${esc(r.title)}</b><small>${r.description ? esc(r.description) + ' · ' : ''}${esc(r.at)} · ${r.credits} credits</small>${window.userTags ? window.userTags.chips(r.tags) : ''}</span>
                <span role="button" tabindex="0" class="wtp-pen" data-meta="${r.id}" title="Edit name and description" aria-label="Edit ${esc(r.title)}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:.85rem;height:.85rem"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </span>${window.aneeTrash ? window.aneeTrash.btn(r.id, r.title) : ''}
                <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>`).join('');
        $id('vaSavedEmpty').classList.toggle('hidden', rows.length > 0);
    }

    let VA_ROWS = [];
    let VA_META_ID = null;
    document.addEventListener('click', async (e) => {
        const saveBtn = e.target.closest('#vaMetaSave');
        if (saveBtn && VA_META_ID !== null) {
            saveBtn.disabled = true;
            try {
                const res = await api(VA_META_URL, { method: 'POST', body: {
                    id: VA_META_ID,
                    title: $id('vaMetaTitle').value.trim(),
                    description: $id('vaMetaDesc').value.trim(),
                    tags: window.userTags ? window.userTags.value($id('vaMetaTags')) : [],
                } });
                toast(res.message);
                closeSheet('vaMetaSheet');
                loadSaved().catch(() => {});
            } catch (err) { toast(err.message, 'error'); }
            finally { saveBtn.disabled = false; }
            return;
        }
        // The bin on a saved row (2026-10-23): to the Trash, through the tool's own delete.
        const bin = e.target.closest('[data-trash]');
        if (bin && bin.closest('#vaSavedList')) {
            e.stopPropagation();
            const row = bin.closest('.wtp-saved');
            window.aneeTrash.run(bin, U.del(bin.dataset.trash), { title: row ? (row.querySelector('b') || {}).textContent : '', what: 'research', paid: true, onDone: () => loadSaved().catch(() => {}) });
            return;
        }
        const pen = e.target.closest('[data-meta]');
        if (pen && pen.closest('#vaSavedList')) {
            e.stopPropagation();
            const r = VA_ROWS.find((x) => String(x.id) === pen.getAttribute('data-meta'));
            if (!r) return;
            VA_META_ID = r.id;
            $id('vaMetaTitle').value = r.title || '';
            $id('vaMetaDesc').value = r.description || '';
            if (window.userTags) window.userTags.set($id('vaMetaTags'), r.tags || []);
            openSheet('vaMetaSheet');
            return;
        }
        const b = e.target.closest('[data-saved]');
        if (!b) return;
        try {
            const res = await api(U.one(b.getAttribute('data-saved')), { method: 'GET' });
            const item = { report: res.data.report, params: res.data.params, charged: res.data.credits, savedId: res.data.id };
            openView(item, 'saved');
        } catch (err) { toast(err.message, 'error'); }
    });

    /* ---------------- tabs ---------------- */
    const tab = (which) => {
        $id('vaGen').classList.toggle('hidden', which !== 'gen');
        $id('vaSavedPane').classList.toggle('hidden', which !== 'saved');
        $id('vaTabGen').classList.toggle('is-on', which === 'gen');
        $id('vaTabSaved').classList.toggle('is-on', which === 'saved');
        if (which === 'saved') loadSaved().catch((err) => toast(err.message, 'error'));
    };
    $id('vaTabGen').addEventListener('click', () => tab('gen'));
    $id('vaTabSaved').addEventListener('click', () => tab('saved'));

    if (window.api) boot();
    else window.addEventListener('load', boot, { once: true });
})();
</script>
@endsection

@push('sheets')
{{-- Rename a saved research and describe it in your own words. --}}
@include('partials.user-tags')
@include('partials.trash-row')
<div class="sheet hidden" id="vaMetaSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Edit this research</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="vaMetaTitle">Name</label>
            <input type="text" id="vaMetaTitle" class="form-input" maxlength="191">
        </div>
        <div>
            <label class="form-label" for="vaMetaDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="vaMetaDesc" class="form-textarea" rows="3" maxlength="2000"></textarea>
        </div>
        <div>
            <span class="form-label">Tags <span class="text-gray-400 font-normal">(optional)</span></span>
            <div class="ut-mount" id="vaMetaTags"></div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="vaMetaSave">Save changes</button>
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
