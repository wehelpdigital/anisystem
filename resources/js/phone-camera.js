/* ------------------------------------------------------------------ */
/* The camera inside the app                                            */
/* ------------------------------------------------------------------ */
/*
 * 2026-11-05, the owner: on another phone the camera "says it can't save the
 * file due to low memory", on a phone with 8 GB of RAM and most of 256 GB free.
 *
 * Those words are the browser's own ("Unable to complete previous operation
 * due to low memory"), and they are not about storage. A photo input with
 * `capture` sends the person out to the phone's camera app. While that app is
 * open the phone may close the browser behind it to give the camera room
 * (many makers do this whatever the RAM), and the photo then comes back to a
 * page that is gone. Some camera apps also just come back empty handed.
 *
 * So every photo input with `capture` is watched from here, and none of the
 * pages that own one had to change:
 *
 *   - The phone's camera app stays the first choice. Its pictures are sharper
 *     than a web page's (focus, HDR, the full sensor), see quick-capture.
 *   - A trip to it leaves a mark in this browser and coming back alive wipes
 *     it. A page that starts with the mark still fresh was closed while the
 *     camera was out: this phone switches to the camera inside the app, and
 *     says why.
 *   - A trip that comes back with nothing offers the camera inside the app.
 *   - The camera inside the app never leaves the page. Its photo goes into the
 *     same input with a change event, exactly as the camera app's would have,
 *     so every page takes it the way it always took a photo. A button in it
 *     goes back to the phone's camera app.
 *
 * Videos still go to the phone's camera app: only photo inputs come here.
 */

const MODE = 'anee.camera';          // 'app' once this phone uses the camera inside the app
const AWAY = 'anee.camera.away';     // when the last trip out to the camera app began
const FRESH_MS = 10 * 60 * 1000;     // a closed page older than this is not the camera's doing
const SILENT_MS = 4000;              // back on the page, and still no photo and no "cancel"

const store = {
    get(k) { try { return localStorage.getItem(k); } catch (_) { return null; } },
    set(k, v) { try { localStorage.setItem(k, v); } catch (_) { /* private window */ } },
    drop(k) { try { localStorage.removeItem(k); } catch (_) { /* private window */ } },
};

const isPhone = () => {
    try { return window.matchMedia('(pointer: coarse)').matches && navigator.maxTouchPoints > 0; } catch (_) { return false; }
};
const canStream = () => !!(window.isSecureContext && navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
const isPhotoCapture = (el) => el instanceof HTMLInputElement && el.type === 'file' && el.hasAttribute('capture')
    && /image/i.test(el.accept || '') && !/video/i.test(el.accept || '');
const usesAppCamera = () => store.get(MODE) === 'app';
const reduced = () => window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;

const ICON = {
    x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>',
    flip: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 11a8 8 0 00-14.9-3M4 13a8 8 0 0014.9 3M4 4v4h4M20 20v-4h-4"/></svg>',
    phone: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path stroke-linecap="round" d="M11 18.5h2"/></svg>',
};

/* ---- the look, written once on first use ---- */
function css() {
    if (document.getElementById('pcam-css')) return;
    const s = document.createElement('style');
    s.id = 'pcam-css';
    s.textContent = `
dialog.pcam { position: fixed; inset: 0; width: 100%; height: 100%; max-width: none; max-height: none; margin: 0; padding: 0;
    border: 0; background: #000; color: #fff; overflow: hidden; opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
dialog.pcam.is-open { opacity: 1; }
dialog.pcam::backdrop { background: #000; }
.pcam-video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; background: #000;
    opacity: 0; transition: opacity .28s cubic-bezier(.22,1,.36,1); }
.pcam-video.is-live { opacity: 1; }
.pcam.is-user .pcam-video { transform: scaleX(-1); }
.pcam-flash { position: absolute; inset: 0; background: #fff; opacity: 0; pointer-events: none; }
.pcam-flash.is-on { animation: pcamFlash .28s cubic-bezier(.22,1,.36,1); }
@keyframes pcamFlash { from { opacity: .75; } to { opacity: 0; } }
.pcam-top { position: absolute; left: 0; right: 0; top: 0; display: flex; align-items: center; justify-content: space-between; gap: .75rem;
    padding: calc(.75rem + env(safe-area-inset-top)) 1rem .75rem; background: linear-gradient(rgb(0 0 0 / .5), transparent); }
.pcam-round { width: 2.75rem; height: 2.75rem; flex: none; border-radius: 999px; border: 0; padding: 0; display: flex; align-items: center;
    justify-content: center; background: rgb(0 0 0 / .4); color: #fff; cursor: pointer; }
.pcam-round svg { width: 1.35rem; height: 1.35rem; }
.pcam-round[hidden] { display: none; }
.pcam-warn { flex: 0 1 auto; min-width: 0; text-align: center; font-size: .78rem; font-weight: 700; line-height: 1.3;
    padding: .4rem .75rem; border-radius: 999px; background: rgb(0 0 0 / .6); }
.pcam-warn[hidden] { display: none; }
.pcam-bottom { position: absolute; left: 0; right: 0; bottom: 0; display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: .75rem;
    padding: 1.25rem 1rem calc(1.25rem + env(safe-area-inset-bottom)); background: linear-gradient(transparent, rgb(0 0 0 / .6)); }
.pcam-side { justify-self: start; display: inline-flex; flex-direction: column; align-items: center; gap: .2rem; padding: .25rem;
    border: 0; background: none; color: #fff; font-size: .7rem; font-weight: 700; cursor: pointer; }
.pcam-side svg { width: 1.6rem; height: 1.6rem; }
.pcam-right { justify-self: end; }
.pcam-shutter { width: 4.5rem; height: 4.5rem; border-radius: 999px; border: 4px solid #fff; background: transparent; padding: 0;
    display: flex; align-items: center; justify-content: center; cursor: pointer; }
.pcam-shutter span { width: 3.4rem; height: 3.4rem; border-radius: 999px; background: #fff;
    transition: transform .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
.pcam-shutter:active span { transform: scale(.86); }
.pcam.is-busy .pcam-shutter span { transform: scale(.7); opacity: .55; }
.pcam.is-off .pcam-shutter { opacity: .35; pointer-events: none; }
.pcam-done { display: inline-flex; align-items: center; gap: .45rem; padding: .3rem .8rem .3rem .3rem; border: 0; border-radius: 999px;
    background: #f5c518; color: #1f2937; font-size: .85rem; font-weight: 800; cursor: pointer; animation: pcamIn .28s cubic-bezier(.22,1,.36,1); }
.pcam-done[hidden] { display: none; }
.pcam-done img { width: 2.2rem; height: 2.2rem; border-radius: .55rem; object-fit: cover; display: block; }
@keyframes pcamIn { from { opacity: 0; transform: scale(.9); } to { opacity: 1; transform: none; } }
.pcam-say { position: absolute; left: 1.5rem; right: 1.5rem; top: 50%; transform: translateY(-50%); text-align: center;
    font-size: .95rem; line-height: 1.5; animation: pcamIn .28s cubic-bezier(.22,1,.36,1); }
.pcam-say[hidden] { display: none; }
.pcam-say button { margin-top: 1rem; padding: .6rem 1.3rem; border: 0; border-radius: 999px; background: #fff; color: #111827; font-weight: 700; cursor: pointer; }
.pcam-note { position: fixed; inset: auto 1rem calc(1rem + env(safe-area-inset-bottom)) 1rem; z-index: 2147483000; margin: 0 auto;
    width: auto; max-width: 26rem; height: auto; padding: 1rem 1.1rem; border: 0; border-radius: 1rem; background: #1f2937; color: #fff;
    font-size: .875rem; line-height: 1.5; box-shadow: 0 24px 48px -20px rgb(0 0 0 / .6); overflow: visible;
    opacity: 0; transform: translateY(12px); transition: opacity .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
.pcam-note.is-shown { opacity: 1; transform: none; }
.pcam-note p { margin: 0; }
.pcam-note-acts { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: .5rem; margin-top: .8rem; }
.pcam-note button { padding: .5rem .95rem; border: 0; border-radius: 999px; font-size: .8125rem; font-weight: 700; cursor: pointer; }
.pcam-note .is-go { background: #f5c518; color: #1f2937; }
.pcam-note .is-quiet { background: rgb(255 255 255 / .12); color: #fff; }
@media (prefers-reduced-motion: reduce) {
    dialog.pcam, .pcam-video, .pcam-shutter span, .pcam-note { transition: none; }
    .pcam-flash.is-on, .pcam-done, .pcam-say { animation: none; }
}`;
    document.head.appendChild(s);
}

/* ---- a note at the foot of the screen, with a button or two ---- */
let noteEl = null, noteTimer = null;
function note(text, { go = null, onGo = null, quiet = 'OK', ms = 0 } = {}) {
    css();
    closeNote(true);
    const el = document.createElement('div');
    el.className = 'pcam-note';
    el.setAttribute('role', 'status');
    // The top layer, like the toasts: a note under an open sheet is no note.
    if ('popover' in el) el.popover = 'manual';
    el.innerHTML = '<p></p><div class="pcam-note-acts"></div>';
    el.querySelector('p').textContent = text;
    const acts = el.querySelector('.pcam-note-acts');
    const btn = (label, cls, fn) => {
        const b = document.createElement('button');
        b.type = 'button'; b.className = cls; b.textContent = label;
        b.addEventListener('click', () => { closeNote(); fn?.(); });
        acts.appendChild(b);
    };
    if (quiet) btn(quiet, 'is-quiet');
    if (go) btn(go, 'is-go', onGo);
    document.body.appendChild(el);
    try { el.showPopover?.(); } catch (_) { /* no top layer: the z-index holds */ }
    requestAnimationFrame(() => el.classList.add('is-shown'));
    noteEl = el;
    if (ms > 0) noteTimer = setTimeout(() => closeNote(), ms);
}
function closeNote(now = false) {
    clearTimeout(noteTimer);
    const el = noteEl;
    if (!el) return;
    noteEl = null;
    el.classList.remove('is-shown');
    const gone = () => el.remove();
    if (now || reduced()) gone(); else setTimeout(gone, 300);
}

/* ---- the camera itself ---- */
let dlg = null, stream = null, target = null, facing = 'environment', busy = false;
let shots = [], armedUntil = 0, warnTimer = null, closeTimer = null, startTok = 0;
const $ = (sel) => dlg.querySelector(sel);

function build() {
    if (dlg) return;
    css();
    dlg = document.createElement('dialog');
    dlg.className = 'pcam';
    dlg.setAttribute('aria-label', 'Camera');
    dlg.innerHTML = `
        <video class="pcam-video" playsinline muted autoplay></video>
        <div class="pcam-flash"></div>
        <div class="pcam-say" hidden><p></p><button type="button" data-pcam="retry">Try again</button></div>
        <div class="pcam-top">
            <button type="button" class="pcam-round" data-pcam="close" aria-label="Close the camera">${ICON.x}</button>
            <span class="pcam-warn" hidden></span>
            <button type="button" class="pcam-round" data-pcam="flip" aria-label="Switch to the other camera" hidden>${ICON.flip}</button>
        </div>
        <div class="pcam-bottom">
            <button type="button" class="pcam-side" data-pcam="phone" title="Take it with the phone's own camera app">${ICON.phone}<span>Phone camera</span></button>
            <button type="button" class="pcam-shutter" data-pcam="snap" aria-label="Take the photo"><span></span></button>
            <div class="pcam-right"><button type="button" class="pcam-done" data-pcam="done" hidden><img alt=""><b></b></button></div>
        </div>`;
    document.body.appendChild(dlg);
    // Escape, and the phone's back gesture on a modal dialog.
    dlg.addEventListener('cancel', (e) => { e.preventDefault(); leave(); });
    dlg.addEventListener('click', (e) => {
        const b = e.target.closest('[data-pcam]');
        if (!b) return;
        const act = b.getAttribute('data-pcam');
        if (act === 'snap') snap();
        else if (act === 'close') leave();
        else if (act === 'done') hand(shots.map((s) => s.file));
        else if (act === 'retry') start();
        else if (act === 'flip') { facing = facing === 'user' ? 'environment' : 'user'; start(); }
        else if (act === 'phone') toPhoneCamera();
    });
}

async function open(input) {
    build();
    closeNote(true);
    clearTimeout(closeTimer);
    target = input;
    clearShots();
    armedUntil = 0;
    warn(null);
    facing = input.getAttribute('capture') === 'user' ? 'user' : 'environment';
    if (!dlg.open) dlg.showModal();
    requestAnimationFrame(() => dlg.classList.add('is-open'));
    await start();
}

async function start() {
    const tok = ++startTok;
    stop();
    say(null);
    dlg.classList.add('is-off');
    dlg.classList.toggle('is-user', facing === 'user');
    const v = $('.pcam-video');
    // With a still camera to ask, the preview only has to be good enough to
    // aim with. Without one, the photo IS a preview frame, so ask for the most.
    const big = !('ImageCapture' in window);
    let s;
    try {
        s = await navigator.mediaDevices.getUserMedia({ audio: false, video: {
            facingMode: { ideal: facing },
            width: { ideal: big ? 3840 : 1920 },
            height: { ideal: big ? 2160 : 1080 },
        } });
    } catch (err) {
        if (tok !== startTok) return;
        const blocked = err && (err.name === 'NotAllowedError' || err.name === 'SecurityError');
        say(blocked
            ? 'The camera is blocked for this site. Allow it in the browser\'s site settings and try again, or use the phone camera.'
            : 'The camera could not start. Another app may be using it. Try again, or use the phone camera.');
        return;
    }
    // Closed, or turned around again, while this camera was still starting.
    if (!dlg.open || tok !== startTok) { s.getTracks().forEach((t) => t.stop()); return; }
    stream = s;
    v.srcObject = s;
    v.addEventListener('loadeddata', () => { v.classList.add('is-live'); dlg.classList.remove('is-off'); }, { once: true });
    try { await v.play(); } catch (_) { /* autoplay holds it, muted */ }
    try {
        const cams = (await navigator.mediaDevices.enumerateDevices()).filter((d) => d.kind === 'videoinput');
        $('[data-pcam="flip"]').hidden = cams.length < 2;
    } catch (_) { /* one camera it is */ }
}

function stop() {
    if (stream) { stream.getTracks().forEach((t) => t.stop()); stream = null; }
    if (!dlg) return;
    const v = $('.pcam-video');
    v.srcObject = null;
    v.classList.remove('is-live');
}

function say(text) {
    const box = $('.pcam-say');
    box.hidden = !text;
    if (text) box.querySelector('p').textContent = text;
}

function warn(text, ms = 0) {
    clearTimeout(warnTimer);
    const w = $('.pcam-warn');
    w.hidden = !text;
    if (text) w.textContent = text;
    if (text && ms) warnTimer = setTimeout(() => { w.hidden = true; }, ms);
}

/**
 * The picture. A still camera first (ImageCapture: the full sensor and its own
 * focus, the nearest a page gets to the camera app), then a frame off the
 * preview when the phone has none, it stalls, or it hands back a picture lying
 * on its side.
 */
async function still(v) {
    const track = stream && stream.getVideoTracks()[0];
    if ('ImageCapture' in window && track && track.readyState === 'live') {
        try {
            const ic = new window.ImageCapture(track);
            let size = null;
            try {
                const caps = await ic.getPhotoCapabilities();
                if (caps?.imageWidth?.max && caps?.imageHeight?.max) size = { imageWidth: caps.imageWidth.max, imageHeight: caps.imageHeight.max };
            } catch (_) { /* the phone's own size */ }
            const take = size ? ic.takePhoto(size).catch(() => ic.takePhoto()) : ic.takePhoto();
            const blob = await Promise.race([take, new Promise((_, no) => setTimeout(() => no(new Error('slow')), 8000))]);
            if (blob && blob.size && await sameWayUp(blob, v)) return blob;
        } catch (_) { /* the preview frame below */ }
    }
    return frame(v);
}

/** Portrait on screen should be portrait in the file, and the other way round. */
function sameWayUp(blob, v) {
    return new Promise((res) => {
        const url = URL.createObjectURL(blob);
        const img = new Image();
        const done = (ok) => { URL.revokeObjectURL(url); res(ok); };
        img.onload = () => done((img.naturalWidth >= img.naturalHeight) === (v.videoWidth >= v.videoHeight));
        img.onerror = () => done(false);
        img.src = url;
    });
}

function frame(v) {
    if (!v.videoWidth) return Promise.resolve(null);
    const c = document.createElement('canvas');
    c.width = v.videoWidth;
    c.height = v.videoHeight;
    c.getContext('2d').drawImage(v, 0, 0);
    return new Promise((res) => c.toBlob(res, 'image/jpeg', 0.92));
}

async function snap() {
    if (busy || !stream) return;
    const v = $('.pcam-video');
    if (!v.videoWidth) return;
    busy = true;
    dlg.classList.add('is-busy');
    const fl = $('.pcam-flash');
    fl.classList.remove('is-on'); void fl.offsetWidth; fl.classList.add('is-on');
    let blob = null;
    try { blob = await still(v); } catch (_) { blob = null; }
    busy = false;
    if (!dlg.open) return;
    dlg.classList.remove('is-busy');
    if (!blob) { warn('The photo did not take. Please try again.', 3000); return; }
    const type = blob.type || 'image/jpeg';
    const ext = type === 'image/png' ? 'png' : 'jpg';
    const d = new Date(), two = (n) => String(n).padStart(2, '0');
    const stamp = d.getFullYear() + two(d.getMonth() + 1) + two(d.getDate()) + '_' + two(d.getHours()) + two(d.getMinutes()) + two(d.getSeconds());
    const file = new File([blob], `photo_${stamp}_${shots.length + 1}.${ext}`, { type, lastModified: Date.now() });
    if (!target.multiple) { hand([file]); return; }
    shots.push({ file, url: URL.createObjectURL(blob) });
    paintShots();
}

function paintShots() {
    const b = $('[data-pcam="done"]');
    b.hidden = !shots.length;
    if (!shots.length) return;
    b.querySelector('img').src = shots[shots.length - 1].url;
    b.querySelector('b').textContent = 'Use ' + shots.length;
}

function clearShots() {
    shots.forEach((s) => URL.revokeObjectURL(s.url));
    shots = [];
    if (dlg) paintShots();
}

/** The photos go into the input the page gave us, as if the camera app sent them. */
function hand(files) {
    const input = target;
    shut();
    if (!input || !files.length) return;
    try {
        const dt = new DataTransfer();
        files.forEach((f) => dt.items.add(f));
        input.files = dt.files;
    } catch (_) {
        window.toast?.('This browser cannot pass the photo on. Use the phone camera instead.', 'error');
        return;
    }
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}

/** Closed with nothing taken: the page hears "cancel", as from the camera app. */
function leave() {
    if (shots.length && Date.now() > armedUntil) {
        armedUntil = Date.now() + 3000;
        warn('Tap ✕ again to throw away ' + shots.length + (shots.length === 1 ? ' photo' : ' photos'), 3000);
        return;
    }
    const input = target;
    shut();
    input?.dispatchEvent(new Event('cancel', { bubbles: true }));
}

function shut(now = false) {
    stop();
    clearShots();
    target = null;
    busy = false;
    if (!dlg || !dlg.open) return;
    dlg.classList.remove('is-open', 'is-busy');
    const done = () => { try { dlg.close(); } catch (_) { /* already closed */ } };
    clearTimeout(closeTimer);
    if (now || reduced()) done(); else closeTimer = setTimeout(done, 280);
}

/** Back to the phone's camera app, and this phone keeps that choice. */
function toPhoneCamera() {
    const input = target;
    store.drop(MODE);
    // Shut at once: the input sits under the modal camera until it closes.
    shut(true);
    if (!input) return;
    passThrough = input;
    input.click();
}

/* ---- the trips out to the phone's camera app ---- */
let passThrough = null;   // the one click let through to the camera app on purpose
let awayFrom = null;      // the input whose trip is under way
let silentTimer = null;

function offer(input) {
    if (!canStream() || !input.isConnected) return;
    note('No photo came back from the camera. If it could not save the photo, take it with the camera inside anee.io instead.', {
        quiet: 'Not now',
        go: 'Use the app camera',
        onGo: () => { store.set(MODE, 'app'); open(input); },
        ms: 12000,
    });
}

document.addEventListener('click', (e) => {
    const input = e.target;
    if (!isPhotoCapture(input) || !isPhone()) return;
    if (input === passThrough) passThrough = null;
    else if (usesAppCamera() && canStream()) { e.preventDefault(); open(input); return; }
    awayFrom = input;
    clearTimeout(silentTimer);
    store.set(AWAY, String(Date.now()));
}, true);

document.addEventListener('change', (e) => {
    if (!awayFrom || e.target !== awayFrom) return;
    awayFrom = null;
    clearTimeout(silentTimer);
    store.drop(AWAY);
    closeNote();   // a slow photo that arrived after the offer went up
}, true);

document.addEventListener('cancel', (e) => {
    if (!awayFrom || e.target !== awayFrom) return;
    const input = awayFrom;
    awayFrom = null;
    clearTimeout(silentTimer);
    store.drop(AWAY);
    offer(input);
}, true);

// Back on the page, and the page lived: wipe the mark. If neither a photo nor
// a "cancel" follows, the browser lost the camera's answer on the way back.
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState !== 'visible' || !awayFrom) return;
    store.drop(AWAY);
    const input = awayFrom;
    clearTimeout(silentTimer);
    silentTimer = setTimeout(() => {
        if (awayFrom !== input) return;
        awayFrom = null;
        offer(input);
    }, SILENT_MS);
});

// Leaving the page the ordinary way is not the phone closing it.
window.addEventListener('pagehide', () => { if (awayFrom) store.drop(AWAY); });

/* A page that opens with a fresh mark was closed while the camera was out. */
function closedWhileAway() {
    const at = Number(store.get(AWAY) || 0);
    if (!at) return;
    store.drop(AWAY);
    if (Date.now() - at > FRESH_MS || !isPhone() || !canStream()) return;
    store.set(MODE, 'app');
    note('The phone closed anee.io while its camera was open, so that photo was lost. From now on this phone takes photos with the camera inside anee.io, which keeps the app open. Please take the photo again.');
}
if (document.readyState === 'complete') setTimeout(closedWhileAway, 400);
else window.addEventListener('load', () => setTimeout(closedWhileAway, 400), { once: true });

// For a probe, and for any page that wants to open it on purpose.
window.aneeCamera = { open: (input) => isPhotoCapture(input) && canStream() && open(input), usesAppCamera };
