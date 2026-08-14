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

    const MAX = 300 * 1024 * 1024; // 300 MB
    const VID_RE = /\.(mp4|mov|webm|mkv|avi|3gp|m4v)$/i;
    const say = (m, t) => { if (typeof window.toast === 'function') window.toast(m, t); };
    const hostOf = (el) => el && el.closest('form, [data-video-host]');
    const fmtMB = (b) => (b / 1048576).toFixed(b > 10485760 ? 0 : 1) + ' MB';

    function setChip(host, file) {
        const chip = host && host.querySelector('.js-video-chip');
        if (!chip) return;
        // Inline display avoids a hidden/flex utility clash and needs no rebuild.
        chip.style.display = file ? 'inline-flex' : 'none';
        const name = chip.querySelector('.js-video-name');
        if (name) name.textContent = file ? ('🎬 ' + (file.name || 'video') + ' · ' + fmtMB(file.size)) : '';
    }
    // Put a File/Blob into the host's real file input so submit handlers are uniform.
    function assignFile(host, file) {
        const input = host && host.querySelector('.js-video-file');
        if (!input) return false;
        if (file.size > MAX) { say('Video is larger than 300 MB.', 'error'); return false; }
        try { const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; }
        catch (_) { return false; }
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
        if (attach) { e.preventDefault(); hostOf(attach)?.querySelector('.js-video-file')?.click(); return; }
        const clear = e.target.closest('.js-video-clear');
        if (clear) { e.preventDefault(); window.plazaClearVideo(hostOf(clear)); return; }
        const rec = e.target.closest('.js-video-record');
        if (rec) { e.preventDefault(); openRecorder(hostOf(rec)); return; }
    });

    document.addEventListener('change', (e) => {
        if (!e.target.classList || !e.target.classList.contains('js-video-file')) return;
        const host = hostOf(e.target);
        const file = e.target.files && e.target.files[0];
        if (!file) { setChip(host, null); return; }
        if (!/^video\//.test(file.type) && !VID_RE.test(file.name)) {
            say('Please choose a video file.', 'error'); e.target.value = ''; setChip(host, null); return;
        }
        if (file.size > MAX) { say('Video is larger than 300 MB.', 'error'); e.target.value = ''; setChip(host, null); return; }
        setChip(host, file);
    });

    /* ---------------- Recorder modal ---------------- */
    let modal, stream, recorder, chunks, timerId, startedAt, targetHost, recordedBlob;

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
            recordedBlob = new Blob(chunks, { type: (recorder.mimeType || 'video/webm') });
            const prev = modal.querySelector('.pvm-preview');
            prev.srcObject = null; prev.muted = false;
            prev.src = URL.createObjectURL(recordedBlob); prev.controls = true;
            btn.textContent = '● Re-record';
            const use = modal.querySelector('.pvm-use');
            use.classList.toggle('hidden', recordedBlob.size === 0 || recordedBlob.size > MAX);
            if (recordedBlob.size > MAX) say('That recording is over 300 MB — record a shorter clip.', 'error');
        };
        recorder.start();
        startedAt = Date.now();
        timerId = setInterval(tick, 500);
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
    .plaza-vid-modal { position: fixed; inset: 0; z-index: 80; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .plaza-vid-modal.hidden { display: none; }
    .pvm-backdrop { position: absolute; inset: 0; background: rgb(0 0 0 / .6); }
    .pvm-card { position: relative; width: 100%; max-width: 32rem; background: var(--color-white); border-radius: 1rem; overflow: hidden; box-shadow: var(--shadow-card-lg); }
    .pvm-head { display: flex; align-items: center; justify-content: space-between; padding: .75rem 1rem; border-bottom: 1px solid var(--color-gray-100); }
    .pvm-x { border: 0; background: transparent; color: var(--color-gray-400); cursor: pointer; font-size: 1rem; }
    .pvm-stage { position: relative; background: #000; }
    .pvm-preview { width: 100%; max-height: 60vh; display: block; background: #000; }
    .pvm-timer { position: absolute; top: .5rem; left: .5rem; background: rgb(0 0 0 / .55); color: #fff; font-size: .75rem; font-weight: 700; padding: .1rem .5rem; border-radius: 999px; }
    .pvm-foot { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; padding: .75rem 1rem; }
    .pvm-rec { min-width: 6.5rem; }
</style>
