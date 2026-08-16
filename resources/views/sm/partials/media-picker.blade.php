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
    $smPickerUrl = \Illuminate\Support\Facades\Route::has('sm.media-picker')
        ? route('sm.media-picker')
        : url('/app/sm-media-picker');
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
        <div id="smMediaPickerGrid" class="mp-grid" role="listbox" aria-label="Season media"></div>
        <p class="mp-state" id="smMediaPickerState">Loading…</p>
    </div>
</div>

<style>
    .mp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(6.5rem, 1fr)); gap: .5rem; }
    .mp-grid:empty { display: none; }
    .mp-tile { position: relative; display: block; width: 100%; text-align: left; padding: 0;
        border: 1px solid var(--tl-border, #e5e7eb); border-radius: .7rem; overflow: hidden;
        background: var(--tl-surface, #fff); cursor: pointer;
        transition: transform .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1),
            box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .mp-tile:hover, .mp-tile:focus-visible { transform: translateY(-2px); border-color: #a8cc7e;
        box-shadow: 0 8px 20px -12px rgb(0 0 0 / .45); }
    .mp-shot { position: relative; aspect-ratio: 1; background: var(--color-gray-100, #f3f4f6); }
    .mp-shot img { width: 100%; height: 100%; object-fit: cover; display: block; }
    /* A clip with no poster frame is still a clip: say so rather than show a
       grey square that looks like a failed photo. */
    .mp-shot .mp-blank { position: absolute; inset: 0; display: flex; align-items: center;
        justify-content: center; font-size: 1.4rem; color: var(--tl-text-faint, #9ca3af); }
    .mp-badge { position: absolute; left: .3rem; top: .3rem; display: inline-flex; align-items: center;
        gap: .15rem; padding: .1rem .35rem; border-radius: 999px; background: rgb(17 24 39 / .72);
        color: #fff; font-size: .62rem; font-weight: 800; letter-spacing: .02em; }
    .mp-meta { padding: .3rem .4rem .4rem; }
    .mp-name { display: block; font-size: .7rem; font-weight: 700; color: var(--tl-text, #374151);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mp-sub { display: block; font-size: .62rem; color: var(--tl-text-faint, #9ca3af);
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mp-state { padding: 1.5rem .5rem; text-align: center; font-size: .82rem; font-weight: 600;
        color: var(--tl-text-faint, #9ca3af); }
    .mp-state[hidden] { display: none; }
    html.dark .mp-tile { background: #1c2416; border-color: #2b3a1c; }
    html.dark .mp-shot { background: #151b12; }
    html.dark .mp-name { color: #e5e9f5; }
    @media (prefers-reduced-motion: reduce) { .mp-tile { transition: none; } .mp-tile:hover { transform: none; } }
</style>

<script>
(function smMediaPicker() {
    if (window.smPickMedia) return;

    const URL_BASE = @json($smPickerUrl);
    const $ = (id) => document.getElementById(id);
    const esc = window.escapeHtml || ((s) => String(s ?? ''));

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
                : '<span class="mp-blank">🎬</span>';
            return `<button type="button" class="mp-tile" role="option" data-pick="${i}">
                <span class="mp-shot">${inner}<span class="mp-badge">${esc(LABELS[m.kind] || 'File')}</span></span>
                <span class="mp-meta">
                    <span class="mp-name">${esc(m.title)}</span>
                    <span class="mp-sub">${esc(m.source)}${m.when ? ' · ' + esc(m.when) : ''}</span>
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
