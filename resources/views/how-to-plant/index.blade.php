@extends('layouts.app')
@section('title', 'How to Plant')
@section('page-title', 'How to Plant')
@section('page-subtitle', 'The traditional way, crop by crop')
@section('back', \App\Support\BackTo::url(route('app.dashboard')))
@section('body-class', 'hide-tabbar no-footer')

{{-- How to Plant (2026-10-19, the owner): how traditional Filipino farmers
     plant each crop, step by step on a day count, with a clear word on top
     that it is the traditional way and should be updated for a higher yield,
     a video where a good one exists (loaded only on a tap, from
     youtube-nocookie.com), and "Use this in my Protocol Builder", which makes
     a new protocol draft of the way shown. Picking another crop draws its
     guide in place (HowToPlantController::guide); nothing moves on by itself.
     The guides are App\Support\HowToPlant's. --}}
@push('head')
<style>
    .htp { --e: cubic-bezier(.22,1,.36,1); max-width: 62rem; margin: 0 auto; padding-bottom: calc(4rem + env(safe-area-inset-bottom)); }
    .htp [hidden] { display: none !important; }

    /* ---- the top: a field being planted, and the crop to pick ---- */
    .htp-top { position: relative; overflow: hidden; border-radius: 1.4rem; color: #eef7e4;
        background: radial-gradient(120% 150% at 100% 0%, #8cc152 0%, #3f7a22 50%, #1c3a0c 100%); }
    .htp-band { display: flex; gap: 1rem; align-items: center; padding: 1.1rem 1.2rem .4rem; }
    .htp-art { flex: none; width: 5.4rem; height: 5rem; }
    .htp-art svg { width: 100%; height: 100%; overflow: visible; }
    .htp-sprout { transform-origin: 50% 100%; transform-box: fill-box; animation: htpGrow 3.6s var(--e) infinite; }
    .htp-sprout.is-2 { animation-delay: .5s; } .htp-sprout.is-3 { animation-delay: 1s; }
    @keyframes htpGrow { 0%, 100% { transform: scaleY(.86); } 50% { transform: scaleY(1.06); } }
    .htp-hand { animation: htpHand 3.6s var(--e) infinite; }
    @keyframes htpHand { 0%, 100% { transform: translate(0, 0); } 45% { transform: translate(-3px, 5px); } 60% { transform: translate(-3px, 5px); } }
    .htp-sun { animation: htpSun 6s ease-in-out infinite; transform-origin: center; transform-box: fill-box; }
    @keyframes htpSun { 50% { transform: scale(1.12); } }
    .htp-band h2 { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; line-height: 1.2; color: #fff; }
    .htp-band p { margin-top: .3rem; font-size: .86rem; line-height: 1.5; color: #dcefc9; }
    .htp-pick { padding: .2rem 1rem 1rem; }
    .htp-pick > span { display: block; margin: .5rem .1rem 0; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #cfe6b8; }
    .htp-pick .cp-tag { margin-top: .35rem; background: #fff; border-color: transparent; box-shadow: 0 14px 30px -20px rgb(0 0 0 / .7); }
    .htp-pick .cp-tag:hover { border-color: #f5c518; }

    /* ---- the guide: it fades out, the next one rises in ---- */
    .htp-guide { margin-top: 1rem; transition: opacity .22s var(--e), transform .22s var(--e); }
    .htp-guide.is-out { opacity: 0; transform: translateY(6px); }
    .htp-guide.is-busy { pointer-events: none; }
    .hg { display: grid; gap: .9rem; }
    .hg > * { animation: hgIn .46s var(--e) both; animation-delay: calc(var(--i, 0) * 45ms); }
    @keyframes hgIn { from { opacity: 0; transform: translateY(10px); } }
    .hg-h { font-family: var(--font-heading); font-size: 1.08rem; font-weight: 800; color: var(--color-gray-900); }

    /* The disclaimer: amber, on top, in plain words. */
    .hg-warn { display: flex; gap: .75rem; align-items: flex-start; padding: .95rem 1rem; border-radius: 1.15rem; background: #fff7e0; border: 1px solid #f3d27a; color: #5b3d00; }
    .hg-warn-ic { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .8rem; display: grid; place-items: center; background: #f5c518; color: #3b2f00; }
    .hg-warn-ic svg { width: 1.15rem; height: 1.15rem; }
    .hg-warn b { display: block; font-size: .92rem; font-weight: 900; color: #4a3200; }
    .hg-warn p { margin-top: .2rem; font-size: .84rem; line-height: 1.55; }
    .hg-warn-go { display: inline-flex; align-items: center; gap: .25rem; margin-top: .45rem; font-size: .8rem; font-weight: 800; color: #7a4f00; text-decoration: underline; text-underline-offset: 3px; }
    .hg-warn-go svg { width: .8rem; height: .8rem; transition: transform .28s var(--e); }
    .hg-warn-go:hover svg { transform: translateX(2px); }

    /* The crop. */
    .hg-hero { display: grid; grid-template-columns: auto 1fr; gap: .2rem .9rem; align-items: center; padding: 1.05rem 1.1rem 1.1rem; border-radius: 1.25rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .hg-hero-ic { width: 3.6rem; height: 3.6rem; border-radius: 1.1rem; display: grid; place-items: center; font-size: 2rem; background: linear-gradient(135deg, #eef7e4, #dcefc9); }
    .hg-hero-t small { display: block; font-size: .68rem; font-weight: 900; letter-spacing: .09em; text-transform: uppercase; color: #4a7c2a; }
    .hg-hero-t h2 { font-family: var(--font-heading); font-size: clamp(1.45rem, 5vw, 1.9rem); font-weight: 800; line-height: 1.1; color: var(--color-gray-900); }
    .hg-names { display: block; margin-top: .15rem; font-size: .84rem; font-weight: 700; color: var(--color-gray-600); }
    .hg-names i { font-style: normal; font-weight: 600; color: var(--color-gray-500); }
    .hg-sum { grid-column: 1 / -1; margin-top: .65rem; font-size: .9rem; line-height: 1.6; color: var(--color-gray-700); }
    .hg-badges { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .6rem; }
    .hg-badges span { display: inline-flex; align-items: center; gap: .3rem; padding: .22rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #2d5016; background: #eef5e6; }
    .hg-badges svg { width: .85rem; height: .85rem; }
    .hg-badges .is-vid { color: #8a1c1c; background: #fdeaea; }

    .hg-facts { display: grid; gap: .75rem; }
    @media (min-width: 720px) { .hg-facts { grid-template-columns: 1fr 1fr; } }
    .hg-fact { padding: .95rem 1rem; border-radius: 1.15rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .hg-fact-ic { display: inline-grid; place-items: center; width: 2rem; height: 2rem; border-radius: .7rem; color: #3d6823; background: #e9f2de; }
    .hg-fact-ic svg { width: 1.05rem; height: 1.05rem; }
    .hg-fact h3 { margin-top: .45rem; font-size: .92rem; font-weight: 900; color: var(--color-gray-900); }
    .hg-fact p { margin-top: .25rem; font-size: .86rem; line-height: 1.6; color: var(--color-gray-700); }

    /* The video: a picture first, the player only on a tap. */
    .hg-video { padding: 1rem; border-radius: 1.25rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .hg-yt { position: relative; margin-top: .7rem; aspect-ratio: 16 / 9; border-radius: 1rem; overflow: hidden; background: #0d1409; }
    .hg-yt-btn { position: absolute; inset: 0; width: 100%; height: 100%; padding: 0; border: 0; cursor: pointer; background: #0d1409; }
    .hg-yt-btn img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s var(--e), opacity .28s var(--e); }
    .hg-yt-btn:hover img { transform: scale(1.03); }
    .hg-yt-play { position: absolute; left: 50%; top: 50%; width: 4rem; height: 4rem; margin: -2rem 0 0 -2rem; border-radius: 999px; display: grid; place-items: center;
        background: #f5c518; color: #3b2f00; box-shadow: 0 12px 30px -10px rgb(0 0 0 / .7); transition: transform .28s var(--e); }
    .hg-yt-play svg { width: 1.7rem; height: 1.7rem; margin-left: 3px; }
    .hg-yt-btn:hover .hg-yt-play { transform: scale(1.08); }
    .hg-yt-btn:focus-visible { outline: 3px solid #f5c518; outline-offset: -3px; }
    .hg-yt-len { position: absolute; right: .6rem; bottom: .6rem; padding: .15rem .5rem; border-radius: .45rem; font-size: .72rem; font-weight: 800; color: #fff; background: rgb(0 0 0 / .72); }
    .hg-yt iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; opacity: 0; transition: opacity .4s var(--e); }
    .hg-yt.is-on iframe { opacity: 1; }
    .hg-yt.is-on .hg-yt-btn img { opacity: .4; }
    .hg-yt-cap { margin-top: .6rem; }
    .hg-yt-cap b { display: block; font-size: .88rem; font-weight: 800; line-height: 1.35; color: var(--color-gray-900); }
    .hg-yt-cap span { display: block; margin-top: .1rem; font-size: .76rem; font-weight: 700; color: var(--color-gray-500); }
    .hg-yt-cap p { margin-top: .35rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-600); }
    @media (min-width: 900px) {
        .hg-video { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(0, 1fr); column-gap: 1.2rem; align-items: start; }
        .hg-video .hg-h { grid-column: 1 / -1; }
        .hg-yt-cap { margin-top: .8rem; }
        .hg-yt-cap b { font-size: .95rem; }
    }

    /* The steps. */
    .hg-steps { padding: 1rem 1rem 1.1rem; border-radius: 1.25rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .hg-ways { position: relative; display: grid; grid-template-columns: repeat(var(--n), minmax(0, 1fr)); gap: .25rem; margin-top: .75rem; padding: .3rem; border-radius: 1rem; background: var(--color-gray-100); }
    .hg-ways-ind { position: absolute; top: .3rem; bottom: .3rem; left: .3rem; width: calc((100% - .6rem - (var(--n) - 1) * .25rem) / var(--n)); border-radius: .75rem; background: #4a7c2a;
        box-shadow: 0 6px 14px -8px rgb(45 80 22 / .8); transform: translateX(calc(var(--at, 0) * (100% + .25rem))); transition: transform .28s var(--e); }
    .hg-way { position: relative; z-index: 1; min-height: 3rem; padding: .35rem .4rem; border-radius: .75rem; display: grid; align-content: center; justify-items: center; text-align: center; color: var(--color-gray-700); transition: color .28s var(--e); }
    .hg-way b { font-size: .84rem; font-weight: 900; line-height: 1.2; }
    .hg-way small { font-size: .66rem; font-weight: 700; line-height: 1.25; opacity: .8; }
    .hg-way[aria-selected="true"] { color: #fff; }
    .hg-way:focus-visible { outline: 2px solid #f5c518; outline-offset: 1px; }
    .hg-pane { margin-top: .85rem; animation: hgPane .34s var(--e) both; }
    @keyframes hgPane { from { opacity: 0; transform: translateY(6px); } }
    .hg-way-head b { display: block; font-size: .95rem; font-weight: 900; color: var(--color-gray-900); }
    .hg-way-head span { display: inline-block; margin-top: .1rem; font-size: .8rem; font-weight: 700; color: #3d6823; }
    .hg-way-head p { margin-top: .35rem; font-size: .8rem; line-height: 1.5; color: var(--color-gray-500); }

    /* The yellow door into the Protocol Builder. */
    .hg-port { margin-top: .85rem; display: grid; gap: .35rem; }
    .hg-port-btn { width: 100%; min-height: 3.1rem; display: flex; align-items: center; justify-content: center; gap: .55rem; padding: .7rem 1rem; border-radius: 1rem;
        font-size: .95rem; font-weight: 900; color: #3b2f00; background: #f5c518; box-shadow: 0 10px 24px -14px rgb(180 140 0 / .95);
        transition: background-color .28s var(--e), transform .28s var(--e), box-shadow .28s var(--e); }
    .hg-port-btn:hover { background: #ffd23f; transform: translateY(-1px); box-shadow: 0 14px 28px -14px rgb(180 140 0 / 1); }
    .hg-port-btn:active { transform: scale(.98); }
    .hg-port-btn:focus-visible { outline: 3px solid #3b2f00; outline-offset: 2px; }
    .hg-port-btn > svg { flex: none; width: 1.3rem; height: 1.3rem; }
    .hg-port-btn i { display: inline-flex; animation: hgNudge 2.4s var(--e) infinite; }
    .hg-port-btn i svg { width: 1rem; height: 1rem; }
    @keyframes hgNudge { 0%, 60%, 100% { transform: none; } 30% { transform: translateX(4px); } }
    .hg-port small { font-size: .74rem; line-height: 1.45; text-align: center; color: var(--color-gray-500); }
    @media (min-width: 720px) { .hg-port { grid-template-columns: auto 1fr; align-items: center; gap: .9rem; } .hg-port-btn { width: auto; } .hg-port small { text-align: left; } }

    .hg-line { margin-top: 1rem; display: grid; gap: 1rem; }
    .hg-phase-h { display: flex; align-items: center; gap: .5rem; }
    .hg-phase-ic { flex: none; width: 2.1rem; height: 2.1rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: var(--ph, #4a7c2a); box-shadow: 0 0 0 4px var(--ph-soft, #e9f2de); }
    .hg-phase-ic svg { width: 1.05rem; height: 1.05rem; }
    .hg-phase-h b { font-size: .95rem; font-weight: 900; color: var(--color-gray-900); }
    .hg-phase-h i { font-style: normal; font-size: .76rem; font-weight: 700; color: var(--color-gray-500); }
    .hg-phase-h em { margin-left: auto; font-style: normal; font-size: .7rem; font-weight: 800; padding: .15rem .5rem; border-radius: 999px; color: var(--color-gray-600); background: var(--color-gray-100); }
    .hg-phase.is-prep { --ph: #8a6a2f; --ph-soft: #f3eadb; }
    .hg-phase.is-nursery { --ph: #2f8a6a; --ph-soft: #dcf2ea; }
    .hg-phase.is-planting { --ph: #4a7c2a; --ph-soft: #e9f2de; }
    .hg-phase.is-care { --ph: #2b6cb0; --ph-soft: #e1ecf8; }
    .hg-phase.is-harvest { --ph: #b7791f; --ph-soft: #fbeed5; }
    .hg-phase > ol { position: relative; margin: .35rem 0 0 1.05rem; padding-left: 1.15rem; border-left: 2px solid var(--ph-soft, #e9f2de); display: grid; gap: .55rem; }
    .hg-step { position: relative; padding: .7rem .8rem .75rem; border-radius: .95rem; background: var(--color-gray-50, #f9fafb); border: 1px solid var(--color-gray-100);
        transition: opacity .45s var(--e), transform .45s var(--e), border-color .28s var(--e); }
    .hg-step:hover { border-color: var(--ph-soft, #e9f2de); }
    .hg-io .hg-step:not(.is-seen) { opacity: 0; transform: translateY(10px); }
    .hg-dot { position: absolute; left: calc(-1.15rem - 7px); top: 1rem; width: 12px; height: 12px; border-radius: 999px; background: var(--color-white); border: 3px solid var(--ph, #4a7c2a); }
    .hg-step-top { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; }
    .hg-when { font-size: .72rem; font-weight: 900; padding: .16rem .55rem; border-radius: 999px; color: #fff; background: var(--ph, #4a7c2a); font-variant-numeric: tabular-nums; }
    .hg-kind { font-size: .68rem; font-weight: 800; color: var(--color-gray-500); text-transform: uppercase; letter-spacing: .05em; }
    .hg-step h4 { margin-top: .4rem; font-size: .93rem; font-weight: 900; line-height: 1.3; color: var(--color-gray-900); }
    .hg-step p { margin-top: .2rem; font-size: .86rem; line-height: 1.58; color: var(--color-gray-700); }
    .hg-tip { display: flex; gap: .4rem; align-items: flex-start; margin-top: .5rem !important; padding: .45rem .55rem; border-radius: .7rem; background: #fffbea; color: #6b4a00 !important; font-size: .8rem !important; }
    .hg-tip svg { flex: none; width: .95rem; height: .95rem; margin-top: .15rem; color: #b7791f; }

    /* A step done in parts: numbered, folded until tapped (max height, measured by the script). */
    .hg-parts { margin-top: .55rem; border-radius: .85rem; background: var(--color-white); border: 1px solid var(--ph-soft, #e9f2de); }
    .hg-parts-btn { width: 100%; min-height: 2.6rem; display: flex; align-items: center; gap: .5rem; padding: .4rem .65rem; border-radius: .85rem; text-align: left;
        font-size: .8rem; font-weight: 900; color: var(--color-gray-800); transition: background-color .28s var(--e); }
    .hg-parts-btn:hover { background: var(--color-gray-50, #f9fafb); }
    .hg-parts-btn:focus-visible { outline: 2px solid #f5c518; outline-offset: 1px; }
    .hg-parts-btn > span:nth-child(2) { flex: 1; }
    .hg-parts-n { flex: none; min-width: 1.5rem; height: 1.5rem; padding: 0 .35rem; border-radius: 999px; display: grid; place-items: center; font-size: .72rem; font-weight: 900; color: #fff; background: var(--ph, #4a7c2a); }
    .hg-parts-btn svg { flex: none; width: .95rem; height: .95rem; color: var(--color-gray-500); transition: transform .28s var(--e); }
    .hg-parts.is-open .hg-parts-btn svg { transform: rotate(180deg); }
    .hg-parts-fold { max-height: 0; overflow: hidden; opacity: 0; visibility: hidden; transition: max-height .28s var(--e), opacity .28s var(--e), visibility 0s linear .28s; }
    .hg-parts.is-open .hg-parts-fold { max-height: none; opacity: 1; visibility: visible; transition-delay: 0s; }
    .hg-parts ol { display: grid; gap: .55rem; padding: .15rem .7rem .75rem; }
    .hg-parts li { display: flex; gap: .55rem; align-items: flex-start; }
    .hg-parts li > i { flex: none; width: 1.4rem; height: 1.4rem; margin-top: .05rem; border-radius: .45rem; display: grid; place-items: center; font-style: normal; font-size: .7rem; font-weight: 900; color: var(--ph, #4a7c2a); background: var(--ph-soft, #e9f2de); }
    .hg-parts li b { font-size: .84rem; font-weight: 900; color: var(--color-gray-900); }
    .hg-parts li p { margin-top: .12rem !important; font-size: .82rem !important; line-height: 1.55; }
    .hg-time { display: inline-flex; align-items: center; gap: .2rem; margin-left: .4rem; padding: .08rem .45rem; border-radius: 999px; font-size: .66rem; font-weight: 800; vertical-align: .08em;
        color: #6b4a00; background: #fff3c4; white-space: nowrap; }
    .hg-time svg { width: .7rem; height: .7rem; }
    .hg-ptip { color: #6b4a00 !important; font-style: italic; }

    /* The details. */
    .hg-details, .hg-mist, .hg-better { padding: 1rem; border-radius: 1.25rem; background: var(--color-white); border: 1px solid var(--color-gray-200); }
    .hg-dgrid { display: grid; gap: .6rem; margin-top: .7rem; }
    @media (min-width: 720px) { .hg-dgrid { grid-template-columns: 1fr 1fr; } }
    .hg-d { padding: .8rem .85rem; border-radius: .95rem; background: var(--color-gray-50, #f9fafb); border: 1px solid var(--color-gray-100); }
    .hg-d-ic { display: inline-grid; place-items: center; width: 1.8rem; height: 1.8rem; border-radius: .6rem; color: #3d6823; background: #e9f2de; }
    .hg-d-ic svg { width: .95rem; height: .95rem; }
    .hg-d h4 { margin-top: .35rem; font-size: .88rem; font-weight: 900; color: var(--color-gray-900); }
    .hg-d p { margin-top: .2rem; font-size: .84rem; line-height: 1.58; color: var(--color-gray-700); }

    .hg-mist ul, .hg-better ul { margin-top: .65rem; display: grid; gap: .5rem; }
    .hg-mist li { display: flex; gap: .55rem; align-items: flex-start; font-size: .86rem; line-height: 1.55; color: var(--color-gray-700); }
    .hg-mist li > span { flex: none; width: 1.45rem; height: 1.45rem; border-radius: 999px; display: grid; place-items: center; color: #9b1c1c; background: #fde2e2; margin-top: .05rem; }
    .hg-mist li > span svg { width: .7rem; height: .7rem; }

    .hg-better { background: linear-gradient(180deg, #f3faea, var(--color-white) 55%); border-color: #cfe5b6; }
    .hg-better-lead { margin-top: .2rem; font-size: .82rem; color: var(--color-gray-500); }
    .hg-better li { display: flex; gap: .6rem; align-items: flex-start; }
    .hg-ok { flex: none; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: #4a7c2a; margin-top: .05rem; }
    .hg-ok svg { width: .8rem; height: .8rem; }
    .hg-better li p { font-size: .88rem; line-height: 1.55; color: var(--color-gray-800); }
    .hg-tools { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .4rem; }
    .hg-tool { display: inline-flex; align-items: center; gap: .3rem; min-height: 2rem; padding: .25rem .65rem; border-radius: 999px; font-size: .74rem; font-weight: 800;
        color: #2d5016; background: #fff; border: 1px solid #cfe5b6; transition: background-color .28s var(--e), border-color .28s var(--e), transform .28s var(--e); }
    .hg-tool:hover { background: #eef6e4; border-color: #9ccb6f; transform: translateY(-1px); }
    .hg-tool svg { width: .72rem; height: .72rem; }
    .hg-tool .hg-lock { width: .78rem; height: .78rem; color: #8a6d00; }
    .hg-port.is-end { margin-top: 1rem; padding-top: .9rem; border-top: 1px dashed #cfe5b6; }

    .htp-empty { margin-top: 1rem; padding: 1.4rem 1.1rem; border-radius: 1.25rem; text-align: center; color: var(--color-gray-500); background: var(--color-white); border: 1.5px dashed var(--color-gray-300, #d1d5db); }

    /* At night. */
    html.dark .hg-warn { background: #2a210a; border-color: #4d3a0d; color: #f3dfa6; }
    html.dark .hg-warn b { color: #fde68a; }
    html.dark .hg-warn-go { color: #fcd34d; }
    html.dark .hg-hero, html.dark .hg-fact, html.dark .hg-video, html.dark .hg-steps, html.dark .hg-details, html.dark .hg-mist, html.dark .htp-empty { background: #151b12; border-color: #2b3a1c; }
    html.dark .hg-better { background: linear-gradient(180deg, #1a2614, #151b12 55%); border-color: #2f4520; }
    html.dark .hg-hero-ic { background: linear-gradient(135deg, #1f2d16, #2b3a1c); }
    html.dark .hg-hero-t small, html.dark .hg-way-head span { color: #a5c97e; }
    html.dark .hg-badges span { background: #22301a; color: #cfe3bd; }
    html.dark .hg-badges .is-vid { background: #3a1616; color: #fca5a5; }
    html.dark .hg-fact-ic, html.dark .hg-d-ic { background: #22301a; color: #b9d99a; }
    html.dark .hg-ways { background: #1c2616; }
    html.dark .hg-step, html.dark .hg-d { background: #1a2215; border-color: #26331b; }
    html.dark .hg-dot { background: #151b12; }
    html.dark .hg-phase-h em { background: #22301a; color: #cfe3bd; }
    html.dark .hg-phase.is-prep { --ph-soft: #3a2f1a; } html.dark .hg-phase.is-nursery { --ph-soft: #173a2e; } html.dark .hg-phase.is-planting { --ph-soft: #2b3a1c; }
    html.dark .hg-phase.is-care { --ph-soft: #1b2e45; } html.dark .hg-phase.is-harvest { --ph-soft: #3d2e12; }
    html.dark .hg-tip { background: #2a2410; color: #f3dfa6 !important; }
    html.dark .hg-mist li > span { background: #3a1616; color: #fca5a5; }
    html.dark .hg-tool { background: #1a2614; border-color: #2f4520; color: #cfe6b8; }
    html.dark .hg-tool:hover { background: #22301a; }
    html.dark .hg-port.is-end { border-top-color: #2f4520; }
    html.dark .hg-ptip { color: #e8cf7f !important; }
    html.dark .hg-time { background: #3a3110; color: #f6dc8a; }
    html.dark .hg-parts-btn:hover { background: #1c2616; }

    @media (prefers-reduced-motion: reduce) {
        .htp-sprout, .htp-hand, .htp-sun, .hg > *, .hg-pane, .hg-port-btn i { animation: none !important; }
        .htp-guide, .hg-step, .hg-ways-ind, .hg-yt-btn img, .hg-yt iframe, .hg-port-btn, .hg-tool, .hg-parts-fold, .hg-parts-btn svg { transition: none !important; }
        .hg-io .hg-step:not(.is-seen) { opacity: 1; transform: none; }
    }
    html.sm-still .htp-sprout, html.sm-still .htp-hand, html.sm-still .htp-sun, html.sm-still .hg > *, html.sm-still .hg-pane, html.sm-still .hg-port-btn i { animation: none !important; }
    html.sm-still .hg-io .hg-step:not(.is-seen) { opacity: 1; transform: none; }
</style>
@endpush

@section('content')
@php
    $htpBoot = ['guide' => url('/app/how-to-plant/guide'), 'port' => route('htp.port'), 'page' => route('htp.page')];
@endphp
<script type="application/json" id="htpBoot">@json($htpBoot)</script>
<div class="htp" id="htp">
    <section class="htp-top">
        <div class="htp-band">
            <div class="htp-art" aria-hidden="true">
                <svg viewBox="0 0 90 84">
                    <circle class="htp-sun" cx="72" cy="14" r="8" fill="#ffe27a"/>
                    <path d="M2 64h86v20H2z" fill="#6b4a1f"/>
                    <path d="M2 64c8-5 14-5 22 0s14 5 22 0 14-5 22 0 12 4 20 0v4H2z" fill="#86602c"/>
                    <g class="htp-sprout"><path d="M14 64V51" stroke="#bfe58f" stroke-width="2.6" stroke-linecap="round"/><path d="M14 55c-6 0-9-4-9-9 6 0 9 4 9 9zM14 53c5-1 8-5 8-10-6 1-8 5-8 10z" fill="#9fd36a"/></g>
                    <g class="htp-sprout is-2"><path d="M36 64V49" stroke="#bfe58f" stroke-width="2.6" stroke-linecap="round"/><path d="M36 54c-7 0-10-5-10-10 7 0 10 5 10 10zM36 51c6-1 9-6 9-11-7 1-9 6-9 11z" fill="#9fd36a"/></g>
                    <g class="htp-sprout is-3"><path d="M58 64V54" stroke="#bfe58f" stroke-width="2.4" stroke-linecap="round"/><path d="M58 57c-5 0-7-3-7-7 5 0 7 3 7 7zM58 56c4-1 6-4 6-8-5 1-6 4-6 8z" fill="#9fd36a"/></g>
                    <g class="htp-hand"><path d="M70 40c4 0 8 3 9 8l2 8h-8l-4-6c-2-3-1-10 1-10z" fill="#e9b98b"/><path d="M71 52c0 4-1 7-3 9" stroke="#bfe58f" stroke-width="2.2" stroke-linecap="round"/><path d="M68 58c-3 0-5-2-5-5 3 0 5 2 5 5z" fill="#9fd36a"/></g>
                    <path d="M8 76h74" stroke="#f5c518" stroke-width="2" stroke-linecap="round" stroke-dasharray="2 6"/>
                </svg>
            </div>
            <div>
                <h2>How to Plant{{ \App\Support\Tagalog::flag('how-to-plant') }}</h2>
                <p>How traditional farmers here plant each crop, step by step, from getting the field ready to the harvest. A starting point you can make better.</p>
            </div>
        </div>
        <div class="htp-pick">
            <span>Which crop?</span>
            @if ($crops)
                @include('public.site.partials.crop-pick', ['id' => 'htpCropSheet', 'crops' => $crops, 'current' => $pick, 'title' => 'Which crop?', 'noun' => null])
            @endif
        </div>
    </section>

    <div class="htp-guide" id="htpGuide" aria-live="polite">
        @if ($guide)
            @include('how-to-plant.partials.guide', ['guide' => $guide, 'way' => $way])
        @else
            <p class="htp-empty">The guides are on their way. Please check again soon.</p>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const B = JSON.parse($('htpBoot').textContent);
    const box = $('htpGuide');
    const still = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const toast = (m, t) => (window.toast ? window.toast(m, t) : alert(m));
    const wait = (ms) => new Promise((r) => setTimeout(r, ms));

    /* The address keeps the crop and the way, so a reload or a shared link opens the same guide. */
    function remember(crop, way) {
        try {
            const u = new URL(location.href);
            u.searchParams.set('crop', crop);
            if (way) u.searchParams.set('way', way); else u.searchParams.delete('way');
            history.replaceState(history.state, '', u);
        } catch (_) { /* an old browser: the address stays */ }
    }

    /* Steps rise as they come into view (only once, and never when motion is off). */
    let io = null;
    function reveal(root) {
        if (still() || !('IntersectionObserver' in window)) return;
        root.classList.add('hg-io');
        io = io || new IntersectionObserver((list) => {
            list.forEach((en) => { if (en.isIntersecting) { en.target.classList.add('is-seen'); io.unobserve(en.target); } });
        }, { rootMargin: '0px 0px -8% 0px' });
        root.querySelectorAll('.hg-step:not(.is-seen)').forEach((s) => io.observe(s));
    }

    /* The way tabs: the green slides under the picked way, its steps fade in. */
    function placeInd(g) {
        const bar = g.querySelector('.hg-ways');
        if (!bar) return;
        const tabs = [...bar.querySelectorAll('.hg-way')];
        const at = Math.max(0, tabs.findIndex((t) => t.getAttribute('aria-selected') === 'true'));
        bar.style.setProperty('--at', at);
    }
    function pickWay(g, key) {
        g.dataset.way = key;
        g.querySelectorAll('.hg-way').forEach((t) => t.setAttribute('aria-selected', String(t.dataset.way === key)));
        g.querySelectorAll('.hg-pane').forEach((p) => {
            const on = p.dataset.way === key;
            if (on && p.hidden) { p.hidden = false; p.style.animation = 'none'; void p.offsetWidth; p.style.animation = ''; reveal(p); }
            else if (!on) p.hidden = true;
        });
        g.querySelectorAll('.hg-port.is-end [data-port]').forEach((b) => { b.dataset.way = key; });
        placeInd(g);
        remember(g.dataset.crop, key);
    }

    /* The video: the picture until a tap, then the player from youtube-nocookie.com. */
    function play(holder) {
        if (holder.classList.contains('is-on')) return;
        const id = holder.dataset.yt;
        if (!/^[A-Za-z0-9_-]{11}$/.test(id)) return;
        const f = document.createElement('iframe');
        f.src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0&modestbranding=1&playsinline=1';
        f.title = holder.querySelector('.hg-yt-btn')?.getAttribute('aria-label') || 'Video';
        f.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
        f.allowFullscreen = true;
        f.referrerPolicy = 'strict-origin-when-cross-origin';
        f.addEventListener('load', () => holder.classList.add('is-on'), { once: true });
        holder.appendChild(f);
        setTimeout(() => holder.classList.add('is-on'), 1200);
        holder.querySelector('.hg-yt-btn')?.setAttribute('tabindex', '-1');
    }

    function boot(g) {
        if (!g) return;
        placeInd(g);
        reveal(g);
    }
    boot(box.querySelector('.hg'));

    /* Another crop: the guide fades out, the new one is drawn in its place. */
    let asking = 0;
    document.addEventListener('croppick', async (e) => {
        if (!e.target.closest('.htp-pick')) return;
        const crop = e.detail?.crop;
        if (!crop) return;
        const ticket = ++asking;
        box.classList.add('is-busy');
        if (!still()) box.classList.add('is-out');
        try {
            const [res] = await Promise.all([window.api(B.guide + '/' + encodeURIComponent(crop), { method: 'GET' }), still() ? null : wait(220)]);
            if (ticket !== asking) return;
            box.innerHTML = res.data.html;
            remember(res.data.crop, res.data.way);
            const top = box.getBoundingClientRect().top + window.scrollY - (parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--app-head')) || 64) - 12;
            if (window.scrollY > top) window.scrollTo({ top, behavior: 'auto' });
            boot(box.querySelector('.hg'));
        } catch (err) {
            if (!err.tierLock) toast(err.offline ? 'No connection. Try again.' : (err.message || 'Could not connect. Please try again.'), 'error');
        } finally {
            if (ticket === asking) { box.classList.remove('is-out', 'is-busy'); }
        }
    });

    /* A step's parts fold open and shut by their measured height (a class alone would snap). */
    function fold(wrap) {
        const body = wrap.querySelector('.hg-parts-fold');
        const btn = wrap.querySelector('.hg-parts-btn');
        const open = !wrap.classList.contains('is-open');
        btn.setAttribute('aria-expanded', String(open));
        if (still()) { wrap.classList.toggle('is-open', open); body.style.maxHeight = ''; return; }
        clearTimeout(body._t);
        if (open) {
            body.style.maxHeight = '0px';
            wrap.classList.add('is-open');
            void body.offsetHeight;
            body.style.maxHeight = body.scrollHeight + 'px';
            body._t = setTimeout(() => { body.style.maxHeight = ''; }, 320);
        } else {
            body.style.maxHeight = body.scrollHeight + 'px';
            void body.offsetHeight;
            wrap.classList.remove('is-open');
            body.style.maxHeight = '0px';
            body._t = setTimeout(() => { body.style.maxHeight = ''; }, 320);
        }
    }

    box.addEventListener('click', async (e) => {
        const parts = e.target.closest('.hg-parts-btn');
        if (parts) { fold(parts.closest('.hg-parts')); return; }
        const tab = e.target.closest('.hg-way');
        if (tab) { pickWay(tab.closest('.hg'), tab.dataset.way); return; }
        const yt = e.target.closest('.hg-yt-btn');
        if (yt) { play(yt.closest('.hg-yt')); return; }
        const jump = e.target.closest('[data-hg-jump]');
        if (jump) {
            const t = box.querySelector('#hgBetter');
            if (t) { e.preventDefault(); t.scrollIntoView({ behavior: still() ? 'auto' : 'smooth', block: 'start' }); }
            return;
        }
        const port = e.target.closest('[data-port]');
        if (port) {
            const btn = port;
            if (btn.disabled) return;
            btn.disabled = true;
            const veil = window.screenLoader ? window.screenLoader('Making your protocol') : null;
            try {
                const res = await window.api(B.port, { method: 'POST', body: { crop: btn.dataset.crop, way: btn.dataset.way } });
                toast(res.message || 'A new protocol was made.');
                location.href = res.data.url;
            } catch (err) {
                veil?.hide?.();
                if (!err.tierLock) toast(err.offline ? 'No connection. Try again.' : (err.message || 'Could not connect. Please try again.'), 'error');
                btn.disabled = false;
            }
        }
    });
    /* Arrow keys move between the way tabs. */
    box.addEventListener('keydown', (e) => {
        const tab = e.target.closest('.hg-way');
        if (!tab || !['ArrowLeft', 'ArrowRight'].includes(e.key)) return;
        const tabs = [...tab.parentElement.querySelectorAll('.hg-way')];
        const next = tabs[(tabs.indexOf(tab) + (e.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length];
        next.focus();
        pickWay(tab.closest('.hg'), next.dataset.way);
    });
})();
</script>
@endpush
