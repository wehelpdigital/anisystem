@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Notes — ' . $schedule->title)
@section('page-title', 'Notes')
@section('page-subtitle', $schedule->title)
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        .note-body { font-size: .9rem; line-height: 1.55; color: var(--tl-text-muted, #4b5563); }
        .note-body p { margin: .3rem 0; } .note-body ul { list-style: disc; padding-left: 1.2rem; }
        .note-body ol { list-style: decimal; padding-left: 1.35rem; }
        .note-body img { max-width: 100%; border-radius: .5rem; margin: .35rem 0; }
        .note-photo { max-height: 240px; border-radius: .6rem; }
        .note-quill .ql-container { min-height: 9rem; border-bottom-left-radius: .75rem; border-bottom-right-radius: .75rem; }
        .note-quill .ql-toolbar { border-top-left-radius: .75rem; border-top-right-radius: .75rem; }
    </style>
@endpush

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'notes'])

<button type="button" class="btn btn-primary w-full mb-4 hidden md:inline-flex" data-note-add>
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    New note
</button>

<div class="space-y-3" id="notesList" data-animate-list>
    @foreach ($notes as $n)
        <div class="card p-4 note-card" data-id="{{ $n->id }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 grow">
                    <h3 class="font-bold text-gray-900 leading-snug js-title">{{ $n->title }}</h3>
                    <p class="text-xs text-gray-400 mt-0.5 js-time">{{ $n->updated_at?->diffForHumans() }}</p>
                </div>
                <div class="flex gap-1 shrink-0">
                    <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit note">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete note">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
            @if (filled($n->body))
                <div class="note-body mt-2">{!! $n->body !!}</div>
            @endif
            @if (filled($n->imagePath))
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($n->imagePath) }}" alt="" class="note-photo mt-3" loading="lazy">
            @endif
        </div>
    @endforeach
</div>

<div class="card p-8 text-center {{ $notes->isEmpty() ? '' : 'hidden' }}" id="notesEmpty">
    <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <p class="font-semibold text-gray-700 mt-3">No notes yet</p>
    <p class="text-sm text-gray-500 mt-1">Jot down observations, reminders or anything worth remembering — attach a photo too.</p>
    <button type="button" class="btn btn-primary mt-4" data-note-add>New note</button>
</div>

<button type="button" data-note-add aria-label="New note"
    class="fixed bottom-24 right-4 z-30 w-14 h-14 rounded-full shadow-lg md:hidden flex items-center justify-center bg-brand-600 text-white hover:bg-brand-700 active:bg-brand-800">
    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
</button>
@endsection

@push('sheets')
<div class="sheet hidden" id="noteSheet" style="--sheet-width:34rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="noteSheetTitle">New note</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <input type="hidden" id="noteId">
        <input type="hidden" id="noteImagePath">
        <div class="mb-4">
            <label class="form-label" for="noteTitle">Title <span class="text-red-500">*</span></label>
            <input type="text" id="noteTitle" class="form-input" maxlength="191" placeholder="e.g. Pest scouting — west corner">
        </div>
        <div class="mb-4">
            <label class="form-label">Note</label>
            <div class="note-quill">
                <div id="noteEditor"></div>
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label" for="notePhoto">Photo</label>
            <input type="file" id="notePhoto" class="form-input" accept="image/*" capture="environment">
            <p class="form-hint">Take or choose a photo to attach to this note.</p>
            <div id="notePhotoPreview" class="mt-2 hidden">
                <img src="" alt="" class="note-photo">
                <button type="button" class="btn btn-sm btn-ghost text-red-600 mt-1" id="notePhotoRemove">Remove photo</button>
            </div>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="noteSaveBtn">Save note</button>
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
const __init = () => {
    const SCHEDULE_ID = @json($schedule->id);
    const URLS = {
        store: @json(route('sm.notes.store')) + '?scheduleId=' + SCHEDULE_ID,
        update: (id) => @json(route('sm.notes.update')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
        destroy: (id) => @json(route('sm.notes.destroy')) + '?scheduleId=' + SCHEDULE_ID + '&id=' + id,
        upload: @json(route('sm.notes.image-upload')) + '?scheduleId=' + SCHEDULE_ID,
    };
    @php
        $seed = $notes->mapWithKeys(fn ($n) => [$n->id => [
            'id' => $n->id, 'title' => $n->title, 'body' => $n->body,
            'imagePath' => $n->imagePath,
            'imageUrl' => $n->imagePath ? \Illuminate\Support\Facades\Storage::disk('public')->url($n->imagePath) : null,
        ]]);
    @endphp
    const NOTES = @json($seed->isEmpty() ? new stdClass() : $seed);

    const list = document.getElementById('notesList');
    const emptyEl = document.getElementById('notesEmpty');
    const fld = (id) => document.getElementById(id);
    let quill = null;

    /* ---- Quill, lazy-loaded from CDN (open-source, no paid tier) ---- */
    function loadQuill() {
        if (window.Quill) return Promise.resolve();
        return new Promise((resolve, reject) => {
            const css = document.createElement('link');
            css.rel = 'stylesheet'; css.href = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css';
            document.head.appendChild(css);
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.min.js';
            s.onload = resolve; s.onerror = reject; document.head.appendChild(s);
        });
    }
    async function ensureQuill() {
        await loadQuill();
        if (!quill) {
            quill = new Quill('#noteEditor', {
                theme: 'snow', placeholder: 'Write your note…',
                modules: { toolbar: [['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link', 'clean']] },
            });
        }
        return quill;
    }

    function setPhoto(path, url) {
        fld('noteImagePath').value = path || '';
        const w = fld('notePhotoPreview');
        w.classList.toggle('hidden', !path);
        w.querySelector('img').src = url || '';
    }

    async function openNoteSheet(note = null) {
        fld('noteId').value = note ? note.id : '';
        fld('noteSheetTitle').textContent = note ? 'Edit note' : 'New note';
        fld('noteTitle').value = note ? note.title : '';
        fld('notePhoto').value = '';
        setPhoto(note ? note.imagePath : '', note ? note.imageUrl : '');
        openSheet('noteSheet');
        const q = await ensureQuill();
        q.root.innerHTML = note && note.body ? note.body : '';
    }

    document.querySelectorAll('[data-note-add]').forEach((b) => b.addEventListener('click', () => openNoteSheet(null)));
    fld('notePhotoRemove').addEventListener('click', () => setPhoto('', ''));

    fld('notePhoto').addEventListener('change', async (e) => {
        const file = e.target.files && e.target.files[0]; if (!file) return;
        const form = new FormData(); form.append('image', file);
        try {
            const res = await fetch(URLS.upload, { method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                body: form, credentials: 'same-origin' });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Upload failed.');
            setPhoto(json.data.path, json.data.url); toast(json.message);
        } catch (err) { toast(err.message, 'error'); e.target.value = ''; }
    });

    /** Mirrors the Blade card above. */
    function renderCard(n) {
        const el = document.createElement('div');
        el.className = 'card p-4 note-card';
        el.dataset.id = n.id;
        el.innerHTML = `
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 grow">
                    <h3 class="font-bold text-gray-900 leading-snug js-title">${escapeHtml(n.title)}</h3>
                    <p class="text-xs text-gray-400 mt-0.5 js-time">just now</p>
                </div>
                <div class="flex gap-1 shrink-0">
                    <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit note"><svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                    <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete note"><svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                </div>
            </div>
            ${n.body ? `<div class="note-body mt-2">${n.body}</div>` : ''}
            ${n.imageUrl ? `<img src="${escapeHtml(n.imageUrl)}" alt="" class="note-photo mt-3" loading="lazy">` : ''}`;
        return el;
    }

    function refreshEmpty() {
        emptyEl.classList.toggle('hidden', list.querySelectorAll('.note-card').length > 0);
    }

    fld('noteSaveBtn').addEventListener('click', async () => {
        const id = fld('noteId').value;
        const title = fld('noteTitle').value.trim();
        if (!title) { toast('Give the note a title.', 'error'); return; }
        const body = quill ? quill.root.innerHTML : '';
        const payload = { title, body: (body === '<p><br></p>' ? null : body), imagePath: fld('noteImagePath').value || null };

        const btn = fld('noteSaveBtn'); btn.disabled = true;
        try {
            const res = await api(id ? URLS.update(id) : URLS.store, { method: id ? 'PUT' : 'POST', body: payload });
            const n = { id: res.data.id, title: res.data.title, body: res.data.body, imagePath: res.data.imagePath, imageUrl: res.data.imageUrl };
            NOTES[n.id] = n;
            const fresh = renderCard(n);
            const existing = list.querySelector('.note-card[data-id="' + n.id + '"]');
            if (existing) existing.replaceWith(fresh); else list.prepend(fresh);
            refreshEmpty(); closeSheet('noteSheet'); toast(res.message);
        } catch (err) { toast(err.message, 'error'); } finally { btn.disabled = false; }
    });

    list.addEventListener('click', async (e) => {
        const card = e.target.closest('.note-card'); if (!card) return;
        const id = card.dataset.id;
        if (e.target.closest('.js-edit')) { openNoteSheet(NOTES[id] || null); return; }
        if (e.target.closest('.js-delete')) {
            const name = NOTES[id] ? NOTES[id].title : 'this note';
            const ok = await confirmAction({ title: 'Delete note?', message: '"' + name + '" will be removed.', confirmText: 'Delete' });
            if (!ok) return;
            try {
                const res = await api(URLS.destroy(id), { method: 'DELETE' });
                delete NOTES[id];
                const finish = () => { card.remove(); refreshEmpty(); };
                if (window.animateOut) window.animateOut(card, finish); else finish();
                toast(res.message);
            } catch (err) { toast(err.message, 'error'); }
        }
    });
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
