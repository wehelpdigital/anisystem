{{-- A photograph, looked at properly — the one in this app.

     It began on the community wall and the AI chat grew a second one of its
     own, which is how two pictures in the same app came to zoom differently.
     This is the wall's, moved somewhere both can reach it, with the chat's
     one good idea folded in: a way to save the picture.

     Delegated from the document, so it covers pictures that arrive later —
     the next page of the wall, a post just written, an answer landing in a
     chat. Include it anywhere pictures are shown; it guards itself. --}}
@once
<style>
    .post-media img, .post-img, [data-lightbox] img, img[data-lightbox],
    .ai-shots img, .aibubble img, .ai-float-msg .b img, .sai-b img { cursor: zoom-in; }
    /* Her own face is not a photograph, and neither are the small marks she
       writes with. */
    .anee-emo img, img[data-ai-face] { cursor: inherit; }

    .plaza-lightbox { position:fixed; inset:0; z-index:2400; background:rgb(10 14 20 / .88);
        display:flex; align-items:center; justify-content:center; padding:2rem;
        opacity:0; transition:opacity .28s cubic-bezier(.22,1,.36,1); }
    .plaza-lightbox.is-open { opacity:1; }
    .plaza-lightbox.hidden { display:none; }
    .plaza-lightbox img { max-width:min(92vw, 60rem); max-height:86vh; border-radius:.75rem;
        box-shadow:0 24px 64px rgb(0 0 0 / .5); transform:scale(.94);
        transition:transform .28s cubic-bezier(.22,1,.36,1); cursor:default;
        touch-action:none; user-select:none; -webkit-user-drag:none; }
    .plaza-lightbox.is-open img { transform:none; }
    /* The two controls, on one row, out of the picture's way. */
    .plaza-lightbox-bar { position:absolute; top:1rem; right:1rem; z-index:2;
        display:flex; align-items:center; gap:.5rem; }
    .plaza-lightbox-x, .plaza-lightbox-get {
        display:flex; align-items:center; justify-content:center; gap:.4rem;
        min-width:2.5rem; height:2.5rem; padding:0 .75rem;
        border-radius:9999px; border:none; background:rgb(255 255 255 / .12); color:#fff;
        font-size:.8rem; font-weight:800; text-decoration:none;
        cursor:pointer; transition:background .2s ease; }
    .plaza-lightbox-x { padding:0; font-size:1.1rem; }
    .plaza-lightbox-x:hover, .plaza-lightbox-get:hover { background:rgb(255 255 255 / .24); }
    .plaza-lightbox-get svg { width:1.05rem; height:1.05rem; }
    .plaza-lightbox-hint { position:absolute; left:0; right:0; bottom:0; z-index:2;
        padding:.6rem .8rem 1.1rem; text-align:center; pointer-events:none;
        font-size:.72rem; font-weight:700; color:rgb(255 255 255 / .55); }
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
        box.innerHTML = '<div class="plaza-lightbox-bar">'
            + '<a class="plaza-lightbox-get" download href="#" target="_blank" rel="noopener">'
            + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">'
            + '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0l-4-4m4 4l4-4M5 19h14"/></svg>Save</a>'
            + '<button type="button" class="plaza-lightbox-x" aria-label="Close">✕</button>'
            + '</div><img alt="">'
            + '<p class="plaza-lightbox-hint">Pinch or double-tap to zoom · drag to move</p>';
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
        const get = el.querySelector('.plaza-lightbox-get');
        get.setAttribute('href', src);
        // A name the phone's downloads list can be read: the file's own, or a
        // plain one when the URL has nothing usable on the end of it.
        try {
            const last = new URL(src, location.href).pathname.split('/').pop() || '';
            get.setAttribute('download', /\.[a-z0-9]{2,5}$/i.test(last) ? last : 'photo.jpg');
        } catch (_) { get.setAttribute('download', 'photo.jpg'); }
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
    // For anything that wants to open a picture without one to click.
    window.plazaLightbox = open;
    document.addEventListener('click', (e) => {
        if (e.target.closest('.plaza-lightbox')) return;
        /* img[data-lightbox] as well as [data-lightbox] img: the messenger and
           the shared-post quote mark the picture ITSELF, and the descendant
           form quietly never matched them. The chat bubbles are here too, so
           a photograph is a photograph wherever it is shown. */
        const img = e.target.closest('.post-media img, .post-img, [data-lightbox] img, img[data-lightbox],'
            + ' .ai-shots img, .aibubble img, .ai-float-msg .b img, .sai-b img');
        if (!img) return;
        // Not her face, and not the small marks she writes with.
        if (img.closest('.anee-emo') || img.hasAttribute('data-ai-face')) return;
        const link = img.closest('a');
        if (link) e.preventDefault();
        open(img.currentSrc || img.src, img.alt);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && box && !box.classList.contains('hidden')) close();
    });
})();
</script>
@endonce
