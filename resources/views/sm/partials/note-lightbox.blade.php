{{-- Shared note ATTACHMENTS: canonical thumbnail markup + an expandable
     lightbox. Include ONCE per page that shows notes (activities/hub/notebook).
     Exposes window.noteMediaThumb(m, extra) and window.noteMediaCells(arr).
     Any `.nm[data-lb-url]` element opens the lightbox on click. --}}
<div class="note-lb" id="noteLightbox" aria-hidden="true">
    <button type="button" class="note-lb-close" aria-label="Close">✕</button>
    <div class="note-lb-stage"></div>
</div>

<style>
    /* Attachment thumbnail (square icon) */
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
        if (m.type === 'map') {
            const href = m.mapUrl || '';
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

    const lb = document.getElementById('noteLightbox');
    const stage = lb.querySelector('.note-lb-stage');
    function open(type, url, poster, fromRect) {
        stage.innerHTML = type === 'video'
            ? `<video src="${esc(url)}"${poster ? ` poster="${esc(poster)}"` : ''} controls autoplay playsinline></video>`
            : `<img src="${esc(url)}" alt="">`;
        lb.classList.add('is-open'); lb.setAttribute('aria-hidden', 'false');
        zoomExpand(stage.firstElementChild, fromRect);
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
    function close() { lb.classList.remove('is-open'); lb.setAttribute('aria-hidden', 'true'); stage.innerHTML = ''; }

    document.addEventListener('click', (e) => {
        if (e.target.closest('.rm')) return;                       // remove button, not preview
        const cell = e.target.closest('.nm[data-lb-url]');
        if (cell) { e.preventDefault(); open(cell.getAttribute('data-lb-type'), cell.getAttribute('data-lb-url'), cell.getAttribute('data-lb-poster'), cell.getBoundingClientRect()); return; }
        if (e.target.closest('.note-lb-close') || e.target === lb) close();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && lb.classList.contains('is-open')) close(); });
})();
</script>
