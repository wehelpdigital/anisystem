{{-- Anee peeks in (owner, 2026-10-07): as the homepage hero appears she
     leans in from the right edge of the screen, looks around, notices the
     visitor, waves with a big smile, and leaves.

     Drawn on a canvas, frame by frame, from her own art: the 23 poses in
     images/anee/peek.webp are cut from her hello film (the walk up, the
     "oh!", the wave, the happy close) and packed in a 6 wide grid of
     334 px squares, each scaled so her head is the same size and in the
     same place. The leaning in, the looking around, the hop, the "!",
     the sparkles and the leaving are all done here. Once per page view,
     about six seconds, never in anyone's way (no pointer events) and not at
     all under reduced motion or once the visitor has scrolled on. --}}
<canvas class="hp-peek" data-peek data-sheet="{{ asset('images/anee/peek.webp') }}?v=1" aria-hidden="true"></canvas>

@once
@push('head')
<style>
    .hp-peek { position: absolute; right: 0; top: 0; z-index: 4; width: 300px; height: 300px; pointer-events: none; visibility: hidden;
        -webkit-mask-image: linear-gradient(180deg, #000 80%, transparent); mask-image: linear-gradient(180deg, #000 80%, transparent); }
    .hp-peek.is-on { visibility: visible; }
    @media (max-width: 639.98px) { .hp-peek { width: 190px; height: 190px; } }
    @media (prefers-reduced-motion: reduce) { .hp-peek { display: none; } }
</style>
@endpush
@push('scripts')
<script>
(() => {
    const cv = document.querySelector('[data-peek]');
    if (!cv || !cv.getContext || matchMedia('(prefers-reduced-motion: reduce)').matches) { cv?.remove(); return; }
    const hero = cv.closest('[data-hero]') || cv.parentElement;
    const ctx = cv.getContext('2d');
    const FW = 334, COLS = 6, RAD = Math.PI / 180;
    // The poses in the sheet, in order.
    const OH = 0, NEAR = 1, SMILE = 2, ARM = 3, WAVE = [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17], DOWN = [18, 19, 20], HAPPY = 21, HAPPY2 = 22;
    const END = 6.2;
    let size = 300, dpr = 1;

    const clamp = (v, a = 0, b = 1) => Math.max(a, Math.min(b, v));
    const seg = (t, a, b) => clamp((t - a) / (b - a));
    const lerp = (a, b, k) => a + (b - a) * k;
    const eOut = (k) => 1 - Math.pow(1 - k, 3);
    const eBack = (k) => 1 + 2.70158 * Math.pow(k - 1, 3) + 1.70158 * Math.pow(k - 1, 2);
    const eInBack = (k) => 2.70158 * k * k * k - 1.70158 * k * k;

    // Where she is and which pose, at second t. x is how far she sits out
    // past the right edge (in frame widths), rot her lean, y a hop.
    const pose = (t) => {
        const s = { x: .62, rot: -16, y: 0, frame: NEAR, bang: 0, spark: 0, glow: 1 };
        // In from the edge, only her face and a shoulder: a peek.
        s.x = lerp(1.05, .5, eOut(seg(t, 0, .75)));
        s.glow = seg(t, 0, .6);
        // Looking around.
        if (t > .75 && t < 1.55) { const k = (t - .75) / .8; s.rot = -16 + Math.sin(k * Math.PI * 2) * 4; s.y = Math.sin(k * Math.PI * 4) * -2; }
        // She notices you: the "oh!", a hop, the "!".
        if (t >= 1.55) {
            s.frame = OH;
            s.y = -14 * Math.sin(seg(t, 1.55, 1.85) * Math.PI);
            s.bang = eBack(seg(t, 1.55, 1.75)) * (1 - seg(t, 2.3, 2.5));
        }
        // She leans all the way in, and smiles.
        if (t >= 1.95) {
            const k = eBack(seg(t, 1.95, 2.45));
            s.x = lerp(.5, .04, k); s.rot = lerp(-16, -3, k);
            s.frame = SMILE;
        }
        // The wave: the arm comes up, two rounds of waving, the arm comes down.
        if (t >= 2.45) s.frame = ARM;
        if (t >= 2.55) {
            const f = Math.floor((t - 2.55) * 12);
            s.frame = f < WAVE.length ? WAVE[f] : WAVE[4 + ((f - WAVE.length) % (WAVE.length - 4))];
            s.spark = seg(t, 2.6, 2.9);
        }
        if (t >= 4.45) {
            const f = Math.floor((t - 4.45) * 12);
            s.frame = f < DOWN.length ? DOWN[f] : (t < 4.95 ? HAPPY : HAPPY2);
            s.spark = 1 - seg(t, 4.45, 4.9);
        }
        // And off she goes, still smiling.
        if (t >= 5.3) {
            const k = eInBack(seg(t, 5.3, END - .1));
            s.x = lerp(.04, 1.1, k); s.rot = lerp(-3, -18, k);
            s.glow = 1 - seg(t, 5.6, END);
        }
        return s;
    };

    const star = (x, y, r, rot, alpha) => {
        ctx.save(); ctx.translate(x, y); ctx.rotate(rot); ctx.globalAlpha = alpha;
        ctx.beginPath();
        for (let i = 0; i < 4; i++) { ctx.rotate(Math.PI / 2); ctx.lineTo(0, -r); ctx.quadraticCurveTo(0, 0, r * .28, -r * .28); }
        ctx.fillStyle = '#fff6c2'; ctx.fill(); ctx.lineWidth = 1.2; ctx.strokeStyle = '#f5c518'; ctx.stroke();
        ctx.restore();
    };

    const draw = (img, t) => {
        const s = pose(t);
        const u = size / FW;   // canvas pixels per sheet pixel, before dpr
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.clearRect(0, 0, cv.width, cv.height);
        ctx.setTransform(dpr * u, 0, 0, dpr * u, 0, 0);
        // A soft light behind her, so she reads on the dark photo.
        if (s.glow > 0) {
            const g = ctx.createRadialGradient(205, 140, 10, 205, 160, 190);
            g.addColorStop(0, `rgba(250, 226, 130, ${.34 * s.glow})`); g.addColorStop(1, 'rgba(250, 226, 130, 0)');
            ctx.fillStyle = g; ctx.fillRect(0, 0, FW, FW);
        }
        ctx.save();
        // Leaning in around the corner: she turns about a point low at the right edge.
        // A little low, so the cut at her waist stays under the canvas's edge.
        ctx.translate(s.x * FW, s.y + FW * .12);
        ctx.translate(FW * .78, FW * 1.05); ctx.rotate(s.rot * RAD); ctx.translate(-FW * .78, -FW * 1.05);
        const sx = (s.frame % COLS) * FW, sy = Math.floor(s.frame / COLS) * FW;
        ctx.shadowColor = 'rgba(0, 0, 0, .35)'; ctx.shadowBlur = 14; ctx.shadowOffsetY = 6;
        ctx.drawImage(img, sx, sy, FW, FW, 0, 0, FW, FW);
        ctx.shadowColor = 'transparent';
        ctx.restore();
        // "!" over her head as she notices you (in the canvas's own place, so it never slips off).
        if (s.bang > .01) {
            ctx.save(); ctx.translate(236, 70 + s.y); ctx.scale(s.bang, s.bang); ctx.rotate(12 * RAD);
            ctx.beginPath(); ctx.arc(0, 0, 17, 0, Math.PI * 2);
            ctx.fillStyle = '#f5c518'; ctx.fill(); ctx.lineWidth = 3; ctx.strokeStyle = '#fff'; ctx.stroke();
            ctx.fillStyle = '#1a1a1a'; ctx.font = '900 23px system-ui, sans-serif'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText('!', 0, 1.5);
            ctx.restore();
        }
        // Sparkles round her hand and her face while she waves.
        if (s.spark > .01) {
            [[34, 92, 10, 0], [98, 40, 7, 1.3], [270, 70, 9, 2.1], [22, 170, 7, 3.4], [292, 150, 6, 4.2]].forEach(([x, y, r, ph]) => {
                const tw = .6 + .4 * Math.sin(t * 9 + ph * 2);
                star(x, y + FW * .08, r * tw, t * 1.6 + ph, s.spark * tw);
            });
        }
    };

    const place = () => {
        // Low on the first screen at the right edge, standing just past its corner.
        const r = hero.getBoundingClientRect();
        const bottom = Math.min(r.height, innerHeight - Math.max(0, r.top));
        cv.style.top = Math.max(80, bottom - cv.offsetHeight) + 'px';
        const b = cv.getBoundingClientRect();
        size = b.width;
        dpr = Math.min(2, devicePixelRatio || 1);
        cv.width = Math.round(b.width * dpr); cv.height = Math.round(b.height * dpr);
    };

    const run = (img) => {
        if (scrollY > 200 || document.hidden) { cv.remove(); return; }
        place();
        cv.classList.add('is-on');
        let t0 = null;
        const frame = (now) => {
            if (t0 === null) t0 = now;
            const t = (now - t0) / 1000;
            if (t >= END) { cv.remove(); return; }
            draw(img, t);
            requestAnimationFrame(frame);
        };
        requestAnimationFrame(frame);
    };

    // Her poses load after the page; she comes in once they are ready and
    // the page's first paint veil has lifted.
    const img = new Image();
    img.decoding = 'async';
    const ready = new Promise((res) => { img.onload = () => res(true); img.onerror = () => res(false); });
    const shown = new Promise((res) => {
        const root = document.documentElement;
        if (!root.classList.contains('booting')) { res(); return; }
        const mo = new MutationObserver(() => { if (!root.classList.contains('booting')) { mo.disconnect(); res(); } });
        mo.observe(root, { attributes: true, attributeFilter: ['class'] });
    });
    img.src = cv.dataset.sheet;
    Promise.all([ready, shown]).then(([ok]) => {
        if (!ok) { cv.remove(); return; }
        setTimeout(() => run(img), 600);
    });
})();
</script>
@endpush
@endonce
