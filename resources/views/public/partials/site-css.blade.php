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
</style>
@endpush
@endonce
