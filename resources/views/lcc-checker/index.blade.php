@extends('layouts.app')
@section('title', 'LCC Checker')
@section('page-title', 'LCC Checker')
@section('page-subtitle', 'For rice: is it time for nitrogen?')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))
@section('body-class', 'hide-tabbar no-footer')

{{-- The LCC Checker (2026-10-14): the leaf color chart read for a rice
     farmer, free from their own chart or by Anee from leaf photos.

     Second pass (same day): a flow instead of one long form. The field
     first (how it was planted, the planting date from a calendar sheet, the
     variety or its maturity from a sheet of the rice variety catalogue, the
     area), the crop's age in DAT or DAS counted live from the date and the
     season read from the planting month (flippable); then the choice of
     Anee reading photos (credits) or the farmer's own chart (free); then the
     reading, and the answer in a full screen view. Every check is saved and
     listed under "Your checks", to open again, rename or delete. "How the
     leaf color chart works" is a five step animated explainer in a sheet.

     The page measures each photo's dark green color index (DGCI) itself and
     joins the photos into one numbered picture for Anee; the rule (more than
     half below the critical panel) is LccCheckerController::decide(). --}}
@push('head')
<style>
    .lc-wrap { max-width: 46rem; margin: 0 auto; padding-bottom: calc(2rem + env(safe-area-inset-bottom)); }
    .lc-wrap [hidden], .lc-view[hidden], .lc-sheet [hidden] { display: none !important; }
    html.lc-lock, html.lc-lock body { overflow: hidden; }

    /* ---- the hero ---- */
    .lc-hero { position: relative; overflow: hidden; display: flex; gap: .9rem; align-items: flex-start; padding: 1.05rem 1.1rem 1.25rem; border-radius: 1.4rem; color: #e8f1de;
        background: radial-gradient(120% 140% at 100% 0%, #4a7c2a 0%, #1d3310 55%, #0f1d08 100%); }
    .lc-hero::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 5px;
        background: linear-gradient(90deg, #c8d96f 0 25%, #8db544 25% 50%, #4d8a2a 50% 75%, #2c5a17 75%); }
    .lc-hero > img { flex: none; width: 3.2rem; height: 3.2rem; padding: .45rem; border-radius: 1rem; background: rgb(255 255 255 / .12); }
    .lc-only { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .55rem .2rem .45rem; border-radius: 999px; background: #f5c518; color: #3b2f00;
        font-size: .66rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; }
    .lc-only svg { width: .85rem; height: .85rem; }
    .lc-hero h2 { margin-top: .45rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.2rem; line-height: 1.25; color: #fff; }
    .lc-hero p { margin-top: .25rem; font-size: .84rem; line-height: 1.5; color: #c9dcb5; }
    .lc-howbtn { margin-top: .75rem; display: inline-flex; align-items: center; gap: .5rem; padding: .35rem .85rem .35rem .35rem; border-radius: 999px; font-size: .8rem; font-weight: 800;
        color: #fff; background: rgb(255 255 255 / .12); border: 1px solid rgb(255 255 255 / .2);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .lc-howbtn:hover { background: rgb(255 255 255 / .2); transform: translateY(-1px); }
    .lc-howbtn i { width: 1.7rem; height: 1.7rem; border-radius: 999px; display: grid; place-items: center; background: #f5c518; color: #3b2f00; }
    .lc-howbtn i svg { width: .7rem; height: .7rem; margin-left: 2px; }

    /* ---- the steps ---- */
    .lc-steps { display: flex; align-items: center; gap: .45rem; margin: 1.1rem .1rem .75rem; }
    .lc-steps > div { display: flex; align-items: center; gap: .4rem; flex: none; font-size: .74rem; font-weight: 800; color: var(--color-gray-400); transition: color .28s cubic-bezier(.22,1,.36,1); }
    .lc-steps > div b { width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center; font-size: .74rem; background: var(--color-gray-100); color: var(--color-gray-500);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .lc-steps > i { flex: 1; height: 2px; min-width: .6rem; border-radius: 2px; background: var(--color-gray-200); overflow: hidden; position: relative; }
    .lc-steps > i::after { content: ''; position: absolute; inset: 0; background: #6b9f3d; transform: scaleX(0); transform-origin: left; transition: transform .4s cubic-bezier(.22,1,.36,1); }
    .lc-steps > i.is-done::after { transform: none; }
    .lc-steps > div.is-on { color: var(--color-gray-900); }
    .lc-steps > div.is-on b { background: #4a7c2a; color: #fff; box-shadow: 0 0 0 4px rgb(107 159 61 / .2); }
    .lc-steps > div.is-done b { background: #dcebc9; color: #2d5016; }
    @media (max-width: 380px) { .lc-steps > div:not(.is-on) span { display: none; } }

    /* ---- steps slide in, forward from the right, back from the left ---- */
    .lc-step { display: none; }
    /* Steps fade, never slide (2026-10-16, the owner). */
    .lc-step.is-on { display: block; animation: lcInF .26s ease .1s both; }
    .lc-stage[data-dir="back"] .lc-step.is-on { animation-name: lcInB; }
    @keyframes lcInF { from { opacity: 0; } }
    @keyframes lcInB { from { opacity: 0; } }

    /* ---- cards and fields ---- */
    .lc-card { padding: 1.1rem; border-radius: 1.3rem; background: #fff; border: 1px solid var(--color-gray-200); }
    .lc-card-h { display: flex; align-items: center; justify-content: space-between; gap: .6rem; }
    .lc-card-h h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.08rem; color: var(--color-gray-900); }
    .lc-rice { flex: none; display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 800;
        color: #8a5a00; background: #fff4d6; border: 1px solid #f6e3b0; }
    .lc-rice svg { width: .85rem; height: .85rem; }
    .lc-rice.is-free { color: #2d5016; background: #e4efd4; border-color: #cfe3bd; }
    .lc-sub { margin-top: .25rem; font-size: .8rem; line-height: 1.45; color: var(--color-gray-500); }
    .lc-fgrid { display: grid; gap: 1.05rem; margin-top: 1rem; }
    .lc-fgrid > .lc-f-area { order: 4; }
    @media (min-width: 720px) { .lc-fgrid { grid-template-columns: 1fr 1fr; align-items: start; } .lc-fgrid > .lc-f-area { order: 0; } }
    .lc-lab { display: block; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--color-brand-700); margin-bottom: .4rem; }
    .lc-seg { display: flex; gap: .3rem; padding: .25rem; border-radius: .95rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .lc-seg button { flex: 1; min-height: 2.6rem; padding: .45rem .4rem; border-radius: .75rem; font-size: .86rem; font-weight: 800; color: var(--color-gray-600);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .lc-seg button.is-on { background: #4a7c2a; color: #fff; box-shadow: 0 6px 14px -8px rgb(45 80 22 / .8); }

    /* The taglike buttons: an icon, two lines, "Change". */
    .lc-tag { width: 100%; display: flex; align-items: center; gap: .7rem; padding: .55rem .65rem .55rem .55rem; border-radius: 1rem; text-align: left;
        background: #f6f8f3; border: 1px solid #dfe8d4; color: #14210c; cursor: pointer;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .lc-tag:hover { border-color: #a8cc7e; background: #fff; box-shadow: 0 10px 24px -20px rgb(20 33 12 / .6); }
    .lc-tag:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    .lc-tag.is-empty { border-style: dashed; border-color: #b9d69a; }
    .lc-tag.is-shake { animation: lcShake .42s cubic-bezier(.22,1,.36,1); }
    @keyframes lcShake { 20%, 60% { transform: translateX(-5px); } 40%, 80% { transform: translateX(5px); } }
    .lc-tag-ico { flex: none; width: 2.5rem; height: 2.5rem; border-radius: .8rem; display: grid; place-items: center; background: #fff; border: 1px solid #e5ebdf; color: #4a7c2a; }
    .lc-tag-ico svg { width: 1.25rem; height: 1.25rem; }
    .lc-tag.is-bump .lc-tag-ico { animation: lcBump .42s cubic-bezier(.22,1,.36,1); }
    @keyframes lcBump { 40% { transform: scale(1.14) rotate(-6deg); } }
    .lc-tag-t { flex: 1; min-width: 0; }
    .lc-tag-t b { display: block; font-family: var(--font-heading); font-weight: 800; font-size: .98rem; line-height: 1.25; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lc-tag-t small { display: block; margin-top: .05rem; font-size: .76rem; line-height: 1.35; color: #6b7280; }
    .lc-chev { flex: none; display: inline-flex; align-items: center; gap: .3rem; padding: .3rem .55rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #2d5016; background: #e4efd4; }
    .lc-chev svg { width: .8rem; height: .8rem; }

    /* The crop's age, live. */
    .lc-age { display: flex; align-items: center; gap: .75rem; margin-top: .55rem; padding: .55rem .65rem; border-radius: 1rem; background: #f4f9ee; border: 1px solid #dcebc9;
        animation: lcRise .34s cubic-bezier(.22,1,.36,1) both; }
    @keyframes lcRise { from { opacity: 0; transform: translateY(6px); } }
    .lc-age-n { flex: none; min-width: 3.5rem; padding: .35rem .45rem .3rem; border-radius: .85rem; text-align: center; color: #fff; background: linear-gradient(150deg, #2d5016, #4a7c2a); }
    .lc-age-n b { display: block; font-family: var(--font-heading); font-size: 1.45rem; line-height: 1; font-variant-numeric: tabular-nums; }
    .lc-age-n span { font-size: .6rem; font-weight: 900; letter-spacing: .1em; }
    .lc-age-n.is-bump { animation: lcBump .42s cubic-bezier(.22,1,.36,1); }
    .lc-age-t { min-width: 0; }
    .lc-age-t b { display: block; font-size: .86rem; font-weight: 800; color: var(--color-gray-900); }
    .lc-age-t small { display: inline-block; margin-top: .25rem; padding: .14rem .5rem; border-radius: .6rem; font-size: .72rem; font-weight: 800; line-height: 1.35; }
    .lc-age-t small.is-on { background: #dcf3c8; color: #2d5016; }
    .lc-age-t small.is-early { background: #e2e8f0; color: #334155; }
    .lc-age-t small.is-late { background: #fde2c8; color: #9a3412; }
    .lc-season { display: flex; align-items: flex-start; gap: .5rem; margin-top: .55rem; font-size: .8rem; line-height: 1.45; color: var(--color-gray-600); animation: lcRise .34s cubic-bezier(.22,1,.36,1) both; }
    .lc-season > svg { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .05rem; color: #3b82c4; }
    .lc-season.is-dry > svg { color: #d97706; }
    .lc-season b { color: var(--color-gray-900); }
    .lc-season small { display: block; font-size: .74rem; color: var(--color-gray-500); }
    .lc-season button { font-weight: 800; color: #3d6823; text-decoration: underline; text-underline-offset: 3px; white-space: nowrap; }

    .lc-area { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr); gap: .5rem; align-items: stretch; }
    .lc-area-in { display: flex; align-items: center; min-width: 0; border-radius: .9rem; border: 1px solid var(--color-gray-200); background: var(--color-white); overflow: hidden; }
    .lc-area-in.is-bad { border-color: #ef4444; box-shadow: 0 0 0 3px rgb(239 68 68 / .15); animation: lcShake .42s cubic-bezier(.22,1,.36,1); }
    /* The unit, a tag that opens its sheet. */
    .lc-unit { display: flex; align-items: center; gap: .4rem; min-width: 0; min-height: 3rem; padding: .35rem .7rem .35rem .8rem; border-radius: .9rem; text-align: left;
        background: #f6faf1; border: 1.5px solid #cfe5b8; transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .lc-unit:hover { border-color: #86b556; background: #fff; }
    .lc-unit:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    .lc-unit-t { flex: 1; min-width: 0; display: grid; line-height: 1.15; }
    .lc-unit-t small { font-size: .58rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #4a7c2a; }
    .lc-unit-t b { font-size: .92rem; font-weight: 800; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lc-unit > svg { flex: none; width: .95rem; height: .95rem; color: #4a7c2a; }
    .lc-units { display: grid; gap: .45rem; padding: .2rem 1rem calc(1.1rem + env(safe-area-inset-bottom)); }
    .lc-urow { display: flex; align-items: center; gap: .75rem; width: 100%; padding: .7rem .8rem; border-radius: 1rem; text-align: left; background: var(--color-gray-50); border: 1.5px solid var(--color-gray-200);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .lc-urow:hover { border-color: #a8cc7e; }
    .lc-urow[aria-checked="true"] { border-color: #4a7c2a; background: #f1f8e9; }
    .lc-urow .u-ab { flex: none; width: 2.6rem; height: 2.6rem; border-radius: .8rem; display: grid; place-items: center; font-size: .8rem; font-weight: 900; color: #2d5016; background: #e4efd4; }
    .lc-urow .u-t { flex: 1; min-width: 0; display: grid; gap: .05rem; }
    .lc-urow .u-t b { font-size: .95rem; font-weight: 800; color: var(--color-gray-900); }
    .lc-urow .u-t small { font-size: .74rem; color: var(--color-gray-500); }
    .lc-urow .u-ck { flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: var(--color-gray-200); transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .lc-urow .u-ck svg { width: .8rem; height: .8rem; opacity: 0; transition: opacity .2s ease; }
    .lc-urow[aria-checked="true"] .u-ck { background: #4a7c2a; }
    .lc-urow[aria-checked="true"] .u-ck svg { opacity: 1; }
    html.dark .lc-unit { background: #1a2414; border-color: #2f4220; }
    html.dark .lc-unit-t b, html.dark .lc-urow .u-t b { color: #e8efe1; }
    html.dark .lc-urow { background: #151b12; border-color: #2b3a1c; }
    html.dark .lc-urow[aria-checked="true"] { background: #1f2b15; border-color: #6b9f3d; }
    html.dark .lc-urow .u-ab { background: #26331d; color: #cfe0bb; }
    @media (prefers-reduced-motion: reduce) { .lc-unit, .lc-urow, .lc-urow .u-ck { transition: none; } .lc-area-in.is-bad { animation: none; } }
    .lc-area-in:focus-within { border-color: #86b556; box-shadow: 0 0 0 3px rgb(134 181 86 / .2); }
    .lc-area-in input { flex: 1; min-width: 0; width: 4rem; min-height: 3rem; padding: 0 .8rem; border: 0; background: transparent; font-size: 1rem; font-weight: 800; color: var(--color-gray-900); outline: none; box-shadow: none; }
    .lc-area-in span { padding: 0 .7rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-500); }
    .lc-chips { display: flex; gap: .3rem; flex-wrap: wrap; }
    .lc-chip { min-height: 2.3rem; padding: .3rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-200);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .lc-chip.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    .lc-hint { margin-top: .35rem; font-size: .74rem; color: var(--color-gray-500); }
    .lc-next { width: 100%; margin-top: 1.3rem; min-height: 3.2rem; border-radius: 1rem; font-size: 1rem; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: .5rem; }
    .lc-next svg { width: 1.1rem; height: 1.1rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .lc-next:hover svg { transform: translateX(3px); }

    /* ---- step 2: the choice ---- */
    .lc-sum { display: flex; align-items: center; gap: .5rem; padding: .55rem .55rem .55rem .75rem; border-radius: 1.1rem; background: #fff; border: 1px solid var(--color-gray-200); }
    .lc-sum-chips { flex: 1; min-width: 0; display: flex; flex-wrap: wrap; gap: .3rem; }
    .lc-sum-chips span { padding: .16rem .5rem; border-radius: 999px; font-size: .74rem; font-weight: 700; color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .lc-sum > button { flex: none; padding: .45rem .7rem; border-radius: .75rem; font-size: .78rem; font-weight: 800; color: #2d5016; background: #e4efd4; }
    .lc-warn { display: flex; gap: .6rem; margin-top: .7rem; padding: .7rem .8rem; border-radius: 1rem; font-size: .83rem; line-height: 1.5; color: #92400e; background: #fff7e6; border: 1px solid #fde4b8; }
    .lc-warn svg { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .1rem; }
    .lc-q { margin: 1.05rem .1rem 0; font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; color: var(--color-gray-900); }
    .lc-ways { display: grid; gap: .7rem; margin-top: .7rem; }
    @media (min-width: 640px) { .lc-ways { grid-template-columns: 1fr 1fr; } }
    .lc-way { position: relative; display: flex; align-items: center; gap: .85rem; padding: .95rem; border-radius: 1.3rem; text-align: left; background: #fff; border: 1.5px solid var(--color-gray-200);
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .lc-way:hover { transform: translateY(-2px); border-color: #a8cc7e; box-shadow: 0 16px 30px -24px rgb(20 33 12 / .6); }
    .lc-way:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    @media (min-width: 640px) { .lc-way { flex-direction: column; align-items: flex-start; padding: 1.15rem; } }
    .lc-way-art { flex: none; width: 3.4rem; height: 3.4rem; border-radius: 1.05rem; overflow: hidden; display: grid; place-items: center; }
    .lc-way-art img { width: 100%; height: 100%; object-fit: cover; }
    .lc-way.is-chart .lc-way-art { background: linear-gradient(90deg, #c8d96f 0 25%, #8db544 25% 50%, #4d8a2a 50% 75%, #2c5a17 75%); }
    .lc-way.is-chart .lc-way-art svg { width: 1.7rem; height: 1.7rem; color: #fff; filter: drop-shadow(0 1px 2px rgb(0 0 0 / .35)); }
    .lc-way-t { flex: 1; min-width: 0; }
    .lc-way-t b { display: block; font-family: var(--font-heading); font-weight: 800; font-size: 1.02rem; color: var(--color-gray-900); }
    .lc-way-t small { display: block; margin-top: .2rem; font-size: .8rem; line-height: 1.45; color: var(--color-gray-500); }
    .lc-way-p { display: inline-flex; margin-top: .5rem; padding: .2rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 900; color: #8a5a00; background: #fff4d6; }
    .lc-way-p.is-free { color: #2d5016; background: #e4efd4; }
    .lc-way-p.is-lock { color: var(--color-gray-600); background: var(--color-gray-100); }
    .lc-way > svg { flex: none; width: 1.1rem; height: 1.1rem; color: var(--color-gray-300); }
    @media (min-width: 640px) { .lc-way > svg { position: absolute; right: 1rem; top: 1.15rem; } }
    .lc-back { margin-top: .8rem; display: inline-flex; align-items: center; gap: .3rem; padding: .4rem .2rem; font-size: .84rem; font-weight: 800; color: var(--color-gray-500);
        transition: color .28s cubic-bezier(.22,1,.36,1); }
    .lc-back:hover { color: var(--color-gray-800); }
    .lc-back svg { width: 1rem; height: 1rem; }

    /* ---- step 3: photos ---- */
    .lc-tips { counter-reset: tip; display: grid; gap: .55rem; margin-top: .85rem; }
    .lc-tips li { display: flex; gap: .6rem; font-size: .83rem; line-height: 1.5; color: var(--color-gray-600); }
    .lc-tips li::before { counter-increment: tip; content: counter(tip); flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: grid; place-items: center; font-size: .74rem; font-weight: 900;
        color: #2d5016; background: #e4efd4; }
    .lc-shots { display: grid; grid-template-columns: repeat(auto-fill, minmax(4.6rem, 1fr)); gap: .45rem; margin-top: .9rem; }
    .lc-shot { position: relative; aspect-ratio: 1; border-radius: .8rem; overflow: hidden; background: var(--color-gray-100); animation: lcPopIn .32s cubic-bezier(.22,1,.36,1) both; }
    @keyframes lcPopIn { from { opacity: 0; transform: scale(.85); } }
    .lc-shot img { width: 100%; height: 100%; object-fit: cover; }
    .lc-shot b { position: absolute; left: .3rem; top: .3rem; min-width: 1.3rem; height: 1.3rem; display: grid; place-items: center; border-radius: 999px; font-size: .7rem; color: #fff; background: rgb(0 0 0 / .55); }
    .lc-shot i { position: absolute; left: .3rem; bottom: .3rem; padding: .05rem .35rem; border-radius: .4rem; font-style: normal; font-size: .6rem; font-weight: 800; color: #fff; background: rgb(0 0 0 / .5); }
    .lc-shot button { position: absolute; right: .25rem; top: .25rem; width: 1.5rem; height: 1.5rem; border-radius: 999px; color: #fff; background: rgb(0 0 0 / .55); font-size: .7rem; }
    .lc-add { aspect-ratio: 1; border-radius: .8rem; border: 2px dashed #b9d69a; display: grid; place-items: center; text-align: center; font-size: .72rem; font-weight: 800; color: var(--color-brand-700); cursor: pointer;
        transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .lc-add:hover { background: #f4f9ee; }
    .lc-add svg { display: block; width: 1.4rem; height: 1.4rem; margin: 0 auto .2rem; }
    .lc-go { width: 100%; margin-top: 1rem; min-height: 3.2rem; border-radius: 1rem; font-size: 1rem; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: .5rem; }
    .lc-go:disabled { opacity: .55; cursor: not-allowed; }
    .lc-fine { margin-top: .55rem; font-size: .76rem; text-align: center; color: var(--color-gray-500); }
    .lc-why { margin-top: .8rem; padding: .7rem .8rem; border-radius: 1rem; font-size: .82rem; line-height: 1.5; color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-200); }
    .lc-why button { margin-top: .4rem; display: inline-flex; font-weight: 800; color: #3d6823; text-decoration: underline; text-underline-offset: 3px; }

    /* ---- step 3: the chart ---- */
    .lc-strip { display: grid; grid-template-columns: repeat(10, minmax(0, 1fr)); gap: .15rem; margin-top: .9rem; }
    .lc-sl { display: flex; flex-direction: column; align-items: center; gap: .15rem; padding: .35rem 0 .3rem; border-radius: .75rem;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .lc-sl svg { width: 1.15rem; height: 2rem; overflow: visible; }
    @media (min-width: 640px) { .lc-sl svg { width: 1.5rem; height: 2.6rem; } .lc-sl small { font-size: .74rem; } }
    .lc-sl .lc-sl-blade { fill: var(--c, transparent); stroke: var(--color-gray-300); stroke-width: 1.6; transition: fill .28s cubic-bezier(.22,1,.36,1), stroke .28s cubic-bezier(.22,1,.36,1); }
    .lc-sl .lc-sl-rib { fill: none; stroke: var(--color-gray-200); stroke-width: 1; transition: stroke .28s cubic-bezier(.22,1,.36,1); }
    .lc-sl.is-set .lc-sl-blade { stroke: rgb(0 0 0 / .14); }
    .lc-sl.is-set .lc-sl-rib { stroke: rgb(255 255 255 / .5); }
    .lc-sl small { font-size: .66rem; font-weight: 800; color: var(--color-gray-500); font-variant-numeric: tabular-nums; }
    .lc-sl.is-cur { background: #e4efd4; transform: translateY(-2px); }
    .lc-sl.is-cur small { color: #2d5016; }
    .lc-sl.is-low small { color: #b45309; }
    .lc-sl.is-bump svg { animation: lcBump .42s cubic-bezier(.22,1,.36,1); }
    .lc-now { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; margin-top: .9rem; font-size: .8rem; color: var(--color-gray-500); }
    .lc-now b { font-family: var(--font-heading); font-size: 1.05rem; color: var(--color-gray-900); }
    .lc-pal { display: grid; grid-template-columns: 2fr 1fr 2fr 1fr 2fr 1fr 2fr; gap: .3rem; margin-top: 1.5rem; }
    .lc-sw { position: relative; height: 4.6rem; border-radius: .95rem; display: grid; place-items: center; font-family: var(--font-heading); font-weight: 900; font-size: 1.3rem;
        box-shadow: inset 0 0 0 1px rgb(0 0 0 / .07); transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .lc-sw.is-half { font-size: .78rem; }
    .lc-sw:hover { transform: translateY(-2px); }
    .lc-sw:active { transform: scale(.94); }
    .lc-sw.is-pick { box-shadow: 0 0 0 3px #f5c518, inset 0 0 0 1px rgb(0 0 0 / .07); }
    .lc-sw.is-crit::before { content: ''; position: absolute; left: -.22rem; top: -.55rem; bottom: -.3rem; border-left: 2.5px dashed #d97706; }
    .lc-sw.is-crit::after { content: 'line'; position: absolute; left: -.22rem; top: -1.4rem; transform: translateX(-50%); font-family: var(--font-body, inherit); font-size: .6rem; line-height: 1; font-weight: 900;
        letter-spacing: .08em; text-transform: uppercase; color: #b45309; }
    .lc-pal-lab { display: flex; justify-content: space-between; margin-top: .4rem; font-size: .72rem; font-weight: 700; color: var(--color-gray-500); }
    .lc-crit { margin-top: .7rem; font-size: .78rem; line-height: 1.45; color: var(--color-gray-600); }
    .lc-crit b { color: #b45309; }
    .lc-chart-foot { display: flex; align-items: center; justify-content: space-between; margin-top: .9rem; font-size: .8rem; color: var(--color-gray-500); }
    .lc-chart-foot button { font-weight: 800; color: var(--color-gray-500); padding: .35rem .2rem; }
    .lc-chart-foot button:hover { color: #b91c1c; }

    /* ---- your checks ---- */
    .lc-checks { margin-top: 1.7rem; }
    .lc-checks-h { display: flex; align-items: baseline; justify-content: space-between; gap: .6rem; padding: 0 .1rem; }
    .lc-checks-h h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.15rem; color: var(--color-gray-900); }
    .lc-checks-h span { font-size: .78rem; font-weight: 700; color: var(--color-gray-500); }
    .lc-ck-list { display: grid; gap: .6rem; margin-top: .7rem; }
    @media (min-width: 720px) { .lc-ck-list { grid-template-columns: 1fr 1fr; } }
    .lc-ck { position: relative; display: flex; gap: .75rem; padding: .85rem .6rem .8rem .85rem; border-radius: 1.15rem; background: #fff; border: 1px solid var(--color-gray-200); cursor: pointer;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .lc-ck::before { content: ''; position: absolute; left: -1px; top: .95rem; bottom: .95rem; width: 3px; border-radius: 0 3px 3px 0; background: var(--vc, #86b556); }
    .lc-ck.is-due { --vc: #d97706; } .lc-ck.is-ok { --vc: #4a7c2a; } .lc-ck.is-early { --vc: #64748b; } .lc-ck.is-late { --vc: #a8a29e; }
    .lc-ck:hover { transform: translateY(-2px); border-color: #b9d69a; box-shadow: 0 16px 28px -24px rgb(20 33 12 / .6); }
    .lc-ck:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    .lc-ck.is-new { animation: lcRise .4s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--k, 0) * .05s); }
    .lc-ck.is-loading { opacity: .55; pointer-events: none; }
    .lc-ck.is-gone { opacity: 0; transform: scale(.96); pointer-events: none; }
    .lc-ck-date { flex: none; width: 3.1rem; align-self: flex-start; padding: .35rem 0 .4rem; border-radius: .85rem; text-align: center; background: var(--color-gray-50); border: 1px solid var(--color-gray-100); }
    .lc-ck-date b { display: block; font-family: var(--font-heading); font-size: 1.35rem; line-height: 1.1; color: var(--color-gray-900); }
    .lc-ck-date span { font-size: .64rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--color-gray-500); }
    .lc-ck-main { flex: 1; min-width: 0; }
    .lc-ck-top { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
    .lc-pill { display: inline-flex; align-items: center; gap: .3rem; padding: .16rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 800; }
    .lc-pill::before { content: ''; width: .42rem; height: .42rem; border-radius: 999px; background: currentColor; }
    .lc-pill.is-due { color: #92400e; background: #fef3c7; } .lc-pill.is-ok { color: #2d5016; background: #e4efd4; }
    .lc-pill.is-early { color: #334155; background: #e2e8f0; } .lc-pill.is-late { color: #44403c; background: #e7e5e4; }
    .lc-ck-mode { font-size: .7rem; font-weight: 700; color: var(--color-gray-400); }
    .lc-ck h4 { margin-top: .3rem; font-weight: 800; font-size: .92rem; line-height: 1.3; color: var(--color-gray-900); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; overflow-wrap: anywhere; }
    .lc-ck-meta { display: flex; flex-wrap: wrap; gap: .2rem .7rem; margin-top: .3rem; font-size: .76rem; color: var(--color-gray-500); }
    .lc-ck-meta b { font-weight: 800; color: var(--color-gray-800); }
    .lc-ck-dots { display: flex; gap: 3px; margin-top: .5rem; }
    .lc-ck-dots i { width: .62rem; height: .9rem; border-radius: 999px 0; background: var(--c); }
    .lc-ck-dots i.is-low { box-shadow: 0 0 0 1.5px #f59e0b; }
    .lc-ck-acts { flex: none; display: flex; flex-direction: column; gap: .15rem; }
    .lc-ib { width: 2.2rem; height: 2.2rem; border-radius: .75rem; display: grid; place-items: center; color: var(--color-gray-400);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .lc-ib svg { width: 1rem; height: 1rem; }
    .lc-ib:hover { background: var(--color-gray-100); color: var(--color-gray-700); }
    .lc-ib.is-del:hover { background: #fef2f2; color: #b91c1c; }
    .lc-empty { display: flex; align-items: center; gap: .9rem; margin-top: .7rem; padding: 1rem; border-radius: 1.2rem; border: 1.5px dashed var(--color-gray-200); font-size: .84rem; line-height: 1.5; color: var(--color-gray-500); }
    .lc-empty svg { flex: none; width: 2.6rem; height: 2.6rem; }
    .lc-empty b { display: block; color: var(--color-gray-800); }
    .lc-more { display: block; margin: .8rem auto 0; padding: .55rem 1.1rem; border-radius: 999px; font-size: .82rem; font-weight: 800; color: #2d5016; background: #e4efd4; }

    /* ---- the answer, full screen ---- */
    .lc-view { position: fixed; inset: 0; z-index: 90; overflow-y: auto; overscroll-behavior: contain; -webkit-overflow-scrolling: touch; background: var(--color-gray-50);
        opacity: 0; transform: translateY(16px); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .lc-view.is-on { opacity: 1; transform: none; }
    .lc-view:focus { outline: none; }
    .lc-view-bar { position: sticky; top: 0; z-index: 2; display: flex; align-items: center; gap: .55rem; padding: .6rem max(.8rem, calc((100% - 40rem) / 2 + .6rem)); padding-top: max(.6rem, env(safe-area-inset-top));
        background: rgb(250 250 248 / .92); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); border-bottom: 1px solid var(--color-gray-200); }
    .lc-view-x { flex: none; width: 2.5rem; height: 2.5rem; border-radius: 999px; display: grid; place-items: center; color: var(--color-gray-700); background: var(--color-white); border: 1px solid var(--color-gray-200);
        transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .lc-view-x:hover { background: var(--color-gray-100); }
    .lc-view-x svg { width: 1.1rem; height: 1.1rem; }
    .lc-view-name { flex: 1; min-width: 0; display: flex; align-items: center; gap: .4rem; padding: .35rem .5rem; border-radius: .8rem; text-align: left; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .lc-view-name:hover { background: var(--color-gray-100); }
    .lc-view-name b { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .98rem; color: var(--color-gray-900); }
    .lc-view-name svg { flex: none; width: .9rem; height: .9rem; color: var(--color-gray-400); }
    .lc-view-body { max-width: 40rem; margin: 0 auto; padding: 1rem 1rem calc(2.5rem + env(safe-area-inset-bottom)); display: grid; gap: .8rem; }
    .lc-view.is-on .lv-in { animation: lvUp .48s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--k, 0) * .07s + .05s); }
    @keyframes lvUp { from { opacity: 0; transform: translateY(14px); } }
    .lv-saved { justify-self: start; display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .65rem .3rem .45rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: #2d5016; background: #e4efd4; }
    .lv-saved svg { width: .9rem; height: .9rem; }
    .lv-hero { position: relative; overflow: hidden; padding: 1.15rem 1.15rem 1.2rem; border-radius: 1.4rem; color: #fff; }
    .lv-hero::after { content: ''; position: absolute; right: -2.5rem; top: -2.5rem; width: 9rem; height: 9rem; border-radius: 999px 0; background: rgb(255 255 255 / .07); transform: rotate(20deg); }
    .lv-hero.is-due { background: linear-gradient(140deg, #8a4b08, #ca8a04); }
    .lv-hero.is-ok { background: linear-gradient(140deg, #1f3a0f, #4a7c2a); }
    .lv-hero.is-early { background: linear-gradient(140deg, #334155, #64748b); }
    .lv-hero.is-late { background: linear-gradient(140deg, #44403c, #78716c); }
    .lv-top { position: relative; z-index: 1; display: flex; align-items: center; gap: .6rem; }
    .lv-ico { flex: none; width: 2.6rem; height: 2.6rem; border-radius: 999px; display: grid; place-items: center; background: rgb(255 255 255 / .18); }
    .lv-ico svg { width: 1.35rem; height: 1.35rem; }
    .lv-eyebrow { font-size: .72rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: rgb(255 255 255 / .85); }
    .lv-hero h2 { position: relative; z-index: 1; margin-top: .75rem; font-family: var(--font-heading); font-weight: 800; font-size: 1.65rem; line-height: 1.15; color: #fff; }
    .lv-hero p { position: relative; z-index: 1; margin-top: .4rem; font-size: .92rem; line-height: 1.55; color: rgb(255 255 255 / .92); }
    .lv-meter { position: relative; z-index: 1; margin-top: 1.7rem; }
    .lv-bar { position: relative; display: flex; gap: 3px; height: 1rem; }
    .lv-bar i { flex: 1; border-radius: 5px; background: rgb(255 255 255 / .24); transform-origin: left; animation: lvSeg .4s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * .06s + .35s); }
    .lv-bar i.is-low { background: #fff3c4; }
    @keyframes lvSeg { from { opacity: 0; transform: scaleX(.2); } }
    .lv-half { position: absolute; left: 50%; top: -.45rem; bottom: -.45rem; border-left: 2px dashed #fff; }
    .lv-half em { position: absolute; top: -1.1rem; left: 0; transform: translateX(-50%); font-style: normal; font-size: .6rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
    .lv-meter-t { display: flex; justify-content: space-between; gap: .5rem; margin-top: .6rem; font-size: .8rem; color: rgb(255 255 255 / .88); }
    .lv-meter-t b { color: #fff; }
    .lv-card { padding: 1rem; border-radius: 1.2rem; background: #fff; border: 1px solid var(--color-gray-200); }
    .lv-card h3 { display: flex; align-items: center; gap: .45rem; font-weight: 800; font-size: .96rem; color: var(--color-gray-900); }
    .lv-card h3 > svg { width: 1.15rem; height: 1.15rem; color: #4a7c2a; }
    .lv-big { display: flex; align-items: flex-end; gap: .75rem; margin-top: .7rem; }
    .lv-big > b { font-family: var(--font-heading); font-size: 2.5rem; line-height: .95; color: #92400e; white-space: nowrap; }
    .lv-big span { font-size: .86rem; line-height: 1.35; color: var(--color-gray-600); }
    .lv-big em { display: block; font-style: normal; font-weight: 800; color: var(--color-gray-800); }
    .lv-grid3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem; margin-top: .85rem; }
    .lv-grid3 span { padding: .6rem .3rem; border-radius: .9rem; text-align: center; font-size: .64rem; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; color: var(--color-gray-500); background: var(--color-gray-50); }
    .lv-grid3 b { display: block; margin-bottom: .1rem; font-family: var(--font-heading); font-size: 1.15rem; letter-spacing: 0; text-transform: none; color: var(--color-gray-900); }
    .lv-how { display: grid; gap: .4rem; margin-top: .85rem; }
    .lv-how li { display: flex; gap: .5rem; font-size: .84rem; line-height: 1.5; color: var(--color-gray-700); }
    .lv-how li svg { flex: none; width: 1rem; height: 1rem; margin-top: .2rem; color: #4a7c2a; }
    /* The fertilizer on hand (2026-10-15, the owner): a tag on the answer that opens a sheet of bags. */
    .lv-ftag { display: flex; align-items: center; gap: .65rem; width: 100%; margin-top: .75rem; padding: .5rem .55rem .5rem .5rem; border-radius: 1rem; text-align: left; background: #fffbeb; border: 1.5px solid #fde68a;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .lv-ftag:hover { border-color: #f5c518; background: #fff8db; }
    .lv-ftag:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    .lv-ftag.is-bump { animation: lvFBump .42s cubic-bezier(.22,1,.36,1); }
    @keyframes lvFBump { 40% { transform: scale(1.025); } }
    .lv-ftag-g { flex: none; min-width: 4.4rem; padding: .5rem .45rem; border-radius: .75rem; text-align: center; font-family: var(--font-heading); font-size: .84rem; font-weight: 800; font-variant-numeric: tabular-nums; color: #fff; background: #92400e; }
    .lv-ftag-t { flex: 1; min-width: 0; display: grid; line-height: 1.2; }
    .lv-ftag-t small { font-size: .58rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #a16207; }
    .lv-ftag-t b { font-size: 1rem; font-weight: 800; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lv-ftag-c { flex: none; display: inline-flex; align-items: center; gap: .25rem; padding: .4rem .65rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: #92400e; background: #fff; border: 1px solid #fde68a; }
    .lv-ftag-c svg { width: .8rem; height: .8rem; }
    .lv-big i, .lv-grid3 i { font-style: normal; font-variant-numeric: tabular-nums; }
    .lv-fplus { display: flex; gap: .5rem; margin-top: .75rem; padding: .6rem .75rem; border-radius: .9rem; font-size: .84rem; line-height: 1.45; color: #1e3a10; background: #f1f8e9; }
    .lv-fplus svg { flex: none; width: 1rem; height: 1rem; margin-top: .15rem; color: #4a7c2a; }
    .lc-fsub { padding: 0 1.15rem .75rem; font-size: .84rem; line-height: 1.45; color: var(--color-gray-600); }
    .lc-fsub b { color: var(--color-gray-900); }
    .lc-ferts { flex: 1 1 auto; min-height: 0; overflow-y: auto; overscroll-behavior: contain; }
    .lc-frow .u-ab { width: 4rem; font-size: .74rem; font-variant-numeric: tabular-nums; color: #fff; background: #92400e; }
    .lc-frow .u-ab.is-own { color: #6b7280; background: #f3f4f6; border: 1.5px dashed #d1d5db; }
    .lc-frow .u-q { flex: none; display: grid; text-align: right; line-height: 1.15; }
    .lc-frow .u-q b { font-family: var(--font-heading); font-size: .98rem; font-weight: 800; color: #92400e; white-space: nowrap; }
    .lc-frow .u-q small { font-size: .7rem; color: var(--color-gray-500); white-space: nowrap; }
    .lc-fown { display: grid; grid-template-rows: 0fr; opacity: 0; margin-top: -.45rem;
        transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1), margin-top .28s cubic-bezier(.22,1,.36,1); }
    .lc-fown.is-on { grid-template-rows: 1fr; opacity: 1; margin-top: 0; }
    .lc-fown > div { min-height: 0; overflow: hidden; }
    .lc-fown-box { display: grid; gap: .65rem; padding: .8rem; border-radius: 1rem; background: #fffbeb; border: 1.5px solid #fde68a; }
    .lc-fown-in { display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr) auto minmax(0, 1fr); align-items: end; gap: .3rem; }
    .lc-fown-in label { display: grid; gap: .25rem; min-width: 0; }
    .lc-fown-in small { font-size: .6rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; text-align: center; color: #a16207; }
    .lc-fown-in input { width: 100%; min-height: 3rem; border-radius: .8rem; border: 1.5px solid #e5e7eb; background: #fff; text-align: center; font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; color: #14210c; -moz-appearance: textfield; }
    .lc-fown-in input::-webkit-outer-spin-button, .lc-fown-in input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .lc-fown-in input::placeholder { color: #d6d3d1; font-weight: 700; }
    .lc-fown-in input:focus { border-color: #f5c518; outline: none; box-shadow: 0 0 0 3px rgb(245 197 24 / .25); }
    .lc-fown-in > span { padding-bottom: .75rem; font-weight: 900; color: #a16207; }
    .lc-fown-out { font-size: .84rem; line-height: 1.4; color: #78350f; }
    .lc-fown-out.is-bad { color: #b91c1c; }
    .lc-fown .btn { min-height: 2.85rem; }
    .lv-leaves { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: .55rem .45rem; margin-top: .8rem; }
    @media (min-width: 560px) { .lv-leaves { grid-template-columns: repeat(10, minmax(0, 1fr)); } }
    .lv-leaf { text-align: center; animation: lcPopIn .34s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * .04s + .3s); }
    .lv-leaf b { display: grid; place-items: center; height: 2.6rem; border-radius: .85rem; font-family: var(--font-heading); font-weight: 900; font-size: .95rem;
        color: var(--t); background: var(--c); box-shadow: inset 0 0 0 1px rgb(0 0 0 / .08); }
    .lv-leaf small { display: block; margin-top: .2rem; font-size: .62rem; font-weight: 800; color: var(--color-gray-400); }
    .lv-leaf.is-low b { box-shadow: 0 0 0 2.5px #f59e0b; }
    .lv-leaf.is-null b { color: var(--color-gray-500); background: var(--color-gray-100); }
    .lv-legend { display: flex; align-items: center; gap: .45rem; margin-top: .7rem; font-size: .76rem; color: var(--color-gray-500); }
    .lv-legend i { flex: none; width: .9rem; height: .9rem; border-radius: .3rem; box-shadow: 0 0 0 2.5px #f59e0b; }
    .lv-next { display: flex; align-items: center; gap: .9rem; }
    .lv-cal { flex: none; width: 3.7rem; overflow: hidden; border-radius: .95rem; text-align: center; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .lv-cal i { display: block; padding: .18rem; font-style: normal; font-size: .62rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #fff; background: #4a7c2a; }
    .lv-cal b { display: block; padding: .15rem 0 .3rem; font-family: var(--font-heading); font-size: 1.5rem; color: var(--color-gray-900); }
    .lv-cal.is-icon { display: grid; place-items: center; height: 3.7rem; color: #4a7c2a; }
    .lv-cal.is-icon svg { width: 1.6rem; height: 1.6rem; }
    .lv-next small { display: block; font-size: .7rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--color-brand-700); }
    .lv-next b { display: block; font-family: var(--font-heading); font-size: 1.15rem; color: var(--color-gray-900); }
    .lv-next p { margin-top: .15rem; font-size: .82rem; line-height: 1.45; color: var(--color-gray-600); }
    .lv-anee h3 img { width: 1.6rem; height: 1.6rem; border-radius: 999px; object-fit: cover; }
    .lv-conf { margin-left: auto; padding: .15rem .5rem; border-radius: 999px; font-size: .68rem; font-weight: 800; color: var(--color-gray-600); background: var(--color-gray-100); }
    .lv-note { margin-top: .7rem; padding: .7rem .8rem; border-radius: .95rem; font-size: .85rem; line-height: 1.55; color: var(--color-gray-700); background: var(--color-gray-50); }
    .lv-note > b { display: block; margin-bottom: .15rem; font-size: .7rem; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; color: var(--color-brand-700); }
    .lv-note ul { display: grid; gap: .3rem; }
    .lv-note li b { color: var(--color-gray-900); }
    .lv-dl { display: grid; grid-template-columns: 1fr 1fr; gap: .55rem .8rem; margin-top: .75rem; }
    .lv-dl dt { font-size: .66rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: var(--color-gray-400); }
    .lv-dl dd { margin-top: .05rem; font-size: .86rem; font-weight: 700; color: var(--color-gray-800); overflow-wrap: anywhere; }
    .lv-acts { display: grid; gap: .5rem; }
    @media (min-width: 520px) { .lv-acts { grid-template-columns: 2fr 1fr 1fr; } }
    .lv-acts .btn { min-height: 2.9rem; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; }
    .lv-acts .btn svg { width: 1rem; height: 1rem; }
    .lv-acts .lv-del:hover { color: #b91c1c; }

    /* ---- the sheets: from the bottom on a phone, in the middle on a wide screen ---- */
    .lc-sheet { padding: 0; border: 0; background: transparent; max-width: none; max-height: none; width: 100%; height: 100%; margin: 0; inset: 0; overflow: hidden; }
    .lc-sheet::backdrop { background: rgb(10 18 6 / .5); animation: lcFade .28s cubic-bezier(.22,1,.36,1); }
    .lc-sheet.is-closing::backdrop { animation: lcFade .22s cubic-bezier(.22,1,.36,1) reverse forwards; }
    .lc-sbox { position: absolute; left: 0; right: 0; bottom: 0; max-height: 90vh; max-height: 90dvh; display: flex; flex-direction: column; border-radius: 1.4rem 1.4rem 0 0;
        background: #fff; box-shadow: 0 -20px 50px -20px rgb(10 18 6 / .5); animation: lcUp .34s cubic-bezier(.22,1,.36,1); }
    .lc-sheet.is-closing .lc-sbox { animation: lcUp .24s cubic-bezier(.22,1,.36,1) reverse forwards; }
    @media (min-width: 640px) {
        .lc-sbox { left: 50%; top: 50%; bottom: auto; right: auto; width: min(30rem, calc(100vw - 2rem)); max-height: min(46rem, 88vh); border-radius: 1.4rem; transform: translate(-50%, -50%); animation-name: lcPopC; }
        .lc-sheet.is-closing .lc-sbox { animation-name: lcPopC; }
        .lc-sheet.is-date .lc-sbox { width: min(25rem, calc(100vw - 2rem)); }
        .lc-sheet.is-small .lc-sbox { width: min(24rem, calc(100vw - 2rem)); }
        .lc-sheet.is-how .lc-sbox { width: min(32rem, calc(100vw - 2rem)); }
    }
    @keyframes lcFade { from { opacity: 0; } }
    @keyframes lcUp { from { transform: translateY(100%); } }
    @keyframes lcPopC { from { opacity: 0; transform: translate(-50%, -46%) scale(.97); } }
    .lc-sbox:focus { outline: none; }
    .lc-grip { width: 2.6rem; height: .3rem; margin: .55rem auto 0; border-radius: 999px; background: #d7dfcd; }
    @media (min-width: 640px) { .lc-grip { display: none; } }
    .lc-shead { display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem .55rem 1.15rem; }
    .lc-shead h2 { flex: 1; font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; color: #14210c; }
    .lc-x { flex: none; width: 2.4rem; height: 2.4rem; border-radius: 999px; display: grid; place-items: center; color: #4b5563; background: #f3f5ef; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .lc-x:hover { background: #e7ecdf; }
    .lc-x svg { width: 1.1rem; height: 1.1rem; }
    .lc-sfoot { display: flex; align-items: center; gap: .8rem; padding: .8rem 1rem calc(.9rem + env(safe-area-inset-bottom)); border-top: 1px solid var(--color-gray-100); }
    @media (min-width: 640px) { .lc-sfoot { padding-bottom: .9rem; } }

    /* The date sheet. */
    .lc-quick { display: flex; gap: .4rem; overflow-x: auto; padding: 0 1rem .75rem; scrollbar-width: none; }
    .lc-quick::-webkit-scrollbar { display: none; }
    .lc-qc { flex: none; min-height: 2.3rem; padding: .35rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-200);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .lc-qc.is-on { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    .lc-cal { padding: 0 1rem; overflow: hidden; }
    .lc-mon { display: flex; align-items: center; justify-content: space-between; padding: 0 0 .5rem; }
    .lc-mon b { font-family: var(--font-heading); font-weight: 800; font-size: 1.05rem; color: var(--color-gray-900); }
    .lc-mon button { width: 2.6rem; height: 2.6rem; border-radius: 999px; display: grid; place-items: center; color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-200);
        transition: background-color .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .lc-mon button:hover:not(:disabled) { background: #e4efd4; }
    .lc-mon button:disabled { opacity: .3; cursor: default; }
    .lc-mon svg { width: 1.1rem; height: 1.1rem; }
    .lc-wk { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); padding-bottom: .3rem; text-align: center; font-size: .7rem; font-weight: 800; color: var(--color-gray-400); }
    .lc-days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .2rem; }
    .lc-days.is-slide-l { animation: lcSlideL .3s cubic-bezier(.22,1,.36,1); }
    .lc-days.is-slide-r { animation: lcSlideR .3s cubic-bezier(.22,1,.36,1); }
    @keyframes lcSlideL { from { opacity: 0; transform: translateX(-26px); } }
    @keyframes lcSlideR { from { opacity: 0; transform: translateX(26px); } }
    .lc-d { width: 100%; aspect-ratio: 1; max-height: 3.1rem; border-radius: 999px; display: grid; place-items: center; font-size: 1rem; font-weight: 700; color: var(--color-gray-800); font-variant-numeric: tabular-nums;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .lc-d:hover:not(:disabled):not(.is-sel) { background: #eef5e6; }
    .lc-d.is-today { box-shadow: inset 0 0 0 2px #86b556; }
    .lc-d.is-sel { background: #4a7c2a; color: #fff; font-weight: 900; animation: lcSel .34s cubic-bezier(.22,1,.36,1); }
    @keyframes lcSel { 40% { transform: scale(1.16); } }
    .lc-d:disabled { color: var(--color-gray-300); cursor: default; }
    .lc-pickline { flex: 1; min-width: 0; }
    .lc-pickline b { display: block; font-weight: 800; font-size: .95rem; color: var(--color-gray-900); }
    .lc-pickline span { display: block; font-size: .78rem; color: var(--color-gray-500); }
    .lc-sfoot .btn { flex: none; min-height: 2.9rem; padding-left: 1.1rem; padding-right: 1.1rem; }

    /* The variety sheet. */
    .lc-find { margin: 0 1rem .6rem; display: flex; align-items: center; gap: .5rem; padding: 0 .8rem; border-radius: .9rem; background: #f6f8f3; border: 1px solid #e1e9d7; }
    .lc-find:focus-within { border-color: #86b556; background: #fff; }
    .lc-find svg { flex: none; width: 1.05rem; height: 1.05rem; color: #6b9f3d; }
    .lc-find input { flex: 1; min-width: 0; min-height: 2.75rem; border: 0; background: transparent; font-size: 1rem; color: #14210c; outline: none; box-shadow: none; }
    .lc-find input::-webkit-search-cancel-button { -webkit-appearance: none; appearance: none; }
    .lc-sheet.is-tall .lc-sbox { height: 86vh; height: 86dvh; }
    @media (min-width: 640px) { .lc-sheet.is-tall .lc-sbox { height: min(44rem, 88vh); } }
    .lc-slist { flex: 1; overflow-y: auto; overscroll-behavior: contain; padding: 0 .6rem 1rem; }
    .lc-sgh { position: sticky; top: 0; z-index: 1; display: block; padding: .6rem .55rem .4rem; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; background: #fff; }
    .lc-sgh.is-ask { color: #b45309; }
    .lc-mats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem; padding: 0 .4rem; }
    .lc-mat { padding: .7rem .3rem .6rem; border-radius: 1rem; text-align: center; background: #fff; border: 1.5px solid var(--color-gray-200);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .lc-mat:hover { border-color: #a8cc7e; }
    .lc-mat b { display: block; font-family: var(--font-heading); font-weight: 800; font-size: .95rem; color: var(--color-gray-900); }
    .lc-mat span { display: block; font-size: .7rem; color: var(--color-gray-500); }
    .lc-mat i { display: block; width: var(--w); height: .3rem; margin: .45rem auto 0; border-radius: 999px; background: #86b556; }
    .lc-mat.is-on { border-color: #4a7c2a; background: #f4f9ee; box-shadow: 0 0 0 3px rgb(107 159 61 / .18); }
    .lc-unsure { display: block; margin: .5rem .4rem 0; padding: .45rem .6rem; border-radius: .8rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-500); }
    .lc-unsure.is-on { color: #2d5016; background: #eef5e6; }
    .lc-vrow { width: 100%; display: flex; align-items: center; gap: .55rem; padding: .55rem .55rem; border-radius: .9rem; text-align: left; color: #14210c; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .lc-vrow:hover { background: #f4f8ef; }
    .lc-vrow-t { flex: 1; min-width: 0; }
    .lc-vrow-t b { display: block; font-weight: 800; font-size: .93rem; line-height: 1.3; }
    .lc-vrow-t small { display: block; font-size: .74rem; line-height: 1.3; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lc-vd { flex: none; padding: .2rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 900; color: #3d6823; background: #eef5e6; white-space: nowrap; }
    .lc-hy { flex: none; padding: .1rem .4rem; border-radius: .4rem; font-size: .6rem; font-weight: 900; letter-spacing: .05em; text-transform: uppercase; color: #8a5a00; background: #fff4d6; }
    .lc-on { flex: none; width: 1.4rem; height: 1.4rem; border-radius: 999px; display: grid; place-items: center; color: #3b2f00; background: #f5c518;
        opacity: 0; transform: scale(.5); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .lc-on svg { width: .8rem; height: .8rem; }
    .lc-vrow[aria-selected="true"] { background: #eef5e6; }
    .lc-vrow[aria-selected="true"] .lc-on { opacity: 1; transform: none; }
    .lc-vrow.is-typed { margin-bottom: .2rem; border: 1.5px dashed #b9d69a; }
    .lc-vrow.is-typed .lc-vrow-t b { color: #2d5016; }
    .lc-none { padding: 1rem; text-align: center; font-size: .86rem; color: #6b7280; }

    .lc-ren { padding: .3rem 1.15rem .4rem; }
    .lc-ren input { width: 100%; min-height: 2.9rem; font-size: 1rem; }
    .lc-ren p { margin-top: .4rem; font-size: .76rem; color: var(--color-gray-500); }
    .lc-sfoot.is-end { justify-content: flex-end; }

    /* ---- How the leaf color chart works ---- */
    .lx-stage { --lx-bg: #eef5e6; --lx-line: #cddfb9; --lx-ink: #14210c; --lx-mute: #5b6b4e; padding: 0 1rem; touch-action: pan-y; }
    .lx-step { display: none; }
    .lx-step.is-on { display: block; animation: lcRise .34s cubic-bezier(.22,1,.36,1) both; }
    .lx-svg { display: block; width: 100%; height: auto; }
    .lx-step figcaption { padding: .85rem .2rem .2rem; min-height: 7.4rem; }
    .lx-step figcaption b { display: block; font-family: var(--font-heading); font-weight: 800; font-size: 1.08rem; color: var(--color-gray-900); }
    .lx-step figcaption span { display: block; margin-top: .25rem; font-size: .88rem; line-height: 1.55; color: var(--color-gray-600); }
    .lx-foot { display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem calc(.9rem + env(safe-area-inset-bottom)); border-top: 1px solid var(--color-gray-100); }
    @media (min-width: 640px) { .lx-foot { padding-bottom: .9rem; } }
    .lx-dots { flex: 1; display: flex; justify-content: center; gap: .35rem; }
    .lx-dots button { width: .55rem; height: .55rem; padding: 0; border-radius: 999px; background: var(--color-gray-200); transition: width .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .lx-dots button.is-on { width: 1.4rem; background: #4a7c2a; }
    .lx-foot .btn { min-height: 2.75rem; min-width: 5.5rem; }
    .lx-bg { fill: var(--lx-bg); }
    .lx-rows, .lx-grid { fill: none; stroke: var(--lx-line); stroke-width: 1.5; stroke-dasharray: 3 6; }
    .lx-walk { fill: none; stroke: #f59e0b; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; opacity: .75; stroke-dasharray: 1; }
    .lx-blade { fill: none; stroke: #4d8a2a; stroke-width: 2.6; stroke-linecap: round; }
    .lx-badge { fill: #2d5016; }
    .lx-badge-t { fill: #fff; font-size: 10px; font-weight: 800; text-anchor: middle; }
    .lx-pop { transform-box: fill-box; transform-origin: 50% 100%; }
    .lx-leaf { fill: #8db544; stroke: rgb(0 0 0 / .12); stroke-width: 1; }
    .lx-rib { fill: none; stroke: rgb(255 255 255 / .55); stroke-width: 1.6; stroke-linecap: round; }
    .lx-mark { fill: var(--lx-ink); transform: translateX(60px); }
    .lx-pn { font-size: 15px; font-weight: 900; text-anchor: middle; }
    .lx-small { fill: var(--lx-mute); font-size: 11px; font-weight: 700; }
    .lx-crit { fill: none; stroke: #d97706; stroke-width: 2.5; stroke-dasharray: 1; }
    .lx-critt { fill: #b45309; font-size: 11px; font-weight: 900; }
    .lx-halo { fill: none; stroke: #f59e0b; stroke-width: 2.5; transform-box: fill-box; transform-origin: center; }
    .lx-tally rect { fill: #2d5016; }
    .lx-tally text { fill: #fff; font-size: 12px; font-weight: 800; }
    .lx-half { stroke: var(--lx-ink); stroke-width: 2; stroke-dasharray: 3 3; }
    .lx-bag { transform-box: fill-box; transform-origin: 50% 100%; }
    .lx-sack { fill: #fff; stroke: #9fb98a; stroke-width: 2; }
    .lx-sack-top { fill: none; stroke: #9fb98a; stroke-width: 2; }
    .lx-sack-t { fill: #2d5016; font-size: 14px; font-weight: 900; }
    .lx-sack-s { fill: #6b7280; font-size: 10px; font-weight: 800; }
    .lx-kg { fill: var(--lx-ink); font-size: 22px; font-weight: 900; }
    .lx-axis { stroke: var(--lx-line); stroke-width: 4; stroke-linecap: round; }
    .lx-axis-on { stroke: #4d8a2a; stroke-width: 4; stroke-linecap: round; stroke-dasharray: 1; }
    .lx-tick { fill: #fff; stroke: #4d8a2a; stroke-width: 2.5; }
    .lx-panicle { fill: none; stroke: #c08a1e; stroke-width: 2.4; stroke-linecap: round; }
    .lx-grain { fill: #d9a531; }
    .lx-step.is-on .lx-walk { animation: lxDraw 2.6s cubic-bezier(.45,0,.2,1) .2s both; }
    .lx-step.is-on .lx-pop { animation: lxPop .5s cubic-bezier(.22,1,.36,1) both; animation-delay: calc(var(--i) * .16s + .15s); }
    .lx-step.is-on .lx-leaf { animation: lxHue 6s ease-in-out infinite; }
    .lx-step.is-on .lx-mark { animation: lxSlide 6s cubic-bezier(.22,1,.36,1) infinite; }
    .lx-step.is-on .lx-crit { animation: lxDraw .9s cubic-bezier(.45,0,.2,1) 1.8s both; }
    .lx-step.is-on .lx-critt { animation: lxFade .4s ease 2.3s both; }
    .lx-step.is-on .lx-halo { animation: lxHalo .5s cubic-bezier(.22,1,.36,1) 2.5s both; }
    .lx-step.is-on .lx-tally { animation: lxFade .5s cubic-bezier(.22,1,.36,1) 3s both; }
    .lx-step.is-on .lx-bag { animation: lxDrop .7s cubic-bezier(.34,1.56,.64,1) 1.9s both; }
    .lx-step.is-on .lx-fade { animation: lxFade .5s cubic-bezier(.22,1,.36,1) var(--d, 1s) both; }
    .lx-step.is-on .lx-axis-on { animation: lxDraw 2.6s linear .2s both; }
    @keyframes lxDraw { from { stroke-dashoffset: 1; } }
    @keyframes lxPop { from { opacity: 0; transform: scale(.3) translateY(6px); } }
    @keyframes lxFade { from { opacity: 0; transform: translateY(6px); } }
    @keyframes lxHalo { from { opacity: 0; transform: scale(.3); } }
    @keyframes lxDrop { from { opacity: 0; transform: translateY(-46px); } }
    @keyframes lxHue { 0%, 20% { fill: #c8d96f; } 25%, 45% { fill: #8db544; } 50%, 70% { fill: #4d8a2a; } 75%, 95% { fill: #2c5a17; } 100% { fill: #c8d96f; } }
    @keyframes lxSlide { 0%, 20% { transform: translateX(0); } 25%, 45% { transform: translateX(60px); } 50%, 70% { transform: translateX(120px); } 75%, 95% { transform: translateX(180px); } 100% { transform: translateX(0); } }

    /* ---- night ---- */
    html.dark .lc-card, html.dark .lc-sum, html.dark .lc-way, html.dark .lc-ck, html.dark .lv-card, html.dark .lc-sbox, html.dark .lc-mat { background: #151b12; border-color: #2b3a1c; }
    html.dark .lc-tag { background: #1c2616; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .lc-tag:hover { background: #22301a; }
    html.dark .lc-tag-ico { background: #151b12; border-color: #2b3a1c; color: #a5c97e; }
    html.dark .lc-tag-t small { color: #b9caa8; }
    html.dark .lc-chev, html.dark .lc-sum > button, html.dark .lc-more { background: #2b3a1c; color: #d5e3c5; }
    html.dark .lc-rice { background: rgb(245 197 24 / .12); border-color: rgb(245 197 24 / .25); color: #fcd34d; }
    html.dark .lc-rice.is-free { background: rgb(107 159 61 / .2); border-color: rgb(107 159 61 / .3); color: #b9dc98; }
    html.dark .lc-age { background: #1a2414; border-color: #2b3a1c; }
    html.dark .lc-age-t small.is-on { background: rgb(107 159 61 / .22); color: #b9dc98; }
    html.dark .lc-age-t small.is-early { background: rgb(100 116 139 / .28); color: #cbd5e1; }
    html.dark .lc-age-t small.is-late { background: rgb(234 88 12 / .2); color: #fdba74; }
    html.dark .lc-season button, html.dark .lc-why button { color: #a5c97e; }
    html.dark .lc-steps > div.is-done b { background: #2b3a1c; color: #b9dc98; }
    html.dark .lc-warn { background: rgb(180 83 9 / .14); border-color: rgb(251 191 36 / .25); color: #fcd34d; }
    html.dark .lc-way-p { background: rgb(245 197 24 / .14); color: #fcd34d; }
    html.dark .lc-way-p.is-free { background: rgb(107 159 61 / .2); color: #b9dc98; }
    html.dark .lc-tips li::before, html.dark .lv-saved { background: rgb(107 159 61 / .2); color: #b9dc98; }
    html.dark .lc-add { border-color: #3d5a28; }
    html.dark .lc-add:hover { background: #1a2414; }
    html.dark .lc-sl.is-cur { background: #22301a; }
    html.dark .lc-sl.is-cur small { color: #b9dc98; }
    html.dark .lc-pill.is-due { background: rgb(202 138 4 / .2); color: #fcd34d; }
    html.dark .lc-pill.is-ok { background: rgb(107 159 61 / .2); color: #b9dc98; }
    html.dark .lc-pill.is-early { background: rgb(100 116 139 / .28); color: #cbd5e1; }
    html.dark .lc-pill.is-late { background: rgb(120 113 108 / .3); color: #d6d3d1; }
    html.dark .lc-ib.is-del:hover { background: rgb(185 28 28 / .2); color: #fca5a5; }
    html.dark .lc-view { background: #0d110a; }
    html.dark .lc-view-bar { background: rgb(13 17 10 / .92); border-color: #2b3a1c; }
    html.dark .lc-view-x { background: #151b12; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .lv-big > b { color: #fcd34d; }
    html.dark .lv-cal { background: #1c2616; border-color: #2b3a1c; }
    html.dark .lv-card h3 > svg, html.dark .lv-how li svg { color: #a5c97e; }
    html.dark .lc-shead h2 { color: #e8efe1; }
    html.dark .lc-x { background: #1c2616; color: #d5e3c5; }
    html.dark .lc-grip { background: #2b3a1c; }
    html.dark .lc-find { background: #1c2616; border-color: #2b3a1c; }
    html.dark .lc-find input { color: #e8efe1; }
    html.dark .lc-sgh { background: #151b12; color: #a5c97e; }
    html.dark .lc-vrow { color: #e8efe1; }
    html.dark .lc-vrow-t small { color: #b9caa8; }
    html.dark .lc-vrow:hover, html.dark .lc-vrow[aria-selected="true"], html.dark .lc-unsure.is-on { background: #1c2616; }
    html.dark .lc-vd { background: #2b3a1c; color: #d5e3c5; }
    html.dark .lc-hy { background: rgb(245 197 24 / .14); color: #fcd34d; }
    html.dark .lc-mat.is-on { background: #1a2414; border-color: #6b9f3d; }
    html.dark .lc-d:hover:not(:disabled):not(.is-sel), html.dark .lc-mon button:hover:not(:disabled) { background: #22301a; }
    html.dark .lv-grid3 span, html.dark .lv-note, html.dark .lc-ck-date, html.dark .lc-sum-chips span { background: #1c2616; border-color: #2b3a1c; }
    html.dark .lx-stage { --lx-bg: #1c2616; --lx-line: #34482a; --lx-ink: #e8efe1; --lx-mute: #a9bb98; }
    html.dark .lv-ftag { background: rgb(202 138 4 / .1); border-color: rgb(202 138 4 / .35); }
    html.dark .lv-ftag:hover { background: rgb(202 138 4 / .16); border-color: #ca8a04; }
    html.dark .lv-ftag-g, html.dark .lc-frow .u-ab { background: #b45309; color: #fff; }
    html.dark .lv-ftag-t small { color: #fbbf24; }
    html.dark .lv-ftag-t b { color: #fef3c7; }
    html.dark .lv-ftag-c { background: #1c2616; border-color: rgb(202 138 4 / .35); color: #fcd34d; }
    html.dark .lv-fplus { background: #1c2616; color: #d5e3c5; }
    html.dark .lv-fplus svg { color: #a5c97e; }
    html.dark .lc-fsub { color: #b9caa8; }
    html.dark .lc-fsub b { color: #e8efe1; }
    html.dark .lc-frow .u-q b { color: #fcd34d; }
    html.dark .lc-frow .u-q small { color: #9fb08f; }
    html.dark .lc-frow .u-ab.is-own { background: #151b12; border-color: #3d5a28; color: #b9caa8; }
    html.dark .lc-fown-box { background: rgb(202 138 4 / .08); border-color: rgb(202 138 4 / .3); }
    html.dark .lc-fown-in input { background: #0d110a; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .lc-fown-in input::placeholder { color: #44523a; }
    html.dark .lc-fown-in small, html.dark .lc-fown-in > span { color: #fbbf24; }
    html.dark .lc-fown-out { color: #fde68a; }
    html.dark .lc-fown-out.is-bad { color: #fca5a5; }

    @media (prefers-reduced-motion: reduce) {
        .lc-step.is-on, .lc-age, .lc-season, .lc-shot, .lc-ck.is-new, .lc-view.is-on .lv-in, .lv-bar i, .lv-leaf, .lc-days.is-slide-l, .lc-days.is-slide-r, .lc-d.is-sel,
        .lc-tag.is-bump .lc-tag-ico, .lc-tag.is-shake, .lc-age-n.is-bump, .lc-sl.is-bump svg, .lx-step.is-on,
        .lc-sbox, .lc-sheet::backdrop, .lc-sheet.is-closing .lc-sbox, .lc-sheet.is-closing::backdrop, .lv-ftag.is-bump { animation: none !important; }
        .lx-step *, .lx-step.is-on * { animation: none !important; }
        .lv-ftag, .lc-fown { transition: none; }
        .lc-view, .lc-seg button, .lc-tag, .lc-way, .lc-ck, .lc-sl, .lc-sw, .lc-d, .lc-on, .lc-steps > i::after, .lx-dots button { transition: none; }
    }
    html.sm-still .lc-step.is-on, html.sm-still .lc-view.is-on .lv-in, html.sm-still .lx-step *, html.sm-still .lv-bar i, html.sm-still .lv-leaf { animation: none !important; }
</style>
@endpush

@section('content')
<script type="application/json" id="lcBoot">@json(['today' => $today, 'varieties' => $varieties])</script>
<div class="lc-wrap">
    <section class="lc-hero">
        <img src="{{ asset('images/icons/leaf-color.svg') }}" alt="">
        <div>
            <span class="lc-only"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 21V9M12 9c-2.5-1-4-3.5-4-6 2.5 1 4 3.5 4 6zM12 13c2.5-1 4-3.5 4-6-2.5 1-4 3.5-4 6zM12 17c-2.5-1-4-3.5-4-6 2.5 1 4 3.5 4 6z"/></svg>For rice only</span>
            <h2>Leaf color, read for you{{ \App\Support\Tagalog::flag('lcc-checker') }}</h2>
            <p>The leaf color chart times nitrogen on rice. Read 10 rice leaves each week, from 14 days after transplanting or 21 after sowing, and the checker says if urea is due and how much for your field.</p>
            <button type="button" class="lc-howbtn" id="lcHowOpen"><i><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15l13-7.5z"/></svg></i>How the leaf color chart works</button>
        </div>
    </section>

    <div class="lc-steps" id="lcSteps" aria-label="Steps">
        <div class="is-on" data-s="1"><b>1</b><span>Your field</span></div><i></i>
        <div data-s="2"><b>2</b><span>How to read</span></div><i></i>
        <div data-s="3"><b>3</b><span>Read the leaves</span></div>
    </div>

    <div class="lc-stage" id="lcStage" data-dir="fwd">
        {{-- 1. The field --}}
        <section class="lc-step is-on" data-step="1" aria-label="Your rice field">
            <div class="lc-card">
                <div class="lc-card-h">
                    <h3>Your rice field</h3>
                    <span class="lc-rice"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 21V9M12 9c-2.5-1-4-3.5-4-6 2.5 1 4 3.5 4 6zM12 13c2.5-1 4-3.5 4-6-2.5 1-4 3.5-4 6z"/></svg>Rice only</span>
                </div>
                <p class="lc-sub">The leaf color chart is made for rice leaves. It does not work for corn, vegetables or other crops.</p>
                <div class="lc-fgrid">
                    <div>
                        <span class="lc-lab">How it was planted</span>
                        <div class="lc-seg" id="lcMethod" role="radiogroup" aria-label="How it was planted">
                            <button type="button" data-v="tpr" class="is-on" role="radio" aria-checked="true">Transplanted{{ \App\Support\Tagalog::flag('transplanting') }}</button>
                            <button type="button" data-v="dsr" role="radio" aria-checked="false">Direct seeded{{ \App\Support\Tagalog::flag('direct-seeding') }}</button>
                        </div>
                    </div>
                    <div class="lc-f-area">
                        <span class="lc-lab" id="lcAreaLab">Area</span>
                        {{-- The size, and its unit as a tag that opens a sheet (2026-10-15, the owner). --}}
                        <div class="lc-area">
                            <label class="lc-area-in"><span class="sr-only">How big</span><input type="number" inputmode="decimal" min="0.0001" step="any" id="lcArea" value="1" aria-labelledby="lcAreaLab" placeholder="0"></label>
                            <button type="button" class="lc-unit" id="lcUnitBtn" aria-haspopup="dialog" aria-label="Area unit">
                                <span class="lc-unit-t"><small>Unit</small><b id="lcUnitNow">Hectares</b></span>
                                <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                        <p class="lc-hint" id="lcAreaHint">1 hectare is 10,000 square meters.</p>
                    </div>
                    <div>
                        <span class="lc-lab" id="lcDateLab">Transplanted on</span>
                        <button type="button" class="lc-tag is-empty" id="lcDateBtn" aria-haspopup="dialog">
                            <span class="lc-tag-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="3"/><path d="M8 3v4M16 3v4M3.5 10h17M8 14h2M14 14h2M8 17.5h2"/></svg></span>
                            <span class="lc-tag-t"><b id="lcDateNow">Pick the date</b><small id="lcDateSub">The day the seedlings went in</small></span>
                            <span class="lc-chev">Pick<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></span>
                        </button>
                        <div class="lc-age" id="lcAge" hidden aria-live="polite">
                            <div class="lc-age-n" id="lcAgeBox"><b id="lcAgeN">0</b><span id="lcAgeW" data-tl="dat">DAT</span></div>
                            <div class="lc-age-t"><b id="lcAgeLine"></b><small id="lcAgeState"></small></div>
                        </div>
                        <div class="lc-season" id="lcSeason" hidden>
                            <svg id="lcSeasonIco" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"></svg>
                            <div><span id="lcSeasonLine"></span> <button type="button" id="lcSeasonFlip"></button><small id="lcSeasonWhy"></small></div>
                        </div>
                    </div>
                    <div>
                        <span class="lc-lab">Variety and maturity{{ \App\Support\Tagalog::flag('maturity-days') }}</span>
                        <button type="button" class="lc-tag is-empty" id="lcVarBtn" aria-haspopup="dialog">
                            <span class="lc-tag-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 21V9M12 9c-2.5-1-4-3.5-4-6 2.5 1 4 3.5 4 6zM12 13c2.5-1 4-3.5 4-6-2.5 1-4 3.5-4 6zM12 17c-2.5-1-4-3.5-4-6 2.5 1 4 3.5 4 6z"/></svg></span>
                            <span class="lc-tag-t"><b id="lcVarNow">Not sure</b><small id="lcVarSub">Pick your variety or how long it takes</small></span>
                            <span class="lc-chev">Change<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></span>
                        </button>
                        <p class="lc-hint">The days to harvest tell when the first flowers come. Nitrogen stops there.</p>
                    </div>
                </div>
                <button type="button" class="btn btn-primary lc-next" id="lcNext">Next<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg></button>
            </div>
        </section>

        {{-- 2. The choice --}}
        <section class="lc-step" data-step="2" aria-label="How to read the leaves">
            <div class="lc-sum"><div class="lc-sum-chips" id="lcSum"></div><button type="button" data-go="1">Edit</button></div>
            <div class="lc-warn" id="lcWarn" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7.5V13M12 16.5v.01"/></svg><span id="lcWarnText"></span></div>
            <h3 class="lc-q">How will you read the leaves?</h3>
            <div class="lc-ways">
                <button type="button" class="lc-way is-anee" id="lcWayAnee">
                    <span class="lc-way-art"><img id="lcAneeFace" src="{{ asset('images/anee/avatar-512.jpg') }}" alt=""></span>
                    <span class="lc-way-t"><b>Let Anee read photos</b><small>Photograph 10 leaves. Anee matches each one to the chart and looks for spots, bites and other shortages.</small><span class="lc-way-p" id="lcWayPrice">Uses credits</span></span>
                    <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button type="button" class="lc-way is-chart" id="lcWayChart">
                    <span class="lc-way-art"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 19c0-9 5-15 14-17-1 9-6 15-14 17zM5 19l7-8"/></svg></span>
                    <span class="lc-way-t"><b>Use my leaf color chart{{ \App\Support\Tagalog::flag('leaf-color-chart') }}</b><small>Hold your chart to each leaf and tap the panel it matches. The checker does the counting.</small><span class="lc-way-p is-free">Free</span></span>
                    <svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
            <button type="button" class="lc-back" data-go="1"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>Back to your field</button>
        </section>

        {{-- 3a. Photos for Anee --}}
        <section class="lc-step" data-step="3a" aria-label="Leaf photos for Anee">
            <div class="lc-card">
                <div class="lc-card-h"><h3>Photograph 10 leaves</h3><span class="lc-rice">Rice leaves</span></div>
                <ol class="lc-tips">
                    <li>Pick 10 hills spread across the field. On each, take the youngest leaf that is fully open{{ \App\Support\Tagalog::flag('youngest-full-leaf') }}.</li>
                    <li>Lay the leaf flat on white paper and shade it with your body. Morning, about 8 to 10 am, is best.</li>
                    <li>Skip leaves with insect bites or spots. Anee still tells you what she sees.</li>
                </ol>
                <div class="lc-shots" id="lcShots"></div>
                <input type="file" accept="image/*" multiple id="lcFiles" hidden>
                <button type="button" class="btn btn-primary lc-go" id="lcRead" disabled>Add at least 3 leaves</button>
                <p class="lc-fine" id="lcBal"></p>
                <div class="lc-why" id="lcWhy" hidden><span id="lcWhyText"></span><br><button type="button" data-go="3b">Use my own chart instead, free</button></div>
            </div>
            <button type="button" class="lc-back" data-go="2"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>Choose another way</button>
        </section>

        {{-- 3b. The farmer's own chart --}}
        <section class="lc-step" data-step="3b" aria-label="Your leaf color chart">
            <div class="lc-card">
                <div class="lc-card-h"><h3>Tap the panel each leaf matches{{ \App\Support\Tagalog::flag('lcc-panel') }}</h3><span class="lc-rice is-free">Free</span></div>
                <p class="lc-sub">Hold the chart against the middle of the leaf, in your shade. Between two panels? Tap the half.</p>
                <div class="lc-strip" id="lcStrip" role="group" aria-label="Your 10 leaves"></div>
                <div class="lc-now"><b id="lcLeafOf">Leaf 1</b><span id="lcLeafHint">Tap its panel below</span></div>
                <div class="lc-pal" id="lcPal" role="group" aria-label="Chart panels"></div>
                <div class="lc-pal-lab"><span>Pale</span><span>Deep green</span></div>
                <p class="lc-crit" id="lcCrit"></p>
                <div class="lc-chart-foot"><span id="lcCount">0 of 10 read</span><button type="button" id="lcClear">Clear all</button></div>
                <button type="button" class="btn btn-primary lc-go" id="lcCheck" disabled>Read at least 3 leaves</button>
            </div>
            <button type="button" class="lc-back" data-go="2"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>Choose another way</button>
        </section>
    </div>

    <section class="lc-checks" aria-labelledby="lcChecksTitle">
        <div class="lc-checks-h"><h3 id="lcChecksTitle">Your checks</h3><span id="lcChecksN"></span></div>
        <div class="lc-ck-list" id="lcList"></div>
        <div class="lc-empty" id="lcEmpty" hidden>
            <svg viewBox="0 0 48 48" aria-hidden="true"><path d="M10 40C10 22 20 10 40 6c-2 18-12 30-30 34z" fill="#8db544"/><path d="M10 40l16-18" stroke="#fff" stroke-width="2" stroke-linecap="round" fill="none" opacity=".7"/></svg>
            <div><b>No checks yet</b>Every check you run is saved here, so you can see your field week by week.</div>
        </div>
        <button type="button" class="lc-more" id="lcMore" hidden>Show more</button>
    </section>
    @include('sm.partials.anee-wait')
</div>

{{-- The answer, full screen. --}}
<div class="lc-view" id="lcView" hidden role="dialog" aria-modal="true" aria-labelledby="lcViewTitle" tabindex="-1">
    <div class="lc-view-bar">
        <button type="button" class="lc-view-x" id="lcViewX" aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
        <button type="button" class="lc-view-name" id="lcViewName" aria-label="Rename this check"><b id="lcViewTitle">Leaf color check</b><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L19 9l-4-4L4 16v4zM14 6l4 4"/></svg></button>
    </div>
    <div class="lc-view-body" id="lcViewBody"></div>
</div>

{{-- The planting date. --}}
<dialog class="lc-sheet is-date" id="lcDateSheet" aria-labelledby="lcDateTitle">
    <div class="lc-sbox" tabindex="-1">
        <div class="lc-grip" aria-hidden="true"></div>
        <div class="lc-shead"><h2 id="lcDateTitle">When was the rice transplanted?</h2><button type="button" class="lc-x" aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="lc-quick" id="lcQuick"></div>
        <div class="lc-cal">
            <div class="lc-mon">
                <button type="button" id="lcPrevM" aria-label="Earlier month"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
                <b id="lcMonName" aria-live="polite"></b>
                <button type="button" id="lcNextM" aria-label="Later month"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
            </div>
            <div class="lc-wk" aria-hidden="true"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div>
            <div class="lc-days" id="lcDays"></div>
        </div>
        <div class="lc-sfoot">
            <div class="lc-pickline"><b id="lcPickDate">Tap a day</b><span id="lcPickAge">Future days are off</span></div>
            <button type="button" class="btn btn-primary" id="lcDateUse" disabled>Use this date</button>
        </div>
    </div>
</dialog>

{{-- The variety, or its maturity. --}}
<dialog class="lc-sheet is-tall" id="lcVarSheet" aria-labelledby="lcVarTitle">
    <div class="lc-sbox" tabindex="-1">
        <div class="lc-grip" aria-hidden="true"></div>
        <div class="lc-shead"><h2 id="lcVarTitle">Variety and maturity</h2><button type="button" class="lc-x" aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <label class="lc-find">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
            <span class="sr-only">Search a rice variety</span>
            <input type="search" id="lcVarQ" placeholder="Search a variety, like Rc 222 or Tubigan 18" autocomplete="off" maxlength="60">
        </label>
        <div class="lc-slist" id="lcVarList" role="listbox" aria-label="Rice varieties">
            <button type="button" class="lc-vrow is-typed" id="lcVarTyped" hidden><span class="lc-vrow-t"><b>Use “<span id="lcVarTypedName"></span>”</b><small>Not on the list? Keep your own name, then pick its maturity.</small></span></button>
            <div id="lcMatGroup">
                <b class="lc-sgh" id="lcMatHead">Not sure of the variety? Pick how long it takes</b>
                <div class="lc-mats">
                    <button type="button" class="lc-mat" data-mat="early" style="--w:52%"><b>Early{{ \App\Support\Tagalog::flag('early-maturing') }}</b><span>100 to 110 days</span><i></i></button>
                    <button type="button" class="lc-mat" data-mat="medium" style="--w:72%"><b>Medium{{ \App\Support\Tagalog::flag('medium-maturing') }}</b><span>111 to 120 days</span><i></i></button>
                    <button type="button" class="lc-mat" data-mat="late" style="--w:92%"><b>Late{{ \App\Support\Tagalog::flag('late-maturing') }}</b><span>121 to 130 days</span><i></i></button>
                </div>
                <button type="button" class="lc-unsure" data-mat="">I am not sure of either</button>
            </div>
            <div id="lcPopGroup"><b class="lc-sgh">Popular with farmers</b><div id="lcPopRows"></div></div>
            <div id="lcAllGroup"><b class="lc-sgh" id="lcAllHead">All varieties</b><div id="lcAllRows"></div></div>
            <p class="lc-none" id="lcVarNone" hidden>No variety by that name on our list. Use your own name above.</p>
        </div>
    </div>
</dialog>

{{-- The area unit. --}}
<dialog class="lc-sheet is-small" id="lcUnitSheet" aria-labelledby="lcUnitTitle">
    <div class="lc-sbox" tabindex="-1">
        <div class="lc-grip" aria-hidden="true"></div>
        <div class="lc-shead"><h2 id="lcUnitTitle">Area unit</h2><button type="button" class="lc-x" aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="lc-units" id="lcUnitList" role="radiogroup" aria-labelledby="lcUnitTitle"></div>
    </div>
</dialog>

{{-- The fertilizer on hand: the same nitrogen, worked out for each bag (2026-10-15, the owner). --}}
<dialog class="lc-sheet" id="lcFertSheet" aria-labelledby="lcFertTitle">
    <div class="lc-sbox" tabindex="-1">
        <div class="lc-grip" aria-hidden="true"></div>
        <div class="lc-shead"><h2 id="lcFertTitle">Which fertilizer do you have?</h2><button type="button" class="lc-x" aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <p class="lc-fsub" id="lcFertSub"></p>
        <div class="lc-units lc-ferts" id="lcFertList" role="radiogroup" aria-labelledby="lcFertTitle"></div>
    </div>
</dialog>

{{-- Rename a saved check. --}}
<dialog class="lc-sheet is-small" id="lcRenSheet" aria-labelledby="lcRenTitle">
    <form class="lc-sbox" tabindex="-1" id="lcRenForm">
        <div class="lc-grip" aria-hidden="true"></div>
        <div class="lc-shead"><h2 id="lcRenTitle">Rename this check</h2><button type="button" class="lc-x" aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="lc-ren">
            <label class="sr-only" for="lcRenInput">Name</label>
            <input type="text" class="form-input" id="lcRenInput" maxlength="120" autocomplete="off" placeholder="For example North paddy, week 3">
            <p>A name you will know, like the field or the week.</p>
        </div>
        <div class="lc-sfoot is-end">
            <button type="button" class="btn btn-ghost" data-close>Cancel</button>
            <button type="submit" class="btn btn-primary" id="lcRenSave">Save the name</button>
        </div>
    </form>
</dialog>

{{-- How the leaf color chart works. --}}
<dialog class="lc-sheet is-how" id="lcHowSheet" aria-labelledby="lcHowTitle">
    <div class="lc-sbox" tabindex="-1">
        <div class="lc-grip" aria-hidden="true"></div>
        <div class="lc-shead"><h2 id="lcHowTitle">How the leaf color chart works</h2><button type="button" class="lc-x" aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="lx-stage" id="lxStage" aria-live="polite"></div>
        <div class="lx-foot">
            <button type="button" class="btn btn-white" id="lxBack">Back</button>
            <div class="lx-dots" id="lxDots"></div>
            <button type="button" class="btn btn-primary" id="lxNext">Next</button>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
(() => {
    const U = {
        options: '{{ route('lcc.options') }}',
        manual: '{{ route('lcc.manual') }}',
        generate: '{{ route('lcc.generate') }}',
        list: '{{ route('lcc.list') }}',
        rename: '{{ route('lcc.rename') }}',
        job: (id) => '{{ url('/app/lcc-checker/job') }}/' + id,
        one: (id) => '{{ url('/app/lcc-checker/one') }}/' + id,
        del: (id) => '{{ url('/app/lcc-checker') }}/' + id,
    };
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const toast = (m, t) => (window.toast ? window.toast(m, t) : alert(m));
    const api = (url, o) => window.api(url, o || { method: 'GET' });
    const reduce = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const num = (x) => Number(x || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });
    const cap = (s) => String(s || '').charAt(0).toUpperCase() + String(s || '').slice(1);
    const trimDot = (s) => String(s || '').trim().replace(/[.\s]+$/, '');
    const bump = (el, cls) => { if (!el || reduce()) return; el.classList.remove(cls); void el.offsetWidth; el.classList.add(cls); };
    // A Tagalog help flag, written straight after a word ('' on the international face).
    const tl = (k) => (window.aneeTl ? window.aneeTl.flag(k) : '');

    let BOOT = { today: new Date().toISOString().slice(0, 10), varieties: [] };
    try { BOOT = JSON.parse($('lcBoot').textContent) || BOOT; } catch (e) { /* the page still works without the catalogue */ }
    const TODAY = BOOT.today;
    const VARS = BOOT.varieties || [];
    const MAT = { early: ['Early', 100, 110, 105], medium: ['Medium', 111, 120, 115], late: ['Late', 121, 130, 125] };
    const PANEL_COLOR = { 2: ['#c8d96f', '#3f4a12'], 2.5: ['#abc85a', '#2e3a0d'], 3: ['#8db544', '#1f2d07'], 3.5: ['#6d9f37', '#fff'], 4: ['#4d8a2a', '#fff'], 4.5: ['#3b7020', '#fff'], 5: ['#2c5a17', '#fff'] };
    const PANELS = [2, 2.5, 3, 3.5, 4, 4.5, 5];
    const panelBg = (p) => (PANEL_COLOR[p] || ['#9ca3af'])[0];
    const panelFg = (p) => (PANEL_COLOR[p] || ['', '#fff'])[1];
    const I = {
        check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>',
        sack: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8.5 3.5h7L14 7h-4z"/><path d="M10 7C5.5 9 4.5 14 5.2 17.6A3 3 0 008.2 20h7.6a3 3 0 003-2.4C19.5 14 18.5 9 14 7"/><path d="M9.5 13.5h5"/></svg>',
        clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>',
        flower: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 21V8"/><path d="M12 8c-2.4-.9-3.6-2.9-3.6-5 2.4.9 3.6 2.9 3.6 5zM12 12c2.4-.9 3.6-2.9 3.6-5-2.4.9-3.6 2.9-3.6 5zM12 16c-2.4-.9-3.6-2.9-3.6-5 2.4.9 3.6 2.9 3.6 5z"/></svg>',
        leaf: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 19c0-9 5-15 14-17-1 9-6 15-14 17zM5 19l7-8"/></svg>',
        cal: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="3"/><path d="M8 3v4M16 3v4M3.5 10h17"/></svg>',
        field: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 18l9-4 9 4M3 13.5l9-4 9 4M12 9.5V3"/></svg>',
        pen: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4zM14 6l4 4"/></svg>',
        trash: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg>',
        plus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
        camera: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/></svg>',
        rain: '<path d="M7 15a4 4 0 01-.5-8 5.5 5.5 0 0110.6 1.5A3.5 3.5 0 0117 15z"/><path d="M8 18l-1 2.5M12 18l-1 2.5M16 18l-1 2.5"/>',
        sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4"/>',
    };

    /* ---------------- dates: whole days, Manila's today from the server ---------------- */
    const dn = (s) => Date.UTC(+s.slice(0, 4), +s.slice(5, 7) - 1, +s.slice(8, 10)) / 864e5;
    const ds = (n) => new Date(n * 864e5).toISOString().slice(0, 10);
    const fmt = (s, o) => new Date(dn(s) * 864e5).toLocaleDateString('en-US', { timeZone: 'UTC', ...o });
    const ageOf = (s) => dn(TODAY) - dn(s);
    const MIN_DATE = ds(dn(TODAY) - 200);
    const seasonOf = (s) => { const m = +s.slice(5, 7); return m >= 6 && m <= 11 ? 'wet' : 'dry'; };
    const classOf = (d) => (d <= 110 ? 'early' : (d <= 120 ? 'medium' : 'late'));

    /* ---------------- the state, remembered on this phone ---------------- */
    let OPT = null;
    // Area units (the Land Prep Planner's): the farmer's number and unit, hectares worked out.
    const UNITS = { ha: ['Hectares', 'ha', 1, '10,000 square meters'], sqm: ['Square meters', 'm²', 0.0001, 'Like 2,500 for a quarter hectare'], acre: ['Acres', 'ac', 0.40468564, 'About 4,047 square meters'] };
    const st = { step: '1', method: 'tpr', date: null, seasonSet: null, variety: '', varSrc: null, maturity: null, maturityDays: null, area: 1, unit: 'ha',
        shots: [], chart: Array(10).fill(null), cur: 0 };
    const KEY = 'lcc-field-v2';
    function remember() {
        try { localStorage.setItem(KEY, JSON.stringify({ method: st.method, date: st.date, seasonSet: st.seasonSet, variety: st.variety, varSrc: st.varSrc, maturity: st.maturity, maturityDays: st.maturityDays, area: st.area, unit: st.unit })); } catch (e) { /* private mode */ }
    }
    function recall() {
        try {
            const s = JSON.parse(localStorage.getItem(KEY) || 'null');
            if (!s) return;
            if (s.method === 'dsr' || s.method === 'tpr') st.method = s.method;
            if (typeof s.date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(s.date) && s.date <= TODAY && s.date >= MIN_DATE) { st.date = s.date; st.seasonSet = s.seasonSet === 'wet' || s.seasonSet === 'dry' ? s.seasonSet : null; }
            if (typeof s.variety === 'string') st.variety = s.variety.slice(0, 60);
            st.varSrc = s.varSrc === 'list' || s.varSrc === 'typed' ? s.varSrc : null;
            if (MAT[s.maturity]) st.maturity = s.maturity;
            if (Number(s.maturityDays) >= 60 && Number(s.maturityDays) <= 200) st.maturityDays = Number(s.maturityDays);
            if (Number(s.area) > 0) st.area = Number(s.area);
            if (UNITS[s.unit]) st.unit = s.unit;
        } catch (e) { /* nothing kept */ }
    }

    const season = () => st.seasonSet || (st.date ? seasonOf(st.date) : 'wet');
    const startDay = () => (st.method === 'dsr' ? 21 : 14);
    const matDays = () => st.maturityDays || (st.maturity ? MAT[st.maturity][3] : null);
    const flowerDay = () => { const m = matDays(); return m ? Math.max(startDay() + 7, m - 30 - (st.method === 'dsr' ? 0 : 20)) : null; };
    const stageOf = (days) => (days < startDay() ? 'early' : (flowerDay() && days >= flowerDay() ? 'late' : 'on'));
    const word = () => (st.method === 'dsr' ? 'sowing' : 'transplanting');
    const dayWord = () => (st.method === 'dsr' ? 'DAS' : 'DAT');
    const caseBody = () => ({ method: st.method, plantingDate: st.date, season: season(), maturity: st.maturity, maturityDays: st.maturityDays,
        areaHa: areaHa() || 1, areaInput: Number(st.area) || 1, areaUnit: st.unit, variety: st.variety, notes: '' });
    function areaHa() { return Math.round((Number(st.area) || 0) * UNITS[st.unit][2] * 10000) / 10000; }
    // "0.5 hectares", "5,000 square meters (0.5 ha)": the area as the farmer gave it.
    function areaWords(input, unit, ha) {
        const u = UNITS[unit] || UNITS.ha, n = Number(input) || Number(ha) || 1;
        const name = unit === 'sqm' ? 'square meters' : (unit === 'acre' ? (n === 1 ? 'acre' : 'acres') : (n <= 1 ? 'hectare' : 'hectares'));
        return num(n) + ' ' + name + (unit && unit !== 'ha' && ha ? ' (' + num(ha) + ' ha)' : '');
    }

    /* ---------------- the fertilizer on hand (2026-10-15, the owner) ----------------
       The reading sets the nitrogen and the bag sets the kilos. The first number on a
       bag is its percent of nitrogen, so the kilos are the nitrogen over that percent. */
    const FERTS = {
        urea: { name: 'Urea', short: 'urea', cell: 'urea', g: [46, 0, 0], tl: 'urea', blurb: 'The most nitrogen in a bag',
            line: 'Urea has the most nitrogen in a bag, so it takes the fewest bags to carry.' },
        ammosul: { name: 'Ammosul', short: 'ammosul', cell: 'ammosul', g: [21, 0, 0], s: 24, tl: 'ammosul', blurb: 'Ammonium sulfate. Adds sulfur',
            line: 'Ammosul also feeds sulfur. It suits alkaline or sulfur poor soil. It sours the soil a little, so skip it on very sour soil.' },
        amcl: { name: 'Ammonium chloride', short: 'ammonium chloride', cell: '25-0-0', g: [25, 0, 0], tl: 'ammonium-chloride', blurb: 'Works well on flooded rice',
            line: 'Ammonium chloride works well on flooded rice. Like ammosul, it sours the soil a little.' },
        ammophos: { name: 'Ammophos', short: 'ammophos', cell: 'ammophos', g: [16, 20, 0], tl: 'ammophos', blurb: 'Ammonium phosphate. Adds phosphorus',
            line: 'Ammophos also feeds phosphorus. It does the most good in the first weeks after planting, while the roots are young.' },
        complete: { name: 'Complete', short: 'complete fertilizer', cell: '14-14-14', g: [14, 14, 14], tl: 'complete-fertilizer', blurb: 'Adds phosphorus and potassium',
            line: 'Complete also feeds phosphorus and potassium. It does the most good early in the season. Later on, a straight nitrogen bag means fewer kilos.' },
    };
    const FKEY = 'lcc-fert-v1';
    const TICK = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>';
    const CHEV = '<svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';
    // A bag the farmer types: nitrogen 1 to 46 (urea is the richest granular bag), P and K up to 60.
    function okGrade(g) { return Array.isArray(g) && g.length === 3 && g.every((x) => Number.isInteger(x)) && g[0] >= 1 && g[0] <= 46 && g[1] >= 0 && g[1] <= 60 && g[2] >= 0 && g[2] <= 60; }
    let FERT = { k: 'urea' };
    try { const s = JSON.parse(localStorage.getItem(FKEY) || 'null'); if (s && (FERTS[s.k] || (s.k === 'own' && okGrade(s.g)))) FERT = s.k === 'own' ? { k: 'own', g: s.g } : { k: s.k }; } catch (e) { /* urea it is */ }
    const gradeOf = (g) => g.join('-');
    function fert(f) {
        f = f || FERT;
        if (f.k !== 'own') return { k: f.k, ...FERTS[f.k] };
        const gr = gradeOf(f.g);
        return { k: 'own', name: 'Your ' + gr, short: gr, cell: gr, g: f.g, tl: 'fertilizer-grade', line: 'The first number on your bag is its nitrogen. The lower it is, the more kilos it takes.' };
    }
    // Rounded per hectare first, the way the server rounds urea, so a urea answer matches the saved check.
    function fertQty(kgN, area, f) {
        const ha = Math.round(kgN / (f.g[0] / 100));
        return { ha, kg: Math.round(ha * area), bha: Math.round(ha / 50 * 10) / 10, bags: Math.round(ha * area / 50 * 10) / 10 };
    }
    const bagWord = (x) => (Number(x) === 1 ? 'bag' : 'bags');
    function fertPlus(f, q) {
        const bits = [];
        if (f.g[1]) bits.push(`${num(Math.round(q.ha * f.g[1] / 100))} kg of phosphorus`);
        if (f.g[2]) bits.push(`${num(Math.round(q.ha * f.g[2] / 100))} kg of potassium`);
        if (f.s) bits.push(`${num(Math.round(q.ha * f.s / 100))} kg of sulfur`);
        if (!bits.length) return '';
        return `It also gives ${bits.length > 1 ? bits.slice(0, -1).join(', ') + ' and ' + bits[bits.length - 1] : bits[0]} per hectare.`;
    }
    // The answer card's inside: the tag, the kilos for the field, per hectare, and how.
    function fertBody(d, p, area) {
        const f = fert(), kgN = Number(d.kgN) || 23, q = fertQty(kgN, area, f), plus = fertPlus(f, q);
        return `<button type="button" class="lv-ftag" data-vact="fert" aria-haspopup="dialog" aria-label="Change the fertilizer. Now ${esc(f.name)}, ${gradeOf(f.g)}"><span class="lv-ftag-g">${gradeOf(f.g)}</span><span class="lv-ftag-t"><small>Your fertilizer</small><b>${esc(f.name)}</b></span><span class="lv-ftag-c">Change${CHEV}</span></button>
            <div class="lv-big"><b><i data-n="kg" data-v="${q.kg}">${num(q.kg)}</i> kg</b><span>of ${esc(f.short)} for your ${esc(areaWords(p.areaInput, p.areaUnit, area))}<em data-tl="fertilizer-bags">about <i data-n="bags" data-d="1" data-v="${q.bags}">${num(q.bags)}</i> ${bagWord(q.bags)} of 50 kg</em></span></div>
            <div class="lv-grid3"><span data-tl="nitrogen"><b>${num(kgN)} kg</b>nitrogen per ha</span><span data-tl="kg-per-hectare"><b><i data-n="ha" data-v="${q.ha}">${num(q.ha)}</i> kg</b>${esc(f.cell)} per ha</span><span><b><i data-n="bha" data-d="1" data-v="${q.bha}">${num(q.bha)}</i></b>${bagWord(q.bha)} per ha</span></div>
            ${plus ? `<p class="lv-fplus">${I.plus}<span>${esc(plus)}</span></p>` : ''}
            <ul class="lv-how"><li>${I.check}<span>Spread it evenly on 2 to 3 cm of water, late in the afternoon.</span></li><li>${I.check}<span>That is the ${p.season === 'dry' ? 'dry' : 'wet'} season rate, ${num(kgN)} kg of nitrogen per hectare. ${esc(cap(f.short))} is ${f.g[0]} percent nitrogen, so it takes ${num(q.ha)} kg of it.</span></li><li>${I.check}<span>${esc(f.line)}</span></li><li>${I.check}<span>Keep reading every week. The leaves should green up.</span></li></ul>`;
    }
    // The saved checks list: each due check's kilos per hectare in the bag on hand.
    function fertMeta(uha) {
        const f = fert(), kgN = Math.round(Number(uha) * 0.46);
        return `<b>${num(fertQty(kgN, 1, f).ha)} kg</b> ${esc(f.short)} per ha`;
    }
    const seasonWhy = () => { const f = fert(); return `Wet is June to November, dry is December to May. It sets the ${f.short}: ${num(fertQty(23, 1, f).ha)} kg per ha wet, ${num(fertQty(30, 1, f).ha)} kg dry.`; };
    // Numbers count from the old bag's to the new one's, in the house easing.
    function countIn(box, was) {
        if (reduce()) return;
        box.querySelectorAll('[data-n]').forEach((el) => {
            const to = Number(el.dataset.v), from = was[el.dataset.n], dec = Number(el.dataset.d || 0);
            if (from == null || from === to) return;
            const t0 = performance.now(), dur = 560;
            const step = (t) => {
                const k = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - k, 3);
                el.textContent = num(Number((from + (to - from) * e).toFixed(dec)));
                if (k < 1 && el.isConnected) requestAnimationFrame(step);
            };
            el.textContent = num(from);
            requestAnimationFrame(step);
        });
    }
    function paintFert() {
        const box = $('lvFert');
        if (box && VIEW) {
            const was = {};
            box.querySelectorAll('[data-n]').forEach((el) => { was[el.dataset.n] = Number(el.dataset.v); });
            const d = (VIEW.item.report || {}).decision || {}, p = VIEW.item.params || {};
            box.innerHTML = fertBody(d, p, Number(d.areaHa) || 1);
            countIn(box, was);
            bump(box.querySelector('.lv-ftag'), 'is-bump');
        }
        document.querySelectorAll('[data-uha]').forEach((el) => { el.innerHTML = fertMeta(el.dataset.uha); });
        document.querySelectorAll('[data-nofert]').forEach((el) => { el.textContent = `No ${fert().short} this week`; });
        if ($('lcSeasonWhy').textContent) $('lcSeasonWhy').textContent = seasonWhy();
    }
    function setFert(f) {
        FERT = f;
        try { localStorage.setItem(FKEY, JSON.stringify(f)); } catch (e) { /* private mode */ }
        paintFert();
    }
    // The sheet: each bag with the kilos it takes for this field, and a row for any other bag.
    let FS = { kgN: 23, area: 1 };
    function fertRow(k, f, on) {
        const q = fertQty(FS.kgN, FS.area, f);
        return `<button type="button" class="lc-urow lc-frow" role="radio" data-f="${k}" aria-checked="${on}"><span class="u-ab">${gradeOf(f.g)}</span><span class="u-t"><b data-tl="${f.tl}">${esc(f.name)}</b><small>${esc(f.blurb)}</small></span><span class="u-q"><b>${num(q.kg)} kg</b><small>${num(q.bags)} ${bagWord(q.bags)}</small></span><span class="u-ck" aria-hidden="true">${TICK}</span></button>`;
    }
    function paintFertList() {
        const own = FERT.k === 'own' ? FERT.g : null;
        const ownQ = own ? fertQty(FS.kgN, FS.area, fert()) : null;
        $('lcFertList').innerHTML = Object.entries(FERTS).map(([k, f]) => fertRow(k, f, FERT.k === k)).join('')
            + `<button type="button" class="lc-urow lc-frow" role="radio" data-f="own" aria-checked="${!!own}" aria-expanded="${!!own}" aria-controls="lcFertOwn"><span class="u-ab is-own">${own ? gradeOf(own) : 'N-P-K'}</span><span class="u-t"><b data-tl="fertilizer-grade">Another bag</b><small>Type the three numbers on it</small></span><span class="u-q">${ownQ ? `<b>${num(ownQ.kg)} kg</b><small>${num(ownQ.bags)} ${bagWord(ownQ.bags)}</small>` : ''}</span><span class="u-ck" aria-hidden="true">${TICK}</span></button>
            <div class="lc-fown${own ? ' is-on' : ''}" id="lcFertOwn"${own ? '' : ' inert'}><div><div class="lc-fown-box">
                <div class="lc-fown-in">
                    <label><small>Nitrogen</small><input type="number" inputmode="numeric" min="1" max="46" step="1" id="lcOwnN" value="${own ? own[0] : ''}" placeholder="21" aria-label="Nitrogen, the first number"></label><span aria-hidden="true">-</span>
                    <label><small>Phosphorus</small><input type="number" inputmode="numeric" min="0" max="60" step="1" id="lcOwnP" value="${own ? own[1] : ''}" placeholder="0" aria-label="Phosphorus, the second number"></label><span aria-hidden="true">-</span>
                    <label><small>Potassium</small><input type="number" inputmode="numeric" min="0" max="60" step="1" id="lcOwnK" value="${own ? own[2] : ''}" placeholder="0" aria-label="Potassium, the third number"></label>
                </div>
                <p class="lc-fown-out" id="lcOwnOut" aria-live="polite"></p>
                <button type="button" class="btn btn-primary" id="lcOwnUse">Use this bag</button>
            </div></div></div>`;
        ownLine();
    }
    function ownGrade() {
        const v = ['lcOwnN', 'lcOwnP', 'lcOwnK'].map((id) => $(id).value.trim());
        return [v[0] === '' ? NaN : Number(v[0]), v[1] === '' ? 0 : Number(v[1]), v[2] === '' ? 0 : Number(v[2])];
    }
    function ownLine() {
        const out = $('lcOwnOut');
        if (!out) return false;
        const g = ownGrade(), ok = okGrade(g);
        out.classList.toggle('is-bad', !ok && $('lcOwnN').value.trim() !== '');
        if (ok) {
            const q = fertQty(FS.kgN, FS.area, { g });
            out.textContent = `${gradeOf(g)} takes ${num(q.kg)} kg for your field, about ${num(q.bags)} ${bagWord(q.bags)} of 50 kg.`;
        } else {
            out.textContent = $('lcOwnN').value.trim() === '' ? 'The first number is the nitrogen. A bag that says 21-0-0 has 21 kg of nitrogen in every 100 kg.' : 'Use whole numbers. The first one goes from 1 to 46, the others from 0 to 60.';
        }
        return ok;
    }
    function openFert() {
        if (!VIEW) return;
        const d = (VIEW.item.report || {}).decision || {}, p = VIEW.item.params || {};
        FS = { kgN: Number(d.kgN) || 23, area: Number(d.areaHa) || 1 };
        $('lcFertSub').innerHTML = `Each one gives your ${esc(areaWords(p.areaInput, p.areaUnit, FS.area))} the same <b>${num(Math.round(FS.kgN * FS.area * 10) / 10)} kg of nitrogen</b>. A bag with less nitrogen takes more kilos.`;
        paintFertList();
        sheetOpen($('lcFertSheet'));
        $('lcFertList').querySelector('[aria-checked="true"]')?.scrollIntoView({ block: 'nearest' });
    }
    function fertDone() { setTimeout(() => sheetClose($('lcFertSheet')), reduce() ? 0 : 180); }

    /* ---------------- 1. the field ---------------- */
    function paintField() {
        $('lcMethod').querySelectorAll('button').forEach((b) => { const on = b.dataset.v === st.method; b.classList.toggle('is-on', on); b.setAttribute('aria-checked', String(on)); });
        $('lcDateLab').textContent = st.method === 'dsr' ? 'Sown on' : 'Transplanted on';
        $('lcDateBtn').classList.toggle('is-empty', !st.date);
        $('lcDateBtn').querySelector('.lc-chev').firstChild.textContent = st.date ? 'Change' : 'Pick';
        if (st.date) {
            const days = ageOf(st.date), stage = stageOf(days), fl = flowerDay();
            $('lcDateNow').textContent = fmt(st.date, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
            $('lcDateSub').textContent = days === 0 ? 'Today' : `${days} ${days === 1 ? 'day' : 'days'} ago`;
            const was = $('lcAgeN').textContent;
            $('lcAgeN').textContent = days;
            if (was !== String(days)) bump($('lcAgeBox'), 'is-bump');
            $('lcAgeW').textContent = dayWord();
            $('lcAgeW').setAttribute('data-tl', st.method === 'dsr' ? 'das' : 'dat');
            $('lcAgeLine').textContent = `${days} ${days === 1 ? 'day' : 'days'} after ${word()}`;
            const s = $('lcAgeState');
            s.className = 'is-' + stage;
            s.textContent = stage === 'early' ? `Too early. Start reading on ${fmt(ds(dn(st.date) + startDay()), { month: 'short', day: 'numeric' })}, day ${startDay()}.`
                : stage === 'late' ? `Past the first flowers, about day ${fl}. No more nitrogen.`
                : (fl ? `Ready to read, until about day ${fl}` : 'Ready to read');
            $('lcAge').hidden = false;
            const now = season(), other = now === 'wet' ? 'dry' : 'wet';
            $('lcSeason').hidden = false;
            $('lcSeason').classList.toggle('is-dry', now === 'dry');
            $('lcSeasonIco').innerHTML = now === 'dry' ? I.sun : I.rain;
            $('lcSeasonLine').innerHTML = st.seasonSet ? `<b>${cap(now)} season</b>, as you set it.` : `<b>${cap(now)} season</b> crop, from the ${fmt(st.date, { month: 'long' })} planting.`;
            $('lcSeasonFlip').textContent = `Switch to ${other}`;
            $('lcSeasonWhy').textContent = seasonWhy();
        } else {
            $('lcDateNow').textContent = 'Pick the date';
            $('lcDateSub').textContent = st.method === 'dsr' ? 'The day the seeds were sown' : 'The day the seedlings went in';
            $('lcAge').hidden = true;
            $('lcSeason').hidden = true;
        }
        paintVarTag();
        $('lcArea').value = st.area;
        paintUnit();
    }
    function varRow() { return st.varSrc === 'list' ? VARS.find((v) => v.n === st.variety) : null; }
    function paintVarTag() {
        const v = varRow();
        let b = 'Not sure', s = 'Pick your variety or how long it takes';
        if (v) { b = v.n; s = [v.l, `${v.d} days to harvest, ${MAT[classOf(v.d)][0].toLowerCase()}`].filter(Boolean).join(' · '); }
        else if (st.variety && st.maturity) { b = st.variety; s = `${MAT[st.maturity][0]} maturity, ${MAT[st.maturity][1]} to ${MAT[st.maturity][2]} days`; }
        else if (st.variety) { b = st.variety; s = 'Maturity not set. Tap to pick it.'; }
        else if (st.maturity) { b = `${MAT[st.maturity][0]} maturity`; s = `${MAT[st.maturity][1]} to ${MAT[st.maturity][2]} days to harvest`; }
        $('lcVarNow').textContent = b;
        $('lcVarSub').textContent = s;
        $('lcVarBtn').classList.toggle('is-empty', !st.variety && !st.maturity);
    }
    $('lcMethod').addEventListener('click', (e) => {
        const b = e.target.closest('button[data-v]');
        if (!b || b.dataset.v === st.method) return;
        st.method = b.dataset.v;
        paintField(); paintPalette(); paintChartState(); remember();
    });
    $('lcSeasonFlip').addEventListener('click', () => {
        const other = season() === 'wet' ? 'dry' : 'wet';
        st.seasonSet = st.date && other === seasonOf(st.date) ? null : other;
        paintField(); remember();
    });
    function paintUnit() {
        const u = UNITS[st.unit];
        $('lcUnitNow').textContent = u[0];
        const ha = areaHa();
        $('lcAreaHint').textContent = st.unit === 'ha' ? '1 hectare is 10,000 square meters.'
            : (Number(st.area) > 0 ? 'That is ' + num(ha) + ' ' + (ha === 1 ? 'hectare' : 'hectares') + '.' : u[3] + '.');
    }
    $('lcArea').addEventListener('input', () => {
        $('lcArea').closest('.lc-area-in').classList.remove('is-bad');
        const a = Number($('lcArea').value);
        if (a > 0) { st.area = a; paintUnit(); remember(); }
    });
    function paintUnitList() {
        const tick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>';
        $('lcUnitList').innerHTML = Object.entries(UNITS).map(([k, u]) => '<button type="button" class="lc-urow" role="radio" data-u="' + k + '" aria-checked="' + (k === st.unit) + '">'
            + '<span class="u-ab">' + u[1] + '</span><span class="u-t"><b' + (k === 'ha' ? ' data-tl="hectare"' : '') + '>' + u[0] + '</b><small>' + u[3] + '</small></span><span class="u-ck" aria-hidden="true">' + tick + '</span></button>').join('');
    }
    $('lcUnitBtn').addEventListener('click', () => { paintUnitList(); sheetOpen($('lcUnitSheet')); });
    $('lcUnitList').addEventListener('click', (e) => {
        const b = e.target.closest('[data-u]');
        if (!b) return;
        st.unit = b.dataset.u;
        $('lcUnitList').querySelectorAll('[data-u]').forEach((x) => x.setAttribute('aria-checked', String(x === b)));
        paintUnit(); remember();
        setTimeout(() => { sheetClose($('lcUnitSheet')); $('lcArea').focus({ preventScroll: true }); }, reduce() ? 0 : 180);
    });
    $('lcNext').addEventListener('click', () => {
        if (!st.date) { bump($('lcDateBtn'), 'is-shake'); openDate(); return; }
        const a = Number($('lcArea').value);
        st.area = a;
        const ha = areaHa();
        if (!(a > 0) || !(ha > 0) || ha > 10000) {
            const box = $('lcArea').closest('.lc-area-in');
            box.classList.remove('is-bad'); void box.offsetWidth; box.classList.add('is-bad');
            toast('Put the size of your field, like ' + (st.unit === 'sqm' ? '5000' : '1') + ' ' + UNITS[st.unit][0].toLowerCase() + '.', 'error');
            $('lcArea').focus(); return;
        }
        remember();
        go('2');
    });

    /* ---------------- the steps ---------------- */
    const ORDER = { 1: 1, 2: 2, '3a': 3, '3b': 3 };
    function go(step) {
        if (step !== '1' && !st.date) { go('1'); openDate(); return; }
        if (step !== st.step) window.aneeStepOut?.(document.querySelector('.lc-step.is-on'));
        $('lcStage').dataset.dir = ORDER[step] >= ORDER[st.step] ? 'fwd' : 'back';
        st.step = step;
        document.querySelectorAll('.lc-step').forEach((s) => s.classList.toggle('is-on', s.dataset.step === step));
        const n = ORDER[step];
        const nodes = $('lcSteps').children;
        [...nodes].forEach((el) => {
            if (el.dataset.s) { el.classList.toggle('is-on', Number(el.dataset.s) === n); el.classList.toggle('is-done', Number(el.dataset.s) < n); }
        });
        [...$('lcSteps').querySelectorAll(':scope > i')].forEach((line, i) => line.classList.toggle('is-done', i + 1 < n));
        if (step === '2') paintChoice();
        if (step === '3a') paintShots();
        if (step === '3b') paintChartState();
        const top = $('lcSteps').getBoundingClientRect().top + window.scrollY - 76;
        if (window.scrollY > top) window.scrollTo({ top, behavior: 'auto' });
    }
    document.addEventListener('click', (e) => { const b = e.target.closest('.lc-wrap [data-go]'); if (b) go(b.dataset.go); });

    /* ---------------- 2. the choice ---------------- */
    function paintChoice() {
        const days = ageOf(st.date), stage = stageOf(days), v = varRow();
        const chips = [st.method === 'dsr' ? 'Direct seeded' : 'Transplanted', `${days} ${dayWord()}`, `${cap(season())} season`,
            v ? v.n : (st.variety || (st.maturity ? `${MAT[st.maturity][0]} maturity` : 'Variety not set')), areaWords(st.area, st.unit, areaHa())];
        $('lcSum').innerHTML = chips.map((c, i) => `<span${i === 1 ? ` data-tl="${st.method === 'dsr' ? 'das' : 'dat'}"` : ''}>${esc(c)}</span>`).join('');
        let warn = '';
        if (stage === 'early') warn = `Your rice is at day ${days} after ${word()}. Readings start on day ${startDay()}, ${fmt(ds(dn(st.date) + startDay()), { weekday: 'short', month: 'short', day: 'numeric' })}. Young leaves are pale anyway, so a check today will say wait.`;
        if (stage === 'late') warn = `Your rice is at day ${days}, past its first flowers (about day ${flowerDay()} for this maturity). Nitrogen now does not reach the grain, and a check will say so.`;
        $('lcWarn').hidden = !warn;
        $('lcWarnText').textContent = warn;
        paintPrice();
    }
    function paintPrice() {
        const p = $('lcWayPrice');
        if (!OPT) { p.textContent = 'Uses credits'; p.className = 'lc-way-p'; return; }
        p.textContent = OPT.canUse ? `${num(OPT.price)} credits` : 'Not on your plan yet';
        p.className = 'lc-way-p' + (OPT.canUse ? '' : ' is-lock');
        if (OPT.aneeFace) $('lcAneeFace').src = OPT.aneeFace;
    }
    // On a plan without Anee the card offers the upgrade instead of a photo step that cannot read.
    $('lcWayAnee').addEventListener('click', () => {
        if (OPT && OPT.tierLocked && window.aneeUpgrade) { window.aneeUpgrade(OPT.whyNot, 'libreAnee'); return; }
        go('3a');
    });
    $('lcWayChart').addEventListener('click', () => go('3b'));

    /* ---------------- 3a. photos: each one squared, measured (DGCI of its green pixels) and kept small ---------------- */
    function dgciOf(ctx, w, h) {
        const d = ctx.getImageData(0, 0, w, h).data;
        let sum = 0, n = 0;
        for (let i = 0; i < d.length; i += 16) {
            const r = d[i] / 255, g = d[i + 1] / 255, b = d[i + 2] / 255;
            const max = Math.max(r, g, b), min = Math.min(r, g, b), c = max - min;
            if (max < 0.12 || c < 0.08 || g < r || g < b) continue;   // not leaf: paper, shadow, soil
            const hue = 60 * (((b - r) / c) + 2);
            if (hue < 60 || hue > 180) continue;
            const s = max ? c / max : 0;
            sum += ((hue - 60) / 60 + (1 - s) + (1 - max)) / 3;
            n++;
        }
        return n > 200 ? Math.round((sum / n) * 1000) / 1000 : null;
    }
    function addFiles(files) {
        [...files].filter((f) => /^image\//.test(f.type) || !f.type).slice(0, 12 - st.shots.length).forEach((f) => {
            const img = new Image();
            img.onload = () => {
                const s = Math.min(img.width, img.height), size = 320;
                const c = document.createElement('canvas'); c.width = size; c.height = size;
                const ctx = c.getContext('2d');
                ctx.drawImage(img, (img.width - s) / 2, (img.height - s) / 2, s, s, 0, 0, size, size);
                if (st.shots.length < 12) st.shots.push({ url: c.toDataURL('image/jpeg', 0.85), canvas: c, dgci: dgciOf(ctx, size, size) });
                URL.revokeObjectURL(img.src);
                paintShots();
            };
            img.onerror = () => { URL.revokeObjectURL(img.src); toast('That photo could not be opened. Try another.', 'error'); };
            img.src = URL.createObjectURL(f);
        });
    }
    function paintShots() {
        $('lcShots').innerHTML = st.shots.map((s, i) => `<div class="lc-shot"><img src="${s.url}" alt="Leaf ${i + 1}"><b>${i + 1}</b>${s.dgci != null ? `<i>${s.dgci.toFixed(2)}</i>` : ''}<button type="button" data-drop="${i}" aria-label="Remove leaf ${i + 1}">✕</button></div>`).join('')
            + (st.shots.length < 12 ? `<label class="lc-add" for="lcFiles"><div>${I.camera}${st.shots.length ? 'Add more' : 'Add leaf photos'}</div></label>` : '');
        const can = !!(OPT && OPT.canUse);
        const n = st.shots.length;
        $('lcRead').disabled = !(n >= 3 && can);
        $('lcRead').textContent = !OPT ? 'Loading…' : (!can ? 'Not on your plan yet' : (n < 3 ? `Add at least 3 leaves${n ? `, ${3 - n} more` : ''} (10 is best)` : `Read my ${n} leaves · ${num(OPT.price)} credits`));
        $('lcBal').textContent = OPT && can ? (OPT.unlimited ? 'Your plan has unlimited credits.' : `You have ${num(Math.floor(OPT.balance))} credits. Nothing is charged if the reading fails.`) : '';
        $('lcWhy').hidden = !(OPT && !can);
        if (OPT && !can) $('lcWhyText').textContent = OPT.whyNot || 'The photo reading is not on your plan yet.';
    }
    $('lcFiles').addEventListener('change', (e) => { addFiles(e.target.files); e.target.value = ''; });
    $('lcShots').addEventListener('click', (e) => { const b = e.target.closest('[data-drop]'); if (!b) return; st.shots.splice(Number(b.dataset.drop), 1); paintShots(); });
    function collage() {
        const n = st.shots.length, cols = n > 6 ? 4 : 3, rows = Math.ceil(n / cols), t = 300;
        const c = document.createElement('canvas'); c.width = cols * t; c.height = rows * t;
        const ctx = c.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
        st.shots.forEach((s, i) => {
            const x = (i % cols) * t, y = Math.floor(i / cols) * t;
            ctx.drawImage(s.canvas, x + 4, y + 4, t - 8, t - 8);
            ctx.fillStyle = 'rgba(0,0,0,.65)'; ctx.fillRect(x + 8, y + 8, 46, 34);
            ctx.fillStyle = '#fff'; ctx.font = 'bold 24px sans-serif'; ctx.fillText(String(i + 1), x + 18, y + 34);
        });
        return c.toDataURL('image/jpeg', 0.82);
    }
    $('lcRead').addEventListener('click', async () => {
        if (!OPT || !OPT.canUse || st.shots.length < 3 || !st.date) return;
        window.aneeWait.show({ title: 'Anee is reading your leaves…', lines: ['Matching each leaf to the chart…', 'Looking for spots, bites and other shortages…', 'Working out the urea for your area…'], sub: 'This takes about half a minute.' });
        try {
            const res = await window.api(U.generate, { method: 'POST', body: { ...caseBody(), image: collage(), leaves: st.shots.length, dgci: st.shots.map((s) => s.dgci) } });
            let data = res.data || {};
            if (data.pending) data = await window.aneeWait.poll({ id: data.id, job: U.job, phases: window.aneeWait.phases.plain, every: 2500 });
            await window.aneeWait.done({ title: 'Done!', line: `${num(data.charged)} credits used.` });
            if (typeof data.balance === 'number' && OPT) OPT.balance = data.balance;
            took(data, true);
        } catch (err) {
            window.aneeWait.fail && window.aneeWait.fail();
            toast(err.message, 'error');
        }
    });

    /* ---------------- 3b. the chart: one leaf at a time, a tap a leaf ---------------- */
    function buildChart() {
        $('lcStrip').innerHTML = st.chart.map((_, i) => `<button type="button" class="lc-sl" data-leaf="${i}"><svg viewBox="0 0 24 40" aria-hidden="true"><path class="lc-sl-blade" d="M12 2C3.5 12 3.5 28 12 38C20.5 28 20.5 12 12 2Z"/><path class="lc-sl-rib" d="M12 7V34"/></svg><small>${i + 1}</small></button>`).join('');
        $('lcPal').innerHTML = PANELS.map((p) => `<button type="button" class="lc-sw${p % 1 ? ' is-half' : ''}" data-p="${p}" style="background:${panelBg(p)};color:${panelFg(p)}" aria-label="Panel ${p}">${p}</button>`).join('');
    }
    function paintPalette() {
        const crit = st.method === 'dsr' ? 3 : 4;
        $('lcPal').querySelectorAll('.lc-sw').forEach((b) => b.classList.toggle('is-crit', Number(b.dataset.p) === crit));
        $('lcCrit').innerHTML = `The <b>dashed line</b> is panel ${crit}${tl('critical-panel')}, the line for ${st.method === 'dsr' ? 'direct seeded' : 'transplanted'} rice. Leaves left of it are pale.`;
    }
    function paintChartState() {
        const crit = st.method === 'dsr' ? 3 : 4;
        $('lcStrip').querySelectorAll('.lc-sl').forEach((b, i) => {
            const p = st.chart[i];
            b.classList.toggle('is-cur', i === st.cur);
            b.classList.toggle('is-set', p !== null);
            b.classList.toggle('is-low', p !== null && p < crit);
            b.style.setProperty('--c', p !== null ? panelBg(p) : 'transparent');
            b.querySelector('small').textContent = p !== null ? p : i + 1;
            b.setAttribute('aria-label', `Leaf ${i + 1}${p !== null ? `, panel ${p}` : ', not read yet'}`);
            b.setAttribute('aria-pressed', String(i === st.cur));
        });
        const cur = st.chart[st.cur];
        $('lcPal').querySelectorAll('.lc-sw').forEach((b) => b.classList.toggle('is-pick', cur !== null && Number(b.dataset.p) === cur));
        const filled = st.chart.filter((p) => p !== null).length;
        $('lcLeafOf').textContent = `Leaf ${st.cur + 1} of 10`;
        $('lcLeafHint').textContent = cur !== null ? `Panel ${cur}. Tap another to change it.` : 'Tap its panel below';
        $('lcCount').textContent = `${filled} of 10 read`;
        $('lcCheck').disabled = filled < 3;
        $('lcCheck').textContent = filled < 3 ? `Read at least 3 leaves${filled ? `, ${3 - filled} more` : ''}` : `Check my ${filled} readings`;
    }
    $('lcStrip').addEventListener('click', (e) => { const b = e.target.closest('[data-leaf]'); if (!b) return; st.cur = Number(b.dataset.leaf); paintChartState(); });
    $('lcPal').addEventListener('click', (e) => {
        const b = e.target.closest('[data-p]'); if (!b) return;
        const i = st.cur;
        st.chart[i] = Number(b.dataset.p);
        bump($('lcStrip').children[i], 'is-bump');
        // On to the next leaf still to read; stay put once all ten are in.
        for (let k = 1; k <= 10; k++) { const j = (i + k) % 10; if (st.chart[j] === null) { st.cur = j; break; } }
        paintChartState();
    });
    $('lcClear').addEventListener('click', () => { st.chart = Array(10).fill(null); st.cur = 0; paintChartState(); });
    $('lcCheck').addEventListener('click', async () => {
        const btn = $('lcCheck');
        const panels = st.chart.filter((p) => p !== null);
        if (panels.length < 3) { toast('Tap a panel for at least 3 leaves (10 is best).', 'error'); return; }
        btn.disabled = true;
        const was = btn.textContent;
        btn.textContent = 'Counting…';
        try {
            const res = await api(U.manual, { method: 'POST', body: { ...caseBody(), panels } });
            took(res.data, true);
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; btn.textContent = was; paintChartState(); }
    });

    /* ---------------- the answer, full screen ---------------- */
    const CACHE = new Map();
    let VIEW = null;
    const verdictOf = (d) => (d.stage === 'early' ? 'early' : (d.stage === 'late' ? 'late' : (d.due ? 'due' : 'ok')));
    const VICON = { due: I.sack, ok: I.check, early: I.clock, late: I.flower };
    function calBox(iso) { return `<span class="lv-cal"><i>${fmt(iso, { month: 'short' })}</i><b>${fmt(iso, { day: 'numeric' })}</b></span>`; }
    function viewHtml(item, fresh) {
        const r = item.report || {}, d = r.decision || {}, p = item.params || {}, a = r.anee || null;
        const v = verdictOf(d);
        const dsr = p.method === 'dsr';
        const wd = dsr ? 'sowing' : 'transplanting', dw = dsr ? 'DAS' : 'DAT';
        const days = p.days ?? d.days ?? null;
        const crit = d.critical || (dsr ? 3 : 4);
        const panels = (r.panels || []).map(Number);
        const n = d.leaves ?? panels.length, below = d.below ?? panels.filter((x) => x < crit).length;
        const seasonName = p.season === 'dry' ? 'dry' : 'wet';
        const head = {
            due: ['Time for nitrogen', `${below} of ${n} leaves read below panel ${crit}. That is more than half, so your rice wants nitrogen now.`],
            ok: ['No nitrogen yet', `${below} of ${n} leaves read below panel ${crit}. That is not more than half, so hold the ${fert().short} for now.`],
            early: ['Too early to read', `Young rice leaves are pale anyway. Readings start ${d.startDay || (dsr ? 21 : 14)} days after ${wd}${days != null ? `, and your rice is at day ${days}` : ''}.`],
            late: ['Past the nitrogen window', 'Your rice is at or past its first flowers. Nitrogen now feeds leaves, not grain, and invites pests and lodging. Keep the water steady instead.'],
        }[v];
        let k = 0;
        const K = () => `style="--k:${k++}"`;
        const out = [];
        out.push(`<div class="lv-saved lv-in" ${K()}>${I.check}${fresh ? 'Saved to Your checks' : esc(`Checked ${r.at || ''}`)}</div>`);
        const sorted = panels.slice().sort((x, y) => x - y);
        const meter = (v === 'due' || v === 'ok') && n ? `<div class="lv-meter"><div class="lv-bar">${sorted.map((x, i) => `<i class="${x < crit ? 'is-low' : ''}" style="--i:${i}"></i>`).join('')}<span class="lv-half"><em>half</em></span></div>
            <div class="lv-meter-t"><span data-tl="critical-panel"><b>${below} of ${n}</b> below panel ${crit}</span>${d.average != null ? `<span>Average panel ${d.average}</span>` : ''}</div></div>` : '';
        // The eyebrow's parts escaped one by one, so the day count can carry its flag.
        const eyebrow = [esc(String(r.at || '').replace(/,? \d{4}.*$/, '')), days != null ? esc(`${days} ${dw}`) + tl(dsr ? 'das' : 'dat') : '', r.mode === 'anee' ? 'Read by Anee' : 'Your chart'].filter(Boolean).join(' · ');
        out.push(`<section class="lv-hero is-${v} lv-in" ${K()}><div class="lv-top"><span class="lv-ico">${VICON[v]}</span><span class="lv-eyebrow">${eyebrow}</span></div><h2 data-tl="${v === 'due' || v === 'ok' ? 'nitrogen' : 'nitrogen-window'}">${head[0]}</h2><p>${head[1]}</p>${meter}</section>`);
        if (v === 'due') {
            // Not only urea (2026-10-15, the owner): the tag picks the bag on hand and the kilos follow it.
            out.push(`<section class="lv-card lv-in" ${K()}><h3>${I.sack}<span data-tl="granular-fertilizer">How much fertilizer</span></h3><div id="lvFert">${fertBody(d, p, Number(d.areaHa) || 1)}</div></section>`);
        }
        const tiles = a && (a.leaves || []).length ? a.leaves.map((l) => ({ n: l.n, p: l.panel })) : panels.map((x, i) => ({ n: i + 1, p: x }));
        if (tiles.length) {
            out.push(`<section class="lv-card lv-in" ${K()}><h3>${I.leaf}<span data-tl="lcc-panel">Your ${tiles.length} leaves</span></h3><div class="lv-leaves">${tiles.map((t, i) => t.p == null
                ? `<span class="lv-leaf is-null" style="--i:${i}"><b>?</b><small>Leaf ${esc(t.n)}</small></span>`
                : `<span class="lv-leaf${t.p < crit ? ' is-low' : ''}" style="--c:${panelBg(t.p)};--t:${panelFg(t.p)};--i:${i}"><b>${t.p}</b><small>Leaf ${esc(t.n)}</small></span>`).join('')}</div>
                <p class="lv-legend"><i></i><span>Ringed leaves are below panel ${crit}${tl('critical-panel')}, the line for ${dsr ? 'direct seeded' : 'transplanted'} rice.${tiles.some((t) => t.p == null) ? ' A question mark is a photo Anee could not read.' : ''}</span></p></section>`);
        }
        if (v !== 'late' && (d.nextCheck || d.lastWeek)) {
            let small = 'Next check', big = d.nextCheck || '', line = `Then every 7 days until the first flowers${d.flowerDate ? `, around ${d.flowerDate}` : ''}.`;
            if (v === 'early') { small = 'Start reading on'; line = `That is day ${d.startDay} after ${wd}. Then check every 7 days.`; }
            if (d.lastWeek) { big = 'About the last one'; line = `The first flowers come ${d.flowerDate ? `around ${d.flowerDate}` : 'within a week'}. After them, no more nitrogen.`; }
            out.push(`<section class="lv-card lv-next lv-in" ${K()}>${d.nextCheckIso ? calBox(d.nextCheckIso) : `<span class="lv-cal is-icon">${I.cal}</span>`}<div><small>${small}</small><b>${esc(big)}</b><p>${esc(line)}</p></div></section>`);
        }
        if (a) {
            const items = [];
            if (a.health) items.push(['What Anee sees', esc(a.health)]);
            if ((a.otherCauses || []).length) items.push(['Not only nitrogen', `<ul>${a.otherCauses.map((c) => `<li><b>${esc(trimDot(c.cause))}</b>: ${esc(trimDot(c.sign))}. ${esc(c.action)}</li>`).join('')}</ul>`, 'nutrient-deficiency']);
            const notes = (a.leaves || []).filter((l) => l.note);
            if (notes.length) items.push(['Leaf by leaf', `<ul>${notes.map((l) => `<li><b>Leaf ${esc(l.n)}</b>: ${esc(l.note)}</li>`).join('')}</ul>`]);
            if (a.tip) items.push(['This week', esc(a.tip)]);
            if (a.photoNote) items.push([`Your photos${a.photoQuality ? `, ${esc(a.photoQuality)}` : ''}`, esc(a.photoNote)]);
            const face = (OPT && OPT.aneeFace) || $('lcAneeFace').src;
            out.push(`<section class="lv-card lv-anee lv-in" ${K()}><h3><img src="${esc(face)}" alt="">Anee's notes${a.confidence ? `<span class="lv-conf" data-tl="confidence">${esc(a.confidence)} confidence</span>` : ''}</h3>${items.map(([h, b, k]) => `<div class="lv-note"><b${k ? ` data-tl="${k}"` : ''}>${h}</b><div>${b}</div></div>`).join('')}</section>`);
        }
        const varLine = p.variety ? esc(p.variety) + (p.maturityDays ? `, ${p.maturityDays} days` : (p.maturity ? `, ${(MAT[p.maturity] || [''])[0].toLowerCase()} maturity` : '')) : (p.maturity && MAT[p.maturity] ? `${MAT[p.maturity][0]} maturity` : 'Not given');
        const rows = [
            [dsr ? 'Sown' : 'Transplanted', p.plantingDate ? `${fmt(p.plantingDate, { month: 'short', day: 'numeric', year: 'numeric' })}${days != null ? `, ${days} ${dw}` : ''}` : (days != null ? `${days} ${dw}` : 'Not given'), dsr ? 'direct-seeding' : 'transplanting'],
            ['Season', `${cap(seasonName)}${p.seasonInferred && p.seasonInferred !== p.season ? ', set by you' : ''}`],
            ['Variety', varLine],
            ['Area', areaWords(p.areaInput, p.areaUnit, p.areaHa || d.areaHa || 1)],
            ['Read', r.mode === 'anee' ? `By Anee from photos${item.charged ? `, ${num(item.charged)} credits` : ''}` : 'With your chart, free'],
        ];
        out.push(`<section class="lv-card lv-in" ${K()}><h3>${I.field}Your field</h3><dl class="lv-dl">${rows.map(([t, x, k]) => `<div><dt${k ? ` data-tl="${k}"` : ''}>${t}</dt><dd>${x}</dd></div>`).join('')}</dl></section>`);
        out.push(`<div class="lv-acts lv-in" ${K()}><button type="button" class="btn btn-primary" data-vact="new">${I.plus}New check</button><button type="button" class="btn btn-white" data-vact="ren">${I.pen}Rename</button><button type="button" class="btn btn-white lv-del" data-vact="del">${I.trash}Delete</button></div>`);
        return out.join('');
    }
    function openView(item, fresh) {
        VIEW = { item, fresh };
        $('lcViewTitle').textContent = item.title || 'Leaf color check';
        $('lcViewBody').innerHTML = viewHtml(item, fresh);
        const v = $('lcView');
        const wasOpen = !v.hidden;
        v.hidden = false;
        v.scrollTop = 0;
        document.documentElement.classList.add('lc-lock');
        if (!wasOpen && window.registerOverlay) window.registerOverlay('lcc-view', closeView);
        requestAnimationFrame(() => requestAnimationFrame(() => v.classList.add('is-on')));
        v.focus({ preventScroll: true });
    }
    function closeView() {
        const v = $('lcView');
        if (v.hidden) return;
        // A check just made, closed: a new check starts at step 1 (2026-10-17, the owner).
        // The field stays, as the page remembers it; the photos and the readings go.
        const fresh = !!(VIEW && VIEW.fresh);
        v.classList.remove('is-on');
        document.documentElement.classList.remove('lc-lock');
        if (window.unregisterOverlay) window.unregisterOverlay('lcc-view');
        VIEW = null;
        setTimeout(() => { if (!v.classList.contains('is-on')) { v.hidden = true; $('lcViewBody').innerHTML = ''; } if (fresh) newCheck(); }, reduce() ? 0 : 300);
    }
    $('lcViewX').addEventListener('click', closeView);
    $('lcViewName').addEventListener('click', () => { if (VIEW) openRename(VIEW.item.id); });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape' || !VIEW) return;
        if (document.querySelector('.lc-sheet[open]') || document.documentElement.classList.contains('sheet-open')) return;
        closeView();
    });
    $('lcViewBody').addEventListener('click', (e) => {
        const b = e.target.closest('[data-vact]');
        if (!b || !VIEW) return;
        if (b.dataset.vact === 'new') { VIEW.fresh = false; closeView(); newCheck(); }
        if (b.dataset.vact === 'ren') openRename(VIEW.item.id);
        if (b.dataset.vact === 'del') delCheck(VIEW.item.id);
        if (b.dataset.vact === 'fert') openFert();
    });
    $('lcFertList').addEventListener('click', (e) => {
        const b = e.target.closest('[data-f]');
        if (b && b.dataset.f !== 'own') {
            $('lcFertList').querySelectorAll('[data-f]').forEach((x) => x.setAttribute('aria-checked', String(x === b)));
            setFert({ k: b.dataset.f });
            fertDone();
            return;
        }
        if (b) {
            // Another bag: open its three numbers. It counts once "Use this bag" is tapped.
            const box = $('lcFertOwn'), on = !box.classList.contains('is-on');
            box.classList.toggle('is-on', on);
            box.inert = !on;
            b.setAttribute('aria-expanded', String(on));
            if (on && matchMedia('(pointer: fine)').matches) setTimeout(() => $('lcOwnN').focus({ preventScroll: true }), 60);
            if (on) setTimeout(() => box.scrollIntoView({ block: 'nearest', behavior: reduce() ? 'auto' : 'smooth' }), reduce() ? 0 : 280);
            return;
        }
        if (e.target.closest('#lcOwnUse')) {
            if (!ownLine()) { bump($('lcOwnOut'), 'is-shake'); $('lcOwnN').focus(); return; }
            setFert({ k: 'own', g: ownGrade() });
            fertDone();
        }
    });
    $('lcFertList').addEventListener('input', (e) => { if (e.target.closest('.lc-fown-in')) ownLine(); });
    $('lcFertList').addEventListener('keydown', (e) => { if (e.key === 'Enter' && e.target.closest('.lc-fown-in')) { e.preventDefault(); $('lcOwnUse').click(); } });
    function newCheck() {
        st.chart = Array(10).fill(null); st.cur = 0; st.shots = [];
        paintChartState(); paintShots();
        go('1');
        const top = $('lcSteps').getBoundingClientRect().top + window.scrollY - 76;
        window.scrollTo({ top: Math.max(0, top), behavior: reduce() ? 'auto' : 'smooth' });
    }
    function took(data, fresh) {
        const item = { id: data.id, title: data.title, report: data.report, params: data.params, charged: data.charged };
        CACHE.set(String(item.id), item);
        if (data.history) paintList(data.history, data.hasMore, false);
        openView(item, fresh);
    }

    /* ---------------- your checks ---------------- */
    const LIST = { rows: [], page: 1, hasMore: false, seen: new Set(), busy: false };
    const VERD = { due: ['Nitrogen due', 'is-due'], ok: ['No nitrogen yet', 'is-ok'], early: ['Too early', 'is-early'], late: ['Past flowering', 'is-late'] };
    function cardHtml(r, k) {
        const v = VERD[r.verdict] || VERD.ok;
        const meta = [];
        if (r.days != null) meta.push(`<span><b>${r.days}</b> ${r.dayWord}</span>`);
        if (r.verdict === 'due' && r.ureaPerHa) meta.push(`<span data-uha="${Number(r.ureaPerHa)}">${fertMeta(r.ureaPerHa)}</span>`);
        else if (r.verdict === 'ok') meta.push(`<span data-nofert>No ${esc(fert().short)} this week</span>`);
        if (r.leaves) meta.push(`<span>${r.below} of ${r.leaves} pale</span>`);
        const dots = (r.panels || []).length ? `<div class="lc-ck-dots" aria-hidden="true">${r.panels.map((p) => `<i style="--c:${panelBg(p)}" class="${r.critical && p < r.critical ? 'is-low' : ''}"></i>`).join('')}</div>` : '';
        const isNew = !LIST.seen.has(r.id);
        return `<article class="lc-ck ${v[1]}${isNew ? ' is-new' : ''}" data-open="${r.id}" tabindex="0" role="button" aria-label="Open ${esc(r.title)}" style="--k:${k}">
            <div class="lc-ck-date"><b>${esc(r.day)}</b><span>${esc(r.month)}</span></div>
            <div class="lc-ck-main">
                <div class="lc-ck-top"><span class="lc-pill ${v[1]}">${v[0]}</span><span class="lc-ck-mode">${r.mode === 'anee' ? 'Read by Anee' : 'Your chart'}</span></div>
                <h4>${esc(r.title)}</h4>
                <div class="lc-ck-meta">${meta.join('')}</div>
                ${dots}
            </div>
            <div class="lc-ck-acts"><button type="button" class="lc-ib" data-ren="${r.id}" aria-label="Rename ${esc(r.title)}">${I.pen}</button><button type="button" class="lc-ib is-del" data-del="${r.id}" aria-label="Delete ${esc(r.title)}">${I.trash}</button></div>
        </article>`;
    }
    function paintList(rows, hasMore, append) {
        LIST.rows = append ? LIST.rows.concat(rows) : rows;
        LIST.hasMore = !!hasMore;
        if (!append) LIST.page = 1;
        const html = rows.map((r, i) => cardHtml(r, i)).join('');
        if (append) $('lcList').insertAdjacentHTML('beforeend', html); else $('lcList').innerHTML = html;
        rows.forEach((r) => LIST.seen.add(r.id));
        $('lcEmpty').hidden = LIST.rows.length > 0;
        $('lcMore').hidden = !LIST.hasMore;
        $('lcChecksN').textContent = LIST.rows.length ? `${LIST.rows.length}${LIST.hasMore ? '+' : ''} saved` : '';
    }
    async function openSaved(id, card) {
        const key = String(id);
        if (CACHE.has(key)) { openView(CACHE.get(key), false); return; }
        card && card.classList.add('is-loading');
        try {
            const res = await api(U.one(id));
            const item = { id: res.data.id, title: res.data.title, report: res.data.report, params: res.data.params, charged: res.data.charged };
            CACHE.set(key, item);
            openView(item, false);
        } catch (err) { toast(err.message, 'error'); }
        finally { card && card.classList.remove('is-loading'); }
    }
    $('lcList').addEventListener('click', (e) => {
        const ren = e.target.closest('[data-ren]');
        if (ren) { openRename(ren.dataset.ren); return; }
        const del = e.target.closest('[data-del]');
        if (del) { delCheck(del.dataset.del); return; }
        const card = e.target.closest('[data-open]');
        if (card) openSaved(card.dataset.open, card);
    });
    $('lcList').addEventListener('keydown', (e) => {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('[data-open]')) { e.preventDefault(); openSaved(e.target.dataset.open, e.target); }
    });
    $('lcMore').addEventListener('click', async () => {
        const btn = $('lcMore');
        if (LIST.busy) return;
        LIST.busy = true; btn.textContent = 'Loading…';
        try {
            const res = await api(U.list + '?page=' + (LIST.page + 1));
            LIST.page = res.data.page;
            paintList(res.data.history || [], res.data.hasMore, true);
        } catch (err) { toast(err.message, 'error'); }
        finally { LIST.busy = false; btn.textContent = 'Show more'; }
    });
    async function delCheck(id) {
        const row = LIST.rows.find((r) => String(r.id) === String(id));
        const anee = (row && row.mode === 'anee') || (VIEW && String(VIEW.item.id) === String(id) && (VIEW.item.report || {}).mode === 'anee');
        const ok = window.confirmAction
            ? await window.confirmAction({ title: 'Delete this check?', message: 'It leaves Your checks for good.' + (anee ? ' The credits it used are not refunded.' : ''), confirmText: 'Delete' })
            : confirm('Delete this check?');
        if (!ok) return;
        const card = $('lcList').querySelector(`[data-open="${CSS.escape(String(id))}"]`);
        card && card.classList.add('is-gone');
        try {
            const res = await api(U.del(id), { method: 'DELETE' });
            CACHE.delete(String(id));
            if (VIEW && String(VIEW.item.id) === String(id)) closeView();
            setTimeout(() => paintList(res.data.history || [], res.data.hasMore, false), reduce() ? 0 : 280);
            toast('Deleted.');
        } catch (err) { card && card.classList.remove('is-gone'); toast(err.message, 'error'); }
    }

    /* ---------------- the sheets ---------------- */
    const sheetLock = () => document.documentElement.classList.toggle('lc-lock', !!document.querySelector('.lc-sheet[open]') || !$('lcView').hidden);
    function sheetOpen(dlg, focus) {
        if (!dlg || dlg.open || !dlg.showModal) return;
        dlg.showModal();
        sheetLock();
        if (window.registerOverlay) window.registerOverlay('lcc:' + dlg.id, () => sheetClose(dlg));
        // The search box only where typing is easy (not on a phone, where it raises the keyboard).
        if (focus && matchMedia('(pointer: fine)').matches) focus.focus(); else dlg.querySelector('.lc-sbox').focus({ preventScroll: true });
    }
    function sheetClose(dlg) {
        if (!dlg.open || dlg.classList.contains('is-closing')) return;
        if (window.unregisterOverlay) window.unregisterOverlay('lcc:' + dlg.id);
        const done = () => { dlg.classList.remove('is-closing'); dlg.close(); sheetLock(); };
        if (reduce()) { done(); return; }
        dlg.classList.add('is-closing');
        setTimeout(done, 230);
    }
    document.querySelectorAll('.lc-sheet').forEach((dlg) => {
        dlg.addEventListener('click', (e) => { if (e.target === dlg || e.target.closest('.lc-x') || e.target.closest('[data-close]')) sheetClose(dlg); });
        dlg.addEventListener('cancel', (e) => { e.preventDefault(); sheetClose(dlg); });
    });

    /* The planting date: quick chips, a month of big days, the age it makes. */
    const DS = { month: TODAY.slice(0, 7), sel: null };
    const QUICK = [['Today', 0], ['1 week ago', 7], ['2 weeks ago', 14], ['3 weeks ago', 21], ['4 weeks ago', 28], ['6 weeks ago', 42], ['8 weeks ago', 56]];
    function openDate() {
        DS.sel = st.date;
        DS.month = (st.date || TODAY).slice(0, 7);
        $('lcDateTitle').textContent = st.method === 'dsr' ? 'When was the rice sown?' : 'When was the rice transplanted?';
        $('lcQuick').innerHTML = QUICK.map(([t, n]) => `<button type="button" class="lc-qc" data-q="${ds(dn(TODAY) - n)}">${t}</button>`).join('');
        paintMonth(0); paintPick();
        sheetOpen($('lcDateSheet'));
        $('lcQuick').querySelector('.is-on')?.scrollIntoView({ inline: 'center', block: 'nearest' });
        $('lcDays').querySelector('.is-sel')?.scrollIntoView({ block: 'nearest' });
    }
    function paintMonth(dir) {
        const [y, m] = DS.month.split('-').map(Number);
        const first = Date.UTC(y, m - 1, 1) / 864e5;
        const count = new Date(Date.UTC(y, m, 0)).getUTCDate();
        const lead = new Date(first * 864e5).getUTCDay();
        $('lcMonName').textContent = new Date(first * 864e5).toLocaleDateString('en-US', { timeZone: 'UTC', month: 'long', year: 'numeric' });
        let h = '';
        for (let i = 0; i < lead; i++) h += '<span aria-hidden="true"></span>';
        for (let dd = 1; dd <= count; dd++) {
            const s = ds(first + dd - 1), off = s > TODAY || s < MIN_DATE;
            h += `<button type="button" class="lc-d${s === TODAY ? ' is-today' : ''}${s === DS.sel ? ' is-sel' : ''}" data-d="${s}"${off ? ' disabled' : ''} aria-pressed="${s === DS.sel}" aria-label="${fmt(s, { weekday: 'long', month: 'long', day: 'numeric' })}">${dd}</button>`;
        }
        // Always six weeks tall, so the sheet does not jump from month to month.
        for (let i = lead + count; i < 42; i++) h += '<span class="lc-d" aria-hidden="true"></span>';
        const g = $('lcDays');
        g.innerHTML = h;
        if (dir && !reduce()) { g.classList.remove('is-slide-l', 'is-slide-r'); void g.offsetWidth; g.classList.add(dir < 0 ? 'is-slide-l' : 'is-slide-r'); }
        $('lcPrevM').disabled = DS.month <= MIN_DATE.slice(0, 7);
        $('lcNextM').disabled = DS.month >= TODAY.slice(0, 7);
    }
    function paintPick() {
        $('lcQuick').querySelectorAll('.lc-qc').forEach((c) => c.classList.toggle('is-on', c.dataset.q === DS.sel));
        if (!DS.sel) { $('lcPickDate').textContent = 'Tap a day'; $('lcPickAge').textContent = 'Days after today are off'; $('lcDateUse').disabled = true; return; }
        const a = ageOf(DS.sel);
        $('lcPickDate').textContent = fmt(DS.sel, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
        $('lcPickAge').textContent = a === 0 ? `Today, day 0 after ${word()}` : `${a} ${a === 1 ? 'day' : 'days'} after ${word()}`;
        $('lcDateUse').disabled = false;
    }
    function pickDay(s) {
        const chip = $('lcQuick').querySelector(`[data-q="${s}"]`);
        const old = $('lcDays').querySelector('.is-sel');
        if (old) { old.classList.remove('is-sel'); old.setAttribute('aria-pressed', 'false'); }
        DS.sel = s;
        if (s.slice(0, 7) !== DS.month) { const dir = s.slice(0, 7) < DS.month ? -1 : 1; DS.month = s.slice(0, 7); paintMonth(dir); }
        else { const b = $('lcDays').querySelector(`[data-d="${s}"]`); if (b) { b.classList.add('is-sel'); b.setAttribute('aria-pressed', 'true'); } }
        paintPick();
        if (chip) chip.scrollIntoView({ inline: 'nearest', block: 'nearest', behavior: reduce() ? 'auto' : 'smooth' });
    }
    $('lcDays').addEventListener('click', (e) => { const b = e.target.closest('[data-d]'); if (b && !b.disabled) pickDay(b.dataset.d); });
    $('lcQuick').addEventListener('click', (e) => { const b = e.target.closest('[data-q]'); if (b) pickDay(b.dataset.q); });
    const shiftMonth = (k) => {
        const [y, m] = DS.month.split('-').map(Number);
        const d = new Date(Date.UTC(y, m - 1 + k, 1));
        const next = d.toISOString().slice(0, 7);
        if (next > TODAY.slice(0, 7) || next < MIN_DATE.slice(0, 7)) return;
        DS.month = next; paintMonth(k);
    };
    $('lcPrevM').addEventListener('click', () => shiftMonth(-1));
    $('lcNextM').addEventListener('click', () => shiftMonth(1));
    $('lcDateUse').addEventListener('click', () => {
        if (!DS.sel) return;
        const changed = DS.sel !== st.date;
        st.date = DS.sel;
        if (changed) st.seasonSet = null;
        sheetClose($('lcDateSheet'));
        paintField(); remember();
        if (changed) bump($('lcDateBtn'), 'is-bump');
    });
    $('lcDateBtn').addEventListener('click', openDate);

    /* The variety, or only its maturity: the catalogue with a search, built on first open. */
    let VKEYS = null;
    function buildVars() {
        if (VKEYS) return;
        const sub = (v) => [v.l, v.t].filter(Boolean).join(' · ');
        const row = (v, i) => `<button type="button" class="lc-vrow" role="option" data-vi="${i}" aria-selected="false"><span class="lc-vrow-t"><b>${esc(v.n)}</b>${sub(v) ? `<small>${esc(sub(v))}</small>` : ''}</span>${v.h ? '<span class="lc-hy">Hybrid</span>' : ''}<span class="lc-vd">${v.d} days</span><span class="lc-on">${I.check}</span></button>`;
        $('lcPopRows').innerHTML = VARS.map((v, i) => (v.p ? row(v, i) : '')).join('');
        $('lcAllRows').innerHTML = VARS.map(row).join('');
        VKEYS = VARS.map((v) => { const s = `${v.n} ${v.l} ${v.t}`.toLowerCase(); return [s, s.replace(/[^a-z0-9]/g, '')]; });
    }
    function filterVars() {
        const raw = $('lcVarQ').value.trim(), q = raw.toLowerCase(), qc = q.replace(/[^a-z0-9]/g, '');
        const all = !q;
        let n = 0;
        $('lcAllRows').querySelectorAll('.lc-vrow').forEach((r) => {
            const k = VKEYS[Number(r.dataset.vi)];
            const hit = all || k[0].includes(q) || (qc.length > 1 && k[1].includes(qc));
            r.hidden = !hit;
            if (hit) n++;
        });
        $('lcMatGroup').hidden = !all;
        $('lcPopGroup').hidden = !all || !$('lcPopRows').children.length;
        $('lcAllHead').textContent = all ? `All ${VARS.length} varieties, newest first` : `${n} ${n === 1 ? 'variety' : 'varieties'} found`;
        $('lcAllGroup').hidden = !all && !n;
        $('lcVarTyped').hidden = !raw;
        $('lcVarTypedName').textContent = raw;
        // Your own name: on top when nothing matches, under the matches when something does.
        const list = $('lcVarList'), typed = $('lcVarTyped');
        if (n && !all) { if (typed.nextElementSibling !== $('lcVarNone')) list.insertBefore(typed, $('lcVarNone')); }
        else if (list.firstElementChild !== typed) list.insertBefore(typed, list.firstElementChild);
        $('lcVarNone').hidden = all || n > 0;
    }
    function markVars() {
        const v = varRow();
        document.querySelectorAll('#lcVarSheet .lc-vrow[data-vi]').forEach((r) => r.setAttribute('aria-selected', String(!!v && VARS[Number(r.dataset.vi)] === v)));
        document.querySelectorAll('#lcVarSheet [data-mat]').forEach((b) => b.classList.toggle('is-on', !v && (b.dataset.mat || null) === st.maturity));
        $('lcMatHead').classList.remove('is-ask');
        $('lcMatHead').textContent = 'Not sure of the variety? Pick how long it takes';
    }
    function openVars() {
        buildVars();
        $('lcVarQ').value = '';
        filterVars(); markVars();
        sheetOpen($('lcVarSheet'), $('lcVarQ'));
        const on = $('lcVarSheet').querySelector('#lcAllRows .lc-vrow[aria-selected="true"]');
        if (on) on.scrollIntoView({ block: 'center' }); else $('lcVarList').scrollTop = 0;
    }
    const varPicked = () => { paintField(); remember(); bump($('lcVarBtn'), 'is-bump'); };
    $('lcVarBtn').addEventListener('click', openVars);
    $('lcVarQ').addEventListener('input', filterVars);
    $('lcVarList').addEventListener('click', (e) => {
        const row = e.target.closest('.lc-vrow[data-vi]');
        if (row) {
            const v = VARS[Number(row.dataset.vi)];
            st.variety = v.n; st.varSrc = 'list'; st.maturityDays = v.d; st.maturity = classOf(v.d);
            sheetClose($('lcVarSheet')); varPicked();
            return;
        }
        const mat = e.target.closest('[data-mat]');
        if (mat) {
            st.maturity = mat.dataset.mat || null; st.maturityDays = null;
            if (st.varSrc === 'list') { st.variety = ''; st.varSrc = null; }
            sheetClose($('lcVarSheet')); varPicked();
            return;
        }
        if (e.target.closest('#lcVarTyped')) {
            st.variety = $('lcVarQ').value.trim().slice(0, 60); st.varSrc = 'typed'; st.maturityDays = null;
            $('lcVarQ').value = '';
            filterVars(); markVars();
            $('lcMatHead').textContent = `Now pick how long ${st.variety} takes`;
            $('lcMatHead').classList.add('is-ask');
            $('lcVarList').scrollTo({ top: 0, behavior: reduce() ? 'auto' : 'smooth' });
            paintField(); remember();
        }
    });

    /* Rename. */
    let REN = null;
    function openRename(id) {
        const row = LIST.rows.find((r) => String(r.id) === String(id));
        const title = row ? row.title : (VIEW && String(VIEW.item.id) === String(id) ? VIEW.item.title : '');
        REN = id;
        $('lcRenInput').value = title || '';
        sheetOpen($('lcRenSheet'), $('lcRenInput'));
    }
    $('lcRenForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = $('lcRenSave');
        const title = $('lcRenInput').value.trim();
        if (!title) { $('lcRenInput').focus(); return; }
        btn.disabled = true;
        try {
            const res = await api(U.rename, { method: 'POST', body: { id: REN, title } });
            const c = CACHE.get(String(REN));
            if (c) c.title = res.data.title;
            if (VIEW && String(VIEW.item.id) === String(REN)) { VIEW.item.title = res.data.title; $('lcViewTitle').textContent = res.data.title; }
            paintList(res.data.history || [], res.data.hasMore, false);
            sheetClose($('lcRenSheet'));
            toast('Renamed.');
        } catch (err) { toast(err.message, 'error'); }
        finally { btn.disabled = false; }
    });

    /* ---------------- How the leaf color chart works ---------------- */
    const lxCol = (p) => panelBg(p);
    const LX = [
        ['Pick 10 hills', 'Walk across the field and stop at 10 hills, spread out. On each, take the youngest leaf that is fully open.', () => {
            const pts = [[34, 54], [62, 118], [90, 54], [118, 118], [146, 54], [174, 118], [202, 54], [230, 118], [258, 54], [286, 118]];
            return `<rect class="lx-bg" x="4" y="4" width="312" height="152" rx="18"/><path class="lx-rows" d="M18 36H302M18 70H302M18 104H302M18 138H302"/>
                <polyline class="lx-walk" pathLength="1" points="${pts.map((p) => p.join(',')).join(' ')}"/>
                ${pts.map(([x, y], i) => `<g transform="translate(${x} ${y})"><g class="lx-pop" style="--i:${i}"><path class="lx-blade" d="M0 0C-1 -9 -6 -15 -11 -19M0 0C0 -10 1 -17 2 -24M0 0C2 -8 7 -13 12 -16"/><circle class="lx-badge" cy="12" r="8"/><text class="lx-badge-t" y="15.5">${i + 1}</text></g></g>`).join('')}`;
        }],
        ['Match each leaf to the chart', 'Hold the middle of the leaf on the chart, in your own shade. Panel 2 is pale, panel 5 is deep green. Between two? Use the half.', () => `
            <rect class="lx-bg" x="4" y="4" width="312" height="152" rx="18"/>
            <path class="lx-leaf" d="M26 58C92 18 226 14 296 40C230 62 112 92 26 58Z"/><path class="lx-rib" d="M34 57C120 50 220 38 288 41"/>
            <g class="lx-mark"><path d="M69 99l-8 -11h16z"/></g>
            ${[2, 3, 4, 5].map((p, i) => `<rect x="${40 + i * 60}" y="102" width="58" height="34" rx="7" fill="${lxCol(p)}"/><text class="lx-pn" x="${69 + i * 60}" y="124" fill="${panelFg(p)}">${p}</text>`).join('')}
            <text class="lx-small" x="40" y="151">pale</text><text class="lx-small" x="278" y="151" text-anchor="end">deep green</text>`],
        ['Count the pale ones', 'The line is panel 4 for transplanted rice and panel 3 for direct seeded. Count the leaves that fall below it.', () => {
            const V = [3, 3.5, 4, 3, 4.5, 3, 3.5, 4, 3, 3.5];
            const y = (p) => 140 - (p - 2) * 36;
            return `<rect class="lx-bg" x="4" y="4" width="312" height="152" rx="18"/>
                ${[2, 3, 4, 5].map((p) => `<path class="lx-grid" d="M40 ${y(p)}H300"/><text class="lx-small" x="24" y="${y(p) + 4}" text-anchor="middle">${p}</text>`).join('')}
                <path class="lx-crit" pathLength="1" d="M40 68H300"/><text class="lx-critt" x="42" y="61">panel 4 line</text>
                ${V.map((p, i) => `<g transform="translate(${56 + i * 26} ${y(p)})"><g class="lx-pop" style="--i:${i}">${p < 4 ? '<circle class="lx-halo" r="12"/>' : ''}<path d="M0 -10C-6 -4 -6 4 0 10C6 4 6 -4 0 -10Z" fill="${lxCol(p)}" stroke="rgb(0 0 0 / .15)"/></g></g>`).join('')}
                <g class="lx-tally"><rect x="178" y="124" width="126" height="26" rx="13"/><text x="241" y="141" text-anchor="middle">7 of 10 below</text></g>`;
        }],
        ['More than half pale? Time for urea', 'Spread about 50 kg of urea per hectare in the wet season, or 65 kg in the dry season. Half or fewer? Wait a week and read again.', () => `
            <rect class="lx-bg" x="4" y="4" width="312" height="152" rx="18"/>
            ${Array.from({ length: 10 }, (_, i) => `<g class="lx-pop" style="--i:${i}"><rect x="${32 + i * 26}" y="16" width="22" height="14" rx="4" fill="${i < 7 ? '#f59e0b' : '#4d8a2a'}"/></g>`).join('')}
            <path class="lx-half" d="M160 9V37"/><text class="lx-small" x="160" y="49" text-anchor="middle">half</text>
            <g class="lx-bag"><path class="lx-sack" d="M134 72h52l10 72h-72z"/><path class="lx-sack-top" d="M134 72q26 -10 52 0"/><text class="lx-sack-t" x="160" y="106" text-anchor="middle">UREA</text><text class="lx-sack-s" x="160" y="124" text-anchor="middle">46-0-0</text></g>
            <g class="lx-fade" style="--d:2.4s"><text class="lx-kg" x="66" y="104" text-anchor="middle">50 kg</text><text class="lx-small" x="66" y="122" text-anchor="middle">per ha, wet season</text></g>
            <g class="lx-fade" style="--d:2.7s"><text class="lx-kg" x="256" y="104" text-anchor="middle">65 kg</text><text class="lx-small" x="256" y="122" text-anchor="middle">per ha, dry season</text></g>`],
        ['Check every week', 'Start 14 days after transplanting, or 21 after sowing. Read again every 7 days until the first flowers.', () => {
            const X = (d) => 24 + d * 4.2;
            return `<rect class="lx-bg" x="4" y="4" width="312" height="152" rx="18"/>
                <path class="lx-axis" d="M24 100H290"/><path class="lx-axis-on" pathLength="1" d="M24 100H290"/>
                <g transform="translate(24 100)"><path class="lx-blade" d="M0 0C-1 -6 -4 -10 -7 -12M0 0C0 -7 1 -11 1 -15M0 0C2 -5 5 -8 8 -10"/></g>
                ${[14, 21, 28, 35, 42, 49, 56].map((d, i) => `<g transform="translate(${X(d)} 100)"><g class="lx-pop" style="--i:${i * 2 + 2}"><circle class="lx-tick" r="4"/><path d="M0 -32C-6 -26 -6 -18 0 -12C6 -18 6 -26 0 -32Z" fill="#4d8a2a"/></g></g>`).join('')}
                <g transform="translate(288 100)"><g class="lx-pop" style="--i:16"><path class="lx-panicle" d="M0 0V-36M0 -32c-4 2-7 6-8 11M0 -25c4 2 7 6 8 11M0 -18c-4 2-6 5-7 9"/><circle class="lx-grain" cx="-8" cy="-21" r="2.4"/><circle class="lx-grain" cx="8" cy="-14" r="2.4"/><circle class="lx-grain" cx="-7" cy="-9" r="2.4"/><circle class="lx-grain" cx="0" cy="-37" r="2.4"/></g></g>
                <text class="lx-small" x="${X(14)}" y="123" text-anchor="middle">Day 14</text>
                <text class="lx-small" x="${X(35)}" y="56" text-anchor="middle">every 7 days</text>
                <text class="lx-small" x="294" y="123" text-anchor="end">First flowers</text>`;
        }],
    ];
    // The Tagalog flag each step's title carries, step by step.
    const LX_TL = ['hill', 'lcc-panel', 'critical-panel', 'urea', ''];
    let LXI = 0;
    function lxBuild() {
        if ($('lxStage').children.length) return;
        $('lxStage').innerHTML = LX.map(([t, s, art], i) => `<figure class="lx-step" data-x="${i}"><svg class="lx-svg" viewBox="0 0 320 160" role="img" aria-label="${esc(t)}">${art()}</svg><figcaption><b${LX_TL[i] ? ` data-tl="${LX_TL[i]}"` : ''}>${i + 1}. ${esc(t)}</b><span>${esc(s)}</span></figcaption></figure>`).join('');
        $('lxDots').innerHTML = LX.map((_, i) => `<button type="button" data-x="${i}" aria-label="Step ${i + 1}"></button>`).join('');
    }
    function lxGo(i) {
        LXI = Math.max(0, Math.min(LX.length - 1, i));
        $('lxStage').querySelectorAll('.lx-step').forEach((f, j) => f.classList.toggle('is-on', j === LXI));
        $('lxDots').querySelectorAll('button').forEach((b, j) => { b.classList.toggle('is-on', j === LXI); b.setAttribute('aria-current', j === LXI ? 'step' : 'false'); });
        $('lxBack').disabled = false;   // on the first page it goes back where they came from
        $('lxBack').style.visibility = LXI === 0 ? 'hidden' : '';
        $('lxNext').textContent = LXI === LX.length - 1 ? 'Start my check' : 'Next';
    }
    $('lcHowOpen').addEventListener('click', () => { lxBuild(); lxGo(0); sheetOpen($('lcHowSheet')); });
    $('lxBack').addEventListener('click', () => (LXI === 0 ? window.aneeLeave() : lxGo(LXI - 1)));
    $('lxNext').addEventListener('click', () => {
        if (LXI < LX.length - 1) { lxGo(LXI + 1); return; }
        sheetClose($('lcHowSheet'));
        if (st.step !== '1') go('1');
    });
    $('lxDots').addEventListener('click', (e) => { const b = e.target.closest('[data-x]'); if (b) lxGo(Number(b.dataset.x)); });
    let lxX = null;
    $('lxStage').addEventListener('touchstart', (e) => { lxX = e.touches[0].clientX; }, { passive: true });
    $('lxStage').addEventListener('touchend', (e) => {
        if (lxX === null) return;
        const dx = e.changedTouches[0].clientX - lxX;
        lxX = null;
        if (Math.abs(dx) > 50) lxGo(LXI + (dx < 0 ? 1 : -1));
    }, { passive: true });

    /* ---------------- boot ---------------- */
    async function boot() {
        recall();
        // The varieties a saved pick named must still exist, or it becomes a typed name.
        if (st.varSrc === 'list' && !varRow()) st.varSrc = st.variety ? 'typed' : null;
        buildChart();
        paintField(); paintPalette(); paintChartState(); paintShots();
        try {
            const res = await api(U.options);
            OPT = res.data;
            paintPrice(); paintShots();
            paintList(OPT.history || [], OPT.hasMore, false);
        } catch (err) { toast(err.message, 'error'); }
    }
    if (window.api) boot(); else window.addEventListener('load', boot, { once: true });
})();
</script>
@endpush
