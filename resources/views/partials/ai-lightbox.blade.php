{{-- A photo in a chat, looked at properly.

     Somebody photographs a leaf, sends it, and then wants to see the leaf —
     which in a chat bubble is 260 pixels tall with a bubble's rounding cut
     into it. Tapping one opens it on the whole screen, where it can be pinched
     into and dragged around, and saved.

     Delegated from the document, so it covers every photo in every chat —
     the ones rendered server-side with the history and the ones written into
     the thread as a string when an answer lands.

     Zoom is done here rather than left to the browser because a page cannot
     hand pinch-zoom to one element: the gesture belongs to the document, and
     inside an app shell the document is not allowed to move. --}}
@once
<style>
    .ailb { position: fixed; inset: 0; z-index: 2400; display: none;
        background: rgb(8 12 6 / .94); touch-action: none; overscroll-behavior: contain; }
    .ailb.is-on { display: block; }
    .ailb-stage { position: absolute; inset: 0; overflow: hidden;
        display: flex; align-items: center; justify-content: center; }
    .ailb-img { max-width: 100%; max-height: 100%; user-select: none; -webkit-user-drag: none;
        transform-origin: 0 0; will-change: transform; }
    .ailb-bar { position: absolute; left: 0; right: 0; top: 0; z-index: 2;
        display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        padding: .7rem .8rem; background: linear-gradient(rgb(0 0 0 / .55), transparent); }
    .ailb-btn { display: inline-flex; align-items: center; justify-content: center; gap: .4rem;
        min-width: 2.4rem; height: 2.4rem; padding: 0 .7rem; border: 0; border-radius: 999px;
        background: rgb(255 255 255 / .16); color: #fff; cursor: pointer;
        font-size: .78rem; font-weight: 800;
        transition: background .28s cubic-bezier(.22,1,.36,1); }
    .ailb-btn:hover { background: rgb(255 255 255 / .28); }
    .ailb-btn svg { width: 1.1rem; height: 1.1rem; }
    .ailb-hint { position: absolute; left: 0; right: 0; bottom: 0; z-index: 2;
        padding: .6rem .8rem 1.1rem; text-align: center;
        font-size: .72rem; font-weight: 700; color: rgb(255 255 255 / .6);
        background: linear-gradient(transparent, rgb(0 0 0 / .5)); pointer-events: none; }
    @media (prefers-reduced-motion: reduce) { .ailb-btn { transition: none; } }
</style>

<div class="ailb" id="aiLbBox" role="dialog" aria-modal="true" aria-label="Photo">
    <div class="ailb-stage" id="aiLbStage">
        <img class="ailb-img" id="aiLbImg" alt="">
    </div>
    <div class="ailb-bar">
        <button type="button" class="ailb-btn" id="aiLbClose" aria-label="Close">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <a class="ailb-btn" id="aiLbGet" download href="#" target="_blank" rel="noopener">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0l-4-4m4 4l4-4M5 19h14"/></svg>
            Save
        </a>
    </div>
    <p class="ailb-hint">Pinch or double-tap to zoom · drag to move</p>
</div>

<script>
(() => {
    /* Not "if (window.aiLightbox)": a browser hangs every id in the document
       off window, so an element called aiLightbox WAS window.aiLightbox and
       this guard returned before the function it was guarding existed. The
       flag is a name nothing in the markup can take. */
    if (window.__aiLbWired) return;
    window.__aiLbWired = true;
    const box = document.getElementById('aiLbBox');
    const stage = document.getElementById('aiLbStage');
    const img = document.getElementById('aiLbImg');
    const get = document.getElementById('aiLbGet');
    if (!box) return;

    /* Where the picture is: a scale and an offset, applied as one transform.
       Kept as plain numbers rather than read back off the element, because
       reading a transform to change it is how a zoom ends up drifting. */
    let z = 1, x = 0, y = 0, natural = { w: 0, h: 0 };
    const apply = () => { img.style.transform = `translate(${x}px, ${y}px) scale(${z})`; };

    const fit = () => {
        z = 1; x = 0; y = 0;
        img.style.maxWidth = '100%';
        img.style.maxHeight = '100%';
        img.style.transformOrigin = '50% 50%';
        apply();
    };

    /* Zooming about a point on the screen, so what is under the fingers
       stays under them — the whole difference between a zoom that feels like
       a magnifier and one that feels like a slider. */
    const zoomAt = (px, py, next) => {
        const r = img.getBoundingClientRect();
        const cx = px - r.left, cy = py - r.top;
        const k = next / z;
        x -= (k - 1) * cx;
        y -= (k - 1) * cy;
        z = next;
        clamp();
        apply();
    };

    const clamp = () => {
        z = Math.min(6, Math.max(1, z));
        if (z === 1) { x = 0; y = 0; }
    };

    window.aiLightbox = (src, alt) => {
        if (!src) return;
        img.src = src;
        img.alt = alt || 'Photo';
        get.setAttribute('href', src);
        // A name the phone's downloads list can be read: the file's own, or a
        // plain one when the URL has nothing usable on the end of it.
        try {
            const last = new URL(src, location.href).pathname.split('/').pop() || '';
            get.setAttribute('download', /\.[a-z0-9]{2,5}$/i.test(last) ? last : 'photo.jpg');
        } catch (_) { get.setAttribute('download', 'photo.jpg'); }
        fit();
        /* Moved to the body the first time it is used.
         *
         * This markup ships with the chat partials, and a chat can sit inside
         * a pane that has a transform or an overflow on it — either of which
         * makes position:fixed mean "fixed inside THAT", so a full-screen
         * overlay opened inside one is neither full-screen nor on top. The
         * sheets in this app move themselves for the same reason. */
        if (box.parentElement !== document.body) document.body.appendChild(box);
        box.classList.add('is-on');
        document.documentElement.style.overflow = 'hidden';
    };
    const close = () => {
        box.classList.remove('is-on');
        document.documentElement.style.overflow = '';
        img.removeAttribute('src');
    };
    document.getElementById('aiLbClose')?.addEventListener('click', close);
    box.addEventListener('click', (e) => { if (e.target === box || e.target === stage) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && box.classList.contains('is-on')) close(); });

    /* ---- the gestures ---- */
    const pts = new Map();
    let start = null;

    stage.addEventListener('pointerdown', (e) => {
        if (e.target === get || get.contains(e.target)) return;
        stage.setPointerCapture?.(e.pointerId);
        pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
        if (pts.size === 2) {
            const [a, b] = [...pts.values()];
            start = { d: Math.hypot(a.x - b.x, a.y - b.y), z, mx: (a.x + b.x) / 2, my: (a.y + b.y) / 2 };
        }
    });
    stage.addEventListener('pointermove', (e) => {
        const p = pts.get(e.pointerId);
        if (!p) return;
        const dx = e.clientX - p.x, dy = e.clientY - p.y;
        pts.set(e.pointerId, { x: e.clientX, y: e.clientY });

        if (pts.size >= 2 && start) {
            const [a, b] = [...pts.values()];
            const d = Math.hypot(a.x - b.x, a.y - b.y);
            if (start.d > 0) zoomAt(start.mx, start.my, start.z * (d / start.d));

            return;
        }
        // One finger drags, but only when there is something to drag: at rest
        // the picture fills its frame and moving it would just slide it off.
        if (z > 1) { x += dx; y += dy; apply(); }
    });
    const up = (e) => {
        pts.delete(e.pointerId);
        if (pts.size < 2) start = null;
    };
    stage.addEventListener('pointerup', up);
    stage.addEventListener('pointercancel', up);

    // Double-tap / double-click: in to three, or all the way back out.
    let lastTap = 0;
    stage.addEventListener('click', (e) => {
        if (e.target !== img) return;
        const now = Date.now();
        if (now - lastTap < 320) { zoomAt(e.clientX, e.clientY, z > 1.05 ? 1 : 3); lastTap = 0; return; }
        lastTap = now;
    });
    stage.addEventListener('wheel', (e) => {
        e.preventDefault();
        zoomAt(e.clientX, e.clientY, z * (e.deltaY < 0 ? 1.14 : 1 / 1.14));
    }, { passive: false });

    /* Every photo in every chat, however it got onto the page. */
    document.addEventListener('click', (e) => {
        const el = e.target.closest('.ai-shots img, .aibubble img, .ai-float-msg .b img, .sai-b img, [data-ai-photo]');
        if (!el || !el.getAttribute('src')) return;
        // Not her own face, and not the small marks she writes with.
        if (el.closest('.anee-emo') || el.hasAttribute('data-ai-face')) return;
        e.preventDefault();
        window.aiLightbox(el.currentSrc || el.src, el.alt);
    });
})();
</script>
@endonce
