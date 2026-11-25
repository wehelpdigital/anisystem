{{-- The veil that hides a page being built.

     Several components ship their CSS in a <style> inside the body (the post
     actions, the photo viewer, the mutual sheet). While the HTML streams on a
     slow line, everything above those blocks paints undressed — a page of raw
     text and giant icons, which is what a grower on 3G was seeing.

     Include as the FIRST thing inside <body>. Its dress is in the head (see
     partials.boot-veil-css), already in force, along with the html.booting
     rule that keeps the page's own content out of sight until it is whole.

     Lifted a frame after DOMContentLoaded — the moment every <style> in the
     body has been parsed, which is what "the page is dressed" means here.

     $fast (the public site, 2026-11-05 SEO audit): lifted as soon as the document is parsed, the same promise that every
     <style> has been read, but without waiting for the script bundle (DOMContentLoaded does) or the pictures. Those cost a
     guide about two seconds on a slow phone, every visitor's first look and the figure Google ranks a phone page by. --}}
@php $fast = $fast ?? false; @endphp
<div id="bootVeil" class="boot-veil" aria-hidden="true"><span class="boot-veil-spin"></span></div>
<script>
    (() => {
        const root = document.documentElement;
        const veil = document.getElementById('bootVeil');
        let lifted = false;
        @unless ($fast)
        /* THE SAME CARD, STILL UP (2026-11-26, the owner: on slow phones a tap "refreshes the page before the loader
         * appears"). The page before put its waiting card up on the tap and left it here. Drawn now, before anything
         * else of this page has been read, it is the same words in the same place: one wait, not a card, an empty
         * screen, and another card. Without a note (a fresh visit, a reload from the browser) the ring turns. */
        try {
            const note = JSON.parse(sessionStorage.getItem('nav.card') || 'null');
            sessionStorage.removeItem('nav.card');
            if (note && note.html && Date.now() - note.at < 45000) {
                veil.innerHTML = note.html;
                veil.classList.add('has-card');
                window.__navAt = note.at;
            }
        } catch (_) { /* the ring, then */ }
        @endunless
        const lift = () => {
            if (lifted) return;
            lifted = true;
            // The content is shown first, then the veil fades off it.
            root.classList.remove('booting');
            if (!veil) return;
            veil.classList.add('is-off');
            setTimeout(() => veil.remove(), 400);
        };
        @if ($fast)
        /* THE FIRST SCREEN, SOONER (2026-11-18 speed audit: a phone waited 5.7 s for the home page's hero, the whole
         * 1.2 MB document had to be parsed before the veil lifted). A public page calls this right after its first
         * screen; nothing that streams in after can show undressed, PublicSeoTags moves every body <style> to the head.
         * Measured: the hero showed in half the time; hiding the rest until the end, or content-visibility on it, each
         * cost a phone a second or more of blocked time when it was revealed, so the rest simply streams in below. */
        window.__bootEarly = lift;
        const parsed = () => requestAnimationFrame(lift);
        if (document.readyState === 'loading') {
            document.addEventListener('readystatechange', function ready() {
                if (document.readyState === 'loading') return;
                document.removeEventListener('readystatechange', ready);
                parsed();
            });
        } else parsed();
        @else
        /* AND THE PICTURES, NOT ONLY THE MARKUP.
         *
         * Lifting a frame after the document is parsed shows a page that is
         * dressed but not yet illustrated: the logo and the tile icons are
         * still in flight, so the first thing a farmer sees is a broken-image
         * glyph where the mark should be. The veil now waits for the pictures
         * that are already on the page and NOT lazy — the chrome, in other
         * words, not the forty photos down the feed.
         *
         * Capped hard at 2.5 seconds of extra waiting. A spinner held by one
         * slow file is worse than one icon arriving late, and the 6-second
         * backstop below still has the last word either way. */
        const dressed = () => {
            // Only the ones that would be seen: a picture inside a sheet nobody has opened held the season's board for a
            // second and a half on a slow line (2026-11-26).
            const seen = (i) => { const r = i.getBoundingClientRect(); return r.width > 0 && r.height > 0 && r.top < innerHeight && r.bottom > 0 && r.left < innerWidth && r.right > 0; };
            const shots = [...document.images].filter((i) => i.loading !== 'lazy' && !i.complete && i.src && seen(i));
            if (!shots.length) return Promise.resolve();

            return Promise.race([
                Promise.all(shots.map((i) => new Promise((done) => {
                    i.addEventListener('load', done, { once: true });
                    i.addEventListener('error', done, { once: true });
                }))),
                new Promise((done) => setTimeout(done, 2500)),
            ]);
        };
        // A frame after the document is parsed: the last body stylesheet gets
        // to paint before anyone sees the page under here. Then the artwork.
        /* The season's board and its modules have a wait of their own. While one is still up, this card stays in front
           of it: taking it off would only show another card behind it. Eight seconds at most, then the page's own. */
        const within = () => {
            if (document.body.classList.contains('module-booting')) return true;
            const b = document.getElementById('boardVeil');
            return !!b && !b.hidden && !b.classList.contains('is-done') && getComputedStyle(b).display !== 'none';
        };
        const through = () => {
            if (!veil.classList.contains('has-card')) { lift(); return; }
            const until = performance.now() + 8000;
            const look = () => { (within() && performance.now() < until) ? setTimeout(look, 120) : lift(); };
            look();
        };
        const soon = () => requestAnimationFrame(() => requestAnimationFrame(
            () => { dressed().then(through, through); }
        ));
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', soon);
        else soon();
        @endif
        // Nothing may sit behind a spinner forever, and nothing may stay
        // hidden. A stalled asset, a script that threw, an event that never
        // came — it lifts anyway.
        @if ($fast)
        setTimeout(lift, 6000);
        @else
        /* Not while the page is still being read: on a slow phone six seconds was sooner than the last of it, and what
           came out from behind the veil was half a page. */
        const giveUp = () => {
            if (document.readyState === 'loading' && performance.now() < 20000) { setTimeout(giveUp, 1000); return; }
            setTimeout(lift, 2600);   // the pictures' own cap, a breath later
        };
        setTimeout(giveUp, 6000);
        @endif
        // Back/forward restores show a page that is already whole.
        window.addEventListener('pageshow', (e) => { if (e.persisted || @json($fast)) lift(); });
    })();
</script>
