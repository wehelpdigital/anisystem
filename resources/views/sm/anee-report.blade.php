@extends('layouts.app')

@php
    $isSofar = ($kind ?? 'season') === 'sofar';
    $price = \App\Support\AiPrices::of($isSofar ? 'sofar' : 'season');
    $aneeName = \App\Models\AiSetting::current()->assistantName;
    $pageName = $isSofar ? 'Analyze So Far' : 'Anee Season Report';
@endphp

@section('title', $pageName . ' — ' . $schedule->title)
@section('page-title', $pageName)
@section('page-subtitle', $schedule->title)
@section('back', \App\Support\BackTo::url(route('sm.reports', ['id' => $schedule->id]), $schedule->id))

@push('head')
@include('partials.tag-sheet-css')
<style>
    /* ===== Anee's own reports ========================================
       The when-to-plant idiom: two tabs, a folding price note, a full
       page veil while she works, then a report drawn in cards. */
    .ar-wrap { max-width: 44rem; margin: 0 auto; }
    .ar-tabs { display: flex; gap: .4rem; margin-bottom: 1rem; }
    .ar-tab { flex: 1 1 0; padding: .6rem; border-radius: .8rem; font-weight: 800; font-size: .9rem;
        text-align: center; color: var(--color-gray-500); background: var(--color-white);
        border: 1px solid var(--color-gray-200); cursor: pointer; }
    .ar-tab.is-on { background: var(--color-brand-600); border-color: var(--color-brand-600); color: #fff; }
    html.dark .ar-tab { background: #151b12; border-color: #2b3a1c; color: #93a684; }
    html.dark .ar-tab.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }

    /* The price note — the wtp fold, same clothes. */
    .ar-quote { border-radius: .9rem; margin-bottom: 1rem; overflow: hidden;
        background: linear-gradient(115deg, #f3f8ec, #e4efd4); border: 1px solid #cfe3b8; }
    .ar-quote b { color: #2d5016; }
    .arq-head { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left; padding: .7rem .9rem; cursor: pointer; }
    .arq-head .e { font-size: 1.15rem; flex: none; }
    .arq-title { flex: 1 1 auto; min-width: 0; font-size: .84rem; font-weight: 800; color: #2d5016; }
    .arq-body { display: grid; gap: .5rem; padding: 0 .9rem .6rem; }
    .arq-card { border-radius: .7rem; padding: .6rem .75rem; font-size: .82rem; color: #3d5226;
        line-height: 1.5; background: rgb(255 255 255 / .6); border: 1px solid rgb(207 227 184 / .8); }
    html.dark .ar-quote { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; }
    html.dark .ar-quote b { color: #cfe6b8; }
    html.dark .arq-title { color: #cfe6b8; }
    html.dark .arq-card { background: rgb(255 255 255 / .05); border-color: #2b3a1c; color: #a8bd93; }

    /* Readiness checklist */
    .ar-check { border-radius: .8rem; padding: .65rem .8rem; font-size: .8rem; line-height: 1.5; }
    .ar-check + .ar-check { margin-top: .45rem; }
    .ar-check.is-block { border: 1px solid #f0caca; background: #fdf1f1; color: #8a2626; }
    .ar-check.is-warn { border: 1px solid #f3e3b7; background: #fdf8ec; color: #92610e; }
    html.dark .ar-check.is-block { background: #271414; border-color: #4c2222; color: #e79c9c; }
    html.dark .ar-check.is-warn { background: #241f10; border-color: #43391b; color: #e0b95c; }

    .ar-run { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%;
        padding: .85rem 1rem; border-radius: 1rem; color: #fff; font-weight: 800; font-size: .95rem;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 30%, #3d6823 55%, #6b9f3d 80%, #8fc96a);
        background-size: 260% 100%; animation: arTide 5.5s ease-in-out infinite alternate;
        box-shadow: 0 10px 22px -12px rgb(61 104 35 / .65); }
    .ar-run:disabled { opacity: .55; animation: none; }
    @keyframes arTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }

    /* The wait while she works is the shared one -- sm/partials/anee-wait. */
    @media (prefers-reduced-motion: reduce) { .ar-run { transition: none; animation: none; } }

    /* The report, drawn */
    .ar-report { display: grid; gap: .9rem; }
    .ar-hero { border-radius: 1.1rem; padding: 1.1rem 1.2rem; color: #fff;
        background: linear-gradient(130deg, #4a7c2a, #2d5016 70%); }
    .ar-hero.is-watch { background: linear-gradient(130deg, #b45309, #92400e 70%); }
    .ar-hero.is-rescue { background: linear-gradient(130deg, #b91c1c, #7f1d1d 70%); }
    .ar-hero h2 { font-size: 1.15rem; font-weight: 800; }
    .ar-hero .why { font-size: .85rem; opacity: .93; line-height: 1.55; margin-top: .45rem; }
    .ar-hero .chip { display: inline-block; margin-top: .55rem; font-size: .68rem; font-weight: 800;
        letter-spacing: .05em; text-transform: uppercase; padding: .2rem .6rem; border-radius: 999px;
        background: rgb(255 255 255 / .18); }
    /* The so-far hero: a standing word with a drawn mark, a score ring, a short headline. */
    .ar-hero-row { display: flex; align-items: center; gap: .9rem; }
    .ar-hero-t { min-width: 0; flex: 1 1 auto; }
    .ar-stand { display: inline-flex; align-items: center; gap: .35rem; font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        padding: .22rem .65rem .22rem .5rem; border-radius: 999px; background: rgb(255 255 255 / .2); margin-bottom: .45rem; }
    .ar-stand svg { width: .95rem; height: .95rem; }
    .ar-ring { flex: none; width: 4.2rem; height: 4.2rem; border-radius: 999px; display: grid; place-items: center; position: relative;
        background: conic-gradient(rgb(255 255 255 / .95) calc(var(--p, 0) * 1%), rgb(255 255 255 / .22) 0); }
    .ar-ring::before { content: ''; position: absolute; inset: .38rem; border-radius: 999px; background: rgb(0 0 0 / .22); backdrop-filter: blur(2px); }
    .ar-ring b { position: relative; font-size: 1.2rem; font-weight: 900; font-variant-numeric: tabular-nums; }
    .ar-ring small { position: absolute; bottom: .5rem; font-size: .5rem; font-weight: 800; opacity: .8; letter-spacing: .04em; }
    /* The graphs: the crop on its clock, the plan to today, the money by category. */
    .ar-prog { display: grid; gap: .55rem; }
    .ar-prog-row { display: grid; gap: .25rem; }
    .ar-prog-h { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; font-size: .8rem; color: var(--color-gray-700); }
    .ar-prog-h b { color: var(--color-gray-900); }
    .ar-prog-h small { color: var(--color-gray-500); font-size: .72rem; white-space: nowrap; }
    .ar-prog .track { display: block; height: 10px; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .ar-prog .fill { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #6b9f3d, #3d6823); width: 0; transition: width .7s cubic-bezier(.22,1,.36,1); }
    .ar-prog .fill.is-plan { background: linear-gradient(90deg, #60a5fa, #2563eb); }
    .ar-kv { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
    .ar-kv span { display: inline-flex; align-items: baseline; gap: .3rem; padding: .22rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 700; border: 1px solid var(--color-gray-200); color: var(--color-gray-600); background: var(--color-white); }
    .ar-kv span b { font-size: .84rem; font-weight: 800; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .ar-kv span.is-bad b { color: #b91c1c; }
    .ar-stack { display: flex; height: 14px; border-radius: 999px; overflow: hidden; background: var(--color-gray-100); margin: .4rem 0 .5rem; }
    .ar-stack i { display: block; height: 100%; }
    .ar-legend { display: flex; flex-wrap: wrap; gap: .3rem .7rem; font-size: .72rem; color: var(--color-gray-600); }
    .ar-legend i { display: inline-block; width: .6rem; height: .6rem; border-radius: .2rem; margin-right: .3rem; vertical-align: -1px; }
    .ar-legend b { color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    /* The good and the bad, as rows. */
    .ar-gb { display: grid; gap: .4rem; }
    .ar-gb-row { padding: .55rem .7rem; border-radius: .75rem; font-size: .8rem; line-height: 1.5; border: 1px solid var(--color-gray-200); color: var(--color-gray-700); background: var(--color-white); }
    .ar-gb-row b { display: block; color: var(--color-gray-900); }
    .ar-gb-row.is-good { border-color: #cfe3bd; background: #f6fbf0; }
    .ar-gb-row.is-bad { border-color: #f3d9a4; background: #fffbf0; }
    .ar-gb-row.is-bad em { display: block; font-style: normal; color: #92400e; margin-top: .15rem; }
    .ar-two { display: grid; grid-template-columns: minmax(0, 1fr); gap: .5rem; margin-top: .5rem; }
    @media (min-width: 560px) { .ar-two { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .ar-two > div { border-radius: .75rem; padding: .55rem .7rem; font-size: .8rem; line-height: 1.5; border: 1px solid var(--color-gray-200); }
    .ar-two > div b { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; margin-bottom: .25rem; }
    .ar-two .is-ok { border-color: #cfe3bd; background: #f6fbf0; color: #2f5219; }
    .ar-two .is-miss { border-color: #f5c2c2; background: #fff7f7; color: #7f1d1d; }
    .ar-two ul { margin: 0; padding-left: 1.05rem; }
    .ar-drift { margin-top: .5rem; font-size: .8rem; color: var(--color-gray-700); line-height: 1.5; padding: .5rem .7rem; border-radius: .7rem; background: var(--color-gray-50); }
    html.dark .ar-prog-h, html.dark .ar-drift { color: #cbd5c0; }
    html.dark .ar-prog-h b, html.dark .ar-kv span b, html.dark .ar-legend b, html.dark .ar-gb-row b { color: #e8efe1; }
    html.dark .ar-prog .track, html.dark .ar-stack { background: #22301a; }
    html.dark .ar-kv span, html.dark .ar-gb-row { background: #151b12; border-color: #2b3a1c; color: #cbd5c0; }
    html.dark .ar-gb-row.is-good { background: #1a2513; border-color: #3f5a2a; }
    html.dark .ar-gb-row.is-bad { background: #262012; border-color: #6b4f16; }
    html.dark .ar-gb-row.is-bad em { color: #f0d9a8; }
    html.dark .ar-two .is-ok { background: #1a2513; border-color: #3f5a2a; color: #cfe6b8; }
    html.dark .ar-two .is-miss { background: #2a1717; border-color: #6b2b2b; color: #f0a3a3; }
    html.dark .ar-drift { background: #1c2416; }
    html.dark .ar-legend { color: #a5b89a; }
    .ar-card { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        padding: 1rem 1.1rem; }
    .ar-card h3 { font-weight: 800; font-size: .92rem; color: var(--color-gray-900); margin-bottom: .6rem; }
    .ar-li { display: flex; gap: .5rem; font-size: .84rem; color: var(--color-gray-700); line-height: 1.55; }
    .ar-li + .ar-li { margin-top: .45rem; }
    .ar-li .e { flex: none; }
    .ar-prose { font-size: .84rem; color: var(--color-gray-700); line-height: 1.6; }
    .ar-score { display: grid; grid-template-columns: 7.4rem 1fr auto; gap: .55rem; align-items: center; font-size: .78rem; color: var(--color-gray-600); }
    .ar-score + .ar-score { margin-top: .45rem; }
    .ar-score .track { display: block; height: 9px; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .ar-score .fill { display: block; height: 100%; border-radius: 999px; background: var(--color-brand-600); width: 0;
        transition: width .7s cubic-bezier(.22,1,.36,1); }
    .ar-score b { font-variant-numeric: tabular-nums; color: var(--color-gray-900); }
    .ar-proto { border: 1px solid var(--color-gray-100); border-radius: .8rem; padding: .65rem .8rem; }
    .ar-proto + .ar-proto { margin-top: .5rem; }
    .ar-proto b { display: block; font-size: .85rem; color: var(--color-gray-900); }
    .ar-proto .swap { font-size: .78rem; color: var(--color-gray-600); margin-top: .3rem; line-height: 1.5; }
    .ar-proto .swap s { color: #b91c1c; text-decoration-thickness: 2px; }
    .ar-proto .swap em { font-style: normal; color: #15803d; font-weight: 700; }
    .ar-next { border: 1px solid var(--color-gray-100); border-radius: .8rem; padding: .6rem .75rem;
        display: flex; gap: .6rem; align-items: flex-start; }
    .ar-next + .ar-next { margin-top: .5rem; }
    .ar-next .n { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; background: var(--color-brand-50);
        color: var(--color-brand-800); display: inline-flex; align-items: center; justify-content: center;
        font-size: .78rem; font-weight: 800; }
    .ar-next .t { min-width: 0; }
    .ar-next .t b { display: block; font-size: .85rem; color: var(--color-gray-900); }
    .ar-next .t small { display: block; font-size: .74rem; color: var(--color-gray-500); margin-top: .15rem; line-height: 1.5; }
    .ar-heart { border-radius: 1rem; border: 1px solid #cfe3b8; padding: 1rem 1.1rem; font-size: .86rem;
        color: #3d5226; line-height: 1.6; background: linear-gradient(115deg, #f3f8ec, #e4efd4);
        display: flex; gap: .7rem; align-items: flex-start; }
    .ar-heart img { width: 2.2rem; height: 2.2rem; border-radius: 999px; object-fit: cover; flex: none; }
    .ar-acts { display: grid; grid-template-columns: 1fr; gap: .5rem; }
    @media (min-width: 640px) { .ar-acts { grid-template-columns: 1fr 1fr 1fr; } }
    html.dark .ar-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .ar-card h3, html.dark .ar-li b, html.dark .ar-score b, html.dark .ar-proto b, html.dark .ar-next .t b { color: #e8efe1; }
    html.dark .ar-li, html.dark .ar-prose { color: #b7c2ad; }
    html.dark .ar-heart { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2b3a1c; color: #a8bd93; }
    html.dark .ar-score .track { background: #222b1a; }
    html.dark .ar-proto, html.dark .ar-next { border-color: #2b3a1c; }

    .ar-saved-row { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left;
        padding: .7rem .8rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer; }
    .ar-saved-row:hover { background: var(--color-brand-50); }
    .ar-saved-row b { display: block; font-size: .86rem; color: var(--color-gray-900); }
    .ar-saved-row small { color: var(--color-gray-400); font-size: .72rem; }
    html.dark .ar-saved-row { border-color: #222b1a; }
    html.dark .ar-saved-row:hover { background: #161e10; }
    html.dark .ar-saved-row b { color: #e8efe1; }

    /* ===== The season report's graphs (report.facts, v2) =============
       Every mark is the app's own arithmetic; the words beside them are
       Anee's. The house chart rules: thin marks with a 4px rounded end,
       a 2px surface gap between stacked parts, the value said at the tip,
       text in text colours (never the series colour), a legend wherever
       two or more colours meet, and a line under each graph saying how
       to read it. Tap or hover a column for its numbers. */
    .k-materials { background: #15803d; } .k-labor { background: #d97706; } .k-services { background: #2563eb; }
    .k-expense { background: #b91c1c; } .k-purchase { background: #7c3aed; }
    .k-in { background: #15803d; } .k-out { background: #64748b; } .k-rain { background: #0284c7; }
    .k-this { background: #4a7c2a; } .k-past { background: #a8a29e; } .k-loss { background: #b91c1c; }
    .k-late { background: #d97706; }
    .ar-prog .fill.k-in, .ar-prog .fill.k-out, .ar-prog .fill.k-labor, .ar-prog .fill.k-this, .ar-prog .fill.k-past,
    .ar-prog .fill.k-loss, .ar-prog .fill.k-late { background-image: none; }
    .ar-prog .fill.k-in { background-color: #15803d; } .ar-prog .fill.k-out { background-color: #64748b; }
    .ar-prog .fill.k-labor, .ar-prog .fill.k-late { background-color: #d97706; } .ar-prog .fill.k-this { background-color: #4a7c2a; }
    .ar-prog .fill.k-past { background-color: #a8a29e; } .ar-prog .fill.k-loss { background-color: #b91c1c; }
    .ar-stack { gap: 2px; }
    .ar-legend i[class^="k-"] { display: inline-block; width: .6rem; height: .6rem; border-radius: .2rem; margin-right: .3rem; vertical-align: -1px; }
    .ar-legend em { font-style: normal; color: var(--color-gray-500); }
    .ar-sub { font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); margin: .85rem 0 .4rem; }
    .ar-sub:first-child { margin-top: 0; }
    .ar-cap { font-size: .72rem; color: var(--color-gray-500); margin-top: .5rem; line-height: 1.45; }
    .ar-figs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    @media (min-width: 560px) { .ar-figs { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .ar-fig { border: 1px solid var(--color-gray-200); border-radius: .85rem; padding: .6rem .75rem; background: var(--color-white); min-width: 0; }
    .ar-fig small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); }
    .ar-fig b { display: block; font-size: 1.08rem; font-weight: 900; color: var(--color-gray-900); font-variant-numeric: tabular-nums; margin-top: .12rem; overflow-wrap: anywhere; line-height: 1.25; }
    .ar-fig span { display: block; font-size: .72rem; color: var(--color-gray-500); margin-top: .15rem; line-height: 1.35; }
    .ar-fig.is-good { border-color: #cfe3bd; background: #f6fbf0; } .ar-fig.is-good b { color: #166534; }
    .ar-fig.is-bad { border-color: #f5c2c2; background: #fff7f7; } .ar-fig.is-bad b { color: #b91c1c; }
    .ar-score-why { grid-column: 1 / -1; font-size: .74rem; color: var(--color-gray-500); line-height: 1.45; margin: -.2rem 0 .1rem; }
    /* Columns: the money by month, the rain by month. */
    .ar-cols { display: flex; align-items: flex-end; gap: .35rem; height: 10.5rem; border-bottom: 1px solid var(--color-gray-200); }
    .ar-col { flex: 1 1 0; min-width: 0; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; cursor: pointer; outline: none; border-radius: .45rem .45rem 0 0; }
    .ar-col:focus-visible { box-shadow: 0 0 0 2px var(--color-brand-500); }
    .ar-col-v { font-size: .66rem; font-weight: 800; color: var(--color-gray-700); font-variant-numeric: tabular-nums; margin-bottom: .2rem; white-space: nowrap; min-height: .9rem; }
    .ar-col-bar { width: 100%; max-width: 24px; height: 0; display: flex; flex-direction: column-reverse; gap: 2px; border-radius: 4px 4px 0 0; overflow: hidden;
        transition: height .7s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .is-grown .ar-col-bar { height: calc(9.1rem * var(--h, 0) / 100); }
    .ar-col-bar i { display: block; flex: 1 1 0; min-height: 0; }
    .ar-cols:hover .ar-col:not(:hover):not(.is-on) .ar-col-bar, .ar-cols.has-on .ar-col:not(.is-on) .ar-col-bar { opacity: .55; }
    .ar-col.is-on .ar-col-v { color: var(--color-gray-900); }
    .ar-col-ls { display: flex; gap: .35rem; margin-top: .3rem; }
    .ar-col-ls span { flex: 1 1 0; min-width: 0; text-align: center; font-size: .68rem; font-weight: 700; color: var(--color-gray-500); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ar-tipline { font-size: .76rem; color: var(--color-gray-700); margin-top: .45rem; min-height: 1.15rem; line-height: 1.45; }
    /* The crop's days: the bar, and a dark line where a typical crop stops. */
    .ar-cal .track { position: relative; overflow: visible; }
    .ar-cal .mark { position: absolute; top: -4px; bottom: -4px; width: 2px; margin-left: -1px; border-radius: 2px; background: var(--color-gray-900); }
    .ar-lot { border: 1px solid var(--color-gray-100); border-radius: .85rem; padding: .7rem .8rem; }
    .ar-lot + .ar-lot { margin-top: .55rem; }
    .ar-lot-h { display: flex; flex-wrap: wrap; align-items: baseline; gap: .1rem .45rem; font-size: .78rem; color: var(--color-gray-500); margin-bottom: .5rem; }
    .ar-lot-h b { font-size: .9rem; color: var(--color-gray-900); }
    .ar-verdict { display: inline-flex; align-items: center; gap: .35rem; margin-top: .6rem; padding: .25rem .7rem; border-radius: 999px; font-size: .76rem; font-weight: 800;
        background: var(--color-gray-100); color: var(--color-gray-700); }
    .ar-verdict.is-good { background: #dcfce7; color: #166534; } .ar-verdict.is-bad { background: #fee2e2; color: #991b1b; }
    /* The season in moments: a line down the left, a dot per moment. */
    .ar-tl { position: relative; display: grid; gap: .6rem; }
    .ar-tl::before { content: ''; position: absolute; left: .64rem; top: .5rem; bottom: .5rem; width: 2px; background: var(--color-gray-200); }
    .ar-tl-row { position: relative; display: flex; gap: .6rem; align-items: flex-start; }
    .ar-tl-dot { flex: none; width: 1.3rem; height: 1.3rem; border-radius: 999px; display: grid; place-items: center; font-size: .68rem; font-weight: 900; color: #fff;
        background: #a8a29e; box-shadow: 0 0 0 2px var(--color-white); position: relative; }
    .ar-tl-row.is-good .ar-tl-dot { background: #15803d; } .ar-tl-row.is-bad .ar-tl-dot { background: #b91c1c; }
    .ar-tl-t { min-width: 0; font-size: .82rem; line-height: 1.45; color: var(--color-gray-700); }
    .ar-tl-t small { display: block; font-size: .7rem; font-weight: 800; color: var(--color-gray-500); }
    .ar-save { display: flex; gap: .7rem; align-items: flex-start; justify-content: space-between; padding: .6rem .75rem; border-radius: .8rem; border: 1px solid #cfe3bd; background: #f6fbf0; }
    .ar-save + .ar-save { margin-top: .45rem; }
    .ar-save > div { min-width: 0; }
    .ar-save b { display: block; font-size: .84rem; color: var(--color-gray-900); }
    .ar-save span { display: block; font-size: .78rem; color: var(--color-gray-700); line-height: 1.5; margin-top: .1rem; }
    .ar-save em { flex: none; font-style: normal; font-weight: 900; font-size: .76rem; color: #166534; background: #dcfce7; border-radius: 999px; padding: .2rem .55rem; white-space: nowrap; }
    /* Planned but not done yet: a hatch, so it reads apart from spent without colour. */
    .k-planned { background: repeating-linear-gradient(135deg, #cbd5e1 0 3px, #e2e8f0 3px 6px); }
    .ar-fig.is-long b { font-size: .9rem; }
    /* The season's span, on the hero (its own name: the app has a global .chip). */
    .ar-span { display: inline-block; margin-top: .65rem; font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase;
        padding: .22rem .65rem; border-radius: 999px; background: rgb(255 255 255 / .2); color: #fff; }
    .ar-lot-next { font-size: .72rem; color: var(--color-gray-500); line-height: 1.4; }
    .ar-gb-row em.ar-watch { display: block; font-style: normal; color: #92400e; margin-top: .15rem; }
    .ar-dues { display: grid; gap: .35rem; }
    .ar-due { display: flex; align-items: center; gap: .6rem; padding: .45rem .65rem; border-radius: .7rem; border: 1px solid var(--color-gray-100); background: var(--color-white); }
    .ar-due-d { flex: none; min-width: 3.1rem; font-size: .72rem; font-weight: 800; color: var(--color-gray-700); font-variant-numeric: tabular-nums; }
    .ar-due-t { flex: 1 1 auto; min-width: 0; font-size: .8rem; line-height: 1.35; }
    .ar-due-t b { display: block; color: var(--color-gray-900); font-weight: 700; overflow-wrap: anywhere; }
    .ar-due-t small { display: block; font-size: .7rem; color: var(--color-gray-500); }
    .ar-due em { flex: none; font-style: normal; font-size: .7rem; font-weight: 800; color: var(--color-gray-600); white-space: nowrap; }
    .ar-dues.is-late .ar-due { border-color: #f5c2c2; background: #fff7f7; }
    .ar-dues.is-late .ar-due em { color: #b91c1c; }
    html.dark .k-planned { background: repeating-linear-gradient(135deg, #475569 0 3px, #334155 3px 6px); }
    html.dark .ar-due { background: #151b12; border-color: #2b3a1c; }
    html.dark .ar-due-d, html.dark .ar-due em { color: #cbd5c0; }
    html.dark .ar-due-t b { color: #e8efe1; }
    html.dark .ar-dues.is-late .ar-due { background: #2a1717; border-color: #6b2b2b; }
    html.dark .ar-dues.is-late .ar-due em { color: #f0a3a3; }
    html.dark .ar-gb-row em.ar-watch { color: #f0d9a8; }
    html.dark .k-expense, html.dark .ar-prog .fill.k-loss { background-color: #dc2626; }
    html.dark .k-out, html.dark .ar-prog .fill.k-out { background-color: #94a3b8; }
    html.dark .k-past, html.dark .ar-prog .fill.k-past { background-color: #78716c; }
    html.dark .k-this, html.dark .ar-prog .fill.k-this { background-color: #6b9f3d; }
    html.dark .ar-fig, html.dark .ar-lot { background: #151b12; border-color: #2b3a1c; }
    html.dark .ar-fig b, html.dark .ar-lot-h b, html.dark .ar-save b, html.dark .ar-col.is-on .ar-col-v { color: #e8efe1; }
    html.dark .ar-fig.is-good { background: #1a2513; border-color: #3f5a2a; } html.dark .ar-fig.is-good b { color: #a8cc7e; }
    html.dark .ar-fig.is-bad { background: #2a1717; border-color: #6b2b2b; } html.dark .ar-fig.is-bad b { color: #f0a3a3; }
    html.dark .ar-cols { border-bottom-color: #2b3a1c; }
    html.dark .ar-col-v, html.dark .ar-tipline, html.dark .ar-tl-t, html.dark .ar-save span { color: #cbd5c0; }
    html.dark .ar-cal .mark { background: #e8efe1; }
    html.dark .ar-tl::before { background: #2b3a1c; }
    html.dark .ar-tl-dot { box-shadow: 0 0 0 2px #151b12; }
    html.dark .ar-save { background: #1a2513; border-color: #3f5a2a; }
    html.dark .ar-save em, html.dark .ar-verdict.is-good { background: #1f3a1a; color: #a8cc7e; }
    html.dark .ar-verdict { background: #22301a; color: #cbd5c0; } html.dark .ar-verdict.is-bad { background: #3a1a1a; color: #f0a3a3; }
    @media (prefers-reduced-motion: reduce) { .ar-col-bar, .ar-prog .fill, .ar-score .fill { transition: none; } }

    .badge-sev-high { background: #fee2e2; color: #b91c1c; }
    .badge-sev-moderate { background: #fef3c7; color: #92400e; }
    .badge-sev-low { background: #ecfdf5; color: #047857; }
</style>
@endpush

@section('content')
@php
    // A view-level worker reads the shelf; running a new report is edit work.
    $arMayGen = \App\Support\WorkerContext::canWriteModule('reports');
@endphp
@include('sm.partials.tag-picker')
@include('sm.partials.report-view')
<div class="ar-wrap">
    <div class="ar-tabs" role="tablist">
        <button type="button" class="ar-tab is-on" id="arTabGen" @unless($arMayGen) hidden @endunless>Generate</button>
        <button type="button" class="ar-tab" id="arTabSaved">Saved</button>
    </div>

    <div id="arGen">
        {{-- What this report is, before the price and the checks. --}}
        <div class="rx-about">
            <span class="rx-about-e"><img src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt=""></span>
            <div class="rx-about-t">
                {{-- One short paragraph, no dashes or lists (the owner's ask,
                     2026-09-29), as the analyses introduce themselves. --}}
                @if ($isSofar)
                    <b>About So Far</b>
                    <p>{{ $aneeName }} reads your season as it is today: the work, the money and the recent weather. She tells you how each lot is doing, the risks ahead and what to do next. Every report is kept on the Saved tab.</p>
                @else
                    <b>About this report</b>
                    <p>{{ $aneeName }} reads your whole finished season: the work, the money, the harvest, your notes and the weather. She tells you what went right, what went wrong and what to change next time, with a score. Every report is kept on the Saved tab.</p>
                @endif
            </div>
        </div>

        {{-- The price, said before anything is spent — folding, like wtp. --}}
        <div class="ar-quote" id="arQuote">
            <button type="button" class="arq-head" id="arQuoteHead">
                <span class="e">🔎</span>
                <span class="arq-title">Before you run one</span>
                <svg style="width:1rem;height:1rem;flex:none;color:#3d5226;opacity:.6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="arq-body" id="arQuoteBody">
                <div class="arq-card">
                    One report costs <b>{{ $price }} credits</b>. You have @if (\App\Support\WorkerContext::inWorkerContext())<span class="credit-coin">@else<a class="credit-coin" href="{{ route('ai.credits') }}" title="My Credits: your log and credits to buy">@endif<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9.2" fill="#f0b429" stroke="#c98a12" stroke-width="1.6"/><circle cx="12" cy="12" r="5" fill="none" stroke="#c98a12" stroke-width="1.3" opacity=".75"/></svg><b id="arBalance">…</b>@if (\App\Support\WorkerContext::inWorkerContext())</span>@else</a>@endif. Nothing is charged until you press Run.
                </div>
                <div class="arq-card">
                    {{ $isSofar
                        ? 'Treat it as a guide. Anee is honest, so she will tell you when a lot needs rescue.'
                        : 'Treat it as a guide, not a final verdict. It is honest about what went wrong.' }}
                </div>
            </div>
        </div>

        {{-- Readiness --}}
        <div class="card p-4 mb-4" id="arReadyCard">
            <p class="text-sm font-bold text-gray-900 mb-2" id="arReadyTitle">Checking the season…</p>
            <div id="arChecks"></div>
            @if ($isSofar && $schedule->lots->count())
                <div class="mt-3">
                    <span class="form-label text-xs! mb-1!">Analyze which lot?</span>
                    <button type="button" class="crop-tag" id="arLotBtn">
                        <span class="crop-tag-e">🌾</span>
                        <span class="crop-tag-t is-none" id="arLotNow">The whole season</span>
                        <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                    </button>
                </div>
            @endif
            <button type="button" class="ar-run mt-4" id="arRunBtn" disabled>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 0a7 7 0 017 7v3a3 3 0 01-3 3H8a3 3 0 01-3-3v-3a7 7 0 017-7zM9 12h.01M15 12h.01M9.5 17h5"/></svg>
                <span id="arRunSays">Run the analysis ({{ $price }} credits)</span>
            </button>
        </div>

        <div class="ar-report" id="arReport" hidden></div>
    </div>

    <div id="arSavedPane" class="hidden">
        <div class="card !p-0 overflow-hidden">
            <div id="arSavedList"></div>
            <div id="arSavedEmpty" class="hidden rx-empty">
                <span class="rx-empty-e"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg></span>
                <p class="rx-empty-t">Nothing saved yet</p>
                <p class="rx-empty-p">Each report you run is saved here, newest first. You can rename it and add a note.</p>
            </div>
        </div>
        <div class="ar-report mt-4" id="arSavedReport" hidden></div>
    </div>

    {{-- The wait: Anee's face at work, shared by every AI run. --}}
    @include('sm.partials.anee-wait')
</div>
@endsection

@push('sheets')
@if ($isSofar && $schedule->lots->count())
<div class="sheet hidden" id="arLotSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Analyze which lot?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="arLotList">
        <button type="button" class="dt-row is-on" data-ar-lot="0">
            <span class="dt-row-e">🗺️</span>
            <span class="dt-row-body"><b>The whole season</b><i>All lots together</i></span>
            <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </button>
        @foreach ($schedule->lots as $lot)
            <button type="button" class="dt-row" data-ar-lot="{{ $lot->id }}">
                <span class="dt-row-e">🌾</span>
                <span class="dt-row-body"><b>{{ $lot->lotName }}</b><i>{{ \App\Support\CropStages::label($lot->crop) ?: 'No crop set' }}{{ $lot->variety ? ' · ' . $lot->variety : '' }}</i></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>
        @endforeach
    </div>
</div>
@endif
@endpush

@push('scripts')
<script>
(() => {
const __init = () => {
    const $id = (i) => document.getElementById(i);
    const esc = window.escapeHtml || ((s) => String(s ?? ''));
    const KIND = @json($isSofar ? 'sofar' : 'season');
    const PRICE = @json($price);
    const SCHEDULE_ID = @json($schedule->id);
    const ANEE = @json($aneeName);
    const FACE = @json(\App\Models\AiSetting::current()->faceUrl());
    const U = {
        status: @json(route('sm.anee.status') . '?id=' . $schedule->id . '&kind=' . ($isSofar ? 'sofar' : 'season')),
        generate: @json(route('sm.anee.generate')),
        job: (id) => @json(route('sm.anee.job', ['id' => '__ID__'])).replace('__ID__', id),
        list: @json(route('sm.anee.list') . '?id=' . $schedule->id . '&kind=' . ($isSofar ? 'sofar' : 'season')),
        one: (id) => @json(route('sm.anee.one', ['id' => '__ID__'])).replace('__ID__', id),
        del: (id) => @json(route('sm.anee.delete', ['id' => '__ID__'])).replace('__ID__', id),
        ai: @json(route('ai.index')),
    };
    let STATUS = null;
    let LOT_ID = 0;

    /* ---------------- fold ---------------- */
    $id('arQuoteHead').addEventListener('click', () => {
        const b = $id('arQuoteBody');
        b.hidden = !b.hidden;
    });

    /* ---------------- tabs ---------------- */
    const showTab = (gen) => {
        $id('arTabGen').classList.toggle('is-on', gen);
        $id('arTabSaved').classList.toggle('is-on', !gen);
        $id('arGen').classList.toggle('hidden', !gen);
        $id('arSavedPane').classList.toggle('hidden', gen);
        if (!gen) loadSaved();
    };
    $id('arTabGen').addEventListener('click', () => showTab(true));
    $id('arTabSaved').addEventListener('click', () => showTab(false));
    // A visitor who may only read lands on the shelf, not on a Run button
    // that could only ever answer no.
    if (@json(! $arMayGen)) showTab(false);

    /* ---------------- readiness ---------------- */
    async function loadStatus() {
        try {
            const res = await api(U.status);
            STATUS = res.data;
            $id('arBalance').textContent = STATUS.unlimited ? '∞' : Number(STATUS.balance).toLocaleString();
            const checks = [];
            (STATUS.blockers || []).forEach((t) => checks.push(`<div class="ar-check is-block">⛔ ${esc(t)}</div>`));
            (STATUS.warnings || []).forEach((t) => checks.push(`<div class="ar-check is-warn">⚠️ ${esc(t)}</div>`));
            $id('arChecks').innerHTML = checks.join('');
            $id('arReadyTitle').textContent = STATUS.ready
                ? (checks.length ? 'Ready, with a few notes' : 'The season is ready')
                : 'Not ready yet';
            $id('arRunBtn').disabled = !STATUS.ready;
        } catch (err) { toast(err.message, 'error'); }
    }

    /* ---------------- lot picker (sofar) ---------------- */
    $id('arLotBtn')?.addEventListener('click', () => openSheet('arLotSheet'));
    $id('arLotList')?.addEventListener('click', (e) => {
        const row = e.target.closest('[data-ar-lot]');
        if (!row) return;
        LOT_ID = Number(row.dataset.arLot);
        document.querySelectorAll('#arLotList [data-ar-lot]').forEach((r) => r.classList.toggle('is-on', r === row));
        const t = $id('arLotNow');
        t.textContent = LOT_ID ? row.querySelector('b').textContent : 'The whole season';
        t.classList.toggle('is-none', !LOT_ID);
        closeSheet('arLotSheet');
    });

    /* ---------------- the veil's rotating lines ---------------- */
    const LINES = KIND === 'sofar'
        ? ['Reading the season as it is now…', 'Checking the work against the crop\'s age…', 'Checking the recent weather…', 'Looking at the risks…', 'Writing what to do next…']
        : ['Reading the whole season…', 'Adding up the money…', 'Checking the weather and El Niño…', 'Reading your notes and photos…', 'Comparing with your past seasons…', 'Writing it up honestly…'];
    /* ---------------- generate + poll ---------------- */
    $id('arRunBtn').addEventListener('click', async () => {
        if (!STATUS || !STATUS.ready) return;
        window.aneeWait.show({
            title: KIND === 'sofar' ? 'Anee is reading the season so far…' : 'Anee is reading the whole season…',
            lines: LINES,
            sub: 'This takes a few minutes. That is normal.',
        });
        let landed = false;
        try {
            const res = await api(U.generate, { method: 'POST', body: { scheduleId: SCHEDULE_ID, kind: KIND, lotId: LOT_ID || null } });
            let data = res.data;
            if (data.pending) {
                for (let i = 0; i < 160 && (!data || data.status !== 'ready'); i++) {
                    await new Promise((r) => setTimeout(r, 3000));
                    const st = await api(U.job(data.id));
                    if (st.data && st.data.status === 'ready') { data = st.data; break; }
                }
                if (!data || data.status !== 'ready') {
                    throw new Error('Still working. Wait a minute, then check the Saved tab.');
                }
            }
            drawReport($id('arReport'), data.report, data, 'fresh');
            landed = true;
            // Her face lights up over the finished report; the veil lifts after.
            await window.aneeWait.done({ title: 'Done!', line: `${data.credits} credits used. Your report is saved.` });
            toast(`Done. ${data.credits} credits used. Your report is saved.`);
            showInView(data, $id('arReport'), 'fresh');
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            if (!landed) window.aneeWait.fail();
        }
    });

    /* ---------------- the season report's graphs ----------------
     * report.facts (v2) is the app's own arithmetic: the money and when it
     * went, lot by lot, the work, the crop's days, the sky, past seasons.
     * Anee's words sit under each graph. A report without facts (none
     * were kept before 2026-09-29, and the server fills them in the first
     * time an old one is opened) draws its words alone, as it always did. */
    const REG = window.ANEE_REGION || {};
    const peso = (n) => (REG.symbol || '₱') + Math.round(Number(n || 0)).toLocaleString(REG.locale || 'en-PH');
    const pesoK = (n) => {
        const v = Number(n || 0);
        const a = Math.abs(v);
        const s = REG.symbol || '₱';
        if (a >= 1e6) return s + (v / 1e6).toFixed(a >= 1e7 ? 0 : 1).replace(/\.0$/, '') + 'M';
        if (a >= 1e3) return s + (v / 1e3).toFixed(a >= 1e4 ? 0 : 1).replace(/\.0$/, '') + 'k';
        return s + Math.round(v);
    };
    const nf = (n, d = 1) => Number(n || 0).toLocaleString(REG.locale || 'en-PH', { maximumFractionDigits: d });
    const pct = (v, max) => (max > 0 ? Math.max(0, Math.min(100, Number(v || 0) / max * 100)) : 0);
    const CATS = [['materials', 'Materials'], ['labor', 'Labor'], ['services', 'Services'], ['expense', 'Extra expenses'], ['purchase', 'Stock buys']];
    const monthName = (ym) => { const d = new Date(String(ym) + '-01T00:00:00'); return isNaN(d) ? String(ym) : d.toLocaleString('en', { month: 'short' }); };
    const one = (unit) => { const u = String(unit || ''); return /^(kg|kgs|g|t|mt)$/i.test(u) ? u : u.replace(/s$/i, ''); };
    const cardOf = (title, body) => `<div class="ar-card"><h3>${title}</h3>${body}</div>`;
    const capOf = (t) => (t ? `<p class="ar-cap">${esc(t)}</p>` : '');
    const proseOf = (t, top = true) => (t ? `<p class="ar-prose${top ? ' mt-3' : ''}">${esc(t)}</p>` : '');
    const barRow = (label, sub, right, w, cls, extra = '') => `<div class="ar-prog-row"><div class="ar-prog-h"><span><b>${esc(label)}</b>${sub ? ' · ' + esc(sub) : ''}</span><small>${esc(right)}</small></div><span class="track"><span class="fill ${cls}" data-w="${Number(w).toFixed(1)}"></span>${extra}</span></div>`;
    // A column chart: each column's value on its cap (all of them while
    // they are few, only the tallest when many), its month underneath,
    // and the line below it says what a tapped column holds.
    const colChart = (cols, fmt, hint) => {
        const max = Math.max(0, ...cols.map((c) => Number(c.total) || 0)) || 1;
        const few = cols.length <= 8;
        const top = cols.reduce((b, c, i) => (Number(c.total) > Number(cols[b].total) ? i : b), 0);
        return `<div class="ar-chart" data-ar-chart>
            <div class="ar-cols">${cols.map((c, i) => `<div class="ar-col" tabindex="0" role="button" aria-label="${esc(c.tip)}" data-tip="${esc(c.tip)}">
                <span class="ar-col-v">${Number(c.total) > 0 && (few || i === top) ? esc(fmt(c.total)) : ''}</span>
                <span class="ar-col-bar" style="--h:${pct(c.total, max).toFixed(1)}">${c.parts.filter((p) => Number(p[1]) > 0).map((p) => `<i class="${p[0]}" style="flex-grow:${Number(p[1])}"></i>`).join('')}</span>
            </div>`).join('')}</div>
            <div class="ar-col-ls">${cols.map((c) => `<span>${esc(c.label)}</span>`).join('')}</div>
            <p class="ar-tipline" data-hint="${esc(hint)}">${esc(hint)}</p>
        </div>`;
    };
    function wireCharts(host) {
        host.querySelectorAll('[data-ar-chart]').forEach((ch) => {
            const line = ch.querySelector('.ar-tipline');
            const cols = ch.querySelector('.ar-cols');
            const show = (col) => {
                ch.querySelectorAll('.ar-col.is-on').forEach((x) => x.classList.remove('is-on'));
                cols.classList.toggle('has-on', !!col);
                if (!col) { line.textContent = line.dataset.hint; return; }
                col.classList.add('is-on');
                line.textContent = col.dataset.tip;
            };
            ch.addEventListener('pointerover', (e) => { const c = e.target.closest('.ar-col'); if (c && e.pointerType === 'mouse') show(c); });
            ch.addEventListener('pointerleave', (e) => { if (e.pointerType === 'mouse') show(null); });
            ch.addEventListener('click', (e) => { const c = e.target.closest('.ar-col'); if (c) show(c.classList.contains('is-on') && e.pointerType !== 'mouse' ? null : c); });
            ch.addEventListener('focusin', (e) => { const c = e.target.closest('.ar-col'); if (c) show(c); });
        });
    }

    function seasonCards(r, parts, listCard) {
        const F = r.facts && Number(r.facts.v) >= 2 ? r.facts : null;
        const M = F && F.money ? F.money : null;
        const lots = F ? (F.lots || []) : [];
        const loss = M ? Number(M.profit) < 0 : false;

        // At a glance: the six numbers a farmer asks first (not for a
        // season with no money and no work on it -- a row of zeros says nothing).
        if (M && (Number(M.cost) > 0 || Number(M.revenue) > 0 || Number((F.work || {}).total) > 0)) {
            const ran = lots.map((l) => Number(l.daysRan)).filter((n) => n > 0);
            const perHa = lots.filter((l) => l.perHa !== null && l.perHa !== undefined);
            const tiles = [
                [loss ? 'is-bad' : 'is-good', loss ? 'Loss' : 'Net profit', peso(Math.abs(Number(M.profit))),
                    M.margin !== null && M.margin !== undefined ? (loss ? 'more went out than came in' : `${nf(M.margin)}% of the money in was kept`) : ''],
                ['', 'Money in', peso(M.revenue), Number(M.dayIncome) > 0 ? 'the harvest and other income' : 'from the harvest'],
                ['', 'Money out', peso(M.cost), 'everything the season spent'],
            ];
            const oneUnit = perHa.length && perHa.every((l) => l.unit === perHa[0].unit);
            const lo = oneUnit ? Math.min(...perHa.map((l) => Number(l.perHa))) : 0;
            const hi = oneUnit ? Math.max(...perHa.map((l) => Number(l.perHa))) : 0;
            if ((F.harvest || []).length) tiles.push(['', 'Harvest', F.harvest.join(' + '), oneUnit ? `${lo === hi ? nf(lo) : nf(lo) + ' to ' + nf(hi)} ${perHa[0].unit} per hectare` : (perHa.length ? 'per hectare, lot by lot below' : '')]);
            if (ran.length) tiles.push(['', 'Days to harvest', Math.min(...ran) === Math.max(...ran) ? `${ran[0]} days` : `${Math.min(...ran)} to ${Math.max(...ran)} days`, 'from day zero to harvest']);
            if (F.work) tiles.push(['', 'Work done', `${F.work.done} ${Number(F.work.done) === 1 ? 'job' : 'jobs'}`, `${nf(F.work.workerDays)} worker days`]);
            parts.push(cardOf('✨ At a glance', `<div class="ar-figs">${tiles.map(([cls, k, v, sub]) => `<div class="ar-fig ${cls}"><small>${esc(k)}</small><b>${esc(v)}</b>${sub ? `<span>${esc(sub)}</span>` : ''}</div>`).join('')}</div>`));
        }

        // The scores, each with the reason for it.
        if (r.scores) {
            const S = r.scores;
            const W = r.scoreWhy || {};
            const rows = [['overall', 'Overall'], ['planning', 'Planning'], ['execution', 'Execution'], ['costControl', 'Cost control'], ['timing', 'Timing'], ['recordKeeping', 'Record keeping']]
                .filter(([k]) => S[k] !== undefined && S[k] !== null);
            const v = (k) => Math.max(0, Math.min(100, Number(S[k]) || 0));
            if (rows.length) parts.push(cardOf('📈 The season, scored', rows.map(([k, label]) => `
                <div class="ar-score"><span>${label}</span><span class="track"><span class="fill" data-w="${v(k)}"></span></span><b>${v(k)}</b>${W[k] ? `<p class="ar-score-why">${esc(W[k])}</p>` : ''}</div>`).join('')
                + capOf('Each score is out of 100. Higher is better.')));
        }

        // The money: in against out, then where it went.
        if (M && (Number(M.cost) > 0 || Number(M.revenue) > 0)) {
            const max = Math.max(Number(M.revenue) || 0, Number(M.cost) || 0) || 1;
            const C = M.cats || {};
            const cats = CATS.filter(([k]) => Number(C[k]) > 0);
            const total = cats.reduce((n, [k]) => n + Number(C[k]), 0) || 1;
            parts.push(cardOf('💰 The money', `
                <div class="ar-prog">
                    ${barRow('Money in', '', peso(M.revenue), pct(M.revenue, max), 'k-in')}
                    ${barRow('Money out', '', peso(M.cost), pct(M.cost, max), 'k-out')}
                </div>
                <div class="ar-kv"><span class="${loss ? 'is-bad' : ''}">${loss ? 'Loss' : 'Kept'} <b>${peso(Math.abs(Number(M.profit)))}</b></span>${M.margin !== null && M.margin !== undefined ? `<span>Margin <b>${nf(M.margin)}%</b></span>` : ''}</div>
                ${cats.length ? `<p class="ar-sub">Where the money went</p>
                    <div class="ar-stack">${cats.map(([k, label]) => `<i class="k-${k}" style="width:${(Number(C[k]) / total * 100).toFixed(1)}%" title="${esc(label)}: ${esc(peso(C[k]))}"></i>`).join('')}</div>
                    <div class="ar-legend">${cats.map(([k, label]) => `<span><i class="k-${k}"></i>${label} <b>${peso(C[k])}</b> <em>${Math.round(Number(C[k]) / total * 100)}%</em></span>`).join('')}</div>` : ''}
                ${proseOf(r.moneyStory)}`));
        }

        // When the money went, month by month.
        if (F && (F.months || []).length) {
            const cols = F.months.map((m) => {
                const bits = CATS.filter(([k]) => Number(m[k]) > 0).map(([k, label]) => `${label} ${peso(m[k])}`);
                return { label: m.label, total: Number(m.total), parts: CATS.map(([k]) => ['k-' + k, Number(m[k] || 0)]),
                    tip: `${m.label}: ${peso(m.total)} spent${bits.length > 1 ? ' (' + bits.join(', ') + ')' : ''}` };
            });
            const used = CATS.filter(([k]) => F.months.some((m) => Number(m[k]) > 0));
            const top = F.months.reduce((b, m) => (Number(m.total) > Number(b.total) ? m : b), F.months[0]);
            parts.push(cardOf('📅 When the money went', colChart(cols, pesoK, 'Tap a month to see what it paid for.')
                + (used.length > 1 ? `<div class="ar-legend mt-2">${used.map(([k, label]) => `<span><i class="k-${k}"></i>${label}</span>`).join('')}</div>` : '')
                + capOf(`The most went out in ${top.label}: ${peso(top.total)}.` + (Number(F.undated) > 0 ? ` Another ${peso(F.undated)} had no date and is not drawn.` : ''))));
        }

        // Lot by lot.
        const shown = lots.filter((l) => Number(l.revenue) > 0 || Number(l.cost) > 0 || (l.yield || []).length);
        if (shown.length) {
            const max = Math.max(0, ...shown.flatMap((l) => [Number(l.revenue) || 0, Number(l.cost) || 0])) || 1;
            parts.push(cardOf('🌾 Lot by lot', shown.map((l) => `<div class="ar-lot">
                <div class="ar-lot-h"><b>${esc(l.name)}</b><span>${esc(l.icon || '')} ${esc(l.crop || '')}${l.variety ? ' · ' + esc(l.variety) : ''}${l.size ? ' · ' + esc(l.size) : ''}</span></div>
                <div class="ar-prog">${barRow('Money in', '', peso(l.revenue), pct(l.revenue, max), 'k-in')}${barRow('Money out', '', peso(l.cost), pct(l.cost, max), 'k-out')}</div>
                <div class="ar-kv">
                    ${(l.yield || []).length ? `<span>Harvest <b>${esc(l.yield.join(' + '))}</b></span>` : ''}
                    ${l.perHa !== null && l.perHa !== undefined ? `<span><b>${nf(l.perHa)}</b> ${esc(l.unit)} per hectare</span>` : ''}
                    ${l.costPerUnit !== null && l.costPerUnit !== undefined ? `<span><b>${peso(l.costPerUnit)}</b> cost per ${esc(one(l.unit))}</span>` : ''}
                    <span class="${Number(l.profit) < 0 ? 'is-bad' : ''}">${Number(l.profit) < 0 ? 'Loss' : 'Kept'} <b>${peso(Math.abs(Number(l.profit)))}</b></span>
                </div>
            </div>`).join('')
                + capOf('The bars share one scale, so lots can be set side by side.'
                    + (M && Number(M.general) > 0 ? ` ${peso(M.general)} of costs belong to the whole farm rather than one lot, so they are not in any lot's bar.` : ''))));
        }

        // The harvest against a typical farm: the farm's own per hectare,
        // Anee's estimate of a typical one.
        const H = r.harvest && typeof r.harvest === 'object' ? r.harvest : null;
        if (H && (H.typical || H.note)) {
            const verdict = String(H.verdict || '').toLowerCase();
            const VERD = { above: ['is-good', '▲', 'Above a typical farm'], typical: ['', '●', 'About the same as a typical farm'], below: ['is-bad', '▼', 'Below a typical farm'] };
            const mine = lots.filter((l) => l.perHa !== null && l.perHa !== undefined).slice(0, 3);
            const typical = H.typical && String(H.typical).toLowerCase() !== 'unknown' ? H.typical : '';
            parts.push(cardOf('🌱 Your harvest against a typical farm', `
                ${mine.length || typical ? `<div class="ar-figs">
                    ${mine.map((l) => `<div class="ar-fig"><small>${esc(l.name)}</small><b>${nf(l.perHa)} ${esc(l.unit)}</b><span>per hectare, your farm</span></div>`).join('')}
                    ${typical ? `<div class="ar-fig${String(typical).length > 22 ? ' is-long' : ''}"><small>A typical farm</small><b>${esc(typical)}</b><span>${esc(ANEE)}'s estimate for this crop in your area</span></div>` : ''}
                </div>` : ''}
                ${VERD[verdict] ? `<span class="ar-verdict ${VERD[verdict][0]}">${VERD[verdict][1]} ${VERD[verdict][2]}</span>` : ''}
                ${proseOf(H.note)}`));
        }

        // The work: what it was, and who did it.
        if (F && F.work && (F.work.types || []).length) {
            const all = F.work.types;
            const T = all.slice(0, 6);
            if (all.length > 6) {
                const rest = all.slice(6);
                T.push({ label: 'Other kinds', count: rest.reduce((n, t) => n + Number(t.count), 0), cost: rest.reduce((n, t) => n + Number(t.cost), 0) });
            }
            const maxC = Math.max(0, ...T.map((t) => Number(t.count))) || 1;
            const W = F.work.workers || [];
            const maxD = Math.max(0, ...W.map((w) => Number(w.days))) || 1;
            parts.push(cardOf('🧑‍🌾 The work', `
                <p class="ar-sub">What the work was</p>
                <div class="ar-prog">${T.map((t) => barRow(t.label, '', `${t.count} ${Number(t.count) === 1 ? 'job' : 'jobs'}${Number(t.cost) > 0 ? ' · ' + peso(t.cost) : ''}`, pct(t.count, maxC), '')).join('')}</div>
                ${W.length ? `<p class="ar-sub">Who did it</p>
                    <div class="ar-prog">${W.map((w) => barRow(w.name, '', `${nf(w.days)} ${Number(w.days) === 1 ? 'day' : 'days'} · ${peso(w.pay)}`, pct(w.days, maxD), 'k-labor')).join('')}</div>` : ''}
                ${capOf(`${F.work.done} of ${F.work.total} jobs were marked done. ${nf(F.work.workerDays)} worker days in all, with a half day counted as half.`
                    + (Number(F.work.workerCount) > W.length ? ` The ${W.length} busiest workers are shown.` : ''))}
                ${proseOf(r.workStory)}`));
        }

        // How long the crop took, against a typical crop.
        const cal = lots.filter((l) => Number(l.daysRan) > 0);
        if (cal.length || r.delays) {
            let body = '';
            if (cal.length) {
                const maxD = Math.max(...cal.map((l) => Math.max(Number(l.daysRan), Number(l.maturity) || 0))) * 1.08 || 1;
                body = `<div class="ar-prog ar-cal">${cal.map((l) => barRow(l.name, l.crop || '',
                    `${l.daysRan} days${l.maturity ? ` · typical ${l.maturity}` : ''}`,
                    pct(l.daysRan, maxD),
                    l.maturity && Number(l.daysRan) > Number(l.maturity) + 10 ? 'k-late' : '',
                    l.maturity ? `<span class="mark" style="left:${pct(l.maturity, maxD).toFixed(1)}%" title="A typical crop: ${esc(l.maturity)} days"></span>` : '')).join('')}</div>`
                    + capOf('Each bar runs from day zero to harvest. The dark line marks where a typical crop is ready. Orange means it ran well past it.');
            }
            parts.push(cardOf('⏱️ How long the crop took', body + proseOf(r.delays, !!body)));
        }

        // The weather, month by month.
        const WX = F && F.weather && (F.weather.months || []).length ? F.weather : null;
        if (WX || r.weatherStory) {
            let body = '';
            if (WX) {
                const cols = WX.months.map((m) => ({
                    label: monthName(m.ym), total: Number(m.rain), parts: [['k-rain', Number(m.rain)]],
                    tip: `${monthName(m.ym)}: ${nf(m.rain, 0)} mm of rain on ${m.wet} wet ${Number(m.wet) === 1 ? 'day' : 'days'}`
                        + (m.hot ? `, ${m.hot} ${Number(m.hot) === 1 ? 'day' : 'days'} at 35°C or hotter` : '')
                        + (m.windy ? `, ${m.windy} windy ${Number(m.windy) === 1 ? 'day' : 'days'}` : '')
                        + (m.tmax ? `. Days averaged ${nf(m.tmax)}°C at their hottest.` : '.'),
                }));
                const sum = (k) => WX.months.reduce((n, m) => n + Number(m[k] || 0), 0);
                body = colChart(cols, (v) => nf(v, 0) + ' mm', 'Tap a month to see its rain, heat and wind.')
                    + `<div class="ar-kv"><span><b>${nf(sum('rain'), 0)} mm</b> of rain in all</span><span><b>${sum('wet')}</b> wet days</span><span><b>${sum('dry')}</b> dry days</span>`
                    + (sum('hot') ? `<span class="is-bad"><b>${sum('hot')}</b> days at 35°C or hotter</span>` : '')
                    + (sum('windy') ? `<span class="is-bad"><b>${sum('windy')}</b> windy days</span>` : '') + `</div>`
                    + capOf('Taller bars mean more rain that month. From the weather archive for your field.');
            }
            parts.push(cardOf('🌦️ The weather', body + proseOf(r.weatherStory, !!body)));
        }

        // The season in moments.
        if ((r.moments || []).length) {
            const MOOD = { good: ['✓', 'Went well'], bad: ['!', 'A setback'], neutral: ['•', 'A moment'] };
            parts.push(cardOf('🗓️ The season in moments', `<div class="ar-tl">${r.moments.map((m) => {
                const mood = MOOD[m.mood] ? m.mood : 'neutral';
                return `<div class="ar-tl-row is-${mood}"><span class="ar-tl-dot" role="img" aria-label="${MOOD[mood][1]}" title="${MOOD[mood][1]}">${MOOD[mood][0]}</span><span class="ar-tl-t"><small>${esc(m.when || '')}</small>${esc(m.what || '')}</span></div>`;
            }).join('')}</div>` + capOf('A green tick went well, a red mark was a setback.')));
        }

        parts.push(listCard('💪 What went well', r.strengths, '✅'));
        parts.push(listCard('🥀 What went wrong', r.wentWrong, '⚠️'));
        parts.push(listCard('💡 What to improve', r.improvements, '👉'));

        if ((r.savings || []).length) {
            parts.push(cardOf('💸 Where you can save', r.savings.map((x) => `<div class="ar-save"><div><b>${esc(x.what || '')}</b><span>${esc(x.idea || '')}</span></div>${x.save ? `<em>${esc(x.save)}</em>` : ''}</div>`).join('')
                + capOf(`Rough amounts for one season, ${ANEE}'s estimate.`)));
        }

        if ((r.protocolChanges || []).length) {
            parts.push(`<div class="ar-card"><h3>🔁 Protocol changes</h3>${r.protocolChanges.map((p) => `
                <div class="ar-proto"><b>${esc(p.change || '')}</b>
                    <div class="swap"><s>${esc(p.current || '')}</s><br><em>${esc(p.suggested || '')}</em>
                    ${p.timing ? ` <span class="badge badge-gray">${esc(p.timing)}</span>` : ''}</div>
                    ${p.why ? `<div class="swap">${esc(p.why)}</div>` : ''}</div>`).join('')}</div>`);
        }

        // Against the farmer's past seasons of the same crop.
        const P = F ? (F.past || []).filter((p) => p.this || Number(p.revenue) > 0 || Number(p.cost) > 0) : [];
        if (P.length > 1 || r.comparison) {
            let body = '';
            if (P.length > 1) {
                const max = Math.max(0, ...P.map((p) => Math.abs(Number(p.profit)))) || 1;
                body = `<div class="ar-prog">${P.map((p) => barRow(p.this ? 'This season' : p.title, p.this ? p.title : '',
                    `${Number(p.profit) < 0 ? 'Loss ' : ''}${peso(Math.abs(Number(p.profit)))}`, pct(Math.abs(Number(p.profit)), max),
                    Number(p.profit) < 0 ? 'k-loss' : (p.this ? 'k-this' : 'k-past'))).join('')}</div>`
                    + capOf('Net profit of each of your seasons of the same crop. Longer is better. Red is a loss.');
            }
            parts.push(cardOf('📊 Against your past seasons', body + proseOf(r.comparison, !!body)));
        }

        parts.push(listCard('🧾 What was lacking', r.lacking, '▫️'));
        parts.push(listCard('📋 Next season checklist', r.nextSeason, '☑️'));
    }

    /* ---------------- the so-far report's cards ----------------
     * facts v2 (2026-09-29) adds each lot's stage, next stage and harvest
     * date, what is overdue and coming, the money SPENT against the whole
     * plan and month by month, the work by kind, and the rain so far. An
     * older report (facts v1, or none) draws what it kept, as before. */
    function sofarCards(r, parts, listCard) {
        const F = r.facts || null;
        const V2 = !!(F && Number(F.v) >= 2);
        const P = F && F.plan ? F.plan : null;
        const M = F && F.money ? F.money : null;
        const lots = F ? (F.lots || []) : [];
        const WX = V2 && F.weather && (F.weather.months || []).length ? F.weather : null;
        const wxSum = (k) => (WX ? WX.months.reduce((n, m) => n + Number(m[k] || 0), 0) : 0);

        // At a glance.
        if (V2) {
            const due = lots.filter((l) => l.daysLeft !== null && l.daysLeft !== undefined).sort((a, b) => a.daysLeft - b.daysLeft);
            const soon = due.find((l) => l.daysLeft >= 0) || due[due.length - 1] || null;
            const tiles = [];
            if (M) tiles.push(['', 'Spent so far', peso(M.cost), Number(M.plan) > 0 ? `of about ${peso(M.plan)} for the whole plan` : 'work marked done and costs up to today']);
            if (P && P.planned) tiles.push([P.overdue ? 'is-bad' : 'is-good', 'Work due by today', `${P.done} of ${P.planned} done`, P.overdue ? `${P.overdue} overdue` : 'nothing overdue']);
            if (soon) tiles.push(['', 'Next harvest', soon.daysLeft > 0 ? `in ${soon.daysLeft} ${soon.daysLeft === 1 ? 'day' : 'days'}` : 'due now', `${soon.name} · about ${soon.harvestOn}`]);
            if (P) tiles.push(['', 'Coming up', `${P.coming} ${Number(P.coming) === 1 ? 'job' : 'jobs'}`, 'in the next 14 days']);
            if (WX) tiles.push(['', 'Rain so far', `${nf(wxSum('rain'), 0)} mm`, `${wxSum('wet')} wet days, ${wxSum('dry')} dry`]);
            if (F.work && Number(F.work.workerDays) > 0) tiles.push(['', 'Worker days', nf(F.work.workerDays), `by ${F.work.workerCount} ${Number(F.work.workerCount) === 1 ? 'worker' : 'workers'} so far`]);
            if (tiles.length) parts.push(cardOf('✨ At a glance', `<div class="ar-figs">${tiles.map(([cls, k, v, sub]) => `<div class="ar-fig ${cls}"><small>${esc(k)}</small><b>${esc(v)}</b>${sub ? `<span>${esc(sub)}</span>` : ''}</div>`).join('')}</div>`));
        }

        // The scores, five ways, each with its reason.
        if (r.scores && typeof r.scores === 'object') {
            const S = r.scores;
            const W = r.scoreWhy || {};
            const v = (k) => Math.max(0, Math.min(100, Number(S[k]) || 0));
            const rows = [['protocol', 'Protocol'], ['timing', 'Timing'], ['weather', 'Weather'], ['money', 'Money'], ['records', 'Records']].filter(([k]) => S[k] !== undefined && S[k] !== null);
            if (rows.length) parts.push(cardOf('📈 The season so far, scored', rows.map(([k, label]) => `
                <div class="ar-score"><span>${label}</span><span class="track"><span class="fill" data-w="${v(k)}"></span></span><b>${v(k)}</b>${W[k] ? `<p class="ar-score-why">${esc(W[k])}</p>` : ''}</div>`).join('')
                + capOf('Each score is out of 100. Higher is better.')));
        }

        // The good and the bad.
        if ((r.good || []).length) parts.push(`<div class="ar-card"><h3>💪 What's good</h3><div class="ar-gb">${r.good.map((g) => `<div class="ar-gb-row is-good"><b>${esc(g.point || '')}</b>${esc(g.why || '')}</div>`).join('')}</div></div>`);
        if ((r.bad || []).length) parts.push(`<div class="ar-card"><h3>🩹 What needs work</h3><div class="ar-gb">${r.bad.map((b) => `<div class="ar-gb-row is-bad"><b>${esc(b.point || '')}</b>${esc(b.why || '')}${b.fix ? `<em>Fix: ${esc(b.fix)}</em>` : ''}</div>`).join('')}</div></div>`);

        // Where the crop stands, and what it needs now.
        const needs = (r.cropNow || []).filter((c) => c && (c.needs || c.watch));
        if (lots.length || needs.length) {
            const bars = lots.length ? `<div class="ar-prog">${lots.map((l) => {
                // v2 says the stage on its own line, with what comes next;
                // the header keeps only the count, so a phone reads it clean.
                const bits = [];
                if (V2 && l.stage) bits.push(l.stageNo ? `${l.stage} (stage ${l.stageNo} of ${l.stages})` : l.stage);
                if (V2 && l.next) bits.push(`${l.next.label} in ${l.next.inDays} ${Number(l.next.inDays) === 1 ? 'day' : 'days'}`);
                if (V2 && l.harvestOn) bits.push(l.daysLeft > 0 ? `harvest about ${l.harvestOn}` : `harvest due (${l.harvestOn})`);
                return `<div class="ar-prog-row">
                    <div class="ar-prog-h"><span><b>${esc(l.name)}</b> · ${esc(l.icon || '')} ${esc(l.crop || '')}</span><small>${l.day !== null && l.day !== undefined ? `${esc(l.counter)} ${l.day}` : 'no day zero'}${l.maturity ? ` of ~${l.maturity}` : ''}${!V2 && l.stage ? ` · ${esc(l.stage)}` : ''}</small></div>
                    <span class="track"><span class="fill" data-w="${l.pct === null || l.pct === undefined ? 0 : l.pct}"></span></span>
                    ${bits.length ? `<p class="ar-lot-next">${esc(bits.join(' · '))}</p>` : ''}
                </div>`;
            }).join('')}</div>` + capOf(`As of ${F.asOf || ''}. Each bar is the crop's calendar, from day zero to a typical harvest.`) : '';
            const now = needs.length ? `<p class="ar-sub">What the crop needs now</p><div class="ar-gb">${needs.map((c) => `
                <div class="ar-gb-row"><b>${esc(c.lot || '')}</b>${esc(c.needs || '')}${c.watch ? `<em class="ar-watch">Watch for: ${esc(c.watch)}</em>` : ''}</div>`).join('')}</div>` : '';
            parts.push(cardOf('🌱 Where the crop stands', bars + now));
        }

        // What to do next: Anee's list, then the plan's own calendar.
        if ((r.whatsNext || []).length) {
            parts.push(`<div class="ar-card"><h3>🧭 What's next</h3>${r.whatsNext.map((x, i) => `
                <div class="ar-next"><span class="n">${i + 1}</span><span class="t"><b>${esc(x.action || '')}
                    ${x.urgency === 'now' ? '<span class="badge badge-sev-high">now</span>' : (x.urgency === 'soon' ? '<span class="badge badge-sev-moderate">soon</span>' : '')}</b>
                    <small>${esc(x.when || '')}${x.why ? ' · ' + esc(x.why) : ''}</small></span></div>`).join('')}</div>`);
        }
        if (P && P.total) {
            const pc = P.planned ? Math.round(P.done / P.planned * 100) : 0;
            const listOf = (rows, say) => rows.map((o) => `<div class="ar-due"><span class="ar-due-d">${esc(o.date)}</span><span class="ar-due-t"><b>${esc(o.title || 'Untitled')}</b>${o.lots ? `<small>${esc(o.lots)}</small>` : ''}</span><em>${esc(say(o))}</em></div>`).join('');
            const late = V2 ? (F.overdue || []) : [];
            const next = V2 ? (F.coming || []) : [];
            parts.push(`<div class="ar-card"><h3>📋 The plan to today</h3>
                <div class="ar-prog"><div class="ar-prog-row"><div class="ar-prog-h"><span><b>${P.done}</b> of ${P.planned} due by today are done</span><small>${pc}%</small></div><span class="track"><span class="fill is-plan" data-w="${pc}"></span></span></div></div>
                <div class="ar-kv"><span class="${P.overdue ? 'is-bad' : ''}"><b>${P.overdue}</b> overdue</span><span><b>${P.coming}</b> in the next 14 days</span><span><b>${P.doneAll}</b> of ${P.total} done overall</span></div>
                ${late.length ? `<p class="ar-sub">Overdue</p><div class="ar-dues is-late">${listOf(late, (o) => `${o.late} ${o.late === 1 ? 'day' : 'days'} late`)}</div>` : ''}
                ${next.length ? `<p class="ar-sub">Coming up</p><div class="ar-dues">${listOf(next, (o) => (o.in === 0 ? 'today' : (o.in === 1 ? 'tomorrow' : `in ${o.in} days`)))}</div>` : ''}
                ${Number(P.coming) > next.length && next.length ? capOf(`The next ${next.length} of ${P.coming} are shown.`) : ''}</div>`);
        }

        // The money so far.
        if (M && (Number(M.cost) > 0 || Number(M.revenue) > 0 || Number(M.planned) > 0)) {
            const C = M.cats || {};
            const cats = CATS.filter(([k]) => Number(C[k]) > 0);
            const total = cats.reduce((n, [k]) => n + Number(C[k]), 0) || 1;
            let body = '';
            if (V2 && Number(M.plan) > 0) {
                body += `<div class="ar-prog">${barRow('Spent so far', '', peso(M.cost), pct(M.cost, M.plan), 'k-in')}${barRow('The whole plan', '', peso(M.plan), 100, 'k-out')}</div>`;
            }
            body += `<div class="ar-kv"><span>Spent <b>${peso(M.cost)}</b></span>${V2 ? `<span>Still to spend <b>${peso(M.planned)}</b></span>` : ''}<span>Earned <b>${peso(M.revenue)}</b></span>${r.money && r.money.verdict ? `<span>Spend is <b>${esc(r.money.verdict)}</b></span>` : ''}</div>`;
            if (cats.length) {
                body += `<p class="ar-sub">Where it went</p><div class="ar-stack">${cats.map(([k, label]) => `<i class="k-${k}" style="width:${(Number(C[k]) / total * 100).toFixed(1)}%" title="${esc(label)}: ${esc(peso(C[k]))}"></i>`).join('')}</div>
                    <div class="ar-legend">${cats.map(([k, label]) => `<span><i class="k-${k}"></i>${label} <b>${peso(C[k])}</b> <em>${Math.round(Number(C[k]) / total * 100)}%</em></span>`).join('')}</div>`;
            }
            if (V2 && (F.months || []).length) {
                const cols = F.months.map((m) => ({
                    label: m.label, total: Number(m.total), parts: [['k-in', Number(m.spent)], ['k-planned', Number(m.planned)]],
                    tip: `${m.label}: ${peso(m.spent)} spent` + (Number(m.planned) > 0 ? `, ${peso(m.planned)} still planned` : ''),
                }));
                body += `<p class="ar-sub">Month by month</p>` + colChart(cols, pesoK, 'Tap a month to see what it cost.')
                    + `<div class="ar-legend mt-2"><span><i class="k-in"></i>Spent</span>${F.months.some((m) => Number(m.planned) > 0) ? '<span><i class="k-planned"></i>Planned, not done yet</span>' : ''}</div>`;
            }
            body += capOf(V2 ? 'Spent means work marked done, plus extra costs and stock bought, up to today.' + (Number(M.general) > 0 ? ` ${peso(M.general)} of whole farm costs are not counted in this lot.` : '') : '');
            if (r.money && r.money.summary) body += proseOf(r.money.summary);
            parts.push(cardOf('💸 The money so far', body));
        } else if (r.money && r.money.summary) {
            parts.push(cardOf('💸 The money so far', proseOf(r.money.summary, false)));
        }

        // The work, by kind: how much of each is done.
        if (V2 && F.work && (F.work.types || []).length) {
            const all = F.work.types;
            const T = all.slice(0, 6);
            if (all.length > 6) {
                const rest = all.slice(6);
                T.push({ label: 'Other kinds', done: rest.reduce((n, t) => n + Number(t.done), 0), total: rest.reduce((n, t) => n + Number(t.total), 0) });
            }
            const W = F.work.workers || [];
            const maxD = Math.max(0, ...W.map((w) => Number(w.days))) || 1;
            parts.push(cardOf('🧑‍🌾 The work', `
                <p class="ar-sub">Done, by kind of work</p>
                <div class="ar-prog">${T.map((t) => barRow(t.label, '', `${t.done} of ${t.total} done`, pct(t.done, t.total), '')).join('')}</div>
                ${W.length ? `<p class="ar-sub">Who has done it</p><div class="ar-prog">${W.map((w) => barRow(w.name, '', `${nf(w.days)} ${Number(w.days) === 1 ? 'day' : 'days'}${Number(w.pay) > 0 ? ' · ' + peso(w.pay) : ''}`, pct(w.days, maxD), 'k-labor')).join('')}</div>` : ''}
                ${capOf('A full bar means every job of that kind is done. Worker days count a half day as half.')}`));
        }

        // The weather so far, and ahead.
        const Wx = r.weather && typeof r.weather === 'object' ? r.weather : null;
        if (WX || (Wx && (Wx.summary || Wx.outlook)) || r.weatherStory) {
            let body = '';
            if (WX) {
                const cols = WX.months.map((m) => ({
                    label: monthName(m.ym), total: Number(m.rain), parts: [['k-rain', Number(m.rain)]],
                    tip: `${monthName(m.ym)}: ${nf(m.rain, 0)} mm of rain on ${m.wet} wet ${Number(m.wet) === 1 ? 'day' : 'days'}`
                        + (m.hot ? `, ${m.hot} ${Number(m.hot) === 1 ? 'day' : 'days'} at 35°C or hotter` : '')
                        + (m.windy ? `, ${m.windy} windy ${Number(m.windy) === 1 ? 'day' : 'days'}` : '') + '.',
                }));
                body = colChart(cols, (v) => nf(v, 0) + ' mm', 'Tap a month to see its rain, heat and wind.')
                    + `<div class="ar-kv"><span><b>${nf(wxSum('rain'), 0)} mm</b> of rain so far</span><span><b>${wxSum('wet')}</b> wet days</span><span><b>${wxSum('dry')}</b> dry days</span>`
                    + (wxSum('hot') ? `<span class="is-bad"><b>${wxSum('hot')}</b> days at 35°C or hotter</span>` : '')
                    + (wxSum('windy') ? `<span class="is-bad"><b>${wxSum('windy')}</b> windy days</span>` : '') + '</div>'
                    + capOf('Taller bars mean more rain that month. From the weather archive for your field, up to a few days ago.');
            }
            if (Wx) {
                body += proseOf(Wx.summary, !!body);
                if (Wx.outlook) body += `<p class="ar-prose mt-2"><b>Ahead:</b> ${esc(Wx.outlook)}</p>`;
                if ((Wx.risks || []).length) body += `<div class="ar-kv">${Wx.risks.map((x) => `<span>${esc(x)}</span>`).join('')}</div>`;
            } else {
                body += proseOf(r.weatherStory, !!body);
            }
            parts.push(cardOf('🌦️ The weather', body));
        }

        if ((r.risks || []).length) {
            parts.push(`<div class="ar-card"><h3>⚠️ The risks</h3>${r.risks.map((x) => `
                <div class="ar-li"><span class="e">•</span><span><b>${esc(x.risk || '')}</b>
                    <span class="badge badge-sev-${esc((x.severity || 'low').toLowerCase())}">${esc(x.severity || '')}</span><br>${esc(x.why || '')}</span></div>`).join('')}</div>`);
        }

        // The harvest ahead: the calendar's dates, and Anee's outlook.
        const HO = r.harvestOutlook && typeof r.harvestOutlook === 'object' ? r.harvestOutlook : null;
        const dated = lots.filter((l) => l.harvestOn);
        if (HO || (V2 && dated.length)) {
            const expect = HO && HO.expect && String(HO.expect).toLowerCase() !== 'unknown' ? HO.expect : '';
            parts.push(cardOf('🌾 The harvest ahead', `
                ${dated.length ? `<div class="ar-dues">${dated.map((l) => `<div class="ar-due"><span class="ar-due-d">${esc(String(l.harvestOn).replace(/, \d{4}$/, ''))}</span><span class="ar-due-t"><b>${esc(l.name)}</b><small>${esc(l.crop || '')}</small></span><em>${l.daysLeft > 0 ? `in ${l.daysLeft} ${l.daysLeft === 1 ? 'day' : 'days'}` : 'due now'}</em></div>`).join('')}</div>`
                    + capOf('Day zero plus the typical days to maturity. Weather and the crop itself move it.') : ''}
                ${HO && (HO.when || expect) ? `<div class="ar-figs mt-3">${HO.when ? `<div class="ar-fig${String(HO.when).length > 22 ? ' is-long' : ''}"><small>Likely harvest</small><b>${esc(HO.when)}</b></div>` : ''}${expect ? `<div class="ar-fig${String(expect).length > 22 ? ' is-long' : ''}"><small>A fair harvest to expect</small><b>${esc(expect)}</b><span>${esc(ANEE)}'s estimate, if things go on like this</span></div>` : ''}</div>` : ''}
                ${HO ? proseOf(HO.note) : ''}`));
        }

        // The protocol so far, and the timing.
        if (r.protocol && typeof r.protocol === 'object') {
            const Pp = r.protocol;
            parts.push(`<div class="ar-card"><h3>📋 The protocol so far</h3>
                ${Pp.summary ? `<p class="ar-prose">${esc(Pp.summary)}</p>` : ''}
                ${((Pp.followed || []).length || (Pp.missed || []).length) ? `<div class="ar-two">
                    ${(Pp.followed || []).length ? `<div class="is-ok"><b>Done as planned</b><ul>${Pp.followed.map((x) => `<li>${esc(x)}</li>`).join('')}</ul></div>` : ''}
                    ${(Pp.missed || []).length ? `<div class="is-miss"><b>Missed, late or never planned</b><ul>${Pp.missed.map((x) => `<li>${esc(x)}</li>`).join('')}</ul></div>` : ''}
                </div>` : ''}
                ${Pp.drift ? `<p class="ar-drift">${esc(Pp.drift)}</p>` : ''}</div>`);
        }
        if (r.timing && typeof r.timing === 'object' && (r.timing.summary || r.timing.stage)) {
            const T = r.timing;
            const behind = Number.isFinite(Number(T.daysBehind)) && T.daysBehind !== null ? Number(T.daysBehind) : null;
            parts.push(`<div class="ar-card"><h3>⏱️ Timing</h3>
                <div class="ar-kv" style="margin:0 0 .5rem">${T.stage ? `<span>Stage <b>${esc(T.stage)}</b></span>` : ''}${behind !== null ? `<span class="${behind > 0 ? 'is-bad' : ''}"><b>${behind > 0 ? behind + ' days behind' : (behind < 0 ? (-behind) + ' days ahead' : 'On time')}</b></span>` : ''}</div>
                ${T.summary ? `<p class="ar-prose">${esc(T.summary)}</p>` : ''}</div>`);
        }
        parts.push(listCard('🧾 What the records lack', r.lacking, '▫️'));
    }

    /* ---------------- the report, drawn ---------------- */
    const li = (e, t) => `<div class="ar-li"><span class="e">${e}</span><span>${esc(t)}</span></div>`;
    function drawReport(host, r, meta, mode) {
        r = r || {};
        const parts = [];
        const standing = (r.standing || '').toLowerCase();
        const heroCls = KIND === 'sofar' ? (standing === 'rescue' ? ' is-rescue' : (standing === 'watch' ? ' is-watch' : '')) : '';
        // The standing, said with a drawn mark and plain words.
        const STAND = {
            'on-track': { word: 'On track', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>' },
            watch: { word: 'Needs attention', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2.5 20h19L12 3z"/><path d="M12 9v5m0 3h.01"/></svg>' },
            rescue: { word: 'Needs rescue', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="m5.6 5.6 3.6 3.6m5.6 5.6 3.6 3.6m0-12.8-3.6 3.6m-5.6 5.6-3.6 3.6"/></svg>' },
        };
        const st = KIND === 'sofar' ? (STAND[standing] || null) : null;
        // The ring: the so-far report's own score, the season report's overall.
        const rawScore = KIND === 'sofar' ? r.score : (r.scores || {}).overall;
        const score = rawScore !== undefined && rawScore !== null && Number.isFinite(Number(rawScore)) ? Math.max(0, Math.min(100, Math.round(Number(rawScore)))) : null;
        const SPAN = KIND === 'season' && r.facts && r.facts.span ? r.facts.span : null;
        parts.push(`<div class="ar-hero${heroCls}">
            <div class="ar-hero-row">
                ${score !== null ? `<div class="ar-ring" style="--p:${score}"><b>${score}</b><small>/100</small></div>` : ''}
                <div class="ar-hero-t">
                    ${st ? `<span class="ar-stand">${st.icon}${st.word}</span>` : ''}
                    <h2>${esc(r.headline || meta.title || '')}</h2>
                </div>
            </div>
            <p class="why">${esc(r.verdict || '')}</p>
            ${SPAN ? `<span class="ar-span">${esc(SPAN.from)} to ${esc(SPAN.to)} · ${SPAN.days} days</span>` : ''}
        </div>`);

        const listCard = (title, arr, e) => (arr || []).length
            ? `<div class="ar-card"><h3>${title}</h3>${arr.map((t) => li(e, t)).join('')}</div>` : '';
        if (KIND === 'season') {
            seasonCards(r, parts, listCard);
        } else {
            sofarCards(r, parts, listCard);
        }

        if (r.encouragement) {
            parts.push(`<div class="ar-heart"><img src="${esc(FACE)}" alt="">
                <span><b>A word from ${esc(ANEE)}</b><br>${esc(r.encouragement)}</span></div>`);
        }

        host.innerHTML = parts.join('');
        requestAnimationFrame(() => {
            host.querySelectorAll('.ar-score .fill, .ar-prog .fill').forEach((f) => { f.style.width = f.dataset.w + '%'; });
            host.querySelectorAll('[data-ar-chart]').forEach((ch) => ch.classList.add('is-grown'));
        });
        wireCharts(host);
        host.querySelector('[data-ar-again]')?.addEventListener('click', () => {
            host.hidden = true;
            $id('arReadyCard').hidden = false;
            $id('arQuote').hidden = false;
            loadStatus();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        host.querySelector('[data-ar-del]')?.addEventListener('click', async (e) => {
            const id = e.currentTarget.getAttribute('data-ar-del');
            const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this report?', message: 'The credits it used are not returned.', confirmText: 'Delete' }) : true;
            if (!ok) return;
            try {
                await api(U.del(id), { method: 'DELETE' });
                toast('Report removed.');
                host.hidden = true;
                $id('arReadyCard').hidden = false;
                $id('arQuote').hidden = false;
                loadStatus();
            } catch (err) { toast(err.message, 'error'); }
        });
    }

    /* ---------------- the full-screen view ----------------
     * A fresh report and a shelf row land in the same screen, its actions
     * as icons in the top bar (the X is the close). Closed, the farmer is
     * on the Saved shelf. */
    let VIEWING = null;
    function showInView(meta, host, mode) {
        VIEWING = { id: meta.id, title: meta.title || '', description: meta.description || '', mine: meta.mine !== false };
        const actions = [
            { label: 'Ask ' + ANEE + ' about it', face: FACE, kind: 'primary', href: U.ai + '?freport=' + meta.id },
        ];
        if (@json($arMayGen) && VIEWING.mine) {
            actions.push({ label: 'Name & description', icon: 'pen', onClick: () => openReportMeta(meta.id) });
            actions.push({ label: 'Delete', icon: 'trash', kind: 'danger', onClick: async () => {
                const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this report?', message: 'The credits it used are not returned.', confirmText: 'Delete' }) : confirm('Delete this report?');
                if (!ok) return;
                try { await api(U.del(meta.id), { method: 'DELETE' }); toast('Report removed.'); window.reportView.close(); }
                catch (err) { toast(err.message, 'error'); }
            } });
        }
        window.reportView.open({
            title: VIEWING.title || (KIND === 'sofar' ? 'Analyze So Far' : ANEE + ' Season Report'),
            node: host,
            actions,
            onClose: () => { VIEWING = null; $id('arTabSaved')?.click(); },
        });
    }

    /* ---------------- saved shelf ---------------- */
    async function loadSaved() {
        try {
            const res = await api(U.list + '&_=' + Date.now());
            const rows = res.data.rows || [];
            $id('arSavedEmpty').classList.toggle('hidden', rows.length > 0);
            SAVED_ROWS = rows;
            $id('arSavedList').innerHTML = rows.map((r) => `
                <button type="button" class="ar-saved-row" data-ar-open="${r.id}">
                    <img src="${esc(FACE)}" alt="" style="width:1.6rem;height:1.6rem;border-radius:999px;object-fit:cover;flex:none;">
                    <span class="min-w-0 grow"><b>${esc(r.title)}</b><small>${r.description ? esc(r.description) + ' · ' : ''}${esc(r.when || '')} · ${r.credits} credits</small></span>
                    ${@json($arMayGen) ? `<span role="button" tabindex="0" class="ar-pen" data-ar-meta="${r.id}" title="Edit name, description and tags" aria-label="Edit ${esc(r.title)}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:.85rem;height:.85rem"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </span>` : ''}
                    <svg style="width:1rem;height:1rem;flex:none;color:var(--color-gray-300)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>`).join('');
        } catch (err) { toast(err.message, 'error'); }
    }
    let SAVED_ROWS = [];
    let META_ID = null;
    async function openReportMeta(id) {
        let r = SAVED_ROWS.find((x) => String(x.id) === String(id));
        if (!r) {
            try { const res = await api(U.one(id)); r = { id: res.data.id, title: res.data.title || '', description: res.data.description || '' }; }
            catch (err) { toast(err.message, 'error'); return; }
        }
        META_ID = r.id;
        document.getElementById('arMetaTitle').value = r.title || '';
        document.getElementById('arMetaDesc').value = r.description || '';
        const mount = document.getElementById('arMetaTags');
        if (window.smTags && mount) {
            window.smTags.mount(mount);
            window.smTags.load(mount, 'report', r.id);
        }
        openSheet('arMetaSheet');
    }
    document.addEventListener('click', async (e) => {
        const saveBtn = e.target.closest('#arMetaSave');
        if (!saveBtn || META_ID === null) return;
        saveBtn.disabled = true;
        try {
            const res = await api(@json(route('sm.anee.meta')), {
                method: 'POST',
                body: {
                    id: META_ID,
                    title: document.getElementById('arMetaTitle').value.trim(),
                    description: document.getElementById('arMetaDesc').value.trim(),
                    tags: window.smTags ? window.smTags.value(document.getElementById('arMetaTags')) : [],
                },
            });
            toast(res.message);
            closeSheet('arMetaSheet');
            if (VIEWING && VIEWING.id === META_ID) {
                VIEWING.title = document.getElementById('arMetaTitle').value.trim();
                window.reportView?.setTitle(VIEWING.title);
            }
            loadSaved();
        } catch (err) { toast(err.message, 'error'); }
        finally { saveBtn.disabled = false; }
    });
    $id('arSavedList').addEventListener('click', async (e) => {
        const pen = e.target.closest('[data-ar-meta]');
        if (pen) { e.stopPropagation(); openReportMeta(pen.getAttribute('data-ar-meta')); return; }
        const row = e.target.closest('[data-ar-open]');
        if (!row) return;
        try {
            const res = await api(U.one(row.getAttribute('data-ar-open')));
            drawReport($id('arSavedReport'), res.data.report, res.data, 'saved');
            showInView(res.data, $id('arSavedReport'), 'saved');
        } catch (err) { toast(err.message, 'error'); }
    });

    loadStatus();

    // A tag shelf names one saved report (?open=<id>): draw it straight
    // away, exactly as tapping its row on the shelf below would.
    {
        const want = new URLSearchParams(location.search).get('open');
        if (want) (async () => {
            try {
                const res = await api(U.one(String(want).replace(/[^\d]/g, '')));
                drawReport($id('arSavedReport'), res.data.report, res.data, 'saved');
                showInView(res.data, $id('arSavedReport'), 'saved');
            } catch (err) { toast(err.message || 'That saved report could not be opened.', 'error'); }
        })();
    }
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush

@push('head')
<style>
    .ar-pen { flex: none; width: 1.6rem; height: 1.6rem; border-radius: .45rem; display: inline-flex;
        align-items: center; justify-content: center; color: var(--color-gray-400); }
    .ar-pen:hover { color: var(--color-brand-700); background: var(--color-brand-50); }
    html.dark .ar-pen:hover { background: rgb(107 159 61 / .18); color: #a5c97e; }
</style>
@endpush

@push('sheets')
{{-- Rename a saved report, describe it, retie its tags. --}}
<div class="sheet hidden" id="arMetaSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Edit this report</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="arMetaTitle">Name</label>
            <input type="text" id="arMetaTitle" class="form-input" maxlength="191">
        </div>
        <div>
            <label class="form-label" for="arMetaDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="arMetaDesc" class="form-textarea" rows="3" maxlength="2000"></textarea>
        </div>
        <div>
            <span class="form-label">Tags</span>
            <div class="tp-mount" data-tags data-tags-kind="report" id="arMetaTags"></div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="arMetaSave">Save changes</button>
    </div>
</div>
@endpush
