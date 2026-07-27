@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Documentation — ' . $schedule->title)
@section('page-title', 'Documentation')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'documentation'])

<div>
    <button type="button" class="btn btn-primary w-full mb-4 hidden md:inline-flex" data-doc-add>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        Add Document
        <span class="badge badge-gray ml-1" id="docCount">{{ $schedule->docEntries->count() }}</span>
    </button>

    <div id="docList" class="space-y-3" data-animate-list></div>

    <div class="card p-8 text-center hidden mt-3" id="docEmpty">
        <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <p class="font-semibold text-gray-700 mt-3">No documents yet</p>
        <p class="text-sm text-gray-500 mt-1">Add a protocol, introduction, critical rule, miscellaneous note, or any tagged reference — each with rich text and files.</p>
        <button type="button" class="btn btn-primary mt-4" data-doc-add>Add Document</button>
    </div>

    {{-- Mobile FAB --}}
    <button type="button" data-doc-add aria-label="Add document"
        class="fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full btn-primary shadow-lg md:hidden flex items-center justify-center bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    </button>
</div>
@endsection

@push('sheets')
{{-- Add / edit document --}}
<div class="sheet hidden" id="docSheet" style="--sheet-width:36rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="docSheetTitle">Add Document</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <input type="hidden" id="docId" value="">

        <div class="mb-4">
            <label class="form-label" for="docType">Type <span class="text-red-500">*</span></label>
            <select id="docType" class="form-select"></select>
        </div>

        {{-- New-tag inline creator (shown when "+ Add new tag" is picked) --}}
        <div class="mb-4 hidden" id="docNewTagWrap">
            <label class="form-label" for="docNewTag">New tag name</label>
            <div class="flex items-center gap-2">
                <input type="text" id="docNewTag" class="form-input flex-1 min-w-0!" maxlength="100" placeholder="e.g. Mixing Chart">
                <button type="button" class="btn btn-white shrink-0" id="docNewTagAdd">Add</button>
            </div>
            <p class="form-hint">Saved to this schedule so you can pick it again later.</p>
        </div>

        <div class="mb-4">
            <label class="form-label" for="docTitle">Title <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="text" id="docTitle" class="form-input" maxlength="255" placeholder="e.g. Foliar mixing chart">
        </div>

        <div class="mb-4">
            <label class="form-label">Content</label>
            <div class="rich-editor">
                <div id="docContentEditor"></div>
            </div>
        </div>

        <div class="mb-2">
            <label class="form-label">Files <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="file" id="docFiles" multiple accept="image/*,.pdf,.doc,.docx,.txt,.xls,.xlsx"
                class="flex items-center w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:text-brand-700 file:font-semibold file:px-4 file:py-2.5 file:cursor-pointer cursor-pointer">
            <p class="form-hint">Add any number of files — images, PDF, Word, Excel or TXT. Max 10 MB each.</p>
            <div id="docFileList" class="flex flex-wrap gap-2 mt-3"></div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="docSaveBtn">Save Document</button>
    </div>
</div>
@endpush

@push('scripts')
@php
    // Shape each entry for the front-end: resolve the label + public file URLs.
    $docEntriesSeed = $schedule->docEntries->mapWithKeys(fn ($e) => [$e->id => [
        'id' => $e->id,
        'type' => $e->type,
        'tagId' => $e->tagId,
        'typeLabel' => $e->type_label,
        'title' => $e->title,
        'content' => $e->content,
        'files' => collect($e->files ?? [])->map(fn ($f) => [
            'path' => $f['path'] ?? null,
            'name' => $f['name'] ?? 'file',
            'size' => (int) ($f['size'] ?? 0),
            'mime' => $f['mime'] ?? null,
            'url' => isset($f['path']) ? Storage::disk('public')->url($f['path']) : null,
            'isImage' => isset($f['mime']) && str_starts_with((string) $f['mime'], 'image/'),
        ])->values(),
    ]]);
    $docTagsSeed = $schedule->docTags->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values();
@endphp
<script>
(function () {
    const SCHEDULE_ID = @json($schedule->id);
    const URLS = {
        docStore: @json(route('sm.doc-entries.store')) + '?scheduleId=' + SCHEDULE_ID,
        docUpdate: (id) => @json(route('sm.doc-entries.update')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
        docDestroy: (id) => @json(route('sm.doc-entries.destroy')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
        tagStore: @json(route('sm.doc-tags.store')) + '?scheduleId=' + SCHEDULE_ID,
    };

    /* ================================================================= */
    /* Shared rich-text editor (Quill 2, lazy-loaded from CDN)           */
    /* ================================================================= */
    let quillLoading = null;
    function ensureQuill() {
        if (window.Quill) return Promise.resolve();
        if (quillLoading) return quillLoading;
        quillLoading = new Promise((resolve, reject) => {
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css';
            document.head.appendChild(css);
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.min.js';
            s.onload = () => resolve();
            s.onerror = () => { quillLoading = null; reject(new Error('Could not load the editor. Check your connection and try again.')); };
            document.head.appendChild(s);
        });
        return quillLoading;
    }
    const RICH_TOOLBAR = [
        [{ header: [2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link'],
        ['clean'],
    ];
    const _editors = {};
    async function mountEditor(elId, placeholder = '') {
        await ensureQuill();
        if (!_editors[elId]) {
            _editors[elId] = new Quill('#' + elId, {
                theme: 'snow',
                placeholder,
                modules: { toolbar: RICH_TOOLBAR },
            });
        }
        return _editors[elId];
    }
    function setEditorHtml(q, html) {
        q.setContents([]);
        if (html && html.trim() !== '') q.clipboard.dangerouslyPasteHTML(html);
    }
    function editorHtml(q) {
        return q && q.getText().trim() !== '' ? q.root.innerHTML : '';
    }

    /* ================================================================= */
    /* Documents (unified list — protocol / intro / rule / misc / tag)   */
    /* ================================================================= */
    const ENTRIES = @json($docEntriesSeed->isEmpty() ? new stdClass() : $docEntriesSeed);
    let TAGS = @json($docTagsSeed);

    const BUILTIN = [
        { value: 'protocol', label: 'Protocol' },
        { value: 'introduction', label: 'Introduction' },
        { value: 'critical_rule', label: 'Critical Rule' },
        { value: 'miscellaneous', label: 'Miscellaneous' },
    ];
    const TYPE_CLASS = {
        protocol: 'doc-badge-protocol',
        introduction: 'doc-badge-introduction',
        critical_rule: 'doc-badge-critical_rule',
        miscellaneous: 'doc-badge-miscellaneous',
        custom: 'doc-badge-custom',
    };

    const listEl = document.getElementById('docList');
    const emptyEl = document.getElementById('docEmpty');
    const countEl = document.getElementById('docCount');
    const fld = (id) => document.getElementById(id);

    const fmtSize = (bytes) => {
        const n = Number(bytes || 0);
        if (n < 1024) return n + ' B';
        if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
        return (n / (1024 * 1024)).toFixed(1) + ' MB';
    };
    const ext = (name) => ((name || '').split('.').pop() || 'FILE').toUpperCase().slice(0, 4);

    function fileChipHtml(f) {
        const inner = f.isImage && f.url
            ? `<img src="${escapeHtml(f.url)}" alt="${escapeHtml(f.name)}" class="w-7 h-7 rounded object-cover shrink-0">`
            : `<span class="doc-file-ext">${escapeHtml(ext(f.name))}</span>`;
        const open = f.url ? ` href="${escapeHtml(f.url)}" target="_blank" rel="noopener"` : '';
        return `<a class="doc-file-chip"${open}>
            ${inner}
            <span class="min-w-0">
                <span class="block truncate text-xs font-semibold text-gray-800">${escapeHtml(f.name)}</span>
                <span class="block text-[10px] text-gray-400">${escapeHtml(fmtSize(f.size))}</span>
            </span>
        </a>`;
    }

    function entryCardHtml(e) {
        const badgeClass = TYPE_CLASS[e.type] || 'doc-badge-custom';
        const title = e.title ? `<span class="font-bold text-gray-900 leading-snug">${escapeHtml(e.title)}</span>` : '';
        const content = e.content ? `<div class="rich-text text-sm text-gray-700 mt-2 js-content">${e.content}</div>` : '';
        const files = (e.files && e.files.length)
            ? `<div class="flex flex-wrap gap-2 mt-3 js-files">${e.files.map(fileChipHtml).join('')}</div>`
            : '';
        return `
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 grow">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="doc-badge ${badgeClass}">${escapeHtml(e.typeLabel)}</span>
                        ${title}
                    </div>
                    ${content}
                    ${files}
                </div>
                <div class="flex gap-1 shrink-0">
                    <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit document">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete document">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>`;
    }

    function upsertCard(e) {
        let card = listEl.querySelector('.doc-entry-card[data-id="' + e.id + '"]');
        if (!card) {
            card = document.createElement('div');
            card.className = 'card p-4 doc-entry-card';
            card.dataset.id = e.id;
            listEl.appendChild(card);
        }
        card.innerHTML = entryCardHtml(e);
    }

    function refreshEmptyState() {
        const n = listEl.querySelectorAll('.doc-entry-card').length;
        emptyEl.classList.toggle('hidden', n > 0);
        countEl.textContent = n;
    }

    function renderAll() {
        listEl.innerHTML = '';
        Object.values(ENTRIES).forEach(upsertCard);
        refreshEmptyState();
    }

    /* ---- type dropdown ---- */
    function typeSelectValue(e) {
        return e.type === 'custom' ? ('custom:' + e.tagId) : e.type;
    }
    function buildTypeOptions(selectedValue) {
        const sel = fld('docType');
        let html = '';
        BUILTIN.forEach((b) => { html += `<option value="${b.value}">${escapeHtml(b.label)}</option>`; });
        if (TAGS.length) {
            html += '<optgroup label="Tags">';
            TAGS.forEach((t) => { html += `<option value="custom:${t.id}">${escapeHtml(t.name)}</option>`; });
            html += '</optgroup>';
        }
        html += '<option value="__new__">+ Add new tag…</option>';
        sel.innerHTML = html;
        if (selectedValue) sel.value = selectedValue;
    }

    fld('docType').addEventListener('change', () => {
        const isNew = fld('docType').value === '__new__';
        fld('docNewTagWrap').classList.toggle('hidden', !isNew);
        if (isNew) fld('docNewTag').focus();
    });

    fld('docNewTagAdd').addEventListener('click', async () => {
        const name = fld('docNewTag').value.trim();
        if (!name) { toast('Enter a tag name.', 'error'); return; }
        const btn = fld('docNewTagAdd');
        btn.disabled = true;
        try {
            const res = await api(URLS.tagStore, { method: 'POST', body: { name } });
            const tag = res.data;
            if (!TAGS.some((t) => t.id === tag.id)) TAGS.push(tag);
            buildTypeOptions('custom:' + tag.id);
            fld('docNewTag').value = '';
            fld('docNewTagWrap').classList.add('hidden');
            toast(res.message);
        } catch (e) {
            toast(e.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ---- files in the sheet: new picks + kept existing ---- */
    let newFiles = [];   // File[]
    let keepFiles = [];  // existing file objects to keep

    function renderSheetFiles() {
        const wrap = fld('docFileList');
        const existing = keepFiles.map((f, i) => `
            <span class="doc-file-chip" data-keep="${i}">
                ${f.isImage && f.url ? `<img src="${escapeHtml(f.url)}" class="w-7 h-7 rounded object-cover shrink-0">` : `<span class="doc-file-ext">${escapeHtml(ext(f.name))}</span>`}
                <span class="min-w-0"><span class="block truncate text-xs font-semibold text-gray-800">${escapeHtml(f.name)}</span><span class="block text-[10px] text-gray-400">${escapeHtml(fmtSize(f.size))}</span></span>
                <button type="button" class="doc-file-x" data-remove-keep="${i}" aria-label="Remove">✕</button>
            </span>`).join('');
        const picked = newFiles.map((f, i) => `
            <span class="doc-file-chip doc-file-new" data-new="${i}">
                <span class="doc-file-ext">${escapeHtml(ext(f.name))}</span>
                <span class="min-w-0"><span class="block truncate text-xs font-semibold text-gray-800">${escapeHtml(f.name)}</span><span class="block text-[10px] text-gray-400">${escapeHtml(fmtSize(f.size))}</span></span>
                <button type="button" class="doc-file-x" data-remove-new="${i}" aria-label="Remove">✕</button>
            </span>`).join('');
        wrap.innerHTML = existing + picked;
    }

    fld('docFiles').addEventListener('change', (ev) => {
        for (const f of ev.target.files) {
            if (f.size > 10 * 1024 * 1024) { toast(`"${f.name}" is over 10 MB — skipped.`, 'error'); continue; }
            newFiles.push(f);
        }
        ev.target.value = '';   // allow re-picking; we keep our own list
        renderSheetFiles();
    });

    fld('docFileList').addEventListener('click', (ev) => {
        const rn = ev.target.closest('[data-remove-new]');
        const rk = ev.target.closest('[data-remove-keep]');
        if (rn) { newFiles.splice(Number(rn.dataset.removeNew), 1); renderSheetFiles(); }
        else if (rk) { keepFiles.splice(Number(rk.dataset.removeKeep), 1); renderSheetFiles(); }
    });

    /* ---- open sheet (add / edit) ---- */
    let docEditor = null;
    async function openDocSheet(entry = null) {
        fld('docId').value = entry ? entry.id : '';
        fld('docSheetTitle').textContent = entry ? 'Edit Document' : 'Add Document';
        buildTypeOptions(entry ? typeSelectValue(entry) : 'protocol');
        fld('docNewTagWrap').classList.add('hidden');
        fld('docNewTag').value = '';
        fld('docTitle').value = entry ? (entry.title || '') : '';
        newFiles = [];
        keepFiles = entry && entry.files ? entry.files.slice() : [];
        renderSheetFiles();
        openSheet('docSheet');
        try {
            docEditor = await mountEditor('docContentEditor', 'Write the document…');
            setEditorHtml(docEditor, entry ? (entry.content || '') : '');
        } catch (e) {
            toast(e.message, 'error');
        }
    }

    document.querySelectorAll('[data-doc-add]').forEach((btn) => btn.addEventListener('click', () => openDocSheet(null)));

    /* ---- save ---- */
    fld('docSaveBtn').addEventListener('click', async () => {
        const id = fld('docId').value;
        const typeVal = fld('docType').value;
        if (typeVal === '__new__') { toast('Add the new tag first, or pick a type.', 'error'); return; }

        let type = typeVal, tagId = null;
        if (typeVal.startsWith('custom:')) { type = 'custom'; tagId = typeVal.slice(7); }

        const content = editorHtml(docEditor);
        const title = fld('docTitle').value.trim();
        if (!content && !title && !newFiles.length && !keepFiles.length) {
            toast('Add some text, a title, or a file.', 'error');
            return;
        }

        const fd = new FormData();
        fd.append('type', type);
        if (tagId) fd.append('tagId', tagId);
        fd.append('title', title);
        fd.append('content', content);
        newFiles.forEach((f) => fd.append('files[]', f));
        keepFiles.forEach((f) => fd.append('keepPaths[]', f.path));

        const btn = fld('docSaveBtn');
        btn.disabled = true;
        try {
            const res = await api(id ? URLS.docUpdate(id) : URLS.docStore, { method: 'POST', body: fd });
            const e = res.data;
            ENTRIES[e.id] = e;
            upsertCard(e);
            refreshEmptyState();
            closeSheet('docSheet');
            toast(res.message);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    /* ---- row actions ---- */
    listEl.addEventListener('click', async (e) => {
        const card = e.target.closest('.doc-entry-card');
        if (!card) return;
        const id = card.dataset.id;

        if (e.target.closest('.js-edit')) {
            openDocSheet(ENTRIES[id] || null);
            return;
        }
        if (e.target.closest('.js-delete')) {
            const label = ENTRIES[id] ? (ENTRIES[id].title || ENTRIES[id].typeLabel) : 'this document';
            const ok = await confirmAction({
                title: 'Delete document?',
                message: '"' + label + '" will be removed from this schedule.',
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const res = await api(URLS.docDestroy(id), { method: 'DELETE' });
                delete ENTRIES[id];
                card.remove();
                refreshEmptyState();
                toast(res.message);
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });

    // app.js (deferred) defines the shared globals (escapeHtml/api/toast/…).
    // It runs after this inline script, so hold the first render until the
    // document is ready and those globals exist.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderAll, { once: true });
    } else {
        renderAll();
    }
})();
</script>
@endpush
