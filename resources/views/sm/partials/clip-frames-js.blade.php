{{-- Frames for the clips that have none, wherever clips are shown.

     The media picker learned this first; the galleries speak the same
     script now. Any element carrying data-needs-frame="<path>" (and
     data-clip-url="<url>" for the browser fallback) gets a poster: the
     server is asked first — it keeps every frame ever cut, so a clip is
     only ever cut once — and when the server cannot, this browser draws a
     frame itself and hands it in so the next phone is shown a picture.

     While a tile waits or is being cut it wears a small ring (the
     data-needs-frame attribute and the .clip-cutting class are what the
     CSS keys on). One clip at a time, because cutting a frame is a second
     of somebody's server.

     How the finished frame lands is the tile's choice:
       data-frame-mode="poster"    → set poster= on the tile's <video>
       data-frame-replace="video"  → the coaxed <video> goes, an <img> comes
       otherwise                   → an <img> is appended to the tile

     Guarded singleton; exposes window.smClipFrames(root?). --}}
@once
<style>
    [data-needs-frame], .clip-cutting { position: relative; }
    [data-needs-frame]::after, .clip-cutting::after {
        content: ''; position: absolute; top: 50%; left: 50%; width: 1.4rem; height: 1.4rem;
        margin: -0.7rem 0 0 -0.7rem; border-radius: 999px; z-index: 3; pointer-events: none;
        border: 2.5px solid rgb(255 255 255 / .45); border-top-color: #fff;
        filter: drop-shadow(0 1px 3px rgb(0 0 0 / .45));
        animation: clipCutSpin .8s linear infinite;
    }
    @keyframes clipCutSpin { to { transform: rotate(360deg); } }
    @media (prefers-reduced-motion: reduce) {
        [data-needs-frame]::after, .clip-cutting::after { animation-duration: 2.4s; }
    }
    .clip-frame { position: absolute; inset: 0; width: 100%; height: 100%;
        object-fit: cover; z-index: 1; }
</style>
<script>
(function () {
    if (window.__smClipFramesBound) return;
    window.__smClipFramesBound = true;

    const POSTER_URL = @json(route('sm.media-picker.poster'));
    const POSTER_SAVE_URL = @json(route('sm.media-picker.poster-save'));

    /* A frame drawn here, when the server has no way to draw one. Same
     * canvas walk the picker uses; a clip on a foreign host taints the
     * canvas and this quietly gives up. */
    function grabFrame(url) {
        /* Twice if it has to: with CORS first (only a CORS-clean video can be
         * read back off a canvas), and plainly when that load is refused —
         * a tainted plain frame throws at toDataURL and gives up as before. */
        /* And never a blank one: each draw is checked for actual content
         * (sampled luminance spread — Chrome can paint black before a big
         * clip's first frame composites, and phone clips fade in from
         * black), and a blank draw walks forward through the clip before
         * answering. All blank → null; a black rectangle kept as a frame
         * would be kept forever. */
        const blank = (ctx, w, h) => {
            try {
                const d = ctx.getImageData(0, 0, w, h).data;
                let mn = 255, mx = 0;
                const step = Math.max(1, Math.floor(d.length / 4 / 400)) * 4;
                for (let i = 0; i < d.length; i += step) {
                    const l = (d[i] * 3 + d[i + 1] * 6 + d[i + 2]) / 10;
                    if (l < mn) mn = l;
                    if (l > mx) mx = l;
                }
                return (mx - mn) < 14;
            } catch (_) { return false; }               // tainted: toDataURL will say so
        };
        const attempt = (cors) => new Promise((resolve) => {
            let done = false;
            const v = document.createElement('video');
            const finish = (val) => { if (!done) { done = true; try { v.src = ''; } catch (_) {} resolve(val); } };
            v.muted = true; v.playsInline = true; v.preload = 'metadata';
            if (cors) v.crossOrigin = 'anonymous';
            v.src = url + (url.includes('#') ? '' : '#t=0.1');
            v.addEventListener('error', () => finish(null), { once: true });
            setTimeout(() => finish(null), 20000);
            const seek = (t) => new Promise((r) => {
                v.addEventListener('seeked', () => r(), { once: true });
                setTimeout(r, 4000);
                try { v.currentTime = t; } catch (_) { r(); }
            });
            v.addEventListener('loadeddata', async () => {
                try {
                    const w = 640;
                    const h = Math.max(1, Math.round((v.videoHeight || 360) * w / (v.videoWidth || 640)));
                    const c = document.createElement('canvas');
                    c.width = w; c.height = h;
                    const ctx = c.getContext('2d');
                    const dur = Number.isFinite(v.duration) ? v.duration : 0;
                    let good = false;
                    for (const t of [null, 1, 3, 8]) {
                        if (t !== null) {
                            if (dur && t >= dur - 0.2) break;
                            await seek(t);
                            if (done) return;
                        }
                        ctx.drawImage(v, 0, 0, w, h);
                        if (!blank(ctx, w, h)) { good = true; break; }
                    }
                    if (!good) { finish(null); return; }
                    const data = c.toDataURL('image/jpeg', 0.8);
                    finish(data && data.length > 2048 ? data : null);
                } catch (_) { finish(null); }
            }, { once: true });
        });

        return attempt(true).then((shot) => shot || attempt(false));
    }

    const post = (url, body) => fetch(url, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
        body: JSON.stringify(body),
    }).then((r) => r.ok ? r.json() : null).catch(() => null);

    let running = false;
    window.smClipFrames = async function (root) {
        if (running) return;   // the queue drains everything, wherever it is
        running = true;
        try {
            for (;;) {
                const tile = (root && root.querySelector ? root : document).querySelector('[data-needs-frame]');
                if (!tile) return;
                const path = tile.getAttribute('data-needs-frame');
                const clipUrl = tile.getAttribute('data-clip-url') || '';
                tile.removeAttribute('data-needs-frame');   // asked; never twice
                tile.classList.add('clip-cutting');
                try {
                    const res = await post(POSTER_URL, { path });
                    let url = res && res.data && res.data.posterUrl;
                    if (!url && clipUrl) {
                        const shot = await grabFrame(clipUrl);
                        if (shot) {
                            const saved = await post(POSTER_SAVE_URL, { path, image: shot });
                            url = (saved && saved.data && saved.data.posterUrl) || shot;
                        }
                    }
                    if (url) {
                        if (tile.getAttribute('data-frame-mode') === 'poster') {
                            tile.querySelector('video')?.setAttribute('poster', url);
                        } else {
                            if (tile.getAttribute('data-frame-replace') === 'video') {
                                tile.querySelector('video')?.remove();
                            }
                            const img = document.createElement('img');
                            img.className = 'clip-frame';
                            img.src = url; img.alt = ''; img.loading = 'lazy'; img.decoding = 'async';
                            img.onload = () => img.classList.add('is-loaded');
                            img.onerror = () => img.remove();
                            tile.prepend(img);
                        }
                    }
                } catch (_) { /* the placeholder stays; it is not wrong */ }
                finally { tile.classList.remove('clip-cutting'); }
            }
        } finally {
            running = false;
        }
    };

    const start = () => window.smClipFrames();
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
</script>
@endonce
