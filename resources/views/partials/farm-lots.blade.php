{{-- THE FARM'S LOTS, the kit every analysis and Global Lots share (2026-10-16, the owner).

     "Most of these analyses ask the same questions about the soil and the
     water, and they always pertain to a specific lot": a lot is answered once,
     with where it is (province, town or city and barangay, each a tag that
     opens a list), its size, and its soil, water and
     last season (the shared field questions, partials/field-profile), and an
     analysis then asks only which lot. App\Support\FarmLots and
     FarmLotController keep them (as_field_profiles).

     Include it once on a page: @include('partials.farm-lots'). It brings the
     field questions and the field card with it.

     JS (window.aneeLots):
       const p = aneeLots.picker(el, {
           crop: () => 'rice',          // the analysis's crop, for the troubles list (or null)
           value: 12,                   // a lot id to start on (optional)
           onPick(lot) {},              // a lot picked, saved or changed (null when cleared)
       });
       p.value()  p.set(id)  p.refresh()
       aneeLots.edit(lot | null, { crop }) -> Promise<lot | null>   the lot sheet (new when null)
       aneeLots.rows(fresh) -> Promise<lots>
       aneeLots.answered(lot) -> the question keys the lot has answered
       aneeLots.has(lot, key) -> whether one question is answered
       aneeLots.legacy(lot, crop) -> Promise of FieldProfile::legacy() for the lot's answers
       aneeLots.payload(lot) -> { lotId, location, place, areaHa, fieldProfile, fieldProfileFrom }
       aneeLots.confirm(lot) -> Promise<lot>   "Still correct": the six months start again
       aneeLots.togglePreview(host, lot, { crop, onEdit }) -> bool   the lot's answers as cards to
                                     slide through, opened in (or closed from) any host element
     A lot has no crop (2026-10-16, the owner: what grows on a lot changes often; the analysis says it).
     A lot: { id, name, province, town, barangay, place, where, areaHa, areaUnit,
              answers, count, updated, updatedAt, ageDays, stale, staleOn }
     Every save fires `aneelots:changed` on window, and every picker on the page repaints. --}}
@include('partials.field-profile-card')
@once
@push('head')
<style>
    :root { --fl-e: cubic-bezier(.22,1,.36,1); }
    .fl-pick { --fl-e: cubic-bezier(.22,1,.36,1); display: grid; gap: .55rem; min-width: 0; }
    .fl-card { position: relative; display: flex; align-items: center; gap: .75rem; width: 100%; min-width: 0; padding: .75rem .8rem; border-radius: 1.1rem; text-align: left;
        background: var(--color-white); border: 1.5px solid var(--color-gray-200); color: var(--color-gray-900); cursor: pointer;
        transition: border-color .28s var(--fl-e), background-color .28s var(--fl-e), transform .28s var(--fl-e), box-shadow .28s var(--fl-e); animation: flIn .32s var(--fl-e) both; }
    @media (hover: hover) { .fl-card:hover { border-color: #b9d39c; transform: translateY(-1px); box-shadow: 0 10px 22px -16px rgb(20 33 12 / .35); } }
    .fl-card.is-on { border-color: #4a7c2a; background: #f3f8ec; box-shadow: 0 0 0 1px #4a7c2a inset; }
    @keyframes flIn { from { opacity: 0; transform: translateY(6px); } }
    .fl-ic { flex: none; width: 2.7rem; height: 2.7rem; border-radius: .9rem; display: grid; place-items: center; font-size: 1.35rem; background: #f1f6ea; border: 1px solid #e1ecd4; }
    .fl-ic img { width: 1.7rem; height: 1.7rem; object-fit: contain; }
    .fl-tx { flex: 1; min-width: 0; }
    .fl-tx > b { display: block; font-size: .95rem; font-weight: 800; line-height: 1.3; overflow-wrap: anywhere; }
    .fl-tx > small { display: block; margin-top: .05rem; font-size: .78rem; line-height: 1.4; color: var(--color-gray-500); overflow-wrap: anywhere; }
    .fl-tx > small.is-none { color: #b45309; font-weight: 700; }
    .fl-chips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
    .fl-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .12rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 800; line-height: 1.35; color: #3d5226; background: #eef5e6; }
    .fl-chip.is-none { color: var(--color-gray-500); background: var(--color-gray-100); }
    .fl-chip.is-old { color: #92400e; background: #fef3c7; }
    .fl-chip svg { width: .78rem; height: .78rem; }
    .fl-ck { flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: grid; place-items: center; border: 2px solid #cbd5c1; color: #fff; transition: background-color .28s var(--fl-e), border-color .28s var(--fl-e); }
    .fl-ck svg { width: .8rem; height: .8rem; opacity: 0; transform: scale(.6); transition: opacity .2s ease, transform .28s var(--fl-e); }
    .fl-card.is-on .fl-ck { background: #4a7c2a; border-color: #4a7c2a; }
    .fl-card.is-on .fl-ck svg { opacity: 1; transform: none; }
    .fl-add { display: flex; align-items: center; justify-content: center; gap: .45rem; width: 100%; min-height: 3.1rem; padding: .6rem 1rem; border-radius: 1.1rem; font-size: .9rem; font-weight: 800;
        color: #3d6823; background: transparent; border: 1.5px dashed #b9d39c; cursor: pointer; transition: background-color .28s var(--fl-e), border-color .28s var(--fl-e); }
    .fl-add:hover { background: #f3f8ec; border-color: #86b556; }
    .fl-add svg { width: 1.05rem; height: 1.05rem; }
    .fl-more { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .4rem; font-size: .76rem; color: var(--color-gray-500); }
    .fl-more a { font-weight: 800; color: #3d6823; text-decoration: underline; text-underline-offset: 3px; }
    .fl-empty { padding: 1rem; border-radius: 1.1rem; font-size: .86rem; line-height: 1.55; color: var(--color-gray-600); background: var(--color-gray-50); border: 1px dashed var(--color-gray-200); }
    .fl-wait { padding: .9rem; font-size: .84rem; color: var(--color-gray-400); }
    .fl-old { display: grid; gap: .55rem; padding: .75rem .85rem; border-radius: 1rem; font-size: .82rem; line-height: 1.5; color: #78350f; background: #fffbeb; border: 1px solid #fde68a; animation: flIn .3s var(--fl-e) both; }
    .fl-old[hidden] { display: none; }
    .fl-old > div { display: flex; flex-wrap: wrap; gap: .45rem; }
    .fl-old button { min-height: 2.4rem; padding: .35rem .9rem; border-radius: 999px; font-size: .8rem; font-weight: 800; border: 1.5px solid #f59e0b; color: #92400e; background: #fff; transition: background-color .28s var(--fl-e); }
    .fl-old button.is-main { color: #fff; background: #d97706; border-color: #d97706; }
    .fl-old button:hover { background: #fef3c7; }
    .fl-old button.is-main:hover { background: #b45309; }

    /* The picked lot's answers, as cards to slide through (2026-10-16, the owner). */
    .fl-prev { display: grid; gap: .55rem; min-width: 0; margin: -.1rem 0 .15rem; padding: .7rem 0 .65rem; border-radius: 1.1rem;
        background: linear-gradient(160deg, #fbfdf8, #f2f8ea); border: 1px solid #e0ebd3; overflow: hidden; animation: flPrevIn .38s var(--fl-e) both; }
    .fl-prev.is-out { animation: flPrevOut .24s var(--fl-e) both; }
    @keyframes flPrevIn { from { opacity: 0; transform: translateY(-6px); clip-path: inset(0 0 100% 0 round 1.1rem); } to { opacity: 1; transform: none; clip-path: inset(0 0 0 0 round 1.1rem); } }
    @keyframes flPrevOut { to { opacity: 0; transform: translateY(-6px); } }
    .fl-prev-h { display: flex; align-items: center; gap: .5rem; padding: 0 .85rem; min-width: 0; }
    .fl-prev-h b { flex: 1; min-width: 0; font-size: .8rem; font-weight: 900; letter-spacing: .04em; text-transform: uppercase; color: #3d6823; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fl-prev-h b small { display: block; margin-top: .1rem; font-size: .72rem; font-weight: 700; letter-spacing: 0; text-transform: none; color: #6b7a5c; white-space: normal; }
    .fl-prev-h button.is-main { color: #fff; background: #4a7c2a; border-color: #4a7c2a; }
    .fl-prev-h button.is-main:hover { background: #3d6823; border-color: #3d6823; }
    .fl-prev.is-sheet { margin: 0; }
    .fl-prev-h button { flex: none; min-height: 2.2rem; padding: .25rem .8rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: #3d6823; background: #fff; border: 1.5px solid #cfe0bd;
        transition: background-color .28s var(--fl-e), border-color .28s var(--fl-e); }
    .fl-prev-h button:hover { background: #eef5e6; border-color: #86b556; }
    .fl-prev-row { display: flex; gap: .6rem; overflow-x: auto; overscroll-behavior-x: contain; scroll-snap-type: x mandatory; scroll-padding: 0 .85rem; padding: .1rem .85rem .2rem;
        scrollbar-width: none; -webkit-overflow-scrolling: touch; }
    .fl-prev-row::-webkit-scrollbar { display: none; }
    .fl-prev-row:focus-visible { outline: 2px solid #86b556; outline-offset: -2px; border-radius: .9rem; }
    .fl-prev-row { align-items: stretch; }
    .fl-pc { flex: 0 0 84%; max-width: 20rem; min-width: 0; scroll-snap-align: start; display: flex; flex-direction: column; padding: .8rem .85rem .85rem; border-radius: 1.05rem;
        background: #fff; border: 1px solid #e3ecd9; box-shadow: 0 10px 22px -18px rgb(20 33 12 / .55); }
    @media (min-width: 640px) { .fl-pc { flex-basis: 17.5rem; } }
    .fl-pc header { display: flex; align-items: center; gap: .55rem; padding-bottom: .55rem; margin-bottom: .2rem; border-bottom: 1px solid #edf2e6; }
    .fl-pc header > span { flex: none; width: 2.1rem; height: 2.1rem; border-radius: .75rem; display: grid; place-items: center; font-size: 1.1rem; background: #f1f6ea; }
    .fl-pc header > div { flex: 1; min-width: 0; }
    .fl-pc header b { display: block; font-size: .9rem; font-weight: 800; line-height: 1.25; color: #14210c; }
    .fl-pc header small { display: block; font-size: .7rem; font-weight: 700; color: #6b7a5c; }
    .fl-pc header small.is-done { color: #3d6823; }
    .fl-pc header em { flex: none; font-style: normal; font-size: .66rem; font-weight: 800; color: #9aa68d; }
    .fl-qa { list-style: none; margin: 0; padding: 0; display: grid; }
    .fl-qa li { display: grid; gap: .12rem; padding: .5rem 0; border-bottom: 1px dashed #e7eee0; min-width: 0; }
    .fl-qa li:last-child { border-bottom: 0; padding-bottom: 0; }
    .fl-qa span { font-size: .74rem; line-height: 1.4; color: #6b7280; overflow-wrap: anywhere; }
    .fl-qa b { display: flex; align-items: flex-start; gap: .35rem; font-size: .86rem; font-weight: 800; line-height: 1.35; color: #1f3a10; overflow-wrap: anywhere; }
    .fl-qa b::before { content: ''; flex: none; width: .5rem; height: .5rem; margin-top: .38em; border-radius: 999px; background: #6b9f3d; }
    .fl-qa li.is-empty b { font-weight: 600; font-style: italic; color: #a1a8a0; }
    .fl-qa li.is-empty b::before { background: transparent; box-shadow: inset 0 0 0 1.5px #c9d3c0; }
    .fl-prev-f { display: flex; align-items: center; gap: .6rem; padding: 0 .85rem; min-width: 0; }
    .fl-dots { display: flex; gap: .3rem; flex: none; }
    .fl-dots i { width: .4rem; height: .4rem; border-radius: 999px; background: #cfdcc0; transition: width .28s var(--fl-e), background-color .28s var(--fl-e); }
    .fl-dots i.is-on { width: 1.1rem; background: #4a7c2a; }
    /* "Slide to see more": a finger that swipes, until the cards are slid once. */
    .fl-hint { flex: 1; min-width: 0; display: inline-flex; align-items: center; justify-content: flex-end; gap: .45rem; font-size: .74rem; font-weight: 800; color: #5b6b4c;
        transition: opacity .28s var(--fl-e), transform .28s var(--fl-e); }
    .fl-hint.is-gone { opacity: 0; transform: translateX(6px); pointer-events: none; }
    .fl-hint svg { flex: none; width: 3.25rem; height: 1.5rem; overflow: visible; }
    .fl-hint .fl-f1 { fill: rgb(134 181 86 / .3); }
    .fl-hint .fl-f2 { fill: #4a7c2a; stroke: #fff; stroke-width: 1.6; }
    html.dark .fl-hint .fl-f1 { fill: rgb(143 194 94 / .28); }
    html.dark .fl-hint .fl-f2 { fill: #8fc25e; stroke: #10150d; }
    .fl-hint .fl-finger { animation: flSwipe 1.9s var(--fl-e) infinite; transform-origin: center; }
    .fl-hint .fl-trail { animation: flTrail 1.9s var(--fl-e) infinite; }
    @keyframes flSwipe { 0% { transform: translateX(14px); opacity: 0; } 12% { transform: translateX(14px); opacity: 1; } 62% { transform: translateX(-18px); opacity: 1; } 82%, 100% { transform: translateX(-18px); opacity: 0; } }
    @keyframes flTrail { 0%, 12% { stroke-dashoffset: -36; opacity: 0; } 62% { stroke-dashoffset: 0; opacity: .7; } 82%, 100% { stroke-dashoffset: 0; opacity: 0; } }
    .fl-arrows { display: none; gap: .35rem; flex: none; }
    @media (hover: hover) and (pointer: fine) {
        .fl-arrows { display: inline-flex; }
        .fl-hint .fl-touch { display: none; }
    }
    @media not all and (hover: hover) and (pointer: fine) { .fl-hint .fl-mouse { display: none; } }
    .fl-arrows button { width: 2.1rem; height: 2.1rem; border-radius: 999px; display: grid; place-items: center; color: #3d6823; background: #fff; border: 1.5px solid #cfe0bd;
        transition: background-color .28s var(--fl-e), opacity .28s var(--fl-e); }
    .fl-arrows button:hover:not(:disabled) { background: #eef5e6; }
    .fl-arrows button:disabled { opacity: .4; cursor: default; }
    .fl-arrows svg { width: .9rem; height: .9rem; }
    html.dark .fl-prev { background: #151b12; border-color: #2b3a1c; }
    html.dark .fl-prev-h b { color: #a5c97e; }
    html.dark .fl-prev-h button, html.dark .fl-arrows button { color: #a5c97e; background: #10150d; border-color: #3d5a26; }
    html.dark .fl-pc { background: #10150d; border-color: #2b3a1c; }
    html.dark .fl-pc header > span { background: #1c2616; }
    html.dark .fl-pc header b { color: #e8efe1; }
    html.dark .fl-pc header { border-color: #22301a; }
    html.dark .fl-pc header small { color: #93a684; }
    html.dark .fl-pc header small.is-done { color: #a5c97e; }
    html.dark .fl-qa li { border-color: #22301a; }
    html.dark .fl-qa span { color: #9aa78d; }
    html.dark .fl-qa b { color: #d5ebc0; }
    html.dark .fl-qa b::before { background: #8fc25e; }
    html.dark .fl-qa li.is-empty b { color: #6f7b66; }
    html.dark .fl-qa li.is-empty b::before { box-shadow: inset 0 0 0 1.5px #3a4a2c; }
    html.dark .fl-dots i { background: #3a4a2c; }
    html.dark .fl-dots i.is-on { background: #8fc25e; }
    html.dark .fl-hint { color: #b9caa8; }
    @media (prefers-reduced-motion: reduce) {
        .fl-prev, .fl-prev.is-out, .fl-hint .fl-finger, .fl-hint .fl-trail { animation: none !important; }
        .fl-dots i, .fl-hint { transition: none; }
    }
    html.sm-still .fl-prev, html.sm-still .fl-hint .fl-finger, html.sm-still .fl-hint .fl-trail { animation: none !important; }

    /* The lot sheet. It wears the field questions' sheet (fq-sheet, fq-box). */
    .fl-f { margin-top: 1rem; }
    .fl-f:first-child { margin-top: .4rem; }
    .fl-lab { display: block; margin-bottom: .4rem; font-size: .84rem; font-weight: 800; color: var(--color-gray-800); }
    .fl-lab small { font-weight: 600; color: var(--color-gray-400); }
    .fl-sub { margin: -.2rem 0 .5rem; font-size: .76rem; line-height: 1.45; color: var(--color-gray-500); }
    .fl-tags { display: grid; gap: .45rem; }
    @container (min-width: 32rem) { .fl-tags.is-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .fl-tag { display: flex; align-items: center; gap: .55rem; width: 100%; min-width: 0; min-height: 3rem; padding: .45rem .75rem; border-radius: .95rem; text-align: left;
        background: var(--color-white); border: 1.5px solid var(--color-gray-200); color: var(--color-gray-900); cursor: pointer; transition: border-color .28s var(--fl-e), background-color .28s var(--fl-e), opacity .28s var(--fl-e); }
    .fl-tag:hover:not(:disabled) { border-color: #86b556; background: #f8fbf4; }
    .fl-tag:disabled { opacity: .5; cursor: default; }
    .fl-tag .ti { flex: none; font-size: 1.05rem; }
    .fl-tag .tt { flex: 1; min-width: 0; }
    .fl-tag .tt small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--color-gray-400); }
    .fl-tag .tt b { display: block; font-size: .9rem; font-weight: 800; color: #2d5016; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fl-tag.is-none .tt b { font-weight: 600; color: var(--color-gray-400); }
    .fl-tag .tc { flex: none; width: 1rem; height: 1rem; color: var(--color-gray-400); }
    .fl-tag.is-bump { animation: flBump .4s var(--fl-e); }
    @keyframes flBump { 40% { transform: scale(1.02); } }
    /* The size and its unit (2026-10-16, the owner): the unit is a tag that opens its list. */
    .fl-size { display: flex; gap: .5rem; align-items: stretch; min-width: 0; }
    .fl-size .form-input { flex: 1 1 auto; min-width: 0; }
    .fl-size .fl-tag { flex: none; width: auto; min-width: 9.5rem; }
    @media (max-width: 380px) { .fl-size .fl-tag { min-width: 8.2rem; } }
    .fl-q { padding: .8rem; border-radius: 1.1rem; background: linear-gradient(160deg, #fbfdf8, #f2f8ea); border: 1px solid #e0ebd3; }
    .fl-q-tiles { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem; }
    @container (min-width: 32rem) { .fl-q-tiles { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
    .fl-qt { position: relative; display: flex; flex-direction: column; align-items: center; gap: .1rem; padding: .5rem .25rem; border-radius: .85rem; text-align: center; background: #fff; border: 1.5px solid #e5e9e0;
        transition: border-color .28s var(--fl-e), background-color .28s var(--fl-e); }
    .fl-qt > span { font-size: 1.1rem; filter: grayscale(.6); opacity: .75; transition: filter .28s var(--fl-e), opacity .28s var(--fl-e); }
    .fl-qt > b { font-size: .7rem; font-weight: 800; line-height: 1.2; color: #14210c; }
    .fl-qt > small { font-size: .62rem; font-weight: 700; color: #8a9480; }
    .fl-qt.is-on { border-color: #86b556; background: #f4f9ee; }
    .fl-qt.is-on > span { filter: none; opacity: 1; }
    .fl-q-say { margin: .6rem 0 .1rem; font-size: .78rem; line-height: 1.5; color: #3f4a37; }
    .fl-q .btn { width: 100%; margin-top: .55rem; min-height: 2.9rem; }
    .fl-warn { margin-top: .4rem; padding: .6rem .75rem; border-radius: .85rem; font-size: .8rem; line-height: 1.5; color: #78350f; background: #fffbeb; border: 1px solid #fde68a; }
    .fl-err { margin-top: .6rem; font-size: .8rem; font-weight: 700; color: #b91c1c; }
    .fl-err[hidden], .fl-warn[hidden] { display: none; }
    .fl-shake { animation: flShake .4s var(--fl-e); }
    @keyframes flShake { 20%, 60% { transform: translateX(-5px); } 40%, 80% { transform: translateX(5px); } }
    #flEditSheet .fq-body { container-type: inline-size; }

    html.dark .fl-card, html.dark .fl-tag { background: #151b12; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .fl-card.is-on { background: #1c2914; border-color: #6b9f3d; box-shadow: 0 0 0 1px #6b9f3d inset; }
    html.dark .fl-ic { background: #1c2616; border-color: #2b3a1c; }
    html.dark .fl-chip { color: #cfe6b8; background: #22301a; }
    html.dark .fl-chip.is-old { color: #fcd34d; background: #3a2a0c; }
    html.dark .fl-tag .tt b { color: #a5c97e; }
    html.dark .fl-tag.is-none .tt b { color: #7d8b70; }
    html.dark .fl-add { color: #a5c97e; border-color: #3d5a26; }
    html.dark .fl-add:hover { background: #1c2914; }
    html.dark .fl-more a { color: #a5c97e; }
    html.dark .fl-old, html.dark .fl-warn { color: #fde68a; background: #2a2010; border-color: #5c4413; }
    html.dark .fl-old button { background: #1a1408; color: #fcd34d; }
    html.dark .fl-q { background: #151b12; border-color: #2b3a1c; }
    html.dark .fl-qt { background: #10150d; border-color: #2b3a1c; }
    html.dark .fl-qt > b { color: #e8efe1; }
    html.dark .fl-qt.is-on { background: #1c2914; border-color: #6b9f3d; }
    html.dark .fl-q-say { color: #cdd8c0; }
    @media (prefers-reduced-motion: reduce) {
        .fl-card, .fl-old, .fl-tag.is-bump, .fl-shake { animation: none !important; }
        .fl-card, .fl-ck, .fl-ck svg, .fl-add, .fl-tag, .fl-qt, .fl-qt > span, .fl-old button { transition: none !important; }
    }
    html.sm-still .fl-card, html.sm-still .fl-old { animation: none !important; }
</style>
@endpush

@push('sheets')
{{-- One lot: its name, where it is, its size and its field answers. --}}
<dialog class="fq-sheet is-full" id="flEditSheet" aria-labelledby="flEditTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><h2 id="flEditTitle">New lot<small id="flEditSub">Where it is, its size, and its soil and water</small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="fq-body" id="flEditBody"></div>
        <div class="fq-foot"><button type="button" class="btn btn-white" data-fq-close>Cancel</button><span></span><button type="button" class="btn btn-primary" id="flEditSave">Save the lot</button></div>
    </div>
</dialog>

{{-- A list to pick from: the provinces, the towns, the barangays, the units. --}}
<dialog class="fq-sheet" id="flPickSheet" aria-labelledby="flPickTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><h2 id="flPickTitle">Choose<small id="flPickSub"></small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <label class="fq-find"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg><span class="sr-only">Search</span><input type="search" id="flPickQ" placeholder="Search" autocomplete="off" maxlength="60"></label>
        <div class="fq-list" id="flPickList"></div>
    </div>
</dialog>
@endpush

@push('scripts')
@php
    $flData = [
        'urls' => [
            'list' => route('farmlots.list'), 'save' => route('farmlots.save'), 'update' => url('/app/farm-lots'),
            'places' => route('farmlots.places'), 'page' => route('farmlots.index'),
        ],
        'ph' => \App\Support\Region::ph(),
        'staleDays' => \App\Support\FarmLots::STALE_DAYS,
        'crops' => collect(\App\Support\FieldCatalogue::crops())->map(fn ($c, $k) => [$k, $c[1], $c[2], $c[0]])->values(),
        'pages' => collect(\App\Support\FieldProfile::QUESTIONS)->map(fn ($q) => $q['page'])->all(),
        'groups' => collect(\App\Support\FieldProfile::GROUPS)->map(fn ($g) => $g[0])->all(),
        'icon' => asset('images/treasure-map.png'),
        'units' => \App\Support\FarmLots::AREA_UNITS,
        // The questions in words, for the picked lot's preview: key => [short name, page, option => label].
        'qs' => collect(\App\Support\FieldProfile::QUESTIONS)->map(fn ($q) => [$q['short'], $q['page'], collect($q['o'])->map(fn ($o) => $o[0])->all(), $q['q']])->all(),
        'others' => collect(\App\Support\FieldProfile::OTHER)->map(fn ($o) => $o[0])->all(),
    ];
@endphp
<script>
(() => {
    if (window.aneeLots) return;
    const D = @json($flData);
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const clone = (o) => JSON.parse(JSON.stringify(o || {}));
    const reduce = () => document.documentElement.classList.contains('sm-still') || matchMedia('(prefers-reduced-motion: reduce)').matches;
    const apiReady = () => (window.api ? Promise.resolve() : new Promise((r) => window.addEventListener('load', r, { once: true })));
    const call = (url, opts) => apiReady().then(() => window.api(url, opts));
    const CROPS = Object.fromEntries(D.crops.map((c) => [c[0], c]));
    const TILE = { soil: ['🟤', 'Soil'], water: ['💧', 'Water'], acid: ['🧪', 'Acidity'], land: ['⛰️', 'The land'], last: ['🌾', 'Last season'], weeds: ['🌿', 'Weeds'], problems: ['🐛', 'Troubles'] };
    const CHECK = '<svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    const CLOCK = '<svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path stroke-linecap="round" d="M12 7.5V12l3 2"/></svg>';
    const PLUS = '<svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>';
    const CHEV = '<svg class="tc" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>';
    const fmtHa = (a) => { const v = Number(a); return v ? (v < 0.1 ? Math.round(v * 10000).toLocaleString() + ' sq m' : (Math.round(v * 100) / 100).toLocaleString() + ' ha') : ''; };
    // A size in hectares, said in a unit (the one the lot was given in).
    const UNITS = D.units;
    const inUnit = (ha, u) => { const f = (UNITS[u] || UNITS.ha)[2]; const v = Number(ha) / f; return v >= 100 ? Math.round(v) : Math.round(v * 100) / 100; };
    const fmtSize = (lot) => { const u = UNITS[lot && lot.areaUnit] ? lot.areaUnit : 'ha'; return lot && Number(lot.areaHa) > 0 ? (u === 'ha' ? fmtHa(lot.areaHa) : inUnit(lot.areaHa, u).toLocaleString() + ' ' + UNITS[u][1]) : ''; };

    /* ---- the farm's lots, once a page (fresh after a save) ---- */
    let ROWS = null, META = { ready: true, stale: 0 };
    function rows(fresh) {
        if (!ROWS || fresh) {
            ROWS = call(D.urls.list).then((r) => { META = { ready: !!(r.data && r.data.ready), stale: (r.data && r.data.stale) || 0 }; return (r.data && r.data.rows) || []; })
                .catch(() => { ROWS = null; return []; });
        }
        return ROWS;
    }
    const changed = (lot) => { ROWS = null; window.dispatchEvent(new CustomEvent('aneelots:changed', { detail: { lot } })); };

    /* ---- what a lot has answered ---- */
    const answered = (lot) => Object.keys((lot && lot.answers) || {}).filter((k) => { const v = lot.answers[k]; return v !== undefined && v !== null && v !== '' && !(Array.isArray(v) && !v.length); });
    const has = (lot, key) => answered(lot).includes(key);
    const pageDone = (answers, page) => Object.keys(answers || {}).some((k) => D.pages[k] === page && answers[k] !== undefined && !(Array.isArray(answers[k]) && !answers[k].length));
    const legacy = (lot, crop) => (window.aneeFieldCard ? window.aneeFieldCard.legacy((lot && lot.answers) || {}, crop || lastCrop(lot && lot.answers)) : Promise.resolve({}));
    const payload = (lot) => !lot ? {} : ({ lotId: lot.id, location: lot.where || lot.place || '', place: lot.place || '', areaHa: lot.areaHa || null,
        fieldProfile: lot.count ? clone(lot.answers) : null, fieldProfileFrom: lot.count ? lot.id : null });

    /* ---- the sheets: open, close, and wait for the close ---- */
    const lock = () => document.documentElement.classList.toggle('fq-lock', !!document.querySelector('.fq-sheet[open]'));
    function sheetOpen(dlg) {
        if (!dlg || dlg.open || !dlg.showModal) return;
        dlg.showModal();
        lock();
        if (window.registerOverlay) window.registerOverlay('fq:' + dlg.id, () => sheetClose(dlg));
        dlg.querySelector('.fq-box').focus({ preventScroll: true });
    }
    function sheetClose(dlg) {
        if (!dlg.open || dlg.classList.contains('is-closing')) return;
        if (window.unregisterOverlay) window.unregisterOverlay('fq:' + dlg.id);
        const done = () => { dlg.classList.remove('is-closing'); dlg.close(); lock(); dlg.dispatchEvent(new Event('fqclosed')); };
        if (reduce()) { done(); return; }
        dlg.classList.add('is-closing');
        setTimeout(done, 230);
    }
    const closed = (dlg) => new Promise((r) => dlg.addEventListener('fqclosed', r, { once: true }));
    function bind(dlg) {
        if (!dlg || dlg.dataset.flBound) return;
        dlg.dataset.flBound = '1';
        dlg.addEventListener('click', (e) => { if (e.target === dlg || e.target.closest('[data-fq-close]')) sheetClose(dlg); });
        dlg.addEventListener('cancel', (e) => { e.preventDefault(); sheetClose(dlg); });
    }

    /* The crop whose troubles the "what troubled the last crop" question lists: the lot's last
       crop when it says one, else the analysis's crop (a lot keeps no crop of its own). */
    function lastCrop(a, fallback) {
        const pc = (a || {}).prevCrop || '';
        if (pc.startsWith('rice')) return 'rice';
        if (pc === 'corn') return 'corn_yellow';
        if (pc === 'legume') return 'mungbean';
        return fallback || null;
    }

    /* ---- a list to pick from (provinces, towns, barangays, units) ---- */
    function pickFrom(o) {
        const dlg = $('flPickSheet');
        bind(dlg);
        $('flPickTitle').firstChild.textContent = o.title;
        $('flPickSub').textContent = o.sub || '';
        const q = $('flPickQ');
        q.value = '';
        q.placeholder = o.find || 'Search';
        let result = null;
        const paint = () => {
            const t = q.value.trim().toLowerCase();
            const items = (o.items || []).filter((x) => !t || (x.l + ' ' + (x.s || '') + ' ' + (x.f || '')).toLowerCase().includes(t));
            $('flPickList').innerHTML = (o.none ? '<button type="button" class="fq-row" data-v=""><span class="fq-ri">✖️</span><span class="fq-rt"><b>' + esc(o.none) + '</b></span></button>' : '')
                + (items.length ? items.slice(0, 400).map((x) => '<button type="button" class="fq-row" role="option" data-v="' + esc(x.v) + '" aria-checked="' + (x.v === o.value) + '">'
                    + (x.i ? '<span class="fq-ri">' + esc(x.i) + '</span>' : '') + '<span class="fq-rt"><b>' + esc(x.l) + '</b>' + (x.s ? '<small>' + esc(x.s) + '</small>' : '') + '</span>'
                    + (x.v === o.value ? '<span class="fq-ck">' + CHECK + '</span>' : '') + '</button>').join('')
                    : '<p class="fq-nothing">' + esc(o.empty || 'Nothing by that name.') + '</p>');
        };
        q.oninput = paint;
        $('flPickList').onclick = (e) => {
            const b = e.target.closest('[data-v]');
            if (!b) return;
            result = { v: b.getAttribute('data-v') };
            sheetClose(dlg);
        };
        paint();
        const p = closed(dlg).then(() => result);
        sheetOpen(dlg);
        $('flPickList').scrollTop = 0;
        if (matchMedia('(pointer: fine)').matches && (o.items || []).length > 8) setTimeout(() => q.focus(), 60);
        return p;
    }
    const PLACES = {};
    function places(province, town) {
        const key = (province || '') + '|' + (town || '');
        if (!PLACES[key]) {
            const qs = new URLSearchParams();
            if (province) qs.set('province', province);
            if (town) qs.set('town', town);
            PLACES[key] = call(D.urls.places + (qs.toString() ? '?' + qs.toString() : '')).then((r) => (r.data && r.data.items) || [])
                .catch((err) => { delete PLACES[key]; throw err; });
        }
        return PLACES[key];
    }

    /* ---- one lot, in its sheet ---- */
    let editing = null;
    function edit(lot, o) {
        o = o || {};
        const dlg = $('flEditSheet');
        bind(dlg);
        const st = {
            id: lot && lot.id ? lot.id : null, name: (lot && lot.name) || '', province: (lot && lot.province) || '', town: (lot && lot.town) || '',
            barangay: (lot && lot.barangay) || '', crop: o.crop || '',
            areaUnit: lot && UNITS[lot.areaUnit] ? lot.areaUnit : 'ha', area: '',
            answers: clone(lot && lot.answers), stale: !!(lot && lot.stale), updated: (lot && lot.updated) || '',
        };
        if (lot && Number(lot.areaHa) > 0) st.area = String(inUnit(lot.areaHa, st.areaUnit));
        // A crop the catalogue keys differently (corn is yellow corn, every rice is rice).
        // The analysis's crop, keyed as the catalogue keys it, only lists the troubles: it is not saved.
        if (st.crop && !CROPS[st.crop]) st.crop = String(st.crop).startsWith('rice') ? 'rice' : (st.crop === 'corn' ? 'corn_yellow' : '');
        editing = st;
        $('flEditTitle').firstChild.textContent = st.id ? st.name || 'Your lot' : 'New lot';
        $('flEditSub').textContent = o.reason === 'place' ? 'Say where it is, so Anee reads the right weather' : 'Where it is, its size, and its soil and water';
        let result = null;
        const body = $('flEditBody');
        const tag = (id, icon, label, value, none, disabled) => '<button type="button" class="fl-tag' + (value ? '' : ' is-none') + '" id="' + id + '" aria-haspopup="dialog"' + (disabled ? ' disabled' : '') + '>'
            + '<span class="ti" aria-hidden="true">' + icon + '</span><span class="tt"><small>' + esc(label) + '</small><b>' + esc(value || none) + '</b></span>' + CHEV + '</button>';
        const paintPlace = () => {
            const box = $('flPlace');
            if (!box) return;
            box.innerHTML = D.ph
                ? tag('flProv', '🗺️', 'Province', st.province, 'Choose the province', false)
                    + tag('flTown', '🏘️', 'Town or city', st.town, st.province ? 'Choose the town or city' : 'Pick the province first', !st.province)
                    + tag('flBrgy', '📍', 'Barangay', st.barangay, st.town ? 'Choose the barangay' : 'Pick the town first', !st.town)
                : '<input type="text" class="form-input" id="flTownIn" maxlength="120" placeholder="Town or city" value="' + esc(st.town) + '">'
                    + '<input type="text" class="form-input" id="flProvIn" maxlength="120" placeholder="Province, state or region" value="' + esc(st.province) + '">';
        };
        const paintQ = () => {
            const box = $('flQ');
            if (!box) return;
            box.innerHTML = prevHtml({ name: st.name, answers: st.answers }, { sheet: true });
            const pv = box.firstElementChild;
            pv.style.animation = 'none';
            wirePrev(pv);
        };
        body.innerHTML = (st.stale ? '<div class="fl-warn">These answers were saved on ' + esc(st.updated) + ', over six months ago. Check each one and change what is different now, then save.</div>' : '')
            + '<div class="fl-f"><label class="fl-lab" for="flName">Name of the lot</label><input type="text" class="form-input" id="flName" maxlength="120" placeholder="Like North field, or the lot by the creek" value="' + esc(st.name) + '"></div>'
            + '<div class="fl-f"><span class="fl-lab">Where is it?</span><p class="fl-sub">The weather, the season and the advice follow the place.</p><div class="fl-tags is-3" id="flPlace"></div></div>'
            + '<div class="fl-f"><label class="fl-lab" for="flArea">How big is it? <small>(optional)</small></label><div class="fl-size"><input type="number" class="form-input" id="flArea" min="0" step="any" inputmode="decimal" placeholder="e.g. 1.5" value="' + esc(st.area) + '">'
            + '<button type="button" class="fl-tag" id="flUnit" aria-haspopup="dialog"><span class="ti" aria-hidden="true">📐</span><span class="tt"><small>Unit</small><b>' + esc(UNITS[st.areaUnit][0]) + '</b></span>' + CHEV + '</button></div></div>'
            + '<div class="fl-f"><span class="fl-lab">Its soil, water and last season</span><div id="flQ"></div></div>'
            + '<div class="fl-f" id="flTests"></div>'
            + '<p class="fl-err" id="flErr" hidden></p>';
        // The soil test log (2026-10-16, the owner): kept with the lot when it is saved.
        const paintTests = () => { const box = $('flTests'); if (box && TESTS()) box.innerHTML = TESTS().html(st.answers); };
        paintPlace(); paintQ(); paintTests();
        const err = (msg, el) => { const e = $('flErr'); e.textContent = msg; e.hidden = !msg; if (el && !reduce()) { el.classList.remove('fl-shake'); void el.offsetWidth; el.classList.add('fl-shake'); } };
        const bump = (el) => { if (el && !reduce()) { el.classList.remove('is-bump'); void el.offsetWidth; el.classList.add('is-bump'); } };

        body.onclick = async (e) => {
            const t = e.target.closest('button');
            if (!t || t.disabled) return;
            if (t.matches('[data-st-add], [data-st-edit]') && TESTS()) {
                if (await TESTS().editIn(st.answers, t.dataset.stEdit || null)) { paintTests(); paintQ(); }
                return;
            }
            try {
                if (t.id === 'flProv') {
                    const list = await places();
                    const r = await pickFrom({ title: 'Choose the province', items: list.map((n) => ({ v: n, l: n })), value: st.province, find: 'Search a province' });
                    if (r && r.v && r.v !== st.province) { st.province = r.v; st.town = ''; st.barangay = ''; paintPlace(); bump($('flProv')); }
                } else if (t.id === 'flTown') {
                    const list = await places(st.province);
                    const r = await pickFrom({ title: 'Choose the town or city', sub: st.province, items: list.map((n) => ({ v: n, l: n })), value: st.town, find: 'Search a town or city' });
                    if (r && r.v && r.v !== st.town) { st.town = r.v; st.barangay = ''; paintPlace(); bump($('flTown')); }
                } else if (t.id === 'flBrgy') {
                    const list = await places(st.province, st.town);
                    const r = await pickFrom({ title: 'Choose the barangay', sub: st.town + ', ' + st.province, items: list.map((n) => ({ v: n, l: n })), value: st.barangay,
                        find: 'Search a barangay', none: st.barangay ? 'No barangay' : null, empty: 'No barangay by that name.' });
                    if (r) { st.barangay = r.v || ''; paintPlace(); bump($('flBrgy')); }
                } else if (t.id === 'flUnit') {
                    const r = await pickFrom({ title: 'The size is in', sub: 'The number you typed stays: pick the unit it is in', value: st.areaUnit,
                        items: Object.entries(UNITS).map(([k, u]) => ({ v: k, l: u[0], s: u[3], i: k === 'ha' ? '🌾' : k === 'sqm' ? '📏' : '🗺️' })) });
                    if (r && r.v && UNITS[r.v]) { st.areaUnit = r.v; t.querySelector('b').textContent = UNITS[r.v][0]; bump(t); }
                } else if (t.id === 'flQBtn') {
                    await askQuestions();
                }
            } catch (_) {
                window.toast?.('The list could not load. Please try again.', 'error');
            }
        };
        const read = () => {
            st.name = $('flName').value.trim();
            st.area = $('flArea').value.trim();
            if (!D.ph) { st.town = ($('flTownIn') || {}).value?.trim() || ''; st.province = ($('flProvIn') || {}).value?.trim() || ''; }
        };
        async function askQuestions() {
            read();
            if (!window.aneeFieldProfile) return;
            const got = await window.aneeFieldProfile.open({ crop: lastCrop(st.answers, st.crop), value: st.answers, title: st.name || 'Your lot', saved: false });
            if (got) { st.answers = got; paintQ(); paintTests(); }
        }
        $('flEditSave').onclick = async () => {
            read();
            if (!st.name) { err('Give the lot a name.', $('flName')); $('flName').focus(); return; }
            if (!st.town || !st.province) { err(D.ph ? 'Choose the province and the town or city.' : 'Type the town and the province or state.', $('flPlace')); return; }
            err('');
            // A new lot is not done until its questions are asked: the first save walks them.
            if (!st.id && !(window.aneeFieldProfile && window.aneeFieldProfile.count(st.answers)) && o.ask !== false) await askQuestions();
            const btn = $('flEditSave');
            btn.disabled = true;
            try {
                const body = { name: st.name, province: st.province, town: st.town, barangay: st.barangay || null, areaHa: Number(st.area) > 0 ? Number(st.area) * UNITS[st.areaUnit][2] : null, areaUnit: st.areaUnit,
                    crop: null, answers: st.answers };
                const r = await call(st.id ? D.urls.update + '/' + st.id : D.urls.save, { method: 'POST', body });
                result = r.data.row;
                window.toast?.(r.message, 'success');
                changed(result);
                sheetClose(dlg);
            } catch (e) {
                err(e.message || 'The lot could not be saved. Please try again.');
            } finally { btn.disabled = false; }
        };
        const p = closed(dlg).then(() => { editing = null; return result; });
        sheetOpen(dlg);
        $('flEditBody').scrollTop = 0;
        return p;
    }

    async function confirm(lot) {
        const r = await call(D.urls.update + '/' + lot.id + '/checked', { method: 'POST' });
        window.toast?.(r.message, 'success');
        changed(r.data.row);
        return r.data.row;
    }

    /* ---- a lot as a card ---- */
    function card(lot, on, i) {
        const chips = [];
        if (lot.areaHa) chips.push('<span class="fl-chip">' + esc(fmtSize(lot)) + '</span>');
        chips.push(lot.count ? '<span class="fl-chip">' + lot.count + ' answer' + (lot.count === 1 ? '' : 's') + '</span>' : '<span class="fl-chip is-none">No answers yet</span>');
        if (lot.stale) chips.push('<span class="fl-chip is-old" title="Saved on ' + esc(lot.updated) + '">' + CLOCK + 'Check it</span>');
        return '<button type="button" class="fl-card' + (on ? ' is-on' : '') + '" data-fl-lot="' + lot.id + '" role="radio" aria-checked="' + !!on + '" style="animation-delay:' + Math.min(i || 0, 8) * 30 + 'ms">'
            + '<span class="fl-ic" aria-hidden="true"><img src="' + esc(D.icon) + '" alt=""></span>'
            + '<span class="fl-tx"><b>' + esc(lot.name) + '</b>' + (lot.place ? '<small>' + esc(lot.place) + '</small>' : '<small class="is-none">No place yet: tap to add it</small>')
            + '<span class="fl-chips">' + chips.join('') + '</span></span><span class="fl-ck">' + CHECK + '</span></button>';
    }

    /* ---- the picked lot's answers, as cards to slide through ---- */
    const PREV_ICON = { lot: '🗺️', soil: '🟤', water: '💧', acid: '🧪', tests: '🧫', land: '⛰️', last: '🌾', weeds: '🌿', problems: '🐛' };
    const PREV_NAME = { lot: 'The lot', soil: 'Soil', water: 'Water', acid: 'Acidity and lime', tests: 'Soil tests', land: 'The land', last: 'Last season', weeds: 'Weeds', problems: 'Troubles' };
    const TESTS = () => (window.aneeFieldProfile && window.aneeFieldProfile.tests) || null;
    const ARROW = (d) => '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="' + (d < 0 ? 'M15 5l-7 7 7 7' : 'M9 5l7 7-7 7') + '"/></svg>';
    const troubleName = (k) => k.startsWith('other:') ? (D.others[k.slice(6)] || k.slice(6)) : k.replace(/^[a-z]+:/, '').replace(/-/g, ' ').replace(/^./, (c) => c.toUpperCase());
    function prevCards(lot, o) {
        const a = (lot && lot.answers) || {};
        const said = (v) => !(v === undefined || v === null || v === '' || (Array.isArray(v) && !v.length));
        const cards = o.sheet ? [] : [{ key: 'lot', rows: [['Where is it?', lot.place || ''], ['How big is it?', fmtSize(lot)], ['Last checked', lot.updated ? lot.updated + (lot.stale ? ', due for a check' : '') : '']], lot: true }];
        Object.keys(D.groups).forEach((g) => {
            const rows = [];
            Object.entries(D.qs).forEach(([k, q]) => {
                if (q[1] !== g) return;
                const v = a[k];
                let words = '';
                if (said(v)) {
                    words = k === 'outbreaks' ? ([].concat(v).includes('none') ? 'None' : [].concat(v).map(troubleName).join(', '))
                        : [].concat(v).map((x) => (q[2] && q[2][x]) || x).join(', ');
                }
                rows.push([q[3] || q[0], words]);
            });
            cards.push({ key: g, rows });
            // The soil test log follows the acidity: each result by its date, newest first.
            const T = TESTS();
            if (g === 'acid' && T) {
                const list = T.list(a);
                cards.push({ key: 'tests', extra: true, none: 'No soil test logged. A test is optional.', rows: list.length ? list.map((t) => [T.date(t), T.words(t)]) : [['Soil test', '']] });
            }
        });
        return cards;
    }
    function prevHtml(lot, o) {
        o = o || {};
        const cards = prevCards(lot, o);
        const n = cards.length;
        const asked = cards.filter((c) => !c.lot && !c.extra).reduce((t, c) => t + c.rows.length, 0);
        const got = cards.filter((c) => !c.lot && !c.extra).reduce((t, c) => t + c.rows.filter((r) => r[1]).length, 0);
        const body = cards.map((cd, i) => {
            const done = cd.rows.filter((r) => r[1]).length;
            return '<article class="fl-pc" aria-label="' + esc(PREV_NAME[cd.key]) + '"><header><span aria-hidden="true">' + PREV_ICON[cd.key] + '</span>'
                + '<div><b>' + esc(PREV_NAME[cd.key]) + '</b>' + (cd.lot ? '<small>Where it is and its size</small>'
                    : cd.extra ? '<small' + (done ? ' class="is-done"' : '') + '>' + (done ? done + (done === 1 ? ' result logged' : ' results logged') : 'Optional') + '</small>'
                    : '<small' + (done === cd.rows.length ? ' class="is-done"' : '') + '>' + (done ? done + ' of ' + cd.rows.length + ' answered' : 'Not answered yet') + '</small>') + '</div>'
                + '<em>' + (i + 1) + ' of ' + n + '</em></header>'
                + '<ul class="fl-qa">' + cd.rows.map((r) => '<li' + (r[1] ? '' : ' class="is-empty"') + '><span>' + esc(r[0]) + '</span><b>' + esc(r[1] || cd.none || 'Not answered yet') + '</b></li>').join('') + '</ul>'
                + '</article>';
        }).join('');
        const head = o.sheet
            ? '<div class="fl-prev-h"><b>' + (got ? got + ' of ' + asked + ' questions answered' : 'Not answered yet') + '<small>' + (got ? 'Slide through them below. Change any that is different now.' : 'Every analysis reads these answers, so they are asked once, here.') + '</small></b>'
                + '<button type="button" id="flQBtn"' + (got ? '' : ' class="is-main"') + '>' + (got ? 'Change the answers' : 'Answer the questions') + '</button></div>'
            : '<div class="fl-prev-h"><b>Its answers<small>' + got + ' of ' + asked + ' questions answered</small></b><button type="button" data-fl-edit>Edit the lot</button></div>';
        return '<div class="fl-prev' + (o.sheet ? ' is-sheet' : '') + '" data-fl-prev>'
            + head
            + '<div class="fl-prev-row" tabindex="0" role="group" aria-label="' + esc(lot.name || 'The lot') + ': its answers, ' + n + ' cards">' + body + '</div>'
            + '<div class="fl-prev-f"><span class="fl-dots" aria-hidden="true">' + cards.map((_, i) => '<i' + (i ? '' : ' class="is-on"') + '></i>').join('') + '</span>'
            + '<span class="fl-hint" aria-hidden="true"><svg class="fl-touch" viewBox="0 0 52 24"><path class="fl-trail" d="M46 12H10" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-dasharray="36"/>'
            + '<g class="fl-finger"><circle class="fl-f1" cx="30" cy="12" r="8.5"/><circle class="fl-f2" cx="30" cy="12" r="5"/></g></svg>'
            + '<span class="fl-touch">Slide to see more</span><span class="fl-mouse">More with the arrows</span></span>'
            + '<span class="fl-arrows"><button type="button" data-fl-go="-1" aria-label="Back one card" disabled>' + ARROW(-1) + '</button><button type="button" data-fl-go="1" aria-label="Next card">' + ARROW(1) + '</button></span></div>'
            + '</div>';
    }
    // Wire one preview: the dots follow the slide, the hint leaves after the first one.
    function wirePrev(box) {
        const row = box.querySelector('.fl-prev-row');
        const dots = [...box.querySelectorAll('.fl-dots i')];
        const hint = box.querySelector('.fl-hint');
        const [back, next] = box.querySelectorAll('[data-fl-go]');
        const step = () => { const c = row.querySelector('.fl-pc'); return c ? c.getBoundingClientRect().width + 9.6 : row.clientWidth; };
        let raf = 0, slid = false;
        const sync = () => {
            raf = 0;
            const i = Math.max(0, Math.min(dots.length - 1, Math.round(row.scrollLeft / step())));
            dots.forEach((d, k) => d.classList.toggle('is-on', k === i));
            const end = row.scrollLeft + row.clientWidth >= row.scrollWidth - 4;
            if (back) back.disabled = row.scrollLeft < 4;
            if (next) next.disabled = end;
            if (row.scrollLeft > 8 && !slid) { slid = true; hint.classList.add('is-gone'); }
        };
        row.addEventListener('scroll', () => { if (!raf) raf = requestAnimationFrame(sync); }, { passive: true });
        box.addEventListener('click', (e) => {
            const b = e.target.closest('[data-fl-go]');
            if (!b) return;
            row.scrollBy({ left: Number(b.dataset.flGo) * step(), behavior: reduce() ? 'auto' : 'smooth' });
        });
        row.addEventListener('keydown', (e) => {
            if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
            e.preventDefault();
            row.scrollBy({ left: (e.key === 'ArrowRight' ? 1 : -1) * step(), behavior: reduce() ? 'auto' : 'smooth' });
        });
        // Everything fits (a wide screen): nothing to slide, so no hint and no arrows.
        requestAnimationFrame(() => { if (row.scrollWidth <= row.clientWidth + 4) { hint.classList.add('is-gone'); box.querySelector('.fl-arrows').style.display = 'none'; } sync(); });
    }

    /* The same cards anywhere (Global Lots, the Land Prep and Anee Auto lot cards): opened in a
       host, closed again on a second tap. Its own "Edit the lot" opens the lot's sheet. */
    function preview(host, lot, o) {
        o = o || {};
        host.innerHTML = prevHtml(lot);
        const box = host.firstElementChild;
        if (o.still) box.style.animation = 'none';
        wirePrev(box);
        box.addEventListener('click', async (e) => {
            if (!e.target.closest('[data-fl-edit]')) return;
            const row = await edit(lot, { crop: o.crop || null, ask: false });
            if (!row) return;
            if (o.onEdit) o.onEdit(row); else preview(host, row, Object.assign({}, o, { still: true }));
        });
        return box;
    }
    function togglePreview(host, lot, o) {
        if (!host || !lot) return false;
        const open = host.querySelector(':scope > .fl-prev');
        if (open) {
            if (reduce()) { open.remove(); return false; }
            open.classList.add('is-out');
            setTimeout(() => open.remove(), 240);
            return false;
        }
        preview(host, lot, o);
        return true;
    }

    /* ---- the picker an analysis wears ---- */
    function picker(el, o) {
        o = o || {};
        const crop = () => (typeof o.crop === 'function' ? o.crop() : o.crop) || null;
        const st = { id: o.value || null, lot: null, all: [] };
        el.classList.add('fl-pick');
        el.setAttribute('role', 'radiogroup');
        async function paint(fresh) {
            if (!st.all.length) el.innerHTML = '<p class="fl-wait">Loading your lots…</p>';
            st.all = await rows(fresh);
            st.lot = st.all.find((l) => l.id === st.id) || null;
            if (st.id && !st.lot) st.id = null;
            el.innerHTML = (st.all.length ? st.all.map((l, i) => card(l, l.id === st.id, i)).join('')
                : '<div class="fl-empty">No lots yet. Add your first one: where it is, its size, and its soil and water. You answer it once, and every analysis reads it.</div>')
                + '<button type="button" class="fl-add" data-fl-add>' + PLUS + (st.all.length ? 'Add another lot' : 'Add a lot') + '</button>'
                + '<div class="fl-old" data-fl-old hidden></div>'
                + '<p class="fl-more"><span>Your lots are kept in Global Lots.</span><a href="' + esc(D.urls.page) + '" target="_blank" rel="noopener">Open Global Lots</a></p>';
            paintOld();
            paintPrev(false);
        }
        // The picked lot's answers open right under its card.
        function paintPrev(animate) {
            const old = el.querySelector('[data-fl-prev]');
            const card = st.lot ? el.querySelector('[data-fl-lot="' + st.lot.id + '"]') : null;
            if (old && (!card || old.previousElementSibling !== card || animate)) {
                if (animate && !reduce()) { old.classList.add('is-out'); old.removeAttribute('data-fl-prev'); setTimeout(() => old.remove(), 240); }
                else old.remove();
            } else if (old) {
                return;
            }
            if (!card) return;
            const tpl = document.createElement('div');
            tpl.innerHTML = prevHtml(st.lot);
            const box = tpl.firstChild;
            if (!animate) box.style.animation = 'none';
            card.after(box);
            wirePrev(box);
        }
        function paintOld() {
            const box = el.querySelector('[data-fl-old]');
            if (!box) return;
            const l = st.lot;
            if (!l || !l.stale) { box.hidden = true; return; }
            box.innerHTML = '<span><b>' + esc(l.name) + '</b> was last checked on ' + esc(l.updated) + ', over six months ago. Are its answers still right?</span>'
                + '<div><button type="button" class="is-main" data-fl-check>Check them</button><button type="button" data-fl-fine>Still correct</button></div>';
            box.hidden = false;
        }
        function pick(lot) {
            st.id = lot ? lot.id : null;
            st.lot = lot;
            el.querySelectorAll('.fl-card').forEach((c) => { const on = Number(c.getAttribute('data-fl-lot')) === st.id; c.classList.toggle('is-on', on); c.setAttribute('aria-checked', on ? 'true' : 'false'); });
            paintOld();
            paintPrev(true);
            if (o.onPick) o.onPick(lot ? clone(lot) : null);
        }
        el.addEventListener('click', async (e) => {
            const add = e.target.closest('[data-fl-add]');
            if (add) { const row = await edit(null, { crop: crop() }); if (row) { st.id = row.id; await paint(true); pick(st.lot); } return; }
            if (e.target.closest('[data-fl-edit]') && st.lot) { const row = await edit(st.lot, { crop: crop(), ask: false }); if (row) { await paint(true); pick(st.lot); } return; }
            if (e.target.closest('[data-fl-check]') && st.lot) { const row = await edit(st.lot, { crop: crop() }); if (row) { await paint(true); pick(st.lot); } return; }
            if (e.target.closest('[data-fl-fine]') && st.lot) { try { await confirm(st.lot); await paint(true); pick(st.lot); } catch (err) { window.toast?.(err.message, 'error'); } return; }
            const c = e.target.closest('[data-fl-lot]');
            if (!c) return;
            const lot = st.all.find((l) => l.id === Number(c.getAttribute('data-fl-lot')));
            if (!lot) return;
            // A lot saved before the lots had a place: say where it is first.
            if (!lot.where) {
                const row = await edit(lot, { crop: crop(), reason: 'place', ask: false });
                if (!row) return;
                st.id = row.id; await paint(true); pick(st.lot); return;
            }
            pick(lot);
        });
        window.addEventListener('aneelots:changed', () => { paint(true).then(() => { if (st.lot && o.onPick) o.onPick(clone(st.lot)); }); });
        paint();
        return {
            el,
            value: () => (st.lot ? clone(st.lot) : null),
            set: (id) => { st.id = id || null; return paint().then(() => { if (st.lot) pick(st.lot); }); },
            refresh: () => paint(true),
        };
    }

    window.aneeLots = { picker, edit, rows, answered, has, legacy, payload, confirm, card, meta: () => META, staleDays: D.staleDays, crops: CROPS, fmtHa, fmtSize, units: UNITS, preview, togglePreview };
})();
</script>
@endpush
@endonce
