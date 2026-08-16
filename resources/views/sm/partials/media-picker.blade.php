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
    </div>
</div>

<style>
    .smp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(6.5rem, 1fr)); gap: .5rem; }
    .smp-grid:empty { display: none; }
    .smp-tile { position: relative; display: block; width: 100%; text-align: left; padding: 0;
        border: 1px solid var(--tl-border, #e5e7eb); border-radius: .7rem; overflow: hidden;
        background: var(--tl-surface, #fff); cursor: pointer;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1),
            box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .smp-tile:hover, .smp-tile:focus-visible { transform: translateY(-2px); border-color: #a8cc7e;
        box-shadow: 0 8px 20px -12px rgb(0 0 0 / .45); }
    .smp-shot { position: relative; aspect-ratio: 1; background: var(--color-gray-100, #f3f4f6); }
    .smp-shot img { width: 100%; height: 100%; object-fit: cover; display: block; }
    /* A clip with no poster frame is still a clip: say so rather than show a
       grey square that looks like a failed photo. */
    .smp-shot .smp-blank { position: absolute; inset: 0; display: flex; align-items: center;
        justify-content: center; font-size: 1.4rem; color: var(--tl-text-faint, #9ca3af); }
    .smp-badge { position: absolute; left: .3rem; top: .3rem; display: inline-flex; align-items: center;
        gap: .15rem; padding: .1rem .35rem; border-radius: 999px; background: rgb(17 24 39 / .72);
        color: #fff; font-size: .62rem; font-weight: 800; letter-spacing: .02em; }
    .smp-meta { padding: .3rem .4rem .4rem; }
    .smp-name { display: block; font-size: .7rem; font-weight: 700; color: var(--tl-text, #374151);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .smp-sub { display: block; font-size: .62rem; color: var(--tl-text-faint, #9ca3af);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .smp-state { padding: 1.5rem .5rem; text-align: center; font-size: .82rem; font-weight: 600;
        color: var(--tl-text-faint, #9ca3af); }
    .smp-state[hidden] { display: none; }
    html.dark .smp-tile { background: #1c2416; border-color: #2b3a1c; }
    html.dark .smp-shot { background: #151b12; }
    html.dark .smp-name { color: #e5e9f5; }
    @media (prefers-reduced-motion: reduce) { .smp-tile { transition: none; } .smp-tile:hover { transform: none; } }
</style>

<script>
(function smMediaPicker() {
    if (window.smPickMedia) return;

    const URL_BASE = @json($smPickerUrl);
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

    const LABELS = { image: 'Photo', video: 'Clip', drawing: 'Drawing', map: 'Map' };

    function paint() {
        const q = ($('smMediaPickerSearch').value || '').trim().toLowerCase();
        const shown = q
            ? items.filter((m) => (m.title + ' ' + m.source).toLowerCase().includes(q))
            : items;

        $('smMediaPickerGrid').innerHTML = shown.map((m, i) => {
            const shot = m.posterUrl || (m.type === 'image' ? m.url : null);
            const inner = shot
                ? `<img src="${esc(shot)}" alt="" loading="lazy">`
                : '<span class="smp-blank">🎬</span>';
            return `<button type="button" class="smp-tile" role="option" data-pick="${i}">
                <span class="smp-shot">${inner}<span class="smp-badge">${esc(LABELS[m.kind] || 'File')}</span></span>
                <span class="smp-meta">
                    <span class="smp-name">${esc(m.title)}</span>
                    <span class="smp-sub">${esc(m.source)}${m.when ? ' · ' + esc(m.when) : ''}</span>
                </span>
            </button>`;
        }).join('');
        // The index on the tile is into the filtered list, so keep that list
        // where the click handler can reach it.
        $('smMediaPickerGrid').__shown = shown;

        const state = $('smMediaPickerState');
        state.hidden = shown.length > 0;
        state.textContent = items.length === 0
            ? 'Nothing kept for this season yet — take a photo or upload one instead.'
            : 'Nothing matches that.';
    }

    // Bound to the document, not to the elements: this script runs once, but
    // an SPA navigation injects the sheet's markup again, and a listener held
    // on the first copy would be listening to a sheet nobody can see.
    document.addEventListener('input', (e) => {
        if (e.target.id === 'smMediaPickerSearch') paint();
    });

    document.addEventListener('click', (e) => {
        const tile = e.target.closest('#smMediaPickerGrid [data-pick]');
        if (!tile) return;
        const shown = $('smMediaPickerGrid').__shown || [];
        const item = shown[parseInt(tile.getAttribute('data-pick'), 10)];
        if (!item) return;
        window.closeSheet?.('smMediaPickerSheet');
        onPick && onPick(item);
    });

    window.smPickMedia = async function (opts) {
        const cfg = opts || {};
        onPick = typeof cfg.onPick === 'function' ? cfg.onPick : null;
        items = [];
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
        if (!WIRED) {
            state.textContent = 'The gallery is not connected on this install yet.';
            return;
        }

        // Re-read on every open rather than cache: the photo somebody wants to
        // attach is very often the one they took a minute ago in another tab.
        const params = new URLSearchParams({ scheduleId: String(cfg.scheduleId || '') });
        if (cfg.kinds) params.set('kinds', cfg.kinds);
        try {
            const res = await window.api(URL_BASE + '?' + params.toString());
            items = (res.data && res.data.items) || [];
            paint();
        } catch (err) {
            state.hidden = false;
            state.textContent = err.message || 'Could not load this season\'s media.';
        }
    };
})();
</script>
@endonce
