{{-- The Camera tab: everyone's view of the field at once.

     A call is a conversation that happens to carry pictures. This is the
     other thing — several people standing in different corners, all pointing
     a phone at what they can see, while the rest look. Any number share at
     once; tap one to fill the frame and tap again to put it back, without
     interrupting anybody else's.

     It is the SAME LiveKit room the call widget uses, because LiveKit gives
     one identity one connection and a second would knock the first out. The
     floating widget stays as the way to leave; this is the big view.

     Expects: $schedule, $isOwner. --}}
<div class="cam-wrap" data-cam-root>
    <div class="cam-bar">
        <button type="button" id="camShare" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.55-2.28A1 1 0 0121 8.62v6.76a1 1 0 01-1.45.9L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <span id="camShareLabel">Share my camera</span>
        </button>
        <button type="button" id="camFlip" class="btn btn-white btn-sm hidden" title="Front / back camera">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M20 9A8 8 0 006.34 5.34M4 15a8 8 0 0013.66 3.66"/></svg>
            Flip
        </button>
        @if ($isOwner)
            {{-- Only the owner records. A recording is a thing about other
                 people, and everyone in the room can see it start. --}}
            <button type="button" id="camRec" class="btn btn-white btn-sm ml-auto">
                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="7"/></svg>
                <span id="camRecLabel">Record</span>
            </button>
            <span id="camRecTime" class="cam-rectime hidden">0:00</span>
        @endif
    </div>

    <div class="cam-grid" id="camGrid">
        <p class="cam-empty" id="camEmpty">Nobody is sharing a camera yet. Tap <b>Share my camera</b> and everyone in the room will see what you see.</p>
    </div>

    {{-- The spotlight: one feed at full size, with the rest still running
         underneath it. Closing puts it back in the grid where it was. --}}
    <div class="cam-spot hidden" id="camSpot">
        <div class="cam-spot-head">
            <span class="cam-spot-name" id="camSpotName"></span>
            <button type="button" class="cam-spot-x" id="camSpotClose" aria-label="Back to the grid">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9l6 6m0-6l-6 6"/></svg>
            </button>
        </div>
        <div class="cam-spot-stage" id="camSpotStage"></div>
    </div>
</div>

@push('head')
<style>
    .cam-wrap { position: relative; display: flex; flex-direction: column; height: 100%; min-height: 0; }
    .cam-bar { display: flex; align-items: center; gap: .4rem; padding: .5rem; flex-shrink: 0; flex-wrap: wrap; }
    .cam-rectime { font-size: .74rem; font-weight: 800; color: #dc2626; font-variant-numeric: tabular-nums; }
    .cam-rectime.hidden { display: none; }

    .cam-grid { flex: 1 1 auto; min-height: 0; overflow-y: auto; padding: 0 .5rem .5rem;
        display: grid; grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr)); gap: .5rem;
        align-content: start; }
    .cam-empty { grid-column: 1 / -1; padding: 2rem 1rem; text-align: center;
        font-size: .85rem; line-height: 1.6; color: var(--color-gray-400); }
    .cam-tile { position: relative; aspect-ratio: 4/3; border-radius: .7rem; overflow: hidden; background: #111827;
        cursor: pointer; transition: transform .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1); }
    .cam-tile:hover { transform: translateY(-2px); box-shadow: 0 10px 26px -14px rgb(0 0 0 / .6); }
    .cam-tile video { width: 100%; height: 100%; object-fit: cover; display: block; background: #000; }
    .cam-tile-name { position: absolute; left: .35rem; bottom: .35rem; max-width: calc(100% - .7rem);
        padding: .1rem .45rem; border-radius: .4rem; background: rgb(0 0 0 / .55); color: #fff;
        font-size: .66rem; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cam-tile-max { position: absolute; right: .35rem; top: .35rem; width: 1.6rem; height: 1.6rem;
        border-radius: .45rem; background: rgb(0 0 0 / .5); color: #fff;
        display: flex; align-items: center; justify-content: center; opacity: .7; }
    .cam-tile:hover .cam-tile-max { opacity: 1; }
    /* Spotlit: the tile keeps its slot so the grid does not reflow under
       everyone else's feed, but says where the picture has gone. */
    .cam-tile.is-spot { outline: 2px solid #4a7c2a; outline-offset: -2px; }
    .cam-tile.is-spot video { opacity: .25; }
    .cam-tile.is-spot::after { content: 'On the big screen'; position: absolute; inset: 0;
        display: flex; align-items: center; justify-content: center; text-align: center; padding: .5rem;
        font-size: .68rem; font-weight: 800; color: #d8f0be; }

    .cam-spot { position: absolute; inset: 0; z-index: 5; background: #000; display: flex; flex-direction: column;
        animation: camSpotIn .28s cubic-bezier(.22,1,.36,1) both; }
    .cam-spot.hidden { display: none; }
    @keyframes camSpotIn { from { opacity: 0; transform: scale(.985); } }
    .cam-spot-head { display: flex; align-items: center; gap: .5rem; padding: .45rem .6rem; background: #0b1220; color: #fff; flex-shrink: 0; }
    .cam-spot-name { font-size: .82rem; font-weight: 800; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cam-spot-x { margin-left: auto; width: 2rem; height: 2rem; border-radius: .5rem; color: #cbd5e1;
        display: flex; align-items: center; justify-content: center; }
    .cam-spot-x:hover { background: rgb(255 255 255 / .12); }
    .cam-spot-stage { flex: 1 1 auto; min-height: 0; display: flex; align-items: center; justify-content: center; }
    .cam-spot-stage video { width: 100%; height: 100%; object-fit: contain; background: #000; }
    @media (prefers-reduced-motion: reduce) { .cam-tile, .cam-spot { transition: none; animation: none; } }
</style>
@endpush

@push('scripts')
<script>
(function collabCamera() {
    const root = document.querySelector('[data-cam-root]');
    if (!root || window.__camBooted) return;
    window.__camBooted = true;

    const $ = (id) => document.getElementById(id);
    const esc = window.escapeHtml || ((v) => String(v ?? ''));
    const IS_OWNER = @json($isOwner);
    const SCHEDULE_ID = @json($schedule->id);
    const SAVE_URL = @json(route('sm.collab.recording'));

    let sharing = false;
    let facing = 'environment';
    let spotKey = null;

    /* ---- The grid ------------------------------------------------------
     * Rebuilt from the room rather than patched per event: a participant can
     * gain and lose a camera several times in a session, and reconciling
     * that incrementally is how tiles end up showing a feed that stopped
     * two minutes ago. The room is the truth; this just draws it. */
    function videoTracksInRoom() {
        const room = window.smCall?.room();
        if (!room) return [];
        const out = [];
        const add = (p, isLocal) => {
            p.trackPublications.forEach((pub) => {
                if (!pub.track || pub.track.kind !== 'video') return;
                out.push({
                    key: (isLocal ? 'local' : p.sid) + ':' + (pub.trackSid || 'v'),
                    name: isLocal ? 'You' : (p.name || p.identity || 'Member'),
                    track: pub.track,
                    isLocal,
                });
            });
        };
        if (room.localParticipant) add(room.localParticipant, true);
        room.remoteParticipants.forEach((p) => add(p, false));
        return out;
    }

    function paint() {
        const grid = $('camGrid');
        const feeds = videoTracksInRoom();

        if (!feeds.length) {
            grid.innerHTML = '<p class="cam-empty" id="camEmpty">Nobody is sharing a camera yet. '
                + 'Tap <b>Share my camera</b> and everyone in the room will see what you see.</p>';
            closeSpot();
            return;
        }

        grid.innerHTML = '';
        for (const f of feeds) {
            const tile = document.createElement('div');
            tile.className = 'cam-tile' + (spotKey === f.key ? ' is-spot' : '');
            tile.dataset.camKey = f.key;
            tile.dataset.camName = f.name;
            tile.innerHTML = '<span class="cam-tile-name">' + esc(f.name) + '</span>'
                + '<span class="cam-tile-max"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">'
                + '<path stroke-linecap="round" stroke-linejoin="round" d="M4 9V4h5M20 15v5h-5M15 4h5v5M9 20H4v-5"/></svg></span>';
            const v = f.track.attach();
            v.autoplay = true; v.playsInline = true; v.muted = true;
            tile.prepend(v);
            grid.appendChild(tile);
        }

        // A spotlight whose feed has gone belongs nowhere.
        if (spotKey && !feeds.some((f) => f.key === spotKey)) closeSpot();
        else if (spotKey) paintSpot(feeds.find((f) => f.key === spotKey));
    }

    /* ---- The spotlight -------------------------------------------------
     * The same track, attached a second time. LiveKit hands out as many
     * elements as you ask for from one track, so the grid keeps running
     * underneath — nobody else's view is interrupted by your choice. */
    function paintSpot(feed) {
        if (!feed) return closeSpot();
        const stage = $('camSpotStage');
        stage.innerHTML = '';
        const v = feed.track.attach();
        v.autoplay = true; v.playsInline = true; v.muted = true;
        stage.appendChild(v);
        $('camSpotName').textContent = feed.name;
        $('camSpot').classList.remove('hidden');
    }
    function closeSpot() {
        spotKey = null;
        $('camSpot')?.classList.add('hidden');
        const stage = $('camSpotStage');
        if (stage) stage.innerHTML = '';
        root.querySelectorAll('.cam-tile.is-spot').forEach((t) => t.classList.remove('is-spot'));
    }

    $('camGrid').addEventListener('click', (e) => {
        const tile = e.target.closest('[data-cam-key]');
        if (!tile) return;
        const key = tile.dataset.camKey;
        if (spotKey === key) { closeSpot(); return; }
        spotKey = key;
        paint();
    });
    $('camSpotClose').addEventListener('click', () => { closeSpot(); paint(); });

    /* ---- Sharing -------------------------------------------------------
     * Joining is the call's job; this only asks for it. A member who was
     * already on a call simply publishes a camera into the room they are in. */
    $('camShare').addEventListener('click', async () => {
        const room = window.smCall?.room();
        if (!sharing && !room) {
            // Flagged so the call widget knows to stay out of the way: its
            // floating panel would cover the grid it duplicates.
            window.__smCameraJoin = true;
            const ok = await window.smCall.ensure('Team room');
            window.__smCameraJoin = false;
            if (!ok) return;
        }
        const live = window.smCall?.room();
        if (!live) return;

        sharing = !sharing;
        try {
            await live.localParticipant.setCameraEnabled(sharing, { facingMode: facing });
        } catch (err) {
            sharing = !sharing;
            window.toast?.(window.isSecureContext
                ? 'Camera permission was blocked.'
                : 'Sharing a camera needs HTTPS — open the app over https://.', 'error');
        }
        paintShare();
        paint();
    });

    function paintShare() {
        $('camShareLabel').textContent = sharing ? 'Stop sharing' : 'Share my camera';
        $('camShare').classList.toggle('btn-primary', !sharing);
        $('camShare').classList.toggle('btn-white', sharing);
        $('camFlip').classList.toggle('hidden', !sharing);
    }

    $('camFlip').addEventListener('click', async () => {
        const live = window.smCall?.room();
        if (!live || !sharing) return;
        facing = facing === 'environment' ? 'user' : 'environment';
        try {
            await live.localParticipant.setCameraEnabled(false);
            await live.localParticipant.setCameraEnabled(true, { facingMode: facing });
        } catch (_) { window.toast?.('That camera is not available.', 'error'); }
        paint();
    });

    // The room tells us when anything changes; redraw from it.
    // Registered when the room API turns up, whichever of us loads first.
    function watchRoom() {
        if (!window.smCall || watchRoom.done) return;
        watchRoom.done = true;
        window.smCall.watch((evt) => {
            if (evt === 'gone') { sharing = false; paintShare(); }
            paint();
        });
    }
    document.addEventListener('smcall:ready', watchRoom);
    watchRoom();

    @if ($isOwner)
    /* ---- Recording (owner only) ----------------------------------------
     * What is recorded is what the owner is looking at: the spotlight if one
     * is up, otherwise every feed drawn side by side. A canvas is the only
     * way to record more than one video at once in a browser, so the grid is
     * literally re-drawn into one, frame by frame, and that canvas is what
     * MediaRecorder sees.
     *
     * Every microphone in the room is mixed in through one WebAudio graph.
     * Recording a conversation without its words would be a strange thing to
     * keep. */
    let rec = null, chunks = [], recTimer = null, recStart = 0, drawTimer = null, audioCtx = null;

    function startDrawing(canvas) {
        const cx = canvas.getContext('2d');
        const step = () => {
            const feeds = spotKey
                ? [...root.querySelectorAll('#camSpotStage video')]
                : [...root.querySelectorAll('.cam-tile video')];
            cx.fillStyle = '#000';
            cx.fillRect(0, 0, canvas.width, canvas.height);
            if (feeds.length) {
                // Whatever the count, a square-ish arrangement: 1 wide for
                // one feed, 2 for up to four, 3 beyond that.
                const cols = feeds.length === 1 ? 1 : (feeds.length <= 4 ? 2 : 3);
                const rows = Math.ceil(feeds.length / cols);
                const cw = canvas.width / cols, ch = canvas.height / rows;
                feeds.forEach((v, i) => {
                    if (!v.videoWidth) return;
                    const x = (i % cols) * cw, y = Math.floor(i / cols) * ch;
                    // Contain, not cover: a recording that crops off the
                    // thing being pointed at is worse than one with bars.
                    const scale = Math.min(cw / v.videoWidth, ch / v.videoHeight);
                    const w = v.videoWidth * scale, h = v.videoHeight * scale;
                    cx.drawImage(v, x + (cw - w) / 2, y + (ch - h) / 2, w, h);
                });
            }
            drawTimer = requestAnimationFrame(step);
        };
        step();
    }

    function mixedAudio() {
        const tracks = window.smCall?.audioTracks() || [];
        if (!tracks.length) return null;
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const dest = audioCtx.createMediaStreamDestination();
        for (const t of tracks) {
            try {
                const ms = new MediaStream([t.mediaStreamTrack]);
                audioCtx.createMediaStreamSource(ms).connect(dest);
            } catch (_) { /* one silent source beats no recording */ }
        }
        return dest.stream;
    }

    function pickMime() {
        for (const m of ['video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm', 'video/mp4']) {
            try { if (window.MediaRecorder && MediaRecorder.isTypeSupported(m)) return m; } catch (_) {}
        }
        return '';
    }

    async function startRec() {
        if (!window.MediaRecorder) { window.toast?.('Recording is not supported on this device.', 'error'); return; }
        if (!videoTracksInRoom().length) { window.toast?.('There is nothing to record yet.', 'error'); return; }

        const canvas = document.createElement('canvas');
        canvas.width = 1280; canvas.height = 720;
        startDrawing(canvas);

        const stream = canvas.captureStream(24);
        const audio = mixedAudio();
        audio?.getAudioTracks().forEach((t) => stream.addTrack(t));

        chunks = [];
        const mime = pickMime();
        try { rec = mime ? new MediaRecorder(stream, { mimeType: mime }) : new MediaRecorder(stream); }
        catch (_) { window.toast?.('Could not start recording.', 'error'); stopDrawing(); return; }

        rec.ondataavailable = (e) => { if (e.data && e.data.size) chunks.push(e.data); };
        rec.onstop = () => {
            stopDrawing();
            const blob = new Blob(chunks, { type: rec.mimeType || 'video/webm' });
            chunks = [];
            if (blob.size > 0) askAndSave(blob);
        };
        rec.start(1000);
        recStart = Date.now();
        recTimer = setInterval(() => {
            const s = Math.floor((Date.now() - recStart) / 1000);
            $('camRecTime').textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
            // Half an hour of 720p is already a large upload on a farm line.
            if (s >= 1800) stopRec();
        }, 500);
        $('camRecTime').classList.remove('hidden');
        $('camRecLabel').textContent = 'Stop';
        window.toast?.('Recording — everyone in the room can see this.');
    }

    function stopDrawing() {
        if (drawTimer) { cancelAnimationFrame(drawTimer); drawTimer = null; }
        try { audioCtx?.close(); } catch (_) {}
        audioCtx = null;
    }

    function stopRec() {
        if (!rec) return;
        try { rec.stop(); } catch (_) {}
        rec = null;
        clearInterval(recTimer); recTimer = null;
        $('camRecTime').classList.add('hidden');
        $('camRecTime').textContent = '0:00';
        $('camRecLabel').textContent = 'Record';
    }

    $('camRec').addEventListener('click', () => (rec ? stopRec() : startRec()));

    /* A recording nobody named is a file called "recording-1755..." that
       nobody ever opens again. Ask while it is still fresh. */
    function askAndSave(blob) {
        window.smAskRecording?.({
            sizeMB: blob.size / 1048576,
            onSave: async ({ title, description }) => {
                const waiting = window.toast?.('Compressing and saving the recording…', 'info', 0);
                const form = new FormData();
                form.append('clip', blob, 'team-recording.webm');
                form.append('scheduleId', SCHEDULE_ID);
                form.append('title', title);
                form.append('description', description);
                form.append('kind', 'camera');
                try {
                    const res = await fetch(SAVE_URL, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            Accept: 'application/json',
                        },
                        body: form,
                        credentials: 'same-origin',
                    });
                    const json = await res.json().catch(() => ({}));
                    if (!json.success) throw new Error(json.message || 'Could not save the recording.');
                    waiting?.close();
                    window.toast?.(json.message);
                } catch (err) {
                    waiting?.close();
                    window.toast?.(err.message, 'error');
                }
            },
        });
    }
    @endif

    // A tab that was never opened has nothing to draw; one that is opened
    // mid-call has everything.
    document.addEventListener('collab:show', (e) => { if (e.detail?.tab === 'camera') paint(); });
    paint();
})();
</script>
@endpush
