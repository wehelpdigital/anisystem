{{-- A video sent in pieces (2026-11-26, after a worker's clips kept failing in the field; App\Support\ClipUpload).

     Any page that sends a clip includes this and puts the clip in its form with

       await window.aneeClip.into(form, 'clip', file, { onProgress: (fraction) => …, onWait: (words) => … });

     which sends the video first, 5 MB at a time, and leaves `clipToken` in the form where the file would have been. A
     piece that does not get through is sent again by itself, for about three minutes; a video that still did not make it
     goes on from the piece it stopped at when the same file is sent again. The screen is kept awake meanwhile: a phone
     that sleeps drops its upload. `aneeClip.send(url, form, …)` posts the form and asks again if the answer is lost,
     which is safe for a form with a clipToken (TakesClipToken answers the same clip the same way). --}}
@once
<script>
(() => {
    if (window.aneeClip) return;
    const START = @json(route('clip-upload.start')), PART = @json(route('clip-upload.part'));
    const PART_BYTES = @json(\App\Support\ClipUpload::PART_BYTES);
    const WAITS = [1, 2, 4, 8, 15, 20, 30, 30, 30];   // seconds between tries at one piece
    const csrf = () => document.querySelector('meta[name=csrf-token]')?.content || '';
    const nap = (s) => new Promise((ok) => setTimeout(ok, s * 1000));
    // Worth another try: the line, a busy server, a hiccup between the two.
    const passing = (status) => !status || [408, 425, 429, 500, 502, 503, 504, 520, 521, 522, 523, 524].includes(status);
    const begun = new WeakMap();   // the file -> how far it got

    /* ---- the screen stays on, and the page asks before it is left ---- */
    let sending = 0, lock = null;
    const awake = async () => {
        try { if (sending && !document.hidden && !lock) { lock = await navigator.wakeLock?.request('screen'); lock?.addEventListener('release', () => { lock = null; }); } } catch (_) { /* a phone without it */ }
    };
    document.addEventListener('visibilitychange', awake);
    window.addEventListener('beforeunload', (e) => { if (sending) { e.preventDefault(); e.returnValue = ''; } });
    const open = () => { sending++; awake(); };
    const shut = () => { sending--; if (!sending) { lock?.release().catch(() => {}); lock = null; } };

    /** One request. Never rejects: { status, json } with status 0 when it did not get through. */
    function post(url, form, onProgress) {
        return new Promise((done) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.withCredentials = true;
            xhr.timeout = 240000;   // a piece on the slowest signal worth waiting for
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf());
            xhr.setRequestHeader('Accept', 'application/json');
            if (onProgress) xhr.upload.addEventListener('progress', (e) => { if (e.lengthComputable) onProgress(e.loaded); });
            xhr.addEventListener('load', () => {
                let json = null;
                try { json = JSON.parse(xhr.responseText); } catch (_) { /* an error page */ }
                done({ status: xhr.status, json });
            });
            ['error', 'timeout', 'abort'].forEach((k) => xhr.addEventListener(k, () => done({ status: 0, json: null })));
            xhr.send(form);
        });
    }

    /** A request made until it gets through, or until waiting longer is pointless. */
    async function until(make, onProgress, onWait) {
        for (let i = 0; ; i++) {
            const { url, form } = make();
            const { status, json } = await post(url, form, onProgress);
            if (status >= 200 && status < 300 && json?.success) return json;
            // A server that answered in words has answered; one that fell over is asked again.
            const said = status === 500 && json?.message && json.message !== 'Server Error';
            if (said || !passing(status) || i >= WAITS.length) {
                const e = new Error(json?.message
                    || ([401, 419].includes(status) ? 'You were logged out. Log in again, then send the video.'
                        : (passing(status) ? 'The signal kept dropping. Try again and it goes on from where it stopped.' : 'The video could not be sent.')));
                e.status = status;
                e.line = !status;   // nobody was reached
                throw e;
            }
            onWait?.('The signal dropped. Trying again…');
            await nap(WAITS[i]);
        }
    }

    const readable = async (blob) => {
        try { await blob.slice(0, 1).arrayBuffer(); return true; } catch (_) { return false; }
    };

    async function sendFile(file, { onProgress, onWait } = {}) {
        open();
        try {
            let at = begun.get(file);
            if (!at) {
                const s = await until(() => {
                    const form = new FormData();
                    form.append('name', file.name || 'clip.mp4');
                    form.append('bytes', String(file.size));
                    return { url: START, form };
                }, null, onWait);
                at = { token: s.token, parts: s.parts, done: 0 };
                begun.set(file, at);
            }
            while (at.done < at.parts) {
                const n = at.done + 1;
                const blob = file.slice(at.done * PART_BYTES, Math.min(file.size, n * PART_BYTES));
                // A file moved or cleaned away since it was picked reads as a dropped line. Say what it is.
                if (!(await readable(blob))) throw new Error('The phone could not read that video. Choose it again.');
                const before = at.done * PART_BYTES;
                let r;
                try {
                    r = await until(() => {
                        const form = new FormData();
                        form.append('token', at.token);
                        form.append('number', String(n));
                        form.append('part', blob, 'part');
                        return { url: PART, form };
                    }, (loaded) => onProgress?.(Math.min(1, (before + Math.min(loaded, blob.size)) / file.size)), onWait);
                } catch (e) {
                    if (e.status === 410) begun.delete(file);   // too old to go on with
                    throw e;
                }
                at.token = r.token;
                at.done = n;
                onProgress?.(Math.min(1, (before + blob.size) / file.size));
            }
            return at.token;
        } finally {
            shut();
        }
    }

    window.aneeClip = {
        /** Send the video and leave its token in the form, in place of the file. */
        async into(form, field, file, opts = {}) {
            form.append('clipToken', await sendFile(file, opts));
            form.append('clipField', field);
            return form;
        },
        /** Post a form that holds a clipToken, asking again if the answer is lost. Resolves to the door's own JSON. */
        send(url, form, opts = {}) {
            open();
            return until(() => ({ url, form }), null, opts.onWait).finally(shut);
        },
        isVideo: (f) => /^video\//.test(f.type || '') || (!f.type && /\.(mp4|mov|webm|mkv|m4v|3gp|avi)$/i.test(f.name || '')),
    };
})();
</script>
@endonce
