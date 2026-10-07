{{-- The catalogue hubs' styles (/weeds, /pests, /diseases): the hero mosaic,
     the group cards, the searchable catalogue and the helper card. Pushed
     into the head by the hub that includes it. --}}
    <style>
        .wk-hero { display: grid; gap: 2rem; align-items: center; }
        @media (min-width: 1024px) { .wk-hero { grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 3rem; } }
        .wk-jump { display: flex; flex-wrap: wrap; gap: .5rem; }
        .wk-jump a { display: inline-flex; align-items: center; gap: .45rem; padding: .55rem .95rem; border-radius: 999px; font-size: .88rem; font-weight: 800;
            color: #2d5016; background: #fff; border: 1px solid #d7e8c2; text-decoration: none; box-shadow: 0 8px 18px -16px rgb(20 33 12 / .6);
            transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), background-color .28s cubic-bezier(.22,1,.36,1); }
        .wk-jump a:hover { transform: translateY(-2px); border-color: #a8cc7e; background: #f9fbf6; }
        .wk-jump svg { width: 1rem; height: 1rem; color: #4a7c2a; }
        .wk-mosaic { position: relative; display: grid; grid-template-columns: 1.15fr 1fr; grid-template-rows: 1fr 1fr; gap: .6rem; height: 19rem; }
        .wk-mosaic figure { position: relative; margin: 0; overflow: hidden; border-radius: 1.2rem; background: #e4efd4; box-shadow: 0 22px 40px -30px rgb(20 33 12 / .7); }
        .wk-mosaic figure:first-child { grid-row: span 2; }
        .wk-mosaic img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s cubic-bezier(.22,1,.36,1); }
        .wk-mosaic figure:hover img { transform: scale(1.04); }
        .wk-mosaic figcaption { position: absolute; left: .6rem; bottom: .6rem; display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .6rem; border-radius: 999px;
            font-size: .72rem; font-weight: 800; color: #fff; background: rgb(15 26 10 / .62); backdrop-filter: blur(4px); }
        .wk-mosaic figcaption i { width: .5rem; height: .5rem; border-radius: 999px; background: hsl(var(--g) 60% 62%); }
        @media (max-width: 639.98px) { .wk-mosaic { height: 15rem; } }

        .wk-sec-h { max-width: 46rem; }
        .wk-sec-h h2 { font-family: var(--font-heading); font-weight: 800; color: #14210c; font-size: clamp(1.6rem, 3.2vw, 2.2rem); line-height: 1.2; text-wrap: balance; }
        .wk-sec-h p { margin-top: .7rem; color: #4b5563; line-height: 1.7; }

        /* The three groups */
        .wk-groups { display: grid; gap: 1rem; }
        @media (min-width: 768px) { .wk-groups { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .wk-group { --g: 98; display: flex; flex-direction: column; gap: .7rem; padding: 1.25rem; border-radius: 1.25rem; background: #fff;
            border: 1px solid hsl(var(--g) 35% 86%); box-shadow: 0 16px 34px -30px rgb(20 33 12 / .6); }
        .wk-group-top { display: flex; align-items: center; gap: .75rem; }
        .wk-gico { flex: none; width: 2.8rem; height: 2.8rem; border-radius: .9rem; display: grid; place-items: center; color: hsl(var(--g) 55% 26%);
            background: linear-gradient(145deg, hsl(var(--g) 60% 95%), hsl(var(--g) 50% 86%)); box-shadow: inset 0 0 0 1px hsl(var(--g) 40% 78%); }
        .wk-gico svg { width: 1.5rem; height: 1.5rem; }
        .wk-group h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.2rem; color: #14210c; }
        .wk-group h3 small { display: block; font-family: inherit; font-size: .78rem; font-weight: 700; color: hsl(var(--g) 40% 35%); }
        .wk-group ul { display: grid; gap: .4rem; font-size: .9rem; line-height: 1.5; color: #374151; }
        .wk-group li { display: flex; gap: .5rem; }
        .wk-group li::before { content: ''; flex: none; width: .4rem; height: .4rem; margin-top: .5rem; border-radius: 999px; background: hsl(var(--g) 50% 45%); }
        .wk-group button { margin-top: auto; align-self: flex-start; display: inline-flex; align-items: center; gap: .35rem; font-size: .86rem; font-weight: 800;
            color: hsl(var(--g) 55% 26%); padding: .45rem .85rem; border-radius: 999px; background: hsl(var(--g) 55% 94%);
            transition: background-color .28s cubic-bezier(.22,1,.36,1); }
        .wk-group button:hover { background: hsl(var(--g) 50% 88%); }
        .wk-group button svg { width: .9rem; height: .9rem; }

        /* The catalogue */
        .wk-tools { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1rem; }
        .wk-search { position: relative; flex: 1 1 18rem; max-width: 26rem; }
        .wk-search svg { position: absolute; left: .85rem; top: 50%; translate: 0 -50%; width: 1.05rem; height: 1.05rem; color: #6b9f3d; pointer-events: none; }
        .wk-search input { width: 100%; padding: .7rem .9rem .7rem 2.5rem; border-radius: 999px; border: 1px solid #d7e8c2; background: #fff; font-size: .95rem; color: #14210c;
            transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
        .wk-search input:focus { outline: none; border-color: #86b556; box-shadow: 0 0 0 4px rgb(134 181 86 / .18); }
        .wk-cats button { display: inline-flex; align-items: center; gap: .4rem; }
        .wk-cats button i { font-style: normal; font-size: .72rem; font-weight: 800; opacity: .7; }
        .wk-count { font-size: .85rem; color: #6b7280; }
        .wk-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 12.75rem), 1fr)); }
        .wk-card { --g: 98; display: flex; flex-direction: column; overflow: hidden; border-radius: 1.1rem; background: #fff; border: 1px solid #e5ebdf; text-decoration: none;
            transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        .wk-card:hover { transform: translateY(-3px); box-shadow: 0 18px 36px -26px rgb(20 33 12 / .55); border-color: hsl(var(--g) 40% 75%); }
        .wk-ph { position: relative; aspect-ratio: 4 / 3; background: radial-gradient(circle at 30% 20%, hsl(var(--g) 45% 92%), hsl(var(--g) 35% 84%)); overflow: hidden; }
        .wk-ph img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s cubic-bezier(.22,1,.36,1); }
        .wk-card:hover .wk-ph img { transform: scale(1.05); }
        .wk-ph .none { position: absolute; inset: 0; display: grid; place-items: center; color: hsl(var(--g) 40% 55%); }
        .wk-ph .none svg { width: 2.6rem; height: 2.6rem; }
        .wk-tag { position: absolute; left: .55rem; top: .55rem; padding: .22rem .55rem; border-radius: 999px; font-size: .66rem; font-weight: 800; letter-spacing: .05em;
            text-transform: uppercase; color: hsl(var(--g) 60% 20%); background: hsl(var(--g) 60% 93% / .94); }
        .wk-in { padding: .8rem .9rem 1rem; display: flex; flex-direction: column; gap: .2rem; flex: 1; }
        .wk-in b { font-family: var(--font-heading); font-size: 1.02rem; line-height: 1.3; color: #14210c; }
        .wk-in em { font-size: .82rem; color: #4b5563; }
        .wk-in p { margin-top: .3rem; font-size: .8rem; line-height: 1.5; color: #6b7280; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .wk-in p span { font-weight: 700; color: #4b5563; }
        /* A phone shows two weeds a row, so the catalogue is not a long scroll. */
        @media (max-width: 639.98px) {
            .wk-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
            .wk-in { padding: .6rem .65rem .75rem; }
            .wk-in b { font-size: .92rem; }
            .wk-in em { font-size: .74rem; }
            .wk-in p { font-size: .72rem; }
            .wk-tag { left: .4rem; top: .4rem; font-size: .6rem; }
        }
        .wk-card.is-out { display: none; }
        .wk-card.is-in { animation: spIn .32s cubic-bezier(.22,1,.36,1) both; }
        /* Under a crop chip, the ones that live on another shelf but attack
           this crop too come after this shelf's own. */
        .wk-card.is-also { order: 1; }
        /* A phone shows the first eight until the reader searches, picks a
           group or asks for all: the helper below is not 40 rows away. */
        .wk-more { display: none; }
        @media (max-width: 639.98px) {
            .wk-grid.is-capped > .wk-card:nth-child(n+9) { display: none; }
            .wk-grid.is-capped + .wk-more { display: flex; }
        }
        .wk-more { margin: 1.1rem auto 0; width: 100%; max-width: 24rem; min-height: 2.9rem; align-items: center; justify-content: center; gap: .45rem;
            padding: .7rem 1.1rem; border-radius: 999px; font-size: .95rem; font-weight: 800; color: #2d5016; background: #fff; border: 1px solid #c9e0ad;
            box-shadow: 0 10px 22px -18px rgb(20 33 12 / .6); transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        .wk-more:hover { background: #f6faf1; border-color: #a8cc7e; }
        .wk-more svg { width: 1rem; height: 1rem; }
        /* The shelf cards: on a phone the whole card is the button, and it sits tighter. */
        .wk-group { position: relative; }
        @media (max-width: 639.98px) {
            .wk-group { padding: 1rem 1.05rem 1.05rem; gap: .55rem; }
            .wk-group button::after { content: ''; position: absolute; inset: 0; border-radius: 1.25rem; }
        }
        /* The guides on a phone: a row each (picture beside the words), not a
           screen tall card each. */
        @media (max-width: 639.98px) {
            .wk-guides { gap: .7rem; }
            .wk-guides .sp-tile { flex-direction: row; }
            .wk-guides .sp-tile img { flex: none; width: 6.6rem; aspect-ratio: auto; height: auto; align-self: stretch; object-fit: cover; }
            .wk-guides .sp-tile .in { padding: .75rem .85rem .8rem; gap: .25rem; min-width: 0; }
            .wk-guides .sp-tile b { font-size: .98rem; }
            .wk-guides .sp-tile p { font-size: .84rem; -webkit-line-clamp: 2; }
            .wk-guides .sp-tile .go { padding-top: .2rem; }
            .wk-guides .sp-tile .cat { font-size: .72rem; }
            /* The group chips are real buttons: a thumb sized row. */
            .wk-cats button { min-height: 2.5rem; padding-left: .9rem; padding-right: .9rem; }
        }
        .wk-empty { display: none; padding: 2rem 1rem; text-align: center; color: #6b7280; border: 1px dashed #d7e8c2; border-radius: 1.1rem; }
        .wk-empty.is-on { display: block; animation: spIn .32s cubic-bezier(.22,1,.36,1) both; }
        .wk-empty a { color: #3d6823; font-weight: 700; }
        .wk-else { margin-bottom: .6rem; }
        .wk-else[hidden] { display: none; }
        .wk-else button { display: inline-flex; align-items: center; min-height: 2.5rem; margin: .4rem .2rem 0; padding: .5rem 1rem; border-radius: 999px; font-weight: 800;
            color: #fff; background: #2d5016; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
        .wk-else button:hover { background: #3d6823; }
        .wk-else:not([hidden]) + .wk-none { display: none; }

        /* The weed control helper */
        .wc-sec { background: linear-gradient(180deg, #f6faf1, #eef5e6); border-top: 1px solid #e4efd4; border-bottom: 1px solid #e4efd4; scroll-margin-top: 5rem; }
        .wc { display: grid; gap: 1.25rem; }
        @media (min-width: 1024px) { .wc { grid-template-columns: minmax(0, 23rem) minmax(0, 1fr); gap: 1.75rem; align-items: start; } }
        .wc-ask { display: grid; gap: 1.1rem; padding: 1.25rem; border-radius: 1.3rem; background: #fff; border: 1px solid #e1edd3; box-shadow: 0 20px 40px -34px rgb(20 33 12 / .6); }
        @media (min-width: 1024px) { .wc-ask { position: sticky; top: 6rem; } }
        .wc-q > b { display: flex; align-items: center; gap: .55rem; font-family: var(--font-heading); font-weight: 800; color: #14210c; }
        .wc-q > b i { font-style: normal; width: 1.6rem; height: 1.6rem; border-radius: 999px; display: grid; place-items: center; font-size: .8rem; color: #fff; background: #4a7c2a; }
        .wc-q > small { display: block; margin: .15rem 0 0 2.15rem; font-size: .8rem; color: #6b7280; }
        .wc-opts { margin-top: .7rem; display: flex; flex-wrap: wrap; gap: .4rem; }
        .wc-opts button { padding: .5rem .8rem; border-radius: .8rem; font-size: .86rem; font-weight: 700; color: #374151; background: #f6f8f3; border: 1px solid #e5ebdf;
            transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
        .wc-opts button small { display: block; font-size: .7rem; font-weight: 600; opacity: .75; }
        .wc-opts button:hover:not([disabled]) { border-color: #a8cc7e; }
        .wc-opts button[aria-checked="true"], .wc-opts button[aria-pressed="true"] { background: #2d5016; border-color: #2d5016; color: #fff; }
        .wc-opts button[disabled] { opacity: .4; cursor: not-allowed; }
        .wc-seg button { flex: 1 1 8rem; text-align: left; }
        .wc-days { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .wc-days button { text-align: center; white-space: nowrap; }
        .wc-gs { display: grid; gap: .45rem; }
        .wc-gs button { --g: 98; display: flex; align-items: center; gap: .7rem; text-align: left; padding: .6rem .75rem; }
        .wc-gs button span.ico { flex: none; width: 2.1rem; height: 2.1rem; border-radius: .7rem; display: grid; place-items: center; color: hsl(var(--g) 55% 26%); background: hsl(var(--g) 50% 90%);
            transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
        .wc-gs button span.ico svg { width: 1.2rem; height: 1.2rem; }
        .wc-gs button[aria-pressed="true"] span.ico { background: rgb(255 255 255 / .16); color: #fff; }
        .wc-gs button .tick { margin-left: auto; flex: none; width: 1.25rem; height: 1.25rem; border-radius: .4rem; border: 2px solid #c9d6bb; display: grid; place-items: center;
            transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
        .wc-gs button .tick svg { width: .8rem; height: .8rem; opacity: 0; transform: scale(.5); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
        .wc-gs button[aria-pressed="true"] .tick { background: #f5c518; border-color: #f5c518; color: #3b2f00; }
        .wc-gs button[aria-pressed="true"] .tick svg { opacity: 1; transform: none; }
        .wc-out { min-width: 0; }
        .wc-card { border-radius: 1.3rem; background: #fff; border: 1px solid #e1edd3; box-shadow: 0 24px 48px -36px rgb(20 33 12 / .65); overflow: hidden; }
        .wc-when { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .8rem; padding: 1rem 1.25rem; color: #e8efe1; background: linear-gradient(135deg, #3d6823, #24400f 80%); }
        .wc-when b { font-family: var(--font-heading); font-size: 1.15rem; color: #fff; }
        .wc-when small { width: 100%; font-size: .82rem; color: #cfe0bd; }
        .wc-timing { padding: .22rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #3b2f00; background: #f5c518; }
        .wc-body { padding: 1.15rem 1.25rem 1.3rem; display: grid; gap: 1.1rem; }
        .wc-body h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.02rem; color: #14210c; }
        .wc-steps { margin-top: .55rem; display: grid; gap: .5rem; counter-reset: wc; }
        .wc-steps li { position: relative; padding-left: 2.1rem; font-size: .95rem; line-height: 1.6; color: #374151; counter-increment: wc; }
        .wc-steps li::before { content: counter(wc); position: absolute; left: 0; top: .1rem; width: 1.45rem; height: 1.45rem; border-radius: 999px; display: grid; place-items: center;
            font-size: .75rem; font-weight: 800; color: #2d5016; background: #e4efd4; }
        .wc-ings { margin-top: .6rem; display: flex; flex-wrap: wrap; gap: .45rem; }
        .wc-ings li { display: inline-flex; align-items: center; gap: .45rem; padding: .42rem .45rem .42rem .75rem; border-radius: .8rem; background: #f6f8f3; border: 1px solid #e5ebdf;
            font-size: .88rem; font-weight: 700; color: #1f2937; }
        .wc-ings li em { font-style: normal; font-size: .68rem; font-weight: 800; padding: .15rem .45rem; border-radius: 999px; color: #2d5016; background: #e4efd4; white-space: nowrap; cursor: help; }
        .wc-split { display: grid; gap: .9rem; }
        @media (min-width: 640px) { .wc-split { grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr)); } }
        .wc-split > div { --g: 98; padding: .8rem; border-radius: 1rem; background: hsl(var(--g) 45% 97%); border: 1px solid hsl(var(--g) 35% 88%); }
        .wc-split > div > b { font-size: .82rem; font-weight: 800; color: hsl(var(--g) 55% 25%); }
        .wc-note { padding: .8rem .95rem; border-radius: .9rem; font-size: .9rem; line-height: 1.55; background: #fff8e6; border: 1px solid #f5d98a; color: #6b4a00; }
        .wc-note.ok { background: #f3f8ec; border-color: #c9e0ad; color: #24400f; }
        .wc-legend { font-size: .8rem; line-height: 1.55; color: #6b7280; }
        .wc-card.is-swap .wc-when, .wc-card.is-swap .wc-body > * { animation: wcIn .34s cubic-bezier(.22,1,.36,1) both; }
        .wc-card.is-swap .wc-body > *:nth-child(2) { animation-delay: .04s; }
        .wc-card.is-swap .wc-body > *:nth-child(3) { animation-delay: .08s; }
        .wc-card.is-swap .wc-body > *:nth-child(4) { animation-delay: .12s; }
        @keyframes wcIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        .wc-fine { margin-top: 1.1rem; display: flex; gap: .6rem; font-size: .85rem; line-height: 1.6; color: #4b5563; }
        .wc-fine svg { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .15rem; color: #c79e00; }
        .wc-fine a { color: #3d6823; font-weight: 700; text-decoration: underline; text-decoration-color: #a8cc7e; text-underline-offset: 3px; }
        /* Below two columns the answer sits under the questions, out of
           sight: this button says what is waiting and takes the reader there. */
        .wc-out { scroll-margin-top: 5rem; }
        .wc-go { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; min-height: 3rem; padding: .7rem 1rem; border-radius: .95rem;
            font-family: var(--font-heading); font-size: 1rem; font-weight: 800; color: #3b2f00; background: #f5c518; box-shadow: 0 12px 24px -18px rgb(59 47 0 / .8);
            transition: background-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
        .wc-go:hover { background: #ffd23f; }
        .wc-go svg { flex: none; width: 1.1rem; height: 1.1rem; }
        .wc-go.is-bump { animation: wcBump .42s cubic-bezier(.22,1,.36,1); }
        @keyframes wcBump { 40% { transform: scale(1.035); } }
        @media (min-width: 1024px) { .wc-go { display: none; } }
        @media (prefers-reduced-motion: reduce) {
            .wk-jump a, .wk-mosaic img, .wk-card, .wk-ph img, .wc-opts button, .wc-gs button .tick, .wc-gs button .tick svg, .wk-more, .wc-go { transition: none; }
            .wk-card.is-in, .wk-empty.is-on, .wc-card.is-swap .wc-when, .wc-card.is-swap .wc-body > *, .wc-go.is-bump { animation: none; }
        }
    </style>
