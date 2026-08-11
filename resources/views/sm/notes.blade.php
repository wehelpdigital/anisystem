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
        .note-quill .ql-container { min-height: 3.25rem; border-bottom-left-radius: .75rem; border-bottom-right-radius: .75rem; }
        .note-quill .ql-editor { min-height: 3.25rem; }
        .note-quill .ql-toolbar { border-top-left-radius: .75rem; border-top-right-radius: .75rem; }

        /* Accordion: each note folds to its header; the chevron flags state. */
        .note-head { cursor: pointer; }
        .note-origin { margin-top: .2rem; display: inline-flex; }
        .note-fold { display: grid; grid-template-rows: 1fr; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
        .note-fold-inner { overflow: hidden; min-height: 0; }
        .note-card.is-collapsed .note-fold { grid-template-rows: 0fr; }
        .note-chevron { transition: transform .2s ease; color: #9ca3af; }
        .note-card:not(.is-collapsed) .note-chevron { transform: rotate(90deg); }
        @media (prefers-reduced-motion: reduce) { .note-fold, .note-chevron { transition: none; } }

        /* Media gallery on a card */
        .note-media { display: grid; grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr)); gap: .5rem; margin-top: .6rem; }
        .note-media .nm { position: relative; border-radius: .6rem; overflow: hidden; background: #000; aspect-ratio: 1; }
        .note-media .nm img, .note-media .nm video { width: 100%; height: 100%; object-fit: cover; display: block; }
        /* Editor thumbs (with a remove control) */
        .note-mthumbs:empty { display: none; }
        .note-mthumbs { display: grid; grid-template-columns: repeat(auto-fill, minmax(5.5rem, 1fr)); gap: .5rem; }
        .note-mthumb { position: relative; aspect-ratio: 1; border-radius: .55rem; overflow: hidden; border: 1px solid var(--color-gray-200); background: #000; }
        .note-mthumb img, .note-mthumb video { width: 100%; height: 100%; object-fit: cover; }
        .note-mthumb .rm { position: absolute; top: .2rem; right: .2rem; width: 1.7rem; height: 1.7rem; border-radius: 999px; background: rgb(17 24 39 / .72); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: .9rem; line-height: 1; }
        .note-mthumb .vtag { position: absolute; left: .2rem; bottom: .2rem; font-size: 10px; font-weight: 700; color: #fff; background: rgb(0 0 0 / .5); border-radius: .3rem; padding: 0 .3rem; }
    </style>
@endpush

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'notes'])

<button type="button" class="btn btn-primary w-full mb-4 inline-flex" data-note-add>
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    New note
</button>

<div class="space-y-3" id="notesList" data-animate-list>
    @foreach ($notes as $n)
        @php
            $mediaItems = [];
            if (filled($n->imagePath)) {
                $mediaItems[] = ['type' => 'image', 'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($n->imagePath)];
            }
            // The module's OWN url, not the shell's ?module=maps: inside the
            // Activities shell a link is matched by its base path, and
            // /app/sm-activities matches the Activities module itself — so the
            // shell answered a tap on "View map" by showing the board.
            // A note the map save created points at its own snapshot, so
            // "View map" lands on the map that note is about.
            $saveIdForNote = ($mapSaves ?? collect())->firstWhere('noteId', $n->id)['id'] ?? null;
            $mapUrl = $saveIdForNote
                ? route('sm.maps', ['id' => $schedule->id, 'save' => $saveIdForNote])
                : route('sm.maps', ['id' => $schedule->id]);
            foreach ((is_array($n->media) ? $n->media : []) as $m) {
                if (empty($m['path'])) continue;
                // Maps saved before they announced themselves are recognised by
                // the filename the map save writes, so old notes get the link
                // too rather than a thumbnail that may no longer resolve.
                $isMap = ($m['type'] ?? '') === 'map'
                    || (bool) preg_match('~/map-[A-Za-z0-9]+\.png$~', (string) $m['path']);
                $mediaItems[] = [
                    'type' => $isMap ? 'map' : ($m['type'] ?? 'image'),
                    'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($m['path']),
                    'posterUrl' => ! empty($m['poster']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($m['poster']) : null,
                    'mapUrl' => $isMap ? $mapUrl : null,
                ];
            }
        @endphp
        <div class="card p-4 note-card" data-id="{{ $n->id }}">
            <div class="note-head flex items-start justify-between gap-3">
                <div class="flex items-start gap-2 min-w-0 grow">
                    <svg class="note-chevron w-4 h-4 mt-1 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <div class="min-w-0 grow">
                        <h3 class="font-bold text-gray-900 leading-snug js-title">{{ $n->title }}</h3>
                        @if ($saveIdForNote)
                            {{-- Where this note came from. The card that used to
                                 list saved maps separately said the same thing
                                 twice; the note is the record, this is its
                                 provenance. --}}
                            <span class="badge badge-green note-origin">Team map</span>
                        @endif
                        <p class="text-xs text-gray-400 mt-0.5 js-time">{{ $n->updated_at?->diffForHumans() }}</p>
                    </div>
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
            <div class="note-fold"><div class="note-fold-inner js-note-body-wrap">
                @if (filled($n->body))
                    <div class="note-body mt-2">{!! $n->body !!}</div>
                @endif
                @if (! empty($mediaItems))
                    <div class="note-media">@include('sm.partials.note-media', ['media' => $mediaItems])</div>
                @endif
            </div></div>
        </div>
    @endforeach
</div>

<div class="card p-8 text-center {{ $notes->isEmpty() ? '' : 'hidden' }}" id="notesEmpty">
    <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    <p class="font-semibold text-gray-700 mt-3">No notes yet</p>
    <p class="text-sm text-gray-500 mt-1">Jot down observations, reminders or anything worth remembering — attach a photo too.</p>
    <button type="button" class="btn btn-primary mt-4" data-note-add>New note</button>
</div>
@endsection

@push('sheets')
<div class="sheet sheet-full hidden" id="noteSheet" style="--sheet-width:34rem">
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
            <div class="flex items-center justify-between gap-2">
                <label class="form-label mb-0">Note</label>
                <div class="flex items-center gap-1.5">
                    <button type="button" id="noteEmojiBtn" class="btn btn-white btn-sm">😊 Emoji</button>
                    <button type="button" id="noteDrawBtn" class="btn btn-white btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20l4-1 10-10-3-3L5 16l-1 4z"/></svg>
                        Draw
                    </button>
                </div>
            </div>
            <div class="note-quill mt-1.5">
                <div id="noteEditor"></div>
            </div>
        </div>
        <div class="mb-2" data-video-host>
            <label class="form-label">Photos &amp; videos</label>
            <div id="noteMthumbs" class="note-mthumbs mb-2"></div>
            <div class="flex flex-wrap gap-2">
                <input type="file" id="notePhoto" accept="image/*" capture="environment" class="hidden" multiple>
                <button type="button" id="noteAddPhoto" class="btn btn-white btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Add photo
                </button>
                <input type="file" class="js-video-file hidden" accept="video/*">
                <button type="button" class="btn btn-white btn-sm js-video-attach">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 6h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/></svg>
                    Add video
                </button>
                <button type="button" class="btn btn-white btn-sm js-video-record">
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="7"/></svg>
                    Record
                </button>
                <span class="js-video-chip"></span>
            </div>
            <p class="form-hint">Photos and videos are auto-compressed. Attach as many as you like.</p>
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="noteSaveBtn">Save note</button>
    </div>
</div>
{{-- When opened standalone (not as a module inside the activities shell, which
     already ships the pad), bring our own drawing canvas. --}}
@if (! request()->boolean('partial'))
@include('sm.partials.draw-canvas')
@include('sm.partials.note-lightbox')
@endif
@include('community.partials.video-js')
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
        videoUpload: @json(route('sm.notes.video-upload')) + '?scheduleId=' + SCHEDULE_ID,
    };
    const CSRF = document.querySelector('meta[name=csrf-token]').content;
    @php
        // Same classification as the server-rendered list above, so a note
        // re-rendered after an edit keeps its "View map" tile.
        $mapModuleUrl = route('sm.maps', ['id' => $schedule->id]);
        $mediaUrls = function ($items) use ($mapModuleUrl) {
            return collect(is_array($items) ? $items : [])->map(function ($m) use ($mapModuleUrl) {
                if (empty($m['path'])) {
                    return null;
                }
                $isMap = ($m['type'] ?? '') === 'map'
                    || (bool) preg_match('~/map-[A-Za-z0-9]+\.png$~', (string) $m['path']);

                return [
                    'type' => $isMap ? 'map' : ($m['type'] ?? 'image'),
                    'path' => $m['path'],
                    'strokes' => $m['strokes'] ?? null,
                    'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($m['path']),
                    'poster' => $m['poster'] ?? null,
                    'posterUrl' => ! empty($m['poster']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($m['poster']) : null,
                    'mapUrl' => $isMap ? $mapModuleUrl : null,
                ];
            })->filter()->values()->all();
        };
        $mapNoteIds = ($mapSaves ?? collect())->pluck('noteId')->filter()->flip();
        $seed = $notes->mapWithKeys(fn ($n) => [$n->id => [
            'fromMap' => $mapNoteIds->has($n->id),
            'id' => $n->id, 'title' => $n->title, 'body' => $n->body,
            'imagePath' => $n->imagePath,
            'imageUrl' => $n->imagePath ? \Illuminate\Support\Facades\Storage::disk('public')->url($n->imagePath) : null,
            'media' => $mediaUrls($n->media),
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

    // Working media set for the open editor: {type, path, url, poster?, posterUrl?}
    let media = [];
    function renderMthumbs() {
        fld('noteMthumbs').innerHTML = media.map((m, i) => window.noteMediaThumb
            ? window.noteMediaThumb(m, `<button type="button" class="rm" data-rm="${i}" aria-label="Remove">✕</button>`)
            : '').join('');
    }

    async function openNoteSheet(note = null) {
        fld('noteId').value = note ? note.id : '';
        fld('noteSheetTitle').textContent = note ? 'Edit note' : 'New note';
        fld('noteTitle').value = note ? note.title : '';
        fld('notePhoto').value = '';
        // Seed the gallery: existing media, plus any legacy single photo.
        media = note && Array.isArray(note.media) ? note.media.map((m) => ({ ...m })) : [];
        if (note && note.imageUrl && !media.some((m) => m.url === note.imageUrl)) {
            media.unshift({ type: 'image', path: note.imagePath, url: note.imageUrl });
        }
        renderMthumbs();
        openSheet('noteSheet');
        const q = await ensureQuill();
        q.root.innerHTML = note && note.body ? note.body : '';
    }

    // Where a saved map opens. Read by noteMediaThumb for tiles it builds
    // after an edit, which have no per-item link of their own.
    window.NOTE_MAP_URL = @json(route('sm.maps', ['id' => $schedule->id]));

    document.querySelectorAll('[data-note-add]').forEach((b) => b.addEventListener('click', () => openNoteSheet(null)));
    // Emoji → insert into the note body at the cursor.
    (function () {
        const EMOJIS = ['🌱','🌾','🌽','🍚','🍅','🍆','🥒','🥬','🌶️','🥭','🍌','🥥','☀️','🌤️','🌧️','⛈️','🌈','💧','🌡️','🐛','🐌','🐜','🐔','🐖','🐃','🚜','🧺','🧑‍🌾','😀','😄','😅','🤔','😮','😢','😍','🙏','👍','👏','💪','🤝','❤️','🔥','✅','⚠️','📌','⭐'];
        const pop = document.createElement('div');
        pop.className = 'nb-emoji-pop';
        pop.style.cssText = 'position:fixed;z-index:200;display:none;grid-template-columns:repeat(8,1fr);gap:.1rem;background:var(--color-white);border:1px solid var(--color-gray-200);border-radius:.75rem;padding:.4rem;box-shadow:0 12px 32px -8px rgb(0 0 0 /.35);max-width:17rem;';
        pop.innerHTML = EMOJIS.map((em) => `<button type="button" style="width:2rem;height:2rem;font-size:1.15rem;border-radius:.4rem;background:transparent;cursor:pointer">${em}</button>`).join('');
        document.body.appendChild(pop);
        const eb = fld('noteEmojiBtn');
        eb?.addEventListener('click', (e) => {
            e.stopPropagation();
            if (pop.style.display === 'grid') { pop.style.display = 'none'; return; }
            const r = eb.getBoundingClientRect();
            pop.style.display = 'grid';
            pop.style.left = Math.max(8, Math.min(r.left, window.innerWidth - pop.offsetWidth - 8)) + 'px';
            pop.style.top = Math.min(r.bottom + 6, window.innerHeight - pop.offsetHeight - 8) + 'px';
        });
        pop.addEventListener('click', async (e) => {
            const b = e.target.closest('button'); if (!b) return;
            await ensureQuill();
            const range = quill.getSelection(true) || { index: quill.getLength() };
            quill.insertText(range.index, b.textContent, 'user');
            quill.setSelection(range.index + b.textContent.length, 0);
        });
        document.addEventListener('click', (e) => { if (!e.target.closest('.nb-emoji-pop') && e.target !== eb) pop.style.display = 'none'; });
    })();

    fld('noteAddPhoto').addEventListener('click', () => fld('notePhoto').click());
    // Remove a media item from the working gallery.
    fld('noteMthumbs').addEventListener('click', (e) => {
        const rm = e.target.closest('[data-rm]');
        if (!rm) return;
        media.splice(parseInt(rm.getAttribute('data-rm'), 10), 1);
        renderMthumbs();
    });

    /* ---- Accordion fold state (per note, remembered across visits) ---- */
    const FOLD_KEY = 'noteFold:' + SCHEDULE_ID;
    let collapsedIds = (() => { try { return new Set(JSON.parse(localStorage.getItem(FOLD_KEY) || '[]')); } catch (_) { return new Set(); } })();
    function persistFold() { try { localStorage.setItem(FOLD_KEY, JSON.stringify([...collapsedIds])); } catch (_) { /* ignore */ } }
    function applyFold() { list.querySelectorAll('.note-card').forEach((c) => c.classList.toggle('is-collapsed', collapsedIds.has(String(c.dataset.id)))); }
    applyFold();

    // Draw → upload the sketch → add it as an attachment (not inline).
    fld('noteDrawBtn')?.addEventListener('click', () => {
        if (typeof window.openDrawCanvas !== 'function') { toast('Drawing tool unavailable.', 'error'); return; }
        window.openDrawCanvas(async (dataUrl) => {
            try {
                const res = await api(@json(route('notes.hub.draw')), { method: 'POST', body: { image: dataUrl } });
                const url = res && res.data && res.data.url;
                if (!url) throw new Error('Upload failed.');
                media.push({ type: 'image', path: res.data.path, url });
                renderMthumbs();
                toast('Drawing added.');
            } catch (err) { toast(err.message || 'Could not add drawing.', 'error'); }
        });
    });

    // Add photo(s) — each is compressed server-side and pushed to the gallery.
    fld('notePhoto').addEventListener('change', async (e) => {
        const files = Array.from(e.target.files || []); if (!files.length) return;
        for (const file of files) {
            const form = new FormData(); form.append('image', file);
            try {
                const res = await fetch(URLS.upload, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: form, credentials: 'same-origin' });
                const json = await res.json();
                if (!json.success) throw new Error(json.message || 'Upload failed.');
                media.push({ type: 'image', path: json.data.path, url: json.data.url });
                renderMthumbs();
            } catch (err) { toast(err.message, 'error'); }
        }
        e.target.value = '';
    });

    // Add / record a video — the shared video partial writes the picked or
    // recorded file into .js-video-file; we compress + attach it here.
    const noteVideoInput = document.querySelector('#noteSheet .js-video-file');
    noteVideoInput?.addEventListener('change', async () => {
        const file = noteVideoInput.files && noteVideoInput.files[0]; if (!file) return;
        toast('Compressing video…');
        const form = new FormData(); form.append('video', file);
        try {
            const res = await fetch(URLS.videoUpload, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' }, body: form, credentials: 'same-origin' });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Upload failed.');
            media.push({ type: 'video', path: json.data.path, poster: json.data.poster, url: json.data.url, posterUrl: json.data.posterUrl });
            renderMthumbs();
            toast('Video attached.');
        } catch (err) { toast(err.message, 'error'); }
        noteVideoInput.value = '';
        const host = document.querySelector('#noteSheet [data-video-host]');
        if (host && window.plazaClearVideo) window.plazaClearVideo(host);
    });

    function mediaGalleryHtml(n) {
        const items = [];
        if (n.imageUrl) items.push({ type: 'image', url: n.imageUrl });
        (n.media || []).forEach((m) => items.push(m));
        if (!items.length) return '';
        return `<div class="note-media">${window.noteMediaCells ? window.noteMediaCells(items) : ''}</div>`;
    }

    /** Mirrors the Blade card above (accordion header + folded body/media). */
    function renderCard(n) {
        const el = document.createElement('div');
        el.className = 'card p-4 note-card';
        el.dataset.id = n.id;
        el.innerHTML = `
            <div class="note-head flex items-start justify-between gap-3">
                <div class="flex items-start gap-2 min-w-0 grow">
                    <svg class="note-chevron w-4 h-4 mt-1 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    <div class="min-w-0 grow">
                        <h3 class="font-bold text-gray-900 leading-snug js-title">${escapeHtml(n.title)}</h3>
                        ${n.fromMap ? '<span class="badge badge-green note-origin">Team map</span>' : ''}
                        <p class="text-xs text-gray-400 mt-0.5 js-time">just now</p>
                    </div>
                </div>
                <div class="flex gap-1 shrink-0">
                    <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit note"><svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                    <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete note"><svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                </div>
            </div>
            <div class="note-fold"><div class="note-fold-inner js-note-body-wrap">
                ${n.body ? `<div class="note-body mt-2">${n.body}</div>` : ''}
                ${mediaGalleryHtml(n)}
            </div></div>`;
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
        const payload = {
            title,
            body: (body === '<p><br></p>' ? null : body),
            imagePath: null,
            media: media.map((m) => ({ type: m.type, path: m.path, poster: m.poster || null })),
        };

        const btn = fld('noteSaveBtn'); btn.disabled = true;
        try {
            const res = await api(id ? URLS.update(id) : URLS.store, { method: id ? 'PUT' : 'POST', body: payload });
            const n = { id: res.data.id, title: res.data.title, body: res.data.body, imagePath: res.data.imagePath, imageUrl: res.data.imageUrl, media: res.data.media || [] };
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
        // A tap on the header (but not the action buttons) folds/unfolds the note.
        if (e.target.closest('.note-head') && !e.target.closest('.js-delete')) {
            card.classList.toggle('is-collapsed');
            if (card.classList.contains('is-collapsed')) collapsedIds.add(String(id)); else collapsedIds.delete(String(id));
            persistFold();
            return;
        }
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
