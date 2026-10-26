@extends('layouts.app')
@section('title', 'Market Price Forecast')
@section('page-title', 'Market Price Forecast')
@section('page-subtitle', 'When to sell your harvest')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))
@section('body-class', 'hide-tabbar no-footer')

{{-- Market Price Forecast (2026-10-27, the owner: "select the crop, and will
     give the potential forecast of the upcoming price for the year and next
     year based on the historical data ... the main goal here is to show when
     to sell or trade the output of crops based on its market price").

     A crop and a place: its farmgate price these years, the forecast to the
     end of next year with the likely range, the months of its year, whether
     to sell at harvest or keep it, the trader's price beside the farmer's,
     the imports and policies that moved it, and the latest local prices.
     App\Support\MarketBook works it out; the page draws it. No credits.
     The charts follow one palette: the price in blue, the forecast in
     orange, a month above its average in blue and below it in red, each
     with a label or a table beside it, never color alone. --}}
@push('head')
<style>
    .mp-wrap { --mp-e: cubic-bezier(.22,1,.36,1); --mp-a: #2a78d6; --mp-f: #eb6834; --mp-up: #2a78d6; --mp-dn: #e34948; --mp-grid: #e1e0d9; --mp-axis: #898781;
        --mp-ink: #0b0b0b; --mp-ink2: #52514e; --mp-surface: #fcfcfb; max-width: 72rem; margin: 0 auto; padding-bottom: calc(2rem + env(safe-area-inset-bottom)); }
    html.dark .mp-wrap { --mp-a: #3987e5; --mp-f: #d95926; --mp-up: #3987e5; --mp-dn: #e66767; --mp-grid: #2c2c2a; --mp-ink: #f0efec; --mp-ink2: #c3c2b7; --mp-surface: #151b12; }
    .mp-wrap [hidden] { display: none !important; }

    /* ---- the hero ---- */
    .mp-hero { position: relative; overflow: hidden; display: flex; gap: 1rem; align-items: center; padding: 1.1rem 1.2rem 1.25rem; border-radius: 1.4rem; color: #fff8ee;
        background: radial-gradient(120% 150% at 100% 0%, #f0a24b 0%, #b8641f 42%, #4a2a0e 100%); }
    .mp-hero::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 5px; background: linear-gradient(90deg, #2a78d6, #eb6834); }
    .mp-art { flex: none; width: 5.4rem; height: 5.4rem; }
    .mp-art .mp-spark { stroke-dasharray: 120; stroke-dashoffset: 120; animation: mpDraw 2.4s var(--mp-e) .3s forwards; }
    @keyframes mpDraw { to { stroke-dashoffset: 0; } }
    .mp-hero h2 { font-family: var(--font-heading); font-size: 1.22rem; font-weight: 800; line-height: 1.2; color: #fff; }
    .mp-hero p { margin-top: .3rem; font-size: .88rem; line-height: 1.5; color: #ffe8cf; }

    /* ---- the tabs ---- */
    .mp-tabs { position: relative; display: grid; grid-template-columns: 1fr 1fr; padding: .3rem; margin-top: 1rem; border-radius: 1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .mp-tab-ind { position: absolute; top: .3rem; bottom: .3rem; left: .3rem; width: calc(50% - .3rem); border-radius: .75rem; background: #4a7c2a; transition: transform .28s var(--mp-e); }
    .mp-tabs.is-saved .mp-tab-ind { transform: translateX(100%); }
    .mp-tab { position: relative; z-index: 1; min-height: 2.6rem; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; font-weight: 800; font-size: .9rem; color: var(--color-gray-600); transition: color .28s var(--mp-e); }
    .mp-tab[aria-selected="true"] { color: #fff; }
    .mp-tab-n { min-width: 1.35rem; padding: .05rem .4rem; border-radius: 999px; font-size: .7rem; font-weight: 900; color: #2d5016; background: #e4efd4; }
    .mp-tab-n:empty { display: none; }

    /* ---- the questions ---- */
    .mp-qs { display: grid; gap: .75rem; margin-top: 1rem; }
    @media (min-width: 900px) { .mp-qs { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .mp-q { padding: .9rem 1rem 1rem; border-radius: 1.2rem; background: var(--color-white); border: 1px solid var(--color-gray-200); min-width: 0; }
    .mp-q h3 { display: flex; align-items: center; gap: .5rem; font-family: var(--font-heading); font-size: .98rem; font-weight: 800; color: var(--color-gray-900); }
    .mp-q h3 i { flex: none; width: 1.8rem; height: 1.8rem; border-radius: .65rem; display: grid; place-items: center; font-style: normal; font-size: .8rem; font-weight: 900; color: #7a3d0b; background: #fdebd7; }
    .mp-q h3 small { font-family: inherit; font-size: .72rem; font-weight: 700; color: var(--color-gray-400); }
    .mp-tag { display: flex; align-items: center; justify-content: space-between; gap: .5rem; width: 100%; min-height: 2.9rem; margin-top: .65rem; padding: 0 .8rem; border-radius: .85rem;
        border: 1.5px solid var(--color-gray-200); background: var(--color-gray-50, #f9fafb); font-size: .88rem; font-weight: 800; color: var(--color-gray-800); text-align: left;
        transition: border-color .28s var(--mp-e), transform .28s var(--mp-e); }
    .mp-tag:hover { border-color: #e0a15c; transform: translateY(-1px); }
    .mp-tag > span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mp-tag.is-none > span { color: var(--color-gray-500); font-weight: 700; }
    .mp-tag svg { flex: none; width: .85rem; height: .85rem; color: var(--color-gray-400); }
    .mp-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .55rem; }
    .mp-chip { padding: .38rem .7rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: var(--color-gray-700); background: var(--color-white); border: 1.5px solid var(--color-gray-200);
        transition: background-color .28s var(--mp-e), border-color .28s var(--mp-e), color .28s var(--mp-e); }
    .mp-chip.is-on { color: #fff; background: #b8641f; border-color: #b8641f; }
    .mp-in { position: relative; display: flex; align-items: center; margin-top: .55rem; }
    .mp-in input { width: 100%; min-width: 0; padding-right: 3.4rem; font-variant-numeric: tabular-nums; }
    .mp-in em { position: absolute; right: .75rem; font-style: normal; font-size: .78rem; font-weight: 800; color: var(--color-gray-400); pointer-events: none; }
    .mp-hint { margin-top: .4rem; font-size: .74rem; line-height: 1.45; color: var(--color-gray-500); }

    /* ---- the answer ---- */
    .mp-out { margin-top: 1rem; transition: opacity .28s var(--mp-e); }
    .mp-out.is-busy { opacity: .45; pointer-events: none; }
    .mp-empty { display: flex; gap: .8rem; align-items: center; margin-top: 1rem; padding: 1.1rem; border-radius: 1.25rem; border: 1.5px dashed var(--color-gray-300, #d1d5db); color: var(--color-gray-500); font-size: .86rem; line-height: 1.5; background: var(--color-white); }
    .mp-empty svg { flex: none; width: 2.6rem; height: 2.6rem; }
    .mp-saved-note { display: flex; flex-wrap: wrap; gap: .5rem .8rem; align-items: center; justify-content: space-between; padding: .7rem .9rem; margin-bottom: .8rem; border-radius: 1rem; background: #fff6e8; border: 1px solid #f6d9ae; font-size: .8rem; color: #7a3d0b; }
    .mp-saved-note button { font-weight: 800; text-decoration: underline; text-underline-offset: 2px; }
    .mp-sum { position: relative; overflow: hidden; padding: 1.1rem 1.15rem; border-radius: 1.35rem; color: #fff; background: linear-gradient(135deg, #1d4f8c, #0f2747); box-shadow: 0 18px 40px -26px rgb(15 39 71 / .9); animation: mpIn .45s var(--mp-e) both; }
    .mp-sum small { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; opacity: .85; }
    .mp-sum b { display: block; margin-top: .2rem; font-family: var(--font-heading); font-size: clamp(1.08rem, 3.4vw, 1.3rem); font-weight: 800; line-height: 1.3; }
    .mp-sum p { margin-top: .45rem; font-size: .84rem; line-height: 1.55; color: #d9e7f8; }
    .mp-sum .mp-where { display: inline-flex; margin-top: .6rem; padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; background: rgb(255 255 255 / .14); }
    @keyframes mpIn { from { opacity: 0; transform: translateY(8px); } }

    .mp-tiles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; margin-top: .75rem; }
    @media (min-width: 900px) { .mp-tiles { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .mp-tile { padding: .85rem .9rem; border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); min-width: 0; animation: mpIn .45s var(--mp-e) both; }
    .mp-tile > span { display: block; font-size: .72rem; font-weight: 800; color: var(--color-gray-500); }
    .mp-tile > b { display: block; margin-top: .2rem; font-family: var(--font-heading); font-size: clamp(1.25rem, 4vw, 1.6rem); font-weight: 800; line-height: 1.1; color: var(--color-gray-900); }
    .mp-tile > b small { font-size: .72rem; font-weight: 800; color: var(--color-gray-500); }
    .mp-tile > em { display: block; margin-top: .25rem; font-style: normal; font-size: .72rem; line-height: 1.4; color: var(--color-gray-500); }
    .mp-tile > em i { font-style: normal; font-weight: 800; color: var(--color-gray-700); }

    .mp-card { margin-top: .75rem; padding: 1rem 1rem 1.05rem; border-radius: 1.25rem; background: var(--color-white); border: 1px solid var(--color-gray-200); animation: mpIn .45s var(--mp-e) both; min-width: 0; }
    .mp-card h3 { font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; color: var(--color-gray-900); }
    .mp-card h3 + p, .mp-card .mp-sub { margin-top: .2rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-500); }
    .mp-two { display: grid; gap: 0; }
    @media (min-width: 1024px) { .mp-two { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: .75rem; } }

    /* ---- the charts ---- */
    .mp-legend { display: flex; flex-wrap: wrap; gap: .3rem 1rem; margin-top: .6rem; font-size: .74rem; color: var(--mp-ink2); }
    .mp-legend span { display: inline-flex; align-items: center; gap: .4rem; }
    .mp-legend i { display: inline-block; width: 1rem; height: 2px; border-radius: 2px; }
    .mp-legend i.is-band { height: .7rem; border-radius: 3px; opacity: .18; }
    .mp-legend i.is-box { width: .7rem; height: .7rem; border-radius: 3px; }
    .mp-chart { position: relative; margin-top: .5rem; outline: none; border-radius: .6rem; }
    .mp-chart:focus-visible { box-shadow: 0 0 0 2px #e0a15c; }
    .mp-chart svg { display: block; width: 100%; height: auto; overflow: visible; }
    .mp-chart text { font-family: inherit; font-size: 11px; fill: var(--mp-axis); }
    .mp-chart text.is-label { fill: var(--mp-ink); font-weight: 700; }
    .mp-chart .mp-g { stroke: var(--mp-grid); stroke-width: 1; shape-rendering: crispEdges; }
    .mp-chart .mp-line { fill: none; stroke-width: 2; stroke-linejoin: round; stroke-linecap: round; }
    .mp-chart .mp-draw { transition: stroke-dashoffset 1.2s var(--mp-e); }
    .mp-chart .mp-band { transition: opacity .9s var(--mp-e) .7s; }
    .mp-chart .mp-col { transition: transform .7s var(--mp-e), opacity .28s var(--mp-e); transform-box: fill-box; }
    .mp-chart .mp-col:hover, .mp-chart .mp-col:focus { opacity: .8; outline: none; }
    .mp-chart .mp-dot { stroke: var(--mp-surface); stroke-width: 2; }
    .mp-chart .mp-cross { stroke: var(--mp-axis); stroke-width: 1; pointer-events: none; }
    .mp-tip { position: absolute; z-index: 5; min-width: 9rem; max-width: 15rem; padding: .5rem .65rem; border-radius: .7rem; pointer-events: none; font-size: .74rem; line-height: 1.4;
        background: var(--color-white); color: var(--color-gray-700); border: 1px solid var(--color-gray-200); box-shadow: 0 12px 28px -16px rgb(0 0 0 / .45); transition: opacity .15s; }
    .mp-tip b { display: block; font-size: .92rem; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }
    .mp-tip i { display: inline-block; width: .8rem; height: 2px; margin-right: .3rem; vertical-align: middle; border-radius: 2px; }
    .mp-tip small { display: block; color: var(--color-gray-500); }

    .mp-pills { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .65rem; }
    .mp-pill { display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 700; background: var(--color-gray-50, #f9fafb); border: 1px solid var(--color-gray-200); color: var(--color-gray-700); }
    .mp-pill i { width: .55rem; height: .55rem; border-radius: 2px; }
    .mp-note { margin-top: .6rem; font-size: .8rem; line-height: 1.55; color: var(--color-gray-600); }
    .mp-note b { color: var(--color-gray-900); }

    /* ---- sell now or keep ---- */
    .mp-plan { display: grid; gap: .35rem; margin-top: .7rem; }
    .mp-prow { display: grid; grid-template-columns: 4.6rem minmax(0, 1fr) auto; align-items: center; gap: .5rem; font-size: .76rem; color: var(--color-gray-600); }
    .mp-prow > b { font-weight: 800; color: var(--color-gray-800); }
    .mp-prow .mp-bar { position: relative; height: 1.15rem; border-radius: 4px; background: var(--color-gray-100, #f3f4f6); overflow: hidden; }
    .mp-prow .mp-bar span { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 0 4px 4px 0; background: var(--mp-a); opacity: .55; transform-origin: left; transition: transform .7s var(--mp-e); }
    .mp-prow.is-best .mp-bar span { opacity: 1; }
    .mp-prow.is-harvest .mp-bar span { background: var(--mp-f); opacity: .85; }
    .mp-prow > em { font-style: normal; font-weight: 800; color: var(--color-gray-800); font-variant-numeric: tabular-nums; text-align: right; }
    .mp-prow small { font-weight: 700; color: var(--color-gray-500); }
    .mp-big { margin-top: .6rem; padding: .8rem .9rem; border-radius: 1rem; background: #f2f8ee; border: 1px solid #d6e8c6; font-size: .84rem; line-height: 1.55; color: #24420f; }
    .mp-big b { font-size: .98rem; }

    /* ---- events ---- */
    .mp-evs { display: grid; gap: .55rem; margin-top: .7rem; }
    .mp-ev { display: grid; grid-template-columns: 3.4rem minmax(0, 1fr); gap: .7rem; padding: .7rem .75rem; border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-gray-50, #fafafa); }
    .mp-ev-d { text-align: center; line-height: 1.05; }
    .mp-ev-d b { display: block; font-size: .98rem; font-weight: 900; color: var(--color-gray-900); }
    .mp-ev-d small { display: block; font-size: .64rem; font-weight: 800; text-transform: uppercase; color: var(--color-gray-500); }
    .mp-ev h4 { font-size: .86rem; font-weight: 800; line-height: 1.35; color: var(--color-gray-900); }
    .mp-ev p { margin-top: .2rem; font-size: .78rem; line-height: 1.5; color: var(--color-gray-600); }
    .mp-ev-t { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
    .mp-ev-t span { font-size: .66rem; font-weight: 800; padding: .1rem .5rem; border-radius: 999px; background: #eef2f7; color: #334155; }
    .mp-ev-t span.is-down { background: #fde8e8; color: #991b1b; }
    .mp-ev-t span.is-up { background: #e0effb; color: #1d5f99; }
    .mp-ev-t span.is-ahead { background: #fff3d6; color: #8a5a00; }
    .mp-ev-s { margin-top: .35rem; font-size: .7rem; color: var(--color-gray-500); }
    .mp-ev-s a { color: #2d6fb8; text-decoration: underline; text-underline-offset: 2px; }
    .mp-more { display: block; margin: .6rem auto 0; padding: .5rem 1rem; border-radius: .9rem; font-weight: 800; font-size: .82rem; color: #7a3d0b; background: #fdebd7; }

    /* ---- local reports ---- */
    .mp-locs { display: grid; gap: .4rem; margin-top: .65rem; }
    .mp-loc { display: grid; grid-template-columns: 3.1rem minmax(0, 1fr) auto; gap: .6rem; align-items: center; padding: .5rem .6rem; border-radius: .85rem; background: var(--color-gray-50, #fafafa); border: 1px solid var(--color-gray-200); }
    .mp-loc-d { text-align: center; line-height: 1.05; font-size: .62rem; font-weight: 800; text-transform: uppercase; color: var(--color-gray-500); }
    .mp-loc-d b { display: block; font-size: .95rem; color: var(--color-gray-900); }
    .mp-loc span { font-size: .78rem; line-height: 1.35; color: var(--color-gray-700); min-width: 0; }
    .mp-loc span small { display: block; color: var(--color-gray-500); }
    .mp-loc em { font-style: normal; font-weight: 900; font-size: .9rem; color: var(--color-gray-900); font-variant-numeric: tabular-nums; }

    /* ---- the table and how it was counted ---- */
    .mp-how { margin-top: .75rem; font-size: .78rem; color: var(--color-gray-600); }
    .mp-how summary { cursor: pointer; font-weight: 800; color: var(--color-gray-700); }
    .mp-how ol { margin-top: .4rem; padding-left: 1.1rem; display: grid; gap: .3rem; list-style: decimal; line-height: 1.5; }
    .mp-table { width: 100%; margin-top: .5rem; border-collapse: collapse; font-size: .76rem; }
    .mp-table th { text-align: left; font-weight: 800; color: var(--color-gray-500); padding: .3rem .4rem; border-bottom: 1px solid var(--color-gray-200); }
    .mp-table td { padding: .3rem .4rem; border-bottom: 1px solid var(--color-gray-100, #f3f4f6); color: var(--color-gray-700); font-variant-numeric: tabular-nums; }
    .mp-table td:not(:first-child), .mp-table th:not(:first-child) { text-align: right; }
    .mp-acts { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1rem; }
    .mp-save { flex: 1 1 12rem; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 3rem; padding: 0 1.2rem; border-radius: 1rem;
        font-weight: 800; color: #14210c; background: #f5c518; box-shadow: 0 10px 22px -14px rgb(150 110 0 / .9); transition: transform .28s var(--mp-e), opacity .28s var(--mp-e); }
    .mp-save:hover { transform: translateY(-1px); } .mp-save:disabled { opacity: .5; transform: none; }
    .mp-save svg { width: 1.1rem; height: 1.1rem; }
    .mp-reset { min-height: 3rem; padding: 0 1rem; border-radius: 1rem; font-weight: 800; color: var(--color-gray-600); border: 1.5px solid var(--color-gray-200); background: var(--color-white); }

    /* ---- the saved list ---- */
    .mp-list { display: grid; gap: .6rem; margin-top: 1rem; }
    .mp-srow { display: flex; align-items: center; gap: .75rem; padding: .75rem .8rem; border-radius: 1.1rem; background: var(--color-white); border: 1px solid var(--color-gray-200); animation: mpIn .35s var(--mp-e) both;
        transition: opacity .28s var(--mp-e), transform .28s var(--mp-e); }
    .mp-srow.is-gone { opacity: 0; transform: translateX(12px); }
    .mp-sico { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .9rem; display: grid; place-items: center; font-size: 1.3rem; background: #fdebd7; }
    .mp-sopen { flex: 1 1 auto; min-width: 0; text-align: left; }
    .mp-sopen b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); overflow-wrap: anywhere; }
    .mp-sopen small { display: block; margin-top: .1rem; font-size: .74rem; color: var(--color-gray-500); }
    .mp-sdel { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .8rem; display: grid; place-items: center; color: var(--color-gray-400); }
    .mp-sdel:hover { color: #b91c1c; background: #fef2f2; }
    .mp-sdel svg { width: 1.05rem; height: 1.05rem; }
    .mp-sempty { margin-top: 1rem; padding: 1.4rem 1.1rem; border-radius: 1.2rem; text-align: center; font-size: .86rem; color: var(--color-gray-500); background: var(--color-white); border: 1.5px dashed var(--color-gray-200); }

    /* ---- the sheets ---- */
    .mp-search { width: 100%; margin-bottom: .7rem; }
    .mp-opts { display: grid; gap: .4rem; }
    .mp-opt { display: flex; align-items: center; gap: .7rem; width: 100%; padding: .65rem .8rem; border-radius: .95rem; text-align: left; border: 1.5px solid var(--color-gray-200); background: var(--color-white);
        transition: border-color .28s var(--mp-e), background-color .28s var(--mp-e); }
    .mp-opt:hover { border-color: #e0a15c; }
    .mp-opt.is-on { border-color: #b8641f; background: #fff6ec; }
    .mp-opt .mp-oico { flex: none; width: 2rem; height: 2rem; border-radius: .7rem; display: grid; place-items: center; font-size: 1.1rem; background: #fdf3e6; }
    .mp-opt span { flex: 1 1 auto; min-width: 0; }
    .mp-opt b { display: block; font-size: .9rem; font-weight: 800; color: var(--color-gray-900); }
    .mp-opt small { display: block; margin-top: .05rem; font-size: .74rem; color: var(--color-gray-500); }
    .mp-ohead { margin: .7rem 0 .2rem; font-size: .7rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-500); }

    /* ---- dark ---- */
    html.dark .mp-tabs, html.dark .mp-q, html.dark .mp-tile, html.dark .mp-card, html.dark .mp-empty, html.dark .mp-srow, html.dark .mp-sempty, html.dark .mp-opt, html.dark .mp-reset, html.dark .mp-chip, html.dark .mp-tip { background: #151b12; border-color: #2b3a1c; }
    html.dark .mp-q h3, html.dark .mp-card h3, html.dark .mp-tile > b, html.dark .mp-sopen b, html.dark .mp-opt b, html.dark .mp-ev h4, html.dark .mp-ev-d b, html.dark .mp-loc-d b, html.dark .mp-loc em, html.dark .mp-tip b, html.dark .mp-note b, html.dark .mp-prow > b, html.dark .mp-prow > em { color: #eef3e8; }
    html.dark .mp-tag { background: #1b2416; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .mp-q h3 i, html.dark .mp-sico, html.dark .mp-opt .mp-oico { background: #3a2610; color: #f6c48f; }
    html.dark .mp-ev, html.dark .mp-loc, html.dark .mp-pill { background: #1b2416; border-color: #2b3a1c; }
    html.dark .mp-ev-t span { background: #1f2933; color: #cbd5e1; } html.dark .mp-ev-t span.is-down { background: #3a1616; color: #fca5a5; }
    html.dark .mp-ev-t span.is-up { background: #10273b; color: #93c5fd; } html.dark .mp-ev-t span.is-ahead { background: #33270f; color: #fcd34d; }
    html.dark .mp-big { background: #17240f; border-color: #2e4a1c; color: #cfe6bb; }
    html.dark .mp-saved-note { background: #2b2210; border-color: #4a3a18; color: #f5c77e; }
    html.dark .mp-opt.is-on { background: #2b1d0c; }
    html.dark .mp-prow .mp-bar { background: #1f2a19; }
    html.dark .mp-more { background: #3a2610; color: #f6c48f; }
    html.dark .mp-chip.is-on { background: #b8641f; border-color: #b8641f; color: #fff; }

    @media (prefers-reduced-motion: reduce) {
        .mp-art .mp-spark { animation: none; stroke-dashoffset: 0; }
        .mp-sum, .mp-tile, .mp-card, .mp-srow { animation: none !important; }
        .mp-chart .mp-draw, .mp-chart .mp-band, .mp-chart .mp-col, .mp-prow .mp-bar span, .mp-tab-ind { transition: none !important; }
    }
    html.sm-still .mp-art .mp-spark { animation: none; stroke-dashoffset: 0; }
</style>
@endpush

@section('content')
@php
    $chev = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
    // Built here: the json directive reads one argument.
    $mpBoot = ['catalog' => $catalog, 'urls' => ['outlook' => route('market.outlook'), 'places' => route('market.places'), 'save' => route('market.save'),
        'list' => route('market.list'), 'one' => url('/app/market-prices/one'), 'del' => url('/app/market-prices'), 'when' => \Illuminate\Support\Facades\Route::has('wtp.page') ? route('wtp.page') : null]];
@endphp
<script type="application/json" id="mpBoot">@json($mpBoot)</script>
<div class="mp-wrap">
    <section class="mp-hero">
        <svg class="mp-art" viewBox="0 0 64 64" aria-hidden="true">
            <rect x="8" y="10" width="48" height="40" rx="8" fill="rgb(255 255 255 / .14)"/>
            <path d="M14 40h36M14 30h36M14 20h36" stroke="rgb(255 255 255 / .22)" stroke-width="1"/>
            <path class="mp-spark" d="M14 38l8-6 7 4 8-12 6 5 7-9" fill="none" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="50" cy="20" r="3.4" fill="#f5c518"/>
            <path d="M20 54c3-4 7-4 10 0M34 54c3-4 7-4 10 0" stroke="#f5c518" stroke-width="2.4" stroke-linecap="round" fill="none"/>
        </svg>
        <div>
            <h2>Market Price Forecast{{ \App\Support\Tagalog::flag('market-forecast') }}</h2>
            <p>Pick your crop and where you sell. See its price these years, where it is heading this year and next, the best months to sell, and what imports did to it.</p>
        </div>
    </section>

    <div class="mp-tabs" id="mpTabs" role="tablist" aria-label="Market Price Forecast">
        <span class="mp-tab-ind" aria-hidden="true"></span>
        <button type="button" class="mp-tab" id="mpTabCalc" role="tab" aria-selected="true" aria-controls="mpPaneCalc">Forecast</button>
        <button type="button" class="mp-tab" id="mpTabSaved" role="tab" aria-selected="false" aria-controls="mpPaneSaved" tabindex="-1">Saved<span class="mp-tab-n" id="mpTabN"></span></button>
    </div>

    <div id="mpPaneCalc" role="tabpanel" aria-labelledby="mpTabCalc">
        <div class="mp-qs">
            <section class="mp-q">
                <h3><i>1</i>Your crop</h3>
                <button type="button" class="mp-tag is-none" id="mpCrop" aria-haspopup="dialog"><span>Choose a crop</span>{!! $chev !!}</button>
                <div class="mp-chips" id="mpItems" hidden></div>
            </section>
            <section class="mp-q">
                <h3><i>2</i>Where you sell</h3>
                <button type="button" class="mp-tag" id="mpPlace" aria-haspopup="dialog" disabled><span>The whole country</span>{!! $chev !!}</button>
                <p class="mp-hint" id="mpPlaceHint">A province's own prices when it has enough of them, else its region's.</p>
            </section>
            <section class="mp-q">
                <h3><i>3</i>Your harvest <small>(optional)</small></h3>
                <button type="button" class="mp-tag is-none" id="mpHarvest" aria-haspopup="dialog"><span>When is it harvested?</span>{!! $chev !!}</button>
                <label class="mp-in"><input type="number" class="form-input" id="mpQty" min="0" step="any" inputmode="decimal" placeholder="How much, like 5000" aria-label="How much you will sell"><em id="mpQtyUnit">kg</em></label>
            </section>
        </div>

        <div class="mp-empty" id="mpEmpty">
            <svg viewBox="0 0 48 48" aria-hidden="true"><path d="M8 36l9-8 8 5 9-14 6 6" fill="none" stroke="#e0a15c" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 42h32" stroke="#e5e7eb" stroke-width="2" stroke-linecap="round"/></svg>
            <span id="mpEmptySay">Choose a crop, and its prices and forecast show here.</span>
        </div>
        <div class="mp-out" id="mpOut" hidden aria-live="polite"></div>
    </div>

    <section id="mpPaneSaved" role="tabpanel" aria-labelledby="mpTabSaved" hidden>
        <div class="mp-list" id="mpList"></div>
        <div class="mp-sempty" id="mpSEmpty" hidden>No saved forecasts yet. Make one on the Forecast tab and save it, to look back at what it said.</div>
        <button type="button" class="mp-more" id="mpMoreSaved" hidden>Show more</button>
    </section>
</div>

<div class="sheet hidden" id="mpSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="mpSheetTitle">Choose</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <input type="search" class="form-input mp-search" id="mpSheetFind" placeholder="Search" autocomplete="off" aria-label="Search">
        <div class="mp-opts" id="mpSheetRows"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const B = JSON.parse($('mpBoot').textContent);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const still = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const toast = (m, t) => (window.toast ? window.toast(m, t) : alert(m));
    const MN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const MF = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const idx = (ym) => { const [y, m] = ym.split('-').map(Number); return y * 12 + m - 1; };
    const ymOf = (t) => Math.floor(t / 12) + '-' + String(t % 12 + 1).padStart(2, '0');
    const ymWord = (ym, full) => (full ? MF : MN)[idx(ym) % 12] + ' ' + Math.floor(idx(ym) / 12);
    const peso = (v) => '₱' + Number(v).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const pesoWhole = (v) => '₱' + Math.round(Number(v)).toLocaleString('en-PH');
    const pct = (x, sign = true) => (x === null || x === undefined ? '' : (sign && x > 0 ? '+' : (x < 0 ? '−' : '')) + Math.abs(Math.round(x * 100)) + '%');
    const unitWord = (o) => (o.item.unit === 'kg' ? 'a kilo' : 'a ' + o.item.unit);
    const unitShort = (o) => (o.item.unit === 'kg' ? '/kg' : '/' + o.item.unit);
    // Month numbers in words: "June to August", "October, December and January" (in calendar order, wrapping the year).
    const monthsWord = (ms) => {
        const m = [...ms].sort((a, b) => a - b);
        if (m.length === 3 && m[0] === 1 && m[2] === 12 && m[1] !== 2 && m[1] !== 11) { /* not a run */ }
        const runs = (arr) => { for (let i = 0; i < arr.length; i++) { const r = arr.slice(i).concat(arr.slice(0, i)); if (r.every((x, k) => k === 0 || (r[k - 1] % 12) + 1 === x)) return r; } return null; };
        const run = runs(m);
        if (run && run.length >= 3) return MF[run[0] - 1] + ' to ' + MF[run[run.length - 1] - 1];
        const w = (run || m).map((x) => MF[x - 1]);
        return w.length < 2 ? w[0] : w.slice(0, -1).join(', ') + ' and ' + w[w.length - 1];
    };

    const st = { crop: null, item: null, place: 'PH', placeName: 'The whole country', harvest: null, qty: null, o: null, plan: null, saved: null, places: {} };
    const LS = 'anee.market.last';
    try { const l = JSON.parse(localStorage.getItem(LS) || 'null'); if (l && typeof l === 'object') { Object.assign(st, { crop: l.crop || null, item: l.item || null, place: l.place || 'PH', placeName: l.placeName || 'The whole country', harvest: null, qty: l.qty || null }); } } catch (e) {}
    const remember = () => { try { localStorage.setItem(LS, JSON.stringify({ crop: st.crop, item: st.item, place: st.place, placeName: st.placeName, qty: st.qty })); } catch (e) {} };
    const cropOf = (k) => B.catalog.find((c) => c.crop === k) || null;
    const itemOf = (k) => { for (const c of B.catalog) { const i = c.items.find((x) => x.key === k); if (i) return i; } return null; };

    /* ---- the sheet: crops, places, months ---- */
    let sheetPick = null, sheetRows = [];
    const paintSheet = () => {
        const q = $('mpSheetFind').value.trim().toLowerCase();
        let head = '';
        $('mpSheetRows').innerHTML = sheetRows.filter((r) => r.head || !q || (r.find || r.title).toLowerCase().includes(q)).map((r) => {
            if (r.head) { head = r.head; return q ? '' : '<div class="mp-ohead">' + esc(r.head) + '</div>'; }
            return '<button type="button" class="mp-opt' + (r.on ? ' is-on' : '') + '" data-k="' + esc(r.key) + '">' + (r.icon ? '<i class="mp-oico" aria-hidden="true">' + esc(r.icon) + '</i>' : '')
                + '<span><b>' + esc(r.title) + '</b>' + (r.sub ? '<small>' + esc(r.sub) + '</small>' : '') + '</span></button>';
        }).join('') || '<p class="mp-hint">Nothing matches.</p>';
    };
    const openSheet = (title, rows, cb, find = true) => {
        $('mpSheetTitle').textContent = title; sheetRows = rows; sheetPick = cb;
        $('mpSheetFind').value = ''; $('mpSheetFind').hidden = !find;
        paintSheet(); window.openSheet('mpSheet');
        if (find && window.matchMedia('(min-width: 768px)').matches) setTimeout(() => $('mpSheetFind').focus(), 250);
    };
    $('mpSheetFind').addEventListener('input', paintSheet);
    $('mpSheetRows').addEventListener('click', (e) => {
        const b = e.target.closest('[data-k]'); if (!b || !sheetPick) return;
        $('mpSheetRows').querySelectorAll('.mp-opt').forEach((x) => x.classList.toggle('is-on', x === b));
        const cb = sheetPick; sheetPick = null;
        setTimeout(() => window.closeSheet('mpSheet'), 160);
        cb(b.dataset.k);
    });

    $('mpCrop').addEventListener('click', () => {
        if (!B.catalog.length) { toast('The price records are not loaded yet.', 'error'); return; }
        openSheet('Choose a crop', B.catalog.map((c) => ({ key: c.crop, title: c.label, icon: c.icon, on: c.crop === st.crop,
            sub: c.items.length > 1 ? c.items.length + ' kinds: ' + c.items.map((i) => i.name.replace(/^[^,]*,\s*/, '')).slice(0, 3).join(', ') + (c.items.length > 3 ? ' and more' : '') : c.items[0].name })), (k) => {
            if (k === st.crop) return;
            st.crop = k; st.item = cropOf(k).items[0].key; st.saved = null;
            paintInputs(); load();
        });
    });
    $('mpItems').addEventListener('click', (e) => {
        const c = e.target.closest('[data-item]'); if (!c || c.dataset.item === st.item) return;
        st.item = c.dataset.item; st.saved = null; paintInputs(); load();
    });
    $('mpPlace').addEventListener('click', async () => {
        if (!st.item) return;
        let list = st.places[st.item];
        if (!list) {
            try { const r = await window.api(B.urls.places + '?item=' + encodeURIComponent(st.item), { method: 'GET' }); list = st.places[st.item] = r.data.places; }
            catch (err) { toast(err.message, 'error'); return; }
        }
        const rows = [];
        const nat = list.find((p) => p.level === 'national');
        rows.push({ key: 'PH', title: 'The whole country', sub: nat ? 'Prices up to ' + ymWord(nat.last) : '', on: st.place === 'PH' });
        const regs = list.filter((p) => p.level === 'region');
        if (regs.length) { rows.push({ head: 'Regions' }); regs.forEach((p) => rows.push({ key: p.key, title: p.name, sub: 'Prices up to ' + ymWord(p.last), on: st.place === p.key, find: p.name + ' region' })); }
        const provs = list.filter((p) => p.level === 'province');
        let reg = null;
        provs.forEach((p) => { if (p.region !== reg) { reg = p.region; rows.push({ head: reg || 'Provinces' }); } rows.push({ key: p.key, title: p.name, sub: (p.region ? p.region + ', ' : '') + 'prices up to ' + ymWord(p.last), on: st.place === p.key, find: p.name + ' ' + (p.region || '') }); });
        openSheet('Where you sell', rows, (k) => {
            const p = list.find((x) => x.key === k);
            st.place = k; st.placeName = k === 'PH' ? 'The whole country' : (p ? p.name : k); st.saved = null;
            paintInputs(); load();
        });
    });
    $('mpHarvest').addEventListener('click', () => {
        const now = new Date(), t0 = now.getFullYear() * 12 + now.getMonth();
        const rows = [{ key: '', title: 'Not set', sub: 'Show the year and the forecast only', on: !st.harvest }];
        for (let k = -2; k <= 16; k++) { const ym = ymOf(t0 + k); rows.push({ key: ym, title: ymWord(ym, true), sub: k < 0 ? 'Already harvested, still to sell' : (k === 0 ? 'This month' : ''), on: st.harvest === ym }); }
        openSheet('When is it harvested?', rows, (k) => { st.harvest = k || null; paintInputs(); if (st.item) load(); }, false);
    });
    let qtyT = null;
    $('mpQty').addEventListener('input', () => { const v = Number($('mpQty').value); st.qty = v > 0 ? v : null; remember(); clearTimeout(qtyT); if (st.item && st.harvest) qtyT = setTimeout(load, 450); });

    const paintInputs = () => {
        const c = cropOf(st.crop), it = itemOf(st.item);
        $('mpCrop').classList.toggle('is-none', !c);
        $('mpCrop').querySelector('span').textContent = c ? c.icon + ' ' + c.label : 'Choose a crop';
        $('mpItems').hidden = !(c && c.items.length > 1);
        if (c && c.items.length > 1) $('mpItems').innerHTML = c.items.map((i) => '<button type="button" class="mp-chip' + (i.key === st.item ? ' is-on' : '') + '" data-item="' + esc(i.key) + '">' + esc(i.name) + '</button>').join('');
        $('mpPlace').disabled = !it;
        $('mpPlace').querySelector('span').textContent = st.placeName;
        $('mpHarvest').classList.toggle('is-none', !st.harvest);
        $('mpHarvest').querySelector('span').textContent = st.harvest ? 'Harvest in ' + ymWord(st.harvest, true) : 'When is it harvested?';
        $('mpQtyUnit').textContent = it && it.unit !== 'kg' ? it.unit + 's' : 'kg';
        if (st.qty && !$('mpQty').value) $('mpQty').value = st.qty;
        remember();
    };

    /* ---- the forecast ---- */
    let ask = 0;
    const load = async () => {
        if (!st.item) return;
        const mine = ++ask;
        $('mpOut').classList.add('is-busy');
        const q = new URLSearchParams({ item: st.item, place: st.place || 'PH' });
        if (st.harvest) q.set('harvest', st.harvest);
        if (st.qty) q.set('qty', st.qty);
        try {
            const r = await window.api(B.urls.outlook + '?' + q.toString(), { method: 'GET' });
            if (mine !== ask) return;
            st.o = r.data.outlook; st.plan = r.data.plan;
            render();
        } catch (err) {
            if (mine !== ask) return;
            $('mpOut').hidden = true; $('mpEmpty').hidden = false; $('mpEmptySay').textContent = err.message || 'The prices could not load.';
        } finally { if (mine === ask) $('mpOut').classList.remove('is-busy'); }
    };

    /* ---- the charts (inline SVG: thin marks, hairline grid, a crosshair and a tip, drawn in) ---- */
    const NS = 'http://www.w3.org/2000/svg';
    const niceTicks = (lo, hi, n = 4) => {
        const span = Math.max(1e-6, hi - lo), raw = span / n, mag = Math.pow(10, Math.floor(Math.log10(raw)));
        const step = [1, 2, 2.5, 5, 10].map((k) => k * mag).find((s) => s >= raw) || raw;
        const out = []; for (let v = Math.ceil(lo / step) * step; v <= hi + 1e-9; v += step) out.push(+v.toFixed(6));
        return out;
    };
    const tipAt = (box, tip, x, y, html) => {
        tip.innerHTML = html; tip.hidden = false;
        const w = tip.offsetWidth, W = box.clientWidth;
        tip.style.left = Math.max(0, Math.min(W - w, x + 12 > W - w ? x - w - 12 : x + 12)) + 'px';
        tip.style.top = Math.max(0, y - tip.offsetHeight - 10) + 'px';
    };
    const colPath = (x, y0, y1, w, r) => {
        // A column with a rounded data end and a square foot on the baseline.
        const up = y1 < y0, h = Math.abs(y1 - y0), rr = Math.min(r, h, w / 2);
        if (h < 0.5) return '';
        return up ? `M${x},${y0}V${y1 + rr}Q${x},${y1} ${x + rr},${y1}H${x + w - rr}Q${x + w},${y1} ${x + w},${y1 + rr}V${y0}Z`
            : `M${x},${y0}V${y1 - rr}Q${x},${y1} ${x + rr},${y1}H${x + w - rr}Q${x + w},${y1} ${x + w},${y1 - rr}V${y0}Z`;
    };
    const drawIn = (paths, delay = 0) => {
        paths.forEach((p) => { if (still()) return; const L = p.getTotalLength(); p.style.strokeDasharray = L; p.style.strokeDashoffset = L; p.getBoundingClientRect();
            setTimeout(() => { p.style.strokeDashoffset = 0; }, delay); });
    };

    // The price path: the last years of prices and the forecast with its likely range, one axis.
    function pathChart(box, o) {
        const W = Math.max(300, box.clientWidth), phone = W < 560, H = phone ? 230 : 280, L = 44, R = phone ? 54 : 66, T = 18, Bm = 26;
        const hist = o.history.slice(phone ? -48 : -72), fc = o.forecast;
        if (!hist.length) { box.innerHTML = ''; return; }
        const x0 = idx(hist[0].ym), x1 = fc.length ? idx(fc[fc.length - 1].ym) : idx(hist[hist.length - 1].ym);
        const vals = hist.map((h) => h.p).concat(fc.flatMap((f) => [f.lo, f.hi]));
        let lo = Math.min(...vals), hi = Math.max(...vals);
        const pad = (hi - lo) * 0.08 || 1; lo = Math.max(0, lo - pad); hi += pad;
        const ticks = niceTicks(lo, hi); lo = Math.min(lo, ticks[0]); hi = Math.max(hi, ticks[ticks.length - 1]);
        const X = (t) => L + (W - L - R) * (t - x0) / Math.max(1, x1 - x0), Y = (v) => T + (H - T - Bm) * (1 - (v - lo) / Math.max(1e-6, hi - lo));
        const svg = document.createElementNS(NS, 'svg'); svg.setAttribute('viewBox', `0 0 ${W} ${H}`); svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Price for ' + hist.length + ' months and the forecast to ' + (fc.length ? ymWord(fc[fc.length - 1].ym) : '') + '. The table below has every number.');
        let g = '';
        ticks.forEach((v) => { g += `<line class="mp-g" x1="${L}" x2="${W - R}" y1="${Y(v)}" y2="${Y(v)}"/><text x="${L - 6}" y="${Y(v) + 4}" text-anchor="end">${v >= 100 ? Math.round(v) : v}</text>`; });
        for (let t = x0; t <= x1; t++) if (t % 12 === 0) g += `<text x="${X(t)}" y="${H - 6}" text-anchor="middle">${Math.floor(t / 12)}</text>`;
        const lastT = idx(hist[hist.length - 1].ym), lastP = hist[hist.length - 1].p;
        if (fc.length) {
            const up = fc.map((f) => `${X(idx(f.ym))},${Y(f.hi)}`), dn = fc.slice().reverse().map((f) => `${X(idx(f.ym))},${Y(f.lo)}`);
            g += `<polygon class="mp-band" fill="var(--mp-f)" opacity="${still() ? 0.14 : 0}" points="${X(lastT)},${Y(lastP)} ${up.join(' ')} ${dn.join(' ')}"/>`;
            g += `<line class="mp-g" x1="${X(lastT)}" x2="${X(lastT)}" y1="${T}" y2="${H - Bm}"/><text x="${X(lastT) + 4}" y="${T + 2}">forecast</text>`;
        }
        const pts = (arr) => arr.map((h) => `${X(idx(h.ym))},${Y(h.p)}`).join(' ');
        // A gap of more than two months in the records breaks the line, never bridged by a straight run.
        const runs = []; hist.forEach((h, i) => { if (!i || idx(h.ym) - idx(hist[i - 1].ym) > 2) runs.push([]); runs[runs.length - 1].push(h); });
        runs.forEach((r) => { g += r.length > 1 ? `<polyline class="mp-line mp-draw is-a" stroke="var(--mp-a)" points="${pts(r)}"/>` : `<circle cx="${X(idx(r[0].ym))}" cy="${Y(r[0].p)}" r="2" fill="var(--mp-a)"/>`; });
        if (fc.length) g += `<polyline class="mp-line mp-draw is-f" stroke="var(--mp-f)" points="${X(lastT)},${Y(lastP)} ${pts(fc)}"/>`;
        // The labels: the last price and the forecast's end, nothing else.
        g += `<circle class="mp-dot" cx="${X(lastT)}" cy="${Y(lastP)}" r="4.5" fill="var(--mp-a)"/><text class="is-label" x="${X(lastT) - 6}" y="${Y(lastP) - 9}" text-anchor="end">${esc(peso(lastP))}</text>`;
        if (fc.length) { const e = fc[fc.length - 1]; g += `<circle class="mp-dot" cx="${X(idx(e.ym))}" cy="${Y(e.p)}" r="4.5" fill="var(--mp-f)"/><text class="is-label" x="${X(idx(e.ym)) + 7}" y="${Y(e.p) + 4}">${esc(peso(e.p))}</text>`; }
        g += `<line class="mp-cross" x1="0" x2="0" y1="${T}" y2="${H - Bm}" opacity="0"/><circle class="mp-dot mp-hov" r="4.5" opacity="0"/><rect class="mp-hit" x="${L}" y="${T}" width="${W - L - R}" height="${H - T - Bm}" fill="transparent"/>`;
        svg.innerHTML = g;
        box.innerHTML = ''; box.appendChild(svg);
        const tip = document.createElement('div'); tip.className = 'mp-tip'; tip.hidden = true; box.appendChild(tip);
        drawIn([...svg.querySelectorAll('.is-a')], 60); if (fc.length) drawIn([svg.querySelector('.is-f')], 900);
        if (!still()) setTimeout(() => { const b = svg.querySelector('.mp-band'); if (b) b.setAttribute('opacity', 0.14); }, 50);
        const all = hist.map((h) => ({ t: idx(h.ym), ...h, kind: 'a' })).concat(fc.map((f) => ({ t: idx(f.ym), ...f, kind: 'f' })));
        const cross = svg.querySelector('.mp-cross'), dot = svg.querySelector('.mp-hov');
        let at = all.length - fc.length - 1;
        const show = (i) => {
            at = Math.max(0, Math.min(all.length - 1, i)); const d = all[at], x = X(d.t), y = Y(d.p);
            cross.setAttribute('x1', x); cross.setAttribute('x2', x); cross.setAttribute('opacity', 1);
            dot.setAttribute('cx', x); dot.setAttribute('cy', y); dot.setAttribute('fill', d.kind === 'a' ? 'var(--mp-a)' : 'var(--mp-f)'); dot.setAttribute('opacity', 1);
            const s = box.clientWidth / W;
            tipAt(box, tip, x * s, y * s, '<b>' + esc(peso(d.p)) + '</b><i style="background:' + (d.kind === 'a' ? 'var(--mp-a)' : 'var(--mp-f)') + '"></i>' + (d.kind === 'a' ? 'Price, ' : 'Forecast, ') + esc(ymWord(d.ym, true))
                + (d.kind === 'f' ? '<small>Likely ' + esc(peso(d.lo)) + ' to ' + esc(peso(d.hi)) + '</small>' : ''));
        };
        const hide = () => { cross.setAttribute('opacity', 0); dot.setAttribute('opacity', 0); tip.hidden = true; };
        svg.querySelector('.mp-hit').addEventListener('pointermove', (e) => {
            const r = svg.getBoundingClientRect(), x = (e.clientX - r.left) * W / r.width;
            const t = x0 + Math.round((x - L) / ((W - L - R) / Math.max(1, x1 - x0)));
            show(all.findIndex((d) => d.t >= t) === -1 ? all.length - 1 : all.findIndex((d) => d.t >= t));
        });
        svg.querySelector('.mp-hit').addEventListener('pointerleave', hide);
        box.onkeydown = (e) => { if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') { e.preventDefault(); show(at + (e.key === 'ArrowLeft' ? -1 : 1)); } };
        box.onfocus = () => show(at); box.onblur = hide;
    }

    // The months of the year: each month's price against the year's average, above in blue, below in red.
    function monthsChart(box, o, harvestM) {
        const W = Math.max(300, box.clientWidth), phone = W < 560, H = phone ? 200 : 230, L = 40, R = 10, T = 22, Bm = 34;
        const vals = o.seasonal.map((s) => (s.idx - 1) * 100);
        let lo = Math.min(0, ...vals), hi = Math.max(0, ...vals); const pad = Math.max(2, (hi - lo) * 0.12); lo -= pad; hi += pad;
        const ticks = niceTicks(lo, hi, 4);
        const X = (m) => L + (W - L - R) * (m + 0.5) / 12, Y = (v) => T + (H - T - Bm) * (1 - (v - lo) / (hi - lo)), bw = Math.min(24, (W - L - R) / 12 - 6);
        const order = vals.map((v, m) => [v, m]).sort((a, b) => b[0] - a[0]);
        const labelled = new Set([order[0][1], order[11][1]]);
        let g = '';
        ticks.forEach((v) => { g += `<line class="mp-g" x1="${L}" x2="${W - R}" y1="${Y(v)}" y2="${Y(v)}"/><text x="${L - 6}" y="${Y(v) + 4}" text-anchor="end">${v > 0 ? '+' : ''}${Math.round(v)}%</text>`; });
        g += `<line x1="${L}" x2="${W - R}" y1="${Y(0)}" y2="${Y(0)}" stroke="var(--mp-axis)" stroke-width="1"/>`;
        o.seasonal.forEach((s, m) => {
            const v = vals[m], x = X(m) - bw / 2, up = v >= 0;
            g += `<path class="mp-col" tabindex="0" data-m="${m}" fill="${up ? 'var(--mp-up)' : 'var(--mp-dn)'}" d="${colPath(x, Y(0), Y(v), bw, 4)}" style="transform-origin:center ${up ? 'bottom' : 'top'};${still() ? '' : 'transform:scaleY(0)'}"><title>${esc(MF[m] + ': ' + pct(v / 100))}</title></path>`;
            g += `<text x="${X(m)}" y="${H - Bm + 14}" text-anchor="middle"${harvestM === m ? ' class="is-label"' : ''}>${phone ? MN[m][0] : MN[m]}</text>`;
            if (harvestM === m) g += `<text class="is-label" x="${X(m)}" y="${H - 4}" text-anchor="middle">harvest</text>`;
            if (labelled.has(m)) g += `<text class="is-label" x="${X(m)}" y="${up ? Y(v) - 6 : Y(v) + 14}" text-anchor="middle">${pct(v / 100)}</text>`;
        });
        const svg = document.createElementNS(NS, 'svg'); svg.setAttribute('viewBox', `0 0 ${W} ${H}`); svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Each month against the year\'s average price. Highest in ' + MF[order[0][1]] + ', lowest in ' + MF[order[11][1]] + '.');
        svg.innerHTML = g; box.innerHTML = ''; box.appendChild(svg);
        const tip = document.createElement('div'); tip.className = 'mp-tip'; tip.hidden = true; box.appendChild(tip);
        svg.querySelectorAll('.mp-col').forEach((c, i) => {
            if (!still()) setTimeout(() => { c.style.transform = 'scaleY(1)'; }, 60 + i * 45);
            const m = Number(c.dataset.m), s = o.seasonal[m];
            const say = () => { const r = c.getBoundingClientRect(), b = box.getBoundingClientRect();
                tipAt(box, tip, r.left - b.left + r.width / 2, r.top - b.top, '<b>' + esc(pct(s.idx - 1)) + '</b>' + esc(MF[m]) + ', against the year\'s average'
                    + '<small>' + esc((s.idx >= 1 ? 'Above' : 'Below') + ' it in ' + Math.round(s.sure * s.years) + ' of ' + s.years + ' years') + '</small>'); };
            c.addEventListener('pointerenter', say); c.addEventListener('focus', say);
            c.addEventListener('pointerleave', () => { tip.hidden = true; }); c.addEventListener('blur', () => { tip.hidden = true; });
        });
    }

    // Columns by year (a yearly average, or yearly imports): the known years in blue, forecast years in orange.
    function yearsChart(box, rows, opt) {
        const W = Math.max(300, box.clientWidth), phone = W < 560, H = phone ? 200 : 230, L = 46, R = 8, T = 32, Bm = 24;
        const show = rows.slice(phone ? -14 : -24);
        if (!show.length) { box.innerHTML = ''; return; }
        const vs = show.map((r) => r.v).filter((v) => v !== null), hi0 = Math.max(...vs) * 1.12 || 1, ticks = niceTicks(0, hi0, 4), hi = Math.max(hi0, ticks[ticks.length - 1]);
        const step = (W - L - R) / show.length, bw = Math.min(24, step - 4), X = (i) => L + step * (i + 0.5), Y = (v) => T + (H - T - Bm) * (1 - v / hi);
        // The labels: the highest column and the last one, never two side by side.
        const top = show.findIndex((r) => r.v === Math.max(...vs));
        const lab = new Set([show.length - 1]); if (Math.abs(top - (show.length - 1)) > 1) lab.add(top);
        let g = '';
        ticks.forEach((v) => { g += `<line class="mp-g" x1="${L}" x2="${W - R}" y1="${Y(v)}" y2="${Y(v)}"/><text x="${L - 6}" y="${Y(v) + 4}" text-anchor="end">${opt.tick(v)}</text>`; });
        show.forEach((r, i) => {
            if (r.v === null) { if (!phone || i % 2 === (show.length - 1) % 2) g += `<text x="${X(i)}" y="${H - 6}" text-anchor="middle">${phone ? "'" + String(r.y).slice(2) : r.y}</text>`; return; }
            g += `<path class="mp-col" tabindex="0" data-i="${i}" fill="${r.fc ? 'var(--mp-f)' : 'var(--mp-a)'}" d="${colPath(X(i) - bw / 2, Y(0), Y(r.v), bw, 4)}" style="transform-origin:center bottom;${still() ? '' : 'transform:scaleY(0)'}"></path>`;
            if (!phone || i % 2 === (show.length - 1) % 2) g += `<text x="${X(i)}" y="${H - 6}" text-anchor="middle">${phone ? "'" + String(r.y).slice(2) : r.y}</text>`;
            if (lab.has(i)) g += `<text class="is-label" x="${X(i)}" y="${Y(r.v) - 6}" text-anchor="middle">${esc(opt.label(r.v))}</text>`;
            if (r.mark) g += `<path d="M${X(i)},4 l4,5 l-4,5 l-4,-5z" fill="var(--mp-ink2)"/>`;
        });
        const svg = document.createElementNS(NS, 'svg'); svg.setAttribute('viewBox', `0 0 ${W} ${H}`); svg.setAttribute('role', 'img'); svg.setAttribute('aria-label', opt.aria);
        svg.innerHTML = g; box.innerHTML = ''; box.appendChild(svg);
        const tip = document.createElement('div'); tip.className = 'mp-tip'; tip.hidden = true; box.appendChild(tip);
        svg.querySelectorAll('.mp-col').forEach((c, k) => {
            if (!still()) setTimeout(() => { c.style.transform = 'scaleY(1)'; }, 60 + k * 35);
            const r = show[Number(c.dataset.i)];
            const say = () => { const a = c.getBoundingClientRect(), b = box.getBoundingClientRect(); tipAt(box, tip, a.left - b.left + a.width / 2, a.top - b.top, opt.tip(r)); };
            c.addEventListener('pointerenter', say); c.addEventListener('focus', say);
            c.addEventListener('pointerleave', () => { tip.hidden = true; }); c.addEventListener('blur', () => { tip.hidden = true; });
        });
    }

    /* ---- paint the answer ---- */
    const render = () => {
        const o = st.o; if (!o) return;
        $('mpEmpty').hidden = true; const out = $('mpOut'); out.hidden = false;
        const u = unitShort(o), now = new Date(), nowT = now.getFullYear() * 12 + now.getMonth();
        const fc12 = o.forecast.filter((f) => idx(f.ym) > nowT - 1 && idx(f.ym) <= nowT + 12);
        const bestAhead = fc12.length ? fc12.reduce((a, b) => (b.p > a.p ? b : a)) : null, worstAhead = fc12.length ? fc12.reduce((a, b) => (b.p < a.p ? b : a)) : null;
        const seas = o.seasonal, best = o.best, worst = o.worst;
        const bi = seas[best[0] - 1].idx, wi = seas[worst[0] - 1].idx;
        const perish = !o.item.store;
        const fAt = (t) => o.forecast.find((f) => idx(f.ym) === t);
        const base = fAt(nowT) ? fAt(nowT).p : o.latest.p, next3 = [1, 2, 3].map((k) => fAt(nowT + k)).filter(Boolean);
        const dir3 = next3.length ? next3.reduce((s, f) => s + f.p, 0) / next3.length / base - 1 : 0;
        const head = perish
            ? 'Fresh ' + o.item.name.toLowerCase() + ' does not keep, so time the harvest: it sells best in ' + monthsWord(best) + '.'
            : 'Best months to sell: ' + monthsWord(best) + '. ' + monthsWord(worst) + ' is the low season.';
        let h = '';
        if (st.saved) h += '<div class="mp-saved-note"><span>Saved ' + esc(st.saved) + '. Prices since then may have moved.</span><button type="button" data-act="fresh">See today\'s forecast</button></div>';
        h += '<div class="mp-sum"><small>' + esc(o.item.name) + '</small><b>' + esc(head) + '</b><p>'
            + esc('Prices usually run about ' + Math.round((bi / wi - 1) * 100) + '% higher in ' + MF[best[0] - 1] + ' than in ' + MF[worst[0] - 1] + '. ')
            + (bestAhead ? esc('In the next twelve months the forecast is highest in ' + ymWord(bestAhead.ym, true) + ', about ' + peso(bestAhead.p) + ', and lowest in ' + ymWord(worstAhead.ym, true) + ', about ' + peso(worstAhead.p) + '. ') : '')
            + (Math.abs(dir3) >= 0.03 ? esc('Over the next three months it is expected to ' + (dir3 > 0 ? 'rise' : 'fall') + ' about ' + Math.abs(Math.round(dir3 * 100)) + '%.') : esc('Over the next three months it is expected to hold about steady.'))
            + '</p><span class="mp-where">' + esc('Farmgate prices in ' + o.place.name + ', up to ' + ymWord(o.data.last, true)) + '</span></div>';
        // The tiles.
        const Y = o.years;
        h += '<div class="mp-tiles">'
            + '<div class="mp-tile"><span>Latest farmgate price</span><b>' + esc(peso(o.latest.p)) + '<small>' + esc(u) + '</small></b><em>' + esc(ymWord(o.latest.ym, true)) + (o.latest.yoy !== null ? ', <i>' + esc(pct(o.latest.yoy)) + '</i> on a year before' : '') + '</em></div>'
            + (Y.this ? '<div class="mp-tile"><span>This year, ' + Y.this.y + '</span><b>' + esc(peso(Y.this.avg)) + '<small>' + esc(u) + '</small></b><em>' + esc(Y.this.known + ' months known' + (Y.this.known < 12 ? ', the rest forecast' : '')) + (Y.last ? ', <i>' + esc(pct(Y.this.avg / Y.last.avg - 1)) + '</i> on ' + Y.last.y : '') + '</em></div>' : '')
            + (Y.next ? '<div class="mp-tile"><span>Next year, ' + Y.next.y + ' (forecast)</span><b>' + esc(peso(Y.next.avg)) + '<small>' + esc(u) + '</small></b><em>Likely <i>' + esc(peso(Y.next.lo)) + '</i> to <i>' + esc(peso(Y.next.hi)) + '</i></em></div>' : '')
            + (o.growth !== null ? '<div class="mp-tile"><span>The long run</span><b>' + esc(pct(o.growth)) + '<small> a year</small></b><em>The price\'s own climb over the last eight years, its seasons set aside</em></div>' : '')
            + '</div>';
        // The price path.
        h += '<section class="mp-card"><h3>The price these years, and where it is heading</h3><p>Every month\'s farmgate price, then the forecast to the end of ' + (Y.next ? Y.next.y : 'next year') + '. The shaded band is where the price is likely to fall.</p>'
            + '<div class="mp-legend"><span><i style="background:var(--mp-a)"></i>Price</span><span><i style="background:var(--mp-f)"></i>Forecast</span><span><i class="is-band" style="background:var(--mp-f)"></i>Likely range</span></div>'
            + '<div class="mp-chart" id="mpC1" tabindex="0" aria-describedby="mpTblCap"></div>'
            + (o.gap !== null && Math.abs(o.gap) >= 0.08 ? '<p class="mp-note">The price now sits <b>' + esc(Math.abs(Math.round(o.gap * 100)) + '% ' + (o.gap > 0 ? 'above' : 'below')) + '</b> its usual level for the season. ' + (o.halfLife ? esc('Gaps like this have usually closed by half in about ' + Math.max(1, Math.round(o.halfLife)) + ' month' + (Math.round(o.halfLife) > 1 ? 's' : '') + ', and the forecast lets it fade the same way.') : '') + '</p>' : '')
            + (o.boomBust && o.boomBust.r <= -0.25 ? '<p class="mp-note">Watch the cycle: a year of good prices for this crop has often been followed by a poor one, when many farmers plant it after a good year.</p>' : '')
            + '</section>';
        // The months, and the sell plan.
        const harvestM = st.harvest ? idx(st.harvest) % 12 : null;
        h += '<div class="mp-two"><section class="mp-card"><h3>The months of its year</h3><p>Each month\'s price against the year\'s average, over the last ten years. Sell in the blue months when you can; the red ones are the harvest glut.</p>'
            + '<div class="mp-legend"><span><i class="is-box" style="background:var(--mp-up)"></i>Above the average</span><span><i class="is-box" style="background:var(--mp-dn)"></i>Below it</span></div>'
            + '<div class="mp-chart" id="mpC2"></div>'
            + '<div class="mp-pills">' + best.map((m) => '<span class="mp-pill"><i style="background:var(--mp-up)"></i>' + esc(MF[m - 1] + ' ' + pct(seas[m - 1].idx - 1)) + '</span>').join('')
            + worst.map((m) => '<span class="mp-pill"><i style="background:var(--mp-dn)"></i>' + esc(MF[m - 1] + ' ' + pct(seas[m - 1].idx - 1)) + '</span>').join('') + '</div></section>';
        h += '<section class="mp-card"><h3>Sell at harvest, or keep it?</h3>' + planHtml(o) + '</section></div>';
        // The trader's price.
        if (o.wholesale) {
            const w = o.wholesale;
            h += '<section class="mp-card"><h3>What traders sell it for</h3><p>The last ' + w.months + ' months, beside what farmers got.</p><p class="mp-note">'
                + (w.milled ? esc('Traders sold milled rice at about ' + peso(w.trade) + ' a kilo in ' + w.place + '. A kilo of palay makes about 0.63 kilo of rice, so that is about ' + peso(w.equiv) + ' for each kilo of palay. ')
                    : esc('Traders sold it at about ' + peso(w.trade) + unitShort(o) + ' in ' + w.place + '. '))
                + 'Farmers got about <b>' + esc(peso(w.farm)) + '</b>, ' + (w.share ? '<b>' + esc(Math.round(w.share * 100) + '%') + '</b> of the trader\'s price' : '') + '. '
                + esc(w.share && w.share < 0.7 ? 'The gap is wide: selling together with your neighbors, or straight to a market or a processor, can keep more of it with you.' : 'The gap is narrow for this crop: where you sell matters less than when.') + '</p></section>';
        }
        // Imports and policies.
        if (o.events.length || o.imports.length) {
            h += '<section class="mp-card"><h3>Imports and policies that moved it</h3><p>What came in from abroad, the bans, the tariffs, and what the farmgate price did in the months after.</p>';
            if (o.imports.length >= 3) h += '<div class="mp-legend"><span><i class="is-box" style="background:var(--mp-a)"></i>Imports a year, tons</span></div><div class="mp-chart" id="mpC4"></div>';
            h += '<div class="mp-evs" id="mpEvs"></div>' + (o.events.length > 5 ? '<button type="button" class="mp-more" data-act="moreEv">Show all ' + o.events.length + '</button>' : '') + '</section>';
        }
        // Year by year.
        h += '<section class="mp-card"><h3>Year by year</h3><p>The yearly average farmgate price' + (o.events.length ? ', with a mark over the years an import or policy event was reported' : '') + '. The last columns are forecast.</p>'
            + '<div class="mp-legend"><span><i class="is-box" style="background:var(--mp-a)"></i>Yearly average</span><span><i class="is-box" style="background:var(--mp-f)"></i>Forecast</span></div><div class="mp-chart" id="mpC3"></div></section>';
        // The latest local prices.
        if (o.local.length) {
            h += '<section class="mp-card"><h3>Prices reported near you lately</h3><p>What traders paid or farmers got, as reported in ' + esc(o.place.name) + '. Wet palay sells below dry.</p><div class="mp-locs">'
                + o.local.slice(0, 8).map((l) => { const d = new Date(l.day + 'T00:00:00');
                    return '<div class="mp-loc"><span class="mp-loc-d"><b>' + d.getDate() + '</b>' + esc(MN[d.getMonth()] + ' ' + String(d.getFullYear()).slice(2)) + '</span><span>' + esc(l.town || l.province || o.place.name)
                        + '<small>' + esc([l.form && l.form.indexOf('unspecified') < 0 ? l.form : '', l.buyer, l.farmer ? 'a farmer\'s own report' : ''].filter(Boolean).join(', ')) + '</small></span><em>' + esc(l.lo && l.hi ? peso(l.lo) + ' to ' + peso(l.hi) : peso(l.p)) + '</em></div>'; }).join('')
                + '</div></section>';
        }
        // How it was counted, and every number.
        const acc = o.accuracy || {};
        h += '<details class="mp-how"><summary>How it was counted, and the numbers</summary><ol>'
            + '<li>' + esc('Monthly farmgate prices of ' + o.item.name.toLowerCase() + ' in ' + o.place.name + ' from ' + ymWord(o.data.from, true) + ' to ' + ymWord(o.data.last, true) + '.' + (o.place.why ? ' ' + o.place.why : '')) + '</li>'
            + '<li>Each month is set against the year around it, over the last ten years, to find the months of its year.</li>'
            + '<li>' + esc('With the months set aside, a line through the last eight years gives the long run (' + pct(o.growth) + ' a year), held to half that pace beyond a year ahead.') + '</li>'
            + '<li>' + esc('How far the price sits from that line now fades at the pace such gaps have faded before.') + '</li>'
            + '<li>' + esc('The likely range comes from making the same forecast from each of the last six years and checking it against what the price then did.' + (acc.m6 ? ' Looking back, it was off by about ' + Math.round(acc.m6 * 100) + '% six months ahead' + (acc.m12 ? ' and ' + Math.round(acc.m12 * 100) + '% a year ahead' : '') + ', in the middle of those checks.' : '')) + '</li>'
            + '<li>Imports, bans and storms the records cannot see can move the price outside the range. Check the latest news before a big sale.</li></ol>'
            + '<table class="mp-table" aria-describedby="mpTblCap"><caption id="mpTblCap" class="sr-only">The forecast, month by month</caption><thead><tr><th>Month</th><th>Forecast</th><th>Low</th><th>High</th></tr></thead><tbody>'
            + o.forecast.map((f) => '<tr><td>' + esc(ymWord(f.ym)) + '</td><td>' + esc(peso(f.p)) + '</td><td>' + esc(peso(f.lo)) + '</td><td>' + esc(peso(f.hi)) + '</td></tr>').join('') + '</tbody></table></details>';
        h += '<div class="mp-acts"><button type="button" class="mp-save" data-act="save"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4h11l3 3v13H5zM8 4v5h7V4M8 20v-6h8v6"/></svg>Save this forecast</button><button type="button" class="mp-reset" data-act="reset">Start over</button></div>';
        out.innerHTML = h;
        // The drawings.
        pathChart($('mpC1'), o);
        monthsChart($('mpC2'), o, harvestM);
        const evYears = new Set(o.events.map((e) => Number(e.day.slice(0, 4))));
        const byY = {}; o.annual.forEach((a) => { byY[a.y] = a; });
        const yrs = []; for (let y = Math.max(o.annual[0].y, now.getFullYear() - 24); y < now.getFullYear(); y++) { const a = byY[y]; yrs.push({ y, v: a && a.n >= 6 ? a.avg : null, fc: false, part: a && a.n < 12 && a.n >= 6 ? a.n : undefined, mark: evYears.has(y) }); }
        if (Y.this) yrs.push({ y: Y.this.y, v: Y.this.avg, fc: true, part: Y.this.known, mark: evYears.has(Y.this.y) });
        if (Y.next) yrs.push({ y: Y.next.y, v: Y.next.avg, fc: true, lo: Y.next.lo, hi: Y.next.hi, mark: evYears.has(Y.next.y) });
        yearsChart($('mpC3'), yrs, { tick: (v) => (v >= 100 ? Math.round(v) : v), label: (v) => peso(v), aria: 'Yearly average farmgate price, ' + yrs[0].y + ' to ' + yrs[yrs.length - 1].y + '.',
            tip: (r) => '<b>' + esc(peso(r.v)) + '</b>' + esc(r.y + (r.fc ? (r.part !== undefined ? ', ' + r.part + ' months known and the rest forecast' : ', forecast') : (r.part ? ', ' + r.part + ' months on record' : ', yearly average')))
                + (r.lo ? '<small>Likely ' + esc(peso(r.lo)) + ' to ' + esc(peso(r.hi)) + '</small>' : '')
                + (r.mark ? '<small>' + esc(o.events.filter((e) => Number(e.day.slice(0, 4)) === r.y).slice(0, 3).map((e) => e.title).join('; ')) + '</small>' : '') });
        if ($('mpC4')) yearsChart($('mpC4'), o.imports.map((i) => ({ y: i.y, v: i.tons, fc: false })), { tick: (v) => (v >= 1e6 ? (v / 1e6) + 'M' : (v >= 1e3 ? Math.round(v / 1e3) + 'K' : v)),
            label: (v) => (v >= 1e6 ? (v / 1e6).toFixed(1) + 'M' : Math.round(v / 1e3) + 'K') + ' t', aria: 'Imports a year in tons.',
            tip: (r) => { const i = o.imports.find((x) => x.y === r.y); return '<b>' + esc(Math.round(r.v).toLocaleString('en-PH')) + ' tons</b>' + esc(r.y + (i && i.partial ? ', the year so far' : '')) + (i && i.basis ? '<small>' + esc(i.basis) + '</small>' : ''); } });
        paintEvents(5);
        out.querySelectorAll('.mp-prow .mp-bar span').forEach((s, i) => { if (!still()) { const w = s.dataset.w; s.style.transform = 'scaleX(0)'; setTimeout(() => { s.style.transform = 'scaleX(1)'; }, 80 + i * 50); } });
    };

    const planHtml = (o) => {
        const p = st.plan;
        if (!st.harvest) {
            return '<p>Tell us when you harvest (question 3) to weigh selling then against keeping it.</p><p class="mp-note">'
                + esc(o.item.store ? o.item.name + ' keeps up to about ' + o.item.store + ' months in good storage, losing about ' + o.item.loss + '% a month to drying, pests and spoilage.' : o.item.name + ' does not keep: it is sold fresh. Time the harvest for the best months instead.') + '</p>'
                + (!o.item.store && B.urls.when ? '<p class="mp-note"><a href="' + esc(B.urls.when) + '" style="font-weight:800;text-decoration:underline">Plan the planting in When to Plant</a></p>' : '');
        }
        if (!p) return '';
        const h = p.harvest, b = p.best, max = Math.max(...p.rows.map((r) => r.net)), top = p.rows.reduce((a, r) => (r.net > a.net ? r : a), p.rows[0]);
        let s = '<p>' + esc(o.item.store ? 'The price each month you could wait, less what keeping it costs: about ' + p.loss + '% a month lost in storage and 1% for the money.' : 'It does not keep, so it sells the month it is harvested.') + '</p>';
        s += '<div class="mp-big">' + (b.wait > 0
            ? 'Harvest in ' + esc(ymWord(h.ym, true)) + ': about <b>' + esc(peso(h.p)) + '</b>. Keep it and sell in <b>' + esc(ymWord(b.ym, true)) + '</b> for about ' + esc(peso(b.p)) + ', or ' + esc(peso(b.net)) + ' after the losses: <b>' + esc(pct(p.gain)) + '</b>'
                + (st.qty && b.money !== null && h.money !== null ? '. On ' + esc(Number(st.qty).toLocaleString('en-PH') + ' ' + (o.item.unit === 'kg' ? 'kg' : o.item.unit + 's')) + ' that is about <b>' + esc(pesoWhole(b.money - h.money)) + '</b> more.' : '.')
            : 'Sell at harvest in ' + esc(ymWord(h.ym, true)) + ', about <b>' + esc(peso(h.p)) + '</b>' + (st.qty && h.money !== null ? ', about <b>' + esc(pesoWhole(h.money)) + '</b> for ' + esc(Number(st.qty).toLocaleString('en-PH') + ' ' + (o.item.unit === 'kg' ? 'kg' : o.item.unit + 's')) : '') + '. '
                + (o.item.store ? (top.net > h.net ? esc('Keeping it to ' + ymWord(top.ym, true) + ' adds only about ' + Math.max(1, Math.round((top.net / h.p - 1) * 100)) + '% after the losses, too little for the risk.') : 'Keeping it is not expected to pay this time.') : '')) + '</div>';
        if (p.rows.length > 1) {
            s += '<div class="mp-plan">' + p.rows.map((r) => '<div class="mp-prow' + (r.ym === b.ym ? ' is-best' : '') + (r.wait === 0 ? ' is-harvest' : '') + '"><b>' + esc(ymWord(r.ym)) + '</b><span class="mp-bar"><span style="width:' + Math.max(4, r.net / max * 100) + '%"></span></span><em>' + esc(peso(r.net)) + ' <small>' + esc(r.wait ? 'after ' + r.wait + ' mo' : 'at harvest') + '</small></em></div>').join('') + '</div>';
            s += '<p class="mp-hint">After the storage losses. The forecast\'s own range still applies.</p>';
        }
        return s;
    };

    const paintEvents = (n) => {
        const box = $('mpEvs'); if (!box || !st.o) return;
        const kindWord = { import_surge: 'Import surge', import_ban: 'Import ban', import_suspension: 'Import stop', import_order: 'Import order', tariff: 'Tariff', price_cap: 'Price cap', support_price: 'Support price', smuggling: 'Smuggling', export: 'Exports', policy: 'Policy', scheduled: 'Coming up', other: 'Event' };
        box.innerHTML = st.o.events.slice(0, n).map((e) => {
            const d = new Date(e.day + 'T00:00:00');
            const tags = '<span class="' + (e.direction === 'down' ? 'is-down' : (e.direction === 'up' ? 'is-up' : '')) + '">' + esc(e.direction === 'down' ? '▼ Pushed prices down' : (e.direction === 'up' ? '▲ Pushed prices up' : 'Moved prices')) + '</span>'
                + '<span>' + esc(kindWord[e.kind] || 'Event') + '</span>' + (e.ahead ? '<span class="is-ahead">Ahead</span>' : '')
                + (e.move6 !== null ? '<span>' + esc('Farmgate ' + pct(e.move6) + ' six months on') + '</span>' : (e.move3 !== null ? '<span>' + esc('Farmgate ' + pct(e.move3) + ' three months on') + '</span>' : ''));
            const src = (e.sources || []).filter((s) => s.publisher).slice(0, 3);
            return '<article class="mp-ev"><div class="mp-ev-d">' + (e.precision === 'year' ? '<b>' + d.getFullYear() + '</b>' : '<b>' + esc(MN[d.getMonth()]) + '</b><small>' + d.getFullYear() + '</small>') + '</div><div><h4>' + esc(e.title) + '</h4><p>' + esc(e.summary) + '</p>'
                + '<div class="mp-ev-t">' + tags + '</div>' + (src.length ? '<div class="mp-ev-s">Reported by ' + src.map((s) => s.url ? '<a href="' + esc(s.url) + '" target="_blank" rel="noopener nofollow">' + esc(s.publisher) + '</a>' : esc(s.publisher)).join(', ') + '</div>' : '') + '</div></article>';
        }).join('') || '<p class="mp-hint">No import or policy events kept for this crop yet.</p>';
    };

    $('mpOut').addEventListener('click', async (e) => {
        const a = e.target.closest('[data-act]'); if (!a) return;
        if (a.dataset.act === 'moreEv') { paintEvents(999); a.remove(); return; }
        if (a.dataset.act === 'fresh') { st.saved = null; load(); return; }
        if (a.dataset.act === 'reset') {
            Object.assign(st, { crop: null, item: null, place: 'PH', placeName: 'The whole country', harvest: null, qty: null, o: null, plan: null, saved: null });
            $('mpQty').value = ''; paintInputs(); $('mpOut').hidden = true; $('mpEmpty').hidden = false; $('mpEmptySay').textContent = 'Choose a crop, and its prices and forecast show here.';
            window.scrollTo({ top: 0, behavior: still() ? 'auto' : 'smooth' }); return;
        }
        if (a.dataset.act === 'save') {
            a.disabled = true;
            try { const r = await window.api(B.urls.save, { method: 'POST', body: { item: st.item, place: st.place, harvest: st.harvest, qty: st.qty } }); shelf(r.data, false); toast('Saved: ' + r.data.title, 'success'); }
            catch (err) { toast(err.message || 'It could not be saved.', 'error'); }
            finally { a.disabled = false; }
        }
    });
    let rz = null;
    window.addEventListener('resize', () => { clearTimeout(rz); rz = setTimeout(() => { if (st.o && !$('mpOut').hidden) { pathChart($('mpC1'), st.o); monthsChart($('mpC2'), st.o, st.harvest ? idx(st.harvest) % 12 : null); } }, 250); });

    /* ---- the tabs and the saved list ---- */
    const LIST = { rows: [], page: 1, more: false, loaded: false };
    const tab = (saved) => {
        $('mpTabs').classList.toggle('is-saved', saved);
        $('mpTabCalc').setAttribute('aria-selected', String(!saved)); $('mpTabSaved').setAttribute('aria-selected', String(saved));
        $('mpTabCalc').tabIndex = saved ? -1 : 0; $('mpTabSaved').tabIndex = saved ? 0 : -1;
        $('mpPaneCalc').hidden = saved; $('mpPaneSaved').hidden = !saved;
        if (saved && !LIST.loaded) loadList(1);
        if (!saved && st.o) render();
    };
    $('mpTabCalc').addEventListener('click', () => tab(false));
    $('mpTabSaved').addEventListener('click', () => tab(true));
    const shelf = (d, append) => {
        LIST.rows = append ? LIST.rows.concat(d.history || []) : (d.history || []);
        LIST.more = !!d.hasMore; LIST.page = d.page || 1; LIST.loaded = true;
        $('mpTabN').textContent = LIST.rows.length ? (LIST.more ? LIST.rows.length + '+' : LIST.rows.length) : '';
        $('mpSEmpty').hidden = LIST.rows.length > 0; $('mpMoreSaved').hidden = !LIST.more;
        $('mpList').innerHTML = LIST.rows.map((r, i) => '<div class="mp-srow" style="animation-delay:' + Math.min(i, 8) * 40 + 'ms" data-id="' + r.id + '"><span class="mp-sico" aria-hidden="true">' + esc(r.icon || '📈') + '</span>'
            + '<button type="button" class="mp-sopen" data-open="' + r.id + '"><b>' + esc(r.title) + '</b><small>' + esc([r.latest !== null ? 'Then ' + peso(r.latest) : '', r.next !== null ? 'next year about ' + peso(r.next) : '', (r.best || []).length ? 'best ' + r.best.map((m) => MN[m - 1]).join(', ') : '', r.at].filter(Boolean).join(' · ')) + '</small></button>'
            + '<button type="button" class="mp-sdel" data-del="' + r.id + '" aria-label="Move to the Trash" title="Move to the Trash"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M10 11v6m4-6v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg></button></div>').join('');
    };
    const loadList = async (page) => {
        try { const r = await window.api(B.urls.list + '?page=' + page, { method: 'GET' }); shelf(r.data, page > 1); }
        catch (err) { toast(err.message || 'The list could not load.', 'error'); }
    };
    $('mpMoreSaved').addEventListener('click', () => loadList(LIST.page + 1));
    $('mpList').addEventListener('click', async (e) => {
        const o = e.target.closest('[data-open]');
        if (o) {
            try {
                const r = await window.api(B.urls.one + '/' + o.dataset.open, { method: 'GET' }), d = r.data;
                const it = itemOf(d.params.item);
                Object.assign(st, { crop: it ? it.crop : null, item: d.params.item, place: d.params.place, harvest: d.params.harvest, qty: d.params.qty, o: d.outlook, plan: d.plan, saved: d.savedAt,
                    placeName: d.params.place === 'PH' ? 'The whole country' : (d.outlook && d.outlook.place ? (d.outlook.place.level === 'province' || d.outlook.place.level === 'region' ? d.outlook.place.name : d.params.place) : d.params.place) });
                $('mpQty').value = st.qty || ''; paintInputs(); tab(false); window.scrollTo({ top: 0, behavior: still() ? 'auto' : 'smooth' });
            } catch (err) { toast(err.message, 'error'); }
            return;
        }
        const d = e.target.closest('[data-del]'); if (!d) return;
        const ok = window.confirmAction ? await window.confirmAction({ title: 'Delete this forecast?', message: 'It goes to the Trash. You can restore it there for 30 days.', confirmText: 'Delete' }) : confirm('Delete this forecast?');
        if (!ok) return;
        const row = d.closest('.mp-srow'); row && row.classList.add('is-gone');
        try { const r = await window.api(B.urls.del + '/' + d.dataset.del, { method: 'DELETE' }); setTimeout(() => shelf(r.data, false), still() ? 0 : 260); }
        catch (err) { row && row.classList.remove('is-gone'); toast(err.message, 'error'); }
    });

    const boot = () => {
        // Opened from When to Plant or What to Plant: ?crop=okra picks that crop.
        const want = new URLSearchParams(location.search).get('crop');
        if (want && cropOf(want) && want !== st.crop) { st.crop = want; st.item = cropOf(want).items[0].key; st.harvest = null; }
        if (st.item && !itemOf(st.item)) { st.crop = null; st.item = null; }
        paintInputs();
        loadList(1);
        if (st.item) load();
    };
    if (window.api) boot(); else document.addEventListener('DOMContentLoaded', boot, { once: true });
})();
</script>
@endpush
