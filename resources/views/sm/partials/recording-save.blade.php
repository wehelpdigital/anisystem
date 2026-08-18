{{-- What to call the thing you just recorded.

     Asked the moment the recording stops, while everyone still remembers
     what it was of. A file named "team-recording-1755203841.webm" is one
     nobody opens again, and a box is only useful if the things in it
     say what they are.

     Exposes window.smAskRecording({
         sizeMB, hint?, albumsUrl?, scheduleId?,
         onSave({title, description, albumId})
     }).
     `albumsUrl` + `scheduleId` add an optional "file it in an album too"
     select, filled from that schedule's Gallery albums — the note flows use
     it; the Team box flow does not pass it and never sees the row.
     Include once per page. --}}
<div class="rs-modal hidden" id="recSaveModal" role="dialog" aria-modal="true" aria-label="Save this recording">
    <div class="rs-backdrop" data-rs-cancel></div>
    <div class="rs-card">
        <div class="rs-head">
            <span class="rs-dot" aria-hidden="true"></span>
            <p class="font-bold text-gray-900">Save this recording</p>
            <span class="rs-size" id="recSaveSize"></span>
        </div>
        <div class="rs-body space-y-3">
            <div>
                <label class="form-label" for="recSaveTitle">Title <span class="text-red-500">*</span></label>
                <input type="text" id="recSaveTitle" class="form-input" maxlength="191"
                       placeholder="e.g. Walkthrough of the flooded corner" autocomplete="off">
            </div>
            <div>
                <label class="form-label" for="recSaveDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="recSaveDesc" class="form-textarea" rows="3" maxlength="2000"
                          placeholder="What was being shown, and who was there?"></textarea>
            </div>
            {{-- Only when the caller says which schedule's albums to offer:
                 a clip attached to a note can also sit in the Gallery beside
                 the photos of the same walk. --}}
            <div id="recSaveAlbumWrap" hidden>
                <label class="form-label" for="recSaveAlbum">Album <span class="text-gray-400 font-normal">(optional)</span></label>
                <select id="recSaveAlbum" class="form-select">
                    <option value="">Not in an album</option>
                </select>
                <p class="form-hint">Also file this video in one of this schedule's Gallery albums.</p>
            </div>
            <p class="form-hint" id="recSaveHint">Saved to the Team box in the Gallery, where everyone on the schedule can find it.</p>
        </div>
        <div class="rs-foot">
            <button type="button" class="btn btn-ghost" data-rs-cancel>Discard</button>
            <button type="button" class="btn btn-primary ml-auto" id="recSaveGo">Save</button>
        </div>
    </div>
</div>

@push('head')
<style>
    .rs-modal { position: fixed; inset: 0; z-index: 170; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .rs-modal.hidden { display: none; }
    .rs-backdrop { position: absolute; inset: 0; background: rgb(0 0 0 / .6); opacity: 0;
        transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .rs-modal.is-open .rs-backdrop { opacity: 1; }
    .rs-card { position: relative; width: 100%; max-width: 26rem; max-height: 92dvh; display: flex; flex-direction: column;
        background: var(--color-white); border-radius: 1rem; overflow: hidden; box-shadow: var(--shadow-card-lg);
        transform: translateY(1rem) scale(.98); opacity: 0;
        transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .rs-modal.is-open .rs-card { transform: none; opacity: 1; }
    .rs-head { display: flex; align-items: center; gap: .5rem; padding: .75rem 1rem;
        border-bottom: 1px solid var(--color-gray-100); }
    .rs-dot { width: .6rem; height: .6rem; border-radius: 999px; background: #ef4444; flex: none; }
    .rs-size { margin-left: auto; font-size: .68rem; font-weight: 700; color: var(--color-gray-400); }
    .rs-body { padding: 1rem; overflow-y: auto; min-height: 0; }
    .rs-foot { display: flex; align-items: center; gap: .5rem; padding: .75rem 1rem;
        border-top: 1px solid var(--color-gray-100); }
    html.dark .rs-card { background: #151b12; }
    html.dark .rs-head, html.dark .rs-foot { border-color: #2b3a1c; }
    @media (prefers-reduced-motion: reduce) { .rs-backdrop, .rs-card { transition: none; } }
</style>
@endpush

@push('scripts')
<script>
(function recordingSave() {
    if (!document.getElementById('recSaveModal') || window.smAskRecording) return;
    const $ = (id) => document.getElementById(id);
    // Looked up at call time, not held: inside the activities shell a module
    // can be re-injected, and a handle to the first render would be a handle
    // to a node no longer on the page.
    const modalEl = () => document.getElementById('recSaveModal');
    let cfg = null;
    const HINT_DEFAULT = $('recSaveHint').textContent;

    function close() {
        const modal = modalEl();
        if (modal) {
            modal.classList.remove('is-open');
            setTimeout(() => modal.classList.add('hidden'), 260);
        }
        document.body.style.overflow = '';
        cfg = null;
    }

    /* The albums live server-side per schedule; asked for fresh each open so
       an album made a minute ago in the Gallery is already on the list. */
    async function loadAlbums(url, scheduleId) {
        const sel = $('recSaveAlbum');
        sel.innerHTML = '';
        const none = document.createElement('option');
        none.value = ''; none.textContent = 'Not in an album';
        sel.appendChild(none);
        try {
            const res = await fetch(url + '?scheduleId=' + encodeURIComponent(scheduleId), {
                headers: { Accept: 'application/json' }, credentials: 'same-origin',
            });
            const json = await res.json();
            (json.data?.albums || []).forEach((a) => {
                const opt = document.createElement('option');
                opt.value = a.id; opt.textContent = a.title;
                sel.appendChild(opt);
            });
        } catch (_) { /* "Not in an album" still stands, and the clip still saves */ }
    }

    window.smAskRecording = function (opts) {
        const modal = modalEl();
        if (!modal) return;
        cfg = opts || {};
        $('recSaveTitle').value = '';
        $('recSaveDesc').value = '';
        $('recSaveSize').textContent = cfg.sizeMB ? cfg.sizeMB.toFixed(1) + ' MB' : '';
        // Each caller says where the recording will live; the Team box words
        // would be a lie under the note flows.
        $('recSaveHint').textContent = cfg.hint || HINT_DEFAULT;
        const wantAlbums = !!(cfg.albumsUrl && cfg.scheduleId);
        $('recSaveAlbumWrap').hidden = !wantAlbums;
        if (wantAlbums) loadAlbums(cfg.albumsUrl, cfg.scheduleId);
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        // The keyboard waits: read the question, then answer it.
        window.smFocus?.($('recSaveTitle'), { delay: 260 });
        window.registerOverlay?.('recSave', close);
    };

    // Delegated, not bound to the node: a re-injected copy of the sheet keeps
    // working without anyone re-running this script.
    document.addEventListener('click', (e) => {
        const modal = modalEl();
        if (!modal || modal.classList.contains('hidden')) return;
        if (e.target.closest('#recSaveGo')) {
            const title = $('recSaveTitle').value.trim();
            if (!title) { window.toast?.('Give the recording a title.', 'error'); $('recSaveTitle').focus(); return; }
            const fn = cfg?.onSave;
            const description = $('recSaveDesc').value.trim();
            const albumId = $('recSaveAlbumWrap').hidden ? null : ($('recSaveAlbum').value || null);
            close();
            fn?.({ title, description, albumId });
            return;
        }
        if (!e.target.closest('#recSaveModal [data-rs-cancel]')) return;
        // Discarding loses the only copy, so it asks first.
        const go = () => close();
        if (window.confirmAction) {
            window.confirmAction({
                title: 'Discard this recording?',
                message: 'It has not been saved anywhere. This cannot be undone.',
                confirmText: 'Discard', danger: true,
            }).then((ok) => { if (ok) go(); });
        } else if (confirm('Discard this recording?')) go();
    });
})();
</script>
@endpush
