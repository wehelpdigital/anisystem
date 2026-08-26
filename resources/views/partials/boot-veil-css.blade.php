{{-- The veil's dress, in the HEAD, so it is in force before the first byte
     of <body> is parsed. The veil's own markup lives in partials.boot-veil,
     first thing inside the body.

     Two things happen here, deliberately belt AND braces:

     1. html.booting hides the page's own content outright. An overlay only
        works if it renders and stacks the way it was told to; on an older
        phone `inset` may not be understood, a stacking context may lift
        something over it, and then a half-dressed page shows through. Hidden
        content cannot show through anything. visibility, not display, so the
        page still lays out underneath and nothing measures zero.
     2. The veil itself paints the ground and turns a spinner over it, so the
        wait reads as work rather than as a blank screen.

     The class is stamped on <html> by the server, not by a script, so there
     is no frame in which it has not been applied yet. It is taken off when
     the page is whole (see partials.boot-veil). --}}
<style>
    /* Hidden — but on a timer that CSS keeps by itself. If the script below
       never runs at all (it threw, it was stripped, the browser choked on
       it), the page still comes back on its own after eight seconds. A
       delayed zero-length animation is the only way to say "give up" in CSS,
       and it costs nothing when the script does its job. */
    html.booting body > *:not(#bootVeil) { visibility: hidden;
        animation: bootReveal 0s linear 8s forwards; }
    @keyframes bootReveal { to { visibility: visible; } }
    .boot-veil { position: fixed; top: 0; right: 0; bottom: 0; left: 0;
        z-index: 400; display: flex; align-items: center; justify-content: center;
        background: #f1f3f5;
        transition: opacity .28s cubic-bezier(.22, 1, .36, 1);
        animation: bootGiveUp 0s linear 8s forwards; }
    @keyframes bootGiveUp { to { opacity: 0; visibility: hidden; } }
    body.plaza-ground .boot-veil { background: #eef4e8; }
    html.dark .boot-veil { background: #14171c; }
    html.dark body.plaza-ground .boot-veil { background: #0b140d; }
    /* The public pages (login, signup, the landing) stand on white. */
    body.bg-white .boot-veil { background: #fff; }
    html.dark body.bg-white .boot-veil { background: #191d23; }
    .boot-veil.is-off { opacity: 0; pointer-events: none; }
    .boot-veil-spin { display: block; width: 2.4rem; height: 2.4rem; border-radius: 999px;
        border: 3px solid rgba(74, 124, 42, .2); border-top-color: #4a7c2a;
        animation: bootVeilSpin .8s linear infinite; }
    @keyframes bootVeilSpin { to { transform: rotate(360deg); } }
    html.dark .boot-veil-spin { border-color: rgba(107, 159, 61, .25); border-top-color: #6b9f3d; }
    @media (prefers-reduced-motion: reduce) {
        .boot-veil { transition: none; }
        /* Slowed, not stopped: the turn is the message that work is happening. */
        .boot-veil-spin { animation-duration: 1.6s; }
    }
</style>
{{-- Without JavaScript nothing would ever lift the hiding, so it is never
     applied in the first place. --}}
<noscript><style>
    html.booting body > *:not(#bootVeil) { visibility: visible; }
    .boot-veil { display: none; }
</style></noscript>
