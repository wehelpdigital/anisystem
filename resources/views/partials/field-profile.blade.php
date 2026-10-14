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
       aneeFieldProfile.groups   page key -> [title, line]
       aneeFieldProfile.count(value)  aneeFieldProfile.words(value)  (a short summary)
       aneeFieldProfile.save({ name, crop, answers, lotId, scheduleId }) -> Promise<row or null>
     The answers are FieldProfile's flat map; the server's FieldProfile::normalize is the judge. --}}
@once
@push('head')
<style>
    .fq { --fq-e: cubic-bezier(.22,1,.36,1); --fq-g: #4a7c2a; --fq-gd: #2d5016; --fq-gl: #e4efd4; --fq-gx: #f4f9ee; container-type: inline-size; min-width: 0; }
    .fq-page { min-width: 0; }
    .fq-page.is-in { animation: fqIn .34s var(--fq-e) both; }
    .fq-page.is-back { animation-name: fqBack; }
    @keyframes fqIn { from { opacity: 0; transform: translateX(22px); } }
    @keyframes fqBack { from { opacity: 0; transform: translateX(-22px); } }
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
        <div class="fq-head"><h2 id="fqTroubleTitle">What troubled the last crop?<small>Tick everything that hurt it</small></h2><button type="button" class="fq-x" data-fq-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button></div>
        <label class="fq-find"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path stroke-linecap="round" d="M20 20l-4.2-4.2"/></svg><span class="sr-only">Search</span><input type="search" id="fqTroubleQ" placeholder="Search, like tungro or rats" autocomplete="off" maxlength="40"></label>
        <div class="fq-list" id="fqTroubleList"></div>
        <div class="fq-foot"><span id="fqTroubleCount">None ticked</span><button type="button" class="btn btn-primary" data-fq-close>Done</button></div>
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
@endpush

@push('scripts')
@php
    $fqData = ['bank' => \App\Support\FieldProfile::forPayload(null), 'other' => \App\Support\FieldProfile::OTHER,
        'urls' => ['troubles' => route('fieldprofile.troubles'), 'list' => route('fieldprofile.list'), 'save' => route('fieldprofile.save')]];
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

    /* Tagalog help flags (data-tl, see partials/tagalog-flags): the technical
       questions by key, and the technical answers by question.answer, with a
       1 when the term sits in the answer's small line. */
    const TLQ = { texture: 'soil-texture', organic: 'organic-matter', water: 'water-source', drainage: 'drainage', flooding: 'flood-prone',
        salt: 'saline', acidity: 'acidic', lastLimed: 'lime', hardpan: 'hardpan' };
    const TLO = { 'texture.sandy': ['sandy', 1], 'texture.loam': ['loam', 1], 'texture.clay': ['clay', 1], 'water.irrigation': ['irrigation'], 'water.rainfed': ['rainfed'],
        'salt.sodic': ['sodic', 1], 'acidity.sulfate': ['acid-sulfate'], 'acidity.alkaline': ['alkaline'], 'stubble.ratoon': ['ratoon'], 'weeds.sedges': ['weed-sedge'] };
    const tlAt = (key) => (key ? ' data-tl="' + esc(key) + '"' : '');
    const tlFlag = (key) => (window.aneeTl ? window.aneeTl.flag(key) : '');

    /* ---- the bank, for a crop ---- */
    const qsFor = (crop, page) => Object.entries(Q).filter(([, q]) => q.page === page && !(crop && q.only === 'rice' && !isRice(crop)))
        .map(([k, q]) => [k, Object.assign({}, q, { o: Object.fromEntries(Object.entries(q.o || {}).filter(([, o]) => !(crop && o[2] === 'rice' && !isRice(crop)))) })]);
    const pagesFor = (crop) => Object.keys(G).filter((p) => qsFor(crop, p).length);
    const count = (v) => Object.keys(v || {}).filter((k) => Q[k] && v[k] !== undefined && !(Array.isArray(v[k]) && !v[k].length)).length + (v && v.phFrom != null && !v.acidity ? 1 : 0);
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

    /* ---- the data the sheets need ---- */
    const TROUBLES = {};
    async function troublesFor(crop) {
        const key = crop || 'rice';
        if (!TROUBLES[key]) {
            TROUBLES[key] = window.api(D.urls.troubles + '?crop=' + encodeURIComponent(key)).then((r) => { const rows = (r.data && r.data.rows) || []; rows.forEach((x) => { TNAMES[x.k] = x.n; }); return rows; })
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
            dlg.addEventListener('click', (e) => { if (e.target === dlg || e.target.closest('[data-fq-close]')) sheetClose(dlg); });
            dlg.addEventListener('cancel', (e) => { e.preventDefault(); sheetClose(dlg); });
        });
    });
    const once = (dlg) => new Promise((res) => dlg.addEventListener('fqclosed', res, { once: true }));

    /* The troubles sheet works on one lot of one form at a time. */
    let TS = null;   // { form, lot, rows }
    function paintTroubles() {
        if (!TS) return;
        const v = TS.form.lot(TS.lot).value;
        const on = new Set((v.outbreaks || []).filter((x) => x !== 'none'));
        const q = $('fqTroubleQ').value.trim().toLowerCase();
        const hit = (r) => !q || (r.n + ' ' + (r.l || '')).toLowerCase().includes(q);
        const row = (r) => '<button type="button" class="fq-row" role="checkbox" aria-checked="' + on.has(r.k) + '" data-t="' + esc(r.k) + '"><span class="fq-ri">'
            + (r.img ? '<img src="' + esc(r.img) + '" alt="" loading="lazy">' : esc(r.icon || '•')) + '</span><span class="fq-rt"><b>' + esc(r.n) + '</b>' + (r.l ? '<small>' + esc(r.l) + '</small>' : '') + '</span><span class="fq-ck">' + CHECK + '</span></button>';
        const parts = [['other', 'Other troubles'], ['pest', 'Pests'], ['disease', 'Diseases']].map(([g, label]) => {
            const rows = TS.rows.filter((r) => r.g === g && hit(r));
            return rows.length ? '<b class="fq-gh">' + label + '</b>' + rows.map(row).join('') : '';
        }).join('');
        $('fqTroubleList').innerHTML = parts || '<p class="fq-nothing">' + (TS.rows.length ? 'Nothing by that name.' : 'Loading the list…') + '</p>';
        $('fqTroubleCount').textContent = on.size ? on.size + (on.size === 1 ? ' ticked' : ' ticked') : 'None ticked';
    }
    document.addEventListener('DOMContentLoaded', () => {
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

    /* ---- one form: a page of one lot at a time ---- */
    function mount(el, o) {
        o = o || {};
        const st = { crop: o.crop || null, saved: o.saved !== false, onChange: o.onChange, onSkip: o.onSkip, lot: 0, page: null, lots: [] };
        const setLots = (lots) => {
            st.lots = (lots && lots.length ? lots : [{ name: o.name || 'Your field', value: o.value || {} }]).map((l, i) => ({
                name: l.name || ('Lot ' + (i + 1)), value: clone(l.value), copiedFrom: l.copiedFrom ?? null, edited: !!l.edited, from: l.from || null,
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
            const rows = TROUBLES[st.crop || 'rice'];
            return '<div class="fq-tchips">' + picked.map((k) => '<span class="fq-tc"><span data-timg="' + esc(k) + '">•</span><em>' + esc(tname(k)) + '</em><button type="button" data-tx="' + esc(k) + '" aria-label="Remove ' + esc(tname(k)) + '">' + XS + '</button></span>').join('') + '</div>'
                + '<div class="fq-tacts"><button type="button" class="fq-add" data-topen>' + PLUS + (picked.length ? 'Add or change' : 'Choose the troubles') + '</button><button type="button" class="fq-none' + (none ? ' is-on' : '') + '" data-tnone aria-pressed="' + none + '">None last season</button></div>';
        }
        async function fillThumbs() {
            const rows = await troublesFor(st.crop);
            const by = Object.fromEntries(rows.map((r) => [r.k, r]));
            el.querySelectorAll('[data-timg]').forEach((s) => { const r = by[s.dataset.timg]; if (r) { s.innerHTML = r.img ? '<img src="' + esc(r.img) + '" alt="">' : esc(r.icon || '•'); const em = s.parentNode.querySelector('em'); if (em) em.textContent = r.n; } });
        }
        function qHtml(k, q, v) {
            const done = k === 'outbreaks' ? (v.outbreaks || []).length > 0 : (q.multi ? (v[k] || []).length > 0 : v[k] !== undefined);
            let h = '<section class="fq-q' + (done ? ' is-done' : '') + '" data-k="' + esc(k) + '"><h3><span' + tlAt(TLQ[k]) + '>' + esc(q.q) + '</span><i class="fq-tick" aria-hidden="true">' + CHECK + '</i></h3>'
                + '<p class="fq-why">' + BULB + '<span>' + esc(q.why) + '</span></p>'
                + (q.multi && k !== 'outbreaks' ? '<p class="fq-many">Pick all that fit</p>' : '');
            h += k === 'outbreaks' ? troubleHtml(v) : optHtml(k, q, v);
            if (q.ph) {
                h += '<div class="fq-ph"><label>Known pH' + tlFlag('ph') + ' from a soil test <small>(optional)</small></label><div class="fq-phr">'
                    + '<label>From<input type="text" inputmode="decimal" maxlength="4" data-ph="phFrom" value="' + esc(v.phFrom ?? '') + '" aria-label="pH from"></label><span>to</span>'
                    + '<label>To<input type="text" inputmode="decimal" maxlength="4" data-ph="phTo" value="' + esc(v.phTo ?? '') + '" aria-label="pH to"></label></div><p class="fq-err" data-pherr hidden>A pH is a number from 3 to 10.</p></div>';
            }
            return h + '</section>';
        }
        async function show(lot, page, back) {
            st.lot = Math.max(0, Math.min(st.lots.length - 1, lot || 0));
            const pages = pagesFor(st.crop);
            st.page = pages.includes(page) ? page : pages[0];
            const l = lotOf(st.lot), v = l.value, pi = pages.indexOf(st.page), first = pi === 0, many = st.lots.length > 1;
            let h = '<div class="fq-page' + (reduce() ? '' : ' is-in' + (back ? ' is-back' : '')) + '">';
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
            if (st.page === 'problems') { troublesFor(st.crop).then(fillThumbs); }
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
                let a = (v[k] || []).slice();
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
            let a = (v.outbreaks || []).filter((x) => x !== 'none');
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
            const o2 = e.target.closest('.fq-o');
            if (o2) { pick(o2.closest('.fq-q').dataset.k, o2.dataset.v); return; }
            if (e.target.closest('[data-topen]')) {
                TS = { form: api, lot: st.lot, rows: [] };
                $('fqTroubleQ').value = '';
                paintTroubles();
                sheetOpen($('fqTroubleSheet'), $('fqTroubleQ'));
                TS.rows = await troublesFor(st.crop);
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
                    const keep = Object.fromEntries(Object.entries(r.answers || {}).filter(([k]) => Q[k] || k === 'phFrom' || k === 'phTo'));
                    if (!isRice(st.crop)) { delete keep.level; ['weeds', 'stubble'].forEach((k) => { if (keep[k]) keep[k] = [].concat(keep[k]).filter((x) => !(Q[k].o[x] && Q[k].o[x][2] === 'rice')); }); }
                    l.value = keep; l.from = r.name; l.copiedFrom = null; l.edited = false;
                    if (st.onChange) st.onChange(me, clone(l.value), Object.assign({}, l, { value: undefined }));
                    show(me, st.page);
                };
            }
        });
        el.addEventListener('input', (e) => {
            const inp = e.target.closest('[data-ph]');
            if (!inp) return;
            const v = lotOf(st.lot).value, raw = inp.value.trim().replace(',', '.'), n = parseFloat(raw);
            const bad = raw !== '' && !(n >= 3 && n <= 10);
            inp.classList.toggle('is-bad', bad);
            const err = inp.closest('.fq-ph').querySelector('[data-pherr]');
            err.hidden = !el.querySelector('[data-ph].is-bad');
            if (raw === '' || bad) delete v[inp.dataset.ph]; else v[inp.dataset.ph] = Math.round(n * 10) / 10;
            changed(st.lot);
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

    async function save(p) {
        try { const r = await window.api(D.urls.save, { method: 'POST', body: p }); SAVED = null; return (r.data && r.data.row) || null; } catch (_) { return null; }
    }

    window.aneeFieldProfile = { mount, open, save, groups: G, questions: Q, count, words, pagesFor };
})();
</script>
@endpush
@endonce
