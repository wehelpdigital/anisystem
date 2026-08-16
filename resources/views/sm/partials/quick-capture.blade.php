{{--
    Quick Capture — snap one or more photos, add an optional rich-text note,
    pick a schedule, then either save them to that schedule's notebook or hand
    the first photo to the AI Technician.
    Params:
      $allSchedules (id, title) — for the schedule picker (dashboard use).
      $fixedScheduleId (optional) — when included inside a schedule, the target
      is known so the picker is replaced by a hidden field.
--}}
@php
    $fixedScheduleId = $fixedScheduleId ?? null;
    $allSchedules = $allSchedules ?? collect();
@endphp
@push('head')
<style>
    /* z-index above the AI float (60) and team float (61-62): Quick Capture is
       a modal you summoned, so nothing ambient may sit on top of it — at equal
       z the float painted over it purely by coming later in the DOM. */
    .qc-overlay { position: fixed; inset: 0; z-index: 130; background: rgba(17,24,39,.55); display: flex; align-items: flex-end; justify-content: center; padding: 0;
        opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    /* Open/close move on transitions from an .is-open class, so the exit is
       the entrance run backwards; `hidden` stays the resting state and is put
       back only after the exit has played (see close()). */
    .qc-overlay.is-open { opacity: 1; }
    /* Two-class selector beats the unlayered `.qc-overlay { display:flex }`,
       so the `hidden` utility actually hides the modal. */
    .qc-overlay.hidden { display: none !important; }
    @media (min-width: 640px) { .qc-overlay { align-items: center; padding: 1rem; } }
    /* Use the palette variables (they flip under html.dark) instead of fixed
       hex, so the modal reads correctly in both light and dark. */
    .qc-modal { background: var(--color-white); color: var(--color-gray-900); width: 100%; max-width: 34rem; max-height: 92vh; display: flex; flex-direction: column;
        border-radius: 1rem 1rem 0 0; overflow: hidden;
        transform: translateY(100%);
        transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .qc-overlay.is-open .qc-modal { transform: none; }
    /* Phones: the whole screen, not a bottom sheet — a capture flow with the
       board peeking around it read as two competing screens. The dimmed
       overlay stays behind it for the edges the modal does not reach. It
       slides up from the floor there; on a desktop it pops in place. */
    @media (max-width: 639px) { .qc-modal { height: 100dvh; max-height: 100dvh; border-radius: 0; } }
    @media (min-width: 640px) {
        .qc-modal { border-radius: 1rem; transform: translateY(10px) scale(.97); opacity: 0; }
        .qc-overlay.is-open .qc-modal { transform: none; opacity: 1; }
    }
    /* Each step eases in as it takes over; the swap itself is a display
       toggle, so only the incoming one moves. */
    @keyframes qcStepIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }
    .qc-modal > [data-qc-step].qc-step-in { animation: qcStepIn .28s cubic-bezier(.22,1,.36,1); }
    /* A fresh photo lands with a small pop; only the new ones replay. */
    @keyframes qcThumbIn { from { opacity: 0; transform: scale(.92); } to { opacity: 1; transform: none; } }
    .qc-thumb.qc-thumb-in { animation: qcThumbIn .28s cubic-bezier(.22,1,.36,1); }
    @media (prefers-reduced-motion: reduce) {
        .qc-overlay, .qc-modal, .qc-camera-wrap video { transition: none; }
        .qc-modal > [data-qc-step].qc-step-in, .qc-thumb.qc-thumb-in { animation: none; }
    }
    .qc-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--color-gray-200); flex: none; }
    /* A step is head + body + foot inside a column that is exactly as tall as
       the screen. Without min-height:0 the body refuses to shrink below its
       content, which pushed Confirm off the bottom with nothing to scroll —
       the details step had grown a title and an album picker and no longer
       fitted. */
    .qc-modal > [data-qc-step] { display: flex; flex-direction: column; min-height: 0; flex: 1 1 auto; }
    .qc-modal > [data-qc-step].hidden { display: none; }
    .qc-body { padding: 1.25rem; overflow-y: auto; overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch; flex: 1 1 auto; min-height: 0; }
    .qc-foot { display: flex; gap: .5rem; padding: 1rem 1.25rem; border-top: 1px solid var(--color-gray-200);
        flex: none; background: var(--color-white); padding-bottom: calc(1rem + env(safe-area-inset-bottom)); }
    html.dark .qc-foot { background: var(--color-white); }
    .qc-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; }
    .qc-thumb { position: relative; aspect-ratio: 1; border-radius: .6rem; overflow: hidden; background: var(--color-gray-100); }
    .qc-thumb img, .qc-thumb video { width: 100%; height: 100%; object-fit: cover; }
    /* A clip and a photo are the same grey square until one of them moves, so
       the tile says which it is. */
    .qc-kind { position: absolute; left: .25rem; bottom: .25rem; padding: 0 .3rem; border-radius: .3rem;
        background: rgba(17,24,39,.72); color: #fff; font-size: .625rem; font-weight: 700; letter-spacing: .03em; }
    .qc-thumb button { position: absolute; top: .25rem; right: .25rem; width: 1.5rem; height: 1.5rem; border-radius: 999px;
        background: rgba(17,24,39,.7); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .875rem; line-height: 1; }
    .qc-add { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .35rem; aspect-ratio: 1;
        border: 1.5px dashed var(--color-gray-300); border-radius: .6rem; color: var(--color-gray-500); font-size: .75rem; font-weight: 600; cursor: pointer; background: var(--color-gray-50); }
    .qc-add:hover { border-color: var(--color-gray-400); color: var(--color-gray-600); }
    .qc-target { display: flex; align-items: flex-start; gap: .6rem; padding: .75rem; border: 1.5px solid var(--color-gray-200); border-radius: .7rem; cursor: pointer; }
    .qc-target.is-on { border-color: var(--color-brand-600, #4a7c2a); background: rgba(74,124,42,.12); }
    /* A destination that cannot take what was captured stays on screen, greyed:
       "where did Save to notes go?" is a worse question than a row you can see
       is unavailable and a line saying why. */
    .qc-target.is-off { opacity: .5; cursor: not-allowed; }
    .qc-target input { margin-top: .2rem; }
    /* One row per captured thing: a thumbnail to recognise it by, a name, a
       description. Deliberately cramped — ten of these sit under everything
       else the details step already asks for. */
    .qc-item { display: flex; gap: .6rem; align-items: flex-start; padding: .55rem 0; border-top: 1px solid var(--color-gray-100); }
    .qc-item:first-child { border-top: 0; padding-top: 0; }
    .qc-item-shot { position: relative; width: 3rem; height: 3rem; flex: none; border-radius: .5rem; overflow: hidden; background: var(--color-gray-100); }
    .qc-item-shot img, .qc-item-shot video { width: 100%; height: 100%; object-fit: cover; display: block; }
    .qc-item-fields { flex: 1 1 auto; min-width: 0; display: grid; gap: .3rem; }
    .qc-item-fields .form-input { padding-top: .4rem; padding-bottom: .4rem; font-size: .8125rem; }
    /* Height and look come from the shared rules in app.css. */
    .qc-editor-wrap .ql-toolbar { border-top-left-radius: .6rem; border-top-right-radius: .6rem; }
    /* Live camera */
    .qc-camera-wrap { background: #000; aspect-ratio: 3 / 4; max-height: 70vh; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    /* The stream's first frames arrive as a black flash; the video fades up
       once it actually has a picture (see the loadeddata handler). */
    .qc-camera-wrap video { width: 100%; height: 100%; object-fit: cover;
        opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .qc-camera-wrap video.is-live { opacity: 1; }
    .qc-shutter { width: 3.5rem; height: 3.5rem; border-radius: 999px; border: 3px solid var(--color-gray-300); background: transparent; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
    .qc-shutter span { width: 2.6rem; height: 2.6rem; border-radius: 999px; background: var(--color-brand-600, #4a7c2a); transition: transform .1s ease; }
    .qc-shutter:active span { transform: scale(.88); }
</style>
@endpush

<div id="quickCaptureModal" class="qc-overlay hidden" aria-hidden="true">
    <div class="qc-modal" role="dialog" aria-modal="true" aria-label="Quick capture">
        <div class="qc-head">
            <h3 class="font-bold text-gray-900" id="qcTitle">Quick Capture</h3>
            <button type="button" id="qcClose" class="btn-ghost p-2 rounded-full" aria-label="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        {{-- STEP 0 — live camera (getUserMedia; secure origins only) --}}
        <div data-qc-step="camera" class="hidden">
            <div class="qc-camera-wrap">
                <video id="qcVideo" autoplay playsinline muted></video>
            </div>
            <div class="qc-foot">
                <button type="button" class="btn btn-ghost" data-qc-cancel>Cancel</button>
                <button type="button" id="qcShutter" class="qc-shutter mx-auto" aria-label="Take photo"><span></span></button>
                <button type="button" id="qcUseFile" class="btn btn-ghost ml-auto" title="Choose photos or clips already on this device">Upload</button>
            </div>
        </div>

        {{-- STEP 1 — capture --}}
        <div data-qc-step="capture">
            <div class="qc-body">
                <p class="text-sm text-gray-600 mb-3">Snap your crop, a pest, the soil — anything worth remembering. Add as many as you like; Upload takes video clips too.</p>
                <div class="qc-grid" id="qcPreviews">
                    <button type="button" class="qc-add" id="qcAddPhoto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.66-.9l.82-1.2A2 2 0 0110.07 4h3.86a2 2 0 011.66.9l.82 1.2a2 2 0 001.66.9H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Take photo
                    </button>
                    {{-- Photos worth keeping are often already on the phone — and
                         so is the clip taken beside them, which belongs in the
                         same album rather than a second trip through Quick
                         Record. --}}
                    <button type="button" class="qc-add" id="qcPickPhoto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 15l4-4 4 4 3-3 5 5"/><circle cx="9" cy="8.5" r="1.3"/></svg>
                        Upload
                    </button>
                </div>
                {{-- Two inputs, because one attribute decides everything: with
                     `capture` a phone opens its camera and nothing else, which
                     is right for "take another photo" and exactly wrong for
                     "Upload". --}}
                <input type="file" id="qcFile" accept="image/*" capture="environment" class="hidden">
                <input type="file" id="qcPick" accept="image/*,video/*" multiple class="hidden">
            </div>
            <div class="qc-foot">
                <button type="button" class="btn btn-ghost" data-qc-cancel>Cancel</button>
                <button type="button" class="btn btn-primary ml-auto" id="qcContinue" disabled>Continue</button>
            </div>
        </div>

        {{-- STEP 2 — details --}}
        <div data-qc-step="details" class="hidden">
            <div class="qc-body space-y-4">
                {{-- A capture deserves a name of its own. Without one every
                     record read "Quick capture — Aug 13, 2026 4:02 PM", which
                     tells you when you were there and nothing about what you
                     saw. --}}
                <div>
                    <label class="form-label" for="qcNoteTitle">Title</label>
                    <input type="text" id="qcNoteTitle" class="form-input" maxlength="191"
                           placeholder="e.g. Flooded corner, east lot" autocomplete="off">
                </div>
                <div>
                    <label class="form-label">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                    <div class="qc-editor-wrap"><div id="qcEditor"></div></div>
                </div>
                @if (!empty($fixedScheduleId))
                    {{-- Inside a schedule: the target is known, no picker needed. --}}
                    <input type="hidden" id="qcSchedule" value="{{ $fixedScheduleId }}">
                @else
                    <div>
                        <label class="form-label" for="qcSchedule">Connect to schedule</label>
                        <select id="qcSchedule" class="form-select">
                            @foreach ($allSchedules as $s)
                                <option value="{{ $s->id }}">{{ $s->title }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <span class="form-label">What should we do with it?</span>
                    <div class="grid gap-2 mt-1.5">
                        <label class="qc-target is-on" data-qc-target-row>
                            <input type="radio" name="qcTarget" value="note" checked>
                            <span>
                                <span class="block font-semibold text-gray-900">Save to notes</span>
                                <span class="block text-xs text-gray-500">Keep the photos in this schedule's notebook.</span>
                            </span>
                        </label>
                        <label class="qc-target" data-qc-target-row>
                            <input type="radio" name="qcTarget" value="gallery">
                            <span>
                                <span class="block font-semibold text-gray-900">Save to gallery</span>
                                <span class="block text-xs text-gray-500">An album of their own — photos, clips, or both, each one named.</span>
                            </span>
                        </label>
                        <label class="qc-target" data-qc-target-row>
                            <input type="radio" name="qcTarget" value="ai">
                            <span>
                                <span class="block font-semibold text-gray-900">Ask the AI Technician</span>
                                <span class="block text-xs text-gray-500">Get advice on the first photo (uses AI Credits).</span>
                            </span>
                        </label>
                    </div>
                    <p id="qcClipHint" class="hidden text-xs text-gray-500 mt-1.5">A clip can only go to an album — notes and the AI Technician read photos.</p>
                </div>
                {{-- Only asked once the gallery is the destination. --}}
                <div id="qcAlbumWrap" class="hidden">
                    <label class="form-label" for="qcAlbum">Album</label>
                    {{-- A capture is a moment and deserves an album of its own,
                         so a new one is what is offered first; the existing
                         albums are still one tap down the list. --}}
                    <select id="qcAlbum" class="form-select">
                        <option value="">➕ New album…</option>
                    </select>
                    <div id="qcNewAlbum" class="mt-2 space-y-2">
                        <input type="text" id="qcAlbumTitle" class="form-input" maxlength="191"
                               placeholder="Name the new album" autocomplete="off">
                        <textarea id="qcAlbumDesc" class="form-textarea" rows="2" maxlength="2000"
                                  placeholder="What is this album about? (optional)"></textarea>
                    </div>
                </div>
                {{-- A note tells one story about its photos, but an album is a
                     shelf: every picture and clip on it wants its own label. --}}
                <div id="qcItemsWrap" class="hidden">
                    <span class="form-label">Name each item <span class="text-gray-400 font-normal">(optional)</span></span>
                    <div id="qcItems"></div>
                </div>
            </div>
            <div class="qc-foot">
                <button type="button" class="btn btn-ghost" data-qc-back>Back</button>
                <button type="button" class="btn btn-primary ml-auto" id="qcConfirm">Confirm</button>
            </div>
        </div>

        {{-- STEP 3 — result --}}
        <div data-qc-step="result" class="hidden">
            <div class="qc-body">
                <div id="qcResult" class="text-sm text-gray-700"></div>
            </div>
            <div class="qc-foot">
                <a id="qcResultLink" href="#" class="btn btn-white hidden">Open</a>
                <button type="button" class="btn btn-primary ml-auto" data-qc-cancel>Done</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('quickCaptureModal');
    if (!modal) return;
    const $ = (id) => document.getElementById(id);
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';
    const NOTES_URL = @json(route('quick-capture.notes'));
    const ALBUMS_URL = @json(route('quick-capture.albums'));
    const GALLERY_URL = @json(route('quick-capture.gallery'));
    const AI_PHOTO_URL = @json(route('ai.photo'));
    const AI_ASK_URL = @json(route('ai.ask'));

    // One entry per captured thing, in the order it was captured:
    // { file, kind: 'image'|'video', url, title, desc }. The names live here
    // rather than on the inputs so they survive a trip back to the camera.
    let items = [];
    let quill = null;
    let stream = null;       // live camera MediaStream, when open
    let albumNamed = false;  // the album name was typed, not inherited
    // The album was chosen for them by a clip, not by them. It has to be
    // remembered: a destination picked on their behalf may be taken back when
    // the clip that forced it goes, and one picked by hand may not.
    let targetForced = false;
    // Photos are cheap and clips are not; both ceilings match what the
    // controller will accept, so the refusal happens before the upload.
    const MAX_PHOTOS = 10;
    const MAX_CLIPS = 4;
    const VIDEO_NAME = /\.(mp4|mov|webm|mkv|m4v|3gp)$/i;

    const countKind = (kind) => items.filter((it) => it.kind === kind).length;

    /* A picked file, turned into an entry. Videos are recognised by their
       type where the browser reports one and by name where it does not —
       some Android pickers hand over an empty type for a .mkv. */
    function addFiles(picked) {
        picked.forEach((file) => {
            const kind = /^video\//.test(file.type) || (!file.type && VIDEO_NAME.test(file.name))
                ? 'video' : 'image';
            if (kind === 'video' && countKind('video') >= MAX_CLIPS) {
                toast('Up to ' + MAX_CLIPS + ' clips in one capture.', 'error');
                return;
            }
            if (kind === 'image' && countKind('image') >= MAX_PHOTOS) {
                toast('Up to ' + MAX_PHOTOS + ' photos in one capture.', 'error');
                return;
            }
            items.push({ file, kind, url: URL.createObjectURL(file), title: '', desc: '' });
        });
    }

    /* The tile for an entry — the same element the preview grid and the
       naming row both need. A video has no poster of its own, so it is asked
       for a frame a tenth of a second in; without that the tile is black. */
    function shotFor(item) {
        if (item.kind === 'video') {
            const v = document.createElement('video');
            v.src = item.url + '#t=0.1';
            v.muted = true; v.playsInline = true; v.preload = 'metadata';
            return v;
        }
        const img = document.createElement('img');
        img.src = item.url; img.alt = 'Captured photo';
        return img;
    }

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
            quill = new Quill('#qcEditor', {
                theme: 'snow', placeholder: 'What did you notice?',
                modules: { toolbar: window.SM_RICH_TOOLBAR },
            });
            window.smQuillTouch?.(quill);
        }
        return quill;
    }

    function showStep(step) {
        modal.querySelectorAll('[data-qc-step]').forEach((el) => {
            const show = el.getAttribute('data-qc-step') === step;
            const wasShowing = !el.classList.contains('hidden');
            el.classList.toggle('hidden', !show);
            // Only a step that just came on screen eases in — re-asserting
            // the current one must not replay its entrance.
            if (show && !wasShowing) {
                el.classList.remove('qc-step-in');
                void el.offsetWidth;
                el.classList.add('qc-step-in');
            }
        });
    }

    /**
     * A phone has a better camera app than this one.
     *
     * The in-page camera is a video stream, and a still pulled off a preview
     * stream is soft — no autofocus lock, no HDR, no scene processing, and
     * often a fraction of the sensor's resolution. Every one of those belongs
     * to the phone's own camera software, and `capture` hands the job to it:
     * the picture that comes back is the full-quality one it would have saved
     * to the gallery. Desktops have no camera app, so they keep the live
     * preview.
     */
    const hasCameraApp = () => window.matchMedia
        ? (window.matchMedia('(pointer: coarse)').matches && navigator.maxTouchPoints > 0)
        : /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);

    async function startCapture() {
        items.forEach((it) => URL.revokeObjectURL(it.url));
        items = [];
        albumNamed = false;
        targetForced = false;
        renderPreviews();
        if (quill) quill.setText('');
        // Every field, not just the photos. A second capture in one session
        // inherited the first one's title, album name and description — and
        // since the picker opens on "New album", it quietly made a second
        // album out of them and put this capture in there.
        $('qcNoteTitle').value = '';
        $('qcAlbumTitle').value = '';
        $('qcAlbumDesc').value = '';
        $('qcAlbum').value = '';
        $('qcItems').innerHTML = '';
        // Last run's answer, still on the result step behind us.
        $('qcResult').innerHTML = '';
        const link = $('qcResultLink');
        link.classList.add('hidden'); link.href = '#'; link.textContent = 'Open';
        $('qcTitle').textContent = 'Quick Capture';
        // Nothing has been captured yet, so every destination is open again —
        // last run's clip may have left two of them disabled.
        syncDestinations();
        pickTarget('note');
        showStep('capture');
        if (hasCameraApp()) { $('qcFile').click(); return; }
        const camOk = await openCamera();
        if (!camOk) $('qcFile').click();
    }

    async function openCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return false;
        try {
            // Ask for the best the device will give: the default is a
            // 640x480 preview on plenty of hardware, which is where "the
            // photos look blurry" came from.
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 3840 },
                    height: { ideal: 2160 },
                },
                audio: false,
            });
            const v = $('qcVideo');
            v.srcObject = stream;
            // Fade the preview up from the black once it has a real frame.
            v.classList.remove('is-live');
            v.addEventListener('loadeddata', () => v.classList.add('is-live'), { once: true });
            showStep('camera');
            openModal();
            return true;
        } catch (_) {
            stream = null;
            return false;   // denied / no camera / insecure origin
        }
    }

    function stopCamera() {
        if (stream) { stream.getTracks().forEach((t) => t.stop()); stream = null; }
        const v = $('qcVideo');
        if (v) { v.srcObject = null; v.classList.remove('is-live'); }
    }

    function snapPhoto() {
        const v = $('qcVideo');
        if (!v || !v.videoWidth) return;
        const canvas = document.createElement('canvas');
        canvas.width = v.videoWidth;
        canvas.height = v.videoHeight;
        canvas.getContext('2d').drawImage(v, 0, 0);
        canvas.toBlob((blob) => {
            if (!blob) return;
            addFiles([new File([blob], 'capture-' + items.length + '.jpg', { type: 'image/jpeg' })]);
            stopCamera();
            renderPreviews();
            showStep('capture');
        }, 'image/jpeg', 0.95);
    }

    /* Open and close play the same transition in opposite directions:
       `hidden` comes off, a frame later `is-open` goes on; closing removes
       `is-open` and puts `hidden` back only after the exit has played, the
       same handoff the shared sheets use. The timer guard keeps a quick
       reopen from being swallowed by a close still on its way out. */
    let hideTimer = null;
    function openModal() {
        if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
        if (!modal.classList.contains('hidden') && modal.classList.contains('is-open')) return;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => modal.classList.add('is-open'));
        document.body.style.overflow = 'hidden';
    }
    function close() {
        stopCamera();
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        const finish = () => { modal.classList.add('hidden'); hideTimer = null; };
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches
            || document.documentElement.classList.contains('sm-still')) { finish(); return; }
        if (hideTimer) clearTimeout(hideTimer);
        hideTimer = setTimeout(finish, 300);
    }

    // How many thumbs were already on screen: the grid rebuilds wholesale,
    // and without this every existing photo replayed its entrance on each add.
    let seenThumbs = 0;
    function renderPreviews() {
        const grid = $('qcPreviews');
        grid.querySelectorAll('[data-qc-thumb]').forEach((n) => n.remove());
        const addBtn = $('qcAddPhoto');
        items.forEach((item, i) => {
            const div = document.createElement('div');
            div.className = 'qc-thumb' + (i >= seenThumbs ? ' qc-thumb-in' : '');
            div.setAttribute('data-qc-thumb', i);
            div.appendChild(shotFor(item));
            if (item.kind === 'video') {
                const kind = document.createElement('span');
                kind.className = 'qc-kind'; kind.textContent = 'CLIP';
                div.appendChild(kind);
            }
            const rm = document.createElement('button');
            rm.type = 'button'; rm.innerHTML = '&times;';
            rm.setAttribute('aria-label', item.kind === 'video' ? 'Remove clip' : 'Remove photo');
            rm.addEventListener('click', () => {
                URL.revokeObjectURL(item.url);
                items.splice(i, 1);
                renderPreviews();
            });
            div.appendChild(rm);
            grid.insertBefore(div, addBtn);
        });
        seenThumbs = items.length;
        $('qcContinue').disabled = items.length === 0;
    }

    /* ---- entry points ---- */
    $('quickCaptureBtn')?.addEventListener('click', startCapture);
    $('quickCaptureFab')?.addEventListener('click', startCapture);
    $('qcClose').addEventListener('click', close);
    modal.querySelectorAll('[data-qc-cancel]').forEach((b) => b.addEventListener('click', close));
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
    // Escape closes it like every other overlay in the app.
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });

    /* ---- camera step ---- */
    $('qcShutter')?.addEventListener('click', snapPhoto);
    $('qcUseFile')?.addEventListener('click', () => { stopCamera(); $('qcPick').click(); });

    /* ---- capture step ---- */
    $('qcAddPhoto').addEventListener('click', async () => {
        if (hasCameraApp()) { $('qcFile').click(); return; }   // the phone's own camera
        const camOk = await openCamera();   // a desktop webcam, previewed here
        if (!camOk) $('qcFile').click();
    });
    $('qcPickPhoto')?.addEventListener('click', () => { stopCamera(); $('qcPick').click(); });
    const tookPhotos = (e) => {
        const picked = Array.from(e.target.files || []);
        if (picked.length) {
            addFiles(picked);
            renderPreviews();
            showStep('capture');   // land on the review sheet
            openModal();           // first shot brings up the modal
        }
        e.target.value = '';   // let the same shot be re-taken
    };
    $('qcFile').addEventListener('change', tookPhotos);
    $('qcPick').addEventListener('change', tookPhotos);
    $('qcContinue').addEventListener('click', async () => {
        syncDestinations();
        showStep('details');
        try { await ensureQuill(); } catch (_) { /* editor optional */ }
    });

    /* ---- details step ---- */
    modal.querySelectorAll('[data-qc-back]').forEach((b) => b.addEventListener('click', () => showStep('capture')));
    modal.querySelectorAll('[data-qc-target-row]').forEach((row) => {
        row.addEventListener('click', () => {
            if (row.classList.contains('is-off')) return;   // the radio is disabled; the paint must agree
            // Chosen by hand, so nothing may quietly move it back later.
            targetForced = false;
            pickTarget(row.querySelector('input[name=qcTarget]').value);
        });
    });

    /* Choose a destination and make the paint agree. The rows are labels
       around radios, so checking one without repainting leaves two of them
       looking chosen. */
    function pickTarget(value) {
        const input = modal.querySelector('input[name=qcTarget][value=' + value + ']');
        if (!input || input.disabled) return;
        input.checked = true;
        modal.querySelectorAll('[data-qc-target-row]').forEach((r) => {
            r.classList.toggle('is-on', r.contains(input));
        });
        syncTarget();
    }

    /**
     * Which destinations this capture can actually reach.
     *
     * A note keeps photos and the AI Technician reads one, so a set with a
     * clip in it has exactly one home. Rather than let someone pick a
     * destination that will refuse them at the end, the album is chosen for
     * them here and the others are greyed with the reason underneath.
     */
    function syncDestinations() {
        const hasClip = countKind('video') > 0;
        modal.querySelectorAll('[data-qc-target-row]').forEach((row) => {
            const input = row.querySelector('input[name=qcTarget]');
            const off = hasClip && input.value !== 'gallery';
            row.classList.toggle('is-off', off);
            input.disabled = off;
            if (off && input.checked) input.checked = false;
        });
        $('qcClipHint').classList.toggle('hidden', !hasClip);
        if (hasClip && !modal.querySelector('input[name=qcTarget]:checked')) {
            targetForced = true;   // the album was chosen for them, not by them
            pickTarget('gallery');
            return;
        }
        // The clips are gone and nobody ever asked for the album: put the
        // destination back. Without this the album stuck, and every later
        // photo-only capture in the session opened on the wrong one.
        if (!hasClip && targetForced) {
            targetForced = false;
            pickTarget('note');
            return;
        }
        syncTarget();
    }

    function noteHtml() { return quill ? quill.root.innerHTML : ''; }
    function noteText() { return quill ? quill.getText().trim() : ''; }

    $('qcConfirm').addEventListener('click', async () => {
        if (!items.length) { toast('Capture a photo first.', 'error'); showStep('capture'); return; }
        const scheduleId = $('qcSchedule').value;
        const target = modal.querySelector('input[name=qcTarget]:checked')?.value || 'note';
        const btn = $('qcConfirm');
        btn.disabled = true; const orig = btn.textContent; btn.textContent = 'Working…';
        try {
            if (target === 'note') {
                await saveNotes(scheduleId);
            } else if (target === 'gallery') {
                await saveGallery(scheduleId);
            } else {
                await askAi(scheduleId);
            }
        } catch (err) {
            toast(err.message || 'Something went wrong.', 'error');
        } finally {
            btn.disabled = false; btn.textContent = orig;
        }
    });

    /* ---- Gallery: the album picker, filled from the chosen schedule ---- */
    function currentTarget() {
        return modal.querySelector('input[name=qcTarget]:checked')?.value || 'note';
    }

    function syncTarget() {
        const gallery = currentTarget() === 'gallery';
        $('qcAlbumWrap').classList.toggle('hidden', !gallery);
        $('qcItemsWrap').classList.toggle('hidden', !gallery);
        if (gallery) { loadAlbums(); syncAlbumField(); renderItemRows(); }
    }

    // The new-album fields only matter when no existing album is chosen.
    function syncAlbumField() {
        const fresh = !$('qcAlbum').value;
        $('qcNewAlbum').classList.toggle('hidden', !fresh);
        // A capture that has been given a title has already named its album;
        // typing in the album box takes that over for good.
        if (fresh && !albumNamed) $('qcAlbumTitle').value = $('qcNoteTitle').value.trim();
    }

    $('qcAlbumTitle').addEventListener('input', () => { albumNamed = true; });
    $('qcNoteTitle').addEventListener('input', () => {
        if (!albumNamed) $('qcAlbumTitle').value = $('qcNoteTitle').value.trim();
    });

    /**
     * A row per captured item: what it looks like, what it is called, what it
     * is about. Rebuilt whenever the album becomes the destination — the
     * typed names live on the items themselves, so nothing is lost by it.
     */
    function renderItemRows() {
        const wrap = $('qcItems');
        wrap.innerHTML = '';
        items.forEach((item, i) => {
            const row = document.createElement('div');
            row.className = 'qc-item';

            const shot = document.createElement('div');
            shot.className = 'qc-item-shot';
            shot.appendChild(shotFor(item));
            if (item.kind === 'video') {
                const kind = document.createElement('span');
                kind.className = 'qc-kind'; kind.textContent = 'CLIP';
                shot.appendChild(kind);
            }

            const fields = document.createElement('div');
            fields.className = 'qc-item-fields';
            const noun = item.kind === 'video' ? 'Clip' : 'Photo';

            const title = document.createElement('input');
            title.type = 'text'; title.className = 'form-input'; title.maxLength = 191;
            title.autocomplete = 'off';
            title.placeholder = noun + ' ' + (i + 1) + ' — name it';
            title.setAttribute('aria-label', noun + ' ' + (i + 1) + ' title');
            title.value = item.title;
            title.addEventListener('input', () => { item.title = title.value; });

            // One line, not a textarea: ten stacked boxes turned the details
            // step into a page nobody scrolled to the bottom of.
            const desc = document.createElement('input');
            desc.type = 'text'; desc.className = 'form-input'; desc.maxLength = 2000;
            desc.autocomplete = 'off';
            desc.placeholder = 'Description (optional)';
            desc.setAttribute('aria-label', noun + ' ' + (i + 1) + ' description');
            desc.value = item.desc;
            desc.addEventListener('input', () => { item.desc = desc.value; });

            fields.appendChild(title); fields.appendChild(desc);
            row.appendChild(shot); row.appendChild(fields);
            wrap.appendChild(row);
        });
    }

    let albumsFor = null;   // schedule id the picker was last filled for
    async function loadAlbums() {
        const scheduleId = $('qcSchedule').value;
        if (albumsFor === scheduleId) return;
        albumsFor = scheduleId;
        const sel = $('qcAlbum');
        try {
            const res = await fetch(ALBUMS_URL + '?scheduleId=' + encodeURIComponent(scheduleId), {
                headers: { Accept: 'application/json' },
            });
            const data = await res.json().catch(() => ({}));
            const albums = data?.data?.albums || [];
            sel.innerHTML = '<option value="">➕ New album…</option>'
                + albums.map((a) => `<option value="${a.id}">${escapeHtml(a.title)}</option>`).join('');
            // Left on "New album" on purpose: a capture is its own occasion,
            // and dropping it into whichever album happened to be newest is
            // how photos of three different problems ended up in one pile.
            sel.value = '';
        } catch (_) {
            albumsFor = null;   // a failed load must not stick
        }
        syncAlbumField();
    }

    $('qcAlbum').addEventListener('change', syncAlbumField);
    $('qcSchedule')?.addEventListener('change', () => { albumsFor = null; if (currentTarget() === 'gallery') loadAlbums(); });

    async function saveGallery(scheduleId) {
        const fd = new FormData();
        fd.append('scheduleId', scheduleId);
        const albumId = $('qcAlbum').value;
        if (albumId) {
            fd.append('albumId', albumId);
        } else {
            if ($('qcAlbumTitle').value.trim()) fd.append('albumTitle', $('qcAlbumTitle').value.trim());
            if ($('qcAlbumDesc').value.trim()) fd.append('albumDescription', $('qcAlbumDesc').value.trim());
        }
        if ($('qcNoteTitle').value.trim()) fd.append('title', $('qcNoteTitle').value.trim());
        const gHtml = noteHtml();
        if (gHtml && gHtml !== '<p><br></p>') fd.append('note', gHtml);
        // The index in the field name is the pairing. Photos and clips go in
        // separate buckets because the server checks and stores them
        // differently, but the names are one list, so images[2] and titles[2]
        // are the same thing.
        items.forEach((item, i) => {
            fd.append((item.kind === 'video' ? 'clips[' : 'images[') + i + ']', item.file);
            if (item.title.trim()) fd.append('titles[' + i + ']', item.title.trim());
            if (item.desc.trim()) fd.append('descriptions[' + i + ']', item.desc.trim());
        });
        const res = await fetch(GALLERY_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: fd,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) throw new Error(data.message || 'Could not save.');
        // Some of it saved and some of it did not. A green tick and a success
        // toast over "Could not process the video" is how a lost clip goes
        // unnoticed until somebody goes looking for it, so a partial save
        // wears a warning and the toast says the part that failed.
        const partial = !!data.partial;
        const mark = partial
            ? '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 4.3L2.6 18a2 2 0 001.7 3h15.4a2 2 0 001.7-3L13.7 4.3a2 2 0 00-3.4 0z"/></svg>'
            : '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
        $('qcResult').innerHTML = `<div class="flex items-start gap-2 ${partial ? 'text-amber-700' : 'text-brand-700'} font-semibold mb-1">
            ${mark}
            <span>${escapeHtml(data.message)}</span></div><p class="text-gray-500 text-sm">${partial
                ? 'What did not save is still on your device — try that one on its own.'
                : 'You can move or rename them anytime in the Gallery.'}</p>`;
        const link = $('qcResultLink');
        link.href = data.galleryUrl; link.classList.remove('hidden'); link.textContent = 'Open gallery';
        $('qcTitle').textContent = partial ? 'Partly saved' : 'Saved';
        showStep('result');
        toast(partial ? (data.trouble || data.message) : data.message, partial ? 'error' : 'success');
    }

    async function saveNotes(scheduleId) {
        const fd = new FormData();
        fd.append('scheduleId', scheduleId);
        if ($('qcNoteTitle').value.trim()) fd.append('title', $('qcNoteTitle').value.trim());
        const html = noteHtml();
        if (html && html !== '<p><br></p>') fd.append('note', html);
        // Photos only — a note takes pictures, which is why a set with a clip
        // in it never gets offered this destination.
        items.filter((it) => it.kind === 'image').forEach((it) => fd.append('images[]', it.file));
        const res = await fetch(NOTES_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: fd,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) throw new Error(data.message || 'Could not save.');
        $('qcResult').innerHTML = `<div class="flex items-center gap-2 text-brand-700 font-semibold mb-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            ${escapeHtml(data.message)}</div><p class="text-gray-500 text-sm">Find it anytime in this schedule's Notes module.</p>`;
        const link = $('qcResultLink');
        link.href = data.notesUrl; link.classList.remove('hidden'); link.textContent = 'Open notes';
        $('qcTitle').textContent = 'Saved';
        showStep('result');
        toast(data.message);
    }

    async function askAi(scheduleId) {
        // Upload the first photo, then ask the AI about it.
        const photos = items.filter((it) => it.kind === 'image');
        if (!photos.length) throw new Error('The AI Technician reads photos — capture one first.');
        const fd = new FormData();
        fd.append('image', photos[0].file);
        fd.append('scheduleId', scheduleId);
        const up = await fetch(AI_PHOTO_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: fd,
        });
        const upData = await up.json().catch(() => ({}));
        if (!up.ok || !upData.success) throw new Error(upData.message || 'Photo upload failed.');
        const imagePath = upData.data?.path || upData.data?.imagePath || upData.path;

        const message = noteText() || 'Please take a look at this photo of my crop and tell me what you notice and what I should do.';
        const ask = await fetch(AI_ASK_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ message, imagePath, scheduleId }),
        });
        const askData = await ask.json().catch(() => ({}));
        if (!ask.ok || !askData.success) throw new Error(askData.message || 'The AI Technician could not answer right now.');

        const reply = askData.data?.answer?.content || 'Answer received.';
        $('qcResult').innerHTML = `<div class="font-semibold text-gray-900 mb-2">AI Technician says:</div>
            <div class="text-sm text-gray-700 whitespace-pre-line">${escapeHtml(reply)}</div>
            ${photos.length > 1 ? '<p class="text-xs text-gray-400 mt-2">Only the first photo was sent to the AI.</p>' : ''}`;
        const link = $('qcResultLink');
        link.href = @json(url('/app/sm-activities')) + '?id=' + scheduleId + '&module=ai';
        link.classList.remove('hidden'); link.textContent = 'Open AI Technician';
        $('qcTitle').textContent = 'AI Technician';
        showStep('result');
    }

    function escapeHtml(s) {
        const d = document.createElement('div'); d.textContent = String(s ?? ''); return d.innerHTML;
    }
});
</script>
@endpush
