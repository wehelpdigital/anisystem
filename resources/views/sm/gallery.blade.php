@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Gallery — ' . $schedule->title)
@section('page-title', 'Gallery')
@section('page-subtitle', $schedule->title)
@section('help-key', 'gallery')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
@include('sm.partials.gallery-chrome-css')
@endpush

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'gallery'])
@include('sm.partials.module-note', [
    'say' => 'Everything the season produced, wherever it was taken — photos and clips from notes, days, drawings, maps and the AI — plus the albums you put together yourself. Nothing here is a copy: delete a picture where it lives and it leaves here too, and an album picture can be deleted from here because here is where it lives.',
])


{{-- One button, not a strip that scrolls.

     Four shelves never fitted across a phone, so the strip scrolled — and a
     tab you have to discover by dragging is a tab most people never learn
     is there. This says which shelf you are on and opens the rest, the same
     way the schedule list asks about its order. --}}
<div class="ga-shelfbar">
    {{-- The same button as Modules and Tools in the shell's toolbar. It does
         the same job they do — a hamburger that opens a list of places to
         go — so there is no reason for it to be its own invention. --}}
    <button type="button" id="gaTabBtn" class="btn btn-white btn-sm" aria-haspopup="dialog" title="Which shelf?">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <span id="gaTabNow">All Media</span>
        <span class="ga-n" id="gaTabNowN">{{ $counts['all'] }}</span>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
    </button>
</div>

{{-- The shelves, asked for once. --}}
<div class="ga-modal hidden" id="gaTabModal" role="dialog" aria-modal="true" aria-label="Which shelf?">
    <div class="ga-modal-back" data-ga-close></div>
    <div class="ga-modal-card">
        <div class="ga-modal-head">
            <p class="font-bold text-gray-900">Which shelf?</p>
            <button type="button" class="btn-ghost rounded-full w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700" data-ga-close aria-label="Close">✕</button>
        </div>
        <div class="ga-modal-body" role="tablist">
            @php
                $shelves = [
                    ['all', 'All Media', $counts['all'], 'Everything the season produced, wherever it was taken.'],
                    ['albums', 'Albums', count($albums), 'The ones you put together on purpose.'],
                    ['videos', 'Videos', $counts['videos'], 'Clips on their own, because you pick a video and scan photos.'],
                    ['team', 'Team box', $counts['team'], 'What the Collab Room made: recordings, whiteboards, saved maps.'],
                ];
            @endphp
            @foreach ($shelves as [$key, $label, $n, $why])
                @if ($key !== 'videos' || $counts['videos'])
                    <button type="button" class="ga-opt{{ $key === 'all' ? ' is-on' : '' }}" data-tab="{{ $key }}" role="tab" aria-selected="{{ $key === 'all' ? 'true' : 'false' }}">
                        <span class="ga-opt-txt">
                            <b>{{ $label }} <span class="ga-n">{{ $n }}</span></b>
                            <i>{{ $why }}</i>
                        </span>
                        <span class="ga-opt-tick" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    </button>
                @endif
            @endforeach
        </div>
    </div>
</div>

{{-- ============================ everything ============================ --}}
<div class="ga-pane" data-pane="all">
    <div class="ga-tools">
        <label class="ga-search">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            <input type="search" id="gaFind" placeholder="Search by what it was about…" autocomplete="off">
        </label>
        <div class="ga-filters" id="gaFilters">
            <button type="button" class="ga-filter is-on" data-src="">Everything</button>
            <button type="button" class="ga-filter" data-src="Note">Notes</button>
            <button type="button" class="ga-filter" data-src="Day note">Day notes</button>
            <button type="button" class="ga-filter" data-src="Drawing">Drawings</button>
            <button type="button" class="ga-filter" data-src="Map">Maps</button>
            <button type="button" class="ga-filter" data-src="Album">Albums</button>
            <button type="button" class="ga-filter" data-src="Activity">Activities</button>
        </div>
    </div>
    <div class="ga-all" id="gaAll"></div>
    <p class="ga-none hidden" id="gaAllNone">Nothing here yet. Anything taken anywhere in this schedule — a note, a day, a drawing, a map — arrives here on its own.</p>
</div>

{{-- ============================== albums ============================== --}}
<div class="ga-pane" data-pane="albums" hidden>
    <div class="ga-top">
        <button type="button" class="ga-new" id="gaNewAlbum">
            <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>New album</span>
        </button>
        <p class="text-xs text-gray-500">An album is what you chose to keep together — a corner of the field, a problem you are following, the pictures a buyer asked for.</p>
    </div>

    <div id="gaAlbums"></div>

    <div class="card p-8 text-center hidden" id="gaEmpty">
        <div class="text-4xl mb-2">🖼️</div>
        <p class="font-bold text-gray-900">No albums yet</p>
        <p class="text-sm text-gray-500 mt-1">Make one for a corner of the field, a problem you are following, or anything worth keeping together.</p>
    </div>
</div>

{{-- ============================== videos ============================== --}}
<div class="ga-pane" data-pane="videos" hidden>
    {{-- The same line the other shelves get. A bare grid with no word above
         it reads as a page that has not finished loading. --}}
    <p class="tb-say">Every clip this season produced, wherever it was taken — a note, a day, the Collab Room, or Quick Record. Tap one to watch it.</p>
    <div class="ga-all" id="gaVideos"></div>
</div>

{{-- ============================= team box ============================= --}}
<div class="ga-pane" data-pane="team" hidden>
    <p class="tb-say">Everything the Collab Room made: recordings from a shared camera or a call, the whiteboard drawings, and the maps the team saved. Kept for the schedule, so anyone on it can find them again.</p>

    <div class="ga-filters tb-filters" id="tbFilters">
        <button type="button" class="ga-filter is-on" data-tb="">Everything</button>
        <button type="button" class="ga-filter" data-tb="Recording">Recordings</button>
        <button type="button" class="ga-filter" data-tb="Drawing">Drawings</button>
        <button type="button" class="ga-filter" data-tb="Map">Maps</button>
    </div>

    <div class="tb-grid" id="tbGrid"></div>
    <p class="ga-none hidden" id="tbNone">The Collab Room has not made anything yet. Recordings, whiteboard drawings and saved maps all land here.</p>
</div>

{{-- The picking bar, shown once a picture is chosen. --}}
<div class="ga-bar" id="gaBar" hidden>
    <span class="ga-bar-n" id="gaBarN">0 picked</span>
    <button type="button" id="gaMove">Move to…</button>
    <button type="button" class="ga-bar-del" id="gaDelete">Delete</button>
    <button type="button" id="gaClear">Cancel</button>
</div>

@if (! request()->boolean('partial'))
    @include('sm.partials.note-lightbox')
@endif
@include('sm.partials.clip-frames-js')
@endsection

@push('sheets')
<div class="sheet hidden" id="gaAlbumSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="gaAlbumSheetTitle">New album</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <input type="hidden" id="gaAlbumId">
        <label class="form-label" for="gaAlbumTitle">Title <span class="text-red-500">*</span></label>
        <input type="text" id="gaAlbumTitle" class="form-input" maxlength="191" placeholder="e.g. Flooded corner — August">
        <label class="form-label mt-3" for="gaAlbumDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
        <textarea id="gaAlbumDesc" class="form-input" rows="3" maxlength="2000" placeholder="What these pictures are about."></textarea>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="gaAlbumSave">Save album</button>
    </div>
</div>

<div class="sheet hidden" id="gaMoveSheet" style="--sheet-width:26rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Move to which album?</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body" id="gaMoveList" style="padding-bottom:1rem"></div>
</div>

{{-- The name a picture was given, editable at last — opened from the pencil
     the Gallery hangs on the shared lightbox. Both fields arrive prefilled,
     and both are sent back whatever they hold: an emptied field means "take
     the name off", which the endpoint honours on purpose. --}}
<div class="sheet hidden" id="gaNameSheet" style="--sheet-width:28rem">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title">Rename this picture</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body">
        <input type="hidden" id="gaNameId">
        <label class="form-label" for="gaNameCaption">Name</label>
        <input type="text" id="gaNameCaption" class="form-input" maxlength="255" placeholder="What this one is called">
        <label class="form-label mt-3" for="gaNameDesc">Description <span class="text-gray-400 font-normal">(optional)</span></label>
        <textarea id="gaNameDesc" class="form-input" rows="3" maxlength="2000" placeholder="What it is about."></textarea>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn btn-primary" id="gaNameSave">Save name</button>
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
    const init = () => {
        const SCHEDULE_ID = @json($schedule->id);
        const EVERYTHING = @json($everything);
        const U = {
            album: @json(route('sm.gallery.album.save')) + '?scheduleId=' + SCHEDULE_ID,
            albumDel: @json(route('sm.gallery.album.destroy')) + '?scheduleId=' + SCHEDULE_ID,
            upload: @json(route('sm.gallery.image.store')) + '?scheduleId=' + SCHEDULE_ID,
            move: @json(route('sm.gallery.image.move')) + '?scheduleId=' + SCHEDULE_ID,
            rename: @json(route('sm.gallery.image.rename')) + '?scheduleId=' + SCHEDULE_ID,
            del: @json(route('sm.gallery.image.destroy')) + '?scheduleId=' + SCHEDULE_ID,
        };
        const CSRF = document.querySelector('meta[name=csrf-token]').content;
        let ALBUMS = @json($albums);
        const picked = new Set();
        const $ = (id) => document.getElementById(id);

        // Resolved per call, not captured once: window.escapeHtml arrives with
        // app.js as a deferred module, so on a direct load this runs first and
        // a snapshot would be the fallback for the life of the page. And the
        // fallback escapes for real — captions and album titles are typed by
        // workers, and these go inside quoted attributes, where a stray " is
        // not a broken caption but a way out of the attribute.
        const esc = (s) => (typeof window.escapeHtml === 'function'
            ? window.escapeHtml(s)
            : String(s ?? '')
                .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;').replaceAll("'", '&#039;'));

        function albumHtml(a) {
            const cells = a.images.map((im) => {
                const name = im.caption || '';
                const about = im.description || '';
                // A clip in an album is a clip. Putting one in an <img> is how
                // a perfectly good recording came to report itself missing —
                // and a <video> has no alt, so its name has to be said another
                // way or the tile is announced as nothing at all.
                const shot = im.kind === 'video'
                    ? `${im.posterUrl
                          ? `<img src="${esc(im.posterUrl)}" alt="${esc(name || 'Clip in this album')}" loading="lazy"
                              onload="this.classList.add('is-loaded')"
                              onerror="this.closest('.ga-cell')?.classList.add('is-gone'); this.remove();">`
                          : `<video src="${esc(im.url)}#t=0.1" preload="metadata" playsinline muted
                              aria-label="${esc(name || 'Clip in this album')}"
                              onloadeddata="this.classList.add('is-loaded')"
                              onerror="this.closest('.ga-cell')?.classList.add('is-gone'); this.remove();"></video>`}
                       <span class="ga-vid" aria-hidden="true">▶</span>`
                    : `<img src="${esc(im.url)}" alt="${esc(name)}" loading="lazy"
                        onload="this.classList.add('is-loaded')"
                        onerror="this.closest('.ga-cell')?.classList.add('is-gone'); this.remove();">`;
                // The tile says what it is called; the full text is the tooltip
                // for the descriptions a 7rem square cannot hold.
                const label = (name || about)
                    ? `<span class="ga-cap">${name ? `<b>${esc(name)}</b>` : ''}${about ? `<i>${esc(about)}</i>` : ''}</span>`
                    : '';
                const tip = [name, about].filter(Boolean).join(' — ');
                return `
                <div class="ga-cell" data-image="${im.id}" data-lb-type="${im.kind === 'video' ? 'video' : 'image'}" data-lb-url="${esc(im.url)}"${(im.kind === 'video' && !im.posterUrl && im.path) ? ` data-needs-frame="${esc(im.path)}" data-clip-url="${esc(im.url)}" data-frame-replace="video"` : ''}
                     data-lb-image="${im.id}" data-lb-caption="${esc(name)}" data-lb-desc="${esc(about)}"${tip ? ` title="${esc(tip)}"` : ''}>
                    ${shot}
                    ${im.team ? '<span class="ga-teamchip" title="Drawn together in the Collab Room">Team</span>' : ''}
                    <span class="ga-pick" data-pick><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                    ${label}
                </div>`;
            }).join('');
            return `<div class="ga-album" data-album="${a.id}">
                <div class="ga-head">
                    <span class="min-w-0 grow">
                        <span class="ga-title block">${esc(a.title)}</span>
                        ${a.description ? `<span class="ga-desc block">${esc(a.description)}</span>` : ''}
                        <span class="ga-count mt-1 inline-block">${a.images.length} ${a.images.length === 1 ? 'picture' : 'pictures'}</span>
                    </span>
                    <span class="ga-acts">
                        <button type="button" class="ga-act" data-add title="Add pictures" aria-label="Add pictures">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        </button>
                        <button type="button" class="ga-act" data-edit title="Rename" aria-label="Rename album">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button type="button" class="ga-act is-danger" data-del title="Delete album" aria-label="Delete album">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                        </button>
                    </span>
                </div>
                ${a.images.length
                    ? `<div class="ga-grid">${cells}</div>`
                    : '<p class="ga-empty">Nothing in here yet — tap + to add pictures.</p>'}
            </div>`;
        }

        function paint() {
            // The bar may be docked inside an album card, and the next line
            // rebuilds every card from a string - anything standing inside is
            // destroyed, not moved. Lift it out first; paintBar docks it back.
            const liveBar = $('gaBar');
            if (liveBar && liveBar.parentElement !== document.body) document.body.appendChild(liveBar);
            // Through the same windowed fill() the shelves use: a season of
            // albums arrives 24 sections at a time instead of as one document.
            // (fill() empties the host itself, after the bar rescue above.)
            fill($('gaAlbums'), ALBUMS, '', albumHtml);
            $('gaEmpty').classList.toggle('hidden', ALBUMS.length > 0);
            paintBar();
        }

        let barHome = null;   // data-album of the album the bar docks in
        function paintBar() {
            const n = picked.size;
            const bar = $('gaBar');
            bar.hidden = n === 0;
            $('gaBarN').textContent = n + ' picked';
            if (n) {
                // Docked inside the album of the most recent pick, right after
                // its header. paint() rebuilds the grid with innerHTML, which
                // would eat the bar - so it lives outside the grid and is
                // APPENDED here on every paint, never serialized.
                const home = (barHome && document.querySelector('.ga-album[data-album="' + barHome + '"]'))
                    || document.querySelector('.ga-album');
                const head = home && home.querySelector('.ga-head');
                if (head && head.nextElementSibling !== bar) head.insertAdjacentElement('afterend', bar);
            }
            document.querySelectorAll('.ga-cell').forEach((c) => {
                c.classList.toggle('is-picked', picked.has(c.getAttribute('data-image')));
            });
        }

        /* ---- albums ---- */
        function openAlbumSheet(album) {
            $('gaAlbumId').value = album ? album.id : '';
            $('gaAlbumSheetTitle').textContent = album ? 'Rename album' : 'New album';
            $('gaAlbumTitle').value = album ? album.title : '';
            $('gaAlbumDesc').value = album ? (album.description || '') : '';
            openSheet('gaAlbumSheet');
            window.smFocus($('gaAlbumTitle'), { delay: 150 });
        }
        $('gaNewAlbum').addEventListener('click', () => openAlbumSheet(null));

        $('gaAlbumSave').addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            const title = $('gaAlbumTitle').value.trim();
            if (!title) { toast('Give the album a title.', 'error'); return; }
            btn.disabled = true;
            try {
                const res = await api(U.album, { method: 'POST', body: {
                    id: $('gaAlbumId').value || null, title, description: $('gaAlbumDesc').value.trim() || null,
                } });
                const row = res.data;
                const at = ALBUMS.findIndex((a) => String(a.id) === String(row.id));
                if (at >= 0) { ALBUMS[at].title = row.title; ALBUMS[at].description = row.description; }
                else ALBUMS.unshift({ id: row.id, title: row.title, description: row.description, images: [] });
                paint();
                closeSheet('gaAlbumSheet');
                toast(res.message);
            } catch (err) { toast(err.message || 'Could not save that.', 'error'); }
            finally { btn.disabled = false; }
        });

        async function deleteAlbum(album) {
            const has = album.images.length;
            const others = ALBUMS.filter((a) => a.id !== album.id);
            // An album full of pictures is not something to remove on one tap.
            const ok = await confirmAction({
                title: 'Delete “' + album.title + '”?',
                message: has
                    ? has + ' ' + (has === 1 ? 'picture is' : 'pictures are') + ' in it. They are deleted with it — move them to another album first if you want to keep them.'
                    : 'The album is empty.',
                confirmText: has ? 'Delete album and pictures' : 'Delete album',
            });
            if (!ok) return;
            try {
                const res = await api(U.albumDel, { method: 'DELETE', body: { id: album.id, withImages: has ? 1 : 0 } });
                ALBUMS = ALBUMS.filter((a) => a.id !== album.id);
                album.images.forEach((im) => picked.delete(String(im.id)));
                paint();
                toast(res.message);
            } catch (err) { toast(err.message || 'Could not remove that.', 'error'); }
        }

        /* ---- pictures ---- */
        const filePicker = document.createElement('input');
        filePicker.type = 'file';
        filePicker.accept = 'image/*';
        filePicker.multiple = true;
        filePicker.className = 'hidden';
        document.body.appendChild(filePicker);
        let uploadInto = null;

        filePicker.addEventListener('change', async () => {
            const files = [...(filePicker.files || [])];
            filePicker.value = '';
            if (!files.length || !uploadInto) return;
            const form = new FormData();
            form.append('albumId', uploadInto);
            files.forEach((f) => form.append('images[]', f));
            toast(files.length === 1 ? 'Adding the picture…' : ('Adding ' + files.length + ' pictures…'));
            try {
                const res = await fetch(U.upload, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
                    body: form, credentials: 'same-origin',
                });
                const json = await res.json();
                if (!json.success) throw new Error(json.message || 'Upload failed.');
                const album = ALBUMS.find((a) => String(a.id) === String(json.data.albumId));
                // Onto the end, in the order they were picked — which is where
                // the server just put them and where a reload will show them.
                // unshift() put them at the front and reversed a multi-file
                // upload among itself, so the page disagreed with itself the
                // moment it was refreshed.
                if (album) album.images.push(...json.data.images);
                paint();
                toast(json.message);
            } catch (err) { toast(err.message || 'Could not add those.', 'error'); }
        });

        document.addEventListener('click', async (e) => {
            const albumEl = e.target.closest('.ga-album');
            const album = albumEl ? ALBUMS.find((a) => String(a.id) === albumEl.getAttribute('data-album')) : null;

            if (e.target.closest('[data-add]') && album) { uploadInto = album.id; filePicker.click(); return; }
            if (e.target.closest('[data-edit]') && album) { openAlbumSheet(album); return; }
            if (e.target.closest('[data-del]') && album) { deleteAlbum(album); return; }

            // The tick starts picking; once picking, the whole cell toggles.
            const cell = e.target.closest('.ga-cell');
            if (!cell) return;
            const id = cell.getAttribute('data-image');
            if (e.target.closest('[data-pick]') || picked.size > 0) {
                e.preventDefault();
                e.stopPropagation();
                if (picked.has(id)) picked.delete(id); else picked.add(id);
                // The bar follows the hand: it docks in the album this pick
                // happened in. Picks in another album keep counting here too.
                if (albumEl) barHome = albumEl.getAttribute('data-album');
                paintBar();
            }
        }, true);

        $('gaClear').addEventListener('click', () => { picked.clear(); paintBar(); });

        $('gaDelete').addEventListener('click', async () => {
            const n = picked.size;
            const ok = await confirmAction({
                title: 'Delete ' + n + ' ' + (n === 1 ? 'picture' : 'pictures') + '?',
                message: 'They are removed from the album and from storage.',
                confirmText: 'Delete',
            });
            if (!ok) return;
            try {
                const ids = [...picked];
                const res = await api(U.del, { method: 'DELETE', body: { ids } });
                ALBUMS.forEach((a) => { a.images = a.images.filter((im) => !picked.has(String(im.id))); });
                picked.clear();
                paint();
                toast(res.message);
            } catch (err) { toast(err.message || 'Could not delete those.', 'error'); }
        });

        $('gaMove').addEventListener('click', () => {
            const list = $('gaMoveList');
            if (ALBUMS.length < 2) {
                toast('Make another album first — there is nowhere to move them to.', 'error');
                return;
            }
            list.innerHTML = ALBUMS.map((a) => `
                <button type="button" class="w-full flex items-center gap-3 rounded-xl px-3 py-3 text-left font-semibold text-gray-700 hover:bg-gray-50" data-move-to="${a.id}">
                    <span class="grow min-w-0">${esc(a.title)}</span>
                    <span class="ga-count">${a.images.length}</span>
                </button>`).join('');
            openSheet('gaMoveSheet');
        });

        $('gaMoveList').addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-move-to]');
            if (!btn) return;
            const albumId = btn.getAttribute('data-move-to');
            try {
                const ids = [...picked];
                const res = await api(U.move, { method: 'POST', body: { ids, albumId } });
                // Rebuild in place: the pictures leave one album and join another.
                const moving = [];
                ALBUMS.forEach((a) => {
                    a.images = a.images.filter((im) => {
                        if (!picked.has(String(im.id))) return true;
                        moving.push(im);
                        return false;
                    });
                });
                const target = ALBUMS.find((a) => String(a.id) === String(albumId));
                // At the tail, not the head. The server re-seeds them onto the
                // end of the target album; putting them at the front here meant
                // the page and the next refresh told two different stories
                // about where a picture had gone.
                if (target) target.images.push(...moving);
                picked.clear();
                paint();
                closeSheet('gaMoveSheet');
                toast(res.message);
            } catch (err) { toast(err.message || 'Could not move those.', 'error'); }
        });

        /* ================================================================
         * The "all pictures" shelf: read-only, and every tile knows the way
         * back to the record it came from. A drawing opens in the pad that
         * can change it; a map opens in Maps; a photo opens where it was
         * explained. Videos get their own tab because you pick a video and
         * scan photos, and mixing them makes both harder.
         * ============================================================= */
        const KIND_LABEL = { drawing: 'Drawing', map: 'Map', video: 'Video', image: '' };
        let findText = '';
        let findSource = '';

        // What the file name says, when the payload and the file disagree.
        // Belt and braces: an item typed 'image' whose path is an .mp4 is how
        // a perfectly good clip came to report itself missing, and the file
        // name is the one thing that cannot be wrong about that.
        // Keep in step with SeasonMedia::kindOf() — including AVI, which every
        // copy of this list used to leave out.
        const VIDEO_RE = /\.(mp4|mov|webm|mkv|m4v|3gp|avi)(\?|$)/i;

        function itemHtml(m) {
            const kind = (m.kind === 'image' && VIDEO_RE.test(m.url || '')) ? 'video' : (m.kind || 'image');
            const badge = KIND_LABEL[kind]
                ? `<span class="ga-kind is-${kind}">${KIND_LABEL[kind]}</span>` : '';
            // No poster — which is what a clip stored on a server without
            // ffmpeg looks like — so show the video's own first frame rather
            // than an empty black box.
            /* A bare clip asks for its frame while the page is open
             * (clip-frames-js): the tile wears a turning ring, the frame
             * lands as a picture, and the registry remembers it — the same
             * clip is never cut twice. The coaxed <video> stays underneath
             * for the moment before, and is replaced. */
            const wants = (kind === 'video' && !m.posterUrl && m.path)
                ? ` data-needs-frame="${esc(m.path)}" data-clip-url="${esc(m.url)}" data-frame-replace="video"` : '';
            const shot = kind === 'video'
                ? `<div class="ga-shot"${wants}>${m.posterUrl
                        ? `<img src="${esc(m.posterUrl)}" alt="" loading="lazy" onload="this.classList.add('is-loaded')"
                             onerror="this.closest('.ga-shot')?.classList.add('ga-noshot')">`
                        : `<video src="${esc(m.url)}#t=0.1" preload="metadata" playsinline muted
                             onloadeddata="this.classList.add('is-loaded')"
                             onerror="this.closest('.ga-shot')?.classList.add('is-gone'); this.remove();"></video>`}
                     <span class="ga-play"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>${badge}</div>`
                : `<div class="ga-shot"><img src="${esc(m.url)}" alt="" loading="lazy"
                        onload="this.classList.add('is-loaded')"
                        onerror="this.closest('.ga-shot')?.classList.add('is-gone'); this.remove();">${badge}</div>`;
            const inner = `${shot}<div class="ga-info">
                    <span class="ga-it">${esc(m.title)}</span>
                    <span class="ga-is">${esc(m.source)}${m.when ? ' · ' + esc(m.when) : ''}</span>
                </div>`;
            /* Only an album picture can be deleted from here, and only
               because the Gallery is where it lives. Everything else on this
               shelf is a view of something kept in a note, a drawing or a
               map — offering to delete one of those here would either lie
               about what it did or tear a hole in the record it belongs to.
               Those still say "delete it where it lives", which is what the
               note at the top of the page has always promised. */
            const bin = m.albumImageId
                ? `<button type="button" class="ga-del" data-del-image="${m.albumImageId}"
                        title="Delete this from the album" aria-label="Delete">
                        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                   </button>`
                : '';
            // Photos and videos open in the lightbox; drawings and maps open
            // where they can be worked on.
            const tile = (kind === 'drawing' || kind === 'map') && m.href
                ? `<a class="ga-item" href="${esc(m.href)}">${inner}</a>`
                // The same strip the Albums tab hangs on the lightbox reads
                // these. Without them one album picture said its name when
                // opened from Albums and nothing when opened from All — the
                // same picture, two answers.
                // data-lb-image only for an album's own row: it is what makes
                // the lightbox offer a rename, and renaming is only honest for
                // the one kind of tile whose name lives in the Gallery.
                : `<button type="button" class="ga-item" data-lb-type="${kind === 'video' ? 'video' : 'image'}"
                        data-lb-url="${esc(m.url)}" data-lb-poster="${esc(m.posterUrl || '')}"
                        ${m.albumImageId ? `data-lb-image="${m.albumImageId}" ` : ''}data-lb-caption="${esc(m.title || '')}"
                        data-lb-desc="${esc([m.source, m.when].filter(Boolean).join(' · '))}">${inner}</button>`;
            return bin ? `<div class="ga-wrap">${tile}${bin}</div>` : tile;
        }

        /* ---- Filling a shelf a screenful at a time -----------------------
         * A season can produce hundreds of pictures, and drawing them all at
         * once means hundreds of <img> and <video> elements racing to load
         * before anybody has scrolled past the first row. The list itself
         * stays in memory — it is only titles and urls, and searching it has
         * to see everything — but the tiles arrive a page at a time, and the
         * next page is asked for when the end of the last one comes into
         * view.
         *
         * An IntersectionObserver rather than a scroll handler: it fires
         * when the sentinel is actually visible, which is the question being
         * asked, and it does not run on every pixel of every scroll. */
        const PAGE = 24;
        const feeds = new Map();      // host id → { items, drawn, io }

        function fill(host, items, emptyHtml, render) {
            if (!host) return;
            const state = feeds.get(host.id) || {};
            state.io?.disconnect();

            host.innerHTML = '';
            state.items = items;
            state.drawn = 0;
            feeds.set(host.id, state);

            if (!items.length) {
                host.innerHTML = emptyHtml || '';
                return;
            }

            const more = () => {
                const next = state.items.slice(state.drawn, state.drawn + PAGE);
                if (!next.length) return false;
                state.drawn += next.length;
                // insertAdjacentHTML, not innerHTML +=: rebuilding the whole
                // shelf would drop every picture already decoded and start
                // them loading again.
                sentinel.insertAdjacentHTML('beforebegin', next.map(render || itemHtml).join(''));
                window.smClipFrames?.();   // the page that just arrived may hold bare clips
                return state.drawn < state.items.length;
            };

            const sentinel = document.createElement('div');
            sentinel.className = 'ga-more';
            sentinel.innerHTML = '<span class="ga-more-spin" aria-hidden="true"></span>';
            host.appendChild(sentinel);

            state.io = new IntersectionObserver((entries) => {
                if (!entries.some((e) => e.isIntersecting)) return;
                if (!more()) { state.io.disconnect(); sentinel.remove(); }
            }, { root: null, rootMargin: '600px 0px' });   // ask early, so it never stalls

            if (!more()) { sentinel.remove(); } else { state.io.observe(sentinel); }
            // Any clip drawn frameless asks for its frame now.
            window.smClipFrames?.();
        }

        function paintAll() {
            const q = findText.trim().toLowerCase();
            const shown = EVERYTHING.filter((m) => m.kind !== 'video')
                .filter((m) => !findSource || m.source === findSource)
                .filter((m) => !q || (m.title + ' ' + m.source).toLowerCase().includes(q));

            fill($('gaAll'), shown);
            $('gaAllNone').classList.toggle('hidden', shown.length > 0);
            if (shown.length === 0 && (q || findSource)) {
                $('gaAllNone').textContent = 'Nothing matches that.';
            }

            fill($('gaVideos'), EVERYTHING.filter((m) => m.kind === 'video'),
                '<p class="ga-none">No videos in this schedule yet.</p>');
        }

        /* ---- Which shelf ------------------------------------------------
         * One button says where you are; the sheet is the whole list. The
         * strip that used to sit here scrolled, and a tab you only find by
         * dragging is a tab most people never learn is there. */
        (function shelfPicker() {
            const modal = $('gaTabModal');
            const btn = $('gaTabBtn');
            if (!modal || !btn) return;

            /* The sheet is moved to the body once, on the way up.
             *
             * It is position: fixed, and a fixed element is only fixed to the
             * window while no ancestor has a transform — the shell animates
             * its panes with one, and for those 300ms the sheet would be
             * fixed to a pane instead, landing under the header and clipped
             * by it. Parked on the body it cannot be caught by anything. */
            if (modal.parentElement !== document.body) document.body.appendChild(modal);

            const open = () => {
                modal.classList.remove('hidden');
                void modal.offsetWidth;
                modal.classList.add('is-open');
                document.body.style.overflow = 'hidden';
                window.registerOverlay?.('gaTabs', close);
            };
            const close = () => {
                modal.classList.remove('is-open');
                document.body.style.overflow = '';
                setTimeout(() => modal.classList.add('hidden'), 260);
            };

            function show(want) {
                modal.querySelectorAll('.ga-opt').forEach((o) => {
                    const on = o.dataset.tab === want;
                    o.classList.toggle('is-on', on);
                    o.setAttribute('aria-selected', on ? 'true' : 'false');
                    if (on) {
                        // The button wears the shelf's own name and count, so
                        // it says where you are without being opened.
                        $('gaTabNow').textContent = o.querySelector('b')?.childNodes[0]?.textContent.trim() || '';
                        $('gaTabNowN').textContent = o.querySelector('b .ga-n')?.textContent || '';
                    }
                });
                document.querySelectorAll('.ga-pane').forEach((p) => {
                    const mine = p.getAttribute('data-pane') === want;
                    p.hidden = !mine;
                    p.classList.remove('is-in');
                    if (!mine) return;
                    // Restart the animation on every switch: without the
                    // reflow the class goes on an element that already has
                    // it and the browser skips the whole thing.
                    void p.offsetWidth;
                    p.classList.add('is-in');
                });
            }

            btn.addEventListener('click', open);
            modal.addEventListener('click', (e) => {
                if (e.target.closest('[data-ga-close]')) { close(); return; }
                const opt = e.target.closest('.ga-opt');
                if (!opt) return;
                show(opt.dataset.tab);
                close();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
            });

            // Anything that used to click a tab can still ask for one.
            window.gaShowShelf = show;
        })();

        /* ---- Team box ---------------------------------------------------
         * Three different things — a recording, a whiteboard page, a saved
         * map — drawn the same way because people are looking for them with
         * the same question. A recording plays where it sits; the other two
         * open the thing they came from, because a picture of a map is not
         * a map. */
        const TEAM = @json($teamBox);
        const SAVE_URL = @json(route('media.save'));
        let tbFilter = '';

        function teamCardHtml(r) {
            const shot = r.video
                // #t=0.1 so a clip with no poster still shows a frame rather
                // than a black rectangle.
                ? '<video src="' + esc(r.url) + (r.posterUrl ? '' : '#t=0.1') + '"'
                    + (r.posterUrl ? ' poster="' + esc(r.posterUrl) + '"' : '')
                    + ' preload="metadata" playsinline controls></video>'
                    + (r.posterUrl ? '' : '<span class="tb-play"><span>▶</span></span>')
                : '<img src="' + esc(r.url) + '" alt="" loading="lazy">';
            // A recording is a thing people want off the app and onto a
            // phone — to send to a supplier, to keep. The button re-serves it
            // through our own origin so the browser saves rather than opens.
            const save = '<a class="tb-save" title="Save to this device" aria-label="Save"'
                + ' href="' + esc(SAVE_URL + '?u=' + encodeURIComponent(r.url) + '&n=' + encodeURIComponent(r.title || 'recording'))
                + '" download onclick="event.stopPropagation()">'
                + '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v10m0 0l-3.5-3.5M12 14l3.5-3.5M5 19h14"/></svg></a>';
            // The save button only goes on a card that is NOT a link: an
            // anchor inside an anchor is invalid HTML, and the parser closes
            // the card early, spilling its title and date out beside it.
            const inner = '<span class="tb-shot"><span class="tb-kind">' + esc(r.kind) + '</span>' + shot
                + (r.href ? '' : save) + '</span>'
                + '<span class="tb-body">'
                + '<span class="tb-title">' + esc(r.title) + '</span>'
                + (r.note ? '<span class="tb-note">' + esc(r.note) + '</span>' : '')
                + '<span class="tb-meta">' + esc([r.by, r.when].filter(Boolean).join(' · ')) + '</span>'
                + '</span>';
            // A recording is watched here; a drawing or a map is a way back to
            // the thing itself, which is still editable there.
            return r.href
                ? '<a class="tb-card" href="' + esc(r.href) + '">' + inner + '</a>'
                : '<div class="tb-card">' + inner + '</div>';
        }

        function paintTeam() {
            const rows = TEAM.filter((r) => !tbFilter || r.kind === tbFilter);
            const grid = $('tbGrid');
            if (!grid) return;
            $('tbNone')?.classList.toggle('hidden', rows.length > 0);
            // Recordings are the heaviest things the app stores, so this
            // shelf wants filling by the screenful more than any other.
            fill(grid, rows, '', teamCardHtml);
        }

        $('tbFilters')?.addEventListener('click', (e) => {
            const b = e.target.closest('.ga-filter');
            if (!b) return;
            tbFilter = b.getAttribute('data-tb') || '';
            $('tbFilters').querySelectorAll('.ga-filter').forEach((x) => x.classList.toggle('is-on', x === b));
            paintTeam();
        });
        paintTeam();

        // A notification that says "saved to the Team box" should land on it.
        if (new URLSearchParams(location.search).get('tab') === 'team') {
            window.gaShowShelf?.('team');
        }

        /* Deleting from the shelf. It asks first, and it says what it is
           about to do — a picture is somebody's record of a day, and the file
           goes with the row. */
        document.addEventListener('click', async (e) => {
            const bin = e.target.closest('[data-del-image]');
            if (!bin) return;
            e.preventDefault();
            e.stopPropagation();
            const id = parseInt(bin.getAttribute('data-del-image'), 10);
            if (!id) return;

            const ok = window.confirmAction
                ? await window.confirmAction({
                    title: 'Delete this from the album?',
                    message: 'It will be removed from the Gallery and the file deleted. This cannot be undone.',
                    confirmText: 'Delete', danger: true,
                })
                : confirm('Delete this from the album?');
            if (!ok) return;

            const wrap = bin.closest('.ga-wrap');
            try {
                const res = await api(U.del, { method: 'DELETE', body: { ids: [id] } });
                // Gone from the page, and gone from the list the page redraws
                // from — otherwise a search would bring it back.
                const at = EVERYTHING.findIndex((m) => m.albumImageId === id);
                if (at >= 0) EVERYTHING.splice(at, 1);
                // The albums tab draws from its own list, which would still
                // be holding the picture when you switched to it.
                ALBUMS.forEach((a) => { a.images = a.images.filter((im) => Number(im.id) !== id); });
                if (wrap && window.animateOut) window.animateOut(wrap, () => wrap.remove());
                else wrap?.remove();
                toast(res.message || 'Deleted.');
            } catch (err) {
                toast(err.message || 'Could not delete that.', 'error');
            }
        });

        /* ---- The name, in the lightbox ----------------------------------
         * The shared lightbox is handed a type and a url and nothing else,
         * and it is included on half the app's pages — so the Gallery hangs
         * its own strip on it rather than teaching every module about
         * captions. The click that opens a picture also says what it is.
         */
        (function lightboxCaption() {
            const lb = document.getElementById('noteLightbox');
            // Inside the Activities shell the lightbox belongs to the host
            // page and outlives this module, so a second visit would hang a
            // second strip on it. One is enough, and it is still wired.
            if (!lb || lb.querySelector('.ga-lb-cap')) return;
            const bar = document.createElement('div');
            bar.className = 'ga-lb-cap';
            bar.hidden = true;
            lb.appendChild(bar);

            // Runs after the lightbox's own handler — that one is registered
            // while the page is still parsing, this one from the script stack
            // at the end — so by now the picture is already on the stage.
            document.addEventListener('click', (e) => {
                const cell = e.target.closest('[data-lb-url]');
                if (!cell) return;
                const name = cell.getAttribute('data-lb-caption') || '';
                const about = cell.getAttribute('data-lb-desc') || '';
                bar.innerHTML = (name ? `<b>${esc(name)}</b>` : '') + (about ? `<i>${esc(about)}</i>` : '');
                bar.hidden = !(name || about);
            });

            // The lightbox closes half a dozen ways — the backdrop, Escape,
            // another overlay taking over — so the strip watches for the
            // class going off instead of guessing which one happened.
            new MutationObserver(() => {
                if (!lb.classList.contains('is-open')) bar.hidden = true;
            }).observe(lb, { attributes: true, attributeFilter: ['class'] });
        })();

        /* ---- The rename pencil, in the lightbox -------------------------
         * The server's question is "does this row belong to the schedule";
         * the button's question is only "does this media carry a gallery
         * image id". The lightbox is shared by notes, activities and the
         * Gallery, and a note's photo has no name here to edit — so no id,
         * no button, the same way the caption strip stays hidden. Hung on
         * the lightbox for the same reason the strip is: the lightbox
         * belongs to the host page and knows nothing about captions.
         */
        (function lightboxRename() {
            const lb = document.getElementById('noteLightbox');
            const sheet = $('gaNameSheet');
            if (!lb || !sheet) return;

            let btn = lb.querySelector('.ga-lb-rename');
            if (!btn) {
                btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ga-lb-rename';
                btn.title = 'Edit the name';
                btn.setAttribute('aria-label', 'Edit the name');
                btn.hidden = true;
                // The same pencil the album header wears for its own rename.
                btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
                lb.appendChild(btn);

                // Deliberately stateless: a deep link throws this pane away
                // and refetches it, while the lightbox — and this button —
                // belong to the host page and live on. Everything the
                // handlers need is read off the DOM or reached through
                // window.gaRenameOpen, which every init re-points at its own
                // fresh ALBUMS.
                document.addEventListener('click', (e) => {
                    const cell = e.target.closest('[data-lb-url]');
                    if (!cell) return;
                    btn.hidden = !cell.getAttribute('data-lb-image');
                    btn.dataset.image = cell.getAttribute('data-lb-image') || '';
                    // Which subtitle the strip is wearing: an album cell
                    // shows the picture's own description, an All-shelf tile
                    // shows where it came from — only the former should
                    // change when the words do.
                    btn.dataset.live = cell.classList.contains('ga-cell') ? '1' : '';
                    btn.dataset.sub = cell.getAttribute('data-lb-desc') || '';
                });
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (btn.dataset.image) window.gaRenameOpen?.(btn.dataset.image);
                });
                // Programmatic opens (openNoteLightbox) never pass the click
                // listener above, so the last visit's pencil would linger on
                // media with no name to edit. The class going off is the one
                // signal every close path shares.
                new MutationObserver(() => {
                    if (!lb.classList.contains('is-open')) btn.hidden = true;
                }).observe(lb, { attributes: true, attributeFilter: ['class'] });
            }

            window.gaRenameOpen = (imageId) => {
                // From state, not from the opening click's attributes: a
                // second rename in the same viewing must prefill what the
                // first one just saved.
                let im = null;
                for (const a of ALBUMS) {
                    im = a.images.find((x) => String(x.id) === String(imageId));
                    if (im) break;
                }
                if (!im) { toast('That picture is no longer here.', 'error'); return; }
                $('gaNameId').value = im.id;
                $('gaNameCaption').value = im.caption || '';
                $('gaNameDesc').value = im.description || '';
                openSheet('gaNameSheet');
                window.smFocus($('gaNameCaption'), { delay: 150 });
            };

            // The scoped z bump (see the CSS above): on while the sheet is
            // up, off again on every way the sheet closes.
            sheet.addEventListener('sheet:open', () => document.documentElement.classList.add('ga-naming'));
            sheet.addEventListener('sheet:close', () => document.documentElement.classList.remove('ga-naming'));

            // The lightbox's own Escape handler cannot see the sheet standing
            // over it and would take the picture away with the form. Capture
            // phase runs before every bubble handler on document, so while
            // the sheet is up Escape peels only the top layer. (Duplicates
            // from a refetched pane are harmless: the first stops the rest.)
            document.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape') return;
                if (!document.documentElement.classList.contains('ga-naming')) return;
                e.stopImmediatePropagation();
                closeSheet('gaNameSheet');
            }, true);

            $('gaNameSave').addEventListener('click', async (e) => {
                const save = e.currentTarget;
                const id = $('gaNameId').value;
                if (!id) return;
                save.disabled = true;
                try {
                    const res = await api(U.rename, { method: 'POST', body: {
                        id,
                        caption: $('gaNameCaption').value.trim() || null,
                        description: $('gaNameDesc').value.trim() || null,
                    } });
                    const row = res.data;
                    ALBUMS.forEach((a) => a.images.forEach((im) => {
                        if (String(im.id) === String(row.id)) { im.caption = row.caption; im.description = row.description; }
                    }));
                    // The All shelf reads titles from its own list — same
                    // fallback as the server, so a cleared name says the same
                    // thing after a refresh as it does right now.
                    const it = EVERYTHING.find((m) => String(m.albumImageId) === String(row.id));
                    if (it) it.title = row.caption || 'In an album';
                    paint();
                    // The shelf tiles are patched in place rather than
                    // refilled: fill() would fold a long scroll back to its
                    // first screenful, the same reason deleting one does.
                    document.querySelectorAll('.ga-item[data-lb-image="' + row.id + '"]').forEach((t) => {
                        t.setAttribute('data-lb-caption', row.caption || '');
                        const ti = t.querySelector('.ga-it');
                        if (ti) ti.textContent = it ? it.title : (row.caption || 'In an album');
                    });
                    // And the strip on the still-open lightbox says the new
                    // name without anyone closing and reopening anything.
                    const bar = lb.querySelector('.ga-lb-cap');
                    const pencil = lb.querySelector('.ga-lb-rename');
                    if (bar && lb.classList.contains('is-open')) {
                        const sub = pencil?.dataset.live ? (row.description || '') : (pencil?.dataset.sub || '');
                        bar.innerHTML = (row.caption ? `<b>${esc(row.caption)}</b>` : '') + (sub ? `<i>${esc(sub)}</i>` : '');
                        bar.hidden = !(row.caption || sub);
                    }
                    closeSheet('gaNameSheet');
                    toast(res.message);
                } catch (err) { toast(err.message || 'Could not save that.', 'error'); }
                finally { save.disabled = false; }
            });
        })();

        $('gaFind')?.addEventListener('input', (e) => { findText = e.target.value; paintAll(); });
        $('gaFilters')?.addEventListener('click', (e) => {
            const b = e.target.closest('.ga-filter');
            if (!b) return;
            findSource = b.getAttribute('data-src') || '';
            document.querySelectorAll('.ga-filter').forEach((x) => x.classList.toggle('is-on', x === b));
            paintAll();
        });

        paintAll();
        paint();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
</script>
@endpush
