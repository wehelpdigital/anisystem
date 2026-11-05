{{-- Import from phone (2026-11-05, the owner: "in galleries, both the global and the internal per cropping schedule have an
     option to import from phone ... a button or a tab, in a taglike button"). Pictures already on the phone go into an album
     in one go, with no naming each one: that is what Quick Capture's own flow is for.

     $season: the season's Gallery names its own season. $seasons: the Global Gallery asks which one, because an album belongs
     to a season. $video: whether this person may bring in clips too.

     Each file goes up in its own request. A phone's forty photos then show a real count, one too big for the server costs
     only itself, and a dropped line says how far it got. Photos go through Quick Capture's door and clips through Quick
     Record's, which is the one that asks about the video right and the plan.

     Only what the person chose goes in, and a video meets two walls the phone can see coming: Cloudflare turns away a request
     over 100 MB before it reaches us (measured 2026-11-06: 90 MB passes, 120 MB is a 413), and a clip may run one minute
     (VideoOptimizer::MAX_SECONDS). Those are marked on their tile and left out, rather than sent to be refused. --}}
@php
    $giSeason = $season ?? null;
    $giSeasons = $seasons ?? collect();
@endphp
<button type="button" class="ga-import" data-ga-import data-video="{{ ($video ?? true) ? '1' : '0' }}">
    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg>
    <span>Import from phone</span>
</button>

@push('head')
<style>
    .ga-shelfbar:has(.ga-import) { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    /* A tag you can tap: the app's important actions are yellow. */
    .ga-import { display: inline-flex; align-items: center; gap: .35rem; height: 2.25rem; padding: 0 .85rem; border-radius: 999px;
        background: #f5c518; color: #1f2937; font-size: .8125rem; font-weight: 800; white-space: nowrap; cursor: pointer;
        transition: transform .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .ga-import:hover { background: #e6b800; }
    .ga-import:active { transform: scale(.96); }
    .ga-import svg { width: 1rem; height: 1rem; flex: none; }
    /* On the narrowest phones the words stay and the icon goes, so it keeps its place beside the shelf button. */
    @media (max-width: 379px) { .ga-import svg { display: none; } }
    .gi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(4.5rem, 1fr)); gap: .4rem; }
    .gi-cell { position: relative; aspect-ratio: 1; border-radius: .6rem; overflow: hidden; background: var(--color-gray-100); animation: giIn .28s cubic-bezier(.22,1,.36,1); }
    .gi-cell img, .gi-cell video { width: 100%; height: 100%; object-fit: cover; display: block; }
    .gi-cell button { position: absolute; top: .2rem; right: .2rem; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: flex; align-items: center;
        justify-content: center; background: rgb(17 24 39 / .72); color: #fff; font-size: .85rem; line-height: 1; }
    .gi-kind { position: absolute; left: .2rem; bottom: .2rem; padding: 0 .3rem; border-radius: .3rem; background: rgb(17 24 39 / .72); color: #fff; font-size: .625rem; font-weight: 700; }
    .gi-more { aspect-ratio: 1; border: 1.5px dashed var(--color-gray-300); border-radius: .6rem; background: var(--color-gray-50); color: var(--color-gray-500);
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .15rem; font-size: .72rem; font-weight: 700; }
    .gi-more svg { width: 1.2rem; height: 1.2rem; }
    .gi-cell.is-out img, .gi-cell.is-out video { opacity: .35; }
    .gi-cell.is-out .gi-kind { background: #b91c1c; }
    .gi-note { margin-top: .45rem; font-size: .75rem; line-height: 1.35; color: var(--color-gray-500); }
    .gi-note.is-warn { color: var(--color-gray-700); font-weight: 700; }
    .gi-go { background: #f5c518; color: #1f2937; font-weight: 800; }
    .gi-go:hover { background: #e6b800; }
    .gi-go:disabled { opacity: .5; }
    @keyframes giIn { from { opacity: 0; transform: scale(.92); } }
    @media (prefers-reduced-motion: reduce) { .ga-import { transition: none; } .gi-cell { animation: none; } }
</style>
@endpush

@push('sheets')
<div class="sheet hidden" id="gaImportSheet" style="--sheet-width:30rem" data-season="{{ $giSeason }}">
    <div class="sheet-handle"></div>
    <div class="sheet-header">
        <h3 class="sheet-title" id="giTitle">Import from phone</h3>
        <button type="button" data-sheet-close class="btn-ghost p-2 rounded-full" aria-label="Close">✕</button>
    </div>
    <div class="sheet-body space-y-4">
        <div>
            <div class="gi-grid" id="giGrid"></div>
            <p class="gi-note" id="giNote"></p>
        </div>
        @if (! $giSeason)
            <div>
                <label class="form-label" for="giSeason">Which cropping schedule?</label>
                <select id="giSeason" class="form-select">
                    @if ($giSeasons->count() > 1)<option value="">Choose one</option>@endif
                    @foreach ($giSeasons as $s)
                        <option value="{{ $s->id }}">{{ $s->title }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label class="form-label" for="giAlbum">Album</label>
            <select id="giAlbum" class="form-select"><option value="">➕ New album</option></select>
            <input type="text" id="giAlbumTitle" class="form-input mt-2" maxlength="191" placeholder="Name of the new album" aria-label="Name of the new album">
        </div>
    </div>
    <div class="sheet-footer">
        <button type="button" class="btn btn-ghost" data-sheet-close>Cancel</button>
        <button type="button" class="btn gi-go" id="giGo">Import</button>
    </div>
</div>
@endpush

@push('scripts')
<script>
(() => {
    if (window.gaImportBound) return;
    window.gaImportBound = true;
    const SAVE = @json(route('quick-capture.gallery'));
    const CLIP = @json(route('quick-record.clip'));
    const ALBUMS = @json(route('quick-capture.albums'));
    const PHOTO_MAX = 8 * 1024 * 1024;   // what the server takes, QuickCaptureController::storeGallery
    const CLIP_MAX = @json(min(95 * 1024 * 1024, \Illuminate\Http\UploadedFile::getMaxFilesize()));   // under Cloudflare's 100 MB, or this server's own limit if lower
    const CLIP_SECONDS = @json(\App\Support\VideoOptimizer::MAX_SECONDS);
    const LIMIT_WORDS = 'A video can be up to 1 minute long and ' + Math.floor(CLIP_MAX / 1048576) + ' MB.';
    const FLASH = 'anee.galleryImport';
    const $ = (id) => document.getElementById(id);
    const csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';
    const isVideo = (f) => /^video\//.test(f.type) || (!f.type && /\.(mp4|mov|webm|mkv|m4v|3gp|avi)$/i.test(f.name));
    const word = (n, list) => n + (list.some(isVideo) ? ' item' : ' photo') + (n === 1 ? '' : 's');
    const whyOut = (p) => !isVideo(p.file) ? '' : (p.file.size > CLIP_MAX ? 'Too big' : (p.long ? 'Too long' : ''));
    const going = () => picked.filter((p) => !whyOut(p));
    let picked = [];   // { file, url, long }
    let videoOk = true;
    let running = false;

    // No `capture` on it: this opens the phone's photos, not its camera. In the page, so a probe and a phone reach it alike.
    const input = document.createElement('input');
    input.type = 'file';
    input.multiple = true;
    input.hidden = true;
    input.id = 'giPicker';
    document.body.appendChild(input);

    const choose = () => {
        input.accept = videoOk ? 'image/*,video/*' : 'image/*';
        input.click();
    };

    function paint() {
        const grid = $('giGrid');
        grid.innerHTML = '';
        picked.forEach((p, i) => {
            const cell = document.createElement('div');
            const why = whyOut(p);
            cell.className = 'gi-cell' + (why ? ' is-out' : '');
            const video = isVideo(p.file);
            const shot = document.createElement(video ? 'video' : 'img');
            if (video) {
                shot.src = p.url + '#t=0.1'; shot.muted = true; shot.playsInline = true; shot.preload = 'metadata';
                // A length the phone cannot read is let through: the server asks again, of the file itself.
                shot.addEventListener('loadedmetadata', () => {
                    if (!p.long && Number.isFinite(shot.duration) && shot.duration > CLIP_SECONDS + 0.75) { p.long = true; paint(); }
                });
            } else {
                shot.src = p.url; shot.alt = ''; shot.loading = 'lazy'; shot.decoding = 'async';
            }
            cell.appendChild(shot);
            if (video) {
                const kind = document.createElement('span');
                kind.className = 'gi-kind'; kind.textContent = why || 'Video';
                cell.appendChild(kind);
            }
            const x = document.createElement('button');
            x.type = 'button'; x.textContent = '×'; x.setAttribute('aria-label', 'Leave this one out');
            x.addEventListener('click', () => {
                URL.revokeObjectURL(p.url);
                picked.splice(i, 1);
                if (!picked.length) { window.closeSheet('gaImportSheet'); return; }
                paint();
            });
            cell.appendChild(x);
            grid.appendChild(cell);
        });
        const more = document.createElement('button');
        more.type = 'button'; more.className = 'gi-more';
        more.innerHTML = '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14m7-7H5"/></svg><span>Add more</span>';
        more.addEventListener('click', choose);
        grid.appendChild(more);
        const files = going().map((p) => p.file);
        const out = picked.length - files.length;
        const say = files.length ? 'Import ' + word(files.length, files) : 'Nothing to import';
        $('giTitle').textContent = say;
        $('giGo').textContent = say;
        $('giGo').disabled = !files.length;
        const note = $('giNote');
        note.textContent = out
            ? (out === 1 ? '1 video' : out + ' videos') + ' will stay on your phone. ' + LIMIT_WORDS
            : 'Only these go in. Everything else stays on your phone. Tap × to leave one out.';
        note.classList.toggle('is-warn', out > 0);
    }

    const seasonNow = () => $('gaImportSheet')?.dataset.season || $('giSeason')?.value || '';

    function syncAlbumName() {
        $('giAlbumTitle').classList.toggle('hidden', !!$('giAlbum').value);
    }

    let asked = null;
    async function loadAlbums() {
        const sid = seasonNow();
        if (asked === sid) return;
        asked = sid;
        const sel = $('giAlbum');
        sel.innerHTML = '<option value="">➕ New album</option>';
        syncAlbumName();
        if (!sid) return;
        try {
            const res = await fetch(ALBUMS + '?scheduleId=' + encodeURIComponent(sid), { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const data = await res.json();
            (data?.data?.albums || []).forEach((a) => {
                const o = document.createElement('option');
                o.value = a.id; o.textContent = a.title;
                sel.appendChild(o);
            });
        } catch (_) { asked = null; }   // no list is no harm: a new album still works
    }

    input.addEventListener('change', () => {
        const fresh = [...(input.files || [])].filter((f) => videoOk || !isVideo(f));
        input.value = '';
        if (!fresh.length) return;
        const wasEmpty = !picked.length;
        fresh.forEach((file) => picked.push({ file, url: URL.createObjectURL(file) }));
        paint();
        if (!wasEmpty) return;
        const day = new Date().toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' });
        $('giAlbumTitle').value = 'Imported from phone, ' + day;
        window.openSheet('gaImportSheet');
        loadAlbums();
    });

    // The server keeps jpg, png and webp. Anything else (HEIC, GIF, BMP) becomes a jpg here, if this phone can open it at all.
    async function asJpeg(file) {
        try {
            const bmp = await createImageBitmap(file);
            const canvas = document.createElement('canvas');
            canvas.width = bmp.width;
            canvas.height = bmp.height;
            canvas.getContext('2d').drawImage(bmp, 0, 0);
            bmp.close?.();
            const blob = await new Promise((ok) => canvas.toBlob(ok, 'image/jpeg', 0.85));
            return blob ? window.aneeShrinkPhoto(new File([blob], (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' })) : null;
        } catch (_) {
            return null;
        }
    }

    async function run() {
        const sid = seasonNow();
        if (!sid) { window.toast('Choose which cropping schedule these belong to.', 'error'); $('giSeason')?.focus(); return; }
        let albumId = $('giAlbum').value;
        const albumName = albumId ? $('giAlbum').selectedOptions[0].textContent : $('giAlbumTitle').value.trim();
        if (!albumName) { window.toast('Name the new album first.', 'error'); $('giAlbumTitle').focus(); return; }

        if (running) return;
        running = true;
        const list = going().map((p) => p.file);
        if (!list.length) { running = false; return; }
        const left = picked.length - list.length;
        window.closeSheet('gaImportSheet');
        const busy = window.smBusy('Importing 1 of ' + list.length + '…');
        let done = 0, trouble = left ? LIMIT_WORDS : '', clipsShut = false;
        for (let i = 0; i < list.length; i++) {
            const video = isVideo(list[i]);
            busy.say('Importing ' + (i + 1) + ' of ' + list.length + '…' + (video ? ' A video takes longer. Keep this page open.' : ''));
            if (video && clipsShut) continue;
            let file = list[i];
            if (!video) {
                file = /^image\/(jpe?g|png|webp)$/.test(file.type) ? await window.aneeShrinkPhoto(file) : await asJpeg(file);
                if (!file) { trouble = trouble || 'A photo was in a format this phone cannot read.'; continue; }
                if (file.size > PHOTO_MAX) { trouble = trouble || 'A photo was over 8 MB.'; continue; }
            }
            const fd = new FormData();
            fd.append('scheduleId', sid);
            if (albumId) fd.append('albumId', albumId); else fd.append('albumTitle', albumName);
            if (video) {
                fd.append('target', 'gallery');
                fd.append('title', albumName);   // or it is captioned "Quick record" and the hour
                fd.append('clip', file);
            } else {
                fd.append('images[0]', file);
            }
            try {
                const res = await fetch(video ? CLIP : SAVE, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' }, body: fd, credentials: 'same-origin' });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success) {
                    done++;
                    // The first one made the album; the rest join it, not one album each.
                    albumId = albumId || String(data.albumId || '');
                    continue;
                }
                // A validation reply names the field ("images.0"), which helps nobody.
                const said = data.errors ? 'A file was in a format the app cannot take.' : data.message;
                trouble = trouble || said || (res.status === 413 ? 'A video was too big to send.' : 'One could not be saved.');
                // No clips for this season or plan: the photos can still go.
                if (video && res.status === 403) { clipsShut = true; continue; }
                // Refused outright: the next one would hear the same.
                if ([401, 403, 419, 423].includes(res.status)) break;
            } catch (_) {
                trouble = trouble || 'The connection dropped.';
                // A long video can lose its send alone and the next file still go. A photo losing it means the line is gone.
                if (!video) break;
            }
        }
        busy.close();
        running = false;

        const missed = picked.length - done;
        if (!done) {
            window.toast(trouble || 'Nothing could be imported.', 'error', 9000);
            // Back to the sheet with the picks still in it, after the close above has finished hiding it.
            setTimeout(() => window.openSheet('gaImportSheet'), 300);
            return;
        }
        // Said after the reload, which is what puts the new pictures on the shelves.
        try {
            sessionStorage.setItem(FLASH, JSON.stringify(missed
                ? { type: 'error', text: word(done, list) + ' imported to ' + albumName + '. ' + missed + ' did not. ' + trouble }
                : { type: 'success', text: word(done, list) + ' imported to ' + albumName + '.' }));
        } catch (_) { /* a private window goes without the words */ }
        picked.forEach((p) => URL.revokeObjectURL(p.url));
        picked = [];
        location.reload();
    }

    document.addEventListener('click', (e) => {
        const door = e.target.closest('[data-ga-import]');
        if (door) {
            // A fresh start: what was left behind by a Cancel does not ride along.
            picked.forEach((p) => URL.revokeObjectURL(p.url));
            picked = [];
            videoOk = door.dataset.video !== '0';
            choose();
            return;
        }
        if (e.target.closest('#giGo')) run();
    });
    document.addEventListener('change', (e) => {
        if (e.target.id === 'giAlbum') syncAlbumName();
        if (e.target.id === 'giSeason') { asked = null; loadAlbums(); }
    });
    const tell = () => {
        let said = null;
        try { said = JSON.parse(sessionStorage.getItem(FLASH) || 'null'); sessionStorage.removeItem(FLASH); } catch (_) { /* nothing to say */ }
        if (said) window.toast(said.text, said.type, said.type === 'error' ? 9000 : 4500);
    };
    // Inside the season's shell this script arrives long after the page's load.
    if (document.readyState === 'complete') tell(); else window.addEventListener('load', tell);
})();
</script>
@endpush
