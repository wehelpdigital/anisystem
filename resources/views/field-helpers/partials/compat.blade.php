{{-- The Compatibility Checker (2026-10-18, the owner): what goes on together.
     The crop and how old it is, then what the farmer wants to apply (from
     the product book, their own products, or only a name), each with its
     rate, then the water, the weather and the sprayer; "Check the mix"
     opens the reading in a full screen sheet: the verdict, pair by pair,
     what to watch, how strong the spray is for the crop, the mixing order,
     the jar test with each product's amount in a liter, and how to split
     the work. What the book cannot read is said plainly, with Anee's online
     check and its price before it runs.

     Expects $book (App\Http\Controllers\CompatController::book()). Calls go
     to the compat.* routes. The mix on the screen is kept on this phone
     (localStorage anee.compat.mix) so a reload does not lose it. --}}
@php
    $crops = $book['crops'];
    $cxCrop = request('crop') && isset($crops[request('crop')]) ? request('crop') : $book['crop'];
    $counts = $book['counts'];
    $nSprays = array_sum(array_intersect_key($counts, array_flip(['insecticide', 'fungicide', 'herbicide', 'molluscicide', 'nematicide', 'acaricide', 'pgr'])));
    $nFerts = array_sum(array_intersect_key($counts, array_flip(['granular', 'soluble', 'foliar', 'amendment', 'biostimulant', 'biological'])));
    $nAll = array_sum($counts);
    $TL = fn ($k) => \App\Support\Tagalog::flag($k);
@endphp
@include('field-helpers.partials.kit')
<style>
    /* ---- The page: three boxes, two columns on a wide screen ----------- */
    .cx { display: grid; gap: 1rem; }
    @media (min-width: 1024px) { .cx { grid-template-columns: minmax(0, 23rem) minmax(0, 1fr); grid-template-areas: "field items" "more items"; align-items: start; gap: 1.1rem 1.6rem; }
        .cx-field { grid-area: field; } .cx-more { grid-area: more; } .cx-mix { grid-area: items; } }
    .cx-box { min-width: 0; display: grid; gap: 1rem; padding: 1.1rem; border-radius: 1.3rem; background: #fff; border: 1px solid #e1edd3; box-shadow: 0 20px 40px -34px rgb(20 33 12 / .6); }
    .cx-q > b { display: flex; align-items: center; gap: .55rem; font-family: var(--font-heading); font-weight: 800; color: #14210c; }
    .cx-q > b i { flex: none; font-style: normal; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center; font-size: .8rem; color: #fff; background: #4a7c2a; }
    .cx-q > small { display: block; margin: .15rem 0 0 2.15rem; font-size: .8rem; line-height: 1.45; color: #6b7280; }
    .cx-q .fh-otag-box { margin-top: .6rem; }
    .cx-q .cp-tag { margin-top: .6rem; }
    .cx-tags2 { display: grid; gap: .5rem; margin-top: .6rem; }
    .cx-tags2 .fh-otag-box { margin-top: 0; }
    /* The book's size, said under the heading. */
    .cx-lead { display: flex; flex-wrap: wrap; gap: .35rem; margin: -.2rem 0 .1rem 2.15rem; }
    .cx-lead span { padding: .14rem .55rem; border-radius: 999px; font-size: .68rem; font-weight: 800; color: #2d5016; background: #eef5e6; }

    /* ---- What will you apply: one card per product ----------------------- */
    .cx-items { display: grid; gap: .6rem; }
    .cx-empty { display: grid; place-items: center; gap: .35rem; padding: 1.4rem 1rem; border-radius: 1.1rem; text-align: center; color: #4b5563; background: #f8faf5; border: 1.5px dashed #d4e3c1; }
    .cx-empty b { font-family: var(--font-heading); font-size: 1rem; color: #14210c; }
    .cx-empty small { font-size: .82rem; line-height: 1.5; max-width: 26rem; }
    .cx-empty svg { width: 2.6rem; height: 2.6rem; color: #6b9f3d; }
    .cx-it { --c: #4a7c2a; --cb: #eef5e6; position: relative; min-width: 0; padding: .7rem .75rem .75rem; border-radius: 1.1rem; background: #f8faf5; border: 1px solid #e1e9d7; }
    .cx-it.is-in { animation: cxIn .34s cubic-bezier(.22,1,.36,1) both; }
    .cx-it.is-out { animation: cxOut .24s cubic-bezier(.22,1,.36,1) forwards; }
    @keyframes cxIn { from { opacity: 0; transform: translateY(8px) scale(.98); } }
    @keyframes cxOut { to { opacity: 0; transform: translateX(16px); } }
    .cx-it-top { display: flex; align-items: flex-start; gap: .6rem; }
    .cx-ico { flex: none; width: 2.4rem; height: 2.4rem; border-radius: .8rem; display: grid; place-items: center; color: var(--c); background: var(--cb); }
    .cx-ico svg { width: 1.3rem; height: 1.3rem; }
    .cx-it-n { flex: 1; min-width: 0; display: grid; gap: .1rem; padding: 0; text-align: left; background: none; border: 0; color: inherit; cursor: pointer; }
    .cx-it-n b { font-family: var(--font-heading); font-size: .98rem; font-weight: 800; line-height: 1.25; color: #14210c; overflow-wrap: anywhere; }
    .cx-it-n small { font-size: .74rem; line-height: 1.4; color: #6b7280; overflow-wrap: anywhere; }
    .cx-it-n:focus-visible, .cx-x:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; border-radius: .6rem; }
    .cx-x { flex: none; width: 2.25rem; height: 2.25rem; border-radius: 999px; display: grid; place-items: center; color: #6b7280; background: #fff; border: 1px solid #e3ead9; cursor: pointer;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .cx-x:hover { color: #b91c1c; background: #fdecec; }
    .cx-x svg { width: 1rem; height: 1rem; }
    .cx-chips { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .45rem; }
    .cx-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .12rem .5rem; border-radius: 999px; font-size: .66rem; font-weight: 800; line-height: 1.35; color: var(--c); background: var(--cb); }
    .cx-chip.is-warn { color: #7a4b00; background: #fdf0c8; }
    .cx-chip.is-bad { color: #9b1c1c; background: #fde8e8; }
    .cx-chip.is-good { color: #14532d; background: #dcfce7; }
    .cx-chip.is-grey { color: #475569; background: #eef2f7; }
    .cx-chip svg { width: .75rem; height: .75rem; }
    /* The rate row: an amount, its unit and what it is for. */
    .cx-rate { display: grid; grid-template-columns: minmax(0, 5.5rem) minmax(0, 5.2rem) minmax(0, 1fr); gap: .4rem; margin-top: .6rem; }
    .cx-rate input { width: 100%; min-height: 2.75rem; padding: .5rem .65rem; border-radius: .8rem; border: 1px solid #d7e3c8; background: #fff; font-size: 1rem; font-weight: 700; color: #14210c;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .cx-rate input:focus { outline: none; border-color: #86b556; box-shadow: 0 0 0 4px rgb(134 181 86 / .18); }
    .cx-pick { min-width: 0; min-height: 2.75rem; display: flex; align-items: center; justify-content: space-between; gap: .3rem; padding: .4rem .55rem .4rem .7rem; border-radius: .8rem;
        font-size: .84rem; font-weight: 800; text-align: left; color: #2d5016; background: #fff; border: 1px solid #d7e3c8; cursor: pointer;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
    .cx-pick span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cx-pick svg { flex: none; width: .8rem; height: .8rem; }
    .cx-pick:hover { border-color: #a8cc7e; background: #f7fbf2; }
    .cx-pick:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    .cx-how { display: inline-grid; grid-template-columns: 1fr 1fr; margin-top: .6rem; padding: .2rem; border-radius: 999px; background: #eef3e7; }
    .cx-how button { min-height: 2.2rem; padding: .25rem .8rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: #3d4b33; background: none; cursor: pointer;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .cx-how button[aria-pressed="true"] { color: #fff; background: #2d5016; }
    .cx-label { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem .6rem; margin-top: .5rem; font-size: .76rem; line-height: 1.45; color: #4b5563; }
    .cx-label b { color: #2d5016; }
    .cx-use { padding: .2rem .6rem; border-radius: 999px; font-size: .72rem; font-weight: 800; color: #2d5016; background: #e4efd4; cursor: pointer; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .cx-use:hover { background: #d7e8c2; }
    .cx-adds { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: .5rem; }
    .cx-add { display: flex; align-items: center; justify-content: center; gap: .45rem; min-height: 3rem; padding: .6rem .8rem; border-radius: .95rem; font-weight: 800; font-size: .92rem; cursor: pointer;
        color: #2d5016; background: #eef6e4; border: 1.5px solid #a8cc7e; transition: background-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .cx-add:hover { background: #e2f0d2; transform: translateY(-1px); }
    .cx-add.is-ghost { background: #fff; border-color: #dfe8d4; color: #3d4b33; }
    .cx-add svg { width: 1.05rem; height: 1.05rem; }
    .cx-add:focus-visible, .cx-go:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    /* The call to action: yellow (the owner's rule), with its nudging arrow. */
    .cx-go { display: flex; align-items: center; justify-content: center; gap: .55rem; width: 100%; min-height: 3.3rem; padding: .75rem 1rem; border-radius: 1rem; font-family: var(--font-heading);
        font-size: 1.05rem; font-weight: 800; color: #3b2f00; background: #f5c518; box-shadow: 0 14px 28px -18px rgb(140 105 0 / .9); cursor: pointer;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .cx-go:hover { background: #ffd23f; transform: translateY(-1px); }
    .cx-go svg { width: 1.15rem; height: 1.15rem; animation: cxNudge 1.6s cubic-bezier(.22,1,.36,1) infinite; }
    @keyframes cxNudge { 50% { transform: translateX(4px); } }
    .cx-go[disabled] { opacity: .55; cursor: not-allowed; transform: none; }
    .cx-go[disabled] svg { animation: none; }
    .cx-go.is-busy svg { animation: cxSpin .8s linear infinite; }
    @keyframes cxSpin { to { transform: rotate(360deg); } }
    .cx-fine { display: flex; gap: .5rem; font-size: .8rem; line-height: 1.55; color: #4b5563; }
    .cx-fine svg { flex: none; width: 1.05rem; height: 1.05rem; margin-top: .12rem; color: #c79e00; }

    /* ---- The add sheet: search the book ---------------------------------- */
    .cx-find { position: sticky; top: 0; z-index: 2; display: grid; gap: .6rem; padding: .8rem 1rem .7rem; background: rgb(244 246 241 / .96); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); }
    .cx-shelves { display: flex; gap: .35rem; overflow-x: auto; scrollbar-width: none; padding-bottom: .1rem; }
    .cx-shelves::-webkit-scrollbar { display: none; }
    .cx-shelves button { flex: none; min-height: 2.3rem; padding: .3rem .85rem; border-radius: 999px; font-size: .8rem; font-weight: 800; color: #3d4b33; background: #fff; border: 1px solid #dfe8d4; cursor: pointer;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .cx-shelves button[aria-pressed="true"] { color: #fff; background: #2d5016; border-color: #2d5016; }
    .cx-results { display: grid; gap: .45rem; padding: .2rem 1rem 1.5rem; }
    .cx-row { --c: #4a7c2a; --cb: #eef5e6; display: flex; align-items: center; gap: .65rem; width: 100%; min-width: 0; padding: .6rem .65rem; border-radius: 1rem; text-align: left; color: inherit;
        background: #fff; border: 1px solid #e3ead9; cursor: pointer; transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .cx-row:hover { border-color: #a8cc7e; transform: translateY(-1px); }
    .cx-row:focus-visible { outline: 3px solid #f5c518; outline-offset: 2px; }
    .cx-row.is-in { animation: cxIn .3s cubic-bezier(.22,1,.36,1) both; }
    .cx-row .cx-ico { width: 2.2rem; height: 2.2rem; }
    .cx-row-t { flex: 1; min-width: 0; display: grid; gap: .08rem; }
    .cx-row-t b { font-size: .92rem; font-weight: 800; line-height: 1.3; color: #14210c; overflow-wrap: anywhere; }
    .cx-row-t small { font-size: .72rem; line-height: 1.4; color: #6b7280; overflow-wrap: anywhere; }
    .cx-row-plus { flex: none; width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center; color: #3b2f00; background: #f5c518; }
    .cx-row-plus svg { width: .95rem; height: .95rem; }
    .cx-quick { display: flex; flex-wrap: wrap; gap: .4rem; }
    .cx-quick button { padding: .4rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 700; color: #2d3a24; background: #fff; border: 1px solid #e1ebd5; cursor: pointer; transition: border-color .28s cubic-bezier(.22,1,.36,1); }
    .cx-quick button:hover { border-color: #a8cc7e; }
    .cx-hint { padding: .9rem 1rem; border-radius: 1rem; font-size: .86rem; line-height: 1.55; color: #4b5563; background: #fff; border: 1px dashed #d4e3c1; }
    .cx-hint b { color: #14210c; }
    .cx-hint .cx-add { margin-top: .6rem; width: 100%; }
    .cx-sh { margin: .4rem .1rem .1rem; font-size: .64rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #6b7f5a; }

    /* ---- The own product form --------------------------------------------- */
    .cx-form { display: grid; gap: .9rem; padding: 1rem 1rem 1.5rem; max-width: 40rem; margin: 0 auto; }
    .cx-field2 { display: grid; gap: .3rem; }
    .cx-field2 > span { font-size: .74rem; font-weight: 800; letter-spacing: .04em; color: #3d6823; text-transform: uppercase; }
    .cx-field2 input, .cx-field2 textarea { width: 100%; min-height: 2.8rem; padding: .55rem .75rem; border-radius: .85rem; border: 1px solid #d7e3c8; background: #fff; font-size: 1rem; color: #14210c; }
    .cx-field2 textarea { min-height: 4.5rem; resize: vertical; }
    .cx-field2 input:focus, .cx-field2 textarea:focus { outline: none; border-color: #86b556; box-shadow: 0 0 0 4px rgb(134 181 86 / .18); }
    .cx-grid2 { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: .5rem; }
    .cx-airow { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 5rem) minmax(0, 4.6rem) auto; gap: .35rem; align-items: center; }
    .cx-airow input { min-height: 2.7rem; padding: .45rem .6rem; border-radius: .75rem; border: 1px solid #d7e3c8; background: #fff; font-size: .95rem; color: #14210c; width: 100%; }
    .cx-nut { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .4rem; }
    @media (min-width: 560px) { .cx-nut { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
    .cx-nut label { display: grid; gap: .15rem; font-size: .72rem; font-weight: 800; color: #3d6823; }
    .cx-nut input { min-height: 2.6rem; padding: .4rem .5rem; border-radius: .7rem; border: 1px solid #d7e3c8; background: #fff; font-size: .95rem; width: 100%; }
    .cx-link { justify-self: start; font-size: .84rem; font-weight: 800; color: #3d6823; text-decoration: underline; text-underline-offset: 3px; background: none; border: 0; cursor: pointer; }
    .cx-save { display: flex; gap: .5rem; }
    .cx-save button { flex: 1; min-height: 3rem; border-radius: .95rem; font-weight: 800; cursor: pointer; }
    .cx-save .is-main { color: #3b2f00; background: #f5c518; }
    .cx-save .is-del { flex: none; padding: 0 1rem; color: #9b1c1c; background: #fdecec; }

    /* ---- The reading ------------------------------------------------------ */
    .cx-res { display: grid; gap: .9rem; max-width: 52rem; margin: 0 auto; }
    .cx-res > * { animation: cxIn .38s cubic-bezier(.22,1,.36,1) both; }
    .cx-res > :nth-child(2) { animation-delay: .05s; } .cx-res > :nth-child(3) { animation-delay: .1s; } .cx-res > :nth-child(4) { animation-delay: .14s; } .cx-res > :nth-child(n+5) { animation-delay: .18s; }
    .cx-verdict { --v: #15803d; --vb: #e8f7ec; --vl: #bfe5c9; display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .85rem; align-items: center; padding: 1rem 1.05rem; border-radius: 1.3rem;
        background: var(--vb); border: 1.5px solid var(--vl); }
    .cx-verdict.is-care { --v: #b45309; --vb: #fff6df; --vl: #f5d98a; }
    .cx-verdict.is-no { --v: #b91c1c; --vb: #fdeeee; --vl: #f5c2c2; }
    .cx-verdict.is-unknown { --v: #475569; --vb: #eef2f7; --vl: #d3dbe6; }
    .cx-vico { width: 3.4rem; height: 3.4rem; border-radius: 1.1rem; display: grid; place-items: center; color: #fff; background: var(--v); box-shadow: 0 12px 22px -14px var(--v); }
    .cx-vico svg { width: 1.9rem; height: 1.9rem; }
    .cx-verdict h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.3rem; line-height: 1.2; color: var(--v); }
    .cx-verdict p { margin-top: .2rem; font-size: .9rem; line-height: 1.5; color: #374151; }
    .cx-verdict .cx-mixline { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: .35rem; }
    .cx-dot { display: inline-flex; align-items: center; gap: .35rem; padding: .25rem .6rem .25rem .4rem; border-radius: 999px; font-size: .76rem; font-weight: 800; color: #1f2937; background: rgb(255 255 255 / .8); border: 1px solid rgb(0 0 0 / .06); }
    .cx-dot i { width: .6rem; height: .6rem; border-radius: 999px; background: #16a34a; }
    .cx-dot i.is-care { background: #d97706; } .cx-dot i.is-no { background: #dc2626; } .cx-dot i.is-unknown { background: #94a3b8; }
    .cx-sec { padding: .9rem .95rem 1rem; border-radius: 1.15rem; background: #fff; border: 1px solid #e5ebdf; min-width: 0; }
    .cx-sec > h3 { display: flex; align-items: center; gap: .45rem; margin-bottom: .6rem; font-size: .72rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; }
    .cx-sec > h3 svg { width: 1.05rem; height: 1.05rem; }
    .cx-pairs { display: grid; gap: .55rem; }
    .cx-pair { --v: #15803d; --vb: #f2faf4; position: relative; padding: .7rem .8rem .75rem 1rem; border-radius: 1rem; background: var(--vb); border: 1px solid rgb(0 0 0 / .05); overflow: hidden; }
    .cx-pair::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: .3rem; background: var(--v); }
    .cx-pair.is-care { --v: #d97706; --vb: #fffaf0; } .cx-pair.is-no { --v: #dc2626; --vb: #fff5f5; } .cx-pair.is-unknown { --v: #94a3b8; --vb: #f6f8fb; }
    .cx-pair-h { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .35rem .6rem; }
    .cx-pair-h b { min-width: 0; font-size: .92rem; font-weight: 800; line-height: 1.35; color: #14210c; overflow-wrap: anywhere; }
    .cx-pair-h b em { font-style: normal; color: #6b7280; font-weight: 700; }
    .cx-lvl { flex: none; padding: .16rem .6rem; border-radius: 999px; font-size: .68rem; font-weight: 900; letter-spacing: .03em; color: #fff; background: var(--v); }
    .cx-why { margin-top: .4rem; font-size: .88rem; line-height: 1.55; color: #374151; }
    .cx-fix { margin-top: .35rem; font-size: .86rem; line-height: 1.5; color: #1f2937; }
    .cx-fix b { color: var(--v); }
    .cx-gap { display: inline-flex; align-items: center; gap: .3rem; margin-top: .45rem; padding: .18rem .6rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: #7c2d12; background: #ffedd5; }
    .cx-gap svg { width: .85rem; height: .85rem; }
    .cx-anee { display: inline-flex; align-items: center; gap: .3rem; margin-left: .3rem; padding: .08rem .45rem; border-radius: 999px; font-size: .62rem; font-weight: 900; color: #3b2f00; background: #fdeeb2; vertical-align: 1px; }
    .cx-fine-ok { display: flex; flex-wrap: wrap; gap: .35rem; }
    .cx-fine-ok span { padding: .3rem .65rem; border-radius: 999px; font-size: .78rem; font-weight: 700; color: #14532d; background: #ecf8ef; border: 1px solid #c9ead3; }
    .cx-notes { display: grid; gap: .5rem; }
    .cx-note { --v: #d97706; display: grid; grid-template-columns: auto minmax(0, 1fr); gap: .55rem; padding: .65rem .75rem; border-radius: .95rem; background: #fffaf0; border: 1px solid #f6e2b4; }
    .cx-note.is-no { --v: #dc2626; background: #fff5f5; border-color: #f5c9c9; }
    .cx-note > svg { width: 1.1rem; height: 1.1rem; margin-top: .1rem; color: var(--v); }
    .cx-note p { font-size: .88rem; line-height: 1.5; color: #374151; }
    .cx-note p + p { margin-top: .25rem; color: #1f2937; }
    .cx-tips { display: grid; gap: .4rem; }
    .cx-tips li { position: relative; padding-left: 1.1rem; font-size: .86rem; line-height: 1.5; color: #374151; }
    .cx-tips li::before { content: ''; position: absolute; left: .2rem; top: .55rem; width: .4rem; height: .4rem; border-radius: 999px; background: #6b9f3d; }
    /* The strength meter: where the spray sits against the crop's limit. */
    .cx-meter { position: relative; height: .9rem; margin: .4rem 0 .5rem; border-radius: 999px; background: linear-gradient(90deg, #bbf7d0 0%, #bbf7d0 60%, #fde68a 60%, #fde68a 80%, #fecaca 80%); overflow: hidden; }
    .cx-meter i { position: absolute; top: -.15rem; bottom: -.15rem; width: .3rem; border-radius: 999px; background: #14210c; box-shadow: 0 0 0 2px #fff; transition: left .6s cubic-bezier(.22,1,.36,1); }
    .cx-meter-l { display: flex; justify-content: space-between; font-size: .72rem; color: #6b7280; }
    .cx-strong { font-size: .9rem; line-height: 1.55; color: #1f2937; }
    .cx-strong b { font-family: var(--font-heading); font-size: 1.1rem; }
    .cx-steps { display: grid; gap: .45rem; counter-reset: cx; }
    .cx-steps li { position: relative; padding-left: 2.1rem; font-size: .92rem; line-height: 1.55; color: #374151; counter-increment: cx; }
    .cx-steps li::before { content: counter(cx); position: absolute; left: 0; top: .05rem; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: grid; place-items: center; font-size: .76rem; font-weight: 800; color: #fff; background: #4a7c2a; }
    .cx-steps li b { color: #14210c; }
    .cx-jar { display: grid; gap: .35rem; margin-top: .6rem; }
    .cx-jar div { display: flex; align-items: baseline; justify-content: space-between; gap: .6rem; padding: .45rem .65rem; border-radius: .8rem; background: #f6f9f2; font-size: .88rem; }
    .cx-jar div b { min-width: 0; overflow-wrap: anywhere; }
    .cx-jar div span { flex: none; font-weight: 800; color: #2d5016; }
    .cx-trips { display: grid; gap: .5rem; }
    @media (min-width: 640px) { .cx-trips { grid-template-columns: repeat(auto-fill, minmax(14rem, 1fr)); } }
    .cx-trip { padding: .7rem .8rem; border-radius: 1rem; background: #f6f9f2; border: 1px solid #e1ebd5; }
    .cx-trip b { display: block; font-size: .7rem; font-weight: 900; letter-spacing: .07em; text-transform: uppercase; color: #3d6823; }
    .cx-trip ul { margin-top: .35rem; display: grid; gap: .2rem; }
    .cx-trip li { font-size: .88rem; font-weight: 700; color: #14210c; }
    .cx-phi { display: grid; gap: .35rem; }
    .cx-phi div { display: flex; justify-content: space-between; gap: .6rem; font-size: .88rem; padding: .4rem 0; border-bottom: 1px solid #eef2e8; }
    .cx-phi div:last-child { border-bottom: 0; }
    .cx-phi span { flex: none; font-weight: 800; color: #7c2d12; }
    /* Not in the book: what Anee can look up, her price first. */
    .cx-ask { display: grid; gap: .7rem; padding: 1rem; border-radius: 1.2rem; background: linear-gradient(135deg, #fffdf2, #fff7d6); border: 1.5px solid #f5d98a; }
    .cx-ask-h { display: flex; align-items: center; gap: .7rem; }
    .cx-ask-h img { flex: none; width: 3rem; height: 3rem; border-radius: 1rem; object-fit: cover; background: #fff; }
    .cx-ask-h b { display: block; font-family: var(--font-heading); font-size: 1.02rem; color: #3b2f00; }
    .cx-ask-h small { display: block; font-size: .8rem; line-height: 1.45; color: #6b4a00; }
    .cx-ask ul { display: flex; flex-wrap: wrap; gap: .35rem; }
    .cx-ask li { padding: .25rem .6rem; border-radius: 999px; font-size: .78rem; font-weight: 800; color: #6b4a00; background: #fff; border: 1px solid #f5d98a; }
    .cx-ask .cx-go { min-height: 3rem; font-size: .98rem; }
    .cx-ask .cx-fine { font-size: .76rem; color: #6b4a00; }
    .cx-foot { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: .5rem; }
    .cx-foot button { min-height: 3rem; border-radius: .95rem; font-weight: 800; cursor: pointer; color: #2d5016; background: #eef6e4; border: 1px solid #c9e0ad; }
    .cx-foot button:hover { background: #e2f0d2; }

    /* The product's sheet. */
    .cx-info { display: grid; gap: .8rem; padding: 1rem 1rem 2rem; max-width: 46rem; margin: 0 auto; }
    .cx-info h2 { font-family: var(--font-heading); font-weight: 800; font-size: 1.4rem; line-height: 1.2; color: #14210c; }
    .cx-uses { display: grid; gap: .4rem; }
    .cx-uses div { padding: .5rem .65rem; border-radius: .8rem; background: #f6f9f2; font-size: .84rem; line-height: 1.45; color: #374151; }
    .cx-uses div b { color: #14210c; }
    .cx-uses div.is-on { background: #eaf5dc; box-shadow: inset 0 0 0 1.5px #a8cc7e; }
    .cx-uses small { display: block; margin-top: .1rem; color: #6b7280; }
    .cx-ais { display: grid; gap: .35rem; }
    .cx-ais div { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .3rem .6rem; padding: .5rem .65rem; border-radius: .8rem; background: #f6f9f2; font-size: .88rem; }
    .cx-ais b { color: #14210c; }
    .cx-ais small { color: #6b7280; }

    @media (max-width: 379.98px) { .cx-rate { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } .cx-rate .cx-pick.is-per { grid-column: 1 / -1; } .cx-adds { grid-template-columns: minmax(0, 1fr); } }
    @media (prefers-reduced-motion: reduce) {
        .cx-it.is-in, .cx-it.is-out, .cx-row.is-in, .cx-res > *, .cx-go svg { animation: none !important; }
        .cx-x, .cx-rate input, .cx-pick, .cx-how button, .cx-use, .cx-add, .cx-go, .cx-shelves button, .cx-row, .cx-meter i, .cx-quick button { transition: none; }
    }
    html.sm-still .cx-go svg { animation: none; }

    /* ---- Night ------------------------------------------------------------- */
    html.dark .cx-box, html.dark .cx-sec, html.dark .cx-row, html.dark .cx-hint, html.dark .cx-quick button, html.dark .cx-shelves button, html.dark .cx-x { background: #151b12; border-color: #2b3a1c; box-shadow: none; }
    html.dark .cx-q > b, html.dark .cx-it-n b, html.dark .cx-row-t b, html.dark .cx-pair-h b, html.dark .cx-empty b, html.dark .cx-info h2, html.dark .cx-trip li, html.dark .cx-steps li b, html.dark .cx-jar div b,
    html.dark .cx-uses div b, html.dark .cx-ais b, html.dark .cx-hint b { color: #e8efe1; }
    html.dark .cx-q > small, html.dark .cx-it-n small, html.dark .cx-row-t small, html.dark .cx-label, html.dark .cx-fine, html.dark .cx-uses small, html.dark .cx-ais small { color: #b9caa8; }
    html.dark .cx-it, html.dark .cx-empty { background: #1a2414; border-color: #2b3a1c; color: #c9d8b8; }
    html.dark .cx-rate input, html.dark .cx-pick, html.dark .cx-field2 input, html.dark .cx-field2 textarea, html.dark .cx-airow input, html.dark .cx-nut input { background: #0f150c; border-color: #2b3a1c; color: #e8efe1; }
    html.dark .cx-pick { color: #cfe3b8; }
    html.dark .cx-how { background: #1f2a17; }
    html.dark .cx-how button { color: #c9d8b8; }
    html.dark .cx-how button[aria-pressed="true"] { background: #4a7c2a; color: #fff; }
    html.dark .cx-add { background: #1f2a17; border-color: #4a7c2a; color: #d5e3c5; }
    html.dark .cx-add.is-ghost { background: #151b12; border-color: #2b3a1c; }
    html.dark .cx-find { background: rgb(13 17 10 / .95); }
    html.dark .cx-shelves button { color: #c9d8b8; }
    html.dark .cx-shelves button[aria-pressed="true"] { background: #4a7c2a; border-color: #4a7c2a; color: #fff; }
    html.dark .cx-lead span, html.dark .cx-use { background: #22301a; color: #cfe3b8; }
    html.dark .cx-why, html.dark .cx-note p, html.dark .cx-tips li, html.dark .cx-steps li, html.dark .cx-strong, html.dark .cx-verdict p, html.dark .cx-uses div, html.dark .cx-hint { color: #d5e3c5; }
    html.dark .cx-fix, html.dark .cx-note p + p { color: #e8efe1; }
    html.dark .cx-verdict { --vb: #13261a; --vl: #24502f; }
    html.dark .cx-verdict.is-care { --vb: #2a2210; --vl: #5c4a24; }
    html.dark .cx-verdict.is-no { --vb: #2c1414; --vl: #5c2a2a; }
    html.dark .cx-verdict.is-unknown { --vb: #1a2029; --vl: #334155; }
    html.dark .cx-verdict h3 { color: #fff; }
    html.dark .cx-dot { background: rgb(0 0 0 / .25); color: #e8efe1; border-color: rgb(255 255 255 / .08); }
    html.dark .cx-pair { --vb: #142018; } html.dark .cx-pair.is-care { --vb: #231d10; } html.dark .cx-pair.is-no { --vb: #261414; } html.dark .cx-pair.is-unknown { --vb: #171c24; }
    html.dark .cx-note { background: #231d10; border-color: #5c4a24; } html.dark .cx-note.is-no { background: #261414; border-color: #5c2a2a; }
    html.dark .cx-fine-ok span { background: #14261a; border-color: #24502f; color: #bbf7d0; }
    html.dark .cx-jar div, html.dark .cx-trip, html.dark .cx-uses div, html.dark .cx-ais div { background: #1a2414; border-color: #2b3a1c; }
    html.dark .cx-uses div.is-on { background: #22301a; }
    html.dark .cx-phi div { border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .cx-phi span, html.dark .cx-gap { color: #fed7aa; background: #3a2412; }
    html.dark .cx-ask { background: linear-gradient(135deg, #241f0e, #2a2410); border-color: #5c4a24; }
    html.dark .cx-ask-h b { color: #f5d98a; } html.dark .cx-ask-h small, html.dark .cx-ask .cx-fine { color: #e6cf98; }
    html.dark .cx-ask li { background: #151b12; border-color: #5c4a24; color: #f5d98a; }
    html.dark .cx-foot button { background: #1f2a17; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .cx-chip.is-warn { color: #f5d98a; background: #3a2f12; } html.dark .cx-chip.is-bad { color: #ffc9c9; background: #3a1b1b; }
    html.dark .cx-chip.is-good { color: #bbf7d0; background: #14351f; } html.dark .cx-chip.is-grey { color: #cbd5e1; background: #1e2733; }
    html.dark .cx-field2 > span, html.dark .cx-nut label, html.dark .cx-link { color: #a8cc7e; }
    html.dark .cx-label b, html.dark .cx-sec > h3, html.dark .cx-trip b, html.dark .cx-sh { color: #a8cc7e; }
    html.dark .cx-jar div span { color: #cfe3b8; }
    html.dark .cx-ico { color: #e8efe1; background: rgb(255 255 255 / .09); }
    html.dark .cx-chip:not(.is-warn):not(.is-bad):not(.is-good):not(.is-grey) { color: #d5e3c5; background: rgb(255 255 255 / .08); }
    html.dark .cx-empty svg { color: #a8cc7e; }
</style>

<div class="cx" id="cxApp">
    <section class="cx-box cx-field" aria-label="Your crop">
        <div class="cx-q">
            <b><i>1</i>Which crop?</b>
            <small>The crop decides how strong a spray it can take and which labels name it</small>
            <div id="cxCrop">
                @include('public.site.partials.crop-pick', ['id' => 'cxCropSheet', 'crops' => $crops, 'current' => $cxCrop, 'title' => 'Which crop?', 'noun' => null])
            </div>
        </div>
        <div class="cx-q">
            <b><i>2</i>How old is it?</b>
            <small>Young plants and flowers burn more easily</small>
            <div class="fh-otag-box" id="cxStage"></div>
        </div>
    </section>

    <section class="cx-box cx-mix" aria-label="What you will apply">
        <div class="cx-q">
            <b><i>3</i>What will you apply together?{{ $TL('tank-mix') }}</b>
            <small>Add each spray, fertilizer or spray helper, with how much you use</small>
        </div>
        @if ($nAll)
            <div class="cx-lead"><span>{{ number_format($nSprays) }} sprays</span><span>{{ number_format($nFerts) }} fertilizers</span><span>{{ number_format($nAll - $nSprays - $nFerts) }} helpers and plain chemicals</span></div>
        @endif
        <div class="cx-items" id="cxItems" aria-live="polite"></div>
        <div class="cx-adds">
            <button type="button" class="cx-add" id="cxAddBtn" aria-haspopup="dialog"><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg><span>Add a product</span></button>
            <button type="button" class="cx-add is-ghost" id="cxOwnBtn" aria-haspopup="dialog"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4l10.5-10.5a2.1 2.1 0 00-3-3L5 17v3z"/></svg><span>Add your own</span></button>
        </div>
        <button type="button" class="cx-go" id="cxGo" disabled><span>Check the mix</span><svg fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h13m-5-6l6 6-6 6"/></svg></button>
        <p class="cx-fine"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
            <span>The product label always wins. When a mix is new to you, test a small jar first{{ $TL('jar-test') }} and spray a few plants before the field.</span></p>
    </section>

    <section class="cx-box cx-more" aria-label="Your water, weather and sprayer">
        <div class="cx-q">
            <b><i>4</i>Your water and the weather</b>
            <small>Hard or muddy water and the heat change what mixes well. Leave them if you are not sure</small>
            <div class="cx-tags2">
                <div class="fh-otag-box" id="cxWater"></div>
                <div class="fh-otag-box" id="cxWeather"></div>
            </div>
        </div>
        <div class="cx-q">
            <b><i>5</i>Your sprayer</b>
            <small>For the amounts in the tank and the strength of the spray</small>
            <div class="cx-tags2">
                <div class="fh-otag-box" id="cxTank"></div>
                <div class="fh-otag-box" id="cxWaterHa"></div>
            </div>
        </div>
    </section>
</div>

{{-- Add a product: the whole book, searched. --}}
<dialog class="fhm" id="cxAdd" aria-labelledby="cxAddTitle">
    <div class="fhm-bar">
        <div class="fhm-title"><b id="cxAddTitle">Add a product</b><small>Search by brand, company or active ingredient</small></div>
        <button type="button" class="fhm-x" data-fhm-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
    </div>
    <div class="fhm-panes"><div class="fhm-pane" style="position:absolute;inset:0" id="cxAddPane">
        <div class="cx-find">
            <label class="fhc-search"><svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4.2-4.2"/></svg>
                <span class="sr-only">Search the products</span><input type="search" id="cxQ" placeholder="Like Prevathon, mancozeb, urea or Syngenta" autocomplete="off" enterkeyhint="search"></label>
            <div class="cx-shelves" role="group" aria-label="Which shelf">
                <button type="button" data-shelf="" aria-pressed="true">All</button>
                <button type="button" data-shelf="sprays" aria-pressed="false">Sprays</button>
                <button type="button" data-shelf="fertilizers" aria-pressed="false">Fertilizers</button>
                <button type="button" data-shelf="helpers" aria-pressed="false">Spray helpers</button>
                <button type="button" data-shelf="mine" aria-pressed="false">My own</button>
            </div>
        </div>
        <div class="cx-results" id="cxResults"></div>
    </div></div>
</dialog>

{{-- The farmer's own product: its name, what it is, what is in it, the usual rate. --}}
<dialog class="fhm" id="cxOwn" aria-labelledby="cxOwnTitle">
    <div class="fhm-bar">
        <div class="fhm-title"><b id="cxOwnTitle">Your own product</b><small>Kept for your farm, ready next time</small></div>
        <button type="button" class="fhm-x" data-fhm-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
    </div>
    <div class="fhm-panes"><div class="fhm-pane" style="position:absolute;inset:0">
        <form class="cx-form" id="cxOwnForm" novalidate>
            <label class="cx-field2"><span>Name on the label</span><input name="name" maxlength="120" required placeholder="Like Topdex 50 EC or my 14-14-14"></label>
            <div class="cx-grid2">
                <div class="cx-field2"><span>What it is</span><button type="button" class="cx-pick" id="cxOwnType"><span>Insecticide</span><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 9l-7 7-7-7"/></svg></button></div>
                <div class="cx-field2"><span>Formulation{{ $TL('formulation') }}</span><button type="button" class="cx-pick" id="cxOwnForm2"><span>Not sure</span><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 9l-7 7-7-7"/></svg></button></div>
            </div>
            <div class="cx-field2"><span>How it goes on</span>
                <div class="cx-how" role="group" id="cxOwnHow"><button type="button" data-how="spray" aria-pressed="true">Sprayed</button><button type="button" data-how="soil" aria-pressed="false">On the soil</button></div></div>
            <div class="cx-field2" id="cxOwnAiBox"><span>Active ingredients{{ $TL('active-ingredient') }}</span>
                <div id="cxOwnAi" style="display:grid;gap:.4rem"></div>
                <button type="button" class="cx-link" id="cxOwnAiAdd">Add another ingredient</button>
                <datalist id="cxAiNames">@foreach ($book['ingredients'] as $n)<option value="{{ $n }}">@endforeach</datalist></div>
            <div class="cx-field2" id="cxOwnNutBox" hidden><span>What is in it, percent</span>
                <div class="cx-nut">@foreach (['N' => 'N', 'P2O5' => 'P₂O₅', 'K2O' => 'K₂O', 'Ca' => 'Ca', 'Mg' => 'Mg', 'S' => 'S', 'Zn' => 'Zn', 'B' => 'B', 'Fe' => 'Fe', 'SiO2' => 'SiO₂'] as $k => $l)
                    <label>{{ $l }}<input inputmode="decimal" data-nut="{{ $k }}" placeholder="0"></label>@endforeach</div></div>
            <div class="cx-field2"><span>The rate you use</span>
                <div class="cx-rate"><input inputmode="decimal" id="cxOwnAmt" placeholder="Amount"><button type="button" class="cx-pick" id="cxOwnUnit"><span>tbsp</span><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 9l-7 7-7-7"/></svg></button>
                    <button type="button" class="cx-pick is-per" id="cxOwnPer"><span>per 16 L knapsack</span><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 9l-7 7-7-7"/></svg></button></div></div>
            <label class="cx-field2"><span>Notes, if any</span><textarea name="notes" maxlength="500" placeholder="Anything the label says about mixing"></textarea></label>
            <div class="cx-save"><button type="button" class="is-del" id="cxOwnDel" hidden>Remove</button><button type="submit" class="is-main" id="cxOwnSave">Save and add to the mix</button></div>
        </form>
    </div></div>
</dialog>

{{-- One product's sheet. --}}
<dialog class="fhm" id="cxInfo" aria-labelledby="cxInfoTitle">
    <div class="fhm-bar">
        <div class="fhm-title"><b id="cxInfoTitle">Product</b><small id="cxInfoSub"></small></div>
        <button type="button" class="fhm-x" data-fhm-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
    </div>
    <div class="fhm-panes"><div class="fhm-pane" style="position:absolute;inset:0"><div class="cx-info" id="cxInfoBody"></div></div></div>
</dialog>

{{-- The reading. --}}
<dialog class="fhm" id="cxRes" aria-labelledby="cxResTitle">
    <div class="fhm-bar">
        <div class="fhm-title"><b id="cxResTitle">Your mix</b><small id="cxResSub"></small></div>
        <button type="button" class="fhm-x" data-fhm-close aria-label="Close"><svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg></button>
    </div>
    <div class="fhm-panes"><div class="fhm-pane" style="position:absolute;inset:0" id="cxResPane"><div class="fhm-in"><div class="cx-res" id="cxResBody"></div></div></div></div>
</dialog>

@include('sm.partials.anee-wait')

<script type="application/json" id="cxData">{!! json_encode([
    'crops' => collect($crops)->map(fn ($c) => [$c[1], $c[0], $c[3]]),
    'crop' => $cxCrop,
    'stages' => $book['stages'],
    'water' => $book['water'],
    'weather' => $book['weather'],
    'types' => $book['types'],
    'typesOwn' => $book['typesOwn'],
    'formsOwn' => $book['formsOwn'],
    'mine' => $book['mine'],
    'aneeFace' => \App\Models\AiSetting::current()->faceUrl(),
    'urls' => [
        'find' => route('compat.find'), 'product' => url('/app/field-helpers/compat/product'), 'check' => route('compat.check'), 'mine' => route('compat.mine.save'),
        'mineDel' => url('/app/field-helpers/compat/mine'), 'anee' => route('compat.anee'), 'aneeOptions' => route('compat.anee.options'), 'job' => url('/app/field-helpers/compat/anee'),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@push('scripts')
<script>
(() => {
    const el = document.getElementById('cxData');
    if (!el || !window.FH) return;
    const FH = window.FH, esc = FH.esc, I = FH.icon;
    const D = JSON.parse(el.textContent);
    const $ = (id) => document.getElementById(id);
    const reduce = () => FH.reduce() || document.documentElement.classList.contains('sm-still');
    const say = (m, kind) => (window.toast ? window.toast(m, kind || 'info') : null);
    const tl = (k) => (window.aneeTl && window.aneeTl.flag ? window.aneeTl.flag(k) : '');
    const svg = (d, w) => '<svg fill="none" stroke="currentColor" stroke-width="' + (w || 2) + '" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">' + d + '</svg>';

    /* ---- How each kind of product looks: a colour and a picture ---------- */
    const KIND = {
        insecticide: ['#b4231c', '#fdecea', '<path d="M12 7.5a3 3 0 013 3v4.5a3 3 0 01-6 0V10.5a3 3 0 013-3zM9.5 7l-2-2.5M14.5 7l2-2.5M9 12H5.5M15 12h3.5M9 15.5l-3 2M15 15.5l3 2"/>'],
        acaricide: ['#b4231c', '#fdecea', '<circle cx="12" cy="13" r="4"/><path d="M8.5 10L5 8M15.5 10L19 8M8 13H4M16 13h4M8.5 16L5 18M15.5 16l3.5 2"/>'],
        nematicide: ['#b4231c', '#fdecea', '<path d="M5 15c2-4 4 4 6 0s4 4 6 0 2-4 2-4"/>'],
        molluscicide: ['#8a4b0f', '#fbefe1', '<path d="M4 17h12a4 4 0 100-8 3 3 0 00-3 3M4 17c0-3 2-5 5-5M18 9l1.5-3M15.5 9L15 6"/>'],
        fungicide: ['#1d4ed8', '#e5edff', '<path d="M4 12a8 8 0 0116 0H4zM10 12v6a2 2 0 004 0v-6"/>'],
        herbicide: ['#6d28d9', '#efe8ff', '<path d="M12 21v-8m0 0C12 8 8 6 4 6c0 4 3 7 8 7zm0 0c0-4 3-7 8-7 0 4-4 7-8 7zM4 21h16"/>'],
        pgr: ['#a16207', '#fdf3d1', '<path d="M12 21V9m0 0l-4 4m4-4l4 4M7 5h10"/>'],
        rodenticide: ['#57534e', '#efedeb', '<path d="M6 14a5 5 0 0110 0v2H6zM16 14h3l1 2M9 10l-1-3M13 10l1-3"/>'],
        adjuvant: ['#0f766e', '#ddf5f0', '<path d="M12 3.5s6 6.4 6 10.6a6 6 0 01-12 0c0-4.2 6-10.6 6-10.6zM9.5 15a2.5 2.5 0 002.5 2.5"/>'],
        chemical: ['#0f766e', '#ddf5f0', '<path d="M9 3h6M10 3v5l-5 9a2 2 0 001.8 3h10.4a2 2 0 001.8-3l-5-9V3"/>'],
        foliar: ['#2d6a1f', '#e7f3dc', '<path d="M12 3.5s6 6.4 6 10.6a6 6 0 01-12 0c0-4.2 6-10.6 6-10.6z"/>'],
        soluble: ['#2d6a1f', '#e7f3dc', '<path d="M7 4h10l-1 16H8L7 4zM8 9h8"/>'],
        granular: ['#4d7c0f', '#ecf5d6', '<path d="M6 8h12l-1 12H7L6 8zm3 0V6.5a3 3 0 016 0V8"/><circle cx="10" cy="14" r=".9"/><circle cx="14" cy="15" r=".9"/><circle cx="12" cy="12" r=".9"/>'],
        amendment: ['#78716c', '#f1efec', '<path d="M3 18c3-6 6-9 9-9s6 3 9 9H3z"/>'],
        biostimulant: ['#15803d', '#dcfce7', '<path d="M12 21v-8m0 0c0-4-3-6.5-7-6.5 0 4 3 6.5 7 6.5zm0 0c0-3 2.5-5.5 6-5.5 0 3.2-2.6 5.5-6 5.5z"/>'],
        biological: ['#15803d', '#dcfce7', '<circle cx="9" cy="10" r="3"/><circle cx="15.5" cy="14.5" r="2.5"/><circle cx="8.5" cy="17" r="1.5"/>'],
        other: ['#475569', '#eef2f7', '<circle cx="12" cy="12" r="8"/><path d="M12 8v4M12 16h.01"/>'],
    };
    const kindOf = (t) => KIND[t] || KIND.other;
    const icoHtml = (t) => '<span class="cx-ico" aria-hidden="true">' + svg(kindOf(t)[2], 1.9) + '</span>';
    const tint = (t) => ' style="--c:' + kindOf(t)[0] + ';--cb:' + kindOf(t)[1] + '"';
    const TYPEW = D.types || {};
    const SPRAY_LIQ = ['EC', 'SC', 'SL', 'OD', 'EW', 'ME', 'CS', 'ZC', 'SE', 'OL', 'DC', 'AS', 'L', 'LC', 'F', 'FW', 'liquid'];
    const isFert = (t) => ['granular', 'soluble', 'foliar', 'amendment', 'biostimulant', 'biological'].includes(t);

    /* ---- The units and what an amount is for ------------------------------ */
    const UNIT = { mL: 'mL', L: 'L', tbsp: 'tbsp', tsp: 'tsp', g: 'g', kg: 'kg', bag: 'bag (50 kg)' };
    const perWord = (p) => ({ tank: 'per tank (' + st.tankL + ' L)', '16L': 'per 16 L knapsack', '20L': 'per 20 L knapsack', '200L': 'per 200 L drum', '1L': 'per liter of water', ha: 'per hectare', plant: 'per plant or hill' }[p] || p);
    const perShort = (p) => ({ tank: 'per tank', '16L': 'per 16 L', '20L': 'per 20 L', '200L': 'per drum', '1L': 'per liter', ha: 'per hectare', plant: 'per plant' }[p] || p);
    const PERS_SPRAY = ['16L', '20L', '200L', '1L', 'tank', 'ha'];
    const PERS_SOIL = ['ha', 'plant'];
    const UNITS_SPRAY = ['tbsp', 'tsp', 'mL', 'L', 'g', 'kg'];
    const UNITS_SOIL = ['kg', 'bag', 'g'];

    /* ---- The state, kept on this phone -------------------------------------- */
    const KEY = 'anee.compat.mix';
    let uid = 1;
    const st = { crop: D.crop, stage: '', water: '', weather: '', tankL: 16, waterHa: 200, items: [] };
    try {
        const saved = JSON.parse(localStorage.getItem(KEY) || 'null');
        if (saved && Array.isArray(saved.items)) {
            Object.assign(st, { stage: saved.stage || '', water: saved.water || '', weather: saved.weather || '', tankL: Number(saved.tankL) || 16, waterHa: Number(saved.waterHa) || 200 });
            if (saved.crop && D.crops[saved.crop] && !new URLSearchParams(location.search).get('crop')) st.crop = saved.crop;
            st.items = saved.items.slice(0, 10).map((x) => ({ ...x, uid: uid++ }));
        }
    } catch (_) { /* nothing kept */ }
    const keep = () => { try { localStorage.setItem(KEY, JSON.stringify({ ...st, items: st.items.map(({ uid: _u, ...x }) => x) })); } catch (_) { /* not kept */ } };

    /* ---- The field's tags ----------------------------------------------------- */
    const cropTag = document.querySelector('#cxCrop .cp-tag');
    if (cropTag && st.crop !== D.crop) window.cropPickSet?.(cropTag, st.crop);
    cropTag?.addEventListener('croppick', (e) => { st.crop = e.detail.crop; keep(); paintItems(true); });
    const stageTag = FH.optTag({ box: $('cxStage'), title: 'How old is the crop?', icon: I.sprout, empty: 'Pick its age', value: st.stage, all: 'Not sure', allSub: 'Read for a growing crop',
        opts: [{ k: 'preplant', l: 'Before planting', sub: 'Land preparation, burndown' }, { k: 'seedling', l: 'Seedling or newly transplanted', sub: 'The first two to three weeks' },
            { k: 'vegetative', l: 'Growing', sub: 'Leaves and tillers or vines' }, { k: 'flowering', l: 'Flowering', sub: 'Open flowers' }, { k: 'fruiting', l: 'Filling grain or fruit', sub: 'After the flowers' }],
        onPick: (v) => { st.stage = v; keep(); } });
    const waterTag = FH.optTag({ box: $('cxWater'), title: 'Your spray water', icon: I.drop, empty: 'Your water: not sure', value: st.water, all: 'Not sure', allSub: 'We will say what to watch',
        opts: [{ k: 'soft', l: 'Clear and soft', sub: 'Rain, tap or a shallow well' }, { k: 'hard', l: 'Hard water', sub: 'A deep well, or it leaves a white crust on pails' }, { k: 'muddy', l: 'Muddy or brown', sub: 'From a canal or a river' }],
        onPick: (v) => { st.water = v; keep(); } });
    const weatherTag = FH.optTag({ box: $('cxWeather'), title: 'The weather when you apply', icon: svg('<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>'),
        empty: 'The weather: not sure', value: st.weather, all: 'Not sure', allSub: 'We will say what to watch',
        opts: [{ k: 'cool', l: 'Cool morning or late afternoon', sub: 'The best time to spray' }, { k: 'hot', l: 'Hot, above 32 C', sub: 'Midday, the hot season' }, { k: 'rain', l: 'Rain may come soon', sub: 'Clouds, a rainy afternoon' }],
        onPick: (v) => { st.weather = v; keep(); } });
    const tankTag = FH.optTag({ box: $('cxTank'), title: 'Your sprayer', icon: svg('<path d="M8 6h8a2 2 0 012 2v11a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2zM10 6V4h4v2M6 11h12"/>'), value: String(st.tankL),
        opts: [{ k: '16', l: '16 L knapsack', sub: 'The usual knapsack' }, { k: '20', l: '20 L knapsack' }, { k: '18', l: '18 L knapsack' }, { k: '200', l: '200 L drum', sub: 'A power sprayer or a drum' }, { k: '1', l: '1 L bottle', sub: 'A small hand sprayer' }],
        onPick: (v) => { st.tankL = Number(v) || 16; keep(); paintItems(true); } });
    const waterHaTag = FH.optTag({ box: $('cxWaterHa'), title: 'Water per hectare', note: 'Used only for a rate written per hectare: it says how much goes in each tank.', icon: I.drop, value: String(st.waterHa),
        opts: [160, 200, 300, 400, 500].map((n) => ({ k: String(n), l: n + ' L per hectare', sub: 'About ' + Math.round(n / 16) + ' knapsacks of 16 L' })),
        onPick: (v) => { st.waterHa = Number(v) || 200; keep(); } });

    /* ---- The items ------------------------------------------------------------ */
    const box = $('cxItems'), go = $('cxGo');
    const cropName = () => (D.crops[st.crop] || [])[0] || 'your crop';
    const labelLine = (x) => {
        const r = x.rate;
        if (!r || !r.text) return '';
        const can = r.parsed && fromParsed(r.parsed, x);
        return '<p class="cx-label"><span><b>Label for ' + esc(String(cropName()).toLowerCase()) + ':</b> ' + esc(r.text) + (r.phi ? '. Last spray ' + esc(r.phi) + ' before harvest' : '') + '</span>'
            + (can ? '<button type="button" class="cx-use" data-use="' + x.uid + '">Use the label rate</button>' : '') + '</p>';
    };
    // A label rate [lo, hi, unit, per] as this page's amount, unit and per (the low end: the gentler dose).
    function fromParsed(p, x) {
        let [lo, , unit, per] = p;
        const ok = { tbsp: 1, tsp: 1, mL: 1, L: 1, g: 1, kg: 1 };
        if (!ok[unit]) return null;
        if (per === 'hill' || per === 'tree') per = 'plant';
        if (/^\d+(\.\d+)?L$/.test(per) && !['16L', '20L', '200L', '1L'].includes(per)) { lo = lo / parseFloat(per); per = '1L'; }
        if (!['16L', '20L', '200L', '1L', 'ha', 'plant'].includes(per)) return null;
        return { amount: Math.round(lo * 1000) / 1000, unit, per };
    }
    const itemHtml = (x) => {
        const chips = [];
        if (x.typeWord) chips.push('<span class="cx-chip">' + esc(x.typeWord) + '</span>');
        if (x.f) chips.push('<span class="cx-chip"' + (FH.tlForm(x.f) ? ' data-tl="' + FH.tlForm(x.f) + '"' : '') + '>' + esc(x.f) + '</span>');
        if (x.grade) chips.push('<span class="cx-chip">' + esc(x.grade) + '</span>');
        if (x.ref === 'x') chips.push('<span class="cx-chip is-grey">' + svg('<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>', 2.4) + 'Not in our book</span>');
        if (x.onCrop === false && !isFert(x.t)) chips.push('<span class="cx-chip is-warn">' + svg('<path d="M12 9v4m0 4h.01"/>', 2.6) + 'Label does not name ' + esc(String(cropName()).toLowerCase()) + '</span>');
        if (x.onCrop === true) chips.push('<span class="cx-chip is-good">' + svg('<path d="M5 13l4 4L19 7"/>', 3) + 'Labelled for ' + esc(String(cropName()).toLowerCase()) + '</span>');
        if (x.anee) chips.push('<span class="cx-chip is-warn">Found by Anee</span>');
        const spray = x.applied !== 'soil';
        const showHow = isFert(x.t) || x.t === 'chemical' || x.t === 'other';
        return '<article class="cx-it" data-uid="' + x.uid + '"' + tint(x.t) + '>'
            + '<div class="cx-it-top">' + icoHtml(x.t)
            + '<button type="button" class="cx-it-n" data-info="' + x.uid + '"' + (x.id || x.ref.startsWith('m:') ? '' : ' disabled') + '><b>' + esc(x.name) + '</b>'
            + '<small>' + esc([x.company, (x.ai || []).join(', ')].filter(Boolean).join(' · ') || (x.ref === 'x' ? 'We will check what we can' : '')) + '</small></button>'
            + '<button type="button" class="cx-x" data-rm="' + x.uid + '" aria-label="Take ' + esc(x.name) + ' out of the mix">' + I.x + '</button></div>'
            + '<div class="cx-chips">' + chips.join('') + '</div>'
            + (showHow ? '<div class="cx-how" role="group" aria-label="How it goes on"><button type="button" data-how="spray" data-for="' + x.uid + '" aria-pressed="' + spray + '">Sprayed</button><button type="button" data-how="soil" data-for="' + x.uid + '" aria-pressed="' + !spray + '">On the soil</button></div>' : '')
            + '<div class="cx-rate"><input inputmode="decimal" data-amt="' + x.uid + '" value="' + (x.amount ?? '') + '" placeholder="How much" aria-label="Amount of ' + esc(x.name) + '">'
            + '<button type="button" class="cx-pick" data-unit="' + x.uid + '" aria-label="Unit"><span>' + esc(UNIT[x.unit] || x.unit) + '</span>' + I.chev + '</button>'
            + '<button type="button" class="cx-pick is-per" data-per="' + x.uid + '" aria-label="' + esc('The amount is ' + perWord(x.per)) + '"><span>' + esc(perShort(x.per)) + '</span>' + I.chev + '</button></div>'
            + labelLine(x) + '</article>';
    };
    const paintItems = (animate, added) => {
        const draw = () => {
            box.innerHTML = st.items.length ? st.items.map(itemHtml).join('')
                : '<div class="cx-empty">' + svg('<path d="M8 6h8a2 2 0 012 2v11a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2zM10 6V4h4v2M6 11h12"/>', 1.8)
                    + '<b>Nothing in the mix yet</b><small>Add the sprays and fertilizers you want to put on together, with how much of each.</small></div>';
            if (added && !reduce()) box.querySelector('.cx-it[data-uid="' + added + '"]')?.classList.add('is-in');
        };
        if (animate) FH.swap(box, draw); else draw();
        go.disabled = st.items.length < 1;
        go.querySelector('span').textContent = st.items.length >= 2 ? 'Check the mix of ' + st.items.length : (st.items.length ? 'Check this product' : 'Check the mix');
    };
    const byUid = (u) => st.items.find((x) => String(x.uid) === String(u));
    box.addEventListener('input', (e) => {
        const a = e.target.closest('[data-amt]');
        if (!a) return;
        const x = byUid(a.dataset.amt);
        const v = a.value.replace(',', '.').trim();
        x.amount = v === '' || isNaN(Number(v)) ? null : Number(v);
        keep();
    });
    box.addEventListener('click', async (e) => {
        const rm = e.target.closest('[data-rm]');
        if (rm) {
            const art = rm.closest('.cx-it'), u = rm.dataset.rm;
            const done = () => { st.items = st.items.filter((x) => String(x.uid) !== String(u)); keep(); paintItems(true); };
            if (reduce()) done(); else { art.classList.add('is-out'); setTimeout(done, 230); }
            return;
        }
        const how = e.target.closest('[data-how][data-for]');
        if (how) {
            const x = byUid(how.dataset.for);
            if (x.applied === how.dataset.how) return;
            x.applied = how.dataset.how;
            if (x.applied === 'soil') { if (!UNITS_SOIL.includes(x.unit)) x.unit = 'kg'; if (!PERS_SOIL.includes(x.per)) x.per = 'ha'; }
            else { if (!UNITS_SPRAY.includes(x.unit)) x.unit = 'g'; if (!PERS_SPRAY.includes(x.per)) x.per = '16L'; }
            keep(); paintItems(true);
            return;
        }
        const un = e.target.closest('[data-unit]');
        if (un) {
            const x = byUid(un.dataset.unit);
            const list = x.applied === 'soil' ? UNITS_SOIL : UNITS_SPRAY;
            const v = await FH.pick({ title: 'Unit for ' + x.name, value: x.unit, counts: false, from: un, opts: list.map((k) => ({ k, l: UNIT[k], sub: { tbsp: 'A tablespoon, 15 mL', tsp: 'A teaspoon, 5 mL', bag: 'A 50 kg bag' }[k] || '' })) });
            if (v) { x.unit = v; keep(); paintItems(false); FH.bump(box.querySelector('[data-unit="' + x.uid + '"]')); }
            return;
        }
        const pe = e.target.closest('[data-per]');
        if (pe) {
            const x = byUid(pe.dataset.per);
            const list = x.applied === 'soil' ? PERS_SOIL : PERS_SPRAY;
            const v = await FH.pick({ title: 'The amount is for', value: x.per, counts: false, from: pe, opts: list.map((k) => ({ k, l: perWord(k) })) });
            if (v) { x.per = v; keep(); paintItems(false); }
            return;
        }
        const use = e.target.closest('[data-use]');
        if (use) {
            const x = byUid(use.dataset.use), r = x.rate && x.rate.parsed && fromParsed(x.rate.parsed, x);
            if (r) { Object.assign(x, r); keep(); paintItems(false); FH.bump(box.querySelector('[data-amt="' + x.uid + '"]')); }
            return;
        }
        const inf = e.target.closest('[data-info]');
        if (inf) { const x = byUid(inf.dataset.info); if (x.ref.startsWith('m:')) openOwn(D.mine.find((m) => m.ref === x.ref)); else if (x.id) openInfo(x.id); }
    });

    // A product (a book card, or one of the farmer's own) into the mix, with a rate to start from.
    const add = (c) => {
        if (st.items.length >= 10) { say('Ten products is the most one check takes.', 'error'); return; }
        if (c.ref && st.items.some((x) => x.ref === c.ref)) { say(c.b + ' is already in the mix.'); return; }
        const applied = c.applied === 'soil' ? 'soil' : 'spray';
        const liquid = SPRAY_LIQ.includes(c.f) || c.form === 'liquid';
        const x = { uid: uid++, ref: c.ref || (c.id ? 'p:' + c.id : 'x'), id: c.id || null, name: c.b, company: c.c || '', t: c.t || 'other', typeWord: c.type || TYPEW[c.t] || '', f: c.f || '',
            grade: c.grade || '', ai: c.ai || [], onCrop: c.onCrop ?? null, rate: c.rate || null, anee: !!c.anee, applied,
            amount: null, unit: applied === 'soil' ? 'kg' : (liquid ? 'tbsp' : 'g'), per: applied === 'soil' ? 'ha' : (st.tankL === 16 ? '16L' : 'tank') };
        if (c.rate && c.rate.parsed) { const r = fromParsed(c.rate.parsed, x); if (r) Object.assign(x, r); }
        if (c.ownRate) Object.assign(x, c.ownRate);
        st.items.push(x);
        keep();
        paintItems(true, x.uid);
    };

    /* ---- The add sheet ---------------------------------------------------------- */
    const addDlg = $('cxAdd'), q = $('cxQ'), results = $('cxResults');
    let shelf = '', qt = 0, seq = 0, found = [];
    const QUICK = ['Urea', 'Ammonium sulfate', 'Muriate of potash', 'Complete 14-14-14', 'Calcium nitrate', 'Potassium nitrate', 'Borax', 'Zinc sulfate', 'Epsom salt', 'Potassium silicate', 'Spreader sticker', 'Mancozeb', 'Cypermethrin', 'Glyphosate'];
    const rowHtml = (c, i) => '<button type="button" class="cx-row" data-pick="' + i + '"' + tint(c.t) + '>' + icoHtml(c.t)
        + '<span class="cx-row-t"><b>' + esc(c.b) + '</b><small>' + esc([c.c, c.type || TYPEW[c.t], c.f || c.grade].filter(Boolean).join(' · ')) + '</small>'
        + (c.ai && c.ai.length ? '<small>' + esc(c.ai.join(', ')) + '</small>' : '') + (c.onCrop ? '<small style="color:#2d6a1f;font-weight:700">Labelled for ' + esc(String(cropName()).toLowerCase()) + '</small>' : '') + '</span>'
        + '<span class="cx-row-plus" aria-hidden="true">' + svg('<path d="M12 5v14M5 12h14"/>', 3) + '</span></button>';
    const drawFind = (list, mine, text) => {
        found = list.concat(mine);
        const anim = !reduce();
        let h = '';
        if (!text) {
            h = (shelf === 'mine' ? '' : '<p class="cx-sh">Plain fertilizers and chemicals</p><div class="cx-quick">' + QUICK.map((w) => '<button type="button" data-quick="' + esc(w) + '">' + esc(w) + '</button>').join('') + '</div>')
                + (D.mine.length ? '<p class="cx-sh">Your own products</p>' + D.mine.map((c, i) => rowHtml(c, list.length + i)).join('') : (shelf === 'mine' ? '<div class="cx-hint">You have no products of your own yet. <b>Add your own</b> with its ingredients and rate, and it waits here next time.<button type="button" class="cx-add is-ghost" data-own>Add your own</button></div>' : ''));
            found = list.concat(D.mine);
        } else if (!found.length) {
            h = '<div class="cx-hint"><b>Nothing by that name in our book.</b> You can still add "' + esc(text) + '": the check will say we do not know it, and Anee can look it up online. Or add it as your own with its ingredients.'
                + '<button type="button" class="cx-add" data-typed>Add "' + esc(text) + '" anyway</button><button type="button" class="cx-add is-ghost" data-own>Add it as your own</button></div>';
        } else {
            h = (mine.length ? '<p class="cx-sh">Your own</p>' + mine.map((c, i) => rowHtml(c, list.length + i)).join('') : '') + (list.length ? '<p class="cx-sh">In our book</p>' + list.map(rowHtml).join('') : '')
                + '<div class="cx-hint">Not the one? Add "' + esc(text) + '" anyway, or as your own.<button type="button" class="cx-add is-ghost" data-typed>Add "' + esc(text) + '" anyway</button></div>';
        }
        results.innerHTML = h;
        if (anim) results.querySelectorAll('.cx-row').forEach((r, i) => { r.style.animationDelay = Math.min(i, 12) * 18 + 'ms'; r.classList.add('is-in'); });
    };
    const runFind = async () => {
        const text = q.value.trim();
        const my = ++seq;
        if (shelf === 'mine') {
            const m = D.mine.filter((c) => !text || (c.b + ' ' + (c.ai || []).join(' ')).toLowerCase().includes(text.toLowerCase()));
            drawFind([], m, text);
            if (!text) results.innerHTML = results.innerHTML || '';
            return;
        }
        if (text.length < 2) { drawFind([], [], ''); return; }
        try {
            const r = await window.api(D.urls.find + '?q=' + encodeURIComponent(text) + '&crop=' + encodeURIComponent(st.crop || '') + (shelf ? '&shelf=' + shelf : ''));
            if (my !== seq) return;
            drawFind((r.data || {}).items || [], shelf ? [] : (r.data || {}).mine || [], text);
        } catch (err) { if (my === seq) results.innerHTML = '<div class="cx-hint">' + esc(err.message || 'Could not connect. Please try again.') + '</div>'; }
    };
    q.addEventListener('input', () => { clearTimeout(qt); qt = setTimeout(runFind, 220); });
    addDlg.querySelector('.cx-shelves').addEventListener('click', (e) => {
        const b = e.target.closest('[data-shelf]');
        if (!b) return;
        shelf = b.dataset.shelf;
        addDlg.querySelectorAll('[data-shelf]').forEach((x) => x.setAttribute('aria-pressed', String(x === b)));
        runFind();
    });
    results.addEventListener('click', (e) => {
        const quick = e.target.closest('[data-quick]');
        if (quick) { q.value = quick.dataset.quick; runFind(); return; }
        const p = e.target.closest('[data-pick]');
        if (p) {
            const c = found[Number(p.dataset.pick)];
            if (!c) return;
            add(c.ref && c.ref.startsWith('m:') ? { ...c, ownRate: c.rate || null, rate: null } : { ...c, ref: 'p:' + c.id });
            FH.closeModal(addDlg);
            return;
        }
        if (e.target.closest('[data-typed]')) { const t = q.value.trim(); if (t) { add({ b: t, ref: 'x', t: 'other' }); FH.closeModal(addDlg); } return; }
        if (e.target.closest('[data-own]')) { FH.closeModal(addDlg).then(() => openOwn(null, q.value.trim())); }
    });
    $('cxAddBtn').addEventListener('click', () => {
        q.value = ''; shelf = '';
        addDlg.querySelectorAll('[data-shelf]').forEach((x) => x.setAttribute('aria-pressed', String(x.dataset.shelf === '')));
        drawFind([], [], '');
        FH.openModal(addDlg);
        if (matchMedia('(pointer: fine)').matches) setTimeout(() => q.focus(), 60);
    });

    /* ---- The farmer's own product ------------------------------------------------- */
    const ownDlg = $('cxOwn'), ownForm = $('cxOwnForm');
    const own = { id: null, type: 'insecticide', form: '', applied: 'spray', unit: 'tbsp', per: '16L' };
    const AIU = ['g/L', 'g/kg', '%', 'mL/L'];
    const aiRow = (a) => '<div class="cx-airow"><input list="cxAiNames" data-ain placeholder="Like cypermethrin" value="' + esc(a.n || '') + '" aria-label="Active ingredient">'
        + '<input inputmode="decimal" data-aia placeholder="50" value="' + esc(a.a ?? '') + '" aria-label="Strength"><button type="button" class="cx-pick" data-aiu><span>' + esc(a.u || 'g/L') + '</span>' + I.chev + '</button>'
        + '<button type="button" class="cx-x" data-airm aria-label="Remove this ingredient">' + I.x + '</button></div>';
    const paintOwn = () => {
        const fert = isFert(own.type);
        $('cxOwnType').querySelector('span').textContent = TYPEW[own.type] || own.type;
        $('cxOwnForm2').querySelector('span').textContent = own.form || 'Not sure';
        $('cxOwnAiBox').hidden = fert;
        $('cxOwnNutBox').hidden = !fert && own.type !== 'other';
        $('cxOwnHow').querySelectorAll('[data-how]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.how === own.applied)));
        $('cxOwnUnit').querySelector('span').textContent = UNIT[own.unit] || own.unit;
        $('cxOwnPer').querySelector('span').textContent = perShort(own.per);
        $('cxOwnDel').hidden = !own.id;
    };
    function openOwn(m, name) {
        ownForm.reset();
        own.id = m ? m.id : null;
        const raw = m ? m.raw : null;
        own.type = raw ? raw.type : 'insecticide';
        own.form = raw ? raw.form : '';
        own.applied = raw ? raw.applied : 'spray';
        own.unit = m && m.rate ? m.rate.unit || 'tbsp' : 'tbsp';
        own.per = m && m.rate ? m.rate.per || '16L' : '16L';
        ownForm.elements.name.value = m ? m.b : (name || '');
        ownForm.elements.notes.value = raw ? raw.notes || '' : '';
        $('cxOwnAmt').value = m && m.rate ? m.rate.amount : '';
        $('cxOwnAi').innerHTML = ((raw && raw.ai && raw.ai.length) ? raw.ai : [{}]).map(aiRow).join('');
        ownForm.querySelectorAll('[data-nut]').forEach((i) => { i.value = raw && raw.n && raw.n[i.dataset.nut] ? raw.n[i.dataset.nut] : ''; });
        $('cxOwnTitle').textContent = m ? 'Your own: ' + m.b : 'Your own product';
        paintOwn();
        FH.openModal(ownDlg);
    }
    $('cxOwnBtn').addEventListener('click', () => openOwn(null));
    $('cxOwnType').addEventListener('click', async (e) => {
        const v = await FH.pick({ title: 'What it is', value: own.type, counts: false, from: e.currentTarget, opts: D.typesOwn.map((k) => ({ k, l: TYPEW[k] || k, icon: svg(kindOf(k)[2], 1.9) })) });
        if (v) { own.type = v; if (isFert(v) && v !== 'foliar' && v !== 'soluble') { own.applied = 'soil'; own.unit = 'kg'; own.per = 'ha'; } paintOwn(); }
    });
    $('cxOwnForm2').addEventListener('click', async (e) => {
        const v = await FH.pick({ title: 'Formulation, as on the label', value: own.form, counts: false, from: e.currentTarget, all: 'Not sure', opts: D.formsOwn.map((k) => ({ k, l: { liquid: 'Liquid fertilizer', soluble: 'Soluble powder', granular: 'Granules' }[k] || k })) });
        if (v !== null) { own.form = v; paintOwn(); }
    });
    $('cxOwnHow').addEventListener('click', (e) => { const b = e.target.closest('[data-how]'); if (!b) return; own.applied = b.dataset.how; if (own.applied === 'soil') { own.unit = 'kg'; own.per = 'ha'; } else { own.unit = 'tbsp'; own.per = '16L'; } paintOwn(); });
    $('cxOwnUnit').addEventListener('click', async (e) => {
        const v = await FH.pick({ title: 'Unit', value: own.unit, counts: false, from: e.currentTarget, opts: (own.applied === 'soil' ? UNITS_SOIL : UNITS_SPRAY).map((k) => ({ k, l: UNIT[k] })) });
        if (v) { own.unit = v; paintOwn(); }
    });
    $('cxOwnPer').addEventListener('click', async (e) => {
        const v = await FH.pick({ title: 'The amount is for', value: own.per, counts: false, from: e.currentTarget, opts: (own.applied === 'soil' ? PERS_SOIL : PERS_SPRAY).map((k) => ({ k, l: perWord(k) })) });
        if (v) { own.per = v; paintOwn(); }
    });
    $('cxOwnAiAdd').addEventListener('click', () => { if ($('cxOwnAi').children.length < 6) $('cxOwnAi').insertAdjacentHTML('beforeend', aiRow({})); });
    $('cxOwnAi').addEventListener('click', async (e) => {
        const rm = e.target.closest('[data-airm]');
        if (rm) { rm.closest('.cx-airow').remove(); return; }
        const u = e.target.closest('[data-aiu]');
        if (u) { const v = await FH.pick({ title: 'Strength unit', value: u.textContent.trim(), counts: false, from: u, opts: AIU.map((k) => ({ k, l: k, sub: { 'g/L': 'Grams in a liter (liquids)', 'g/kg': 'Grams in a kilo (powders, granules)', '%': 'Percent', 'mL/L': 'Milliliters in a liter' }[k] })) }); if (v) u.querySelector('span').textContent = v; }
    });
    ownForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = $('cxOwnSave');
        const name = ownForm.elements.name.value.trim();
        if (!name) { say('Write the name on the label first.', 'error'); ownForm.elements.name.focus(); return; }
        const ai = [...$('cxOwnAi').querySelectorAll('.cx-airow')].map((r) => ({ n: r.querySelector('[data-ain]').value.trim(), a: r.querySelector('[data-aia]').value.replace(',', '.').trim(), u: r.querySelector('[data-aiu] span').textContent.trim() })).filter((a) => a.n);
        const n = {};
        ownForm.querySelectorAll('[data-nut]').forEach((i) => { const v = i.value.replace(',', '.').trim(); if (v && !isNaN(Number(v)) && Number(v) > 0) n[i.dataset.nut] = Number(v); });
        const amt = $('cxOwnAmt').value.replace(',', '.').trim();
        btn.disabled = true;
        try {
            const r = await window.api(D.urls.mine, { method: 'POST', body: { id: own.id, name, type: own.type, form: own.form, applied: own.applied, ai: isFert(own.type) ? [] : ai, n,
                rateAmount: amt && !isNaN(Number(amt)) ? Number(amt) : null, rateUnit: own.unit, ratePer: own.per, notes: ownForm.elements.notes.value.trim() } });
            D.mine = (r.data || {}).mine || D.mine;
            const it = (r.data || {}).item;
            const was = own.id;
            await FH.closeModal(ownDlg);
            if (it) {
                const inMix = st.items.find((x) => x.ref === it.ref);
                if (inMix) { Object.assign(inMix, { name: it.b, t: it.t, typeWord: it.type, f: it.f, ai: it.ai, applied: it.applied }); keep(); paintItems(true); }
                else if (!was) add({ ...it, ownRate: it.rate || null, rate: null });
            }
            say('Saved for your farm.', 'success');
        } catch (err) { say(err.message, 'error'); } finally { btn.disabled = false; }
    });
    $('cxOwnDel').addEventListener('click', async () => {
        const btn = $('cxOwnDel');
        if (!own.id) return;
        if (window.confirmAction && !(await window.confirmAction('Remove this product of your own?', { confirm: 'Remove' }))) return;
        btn.disabled = true;
        try {
            const r = await window.api(D.urls.mineDel + '/' + own.id, { method: 'DELETE' });
            D.mine = (r.data || {}).mine || [];
            st.items = st.items.filter((x) => x.ref !== 'm:' + own.id);
            keep(); paintItems(true);
            await FH.closeModal(ownDlg);
        } catch (err) { say(err.message, 'error'); } finally { btn.disabled = false; }
    });

    /* ---- One product's sheet -------------------------------------------------------- */
    const infoDlg = $('cxInfo');
    async function openInfo(id) {
        $('cxInfoTitle').textContent = 'Product';
        $('cxInfoSub').textContent = '';
        $('cxInfoBody').innerHTML = '<p class="cx-hint">Opening…</p>';
        FH.openModal(infoDlg);
        try {
            const r = await window.api(D.urls.product + '/' + id + '?crop=' + encodeURIComponent(st.crop || ''));
            const p = (r.data || {}).p;
            $('cxInfoTitle').textContent = p.b;
            $('cxInfoSub').textContent = [p.c, p.type].filter(Boolean).join(' · ');
            const sec = (h, body, k) => body ? '<section class="cx-sec"><h3' + (k ? ' data-tl="' + k + '"' : '') + '>' + esc(h) + '</h3>' + body + '</section>' : '';
            $('cxInfoBody').innerHTML = '<div' + tint(p.t) + ' style="display:flex;gap:.7rem;align-items:center">' + icoHtml(p.t) + '<div><h2>' + esc(p.b) + '</h2><p class="cx-label" style="margin-top:.15rem">'
                + esc([p.c, p.type, p.f, p.grade].filter(Boolean).join(' · ')) + '</p></div></div>'
                + sec('Active ingredients', (p.known || []).length ? '<div class="cx-ais">' + p.known.map((a, i) => '<div><b>' + esc((p.ai[i] || a.name)) + '</b><small>' + esc([a.cls, a.grp].filter(Boolean).join(', ')) + '</small></div>').join('') + '</div>'
                    : ((p.ai || []).length ? '<div class="cx-ais">' + p.ai.map((a) => '<div><b>' + esc(a) + '</b></div>').join('') + '</div>' : ''), 'active-ingredient')
                + (p.notes ? '<p class="cx-strong">' + esc(p.notes) + '</p>' : '')
                + sec('What is in it', p.analysis ? '<p class="cx-strong">' + esc(p.analysis) + '</p>' : '')
                + sec('Its chemistry', p.chem ? '<div class="cx-ais">' + [['si', 'Salt index (sodium nitrate is 100)'], ['sol', 'Dissolves, grams in a liter'], ['ph', 'pH in water'], ['ec', 'EC of 1 g in a liter, mS per cm'], ['crh', 'Takes up water from the air above this humidity, percent']]
                    .filter(([k]) => p.chem[k] !== undefined && p.chem[k] !== null && p.chem[k] !== '').map(([k, l]) => '<div><small>' + esc(l) + '</small><b>' + esc(p.chem[k]) + '</b></div>').join('')
                    + ((p.chem.foliarMax || []).length ? '<div><small>Strongest leaf spray</small><b>' + esc(p.chem.foliarMax.map((f) => f[1] + '% for ' + f[0]).join(', ')) + '</b></div>' : '') + '</div>' : '')
                + sec(p.c ? 'What its label says about mixing' : 'What to know about mixing it', (p.mix || []).length ? '<ul class="cx-tips">' + p.mix.map((m) => '<li>' + esc(m) + '</li>').join('') + '</ul>' : '')
                + sec('On its label', (p.uses || []).length ? '<div class="cx-uses">' + p.uses.map((u) => '<div class="' + (u.key === st.crop ? 'is-on' : '') + '"><b>' + esc(u.crop) + '</b>' + (u.for ? ': ' + esc(u.for) : '')
                    + (u.rate || u.phi ? '<small>' + esc([u.rate, u.phi ? 'last spray ' + u.phi + ' before harvest' : ''].filter(Boolean).join('. ')) + '</small>' : '') + '</div>').join('') + '</div>'
                    : ((p.crops || []).length ? '<p class="cx-strong">' + esc(p.crops.join(', ')) + '</p>' : ''), 'label-rate')
                + (p.reg ? '<p class="cx-label">FPA reg. no. ' + esc(p.reg) + '</p>' : '')
                + (p.anee ? '<p class="cx-label">Anee found this product online for a farmer. Check its label before you use it.</p>' : '')
                + '<p class="cx-fine">' + svg('<path d="M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/>') + '<span>Use the rate on the product label. The label always wins.</span></p>';
        } catch (err) { $('cxInfoBody').innerHTML = '<p class="cx-hint">' + esc(err.message) + '</p>'; }
    }

    /* ---- The reading -------------------------------------------------------------------- */
    const resDlg = $('cxRes'), resBody = $('cxResBody');
    const LV = { ok: 'Mixes fine', care: 'Jar test first', no: 'Do not mix', unknown: 'Not in our book' };
    const VICO = { ok: '<path d="M5 13l4 4L19 7"/>', care: '<path d="M9 3h6M10 3v5l-5 9a2 2 0 001.8 3h10.4a2 2 0 001.8-3l-5-9V3"/>', no: '<path d="M6 6l12 12M18 6L6 18"/>', unknown: '<path d="M9.5 9a2.5 2.5 0 015 .5c0 1.7-2.5 2-2.5 4M12 17h.01"/>' };
    let last = null, lastBody = null, aneeOpt = null;
    const caseBody = () => ({ crop: st.crop, stage: st.stage, water: st.water, weather: st.weather, tankL: st.tankL, waterHa: st.waterHa,
        items: st.items.map((x) => ({ ref: x.ref, name: x.name, applied: x.applied, amount: x.amount, unit: x.unit, per: x.per })) });
    const nm = (r, i) => esc((r.items[i] || {}).name || '');
    function drawResult(r) {
        last = r;
        const worstOf = (i) => r.pairs.filter((p) => p.a === i || p.b === i).reduce((w, p) => ({ ok: 0, unknown: 1, care: 2, no: 3 }[p.level] > { ok: 0, unknown: 1, care: 2, no: 3 }[w] ? p.level : w), r.items[i].known ? 'ok' : 'unknown');
        const out = [];
        out.push('<section class="cx-verdict is-' + r.verdict + '"><span class="cx-vico" aria-hidden="true">' + svg(VICO[r.verdict], 3) + '</span><div><h3>' + esc(r.headline.t) + '</h3><p>' + esc(r.headline.s) + '</p></div>'
            + '<div class="cx-mixline">' + r.items.map((x) => '<span class="cx-dot"><i class="is-' + worstOf(x.i) + '"></i>' + esc(x.name) + '</span>').join('') + '</div></section>');
        // What the book could not read, and Anee's offer.
        const unk = r.unknown.items.map((i) => r.items[i].name);
        if ((unk.length || r.unknown.pairs.length) && !r.aneeChecked) {
            out.push('<section class="cx-ask" id="cxAsk"><div class="cx-ask-h">' + (D.aneeFace ? '<img src="' + esc(D.aneeFace) + '" alt="">' : '') + '<div><b>Ask Anee to check online</b>'
                + '<small>Our book does not know ' + (unk.length ? 'these well enough' : 'how these pairs mix') + '. Anee reads the labels and the registrations online, and what she finds is kept so the next check is free.</small></div></div>'
                + (unk.length ? '<ul>' + unk.map((n) => '<li>' + esc(n) + '</li>').join('') + '</ul>' : '')
                + '<button type="button" class="cx-go" id="cxAskGo" disabled><span>Checking the price…</span>' + svg('<path d="M5 12h13m-5-6l6 6-6 6"/>', 2.6) + '</button>'
                + '<p class="cx-fine" id="cxAskWhy">' + svg('<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>') + '<span>The price is shown before it runs. Nothing is charged if she cannot finish.</span></p></section>');
        }
        // Pair by pair: the ones that matter first, the fine ones in one line.
        const bad = r.pairs.filter((p) => p.level !== 'ok');
        const fine = r.pairs.filter((p) => p.level === 'ok');
        if (r.pairs.length) {
            out.push('<section class="cx-sec"><h3 data-tl="tank-mix">' + svg('<path d="M8 7h8M8 12h8M8 17h5"/>') + 'Pair by pair</h3><div class="cx-pairs">'
                + bad.map((p) => '<article class="cx-pair is-' + p.level + '"><div class="cx-pair-h"><b>' + nm(r, p.a) + ' <em>+</em> ' + nm(r, p.b) + '</b><span class="cx-lvl">' + LV[p.level] + '</span></div>'
                    + (p.reasons.length ? p.reasons.map((x) => '<p class="cx-why">' + esc(x.why) + (x.anee ? '<span class="cx-anee">Anee checked</span>' : '') + '</p>' + (x.fix ? '<p class="cx-fix"><b>Do instead:</b> ' + esc(x.fix) + '</p>' : '')).join('')
                        : '<p class="cx-why">' + (p.level === 'unknown' ? 'We do not know ' + esc(r.items[p.a].known ? r.items[p.b].name : r.items[p.a].name) + ' well enough to say. Do a jar test, or ask Anee to check online.' : '') + '</p>')
                    + (p.gap ? '<span class="cx-gap">' + svg('<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/>', 2.2) + 'Keep ' + p.gap + ' days apart</span>' : '')
                    + (p.mode !== 'tank' && p.level !== 'unknown' ? '<p class="cx-label">' + (p.mode === 'blend' ? 'Mixed in one bag and spread together.' : 'One sprayed, one on the soil, on the same field.') + '</p>' : '') + '</article>').join('')
                + (fine.length ? '<div><p class="cx-label" style="margin-bottom:.35rem"><b>These pairs mix fine as far as we know:</b></p><div class="cx-fine-ok">'
                    + fine.map((p) => '<span>' + nm(r, p.a) + ' + ' + nm(r, p.b) + (p.good ? ', a common mix' : '') + '</span>').join('') + '</div></div>' : '')
                + '</div></section>');
        }
        // The whole tank.
        if (r.tank.length) {
            out.push('<section class="cx-sec"><h3>' + I.warn + 'Watch out</h3><div class="cx-notes">' + r.tank.map((n) => '<div class="cx-note' + (n.level === 'no' ? ' is-no' : '') + '">' + I.warn
                + '<div><p>' + esc(n.why) + '</p>' + (n.fix ? '<p><b>Do instead:</b> ' + esc(n.fix) + '</p>' : '') + '</div></div>').join('') + '</div></section>');
        }
        // How strong.
        if (r.strength) {
            const s = r.strength, max = Math.max(s.limit * 1.6, s.pct * 1.1, 0.1), at = Math.min(100, s.pct / max * 100), limAt = s.limit / max * 100;
            out.push('<section class="cx-sec"><h3 data-tl="foliar-strength">' + I.drop + 'How strong is the spray</h3>'
                + (s.gl > 0 ? '<p class="cx-strong">The fertilizer in the spray: <b>' + s.pct + '%</b>, about ' + s.gl + ' g in a liter. The limit for ' + esc(String(r.cropName || 'this crop').toLowerCase()) + (r.stageWord ? ' ' + esc(r.stageWord) : '') + ' is about ' + s.limit + '%.</p>'
                    + '<div class="cx-meter" style="background:linear-gradient(90deg,#bbf7d0 0%,#bbf7d0 ' + (limAt * 0.8).toFixed(1) + '%,#fde68a ' + (limAt * 0.8).toFixed(1) + '%,#fde68a ' + limAt.toFixed(1) + '%,#fecaca ' + limAt.toFixed(1) + '%)"><i style="left:calc(' + at.toFixed(1) + '% - .15rem)"></i></div>'
                    + '<div class="cx-meter-l"><span>Weak</span><span>The limit</span><span>Burns</span></div>' : '')
                + (s.missing.length ? '<p class="cx-label">Add the amount for ' + esc(s.missing.join(', ')) + ' to see how strong the spray is.</p>' : '')
                + '</section>');
        }
        // How to mix, and the jar test.
        if (r.order.length > 2) {
            out.push('<section class="cx-sec"><h3 data-tl="mixing-order">' + svg('<path d="M4 6h16M4 12h10M4 18h6"/>') + 'How to mix, in this order</h3><ol class="cx-steps">'
                + r.order.map((s) => '<li>' + esc(s.say) + '</li>').join('') + '</ol></section>');
            const jar = r.jar.filter((j) => j.amount);
            out.push('<section class="cx-sec"><h3 data-tl="jar-test">' + svg('<path d="M8 3h8M9 3v3l-2 3v11a1 1 0 001 1h8a1 1 0 001-1V9l-2-3V3"/>') + 'The jar test</h3><ol class="cx-steps">'
                + (window.CX_JAR || []).map((s) => '<li>' + esc(s) + '</li>').join('') + '</ol>'
                + (jar.length ? '<div class="cx-jar">' + jar.map((j) => '<div><b>' + esc(j.name) + '</b><span>' + esc(j.amount) + '</span></div>').join('') + '</div><p class="cx-label">In one liter of water, the same strength as your tank.</p>'
                    : '<p class="cx-label">Add the amounts to see how much of each goes into the jar.</p>') + '</section>');
        }
        // Split the work.
        const trips = r.trips.filter((t) => t.length);
        if (trips.length > 1 || r.gaps.length || r.spreads.length > 1) {
            out.push('<section class="cx-sec"><h3>' + svg('<path d="M7 7h10M7 12h10M7 17h10"/>') + 'Split the work</h3><div class="cx-trips">'
                + trips.map((t, k) => '<div class="cx-trip"><b>Spray ' + (k + 1) + '</b><ul>' + t.map((i) => '<li>' + nm(r, i) + '</li>').join('') + '</ul></div>').join('')
                + (r.spreads.length > 1 ? r.spreads.map((t, k) => '<div class="cx-trip"><b>Spread ' + (k + 1) + '</b><ul>' + t.map((i) => '<li>' + nm(r, i) + '</li>').join('') + '</ul></div>').join('') : '')
                + '</div>' + (r.gaps.length ? '<ul class="cx-tips" style="margin-top:.6rem">' + r.gaps.map((g) => '<li>Keep ' + g.days + ' days between ' + nm(r, g.a) + ' and ' + nm(r, g.b) + '.</li>').join('') + '</ul>' : '') + '</section>');
        }
        // Before harvest, and the labels' own words.
        const phi = r.items.filter((x) => x.phi);
        if (phi.length) out.push('<section class="cx-sec"><h3>' + svg('<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/>') + 'Last spray before harvest</h3><div class="cx-phi">'
            + phi.map((x) => '<div><b>' + esc(x.name) + '</b><span>' + esc(x.phi) + '</span></div>').join('') + '</div></section>');
        const mix = r.items.filter((x) => (x.mix || []).length);
        if (mix.length) out.push('<section class="cx-sec"><h3>' + I.book + 'What the labels say about mixing</h3>' + mix.map((x) => '<p class="cx-label" style="margin-top:.4rem"><b>' + esc(x.name) + '</b></p><ul class="cx-tips">'
            + x.mix.map((m) => '<li>' + esc(m) + '</li>').join('') + '</ul>').join('') + '</section>');
        if (r.tips.length) out.push('<section class="cx-sec"><h3>' + I.info + 'Good to know</h3><ul class="cx-tips">' + r.tips.map((t) => '<li>' + esc(t) + '</li>').join('') + '</ul></section>');
        if (r.anee && (r.anee.summary || (r.anee.notes || []).length)) out.push('<section class="cx-sec"><h3>' + I.search + 'What Anee found online</h3>' + (r.anee.summary ? '<p class="cx-strong">' + esc(r.anee.summary) + '</p>' : '')
            + ((r.anee.notes || []).length ? '<ul class="cx-tips" style="margin-top:.5rem">' + r.anee.notes.map((t) => '<li>' + esc(t) + '</li>').join('') + '</ul>' : '') + '</section>');
        out.push('<p class="cx-fine">' + I.warn + '<span>The product label always wins over this check. Wear gloves and a mask, and never mix concentrates together before the water.</span></p>');
        out.push('<div class="cx-foot"><button type="button" data-fhm-close>Change the mix</button><button type="button" id="cxAgain">Check again</button></div>');
        resBody.innerHTML = out.join('');
        $('cxResSub').textContent = [r.cropName, r.stageWord].filter(Boolean).join(', ') + ' · ' + r.items.length + ' ' + (r.items.length === 1 ? 'product' : 'products');
        $('cxResPane').scrollTop = 0;
        $('cxAgain')?.addEventListener('click', () => check());
        if ($('cxAsk')) priceAnee(r);
    }
    async function check() {
        if (!st.items.length) return;
        const btn = go;
        btn.disabled = true; btn.classList.add('is-busy');
        try {
            lastBody = caseBody();
            const r = await window.api(D.urls.check, { method: 'POST', body: lastBody });
            drawResult((r.data || {}).result);
            FH.openModal(resDlg);
        } catch (err) { say(err.message || 'Could not connect. Please try again.', 'error'); }
        finally { btn.disabled = st.items.length < 1; btn.classList.remove('is-busy'); }
    }
    go.addEventListener('click', check);

    /* ---- Anee online -------------------------------------------------------------------- */
    async function priceAnee(r) {
        const b = $('cxAskGo');
        try {
            const o = await window.api(D.urls.aneeOptions);
            aneeOpt = o.data || {};
            if (!b || !b.isConnected) return;
            if (!aneeOpt.canUse) {
                b.querySelector('span').textContent = aneeOpt.tierLocked ? 'Unlock Anee' : 'Not available';
                $('cxAskWhy').querySelector('span').textContent = aneeOpt.whyNot || '';
                b.disabled = !aneeOpt.tierLocked;
                if (aneeOpt.tierLocked) b.onclick = () => window.aneeUpgrade?.(aneeOpt.whyNot, 'libreAnee');
                return;
            }
            b.querySelector('span').innerHTML = 'Ask Anee to check online, ' + (window.creditCoin ? window.creditCoin(aneeOpt.price + ' credits') : esc(aneeOpt.price + ' credits'));
            b.disabled = false;
            if (!aneeOpt.unlimited) $('cxAskWhy').querySelector('span').textContent = 'You have ' + Math.floor(aneeOpt.balance) + ' credits. Nothing is charged if she cannot finish.';
            b.onclick = () => runAnee(aneeOpt.price);
        } catch (err) { if (b && b.isConnected) { b.querySelector('span').textContent = 'Could not check the price'; } }
    }
    async function runAnee(price) {
        const body = { ...(lastBody || caseBody()), quote: price };
        await FH.closeModal(resDlg);
        window.aneeWait.show({ title: 'Anee is checking online…', lines: ['Reading the product labels…', 'Looking up the registrations…', 'Checking how these mix…'], sub: 'This takes a minute or two.' });
        try {
            const res = await window.api(D.urls.anee, { method: 'POST', body });
            let data = res.data || {};
            if (data.pending) data = await window.aneeWait.poll({ id: data.id, job: (id) => D.urls.job + '/' + id, phases: window.aneeWait.phases.research, every: 3000 });
            await window.aneeWait.done({ title: 'Done!', line: (data.charged || 0) + ' credits used.' });
            const rep = data.report || {};
            // Products she found are in the book now: the mix points at them.
            (rep.refs || []).forEach((ref, i) => { if (st.items[i] && ref && ref !== st.items[i].ref && ref.startsWith('p:')) { st.items[i].ref = ref; st.items[i].id = Number(ref.slice(2)); st.items[i].anee = true; } });
            keep(); paintItems(false);
            drawResult({ ...rep.result, anee: rep.anee });
            FH.openModal(resDlg);
        } catch (err) {
            window.aneeWait.fail && window.aneeWait.fail();
            say(err.message, 'error');
            if (last) { drawResult(last); FH.openModal(resDlg); }
        }
    }

    window.CX_JAR = @json(\App\Support\CompatBook::rules()['jar'] ?? []);
    paintItems(false);
})();
</script>
@endpush
