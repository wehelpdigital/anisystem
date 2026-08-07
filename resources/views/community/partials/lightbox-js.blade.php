{{-- Click-to-expand lightbox for posted photos/GIFs. Delegated on
     .post-media img / .post-img / [data-lightbox] img so content added later
     still works. Self-contained (styles below) — include once per page
     (guarded); safe on pages without plaza-css. --}}
<style>
    .post-media img, .post-img, [data-lightbox] img { cursor: zoom-in; }
    .plaza-lightbox { position:fixed; inset:0; z-index:120; background:rgb(10 14 20 / .88);
        display:flex; align-items:center; justify-content:center; padding:2rem;
        opacity:0; transition:opacity .28s cubic-bezier(.22,1,.36,1); }
    .plaza-lightbox.is-open { opacity:1; }
    .plaza-lightbox.hidden { display:none; }
    .plaza-lightbox img { max-width:min(92vw, 60rem); max-height:86vh; border-radius:.75rem;
        box-shadow:0 24px 64px rgb(0 0 0 / .5); transform:scale(.94);
        transition:transform .28s cubic-bezier(.22,1,.36,1); cursor:default; }
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
        return box;
    }
    function open(src, alt) {
        const el = ensure();
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
        const img = e.target.closest('.post-media img, .post-img, [data-lightbox] img');
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
