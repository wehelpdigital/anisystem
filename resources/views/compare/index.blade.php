@extends('layouts.app')

@section('title', 'Compare Reports')
@section('page-title', 'Compare Reports')
@section('page-subtitle', 'Two saved reports, side by side')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))

{{-- COMPARE REPORTS, a Quick Tool (2026-09-25). Each side is chosen twice
     over -- the cropping schedule it comes from, then the saved report on
     that season's shelf -- so this season can stand beside the last one.
     The result is drawn by compare/partials/result and held up in the
     report view every report wears (sm/partials/report-view). --}}

@push('head')
@include('partials.tag-sheet-css')
<style>
    .cmp-wrap { max-width: 46rem; margin: 0 auto; }
    .cmp-wrap [hidden], .cmp-sheet [hidden] { display: none !important; }

    /* ---- the two tabs, one ink that slides between them ---- */
    .cmp-tabs { position: relative; display: grid; grid-template-columns: 1fr 1fr; padding: 4px; margin-bottom: 1rem; border-radius: 1rem;
        background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .cmp-ink { position: absolute; top: 4px; bottom: 4px; left: 4px; width: calc(50% - 4px); border-radius: .75rem;
        background: linear-gradient(135deg, #4a7c2a, #2d5016); box-shadow: 0 8px 16px -10px rgb(45 80 22 / .8);
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .cmp-tabs.is-saved .cmp-ink { transform: translateX(100%); }
    .cmp-tab { position: relative; z-index: 1; display: flex; align-items: center; justify-content: center; gap: .45rem; padding: .6rem .5rem; border-radius: .75rem;
        font-weight: 800; font-size: .9rem; color: var(--color-gray-500); transition: color .28s cubic-bezier(.22,1,.36,1); }
    .cmp-tab svg { width: 1.05rem; height: 1.05rem; }
    .cmp-tab.is-on { color: #fff; }
    .cmp-count { min-width: 1.35rem; height: 1.25rem; padding: 0 .4rem; border-radius: 999px; font-size: .68rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-gray-100); color: var(--color-gray-600); transition: background .28s, color .28s; }
    .cmp-tab.is-on .cmp-count { background: rgb(255 255 255 / .22); color: #fff; }
    .cmp-pane.is-in { animation: cmpIn .28s cubic-bezier(.22,1,.36,1) both; }
    @keyframes cmpIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    html.dark .cmp-tabs { background: #151b12; border-color: #2b3a1c; }
    html.dark .cmp-tab { color: #93a684; }
    html.dark .cmp-tab.is-on { color: #fff; }
    html.dark .cmp-count { background: #22301a; color: #b7c2ad; }

    /* ---- the wizard card ---- */
    .cmp-card { border-radius: 1.25rem; background: var(--color-white); border: 1px solid var(--color-gray-200); padding: 1rem 1rem 1.05rem;
        box-shadow: 0 18px 34px -30px rgb(29 53 16 / .55); }
    .cmp-card + .cmp-card { margin-top: .8rem; }
    .cmp-step + .cmp-step { margin-top: 1.1rem; padding-top: 1.1rem; border-top: 1px dashed var(--color-gray-200); }
    .cmp-step-h { display: flex; gap: .65rem; align-items: flex-start; margin-bottom: .7rem; }
    .cmp-n { flex: none; width: 1.65rem; height: 1.65rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center;
        font-size: .78rem; font-weight: 900; background: #2d5016; color: #fff; transition: background .28s cubic-bezier(.22,1,.36,1); }
    .cmp-n svg { width: .9rem; height: .9rem; }
    .cmp-step.is-done .cmp-n { background: #6b9f3d; }
    .cmp-step-h b { display: block; font-size: .95rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.3; }
    .cmp-step-h small { display: block; font-size: .76rem; color: var(--color-gray-500); margin-top: .12rem; line-height: 1.45; }
    .crop-tag:disabled { opacity: .5; cursor: not-allowed; }
    .crop-tag:disabled:hover { border-color: var(--color-gray-200); background: var(--color-white); }
    .crop-tag-e img { width: 1.3rem; height: 1.3rem; object-fit: contain; display: block; }
    .crop-tag-e svg { width: 1.15rem; height: 1.15rem; display: block; color: var(--color-gray-400); }
    .crop-tag { transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .crop-tag.is-next { box-shadow: 0 0 0 3px rgb(107 159 61 / .28); border-color: #86b556; animation: cmpNudge 1.4s cubic-bezier(.22,1,.36,1) 2; }
    @keyframes cmpNudge { 0%, 100% { box-shadow: 0 0 0 3px rgb(107 159 61 / .28); } 50% { box-shadow: 0 0 0 6px rgb(107 159 61 / .12); } }
    html.dark .crop-tag:disabled:hover { background: #1c2416; border-color: #2b3a1c; }
    /* The chosen word in a tag is a dark green -- readable on white, lost on
       the dark surface. */
    html.dark .cmp-wrap .crop-tag-t:not(.is-none) { color: #b5d98f; }
    html.dark .cmp-card { background: #151b12; border-color: #2b3a1c; box-shadow: none; }
    html.dark .cmp-step + .cmp-step { border-color: #2b3a1c; }
    html.dark .cmp-step-h b { color: #e8efe1; }
    html.dark .cmp-n { background: #4a7c2a; }

    /* the two sides, A over B on a phone, A | B on a wide screen */
    .cmp-duo { display: grid; grid-template-columns: minmax(0, 1fr); gap: .55rem; }
    @media (min-width: 640px) { .cmp-duo { grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); align-items: stretch; } }
    .cmp-side { position: relative; display: grid; grid-template-columns: minmax(0, 1fr); gap: .35rem; align-content: start; min-width: 0; border-radius: 1.05rem; padding: .8rem .8rem .75rem; border: 1px solid;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .cmp-side.is-a { background: linear-gradient(180deg, #f3f9ec, var(--color-white) 75%); border-color: #d6e7c4; }
    .cmp-side.is-b { background: linear-gradient(180deg, #fdf5e8, var(--color-white) 75%); border-color: #efdcb8; }
    .cmp-side.is-done.is-a { border-color: #9cc57a; box-shadow: 0 10px 22px -18px rgb(74 124 42 / .8); }
    .cmp-side.is-done.is-b { border-color: #e2b56a; box-shadow: 0 10px 22px -18px rgb(194 98 12 / .7); }
    .cmp-side-h { display: flex; align-items: center; gap: .5rem; margin-bottom: .1rem; }
    .cmp-side-h b { font-size: .92rem; font-weight: 800; color: var(--color-gray-900); }
    .cmp-ok { margin-left: auto; width: 1.4rem; height: 1.4rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; color: #fff;
        opacity: 0; transform: scale(.4); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .cmp-side.is-a .cmp-ok { background: #4a7c2a; }
    .cmp-side.is-b .cmp-ok { background: #c2620c; }
    .cmp-ok svg { width: .85rem; height: .85rem; }
    .cmp-side.is-done .cmp-ok { opacity: 1; transform: none; }
    .cmp-lab { font-size: .64rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--color-gray-500); margin-top: .25rem; }
    .cmp-side-meta { display: flex; flex-wrap: wrap; gap: .3rem; min-height: 0; }
    .cmp-side-meta:empty { display: none; }
    .cmp-mini { display: inline-flex; align-items: center; gap: .25rem; padding: .12rem .5rem; border-radius: 999px; font-size: .66rem; font-weight: 700;
        border: 1px solid var(--color-gray-200); color: var(--color-gray-600); background: var(--color-white); }
    .cmp-mini svg { width: .75rem; height: .75rem; }
    .cmp-vs { align-self: center; justify-self: center; z-index: 1; width: 2.35rem; height: 2.35rem; margin: -.95rem 0; border-radius: 999px; display: grid; place-items: center;
        font-size: .7rem; font-weight: 900; letter-spacing: .04em; color: #fff; background: linear-gradient(135deg, #2d5016, #4a7c2a);
        box-shadow: 0 0 0 4px var(--color-white), 0 8px 16px -8px rgb(29 53 16 / .7); }
    @media (min-width: 640px) { .cmp-vs { margin: 0 -.35rem; } }
    html.dark .cmp-side.is-a { background: linear-gradient(180deg, #1a2513, #151b12 75%); border-color: #2f4521; }
    html.dark .cmp-side.is-b { background: linear-gradient(180deg, #251d10, #151b12 75%); border-color: #4a3818; }
    html.dark .cmp-side.is-done.is-a { border-color: #5c8a3a; box-shadow: none; }
    html.dark .cmp-side.is-done.is-b { border-color: #9a6a24; box-shadow: none; }
    html.dark .cmp-side-h b { color: #e8efe1; }
    html.dark .cmp-mini { background: #1b2316; border-color: #2b3a1c; color: #b7c2ad; }
    html.dark .cmp-vs { box-shadow: 0 0 0 4px #151b12; }

    /* Anee's read: a switch, her face, the price and the wallet */
    .cmp-ai { position: relative; display: flex; align-items: center; gap: .75rem; padding: .8rem .85rem; border-radius: 1.05rem; cursor: pointer;
        background: linear-gradient(115deg, #f3f8ec, #eaf4de); border: 1px solid #cfe3b8;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .cmp-ai.is-on { border-color: #6b9f3d; box-shadow: 0 0 0 3px rgb(107 159 61 / .18); }
    .cmp-ai.is-off { cursor: not-allowed; }
    .cmp-switch-in { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
    .cmp-switch { flex: none; position: relative; width: 2.7rem; height: 1.55rem; border-radius: 999px; background: var(--color-gray-300); transition: background .28s cubic-bezier(.22,1,.36,1); }
    .cmp-switch::after { content: ''; position: absolute; top: 3px; left: 3px; width: calc(1.55rem - 6px); height: calc(1.55rem - 6px); border-radius: 999px; background: #fff;
        box-shadow: 0 1px 3px rgb(0 0 0 / .3); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .cmp-switch-in:checked + .cmp-switch { background: #4a7c2a; }
    .cmp-switch-in:checked + .cmp-switch::after { transform: translateX(1.15rem); }
    .cmp-switch-in:disabled + .cmp-switch { opacity: .5; }
    .cmp-switch-in:focus-visible + .cmp-switch { outline: 2px solid var(--color-brand-500); outline-offset: 2px; }
    .cmp-ai-t { flex: 1 1 auto; min-width: 0; }
    .cmp-ai-t b { display: block; font-size: .88rem; font-weight: 800; color: #2d5016; }
    .cmp-ai-t i { display: block; font-style: normal; font-size: .76rem; color: #3d5226; line-height: 1.5; margin-top: .15rem; }
    .cmp-ai-t .cmp-bal { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem; margin-top: .35rem; font-weight: 700; }
    .cmp-ai-t .cmp-bal.is-short { color: #92400e; }
    .cmp-ai-t .cmp-bal a { color: #3d6823; text-decoration: underline; }
    .cmp-ai-face { flex: none; width: 2.5rem; height: 2.5rem; border-radius: 999px; object-fit: cover; border: 2px solid #fff; box-shadow: 0 0 0 2px #a9d383; }
    html.dark .cmp-ai { background: linear-gradient(115deg, #1c2913, #22301a); border-color: #2f3f1f; }
    html.dark .cmp-ai.is-on { border-color: #6b9f3d; }
    html.dark .cmp-ai-t b { color: #cfe6b8; }
    html.dark .cmp-ai-t i { color: #a8bd93; }
    html.dark .cmp-ai-t .cmp-bal.is-short { color: #f5c77a; }
    html.dark .cmp-ai-t .cmp-bal a { color: #a5c97e; }
    html.dark .cmp-ai-face { border-color: #151b12; }

    /* the run */
    .cmp-run { display: flex; align-items: center; justify-content: center; gap: .55rem; width: 100%; margin-top: 1.1rem; padding: .95rem 1rem; border-radius: 1.05rem;
        color: #fff; font-weight: 800; font-size: 1rem;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 30%, #3d6823 55%, #6b9f3d 80%, #8fc96a); background-size: 260% 100%; animation: cmpTide 5.5s ease-in-out infinite alternate;
        box-shadow: 0 12px 24px -14px rgb(61 104 35 / .75);
        transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1), filter .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .cmp-run:hover:not(:disabled) { transform: translateY(-1px); }
    .cmp-run:disabled { opacity: .5; filter: grayscale(.4); animation: none; box-shadow: none; cursor: not-allowed; }
    .cmp-run > svg { width: 1.2rem; height: 1.2rem; }
    .cmp-run .credit-coin { background: rgb(255 255 255 / .92); }
    @keyframes cmpTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }
    .cmp-hint { text-align: center; font-size: .76rem; color: var(--color-gray-500); margin-top: .55rem; min-height: 1.1rem; transition: opacity .28s cubic-bezier(.22,1,.36,1); }

    /* loading bones */
    .cmp-bones { display: grid; gap: .7rem; }
    .cmp-bone { height: 2.8rem; border-radius: .85rem; background: linear-gradient(100deg, var(--color-gray-100) 40%, var(--color-gray-50) 50%, var(--color-gray-100) 60%);
        background-size: 200% 100%; animation: cmpShimmer 1.3s linear infinite; }
    .cmp-bone.is-sm { height: 1rem; width: 60%; }
    .cmp-bone.is-tall { height: 9rem; }
    @keyframes cmpShimmer { from { background-position: 150% 0; } to { background-position: -50% 0; } }
    html.dark .cmp-bone { background: linear-gradient(100deg, #1c2416 40%, #26301c 50%, #1c2416 60%); background-size: 200% 100%; }

    /* ---- the saved shelf ---- */
    .cmp-search { position: relative; margin-bottom: .75rem; }
    .cmp-search svg { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); width: 1.05rem; height: 1.05rem; color: var(--color-gray-400); pointer-events: none; }
    .cmp-search .form-input { padding-left: 2.5rem !important; }
    .cmp-list { border-radius: 1.15rem; background: var(--color-white); border: 1px solid var(--color-gray-200); overflow: hidden; }
    .cmp-row { display: flex; align-items: center; gap: .75rem; width: 100%; text-align: left; padding: .85rem .9rem; border-bottom: 1px solid var(--color-gray-100); cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1);
        animation: cmpRowIn .34s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i, 0) * 35ms); }
    @keyframes cmpRowIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
    .cmp-row:last-child { border-bottom: 0; }
    #cmpRows.is-quiet .cmp-row { animation: none; }
    .cmp-row:hover { background: var(--color-brand-50); }
    .cmp-row.is-busy { opacity: .55; pointer-events: none; }
    .cmp-row-ico { position: relative; flex: none; width: 2.7rem; height: 2.7rem; border-radius: .9rem; display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #eef6e6 0%, #eef6e6 50%, #fdf1de 50%, #fdf1de 100%); border: 1px solid #e3ecd7; }
    .cmp-row-ico img { width: 1.5rem; height: 1.5rem; object-fit: contain; }
    .cmp-row-ico .face { position: absolute; right: -5px; bottom: -5px; width: 1.25rem; height: 1.25rem; border-radius: 999px; object-fit: cover; border: 2px solid var(--color-white); }
    .cmp-row-t { flex: 1 1 auto; min-width: 0; }
    .cmp-row-t b { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: .9rem; font-weight: 800; line-height: 1.3; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .cmp-row-tags { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .3rem; }
    .cmp-tag { display: inline-flex; align-items: center; gap: .25rem; max-width: 100%; padding: .12rem .5rem; border-radius: 999px; font-size: .66rem; font-weight: 700;
        border: 1px solid var(--color-gray-200); color: var(--color-gray-600); background: var(--color-white); }
    .cmp-tag span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cmp-tag svg { flex: none; width: .75rem; height: .75rem; }
    .cmp-tag.is-season { border-color: #cfe3bd; color: #2f5219; background: #f1f8ea; }
    .cmp-tag.is-anee { border-color: #f1d6a2; color: #92400e; background: #fff8ec; }
    .cmp-row-t small { display: block; font-size: .72rem; color: var(--color-gray-500); margin-top: .3rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cmp-pen { flex: none; width: 2.1rem; height: 2.1rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; color: var(--color-gray-400);
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .cmp-pen:hover { background: var(--color-gray-100); color: var(--color-brand-700); }
    .cmp-pen svg { width: .95rem; height: .95rem; }
    .cmp-go { flex: none; width: 1rem; height: 1rem; color: var(--color-gray-300); }
    .cmp-more { display: flex; align-items: center; gap: .35rem; margin: .8rem auto 0; padding: .55rem 1.2rem; border-radius: 999px; font-weight: 800; font-size: .84rem;
        color: #3d6823; background: var(--color-white); border: 1px solid #cfe3bd; transition: background .28s cubic-bezier(.22,1,.36,1); }
    .cmp-more:hover { background: #f1f8ea; }
    .cmp-viewonly { display: flex; gap: .55rem; align-items: flex-start; font-size: .8rem; line-height: 1.5; color: var(--color-gray-600); background: var(--color-white);
        border: 1px solid var(--color-gray-200); border-radius: .95rem; padding: .65rem .8rem; margin-bottom: .8rem; }
    .cmp-viewonly svg { flex: none; width: 1.05rem; height: 1.05rem; margin-top: .1rem; color: var(--color-brand-600); }
    html.dark .cmp-list { background: #151b12; border-color: #2b3a1c; }
    html.dark .cmp-row { border-color: #222b1a; }
    html.dark .cmp-row:hover { background: #1c2416; }
    html.dark .cmp-row-ico { background: linear-gradient(135deg, #1f2d15 0%, #1f2d15 50%, #2a2112 50%, #2a2112 100%); border-color: #2b3a1c; }
    html.dark .cmp-row-ico .face { border-color: #151b12; }
    html.dark .cmp-row-t b { color: #e8efe1; }
    html.dark .cmp-tag { background: #151b12; border-color: #2b3a1c; color: #a5b89a; }
    html.dark .cmp-tag.is-season { background: #1f2d15; border-color: #3f5a2a; color: #cfe6b8; }
    html.dark .cmp-tag.is-anee { background: #2a2112; border-color: #5c4418; color: #f5c77a; }
    html.dark .cmp-pen:hover { background: #22301a; color: #a5c97e; }
    html.dark .cmp-more { background: #151b12; border-color: #3f5a2a; color: #a5c97e; }
    html.dark .cmp-viewonly { background: #151b12; border-color: #2b3a1c; color: #b7c2ad; }

    /* ---- the sheets' rows ---- */
    .cmp-sheet .dt-row:disabled { opacity: .45; cursor: not-allowed; }
    .cmp-sheet .dt-row:disabled:hover { border-color: var(--color-gray-200); background: var(--color-white); }
    .cmp-sheet .dt-row-e img { width: 1.35rem; height: 1.35rem; object-fit: contain; display: block; }
    .cmp-sheet .dt-row-body i + i { margin-top: .15rem; }
    .cmp-sheet .dt-row-body .d { color: var(--color-gray-400); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .cmp-sheet .dt-row { animation: cmpRowIn .3s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i, 0) * 30ms); }
    .cmp-sheet-sub { font-size: .78rem; color: var(--color-gray-500); line-height: 1.5; margin: 0 0 .75rem; }
    .cmp-sheet-head { font-size: .64rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--color-gray-400); margin: .6rem 0 .1rem; }
    html.dark .cmp-sheet .dt-row:disabled:hover { background: #1c2417; border-color: #2f3a26; }

    @media (prefers-reduced-motion: reduce) {
        .cmp-ink, .cmp-tab, .cmp-pane.is-in, .cmp-run, .cmp-row, .cmp-sheet .dt-row, .cmp-switch, .cmp-switch::after, .cmp-ok, .crop-tag, .crop-tag.is-next, .cmp-bone { transition: none !important; animation: none !important; }
    }
    html.sm-still .cmp-pane.is-in, html.sm-still .cmp-row, html.sm-still .cmp-sheet .dt-row, html.sm-still .cmp-run, html.sm-still .crop-tag.is-next { animation: none; }
</style>
@endpush

@section('content')
@include('sm.partials.report-view')
@include('compare.partials.result')
@php
    $icoGrid = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/></svg>';
    $icoChev = '<svg class="crop-tag-c" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
    $icoTick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
@endphp
<div class="cmp-wrap">
    @if ($canWrite)
        <div class="cmp-tabs" role="tablist" id="cmpTabs">
            <span class="cmp-ink" aria-hidden="true"></span>
            <button type="button" role="tab" class="cmp-tab is-on" id="cmpTabGen" aria-selected="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>
                Compare
            </button>
            <button type="button" role="tab" class="cmp-tab" id="cmpTabSaved" aria-selected="false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                Saved <span class="cmp-count" id="cmpCount" hidden></span>
            </button>
        </div>

        <div class="cmp-pane" id="cmpGenPane">
            {{-- What this tool is, before the form that uses it. --}}
            <div class="rx-about">
                <span class="rx-about-e"><img src="{{ asset('images/icons/ab-testing.png') }}" alt="" style="border-radius:0;object-fit:contain"></span>
                <div class="rx-about-t">
                    <b>What Compare Reports gives you</b>
                    <p>Two saved reports of the same kind, side by side — from one season or from two: this season's labor against last season's, one lot's protocol against another's.</p>
                    <ul>
                        <li><b>The season first, then the report</b> — for each side, from any of your cropping schedules, closed and archived ones too</li>
                        <li><b>The figures, lined up</b> — every amount beside its match, with the difference and which one did better</li>
                        <li><b>{{ $aneeName }}'s read, if you ask for it</b> — what changed, the strengths of each, and what to carry forward (this part spends credits)</li>
                    </ul>
                    <p class="rx-about-note">Every comparison lands on the Saved tab, where you can rename and describe it.</p>
                </div>
            </div>

            {{-- While the seasons load. --}}
            <div class="cmp-card" id="cmpBones" aria-hidden="true">
                <div class="cmp-bones">
                    <div class="cmp-bone is-sm"></div>
                    <div class="cmp-bone"></div>
                    <div class="cmp-bone is-tall"></div>
                    <div class="cmp-bone"></div>
                </div>
            </div>

            {{-- Nothing on any shelf that could be compared yet. --}}
            <div class="cmp-card" id="cmpNothing" hidden>
                <div class="rx-empty">
                    <span class="rx-empty-e"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg></span>
                    <p class="rx-empty-t" id="cmpNothingT">Nothing to compare yet</p>
                    <p class="rx-empty-p" id="cmpNothingP">Save two reports of the same kind first — a Labor, Expenses or Profit report, a protocol, or one of {{ $aneeName }}'s reads — in any of your seasons. Then come back here.</p>
                    <a href="{{ route('sm.index') }}" class="btn btn-primary mt-4" data-nav-loader>Open my cropping schedules</a>
                </div>
            </div>

            <div class="cmp-card" id="cmpWizard" hidden>
                <div class="cmp-step" id="cmpStepKind">
                    <div class="cmp-step-h"><span class="cmp-n">1</span><div><b>What are you comparing?</b><small>Two reports of the same kind — apples with apples.</small></div></div>
                    <button type="button" class="crop-tag" id="cmpKindBtn">
                        <span class="crop-tag-e" id="cmpKindE">{!! $icoGrid !!}</span>
                        <span class="crop-tag-t is-none" id="cmpKindNow">Choose the kind of report</span>
                        {!! $icoChev !!}
                    </button>
                </div>

                <div class="cmp-step" id="cmpStepPick">
                    <div class="cmp-step-h"><span class="cmp-n">2</span><div><b>Pick the two reports</b><small>For each side, the cropping schedule first, then the saved report from its shelf.</small></div></div>
                    <div class="cmp-duo">
                        @foreach (['a' => 'A', 'b' => 'B'] as $k => $L)
                            <div class="cmp-side is-{{ $k }}" id="cmpSide{{ $L }}">
                                <div class="cmp-side-h"><span class="cx-letter is-{{ $k }}">{{ $L }}</span><b>Report {{ $L }}</b><span class="cmp-ok" aria-hidden="true">{!! $icoTick !!}</span></div>
                                <span class="cmp-lab">Cropping schedule</span>
                                <button type="button" class="crop-tag" data-cmp-season="{{ $k }}" disabled>
                                    <span class="crop-tag-e" data-cmp-e>🌱</span>
                                    <span class="crop-tag-t is-none" data-cmp-t>Choose the season</span>
                                    {!! $icoChev !!}
                                </button>
                                <span class="cmp-lab">Saved report</span>
                                <button type="button" class="crop-tag" data-cmp-report="{{ $k }}" disabled>
                                    <span class="crop-tag-e" data-cmp-e>{!! $icoGrid !!}</span>
                                    <span class="crop-tag-t is-none" data-cmp-t>Choose the report</span>
                                    {!! $icoChev !!}
                                </button>
                                <div class="cmp-side-meta" data-cmp-meta></div>
                            </div>
                            @if ($k === 'a')<span class="cmp-vs" aria-hidden="true">VS</span>@endif
                        @endforeach
                    </div>
                </div>

                <div class="cmp-step" id="cmpStepAi" hidden>
                    <div class="cmp-step-h"><span class="cmp-n">3</span><div><b>{{ $aneeName }}'s read</b><small>Optional — the comparison itself is free.</small></div></div>
                    <label class="cmp-ai" id="cmpAi">
                        <input type="checkbox" class="cmp-switch-in" id="cmpWithAi">
                        <span class="cmp-switch" aria-hidden="true"></span>
                        <span class="cmp-ai-t">
                            <b>Add {{ $aneeName }}'s read · <span id="cmpPrice">30</span> credits</b>
                            <i>She reads both and tells you what changed, the strengths of each, and what to carry forward.</i>
                            <i class="cmp-bal" id="cmpBal"></i>
                        </span>
                        <img class="cmp-ai-face" src="{{ $aneeFace }}" alt="">
                    </label>
                </div>

                <button type="button" class="cmp-run" id="cmpRun" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/></svg>
                    <span id="cmpRunT">Compare them</span>
                </button>
                <p class="cmp-hint" id="cmpHint">Choose the kind of report first.</p>
            </div>

            <div id="cmpReport" hidden></div>
        </div>
    @endif

    <div class="cmp-pane" id="cmpSavedPane" @if ($canWrite) hidden @endif>
        @unless ($canWrite)
            <p class="cmp-viewonly"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9.5"/><path d="M12 16v-4.5M12 8h.01"/></svg><span>These are the farm's saved comparisons, for reading. Making a new one is for the owner, or a worker with edit access to Reports.</span></p>
        @endunless
        <div class="cmp-search" id="cmpSearchWrap" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="search" id="cmpQ" class="form-input" placeholder="Find a comparison…" autocomplete="off" aria-label="Find a comparison">
        </div>
        <div class="cmp-list">
            <div id="cmpRows"></div>
            <div id="cmpRowBones" class="cmp-bones" style="padding:.9rem" aria-hidden="true">
                <div class="cmp-bone"></div><div class="cmp-bone"></div><div class="cmp-bone"></div>
            </div>
            <div class="rx-empty" id="cmpEmpty" hidden>
                <span class="rx-empty-e"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg></span>
                <p class="rx-empty-t">No comparisons yet</p>
                <p class="rx-empty-p">{{ $canWrite ? 'Compare two saved reports on the Compare tab and it lands here by itself — every one you make, newest first.' : 'When the farm saves a comparison, it will be here to read.' }}</p>
            </div>
            <div class="rx-empty" id="cmpNone" hidden>
                <p class="rx-empty-t">Nothing matches</p>
                <p class="rx-empty-p">Try another word — the search reads each comparison's name and description.</p>
            </div>
        </div>
        <button type="button" class="cmp-more" id="cmpMore" hidden>Show more</button>
        <div id="cmpSavedReport" hidden></div>
    </div>

    {{-- The wait: Anee's face at work, shared by every AI run. --}}
    @include('sm.partials.anee-wait')
</div>
@endsection

@push('sheets')
<div class="sheet hidden cmp-sheet" id="cmpKindSheet" style="--sheet-width:27rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">What kind of report?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="cmp-sheet-sub">Counted across every season you can open. It takes two saved reports of a kind to compare.</p>
        <div class="dt-rows" id="cmpKindList"></div>
    </div>
</div>
<div class="sheet hidden cmp-sheet" id="cmpSeasonSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="cmpSeasonTitle">Which season?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="cmp-sheet-sub" id="cmpSeasonSub"></p>
        <div class="dt-rows" id="cmpSeasonList"></div>
    </div>
</div>
<div class="sheet hidden cmp-sheet" id="cmpPickSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="cmpPickTitle">Which report?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <p class="cmp-sheet-sub" id="cmpPickSub"></p>
        <div class="dt-rows" id="cmpPickList"></div>
    </div>
</div>
{{-- Rename a saved comparison, describe it. --}}
<div class="sheet hidden cmp-sheet" id="cmpMetaSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Name and description</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <label class="form-label" for="cmpMetaTitle">Name</label>
            <input type="text" id="cmpMetaTitle" class="form-input" maxlength="191">
        </div>
        <div>
            <label class="form-label" for="cmpMetaDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea id="cmpMetaDesc" class="form-textarea" rows="3" maxlength="2000" placeholder="What you wanted to learn from these two"></textarea>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="cmpMetaSave">Save changes</button>
    </div>
</div>
@endpush

@push('scripts')
{{-- Back: the dashboard, or the ?from= origin (App\Support\BackTo). --}}
<script>
(() => {
const __init = () => {
    const $id = (i) => document.getElementById(i);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
    const CAN_WRITE = @json($canWrite);
    const CAN_ASK = @json($canAsk);
    const ANEE = @json($aneeName);
    const FACE = @json($aneeFace);
    const KINDS = @json($kinds);
    const KIND = Object.fromEntries(KINDS.map((k) => [k.key, k]));
    const ICONS = Object.fromEntries(KINDS.map((k) => [k.key, k.icon]));
    const OPEN_ID = @json($openId);
    const PREF_SEASON = @json($seasonId);
    const U = {
        options: @json(route('sm.compare.options')),
        shelf: (sid, kind) => @json(route('sm.compare.shelf')) + '?scheduleId=' + encodeURIComponent(sid) + '&kind=' + encodeURIComponent(kind),
        gen: @json(route('sm.compare.generate')),
        job: (id) => @json(route('sm.compare.job', ['id' => '__ID__'])).replace('__ID__', id),
        saved: (page, q) => @json(route('sm.compare.saved')) + '?page=' + page + (q ? '&q=' + encodeURIComponent(q) : ''),
        one: (id) => @json(route('sm.compare.one', ['id' => '__ID__'])).replace('__ID__', id),
        meta: @json(route('sm.compare.meta')),
        del: (id) => @json(route('sm.compare.delete', ['id' => '__ID__'])).replace('__ID__', id),
        ai: @json(route('ai.index')),
        credits: @json(route('ai.credits')),
    };
    const SVG = {
        grid: @json($icoGrid),
        cal: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="18" height="17" rx="2.5"/><path d="M16 2.5v4M8 2.5v4M3 10h18"/></svg>',
        user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        filter: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>',
        sprout: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 20h10"/><path d="M10 20c5.5-2.5.8-6.4 3-10"/><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z"/><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"/></svg>',
        pen: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.414-9.414a2 2 0 1 1 2.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
        go: '<svg class="cmp-go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5l7 7-7 7"/></svg>',
        tick: '<svg class="dt-row-tick" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>',
    };
    const coin = (text) => `<span class="credit-coin"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9.2" fill="#f0b429" stroke="#c98a12" stroke-width="1.6"/><circle cx="12" cy="12" r="5" fill="none" stroke="#c98a12" stroke-width="1.3" opacity=".75"/></svg><b>${esc(text)}</b></span>`;
    const bone = (n) => Array.from({ length: n }, () => '<div class="cmp-bone"></div>').join('');
    const later = (ms) => new Promise((r) => setTimeout(r, ms));

    /* ================================ STATE ============================== */
    const S = {
        opts: null, optsReady: null, kind: null,
        a: { season: null, report: null }, b: { season: null, report: null },
        shelves: {}, picking: 'a', busy: false, viewing: null, savedDirty: true,
    };
    const L = { page: 1, q: '', rows: [], hasMore: false, total: 0, token: 0, loaded: false };
    const other = (side) => (side === 'a' ? 'b' : 'a');
    const seasonOf = (id) => (S.opts?.seasons || []).find((s) => s.id === id) || null;
    const countIn = (sid, kind) => Number(((seasonOf(sid) || {}).counts || {})[kind] || 0);

    /* ================================= TABS ============================== */
    function tab(which) {
        if (!CAN_WRITE) which = 'saved';
        const gen = which === 'gen';
        const tabs = $id('cmpTabs');
        if (tabs) {
            tabs.classList.toggle('is-saved', !gen);
            $id('cmpTabGen').classList.toggle('is-on', gen);
            $id('cmpTabSaved').classList.toggle('is-on', !gen);
            $id('cmpTabGen').setAttribute('aria-selected', gen ? 'true' : 'false');
            $id('cmpTabSaved').setAttribute('aria-selected', gen ? 'false' : 'true');
        }
        const show = gen ? $id('cmpGenPane') : $id('cmpSavedPane');
        const hide = gen ? $id('cmpSavedPane') : $id('cmpGenPane');
        if (hide) hide.hidden = true;
        if (show) {
            show.hidden = false;
            show.classList.remove('is-in');
            void show.offsetWidth;
            show.classList.add('is-in');
        }
        if (!gen && (S.savedDirty || !L.loaded)) loadSaved(true);
    }
    $id('cmpTabGen')?.addEventListener('click', () => tab('gen'));
    $id('cmpTabSaved')?.addEventListener('click', () => tab('saved'));

    /* =============================== OPTIONS ============================= */
    async function loadOptions() {
        try {
            const res = await api(U.options);
            S.opts = res.data;
        } catch (err) {
            S.opts = null;
            $id('cmpBones').hidden = true;
            $id('cmpNothingT').textContent = 'Your seasons did not load';
            $id('cmpNothingP').textContent = (err.message || 'Something went wrong.') + ' Pull down or reload the page to try again.';
            $id('cmpNothing').hidden = false;
            return;
        }
        paintWizard();
    }

    /* What each kind holds across the seasons: how many saved, in how many seasons. */
    function kindTotals() {
        const t = {};
        (S.opts?.seasons || []).forEach((s) => {
            Object.entries(s.counts || {}).forEach(([k, n]) => {
                t[k] = t[k] || { n: 0, seasons: 0 };
                t[k].n += Number(n) || 0;
                if (n > 0) t[k].seasons++;
            });
        });
        return t;
    }

    function paintWizard() {
        const totals = kindTotals();
        const any = Object.values(totals).some((t) => t.n >= 2);
        $id('cmpBones').hidden = true;
        if (!any) {
            if (!(S.opts?.seasons || []).length) {
                $id('cmpNothingT').textContent = 'No seasons to compare from yet';
                $id('cmpNothingP').textContent = 'Start a cropping schedule, save a report or two from its Reports, and come back to lay them side by side.';
            }
            reveal($id('cmpNothing'));
            return;
        }
        paintAi();
        reveal($id('cmpWizard'));
        paintAll();
    }

    function reveal(el) {
        if (!el) return;
        el.hidden = false;
        el.classList.remove('cmp-pane', 'is-in');
        void el.offsetWidth;
        el.classList.add('cmp-pane', 'is-in');
    }

    function paintAi() {
        const o = S.opts || {};
        const step = $id('cmpStepAi');
        step.hidden = !o.canUseAi;
        if (!o.canUseAi) { $id('cmpWithAi').checked = false; return; }
        $id('cmpPrice').textContent = o.price;
        const short = !o.unlimited && Number(o.balance) < Number(o.price);
        const bal = $id('cmpBal');
        const have = o.unlimited ? 'Unlimited' : Number(o.balance || 0).toLocaleString();
        const whose = o.payerIsMe ? 'You have' : 'The farm has';
        const coinHtml = o.payerIsMe && window.creditCoin ? window.creditCoin(have) : coin(have);
        bal.classList.toggle('is-short', short);
        bal.innerHTML = short
            ? `${whose} ${coinHtml} — not enough for her read.${o.payerIsMe ? ` <a href="${esc(U.credits)}?tab=buy">Get credits</a>` : ' The farm owner can top up.'}`
            : `${whose} ${coinHtml}`;
        const box = $id('cmpWithAi');
        box.disabled = short;
        if (short) box.checked = false;
        $id('cmpAi').classList.toggle('is-off', short);
        $id('cmpAi').classList.toggle('is-on', box.checked);
    }
    $id('cmpWithAi')?.addEventListener('change', () => { $id('cmpAi').classList.toggle('is-on', $id('cmpWithAi').checked); paintRun(); });

    async function refreshWallet() {
        try {
            const res = await api(U.options);
            if (S.opts) { S.opts.balance = res.data.balance; S.opts.unlimited = res.data.unlimited; }
            paintAi();
            paintRun();
        } catch (_) { /* the old figure stands */ }
    }

    /* ================================ PAINT ============================== */
    function setTag(btn, emojiOrHtml, text, none) {
        const e = btn.querySelector('[data-cmp-e]') || btn.querySelector('.crop-tag-e');
        const t = btn.querySelector('[data-cmp-t]') || btn.querySelector('.crop-tag-t');
        e.innerHTML = emojiOrHtml;
        t.textContent = text;
        t.classList.toggle('is-none', !!none);
    }

    function paintKind() {
        const k = S.kind ? KIND[S.kind] : null;
        setTag($id('cmpKindBtn'), k ? `<img src="${esc(k.icon)}" alt="">` : SVG.grid, k ? k.label : 'Choose the kind of report', !k);
        $id('cmpStepKind').classList.toggle('is-done', !!k);
    }

    function paintSide(side) {
        const L1 = side.toUpperCase();
        const card = $id('cmpSide' + L1);
        const sBtn = card.querySelector('[data-cmp-season]');
        const rBtn = card.querySelector('[data-cmp-report]');
        const st = S[side];
        const season = st.season ? seasonOf(st.season) : null;
        sBtn.disabled = !S.kind;
        setTag(sBtn, season ? esc(season.icon || '🌱') : SVG.sprout, season ? season.title : 'Choose the season', !season);
        rBtn.disabled = !S.kind || !season;
        const k = S.kind ? KIND[S.kind] : null;
        setTag(rBtn, st.report && k ? `<img src="${esc(k.icon)}" alt="">` : SVG.grid, st.report ? st.report.title : 'Choose the report', !st.report);
        const r = st.report;
        card.querySelector('[data-cmp-meta]').innerHTML = r ? [
            r.when ? `<span class="cmp-mini">${SVG.cal}${esc(r.when)}</span>` : '',
            r.author ? `<span class="cmp-mini">${SVG.user}${esc(r.author)}</span>` : '',
            r.filtered ? `<span class="cmp-mini">${SVG.filter}Filtered</span>` : '',
        ].join('') : '';
        card.classList.toggle('is-done', !!r);
    }

    /* The next thing to press glows, gently -- once. */
    function paintNext() {
        document.querySelectorAll('#cmpWizard .crop-tag.is-next').forEach((b) => b.classList.remove('is-next'));
        let next = null;
        if (!S.kind) next = $id('cmpKindBtn');
        else {
            for (const side of ['a', 'b']) {
                const card = $id('cmpSide' + side.toUpperCase());
                if (!S[side].season) { next = card.querySelector('[data-cmp-season]'); break; }
                if (!S[side].report) { next = card.querySelector('[data-cmp-report]'); break; }
            }
        }
        if (next) next.classList.add('is-next');
    }

    function paintRun() {
        const ready = !!(S.kind && S.a.report && S.b.report);
        const withAi = aiOn();
        const btn = $id('cmpRun');
        btn.disabled = !ready || S.busy;
        $id('cmpRunT').innerHTML = withAi ? `Compare with ${esc(ANEE)}’s read ${coin(String(S.opts?.price ?? ''))}` : 'Compare them';
        $id('cmpStepPick').classList.toggle('is-done', !!(S.a.report && S.b.report));
        const hint = !S.kind ? 'Choose the kind of report first.'
            : (!S.a.report && !S.b.report ? 'Now pick Report A and Report B.'
            : (!S.a.report ? 'Now pick Report A.' : (!S.b.report ? 'Now pick Report B.' : (withAi ? 'Saved to the Saved tab the moment she finishes.' : 'Free — saved to the Saved tab.'))));
        $id('cmpHint').textContent = hint;
    }

    function paintAll() {
        paintKind();
        paintSide('a');
        paintSide('b');
        paintRun();
        paintNext();
    }

    const aiOn = () => !!(S.opts?.canUseAi && $id('cmpWithAi')?.checked && !$id('cmpStepAi')?.hidden);

    /* ================================ KIND =============================== */
    function openKind() {
        if (!S.opts) return;
        const totals = kindTotals();
        $id('cmpKindList').innerHTML = KINDS.map((k, n) => {
            const t = totals[k.key] || { n: 0, seasons: 0 };
            const off = t.n < 2;
            const say = t.n === 0 ? 'None saved yet'
                : `${t.n} saved in ${t.seasons} season${t.seasons === 1 ? '' : 's'}${t.n < 2 ? ' — it takes two to compare' : ''}`;
            return `<button type="button" class="dt-row${S.kind === k.key ? ' is-on' : ''}" data-kind="${esc(k.key)}" style="--i:${n}" ${off ? 'disabled' : ''}>
                <span class="dt-row-e"><img src="${esc(k.icon)}" alt=""></span>
                <span class="dt-row-body"><b>${esc(k.label)}</b><i>${esc(say)}</i></span>
                ${SVG.tick}
            </button>`;
        }).join('');
        openSheet('cmpKindSheet');
    }
    $id('cmpKindBtn')?.addEventListener('click', openKind);
    $id('cmpKindList')?.addEventListener('click', (e) => {
        const row = e.target.closest('[data-kind]');
        if (!row || row.disabled) return;
        closeSheet('cmpKindSheet');
        pickKind(row.dataset.kind);
    });

    function pickKind(k) {
        if (k === S.kind) return;
        S.kind = k;
        ['a', 'b'].forEach((side) => {
            S[side].report = null;
            if (S[side].season && !countIn(S[side].season, k)) S[side].season = null;
        });
        // One season holds every saved report of this kind: both sides start there.
        const holding = (S.opts?.seasons || []).filter((s) => countIn(s.id, k) > 0);
        if (holding.length === 1 && countIn(holding[0].id, k) >= 2) {
            S.a.season = S.a.season || holding[0].id;
            S.b.season = S.b.season || holding[0].id;
        }
        // Arrived from a season's old Compare door: Report A starts there.
        if (PREF_SEASON && countIn(PREF_SEASON, k) > 0 && !S.a.season) S.a.season = PREF_SEASON;
        paintAll();
    }

    /* =============================== SEASON ============================== */
    function openSeason(side) {
        if (!S.kind || !S.opts) return;
        S.picking = side;
        const k = KIND[S.kind];
        $id('cmpSeasonTitle').textContent = `Report ${side.toUpperCase()} — which season?`;
        $id('cmpSeasonSub').textContent = `Every cropping schedule you can open, with how many ${k.label}s are saved on it. Closed and archived seasons are here too.`;
        const seasons = (S.opts.seasons || []).slice().sort((x, y) => (countIn(y.id, S.kind) > 0) - (countIn(x.id, S.kind) > 0));
        const taken = S[other(side)].report;
        $id('cmpSeasonList').innerHTML = seasons.map((s, n) => {
            const count = countIn(s.id, S.kind);
            // The one report the other side already took is not on offer twice.
            const left = count - (taken && S[other(side)].season === s.id ? 1 : 0);
            const off = left <= 0;
            const say = count === 0 ? `No ${k.label} saved here`
                : (left <= 0 ? `Its only ${k.label} is already Report ${other(side).toUpperCase()}` : `${count} saved`);
            const bits = [s.statusLabel, s.since ? 'since ' + s.since : null, s.crop].filter(Boolean).join(' · ');
            return `<button type="button" class="dt-row${S[side].season === s.id ? ' is-on' : ''}" data-season="${s.id}" style="--i:${n}" ${off ? 'disabled' : ''}>
                <span class="dt-row-e">${esc(s.icon || '🌱')}</span>
                <span class="dt-row-body"><b>${esc(s.title)}</b><i>${esc(say)}</i><i class="d">${esc(bits)}</i></span>
                ${SVG.tick}
            </button>`;
        }).join('') || '<p class="text-sm text-gray-400 text-center py-6">No seasons to show.</p>';
        openSheet('cmpSeasonSheet');
    }
    document.querySelectorAll('[data-cmp-season]').forEach((b) => b.addEventListener('click', () => openSeason(b.dataset.cmpSeason)));
    $id('cmpSeasonList')?.addEventListener('click', (e) => {
        const row = e.target.closest('[data-season]');
        if (!row || row.disabled) return;
        const side = S.picking;
        const id = Number(row.dataset.season);
        if (S[side].season !== id) {
            S[side].season = id;
            S[side].report = null;
        }
        paintAll();
        // Straight on to the report: the new sheet rises over the old one,
        // so the backdrop never blinks between them.
        openReport(side);
        setTimeout(() => closeSheet('cmpSeasonSheet'), 60);
    });

    /* =============================== REPORT ============================== */
    async function shelf(sid, kind) {
        const key = sid + ':' + kind;
        if (!S.shelves[key]) {
            const res = await api(U.shelf(sid, kind));
            S.shelves[key] = res.data.rows || [];
        }
        return S.shelves[key];
    }

    async function openReport(side) {
        const st = S[side];
        if (!S.kind || !st.season) return;
        S.picking = side;
        const k = KIND[S.kind];
        const season = seasonOf(st.season);
        $id('cmpPickTitle').textContent = `Report ${side.toUpperCase()} — ${k.label}`;
        $id('cmpPickSub').textContent = `Saved on the shelf of “${season ? season.title : 'this season'}”, newest first.`;
        const list = $id('cmpPickList');
        list.innerHTML = `<div class="cmp-bones">${bone(3)}</div>`;
        openSheet('cmpPickSheet');
        let rows;
        try { rows = await shelf(st.season, S.kind); }
        catch (err) { list.innerHTML = `<p class="text-sm text-gray-500 text-center py-6">${esc(err.message || 'The shelf did not load.')}</p>`; return; }
        if (S.picking !== side) return;
        const taken = S[other(side)].report;
        list.innerHTML = rows.map((r, n) => {
            const dup = taken && taken.id === r.id;
            const bits = [r.when, r.author ? 'by ' + r.author : null, r.filtered ? 'filtered' : null].filter(Boolean).join(' · ');
            return `<button type="button" class="dt-row${st.report && st.report.id === r.id ? ' is-on' : ''}" data-report="${r.id}" style="--i:${n}" ${dup ? 'disabled' : ''}>
                <span class="dt-row-e"><img src="${esc(k.icon)}" alt=""></span>
                <span class="dt-row-body"><b>${esc(r.title)}</b><i>${esc(dup ? 'Already Report ' + other(side).toUpperCase() : bits)}</i>${r.description ? `<i class="d">${esc(r.description)}</i>` : ''}</span>
                ${SVG.tick}
            </button>`;
        }).join('') || `<p class="text-sm text-gray-500 text-center py-6">No saved ${esc(k.label)} on this season's shelf.</p>`;
    }
    document.querySelectorAll('[data-cmp-report]').forEach((b) => b.addEventListener('click', () => openReport(b.dataset.cmpReport)));
    $id('cmpPickList')?.addEventListener('click', (e) => {
        const row = e.target.closest('[data-report]');
        if (!row || row.disabled) return;
        const side = S.picking;
        const rows = S.shelves[S[side].season + ':' + S.kind] || [];
        S[side].report = rows.find((r) => r.id === Number(row.dataset.report)) || null;
        closeSheet('cmpPickSheet');
        paintAll();
    });

    /* ================================= RUN =============================== */
    async function run() {
        if (S.busy || !S.a.report || !S.b.report) return;
        const withAi = aiOn();
        const price = S.opts?.price;
        S.busy = true;
        paintRun();
        if (withAi) window.aneeWait.show({ title: `${ANEE} is reading both reports…`, lines: ['Lining the two up, figure by figure…', 'Weighing what changed between them…', 'Finding the strengths of each…', 'Writing what to carry forward…'], sub: 'Half a minute, usually.' });
        let landed = false;
        try {
            const res = await api(U.gen, { method: 'POST', body: { aId: S.a.report.id, bId: S.b.report.id, withAi: withAi ? 1 : 0 } });
            let data = res.data || {};
            if (data.pending) data = await window.aneeWait.poll({ id: data.id, job: U.job, phases: window.aneeWait.phases.plain, limit: 200 });
            landed = true;
            if (withAi) await window.aneeWait.done({ title: 'Done!', line: `${price} credits used — saved to the shelf.` });
            toast(withAi ? `Done — ${price} credits used. Saved to the shelf.` : 'Comparison saved to the shelf.');
            S.savedDirty = true;
            await showResult(data, 'fresh');
            if (withAi) refreshWallet();
        } catch (err) {
            if (!err.tierLock) toast(err.message || 'The comparison could not be made.', 'error');
        } finally {
            S.busy = false;
            paintRun();
            if (withAi && !landed) window.aneeWait.fail();
        }
    }
    $id('cmpRun')?.addEventListener('click', run);

    /* ============================ THE RESULT ============================= */
    function drawOpts() {
        return {
            face: FACE, anee: ANEE, kinds: ICONS,
            price: S.opts?.price,
            canAddAnee: CAN_WRITE && !!S.opts?.canUseAi,
            onAddAnee: addAnee,
        };
    }

    function actionsFor(data) {
        const acts = [];
        if (data.mine && CAN_ASK) acts.push({ label: 'Ask ' + ANEE + ' about it', face: FACE, kind: 'primary', href: U.ai + '?freport=' + data.id });
        if (data.mine && CAN_WRITE) {
            acts.push({ label: 'Name & description', icon: 'pen', onClick: () => openMeta({ id: data.id, title: data.title, description: data.description }) });
            acts.push({ label: 'Delete', icon: 'trash', kind: 'danger', onClick: () => removeViewing() });
        }
        acts.push({ label: 'Close', icon: 'close', onClick: () => window.reportView.close() });
        return acts;
    }

    async function showResult(data, mode) {
        if (S.optsReady) { try { await S.optsReady; } catch (_) { /* drawn without the door */ } }
        const host = mode === 'fresh' ? $id('cmpReport') : $id('cmpSavedReport');
        window.cmpResult.draw(host, data, drawOpts());
        S.viewing = { id: data.id, mode, data };
        window.reportView.open({
            title: data.title || 'Comparison',
            node: host,
            actions: actionsFor(data),
            onClose: () => viewClosed(mode),
        });
        window.cmpResult.play(host);
    }

    function viewClosed(mode) {
        S.viewing = null;
        const fromAddress = new URLSearchParams(location.search).has('open');
        if (fromAddress) {
            // Only ?open= goes: ?from= is where the back arrow leads, and a
            // refresh should still know it.
            const keep = new URLSearchParams(location.search);
            keep.delete('open');
            const rest = keep.toString();
            try { history.replaceState(history.state, '', location.pathname + (rest ? '?' + rest : '')); } catch (_) { /* the address keeps it */ }
        }
        // A fresh one, or one the address opened, closes onto the shelf it lives on.
        if ((mode === 'fresh' || fromAddress) && CAN_WRITE && $id('cmpSavedPane').hidden) tab('saved');
        else if (S.savedDirty) loadSaved(true);
    }

    async function removeViewing() {
        const v = S.viewing;
        if (!v) return;
        const ok = await window.confirmAction({ title: 'Delete this comparison?', message: 'It leaves the Saved shelf. The two reports it compared stay where they are.', detail: v.data.credits > 0 ? 'The credits it used are already spent.' : '', confirmText: 'Delete' });
        if (!ok) return;
        try {
            await api(U.del(v.id), { method: 'DELETE' });
            toast('Comparison removed.');
            S.savedDirty = true;
            window.reportView.close();
        } catch (err) { toast(err.message, 'error'); }
    }

    /* Anee's read, asked for afterwards: the same two, a new comparison with
       her read in it -- and the free copy leaves the shelf once it lands. */
    async function addAnee(data, btn) {
        const rep = data.report || {};
        if (!rep.a?.id || !rep.b?.id) return;
        const o = S.opts || {};
        if (!o.unlimited && Number(o.balance) < Number(o.price)) {
            toast(`${ANEE}’s read costs ${o.price} credits — ${o.payerIsMe ? 'you have' : 'the farm has'} ${Math.floor(Number(o.balance) || 0)}.`, 'error');
            return;
        }
        const ok = await window.confirmAction({
            title: `Add ${ANEE}’s read?`,
            message: `She reads both reports and writes what changed, the strengths of each, and what to carry forward. It costs ${o.price} credits.`,
            confirmText: 'Add her read', confirmClass: 'btn-primary',
        });
        if (!ok) return;
        btn.disabled = true;
        window.aneeWait.show({ title: `${ANEE} is reading both reports…`, lines: ['Lining the two up, figure by figure…', 'Weighing what changed between them…', 'Finding the strengths of each…'], sub: 'Half a minute, usually.' });
        let landed = false;
        try {
            const res = await api(U.gen, { method: 'POST', body: { aId: rep.a.id, bId: rep.b.id, withAi: 1, replaces: data.mine && !(data.credits > 0) ? data.id : 0 } });
            let nd = res.data || {};
            if (nd.pending) nd = await window.aneeWait.poll({ id: nd.id, job: U.job, phases: window.aneeWait.phases.plain, limit: 200 });
            landed = true;
            await window.aneeWait.done({ title: 'Done!', line: `${o.price} credits used — saved to the shelf.` });
            S.savedDirty = true;
            const mode = S.viewing ? S.viewing.mode : 'saved';
            const host = mode === 'fresh' ? $id('cmpReport') : $id('cmpSavedReport');
            window.cmpResult.draw(host, nd, drawOpts());
            S.viewing = { id: nd.id, mode, data: nd };
            window.reportView.setTitle(nd.title || 'Comparison');
            if (typeof window.reportView.actions === 'function') window.reportView.actions(actionsFor(nd));
            host.closest('.va-view')?.scrollTo({ top: 0, behavior: 'smooth' });
            window.cmpResult.play(host);
            refreshWallet();
        } catch (err) {
            if (!err.tierLock) toast(err.message || 'Her read could not be made.', 'error');
            if (btn.isConnected) btn.disabled = false;
        } finally {
            if (!landed) window.aneeWait.fail();
        }
    }

    /* ============================ NAME & SAY ============================= */
    let META_ID = null;
    function openMeta(row) {
        META_ID = row.id;
        $id('cmpMetaTitle').value = row.title || '';
        $id('cmpMetaDesc').value = row.description || '';
        openSheet('cmpMetaSheet');
    }
    $id('cmpMetaSave')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        if (META_ID === null) return;
        const title = $id('cmpMetaTitle').value.trim();
        if (!title) { toast('Give it a name.', 'error'); return; }
        btn.disabled = true;
        try {
            const res = await api(U.meta, { method: 'POST', body: { id: META_ID, title, description: $id('cmpMetaDesc').value.trim() } });
            toast(res.message || 'Saved.');
            closeSheet('cmpMetaSheet');
            const d = res.data || {};
            if (S.viewing && S.viewing.id === META_ID) {
                S.viewing.data.title = d.title;
                S.viewing.data.description = d.description;
                window.reportView.setTitle(d.title);
                const host = S.viewing.mode === 'fresh' ? $id('cmpReport') : $id('cmpSavedReport');
                const t = host.querySelector('.cx-title');
                if (t) t.textContent = d.title;
                let desc = host.querySelector('.cx-desc');
                if (d.description) {
                    if (!desc && t) { desc = document.createElement('p'); desc.className = 'cx-desc'; t.after(desc); }
                    if (desc) desc.textContent = d.description;
                } else if (desc) desc.remove();
                if (typeof window.reportView.actions === 'function') window.reportView.actions(actionsFor(S.viewing.data));
            }
            const row = L.rows.find((r) => r.id === META_ID);
            if (row) { row.title = d.title; row.description = d.description; paintSaved('quiet'); }
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    });

    /* ================================ SAVED ============================== */
    async function loadSaved(reset) {
        const token = ++L.token;
        if (reset) {
            L.page = 1;
            $id('cmpRowBones').hidden = false;
            $id('cmpRows').innerHTML = '';
            $id('cmpEmpty').hidden = true;
            $id('cmpNone').hidden = true;
            $id('cmpMore').hidden = true;
        }
        try {
            const res = await api(U.saved(L.page, L.q));
            if (token !== L.token) return;
            const d = res.data || {};
            const from = reset ? 0 : L.rows.length;
            L.rows = reset ? (d.rows || []) : L.rows.concat(d.rows || []);
            L.hasMore = !!d.hasMore;
            L.total = Number(d.total) || 0;
            L.loaded = true;
            S.savedDirty = false;
            paintSaved(reset ? 'all' : 'append', from);
        } catch (err) {
            if (token !== L.token) return;
            $id('cmpRowBones').hidden = true;
            toast(err.message || 'The shelf did not load.', 'error');
        }
    }

    /* how: 'all' draws the shelf afresh (rows rise in turn), 'append' adds
       the next page under what is there, 'quiet' redraws without the rise
       (a rename should not make the whole shelf dance). */
    function paintSaved(how = 'all', from = 0) {
        $id('cmpRowBones').hidden = true;
        const none = L.rows.length === 0;
        $id('cmpEmpty').hidden = !(none && !L.q);
        $id('cmpNone').hidden = !(none && L.q);
        $id('cmpMore').hidden = !L.hasMore;
        $id('cmpSearchWrap').hidden = !(L.q || L.total > 3);
        const count = $id('cmpCount');
        if (count && !L.q) { count.textContent = String(L.total); count.hidden = L.total === 0; }
        const box = $id('cmpRows');
        box.classList.toggle('is-quiet', how === 'quiet');
        if (how === 'append') {
            box.insertAdjacentHTML('beforeend', L.rows.slice(from).map((r, n) => rowHtml(r, n)).join(''));
            return;
        }
        box.innerHTML = L.rows.map((r, n) => rowHtml(r, n)).join('');
    }
    function rowHtml(r, n) {
        const icon = ICONS[r.kind];
        const small = [r.when, r.author ? 'by ' + r.author : null, r.description].filter(Boolean).join(' · ');
        return `<div class="cmp-row" role="button" tabindex="0" data-open="${r.id}" style="--i:${Math.min(n, 12)}">
            <span class="cmp-row-ico">${icon ? `<img src="${esc(icon)}" alt="">` : SVG.grid}${r.anee ? `<img class="face" src="${esc(FACE)}" alt="">` : ''}</span>
            <span class="cmp-row-t">
                <b>${esc(r.title)}</b>
                <span class="cmp-row-tags">
                    <span class="cmp-tag"><span>${esc(r.kindLabel || 'Report')}</span></span>
                    ${(r.seasons || []).map((sn) => `<span class="cmp-tag is-season" title="${esc(sn)}">${SVG.sprout}<span>${esc(sn)}</span></span>`).join('')}
                    ${r.anee ? `<span class="cmp-tag is-anee"><span>${esc(ANEE)}’s read</span></span>` : ''}
                </span>
                ${small ? `<small>${esc(small)}</small>` : ''}
            </span>
            ${r.mine && CAN_WRITE ? `<button type="button" class="cmp-pen" data-meta="${r.id}" title="Name and description" aria-label="Rename ${esc(r.title)}">${SVG.pen}</button>` : ''}
            ${SVG.go}
        </div>`;
    }

    async function openSaved(id, rowEl) {
        rowEl?.classList.add('is-busy');
        try {
            const res = await api(U.one(id));
            await showResult(res.data, 'saved');
        } catch (err) { toast(err.message || 'That comparison could not be opened.', 'error'); }
        finally { rowEl?.classList.remove('is-busy'); }
    }
    $id('cmpRows')?.addEventListener('click', (e) => {
        const pen = e.target.closest('[data-meta]');
        if (pen) {
            e.stopPropagation();
            const r = L.rows.find((x) => x.id === Number(pen.dataset.meta));
            if (r) openMeta(r);
            return;
        }
        const row = e.target.closest('[data-open]');
        if (row) openSaved(Number(row.dataset.open), row);
    });
    $id('cmpRows')?.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        const row = e.target.closest('[data-open]');
        if (!row || e.target.closest('[data-meta]')) return;
        e.preventDefault();
        openSaved(Number(row.dataset.open), row);
    });
    $id('cmpMore')?.addEventListener('click', () => { L.page++; loadSaved(false); });
    let qTimer = null;
    $id('cmpQ')?.addEventListener('input', () => {
        clearTimeout(qTimer);
        qTimer = setTimeout(() => {
            const q = $id('cmpQ').value.trim();
            if (q === L.q) return;
            L.q = q;
            loadSaved(true);
        }, 280);
    });

    /* ================================ START ============================== */
    if (CAN_WRITE) {
        S.optsReady = loadOptions();
        tab('gen');
    } else {
        tab('saved');
    }
    // A tag shelf, a bookmark or the old door names one saved comparison.
    if (OPEN_ID > 0) openSaved(OPEN_ID, null);
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
