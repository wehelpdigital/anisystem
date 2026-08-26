{{-- The veil that hides a page being built.

     Several components ship their CSS in a <style> inside the body (the post
     actions, the photo viewer, the mutual sheet). While the HTML streams on a
     slow line, everything above those blocks paints undressed — a page of raw
     text and giant icons, which is what a grower on 3G was seeing.

     Include as the FIRST thing inside <body>. Its dress is in the head (see
     partials.boot-veil-css), already in force, along with the html.booting
     rule that keeps the page's own content out of sight until it is whole.

     Lifted a frame after DOMContentLoaded — the moment every <style> in the
     body has been parsed, which is what "the page is dressed" means here. --}}
<div id="bootVeil" class="boot-veil" aria-hidden="true"><span class="boot-veil-spin"></span></div>
<script>
    (() => {
        const root = document.documentElement;
        const veil = document.getElementById('bootVeil');
        let lifted = false;
        const lift = () => {
            if (lifted) return;
            lifted = true;
            // The content is shown first, then the veil fades off it.
            root.classList.remove('booting');
            if (!veil) return;
            veil.classList.add('is-off');
            setTimeout(() => veil.remove(), 400);
        };
        // A frame after the document is parsed: the last body stylesheet gets
        // to paint before anyone sees the page under here.
        const soon = () => requestAnimationFrame(() => requestAnimationFrame(lift));
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', soon);
        else soon();
        // Nothing may sit behind a spinner forever, and nothing may stay
        // hidden. A stalled asset, a script that threw, an event that never
        // came — it lifts anyway.
        setTimeout(lift, 6000);
        // Back/forward restores show a page that is already whole.
        window.addEventListener('pageshow', lift);
    })();
</script>
