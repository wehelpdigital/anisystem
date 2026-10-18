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
       aneeLots.togglePreview(host, lot, { crop, onEdit, onSaved }) -> bool   the lot's answer card,
                                     opened in (or closed from) any host element: its name and place,
                                     "N of M answered" with a bar, four key answers (soil, water,
                                     acidity, last crop) and the yellow "Review all answers".
                                     "Edit the lot" calls onEdit(lot) when given (the caller's own
                                     sheet), else the kit's sheet. onSaved(lot) after the review saved.
       aneeLots.review(lot, { crop, commit, onSaved }) -> Promise<lot | null>   every answer page by
                                     page in one sheet (#flReviewSheet), each with an Edit that opens
                                     that one question (aneeFieldProfile.ask). A change is saved at once
                                     through saveAnswers (answers only); closing fires aneelots:changed
                                     once. commit(answers) -> Promise<{answers}> keeps it elsewhere.
       aneeLots.saveAnswers(lot, answers) -> Promise<lot>   POST /app/farm-lots/{id} with the answers only
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

    /* A lot's answers (2026-10-19, the owner): no card opens under a picked lot. It wears two
       buttons, "Preview answers" (every answer page by page in one sheet, flReviewSheet, each
       with its own Edit) and "Edit answers" (every question in one form, aneeFieldProfile.ask). */
    .fl-card { transition: border-color .28s var(--fl-e), background-color .28s var(--fl-e), transform .28s var(--fl-e), box-shadow .28s var(--fl-e), border-radius .28s var(--fl-e); }
    /* The picked lot and its buttons are one container: square inner corners, one fill, one border, a soft divider. */
    .fl-card.is-on.has-acts { border-bottom-left-radius: 0; border-bottom-right-radius: 0; border-bottom-color: transparent; box-shadow: inset 1px 0 0 #4a7c2a, inset -1px 0 0 #4a7c2a, inset 0 1px 0 #4a7c2a; }
    @media (hover: hover) { .fl-card.is-on.has-acts:hover { transform: none; box-shadow: inset 1px 0 0 #4a7c2a, inset -1px 0 0 #4a7c2a, inset 0 1px 0 #4a7c2a; } }
    .fl-ans { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .45rem; min-width: 0; }
    .fl-ans.is-join { position: relative; margin-top: calc(-.55rem - 1.5px); padding: .2rem .8rem .75rem; border-radius: 0 0 1.1rem 1.1rem; background: #f3f8ec; border: 1.5px solid #4a7c2a; border-top: 0;
        box-shadow: inset 1px 0 0 #4a7c2a, inset -1px 0 0 #4a7c2a, inset 0 -1px 0 #4a7c2a; }
    .fl-ans.is-join::before { content: ''; position: absolute; left: .8rem; right: .8rem; top: 0; height: 1px; background: #d3e4bf; }
    .fl-ans.is-join .fl-ans-b { margin-top: .55rem; }
    .fl-ans.is-under { margin-top: .5rem; }
    .fl-ans[hidden] { display: none; }
    .fl-ans-b { display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-width: 0; min-height: 2.75rem; padding: .35rem .6rem; border-radius: 999px; font-size: .82rem; font-weight: 800; line-height: 1.15;
        color: #2d5016; background: #fff; border: 1.5px solid #cfe0bd; transition: background-color .28s var(--fl-e), border-color .28s var(--fl-e), transform .28s var(--fl-e); }
    .fl-ans-b span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fl-ans-b svg { flex: none; width: 1rem; height: 1rem; }
    .fl-ans-b:hover { background: #f4f9ee; border-color: #86b556; }
    .fl-ans-b:active { transform: scale(.97); }
    .fl-ans-b:focus-visible { outline: 2px solid #4a7c2a; outline-offset: 2px; }
    .fl-ans-b.is-edit { background: #e4efd4; border-color: #b9d39c; }
    .fl-ans-b.is-edit:hover { background: #d9e9c4; border-color: #86b556; }
    @media (max-width: 400px) { .fl-ans-b { gap: .3rem; padding-left: .45rem; padding-right: .45rem; font-size: .8rem; } .fl-ans-b svg { width: .92rem; height: .92rem; } }
    @media (max-width: 340px) { .fl-ans { grid-template-columns: 1fr; } }
    /* How many are answered. */
    .fl-prog { display: grid; gap: .4rem; }
    .fl-prog-t { display: flex; align-items: baseline; justify-content: space-between; gap: .6rem; min-width: 0; font-size: .82rem; color: #4b5a3e; }
    .fl-prog-t b { font-weight: 800; color: #1f3a10; }
    .fl-prog-t span { flex: none; font-size: .74rem; font-weight: 800; color: #5f6e51; }
    .fl-bar { height: .5rem; border-radius: 999px; background: #e1ead5; overflow: hidden; }
    .fl-bar i { display: block; height: 100%; width: 0; border-radius: inherit; background: linear-gradient(90deg, #86b556, #4a7c2a); transition: width .55s var(--fl-e); }
    .fl-bar.is-full i { background: linear-gradient(90deg, #4a7c2a, #2d5016); }
    /* The answers inside the lot's own sheet. */
    .fl-sa { display: grid; gap: .65rem; padding: .85rem; border-radius: 1.1rem; background: linear-gradient(160deg, #fbfdf8, #f2f8ea); border: 1px solid #e0ebd3; }
    .fl-sa .fl-sub { margin: 0; }
    .fl-walk { justify-self: center; min-height: 2.75rem; padding: .3rem .8rem; font-size: .8rem; font-weight: 800; color: #3d6823; text-decoration: underline; text-underline-offset: 3px; }
    /* The yellow call: a new lot's questions. */
    .fl-review { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; min-height: 3rem; padding: .55rem 1.1rem; border-radius: 999px; font-size: .92rem; font-weight: 800;
        color: #3b2f00; background: #f5c518; box-shadow: 0 10px 22px -14px rgb(245 197 24 / .95); transition: background-color .28s var(--fl-e), transform .28s var(--fl-e), box-shadow .28s var(--fl-e); }
    .fl-review:hover { background: #ffd23f; box-shadow: 0 14px 26px -14px rgb(245 197 24); }
    .fl-review:active { transform: scale(.98); }
    .fl-review:focus-visible { outline: 3px solid #3b2f00; outline-offset: 2px; }
    .fl-review svg { flex: none; width: 1.05rem; height: 1.05rem; animation: flNudge 2.6s var(--fl-e) infinite; }
    @keyframes flNudge { 0%, 55%, 100% { transform: none; } 70% { transform: translateX(4px); } 85% { transform: none; } }

    /* Preview answers (flReviewSheet): the lot, then a section a page, an Edit a question. */
    #flReviewSheet .fq-head h2 small { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fl-rv-top { flex: none; display: grid; gap: .45rem; padding: .1rem 1.15rem .8rem; border-bottom: 1px solid #eef2ea; }
    .fl-rv-sum { display: flex; align-items: center; gap: .6rem; min-width: 0; }
    .fl-rv-sum .fl-prog-t { flex: 1; }
    .fl-rv-all { flex: none; display: inline-flex; align-items: center; gap: .35rem; min-height: 2.75rem; padding: .3rem .9rem; border-radius: 999px; font-size: .8rem; font-weight: 800;
        color: #2d5016; background: #e4efd4; border: 1.5px solid #b9d39c; transition: background-color .28s var(--fl-e), border-color .28s var(--fl-e), transform .28s var(--fl-e); }
    .fl-rv-all:hover { background: #d9e9c4; border-color: #86b556; }
    .fl-rv-all:active { transform: scale(.97); }
    .fl-rv-all svg { width: .9rem; height: .9rem; }
    .fl-rv { padding-top: 0; }
    .fl-rs { margin-top: .35rem; }
    .fl-rs[hidden], .fl-rq[hidden], .fl-rv-none[hidden] { display: none; }
    .fl-rs-h { position: sticky; top: 0; z-index: 1; display: flex; align-items: center; gap: .55rem; margin: 0; padding: .75rem 0 .5rem; background: #fff; }
    .fl-rs-h > span { flex: none; width: 2.05rem; height: 2.05rem; border-radius: .7rem; display: grid; place-items: center; font-size: 1.05rem; background: #f1f6ea; }
    .fl-rs-h b { flex: 1; min-width: 0; font-size: .96rem; font-weight: 800; line-height: 1.25; color: #14210c; }
    .fl-rs-h em { flex: none; display: inline-flex; align-items: center; gap: .25rem; padding: .18rem .6rem; border-radius: 999px; font-style: normal; font-size: .72rem; font-weight: 800;
        color: #5b6b4c; background: #f1f4ec; transition: color .28s var(--fl-e), background-color .28s var(--fl-e); }
    .fl-rs-h em.is-done { color: #2d5016; background: #e4efd4; }
    .fl-rs-h em svg { width: .72rem; height: .72rem; }
    .fl-qa { display: grid; border-radius: 1rem; background: #fff; border: 1px solid #e5ece0; overflow: hidden; }
    .fl-rs.is-lot .fl-qa { background: #f8faf6; }
    .fl-rq { display: flex; align-items: center; gap: .65rem; min-width: 0; padding: .6rem .55rem .6rem .85rem; border-top: 1px solid #eef2ea; }
    .fl-rq:first-child { border-top: 0; }
    .fl-rq.is-info { padding-top: .5rem; padding-bottom: .5rem; }
    .fl-rq-t { flex: 1; min-width: 0; }
    .fl-rq-t small { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; font-size: .72rem; font-weight: 700; color: #6b7280; }
    .fl-rq-t b { display: block; margin-top: .08rem; font-size: .9rem; font-weight: 800; line-height: 1.35; color: #1f3a10; overflow-wrap: anywhere; transition: opacity .28s var(--fl-e); }
    .fl-rq.is-empty .fl-rq-t b { font-weight: 600; font-style: italic; color: #969d92; }
    .fl-rq-e { flex: none; display: inline-flex; align-items: center; justify-content: center; gap: .3rem; min-height: 2.75rem; min-width: 2.75rem; padding: .3rem .85rem; border-radius: 999px;
        font-size: .8rem; font-weight: 800; color: #3d6823; background: #fff; border: 1.5px solid #d5e3c5;
        transition: background-color .28s var(--fl-e), border-color .28s var(--fl-e), color .28s var(--fl-e), transform .28s var(--fl-e), opacity .28s var(--fl-e); }
    .fl-rq-e:hover { background: #eef5e6; border-color: #86b556; }
    .fl-rq-e:active { transform: scale(.96); }
    .fl-rq-e svg { flex: none; width: .85rem; height: .85rem; }
    .fl-rq.is-empty:not(.is-extra) .fl-rq-e { color: #fff; background: #4a7c2a; border-color: #4a7c2a; }
    .fl-rq.is-empty:not(.is-extra) .fl-rq-e:hover { background: #3d6823; border-color: #3d6823; }
    .fl-rq.is-busy .fl-rq-t b { opacity: .55; }
    .fl-rq.is-busy .fl-rq-e { pointer-events: none; opacity: .5; }
    /* The answer just saved: a soft yellow wash, the new words rising in, a Saved tag. */
    .fl-rq.is-new { animation: flRvNew 1.9s var(--fl-e); }
    .fl-rq.is-new .fl-rq-t b { animation: flRvVal .45s var(--fl-e) both; }
    @keyframes flRvNew { 0%, 55% { background-color: #fff4c7; box-shadow: inset 4px 0 0 #f5c518; } 100% { background-color: transparent; box-shadow: inset 4px 0 0 transparent; } }
    @keyframes flRvVal { from { opacity: 0; transform: translateY(5px); } }
    .fl-ok { display: inline-flex; align-items: center; gap: .2rem; padding: .05rem .45rem; border-radius: 999px; font-style: normal; font-size: .64rem; font-weight: 900; letter-spacing: .03em;
        color: #2d5016; background: #e4efd4; animation: flOk 2.6s var(--fl-e) both; }
    .fl-ok svg { width: .62rem; height: .62rem; }
    @keyframes flOk { 0% { opacity: 0; transform: scale(.8); } 12%, 80% { opacity: 1; transform: none; } 100% { opacity: 0; } }
    .fl-rv-none { margin: 1.2rem 0 .4rem; padding: 1.1rem; border-radius: 1rem; text-align: center; font-size: .9rem; line-height: 1.5; color: #2d5016; background: #f3f8ec; border: 1px dashed #b9d39c; }
    .fl-rv-note { margin: .8rem 0 0; padding: .65rem .8rem; border-radius: .9rem; font-size: .8rem; line-height: 1.5; color: #5b6472; background: #f8faf6; border: 1px solid #e8eee1; }
    /* "Only unanswered": a switch in the sheet's foot. */
    .fl-only { flex: none; display: inline-flex; align-items: center; gap: .55rem; min-height: 2.75rem; padding: .2rem .5rem .2rem .15rem; border-radius: 999px; font-size: .84rem; font-weight: 800; color: #374151; background: transparent; }
    .fl-only i { position: relative; flex: none; width: 2.6rem; height: 1.5rem; border-radius: 999px; background: #d3dbc9; transition: background-color .28s var(--fl-e); }
    .fl-only i::after { content: ''; position: absolute; top: .18rem; left: .18rem; width: 1.14rem; height: 1.14rem; border-radius: 999px; background: #fff; box-shadow: 0 1px 3px rgb(0 0 0 / .25); transition: transform .28s var(--fl-e); }
    .fl-only[aria-checked="true"] i { background: #4a7c2a; }
    .fl-only[aria-checked="true"] i::after { transform: translateX(1.1rem); }
    .fl-only:focus-visible { outline: 2px solid #86b556; outline-offset: 2px; }

    html.dark .fl-card.is-on.has-acts { border-bottom-color: transparent; box-shadow: inset 1px 0 0 #6b9f3d, inset -1px 0 0 #6b9f3d, inset 0 1px 0 #6b9f3d; }
    html.dark .fl-ans.is-join { background: #1c2914; border-color: #6b9f3d; box-shadow: inset 1px 0 0 #6b9f3d, inset -1px 0 0 #6b9f3d, inset 0 -1px 0 #6b9f3d; }
    html.dark .fl-ans.is-join::before { background: #34482a; }
    html.dark .fl-ans-b { color: #cfe6b8; background: #10150d; border-color: #3d5a26; }
    html.dark .fl-ans-b:hover { background: #1c2914; border-color: #6b9f3d; }
    html.dark .fl-ans-b.is-edit { background: #26361b; border-color: #4a6b2c; }
    html.dark .fl-ans-b.is-edit:hover { background: #2f4321; }
    html.dark .fl-prog-t, html.dark .fl-prog-t span { color: #a9b69c; }
    html.dark .fl-prog-t b { color: #d5ebc0; }
    html.dark .fl-bar { background: #26331d; }
    html.dark .fl-bar i { background: linear-gradient(90deg, #6b9f3d, #8fc25e); }
    html.dark .fl-bar.is-full i { background: linear-gradient(90deg, #8fc25e, #a5c97e); }
    html.dark .fl-sa { background: #151b12; border-color: #2b3a1c; }
    html.dark .fl-walk { color: #a5c97e; }
    html.dark .fl-review:focus-visible { outline-color: #f5c518; }
    html.dark .fl-rv-top { border-color: #222b1a; }
    html.dark .fl-rv-all { color: #cfe6b8; background: #26361b; border-color: #4a6b2c; }
    html.dark .fl-rv-all:hover { background: #2f4321; }
    html.dark .fl-rs-h { background: #151b12; }
    html.dark .fl-rs-h > span { background: #1c2616; }
    html.dark .fl-rs-h b { color: #e8efe1; }
    html.dark .fl-rs-h em { color: #a9b69c; background: #1c2616; }
    html.dark .fl-rs-h em.is-done { color: #cfe6b8; background: #22301a; }
    html.dark .fl-qa { background: #10150d; border-color: #2b3a1c; }
    html.dark .fl-rs.is-lot .fl-qa { background: #131a10; }
    html.dark .fl-rq { border-color: #22301a; }
    html.dark .fl-rq-t small { color: #9aa78d; }
    html.dark .fl-rq-t b { color: #d5ebc0; }
    html.dark .fl-rq.is-empty .fl-rq-t b { color: #75816b; }
    html.dark .fl-rq-e { color: #a5c97e; background: #151b12; border-color: #3d5a26; }
    html.dark .fl-rq-e:hover { background: #1c2914; border-color: #6b9f3d; }
    html.dark .fl-rq.is-empty:not(.is-extra) .fl-rq-e { color: #fff; background: #4a7c2a; border-color: #4a7c2a; }
    html.dark .fl-rq.is-new { animation-name: flRvNewD; }
    @keyframes flRvNewD { 0%, 55% { background-color: #3a3210; box-shadow: inset 4px 0 0 #f5c518; } 100% { background-color: transparent; box-shadow: inset 4px 0 0 transparent; } }
    html.dark .fl-ok { color: #cfe6b8; background: #22301a; }
    html.dark .fl-rv-none { color: #cfe6b8; background: #1a2114; border-color: #3d5a26; }
    html.dark .fl-rv-note { color: #a9b69c; background: #1a2114; border-color: #2b3a1c; }
    html.dark .fl-only { color: #d5e3c5; }
    html.dark .fl-only i { background: #34412b; }
    html.dark .fl-only[aria-checked="true"] i { background: #6b9f3d; }
    /* Reduced motion: no movement. The yellow wash stays: it is a color, and it says what changed. */
    @media (prefers-reduced-motion: reduce) {
        .fl-review svg, .fl-rq.is-new .fl-rq-t b, .fl-ok { animation: none !important; }
        .fl-bar i, .fl-review, .fl-ans-b, .fl-rv-all, .fl-rq-e, .fl-only i, .fl-only i::after, .fl-rs-h em, .fl-rq-t b { transition: none !important; }
    }
    html.sm-still .fl-review svg, html.sm-still .fl-rq.is-new .fl-rq-t b, html.sm-still .fl-ok { animation: none !important; }

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

{{-- Every answer of one lot, page by page, each with its own Edit (aneeLots.review). --}}
<dialog class="fq-sheet is-full" id="flReviewSheet" aria-labelledby="flReviewTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><h2 id="flReviewTitle">Its answers<small id="flReviewSub"></small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="fl-rv-top" id="flReviewTop"></div>
        <div class="fq-body fl-rv" id="flReviewBody"></div>
        <div class="fq-foot"><button type="button" class="fl-only" id="flReviewOnly" role="switch" aria-checked="false"><i aria-hidden="true"></i>Only unanswered</button><span></span><button type="button" class="btn btn-primary" data-fq-close>Done</button></div>
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
        'qs' => collect(\App\Support\FieldProfile::QUESTIONS)->map(fn ($q) => [$q['short'], $q['page'], collect($q['o'])->map(fn ($o) => $o[0])->all(), $q['q'], $q['only'] ?? null])->all(),
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
            box.innerHTML = sheetAnswersHtml(st);
        };
        // Preview answers and Edit answers, here too; a change stays in this sheet until "Save the lot".
        const here = () => ({ id: st.id, name: st.name || 'Your lot', place: [st.barangay, st.town, st.province].filter(Boolean).join(', '), answers: st.answers });
        const keepHere = async (answers) => { st.answers = answers; paintQ(); paintTests(); return { answers }; };
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
                } else if (t.matches('[data-fl-review]')) {
                    read();
                    await review(here(), { crop: st.crop, commit: keepHere });
                } else if (t.matches('[data-fl-answers]')) {
                    read();
                    await editAnswers(here(), { crop: st.crop, commit: keepHere });
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

    /* ---- a lot's answers: "Preview answers" and "Edit answers", each a sheet ----
       (2026-10-19, the owner: the "Its answers" card under a picked lot looked bad on a phone:
       "instead we want a button to preview answers, and edit answers that when clicked shows a
       modal to show the answers or edit.") No card opens under a lot any more: the picked lot
       carries the two buttons, and so do the lot cards of Global Lots, Land Prep, Crop Protocol
       and Anee Auto. Group icon maps list every group: TILE above, PREV_ICON and PREV_NAME here,
       PAGE_ICON and PAGE_SHORT in partials/field-profile. */
    const PREV_ICON = { lot: '🗺️', soil: '🟤', water: '💧', acid: '🧪', tests: '🧫', land: '⛰️', last: '🌾', weeds: '🌿', problems: '🐛' };
    const PREV_NAME = { lot: 'The lot', soil: 'Soil', water: 'Water', acid: 'Acidity and lime', tests: 'Soil tests', land: 'The land', last: 'Last season', weeds: 'Weeds', problems: 'Troubles' };
    const TESTS = () => (window.aneeFieldProfile && window.aneeFieldProfile.tests) || null;
    const PEN = '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L19 9a2.8 2.8 0 00-4-4L4 16v4z"/><path stroke-linecap="round" d="M13.5 6.5l4 4"/></svg>';
    const EYE = '<svg fill="none" stroke="currentColor" stroke-width="2.1" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linejoin="round" d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    const OK = '<svg fill="none" stroke="currentColor" stroke-width="3.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    const troubleName = (k) => k.startsWith('other:') ? (D.others[k.slice(6)] || k.slice(6)) : k.replace(/^[a-z]+:/, '').replace(/-/g, ' ').replace(/^./, (c) => c.toUpperCase());
    const said = (v) => !(v === undefined || v === null || v === '' || (Array.isArray(v) && !v.length));
    // The lot's questions (all of them with no crop; the rice only ones only for rice).
    const qKeys = (crop) => Object.keys(D.qs).filter((k) => !(crop && D.qs[k][4] === 'rice' && !String(crop).startsWith('rice')));
    // The newest pH of the soil test log ("pH 5.8", "pH 5.5 to 6"), or ''.
    function phOf(a) {
        const T = TESTS(), t = T ? T.list(a || {}).find((x) => x && x.ph != null) : null;
        const n = (x) => String(Math.round(Number(x) * 100) / 100);
        return t ? 'pH ' + n(t.ph) + (t.phTo != null && Number(t.phTo) !== Number(t.ph) ? ' to ' + n(t.phTo) : '') : '';
    }
    const isAnswered = (a, k) => said((a || {})[k]) || (k === 'acidity' && !!phOf(a));
    // One answer in plain words, or '' when it is not answered.
    function answerWords(a, k) {
        a = a || {};
        const q = D.qs[k], v = a[k];
        let w = '';
        if (q && said(v)) {
            w = k === 'outbreaks' ? ([].concat(v).includes('none') ? 'None last season' : [].concat(v).map(troubleName).join(', '))
                : [].concat(v).map((x) => (q[2] && q[2][x]) || x).join(', ');
        }
        if (k === 'acidity') { const ph = phOf(a); if (ph) w = w ? w + ', ' + ph : ph + ', from a soil test'; }
        return w;
    }
    function tally(a, crop) {
        const keys = qKeys(crop);
        return { asked: keys.length, got: keys.filter((k) => isAnswered(a, k)).length };
    }
    /* The two buttons, for any lot card: `prev` and `edit` are the attributes each button wears
       (the caller listens for them), `cls` adds to the row's class (is-join: under a picked card). */
    const ansButtons = (prev, edit, cls) => '<div class="fl-ans' + (cls ? ' ' + cls : '') + '">'
        + '<button type="button" class="fl-ans-b" ' + prev + ' aria-haspopup="dialog">' + EYE + '<span>Preview answers</span></button>'
        + '<button type="button" class="fl-ans-b is-edit" ' + edit + ' aria-haspopup="dialog">' + PEN + '<span>Edit answers</span></button></div>';
    /* The answers inside the lot's own sheet: how many, and the same two buttons. A new lot with
       none yet gets the walk through every page instead (the yellow "Answer the questions"). */
    function sheetAnswersHtml(st) {
        const t = tally(st.answers, lastCrop(st.answers, st.crop));
        const pct = t.asked ? Math.round(t.got / t.asked * 100) : 0;
        return '<div class="fl-sa">'
            + '<div class="fl-prog"><div class="fl-prog-t"><b>' + (t.got ? t.got + ' of ' + t.asked + ' answered' : 'Not answered yet') + '</b><span>' + (t.got === t.asked ? 'All answered' : pct + '%') + '</span></div>'
            + '<div class="fl-bar' + (t.got === t.asked ? ' is-full' : '') + '" role="progressbar" aria-label="Questions answered" aria-valuemin="0" aria-valuemax="' + t.asked + '" aria-valuenow="' + t.got + '"><i style="width:' + pct + '%"></i></div></div>'
            + (t.got ? ansButtons('data-fl-review', 'data-fl-answers') + '<button type="button" class="fl-walk" id="flQBtn">Or go through them page by page</button>'
                : '<p class="fl-sub">Every analysis reads these answers, so they are asked once, here.</p><button type="button" class="fl-review" id="flQBtn">Answer the questions<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>')
            + '</div>';
    }

    /* ---- saving: the answers only, through the lot's own save (FarmLots::save) ----
       The place and the size stay as they are. The six month check starts again, as any save does. */
    async function saveAnswers(lot, answers) {
        const r = await call(D.urls.update + '/' + lot.id, { method: 'POST', body: { answers } });
        ROWS = null;
        return r.data.row;
    }
    const sameAnswers = (a, b) => { const FP = window.aneeFieldProfile, sh = FP && FP.shape ? FP.shape : clone; return JSON.stringify(sh(a || {})) === JSON.stringify(sh(b || {})); };

    /* ---- "Edit answers": every question of the lot, page by page in one form, one Save ----
       editAnswers(lot, { crop, commit, onSaved, quiet }) -> Promise<the lot as saved, or null>
       The form is the field questions' own (aneeFieldProfile.ask with all). A save fires
       aneelots:changed (unless quiet: the preview sheet fires it once, when it closes).
       commit(answers) -> Promise<{answers}> keeps the change elsewhere instead. */
    async function editAnswers(lot, o) {
        o = o || {};
        const FP = window.aneeFieldProfile;
        if (!FP || !FP.ask || !lot || !(o.commit || lot.id)) return null;
        const a = clone(lot.answers);
        const got = await FP.ask({ all: true, crop: lastCrop(a, o.crop), value: a, title: lot.name || 'Your lot', sub: 'Change any answer, then save' });
        if (!got || sameAnswers(got, a)) return null;
        try {
            const row = o.commit ? await o.commit(clone(got)) : await saveAnswers(lot, got);
            const out = Object.assign({}, lot, row || {}, { answers: clone((row && row.answers) || got) });
            if (!o.commit && !o.quiet) { window.toast?.('The answers are saved.', 'success'); changed(out); }
            if (o.onSaved && !o.quiet) o.onSaved(clone(out));
            return out;
        } catch (err) {
            window.toast?.(err.message || 'The answers did not save. Please try again.', 'error');
            return null;
        }
    }

    /* ---- "Preview answers": every answer, page by page (flReviewSheet) ----
       review(lot, { crop, commit, onSaved, onEdit }) -> Promise<the lot as saved, or null>
       Each item's Edit opens that one question (aneeFieldProfile.ask); "Edit all" opens the whole
       form. A change is saved at once and shows where it was, and closing the sheet fires
       aneelots:changed once. "The lot" (its place, size and last check) opens the lot's sheet:
       the caller's onEdit(lot) when given, else the kit's. */
    function reviewRows(a, crop) {
        const T = TESTS();
        return Object.keys(D.groups).map((g) => {
            const items = qKeys(crop).filter((k) => D.qs[k][1] === g).map((k) => ({ k, name: D.qs[k][0], words: answerWords(a, k), done: isAnswered(a, k) }));
            // The soil test log rides with the acidity: optional, so it is not counted.
            if (g === 'acid' && T) {
                const list = T.list(a), w = list.length ? T.words(list[0]) : '';
                items.push({ k: 'tests', extra: true, name: 'Soil tests', done: list.length > 0,
                    words: list.length ? (list.length === 1 ? T.date(list[0]) : list.length + ' results. The newest, ' + T.date(list[0])) + (w ? ': ' + w : '') : '' });
            }
            return { g, title: D.groups[g], items };
        }).filter((s) => s.items.length);
    }
    const rqHtml = (it, edit) => '<div class="fl-rq' + (it.done ? '' : ' is-empty') + (it.extra ? ' is-extra' : '') + '" data-k="' + esc(it.k) + '">'
        + '<div class="fl-rq-t"><small>' + esc(it.name) + '</small><b>' + esc(it.words || (it.extra ? 'None logged. A test is optional.' : 'Not answered yet')) + '</b></div>'
        + (edit ? '<button type="button" class="fl-rq-e" data-rv-edit="' + esc(it.k) + '" aria-label="' + esc((it.done ? 'Change: ' : (it.extra ? 'Add: ' : 'Answer: ')) + it.name) + '">'
            + (it.done ? PEN + 'Edit' : PLUS + (it.extra ? 'Add' : 'Answer')) + '</button>' : '')
        + '</div>';
    function rsHtml(s, edit) {
        const asked = s.items.filter((x) => !x.extra), got = asked.filter((x) => x.done).length, all = got === asked.length;
        return '<section class="fl-rs" data-g="' + esc(s.g) + '" aria-label="' + esc(s.title) + '"><h3 class="fl-rs-h"><span aria-hidden="true">' + (PREV_ICON[s.g] || '•') + '</span><b>' + esc(s.title) + '</b>'
            + '<em' + (all ? ' class="is-done"' : '') + '>' + (all ? OK : '') + got + ' of ' + asked.length + '</em></h3>'
            + '<div class="fl-qa">' + s.items.map((it) => rqHtml(it, edit)).join('') + '</div></section>';
    }
    // The lot itself: where it is, its size and its last check, with the lot sheet one tap away.
    function lotRowsHtml(l, edit) {
        const rows = [['Where is it?', l.where || l.place || ''], ['How big is it?', fmtSize(l)], ['Last checked', l.updated ? l.updated + (l.stale ? ', due for a check' : '') : '']];
        return '<section class="fl-rs is-lot" data-g="lot" aria-label="The lot"><h3 class="fl-rs-h"><span aria-hidden="true">' + PREV_ICON.lot + '</span><b>The lot</b>'
            + (edit ? '<button type="button" class="fl-rq-e" data-rv-lot aria-label="Edit the lot: its name, place and size">' + PEN + 'Edit</button>' : '') + '</h3>'
            + '<div class="fl-qa">' + rows.map((r) => '<div class="fl-rq' + (r[1] ? '' : ' is-empty') + ' is-info"><div class="fl-rq-t"><small>' + esc(r[0]) + '</small><b>' + esc(r[1] || 'Not set') + '</b></div></div>').join('') + '</div></section>';
    }
    // Fold an element in or out by its measured height (never by class alone: none to 0 snaps).
    function fold(el, show, animate) {
        if (!el || show === !el.hidden) return;
        if (el._flFold) { el._flFold.cancel(); el._flFold = null; }
        if (!animate || reduce() || !el.animate) { el.hidden = !show; return; }
        el.hidden = false;
        const cs = getComputedStyle(el), h = el.offsetHeight;
        const full = { height: h + 'px', opacity: 1, paddingTop: cs.paddingTop, paddingBottom: cs.paddingBottom, marginTop: cs.marginTop, borderTopWidth: cs.borderTopWidth, borderBottomWidth: cs.borderBottomWidth };
        const zero = { height: '0px', opacity: 0, paddingTop: '0px', paddingBottom: '0px', marginTop: '0px', borderTopWidth: '0px', borderBottomWidth: '0px' };
        el.style.overflow = 'hidden';
        const an = el.animate(show ? [zero, full] : [full, zero], { duration: 280, easing: 'cubic-bezier(.22,1,.36,1)' });
        el._flFold = an;
        an.onfinish = () => { el._flFold = null; el.style.overflow = ''; if (!show) el.hidden = true; };
    }
    function review(lot, o) {
        o = o || {};
        const dlg = $('flReviewSheet'), body = $('flReviewBody'), top = $('flReviewTop'), sw = $('flReviewOnly');
        bind(dlg);
        let cur = Object.assign({}, lot || {}, { answers: clone(lot && lot.answers) });
        let saved = null, only = false, busy = false;
        const FP = window.aneeFieldProfile;
        const canEdit = !!(FP && FP.ask) && !!(o.commit || cur.id);
        const crop = () => lastCrop(cur.answers, o.crop);
        const head = () => {
            $('flReviewTitle').firstChild.textContent = cur.name || 'Your lot';
            $('flReviewSub').textContent = o.commit ? 'Changes are kept when you save the lot' : ([cur.where || cur.place || '', fmtSize(cur)].filter(Boolean).join(' · ') || 'Every answer the analyses read');
        };
        const paintTop = (first) => {
            const t = tally(cur.answers, crop()), pct = t.asked ? Math.round(t.got / t.asked * 100) : 0;
            if (first) {
                top.innerHTML = '<div class="fl-rv-sum"><div class="fl-prog-t"><b></b><span></span></div>'
                    + (canEdit ? '<button type="button" class="fl-rv-all" data-rv-all>' + PEN + 'Edit all</button>' : '') + '</div>'
                    + '<div class="fl-bar" role="progressbar" aria-label="Questions answered" aria-valuemin="0"><i></i></div>';
            }
            top.querySelector('.fl-prog-t b').textContent = t.got + ' of ' + t.asked + ' answered';
            top.querySelector('.fl-prog-t span').textContent = t.got === t.asked ? 'All answered' : (t.asked - t.got) + ' to go';
            const bar = top.querySelector('.fl-bar'), i = bar.querySelector('i');
            bar.classList.toggle('is-full', t.got === t.asked);
            bar.setAttribute('aria-valuemax', String(t.asked));
            bar.setAttribute('aria-valuenow', String(t.got));
            if (first && !reduce()) { i.style.width = '0'; requestAnimationFrame(() => requestAnimationFrame(() => { i.style.width = pct + '%'; })); } else i.style.width = pct + '%';
        };
        // "Only unanswered": the answered rows fold away, and a page with none left folds too.
        const hides = (r, keep) => only && !(keep && keep.has(r.dataset.k)) && (r.classList.contains('is-extra') || r.classList.contains('is-info') || !r.classList.contains('is-empty'));
        const applyOnly = (animate, keep) => {
            let left = false;
            body.querySelectorAll('.fl-rs').forEach((s) => {
                let any = false;
                s.querySelectorAll('.fl-rq').forEach((r) => { const h = hides(r, keep); if (!h) any = true; fold(r, !h, animate); });
                if (any) left = true;
                fold(s, any, animate);
            });
            fold(body.querySelector('.fl-rv-none'), only && !left, animate);
        };
        const paint = () => {
            const keep = body.scrollTop;
            body.innerHTML = (canEdit ? '' : '<p class="fl-rv-note">These are the answers this analysis reads. Change them in Global Lots.</p>')
                + (o.commit || !cur.id ? '' : lotRowsHtml(cur, true))
                + reviewRows(cur.answers, crop()).map((s) => rsHtml(s, canEdit)).join('')
                + '<p class="fl-rv-none" hidden>Every question is answered. Turn off Only unanswered to see them all.</p>';
            body.scrollTop = keep;
        };
        const words = () => { const w = {}; body.querySelectorAll('.fl-rq[data-k]').forEach((r) => { w[r.dataset.k] = r.querySelector('.fl-rq-t b').textContent; }); return w; };
        // After a save: repaint, and the rows whose words changed show it (a yellow wash, a Saved tag).
        function landed(was, k) {
            paintTop(false);
            paint();
            const now = [...body.querySelectorAll('.fl-rq[data-k]')].filter((r) => r.querySelector('.fl-rq-t b').textContent !== was[r.dataset.k]);
            const lit = now.length ? now : [body.querySelector('.fl-rq[data-k="' + k + '"]')].filter(Boolean);
            applyOnly(false, new Set(lit.map((r) => r.dataset.k)));
            lit.forEach((el) => {
                el.classList.add('is-new');
                el.querySelector('.fl-rq-t small').insertAdjacentHTML('beforeend', '<em class="fl-ok">' + OK + 'Saved</em>');
                el.addEventListener('animationend', (ev) => { if (ev.target === el) el.classList.remove('is-new'); });
            });
            if (lit[0]) lit[0].scrollIntoView({ block: 'nearest', behavior: reduce() ? 'auto' : 'smooth' });
            // Answered while showing only the unanswered: it leaves once it is seen.
            if (only) setTimeout(() => { if (dlg.open) applyOnly(true); }, 1500);
        }
        const onClick = async (e) => {
            if (busy) return;
            // The lot itself: its own sheet, over this one (or the caller's, once this one closes).
            if (e.target.closest('[data-rv-lot]')) {
                if (o.onEdit) { const fn = o.onEdit, l = clone(cur); sheetClose(dlg); closed(dlg).then(() => fn(l)); return; }
                const row = await edit(cur, { crop: o.crop || null, ask: false });
                if (row) { cur = Object.assign({}, cur, row, { answers: clone(row.answers) }); saved = cur; head(); paintTop(false); paint(); applyOnly(false); }
                return;
            }
            // Every answer at once, in the whole form.
            if (e.target.closest('[data-rv-all]')) {
                const was = words();
                busy = true;
                try {
                    const row = await editAnswers(cur, { crop: o.crop, commit: o.commit || null, quiet: true });
                    if (row) { cur = row; saved = cur; landed(was, ''); }
                } finally { busy = false; }
                return;
            }
            const b = e.target.closest('[data-rv-edit]');
            if (!b || !canEdit) return;
            const k = b.dataset.rvEdit, key = k === 'tests' ? 'acidity' : k, q = D.qs[key];
            if (!q) return;
            const got = await FP.ask({ crop: crop(), value: cur.answers, keys: [key], title: q[0], sub: (cur.name || 'Your lot') + ', ' + String(D.groups[q[1]] || '').toLowerCase() });
            if (!got || sameAnswers(got, cur.answers)) return;
            const was = words();
            const row = body.querySelector('.fl-rq[data-k="' + k + '"]');
            if (row) { row.classList.add('is-busy'); row.querySelector('.fl-rq-t b').textContent = 'Saving…'; }
            busy = true;
            try {
                const r = o.commit ? await o.commit(clone(got)) : await saveAnswers(cur, got);
                cur = Object.assign({}, cur, r || {}, { answers: clone((r && r.answers) || got) });
                saved = cur;
                landed(was, k);
            } catch (err) {
                paint();
                applyOnly(false);
                window.toast?.(err.message || 'That answer did not save. Please try again.', 'error');
            } finally { busy = false; }
        };
        body.onclick = onClick;
        top.onclick = onClick;
        sw.setAttribute('aria-checked', 'false');
        sw.onclick = () => { only = !only; sw.setAttribute('aria-checked', String(only)); applyOnly(true); if (only) body.scrollTo({ top: 0, behavior: reduce() ? 'auto' : 'smooth' }); };
        const p = closed(dlg).then(() => {
            body.onclick = null;
            top.onclick = null;
            sw.onclick = null;
            if (saved && !o.commit) changed(saved);
            if (saved && o.onSaved) o.onSaved(clone(saved));
            return saved ? clone(saved) : null;
        });
        head();
        paintTop(true);
        paint();
        sheetOpen(dlg);
        body.scrollTop = 0;
        return p;
    }

    /* Kept for the callers of before (2026-10-16 to 10-19, when it opened an answer card in
       `host`): it opens the preview sheet now, and nothing opens in the host. */
    function preview(host, lot, o) { review(lot, o || {}); return null; }
    function togglePreview(host, lot, o) {
        if (!lot) return false;
        if (host) host.querySelectorAll(':scope > .fl-prev').forEach((x) => x.remove());
        review(lot, o || {});
        return false;
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
            paintActs(false);
        }
        /* The bar opens out of the card's bottom edge: height, padding and fade (never the margin, which would
           jump), then the buttons rise in a moment after. Closing runs it back. */
        function joinMove(bar, open, done) {
            if (reduce() || !bar.animate) { done && done(); return; }
            const cs = getComputedStyle(bar), h = bar.offsetHeight;
            const full = { height: h + 'px', paddingTop: cs.paddingTop, paddingBottom: cs.paddingBottom, opacity: 1 };
            const zero = { height: '0px', paddingTop: '0px', paddingBottom: '0px', opacity: 0 };
            bar.style.overflow = 'hidden';
            const an = bar.animate(open ? [zero, full] : [full, zero], { duration: open ? 320 : 240, easing: 'cubic-bezier(.22,1,.36,1)' });
            if (open) bar.querySelectorAll('.fl-ans-b').forEach((b, i) => b.animate([{ opacity: 0, transform: 'translateY(-6px)' }, { opacity: 1, transform: 'none' }],
                { duration: 300, delay: 90 + i * 50, easing: 'cubic-bezier(.22,1,.36,1)', fill: 'backwards' }));
            an.onfinish = () => { bar.style.overflow = ''; done && done(); };
        }
        // The picked lot wears "Preview answers" and "Edit answers", joined under its card as one container.
        function paintActs(animate) {
            const old = el.querySelector('[data-fl-acts]');
            const card = st.lot ? el.querySelector('[data-fl-lot="' + st.lot.id + '"]') : null;
            el.querySelectorAll('.fl-card.has-acts').forEach((c) => { if (c !== card) c.classList.remove('has-acts'); });
            if (old && card && old.previousElementSibling === card) return;
            if (old) {
                old.removeAttribute('data-fl-acts');
                if (animate && !reduce()) joinMove(old, false, () => old.remove()); else old.remove();
            }
            if (!card) return;
            const tpl = document.createElement('div');
            tpl.innerHTML = ansButtons('data-fl-review', 'data-fl-answers', 'is-join');
            const bar = tpl.firstChild;
            bar.setAttribute('data-fl-acts', '');
            bar.setAttribute('aria-label', st.lot.name + ': its answers');
            card.after(bar);
            card.classList.add('has-acts');
            if (animate) joinMove(bar, true);
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
            paintActs(true);
            if (o.onPick) o.onPick(lot ? clone(lot) : null);
        }
        el.addEventListener('click', async (e) => {
            const add = e.target.closest('[data-fl-add]');
            if (add) { const row = await edit(null, { crop: crop() }); if (row) { st.id = row.id; await paint(true); pick(st.lot); } return; }
            if (e.target.closest('[data-fl-edit]') && st.lot) { const row = await edit(st.lot, { crop: crop(), ask: false }); if (row) { await paint(true); pick(st.lot); } return; }
            // Preview answers and Edit answers: a save fires aneelots:changed, which repaints this picker.
            if (e.target.closest('[data-fl-review]') && st.lot) { await review(st.lot, { crop: crop() }); return; }
            if (e.target.closest('[data-fl-answers]') && st.lot) { await editAnswers(st.lot, { crop: crop() }); return; }
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

    window.aneeLots = { picker, edit, rows, answered, has, legacy, payload, confirm, card, meta: () => META, staleDays: D.staleDays, crops: CROPS, fmtHa, fmtSize, units: UNITS, preview, togglePreview, review, editAnswers, saveAnswers, ansButtons };
})();
</script>
@endpush
@endonce
