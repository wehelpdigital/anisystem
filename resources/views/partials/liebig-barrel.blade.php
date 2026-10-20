{{-- LIEBIG'S BARREL, shared (2026-10-21, the owner: "show an animated barrel for the Liebig's in the output").
     NPK Plus's own barrel (its CSS and its turning engine), made mountable so the Crop Protocol's output can show one
     per lot. NPK Plus keeps its own copy on its page for now; keep the two looking alike.

     JS: const b = window.aneeBarrel.mount(hostEl, { label: "Liebig's barrel" })
           b.draw({ staves: [{ k, t, name }], rows: [{ state, ratio, pct, note }], tag, arrow, reach, say })
             state: short | right | over | noflag | dull (drawn full and pale); ratio: the share of the need met (null: not measured)
           b.enter() when it comes into view (it fills and turns), b.leave() when it is hidden. --}}
@once
@push('head')
<style>
    .lb { margin-top: .4rem; }
    .lb-stage { position: relative; height: 24.5rem; display: grid; place-items: center; perspective: 900px; perspective-origin: 50% 28%; cursor: grab; touch-action: pan-y;
        user-select: none; -webkit-user-select: none; border-radius: 1rem; outline: none; overflow: hidden;
        background: radial-gradient(70% 45% at 50% 88%, rgb(74 124 42 / .16), transparent 70%), linear-gradient(180deg, rgb(125 211 252 / .1), transparent 55%); }
    .lb-stage:focus-visible { box-shadow: 0 0 0 3px rgb(74 124 42 / .45); }
    .lb-stage.is-grab { cursor: grabbing; }
    .lb-barrel { position: relative; width: 160px; height: 190px; margin-top: 1.6rem; transform-style: preserve-3d; transform: rotateX(-16deg) rotateY(-18deg); }
    .lb-st { position: absolute; left: 50%; bottom: 0; height: 190px; transform-style: preserve-3d;
        transition: height .9s cubic-bezier(.22,1,.36,1); transition-delay: var(--d, 0ms); }
    .lb-out, .lb-in { position: absolute; inset: 0; backface-visibility: hidden; -webkit-backface-visibility: hidden; overflow: hidden; border-radius: 3px 3px 0 0; }
    .lb-out { background:
            linear-gradient(180deg, transparent calc(100% - 46px), #2f3439 calc(100% - 46px), #6b737c calc(100% - 41px), #2f3439 calc(100% - 37px), transparent calc(100% - 37px)),
            repeating-linear-gradient(90deg, transparent 0 6px, rgb(60 30 5 / .13) 6px 7px),
            linear-gradient(90deg, #6f4519 0%, #a86d34 16%, #c4874a 50%, #a86d34 84%, #6f4519 100%);
        box-shadow: inset 0 3px 0 rgb(255 255 255 / .18); }
    .lb-out::after { content: ""; position: absolute; left: 0; right: 0; bottom: 140px; height: 9px; background: linear-gradient(180deg, #2f3439, #6b737c 50%, #2f3439); }
    .lb-in { transform: rotateY(180deg); background: repeating-linear-gradient(90deg, transparent 0 6px, rgb(0 0 0 / .12) 6px 7px), linear-gradient(90deg, #4a2c0f, #6b4219 50%, #4a2c0f); }
    .lb-out b { position: absolute; left: 0; right: 0; bottom: 14px; text-align: center; font-family: var(--font-heading); font-size: .72rem; font-weight: 800; line-height: 1; color: #fff8e6;
        text-shadow: 0 1px 2px rgb(0 0 0 / .6); }
    .lb-out small { position: absolute; left: 0; right: 0; bottom: 3px; text-align: center; font-size: .56rem; font-weight: 800; line-height: 1; color: #ffe9b8; text-shadow: 0 1px 2px rgb(0 0 0 / .6); }
    .lb-st.is-short .lb-out { box-shadow: inset 0 4px 0 #f59e0b; }
    .lb-st.is-short .lb-out b { color: #fde68a; }
    .lb-st.is-over .lb-out { box-shadow: inset 0 5px 0 #dc2626; }
    /* Solid planks, never see-through: a plank that is not counted is pale
       raw wood, a plank NPK Plus cannot measure is weathered grey. */
    .lb-st.is-unknown .lb-out, .lb-st.is-unknown .lb-in, .lb-st.is-noflag .lb-out, .lb-st.is-noflag .lb-in,
    .lb-st.is-notneeded .lb-out, .lb-st.is-notneeded .lb-in, .lb-st.is-extra .lb-out, .lb-st.is-extra .lb-in { filter: saturate(.25) brightness(1.22); }
    .lb-st.is-dull .lb-out, .lb-st.is-dull .lb-in { filter: grayscale(1) brightness(.8); }
    .lb-st.is-limit .lb-out b { padding: 2px 0; background: rgb(180 83 9 / .85); }
    .lb-spill { position: absolute; right: 3px; top: -3px; bottom: 0; width: 8px; transform: translateZ(2px); backface-visibility: hidden; -webkit-backface-visibility: hidden;
        border-radius: 5px 5px 2px 2px; opacity: 0; transition: opacity .5s cubic-bezier(.22,1,.36,1) .9s;
        background: repeating-linear-gradient(180deg, rgb(147 205 255 / .95) 0 9px, rgb(56 140 225 / .85) 9px 18px); background-size: 100% 18px; animation: lbFlow .55s linear infinite; }
    .lb-st.is-spill .lb-spill { opacity: .92; }
    @keyframes lbFlow { to { background-position: 0 18px; } }
    /* Where the spill lands: a pool on the ground in front of the plank,
       spreading with rings, turning with the barrel. */
    .lb-pool { position: absolute; left: 50%; bottom: 0; width: 92px; height: 58px; margin-left: -40px; border-radius: 50%; pointer-events: none;
        transform: translateY(50%) translateZ(30px) rotateX(90deg) scale(.2); opacity: 0;
        background: radial-gradient(closest-side, rgb(160 214 255 / .92), rgb(70 150 225 / .78) 62%, rgb(56 140 225 / 0));
        transition: transform 1.1s cubic-bezier(.22,1,.36,1) 1.2s, opacity .6s cubic-bezier(.22,1,.36,1) 1.1s; }
    .lb-pool::before, .lb-pool::after { content: ""; position: absolute; left: 50%; top: 42%; width: 18px; height: 12px; margin: -6px 0 0 -9px; border-radius: 50%;
        border: 2px solid rgb(225 242 255 / .85); opacity: 0; animation: lbRing 1.6s ease-out infinite; }
    .lb-pool::after { animation-delay: .8s; }
    @keyframes lbRing { 0% { transform: scale(.4); opacity: .9; } 100% { transform: scale(3.4); opacity: 0; } }
    .lb-st.is-spill .lb-pool { opacity: 1; transform: translateY(50%) translateZ(30px) rotateX(90deg) scale(1); }
    .lb-water { position: absolute; inset: 0; transform-style: preserve-3d; transform-origin: 50% 100%; transform: scaleY(var(--f, .001));
        transition: transform 1.25s cubic-bezier(.22,1,.36,1) .35s; }
    .lb-wf { position: absolute; left: 50%; bottom: 0; height: 190px; background: linear-gradient(180deg, rgb(120 190 245 / .5), rgb(30 100 190 / .7)); }
    .lb-top { position: absolute; left: 50%; top: 0; border-radius: 999px; transform: translateY(-50%) rotateX(90deg);
        background: radial-gradient(circle at 38% 40%, rgb(214 240 255 / .95), rgb(96 172 236 / .9) 45%, rgb(36 108 200 / .92) 80%); background-size: 160% 160%;
        animation: lbShine 4.5s ease-in-out infinite alternate; }
    @keyframes lbShine { to { background-position: 100% 60%; } }
    .lb-floor, .lb-shadow, .lb-rim { position: absolute; left: 50%; border-radius: 999px; transform: translateY(-50%) rotateX(90deg); }
    .lb-floor { background: radial-gradient(circle, #6b4219, #4a2c0f); }
    .lb-shadow { background: radial-gradient(closest-side, rgb(20 33 12 / .38), rgb(20 33 12 / 0)); }
    .lb-rim { border: 2px dashed rgb(74 124 42 / .7); }
    html.dark .lb-rim { border-color: rgb(165 201 126 / .7); }
    .lb-barrel.is-rest .lb-st { height: 22px !important; transition: none; }
    .lb-barrel.is-rest .lb-water { transform: scaleY(.001) !important; transition: none; }
    .lb-barrel.is-rest .lb-spill, .lb-barrel.is-rest .lb-pool { opacity: 0 !important; transition: none; }
    .lb-tag { position: absolute; left: .7rem; top: .6rem; max-width: calc(100% - 4rem); padding: .3rem .6rem; border-radius: .7rem; font-size: .7rem; font-weight: 700; line-height: 1.35;
        color: #2d5016; background: rgb(255 255 255 / .85); border: 1px dashed rgb(74 124 42 / .6); pointer-events: none; }
    html.dark .lb-tag { color: #d5e3c5; background: rgb(21 27 18 / .85); }
    .lb-info { position: absolute; right: .6rem; top: .55rem; z-index: 2; display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 999px; cursor: pointer;
        font-family: Georgia, 'Times New Roman', serif; font-style: italic; font-weight: 700; font-size: 1.05rem; color: #2d5016; background: rgb(255 255 255 / .9);
        border: 1px solid rgb(74 124 42 / .5); box-shadow: 0 6px 14px -8px rgb(0 0 0 / .5); transition: transform .28s cubic-bezier(.22,1,.36,1); }
    .lb-info:hover { transform: scale(1.08); }
    html.dark .lb-info { color: #d5e3c5; background: rgb(21 27 18 / .9); }
    /* "The water is your yield": an arrow drawn by hand onto the stage. */
    .lb-arrow { position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; overflow: visible; opacity: 0; transition: opacity .3s cubic-bezier(.22,1,.36,1); }
    .lb-arrow.is-on { opacity: 1; }
    .lb-arrow path { fill: none; stroke: #1d6fd1; stroke-width: 2.6; stroke-linecap: round; stroke-linejoin: round; }
    html.dark .lb-arrow path { stroke: #7cc0ff; }
    .lb-arrow .lb-ap { transition: stroke-dashoffset 1s cubic-bezier(.22,1,.36,1); }
    .lb-arrow .lb-ah { opacity: 0; transition: opacity .3s cubic-bezier(.22,1,.36,1) .85s; }
    .lb-arrow.is-drawn .lb-ah { opacity: 1; }
    .lb-alab b { display: block; margin-top: .15rem; font-size: .84rem; font-style: normal; line-height: 1.15; }
    .lb-alab small { display: block; margin-top: .1rem; font-size: .7rem; font-style: normal; font-weight: 800; line-height: 1.15; }
    .lb-alab { position: absolute; right: .45rem; top: 42%; width: 5.8rem; padding: .25rem .35rem; border-radius: .55rem; text-align: center; pointer-events: none;
        font-family: var(--font-heading); font-style: italic; font-weight: 800; font-size: .74rem; line-height: 1.15; color: #1d6fd1; background: rgb(255 255 255 / .82);
        transform: rotate(-5deg) scale(.9); opacity: 0; transition: opacity .4s cubic-bezier(.22,1,.36,1), transform .4s cubic-bezier(.22,1,.36,1); }
    .lb-alab.is-on { opacity: 1; transform: rotate(-5deg) scale(1); }
    html.dark .lb-alab { color: #7cc0ff; background: rgb(21 27 18 / .82); }
    .lb-turn { position: absolute; right: .7rem; bottom: .6rem; display: flex; align-items: center; gap: .3rem; font-size: .68rem; font-weight: 700; color: var(--color-gray-500);
        pointer-events: none; transition: opacity .5s cubic-bezier(.22,1,.36,1); }
    .lb-turn svg { width: 1rem; height: 1rem; animation: lbNudge 2.4s ease-in-out infinite; }
    @keyframes lbNudge { 50% { transform: translateX(4px); } }
    .lb-stage.was-turned .lb-turn { opacity: 0; }
    .lb-key { display: grid; gap: .3rem; margin-top: .7rem; }
    .lb-k { display: grid; grid-template-columns: .7rem minmax(0, 1fr) 3.2rem 4.4rem; gap: .45rem; align-items: center; width: 100%; padding: .35rem .45rem; border-radius: .6rem; text-align: left;
        font-size: .74rem; cursor: pointer; border: 0; background: none; transition: background-color .28s cubic-bezier(.22,1,.36,1); }
    .lb-k:hover { background: var(--color-gray-50); }
    html.dark .lb-k:hover { background: #121a0d; }
    .lb-k > i { width: .7rem; height: 1.1rem; border-radius: 2px; background: linear-gradient(90deg, #6f4519, #c4874a, #6f4519); }
    .lb-k.is-dull > i { filter: grayscale(1); opacity: .7; } .lb-k.is-unknown > i, .lb-k.is-noflag > i, .lb-k.is-notneeded > i, .lb-k.is-extra > i { opacity: .45; }
    .lb-k b { font-weight: 800; color: var(--color-gray-800); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lb-k b small { font-weight: 700; color: var(--color-gray-400); }
    .lb-kbar { position: relative; height: .4rem; border-radius: 999px; background: var(--color-gray-100); overflow: hidden; }
    .lb-kbar span { position: absolute; left: 0; top: 0; bottom: 0; border-radius: 999px; background: #4a7c2a; transition: width .6s cubic-bezier(.22,1,.36,1); }
    .lb-k.is-short .lb-kbar span { background: #f59e0b; } .lb-k.is-over .lb-kbar span { background: #dc2626; }
    .lb-k.is-dull .lb-kbar span, .lb-k.is-unknown .lb-kbar span, .lb-k.is-noflag .lb-kbar span, .lb-k.is-notneeded .lb-kbar span, .lb-k.is-extra .lb-kbar span { background: var(--color-gray-300, #cbd5e1); }
    .lb-k em { font-style: normal; font-size: .66rem; font-weight: 800; text-align: right; color: var(--color-gray-500); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lb-k.is-short em { color: #b45309; } .lb-k.is-over em { color: #b91c1c; } .lb-k.is-right em, .lb-k.is-fixed em, .lb-k.is-testok em { color: #2d5016; }
    .lb-reach { display: flex; align-items: baseline; gap: .55rem; margin-top: .7rem; font-size: .8rem; line-height: 1.45; color: var(--color-gray-600); }
    .lb-reach[hidden] { display: none; }
    .lb-reach b { flex: none; font-family: var(--font-heading); font-size: 1.7rem; font-weight: 800; line-height: 1; color: #1d6fd1; }
    html.dark .lb-reach b { color: #7cc0ff; }
    .lb-reach .lb-harvest { display: block; margin-top: .35rem; font-size: .76rem; color: var(--color-gray-500); }
    .lb-reach .lb-harvest b { display: inline; font-size: inherit; font-family: inherit; color: var(--color-gray-700); }
    html.dark .lb-reach .lb-harvest b { color: #e8efe1; }
    .lb-k { cursor: pointer; text-align: left; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const still = () => matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('sm-still');
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    function mount(host, o) {
        o = o || {};
        host.innerHTML = '<div class="lb"><div class="lb-stage" tabindex="0" role="group" aria-label="' + esc(o.label || "Liebig's barrel") + '. Drag, or use the arrow keys, to turn it.">'
            + '<div class="lb-barrel is-rest"></div><svg class="lb-arrow" aria-hidden="true"><path class="lb-ap"/><path class="lb-ah"/></svg>'
            + '<span class="lb-alab" aria-hidden="true">The water is the need met</span><span class="lb-tag">The rim is everything the crop needs.</span>'
            + '<span class="lb-turn" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12h15m0 0l-4-4m4 4l-4 4"/></svg>Drag to turn</span></div>'
            + '<div class="lb-reach" hidden></div><div class="lb-key"></div><div class="lb-say"></div></div>';
        const q = (s) => host.querySelector(s);
        const R = 80, H = 190;
        const el = q('.lb-barrel'), stage = q('.lb-stage'), svg = q('.lb-arrow'), ap = svg.querySelector('.lb-ap'), ah = svg.querySelector('.lb-ah'), alab = q('.lb-alab');
        const disc = (cls, d, y) => '<div class="' + cls + '" style="width:' + d + 'px;height:' + d + 'px;margin-left:' + (-d / 2) + 'px;top:' + y + 'px"></div>';
        let ry = -18, rx = -16, vel = 0, drag = null, aim = null, on = false, raf = 0, t0 = performance.now(), lim = -1, level = 0, arrowT = 0;
        let n = 0, ang = 0, built = '', sts = [], water = null, top = null, rowsNow = null, levelNow = 0, limNow = -1;
        const build = (list) => {
            const sig = list.map((x) => x.k).join(',');
            if (sig === built) return;
            built = sig; n = list.length; ang = 360 / n; lim = -1;
            const W = Math.ceil(2 * R * Math.tan(Math.PI / n)) + 1, RW = R - 4, WW = Math.ceil(2 * RW * Math.tan(Math.PI / n)) + 1;
            el.innerHTML = disc('lb-shadow', 2 * R * 1.7, H + 2) + disc('lb-floor', 2 * R, H)
                + list.map((s, i) => '<div class="lb-st" style="width:' + W + 'px;margin-left:' + (-W / 2) + 'px;transform:rotateY(' + (i * ang) + 'deg) translateZ(' + R + 'px);--d:' + (i * 55) + 'ms">'
                    + '<div class="lb-out"><b>' + esc(s.t) + '</b><small></small></div><div class="lb-in"></div><div class="lb-spill"></div><div class="lb-pool"></div></div>').join('')
                + '<div class="lb-water">' + list.map((s, i) => '<div class="lb-wf" style="width:' + WW + 'px;margin-left:' + (-WW / 2) + 'px;transform:rotateY(' + (i * ang) + 'deg) translateZ(' + RW + 'px)"></div>').join('')
                + disc('lb-top', 2 * RW + 3, 0) + '</div>' + disc('lb-rim', 2 * R + 16, 0);
            sts = [...el.querySelectorAll('.lb-st')]; water = el.querySelector('.lb-water'); top = el.querySelector('.lb-top');
        };
        const apply = () => {
            const idle = !drag && aim === null && Math.abs(vel) < 0.05 && !still();
            if (!idle) t0 = performance.now();
            const sway = idle ? Math.sin((performance.now() - t0) / 1700) * 7 : 0;
            el.style.transform = 'rotateX(' + rx + 'deg) rotateY(' + (ry + sway) + 'deg)';
        };
        const loop = () => {
            raf = 0;
            if (!on || !host.isConnected) { on = false; return; }
            if (!drag) {
                if (aim !== null) { const d = aim - ry; ry += d * 0.09; if (Math.abs(d) < 0.25) { ry = aim; aim = null; } }
                else if (Math.abs(vel) > 0.05) { ry += vel; vel *= 0.93; }
            }
            apply();
            raf = requestAnimationFrame(loop);
        };
        const face = (i) => {
            const target = -i * ang, d = ((target - ry) % 360 + 540) % 360 - 180;
            if (still()) { ry += d; aim = null; apply(); } else aim = ry + d;
        };
        const drawArrow = (animate) => {
            if (!top) return;
            const sr = stage.getBoundingClientRect(), tr = top.getBoundingClientRect(), lr = alab.getBoundingClientRect();
            if (!sr.width || level <= 2) { svg.classList.remove('is-on', 'is-drawn'); alab.classList.remove('is-on'); return; }
            const tx = tr.left + tr.width / 2 - sr.left, ty = tr.top + tr.height / 2 - sr.top;
            const sx = lr.left - sr.left - 3, sy = lr.top + lr.height * 0.35 - sr.top;
            const ex = tx + 12, ey = ty - 4, c1x = sx - 26, c1y = sy - 34, c2x = ex + 40, c2y = Math.min(sy, ey) - 40;
            svg.setAttribute('viewBox', '0 0 ' + sr.width + ' ' + sr.height);
            ap.setAttribute('d', 'M' + sx + ',' + sy + ' C' + c1x + ',' + c1y + ' ' + c2x + ',' + c2y + ' ' + ex + ',' + ey);
            const a = Math.atan2(ey - c2y, ex - c2x), L = 10;
            ah.setAttribute('d', 'M' + (ex - L * Math.cos(a - 0.5)) + ',' + (ey - L * Math.sin(a - 0.5)) + ' L' + ex + ',' + ey + ' L' + (ex - L * Math.cos(a + 0.5)) + ',' + (ey - L * Math.sin(a + 0.5)));
            const len = ap.getTotalLength();
            ap.style.strokeDasharray = len;
            alab.classList.add('is-on'); svg.classList.add('is-on');
            if (animate && !still()) {
                svg.classList.remove('is-drawn'); ap.style.transition = 'none'; ap.style.strokeDashoffset = len; void ap.getBoundingClientRect();
                requestAnimationFrame(() => { ap.style.transition = ''; ap.style.strokeDashoffset = 0; svg.classList.add('is-drawn'); });
            } else { ap.style.strokeDashoffset = 0; svg.classList.add('is-drawn'); }
        };
        const arrowSoon = (animate) => { clearTimeout(arrowT); if (!on) return; arrowT = setTimeout(() => drawArrow(animate), still() ? 30 : 1750); };
        stage.addEventListener('pointerdown', (e) => {
            if (e.button) return;
            drag = { x: e.clientX, y: e.clientY }; vel = 0; aim = null;
            stage.classList.add('is-grab', 'was-turned');
            try { stage.setPointerCapture(e.pointerId); } catch (_) { /* fine */ }
        });
        stage.addEventListener('pointermove', (e) => {
            if (!drag) return;
            const dx = e.clientX - drag.x, dy = e.clientY - drag.y;
            drag.x = e.clientX; drag.y = e.clientY;
            ry += dx * 0.55; vel = still() ? 0 : dx * 0.55;
            if (e.pointerType === 'mouse') rx = Math.max(-42, Math.min(-4, rx - dy * 0.3));
            apply();
        });
        const up = () => { if (!drag) return; drag = null; stage.classList.remove('is-grab'); drawArrow(false); };
        stage.addEventListener('pointerup', up); stage.addEventListener('pointercancel', up);
        stage.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') { e.preventDefault(); stage.classList.add('was-turned'); const t = (aim ?? ry) + (e.key === 'ArrowLeft' ? ang : -ang); if (still()) { ry = t; apply(); } else aim = t; }
        });
        window.addEventListener('resize', () => { if (on) drawArrow(false); });
        q('.lb-key').addEventListener('click', (e) => { const b = e.target.closest('.lb-k'); if (b) { stage.classList.add('was-turned'); face(Number(b.dataset.i)); } });
        const setRows = () => {
            if (!rowsNow) return;
            rowsNow.forEach((r, i) => {
                if (!sts[i]) return;
                sts[i].style.height = r.px + 'px';
                sts[i].className = 'lb-st is-' + r.state + (i === limNow ? ' is-limit' : '') + (i === limNow && levelNow < H - 1 && levelNow > 0 ? ' is-spill' : '');
                sts[i].querySelector('small').textContent = r.pct;
            });
            const moved = Math.abs(levelNow - level) > 1;
            level = levelNow;
            water.style.setProperty('--f', Math.max(0.001, levelNow / H));
            if (limNow !== lim && limNow >= 0 && on) face(limNow);
            lim = limNow;
            if (moved) arrowSoon(false);
        };
        const WORD = { short: 'Short', right: 'Enough', over: 'Too much', noflag: 'Not flagged', dull: 'Not measured' };
        return {
            draw(d) {
                build(d.staves);
                rowsNow = d.rows.map((r) => Object.assign({}, r, { px: r.ratio === null || r.ratio === undefined ? H : Math.max(22, Math.round(H * Math.min(1.22, r.ratio))) }));
                const measured = rowsNow.map((r, i) => [r, i]).filter(([r]) => r.ratio !== null && r.ratio !== undefined && r.state !== 'dull');
                limNow = -1; levelNow = 0;
                if (measured.length) { [, limNow] = measured.reduce((a, b) => (b[0].px < a[0].px ? b : a)); levelNow = Math.min(H, rowsNow[limNow].px); }
                setRows();
                q('.lb-tag').textContent = d.tag || 'The rim is everything the crop needs.';
                alab.innerHTML = d.arrow || 'The water is the need met';
                const rc = q('.lb-reach');
                rc.hidden = !d.reach;
                rc.innerHTML = d.reach || '';
                q('.lb-key').innerHTML = d.staves.map((s, i) => {
                    const r = rowsNow[i];
                    return '<button type="button" class="lb-k is-' + esc(r.state) + '" data-i="' + i + '" title="' + esc(r.note || '') + '"><i></i><b>' + esc(s.name || s.t) + (s.name && s.name !== s.t ? ' <small>' + esc(s.t) + '</small>' : '') + '</b>'
                        + '<span class="lb-kbar"><span style="width:' + (r.ratio === null || r.ratio === undefined ? (r.state === 'dull' ? 100 : 0) : Math.min(100, r.ratio * 100)) + '%"></span></span><em>' + esc(r.word || WORD[r.state] || '') + '</em></button>';
                }).join('');
                q('.lb-say').innerHTML = d.say || '';
                return { limit: limNow, level: levelNow / H };
            },
            enter() {
                on = true;
                el.classList.add('is-rest');
                svg.classList.remove('is-on', 'is-drawn'); alab.classList.remove('is-on');
                void el.offsetWidth;
                requestAnimationFrame(() => requestAnimationFrame(() => el.classList.remove('is-rest')));
                if (lim >= 0) face(lim);
                if (!raf) raf = requestAnimationFrame(loop);
                arrowSoon(true);
            },
            leave() { on = false; clearTimeout(arrowT); },
            face,
        };
    }
    window.aneeBarrel = { mount };
})();
</script>
@endpush
@endonce
