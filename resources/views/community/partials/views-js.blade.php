@once
{{-- Counting what has been looked at.

     A look is an item that came into sight for a moment — not a page that
     mentioned it, and not a card that flew past under a fast thumb. Counted
     once per item per page, so scrolling up and down the same wall does not
     inflate anybody's numbers, while opening the wall again tomorrow does.

     Sent in batches on a short delay, because a scroll brings six cards into
     view in a second and that is one request, not six. --}}
<script>
(function communityViews() {
    if (window.__cvBooted) return;
    window.__cvBooted = true;

    const seen = new Set();     // this page's looks, so none is counted twice
    let pending = [];
    let timer = null;

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    function flush() {
        timer = null;
        const items = pending.splice(0, 40);
        if (!items.length) return;
        fetch(@json(route('community.views')), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json', 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ items }),
        })
            .catch(() => { /* a lost count is a lost count */ });
        /* The look is RECORDED now but SHOWN later: a number that ticks up
         * while you are reading it turns every wall into a slot machine.
         * The count on screen stays what the page opened with; the next
         * open or refresh renders the new total. */
        if (pending.length) timer = setTimeout(flush, 400);
    }

    function saw(kind, id) {
        const key = kind + ':' + id;
        if (seen.has(key)) return;
        seen.add(key);
        pending.push({ kind, id: parseInt(id, 10) });
        if (!timer) timer = setTimeout(flush, 700);
    }

    /* Half a second on screen is a look; a card that flashed past is not.
     *
     * "On screen" cannot be measured as a fraction of the ITEM, which is what
     * intersectionRatio gives. A discussion room's own marker wraps the whole
     * room — 2405px against a 700px phone — so its ratio tops out at 0.29 and
     * a rule of "more than half of it" meant a room's view counter could only
     * ever move from the list of rooms, never from opening one. The same trap
     * was waiting for any long post: a wall card with three photos in it can
     * pass right under a reader's nose and never be half visible at once.
     *
     * So the measure is the visible SLICE against whichever is smaller, the
     * item or the window: half a short card, or half a screenful of a long
     * one. Both mean the same thing — a good look at it. */
    const held = new WeakMap();
    const obs = ('IntersectionObserver' in window) ? new IntersectionObserver((entries) => {
        const vh = window.innerHeight || document.documentElement.clientHeight || 0;
        entries.forEach((en) => {
            const el = en.target;
            const at = el.getAttribute('data-view');
            if (!at) return;
            const [kind, id] = at.split(':');
            const enough = Math.min(en.boundingClientRect.height || 0, vh) * 0.5;
            // Always clear first: several thresholds fire during one scroll,
            // and the old code left every previous timer running.
            clearTimeout(held.get(el));
            if (en.isIntersecting && enough > 0 && en.intersectionRect.height >= enough) {
                held.set(el, setTimeout(() => saw(kind, id), 500));
            }
        });
    }, { threshold: [0, 0.1, 0.25, 0.5, 0.75, 1] }) : null;

    function watch(scope) {
        (scope || document).querySelectorAll('[data-view]:not([data-view-on])').forEach((el) => {
            el.setAttribute('data-view-on', '1');
            if (obs) obs.observe(el);
            // No observer (an old browser): the look counts on sight.
            else { const [k, i] = el.getAttribute('data-view').split(':'); saw(k, i); }
        });
    }

    watch(document);
    // Cards arrive later too — a wall pages in, a story opens.
    if ('MutationObserver' in window) {
        new MutationObserver((recs) => {
            recs.forEach((r) => r.addedNodes.forEach((n) => { if (n.nodeType === 1) watch(n); }));
        }).observe(document.body, { childList: true, subtree: true });
    }

    // What a story counts as: opened, not scrolled past.
    window.smCountView = saw;
})();
</script>
@endonce
