@extends('layouts.app')

@section('title', $protocol['title'])
@section('page-title', $protocol['title'])
@section('page-subtitle', ($protocol['cropLabel'] ?: 'No crop yet') . ' · ' . ($options['dayTypes'][$protocol['dayType']]['label'] ?? $protocol['dayType']))
@section('back', \App\Support\BackTo::key() === 'tags' ? route('tags.global') : \App\Support\BackTo::carry(route('pb.page')))

@push('head')
<style>
    .pb-page { padding-bottom: 1.5rem; }
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
    /* The head's corner: Anee's review, then the pencil for the details. */
    .pb-head-acts { flex: none; display: flex; align-items: center; gap: .35rem; }
    .pb-anee { display: inline-flex; align-items: center; gap: .35rem; height: 2.1rem; padding: 0 .65rem 0 .2rem; border-radius: 999px; font-size: .74rem; font-weight: 800; white-space: nowrap;
        color: #2f5219; background: #f1f8ea; border: 1px solid #cfe3bd;
        transition: transform .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .pb-anee img { width: 1.65rem; height: 1.65rem; border-radius: 999px; object-fit: cover; flex: none; }
    .pb-anee:hover { background: #e4f1d6; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(61,104,35,.15); }
    html.dark .pb-anee { background: #22301a; border-color: #3f5a2a; color: #cfe6b8; }
    html.dark .pb-anee:hover { background: #2a3b20; }
    @media (prefers-reduced-motion: reduce) { .pb-anee { transition: none; } .pb-anee:hover { transform: none; } }
    html.dark .pb-head-e { background: #22301a; }
    html.dark .pb-head-t b { color: #e8efe1; }
    html.dark .pb-head-p { color: #cbd5c0; }
    html.dark .pb-tag { background: #151b12; border-color: #2b3a1c; color: #a5b89a; }
    html.dark .pb-tag b { color: #e8efe1; }
    html.dark .pb-tag.is-score { background: #22301a; border-color: #3f5a2a; color: #cfe6b8; }
    html.dark .pb-tag.is-ported { background: #1d2440; border-color: #33417a; color: #b9c6f5; }
    html.dark .pb-pen { background: #151b12; border-color: #2b3a1c; color: #a5b89a; }

    /* The editing tools: undo, redo, the saved word, add. */
    .pb-tools { display: flex; align-items: center; gap: .4rem; margin-bottom: .75rem; position: sticky; top: calc(3.6rem + env(safe-area-inset-top, 0px)); z-index: 5;
        padding: .45rem .5rem; border-radius: .9rem; background: rgba(255,255,255,.92); border: 1px solid var(--color-gray-200); backdrop-filter: blur(6px); }
    .pb-tool { display: inline-flex; align-items: center; gap: .3rem; padding: .42rem .55rem; border-radius: .7rem; font-size: .76rem; font-weight: 800; white-space: nowrap; color: var(--color-gray-700); border: 1px solid var(--color-gray-200); background: var(--color-white);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .pb-tool svg { width: 1rem; height: 1rem; }
    .pb-tool-w { display: none; }
    @media (max-width: 479px) { .pb-tools { gap: .3rem; padding: .4rem .4rem; } .pb-tool { padding: .42rem .45rem; gap: .25rem; } }
    @media (min-width: 480px) { .pb-tool-w { display: inline; } }
    .pb-tool.is-div { color: #92400e; border-color: #fcd34d; }
    .pb-tool.is-div:not(:disabled):hover { background: #fffbeb; border-color: #f59e0b; }
    html.dark .pb-tool.is-div { color: #f0d9a8; border-color: #6b4f16; }
    .pb-tool:disabled { opacity: .38; cursor: default; }
    .pb-tool:not(:disabled):hover { background: var(--color-brand-50); border-color: var(--color-brand-300); }
    .pb-tool.is-add { background: #3d6823; color: #fff; border-color: #3d6823; }
    .pb-tool.is-add:not(:disabled):hover { background: #2f5219; border-color: #2f5219; }
    .pb-save { font-size: .68rem; font-weight: 700; color: var(--color-gray-400); padding: 0 .2rem; min-width: 0; flex: 1 1 auto; text-align: center; transition: color .28s cubic-bezier(.22,1,.36,1); white-space: nowrap; }
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
    .pb-body-t { display: flex; align-items: flex-start; gap: .5rem; }
    .pb-body-t > span { flex: 1 1 auto; min-width: 0; }
    .pb-body-t b { display: block; font-size: .95rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.3; }
    .pb-chev { display: inline-flex; flex: none; width: 1.9rem; height: 1.9rem; cursor: pointer; border-radius: 999px; align-items: center; justify-content: center; color: var(--color-gray-400); background: var(--color-gray-50); border: 1px solid var(--color-gray-200); margin-top: -.1rem;
        transition: transform .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .pb-chev svg { width: .95rem; height: .95rem; }
    .pb-chev:hover { background: #f1f8ea; color: #3d6823; }
    .pb-card.is-open .pb-chev { transform: rotate(180deg); background: #f1f8ea; color: #3d6823; border-color: #cfe3bd; }
    html.dark .pb-chev { background: #1c2416; border-color: #2b3a1c; color: #a5b89a; }
    html.dark .pb-card.is-open .pb-chev { background: #22301a; color: #cfe6b8; border-color: #3f5a2a; }
    .pb-body-t i { display: block; font-style: normal; font-size: .78rem; color: var(--color-gray-500); margin-top: .1rem; }
    .pb-chips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .45rem; }
    .pb-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .16rem .5rem; border-radius: 999px; font-size: .68rem; font-weight: 700; border: 1px solid var(--color-gray-200); color: var(--color-gray-600); background: var(--color-gray-50); }
    .pb-chip.is-anee-good { border-color: #cfe3bd; background: #f1f8ea; color: #2f5219; }
    .pb-chip.is-anee-check { border-color: #f3d9a4; background: #fdf6e6; color: #92400e; }
    .pb-chip.is-anee-concern { border-color: #f5c2c2; background: #fdecec; color: #991b1b; }
    .pb-more { display: grid; grid-template-rows: 0fr; opacity: 0; font-size: .82rem; color: var(--color-gray-700); line-height: 1.5;
        transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1), margin-top .28s cubic-bezier(.22,1,.36,1), padding-top .28s cubic-bezier(.22,1,.36,1); margin-top: 0; padding-top: 0; border-top: 1px dashed transparent; }
    .pb-more-in { min-height: 0; overflow: hidden; }
    .pb-card.is-open .pb-more { grid-template-rows: 1fr; opacity: 1; margin-top: .6rem; padding-top: .6rem; border-top-color: var(--color-gray-200); }
    @media (prefers-reduced-motion: reduce) { .pb-more, .pb-chev { transition: none; } }
    .pb-more p { margin: 0 0 .4rem; white-space: pre-wrap; }
    .pb-more .pb-g { margin: .35rem 0; }
    .pb-more .pb-g b { display: block; font-size: .76rem; color: var(--color-gray-900); }
    .pb-more .pb-g b em { font-style: normal; font-weight: 600; color: #3d6823; margin-left: .3rem; }
    .pb-more ul { margin: .15rem 0 0; padding: 0 0 0 1.05rem; }
    .pb-more li { margin: .1rem 0; }
    .pb-more li small { color: var(--color-gray-400); }
    .pb-more .pb-note { margin-top: .4rem; padding: .5rem .65rem; border-radius: .6rem; background: #fdf6e6; color: #713f12; font-size: .78rem; }
    .pb-more .pb-anee { margin-top: .4rem; padding: .5rem .65rem; border-radius: .6rem; background: #f1f8ea; color: #2f5219; font-size: .78rem; }
    .pb-acts { flex: none; display: flex; flex-direction: column; justify-content: center; gap: .15rem; padding: .3rem .35rem .3rem 0; }
    .pb-grip, .pb-menu { width: 2rem; height: 2rem; border-radius: .6rem; display: inline-flex; align-items: center; justify-content: center; color: var(--color-gray-400); }
    .pb-grip { cursor: grab; touch-action: none; }
    .pb-grip:active { cursor: grabbing; }
    .pb-grip:hover, .pb-menu:hover { background: var(--color-gray-100); color: var(--color-gray-700); }
    .pb-grip svg, .pb-menu svg { width: 1.15rem; height: 1.15rem; }
    .pb-ghost { position: fixed; top: 0; left: 0; z-index: 90; margin: 0; pointer-events: none; opacity: .78; box-shadow: 0 18px 40px rgba(15,23,42,.3); transform-origin: top left; will-change: transform; transition: none !important; }
    body.pb-dragging { user-select: none; -webkit-user-select: none; -webkit-touch-callout: none; overscroll-behavior: contain; }
    .pb-warn { border-radius: 1rem; border: 1px solid #f3d9a4; background: linear-gradient(135deg, #fffbf0, #fff7e6); margin-bottom: .75rem; overflow: hidden; }
    .pb-warn-h { display: flex; align-items: center; gap: .55rem; width: 100%; text-align: left; padding: .65rem .9rem; cursor: pointer; }
    .pb-warn-e { font-size: 1.05rem; }
    .pb-warn-t { flex: 1 1 auto; font-size: .86rem; font-weight: 800; color: #92400e; }
    .pb-warn-c { width: 1rem; height: 1rem; color: #b45309; opacity: .7; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .pb-warn.is-folded .pb-warn-c { transform: rotate(-90deg); }
    .pb-warn-body { display: grid; grid-template-rows: 1fr; opacity: 1; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .pb-warn.is-folded .pb-warn-body { grid-template-rows: 0fr; opacity: 0; }
    .pb-warn-in { min-height: 0; overflow: hidden; display: grid; gap: .4rem; padding: 0 .9rem .8rem; }
    .pb-warn-row { padding: .5rem .65rem; border-radius: .7rem; background: rgb(255 255 255 / .7); border: 1px solid #f3d9a4; font-size: .78rem; line-height: 1.5; color: #713f12; cursor: pointer; }
    .pb-warn-row b { display: block; color: #92400e; margin-bottom: .1rem; }
    .pb-chip.is-warn { border-color: #f3d9a4; background: #fdf6e6; color: #92400e; }
    .pb-more .pb-warnnote { margin-top: .4rem; padding: .5rem .65rem; border-radius: .6rem; background: #fdf6e6; border: 1px solid #f3d9a4; color: #713f12; font-size: .78rem; }
    .pbt-warn { display: grid; gap: .3rem; margin-top: .5rem; }
    .pbt-warn span { display: block; padding: .45rem .6rem; border-radius: .6rem; background: #fdf6e6; border: 1px solid #f3d9a4; color: #713f12; font-size: .76rem; line-height: 1.45; }
    html.dark .pb-warn { background: linear-gradient(135deg, #262012, #2a2210); border-color: #6b4f16; }
    html.dark .pb-warn-t { color: #f0d9a8; }
    html.dark .pb-warn-row { background: rgb(0 0 0 / .2); border-color: #6b4f16; color: #f0d9a8; }
    html.dark .pb-warn-row b { color: #fcd9a0; }
    html.dark .pb-chip.is-warn, html.dark .pb-more .pb-warnnote, html.dark .pbt-warn span { background: #2a2210; border-color: #6b4f16; color: #f0d9a8; }
    @media (prefers-reduced-motion: reduce) { .pb-warn-body, .pb-warn-c { transition: none; } }
    .pb-note { display: flex; align-items: stretch; border-radius: 1rem; background: #fff9db; border: 1px solid #f1e3a0; overflow: hidden; position: relative;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .pb-note.dragging { opacity: .32; outline: 2px dashed #d4b85a; outline-offset: -2px; }
    .pb-note.just-moved { box-shadow: 0 0 0 3px rgba(212,184,90,.4); }
    .pb-note-rail { flex: none; width: 4.4rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .15rem; padding: .6rem .3rem; background: #f6e7a4; color: #7a5a06; }
    .pb-note-rail svg { width: 1.3rem; height: 1.3rem; }
    .pb-note-rail b { font-size: .58rem; letter-spacing: .06em; text-transform: uppercase; }
    .pb-note-body { flex: 1 1 auto; min-width: 0; padding: .7rem .8rem; font-size: .86rem; line-height: 1.5; color: #4a3a05; white-space: pre-wrap; cursor: pointer; }
    html.dark .pb-note { background: #2a2410; border-color: #4c4018; }
    html.dark .pb-note-rail { background: #3a3114; color: #f0d68a; }
    html.dark .pb-note-body { color: #f2e6bf; }
    .pb-add-row { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem; margin-top: .7rem; }
    .pb-add-row .pb-add-bottom { padding: .75rem .3rem; font-size: .76rem; gap: .3rem; white-space: nowrap; }
    .pb-add-row .pb-add-bottom.is-div { border-color: #f5c56b; color: #92400e; }
    .pb-add-row .pb-add-bottom.is-div:hover { background: #fffbeb; }
    html.dark .pb-add-row .pb-add-bottom.is-div { border-color: #6b4f16; color: #f0d9a8; }
    html.dark .pb-add-row .pb-add-bottom.is-div:hover { background: #262012; }
    .pb-add-row .pb-add-bottom { margin-top: 0; display: flex; }
    .pb-add-row .pb-add-bottom.is-note { border-color: #e2c86a; color: #7a5a06; }
    .pb-add-row .pb-add-bottom.is-note:hover { background: #fff9db; }
    html.dark .pb-add-row .pb-add-bottom.is-note { border-color: #6b5a1f; color: #f0d68a; }
    html.dark .pb-add-row .pb-add-bottom.is-note:hover { background: #2a2410; }
    .pb-add-bottom { display: flex; align-items: center; justify-content: center; gap: .4rem; width: 100%; margin-top: .7rem; padding: .8rem; border-radius: 1rem; border: 1.5px dashed #b9c6a8; color: #3d6823; font-weight: 800; font-size: .86rem; background: transparent;
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .pb-add-bottom:hover { background: #f1f8ea; }
    .pb-add-bottom svg { width: 1rem; height: 1rem; }
    html.dark .pb-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .pb-card:hover { border-color: #3f5a2a; }
    html.dark .pb-body-t b { color: #e8efe1; }
    html.dark .pb-chip { background: #1c2416; border-color: #2b3a1c; color: #a5b89a; }
    html.dark .pb-more { color: #cbd5c0; }
    html.dark .pb-card.is-open .pb-more { border-top-color: #2b3a1c; }
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
    .pb-rv-add { flex: none; padding: .35rem .6rem; border-radius: .6rem; font-size: .72rem; font-weight: 800; color: #fff; background: #3d6823; display: inline-flex; }
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

    /* A phase divider: the activities board's "resume here" line — a dashed
       rule with its bookmark on it — carrying a phase's name instead. */
    .pb-card.pb-div { display: block; background: transparent; border: 0; border-radius: 0; overflow: visible; padding: .25rem 0 .1rem; }
    .pb-card.pb-div:hover { border-color: transparent; }
    .pb-div { --dv: #f59e0b; --dv-bg: #fffbeb; --dv-bd: #fcd34d; --dv-tx: #92400e; }
    .pb-div[data-color="green"] { --dv: #6b9f3d; --dv-bg: #f1f8ea; --dv-bd: #cfe3bd; --dv-tx: #2f5219; }
    .pb-div[data-color="sky"] { --dv: #0ea5e9; --dv-bg: #f0f9ff; --dv-bd: #bae6fd; --dv-tx: #075985; }
    .pb-div[data-color="violet"] { --dv: #8b5cf6; --dv-bg: #f5f3ff; --dv-bd: #ddd6fe; --dv-tx: #5b21b6; }
    .pb-div[data-color="rose"] { --dv: #f43f5e; --dv-bg: #fff1f2; --dv-bd: #fecdd3; --dv-tx: #9f1239; }
    .pb-div[data-color="slate"] { --dv: #64748b; --dv-bg: #f8fafc; --dv-bd: #cbd5e1; --dv-tx: #334155; }
    .pb-div-line { display: flex; align-items: center; justify-content: space-between; gap: .5rem; border-top: 2px dashed var(--dv); padding-top: .45rem;
        transition: border-color .28s cubic-bezier(.22,1,.36,1); }
    .pb-div-pill { display: inline-flex; align-items: center; gap: .35rem; min-width: 0; max-width: 100%; background: var(--dv-bg); border: 1px solid var(--dv-bd); color: var(--dv-tx);
        font-size: .8rem; font-weight: 800; border-radius: 999px; padding: .22rem .75rem .22rem .6rem; cursor: pointer; text-align: left;
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .pb-div-pill svg { width: .95rem; height: .95rem; flex: none; }
    .pb-div-pill span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pb-div-pill small { flex: none; font-size: .68rem; font-weight: 700; opacity: .75; }
    .pb-div .pb-acts { flex-direction: row; padding: 0; }
    .pb-card.pb-div.just-moved { box-shadow: none; }
    .pb-div.dragging { opacity: .32; outline: 2px dashed var(--dv-bd); outline-offset: 2px; }
    .pb-div.just-moved .pb-div-pill { box-shadow: 0 0 0 3px color-mix(in srgb, var(--dv) 35%, transparent); }
    html.dark .pb-div { --dv-bg: color-mix(in srgb, var(--dv) 16%, #151b12); --dv-bd: color-mix(in srgb, var(--dv) 45%, #151b12); --dv-tx: color-mix(in srgb, var(--dv) 45%, #f5f5f4); }
    .pbd-colors { display: flex; flex-wrap: wrap; gap: .5rem; }
    .pbd-color { width: 2.2rem; height: 2.2rem; border-radius: 999px; border: 2px solid transparent; background: var(--sw); display: inline-flex; align-items: center; justify-content: center; color: #fff;
        box-shadow: inset 0 0 0 2px rgba(255,255,255,.55); transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .pbd-color svg { width: 1rem; height: 1rem; opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .pbd-color.is-on { border-color: var(--color-gray-900); transform: scale(1.08); }
    .pbd-color.is-on svg { opacity: 1; }
    html.dark .pbd-color.is-on { border-color: #e8efe1; }
    .pbd-preview { margin-top: .9rem; }
    @media (prefers-reduced-motion: reduce) { .pb-div-line, .pb-div-pill, .pbd-color, .pbd-color svg { transition: none; } }

    /* Every field of an item says what it is. */
    .pbi-f { display: block; min-width: 0; flex: 1 1 auto; }
    .pbi-l { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); margin: 0 0 .15rem .1rem; }
    html.dark .pbi-l { color: #9aa78d; }
    .pbi > .pbi-f { margin-left: 2.25rem; width: calc(100% - 2.25rem); }
    .pbi-row { align-items: flex-end !important; }
    .pbi-row .pb-mini { margin-bottom: .15rem; }

    /* The task sheet. */
    .pbt-when { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .4rem; margin-bottom: .5rem; }
    .pbt-when button { padding: .5rem .5rem; border-radius: .7rem; font-size: .78rem; font-weight: 800; border: 1px solid var(--color-gray-200); background: var(--color-white); color: var(--color-gray-600); }
    .pbt-when button.is-on { background: #3d6823; color: #fff; border-color: #3d6823; }
    .pbt-day { display: flex; gap: .5rem; align-items: stretch; }
    .pbt-unit { display: inline-flex; align-items: center; padding: 0 .6rem; border-radius: .75rem; font-size: .76rem; font-weight: 800; color: #2f5219; background: #f1f8ea; border: 1px solid #cfe3bd; white-space: nowrap; flex: none; }
    html.dark .pbt-when button { background: #151b12; border-color: #2b3a1c; color: #cbd5c0; }
    html.dark .pbt-when button.is-on { background: #3d6823; color: #fff; border-color: #3d6823; }
    html.dark .pbt-unit { background: #22301a; border-color: #3f5a2a; color: #cfe6b8; }
    .pb-day.is-before { background: repeating-linear-gradient(135deg, var(--prio, #6b7280) 0 6px, color-mix(in srgb, var(--prio, #6b7280) 78%, #000) 6px 12px); }
    .pb-day.is-before b { font-size: .52rem; }
    .pbt-counter { display: inline-flex; border: 1px solid var(--color-gray-200); border-radius: .75rem; overflow: hidden; flex: none; }
    .pbt-counter button { padding: 0 .8rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-500); background: var(--color-white); }
    .pbt-counter button.is-on { background: #3d6823; color: #fff; }
    .pbt-day .form-input { flex: 1 1 auto; min-width: 0; font-weight: 800; font-variant-numeric: tabular-nums; }
    .pbt-stage { font-size: .74rem; color: #3d6823; font-weight: 700; margin-top: .3rem; min-height: 1rem; }
    .pbt-sec { margin-top: 1.1rem; }
    .pbt-sec-h { margin-bottom: .1rem; }
    .pbt-sec-h .form-label { margin: 0; }
    .pbt-sec-p { margin: 0 0 .55rem; }
    .pbt-day .crop-tag { flex: 1 1 auto; min-width: 0; }
    .pbt-day .form-input { flex: 0 0 5rem; text-align: center; }
    .pbg { border: 1px solid var(--color-gray-200); border-radius: .9rem; padding: .6rem .6rem .5rem; margin-bottom: .55rem; background: var(--color-gray-50); position: relative; }
    .pbg.dragging { opacity: .32; outline: 2px dashed #b9c6a8; }
    .pbg-head { display: flex; align-items: center; gap: .4rem; }
    .pbg-head .crop-tag { flex: 1 1 auto; min-width: 0; }
    .pbg-items { display: flex; flex-direction: column; gap: .35rem; margin-top: .45rem; }
    .pbi { display: grid; gap: .35rem; padding: .4rem .4rem .45rem; border-radius: .7rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .pbi-row { display: flex; align-items: center; gap: .35rem; }
    .pbi-row .form-input { flex: 1 1 auto; min-width: 0; }
    .pbg-custom { margin-top: .6rem; display: grid; gap: .5rem; }
    .pbi.dragging { opacity: .32; outline: 2px dashed #b9c6a8; }
    .pbi .crop-tag { padding: .42rem .6rem; }
    .pbi .crop-tag-t { font-size: .82rem; }
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
    @media (prefers-reduced-motion: reduce) { .pb-card, .pb-tool, .pb-add-bottom, .pb-save { transition: none; } .pb-card.is-new { animation: none; } }
    /* The version in use, under the head's title. */
    .pb-head-ver { margin-top: .7rem; }
    .pb-ver { display: inline-flex; align-items: center; gap: .45rem; max-width: 100%; padding: .32rem .55rem .32rem .45rem; border-radius: .8rem; font-size: .78rem; font-weight: 700; color: var(--color-gray-600);
        background: var(--color-gray-50); border: 1px solid var(--color-gray-200); transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .pb-ver:hover { background: #f1f8ea; border-color: #cfe3bd; }
    .pb-ver-e { font-size: .95rem; flex: none; }
    .pb-ver b { color: #2f5219; font-weight: 800; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pb-ver small { flex: none; font-size: .68rem; font-weight: 700; color: var(--color-gray-400); }
    .pb-ver svg { width: .9rem; height: .9rem; flex: none; color: var(--color-gray-400); }
    .pb-ver.is-glow { box-shadow: 0 0 0 3px rgba(107,159,61,.35); }
    html.dark .pb-ver { background: #1c2416; border-color: #2b3a1c; color: #a5b89a; }
    html.dark .pb-ver b { color: #cfe6b8; }
    .pbv-row { display: flex; align-items: center; gap: .25rem; }
    .pbv-row .dt-row { flex: 1 1 auto; min-width: 0; }
    .pbv-row .pb-mini { width: 2.2rem; height: 2.2rem; }

    /* The three tabs: tasks, materials, rules & notes. */
    .pb-tabs { position: relative; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0; padding: .25rem; margin-bottom: .75rem; border-radius: .95rem;
        background: var(--color-gray-100); border: 1px solid var(--color-gray-200); }
    .pb-tab-ind { position: absolute; top: .25rem; bottom: .25rem; left: .25rem; width: calc((100% - .5rem) / 3); border-radius: .75rem; background: var(--color-white);
        box-shadow: 0 1px 3px rgba(15,23,42,.12); transform: translateX(calc(var(--i, 0) * 100%)); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .pb-tab { position: relative; z-index: 1; display: inline-flex; align-items: center; justify-content: center; gap: .3rem; padding: .55rem .3rem; border-radius: .75rem; font-size: .8rem; font-weight: 800;
        color: var(--color-gray-500); white-space: nowrap; transition: color .28s cubic-bezier(.22,1,.36,1); }
    .pb-tab[aria-selected="true"] { color: #2f5219; }
    .pb-tab em { font-style: normal; font-size: .66rem; font-weight: 800; min-width: 1.2rem; padding: .05rem .35rem; border-radius: 999px; background: var(--color-gray-200); color: var(--color-gray-600); }
    .pb-tab[aria-selected="true"] em { background: #e4f1d6; color: #2f5219; }
    .pb-tab em.is-short { background: #fdecec; color: #b91c1c; }
    .pb-tab em:empty { display: none; }
    @media (max-width: 479px) { .pb-tab { font-size: .76rem; gap: .25rem; } .pb-tab-e { display: none; } }
    html.dark .pb-tabs { background: #10150d; border-color: #2b3a1c; }
    html.dark .pb-tab-ind { background: #22301a; box-shadow: none; }
    html.dark .pb-tab { color: #9aa78d; }
    html.dark .pb-tab[aria-selected="true"] { color: #cfe6b8; }
    html.dark .pb-tab em { background: #2b3a1c; color: #cbd5c0; }
    html.dark .pb-tab[aria-selected="true"] em { background: #3f5a2a; color: #e8efe1; }
    .pb-panel[hidden] { display: none !important; }
    .pb-panel.is-entering { animation: pbPanelIn .28s cubic-bezier(.22,1,.36,1); }
    @keyframes pbPanelIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    #pbPage[data-tab="materials"] .pb-tools [data-for="tasks"], #pbPage[data-tab="tasks"] .pb-tools [data-for="materials"] { display: none; }
    #pbPage[data-tab="rules"] .pb-tools { display: none; }
    @media (prefers-reduced-motion: reduce) { .pb-tab-ind, .pb-tab, .pb-ver { transition: none; } .pb-panel.is-entering { animation: none; } }

    /* The materials: what there is, what the tasks plan, what is left. */
    .pbm-sum { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem; margin-bottom: .7rem; }
    .pbm-sum > div { padding: .55rem .65rem; border-radius: .85rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .pbm-sum b { display: block; font-size: 1.1rem; font-weight: 900; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .pbm-sum span { font-size: .68rem; font-weight: 700; color: var(--color-gray-500); }
    .pbm-sum .is-short { border-color: #f5c2c2; background: #fff7f7; }
    .pbm-sum .is-short b { color: #b91c1c; }
    .pbm-list { display: flex; flex-direction: column; gap: .55rem; }
    .pbm-card { display: flex; align-items: stretch; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); position: relative;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .pbm-card:hover { border-color: #cfe3bd; }
    .pbm-card.is-short { border-color: #f5c2c2; background: linear-gradient(135deg, #fff, #fff6f6); }
    .pbm-card.dragging { opacity: .32; outline: 2px dashed #b9c6a8; outline-offset: -2px; }
    .pbm-card.just-moved { box-shadow: 0 0 0 3px rgba(107,159,61,.35); }
    .pbm-card.is-new { animation: pbIn .32s cubic-bezier(.22,1,.36,1); }
    .pbm-e { flex: none; width: 3.2rem; display: flex; align-items: flex-start; justify-content: center; padding-top: .8rem; font-size: 1.35rem; }
    .pbm-body { flex: 1 1 auto; min-width: 0; padding: .7rem .2rem .7rem 0; cursor: pointer; }
    .pbm-t b { display: block; font-size: .93rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.3; }
    .pbm-t i { display: block; font-style: normal; font-size: .74rem; color: var(--color-gray-500); margin-top: .05rem; }
    .pbm-nums { display: flex; flex-wrap: wrap; gap: .25rem .8rem; margin-top: .45rem; font-size: .74rem; color: var(--color-gray-500); }
    .pbm-nums b { color: var(--color-gray-900); font-weight: 800; font-variant-numeric: tabular-nums; }
    .pbm-nums .is-left b { color: #2f5219; }
    .pbm-card.is-short .pbm-nums .is-left b { color: #b91c1c; }
    .pbm-bar { height: .4rem; border-radius: 999px; background: var(--color-gray-100); margin-top: .4rem; overflow: hidden; }
    .pbm-bar span { display: block; height: 100%; width: 0; border-radius: 999px; background: #6b9f3d; transition: width .5s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .pbm-card.is-short .pbm-bar span { background: #dc2626; }
    .pbm-warn { margin-top: .45rem; padding: .4rem .6rem; border-radius: .6rem; background: #fdecec; border: 1px solid #f5c2c2; color: #991b1b; font-size: .76rem; font-weight: 700; line-height: 1.4; }
    .pbm-uses { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .45rem; }
    .pbm-uses .pb-chip { background: var(--color-white); }
    .pbm-none { font-size: .74rem; color: var(--color-gray-400); margin-top: .4rem; }
    html.dark .pbm-sum > div, html.dark .pbm-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .pbm-sum b, html.dark .pbm-t b, html.dark .pbm-nums b { color: #e8efe1; }
    html.dark .pbm-sum .is-short, html.dark .pbm-card.is-short { background: #2a1717; border-color: #6b2b2b; }
    html.dark .pbm-bar { background: #22301a; }
    html.dark .pbm-warn { background: #2a1717; border-color: #6b2b2b; color: #fecaca; }
    html.dark .pbm-nums .is-left b { color: #cfe6b8; }
    html.dark .pbm-uses .pb-chip { background: #1c2416; }
    @media (prefers-reduced-motion: reduce) { .pbm-card, .pbm-bar span { transition: none; } .pbm-card.is-new { animation: none; } }

    /* An item that draws from the materials. */
    .pbi-mat { font-size: .72rem; color: var(--color-gray-500); margin: -.1rem 0 0 2.25rem; }
    .pbi-qrow { display: flex; gap: .4rem; align-items: stretch; }
    .pbi-qrow .form-input { flex: 1 1 auto; min-width: 0; font-weight: 800; font-variant-numeric: tabular-nums; }
    .pbi-left { margin: 0 0 0 2.25rem; font-size: .74rem; font-weight: 700; color: #3d6823; line-height: 1.4; transition: color .28s cubic-bezier(.22,1,.36,1); }
    .pbi-left.is-short { color: #b91c1c; }
    .pbi-left.is-quiet { color: var(--color-gray-500); font-weight: 600; }
    .pbi.is-linked { border-color: #cfe3bd; background: #fbfdf8; }
    html.dark .pbi.is-linked { border-color: #3f5a2a; background: #17200f; }
    html.dark .pbi-left { color: #a5c97e; }
    html.dark .pbi-left.is-short { color: #fca5a5; }
    .pbg-loads { display: grid; grid-template-rows: 0fr; opacity: 0; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1), margin-top .28s cubic-bezier(.22,1,.36,1); margin-top: 0; }
    .pbg-loads.is-on { grid-template-rows: 1fr; opacity: 1; margin-top: .45rem; }
    .pbg-loads-in { min-height: 0; overflow: hidden; }
    .pbg-loads-in .pbi-f { margin-left: 2.25rem; width: calc(100% - 2.25rem); display: block; }
    .pbg-loads-in .pbh-hint { margin: .2rem 0 0 2.35rem; }
    @media (prefers-reduced-motion: reduce) { .pbg-loads, .pbi-left { transition: none; } }

    /* Rules & notes: the document, then the files beside it. */
    .pbr-card { padding: 1rem 1.05rem; margin-bottom: .8rem; }
    .pbr-h { display: flex; align-items: center; gap: .5rem; }
    .pbr-h b { flex: 1 1 auto; font-family: var(--font-heading); font-size: 1rem; color: var(--color-gray-900); }
    .pbr-h .pb-save { flex: none; }
    .pbr-card > .pbh-hint { margin: .2rem 0 .7rem; }
    .pbr-ed { min-height: 10rem; }
    .pbr-files { display: grid; gap: .4rem; margin-bottom: .6rem; }
    .pbr-file { display: flex; align-items: center; gap: .6rem; padding: .55rem .6rem; border-radius: .8rem; border: 1px solid var(--color-gray-200); background: var(--color-white);
        transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .pbr-file.is-new { animation: pbIn .32s cubic-bezier(.22,1,.36,1); }
    .pbr-file.is-gone { opacity: 0; transform: translateX(10px); }
    .pbr-file-e { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .6rem; display: inline-flex; align-items: center; justify-content: center; font-size: 1.1rem; background: #eef6e6; }
    .pbr-file-t { flex: 1 1 auto; min-width: 0; }
    .pbr-file-t a, .pbr-file-t span { display: block; font-size: .84rem; font-weight: 800; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-decoration: none; }
    .pbr-file-t a:hover { color: #3d6823; text-decoration: underline; }
    .pbr-file-t small { display: block; font-size: .7rem; color: var(--color-gray-500); }
    .pbr-file .pb-mini { text-decoration: none; }
    .pbr-bar { height: .3rem; border-radius: 999px; background: var(--color-gray-100); margin-top: .3rem; overflow: hidden; }
    .pbr-bar span { display: block; height: 100%; width: 40%; border-radius: 999px; background: #6b9f3d; animation: pbrSlide 1.1s cubic-bezier(.22,1,.36,1) infinite; }
    @keyframes pbrSlide { from { transform: translateX(-100%); } to { transform: translateX(250%); } }
    .pbr-empty { font-size: .8rem; color: var(--color-gray-400); padding: .2rem 0 .5rem; }
    html.dark .pbr-h b { color: #e8efe1; }
    html.dark .pbr-file { background: #151b12; border-color: #2b3a1c; }
    html.dark .pbr-file-e { background: #22301a; }
    html.dark .pbr-file-t a, html.dark .pbr-file-t span { color: #e8efe1; }
    @media (prefers-reduced-motion: reduce) { .pbr-file { transition: none; } .pbr-file.is-new { animation: none; } .pbr-bar span { animation: none; } }
</style>
@endpush

@section('content')
<div class="max-w-2xl mx-auto pb-page" id="pbPage">
    <div class="card pb-head">
        <div class="pb-head-row">
            <span class="pb-head-e" id="pbHeadIcon">{{ $protocol['cropIcon'] }}</span>
            <div class="pb-head-t">
                <b id="pbHeadTitle">{{ $protocol['title'] }}</b>
                <small id="pbHeadSub"></small>
            </div>
            <div class="pb-head-acts">
                <button type="button" class="pb-anee" id="pbAneeBtn" title="Ask Anee to analyze this" aria-label="Ask Anee to analyze this"
                    @if ($options['aiLocked']) data-tier-lock="{{ \App\Support\Tier::farmUnlocksAt('aiAnalyses') }}" data-lock-say="Anee's review of your protocol comes with {{ \App\Support\Tier::withPlan(\App\Support\Tier::farmUnlocksAt('aiAnalyses')) }} — she reads every task against the crop's stages and says what is strong, what is missing and what could go wrong." @endif>
                    <img src="{{ $options['aneeFace'] }}" alt=""> <span>Ask Anee</span>
                </button>
                <button type="button" class="pb-pen" id="pbMetaBtn" title="Name, crop, variety, day count" aria-label="Edit the protocol's details">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
            </div>
        </div>
        <div class="pb-head-ver">
            <button type="button" class="pb-ver" id="pbVerBtn" title="Versions of this protocol" aria-label="Versions of this protocol">
                <span class="pb-ver-e">🗂️</span><span>Version</span><b id="pbVerName">{{ $protocol['versionName'] }}</b><small id="pbVerCount"></small>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
        </div>
        <p class="pb-head-p hidden" id="pbHeadDesc"></p>
        <div class="pb-head-tags" id="pbHeadTags"></div>
    </div>

    <div class="pb-tabs" id="pbTabs" role="tablist" aria-label="Parts of the protocol">
        <span class="pb-tab-ind" id="pbTabInd" aria-hidden="true"></span>
        <button type="button" class="pb-tab" role="tab" data-tab="tasks" aria-selected="true" aria-controls="pbPanelTasks"><span class="pb-tab-e">📋</span> Tasks <em id="pbTabTasksN"></em></button>
        <button type="button" class="pb-tab" role="tab" data-tab="materials" aria-selected="false" aria-controls="pbPanelMat"><span class="pb-tab-e">🧴</span> Materials <em id="pbTabMatN"></em></button>
        <button type="button" class="pb-tab" role="tab" data-tab="rules" aria-selected="false" aria-controls="pbPanelRules"><span class="pb-tab-e">📜</span> Rules &amp; notes</button>
    </div>

    <div class="pb-tools" id="pbTools">
        <button type="button" class="pb-tool" id="pbUndo" disabled title="Undo (Ctrl+Z)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg><span class="pb-tool-w">Undo</span>
        </button>
        <button type="button" class="pb-tool" id="pbRedo" disabled title="Redo (Ctrl+Shift+Z)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m15 14 5-5-5-5"/><path d="M20 9H9.5a5.5 5.5 0 0 0 0 11H13"/></svg><span class="pb-tool-w">Redo</span>
        </button>
        <span class="pb-save" id="pbSaveState" aria-live="polite"></span>
        <button type="button" class="pb-tool is-add" id="pbAddTop" data-for="tasks">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Task
        </button>
        <button type="button" class="pb-tool" id="pbAddNoteTop" data-for="tasks" title="A note between the tasks">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v12l-4 4H4z"/><path d="M16 20v-4h4"/></svg> Note
        </button>
        <button type="button" class="pb-tool is-div" id="pbAddDivTop" data-for="tasks" title="A phase divider — a named line between the tasks">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h3m4 0h4m4 0h3"/><path d="M8 4h8v5l-4-2-4 2z"/></svg> Divider
        </button>
        <button type="button" class="pb-tool is-add" id="pbAddMatTop" data-for="materials">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Material
        </button>
    </div>

    <div class="pb-panel" id="pbPanelTasks" data-panel="tasks" role="tabpanel">
    <div id="pbReview"></div>
    <div id="pbWarn"></div>

    <div class="pb-list" id="pbList"></div>
    <div class="rx-empty hidden" id="pbEmpty">
        <span class="rx-empty-e"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 7h6m-6 4h4"/></svg></span>
        <p class="rx-empty-t">No tasks yet</p>
        <p class="rx-empty-p" id="pbEmptyP">Add the first task: the day of the count it falls on, or how many days before it starts, what is done, and what to apply.</p>
    </div>
    <div class="pb-add-row">
        <button type="button" class="pb-add-bottom" id="pbAddBottom">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Add a task
        </button>
        <button type="button" class="pb-add-bottom is-note" id="pbAddNoteBottom">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v12l-4 4H4z"/><path d="M16 20v-4h4"/></svg> Add a note
        </button>
        <button type="button" class="pb-add-bottom is-div" id="pbAddDivBottom">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h3m4 0h4m4 0h3"/><path d="M8 4h8v5l-4-2-4 2z"/></svg> Add a divider
        </button>
    </div>
    </div>

    {{-- Materials: the protocol's own list, and what the tasks draw from it. --}}
    <div class="pb-panel" id="pbPanelMat" data-panel="materials" role="tabpanel" hidden>
        <div class="pbm-sum" id="pbMatSum"></div>
        <div class="pbm-list" id="pbMatList"></div>
        <div class="rx-empty hidden" id="pbMatEmpty">
            <span class="rx-empty-e"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg></span>
            <p class="rx-empty-t">No materials yet</p>
            <p class="rx-empty-p">List what you will use for the whole protocol — the seed, the fertilizers, the sprays — and how much you have. A task's items can then draw from it, and you will see what is left.</p>
        </div>
        <button type="button" class="pb-add-bottom" id="pbAddMatBottom">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Add a material
        </button>
    </div>

    {{-- Rules & notes: a document of the protocol's standing rules, and files beside it. --}}
    <div class="pb-panel" id="pbPanelRules" data-panel="rules" role="tabpanel" hidden>
        <div class="card pbr-card">
            <div class="pbr-h"><b>Rules &amp; notes</b><span class="pb-save" id="pbRulesState" aria-live="polite"></span></div>
            <p class="pbh-hint">The rules you keep for this protocol: when not to spray, how to mix, what to watch for, who to call. It saves as you write.</p>
            <div class="pbr-ed" id="pbRulesEd"></div>
        </div>
        <div class="card pbr-card">
            <div class="pbr-h"><b>Related files</b></div>
            <p class="pbh-hint">Product labels, a leaflet, a soil test, photos — PDF, pictures or documents, up to 10 MB each.</p>
            <div class="pbr-files" id="pbFiles"></div>
            <button type="button" class="pbt-add" id="pbFileBtn"
                @unless ($options['canUpload']) data-tier-lock="libreAnee" data-lock-say="Files beside your protocol — product labels, leaflets, a soil test — come with Libre + Anee. Writing the rules and notes stays free on every plan." @endunless>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg> Upload a file
            </button>
            <input type="file" id="pbFileIn" hidden multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.txt,.csv,.xls,.xlsx,.ppt,.pptx,image/*,application/pdf">
        </div>
    </div>
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
            <span class="form-label">When</span>
            <div class="pbt-day">
                <button type="button" class="crop-tag" id="pbtWhenBtn">
                    <span class="crop-tag-e" id="pbtWhenIcon">🗓️</span>
                    <span class="crop-tag-t" id="pbtWhenNow">On the count</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
                <input type="number" id="pbtDay" class="form-input" inputmode="numeric" step="1" min="0" max="999" placeholder="day" aria-label="Day">
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
        <p class="pbt-warn" id="pbtWarn" hidden></p>
        <div class="mt-3">
            <label class="form-label" for="pbtDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="pbtDesc" class="form-textarea" rows="3" maxlength="4000" placeholder="How it is done, what to watch while doing it…"></textarea>
        </div>

        <div class="pbt-sec">
            <div class="pbt-sec-h"><span class="form-label">What to apply</span></div>
            <p class="pbh-hint pbt-sec-p">Groups of items — per knapsack, per hectare, or a name of your own. An item can draw from your Materials, and what is left shows as you type.</p>
            <div id="pbGroups"></div>
            <button type="button" class="pbt-add" id="pbGroupAdd">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Add a group
            </button>
        </div>

        <div class="mt-4">
            <label class="form-label" for="pbtNote">Note <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="pbtNote" class="form-textarea" rows="2" maxlength="2000" placeholder="A caution, a reminder, a lesson from last season…"></textarea>
        </div>
        <div class="mt-4">
            <span class="form-label">Importance</span>
            <button type="button" class="crop-tag" id="pbtPrioBtn">
                <span class="crop-tag-e" id="pbtPrioIcon">🟡</span>
                <span class="crop-tag-t" id="pbtPrioNow">Medium</span>
                <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
            </button>
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

{{-- When: which count, on it or before it --}}
<div class="sheet hidden" id="pbWhenSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">When is it?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="pbWhenList"></div>
</div>

{{-- Importance --}}
<div class="sheet hidden" id="pbPrioSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">How much does it matter?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="pbPrioList"></div>
</div>

{{-- A group's name: per knapsack, per hectare, or your own --}}
<div class="sheet hidden" id="pbGroupSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Name the group</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="dt-rows" id="pbGroupList"></div>
        <div class="pbg-custom" id="pbGroupCustomWrap" hidden>
            <input type="text" id="pbGroupCustom" class="form-input" maxlength="120" placeholder="e.g. Tank mix A, Per 10 L bucket">
            <button type="button" class="btn btn-primary w-full" id="pbGroupCustomGo">Use this name</button>
        </div>
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

{{-- A note between the tasks --}}
<div class="sheet hidden" id="pbNoteSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="pbNoteTitle">New note</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <label class="form-label" for="pbnText">The note</label>
        <textarea id="pbnText" class="form-textarea" rows="5" maxlength="2000" placeholder="A reminder between the tasks — what to watch for, what last season taught you, who to call…"></textarea>
        <p class="pbh-hint">It sits where you drag it and follows the task above it. When the protocol is ported, it lands on that day's day book.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn text-red-600 bg-red-50 hover:bg-red-100 border border-red-100" id="pbnDelete" hidden>Delete</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="pbnSave">Save note</button>
    </div>
</div>

{{-- A phase divider: its name, its colour, the day it begins --}}
<div class="sheet hidden" id="pbDivSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="pbDivTitle">New divider</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <label class="form-label" for="pbdLabel">Name of the phase</label>
        <input type="text" id="pbdLabel" class="form-input" maxlength="80" placeholder="e.g. Vegetative phase">
        <div class="mt-4">
            <span class="form-label">It begins</span>
            <div class="pbt-day">
                <button type="button" class="crop-tag" id="pbdWhenBtn">
                    <span class="crop-tag-e" id="pbdWhenIcon">🗓️</span>
                    <span class="crop-tag-t" id="pbdWhenNow">On the count</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
                <input type="number" id="pbdDay" class="form-input" inputmode="numeric" step="1" min="0" max="999" placeholder="day" aria-label="Day">
            </div>
            <p class="pbh-hint">It sits above the first task of that day. Drag it by its grip to move it; it takes the day of the task below it.</p>
        </div>
        <div class="mt-4">
            <span class="form-label">Colour</span>
            <div class="pbd-colors" id="pbdColors"></div>
        </div>
        <div class="pbd-preview" id="pbdPreview"></div>
        <p class="pbh-hint">Dividers are not tasks: they are not counted or checked. Anee reads them as your phase headings, and a port writes each one onto that day's day book.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn text-red-600 bg-red-50 hover:bg-red-100 border border-red-100" id="pbdDelete" hidden>Delete</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="pbdSave">Save divider</button>
    </div>
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
                <button type="button" class="crop-tag" id="pbFixWhenBtn">
                    <span class="crop-tag-e" id="pbFixWhenIcon">🗓️</span>
                    <span class="crop-tag-t" id="pbFixWhenNow">On the count</span>
                    <svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                </button>
                <input type="number" id="pbFixDay" class="form-input" inputmode="numeric" step="1" min="0" max="999" placeholder="day" aria-label="Day">
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

{{-- Ask Anee --}}
<div class="sheet hidden" id="pbAskSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Ask Anee to analyze this</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div class="pb-quote"><img src="{{ $options['aneeFace'] }}" alt=""><div id="pbAskQuote"></div></div>
        <p class="text-sm text-gray-600">Anee reads every task against the crop's growth stages and says what is strong, what is missing, what could go wrong, and what she would add. The review is kept with the protocol; a new one replaces it.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-primary w-full" id="pbAskGo">Analyze it</button>
    </div>
</div>

{{-- A material: name, kind, how much there is, in what unit --}}
<div class="sheet hidden" id="pbMatSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="pbMatTitle">New material</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <label class="form-label" for="pbmName">Name</label>
        <input type="text" id="pbmName" class="form-input" maxlength="120" placeholder="e.g. Urea 46-0-0, Butachlor 60 EC">
        <div class="mt-3">
            <span class="form-label">Kind</span>
            <button type="button" class="crop-tag" id="pbmKindBtn"></button>
        </div>
        <div class="mt-3">
            <label class="form-label" for="pbmQty">How much you have for the protocol</label>
            <div class="pbt-day">
                <input type="number" id="pbmQty" class="form-input" inputmode="decimal" min="0" step="any" placeholder="e.g. 10" style="flex:1 1 auto;text-align:left">
                <button type="button" class="crop-tag" id="pbmUnitBtn" style="flex:0 0 9.5rem"></button>
            </div>
            <p class="pbh-hint">The total for the whole protocol. What the tasks draw is taken from it, and the rest shows as what is left.</p>
        </div>
        <div class="mt-3">
            <label class="form-label" for="pbmNote">Note <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="pbmNote" class="form-textarea" rows="2" maxlength="500" placeholder="Where it is bought, the brand you trust, how it is kept…"></textarea>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn text-red-600 bg-red-50 hover:bg-red-100 border border-red-100" id="pbmDelete" hidden>Delete</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="pbmSave">Save material</button>
    </div>
</div>

{{-- A material's unit --}}
<div class="sheet hidden" id="pbUnitSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Counted in</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="dt-rows" id="pbUnitList"></div>
        <div class="pbg-custom" id="pbUnitCustomWrap" hidden>
            <input type="text" id="pbUnitCustom" class="form-input" maxlength="20" placeholder="e.g. tray, bundle, gallon">
            <button type="button" class="btn btn-primary w-full" id="pbUnitCustomGo">Use this unit</button>
        </div>
    </div>
</div>

{{-- Which material an item draws from --}}
<div class="sheet hidden" id="pbMatPickSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">From your materials</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows" id="pbMatPickList"></div>
</div>

{{-- A material's menu --}}
<div class="sheet hidden" id="pbMatMenu" style="--sheet-width:22rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="pbMatMenuTitle">Material</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body dt-rows">
        <button type="button" class="dt-row" data-mat-act="edit"><span class="dt-row-e">✏️</span><span class="dt-row-body"><b>Edit</b><i>Name, kind, how much you have.</i></span></button>
        <button type="button" class="dt-row" data-mat-act="copy"><span class="dt-row-e">📑</span><span class="dt-row-body"><b>Duplicate</b><i>A copy to change.</i></span></button>
        <button type="button" class="dt-row" data-mat-act="delete"><span class="dt-row-e">🗑️</span><span class="dt-row-body"><b>Delete</b><i>Items drawing from it keep their words. Undo can bring it back.</i></span></button>
    </div>
</div>

{{-- The versions of this protocol --}}
<div class="sheet hidden" id="pbVerSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Versions</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="pbh-hint" style="margin:0 0 .6rem">Each version has its own tasks, materials, rules and files — a wet-season and a dry-season way of running the same crop. Tap one to work on it.</p>
        <div class="dt-rows" id="pbVerList"></div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-primary w-full" id="pbVerNew">New version — a copy of this one</button>
    </div>
</div>

{{-- A version's name --}}
<div class="sheet hidden" id="pbVerNameSheet" style="--sheet-width:24rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="pbVerNameTitle">Name the version</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <label class="form-label" for="pbVerNameIn">Name</label>
        <input type="text" id="pbVerNameIn" class="form-input" maxlength="80" placeholder="e.g. Dry season">
        <p class="pbh-hint" id="pbVerNameHint"></p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="pbVerNameGo">Save</button>
    </div>
</div>

@include('partials.user-tags')
@include('sm.partials.anee-wait')

<script>
(() => {
    const BOOT = @json($protocol);
    const OPT = @json($options);
    let STAGES = @json($stages);
    const U = {
        base: @json(url('/app/protocol-builder')) + '/' + BOOT.id,
        list: @json(\App\Support\BackTo::key() === 'tags' ? route('tags.global') : \App\Support\BackTo::carry(route('pb.page'))),
        job: (id) => @json(url('/app/protocol-builder')) + '/' + id + '/job',
        ver: (vid) => @json(url('/app/protocol-builder')) + '/' + BOOT.id + '/versions/' + vid,
    };
    const $id = (i) => document.getElementById(i);
    const esc = (s) => String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const clone = (v) => JSON.parse(JSON.stringify(v));
    const uid = (p) => p + Math.random().toString(36).slice(2, 9);
    const PHASE = { DAS: 0, DAP: 0, DOS: 0, DAT: 1 };
    const TYPE_ICON = { equipment_prep: '🛠️', land_prep: '🚜', seed_treatment: '🧪', planting: '🌱', irrigation: '💧', service: '🧾', fertilizer: '🧂', foliar_spray: '🌫️', herbicide: '🌿', pesticide: '🐛', copper_fungicide: '🟠', fungicide: '🍄', microbial: '🦠', harvest: '🌾', monitoring: '🔍', worker_payroll: '👷', reminder_checklist: '✅', other: '📌' };
    const GRIP = '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="6" r="1.6"/><circle cx="15" cy="6" r="1.6"/><circle cx="9" cy="12" r="1.6"/><circle cx="15" cy="12" r="1.6"/><circle cx="9" cy="18" r="1.6"/><circle cx="15" cy="18" r="1.6"/></svg>';
    const DOTS = '<svg viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>';
    const X = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6 6 18"/></svg>';
    const CHEV = '<svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';

    /* ------------------------------------------------------------ state */
    let P = { id: BOOT.id, title: BOOT.title, description: BOOT.description, tags: BOOT.tags || [], crop: BOOT.crop, cropLabel: BOOT.cropLabel, cropIcon: BOOT.cropIcon, variety: BOOT.variety, dayType: BOOT.dayType, ported: BOOT.ported };
    let TASKS = Array.isArray(BOOT.tasks) ? BOOT.tasks : [];
    // The protocol's own list of materials; a task's item can draw from one.
    let MATS = Array.isArray(BOOT.materials) ? BOOT.materials : [];
    let HIST = { undo: (BOOT.history && BOOT.history.undo) || [], redo: (BOOT.history && BOOT.history.redo) || [] };
    let REV = BOOT.rev || 1;
    // The version being worked on — each has its own tasks, materials, rules, files and history.
    let VER = { id: BOOT.versionId, name: BOOT.versionName };
    let VERSIONS = Array.isArray(BOOT.versions) ? BOOT.versions : [];
    let RULES = BOOT.rules || '';
    let FILES = Array.isArray(BOOT.files) ? BOOT.files : [];
    let REVIEW = BOOT.analysis || null;
    let REVIEW_AT = BOOT.analysisAt || null;
    let REVIEW_CREDITS = BOOT.analysisCredits || 0;
    const UNDO_MAX = 30;
    const counters = () => (OPT.dayTypes[P.dayType] || OPT.dayTypes.DAS).counters;

    const isNote = (t) => t && t.kind === 'note';
    const isDivider = (t) => t && t.kind === 'divider';
    // A real task: not a note between the tasks, not a phase divider.
    const isTask = (t) => !!t && !isNote(t) && !isDivider(t);
    /* ------------------------------------------------------------ materials: the arithmetic
     * An item drawn from a material uses its quantity — times the loads
     * when its group is mixed per knapsack. What is left is the material's
     * total less everything the tasks plan. */
    const matOf = (id) => MATS.find((m) => m.id === id) || null;
    const hasQty = (v) => v !== null && v !== undefined && v !== '' && Number.isFinite(Number(v));
    const fmt = (n) => (Math.round(Number(n) * 1000) / 1000).toLocaleString('en-US', { maximumFractionDigits: 3 });
    function itemUse(g, it) {
        if (!it || !it.materialId || !hasQty(it.qty)) return null;
        const loads = g && g.perKnapsack && Number(g.loads) > 0 ? Number(g.loads) : 1;
        return Number(it.qty) * loads;
    }
    function useOf(list) {
        const u = {}, w = {};
        list.filter((t) => t && t.kind !== 'note' && t.kind !== 'divider').forEach((t) => (t.groups || []).forEach((g) => (g.items || []).forEach((it) => {
            const n = itemUse(g, it);
            if (n === null || !matOf(it.materialId)) return;
            u[it.materialId] = (u[it.materialId] || 0) + n;
            (w[it.materialId] = w[it.materialId] || []).push({ t, n });
        })));
        return { u, w };
    }
    const matLeft = (m, use) => hasQty(m.qty) ? Number(m.qty) - ((use.u || {})[m.id] || 0) : Infinity;
    // Linked items say the material's name, kind and unit; a material that is gone leaves plain words.
    function syncLinks() {
        TASKS.forEach((t) => (t.groups || []).forEach((g) => (g.items || []).forEach((it) => {
            if (!it.materialId) return;
            const m = matOf(it.materialId);
            if (!m) { delete it.materialId; delete it.qty; return; }
            it.name = m.name; it.kind = m.kind;
            it.amount = hasQty(it.qty) ? `${fmt(it.qty)}${m.unit ? ' ' + m.unit : ''}` : '';
        })));
    }
    const keyOf = (t) => [PHASE[t.counter] ?? 0, t.day, t.pos ?? 0];
    const cmpKey = (a, b) => (a[0] - b[0]) || (a[1] - b[1]) || ((a[2] ?? 0) - (b[2] ?? 0));
    const cmpDay = (a, b) => (a[0] - b[0]) || (a[1] - b[1]);
    function sortTasks() { TASKS.sort((a, b) => cmpKey(keyOf(a), keyOf(b))); }
    function stageLabel(counter, day) {
        // Standing trees: the stage is the trees' age, which the protocol
        // does not fix, so the rail only marks the start.
        if (counter === 'DOS') return day < 0 ? 'Before the start' : (day === 0 ? 'Program starts' : '');
        const two = counters().length === 2;
        if (two && counter === 'DAS') return day < 0 ? 'Before sowing' : 'Seedbed';
        if (day < 0) return counter === 'DAP' ? 'Before planting' : 'Before sowing';
        const rows = STAGES[counter] || [];
        let label = '';
        for (const r of rows) { if (r[0] <= day) label = r[1]; else break; }
        return label;
    }

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
            const res = await api(U.base + '/save', { method: 'POST', body: { versionId: VER.id, rev: REV, tasks: TASKS, materials: MATS, history: HIST } });
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
    window.addEventListener('beforeunload', (e) => { if (((DIRTY || BUSY) && !STALE) || R_DIRTY || R_BUSY) { e.preventDefault(); e.returnValue = ''; } });
    const beacon = (url, body) => {
        try {
            fetch(url, { method: 'POST', keepalive: true, credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify(body) });
        } catch (_) {}
    };
    window.addEventListener('pagehide', () => {
        if (DIRTY && !STALE) beacon(U.base + '/save', { versionId: VER.id, rev: REV, tasks: TASKS, materials: MATS, history: HIST });
        if (R_DIRTY) beacon(U.ver(VER.id) + '/rules', { rules: rulesHtml() });
    });

    /* ------------------------------------------------------------ undo / redo */
    // A step of history is the tasks AND the materials; an older step is a bare task list.
    const snap = () => clone({ tasks: TASKS, materials: MATS });
    function restore(s) {
        if (Array.isArray(s)) { TASKS = s; return; }
        TASKS = Array.isArray(s && s.tasks) ? s.tasks : [];
        MATS = Array.isArray(s && s.materials) ? s.materials : MATS;
    }
    function commit(label, mutate) {
        HIST.undo.push(snap());
        if (HIST.undo.length > UNDO_MAX) HIST.undo.shift();
        HIST.redo = [];
        mutate();
        syncLinks();
        sortTasks();
        render();
        markDirty();
        return label;
    }
    function travel(from, to, word) {
        if (!from.length) return;
        to.push(snap());
        restore(from.pop());
        syncLinks();
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
        if (!(e.ctrlKey || e.metaKey)) return;
        const k = (e.key || '').toLowerCase();
        const isUndo = k === 'z' && !e.shiftKey;
        const isRedo = (k === 'z' && e.shiftKey) || k === 'y';
        if (!isUndo && !isRedo) return;
        const tag = (e.target.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;
        if (document.querySelector('.sheet.is-open')) return;
        if ($id('pbPage').dataset.tab === 'rules') return;
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
        const onlyTasks = TASKS.filter(isTask);
        const noteCount = TASKS.filter(isNote).length;
        const divCount = TASKS.filter(isDivider).length;
        const tags = [`<span class="pb-tag"><b>${onlyTasks.length}</b> ${onlyTasks.length === 1 ? 'task' : 'tasks'}</span>`];
        if (noteCount) tags.push(`<span class="pb-tag"><b>${noteCount}</b> ${noteCount === 1 ? 'note' : 'notes'}</span>`);
        if (divCount) tags.push(`<span class="pb-tag"><b>${divCount}</b> ${divCount === 1 ? 'phase' : 'phases'}</span>`);
        (P.tags || []).forEach((t) => tags.push(`<span class="pb-tag is-score">🏷️ ${esc(t)}</span>`));
        const f = onlyTasks.length ? onlyTasks[0] : null;
        if (f && f.day < 0) tags.push(`<span class="pb-tag">Starts <b>${esc(sayWhen(f.counter, f.day))}</b></span>`);
        const n = onlyTasks.length ? onlyTasks[onlyTasks.length - 1] : null;
        if (n) tags.push(`<span class="pb-tag">Runs to <b>${esc(sayWhen(n.counter, n.day))}</b></span>`);
        if (REVIEW) tags.push(`<span class="pb-tag is-score">Anee: <b>${REVIEW.score}/100</b></span>`);
        if (P.ported) tags.push(`<a class="pb-tag is-ported" href="${esc(P.ported.url)}">Ported ${esc(P.ported.at || '')} → <b>${esc(P.ported.title || 'the season')}</b></a>`);
        $id('pbHeadTags').innerHTML = tags.join('');
        $id('pbVerName').textContent = VER.name || 'Version 1';
        $id('pbVerCount').textContent = VERSIONS.length > 1 ? `· ${VERSIONS.length} versions` : '';
        $id('pbTabTasksN').textContent = onlyTasks.length ? onlyTasks.length : '';
        const matN = $id('pbTabMatN');
        const shortN = MATS.filter((m) => matLeft(m, USE) < -1e-9).length;
        matN.textContent = MATS.length ? (shortN ? '⚠ ' + MATS.length : MATS.length) : '';
        matN.classList.toggle('is-short', shortN > 0);
        const pageTitle = $id('appPageTitle'); if (pageTitle) { pageTitle.textContent = P.title; const sub = pageTitle.nextElementSibling; if (sub && sub.tagName === 'P') sub.textContent = `${P.cropLabel || 'No crop yet'} · ${dt.label || P.dayType}`; }
        document.title = P.title + ' | anee.io';
    }
    function reviewFor(id) {
        if (!REVIEW || !Array.isArray(REVIEW.tasks)) return null;
        return REVIEW.tasks.find((r) => String(r.id) === String(id)) || null;
    }
    function noteHtml(t) {
        return `
            <div class="pb-note pb-card" data-id="${esc(t.id)}" data-note="1">
                <div class="pb-note-rail"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v12l-4 4H4z"/><path d="M16 20v-4h4"/></svg><b>Note</b></div>
                <div class="pb-note-body">${esc(t.text)}</div>
                <div class="pb-acts">
                    <button type="button" class="pb-grip" aria-label="Drag to reorder" title="Drag to reorder">${GRIP}</button>
                    <button type="button" class="pb-menu" aria-label="More" title="More">${DOTS}</button>
                </div>
            </div>`;
    }
    const BOOKMARK = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1z"/></svg>';
    function dividerHtml(t) {
        return `
            <div class="pb-div pb-card" data-id="${esc(t.id)}" data-divider="1" data-color="${esc(t.color || 'amber')}">
                <div class="pb-div-line">
                    <button type="button" class="pb-div-pill" title="Edit the divider">${BOOKMARK}<span>${esc(t.label)}</span><small>from ${esc(sayWhen(t.counter, t.day))}</small></button>
                    <div class="pb-acts">
                        <button type="button" class="pb-grip" aria-label="Drag to move" title="Drag to move">${GRIP}</button>
                        <button type="button" class="pb-menu" aria-label="More" title="More">${DOTS}</button>
                    </div>
                </div>
            </div>`;
    }
    function cardHtml(t) {
        if (isNote(t)) return noteHtml(t);
        if (isDivider(t)) return dividerHtml(t);
        const stage = stageLabel(t.counter, t.day);
        const type = t.type ? OPT.types[t.type] : null;
        const items = t.groups.reduce((n, g) => n + g.items.length, 0);
        const drawn = t.groups.reduce((n, g) => n + g.items.filter((it) => it.materialId && matOf(it.materialId)).length, 0);
        const rv = reviewFor(t.id);
        const chips = [];
        if (type) chips.push(`<span class="pb-chip">${TYPE_ICON[t.type] || '📌'} ${esc(type)}</span>`);
        chips.push(`<span class="pb-chip">${esc((OPT.priorities[t.priority] || {}).label || t.priority)}</span>`);
        if (items) chips.push(`<span class="pb-chip">🧴 ${items} ${items === 1 ? 'item' : 'items'}${drawn ? ` · 📦 ${drawn} from materials` : ''}</span>`);
        if (t.workers !== null && t.workers !== undefined) chips.push(`<span class="pb-chip">👷 ${t.workers}</span>`);
        if (t.note) chips.push(`<span class="pb-chip">📝 note</span>`);
        if (rv) chips.push(`<span class="pb-chip is-anee-${esc(rv.verdict || 'check')}">Anee: ${esc(rv.verdict || 'check')}</span>`);
        if ((WARN[t.id] || []).length) chips.push(`<span class="pb-chip is-warn" title="${esc(uniq(WARN[t.id]).join(' '))}">⚠️ ${uniq(WARN[t.id]).length === 1 ? 'Check this' : uniq(WARN[t.id]).length + ' to check'}</span>`);
        const more = [];
        if (t.description) more.push(`<p>${esc(t.description)}</p>`);
        t.groups.forEach((g) => {
            const loads = g.perKnapsack && Number(g.loads) > 0 ? Number(g.loads) : null;
            more.push(`<div class="pb-g"><b>${esc(g.title || 'Apply')}${g.perKnapsack ? `<em>per knapsack${loads ? ' · ' + fmt(loads) + (loads === 1 ? ' load' : ' loads') : ''}</em>` : ''}</b>${g.items.length ? '<ul>' + g.items.map((it) => {
                const m = it.materialId ? matOf(it.materialId) : null;
                const use = m ? itemUse(g, it) : null;
                const total = (use !== null && loads) ? ` × ${fmt(loads)} = ${fmt(use)} ${m.unit}` : '';
                return `<li>${m ? '📦 ' : ''}${esc(it.name)}${it.kind && it.kind !== 'other' ? ` <small>· ${esc((OPT.kinds[it.kind] || {}).label || it.kind)}</small>` : ''}${it.amount ? ` <small>· ${esc(it.amount + total)}</small>` : ''}</li>`;
            }).join('') + '</ul>' : ''}</div>`);
        });
        if (t.note) more.push(`<div class="pb-note">📝 ${esc(t.note)}</div>`);
        if (rv && rv.note) more.push(`<div class="pb-anee">Anee: ${esc(rv.note)}</div>`);
        uniq(WARN[t.id] || []).forEach((w) => more.push(`<div class="pb-warnnote">⚠️ ${esc(w)}</div>`));
        return `
            <div class="pb-card" data-id="${esc(t.id)}" data-prio="${esc(t.priority)}">
                <div class="pb-day${t.day < 0 ? ' is-before' : ''}">${t.day < 0 ? `<b>BEFORE ${esc(t.counter)}</b><span>${-t.day}</span><i title="${esc(stage)}">${-t.day === 1 ? 'day' : 'days'} before</i>` : `<b>${esc(t.counter)}</b><span>${t.day}</span>${stage ? `<i title="${esc(stage)}">${esc(stage)}</i>` : ''}`}</div>
                <div class="pb-body">
                    <div class="pb-body-t"><span><b>${esc(t.title)}</b>${t.subtitle ? `<i>${esc(t.subtitle)}</i>` : ''}</span><button type="button" class="pb-chev" aria-label="Show more" title="Show more"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg></button></div>
                    <div class="pb-chips">${chips.join('')}</div>
                    <div class="pb-more"><div class="pb-more-in">${more.join('') || '<p class="text-gray-400">Nothing more on this task.</p>'}</div></div>
                </div>
                <div class="pb-acts">
                    <button type="button" class="pb-grip" aria-label="Drag to reorder" title="Drag to reorder">${GRIP}</button>
                    <button type="button" class="pb-menu" aria-label="More" title="More">${DOTS}</button>
                </div>
            </div>`;
    }
    let OPEN = new Set();
    let WARN = {};
    function renderWarnings() {
        const host = $id('pbWarn');
        const entries = Object.entries(WARN);
        if (!entries.length) { host.innerHTML = ''; return; }
        const rows = [];
        entries.forEach(([id, ws]) => { const t = TASKS.find((x) => x.id === id); if (!t) return; uniq(ws).forEach((w) => rows.push({ t, w })); });
        host.innerHTML = `<div class="pb-warn${host._folded ? ' is-folded' : ''}">
            <button type="button" class="pb-warn-h" id="pbWarnHead"><span class="pb-warn-e">⚠️</span><span class="pb-warn-t">${rows.length} ${rows.length === 1 ? 'thing' : 'things'} to check</span><svg class="pb-warn-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button>
            <div class="pb-warn-body"><div class="pb-warn-in">${rows.map(({ t, w }) => `<div class="pb-warn-row" data-warn-for="${esc(t.id)}"><b>${esc(sayWhen(t.counter, t.day))} · ${esc(t.title)}</b>${esc(w)}</div>`).join('')}</div></div>
        </div>`;
    }
    let USE = { u: {}, w: {} };
    function render() {
        USE = useOf(TASKS);
        renderHead();
        WARN = warningsFor(TASKS);
        renderWarnings();
        const list = $id('pbList');
        list.innerHTML = TASKS.map(cardHtml).join('');
        OPEN.forEach((id) => { const c = list.querySelector(`.pb-card[data-id="${CSS.escape(id)}"]`); if (c) c.classList.add('is-open'); });
        $id('pbEmpty').classList.toggle('hidden', TASKS.length > 0);
        $id('pbUndo').disabled = !HIST.undo.length;
        $id('pbRedo').disabled = !HIST.redo.length;
        renderReview();
        renderMats();
    }
    // An old ?edit=1 link: the page is always editable now, so drop the word.
    if (new URL(window.location.href).searchParams.has('edit')) { const url = new URL(window.location.href); url.searchParams.delete('edit'); history.replaceState(null, '', url.toString()); }

    // The chevron unfolds a card; the rest of the card opens it to edit.
    $id('pbList').addEventListener('click', (e) => {
        const card = e.target.closest('.pb-card');
        if (!card) return;
        const id = card.getAttribute('data-id');
        if (e.target.closest('.pb-menu')) { openTaskMenu(id); return; }
        if (e.target.closest('.pb-grip')) return;
        const entry = TASKS.find((x) => x.id === id);
        if (!entry) return;
        if (isNote(entry)) { openNote(id); return; }
        if (isDivider(entry)) { openDivider(id); return; }
        if (e.target.closest('.pb-chev')) {
            card.classList.toggle('is-open');
            const open = card.classList.contains('is-open');
            if (open) OPEN.add(id); else OPEN.delete(id);
            e.target.closest('.pb-chev').setAttribute('aria-label', open ? 'Show less' : 'Show more');
            return;
        }
        openTask(id);
    });

    /* ------------------------------------------------------------ the task menu */
    let MENU_ID = null;
    function openTaskMenu(id) {
        MENU_ID = id;
        const t = TASKS.find((x) => x.id === id);
        $id('pbTaskMenuTitle').textContent = t ? (isNote(t) ? 'Note' : (isDivider(t) ? 'Divider · ' + t.label : t.title)) : 'Task';
        openSheet('pbTaskMenu');
    }
    $id('pbTaskMenu').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-task-act]');
        if (!b || MENU_ID === null) return;
        const act = b.getAttribute('data-task-act');
        const id = MENU_ID;
        closeSheet('pbTaskMenu');
        if (act === 'edit') { setTimeout(() => { const t = TASKS.find((x) => x.id === id); if (isNote(t)) openNote(id); else if (isDivider(t)) openDivider(id); else openTask(id); }, 220); return; }
        if (act === 'copy') {
            const t = TASKS.find((x) => x.id === id);
            if (!t) return;
            commit('Duplicated', () => {
                const c = clone(t); c.id = uid(isNote(t) ? 'n' : (isDivider(t) ? 'd' : 't')); c.pos = (t.pos ?? 0) + 5;
                (c.groups || []).forEach((g) => { g.id = uid('g'); g.items.forEach((it) => { it.id = uid('i'); }); });
                TASKS.push(c);
                flash(c.id);
            });
            toast('Duplicated — the copy sits on the same day.');
            return;
        }
        if (act === 'delete') {
            const t = TASKS.find((x) => x.id === id);
            commit('Deleted', () => { TASKS = TASKS.filter((x) => x.id !== id); });
            toast(isNote(t) ? 'Deleted the note — Undo brings it back.' : (isDivider(t) ? `Deleted the divider "${t.label}" — Undo brings it back.` : `Deleted "${t ? t.title : 'the task'}" — Undo brings it back.`));
        }
    });

    /* ------------------------------------------------------------ notes between the tasks */
    let NOTE_ID = null;
    function blankNote() {
        const last = TASKS.length ? TASKS[TASKS.length - 1] : null;
        return { id: uid('n'), kind: 'note', counter: last ? last.counter : counters()[0], day: last ? last.day : 0, text: '', pos: last ? (last.pos ?? 0) + 10 : 0 };
    }
    function openNote(id) {
        const t = id ? TASKS.find((x) => x.id === id) : null;
        NOTE_ID = t ? t.id : null;
        $id('pbNoteTitle').textContent = t ? 'Edit note' : 'New note';
        $id('pbnText').value = t ? t.text : '';
        $id('pbnDelete').hidden = !t;
        openSheet('pbNoteSheet');
        setTimeout(() => $id('pbnText').focus(), 280);
    }
    $id('pbAddNoteTop').addEventListener('click', () => openNote(null));
    $id('pbAddNoteBottom').addEventListener('click', () => openNote(null));
    $id('pbnSave').addEventListener('click', () => {
        const text = $id('pbnText').value.trim();
        if (!text) { toast('Write the note first.', 'error'); $id('pbnText').focus(); return; }
        const id = NOTE_ID;
        closeSheet('pbNoteSheet');
        commit(id ? 'Changed' : 'Added', () => {
            if (id) { const t = TASKS.find((x) => x.id === id); if (t) t.text = text; flash(id); }
            else { const n = blankNote(); n.text = text; TASKS.push(n); flash(n.id); }
        });
    });
    $id('pbnDelete').addEventListener('click', () => {
        const id = NOTE_ID; if (!id) return;
        closeSheet('pbNoteSheet');
        commit('Deleted', () => { TASKS = TASKS.filter((x) => x.id !== id); });
        toast('Deleted the note — Undo brings it back.');
    });
    /* ------------------------------------------------------------ phase dividers
     * A named line between the tasks ("Vegetative phase") on the day the
     * phase begins. It sorts above the tasks of that day, drags like a note
     * (taking the day of the task below it), and undo covers it. */
    const DIV_COLORS = { amber: '#f59e0b', green: '#6b9f3d', sky: '#0ea5e9', violet: '#8b5cf6', rose: '#f43f5e', slate: '#64748b' };
    const TICK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    let DIV_ID = null, DIV_COLOR = 'amber', DIV_DAY = null;
    $id('pbdColors').innerHTML = (OPT.dividerColors || Object.keys(DIV_COLORS)).map((k) => `<button type="button" class="pbd-color" data-color="${k}" style="--sw:${DIV_COLORS[k] || '#f59e0b'}" aria-label="${k}">${TICK}</button>`).join('');
    function paintDivPreview() {
        const v = DIV_DAY ? DIV_DAY.get() : null;
        $id('pbdColors').querySelectorAll('[data-color]').forEach((b) => b.classList.toggle('is-on', b.getAttribute('data-color') === DIV_COLOR));
        const label = $id('pbdLabel').value.trim() || 'Name of the phase';
        $id('pbdPreview').innerHTML = `<div class="pb-div" data-color="${esc(DIV_COLOR)}"><div class="pb-div-line"><span class="pb-div-pill">${BOOKMARK}<span>${esc(label)}</span>${v ? `<small>from ${esc(sayWhen(v.counter, v.day))}</small>` : ''}</span></div></div>`;
    }
    $id('pbdColors').addEventListener('click', (e) => { const b = e.target.closest('[data-color]'); if (!b) return; DIV_COLOR = b.getAttribute('data-color'); paintDivPreview(); });
    $id('pbdLabel').addEventListener('input', paintDivPreview);
    function openDivider(id) {
        const t = id ? TASKS.find((x) => x.id === id) : null;
        DIV_ID = t ? t.id : null;
        if (!DIV_DAY) DIV_DAY = whenPicker('pbd', paintDivPreview);
        const tasks = TASKS.filter(isTask);
        const last = tasks.length ? tasks[tasks.length - 1] : null;
        DIV_DAY.set(t ? t.counter : (last ? last.counter : counters()[0]), t ? t.day : (last ? last.day : 0));
        DIV_COLOR = t ? (t.color || 'amber') : 'amber';
        $id('pbDivTitle').textContent = t ? 'Edit divider' : 'New divider';
        $id('pbdLabel').value = t ? t.label : '';
        $id('pbdDelete').hidden = !t;
        paintDivPreview();
        openSheet('pbDivSheet');
        if (!window.matchMedia('(pointer: coarse)').matches) setTimeout(() => $id('pbdLabel').focus(), 280);
    }
    // Where a divider sits on its day: just above the first entry of that day.
    function divPosFor(counter, day, selfId) {
        const k = PHASE[counter] ?? 0;
        const same = TASKS.filter((x) => x.id !== selfId && (PHASE[x.counter] ?? 0) === k && x.day === day).map((x) => x.pos ?? 0);
        return same.length ? Math.min(...same) - 5 : 0;
    }
    $id('pbAddDivTop').addEventListener('click', () => openDivider(null));
    $id('pbAddDivBottom').addEventListener('click', () => openDivider(null));
    $id('pbdSave').addEventListener('click', () => {
        const label = $id('pbdLabel').value.trim();
        if (!label) { toast('Name the phase first.', 'error'); $id('pbdLabel').focus(); return; }
        const v = DIV_DAY.get();
        if (!v) { toast('Which day does the phase begin?', 'error'); $id('pbdDay').focus(); return; }
        const id = DIV_ID, color = DIV_COLOR;
        const day = Math.max(-365, Math.min(999, v.day));
        closeSheet('pbDivSheet');
        commit(id ? 'Changed' : 'Added', () => {
            if (id) {
                const t = TASKS.find((x) => x.id === id);
                if (t) {
                    const moved = t.counter !== v.counter || t.day !== day;
                    t.label = label; t.color = color; t.counter = v.counter; t.day = day;
                    if (moved) t.pos = divPosFor(v.counter, day, id);
                    flash(id);
                }
            } else {
                const d = { id: uid('d'), kind: 'divider', label, color, counter: v.counter, day, pos: divPosFor(v.counter, day, null) };
                TASKS.push(d); flash(d.id);
            }
        });
    });
    $id('pbdDelete').addEventListener('click', () => {
        const id = DIV_ID; if (!id) return;
        const t = TASKS.find((x) => x.id === id);
        closeSheet('pbDivSheet');
        commit('Deleted', () => { TASKS = TASKS.filter((x) => x.id !== id); });
        toast(`Deleted the divider${t ? ' "' + t.label + '"' : ''} — Undo brings it back.`);
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
    /**
     * The day picker: "On the count" (DAS 14) or "Before it starts" (7 days
     * before DAS 0 -- land preparation, seedbed work, buying the inputs).
     * A before-day is kept as a negative day on its counter, so the order
     * and the port need nothing new. Wired over `${pfx}When/Counter/Day/
     * Unit`; get() reads the signed day back.
     */
    /**
     * The when picker: one tag that says which count and whether the day
     * is on it or before it (Before DAS 0 = land preparation, seedbed work,
     * buying the inputs), and one number beside it. Only the tag changes
     * when the choice does; a before-day is kept as a negative day.
     */
    let WHEN_FOR = null;
    const whenWord = (when, c) => {
        const two = counters().length === 2;
        if (when === 'before') return `Before ${c} 0`;
        if (c === 'DOS') return 'DOS count';
        return two ? (c === 'DAS' ? 'DAS count (seedbed)' : 'DAT count') : `${c} count`;
    };
    function paintWhenList(st) {
        const two = counters().length === 2;
        const rows = [];
        const first = counters()[0];
        // Before is only ever before the program starts: the sowing or the
        // planting. Land preparation for a transplanted field is seedbed-time
        // work, so it goes on the DAS count.
        const tree = first === 'DOS';
        rows.push({ when: 'before', c: first, e: '⏮️', b: `Before ${first} 0`, i: tree ? 'Days counted back from DOS 0 — the day the program starts on the trees. Buying the inputs, readying the tools.' : `Days counted back from ${first} 0 — the ${first === 'DAP' ? 'planting' : 'sowing'}. Land preparation, seedbed work, buying the inputs.` });
        counters().forEach((c) => {
            rows.push({ when: 'on', c, e: '🗓️', b: whenWord('on', c), i: tree ? 'A day of the DOS count — days from the day the program starts on the standing trees, the count the board keeps for an orchard lot.' : (two ? (c === 'DAS' ? 'A day of the DAS count — from sowing to the transplant. Preparing the main field belongs here too.' : 'A day of the DAT count — from the transplant on.') : `A day of the ${c} count.`) });
        });
        $id('pbWhenList').innerHTML = rows.map((r) => `
            <button type="button" class="dt-row${r.when === st.when && r.c === st.counter ? ' is-on' : ''}" data-when="${r.when}" data-c="${r.c}">
                <span class="dt-row-e">${r.e}</span><span class="dt-row-body"><b>${esc(r.b)}</b><i>${esc(r.i)}</i></span>
                <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </button>`).join('');
    }
    $id('pbWhenList').addEventListener('click', (e) => {
        const r = e.target.closest('[data-when]'); if (!r || !WHEN_FOR) return;
        WHEN_FOR.pick(r.getAttribute('data-when'), r.getAttribute('data-c'));
        closeSheet('pbWhenSheet');
    });
    function whenPicker(pfx, onChange) {
        const st = { when: 'on', counter: counters()[0] };
        const now = $id(pfx + 'WhenNow'), icon = $id(pfx + 'WhenIcon'), dayIn = $id(pfx + 'Day');
        const paint = () => { now.textContent = whenWord(st.when, st.counter); icon.textContent = st.when === 'before' ? '⏮️' : '🗓️'; dayIn.placeholder = st.when === 'before' ? 'days' : 'day'; };
        const api = {
            set(counter, day) { st.when = day < 0 ? 'before' : 'on'; st.counter = (day < 0) ? counters()[0] : (counters().includes(counter) ? counter : counters()[0]); dayIn.value = Math.abs(day); paint(); },
            get() { const n = parseInt(dayIn.value, 10); if (!Number.isFinite(n)) return null; const v = Math.abs(n); return { counter: st.counter, day: st.when === 'before' ? -Math.max(1, v) : v }; },
            pick(when, counter) {
                const was = st.when; st.when = when; st.counter = (when === 'before') ? counters()[0] : (counters().includes(counter) ? counter : counters()[0]);
                const n = Math.abs(parseInt(dayIn.value, 10) || 0);
                if (when === 'before' && was !== 'before' && n === 0) dayIn.value = 7;
                paint(); if (onChange) onChange();
            },
            when: () => st.when, counter: () => st.counter,
        };
        $id(pfx + 'WhenBtn').addEventListener('click', () => { WHEN_FOR = api; paintWhenList(st); openSheet('pbWhenSheet'); });
        dayIn.oninput = () => { if (onChange) onChange(); };
        return api;
    }
    function sayWhen(counter, day) { return day < 0 ? `${-day} ${-day === 1 ? 'day' : 'days'} before ${counter} 0` : `${counter} ${day}`; }

    /* ------------------------------------------------------------ things to check
     * The same kind of warning the activities board gives: a tank that must
     * not be shared, two herbicides a day apart, a spray too close to the
     * harvest. Read off the tasks as they stand; nothing is blocked. */
    const SPRAY_TYPES = ['herbicide', 'pesticide', 'copper_fungicide', 'fungicide', 'foliar_spray', 'microbial'];
    const hasKind = (t, kinds) => (t.groups || []).some((g) => (g.items || []).some((it) => kinds.includes(it.kind)));
    const nameHas = (t, re) => (t.groups || []).some((g) => (g.items || []).some((it) => re.test(it.name || '')));
    const isHerb = (t) => t.type === 'herbicide' || hasKind(t, ['herbicide']);
    const isCopper = (t) => t.type === 'copper_fungicide' || nameHas(t, /copper|cupr|cuprous|oxychlor/i);
    const isFert = (t) => t.type === 'fertilizer' || hasKind(t, ['fertilizer']);
    const isFoliar = (t) => t.type === 'foliar_spray' || hasKind(t, ['foliar']);
    const isSpray = (t) => SPRAY_TYPES.includes(t.type) || hasKind(t, ['herbicide', 'insecticide', 'fungicide', 'molluscicide', 'foliar', 'growth', 'bio']);
    const isPesticide = (t) => ['pesticide', 'fungicide', 'copper_fungicide', 'herbicide'].includes(t.type) || hasKind(t, ['insecticide', 'fungicide', 'herbicide', 'molluscicide', 'rodenticide']);
    const isHarvest = (t) => t.type === 'harvest';
    const gap = (a, b) => (PHASE[a.counter] === PHASE[b.counter]) ? Math.abs(a.day - b.day) : null;
    function warningsFor(list) {
        const out = {};
        const add = (t, w) => { (out[t.id] = out[t.id] || []).push(w); };
        const tasks = list.filter(isTask);
        tasks.forEach((t) => {
            // inside one task: the tank
            const herbItems = hasKind(t, ['herbicide']) || t.type === 'herbicide';
            const helpItems = hasKind(t, ['fertilizer', 'foliar', 'growth', 'bio', 'insecticide', 'fungicide']);
            if (herbItems && helpItems) add(t, 'Herbicide should go out alone — it must not share a tank with anything meant to help the crop, and the knapsack wants rinsing after.');
            if (isCopper(t) && (hasKind(t, ['foliar', 'bio', 'adjuvant']) || nameHas(t, /\boil\b|acid/i))) add(t, 'Copper burns leaves when it meets oils or acidic partners, and it puts biologicals down. Spray it on its own.');
        });
        for (let i = 0; i < tasks.length; i++) {
            for (let j = i + 1; j < tasks.length; j++) {
                const a = tasks[i], b = tasks[j];
                const d = gap(a, b);
                if (d === null) continue;
                const both = (w) => { add(a, w); add(b, w); };
                if (isHerb(a) && isHerb(b) && d <= 3) both(`Two herbicide sprays ${d === 0 ? 'on the same day' : d + (d === 1 ? ' day' : ' days') + ' apart'} — a double dose injures the crop. Space them a week or more, or make sure they are different products for different weeds.`);
                if (isFert(a) && isFert(b) && d <= 2 && !(isFoliar(a) && isFoliar(b))) both(`Two fertilizer applications ${d === 0 ? 'on the same day' : d + (d === 1 ? ' day' : ' days') + ' apart'} — usually one is enough; combine them, or space them out.`);
                if (((isCopper(a) && isFoliar(b)) || (isCopper(b) && isFoliar(a))) && d <= 1) both('Copper and a foliar feed within a day — copper burns leaves with acidic partners. Give it three days.');
                if (d === 0 && isSpray(a) && isSpray(b) && !(isHerb(a) && isHerb(b))) both('Two sprays on the same day — check the products can share a tank, or plan a rinse between them.');
                const h = isHarvest(a) ? a : (isHarvest(b) ? b : null);
                const sp = h === a ? b : a;
                if (h && isPesticide(sp) && h.day - sp.day >= 0 && h.day - sp.day <= 14) add(sp, `A spray ${h.day - sp.day === 0 ? 'on the harvest day' : (h.day - sp.day) + (h.day - sp.day === 1 ? ' day' : ' days') + ' before the harvest'} — check the product's pre-harvest interval.`);
            }
        }
        return out;
    }
    function uniq(arr) { return [...new Set(arr)]; }
    function paintType() {
        const t = W.type ? OPT.types[W.type] : null;
        $id('pbtTypeIcon').textContent = W.type ? (TYPE_ICON[W.type] || '📌') : '📌';
        const now = $id('pbtTypeNow');
        now.textContent = t || 'Choose a type';
        now.classList.toggle('is-none', !t);
    }
    let TASK_DAY = null;
    function paintStage() {
        const v = TASK_DAY ? TASK_DAY.get() : null;
        $id('pbtStage').textContent = v ? (stageLabel(v.counter, v.day) || '') : '';
        paintTaskWarn();
    }
    // What the draft would trip, read against the other tasks as they stand.
    function paintTaskWarn() {
        const el = $id('pbtWarn');
        if (!W || !TASK_DAY) { el.hidden = true; return; }
        const v = TASK_DAY.get();
        const draft = { ...W, counter: v ? v.counter : W.counter, day: v ? v.day : W.day };
        const others = TASKS.filter((x) => x.id !== W_ID);
        const ws = uniq((warningsFor([...others, draft])[draft.id]) || []);
        el.hidden = !ws.length;
        el.innerHTML = ws.map((w) => `<span>⚠️ ${esc(w)}</span>`).join('');
    }
    const PRIO_ICON = { critical: '🔴', high: '🟠', medium: '🟡', low: '⚪' };
    function paintPrio() {
        const p = OPT.priorities[W.priority] || OPT.priorities.medium;
        $id('pbtPrioIcon').textContent = PRIO_ICON[W.priority] || '🟡';
        $id('pbtPrioNow').textContent = p.label;
    }
    $id('pbPrioList').innerHTML = Object.entries(OPT.priorities).map(([k, p]) => `
        <button type="button" class="dt-row" data-prio="${k}"><span class="dt-row-e">${PRIO_ICON[k] || '🟡'}</span><span class="dt-row-body"><b>${esc(p.label)}</b><i>${esc(p.sub)}</i></span>
        <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>`).join('');
    $id('pbtPrioBtn').addEventListener('click', () => {
        $id('pbPrioList').querySelectorAll('[data-prio]').forEach((r) => r.classList.toggle('is-on', r.getAttribute('data-prio') === W.priority));
        openSheet('pbPrioSheet');
    });
    $id('pbPrioList').addEventListener('click', (e) => { const r = e.target.closest('[data-prio]'); if (!r || !W) return; W.priority = r.getAttribute('data-prio'); paintPrio(); closeSheet('pbPrioSheet'); });
    const GROUP_PRESETS = [['Per knapsack (16 L)', '🎒'], ['Per knapsack (20 L)', '🎒'], ['Per drum (200 L)', '🛢️'], ['Per hectare', '🌾'], ['Per 1,000 m²', '📐'], ['Per 100 m² of seedbed', '🌱'], ['Per bag of seed', '🌰'], ['Whole lot', '📦']];
    const groupIcon = (g) => g.perKnapsack ? '🎒' : ((GROUP_PRESETS.find(([n]) => n === g.title) || [])[1] || '📦');
    const groupTag = (g) => `<span class="crop-tag-e">${groupIcon(g)}</span><span class="crop-tag-t${g.title ? '' : ' is-none'}">${g.title ? esc(g.title) : 'Name the group'}</span><svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>`;
    function groupHtml(g) {
        return `
            <div class="pbg" data-g="${esc(g.id)}">
                <div class="pbg-head">
                    <button type="button" class="pb-mini is-grip pbg-grip" aria-label="Drag the group">${GRIP}</button>
                    <button type="button" class="crop-tag pbg-name">${groupTag(g)}</button>
                    <button type="button" class="pb-mini is-x pbg-x" aria-label="Remove the group">${X}</button>
                </div>
                <div class="pbg-loads${g.perKnapsack ? ' is-on' : ''}"><div class="pbg-loads-in">
                    <label class="pbi-f"><span class="pbi-l">Loads</span><input type="number" class="form-input pbg-loads-n" inputmode="decimal" min="0" step="any" placeholder="How many knapsack loads, e.g. 3" value="${g.loads ?? ''}"></label>
                    <p class="pbh-hint">How many knapsack loads this mix is made for. What an item draws from the materials is its quantity × the loads.</p>
                </div></div>
                <div class="pbg-items">${g.items.map(itemHtml).join('')}</div>
                <button type="button" class="pbg-add pbi-add"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Add an item</button>
            </div>`;
    }
    /* An item is either free words (name, kind, amount) or drawn from the
       materials: then its name and kind are the material's, its amount is
       a number in the material's unit, and what is left shows under it. */
    function itemHtml(it) {
        const m = it.materialId ? matOf(it.materialId) : null;
        const from = `<label class="pbi-f"><span class="pbi-l">From materials</span><button type="button" class="crop-tag pbi-from">${m
            ? `<span class="crop-tag-e">${esc((OPT.kinds[m.kind] || OPT.kinds.other).icon)}</span><span class="crop-tag-t">${esc(m.name)}</span>`
            : '<span class="crop-tag-e">✏️</span><span class="crop-tag-t is-none">Not from the list</span>'}${CHEV}</button></label>`;
        const head = `
                <div class="pbi-row">
                    <button type="button" class="pb-mini is-grip pbi-grip" aria-label="Drag the item">${GRIP}</button>
                    ${from}
                    <button type="button" class="pb-mini is-x pbi-x" aria-label="Remove the item">${X}</button>
                </div>`;
        if (m) {
            const k = OPT.kinds[m.kind] || OPT.kinds.other;
            return `
            <div class="pbi is-linked" data-i="${esc(it.id)}">${head}
                <p class="pbi-mat">${esc(k.icon)} ${esc(k.label)} · name and kind follow the material</p>
                <label class="pbi-f"><span class="pbi-l">Quantity${m.unit ? ' (' + esc(m.unit) + ')' : ''}</span><span class="pbi-qrow"><input type="number" class="form-input pbi-qty" inputmode="decimal" min="0" step="any" placeholder="e.g. 50" value="${it.qty ?? ''}">${m.unit ? `<span class="pbt-unit">${esc(m.unit)}</span>` : ''}</span></label>
                <p class="pbi-left" data-left></p>
            </div>`;
        }
        const k = OPT.kinds[it.kind] || OPT.kinds.other;
        return `
            <div class="pbi" data-i="${esc(it.id)}">${head}
                <label class="pbi-f"><span class="pbi-l">Item</span><input type="text" class="form-input pbi-name" maxlength="160" placeholder="e.g. Butachlor 60 EC" value="${esc(it.name)}"></label>
                <div class="pbi-f"><span class="pbi-l">Kind</span><button type="button" class="crop-tag pbi-kind"><span class="crop-tag-e">${esc(k.icon)}</span><span class="crop-tag-t">${esc(k.label)}</span>${CHEV}</button></div>
                <label class="pbi-f"><span class="pbi-l">Amount</span><input type="text" class="form-input pbi-amount" maxlength="80" placeholder="e.g. 50 ml" value="${esc(it.amount)}"></label>
            </div>`;
    }
    /* What is left of each material the draft draws from: the other tasks
       as they stand, plus this draft. Painted under every linked item. */
    function paintLefts() {
        if (!W) return;
        const others = useOf(TASKS.filter((x) => x.id !== W_ID)).u;
        const mine = useOf([W]).u;
        $id('pbGroups').querySelectorAll('.pbi.is-linked').forEach((row) => {
            const g = gOf(row), it = iOf(row);
            const el = row.querySelector('[data-left]');
            if (!g || !it || !el) return;
            const m = matOf(it.materialId);
            if (!m) { el.textContent = ''; return; }
            const use = itemUse(g, it);
            const plannedAll = (others[m.id] || 0) + (mine[m.id] || 0);
            const unit = m.unit ? ' ' + m.unit : '';
            const loads = g.perKnapsack ? (Number(g.loads) > 0 ? Number(g.loads) : 1) : 1;
            const has = m.qty !== null && m.qty !== undefined && m.qty !== '';
            let words, cls = '';
            if (use === null) {
                words = has ? `Say how much. ${fmt(Number(m.qty) - plannedAll)}${unit} left of ${fmt(Number(m.qty))}${unit}.` : 'Say how much.';
                cls = 'is-quiet';
            } else {
                const how = g.perKnapsack ? ` (${fmt(Number(it.qty))} × ${fmt(loads)} ${loads === 1 ? 'load' : 'loads'}${Number(g.loads) > 0 ? '' : ' — say the loads above'})` : '';
                if (!has) { words = `Uses ${fmt(use)}${unit}${how} · no amount on hand set for this material`; cls = 'is-quiet'; }
                else {
                    const left = Number(m.qty) - plannedAll;
                    words = `Uses ${fmt(use)}${unit}${how} · ${left < -1e-9 ? `⚠ ${fmt(-left)}${unit} short of the ${fmt(Number(m.qty))}${unit} you have` : `${fmt(left)}${unit} left of ${fmt(Number(m.qty))}${unit}`}`;
                    if (left < -1e-9) cls = 'is-short';
                }
            }
            el.textContent = words;
            el.classList.toggle('is-short', cls === 'is-short');
            el.classList.toggle('is-quiet', cls === 'is-quiet');
        });
    }
    function paintGroups() {
        $id('pbGroups').innerHTML = W.groups.map(groupHtml).join('');
        wireGroupDrags();
        paintLefts();
    }
    function gOf(el) { const g = el.closest('.pbg'); return g ? W.groups.find((x) => x.id === g.getAttribute('data-g')) : null; }
    function iOf(el) { const g = gOf(el); const i = el.closest('.pbi'); return (g && i) ? g.items.find((x) => x.id === i.getAttribute('data-i')) : null; }
    $id('pbGroups').addEventListener('input', (e) => {
        const g = gOf(e.target); if (!g) return;
        const it = iOf(e.target); if (!it) return;
        if (e.target.classList.contains('pbi-name')) { it.name = e.target.value; paintTaskWarn(); }
        if (e.target.classList.contains('pbi-amount')) it.amount = e.target.value;
        if (e.target.classList.contains('pbi-qty')) { const n = parseFloat(e.target.value); it.qty = Number.isFinite(n) && n >= 0 ? n : null; paintLefts(); }
    });
    // A group's loads: every linked item in it draws quantity × loads.
    $id('pbGroups').addEventListener('input', (e) => {
        if (!e.target.classList.contains('pbg-loads-n')) return;
        const g = gOf(e.target); if (!g) return;
        const n = parseFloat(e.target.value);
        g.loads = Number.isFinite(n) && n > 0 ? n : null;
        paintLefts();
    });
    $id('pbGroups').addEventListener('click', (e) => {
        const g = gOf(e.target); if (!g) return;
        if (e.target.closest('.pbg-name')) { openGroupName(g, e.target.closest('.pbg-name')); return; }
        if (e.target.closest('.pbg-x')) { W.groups = W.groups.filter((x) => x !== g); paintGroups(); return; }
        if (e.target.closest('.pbi-from')) { const it0 = iOf(e.target); if (it0) openMatPick(g, it0, e.target.closest('.pbi')); return; }
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
        if (e.target.closest('.pbi-x')) { g.items = g.items.filter((x) => x !== it); e.target.closest('.pbi').remove(); paintLefts(); return; }
        if (e.target.closest('.pbi-kind')) {
            KIND_FOR = { g, it, btn: e.target.closest('.pbi-kind') };
            $id('pbKindList').querySelectorAll('[data-kind]').forEach((r) => r.classList.toggle('is-on', r.getAttribute('data-kind') === it.kind));
            openSheet('pbKindSheet');
        }
    });
    $id('pbGroupAdd').addEventListener('click', () => {
        const g = { id: uid('g'), title: '', perKnapsack: false, items: [{ id: uid('i'), name: '', kind: 'other', amount: '' }] };
        W.groups.push(g);
        $id('pbGroups').insertAdjacentHTML('beforeend', groupHtml(g));
        wireGroupDrags();
        const el = $id('pbGroups').lastElementChild;
        el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        openGroupName(g, el.querySelector('.pbg-name'));
    });
    // The group's name: a preset, or one of your own.
    let GROUP_FOR = null;
    function openGroupName(g, btn) {
        GROUP_FOR = { g, btn };
        const custom = g.title && !GROUP_PRESETS.some(([n]) => n === g.title);
        $id('pbGroupList').innerHTML = GROUP_PRESETS.map(([n, e]) => `
            <button type="button" class="dt-row${g.title === n ? ' is-on' : ''}" data-gname="${esc(n)}"><span class="dt-row-e">${e}</span><span class="dt-row-body"><b>${esc(n)}</b></span><svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>`).join('')
            + `<button type="button" class="dt-row${custom ? ' is-on' : ''}" data-gname="__other"><span class="dt-row-e">✏️</span><span class="dt-row-body"><b>Others…</b><i>${custom ? esc(g.title) : 'Type a name of your own'}</i></span><svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>`;
        $id('pbGroupCustomWrap').hidden = !custom;
        $id('pbGroupCustom').value = custom ? g.title : '';
        openSheet('pbGroupSheet');
    }
    function setGroupName(name) {
        if (!GROUP_FOR) return;
        const { g, btn } = GROUP_FOR;
        g.title = name.trim();
        g.perKnapsack = /knapsack|sprayer/i.test(g.title);
        if (!g.perKnapsack) g.loads = null;
        btn.innerHTML = groupTag(g);
        // The loads field opens (animated) for a knapsack group, folds for any other.
        const box = btn.closest('.pbg')?.querySelector('.pbg-loads');
        if (box) { box.classList.toggle('is-on', g.perKnapsack); if (!g.perKnapsack) { const n = box.querySelector('.pbg-loads-n'); if (n) n.value = ''; } }
        paintLefts();
        closeSheet('pbGroupSheet');
    }
    $id('pbGroupList').addEventListener('click', (e) => {
        const r = e.target.closest('[data-gname]'); if (!r) return;
        const n = r.getAttribute('data-gname');
        if (n === '__other') {
            $id('pbGroupList').querySelectorAll('[data-gname]').forEach((x) => x.classList.toggle('is-on', x === r));
            $id('pbGroupCustomWrap').hidden = false;
            setTimeout(() => $id('pbGroupCustom').focus(), 60);
            return;
        }
        setGroupName(n);
    });
    $id('pbGroupCustomGo').addEventListener('click', () => { const v = $id('pbGroupCustom').value.trim(); if (!v) { $id('pbGroupCustom').focus(); return; } setGroupName(v); });
    $id('pbGroupCustom').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); $id('pbGroupCustomGo').click(); } });
    $id('pbKindList').innerHTML = Object.entries(OPT.kinds).map(([k, v]) => `
        <button type="button" class="dt-row" data-kind="${k}"><span class="dt-row-e">${esc(v.icon)}</span><span class="dt-row-body"><b>${esc(v.label)}</b></span>
        <svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></button>`).join('');
    $id('pbKindList').addEventListener('click', (e) => {
        const r = e.target.closest('[data-kind]'); if (!r || !KIND_FOR) return;
        KIND_FOR.it.kind = r.getAttribute('data-kind');
        const k = OPT.kinds[KIND_FOR.it.kind];
        KIND_FOR.btn.innerHTML = `<span class="crop-tag-e">${esc(k.icon)}</span><span class="crop-tag-t">${esc(k.label)}</span>${CHEV}`;
        closeSheet('pbKindSheet'); paintTaskWarn();
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
        paintType(); paintTaskWarn();
        closeSheet('pbTypeSheet');
    });

    function openTask(id) {
        const t = id ? TASKS.find((x) => x.id === id) : null;
        W = t ? clone(t) : blankTask();
        W_ID = t ? t.id : null;
        $id('pbTaskTitle').textContent = t ? 'Edit task' : 'New task';
        $id('pbtDelete').hidden = !t;
        if (!TASK_DAY) TASK_DAY = whenPicker('pbt', paintStage);
        TASK_DAY.set(W.counter, W.day);
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
        const picked = TASK_DAY.get();
        if (!picked) { toast('Which day of the count is it?', 'error'); $id('pbtDay').focus(); return; }
        W.counter = picked.counter;
        W.day = Math.max(-365, Math.min(999, picked.day));
        W.title = $id('pbtTitleIn').value.trim();
        if (!W.title) { toast('Give the task a title.', 'error'); $id('pbtTitleIn').focus(); return; }
        W.subtitle = $id('pbtSub').value.trim();
        W.description = $id('pbtDesc').value.trim();
        W.note = $id('pbtNote').value.trim();
        const w = $id('pbtWorkers').value.trim();
        W.workers = w === '' ? null : Math.max(0, Math.min(999, parseInt(w, 10) || 0));
        W.groups.forEach((g) => {
            g.title = (g.title || '').trim();
            g.loads = g.perKnapsack && Number(g.loads) > 0 ? Number(g.loads) : null;
            g.items = g.items.map((it) => {
                const m = it.materialId ? matOf(it.materialId) : null;
                if (m) { const q = Number.isFinite(Number(it.qty)) && it.qty !== null && it.qty !== '' ? Math.max(0, Number(it.qty)) : null; return { id: it.id, name: m.name, kind: m.kind, amount: q !== null ? `${fmt(q)}${m.unit ? ' ' + m.unit : ''}` : '', materialId: m.id, qty: q }; }
                const { materialId, qty, ...free } = it;
                return { ...free, name: (it.name || '').trim(), amount: (it.amount || '').trim() };
            }).filter((it) => it.name !== '');
        });
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
        const byId = (oid) => TASKS.find((x) => x.id === oid);
        if (isDivider(t)) {
            // A divider heads what follows: it takes the day of the task below
            // it (or above, at the very end).
            let anchor = null;
            for (let i = at + 1; i < order.length && !anchor; i++) { const x = byId(order[i]); if (isTask(x)) anchor = x; }
            for (let i = at - 1; i >= 0 && !anchor; i--) { const x = byId(order[i]); if (isTask(x)) anchor = x; }
            commit('Moved', () => {
                if (anchor) { t.counter = anchor.counter; t.day = anchor.day; }
                order.forEach((oid, i) => { const x = byId(oid); if (x) x.pos = i * 10; });
                flash(id);
            });
            if (anchor) toast(`${t.label} now begins ${sayWhen(t.counter, t.day)}.`);
            return;
        }
        if (isNote(t)) {
            // A note goes where it is put and takes its day from the task above it (or below, at the top).
            let anchor = null;
            for (let i = at - 1; i >= 0 && !anchor; i--) { const x = byId(order[i]); if (isTask(x)) anchor = x; }
            for (let i = at + 1; i < order.length && !anchor; i++) { const x = byId(order[i]); if (isTask(x)) anchor = x; }
            commit('Moved', () => {
                if (anchor) { t.counter = anchor.counter; t.day = anchor.day; }
                order.forEach((oid, i) => { const x = byId(oid); if (x) x.pos = i * 10; });
                flash(id);
            });
            return;
        }
        let prev = null, next = null;
        for (let i = at - 1; i >= 0 && !prev; i--) { const x = byId(order[i]); if (isTask(x)) prev = x; }
        for (let i = at + 1; i < order.length && !next; i++) { const x = byId(order[i]); if (isTask(x)) next = x; }
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
    function sayKey(t) { return t ? sayWhen(t.counter, t.day) : null; }
    let FIX_DAY = null;
    function fixRangeOk(counter, day) {
        const k = [PHASE[counter] ?? 0, day];
        const okPrev = !FIX.prev || cmpDay(keyOf(FIX.prev), k) <= 0;
        const okNext = !FIX.next || cmpDay(k, keyOf(FIX.next)) <= 0;
        return okPrev && okNext;
    }
    function openFix(t, prev, next) {
        const between = prev && next ? `between <b>${esc(sayKey(prev))}</b> and <b>${esc(sayKey(next))}</b>` : (prev ? `after <b>${esc(sayKey(prev))}</b>` : `ahead of <b>${esc(sayKey(next))}</b>`);
        $id('pbFixSay').innerHTML = `<b>${esc(t.title)}</b> is on <b>${esc(sayKey(t))}</b>, but you put it ${between}. Change its day to fit there, or put it back where it was.`;
        const start = prev || next;
        if (!FIX_DAY) FIX_DAY = whenPicker('pbFix', fixHint);
        FIX_DAY.set(start.counter, start.day);
        fixHint();
        openSheet('pbFixSheet');
        setTimeout(() => { $id('pbFixDay').focus(); $id('pbFixDay').select(); }, 280);
    }
    function fixHint() {
        if (!FIX || !FIX_DAY) return;
        const v = FIX_DAY.get();
        const h = $id('pbFixHint');
        const lo = FIX.prev ? sayKey(FIX.prev) : null, hi = FIX.next ? sayKey(FIX.next) : null;
        const range = lo && hi ? `${lo} up to ${hi}` : (lo ? `${lo} or later` : `${hi} or earlier`);
        const ok = !!v && fixRangeOk(v.counter, v.day);
        h.textContent = ok ? `Fits — ${range}.` : `It has to be ${range}.`;
        h.classList.toggle('is-bad', !ok);
        $id('pbFixGo').disabled = !ok;
    }
    const fixBack = () => { closeSheet('pbFixSheet'); FIX = null; render(); };
    $id('pbFixBack').addEventListener('click', fixBack);
    $id('pbFixX').addEventListener('click', fixBack);
    $id('pbFixGo').addEventListener('click', () => {
        const v = FIX_DAY ? FIX_DAY.get() : null;
        if (!FIX || !v || !fixRangeOk(v.counter, v.day)) return;
        const { id, order } = FIX;
        const counter = v.counter, d = v.day;
        closeSheet('pbFixSheet'); FIX = null;
        commit('Moved', () => {
            const t = TASKS.find((x) => x.id === id);
            if (t) { t.counter = counter; t.day = d; }
            order.forEach((oid, i) => { const x = TASKS.find((y) => y.id === oid); if (x) x.pos = i * 10; });
            flash(id);
        });
        toast(`Moved to ${sayWhen(counter, d)}.`);
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

    /* ------------------------------------------------------------ the materials tab */
    const UNIT_WORDS = { kg: 'Kilogram (kg)', g: 'Gram (g)', L: 'Litre (L)', mL: 'Millilitre (mL)', bag: 'Bag', sack: 'Sack', bottle: 'Bottle', pack: 'Pack', sachet: 'Sachet', can: 'Can', piece: 'Piece', roll: 'Roll' };
    let FLASH_MAT = null;
    function matCardHtml(m) {
        const k = OPT.kinds[m.kind] || OPT.kinds.other;
        const used = USE.u[m.id] || 0;
        const has = hasQty(m.qty);
        const left = has ? Number(m.qty) - used : null;
        const short = has && left < -1e-9;
        const unit = m.unit ? ' ' + m.unit : '';
        const pct = has ? (Number(m.qty) > 0 ? Math.min(100, used / Number(m.qty) * 100) : (used > 0 ? 100 : 0)) : 0;
        const uses = USE.w[m.id] || [];
        const byTask = [];
        uses.forEach(({ t, n }) => { const x = byTask.find((y) => y.t === t); if (x) x.n += n; else byTask.push({ t, n }); });
        return `
            <div class="pbm-card${short ? ' is-short' : ''}" data-mid="${esc(m.id)}">
                <div class="pbm-e">${esc(k.icon)}</div>
                <div class="pbm-body">
                    <div class="pbm-t"><b>${esc(m.name)}</b><i>${esc(k.label)}${m.note ? ' · ' + esc(m.note) : ''}</i></div>
                    <div class="pbm-nums">
                        <span>On hand <b>${has ? fmt(m.qty) + esc(unit) : '—'}</b></span>
                        <span>Planned <b>${fmt(used)}${esc(unit)}</b></span>
                        ${has ? `<span class="is-left">${short ? 'Short' : 'Left'} <b>${fmt(Math.abs(left))}${esc(unit)}</b></span>` : ''}
                    </div>
                    ${has ? `<div class="pbm-bar"><span data-w="${pct.toFixed(1)}"></span></div>` : ''}
                    ${short ? `<p class="pbm-warn">⚠️ ${fmt(-left)}${esc(unit)} short — the tasks plan more than you have. Add to the amount on hand, or lower a task's quantity.</p>` : ''}
                    ${byTask.length ? `<div class="pbm-uses">${byTask.map(({ t, n }) => `<span class="pb-chip" title="${esc(t.title)}">${esc(sayWhen(t.counter, t.day))} · ${fmt(n)}${esc(unit)}</span>`).join('')}</div>` : '<p class="pbm-none">No task draws from it yet.</p>'}
                </div>
                <div class="pb-acts">
                    <button type="button" class="pb-grip" aria-label="Drag to reorder" title="Drag to reorder">${GRIP}</button>
                    <button type="button" class="pb-menu" aria-label="More" title="More">${DOTS}</button>
                </div>
            </div>`;
    }
    function renderMats() {
        const list = $id('pbMatList');
        // The bars grow from where they were, not from nothing.
        const was = {};
        list.querySelectorAll('.pbm-card').forEach((c) => { const s = c.querySelector('.pbm-bar span'); if (s) was[c.getAttribute('data-mid')] = s.style.width; });
        list.innerHTML = MATS.map(matCardHtml).join('');
        $id('pbMatEmpty').classList.toggle('hidden', MATS.length > 0);
        const bars = [...list.querySelectorAll('.pbm-card')].map((c) => ({ s: c.querySelector('.pbm-bar span'), from: was[c.getAttribute('data-mid')] })).filter((x) => x.s);
        bars.forEach(({ s, from }) => { s.style.width = from || '0%'; });
        requestAnimationFrame(() => requestAnimationFrame(() => bars.forEach(({ s }) => { s.style.width = s.getAttribute('data-w') + '%'; })));
        const short = MATS.filter((m) => matLeft(m, USE) < -1e-9);
        const drawnBy = new Set();
        TASKS.filter(isTask).forEach((t) => (t.groups || []).forEach((g) => (g.items || []).forEach((it) => { if (it.materialId && matOf(it.materialId)) drawnBy.add(t.id); })));
        $id('pbMatSum').innerHTML = MATS.length ? `
            <div><b>${MATS.length}</b><span>${MATS.length === 1 ? 'material' : 'materials'}</span></div>
            <div><b>${drawnBy.size}</b><span>${drawnBy.size === 1 ? 'task draws' : 'tasks draw'} from them</span></div>
            <div class="${short.length ? 'is-short' : ''}"><b>${short.length}</b><span>${short.length ? 'running short' : 'short — all covered'}</span></div>` : '';
        if (FLASH_MAT) {
            const cEl = list.querySelector(`.pbm-card[data-mid="${CSS.escape(FLASH_MAT)}"]`);
            if (cEl && $id('pbPage').dataset.tab === 'materials') { cEl.classList.add('is-new', 'just-moved'); setTimeout(() => cEl.classList.remove('just-moved'), 1200); cEl.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
            FLASH_MAT = null;
        }
    }
    let MW = null, MW_ID = null;
    const paintMatKind = () => { const k = OPT.kinds[MW.kind] || OPT.kinds.other; $id('pbmKindBtn').innerHTML = `<span class="crop-tag-e">${esc(k.icon)}</span><span class="crop-tag-t">${esc(k.label)}</span>${CHEV}`; };
    const paintMatUnit = () => { $id('pbmUnitBtn').innerHTML = `<span class="crop-tag-e">📏</span><span class="crop-tag-t${MW.unit ? '' : ' is-none'}">${MW.unit ? esc(MW.unit) : 'Unit'}</span>${CHEV}`; };
    function openMat(id) {
        const m = id ? matOf(id) : null;
        MW = m ? clone(m) : { id: uid('m'), name: '', kind: 'other', qty: null, unit: '', note: '', pos: MATS.length ? Math.max(...MATS.map((x) => x.pos ?? 0)) + 10 : 0 };
        MW_ID = m ? m.id : null;
        $id('pbMatTitle').textContent = m ? 'Edit material' : 'New material';
        $id('pbmName').value = MW.name;
        $id('pbmQty').value = hasQty(MW.qty) ? MW.qty : '';
        $id('pbmNote').value = MW.note || '';
        $id('pbmDelete').hidden = !m;
        paintMatKind(); paintMatUnit();
        openSheet('pbMatSheet');
        if (!window.matchMedia('(pointer: coarse)').matches) setTimeout(() => $id('pbmName').focus(), 280);
    }
    $id('pbmKindBtn').addEventListener('click', () => {
        KIND_FOR = { it: MW, btn: $id('pbmKindBtn') };
        $id('pbKindList').querySelectorAll('[data-kind]').forEach((r) => r.classList.toggle('is-on', r.getAttribute('data-kind') === MW.kind));
        openSheet('pbKindSheet');
    });
    // The unit: a common one, or one typed in.
    const TICKS = '<svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    $id('pbmUnitBtn').addEventListener('click', () => {
        const units = OPT.units || Object.keys(UNIT_WORDS);
        const custom = MW.unit && !units.includes(MW.unit);
        $id('pbUnitList').innerHTML = units.map((u) => `<button type="button" class="dt-row${MW.unit === u ? ' is-on' : ''}" data-unit="${esc(u)}"><span class="dt-row-e">📏</span><span class="dt-row-body"><b>${esc(UNIT_WORDS[u] || u)}</b></span>${TICKS}</button>`).join('')
            + `<button type="button" class="dt-row${custom ? ' is-on' : ''}" data-unit="__other"><span class="dt-row-e">✏️</span><span class="dt-row-body"><b>Other…</b><i>${custom ? esc(MW.unit) : 'Type a unit of your own'}</i></span>${TICKS}</button>`;
        $id('pbUnitCustomWrap').hidden = !custom;
        $id('pbUnitCustom').value = custom ? MW.unit : '';
        openSheet('pbUnitSheet');
    });
    const setUnit = (u) => { MW.unit = u.trim(); paintMatUnit(); closeSheet('pbUnitSheet'); };
    $id('pbUnitList').addEventListener('click', (e) => {
        const r = e.target.closest('[data-unit]'); if (!r || !MW) return;
        const u = r.getAttribute('data-unit');
        if (u === '__other') { $id('pbUnitList').querySelectorAll('[data-unit]').forEach((x) => x.classList.toggle('is-on', x === r)); $id('pbUnitCustomWrap').hidden = false; setTimeout(() => $id('pbUnitCustom').focus(), 60); return; }
        setUnit(u);
    });
    $id('pbUnitCustomGo').addEventListener('click', () => { const v = $id('pbUnitCustom').value.trim(); if (!v) { $id('pbUnitCustom').focus(); return; } setUnit(v); });
    $id('pbUnitCustom').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); $id('pbUnitCustomGo').click(); } });
    $id('pbmSave').addEventListener('click', () => {
        const name = $id('pbmName').value.trim();
        if (!name) { toast('Name the material.', 'error'); $id('pbmName').focus(); return; }
        const q = $id('pbmQty').value.trim();
        const qty = q === '' ? null : Math.max(0, Number(q));
        if (q !== '' && !Number.isFinite(qty)) { toast('The amount on hand is a number.', 'error'); $id('pbmQty').focus(); return; }
        const saved = { ...MW, name, qty, note: $id('pbmNote').value.trim() };
        const isNew = MW_ID === null;
        closeSheet('pbMatSheet');
        commit(isNew ? 'Added' : 'Changed', () => {
            if (isNew) MATS.push(saved);
            else { const i = MATS.findIndex((x) => x.id === MW_ID); if (i >= 0) MATS[i] = saved; else MATS.push(saved); }
            FLASH_MAT = saved.id;
        });
    });
    function deleteMat(id) {
        const m = matOf(id); if (!m) return;
        const n = (USE.w[id] || []).length;
        const go = () => {
            commit('Deleted', () => { MATS = MATS.filter((x) => x.id !== id); });
            toast(`Deleted "${m.name}"${n ? ' — the items that drew from it keep their words' : ''}. Undo brings it back.`);
        };
        if (!n) { go(); return; }
        window.confirmAction({ title: 'Delete this material?', message: `${n} ${n === 1 ? 'item draws' : 'items draw'} from "${m.name}". ${n === 1 ? 'It keeps' : 'They keep'} its name and amount as plain words.`, confirmText: 'Delete', danger: true }).then((ok) => { if (ok) go(); });
    }
    $id('pbmDelete').addEventListener('click', () => { const id = MW_ID; if (!id) return; closeSheet('pbMatSheet'); deleteMat(id); });
    $id('pbAddMatTop').addEventListener('click', () => openMat(null));
    $id('pbAddMatBottom').addEventListener('click', () => openMat(null));
    let MAT_MENU = null;
    $id('pbMatList').addEventListener('click', (e) => {
        const card = e.target.closest('.pbm-card'); if (!card) return;
        const id = card.getAttribute('data-mid');
        if (e.target.closest('.pb-grip')) return;
        if (e.target.closest('.pb-menu')) { MAT_MENU = id; const m = matOf(id); $id('pbMatMenuTitle').textContent = m ? m.name : 'Material'; openSheet('pbMatMenu'); return; }
        openMat(id);
    });
    $id('pbMatMenu').addEventListener('click', (e) => {
        const b = e.target.closest('[data-mat-act]'); if (!b || !MAT_MENU) return;
        const act = b.getAttribute('data-mat-act'), id = MAT_MENU;
        closeSheet('pbMatMenu');
        if (act === 'edit') { setTimeout(() => openMat(id), 220); return; }
        if (act === 'delete') { deleteMat(id); return; }
        if (act === 'copy') {
            const m = matOf(id); if (!m) return;
            commit('Duplicated', () => { const c2 = { ...clone(m), id: uid('m'), name: m.name + ' (copy)', pos: (m.pos ?? 0) + 5 }; MATS.push(c2); MATS.sort((a, b2) => (a.pos ?? 0) - (b2.pos ?? 0)); FLASH_MAT = c2.id; });
        }
    });

    /* Which material an item draws from — or none. */
    let MPICK = null;
    function openMatPick(g, it, row) {
        MPICK = { g, it, row };
        const others = useOf(TASKS.filter((x) => x.id !== W_ID)).u;
        const mine = useOf([W]).u;
        const rows = [`<button type="button" class="dt-row${it.materialId ? '' : ' is-on'}" data-mpick=""><span class="dt-row-e">✏️</span><span class="dt-row-body"><b>Not from the list</b><i>Type the item's name, kind and amount yourself.</i></span>${TICKS}</button>`];
        MATS.forEach((m) => {
            const k = OPT.kinds[m.kind] || OPT.kinds.other;
            const unit = m.unit ? ' ' + m.unit : '';
            const left = hasQty(m.qty) ? Number(m.qty) - (others[m.id] || 0) - (mine[m.id] || 0) : null;
            const say = left === null ? `${k.label} · no amount on hand set` : (left < -1e-9 ? `${k.label} · ⚠ ${fmt(-left)}${unit} short` : `${k.label} · ${fmt(left)}${unit} left of ${fmt(m.qty)}${unit}`);
            rows.push(`<button type="button" class="dt-row${it.materialId === m.id ? ' is-on' : ''}" data-mpick="${esc(m.id)}"><span class="dt-row-e">${esc(k.icon)}</span><span class="dt-row-body"><b>${esc(m.name)}</b><i>${esc(say)}</i></span>${TICKS}</button>`);
        });
        if (!MATS.length) rows.push('<p class="pbh-hint" style="padding:.4rem .2rem">No materials yet. List them on the Materials tab — what you will use and how much you have — and an item can draw from them.</p>');
        $id('pbMatPickList').innerHTML = rows.join('');
        openSheet('pbMatPickSheet');
    }
    $id('pbMatPickList').addEventListener('click', (e) => {
        const r = e.target.closest('[data-mpick]'); if (!r || !MPICK) return;
        const { it, row } = MPICK;
        const id = r.getAttribute('data-mpick');
        if (id) {
            const m = matOf(id); if (!m) return;
            // A number already typed in the amount carries over when the item first links.
            if (!it.materialId && !hasQty(it.qty)) { const n = parseFloat(String(it.amount || '').replace(/,/g, '')); it.qty = Number.isFinite(n) ? n : null; }
            it.materialId = m.id; it.name = m.name; it.kind = m.kind;
        } else if (it.materialId) {
            const m = matOf(it.materialId);
            it.amount = m && hasQty(it.qty) ? `${fmt(it.qty)}${m.unit ? ' ' + m.unit : ''}` : (it.amount || '');
            delete it.materialId; delete it.qty;
        }
        closeSheet('pbMatPickSheet');
        if (row && row.isConnected) { row.outerHTML = itemHtml(it); }
        paintLefts(); paintTaskWarn();
        const fresh = $id('pbGroups').querySelector(`.pbi[data-i="${CSS.escape(it.id)}"]`);
        if (fresh) { fresh.classList.add('just-moved'); const q = fresh.querySelector('.pbi-qty') || fresh.querySelector('.pbi-name'); if (q && !window.matchMedia('(pointer: coarse)').matches) setTimeout(() => q.focus(), 240); }
    });

    /* ------------------------------------------------------------ the tabs */
    const TAB_KEY = 'anee-pb-tab-' + BOOT.id;
    const TABS = ['tasks', 'materials', 'rules'];
    function showTab(tab, animate) {
        if (!TABS.includes(tab)) tab = 'tasks';
        const page = $id('pbPage');
        const was = page.dataset.tab;
        page.dataset.tab = tab;
        $id('pbTabs').querySelectorAll('[data-tab]').forEach((b) => b.setAttribute('aria-selected', b.getAttribute('data-tab') === tab ? 'true' : 'false'));
        $id('pbTabInd').style.setProperty('--i', TABS.indexOf(tab));
        document.querySelectorAll('.pb-panel[data-panel]').forEach((panel) => {
            const on = panel.getAttribute('data-panel') === tab;
            panel.hidden = !on;
            if (on && animate && was !== tab) { panel.classList.remove('is-entering'); void panel.offsetWidth; panel.classList.add('is-entering'); }
        });
        if (tab === 'rules') ensureRules();
        try { localStorage.setItem(TAB_KEY, tab); } catch (_) { /* not remembered */ }
    }
    $id('pbTabs').addEventListener('click', (e) => { const b = e.target.closest('[data-tab]'); if (b) showTab(b.getAttribute('data-tab'), true); });
    document.querySelectorAll('.pb-panel').forEach((panel) => panel.addEventListener('animationend', () => panel.classList.remove('is-entering')));

    /* ------------------------------------------------------------ rules & notes */
    let RQ = null, R_TOUCHED = false, R_DIRTY = false, R_BUSY = false, R_TIMER = null, R_LAST = RULES, R_FAILS = 0;
    function sayRules(state) {
        const el = $id('pbRulesState');
        el.textContent = { saving: 'Saving…', saved: '✓ Saved', failed: 'Not saved — retrying' }[state] || '';
        el.classList.toggle('is-saved', state === 'saved');
        el.classList.toggle('is-failed', state === 'failed');
    }
    function rulesHtml() {
        if (!RQ) return RULES;
        const root = RQ.root;
        const words = (root.textContent || '').replace(/\u00a0/g, ' ').trim();
        return words ? root.innerHTML : '';
    }
    function ensureRules() {
        if (RQ) return;
        // The editor comes with the app's script, which may still be arriving.
        if (!window.Quill) { if (!ensureRules.waiting) { ensureRules.waiting = true; window.addEventListener('load', () => { if ($id('pbPage').dataset.tab === 'rules') ensureRules(); }, { once: true }); } return; }
        const host = $id('pbRulesEd');
        host.innerHTML = RULES;
        RQ = new Quill(host, { theme: 'snow', placeholder: 'Write the rules you keep for this protocol — one idea to a paragraph…', modules: { toolbar: window.SM_RICH_TOOLBAR } });
        // Only a person's own writing saves: loading a version's document must not.
        ['input', 'keydown', 'paste', 'cut', 'drop'].forEach((ev) => host.addEventListener(ev, () => { R_TOUCHED = true; }, true));
        host.addEventListener('click', (e) => { if (e.target.closest('.se-toolbar, .se-btn, .se-list-layer')) R_TOUCHED = true; }, true);
        new MutationObserver(() => { if (R_TOUCHED) rulesDirty(); }).observe(host, { subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: ['style', 'class', 'href'] });
    }
    function rulesDirty() {
        R_DIRTY = true;
        if (!R_BUSY) sayRules('');
        clearTimeout(R_TIMER);
        R_TIMER = setTimeout(saveRules, 1200);
    }
    async function saveRules() {
        clearTimeout(R_TIMER);
        if (!R_DIRTY) return;
        if (R_BUSY) { R_TIMER = setTimeout(saveRules, 600); return; }
        const html = rulesHtml();
        const vid = VER.id;
        if (html === R_LAST) { R_DIRTY = false; sayRules('saved'); return; }
        R_BUSY = true; R_DIRTY = false;
        sayRules('saving');
        try {
            await api(U.ver(vid) + '/rules', { method: 'POST', body: { rules: html } });
            R_LAST = html; RULES = html; R_FAILS = 0;
            sayRules('saved');
        } catch (err) {
            R_DIRTY = true; sayRules('failed');
            if (++R_FAILS <= 8) R_TIMER = setTimeout(saveRules, Math.min(30000, 2000 * R_FAILS));
        } finally { R_BUSY = false; }
    }
    async function flushRules() {
        clearTimeout(R_TIMER);
        for (let i = 0; i < 20 && R_BUSY; i++) await new Promise((r) => setTimeout(r, 150));
        if (R_DIRTY) await saveRules();
    }
    function setRules(html) {
        RULES = html || ''; R_LAST = RULES; R_TOUCHED = false; R_DIRTY = false; clearTimeout(R_TIMER); sayRules('');
        if (!RQ) return;
        RQ.setContents([]);
        if (RULES.trim() !== '') RQ.clipboard.dangerouslyPasteHTML(RULES);
    }

    /* The files beside the rules. */
    const fileIcon = (f) => /pdf/i.test(f.mime) || /\.pdf$/i.test(f.name) ? '📕' : (/^image\//i.test(f.mime) ? '🖼️' : (/sheet|excel|csv/i.test(f.mime) || /\.(xlsx?|csv)$/i.test(f.name) ? '📊' : (/presentation|powerpoint/i.test(f.mime) || /\.pptx?$/i.test(f.name) ? '📽️' : '📄')));
    const fileSize = (b) => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
    const OPEN_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4"/></svg>';
    const BIN_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>';
    let NEW_FILE = null;
    function fileRow(f) {
        return `<div class="pbr-file${NEW_FILE === f.id ? ' is-new' : ''}" data-fid="${esc(f.id)}">
            <span class="pbr-file-e">${fileIcon(f)}</span>
            <span class="pbr-file-t">${f.url ? `<a href="${esc(f.url)}" target="_blank" rel="noopener">${esc(f.name)}</a>` : `<span>${esc(f.name)}</span>`}<small>${fileSize(f.size || 0)}${f.at ? ' · ' + esc(f.at) : ''}</small></span>
            ${f.url ? `<a class="pb-mini" href="${esc(f.url)}" target="_blank" rel="noopener" aria-label="Open" title="Open">${OPEN_SVG}</a>` : ''}
            <button type="button" class="pb-mini is-x" data-fdel="${esc(f.id)}" aria-label="Delete the file" title="Delete">${BIN_SVG}</button>
        </div>`;
    }
    function renderFiles() {
        $id('pbFiles').innerHTML = FILES.length ? FILES.map(fileRow).join('') : '<p class="pbr-empty">No files yet.</p>';
        NEW_FILE = null;
    }
    $id('pbFileBtn').addEventListener('click', () => $id('pbFileIn').click());
    $id('pbFileIn').addEventListener('change', async (e) => {
        const files = [...(e.target.files || [])];
        e.target.value = '';
        const vid = VER.id;
        for (const file of files) {
            if (file.size > 10 * 1048576) { toast(`"${file.name}" is over 10 MB.`, 'error'); continue; }
            const host = $id('pbFiles');
            host.querySelector('.pbr-empty')?.remove();
            host.insertAdjacentHTML('beforeend', `<div class="pbr-file is-new" data-up><span class="pbr-file-e">⏳</span><span class="pbr-file-t"><span>${esc(file.name)}</span><small>Uploading…</small><span class="pbr-bar"><span></span></span></span></div>`);
            const fd = new FormData();
            fd.append('file', file);
            try {
                const res = await api(U.ver(vid) + '/files', { method: 'POST', body: fd });
                if (VER.id === vid) { FILES.push(res.data.file); NEW_FILE = res.data.file.id; }
                toast(res.message);
            } catch (err) { if (!err.tierLock) toast(err.message, 'error'); }
            renderFiles();
        }
    });
    $id('pbFiles').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-fdel]'); if (!b) return;
        const fid = b.getAttribute('data-fdel');
        const f = FILES.find((x) => x.id === fid); if (!f) return;
        const ok = await window.confirmAction({ title: 'Delete this file?', message: `"${f.name}" will be removed from this version.`, confirmText: 'Delete', danger: true });
        if (!ok) return;
        try {
            const res = await api(U.ver(VER.id) + '/files/' + encodeURIComponent(fid) + '/delete', { method: 'POST', body: {} });
            const row = b.closest('.pbr-file');
            if (row) { row.classList.add('is-gone'); await new Promise((r) => setTimeout(r, 280)); }
            FILES = FILES.filter((x) => x.id !== fid);
            renderFiles();
            toast(res.message);
        } catch (err) { toast(err.message, 'error'); }
    });

    /* ------------------------------------------------------------ versions */
    function loadVersion(v) {
        VER = { id: v.versionId, name: v.versionName };
        TASKS = Array.isArray(v.tasks) ? v.tasks : [];
        MATS = Array.isArray(v.materials) ? v.materials : [];
        HIST = { undo: (v.history && v.history.undo) || [], redo: (v.history && v.history.redo) || [] };
        REV = v.rev || 1;
        FILES = Array.isArray(v.files) ? v.files : [];
        DIRTY = false; FAILS = 0; OPEN = new Set();
        setRules(v.rules || '');
        sortTasks(); render(); renderFiles();
        say('');
        const cur = $id('pbPage').dataset.tab;
        const panel = document.querySelector(`.pb-panel[data-panel="${cur}"]`);
        if (panel) { panel.classList.remove('is-entering'); void panel.offsetWidth; panel.classList.add('is-entering'); }
        const pill = $id('pbVerBtn'); pill.classList.add('is-glow'); setTimeout(() => pill.classList.remove('is-glow'), 900);
    }
    async function settleAll() {
        await flushSave();
        await flushRules();
        if (STALE) { toast('Reload the page first.', 'error'); return false; }
        if (DIRTY || R_DIRTY) { toast('Your last change has not saved yet — try again in a moment.', 'error'); return false; }
        return true;
    }
    const PEN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
    function paintVersions() {
        $id('pbVerList').innerHTML = VERSIONS.map((v) => {
            const on = v.id === VER.id;
            return `<div class="pbv-row">
                <button type="button" class="dt-row${on ? ' is-on' : ''}" data-v-use="${v.id}"><span class="dt-row-e">${on ? '📖' : '🗂️'}</span><span class="dt-row-body"><b>${esc(v.name)}</b><i>${v.count} ${v.count === 1 ? 'task' : 'tasks'} · ${v.materials} ${v.materials === 1 ? 'material' : 'materials'}${on ? ' · in use' : ''}</i></span>${TICKS}</button>
                <button type="button" class="pb-mini" data-v-ren="${v.id}" aria-label="Rename ${esc(v.name)}" title="Rename">${PEN}</button>
                ${VERSIONS.length > 1 ? `<button type="button" class="pb-mini is-x" data-v-del="${v.id}" aria-label="Delete ${esc(v.name)}" title="Delete">${BIN_SVG}</button>` : ''}
            </div>`;
        }).join('');
    }
    function syncVersionCounts() {
        const v = VERSIONS.find((x) => x.id === VER.id);
        if (v) { v.count = TASKS.filter(isTask).length; v.materials = MATS.length; v.name = VER.name; }
    }
    $id('pbVerBtn').addEventListener('click', () => { syncVersionCounts(); paintVersions(); openSheet('pbVerSheet'); });
    let VNAME = null;   // { mode: 'new'|'rename', id }
    function askVersionName(mode, id) {
        VNAME = { mode, id };
        const v = VERSIONS.find((x) => x.id === id);
        $id('pbVerNameTitle').textContent = mode === 'new' ? 'New version' : 'Rename the version';
        $id('pbVerNameIn').value = mode === 'new' ? '' : (v ? v.name : '');
        $id('pbVerNameIn').placeholder = mode === 'new' ? `e.g. Dry season, or Version ${VERSIONS.length + 1}` : 'e.g. Dry season';
        $id('pbVerNameHint').textContent = mode === 'new' ? `A copy of "${VER.name}" — its tasks, materials, rules and files. You can change it without touching this one.` : '';
        openSheet('pbVerNameSheet');
        if (!window.matchMedia('(pointer: coarse)').matches) setTimeout(() => $id('pbVerNameIn').focus(), 280);
    }
    $id('pbVerNew').addEventListener('click', () => { closeSheet('pbVerSheet'); setTimeout(() => askVersionName('new', VER.id), 200); });
    $id('pbVerNameIn').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); $id('pbVerNameGo').click(); } });
    $id('pbVerNameGo').addEventListener('click', async () => {
        const name = $id('pbVerNameIn').value.trim() || (VNAME && VNAME.mode === 'new' ? `Version ${VERSIONS.length + 1}` : '');
        if (!name || !VNAME) { $id('pbVerNameIn').focus(); return; }
        const btn = $id('pbVerNameGo'); btn.disabled = true;
        try {
            if (VNAME.mode === 'new') {
                if (!(await settleAll())) return;
                const res = await api(U.base + '/versions', { method: 'POST', body: { name, from: VER.id } });
                VERSIONS = res.data.versions || VERSIONS;
                closeSheet('pbVerNameSheet');
                loadVersion(res.data.version);
                toast(res.message);
            } else {
                const res = await api(U.ver(VNAME.id) + '/rename', { method: 'POST', body: { name } });
                VERSIONS = res.data.versions || VERSIONS;
                if (VNAME.id === VER.id) VER.name = name;
                closeSheet('pbVerNameSheet');
                renderHead();
                toast('Renamed to "' + name + '".');
            }
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    });
    $id('pbVerList').addEventListener('click', async (e) => {
        const ren = e.target.closest('[data-v-ren]');
        if (ren) { closeSheet('pbVerSheet'); setTimeout(() => askVersionName('rename', +ren.getAttribute('data-v-ren')), 200); return; }
        const del = e.target.closest('[data-v-del]');
        if (del) {
            const id = +del.getAttribute('data-v-del');
            const v = VERSIONS.find((x) => x.id === id); if (!v) return;
            if (VERSIONS.length <= 1) { toast('A protocol keeps at least one version.', 'error'); return; }
            const ok = await window.confirmAction({ title: `Delete "${v.name}"?`, message: `Its ${v.count} ${v.count === 1 ? 'task' : 'tasks'}, materials, rules and files go with it. The other versions stay as they are.`, confirmText: 'Delete', danger: true });
            if (!ok) return;
            if (id === VER.id && !(await settleAll())) return;
            try {
                const res = await api(U.ver(id) + '/delete', { method: 'POST', body: {} });
                VERSIONS = res.data.versions || VERSIONS.filter((x) => x.id !== id);
                if (res.data.version) loadVersion(res.data.version); else renderHead();
                paintVersions();
                toast(res.message);
            } catch (err) { toast(err.message, 'error'); }
            return;
        }
        const use = e.target.closest('[data-v-use]');
        if (use) {
            const id = +use.getAttribute('data-v-use');
            if (id === VER.id) { closeSheet('pbVerSheet'); return; }
            if (!(await settleAll())) return;
            try {
                const res = await api(U.ver(id) + '/use', { method: 'POST', body: {} });
                VERSIONS = res.data.versions || VERSIONS;
                closeSheet('pbVerSheet');
                loadVersion(res.data.version);
                toast(res.message);
            } catch (err) { toast(err.message, 'error'); }
        }
    });

    // The materials reorder freely.
    sortable($id('pbMatList'), '.pbm-card', '.pb-grip', (el, from, to, cancelled) => {
        if (cancelled || from === to) { renderMats(); return; }
        const ids = Array.from($id('pbMatList').children).map((x) => x.getAttribute('data-mid'));
        const id = el.getAttribute('data-mid');
        commit('Moved', () => { MATS.forEach((m) => { m.pos = ids.indexOf(m.id) * 10; }); MATS.sort((a, b) => (a.pos ?? 0) - (b.pos ?? 0)); FLASH_MAT = id; });
    });

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
            P = { id: p.id, title: p.title, description: p.description, tags: p.tags || [], crop: p.crop, cropLabel: p.cropLabel, cropIcon: p.cropIcon, variety: p.variety, dayType: p.dayType, ported: p.ported };
            if (window.userTags) window.userTags.invalidate();
            TASKS = p.tasks || []; MATS = p.materials || MATS; REV = p.rev; STAGES = res.data.stages || {};
            VERSIONS = p.versions || VERSIONS; VER = { id: p.versionId || VER.id, name: p.versionName || VER.name };
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
    $id('pbWarn').addEventListener('click', (e) => {
        if (e.target.closest('#pbWarnHead')) { const host = $id('pbWarn'); host._folded = !host._folded; renderWarnings(); return; }
        const row = e.target.closest('[data-warn-for]');
        if (row) { const c = $id('pbList').querySelector(`.pb-card[data-id="${CSS.escape(row.getAttribute('data-warn-for'))}"]`); if (c) { c.classList.add('is-open', 'just-moved'); OPEN.add(row.getAttribute('data-warn-for')); c.scrollIntoView({ block: 'center', behavior: 'smooth' }); setTimeout(() => c.classList.remove('just-moved'), 1200); } }
    });
    $id('pbReview').addEventListener('click', (e) => {
        const host = $id('pbReview');
        if (e.target.closest('#pbFold')) { host._folded = !host._folded; renderReview(); return; }
        if (e.target.closest('#pbAgain')) { askAnee(); return; }
        const add = e.target.closest('[data-add]');
        if (add && REVIEW) {
            const a = REVIEW.additions[+add.getAttribute('data-add')];
            if (!a) return;
            const counter = counters().includes(a.counter) ? a.counter : counters()[0];
            const t = { ...blankTask(), counter, day: a.day, title: a.title || 'Suggested task', type: OPT.types[a.type] ? a.type : null, note: a.why ? 'Anee: ' + a.why : '' };
            commit('Added', () => { TASKS.push(t); flash(t.id); });
            toast(`Added "${t.title}" — ${sayWhen(counter, a.day)}.`);
        }
    });
    function askAnee() {
        if (OPT.aiLocked) { const b = $id('pbAneeBtn'); window.aneeUpgrade(b?.dataset.lockSay || "Anee's review of your protocol is not on your plan.", b?.dataset.tierLock); return; }
        if (!OPT.canAnalyze) { toast('Anee is not available right now.', 'error'); return; }
        if (!TASKS.filter(isTask).length) { toast('Add a task or two first — there is nothing to review yet.', 'error'); return; }
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
    renderFiles();
    let firstTab = 'tasks';
    try { firstTab = localStorage.getItem(TAB_KEY) || 'tasks'; } catch (_) { /* tasks */ }
    showTab(firstTab, false);
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
