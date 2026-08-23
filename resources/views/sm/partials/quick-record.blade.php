{{-- Quick Record — one clip, named, filed.

     Quick Capture's shorter sibling. A video is one thing rather than a group,
     and a still is what the AI technician reads, so there is no assembling
     step and no AI door: record, say what it is, choose where it lands.

     Expects $allSchedules (and optionally $fixedScheduleId, when opened from
     inside a schedule). Recording itself is the shared recorder every other
     composer uses, borrowed through a hidden host. --}}
<div class="qr-modal hidden" id="quickRecordModal" role="dialog" aria-modal="true" aria-label="Quick Record">
    <div class="qr-backdrop" data-qr-cancel></div>
    <div class="qr-card">
        <div class="qr-head">
            <h3 class="font-bold text-gray-900">Quick Record</h3>
            <button type="button" class="btn-ghost p-2 rounded-full" data-qr-cancel aria-label="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <div class="qr-body space-y-4">
            <div class="qr-clip" id="qrClip">
                <video id="qrPreview" playsinline controls></video>
                <span class="qr-size" id="qrSize"></span>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn btn-white btn-sm" id="qrRerecord">
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="7"/></svg>
                    Record again
                </button>
                <button type="button" class="btn btn-white btn-sm" id="qrUpload">Choose a video instead</button>
            </div>

            <div>
                <label class="form-label" for="qrTitle">Title <span class="text-red-500">*</span></label>
                <input type="text" id="qrTitle" class="form-input" maxlength="191"
                       placeholder="e.g. Pump noise, west line" autocomplete="off">
            </div>

            <div>
                <label class="form-label" for="qrNote">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="qrNote" class="form-textarea" rows="3" maxlength="5000"
                          placeholder="What is happening in the clip?"></textarea>
            </div>

            @if (!empty($fixedScheduleId))
                <input type="hidden" id="qrSchedule" value="{{ $fixedScheduleId }}">
            @else
                <div>
                    <label class="form-label" for="qrSchedule">Connect to schedule</label>
                    <select id="qrSchedule" class="form-select">
                        @foreach ($allSchedules as $s)
                            <option value="{{ $s->id }}">{{ $s->title }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <span class="form-label">Where should it go?</span>
                @php
                    // The same rule the capture sheet follows: a worker who may
                    // only read the notebook is not offered it as a shelf.
                    $qrMayNote = \App\Support\WorkerContext::canWriteModule('notes');
                @endphp
                <div class="grid gap-2 mt-1.5">
                    @if ($qrMayNote)
                    <label class="qr-target is-on">
                        <input type="radio" name="qrTarget" value="note" checked>
                        <span>
                            <span class="block font-semibold text-gray-900">Save to notes</span>
                            <span class="block text-xs text-gray-500">Keep it in this schedule's notebook.</span>
                        </span>
                    </label>
                    @endif
                    <label class="qr-target {{ $qrMayNote ? '' : 'is-on' }}">
                        <input type="radio" name="qrTarget" value="gallery" @checked(! $qrMayNote)>
                        <span>
                            <span class="block font-semibold text-gray-900">Save to an album</span>
                            <span class="block text-xs text-gray-500">Put it in the Gallery, beside the photos.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div id="qrAlbumWrap" class="hidden">
                <label class="form-label" for="qrAlbum">Album</label>
                <select id="qrAlbum" class="form-select">
                    <option value="">➕ New album…</option>
                </select>
                <input type="text" id="qrAlbumTitle" class="form-input mt-2" maxlength="191"
                       placeholder="Name the new album" autocomplete="off">
            </div>
        </div>

        <div class="qr-foot">
            <button type="button" class="btn btn-ghost" data-qr-cancel>Cancel</button>
            <button type="button" class="btn btn-primary ml-auto" id="qrSave">Save clip</button>
        </div>
    </div>
</div>

{{-- The shared recorder writes whatever it captures into this input. --}}
<span id="qrHost" data-video-host class="hidden">
    <input type="file" class="js-video-file" accept="video/*">
    <button type="button" class="js-video-record"></button>
</span>

{{-- The uploading veil — Quick Capture's, borrowed shape for shape. A clip is
     tens of megabytes on a farm line, and a spinner that says nothing for a
     minute reads as a hang: the bar breathes until the first byte moves,
     counts while the bytes travel, and says "Finishing…" while the server is
     still compressing. A sibling of the modal, not a child: the card carries
     a transform, and a transform ancestor traps position:fixed. --}}
<div class="qr-busy" id="qrBusy" hidden role="alert" aria-busy="true">
    <div class="qr-busy-card">
        <p class="qr-busy-title">Uploading the video…</p>
        <div class="qr-busy-track"><div class="qr-busy-fill" id="qrBusyFill"></div></div>
        <p class="qr-busy-pct" id="qrBusyPct"></p>
    </div>
</div>

@push('head')
<style>
    .qr-modal { position: fixed; inset: 0; z-index: 90; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .qr-modal.hidden { display: none; }
    .qr-backdrop { position: absolute; inset: 0; background: rgb(0 0 0 / .6);
        opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .qr-modal.is-open .qr-backdrop { opacity: 1; }
    .qr-card { position: relative; width: 100%; max-width: 30rem; max-height: 92vh; display: flex; flex-direction: column;
        background: var(--color-white); border-radius: 1rem; overflow: hidden; box-shadow: var(--shadow-card-lg);
        transform: translateY(1.25rem) scale(.98); opacity: 0;
        transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .qr-modal.is-open .qr-card { transform: none; opacity: 1; }
    .qr-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        padding: .75rem 1rem; border-bottom: 1px solid var(--color-gray-100); }
    .qr-body { padding: 1rem; overflow-y: auto; }
    .qr-foot { display: flex; align-items: center; gap: .5rem; padding: .75rem 1rem;
        border-top: 1px solid var(--color-gray-100); }
    .qr-clip { position: relative; border-radius: .75rem; overflow: hidden; background: #000; }
    .qr-clip video { width: 100%; max-height: 40vh; display: block; background: #000; }
    .qr-size { position: absolute; top: .4rem; left: .4rem; padding: .1rem .45rem; border-radius: 999px;
        background: rgb(0 0 0 / .55); color: #fff; font-size: .62rem; font-weight: 700; }
    .qr-target { display: flex; align-items: flex-start; gap: .6rem; padding: .6rem .7rem; cursor: pointer;
        border: 1px solid var(--color-gray-200); border-radius: .7rem; background: var(--color-white);
        transition: border-color .28s cubic-bezier(.22,1,.36,1), background .28s cubic-bezier(.22,1,.36,1); }
    .qr-target.is-on { border-color: #4a7c2a; background: #f4f9ee; }
    .qr-target input { margin-top: .2rem; }
    html.dark .qr-card { background: #151b12; }
    html.dark .qr-head, html.dark .qr-foot { border-color: #2b3a1c; }
    html.dark .qr-target { background: #1c2416; border-color: #2b3a1c; }
    html.dark .qr-target.is-on { background: #24301a; border-color: #6ba33c; }
    /* The uploading veil — same bones as Quick Capture's .qc-busy, sat above
       the modal (90) and every ambient float. */
    .qr-busy { position: fixed; inset: 0; z-index: 460; display: flex; align-items: center; justify-content: center;
        background: rgb(0 0 0 / .55); animation: qrBusyIn .28s cubic-bezier(.22,1,.36,1) both; }
    .qr-busy[hidden] { display: none; }
    .qr-busy-card { width: min(20rem, calc(100vw - 3rem)); background: var(--color-white); border-radius: 1rem;
        padding: 1.4rem 1.2rem; text-align: center; box-shadow: 0 30px 60px -30px rgb(0 0 0 / .6); }
    .qr-busy-title { font-weight: 800; color: var(--color-gray-800); margin-bottom: .8rem; }
    .qr-busy-track { height: .55rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .qr-busy-fill { height: 100%; width: 0%; border-radius: 999px; background: #4a7c2a;
        transition: width .2s ease; }
    /* Before the first byte moves there is no honest percentage — the bar
       breathes instead of lying about one. */
    .qr-busy.is-vague .qr-busy-fill { width: 40% !important; animation: qrBusyVague 1.1s ease-in-out infinite alternate; }
    .qr-busy-pct { font-size: .74rem; font-weight: 700; color: var(--color-gray-500); margin-top: .5rem; min-height: 1em; }
    @keyframes qrBusyIn { from { opacity: 0; } }
    @keyframes qrBusyVague { from { margin-left: 0; } to { margin-left: 60%; } }
    html.dark .qr-busy-card { background: #151b12; }
    html.dark .qr-busy-title { color: #e8efe1; }
    html.dark .qr-busy-track { background: rgb(255 255 255 / .08); }
    @media (prefers-reduced-motion: reduce) {
        .qr-backdrop, .qr-card { transition: none; }
        .qr-busy { animation: none; }
        .qr-busy.is-vague .qr-busy-fill { animation: none; margin-left: 30%; }
    }
</style>
@endpush

@push('scripts')
<script>
(function quickRecord() {
    const modal = document.getElementById('quickRecordModal');
    if (!modal || window.openQuickRecord) return;
    const $ = (id) => document.getElementById(id);
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';
    const CLIP_URL = @json(route('quick-record.clip'));
    const ALBUMS_URL = @json(route('quick-capture.albums'));

    const host = $('qrHost');
    const fileInput = host.querySelector('.js-video-file');
    let clip = null;

    const fmtMB = (b) => (b / 1048576).toFixed(b > 10485760 ? 0 : 1) + ' MB';

    function showClip(file) {
        clip = file;
        const url = URL.createObjectURL(file);
        const v = $('qrPreview');
        v.src = url;
        v.load?.();
        $('qrSize').textContent = fmtMB(file.size);
    }

    /* The recorder hands its result to the host's file input. Watching that
       one place covers both ways in — recorded here, or chosen off the
       device — so nothing downstream needs to know which it was. */
    fileInput.addEventListener('change', () => {
        const f = fileInput.files && fileInput.files[0];
        if (!f) return;
        showClip(f);
        // A tick later: the recorder closes itself right after handing the
        // clip over, and its close resets the page scroll lock. Opening
        // first would have that reset land on top of ours.
        if (modal.classList.contains('hidden')) setTimeout(open, 0);
    });

    function open() {
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        window.registerOverlay?.('quickRecord', close);
    }
    function close() {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
        setTimeout(() => {
            modal.classList.add('hidden');
            const v = $('qrPreview');
            v.pause?.(); v.removeAttribute('src'); v.load?.();
        }, 260);
        clip = null;
        fileInput.value = '';
    }

    /* Recording starts before the form is asked for: nobody can title a clip
       that does not exist yet, and standing in front of the thing is the
       moment that matters. The sheet opens once there is something to name. */
    window.openQuickRecord = function () {
        clip = null;
        $('qrTitle').value = '';
        $('qrNote').value = '';
        host.querySelector('.js-video-record').click();
    };

    $('qrRerecord').addEventListener('click', () => host.querySelector('.js-video-record').click());
    $('qrUpload').addEventListener('click', () => fileInput.click());
    modal.addEventListener('click', (e) => { if (e.target.closest('[data-qr-cancel]')) close(); });

    // Destination: paint the choice, and only ask about albums when relevant.
    modal.addEventListener('change', async (e) => {
        if (e.target.name !== 'qrTarget') return;
        modal.querySelectorAll('.qr-target').forEach((l) => l.classList.toggle('is-on', l.contains(e.target)));
        const gallery = e.target.value === 'gallery';
        $('qrAlbumWrap').classList.toggle('hidden', !gallery);
        if (gallery) await loadAlbums();
    });

    async function loadAlbums() {
        const id = $('qrSchedule')?.value;
        if (!id) return;
        try {
            const res = await fetch(ALBUMS_URL + '?scheduleId=' + encodeURIComponent(id), {
                headers: { Accept: 'application/json' }, credentials: 'same-origin',
            });
            const json = await res.json();
            const sel = $('qrAlbum');
            sel.innerHTML = '<option value="">➕ New album…</option>'
                + (json.data?.albums || []).map((a) =>
                    '<option value="' + a.id + '">' + (window.escapeHtml ? window.escapeHtml(a.title) : a.title) + '</option>').join('');
        } catch (_) { /* the new-album path still works */ }
    }

    /* ---- the veil and the wire — Quick Capture's, name for name ------
     * fetch() cannot watch an upload: only XHR reports bytes as they leave,
     * and the bytes leaving are the whole wait here. Vague until the first
     * byte moves (no honest number exists yet), percent while they travel,
     * and "Finishing…" at 100% — all sent is not the same as saved, the
     * server is still compressing. */
    function busyShow() {
        const b = $('qrBusy');
        b.hidden = false; b.classList.add('is-vague');
        $('qrBusyFill').style.width = '0%';
        $('qrBusyPct').textContent = '';
    }
    function busyPct(p) {
        const b = $('qrBusy');
        b.classList.remove('is-vague');
        $('qrBusyFill').style.width = p + '%';
        $('qrBusyPct').textContent = p < 100 ? p + '%' : 'Finishing…';
    }
    function busyHide() { $('qrBusy').hidden = true; }
    function wire(url, fd) {
        return new Promise((resolve, reject) => {
            const x = new XMLHttpRequest();
            x.open('POST', url);
            x.withCredentials = true;
            x.setRequestHeader('X-CSRF-TOKEN', CSRF);
            x.setRequestHeader('Accept', 'application/json');
            x.upload.onprogress = (e) => { if (e.lengthComputable) busyPct(Math.round((e.loaded / e.total) * 100)); };
            x.onload = () => {
                let data = {};
                try { data = JSON.parse(x.responseText); } catch (_) { }
                if (x.status >= 200 && x.status < 300 && data.success) resolve(data);
                else reject(new Error(data.message || 'Could not save the clip.'));
            };
            x.onerror = () => reject(new Error('The connection dropped mid-upload. Nothing may have arrived — try again.'));
            x.send(fd);
        });
    }

    $('qrSave').addEventListener('click', async () => {
        const title = $('qrTitle').value.trim();
        if (!clip) { window.toast?.('Record or choose a clip first.', 'error'); return; }
        if (!title) { window.toast?.('Give the clip a title.', 'error'); $('qrTitle').focus(); return; }

        const btn = $('qrSave');
        btn.disabled = true;
        busyShow();

        const form = new FormData();
        form.append('clip', clip, clip.name || 'recording.webm');
        form.append('scheduleId', $('qrSchedule').value);
        form.append('title', title);
        form.append('note', $('qrNote').value.trim());
        const target = modal.querySelector('input[name=qrTarget]:checked')?.value
            || modal.querySelector('input[name=qrTarget]')?.value
            || 'gallery';
        form.append('target', target);
        if (target === 'gallery') {
            form.append('albumId', $('qrAlbum').value || '');
            form.append('albumTitle', $('qrAlbumTitle').value.trim());
        }

        try {
            const json = await wire(CLIP_URL, form);
            busyHide();
            window.toast?.(json.message);
            close();
        } catch (err) {
            busyHide();
            window.toast?.(err.message, 'error');
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('quickRecordBtn')?.addEventListener('click', window.openQuickRecord);
    document.getElementById('quickRecordFab')?.addEventListener('click', window.openQuickRecord);
})();
</script>
@endpush
