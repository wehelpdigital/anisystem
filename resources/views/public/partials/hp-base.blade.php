{{-- The public pages' shared look (2026-10-06), first built for the homepage:
     the hp- tokens, the section rhythm, the kicker, heading and paragraph,
     the call under every section (a shining gold button and its quieter
     partner), Anee's round face, the live dot, and the hero's shimmering
     words with the hand drawn underline that reveals itself left to right.
     Home, About and Contact include it; each page keeps its own extras. --}}
@once
@push('head')
<style>
    :root { --hp-ease: cubic-bezier(.22,1,.36,1); --hp-green: #4a7c2a; --hp-deep: #2f5219; --hp-ink: #14210c; --hp-sun: #f5c518; }
    html { scroll-behavior: smooth; }
    /* padding-block, never the shorthand: some containers wear hp-sec AND
       px-4, and a shorthand here would take their side gutters away. */
    .hp-sec { padding-block: 4.5rem; }
    @media (min-width: 640px) { .hp-sec { padding-block: 6rem; } }
    .hp-head { max-width: 46rem; margin: 0 auto; text-align: center; }
    .hp-kick { display: inline-flex; align-items: center; gap: .5rem; font-size: .76rem; font-weight: 800;
        letter-spacing: .1em; text-transform: uppercase; color: var(--hp-green); }
    .hp-kick::before { content: ''; width: .45rem; height: .45rem; border-radius: 999px; background: currentColor;
        box-shadow: 0 0 0 4px color-mix(in srgb, currentColor 18%, transparent); }
    .hp-kick.is-red { color: #dc2626; }
    .hp-h2 { margin-top: .7rem; font-family: var(--font-heading); font-weight: 800; color: var(--hp-ink);
        font-size: clamp(1.8rem, 4.2vw, 2.85rem); line-height: 1.1; letter-spacing: -.015em; text-wrap: balance; }
    .hp-h2 em { font-style: normal; color: var(--hp-green); }
    .hp-p { margin-top: 1rem; color: #4b5563; font-size: clamp(1rem, 1.5vw, 1.1rem); line-height: 1.7; text-wrap: pretty; }
    .hp-p b { color: var(--hp-ink); }
    .on-dark .hp-kick { color: var(--hp-sun); }
    .on-dark .hp-h2 { color: #fff; }
    .on-dark .hp-h2 em { color: var(--hp-sun); }
    .on-dark .hp-p { color: #d3dec7; }
    .on-dark .hp-p b { color: #fff; }

    /* ---- the call under every section ---- */
    .hp-cta { margin-top: 3rem; display: flex; flex-direction: column; align-items: center; gap: .8rem; text-align: center; }
    .hp-cta.is-tight { margin-top: 2.25rem; }
    .hp-cta.is-left { align-items: flex-start; text-align: left; margin-top: 2rem; }
    .hp-cta-row { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; }
    .hp-cta.is-left .hp-cta-row { justify-content: flex-start; }
    .hp-cta-note { font-size: .86rem; color: #6b7280; max-width: 40rem; }
    .hp-cta-note a { font-weight: 800; color: var(--hp-green); }
    .on-dark .hp-cta-note { color: rgb(255 255 255 / .7); }
    .on-dark .hp-cta-note a { color: var(--hp-sun); }
    .hp-go { position: relative; overflow: hidden; isolation: isolate; gap: .55rem;
        box-shadow: 0 14px 30px -14px rgb(245 197 24 / .75);
        transition: transform .28s var(--hp-ease), box-shadow .28s var(--hp-ease), background-color .28s var(--hp-ease); }
    .hp-go:hover { transform: translateY(-2px); box-shadow: 0 20px 36px -14px rgb(245 197 24 / .85); }
    .hp-go svg { width: 1.2rem; height: 1.2rem; flex: none; transition: transform .28s var(--hp-ease); }
    .hp-go:hover svg { transform: translateX(4px); }
    .hp-go::after { content: ''; position: absolute; inset: 0; z-index: -1; pointer-events: none;
        background: linear-gradient(105deg, transparent 35%, rgb(255 255 255 / .55) 50%, transparent 65%);
        transform: translateX(-120%); animation: hpShine 6s var(--hp-ease) infinite 2s; }
    @keyframes hpShine { 0%, 72% { transform: translateX(-120%); } 100% { transform: translateX(120%); } }
    .hp-alt { display: inline-flex; align-items: center; justify-content: center; gap: .55rem; min-height: 3.25rem;
        padding: .7rem 1.35rem; border-radius: 1rem; font-weight: 800; font-size: 1rem; color: var(--hp-deep);
        background: #fff; border: 2px solid #cfe3b8; text-decoration: none;
        transition: border-color .28s var(--hp-ease), transform .28s var(--hp-ease), background-color .28s var(--hp-ease); }
    .hp-alt:hover { border-color: var(--hp-green); transform: translateY(-2px); }
    .on-dark .hp-alt, .hp-alt.is-glass { color: #fff; background: rgb(255 255 255 / .1); border-color: rgb(255 255 255 / .35);
        backdrop-filter: blur(8px); }
    .on-dark .hp-alt:hover, .hp-alt.is-glass:hover { background: rgb(255 255 255 / .18); border-color: rgb(255 255 255 / .7); }
    .hp-face { width: 1.7rem; height: 1.7rem; border-radius: 999px; object-fit: cover; flex: none;
        box-shadow: 0 0 0 2px rgb(245 197 24 / .8); }
    .hp-face.is-lg { width: 2.4rem; height: 2.4rem; }
    .hp-live { display: inline-block; width: .5rem; height: .5rem; border-radius: 999px; background: #4ade80; margin-right: .35rem;
        box-shadow: 0 0 0 0 rgb(74 222 128 / .6); animation: hpLive 2s ease-out infinite; }
    @keyframes hpLive { 70% { box-shadow: 0 0 0 7px rgb(74 222 128 / 0); } 100% { box-shadow: 0 0 0 0 rgb(74 222 128 / 0); } }

    .hp-shimmer { display: inline-block; color: transparent; -webkit-background-clip: text; background-clip: text;
        background-image: linear-gradient(100deg, #f5c518 0%, #fde68a 30%, #f5c518 55%, #e9a80b 80%, #f5c518 100%);
        background-size: 220% 100%; animation: hpShimmer 6s linear infinite; }
    @keyframes hpShimmer { from { background-position: 0% 0; } to { background-position: -220% 0; } }
    /* The hand drawn underline under "higher yield". */
    .hp-mark { position: relative; display: inline-block; white-space: nowrap; }
    /* Gradient text paints only inside its own box, and the h1's tight line
       height cut the tails of the g and y off: the box reaches below them
       now, and gives the room back so the lines stay where they were. */
    .hp-mark .hp-shimmer { padding: .04em .03em .2em; margin: -.04em -.03em -.2em; }
    /* Drawn by revealing the whole stroke from left to right with a clip, not
       by animating a dash: a dash on a stretched, non-scaling stroke is
       measured differently by each browser, and on some phones the line
       showed under "yield" first and ended under "higher" only. */
    .hp-mark-line { position: absolute; left: -2%; bottom: -.3em; width: 104%; height: .3em; overflow: visible; pointer-events: none;
        -webkit-clip-path: inset(-60% 102% -60% -4%); clip-path: inset(-60% 102% -60% -4%);
        animation: hpReveal 1.2s cubic-bezier(.65,0,.35,1) .7s forwards; }
    .hp-mark-line path { fill: none; stroke-linecap: round; vector-effect: non-scaling-stroke; }
    .hp-mark-line .a { stroke: #f5c518; stroke-width: 7px; filter: drop-shadow(0 3px 8px rgb(245 197 24 / .45));
        animation: hpGlint 4.5s ease-in-out 2.4s infinite; }
    @keyframes hpReveal { to { -webkit-clip-path: inset(-60% -4% -60% -4%); clip-path: inset(-60% -4% -60% -4%); } }
    @keyframes hpGlint { 0%, 100% { stroke: #f5c518; } 50% { stroke: #fde68a; } }

    @media (prefers-reduced-motion: reduce) {
        html { scroll-behavior: auto; }
        .hp-shimmer, .hp-mark-line, .hp-mark-line path, .hp-go::after, .hp-live { animation: none !important; }
        .hp-go, .hp-alt { transition: none !important; }
        .hp-mark-line { -webkit-clip-path: none; clip-path: none; }
    }
</style>
@endpush
@endonce
