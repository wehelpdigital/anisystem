{{-- "People you may know" — a band across the page, not a card on it.

     Fetched rather than rendered: the ranking walks friends-of-friends and
     the threads you have commented in, which is slower than a page should
     wait for. Skeleton cards hold the space so nothing jumps.

     Drawn on the wall and on the members page, so it lives here. --}}
<section class="pymk reco-edge" id="pymk" aria-label="People you may know">
    {{-- The heading is the handle: the band is worth having and is not what
         anybody came for, so it folds away and stays folded. "See all" is
         gone — the members page is one tap up in the section bar, and a link
         out of a strip of suggestions is a second way to leave a page nobody
         was leaving. --}}
    <button type="button" class="pymk-head" id="pymkToggle" aria-expanded="true" aria-controls="pymkBody">
        <h2>People you may know</h2>
        <svg class="pymk-chev" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
    </button>
    <div class="pymk-body" id="pymkBody">
        <div class="pymk-wrap">
            <div class="pymk-rail" id="pymkRail">
                @for ($i = 0; $i < 3; $i++)
                    <div class="pymk-skel" aria-hidden="true"></div>
                @endfor
            </div>
            {{-- The next three, and the last three. Drawn only while there is
                 somewhere to go — see the script below. --}}
            <button type="button" class="pymk-arrow is-prev" id="pymkPrev" aria-label="Previous suggestions" hidden>
                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/></svg>
            </button>
            <button type="button" class="pymk-arrow is-next" id="pymkNext" aria-label="More suggestions" hidden>
                <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
            </button>
        </div>
        <p class="pymk-empty hidden" id="pymkEmpty">No suggestions yet — connect with a few co-farmers and this fills up.</p>
    </div>
</section>

@once
@push('scripts')
<script>
/* Folded or not, remembered.
 *
 * The same contract every other panel in the app keeps: the state lives in
 * localStorage under one key, it is read before the first paint the reader
 * sees, and a browser that refuses storage simply starts open. */
(() => {
    const band = document.getElementById('pymk');
    const btn = document.getElementById('pymkToggle');
    if (!band || !btn) return;
    const KEY = 'plaza.pymk.folded';
    let folded = false;
    try { folded = localStorage.getItem(KEY) === '1'; } catch (_) {}
    const paint = () => {
        band.classList.toggle('is-folded', folded);
        btn.setAttribute('aria-expanded', folded ? 'false' : 'true');
    };
    paint();
    btn.addEventListener('click', () => {
        folded = !folded;
        paint();
        try { localStorage.setItem(KEY, folded ? '1' : '0'); } catch (_) {}
        // A rail measured while it was folded measured nothing.
        if (!folded) setTimeout(() => window.__pymkArrows?.(), 340);
    });

    /* The two arrows.
     *
     * The rail deals three cards to a screen and scrolls by a screenful, so
     * a tap moves to the next three rather than nudging a card and a half
     * into view. Each arrow exists only while there is something on that
     * side of the rail — an arrow that scrolls nowhere is a promise the
     * list cannot keep — and the pair is re-measured whenever the rail is
     * filled, resized, or scrolled by a thumb. */
    const rail = document.getElementById('pymkRail');
    const prev = document.getElementById('pymkPrev');
    const next = document.getElementById('pymkNext');
    if (!rail || !prev || !next) return;

    function arrows() {
        const room = rail.scrollWidth - rail.clientWidth;
        // 4px of slack: a fractional column leaves a pixel of scroll behind.
        const atStart = rail.scrollLeft <= 4;
        const atEnd = rail.scrollLeft >= room - 4;
        prev.hidden = room <= 4 || atStart;
        next.hidden = room <= 4 || atEnd;
    }
    window.__pymkArrows = arrows;

    /* A tap moves two cards, not a screenful: the rail shows two and the
       edge of a third, so scrolling by its own width would land mid-card and
       leave the snap to tidy it up. */
    const step = () => {
        const first = rail.firstElementChild;
        if (!first) return Math.max(120, rail.clientWidth);
        return (first.getBoundingClientRect().width + 8) * 2;
    };
    prev.addEventListener('click', () => { rail.scrollLeft -= step(); setTimeout(arrows, 380); });
    next.addEventListener('click', () => { rail.scrollLeft += step(); setTimeout(arrows, 380); });
    rail.addEventListener('scroll', () => { clearTimeout(arrows.t); arrows.t = setTimeout(arrows, 90); }, { passive: true });
    window.addEventListener('resize', arrows, { passive: true });
    // The cards arrive from a fetch, so the count is not known at load.
    new MutationObserver(arrows).observe(rail, { childList: true });
    arrows();
})();
</script>
@endpush
@endonce
