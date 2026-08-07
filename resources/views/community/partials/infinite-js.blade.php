{{-- Turns any [data-infinite] "Load more" button into infinite scroll: when
     the button nears the viewport it auto-clicks, reusing the page's existing
     load handler. When the handler hides the button (no more pages), the
     observer stops. Guarded singleton; re-binds as new triggers are injected. --}}
<script>
(function () {
    if (window.__plazaInfiniteBound) return;
    window.__plazaInfiniteBound = true;

    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            const btn = entry.target;
            // Skip while loading (disabled) or when hidden (no more pages).
            if (btn.disabled || btn.classList.contains('hidden') || btn.offsetParent === null) return;
            btn.click();
        });
    }, { rootMargin: reduce ? '0px' : '700px 0px' });

    function bind() {
        document.querySelectorAll('[data-infinite]:not([data-io-bound])').forEach((el) => {
            el.setAttribute('data-io-bound', '1');
            io.observe(el);
        });
    }
    bind();
    // Load handlers inject new content (and sometimes new triggers); re-scan.
    new MutationObserver(bind).observe(document.documentElement, { childList: true, subtree: true });
})();
</script>
