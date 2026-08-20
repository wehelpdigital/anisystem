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
            .then((r) => r.json())
            .then((j) => {
                const counts = (j.data && j.data.counts) || {};
                // Show the new number where it is displayed, so a reader sees
                // their own look land rather than waiting for a reload.
                Object.keys(counts).forEach((kind) => {
                    Object.entries(counts[kind] || {}).forEach(([id, n]) => {
                        document.querySelectorAll(`[data-view-count="${kind}:${id}"]`)
                            .forEach((el) => { el.textContent = n; });
                    });
                });
            })
            .catch(() => { /* a lost count is a lost count */ });
        if (pending.length) timer = setTimeout(flush, 400);
    }

    function saw(kind, id) {
        const key = kind + ':' + id;
        if (seen.has(key)) return;
        seen.add(key);
        pending.push({ kind, id: parseInt(id, 10) });
        if (!timer) timer = setTimeout(flush, 700);
    }

    /* Half a second on screen is a look; a card that flashed past is not. */
    const held = new WeakMap();
    const obs = ('IntersectionObserver' in window) ? new IntersectionObserver((entries) => {
        entries.forEach((en) => {
            const el = en.target;
            const at = el.getAttribute('data-view');
            if (!at) return;
            const [kind, id] = at.split(':');
            if (en.isIntersecting && en.intersectionRatio > 0.5) {
                held.set(el, setTimeout(() => saw(kind, id), 500));
            } else {
                clearTimeout(held.get(el));
            }
        });
    }, { threshold: [0, 0.5, 1] }) : null;

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
