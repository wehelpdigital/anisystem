{{-- ============================================================
     The four ways in, as one row.

     Every composer in the app should offer the same four: choose from the
     gallery, upload a file, record a clip, take a photo. Most offered one or
     two, and which two depended on which screen you happened to be on.

     Include it as many times as a page has composers:

       @include('sm.partials.attach-bar', [
           'barId'      => 'phAttach',                 // to listen on; not `id`,
                                                       //   which half the pages
                                                       //   already have of their own
           'scheduleId' => $schedule->id,              // whose gallery to offer
           'imageUrl'   => $photoUploadEndpoint,       // POST, field `image`
           'videoUrl'   => $videoUploadEndpoint,       // optional; no url, no Record
           'kinds'      => 'image,video',              // what the gallery offers
           'imageField' => 'image', 'videoField' => 'video',   // if yours differ
           'multiple'   => true,                       // several files at once
           'hint'       => 'Snap the sacks…',          // optional line beneath
       ])

     Then listen on the element for what came back:

       bar.addEventListener('attach:add', (e) => add(e.detail));
         // detail: {type:'image'|'video', path, url, poster?, posterUrl?, source}

     `attach:camera` fires first and is cancellable: a host with a better
     camera of its own (a desktop live preview, say) can preventDefault it and
     open that instead. On a phone, let it through — the camera app takes a
     far better picture than a getUserMedia preview ever will.

     Everything the bar needs comes with it: the shared recorder writes into
     this row's .js-video-file, and the gallery sheet is pulled in on its own.
     ============================================================ --}}
@once
    {{-- Both are guarded singletons, so a page that already includes either
         one is unharmed by asking again. --}}
    @include('community.partials.video-js')
    @include('sm.partials.media-picker')
@endonce

<div class="ab" @isset($barId) id="{{ $barId }}" @endisset
     data-attach-bar data-video-host
     data-schedule-id="{{ $scheduleId ?? '' }}"
     data-image-url="{{ $imageUrl ?? '' }}"
     data-video-url="{{ $videoUrl ?? '' }}"
     data-image-field="{{ $imageField ?? 'image' }}"
     data-video-field="{{ $videoField ?? 'video' }}"
     data-kinds="{{ $kinds ?? 'image,video' }}">
    <div class="ab-row" role="group" aria-label="{{ $label ?? 'Attach something' }}">
        <button type="button" class="ab-btn" data-ab="gallery" title="Choose something already saved for this season">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 15l4-4 4 4 3-3 5 5"/><circle cx="9" cy="8.5" r="1.3"/></svg>
            <span>Gallery</span>
        </button>
        <button type="button" class="ab-btn" data-ab="upload" title="Choose a file already on this device">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0-12l-4 4m4-4l4 4"/></svg>
            <span>Upload</span>
        </button>
        @if (filled($videoUrl ?? null))
            <button type="button" class="ab-btn js-video-record" title="Record a video">
                <svg viewBox="0 0 24 24" fill="currentColor" class="text-red-500"><circle cx="12" cy="12" r="7"/></svg>
                <span>Record</span>
            </button>
        @endif
        <button type="button" class="ab-btn" data-ab="camera" title="Take a photo now">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Photo</span>
        </button>
    </div>
    @isset($hint)
        <p class="form-hint">{{ $hint }}</p>
    @endisset
    {{-- The recorder writes the clip into this input and fires a change on it
         by hand, because assigning .files in script does not fire one. --}}
    <input type="file" class="js-video-file" accept="video/*" hidden>
    <input type="file" data-ab-file accept="{{ filled($videoUrl ?? null) ? 'image/*,video/*' : 'image/*' }}" hidden @if ($multiple ?? true) multiple @endif>
    {{-- capture= asks the phone for its camera rather than its files. Desktop
         browsers ignore it and show a picker, which is the right fallback. --}}
    <input type="file" data-ab-camera accept="image/*" capture="environment" hidden>
    <div class="ab-ups"></div>
</div>

@once
<style>
    /* One row that stays one row: the buttons share the width instead of each
       taking the width of its own word. Same shape as the note editor's tool
       row, which is what most people will have met first. */
    .ab { display: grid; gap: .35rem; }
    .ab-row { display: flex; align-items: stretch; gap: .35rem; }
    .ab-btn { flex: 1 1 0; min-width: 0; display: inline-flex; flex-direction: column;
        align-items: center; justify-content: center; gap: .15rem; padding: .4rem .2rem;
        border: 1px solid var(--color-gray-200); border-radius: .65rem; background: var(--color-white);
        font-size: .66rem; font-weight: 700; color: var(--color-gray-600); cursor: pointer;
        transition: background .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1),
            color .28s cubic-bezier(.22,1,.36,1); }
    .ab-btn:hover { background: #f3f8ec; border-color: #a8cc7e; color: #3d6823; }
    .ab-btn svg { width: 1.05rem; height: 1.05rem; }
    .ab-btn span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; }
    .ab .form-hint { margin: 0; }
    /* Upload progress: a phone video is tens of megabytes, and a quiet wait
       reads as a hang. Percent while the bytes travel, then a word for the
       compressing the server still has to do. */
    .ab-ups:empty { display: none; }
    .ab-ups { display: grid; gap: .4rem; }
    .ab-up { border: 1px solid var(--color-gray-200); border-radius: .6rem; padding: .45rem .6rem; }
    .ab-up-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        font-size: .75rem; font-weight: 700; color: var(--tl-text-muted, #4b5563); }
    .ab-up-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ab-up-pct { flex: 0 0 auto; font-variant-numeric: tabular-nums; }
    .ab-up-bar { height: .3rem; border-radius: 999px; background: var(--color-gray-200);
        overflow: hidden; margin-top: .35rem; }
    .ab-up-fill { height: 100%; width: 0; border-radius: 999px; background: var(--color-primary, #4a7c2a);
        transition: width .2s linear; }
    .ab-up.is-error { border-color: #fecaca; background: #fef2f2; }
    .ab-up.is-error .ab-up-top { color: #b91c1c; }
    .ab-up.is-error .ab-up-fill { background: #ef4444; }
    html.dark .ab-btn { background: #1c2416; border-color: #2b3a1c; color: #cdd8c0; }
    html.dark .ab-btn:hover { background: #24301a; border-color: #3d6823; }
    html.dark .ab-up { border-color: #2b3a1c; }
    @media (prefers-reduced-motion: reduce) {
        .ab-btn { transition: none; }
        .ab-up-fill { transition: none; }
    }
</style>

<script>
(function attachBar() {
    if (window.smAttachBar) return;

    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';
    const VID_RE = /\.(mp4|mov|webm|mkv|avi|3gp|m4v)$/i;
    const isVideo = (f) => /^video\//.test(f.type || '') || VID_RE.test(f.name || '');
    const say = (m, t) => window.toast?.(m, t);
    const barOf = (el) => el && el.closest('[data-attach-bar]');

    function fmtSize(bytes) {
        if (!bytes) return '';
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    /** Hand the item to whoever is hosting this bar. */
    function emit(bar, item) {
        bar.dispatchEvent(new CustomEvent('attach:add', { bubbles: true, detail: item }));
    }

    /** Upload one file, saying how far it has got. XHR, not fetch, because
     *  fetch cannot report upload progress — and on a field connection the
     *  difference between 40% and silence is the difference between waiting
     *  and pressing the button again. */
    function upload(bar, url, field, file) {
        const row = document.createElement('div');
        row.className = 'ab-up';
        row.innerHTML = '<div class="ab-up-top"><span class="ab-up-name"></span><span class="ab-up-pct">0%</span></div>'
            + '<div class="ab-up-bar"><div class="ab-up-fill"></div></div>';
        row.querySelector('.ab-up-name').textContent = (file.name || 'File') + (file.size ? ' · ' + fmtSize(file.size) : '');
        bar.querySelector('.ab-ups').appendChild(row);
        const pct = row.querySelector('.ab-up-pct');
        const fill = row.querySelector('.ab-up-fill');

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
                resolve(json.data || {});
            });
            xhr.addEventListener('error', () => fail('Upload failed — check your connection.'));
            xhr.addEventListener('abort', () => fail('Upload cancelled.'));
            xhr.send(form);
        });
    }

    /** One file, sent to whichever endpoint its kind belongs to. */
    async function take(bar, file, source) {
        const video = isVideo(file);
        const url = video ? bar.dataset.videoUrl : bar.dataset.imageUrl;
        if (!url) {
            say(video ? 'This only takes photos — a clip has nowhere to go here.'
                : 'There is nowhere to put a photo here.', 'error');
            return;
        }
        const field = video ? (bar.dataset.videoField || 'video') : (bar.dataset.imageField || 'image');
        try {
            const d = await upload(bar, url, field, file);
            emit(bar, {
                type: video ? 'video' : 'image',
                path: d.path,
                poster: d.poster || null,
                url: d.url,
                posterUrl: d.posterUrl || null,
                source,
            });
        } catch (err) {
            say(err.message, 'error');
        }
    }

    async function takeAll(bar, files, source) {
        for (const file of files) {
            // One at a time: a phone on a field connection sending four videos
            // at once finishes none of them.
            await take(bar, file, source);
        }
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-ab]');
        if (!btn) return;
        const bar = barOf(btn);
        if (!bar) return;
        e.preventDefault();
        const what = btn.getAttribute('data-ab');

        if (what === 'upload') { bar.querySelector('[data-ab-file]')?.click(); return; }

        if (what === 'camera') {
            // Cancellable, so a host with a better camera can step in.
            const ev = new CustomEvent('attach:camera', { bubbles: true, cancelable: true });
            bar.dispatchEvent(ev);
            if (!ev.defaultPrevented) bar.querySelector('[data-ab-camera]')?.click();
            return;
        }

        if (what === 'gallery') {
            if (typeof window.smPickMedia !== 'function') {
                say('The gallery is not available on this page yet.', 'error');
                return;
            }
            window.smPickMedia({
                scheduleId: bar.dataset.scheduleId,
                kinds: bar.dataset.kinds,
                onPick: (item) => emit(bar, {
                    type: item.type,
                    path: item.path,
                    poster: item.poster || null,
                    url: item.url,
                    posterUrl: item.posterUrl || null,
                    source: 'gallery',
                }),
            });
        }
    });

    document.addEventListener('change', (e) => {
        const input = e.target;
        if (!input.matches || !input.matches('[data-ab-file], [data-ab-camera], .js-video-file')) return;
        const bar = barOf(input);
        if (!bar) return;                       // some other page's video input

        const files = Array.from(input.files || []);
        input.value = '';                       // so the same file can be picked twice
        if (!files.length) return;
        const source = input.hasAttribute('data-ab-camera') ? 'camera'
            : (input.classList.contains('js-video-file') ? 'record' : 'upload');
        // The recorder leaves the clip in its input and shows a chip for it;
        // once the bytes are on their way the progress row says everything the
        // chip did, so clear it rather than leave two things saying "video".
        if (source === 'record') window.plazaClearVideo?.(bar);
        takeAll(bar, files, source);
    });

    /**
     * For hosts that would rather hold a handle than an element.
     *   const a = window.smAttachBar('phAttach');
     *   a.onAdd((item) => …);
     */
    window.smAttachBar = function (elOrId) {
        const root = typeof elOrId === 'string' ? document.getElementById(elOrId) : elOrId;
        return {
            root,
            onAdd(fn) { root?.addEventListener('attach:add', (e) => fn(e.detail)); return this; },
            reset() { const u = root?.querySelector('.ab-ups'); if (u) u.innerHTML = ''; return this; },
        };
    };
})();
</script>
@endonce
