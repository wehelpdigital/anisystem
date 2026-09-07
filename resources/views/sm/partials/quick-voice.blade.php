{{-- Quick Voice — say it, name it, file it.

     Quick Record's spoken sibling. Talking is the fastest way a farmer in a
     field records anything, so the recorder opens straight into listening:
     one big button, a clock, stop, and then a name and an optional line.
     The result is a GLOBAL note wearing an audio player — no schedule is
     asked for — and the Global Gallery lists the same recording under
     Voice. Also exposes window.smRecordVoice so other composers (the notes
     editor) can borrow just the recorder. --}}
<div class="qv-modal hidden" id="quickVoiceModal" role="dialog" aria-modal="true" aria-label="Quick Voice">
    <div class="qv-backdrop" data-qv-cancel></div>
    <div class="qv-card">
        <div class="qv-head">
            <h3 class="font-bold text-gray-900">Quick Voice</h3>
            <button type="button" class="btn-ghost p-2 rounded-full" data-qv-cancel aria-label="Close">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <div class="qv-body space-y-4">
            {{-- The recorder: one state at a time — ready, listening, done. --}}
            <div class="qv-rec" id="qvRec">
                <button type="button" class="qv-mic" id="qvMicBtn" aria-label="Start recording">
                    <img src="{{ asset('images/voice-recorder.png') }}" alt="" class="qv-mic-img">
                </button>
                <p class="qv-say" id="qvSay">Tap to start recording</p>
                <p class="qv-clock" id="qvClock" hidden>0:00</p>
                <audio id="qvPlayback" controls hidden></audio>
            </div>

            <div class="flex flex-wrap gap-2" id="qvAgainRow" hidden>
                <button type="button" class="btn btn-white btn-sm" id="qvAgain">
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="7"/></svg>
                    Record again
                </button>
            </div>

            <div>
                <label class="form-label" for="qvTitle">Title <span class="text-red-500">*</span></label>
                <input type="text" id="qvTitle" class="form-input" maxlength="191"
                       placeholder="e.g. What I noticed on the east rows" autocomplete="off">
            </div>

            <div>
                <label class="form-label" for="qvNote">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                <textarea id="qvNote" class="form-textarea" rows="2" maxlength="5000"
                          placeholder="Anything worth adding in writing?"></textarea>
            </div>

            {{-- No schedule question: a spoken thought is the speaker's own.
                 It files straight into Global Notes, and the Global Gallery
                 lists the same recording under Voice. --}}
            <p class="form-hint">Saves to your Global Notes, and the Global Gallery lists it under Voice — same recording, both places.</p>
        </div>

        <div class="qv-foot">
            <button type="button" class="btn btn-ghost" data-qv-cancel>Cancel</button>
            <button type="button" class="btn btn-primary ml-auto" id="qvSave" disabled>Save voice note</button>
        </div>
    </div>
</div>

@push('head')
<style>
    .qv-modal { position: fixed; inset: 0; z-index: 90; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .qv-modal.hidden { display: none; }
    .qv-backdrop { position: absolute; inset: 0; background: rgb(0 0 0 / .6);
        opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
    .qv-modal.is-open .qv-backdrop { opacity: 1; }
    .qv-card { position: relative; width: 100%; max-width: 28rem; max-height: 92vh; display: flex; flex-direction: column;
        background: var(--color-white); border-radius: 1rem; overflow: hidden; box-shadow: var(--shadow-card-lg);
        transform: translateY(1.25rem) scale(.98); opacity: 0;
        transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    .qv-modal.is-open .qv-card { transform: none; opacity: 1; }
    .qv-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem;
        padding: .75rem 1rem; border-bottom: 1px solid var(--color-gray-100); }
    .qv-body { padding: 1rem; overflow-y: auto; }
    .qv-foot { display: flex; align-items: center; gap: .5rem; padding: .75rem 1rem;
        border-top: 1px solid var(--color-gray-100); }
    .qv-rec { display: flex; flex-direction: column; align-items: center; gap: .5rem;
        padding: 1.1rem .75rem; border-radius: .9rem; background: var(--color-gray-50);
        border: 1px dashed var(--color-gray-300); }
    .qv-mic { width: 4.2rem; height: 4.2rem; border-radius: 999px; display: inline-flex;
        align-items: center; justify-content: center; background: var(--color-white);
        border: 2px solid var(--color-gray-200); cursor: pointer;
        transition: border-color .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    .qv-mic:hover { border-color: #6b9f3d; transform: scale(1.04); }
    .qv-mic-img { width: 2.3rem; height: 2.3rem; object-fit: contain; }
    /* Listening: the ring pulses red so nobody wonders whether it is on. */
    .qv-rec.is-live .qv-mic { border-color: #dc2626; animation: qvPulse 1.2s ease-in-out infinite; }
    @keyframes qvPulse { 0%, 100% { box-shadow: 0 0 0 0 rgb(220 38 38 / .35); } 50% { box-shadow: 0 0 0 12px rgb(220 38 38 / 0); } }
    .qv-say { font-size: .8rem; font-weight: 700; color: var(--color-gray-600); }
    .qv-clock { font-size: 1.1rem; font-weight: 800; color: #dc2626; font-variant-numeric: tabular-nums; }
    .qv-rec audio { width: 100%; margin-top: .25rem; }
    html.dark .qv-card { background: #151b12; }
    html.dark .qv-head, html.dark .qv-foot { border-color: #2b3a1c; }
    html.dark .qv-rec { background: #1c2416; border-color: #2b3a1c; }
    html.dark .qv-mic { background: #151b12; border-color: #3a414c; }
    @media (prefers-reduced-motion: reduce) {
        .qv-backdrop, .qv-card, .qv-mic { transition: none; }
        .qv-rec.is-live .qv-mic { animation: none; }
    }
</style>
@endpush

@push('scripts')
<script>
(function quickVoice() {
    const modal = document.getElementById('quickVoiceModal');
    if (!modal || window.openQuickVoice) return;
    const $ = (id) => document.getElementById(id);
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';
    const VOICE_URL = @json(route('quick-voice.clip'));

    /* ---------------- the recorder itself ----------------
       Small on purpose: audio-only MediaRecorder, a clock, one blob out.
       Exposed as window.smRecordVoice so the notes editor can borrow it
       without carrying the filing form that follows here. */
    let rec = null, chunks = [], stream = null, timer = null, t0 = 0;
    let clip = null;
    const bestMime = () => ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4']
        .find((t) => window.MediaRecorder && MediaRecorder.isTypeSupported(t)) || '';

    const clock = () => {
        const s = Math.floor((Date.now() - t0) / 1000);
        $('qvClock').textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    };

    async function startRec() {
        if (!navigator.mediaDevices || !window.MediaRecorder) {
            window.toast?.('This browser cannot record audio.', 'error');
            return false;
        }
        try {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        } catch (_) {
            window.toast?.('Microphone blocked. Allow it for this site.', 'error');
            return false;
        }
        chunks = [];
        const mime = bestMime();
        try { rec = new MediaRecorder(stream, mime ? { mimeType: mime } : undefined); }
        catch (_) { rec = new MediaRecorder(stream); }
        rec.ondataavailable = (e) => { if (e.data && e.data.size) chunks.push(e.data); };
        rec.start(250);
        t0 = Date.now();
        clock();
        timer = setInterval(clock, 500);
        return true;
    }

    function stopRec() {
        return new Promise((resolve) => {
            if (!rec) { resolve(null); return; }
            const r = rec;
            rec = null;
            clearInterval(timer);
            r.onstop = () => {
                stream?.getTracks().forEach((t) => t.stop());
                stream = null;
                const type = r.mimeType || 'audio/webm';
                const blob = new Blob(chunks, { type });
                chunks = [];
                const ext = type.includes('mp4') ? 'm4a' : 'webm';
                resolve(new File([blob], 'voice-note.' + ext, { type }));
            };
            try { r.stop(); } catch (_) { resolve(null); }
        });
    }

    function killRec() {
        if (rec) { try { rec.stop(); } catch (_) { } rec = null; }
        clearInterval(timer);
        stream?.getTracks().forEach((t) => t.stop());
        stream = null;
        chunks = [];
    }

    /* Borrowable: records one voice clip and hands the File to the caller.
       Runs inside this modal's recorder pane rules-free — callers that want
       their own UI pass handlers instead. */
    window.smRecordVoice = { start: startRec, stop: stopRec, cancel: killRec };

    /* ---------------- the Quick Voice modal ---------------- */
    function paint(state) {
        // 'ready' | 'live' | 'done'
        $('qvRec').classList.toggle('is-live', state === 'live');
        $('qvSay').textContent = state === 'live' ? 'Listening… tap to stop'
            : (state === 'done' ? 'Recorded. Listen back, or record again.' : 'Tap to start recording');
        $('qvClock').hidden = state !== 'live';
        $('qvPlayback').hidden = state !== 'done';
        $('qvAgainRow').hidden = state !== 'done';
        $('qvSave').disabled = state !== 'done';
    }

    function open() {
        clip = null;
        $('qvTitle').value = '';
        $('qvNote').value = '';
        $('qvPlayback').removeAttribute('src');
        paint('ready');
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        window.registerOverlay?.('quickVoice', close);
    }
    function close() {
        killRec();
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
        setTimeout(() => {
            modal.classList.add('hidden');
            const a = $('qvPlayback');
            a.pause?.(); a.removeAttribute('src'); a.load?.();
        }, 260);
        clip = null;
    }

    window.openQuickVoice = open;

    $('qvMicBtn').addEventListener('click', async () => {
        if (rec) {
            clip = await stopRec();
            if (clip) {
                $('qvPlayback').src = URL.createObjectURL(clip);
                paint('done');
            } else { paint('ready'); }
            return;
        }
        if (await startRec()) paint('live');
    });
    $('qvAgain').addEventListener('click', () => {
        clip = null;
        $('qvPlayback').pause?.();
        $('qvPlayback').removeAttribute('src');
        paint('ready');
        $('qvMicBtn').click();
    });
    modal.addEventListener('click', (e) => { if (e.target.closest('[data-qv-cancel]')) close(); });

    $('qvSave').addEventListener('click', async () => {
        const title = $('qvTitle').value.trim();
        if (!clip) { window.toast?.('Record something first.', 'error'); return; }
        if (!title) { window.toast?.('Give the voice note a title.', 'error'); $('qvTitle').focus(); return; }
        const btn = $('qvSave');
        btn.disabled = true;
        const form = new FormData();
        form.append('clip', clip, clip.name);
        form.append('title', title);
        form.append('note', $('qvNote').value.trim());
        // No signal but Offline Mode on: the recording waits in the outbox
        // and uploads itself when the line returns.
        if (window.aneeOffline?.on() && !navigator.onLine) {
            try {
                await window.aneeOffline.enqueueForm(VOICE_URL, form);
                window.toast?.('Saved on this phone — the voice note will upload when the signal returns.');
                close();
            } catch (_) {
                window.toast?.('Could not keep the recording on this phone.', 'error');
                btn.disabled = false;
            }
            return;
        }
        try {
            const res = await fetch(VOICE_URL, {
                method: 'POST', credentials: 'same-origin', body: form,
                headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            });
            const json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Could not save the voice note.');
            window.toast?.(json.message);
            close();
        } catch (err) {
            window.toast?.(err.message, 'error');
            btn.disabled = false;
        }
    });

    document.getElementById('quickVoiceBtn')?.addEventListener('click', open);
})();
</script>
@endpush
