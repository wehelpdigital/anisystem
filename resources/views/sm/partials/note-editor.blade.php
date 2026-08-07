{{-- Shared rich note editor (modal). Include ONCE per page (needs Quill loaded
     + the draw-canvas + video-js partials). Exposes:
       window.openNoteEditor({
         title, bodyHtml, media:[{type,path,url,poster,posterUrl}],
         imageUploadUrl, videoUploadUrl, drawUploadUrl,
         onSave({body, media}), onDelete?(), deleteLabel?, saveLabel?
       })
     Body carries text + drawings + emoji; photos & videos go in the gallery. --}}
<div class="sheet sheet-full hidden" id="noteEditorSheet" style="--sheet-width:36rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="noteEditorTitle">Note</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <div class="ne-tools">
            <button type="button" class="btn btn-white btn-sm" id="noteEditorDraw">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1 10-10-3-3L5 16l-1 4z"/></svg>Draw
            </button>
            <button type="button" class="btn btn-white btn-sm" id="noteEditorEmoji">😊 Emoji</button>
            <button type="button" class="btn btn-white btn-sm" id="noteEditorPhoto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>Photo
            </button>
            <span class="ne-vid" data-video-host>
                <input type="file" class="js-video-file hidden" accept="video/*">
                <button type="button" class="btn btn-white btn-sm js-video-attach">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 6h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>Video
                </button>
                <button type="button" class="btn btn-white btn-sm js-video-record">
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="7"/></svg>Record
                </button>
                <span class="js-video-chip"></span>
            </span>
            <input type="file" id="noteEditorPhotoInput" accept="image/*" capture="environment" class="hidden" multiple>
        </div>
        <div class="ne-quill"><div id="noteEditorBody"></div></div>
        <div id="noteEditorMedia" class="ne-thumbs mt-3"></div>
        <p class="form-hint">Photos & videos are auto-compressed. Draw and emoji drop straight into the note.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" id="noteEditorDelete" class="btn btn-danger-outline mr-auto hidden">Delete</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="noteEditorSave" class="btn btn-primary">Save note</button>
    </div>
</div>

<style>
    .ne-tools { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-bottom: .6rem; }
    .ne-vid { display: inline-flex; gap: .4rem; align-items: center; }
    /* Roomy typing box (grows further as you write). */
    .ne-quill .ql-container { min-height: 11rem; border-bottom-left-radius: .6rem; border-bottom-right-radius: .6rem; font-size: .92rem; }
    .ne-quill .ql-editor { min-height: 11rem; }
    .ne-quill .ql-toolbar { border-top-left-radius: .6rem; border-top-right-radius: .6rem; }
    .ne-thumbs:empty { display: none; }
    .ne-thumbs { display: grid; grid-template-columns: repeat(auto-fill, minmax(5.5rem, 1fr)); gap: .5rem; }
    /* Emoji popover */
    .ne-emoji-pop { position: fixed; z-index: 200; display: none; grid-template-columns: repeat(8, 1fr); gap: .1rem;
        background: var(--color-white); border: 1px solid var(--color-gray-200); border-radius: .75rem; padding: .4rem;
        box-shadow: 0 12px 32px -8px rgb(0 0 0 / .35); max-width: 17rem; }
    .ne-emoji-pop.is-open { display: grid; }
    .ne-emoji-pop button { width: 2rem; height: 2rem; font-size: 1.15rem; border-radius: .4rem; cursor: pointer; background: transparent; }
    .ne-emoji-pop button:hover { background: var(--color-gray-100); }
    /* Dark mode. Fallbacks matter: the --tl-* tokens are only defined on the
       Activities/AI pages, so on the Notes hub the toolbar icons would otherwise
       stay dark-on-dark (invisible) without an explicit colour here. */
    html.dark .ne-quill .ql-toolbar, html.dark .ne-quill .ql-container { border-color: var(--tl-border, #2b3a1c); background: var(--tl-surface, #151b12); }
    html.dark .ne-quill .ql-editor { color: var(--tl-text, #e5e7eb); }
    html.dark .ne-quill .ql-editor.ql-blank::before { color: var(--tl-text-faint, #6b7280); }
    html.dark .ne-quill .ql-stroke { stroke: var(--tl-text-muted, #cdd8c0); }
    html.dark .ne-quill .ql-fill { fill: var(--tl-text-muted, #cdd8c0); }
    html.dark .ne-quill .ql-picker { color: var(--tl-text-muted, #cdd8c0); }
    html.dark .ne-quill .ql-picker-options { background: var(--tl-surface, #1c2416); border-color: var(--tl-border, #2b3a1c); }
    html.dark .ne-quill button:hover .ql-stroke, html.dark .ne-quill .ql-toolbar button.ql-active .ql-stroke { stroke: #8fd45a; }
    html.dark .ne-quill button:hover .ql-fill, html.dark .ne-quill .ql-toolbar button.ql-active .ql-fill { fill: #8fd45a; }
    html.dark .ne-emoji-pop { background: #1c2416; border-color: #2b3a1c; }
</style>

<script>
(function noteEditor() {
    if (window.openNoteEditor) return;
    const $ = (id) => document.getElementById(id);
    const esc = window.escapeHtml || ((s) => String(s ?? ''));
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';
    const EMOJIS = ['🌱','🌾','🌽','🍚','🍅','🍆','🥒','🥬','🌶️','🥭','🍌','🥥','☀️','🌤️','🌧️','⛈️','🌈','💧','🌡️','🐛','🐌','🐜','🐔','🐖','🐃','🚜','🧺','🧑‍🌾','😀','😄','😅','🤔','😮','😢','😍','🙏','👍','👏','💪','🤝','❤️','🔥','✅','⚠️','📌','⭐'];

    let quill = null, media = [], cfg = {};

    function ensureQuill() {
        if (!quill && window.Quill) {
            quill = new Quill('#noteEditorBody', {
                theme: 'snow',
                placeholder: 'Write your note…',
                modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link', 'clean']] },
            });
        }
        return quill;
    }

    function renderThumbs() {
        $('noteEditorMedia').innerHTML = media.map((m, i) => window.noteMediaThumb
            ? window.noteMediaThumb(m, `<button type="button" class="rm" data-rm="${i}" aria-label="Remove">✕</button>`)
            : '').join('');
    }

    async function upload(url, field, file) {
        const form = new FormData(); form.append(field, file);
        const res = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: form, credentials: 'same-origin' });
        const json = await res.json().catch(() => ({}));
        if (!json.success) throw new Error(json.message || 'Upload failed.');
        return json.data;
    }

    // ---- Photos
    $('noteEditorPhoto').addEventListener('click', () => $('noteEditorPhotoInput').click());
    $('noteEditorPhotoInput').addEventListener('change', async (e) => {
        const files = Array.from(e.target.files || []); e.target.value = '';
        for (const file of files) {
            try { const d = await upload(cfg.imageUploadUrl, 'image', file); media.push({ type: 'image', path: d.path, url: d.url }); renderThumbs(); }
            catch (err) { window.toast?.(err.message, 'error'); }
        }
    });

    // ---- Video (shared partial writes the file into .js-video-file)
    const vInput = document.querySelector('#noteEditorSheet .js-video-file');
    vInput?.addEventListener('change', async () => {
        const file = vInput.files && vInput.files[0]; if (!file) return;
        window.toast?.('Compressing video…');
        try { const d = await upload(cfg.videoUploadUrl, 'video', file); media.push({ type: 'video', path: d.path, poster: d.poster, url: d.url, posterUrl: d.posterUrl }); renderThumbs(); window.toast?.('Video attached.'); }
        catch (err) { window.toast?.(err.message, 'error'); }
        vInput.value = '';
        const host = document.querySelector('#noteEditorSheet [data-video-host]');
        if (host && window.plazaClearVideo) window.plazaClearVideo(host);
    });

    // ---- Remove a media item
    $('noteEditorMedia').addEventListener('click', (e) => {
        const rm = e.target.closest('[data-rm]'); if (!rm) return;
        media.splice(parseInt(rm.getAttribute('data-rm'), 10), 1); renderThumbs();
    });

    // ---- Draw → upload → add as an attachment (not inline in the body)
    $('noteEditorDraw').addEventListener('click', () => {
        if (typeof window.openDrawCanvas !== 'function') { window.toast?.('Drawing tool unavailable.', 'error'); return; }
        window.openDrawCanvas(async (dataUrl) => {
            try {
                const res = await fetch(cfg.drawUploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ image: dataUrl }), credentials: 'same-origin' });
                const json = await res.json().catch(() => ({}));
                if (!json.success || !json.data?.url) throw new Error(json.message || 'Upload failed.');
                media.push({ type: 'image', path: json.data.path, url: json.data.url });
                renderThumbs();
                window.toast?.('Drawing added.');
            } catch (err) { window.toast?.(err.message || 'Could not add drawing.', 'error'); }
        });
    });

    // ---- Emoji popover → insert into the body
    const pop = document.createElement('div');
    pop.className = 'ne-emoji-pop';
    pop.innerHTML = EMOJIS.map((em) => `<button type="button">${em}</button>`).join('');
    document.body.appendChild(pop);
    function toggleEmoji(anchor) {
        if (pop.classList.contains('is-open')) { pop.classList.remove('is-open'); return; }
        const r = anchor.getBoundingClientRect();
        pop.classList.add('is-open');
        pop.style.left = Math.max(8, Math.min(r.left, window.innerWidth - pop.offsetWidth - 8)) + 'px';
        pop.style.top = Math.min(r.bottom + 6, window.innerHeight - pop.offsetHeight - 8) + 'px';
    }
    $('noteEditorEmoji').addEventListener('click', (e) => { e.stopPropagation(); toggleEmoji(e.currentTarget); });
    pop.addEventListener('click', (e) => {
        const b = e.target.closest('button'); if (!b) return;
        ensureQuill();
        const range = quill.getSelection(true) || { index: quill.getLength() };
        quill.insertText(range.index, b.textContent, 'user');
        quill.setSelection(range.index + b.textContent.length, 0);
    });
    document.addEventListener('click', (e) => { if (!e.target.closest('.ne-emoji-pop') && e.target !== $('noteEditorEmoji')) pop.classList.remove('is-open'); });

    // ---- Save / delete
    $('noteEditorSave').addEventListener('click', () => {
        const raw = quill ? quill.root.innerHTML : '';
        const body = (raw === '<p><br></p>' || raw.trim() === '') ? '' : raw;
        const payloadMedia = media.map((m) => ({ type: m.type, path: m.path, poster: m.poster || null }));
        cfg.onSave && cfg.onSave({ body, media: payloadMedia });
        close();
    });
    $('noteEditorDelete').addEventListener('click', () => { cfg.onDelete && cfg.onDelete(); close(); });

    function close() { pop.classList.remove('is-open'); window.closeSheet?.('noteEditorSheet'); }

    window.openNoteEditor = function (opts) {
        cfg = opts || {};
        ensureQuill();
        $('noteEditorTitle').textContent = opts.title || 'Note';
        if (quill) quill.root.innerHTML = opts.bodyHtml || '';
        media = Array.isArray(opts.media) ? opts.media.map((m) => ({ ...m })) : [];
        renderThumbs();
        const del = $('noteEditorDelete');
        del.classList.toggle('hidden', !opts.onDelete);
        del.textContent = opts.deleteLabel || 'Delete';
        $('noteEditorSave').textContent = opts.saveLabel || 'Save note';
        window.openSheet?.('noteEditorSheet');
        setTimeout(() => quill && quill.focus(), 250);
    };
})();
</script>
