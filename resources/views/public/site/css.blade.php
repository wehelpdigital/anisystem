{{-- The guides, blog and feature pages (App\Support\SitePages): the article,
     its blocks, the side column and the hubs. Plain CSS over the site's
     tokens, so a block drawn here matches the builder's preview exactly. --}}
@once
@push('head')
<style>
    .sp-hero { background: linear-gradient(180deg, #f3f8ec 0%, #fff 100%); border-bottom: 1px solid #e4efd4; }
    .sp-crumbs { display: flex; flex-wrap: wrap; gap: .35rem; align-items: center; font-size: .8rem; color: #6b7280; }
    .sp-crumbs a { color: #3d6823; font-weight: 600; text-decoration: none; }
    .sp-crumbs a:hover { text-decoration: underline; }
    .sp-crumbs svg { width: .8rem; height: .8rem; color: #a8cc7e; }
    .sp-crumbs .sp-here { display: inline-flex; align-items: center; gap: .35rem; min-width: 0; }
    /* A phone: the trail stops at the section. The page's own name is the
       title right under it, and on a long title it only wrapped and cut off. */
    @media (max-width: 639.98px) {
        .sp-crumbs .sp-here { display: none; }
        .sp-crumbs a { padding: .55rem 0; margin: -.55rem 0; }
    }
    .sp-finder-link { display: inline-flex; align-items: center; min-height: 2.5rem; margin-left: .5rem; font-size: .85rem; font-weight: 800; color: #3d6823; text-decoration: none;
        transition: color .28s cubic-bezier(.22,1,.36,1); }
    .sp-finder-link:hover { color: #2d5016; text-decoration: underline; }
    @media (max-width: 639.98px) { .sp-finder-link { display: flex; margin: .35rem 0 0; } }
    .sp-chip { display: inline-flex; align-items: center; gap: .35rem; font-size: .72rem; font-weight: 800; letter-spacing: .06em;
        text-transform: uppercase; color: #2d5016; background: #e4efd4; border-radius: 999px; padding: .3rem .7rem; }
    .sp-h1 { font-family: var(--font-heading); font-weight: 800; color: #14210c; font-size: clamp(1.9rem, 4.2vw, 2.9rem);
        line-height: 1.15; letter-spacing: -.01em; text-wrap: balance; }
    .sp-lead { font-size: 1.12rem; line-height: 1.7; color: #374151; }
    .sp-meta { display: flex; flex-wrap: wrap; gap: .4rem 1rem; font-size: .8rem; color: #6b7280; }
    .sp-meta span { display: inline-flex; align-items: center; gap: .3rem; }
    .sp-meta svg { width: .95rem; height: .95rem; color: #86b556; }
    .sp-figure { margin: 0; }
    /* "On this page" on a phone: under the title, folded. The side column's
       copy is the desktop's. */
    .sp-mtoc { margin: -.5rem 0 1.75rem; border-radius: 1rem; background: #fff; border: 1px solid #e5ebdf; box-shadow: 0 10px 24px -22px rgb(20 33 12 / .5); }
    .sp-mtoc-h { display: flex; align-items: center; gap: .6rem; width: 100%; padding: .85rem 1rem; text-align: left; font-weight: 800; color: #14210c; }
    .sp-mtoc-h svg { width: 1.15rem; height: 1.15rem; color: #4d7c2a; flex: none; }
    .sp-mtoc-h small { font-weight: 600; font-size: .78rem; color: #6b7280; }
    .sp-mtoc-h i { position: relative; margin-left: auto; width: .85rem; height: .85rem; }
    .sp-mtoc-h i::before, .sp-mtoc-h i::after { content: ""; position: absolute; left: 0; right: 0; top: 50%; height: 2px; margin-top: -1px; border-radius: 2px; background: #3d6823; transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .sp-mtoc-h i::after { transform: rotate(90deg); }
    .sp-mtoc.is-open .sp-mtoc-h i::after { transform: rotate(0); }
    .sp-mtoc-fold { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
    .sp-mtoc.is-open .sp-mtoc-fold { grid-template-rows: 1fr; }
    .sp-mtoc-fold > nav { overflow: hidden; display: grid; padding: 0 .6rem; }
    .sp-mtoc.is-open .sp-mtoc-fold > nav { padding-bottom: .6rem; }
    .sp-mtoc-fold a { padding: .55rem .5rem; border-top: 1px solid #f1f5ec; font-size: .92rem; color: #374151; text-decoration: none; }
    .sp-mtoc-fold a:active { color: #3d6823; }
    @media (min-width: 1024px) { .sp-mtoc { display: none; } }
    /* Below the desktop the side column falls under the article. Its contents
       and its "Keep reading" list are then repeats: the folded contents sit
       under the title, and the same pages come next as cards. */
    @media (max-width: 1023.98px) { .sp-toc-card, .sp-keep { display: none; } }
    @media (prefers-reduced-motion: reduce) { .sp-mtoc-fold, .sp-mtoc-h i::before, .sp-mtoc-h i::after { transition: none; } }
    /* A feature page: its icon, and the screen shown in a frame. */
    .sp-hero.is-feature { background: radial-gradient(60rem 22rem at 85% 0%, hsl(var(--h, 100) 70% 92%) 0%, transparent 70%), linear-gradient(180deg, #f6faf1 0%, #fff 100%); }
    .sp-fbadge { display: inline-flex; align-items: center; gap: .6rem; font-size: .8rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: hsl(var(--h, 100) 55% 28%); }
    .sp-fico { width: 2.6rem; height: 2.6rem; border-radius: .85rem; display: grid; place-items: center; color: hsl(var(--h, 100) 60% 30%);
        background: linear-gradient(145deg, hsl(var(--h, 100) 70% 94%), hsl(var(--h, 100) 60% 85%)); box-shadow: inset 0 0 0 1px hsl(var(--h, 100) 50% 78%), 0 10px 22px -16px hsl(var(--h, 100) 40% 20% / .6); }
    .sp-fico svg { width: 1.35rem; height: 1.35rem; }
    .sp-figure.is-product { padding: .6rem; border-radius: 1.5rem; background: linear-gradient(160deg, #fff, #eef5e6); border: 1px solid #e1edd3; box-shadow: 0 30px 60px -40px rgb(20 33 12 / .55); }
    .sp-fhero { display: grid; gap: 2rem; align-items: center; }
    @media (min-width: 1024px) { .sp-fhero { grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr); gap: 3.5rem; } }
    .sp-phone { margin: 0 auto; width: min(100%, 17.5rem); padding: .55rem; border-radius: 2.3rem; background: linear-gradient(160deg, #22321a, #0f1a0a);
        box-shadow: 0 0 0 1px rgb(255 255 255 / .08) inset, 0 40px 70px -40px rgb(20 33 12 / .7), 0 0 0 10px hsl(var(--h, 100) 60% 90% / .6); transform: rotate(2deg);
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .sp-phone:hover { transform: rotate(0); }
    .sp-phone img { display: block; width: 100%; max-height: 32rem; object-fit: cover; object-position: top; border-radius: 1.8rem; }
    /* A feature's film plays in the phone (2026-10-06). The phone keeps a
       real phone's shape; the tilt stays off so the screen reads straight. */
    .sp-phone.is-film { position: relative; width: min(100%, 16.5rem); transform: none; margin-bottom: 2.8rem; }
    .sp-phone video { display: block; width: 100%; aspect-ratio: 390 / 844; object-fit: cover; border-radius: 1.8rem; background: #eef2ea; }
    .sp-phone-tag { position: absolute; left: 50%; bottom: -2.6rem; translate: -50% 0; display: inline-flex; align-items: center; gap: .4rem; white-space: nowrap;
        padding: .35rem .8rem; border-radius: 999px; font-size: .74rem; font-weight: 800; color: #14210c; background: #f5c518;
        box-shadow: 0 10px 22px -12px rgb(0 0 0 / .5); }
    .sp-phone-tag i { width: .45rem; height: .45rem; border-radius: 999px; background: #2f5219; }
    @media (prefers-reduced-motion: reduce) { .sp-phone { transform: none; transition: none; } }
    .sp-figure.is-product img { aspect-ratio: 16 / 8.5; border-radius: 1.05rem; box-shadow: none; object-position: top center; }
    .sp-figure img { width: 100%; aspect-ratio: 16 / 8; object-fit: cover; border-radius: 1.25rem; box-shadow: 0 24px 48px -30px rgb(20 33 12 / .5); }
    .sp-figure figcaption { margin-top: .5rem; font-size: .75rem; color: #9ca3af; }
    .sp-figure figcaption a, .sp-img figcaption a { color: #4b5563; font-weight: 700; text-decoration: underline; text-decoration-color: #c9d6bb; text-underline-offset: 2px; }

    .sp-wrap { display: grid; gap: 2.5rem; }
    @media (min-width: 1024px) { .sp-wrap { grid-template-columns: minmax(0, 1fr) 19rem; gap: 3.5rem; } }
    .sp-side { display: grid; gap: 1.25rem; align-content: start; }
    @media (min-width: 1024px) { .sp-side { position: sticky; top: 6rem; } }
    .sp-card { border: 1px solid #e5ebdf; border-radius: 1.1rem; background: #fff; padding: 1.1rem 1.15rem; }
    .sp-card h4, .sp-card .sp-card-h { font-family: var(--font-heading); font-weight: 800; font-size: .95rem; color: #14210c; }
    .sp-toc { display: grid; gap: .15rem; margin-top: .6rem; }
    .sp-toc a { display: block; padding: .38rem .55rem; border-radius: .55rem; font-size: .86rem; line-height: 1.35; color: #4b5563; text-decoration: none;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .sp-toc a:hover, .sp-toc a.is-on { background: #f3f8ec; color: #2d5016; }
    .sp-toc a.is-on { font-weight: 700; }
    .sp-wcard p { margin-top: .4rem; font-size: .86rem; line-height: 1.55; color: #4b5563; }
    .sp-wcard .btn { margin-top: .8rem; width: 100%; justify-content: center; }
    .sp-promo { border-radius: 1.1rem; padding: 1.2rem; color: #e8efe1; background: linear-gradient(140deg, #2d5016, #24400f 70%); }
    /* An answered question on a phone ends on one ask: "Ask Anee for free".
       The season promo is the desktop side column's. */
    @media (max-width: 1023.98px) { .sp-side.is-question .sp-promo { display: none; } }
    .sp-promo b { display: block; font-family: var(--font-heading); font-size: 1.05rem; color: #fff; }
    .sp-promo p { margin-top: .4rem; font-size: .86rem; line-height: 1.55; color: #cfe0bd; }
    .sp-promo .btn { margin-top: .9rem; width: 100%; justify-content: center; }
    .sp-rel { display: grid; gap: .5rem; margin-top: .6rem; }
    .sp-rel a { display: block; font-size: .86rem; font-weight: 600; color: #3d6823; text-decoration: none; line-height: 1.35; }
    .sp-rel a:hover { text-decoration: underline; }

    /* ---- the article ---- */
    .sp-body { min-width: 0; font-size: 1.04rem; line-height: 1.75; color: #1f2937; }
    .sp-body > * + * { margin-top: 1.15rem; }
    .sp-body h2 { font-family: var(--font-heading); font-weight: 800; font-size: clamp(1.35rem, 2.6vw, 1.7rem); line-height: 1.25; color: #14210c;
        margin-top: 2.6rem; scroll-margin-top: 6.5rem; }
    .sp-body h3 { font-family: var(--font-heading); font-weight: 800; font-size: 1.18rem; line-height: 1.3; color: #1f3312; margin-top: 1.8rem; scroll-margin-top: 6.5rem; }
    .sp-body p a, .sp-body li a, .sp-body td a, .sp-body .sp-call a, .sp-body .sp-faq a { color: #3d6823; font-weight: 600; text-decoration: underline;
        text-decoration-color: #a8cc7e; text-underline-offset: 3px; }
    .sp-body p a:hover, .sp-body li a:hover { text-decoration-color: #3d6823; }
    .sp-body strong { color: #14210c; }
    .sp-body ul.sp-list, .sp-body ol.sp-list { padding-left: 1.3rem; display: grid; gap: .45rem; }
    .sp-body ul.sp-list { list-style: disc; } .sp-body ol.sp-list { list-style: decimal; }
    .sp-body ul.sp-list li::marker { color: #6b9f3d; } .sp-body ol.sp-list li::marker { color: #4a7c2a; font-weight: 800; }
    .sp-steps { display: grid; gap: .8rem; counter-reset: sp-step; }
    .sp-step { position: relative; padding: 1rem 1.1rem 1rem 3.6rem; border-radius: 1rem; background: #f9fbf6; border: 1px solid #e5ebdf; counter-increment: sp-step; }
    .sp-step::before { content: counter(sp-step); position: absolute; left: 1rem; top: 1rem; width: 1.9rem; height: 1.9rem; border-radius: 999px;
        display: grid; place-items: center; background: #4a7c2a; color: #fff; font-weight: 800; font-size: .9rem; }
    .sp-step b { display: block; font-family: var(--font-heading); color: #14210c; font-size: 1.02rem; }
    .sp-step p { margin-top: .25rem; font-size: .97rem; color: #374151; }
    .sp-table-wrap { overflow-x: auto; border: 1px solid #e5ebdf; border-radius: 1rem; }
    .sp-table { width: 100%; border-collapse: collapse; font-size: .92rem; line-height: 1.5; }
    .sp-table th { text-align: left; background: #f3f8ec; color: #2d5016; font-weight: 800; padding: .65rem .8rem; border-bottom: 1px solid #e4efd4; white-space: nowrap; }
    .sp-table td { padding: .6rem .8rem; border-bottom: 1px solid #f0f3ec; vertical-align: top; }
    .sp-table tr:last-child td { border-bottom: 0; }
    .sp-table caption { caption-side: bottom; text-align: left; padding: .5rem .8rem; font-size: .78rem; color: #6b7280; }
    /* A phone: a table of three columns or more becomes one card per row
       (blocks.blade marks it .is-stack and labels each cell with its column).
       Before, the newest prices sat past the right edge behind a thin scroll. */
    @media (max-width: 639.98px) {
        .sp-table th { white-space: normal; }
        .sp-table-wrap.is-stack { overflow: visible; border: 0; border-radius: 0; }
        .is-stack .sp-table { display: flex; flex-direction: column; gap: .6rem; font-size: .93rem; }
        .is-stack .sp-table thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }
        .is-stack .sp-table tbody { display: grid; gap: .6rem; }
        .is-stack .sp-table tr { display: block; padding: .75rem .95rem .6rem; border: 1px solid #e5ebdf; border-radius: 1rem; background: #fff; }
        .is-stack .sp-table td { display: grid; grid-template-columns: minmax(0, 40%) minmax(0, 1fr); gap: .8rem; padding: .4rem 0; border-bottom: 1px solid #f0f3ec; }
        .is-stack .sp-table td::before { content: attr(data-th); font-size: .8rem; font-weight: 700; line-height: 1.45; color: #4d7c2a; padding-top: .1rem; }
        .is-stack .sp-table td:first-child { display: block; padding: 0 0 .5rem; font-family: var(--font-heading); font-weight: 800; font-size: 1rem; color: #14210c; }
        .is-stack .sp-table td:first-child::before { content: none; }
        .is-stack .sp-table tr td:last-child { border-bottom: 0; }
        .is-stack .sp-table caption { order: 2; display: block; padding: 0 .2rem; }
    }
    .sp-call { display: flex; gap: .8rem; padding: 1rem 1.1rem; border-radius: 1rem; border: 1px solid; }
    .sp-call svg { flex: none; width: 1.35rem; height: 1.35rem; margin-top: .15rem; }
    .sp-call b { display: block; font-family: var(--font-heading); }
    .sp-call p { margin-top: .2rem; font-size: .97rem; }
    .sp-call.tip { background: #f3f8ec; border-color: #c9e0ad; color: #24400f; } .sp-call.tip svg { color: #4a7c2a; }
    .sp-call.warn { background: #fff8e6; border-color: #f5d98a; color: #6b4a00; } .sp-call.warn svg { color: #c79e00; }
    .sp-call.info { background: #eef4fd; border-color: #c7dbf5; color: #1e3a6b; } .sp-call.info svg { color: #2563eb; }
    .sp-img { margin: 0; }
    .sp-img img { width: 100%; border-radius: 1rem; }
    .sp-img figcaption { margin-top: .45rem; font-size: .8rem; color: #6b7280; }
    .sp-img.is-news { position: relative; }
    .sp-img.is-news img { aspect-ratio: 16 / 9; object-fit: cover; border-radius: 1.1rem; background: #eef2ea; box-shadow: 0 18px 40px -30px rgb(20 33 12 / .55); }
    .sp-img.is-news figcaption { position: absolute; left: .7rem; bottom: .7rem; margin: 0; max-width: calc(100% - 1.4rem); padding: .3rem .65rem; border-radius: 999px;
        font-size: .72rem; color: #fff; background: rgb(15 23 12 / .68); backdrop-filter: blur(4px); }
    .sp-img.is-news figcaption a { color: #fff; text-decoration-color: rgb(255 255 255 / .55); }
    .sp-quote { border-left: 4px solid #86b556; padding: .3rem 0 .3rem 1.1rem; font-family: var(--font-heading); font-size: 1.15rem; color: #1f3312; }
    .sp-quote cite { display: block; margin-top: .4rem; font-family: inherit; font-size: .85rem; font-style: normal; color: #6b7280; }
    .sp-faq { display: grid; gap: .6rem; }
    .sp-faq details { border: 1px solid #e5ebdf; border-radius: 1rem; background: #fff; overflow: hidden;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .sp-faq details[open] { border-color: #a8cc7e; box-shadow: 0 12px 28px -24px rgb(20 33 12 / .6); }
    .sp-faq summary { cursor: pointer; list-style: none; display: flex; justify-content: space-between; gap: 1rem; padding: .9rem 1.1rem;
        font-weight: 700; color: #14210c; }
    .sp-faq summary::-webkit-details-marker { display: none; }
    .sp-faq summary svg { flex: none; width: 1.1rem; height: 1.1rem; color: #6b9f3d; transition: transform .28s cubic-bezier(.22,1,.36,1); margin-top: .2rem; }
    .sp-faq details[open] summary svg { transform: rotate(45deg); }
    .sp-faq .ans { padding: 0 1.1rem 1rem; color: #374151; font-size: .98rem; }
    /* A news roundup's source: who and when, then the report as a button. */
    .sp-body .sp-srcline { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem .8rem; }
    .sp-srcline span { font-size: .88rem; font-weight: 700; color: #4b5563; }
    .sp-body .sp-srcline .sp-srcbtn { display: inline-flex; align-items: center; gap: .45rem; min-height: 2.6rem; padding: .5rem 1rem; border-radius: 999px; border: 1.5px solid #a8cc7e;
        background: #fff; color: #2d5016; font-size: .9rem; font-weight: 800; line-height: 1.25; text-decoration: none;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .sp-srcbtn svg { flex: none; width: 1rem; height: 1rem; }
    .sp-body .sp-srcline .sp-srcbtn:hover { background: #f3f8ec; border-color: #6b9f3d; }
    .sp-srcbtn:active { transform: scale(.98); }
    @media (prefers-reduced-motion: reduce) { .sp-body .sp-srcline .sp-srcbtn { transition: none; } }
    .sp-cta { border-radius: 1.25rem; padding: 1.5rem 1.4rem; color: #e8efe1; background: linear-gradient(135deg, #3d6823, #24400f 75%); position: relative; overflow: hidden; }
    .sp-cta::after { content: ''; position: absolute; right: -3rem; top: -3rem; width: 11rem; height: 11rem; border-radius: 999px; background: rgb(245 197 24 / .14); }
    .sp-cta b { position: relative; display: block; font-family: var(--font-heading); font-size: 1.3rem; color: #fff; line-height: 1.3; }
    .sp-cta p { position: relative; margin-top: .45rem; color: #d7e6c8; font-size: .98rem; }
    .sp-cta .btn { position: relative; margin-top: 1rem; }
    .sp-cta-list { position: relative; margin-top: .8rem; display: grid; gap: .45rem; }
    .sp-cta-list li { display: flex; gap: .55rem; align-items: flex-start; color: #eef5e6; font-size: .95rem; line-height: 1.5; }
    .sp-cta-list svg { flex: none; width: 1.1rem; height: 1.1rem; margin-top: .15rem; padding: .12rem; border-radius: 999px; background: #f5c518; color: #3b2f00; }
    .sp-ask { border-radius: 1.1rem; padding: 1.2rem; background: #fff; border: 1px solid #dbe8cc; box-shadow: 0 14px 30px -24px rgb(20 40 10 / .45); }
    .sp-ask img { width: 2.8rem; height: 2.8rem; border-radius: 999px; object-fit: cover; }
    .sp-ask b { display: block; margin-top: .6rem; font-family: var(--font-heading); font-size: 1.05rem; color: #14210c; }
    .sp-ask p { margin-top: .35rem; font-size: .86rem; line-height: 1.55; color: #4b5563; }
    .sp-ask .btn { margin-top: .9rem; width: 100%; justify-content: center; }
    .sp-links { border-radius: 1.1rem; background: #f9fbf6; border: 1px solid #e5ebdf; padding: 1.1rem 1.2rem; }
    .sp-links b { font-family: var(--font-heading); color: #14210c; }
    .sp-links ul { margin-top: .5rem; display: grid; gap: .4rem; }
    @media (min-width: 640px) { .sp-links ul { grid-template-columns: 1fr 1fr; } }
    .sp-links a { display: flex; align-items: center; gap: .4rem; padding: .3rem 0; color: #3d6823; font-weight: 700; font-size: .95rem; text-decoration: none; }
    .sp-links ul { gap: .1rem .4rem; }
    .sp-links a:hover { text-decoration: underline; }
    .sp-links a svg { flex: none; width: .9rem; height: .9rem; }
    .sp-sources { font-size: .85rem; color: #6b7280; border-top: 1px solid #eef1ea; padding-top: 1rem; }
    .sp-sources b { color: #374151; }
    .sp-sources ol { margin-top: .4rem; padding-left: 1.2rem; list-style: decimal; display: grid; gap: .25rem; }
    .sp-sources a { color: #4b5563; word-break: break-word; }
    .sp-divider { border: 0; border-top: 1px solid #e5ebdf; }
    /* A line between parts gets room on both sides, and the heading under it sits closer to it. */
    .sp-body > .sp-divider { margin-top: 2.2rem; }
    .sp-body > .sp-divider + h2, .sp-body > .sp-divider + h3 { margin-top: 1.6rem; }

    /* ---- cards (hubs, "keep reading") ---- */
    .sp-grid { display: grid; gap: 1.1rem; grid-template-columns: repeat(auto-fill, minmax(16rem, 1fr)); }
    .sp-tile { display: flex; flex-direction: column; border-radius: 1.1rem; overflow: hidden; background: #fff; border: 1px solid #e5ebdf; text-decoration: none;
        transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .sp-tile:hover { transform: translateY(-3px); box-shadow: 0 18px 36px -26px rgb(20 33 12 / .55); border-color: #c9e0ad; }
    .sp-tile img { width: 100%; aspect-ratio: 16 / 9; object-fit: cover; background: #f3f8ec; }
    .sp-tile .in { padding: .95rem 1.05rem 1.1rem; display: flex; flex-direction: column; gap: .35rem; flex: 1; }
    .sp-tile .cat { font-size: .7rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: #6b9f3d; }
    .sp-tile b { font-family: var(--font-heading); font-size: 1.05rem; line-height: 1.3; color: #14210c; }
    .sp-tile p { font-size: .88rem; line-height: 1.55; color: #4b5563; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .sp-tile .go { margin-top: auto; padding-top: .4rem; font-size: .82rem; font-weight: 800; color: #3d6823; }
    .sp-tile .cat time { color: #6b7280; font-weight: 700; }
    /* A phone, on the hubs and under an article: a list, a small picture
       beside the words, so a screen shows five guides instead of two. */
    @media (max-width: 639.98px) {
        .sp-grid.is-list { grid-template-columns: minmax(0, 1fr); gap: .65rem; }
        .sp-grid.is-list .sp-tile { flex-direction: row; align-items: stretch; border-radius: 1rem; }
        .sp-grid.is-list .sp-tile img { flex: none; align-self: stretch; width: 6.6rem; height: auto; min-height: 7.25rem; aspect-ratio: auto; }
        .sp-grid.is-list .sp-tile .in { min-width: 0; padding: .7rem .85rem .75rem; gap: .2rem; justify-content: center; }
        .sp-grid.is-list .sp-tile b { font-size: .98rem; line-height: 1.28; }
        .sp-grid.is-list .sp-tile p { font-size: .84rem; line-height: 1.45; -webkit-line-clamp: 2; }
        .sp-grid.is-list .sp-tile .go { display: none; }
        .sp-grid.is-list .sp-tile:hover { transform: none; }
    }
    /* One row always (owner, 2026-10-07): on a narrow screen it slides sideways, the active tab brought into view. */
    .sp-tabs { display: inline-flex; flex-wrap: nowrap; max-width: 100%; overflow-x: auto; scrollbar-width: none; gap: .35rem; padding: .3rem; border-radius: 999px; background: #fff; border: 1px solid #e4efd4; }
    .sp-tabs::-webkit-scrollbar { display: none; }
    .sp-tabs.is-scroll { -webkit-mask-image: linear-gradient(90deg, #000 82%, transparent); mask-image: linear-gradient(90deg, #000 82%, transparent); }
    .sp-tabs.is-scroll.is-end { -webkit-mask-image: linear-gradient(270deg, #000 82%, transparent); mask-image: linear-gradient(270deg, #000 82%, transparent); }
    .sp-tabs a { flex: none; white-space: nowrap; padding: .45rem .95rem; border-radius: 999px; font-size: .88rem; font-weight: 700; color: #3d6823; text-decoration: none;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .sp-tabs a.is-on { background: #4a7c2a; color: #fff; }
    @media (max-width: 639.98px) { .sp-tabs a { padding: .6rem 1rem; } }
    .sp-tabs a:not(.is-on):hover { background: #f3f8ec; }
    .sp-cats { display: flex; flex-wrap: wrap; gap: .4rem; }
    .sp-cats button { padding: .35rem .8rem; border-radius: 999px; font-size: .8rem; font-weight: 700; border: 1px solid #e4efd4; background: #fff; color: #4b5563;
        transition: background-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    .sp-cats button.is-on { background: #2d5016; border-color: #2d5016; color: #fff; }
    .sp-cats button small { margin-left: .3rem; font-size: .74rem; font-weight: 700; opacity: .65; }
    /* A phone: chips a thumb can hit. */
    @media (max-width: 639.98px) { .sp-cats.is-hub button { min-height: 2.5rem; padding: .45rem .9rem; font-size: .84rem; } }
    /* A feature guide's "More of what anee.io does" on a phone: the eight
       nearest features (same part of the farm first), then one button to all. */
    .sp-feats-all { display: none; }
    @media (max-width: 559.98px) {
        .sp-feats .fg-card:nth-child(n+9) { display: none; }
        .sp-feats-all { display: flex; justify-content: center; width: 100%; margin-top: 1.1rem; }
    }
    .sp-tile.is-out { display: none; }
    .sp-tile.is-in { animation: spIn .32s cubic-bezier(.22,1,.36,1) both; }
    @keyframes spIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    @media (prefers-reduced-motion: reduce) {
        .sp-tile, .sp-toc a, .sp-faq details, .sp-faq summary svg, .sp-tabs a, .sp-cats button { transition: none; }
        .sp-tile.is-in { animation: none; }
    }
</style>
<script>
    /* The section tabs: when the row is wider than the screen, the active tab
       is scrolled into view and the open edge fades. */
    document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('.sp-tabs').forEach((t) => {
        const fit = () => {
            const over = t.scrollWidth > t.clientWidth + 2;
            t.classList.toggle('is-scroll', over);
            t.classList.toggle('is-end', over && t.scrollLeft + t.clientWidth >= t.scrollWidth - 4);
        };
        const on = t.querySelector('.is-on');
        if (on && t.scrollWidth > t.clientWidth) t.scrollLeft = on.offsetLeft - (t.clientWidth - on.offsetWidth) / 2;
        fit();
        t.addEventListener('scroll', fit, { passive: true });
        window.addEventListener('resize', fit);
    }));
</script>
@endpush
@endonce
