{{-- Click-to-expand lightbox for posted photos/GIFs. Delegated on
     .post-media img / .post-img / [data-lightbox] img so content added later
     still works. Self-contained (styles below) — include once per page
     (guarded); safe on pages without plaza-css. --}}
<style>
    .post-media img, .post-img, [data-lightbox] img, img[data-lightbox] { cursor: zoom-in; }
    .plaza-lightbox { position:fixed; inset:0; z-index:120; background:rgb(10 14 20 / .88);
        display:flex; align-items:center; justify-content:center; padding:2rem;
        opacity:0; transition:opacity .28s cubic-bezier(.22,1,.36,1); }
    .plaza-lightbox.is-open { opacity:1; }
    .plaza-lightbox.hidden { display:none; }
    .plaza-lightbox img { max-width:min(92vw, 60rem); max-height:86vh; border-radius:.75rem;
        box-shadow:0 24px 64px rgb(0 0 0 / .5); transform:scale(.94);
        transition:transform .28s cubic-bezier(.22,1,.36,1); cursor:default;
        touch-action:none; user-select:none; -webkit-user-drag:none; }
    .plaza-lightbox.is-open img { transform:none; }
    .plaza-lightbox-x { position:absolute; top:1rem; right:1rem; width:2.5rem; height:2.5rem;
        border-radius:9999px; border:none; background:rgb(255 255 255 / .12); color:#fff;
        font-size:1.1rem; display:flex; align-items:center; justify-content:center;
        cursor:pointer; transition:background .2s ease; }
    .plaza-lightbox-x:hover { background:rgb(255 255 255 / .24); }
    @media (prefers-reduced-motion: reduce) {
        .plaza-lightbox, .plaza-lightbox img { transition:none !important; }
    }
</style>
<script>
/* The post carousel.
 *
 * Lives beside the lightbox because the two are about the same pictures and
 * every page that draws posts already carries this file.
 *
 * One ticker for the whole page rather than a timer per post: a wall can hold
 * thirty carousels, and thirty intervals is thirty wakeups a second on a
 * phone. Only the ones actually on the screen advance, and only while nobody
 * is touching them — a picture that moves while you are looking at it is a
 * picture you cannot look at. Touch it and it stops for good: you are driving
 * now.
 */
(function () {
    if (window.__plazaCarouselBound) return;
    window.__plazaCarouselBound = true;

    const SLOW = 6000;                 // a slide about every six seconds
    const still = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const live = new Set();            // carousels currently on screen

    const seen = new IntersectionObserver((rows) => {
        rows.forEach((r) => {
            if (r.isIntersecting) live.add(r.target);
            else live.delete(r.target);
        });
    }, { threshold: .35 });

    function paint(box) {
        const track = box.querySelector('.pc-track');
        const dots = box.querySelectorAll('.pc-dot');
        const count = box.querySelector('.pc-count b');
        if (!track || !track.children.length) return;
        const at = Math.round(track.scrollLeft / Math.max(1, track.clientWidth));
        dots.forEach((d, i) => d.classList.toggle('is-on', i === at));
        if (count) count.textContent = String(Math.min(at + 1, track.children.length));
    }

    function wire(box) {
        if (box.dataset.carouselOn) return;
        box.dataset.carouselOn = '1';
        const track = box.querySelector('.pc-track');
        if (!track) return;

        let held = false;
        const hold = () => { held = true; };
        // A hand on the track, a wheel over it, or a key in it: all the same
        // answer, and none of them undone — once somebody has taken hold of
        // a set of pictures, it is theirs.
        ['pointerdown', 'touchstart', 'wheel', 'keydown'].forEach((ev) =>
            track.addEventListener(ev, hold, { passive: true }));
        box.__held = () => held;

        let t = null;
        track.addEventListener('scroll', () => {
            clearTimeout(t);
            t = setTimeout(() => paint(box), 80);
        }, { passive: true });

        // The dots are a place to go, not only a place-marker.
        box.querySelectorAll('.pc-dot').forEach((dot, i) => {
            dot.addEventListener('click', () => {
                hold();
                track.scrollLeft = i * track.clientWidth;
            });
        });

        seen.observe(box);
        paint(box);
    }

    function scan() {
        document.querySelectorAll('[data-shots]:not([data-carousel-on])').forEach(wire);
    }

    if (!still) {
        setInterval(() => {
            live.forEach((box) => {
                if (box.__held && box.__held()) return;
                // A slider of films stays where it was put: sliding away from
                // a clip somebody is watching is worse than not sliding.
                if (box.hasAttribute('data-noauto')) return;
                const track = box.querySelector('.pc-track');
                if (!track || track.children.length < 2) return;
                const w = Math.max(1, track.clientWidth);
                const at = Math.round(track.scrollLeft / w);
                const next = at + 1 >= track.children.length ? 0 : at + 1;
                track.scrollLeft = next * w;
            });
        }, SLOW);
    }

    scan();
    // Posts arrive from a fetch — the next page of the wall, a post just
    // written — so the page is watched rather than scanned once.
    new MutationObserver(scan).observe(document.documentElement, { childList: true, subtree: true });
})();
</script>
<script>
(function () {
    if (window.__plazaLightboxBound) return;
    window.__plazaLightboxBound = true;

    let box = null;
    function ensure() {
        if (box) return box;
        box = document.createElement('div');
        box.className = 'plaza-lightbox hidden';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.innerHTML = '<button type="button" class="plaza-lightbox-x" aria-label="Close">✕</button><img alt="">';
        document.body.appendChild(box);
        box.addEventListener('click', (e) => {
            if (e.target === box || e.target.closest('.plaza-lightbox-x')) close();
        });
        // Zoom lives on the image: pinch or wheel to any level, double-tap
        // to jump in and out, one finger pans while zoomed.
        const img = box.querySelector('img');
        let scale = 1, tx = 0, ty = 0, start = null;
        const ptrs = new Map();
        function apply(animated) {
            img.style.transition = animated ? '' : 'none';
            img.style.transform = (scale === 1 && !tx && !ty) ? '' : 'translate(' + tx + 'px, ' + ty + 'px) scale(' + scale + ')';
        }
        box.__reset = () => { scale = 1; tx = 0; ty = 0; start = null; ptrs.clear(); img.style.transform = ''; img.style.transition = ''; };
        img.addEventListener('dblclick', (e) => {
            e.preventDefault();
            if (scale > 1) { scale = 1; tx = 0; ty = 0; } else { scale = 2.5; }
            apply(true);
        });
        box.addEventListener('wheel', (e) => {
            e.preventDefault();
            scale = Math.min(4, Math.max(1, scale * (e.deltaY < 0 ? 1.15 : 1 / 1.15)));
            if (scale === 1) { tx = 0; ty = 0; }
            apply(true);
        }, { passive: false });
        img.addEventListener('pointerdown', (e) => {
            ptrs.set(e.pointerId, { x: e.clientX, y: e.clientY });
            start = { scale, tx, ty, pts: [...ptrs.values()].map((p) => ({ ...p })) };
            if (img.setPointerCapture) img.setPointerCapture(e.pointerId);
            if (scale > 1 || ptrs.size === 2) e.preventDefault();
        });
        img.addEventListener('pointermove', (e) => {
            if (!ptrs.has(e.pointerId) || !start) return;
            ptrs.set(e.pointerId, { x: e.clientX, y: e.clientY });
            const cur = [...ptrs.values()];
            if (cur.length === 2 && start.pts.length === 2) {
                const d0 = Math.hypot(start.pts[0].x - start.pts[1].x, start.pts[0].y - start.pts[1].y) || 1;
                const d1 = Math.hypot(cur[0].x - cur[1].x, cur[0].y - cur[1].y);
                scale = Math.min(4, Math.max(1, start.scale * (d1 / d0)));
                apply(false);
            } else if (cur.length === 1 && scale > 1) {
                tx = start.tx + (cur[0].x - start.pts[0].x);
                ty = start.ty + (cur[0].y - start.pts[0].y);
                apply(false);
            }
        });
        const lift = (e) => {
            ptrs.delete(e.pointerId);
            start = ptrs.size ? { scale, tx, ty, pts: [...ptrs.values()].map((p) => ({ ...p })) } : null;
            if (scale === 1 && (tx || ty)) { tx = 0; ty = 0; apply(true); }
        };
        img.addEventListener('pointerup', lift);
        img.addEventListener('pointercancel', lift);
        return box;
    }
    function open(src, alt) {
        const el = ensure();
        if (el.__reset) el.__reset();
        const img = el.querySelector('img');
        img.src = src;
        img.alt = alt || '';
        el.classList.remove('hidden');
        void el.offsetWidth;
        el.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        if (!box) return;
        box.classList.remove('is-open');
        document.body.style.overflow = '';
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) box.classList.add('hidden');
        else setTimeout(() => box.classList.add('hidden'), 300);
    }
    document.addEventListener('click', (e) => {
        if (e.target.closest('.plaza-lightbox')) return;
        /* img[data-lightbox] as well as [data-lightbox] img: the messenger and
           the shared-post quote mark the picture ITSELF, and the descendant
           form quietly never matched them. */
        const img = e.target.closest('.post-media img, .post-img, [data-lightbox] img, img[data-lightbox]');
        if (!img) return;
        const link = img.closest('a');
        if (link) e.preventDefault();
        open(img.currentSrc || img.src, img.alt);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && box && !box.classList.contains('hidden')) close();
    });
})();
</script>
