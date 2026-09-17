{{-- REALIGN BY ANEE — the sheets and the block, shared by the Growth Stages
     module and the activities board's growth-stage sheet (the Tools row
     and a day header's plant pill both open that one).

     The calendar counts days and reads a stage off the count; the plant
     does not always agree. Anee reads the lot's whole history and says
     where the crop actually is; the lot keeps her answer as a shift
     applied to every stage reading. A paid plan's: on Libre the button
     shows, locked, and opens the upgrade sheet.

     Expects: $schedule. One API for the pages:

         window.growthRealign.block({ lotId, lotName, realign })  -> HTML for a lot's card
         window.growthRealign.onApplied = (lotId, realign) => {} -> what a page does after a run

     The wait is the shared one (sm/partials/anee-wait). --}}
@php
    $grxLocked = \App\Support\Tier::forSchedule($schedule) === 'libre';
    $grxPrice = \App\Support\AiPrices::of('realign');
@endphp
@once
@include('sm.partials.anee-wait')

{{-- Before the run: what it costs and what she will do. --}}
<div class="sheet hidden" id="grRealignSheet" style="--sheet-width:30rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Realign by Anee</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="grx-lead">
            <img class="grx-face" src="{{ \App\Models\AiSetting::current()->faceUrl() }}" alt="">
            <div class="min-w-0">
                <p class="grx-lot" id="grxLotName">Lot</p>
                <p class="grx-cal" id="grxCalendar">The calendar says…</p>
            </div>
        </div>
        <p class="grx-what">Anee reads everything this lot has been through since day zero — every activity and what was applied, your notes and tags, the observations, the weather the field had — and says which stage the crop is <b>actually</b> in today, and how many days it runs ahead of or behind the calendar. A herbicide that set it back, a heatwave that stunted it, a hungry field: she weighs all of it.</p>
        <p class="grx-what">Her answer becomes this lot's growth stage everywhere — the board's day headers, the Tools sheet, the Growth Stages module — until you ask her again.</p>
        <div class="grx-prev" id="grxPrev" hidden></div>
        <div class="grx-price">
            <span class="grx-coins" aria-hidden="true">🪙</span>
            <span class="grow"><b>{{ $grxPrice }} credits, flat</b> — one price however long the season, said before anything is spent<span id="grxBalance"></span></span>
        </div>
        <p class="grx-blocked" id="grxBlocked" hidden></p>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-primary w-full" id="grxRun">✨ Realign this lot ({{ $grxPrice }} credits)</button>
    </div>
</div>

{{-- After the run: what she found, laid out to be read in the field. --}}
<div class="sheet hidden" id="grRealignResultSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="grxResultTitle">Anee's reading</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" id="grxResultBody"></div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-primary w-full" data-sheet-close>Got it</button>
    </div>
</div>

<style>
    .grx-lead { display: flex; align-items: center; gap: .75rem; margin-bottom: .8rem; }
    .grx-face { width: 3rem; height: 3rem; border-radius: 999px; object-fit: cover; flex: none; box-shadow: 0 0 0 3px #fff, 0 8px 20px -10px rgb(40 70 15 / .5); }
    .grx-lot { font-family: var(--font-heading); font-size: 1.05rem; font-weight: 800; color: var(--color-gray-900); }
    .grx-cal { font-size: .8rem; color: var(--color-gray-500); margin-top: .1rem; }
    .grx-what { font-size: .84rem; line-height: 1.55; color: var(--color-gray-600); margin-bottom: .6rem; }
    .grx-what b { color: var(--color-gray-900); }
    .grx-prev { margin: .2rem 0 .7rem; padding: .6rem .8rem; border-radius: .85rem; font-size: .78rem; line-height: 1.5;
        background: var(--color-gray-50); border: 1px solid var(--color-gray-200); color: var(--color-gray-600); }
    .grx-prev b { color: var(--color-gray-900); }
    .grx-price { display: flex; align-items: center; gap: .6rem; padding: .7rem .85rem; border-radius: .9rem; font-size: .85rem;
        background: var(--color-brand-50); border: 1px solid var(--color-brand-200); color: var(--color-gray-700); }
    .grx-price b { color: var(--color-gray-900); }
    .grx-coins { font-size: 1.3rem; }
    .grx-blocked { margin-top: .7rem; padding: .6rem .8rem; border-radius: .85rem; font-size: .8rem; font-weight: 600;
        color: #9a1d13; background: #fdf0ee; border: 1px solid #f7d4cf; }

    /* The lot card's block: the button, and the note once she has spoken. */
    .grx-block { margin-top: .8rem; padding-top: .7rem; border-top: 1px dashed var(--color-gray-200); }
    .grx-btn { display: inline-flex; align-items: center; gap: .45rem; padding: .55rem .9rem; border-radius: .85rem; border: 0; cursor: pointer;
        font: inherit; font-size: .82rem; font-weight: 800; color: #fff;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 40%, #3d6823 70%, #6b9f3d); background-size: 220% 100%;
        animation: grxTide 6s ease-in-out infinite alternate; box-shadow: 0 8px 18px -10px rgb(61 104 35 / .6);
        transition: transform .28s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .grx-btn:hover { transform: translateY(-1px); }
    .grx-btn img { width: 1.35rem; height: 1.35rem; border-radius: 999px; object-fit: cover; box-shadow: 0 0 0 2px rgb(255 255 255 / .6); }
    .grx-btn.is-locked { background: var(--color-gray-100); color: var(--color-gray-500); animation: none; box-shadow: none; }
    .grx-btn.is-locked img { filter: grayscale(1); opacity: .7; }
    .grx-btn small { font-weight: 600; opacity: .85; }
    @keyframes grxTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }
    .grx-note { margin-top: .6rem; padding: .65rem .8rem; border-radius: .9rem; font-size: .8rem; line-height: 1.5;
        background: linear-gradient(120deg, #f4faee, #e8f3dc); border: 1px solid var(--color-brand-200); color: var(--color-gray-700); }
    .grx-note-head { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: .25rem; }
    .grx-note-head b { color: var(--color-gray-900); font-size: .82rem; }
    .grx-note-when { font-size: .7rem; color: var(--color-gray-400); font-weight: 600; }
    .grx-note-more { margin-top: .35rem; font-size: .78rem; font-weight: 800; color: var(--color-brand-700); background: none; border: 0; padding: 0; cursor: pointer; }
    .grx-shift { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .55rem; border-radius: 999px; font-size: .7rem; font-weight: 800; }
    .grx-shift.is-behind { background: #fff1e6; color: #b45309; border: 1px solid #fdd7b0; }
    .grx-shift.is-ahead { background: #e6f5ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .grx-shift.is-even { background: #eaf6e1; color: #2f5219; border: 1px solid #cfe3bd; }

    /* The reading, laid out. */
    .grx-hero { border-radius: 1.1rem; padding: 1rem 1.1rem; color: #fff; margin-bottom: .85rem;
        background: linear-gradient(120deg, #3d6823, #6b9f3d 45%, #4a7c2a 75%, #2f5219); background-size: 220% 220%; animation: grxTide 9s ease-in-out infinite alternate; }
    .grx-hero-k { font-size: .66rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; opacity: .85; }
    .grx-hero-stage { font-family: var(--font-heading); font-size: 1.45rem; font-weight: 800; line-height: 1.15; margin-top: .15rem; }
    .grx-hero-line { margin-top: .35rem; font-size: .82rem; opacity: .95; }
    .grx-hero .grx-shift { margin-top: .5rem; background: rgb(255 255 255 / .18); color: #fff; border-color: rgb(255 255 255 / .35); }
    .grx-two { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-bottom: .85rem; }
    .grx-cell { padding: .6rem .75rem; border-radius: .85rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-200); }
    .grx-cell i { display: block; font-style: normal; font-size: .64rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: var(--color-gray-400); }
    .grx-cell b { display: block; font-size: .92rem; color: var(--color-gray-900); margin-top: .1rem; }
    .grx-cell span { display: block; font-size: .72rem; color: var(--color-gray-500); }
    .grx-cell.is-anee { background: var(--color-brand-50); border-color: var(--color-brand-200); }
    .grx-conf { display: flex; align-items: center; gap: .6rem; font-size: .76rem; font-weight: 700; color: var(--color-gray-600); margin-bottom: .85rem; }
    .grx-conf-bar { flex: 1; height: .45rem; border-radius: 999px; background: var(--color-gray-200); overflow: hidden; }
    .grx-conf-bar span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #6b9f3d, #a9d383); width: 0; transition: width .9s var(--ease-house, cubic-bezier(.22,1,.36,1)) .2s; }
    .grx-sum { font-size: .9rem; line-height: 1.55; color: var(--color-gray-800); margin-bottom: .9rem; }
    .grx-card { border-radius: 1rem; border: 1px solid var(--color-gray-200); padding: .8rem .9rem; margin-bottom: .7rem; background: var(--color-white); }
    .grx-card h4 { display: flex; align-items: center; gap: .4rem; font-size: .8rem; font-weight: 800; color: var(--color-gray-900); margin-bottom: .4rem; }
    .grx-card ul { display: flex; flex-direction: column; gap: .35rem; }
    .grx-card li { display: flex; gap: .5rem; font-size: .82rem; line-height: 1.45; color: var(--color-gray-700); }
    .grx-card li .e { flex: none; }
    .grx-card.is-do { border-color: var(--color-brand-200); background: #f7fbf2; }
    .grx-card.is-watch { border-color: #fde4b8; background: #fffaf0; }
    .grx-applied { font-size: .76rem; line-height: 1.5; color: var(--color-gray-500); padding: .6rem .8rem; border-radius: .85rem; background: var(--color-gray-50); border: 1px dashed var(--color-gray-200); }
    html.dark .grx-lot, html.dark .grx-what b, html.dark .grx-price b, html.dark .grx-prev b, html.dark .grx-note-head b, html.dark .grx-cell b, html.dark .grx-card h4, html.dark .grx-sum { color: #e8efe1; }
    html.dark .grx-cal, html.dark .grx-what, html.dark .grx-prev, html.dark .grx-note, html.dark .grx-cell span, html.dark .grx-card li, html.dark .grx-conf, html.dark .grx-applied { color: #b7c2ad; }
    html.dark .grx-prev, html.dark .grx-cell, html.dark .grx-applied { background: #151b12; border-color: #2b3a1c; }
    html.dark .grx-price, html.dark .grx-note, html.dark .grx-cell.is-anee { background: rgb(107 159 61 / .12); border-color: #2f4d24; }
    html.dark .grx-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .grx-card.is-do { background: rgb(107 159 61 / .1); border-color: #2f4d24; }
    html.dark .grx-card.is-watch { background: rgb(180 83 9 / .1); border-color: rgb(251 191 36 / .25); }
    html.dark .grx-btn.is-locked { background: #1c2416; color: #93a58b; }

    /* ---- Where the crop is on its clock: the calendar's day and Anee's on one rail ---- */
    .grx-rail-card { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white); padding: .8rem .9rem; margin-bottom: .85rem; }
    .grx-rail-head { display: flex; align-items: center; justify-content: space-between; gap: .6rem; flex-wrap: wrap; margin-bottom: .6rem; }
    .grx-rail-head h4 { font-size: .8rem; font-weight: 800; color: var(--color-gray-900); display: flex; align-items: center; gap: .4rem; }
    .grx-seg { display: inline-flex; padding: .15rem; border-radius: 999px; background: var(--color-gray-100); }
    .grx-seg button { border: 0; background: transparent; padding: .3rem .7rem; border-radius: 999px; font: inherit; font-size: .72rem; font-weight: 800; color: var(--color-gray-500); cursor: pointer;
        transition: background .28s var(--ease-house, cubic-bezier(.22,1,.36,1)), color .28s, box-shadow .28s; }
    .grx-seg button.is-on { background: var(--color-white); color: var(--color-brand-800); box-shadow: 0 2px 8px -4px rgb(0 0 0 / .35); }
    .grx-seg button.is-on[data-grx-view="calendar"] { color: var(--color-gray-800); }
    /* Anee's marker hangs above the rail, the calendar's below it, so two
       days a week apart never collide. */
    .grx-rail { padding: 1.9rem 0 .2rem; }
    .grx-rail-track { position: relative; display: flex; gap: 3px; height: 1.05rem; }
    .grx-rail-seg { flex: 1 1 0; min-width: 4px; border-radius: 999px; background: var(--color-gray-200); cursor: pointer; border: 0; padding: 0;
        transition: background .35s var(--ease-house, cubic-bezier(.22,1,.36,1)), box-shadow .28s, transform .28s; }
    .grx-rail-seg:first-child { border-radius: 999px 4px 4px 999px; } .grx-rail-seg:last-child { border-radius: 4px 999px 999px 4px; }
    .grx-rail-seg:hover { transform: scaleY(1.15); }
    .grx-rail-seg.is-past { background: #8fbf62; }
    .grx-rail-seg.is-now { background: linear-gradient(90deg, #4a7c2a, #6b9f3d); box-shadow: 0 0 0 3px rgb(107 159 61 / .22); }
    .grx-rail-seg.is-sel { outline: 2px solid #3d6823; outline-offset: 3px; }
    html.dark .grx-rail-seg.is-sel { outline-color: #cfe6b5; }
    .grx-rail-mark { position: absolute; top: -1.75rem; transform: translateX(-50%); display: flex; flex-direction: column; align-items: center; pointer-events: none;
        transition: left .6s var(--ease-house, cubic-bezier(.22,1,.36,1)), opacity .35s, transform .35s; }
    .grx-rail-mark b { font-size: .6rem; font-weight: 800; line-height: 1.15; text-align: center; white-space: nowrap; padding: .12rem .4rem; border-radius: .4rem; }
    .grx-rail-mark i { width: 2px; height: 1.6rem; margin-top: .1rem; display: block; border-radius: 2px; }
    .grx-rail-mark.is-cal { top: auto; bottom: -1.95rem; flex-direction: column-reverse; }
    .grx-rail-mark.is-cal i { margin-top: 0; margin-bottom: .1rem; }
    .grx-rail-mark.is-cal b { background: var(--color-gray-100); color: var(--color-gray-600); }
    .grx-rail-mark.is-cal i { background: var(--color-gray-400); }
    .grx-rail-mark.is-anee b { background: #3d6823; color: #fff; }
    .grx-rail-mark.is-anee i { background: #3d6823; }
    .grx-rail-mark.is-dim { opacity: .45; transform: translateX(-50%) scale(.92); }
    .grx-rail-mark.is-lead { z-index: 2; }
    .grx-rail-mark.is-lead b { box-shadow: 0 4px 12px -6px rgb(0 0 0 / .5); }
    .grx-rail-shift { position: absolute; top: -.35rem; height: 1.75rem; border-radius: .3rem; background: repeating-linear-gradient(135deg, rgb(180 83 9 / .18) 0 4px, transparent 4px 8px); pointer-events: none;
        transition: left .6s var(--ease-house, cubic-bezier(.22,1,.36,1)), width .6s var(--ease-house, cubic-bezier(.22,1,.36,1)); }
    .grx-rail-shift.is-ahead { background: repeating-linear-gradient(135deg, rgb(29 78 216 / .16) 0 4px, transparent 4px 8px); }
    .grx-rail-scale { display: flex; justify-content: space-between; font-size: .62rem; font-weight: 700; color: var(--color-gray-400); margin-top: 2.15rem; }
    .grx-rail-cap { margin-top: .6rem; padding: .6rem .75rem; border-radius: .8rem; background: var(--color-gray-50); border: 1px solid var(--color-gray-200); font-size: .78rem; line-height: 1.5; color: var(--color-gray-600);
        transition: opacity .28s; }
    .grx-rail-cap b { color: var(--color-gray-900); }
    .grx-rail-cap.is-swap { opacity: 0; }
    .grx-rail-cap-t { display: flex; align-items: baseline; gap: .5rem; flex-wrap: wrap; margin-bottom: .15rem; }
    .grx-rail-cap-t small { font-size: .68rem; color: var(--color-gray-400); font-weight: 700; }
    .grx-rail-cap-who { display: flex; gap: .35rem; flex-wrap: wrap; margin-top: .35rem; }
    .grx-rail-cap-who span { font-size: .66rem; font-weight: 800; padding: .12rem .5rem; border-radius: 999px; }
    .grx-rail-cap-who .is-anee { background: #e6f2d8; color: #2f5219; }
    .grx-rail-cap-who .is-cal { background: var(--color-gray-100); color: var(--color-gray-600); }
    .grx-stages { margin-top: .6rem; display: grid; gap: .2rem; }
    .grx-stage-row { display: flex; align-items: center; gap: .55rem; width: 100%; text-align: left; border: 0; background: transparent; padding: .35rem .4rem; border-radius: .6rem; cursor: pointer; font: inherit; color: var(--color-gray-500);
        transition: background .2s, color .28s; }
    .grx-stage-row:hover { background: var(--color-gray-50); }
    .grx-stage-row.is-sel { background: var(--color-gray-100); }
    .grx-stage-dot { flex: none; width: .62rem; height: .62rem; border-radius: 999px; background: var(--color-gray-300); transition: background .35s, box-shadow .35s; }
    .grx-stage-row.is-past .grx-stage-dot { background: #8fbf62; }
    .grx-stage-row.is-now { color: var(--color-gray-900); font-weight: 700; }
    .grx-stage-row.is-now .grx-stage-dot { background: #4a7c2a; box-shadow: 0 0 0 3px rgb(107 159 61 / .25); }
    .grx-stage-t { flex: 1 1 auto; min-width: 0; font-size: .78rem; line-height: 1.25; }
    .grx-stage-t small { display: block; font-size: .64rem; font-weight: 600; color: var(--color-gray-400); }
    .grx-stage-tags { flex: none; display: flex; gap: .3rem; }
    .grx-stage-tags em { font-style: normal; font-size: .6rem; font-weight: 800; padding: .1rem .45rem; border-radius: 999px; }
    .grx-stage-tags .is-anee { background: #3d6823; color: #fff; }
    .grx-stage-tags .is-cal { background: var(--color-gray-200); color: var(--color-gray-700); }
    html.dark .grx-rail-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .grx-rail-head h4, html.dark .grx-rail-cap b, html.dark .grx-stage-row.is-now { color: #e8efe1; }
    html.dark .grx-seg { background: #1c2416; }
    html.dark .grx-seg button { color: #93a58b; }
    html.dark .grx-seg button.is-on { background: #2b3a1c; color: #cfe6b5; }
    html.dark .grx-seg button.is-on[data-grx-view="calendar"] { color: #e8efe1; }
    html.dark .grx-rail-seg { background: #2b3a1c; }
    html.dark .grx-rail-seg.is-past { background: #6f9a4a; }
    html.dark .grx-rail-seg.is-now { background: linear-gradient(90deg, #8fc96a, #a8cc7e); box-shadow: 0 0 0 3px rgb(168 204 126 / .25); }
    html.dark .grx-rail-mark.is-cal b { background: #2b3a1c; color: #d5e3c5; }
    html.dark .grx-rail-mark.is-cal i { background: #93a58b; }
    html.dark .grx-rail-cap { background: #10150c; border-color: #2b3a1c; color: #b7c2ad; }
    html.dark .grx-rail-cap-who .is-anee { background: #2f4d24; color: #cfe6b5; }
    html.dark .grx-rail-cap-who .is-cal, html.dark .grx-stage-tags .is-cal { background: #2b3a1c; color: #d5e3c5; }
    html.dark .grx-stage-row:hover, html.dark .grx-stage-row.is-sel { background: #1c2416; }
    html.dark .grx-stage-dot { background: #3a4a2c; }
    @media (prefers-reduced-motion: reduce) { .grx-rail-seg, .grx-rail-mark, .grx-rail-shift, .grx-stage-dot, .grx-seg button, .grx-rail-cap { transition: none; } }
    @media (prefers-reduced-motion: reduce) { .grx-btn, .grx-hero { animation: none; } .grx-conf-bar span { transition: none; } }
</style>

<script>
(() => {
    if (window.growthRealign) return;
    const SCHEDULE_ID = @json((int) $schedule->id);
    const LOCKED = @json($grxLocked);
    const PRICE = @json($grxPrice);
    const FACE = @json(\App\Models\AiSetting::current()->faceUrl());
    const U = {
        quote: @json(route('sm.growth.realign.quote')),
        run: @json(route('sm.growth.realign')),
        job: (id) => @json(route('sm.growth.realign.job', ['id' => '__ID__'])).replace('__ID__', id),
    };
    const esc = window.escapeHtml || ((s) => String(s == null ? '' : s));
    const $id = (s) => document.getElementById(s);
    let current = null;     // { lotId, lotName } for the open confirm sheet

    const shiftWords = (n) => {
        n = Number(n) || 0;
        if (n === 0) return 'on the calendar';
        return Math.abs(n) + ' day' + (Math.abs(n) === 1 ? '' : 's') + (n < 0 ? ' behind' : ' ahead');
    };
    const shiftChip = (n) => `<span class="grx-shift ${n < 0 ? 'is-behind' : (n > 0 ? 'is-ahead' : 'is-even')}">${n < 0 ? '🐢' : (n > 0 ? '🐇' : '✅')} ${esc(shiftWords(n))}</span>`;
    const when = (iso) => { try { const d = new Date(iso); return isNaN(d) ? '' : d.toLocaleDateString([], { month: 'short', day: 'numeric' }); } catch (_) { return ''; } };

    /* The block a lot's card carries: the button, and her note once she has spoken. */
    /* `calendar` is the calendar's own reading as the caller already has it
       ("DAT 45 — Tillering"): the sheet shows it at once instead of asking
       the server to walk the season again. */
    function block({ lotId, lotName, realign, calendar }) {
        const btn = LOCKED
            ? `<button type="button" class="grx-btn is-locked" data-tier-lock="solo" data-lock-say="Realign by Anee comes with a paid plan. Upgrade and she reads your lot's whole history to say where the crop really is."><img src="${esc(FACE)}" alt="">Realign by Anee <small>· 🔒 paid plans</small></button>`
            : `<button type="button" class="grx-btn" data-grx-open="${Number(lotId)}" data-grx-name="${esc(lotName)}" data-grx-cal="${esc(calendar || '')}"><img src="${esc(FACE)}" alt="">${realign ? 'Realign again' : 'Realign by Anee'} <small>· ${PRICE} credits</small></button>`;
        const note = realign ? `<div class="grx-note">
                <div class="grx-note-head"><b>Realigned by Anee</b>${shiftChip(realign.shiftDays)}<span class="grx-note-when">${esc(when(realign.at || realign.asOf))}</span></div>
                <div>${esc(realign.summary || '')}</div>
                <button type="button" class="grx-note-more" data-grx-show="${Number(lotId)}">Read her full reading →</button>
            </div>` : '';
        return `<div class="grx-block" data-grx-lot="${Number(lotId)}">${btn}${note}</div>`;
    }

    /* Where the crop is on its clock: every stage of the crop on one rail,
       the calendar's day and Anee's marked on it, the stages listed under
       it. The toggle chooses whose reading lights the "now" stage; a tap
       on a segment or a row explains that stage and says where each
       reading falls against it. Drawn only when the payload carries the
       crop's stage table (a lot read before the table travelled has none). */
    const stageAt = (stages, day) => { let at = -1; stages.forEach((s, i) => { if (day >= s.from) at = i; }); return at; };
    function rail(r) {
        const stages = Array.isArray(r.stages) ? r.stages : [];
        if (stages.length < 2) return '';
        const counter = r.counter || 'Day';
        const calDay = Number(r.calendarDay) || 0;
        const aneeDay = Number(r.physiologicalDay ?? (calDay + (Number(r.shiftDays) || 0)));
        const last = stages[stages.length - 1].from;
        const end = Math.max(Number(r.maturity) || 0, last + Math.max(10, Math.round(last * 0.15)), calDay + 1, aneeDay + 1);
        const pct = (d) => Math.max(0, Math.min(100, (d / end) * 100));
        const shift = Number(r.shiftDays) || 0;
        const segs = stages.map((s, i) => { const until = i + 1 < stages.length ? stages[i + 1].from : end; return `<button type="button" class="grx-rail-seg" data-grx-stage="${i}" style="flex-basis:${Math.max(2, ((until - s.from) / end) * 100)}%" title="${esc(s.label)} · ${esc(counter)} ${s.from}${i + 1 < stages.length ? '–' + (until - 1) : '+'}" aria-label="${esc(s.label)}"></button>`; }).join('');
        const lo = Math.min(calDay, aneeDay), hi = Math.max(calDay, aneeDay);
        return `
            <div class="grx-rail-card" data-grx-rail data-view="anee" data-sel="${stageAt(stages, aneeDay)}">
                <div class="grx-rail-head">
                    <h4>📍 Where the crop is on its clock</h4>
                    <div class="grx-seg" role="group" aria-label="Whose reading to show">
                        <button type="button" class="is-on" data-grx-view="anee">Anee</button>
                        <button type="button" data-grx-view="calendar">Calendar</button>
                    </div>
                </div>
                <div class="grx-rail">
                    <div class="grx-rail-track">
                        ${segs}
                        ${shift !== 0 ? `<span class="grx-rail-shift ${shift > 0 ? 'is-ahead' : 'is-behind'}" style="left:${pct(lo)}%;width:${Math.max(0.5, pct(hi) - pct(lo))}%"></span>` : ''}
                        <span class="grx-rail-mark is-cal" style="left:${pct(calDay)}%"><b>Calendar<br>${esc(counter)} ${calDay}</b><i></i></span>
                        <span class="grx-rail-mark is-anee is-lead" style="left:${pct(aneeDay)}%"><b>Anee<br>${esc(counter)} ${aneeDay}</b><i></i></span>
                    </div>
                    <div class="grx-rail-scale"><span>${esc(counter)} 0</span><span>${Number(r.maturity) ? 'harvest ~' + esc(counter) + ' ' + Number(r.maturity) : esc(counter) + ' ' + end}</span></div>
                </div>
                <div class="grx-rail-cap" data-grx-cap></div>
                <div class="grx-stages">
                    ${stages.map((s, i) => `<button type="button" class="grx-stage-row" data-grx-stage="${i}"><span class="grx-stage-dot"></span><span class="grx-stage-t">${esc(s.label)}<small>${esc(counter)} ${s.from}+</small></span><span class="grx-stage-tags" data-grx-tags="${i}"></span></button>`).join('')}
                </div>
            </div>`;
    }
    function paintRail(card, r) {
        const stages = Array.isArray(r.stages) ? r.stages : [];
        const view = card.dataset.view || 'anee';
        const sel = Math.max(0, Math.min(stages.length - 1, Number(card.dataset.sel) || 0));
        const counter = r.counter || 'Day';
        const calDay = Number(r.calendarDay) || 0;
        const aneeDay = Number(r.physiologicalDay ?? (calDay + (Number(r.shiftDays) || 0)));
        const calAt = stageAt(stages, calDay), aneeAt = stageAt(stages, aneeDay);
        const nowAt = view === 'anee' ? aneeAt : calAt;
        card.querySelectorAll('[data-grx-view]').forEach((b) => b.classList.toggle('is-on', b.dataset.grxView === view));
        card.querySelectorAll('.grx-rail-seg').forEach((seg, i) => { seg.classList.toggle('is-past', i < nowAt); seg.classList.toggle('is-now', i === nowAt); seg.classList.toggle('is-sel', i === sel); });
        card.querySelectorAll('.grx-stage-row').forEach((row, i) => { row.classList.toggle('is-past', i < nowAt); row.classList.toggle('is-now', i === nowAt); row.classList.toggle('is-sel', i === sel); });
        card.querySelectorAll('[data-grx-tags]').forEach((t, i) => { t.innerHTML = (i === aneeAt ? '<em class="is-anee">Anee</em>' : '') + (i === calAt ? '<em class="is-cal">Calendar</em>' : ''); });
        const mc = card.querySelector('.grx-rail-mark.is-cal'), ma = card.querySelector('.grx-rail-mark.is-anee');
        if (mc && ma) { mc.classList.toggle('is-dim', view === 'anee'); ma.classList.toggle('is-dim', view !== 'anee'); mc.classList.toggle('is-lead', view !== 'anee'); ma.classList.toggle('is-lead', view === 'anee'); }
        const s = stages[sel];
        const cap = card.querySelector('[data-grx-cap]');
        if (s && cap) {
            const until = sel + 1 < stages.length ? stages[sel + 1].from - 1 : null;
            const rel = (at, who) => at === sel ? `${who} is here` : (at > sel ? `${who}: ${at - sel} stage${at - sel === 1 ? '' : 's'} past this` : `${who}: ${sel - at} stage${sel - at === 1 ? '' : 's'} before this`);
            cap.classList.add('is-swap');
            setTimeout(() => {
                cap.innerHTML = `<div class="grx-rail-cap-t"><b>${esc(s.label)}</b><small>${esc(counter)} ${s.from}${until !== null ? '–' + until : '+'}</small></div>${s.what ? `<div>${esc(s.what)}</div>` : ''}<div class="grx-rail-cap-who"><span class="is-anee">${esc(rel(aneeAt, 'Anee'))}</span><span class="is-cal">${esc(rel(calAt, 'Calendar'))}</span></div>`;
                cap.classList.remove('is-swap');
            }, cap.innerHTML ? 140 : 0);
        }
    }
    document.addEventListener('click', (e) => {
        const card = e.target.closest('[data-grx-rail]');
        if (!card) return;
        const r = card.__realign;
        if (!r) return;
        const v = e.target.closest('[data-grx-view]');
        if (v) { card.dataset.view = v.dataset.grxView; paintRail(card, r); return; }
        const st = e.target.closest('[data-grx-stage]');
        if (st) { card.dataset.sel = st.dataset.grxStage; paintRail(card, r); }
    });

    /* The reading, drawn into the result sheet. */
    function draw(r, lotName) {
        const conf = Math.max(0, Math.min(100, Number(r.confidence) || 0));
        const li = (e, t) => `<li><span class="e">${e}</span><span>${esc(t)}</span></li>`;
        const listCard = (cls, title, arr, e) => (arr || []).length ? `<div class="grx-card ${cls}"><h4>${title}</h4><ul>${arr.map((t) => li(e, t)).join('')}</ul></div>` : '';
        const counter = r.counter || 'Day';
        $id('grxResultTitle').textContent = `Anee's reading — ${lotName || ''}`;
        $id('grxResultBody').innerHTML = `
            <div class="grx-hero">
                <div class="grx-hero-k">The crop is in</div>
                <div class="grx-hero-stage">${esc(r.stageLabel || '')}</div>
                <div class="grx-hero-line">as if it were ${esc(counter)} ${esc(String(r.physiologicalDay ?? ''))} — the calendar counts ${esc(counter)} ${esc(String(r.calendarDay ?? ''))}</div>
                ${shiftChip(r.shiftDays)}
            </div>
            <div class="grx-two">
                <div class="grx-cell"><i>The calendar said</i><b>${esc(r.calendarStageLabel || '—')}</b><span>${esc(counter)} ${esc(String(r.calendarDay ?? ''))}</span></div>
                <div class="grx-cell is-anee"><i>Anee says</i><b>${esc(r.stageLabel || '')}</b><span>${esc(shiftWords(r.shiftDays))}</span></div>
            </div>
            ${rail(r)}
            <div class="grx-conf"><span>Confidence</span><span class="grx-conf-bar"><span data-w="${conf}"></span></span><b>${conf}%</b></div>
            <p class="grx-sum">${esc(r.summary || '')}</p>
            ${listCard('', '🔎 Why she reads it this way', r.reasons, '•')}
            ${listCard('is-do', '✅ What to do now', r.recommendations, '👉')}
            ${listCard('is-watch', '👀 What to watch for this week', r.watch, '⚠️')}
            <p class="grx-applied">Applied to <b>${esc(lotName || 'this lot')}</b>: the board's day headers, the Tools sheet and the Growth Stages module now read this stage. The day count stays the calendar's; only the stage read off it has moved. Ask her again whenever the field tells a different story.</p>`;
        const card = $id('grxResultBody').querySelector('[data-grx-rail]');
        if (card) { card.__realign = r; paintRail(card, r); }
        openSheet('grRealignResultSheet');
        requestAnimationFrame(() => setTimeout(() => { $id('grxResultBody').querySelectorAll('.grx-conf-bar span').forEach((el) => { el.style.width = el.dataset.w + '%'; }); }, 60));
    }

    async function open(lotId, lotName, calendar) {
        current = { lotId: Number(lotId), lotName: lotName || '' };
        $id('grxLotName').textContent = current.lotName || 'Lot';
        // What the page already knows, shown at once; the quote is only the price and the balance.
        $id('grxCalendar').textContent = calendar ? `The calendar says ${calendar}` : 'Anee reads the whole season when she runs.';
        $id('grxBalance').textContent = '';
        $id('grxPrev').hidden = true;
        $id('grxBlocked').hidden = true;
        $id('grxRun').disabled = true;
        openSheet('grRealignSheet');
        try {
            const res = await api(`${U.quote}?scheduleId=${SCHEDULE_ID}&lotId=${current.lotId}`);
            const d = res.data;
            current.quote = d;
            if (!calendar) {
                $id('grxCalendar').textContent = d.lot.crop ? `${d.lot.crop} · Anee reads the whole season when she runs.` : 'No crop set';
            }
            if (d.realign) {
                const p = $id('grxPrev');
                p.innerHTML = `<b>Her last reading</b> (${esc(when(p.dataset.at = d.realign.at || d.realign.asOf))}): ${esc(shiftWords(d.realign.shiftDays))} — ${esc(d.realign.summary || '')}`;
                p.hidden = false;
            }
            $id('grxBalance').textContent = d.unlimited ? ' · unlimited credits' : ` · you have ${Number(d.balance).toLocaleString()}`;
            if (d.blocked) { $id('grxBlocked').textContent = d.blocked; $id('grxBlocked').hidden = false; return; }
            if (!d.aiUsable) { $id('grxBlocked').textContent = 'The AI Technician is not available right now.'; $id('grxBlocked').hidden = false; return; }
            if (!d.unlimited && Number(d.balance) < Number(d.price)) {
                $id('grxBlocked').innerHTML = `You need ${esc(String(d.price))} credits for this and have ${esc(String(d.balance))}. <a href="${@json(route('sm.ai', ['id' => $schedule->id]))}" class="underline font-bold">Top up</a>.`;
                $id('grxBlocked').hidden = false;
                return;
            }
            $id('grxRun').disabled = false;
        } catch (err) {
            $id('grxBlocked').textContent = err.message;
            $id('grxBlocked').hidden = false;
        }
    }

    async function run() {
        if (!current) return;
        const { lotId, lotName } = current;
        closeSheet('grRealignSheet');
        window.aneeWait.show({
            title: `Anee is reading ${lotName || 'the lot'}…`,
            lines: ['Reading every activity since day zero…', 'Weighing what was applied, and when…', 'Reading your notes, tags and observations…', 'Checking the sky the field had…', 'Placing the crop on its own clock…'],
            sub: 'A deep read of one lot — about a minute.',
        });
        let landed = false;
        try {
            // The schedule rides the query string: that is where every module write reads it.
            const res = await api(`${U.run}?scheduleId=${SCHEDULE_ID}`, { method: 'POST', body: { lotId } });
            let data = res.data;
            if (data.pending) {
                // The shared poll: the bar and the clock ride along.
                data = await window.aneeWait.poll({ id: data.id || res.data.id, job: U.job, phases: window.aneeWait.phases.plain, limit: 80 });
            }
            landed = true;
            const realign = data.realign || data.result;
            try { window.growthRealign.onApplied?.(lotId, realign); } catch (_) {}
            await window.aneeWait.done({ title: 'Done!', line: `${data.credits} credits used — ${lotName || 'the lot'} is realigned.` });
            draw(realign, lotName);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            if (!landed) window.aneeWait.fail();
        }
    }

    document.addEventListener('click', (e) => {
        const o = e.target.closest('[data-grx-open]');
        if (o) { open(o.dataset.grxOpen, o.dataset.grxName, o.dataset.grxCal); return; }
        const s = e.target.closest('[data-grx-show]');
        if (s) {
            const lotId = Number(s.dataset.grxShow);
            const r = (window.growthRealign.known || {})[lotId];
            if (r) draw(r, (window.growthRealign.names || {})[lotId] || '');
        }
    });
    $id('grxRun').addEventListener('click', run);

    window.growthRealign = {
        block, draw, open,
        // What each page knows: the applied readings and the lot names, so
        // "read her full reading" can open without another request.
        known: {}, names: {},
        onApplied: null,
        shiftWords,
    };
})();
</script>
@endonce
