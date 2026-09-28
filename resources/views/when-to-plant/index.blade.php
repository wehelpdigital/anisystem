@extends('layouts.app')
@section('title', 'When to Plant')
@section('page-title', 'When to Plant')
@section('page-subtitle', 'The right window, argued from the climate')

@section('back', \App\Support\BackTo::url(route('app.dashboard')))
{{-- Back is the dashboard, or wherever the door that opened this page said
     it stood (?from=, App\Support\BackTo). It used to be history.back()
     whenever the referrer was on this site, which after a reload, a
     redirect or a page's own link went back to this page itself. --}}


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
    .q-body { overflow: hidden; opacity: 1;
        transition: max-height .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .is-min .q-body { max-height: 0; opacity: 0; }
    .q-body-in { min-height: 0; display: grid; gap: .5rem; padding: 0 .9rem; }
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

    /* THE MONTHS, picked as a range: this month and the next 23, a year to
       a block. The two ends are solid, the months between tinted; every
       change of state eases on the house curve. */
    .wtp-mpick { display: grid; gap: .8rem; }
    .wtp-myear-h { font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase;
        color: var(--color-gray-500); margin-bottom: .35rem; }
    .wtp-mgrid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .35rem; }
    @media (min-width: 640px) { .wtp-mgrid { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
    .wtp-mchip { padding: .6rem .25rem; border-radius: .75rem; border: 1.5px solid var(--color-gray-200);
        background: var(--color-white); font-weight: 800; font-size: .86rem; color: var(--color-gray-700);
        text-align: center; cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1),
            color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .wtp-mchip:hover { transform: translateY(-1px); }
    .wtp-mchip.is-in { background: var(--color-brand-50); border-color: var(--color-brand-300); color: var(--color-brand-800); }
    .wtp-mchip.is-end { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    .wtp-mchip:disabled { opacity: .35; cursor: not-allowed; transform: none; }
    .wtp-mtags { display: grid; gap: .6rem; }
    @media (min-width: 480px) { .wtp-mtags { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .wtp-mtags > div { min-width: 0; }
    .wtp-mtags .crop-tag { width: 100%; max-width: 100%; min-width: 0; }
    .wtp-mtags .crop-tag-t { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .wtp-msay { display: flex; align-items: center; gap: .6rem; margin-top: .8rem; padding: .6rem .75rem;
        border-radius: .8rem; background: #f4f9ee; border: 1px solid #dcebc9; font-size: .82rem; color: #3d5226;
        transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .wtp-msay b { color: #1f3a10; }
    .wtp-msay span { flex: 1 1 auto; min-width: 0; }
    .wtp-msay button { flex: none; font-size: .76rem; font-weight: 800; color: var(--color-brand-700); padding: .2rem .45rem; border-radius: .5rem; }
    .wtp-msay button:hover { background: rgb(255 255 255 / .7); }
    .wtp-msay.is-empty { opacity: .75; }
    /* The report's chart marks the farmer's own months under their bars. */
    .wtp-mlbl.is-mine { color: var(--color-brand-700); font-weight: 900; }
    .wtp-mlbl.is-mine::after { content: ''; display: block; width: .3rem; height: .3rem; margin: .12rem auto 0; border-radius: 999px; background: currentColor; }

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
    /* A grid item's automatic minimum is its content's width — a chart's
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

    /* THE BEST WEEKS, RANKED: the heart of the report. The first is the
       green one; each row's bar fills when the report lands. */
    .wtp-wks { display: grid; gap: .6rem; }
    .wtp-wk { display: flex; gap: .7rem; padding: .75rem .8rem; border-radius: .9rem;
        border: 1px solid var(--color-gray-200); background: var(--color-white); }
    .wtp-wk.is-top { border-color: #9cc97a; background: linear-gradient(135deg, #f3f9ec, #e7f2da); }
    .wtp-wk-n { flex: none; width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center;
        font-weight: 900; font-size: .95rem; color: #3d5226; background: #eef4e6; }
    .wtp-wk.is-top .wtp-wk-n { background: #4a7c2a; color: #fff; }
    .wtp-wk-b { flex: 1 1 auto; min-width: 0; }
    .wtp-wk-h { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; }
    .wtp-wk-h b { font-size: .95rem; font-weight: 800; color: var(--color-gray-900); }
    .wtp-wk-h small { flex: none; font-size: .72rem; font-weight: 800; color: #3d6823; }
    .wtp-wk-bar { height: 6px; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; margin: .35rem 0 .45rem; }
    .wtp-wk-bar i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #8fc96a, #4a7c2a);
        transform-origin: left; transform: scaleX(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .wtp-wk-bar i { transform: scaleX(var(--w)); }
    .wtp-wk p { font-size: .8rem; line-height: 1.5; color: var(--color-gray-700); }
    .wtp-wk-f { display: grid; gap: .2rem; margin-top: .4rem; font-size: .74rem; color: var(--color-gray-600); }
    .wtp-wk-f span b { color: var(--color-gray-800); font-weight: 700; }
    .wtp-wk-c { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .45rem; }
    .wtp-wk-c span { font-size: .68rem; font-weight: 700; padding: .16rem .5rem; border-radius: 999px; background: #eef4e6; color: #3d5226; }
    .wtp-wk-w { margin-top: .35rem; font-size: .74rem !important; color: #92400e !important; }
    html.dark .wtp-wk { background: #151b12; border-color: #2b3a1c; }
    html.dark .wtp-wk.is-top { background: linear-gradient(135deg, #1c2a14, #172013); border-color: #3f5a2a; }
    html.dark .wtp-wk-n { background: #22301a; color: #cfe6b8; }
    html.dark .wtp-wk-h b { color: #eef4e8; }
    html.dark .wtp-wk p, html.dark .wtp-wk-f { color: #b9c6ad; }
    html.dark .wtp-wk-f span b { color: #dfe9d4; }
    html.dark .wtp-wk-bar { background: #22301a; }
    html.dark .wtp-wk-c span { background: #22301a; color: #cfe6b8; }
    html.dark .wtp-wk-w { color: #fbbf24 !important; }

    /* Twelve bars: the year, scored. They grow when the report lands. */
    .wtp-months { display: flex; align-items: flex-end; gap: .3rem; height: 8rem; }
    .wtp-mcol { flex: 1 1 0; display: flex; flex-direction: column; align-items: center; gap: .25rem;
        height: 100%; justify-content: flex-end; min-width: 0; }
    .wtp-mbar { width: 100%; max-width: 1.6rem; border-radius: .3rem .3rem 0 0; background: var(--color-gray-200);
        transform-origin: bottom; transform: scaleY(0); min-height: 3px;
        transition: transform .6s cubic-bezier(.22,1,.36,1); position: relative; }
    .wtp-report.is-drawn .wtp-mbar { transform: scaleY(1); }
    .wtp-mbar.is-best { background: #4a7c2a; }
    .wtp-mbar.is-good { background: #8fc96a; }
    .wtp-mbar.is-poor { background: #f0b04a; }
    .wtp-mbar.is-bad { background: #ef7676; }
    .wtp-mlbl i { display: block; font-style: normal; font-size: .55rem; opacity: .7; line-height: 1; }
    .wtp-mlbl { font-size: .58rem; font-weight: 700; color: var(--color-gray-500); }
    .wtp-mnote { font-size: .68rem; color: var(--color-gray-500); margin-top: .5rem; line-height: 1.5; }
    /* TYPHOON CHANCES: one series, so one colour -- the risk chart's storm
       blue -- and the bar's height is the probability on a fixed 0-100%
       scale (a dashed guide at 50%). The farmer's months wear a dot. */
    .wtp-ty { position: relative; display: flex; align-items: flex-end; gap: 2px; height: 8rem;
        border-bottom: 1px solid var(--color-gray-200); }
    .wtp-ty::before { content: ''; position: absolute; left: 0; right: 0; bottom: 50%; border-top: 1px dashed var(--color-gray-200); }
    .wtp-ty-col { position: relative; flex: 1 1 0; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; height: 100%; }
    .wtp-ty-bar { width: 100%; max-width: 1.5rem; min-height: 2px; border-radius: 4px 4px 0 0; background: #2563eb;
        transform-origin: bottom; transform: scaleY(0); transition: transform .6s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .wtp-ty-bar { transform: scaleY(1); }
    .wtp-ty-bar.is-zero { background: var(--color-gray-200); }
    .wtp-ty-v { position: absolute; font-size: .62rem; font-weight: 800; color: var(--color-gray-700); white-space: nowrap; }
    .wtp-ty-lbls { display: flex; gap: 2px; padding-top: .3rem; }
    .wtp-ty-lbl { flex: 1 1 0; text-align: center; font-size: .58rem; font-weight: 700; color: var(--color-gray-500); }
    .wtp-ty-lbl.is-mine { color: var(--color-brand-700); font-weight: 900; }
    .wtp-ty-lbl.is-mine::after { content: ''; display: block; width: .3rem; height: .3rem; margin: .12rem auto 0; border-radius: 999px; background: currentColor; }
    .wtp-ty-axis { display: flex; justify-content: space-between; font-size: .6rem; color: var(--color-gray-400); margin-bottom: .2rem; }
    .wtp-ty-mine { margin-top: .6rem; font-size: .76rem; color: var(--color-gray-700); }
    .wtp-ty-mine b { color: var(--color-gray-900); }
    .wtp-ty-t { margin-top: .5rem; font-size: .74rem; }
    .wtp-ty-t summary { cursor: pointer; font-weight: 700; color: var(--color-brand-700); }
    .wtp-ty-t table { width: 100%; margin-top: .4rem; border-collapse: collapse; }
    .wtp-ty-t th, .wtp-ty-t td { text-align: left; padding: .2rem .3rem; border-bottom: 1px solid var(--color-gray-100); color: var(--color-gray-700); }
    .wtp-ty-t td:nth-child(2), .wtp-ty-t td:nth-child(3), .wtp-ty-t th:nth-child(2), .wtp-ty-t th:nth-child(3) { text-align: right; font-variant-numeric: tabular-nums; }
    html.dark .wtp-ty-bar { background: #60a5fa; }
    html.dark .wtp-ty-bar.is-zero, html.dark .wtp-ty { border-color: #2b3a1c; }
    html.dark .wtp-ty-bar.is-zero { background: #2b3a1c; }
    html.dark .wtp-ty::before { border-color: #2b3a1c; }
    html.dark .wtp-ty-v, html.dark .wtp-ty-mine, html.dark .wtp-ty-t th, html.dark .wtp-ty-t td { color: #cfdcc3; }
    html.dark .wtp-ty-mine b { color: #eef4e8; }
    html.dark .wtp-ty-t th, html.dark .wtp-ty-t td { border-color: #22301a; }

    /* Twenty years of risk, month by month: a stacked bar per month, one
       colour per kind, under the same months as the score chart. */
    .wtp-rk { display: flex; align-items: flex-end; gap: .3rem; height: 7.5rem; border-bottom: 1px solid var(--color-gray-200); padding-bottom: .15rem; }
    .wtp-rk-col { flex: 1 1 0; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
    .wtp-rk-bar { width: 100%; max-width: 1.6rem; display: flex; flex-direction: column-reverse; border-radius: .3rem .3rem 0 0; overflow: hidden;
        transform-origin: bottom; transform: scaleY(0); transition: transform .7s cubic-bezier(.22,1,.36,1); }
    .wtp-report.is-drawn .wtp-rk-bar { transform: scaleY(1); }
    .wtp-rk-seg { width: 100%; }
    .wtp-rk-seg.is-storm { background: #2563eb; } .wtp-rk-seg.is-flood { background: #0891b2; }
    .wtp-rk-seg.is-drought { background: #d97706; } .wtp-rk-seg.is-heat { background: #dc2626; } .wtp-rk-seg.is-frost { background: #7c3aed; }
    .wtp-rk-lbls { display: flex; gap: .3rem; padding-top: .3rem; }
    .wtp-rk-lbl { flex: 1 1 0; text-align: center; font-size: .58rem; font-weight: 700; color: var(--color-gray-500); }
    .wtp-rk-legend { display: flex; flex-wrap: wrap; gap: .3rem .7rem; margin-top: .55rem; }
    .wtp-rk-legend span { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 700; color: var(--color-gray-600); }
    .wtp-rk-legend i { width: .7rem; height: .7rem; border-radius: .2rem; display: inline-block; }
    .wtp-rk-ev { display: grid; gap: .35rem; margin-top: .7rem; }
    .wtp-rk-ev div { display: flex; gap: .55rem; align-items: flex-start; font-size: .78rem; line-height: 1.45; color: var(--color-gray-700); padding: .45rem .6rem; border-radius: .7rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .wtp-rk-ev b { flex: none; font-size: .72rem; color: var(--color-gray-900); min-width: 3.4rem; }
    .wtp-rk-ev small { display: block; font-size: .66rem; color: var(--color-gray-400); }
    .wtp-rk-ev .is-high b { color: #b91c1c; }
    html.dark .wtp-rk { border-color: #2b3a1c; }
    html.dark .wtp-rk-legend span { color: #b7c2ad; }
    html.dark .wtp-rk-ev div { background: #10150c; border-color: #222b1a; color: #b7c2ad; }
    html.dark .wtp-rk-ev b { color: #e8efe1; }
    html.dark .wtp-rk-ev .is-high b { color: #fca5a5; }
    .va-links { display: grid; gap: .3rem; }
    .va-link { display: flex; align-items: center; gap: .5rem; font-size: .78rem; color: var(--color-brand-700); text-decoration: none; padding: .35rem .5rem; border-radius: .6rem; min-width: 0; }
    .va-link:hover { background: var(--color-brand-50); }
    .va-link.is-plain { color: var(--color-gray-700); cursor: default; }
    .va-link.is-plain:hover { background: transparent; }
    html.dark .va-link.is-plain { color: #d5e3c5; }
    .va-link .l-t { min-width: 0; flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .va-link .l-h { flex: none; font-size: .66rem; color: var(--color-gray-400); max-width: 9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    html.dark .va-link { color: #a5c97e; }
    html.dark .va-link:hover { background: #22301a; }
    .wtp-shelf-search { position: relative; padding: .7rem .8rem; border-bottom: 1px solid var(--color-gray-100); }
    .wtp-shelf-search svg { position: absolute; left: 1.55rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: var(--color-gray-400); pointer-events: none; }
    .wtp-shelf-search .form-input { padding-left: 2.3rem; }
    .wtp-shelf-more { text-align: center; font-size: .74rem; color: var(--color-gray-400); padding: .8rem; }
    html.dark .wtp-shelf-search { border-color: #222b1a; }

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

    /* Chips on a light card (the hero's chips are white on green). */
    .wtp-chips-dark { margin-top: 0; margin-bottom: .2rem; }
    .wtp-chips-dark .wtp-chip { background: var(--color-brand-50); color: var(--color-brand-800); border: 1px solid var(--color-brand-100); }
    html.dark .wtp-chips-dark .wtp-chip { background: #22301a; color: #cfe6b8; border-color: #2b3a1c; }
    .wp-watch { font-size: .74rem; color: #92610e; }
    html.dark .wp-watch { color: #e0b95c; }
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
    html.dark .wtp-mchip { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .wtp-mchip.is-in { background: #22301a; border-color: #3f5a2a; color: #cfe6b8; }
    html.dark .wtp-mchip.is-end { background: #4a7c2a; border-color: #6b9f3d; color: #fff; }
    html.dark .wtp-msay { background: #172013; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .wtp-msay b { color: #eef4e8; }
    html.dark .wtp-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .wtp-card h3 { color: #e8efe1; }
    html.dark .wtp-mbar { background: #2b3a1c; }
    /* The band colours must outrank the dark base, or every bar is one green. */
    html.dark .wtp-mbar.is-best { background: #6b9f3d; }
    html.dark .wtp-mbar.is-good { background: #a5c97e; }
    html.dark .wtp-mbar.is-poor { background: #f0b04a; }
    html.dark .wtp-mbar.is-bad { background: #f08080; }
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
    .wtp-loc-country { margin-bottom: .8rem; }
    .wtp-loc-country .form-label { margin-bottom: .3rem; }

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
        .wtp-wk-bar i { transition: none; }
        .wtp-ty-bar { transition: none; transform: none; }
        .wtp-wait, .q-body, .q-c, .q-hint, .wtp-prob, .wtp-mchip, .wtp-msay { transition: none; transform: none; }
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
            {{-- Step 1: the place, first, as What to Plant asks it. The
                 field's country is the farmer's own (their account's) unless
                 they say otherwise. It sets the address words, the example
                 place, the crop book and whose climate record and agencies
                 the analysis reads. --}}
            <section class="wtp-step is-on" data-step="0">
                <p class="wtp-q">Where is the field?</p>
                <div class="wtp-loc-country">
                    <label class="form-label">Country of the field</label>
                    @include('partials.country-pick', ['id' => 'wtpCountry', 'name' => 'country', 'value' => \App\Support\Region::code()])
                </div>
                <p class="wtp-sub" id="wtpLocSub">{{ \App\Support\Region::ph() ? 'Town and province' : ((\App\Support\Region::address()['city']['label'] ?? 'City') . ' and ' . strtolower(\App\Support\Region::address()['region']['label'] ?? 'state')) }} is enough — the climate patterns differ by region.</p>
                <input type="text" id="wtpLocation" class="form-input" maxlength="160" placeholder="{{ \App\Support\Region::get('exampleLocation') }}">
            </section>
            {{-- Step 2: the MONTHS the farmer is weighing, not a named
                 season (the owner's call, 2026-09-28): the rains keep no
                 calendar any more, so the old wet / dry dates would steer the
                 answer wrong. A start and an end, up to twelve months. --}}
            <section class="wtp-step" data-step="1">
                <p class="wtp-q">When are you thinking of planting?</p>
                <p class="wtp-sub">The first and the last month you might plant. The old wet and dry season dates no longer hold, so each week is read on its own — and the months after yours are scored too, in case one of them is safer.</p>
                {{-- Two tags, the lot form's tag-and-sheet: each opens the
                     month sheet for its own end of the range. --}}
                <div class="wtp-mtags">
                    <div>
                        <span class="form-label">From</span>
                        <button type="button" class="crop-tag" id="wtpFromBtn" data-m-end="from">
                            <span class="crop-tag-e">🗓️</span>
                            <span class="crop-tag-t is-none" id="wtpFromNow">Pick a month</span>
                            <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                        </button>
                    </div>
                    <div>
                        <span class="form-label">To</span>
                        <button type="button" class="crop-tag" id="wtpToBtn" data-m-end="to">
                            <span class="crop-tag-e">🏁</span>
                            <span class="crop-tag-t is-none" id="wtpToNow">Pick a month</span>
                            <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                        </button>
                    </div>
                </div>
                <div class="wtp-msay is-empty" id="wtpMonthsSay" aria-live="polite"></div>
            </section>
            {{-- Step 4: the crop. The lot form's tag-and-sheet, not a
                 dropdown: the tag wears the chosen crop's face, the sheet
                 holds the searchable catalogue for the field's country. --}}
            <section class="wtp-step" data-step="2">
                <p class="wtp-q">What will you plant?</p>
                <p class="wtp-sub">The same catalogue your lots choose from.</p>
                <button type="button" class="crop-tag" id="wtpCropBtn">
                    <span class="crop-tag-e" id="wtpCropIcon">🌱</span>
                    <span class="crop-tag-t is-none" id="wtpCropNow">Choose the crop</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
            </section>
            {{-- Step 5: the variety --}}
            <section class="wtp-step" data-step="3">
                <p class="wtp-q">Which variety?</p>
                <p class="wtp-sub">Type it as it is sold — e.g. <span id="wtpVarEx">{{ \App\Support\Region::ph() ? 'NSIC Rc222' : 'Pioneer P1197' }}</span>. If its data is not published, the analysis will say so rather than guess.</p>
                <input type="text" id="wtpVariety" class="form-input" maxlength="80" placeholder="Variety name (optional)">
            </section>
            {{-- Step 6: the troubles --}}
            <section class="wtp-step" data-step="4">
                <p class="wtp-q">What does this field struggle with?</p>
                <p class="wtp-sub">Tick what you have seen — each one moves the window.</p>
                <div class="wtp-probs" id="wtpProbs"></div>
            </section>
            {{-- Step 7: the decision --}}
            <section class="wtp-step" data-step="5">
                <p class="wtp-q">Ready to run it?</p>
                <p class="wtp-sub" id="wtpReview"></p>
                <button type="button" class="wtp-run" id="wtpRun">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span id="wtpRunSays">Run the analysis</span>
                </button>
                <p class="text-xs text-gray-400 mt-2 text-center" id="wtpRunFine"></p>
            </section>

            {{-- The wait: Anee's face at work, shared by every AI run. --}}
            @include('sm.partials.anee-wait')

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
            <div class="wtp-shelf-search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                <input type="search" id="wtpSavedSearch" class="form-input" placeholder="Search your analyses" autocomplete="off" aria-label="Search saved analyses">
            </div>
            <div id="wtpSavedList"></div>
            <div class="wtp-shelf-more" id="wtpSavedMore" hidden>Loading more…</div>
            <div id="wtpSavedEmpty" class="hidden text-center py-10">
                <p class="font-bold text-gray-900">Nothing saved yet</p>
                <p class="text-sm text-gray-400">Run an analysis and keep the ones worth keeping.</p>
            </div>
        </div>
        <div class="wtp-report mt-4" id="wtpSavedReport" hidden></div>
    </div>

    <div class="va-view" id="wtpView" hidden role="dialog" aria-modal="true" aria-label="When to plant analysis">
        <div class="va-view-bar">
            <b id="wtpViewTitle">When to plant</b>
            <button type="button" class="va-view-x" id="wtpViewX" aria-label="Close">✕</button>
        </div>
        <div class="va-view-body"><div class="wtp-report" id="wtpViewReport"></div></div>
    </div>
</div>

{{-- One end of the planting months at a time: this month and the next 23. --}}
<div class="sheet hidden" id="wtpMonthSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="wtpMonthSheetTitle">From which month?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="form-hint mt-0 mb-3" id="wtpMonthSheetHint"></p>
        <div class="wtp-mpick" id="wtpMonths"></div>
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
                   placeholder="{{ \App\Support\Region::t('cropSearch') }}">
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
    const WTP_META_URL = '{{ route('wtp.meta') }}';
    const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const SEG_HUES = ['#4a7c2a', '#6b9f3d', '#b45309', '#1d4ed8', '#5b21b6', '#0e7490', '#9f1239'];

    let OPT = null;
    /* from / to: months as one number (year * 12 + month - 1), null until
       picked; the From and To tags each set their own end. */
    const state = { from: null, to: null, crop: '', variety: '', location: '', problems: [], country: '' };
    const MONTHS_LONG = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const ym = (n) => ({ year: Math.floor(n / 12), month: (n % 12) + 1 });
    const monthIdx = (year, month) => Number(year) * 12 + Number(month) - 1;
    /* The months as words: "Nov 2026 – Jan 2027", or one month alone. An
       analysis saved before the months replaced the season keeps its
       season's name. */
    const whenSaid = (p) => {
        if (p && p.fromMonth) {
            const a = MONTHS[p.fromMonth - 1] + ' ' + p.fromYear;
            const b = MONTHS[(p.toMonth || p.fromMonth) - 1] + ' ' + (p.toYear || p.fromYear);
            return a === b ? a : a + ' – ' + b;
        }
        return seasonSaid((p || {}).season, (p || {}).year, (p || {}).country);
    };
    // "Dry season 2026–27": the season named with the years it actually spans.
    /* The seasons offered are the FIELD's country's: dry / wet / third crop
       for a Philippine field, spring / summer / autumn / winter elsewhere
       (window.ANEE_REGION_RULES comes with the country picker). */
    const RULES = () => (window.ANEE_REGION_RULES || {});
    const rulesFor = (code) => RULES()[code] || RULES()['*'] || {};
    /* A country's own name, from the picker's list: the rules only know the
       configured few and call every other country "International". */
    const nameOf = (code) => ((document.querySelector(`#wtpCountryList [data-country="${code}"] .country-row-t`) || {}).textContent || '').trim()
        || (RULES()[code] || {}).name || code || '';
    // "in the Philippines", "for the United States": the few names that take "the".
    const countryName = (code) => { const n = nameOf(code) || 'that country'; return ['PH', 'US', 'GB', 'NL', 'AE'].includes(code) ? 'the ' + n : n; };
    const seasonsFor = (code) => { const r = rulesFor(code); return (r.seasons && Object.keys(r.seasons).length) ? r.seasons : ((OPT && OPT.seasons) || {}); };
    const seasonSaid = (season, year, country) => {
        const y = Number(year) || 0;
        const label = seasonsFor(country || state.country || (OPT && OPT.country))[season] || (OPT && OPT.seasons[season]) || '';
        const crosses = season === 'dry' || season === 'winter';
        return crosses && y ? `${label} ${y}–${String(y + 1).slice(-2)}` : `${label} ${y || ''}`.trim();
    };
    let step = 0;
    const STEPS = 6;
    // A crop let go because the field's country changed: the step that asks
    // for it again says why.
    let cropDropped = false;
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
        // The tag says the account's country until the farmer picks another.
        state.country = state.country || ($id('wtpCountry') || {}).value || OPT.country || ((window.ANEE_REGION || {}).code) || 'PH';
        paintMonths();
        paintCrops();
        $id('wtpProbs').innerHTML = Object.entries(OPT.problems).map(([k, label]) => `
            <label class="wtp-prob" data-prob="${k}"><input type="checkbox" value="${k}"><span>${esc(label)}</span></label>`).join('');
        $id('wtpDots').innerHTML = Array.from({ length: STEPS }, (_, i) => `<span class="wtp-dot${i === 0 ? ' is-on' : ''}"></span>`).join('');

        paintQuote();
    }

    /* ---------------- the months ---------------- */
    const FIRST = () => monthIdx(OPT.monthsFrom.year, OPT.monthsFrom.month);
    const MAX_SPAN = () => Number(OPT.maxSpan) || 12;
    /* Which end the month sheet is choosing: 'from' or 'to'. */
    let M_END = 'from';
    const monthWords = (i) => MONTHS_LONG[ym(i).month - 1] + ' ' + ym(i).year;
    function paintMonths() {
        if (!OPT || !OPT.monthsFrom) return;
        const first = FIRST();
        const n = Number(OPT.monthsAhead) || 24;
        const byYear = {};
        for (let i = first; i < first + n; i++) (byYear[ym(i).year] = byYear[ym(i).year] || []).push(i);
        $id('wtpMonths').innerHTML = Object.entries(byYear).map(([y, list]) => `
            <div class="wtp-myear">
                <p class="wtp-myear-h">${esc(y)}${Number(y) === OPT.monthsFrom.year ? ' · this year' : ''}</p>
                <div class="wtp-mgrid">${list.map((i) => `<button type="button" class="wtp-mchip" data-m="${i}" aria-pressed="false" aria-label="${esc(monthWords(i))}">${MONTHS[ym(i).month - 1]}</button>`).join('')}</div>
            </div>`).join('');
        paintRange();
    }
    function paintRange() {
        const { from, to } = state;
        // In the To sheet a month before From, or more than a year past it,
        // is not a choice.
        const lo = M_END === 'to' && from !== null ? from : -Infinity;
        const hi = M_END === 'to' && from !== null ? from + MAX_SPAN() - 1 : Infinity;
        document.querySelectorAll('#wtpMonths .wtp-mchip').forEach((c) => {
            const i = Number(c.getAttribute('data-m'));
            const inside = from !== null && i >= from && i <= to;
            const end = from !== null && (i === from || i === to);
            c.classList.toggle('is-in', inside && !end);
            c.classList.toggle('is-end', end);
            c.disabled = i < lo || i > hi;
            c.setAttribute('aria-pressed', inside ? 'true' : 'false');
        });
        [['wtpFromNow', from], ['wtpToNow', to]].forEach(([id, v]) => {
            const t = $id(id);
            t.textContent = v === null ? 'Pick a month' : monthWords(v);
            t.classList.toggle('is-none', v === null);
        });
        const say = $id('wtpMonthsSay');
        if (from === null) {
            say.classList.add('is-empty');
            say.innerHTML = '<span>No months picked yet.</span>';
            return;
        }
        say.classList.remove('is-empty');
        const n = to - from + 1;
        const a = ym(from), b = ym(to);
        const words = n === 1
            ? `<b>${MONTHS_LONG[a.month - 1]} ${a.year}</b> only`
            : `Between <b>${MONTHS_LONG[a.month - 1]} ${a.year}</b> and <b>${MONTHS_LONG[b.month - 1]} ${b.year}</b> · ${n} months`;
        say.innerHTML = `<span>${words}</span><button type="button" data-m-clear>Clear</button>`;
    }
    function openMonthSheet(end) {
        if (!OPT) return;
        // No start yet: the To tag asks for the start first.
        M_END = end === 'to' && state.from === null ? 'from' : end;
        $id('wtpMonthSheetTitle').textContent = M_END === 'from' ? 'From which month?' : 'To which month?';
        $id('wtpMonthSheetHint').textContent = M_END === 'from'
            ? 'The first month you might plant.'
            : `The last month you might plant — up to ${MAX_SPAN()} months from ${monthWords(state.from)}.`;
        paintRange();
        openSheet('wtpMonthSheet');
    }
    document.querySelectorAll('[data-m-end]').forEach((b) => b.addEventListener('click', () => openMonthSheet(b.getAttribute('data-m-end'))));
    $id('wtpMonths').addEventListener('click', (e) => {
        const c = e.target.closest('[data-m]');
        if (!c || c.disabled) return;
        const i = Number(c.getAttribute('data-m'));
        let askTo = false;
        if (M_END === 'from') {
            askTo = state.to === null;
            state.from = i;
            // The end follows a start moved past it, or more than a year before it.
            if (state.to === null || state.to < i || state.to - i + 1 > MAX_SPAN()) state.to = i;
        } else {
            state.to = i;
        }
        paintRange();
        closeSheet('wtpMonthSheet');
        // A first start goes straight on to asking for the end.
        if (askTo) setTimeout(() => openMonthSheet('to'), 320);
    });
    $id('wtpMonthsSay').addEventListener('click', (e) => {
        if (!e.target.closest('[data-m-clear]')) return;
        state.from = null; state.to = null;
        paintRange();
    });

    /* The crop book for the FIELD's country, as What to Plant keeps it: the
       temperate crops (wheat, apple...) only for a field abroad. */
    const bookFor = () => ((OPT && OPT.crops) || []).filter((c) => state.country !== 'PH' || !c.intl);
    function paintCrops() {
        const groups = {};
        bookFor().forEach((c) => { (groups[c.group] = groups[c.group] || []).push(c); });
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
        if (($id('wtpCropSearch').value || '').trim()) cropSift();
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
        $id('wtpQuoteCost').innerHTML = `One analysis spends <b>${OPT.quote} credits</b>, and you have ${creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString())}. Nothing is charged until you press Run.`;
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
            case 0: state.location = $id('wtpLocation').value.trim();
                return !!state.location || (toast('Say where the field is.', 'error'), false);
            case 1: if (state.from === null) { toast('Pick the months you are thinking of planting in.', 'error'); return false; }
                if (state.to === null) state.to = state.from;
                return true;
            case 2: return !!state.crop || (toast(cropDropped ? `The crop list is different for ${countryName(state.country)} — pick the crop again.` : 'Pick the crop.', 'error'), false);
            case 3: state.variety = $id('wtpVariety').value.trim(); return true;
            case 4: state.problems = [...document.querySelectorAll('#wtpProbs input:checked')].map((i) => i.value); return true;
            default: {
                // The run itself: every answer still stands for the field's country.
                state.location = $id('wtpLocation').value.trim();
                const gone = state.from !== null && OPT && state.from < FIRST();
                const back = !state.location ? 0 : (state.from === null || gone) ? 1 : !state.crop ? 2 : -1;
                if (back < 0) return true;
                toast(['Say where the field is.', gone ? 'Those months have started going by — pick them again.' : 'Pick the months.', 'Pick the crop again.'][back], 'error');
                setTimeout(() => show(back, true), 250);
                return false;
            }
        }
    }

    function review() {
        const crop = (OPT.crops.find((c) => c.key === state.crop) || {});
        $id('wtpReview').innerHTML = `${esc(crop.icon || '')} <b>${esc(crop.label || '')}</b>`
            + `${state.variety ? ' · ' + esc(state.variety) : ''} · ${esc(whenSaid(rangeParams()))}`
            + ` · ${esc(state.location)}${state.country && state.country !== (OPT.country || '') ? ' · ' + esc(nameOf(state.country)) : ''}`
            + (state.problems.length ? `<br><span class="text-xs">${state.problems.length} field problem${state.problems.length === 1 ? '' : 's'} considered</span>` : '');
        $id('wtpRunSays').textContent = OPT.canUse && OPT.quote ? `Run the analysis (${OPT.quote} credits)` : 'Run the analysis';
        $id('wtpRunFine').textContent = OPT.canUse
            ? 'Charged to the same AI credits your questions use — it shows in your subscription’s credit log.'
            : (OPT.whyNot || '');
        $id('wtpRun').disabled = !OPT.canUse;
    }

    /* The picked months as the server asks for them. */
    const rangeParams = () => {
        if (state.from === null) return {};
        const a = ym(state.from), b = ym(state.to);
        return { fromMonth: a.month, fromYear: a.year, toMonth: b.month, toYear: b.year };
    };
    $id('wtpNext').addEventListener('click', () => { if (stepReady()) show(step + 1); });
    $id('wtpBack').addEventListener('click', () => show(step - 1, true));
    $id('wtpCountry')?.addEventListener('country:change', (e) => {
        const code = e.detail && e.detail.code;
        const r = e.detail && e.detail.rules;
        if (!code || !r) return;
        state.country = code;
        const city = (r.address && r.address.city && r.address.city.label) || 'City';
        const region = (r.address && r.address.region && r.address.region.label) || 'State / Region';
        $id('wtpLocSub').textContent = `${code === 'PH' ? 'Town and province' : city + ' and ' + region.toLowerCase()} is enough — the climate patterns differ by region.`;
        $id('wtpLocation').placeholder = r.exampleLocation || '';
        const ex = $id('wtpVarEx');
        if (ex) ex.textContent = code === 'PH' ? 'NSIC Rc222' : 'Pioneer P1197';
        // The crop book follows the field's country: a crop that is not in
        // the new one is let go.
        if (!OPT) return;
        paintCrops();
        if (state.crop && !bookFor().some((c) => c.key === state.crop)) {
            state.crop = '';
            cropDropped = true;
            $id('wtpCropIcon').textContent = '🌱';
            const now = $id('wtpCropNow');
            now.textContent = 'Choose the crop';
            now.classList.add('is-none');
        }
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
        cropDropped = false;
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
        window.aneeWait.show({ title: 'Anee is reading the climate for your field…', lines: ['Reading twenty years of storms, droughts and floods for your region…', state.country === 'PH' ? 'Typhoon seasonality and the wet-dry rhythm…' : 'Frost dates, heat and the rain rhythm of the region…', 'Your crop\'s own calendar against it…', 'Weighing each week of your months, one by one…', 'Ranking the best weeks, and the weeks to avoid…'], sub: 'Half a minute, usually.' });
        $id('wtpReport').hidden = true;
        let landed = false;
        try {
            const res = await api(U.generate, { method: 'POST', body: {
                ...rangeParams(), crop: state.crop,
                variety: state.variety, location: state.location, problems: state.problems,
                country: state.country,
            } });
            let data = res.data;
            /* The server answers at once and works after the reply; the page
               keeps the spinner up and asks the row how it is doing. A
               failed job comes back through api() as its own message. */
            if (data.pending) {
                // The shared poll: the bar, the clock and the hang check ride along.
                data = await window.aneeWait.poll({ id: data.id || res.data.id, job: U.job, phases: window.aneeWait.phases.research });
            }
            LAST = { report: data.report, params: data.params, charged: data.charged, savedId: data.savedId || null };
            OPT.balance = data.balance;
            landed = true;
            // Full screen first: the tabs and the wizard wait behind it. A
            // slip in drawing must not strand the veil: the result is saved.
            try { openView(LAST, 'fresh'); }
            catch (drawErr) { console.error(drawErr); toast('The analysis is saved on the Saved tab, but this page could not draw it.', 'error'); }
            await window.aneeWait.done({ title: 'Done!', line: `${data.charged} credits used.` });
            toast(`Done — ${data.charged} credits used.`);
            loadSavedQuietly();
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            if (!landed) window.aneeWait.fail();
            wiz.querySelectorAll('.wtp-step, .wtp-nav, .wtp-dots').forEach((el) => el.style.display = '');
            show(step);
            // The view has the floor; closing it brings the wizard back at
            // its first step, EMPTY, with the run already on the Saved tab.
            if (landed) { resetWizard(); wiz.hidden = true; $id('wtpQuote').hidden = true; }
        }
    });

    /* Full screen when it lands, and for anything opened from the shelf. */
    function openView(item, mode) {
        const view = $id('wtpView');
        VIEW_MODE = mode;
        const crop = (OPT ? OPT.crops.find((c) => c.key === (item.params || {}).crop) : null) || {};
        $id('wtpViewTitle').textContent = (crop.label ? crop.label + ' — ' : '') + whenSaid(item.params || {});
        const host = $id('wtpViewReport');
        host.classList.remove('is-drawn');
        drawReport(host, item, mode, true);
        view.hidden = false;
        document.documentElement.classList.add('va-view-lock');
        view.scrollTop = 0;
        requestAnimationFrame(() => requestAnimationFrame(() => { view.classList.add('is-on'); host.classList.add('is-drawn'); }));
    }
    let VIEW_MODE = null;
    function closeView() {
        const view = $id('wtpView');
        if (view.hidden) return;
        view.classList.remove('is-on');
        document.documentElement.classList.remove('va-view-lock');
        const wasFresh = VIEW_MODE === 'fresh';
        VIEW_MODE = null;
        setTimeout(() => { view.hidden = true; $id('wtpViewReport').innerHTML = ''; if (wasFresh) wizardBack(); }, 300);
    }
    $id('wtpViewX').addEventListener('click', closeView);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeView(); });

    /* A finished run leaves a clean form: the place, the months, the crop,
       the variety and the field's troubles all cleared, back at the first
       step. The field's country stays -- a setting more than an answer. */
    function resetWizard() {
        Object.assign(state, { from: null, to: null, crop: '', variety: '', location: '', problems: [] });
        cropDropped = false;
        $id('wtpLocation').value = '';
        $id('wtpVariety').value = '';
        document.querySelectorAll('#wtpProbs input:checked').forEach((i) => { i.checked = false; i.closest('.wtp-prob')?.classList.remove('is-on'); });
        $id('wtpCropIcon').textContent = '🌱';
        const now = $id('wtpCropNow');
        now.textContent = 'Choose the crop';
        now.classList.add('is-none');
        if ($id('wtpCropSearch')) { $id('wtpCropSearch').value = ''; cropSift(); }
        paintRange();
        show(0);
    }

    function wizardBack() {
        $id('wtpWiz').hidden = false;
        if (OPT && OPT.canUse && OPT.quote) $id('wtpQuote').hidden = false;
        $id('wtpReport').hidden = true;
        $id('wtpReport').classList.remove('is-drawn');
        show(0);
        $id('wtpWiz').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // The host a source came from, or nothing for a search redirect — the
    // card names sources, it does not say how they were found.
    const hostOf = (u) => { try { const h = new URL(u).hostname.replace(/^www\./, ''); return /vertexaisearch\.cloud\.google\.com$/.test(h) ? '' : h; } catch (_) { return ''; } };

    /* ---------------- the report, drawn ---------------- */
    function drawReport(host, item, mode, quiet) {
        const r = item.report;
        const p = item.params;
        // Older saved reports carry the persona's :anee-…: shortcodes, which
        // have no renderer here — swept so prose reads as prose.
        // No emoji shortcodes, and no interjections -- a report saved before the
        // register was written may still open with "Aray," or "Naku!"; they go.
        const sweep = (t) => { const s = String(t || '').replace(/:[a-z0-9_-]+:/gi, '').replace(/(^|[.!?]\s+)(aray|naku|hala|grabe|whoa|wow|ooh|oh no|hay naku|sus|ay)\b[,!.]*\s*/gi, '$1').replace(/\s{2,}/g, ' ').trim(); return s.charAt(0).toUpperCase() + s.slice(1); };
        const crop = (OPT ? OPT.crops.find((c) => c.key === p.crop) : null) || {};
        const bw = r.bestWindow || {};
        const m1 = MONTHS[(bw.fromMonth || 1) - 1];
        const m2 = MONTHS[(bw.toMonth || 1) - 1];
        const bestMonths = new Set();
        for (let m = bw.fromMonth; m; m = (m === bw.toMonth ? 0 : (m % 12) + 1)) { bestMonths.add(m); if (bestMonths.size > 12) break; }

        /* The season's own run of months. A dry season's answer comes back
           December first and January–November of the next year after it;
           sorted by year then month it reads as the season runs, and the
           year sits under the month so January is plainly next year's. */
        const scores = (r.monthScores || []).slice(0, 12).sort((a, b) => ((a.year || 0) - (b.year || 0)) || ((a.month || 0) - (b.month || 0)));
        // The farmer's own months, marked under the bars; the rest are there
        // for comparison. The year shows when the run crosses into another.
        const mine = (s) => !!(p.fromMonth && s.year && monthIdx(s.year, s.month) >= monthIdx(p.fromYear, p.fromMonth)
            && monthIdx(s.year, s.month) <= monthIdx(p.toYear || p.fromYear, p.toMonth || p.fromMonth));
        const crossesYear = new Set(scores.map((s) => s.year).filter(Boolean)).size > 1 || p.season === 'dry' || p.season === 'winter';

        /* Twenty years of risk, month by month, in the season's own month
           order so it reads under the score chart. Each month is a stacked
           bar of the kinds that struck it; the worst years follow. */
        const RK = [['storm', p.country === 'PH' || !p.country ? 'Typhoons & storms' : 'Storms'], ['flood', 'Floods'], ['drought', 'Drought'], ['heat', 'Heat'], ['frost', 'Frost']];
        const rh = r.riskHistory || {};
        const rkMonths = Array.isArray(rh.months) ? rh.months : [];
        const rkOrder = scores.length ? scores.map((s) => s.month) : Array.from({ length: 12 }, (_, i) => i + 1);
        const rkOf = (m) => rkMonths.find((x) => Number(x.month) === Number(m)) || {};
        const rkSum = (x) => RK.reduce((t, [k]) => t + (Number(x[k]) || 0), 0);
        const rkMax = Math.max(1, ...rkOrder.map((m) => rkSum(rkOf(m))));
        const rkUsed = RK.filter(([k]) => rkMonths.some((x) => (Number(x[k]) || 0) > 0));
        const riskCard = rkMonths.length ? `
            <div class="wtp-card">
                <h3>Twenty years of risk, month by month <small style="display:block;font-size:.72rem;font-weight:500;color:var(--color-gray-500);margin-top:.1rem">${esc(rh.years || 'the past twenty years')} — how often each kind struck, and how hard</small></h3>
                <div class="wtp-rk">
                    ${rkOrder.map((m) => { const x = rkOf(m); const tot = rkSum(x); return `<div class="wtp-rk-col" title="${esc(x.note || '')}"><div class="wtp-rk-bar" style="height:${Math.max(3, Math.round(tot / rkMax * 100))}%">${RK.map(([k]) => (Number(x[k]) || 0) > 0 ? `<span class="wtp-rk-seg is-${k}" style="flex:${Number(x[k])}" title="${esc(k)}: ${Number(x[k])}"></span>` : '').join('')}</div></div>`; }).join('')}
                </div>
                <div class="wtp-rk-lbls">${rkOrder.map((m) => `<span class="wtp-rk-lbl">${MONTHS[(m || 1) - 1]}</span>`).join('')}</div>
                <div class="wtp-rk-legend">${(rkUsed.length ? rkUsed : RK.slice(0, 4)).map(([k, label]) => `<span><i class="wtp-rk-seg is-${k}"></i>${esc(label)}</span>`).join('')}</div>
                ${(rh.events || []).length ? `<div class="wtp-rk-ev">${(rh.events || []).slice(0, 8).map((e) => `<div class="${e.impact === 'high' ? 'is-high' : ''}"><b>${esc(e.year || '')}${e.month ? ' ' + MONTHS[(e.month || 1) - 1] : ''}</b><span>${esc(e.what || '')}<small>${esc(e.kind || '')}${e.impact ? ' · ' + esc(e.impact) + ' impact' : ''}</small></span></div>`).join('')}</div>` : ''}
                <p class="wtp-mnote">${esc(sweep(rh.note || 'Taller is worse. A month\'s bar stacks the kinds of trouble that struck it over the years read, each sized by how often and how badly.'))}</p>
            </div>` : '';

        /* The best weeks, ranked — the week-by-week answer (older analyses
           have none, and simply do not show the card). */
        const weeks = Array.isArray(r.weekRanks) ? r.weekRanks : [];
        const FACT = [['rain', '🌧️ Rain'], ['storms', '🌀 Storms'], ['enso', '🌡️ ENSO'], ['field', '🌾 Your field']];
        const weeksCard = weeks.length ? `
            <div class="wtp-card">
                <h3>The best weeks to plant, ranked <small style="display:block;font-size:.72rem;font-weight:500;color:var(--color-gray-500);margin-top:.1rem">Each week of your months weighed against the rain, the storm record, ENSO, your field and your crop's calendar</small></h3>
                <div class="wtp-wks">
                    ${weeks.map((w, i) => `
                        <div class="wtp-wk${i === 0 ? ' is-top' : ''}">
                            <span class="wtp-wk-n">${esc(w.rank || i + 1)}</span>
                            <div class="wtp-wk-b">
                                <div class="wtp-wk-h"><b>${esc(w.label || '')}</b><small>${i === 0 ? 'Best · ' : (i === 1 ? 'Second · ' : (i === 2 ? 'Third · ' : ''))}${esc(w.score ?? '')}/100</small></div>
                                <div class="wtp-wk-bar"><i style="--w:${Math.max(0, Math.min(100, Number(w.score) || 0)) / 100}"></i></div>
                                <p>${esc(sweep(w.why))}</p>
                                ${FACT.some(([k]) => w.factors && w.factors[k]) ? `<div class="wtp-wk-f">${FACT.filter(([k]) => w.factors && w.factors[k]).map(([k, lbl]) => `<span><b>${lbl}:</b> ${esc(sweep(w.factors[k]))}</span>`).join('')}</div>` : ''}
                                ${(w.flowering || w.harvest) ? `<div class="wtp-wk-c">${w.flowering ? `<span>🌼 Flowers ${esc(w.flowering)}</span>` : ''}${w.harvest ? `<span>🧺 Harvest ${esc(w.harvest)}</span>` : ''}</div>` : ''}
                                ${w.watch ? `<p class="wtp-wk-w">👁 ${esc(sweep(w.watch))}</p>` : ''}
                            </div>
                        </div>`).join('')}
                </div>
            </div>` : '';

        /* Typhoon chances by month: the share of the years read in which a
           cyclone affected the place that month, in the season's own month
           order so it reads under the charts above it. */
        const to = r.typhoonOdds && Array.isArray(r.typhoonOdds.months) && r.typhoonOdds.months.length === 12 ? r.typhoonOdds : null;
        const tyOf = (m) => (to ? to.months.find((x) => Number(x.month) === Number(m)) : null) || { chance: 0, storms: 0, note: '' };
        const tyMine = (m) => !!(p.fromMonth && scores.some((s) => Number(s.month) === Number(m) && mine(s)));
        const tyPeak = to ? Math.max(...to.months.map((x) => Number(x.chance) || 0)) : 0;
        const PH_FIELD = !p.country || p.country === 'PH';
        const mineList = to ? rkOrder.filter(tyMine).map((m) => `${MONTHS[m - 1]} <b>${tyOf(m).chance}%</b>`).join(' · ') : '';
        const typhoonCard = to ? `
            <div class="wtp-card">
                <h3>${PH_FIELD ? 'Chance of a typhoon, month by month' : 'Chance of a tropical storm, month by month'} <small style="display:block;font-size:.72rem;font-weight:500;color:var(--color-gray-500);margin-top:.1rem">${esc(to.years || 'the past twenty years')} — the share of years in which one affected ${esc(p.location || 'the place')} that month</small></h3>
                <div class="wtp-ty-axis"><span>100%</span><span>dashed line = 50%</span></div>
                <div class="wtp-ty" role="img" aria-label="${esc((PH_FIELD ? 'Typhoon' : 'Tropical storm') + ' chance by month: ' + rkOrder.map((m) => MONTHS[m - 1] + ' ' + tyOf(m).chance + '%').join(', '))}">
                    ${rkOrder.map((m) => { const x = tyOf(m); const c = Number(x.chance) || 0; return `<div class="wtp-ty-col" title="${esc(MONTHS[m - 1] + ': ' + c + '% of years · ' + (x.storms || 0) + ' ' + ((x.storms || 0) === 1 ? 'storm' : 'storms') + (x.note ? ' · ' + x.note : ''))}">
                        ${c > 0 && c === tyPeak ? `<span class="wtp-ty-v" style="bottom:calc(${c}% + 2px)">${c}%</span>` : ''}
                        <div class="wtp-ty-bar${c === 0 ? ' is-zero' : ''}" style="height:${Math.max(0.5, c)}%"></div>
                    </div>`; }).join('')}
                </div>
                <div class="wtp-ty-lbls">${rkOrder.map((m) => `<span class="wtp-ty-lbl${tyMine(m) ? ' is-mine' : ''}">${MONTHS[m - 1]}</span>`).join('')}</div>
                ${mineList ? `<p class="wtp-ty-mine">Your months: ${mineList}</p>` : ''}
                ${to.peak ? `<p class="wtp-mnote">⚠️ ${esc(sweep(to.peak))}</p>` : ''}
                <p class="wtp-mnote">${esc(sweep(to.note || 'Counted from the storm record for the place: how many of the years read had one that month.'))}</p>
                <details class="wtp-ty-t"><summary>See the numbers</summary>
                    <table><thead><tr><th>Month</th><th>Chance</th><th>Storms</th><th>Strongest</th></tr></thead><tbody>
                    ${to.months.map((x) => `<tr><td>${MONTHS[x.month - 1]}</td><td>${Number(x.chance) || 0}%</td><td>${Number(x.storms) || 0}</td><td>${esc(x.note || '')}</td></tr>`).join('')}
                    </tbody></table>
                </details>
            </div>` : '';

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
                <h2>${esc(crop.icon || '🌱')} ${esc(crop.label || 'Your crop')} — ${esc(whenSaid(p))}</h2>
                <p class="h-win">${esc(bw.label || (m1 + ' ' + (bw.fromDay || '') + (bw.fromYear ? ', ' + bw.fromYear : '') + ' – ' + m2 + ' ' + (bw.toDay || '') + (bw.toYear ? ', ' + bw.toYear : '')))}</p>
                <p class="h-why">${esc(sweep(bw.why))}</p>
                <div class="wtp-chips">
                    <span class="wtp-chip">📍 ${esc(p.location || '')}${p.country && p.country !== (OPT && OPT.country) ? ' · ' + esc(nameOf(p.country)) : ''}</span>
                    ${p.variety ? `<span class="wtp-chip">🧬 ${esc(p.variety)}</span>` : ''}
                    <span class="wtp-chip">Confidence: ${esc(r.confidence || 'moderate')}</span>
                    ${item.charged ? `<span class="wtp-chip">${item.charged} credits</span>` : ''}
                </div>
            </div>

            ${weeksCard}

            ${windowsCard}

            <div class="wtp-card">
                <h3>How each month scores for planting</h3>
                <div class="wtp-months">
                    ${scores.map((s) => {
                        // The window is deep green; outside it, 60 and up is still green, 25–59 amber, under 25 red.
                        const cls = bestMonths.has(s.month) ? 'is-best' : (s.score >= 60 ? 'is-good' : (s.score >= 25 ? 'is-poor' : 'is-bad'));
                        return `<div class="wtp-mcol">
                            <div class="wtp-mbar ${cls}" style="height:${Math.max(4, s.score)}%" title="${esc(s.note || '')}"></div>
                            <span class="wtp-mlbl${mine(s) ? ' is-mine' : ''}">${MONTHS[(s.month || 1) - 1]}${s.year && crossesYear ? `<i>${esc(String(s.year).slice(-2))}</i>` : ''}</span>
                        </div>`;
                    }).join('')}
                </div>
                <p class="wtp-mnote">Green is the recommended window; lighter green still works, amber is risky, red is asking for trouble.${p.fromMonth ? ' The months you picked carry a dot; the others are scored for comparison.' : ''} Hover a bar for its note.</p>
            </div>

            ${typhoonCard}

            ${riskCard}

            ${(r.threats || []).length ? `
            <div class="wtp-card">
                <h3>If you plant outside the window</h3>
                ${(r.threats || []).map((t, i) => `
                    <div class="wtp-threat sev-${esc(t.severity || 'moderate')}" style="transition-delay:${i * 80}ms">
                        <span>⚠️</span>
                        <span><b>${esc(t.whenNot || '')}</b>${esc(t.threat || '')}</span>
                    </div>`).join('')}
            </div>` : ''}

            ${r.variety && r.variety.found ? `
            <div class="wtp-card">
                <h3>🧬 About ${esc(r.variety.name || p.variety || 'the variety')}</h3>
                <div class="wtp-chips wtp-chips-dark">
                    ${Number(r.variety.maturityDays) > 0 ? `<span class="wtp-chip">⏱ ${esc(String(Math.round(Number(r.variety.maturityDays))))} days to maturity</span>` : ''}
                    ${r.variety.season ? `<span class="wtp-chip">🗓️ ${esc(r.variety.season)}</span>` : ''}
                    ${r.variety.source ? `<span class="wtp-chip">📚 ${esc(r.variety.source)}</span>` : ''}
                </div>
                <p class="wtp-plain mt-2">${esc(sweep(r.variety.traits))}</p>
                ${r.variety.caution ? `<p class="wp-watch mt-1">⚠️ ${esc(sweep(r.variety.caution))}</p>` : ''}
            </div>` : ''}

            <div class="wtp-card">
                <h3>In plain words</h3>
                <p class="wtp-plain">${esc(sweep(r.summary))}</p>
                ${(r.dataGaps || []).length ? `
                    <h3 class="mt-4">What this analysis could not know</h3>
                    <ul class="wtp-gap">${(r.dataGaps || []).map((g) => `<li>${esc(g)}</li>`).join('')}</ul>` : ''}
            </div>

            ${(r.webSources || []).length ? `
            <div class="wtp-card">
                <h3>📚 Additional Sources of Analysis</h3>
                <div class="va-links">${(() => { const seen = new Set(); return (r.webSources || []).map((s) => ({ name: s.title || hostOf(s.url) || 'A published source', host: hostOf(s.url) })).filter((x) => { const k = x.name.toLowerCase(); if (seen.has(k)) return false; seen.add(k); return true; }).map((x) => `<span class="va-link is-plain"><span class="l-t">${esc(x.name)}</span>${x.host && x.host !== x.name ? `<span class="l-h">${esc(x.host)}</span>` : ''}</span>`).join(''); })()}</div>
            </div>` : ''}

            <div class="wtp-card">
                <h3>🧭 A guide, not a promise</h3>
                <p class="wtp-fine">Weather and climate carry real uncertainty, and no analysis can see a particular storm. What this gives you is a data-grounded starting point — the patterns of past seasons weighed against your crop and your field — which beats deciding with nothing to compare against. Check PAGASA advisories as planting approaches.</p>
            </div>

            <div class="wtp-acts">
                <button type="button" class="btn btn-primary w-full" data-wtp-attach>
                    ${OPT && OPT.aneeFace ? `<img class="wtp-anee-face" src="${esc(OPT.aneeFace)}" alt="">` : '🤖'} Attach to Anee
                </button>
                ${mode === 'fresh' ? `<button type="button" class="btn btn-white w-full" data-wtp-again>⚡ Run another analysis</button>` : ''}
                <button type="button" class="btn btn-white w-full" data-wtp-delete>🗑 Delete</button>
            </div>`;

        host.hidden = false;
        if (!quiet) {
            requestAnimationFrame(() => requestAnimationFrame(() => host.classList.add('is-drawn')));
            host.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // A finished run is already on the shelf, so both views carry the
        // same three verbs: attach, run again (fresh only), delete.
        host.querySelector('[data-wtp-attach]').addEventListener('click', () => {
            if (item.savedId) window.location.href = U.anee + '?analysis=' + item.savedId;
        });
        host.querySelector('[data-wtp-again]')?.addEventListener('click', () => { VIEW_MODE = null; closeView(); wizardBack(); });
        host.querySelector('[data-wtp-delete]').addEventListener('click', async () => {
            const ok = window.confirmAction
                ? await confirmAction({ title: 'Delete this analysis?', message: 'The credits it cost are already spent; only the report goes.', confirmText: 'Delete', danger: true })
                : confirm('Delete this analysis?');
            if (!ok) return;
            try {
                const res = await api(U.del(item.savedId), { method: 'DELETE' });
                toast(res.message);
                host.hidden = true;
                VIEW_MODE = null;
                closeView();
                loadSaved();
                if (mode === 'fresh') wizardBack();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    /* The shelf list, refreshed without switching tabs — a finished run
       already lives there. */
    function loadSavedQuietly() { loadSaved().catch(() => {}); }

    /* ---------------- saved ----------------
       A page at a time (twenty), more as the farmer scrolls, and a search
       that asks the server -- a shelf of a hundred reports must not arrive
       whole, and a name must be findable. */
    const SHELF = { page: 1, hasMore: false, q: '', busy: false };
    const rowHtml = (r) => `
                <button type="button" class="wtp-saved" data-saved="${r.id}">
                    <span class="grow min-w-0"><b>${esc(r.title)}</b><small>${r.description ? esc(r.description) + ' · ' : ''}${esc(r.at)} · ${r.credits} credits</small>${window.userTags ? window.userTags.chips(r.tags) : ''}</span>
                    <span role="button" tabindex="0" class="wtp-pen" data-meta="${r.id}" title="Edit name and description" aria-label="Edit ${esc(r.title)}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:.85rem;height:.85rem"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </span>
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
            WTP_ROWS = more ? WTP_ROWS.concat(rows) : rows;
            const html = rows.map(rowHtml).join('');
            if (more) $id('wtpSavedList').insertAdjacentHTML('beforeend', html); else $id('wtpSavedList').innerHTML = html;
            $id('wtpSavedEmpty').classList.toggle('hidden', WTP_ROWS.length > 0);
            $id('wtpSavedEmpty').querySelector('p.font-bold').textContent = SHELF.q ? 'Nothing matches that' : 'Nothing saved yet';
            $id('wtpSavedMore').hidden = !SHELF.hasMore;
        } catch (err) { toast(err.message, 'error'); }
        finally { SHELF.busy = false; }
    }
    // Typing searches the shelf, a beat after the last key.
    let shelfTimer = null;
    $id('wtpSavedSearch').addEventListener('input', () => {
        clearTimeout(shelfTimer);
        shelfTimer = setTimeout(() => { SHELF.q = $id('wtpSavedSearch').value.trim(); loadSaved(false); }, 280);
    });
    // Scrolling to the foot of the list asks for the next page.
    if ('IntersectionObserver' in window) {
        new IntersectionObserver((entries) => { if (entries.some((e) => e.isIntersecting) && SHELF.hasMore && !SHELF.busy) loadSaved(true); }, { rootMargin: '200px' }).observe($id('wtpSavedMore'));
    }

    let WTP_ROWS = [];
    let WTP_META_ID = null;
    document.addEventListener('click', async (e) => {
        const saveBtn = e.target.closest('#wtpMetaSave');
        if (saveBtn && WTP_META_ID !== null) {
            saveBtn.disabled = true;
            try {
                const res = await api(WTP_META_URL, { method: 'POST', body: {
                    id: WTP_META_ID,
                    title: $id('wtpMetaTitle').value.trim(),
                    description: $id('wtpMetaDesc').value.trim(),
                    tags: window.userTags ? window.userTags.value($id('wtpMetaTags')) : [],
                } });
                toast(res.message);
                closeSheet('wtpMetaSheet');
                loadSaved();
            } catch (err) { toast(err.message, 'error'); }
            finally { saveBtn.disabled = false; }
            return;
        }
        const pen = e.target.closest('[data-meta]');
        if (pen && pen.closest('#wtpSavedList')) {
            e.stopPropagation();
            const r = WTP_ROWS.find((x) => String(x.id) === pen.getAttribute('data-meta'));
            if (!r) return;
            WTP_META_ID = r.id;
            $id('wtpMetaTitle').value = r.title || '';
            $id('wtpMetaDesc').value = r.description || '';
            if (window.userTags) window.userTags.set($id('wtpMetaTags'), r.tags || []);
            openSheet('wtpMetaSheet');
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

@push('sheets')
{{-- Rename a saved analysis and describe it in your own words. --}}
@include('partials.user-tags')
<div class="sheet hidden" id="wtpMetaSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Edit this analysis</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="wtpMetaTitle">Name</label>
            <input type="text" id="wtpMetaTitle" class="form-input" maxlength="191">
        </div>
        <div>
            <label class="form-label" for="wtpMetaDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="wtpMetaDesc" class="form-textarea" rows="3" maxlength="2000"></textarea>
        </div>
        <div>
            <span class="form-label">Tags <span class="text-gray-400 font-normal">(optional)</span></span>
            <div class="ut-mount" id="wtpMetaTags"></div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="wtpMetaSave">Save changes</button>
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
