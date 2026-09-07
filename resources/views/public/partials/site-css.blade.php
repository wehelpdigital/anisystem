{{-- Shared dress for the marketing pages: the phone frame the app
     screenshots sit in, the alternating feature rows, the deep-green Anee
     band (the Tip of the Day's clothes, worn outdoors), stat and pricing
     cards. Pulled in by home, features and pricing so the three pages
     cannot drift apart. --}}
@once
@push('head')
<style>
    /* ---- a real screenshot in a phone's clothes ---- */
    .ph-frame { position: relative; width: min(300px, 78vw); border-radius: 2.2rem; padding: .55rem;
        background: linear-gradient(160deg, #1d232b, #0e1116 70%); box-shadow:
            0 24px 60px -28px rgb(16 22 12 / .55), 0 0 0 1px rgb(255 255 255 / .08) inset; }
    .ph-frame::before { content: ''; position: absolute; top: .95rem; left: 50%; transform: translateX(-50%);
        width: 5.2rem; height: .45rem; border-radius: 999px; background: #0e1116; z-index: 2; }
    .ph-frame img { display: block; width: 100%; border-radius: 1.7rem; }
    .ph-tilt-l { transform: rotate(-2.2deg); }
    .ph-tilt-r { transform: rotate(2.2deg); }
    @media (hover: hover) {
        .ph-frame { transition: transform .5s cubic-bezier(.22,1,.36,1); }
        .ph-frame:hover { transform: rotate(0) translateY(-6px); }
    }

    /* ---- alternating feature rows ---- */
    .fx-row { display: grid; gap: 2.5rem; align-items: center; }
    @media (min-width: 900px) {
        .fx-row { grid-template-columns: 1fr 1fr; gap: 4rem; }
        .fx-row.is-flip > .fx-media { order: 2; }
    }
    .fx-media { display: flex; justify-content: center; }
    .fx-kicker { font-size: .78rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #4a7c2a; }
    .fx-h { margin-top: .5rem; font-family: var(--font-heading); font-size: clamp(1.5rem, 3vw, 2.1rem);
        font-weight: 800; color: #14210c; line-height: 1.15; }
    .fx-p { margin-top: .9rem; color: #4b5563; line-height: 1.65; }
    .fx-list { margin-top: 1.1rem; display: grid; gap: .55rem; }
    .fx-list li { display: flex; gap: .6rem; align-items: flex-start; color: #374151; font-size: .95rem; line-height: 1.55; }
    .fx-list li svg { flex: none; width: 1.15rem; height: 1.15rem; margin-top: .15rem; color: #4a7c2a; }
    .fx-glow { position: relative; }
    .fx-glow::before { content: ''; position: absolute; inset: -12% -8%; z-index: -1; border-radius: 999px;
        background: radial-gradient(closest-side, rgb(168 204 126 / .35), transparent 72%); }

    /* ---- the Anee band: the Tip of the Day's deep green, outdoors ---- */
    .anee-band { position: relative; overflow: hidden; color: #e8efe1;
        background: linear-gradient(135deg, #0d1309 0%, #1c2416 55%, #26331b 100%); }
    .anee-band::before { content: ''; position: absolute; inset: -30% -10%; pointer-events: none;
        background: radial-gradient(closest-side, rgb(134 181 86 / .3), transparent 70%);
        animation: aneeGlow 8s ease-in-out infinite; }
    @keyframes aneeGlow {
        0%, 100% { transform: translateX(-25%) scale(.9); opacity: .5; }
        50% { transform: translateX(25%) scale(1.1); opacity: .9; }
    }
    @media (prefers-reduced-motion: reduce) { .anee-band::before { animation: none; } }

    /* ---- honest numbers ---- */
    .stat-band { display: grid; gap: 1rem; grid-template-columns: repeat(2, 1fr); }
    @media (min-width: 720px) { .stat-band { grid-template-columns: repeat(4, 1fr); } }
    .stat-card { text-align: center; padding: 1.2rem .8rem; border-radius: 1rem;
        background: #f3f8ec; border: 1px solid #dcead0; }
    .stat-n { font-family: var(--font-heading); font-size: clamp(1.6rem, 3.4vw, 2.3rem); font-weight: 800; color: #2f5219; }
    .stat-l { margin-top: .2rem; font-size: .78rem; font-weight: 700; color: #6b7f5a; text-transform: uppercase; letter-spacing: .05em; }

    /* ---- pricing cards ---- */
    .pr-grid { display: grid; gap: 1.25rem; }
    @media (min-width: 900px) { .pr-grid { grid-template-columns: repeat(3, 1fr); align-items: stretch; } }
    .pr-card { position: relative; display: flex; flex-direction: column; border-radius: 1.25rem;
        border: 1px solid var(--color-gray-200); background: #fff; padding: 1.6rem 1.4rem; }
    .pr-card.is-star { border-color: #4a7c2a; box-shadow: 0 24px 50px -30px rgb(47 82 25 / .45); }
    .pr-flag { position: absolute; top: -0.85rem; left: 50%; transform: translateX(-50%);
        padding: .3rem .85rem; border-radius: 999px; background: #4a7c2a; color: #fff;
        font-size: .7rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; }
    .pr-name { font-family: var(--font-heading); font-size: 1.15rem; font-weight: 800; color: #14210c; }
    .pr-for { margin-top: .2rem; font-size: .82rem; color: #6b7280; }
    .pr-price { margin-top: 1rem; display: flex; align-items: baseline; gap: .35rem; }
    .pr-amount { font-family: var(--font-heading); font-size: 2.2rem; font-weight: 800; color: #2f5219; }
    .pr-per { font-size: .82rem; color: #6b7280; }
    .pr-year { margin-top: .15rem; font-size: .78rem; font-weight: 700; color: #4a7c2a; min-height: 1.1rem; }
    .pr-list { margin-top: 1.1rem; display: grid; gap: .5rem; flex: 1 1 auto; }
    .pr-list li { display: flex; gap: .55rem; align-items: flex-start; font-size: .88rem; color: #374151; line-height: 1.5; }
    .pr-list li.is-off { color: #9ca3af; }
    .pr-list li svg { flex: none; width: 1.05rem; height: 1.05rem; margin-top: .18rem; color: #4a7c2a; }
    .pr-list li.is-off svg { color: #d1d5db; }
    .pr-card .btn { margin-top: 1.4rem; justify-content: center; }

    /* soft float for hero ornaments */
    @keyframes siteFloat { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    .site-float { animation: siteFloat 6s ease-in-out infinite; }
    @media (prefers-reduced-motion: reduce) { .site-float { animation: none; } }

    /* ---- a feature video in its Sunday clothes ---- */
    .vid-card { position: relative; border-radius: 1.25rem; overflow: hidden; background: #0e1116;
        aspect-ratio: 16 / 9; box-shadow: 0 24px 55px -28px rgb(16 22 12 / .5);
        outline: 1px solid rgb(255 255 255 / .12); outline-offset: -1px; width: 100%; }
    .vid-card video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
    .vid-play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
        background: linear-gradient(to top, rgb(0 0 0 / .45), rgb(0 0 0 / .15)); cursor: pointer;
        border: 0; padding: 0; transition: background .28s cubic-bezier(.22,1,.36,1); }
    .vid-play:hover { background: linear-gradient(to top, rgb(0 0 0 / .35), rgb(0 0 0 / .08)); }
    .vid-play > span { display: flex; align-items: center; justify-content: center; width: 4rem; height: 4rem;
        border-radius: 999px; background: var(--color-accent-500, #f2c94c); color: #14210c;
        box-shadow: 0 12px 30px -10px rgb(0 0 0 / .6), 0 0 0 6px rgb(255 255 255 / .18);
        transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .vid-play:hover > span { transform: scale(1.08); }
    .vid-play svg { width: 1.7rem; height: 1.7rem; margin-left: .2rem; }
    .vid-tag { position: absolute; left: .8rem; top: .8rem; display: inline-flex; align-items: center; gap: .4rem;
        padding: .3rem .75rem; border-radius: 999px; background: rgb(0 0 0 / .45); backdrop-filter: blur(6px);
        color: #fff; font-size: .72rem; font-weight: 700; letter-spacing: .02em;
        box-shadow: 0 0 0 1px rgb(255 255 255 / .2) inset; pointer-events: none; }
    .vid-tag svg { width: .85rem; height: .85rem; color: var(--color-accent-400, #f4d778); }

    /* ---- a CTA under every section, always the same promise ---- */
    .sec-cta { margin-top: 2.75rem; display: flex; flex-direction: column; align-items: center; gap: .55rem; text-align: center; }
    .sec-cta .sec-cta-note { font-size: .82rem; color: #6b7280; }
    .sec-cta.on-dark .sec-cta-note { color: rgb(255 255 255 / .65); }

    /* ---- living backgrounds: two soft blobs adrift behind a section ---- */
    .bg-drift { position: relative; overflow: hidden; }
    .bg-drift > * { position: relative; z-index: 1; }
    .bg-drift::before, .bg-drift::after { content: ''; position: absolute; z-index: 0; border-radius: 999px;
        filter: blur(60px); pointer-events: none; }
    .bg-drift::before { width: 34rem; height: 34rem; left: -12rem; top: -10rem;
        background: radial-gradient(closest-side, rgb(168 204 126 / .38), transparent 72%);
        animation: blobDriftA 18s ease-in-out infinite; }
    .bg-drift::after { width: 30rem; height: 30rem; right: -10rem; bottom: -12rem;
        background: radial-gradient(closest-side, rgb(242 201 76 / .28), transparent 72%);
        animation: blobDriftB 22s ease-in-out infinite; }
    @keyframes blobDriftA { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(6rem, 3rem) scale(1.12); } }
    @keyframes blobDriftB { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(-5rem, -4rem) scale(.92); } }

    /* ---- drifting fireflies for the dark bands ---- */
    .spark-field { position: relative; }
    .spark-field::after { content: ''; position: absolute; inset: 0; z-index: 0; pointer-events: none; opacity: .5;
        background-image:
            radial-gradient(2px 2px at 18% 30%, rgb(244 215 120 / .9), transparent 60%),
            radial-gradient(2.5px 2.5px at 62% 68%, rgb(244 215 120 / .7), transparent 60%),
            radial-gradient(1.5px 1.5px at 84% 22%, rgb(255 255 255 / .8), transparent 60%),
            radial-gradient(2px 2px at 38% 82%, rgb(168 204 126 / .8), transparent 60%),
            radial-gradient(1.5px 1.5px at 74% 44%, rgb(255 255 255 / .6), transparent 60%);
        background-size: 46rem 30rem; background-repeat: repeat;
        animation: sparkDrift 60s linear infinite; }
    @keyframes sparkDrift { from { background-position: 0 0; } to { background-position: 46rem -30rem; } }

    /* Anee's portrait, framed like a poster with a breathing glow */
    .anee-portrait { position: relative; border-radius: 1.5rem; overflow: hidden;
        box-shadow: 0 30px 60px -30px rgb(0 0 0 / .65), 0 0 0 1px rgb(255 255 255 / .18) inset; }
    .anee-portrait img { display: block; width: 100%; height: 100%; object-fit: cover; }
    .anee-portrait::after { content: ''; position: absolute; inset: 0; pointer-events: none;
        box-shadow: 0 0 0 1px rgb(255 255 255 / .15) inset; border-radius: inherit; }
    .anee-halo { position: relative; }
    .anee-halo::before { content: ''; position: absolute; inset: -8%; z-index: -1; border-radius: 999px;
        background: radial-gradient(closest-side, rgb(168 204 126 / .4), transparent 70%);
        animation: aneeBreath 6s ease-in-out infinite; }
    @keyframes aneeBreath { 0%, 100% { transform: scale(.95); opacity: .6; } 50% { transform: scale(1.06); opacity: 1; } }

    /* free-tier price row on the public tier cards */
    .pr-amount.is-free { color: #4a7c2a; }
    .pr-billing { margin-top: 1rem; display: inline-flex; align-self: flex-start; border-radius: 999px;
        background: #eef4e6; padding: .2rem; gap: .2rem; }
    .pr-billing button { border: 0; background: transparent; border-radius: 999px; padding: .3rem .8rem;
        font-size: .75rem; font-weight: 800; color: #6b7f5a; cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    .pr-billing button.is-on { background: #4a7c2a; color: #fff; }

    @media (prefers-reduced-motion: reduce) {
        .bg-drift::before, .bg-drift::after, .spark-field::after, .anee-halo::before { animation: none; }
        .vid-play > span, .vid-play { transition: none; }
    }
</style>
@endpush
@endonce
