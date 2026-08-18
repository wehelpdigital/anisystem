{{-- Shared rich note editor (modal). Include ONCE per page (needs Quill loaded
     + the draw-canvas + video-js partials). Exposes:
       window.openNoteEditor({
         title, bodyHtml, media:[{type,path,url,poster,posterUrl}],
         imageUploadUrl, videoUploadUrl, drawUploadUrl,
         askTitle?, noteTitle?,
         onSave({body, media, noteTitle}), onDelete?(), deleteLabel?, saveLabel?
       })
     Body carries text + drawings + emoji; photos & videos go in the gallery. --}}
<div class="sheet sheet-full hidden" id="noteEditorSheet" style="--sheet-width:36rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="noteEditorTitle">Note</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        {{-- One row, five things, all the same size: take a photo, upload one,
             attach a video, record one, add an emoji. They were wrapping onto
             two and three lines because each carried a word of its own width,
             and "Photo" was the only way in — a picker, with no way to just
             take the picture in front of you. --}}
        <div class="ne-tools" role="group" aria-label="Attach to this note">
            <button type="button" class="ne-tool" id="noteEditorCamera" title="Take a photo now" aria-label="Take a photo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Camera</span>
            </button>
            <button type="button" class="ne-tool" id="noteEditorPhoto" title="Choose a photo already on this device" aria-label="Upload a photo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 15l4-4 4 4 3-3 5 5"/><circle cx="9" cy="8.5" r="1.3"/></svg>
                <span>Upload</span>
            </button>
            <span class="ne-vid" data-video-host>
                <input type="file" class="js-video-file hidden" accept="video/*">
                <button type="button" class="ne-tool js-video-attach" title="Attach a video" aria-label="Attach a video">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 6h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
                    <span>Video</span>
                </button>
                <button type="button" class="ne-tool js-video-record" title="Record a video" aria-label="Record a video">
                    <svg viewBox="0 0 24 24" fill="currentColor" class="text-red-500"><circle cx="12" cy="12" r="7"/></svg>
                    <span>Record</span>
                </button>
                <span class="js-video-chip"></span>
            </span>
            <button type="button" class="ne-tool" id="noteEditorEmoji" title="Add an emoji" aria-label="Add an emoji">
                <span class="ne-tool-emoji" aria-hidden="true">😊</span>
                <span>Emoji</span>
            </button>
            <input type="file" id="noteEditorPhotoInput" accept="image/*" class="hidden" multiple>
            {{-- capture= asks the phone for its camera rather than its files.
                 Desktop browsers ignore it and show a picker, which is the
                 right fallback. --}}
            <input type="file" id="noteEditorCameraInput" accept="image/*" capture="environment" class="hidden">
        </div>
        {{-- A name for the note. Optional to the editor — the day notes ask
             for it, the older callers do not — because a day with three notes
             needs to say which is which, and "the one with the photo" is not
             a name. --}}
        <div id="noteEditorTitleWrap" class="mb-3" hidden>
            <label class="form-label" for="noteEditorTitleInput">Title <span class="text-red-500">*</span></label>
            <input type="text" id="noteEditorTitleInput" class="form-input" maxlength="191" placeholder="e.g. Pump repair — west line">
        </div>
        {{-- What the note is about, chosen by tapping. Typing "Apartado 1"
             into the words makes a sentence; tapping the lot makes a fact the
             Gallery and the reports can filter on. Hidden unless the caller
             passes something to tag. --}}
        <div id="noteEditorTags" class="ne-tags" hidden>
            <div class="ne-tagrow" id="noteEditorLotRow" hidden>
                <span class="ne-taglabel">Which lot?</span>
                <div class="ne-tagpills" id="noteEditorLots"></div>
            </div>
            <div class="ne-tagrow" id="noteEditorActRow" hidden>
                <span class="ne-taglabel">About which task?</span>
                <div class="ne-tagpills" id="noteEditorActs"></div>
            </div>
        </div>
        <div class="ne-quill"><div id="noteEditorBody"></div></div>
        <div id="noteEditorUploads" class="ne-ups mt-3"></div>
        <div id="noteEditorMedia" class="ne-thumbs mt-3"></div>
        <p class="form-hint">Photos &amp; videos are auto-compressed. A drawing already attached can still be tapped to edit it.</p>
    </div>
    <div class="sheet-footer">
        <button type="button" id="noteEditorDelete" class="btn btn-danger-outline mr-auto hidden">Delete</button>
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" id="noteEditorSave" class="btn btn-primary">Save note</button>
    </div>
</div>

<style>
    /* One row that stays one row: the buttons share the width instead of
       each taking the width of its own word. */
    .ne-tools { display: flex; align-items: center; gap: .35rem; margin-bottom: .6rem; }
    .ne-vid { display: contents; }
    .ne-tool { flex: 1 1 0; min-width: 0; display: inline-flex; flex-direction: column;
        align-items: center; justify-content: center; gap: .15rem; padding: .4rem .2rem;
        border: 1px solid var(--color-gray-200); border-radius: .65rem; background: var(--color-white);
        font-size: .66rem; font-weight: 700; color: var(--color-gray-600); cursor: pointer;
        transition: background .2s ease, border-color .2s ease, color .2s ease; }
    .ne-tool:hover { background: #f3f8ec; border-color: #a8cc7e; color: #3d6823; }
    .ne-tool svg { width: 1.05rem; height: 1.05rem; }
    .ne-tool-emoji { font-size: 1.05rem; line-height: 1; }
    .ne-tool span:last-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; }
    .js-video-chip:empty { display: none; }
    html.dark .ne-tool { background: #1c2416; border-color: #2b3a1c; color: #cdd8c0; }
    html.dark .ne-tool:hover { background: #24301a; border-color: #3d6823; }
    @media (prefers-reduced-motion: reduce) { .ne-tool { transition: none; } }
    /* Height and look come from the shared rules in app.css. */
    /* Upload progress: a phone video is tens of megabytes, and a quiet wait
       reads as a hang. Percent while the bytes travel, then a word for the
       compressing the server still has to do. */
    .ne-ups:empty { display: none; }
    .ne-ups { display: grid; gap: .4rem; }
    .ne-up { border: 1px solid var(--color-gray-200); border-radius: .6rem; padding: .45rem .6rem; }
    .ne-up-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; font-size: .75rem; font-weight: 700; color: var(--tl-text-muted, #4b5563); }
    .ne-up-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ne-up-pct { flex: 0 0 auto; font-variant-numeric: tabular-nums; }
    .ne-up-bar { height: .3rem; border-radius: 999px; background: var(--color-gray-200); overflow: hidden; margin-top: .35rem; }
    .ne-up-fill { height: 100%; width: 0; border-radius: 999px; background: var(--color-primary, #4a7c2a); transition: width .2s linear; }
    .ne-up.is-error { border-color: #fecaca; background: #fef2f2; }
    .ne-up.is-error .ne-up-top { color: #b91c1c; }
    .ne-up.is-error .ne-up-fill { background: #ef4444; }
    @media (prefers-reduced-motion: reduce) { .ne-up-fill { transition: none; } }
    /* Tag pills: one tap on, one tap off. Wide enough for a thumb, quiet
       enough to sit above the words without competing with them. */
    .ne-tags { display: grid; gap: .45rem; margin-bottom: .6rem; }
    .ne-tags[hidden] { display: none; }
    .ne-tagrow[hidden] { display: none; }
    .ne-taglabel { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .03em;
        text-transform: uppercase; color: var(--tl-text-faint, #9ca3af); margin-bottom: .25rem; }
    /* A grid track and a flex item both size themselves to their longest
       unbreakable line unless told they may be narrower, and a lot called
       "Apartado 1 — riverside terraces behind the pump house" is one long
       line. Without these min-width:0s the row grew past the sheet and the
       body got a sideways scrollbar. */
    .ne-tagrow { min-width: 0; }
    .ne-tagpills { display: flex; flex-wrap: wrap; gap: .3rem; min-width: 0; }
    .ne-tagpill { display: inline-flex; align-items: center; gap: .25rem; max-width: 100%; min-width: 0;
        padding: .3rem .6rem; border-radius: 999px; cursor: pointer;
        border: 1px solid var(--tl-border, #e5e7eb); background: var(--tl-surface, #fff);
        font-size: .72rem; font-weight: 700; color: var(--tl-text-muted, #4b5563);
        /* Buttons centre their text by default, so a task name long enough
           to wrap read as a centred poem. Ragged-right like any other line
           of reading. */
        text-align: left; justify-content: flex-start;
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1); }
    /* A long name wraps onto a second line rather than being cut to an
       ellipsis: you are choosing which lot you are writing about, and "Apar…"
       does not tell you that. `anywhere` breaks names with no spaces in them
       at all. Three lines is the ceiling, so a truly absurd name makes a
       taller pill, not a taller sheet. */
    .ne-tagpill span { min-width: 0; white-space: normal; overflow-wrap: anywhere;
        line-height: 1.25; max-height: 3.75em; overflow: hidden; }
    .ne-tagpill:hover { border-color: #a8cc7e; color: #3d6823; }
    .ne-tagpill.is-on { background: #eaf4dd; border-color: #4a7c2a; color: #2f5a17; }
    .ne-tagpill.is-on::before { content: '✓'; font-size: .68rem; }
    html.dark .ne-tagpill { background: #1c2416; border-color: #2b3a1c; color: #cdd8c0; }
    html.dark .ne-tagpill.is-on { background: #2c4318; border-color: #6ba33c; color: #d8f0be; }
    @media (prefers-reduced-motion: reduce) { .ne-tagpill { transition: none; } }
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
    // What this note is about. Null means "not said", which is different from
    // "no lot" — a note can simply be about the day.
    let tagLot = null, tagAct = null;
    // Uploads still on the wire. A phone clip takes real seconds, and Save
    // used to be perfectly happy to file the note without it — the video
    // "disappeared" because it had never joined the media list yet.
    let uploadsInFlight = 0;

    function ensureQuill() {
        if (!quill && window.Quill) {
            quill = new Quill('#noteEditorBody', {
                theme: 'snow',
                placeholder: 'Write your note…',
                modules: { toolbar: window.SM_RICH_TOOLBAR },
            });
            window.smQuillTouch?.(quill);
        }
        return quill;
    }

    function renderThumbs() {
        $('noteEditorMedia').innerHTML = media.map((m, i) => window.noteMediaThumb
            ? window.noteMediaThumb(m, `<button type="button" class="rm" data-rm="${i}" aria-label="Remove">✕</button>`, i)
            : '').join('');
    }

    /* ---- What the note is about --------------------------------------
     * Both rows are the same idea: a list of things, one of which may be
     * chosen, chosen by tapping. Tapping the chosen one again unsays it,
     * because "I picked the wrong lot" is more common than "I meant to pick
     * none" and both need the same escape. */
    function paintTags() {
        const lots = Array.isArray(cfg.tags?.lots) ? cfg.tags.lots : [];
        const acts = Array.isArray(cfg.tags?.activities) ? cfg.tags.activities : [];
        const pill = (id, label, on) => '<button type="button" class="ne-tagpill' + (on ? ' is-on' : '')
            + '" data-tag-id="' + id + '"><span>' + esc(label) + '</span></button>';

        $('noteEditorLots').innerHTML = lots.map((l) => pill(l.id, l.name, Number(l.id) === Number(tagLot))).join('');
        $('noteEditorActs').innerHTML = acts.map((a) => pill(a.id, a.title, Number(a.id) === Number(tagAct))).join('');
        $('noteEditorLotRow').hidden = lots.length === 0;
        $('noteEditorActRow').hidden = acts.length === 0;
        $('noteEditorTags').hidden = lots.length === 0 && acts.length === 0;
    }

    function wireTagRow(hostId, get, set) {
        $(hostId).addEventListener('click', (e) => {
            const b = e.target.closest('[data-tag-id]');
            if (!b) return;
            const id = parseInt(b.getAttribute('data-tag-id'), 10);
            set(get() === id ? null : id);
            paintTags();
        });
    }
    wireTagRow('noteEditorLots', () => tagLot, (v) => { tagLot = v; });
    wireTagRow('noteEditorActs', () => tagAct, (v) => { tagAct = v; });

    function fmtSize(bytes) {
        if (!bytes) return '';
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    /** Upload one file, saying how far it has got. XHR, not fetch, because
     *  fetch cannot report upload progress. `extra` rides along as form
     *  fields — a recording's title and story. */
    function upload(url, field, file, extra) {
        const row = document.createElement('div');
        row.className = 'ne-up';
        row.innerHTML = '<div class="ne-up-top"><span class="ne-up-name"></span><span class="ne-up-pct">0%</span></div>'
            + '<div class="ne-up-bar"><div class="ne-up-fill"></div></div>';
        row.querySelector('.ne-up-name').textContent = (file.name || 'File') + (file.size ? ' · ' + fmtSize(file.size) : '');
        $('noteEditorUploads').appendChild(row);
        const pct = row.querySelector('.ne-up-pct');
        const fill = row.querySelector('.ne-up-fill');

        uploadsInFlight++;
        return new Promise((resolve, reject) => {
            const form = new FormData(); form.append(field, file);
            // A recording travels with the name and story it was just asked
            // for; a plain picked file sends none of these.
            Object.entries(extra || {}).forEach(([k, v]) => {
                if (v !== null && v !== undefined && v !== '') form.append(k, v);
            });
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.withCredentials = true;
            xhr.setRequestHeader('X-CSRF-TOKEN', CSRF);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.addEventListener('progress', (e) => {
                if (!e.lengthComputable) return;
                const n = Math.min(99, Math.round((e.loaded / e.total) * 100));
                pct.textContent = n + '%';
                fill.style.width = n + '%';
            });
            xhr.upload.addEventListener('load', () => { pct.textContent = 'Processing…'; fill.style.width = '100%'; });
            const fail = (msg) => {
                row.classList.add('is-error');
                pct.textContent = 'Failed';
                setTimeout(() => row.remove(), 4000);
                reject(new Error(msg));
            };
            xhr.addEventListener('load', () => {
                let json = null;
                try { json = JSON.parse(xhr.responseText); } catch (_) { /* handled below */ }
                if (!json || !json.success) return fail((json && json.message) || 'Upload failed.');
                pct.textContent = 'Done';
                setTimeout(() => row.remove(), 700);
                resolve(json.data);
            });
            xhr.addEventListener('error', () => fail('Upload failed — check your connection.'));
            xhr.addEventListener('abort', () => fail('Upload cancelled.'));
            xhr.send(form);
        }).finally(() => { uploadsInFlight--; });
    }

    // ---- Photos: taken here, or already on the device. Same road after that.
    $('noteEditorPhoto').addEventListener('click', () => $('noteEditorPhotoInput').click());
    $('noteEditorCamera').addEventListener('click', () => $('noteEditorCameraInput').click());
    const onPicked = async (e) => {
        const files = Array.from(e.target.files || []); e.target.value = '';
        for (const file of files) {
            try { const d = await upload(cfg.imageUploadUrl, 'image', file); media.push({ type: 'image', path: d.path, url: d.url }); renderThumbs(); }
            catch (err) { window.toast?.(err.message, 'error'); }
        }
    };
    $('noteEditorPhotoInput').addEventListener('change', onPicked);
    $('noteEditorCameraInput').addEventListener('change', onPicked);

    // ---- Video (shared partial writes the file into .js-video-file)
    const vInput = document.querySelector('#noteEditorSheet .js-video-file');

    async function attachVideo(file, meta) {
        try {
            const d = await upload(cfg.videoUploadUrl, 'video', file, meta);
            media.push({
                type: 'video', path: d.path, poster: d.poster, url: d.url, posterUrl: d.posterUrl,
                // Some upload endpoints echo the name back, older ones do not
                // — what was typed is the truth either way.
                title: (meta && meta.title) || d.title || null,
                description: (meta && meta.description) || d.description || null,
            });
            renderThumbs();
            window.toast?.('Video attached.');
        } catch (err) { window.toast?.(err.message, 'error'); }
    }

    vInput?.addEventListener('change', async () => {
        const file = vInput.files && vInput.files[0]; if (!file) return;
        // A clip filmed just now is asked its name and story while everyone
        // still remembers what it was of; a file picked off the device keeps
        // the quick road. Read-and-clear — the recorder's flag must not
        // outlive this change event.
        const recorded = vInput.dataset.smRecorded === '1';
        delete vInput.dataset.smRecorded;
        vInput.value = '';
        const host = document.querySelector('#noteEditorSheet [data-video-host]');
        if (host && window.plazaClearVideo) window.plazaClearVideo(host);

        if (recorded && window.smAskRecording) {
            window.smAskRecording({
                sizeMB: file.size / 1048576,
                hint: 'Attached to this note — give the clip a name.',
                onSave: ({ title, description }) => attachVideo(file, { title, description }),
            });
            return;
        }
        attachVideo(file, null);
    });

    // ---- Remove a media item
    $('noteEditorMedia').addEventListener('click', (e) => {
        const rm = e.target.closest('[data-rm]'); if (!rm) return;
        media.splice(parseInt(rm.getAttribute('data-rm'), 10), 1); renderThumbs();
    });

    /* ---- Draw → upload → attach ------------------------------------------
     * Two ways to keep it, chosen in the canvas: "Save as image" leaves a flat
     * picture, "Save as drawing" also stores the strokes so the drawing can be
     * reopened and changed later. The strokes ride in the note's own media
     * record rather than a file, which also means they survive a wiped disk.
     * `index` reopens an existing drawing in place instead of adding another. */
    async function uploadDrawing(dataUrl, objects, index) {
        try {
            const res = await fetch(cfg.drawUploadUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ image: dataUrl }), credentials: 'same-origin' });
            const json = await res.json().catch(() => ({}));
            if (!json.success || !json.data?.url) throw new Error(json.message || 'Upload failed.');
            const entry = objects && objects.length
                ? { type: 'drawing', path: json.data.path, url: json.data.url, strokes: objects }
                : { type: 'image', path: json.data.path, url: json.data.url };
            if (index != null && media[index]) media[index] = entry;
            else media.push(entry);
            renderThumbs();
            window.toast?.(entry.type === 'drawing' ? 'Drawing saved — tap it to edit again.' : 'Drawing added.');
        } catch (err) { window.toast?.(err.message || 'Could not add drawing.', 'error'); }
    }

    // Tapping a saved drawing in the editor reopens it with its strokes.
    $('noteEditorMedia').addEventListener('click', (e) => {
        if (e.target.closest('[data-rm]')) return;              // the remove ✕
        const tile = e.target.closest('[data-edit-draw]');
        if (!tile) return;
        e.preventDefault();
        e.stopPropagation();
        const i = parseInt(tile.getAttribute('data-edit-draw'), 10);
        const m = media[i];
        if (!m || typeof window.openDrawCanvas !== 'function') return;
        // null, not [] — the pad only falls back to drawing over the PNG when
        // objects is empty, and a drawing whose strokes were lost (older saves
        // stripped them) must land on that fallback rather than a blank sheet.
        window.openDrawCanvas((dataUrl, objects) => uploadDrawing(dataUrl, objects, i), m.url,
            { editable: true, objects: (m.strokes && m.strokes.length) ? m.strokes : null });
    }, true);

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
        // Saving mid-upload filed the note without its video — the clip had
        // not joined the media list yet, and nobody was told.
        if (uploadsInFlight > 0) {
            window.toast?.('Still uploading an attachment — one moment, then save.', 'error');
            return;
        }
        const raw = quill ? quill.root.innerHTML : '';
        const body = (raw === '<p><br></p>' || raw.trim() === '') ? '' : raw;
        // The whole item goes back, urls and strokes included: the server
        // keeps only what it stores, but the caller repaints its chips from
        // this array and a stripped one rendered none until the next reload —
        // and dropping strokes here was flattening every edited drawing.
        const payloadMedia = media.map((m) => Object.assign({}, m, { poster: m.poster || null, strokes: m.strokes || null }));
        const wantTitle = !$('noteEditorTitleWrap').hidden;
        const noteTitle = ($('noteEditorTitleInput').value || '').trim();
        if (wantTitle && !noteTitle) {
            window.toast?.('Give this note a title.', 'error');
            $('noteEditorTitleInput').focus();
            return;
        }
        cfg.onSave && cfg.onSave({ body, media: payloadMedia, noteTitle, lotId: tagLot, activityId: tagAct });
        close();
    });
    $('noteEditorDelete').addEventListener('click', () => { cfg.onDelete && cfg.onDelete(); close(); });

    function close() { pop.classList.remove('is-open'); window.closeSheet?.('noteEditorSheet'); }

    window.openNoteEditor = function (opts) {
        cfg = opts || {};
        ensureQuill();
        $('noteEditorTitle').textContent = opts.title || 'Note';
        // Load through Quill rather than assigning root.innerHTML. Quill decides
        // whether to draw the placeholder from its own ql-blank class, which it
        // only maintains for content it applied itself — writing the HTML in
        // behind its back left it believing the editor was empty, so the
        // placeholder sat on top of the note.
        if (quill) {
            const bodyHtml = opts.bodyHtml || '';
            quill.setContents([]);
            if (bodyHtml.trim() !== '') quill.clipboard.dangerouslyPasteHTML(bodyHtml);
        }
        const wantTitle = !!opts.askTitle;
        $('noteEditorTitleWrap').hidden = !wantTitle;
        $('noteEditorTitleInput').value = opts.noteTitle || '';
        tagLot = opts.tags?.lotId ? Number(opts.tags.lotId) : null;
        tagAct = opts.tags?.activityId ? Number(opts.tags.activityId) : null;
        paintTags();
        media = Array.isArray(opts.media) ? opts.media.map((m) => ({ ...m })) : [];
        $('noteEditorUploads').innerHTML = '';
        renderThumbs();
        const del = $('noteEditorDelete');
        del.classList.toggle('hidden', !opts.onDelete);
        del.textContent = opts.deleteLabel || 'Delete';
        $('noteEditorSave').textContent = opts.saveLabel || 'Save note';
        window.openSheet?.('noteEditorSheet');

        // On a phone the keyboard would cover the note you just opened, before
        // you have decided whether to change it. Focus cannot simply be cleared
        // afterwards — Quill takes it while mounting and again when the body is
        // set, so anything reacting later is racing it. The keyboard follows a
        // focusable contenteditable, so do not present one: open read-only, and
        // let the first tap enable editing and place the caret where the finger
        // landed. Writing still takes a single tap. A mouse opens ready to type.
        if (window.matchMedia('(pointer: coarse)').matches) {
            const root = quill && quill.root;
            if (root) {
                root.setAttribute('contenteditable', 'false');
                try { quill.blur(); } catch (_) {}
                root.blur?.();
                root.addEventListener('pointerdown', () => {
                    root.setAttribute('contenteditable', 'true');
                    setTimeout(() => { try { quill.focus(); } catch (_) {} }, 0);
                }, { once: true });
            }
        } else {
            setTimeout(() => quill && quill.focus(), 250);
        }
    };
})();
</script>
