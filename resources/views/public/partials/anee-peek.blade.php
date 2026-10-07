{{-- Anee peeks in (owner, 2026-10-07): as the homepage hero appears she
     leans in from the right edge of the screen, looks around, notices the
     visitor, waves with a big smile, and leaves. Drawn on a canvas, frame by
     frame (no video, no picture): every shape below is Anee as the avatar
     draws her, green hair in two leafy bunches with yellow flowers, the
     sprout on top, green eyes, the headset and the green jacket with the
     gold collar. Once per page view, about five and a half seconds, never
     in anyone's way (no pointer events) and not at all under reduced motion. --}}
<canvas class="hp-peek" data-peek aria-hidden="true"></canvas>

@once
@push('head')
<style>
    .hp-peek { position: absolute; right: 0; top: 0; z-index: 4; width: 280px; height: 330px; pointer-events: none; visibility: hidden;
        -webkit-mask-image: linear-gradient(180deg, #000 86%, transparent); mask-image: linear-gradient(180deg, #000 86%, transparent); }
    .hp-peek.is-on { visibility: visible; }
    @media (max-width: 639.98px) { .hp-peek { width: 176px; height: 207px; } }
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
    const W = 280, H = 330, RAD = Math.PI / 180;
    let scale = 1, dpr = 1;

    const C = {
        hair: '#5aa531', hairD: '#3c7d1d', hairL: '#93d566', bunch: '#67b23a', sprout: '#7fc64c',
        skin: '#ffe3cd', skinD: '#f2c1a0', line: 'rgba(70, 45, 25, .45)',
        jacket: '#3e7f26', jacketD: '#2b5c18', gold: '#dcae2e',
        petal: '#f8ca35', petalD: '#dd9c19', bud: '#e5871b',
        lash: '#283a19', mouth: '#a8343f', tongue: '#f28b91', set: '#7cbc4b', setD: '#4e8b2c',
    };

    // Timing helpers.
    const clamp = (v, a = 0, b = 1) => Math.max(a, Math.min(b, v));
    const seg = (t, a, b) => clamp((t - a) / (b - a));
    const lerp = (a, b, k) => a + (b - a) * k;
    const eOut = (k) => 1 - Math.pow(1 - k, 3);
    const eInOut = (k) => (k < .5 ? 4 * k * k * k : 1 - Math.pow(-2 * k + 2, 3) / 2);
    const eBack = (k) => 1 + 2.70158 * Math.pow(k - 1, 3) + 1.70158 * Math.pow(k - 1, 2);
    const eInBack = (k) => 2.70158 * k * k * k - 1.70158 * k * k;
    const END = 5.5;

    // Where she is and what her face does at second t.
    const pose = (t) => {
        const s = { off: 300, rot: -20, lift: 0, look: 0, open: 1, brow: 0, mouth: 0, blush: .45, arm: 0, wave: 0, happy: 0, bang: 0, spark: 0 };
        // In from the edge, only half of her: a peek.
        const a = eOut(seg(t, 0, .7));
        s.off = lerp(300, 104, a); s.rot = lerp(-22, -13, a);
        // Looking around, a little bob.
        if (t > .7 && t < 1.6) { const k = (t - .7) / .9; s.look = -2 - Math.sin(k * Math.PI * 2) * 5; s.lift = Math.sin(k * Math.PI * 3) * 1.6; }
        // She notices you: eyes wide, brows up, a hop, the "!".
        if (t >= 1.6) {
            const n = seg(t, 1.6, 1.85);
            s.look = lerp(-2, 0, n); s.open = 1 + .2 * Math.sin(n * Math.PI);
            s.brow = Math.sin(seg(t, 1.6, 2.3) * Math.PI) * 4;
            s.lift = -8 * Math.sin(n * Math.PI);
            s.bang = eBack(seg(t, 1.6, 1.8)) * (1 - seg(t, 2.25, 2.5));
        }
        // She leans all the way in and smiles.
        if (t >= 1.85) {
            const l = eBack(seg(t, 1.85, 2.45));
            s.off = lerp(104, 0, l); s.rot = lerp(-13, -5, l);
            s.mouth = eOut(seg(t, 1.95, 2.25));
        }
        // The wave: up, three and a half waves, down.
        s.arm = eOut(seg(t, 2.15, 2.55)) * (1 - eInOut(seg(t, 4.3, 4.65)));
        if (t > 2.45 && t < 4.4) s.wave = Math.sin((t - 2.45) * Math.PI * 2 * 1.8) * 24 * Math.min(1, (t - 2.45) * 4);
        s.happy = seg(t, 2.7, 2.85) * (1 - seg(t, 3.7, 3.85));
        s.blush = .45 + .45 * seg(t, 2.3, 2.8);
        s.spark = seg(t, 2.55, 2.85) * (1 - seg(t, 4.1, 4.5));
        // And off she goes.
        if (t >= 4.6) {
            const o = eInBack(seg(t, 4.6, END - .1));
            s.off = lerp(0, 330, o); s.rot = lerp(-5, -18, o); s.mouth = 1 - .5 * seg(t, 4.6, 4.8);
        }
        return s;
    };

    // ---- the drawing ---------------------------------------------------
    const path = (fill, stroke, w = 1.6) => {
        if (fill) { ctx.fillStyle = fill; ctx.fill(); }
        if (stroke) { ctx.strokeStyle = stroke; ctx.lineWidth = w; ctx.stroke(); }
    };
    const leaf = (x, y, len, wid, ang, fill, stroke) => {
        ctx.save(); ctx.translate(x, y); ctx.rotate(ang * RAD);
        ctx.beginPath(); ctx.moveTo(0, 0);
        ctx.quadraticCurveTo(wid, -len * .55, 0, -len); ctx.quadraticCurveTo(-wid, -len * .55, 0, 0);
        path(fill, stroke, 1.4);
        ctx.restore();
    };
    const bunch = (x, y, dir) => {
        // A leafy bunch of hair: leaves fanned out and up, away from the face.
        [[-62, 40], [-34, 46], [-6, 44], [22, 36], [-86, 30], [48, 26]].forEach(([a, len], i) => {
            leaf(x, y, len, 13, a * dir + (dir < 0 ? -10 : 10), i % 2 ? C.bunch : C.hair, C.hairD);
        });
        ctx.beginPath(); ctx.ellipse(x, y, 17, 15, 0, 0, Math.PI * 2); path(C.hair, null);
    };
    const flower = (x, y, r) => {
        for (let i = 0; i < 5; i++) {
            ctx.save(); ctx.translate(x, y); ctx.rotate((i * 72 - 90) * RAD);
            ctx.beginPath(); ctx.ellipse(0, -r * .95, r * .62, r * .82, 0, 0, Math.PI * 2); path(C.petal, C.petalD, 1.2);
            ctx.restore();
        }
        ctx.beginPath(); ctx.arc(x, y, r * .5, 0, Math.PI * 2); path(C.bud, C.petalD, 1);
    };
    const eye = (cx, cy, s, side) => {
        if (s.happy > .5) {
            ctx.beginPath(); ctx.moveTo(cx - 13, cy + 4); ctx.quadraticCurveTo(cx, cy - 13, cx + 13, cy + 4);
            ctx.lineCap = 'round'; path(null, C.lash, 4.2); return;
        }
        const ry = 17.5 * s.open * (1 - s.happy * 1.6);
        ctx.save();
        ctx.beginPath(); ctx.ellipse(cx, cy, 14, Math.max(2, ry), 0, 0, Math.PI * 2); path('#fff', null); ctx.clip();
        const ix = cx + s.look, iy = cy + 2;
        const g = ctx.createRadialGradient(ix, iy + 3, 2, ix, iy, 14);
        g.addColorStop(0, '#a6e07a'); g.addColorStop(.55, '#4f9d2c'); g.addColorStop(1, '#1f4d12');
        ctx.beginPath(); ctx.ellipse(ix, iy, 11.5, 14, 0, 0, Math.PI * 2); path(g, null);
        ctx.beginPath(); ctx.ellipse(ix, iy + 1, 5.4, 7, 0, 0, Math.PI * 2); path('#163a0d', null);
        ctx.beginPath(); ctx.arc(ix - 4.2, iy - 5.5, 3.8, 0, Math.PI * 2); path('#fff', null);
        ctx.beginPath(); ctx.arc(ix + 4.2, iy + 5, 1.9, 0, Math.PI * 2); path('rgba(255,255,255,.9)', null);
        ctx.restore();
        // The upper lash, heavier, with a flick at the outer corner.
        ctx.lineCap = 'round';
        ctx.beginPath(); ctx.ellipse(cx, cy, 14.8, Math.max(2, ry) + .6, 0, Math.PI * 1.06, Math.PI * 1.94); path(null, C.lash, 3.6);
        ctx.beginPath(); ctx.moveTo(cx + side * 13.5, cy - ry * .45); ctx.lineTo(cx + side * 19, cy - ry * .75); path(null, C.lash, 2.6);
        // The brow.
        ctx.beginPath(); ctx.moveTo(cx - 9, cy - 25 - s.brow); ctx.quadraticCurveTo(cx, cy - 29 - s.brow, cx + 9, cy - 25 - s.brow); path(null, C.hairD, 2.4);
    };
    const mouth = (s) => {
        const mx = 150, my = 191;
        ctx.lineCap = 'round';
        if (s.mouth < .06) {
            ctx.beginPath(); ctx.moveTo(mx - 8, my); ctx.quadraticCurveTo(mx, my + 6, mx + 8, my); path(null, '#8c3a3a', 2.4); return;
        }
        const w = 8 + 5 * s.mouth, h = 3 + 9 * s.mouth;
        ctx.save();
        ctx.beginPath(); ctx.moveTo(mx - w, my - 1);
        ctx.quadraticCurveTo(mx, my + 1.5, mx + w, my - 1);
        ctx.quadraticCurveTo(mx + w * .85, my + h * 1.35, mx, my + h * 1.45);
        ctx.quadraticCurveTo(mx - w * .85, my + h * 1.35, mx - w, my - 1);
        path(C.mouth, null); ctx.clip();
        ctx.beginPath(); ctx.ellipse(mx, my + h * 1.35, w * .62, h * .55, 0, 0, Math.PI * 2); path(C.tongue, null);
        ctx.beginPath(); ctx.rect(mx - w, my - 2, w * 2, 2.6 * s.mouth); path('#fff', null);
        ctx.restore();
        ctx.beginPath(); ctx.moveTo(mx - w, my - 1); ctx.quadraticCurveTo(mx, my + 1.5, mx + w, my - 1);
        ctx.quadraticCurveTo(mx + w * .85, my + h * 1.35, mx, my + h * 1.45); ctx.quadraticCurveTo(mx - w * .85, my + h * 1.35, mx - w, my - 1);
        path(null, '#7a2730', 1.4);
    };
    const arm = (s) => {
        // Her right arm (your left), from the shoulder: down at rest, up beside her face to wave.
        const sx = 104, sy = 252;
        const ex = lerp(98, 70, s.arm), ey = lerp(330, 236, s.arm);
        const fore = lerp(170, -8, s.arm) + s.wave;
        ctx.lineCap = 'round';
        ctx.beginPath(); ctx.moveTo(sx, sy); ctx.lineTo(ex, ey); path(null, C.jacketD, 25);
        ctx.beginPath(); ctx.moveTo(sx, sy); ctx.lineTo(ex, ey); path(null, C.jacket, 21);
        ctx.save(); ctx.translate(ex, ey); ctx.rotate(fore * RAD);
        ctx.beginPath(); ctx.moveTo(0, 0); ctx.lineTo(0, -52); path(null, C.jacketD, 21);
        ctx.beginPath(); ctx.moveTo(0, 0); ctx.lineTo(0, -52); path(null, C.jacket, 17);
        ctx.beginPath(); ctx.moveTo(-8.5, -50); ctx.lineTo(8.5, -50); path(null, C.gold, 4);
        // The hand: a palm, four fingers spread, the thumb out.
        [[-8.5, 13], [-3, 16], [2.6, 15.5], [8, 12.5]].forEach(([fx, fl], i) => {
            const spread = (i - 1.5) * 9;
            ctx.save(); ctx.translate(fx * .6, -66); ctx.rotate(spread * RAD);
            ctx.beginPath(); ctx.moveTo(0, 0); ctx.lineTo(0, -fl); path(null, C.skinD, 7.4);
            ctx.beginPath(); ctx.moveTo(0, 0); ctx.lineTo(0, -fl); path(null, C.skin, 5.4);
            ctx.restore();
        });
        ctx.save(); ctx.translate(-9, -60); ctx.rotate(-58 * RAD);
        ctx.beginPath(); ctx.moveTo(0, 0); ctx.lineTo(0, -11); path(null, C.skinD, 7.6);
        ctx.beginPath(); ctx.moveTo(0, 0); ctx.lineTo(0, -11); path(null, C.skin, 5.6);
        ctx.restore();
        ctx.beginPath(); ctx.ellipse(0, -62, 11.5, 12, 0, 0, Math.PI * 2); path(C.skin, C.skinD, 1.4);
        ctx.restore();
    };
    const star = (x, y, r, rot, alpha) => {
        ctx.save(); ctx.translate(x, y); ctx.rotate(rot); ctx.globalAlpha = alpha;
        ctx.beginPath();
        for (let i = 0; i < 4; i++) {
            ctx.rotate(Math.PI / 2);
            ctx.lineTo(0, -r); ctx.quadraticCurveTo(0, 0, r * .28, -r * .28);
        }
        path('#fff6c2', '#f5c518', 1.2);
        ctx.restore();
    };

    const draw = (t) => {
        const s = pose(t);
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.clearRect(0, 0, cv.width, cv.height);
        ctx.setTransform(dpr * scale, 0, 0, dpr * scale, 0, 0);
        ctx.save();
        ctx.translate(s.off, s.lift);
        // Leaning in around the corner: the turn is low on the right edge.
        ctx.translate(262, 340); ctx.rotate(s.rot * RAD); ctx.translate(-262, -340);

        // A soft glow, so she reads on the dark photo.
        const glow = ctx.createRadialGradient(150, 160, 20, 150, 170, 150);
        glow.addColorStop(0, 'rgba(245, 220, 120, .32)'); glow.addColorStop(1, 'rgba(245, 220, 120, 0)');
        ctx.fillStyle = glow; ctx.fillRect(0, 10, 300, 320);

        // The bunches and the back of her hair.
        bunch(84, 98, -1); bunch(216, 98, 1);
        ctx.beginPath(); ctx.ellipse(150, 142, 73, 71, 0, 0, Math.PI * 2); path(C.hair, C.hairD, 1.6);
        // The jacket, the neck, the gold collar.
        // (The jacket runs on past the canvas, so no edge of it ever shows.)
        ctx.beginPath(); ctx.moveTo(24, 420); ctx.bezierCurveTo(36, 266, 86, 238, 128, 224); ctx.lineTo(172, 224);
        ctx.bezierCurveTo(216, 238, 268, 262, 320, 292); ctx.lineTo(440, 330); ctx.lineTo(440, 420); ctx.closePath(); path(C.jacket, C.jacketD, 2);
        ctx.beginPath(); ctx.moveTo(137, 196); ctx.lineTo(163, 196); ctx.lineTo(165, 230); ctx.lineTo(135, 230); ctx.closePath(); path(C.skinD, null);
        ctx.beginPath(); ctx.moveTo(124, 221); ctx.lineTo(150, 239); ctx.lineTo(150, 256); ctx.lineTo(116, 236); ctx.closePath(); path(C.jacketD, C.gold, 2.6);
        ctx.beginPath(); ctx.moveTo(176, 221); ctx.lineTo(150, 239); ctx.lineTo(150, 256); ctx.lineTo(184, 236); ctx.closePath(); path(C.jacketD, C.gold, 2.6);
        ctx.beginPath(); ctx.moveTo(150, 256); ctx.lineTo(150, 420); path(null, C.gold, 2.2);
        // The face.
        ctx.beginPath(); ctx.moveTo(96, 138);
        ctx.bezierCurveTo(96, 96, 204, 96, 204, 138);
        ctx.bezierCurveTo(205, 176, 182, 205, 150, 209);
        ctx.bezierCurveTo(118, 205, 95, 176, 96, 138);
        path(C.skin, C.line, 1.6);
        // Cheeks, nose, mouth, eyes.
        ctx.fillStyle = `rgba(255, 110, 125, ${.38 * s.blush})`;
        ctx.beginPath(); ctx.ellipse(115, 180, 10.5, 5.5, 0, 0, Math.PI * 2); ctx.fill();
        ctx.beginPath(); ctx.ellipse(185, 180, 10.5, 5.5, 0, 0, Math.PI * 2); ctx.fill();
        ctx.lineCap = 'round';
        [[110, 177], [115, 176], [120, 177], [180, 177], [185, 176], [190, 177]].forEach(([bx, by]) => {
            ctx.beginPath(); ctx.moveTo(bx, by); ctx.lineTo(bx - 2, by + 4); path(null, `rgba(230, 80, 95, ${.5 * s.blush})`, 1.3);
        });
        ctx.beginPath(); ctx.moveTo(150, 174); ctx.lineTo(151.5, 178); path(null, C.skinD, 1.8);
        mouth(s);
        eye(126, 156, s, -1); eye(174, 156, s, 1);
        // The fringe, pointed, over the brow; the side locks down to the jaw.
        ctx.beginPath(); ctx.moveTo(90, 146);
        ctx.bezierCurveTo(84, 96, 118, 78, 150, 78); ctx.bezierCurveTo(182, 78, 216, 96, 210, 146);
        const tips = [[202, 128], [196, 146], [186, 122], [176, 142], [164, 118], [152, 140], [140, 118], [126, 142], [114, 122], [104, 144], [97, 126]];
        tips.forEach(([x, y], i) => { const [px, py] = i ? tips[i - 1] : [210, 146]; ctx.quadraticCurveTo((px + x) / 2 + 2, (py + y) / 2 - 2, x, y); });
        ctx.quadraticCurveTo(93, 134, 90, 146); ctx.closePath(); path(C.hair, C.hairD, 1.6);
        ctx.beginPath(); ctx.moveTo(93, 128); ctx.quadraticCurveTo(84, 172, 92, 214); ctx.quadraticCurveTo(100, 186, 108, 150); ctx.closePath(); path(C.hair, C.hairD, 1.4);
        ctx.beginPath(); ctx.moveTo(207, 128); ctx.quadraticCurveTo(216, 172, 208, 214); ctx.quadraticCurveTo(200, 186, 192, 150); ctx.closePath(); path(C.hair, C.hairD, 1.4);
        ctx.lineCap = 'round';
        [[118, 92, 132, 86], [158, 86, 174, 92]].forEach(([a, b, c, d]) => { ctx.beginPath(); ctx.moveTo(a, b); ctx.quadraticCurveTo((a + c) / 2, b - 6, c, d); path(null, C.hairL, 3); });
        // The headset over her hair, its cup and microphone.
        ctx.beginPath(); ctx.arc(150, 142, 70, 200 * RAD, 340 * RAD); path(null, C.set, 5.5);
        ctx.beginPath(); ctx.roundRect ? ctx.roundRect(203, 150, 17, 28, 7) : ctx.rect(203, 150, 17, 28); path(C.set, C.setD, 1.8);
        ctx.beginPath(); ctx.moveTo(207, 178); ctx.quadraticCurveTo(200, 199, 175, 199); path(null, C.setD, 3.4);
        ctx.beginPath(); ctx.ellipse(172, 199, 6, 4, 0, 0, Math.PI * 2); path(C.setD, null);
        // The sprout on top, and the flowers.
        ctx.beginPath(); ctx.moveTo(150, 82); ctx.quadraticCurveTo(147, 70, 151, 60); path(null, C.hairD, 3);
        leaf(151, 61, 24, 11, -58, C.sprout, C.hairD); leaf(151, 61, 24, 11, 58, C.sprout, C.hairD);
        flower(106, 104, 8); flower(194, 104, 8);
        // The arm, in front of it all.
        if (s.arm > 0.01) arm(s);
        // "!" as she notices you.
        if (s.bang > 0.01) {
            ctx.save(); ctx.translate(222, 62); ctx.scale(s.bang, s.bang); ctx.rotate(10 * RAD);
            ctx.beginPath(); ctx.arc(0, 0, 14, 0, Math.PI * 2); path('#f5c518', '#fff', 2.6);
            ctx.fillStyle = '#1a1a1a'; ctx.font = '900 19px system-ui, sans-serif'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle'; ctx.fillText('!', 0, 1);
            ctx.restore();
        }
        // Sparkles while she waves.
        if (s.spark > 0.01) {
            [[58, 96, 9, 0], [236, 58, 7, 1.3], [248, 160, 8, 2.1], [54, 208, 6, 3.4], [112, 40, 6, 4.2]].forEach(([x, y, r, ph]) => {
                const tw = .6 + .4 * Math.sin(t * 9 + ph * 2);
                star(x, y, r * tw, t * 1.6 + ph, s.spark * tw);
            });
        }
        ctx.restore();
    };

    const place = () => {
        // Low on the first screen, at the right edge, clear of the header.
        const r = hero.getBoundingClientRect();
        const ch = cv.offsetHeight;
        const bottom = Math.min(r.height, innerHeight - Math.max(0, r.top));
        // Her jacket runs off the bottom of the first screen, as if she stood just past its corner.
        cv.style.top = Math.max(80, bottom - ch) + 'px';
        const b = cv.getBoundingClientRect();
        dpr = Math.min(2, devicePixelRatio || 1);
        cv.width = Math.round(b.width * dpr); cv.height = Math.round(b.height * dpr);
        scale = b.width / W;
    };

    const run = () => {
        if (scrollY > 200 || document.hidden) { cv.remove(); return; }
        place();
        cv.classList.add('is-on');
        let t0 = null;
        const frame = (now) => {
            if (t0 === null) t0 = now;
            const t = (now - t0) / 1000;
            if (t >= END) { cv.remove(); return; }
            draw(t);
            requestAnimationFrame(frame);
        };
        requestAnimationFrame(frame);
    };
    // As soon as the hero shows: after the page's first paint veil lifts.
    const start = () => setTimeout(run, 650);
    const root = document.documentElement;
    if (!root.classList.contains('booting')) start();
    else {
        const mo = new MutationObserver(() => { if (!root.classList.contains('booting')) { mo.disconnect(); start(); } });
        mo.observe(root, { attributes: true, attributeFilter: ['class'] });
    }
})();
</script>
@endpush
@endonce
