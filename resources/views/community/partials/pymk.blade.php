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
        {{-- On a desk the rail is driven by two buttons, not by a grabbed
             mouse: grab-and-slide is a gesture nobody is told about, and on a
             wide screen there is room to say it out loud. `drag-scroll` is
             gone with it — that behaviour only ever bound a mouse, so a
             finger still pans the rail exactly as before. --}}
        <div class="pymk-wrap">
            <button type="button" class="pymk-arrow is-prev" id="pymkPrev" aria-label="Show earlier suggestions" tabindex="-1">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 6l-6 6 6 6"/></svg>
            </button>
            <div class="pymk-rail" id="pymkRail">
                @for ($i = 0; $i < 3; $i++)
                    <div class="pymk-skel" aria-hidden="true"></div>
                @endfor
            </div>
            <button type="button" class="pymk-arrow is-next" id="pymkNext" aria-label="Show more suggestions" tabindex="-1">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6"/></svg>
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
    });

    /* The two arrows, on a desk only.
     *
     * They are not decoration: each one is only there while there is
     * something in that direction, so they arrive and leave as the rail
     * moves — faded and eased in on the house curve rather than blinking
     * into place. CSS keeps them off a phone entirely; this keeps them
     * honest about where the rail actually is.
     *
     * The rail fills from a fetch long after this runs, so its width is
     * watched rather than measured once. */
    const rail = document.getElementById('pymkRail');
    const prev = document.getElementById('pymkPrev');
    const next = document.getElementById('pymkNext');
    if (!rail || !prev || !next) return;

    const desk = window.matchMedia('(min-width: 768px)');
    const paintArrows = () => {
        if (!desk.matches || folded) {
            prev.classList.remove('is-on');
            next.classList.remove('is-on');

            return;
        }
        const room = rail.scrollWidth - rail.clientWidth;
        // A rail that does not scroll gets no arrows at all.
        prev.classList.toggle('is-on', room > 4 && rail.scrollLeft > 4);
        next.classList.toggle('is-on', room > 4 && rail.scrollLeft < room - 4);
    };

    // One nudge is most of a screen, so a click lands on whole new faces
    // rather than shuffling the row along by a sliver.
    const step = (dir) => rail.scrollBy({ left: dir * Math.max(160, rail.clientWidth * 0.82), behavior: 'smooth' });
    prev.addEventListener('click', () => step(-1));
    next.addEventListener('click', () => step(1));
    rail.addEventListener('scroll', paintArrows, { passive: true });
    window.addEventListener('resize', paintArrows);
    desk.addEventListener?.('change', paintArrows);
    btn.addEventListener('click', () => setTimeout(paintArrows, 340));
    if (window.ResizeObserver) new ResizeObserver(paintArrows).observe(rail);
    // The fetch replaces the skeletons without resizing the rail itself, so
    // the observer can stay quiet — watch the children too.
    if (window.MutationObserver) new MutationObserver(paintArrows).observe(rail, { childList: true });
    paintArrows();
})();
</script>
@endpush
@endonce
