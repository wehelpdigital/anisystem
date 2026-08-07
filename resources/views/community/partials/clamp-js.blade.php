{{-- Read-more clamp: collapses long bodies to a few lines and expands them
     like an accordion (house easing). Wire any `.plaza-clamp` wrapper with a
     `.plaza-clamp-body` + `.plaza-clamp-toggle`. Guarded singleton; exposes
     window.plazaClampScan(root) for content added after load. --}}
<script>
(function () {
    if (window.__plazaClampBound) return;
    window.__plazaClampBound = true;

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function setup(wrap) {
        if (wrap.dataset.clampReady) return;
        const body = wrap.querySelector('.plaza-clamp-body');
        const btn = wrap.querySelector('.plaza-clamp-toggle');
        if (!body || !btn) return;
        wrap.dataset.clampReady = '1';

        const collapsed = parseFloat(getComputedStyle(body).maxHeight) || 0;
        // Short enough to fit — no toggle needed, let it show in full.
        if (body.scrollHeight <= collapsed + 4) {
            body.style.maxHeight = 'none';
            btn.remove();
            return;
        }

        btn.classList.remove('hidden');
        btn.textContent = 'Read more';
        btn.addEventListener('click', () => {
            const open = wrap.classList.toggle('is-open');
            if (open) {
                btn.textContent = 'Show less';
                if (reduce) { body.style.maxHeight = 'none'; return; }
                body.style.maxHeight = body.scrollHeight + 'px';
                body.addEventListener('transitionend', function te(e) {
                    if (e.propertyName !== 'max-height') return;
                    body.style.maxHeight = 'none';   // let it reflow freely once open
                    body.removeEventListener('transitionend', te);
                });
            } else {
                btn.textContent = 'Read more';
                if (reduce) { body.style.maxHeight = ''; return; }
                body.style.maxHeight = body.scrollHeight + 'px';   // pin current height
                void body.offsetHeight;                            // force reflow
                requestAnimationFrame(() => { body.style.maxHeight = ''; }); // → CSS collapsed
            }
        });
    }

    function scan(root) { (root || document).querySelectorAll('.plaza-clamp').forEach(setup); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => scan());
    else scan();
    window.plazaClampScan = scan;
})();
</script>
