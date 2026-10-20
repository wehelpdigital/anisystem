{{-- FIELD PROFILE, the shared questions (2026-10-15).

     One lot's soil, water and history, asked one group a page with a plain
     line under every question (App\Support\FieldProfile is the bank), the
     troubles sheet with search and photos, a known pH range, "Same as Lot X"
     when there are several lots, and "Use the answers you saved before"
     (as_field_profiles, FieldProfileController). Include it once on a page:
     @include('partials.field-profile'). Styles and dialogs are pushed once.

     JS (window.aneeFieldProfile):
       const fq = aneeFieldProfile.mount(el, {
           crop: 'rice',                       // a FieldCatalogue crop key, or null
           lots: [{ name: 'Lot 1', value: {}, copiedFrom: null, edited: false, from: null }],
           // or value: {} and name: 'Your field' for one profile
           saved: true,                        // offer the farm's saved profiles on the first page
           onChange(lotIndex, value, lot) {},  // after every answer
           onSkip(lotIndex) {},                // the "Next lot" button of a copied lot (shown only when given)
       });
       fq.pages()            the page keys for the crop: soil, water, acid, last, weeds, problems
       fq.show(lot, page, back)   draw one page of one lot (animated; back slides the other way)
       fq.value(lot)  fq.values()  fq.lot(lot)  fq.count(lot)
       fq.setCrop(crop)  fq.setLots(lots)
       aneeFieldProfile.open({ crop, value, title, saved }) -> Promise<answers or null>
           the same questions in a full screen sheet with its own Back and Next.
       aneeFieldProfile.ask({ crop, value, keys: ['texture'], page, title, sub }) -> Promise<answers or null>
           ONE question (or the questions in `keys`, or a whole `page`) in a small sheet with
           Cancel and Save (2026-10-19, the lot review's Edit). The answers come back whole.
       aneeFieldProfile.ask({ all: true, crop, value, title, sub }) -> Promise<answers or null>
           EVERY question in one form, page by page under sticky titles, with a row of page
           buttons to jump and one Save (the lot's "Edit answers"). A tap outside does not close it.
       mount(el, { keys }) draws only those questions on each page, with no lot banner;
       mount(el, { all: true }) draws every page at once.
       aneeFieldProfile.groups   page key -> [title, line]
       aneeFieldProfile.count(value)  aneeFieldProfile.words(value)  (a short summary)
       aneeFieldProfile.save({ name, crop, answers, lotId, scheduleId }) -> Promise<row or null>
       aneeFieldProfile.tests: the soil test log (answers.tests, newest first; FieldProfile::cleanTests)
           .list(answers)  .html(answers)  .edit(test or null) -> Promise<{test} | {remove} | null>
           .apply(answers, list)  (sorts it and sets phFrom/phTo from the newest pH)
           .latest(list)  .levels(test)  .words(test)  .date(test)
     The answers are FieldProfile's flat map; the server's FieldProfile::normalize is the judge. --}}
@once
@push('head')
<style>
    .fq { --fq-e: cubic-bezier(.22,1,.36,1); --fq-g: #4a7c2a; --fq-gd: #2d5016; --fq-gl: #e4efd4; --fq-gx: #f4f9ee; container-type: inline-size; min-width: 0; }
    .fq-page { min-width: 0; }
    /* Pages fade, never slide (2026-10-16, the owner). */
    .fq-page.is-in { animation: fqIn .26s ease .06s both; }
    .fq-page.is-back { animation-name: fqBack; }
    @keyframes fqIn { from { opacity: 0; } }
    @keyframes fqBack { from { opacity: 0; } }
    @keyframes fqPop { from { opacity: 0; transform: translateY(8px); } }

    /* Whose page this is: the lot, and where in its pages. */
    .fq-lot { display: flex; align-items: center; gap: .5rem; margin-bottom: .85rem; padding: .5rem .65rem; border-radius: .9rem; background: color-mix(in srgb, var(--lc, #4a7c2a) 9%, #fff); border: 1px solid color-mix(in srgb, var(--lc, #4a7c2a) 26%, #fff); min-width: 0; }
    .fq-lot > i { flex: none; width: .75rem; height: .75rem; border-radius: 999px; background: var(--lc, #4a7c2a); box-shadow: 0 0 0 3px color-mix(in srgb, var(--lc, #4a7c2a) 22%, transparent); }
    .fq-lot b { flex: none; font-size: .8rem; font-weight: 900; color: #1f2937; }
    .fq-lot span { min-width: 0; flex: 1; font-size: .8rem; font-weight: 700; color: #4b5563; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fq-dots { flex: none; display: inline-flex; gap: .22rem; }
    .fq-dots u { width: .42rem; height: .42rem; border-radius: 999px; background: color-mix(in srgb, var(--lc, #4a7c2a) 30%, #fff); text-decoration: none; transition: background-color .28s var(--fq-e), transform .28s var(--fq-e); }
    .fq-dots u.is-on { background: var(--lc, #4a7c2a); transform: scale(1.25); }
    .fq-dots u.is-done { background: color-mix(in srgb, var(--lc, #4a7c2a) 60%, #fff); }

    .fq-t { font-family: var(--font-heading); font-size: 1.2rem; font-weight: 800; line-height: 1.25; color: #14210c; }
    .fq-s { margin: .3rem 0 1rem; font-size: .84rem; line-height: 1.5; color: #6b7280; }

    /* "Same as Lot 1", "Copied from Lot 1", "Use saved answers". */
    .fq-same, .fq-copied, .fq-saved { display: flex; align-items: center; gap: .7rem; margin-bottom: 1rem; padding: .75rem .8rem; border-radius: 1rem; animation: fqPop .34s var(--fq-e) both; min-width: 0; }
    .fq-same { background: linear-gradient(115deg, #f3f8ec, #e8f1dc); border: 1px dashed #b9d59a; }
    .fq-same > span, .fq-saved > span { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .8rem; display: grid; place-items: center; background: #fff; color: var(--fq-g); border: 1px solid #d7e7c4; }
    .fq-same > span svg, .fq-saved > span svg { width: 1.2rem; height: 1.2rem; }
    .fq-same > div, .fq-copied > div, .fq-saved > div { flex: 1; min-width: 0; }
    .fq-same b, .fq-copied b, .fq-saved b { display: block; font-size: .9rem; font-weight: 800; color: #14210c; line-height: 1.3; }
    .fq-same small, .fq-copied small, .fq-saved small { display: block; margin-top: .1rem; font-size: .76rem; line-height: 1.4; color: #4b5563; }
    .fq-btn { flex: none; min-height: 2.5rem; padding: .4rem .9rem; border-radius: 999px; font-size: .84rem; font-weight: 800; color: #fff; background: var(--fq-g); white-space: nowrap;
        transition: background-color .28s var(--fq-e), transform .28s var(--fq-e); }
    .fq-btn:hover { background: var(--fq-gd); }
    .fq-btn:active { transform: scale(.96); }
    .fq-btn.is-soft { color: var(--fq-gd); background: #fff; border: 1px solid #cfe3b8; }
    .fq-btn.is-soft:hover { background: var(--fq-gl); }
    .fq-copied { background: #ecfdf5; border: 1px solid #a7f3d0; }
    .fq-copied > i { flex: none; width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: #059669; font-style: normal; }
    .fq-copied > i svg { width: 1rem; height: 1rem; }
    .fq-copied.is-edited { background: #fffbeb; border-color: #fde68a; }
    .fq-copied.is-edited > i { background: #d97706; }
    .fq-saved { padding: .55rem .7rem; background: #f8faf6; border: 1px solid #e3ebda; cursor: pointer; text-align: left; width: 100%; transition: border-color .28s var(--fq-e), background-color .28s var(--fq-e); }
    .fq-saved:hover { border-color: #a8cc7e; background: #fff; }
    .fq-saved > span { width: 2.1rem; height: 2.1rem; }
    .fq-saved em { flex: none; font-style: normal; font-size: .74rem; font-weight: 800; color: var(--fq-gd); }
    @container (max-width: 25rem) {
        .fq-same, .fq-copied { flex-wrap: wrap; row-gap: .55rem; }
        .fq-same > div { flex: 1 1 calc(100% - 3.2rem); }
        .fq-copied > div { flex: 1 1 calc(100% - 2.8rem); }
        .fq-same .fq-btn { margin-left: 3.1rem; }
        .fq-copied .fq-btn { margin-left: 2.7rem; }
    }

    /* A question: the question, why it is asked, the answers. */
    .fq-q { padding: 1rem 0; border-top: 1px solid #eef2ea; min-width: 0; }
    .fq-q:first-of-type { border-top: 0; padding-top: .2rem; }
    .fq-q h3 { display: flex; align-items: flex-start; gap: .45rem; font-size: 1rem; font-weight: 800; line-height: 1.35; color: #14210c; }
    .fq-q h3 span { flex: 1; min-width: 0; }
    .fq-tick { flex: none; margin-top: .12rem; width: 1.2rem; height: 1.2rem; border-radius: 999px; display: grid; place-items: center; color: #fff; background: var(--fq-g); transform: scale(0); transition: transform .28s var(--fq-e); }
    .fq-tick svg { width: .7rem; height: .7rem; }
    .fq-q.is-done .fq-tick { transform: none; }
    .fq-why { display: flex; gap: .45rem; margin: .3rem 0 .7rem; font-size: .8rem; line-height: 1.5; color: #5b6472; }
    .fq-why svg { flex: none; width: .95rem; height: .95rem; margin-top: .15rem; color: #9aa69a; }
    .fq-many { margin: -.35rem 0 .6rem 1.4rem; font-size: .7rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: var(--fq-g); }
    .fq-opts { display: grid; gap: .45rem; }
    @container (min-width: 32rem) { .fq-opts:not(.is-pills) { grid-template-columns: 1fr 1fr; } }
    .fq-o { width: 100%; display: flex; align-items: center; gap: .65rem; min-height: 3rem; padding: .6rem .75rem; border-radius: .95rem; text-align: left; background: #fff; border: 1.5px solid #e5e9e0; color: #14210c;
        transition: border-color .28s var(--fq-e), background-color .28s var(--fq-e), box-shadow .28s var(--fq-e), transform .28s var(--fq-e); }
    .fq-o:hover { border-color: #b7d595; }
    .fq-o:active { transform: scale(.985); }
    .fq-o.is-on { border-color: var(--fq-g); background: var(--fq-gx); box-shadow: 0 0 0 3px rgb(107 159 61 / .14); }
    .fq-m { flex: none; position: relative; width: 1.3rem; height: 1.3rem; border-radius: 999px; border: 2px solid #c9d2c0; transition: border-color .28s var(--fq-e), background-color .28s var(--fq-e); }
    .fq-m::after { content: ''; position: absolute; inset: .2rem; border-radius: inherit; background: #fff; transform: scale(0); transition: transform .28s var(--fq-e); }
    .fq-o.is-on .fq-m { border-color: var(--fq-g); background: var(--fq-g); }
    .fq-o.is-on .fq-m::after { transform: scale(1); }
    .fq-opts.is-multi .fq-m { border-radius: .4rem; }
    .fq-opts.is-multi .fq-m::after { inset: .12rem .32rem .3rem; border-radius: 0; background: transparent; border: solid #fff; border-width: 0 2.5px 2.5px 0; transform: rotate(45deg) scale(0); }
    .fq-opts.is-multi .fq-o.is-on .fq-m::after { transform: rotate(45deg) scale(1); }
    .fq-ot { min-width: 0; flex: 1; }
    .fq-ot b { display: block; font-size: .92rem; font-weight: 700; line-height: 1.3; overflow-wrap: anywhere; }
    .fq-ot small { display: block; margin-top: .05rem; font-size: .75rem; line-height: 1.35; color: #6b7280; overflow-wrap: anywhere; }
    .fq-opts.is-pills { display: flex; flex-wrap: wrap; }
    .fq-opts.is-pills .fq-o { width: auto; min-height: 2.75rem; padding: .45rem 1rem .45rem .6rem; border-radius: 999px; }

    /* A known pH range. */
    .fq-ph { margin-top: .8rem; padding: .7rem .75rem; border-radius: .95rem; background: #f8faf6; border: 1px solid #e8eee1; }
    .fq-ph > label { display: block; font-size: .8rem; font-weight: 800; color: #374151; }
    .fq-ph > label small { font-weight: 600; color: #9ca3af; }
    .fq-phr { display: flex; align-items: flex-end; gap: .5rem; margin-top: .45rem; }
    .fq-phr label { display: flex; flex-direction: column; gap: .2rem; font-size: .7rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #6b7280; }
    .fq-phr input { width: 5.6rem; min-height: 2.75rem; padding: .4rem .6rem; border-radius: .75rem; border: 1.5px solid #dfe5d8; background: #fff; font-size: 1rem; font-weight: 700; color: #14210c; text-align: center; transition: border-color .28s var(--fq-e); }
    .fq-phr input:focus { outline: none; border-color: #86b556; }
    .fq-phr span { padding-bottom: .7rem; font-size: .84rem; font-weight: 700; color: #6b7280; }
    .fq-phr input.is-bad { border-color: #ef4444; }
    .fq-err { margin-top: .4rem; font-size: .76rem; font-weight: 700; color: #b91c1c; }

    /* The soil test log (2026-10-16, the owner): each result with the day it was
       sampled, newest first. It sits under the acidity question and in the lot sheet. */
    .fq-tests { --fq-e: cubic-bezier(.22,1,.36,1); --fq-g: #4a7c2a; --fq-gd: #2d5016; margin-top: .85rem; padding: .8rem; border-radius: 1rem; background: #f8faf6; border: 1px solid #e8eee1; min-width: 0; }
    .fq-tests-h { display: flex; align-items: flex-start; gap: .6rem; }
    .fq-tests-h > span { flex: none; width: 2.2rem; height: 2.2rem; border-radius: .8rem; display: grid; place-items: center; font-size: 1.1rem; background: #fff; border: 1px solid #e1e9d7; }
    .fq-tests-h > div { flex: 1; min-width: 0; }
    .fq-tests-h b { display: block; font-size: .92rem; font-weight: 800; color: #14210c; }
    .fq-tests-h small { display: block; margin-top: .1rem; font-size: .77rem; line-height: 1.45; color: #6b7280; }
    .fq-tl { list-style: none; margin: .7rem 0 0; padding: 0; display: grid; gap: .45rem; }
    .fq-tl li { display: flex; align-items: center; gap: .6rem; min-width: 0; padding: .6rem .65rem; border-radius: .9rem; background: #fff; border: 1px solid #e5e9e0; animation: fqPop .3s var(--fq-e) both; }
    .fq-tl li > div { flex: 1; min-width: 0; }
    .fq-tl b { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; font-size: .86rem; font-weight: 800; color: #14210c; }
    .fq-tl b em { font-style: normal; font-size: .62rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; padding: .1rem .45rem; border-radius: 999px; color: #3d6823; background: #eef5e6; }
    .fq-tl b em.is-old { color: #6b7280; background: #f1f3ee; }
    .fq-tl small { display: block; margin-top: .15rem; font-size: .77rem; line-height: 1.45; color: #4b5563; overflow-wrap: anywhere; }
    .fq-tl button { flex: none; min-height: 2.4rem; padding: .25rem .85rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: #3d6823; background: #fff; border: 1.5px solid #cfe0bd;
        transition: background-color .28s var(--fq-e), border-color .28s var(--fq-e); }
    .fq-tl button:hover { background: #eef5e6; border-color: #86b556; }
    .fq-tests .fq-add { margin-top: .7rem; }
    .fqt-f { margin-top: 1rem; }
    .fqt-f:first-child { margin-top: .3rem; }
    .fqt-l { display: block; margin-bottom: .4rem; font-size: .86rem; font-weight: 800; color: #1f2937; }
    .fqt-l small { font-weight: 600; color: #9ca3af; }
    .fqt-f .form-input { width: 100%; }
    .fqt-n { max-width: 9rem; text-align: center; font-weight: 700; }
    .fqt-ph { display: flex; align-items: center; gap: .55rem; }
    .fqt-ph .fqt-n { flex: 0 1 7rem; min-width: 0; }
    .fqt-ph > span { flex: none; font-size: .86rem; font-weight: 700; color: #6b7280; }
    html.dark .fqt-ph > span { color: #a9b69c; }
    .fqt-kind { display: grid; gap: .45rem; }
    @container (min-width: 30rem) { .fqt-kind { grid-template-columns: 1fr 1fr; } }
    .fqt-sec { margin-top: 1rem; padding: .85rem; border-radius: 1rem; background: #f8faf6; border: 1px solid #e8eee1; animation: fqPop .3s var(--fq-e) both; }
    .fqt-sec[hidden] { display: none; }
    .fqt-sec > p { margin: 0 0 .6rem; font-size: .78rem; line-height: 1.45; color: #6b7280; }
    .fqt-lv { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .6rem; padding: .45rem 0; border-top: 1px dashed #e3eadb; }
    .fqt-lv:first-of-type { border-top: 0; }
    .fqt-lv > span { flex: 1 1 8rem; font-size: .86rem; font-weight: 700; color: #1f2937; }
    .fqt-pills { display: inline-flex; gap: .3rem; flex-wrap: wrap; }
    .fqt-pills button { min-height: 2.4rem; padding: .3rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: #374151; background: #fff; border: 1.5px solid #e1e7da;
        transition: background-color .28s var(--fq-e), border-color .28s var(--fq-e), color .28s var(--fq-e); }
    .fqt-pills button:hover { border-color: #b7d595; }
    .fqt-pills button.is-on { color: #fff; background: #4a7c2a; border-color: #4a7c2a; }
    .fqt-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem .7rem; }
    .fqt-grid label { display: flex; flex-direction: column; gap: .25rem; min-width: 0; font-size: .76rem; font-weight: 800; color: #374151; }
    .fqt-grid label small { font-weight: 600; color: #9ca3af; }
    .fqt-grid .fqt-pills { margin-top: .2rem; }
    .fqt-grid .fqt-pills button { min-height: 2rem; padding: .15rem .6rem; font-size: .72rem; }
    .fqt-was { margin-top: .35rem; font-size: .74rem; color: #6b7280; }
    .fq-foot .fqt-del { color: #b91c1c; }
    .fq-foot .fqt-del[hidden] { display: none; }
    html.dark .fq-tests, html.dark .fqt-sec { background: #1a2114; border-color: #2b3a1c; }
    html.dark .fq-tests-h > span, html.dark .fq-tl li { background: #151b12; border-color: #2b3a1c; }
    html.dark .fq-tests-h b, html.dark .fq-tl b, html.dark .fqt-l, html.dark .fqt-lv > span, html.dark .fqt-grid label { color: #e8efe1; }
    html.dark .fq-tests-h small, html.dark .fq-tl small, html.dark .fqt-sec > p, html.dark .fqt-was { color: #a9b69c; }
    html.dark .fq-tl b em { color: #a5c97e; background: #22301a; }
    html.dark .fq-tl b em.is-old { color: #a9b69c; background: #1c2616; }
    html.dark .fq-tl button, html.dark .fqt-pills button { color: #cfe6b8; background: #151b12; border-color: #2b3a1c; }
    html.dark .fqt-pills button.is-on { color: #fff; background: #4a7c2a; border-color: #6b9f3d; }
    html.dark .fqt-lv { border-color: #22301a; }
    html.dark .fq-foot .fqt-del { color: #fca5a5; }
    @media (prefers-reduced-motion: reduce) { .fq-tl li, .fqt-sec { animation: none !important; } .fq-tl button, .fqt-pills button { transition: none !important; } }
    html.sm-still .fq-tl li, html.sm-still .fqt-sec { animation: none !important; }

    /* The troubles: chips of what is picked, a button that opens the sheet. */
    .fq-tchips { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .6rem; }
    .fq-tc { display: inline-flex; align-items: center; gap: .4rem; max-width: 100%; min-height: 2.3rem; padding: .2rem .3rem .2rem .25rem; border-radius: 999px; background: #fff; border: 1.5px solid var(--fq-g); font-size: .82rem; font-weight: 700; color: #14210c; animation: fqPop .3s var(--fq-e) both; }
    .fq-tc > span:first-child { flex: none; width: 1.75rem; height: 1.75rem; border-radius: 999px; overflow: hidden; display: grid; place-items: center; font-size: .95rem; background: #f3f6ef; }
    .fq-tc img { width: 100%; height: 100%; object-fit: cover; }
    .fq-tc em { font-style: normal; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fq-tc button { flex: none; width: 1.75rem; height: 1.75rem; border-radius: 999px; display: grid; place-items: center; color: #6b7280; transition: background-color .28s var(--fq-e), color .28s var(--fq-e); }
    .fq-tc button:hover { background: #fee2e2; color: #b91c1c; }
    .fq-tc button svg { width: .75rem; height: .75rem; }
    .fq-tacts { display: flex; flex-wrap: wrap; gap: .5rem; }
    .fq-add { display: inline-flex; align-items: center; gap: .45rem; min-height: 2.9rem; padding: .5rem 1rem; border-radius: 999px; font-size: .88rem; font-weight: 800; color: #fff; background: var(--fq-g); transition: background-color .28s var(--fq-e), transform .28s var(--fq-e); }
    .fq-add:hover { background: var(--fq-gd); }
    .fq-add:active { transform: scale(.97); }
    .fq-add svg { width: 1rem; height: 1rem; }
    .fq-none { min-height: 2.9rem; padding: .5rem 1rem; border-radius: 999px; font-size: .86rem; font-weight: 800; color: #374151; background: #fff; border: 1.5px solid #e5e9e0; transition: border-color .28s var(--fq-e), background-color .28s var(--fq-e), color .28s var(--fq-e); }
    .fq-none.is-on { color: #fff; background: #4b5563; border-color: #4b5563; }

    /* The weeds and grasses catalogue (2026-10-21, the owner): a link in the weeds question's line
       opens it, for telling the weeds apart. Nothing is picked from it. */
    .fq-wlink { display: inline; padding: 0; font: inherit; font-weight: 800; color: var(--fq-g, #4a7c2a); text-decoration: underline; text-underline-offset: 2px; text-decoration-thickness: 1.5px;
        transition: color .28s cubic-bezier(.22,1,.36,1); }
    .fq-wlink:hover { color: var(--fq-gd, #2d5016); }
    .fqw-groups { flex: none; display: flex; flex-wrap: wrap; gap: .4rem; margin: 0 1rem .5rem; }
    .fqw-groups button { display: inline-flex; align-items: center; gap: .35rem; min-height: 2.6rem; padding: .3rem .85rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: #2d5016;
        background: #f3f8ec; border: 1px solid #d7e7c4; transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .fqw-groups button:hover { border-color: #a8cc7e; }
    .fqw-groups button.is-on { color: #fff; background: #4a7c2a; border-color: #4a7c2a; }
    .fqw-groups small { font-size: .7rem; font-weight: 700; opacity: .75; }
    .fqw-ghint { flex: none; margin: -.1rem 1.15rem .5rem; font-size: .78rem; line-height: 1.45; color: #5b6472; animation: fqPop .28s cubic-bezier(.22,1,.36,1) both; }
    .fqw-ghint[hidden], .fqw-one[hidden], .fq-list[hidden], .fqw-tools[hidden], .fqw-back[hidden] { display: none; }
    .fqw-tools { flex: none; display: flex; flex-direction: column; }
    .fqw-row .fq-ri { width: 3.4rem; height: 3.4rem; border-radius: .85rem; }
    .fqw-row .fq-rt small { display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .fqw-row > svg { flex: none; width: 1rem; height: 1rem; color: #9aa69a; }
    .fqw-in { animation: fqPop .3s cubic-bezier(.22,1,.36,1) both; }
    .fqw-fig { margin: .2rem 0 0; }
    .fqw-fig img { display: block; width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 1rem; background: #f3f6ef; }
    .fqw-fig figcaption { margin-top: .3rem; font-size: .68rem; line-height: 1.4; color: #8a9480; overflow-wrap: anywhere; }
    .fqw-name { margin-top: .8rem; font-family: var(--font-heading); font-size: 1.25rem; font-weight: 800; line-height: 1.25; color: #14210c; }
    .fqw-sci { margin-top: .1rem; font-size: .84rem; font-style: italic; color: #6b7280; }
    .fqw-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .55rem; }
    .fqw-chips span { padding: .2rem .65rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: #3d6823; background: #eef5e6; }
    .fqw-sec { margin-top: .95rem; }
    .fqw-sec b { display: block; font-size: .72rem; font-weight: 900; letter-spacing: .06em; text-transform: uppercase; color: #3d6823; }
    .fqw-sec p { margin-top: .25rem; font-size: .9rem; line-height: 1.55; color: #1f2937; }
    html.dark .fq-wlink { color: #a5c97e; }
    html.dark .fqw-groups button { color: #cfe6b8; background: #1a2414; border-color: #2f4321; }
    html.dark .fqw-groups button.is-on { color: #fff; background: #4a7c2a; border-color: #6b9f3d; }
    html.dark .fqw-ghint, html.dark .fqw-sci { color: #a9b69c; }
    html.dark .fqw-name { color: #e8efe1; }
    html.dark .fqw-chips span { color: #cfe6b8; background: #22301a; }
    html.dark .fqw-sec b { color: #a5c97e; }
    html.dark .fqw-sec p { color: #d5e3c5; }
    html.dark .fqw-fig img { background: #1c2616; }
    @media (prefers-reduced-motion: reduce) { .fqw-in, .fqw-ghint { animation: none !important; } .fq-wlink, .fqw-groups button { transition: none !important; } }
    html.sm-still .fqw-in, html.sm-still .fqw-ghint { animation: none !important; }

    /* The sheets. */
    .fq-sheet { padding: 0; border: 0; background: transparent; max-width: none; max-height: none; width: 100%; height: 100%; margin: 0; inset: 0; overflow: hidden; }
    .fq-sheet::backdrop { background: rgb(10 18 6 / .5); animation: fqFade .28s cubic-bezier(.22,1,.36,1); }
    .fq-sheet.is-closing::backdrop { animation: fqFade .22s ease reverse forwards; }
    .fq-box { position: absolute; left: 0; right: 0; bottom: 0; height: 88vh; height: 88dvh; display: flex; flex-direction: column; border-radius: 1.4rem 1.4rem 0 0; background: #fff;
        box-shadow: 0 -20px 50px -20px rgb(10 18 6 / .5); animation: fqUp .34s cubic-bezier(.22,1,.36,1); }
    .fq-sheet.is-small .fq-box { height: auto; max-height: 80vh; max-height: 80dvh; }
    .fq-sheet.is-closing .fq-box { animation: fqUp .24s cubic-bezier(.22,1,.36,1) reverse forwards; }
    @media (min-width: 640px) {
        .fq-box { left: 50%; top: 50%; bottom: auto; right: auto; width: min(34rem, calc(100vw - 2rem)); height: min(44rem, 88vh); border-radius: 1.4rem; transform: translate(-50%, -50%); animation-name: fqPopC; }
        .fq-sheet.is-small .fq-box { width: min(26rem, calc(100vw - 2rem)); height: auto; max-height: 80vh; }
        .fq-sheet.is-closing .fq-box { animation-name: fqPopC; }
    }
    .fq-sheet.is-full .fq-box { height: 94vh; height: 94dvh; }
    @media (min-width: 640px) { .fq-sheet.is-full .fq-box { width: min(40rem, calc(100vw - 2rem)); height: min(52rem, 92vh); } }
    /* One question (aneeFieldProfile.ask): as tall as the question, never past the screen. */
    .fq-sheet.is-ask .fq-box { height: auto; max-height: 90vh; max-height: 90dvh; }
    @media (min-width: 640px) { .fq-sheet.is-ask .fq-box { width: min(36rem, calc(100vw - 2rem)); height: auto; max-height: min(46rem, 90vh); } }
    .fq-sheet.is-ask .fq-body { padding-top: .1rem; }
    .fq-sheet.is-ask .fq-head h2 small, #fqAskSheet .fq-head h2 small { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    /* Every question in one form (aneeFieldProfile.ask with all): page buttons, then each page under its sticky title. */
    .fq-jump { display: flex; flex-wrap: wrap; gap: .4rem; margin: .4rem 0 .1rem; }
    .fq-jump button { display: inline-flex; align-items: center; gap: .35rem; min-height: 2.75rem; padding: .3rem .85rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: #2d5016;
        background: #f3f8ec; border: 1px solid #d7e7c4; transition: background-color .28s var(--fq-e), border-color .28s var(--fq-e), transform .28s var(--fq-e); }
    .fq-jump button:hover { background: #e4efd4; border-color: #a8cc7e; }
    .fq-jump button:active { transform: scale(.97); }
    .fq-sec { scroll-margin-top: 0; }
    .fq-sec-h { position: sticky; top: 0; z-index: 2; display: flex; align-items: center; gap: .55rem; margin-top: .9rem; padding: .7rem 0 .55rem; background: #fff; border-bottom: 1px solid #eef2ea; }
    .fq-sec-h > span { flex: none; width: 2.05rem; height: 2.05rem; border-radius: .7rem; display: grid; place-items: center; font-size: 1.05rem; background: #f1f6ea; }
    .fq-sec-h b { flex: 1; min-width: 0; font-family: var(--font-heading); font-size: 1.02rem; font-weight: 800; line-height: 1.25; color: #14210c; }
    .fq-sec-h em { flex: none; padding: .15rem .55rem; border-radius: 999px; font-style: normal; font-size: .72rem; font-weight: 800; color: #5b6b4c; background: #f1f4ec; transition: color .28s var(--fq-e), background-color .28s var(--fq-e); }
    .fq-sec-h em.is-done { color: #2d5016; background: #e4efd4; }
    .fq-sec .fq-q:first-of-type { padding-top: .85rem; }
    html.dark .fq-jump button { color: #cfe6b8; background: #1a2414; border-color: #2f4321; }
    html.dark .fq-jump button:hover { background: #22301a; }
    html.dark .fq-sec-h { background: #151b12; border-color: #222b1a; }
    html.dark .fq-sec-h > span { background: #1c2616; }
    html.dark .fq-sec-h b { color: #e8efe1; }
    html.dark .fq-sec-h em { color: #a9b69c; background: #1c2616; }
    html.dark .fq-sec-h em.is-done { color: #cfe6b8; background: #22301a; }
    @media (prefers-reduced-motion: reduce) { .fq-jump button, .fq-sec-h em { transition: none !important; } }
    @keyframes fqFade { from { opacity: 0; } }
    @keyframes fqUp { from { transform: translateY(100%); } }
    @keyframes fqPopC { from { opacity: 0; transform: translate(-50%, -46%) scale(.97); } }
    .fq-box:focus { outline: none; }
    .fq-grip { flex: none; width: 2.6rem; height: .3rem; margin: .55rem auto 0; border-radius: 999px; background: #d7dfcd; }
    @media (min-width: 640px) { .fq-grip { display: none; } }
    .fq-head { flex: none; display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem .55rem 1.15rem; }
    .fq-head h2 { flex: 1; min-width: 0; font-family: var(--font-heading); font-weight: 800; font-size: 1.1rem; color: #14210c; }
    .fq-head h2 small { display: block; font-family: inherit; font-size: .76rem; font-weight: 600; color: #6b7280; }
    .fq-x { flex: none; width: 2.4rem; height: 2.4rem; border-radius: 999px; display: grid; place-items: center; color: #4b5563; background: #f3f5ef; transition: background-color .28s ease; }
    .fq-x:hover { background: #e7ecdf; }
    .fq-x svg { width: 1.1rem; height: 1.1rem; }
    /* The troubles sheet's crop filter (2026-10-16): every crop's troubles, narrowed on request. */
    .fq-cropf { flex: none; display: flex; align-items: center; gap: .55rem; margin: 0 1rem .55rem; min-width: 0; }
    .fq-cropf > span { flex: none; font-size: .78rem; font-weight: 700; color: #5b6650; }
    .fq-cropbtn { flex: 1; min-width: 0; display: flex; align-items: center; gap: .45rem; min-height: 2.6rem; padding: .3rem .8rem; border-radius: 999px; text-align: left;
        background: #fff; border: 1.5px solid #d5e3c5; color: #2d5016; transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .fq-cropbtn:hover { border-color: #86b556; background: #f8fbf4; }
    .fq-cropbtn.is-on { background: #eef5e6; border-color: #86b556; }
    .fq-cropbtn i { flex: none; font-style: normal; font-size: 1rem; }
    .fq-cropbtn b { flex: 1; min-width: 0; font-size: .86rem; font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fq-cropbtn svg { flex: none; width: .95rem; height: .95rem; color: #6b7a5c; }
    .fq-cropbtn.is-bump { animation: fqBump .4s cubic-bezier(.22,1,.36,1); }
    @keyframes fqBump { 40% { transform: scale(1.03); } }
    .fq-gh small { display: inline; margin-left: .35rem; font-size: .66rem; font-weight: 700; letter-spacing: 0; text-transform: none; color: #8a9480; }
    html.dark .fq-cropf > span { color: #a9b69c; }
    html.dark .fq-cropbtn { background: #151b12; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .fq-cropbtn.is-on { background: #1c2914; border-color: #6b9f3d; }
    @media (prefers-reduced-motion: reduce) { .fq-cropbtn { transition: none; } .fq-cropbtn.is-bump { animation: none; } }
    .fq-find { flex: none; margin: 0 1rem .5rem; display: flex; align-items: center; gap: .5rem; padding: 0 .8rem; border-radius: .9rem; background: #f6f8f3; border: 1px solid #e1e9d7; }
    .fq-find:focus-within { border-color: #86b556; background: #fff; }
    .fq-find svg { flex: none; width: 1.05rem; height: 1.05rem; color: #6b9f3d; }
    .fq-find input { flex: 1; min-width: 0; min-height: 2.75rem; border: 0; background: transparent; font-size: 1rem; color: #14210c; outline: none; box-shadow: none; }
    .fq-list { flex: 1; min-height: 0; overflow-y: auto; overscroll-behavior: contain; padding: 0 .6rem 1rem; }
    .fq-gh { position: sticky; top: 0; z-index: 1; display: block; padding: .6rem .55rem .4rem; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; background: #fff; }
    .fq-row { width: 100%; display: flex; align-items: center; gap: .65rem; padding: .5rem .55rem; border-radius: .9rem; text-align: left; color: #14210c; transition: background-color .28s ease; }
    .fq-row:hover { background: #f4f8ef; }
    .fq-row[aria-checked="true"] { background: #eef5e6; }
    .fq-row:disabled { opacity: .5; cursor: default; }
    .fq-ri { flex: none; width: 2.6rem; height: 2.6rem; border-radius: 999px; overflow: hidden; display: grid; place-items: center; font-size: 1.2rem; background: #f3f6ef; border: 1px solid #e5ebdf; }
    .fq-ri img { width: 100%; height: 100%; object-fit: cover; }
    .fq-rt { min-width: 0; flex: 1; }
    .fq-rt b { display: block; font-size: .92rem; font-weight: 700; line-height: 1.3; overflow-wrap: anywhere; }
    .fq-rt small { display: block; font-size: .74rem; color: #6b7280; line-height: 1.35; overflow-wrap: anywhere; }
    .fq-ck { flex: none; width: 1.45rem; height: 1.45rem; border-radius: .45rem; display: grid; place-items: center; border: 2px solid #cbd5c1; color: #fff; transition: background-color .28s ease, border-color .28s ease; }
    .fq-ck svg { width: .8rem; height: .8rem; opacity: 0; transition: opacity .2s ease; }
    .fq-row[aria-checked="true"] .fq-ck { background: var(--fq-g, #4a7c2a); border-color: var(--fq-g, #4a7c2a); }
    .fq-row[aria-checked="true"] .fq-ck svg { opacity: 1; }
    .fq-nothing { padding: 1.4rem 1rem; text-align: center; font-size: .9rem; color: #6b7280; }
    .fq-foot { flex: none; display: flex; align-items: center; gap: .7rem; padding: .75rem 1rem calc(.85rem + env(safe-area-inset-bottom)); border-top: 1px solid #eef2ea; }
    @media (min-width: 640px) { .fq-foot { padding-bottom: .85rem; } }
    .fq-foot .btn { flex: none; min-height: 2.9rem; padding-left: 1.2rem; padding-right: 1.2rem; }
    .fq-foot > span { flex: 1; min-width: 0; font-size: .86rem; font-weight: 800; color: #374151; }
    .fq-body { flex: 1; min-height: 0; overflow-y: auto; padding: .2rem 1.15rem 1rem; }
    .fq-prog { flex: none; height: .28rem; margin: 0 1.15rem .5rem; border-radius: 999px; background: #eef2ea; overflow: hidden; }
    .fq-prog i { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #86b556, #4a7c2a); transition: width .4s var(--fq-e, ease); }
    html.fq-lock { overflow: hidden; }

    @media (prefers-reduced-motion: reduce) {
        .fq-page.is-in, .fq-same, .fq-copied, .fq-saved, .fq-tc, .fq-box, .fq-sheet::backdrop, .fq-sheet.is-closing .fq-box, .fq-sheet.is-closing::backdrop { animation: none !important; }
        .fq-o, .fq-m, .fq-m::after, .fq-tick, .fq-dots u, .fq-btn, .fq-add, .fq-none, .fq-prog i { transition: none !important; }
    }

    /* Night. */
    html.dark .fq-t, html.dark .fq-q h3, html.dark .fq-same b, html.dark .fq-copied b, html.dark .fq-saved b, html.dark .fq-head h2, html.dark .fq-row, html.dark .fq-o, html.dark .fq-tc { color: #e8efe1; }
    html.dark .fq-s, html.dark .fq-why, html.dark .fq-same small, html.dark .fq-saved small, html.dark .fq-ot small, html.dark .fq-rt small { color: #a9b69c; }
    html.dark .fq-q { border-color: #222b1a; }
    html.dark .fq-o, html.dark .fq-none, html.dark .fq-phr input, html.dark .fq-tc { background: #151b12; border-color: #2b3a1c; }
    html.dark .fq-none { color: #d5e3c5; }
    html.dark .fq-o:hover { border-color: #4a6b2c; }
    html.dark .fq-o.is-on { background: #22301a; border-color: #6b9f3d; }
    html.dark .fq-m { border-color: #4b5a40; }
    html.dark .fq-lot { background: color-mix(in srgb, var(--lc, #4a7c2a) 14%, #151b12); border-color: color-mix(in srgb, var(--lc, #4a7c2a) 35%, #151b12); }
    html.dark .fq-lot b { color: #e8efe1; }
    html.dark .fq-lot span { color: #b7c2ad; }
    html.dark .fq-dots u { background: color-mix(in srgb, var(--lc, #4a7c2a) 38%, #2b3a1c); }
    html.dark .fq-dots u.is-done { background: color-mix(in srgb, var(--lc, #4a7c2a) 70%, #2b3a1c); }
    html.dark .fq-dots u.is-on { background: var(--lc, #4a7c2a); box-shadow: 0 0 0 2px color-mix(in srgb, var(--lc, #4a7c2a) 35%, transparent); }
    html.dark .fq-same { background: linear-gradient(115deg, #1a2414, #1f2c17); border-color: #3d5a24; }
    html.dark .fq-same > span, html.dark .fq-saved > span { background: #151b12; border-color: #2b3a1c; color: #a5c97e; }
    html.dark .fq-copied { background: #0f2a20; border-color: #1f5c43; }
    html.dark .fq-copied small { color: #a7d7c0; }
    html.dark .fq-copied.is-edited { background: #2a210a; border-color: #4d3a0d; }
    html.dark .fq-saved { background: #1a2114; border-color: #2b3a1c; }
    html.dark .fq-btn.is-soft { background: #1c2616; border-color: #2b3a1c; color: #cfe6b8; }
    html.dark .fq-ph { background: #1a2114; border-color: #2b3a1c; }
    html.dark .fq-ph > label { color: #d5e3c5; }
    html.dark .fq-phr input { color: #e8efe1; }
    html.dark .fq-tc > span:first-child, html.dark .fq-ri { background: #1c2616; border-color: #2b3a1c; }
    html.dark .fq-box { background: #151b12; }
    html.dark .fq-gh { background: #151b12; color: #a5c97e; }
    html.dark .fq-x { background: #1c2616; color: #d5e3c5; }
    html.dark .fq-find { background: #1c2616; border-color: #2b3a1c; }
    html.dark .fq-find input { color: #e8efe1; }
    html.dark .fq-row:hover, html.dark .fq-row[aria-checked="true"] { background: #1c2616; }
    html.dark .fq-foot { border-color: #222b1a; }
    html.dark .fq-foot > span { color: #d5e3c5; }
    html.dark .fq-prog { background: #222b1a; }
</style>
@endpush

@push('sheets')
{{-- The troubles of the last crop: search, photos, tick many. --}}
<dialog class="fq-sheet" id="fqTroubleSheet" aria-labelledby="fqTroubleTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><h2 id="fqTroubleTitle">What troubled the last crop?<small>The last crop grown on this lot, whatever it was, not the one you plan to plant now. Tick everything that hurt it.</small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="fq-cropf"><span>Show the troubles of</span><button type="button" class="fq-cropbtn" id="fqTroubleCrop" aria-haspopup="dialog"><i aria-hidden="true">🌱</i><b>Every crop</b><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg></button></div>
        <label class="fq-find"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg><span class="sr-only">Search</span><input type="search" id="fqTroubleQ" placeholder="Search, like tungro or rats" autocomplete="off" maxlength="40"></label>
        <div class="fq-list" id="fqTroubleList"></div>
        <div class="fq-foot"><span id="fqTroubleCount">None ticked</span><button type="button" class="btn btn-primary" data-fq-close>Done</button></div>
    </div>
</dialog>

{{-- The weeds and grasses catalogue, opened from the weeds question: look, not pick. --}}
<dialog class="fq-sheet is-full" id="fqWeedSheet" aria-labelledby="fqWeedTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><button type="button" class="fq-x fqw-back" id="fqWeedBack" aria-label="Back to the weeds" hidden><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button><h2 id="fqWeedTitle">Weeds and grasses<small id="fqWeedSub">Tap one to see it up close</small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="fqw-tools" id="fqWeedTools">
            <label class="fq-find"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg><span class="sr-only">Search</span><input type="search" id="fqWeedQ" placeholder="Search any name, like kulape or ubod ubod" autocomplete="off" maxlength="40"></label>
            <div class="fqw-groups" id="fqWeedGroups" role="group" aria-label="Weed group"></div>
            <p class="fqw-ghint" id="fqWeedHint" hidden></p>
        </div>
        <div class="fq-list" id="fqWeedList"></div>
        <div class="fq-body fqw-one" id="fqWeedOne" hidden></div>
    </div>
</dialog>

{{-- The crop filter of the troubles sheet: every crop with troubles of its own. --}}
<dialog class="fq-sheet" id="fqCropSheet" aria-labelledby="fqCropTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><h2 id="fqCropTitle">Which crop was it?<small>Only to narrow the list. Pick Every crop to see them all.</small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <label class="fq-find"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg><span class="sr-only">Search</span><input type="search" id="fqCropQ" placeholder="Search a crop" autocomplete="off" maxlength="40"></label>
        <div class="fq-list" id="fqCropList"></div>
    </div>
</dialog>

{{-- One soil test result: when, kit or lab, and what it said. --}}
<dialog class="fq-sheet" id="fqTestSheet" aria-labelledby="fqTestTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><h2 id="fqTestTitle">A soil test result<small>Type what the result paper says. Leave out anything it does not have.</small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="fq-body fq" id="fqTestBody"></div>
        <div class="fq-foot"><button type="button" class="btn btn-white fqt-del" id="fqTestDel" hidden>Remove it</button><span></span><button type="button" class="btn btn-white" data-fq-close>Cancel</button><button type="button" class="btn btn-primary" id="fqTestSave">Save the result</button></div>
    </div>
</dialog>

{{-- Copy another lot's answers. --}}
<dialog class="fq-sheet is-small" id="fqCopySheet" aria-labelledby="fqCopyTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><h2 id="fqCopyTitle">Same as which lot?<small>Every answer is copied, and you can still change them</small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="fq-list" id="fqCopyList"></div>
    </div>
</dialog>

{{-- The farm's saved field profiles. --}}
<dialog class="fq-sheet" id="fqSavedSheet" aria-labelledby="fqSavedTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><h2 id="fqSavedTitle">Use the answers from<small>Fields you described before</small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="fq-list" id="fqSavedList"></div>
    </div>
</dialog>

{{-- The whole profile in one sheet, for a page that asks it outside a wizard. --}}
<dialog class="fq-sheet is-full" id="fqOpenSheet" aria-labelledby="fqOpenTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><h2 id="fqOpenTitle">Your field<small id="fqOpenStep"></small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="fq-prog" aria-hidden="true"><i id="fqOpenBar"></i></div>
        <div class="fq-body" id="fqOpenBody"></div>
        <div class="fq-foot"><button type="button" class="btn btn-white" id="fqOpenBack">Back</button><span></span><button type="button" class="btn btn-primary" id="fqOpenNext">Next</button></div>
    </div>
</dialog>

{{-- One question on its own (aneeFieldProfile.ask): change one answer and save it. --}}
<dialog class="fq-sheet is-ask" id="fqAskSheet" aria-labelledby="fqAskTitle">
    <div class="fq-box" tabindex="-1">
        <div class="fq-grip" aria-hidden="true"></div>
        <div class="fq-head"><h2 id="fqAskTitle">One answer<small id="fqAskSub"></small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <div class="fq-body" id="fqAskBody"></div>
        <div class="fq-foot"><button type="button" class="btn btn-white" data-fq-close>Cancel</button><span></span><button type="button" class="btn btn-primary" id="fqAskSave">Save the answer</button></div>
    </div>
</dialog>
@endpush

@push('scripts')
@php
    $fqData = ['bank' => \App\Support\FieldProfile::forPayload(null), 'other' => \App\Support\FieldProfile::OTHER,
        'urls' => ['troubles' => route('fieldprofile.troubles'), 'weeds' => route('fieldprofile.weeds'), 'list' => route('fieldprofile.list'), 'save' => route('fieldprofile.save')]];
@endphp
<script>
(() => {
    const D = @json($fqData);
    const G = D.bank.groups, Q = D.bank.questions;
    const $ = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const reduce = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const clone = (o) => JSON.parse(JSON.stringify(o || {}));
    const COLORS = ['#4a7c2a', '#d97706', '#0284c7', '#7c3aed', '#db2777', '#0f766e', '#b45309', '#4f46e5', '#65a30d', '#dc2626', '#0891b2', '#9333ea'];
    const CHECK = '<svg fill="none" stroke="currentColor" stroke-width="3.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
    const XS = '<svg fill="none" stroke="currentColor" stroke-width="2.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>';
    const BULB = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18h6M10 21h4M12 3a6 6 0 00-3.5 10.9c.6.4 1 1.1 1 1.8V16h5v-.3c0-.7.4-1.4 1-1.8A6 6 0 0012 3z"/></svg>';
    const COPY = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2.5"/><path stroke-linecap="round" d="M16 8V6.5A2.5 2.5 0 0013.5 4h-7A2.5 2.5 0 004 6.5v7A2.5 2.5 0 006.5 16H8"/></svg>';
    const BOOK = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 4.5A1.5 1.5 0 016.5 3H19v15H6.5A1.5 1.5 0 005 19.5v-15zM5 19.5A1.5 1.5 0 006.5 21H19M9 7h6"/></svg>';
    const PLUS = '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>';
    const isRice = (crop) => String(crop || '').startsWith('rice');
    // Each page's icon and short name, for the one form's titles and page buttons. Every group is listed
    // (as TILE, PREV_ICON and PREV_NAME are in partials/farm-lots and TILES in field-profile-card).
    const PAGE_ICON = { soil: '🟤', water: '💧', acid: '🧪', land: '⛰️', last: '🌾', weeds: '🌿', problems: '🐛' };
    const PAGE_SHORT = { soil: 'Soil', water: 'Water', acid: 'Acidity', land: 'The land', last: 'Last season', weeds: 'Weeds', problems: 'Troubles' };

    /* Tagalog help flags (data-tl, see partials/tagalog-flags): the technical
       questions by key, and the technical answers by question.answer, with a
       1 when the term sits in the answer's small line. The lot-* keys are the
       lot questions' own words (2026-10-21, the owner): what the question or
       the answer means, never why it is asked. */
    const TLQ = { texture: 'lot-texture', organic: 'organic-matter', water: 'water-source', drainage: 'drainage', flooding: 'lot-flood',
        salt: 'saline', lastLimed: 'lot-lime', hardpan: 'hardpan' };
    const TLO = { 'texture.sandy': ['sandy', 1], 'texture.loam': ['loam', 1], 'texture.clay': ['clay', 1], 'texture.heavy_clay': ['heavy-clay'],
        'signs.rust': ['lot-sign-rust'], 'signs.sheen': ['lot-sign-sheen'], 'signs.smell': ['lot-sign-smell'], 'water.irrigation': ['irrigation'], 'water.rainfed': ['rainfed'],
        'salt.crust': ['lot-salt-crust'], 'salt.sea': ['lot-salt-sea'], 'salt.tips': ['lot-salt-tips'], 'salt.sodic': ['lot-salt-sodic'],
        'acidity.sulfate': ['acid-sulfate'], 'acidity.alkaline': ['alkaline'], 'stubble.ratoon': ['ratoon'] };
    const tlAt = (key) => (key ? ' data-tl="' + esc(key) + '"' : '');
    const tlFlag = (key) => (window.aneeTl ? window.aneeTl.flag(key) : '');

    /* ---- the bank, for a crop ---- */
    const qsFor = (crop, page) => Object.entries(Q).filter(([, q]) => q.page === page && !(crop && q.only === 'rice' && !isRice(crop)))
        .map(([k, q]) => [k, Object.assign({}, q, { o: Object.fromEntries(Object.entries(q.o || {}).filter(([, o]) => !(crop && o[2] === 'rice' && !isRice(crop)))) })]);
    const pagesFor = (crop) => Object.keys(G).filter((p) => qsFor(crop, p).length);
    const count = (v) => Object.keys(v || {}).filter((k) => Q[k] && v[k] !== undefined && !(Array.isArray(v[k]) && !v[k].length)).length + (v && v.phFrom != null && !v.acidity ? 1 : 0);
    /* Every answer in the shape the bank asks for NOW. A lot saved before a
       question took several picks kept one word ("salt": "crust" before
       2026-10-16), and a list where one word belongs breaks the other way:
       either made the question refuse every tap. Every way in goes through
       this, so an old answer opens as a ticked box and the rest stay tappable. */
    function shape(v) {
        const out = clone(v);
        Object.keys(out).forEach((k) => {
            const q = Q[k];
            if (!q) return;
            const x = out[k];
            if (q.multi) {
                const a = [...new Set([].concat(x == null ? [] : x).filter((y) => typeof y === 'string' && y !== ''))];
                if (a.length) out[k] = a; else delete out[k];
            } else if (Array.isArray(x)) {
                const one = x.find((y) => typeof y === 'string' && y !== '');
                if (one !== undefined) out[k] = one; else delete out[k];
            } else if (x === null || x === '') {
                delete out[k];
            }
        });
        return out;
    }
    const TNAMES = {};
    const tname = (k) => TNAMES[k] || (k.startsWith('other:') && D.other[k.slice(6)] ? D.other[k.slice(6)][0] : k.replace(/^\w+:/, '').replace(/-/g, ' '));
    function words(v) {
        v = v || {};
        const bits = [];
        Object.entries(Q).forEach(([k, q]) => {
            const a = v[k];
            if (a === undefined || (Array.isArray(a) && !a.length)) return;
            if (k === 'outbreaks') { bits.push(a.includes('none') ? 'no troubles' : a.map(tname).join(', ')); return; }
            bits.push([].concat(a).map((x) => (q.o[x] || [x])[0]).join(', '));
        });
        return bits.join(' · ');
    }

    /* ---- the soil test log (2026-10-16, the owner) ----
       The browser's side of FieldProfile::cleanTests, testLatest, testLevels
       and testWords: one list of results, newest first, an undated one last.
       The newest pH is the lot's pH (phFrom, phTo). A lot from before the log
       kept one pH range: it shows as an undated first result. */
    const T_NUMS = { ph: [3, 10], om: [0, 100], n: [0, 5], p: [0, 1000], k: [0, 5000], zn: [0, 200], s: [0, 1000], b: [0, 50], ec: [0, 100] };
    const T_KIT = [['nL', 'Nitrogen (N)'], ['pL', 'Phosphorus (P)'], ['kL', 'Potassium (K)']];
    const T_LAB = [['om', 'Organic matter', '%'], ['n', 'Total nitrogen', '%'], ['p', 'Phosphorus', 'ppm'], ['k', 'Potassium', ''], ['zn', 'Zinc', 'ppm'], ['s', 'Sulfur', 'ppm'], ['b', 'Boron', 'ppm'], ['ec', 'Salinity (EC)', 'dS/m']];
    const T_LV = { low: 'Low', medium: 'Medium', high: 'High' };
    const T_KIND = { kit: 'Soil test kit', lab: 'Laboratory' };
    const tNum = (v) => String(Math.round(Number(v) * 1000) / 1000);
    function tList(v) {
        v = v || {};
        if (Array.isArray(v.tests)) return v.tests.slice();
        return v.phFrom != null ? [{ id: 't0', date: null, kind: '', ph: Number(v.phFrom), ...(v.phTo != null && Number(v.phTo) !== Number(v.phFrom) ? { phTo: Number(v.phTo) } : {}) }] : [];
    }
    function tSort(list) {
        return list.map((t, i) => [t, i]).sort((a, b) => ((a[0].date ? 0 : 1) - (b[0].date ? 0 : 1)) || String(b[0].date || '').localeCompare(String(a[0].date || '')) || (a[1] - b[1])).map((x) => x[0]);
    }
    function tApply(v, list) {
        v.tests = tSort(list);
        const w = v.tests.find((t) => t.ph != null);
        if (w) { v.phFrom = w.ph; v.phTo = w.phTo != null ? w.phTo : w.ph; } else { delete v.phFrom; delete v.phTo; }
        return v;
    }
    function tLatest(list) {
        let out = null;
        const twin = { nL: ['om', 'n'], om: ['nL'], n: ['nL'], pL: ['p'], p: ['pL'], kL: ['k'], k: ['kL'] };
        (list || []).forEach((t) => {
            if (!t) return;
            if (!out) out = { date: t.date || null, kind: t.kind || '' };
            Object.keys(T_NUMS).concat(['nL', 'pL', 'kL', 'phTo']).forEach((k) => {
                if (t[k] == null || out[k] != null || (twin[k] || []).some((x) => out[x] != null)) return;
                if (k === 'phTo' && out.ph !== t.ph) return;
                out[k] = t[k];
                if (k === 'p') out.pMethod = t.pMethod || 'olsen';
                if (k === 'k') out.kUnit = t.kUnit || 'ppm';
            });
        });
        return out;
    }
    function tLevels(t) {
        if (!t) return {};
        const band = (v, lo, hi) => (v < lo ? 'low' : (v <= hi ? 'medium' : 'high'));
        const out = {};
        if (t.nL) out.N = t.nL; else if (t.om != null) out.N = band(t.om, 2, 4); else if (t.n != null) out.N = band(t.n, 0.1, 0.2);
        if (t.pL) out.P2O5 = t.pL; else if (t.p != null) out.P2O5 = t.pMethod === 'bray' ? band(t.p, 15, 30) : band(t.p, 10, 20);
        if (t.kL) out.K2O = t.kL; else if (t.k != null) out.K2O = band(t.kUnit === 'meq' ? t.k : t.k / 391, 0.2, 0.4);
        if (t.zn != null) out.Zn = t.zn < 0.8 ? 'low' : 'ok';
        return out;
    }
    function tWords(t) {
        const lv = tLevels(t), bits = [];
        if (t.ph != null) bits.push('pH ' + tNum(t.ph) + (t.phTo != null ? ' to ' + tNum(t.phTo) : ''));
        T_KIT.forEach(([k, l]) => { if (t[k]) bits.push(l.replace(/ \(.*$/, '') + ' ' + t[k]); });
        const lab = { om: (x) => 'organic matter ' + x + '%', n: (x) => 'nitrogen ' + x + '%', p: (x) => 'P ' + x + ' ppm' + (lv.P2O5 && !t.pL ? ', ' + lv.P2O5 : ''),
            k: (x) => 'K ' + x + (t.kUnit === 'meq' ? ' meq/100 g' : ' ppm') + (lv.K2O && !t.kL ? ', ' + lv.K2O : ''), zn: (x) => 'zinc ' + x + ' ppm', s: (x) => 'sulfur ' + x + ' ppm', b: (x) => 'boron ' + x + ' ppm', ec: (x) => 'EC ' + x + ' dS/m' };
        Object.entries(lab).forEach(([k, f]) => { if (t[k] != null) bits.push(f(tNum(t[k]))); });
        return bits.join(' · ');
    }
    const tDate = (t) => (t && t.date ? new Date(t.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Date not known');
    function testsHtml(v) {
        const list = tList(v);
        return '<div class="fq-tests" data-tests><div class="fq-tests-h"><span aria-hidden="true">🧪</span><div><b>Soil tests</b><small>'
            + (list.length ? 'Newest first. The newest pH counts for the lot. Older results stay here for comparing.' : 'Had the soil tested, by a kit or a laboratory? Add the result with the day it was sampled. Older results can go in too.')
            + '</small></div></div>'
            + (list.length ? '<ul class="fq-tl">' + list.map((t, i) => '<li><div><b>' + esc(tDate(t)) + '<em' + (i ? ' class="is-old"' : '') + '>' + (i ? 'Older' : 'Newest') + '</em></b><small>'
                + esc([T_KIND[t.kind], tWords(t)].filter(Boolean).join(' · ')) + (t.note ? '<br>' + esc(t.note) : '') + '</small></div><button type="button" data-st-edit="' + esc(t.id) + '">Edit</button></li>').join('') + '</ul>' : '')
            + '<button type="button" class="fq-add" data-st-add>' + PLUS + (list.length ? 'Add another result' : 'Add a soil test result') + '</button></div>';
    }
    /* One result in its sheet. Resolves {test}, {remove: true}, or null when closed. */
    function editTest(t) {
        return new Promise((resolve) => {
            const dlg = $('fqTestSheet'), body = $('fqTestBody');
            const w = Object.assign({ kind: '' }, t ? JSON.parse(JSON.stringify(t)) : {});
            const today = new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10);
            const pills = (k, opts, on) => '<div class="fqt-pills" role="radiogroup" data-pk="' + k + '">' + Object.entries(opts).map(([x, l]) => '<button type="button" data-pv="' + x + '" aria-pressed="' + (on === x) + '"' + (on === x ? ' class="is-on"' : '') + '>' + esc(l) + '</button>').join('') + '</div>';
            const kindBtn = (k, b, sm) => '<button type="button" class="fq-o' + (w.kind === k ? ' is-on' : '') + '" data-kind="' + k + '" aria-pressed="' + (w.kind === k) + '"><span class="fq-m" aria-hidden="true"></span><span class="fq-ot"><b>' + b + '</b><small>' + sm + '</small></span></button>';
            body.innerHTML = '<div class="fqt-f"><label class="fqt-l" for="fqtDate">When was the soil sampled? <small>(leave it empty if you do not remember)</small></label><input type="date" class="form-input" id="fqtDate" max="' + today + '" value="' + esc(w.date || '') + '"></div>'
                + '<div class="fqt-f"><span class="fqt-l">How was it tested?</span><div class="fq-opts fqt-kind" role="radiogroup">'
                + kindBtn('kit', 'A soil test kit', 'Colors on a chart, read as low, medium or high. Often from the MAO or the DA.') + kindBtn('lab', 'A soils laboratory', 'A printed result with numbers') + '</div></div>'
                // One pH, or a range when the result gives one (2026-10-21, the owner).
                + '<div class="fqt-f"><label class="fqt-l" for="fqtPh">pH <small>(optional)</small></label><div class="fqt-ph"><input type="text" inputmode="decimal" maxlength="4" class="form-input fqt-n" id="fqtPh" placeholder="Like 5.8" value="' + esc(w.ph != null ? tNum(w.ph) : '') + '">'
                + '<span>to</span><input type="text" inputmode="decimal" maxlength="4" class="form-input fqt-n" id="fqtPhTo" placeholder="Like 6.2" aria-label="pH, the high end of a range" value="' + esc(w.phTo != null ? tNum(w.phTo) : '') + '"></div>'
                + '<p class="fqt-was">One number, or a range like 5.5 to 6.0 when the result gives one. Leave the second box empty for one number.</p></div>'
                + '<div class="fqt-sec" data-for="kit"' + (w.kind === 'kit' ? '' : ' hidden') + '><p>Tap the level the kit showed. Skip what it did not test.</p>'
                + T_KIT.map(([k, l]) => '<div class="fqt-lv"><span>' + esc(l) + '</span>' + pills(k, T_LV, w[k]) + '</div>').join('') + '</div>'
                + '<div class="fqt-sec" data-for="lab"' + (w.kind === 'lab' ? '' : ' hidden') + '><p>Type the numbers as printed. Leave a box empty if the result does not have it.</p><div class="fqt-grid">'
                + T_LAB.map(([k, l, u]) => '<label><span>' + esc(l) + (u ? ' <small>' + esc(u) + '</small>' : '') + '</span><input type="text" inputmode="decimal" maxlength="8" class="form-input" data-tn="' + k + '" value="' + esc(w[k] != null ? tNum(w[k]) : '') + '">'
                    + (k === 'p' ? pills('pMethod', { olsen: 'Olsen', bray: 'Bray' }, w.pMethod || 'olsen') : '') + (k === 'k' ? pills('kUnit', { ppm: 'ppm', meq: 'meq/100 g' }, w.kUnit || 'ppm') : '') + '</label>').join('')
                + '</div></div>'
                + '<div class="fqt-f"><label class="fqt-l" for="fqtNote">A note <small>(optional)</small></label><input type="text" class="form-input" id="fqtNote" maxlength="120" placeholder="Like the laboratory, or which part of the lot" value="' + esc(w.note || '') + '"></div>'
                + '<p class="fq-err" id="fqtErr" hidden></p>';
            $('fqTestDel').hidden = !t;
            let out = null;
            const err = (m) => { $('fqtErr').textContent = m; $('fqtErr').hidden = !m; if (m) $('fqtErr').scrollIntoView({ block: 'nearest', behavior: reduce() ? 'auto' : 'smooth' }); };
            body.onclick = (e) => {
                const kb = e.target.closest('[data-kind]');
                if (kb) {
                    w.kind = kb.dataset.kind;
                    body.querySelectorAll('[data-kind]').forEach((b) => { const on = b === kb; b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', String(on)); });
                    body.querySelectorAll('[data-for]').forEach((sec) => { sec.hidden = sec.dataset.for !== w.kind; });
                    return;
                }
                const pv = e.target.closest('[data-pv]');
                if (pv) {
                    const k = pv.closest('[data-pk]').dataset.pk, x = pv.dataset.pv;
                    // A kit level taps off again; the P method and the K unit always keep one.
                    w[k] = (w[k] === x && k.endsWith('L')) ? undefined : x;
                    pv.parentNode.querySelectorAll('[data-pv]').forEach((b) => { const on = w[k] === b.dataset.pv; b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', String(on)); });
                }
            };
            $('fqTestSave').onclick = () => {
                const r = { id: (t && t.id) || ('t' + Math.random().toString(36).slice(2, 9)), date: $('fqtDate').value || null, kind: w.kind || '' };
                if (r.date && r.date > today) { err('The day it was sampled cannot be after today.'); return; }
                const num = (raw, k) => { raw = String(raw || '').trim().replace(',', '.'); if (raw === '') return null; const n = Number(raw); const [lo, hi] = T_NUMS[k]; return isFinite(n) && n >= lo && n <= hi ? n : NaN; };
                let ph = num($('fqtPh').value, 'ph'), phTo = num($('fqtPhTo').value, 'ph');
                if (Number.isNaN(ph) || Number.isNaN(phTo)) { err('A pH is a number from 3 to 10, like 5.8.'); (Number.isNaN(ph) ? $('fqtPh') : $('fqtPhTo')).focus(); return; }
                // Only the second box filled is one pH; a range typed high to low reads low to high.
                if (ph == null && phTo != null) { ph = phTo; phTo = null; }
                if (ph != null) {
                    r.ph = Math.round(Math.min(ph, phTo ?? ph) * 10) / 10;
                    const hi = Math.round(Math.max(ph, phTo ?? ph) * 10) / 10;
                    if (hi !== r.ph) r.phTo = hi;
                }
                if (r.kind === 'kit') T_KIT.forEach(([k]) => { if (w[k]) r[k] = w[k]; });
                if (r.kind === 'lab') {
                    for (const inp of body.querySelectorAll('[data-tn]')) {
                        const n = num(inp.value, inp.dataset.tn);
                        if (Number.isNaN(n)) { err('Check the ' + T_LAB.find((x) => x[0] === inp.dataset.tn)[1].toLowerCase() + ': it is not a number in the usual range.'); inp.focus(); return; }
                        if (n != null) r[inp.dataset.tn] = n;
                    }
                    if (r.p != null) r.pMethod = w.pMethod === 'bray' ? 'bray' : 'olsen';
                    if (r.k != null) r.kUnit = w.kUnit === 'meq' ? 'meq' : 'ppm';
                }
                const note = $('fqtNote').value.trim().replace(/\s+/g, ' ').slice(0, 120);
                if (note) r.note = note;
                if (!Object.keys(r).some((k) => !['id', 'date', 'kind', 'note'].includes(k))) { err(r.kind ? 'Add at least one result: the pH, a level or a number.' : 'Pick how it was tested, then add what it said.'); return; }
                out = { test: r };
                sheetClose(dlg);
            };
            $('fqTestDel').onclick = () => { out = { remove: true }; sheetClose(dlg); };
            once(dlg).then(() => resolve(out));
            sheetOpen(dlg);
            body.scrollTop = 0;
        });
    }
    /* A log edit from a page: add, change or remove one result, then hand the new answers back. */
    async function editTestIn(v, id) {
        const list = tList(v);
        const cur = id ? list.find((t) => t.id === id) : null;
        const r = await editTest(cur);
        if (!r) return false;
        tApply(v, r.remove ? list.filter((t) => t !== cur) : (cur ? list.map((t) => (t === cur ? r.test : t)) : list.concat(r.test)));
        window.toast?.(r.remove ? 'The result is removed. Save to keep the change.' : 'The result is in the log. Save to keep it.');
        return true;
    }
    const TESTS = { list: tList, html: testsHtml, edit: editTest, editIn: editTestIn, apply: tApply, latest: tLatest, levels: tLevels, words: tWords, date: tDate };

    /* ---- the data the sheets need ---- */
    /* Every crop's troubles, once a page (2026-10-16, the owner): the last crop on a lot can be
       any crop, so nothing is narrowed to one; the sheet filters by crop on request. `crop` is
       only remembered as the filter to suggest. */
    const TROUBLES = {};
    let TCROPS = [];
    async function troublesFor() {
        const key = 'all';
        if (!TROUBLES[key]) {
            TROUBLES[key] = window.api(D.urls.troubles + '?crop=all').then((r) => { const rows = (r.data && r.data.rows) || []; TCROPS = (r.data && r.data.crops) || []; rows.forEach((x) => { TNAMES[x.k] = x.n; }); return rows; })
                .catch(() => { delete TROUBLES[key]; return []; });
        }
        return TROUBLES[key];
    }
    let SAVED = null;
    async function savedList(fresh) {
        if (!SAVED || fresh) SAVED = window.api(D.urls.list).then((r) => (r.data && r.data.rows) || []).catch(() => []);
        return SAVED;
    }

    /* ---- the sheets ---- */
    const lock = () => document.documentElement.classList.toggle('fq-lock', !!document.querySelector('.fq-sheet[open]'));
    function sheetOpen(dlg, focus) {
        if (!dlg || dlg.open || !dlg.showModal) return;
        dlg.showModal();
        lock();
        if (window.registerOverlay) window.registerOverlay('fq:' + dlg.id, () => sheetClose(dlg));
        if (focus && matchMedia('(pointer: fine)').matches) focus.focus(); else dlg.querySelector('.fq-box').focus({ preventScroll: true });
    }
    function sheetClose(dlg) {
        if (!dlg.open || dlg.classList.contains('is-closing')) return;
        if (window.unregisterOverlay) window.unregisterOverlay('fq:' + dlg.id);
        const done = () => { dlg.classList.remove('is-closing'); dlg.close(); lock(); dlg.dispatchEvent(new Event('fqclosed')); };
        if (reduce()) { done(); return; }
        dlg.classList.add('is-closing');
        setTimeout(done, 230);
    }
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.fq-sheet').forEach((dlg) => {
            // data-keep: a tap outside does not close it (the one form holds many changes until Save).
            dlg.addEventListener('click', (e) => { if ((e.target === dlg && !dlg.hasAttribute('data-keep')) || e.target.closest('[data-fq-close]')) sheetClose(dlg); });
            dlg.addEventListener('cancel', (e) => { e.preventDefault(); sheetClose(dlg); });
        });
    });
    const once = (dlg) => new Promise((res) => dlg.addEventListener('fqclosed', res, { once: true }));

    /* The troubles sheet works on one lot of one form at a time. */
    let TS = null;   // { form, lot, rows, crop }
    const cropOf = (k) => TCROPS.find((c) => c[0] === k) || null;
    function paintCropBtn() {
        const b = $('fqTroubleCrop'), c = TS && TS.crop ? cropOf(TS.crop) : null;
        b.classList.toggle('is-on', !!c);
        b.querySelector('i').textContent = c ? (c[2] || '🌱') : '🌱';
        b.querySelector('b').textContent = c ? c[1] : 'Every crop';
    }
    function paintTroubles() {
        if (!TS) return;
        const v = TS.form.lot(TS.lot).value;
        const on = new Set((v.outbreaks || []).filter((x) => x !== 'none'));
        const q = $('fqTroubleQ').value.trim().toLowerCase();
        // A ticked trouble always shows, whatever the filter, so it can be unticked.
        const fits = (r) => !TS.crop || !(r.c && r.c.length) || r.c.includes(TS.crop) || on.has(r.k);
        const hit = (r) => fits(r) && (!q || (r.n + ' ' + (r.l || '')).toLowerCase().includes(q));
        const row = (r) => '<button type="button" class="fq-row" role="checkbox" aria-checked="' + on.has(r.k) + '" data-t="' + esc(r.k) + '"><span class="fq-ri">'
            + (r.img ? '<img src="' + esc(r.img) + '" alt="" loading="lazy">' : esc(r.icon || '•')) + '</span><span class="fq-rt"><b>' + esc(r.n) + '</b>' + (r.l ? '<small>' + esc(r.l) + '</small>' : '') + '</span><span class="fq-ck">' + CHECK + '</span></button>';
        const parts = [['other', 'Other troubles'], ['pest', 'Pests'], ['disease', 'Diseases']].map(([g, label]) => {
            const rows = TS.rows.filter((r) => r.g === g && hit(r));
            return rows.length ? '<b class="fq-gh">' + label + '<small>' + rows.length + '</small></b>' + rows.map(row).join('') : '';
        }).join('');
        $('fqTroubleList').innerHTML = parts || '<p class="fq-nothing">' + (TS.rows.length ? (q ? 'Nothing by that name.' : 'No troubles listed for this crop.') : 'Loading the list…') + '</p>';
        $('fqTroubleCount').textContent = on.size ? on.size + (on.size === 1 ? ' ticked' : ' ticked') : 'None ticked';
    }
    /* The crop filter's own list. */
    function paintCrops() {
        const q = $('fqCropQ').value.trim().toLowerCase();
        const cur = TS ? TS.crop : null;
        const rows = TCROPS.filter((c) => !q || (c[1] + ' ' + (c[3] || '')).toLowerCase().includes(q));
        $('fqCropList').innerHTML = (q ? '' : '<button type="button" class="fq-row" data-crop="" aria-checked="' + !cur + '"><span class="fq-ri">🌱</span><span class="fq-rt"><b>Every crop</b><small>All the troubles, of any crop</small></span><span class="fq-ck">' + CHECK + '</span></button>')
            + (rows.length ? rows.map((c) => '<button type="button" class="fq-row" data-crop="' + esc(c[0]) + '" aria-checked="' + (cur === c[0]) + '"><span class="fq-ri">' + esc(c[2] || '🌱') + '</span><span class="fq-rt"><b>' + esc(c[1]) + '</b>'
                + (c[3] && c[3] !== c[1] ? '<small>' + esc(c[3]) + '</small>' : '') + '</span><span class="fq-ck">' + CHECK + '</span></button>').join('') : '<p class="fq-nothing">No crop by that name.</p>');
    }
    document.addEventListener('DOMContentLoaded', () => {
        $('fqTroubleCrop').addEventListener('click', async () => {
            if (!TS) return;
            await troublesFor();
            $('fqCropQ').value = '';
            paintCrops();
            sheetOpen($('fqCropSheet'), $('fqCropQ'));
        });
        $('fqCropQ').addEventListener('input', paintCrops);
        $('fqCropList').addEventListener('click', (e) => {
            const b = e.target.closest('[data-crop]');
            if (!b || !TS) return;
            TS.crop = b.dataset.crop || null;
            sheetClose($('fqCropSheet'));
            paintCropBtn();
            paintTroubles();
            $('fqTroubleList').scrollTop = 0;
            const btn = $('fqTroubleCrop');
            if (!reduce()) { btn.classList.remove('is-bump'); void btn.offsetWidth; btn.classList.add('is-bump'); }
        });
        $('fqTroubleQ').addEventListener('input', paintTroubles);
        $('fqTroubleList').addEventListener('click', (e) => {
            const b = e.target.closest('[data-t]');
            if (!b || !TS) return;
            TS.form.toggleTrouble(TS.lot, b.dataset.t);
            const on = b.getAttribute('aria-checked') !== 'true';
            b.setAttribute('aria-checked', String(on));
            const n = (TS.form.lot(TS.lot).value.outbreaks || []).filter((x) => x !== 'none').length;
            $('fqTroubleCount').textContent = n ? n + ' ticked' : 'None ticked';
        });
    });

    /* ---- the weeds and grasses catalogue (2026-10-21, the owner) ----
       Opened from the weeds question's line, to tell the weeds apart: a list with
       a search and the three groups, each weed up close in the same sheet. */
    let WEEDS = null;
    const WS = { group: '', one: null, scroll: 0 };
    const CHEV = '<svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>';
    function weedsData() {
        if (!WEEDS) WEEDS = window.api(D.urls.weeds).then((r) => (r && r.data) || { rows: [], groups: {} }).catch(() => { WEEDS = null; return null; });
        return WEEDS;
    }
    function paintWeedGroups(data) {
        const rows = (data && data.rows) || [];
        const n = (g) => rows.filter((w) => !g || w.kind === g).length;
        const groups = (data && data.groups) || {};
        $('fqWeedGroups').innerHTML = [['', 'All']].concat(Object.entries(groups).map(([k, g]) => [k, g[0]])).map(([k, label]) => '<button type="button" data-wg="' + esc(k) + '" aria-pressed="' + (WS.group === k) + '"' + (WS.group === k ? ' class="is-on"' : '') + '>' + esc(label) + ' <small>' + n(k) + '</small></button>').join('');
        const hint = WS.group && groups[WS.group] ? groups[WS.group][1] : '';
        $('fqWeedHint').textContent = hint;
        $('fqWeedHint').hidden = !hint;
    }
    function paintWeeds(data) {
        if (!data) { $('fqWeedList').innerHTML = '<p class="fq-nothing">The weeds could not load. Check the connection, then close this and open it again.</p>'; return; }
        const q = $('fqWeedQ').value.trim().toLowerCase();
        const hit = (w) => (!WS.group || w.kind === WS.group) && (!q || [w.name, w.local, w.sci].join(' ').toLowerCase().includes(q));
        const row = (w) => '<button type="button" class="fq-row fqw-row" data-wk="' + esc(w.k) + '"><span class="fq-ri">' + (w.thumb ? '<img src="' + esc(w.thumb) + '" alt="" loading="lazy">' : '🌿') + '</span><span class="fq-rt"><b>' + esc(w.name) + '</b>'
            + (w.local ? '<small>' + esc(w.local) + '</small>' : '') + '</span>' + CHEV + '</button>';
        const parts = Object.entries(data.groups || {}).map(([k, g]) => {
            const rows = data.rows.filter((w) => w.kind === k && hit(w));
            return rows.length ? (WS.group ? '' : '<b class="fq-gh">' + esc(g[0]) + '<small>' + rows.length + '</small></b>') + rows.map(row).join('') : '';
        }).join('');
        const gname = WS.group && data.groups[WS.group] ? data.groups[WS.group][0] : '';
        $('fqWeedList').innerHTML = parts || '<p class="fq-nothing">' + (q ? 'No weed by that name' + (gname ? ' in ' + esc(gname) + '. Tap All to look in every group.' : '.') : 'No weeds in this group.') + '</p>';
    }
    function showWeed(data, k) {
        const w = data && data.rows.find((x) => x.k === k);
        const one = $('fqWeedOne'), list = $('fqWeedList');
        // The list keeps its place while one weed is open (a hidden list forgets its scroll).
        if (w && !WS.one) WS.scroll = list.scrollTop;
        WS.one = w ? k : null;
        $('fqWeedTools').hidden = !!w; list.hidden = !!w; one.hidden = !w; $('fqWeedBack').hidden = !w;
        $('fqWeedTitle').firstChild.textContent = w ? w.name : 'Weeds and grasses';
        $('fqWeedSub').textContent = w ? ((data.groups[w.kind] || [w.kind])[0]) : 'Tap one to see it up close';
        if (!w) { list.scrollTop = WS.scroll || 0; return; }
        const g = data.groups[w.kind] || [];
        const sec = (title, text) => (text ? '<div class="fqw-sec"><b>' + esc(title) + '</b><p>' + esc(text) + '</p></div>' : '');
        one.innerHTML = '<div class="fqw-in">'
            + (w.photo ? '<figure class="fqw-fig"><img src="' + esc(w.photo) + '" alt="' + esc(w.alt || w.name) + '">' + (w.credit ? '<figcaption>Photo: ' + esc(w.credit) + '</figcaption>' : '') + '</figure>' : '')
            + '<h3 class="fqw-name">' + esc(w.name) + '</h3>' + (w.sci ? '<p class="fqw-sci">' + esc(w.sci) + '</p>' : '')
            + '<div class="fqw-chips">' + [g[0], w.life].filter(Boolean).map((x) => '<span>' + esc(x) + '</span>').join('') + '</div>'
            + sec('Local names', w.local) + sec('How to know it', [g[1], w.hint].filter(Boolean).join(' ')) + sec('Found in', (w.crops || []).join(', ')) + sec('How to keep it out', w.stop)
            + '</div>';
        one.scrollTop = 0;
        $('fqWeedBack').focus({ preventScroll: true });
    }
    async function openWeeds() {
        const dlg = $('fqWeedSheet');
        WS.group = ''; WS.one = null; WS.scroll = 0;
        $('fqWeedQ').value = '';
        showWeed(null, null);
        $('fqWeedList').innerHTML = '<p class="fq-nothing">Loading the weeds…</p>';
        $('fqWeedGroups').innerHTML = '';
        $('fqWeedHint').hidden = true;
        sheetOpen(dlg, $('fqWeedQ'));
        const data = await weedsData();
        if (data) paintWeedGroups(data);
        paintWeeds(data);
        $('fqWeedList').scrollTop = 0;
    }
    document.addEventListener('DOMContentLoaded', () => {
        $('fqWeedQ').addEventListener('input', async () => paintWeeds(await weedsData()));
        $('fqWeedGroups').addEventListener('click', async (e) => {
            const b = e.target.closest('[data-wg]');
            if (!b) return;
            WS.group = b.dataset.wg;
            const data = await weedsData();
            paintWeedGroups(data);
            paintWeeds(data);
            $('fqWeedList').scrollTop = 0;
        });
        $('fqWeedList').addEventListener('click', async (e) => {
            const b = e.target.closest('[data-wk]');
            if (b) showWeed(await weedsData(), b.dataset.wk);
        });
        $('fqWeedBack').addEventListener('click', async () => { const k = WS.one; showWeed(await weedsData(), null); if (k) $('fqWeedList').querySelector('[data-wk="' + CSS.escape(k) + '"]')?.focus({ preventScroll: true }); });
    });

    /* ---- one form: a page of one lot at a time ---- */
    function mount(el, o) {
        o = o || {};
        // keys: only these questions are drawn (one answer changed on its own, aneeFieldProfile.ask).
        const st = { crop: o.crop || null, saved: o.saved !== false, onChange: o.onChange, onSkip: o.onSkip, lot: 0, page: null, lots: [], keys: Array.isArray(o.keys) && o.keys.length ? o.keys : null, all: !!o.all };
        const setLots = (lots) => {
            st.lots = (lots && lots.length ? lots : [{ name: o.name || 'Your field', value: o.value || {} }]).map((l, i) => ({
                name: l.name || ('Lot ' + (i + 1)), value: shape(l.value), copiedFrom: l.copiedFrom ?? null, edited: !!l.edited, from: l.from || null,
            }));
        };
        setLots(o.lots);
        el.classList.add('fq');
        const lotOf = (i) => st.lots[i] || st.lots[0];
        const changed = (i) => { const l = lotOf(i); if (l.copiedFrom !== null || l.from) l.edited = true; if (st.onChange) st.onChange(i, clone(l.value), Object.assign({}, l, { value: undefined })); };
        const color = (i) => COLORS[i % COLORS.length];

        function optHtml(k, q, v) {
            const multi = !!q.multi;
            const pills = Object.values(q.o).every((x) => !x[1] && String(x[0]).length <= 16);
            const on = (x) => (multi ? (v[k] || []).includes(x) : v[k] === x);
            return '<div class="fq-opts' + (pills ? ' is-pills' : '') + (multi ? ' is-multi' : '') + '" role="' + (multi ? 'group' : 'radiogroup') + '">'
                + Object.entries(q.o).map(([x, lab]) => { const tl = TLO[k + '.' + x] || []; return '<button type="button" class="fq-o' + (on(x) ? ' is-on' : '') + '" data-v="' + esc(x) + '" aria-pressed="' + on(x) + '"><span class="fq-m" aria-hidden="true"></span><span class="fq-ot"><b' + (tl[1] ? '' : tlAt(tl[0])) + '>' + esc(lab[0]) + '</b>' + (lab[1] ? '<small' + (tl[1] ? tlAt(tl[0]) : '') + '>' + esc(lab[1]) + '</small>' : '') + '</span></button>'; }).join('') + '</div>';
        }
        function troubleHtml(v) {
            const picked = (v.outbreaks || []).filter((x) => x !== 'none');
            const none = (v.outbreaks || []).includes('none');
            const rows = TROUBLES.all;
            return '<div class="fq-tchips">' + picked.map((k) => '<span class="fq-tc"><span data-timg="' + esc(k) + '">•</span><em>' + esc(tname(k)) + '</em><button type="button" data-tx="' + esc(k) + '" aria-label="Remove ' + esc(tname(k)) + '">' + XS + '</button></span>').join('') + '</div>'
                + '<div class="fq-tacts"><button type="button" class="fq-add" data-topen>' + PLUS + (picked.length ? 'Add or change' : 'Choose the troubles') + '</button><button type="button" class="fq-none' + (none ? ' is-on' : '') + '" data-tnone aria-pressed="' + none + '">None last season</button></div>';
        }
        async function fillThumbs() {
            const rows = await troublesFor();
            const by = Object.fromEntries(rows.map((r) => [r.k, r]));
            el.querySelectorAll('[data-timg]').forEach((s) => { const r = by[s.dataset.timg]; if (r) { s.innerHTML = r.img ? '<img src="' + esc(r.img) + '" alt="">' : esc(r.icon || '•'); const em = s.parentNode.querySelector('em'); if (em) em.textContent = r.n; } });
        }
        function qHtml(k, q, v) {
            const done = k === 'outbreaks' ? (v.outbreaks || []).length > 0 : (q.multi ? (v[k] || []).length > 0 : v[k] !== undefined);
            let h = '<section class="fq-q' + (done ? ' is-done' : '') + '" data-k="' + esc(k) + '"><h3><span' + tlAt(TLQ[k]) + '>' + esc(q.q) + '</span><i class="fq-tick" aria-hidden="true">' + CHECK + '</i></h3>'
                + '<p class="fq-why">' + BULB + '<span>' + esc(q.why)
                + (k === 'weeds' ? ' <button type="button" class="fq-wlink" data-wcat aria-haspopup="dialog">See the weeds and grasses</button>' : '') + '</span></p>'
                + (q.multi && k !== 'outbreaks' ? '<p class="fq-many">Pick all that fit</p>' : '');
            h += k === 'outbreaks' ? troubleHtml(v) : optHtml(k, q, v);
            if (q.ph) h += testsHtml(v);
            return h + '</section>';
        }
        async function show(lot, page, back) {
            st.lot = Math.max(0, Math.min(st.lots.length - 1, lot || 0));
            const pages = pagesFor(st.crop);
            st.page = pages.includes(page) ? page : pages[0];
            const l = lotOf(st.lot), v = l.value, pi = pages.indexOf(st.page), first = pi === 0, many = st.lots.length > 1;
            let h = '<div class="fq-page' + (reduce() ? '' : ' is-in' + (back ? ' is-back' : '')) + '">';
            if (st.all) {
                // Every page at once: the page buttons, then each page under its own sticky title.
                el.innerHTML = h + '<nav class="fq-jump" aria-label="Go to a page">' + pages.map((p) => '<button type="button" data-jump="' + p + '"><span aria-hidden="true">' + (PAGE_ICON[p] || '') + '</span>' + esc(PAGE_SHORT[p] || G[p][0]) + '</button>').join('') + '</nav>'
                    + pages.map((p) => '<div class="fq-sec" data-page="' + p + '"><div class="fq-sec-h"><span aria-hidden="true">' + (PAGE_ICON[p] || '•') + '</span><b>' + esc(G[p][0]) + '</b><em></em></div>'
                        + qsFor(st.crop, p).map(([k, q]) => qHtml(k, q, v)).join('') + '</div>').join('') + '</div>';
                if (pages.includes('problems')) troublesFor().then(fillThumbs);
                return;
            }
            if (st.keys) {
                // Only the asked questions: no lot banner, no page title, no saved answers row.
                const list = qsFor(st.crop, st.page).filter(([k]) => st.keys.includes(k));
                el.innerHTML = h + list.map(([k, q]) => qHtml(k, q, v)).join('') + '</div>';
                if (list.some(([k]) => k === 'outbreaks')) troublesFor().then(fillThumbs);
                return;
            }
            h += '<div class="fq-lot" style="--lc:' + color(st.lot) + '"><i></i><b>' + esc(many ? 'Lot ' + (st.lot + 1) : l.name) + '</b><span>' + esc(many && l.name !== 'Lot ' + (st.lot + 1) ? l.name : '') + '</span>'
                + '<span class="fq-dots" aria-label="Page ' + (pi + 1) + ' of ' + pages.length + '">' + pages.map((p, j) => '<u class="' + (j === pi ? 'is-on' : (j < pi ? 'is-done' : '')) + '"></u>').join('') + '</span></div>';
            if (l.copiedFrom !== null && st.lots[l.copiedFrom]) {
                const src = 'Lot ' + (l.copiedFrom + 1) + (st.lots[l.copiedFrom].name !== 'Lot ' + (l.copiedFrom + 1) ? ' (' + st.lots[l.copiedFrom].name + ')' : '');
                h += '<div class="fq-copied' + (l.edited ? ' is-edited' : '') + '"><i>' + CHECK + '</i><div><b>' + (l.edited ? 'Started from ' + esc(src) + "'s answers" : 'Same as ' + esc(src)) + '</b><small>' + (l.edited ? 'Changed here. The other answers are still the copied ones.' : 'Every answer was copied. Change anything that differs.') + '</small></div>'
                    + (st.onSkip ? '<button type="button" class="fq-btn is-soft" data-skip>' + (st.lot < st.lots.length - 1 ? 'Next lot' : 'Skip ahead') + ' ›</button>' : '') + '</div>';
            } else if (l.from) {
                h += '<div class="fq-copied' + (l.edited ? ' is-edited' : '') + '"><i>' + CHECK + '</i><div><b>From your saved answers: ' + esc(l.from) + '</b><small>' + (l.edited ? 'Changed here.' : 'Change anything that differs this season.') + '</small></div></div>';
            } else if (first && many && st.lot > 0) {
                const others = st.lots.map((x, j) => [x, j]).filter(([x, j]) => j !== st.lot && count(x.value) > 0);
                if (others.length) {
                    const one = others.length === 1 && st.lots.length === 2;
                    const label = one ? 'Lot ' + (others[0][1] + 1) : 'another lot';
                    h += '<div class="fq-same"><span>' + COPY + '</span><div><b>Same as ' + esc(label) + '?</b><small>Copy every answer, then change only what differs.</small></div><button type="button" class="fq-btn" data-same="' + (one ? others[0][1] : '') + '">' + (one ? 'Copy' : 'Pick a lot') + '</button></div>';
                }
            }
            if (first && st.saved && l.copiedFrom === null && !l.from) h += '<div data-savedrow></div>';
            h += '<p class="fq-t">' + esc(G[st.page][0]) + '</p><p class="fq-s">' + esc(G[st.page][1]) + '</p>';
            h += qsFor(st.crop, st.page).map(([k, q]) => qHtml(k, q, v)).join('') + '</div>';
            el.innerHTML = h;
            if (st.page === 'problems') { troublesFor().then(fillThumbs); }
            if (first && st.saved && l.copiedFrom === null && !l.from) {
                const rows = await savedList();
                const slot = el.querySelector('[data-savedrow]');
                if (slot && rows.length) slot.outerHTML = '<button type="button" class="fq-saved" data-savedopen><span>' + BOOK + '</span><div><b>Use answers you gave before</b><small>' + rows.length + (rows.length === 1 ? ' field' : ' fields') + ' saved, like ' + esc(rows[0].name) + '</small></div><em>Pick ›</em></button>';
                else if (slot) slot.remove();
            }
        }
        function repaintQ(k) {
            const sec = el.querySelector('.fq-q[data-k="' + k + '"]');
            if (!sec) return;
            const q = Q[k], v = lotOf(st.lot).value;
            if (k === 'outbreaks') {
                const box = document.createElement('div');
                box.innerHTML = troubleHtml(v);
                sec.querySelector('.fq-tchips').replaceWith(box.querySelector('.fq-tchips'));
                sec.querySelector('.fq-tacts').replaceWith(box.querySelector('.fq-tacts'));
                fillThumbs();
            } else {
                sec.querySelectorAll('.fq-o').forEach((b) => { const on = q.multi ? (v[k] || []).includes(b.dataset.v) : v[k] === b.dataset.v; b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', String(on)); });
            }
            sec.classList.toggle('is-done', k === 'outbreaks' ? (v.outbreaks || []).length > 0 : (q.multi ? (v[k] || []).length > 0 : v[k] !== undefined));
        }
        function pick(k, x) {
            const q = Q[k], v = lotOf(st.lot).value;
            if (q.multi) {
                let a = [].concat(v[k] == null ? [] : v[k]);
                if (a.includes(x)) a = a.filter((y) => y !== x);
                else a = q.none && x === q.none ? [x] : a.filter((y) => y !== q.none).concat(x);
                if (a.length) v[k] = a; else delete v[k];
            } else if (v[k] === x) delete v[k]; else v[k] = x;
            repaintQ(k);
            changed(st.lot);
            if (lotOf(st.lot).copiedFrom !== null || lotOf(st.lot).from) refreshBanner();
        }
        function refreshBanner() {
            const b = el.querySelector('.fq-copied');
            if (b && !b.classList.contains('is-edited')) {
                b.classList.add('is-edited');
                const t = b.querySelector('b'), s = b.querySelector('small'), l = lotOf(st.lot);
                if (l.copiedFrom !== null) { t.textContent = t.textContent.replace(/^Same as (.*)$/, "Started from $1's answers"); s.textContent = 'Changed here. The other answers are still the copied ones.'; } else s.textContent = 'Changed here.';
            }
        }
        function toggleTrouble(i, key) {
            const v = lotOf(i).value;
            let a = [].concat(v.outbreaks || []).filter((x) => x !== 'none');
            a = a.includes(key) ? a.filter((x) => x !== key) : a.concat(key);
            if (a.length) v.outbreaks = a; else delete v.outbreaks;
            if (i === st.lot) repaintQ('outbreaks');
            changed(i);
        }
        function copy(i, from) {
            const l = lotOf(i);
            l.value = clone(lotOf(from).value); l.copiedFrom = from; l.edited = false; l.from = null;
            changed(i);
            l.edited = false;
            if (st.onChange) st.onChange(i, clone(l.value), Object.assign({}, l, { value: undefined }));
            show(i, st.page);
            window.toast?.('Lot ' + (i + 1) + ' now has the answers of Lot ' + (from + 1) + '.');
        }
        el.addEventListener('click', async (e) => {
            const jb = e.target.closest('[data-jump]');
            if (jb) { const sec = el.querySelector('.fq-sec[data-page="' + jb.dataset.jump + '"]'); if (sec) sec.scrollIntoView({ block: 'start', behavior: reduce() ? 'auto' : 'smooth' }); return; }
            const tb = e.target.closest('[data-st-add], [data-st-edit]');
            if (tb) {
                const i = st.lot, v = lotOf(i).value;
                if (!(await editTestIn(v, tb.dataset.stEdit || null))) return;
                const box = el.querySelector('[data-tests]');
                if (box && i === st.lot) box.outerHTML = testsHtml(v);
                changed(i);
                if (lotOf(i).copiedFrom !== null || lotOf(i).from) refreshBanner();
                return;
            }
            if (e.target.closest('[data-wcat]')) { openWeeds(); return; }
            const o2 = e.target.closest('.fq-o');
            if (o2) { pick(o2.closest('.fq-q').dataset.k, o2.dataset.v); return; }
            if (e.target.closest('[data-topen]')) {
                // Every crop's troubles open; the crop filter starts on Every crop.
                TS = { form: api, lot: st.lot, rows: [], crop: null };
                $('fqTroubleQ').value = '';
                paintCropBtn();
                paintTroubles();
                sheetOpen($('fqTroubleSheet'), $('fqTroubleQ'));
                TS.rows = await troublesFor();
                paintTroubles();
                await once($('fqTroubleSheet'));
                repaintQ('outbreaks');
                return;
            }
            const tx = e.target.closest('[data-tx]');
            if (tx) { const chip = tx.closest('.fq-tc'); if (!reduce()) { chip.style.transition = 'opacity .2s, transform .2s'; chip.style.opacity = '0'; chip.style.transform = 'scale(.9)'; } setTimeout(() => toggleTrouble(st.lot, tx.dataset.tx), reduce() ? 0 : 180); return; }
            if (e.target.closest('[data-tnone]')) {
                const v = lotOf(st.lot).value;
                if ((v.outbreaks || []).includes('none')) delete v.outbreaks; else v.outbreaks = ['none'];
                repaintQ('outbreaks'); changed(st.lot); return;
            }
            const same = e.target.closest('[data-same]');
            if (same) {
                if (same.dataset.same !== '') { copy(st.lot, Number(same.dataset.same)); return; }
                const me = st.lot;
                $('fqCopyList').innerHTML = st.lots.map((x, j) => j === me ? '' : '<button type="button" class="fq-row" data-copy="' + j + '"' + (count(x.value) ? '' : ' disabled') + '><span class="fq-ri" style="background:' + color(j) + ';color:#fff;font-weight:800;font-size:.9rem">' + (j + 1) + '</span><span class="fq-rt"><b>Lot ' + (j + 1) + (x.name !== 'Lot ' + (j + 1) ? ', ' + esc(x.name) : '') + '</b><small>' + (count(x.value) ? count(x.value) + ' answers · ' + esc(words(x.value)).slice(0, 90) : 'No answers yet') + '</small></span></button>').join('');
                sheetOpen($('fqCopySheet'));
                $('fqCopyList').onclick = (ev) => { const b = ev.target.closest('[data-copy]'); if (!b || b.disabled) return; sheetClose($('fqCopySheet')); copy(me, Number(b.dataset.copy)); };
                return;
            }
            if (e.target.closest('[data-skip]')) { if (st.onSkip) st.onSkip(st.lot); return; }
            if (e.target.closest('[data-savedopen]')) {
                const rows = await savedList();
                const me = st.lot;
                $('fqSavedList').innerHTML = rows.length ? rows.map((r) => '<button type="button" class="fq-row" data-saved="' + r.id + '"><span class="fq-ri">🌾</span><span class="fq-rt"><b>' + esc(r.name) + '</b><small>' + esc([r.cropLabel, r.count + ' answers', 'saved ' + r.updated].filter(Boolean).join(' · ')) + '</small></span></button>').join('') : '<p class="fq-nothing">Nothing saved yet.</p>';
                sheetOpen($('fqSavedSheet'));
                $('fqSavedList').onclick = (ev) => {
                    const b = ev.target.closest('[data-saved]');
                    if (!b) return;
                    const r = rows.find((x) => String(x.id) === b.dataset.saved);
                    sheetClose($('fqSavedSheet'));
                    if (!r) return;
                    const l = lotOf(me);
                    const keep = Object.fromEntries(Object.entries(r.answers || {}).filter(([k]) => Q[k] || k === 'phFrom' || k === 'phTo' || k === 'tests'));
                    if (!isRice(st.crop)) { delete keep.level; ['weeds', 'stubble'].forEach((k) => { if (keep[k]) keep[k] = [].concat(keep[k]).filter((x) => !(Q[k].o[x] && Q[k].o[x][2] === 'rice')); }); }
                    l.value = shape(keep); l.from = r.name; l.copiedFrom = null; l.edited = false;
                    if (st.onChange) st.onChange(me, clone(l.value), Object.assign({}, l, { value: undefined }));
                    show(me, st.page);
                };
            }
        });
        const api = {
            el, pages: () => pagesFor(st.crop), show, value: (i) => clone(lotOf(i ?? st.lot).value), values: () => st.lots.map((l) => clone(l.value)),
            lot: (i) => lotOf(i ?? st.lot), count: (i) => count(lotOf(i ?? st.lot).value), toggleTrouble,
            setCrop: (crop) => { st.crop = crop || null; },
            setLots: (lots) => setLots(lots),
            current: () => ({ lot: st.lot, page: st.page }),
        };
        return api;
    }

    /* ---- the whole profile in a sheet ---- */
    function open(o) {
        o = o || {};
        return new Promise((resolve) => {
            const dlg = $('fqOpenSheet'), body = $('fqOpenBody');
            const f = mount(body, { crop: o.crop, value: o.value || {}, name: o.title || 'Your field', saved: o.saved !== false });
            const pages = f.pages();
            let i = 0, result = null;
            $('fqOpenTitle').firstChild.textContent = o.title || 'Your field';
            const paint = (back) => {
                f.show(0, pages[i], back);
                body.scrollTop = 0;
                $('fqOpenStep').textContent = 'Page ' + (i + 1) + ' of ' + pages.length + ', ' + G[pages[i]][0].toLowerCase();
                $('fqOpenBar').style.width = ((i + 1) / pages.length * 100) + '%';
                $('fqOpenBack').disabled = i === 0;
                $('fqOpenNext').textContent = i === pages.length - 1 ? 'Done' : 'Next';
            };
            $('fqOpenBack').onclick = () => { if (i > 0) { i--; paint(true); } };
            $('fqOpenNext').onclick = () => { if (i < pages.length - 1) { i++; paint(); } else { result = f.value(0); sheetClose(dlg); } };
            once(dlg).then(() => resolve(result));
            paint();
            sheetOpen(dlg);
        });
    }

    /* ---- one question (or a few, or one page) in a small sheet: change it, save it ----
       The answers come back whole, with the change in them, or null when it was closed. */
    function ask(o) {
        o = o || {};
        return new Promise((resolve) => {
            const dlg = $('fqAskSheet'), body = $('fqAskBody');
            const keys = (Array.isArray(o.keys) ? o.keys : []).filter((k) => Q[k]);
            // A rice only question keeps its place when the crop is not rice: it is still the lot's answer.
            const crop = keys.some((k) => Q[k].only === 'rice') && !isRice(o.crop) ? null : (o.crop || null);
            const page = o.page && G[o.page] ? o.page : (keys.length ? Q[keys[0]].page : pagesFor(crop)[0]);
            const all = !!o.all;
            const only = keys.length ? keys : qsFor(crop, page).map(([k]) => k);
            // The one form is tall and keeps many changes: a tap outside does not close it.
            dlg.classList.toggle('is-full', all);
            dlg.classList.toggle('is-ask', !all);
            dlg.toggleAttribute('data-keep', all);
            // Each page's title says how many of its questions are answered, as they are.
            const counts = () => body.querySelectorAll('.fq-sec').forEach((sec) => {
                const n = sec.querySelectorAll('.fq-q').length, d = sec.querySelectorAll('.fq-q.is-done').length, em = sec.querySelector('.fq-sec-h em');
                if (em) { em.textContent = d + ' of ' + n; em.classList.toggle('is-done', d === n); }
            });
            // A fresh host each time: a mount listens on its element, so reusing one would stack listeners.
            body.innerHTML = '<div></div>';
            const f = mount(body.firstElementChild, { crop, value: o.value || {}, name: o.title || 'Your lot', saved: false, keys: all ? null : only, all, onChange: all ? counts : null });
            const q1 = keys.length === 1 ? Q[keys[0]] : null;
            $('fqAskTitle').firstChild.textContent = o.title || (all ? 'Your lot' : (q1 ? q1.short : G[page][0]));
            $('fqAskSub').textContent = o.sub || (all ? 'Change any answer, then save' : G[page][0]);
            $('fqAskSave').textContent = all ? 'Save the answers' : 'Save the answer';
            let result = null;
            $('fqAskSave').onclick = () => { result = f.value(0); sheetClose(dlg); };
            once(dlg).then(() => resolve(result));
            f.show(0, page);
            if (all) counts();
            sheetOpen(dlg);
            body.scrollTop = 0;
        });
    }

    async function save(p) {
        try { const r = await window.api(D.urls.save, { method: 'POST', body: p }); SAVED = null; return (r.data && r.data.row) || null; } catch (_) { return null; }
    }

    window.aneeFieldProfile = { mount, open, ask, save, groups: G, questions: Q, count, words, pagesFor, tests: TESTS, shape };
})();
</script>
@endpush
@endonce
