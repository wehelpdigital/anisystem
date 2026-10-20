{{-- The in-app Variety Finder (2026-10-14): every variety of rice and of
     every other crop, one crop at a time. The crop is a tag (the crop sheet
     of partials.crop-pick), and every filter is a tag that opens a sheet of
     its choices (FH.pick), so nothing slides sideways on a phone. A card
     opens the variety in a full screen sheet: its days to harvest against
     the crop's range, its yield against the crop's average as bars, its
     ratings as coloured chips, its grain or fruit, and where and when to
     plant it. The variety catalogue (every crop, every variety) opens in a
     full screen sheet of its own. Never a link to the public variety pages.

     Expects $vBook (App\Support\VarietyFinder::book(), the first crop) and
     $vCrops (VarietyFinder::crops()). Another crop's book comes from
     route('fh.data', 'varieties'). --}}
@php
    $F = \App\Support\FieldCatalogue::class;
    $vKey = $vBook['crop']['key'];
    $vTotal = collect($vCrops)->sum(fn ($c) => $c[4]);
    $byShelf = collect($vCrops)->groupBy(fn ($c) => $c[3], true);
@endphp
@include('field-helpers.partials.kit')
@once
@push('head')
<style>
    /* ---- The finder's tools --------------------------------------------- */
    .fhv-tools { display: grid; gap: .65rem; padding: .85rem; border-radius: 1.3rem; background: #fff; border: 1px solid #e1ecd3; box-shadow: 0 24px 50px -40px rgb(20 33 12 / .6); }
    .fhv-top { display: grid; gap: .6rem; }
    @media (min-width: 768px) { .fhv-top { grid-template-columns: minmax(16rem, 21rem) minmax(0, 1fr); align-items: center; } }
    .fhv-top .cp-tag { margin-top: 0; }
    .fhv-count { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .6rem; margin: 1.1rem .15rem .75rem; font-size: .9rem; color: #4b5563; }
    .fhv-count b { color: #14210c; }
    .fhv-wrap { position: relative; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .fhv-wrap.is-loading { opacity: .35; pointer-events: none; }

    /* ---- A variety's card ----------------------------------------------- */
    .fhv-grid { display: grid; gap: .75rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 17rem), 1fr)); }
    /* "About these yield figures" keeps a gap above the first variety (2026-10-18, the owner). */
    .fhv-wrap > .yn { margin-bottom: 1rem; }
    .fhv-card { position: relative; display: flex; flex-direction: column; gap: .55rem; min-width: 0; padding: .95rem 1rem; border-radius: 1.2rem; text-align: left; color: inherit;
        background: #fff; border: 1px solid #e3ecd8; box-shadow: 0 16px 34px -30px rgb(20 33 12 / .55); cursor: pointer;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .fhv-card:hover { transform: translateY(-3px); border-color: #b9d69a; box-shadow: 0 24px 44px -30px rgb(20 33 12 / .6); }
    .fhv-card:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    .fhv-card.is-hybrid { background: linear-gradient(160deg, #fffaf0, #fff 60%); border-color: #f0e3b4; }
    .fhv-card.is-in { animation: fhIn .34s cubic-bezier(.22,1,.36,1) both; }
    .fhv-ctop { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
    .fhv-name { display: block; font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; color: #14210c; line-height: 1.2; }
    .fhv-trade { display: block; margin-top: .15rem; font-size: .82rem; font-weight: 800; color: #8a6a00; }
    .fhv-local { display: block; margin-top: .1rem; font-size: .78rem; font-weight: 700; color: #6b7f5a; }
    .fhv-year { flex: none; padding: .15rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #3d6823; background: #eef6e4; }
    .fhv-tags { display: flex; flex-wrap: wrap; gap: .3rem; }
    .fhv-tag { padding: .12rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 800; color: #4b5563; background: #f3f5f0; }
    .fhv-tag.is-h { color: #6b4e00; background: #fdeeb2; }
    .fhv-tag.is-g { color: #6b2c86; background: #f1e6f8; }
    .fhv-nums { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem; }
    .fhv-nums > span { display: block; padding: .45rem .4rem; border-radius: .8rem; background: #f7faf3; text-align: center; min-width: 0; }
    .fhv-nums b { display: block; font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: #1f3410; line-height: 1.1; }
    .fhv-nums small { display: block; margin-top: .15rem; font-size: .62rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #7c8d6b; }
    .fhv-nodata { display: block; padding: .6rem .7rem; border-radius: .8rem; font-size: .78rem; font-weight: 700; color: #6b7f5a; background: #f7faf3; }
    .fhv-meter { height: .35rem; border-radius: 999px; background: #eef3e8; overflow: hidden; }
    .fhv-meter i { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #86b556, #4a7c2a); }
    .fhv-cres { display: flex; flex-wrap: wrap; gap: .28rem; }
    .fhv-cres i { font-style: normal; padding: .12rem .42rem; border-radius: .5rem; font-size: .66rem; font-weight: 800; }
    .fhv-cres i.r, .fhv-res li.r { color: #1f5c1a; background: #e1f2d6; }
    .fhv-cres i.m, .fhv-res li.m { color: #6b5600; background: #fbf1c7; }
    .fhv-cres i.s, .fhv-res li.s { color: #9b1c1c; background: #fde4e4; }
    .fhv-more { margin: 1.3rem auto 0; display: flex; align-items: center; justify-content: center; gap: .4rem; width: 100%; max-width: 22rem; min-height: 2.9rem; padding: .7rem 1.3rem; border-radius: 999px;
        font-size: .92rem; font-weight: 800; color: #2d5016; background: #fff; border: 1px solid #cfe1bb; cursor: pointer; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .fhv-more:hover { background: #f4f9ee; }
    .fhv-more[hidden] { display: none; }

    /* ---- A variety, full screen ----------------------------------------- */
    .fhv-in { max-width: 60rem; }
    .fhv-hero { position: relative; overflow: hidden; display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .5rem .9rem; align-items: center; padding: 1.1rem 1.15rem 1.15rem; border-radius: 1.4rem;
        color: #e8f1de; background: radial-gradient(120% 140% at 100% 0%, #4a7c2a 0%, #1d3310 55%, #0f1d08 100%); }
    .fhv-hero::after { content: ''; position: absolute; right: -2.5rem; bottom: -3rem; width: 10rem; height: 10rem; border-radius: 999px; background: rgb(245 197 24 / .1); }
    .fhv-ic { width: 3.6rem; height: 3.6rem; border-radius: 1.1rem; display: grid; place-items: center; font-size: 2rem; background: rgb(255 255 255 / .12); animation: fhBob 4s ease-in-out infinite; }
    @keyframes fhBob { 50% { transform: translateY(-3px) rotate(-2deg); } }
    .fhv-hero small { display: block; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #c9dcb5; }
    .fhv-hero h2 { margin-top: .1rem; font-family: var(--font-heading); font-weight: 800; font-size: clamp(1.45rem, 5.5vw, 2rem); line-height: 1.15; color: #fff; overflow-wrap: anywhere; }
    .fhv-hero p { margin-top: .2rem; font-size: .88rem; line-height: 1.45; color: #d7e6c6; }
    .fhv-chips { grid-column: 1 / -1; position: relative; z-index: 1; display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .35rem; }
    .fhv-chips span { padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #e8f1de; background: rgb(255 255 255 / .12); border: 1px solid rgb(255 255 255 / .16); }
    .fhv-chips .is-h { color: #3b2f00; background: #f5c518; border-color: #f5c518; }
    .fhv-chips .is-g { color: #6b2c86; background: #f1e6f8; border-color: #f1e6f8; }
    .fhv-chips .is-n { color: #1f5c1a; background: #e1f2d6; border-color: #e1f2d6; }
    .fhv-secs { display: grid; gap: .8rem; margin-top: .85rem; }
    @media (min-width: 900px) { .fhv-secs { grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: start; } .fhv-secs > .is-wide { grid-column: 1 / -1; } }
    .fhv-big { display: flex; align-items: baseline; gap: .4rem; }
    .fhv-big b { font-family: var(--font-heading); font-size: 2.3rem; font-weight: 800; line-height: 1; color: #14210c; font-variant-numeric: tabular-nums; }
    .fhv-big span { font-size: .9rem; font-weight: 700; color: #4b5563; }
    .fhv-say { margin-top: .55rem; font-size: .9rem; line-height: 1.5; color: #374151; }
    .fhv-say b { color: #14210c; }
    .fhv-track { position: relative; height: .6rem; margin-top: 1.25rem; border-radius: 999px; background: linear-gradient(90deg, #cfe6b4, #f5e3a1 55%, #f3c9a1); }
    .fhv-track .avg { position: absolute; top: -.35rem; bottom: -.35rem; left: var(--a); width: 2px; margin-left: -1px; border-radius: 2px; background: #6b7280; }
    .fhv-track .me { position: absolute; top: 50%; left: 0; width: 1.2rem; height: 1.2rem; margin-left: -.6rem; border-radius: 999px; background: #2d5016; border: 3px solid #fff;
        box-shadow: 0 4px 10px -3px rgb(20 33 12 / .6); transform: translateY(-50%); transition: left 1s cubic-bezier(.22,1,.36,1) .15s; }
    .is-drawn .fhv-track .me { left: var(--m); }
    .fhv-range-l { display: flex; justify-content: space-between; gap: .5rem; margin-top: .6rem; font-size: .72rem; line-height: 1.3; color: #6b7280; }
    .fhv-range-l span:nth-child(2) { text-align: center; } .fhv-range-l span:last-child { text-align: right; }
    .fhv-range-l b { display: block; font-size: .85rem; color: #14210c; }
    .fhv-bars { display: grid; gap: .65rem; }
    .fhv-brow { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .3rem .6rem; align-items: baseline; }
    .fhv-brow > span { font-size: .8rem; line-height: 1.35; color: #4b5563; }
    .fhv-brow > b { font-size: .92rem; font-weight: 800; color: #14210c; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .fhv-brow > i { grid-column: 1 / -1; height: .7rem; border-radius: 999px; background: #eef3e8; overflow: hidden; }
    .fhv-brow > i > u { display: block; height: 100%; width: var(--w); border-radius: inherit; background: linear-gradient(90deg, #86b556, #4a7c2a); transform-origin: left center; transform: scaleX(0);
        transition: transform 1s cubic-bezier(.22,1,.36,1); }
    .fhv-brow.is-top > i > u { background: linear-gradient(90deg, #f5d76b, #c79e00); }
    .fhv-brow.is-crop > i > u { background: repeating-linear-gradient(135deg, #b8c4ab 0 6px, #c9d3be 6px 12px); }
    .fhv-brow:nth-child(2) > i > u { transition-delay: .1s; } .fhv-brow:nth-child(3) > i > u { transition-delay: .2s; } .fhv-brow:nth-child(n+4) > i > u { transition-delay: .28s; }
    .is-drawn .fhv-brow > i > u { transform: none; }
    .fhv-res { display: flex; flex-wrap: wrap; gap: .4rem; }
    .fhv-res li { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .65rem .35rem .4rem; border-radius: .85rem; font-size: .84rem; font-weight: 800; line-height: 1.25; }
    .fhv-res li b { flex: none; min-width: 1.9rem; padding: .12rem .35rem; border-radius: .5rem; text-align: center; font-size: .7rem; font-weight: 900; background: rgb(255 255 255 / .75); }
    .fhv-res li small { display: block; font-size: .7rem; font-weight: 600; opacity: .85; }
    .fhv-res li { opacity: 0; transform: translateY(6px); transition: opacity .4s cubic-bezier(.22,1,.36,1), transform .4s cubic-bezier(.22,1,.36,1); transition-delay: calc(var(--i, 0) * 45ms + .15s); }
    .is-drawn .fhv-res li { opacity: 1; transform: none; }
    .fhv-tiles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    @media (min-width: 640px) { .fhv-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); } .fhv-secs .fhv-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); } .fhv-secs > .is-wide .fhv-tiles { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .fhv-tile { min-width: 0; padding: .6rem .7rem; border-radius: .9rem; background: #f7faf3; border: 1px solid #e8efe0; }
    .fhv-tile small { display: block; font-size: .64rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #6b7f5a; }
    .fhv-tile.is-trait small { font-size: .74rem; font-weight: 700; letter-spacing: 0; text-transform: none; line-height: 1.3; }
    .fhv-tile b { display: block; margin-top: .1rem; font-size: .98rem; font-weight: 800; color: #14210c; overflow-wrap: anywhere; }
    .fhv-tile span { display: block; font-size: .74rem; line-height: 1.35; color: #4b5563; }
    .fhv-tile i { display: block; height: .3rem; margin-top: .4rem; border-radius: 999px; background: #e3ead9; overflow: hidden; }
    .fhv-tile i u { display: block; height: 100%; width: var(--w); background: #6b9f3d; transform-origin: left; transform: scaleX(0); transition: transform 1s cubic-bezier(.22,1,.36,1) .2s; }
    .is-drawn .fhv-tile i u { transform: none; }
    .fhv-dl { display: grid; gap: .7rem; }
    .fhv-dl > div { display: grid; gap: .3rem; }
    @media (min-width: 480px) { .fhv-dl > div { grid-template-columns: 8.5rem minmax(0, 1fr); gap: .8rem; align-items: baseline; } }
    .fhv-dl dt { font-size: .7rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: #6b7f5a; }
    .fhv-dl dd { font-size: .92rem; line-height: 1.5; color: #1f2937; }
    .fhv-dl dd .fhd-chips li { font-size: .8rem; padding: .25rem .6rem; }
    .fhv-notes { display: grid; gap: .5rem; font-size: .9rem; line-height: 1.55; color: #1f2937; }
    .fhv-notes ul { display: grid; gap: .3rem; }
    /* Where a variety's facts come from (2026-10-21): a line on its card, the list in its sheet. */
    .fhv-src { display: block; margin-top: auto; font-size: .68rem; line-height: 1.35; color: #6b7280; overflow-wrap: anywhere; }
    .fhv-srcs { display: grid; gap: .3rem; padding-left: 1.2rem; list-style: decimal; font-size: .85rem; line-height: 1.45; }
    .fhv-srcs a { color: #2d5016; text-decoration: underline; text-underline-offset: 2px; overflow-wrap: anywhere; }
    html.dark .fhv-src { color: #9aa69a; }
    html.dark .fhv-srcs a { color: #a5c97e; }
    .fhv-notes li { position: relative; padding-left: 1rem; }
    .fhv-notes li::before { content: ''; position: absolute; left: .15rem; top: .6rem; width: .35rem; height: .35rem; border-radius: 999px; background: #6b9f3d; }
    .fhv-notes .fhd-facts { grid-template-columns: minmax(0, 1fr); }
    .fhv-dlg[open] .fhv-hero, .fhv-dlg[open] .fhv-secs > * { animation: fhIn .45s cubic-bezier(.22,1,.36,1) both; }
    .fhv-secs > :nth-child(1) { animation-delay: .06s !important; } .fhv-secs > :nth-child(2) { animation-delay: .1s !important; } .fhv-secs > :nth-child(3) { animation-delay: .14s !important; }
    .fhv-secs > :nth-child(4) { animation-delay: .18s !important; } .fhv-secs > :nth-child(n+5) { animation-delay: .22s !important; }

    /* ---- The variety catalogue ------------------------------------------ */
    .fhvc-shelf { --g: 98; margin-top: 1.1rem; }
    .fhvc-shelf > h3, .fhvc-h { display: flex; align-items: center; gap: .45rem; margin: 0 .2rem .55rem; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: hsl(var(--g) 45% 30%); }
    .fhvc-shelf > h3 svg { width: 1rem; height: 1rem; }
    .fhvc-tiles { display: grid; gap: .5rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    @media (min-width: 640px) { .fhvc-tiles { grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr)); } }
    .fhvc-tile { display: flex; align-items: center; gap: .55rem; min-width: 0; padding: .55rem .6rem; border-radius: 1rem; text-align: left; color: #14210c; background: #fff; border: 1px solid #e5ebdf; cursor: pointer;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .fhvc-tile:hover { transform: translateY(-2px); border-color: hsl(var(--g) 40% 72%); box-shadow: 0 14px 28px -24px rgb(20 33 12 / .6); }
    .fhvc-tile[hidden] { display: none; }
    .fhvc-tile .ic { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .75rem; display: grid; place-items: center; font-size: 1.25rem; background: hsl(var(--g) 45% 94%); }
    .fhvc-tile .t { flex: 1; min-width: 0; }
    .fhvc-tile b { display: block; font-size: .9rem; font-weight: 800; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fhvc-tile small { display: block; font-size: .72rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fhvc-tile .n { flex: none; font-size: .7rem; font-weight: 800; padding: .15rem .45rem; border-radius: 999px; color: #3d6823; background: #eef5e6; }
    .fhvc-rows { display: grid; gap: .4rem; }
    .fhvc-row { display: flex; align-items: center; gap: .6rem; width: 100%; min-width: 0; padding: .6rem .7rem; border-radius: .95rem; text-align: left; color: #14210c; background: #fff; border: 1px solid #e5ebdf; cursor: pointer;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .fhvc-row:hover { border-color: #a8cc7e; background: #fbfdf8; }
    .fhvc-row[hidden] { display: none; }
    .fhvc-row .ic { flex: none; font-size: 1.2rem; }
    .fhvc-row .t { flex: 1; min-width: 0; }
    .fhvc-row b { display: block; font-size: .92rem; font-weight: 800; line-height: 1.25; }
    .fhvc-row small { display: block; font-size: .74rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fhvc-row .n { flex: none; display: grid; text-align: right; font-size: .72rem; color: #4b5563; font-variant-numeric: tabular-nums; }
    .fhvc-row .n b { font-size: .86rem; }
    .fhvc-row > svg { flex: none; width: 1rem; height: 1rem; color: #86b556; }
    .fhvc-head { display: flex; align-items: center; gap: .8rem; padding: .9rem 1rem; border-radius: 1.25rem; color: #e8f1de; background: radial-gradient(120% 140% at 100% 0%, #4a7c2a 0%, #1d3310 55%, #0f1d08 100%); }
    .fhvc-head .ic { flex: none; width: 3rem; height: 3rem; border-radius: 1rem; display: grid; place-items: center; font-size: 1.7rem; background: rgb(255 255 255 / .12); }
    .fhvc-head b { display: block; font-family: var(--font-heading); font-size: 1.3rem; font-weight: 800; color: #fff; }
    .fhvc-head small { display: block; font-size: .82rem; color: #c9dcb5; }
    .fhvc-head .t { flex: 1; min-width: 0; }
    .fhvc-use { flex: none; padding: .5rem .8rem; border-radius: 999px; font-size: .78rem; font-weight: 800; color: #3b2f00; background: #f5c518; cursor: pointer; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .fhvc-use:hover { background: #ffd23f; }
    .fhvc-wait { padding: 2rem 1rem; text-align: center; color: #6b7280; }

    @media (prefers-reduced-motion: reduce) {
        .fhv-wrap, .fhv-card, .fhv-more, .fhv-track .me, .fhv-brow > i > u, .fhv-res li, .fhv-tile i u, .fhvc-tile, .fhvc-row, .fhvc-use { transition: none; }
        .fhv-card.is-in, .fhv-ic, .fhv-dlg[open] .fhv-hero, .fhv-dlg[open] .fhv-secs > * { animation: none; }
        .fhv-res li { opacity: 1; transform: none; }
    }

    /* ---- Night ---------------------------------------------------------- */
    html.dark .fhv-tools, html.dark .fhv-card, html.dark .fhv-more, html.dark .fhvc-tile, html.dark .fhvc-row { background: #151b12; border-color: #2b3a1c; box-shadow: none; color: #e8efe1; }
    html.dark .fhv-card.is-hybrid { background: linear-gradient(160deg, #241d10, #151b12 60%); border-color: #5c4a24; }
    html.dark .fhv-name, html.dark .fhv-count b, html.dark .fhv-nums b, html.dark .fhv-big b, html.dark .fhv-say b, html.dark .fhv-range-l b, html.dark .fhv-brow > b, html.dark .fhv-tile b { color: #e8efe1; }
    html.dark .fhv-count, html.dark .fhv-big span, html.dark .fhv-say, html.dark .fhv-brow > span, html.dark .fhv-tile span, html.dark .fhv-range-l, html.dark .fhvc-tile small, html.dark .fhvc-row small,
    html.dark .fhvc-row .n, html.dark .fhv-dl dd, html.dark .fhv-notes, html.dark .fhvc-wait { color: #c9d8b8; }
    html.dark .fhv-nums > span, html.dark .fhv-tag, html.dark .fhv-nodata, html.dark .fhv-tile { background: #1c2616; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .fhv-year { background: #22301a; color: #cfe3b8; }
    html.dark .fhv-meter, html.dark .fhv-brow > i, html.dark .fhv-tile i { background: #22301a; }
    html.dark .fhv-trade { color: #e3c25a; }
    html.dark .fhv-local, html.dark .fhv-nums small, html.dark .fhv-tile small, html.dark .fhv-dl dt { color: #9fb58a; }
    html.dark .fhv-more { color: #cfe3b8; }
    html.dark .fhvc-tile .ic { background: hsl(var(--g) 25% 18%); }
    html.dark .fhvc-tile .n { background: #22301a; color: #cfe3b8; }
    html.dark .fhvc-shelf > h3, html.dark .fhvc-h { color: hsl(var(--g) 45% 70%); }
    html.dark .fhv-res li b { background: rgb(0 0 0 / .2); }
</style>
@endpush
@endonce

<button type="button" class="fh-cat-open" data-fhv-cat>
    <span class="fh-faces" aria-hidden="true"><span>🌾</span><span>🌽</span><span>🍅</span></span>
    <span class="fh-cat-t"><b>Open the variety catalogue</b><small>{{ number_format($vTotal) }} varieties of {{ count($vCrops) }} crops, crop by crop</small></span>
    <span class="fh-cat-go"><span>Open</span><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></span>
</button>

<div class="fhv-tools">
    <div class="fhv-top">
        <div id="fhvCrop">
            @include('public.site.partials.crop-pick', ['id' => 'fhvCropSheet', 'crops' => $vCrops, 'current' => $vKey, 'title' => 'Varieties of which crop?', 'noun' => 'varieties'])
        </div>
        <label class="fhc-search">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
            <span class="sr-only">Search a variety</span>
            <input type="search" data-fhv-q placeholder="Search a name, number or brand" autocomplete="off" enterkeyhint="search">
        </label>
    </div>
    <div class="fh-ftags" data-fhv-filters></div>
</div>
<div class="fhv-count" aria-live="polite"><span data-fhv-count></span><button type="button" class="fhc-clear" data-fhv-clear hidden>Clear the filters</button></div>
<div class="fhv-wrap" data-fhv-wrap>
    {{-- Where the yield figures come from (partials.yield-note, shared). --}}
    @includeIf('partials.yield-note')
    <div class="fhv-grid" data-fhv-grid></div>
    <p class="fhc-none" data-fhv-none hidden>No variety matches all of these. Clear a filter or two.</p>
    <button type="button" class="fhv-more" data-fhv-more hidden>Show more varieties</button>
</div>

{{-- The same note, compact, for the variety sheet's Yield section (copied in by the script). --}}
<template id="fhvYieldNote">@includeIf('partials.yield-note', ['compact' => true])</template>

{{-- One variety, full screen. --}}
<dialog class="fhm fhv-dlg" id="fhvDetail" aria-label="Variety">
    <div class="fhm-bar">
        <div class="fhm-title"><b data-fhv-dt>Variety</b><small data-fhv-ds></small></div>
        <button type="button" class="fhm-x" data-fhm-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
    </div>
    <div class="fhm-panes"><section class="fhm-pane" data-fhv-pane><div class="fhm-in fhv-in" data-fhv-body></div></section></div>
</dialog>

{{-- The variety catalogue: every crop, then its varieties. --}}
<dialog class="fhm" id="fhvCat" aria-label="Variety catalogue">
    <div class="fhm-bar">
        <button type="button" class="fhm-back" aria-label="Back to every crop" hidden><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
        <div class="fhm-title"><b data-fhm-title>Variety catalogue</b><small data-fhm-sub>{{ number_format($vTotal) }} varieties of {{ count($vCrops) }} crops</small></div>
        <button type="button" class="fhm-x" data-fhm-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
    </div>
    <div class="fhm-panes">
        <section class="fhm-pane fhm-list" aria-label="Every crop">
            <div class="fhm-in">
                <div class="fhc-tools is-one">
                    <label class="fhc-search">
                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg>
                        <span class="sr-only">Search a crop or a variety</span>
                        <input type="search" data-fhvc-q placeholder="Search a crop or a variety, like Rc 222 or Lakan" autocomplete="off" enterkeyhint="search">
                    </label>
                </div>
                <div data-fhvc-hits hidden style="margin-top: 1rem">
                    <h3 class="fhvc-h">Varieties that match</h3>
                    <div class="fhvc-rows" data-fhvc-hitrows></div>
                </div>
                <div data-fhvc-shelves>
                    @foreach ($byShelf as $shelf => $list)
                        @php([$sLabel, , $sHue, $sIcon] = $F::SHELVES[$shelf] ?? ['Other crops', '', 98, ''])
                        <section class="fhvc-shelf" style="--g: {{ $sHue }}">
                            <h3><svg fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $sIcon }}"/></svg>{{ $sLabel }}</h3>
                            <div class="fhvc-tiles">
                                @foreach ($list as $k => $c)
                                    <button type="button" class="fhvc-tile" data-crop="{{ $k }}" data-q="{{ mb_strtolower($c[0] . ' ' . $c[1] . ' ' . $k) }}">
                                        <span class="ic" aria-hidden="true">{{ $c[2] }}</span>
                                        <span class="t"><b>{{ $c[0] }}</b>@if ($c[0] !== $c[1])<small>{{ $c[1] }}</small>@endif</span>
                                        <span class="n">{{ $c[4] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
                <p class="fhc-none" data-fhvc-none hidden>No crop or variety by that name. Try another spelling, or its number.</p>
            </div>
        </section>
        <section class="fhm-pane fhm-detail" aria-live="polite"><div class="fhm-in" data-fhvc-crop></div></section>
    </div>
</dialog>

<script type="application/json" id="fhvData">{!! json_encode([
    'book' => $vBook,
    'crops' => collect($vCrops)->map(fn ($c) => [$c[0], $c[1], $c[2], $c[4]]),
    'url' => route('fh.data', 'varieties'),
    'page' => route('fh.page', 'varieties'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@push('scripts')
<script>
(() => {
    /* The finder: the crop tag loads a crop's book, the search box and the
       filter tags hide the cards that do not match and order the rest; 12
       show at first on a phone, 24 on a desk, more on a tap. */
    const el = document.getElementById('fhvData');
    if (!el || !window.FH) return;
    const FH = window.FH, esc = FH.esc, I = FH.icon;
    const D = JSON.parse(el.textContent);
    const $ = (s, r = document) => r.querySelector(s);
    const grid = $('[data-fhv-grid]'), wrap = $('[data-fhv-wrap]'), q = $('[data-fhv-q]'), ftags = $('[data-fhv-filters]'), countOut = $('[data-fhv-count]');
    const none = $('[data-fhv-none]'), more = $('[data-fhv-more]'), clear = $('[data-fhv-clear]'), cropTag = $('#fhvCrop .cp-tag');
    const STEP = matchMedia('(max-width: 639.98px)').matches ? 12 : 24;
    const books = new Map();
    let book = D.book, st = null;
    books.set(book.crop.key, book);
    const fmt = (x, d = 1) => (x === null || x === undefined || x === '') ? '' : String(Number(Number(x).toFixed(d)));
    const plural = (n, one, many) => n + ' ' + (n === 1 ? one : many);

    // A crop's book, from the page or the server (kept once loaded).
    const loadBook = async (k) => {
        if (books.has(k)) return books.get(k);
        const r = await fetch(D.url + '?crop=' + encodeURIComponent(k), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const j = await r.json().catch(() => ({}));
        if (!r.ok || !j.success) throw new Error(j.message || 'Could not load that crop just now.');
        books.set(k, j);
        return j;
    };
    // What the details compare a variety with: its crop's range and average.
    const statsOf = (b) => {
        if (b._s) return b._s;
        const unit = b.crop.unit, its = b.items;
        const ds = its.filter((v) => v.d && (v.du || 'days') === unit).map((v) => v.d);
        const yu = (() => { const c = {}; its.forEach((v) => { if (v.ya !== undefined && v.yu) c[v.yu] = (c[v.yu] || 0) + 1; }); return Object.keys(c).sort((a, b2) => c[b2] - c[a])[0] || 't/ha'; })();
        const ys = its.filter((v) => v.ya !== undefined && v.yu === yu).map((v) => v.ya);
        const avg = (a) => a.length ? a.reduce((s, x) => s + x, 0) / a.length : null;
        b._s = { unit, dMin: ds.length ? Math.min(...ds) : null, dMax: ds.length ? Math.max(...ds) : null, dAvg: avg(ds), dN: ds.length,
            yu, yAvg: avg(ys), yBest: ys.length ? Math.max(...ys, ...its.filter((v) => v.ym !== undefined && v.yu === yu).map((v) => v.ym)) : null, yN: ys.length };
        return b._s;
    };

    /* ---- The finder ---------------------------------------------------- */
    const fresh = () => ({ q: '', f: {}, sort: 'year', shown: STEP });
    const filterVal = (f) => f.key === 'sort' ? st.sort : (st.f[f.key] ?? (f.multi ? [] : ''));
    const tagText = (f) => {
        const v = filterVal(f);
        if (f.key === 'sort') return (f.opts.find((o) => o.k === v) || f.opts[0]).l;
        if (f.multi) return v.length ? v.map((k) => (f.opts.find((o) => o.k === k) || {}).l).join(', ') : f.all;
        return v ? ((f.opts.find((o) => o.k === v) || {}).l || f.all) : f.all;
    };
    const drawTags = () => {
        ftags.innerHTML = book.filters.map((f) => {
            const v = filterVal(f), set = f.key === 'sort' ? v !== 'year' : (f.multi ? v.length > 0 : !!v);
            return '<button type="button" class="fh-ftag' + (set ? ' is-set' : '') + '" data-f="' + esc(f.key) + '" aria-haspopup="dialog"><span><small>' + esc(f.label) + '</small><b>' + esc(tagText(f)) + '</b></span><i aria-hidden="true">' + I.chev + '</i></button>';
        }).join('');
    };
    const matches = (v, t, tc) => {
        if (t && !(v.q.includes(t) || v.q.includes(tc))) return false;
        for (const f of book.filters) {
            if (f.key === 'sort') continue;
            const val = filterVal(f);
            if (!val || (f.multi && !val.length)) continue;
            if (f.key === 'env' && !(v.env || []).includes(val)) return false;
            if (f.key === 'res' && !val.every((k) => (v.ok || []).includes(k))) return false;
            if (f.key === 'type' && !(val === 'glutinous' && book.crop.key === 'rice' ? v.gl : v.chip === val)) return false;
            if (f.key === 'dom' && !(v.dom || []).includes(val)) return false;
            if (f.key === 'days') { const o = f.opts.find((x) => x.k === val); if (!o || !v.d || v.d < o.lo || v.d > o.hi) return false; }
        }
        return true;
    };
    const order = (list) => {
        const by = st.sort, rice = book.crop.key === 'rice';
        return list.map((v, i) => [v, i]).sort(([a, ia], [b, ib]) => {
            if (by === 'yield') return (b.ya ?? -1) - (a.ya ?? -1) || ia - ib;
            if (by === 'days') return (a.d || 1e5) - (b.d || 1e5) || ia - ib;
            if (by === 'name') return a.n.localeCompare(b.n, undefined, { numeric: true });
            return (rice ? (b.d ? 1 : 0) - (a.d ? 1 : 0) : 0) || (b.y || 0) - (a.y || 0) || ia - ib;
        }).map((x) => x[0]);
    };
    const RES_SHORT = { 'Blast': 'Blast', 'Bacterial leaf blight': 'BLB', 'Tungro': 'Tungro', 'Brown planthopper': 'BPH', 'Green leafhopper': 'GLH', 'Stem borer': 'SB', 'Sheath blight': 'ShB' };
    // Tagalog help flags: a rating code's key, and the filters whose sheet title carries one.
    const RES_TL = { HR: 'res-hr', R: 'res-r', MR: 'res-mr', HT: 'res-ht', T: 'res-t', MT: 'res-mt', I: 'res-i', MS: 'res-ms', S: 'res-s', HS: 'res-hs' };
    const FILTER_TL = { days: 'maturity-days', res: 'resistance-rating' };
    // Where a variety's facts come from (2026-10-21, the owner; App\Support\VarietySources::line's twin): the names for a card.
    const srcLine = (src) => {
        const n = [...new Set((src || []).map((s) => s.name))];
        if (!n.length) return '';
        return n.length > 2 ? n.slice(0, 2).join(', ') + ' and ' + (n.length - 2) + ' more' : n.join(' and ');
    };
    const srcList = (src) => '<ol class="fhv-srcs">' + (src || []).map((s) => '<li>' + (s.url ? '<a href="' + esc(s.url) + '" target="_blank" rel="noopener nofollow">' + esc(s.label || s.name) + '</a>' : esc(s.label || s.name)) + '</li>').join('') + '</ol>';
    const kindTl = (k) => (/hybrid/i.test(k || '') ? 'hybrid' : (/^inbred$/i.test(String(k || '').trim()) ? 'inbred' : ''));
    // `lead`: the first card of the list, the one whose labels and rating chips carry the flags (once each).
    const card = (v, lead) => {
        const s = statsOf(book), rice = book.crop.key === 'rice';
        const flag = (k, seen) => (lead ? FH.tlAttr(k, seen) : ''), rated = new Set();
        const alias = (v.al || []).slice(0, 2).join(', ');
        const unit = (v.du || s.unit) === 'months' ? 'months' : 'days';
        const nums = (v.d || v.ya !== undefined)
            ? '<span class="fhv-nums"><span><b>' + (v.d || 'n/a') + '</b><small' + flag('maturity-days') + '>' + unit + '</small></span>'
                + '<span><b>' + (v.ya !== undefined ? fmt(v.ya, v.yu === 't/ha' ? 1 : 0) : 'n/a') + '</b><small' + flag(rice ? 't-ha-average' : (v.yu === 't/ha' ? 't-ha' : (v.yu ? '' : 'yield'))) + '>' + esc(rice ? 't/ha avg' : (v.yu || 'yield')) + '</small></span>'
                + (rice ? '<span><b>' + (v.ym !== undefined ? fmt(v.ym) : 'n/a') + '</b><small' + flag('t-ha-top') + '>t/ha top</small></span>'
                    : '<span><b>' + (v.h ? fmt(v.h, 0) : 'n/a') + '</b><small>' + (v.hu === 'm' ? 'm tall' : 'cm tall') + '</small></span>') + '</span>'
                + (v.ya !== undefined && s.yBest && v.yu === s.yu ? '<span class="fhv-meter" aria-hidden="true"><i style="width: ' + Math.min(100, Math.round(v.ya / s.yBest * 100)) + '%"></i></span>' : '')
            : '<span class="fhv-nodata">' + esc(v.yt ? 'Yield: ' + v.yt : (rice ? 'Days to harvest and yield not published yet' : 'Traits and ratings inside')) + '</span>';
        const res = (v.res || []).slice(0, rice ? 7 : 4);
        return '<button type="button" class="fhv-card' + (v.hy ? ' is-hybrid' : '') + '" data-s="' + esc(v.s) + '">'
            + '<span class="fhv-ctop"><span><span class="fhv-name">' + esc(v.n) + '</span>' + (alias ? '<span class="fhv-trade">' + esc(alias) + '</span>' : '') + (v.ln ? '<span class="fhv-local">' + esc(v.ln) + '</span>' : '') + '</span>'
            + '<span class="fhv-year">' + esc(v.y || 'Seller') + '</span></span>'
            + '<span class="fhv-tags">' + (v.k ? '<span class="fhv-tag' + (v.hy ? ' is-h' : '') + '">' + esc(v.k) + '</span>' : '') + (v.gl ? '<span class="fhv-tag is-g">Glutinous</span>' : '')
            + ((v.envL || []).length ? '<span class="fhv-tag">' + esc(v.envL.join(', ')) + '</span>' : ((v.where || []).length ? '<span class="fhv-tag">' + esc(v.where.join(', ')) + '</span>' : ''))
            + (!rice && !v.reg ? '<span class="fhv-tag is-g">Not on the registry</span>' : '') + '</span>'
            + nums
            + (res.length ? '<span class="fhv-cres">' + res.map((r) => '<i class="' + r[3] + '" title="' + esc(r[0] + ': ' + r[2]) + '"' + flag(RES_TL[r[1]], rated) + '>' + esc(RES_SHORT[r[0]] || r[0].slice(0, 16)) + ' ' + esc(r[1]) + '</i>').join('') + '</span>' : '')
            + (srcLine(v.src) ? '<span class="fhv-src">Source: ' + esc(srcLine(v.src)) + '</span>' : '')
            + '</button>';
    };
    let ok = [], booted = false;
    const paint = (animate, from = 0) => {
        const t = st.q.toLowerCase().replace(/\s+/g, ' ').trim(), tc = t.replace(/[\s-]/g, '');
        ok = order(book.items.filter((v) => matches(v, t, tc)));
        const view = ok.slice(from, st.shown);
        const html = view.map((v, n) => card(v, !from && !n)).join('');
        if (from) grid.insertAdjacentHTML('beforeend', html); else grid.innerHTML = html;
        if (animate && !FH.reduce()) [...grid.children].slice(from).forEach((c, i) => { c.style.animationDelay = Math.min(i, 12) * 28 + 'ms'; c.classList.add('is-in'); });
        const total = book.items.length, noun = book.crop.name.toLowerCase();
        countOut.innerHTML = '<b>' + ok.length + '</b> of ' + total + ' ' + esc(noun) + ' ' + (total === 1 ? 'variety' : 'varieties');
        none.hidden = ok.length > 0;
        more.hidden = ok.length <= st.shown;
        more.textContent = 'Show more varieties (' + (ok.length - Math.min(ok.length, st.shown)) + ' more)';
        clear.hidden = !(t || st.sort !== 'year' || Object.values(st.f).some((x) => Array.isArray(x) ? x.length : x));
    };
    const start = (b) => {
        book = b; st = fresh(); q.value = '';
        q.placeholder = b.crop.key === 'rice' ? 'Search a name, number or brand, like 222 or Jackpot' : 'Search a ' + b.crop.name.toLowerCase() + ' variety, brand or company';
        drawTags();
        paint(booted);
        booted = true;
    };
    ftags.addEventListener('click', async (e) => {
        const b = e.target.closest('[data-f]');
        if (!b) return;
        const f = book.filters.find((x) => x.key === b.dataset.f);
        if (!f) return;
        const v = await FH.pick({ title: f.label, note: f.note, multi: !!f.multi, value: filterVal(f), all: f.key === 'sort' ? null : f.all, counts: f.key !== 'sort',
            opts: f.opts.map((o) => ({ k: o.k, l: o.l, n: o.n })), tl: FILTER_TL[f.key] });
        if (v === null) return;
        if (f.key === 'sort') st.sort = v || 'year'; else st.f[f.key] = v;
        st.shown = STEP;
        drawTags();
        FH.bump(ftags.querySelector('[data-f="' + f.key + '"]'));
        paint(true);
    });
    let qt = 0;
    q.addEventListener('input', () => { clearTimeout(qt); qt = setTimeout(() => { st.q = q.value; st.shown = STEP; paint(true); }, 140); });
    more.addEventListener('click', () => { const from = st.shown; st.shown += STEP * 2; paint(true, from); });
    clear.addEventListener('click', () => { const keep = book; start(keep); });
    // Another crop: its book from the server, the cards dimmed while it comes.
    const switchCrop = async (k) => {
        wrap.classList.add('is-loading');
        try {
            const b = await loadBook(k);
            start(b);
            try { history.replaceState(history.state, '', D.page + '?crop=' + encodeURIComponent(k)); } catch (e) {}
        } catch (err) {
            countOut.textContent = err.message;
        } finally { wrap.classList.remove('is-loading'); }
    };
    cropTag?.addEventListener('croppick', (e) => switchCrop(e.detail.crop));
    grid.addEventListener('click', (e) => { const c = e.target.closest('.fhv-card'); if (c) openVariety(book, c.dataset.s); });

    /* ---- One variety, full screen -------------------------------------- */
    const dlg = document.getElementById('fhvDetail'), body = $('[data-fhv-body]', dlg), pane = $('[data-fhv-pane]', dlg);
    // tl: a Tagalog help flag's key for the heading (optional).
    const sec = (h, inner, wide, tl) => '<section class="fhd-sec' + (wide ? ' is-wide' : '') + '"><h3' + FH.tlAttr(tl) + '>' + esc(h) + '</h3>' + inner + '</section>';
    const chipsOf = (list, on) => '<ul class="fhd-chips">' + list.map((x) => '<li' + (on ? ' class="is-on"' : '') + '>' + esc(x) + '</li>').join('') + '</ul>';
    const detail = (b, v) => {
        const s = statsOf(b), c = b.crop, rice = c.key === 'rice';
        const unit = (v.du || s.unit) === 'months' ? 'months' : 'days';
        const out = [];
        // Days to harvest against the crop's range.
        if (v.d) {
            let line = '';
            if (s.dN >= 3 && (v.du || s.unit) === s.unit) {
                const span = Math.max(1, s.dMax - s.dMin), pos = (x) => Math.max(0, Math.min(100, (x - s.dMin) / span * 100));
                const diff = Math.round(v.d - s.dAvg);
                const say = Math.abs(diff) < 1 ? 'About the same as the average ' + c.name.toLowerCase() + ' variety.'
                    : '<b>' + Math.abs(diff) + ' ' + unit + ' ' + (diff < 0 ? 'sooner' : 'later') + '</b> than the average ' + c.name.toLowerCase() + ' variety.';
                line = '<p class="fhv-say">' + say + '</p>'
                    + '<div class="fhv-track" style="--a: ' + pos(s.dAvg).toFixed(1) + '%; --m: ' + pos(v.d).toFixed(1) + '%"><i class="avg"></i><i class="me"></i></div>'
                    + '<div class="fhv-range-l"><span><b>' + s.dMin + '</b>Soonest</span><span><b>' + Math.round(s.dAvg) + '</b>Average</span><span><b>' + s.dMax + '</b>Latest</span></div>';
            }
            out.push(sec(unit === 'months' ? 'Months to harvest' : 'Days to harvest', '<div class="fhv-big"><b data-count="' + v.d + '">' + v.d + '</b><span>' + unit + ' ' + esc(c.harvest.replace(/^(days|months|years)\s+/, '')) + '</span></div>'
                + line + (v.dt ? '<p class="fh-fine" style="margin-top:.5rem">In the trials: ' + esc(v.dt) + '</p>' : ''), false, 'maturity-days'));
        }
        // Yield against the crop's average, as bars.
        if (v.ya !== undefined || v.yt || (v.yx || []).length) {
            const rows = [];
            const max = Math.max(v.ya || 0, v.ym || 0, s.yAvg || 0, ...(v.yx || []).map((x) => x[1])) || 1;
            // The first bar's figure carries the t/ha flag.
            const unitTl = new Set((v.yu || s.yu) === 't/ha' ? [] : ['t-ha']);
            const bar = (label, val, cls) => '<div class="fhv-brow ' + (cls || '') + '"><span>' + esc(label) + '</span><b' + FH.tlAttr('t-ha', unitTl) + '>' + fmt(val, val >= 100 ? 0 : 2) + ' ' + esc(v.yu || s.yu) + '</b><i><u style="--w: ' + Math.max(3, val / max * 100).toFixed(1) + '%"></u></i></div>';
            if (v.ya !== undefined) rows.push(bar(rice ? 'This variety, average' : 'This variety', v.ya));
            if (v.ym !== undefined) rows.push(bar('This variety, at its best', v.ym, 'is-top'));
            (v.yx || []).forEach(([l, val]) => rows.push(bar(l, val, 'is-top')));
            if (s.yAvg && s.yN >= 3 && v.ya !== undefined && v.yu === s.yu) rows.push(bar('Average of ' + s.yN + ' ' + c.name.toLowerCase() + ' varieties', s.yAvg, 'is-crop'));
            let say = '';
            if (v.ya !== undefined && s.yAvg && s.yN >= 3 && v.yu === s.yu) {
                const pct = Math.round((v.ya / s.yAvg - 1) * 100);
                say = '<p class="fhv-say">' + (Math.abs(pct) < 2 ? 'About the average yield of its crop.' : '<b>' + Math.abs(pct) + ' percent ' + (pct > 0 ? 'more' : 'less') + '</b> than the average ' + c.name.toLowerCase() + ' variety in the trials.') + '</p>';
            }
            const note = document.getElementById('fhvYieldNote');
            out.push(sec('Yield', (rows.length ? '<div class="fhv-bars">' + rows.join('') + '</div>' : '') + say + (v.yt ? '<p class="fhv-say">' + esc(v.yt) + '</p>' : '')
                + (note ? note.innerHTML.trim() : ''), false, 'yield'));
        }
        // Pests and diseases, as coloured chips.
        if ((v.res || []).length) {
            const rated = new Set();
            out.push(sec('Pests and diseases', '<ul class="fhv-res">' + v.res.map((r, i) => '<li class="' + r[3] + '" style="--i: ' + i + '"><b' + FH.tlAttr(RES_TL[r[1]], rated) + '>' + esc(r[1]) + '</b><span>' + esc(r[0]) + '<small>' + esc(r[2]) + '</small></span></li>').join('') + '</ul>'
                + '<p class="fh-fine" style="margin-top:.6rem">Green held up in the tests, yellow took a middling share of damage, red took clear damage. A rating can break down when a new strain comes, so keep scouting.</p>', false, 'resistance-rating'));
        }
        // What its registration trials showed (2026-10-18): by season, the advantage over the checks, the stresses, the limits.
        const tr = v.tr;
        if (tr && ((tr.sea || []).length || (tr.adv || []).length || (tr.st || []).length || (tr.lim || []).length || (tr.tra || []).length || tr.tried || tr.fit)) {
            const parts = [];
            if ((tr.sea || []).length) {
                parts.push('<div class="fhd-facts">' + tr.sea.map(([l, y, d]) => '<div><small>' + esc(l) + '</small><b style="font-size:.88rem;font-weight:700">'
                    + esc([y !== null && y !== undefined ? fmt(y, y >= 100 ? 0 : 2) + ' ' + (tr.u || 't/ha') : '', d ? d + ' days' : ''].filter(Boolean).join(', ')) + '</b></div>').join('') + '</div>');
            }
            const lines = [];
            if (tr.best) lines.push('Best test: ' + fmt(tr.best, tr.best >= 100 ? 0 : 2) + ' ' + (tr.u || 't/ha'));
            (tr.adv || []).forEach((x) => lines.push(x));
            if (tr.tried) lines.push('Tried over ' + tr.tried);
            if (tr.fit) lines.push(tr.fit);
            (tr.st || []).forEach(([k, w]) => lines.push(k + ': ' + w.toLowerCase()));
            (tr.tra || []).forEach((x) => lines.push(x));
            if (lines.length) parts.push('<ul>' + lines.map((x) => '<li>' + esc(x.charAt(0).toUpperCase() + x.slice(1)) + '</li>').join('') + '</ul>');
            if ((tr.lim || []).length) parts.push('<p class="fhv-say"><b>Watch:</b> ' + esc(tr.lim.join('; ')) + '</p>');
            if (tr.areas) parts.push('<p class="fh-fine">Recommended for ' + esc(tr.areas) + '</p>');
            if (tr.diff) parts.push('<p class="fh-fine">' + esc(tr.diff) + '</p>');
            out.push(sec('In its trials', '<div class="fhv-notes">' + parts.join('') + (v.tr.src ? '<p class="fh-fine">From its <a href="' + esc(v.tr.src.url) + '" target="_blank" rel="noopener nofollow">' + esc(v.tr.src.name) + '</a>.</p>' : '') + '</div>', true));
        }
        // Grain (rice) and the registry's traits (every other crop).
        if ((v.grain || []).length) {
            out.push(sec('Grain and eating quality', '<div class="fhv-tiles">' + v.grain.map((g) => '<div class="fhv-tile"><small>' + esc(g[0]) + '</small><b>' + esc(g[1]) + '</b>' + (g[2] ? '<span>' + esc(g[2]) + '</span>' : '')
                + (g[3] ? '<i><u style="--w: ' + Math.min(100, g[3]) + '%"></u></i>' : '') + '</div>').join('') + '</div>'));
        }
        if ((v.traits || []).length) {
            const tr = v.traits.slice(0, 9);
            out.push(sec('Traits on record', '<div class="fhv-tiles">' + tr.map((t) => '<div class="fhv-tile is-trait"><small>' + esc(t[0]) + '</small><b>' + esc(t[1]) + '</b></div>').join('') + '</div>'
                + (v.traits.length > tr.length ? '<p class="fh-fine" style="margin-top:.5rem">And ' + plural(v.traits.length - tr.length, 'more trait', 'more traits') + ' on record.</p>' : ''), true));
        }
        // Where and when to plant it.
        const dl = [];
        if ((v.envL || []).length) dl.push(['Kind of farm', chipsOf(v.envL, true)]);
        if ((v.where || []).length) dl.push(['Recommended for', chipsOf(v.where)]);
        if ((v.sea || []).length) dl.push(['Season', chipsOf(v.sea)]);
        if ((v.est || []).length) dl.push(['Planted by', chipsOf(v.est)]);
        if ((v.el || []).length) dl.push(['Elevation', chipsOf(v.el)]);
        if (v.h) dl.push(['Plant height', esc(fmt(v.h, 0) + ' ' + (v.hu || 'cm'))]);
        if (v.uses) dl.push(['Uses', esc(v.uses)]);
        if (v.rem) dl.push(['Remarks', esc(v.rem)]);
        if (dl.length) out.push(sec('Where and when to plant', '<dl class="fhv-dl">' + dl.map(([t, d]) => '<div><dt>' + esc(t) + '</dt><dd>' + d + '</dd></div>').join('') + '</dl>', true));
        // What its seller and later trials say.
        const n = v.notes;
        if (n && (n.popular || (n.facts || []).length || (n.traits || []).length)) {
            out.push(sec(n.claims ? 'What its seller says' : 'Good to know', '<div class="fhv-notes">' + (n.popular ? '<p>' + esc(n.popular) + '</p>' : '')
                + ((n.facts || []).length ? '<div class="fhd-facts">' + n.facts.map(([l, x]) => '<div><small>' + esc(l) + '</small><b style="font-size:.88rem;font-weight:700">' + esc(x) + '</b></div>').join('') + '</div>' : '')
                + ((n.traits || []).length ? '<ul>' + n.traits.map((x) => '<li>' + esc(x) + '</li>').join('') + '</ul>' : '')
                + (n.claims ? '<p class="fh-fine">Claims from the seller, not checked in trials.</p>' : '') + '</div>', true));
        }
        const who = [v.o ? (rice ? 'Bred by ' : 'From ') + v.o : '', v.reg ? 'Registered as ' + v.reg : (rice ? '' : 'Not on the NSIC registry'), v.y ? 'Released ' + v.y : ''].filter(Boolean).join('. ');
        const from = rice ? c.source : (v.reg ? 'anee.io\'s variety records' + (n && n.claims ? ', and its seller' : '') : 'its seller');
        const chips = [v.k ? '<span class="' + (v.hy ? 'is-h' : '') + '"' + FH.tlAttr(kindTl(v.k)) + '>' + esc(v.k) + '</span>' : '', v.gl ? '<span class="is-g">Glutinous</span>' : '', v.nw ? '<span class="is-n">New</span>' : '',
            v.y ? '<span>' + esc(v.y) + '</span>' : '', v.reg ? '<span>' + esc(v.reg) + '</span>' : ''].join('');
        return '<header class="fhv-hero"><span class="fhv-ic" aria-hidden="true">' + esc(c.icon) + '</span><div><small>' + esc(c.name) + ' variety</small><h2>' + esc(v.n) + '</h2>'
            + ((v.ln || (v.al || []).length) ? '<p>' + esc([v.ln, (v.al || []).length ? (v.ln ? 'Sold as ' : 'Also called ') + v.al.slice(0, 3).join(', ') : ''].filter(Boolean).join('. ')) + '</p>' : '') + '</div>'
            + (chips ? '<div class="fhv-chips">' + chips + '</div>' : '') + '</header>'
            + '<div class="fhv-secs">' + out.join('') + ((v.src || []).length ? sec('Sources', srcList(v.src) + '<p class="fh-fine">The days, yields and ratings here are as these sources give them. What a seller says about its own seed is said as the seller\'s.</p>', true) : '') + '</div>'
            + (who ? '<p class="fhd-src" style="margin-top:1rem">' + esc(who) + '.</p>' : '');
    };
    // The big number counts up as the sheet opens.
    const countUp = (root) => {
        if (FH.reduce()) return;
        root.querySelectorAll('[data-count]').forEach((n) => {
            const to = Number(n.dataset.count), t0 = performance.now(), dur = 800;
            const step = (t) => { const k = Math.min(1, (t - t0) / dur), e = 1 - Math.pow(1 - k, 3); n.textContent = String(Math.round(to * e)); if (k < 1) requestAnimationFrame(step); };
            n.textContent = '0';
            requestAnimationFrame(step);
        });
    };
    const openVariety = (b, slug) => {
        const v = b.items.find((x) => x.s === slug);
        if (!v) return;
        body.classList.remove('is-drawn');
        body.innerHTML = detail(b, v);
        $('[data-fhv-dt]', dlg).textContent = v.n;
        $('[data-fhv-ds]', dlg).textContent = b.crop.name + ' variety';
        pane.scrollTop = 0;
        FH.openModal(dlg);
        countUp(body);
        requestAnimationFrame(() => requestAnimationFrame(() => body.classList.add('is-drawn')));
    };

    /* ---- The variety catalogue ----------------------------------------- */
    const cat = document.getElementById('fhvCat');
    const cq = $('[data-fhvc-q]', cat), shelves = $('[data-fhvc-shelves]', cat), hits = $('[data-fhvc-hits]', cat), hitRows = $('[data-fhvc-hitrows]', cat), cnone = $('[data-fhvc-none]', cat);
    const cropPane = $('[data-fhvc-crop]', cat), back = $('.fhm-back', cat), ctitle = $('[data-fhm-title]', cat), csub = $('[data-fhm-sub]', cat);
    const ct0 = ctitle.textContent, cs0 = csub.textContent;
    const listPane = $('.fhm-list', cat), detailPane = $('.fhm-detail', cat);
    let index = null, indexing = null;
    const loadIndex = () => indexing || (indexing = fetch(D.url + '?index=1', { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then((r) => r.json()).then((j) => { index = (j.items || []).map((x) => [x[0], x[1], x[2], x[3], (x[2] + ' ' + x[2].replace(/[\s-]/g, '') + ' ' + x[3] + ' ' + x[3].replace(/[\s-]/g, '') + ' ' + (x[4] || '')).toLowerCase()]); return index; })
        .catch(() => { indexing = null; return null; }));
    const row = (crop, v) => {
        const c = D.crops[crop] || ['', '', '🌱'];
        return '<button type="button" class="fhvc-row" data-crop="' + esc(crop) + '" data-s="' + esc(v.s) + '"><span class="ic" aria-hidden="true">' + esc(c[2]) + '</span>'
            + '<span class="t"><b>' + esc(v.n) + '</b><small>' + esc([v.k, (v.al || []).slice(0, 2).join(', ') || v.ln, v.y].filter(Boolean).join(' · ')) + '</small></span>'
            + (v.d || v.ya !== undefined ? '<span class="n">' + (v.d ? '<b>' + v.d + '</b>' + (v.du === 'months' ? 'months' : 'days') : '') + (v.ya !== undefined ? '<span>' + fmt(v.ya) + ' ' + esc(v.yu || '') + '</span>' : '') + '</span>' : '')
            + I.arrow + '</button>';
    };
    const findCrops = () => {
        const t = cq.value.toLowerCase().trim(), tc = t.replace(/[\s-]/g, '');
        let any = 0;
        shelves.querySelectorAll('.fhvc-shelf').forEach((sh) => {
            let n = 0;
            sh.querySelectorAll('.fhvc-tile').forEach((x) => { const on = !t || x.dataset.q.includes(t); x.hidden = !on; if (on) n++; });
            sh.hidden = !n; any += n;
        });
        let found = [];
        if (t.length >= 2 && index) found = index.filter((x) => x[4].includes(t) || x[4].includes(tc)).slice(0, 40);
        hits.hidden = !found.length;
        hitRows.innerHTML = found.map((x) => row(x[0], { s: x[1], n: x[2], al: x[3] ? [x[3]] : [], k: (D.crops[x[0]] || [])[1] })).join('');
        cnone.hidden = !!(any || found.length);
    };
    // The crops answer at once; the varieties as soon as their names have come.
    let cqt = 0;
    cq.addEventListener('input', () => { clearTimeout(cqt); cqt = setTimeout(() => { findCrops(); if (cq.value.trim().length >= 2 && !index) loadIndex().then(findCrops); }, 140); });
    cq.addEventListener('focus', () => { loadIndex(); }, { once: true });
    const showCrops = () => {
        cat.classList.remove('is-detail'); back.hidden = true; ctitle.textContent = ct0; csub.textContent = cs0;
        listPane.removeAttribute('inert'); detailPane.setAttribute('inert', '');
    };
    cat._back = showCrops;
    back.addEventListener('click', showCrops);
    const showCrop = async (k) => {
        const c = D.crops[k] || ['', k, '🌱', 0];
        ctitle.textContent = c[1]; csub.textContent = ct0; back.hidden = false;
        listPane.setAttribute('inert', ''); detailPane.removeAttribute('inert');
        cropPane.innerHTML = '<p class="fhvc-wait">Opening the ' + esc(c[1].toLowerCase()) + ' varieties…</p>';
        detailPane.scrollTop = 0;
        requestAnimationFrame(() => cat.classList.add('is-detail'));
        try {
            const b = await loadBook(k);
            const its = b.items.slice().sort((x, y) => (y.y || 0) - (x.y || 0));
            cropPane.innerHTML = '<div class="fhvc-head"><span class="ic" aria-hidden="true">' + esc(c[2]) + '</span><span class="t"><b>' + esc(c[1]) + '</b><small>' + plural(its.length, 'variety', 'varieties') + (c[0] !== c[1] ? ', ' + esc(c[0]) : '') + '</small></span>'
                + '<button type="button" class="fhvc-use" data-use="' + esc(k) + '">Use in the finder</button></div>'
                + '<div class="fhc-tools is-one" style="margin-top:.8rem"><label class="fhc-search">' + I.search + '<span class="sr-only">Search</span><input type="search" data-fhvc-cq placeholder="Search a ' + esc(c[1].toLowerCase()) + ' variety" autocomplete="off"></label></div>'
                + '<div class="fhvc-rows" style="margin-top:.8rem">' + its.map((v) => row(k, v)).join('') + '</div>';
        } catch (err) {
            cropPane.innerHTML = '<p class="fhvc-wait">' + esc(err.message) + '</p>';
        }
    };
    cat.addEventListener('input', (e) => {
        if (!e.target.matches('[data-fhvc-cq]')) return;
        const t = e.target.value.toLowerCase().trim(), tc = t.replace(/[\s-]/g, '');
        const b = books.get(cropPane.querySelector('[data-use]')?.dataset.use);
        if (!b) return;
        const want = new Set(b.items.filter((v) => !t || v.q.includes(t) || v.q.includes(tc)).map((v) => v.s));
        cropPane.querySelectorAll('.fhvc-row').forEach((r) => { r.hidden = !want.has(r.dataset.s); });
    });
    cat.addEventListener('click', async (e) => {
        const tile = e.target.closest('.fhvc-tile');
        if (tile) { showCrop(tile.dataset.crop); return; }
        const use = e.target.closest('[data-use]');
        if (use) {
            const k = use.dataset.use;
            await FH.closeModal(cat);
            if (cropTag && window.cropPickSet?.(cropTag, k)) { FH.bump(cropTag); switchCrop(k); }
            return;
        }
        const r = e.target.closest('.fhvc-row');
        if (r) {
            try { openVariety(await loadBook(r.dataset.crop), r.dataset.s); } catch (err) { /* the row stays; the farmer can try again */ }
        }
    });
    detailPane.setAttribute('inert', '');
    document.querySelectorAll('[data-fhv-cat]').forEach((b) => b.addEventListener('click', () => { showCrops(); FH.openModal(cat); }));

    // The crop sheet counts "1 varieties" as its noun less an s: say it right.
    document.querySelectorAll('#fhvCropSheet .cp-n').forEach((n) => { if (/^1 varietie$/.test(n.textContent.trim())) n.textContent = '1 variety'; });
    start(book);
    // A link from an analysis (?q=Name, 2026-10-18): the finder opens on that variety's search.
    try {
        const want = new URLSearchParams(location.search).get('q');
        if (want) { q.value = want.slice(0, 80); st.q = q.value; st.shown = STEP; paint(true); }
    } catch (e) {}
})();
</script>
@endpush
