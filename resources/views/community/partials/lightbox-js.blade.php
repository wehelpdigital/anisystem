{{-- The post carousel, and the app's picture lightbox beside it.

     The two are about the same pictures and every page that draws posts
     already carries this file. The lightbox itself lives in
     partials/photo-lightbox so the chats can show a photograph the same way
     the wall does — one lightbox in this app, not one per feature. --}}
@include('partials.photo-lightbox')
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
