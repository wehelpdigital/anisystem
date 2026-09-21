@extends('layouts.app')

@section('title', $protocol['title'])
@section('page-title', $protocol['title'])
@section('page-subtitle', ($protocol['cropLabel'] ?: 'No crop yet') . ' · ' . ($options['dayTypes'][$protocol['dayType']]['label'] ?? $protocol['dayType']))
@section('back', route('pb.page'))

@push('head')
<style>
    .pb-page { padding-bottom: 4.5rem; }
    .pb-head { padding: 1rem 1.05rem; margin-bottom: .85rem; }
    .pb-head-row { display: flex; align-items: flex-start; gap: .75rem; }
    .pb-head-e { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .85rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1.45rem; background: #eef6e6; }
    .pb-head-t { flex: 1 1 auto; min-width: 0; }
    .pb-head-t b { display: block; font-family: var(--font-heading); font-size: 1.1rem; line-height: 1.25; color: var(--color-gray-900); }
    .pb-head-t small { display: block; font-size: .76rem; color: var(--color-gray-500); margin-top: .15rem; }
    .pb-head-p { font-size: .84rem; line-height: 1.55; color: var(--color-gray-700); margin-top: .65rem; white-space: pre-wrap; }
    .pb-head-tags { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .7rem; }
    .pb-tag { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 700; border: 1px solid var(--color-gray-200); color: var(--color-gray-600); background: var(--color-white); text-decoration: none; }
    .pb-tag b { color: var(--color-gray-900); font-weight: 800; }
    .pb-tag.is-score { border-color: #cfe3bd; color: #2f5219; background: #f1f8ea; }
    .pb-tag.is-ported { border-color: #c7d2f5; color: #3546a8; background: #eef2fd; }
    .pb-pen { flex: none; width: 2.1rem; height: 2.1rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; color: var(--color-gray-500); border: 1px solid var(--color-gray-200); background: var(--color-white); }
    .pb-pen:hover { background: var(--color-brand-50); color: var(--color-brand-700); border-color: var(--color-brand-300); }
    .pb-pen svg { width: 1rem; height: 1rem; }
    html.dark .pb-head-e { background: #22301a; }
    html.dark .pb-head-t b { color: #e8efe1; }
    html.dark .pb-head-p { color: #cbd5c0; }
    html.dark .pb-tag { background: #151b12; border-color: #2b3a1c; color: #a5b89a; }
    html.dark .pb-tag b { color: #e8efe1; }
    html.dark .pb-tag.is-score { background: #22301a; border-color: #3f5a2a; color: #cfe6b8; }
    html.dark .pb-tag.is-ported { background: #1d2440; border-color: #33417a; color: #b9c6f5; }
    html.dark .pb-pen { background: #151b12; border-color: #2b3a1c; color: #a5b89a; }

    /* The editing tools: undo, redo, the saved word, add. */
    .pb-tools { display: none; align-items: center; gap: .4rem; margin-bottom: .75rem; position: sticky; top: calc(3.6rem + env(safe-area-inset-top, 0px)); z-index: 5;
        padding: .45rem .5rem; border-radius: .9rem; background: rgba(255,255,255,.92); border: 1px solid var(--color-gray-200); backdrop-filter: blur(6px); }
    .pb-page[data-mode="edit"] .pb-tools { display: flex; }
    .pb-tool { display: inline-flex; align-items: center; gap: .3rem; padding: .42rem .65rem; border-radius: .7rem; font-size: .78rem; font-weight: 800; color: var(--color-gray-700); border: 1px solid var(--color-gray-200); background: var(--color-white);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .pb-tool svg { width: 1rem; height: 1rem; }
    .pb-tool:disabled { opacity: .38; cursor: default; }
    .pb-tool:not(:disabled):hover { background: var(--color-brand-50); border-color: var(--color-brand-300); }
    .pb-tool.is-add { margin-left: auto; background: #3d6823; color: #fff; border-color: #3d6823; }
    .pb-tool.is-add:not(:disabled):hover { background: #2f5219; border-color: #2f5219; }
    .pb-save { font-size: .7rem; font-weight: 700; color: var(--color-gray-400); padding: 0 .3rem; min-width: 3.6rem; transition: color .28s cubic-bezier(.22,1,.36,1); white-space: nowrap; }
    .pb-save.is-saved { color: #4a7c2a; }
    .pb-save.is-failed { color: #b91c1c; }
    .pb-save.is-stale { color: #b45309; }
    html.dark .pb-tools { background: rgba(21,27,18,.92); border-color: #2b3a1c; }
    html.dark .pb-tool { background: #151b12; border-color: #2b3a1c; color: #cbd5c0; }
    html.dark .pb-tool:not(:disabled):hover { background: #22301a; }
    html.dark .pb-tool.is-add { background: #3d6823; color: #fff; border-color: #3d6823; }

    /* The tasks. */
    .pb-list { display: flex; flex-direction: column; gap: .55rem; }
    .pb-card { display: flex; align-items: stretch; gap: 0; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); overflow: hidden; position: relative;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .pb-card:hover { border-color: #cfe3bd; }
    .pb-card.is-new { animation: pbIn .32s cubic-bezier(.22,1,.36,1); }
    @keyframes pbIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
    .pb-card.dragging { opacity: .32; outline: 2px dashed #b9c6a8; outline-offset: -2px; filter: grayscale(.5); }
    .pb-card.just-moved { box-shadow: 0 0 0 3px rgba(107,159,61,.35); }
    .pb-day { flex: none; width: 4.4rem; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: .6rem .3rem; color: #fff; text-align: center; background: var(--prio, #6b7280); }
    .pb-day b { font-size: .6rem; letter-spacing: .06em; opacity: .9; }
    .pb-day span { font-size: 1.35rem; font-weight: 900; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .pb-day i { font-style: normal; font-size: .58rem; opacity: .85; margin-top: .15rem; line-height: 1.15; max-width: 4rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; overflow-wrap: anywhere; }
    .pb-card[data-prio="critical"] { --prio: #9c1c1c; }
    .pb-card[data-prio="high"] { --prio: #d9534f; }
    .pb-card[data-prio="medium"] { --prio: #c9902e; }
    .pb-card[data-prio="low"] { --prio: #7d8a99; }
    .pb-body { flex: 1 1 auto; min-width: 0; padding: .7rem .8rem; cursor: pointer; }
    .pb-body-t b { display: block; font-size: .95rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.3; }
    .pb-body-t i { display: block; font-style: normal; font-size: .78rem; color: var(--color-gray-500); margin-top: .1rem; }
    .pb-chips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .45rem; }
    .pb-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .16rem .5rem; border-radius: 999px; font-size: .68rem; font-weight: 700; border: 1px solid var(--color-gray-200); color: var(--color-gray-600); background: var(--color-gray-50); }
    .pb-chip.is-anee-good { border-color: #cfe3bd; background: #f1f8ea; color: #2f5219; }
    .pb-chip.is-anee-check { border-color: #f3d9a4; background: #fdf6e6; color: #92400e; }
    .pb-chip.is-anee-concern { border-color: #f5c2c2; background: #fdecec; color: #991b1b; }
    .pb-more { display: none; margin-top: .6rem; padding-top: .6rem; border-top: 1px dashed var(--color-gray-200); font-size: .82rem; color: var(--color-gray-700); line-height: 1.5; }
    .pb-card.is-open .pb-more { display: block; }
    .pb-more p { margin: 0 0 .4rem; white-space: pre-wrap; }
    .pb-more .pb-g { margin: .35rem 0; }
    .pb-more .pb-g b { display: block; font-size: .76rem; color: var(--color-gray-900); }
    .pb-more .pb-g b em { font-style: normal; font-weight: 600; color: #3d6823; margin-left: .3rem; }
    .pb-more ul { margin: .15rem 0 0; padding: 0 0 0 1.05rem; }
    .pb-more li { margin: .1rem 0; }
    .pb-more li small { color: var(--color-gray-400); }
    .pb-more .pb-note { margin-top: .4rem; padding: .5rem .65rem; border-radius: .6rem; background: #fdf6e6; color: #713f12; font-size: .78rem; }
    .pb-more .pb-anee { margin-top: .4rem; padding: .5rem .65rem; border-radius: .6rem; background: #f1f8ea; color: #2f5219; font-size: .78rem; }
    .pb-acts { flex: none; display: none; flex-direction: column; justify-content: center; gap: .15rem; padding: .3rem .35rem .3rem 0; }
    .pb-page[data-mode="edit"] .pb-acts { display: flex; }
    .pb-grip, .pb-menu { width: 2rem; height: 2rem; border-radius: .6rem; display: inline-flex; align-items: center; justify-content: center; color: var(--color-gray-400); }
    .pb-grip { cursor: grab; touch-action: none; }
    .pb-grip:active { cursor: grabbing; }
    .pb-grip:hover, .pb-menu:hover { background: var(--color-gray-100); color: var(--color-gray-700); }
    .pb-grip svg, .pb-menu svg { width: 1.15rem; height: 1.15rem; }
    .pb-ghost { position: fixed; top: 0; left: 0; z-index: 90; margin: 0; pointer-events: none; opacity: .78; box-shadow: 0 18px 40px rgba(15,23,42,.3); transform-origin: top left; will-change: transform; transition: none !important; }
    body.pb-dragging { user-select: none; -webkit-user-select: none; -webkit-touch-callout: none; overscroll-behavior: contain; }
    .pb-add-bottom { display: none; align-items: center; justify-content: center; gap: .4rem; width: 100%; margin-top: .7rem; padding: .8rem; border-radius: 1rem; border: 1.5px dashed #b9c6a8; color: #3d6823; font-weight: 800; font-size: .86rem; background: transparent;
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .pb-add-bottom:hover { background: #f1f8ea; }
    .pb-add-bottom svg { width: 1rem; height: 1rem; }
    .pb-page[data-mode="edit"] .pb-add-bottom { display: flex; }
    html.dark .pb-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .pb-card:hover { border-color: #3f5a2a; }
    html.dark .pb-body-t b { color: #e8efe1; }
    html.dark .pb-chip { background: #1c2416; border-color: #2b3a1c; color: #a5b89a; }
    html.dark .pb-more { color: #cbd5c0; border-color: #2b3a1c; }
    html.dark .pb-more .pb-g b { color: #e8efe1; }
    html.dark .pb-more .pb-note { background: #2a2210; color: #f0d9a8; }
    html.dark .pb-more .pb-anee { background: #22301a; color: #cfe6b8; }
    html.dark .pb-grip:hover, html.dark .pb-menu:hover { background: #22301a; }
    html.dark .pb-add-bottom { border-color: #3f5a2a; color: #a5c97e; }
    html.dark .pb-add-bottom:hover { background: #1c2416; }

    /* The empty list. */
    .rx-empty { text-align: center; padding: 2.4rem 1.5rem; }
    .rx-empty-e { display: inline-flex; width: 3.4rem; height: 3.4rem; border-radius: 1rem; background: var(--color-brand-50); color: var(--color-brand-700); align-items: center; justify-content: center; margin-bottom: .7rem; }
    .rx-empty-e svg { width: 1.6rem; height: 1.6rem; }
    .rx-empty-t { font-weight: 800; color: var(--color-gray-900); }
    .rx-empty-p { font-size: .84rem; color: var(--color-gray-500); max-width: 20rem; margin: .3rem auto 0; line-height: 1.5; }
    html.dark .rx-empty-e { background: #22301a; color: #a5c97e; }
    html.dark .rx-empty-t { color: #e8efe1; }

    /* Anee's review. */
    .pb-review { padding: 1rem 1.05rem; margin-bottom: .85rem; border-color: #d9e8c8; background: linear-gradient(135deg, #f7fbf2 0%, #fdfaf0 100%); }
    .pb-rv-head { display: flex; align-items: center; gap: .85rem; }
    .pb-ring { flex: none; width: 4.2rem; height: 4.2rem; border-radius: 999px; display: grid; place-items: center; background: conic-gradient(var(--ring, #6b9f3d) calc(var(--p, 0) * 1%), #e5e7eb 0); position: relative; }
    .pb-ring::before { content: ''; position: absolute; inset: .42rem; border-radius: 999px; background: var(--color-white); }
    .pb-ring b { position: relative; font-size: 1.15rem; font-weight: 900; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .pb-ring small { position: absolute; bottom: .55rem; font-size: .5rem; font-weight: 800; color: var(--color-gray-400); letter-spacing: .04em; }
    .pb-rv-t { min-width: 0; flex: 1 1 auto; }
    .pb-rv-t b { display: block; font-family: var(--font-heading); font-size: 1.02rem; color: #2f5219; }
    .pb-rv-t p { font-size: .84rem; color: #3f4a37; line-height: 1.5; margin-top: .2rem; }
    .pb-rv-t small { display: block; font-size: .7rem; color: #6b7a5e; margin-top: .3rem; }
    .pb-rv-face { width: 2rem; height: 2rem; border-radius: 999px; object-fit: cover; flex: none; }
    .pb-rv-h { font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-500); margin: .9rem 0 .35rem; }
    .pb-rv-list { display: grid; gap: .35rem; }
    .pb-rv-row { padding: .55rem .7rem; border-radius: .75rem; font-size: .8rem; line-height: 1.5; border: 1px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-700); }
    .pb-rv-row b { display: block; color: var(--color-gray-900); }
    .pb-rv-row.is-good { border-color: #cfe3bd; background: #f6fbf0; }
    .pb-rv-row.is-gap { border-color: #f3d9a4; background: #fffbf0; }
    .pb-rv-row.is-risk { border-color: #f5c2c2; background: #fff7f7; }
    .pb-rv-row.is-add { display: flex; align-items: flex-start; gap: .6rem; }
    .pb-rv-row.is-add .grow { min-width: 0; flex: 1 1 auto; }
    .pb-rv-row.is-add .pb-tag { margin-bottom: .2rem; }
    .pb-rv-add { flex: none; padding: .35rem .6rem; border-radius: .6rem; font-size: .72rem; font-weight: 800; color: #fff; background: #3d6823; display: none; }
    .pb-page[data-mode="edit"] .pb-rv-add { display: inline-flex; }
    .pb-rv-add:disabled { opacity: .5; }
    .pb-rv-p { font-size: .82rem; line-height: 1.55; color: #3f4a37; }
    .pb-rv-foot { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-top: .9rem; }
    .pb-rv-again { display: inline-flex; align-items: center; gap: .35rem; padding: .45rem .8rem; border-radius: .7rem; font-size: .76rem; font-weight: 800; color: #2f5219; border: 1px solid #cfe3bd; background: var(--color-white); }
    .pb-rv-again:hover { background: #f1f8ea; }
    .pb-rv-fold { margin-left: auto; font-size: .74rem; font-weight: 700; color: var(--color-gray-500); }
    .pb-review.is-folded .pb-rv-body { display: none; }
    html.dark .pb-review { background: linear-gradient(135deg, #17200f 0%, #221d10 100%); border-color: #2f3f1f; }
    html.dark .pb-ring::before { background: #151b12; }
    html.dark .pb-ring b { color: #e8efe1; }
    html.dark .pb-rv-t b { color: #cfe6b8; }
    html.dark .pb-rv-t p, html.dark .pb-rv-p { color: #b7c2ad; }
    html.dark .pb-rv-row { background: #151b12; border-color: #2b3a1c; color: #cbd5c0; }
    html.dark .pb-rv-row b { color: #e8efe1; }
    html.dark .pb-rv-row.is-good { background: #1a2513; border-color: #3f5a2a; }
    html.dark .pb-rv-row.is-gap { background: #262012; border-color: #6b4f16; }
    html.dark .pb-rv-row.is-risk { background: #2a1717; border-color: #6b2b2b; }
    html.dark .pb-rv-again { background: #151b12; border-color: #3f5a2a; color: #cfe6b8; }

    /* The bar at the bottom: the three things a saved protocol can do. */
    .pb-bar { position: fixed; left: 0; right: 0; bottom: calc(3.5rem + env(safe-area-inset-bottom, 0px)); z-index: 30; display: flex; flex-wrap: nowrap; justify-content: center; gap: .4rem; padding: .55rem .7rem;
        background: rgba(255,255,255,.94); border-top: 1px solid var(--color-gray-200); backdrop-filter: blur(8px); }
    @media (min-width: 1024px) { .pb-bar { bottom: 0; padding-bottom: max(.6rem, env(safe-area-inset-bottom, 0px)); } }
    .pb-btn { display: inline-flex; align-items: center; justify-content: center; gap: .35rem; padding: .55rem .8rem; border-radius: 999px; font-size: .78rem; font-weight: 800; border: 1px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-800); white-space: nowrap; min-width: 0; flex: 0 1 auto;
        transition: transform .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .pb-btn svg { width: .95rem; height: .95rem; flex: none; }
    .pb-btn .pb-long { display: none; } .pb-btn .pb-short { display: inline; }
    @media (min-width: 480px) { .pb-btn .pb-long { display: inline; } .pb-btn .pb-short { display: none; } }
    .pb-btn:hover { transform: translateY(-1px); background: var(--color-gray-50); }
    .pb-btn.is-green { background: #3d6823; color: #fff; border-color: #3d6823; }
    .pb-btn.is-green:hover { background: #2f5219; }
    .pb-btn.is-anee { background: #f1f8ea; color: #2f5219; border-color: #cfe3bd; }
    .pb-btn.is-anee img { width: 1.35rem; height: 1.35rem; border-radius: 999px; object-fit: cover; }
    .pb-btn.is-anee .credit-coin { margin-left: .1rem; }
    .pb-bar [data-mode-only] { display: none; }
    .pb-page[data-mode="view"] ~ .pb-bar [data-mode-only="view"], .pb-page[data-mode="edit"] ~ .pb-bar [data-mode-only="edit"] { display: inline-flex; }
    html.dark .pb-bar { background: rgba(21,27,18,.94); border-color: #2b3a1c; }
    html.dark .pb-btn { background: #151b12; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .pb-btn:hover { background: #1c2416; }
    html.dark .pb-btn.is-anee { background: #22301a; border-color: #3f5a2a; color: #cfe6b8; }

    /* The task sheet. */
    .pbt-day { display: flex; gap: .5rem; align-items: stretch; }
    .pbt-counter { display: inline-flex; border: 1px solid var(--color-gray-200); border-radius: .75rem; overflow: hidden; flex: none; }
    .pbt-counter button { padding: 0 .8rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-500); background: var(--color-white); }
    .pbt-counter button.is-on { background: #3d6823; color: #fff; }
    .pbt-day .form-input { flex: 1 1 auto; min-width: 0; font-weight: 800; font-variant-numeric: tabular-nums; }
    .pbt-stage { font-size: .74rem; color: #3d6823; font-weight: 700; margin-top: .3rem; min-height: 1rem; }
    .pbt-sec { margin-top: 1.1rem; }
    .pbt-sec-h { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; margin-bottom: .4rem; }
    .pbt-sec-h .form-label { margin: 0; }
    .pbt-sec-h small { font-size: .72rem; color: var(--color-gray-500); }
    .pbg { border: 1px solid var(--color-gray-200); border-radius: .9rem; padding: .6rem .6rem .5rem; margin-bottom: .55rem; background: var(--color-gray-50); position: relative; }
    .pbg.dragging { opacity: .32; outline: 2px dashed #b9c6a8; }
    .pbg-head { display: flex; align-items: center; gap: .4rem; }
    .pbg-head .form-input { flex: 1 1 auto; min-width: 0; font-weight: 700; }
    .pbg-knap { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .55rem; border-radius: .6rem; font-size: .7rem; font-weight: 800; color: var(--color-gray-500); border: 1px solid var(--color-gray-200); background: var(--color-white); white-space: nowrap; flex: none; }
    .pbg-knap.is-on { color: #2f5219; border-color: #cfe3bd; background: #f1f8ea; }
    .pbg-items { display: flex; flex-direction: column; gap: .35rem; margin-top: .45rem; }
    .pbi { display: grid; grid-template-columns: auto minmax(0, 1fr) auto; grid-template-areas: 'grip name x' 'grip meta x'; gap: .25rem .35rem; align-items: center; padding: .4rem .4rem; border-radius: .7rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .pbi.dragging { opacity: .32; outline: 2px dashed #b9c6a8; }
    .pbi-grip { grid-area: grip; }
    .pbi-x { grid-area: x; }
    .pbi-name { grid-area: name; }
    .pbi-meta { grid-area: meta; display: flex; gap: .35rem; min-width: 0; }
    .pbi-kind { flex: 1 1 55%; min-width: 0; display: inline-flex; align-items: center; gap: .3rem; padding: .35rem .5rem; border-radius: .6rem; font-size: .72rem; font-weight: 700; color: var(--color-gray-700); border: 1px solid var(--color-gray-200); background: var(--color-gray-50); text-align: left; }
    .pbi-kind span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pbi-kind svg { width: .8rem; height: .8rem; flex: none; margin-left: auto; color: var(--color-gray-400); }
    .pbi-amount { flex: 1 1 45%; min-width: 0; }
    .pbi .form-input, .pbg .form-input { padding: .42rem .6rem !important; font-size: .82rem !important; }
    .pb-mini { width: 1.9rem; height: 1.9rem; border-radius: .55rem; display: inline-flex; align-items: center; justify-content: center; color: var(--color-gray-400); flex: none; }
    .pb-mini:hover { background: var(--color-gray-100); color: var(--color-gray-700); }
    .pb-mini svg { width: 1rem; height: 1rem; }
    .pb-mini.is-grip { cursor: grab; touch-action: none; }
    .pb-mini.is-x:hover { background: #fdecec; color: #b91c1c; }
    .pbg-add, .pbt-add { display: inline-flex; align-items: center; gap: .3rem; font-size: .76rem; font-weight: 800; color: #3d6823; padding: .35rem .5rem; border-radius: .6rem; }
    .pbg-add:hover, .pbt-add:hover { background: #f1f8ea; }
    .pbg-add svg, .pbt-add svg { width: .9rem; height: .9rem; }
    .pbt-add { width: 100%; justify-content: center; border: 1.5px dashed #b9c6a8; padding: .55rem; }
    .pbt-prio { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .4rem; }
    @media (min-width: 480px) { .pbt-prio { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .pbt-prio button { padding: .5rem .4rem; border-radius: .7rem; font-size: .78rem; font-weight: 800; border: 1px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-600); }
    .pbt-prio button.is-on { color: #fff; border-color: transparent; background: var(--prio); }
    .pbt-prio button[data-prio="critical"] { --prio: #9c1c1c; }
    .pbt-prio button[data-prio="high"] { --prio: #d9534f; }
    .pbt-prio button[data-prio="medium"] { --prio: #c9902e; }
    .pbt-prio button[data-prio="low"] { --prio: #7d8a99; }
    html.dark .pbt-counter button { background: #151b12; color: #9aa78d; }
    html.dark .pbt-counter button.is-on { background: #3d6823; color: #fff; }
    html.dark .pbg { background: #1c2416; border-color: #2b3a1c; }
    html.dark .pbi { background: #151b12; border-color: #2b3a1c; }
    html.dark .pbg-knap, html.dark .pbi-kind { background: #151b12; border-color: #2b3a1c; color: #cbd5c0; }
    html.dark .pbg-knap.is-on { background: #22301a; border-color: #3f5a2a; color: #cfe6b8; }
    html.dark .pbt-prio button { background: #151b12; border-color: #2b3a1c; color: #cbd5c0; }
    html.dark .pb-mini:hover { background: #22301a; }

    /* The out-of-order notice. */
    .pbf-say { font-size: .86rem; line-height: 1.55; color: var(--color-gray-700); }
    .pbf-say b { color: var(--color-gray-900); }
    .pbf-hint { font-size: .74rem; color: var(--color-gray-500); margin-top: .35rem; }
    .pbf-hint.is-bad { color: #b91c1c; font-weight: 700; }
    html.dark .pbf-say { color: #cbd5c0; }
    html.dark .pbf-say b { color: #e8efe1; }

    .pbp-two { display: grid; grid-template-columns: minmax(0, 1fr) 7rem; gap: .5rem; }
    .pb-quote { display: flex; gap: .7rem; align-items: center; padding: .8rem .9rem; border-radius: .9rem; background: #f1f8ea; border: 1px solid #cfe3bd; font-size: .84rem; line-height: 1.5; color: #2f5219; }
    .pb-quote img { width: 2.6rem; height: 2.6rem; border-radius: 999px; object-fit: cover; flex: none; }
    html.dark .pb-quote { background: #22301a; border-color: #3f5a2a; color: #cfe6b8; }
    @media (prefers-reduced-motion: reduce) { .pb-card, .pb-tool, .pb-btn, .pb-add-bottom, .pb-save { transition: none; } .pb-card.is-new { animation: none; } }
</style>
@endpush

@section('content')
<div class="max-w-2xl mx-auto pb-page" id="pbPage" data-mode="{{ $startInEdit ? 'edit' : 'view' }}">
    <div class="card pb-head">
        <div class="pb-head-row">
            <span class="pb-head-e" id="pbHeadIcon">{{ $protocol['cropIcon'] }}</span>
            <div class="pb-head-t">
                <b id="pbHeadTitle">{{ $protocol['title'] }}</b>
                <small id="pbHeadSub"></small>
            </div>
            <button type="button" class="pb-pen" id="pbMetaBtn" title="Name, crop, variety, day count" aria-label="Edit the protocol's details">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
        </div>
        <p class="pb-head-p hidden" id="pbHeadDesc"></p>
        <div class="pb-head-tags" id="pbHeadTags"></div>
    </div>

    <div class="pb-tools" id="pbTools">
        <button type="button" class="pb-tool" id="pbUndo" disabled title="Undo (Ctrl+Z)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg> Undo
        </button>
        <button type="button" class="pb-tool" id="pbRedo" disabled title="Redo (Ctrl+Shift+Z)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 14 5-5-5-5"/><path d="M20 9H9.5a5.5 5.5 0 0 0 0 11H13"/></svg> Redo
        </button>
        <span class="pb-save" id="pbSaveState" aria-live="polite"></span>
        <button type="button" class="pb-tool is-add" id="pbAddTop">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Add task
        </button>
    </div>

    <div id="pbReview"></div>

    <div class="pb-list" id="pbList"></div>
    <div class="rx-empty hidden" id="pbEmpty">
        <span class="rx-empty-e"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 7h6m-6 4h4"/></svg></span>
        <p class="rx-empty-t">No tasks yet</p>
        <p class="rx-empty-p" id="pbEmptyP">Add the first task: the day of the count it falls on, what is done, and what to apply.</p>
    </div>
    <button type="button" class="pb-add-bottom" id="pbAddBottom">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Add a task
    </button>
</div>

<div class="pb-bar" id="pbBar">
    <button type="button" class="pb-btn is-green" data-mode-only="view" id="pbEditBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg> Edit
    </button>
    <button type="button" class="pb-btn" data-mode-only="view" id="pbPortBtn"
        @if (! $options['canPort']) data-tier-lock="{{ $options['portRung'] }}" data-lock-say="Every plan has a number of active seasons. Yours is full — finish or archive one, or upgrade for more room." @endif>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="17" rx="2.5"/><path d="M3 9h18M8 2v4M16 2v4M12 12v6M9 15h6"/></svg> <span class="pb-long">Port into a season</span><span class="pb-short">Port to season</span>
    </button>
    <button type="button" class="pb-btn is-anee" data-mode-only="view" id="pbAneeBtn"
        @if ($options['aiLocked']) data-tier-lock="libreAnee" data-lock-say="Anee's review of your protocol comes with Libre + Anee — she reads every task and says what is strong, what is missing and what could go wrong." @endif>
        <img src="{{ $options['aneeFace'] }}" alt=""> <span class="pb-long">Ask Anee to review</span><span class="pb-short">Ask Anee</span>
    </button>
    <button type="button" class="pb-btn is-green" data-mode-only="edit" id="pbDoneBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg> Done editing
    </button>
</div>

{{-- The task sheet: one task, all its parts. --}}
<div class="sheet hidden sheet-full" id="pbTaskSheet" style="--sheet-width:32rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="pbTaskTitle">New task</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div>
            <span class="form-label">Which day of the count</span>
            <div class="pbt-day">
                <div class="pbt-counter" id="pbtCounter"></div>
                <input type="number" id="pbtDay" class="form-input" inputmode="numeric" step="1" min="-365" max="999" placeholder="e.g. 14">
            </div>
            <p class="pbt-stage" id="pbtStage"></p>
        </div>
        <div class="mt-4">
            <label class="form-label" for="pbtTitleIn">Title</label>
            <input type="text" id="pbtTitleIn" class="form-input" maxlength="160" placeholder="e.g. First top-dress">
        </div>
        <div class="mt-3">
            <label class="form-label" for="pbtSub">Subtitle <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="text" id="pbtSub" class="form-input" maxlength="200" placeholder="e.g. Urea + complete, broadcast after the water is let in">
        </div>
        <div class="mt-3">
            <span class="form-label">Activity type</span>
            <button type="button" class="crop-tag" id="pbtTypeBtn">
                <span class="crop-tag-e" id="pbtTypeIcon">📌</span>
                <span class="crop-tag-t is-none" id="pbtTypeNow">Choose a type</span>
                <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
        </div>
        <div class="mt-3">
            <label class="form-label" for="pbtDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="pbtDesc" class="form-input" rows="3" maxlength="4000" placeholder="How it is done, what to watch while doing it…"></textarea>
        </div>

        <div class="pbt-sec">
            <div class="pbt-sec-h">
                <span class="form-label">What to apply</span>
                <small>Groups of items — a group can be per knapsack</small>
            </div>
            <div id="pbGroups"></div>
            <button type="button" class="pbt-add" id="pbGroupAdd">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Add a group
            </button>
        </div>

        <div class="mt-4">
            <label class="form-label" for="pbtNote">Note <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="pbtNote" class="form-input" rows="2" maxlength="2000" placeholder="A caution, a reminder, a lesson from last season…"></textarea>
        </div>
        <div class="mt-4">
            <span class="form-label">Importance</span>
            <div class="pbt-prio" id="pbtPrio"></div>
        </div>
        <div class="mt-4">
            <label class="form-label" for="pbtWorkers">Workers needed <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="number" id="pbtWorkers" class="form-input" inputmode="numeric" min="0" max="999" step="1" placeholder="e.g. 4">
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn text-red-600 bg-red-50 hover:bg-red-100 border border-red-100" id="pbtDelete" hidden>Delete</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="pbtSave">Save task</button>
    </div>
</div>

{{-- Activity type chooser --}}
<div class="sheet hidden" id="pbTypeSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Activity type</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="pbTypeList"></div>
</div>

{{-- Item kind chooser --}}
<div class="sheet hidden" id="pbKindSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">What kind of item?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="pbKindList"></div>
</div>

{{-- A task's menu --}}
<div class="sheet hidden" id="pbTaskMenu" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="pbTaskMenuTitle">Task</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows">
        <button type="button" class="dt-row" data-task-act="edit"><span class="dt-row-e">✏️</span><span class="dt-row-body"><b>Edit</b><i>Day, words, what to apply.</i></span></button>
        <button type="button" class="dt-row" data-task-act="copy"><span class="dt-row-e">📑</span><span class="dt-row-body"><b>Duplicate</b><i>A copy on the same day, to change.</i></span></button>
        <button type="button" class="dt-row" data-task-act="delete"><span class="dt-row-e">🗑️</span><span class="dt-row-body"><b>Delete</b><i>Remove this task. Undo can bring it back.</i></span></button>
    </div>
</div>

{{-- Dropped out of order --}}
<div class="sheet hidden" id="pbFixSheet" style="--sheet-width:26rem" data-static>
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">That day is out of order</h3>
        <button type="button" class="btn-ghost p-2 rounded-full" id="pbFixX" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="pbf-say" id="pbFixSay"></p>
        <div class="mt-4">
            <span class="form-label">Change its day to</span>
            <div class="pbt-day">
                <div class="pbt-counter" id="pbFixCounter"></div>
                <input type="number" id="pbFixDay" class="form-input" inputmode="numeric" step="1" min="-365" max="999">
            </div>
            <p class="pbf-hint" id="pbFixHint"></p>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" id="pbFixBack">Put it back</button>
        <button type="button" class="btn btn-primary" id="pbFixGo">Change it</button>
    </div>
</div>

{{-- Name, crop, variety, count --}}
<div class="sheet hidden" id="pbMetaSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">About this protocol</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        @include('protocol-builder.partials.head-form', ['pfx' => 'pbm'])
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn text-red-600 bg-red-50 hover:bg-red-100 border border-red-100" id="pbMetaDelete">Delete protocol</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="pbMetaSave">Save</button>
    </div>
</div>

{{-- Port into a season --}}
<div class="sheet hidden" id="pbPortSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Port into a cropping schedule</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <p class="text-sm text-gray-600" id="pbPortSay"></p>
        <div>
            <label class="form-label" for="pbPortTitle">Season title</label>
            <input type="text" id="pbPortTitle" class="form-input" maxlength="255">
        </div>
        <div>
            <label class="form-label" for="pbPortStart" id="pbPortStartL">Start date</label>
            <input type="date" id="pbPortStart" class="form-input">
        </div>
        <div id="pbPortTransWrap" hidden>
            <label class="form-label" for="pbPortTrans">Transplant date <span class="text-gray-400 font-normal">(DAT 0)</span></label>
            <input type="date" id="pbPortTrans" class="form-input">
            <p class="pbh-hint" id="pbPortTransHint"></p>
        </div>
        <div>
            <label class="form-label" for="pbPortLot">Lot name</label>
            <input type="text" id="pbPortLot" class="form-input" maxlength="255">
        </div>
        <div>
            <span class="form-label">Lot size</span>
            <div class="pbp-two">
                <input type="number" id="pbPortSize" class="form-input" inputmode="decimal" min="0" step="any" value="1">
                <select id="pbPortUnit" class="form-select">
                    <option value="hectare">Hectare</option>
                    <option value="sqm">Square meter</option>
                    <option value="acre">Acre</option>
                </select>
            </div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="pbPortGo">Create the season</button>
    </div>
</div>

{{-- Ask Anee --}}
<div class="sheet hidden" id="pbAskSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Ask Anee to review</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div class="pb-quote"><img src="{{ $options['aneeFace'] }}" alt=""><div id="pbAskQuote"></div></div>
        <p class="text-sm text-gray-600">Anee reads every task against the crop's growth stages and says what is strong, what is missing, what could go wrong, and what she would add. The review is kept with the protocol; a new one replaces it.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Not now</button>
        <button type="button" class="btn btn-primary" id="pbAskGo">Review it</button>
    </div>
</div>

@include('sm.partials.anee-wait')

<script>
(() => {
    const BOOT = @json($protocol);
    const OPT = @json($options);
    let STAGES = @json($stages);
    const U = {
        base: @json(url('/app/protocol-builder')) + '/' + BOOT.id,
        list: @json(route('pb.page')),
        job: (id) => @json(url('/app/protocol-builder')) + '/' + id + '/job',
    };
    const $id = (i) => document.getElementById(i);
    const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const clone = (v) => JSON.parse(JSON.stringify(v));
    const uid = (p) => p + Math.random().toString(36).slice(2, 9);
    const PHASE = { DAS: 0, DAP: 0, DAT: 1 };
    const TYPE_ICON = { equipment_prep: '🛠️', land_prep: '🚜', seed_treatment: '🧪', planting: '🌱', irrigation: '💧', service: '🧾', fertilizer: '🧂', foliar_spray: '🌫️', herbicide: '🌿', pesticide: '🐛', copper_fungicide: '🟠', fungicide: '🍄', microbial: '🦠', harvest: '🌾', monitoring: '🔍', worker_payroll: '👷', reminder_checklist: '✅', other: '📌' };
    const GRIP = '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>';
    const DOTS = '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>';
    const X = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg>';
    const CHEV = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';

    /* ------------------------------------------------------------ state */
    let P = { id: BOOT.id, title: BOOT.title, description: BOOT.description, crop: BOOT.crop, cropLabel: BOOT.cropLabel, cropIcon: BOOT.cropIcon, variety: BOOT.variety, dayType: BOOT.dayType, ported: BOOT.ported };
    let TASKS = Array.isArray(BOOT.tasks) ? BOOT.tasks : [];
    let HIST = { undo: (BOOT.history && BOOT.history.undo) || [], redo: (BOOT.history && BOOT.history.redo) || [] };
    let REV = BOOT.rev || 1;
    let REVIEW = BOOT.analysis || null;
    let REVIEW_AT = BOOT.analysisAt || null;
    let REVIEW_CREDITS = BOOT.analysisCredits || 0;
    const UNDO_MAX = 30;
    const counters = () => (OPT.dayTypes[P.dayType] || OPT.dayTypes.DAS).counters;
    const mode = () => $id('pbPage').getAttribute('data-mode');
    const editing = () => mode() === 'edit';

    const keyOf = (t) => [PHASE[t.counter] ?? 0, t.day, t.pos ?? 0];
    const cmpKey = (a, b) => (a[0] - b[0]) || (a[1] - b[1]) || ((a[2] ?? 0) - (b[2] ?? 0));
    const cmpDay = (a, b) => (a[0] - b[0]) || (a[1] - b[1]);
    function sortTasks() { TASKS.sort((a, b) => cmpKey(keyOf(a), keyOf(b))); }
    function stageLabel(counter, day) {
        const two = counters().length === 2;
        if (two && counter === 'DAS') return day < 0 ? 'Before sowing' : 'Seedbed';
        if (day < 0) return counter === 'DAT' ? 'Before transplant' : (counter === 'DAP' ? 'Before planting' : 'Before sowing');
        const rows = STAGES[counter] || [];
        let label = '';
        for (const r of rows) { if (r[0] <= day) label = r[1]; else break; }
        return label;
    }
    const sayDay = (t) => `${t.counter} ${t.day}`;

    /* ------------------------------------------------------------ autosave */
    let DIRTY = false, BUSY = false, AGAIN = false, STALE = false, FAILS = 0, saveTimer = null;
    function say(state) {
        const el = $id('pbSaveState');
        el.textContent = { saving: 'Saving…', saved: '✓ Saved', failed: 'Not saved — retrying', stale: 'Reload to save' }[state] || '';
        el.classList.toggle('is-saved', state === 'saved');
        el.classList.toggle('is-failed', state === 'failed');
        el.classList.toggle('is-stale', state === 'stale');
    }
    function markDirty() {
        if (STALE) return;
        DIRTY = true;
        if (!BUSY) say('');
        clearTimeout(saveTimer);
        saveTimer = setTimeout(runSave, 700);
    }
    async function runSave() {
        clearTimeout(saveTimer);
        if (STALE || !DIRTY) return;
        if (BUSY) { AGAIN = true; return; }
        BUSY = true; DIRTY = false;
        say('saving');
        try {
            const res = await api(U.base + '/save', { method: 'POST', body: { rev: REV, tasks: TASKS, history: HIST } });
            REV = res.data.rev; FAILS = 0;
            say('saved');
        } catch (err) {
            if (err.status === 409 || (err.data && err.data.stale)) {
                STALE = true; say('stale');
                window.noticeSheet({ title: 'Changed somewhere else', message: 'This protocol was saved from another tab or device. Reload the page to keep working on the latest copy — what you just did here was not saved.', okText: 'OK' });
            } else {
                DIRTY = true; say('failed');
                if (++FAILS <= 8) saveTimer = setTimeout(runSave, Math.min(30000, 2000 * FAILS));
            }
        } finally {
            BUSY = false;
            if (AGAIN) { AGAIN = false; markDirty(); }
        }
    }
    async function flushSave() { clearTimeout(saveTimer); if (DIRTY && !BUSY) await runSave(); else if (BUSY) { await new Promise((r) => setTimeout(r, 400)); if (DIRTY) await runSave(); } }
    window.addEventListener('beforeunload', (e) => { if ((DIRTY || BUSY) && !STALE) { e.preventDefault(); e.returnValue = ''; } });
    window.addEventListener('pagehide', () => {
        if (!DIRTY || STALE) return;
        try {
            fetch(U.base + '/save', { method: 'POST', keepalive: true, credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ rev: REV, tasks: TASKS, history: HIST }) });
        } catch (_) {}
    });

    /* ------------------------------------------------------------ undo / redo */
    function commit(label, mutate) {
        HIST.undo.push(clone(TASKS));
        if (HIST.undo.length > UNDO_MAX) HIST.undo.shift();
        HIST.redo = [];
        mutate();
        sortTasks();
        render();
        markDirty();
        return label;
    }
    function travel(from, to, word) {
        if (!from.length) return;
        to.push(clone(TASKS));
        TASKS = from.pop();
        sortTasks();
        render();
        markDirty();
        toast(word);
    }
    const undo = () => travel(HIST.undo, HIST.redo, 'Undone');
    const redo = () => travel(HIST.redo, HIST.undo, 'Redone');
    $id('pbUndo').addEventListener('click', undo);
    $id('pbRedo').addEventListener('click', redo);
    document.addEventListener('keydown', (e) => {
        if (!editing() || !(e.ctrlKey || e.metaKey)) return;
        const k = (e.key || '').toLowerCase();
        const isUndo = k === 'z' && !e.shiftKey;
        const isRedo = (k === 'z' && e.shiftKey) || k === 'y';
        if (!isUndo && !isRedo) return;
        const tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;
        if (document.querySelector('.sheet.is-open')) return;
        e.preventDefault();
        (isUndo ? undo : redo)();
    });

    /* ------------------------------------------------------------ paint */
    function renderHead() {
        const dt = OPT.dayTypes[P.dayType] || {};
        $id('pbHeadIcon').textContent = P.cropIcon || '🌱';
        $id('pbHeadTitle').textContent = P.title;
        $id('pbHeadSub').textContent = `${P.cropLabel || 'No crop yet'}${P.variety ? ' · ' + P.variety : ''} · ${dt.label || P.dayType}`;
        const d = $id('pbHeadDesc');
        d.textContent = P.description || '';
        d.classList.toggle('hidden', !P.description);
        const tags = [`<span class="pb-tag"><b>${TASKS.length}</b> ${TASKS.length === 1 ? 'task' : 'tasks'}</span>`];
        const n = TASKS.length ? TASKS[TASKS.length - 1] : null;
        if (n) tags.push(`<span class="pb-tag">Runs to <b>${esc(sayDay(n))}</b></span>`);
        if (REVIEW) tags.push(`<span class="pb-tag is-score">Anee: <b>${REVIEW.score}/100</b></span>`);
        if (P.ported) tags.push(`<a class="pb-tag is-ported" href="${esc(P.ported.url)}">Ported ${esc(P.ported.at || '')} → <b>${esc(P.ported.title || 'the season')}</b></a>`);
        $id('pbHeadTags').innerHTML = tags.join('');
        const pageTitle = $id('appPageTitle'); if (pageTitle) { pageTitle.textContent = P.title; const sub = pageTitle.nextElementSibling; if (sub && sub.tagName === 'P') sub.textContent = `${P.cropLabel || 'No crop yet'} · ${dt.label || P.dayType}`; }
        document.title = P.title + ' | anee.io';
        $id('pbMetaBtn').style.display = editing() ? '' : 'none';
    }
    function reviewFor(id) {
        if (!REVIEW || !Array.isArray(REVIEW.tasks)) return null;
        return REVIEW.tasks.find((r) => String(r.id) === String(id)) || null;
    }
    function cardHtml(t) {
        const stage = stageLabel(t.counter, t.day);
        const type = t.type ? OPT.types[t.type] : null;
        const items = t.groups.reduce((n, g) => n + g.items.length, 0);
        const rv = reviewFor(t.id);
        const chips = [];
        if (type) chips.push(`<span class="pb-chip">${TYPE_ICON[t.type] || '📌'} ${esc(type)}</span>`);
        chips.push(`<span class="pb-chip">${esc((OPT.priorities[t.priority] || {}).label || t.priority)}</span>`);
        if (items) chips.push(`<span class="pb-chip">🧴 ${items} ${items === 1 ? 'item' : 'items'}</span>`);
        if (t.workers !== null && t.workers !== undefined) chips.push(`<span class="pb-chip">👷 ${t.workers}</span>`);
        if (t.note) chips.push(`<span class="pb-chip">📝 note</span>`);
        if (rv) chips.push(`<span class="pb-chip is-anee-${esc(rv.verdict || 'check')}">Anee: ${esc(rv.verdict || 'check')}</span>`);
        const more = [];
        if (t.description) more.push(`<p>${esc(t.description)}</p>`);
        t.groups.forEach((g) => {
            more.push(`<div class="pb-g"><b>${esc(g.title || 'Apply')}${g.perKnapsack ? '<em>per knapsack</em>' : ''}</b>${g.items.length ? '<ul>' + g.items.map((it) => `<li>${esc(it.name)}${it.kind && it.kind !== 'other' ? ` <small>· ${esc((OPT.kinds[it.kind] || {}).label || it.kind)}</small>` : ''}${it.amount ? ` <small>· ${esc(it.amount)}</small>` : ''}</li>`).join('') + '</ul>' : ''}</div>`);
        });
        if (t.note) more.push(`<div class="pb-note">📝 ${esc(t.note)}</div>`);
        if (rv && rv.note) more.push(`<div class="pb-anee">Anee: ${esc(rv.note)}</div>`);
        return `
            <div class="pb-card" data-id="${esc(t.id)}" data-prio="${esc(t.priority)}">
                <div class="pb-day"><b>${esc(t.counter)}</b><span>${t.day}</span>${stage ? `<i title="${esc(stage)}">${esc(stage)}</i>` : ''}</div>
                <div class="pb-body">
                    <div class="pb-body-t"><b>${esc(t.title)}</b>${t.subtitle ? `<i>${esc(t.subtitle)}</i>` : ''}</div>
                    <div class="pb-chips">${chips.join('')}</div>
                    <div class="pb-more">${more.join('') || '<p class="text-gray-400">Nothing more on this task.</p>'}</div>
                </div>
                <div class="pb-acts">
                    <button type="button" class="pb-grip" aria-label="Drag to reorder" title="Drag to reorder">${GRIP}</button>
                    <button type="button" class="pb-menu" aria-label="More" title="More">${DOTS}</button>
                </div>
            </div>`;
    }
    let OPEN = new Set();
    function render() {
        renderHead();
        const list = $id('pbList');
        list.innerHTML = TASKS.map(cardHtml).join('');
        OPEN.forEach((id) => { const c = list.querySelector(`.pb-card[data-id="${CSS.escape(id)}"]`); if (c) c.classList.add('is-open'); });
        $id('pbEmpty').classList.toggle('hidden', TASKS.length > 0);
        $id('pbEmptyP').textContent = editing() ? 'Add the first task: the day of the count it falls on, what is done, and what to apply.' : 'This protocol has no tasks yet. Edit it to add the first one.';
        $id('pbUndo').disabled = !HIST.undo.length;
        $id('pbRedo').disabled = !HIST.redo.length;
        renderReview();
    }
    function setMode(m) {
        $id('pbPage').setAttribute('data-mode', m);
        render();
        const url = new URL(window.location.href);
        if (m === 'edit') url.searchParams.set('edit', '1'); else url.searchParams.delete('edit');
        history.replaceState(null, '', url.toString());
        if (m === 'view') flushSave();
    }
    $id('pbEditBtn').addEventListener('click', () => setMode('edit'));
    $id('pbDoneBtn').addEventListener('click', () => setMode('view'));

    // Tap a card: in view mode it unfolds, in edit mode it opens for editing.
    $id('pbList').addEventListener('click', (e) => {
        const card = e.target.closest('.pb-card');
        if (!card) return;
        const id = card.getAttribute('data-id');
        if (e.target.closest('.pb-menu')) { openTaskMenu(id); return; }
        if (e.target.closest('.pb-grip')) return;
        if (editing()) { openTask(id); return; }
        card.classList.toggle('is-open');
        if (card.classList.contains('is-open')) OPEN.add(id); else OPEN.delete(id);
    });

    /* ------------------------------------------------------------ the task menu */
    let MENU_ID = null;
    function openTaskMenu(id) {
        MENU_ID = id;
        const t = TASKS.find((x) => x.id === id);
        $id('pbTaskMenuTitle').textContent = t ? t.title : 'Task';
        openSheet('pbTaskMenu');
    }
    $id('pbTaskMenu').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-task-act]');
        if (!b || MENU_ID === null) return;
        const act = b.getAttribute('data-task-act');
        const id = MENU_ID;
        closeSheet('pbTaskMenu');
        if (act === 'edit') { setTimeout(() => openTask(id), 220); return; }
        if (act === 'copy') {
            const t = TASKS.find((x) => x.id === id);
            if (!t) return;
            commit('Duplicated', () => {
                const c = clone(t); c.id = uid('t'); c.pos = (t.pos ?? 0) + 5;
                c.groups.forEach((g) => { g.id = uid('g'); g.items.forEach((it) => { it.id = uid('i'); }); });
                TASKS.push(c);
                flash(c.id);
            });
            toast('Duplicated — the copy sits on the same day.');
            return;
        }
        if (act === 'delete') {
            const t = TASKS.find((x) => x.id === id);
            commit('Deleted', () => { TASKS = TASKS.filter((x) => x.id !== id); });
            toast(`Deleted "${t ? t.title : 'the task'}" — Undo brings it back.`);
        }
    });
    let FLASH = null;
    function flash(id) { FLASH = id; }
    const _render = render;
    render = function () {
        _render();
        if (FLASH) {
            const c = $id('pbList').querySelector(`.pb-card[data-id="${CSS.escape(FLASH)}"]`);
            if (c) { c.classList.add('is-new', 'just-moved'); setTimeout(() => c.classList.remove('just-moved'), 1200); c.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
            FLASH = null;
        }
    };

    /* ------------------------------------------------------------ the task sheet */
    let W = null;          // the working copy
    let W_ID = null;       // null = a new task
    let KIND_FOR = null;   // the item row whose kind is being chosen
    function blankTask() {
        const last = TASKS.length ? TASKS[TASKS.length - 1] : null;
        return { id: uid('t'), counter: last ? last.counter : counters()[0], day: last ? last.day : 0, title: '', subtitle: '', description: '', type: null, groups: [], note: '', priority: 'medium', workers: null, pos: last ? (last.pos ?? 0) + 10 : 0 };
    }
    function paintCounter(host, counter, onPick) {
        const list = counters();
        host.innerHTML = list.map((c) => `<button type="button" data-c="${c}" class="${c === counter ? 'is-on' : ''}">${c}</button>`).join('');
        host.style.display = list.length > 1 ? '' : 'none';
        host.onclick = (e) => { const b = e.target.closest('[data-c]'); if (!b) return; host.querySelectorAll('button').forEach((x) => x.classList.toggle('is-on', x === b)); onPick(b.getAttribute('data-c')); };
    }
    function paintType() {
        const t = W.type ? OPT.types[W.type] : null;
        $id('pbtTypeIcon').textContent = W.type ? (TYPE_ICON[W.type] || '📌') : '📌';
        const now = $id('pbtTypeNow');
        now.textContent = t || 'Choose a type';
        now.classList.toggle('is-none', !t);
    }
    function paintStage() {
        const d = parseInt($id('pbtDay').value, 10);
        $id('pbtStage').textContent = Number.isFinite(d) ? (stageLabel(W.counter, d) || '') : '';
    }
    function paintPrio() {
        $id('pbtPrio').innerHTML = Object.entries(OPT.priorities).map(([k, p]) => `<button type="button" data-prio="${k}" class="${W.priority === k ? 'is-on' : ''}" title="${esc(p.sub)}">${esc(p.label)}</button>`).join('');
    }
    function groupHtml(g) {
        return `
            <div class="pbg" data-g="${esc(g.id)}">
                <div class="pbg-head">
                    <button type="button" class="pb-mini is-grip pbg-grip" aria-label="Drag the group">${GRIP}</button>
                    <input type="text" class="form-input pbg-title" maxlength="120" placeholder="Group name — e.g. Tank mix A, Per knapsack (16 L)" value="${esc(g.title)}">
                    <button type="button" class="pbg-knap ${g.perKnapsack ? 'is-on' : ''}" title="The amounts are per knapsack">🎒 <span>${g.perKnapsack ? 'Per knapsack' : 'Per knapsack?'}</span></button>
                    <button type="button" class="pb-mini is-x pbg-x" aria-label="Remove the group">${X}</button>
                </div>
                <div class="pbg-items">${g.items.map(itemHtml).join('')}</div>
                <button type="button" class="pbg-add pbi-add"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Add an item</button>
            </div>`;
    }
    function itemHtml(it) {
        const k = OPT.kinds[it.kind] || OPT.kinds.other;
        return `
            <div class="pbi" data-i="${esc(it.id)}">
                <button type="button" class="pb-mini is-grip pbi-grip" aria-label="Drag the item">${GRIP}</button>
                <input type="text" class="form-input pbi-name" maxlength="160" placeholder="What — e.g. Butachlor 60 EC" value="${esc(it.name)}">
                <div class="pbi-meta">
                    <button type="button" class="pbi-kind">${esc(k.icon)} <span>${esc(k.label)}</span>${CHEV}</button>
                    <input type="text" class="form-input pbi-amount" maxlength="80" placeholder="How much — e.g. 50 ml" value="${esc(it.amount)}">
                </div>
                <button type="button" class="pb-mini is-x pbi-x" aria-label="Remove the item">${X}</button>
            </div>`;
    }
    function paintGroups() {
        $id('pbGroups').innerHTML = W.groups.map(groupHtml).join('');
        wireGroupDrags();
    }
    function gOf(el) { const g = el.closest('.pbg'); return g ? W.groups.find((x) => x.id === g.getAttribute('data-g')) : null; }
    function iOf(el) { const g = gOf(el); const i = el.closest('.pbi'); return (g && i) ? g.items.find((x) => x.id === i.getAttribute('data-i')) : null; }
    $id('pbGroups').addEventListener('input', (e) => {
        const g = gOf(e.target); if (!g) return;
        if (e.target.classList.contains('pbg-title')) { g.title = e.target.value; return; }
        const it = iOf(e.target); if (!it) return;
        if (e.target.classList.contains('pbi-name')) it.name = e.target.value;
        if (e.target.classList.contains('pbi-amount')) it.amount = e.target.value;
    });
    $id('pbGroups').addEventListener('click', (e) => {
        const g = gOf(e.target); if (!g) return;
        if (e.target.closest('.pbg-knap')) {
            g.perKnapsack = !g.perKnapsack;
            const b = e.target.closest('.pbg-knap'); b.classList.toggle('is-on', g.perKnapsack); b.querySelector('span').textContent = g.perKnapsack ? 'Per knapsack' : 'Per knapsack?';
            return;
        }
        if (e.target.closest('.pbg-x')) { W.groups = W.groups.filter((x) => x !== g); paintGroups(); return; }
        if (e.target.closest('.pbi-add')) {
            const it = { id: uid('i'), name: '', kind: 'other', amount: '' };
            g.items.push(it);
            const host = e.target.closest('.pbg').querySelector('.pbg-items');
            host.insertAdjacentHTML('beforeend', itemHtml(it));
            wireGroupDrags();
            host.lastElementChild.querySelector('.pbi-name').focus();
            return;
        }
        const it = iOf(e.target); if (!it) return;
        if (e.target.closest('.pbi-x')) { g.items = g.items.filter((x) => x !== it); e.target.closest('.pbi').remove(); return; }
        if (e.target.closest('.pbi-kind')) {
            KIND_FOR = { g, it, btn: e.target.closest('.pbi-kind') };
            $id('pbKindList').querySelectorAll('[data-kind]').forEach((r) => r.classList.toggle('is-on', r.getAttribute('data-kind') === it.kind));
            openSheet('pbKindSheet');
        }
    });
    $id('pbGroupAdd').addEventListener('click', () => {
        const g = { id: uid('g'), title: '', perKnapsack: W.groups.length === 0, items: [{ id: uid('i'), name: '', kind: 'other', amount: '' }] };
        W.groups.push(g);
        $id('pbGroups').insertAdjacentHTML('beforeend', groupHtml(g));
        wireGroupDrags();
        const el = $id('pbGroups').lastElementChild;
        el.querySelector('.pbg-title').focus();
        el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    });
    $id('pbKindList').innerHTML = Object.entries(OPT.kinds).map(([k, v]) => `
        <button type="button" class="dt-row" data-kind="${k}"><span class="dt-row-e">${esc(v.icon)}</span><span class="dt-row-body"><b>${esc(v.label)}</b></span>
        <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>`).join('');
    $id('pbKindList').addEventListener('click', (e) => {
        const r = e.target.closest('[data-kind]'); if (!r || !KIND_FOR) return;
        KIND_FOR.it.kind = r.getAttribute('data-kind');
        const k = OPT.kinds[KIND_FOR.it.kind];
        KIND_FOR.btn.innerHTML = `${esc(k.icon)} <span>${esc(k.label)}</span>${CHEV}`;
        closeSheet('pbKindSheet');
    });
    $id('pbTypeList').innerHTML = `<button type="button" class="dt-row" data-type=""><span class="dt-row-e">📌</span><span class="dt-row-body"><b>No type</b></span>
        <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>`
        + Object.entries(OPT.types).map(([k, v]) => `
        <button type="button" class="dt-row" data-type="${k}"><span class="dt-row-e">${TYPE_ICON[k] || '📌'}</span><span class="dt-row-body"><b>${esc(v)}</b></span>
        <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>`).join('');
    $id('pbtTypeBtn').addEventListener('click', () => {
        $id('pbTypeList').querySelectorAll('[data-type]').forEach((r) => r.classList.toggle('is-on', (r.getAttribute('data-type') || '') === (W.type || '')));
        openSheet('pbTypeSheet');
    });
    $id('pbTypeList').addEventListener('click', (e) => {
        const r = e.target.closest('[data-type]'); if (!r || !W) return;
        W.type = r.getAttribute('data-type') || null;
        paintType();
        closeSheet('pbTypeSheet');
    });
    $id('pbtPrio').addEventListener('click', (e) => { const b = e.target.closest('[data-prio]'); if (!b) return; W.priority = b.getAttribute('data-prio'); paintPrio(); });
    $id('pbtDay').addEventListener('input', paintStage);

    function openTask(id) {
        const t = id ? TASKS.find((x) => x.id === id) : null;
        W = t ? clone(t) : blankTask();
        W_ID = t ? t.id : null;
        $id('pbTaskTitle').textContent = t ? 'Edit task' : 'New task';
        $id('pbtDelete').hidden = !t;
        paintCounter($id('pbtCounter'), W.counter, (c) => { W.counter = c; paintStage(); });
        $id('pbtDay').value = W.day;
        $id('pbtTitleIn').value = W.title;
        $id('pbtSub').value = W.subtitle;
        $id('pbtDesc').value = W.description;
        $id('pbtNote').value = W.note;
        $id('pbtWorkers').value = W.workers ?? '';
        paintType(); paintStage(); paintPrio(); paintGroups();
        openSheet('pbTaskSheet');
        $id('pbTaskSheet').querySelector('.sheet-body').scrollTop = 0;
        if (!t && !window.matchMedia('(pointer: coarse)').matches) setTimeout(() => $id('pbtTitleIn').focus(), 280);
    }
    $id('pbAddTop').addEventListener('click', () => openTask(null));
    $id('pbAddBottom').addEventListener('click', () => openTask(null));
    $id('pbtSave').addEventListener('click', () => {
        const day = parseInt($id('pbtDay').value, 10);
        if (!Number.isFinite(day)) { toast('Which day of the count is it?', 'error'); $id('pbtDay').focus(); return; }
        W.day = Math.max(-365, Math.min(999, day));
        W.title = $id('pbtTitleIn').value.trim();
        if (!W.title) { toast('Give the task a title.', 'error'); $id('pbtTitleIn').focus(); return; }
        W.subtitle = $id('pbtSub').value.trim();
        W.description = $id('pbtDesc').value.trim();
        W.note = $id('pbtNote').value.trim();
        const w = $id('pbtWorkers').value.trim();
        W.workers = w === '' ? null : Math.max(0, Math.min(999, parseInt(w, 10) || 0));
        W.groups.forEach((g) => { g.title = (g.title || '').trim(); g.items = g.items.filter((it) => (it.name || '').trim() !== '').map((it) => ({ ...it, name: it.name.trim(), amount: (it.amount || '').trim() })); });
        W.groups = W.groups.filter((g) => g.title !== '' || g.items.length);
        const saved = clone(W);
        const isNew = W_ID === null;
        closeSheet('pbTaskSheet');
        commit(isNew ? 'Added' : 'Changed', () => {
            if (isNew) { TASKS.push(saved); }
            else { const i = TASKS.findIndex((x) => x.id === W_ID); if (i >= 0) TASKS[i] = saved; else TASKS.push(saved); }
            flash(saved.id);
        });
    });
    $id('pbtDelete').addEventListener('click', () => {
        const id = W_ID; if (!id) return;
        closeSheet('pbTaskSheet');
        const t = TASKS.find((x) => x.id === id);
        commit('Deleted', () => { TASKS = TASKS.filter((x) => x.id !== id); });
        toast(`Deleted "${t ? t.title : 'the task'}" — Undo brings it back.`);
    });

    /* ------------------------------------------------------------ dragging */
    /**
     * A handle-first sortable on pointer events: works the same for a
     * mouse and a finger, never fights the page scroll (the handle has
     * touch-action:none), and moves the real row live so the list IS the
     * drop preview. onDrop(el, fromIndex, toIndex) says where it landed.
     */
    function sortable(container, itemSel, handleSel, onDrop) {
        let src = null, ghost = null, offX = 0, offY = 0, from = -1, raf = null, lastY = 0, scroller = null;
        const items = () => Array.from(container.children).filter((el) => el.matches(itemSel));
        const scrollHost = () => { let n = container; while (n && n !== document.body) { const o = getComputedStyle(n).overflowY; if ((o === 'auto' || o === 'scroll') && n.scrollHeight > n.clientHeight) return n; n = n.parentElement; } return null; };
        const edge = () => {
            if (!src) return;
            const zone = 70, speed = 12;
            if (scroller) {
                const r = scroller.getBoundingClientRect();
                if (lastY < r.top + zone) scroller.scrollTop -= speed; else if (lastY > r.bottom - zone) scroller.scrollTop += speed;
            } else {
                if (lastY < zone) window.scrollBy(0, -speed); else if (lastY > window.innerHeight - zone) window.scrollBy(0, speed);
            }
            raf = requestAnimationFrame(edge);
        };
        const place = (y) => {
            const rows = items().filter((el) => el !== src);
            let before = null;
            for (const el of rows) { const r = el.getBoundingClientRect(); if (!r.height) continue; if (y < r.top + r.height / 2) { before = el; break; } }
            if (before) { if (before.previousElementSibling !== src) container.insertBefore(src, before); }
            else if (container.lastElementChild !== src) container.appendChild(src);
        };
        container.addEventListener('pointerdown', (e) => {
            const h = e.target.closest(handleSel); if (!h || !container.contains(h)) return;
            const el = h.closest(itemSel); if (!el || el.parentElement !== container) return;
            if (e.button && e.button !== 0) return;
            e.preventDefault();
            src = el; from = items().indexOf(el);
            const r = el.getBoundingClientRect();
            offX = e.clientX - r.left; offY = e.clientY - r.top;
            ghost = el.cloneNode(true); ghost.classList.add('pb-ghost'); ghost.classList.remove('dragging', 'is-open');
            ghost.querySelectorAll('[id]').forEach((n) => n.removeAttribute('id'));
            ghost.style.width = r.width + 'px'; ghost.style.transform = `translate(${r.left}px, ${r.top}px) scale(1.02)`;
            document.body.appendChild(ghost);
            el.classList.add('dragging');
            document.body.classList.add('pb-dragging');
            scroller = scrollHost();
            lastY = e.clientY;
            // Capture on the CONTAINER, never on the handle: the row is moved in the
            // DOM while dragging, and a moved element loses its capture.
            try { container.setPointerCapture(e.pointerId); } catch (_) {}
            raf = requestAnimationFrame(edge);
            const move = (ev) => {
                if (!src) return;
                lastY = ev.clientY;
                ghost.style.transform = `translate(${ev.clientX - offX}px, ${ev.clientY - offY}px) scale(1.02)`;
                place(ev.clientY);
            };
            const unhook = () => { document.removeEventListener('pointermove', move); document.removeEventListener('pointerup', end); document.removeEventListener('pointercancel', cancel); try { container.releasePointerCapture(e.pointerId); } catch (_) {} };
            const end = (ev) => {
                unhook();
                cancelAnimationFrame(raf);
                document.body.classList.remove('pb-dragging');
                if (ghost) ghost.remove(); ghost = null;
                const el2 = src; src = null;
                el2.classList.remove('dragging');
                const to = items().indexOf(el2);
                onDrop(el2, from, to);
            };
            const cancel = () => {
                unhook();
                cancelAnimationFrame(raf);
                document.body.classList.remove('pb-dragging');
                if (ghost) ghost.remove(); ghost = null;
                const el2 = src; src = null;
                el2.classList.remove('dragging');
                onDrop(el2, from, from, true);
            };
            document.addEventListener('pointermove', move);
            document.addEventListener('pointerup', end);
            document.addEventListener('pointercancel', cancel);
        });
        container.addEventListener('click', (e) => { if (e.target.closest(handleSel)) { e.preventDefault(); e.stopPropagation(); } }, true);
    }

    // The task list: the drop must keep the count in order, or the day changes.
    let FIX = null;
    sortable($id('pbList'), '.pb-card', '.pb-grip', (el, from, to, cancelled) => {
        if (cancelled || from === to) { render(); return; }
        const id = el.getAttribute('data-id');
        const t = TASKS.find((x) => x.id === id);
        if (!t) { render(); return; }
        const order = Array.from($id('pbList').children).map((c) => c.getAttribute('data-id'));
        const at = order.indexOf(id);
        const prev = at > 0 ? TASKS.find((x) => x.id === order[at - 1]) : null;
        const next = at < order.length - 1 ? TASKS.find((x) => x.id === order[at + 1]) : null;
        const k = keyOf(t);
        const okPrev = !prev || cmpDay(keyOf(prev), k) <= 0;
        const okNext = !next || cmpDay(k, keyOf(next)) <= 0;
        if (okPrev && okNext) {
            commit('Moved', () => { order.forEach((oid, i) => { const x = TASKS.find((y) => y.id === oid); if (x) x.pos = i * 10; }); flash(id); });
            return;
        }
        FIX = { id, order, prev, next };
        openFix(t, prev, next);
    });
    function sayKey(t) { return t ? `${t.counter} ${t.day}` : null; }
    function fixRangeOk(counter, day) {
        const k = [PHASE[counter] ?? 0, day];
        const okPrev = !FIX.prev || cmpDay(keyOf(FIX.prev), k) <= 0;
        const okNext = !FIX.next || cmpDay(k, keyOf(FIX.next)) <= 0;
        return okPrev && okNext;
    }
    function openFix(t, prev, next) {
        const between = prev && next ? `between <b>${esc(sayKey(prev))}</b> and <b>${esc(sayKey(next))}</b>` : (prev ? `after <b>${esc(sayKey(prev))}</b>` : `before <b>${esc(sayKey(next))}</b>`);
        $id('pbFixSay').innerHTML = `<b>${esc(t.title)}</b> is on <b>${esc(sayKey(t))}</b>, but you put it ${between}. Change its day to fit there, or put it back where it was.`;
        const start = prev || next;
        FIX.counter = start.counter;
        paintCounter($id('pbFixCounter'), FIX.counter, (c) => { FIX.counter = c; fixHint(); });
        $id('pbFixDay').value = start.day;
        fixHint();
        openSheet('pbFixSheet');
        setTimeout(() => { $id('pbFixDay').focus(); $id('pbFixDay').select(); }, 280);
    }
    function fixHint() {
        if (!FIX) return;
        const d = parseInt($id('pbFixDay').value, 10);
        const h = $id('pbFixHint');
        const lo = FIX.prev ? sayKey(FIX.prev) : null, hi = FIX.next ? sayKey(FIX.next) : null;
        const range = lo && hi ? `${lo} up to ${hi}` : (lo ? `${lo} or later` : `${hi} or earlier`);
        const ok = Number.isFinite(d) && fixRangeOk(FIX.counter, d);
        h.textContent = ok ? `Fits — ${range}.` : `It has to be ${range}.`;
        h.classList.toggle('is-bad', !ok);
        $id('pbFixGo').disabled = !ok;
    }
    $id('pbFixDay').addEventListener('input', fixHint);
    const fixBack = () => { closeSheet('pbFixSheet'); FIX = null; render(); };
    $id('pbFixBack').addEventListener('click', fixBack);
    $id('pbFixX').addEventListener('click', fixBack);
    $id('pbFixGo').addEventListener('click', () => {
        const d = parseInt($id('pbFixDay').value, 10);
        if (!FIX || !Number.isFinite(d) || !fixRangeOk(FIX.counter, d)) return;
        const { id, order, counter } = FIX;
        closeSheet('pbFixSheet'); FIX = null;
        commit('Moved', () => {
            const t = TASKS.find((x) => x.id === id);
            if (t) { t.counter = counter; t.day = d; }
            order.forEach((oid, i) => { const x = TASKS.find((y) => y.id === oid); if (x) x.pos = i * 10; });
            flash(id);
        });
        toast(`Moved to ${counter} ${d}.`);
    });

    // Groups and items inside the task sheet reorder freely.
    function wireGroupDrags() {
        const host = $id('pbGroups');
        if (!host._sortable) {
            host._sortable = true;
            sortable(host, '.pbg', '.pbg-grip', (el, from, to, cancelled) => {
                if (cancelled || from === to) return;
                const ids = Array.from(host.children).map((g) => g.getAttribute('data-g'));
                W.groups.sort((a, b) => ids.indexOf(a.id) - ids.indexOf(b.id));
            });
        }
        host.querySelectorAll('.pbg-items').forEach((list) => {
            if (list._sortable) return;
            list._sortable = true;
            sortable(list, '.pbi', '.pbi-grip', (el, from, to, cancelled) => {
                if (cancelled || from === to) return;
                const g = gOf(list); if (!g) return;
                const ids = Array.from(list.children).map((i) => i.getAttribute('data-i'));
                g.items.sort((a, b) => ids.indexOf(a.id) - ids.indexOf(b.id));
            });
        });
    }

    /* ------------------------------------------------------------ the head */
    const metaForm = window.pbHeadForm('pbm');
    $id('pbMetaBtn').addEventListener('click', () => { metaForm.fill(P); openSheet('pbMetaSheet'); });
    $id('pbMetaSave').addEventListener('click', async () => {
        const why = metaForm.check(); if (why) { toast(why, 'error'); return; }
        await flushSave();
        if (STALE) { toast('Reload the page first.', 'error'); return; }
        const btn = $id('pbMetaSave'); btn.disabled = true;
        try {
            const res = await api(U.base + '/meta', { method: 'POST', body: metaForm.read() });
            const p = res.data.protocol;
            P = { id: p.id, title: p.title, description: p.description, crop: p.crop, cropLabel: p.cropLabel, cropIcon: p.cropIcon, variety: p.variety, dayType: p.dayType, ported: p.ported };
            TASKS = p.tasks || []; REV = p.rev; STAGES = res.data.stages || {};
            sortTasks(); render();
            closeSheet('pbMetaSheet');
            toast('Saved.');
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    });
    $id('pbMetaDelete').addEventListener('click', async () => {
        const ok = await window.confirmAction({ title: 'Delete this protocol?', message: `"${P.title}" and its tasks will be removed from your list.`, confirmText: 'Delete' });
        if (!ok) return;
        try {
            await api(U.base + '/delete', { method: 'POST', body: {} });
            STALE = true; DIRTY = false;
            window.location.href = U.list;
        } catch (err) { toast(err.message, 'error'); }
    });

    /* ------------------------------------------------------------ port */
    const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    $id('pbPortBtn').addEventListener('click', () => {
        if (!TASKS.length) { toast('Add a task or two first — there is nothing to port yet.', 'error'); return; }
        const isDat = P.dayType === 'DAT';
        $id('pbPortSay').textContent = `A new cropping schedule with one lot and ${TASKS.length} ${TASKS.length === 1 ? 'activity' : 'activities'}, each on its own date counted from the start.`;
        $id('pbPortTitle').value = P.title;
        $id('pbPortStartL').textContent = P.dayType === 'DAP' ? 'Planting date (DAP 0)' : 'Sowing date (DAS 0)';
        const today = new Date(); today.setHours(0, 0, 0, 0);
        $id('pbPortStart').value = iso(today);
        $id('pbPortTransWrap').hidden = !isDat;
        if (isDat) {
            const das = TASKS.filter((t) => t.counter === 'DAS');
            const gap = das.length ? Math.max(...das.map((t) => t.day)) + 1 : 21;
            const tp = new Date(today); tp.setDate(tp.getDate() + Math.max(1, gap));
            $id('pbPortTrans').value = iso(tp);
            $id('pbPortTransHint').textContent = das.length ? `The seedbed tasks run to DAS ${gap - 1}, so the transplant is put the day after. Change it to suit.` : 'No seedbed tasks — three weeks after sowing is usual for rice. Change it to suit.';
        }
        $id('pbPortLot').value = (P.cropLabel || 'Main') + ' lot';
        openSheet('pbPortSheet');
    });
    $id('pbPortGo').addEventListener('click', async () => {
        const body = { title: $id('pbPortTitle').value.trim(), startDate: $id('pbPortStart').value, transplantDate: P.dayType === 'DAT' ? $id('pbPortTrans').value : null, lotName: $id('pbPortLot').value.trim(), lotSize: $id('pbPortSize').value, lotSizeUnit: $id('pbPortUnit').value };
        if (!body.title) { toast('Give the season a title.', 'error'); return; }
        if (!body.startDate) { toast('Pick the start date.', 'error'); return; }
        if (P.dayType === 'DAT' && !body.transplantDate) { toast('Pick the transplant date.', 'error'); return; }
        const btn = $id('pbPortGo'); btn.disabled = true;
        try {
            await flushSave();
            const res = await api(U.base + '/port', { method: 'POST', body });
            toast(res.message);
            DIRTY = false;
            window.location.href = res.data.redirect;
        } catch (err) { if (!err.tierLock) toast(err.message, 'error'); btn.disabled = false; }
    });

    /* ------------------------------------------------------------ Anee */
    function renderReview() {
        const host = $id('pbReview');
        if (!REVIEW) { host.innerHTML = ''; return; }
        const r = REVIEW;
        const ring = r.score >= 75 ? '#4a7c2a' : (r.score >= 50 ? '#c9902e' : '#b91c1c');
        const row = (cls, b, lines) => `<div class="pb-rv-row ${cls}"><b>${esc(b)}</b>${lines.filter(Boolean).map((l) => esc(l)).join('<br>')}</div>`;
        const additions = (r.additions || []).map((a, i) => `
            <div class="pb-rv-row is-add">
                <div class="grow"><span class="pb-tag"><b>${esc(a.counter)} ${a.day}</b></span> <b>${esc(a.title)}</b>${a.why ? esc(a.why) : ''}</div>
                <button type="button" class="pb-rv-add" data-add="${i}">+ Add</button>
            </div>`).join('');
        host.innerHTML = `
            <div class="card pb-review${host._folded ? ' is-folded' : ''}">
                <div class="pb-rv-head">
                    <div class="pb-ring" style="--p:${r.score};--ring:${ring}"><b>${r.score}</b><small>/100</small></div>
                    <div class="pb-rv-t"><b>${esc(r.verdict || "Anee's review")}</b><p>${esc(r.headline || '')}</p><small>Reviewed ${esc(REVIEW_AT || '')}${REVIEW_CREDITS ? ` · ${REVIEW_CREDITS} credits` : ''}</small></div>
                    <img class="pb-rv-face" src="${esc(OPT.aneeFace)}" alt="">
                </div>
                <div class="pb-rv-body">
                    ${(r.strengths || []).length ? `<p class="pb-rv-h">What is strong</p><div class="pb-rv-list">${r.strengths.map((s) => row('is-good', s.point, [s.why])).join('')}</div>` : ''}
                    ${(r.gaps || []).length ? `<p class="pb-rv-h">What is missing or thin</p><div class="pb-rv-list">${r.gaps.map((g) => row('is-gap', g.what, [g.why, g.fix ? 'Fix: ' + g.fix : ''])).join('')}</div>` : ''}
                    ${(r.risks || []).length ? `<p class="pb-rv-h">What could go wrong</p><div class="pb-rv-list">${r.risks.map((k) => row('is-risk', k.risk + (k.when ? ' · ' + k.when : ''), [k.action])).join('')}</div>` : ''}
                    ${(r.additions || []).length ? `<p class="pb-rv-h">Anee would add</p><div class="pb-rv-list">${additions}</div>` : ''}
                    ${r.sequence ? `<p class="pb-rv-h">Order and spacing</p><p class="pb-rv-p">${esc(r.sequence)}</p>` : ''}
                    ${r.summary ? `<p class="pb-rv-h">In short</p><p class="pb-rv-p">${esc(r.summary)}</p>` : ''}
                </div>
                <div class="pb-rv-foot">
                    <button type="button" class="pb-rv-again" id="pbAgain">Review again</button>
                    <button type="button" class="pb-rv-fold" id="pbFold">${host._folded ? 'Show the review' : 'Hide the review'}</button>
                </div>
            </div>`;
    }
    $id('pbReview').addEventListener('click', (e) => {
        const host = $id('pbReview');
        if (e.target.closest('#pbFold')) { host._folded = !host._folded; renderReview(); return; }
        if (e.target.closest('#pbAgain')) { askAnee(); return; }
        const add = e.target.closest('[data-add]');
        if (add && REVIEW && editing()) {
            const a = REVIEW.additions[+add.getAttribute('data-add')];
            if (!a) return;
            const counter = counters().includes(a.counter) ? a.counter : counters()[0];
            const t = { ...blankTask(), counter, day: a.day, title: a.title || 'Suggested task', type: OPT.types[a.type] ? a.type : null, note: a.why ? 'Anee: ' + a.why : '' };
            commit('Added', () => { TASKS.push(t); flash(t.id); });
            toast(`Added "${t.title}" on ${counter} ${a.day}.`);
        }
    });
    function askAnee() {
        if (OPT.aiLocked) { window.aneeUpgrade("Anee's review of your protocol comes with Libre + Anee.", 'libreAnee'); return; }
        if (!OPT.canAnalyze) { toast('Anee is not available right now.', 'error'); return; }
        if (!TASKS.length) { toast('Add a task or two first — there is nothing to review yet.', 'error'); return; }
        $id('pbAskQuote').innerHTML = `This review spends <b>${OPT.quote} credits</b>, and you have ${window.creditCoin(OPT.unlimited ? '∞' : Number(OPT.balance).toLocaleString())}. Nothing is charged until you press Review.`;
        openSheet('pbAskSheet');
    }
    $id('pbAneeBtn').addEventListener('click', askAnee);
    $id('pbAskGo').addEventListener('click', async () => {
        closeSheet('pbAskSheet');
        await flushSave();
        if (STALE) { toast('Reload the page first.', 'error'); return; }
        window.aneeWait.show({ title: 'Anee is reading your protocol…', lines: ['Reading every task against the growth stages', 'Checking the days and the order', 'Weighing the products and the rates', 'Looking for what is missing', 'Writing the review'], sub: 'About a minute.' });
        let landed = false;
        try {
            const res = await api(U.base + '/analyze', { method: 'POST', body: {} });
            let data = res.data;
            if (data.pending) data = await window.aneeWait.poll({ id: P.id, job: U.job, phases: window.aneeWait.phases.plain, limit: 120 });
            landed = true;
            REVIEW = data.analysis; REVIEW_AT = data.analysisAt; REVIEW_CREDITS = data.charged || 0;
            if (typeof data.balance === 'number') OPT.balance = data.balance;
            $id('pbReview')._folded = false;
            render();
            await window.aneeWait.done({ title: 'Done!', line: `${data.charged} credits used — the review is kept with the protocol.` });
            $id('pbReview').scrollIntoView({ block: 'start', behavior: 'smooth' });
        } catch (err) {
            if (err.data && err.data.outOfCredits) {
                window.noticeSheet({ title: 'Not enough credits', message: err.message, detail: 'Top up on My Credits and come back.', okText: 'OK' });
            } else if (!err.tierLock) toast(err.message, 'error');
        } finally { if (!landed) window.aneeWait.fail(); }
    });

    /* ------------------------------------------------------------ boot */
    sortTasks();
    render();
    // A review left mid-way by a closed tab: finish waiting for it.
    if (BOOT.analysisStatus === 'pending') {
        (async () => {
            try {
                const data = await window.aneeWait.poll({ id: P.id, job: U.job, phases: window.aneeWait.phases.plain, limit: 100 });
                REVIEW = data.analysis; REVIEW_AT = data.analysisAt; REVIEW_CREDITS = data.charged || 0; render();
                toast("Anee's review is in.");
            } catch (_) {}
        })();
    }
})();
</script>
@endsection
