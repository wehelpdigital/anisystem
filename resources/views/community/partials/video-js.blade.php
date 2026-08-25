{{-- Shared video attach (upload) + record (MediaRecorder) for wall composers and
     comment/reply forms. A form opts in by including:
       <button class="js-video-attach">  choose/upload a video
       <button class="js-video-record">  record from the camera
       <input type="file" class="js-video-file" accept="video/*" hidden>
       <span class="js-video-chip hidden"><span class="js-video-name"></span>
             <button class="js-video-clear">✕</button></span>
     inside a <form> or any element with [data-video-host]. On submit, read the
     form's .js-video-file (recordings are written into it too, so it's uniform).
     Guarded singleton. --}}
<script>
(function () {
    if (window.__plazaVideoBound) return;
    window.__plazaVideoBound = true;

    /* ---- A film that leaves the eye stops talking -------------------------
     *
     * Slide a carousel to the next clip, scroll a playing post off the
     * screen, open a sheet over it — the moment a player is (mostly) not
     * visible, it pauses. One observer serves every video on the page,
     * wherever it came from: the wall's posts, a comment's clips, a topic,
     * a reply, pieces that arrive later over the wire. Intersection is
     * measured against every clipping ancestor, which is what makes the
     * carousel case free: a slide scrolled out of its own track reports
     * zero without anybody doing arithmetic.
     *
     * A live camera preview (srcObject) is exempt — pausing one freezes the
     * recorder's mirror, and the recording it belongs to is not a film
     * being watched. */
    (function pauseOffscreen() {
        if (!('IntersectionObserver' in window)) return;
        const io = new IntersectionObserver((entries) => {
            entries.forEach((en) => {
                const v = en.target;
                if (en.intersectionRatio >= 0.25) return;   // still mostly in view
                if (v.srcObject || v.paused || v.ended) return;
                try { v.pause(); } catch (_) { /* already unplayable */ }
            });
        }, { threshold: [0, 0.25] });
        const watch = (root) => {
            if (root.nodeType !== 1) return;
            if (root.tagName === 'VIDEO') io.observe(root);
            root.querySelectorAll?.('video').forEach((v) => io.observe(v));
        };
        const start = () => {
            watch(document.documentElement);
            // Videos keep arriving — load-more pages, fresh comments, a
            // carousel built after a post — and each one is watched on entry.
            new MutationObserver((muts) => muts.forEach((m) => m.addedNodes.forEach(watch)))
                .observe(document.body, { childList: true, subtree: true });
        };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
        else start();
    })();

    /* A minute, not a number of megabytes.
     *
     * Length is what somebody filming can judge before they press record, and
     * a minute of anything a phone shoots compresses small. The size ceiling
     * used to be the gate and it turned away clips that were merely long-ish
     * in high quality while waving through a slow, huge minute. It is asked
     * here so the answer arrives before an upload rather than after one. */
    const MAX_SECONDS = 60;
    const SLACK = 0.75;      // a clip that rounds to a minute is a minute
    const VID_RE = /\.(mp4|mov|webm|mkv|avi|3gp|m4v)$/i;
    const say = (m, t) => { if (typeof window.toast === 'function') window.toast(m, t); };
    const hostOf = (el) => el && el.closest('form, [data-video-host]');
    const fmtMB = (b) => (b / 1048576).toFixed(b > 10485760 ? 0 : 1) + ' MB';

    /**
     * How long a clip runs, without uploading it.
     *
     * Null when the browser will not say — an unusual container, a codec it
     * cannot open — and null means "let it go up": the server measures it
     * again with ffmpeg, which reads formats a browser does not.
     */
    function secondsOf(file) {
        return new Promise((resolve) => {
            let done = false;
            const finish = (v) => { if (!done) { done = true; resolve(v); } };
            const url = URL.createObjectURL(file);
            const v = document.createElement('video');
            v.preload = 'metadata';
            v.muted = true;
            v.onloadedmetadata = () => {
                const d = v.duration;
                URL.revokeObjectURL(url);
                finish(Number.isFinite(d) && d > 0 ? d : null);
            };
            v.onerror = () => { URL.revokeObjectURL(url); finish(null); };
            setTimeout(() => { URL.revokeObjectURL(url); finish(null); }, 8000);
            v.src = url;
        });
    }

    /** True when the clip is too long, having said so. */
    async function tooLong(file) {
        const secs = await secondsOf(file);
        if (secs === null || secs <= MAX_SECONDS + SLACK) {
            return false;
        }
        const mins = Math.floor(secs / 60), rest = Math.round(secs % 60);
        const said = mins ? (mins + ' minute' + (mins > 1 ? 's' : '') + (rest ? ' ' + rest + ' seconds' : ''))
                          : (Math.round(secs) + ' seconds');
        say('That clip is ' + said + ' long. Clips can be up to one minute — trim it and try again.', 'error');

        return true;
    }

    function setChip(host, file) {
        const chip = host && host.querySelector('.js-video-chip');
        if (!chip) return;
        // Inline display avoids a hidden/flex utility clash and needs no rebuild.
        chip.style.display = file ? 'inline-flex' : 'none';

        // A frame of the clip, the way a photo shows itself. The browser
        // paints one as soon as it has the metadata; muted+playsinline keeps
        // it from being treated as media that wants to play.
        const old = chip.querySelector('.js-chip-thumb');
        if (old) {
            if (old.dataset.objectUrl) URL.revokeObjectURL(old.dataset.objectUrl);
            old.remove();
        }
        if (file) {
            const v = document.createElement('video');
            v.className = 'js-chip-thumb';
            v.muted = true;
            v.playsInline = true;
            v.preload = 'metadata';
            const url = URL.createObjectURL(file);
            v.dataset.objectUrl = url;
            v.src = url;
            chip.insertBefore(v, chip.firstChild);
        }

        const name = chip.querySelector('.js-video-name');
        // The size still matters on an upload; the name no longer does, now
        // that the clip is showing itself.
        if (name) name.textContent = file ? fmtMB(file.size) : '';
    }
    // Put a File/Blob into the host's real file input so submit handlers are uniform.
    function assignFile(host, file) {
        const input = host && host.querySelector('.js-video-file');
        if (!input) return false;
        try { const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; }
        catch (_) { return false; }
        // Every road through here is the recorder (camera app or the modal);
        // a picked file fires its own change without passing this way. The
        // flag lets a listener ask "was this filmed just now?" — the note
        // flows only raise the naming sheet for filmed clips. Read-and-clear
        // is the consumer's job.
        input.dataset.smRecorded = '1';
        setChip(host, file);
        // Assigning .files in script does not fire change — the browser only
        // does that for a human choosing a file. Everything downstream waits
        // on change, so a recording used to land in the input and then sit
        // there: Use video, and nothing happened.
        input.dispatchEvent(new Event('change', { bubbles: true }));
        return true;
    }
    // Public helper for form submit handlers.
    window.plazaVideoFile = (host) => {
        const input = host && host.querySelector('.js-video-file');
        return input && input.files && input.files[0] ? input.files[0] : null;
    };
    window.plazaClearVideo = (host) => {
        const input = host && host.querySelector('.js-video-file');
        if (input) input.value = '';
        setChip(host, null);
    };

    document.addEventListener('click', (e) => {
        const attach = e.target.closest('.js-video-attach');
        if (attach) {
            e.preventDefault();
            const input = hostOf(attach)?.querySelector('.js-video-file');
            // A stale "filmed just now" flag from an earlier recording must
            // not survive into a picked file's change event.
            if (input) { delete input.dataset.smRecorded; input.click(); }
            return;
        }
        const clear = e.target.closest('.js-video-clear');
        if (clear) { e.preventDefault(); window.plazaClearVideo(hostOf(clear)); return; }
        const rec = e.target.closest('.js-video-record');
        if (rec) { e.preventDefault(); record(hostOf(rec)); return; }
    });

    document.addEventListener('change', (e) => {
        if (!e.target.classList || !e.target.classList.contains('js-video-file')) return;
        const host = hostOf(e.target);
        const file = e.target.files && e.target.files[0];
        if (!file) { setChip(host, null); return; }
        if (!/^video\//.test(file.type) && !VID_RE.test(file.name)) {
            say('Please choose a video file.', 'error'); e.target.value = ''; setChip(host, null); return;
        }
        // Measured before it is carried anywhere. The input is emptied on a
        // refusal so the next choice is a fresh one.
        tooLong(file).then((no) => {
            if (no) { e.target.value = ''; setChip(host, null); return; }
            setChip(host, file);
        });
    });

    /* ---------------- Which recorder ----------------------------------
     * A phone already has a recorder, and it is a better one than anything a
     * web page can build: real autofocus, stabilisation, the proper sensor
     * mode, the microphone the manufacturer chose. getUserMedia gives a web
     * page a soft, downscaled preview stream — fine for a video call, poor
     * for a record of what a leaf or a leak actually looks like.
     *
     * So on a phone we hand the job over and take back the file. Everywhere
     * else — a desktop, a browser without capture — the built-in recorder
     * stands in, full screen, because the thing being filmed deserves the
     * whole screen while you frame it.
     *
     * The pointer test matters: desktop browsers accept the capture attribute
     * and then ignore it, opening a file picker. A picker is not recording. */
    function hasCameraApp() {
        try {
            return ('capture' in document.createElement('input'))
                && window.matchMedia('(pointer: coarse)').matches;
        } catch (_) { return false; }
    }

    function record(host) {
        if (!host) return;
        if (hasCameraApp()) { nativeRecord(host); return; }
        openRecorder(host);
    }

    /** Hand off to the phone's camera app; take back whatever it filmed. */
    function nativeRecord(host) {
        let inp = host.querySelector('.js-video-native');
        if (!inp) {
            inp = document.createElement('input');
            inp.type = 'file';
            inp.accept = 'video/*';
            // environment: the back camera, which is the one pointed at the
            // field. The user can still flip it inside the camera app.
            inp.setAttribute('capture', 'environment');
            inp.className = 'js-video-native';
            inp.style.display = 'none';
            host.appendChild(inp);
            inp.addEventListener('change', () => {
                const file = inp.files && inp.files[0];
                inp.value = '';
                if (!file) return;                 // they backed out
                if (!assignFile(host, file)) return;
                say('Recording added.', 'success');
            });
        }
        inp.click();
    }

    /* ---------------- Recorder modal (the fallback) ---------------- */
    let modal, stream, recorder, chunks, timerId, startedAt, targetHost, recordedBlob;
    let capId = null;   // the take stops itself at a minute

    function pickMime() {
        const opts = ['video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm', 'video/mp4'];
        for (const m of opts) { try { if (window.MediaRecorder && MediaRecorder.isTypeSupported(m)) return m; } catch (_) {} }
        return '';
    }
    function buildModal() {
        if (modal) return modal;
        modal = document.createElement('div');
        modal.className = 'plaza-vid-modal hidden';
        modal.innerHTML = `
            <div class="pvm-backdrop"></div>
            <div class="pvm-card">
                <div class="pvm-head">
                    <span class="font-bold text-gray-900">Record a video</span>
                    <button type="button" class="pvm-x" data-pvm-close aria-label="Close">✕</button>
                </div>
                <div class="pvm-stage">
                    <video class="pvm-preview" playsinline muted></video>
                    <span class="pvm-timer">0:00</span>
                </div>
                <div class="pvm-foot">
                    <button type="button" class="btn btn-white btn-sm" data-pvm-close>Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm pvm-rec">● Record</button>
                    <button type="button" class="btn btn-primary btn-sm pvm-use hidden">Use video</button>
                </div>
            </div>`;
        document.body.appendChild(modal);
        modal.addEventListener('click', (e) => { if (e.target.closest('[data-pvm-close]') || e.target.classList.contains('pvm-backdrop')) closeRecorder(); });
        modal.querySelector('.pvm-rec').addEventListener('click', toggleRecord);
        modal.querySelector('.pvm-use').addEventListener('click', useRecording);
        return modal;
    }
    function tick() {
        const s = Math.floor((Date.now() - startedAt) / 1000);
        const el = modal.querySelector('.pvm-timer');
        if (el) el.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    }
    async function openRecorder(host) {
        if (!navigator.mediaDevices || !window.MediaRecorder) { say('Recording is not supported on this device — upload a video instead.', 'error'); return; }
        targetHost = host;
        recordedBlob = null;
        buildModal();
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: true });
        } catch (_) {
            say('Camera/microphone permission was denied.', 'error');
            return;
        }
        const prev = modal.querySelector('.pvm-preview');
        prev.srcObject = stream; prev.muted = true; prev.play?.();
        modal.querySelector('.pvm-rec').classList.remove('hidden');
        modal.querySelector('.pvm-rec').textContent = '● Record';
        modal.querySelector('.pvm-use').classList.add('hidden');
        modal.querySelector('.pvm-timer').textContent = '0:00';
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function toggleRecord() {
        const btn = modal.querySelector('.pvm-rec');
        if (recorder && recorder.state === 'recording') { recorder.stop(); return; }
        chunks = [];
        const mimeType = pickMime();
        try { recorder = mimeType ? new MediaRecorder(stream, { mimeType }) : new MediaRecorder(stream); }
        catch (_) { say('Could not start recording.', 'error'); return; }
        recorder.ondataavailable = (ev) => { if (ev.data && ev.data.size) chunks.push(ev.data); };
        recorder.onstop = () => {
            clearInterval(timerId);
            clearTimeout(capId);
            recordedBlob = new Blob(chunks, { type: (recorder.mimeType || 'video/webm') });
            const prev = modal.querySelector('.pvm-preview');
            prev.srcObject = null; prev.muted = false;
            prev.src = URL.createObjectURL(recordedBlob); prev.controls = true;
            btn.textContent = '● Re-record';
            const use = modal.querySelector('.pvm-use');
            use.classList.toggle('hidden', recordedBlob.size === 0);
        };
        recorder.start();
        startedAt = Date.now();
        timerId = setInterval(tick, 500);
        /* A minute, and it stops itself.
         *
         * Filming here cannot run past what the app will take: better the
         * recorder ends the take at the limit than a farmer films two minutes
         * of a field and is told afterwards. */
        clearTimeout(capId);
        capId = setTimeout(() => {
            if (recorder && recorder.state === 'recording') {
                recorder.stop();
                say('A minute is the most a clip can be — that is what was kept.');
            }
        }, MAX_SECONDS * 1000);
        btn.textContent = '■ Stop';
        modal.querySelector('.pvm-use').classList.add('hidden');
        const prev = modal.querySelector('.pvm-preview');
        prev.controls = false; prev.srcObject = stream; prev.muted = true; prev.play?.();
    }
    function useRecording() {
        if (!recordedBlob || !targetHost) return closeRecorder();
        const ext = (recordedBlob.type.indexOf('mp4') >= 0) ? 'mp4' : 'webm';
        const file = new File([recordedBlob], 'recording-' + (Date.now()) + '.' + ext, { type: recordedBlob.type });
        assignFile(targetHost, file);
        closeRecorder();
    }
    function closeRecorder() {
        clearInterval(timerId);
        try { if (recorder && recorder.state === 'recording') recorder.stop(); } catch (_) {}
        recorder = null;
        if (stream) { stream.getTracks().forEach((t) => t.stop()); stream = null; }
        if (modal) { modal.classList.add('hidden'); const p = modal.querySelector('.pvm-preview'); if (p) { p.srcObject = null; p.removeAttribute('src'); p.load?.(); } }
        document.body.style.overflow = '';
    }
})();
</script>
<style>
    /* Full screen while filming. A viewfinder in a small card in the middle
       of a page is a viewfinder you cannot frame anything with — the picture
       is the whole point, so it gets the whole screen. */
    .plaza-vid-modal { position: fixed; inset: 0; z-index: 80; display: flex; align-items: center; justify-content: center; }
    .plaza-vid-modal.hidden { display: none; }
    .pvm-backdrop { position: absolute; inset: 0; background: #000; }
    .pvm-card { position: relative; width: 100%; height: 100%; display: flex; flex-direction: column;
        background: #0b1220; color: #fff; overflow: hidden; }
    .pvm-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        padding: .75rem 1rem; flex: none; }
    .pvm-head .font-bold { color: #fff; }
    .pvm-x { border: 0; background: transparent; color: #cbd5e1; cursor: pointer; font-size: 1.1rem;
        width: 2.2rem; height: 2.2rem; border-radius: 999px; }
    .pvm-x:hover { background: rgb(255 255 255 / .12); }
    .pvm-stage { position: relative; flex: 1 1 auto; min-height: 0; background: #000;
        display: flex; align-items: center; justify-content: center; }
    /* contain, not cover: framing a shot against a cropped preview is
       filming something other than what you can see. */
    .pvm-preview { width: 100%; height: 100%; object-fit: contain; display: block; background: #000; }
    .pvm-timer { position: absolute; top: .75rem; left: .75rem; background: rgb(0 0 0 / .55); color: #fff;
        font-size: .8rem; font-weight: 800; padding: .15rem .6rem; border-radius: 999px;
        font-variant-numeric: tabular-nums; }
    .pvm-foot { display: flex; align-items: center; justify-content: center; gap: .6rem;
        padding: 1rem 1rem calc(1rem + env(safe-area-inset-bottom, 0px)); flex: none;
        /* A solid near-black band under the decision row: over a bright
           snapshot the old see-through footing let Re-record and Use video
           melt into whatever was just filmed. */
        background: rgb(0 0 0 / .78); border-top: 1px solid rgb(255 255 255 / .14); }
    .pvm-rec { min-width: 8rem; }
    /* The recorder's card is always dark, so its buttons must not follow the
       page theme: in dark mode `bg-white` names the darkest surface, which
       painted Cancel and ● Re-record near-black on black — the "retry" button
       nobody could see. Pinned to real white / real green here. */
    .pvm-foot .btn-white, html.dark .pvm-foot .btn-white {
        background: #fff; color: #111827; border-color: #fff; }
    .pvm-foot .btn-white:hover, html.dark .pvm-foot .btn-white:hover { background: #e5e7eb; }
    .pvm-foot .btn-primary, html.dark .pvm-foot .btn-primary {
        background: #4a7c2a; color: #fff; box-shadow: 0 0 0 1.5px rgb(255 255 255 / .35); }
    .pvm-foot .btn-primary:hover, html.dark .pvm-foot .btn-primary:hover { background: #3d6823; }
    /* Thumb-sized on a phone: these are the only two choices on screen. */
    .pvm-foot .btn-sm { min-height: 2.75rem; }
    @media (min-width: 768px) {
        /* A desktop has room to keep the page behind it, so the viewfinder
           is a large panel rather than the entire window. */
        .plaza-vid-modal { padding: 1.5rem; }
        .pvm-backdrop { background: rgb(0 0 0 / .75); }
        .pvm-card { width: min(56rem, 100%); height: min(82vh, 46rem); border-radius: 1rem;
            box-shadow: var(--shadow-card-lg); }
    }
</style>
