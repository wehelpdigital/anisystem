{{-- THE COMPARISON, DRAWN (2026-09-25).

     One partial, one API, used by the Compare page for a fresh comparison
     and for every saved one:

         window.cmpResult.draw(host, data, opts) -> draws into host
             data -> the server's payload {id, title, description, report,
                     credits, when, mine}; report is version 2 (an older
                     row arrives already read into it)
             opts -> { face, anee, canAddAnee, price, onAddAnee(data) }
         window.cmpResult.play(host)             -> runs the entrance once
                                                    the host is on screen

     What it draws, top to bottom: a dark green hero with the two sources
     as cards (season, date, lot, who saved it) and the tally of who leads;
     "At a glance" -- one card per figure, A and B side by side with a bar
     each, the "better" mark and the difference said in words; Anee's read
     as its own section (or the door to ask for it); the breakdowns as
     paired bars; the facts in words; a protocol's steps day by day; and
     the two reports as they were saved, folded. A = green, B = amber,
     everywhere. --}}
@once
<style>
    .cx { display: grid; gap: 1rem; }
    .cx > * { min-width: 0; }
    .cx-host { min-width: 0; }
    /* The view is a reading column (42rem); a comparison is two columns
       and earns a little more room on a wide screen. */
    @media (min-width: 900px) { .va-view-body:has(> #rvBody > .cx-host) { max-width: 56rem; } }

    /* ---- the entrance: sections rise in turn, bars grow ---- */
    .cx-rise { opacity: 0; transform: translateY(10px);
        transition: opacity .45s cubic-bezier(.22,1,.36,1), transform .45s cubic-bezier(.22,1,.36,1);
        transition-delay: calc(var(--i, 0) * 70ms); }
    .cx.is-drawn .cx-rise { opacity: 1; transform: none; }
    .cx-bar i, .cx-gb i, .cx-tally i { width: 0; transition: width .8s cubic-bezier(.22,1,.36,1); transition-delay: calc(var(--i, 0) * 70ms + 120ms); }
    .cx.is-drawn .cx-bar i, .cx.is-drawn .cx-gb i, .cx.is-drawn .cx-tally i { width: var(--w, 0%); }

    /* ---- the letters: A green, B amber ---- */
    .cx-letter { flex: none; display: inline-flex; align-items: center; justify-content: center; width: 1.6rem; height: 1.6rem; border-radius: .55rem;
        font-weight: 900; font-size: .82rem; line-height: 1; }
    .cx-letter.is-a { background: #4a7c2a; color: #fff; }
    .cx-letter.is-b { background: #c2620c; color: #fff; }
    .cx-letter.is-sm { width: 1.15rem; height: 1.15rem; border-radius: .4rem; font-size: .62rem; }

    /* ---- the hero ---- */
    .cx-hero { position: relative; overflow: hidden; border-radius: 1.35rem; padding: 1.15rem 1.1rem 1rem; color: #fff;
        background: radial-gradient(120% 90% at 100% 0%, rgb(143 201 106 / .30), transparent 55%),
                    radial-gradient(90% 80% at 0% 100%, rgb(240 180 41 / .16), transparent 60%),
                    linear-gradient(140deg, #1c3510 0%, #2d5016 46%, #3d6823 100%);
        box-shadow: 0 18px 36px -24px rgb(29 53 16 / .9); }
    .cx-hero::after { content: ''; position: absolute; inset: 0; pointer-events: none;
        background: repeating-linear-gradient(115deg, rgb(255 255 255 / .035) 0 2px, transparent 2px 14px); }
    .cx-hero > * { position: relative; z-index: 1; }
    .cx-kick { display: flex; flex-wrap: wrap; gap: .35rem; align-items: center; }
    .cx-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .22rem .6rem .22rem .3rem; border-radius: 999px; font-size: .68rem; font-weight: 800;
        letter-spacing: .05em; text-transform: uppercase; background: rgb(255 255 255 / .14); border: 1px solid rgb(255 255 255 / .16); }
    .cx-chip img { width: 1.15rem; height: 1.15rem; border-radius: 999px; object-fit: contain; background: rgb(255 255 255 / .9); padding: 1px; }
    .cx-chip.is-anee img { object-fit: cover; padding: 0; }
    .cx-chip.is-anee { background: rgb(240 180 41 / .22); border-color: rgb(240 180 41 / .45); }
    .cx-title { font-family: var(--font-heading); font-size: 1.28rem; font-weight: 800; line-height: 1.25; margin-top: .6rem; overflow-wrap: anywhere; }
    .cx-desc { font-size: .8rem; opacity: .85; line-height: 1.5; margin-top: .3rem; }
    .cx-sides { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: .55rem; margin-top: .9rem; position: relative; }
    .cx-side { border-radius: 1rem; padding: .7rem .7rem .65rem; background: rgb(255 255 255 / .1); border: 1px solid rgb(255 255 255 / .16);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px); min-width: 0; }
    .cx-side.is-a { box-shadow: inset 0 3px 0 #8fc96a; }
    .cx-side.is-b { box-shadow: inset 0 3px 0 #f5b041; }
    .cx-side-top { display: flex; align-items: center; gap: .4rem; }
    .cx-side-top small { font-size: .62rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; opacity: .8; }
    .cx-side .cx-letter.is-a { background: #8fc96a; color: #16270b; }
    .cx-side .cx-letter.is-b { background: #f5b041; color: #3a2503; }
    .cx-side-t { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; font-size: .84rem; font-weight: 800; line-height: 1.3; margin-top: .45rem; overflow-wrap: anywhere; }
    .cx-badges { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .5rem; }
    .cx-badge { display: inline-flex; align-items: center; gap: .28rem; max-width: 100%; padding: .16rem .5rem; border-radius: 999px; font-size: .66rem; font-weight: 700;
        background: rgb(0 0 0 / .18); border: 1px solid rgb(255 255 255 / .12); }
    .cx-badge span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cx-badge svg { flex: none; width: .78rem; height: .78rem; opacity: .85; }
    .cx-badge .e { flex: none; font-size: .78rem; line-height: 1; }
    .cx-badge.is-season { background: rgb(255 255 255 / .92); color: #1f3a10; border-color: transparent; }
    .cx-badge.is-season svg { opacity: 1; color: #4a7c2a; }
    .cx-vs { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); width: 2rem; height: 2rem; border-radius: 999px; display: grid; place-items: center;
        font-size: .64rem; font-weight: 900; letter-spacing: .04em; color: #1f3a10; background: #f4f9ee; box-shadow: 0 0 0 3px rgb(29 53 16 / .9), 0 6px 14px -6px rgb(0 0 0 / .6); }
    .cx-tally { margin-top: .85rem; }
    .cx-tally-bar { display: flex; gap: 3px; height: 8px; border-radius: 999px; overflow: hidden; background: rgb(255 255 255 / .14); }
    .cx-tally i { display: block; height: 100%; border-radius: 999px; }
    .cx-tally i.a { background: linear-gradient(90deg, #a9d383, #6b9f3d); }
    .cx-tally i.e { background: rgb(255 255 255 / .45); }
    .cx-tally i.b { background: linear-gradient(90deg, #f5b041, #fbbf24); }
    .cx-tally p { display: flex; flex-wrap: wrap; gap: .3rem .9rem; margin-top: .45rem; font-size: .76rem; opacity: .95; }
    .cx-tally p b { font-weight: 900; font-variant-numeric: tabular-nums; }
    .cx-tally p .dot { display: inline-block; width: .55rem; height: .55rem; border-radius: 999px; margin-right: .3rem; vertical-align: 0; }
    html.dark .cx-hero { background: radial-gradient(120% 90% at 100% 0%, rgb(143 201 106 / .18), transparent 55%),
                    radial-gradient(90% 80% at 0% 100%, rgb(240 180 41 / .10), transparent 60%),
                    linear-gradient(140deg, #12240a 0%, #1f3a10 50%, #2d5016 100%); box-shadow: none; border: 1px solid #2b3a1c; }
    html.dark .cx-vs { background: #e8efe1; box-shadow: 0 0 0 3px #16270b; }

    /* ---- section heads ---- */
    .cx-h { display: flex; align-items: center; gap: .55rem; margin: 0 0 .6rem; }
    .cx-h-ico { flex: none; width: 1.9rem; height: 1.9rem; border-radius: .65rem; display: inline-flex; align-items: center; justify-content: center;
        background: var(--color-brand-50); color: var(--color-brand-700); }
    .cx-h-ico svg { width: 1.05rem; height: 1.05rem; }
    .cx-h b { display: block; font-family: var(--font-heading); font-size: 1rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.2; }
    .cx-h small { display: block; font-size: .72rem; color: var(--color-gray-500); margin-top: .05rem; }
    html.dark .cx-h-ico { background: #22301a; color: #a5c97e; }
    html.dark .cx-h b { color: #e8efe1; }

    /* ---- at a glance: one card per figure ---- */
    .cx-metrics { display: grid; grid-template-columns: minmax(0, 1fr); gap: .6rem; }
    @media (min-width: 720px) {
        .cx-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        /* An odd figure out spans the row rather than leaving a hole. */
        .cx-metrics > .cx-m:last-child:nth-child(odd) { grid-column: 1 / -1; }
    }
    .cx-m { border-radius: 1.05rem; border: 1px solid var(--color-gray-200); background: var(--color-white); padding: .8rem .8rem .7rem; min-width: 0;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .cx-m:hover { transform: translateY(-1px); box-shadow: 0 10px 22px -18px rgb(29 53 16 / .5); }
    .cx-m-h { display: flex; align-items: center; gap: .55rem; }
    .cx-m-ico { flex: none; width: 1.95rem; height: 1.95rem; border-radius: .65rem; display: inline-flex; align-items: center; justify-content: center; background: var(--color-brand-50); color: var(--color-brand-700); }
    .cx-m-ico svg { width: 1.05rem; height: 1.05rem; }
    .cx-m-h b { display: block; font-size: .88rem; font-weight: 800; color: var(--color-gray-900); line-height: 1.25; }
    .cx-m-h small { display: block; font-size: .68rem; color: var(--color-gray-500); margin-top: .05rem; }
    .cx-m-cols { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: .45rem; margin-top: .65rem; }
    .cx-m-col { position: relative; min-width: 0; border-radius: .8rem; padding: .5rem .6rem .55rem .7rem; background: var(--color-gray-50); border: 1px solid transparent;
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .cx-m-col::before { content: ''; position: absolute; left: 0; top: .55rem; bottom: .55rem; width: 3px; border-radius: 0 3px 3px 0; }
    .cx-m-col.is-a::before { background: #6b9f3d; }
    .cx-m-col.is-b::before { background: #d97706; }
    .cx-m-col.is-win { background: #f1f8ea; border-color: #cfe3bd; }
    .cx-m-l { display: flex; align-items: center; gap: .3rem; font-size: .62rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--color-gray-500); min-height: 1.1rem; }
    .cx-win { margin-left: auto; display: inline-flex; align-items: center; gap: .18rem; padding: .1rem .42rem .1rem .3rem; border-radius: 999px;
        background: #4a7c2a; color: #fff; font-size: .58rem; letter-spacing: .05em; white-space: nowrap; }
    .cx-win svg { width: .7rem; height: .7rem; }
    .cx-m-v { display: block; font-size: 1.15rem; font-weight: 800; color: var(--color-gray-900); font-variant-numeric: tabular-nums; line-height: 1.2; margin-top: .2rem; overflow-wrap: anywhere; }
    .cx-m-v small { font-size: .66rem; font-weight: 700; color: var(--color-gray-500); margin-left: .1rem; }
    .cx-m-v.is-neg { color: #b91c1c; }
    .cx-m-v.is-none { font-size: .86rem; font-weight: 700; color: var(--color-gray-400); }
    .cx-word { display: inline-flex; align-items: center; gap: .3rem; margin-top: .3rem; padding: .2rem .55rem; border-radius: 999px; font-size: .78rem; font-weight: 800; }
    .cx-word.is-good { background: #e7f4dc; color: #2f5219; }
    .cx-word.is-watch { background: #fdf1d8; color: #92400e; }
    .cx-word.is-bad { background: #fde8e8; color: #991b1b; }
    .cx-bar { display: block; height: 7px; border-radius: 999px; background: var(--color-gray-200); margin-top: .45rem; overflow: hidden; }
    .cx-bar i { display: block; height: 100%; border-radius: 999px; }
    .is-a > .cx-bar i, .cx-gb.is-a i { background: linear-gradient(90deg, #a9d383, #4a7c2a); }
    .is-b > .cx-bar i, .cx-gb.is-b i { background: linear-gradient(90deg, #fbc56a, #c2620c); }
    .cx-bar i.is-neg, .cx-gb i.is-neg { background: linear-gradient(90deg, #fca5a5, #b91c1c) !important; }
    .cx-m-d { display: flex; align-items: center; gap: .4rem; margin-top: .6rem; padding-top: .55rem; border-top: 1px dashed var(--color-gray-200); font-size: .76rem; color: var(--color-gray-600); line-height: 1.35; }
    .cx-m-d > svg { flex: none; width: 1rem; height: 1rem; color: var(--color-gray-400); }
    .cx-m-d span { min-width: 0; }
    .cx-pct { margin-left: auto; flex: none; padding: .12rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 800; font-variant-numeric: tabular-nums;
        background: var(--color-gray-100); color: var(--color-gray-600); white-space: nowrap; }
    .cx-m-d.is-good > svg { color: #4a7c2a; }
    .cx-m-d.is-good .cx-pct { background: #e7f4dc; color: #2f5219; }
    .cx-m-d.is-bad > svg { color: #b91c1c; }
    .cx-m-d.is-bad .cx-pct { background: #fde8e8; color: #991b1b; }
    html.dark .cx-m { background: #151b12; border-color: #2b3a1c; }
    html.dark .cx-m:hover { box-shadow: 0 10px 22px -18px rgb(0 0 0 / .9); }
    html.dark .cx-m-ico { background: #22301a; color: #a5c97e; }
    html.dark .cx-m-h b, html.dark .cx-m-v { color: #e8efe1; }
    html.dark .cx-m-col { background: #1b2316; }
    html.dark .cx-m-col.is-win { background: #1f2d15; border-color: #3f5a2a; }
    html.dark .cx-m-v.is-neg { color: #fca5a5; }
    html.dark .cx-bar { background: #2b3a1c; }
    html.dark .cx-m-d { border-color: #2b3a1c; color: #b7c2ad; }
    html.dark .cx-pct { background: #22301a; color: #b7c2ad; }
    html.dark .cx-m-d.is-good .cx-pct, html.dark .cx-word.is-good { background: #22381b; color: #cfe6b8; }
    html.dark .cx-m-d.is-bad .cx-pct, html.dark .cx-word.is-bad { background: #3a1616; color: #fca5a5; }
    html.dark .cx-word.is-watch { background: #3a2a0f; color: #f5c77a; }

    /* ---- cards for breakdowns, facts, days ---- */
    .cx-card { border-radius: 1.05rem; border: 1px solid var(--color-gray-200); background: var(--color-white); padding: .85rem .9rem; min-width: 0; }
    .cx-card + .cx-card { margin-top: .6rem; }
    .cx-card-h { display: flex; align-items: baseline; gap: .5rem; flex-wrap: wrap; margin-bottom: .55rem; }
    .cx-card-h b { font-size: .9rem; font-weight: 800; color: var(--color-gray-900); }
    .cx-card-h small { font-size: .7rem; color: var(--color-gray-500); }
    .cx-legend { display: flex; flex-wrap: wrap; gap: .3rem .9rem; margin-left: auto; font-size: .68rem; font-weight: 800; color: var(--color-gray-500); }
    .cx-legend span { display: inline-flex; align-items: center; gap: .3rem; }
    .cx-legend span::before { content: ''; width: .6rem; height: .6rem; border-radius: .2rem; }
    .cx-legend .is-a::before { background: #6b9f3d; }
    .cx-legend .is-b::before { background: #d97706; }
    .cx-g-row { padding: .55rem 0; border-top: 1px solid var(--color-gray-100); }
    .cx-g-row:first-of-type { border-top: 0; padding-top: .1rem; }
    .cx-g-h { display: flex; align-items: baseline; gap: .5rem; font-size: .8rem; }
    .cx-g-h b { min-width: 0; font-weight: 700; color: var(--color-gray-800); overflow-wrap: anywhere; }
    .cx-g-h i { font-style: normal; font-size: .68rem; color: var(--color-gray-400); }
    .cx-g-d { margin-left: auto; flex: none; font-size: .7rem; font-weight: 800; font-variant-numeric: tabular-nums; color: var(--color-gray-500); white-space: nowrap; }
    .cx-g-note { display: block; font-size: .66rem; color: var(--color-gray-400); margin-top: .05rem; }
    .cx-gb { display: grid; grid-template-columns: auto minmax(0, 1fr) 5.4rem; align-items: center; gap: .45rem; margin-top: .3rem; }
    .cx-gb .t { display: block; height: 9px; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .cx-gb i { display: block; height: 100%; border-radius: 999px; }
    .cx-gb b { font-size: .74rem; font-weight: 700; text-align: right; color: var(--color-gray-700); font-variant-numeric: tabular-nums; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cx-gb b.is-neg { color: #b91c1c; }
    html.dark .cx-card { background: #151b12; border-color: #2b3a1c; }
    html.dark .cx-card-h b, html.dark .cx-g-h b { color: #e8efe1; }
    html.dark .cx-g-row { border-color: #222b1a; }
    html.dark .cx-gb .t { background: #22301a; }
    html.dark .cx-gb b { color: #cbd5c0; }
    html.dark .cx-gb b.is-neg { color: #fca5a5; }

    /* facts in words */
    .cx-f-row { padding: .6rem 0; border-top: 1px solid var(--color-gray-100); }
    .cx-f-row:first-of-type { border-top: 0; padding-top: .1rem; }
    .cx-f-l { display: flex; align-items: center; gap: .4rem; font-size: .64rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; color: var(--color-gray-500); }
    .cx-diff { text-transform: none; letter-spacing: 0; font-size: .64rem; font-weight: 800; padding: .06rem .45rem; border-radius: 999px; background: #fdf1d8; color: #92400e; }
    .cx-same { text-transform: none; letter-spacing: 0; font-size: .64rem; font-weight: 800; padding: .06rem .45rem; border-radius: 999px; background: #e7f4dc; color: #2f5219; }
    .cx-f-cols { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: .45rem; margin-top: .35rem; }
    .cx-f-cols.is-one { grid-template-columns: minmax(0, 1fr); }
    .cx-f-cols > div { position: relative; min-width: 0; font-size: .8rem; line-height: 1.45; color: var(--color-gray-800); padding: .42rem .6rem .42rem .7rem;
        border-radius: .65rem; background: var(--color-gray-50); white-space: pre-line; overflow-wrap: anywhere; }
    .cx-f-cols > div::before { content: ''; position: absolute; left: 0; top: .4rem; bottom: .4rem; width: 3px; border-radius: 0 3px 3px 0; background: var(--color-gray-300); }
    .cx-f-cols > .is-a::before { background: #6b9f3d; }
    .cx-f-cols > .is-b::before { background: #d97706; }
    .cx-f-cols > div.is-none { color: var(--color-gray-400); font-style: italic; }
    .cx-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .4rem; }
    .cx-chip-f { display: inline-flex; align-items: baseline; gap: .35rem; max-width: 100%; padding: .28rem .6rem; border-radius: 999px; font-size: .76rem;
        color: var(--color-gray-700); background: var(--color-gray-50); border: 1px solid var(--color-gray-200); overflow-wrap: anywhere; }
    .cx-chip-f b { font-size: .62rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--color-gray-500); white-space: nowrap; }
    html.dark .cx-f-row { border-color: #222b1a; }
    html.dark .cx-chip-f { background: #1b2316; border-color: #2b3a1c; color: #d5e3c5; }
    html.dark .cx-f-cols > div { background: #1b2316; color: #d5e3c5; }
    html.dark .cx-diff { background: #3a2a0f; color: #f5c77a; }
    html.dark .cx-same { background: #22381b; color: #cfe6b8; }

    /* ---- Anee's read ---- */
    .cx-anee { position: relative; overflow: hidden; border-radius: 1.3rem; padding: 1.15rem 1.05rem 1.05rem;
        background: linear-gradient(165deg, #f5faef 0%, #fbfaf1 55%, #fdf6e8 100%); border: 1px solid #d9e8c8; }
    .cx-anee::before { content: ''; position: absolute; left: 0; right: 0; top: 0; height: 4px; background: linear-gradient(90deg, #2d5016, #6b9f3d 40%, #f0b429 78%, #d97706); }
    .cx-anee-head { display: flex; gap: .8rem; align-items: flex-start; }
    .cx-anee-face { flex: none; width: 3.1rem; height: 3.1rem; border-radius: 999px; padding: 2.5px; background: conic-gradient(from 200deg, #4a7c2a, #a9d383, #f0b429, #4a7c2a); }
    .cx-anee-face img { width: 100%; height: 100%; border-radius: 999px; object-fit: cover; border: 2px solid #fff; display: block; }
    .cx-anee-k { display: inline-flex; align-items: center; gap: .3rem; font-size: .66rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #4a7c2a; }
    .cx-anee-k svg { width: .85rem; height: .85rem; color: #d19a1a; }
    .cx-anee-hl { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 800; line-height: 1.35; color: #1f3a10; margin-top: .15rem; }
    .cx-anee-verdict { font-size: .88rem; line-height: 1.7; color: #3f4a37; margin-top: .75rem; }
    .cx-anee-overall { display: flex; gap: .7rem; align-items: center; margin-top: .85rem; padding: .7rem .8rem; border-radius: .95rem; background: var(--color-white); border: 1px solid #e3ecd7; }
    .cx-anee-overall .cup { flex: none; width: 2.3rem; height: 2.3rem; border-radius: .75rem; display: inline-flex; align-items: center; justify-content: center; background: #fff3d6; color: #b45309; }
    .cx-anee-overall .cup svg { width: 1.2rem; height: 1.2rem; }
    .cx-anee-overall b { display: flex; align-items: center; gap: .35rem; font-size: .86rem; font-weight: 800; color: #1f3a10; }
    .cx-anee-overall p { font-size: .8rem; line-height: 1.5; color: #4a5a3c; margin-top: .1rem; }
    .cx-anee h4 { display: flex; align-items: center; gap: .4rem; font-size: .7rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #3d6823; margin: 1.05rem 0 .5rem; }
    .cx-anee h4 svg { width: .95rem; height: .95rem; }
    .cx-anee h4 small { text-transform: none; letter-spacing: 0; font-weight: 700; color: #6b7a5e; font-size: .7rem; }
    .cx-changes { display: grid; gap: .45rem; }
    .cx-changes li { display: flex; gap: .6rem; align-items: flex-start; font-size: .84rem; line-height: 1.55; color: #334127; padding: .55rem .7rem; border-radius: .85rem;
        background: rgb(255 255 255 / .8); border: 1px solid #e3ecd7; }
    .cx-changes .n { flex: none; width: 1.4rem; height: 1.4rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 900;
        background: #2d5016; color: #fff; margin-top: .05rem; }
    .cx-anee-two { display: grid; grid-template-columns: minmax(0, 1fr); gap: .6rem; }
    @media (min-width: 560px) { .cx-anee-two { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .cx-anee-col { border-radius: 1rem; padding: .75rem .8rem .8rem; background: var(--color-white); border: 1px solid; min-width: 0; }
    .cx-anee-col.is-a { border-color: #cfe3bd; }
    .cx-anee-col.is-b { border-color: #f1d6a2; }
    .cx-anee-col h5 { display: flex; align-items: center; gap: .45rem; font-size: .8rem; font-weight: 800; margin-bottom: .5rem; line-height: 1.25; }
    .cx-anee-col.is-a h5 { color: #2f5219; }
    .cx-anee-col.is-b h5 { color: #92400e; }
    .cx-anee-col h5 small { display: block; font-size: .66rem; font-weight: 700; opacity: .75; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cx-anee-col h5 span:last-child { min-width: 0; }
    .cx-anee-col li { display: flex; gap: .45rem; font-size: .82rem; line-height: 1.5; color: #3f4a37; }
    .cx-anee-col li + li { margin-top: .45rem; }
    .cx-anee-col li svg { flex: none; width: 1rem; height: 1rem; margin-top: .15rem; }
    .cx-anee-col.is-a li svg { color: #4a7c2a; }
    .cx-anee-col.is-b li svg { color: #c2620c; }
    .cx-carry { margin-top: 1.05rem; border-radius: 1rem; padding: .8rem .85rem .85rem; color: #fff;
        background: radial-gradient(100% 120% at 100% 0%, rgb(240 180 41 / .22), transparent 60%), linear-gradient(140deg, #24400f, #3d6823); }
    .cx-carry h4 { color: #d9f0c4; margin-top: 0; }
    .cx-carry li { display: flex; gap: .55rem; font-size: .85rem; line-height: 1.55; }
    .cx-carry li + li { margin-top: .5rem; }
    .cx-carry li svg { flex: none; width: 1rem; height: 1rem; margin-top: .2rem; color: #f5c451; }
    /* The door, when she has not read it yet. */
    .cx-anee.is-cta { display: grid; gap: .8rem; }
    .cx-anee.is-cta p { font-size: .84rem; line-height: 1.55; color: #3f4a37; margin-top: .3rem; }
    .cx-anee-go { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; padding: .8rem 1rem; border-radius: .95rem; font-weight: 800; font-size: .9rem; color: #fff;
        background: linear-gradient(115deg, #7bb24a, #4a7c2a 30%, #3d6823 55%, #6b9f3d 80%, #8fc96a); background-size: 260% 100%; animation: cxTide 5.5s ease-in-out infinite alternate;
        box-shadow: 0 10px 22px -12px rgb(61 104 35 / .65); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .cx-anee-go:hover { transform: translateY(-1px); }
    .cx-anee-go:disabled { opacity: .55; animation: none; }
    .cx-anee-go .credit-coin { background: rgb(255 255 255 / .92); }
    .cx-anee-go img { width: 1.4rem; height: 1.4rem; border-radius: 999px; object-fit: cover; border: 1.5px solid rgb(255 255 255 / .8); }
    @keyframes cxTide { from { background-position: 0% 50%; } to { background-position: 100% 50%; } }
    .cx-anee-note { font-size: .74rem; color: #6b7a5e; }
    html.dark .cx-anee { background: linear-gradient(165deg, #17200f 0%, #1a1d10 60%, #221c10 100%); border-color: #2f3f1f; }
    html.dark .cx-anee-face img { border-color: #151b12; }
    html.dark .cx-anee-k { color: #a5c97e; }
    html.dark .cx-anee-hl, html.dark .cx-anee-overall b { color: #e8efe1; }
    html.dark .cx-anee-verdict, html.dark .cx-anee.is-cta p { color: #b7c2ad; }
    html.dark .cx-anee-overall { background: #151b12; border-color: #2b3a1c; }
    html.dark .cx-anee-overall p { color: #a5b89a; }
    html.dark .cx-anee-overall .cup { background: #3a2a0f; color: #f5c77a; }
    html.dark .cx-anee h4 { color: #a5c97e; }
    html.dark .cx-anee h4 small { color: #93a684; }
    html.dark .cx-changes li { background: rgb(255 255 255 / .03); border-color: #2b3a1c; color: #cbd5c0; }
    html.dark .cx-changes .n { background: #6b9f3d; color: #0d110a; }
    html.dark .cx-anee-col { background: #151b12; }
    html.dark .cx-anee-col.is-a { border-color: #3f5a2a; }
    html.dark .cx-anee-col.is-b { border-color: #5c4418; }
    html.dark .cx-anee-col.is-a h5 { color: #cfe6b8; }
    html.dark .cx-anee-col.is-b h5 { color: #f5c77a; }
    html.dark .cx-anee-col li { color: #cbd5c0; }
    html.dark .cx-anee-col.is-a li svg { color: #8fc96a; }
    html.dark .cx-anee-col.is-b li svg { color: #f5b041; }
    html.dark .cx-carry { background: radial-gradient(100% 120% at 100% 0%, rgb(240 180 41 / .14), transparent 60%), linear-gradient(140deg, #16270b, #2d5016); border: 1px solid #3f5a2a; }
    html.dark .cx-anee-note { color: #93a684; }

    /* ---- a protocol, day by day ---- */
    .cx-tl-row { display: grid; grid-template-columns: minmax(0, 1fr); gap: .35rem; padding: .6rem 0; border-top: 1px solid var(--color-gray-100); }
    .cx-tl-row:first-child { border-top: 0; padding-top: .1rem; }
    .cx-tl-day { display: inline-flex; align-self: start; justify-self: start; padding: .14rem .55rem; border-radius: 999px; font-size: .68rem; font-weight: 800;
        font-variant-numeric: tabular-nums; background: #eef6e6; color: #2f5219; border: 1px solid #cfe3bd; }
    .cx-tl-cols { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: .45rem; }
    .cx-tl-cols > div { position: relative; min-width: 0; padding: .4rem .55rem .45rem .7rem; border-radius: .65rem; background: var(--color-gray-50); }
    .cx-tl-cols > div::before { content: ''; position: absolute; left: 0; top: .4rem; bottom: .4rem; width: 3px; border-radius: 0 3px 3px 0; }
    .cx-tl-cols > .is-a::before { background: #6b9f3d; }
    .cx-tl-cols > .is-b::before { background: #d97706; }
    .cx-tl-cols p { font-size: .78rem; font-weight: 700; color: var(--color-gray-800); line-height: 1.35; overflow-wrap: anywhere; }
    .cx-tl-cols p + p { margin-top: .35rem; }
    .cx-tl-cols small { display: block; font-size: .68rem; font-weight: 600; color: var(--color-gray-500); margin-top: .1rem; line-height: 1.35; }
    .cx-tl-cols .none { color: var(--color-gray-300); font-weight: 800; }
    .cx-tl-more-btn { display: flex; align-items: center; justify-content: center; gap: .35rem; width: 100%; margin-top: .5rem; padding: .55rem; border-radius: .8rem;
        font-size: .8rem; font-weight: 800; color: #3d6823; background: #f3f8ec; border: 1px solid #d9e8c8; transition: background .28s cubic-bezier(.22,1,.36,1); }
    .cx-tl-more-btn:hover { background: #e7f2da; }
    .cx-tl-more-btn svg { width: 1rem; height: 1rem; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .cx-tl-more-btn[aria-expanded="true"] svg { transform: rotate(180deg); }
    html.dark .cx-tl-row { border-color: #222b1a; }
    html.dark .cx-tl-day { background: #22301a; color: #cfe6b8; border-color: #3f5a2a; }
    html.dark .cx-tl-cols > div { background: #1b2316; }
    html.dark .cx-tl-cols p { color: #e8efe1; }
    html.dark .cx-tl-cols .none { color: #4b5a40; }
    html.dark .cx-tl-more-btn { background: #1c2913; border-color: #2f3f1f; color: #a5c97e; }

    /* ---- folds (max-height, never grid rows) ---- */
    .cx-fold-b { max-height: 0; overflow: hidden; opacity: 0; transition: max-height .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .cx-fold.is-open > .cx-fold-b { max-height: none; opacity: 1; }
    .cx-src { display: grid; grid-template-columns: minmax(0, 1fr); gap: .6rem; }
    @media (min-width: 760px) { .cx-src { grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: start; } }
    .cx-src .cx-fold { border-radius: 1rem; border: 1px solid var(--color-gray-200); background: var(--color-white); overflow: hidden; min-width: 0; }
    .cx-fold-h { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left; padding: .7rem .8rem; cursor: pointer; }
    .cx-fold-h .t { flex: 1 1 auto; min-width: 0; }
    .cx-fold-h .t b { display: block; font-size: .84rem; font-weight: 800; color: var(--color-gray-900); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cx-fold-h .t small { display: block; font-size: .7rem; color: var(--color-gray-500); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cx-fold-h > svg { flex: none; width: 1rem; height: 1rem; color: var(--color-gray-400); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .cx-fold.is-open .cx-fold-h > svg { transform: rotate(180deg); }
    .cx-pre { margin: 0; padding: .75rem .85rem .9rem; border-top: 1px solid var(--color-gray-100); max-height: 26rem; overflow: auto; white-space: pre-wrap; overflow-wrap: anywhere;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .7rem; line-height: 1.6; color: var(--color-gray-700); }
    html.dark .cx-src .cx-fold { background: #151b12; border-color: #2b3a1c; }
    html.dark .cx-fold-h .t b { color: #e8efe1; }
    html.dark .cx-pre { border-color: #222b1a; color: #b7c2ad; }

    /* ---- a note, when there is something to say about the sources ---- */
    .cx-note { display: flex; gap: .6rem; align-items: flex-start; padding: .7rem .8rem; border-radius: .95rem; font-size: .8rem; line-height: 1.5;
        background: #fdf8ec; border: 1px solid #f3e3b7; color: #7a5410; }
    .cx-note svg { flex: none; width: 1.05rem; height: 1.05rem; margin-top: .1rem; }
    html.dark .cx-note { background: #241f10; border-color: #43391b; color: #e0b95c; }

    @media (max-width: 420px) {
        .cx-hero { padding: 1rem .85rem .9rem; }
        .cx-title { font-size: 1.12rem; }
        .cx-side { padding: .6rem .55rem .55rem; }
        .cx-m { padding: .7rem .65rem .65rem; }
        .cx-m-v { font-size: 1.02rem; }
        .cx-gb { grid-template-columns: auto minmax(0, 1fr) 4.7rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        .cx-rise, .cx-bar i, .cx-gb i, .cx-tally i, .cx-m, .cx-m-col, .cx-fold-b, .cx-fold-h > svg, .cx-tl-more-btn svg, .cx-anee-go { transition: none !important; animation: none !important; }
        .cx-rise { opacity: 1; transform: none; }
    }
    html.sm-still .cx-rise { opacity: 1; transform: none; transition: none; }
    html.sm-still .cx-bar i, html.sm-still .cx-gb i, html.sm-still .cx-tally i { transition: none; }
    @media print { .cx-anee-go, .cx-tl-more-btn { display: none !important; } .cx-fold-b { max-height: none !important; opacity: 1 !important; } }
</style>

<script>
(() => {
    if (window.cmpResult) return;
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
    const still = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');

    /* ---- icons: drawn, never emoji, so they sit true in their badges ---- */
    const P = {
        coins: '<circle cx="8" cy="8" r="6"/><path d="M18.09 10.37A6 6 0 1 1 10.34 18"/><path d="M7 6h1v4"/><path d="m16.71 13.88.7.71-2.82 2.82"/>',
        people: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        scale: '<path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/><path d="M7 21h10"/><path d="M12 3v18"/><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"/>',
        list: '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        check: '<circle cx="12" cy="12" r="9.5"/><path d="m8.5 12.2 2.4 2.4 4.8-5"/>',
        tick: '<path d="M20 6 9 17l-5-5"/>',
        trend: '<path d="M22 7 13.5 15.5 8.5 10.5 2 17"/><path d="M16 7h6v6"/>',
        percent: '<path d="M19 5 5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
        star: '<path d="M12 2.8l2.83 5.73 6.32.92-4.57 4.46 1.08 6.3L12 17.24l-5.66 2.97 1.08-6.3L2.85 9.45l6.32-.92z"/>',
        flag: '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22v-7"/>',
        alert: '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        clock: '<circle cx="12" cy="12" r="9.5"/><path d="M12 6.5V12l3.5 2"/>',
        box: '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        sack: '<path d="M2 22 16 8"/><path d="M3.47 12.53 5 11l1.53 1.53a3.5 3.5 0 0 1 0 4.94L5 19l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M7.47 8.53 9 7l1.53 1.53a3.5 3.5 0 0 1 0 4.94L9 15l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M11.47 4.53 13 3l1.53 1.53a3.5 3.5 0 0 1 0 4.94L13 11l-1.53-1.53a3.5 3.5 0 0 1 0-4.94Z"/><path d="M20 2h2v2a4 4 0 0 1-4 4h-2V6a4 4 0 0 1 4-4Z"/>',
        sprout: '<path d="M7 20h10"/><path d="M10 20c5.5-2.5.8-6.4 3-10"/><path d="M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z"/><path d="M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"/>',
        calendar: '<rect x="3" y="4.5" width="18" height="17" rx="2.5"/><path d="M16 2.5v4M8 2.5v4M3 10h18"/>',
        user: '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        pin: '<path d="M20 10c0 5-5.54 10.19-7.4 11.8a1 1 0 0 1-1.2 0C9.54 20.19 4 15 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
        filter: '<path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/>',
        grid: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>',
        bars: '<path d="M3 3v18h18"/><path d="M7 16h8M7 11.5h12M7 7h5"/>',
        words: '<path d="M4 7V4h16v3M9 20h6M12 4v16"/>',
        route: '<circle cx="6" cy="19" r="3"/><path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"/><circle cx="18" cy="5" r="3"/>',
        file: '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
        arrow: '<path d="M5 12h14M12 5l7 7-7 7"/>',
        up: '<path d="M12 19V5M5 12l7-7 7 7"/>',
        down: '<path d="M12 5v14M19 12l-7 7-7-7"/>',
        equal: '<path d="M5 9h14M5 15h14"/>',
        dash: '<path d="M5 12h14"/>',
        sparkle: '<path d="M12 3l1.9 5.8L20 10.7l-5.8 1.9L12 18.5l-1.9-5.9L4 10.7l6.1-1.9z"/>',
        cup: '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>',
        chevron: '<path d="M6 9l6 6 6-6"/>',
        bulb: '<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/>',
        swap: '<path d="M16 3h5v5"/><path d="M4 20 21 3"/><path d="M21 16v5h-5"/><path d="M15 15l6 6"/><path d="M4 4l5 5"/>',
        thumb: '<path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/>',
        info: '<circle cx="12" cy="12" r="9.5"/><path d="M12 16v-4.5M12 8h.01"/>',
    };
    const I = (k, cls = '') => `<svg${cls ? ` class="${cls}"` : ''} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${P[k] || P.dash}</svg>`;
    /* The credit coin as a plain span: window.creditCoin() is a link, and a
       link inside a button would leave for My Credits on the tap. */
    const coin = (text) => `<span class="credit-coin"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9.2" fill="#f0b429" stroke="#c98a12" stroke-width="1.6"/><circle cx="12" cy="12" r="5" fill="none" stroke="#c98a12" stroke-width="1.3" opacity=".75"/></svg><b>${esc(text)}</b></span>`;

    /* ---- numbers in the farmer's own money and words ---- */
    const reg = () => window.ANEE_REGION || {};
    const num = (v, d = 0) => Number(v).toLocaleString(reg().locale || 'en-PH', { minimumFractionDigits: d, maximumFractionDigits: d });
    const money = (v, dec) => { const a = Math.abs(v); const d = dec ?? ((a >= 1000 || Number.isInteger(a)) ? 0 : 2); return (v < 0 ? '−' : '') + (reg().symbol || '₱') + num(a, d); };
    /* One figure, one way of writing its money: centavos only when both
       sides are small and one of them carries some. */
    const moneyDec = (vals) => {
        const xs = vals.filter((v) => v !== null && v !== undefined).map(Number);
        return xs.length && xs.every((v) => Math.abs(v) < 1000) && xs.some((v) => !Number.isInteger(v)) ? 2 : 0;
    };
    /* What a count counts, so a difference can say it. */
    const NOUN = { workdays: 'worker-days', assignments: 'assignments', activities: 'activities', entries: 'entries', steps: 'steps',
        crewDays: 'crew-days', materials: 'material lines', skipped: 'unticked activities', overdue: 'overdue activities' };
    const one = (v) => { const r = Math.round(v * 10) / 10; return num(r, Number.isInteger(r) ? 0 : 1); };
    const WORD = { 3: ['On track', 'is-good'], 2: ['Watch', 'is-watch'], 1: ['Rescue', 'is-bad'] };
    function fmt(v, unit, q, dec) {
        if (v === null || v === undefined) return null;
        switch (unit) {
            case 'money': return money(v, dec);
            case 'pct': return one(v) + '%';
            case 'score': return num(Math.round(v));
            case 'days': return num(v) + (Math.abs(v) === 1 ? ' day' : ' days');
            case 'qty': return one(v) + (q ? ' ' + q : '');
            case 'num1': return one(v);
            case 'word': return (WORD[v] || ['—'])[0];
            default: return num(v);
        }
    }
    function gap(d, unit, q, dec) {
        const a = Math.abs(d);
        switch (unit) {
            case 'money': return money(a, dec);
            case 'pct': return one(a) + (a === 1 ? ' point' : ' points');
            case 'score': return num(Math.round(a)) + (Math.round(a) === 1 ? ' point' : ' points');
            case 'days': return num(a) + (a === 1 ? ' day' : ' days');
            case 'qty': return one(a) + (q ? ' ' + q : '');
            case 'num1': return one(a);
            default: return num(a);
        }
    }
    /* Who wins a figure, if the figure has a better side at all. */
    function judge(m) {
        if (m.a === null || m.b === null || m.a === undefined || m.b === undefined) return { win: null, d: null };
        const d = m.b - m.a;
        if (Math.abs(d) < 1e-9) return { win: 'even', d: 0 };
        if (!m.better) return { win: null, d };
        const bWins = m.better === 'higher' ? d > 0 : d < 0;
        return { win: bWins ? 'b' : 'a', d };
    }
    /* The difference, said from A to B -- A is the one on top. */
    function sayDiff(m, j) {
        if (j.d === null) return { text: (m.a === null ? 'Only B' : 'Only A') + ' recorded this', cls: '', icon: 'dash', pct: '' };
        if (j.d === 0) return { text: 'The same in both', cls: '', icon: 'equal', pct: '' };
        const up = j.d > 0;
        let text;
        const noun = NOUN[m.key] ? ' ' + NOUN[m.key] : '';
        if (m.unit === 'word') text = (j.win === 'b' ? 'B' : 'A') + ' stood better';
        else if (m.unit === 'count' || (m.unit === 'num1' && noun)) text = `B has ${gap(j.d, m.unit)} ${up ? 'more' : 'fewer'}${noun} than A`;
        else if (m.unit === 'days') text = `B is ${gap(j.d, m.unit)} ${up ? 'longer' : 'shorter'} than A`;
        else text = `B is ${gap(j.d, m.unit, m.qtyUnit, m.dec)} ${up ? 'higher' : 'lower'} than A`;
        let pct = '';
        if (!['pct', 'score', 'word'].includes(m.unit) && m.a) {
            const p = (j.d / Math.abs(m.a)) * 100;
            pct = (p > 0 ? '+' : '−') + (Math.abs(p) >= 10 ? num(Math.round(Math.abs(p))) : one(Math.abs(p))) + '%';
        }
        const cls = j.win === 'b' ? 'is-good' : (j.win === 'a' ? 'is-bad' : '');
        return { text, cls, icon: m.unit === 'word' ? 'flag' : (up ? 'up' : 'down'), pct };
    }
    const width = (v, max, unit) => {
        if (v === null || v === undefined || Number(v) === 0) return 0;
        if (unit === 'pct' || unit === 'score') return Math.max(0, Math.min(100, Math.abs(v)));
        return max > 0 ? Math.max(2, Math.min(100, Math.abs(v) / max * 100)) : 0;
    };

    /* ================================================================ */

    function hero(rep, data, opts) {
        const tally = { a: 0, b: 0, e: 0 };
        (rep.metrics || []).forEach((m) => { const j = judge(m); if (m.better && j.win) tally[j.win === 'even' ? 'e' : j.win]++; });
        const total = tally.a + tally.b + tally.e;
        const side = (k, s) => {
            s = s || {};
            const badges = [];
            if (s.season) badges.push(`<span class="cx-badge is-season" title="${esc(s.season)}">${s.cropIcon ? `<span class="e">${esc(s.cropIcon)}</span>` : I('sprout')}<span>${esc(s.season)}</span></span>`);
            if (s.when) badges.push(`<span class="cx-badge" title="Saved ${esc(s.when)}">${I('calendar')}<span>${esc(s.when)}</span></span>`);
            if (s.subject) badges.push(`<span class="cx-badge" title="${esc(s.subject)}">${I('pin')}<span>${esc(s.subject)}</span></span>`);
            if (s.filtered) badges.push(`<span class="cx-badge" title="${esc(s.filtered)}">${I('filter')}<span>Filtered</span></span>`);
            if (s.author) badges.push(`<span class="cx-badge" title="Saved by ${esc(s.author)}">${I('user')}<span>${esc(s.author)}</span></span>`);
            if (s.seasonStatus) badges.push(`<span class="cx-badge"><span>${esc(s.seasonStatus)}</span></span>`);
            if (s.gone) badges.push(`<span class="cx-badge"><span>No longer on its shelf</span></span>`);
            return `<div class="cx-side is-${k}">
                <div class="cx-side-top"><span class="cx-letter is-${k}">${k.toUpperCase()}</span><small>Report ${k.toUpperCase()}</small></div>
                <b class="cx-side-t" title="${esc(s.title)}">${esc(s.title || 'A saved report')}</b>
                <div class="cx-badges">${badges.join('')}</div>
            </div>`;
        };
        const icon = (opts.kinds || {})[rep.kind];
        const tallyHtml = total ? `<div class="cx-tally">
                <div class="cx-tally-bar">
                    ${tally.a ? `<i class="a" style="--w:${(tally.a / total * 100).toFixed(1)}%"></i>` : ''}
                    ${tally.e ? `<i class="e" style="--w:${(tally.e / total * 100).toFixed(1)}%"></i>` : ''}
                    ${tally.b ? `<i class="b" style="--w:${(tally.b / total * 100).toFixed(1)}%"></i>` : ''}
                </div>
                <p><span><span class="dot" style="background:#a9d383"></span>A ahead on <b>${tally.a}</b></span><span><span class="dot" style="background:#f5b041"></span>B ahead on <b>${tally.b}</b></span>${tally.e ? `<span><span class="dot" style="background:rgb(255 255 255 / .55)"></span><b>${tally.e}</b> even</span>` : ''}</p>
            </div>` : '';
        return `<header class="cx-hero cx-rise" style="--i:0">
            <div class="cx-kick">
                <span class="cx-chip">${icon ? `<img src="${esc(icon)}" alt="">` : ''}${esc(rep.kindLabel || 'Reports')} · compared</span>
                ${rep.analysis ? `<span class="cx-chip is-anee"><img src="${esc(opts.face)}" alt="">${esc(opts.anee || 'Anee')} read it</span>` : ''}
            </div>
            <h2 class="cx-title">${esc(data.title || 'Comparison')}</h2>
            ${data.description ? `<p class="cx-desc">${esc(data.description)}</p>` : ''}
            <div class="cx-sides">${side('a', rep.a)}${side('b', rep.b)}<span class="cx-vs" aria-hidden="true">VS</span></div>
            ${tallyHtml}
        </header>`;
    }

    function head(icon, title, sub) {
        return `<div class="cx-h"><span class="cx-h-ico">${I(icon)}</span><div><b>${esc(title)}</b>${sub ? `<small>${esc(sub)}</small>` : ''}</div></div>`;
    }

    function metrics(list, i) {
        if (!list || !list.length) return '';
        const cards = list.map((m) => {
            const j = judge(m);
            m.dec = m.unit === 'money' ? moneyDec([m.a, m.b]) : undefined;
            const max = Math.max(Math.abs(m.a || 0), Math.abs(m.b || 0));
            const col = (k) => {
                const v = m[k];
                const win = j.win === k;
                let val;
                if (v === null || v === undefined) val = '<span class="cx-m-v is-none">Not recorded</span>';
                else if (m.unit === 'word') val = `<span class="cx-word ${(WORD[v] || ['', ''])[1]}">${I('flag')}${esc(fmt(v, m.unit))}</span>`;
                else val = `<span class="cx-m-v${v < 0 ? ' is-neg' : ''}">${esc(fmt(v, m.unit, m.qtyUnit, m.dec))}${m.unit === 'score' ? '<small>/100</small>' : ''}</span>`;
                const bar = (m.unit !== 'word' && v !== null && v !== undefined)
                    ? `<span class="cx-bar"><i class="${v < 0 ? 'is-neg' : ''}" style="--w:${width(v, max, m.unit).toFixed(1)}%"></i></span>` : '';
                return `<div class="cx-m-col is-${k}${win ? ' is-win' : ''}">
                    <span class="cx-m-l">${k.toUpperCase()}${win ? `<span class="cx-win">${I('tick')}Better</span>` : ''}</span>
                    ${val}${bar}
                </div>`;
            };
            const s = sayDiff(m, j);
            const hint = m.hint || (m.better === 'lower' ? 'lower is better' : (m.better === 'higher' ? 'higher is better' : ''));
            return `<div class="cx-m">
                <div class="cx-m-h"><span class="cx-m-ico">${I(m.icon)}</span><div><b>${esc(m.label)}${m.qtyUnit ? ' (' + esc(m.qtyUnit) + ')' : ''}</b>${hint ? `<small>${esc(hint)}</small>` : ''}</div></div>
                <div class="cx-m-cols">${col('a')}${col('b')}</div>
                <p class="cx-m-d ${s.cls}">${I(s.icon)}<span>${esc(s.text)}</span>${s.pct ? `<span class="cx-pct">${esc(s.pct)}</span>` : ''}</p>
            </div>`;
        }).join('');
        return `<section class="cx-rise" style="--i:${i}">${head('grid', 'At a glance', 'The figures, side by side — the app’s own arithmetic')}<div class="cx-metrics">${cards}</div></section>`;
    }

    function groups(list, i) {
        if (!list || !list.length) return '';
        const cards = list.map((g) => {
            const max = Math.max(0, ...g.rows.map((r) => Math.max(Math.abs(r.a || 0), Math.abs(r.b || 0))));
            const rows = g.rows.map((r) => {
                const d = (r.b || 0) - (r.a || 0);
                const dTxt = Math.abs(d) < 1e-9 ? 'same' : ((d > 0 ? '+' : '−') + gap(d, g.unit));
                const note = (r.aNote || r.bNote) ? `<span class="cx-g-note">A: ${esc(r.aNote || '—')} · B: ${esc(r.bNote || '—')}</span>` : '';
                const bar = (k) => {
                    const v = r[k] || 0;
                    return `<div class="cx-gb is-${k}"><span class="cx-letter is-${k} is-sm">${k.toUpperCase()}</span><span class="t"><i class="${v < 0 ? 'is-neg' : ''}" style="--w:${width(v, max, g.unit).toFixed(1)}%"></i></span><b class="${v < 0 ? 'is-neg' : ''}">${esc(fmt(v, g.unit))}</b></div>`;
                };
                return `<div class="cx-g-row">
                    <div class="cx-g-h"><div class="min-w-0"><b>${esc(r.label)}</b>${note}</div><span class="cx-g-d">${esc(dTxt)}</span></div>
                    ${bar('a')}${bar('b')}
                </div>`;
            }).join('');
            return `<div class="cx-card">
                <div class="cx-card-h"><b>${esc(g.label)}</b>${g.hint ? `<small>${esc(g.hint)}</small>` : ''}<span class="cx-legend"><span class="is-a">A</span><span class="is-b">B</span></span></div>
                ${rows}
            </div>`;
        }).join('');
        return `<section class="cx-rise" style="--i:${i}">${head('bars', 'Line by line', 'Where the two part ways')}${cards}</section>`;
    }

    function facts(list, i) {
        if (!list || !list.length) return '';
        const cell = (f, k) => {
            const v = f[k];
            return (v === null || v === undefined || v === '') ? `<div class="is-${k} is-none">Not recorded</div>` : `<div class="is-${k}">${esc(v)}</div>`;
        };
        const diff = list.filter((f) => !f.same);
        const same = list.filter((f) => f.same);
        const rows = diff.map((f) => `<div class="cx-f-row">
                <div class="cx-f-l">${esc(f.label)} <span class="cx-diff">Differs</span></div>
                <div class="cx-f-cols">${cell(f, 'a')}${cell(f, 'b')}</div>
            </div>`).join('');
        const chips = same.length ? `<div class="cx-f-row cx-f-same">
                <div class="cx-f-l">Same in both <span class="cx-same">${same.length}</span></div>
                <div class="cx-chips">${same.map((f) => `<span class="cx-chip-f"><b>${esc(f.label)}</b>${esc(f.a ?? f.b ?? '')}</span>`).join('')}</div>
            </div>` : '';
        return `<section class="cx-rise" style="--i:${i}">${head('words', 'In words', diff.length ? 'Where they differ, and what they share' : 'What the two share')}<div class="cx-card">${rows}${chips}</div></section>`;
    }

    function anee(rep, data, opts, i) {
        const an = rep.analysis;
        const name = esc(opts.anee || 'Anee');
        const face = `<span class="cx-anee-face"><img src="${esc(opts.face)}" alt=""></span>`;
        if (!an) {
            // No door when she cannot be asked, or a source has left its shelf.
            if (!opts.canAddAnee || rep.mixed || (rep.a && rep.a.gone) || (rep.b && rep.b.gone)) return '';
            return `<section class="cx-anee is-cta cx-rise" style="--i:${i}">
                <div class="cx-anee-head">${face}
                    <div><span class="cx-anee-k">${I('sparkle')}${name}’s read</span>
                    <h3 class="cx-anee-hl">Want ${name} to read these two?</h3>
                    <p>She reads both, side by side, and tells you what changed, the strengths of each, and what to carry into the next season.</p></div>
                </div>
                <button type="button" class="cx-anee-go" data-cx-add-anee><img src="${esc(opts.face)}" alt="">Add ${name}’s read ${opts.price ? coin(String(opts.price)) : ''}</button>
                <span class="cx-anee-note">It saves as a new comparison with her read${data.mine ? ', and this one leaves the shelf' : ''}.</span>
            </section>`;
        }
        const li = (arr, icon) => (arr || []).map((x) => `<li>${I(icon)}<span>${esc(x)}</span></li>`).join('');
        const sA = rep.a || {};
        const sB = rep.b || {};
        const overall = an.overall && an.overall.pick ? (() => {
            const p = an.overall.pick;
            const who = p === 'A' ? `<span class="cx-letter is-a is-sm">A</span> comes out ahead` : (p === 'B' ? `<span class="cx-letter is-b is-sm">B</span> comes out ahead` : 'Honours even');
            return `<div class="cx-anee-overall"><span class="cup">${I('cup')}</span><div class="min-w-0"><b>${who}</b>${an.overall.why ? `<p>${esc(an.overall.why)}</p>` : ''}</div></div>`;
        })() : '';
        const col = (k, arr, s) => (arr && arr.length) ? `<div class="cx-anee-col is-${k}">
                <h5><span class="cx-letter is-${k} is-sm">${k.toUpperCase()}</span><span>Strengths of ${k.toUpperCase()}${s.season ? `<small>${esc(s.season)}${s.subject ? ' · ' + esc(s.subject) : ''}</small>` : ''}</span></h5>
                <ul>${li(arr, 'thumb')}</ul>
            </div>` : '';
        const two = col('a', an.betterInA, sA) + col('b', an.betterInB, sB);
        return `<section class="cx-anee cx-rise" style="--i:${i}">
            <div class="cx-anee-head">${face}
                <div class="min-w-0"><span class="cx-anee-k">${I('sparkle')}${name}’s read</span>
                <h3 class="cx-anee-hl">${esc(an.headline || '')}</h3></div>
            </div>
            ${an.verdict ? `<p class="cx-anee-verdict">${esc(an.verdict)}</p>` : ''}
            ${overall}
            ${(an.differences || []).length ? `<h4>${I('swap')}What changed</h4><ol class="cx-changes">${an.differences.map((x, n) => `<li><span class="n">${n + 1}</span><span>${esc(x)}</span></li>`).join('')}</ol>` : ''}
            ${two ? `<h4>${I('thumb')}The strengths of each</h4><div class="cx-anee-two">${two}</div>` : ''}
            ${(an.advice || []).length ? `<div class="cx-carry"><h4>${I('bulb')}Carry forward</h4><ul>${li(an.advice, 'arrow')}</ul></div>` : ''}
        </section>`;
    }

    function timeline(rows, rep, i) {
        if (!rows || !rows.length) return '';
        const item = (s) => {
            const meta = [s.time, s.crew ? s.crew + (s.crew === 1 ? ' worker' : ' workers') : null, s.date].filter(Boolean).join(' · ');
            const mats = (s.materials || []).length ? `<small>${(s.materials || []).map(esc).join(', ')}</small>` : '';
            return `<p>${esc(s.title)}${meta ? `<small>${esc(meta)}</small>` : ''}${mats}</p>`;
        };
        const row = (r) => {
            const la = (r.labels || {}).a;
            const lb = (r.labels || {}).b;
            const label = r.day === null ? 'No day count' : ((la && lb && la !== lb) ? `${la} · ${lb}` : (la || lb || ('Day ' + r.day)));
            const cell = (k) => (r[k] || []).length ? `<div class="is-${k}">${r[k].map(item).join('')}</div>` : `<div class="is-${k}"><p class="none">—</p></div>`;
            return `<div class="cx-tl-row"><span class="cx-tl-day">${esc(label)}</span><div class="cx-tl-cols">${cell('a')}${cell('b')}</div></div>`;
        };
        const FIRST = 8;
        const first = rows.slice(0, FIRST).map(row).join('');
        const rest = rows.slice(FIRST);
        return `<section class="cx-rise" style="--i:${i}">${head('route', 'Day by day', 'What each did, on its own day count')}
            <div class="cx-card"><div class="cx-legend" style="margin:0 0 .5rem"><span class="is-a">A</span><span class="is-b">B</span></div>
                <div>${first}</div>
                ${rest.length ? `<div class="cx-fold" data-cx-fold-host><div class="cx-fold-b"><div>${rest.map(row).join('')}</div></div>
                    <button type="button" class="cx-tl-more-btn" data-cx-fold aria-expanded="false"><span>Show all ${rows.length} days</span>${I('chevron')}</button></div>` : ''}
            </div>
        </section>`;
    }

    function sources(rep, i, open) {
        const one = (k) => {
            const s = rep[k] || {};
            const sub = [s.season, s.when].filter(Boolean).join(' · ');
            return `<div class="cx-fold${open ? ' is-open' : ''}" data-cx-fold-host>
                <button type="button" class="cx-fold-h" data-cx-fold aria-expanded="${open ? 'true' : 'false'}">
                    <span class="cx-letter is-${k}">${k.toUpperCase()}</span>
                    <span class="t"><b>${esc(s.title || 'Report ' + k.toUpperCase())}</b>${sub ? `<small>${esc(sub)}</small>` : ''}</span>
                    ${I('chevron')}
                </button>
                <div class="cx-fold-b"><pre class="cx-pre">${esc(s.body || 'Nothing was written down for this one.')}</pre></div>
            </div>`;
        };
        return `<section class="cx-rise" style="--i:${i}">${head('file', 'The two reports, as saved', 'Exactly what each one said when it was compared')}<div class="cx-src">${one('a')}${one('b')}</div></section>`;
    }

    /* ---- folds: the house max-height slide, both ways ---- */
    function toggle(host, btn) {
        const body = host.querySelector(':scope > .cx-fold-b');
        if (!body) return;
        const open = !host.classList.contains('is-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        const label = btn.querySelector('span');
        if (label && btn.classList.contains('cx-tl-more-btn')) label.textContent = open ? 'Show fewer days' : label.dataset.more;
        if (still()) { host.classList.toggle('is-open', open); body.style.maxHeight = open ? 'none' : '0px'; return; }
        if (open) {
            body.style.maxHeight = '0px';
            void body.offsetHeight;
            host.classList.add('is-open');
            body.style.maxHeight = body.scrollHeight + 'px';
            const end = (e) => {
                if (e.target !== body || e.propertyName !== 'max-height') return;
                body.removeEventListener('transitionend', end);
                if (host.classList.contains('is-open')) body.style.maxHeight = 'none';
            };
            body.addEventListener('transitionend', end);
        } else {
            body.style.maxHeight = body.scrollHeight + 'px';
            void body.offsetHeight;
            host.classList.remove('is-open');
            requestAnimationFrame(() => { body.style.maxHeight = '0px'; });
        }
    }

    function draw(host, data, opts = {}) {
        const rep = (data && data.report) || {};
        const hasFigures = (rep.metrics || []).length || (rep.groups || []).length || (rep.facts || []).length;
        let i = 1;
        const parts = [hero(rep, data, opts)];
        const gone = (rep.a && rep.a.gone) || (rep.b && rep.b.gone);
        if (!hasFigures && !gone) {
            const why = rep.mixed
                ? 'This comparison was saved when two different kinds of report could be stacked together — they share no figures to line up, so here are the two as they were written.'
                : rep.legacy
                ? 'This comparison was saved before comparisons lined up their figures, and its reports carry none to read — so here are the two as they were written.'
                : 'These two were saved as text only, so there are no figures to line up — here they are as written.';
            parts.push(`<div class="cx-note cx-rise" style="--i:${i++}">${I('info')}<span>${esc(why)}</span></div>`);
        }
        if (gone) {
            parts.push(`<div class="cx-note cx-rise" style="--i:${i++}">${I('info')}<span>One of the two reports has since left its shelf. What it said is kept below, as it was when compared.</span></div>`);
        }
        parts.push(metrics(rep.metrics, i++));
        parts.push(anee(rep, data, opts, i++));
        parts.push(groups(rep.groups, i++));
        parts.push(facts(rep.facts, i++));
        parts.push(timeline(rep.timeline, rep, i++));
        parts.push(sources(rep, i++, !hasFigures));
        host.classList.add('cx-host');
        host.innerHTML = `<article class="cx">${parts.filter(Boolean).join('')}</article>`;
        host.querySelectorAll('.cx-tl-more-btn span').forEach((s) => { s.dataset.more = s.textContent; });
        host.querySelectorAll('[data-cx-fold]').forEach((btn) => {
            btn.addEventListener('click', () => toggle(btn.closest('[data-cx-fold-host]'), btn));
        });
        const add = host.querySelector('[data-cx-add-anee]');
        if (add && typeof opts.onAddAnee === 'function') {
            add.addEventListener('click', () => opts.onAddAnee(data, add));
        }
    }

    /* The entrance, once the host is on screen: a class flip on a hidden
       node would land with nothing to see. */
    function play(host) {
        const root = host && host.querySelector('.cx');
        if (!root) return;
        root.classList.remove('is-drawn');
        void root.offsetWidth;
        requestAnimationFrame(() => requestAnimationFrame(() => root.classList.add('is-drawn')));
    }

    window.cmpResult = { draw, play };
})();
</script>
@endonce
