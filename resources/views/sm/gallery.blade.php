@extends(request()->boolean('partial') ? 'layouts.partial' : 'layouts.app')

@section('title', 'Gallery — ' . $schedule->title)
@section('page-title', 'Gallery')
@section('page-subtitle', $schedule->title)
@section('help-key', 'gallery')
@section('back', route('sm.hub', ['id' => $schedule->id]))

@push('head')
<style>
    .ga-top { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; margin-bottom: .9rem; }
    .ga-new { display: inline-flex; align-items: center; gap: .4rem; height: 2.75rem; padding: 0 1rem;
        border-radius: .8rem; background: #4a7c2a; color: #fff; font-size: .85rem; font-weight: 800;
        cursor: pointer; box-shadow: 0 8px 18px -12px rgb(61 104 35 / .9); }
    .ga-new:hover { background: #3d6823; }
    .ga-new svg { width: 1.05rem; height: 1.05rem; }

    .ga-album { border: 1px solid var(--color-gray-200); border-radius: 1rem; background: var(--color-white);
        margin-bottom: .9rem; overflow: hidden; }
    .ga-head { display: flex; align-items: flex-start; gap: .6rem; padding: .8rem .9rem; }
    .ga-title { font-size: .98rem; font-weight: 800; color: var(--color-gray-900); }
    .ga-desc { font-size: .8rem; line-height: 1.45; color: var(--tl-text-muted, #6b7280); margin-top: .15rem; }
    .ga-count { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        color: #3d6823; background: #e4efd4; border-radius: 999px; padding: .15rem .5rem; }
    .ga-acts { margin-left: auto; display: flex; gap: .25rem; flex: 0 0 auto; }
    .ga-act { width: 2rem; height: 2rem; border-radius: .55rem; display: inline-flex; align-items: center;
        justify-content: center; color: var(--color-gray-500); background: var(--color-gray-50); cursor: pointer; }
    .ga-act:hover { background: var(--color-gray-100); color: var(--color-gray-800); }
    .ga-act.is-danger:hover { background: #fee2e2; color: #b91c1c; }
    .ga-act svg { width: 1rem; height: 1rem; }

    .ga-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(7rem, 1fr)); gap: .5rem;
        padding: 0 .9rem .9rem; }
    .ga-cell { position: relative; aspect-ratio: 1; border-radius: .7rem; overflow: hidden; background: #0b1220;
        cursor: pointer; }
    .ga-cell img { width: 100%; height: 100%; object-fit: cover; display: block; opacity: 0; transition: opacity .28s ease; }
    .ga-cell img.is-loaded { opacity: 1; }
    .ga-cell.is-gone::after { content: 'File missing'; position: absolute; inset: 0; display: flex;
        align-items: center; justify-content: center; font-size: .66rem; font-weight: 700; color: #94a3b8; }
    /* Picking is a mode: the tick is always there to start it, and once one
       picture is chosen the whole grid becomes a checklist. */
    .ga-pick { position: absolute; top: .3rem; left: .3rem; width: 1.5rem; height: 1.5rem; border-radius: 999px;
        background: rgb(17 24 39 / .55); border: 2px solid rgb(255 255 255 / .8); display: inline-flex;
        align-items: center; justify-content: center; color: transparent; }
    .ga-cell.is-picked .ga-pick { background: #4a7c2a; border-color: #fff; color: #fff; }
    .ga-cell.is-picked { outline: 3px solid #4a7c2a; outline-offset: -3px; }
    .ga-pick svg { width: .9rem; height: .9rem; }
    .ga-empty { padding: 0 .9rem 1rem; font-size: .8rem; color: var(--color-gray-400); }

    /* The bar that appears once something is picked. */
    .ga-bar { position: fixed; left: 50%; transform: translateX(-50%); bottom: 1rem; z-index: 60;
        display: flex; align-items: center; gap: .5rem; padding: .5rem .6rem; border-radius: 999px;
        background: #10160c; color: #e8efe1; box-shadow: 0 18px 40px -18px rgb(0 0 0 / .8);
        animation: gaBarIn .28s cubic-bezier(.22,1,.36,1) both; }
    @keyframes gaBarIn { from { opacity: 0; transform: translate(-50%, 1rem); } }
    .ga-bar[hidden] { display: none; }
    .ga-bar-n { font-size: .78rem; font-weight: 800; padding-left: .35rem; }
    .ga-bar button { font-size: .78rem; font-weight: 700; padding: .4rem .7rem; border-radius: 999px;
        background: rgb(255 255 255 / .12); color: #e8efe1; cursor: pointer; }
    .ga-bar button:hover { background: rgb(255 255 255 / .22); }
    .ga-bar .ga-bar-del:hover { background: #b91c1c; color: #fff; }
    @media (prefers-reduced-motion: reduce) { .ga-bar { animation: none; } .ga-cell img { transition: none; } }

    html.dark .ga-album { background: #151b12; border-color: #2b3a1c; }
    html.dark .ga-title { color: #e8efe1; }
    html.dark .ga-count { background: rgb(61 104 35 / .35); color: #a8cc7e; }
    html.dark .ga-act { background: rgb(255 255 255 / .05); color: #cdd8c0; }
</style>
@endpush

@section('content')
@include('sm.partials.module-header', ['schedule' => $schedule, 'module' => 'gallery'])

<div class="ga-top">
    <button type="button" class="ga-new" id="gaNewAlbum">
        <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        <span>New album</span>
    </button>
    <p class="text-xs text-gray-500">Group pictures by what they are about. The Media Box keeps everything; an album is what you chose.</p>
</div>

<div id="gaAlbums"></div>

<div class="card p-8 text-center hidden" id="gaEmpty">
    <div class="text-4xl mb-2">🖼️</div>
    <p class="font-bold text-gray-900">No albums yet</p>
    <p class="text-sm text-gray-500 mt-1">Make one for a corner of the field, a problem you are following, or anything worth keeping together.</p>
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
@endpush

@push('scripts')
<script>
(() => {
    const init = () => {
        const SCHEDULE_ID = @json($schedule->id);
        const U = {
            album: @json(route('sm.gallery.album.save')) + '?scheduleId=' + SCHEDULE_ID,
            albumDel: @json(route('sm.gallery.album.destroy')) + '?scheduleId=' + SCHEDULE_ID,
            upload: @json(route('sm.gallery.image.store')) + '?scheduleId=' + SCHEDULE_ID,
            move: @json(route('sm.gallery.image.move')) + '?scheduleId=' + SCHEDULE_ID,
            del: @json(route('sm.gallery.image.destroy')) + '?scheduleId=' + SCHEDULE_ID,
        };
        const CSRF = document.querySelector('meta[name=csrf-token]').content;
        let ALBUMS = @json($albums);
        const picked = new Set();
        const $ = (id) => document.getElementById(id);

        const esc = window.escapeHtml || ((s) => String(s ?? ''));

        function albumHtml(a) {
            const cells = a.images.map((im) => `
                <div class="ga-cell" data-image="${im.id}" data-lb-type="image" data-lb-url="${esc(im.url)}">
                    <img src="${esc(im.url)}" alt="${esc(im.caption || '')}" loading="lazy"
                        onload="this.classList.add('is-loaded')"
                        onerror="this.closest('.ga-cell')?.classList.add('is-gone'); this.remove();">
                    <span class="ga-pick" data-pick><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></span>
                </div>`).join('');
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
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.9 12.1a2 2 0 01-2 1.9H7.9a2 2 0 01-2-1.9L5 7m3 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-11 0h16"/></svg>
                        </button>
                    </span>
                </div>
                ${a.images.length
                    ? `<div class="ga-grid">${cells}</div>`
                    : '<p class="ga-empty">Nothing in here yet — tap + to add pictures.</p>'}
            </div>`;
        }

        function paint() {
            $('gaAlbums').innerHTML = ALBUMS.map(albumHtml).join('');
            $('gaEmpty').classList.toggle('hidden', ALBUMS.length > 0);
            paintBar();
        }

        function paintBar() {
            const n = picked.size;
            $('gaBar').hidden = n === 0;
            $('gaBarN').textContent = n + ' picked';
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
            setTimeout(() => $('gaAlbumTitle').focus(), 150);
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
                if (album) album.images.unshift(...json.data.images);
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
                if (target) target.images.unshift(...moving);
                picked.clear();
                paint();
                closeSheet('gaMoveSheet');
                toast(res.message);
            } catch (err) { toast(err.message || 'Could not move those.', 'error'); }
        });

        paint();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
})();
</script>
@endpush
