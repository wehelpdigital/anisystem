{{-- Shared note ATTACHMENTS: canonical thumbnail markup + an expandable
     lightbox. Include ONCE per page that shows notes (activities/hub/notebook).
     Exposes window.noteMediaThumb(m, extra) and window.noteMediaCells(arr).
     Any `.nm[data-lb-url]` or `.na[data-lb-url]` element opens it on click. --}}
<div class="note-lb" id="noteLightbox" aria-hidden="true">
    {{-- Keeping a copy is the other half of looking at one: a photo of a
         receipt, a drawing to send to the co-op, a frame the AI commented on.
         The link carries `download`, so the browser saves rather than
         navigates, and it is here rather than on every thumbnail in the app
         because everything opens through this one lightbox. --}}
    {{-- No target="_blank": that is a navigation, and a navigation is what
         this button was doing instead of saving. The href points at our own
         save route, which re-serves the bytes with an attachment header — the
         download attribute alone is ignored for a cross-origin file, and half
         this app's media is served from the mother site. --}}
    <a class="note-lb-dl" id="noteLbDownload" href="#" download rel="noopener" title="Save to this device" aria-label="Download">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0 0l-3.5-3.5M12 14l3.5-3.5M5 19h14"/></svg>
    </a>
    <button type="button" class="note-lb-close" aria-label="Close">✕</button>
    <div class="note-lb-stage"></div>
</div>

<style>
    /* Attachment thumbnail (square icon) */
    /* Attachment chips: what a note carries, said in words. See
       note-attachments.blade.php for why these replaced thumbnails. */
    .note-atts { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .5rem; }
    .na { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .6rem;
        border: 1px solid var(--color-gray-200, #e5e7eb); border-radius: 999px; background: var(--color-white, #fff);
        font-size: .75rem; font-weight: 700; color: #4b5563; cursor: pointer; text-decoration: none;
        transition: background .2s ease, border-color .2s ease, color .2s ease; }
    .na svg { width: .95rem; height: .95rem; flex: 0 0 auto; }
    .na:hover { background: #f3f8ec; border-color: #a8cc7e; color: #3d6823; }
    .na-photo svg { color: #2563eb; }
    .na-video svg { color: #b91c1c; }
    .na-draw svg { color: #7c3aed; }
    .na-map svg { color: #15803d; }
    html.dark .na { background: #1c2416; border-color: #2b3a1c; color: #cdd8c0; }
    html.dark .na:hover { background: #24301a; border-color: #3d6823; }
    @media (prefers-reduced-motion: reduce) { .na { transition: none; } }

    .nm { position: relative; aspect-ratio: 1; border-radius: .5rem; overflow: hidden; background: #1c2230; cursor: pointer; }
    .nm img, .nm video { width: 100%; height: 100%; object-fit: cover; display: block; }
    /* Loading shimmer behind the image until it decodes — no broken-image flash. */
    .nm::before { content: ''; position: absolute; inset: 0; background: linear-gradient(100deg, rgba(255,255,255,0) 20%, rgba(255,255,255,.14) 50%, rgba(255,255,255,0) 80%); background-size: 220% 100%; animation: nmShimmer 1.15s linear infinite; pointer-events: none; }
    .nm:not(:has(img))::before, .nm:has(img.is-loaded)::before { display: none; }
    .nm img { opacity: 0; transition: opacity .3s ease; }
    .nm img.is-loaded { opacity: 1; }
    @keyframes nmShimmer { from { background-position: 220% 0; } to { background-position: -220% 0; } }
    .nm-video { background: linear-gradient(135deg, #263041, #111827); }
    /* A saved map is a place, not a picture: linking to the module beats a
       thumbnail of it, which cannot be panned, measured or drawn on — and
       which vanishes with the file if the disk is wiped. */
    .nm-map { display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: .25rem; background: linear-gradient(135deg, #1f3a2b, #142218); color: #cfe8d6;
        text-decoration: none; padding: .4rem; text-align: center; }
    .nm-map svg { width: 1.6rem; height: 1.6rem; }
    .nm-map span { font-size: .68rem; font-weight: 800; line-height: 1.2; }
    .nm-map::before { display: none; }
    /* A drawing wears a pencil, so an editable one is told apart from a flat
       picture at a glance. */
    .nm-badge { position: absolute; left: .2rem; bottom: .2rem; width: 1.35rem; height: 1.35rem;
        border-radius: 999px; background: rgb(17 24 39 / .72); color: #fff;
        display: inline-flex; align-items: center; justify-content: center; z-index: 2; pointer-events: none; }
    .nm-badge svg { width: .85rem; height: .85rem; }
    /* An image that never arrives used to shimmer forever, which reads as
       "still loading" long after the file has gone. */
    .nm.is-gone::before { display: none; }
    .nm.is-gone { display: flex; align-items: center; justify-content: center; cursor: default; }
    .nm.is-gone::after { content: 'File missing'; font-size: .64rem; font-weight: 700; color: #94a3b8; padding: .3rem; text-align: center; }
    @media (prefers-reduced-motion: reduce) { .nm::before { animation: none; } .nm img { transition: none; } }
    .nm-play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: #fff; pointer-events: none; text-shadow: 0 1px 6px rgb(0 0 0 / .6); }
    .nm-play svg { width: 2rem; height: 2rem; }
    .nm .rm { position: absolute; top: .2rem; right: .2rem; width: 1.7rem; height: 1.7rem; border-radius: 999px; background: rgb(17 24 39 / .72); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: .9rem; line-height: 1; cursor: pointer; z-index: 2; }
    /* Expandable lightbox */
    .note-lb { position: fixed; inset: 0; z-index: 160; display: none; align-items: center; justify-content: center; background: rgb(0 0 0 / .86); padding: 1rem; }
    .note-lb.is-open { display: flex; animation: noteLbFade .2s ease both; }
    @keyframes noteLbFade { from { opacity: 0; } to { opacity: 1; } }
    @media (prefers-reduced-motion: reduce) { .note-lb.is-open { animation: none; } }
    .note-lb-stage { display: flex; align-items: center; justify-content: center; }
    .note-lb-stage img, .note-lb-stage video { max-width: 94vw; max-height: 90vh; border-radius: .5rem; object-fit: contain; box-shadow: 0 20px 60px -20px rgb(0 0 0 / .8); }
    .note-lb-close { position: fixed; top: 1rem; right: 1rem; width: 2.75rem; height: 2.75rem; border-radius: 999px; background: rgb(255 255 255 / .16); color: #fff; font-size: 1.25rem; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
    .note-lb-close:hover { background: rgb(255 255 255 / .28); }
    .note-lb-dl { position: fixed; top: 1rem; right: 4.25rem; width: 2.75rem; height: 2.75rem; border-radius: 999px;
        background: rgb(255 255 255 / .16); color: #fff; display: inline-flex; align-items: center;
        justify-content: center; cursor: pointer; text-decoration: none; }
    .note-lb-dl:hover { background: rgb(255 255 255 / .28); }
    .note-lb-dl svg { width: 1.25rem; height: 1.25rem; }
</style>

<script>
(function noteLightbox() {
    if (window.noteMediaThumb) return;
    const esc = window.escapeHtml || ((s) => String(s ?? ''));
    const PLAY = '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';

    const ONLOAD = ` onload="this.classList.add('is-loaded')"`
        + ` onerror="this.closest('.nm')?.classList.add('is-gone'); this.remove();"`;
    window.noteMediaThumb = function (m, extra, editIndex) {
        const url = m.url || '', poster = m.posterUrl || '';
        if (m.type === 'drawing') {
            // In the editor (editIndex given) the tile reopens the drawing; in
            // a read view it behaves like any other picture.
            const edit = editIndex != null
                ? ` data-edit-draw="${editIndex}" title="Tap to edit this drawing"`
                : ` data-lb-type="image" data-lb-url="${esc(url)}"`;
            return `<div class="nm nm-draw"${edit}><img src="${esc(url)}" alt="" loading="lazy"${ONLOAD}>`
                + '<span class="nm-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1 10-10-3-3L5 16l-1 4z"/></svg></span>'
                + `${extra || ''}</div>`;
        }
        // A map saved before the type existed is known by the filename the map
        // save writes, so old attachments get the link too.
        const looksLikeMap = m.type === 'map'
            || /\/map-[A-Za-z0-9]+\.png$/.test(m.path || m.url || '');
        // The link is the same for every map in a schedule, so the page states
        // it once rather than threading it through every payload.
        const mapHref = m.mapUrl || window.NOTE_MAP_URL || '';
        if (looksLikeMap && mapHref) {
            const href = mapHref;
            return `<a class="nm nm-map" href="${esc(href)}" title="Open this map in the Maps module">`
                + '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>'
                + `<span>View map</span>${extra || ''}</a>`;
        }
        if (m.type === 'video') {
            return `<div class="nm nm-video" data-lb-type="video" data-lb-url="${esc(url)}" data-lb-poster="${esc(poster)}">${poster ? `<img src="${esc(poster)}" alt=""${ONLOAD}>` : ''}<span class="nm-play">${PLAY}</span>${extra || ''}</div>`;
        }
        return `<div class="nm" data-lb-type="image" data-lb-url="${esc(url)}"><img src="${esc(url)}" alt="" loading="lazy"${ONLOAD}>${extra || ''}</div>`;
    };
    window.noteMediaCells = function (arr) { return (arr || []).map((m) => window.noteMediaThumb(m)).join(''); };

    /** The chips, client side. Twin of note-attachments.blade.php. */
    window.noteAttachmentChips = function (arr) {
        const items = (arr || []).filter((m) => m && (m.url || m.mapUrl));
        if (!items.length) return '';
        const ICON = {
            photo: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 15l4-4 4 4 3-3 5 5"/><circle cx="9" cy="8.5" r="1.3"/></svg>',
            video: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 6h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>',
            draw: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1L20 7a2 2 0 00-3-3L5 16l-1 4zM14 6l4 4"/></svg>',
            map: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5-2V6l5 2m0 12l6-2m-6 2V8m6 10l5 2V8l-5-2m0 12V6M9 8l6-2"/></svg>',
        };
        const cells = items.map((m) => {
            // Only the item's OWN links are honoured — a page-level fallback
            // here made the chips diverge from the Blade twin the moment
            // another module's script had set the globals. A page that wants
            // a module link supplies it per item (see attachmentsOf in the
            // Notes module).
            const looksLikeMap = m.type === 'map' || /\/map-[A-Za-z0-9]+\.png$/.test(m.path || m.url || '');
            if (looksLikeMap) {
                // No saved map to reopen (a day note's attachment, an orphaned
                // save): the picture of it still opens rather than a dead '#'.
                if (!m.mapUrl && m.url) {
                    return `<button type="button" class="na na-map" data-lb-type="image" data-lb-url="${esc(m.url)}" title="Open this map picture">${ICON.map}<span>Map</span></button>`;
                }
                return `<a class="na na-map" href="${esc(m.mapUrl || '#')}" title="Open this map in the Maps module">${ICON.map}<span>Map</span></a>`;
            }
            if (m.type === 'drawing') {
                if (!m.drawUrl && m.url) {
                    return `<button type="button" class="na na-draw" data-lb-type="image" data-lb-url="${esc(m.url)}" title="Open this drawing">${ICON.draw}<span>Drawing</span></button>`;
                }
                return `<a class="na na-draw" href="${esc(m.drawUrl || '#')}" title="Open this drawing in the Draw module">${ICON.draw}<span>Drawing</span></a>`;
            }
            if (m.type === 'video') {
                return `<button type="button" class="na na-video" data-lb-type="video" data-lb-url="${esc(m.url || '')}" data-lb-poster="${esc(m.posterUrl || '')}" title="Play this video">${ICON.video}<span>Video</span></button>`;
            }
            return `<button type="button" class="na na-photo" data-lb-type="image" data-lb-url="${esc(m.url || '')}" title="Open this photo">${ICON.photo}<span>Photo</span></button>`;
        }).join('');
        return `<div class="note-atts">${cells}</div>`;
    };

    const lb = document.getElementById('noteLightbox');
    const stage = lb.querySelector('.note-lb-stage');
    const SAVE_URL = @json(route('media.save'));
    function open(type, url, poster, fromRect) {
        stage.innerHTML = type === 'video'
            ? `<video src="${esc(url)}"${poster ? ` poster="${esc(poster)}"` : ''} controls autoplay playsinline></video>`
            : `<img src="${esc(url)}" alt="">`;
        const dl = document.getElementById('noteLbDownload');
        if (dl) {
            // A sensible filename rather than the storage hash, where the
            // browser will take one.
            const name = decodeURIComponent((url || '').split('/').pop().split('?')[0] || '');
            dl.href = url
                ? SAVE_URL + '?u=' + encodeURIComponent(url) + '&n=' + encodeURIComponent(name)
                : '#';
            if (name) dl.setAttribute('download', name);
            dl.title = type === 'video' ? 'Save this video to your device' : 'Save this photo to your device';
        }
        lb.classList.add('is-open'); lb.setAttribute('aria-hidden', 'false');
        window.registerOverlay?.('noteLightbox', close);
        zoomExpand(stage.firstElementChild, fromRect);
        if (type !== 'video') pinch(stage.firstElementChild);
    }

    /* Opening a photo full screen is only half of looking at it — a note about
       a leaf or a receipt is worth a closer look. Pinch to any level up to 4x,
       double-tap to jump in and out, drag to pan once zoomed. touch-action is
       what makes it possible: without it the browser claims the gesture and
       scrolls the page behind. */
    function pinch(img) {
        if (!img) return;
        let scale = 1, tx = 0, ty = 0, start = null;
        const pts = new Map();
        const paint = (animated) => {
            img.style.transition = animated ? 'transform .2s ease-out' : 'none';
            img.style.transform = scale === 1 && !tx && !ty
                ? '' : `translate(${tx}px, ${ty}px) scale(${scale})`;
        };
        img.style.touchAction = 'none';
        img.addEventListener('dblclick', (e) => {
            e.preventDefault();
            if (scale > 1) { scale = 1; tx = 0; ty = 0; } else { scale = 2.5; }
            paint(true);
        });
        img.addEventListener('wheel', (e) => {
            e.preventDefault();
            scale = Math.min(4, Math.max(1, scale * (e.deltaY < 0 ? 1.15 : 1 / 1.15)));
            if (scale === 1) { tx = 0; ty = 0; }
            paint(true);
        }, { passive: false });
        img.addEventListener('pointerdown', (e) => {
            pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
            start = { scale, tx, ty, pts: [...pts.values()].map((p) => ({ ...p })) };
            if (img.setPointerCapture) { try { img.setPointerCapture(e.pointerId); } catch (_) {} }
            if (scale > 1 || pts.size === 2) e.preventDefault();
        });
        img.addEventListener('pointermove', (e) => {
            if (!pts.has(e.pointerId) || !start) return;
            pts.set(e.pointerId, { x: e.clientX, y: e.clientY });
            const cur = [...pts.values()];
            if (cur.length === 2 && start.pts.length === 2) {
                const d0 = Math.hypot(start.pts[0].x - start.pts[1].x, start.pts[0].y - start.pts[1].y) || 1;
                const d1 = Math.hypot(cur[0].x - cur[1].x, cur[0].y - cur[1].y);
                scale = Math.min(4, Math.max(1, start.scale * (d1 / d0)));
                paint(false);
            } else if (cur.length === 1 && scale > 1) {
                tx = start.tx + (cur[0].x - start.pts[0].x);
                ty = start.ty + (cur[0].y - start.pts[0].y);
                paint(false);
            }
        });
        const lift = (e) => {
            pts.delete(e.pointerId);
            start = pts.size ? { scale, tx, ty, pts: [...pts.values()].map((p) => ({ ...p })) } : null;
            // Snapped back to life size: drop any pan with it.
            if (scale === 1 && (tx || ty)) { tx = 0; ty = 0; paint(true); }
        };
        img.addEventListener('pointerup', lift);
        img.addEventListener('pointercancel', lift);
    }
    // Grow the opened media out of the thumbnail it was tapped from.
    function zoomExpand(media, fromRect) {
        if (!media || !fromRect || !media.animate || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const run = () => {
            const to = media.getBoundingClientRect();
            if (!to.width || !to.height) return;
            const sx = Math.max(.05, fromRect.width / to.width), sy = Math.max(.05, fromRect.height / to.height);
            const dx = (fromRect.left + fromRect.width / 2) - (to.left + to.width / 2);
            const dy = (fromRect.top + fromRect.height / 2) - (to.top + to.height / 2);
            try {
                media.animate([
                    { transform: `translate(${dx}px, ${dy}px) scale(${sx}, ${sy})`, opacity: 0.6 },
                    { transform: 'none', opacity: 1 },
                ], { duration: 300, easing: 'cubic-bezier(.22,1,.36,1)' });
            } catch (_) { /* noop */ }
        };
        if (media.tagName === 'IMG') { (media.complete && media.naturalWidth) ? requestAnimationFrame(run) : media.addEventListener('load', () => requestAnimationFrame(run), { once: true }); }
        else if (media.tagName === 'VIDEO') { media.videoWidth ? requestAnimationFrame(run) : media.addEventListener('loadedmetadata', () => requestAnimationFrame(run), { once: true }); }
        else requestAnimationFrame(run);
    }
    function close() {
        window.unregisterOverlay?.('noteLightbox');
        lb.classList.remove('is-open'); lb.setAttribute('aria-hidden', 'true'); stage.innerHTML = '';
    }

    /* Modules that build their own tiles (the Draw grid) show a picture the
       same way the notebook does, rather than shipping a second viewer. */
    window.openNoteLightbox = function (type, url, poster, fromRect) { open(type || 'image', url, poster, fromRect); };

    document.addEventListener('click', (e) => {
        if (e.target.closest('.rm')) return;                       // remove button, not preview
        // Anything that declares what it wants shown: a thumbnail, an
        // attachment chip, a Media Box cell. The attribute is the contract.
        const cell = e.target.closest('[data-lb-url]');
        if (cell) { e.preventDefault(); open(cell.getAttribute('data-lb-type'), cell.getAttribute('data-lb-url'), cell.getAttribute('data-lb-poster'), cell.getBoundingClientRect()); return; }
        if (e.target.closest('.note-lb-dl')) return;   // saving is not closing
        if (e.target.closest('.note-lb-close') || e.target === lb) close();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && lb.classList.contains('is-open')) close(); });
})();
</script>
