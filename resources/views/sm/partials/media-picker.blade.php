@once
{{-- ============================================================
     Choose from the gallery — the fourth way into an attachment.

     Everything this season has kept, listed so it can be pointed at instead
     of photographed again. Include once per page (the attach bar pulls it in
     on its own, so most pages never mention it); it is schedule-agnostic —
     the caller says which season, so one sheet serves every composer.

       window.smPickMedia({
         scheduleId,                  which season to list
         kinds: 'image,video',        optional; drawings and maps count as images
         title: 'Choose a photo',     optional heading
         onPick(item)                 {type,kind,path,poster,url,posterUrl,title,source,when}
       })

     A pick hands back the stored path, not a copy: the same file ends up on
     the note and on the observation, and deleting one does not take the other.
     ============================================================ --}}
@php
    // routes/web.php belongs to the integrator, so this partial cannot assume
    // the name is registered yet — it falls back to the path the route line
    // uses so a page that includes it still renders while that lands.
    $smPickerWired = \Illuminate\Support\Facades\Route::has('sm.media-picker');
    $smPickerUrl = $smPickerWired ? route('sm.media-picker') : url('/app/sm-media-picker');
@endphp
<div class="sheet hidden" id="smMediaPickerSheet" style="--sheet-width:36rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="smMediaPickerTitle">Choose from the gallery</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <input type="search" id="smMediaPickerSearch" class="form-input mb-3"
               placeholder="Search by name, or where it came from…" autocomplete="off">
        <div id="smMediaPickerGrid" class="smp-grid" role="listbox" aria-label="Season media"></div>
        <p class="smp-state" id="smMediaPickerState">Loading…</p>
        {{-- The scroll asks for the next page when this edges into view. --}}
        <div id="smMediaPickerMore" aria-hidden="true"></div>
    </div>
    {{-- Multi-select only: taps collect, this one button hands them over. --}}
    <div class="sheet-footer hidden" id="smMediaPickerFoot">
        <button type="button" class="btn btn-primary btn-sm w-full" id="smMediaPickerAttach" disabled>Attach</button>
    </div>
</div>

<style>
    /* Above anything that can ask for it.
     *
     * A sheet sits at z-50, but the picker is opened from inside things that
     * sit higher: a discussion's thread modal (60), the messenger dock (90),
     * the community's own modals (120). Whichever asked for it, the gallery
     * is the thing being answered right now, so it goes on top — otherwise
     * it opens perfectly and cannot be seen, which is how this was reported.
     */
    #smMediaPickerSheet { z-index: 150; }
    /* Big enough to see what it is. At 6.5rem a tile was a stamp: on a phone
       three of them across, each one too small to tell one rice field from
       another. Two across on a phone, three or four on a wider screen. */
    .smp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(8.5rem, 1fr)); gap: .6rem; }
    .smp-grid:empty { display: none; }
    .smp-tile { position: relative; display: block; width: 100%; text-align: left; padding: 0;
        border: 1px solid var(--tl-border, #e5e7eb); border-radius: .7rem; overflow: hidden;
        background: var(--tl-surface, #fff); cursor: pointer;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1),
            box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .smp-tile:hover, .smp-tile:focus-visible { transform: translateY(-2px); border-color: #a8cc7e;
        box-shadow: 0 8px 20px -12px rgb(0 0 0 / .45); }
    /* The thumbnail is a square whatever is in it — a picture, a clip's own
       first frame, or nothing that would load. A span is inline, so
       aspect-ratio was being ignored and the box took its height from the
       picture inside it; a tile whose media was missing collapsed to a line
       of text with the badge sitting on top of it. */
    .smp-shot { position: relative; display: block; aspect-ratio: 1; overflow: hidden;
        background: var(--color-gray-100, #f3f4f6); }
    .smp-shot img, .smp-shot .smp-vid { position: absolute; inset: 0; z-index: 1;
        width: 100%; height: 100%; object-fit: cover; display: block; background: transparent; }
    /* A clip with no poster frame is still a clip: say so rather than show a
       grey square that looks like a failed photo. */
    .smp-shot .smp-blank { position: absolute; inset: 0; display: flex; align-items: center;
        justify-content: center; font-size: 1.4rem; color: var(--tl-text-faint, #9ca3af); }
    .smp-badge { position: absolute; left: .3rem; top: .3rem; display: inline-flex; align-items: center;
        gap: .15rem; padding: .1rem .35rem; border-radius: 999px; background: rgb(17 24 39 / .72);
        color: #fff; font-size: .62rem; font-weight: 800; letter-spacing: .02em; }
    /* The words sat against the tile's own edge on three sides. */
    .smp-meta { padding: .45rem .55rem .55rem; }
    .smp-name { display: block; font-size: .7rem; font-weight: 700; color: var(--tl-text, #374151);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .smp-sub { display: block; font-size: .62rem; color: var(--tl-text-faint, #9ca3af);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .smp-state { padding: 1.5rem .5rem; text-align: center; font-size: .82rem; font-weight: 600;
        color: var(--tl-text-faint, #9ca3af); }
    .smp-state[hidden] { display: none; }
    /* Ghost tiles while a page is on the wire — the sheet reads as loading
       instead of stuck, in the exact silhouette of what is coming. */
    .smp-skel { border-radius: .7rem; aspect-ratio: 1 / 1.28; background:
        linear-gradient(100deg, var(--color-gray-100, #f3f4f6) 40%, var(--color-gray-200, #e5e7eb) 50%, var(--color-gray-100, #f3f4f6) 60%);
        background-size: 200% 100%; animation: smpShimmer 1.2s linear infinite; }
    @keyframes smpShimmer { to { background-position: -200% 0; } }
    html.dark .smp-skel { background: linear-gradient(100deg, #1c2416 40%, #26301c 50%, #1c2416 60%); background-size: 200% 100%; animation: smpShimmer 1.2s linear infinite; }
    /* A collected tile wears the ring and the check; taps toggle it. */
    .smp-tile.is-picked { border-color: var(--color-brand-500, #4a7c2a); box-shadow: 0 0 0 2px var(--color-brand-300, #a8cc7e); }
    .smp-tile.is-picked::after { content: '✓'; position: absolute; right: .3rem; top: .3rem; width: 1.25rem; height: 1.25rem;
        border-radius: 999px; background: var(--color-brand-600, #3d6823); color: #fff; font-size: .72rem; font-weight: 800;
        display: flex; align-items: center; justify-content: center; }
    html.dark .smp-tile { background: #1c2416; border-color: #2b3a1c; }
    html.dark .smp-shot { background: #151b12; }
    html.dark .smp-name { color: #e5e9f5; }
    @media (prefers-reduced-motion: reduce) { .smp-tile { transition: none; } .smp-tile:hover { transform: none; }
        .smp-skel { animation: none; } }
</style>

<script>
(function smMediaPicker() {
    if (window.smPickMedia) return;

    const URL_BASE = @json($smPickerUrl);
    /* Every season at once.
     *
     * The per-season list is right when you are inside a season. The messenger
     * is not: a farmer attaching a photo there is remembering a picture, not a
     * schedule. Same sheet, same rows, same pick contract — only the source
     * changes, so callers pass allSchedules and nothing else moves. */
    const URL_ALL = @json(route('gallery.hub'));
    const WIRED = @json($smPickerWired);
    const $ = (id) => document.getElementById(id);

    // Resolved per call, not once at IIFE time. This is an inline script inside
    // @@section('content') and window.escapeHtml arrives with app.js, which
    // @@vite loads as a deferred module — so on a direct page load this runs
    // first and a captured reference would be captured empty, for the life of
    // the page. And the fallback escapes as well: the titles below are typed by
    // workers, so a "fallback" that hands the string back untouched is not a
    // fallback, it is the hole.
    const esc = (s) => (typeof window.escapeHtml === 'function'
        ? window.escapeHtml(s)
        : String(s ?? '')
            .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;').replaceAll("'", '&#039;'));

    let items = [];
    let onPick = null;
    /* Paged, not poured: the sheet fetches a screenful, and the scroll asks
       for the next while the reader nears the bottom. `gen` stamps every
       fetch — a new open or a new search obsoletes whatever is on the wire. */
    let cfg = {}, page = 0, more = false, loading = false, gen = 0;
    /* Multi-select (opt-in): taps collect indices, one button hands them
       over together — the AI composer takes several photos per question. */
    let multi = false, pickMax = Infinity;
    const picked = new Set();

    function sayFoot() {
        const btn = $('smMediaPickerAttach');
        if (!btn) return;
        btn.disabled = picked.size === 0;
        // What is being collected, in its own word: a picker showing films
        // offering to "Attach 2 photos" is the wrong noun in the one place
        // the reader is counting.
        const word = (cfg && cfg.kinds === 'video') ? 'clips' : 'photos';
        btn.textContent = picked.size > 1 ? `Attach ${picked.size} ${word}` : 'Attach';
    }

    const LABELS = { image: 'Photo', video: 'Clip', drawing: 'Drawing', map: 'Map' };

    /* What a tile shows.
     *
     * A clip is drawn as the clip: a muted <video> asked for its metadata,
     * which paints its own first frame. It used to be drawn as an <img>
     * pointed at the poster the record claims to have — and a poster that
     * was never made, or was made on another machine, is a broken-image glyph
     * in a picker. A video element with a missing poster simply shows the
     * frame instead, and one that cannot load anything at all leaves the
     * clapperboard behind it showing.
     *
     * The clapperboard sits under every tile rather than instead of one, so
     * a picture whose file has gone the same way falls back to it too: the
     * img takes itself out and what is underneath is already right.
     */
    // A clip by its file, not only by its label. A record whose kind is
    // anything but "video" while its file is an .mp4 was being drawn as a
    // picture — which is an <img> pointed at a film, and that is the broken
    // glyph this used to show.
    const VID_FILE = /\.(mp4|mov|webm|mkv|3gp|m4v)(\?|#|$)/i;
    /* A clip with no poster has to paint its own first frame, and a browser
     * will not go and fetch one for a video it has only been asked to know
     * the length of. Asking for the tenth of a second mark makes it decode
     * that much and show it — the trick every video thumbnail on the web is
     * made of. Harmless where the frame would have appeared anyway. */
    const clipFrameUrl = (url) => {
        const u = String(url || '');
        return (!u || u.includes('#t=')) ? u : (u + '#t=0.1');
    };
    const isClip = (m) => m.type === 'video' || m.kind === 'video' || VID_FILE.test(String(m.url || ''));

    const tileHtml = (m, i) => {
        const clip = isClip(m);
        const blank = `<span class="smp-blank">${clip ? '🎬' : '🖼️'}</span>`;
        const inner = clip
            ? `<video class="smp-vid" src="${esc(clipFrameUrl(m.url))}"${m.posterUrl ? ` poster="${esc(m.posterUrl)}"` : ''} muted playsinline preload="metadata" onerror="this.remove()"></video>`
            : (m.url ? `<img src="${esc(m.posterUrl || m.url)}" alt="" loading="lazy" decoding="async" onerror="this.remove()">` : '');
        return `<button type="button" class="smp-tile" role="option" data-pick="${i}">
            <span class="smp-shot">${blank}${inner}<span class="smp-badge">${esc(LABELS[m.kind] || 'File')}</span></span>
            <span class="smp-meta">
                <span class="smp-name">${esc(m.title)}</span>
                <span class="smp-sub">${esc(m.source)}${m.when ? ' · ' + esc(m.when) : ''}</span>
            </span>
        </button>`;
    };

    const skeletons = (n) => Array.from({ length: n }, () => '<span class="smp-skel"></span>').join('');

    function sayState() {
        const state = $('smMediaPickerState');
        if (loading) { state.hidden = items.length > 0; state.textContent = 'Loading…'; return; }
        state.hidden = items.length > 0;
        state.textContent = ($('smMediaPickerSearch').value || '').trim()
            ? 'Nothing matches that.'
            : 'Nothing kept for this season yet — take a photo or upload one instead.';
    }

    async function loadPage() {
        if (loading || !more) return;
        loading = true;
        const myGen = gen;
        const grid = $('smMediaPickerGrid');
        if (items.length === 0) grid.innerHTML = skeletons(8);
        const all = !!cfg.allSchedules;
        // A caller may point the sheet at a different list entirely — the
        // member's own community photos use this. Everything else about the
        // sheet is the same: the same tiles, the same pick contract, one
        // implementation of "choose a picture that already exists".
        const src = cfg.endpoint || (all ? URL_ALL : URL_BASE);
        const params = new URLSearchParams({ page: String(page + 1) });
        if (all && !cfg.endpoint) params.set('json', '1');
        else if (!cfg.endpoint) params.set('scheduleId', String(cfg.scheduleId || ''));
        if (cfg.kinds) params.set('kinds', cfg.kinds);
        const q = ($('smMediaPickerSearch').value || '').trim();
        if (q) params.set('q', q);
        try {
            const res = await window.api(src + '?' + params.toString());
            if (myGen !== gen) return;               // superseded while flying
            const fresh = (res.data && res.data.items) || [];
            more = !!(res.data && res.data.more);
            page += 1;
            if (page === 1) grid.innerHTML = '';     // the skeletons go
            // Appended, not repainted: a repaint would restart every lazy
            // image the reader already scrolled past.
            const base = items.length;
            grid.insertAdjacentHTML('beforeend', fresh.map((m, i) => tileHtml(m, base + i)).join(''));
            items.push(...fresh);
            grid.__shown = items;
            sayState();
        } catch (err) {
            if (myGen !== gen) return;
            more = false;
            const state = $('smMediaPickerState');
            state.hidden = false;
            state.textContent = err.message || 'Could not load this season\'s media.';
            if (items.length === 0) grid.innerHTML = '';
        } finally {
            if (myGen === gen) loading = false;
        }
    }

    function restart() {
        gen += 1; items = []; page = 0; more = true; loading = false;
        // A new listing renumbers everything, so the collection starts over.
        picked.clear(); sayFoot();
        $('smMediaPickerGrid').__shown = [];
        loadPage();
    }

    /* The sentinel at the sheet's foot asks for the next page. Rebuilt on
       every open — an SPA navigation injects fresh markup, and an observer
       holding the first copy would watch a sheet nobody can see. */
    let watcher = null;
    function watchMore() {
        watcher?.disconnect();
        const foot = $('smMediaPickerMore');
        if (!foot || typeof IntersectionObserver !== 'function') return;
        watcher = new IntersectionObserver((entries) => {
            if (entries.some((en) => en.isIntersecting)) loadPage();
        }, { root: foot.closest('.sheet-body'), rootMargin: '200px' });
        watcher.observe(foot);
    }

    // Bound to the document, not to the elements: this script runs once, but
    // an SPA navigation injects the sheet's markup again, and a listener held
    // on the first copy would be listening to a sheet nobody can see.
    let searchTimer = null;
    document.addEventListener('input', (e) => {
        if (e.target.id !== 'smMediaPickerSearch') return;
        // The server owns the search now — the sheet only holds fetched pages,
        // and filtering a third of the list would lie about the rest.
        clearTimeout(searchTimer);
        searchTimer = setTimeout(restart, 300);
    });

    document.addEventListener('click', (e) => {
        // The footer's one button: hand over the collection, oldest tap first.
        if (e.target.closest('#smMediaPickerAttach')) {
            const chosen = [...picked].sort((a, b) => a - b).map((i) => items[i]).filter(Boolean);
            window.closeSheet?.('smMediaPickerSheet');
            if (onPick) chosen.forEach((item) => onPick(item));
            return;
        }
        const tile = e.target.closest('#smMediaPickerGrid [data-pick]');
        if (!tile) return;
        const idx = parseInt(tile.getAttribute('data-pick'), 10);
        const shown = $('smMediaPickerGrid').__shown || [];
        const item = shown[idx];
        if (!item) return;
        if (multi) {
            // Taps collect; the footer button is the only way anything leaves.
            if (picked.has(idx)) picked.delete(idx);
            else if (picked.size >= pickMax) { window.toast?.(`Up to ${pickMax} photos for this question.`, 'error'); return; }
            else picked.add(idx);
            tile.classList.toggle('is-picked', picked.has(idx));
            sayFoot();
            return;
        }
        window.closeSheet?.('smMediaPickerSheet');
        onPick && onPick(item);
    });

    window.smPickMedia = function (opts) {
        cfg = opts || {};
        onPick = typeof cfg.onPick === 'function' ? cfg.onPick : null;
        multi = !!cfg.multiple;
        pickMax = Number.isFinite(cfg.max) && cfg.max > 0 ? cfg.max : Infinity;
        picked.clear();
        $('smMediaPickerFoot')?.classList.toggle('hidden', !multi);
        sayFoot();
        $('smMediaPickerTitle').textContent = cfg.title || 'Choose from the gallery';
        $('smMediaPickerSearch').value = '';
        $('smMediaPickerGrid').innerHTML = '';
        const state = $('smMediaPickerState');
        state.hidden = false;
        state.textContent = 'Loading…';
        window.openSheet?.('smMediaPickerSheet');

        // Without the route registered there is nothing at the other end, and a
        // raw 404 in the sheet reads as "this season has no photos" rather than
        // "nobody wired this up". Say which.
        if (!WIRED && !cfg.allSchedules && !cfg.endpoint) {
            state.textContent = 'The gallery is not connected on this install yet.';
            return;
        }

        // Re-read on every open rather than cache: the photo somebody wants to
        // attach is very often the one they took a minute ago in another tab.
        watchMore();
        restart();
    };
})();
</script>
@endonce
