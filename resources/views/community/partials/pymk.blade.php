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

})();
</script>
@endpush
@endonce
