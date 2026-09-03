@extends('layouts.app')
@section('title', 'When to Plant')
@section('page-title', 'When to Plant')
@section('page-subtitle', 'The right window, argued from the climate')

@section('content')
<style>
    /* ---- WHEN TO PLANT -------------------------------------------------
       A wizard that walks, a report that draws itself. Everything animates
       on the house curve and holds still under reduced motion. */
    .wtp-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .wtp-tab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem;
        text-align: center; color: var(--color-gray-500); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; }
    .wtp-tab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }

    /* The price, said before anything is spent: one titled container that
       folds to its headline, holding two small cards — what it costs, and
       how to hold the answer. The fold ANIMATES (grid-rows trick) rather
       than snapping between two layouts. */
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
    .q-body { display: grid; grid-template-rows: 1fr; opacity: 1;
        transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-body { grid-template-rows: 0fr; opacity: 0; }
    .q-body-in { overflow: hidden; min-height: 0; display: grid; gap: .5rem; padding: 0 .9rem; }
    .q-body-in::after { content: ''; height: .4rem; }
    .q-card { border-radius: .7rem; padding: .6rem .75rem; font-size: .82rem; color: #3d5226;
        line-height: 1.5; background: rgb(255 255 255 / .6); border: 1px solid rgb(207 227 184 / .8); }

    /* The wizard: steps slide past each other; the rail says where you are. */
    .wtp-wiz { position: relative; overflow: hidden; }
    .wtp-step { display: none; }
    .wtp-step.is-on { display: block; animation: wtpIn .32s cubic-bezier(.22,1,.36,1) both; }
    @keyframes wtpIn { from { opacity: 0; transform: translateX(24px); } to { opacity: 1; transform: none; } }
    .wtp-step.is-back { animation-name: wtpBack; }
    @keyframes wtpBack { from { opacity: 0; transform: translateX(-24px); } to { opacity: 1; transform: none; } }
    .wtp-dots { display: flex; gap: .35rem; justify-content: center; margin: 1rem 0 .2rem; }
    .wtp-dot { width: .5rem; height: .5rem; border-radius: 999px; background: var(--color-gray-200);
        transition: all .28s cubic-bezier(.22,1,.36,1); }
    .wtp-dot.is-on { width: 1.4rem; background: var(--color-brand-600); }
    .wtp-q { font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); margin-bottom: .2rem; }
    .wtp-sub { font-size: .8rem; color: var(--color-gray-500); margin-bottom: .8rem; }

    .wtp-choices { display: grid; gap: .5rem; }
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

    /* The run button breathes the moving green the app's doors wear. */
    .wtp-run { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%;
        padding: .85rem 1rem; border-radius: 1rem; color: #fff; font-weight: 800; font-size: .95rem;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 30%, #3d6823 55%, #6b9f3d 80%, #8fc96a);
        background-size: 260% 100%; animation: wtpTide 5.5s ease-in-out infinite alternate;
        box-shadow: 0 10px 22px -12px rgb(61 104 35 / .65); }
    .wtp-run:disabled { opacity: .6; }
    @keyframes wtpTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }

    /* While the model thinks: a veil over the WHOLE page — tabs, buttons,
       everything — so nothing invites a click that would abandon the run. */
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

    /* Twelve bars: the year, scored. They grow when the report lands. */
    .wtp-months { display: flex; align-items: flex-end; gap: .3rem; height: 8rem; }
    .wtp-mcol { flex: 1 1 0; display: flex; flex-direction: column; align-items: center; gap: .25rem;
        height: 100%; justify-content: flex-end; min-width: 0; }
    .wtp-mbar { width: 100%; max-width: 1.6rem; border-radius: .3rem .3rem 0 0; background: var(--color-gray-200);
        transform-origin: bottom; transform: scaleY(0); min-height: 3px;
        transition: transform .6s cubic-bezier(.22,1,.36,1); position: relative; }
    .wtp-report.is-drawn .wtp-mbar { transform: scaleY(1); }
    .wtp-mbar.is-best { background: var(--color-brand-600); }
    .wtp-mbar.is-good { background: var(--color-brand-300); }
    .wtp-mbar.is-poor { background: #f0c274; }
    .wtp-mbar.is-bad { background: #fca5a5; }
    .wtp-mlbl { font-size: .58rem; font-weight: 700; color: var(--color-gray-500); }
    .wtp-mnote { font-size: .68rem; color: var(--color-gray-500); margin-top: .5rem; line-height: 1.5; }

    /* Planting to harvest: stage bands, widths in days. */
    .wtp-line { display: flex; border-radius: .6rem; overflow: hidden; height: 2.3rem; }
    .wtp-seg { display: flex; align-items: center; justify-content: center; font-size: .62rem; font-weight: 800;
        color: #fff; white-space: nowrap; overflow: hidden; min-width: 0;
        transform-origin: left; transform: scaleX(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .wtp-seg { transform: scaleX(1); }
    .wtp-legend { display: flex; flex-wrap: wrap; gap: .5rem .9rem; margin-top: .55rem; }
    .wtp-legend span { display: inline-flex; align-items: center; gap: .35rem; font-size: .72rem; color: var(--color-gray-600); }
    .wtp-legend i { width: .7rem; height: .7rem; border-radius: .2rem; }

    .wtp-threat { display: flex; gap: .6rem; padding: .6rem .7rem; border-radius: .7rem; margin-bottom: .45rem;
        font-size: .8rem; line-height: 1.5; border: 1px solid; opacity: 0; transform: translateY(6px);
        transition: all .45s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .wtp-threat { opacity: 1; transform: none; }
    .wtp-threat.sev-high { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
    .wtp-threat.sev-moderate { background: #fffbeb; border-color: #fde68a; color: #713f12; }
    .wtp-threat.sev-low { background: var(--color-gray-50); border-color: var(--color-gray-200); color: var(--color-gray-600); }
    .wtp-threat b { display: block; font-size: .72rem; text-transform: uppercase; letter-spacing: .03em; opacity: .8; }

    .wtp-gap { font-size: .78rem; color: var(--color-gray-500); line-height: 1.55; }
    .wtp-gap li { list-style: disc; margin-left: 1.1rem; }
    .wtp-fine { font-size: .72rem; color: var(--color-gray-400); line-height: 1.55; }

    .wtp-acts { display: grid; gap: .5rem; }
    @media (min-width: 640px) { .wtp-acts { grid-template-columns: 1fr 1fr; } }

    /* Saved rows. */
    .wtp-saved { display: flex; align-items: center; gap: .7rem; width: 100%; text-align: left;
        padding: .8rem .9rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
    .wtp-saved:hover { background: var(--color-gray-50); }
    .wtp-saved b { display: block; font-size: .88rem; color: var(--color-gray-900); }
    .wtp-saved small { color: var(--color-gray-400); font-size: .72rem; }

    /* Night. */
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
    html.dark .wtp-mbar { background: #2b3a1c; }
    html.dark .wtp-saved { border-color: #222b1a; }
    html.dark .wtp-saved:hover { background: #161e10; }
    html.dark .wtp-saved b { color: #e8efe1; }
    html.dark .wtp-threat.sev-high { background: #2a1414; border-color: #4c1d1d; color: #fca5a5; }
    html.dark .wtp-threat.sev-moderate { background: #241d10; border-color: #4d3a12; color: #f0c274; }
    html.dark .wtp-threat.sev-low { background: #161e10; border-color: #2b3a1c; color: #a8bd93; }
    /* The calendar, plainly: one green row to plant by, red rows to keep
       away from. */
    /* Flowing prose, not columns: the bold dates lead and the reason runs
       on after them, however narrow the screen. */
    .wtp-win { display: block; padding: .6rem .7rem;
        border-radius: .7rem; margin-bottom: .45rem; font-size: .84rem; line-height: 1.55;
        border: 1px solid; }
    .wtp-win b { margin-right: .3rem; }
    .wtp-win.is-go { background: #f0f7e8; border-color: #cfe3b8; color: #2d5016; }
    .wtp-win.is-no { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
    .wtp-win.is-no.sev-moderate { background: #fffbeb; border-color: #fde68a; color: #713f12; }
    html.dark .wtp-win.is-go { background: #1c2913; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .wtp-win.is-no { background: #2a1414; border-color: #4c1d1d; color: #fca5a5; }
    html.dark .wtp-win.is-no.sev-moderate { background: #241d10; border-color: #4d3a12; color: #f0c274; }

    /* The summary in its own voice: the dark remap outguns dark: utilities,
       so the words carry their own class. */
    .wtp-plain { font-size: .875rem; line-height: 1.65; color: var(--color-gray-700); }
    html.dark .wtp-plain { color: #d5e3c5; }

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
    @media (prefers-reduced-motion: reduce) { .crop-tag, .crop-row { transition: none; } }

    /* The attach button wears Anee's own face. */
    .wtp-anee-face { width: 1.15rem; height: 1.15rem; border-radius: 999px; object-fit: cover; }

    /* A report that fits a hand: tighter hero, chart labels kept, the
       timeline's in-band words stand down and the legend speaks for them. */
    @media (max-width: 639px) {
        .wtp-hero { padding: .9rem 1rem; }
        .wtp-hero .h-win { font-size: 1.15rem; }
        .wtp-card { padding: .8rem .85rem; }
        .wtp-months { gap: .2rem; height: 6.5rem; }
        .wtp-seg { font-size: 0; }
        .wtp-line { height: 1.5rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .wtp-step.is-on { animation: none; }
        .wtp-run { animation: none; }
        .wtp-mbar, .wtp-seg, .wtp-threat, .wtp-dot { transition: none; transform: none; opacity: 1; }
        .wtp-wait, .q-body, .q-c, .q-hint, .wtp-prob { transition: none; }
    }
</style>

<div class="max-w-2xl mx-auto">
    <div class="wtp-tabs" role="tablist">
        <button type="button" class="wtp-tab is-on" id="wtpTabGen">Generate</button>
        <button type="button" class="wtp-tab" id="wtpTabSaved">Saved</button>
    </div>

    <div id="wtpGen">
        <div class="wtp-quote" id="wtpQuote" hidden>
            <button type="button" class="q-head" id="wtpQuoteHead" aria-expanded="true">
                <span class="q-ico">🔎</span>
                <span class="q-title">Before you run one</span>
                <span class="q-hint" id="wtpQuoteHint"></span>
                <svg class="q-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="q-body">
                <div class="q-body-in">
                    <div class="q-card" id="wtpQuoteCost"></div>
                    <div class="q-card" id="wtpQuoteTreat">Treat the result as a guide: the weather always keeps some surprises. Still, a window built from real data is a much better starting point than guessing.</div>
                </div>
            </div>
        </div>

        <div class="card p-5 wtp-wiz" id="wtpWiz">
            {{-- Step 1: the year --}}
            <section class="wtp-step is-on" data-step="0">
                <p class="wtp-q">What year will you plant?</p>
                <p class="wtp-sub">The analysis reads the climate's patterns for that calendar year.</p>
                <div class="wtp-choices" id="wtpYears"></div>
            </section>
            {{-- Step 2: the season --}}
            <section class="wtp-step" data-step="1">
                <p class="wtp-q">Which cropping season?</p>
                <p class="wtp-sub">The window is searched inside the season you actually farm.</p>
                <div class="wtp-choices" id="wtpSeasons"></div>
            </section>
            {{-- Step 3: the crop. The lot form's tag-and-sheet, not a
                 dropdown: the tag wears the chosen crop's face, the sheet
                 holds the whole searchable catalogue. --}}
            <section class="wtp-step" data-step="2">
                <p class="wtp-q">What will you plant?</p>
                <p class="wtp-sub">The same catalogue your lots choose from.</p>
                <button type="button" class="crop-tag" id="wtpCropBtn">
                    <span class="crop-tag-e" id="wtpCropIcon">🌱</span>
                    <span class="crop-tag-t is-none" id="wtpCropNow">Choose the crop</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </section>
            {{-- Step 4: the variety --}}
            <section class="wtp-step" data-step="3">
                <p class="wtp-q">Which variety?</p>
                <p class="wtp-sub">Type it as it is sold — e.g. NSIC Rc222. If its data is not published, the analysis will say so rather than guess.</p>
                <input type="text" id="wtpVariety" class="form-input" maxlength="80" placeholder="Variety name (optional)">
            </section>
            {{-- Step 5: the place --}}
            <section class="wtp-step" data-step="4">
                <p class="wtp-q">Where is the field?</p>
                <p class="wtp-sub">Town and province is enough — the climate patterns differ by region.</p>
                <input type="text" id="wtpLocation" class="form-input" maxlength="160" placeholder="e.g. Urdaneta, Pangasinan">
            </section>
            {{-- Step 6: the troubles --}}
            <section class="wtp-step" data-step="5">
                <p class="wtp-q">What does this field struggle with?</p>
                <p class="wtp-sub">Tick what you have seen — each one moves the window.</p>
                <div class="wtp-probs" id="wtpProbs"></div>
            </section>
            {{-- Step 7: the decision --}}
            <section class="wtp-step" data-step="6">
                <p class="wtp-q">Ready to run it?</p>
                <p class="wtp-sub" id="wtpReview"></p>
                <button type="button" class="wtp-run" id="wtpRun">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span id="wtpRunSays">Run the analysis</span>
                </button>
                <p class="text-xs text-gray-400 mt-2 text-center" id="wtpRunFine"></p>
            </section>

            <div class="wtp-wait" id="wtpWait">
                <span class="w-spin"></span>
                <p><b>Reading the climate for your field…</b><br>Typhoon seasonality, the wet and dry rhythm, and your crop's own calendar. Half a minute, usually.</p>
                <p class="w-stay">Please stay on this screen — leaving it loses this run.</p>
            </div>

            <div class="wtp-dots" id="wtpDots"></div>
            <div class="wtp-nav" id="wtpNav">
                <button type="button" class="btn btn-white flex-1" id="wtpBack" disabled>Back</button>
                <button type="button" class="btn btn-primary flex-1" id="wtpNext">Next</button>
            </div>
        </div>

        <div class="wtp-report mt-4" id="wtpReport" hidden></div>
    </div>

    <div id="wtpSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div id="wtpSavedList"></div>
            <div id="wtpSavedEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900 dark:text-gray-100">Nothing saved yet</p>
                <p class="text-sm text-gray-400">Run an analysis and keep the ones worth keeping.</p>
            </div>
        </div>
        <div class="wtp-report mt-4" id="wtpSavedReport" hidden></div>
    </div>
</div>

{{-- The whole catalogue, searchable — the same rows the lot form shows. --}}
<div class="sheet hidden" id="wtpCropSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Choose a crop</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="crop-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="text" id="wtpCropSearch" class="form-input" autocomplete="off"
                   placeholder="Search — palay, sayote, mangga…">
            <button type="button" class="crop-search-x hidden" id="wtpCropSearchX" aria-label="Clear">✕</button>
        </div>
        <div id="wtpCropList"></div>
        <p class="crop-none hidden" id="wtpCropNone">Nothing matches that. Try the local name, or pick “Vegetables — mixed”.</p>
    </div>
</div>

<script>
(() => {
    const $id = (x) => document.getElementById(x);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const U = {
        options: '{{ route('wtp.options') }}',
        generate: '{{ route('wtp.generate') }}',
        save: '{{ route('wtp.save') }}',
        list: '{{ route('wtp.list') }}',
        one: (id) => '{{ url('/app/when-to-plant/one') }}/' + id,
        job: (id) => '{{ url('/app/when-to-plant/job') }}/' + id,
        del: (id) => '{{ url('/app/when-to-plant') }}/' + id,
        anee: '{{ route('ai.index') }}',
    };
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const SEG_HUES = ['#4a7c2a', '#6b9f3d', '#b45309', '#1d4ed8', '#5b21b6', '#0e7490', '#9f1239'];

    let OPT = null;
    const state = { year: null, season: null, crop: '', variety: '', location: '', problems: [] };
    let step = 0;
    const STEPS = 7;
    let LAST = null;   // {report, params, charged} — what Save keeps

    /* ---------------- boot ---------------- */
    async function boot() {
        try {
            const res = await api(U.options, { method: 'GET' });
            OPT = res.data;
            paintOptions();
        } catch (err) { toast(err.message, 'error'); }
    }

    function paintOptions() {
        $id('wtpYears').innerHTML = OPT.years.map((y) => `
            <button type="button" class="wtp-choice" data-year="${y}"><span class="c-e">🗓️</span><span>${y}${y === OPT.years[0] ? '<small>This year</small>' : ''}</span></button>`).join('');
        const seasonIcons = { dry: '☀️', wet: '🌧️', third: '🌗' };
        const seasonSubs = {
            dry: 'Roughly November to April in most lowland regions',
            wet: 'Roughly May to October in most lowland regions',
            third: 'The in-between window after the main two',
        };
        $id('wtpSeasons').innerHTML = Object.entries(OPT.seasons).map(([k, label]) => `
            <button type="button" class="wtp-choice" data-season="${k}"><span class="c-e">${seasonIcons[k] || '🌱'}</span><span>${esc(label)}<small>${esc(seasonSubs[k] || '')}</small></span></button>`).join('');
        const groups = {};
        OPT.crops.forEach((c) => { (groups[c.group] = groups[c.group] || []).push(c); });
        $id('wtpCropList').innerHTML = Object.entries(groups).map(([g, list]) => `
            <div class="crop-group" data-crop-group>
                <p class="crop-group-h">${esc(g)}</p>
                ${list.map((c) => `
                    <button type="button" class="crop-row" data-crop="${esc(c.key)}" data-find="${esc((c.label + ' ' + g).toLowerCase())}">
                        <span class="crop-row-e">${esc(c.icon)}</span>
                        <span class="crop-row-t">
                            <b>${esc(c.label)}</b>
                            <small>${c.perennial ? 'Tree crop — read by its age' : (c.maturity ? c.maturity + ' days to harvest' : '')}</small>
                        </span>
                    </button>`).join('')}
            </div>`).join('');
        $id('wtpProbs').innerHTML = Object.entries(OPT.problems).map(([k, label]) => `
            <label class="wtp-prob" data-prob="${k}"><input type="checkbox" value="${k}"><span>${esc(label)}</span></label>`).join('');
        $id('wtpDots').innerHTML = Array.from({ length: STEPS }, (_, i) => `<span class="wtp-dot${i === 0 ? ' is-on' : ''}"></span>`).join('');

        paintQuote();
    }

    /* The price note, in two sizes. The X shrinks it to the one line that
       matters and the choice is remembered; tapping the shrunk card brings
       the whole note back. */
    const QUOTE_MIN_KEY = 'anee-wtp-quote-min';
    let quoteMin = false;
    try { quoteMin = localStorage.getItem(QUOTE_MIN_KEY) === '1'; } catch (_) { /* opens full */ }

    function paintQuote() {
        const q = $id('wtpQuote');
        if (!OPT) return;
        if (!OPT.canUse) {
            $id('wtpQuoteCost').innerHTML = esc(OPT.whyNot || 'The analysis is not available right now.');
            $id('wtpQuoteTreat').hidden = true;
            $id('wtpQuoteHint').textContent = '';
            q.classList.remove('is-min');
            q.hidden = false;
            return;
        }
        if (!OPT.quote) { q.hidden = true; return; }
        q.classList.toggle('is-min', quoteMin);
        $id('wtpQuoteHead').setAttribute('aria-expanded', quoteMin ? 'false' : 'true');
        $id('wtpQuoteCost').innerHTML = `One analysis spends <b>${OPT.quote} credits</b>, and you have <b>${Number(OPT.balance).toLocaleString()}</b>. Nothing is charged until you press Run.`;
        $id('wtpQuoteTreat').hidden = false;
        // The folded card still says the one number that matters.
        $id('wtpQuoteHint').textContent = `${OPT.quote} credits`;
        q.hidden = false;
    }

    $id('wtpQuoteHead').addEventListener('click', () => {
        quoteMin = !quoteMin;
        try { localStorage.setItem(QUOTE_MIN_KEY, quoteMin ? '1' : '0'); } catch (_) { /* not remembered */ }
        paintQuote();
    });

    /* ---------------- the walk ---------------- */
    function show(n, backwards) {
        step = Math.max(0, Math.min(STEPS - 1, n));
        document.querySelectorAll('.wtp-step').forEach((s) => {
            const on = Number(s.getAttribute('data-step')) === step;
            s.classList.toggle('is-on', on);
            s.classList.toggle('is-back', on && !!backwards);
        });
        document.querySelectorAll('.wtp-dot').forEach((d, i) => d.classList.toggle('is-on', i <= step));
        $id('wtpBack').disabled = step === 0;
        $id('wtpNext').style.display = step === STEPS - 1 ? 'none' : '';
        if (step === STEPS - 1) review();
    }

    function stepReady() {
        switch (step) {
            case 0: return !!state.year || (toast('Pick the year first.', 'error'), false);
            case 1: return !!state.season || (toast('Pick the season.', 'error'), false);
            case 2: return !!state.crop || (toast('Pick the crop.', 'error'), false);
            case 3: state.variety = $id('wtpVariety').value.trim(); return true;
            case 4: state.location = $id('wtpLocation').value.trim();
                return !!state.location || (toast('Say where the field is.', 'error'), false);
            case 5: state.problems = [...document.querySelectorAll('#wtpProbs input:checked')].map((i) => i.value); return true;
            default: return true;
        }
    }

    function review() {
        const crop = (OPT.crops.find((c) => c.key === state.crop) || {});
        $id('wtpReview').innerHTML = `${esc(crop.icon || '')} <b>${esc(crop.label || '')}</b>`
            + `${state.variety ? ' · ' + esc(state.variety) : ''} · ${esc(OPT.seasons[state.season] || '')} ${state.year}`
            + ` · ${esc(state.location)}`
            + (state.problems.length ? `<br><span class="text-xs">${state.problems.length} field problem${state.problems.length === 1 ? '' : 's'} considered</span>` : '');
        $id('wtpRunSays').textContent = OPT.canUse && OPT.quote ? `Run the analysis (${OPT.quote} credits)` : 'Run the analysis';
        $id('wtpRunFine').textContent = OPT.canUse
            ? 'Charged to the same AI credits your questions use — it shows in your subscription’s credit log.'
            : (OPT.whyNot || '');
        $id('wtpRun').disabled = !OPT.canUse;
    }

    $id('wtpNext').addEventListener('click', () => { if (stepReady()) show(step + 1); });
    $id('wtpBack').addEventListener('click', () => show(step - 1, true));
    $id('wtpYears').addEventListener('click', (e) => {
        const b = e.target.closest('[data-year]');
        if (!b) return;
        state.year = Number(b.getAttribute('data-year'));
        document.querySelectorAll('#wtpYears .wtp-choice').forEach((c) => c.classList.toggle('is-on', c === b));
        setTimeout(() => show(1), 180);
    });
    $id('wtpSeasons').addEventListener('click', (e) => {
        const b = e.target.closest('[data-season]');
        if (!b) return;
        state.season = b.getAttribute('data-season');
        document.querySelectorAll('#wtpSeasons .wtp-choice').forEach((c) => c.classList.toggle('is-on', c === b));
        setTimeout(() => show(2), 180);
    });
    $id('wtpCropBtn').addEventListener('click', () => {
        openSheet('wtpCropSheet');
        if (!window.matchMedia('(hover: none)').matches) {
            setTimeout(() => $id('wtpCropSearch')?.focus(), 280);
        }
    });
    $id('wtpCropSheet').addEventListener('click', (e) => {
        const row = e.target.closest('.crop-row');
        if (!row) return;
        state.crop = row.getAttribute('data-crop');
        const c = OPT.crops.find((x) => x.key === state.crop) || {};
        $id('wtpCropIcon').textContent = c.icon || '🌱';
        const now = $id('wtpCropNow');
        now.textContent = c.label || 'Choose the crop';
        now.classList.remove('is-none');
        closeSheet('wtpCropSheet');
        setTimeout(() => show(3), 220);
    });
    const cropSift = () => {
        const q = ($id('wtpCropSearch').value || '').trim().toLowerCase();
        $id('wtpCropSearchX').classList.toggle('hidden', !q);
        let shown = 0;
        document.querySelectorAll('#wtpCropList [data-crop-group]').forEach((g) => {
            let left = 0;
            g.querySelectorAll('.crop-row').forEach((r) => {
                const hit = !q || (r.getAttribute('data-find') || '').includes(q);
                r.hidden = !hit;
                if (hit) left++;
            });
            g.hidden = left === 0;
            shown += left;
        });
        $id('wtpCropNone').classList.toggle('hidden', shown > 0);
    };
    $id('wtpCropSearch').addEventListener('input', cropSift);
    $id('wtpCropSearchX').addEventListener('click', () => {
        $id('wtpCropSearch').value = '';
        cropSift();
        $id('wtpCropSearch').focus();
    });

    /* Some troubles cannot share a field: clay that cracks is not sand that
       drains, a field that floods is not fast-draining, one water answer at
       a time, one waterside at a time. Ticking one quietly unticks its
       opposite instead of letting the form claim both. */
    const PROB_FOES = {
        cracking: ['sandy'],
        sandy: ['cracking', 'floods'],
        floods: ['sandy'],
        river: ['sea'],
        sea: ['river'],
        water_source: ['rainfed'],
        rainfed: ['water_source'],
    };
    $id('wtpProbs').addEventListener('change', (e) => {
        const l = e.target.closest('.wtp-prob');
        if (l) l.classList.toggle('is-on', e.target.checked);
        if (!e.target.checked) return;
        (PROB_FOES[e.target.value] || []).forEach((k) => {
            const foe = document.querySelector(`#wtpProbs input[value="${k}"]`);
            if (foe && foe.checked) {
                foe.checked = false;
                foe.closest('.wtp-prob')?.classList.remove('is-on');
            }
        });
    });

    /* ---------------- the run ---------------- */
    $id('wtpRun').addEventListener('click', async () => {
        if (!stepReady()) return;
        const wiz = $id('wtpWiz');
        wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = 'none');
        $id('wtpWait').classList.add('is-on');
        $id('wtpReport').hidden = true;
        let landed = false;
        try {
            const res = await api(U.generate, { method: 'POST', body: {
                year: state.year, season: state.season, crop: state.crop,
                variety: state.variety, location: state.location, problems: state.problems,
            } });
            let data = res.data;
            /* The server answers at once and works after the reply; the page
               keeps the spinner up and asks the row how it is doing. A
               failed job comes back through api() as its own message. */
            if (data.pending) {
                for (let i = 0; i < 100 && (!data || data.status !== 'ready'); i++) {
                    await new Promise((r) => setTimeout(r, 3000));
                    const st = await api(U.job(data.id || res.data.id), { method: 'GET' });
                    if (st.data && st.data.status === 'ready') { data = st.data; break; }
                }
                if (!data || data.status !== 'ready') {
                    throw new Error('Still working — give it a minute, then look on the Saved tab.');
                }
            }
            LAST = { report: data.report, params: data.params, charged: data.charged, savedId: data.savedId || null };
            OPT.balance = data.balance;
            landed = true;
            drawReport($id('wtpReport'), LAST, 'fresh');
            toast(`Done — ${data.charged} credits used.`);
            loadSavedQuietly();
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            $id('wtpWait').classList.remove('is-on');
            wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = '');
            show(step);
            // The report has the floor: the form and its price bow out until
            // the farmer asks for another run.
            if (landed) { wiz.hidden = true; $id('wtpQuote').hidden = true; }
        }
    });

    function wizardBack() {
        $id('wtpWiz').hidden = false;
        if (OPT && OPT.canUse && OPT.quote) $id('wtpQuote').hidden = false;
        $id('wtpReport').hidden = true;
        $id('wtpReport').classList.remove('is-drawn');
        show(0);
        $id('wtpWiz').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /* ---------------- the report, drawn ---------------- */
    function drawReport(host, item, mode) {
        const r = item.report;
        const p = item.params;
        // Older saved reports carry the persona's :anee-…: shortcodes, which
        // have no renderer here — swept so prose reads as prose.
        const sweep = (t) => String(t || '').replace(/:[a-z0-9_-]+:/gi, '').replace(/\s{2,}/g, ' ').trim();
        const crop = (OPT ? OPT.crops.find((c) => c.key === p.crop) : null) || {};
        const bw = r.bestWindow || {};
        const m1 = MONTHS[(bw.fromMonth || 1) - 1];
        const m2 = MONTHS[(bw.toMonth || 1) - 1];
        const bestMonths = new Set();
        for (let m = bw.fromMonth; m; m = (m === bw.toMonth ? 0 : (m % 12) + 1)) { bestMonths.add(m); if (bestMonths.size > 12) break; }

        const scores = (r.monthScores || []).slice(0, 12);

        const windowsCard = `
            <div class="wtp-card">
                <h3>The calendar, plainly</h3>
                <div class="wtp-win is-go"><b>🌱 Plant: ${esc(bw.label || '')}</b><span>${esc(sweep(bw.why))}</span></div>
                ${(r.avoidWindows || []).map((w) => `
                    <div class="wtp-win is-no sev-${esc(w.severity === 'moderate' ? 'moderate' : 'high')}">
                        <b>⛔ Avoid ${esc(w.label || '')}:</b><span>${esc(w.why || '')}</span>
                    </div>`).join('')}
            </div>`;

        host.innerHTML = `
            <div class="wtp-hero">
                <h2>${esc(crop.icon || '🌱')} ${esc(crop.label || 'Your crop')} — ${esc(OPT ? OPT.seasons[p.season] || '' : '')} ${esc(String(p.year || ''))}</h2>
                <p class="h-win">${esc(bw.label || (m1 + ' ' + (bw.fromDay || '') + ' – ' + m2 + ' ' + (bw.toDay || '')))}</p>
                <p class="h-why">${esc(sweep(bw.why))}</p>
                <div class="wtp-chips">
                    <span class="wtp-chip">📍 ${esc(p.location || '')}</span>
                    ${p.variety ? `<span class="wtp-chip">🧬 ${esc(p.variety)}</span>` : ''}
                    <span class="wtp-chip">Confidence: ${esc(r.confidence || 'moderate')}</span>
                    ${item.charged ? `<span class="wtp-chip">${item.charged} credits</span>` : ''}
                </div>
            </div>

            ${windowsCard}

            <div class="wtp-card">
                <h3>How each month scores for planting</h3>
                <div class="wtp-months">
                    ${scores.map((s) => {
                        const cls = bestMonths.has(s.month) ? 'is-best' : (s.score >= 65 ? 'is-good' : (s.score >= 35 ? 'is-poor' : 'is-bad'));
                        return `<div class="wtp-mcol">
                            <div class="wtp-mbar ${cls}" style="height:${Math.max(4, s.score)}%" title="${esc(s.note || '')}"></div>
                            <span class="wtp-mlbl">${MONTHS[(s.month || 1) - 1]}</span>
                        </div>`;
                    }).join('')}
                </div>
                <p class="wtp-mnote">Green is the recommended window; lighter green still works, amber is risky, red is asking for trouble. Hover a bar for its note.</p>
            </div>

            ${(r.threats || []).length ? `
            <div class="wtp-card">
                <h3>If you plant outside the window</h3>
                ${(r.threats || []).map((t, i) => `
                    <div class="wtp-threat sev-${esc(t.severity || 'moderate')}" style="transition-delay:${i * 80}ms">
                        <span>⚠️</span>
                        <span><b>${esc(t.whenNot || '')}</b>${esc(t.threat || '')}</span>
                    </div>`).join('')}
            </div>` : ''}

            <div class="wtp-card">
                <h3>In plain words</h3>
                <p class="wtp-plain">${esc(sweep(r.summary))}</p>
                ${(r.dataGaps || []).length ? `
                    <h3 class="mt-4">What this analysis could not know</h3>
                    <ul class="wtp-gap">${(r.dataGaps || []).map((g) => `<li>${esc(g)}</li>`).join('')}</ul>` : ''}
            </div>

            <div class="wtp-card">
                <h3>🧭 A guide, not a promise</h3>
                <p class="wtp-fine">Weather and climate carry real uncertainty, and no analysis can see a particular storm. What this gives you is a data-grounded starting point — the patterns of past seasons weighed against your crop and your field — which beats deciding with nothing to compare against. Check PAGASA advisories as planting approaches.</p>
            </div>

            <div class="wtp-acts">
                <button type="button" class="btn btn-primary w-full" id="${mode === 'fresh' ? 'wtpAttach' : 'wtpAttachSaved'}">
                    ${OPT && OPT.aneeFace ? `<img class="wtp-anee-face" src="${esc(OPT.aneeFace)}" alt="">` : '🤖'} Attach to Anee
                </button>
                ${mode === 'fresh' ? `<button type="button" class="btn btn-white w-full" id="wtpAgain">⚡ Run another analysis</button>` : ''}
                <button type="button" class="btn btn-white w-full" id="wtpDelete">🗑 Delete</button>
            </div>`;

        host.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => host.classList.add('is-drawn')));
        host.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // A finished run is already on the shelf, so both views carry the
        // same three verbs: attach, run again (fresh only), delete.
        host.querySelector(mode === 'fresh' ? '#wtpAttach' : '#wtpAttachSaved').addEventListener('click', () => {
            if (item.savedId) window.location.href = U.anee + '?analysis=' + item.savedId;
        });
        if (mode === 'fresh') host.querySelector('#wtpAgain').addEventListener('click', wizardBack);
        host.querySelector('#wtpDelete').addEventListener('click', async () => {
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Delete this analysis?', message: 'The credits it cost are already spent; only the report goes.', confirmText: 'Delete', danger: true })
                : confirm('Delete this analysis?');
            if (!ok) return;
            try {
                const res = await api(U.del(item.savedId), { method: 'DELETE' });
                toast(res.message);
                host.hidden = true;
                loadSaved();
                if (mode === 'fresh') wizardBack();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    /* The shelf list, refreshed without switching tabs — a finished run
       already lives there. */
    function loadSavedQuietly() { loadSaved().catch(() => {}); }

    /* ---------------- saved ---------------- */
    async function loadSaved() {
        try {
            // eslint-disable-next-line no-empty
            const res = await api(U.list, { method: 'GET' });
            const rows = res.data.rows || [];
            $id('wtpSavedList').innerHTML = rows.map((r) => `
                <button type="button" class="wtp-saved" data-saved="${r.id}">
                    <span class="grow min-w-0"><b>${esc(r.title)}</b><small>${esc(r.at)} · ${r.credits} credits</small></span>
                    <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>`).join('');
            $id('wtpSavedEmpty').classList.toggle('hidden', rows.length > 0);
        } catch (err) { toast(err.message, 'error'); }
    }

    document.addEventListener('click', async (e) => {
        const b = e.target.closest('[data-saved]');
        if (!b) return;
        try {
            const res = await api(U.one(b.getAttribute('data-saved')), { method: 'GET' });
            const host = $id('wtpSavedReport');
            host.classList.remove('is-drawn');
            drawReport(host, {
                report: res.data.report, params: res.data.params,
                charged: res.data.credits, savedId: res.data.id,
            }, 'saved');
        } catch (err) { toast(err.message, 'error'); }
    });

    /* ---------------- tabs ---------------- */
    const tab = (which) => {
        $id('wtpGen').classList.toggle('hidden', which !== 'gen');
        $id('wtpSavedPane').classList.toggle('hidden', which !== 'saved');
        $id('wtpTabGen').classList.toggle('is-on', which === 'gen');
        $id('wtpTabSaved').classList.toggle('is-on', which === 'saved');
        if (which === 'saved') loadSaved();
    };
    $id('wtpTabGen').addEventListener('click', () => tab('gen'));
    $id('wtpTabSaved').addEventListener('click', () => tab('saved'));

    if (window.api) boot();
    else window.addEventListener('load', boot, { once: true });
})();
</script>
@endsection
