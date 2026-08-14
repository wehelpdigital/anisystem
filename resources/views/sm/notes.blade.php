@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Notes — ' . $schedule->title)
@section('page-title', 'Notes')
@section('page-subtitle', $schedule->title)
@section('help-key', 'notes')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
    <style>
        .note-body { font-size: .9rem; line-height: 1.55; color: var(--tl-text-muted, #4b5563); }
        .note-body p { margin: .3rem 0; } .note-body ul { list-style: disc; padding-left: 1.2rem; }
        .note-body ol { list-style: decimal; padding-left: 1.35rem; }
        .note-body img { max-width: 100%; border-radius: .5rem; margin: .35rem 0; }
        .note-photo { max-height: 240px; border-radius: .6rem; }
        /* Height and look come from the shared rules in app.css, so every
           rich-text box in the app is the same box. */
        /* The attachment buttons are one group, not four loose controls. */
        .note-attach-box { border: 1px dashed var(--color-gray-300, #d1d5db); border-radius: .75rem; padding: .65rem; }
        html.dark .note-attach-box { border-color: #2b3a1c; }
        @media (max-width: 640px) {
            .note-attach-box .btn { flex: 1 1 8.5rem; justify-content: center; }
        }

        /* Accordion: each note folds to its header; the chevron flags state. */
        .note-head { cursor: pointer; }
        .note-origin { margin-top: .2rem; display: inline-flex; }
        .note-fold { display: grid; grid-template-rows: 1fr; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
        .note-fold-inner { overflow: hidden; min-height: 0; }
        .note-card.is-collapsed .note-fold { grid-template-rows: 0fr; }
        .note-chevron { transition: transform .2s ease; color: #9ca3af; }
        .note-card:not(.is-collapsed) .note-chevron { transform: rotate(90deg); }
        @media (prefers-reduced-motion: reduce) { .note-fold, .note-chevron { transition: none; } }

        /* Toolbar: find a note, fold the lot, write a new one — one bar. */
        .note-bar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-bottom: .85rem; }
        .note-bar-acts { display: flex; align-items: center; gap: .5rem; flex: 0 0 auto; }
        .note-iconbtn { display: inline-flex; align-items: center; gap: .35rem; height: 2.75rem;
            padding: 0 .8rem; border: 1px solid var(--color-gray-200, #e5e7eb); border-radius: .8rem;
            background: var(--color-white, #fff); font-size: .8rem; font-weight: 700;
            color: var(--color-gray-600, #4b5563); cursor: pointer; white-space: nowrap;
            transition: background .25s ease, border-color .25s ease, color .25s ease; }
        .note-iconbtn:hover { background: #f3f8ec; border-color: #a8cc7e; color: #3d6823; }
        .note-iconbtn svg { width: 1rem; height: 1rem; }
        .note-newbtn { display: inline-flex; align-items: center; gap: .4rem; height: 2.75rem;
            padding: 0 1rem; border-radius: .8rem; background: #4a7c2a; color: #fff;
            font-size: .85rem; font-weight: 800; cursor: pointer; white-space: nowrap;
            box-shadow: 0 8px 18px -12px rgb(61 104 35 / .9);
            transition: background .25s ease; }
        .note-newbtn:hover { background: #3d6823; }
        .note-newbtn svg { width: 1.05rem; height: 1.05rem; }
        html.dark .note-iconbtn { background: #1c2416; border-color: #2b3a1c; color: #cdd8c0; }
        html.dark .note-iconbtn:hover { background: #24301a; border-color: #3d6823; }
        @media (max-width: 560px) {
            /* Search gets the whole first row; the two actions share the next,
               each half — thumbs, not needles. */
            .note-search { flex: 1 1 100%; }
            .note-bar-acts { flex: 1 1 100%; }
            .note-bar-acts > * { flex: 1 1 0; justify-content: center; }
        }
        @media (prefers-reduced-motion: reduce) { .note-iconbtn, .note-newbtn { transition: none; } }
        .note-search { position: relative; flex: 1 1 12rem; min-width: 0; }
        .note-search .form-input { padding-left: 2.1rem; padding-right: 2.1rem; }
        .note-search .ns-ico { position: absolute; left: .65rem; top: 50%; transform: translateY(-50%); width: 1rem; height: 1rem; color: #9ca3af; pointer-events: none; }
        .note-search .ns-clear { position: absolute; right: .4rem; top: 50%; transform: translateY(-50%); width: 1.6rem; height: 1.6rem; border-radius: 999px; color: #9ca3af; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; }
        .note-search .ns-clear:hover { background: var(--color-gray-100); color: #4b5563; }
        #noteFoldAll .nfa-ico { transition: transform .28s cubic-bezier(.22,1,.36,1); }
        #noteFoldAll.is-allfolded .nfa-ico { transform: rotate(-90deg); }
        .note-count { font-size: .78rem; color: var(--tl-text-muted, #6b7280); margin: -.35rem 0 .6rem; }
        /* Unlayered on purpose: `.hidden` from a utility layer loses to the
           card's own display, so a filtered-out note would stay on screen. */
        .note-card.is-filtered { display: none !important; }
        /* The note something else sent you to. */
        .note-card.is-asked { box-shadow: 0 0 0 3px #a8cc7e, 0 14px 30px -20px rgb(0 0 0 / .5); }
        .note-card { transition: box-shadow .28s cubic-bezier(.22,1,.36,1); }
        @media (prefers-reduced-motion: reduce) { .note-card { transition: none; } }
        @media (prefers-reduced-motion: reduce) { #noteFoldAll .nfa-ico { transition: none; } }

        /* Upload progress — a big video takes real seconds, and silence reads
           as a hang. Each file gets a row: percent while it travels, then
           "Processing" while the server compresses it. */
        .note-ups:empty { display: none; }
        .note-ups { display: grid; gap: .4rem; margin-bottom: .6rem; }
        .note-up { border: 1px solid var(--color-gray-200); border-radius: .6rem; padding: .45rem .6rem; }
        .note-up-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; font-size: .75rem; font-weight: 700; color: var(--tl-text-muted, #4b5563); }
        .note-up-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .note-up-pct { flex: 0 0 auto; font-variant-numeric: tabular-nums; }
        .note-up-bar { height: .3rem; border-radius: 999px; background: var(--color-gray-200); overflow: hidden; margin-top: .35rem; }
        .note-up-fill { height: 100%; width: 0; border-radius: 999px; background: var(--color-primary, #4a7c2a); transition: width .2s linear; }
        .note-up.is-error { border-color: #fecaca; background: #fef2f2; }
        .note-up.is-error .note-up-top { color: #b91c1c; }
        .note-up.is-error .note-up-fill { background: #ef4444; }
        @media (prefers-reduced-motion: reduce) { .note-up-fill { transition: none; } }

        .note-ico { flex: 0 0 auto; width: 1.6rem; height: 1.6rem; margin-top: .1rem; border-radius: .45rem;
            display: inline-flex; align-items: center; justify-content: center;
            background: #e4efd4; color: #3d6823; }
        .note-ico svg { width: 1rem; height: 1rem; }
        html.dark .note-ico { background: rgb(61 104 35 / .35); color: #a8cc7e; }
        /* However long the note is, the way to change or remove it stays where
           it was: the top-right corner of its own card. */
        .note-acts { position: sticky; top: .5rem; align-self: flex-start; z-index: 1; }

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
@include('sm.partials.module-note', [
    'say' => 'A note is words first. A drawing or a map attached here shows as a tag — the picture itself lives in Drawings or Maps, and opens there.',
    'sayLink' => ['label' => 'See every note, from every schedule', 'href' => route('notes.hub')],
])

{{-- One bar: find a note, fold the lot, write a new one. Three separate
     blocks of chrome — a full-width slab, a bare input and a loose button —
     took up the top third of the screen before a single note appeared. --}}
<div class="note-bar">
    <div class="note-search">
        <svg class="ns-ico" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        <input type="text" id="noteSearch" class="form-input" placeholder="Search notes…" autocomplete="off" aria-label="Search notes">
        <button type="button" id="noteSearchClear" class="ns-clear hidden" aria-label="Clear search">✕</button>
    </div>
    <div class="note-bar-acts" id="noteToolbar">
        <button type="button" id="noteFoldAll" class="note-iconbtn" aria-pressed="false" title="Fold every note">
            <svg class="nfa-ico" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            <span id="noteFoldAllLabel">Collapse all</span>
        </button>
        <button type="button" class="note-newbtn" data-note-add>
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>New note</span>
        </button>
    </div>
</div>
<p class="note-count hidden" id="noteCount" aria-live="polite"></p>

<div class="space-y-3" id="notesList" data-animate-list>
    @include('sm.partials.notes-rows', ['notes' => $notes, 'schedule' => $schedule, 'mapSaves' => $mapSaves])
</div>
@include('partials.list-pager', [
    'paginator' => $notes,
    'rowsUrl' => route('sm.notes', ['id' => $schedule->id]) . '&rows=1',
])

<div class="card p-6 text-center hidden" id="notesNoMatch">
    <p class="font-semibold text-gray-700">No note matches that</p>
    <p class="text-sm text-gray-500 mt-1">Try a shorter word — the search looks at titles and note text.</p>
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
                </div>
            </div>
            <div class="note-quill mt-1.5">
                <div id="noteEditor"></div>
            </div>
        </div>
        <div class="mb-2 note-attach-box" data-video-host>
            <label class="form-label">Attachments</label>
            <div id="noteUploads" class="note-ups"></div>
            <div id="noteMthumbs" class="note-mthumbs mb-2"></div>
            <div class="flex flex-wrap gap-2">
                <input type="file" id="notePhoto" accept="image/*" class="hidden" multiple>
                {{-- Two ways in, because they are two different moments: one is
                     "I took this earlier", the other is "look at this now". The
                     camera one asks the phone for its rear camera directly. --}}
                <input type="file" id="noteCamera" accept="image/*" capture="environment" class="hidden">
                <button type="button" id="noteTakePhoto" class="btn btn-white btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Take a photo
                </button>
                <button type="button" id="noteAddPhoto" class="btn btn-white btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Upload photo
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
            <p class="form-hint">Photos and videos are attached to the note, not placed inside the text — they are compressed on the way up, and there is no limit on how many. Drawings are their own thing: make them in the Draw module and they arrive here as an attachment.</p>
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
                    'url' => \App\Support\MediaStore::url($m['path']),
                    'poster' => $m['poster'] ?? null,
                    'posterUrl' => ! empty($m['poster']) ? \App\Support\MediaStore::url($m['poster']) : null,
                    'mapUrl' => $isMap ? $mapModuleUrl : null,
                ];
            })->filter()->values()->all();
        };
        $mapNoteIds = ($mapSaves ?? collect())->pluck('noteId')->filter()->flip();
        $fromBoardIds = $notes->filter(fn ($n) => collect(is_array($n->media) ? $n->media : [])
            ->contains(fn ($m) => (bool) preg_match('~/board-[A-Za-z0-9]+\.png$~', (string) ($m['path'] ?? ''))))
            ->pluck('id')->flip();
        $seed = $notes->mapWithKeys(fn ($n) => [$n->id => [
            'fromMap' => $mapNoteIds->has($n->id),
            'fromDraw' => $fromBoardIds->has($n->id),
            'id' => $n->id, 'title' => $n->title, 'body' => $n->body,
            'imagePath' => $n->imagePath,
            'imageUrl' => $n->imagePath ? \App\Support\MediaStore::url($n->imagePath) : null,
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
                modules: { toolbar: window.SM_RICH_TOOLBAR },
            });
            window.smQuillTouch?.(quill);
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
        fld('noteUploads').innerHTML = '';
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
    // Where a drawing goes when its chip is tapped; the index is appended.
    const DRAW_URL = @json(route('sm.draw', ['id' => $schedule->id]));
    window.NOTE_DRAW_URL = DRAW_URL;

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
    fld('noteTakePhoto')?.addEventListener('click', () => fld('noteCamera').click());
    // Remove a media item from the working gallery.
    fld('noteMthumbs').addEventListener('click', (e) => {
        const rm = e.target.closest('[data-rm]');
        if (!rm) return;
        media.splice(parseInt(rm.getAttribute('data-rm'), 10), 1);
        renderMthumbs();
    });

    /* ---- Accordion fold state (per note, remembered across visits) ----
     * Two things are remembered: which notes you folded, and whether you last
     * said "collapse all" — so a note written after that arrives folded like
     * the rest instead of being the one card standing open. */
    const FOLD_KEY = 'noteFold:' + SCHEDULE_ID;
    const FOLD_ALL_KEY = 'noteFoldAll:' + SCHEDULE_ID;
    let collapsedIds = (() => { try { return new Set(JSON.parse(localStorage.getItem(FOLD_KEY) || '[]')); } catch (_) { return new Set(); } })();
    let foldNewOnes = localStorage.getItem(FOLD_ALL_KEY) === '1';
    function persistFold() {
        try {
            localStorage.setItem(FOLD_KEY, JSON.stringify([...collapsedIds]));
            localStorage.setItem(FOLD_ALL_KEY, foldNewOnes ? '1' : '0');
        } catch (_) { /* private mode: the page still works, it just forgets */ }
    }
    function cards() { return [...list.querySelectorAll('.note-card')]; }

    /* A note asked for by name — ?open=<id> — from a drawing or a saved map
       that wants to show the words explaining why it exists. Unfold it, put
       it on screen and mark it for a second, so it is obvious which of forty
       cards was meant. */
    function openAsked() {
        const want = new URLSearchParams(location.search).get('open');
        if (!want) return;
        const card = list.querySelector(`.note-card[data-id="${CSS.escape(want)}"]`);
        if (!card) return;
        card.classList.remove('is-collapsed');
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        card.classList.add('is-asked');
        setTimeout(() => card.classList.remove('is-asked'), 2600);
    }
    function applyFold() {
        cards().forEach((c) => {
            const id = String(c.dataset.id);
            if (foldNewOnes && !collapsedIds.has(id) && !c.dataset.foldSeen) collapsedIds.add(id);
            c.dataset.foldSeen = '1';
            c.classList.toggle('is-collapsed', collapsedIds.has(id));
        });
        syncFoldButton();
    }
    /** The button offers whichever action there is room for. */
    function syncFoldButton() {
        const btn = fld('noteFoldAll');
        if (!btn) return;
        const visible = cards().filter((c) => !c.classList.contains('is-filtered'));
        const anyOpen = visible.some((c) => !c.classList.contains('is-collapsed'));
        btn.classList.toggle('is-allfolded', !anyOpen);
        btn.setAttribute('aria-pressed', anyOpen ? 'false' : 'true');
        fld('noteFoldAllLabel').textContent = anyOpen ? 'Collapse all' : 'Expand all';
    }
    fld('noteFoldAll')?.addEventListener('click', () => {
        const visible = cards().filter((c) => !c.classList.contains('is-filtered'));
        const collapse = visible.some((c) => !c.classList.contains('is-collapsed'));
        visible.forEach((c) => {
            const id = String(c.dataset.id);
            c.classList.toggle('is-collapsed', collapse);
            if (collapse) collapsedIds.add(id); else collapsedIds.delete(id);
        });
        // Only a fold-all over the whole list decides how new notes arrive; a
        // fold-all while searching is about those results, not everything.
        if (!query) foldNewOnes = collapse;
        persistFold();
        syncFoldButton();
    });

    /* ---- Search ------------------------------------------------------------
     * Title and body, in the browser — the whole notebook is already on the
     * page, so there is nothing to wait for. A match opens while you search
     * and folds back to how you left it when the box is cleared. */
    let query = '';
    function noteText(card) {
        if (!card.dataset.searchText) {
            const t = card.querySelector('.js-title')?.textContent || '';
            const b = card.querySelector('.note-body')?.textContent || '';
            card.dataset.searchText = (t + ' ' + b).toLowerCase().replace(/\s+/g, ' ');
        }
        return card.dataset.searchText;
    }
    function runSearch() {
        const all = cards();
        let shown = 0;
        all.forEach((c) => {
            const hit = !query || noteText(c).includes(query);
            c.classList.toggle('is-filtered', !hit);
            if (hit) shown++;
            // While searching, a hit shows its text; clearing puts the fold
            // back where you left it.
            if (query) c.classList.toggle('is-collapsed', false);
            else c.classList.toggle('is-collapsed', collapsedIds.has(String(c.dataset.id)));
        });
        const count = fld('noteCount');
        count.textContent = query ? (shown + ' of ' + all.length + ' notes') : '';
        count.classList.toggle('hidden', !query);
        fld('notesNoMatch').classList.toggle('hidden', !(query && shown === 0));
        fld('noteSearchClear').classList.toggle('hidden', !query);
        syncFoldButton();
    }
    fld('noteSearch')?.addEventListener('input', (e) => {
        query = e.target.value.trim().toLowerCase();
        runSearch();
    });
    fld('noteSearchClear')?.addEventListener('click', () => {
        fld('noteSearch').value = ''; query = ''; runSearch(); fld('noteSearch').focus();
    });
    function refreshToolbar() {
        // Nothing to search or fold when there are no notes; the New note
        // button lives in the empty state instead.
        const none = cards().length === 0;
        fld('noteToolbar').classList.toggle('hidden', none);
        document.querySelector('.note-search')?.classList.toggle('hidden', none);
    }
    applyFold();

    /* ---- Uploading, out loud ----------------------------------------------
     * A phone video is tens of megabytes and takes real seconds to travel;
     * fetch() cannot say how far it has got, so this uses XHR and shows the
     * percentage. Once the bytes have all arrived the server still has to
     * compress them, and that wait gets its own word rather than a bar
     * sitting at 100% looking stuck. */
    function fmtSize(bytes) {
        if (!bytes) return '';
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }
    function uploadWithProgress(url, field, file) {
        const row = document.createElement('div');
        row.className = 'note-up';
        row.innerHTML = `
            <div class="note-up-top">
                <span class="note-up-name"></span>
                <span class="note-up-pct">0%</span>
            </div>
            <div class="note-up-bar"><div class="note-up-fill"></div></div>`;
        row.querySelector('.note-up-name').textContent = (file.name || 'File') + (file.size ? ' · ' + fmtSize(file.size) : '');
        fld('noteUploads').appendChild(row);
        const pct = row.querySelector('.note-up-pct');
        const fill = row.querySelector('.note-up-fill');

        return new Promise((resolve, reject) => {
            const form = new FormData();
            form.append(field, file);
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
            xhr.upload.addEventListener('load', () => {
                pct.textContent = 'Processing…';
                fill.style.width = '100%';
            });
            const fail = (msg) => {
                row.classList.add('is-error');
                pct.textContent = 'Failed';
                setTimeout(() => row.remove(), 4000);
                reject(new Error(msg));
            };
            xhr.addEventListener('load', () => {
                let json = null;
                try { json = JSON.parse(xhr.responseText); } catch (_) { /* below */ }
                if (!json || !json.success) return fail((json && json.message) || 'Upload failed.');
                pct.textContent = 'Done';
                setTimeout(() => row.remove(), 700);
                resolve(json);
            });
            xhr.addEventListener('error', () => fail('Upload failed — check your connection.'));
            xhr.addEventListener('abort', () => fail('Upload cancelled.'));
            xhr.send(form);
        });
    }

    // Draw → upload the sketch → add it as an attachment (not inline).
    // Add photo(s) — each is compressed server-side and pushed to the gallery.
    // Both inputs land here: one file or several, taken or picked.
    fld('noteCamera')?.addEventListener('change', (e) => onPhotosPicked(e));
    fld('notePhoto').addEventListener('change', (e) => onPhotosPicked(e));
    async function onPhotosPicked(e) {
        const files = Array.from(e.target.files || []); if (!files.length) return;
        for (const file of files) {
            try {
                const json = await uploadWithProgress(URLS.upload, 'image', file);
                media.push({ type: 'image', path: json.data.path, url: json.data.url });
                renderMthumbs();
            } catch (err) { toast(err.message, 'error'); }
        }
        e.target.value = '';
    }

    // Add / record a video — the shared video partial writes the picked or
    // recorded file into .js-video-file; we compress + attach it here.
    const noteVideoInput = document.querySelector('#noteSheet .js-video-file');
    noteVideoInput?.addEventListener('change', async () => {
        const file = noteVideoInput.files && noteVideoInput.files[0]; if (!file) return;
        try {
            const json = await uploadWithProgress(URLS.videoUpload, 'video', file);
            media.push({ type: 'video', path: json.data.path, poster: json.data.poster, url: json.data.url, posterUrl: json.data.posterUrl });
            renderMthumbs();
            toast('Video attached.');
        } catch (err) { toast(err.message, 'error'); }
        noteVideoInput.value = '';
        const host = document.querySelector('#noteSheet [data-video-host]');
        if (host && window.plazaClearVideo) window.plazaClearVideo(host);
    });

    /** Everything this note carries, with a drawing knowing where it lives.
        Map chips get this page's front-door link when the item brings none —
        the chip builder itself no longer falls back to page globals. */
    function attachmentsOf(n) {
        const items = [];
        if (n.imageUrl) items.push({ type: 'image', url: n.imageUrl });
        (n.media || []).forEach((m, i) => items.push(Object.assign({}, m, {
            drawUrl: m.type === 'drawing' ? DRAW_URL + '&open=' + n.id + ':' + i : null,
            mapUrl: m.mapUrl || ((m.type === 'map' || /\/map-[A-Za-z0-9]+\.png$/.test(m.path || m.url || '')) ? window.NOTE_MAP_URL : null),
        })));
        return items;
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
                    <span class="note-ico" aria-hidden="true"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                    <div class="min-w-0 grow">
                        <h3 class="font-bold text-gray-900 leading-snug js-title">${escapeHtml(n.title)}</h3>
                        ${n.fromMap ? '<span class="badge badge-green note-origin">Team map</span>' : ''}
                        ${n.fromDraw ? '<span class="badge badge-blue note-origin">Team drawing</span>' : ''}
                        <p class="text-xs text-gray-400 mt-0.5 js-time">just now</p>
                    </div>
                </div>
                <div class="flex gap-1 shrink-0 note-acts">
                    <button type="button" class="btn btn-sm btn-ghost js-edit" aria-label="Edit note"><svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                    <button type="button" class="btn btn-sm btn-ghost text-red-600 js-delete" aria-label="Delete note"><svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                </div>
            </div>
            <div class="note-fold"><div class="note-fold-inner js-note-body-wrap">
                ${n.body ? `<div class="note-body mt-2">${n.body}</div>` : ''}
                ${window.noteAttachmentChips ? window.noteAttachmentChips(attachmentsOf(n)) : ''}
            </div></div>`;
        return el;
    }

    function refreshEmpty() {
        emptyEl.classList.toggle('hidden', list.querySelectorAll('.note-card').length > 0);
        refreshToolbar();
    }
    refreshToolbar();

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
            // A rewritten card is new to the fold store and to the search
            // index; both are rebuilt from what is on screen now.
            applyFold(); runSearch();
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
            syncFoldButton();
            return;
        }
        if (e.target.closest('.js-delete')) {
            const name = NOTES[id] ? NOTES[id].title : 'this note';
            const ok = await confirmAction({ title: 'Delete note?', message: '"' + name + '" will be removed.', confirmText: 'Delete' });
            if (!ok) return;
            try {
                const res = await api(URLS.destroy(id), { method: 'DELETE' });
                delete NOTES[id];
                const finish = () => { card.remove(); refreshEmpty(); runSearch(); };
                if (window.animateOut) window.animateOut(card, finish); else finish();
                toast(res.message);
            } catch (err) { toast(err.message, 'error'); }
        }
    });

    // Last, once the list is real: a drawing or a map may have sent us here
    // to look at one particular note.
    openAsked();
};
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', __init, { once: true });
    else __init();
})();
</script>
@endpush
