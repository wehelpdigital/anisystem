{{-- The veil that hides a page being built.

     Several components ship their CSS in a <style> inside the body (the post
     actions, the photo viewer, the mutual sheet). While the HTML streams on a
     slow line, everything above those blocks paints undressed — a page of raw
     text and giant icons, which is what a grower on 3G was seeing. This
     covers the page until the document is whole and then lifts.

     Include as the FIRST thing inside <body>: the style sits immediately
     before the box it paints, so the veil is dressed the instant it exists,
     with no dependency on app.css having arrived. The theme script in the
     head has already stamped html.dark, so night never flashes light, and
     each ground is matched exactly so the lift is a fade rather than a pop. --}}
<style>
    .boot-veil { position: fixed; inset: 0; z-index: 400; display: flex;
        align-items: center; justify-content: center; background: #f1f3f5;
        transition: opacity .28s cubic-bezier(.22, 1, .36, 1); }
    body.plaza-ground .boot-veil { background: #eef4e8; }
    html.dark .boot-veil { background: #14171c; }
    html.dark body.plaza-ground .boot-veil { background: #0b140d; }
    /* The public pages (login, signup, the landing) stand on white. */
    body.bg-white .boot-veil { background: #fff; }
    html.dark body.bg-white .boot-veil { background: #191d23; }
    .boot-veil.is-off { opacity: 0; pointer-events: none; }
    .boot-veil-spin { display: block; width: 2.4rem; height: 2.4rem; border-radius: 999px;
        border: 3px solid rgb(74 124 42 / .2); border-top-color: #4a7c2a;
        animation: bootVeilSpin .8s linear infinite; }
    @keyframes bootVeilSpin { to { transform: rotate(360deg); } }
    html.dark .boot-veil-spin { border-color: rgb(107 159 61 / .25); border-top-color: #6b9f3d; }
    @media (prefers-reduced-motion: reduce) {
        .boot-veil { transition: none; }
        /* Slowed, not stopped: the turn is the message that work is happening. */
        .boot-veil-spin { animation-duration: 1.6s; }
    }
</style>
<div id="bootVeil" class="boot-veil" aria-hidden="true"><span class="boot-veil-spin"></span></div>
<script>
    (() => {
        const veil = document.getElementById('bootVeil');
        if (!veil) return;
        let lifted = false;
        const lift = () => {
            if (lifted) return;
            lifted = true;
            veil.classList.add('is-off');
            setTimeout(() => veil.remove(), 400);
        };
        // A frame after the document is parsed: the last body stylesheet gets
        // to paint before anyone sees the page under here.
        const soon = () => requestAnimationFrame(() => requestAnimationFrame(lift));
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', soon);
        else soon();
        // Nothing may sit behind a spinner forever. A stalled asset, a script
        // that threw, an event that never came — it lifts anyway.
        setTimeout(lift, 6000);
        // Back/forward restores show a page that is already whole.
        window.addEventListener('pageshow', lift);
    })();
</script>
